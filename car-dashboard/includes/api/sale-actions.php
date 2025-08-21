<?php
require_once __DIR__ . '.includes/db.php';
require_once __DIR__ . 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'quick_add_sale') {
        try {
            $required = ['vehicle_id', 'customer_id', 'sale_price', 'sale_date'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $vehicle_id = intval($_POST['vehicle_id']);
            $customer_id = intval($_POST['customer_id']);
            $sale_price = floatval($_POST['sale_price']);
            $sale_date = sanitize($_POST['sale_date']);
            $notes = sanitize($_POST['sale_notes'] ?? '');

            // Check vehicle availability
            $stmt = $pdo->prepare("SELECT status FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicle_id]);
            $vehicle = $stmt->fetch();
            
            if ($vehicle['status'] !== 'available') {
                throw new Exception('Vehicle is not available for sale');
            }

            // Start transaction
            $pdo->beginTransaction();

            // Record sale
            $stmt = $pdo->prepare("INSERT INTO sales 
                (vehicle_id, customer_id, sale_price, sale_date, notes, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$vehicle_id, $customer_id, $sale_price, $sale_date, $notes]);

            // Update vehicle status
            $pdo->prepare("UPDATE vehicles SET status = 'sold' WHERE id = ?")
                ->execute([$vehicle_id]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Sale recorded successfully!',
                'refresh' => true
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Sale add error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>