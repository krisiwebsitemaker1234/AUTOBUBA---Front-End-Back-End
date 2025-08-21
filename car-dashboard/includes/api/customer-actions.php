<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'quick_add_customer':
        handleQuickAddCustomer();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleQuickAddCustomer() {
    global $pdo;
    
    $required = ['name', 'email', 'phone'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    try {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $type = sanitize($_POST['customer_type'] ?? 'individual');
        $address = sanitize($_POST['address'] ?? '');
        
        $sql = "INSERT INTO customers 
                (name, email, phone, type, address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $email, $phone, $type, $address]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Customer added successfully!',
            'refresh' => true
        ]);
    } catch (PDOException $e) {
        error_log("Customer add error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred. Please try again.'
        ]);
    }
}
?>