<?php
session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT e.*, u.username as created_by_name FROM events e 
          LEFT JOIN users u ON e.created_by = u.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $query .= " AND e.status = ?";
    $params[] = $status_filter;
}

if (!empty($type_filter)) {
    $query .= " AND e.type = ?";
    $params[] = $type_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(e.start_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(e.start_date) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY e.start_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get stats
$stats_query = "SELECT 
    COUNT(*) as total_events,
    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM events";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/quolywheels logo.png">
    <title>Events - QUOLYWHEELS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Include the sidebar styles */
        <?php include 'includes/sidebar.php'; ?>
        
        /* Page-specific responsive styles */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            margin-bottom: 1rem;
            transition: transform 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stats-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .events-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 1rem;
        }
        
        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-scheduled {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-completed {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .type-maintenance {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .type-inspection {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .type-delivery {
            background-color: #e0f2f1;
            color: #00695c;
        }
        
        .type-pickup {
            background-color: #e8eaf6;
            color: #3f51b5;
        }
        
        .type-other {
            background-color: #f5f5f5;
            color: #616161;
        }
        
        .btn-action {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
            border-radius: 5px;
            font-size: 0.75rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Responsive Design */
        @media (max-width: 1199.98px) {
            .page-header {
                padding: 1.5rem 0;
            }
            
            .stats-card {
                padding: 1.25rem;
            }
            
            .stats-card .stat-number {
                font-size: 1.75rem;
            }
        }
        
        @media (max-width: 991.98px) {
            .page-header {
                padding: 1.25rem 0;
                margin-bottom: 1.5rem;
            }
            
            .stats-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }
            
            .stats-card .stat-number {
                font-size: 1.5rem;
            }
            
            .filter-card {
                padding: 1.25rem;
                margin-bottom: 1.5rem;
            }
            
            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .page-header {
                padding: 1rem 0;
                margin-bottom: 1rem;
            }
            
            .page-header h1 {
                font-size: 1.75rem;
                margin-bottom: 0.5rem;
            }
            
            .stats-card {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .stats-card .stat-number {
                font-size: 1.25rem;
            }
            
            .stats-card .stat-label {
                font-size: 0.8rem;
            }
            
            .filter-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            /* Mobile table - stack columns */
            .events-table {
                font-size: 0.875rem;
            }
            
            .table-responsive {
                border-radius: 10px;
            }
            
            .table th {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }
            
            .table td {
                padding: 0.5rem 0.25rem;
                font-size: 0.8rem;
            }
            
            .status-badge {
                padding: 0.25rem 0.5rem;
                font-size: 0.65rem;
            }
            
            .type-badge {
                padding: 0.2rem 0.4rem;
                font-size: 0.6rem;
            }
            
            .btn-action {
                padding: 0.2rem 0.4rem;
                font-size: 0.65rem;
                margin: 0.1rem;
            }
            
            /* Hide less important columns on mobile */
            .d-none-mobile {
                display: none !important;
            }
            
            .empty-state {
                padding: 2rem 1rem;
            }
            
            .empty-state i {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .page-header {
                padding: 0.75rem 0;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .stats-card {
                padding: 0.5rem;
            }
            
            .stats-card .stat-number {
                font-size: 1.1rem;
            }
            
            .filter-card {
                padding: 0.75rem;
            }
            
            /* Stack form elements on very small screens */
            .filter-form .row > div {
                margin-bottom: 0.5rem;
            }
            
            .btn-group-mobile {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .btn-group-mobile .btn {
                font-size: 0.75rem;
                padding: 0.375rem 0.5rem;
            }
        }
        
        /* Print styles */
        @media print {
            .sidebar,
            .filter-card,
            .btn,
            .btn-action {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .page-header {
                background: none !important;
                color: black !important;
                border-bottom: 2px solid #000;
            }
            
            .events-table {
                box-shadow: none;
                border: 1px solid #000;
            }
            
            .table th,
            .table td {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Mobile Header -->
        <div class="d-md-none bg-white border-bottom p-3">
            <div class="d-flex align-items-center">
                <button class="toggle-sidebar me-3">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0">Events</h5>
            </div>
        </div>
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-0">
                            <i class="fas fa-calendar-alt me-3"></i>Events Management
                        </h1>
                        <p class="mb-0 mt-2 opacity-75">Manage and track all events</p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <button class="btn btn-light" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <a href="add_event.php" class="btn btn-warning ms-2">
                            <i class="fas fa-plus me-2"></i>Add Event
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container-fluid">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="stats-card">
                        <div class="stat-number"><?= number_format($stats['total_events']) ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="stats-card">
                        <div class="stat-number"><?= number_format($stats['scheduled']) ?></div>
                        <div class="stat-label">Scheduled</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="stats-card">
                        <div class="stat-number"><?= number_format($stats['completed']) ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="stats-card">
                        <div class="stat-number"><?= number_format($stats['cancelled']) ?></div>
                        <div class="stat-label">Cancelled</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" class="filter-form">
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Search events...">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="scheduled" <?= $status_filter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="maintenance" <?= $type_filter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                <option value="inspection" <?= $type_filter === 'inspection' ? 'selected' : '' ?>>Inspection</option>
                                <option value="delivery" <?= $type_filter === 'delivery' ? 'selected' : '' ?>>Delivery</option>
                                <option value="pickup" <?= $type_filter === 'pickup' ? 'selected' : '' ?>>Pickup</option>
                                <option value="other" <?= $type_filter === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="date_from" 
                                   value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="date_to" 
                                   value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-lg-1 col-md-6 d-flex align-items-end">
                            <div class="btn-group-mobile w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="all_events.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Events Table -->
            <div class="events-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th class="d-none d-md-table-cell">Type</th>
                                <th>Date & Time</th>
                                <th class="d-none d-lg-table-cell">Location</th>
                                <th>Status</th>
                                <th class="d-none d-md-table-cell">Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-times"></i>
                                            <h5>No Events Found</h5>
                                            <p>No events match your current filters.</p>
                                            <a href="add_event.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Add First Event
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($event['title']) ?></strong>
                                                <?php if (!empty($event['description'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($event['description'], 0, 50)) ?><?= strlen($event['description']) > 50 ? '...' : '' ?></small>
                                                <?php endif; ?>
                                                <!-- Show type on mobile -->
                                                <div class="d-md-none mt-1">
                                                    <span class="type-badge type-<?= $event['type'] ?>">
                                                        <?= ucfirst($event['type']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="type-badge type-<?= $event['type'] ?>">
                                                <?= ucfirst($event['type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?= date('M j, Y', strtotime($event['start_date'])) ?></strong>
                                                <br><small class="text-muted"><?= date('g:i A', strtotime($event['start_date'])) ?></small>
                                                <?php if ($event['end_date']): ?>
                                                    <br><small class="text-muted">to <?= date('g:i A', strtotime($event['end_date'])) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <?= htmlspecialchars($event['location'] ?: 'N/A') ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $event['status'] ?>">
                                                <?= ucfirst($event['status']) ?>
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <?= htmlspecialchars($event['created_by_name'] ?: 'Unknown') ?>
                                        </td>
                                        <td>
                                            <div class="btn-group-mobile">
                                                <a href="view_event.php?id=<?= $event['id'] ?>" 
                                                   class="btn btn-outline-primary btn-action" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_event.php?id=<?= $event['id'] ?>" 
                                                   class="btn btn-outline-warning btn-action" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-action" 
                                                        onclick="deleteEvent(<?= $event['id'] ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event?')) {
                fetch('delete_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + eventId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting event: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting event');
                });
            }
        }
        
        // Auto-submit form on filter change (optional)
        document.querySelectorAll('select[name="status"], select[name="type"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
