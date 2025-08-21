<?php
include_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get pre-selected car if coming from index
$preselected_car = null;
if (isset($_GET['car_id'])) {
    $car_id = $_GET['car_id'];
    $car_stmt = $conn->prepare("SELECT id, make, model, year, price, image_path, status FROM vehicles WHERE id = ?");
    $car_stmt->bind_param("i", $car_id);
    $car_stmt->execute();
    $preselected_car = $car_stmt->get_result()->fetch_assoc();
    $car_stmt->close();
}

// Function to check if column exists in table
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Handle AJAX car search/filter request
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json');
    
    try {
        $whereConditions = ["status != 'Sold'"]; // Exclude sold cars
        $params = [];
        $types = "";

        // Process search filter
        if (!empty($_POST['search'])) {
            $search = '%' . $_POST['search'] . '%';
            $whereConditions[] = "(make LIKE ? OR model LIKE ? OR CONCAT(make, ' ', model) LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }

        // Build WHERE clause
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);

        // Get vehicles with filters - limit to 12 for search results
        $limit = 12;
        $vehicleQuery = "SELECT id, make, model, year, price, image_path, status FROM vehicles $whereClause ORDER BY 
                        CASE WHEN status = 'In Stock' THEN 1 ELSE 2 END, id DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = $conn->prepare($vehicleQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $cars = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Check if car is not in stock
                $isRented = ($row['status'] == 'Reserved' || $row['status'] == 'Rented');
                $notInStock = $isRented;
                
                // Handle image path properly
                $image_path = $row['image_path'];
                if (empty($image_path) || !file_exists($image_path)) {
                    $image_path = 'img/car placeholder.webp';
                }

                $cars[] = [
                    'id' => $row['id'],
                    'make' => $row['make'],
                    'model' => $row['model'],
                    'year' => $row['year'],
                    'price' => $row['price'],
                    'image_path' => $image_path,
                    'status' => $row['status'],
                    'not_in_stock' => $notInStock
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'cars' => $cars,
            'total' => count($cars)
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error searching cars: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    // Set content type for JSON response
    header('Content-Type: application/json');
    
    try {
        // Validate required fields (removed carColor from required fields)
        $required_fields = ['firstName', 'lastName', 'email', 'phone', 'carModel', 'location', 'preferredDate', 'timeSlot'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
            ]);
            exit();
        }
        
        // Sanitize and validate input
        $first_name = trim($_POST['firstName']);
        $last_name = trim($_POST['lastName']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $phone = trim($_POST['phone']);
        $car_model = trim($_POST['carModel']);
        $car_color = 'Standard'; // Default color since we removed color selection
        $location = trim($_POST['location']);
        $preferred_date = $_POST['preferredDate'];
        $time_slot = $_POST['timeSlot'];
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        
        // Validate email
        if (!$email) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            exit();
        }
        
        // Validate date (must be in the future)
        $booking_date = new DateTime($preferred_date);
        $today = new DateTime();
        if ($booking_date <= $today) {
            echo json_encode([
                'success' => false,
                'message' => 'Booking date must be in the future'
            ]);
            exit();
        }
        
        // Validate time slot
        $valid_time_slots = ['morning', 'afternoon', 'evening'];
        if (!in_array($time_slot, $valid_time_slots)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid time slot'
            ]);
            exit();
        }
        
        // Handle file upload for driver's license
        $license_file = null;
        if (isset($_FILES['driverLicense']) && $_FILES['driverLicense']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/licenses/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to create upload directory'
                    ]);
                    exit();
                }
            }
            
            // Validate file type and size
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['driverLicense']['type'], $allowed_types)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed.'
                ]);
                exit();
            }
            
            if ($_FILES['driverLicense']['size'] > $max_size) {
                echo json_encode([
                    'success' => false,
                    'message' => 'File size too large. Maximum size is 5MB.'
                ]);
                exit();
            }
            
            $file_extension = pathinfo($_FILES['driverLicense']['name'], PATHINFO_EXTENSION);
            $license_file = $upload_dir . uniqid('license_') . '.' . $file_extension;
            
            if (!move_uploaded_file($_FILES['driverLicense']['tmp_name'], $license_file)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to upload driver license file'
                ]);
                exit();
            }
        } else {
            // Driver's license is required
            echo json_encode([
                'success' => false,
                'message' => 'Driver license upload is required'
            ]);
            exit();
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert booking into database
            $booking_stmt = $conn->prepare("
                INSERT INTO test_drive_bookings 
                (user_id, first_name, last_name, email, phone, car_model, car_color, location, preferred_date, time_slot, license_file, newsletter_subscription, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $booking_stmt->bind_param("issssssssssi", 
                $user_id, $first_name, $last_name, $email, $phone, 
                $car_model, $car_color, $location, $preferred_date, 
                $time_slot, $license_file, $newsletter
            );
            
            if (!$booking_stmt->execute()) {
                throw new Exception('Failed to create booking: ' . $booking_stmt->error);
            }
            
            $booking_id = $conn->insert_id;
            $booking_stmt->close();
            
            // Also insert into calendar_events table for calendar integration
            $event_title = "Test Drive - $car_model";
            $event_description = "Test drive appointment for $first_name $last_name\nCar: $car_model\nLocation: $location\nContact: $phone";
            
            // Convert time slot to specific time
            $event_time = '';
            switch($time_slot) {
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
            
            // Create proper datetime for calendar event
            $event_datetime = $preferred_date . ' ' . $event_time;
            $end_datetime = date('Y-m-d H:i:s', strtotime($event_datetime . ' +2 hours'));
            
            // Check which columns exist in calendar_events table and build query accordingly
            $has_start_time = columnExists($conn, 'calendar_events', 'start_time');
            $has_end_time = columnExists($conn, 'calendar_events', 'end_time');
            $has_event_date = columnExists($conn, 'calendar_events', 'event_date');
            $has_event_time = columnExists($conn, 'calendar_events', 'event_time');
            $has_related_id = columnExists($conn, 'calendar_events', 'related_id');
            $has_notes = columnExists($conn, 'calendar_events', 'notes');
            $has_description = columnExists($conn, 'calendar_events', 'description');
            
            // Build the calendar insert query based on available columns
            $calendar_columns = ['user_id', 'title', 'event_type'];
            $calendar_values = ['?', '?', '?'];
            $calendar_params = [$user_id, $event_title, 'test_drive'];
            $calendar_types = 'iss';
            
            if ($has_start_time) {
                $calendar_columns[] = 'start_time';
                $calendar_values[] = '?';
                $calendar_params[] = $event_datetime;
                $calendar_types .= 's';
            }
            
            if ($has_end_time) {
                $calendar_columns[] = 'end_time';
                $calendar_values[] = '?';
                $calendar_params[] = $end_datetime;
                $calendar_types .= 's';
            }
            
            if ($has_event_date && !$has_start_time) {
                $calendar_columns[] = 'event_date';
                $calendar_values[] = '?';
                $calendar_params[] = $preferred_date;
                $calendar_types .= 's';
            }
            
            if ($has_event_time && !$has_start_time) {
                $calendar_columns[] = 'event_time';
                $calendar_values[] = '?';
                $calendar_params[] = $event_time;
                $calendar_types .= 's';
            }
            
            if ($has_related_id) {
                $calendar_columns[] = 'related_id';
                $calendar_values[] = '?';
                $calendar_params[] = $booking_id;
                $calendar_types .= 'i';
            }
            
            if ($has_notes) {
                $calendar_columns[] = 'notes';
                $calendar_values[] = '?';
                $calendar_params[] = $event_description;
                $calendar_types .= 's';
            } elseif ($has_description) {
                $calendar_columns[] = 'description';
                $calendar_values[] = '?';
                $calendar_params[] = $event_description;
                $calendar_types .= 's';
            }
            
            $calendar_columns[] = 'created_at';
            $calendar_values[] = 'NOW()';
            
            $calendar_sql = "INSERT INTO calendar_events (" . implode(', ', $calendar_columns) . ") VALUES (" . implode(', ', $calendar_values) . ")";
            
            $calendar_stmt = $conn->prepare($calendar_sql);
            
            if (!empty($calendar_params)) {
                $calendar_stmt->bind_param($calendar_types, ...$calendar_params);
            }
            
            if (!$calendar_stmt->execute()) {
                throw new Exception('Failed to create calendar event: ' . $calendar_stmt->error);
            }
            
            $calendar_stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Test drive booking confirmed!',
                'booking_id' => $booking_id
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            
            // Delete uploaded file if it exists
            if ($license_file && file_exists($license_file)) {
                unlink($license_file);
            }
            
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log('Test drive booking error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your booking. Please try again.'
        ]);
    }
    
    exit();
}

// Get available vehicles for selection - limit to 6 initially, prioritize in-stock cars
$vehicles_query = "SELECT id, make, model, year, price, image_path, status FROM vehicles 
                   WHERE status != 'Sold' 
                   ORDER BY CASE WHEN status = 'In Stock' THEN 1 ELSE 2 END, id DESC 
                   LIMIT 9";
$vehicles_result = $conn->query($vehicles_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/Booking.css">
    <link rel="stylesheet" href="loading.css">
    <link rel="icon" href="img/autobuba logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>AUTOBUBA - Book A Test Drive</title>
    <style>
        /* Error message styling */
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .error-message.show {
            display: flex;
            animation: slideInDown 0.3s ease;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-message-inline {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .success-message-inline.show {
            display: flex;
            animation: slideInDown 0.3s ease;
        }
        
        /* Form validation styling */
        .form-group input.error,
        .form-group select.error {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        .form-group input.success,
        .form-group select.success {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #d90429;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Search Bar Styling */
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-container {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-group {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Poppins', sans-serif;
        }

        .search-input:focus {
            outline: none;
            border-color: #d90429;
            box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
            transform: translateY(-2px);
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #d90429, #ef233c);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(135deg, #c70327, #d90429);
            transform: translateY(-50%) scale(1.05);
        }

        .clear-search-btn {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .clear-search-btn:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
        }

        /* Search Results */
        .search-results {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            display: none;
        }

        .search-results.show {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        .search-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-results-count {
            font-weight: 600;
            color: #333;
        }

        .close-search-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-search-btn:hover {
            color: #d90429;
        }

        /* Not in Stock Styles */
        .car-card.not-in-stock {
            position: relative;
            opacity: 0.8;
        }

        .not-in-stock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 20px 20px 0 0;
        }

        .not-in-stock-label {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            transform: rotate(-5deg);
            animation: pulse 2s infinite;
        }

        .not-in-stock-label i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .not-in-stock-label span {
            font-weight: 700;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .not-in-stock-label small {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        @keyframes pulse {
            0%, 100% {
                transform: rotate(-5deg) scale(1);
            }
            50% {
                transform: rotate(-5deg) scale(1.05);
            }
        }
        
        /* Rest of your existing styles... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Header Styling */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #d90429, #ef233c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo a {
            text-decoration: none;
            color: inherit;
        }

        .cart-icon {
            font-size: 1.5rem;
            color: #d90429;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .cart-icon:hover {
            transform: scale(1.1);
        }

        /* Back to Index Link */
        .back-to-index {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .back-to-index:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
            color: #fff;
            text-decoration: none;
        }

        /* Breadcrumb Styling */
        .breadcrumb {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .breadcrumb span {
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .breadcrumb span.active {
            background: linear-gradient(135deg, #d90429, #ef233c);
            color: white;
            box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
        }

        .breadcrumb span:not(.active) {
            color: #666;
        }

        /* Page Container */
        .page {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page.active {
            display: block;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Section Title */
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #333, #666);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Pre-selected Car Display */
        .selected-car-display {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            border: 2px solid #d90429;
            box-shadow: 0 10px 30px rgba(217, 4, 41, 0.1);
            position: relative;
            overflow: hidden;
        }

        .selected-car-display::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #d90429, #ef233c, #d90429);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .selected-car-image {
            width: 150px;
            height: 100px;
            background-size: cover;
            background-position: center;
            border-radius: 15px;
            margin-right: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .selected-car-details h3 {
            margin: 0 0 15px 0;
            color: #d90429;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .selected-car-details p {
            margin: 8px 0;
            color: #666;
            font-weight: 500;
        }

        /* Car Cards Grid */
        .car-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .car-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 3px solid transparent;
            position: relative;
        }

        .car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .car-card.selected {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff8, #e8f5e8);
        }

        .car-card.preselected {
            border-color: #d90429;
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
        }

        .car-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .car-info {
            padding: 25px;
        }

        .car-model {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .car-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .car-specs {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .car-spec {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #555;
        }

        .car-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: #d90429;
            text-align: center;
            margin-top: 15px;
        }

        /* Form Styling */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d90429;
            box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
            transform: translateY(-2px);
        }

        /* File Upload Styling */
        .file-upload {
            border: 3px dashed #d90429;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            position: relative;
            overflow: hidden;
        }

        .file-upload:hover {
            border-color: #c70327;
            background: linear-gradient(135deg, #ffe6e6, #ffd6d6);
            transform: translateY(-2px);
        }

        .file-upload.dragover {
            border-color: #28a745;
            background: linear-gradient(135deg, #f8fff8, #e8f5e8);
        }

        .file-upload-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #d90429;
        }

        .file-upload-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .file-upload-subtext {
            color: #666;
            font-size: 0.9rem;
        }

        .file-upload-input {
            display: none;
        }

        .file-list {
            margin-top: 15px;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .file-name {
            font-weight: 500;
            color: #333;
        }

        .file-size {
            color: #666;
            font-size: 0.9rem;
        }

        /* Checkbox Styling */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 25px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #d90429;
        }

        .checkbox-group label {
            margin: 0;
            color: #666;
            cursor: pointer;
        }

        /* Button Styling */
        .button-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
        }

        .continue-button,
        .confirm-button,
        .back-button {
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .continue-button,
        .confirm-button {
            background: linear-gradient(135deg, #d90429, #ef233c);
            color: white;
            box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
        }

        .continue-button:hover,
        .confirm-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(217, 4, 41, 0.4);
        }

        .back-button {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

        /* Success Modal - Fixed positioning */
.success-message {
    position: fixed;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    overflow: auto;
    padding: 20px;
    box-sizing: border-box;
}

.success-content {
    background: white;
    padding: 40px;
    border-radius: 25px;
    text-align: center;
    max-width: 90vw;
    width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: modalSlideIn 0.5s ease;
    position: relative;
    margin: auto;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    font-size: 2.5rem;
    color: white;
    font-weight: bold;
}

.success-title {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.success-text {
    color: #666;
    line-height: 1.6;
    margin-bottom: 30px;
    font-size: 1rem;
}

.success-button {
    background: linear-gradient(135deg, #d90429, #ef233c);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 10px;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.success-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
}

/* Close button for modal */
.modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #999;
    cursor: pointer;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f8f9fa;
    color: #333;
}

/* Responsive modal */
@media (max-width: 768px) {
    .success-content {
        padding: 30px 20px;
        margin: 20px;
        max-width: calc(100vw - 40px);
        width: auto;
    }
    
    .success-title {
        font-size: 1.5rem;
    }
    
    .success-text {
        font-size: 0.9rem;
    }
    
    .success-button {
        padding: 10px 20px;
        font-size: 0.9rem;
        margin: 5px;
        width: calc(50% - 10px);
        justify-content: center;
    }
    
    .success-icon {
        width: 60px;
        height: 60px;
        font-size: 2rem;
    }
}

/* Ensure modal appears above everything */
.success-message.show {
    display: flex !important;
}

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                padding: 15px 20px;
            }

            .page {
                padding: 25px;
            }

            .section-title {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .car-cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .selected-car-display {
                flex-direction: column;
                text-align: center;
            }

            .selected-car-image {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .button-container {
                flex-direction: column;
                gap: 15px;
            }

            .continue-button,
            .confirm-button,
            .back-button {
                width: 100%;
            }

            .breadcrumb {
                flex-wrap: wrap;
                gap: 10px;
            }

            .breadcrumb span {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .search-container {
                flex-direction: column;
                gap: 15px;
            }

            .search-input-group {
                min-width: 100%;
            }
        }

        /* Loading Animation */
        .continue-button:disabled,
        .confirm-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .continue-button:disabled::after,
        .confirm-button:disabled::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loading-container">
            <!-- Speedometer -->
            <div class="speedometer-container">
                <div class="speedometer">
                    <div class="speedometer-scale">
                        <div class="scale-mark mark-0"><span>0</span></div>
                        <div class="scale-mark mark-20"><span>20</span></div>
                        <div class="scale-mark mark-40"><span>40</span></div>
                        <div class="scale-mark mark-60"><span>60</span></div>
                        <div class="scale-mark mark-80"><span>80</span></div>
                        <div class="scale-mark mark-100"><span>100</span></div>
                    </div>
                    <div class="needle-container">
                        <div class="needle"></div>
                        <div class="needle-center"></div>
                    </div>
                    <div class="speed-display">
                        <span id="speed-value">0</span>
                        <span class="speed-unit">km/h</span>
                    </div>
                </div>
            </div>
            
            <!-- Loading Text -->
            <div class="loading-text">
                <span>L</span>
                <span>O</span>
                <span>A</span>
                <span>D</span>
                <span>I</span>
                <span>N</span>
                <span>G</span>
                <span>.</span>
                <span>.</span>
                <span>.</span>
            </div>
            
            <!-- Progress Bar -->
            <div class="progress-container">
                <div id="progress-bar" class="progress-bar"></div>
            </div>
            
            <!-- AUTOBUBA Text -->
            <div class="autobuba-text">AUTOBUBA</div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo">
                <a href="index.php">AUTOBUBA</a>
            </div>
            <div class="cart-icon">
                <i class="fas fa-car"></i>
            </div>
        </div>
        
        <a href="index.php" class="back-to-index">
            <i class="fas fa-arrow-left"></i>
            Back to Homepage
        </a>
        
        <!-- Error/Success Messages -->
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="errorText"></span>
        </div>
        
        <div class="success-message-inline" id="successMessageInline">
            <i class="fas fa-check-circle"></i>
            <span id="successText"></span>
        </div>
        
        <div class="breadcrumb">
            <span id="step1" class="active">
                <i class="fas fa-car-side"></i>
                Car Selection
            </span>
            <span>›</span>
            <span id="step2">
                <i class="fas fa-calendar-alt"></i>
                Test Drive
            </span>
            <span>›</span>
            <span id="step3">
                <i class="fas fa-check-circle"></i>
                Confirmation
            </span>
        </div>

        <!-- Page 1: Car Selection -->
        <div id="page1" class="page active">
            <h2 class="section-title">
                <i class="fas fa-car-side"></i>
                Select Your Dream Car
            </h2>

            <!-- Search Section -->
            <div class="search-section">
                <h3 style="margin-bottom: 20px; color: #333; font-weight: 600;">
                    <i class="fas fa-search"></i>
                    Search for Your Perfect Car
                </h3>
                <div class="search-container">
                    <div class="search-input-group">
                        <input type="text" 
                               class="search-input" 
                               id="carSearchInput" 
                               placeholder="Search by make, model, or year (e.g., BMW, Mercedes, 2023)..."
                               autocomplete="off">
                        <button class="search-btn" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <button class="clear-search-btn" id="clearSearchBtn">
                        <i class="fas fa-times"></i>
                        Clear Search
                    </button>
                </div>

                <!-- Search Results -->
                <div class="search-results" id="searchResults">
                    <div class="search-results-header">
                        <div class="search-results-count" id="searchResultsCount">
                            Found 0 cars
                        </div>
                        <button class="close-search-btn" id="closeSearchBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="car-cards" id="searchCarCards">
                        <!-- Search results will be populated here -->
                    </div>
                </div>
            </div>
            
            <?php if ($preselected_car): ?>
                <div class="selected-car-display">
                    <div class="selected-car-image" style="background-image: url('<?= $preselected_car['image_path'] ?: 'img/car placeholder.webp' ?>')"></div>
                    <div class="selected-car-details">
                        <h3>
                            <i class="fas fa-star"></i>
                            Pre-selected: <?= htmlspecialchars($preselected_car['make'] . ' ' . $preselected_car['model']) ?>
                        </h3>
                        <p><strong><i class="fas fa-calendar"></i> Year:</strong> <?= $preselected_car['year'] ?></p>
                        <p><strong><i class="fas fa-dollar-sign"></i> Price:</strong> $<?= number_format($preselected_car['price']) ?></p>
                        <?php 
                        $isPreselectedRented = ($preselected_car['status'] == 'Reserved' || $preselected_car['status'] == 'Rented');
                        if ($isPreselectedRented): ?>
                            <p style="color: #dc3545; font-weight: 600;">
                                <i class="fas fa-exclamation-triangle"></i>
                                This car is currently not in stock
                            </p>
                        <?php else: ?>
                            <p style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-check-circle"></i>
                                This car is available for test drive
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="car-cards" id="carCards">
                <?php if ($vehicles_result->num_rows > 0): ?>
                    <?php while($vehicle = $vehicles_result->fetch_assoc()): ?>
                        <?php
                        // Check if car is not in stock
                        $isRented = ($vehicle['status'] == 'Reserved' || $vehicle['status'] == 'Rented');
                        $notInStock = $isRented;
                        
                        // Handle image path properly
                        $image_path = $vehicle['image_path'];
                        if (empty($image_path) || !file_exists($image_path)) {
                            $image_path = 'img/car placeholder.webp';
                        }
                        ?>
                        <div class="car-card <?= ($preselected_car && $vehicle['id'] == $preselected_car['id']) ? 'preselected selected' : '' ?> <?= $notInStock ? 'not-in-stock' : '' ?>" 
                             data-model="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>" 
                             data-id="<?= $vehicle['id'] ?>"
                             data-year="<?= $vehicle['year'] ?>"
                             data-price="<?= $vehicle['price'] ?>"
                             data-status="<?= $vehicle['status'] ?>"
                             data-available="<?= $notInStock ? 'false' : 'true' ?>">
                            <div class="car-image" style="background-image: url('<?= $image_path ?>')">
                                <?php if ($notInStock): ?>
                                    <div class="not-in-stock-overlay">
                                        <div class="not-in-stock-label">
                                            <i class="fa fa-ban"></i>
                                            <span>NOT IN STOCK</span>
                                            <small>Currently Rented</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="car-info">
                                <div class="car-model"><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></div>
                                <div class="car-description">Experience the <?= $vehicle['year'] ?> <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?> with premium features</div>
                                <div class="car-specs">
                                    <div class="car-spec">
                                        <i class="fas fa-calendar"></i>
                                        <?= $vehicle['year'] ?>
                                    </div>
                                    <div class="car-spec">
                                        <i class="fas fa-cog"></i>
                                        Automatic
                                    </div>
                                    <div class="car-spec">
                                        <i class="fas fa-star"></i>
                                        <?= $notInStock ? 'Unavailable' : 'Available' ?>
                                    </div>
                                </div>
                                <div class="car-price">$<?= number_format($vehicle['price']) ?></div>
                                <?php if ($notInStock): ?>
                                    <div style="text-align: center; margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 8px; color: #856404; font-weight: 600;">
                                        <i class="fas fa-info-circle"></i>
                                        This car is currently not available for test drives
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback cars if no vehicles in database -->
                    <div class="car-card" data-model="AUTOBUBA Sedan" data-available="true">
                        <div class="car-image" style="background-image: url('img/car placeholder.webp')"></div>
                        <div class="car-info">
                            <div class="car-model">No Cars</div>
                            <div class="car-description">Available at the moment</div>
                            <div class="car-specs">
                                <div class="car-spec">
                                    <i class="fas fa-bolt"></i>
                                    N/A HP
                                </div>
                                <div class="car-spec">
                                    <i class="fas fa-gas-pump"></i>
                                    N/A MPG
                                </div>
                                <div class="car-spec">
                                    <i class="fas fa-cog"></i>
                                    N/A
                                </div>
                            </div>
                            <div class="car-price">N/A</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="button-container">
                <button class="continue-button" id="continueToTestDrive">
                    <i class="fas fa-arrow-right"></i>
                    Continue to Test Drive
                </button>
            </div>
        </div>

        <!-- Page 2: Test Drive Scheduling -->
        <div id="page2" class="page">
            <div class="selected-car" id="selectedCarSummary"></div>
            
            <form id="testDriveForm" enctype="multipart/form-data">
                <div class="form-container">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-check"></i>
                        Test Drive Information
                    </h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                First Name
                            </label>
                            <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars(explode(' ', $user_data['full_name'] ?? '')[0] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                Last Name
                            </label>
                            <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars(explode(' ', $user_data['full_name'] ?? '', 2)[1] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-id-card"></i>
                            Upload Driver's License
                        </label>
                        <div class="file-upload" id="fileUpload">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">Drag and drop files here or click to browse</div>
                            <div class="file-upload-subtext">Supported formats: PDF, JPG, PNG (Max 5MB)</div>
                            <input type="file" class="file-upload-input" id="fileInput" name="driverLicense" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                        <div class="file-list" id="fileList"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-map-marker-alt"></i>
                            Dealership Location
                        </label>
                        <select id="location" name="location" required>
                            <option value="">Select a location</option>
                            <option value="north">AUTOBUBA Test Drive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-calendar-alt"></i>
                            Preferred Test Drive Date
                        </label>
                        <input type="date" id="dateInput" name="preferredDate" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-clock"></i>
                            Preferred Time Slot
                        </label>
                        <select id="timeSlot" name="timeSlot" required>
                            <option value="">Select a time slot</option>
                            <option value="morning">
                                <i class="fas fa-sun"></i>
                                Morning (9:00 AM - 12:00 PM)
                            </option>
                            <option value="afternoon">
                                <i class="fas fa-sun"></i>
                                Afternoon (12:00 PM - 5:00 PM)
                            </option>
                            <option value="evening">
                                <i class="fas fa-moon"></i>
                                Evening (5:00 PM - 8:00 PM)
                            </option>
                        </select>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter" checked>
                        <label for="newsletter">
                            <i class="fas fa-envelope-open"></i>
                            Subscribe to our newsletter for special offers
                        </label>
                    </div>

                    <!-- Hidden fields for car selection -->
                    <input type="hidden" id="carModel" name="carModel">
                    <input type="hidden" id="carColor" name="carColor" value="Standard">
                    <input type="hidden" id="carId" name="carId">
                </div>
                
                <div class="button-container">
                    <button type="button" class="back-button" id="backToCarSelection">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </button>
                    <button type="submit" class="confirm-button" id="confirmButton">
                        <i class="fas fa-calendar-check"></i>
                        Schedule Test Drive
                    </button>
                </div>
            </form>
        </div>

        <!-- Success Message Modal -->
<div class="success-message" id="successMessage">
    <div class="success-content">
        <button class="modal-close" id="modalClose" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="success-title">Test Drive Scheduled!</div>
        <div class="success-text" id="successText">
            Your appointment has been successfully scheduled and added to your calendar. We'll send a confirmation email to <strong id="confirmationEmail"></strong> with all the details.
            <br><br>
            Our team will contact you shortly to confirm your test drive.
            <br><br>
            You can view your booking in <strong>My Bookings</strong> section.
        </div>
        <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
            <button class="success-button" id="viewBookings">
                <i class="fas fa-calendar-alt"></i>
                View My Bookings
            </button>
            <button class="success-button" id="backToHome">
                <i class="fas fa-home"></i>
                Back to Home
            </button>
        </div>
    </div>
</div>
    </div>

    <script src="loading.js"></script>
    <script>
        // Enhanced booking functionality with search and stock checking
        let selectedCar = null;
        let selectedColor = 'Standard';
        let searchTimeout = null;

        // Utility functions
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            errorText.textContent = message;
            errorDiv.classList.add('show');
            
            setTimeout(() => {
                errorDiv.classList.remove('show');
            }, 5000);
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessageInline');
            const successText = document.getElementById('successText');
            successText.textContent = message;
            successDiv.classList.add('show');
            
            setTimeout(() => {
                successDiv.classList.remove('show');
            }, 5000);
        }

        function showLoading(show = true) {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = show ? 'flex' : 'none';
        }

        function validateForm() {
            const requiredFields = [
                { name: 'firstName', label: 'First Name' },
                { name: 'lastName', label: 'Last Name' },
                { name: 'email', label: 'Email' },
                { name: 'phone', label: 'Phone' },
                { name: 'location', label: 'Location' },
                { name: 'preferredDate', label: 'Date' },
                { name: 'timeSlot', label: 'Time Slot' }
            ];

            let isValid = true;
            const missingFields = [];

            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field.name}"]`);
                if (!element || !element.value.trim()) {
                    element?.classList.add('error');
                    missingFields.push(field.label);
                    isValid = false;
                } else {
                    element?.classList.remove('error');
                    element?.classList.add('success');
                }
            });

            // Check file upload
            const fileInput = document.getElementById('fileInput');
            if (!fileInput.files || fileInput.files.length === 0) {
                missingFields.push('Driver License');
                isValid = false;
            }

            // Check car selection
            if (!selectedCar) {
                missingFields.push('Car Selection');
                isValid = false;
            }

            // Check if selected car is available
            if (selectedCar && selectedCar.available === 'false') {
                showError('The selected car is not available for test drives. Please choose another car.');
                isValid = false;
            }

            if (!isValid && missingFields.length > 0) {
                showError('Please fill in all required fields: ' + missingFields.join(', '));
            }

            return isValid;
        }

        // Search functionality
        function searchCars(query) {
            if (!query || query.trim().length < 2) {
                hideSearchResults();
                return;
            }

            showLoading(true);

            fetch('book-test-drive.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&search=${encodeURIComponent(query.trim())}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySearchResults(data.cars, query);
                } else {
                    showError('Error searching cars: ' + (data.message || 'Unknown error'));
                    hideSearchResults();
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                showError('Oops! Well things like this happen. Looks like that\'s not possible right now, but no problem. Leave a message or call us at +355 69 311 3209 to book your test drive right now! Drive safe!');
                hideSearchResults();
            })
            .finally(() => {
                showLoading(false);
            });
        }

        function displaySearchResults(cars, query) {
            const searchResults = document.getElementById('searchResults');
            const searchCarCards = document.getElementById('searchCarCards');
            const searchResultsCount = document.getElementById('searchResultsCount');

            if (!cars || cars.length === 0) {
                searchResultsCount.textContent = `No cars found for "${query}"`;
                searchCarCards.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h4>No cars found</h4>
                        <p>Try searching with different keywords like make, model, or year.</p>
                    </div>
                `;
            } else {
                searchResultsCount.textContent = `Found ${cars.length} car${cars.length !== 1 ? 's' : ''} for "${query}"`;
                
                const carCardsHTML = cars.map(car => {
                    const notInStock = car.not_in_stock;
                    const availabilityClass = notInStock ? 'not-in-stock' : '';
                    const availabilityText = notInStock ? 'Unavailable' : 'Available';
                    
                    return `
                        <div class="car-card ${availabilityClass}" 
                             data-model="${car.make} ${car.model}" 
                             data-id="${car.id}"
                             data-year="${car.year}"
                             data-price="${car.price}"
                             data-status="${car.status}"
                             data-available="${notInStock ? 'false' : 'true'}">
                            <div class="car-image" style="background-image: url('${car.image_path}')">
                                ${notInStock ? `
                                    <div class="not-in-stock-overlay">
                                        <div class="not-in-stock-label">
                                            <i class="fa fa-ban"></i>
                                            <span>NOT IN STOCK</span>
                                            <small>Currently Rented</small>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                            <div class="car-info">
                                <div class="car-model">${car.make} ${car.model}</div>
                                <div class="car-description">Experience the ${car.year} ${car.make} ${car.model} with premium features</div>
                                <div class="car-specs">
                                    <div class="car-spec">
                                        <i class="fas fa-calendar"></i>
                                        ${car.year}
                                    </div>
                                    <div class="car-spec">
                                        <i class="fas fa-cog"></i>
                                        Automatic
                                    </div>
                                    <div class="car-spec">
                                        <i class="fas fa-star"></i>
                                        ${availabilityText}
                                    </div>
                                </div>
                                <div class="car-price">$${parseInt(car.price).toLocaleString()}</div>
                                ${notInStock ? `
                                    <div style="text-align: center; margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 8px; color: #856404; font-weight: 600;">
                                        <i class="fas fa-info-circle"></i>
                                        This car is currently not available for test drives
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('');
                
                searchCarCards.innerHTML = carCardsHTML;
                
                // Add click handlers to search result cards
                addCarCardClickHandlers(searchCarCards);
            }

            searchResults.classList.add('show');
        }

        function hideSearchResults() {
            const searchResults = document.getElementById('searchResults');
            searchResults.classList.remove('show');
        }

        function addCarCardClickHandlers(container) {
            const carCards = container.querySelectorAll('.car-card');
            carCards.forEach(card => {
                card.addEventListener('click', function() {
                    const isAvailable = this.dataset.available === 'true';
                    
                    if (!isAvailable) {
                        showError('This car is not available for test drives. Please choose another car.');
                        return;
                    }

                    // Remove previous selection from all cards (both main and search)
                    document.querySelectorAll('.car-card').forEach(c => {
                        c.classList.remove('selected');
                        if (!c.classList.contains('preselected')) {
                            c.style.border = '';
                            c.style.background = '';
                        }
                    });
                    
                    // Add selection to clicked card
                    this.classList.add('selected');
                    
                    selectedCar = {
                        model: this.dataset.model,
                        id: this.dataset.id,
                        year: this.dataset.year || '',
                        price: '$' + parseInt(this.dataset.price).toLocaleString(),
                        image: this.querySelector('.car-image').style.backgroundImage,
                        available: this.dataset.available,
                        status: this.dataset.status
                    };

                    showSuccess(`Selected: ${selectedCar.model}`);
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Check if car is preselected from URL
            const urlParams = new URLSearchParams(window.location.search);
            const preselectedCarId = urlParams.get('car_id');
            
            if (preselectedCarId) {
                const preselectedCard = document.querySelector(`[data-id="${preselectedCarId}"]`);
                if (preselectedCard) {
                    const isAvailable = preselectedCard.dataset.available === 'true';
                    selectedCar = {
                        model: preselectedCard.dataset.model,
                        id: preselectedCard.dataset.id,
                        year: preselectedCard.dataset.year,
                        price: '$' + parseInt(preselectedCard.dataset.price).toLocaleString(),
                        image: preselectedCard.querySelector('.car-image').style.backgroundImage,
                        available: preselectedCard.dataset.available,
                        status: preselectedCard.dataset.status
                    };
                    preselectedCard.classList.add('selected');
                    
                    if (!isAvailable) {
                        showError('The pre-selected car is currently not available for test drives. Please choose another car.');
                    }
                }
            }

            // Search functionality
            const searchInput = document.getElementById('carSearchInput');
            const searchBtn = document.getElementById('searchBtn');
            const clearSearchBtn = document.getElementById('clearSearchBtn');
            const closeSearchBtn = document.getElementById('closeSearchBtn');

            // Search input with debouncing
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Clear previous timeout
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                // Set new timeout for search
                searchTimeout = setTimeout(() => {
                    if (query.length >= 2) {
                        searchCars(query);
                    } else if (query.length === 0) {
                        hideSearchResults();
                    }
                }, 300);
            });

            // Search button click
            searchBtn.addEventListener('click', function() {
                const query = searchInput.value.trim();
                if (query.length >= 2) {
                    searchCars(query);
                } else {
                    showError('Please enter at least 2 characters to search');
                }
            });

            // Enter key search
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = this.value.trim();
                    if (query.length >= 2) {
                        searchCars(query);
                    }
                }
            });

            // Clear search
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                hideSearchResults();
                searchInput.focus();
            });

            // Close search results
            closeSearchBtn.addEventListener('click', function() {
                hideSearchResults();
            });

            // Car selection functionality for main grid
            addCarCardClickHandlers(document.getElementById('carCards'));

            // Continue to test drive
            document.getElementById('continueToTestDrive').addEventListener('click', function() {
                if (!selectedCar) {
                    showError('Please select a car first');
                    return;
                }

                if (selectedCar.available === 'false') {
                    showError('The selected car is not available for test drives. Please choose another car.');
                    return;
                }

                // Update hidden form fields
                document.getElementById('carModel').value = selectedCar.model;
                document.getElementById('carColor').value = selectedColor;
                document.getElementById('carId').value = selectedCar.id;

                // Show selected car summary (removed color display)
                document.getElementById('selectedCarSummary').innerHTML = `
                    <div class="selected-car-display">
                        <div class="selected-car-image" style="${selectedCar.image}"></div>
                        <div class="selected-car-details">
                            <h3><i class="fas fa-car"></i> ${selectedCar.model}</h3>
                            <p><strong><i class="fas fa-dollar-sign"></i> Price:</strong> ${selectedCar.price}</p>
                            ${selectedCar.year ? `<p><strong><i class="fas fa-calendar"></i> Year:</strong> ${selectedCar.year}</p>` : ''}
                            <p style="color: #28a745; font-weight: 600;">
                                <i class="fas fa-check-circle"></i>
                                Available for test drive
                            </p>
                        </div>
                    </div>
                `;

                // Switch pages
                document.getElementById('page1').classList.remove('active');
                document.getElementById('page2').classList.add('active');
                
                // Update breadcrumb
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
            });

            // Back to car selection
            document.getElementById('backToCarSelection').addEventListener('click', function() {
                document.getElementById('page2').classList.remove('active');
                document.getElementById('page1').classList.add('active');
                
                document.getElementById('step2').classList.remove('active');
                document.getElementById('step1').classList.add('active');
            });

            // File upload functionality
            const fileUpload = document.getElementById('fileUpload');
            const fileInput = document.getElementById('fileInput');
            const fileList = document.getElementById('fileList');

            fileUpload.addEventListener('click', () => fileInput.click());
            
            fileUpload.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUpload.classList.add('dragover');
            });
            
            fileUpload.addEventListener('dragleave', () => {
                fileUpload.classList.remove('dragover');
            });
            
            fileUpload.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUpload.classList.remove('dragover');
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });

            function handleFiles(files) {
                fileList.innerHTML = '';
                Array.from(files).forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    fileItem.innerHTML = `
                        <span class="file-name"><i class="fas fa-file"></i> ${file.name}</span>
                        <span class="file-size">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                    `;
                    fileList.appendChild(fileItem);
                });
            }

            // Form submission with enhanced error handling
            document.getElementById('testDriveForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate form before submission
                if (!validateForm()) {
                    return;
                }
                
                const formData = new FormData(this);
                const submitButton = document.getElementById('confirmButton');
                
                // Show loading state
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scheduling...';
                showLoading(true);
                
                fetch('book-test-drive.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        document.getElementById('confirmationEmail').textContent = document.getElementById('email').value;
                        document.getElementById('successMessage').style.display = 'flex';
                        showSuccess('Test drive booking confirmed!');
                    } else {
                        showError(data.message || 'An error occurred while processing your booking');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Custom error message as requested
                    showError('Oops! Well things like this happen. Looks like that\'s not possible right now, but no problem. Leave a message or call us at +355 69 311 3209 to book your test drive right now! Drive safe!');
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-calendar-check"></i> Schedule Test Drive';
                    showLoading(false);
                });
            });

            // Success button actions
            document.getElementById('viewBookings').addEventListener('click', function() {
                window.location.href = 'bookings.php';
            });

            document.getElementById('backToHome').addEventListener('click', function() {
                window.location.href = 'index.php';
            });

            // Real-time form validation
            document.querySelectorAll('input, select').forEach(element => {
                element.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        this.classList.add('error');
                        this.classList.remove('success');
                    } else if (this.value.trim()) {
                        this.classList.remove('error');
                        this.classList.add('success');
                    }
                });
            });

            // Email validation
            document.getElementById('email').addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value && !emailRegex.test(this.value)) {
                    this.classList.add('error');
                    this.classList.remove('success');
                    showError('Please enter a valid email address');
                } else if (this.value) {
                    this.classList.remove('error');
                    this.classList.add('success');
                }
            });

            // Date validation
            document.getElementById('dateInput').addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate <= today) {
                    this.classList.add('error');
                    this.classList.remove('success');
                    showError('Please select a future date for your test drive');
                } else {
                    this.classList.remove('error');
                    this.classList.add('success');
                }
            });

            // Modal close functionality
            document.getElementById('modalClose').addEventListener('click', function() {
                document.getElementById('successMessage').style.display = 'none';
            });

            // Close modal when clicking outside
            document.getElementById('successMessage').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('successMessage');
                    if (modal.style.display === 'flex') {
                        modal.style.display = 'none';
                    }
                    
                    // Also close search results
                    if (document.getElementById('searchResults').classList.contains('show')) {
                        hideSearchResults();
                    }
                }
            });
        });
    </script>
</body>
</html>