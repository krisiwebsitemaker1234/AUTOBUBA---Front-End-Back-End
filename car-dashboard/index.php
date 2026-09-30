<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

$user = currentUser();

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

// Get dashboard data (only for admin users)
$stats = getDashboardStats();
$transactions = getRecentTransactions(5);
$topModels = getTopModels(5);
$newArrivals = getNewArrivals(5); 
$dueRentals = getDueRentals();
$salesChartData = getSalesChartData();

// Current month and year
$currentMonth = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard | <?= SITE_NAME ?></title>
    <link rel="icon" href="img/quolywheels logo.png">
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
    
    /* Chart container */
    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
        padding: 0.5rem;
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
    
    /* Progress bar with color palette */
    .progress {
        height: 0.375rem;
        border-radius: 0.1875rem;
        background-color: #f3f4f6;
    }
    
    .progress-bar {
        background-color: #e11d48 !important;
    }
    
    /* Badges with color palette */
    .badge {
        font-size: 0.6875rem;
        font-weight: 500;
        padding: 0.25rem 0.375rem;
        border-radius: 0.25rem;
    }
    
    .bg-primary {
        background-color: #e11d48 !important;
    }
    
    .badge.bg-primary {
        background-color: #e11d48 !important;
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
    
    /* Dropdown */
    .dropdown-menu {
        font-size: 0.75rem;
        padding: 0.25rem;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: 0.375rem;
    }
    
    .dropdown-item {
        padding: 0.375rem 0.5rem;
        border-radius: 0.25rem;
    }
    
    /* Quick Actions Section */
    .quick-actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    
    .quick-action-card {
        background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
        color: white;
        padding: 1rem;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
    }
    
    .quick-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(225, 29, 72, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .quick-action-icon {
        font-size: 1.5rem;
        margin-right: 0.75rem;
        opacity: 0.9;
    }
    
    .quick-action-content h6 {
        margin: 0 0 0.25rem 0;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .quick-action-content p {
        margin: 0;
        font-size: 0.75rem;
        opacity: 0.9;
    }
    
    /* ===================================
       RESPONSIVE BREAKPOINTS
       =================================== */
    
    /* Small phones (up to 375px) */
    @media (max-width: 375px) {
        .content {
            padding: 0.5rem;
        }
        
        /* Add spacing between sections */
        .stats-grid {
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card {
            margin-bottom: 1.5rem;
        }
        
        .tables-grid {
            margin-bottom: 1.5rem;
        }
        
        .tables-grid .card {
            margin-bottom: 1.25rem;
        }
        
        .stats-card {
            padding: 0.5rem;
        }
        
        .stats-icon {
            width: 2rem;
            height: 2rem;
            margin-right: 0.5rem;
        }
        
        .stats-icon i {
            font-size: 0.875rem;
        }
        
        .stats-value {
            font-size: 1rem;
        }
        
        .card-header, .card-body {
            padding: 0.5rem;
        }
        
        .chart-container {
            height: 200px;
            padding: 0.25rem;
        }
        
        .table th, .table td {
            padding: 0.375rem 0.25rem;
        }
        
        .quick-actions {
            margin-bottom: 1.5rem;
        }
    }

    /* Medium phones (376px - 576px) */
    @media (min-width: 376px) and (max-width: 576px) {
        .content {
            padding: 0.75rem;
        }
        
        /* Add spacing between sections */
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            margin-bottom: 2rem;
        }
        
        .card {
            margin-bottom: 1.75rem;
        }
        
        .tables-grid {
            margin-bottom: 2rem;
        }
        
        .tables-grid .card {
            margin-bottom: 1.5rem;
        }
        
        .chart-container {
            height: 225px;
        }
        
        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
            margin-bottom: 2rem;
        }
    }

    /* Tablets (577px - 767px) */
    @media (min-width: 577px) and (max-width: 767px) {
        .content {
            padding: 1rem;
        }
        
        /* Add spacing between sections */
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        
        .card {
            margin-bottom: 2rem;
        }
        
        .tables-grid {
            margin-bottom: 2.5rem;
        }
        
        .tables-grid .card {
            margin-bottom: 1.75rem;
        }
        
        .card-header, .card-body {
            padding: 1rem;
        }
        
        .chart-container {
            height: 300px;
        }
        
        .stats-value {
            font-size: 1.25rem;
        }
        
        .table {
            font-size: 0.8125rem;
        }
        
        .table th {
            font-size: 0.75rem;
        }
        
        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
            margin-bottom: 2.5rem;
        }
    }
    
    /* Small desktops (768px and up) */
    @media (min-width: 768px) {
        .content {
            padding: 1.25rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .card-header h5 {
            font-size: 1rem;
        }
        
        .card-header, .card-body {
            padding: 1rem;
        }
        
        .chart-container {
            height: 350px;
            padding: 0.75rem;
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
        
        /* Two-column layout for tables */
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .quick-actions {
            grid-template-columns: repeat(3, 1fr);
            margin-bottom: 1.5rem;
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
    
    /* Medium desktops (992px - 1199px) */
    @media (min-width: 992px) {
        .content {
            padding: 1.5rem;
        }
        
        .chart-container {
            height: 400px;
        }
        
        .quick-actions {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    /* Large desktops (1200px and up) */
    @media (min-width: 1200px) {
        .chart-container {
            height: 450px;
        }
        
        .quick-actions {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    
    /* Sidebar active state for mobile */
    @media (max-width: 767px) {
        .sidebar {
            transform: translateX(-100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 80%;
            max-width: 280px;
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
                            Admin Dashboard
                        </h4>
                        <p class="text-muted mb-0">
                            Welcome back, <?= !empty($user['name']) ? sanitize($user['name']) : $user['full_name'] ?>!
                        </p>
                    </div>
                </div>


                <!-- Stats Grid - Mobile First Design -->
                <div class="stats-grid">
                    <!-- Total Vehicles -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Total Vehicles</div>
                            <div class="stats-value"><?= $stats['vehicles']['total'] ?></div>
                            <div class="stats-subtext"><?= $stats['vehicles']['available'] ?> available</div>
                        </div>
                    </div>
                    
                    <!-- Monthly Revenue -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Monthly Revenue</div>
                            <div class="stats-value">$<?= number_format($stats['sales']['monthly_revenue'] ?? 0, 2) ?></div>
                            <div class="stats-subtext"><?= $currentMonth ?></div>
                        </div>
                    </div>
                    
                    <!-- Active Rentals -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Active Rentals</div>
                            <div class="stats-value"><?= $stats['rentals']['active'] ?></div>
                            <div class="stats-subtext"><?= $stats['rentals']['due_today'] ?> due today</div>
                        </div>
                    </div>
                    
                    <!-- Total Customers -->
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-info">
                            <div class="stats-label">Total Customers</div>
                            <div class="stats-value"><?= $stats['customers']['total'] ?></div>
                            <div class="stats-subtext">Potential buyers</div>
                        </div>
                    </div>
                </div>

                <!-- Sales Performance Chart -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Sales Performance</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                                    id="chartPeriodDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Last 6 Months
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="chartPeriodDropdown">
                                <li><a class="dropdown-item" href="#" data-period="3">Last 3 Months</a></li>
                                <li><a class="dropdown-item" href="#" data-period="6">Last 6 Months</a></li>
                                <li><a class="dropdown-item" href="#" data-period="12">This Year</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tables Grid - Mobile First (stacked) -->
                <div class="tables-grid">
                    <!-- Top Models Section -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy"></i> Top Models</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Model</th>
                                            <th>Sales</th>
                                            <th style="width: 35%">Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topModels as $index => $model): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= sanitize($model['make']) ?></strong>
                                                <div class="text-muted small"><?= sanitize($model['model']) ?></div>
                                            </td>
                                            <td><?= $model['sales_count'] ?> <small class="text-muted">(<?= $model['percentage'] ?>%)</small></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" 
                                                        style="width: <?= $model['percentage'] ?>%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- New Arrivals Section -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-star"></i> New Arrivals</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Vehicle</th>
                                            <th>Details</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($newArrivals as $vehicle): ?>
                                        <tr>
                                            <td>
                                                <strong><?= sanitize($vehicle['make']) ?> <?= sanitize($vehicle['model']) ?></strong>
                                                <div class="text-muted small">
                                                    <?= $vehicle['year'] ?> | <?= number_format($vehicle['mileage']) ?> mi
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= sanitize($vehicle['color']) ?></span>
                                                <div class="text-muted small">VIN: <?= sanitize($vehicle['vin']) ?></div>
                                            </td>
                                            <td>$<?= number_format($vehicle['price'], 2) ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'available' => 'bg-success',
                                                    'sold' => 'bg-danger',
                                                    'rented' => 'bg-info',
                                                    'maintenance' => 'bg-warning'
                                                ];
                                                ?>
                                                <span class="badge <?= $statusClass[$vehicle['status']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst($vehicle['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
        // Initialize Sales Chart with responsive options
        const salesChartCanvas = document.getElementById('salesChart');
        if (salesChartCanvas) {
            const salesChartData = <?= json_encode($salesChartData) ?>;
            window.salesChartData = salesChartData; // Make it globally accessible
            
            // Create a function to filter data based on period
            window.filterChartData = function(data, months) {
                // If we have less data than requested months, return all data
                if (data.length <= months) return data;
                
                // Otherwise return the last X months
                return data.slice(-months);
            }
            
            // Initial data (last 6 months by default)
            let filteredData = filterChartData(salesChartData, 6);
            
            // Determine if we're on mobile
            const isMobile = window.innerWidth < 768;
            
            window.salesChart = new Chart(salesChartCanvas, {
                type: 'line',
                data: {
                    labels: filteredData.map(item => item.month),
                    datasets: [
                        {
                            label: 'Sales Count',
                            data: filteredData.map(item => item.sales),
                            borderColor: '#e11d48',
                            backgroundColor: 'rgba(225, 29, 72, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: isMobile ? 1.5 : 2,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#e11d48',
                            pointBorderWidth: isMobile ? 1.5 : 2,
                            pointRadius: isMobile ? 3 : 4,
                            pointHoverRadius: isMobile ? 4 : 6
                        },
                        {
                            label: 'Revenue ($)',
                            data: filteredData.map(item => item.revenue),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: isMobile ? 1.5 : 2,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#3b82f6',
                            pointBorderWidth: isMobile ? 1.5 : 2,
                            pointRadius: isMobile ? 3 : 4,
                            pointHoverRadius: isMobile ? 4 : 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: isMobile ? 8 : 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: isMobile ? 8 : 10,
                                font: {
                                    size: isMobile ? 8 : 10,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#4b5563',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            padding: isMobile ? 8 : 12,
                            cornerRadius: 8,
                            titleFont: {
                                size: isMobile ? 10 : 13,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: isMobile ? 9 : 12
                            },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.datasetIndex === 1) {
                                        label += '$' + Number(context.raw).toLocaleString();
                                    } else {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: isMobile ? 8 : 10
                                },
                                maxRotation: isMobile ? 45 : 0,
                                minRotation: isMobile ? 45 : 0
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: {
                                drawBorder: false,
                                lineWidth: 1
                            },
                            ticks: {
                                font: {
                                    size: isMobile ? 8 : 10
                                }
                            },
                            min: 0
                        }
                    }
                }
            });
        }

        // Add event listeners for chart period dropdown
        const chartPeriodDropdown = document.getElementById('chartPeriodDropdown');
        const chartPeriodOptions = document.querySelectorAll('[data-period]');

        if (chartPeriodOptions.length > 0) {
            chartPeriodOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const period = parseInt(this.getAttribute('data-period'));
                    chartPeriodDropdown.textContent = this.textContent;
                    
                    // Update chart data based on selected period
                    if (window.salesChart) {
                        // Filter data based on selected period
                        const filteredData = filterChartData(window.salesChartData, period);
                        
                        // Update chart data
                        window.salesChart.data.labels = filteredData.map(item => item.month);
                        window.salesChart.data.datasets[0].data = filteredData.map(item => item.sales);
                        window.salesChart.data.datasets[1].data = filteredData.map(item => item.revenue);
                        
                        // Update the chart
                        window.salesChart.update();
                    }
                });
            });
        }
        
        // Handle window resize for chart responsiveness
        window.addEventListener('resize', function() {
            if (window.salesChart) {
                const isMobile = window.innerWidth < 768;
                
                // Update chart options based on screen size
                window.salesChart.options.plugins.legend.labels.font.size = isMobile ? 8 : 10;
                window.salesChart.options.plugins.tooltip.titleFont.size = isMobile ? 10 : 13;
                window.salesChart.options.plugins.tooltip.bodyFont.size = isMobile ? 9 : 12;
                window.salesChart.options.scales.x.ticks.font.size = isMobile ? 8 : 10;
                window.salesChart.options.scales.y.ticks.font.size = isMobile ? 8 : 10;
                window.salesChart.options.scales.x.ticks.maxRotation = isMobile ? 45 : 0;
                
                window.salesChart.update();
            }
        });
    });
    </script>
</body>
</html>
