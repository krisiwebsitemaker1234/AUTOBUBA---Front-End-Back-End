<?php

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();


// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle Add Rental submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rental'])) {
    try {
        $required = ['vehicle_id', 'customer_id', 'start_date', 'end_date', 'daily_rate'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $data = [
            'vehicle_id' => (int)$_POST['vehicle_id'],
            'customer_id' => (int)$_POST['customer_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'daily_rate' => (float)$_POST['daily_rate'],
            'status' => $_POST['status'] ?? 'Active'
        ];

        // Calculate total
        $days = (strtotime($data['end_date']) - strtotime($data['start_date'])) / 86400;
        $data['total'] = $days * $data['daily_rate'];

        $pdo->beginTransaction();

        // Insert rental
        $stmt = $pdo->prepare("INSERT INTO rentals 
            (vehicle_id, customer_id, user_id, daily_rate, total, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['vehicle_id'],
            $data['customer_id'],
            $_SESSION['user_id'],
            $data['daily_rate'],
            $data['total'],
            $data['start_date'],
            $data['end_date'],
            $data['status']
        ]);

        // Update vehicle status
        $pdo->prepare("UPDATE vehicles SET status = 'Rented' WHERE id = ?")
            ->execute([$data['vehicle_id']]);

        $pdo->commit();

        $_SESSION['success_message'] = 'Rental added successfully!';
        header('Location: all_rentals.php');
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error adding rental: ' . $e->getMessage();
        header('Location: all_rentals.php');
        exit;
    }
}

// Handle Delete Rental
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rental'])) {
    try {
        if (empty($_POST['rental_id'])) {
            throw new Exception("Missing rental ID");
        }

        $rental_id = (int)$_POST['rental_id'];

        $pdo->beginTransaction();

        // Get rental details
        $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE id = ?");
        $stmt->execute([$rental_id]);
        $vehicle_id = $stmt->fetchColumn();

        // Delete rental
        $pdo->prepare("DELETE FROM rentals WHERE id = ?")->execute([$rental_id]);

        // Update vehicle status if needed
        $pdo->prepare("UPDATE vehicles SET status = 'In Stock' WHERE id = ?")
            ->execute([$vehicle_id]);

        $pdo->commit();

        $_SESSION['success_message'] = 'Rental deleted successfully!';
        header('Location: all_rentals.php');
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error deleting rental: ' . $e->getMessage();
        header('Location: all_rentals.php');
        exit;
    }
}

// Handle Edit Rental (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_rental'])) {
    try {
        $required = ['rental_id', 'vehicle_id', 'customer_id', 'start_date', 'end_date', 'daily_rate'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $data = [
            'rental_id' => (int)$_POST['rental_id'],
            'vehicle_id' => (int)$_POST['vehicle_id'],
            'customer_id' => (int)$_POST['customer_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'daily_rate' => (float)$_POST['daily_rate'],
            'status' => $_POST['status']
        ];

        // Calculate new total
        $days = (strtotime($data['end_date']) - strtotime($data['start_date'])) / 86400;
        $data['total'] = $days * $data['daily_rate'];

        $pdo->beginTransaction();

        // Get original vehicle ID
        $stmt = $pdo->prepare("SELECT vehicle_id FROM rentals WHERE id = ?");
        $stmt->execute([$data['rental_id']]);
        $original_vehicle_id = $stmt->fetchColumn();

        // Update rental
        $stmt = $pdo->prepare("UPDATE rentals SET
            vehicle_id = ?,
            customer_id = ?,
            start_date = ?,
            end_date = ?,
            daily_rate = ?,
            total = ?,
            status = ?
            WHERE id = ?");
        $stmt->execute([
            $data['vehicle_id'],
            $data['customer_id'],
            $data['start_date'],
            $data['end_date'],
            $data['daily_rate'],
            $data['total'],
            $data['status'],
            $data['rental_id']
        ]);

        // Update vehicle statuses if changed
        if ($original_vehicle_id != $data['vehicle_id']) {
            $pdo->prepare("UPDATE vehicles SET status = 'In Stock' WHERE id = ?")
                ->execute([$original_vehicle_id]);
            $pdo->prepare("UPDATE vehicles SET status = 'Rented' WHERE id = ?")
                ->execute([$data['vehicle_id']]);
        }

        $pdo->commit();

        // Return updated data
        $stmt = $pdo->prepare("
            SELECT r.*, v.make, v.model, c.name AS customer_name 
            FROM rentals r
            JOIN vehicles v ON r.vehicle_id = v.id
            JOIN customers c ON r.customer_id = c.id
            WHERE r.id = ?
        ");
        $stmt->execute([$data['rental_id']]);
        $updatedRental = $stmt->fetch(PDO::FETCH_ASSOC);

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'rental' => $updatedRental
        ]);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error updating rental: ' . $e->getMessage()
        ]);
        exit;
    }
}

