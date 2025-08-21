<?php
// api/get-vehicles.php
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Get available vehicles
    $stmt = $pdo->query("
        SELECT id, make, model, year, vin 
        FROM vehicles 
        WHERE status = 'available'
        ORDER BY make, model
    ");
    
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch vehicles: ' . $e->getMessage()
    ]);
}
?>