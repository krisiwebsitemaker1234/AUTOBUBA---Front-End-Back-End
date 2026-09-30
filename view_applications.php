<?php
include_once 'config.php';

// Check if user is admin or manager
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = (int)$_POST['application_id'];
    $new_status = $_POST['status'];
    $notes = trim($_POST['notes'] ?? '');
    
    $update_stmt = $conn->prepare("UPDATE job_applications SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $update_stmt->bind_param("ssi", $new_status, $notes, $application_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Application status updated successfully!";
    } else {
        $error_message = "Error updating application status.";
    }
    $update_stmt->close();
}

// Get all applications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filter by status if provided
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = '';
$params = [];

if ($status_filter && in_array($status_filter, ['pending', 'reviewed', 'interviewed', 'hired', 'rejected'])) {
    $where_clause = 'WHERE status = ?';
    $params[] = $status_filter;
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM job_applications $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param("s", $params[0]);
}
$count_stmt->execute();
$total_applications = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Get applications
$applications_query = "SELECT * FROM job_applications $where_clause ORDER BY application_date DESC LIMIT ? OFFSET ?";
$applications_stmt = $conn->prepare($applications_query);

if (!empty($params)) {
    $applications_stmt->bind_param("sii", $params[0], $per_page, $offset);
} else {
    $applications_stmt->bind_param("ii", $per_page, $offset);
}

$applications_stmt->execute();
$applications_result = $applications_stmt->get_result();
$applications_stmt->close();

