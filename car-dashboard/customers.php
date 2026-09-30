<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

// Initialize messages
$success_message = '';
$error_message = '';

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    try {
        $data = [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? '')
        ];

        // Validation
        if (empty($data['name']) || empty($data['email'])) {
            throw new Exception("Name and email are required fields");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Insert customer
        $stmt = $pdo->prepare("
            INSERT INTO customers 
            (name, email, phone, address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['address']
        ]);

        $success_message = "Customer added successfully!";
        // Refresh the page to show new customer
        header("Location: customers.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get customers data
try {
    $customers = getAll("SELECT * FROM customers ORDER BY name") ?: [];
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Unable to load customer data";
    $customers = [];
}


// Handle Customer Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    try {
        $customer_id = $_POST['customer_id'];
        $data = [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? '')
        ];

        if (empty($data['name']) || empty($data['email'])) {
            throw new Exception("Name and email are required");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $stmt = $pdo->prepare("
            UPDATE customers 
            SET name=?, email=?, phone=?, address=? 
            WHERE id=?
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['address'],
            $customer_id
        ]);

        $success_message = "Customer updated!";
        header("Location: customers.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Update error: " . $e->getMessage();
    }
}

// Handle Customer Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    try {
        $customer_id = $_POST['customer_id'];
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $success_message = "Customer deleted!";
        header("Location: customers.php");
        exit();
    } catch (Exception $e) {
        $error_message = "Delete error: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="img/quolywheels logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff4d4d;
            --primary-color-dark: #ff1a1a;
            --primary-color-light: #ff6b6b;
            --accent-color: #e60000;
            --text-color: #333;
            --light-bg: #f9f9f9;
            --card-bg: #ffffff;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --btn-shadow: 0 4px 15px rgba(255, 77, 77, 0.3);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        
        /* Main content area */
        .main-content {
            flex: 1;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Page header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
            width: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Tables */
        .table {
            margin-bottom: 0;
            width: 100%;
        }
        
        .table th {
            border-top: none;
            border-bottom: 2px solid var(--primary-color-light);
            color: var(--primary-color);
            font-weight: 600;
            padding: 15px 10px;
            white-space: nowrap;
        }
        
        .table td {
            padding: 15px 10px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Buttons */
        .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
            border: none;
            box-shadow: var(--btn-shadow);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-color-dark) 0%, var(--accent-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 77, 77, 0.4);
        }
        
        /* Form controls */
        .form-control {
            padding: 10px 16px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
            color: #333;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(255, 77, 77, 0.1);
        }
        
        /* Search form specific */
        .d-flex .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            margin-right: -1px;
        }
        
        .btn-outline-secondary {
            color: var(--text-color);
            border: 2px solid #f0f0f0;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            background-color: #f9f9f9;
        }
        
        .btn-outline-secondary:hover {
            color: var(--primary-color);
            background-color: #fff;
            border-color: var(--primary-color-light);
        }
        
        /* Text formatting */
        .text-muted {
            color: #6c757d !important;
        }
        
        /* Content area with subtle pattern */
        .content {
            padding: 25px;
            position: relative;
            flex: 1;
            overflow-x: auto;
        }
        
        .content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ff4d4d05" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,197.3C1248,203,1344,149,1392,122.7L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.7;
            z-index: -1;
        }
        
        /* Table responsiveness improvements */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }
        
        /* Action buttons spacing */
        .btn-sm {
            padding: 6px 10px;
            margin-right: 5px;
        }
        
        form[style="display:inline;"] {
            display: inline-block !important;
        }
        
        /* Add some spacing in the page header buttons */
        .page-header div {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Fix alert messages positioning */
        .alert {
            margin-bottom: 20px;
        }
        
        /* Fix modal scrolling on small screens */
        .modal-dialog {
            max-height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
        }
        
        .modal-body {
            overflow-y: auto;
        }
        
        /* Add responsive classes for small screens */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header div {
                margin-top: 15px;
                width: 100%;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .btn {
                padding: 8px 16px;
            }
        }
        
        @media (max-width: 576px) {
            .content {
                padding: 15px;
            }
            
            .table td, .table th {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topnav.php'; ?>
            
            <div class="content">
                <div class="container-fluid p-0">
                    <div class="page-header mb-4">
                        <h1>Customer Management</h1>
                        <div>
                            <a href="all_customers.php" class="btn btn-primary">
                                <i class="fas fa-users"></i> View All Customers
                            </a>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                <i class="fas fa-plus"></i> Add Customer
                            </button>
                        </div>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Email</th>
                                            <th>Address</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No customers found. Add your first customer to get started!</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td><strong><?= sanitize($customer['name']) ?></strong></td>
                                                <td><?= !empty($customer['phone']) ? formatPhoneNumber($customer['phone']) : 'N/A' ?></td>
                                                <td><?= sanitize($customer['email']) ?></td>
                                                <td><?= !empty($customer['address']) ? sanitize($customer['address']) : 'N/A' ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editCustomerModal"
                                                            data-id="<?= $customer['id'] ?>"
                                                            data-name="<?= htmlspecialchars($customer['name']) ?>"
                                                            data-email="<?= htmlspecialchars($customer['email']) ?>"
                                                            data-phone="<?= htmlspecialchars($customer['phone'] ?? '') ?>"
                                                            data-address="<?= htmlspecialchars($customer['address'] ?? '') ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                                                        <button type="submit" name="delete_customer" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this customer?');">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_customer" class="btn btn-primary">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="customer_id" id="editCustomerId">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="editPhone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="editAddress" name="address" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_customer" class="btn btn-primary">Update Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit modal functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('editCustomerId').value = button.dataset.id;
                document.getElementById('editName').value = button.dataset.name;
                document.getElementById('editEmail').value = button.dataset.email;
                document.getElementById('editPhone').value = button.dataset.phone;
                document.getElementById('editAddress').value = button.dataset.address;
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
    </script>
</body>
</html>