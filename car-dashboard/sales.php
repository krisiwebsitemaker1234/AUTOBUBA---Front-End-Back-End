<?php
// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle Add Sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    try {
        // Validate required fields
        $required = ['vehicle_id', 'customer_id', 'sale_date', 'sale_price'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $data = [
            'vehicle_id' => (int)$_POST['vehicle_id'],
            'customer_id' => (int)$_POST['customer_id'],
            'sale_date' => $_POST['sale_date'],
            'sale_price' => (float)$_POST['sale_price'],
            'payment_method' => filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING),
            'city' => filter_var($_POST['city'] ?? '', FILTER_SANITIZE_STRING),
            'state' => filter_var($_POST['state'] ?? '', FILTER_SANITIZE_STRING),
            'zip' => filter_var($_POST['zip'] ?? '', FILTER_SANITIZE_STRING)
        ];

        $pdo->beginTransaction();

        // Insert new sale
        $stmt = $pdo->prepare("INSERT INTO sales 
            (vehicle_id, customer_id, sale_date, sale_price, payment_method, city, state, zip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['vehicle_id'],
            $data['customer_id'],
            $data['sale_date'],
            $data['sale_price'],
            $data['payment_method'],
            $data['city'],
            $data['state'],
            $data['zip']
        ]);

        // Update vehicle status to 'sold'
        $pdo->prepare("UPDATE vehicles SET status = 'sold' WHERE id = ?")
            ->execute([$data['vehicle_id']]);

        $pdo->commit();

        // Redirect to prevent form resubmission
        $_SESSION['success_message'] = 'Sale added successfully!';
        header('Location: sales.php');
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error adding sale: ' . $e->getMessage();
        header('Location: sales.php');
        exit;
    }
}