$total_pages = ceil($total_applications / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Job Applications Management - QUOLYWHEELS">
    <meta name="keywords" content="QUOLYWHEELS, jobs, applications, careers">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>QUOLYWHEELS - Job Applications</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Global Styles */
        :root {
            --primary-color: #dc3545;
            --secondary-color: #2d3436;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --white-color: #ffffff;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-color);
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        /* Simple Header */
        .simple-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .simple-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .simple-header .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .simple-header .logo img {
            height: 50px;
            width: auto;
        }

        .simple-header .logo h3 {
            margin: 0;
            font-weight: 600;
        }

        .home-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .home-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Main Content */
        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 200px);
        }

        /* Status Badge Styles */
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { 
            background-color: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeaa7;
        }

        .status-reviewed { 
            background-color: #d1ecf1; 
            color: #0c5460; 
            border: 1px solid #bee5eb;
        }

        .status-interviewed { 
            background-color: #e2e3f1; 
            color: #383d41; 
            border: 1px solid #c3c6d4;
        }

        .status-hired { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }

        .status-rejected { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }

        /* Table Styles */
        .table-custom {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table-custom thead {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .table-custom thead th {
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .table-custom tbody td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
            transition: var(--transition);
        }

        /* Statistics Cards */
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-card .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stats-card .stats-label {
            color: var(--gray-color);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Card */
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .filter-card .card-body {
            padding: 1.5rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Button Styles */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        .btn-outline-danger {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-danger:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        /* Modal Fixes */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5) !important;
        }

        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0;
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 10px 10px;
        }

        /* Pagination */
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border-color: var(--primary-color);
            margin: 0 2px;
            border-radius: 5px;
        }

        .pagination .page-link:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Icon fixes */
        .fas, .fa-solid, .fa {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }

        .fab, .fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .simple-header .container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .main-content {
                padding: 1rem 0;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 575px) {
            .simple-header .logo h3 {
                font-size: 1.2rem;
            }
            
            .stats-card .stats-number {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Simple Header -->
    <header class="simple-header">
        <div class="container">
            <div class="logo">
                <img src="img/logo zoom.png" alt="QUOLYWHEELS" class="img-fluid">
                <h3>Job Applications Management</h3>
            </div>
            <a href="index.php" class="home-btn">
                <i class="fas fa-home"></i>
                Go Back to Home
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <?php
                // Get statistics
                $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                    SUM(CASE WHEN status = 'interviewed' THEN 1 ELSE 0 END) as interviewed,
                    SUM(CASE WHEN status = 'hired' THEN 1 ELSE 0 END) as hired,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM job_applications";
                $stats_result = $conn->query($stats_query);
                $stats = $stats_result->fetch_assoc();
                ?>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['total'] ?></div>
                        <div class="stats-label">Total</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['pending'] ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['reviewed'] ?></div>
                        <div class="stats-label">Reviewed</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['interviewed'] ?></div>
                        <div class="stats-label">Interviewed</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['hired'] ?></div>
                        <div class="stats-label">Hired</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['rejected'] ?></div>
                        <div class="stats-label">Rejected</div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card filter-card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="status" class="form-label">
                                <i class="fas fa-filter me-2"></i>Filter by Status:
                            </label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Applications</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="reviewed" <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                <option value="interviewed" <?= $status_filter === 'interviewed' ? 'selected' : '' ?>>Interviewed</option>
                                <option value="hired" <?= $status_filter === 'hired' ? 'selected' : '' ?>>Hired</option>
                                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2"></i>ID</th>
                                    <th><i class="fas fa-user me-2"></i>Name</th>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <th><i class="fas fa-briefcase me-2"></i>Position</th>
                                    <th><i class="fas fa-calendar me-2"></i>Applied</th>
                                    <th><i class="fas fa-flag me-2"></i>Status</th>
                                    <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($applications_result && $applications_result->num_rows > 0): ?>
                                    <?php while ($app = $applications_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>#<?= $app['id'] ?></strong></td>
                                            <td><?= htmlspecialchars($app['full_name']) ?></td>
                                            <td><?= htmlspecialchars($app['email']) ?></td>
                                            <td><?= htmlspecialchars($app['position']) ?></td>
                                            <td><?= date('M j, Y', strtotime($app['application_date'])) ?></td>
                                            <td>
                                                <span class="badge status-<?= $app['status'] ?> status-badge">
                                                    <?= ucfirst($app['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#viewModal<?= $app['id'] ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?= $app['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?= $app['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $app['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="viewModalLabel<?= $app['id'] ?>">
                                                            <i class="fas fa-user-circle me-2"></i>
                                                            Application Details - <?= htmlspecialchars($app['full_name']) ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <strong><i class="fas fa-user me-2 text-primary"></i>Full Name:</strong><br>
                                                                <span class="ms-4"><?= htmlspecialchars($app['full_name']) ?></span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <strong><i class="fas fa-envelope me-2 text-primary"></i>Email:</strong><br>
                                                                <span class="ms-4"><?= htmlspecialchars($app['email']) ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <strong><i class="fas fa-briefcase me-2 text-primary"></i>Position:</strong><br>
                                                                <span class="ms-4"><?= htmlspecialchars($app['position']) ?></span>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <strong><i class="fas fa-flag me-2 text-primary"></i>Status:</strong><br>
                                                                <span class="ms-4">
                                                                    <span class="badge status-<?= $app['status'] ?> status-badge">
                                                                        <?= ucfirst($app['status']) ?>
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <strong><i class="fas fa-user-edit me-2 text-primary"></i>About Themselves:</strong><br>
                                                            <div class="ms-4 mt-2 p-3 bg-light rounded">
                                                                <?= nl2br(htmlspecialchars($app['about_yourself'])) ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($app['notes'])): ?>
                                                            <div class="mb-3">
                                                                <strong><i class="fas fa-sticky-note me-2 text-warning"></i>Admin Notes:</strong><br>
                                                                <div class="ms-4 mt-2 p-3 bg-warning bg-opacity-10 rounded border-start border-warning border-3">
                                                                    <?= nl2br(htmlspecialchars($app['notes'])) ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="border-top pt-3">
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar-plus me-2"></i>Applied: <?= date('F j, Y \a\t g:i A', strtotime($app['application_date'])) ?><br>
                                                                <i class="fas fa-calendar-check me-2"></i>Last Updated: <?= date('F j, Y \a\t g:i A', strtotime($app['updated_at'])) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-2"></i>Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Update Modal -->
                                        <div class="modal fade" id="updateModal<?= $app['id'] ?>" tabindex="-1" aria-labelledby="updateModalLabel<?= $app['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <div class="modal-header bg-warning text-dark">
                                                            <h5 class="modal-title" id="updateModalLabel<?= $app['id'] ?>">
                                                                <i class="fas fa-edit me-2"></i>Update Application Status
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label"><i class="fas fa-user me-2"></i>Applicant:</label>
                                                                <div class="p-2 bg-light rounded">
                                                                    <strong><?= htmlspecialchars($app['full_name']) ?></strong> - <?= htmlspecialchars($app['position']) ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="status<?= $app['id'] ?>" class="form-label">
                                                                    <i class="fas fa-flag me-2"></i>Status:
                                                                </label>
                                                                <select class="form-select" name="status" id="status<?= $app['id'] ?>" required>
                                                                    <option value="pending" <?= $app['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="reviewed" <?= $app['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                                                    <option value="interviewed" <?= $app['status'] === 'interviewed' ? 'selected' : '' ?>>Interviewed</option>
                                                                    <option value="hired" <?= $app['status'] === 'hired' ? 'selected' : '' ?>>Hired</option>
                                                                    <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="notes<?= $app['id'] ?>" class="form-label">
                                                                    <i class="fas fa-sticky-note me-2"></i>Admin Notes:
                                                                </label>
                                                                <textarea class="form-control" name="notes" id="notes<?= $app['id'] ?>" rows="3" placeholder="Add any notes about this application..."><?= htmlspecialchars($app['notes'] ?? '') ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-2"></i>Cancel
                                                            </button>
                                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                                <i class="fas fa-save me-2"></i>Update Status
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No applications found</h5>
                                            <p class="text-muted">
                                                <?php if ($status_filter): ?>
                                                    No applications with status "<?= ucfirst($status_filter) ?>" found.
                                                <?php else: ?>
                                                    No job applications have been submitted yet.
                                                <?php endif; ?>
                                            </p>
                                            <a href="work.php" class="btn btn-outline-danger">
                                                <i class="fas fa-plus me-2"></i>View Job Application Form
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Applications pagination" class="mt-4">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $status_filter ? '&status=' . $status_filter : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Fix modal backdrop issues
            const modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                modal.addEventListener('show.bs.modal', function() {
                    document.body.style.overflow = 'hidden';
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    document.body.style.overflow = 'auto';
                    // Remove any lingering backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(function(backdrop) {
                        backdrop.remove();
                    });
                });
            });
        });
    </script>
</body>

</html>