try {
    // Fetch ALL rentals without date filter
    $rentals = $pdo->query("
        SELECT r.*, v.make, v.model, c.name AS customer_name 
        FROM rentals r
        JOIN vehicles v ON r.vehicle_id = v.id
        JOIN customers c ON r.customer_id = c.id
        ORDER BY r.start_date DESC
    ")->fetchAll();

    // Fetch available vehicles
    $vehicles = $pdo->query("
        SELECT id, CONCAT(make, ' ', model) AS name 
        FROM vehicles 
        WHERE status = 'In Stock'
    ")->fetchAll();

    // Fetch all customers
    $customers = $pdo->query("SELECT id, name FROM customers")->fetchAll();

    // Fetch all vehicles for edit modal
    $all_vehicles = $pdo->query("
        SELECT id, CONCAT(make, ' ', model) AS name, status 
        FROM vehicles
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Unable to load data. Please try again later.";
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Rental | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="icon" href="img/autobuba logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom Modal Styles - No Bootstrap Dependencies */
        .custom-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .custom-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .custom-modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
            position: relative;
        }

        .custom-modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .custom-modal-header.bg-info {
            background-color: #0dcaf0 !important;
            color: white;
        }

        .custom-modal-header.bg-primary {
            background-color: #0d6efd !important;
            color: white;
        }

        .custom-modal-body {
            padding: 1.5rem;
        }

        .custom-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .custom-modal-close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            z-index: 10;
        }

        .custom-modal-close:hover {
            opacity: 0.7;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }

        body {
            background-color: #f8f9fa;
        }
        
        /* Main content responsive layout */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
            padding: 20px;
        }
        
        .main-content.expanded {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
        
        /* Page header responsive */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-header h1 {
            color: #e11d48;
            font-weight: 600;
            margin: 0;
        }
        
        /* Card styling */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        /* Table responsive improvements */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #e11d48;
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(225, 29, 72, 0.05);
        }
        
        /* Button styling */
        .btn-group .btn {
            margin-right: 0.25rem;
        }
        
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        
        /* Badge styling */
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }
            
            .main-content.expanded {
                margin-left: 65px;
                width: calc(100% - 65px);
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            
            .page-header h1 {
                font-size: 1.25rem;
                margin-bottom: 1rem;
            }
            
            /* Hide less important columns on mobile */
            .table th:nth-child(n+4),
            .table td:nth-child(n+4) {
                display: none;
            }
            
            .table th:nth-child(7),
            .table td:nth-child(7) {
                display: table-cell; /* Keep actions column */
            }
            
            /* Stack button groups vertically on mobile */
            .btn-group {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-group .btn {
                margin-right: 0;
                margin-bottom: 0.25rem;
                width: 100%;
            }
        }
        
        @media (max-width: 575.98px) {
            .main-content {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 1.125rem;
            }
            
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.875rem;
            }
            
            /* Show only essential columns on very small screens */
            .table th:nth-child(n+3),
            .table td:nth-child(n+3) {
                display: none;
            }
            
            .table th:nth-child(7),
            .table td:nth-child(7) {
                display: table-cell; /* Keep actions column */
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'includes/topnav.php'; ?>

        <div class="container-fluid">
            <div class="page-header">
                <h1><i class="fas fa-car-side me-2 mt-5"></i> All Rentals</h1>
                <div>
                    <a href="rentals.php" class="btn btn-outline-primary">
                        <i class="fas fa-clock me-2"></i> <span class="d-none d-sm-inline">View Recent</span> Rentals
                    </a>
                    <button class="btn btn-info ms-2" onclick="openModal('addRentalModal')">
                        <i class="fas fa-plus me-2"></i> Add Rental
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <!-- Rentals Table -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if (empty($rentals)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>No rental records found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Period</th>
                                        <th class="d-none d-md-table-cell">Customer</th>
                                        <th class="d-none d-lg-table-cell">Vehicle</th>
                                        <th class="d-none d-lg-table-cell">Daily Rate</th>
                                        <th class="d-none d-md-table-cell">Total</th>
                                        <th class="d-none d-sm-table-cell">Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rentals as $rental): ?>
                                    <tr data-id="<?= $rental['id'] ?>">
                                        <td>
                                            <div class="fw-bold">
                                                <?= date('M j', strtotime($rental['start_date'])) ?> - 
                                                <?= date('M j, Y', strtotime($rental['end_date'])) ?>
                                            </div>
                                            <!-- Show customer on mobile -->
                                            <div class="d-md-none small text-muted mt-1">
                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($rental['customer_name']) ?>
                                            </div>
                                            <!-- Show vehicle on mobile -->
                                            <div class="d-lg-none small text-muted">
                                                <i class="fas fa-car me-1"></i><?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($rental['customer_name']) ?></td>
                                        <td class="d-none d-lg-table-cell"><?= htmlspecialchars($rental['make'] . ' ' . $rental['model']) ?></td>
                                        <td class="d-none d-lg-table-cell">$<?= number_format($rental['daily_rate'], 2) ?></td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="fw-bold">$<?= number_format($rental['total'], 2) ?></span>
                                        </td>
                                        <td class="d-none d-sm-table-cell">
                                            <span class="badge bg-<?= 
                                                match($rental['status']) {
                                                    'Active' => 'success',
                                                    'Reserved' => 'warning',
                                                    'Completed' => 'secondary',
                                                    'Canceled' => 'danger'
                                                }
                                            ?>">
                                                <?= $rental['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="openEditRentalModal(<?= htmlspecialchars(json_encode($rental)) ?>)"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                    <input type="hidden" name="rental_id" value="<?= $rental['id'] ?>">
                                                    <button type="submit" name="delete_rental" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Custom Add Rental Modal -->
        <div id="addRentalModal" class="custom-modal">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-info">
                    <h5><i class="fas fa-car-side me-2"></i> New Rental</h5>
                    <button class="custom-modal-close" onclick="closeModal('addRentalModal')">&times;</button>
                </div>
                <form method="POST">
                    <div class="custom-modal-body">
                        <div class="row g-3">
                            <!-- Customer Selection -->
                            <div class="col-md-6">
                                <label class="form-label">Customer</label>
                                <select class="form-select" name="customer_id" required>
                                    <?php if (!empty($customers)): ?>
                                        <option value="">Select Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>">
                                                <?= htmlspecialchars($customer['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No customers found</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Vehicle Selection -->
                            <div class="col-md-6">
                                <label class="form-label">Vehicle</label>
                                <select class="form-select" name="vehicle_id" required>
                                    <?php if (!empty($vehicles)): ?>
                                        <option value="">Select Vehicle</option>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?= $vehicle['id'] ?>">
                                                <?= htmlspecialchars($vehicle['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No available vehicles</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Dates -->
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" 
                                    min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" 
                                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            </div>

                            <!-- Rate & Status -->
                            <div class="col-md-6">
                                <label class="form-label">Daily Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" 
                                        name="daily_rate" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="Active" selected>Active</option>
                                    <option value="Reserved">Reserved</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addRentalModal')">Close</button>
                        <button type="submit" name="add_rental" class="btn btn-info">
                            <i class="fas fa-save me-2"></i> Save Rental
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Custom Edit Rental Modal -->
        <div id="editRentalModal" class="custom-modal">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-primary">
                    <h5><i class="fas fa-edit me-2"></i> Edit Rental</h5>
                    <button class="custom-modal-close" onclick="closeModal('editRentalModal')">&times;</button>
                </div>
                <form id="editRentalForm">
                    <div class="custom-modal-body">
                        <input type="hidden" name="edit_rental" value="1">
                        <input type="hidden" name="rental_id" id="edit_rental_id">
                        
                        <div class="row g-3">
                            <!-- Customer -->
                            <div class="col-md-6">
                                <label class="form-label">Customer</label>
                                <select class="form-select" name="customer_id" id="edit_rental_customer" required>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Vehicle (Include available + current vehicle) -->
                            <div class="col-md-6">
                                <label class="form-label">Vehicle</label>
                                <select class="form-select" name="vehicle_id" id="edit_rental_vehicle" required>
                                    <?php foreach ($all_vehicles as $v): ?>
                                        <option value="<?= $v['id'] ?>" 
                                            <?= $v['status'] === 'Rented' ? 'data-rented="true"' : '' ?>>
                                            <?= htmlspecialchars($v['name']) ?> 
                                            (<?= $v['status'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Dates -->
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="edit_rental_start" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="edit_rental_end" required>
                            </div>

                            <!-- Rate & Status -->
                            <div class="col-md-6">
                                <label class="form-label">Daily Rate</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" 
                                        name="daily_rate" id="edit_rental_rate" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_rental_status">
                                    <option value="Active">Active</option>
                                    <option value="Reserved">Reserved</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Canceled">Canceled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="custom-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editRentalModal')">Close</button>
                        <button type="submit" class="btn btn-primary" id="editRentalSubmitBtn">
                            <i class="fas fa-save me-2"></i> Update Rental
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Custom Modal System - No Bootstrap Dependencies
    
    function openModal(modalId) {
        console.log('Opening modal:', modalId);
        const modal = document.getElementById(modalId);
        if (modal) {
            document.body.classList.add('modal-open');
            modal.classList.add('show');
            
            // Focus first input
            setTimeout(() => {
                const firstInput = modal.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    }
    
    function closeModal(modalId) {
        console.log('Closing modal:', modalId);
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
        }
    }
    
    function openEditRentalModal(rentalData) {
        console.log('Opening edit rental modal with data:', rentalData);
        
        // Populate form fields
        document.getElementById('edit_rental_id').value = rentalData.id;
        document.getElementById('edit_rental_customer').value = rentalData.customer_id;
        document.getElementById('edit_rental_vehicle').value = rentalData.vehicle_id;
        document.getElementById('edit_rental_start').value = rentalData.start_date;
        document.getElementById('edit_rental_end').value = rentalData.end_date;
        document.getElementById('edit_rental_rate').value = parseFloat(rentalData.daily_rate).toFixed(2);
        document.getElementById('edit_rental_status').value = rentalData.status;

        // Handle vehicle dropdown
        const vehicleSelect = document.getElementById('edit_rental_vehicle');
        Array.from(vehicleSelect.options).forEach(option => {
            if (option.value == rentalData.vehicle_id) {
                option.selected = true;
                option.disabled = false;
            } else if (option.dataset.rented === 'true') {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
        
        openModal('editRentalModal');
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('custom-modal')) {
            const modalId = e.target.id;
            closeModal(modalId);
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.custom-modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
    
    // Handle edit form submission
    document.getElementById('editRentalForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('editRentalSubmitBtn');
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            const formData = new FormData(this);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Unknown error occurred');
            }
            
            // Close modal
            closeModal('editRentalModal');
            
            // Show success message and reload
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                Rental updated successfully! Refreshing page...
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.card'));
            
            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } catch (error) {
            console.error('Error:', error);
            alert(`Operation failed: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    console.log('Custom modal system loaded successfully');
    </script>
</body>
</html>
