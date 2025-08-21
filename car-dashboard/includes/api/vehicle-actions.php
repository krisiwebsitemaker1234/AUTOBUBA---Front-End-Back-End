<?php
require_once '/includes/db.php';
require_once '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'quick_add_vehicle':
        handleQuickAddVehicle();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleQuickAddVehicle() {
    global $pdo;
    
    $required = ['make', 'model', 'year', 'color', 'price', 'vin', 'mileage'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    try {
        $make = sanitize($_POST['make']);
        $model = sanitize($_POST['model']);
        $year = (int)$_POST['year'];
        $color = sanitize($_POST['color']);
        $price = (float)$_POST['price'];
        $vin = sanitize($_POST['vin']);
        $mileage = (int)$_POST['mileage'];
        
        $sql = "INSERT INTO vehicles 
                (make, model, year, color, price, vin, mileage, status, added_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'available', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$make, $model, $year, $color, $price, $vin, $mileage]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Vehicle added successfully!',
            'refresh' => true
        ]);
    } catch (PDOException $e) {
        error_log("Vehicle add error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again.'
        ]);
    }
}
?>