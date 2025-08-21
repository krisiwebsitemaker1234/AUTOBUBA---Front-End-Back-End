<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all requests
error_log("Reschedule booking request received: " . print_r($_POST, true));

include_once 'config.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? 0;
$new_date = $_POST['new_date'] ?? '';
$new_time_slot = $_POST['new_time_slot'] ?? '';

error_log("Processing reschedule for user_id: $user_id, booking_id: $booking_id, new_date: $new_date, new_time_slot: $new_time_slot");

try {
    // Validate inputs
    if (empty($new_date) || empty($new_time_slot)) {
        error_log("Missing required fields");
        echo json_encode(['success' => false, 'message' => 'Please provide both date and time slot']);
        exit();
    }
    
    // Validate date is in the future
    $booking_date = new DateTime($new_date);
    $today = new DateTime();
    if ($booking_date <= $today) {
        error_log("Invalid date - not in future");
        echo json_encode(['success' => false, 'message' => 'Please select a future date']);
        exit();
    }
    
    // Validate time slot
    $valid_time_slots = ['morning', 'afternoon', 'evening'];
    if (!in_array($new_time_slot, $valid_time_slots)) {
        error_log("Invalid time slot: $new_time_slot");
        echo json_encode(['success' => false, 'message' => 'Invalid time slot']);
        exit();
    }
    
    // Verify booking belongs to user and can be rescheduled
    $verify_stmt = $conn->prepare("
        SELECT id, car_model, status 
        FROM test_drive_bookings 
        WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
    ");
    $verify_stmt->bind_param("ii", $booking_id, $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Booking not found or cannot be rescheduled");
        echo json_encode(['success' => false, 'message' => 'Booking not found or cannot be rescheduled']);
        exit();
    }
    
    $booking = $result->fetch_assoc();
    $verify_stmt->close();
    
    error_log("Found booking: " . print_r($booking, true));
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update booking
        $reschedule_stmt = $conn->prepare("
            UPDATE test_drive_bookings 
            SET preferred_date = ?, time_slot = ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $reschedule_stmt->bind_param("ssii", $new_date, $new_time_slot, $booking_id, $user_id);
        
        if (!$reschedule_stmt->execute()) {
            throw new Exception('Failed to reschedule booking: ' . $reschedule_stmt->error);
        }
        
        $affected_rows = $reschedule_stmt->affected_rows;
        error_log("Rescheduled booking, affected rows: $affected_rows");
        $reschedule_stmt->close();
        
        // Update calendar event if it exists
        $event_time = '';
        switch($new_time_slot) {
            case 'morning': 
                $event_time = '09:00:00'; 
                break;
            case 'afternoon': 
                $event_time = '14:00:00'; 
                break;
            case 'evening': 
                $event_time = '17:00:00'; 
                break;
            default: 
                $event_time = '10:00:00';
        }
        
        $event_datetime = $new_date . ' ' . $event_time;
        $end_datetime = date('Y-m-d H:i:s', strtotime($event_datetime . ' +2 hours'));
        
        // Check if calendar table has start_time and end_time columns
        $columns_check = $conn->query("SHOW COLUMNS FROM calendar_events LIKE 'start_time'");
        if ($columns_check->num_rows > 0) {
            $calendar_update = $conn->prepare("
                UPDATE calendar_events 
                SET start_time = ?, end_time = ? 
                WHERE related_id = ? AND event_type = 'test_drive'
            ");
            $calendar_update->bind_param("ssi", $event_datetime, $end_datetime, $booking_id);
            $calendar_update->execute();
            $calendar_update->close();
        } else {
            // Fallback for older schema
            $calendar_update = $conn->prepare("
                UPDATE calendar_events 
                SET event_date = ?, event_time = ? 
                WHERE related_id = ? AND event_type = 'test_drive'
            ");
            $calendar_update->bind_param("ssi", $new_date, $event_time, $booking_id);
            $calendar_update->execute();
            $calendar_update->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        error_log("Successfully rescheduled booking $booking_id");
        echo json_encode([
            'success' => true, 
            'message' => 'Booking rescheduled successfully',
            'booking_id' => $booking_id,
            'new_date' => date('F j, Y', strtotime($new_date)),
            'new_time' => ucfirst($new_time_slot)
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        error_log("Transaction failed: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Reschedule booking error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while rescheduling the booking: ' . $e->getMessage()
    ]);
}
?>
