<?php
// api/get-customers.php
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Get customers
    $stmt = $pdo->query("
        SELECT id, name, email 
        FROM customers 
        ORDER BY name
    ");
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch customers: ' . $e->getMessage()
    ]);
}
?>