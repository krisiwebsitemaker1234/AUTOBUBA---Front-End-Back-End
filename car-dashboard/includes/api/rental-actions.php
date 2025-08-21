<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'quick_add_rental') {
        try {
            $required = ['vehicle_id', 'customer_id', 'start_date', 'end_date', 'daily_rate'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $vehicle_id = intval($_POST['vehicle_id']);
            $customer_id = intval($_POST['customer_id']);
            $start_date = sanitize($_POST['start_date']);
            $end_date = sanitize($_POST['end_date']);
            $daily_rate = floatval($_POST['daily_rate']);
            $status = sanitize($_POST['status'] ?? 'active');

            // Check vehicle availability
            $stmt = $pdo->prepare("SELECT status FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicle_id]);
            $vehicle = $stmt->fetch();
            
            if (!in_array($vehicle['status'], ['available', 'reserved'])) {
                throw new Exception('Vehicle is not available for rental');
            }

            // Calculate total
            $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
            $total = $days * $daily_rate;

            // Start transaction
            $pdo->beginTransaction();

            // Insert rental
            $stmt = $pdo->prepare("INSERT INTO rentals 
                (vehicle_id, customer_id, start_date, end_date, daily_rate, total, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $vehicle_id, 
                $customer_id,
                $start_date,
                $end_date,
                $daily_rate,
                $total,
                $status
            ]);

            // Update vehicle status
            $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE id = ?")
                ->execute([$vehicle_id]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Rental created successfully!',
                'refresh' => true
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Rental add error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>