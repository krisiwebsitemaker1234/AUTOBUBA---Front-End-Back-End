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

// Get filter parameters
$status = $_GET['status'] ?? 'Active';
$search = $_GET['search'] ?? '';

// Get rentals data
$rentals = getAll("
    SELECT r.*, 
           v.make, v.model, v.year, v.image_path,
           c.first_name, c.last_name, c.phone, c.email
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.id
    JOIN customers c ON r.customer_id = c.id
    WHERE r.status LIKE ? 
    AND (c.first_name LIKE ? OR c.last_name LIKE ? OR v.make LIKE ? OR v.model LIKE ?)
    ORDER BY r.end_date ASC
", [
    $status,
    "%$search%", "%$search%", "%$search%", "%$search%"
]);

$stats = getDashboardStats();
$user = currentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Management | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/style.css">
    <link rel="icon" href="img/quolywheels-high-resolution-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topnav.php'; ?>
            
            <div class="content">
                <div class="page-header mb-4">
                    <h1>Rental Management</h1>
                    <div class="d-flex gap-2">
                        <a href="add-rental.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Rental
                        </a>
                        <form class="d-flex" method="get">
                            <input type="text" name="search" class="form-control" placeholder="Search rentals..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between">
                            <ul class="nav nav-tabs card-header-tabs">
                                <li class="nav-item">
                                    <a class="nav-link <?= $status === 'Active' ? 'active' : '' ?>" href="rentals.php?status=Active">Active</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $status === 'Upcoming' ? 'active' : '' ?>" href="rentals.php?status=Upcoming">Upcoming</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $status === 'Completed' ? 'active' : '' ?>" href="rentals.php?status=Completed">Completed</a>
                                </li>
                            </ul>
                            <span class="pt-2"><?= count($rentals) ?> rentals found</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Rental Period</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rentals as $rental): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar me-2">
                                                    <?= substr($rental['first_name'], 0, 1) ?><?= substr($rental['last_name'], 0, 1) ?>
                                                </div>
                                                <div>
                                                    <strong><?= sanitize($rental['last_name']) ?>, <?= sanitize($rental['first_name']) ?></strong>
                                                    <div class="text-muted small">
                                                        <?= !empty($rental['phone']) ? formatPhoneNumber($rental['phone']) : 'No phone' ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= $rental['image_path'] ? 'uploads/'.$rental['image_path'] : 'assets/images/car-placeholder.jpg' ?>" 
                                                     class="vehicle-thumbnail me-2" alt="<?= $rental['make'].' '.$rental['model'] ?>">
                                                <div>
                                                    <strong><?= sanitize($rental['make']) ?></strong>
                                                    <div class="text-muted"><?= sanitize($rental['model']) ?> (<?= $rental['year'] ?>)</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($rental['start_date'])) ?> - 
                                            <?= date('M j, Y', strtotime($rental['end_date'])) ?>
                                            <div class="text-muted small">
                                                <?= rentalDaysRemaining($rental['end_date']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $rental['status'] === 'Completed' ? 'success' : 
                                                ($rental['status'] === 'Upcoming' ? 'warning' : 'primary') 
                                            ?>">
                                                <?= $rental['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-rental.php?id=<?= $rental['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($rental['status'] === 'Active'): ?>
                                                <a href="return-vehicle.php?id=<?= $rental['id'] ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-car"></i> Return
                                                </a>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>