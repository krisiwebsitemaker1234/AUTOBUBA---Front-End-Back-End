<?php
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

// Initialize variables
$filter_name = $_GET['name'] ?? '';
$filter_email = $_GET['email'] ?? '';
$filter_phone = $_GET['phone'] ?? '';
$filter_address = $_GET['address'] ?? '';


// Build filter query
$where = [];
$params = [];

if (!empty($filter_name)) {
    $where[] = "name LIKE ?";
    $params[] = "%$filter_name%";
}

if (!empty($filter_email)) {
    $where[] = "email LIKE ?";
    $params[] = "%$filter_email%";
}

if (!empty($filter_phone)) {
    $where[] = "phone LIKE ?";
    $params[] = "%$filter_phone%";
}

if (!empty($filter_address)) {
    $where[] = "address LIKE ?";
    $params[] = "%$filter_address%";
}

$query = "SELECT * FROM customers";
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY name";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Unable to load customer data";
    $customers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Directory | <?= SITE_NAME ?></title>
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
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Main content area - RESPONSIVE */
        .main-content {
            flex: 1;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
            position: relative;
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 70px;
            width: calc(100% - 70px);
        }
        
        /* Page header - RESPONSIVE */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-header h1 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
            font-size: 1.75rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Tables - RESPONSIVE */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
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
        
        /* Buttons - RESPONSIVE */
        .btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
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
        
        .btn-outline-primary {
            color: var(--primary-color);
            border: 2px solid var(--primary-color-light);
            background-color: transparent;
        }
        
        .btn-outline-primary:hover {
            color: white;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* Form controls - RESPONSIVE */
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
        
        /* Content area */
        .content {
            padding: 25px;
            position: relative;
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
        
        /* RESPONSIVE BREAKPOINTS */
        
        /* Tablet styles */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 220px;
                width: calc(100% - 220px);
            }
            
            .main-content.expanded {
                margin-left: 65px;
                width: calc(100% - 65px);
            }
            
            .content {
                padding: 20px;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
        }
        
        /* Mobile styles */
        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .main-content.expanded {
                margin-left: 0;
                width: 100%;
            }
            
            .content {
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
            }
            
            .page-header h1 {
                margin-bottom: 15px;
                font-size: 1.25rem;
            }
            
            .card-body {
                padding: 20px;
            }
            
            /* Filter form responsive */
            .row.g-3 > div {
                margin-bottom: 15px;
            }
            
            .row.g-3 .col-md-3,
            .row.g-3 .col-md-2,
            .row.g-3 .col-md-1 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            /* Table responsive improvements */
            .table th,
            .table td {
                padding: 10px 8px;
                font-size: 0.875rem;
            }
            
            /* Button improvements */
            .btn {
                padding: 8px 16px;
                font-size: 0.875rem;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }
        
        /* Small mobile styles */
        @media (max-width: 575.98px) {
            .content {
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 1.125rem;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
                font-size: 0.8rem;
            }
            
            /* Stack action buttons vertically on very small screens */
            .btn-group-vertical .btn {
                margin-bottom: 5px;
            }
        }
        
        /* Text formatting */
        .text-muted {
            color: #6c757d !important;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topnav.php'; ?>
            
            <div class="content">
                <div class="page-header mb-4">
                    <h1>Customer Directory</h1>
                    <div>
                        <a href="customers.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> <span class="d-none d-sm-inline">Manage</span> Customers
                        </a>
                    </div>
                </div>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-primary"><i class="fas fa-filter"></i> Filter Customers</h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" placeholder="Search by name" 
                                    value="<?= htmlspecialchars($filter_name) ?>">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="Search by email" 
                                    value="<?= htmlspecialchars($filter_email) ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" placeholder="Search by phone" 
                                    value="<?= htmlspecialchars($filter_phone) ?>">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" placeholder="Search by address" 
                                    value="<?= htmlspecialchars($filter_address) ?>">
                            </div>
                            <div class="col-md-1 col-12 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> <span class="d-none d-sm-inline">Search</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Customer Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3 text-primary"><i class="fas fa-users"></i> Customer List</h5>
                        <?php if (count($customers) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th class="d-none d-md-table-cell">Contact</th>
                                            <th class="d-none d-sm-table-cell">Email</th>
                                            <th class="d-none d-lg-table-cell">Address</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <strong><?= sanitize($customer['name']) ?></strong>
                                                <div class="d-md-none small text-muted">
                                                    <?= !empty($customer['phone']) ? formatPhoneNumber($customer['phone']) : '' ?>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <?= !empty($customer['phone']) ? formatPhoneNumber($customer['phone']) : 'N/A' ?>
                                            </td>
                                            <td class="d-none d-sm-table-cell"><?= sanitize($customer['email']) ?></td>
                                            <td class="d-none d-lg-table-cell">
                                                <?= !empty($customer['address']) ? sanitize($customer['address']) : 'N/A' ?>
                                            </td>
                                            <td>
                                                <a href="customers.php?edit=<?= $customer['id'] ?>" 
                                                class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> <span class="d-none d-sm-inline">Edit</span>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No customers found matching your criteria.
                            </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <small class="text-muted">Showing <?= count($customers) ?> customer(s)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
