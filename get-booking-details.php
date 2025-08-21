<?php
include_once 'config.php';

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? 0;

try {
    // Get detailed booking information
    $details_stmt = $conn->prepare("
        SELECT 
            id, first_name, last_name, email, phone, car_model, car_color, 
            location, preferred_date, time_slot, status, license_file,
            newsletter_subscription, created_at, updated_at
        FROM test_drive_bookings 
        WHERE id = ? AND user_id = ?
    ");
    $details_stmt->bind_param("ii", $booking_id, $user_id);
    $details_stmt->execute();
    $result = $details_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    $booking = $result->fetch_assoc();
    $details_stmt->close();
    
    // Format the data for display
    $booking['formatted_date'] = date('F j, Y', strtotime($booking['preferred_date']));
    $booking['formatted_time'] = ucfirst($booking['time_slot']);
    $booking['formatted_location'] = ucfirst(str_replace(['_', '-'], ' ', $booking['location']));
    $booking['formatted_created'] = date('F j, Y \a\t g:i A', strtotime($booking['created_at']));
    $booking['formatted_updated'] = $booking['updated_at'] ? date('F j, Y \a\t g:i A', strtotime($booking['updated_at'])) : null;
    
    // Format time slot for better display
    switch($booking['time_slot']) {
        case 'morning':
            $booking['time_range'] = '9:00 AM - 12:00 PM';
            break;
        case 'afternoon':
            $booking['time_range'] = '12:00 PM - 5:00 PM';
            break;
        case 'evening':
            $booking['time_range'] = '5:00 PM - 8:00 PM';
            break;
        default:
            $booking['time_range'] = 'Time not specified';
    }
    
    // Format location for better display
    switch($booking['location']) {
        case 'downtown':
            $booking['location_full'] = 'Downtown Showroom - 123 Main St';
            break;
        case 'north':
            $booking['location_full'] = 'North City Dealership - 456 North Ave';
            break;
        case 'west':
            $booking['location_full'] = 'West Side Motors - 789 West Blvd';
            break;
        case 'east':
            $booking['location_full'] = 'East End Auto Center - 321 East St';
            break;
        default:
            $booking['location_full'] = ucfirst(str_replace(['_', '-'], ' ', $booking['location']));
    }
    
    echo json_encode(['success' => true, 'booking' => $booking]);
    
} catch (Exception $e) {
    error_log('Get booking details error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while loading booking details'
    ]);
}
?>