// Handle AJAX requests first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_sale'])) {
    try {
        // Validate and sanitize input
        $required = ['sale_id', 'vehicle_id', 'customer_id', 'sale_date'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $data = [
            'sale_id' => (int)$_POST['sale_id'],
            'vehicle_id' => (int)$_POST['vehicle_id'],
            'customer_id' => (int)$_POST['customer_id'],
            'sale_date' => $_POST['sale_date'],
            'sale_price' => (float)$_POST['sale_price'],
            'payment_method' => filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING),
            'city' => filter_var($_POST['city'] ?? '', FILTER_SANITIZE_STRING),
            'state' => filter_var($_POST['state'] ?? '', FILTER_SANITIZE_STRING),
            'zip' => filter_var($_POST['zip'] ?? '', FILTER_SANITIZE_STRING)
        ];

        $pdo->beginTransaction();

        // Get original vehicle
        $stmt = $pdo->prepare("SELECT vehicle_id FROM sales WHERE id = ?");
        $stmt->execute([$data['sale_id']]);
        $original_vehicle_id = $stmt->fetchColumn();

        // Update sale
        $stmt = $pdo->prepare("UPDATE sales SET
            vehicle_id = ?,
            customer_id = ?,
            sale_date = ?,
            sale_price = ?,
            payment_method = ?,
            city = ?,
            state = ?,
            zip = ?
            WHERE id = ?");
        $stmt->execute([
            $data['vehicle_id'],
            $data['customer_id'],
            $data['sale_date'],
            $data['sale_price'],
            $data['payment_method'],
            $data['city'],
            $data['state'],
            $data['zip'],
            $data['sale_id']
        ]);

        // Update vehicle statuses
        if ($original_vehicle_id != $data['vehicle_id']) {
            $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")
                ->execute([$original_vehicle_id]);
            $pdo->prepare("UPDATE vehicles SET status = 'sold' WHERE id = ?")
                ->execute([$data['vehicle_id']]);
        }

        $pdo->commit();

        // Get updated sale data
        $stmt = $pdo->prepare("
            SELECT s.*, v.make, v.model, c.name AS customer_name 
            FROM sales s
            JOIN vehicles v ON s.vehicle_id = v.id
            JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ?
        ");
        $stmt->execute([$data['sale_id']]);
        $updatedSale = $stmt->fetch(PDO::FETCH_ASSOC);

        // Send clean JSON response
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'sale' => $updatedSale,
            'vehicle' => $updatedSale['make'] . ' ' . $updatedSale['model']
        ]);
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        ob_end_clean();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle regular page load
try {
    // Fetch vehicles (corrected query)
    $vehicles = $pdo->query("
        SELECT id, CONCAT(make, ' ', model) AS name, status 
        FROM vehicles 
        WHERE status != 'sold'
    ")->fetchAll();

    // After (shows last 5 sales)
    $sales = $pdo->query("
        SELECT s.*, v.make, v.model, c.name AS customer_name 
        FROM sales s
        JOIN vehicles v ON s.vehicle_id = v.id
        JOIN customers c ON s.customer_id = c.id
        ORDER BY s.sale_date DESC
        LIMIT 5
    ")->fetchAll();

    // Fetch vehicles and customers
    $vehicles = $pdo->query("
        SELECT id, CONCAT(make, ' ', model) AS name, status 
        FROM vehicles 
        WHERE status != 'sold' OR id IN (SELECT vehicle_id FROM sales)
    ")->fetchAll();

    $customers = $pdo->query("SELECT id, name FROM customers")->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Unable to load data. Please try again later.";
}

// Clean output buffer before HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="icon" href="img/quolywheels logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom responsive styles */
        .dashboard {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            overflow-x: hidden;
        }
        
        .content {
            padding: 20px;
        }
        
        /* Responsive table */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Mobile styles */
        @media (max-width: 991px) {
            .dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100% !important;
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }
        
        /* Table responsive styles */
        @media (max-width: 767px) {
            .table-responsive table {
                min-width: 900px;
            }
            
            .btn-group {
                display: flex;
                flex-direction: row;
                gap: 2px;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }
        
        /* Form responsive styles */
        @media (max-width: 576px) {
            .custom-modal-content {
                margin: 0.5rem;
                width: calc(100% - 1rem);
            }
        }

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

        .custom-modal-header.bg-danger {
            background-color: #dc3545 !important;
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
            flex-wrap: wrap;
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

        .table-hover tbody tr:hover { 
            background-color: rgba(255, 77, 77, 0.05); 
        }

        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }

        /* Responsive button adjustments */
        @media (max-width: 576px) {
            .custom-modal-footer {
                flex-direction: column;
            }
            
            .custom-modal-footer .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .custom-modal-footer .btn:last-child {
                margin-bottom: 0;
            }
        }

        /* Header responsive adjustments */
        @media (max-width: 768px) {
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            
            .d-flex.justify-content-between.align-items-center > div {
                width: 100%;
                display: flex;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topnav.php'; ?>

            <div class="content">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h1 class="h3 mb-0 text-danger"><i class="fas fa-receipt me-2"></i> Sales Management</h1>
                    <div>
                        <button class="btn btn-danger" onclick="openModal('addSaleModal')">
                            <i class="fas fa-plus me-2"></i> Add Sale
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

                <!-- Sales Table -->
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title mb-0">Recent Sales</h5>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search sales...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($sales)): ?>
                            <div class="alert alert-info mb-0 m-3">No sales records found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Vehicle</th>
                                            <th>City</th>
                                            <th>State</th>
                                            <th>Zip</th>
                                            <th>Sale Price</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sales as $sale): ?>
                                        <tr data-id="<?= $sale['id'] ?>"> 
                                            <td><?= htmlspecialchars(date('M j, Y', strtotime($sale['sale_date']))) ?></td>
                                            <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($sale['make'] . ' ' . $sale['model']) ?></td>
                                            <td><?= htmlspecialchars($sale['city'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($sale['state'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($sale['zip'] ?? 'N/A') ?></td>
                                            <td>$<?= number_format($sale['sale_price'], 2) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($sale['payment_method'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view-sale.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-outline-danger" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($sale)) ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="invoice.php?id=<?= $sale['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Print">
                                                        <i class="fas fa-print"></i>
                                                    </a>
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
                
                <div class="mt-3 text-center">
                    <a href="all_sales.php" class="btn btn-outline-danger">View All Sales This Year</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Add Sale Modal -->
    <div id="addSaleModal" class="custom-modal">
        <div class="custom-modal-content">
            <div class="custom-modal-header bg-danger">
                <h5><i class="fas fa-plus me-2"></i>New Sale</h5>
                <button class="custom-modal-close" onclick="closeModal('addSaleModal')">&times;</button>
            </div>
            <form method="POST" id="addSaleForm">
                <div class="custom-modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer *</label>
                            <select class="form-select" name="customer_id" required>
                                <?php if (empty($customers)): ?>
                                    <option value="" disabled>No customers found</option>
                                <?php else: ?>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Vehicle *</label>
                            <select class="form-select" name="vehicle_id" required>
                                <?php if (empty($vehicles)): ?>
                                    <option value="" disabled>No available vehicles</option>
                                <?php else: ?>
                                    <option value="">Select Vehicle</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?= $vehicle['id'] ?>" <?= $vehicle['status'] === 'sold' ? 'disabled' : '' ?>>
                                            <?= htmlspecialchars($vehicle['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sale Date *</label>
                            <input type="date" class="form-control" name="sale_date" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sale Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" 
                                       name="sale_price" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">City *</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">State *</label>
                            <input type="text" class="form-control" name="state" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Zip *</label>
                            <input type="text" class="form-control" name="zip" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="credit">Credit Card</option>
                                <option value="finance">Bank Finance</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addSaleModal')">Close</button>
                    <button type="submit" name="add_sale" class="btn btn-danger">
                        <i class="fas fa-save me-2"></i>Save Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Edit Sale Modal -->
    <div id="editSaleModal" class="custom-modal">
        <div class="custom-modal-content">
            <div class="custom-modal-header bg-primary">
                <h5><i class="fas fa-edit me-2"></i>Edit Sale</h5>
                <button class="custom-modal-close" onclick="closeModal('editSaleModal')">&times;</button>
            </div>
            <form id="editSaleForm">
                <div class="custom-modal-body">
                    <input type="hidden" name="edit_sale" value="1">
                    <input type="hidden" name="sale_id" id="edit_sale_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer *</label>
                            <select class="form-select" name="customer_id" id="edit_customer_id" required>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Vehicle *</label>
                            <select class="form-select" name="vehicle_id" id="edit_vehicle_id" required>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $v['status'] === 'sold' ? 'data-sold="true"' : '' ?>>
                                        <?= htmlspecialchars($v['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sale Date *</label>
                            <input type="date" class="form-control" name="sale_date" id="edit_sale_date" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sale Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" 
                                    name="sale_price" id="edit_sale_price" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" id="edit_city">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" id="edit_state">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Zip</label>
                            <input type="text" class="form-control" name="zip" id="edit_zip">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Payment Method *</label>
                            <select class="form-select" name="payment_method" id="edit_payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit">Credit Card</option>
                                <option value="finance">Bank Finance</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editSaleModal')">Close</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <i class="fas fa-save me-2"></i>Update Sale
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Only load Bootstrap JS for other components, not modals -->
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
    
    function openEditModal(saleData) {
        console.log('Opening edit modal with data:', saleData);
        
        // Populate form fields
        document.getElementById('edit_sale_id').value = saleData.id;
        document.getElementById('edit_customer_id').value = saleData.customer_id;
        document.getElementById('edit_vehicle_id').value = saleData.vehicle_id;
        document.getElementById('edit_sale_date').value = saleData.sale_date;
        document.getElementById('edit_sale_price').value = parseFloat(saleData.sale_price).toFixed(2);
        document.getElementById('edit_city').value = saleData.city || '';
        document.getElementById('edit_state').value = saleData.state || '';
        document.getElementById('edit_zip').value = saleData.zip || '';
        document.getElementById('edit_payment_method').value = saleData.payment_method;

        // Handle vehicle dropdown
        const vehicleSelect = document.getElementById('edit_vehicle_id');
        Array.from(vehicleSelect.options).forEach(option => {
            if (option.value == saleData.vehicle_id) {
                option.selected = true;
                option.disabled = false;
            } else if (option.dataset.sold === 'true') {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
        
        openModal('editSaleModal');
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
    document.getElementById('editSaleForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('editSubmitBtn');
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
            closeModal('editSaleModal');
            
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                Sale updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.content').insertBefore(alertDiv, document.querySelector('.card'));
            
            // Update table row
            const row = document.querySelector(`tr[data-id="${data.sale.id}"]`);
            if (row) {
                row.cells[0].textContent = new Date(data.sale.sale_date).toLocaleDateString('en-US', { 
                    month: 'short', day: 'numeric', year: 'numeric' 
                });
                row.cells[1].textContent = data.sale.customer_name;
                row.cells[2].textContent = data.vehicle;
                row.cells[3].textContent = data.sale.city || 'N/A';
                row.cells[4].textContent = data.sale.state || 'N/A';
                row.cells[5].textContent = data.sale.zip || 'N/A';
                row.cells[6].textContent = `$${parseFloat(data.sale.sale_price).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                row.cells[7].textContent = data.sale.payment_method.charAt(0).toUpperCase() + 
                                        data.sale.payment_method.slice(1);
            }
            
            // Auto-dismiss alert after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
            
        } catch (error) {
            console.error('Error:', error);
            alert(`Operation failed: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    console.log('Custom modal system loaded successfully');
    </script>
</body>
</html>
