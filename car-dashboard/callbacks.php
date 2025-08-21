<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

$user = currentUser();

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access-denied.php");
    exit();
}

// Helper function for safe sanitization
function safeSanitize($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $callback_id = isset($_POST['callback_id']) ? (int)$_POST['callback_id'] : 0;
    $new_status = isset($_POST['status']) ? safeSanitize($_POST['status']) : '';
    
    if ($callback_id > 0 && !empty($new_status)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE callback_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            
            if ($update_stmt->execute([$new_status, $callback_id])) {
                $success_message = "Callback request status updated successfully!";
            } else {
                $error_message = "Failed to update callback request status.";
            }
        } catch (Exception $e) {
            error_log("Error updating callback: " . $e->getMessage());
            $error_message = "An error occurred while updating the callback request.";
        }
    } else {
        $error_message = "Invalid callback request data provided.";
    }
}

// Get filter parameters with null safety
$status_filter = isset($_GET['status']) ? safeSanitize($_GET['status']) : '';
$service_filter = isset($_GET['service']) ? safeSanitize($_GET['service']) : '';
$search = isset($_GET['search']) ? safeSanitize($_GET['search']) : '';

// Build query with null safety
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($service_filter)) {
    $where_conditions[] = "service = ?";
    $params[] = $service_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get callback requests with null safety
try {
    $query = "SELECT * FROM callback_requests 
              $where_clause 
              ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $callbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure callbacks is always an array
    if (!$callbacks) {
        $callbacks = [];
    }
} catch (Exception $e) {
    error_log("Error fetching callbacks: " . $e->getMessage());
    $callbacks = [];
    $error_message = "Error loading callback requests. Please try again.";
}

// Get statistics with null safety
try {
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM callback_requests";
    $stats_result = $pdo->query($stats_query);
    $stats = $stats_result->fetch(PDO::FETCH_ASSOC);
    
    // Ensure all stats have default values
    $stats = array_merge([
        'total' => 0,
        'pending' => 0,
        'contacted' => 0,
        'completed' => 0,
        'cancelled' => 0
    ], $stats ?: []);
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
    $stats = [
        'total' => 0,
        'pending' => 0,
        'contacted' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];
}

// Get service distribution for pie chart
try {
    $service_query = "SELECT 
        service,
        COUNT(*) as count
        FROM callback_requests 
        WHERE service IS NOT NULL AND service != ''
        GROUP BY service
        ORDER BY count DESC";
    $service_result = $pdo->query($service_query);
    $service_data = $service_result->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure service_data is always an array
    if (!$service_data) {
        $service_data = [];
    }
} catch (Exception $e) {
    error_log("Error fetching service data: " . $e->getMessage());
    $service_data = [];
}

// Process chart data for pie chart
$chart_labels = [];
$chart_values = [];
$chart_colors = [
    '#e11d48', '#3b82f6', '#10b981', '#f59e0b',
    '#8b5cf6', '#ef4444', '#06b6d4', '#84cc16'
];

foreach ($service_data as $index => $item) {
    $chart_labels[] = ucfirst($item['service'] ?? 'Unknown');
    $chart_values[] = (int)($item['count'] ?? 0);
}

// If no data, show a placeholder
if (empty($chart_labels)) {
    $chart_labels = ['No Data'];
    $chart_values = [1];
    $chart_colors = ['#e5e7eb'];
}

$currentMonth = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Callback Requests | <?= SITE_NAME ?></title>
    <link rel="icon" href="img/autobuba logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
    /* ===================================
       MOBILE-FIRST RESPONSIVE DASHBOARD
       =================================== */
       
    /* Base styles */
    body {
        overflow-x: hidden;
        background-color: #f9fafb;
    }
    
    /* Dashboard container */
    .dashboard {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        width: 100%;
    }
    
    /* Main content area */
    .main-content {
        flex: 1;
        width: 100%;
        transition: margin-left 0.3s ease;
    }
    
    /* Content container */
    .content {
        padding: 0.75rem;
        width: 100%;
    }
    
    /* Page header */
    .page-header {
        margin-bottom: 1rem;
    }
    
    .page-header h4 {
        font-size: 1.125rem;
        margin-bottom: 0.25rem;
        color: #1f2937;
    }
    
    .page-header p {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0;
    }
    
    /* Card styling with color palette */
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        background-color: #ffffff;
        margin-bottom: 0.75rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #f1f1f1;
        padding: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .card-header h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #e11d48;
        margin: 0;
        display: flex;
        align-items: center;
    }
    
    .card-header h5 i {
        margin-right: 0.375rem;
        color: #e11d48;
    }
    
    .card-body {
        padding: 0.75rem;
    }
    
    /* Stats Grid - Mobile First (1 column) */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    /* Stats Card with color palette */
    .stats-card {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-radius: 10px;
        background-color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stats-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 8px;
        background-color: #e11d48;
        color: white;
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    
    .stats-icon i {
        font-size: 1rem;
    }
    
    .stats-info {
        flex: 1;
        min-width: 0;
    }
    
    .stats-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.125rem;
    }
    
    .stats-value {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0;
        line-height: 1.2;
    }
    
    .stats-subtext {
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    /* Chart container - optimized for pie chart */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .chart-wrapper {
        position: relative;
        width: 100%;
        height: 100%;
        max-width: 400px;
        max-height: 400px;
    }
    
    /* Tables with color palette */
    .table-responsive {
        margin: 0;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table {
        margin-bottom: 0;
        font-size: 0.75rem;
    }
    
    .table-dark {
        background-color: #e11d48 !important;
    }
    
    .table th {
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025rem;
        padding: 0.5rem 0.375rem;
        white-space: nowrap;
    }
    
    .table td {
        padding: 0.5rem 0.375rem;
        vertical-align: middle;
    }
    
    .table td strong {
        font-size: 0.75rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .table .text-muted {
        font-size: 0.6875rem;
    }
    
    /* Status badges */
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-contacted {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .status-completed {
        background: #d4edda;
        color: #155724;
    }
    
    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    /* Service badges */
    .service-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-size: 0.6875rem;
        font-weight: 500;
        background: #f3f4f6;
        color: #374151;
    }
    
    /* Filter section */
    .filter-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    /* Buttons with color palette */
    .btn-outline-primary {
        color: #e11d48;
        border-color: #e11d48;
    }
    
    .btn-outline-primary:hover {
        background-color: #e11d48;
        border-color: #e11d48;
        color: #ffffff;
    }
    
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }
    
    .btn-primary {
        background-color: #e11d48;
        border-color: #e11d48;
    }
    
    .btn-primary:hover {
        background-color: #be123c;
        border-color: #be123c;
    }
    
    /* Form controls */
    .form-select, .form-control {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    /* Callback card */
    .callback-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    
    .callback-card:hover {
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }
    
    /* Alert styles */
    .alert {
        border-radius: 8px;
        font-size: 0.875rem;
    }
    
    /* Contact info styling */
    .contact-info {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        font-size: 0.875rem;
        color: #4b5563;
    }
    
    .contact-item i {
        margin-right: 0.5rem;
        color: #e11d48;
        width: 16px;
    }
    
    /* No data state */
    .no-data-chart {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: #9ca3af;
    }
    
    .no-data-chart i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    /* Responsive breakpoints */
    @media (min-width: 376px) and (max-width: 576px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .chart-container {
            height: 250px;
        }
    }

    @media (min-width: 577px) and (max-width: 767px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .content {
            padding: 1rem;
        }
        
        .chart-container {
            height: 300px;
        }
    }
    
    @media (min-width: 768px) {
        .content {
            padding: 1.25rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            height: 350px;
            padding: 1.5rem;
        }
        
        .page-header h4 {
            font-size: 1.25rem;
        }
        
        .stats-value {
            font-size: 1.5rem;
        }
        
        .table {
            font-size: 0.875rem;
        }
        
        .table th {
            font-size: 0.75rem;
            padding: 0.75rem 0.5rem;
        }
        
        .table td {
            padding: 0.75rem 0.5rem;
        }
        
        /* Sidebar adjustments for desktop */
        .sidebar {
            width: 250px;
        }
        
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        
        .main-content.expanded {
            margin-left: 60px;
            width: calc(100% - 60px);
        }
    }
    
    @media (min-width: 992px) {
        .content {
            padding: 1.5rem;
        }
        
        .chart-container {
            height: 400px;
        }
    }
    
    @media (min-width: 1200px) {
        .chart-container {
            height: 450px;
        }
    }
    
    /* Mobile sidebar */
    @media (max-width: 767px) {
        .sidebar {
            transform: translateX(-100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 70%;
            max-width: 180px;
            z-index: 1050;
            transition: transform 0.3s ease;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0;
            width: 100%;
        }
    }
    </style>
</head>
<body>

    <div class="dashboard">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Dashboard Content -->
            <div class="content">
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-phone-alt me-2"></i>Callback Requests
                        </h4>
                        <p class="text-muted mb-0">
                            Manage and track customer callback requests
                        </p>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= safeSanitize($success_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= safeSanitize($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <!-- Total Callbacks -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Total Requests</div>
                            <div class="stats-value"><?= (int)$stats['total'] ?></div>
                            <div class="stats-subtext">All time</div>
                        </div>
                    </div>
                    
                    <!-- Pending Callbacks -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Pending</div>
                            <div class="stats-value"><?= (int)$stats['pending'] ?></div>
                            <div class="stats-subtext">Need contact</div>
                        </div>
                    </div>
                    
                    <!-- Contacted -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-phone-volume"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Contacted</div>
                            <div class="stats-value"><?= (int)$stats['contacted'] ?></div>
                            <div class="stats-subtext">In progress</div>
                        </div>
                    </div>
                    
                    <!-- Completed -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Completed</div>
                            <div class="stats-value"><?= (int)$stats['completed'] ?></div>
                            <div class="stats-subtext">Finished</div>
                        </div>
                    </div>
                </div>

                <!-- Service Distribution Chart -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Requests by Service</h5>
                        <div class="text-muted small">
                            Service distribution
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($service_data)): ?>
                            <div class="chart-container">
                                <div class="chart-wrapper">
                                    <canvas id="serviceChart"></canvas>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-data-chart">
                                <i class="fas fa-chart-pie"></i>
                                <h6>No Request Data Available</h6>
                                <p class="text-muted mb-0">Callback requests will appear here once submitted</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="contacted" <?= $status_filter === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Service</label>
                            <select name="service" class="form-select">
                                <option value="">All Services</option>
                                <option value="Buy Cars" <?= $service_filter === 'Buy Cars' ? 'selected' : '' ?>>Buy Cars</option>
                                <option value="Sell Cars" <?= $service_filter === 'Sell Cars' ? 'selected' : '' ?>>Sell Cars</option>
                                <option value="Rent Cars" <?= $service_filter === 'Rent Cars' ? 'selected' : '' ?>>Rent Cars</option>
                                <option value="Car Service" <?= $service_filter === 'Car Service' ? 'selected' : '' ?>>Car Service</option>
                                <option value="Insurance" <?= $service_filter === 'Insurance' ? 'selected' : '' ?>>Insurance</option>
                                <option value="Financing" <?= $service_filter === 'Financing' ? 'selected' : '' ?>>Financing</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="<?= $search ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Callback Requests List -->
                <div class="callbacks-list">
                    <?php if (empty($callbacks)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-phone-slash fs-1 text-muted mb-3"></i>
                            <h4 class="text-muted">No callback requests found</h4>
                            <p class="text-muted">Try adjusting your filters or search criteria.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($callbacks as $callback): ?>
                            <?php
                            // Null safety for callback data
                            $callback_id = (int)($callback['id'] ?? 0);
                            $callback_name = safeSanitize($callback['name'] ?? 'Unknown');
                            $callback_email = safeSanitize($callback['email'] ?? 'No email');
                            $callback_phone = safeSanitize($callback['phone'] ?? 'No phone');
                            $callback_service = safeSanitize($callback['service'] ?? 'Unknown');
                            $callback_status = safeSanitize($callback['status'] ?? 'pending');
                            $callback_message = safeSanitize($callback['message'] ?? '');
                            $callback_created_at = $callback['created_at'] ?? date('Y-m-d H:i:s');
                            $callback_updated_at = $callback['updated_at'] ?? $callback_created_at;
                            ?>
                            <div class="card callback-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">
                                            Request #<?= $callback_id ?> - <?= $callback_name ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i><?= date('M j, Y H:i', strtotime($callback_created_at)) ?>
                                            <?php if ($callback_updated_at !== $callback_created_at): ?>
                                                | Updated: <?= date('M j, Y H:i', strtotime($callback_updated_at)) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <span class="status-badge status-<?= $callback_status ?>">
                                            <?= ucfirst($callback_status) ?>
                                        </span>
                                        <span class="service-badge"><?= $callback_service ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Contact Information -->
                                    <div class="contact-info">
                                        <div class="contact-item">
                                            <i class="fas fa-envelope"></i>
                                            <span><?= $callback_email ?></span>
                                        </div>
                                        <div class="contact-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?= $callback_phone ?></span>
                                        </div>
                                        <div class="contact-item">
                                            <i class="fas fa-cogs"></i>
                                            <span><?= $callback_service ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($callback_message)): ?>
                                        <div class="alert alert-light">
                                            <strong>Message:</strong><br>
                                            <?= nl2br($callback_message) ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Update Status Form -->
                                    <form method="POST" class="row g-3">
                                        <input type="hidden" name="callback_id" value="<?= $callback_id ?>">
                                        <div class="col-md-4">
                                            <label class="form-label small">Update Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="pending" <?= $callback_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="contacted" <?= $callback_status === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                                <option value="completed" <?= $callback_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $callback_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Quick Actions</label>
                                            <div class="d-flex gap-2">
                                                <a href="mailto:<?= $callback_email ?>" class="btn btn-outline-primary btn-sm" title="Send Email">
                                                    <i class="fas fa-envelope"></i>
                                                </a>
                                                <a href="tel:<?= $callback_phone ?>" class="btn btn-outline-primary btn-sm" title="Call">
                                                    <i class="fas fa-phone"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">&nbsp;</label>
                                            <div class="d-grid">
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-save me-1"></i>Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- Initialize Dashboard Charts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Service Distribution Pie Chart
        const serviceChartCanvas = document.getElementById('serviceChart');
        if (serviceChartCanvas) {
            const chartLabels = <?= json_encode($chart_labels) ?>;
            const chartValues = <?= json_encode($chart_values) ?>;
            const chartColors = <?= json_encode(array_slice($chart_colors, 0, count($chart_labels))) ?>;
            
            // Determine if we're on mobile
            const isMobile = window.innerWidth < 768;
            
            // Check if we have real data
            const hasData = chartLabels.length > 0 && chartLabels[0] !== 'No Data';
            
            window.serviceChart = new Chart(serviceChartCanvas, {
                type: 'doughnut',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        data: chartValues,
                        backgroundColor: chartColors,
                        borderColor: chartColors.map(color => color + '80'),
                        borderWidth: 2,
                        hoverBorderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            display: hasData,
                            position: isMobile ? 'bottom' : 'right',
                            align: 'center',
                            labels: {
                                boxWidth: isMobile ? 12 : 15,
                                padding: isMobile ? 10 : 15,
                                font: {
                                    size: isMobile ? 10 : 12,
                                    weight: '500'
                                },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const dataset = data.datasets[0];
                                            const value = dataset.data[i];
                                            const total = dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            
                                            return {
                                                text: `${label} (${percentage}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor[i],
                                                lineWidth: dataset.borderWidth,
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            enabled: hasData,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#4b5563',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            padding: isMobile ? 8 : 12,
                            cornerRadius: 8,
                            titleFont: {
                                size: isMobile ? 11 : 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: isMobile ? 10 : 13
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} requests (${percentage}%)`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false,
                        mode: 'point'
                    }
                }
            });
            
            // Add center text for doughnut chart
            if (hasData) {
                const centerText = {
                    id: 'centerText',
                    beforeDatasetsDraw(chart, args, options) {
                        const { ctx, data } = chart;
                        ctx.save();
                        
                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const centerX = chart.getDatasetMeta(0).data[0].x;
                        const centerY = chart.getDatasetMeta(0).data[0].y;
                        
                        ctx.font = isMobile ? 'bold 16px Arial' : 'bold 20px Arial';
                        ctx.fillStyle = '#1f2937';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(total, centerX, centerY - (isMobile ? 8 : 10));
                        
                        ctx.font = isMobile ? '10px Arial' : '12px Arial';
                        ctx.fillStyle = '#6b7280';
                        ctx.fillText('Total Requests', centerX, centerY + (isMobile ? 8 : 12));
                        
                        ctx.restore();
                    }
                };
                
                Chart.register(centerText);
            }
        }
        
        // Handle window resize for chart responsiveness
        window.addEventListener('resize', function() {
            if (window.serviceChart) {
                const isMobile = window.innerWidth < 768;
                
                // Update legend position based on screen size
                window.serviceChart.options.plugins.legend.position = isMobile ? 'bottom' : 'right';
                window.serviceChart.options.plugins.legend.labels.font.size = isMobile ? 10 : 12;
                window.serviceChart.options.plugins.legend.labels.padding = isMobile ? 10 : 15;
                window.serviceChart.options.plugins.legend.labels.boxWidth = isMobile ? 12 : 15;
                
                // Update tooltip font sizes
                window.serviceChart.options.plugins.tooltip.titleFont.size = isMobile ? 11 : 14;
                window.serviceChart.options.plugins.tooltip.bodyFont.size = isMobile ? 10 : 13;
                window.serviceChart.options.plugins.tooltip.padding = isMobile ? 8 : 12;
                
                window.serviceChart.update();
            }
        });
    });
    </script>
</body>
</html>