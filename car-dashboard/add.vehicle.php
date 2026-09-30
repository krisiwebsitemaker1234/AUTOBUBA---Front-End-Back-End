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
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';

// Get vehicles data
$vehicles = getVehicles($filter, $search);
$stats = getDashboardStats();
$user = currentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/quolywheels logo.png">
    <title>Vehicle Inventory | <?= SITE_NAME ?></title>
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
                    <h1>Vehicle Inventory</h1>
                    <div class="d-flex gap-2">
                        <a href="add-vehicle.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Vehicle
                        </a>
                        <form class="d-flex" method="get">
                            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
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
                                    <a class="nav-link <?= empty($filter) ? 'active' : '' ?>" href="vehicles.php">All</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'available' ? 'active' : '' ?>" href="vehicles.php?filter=available">Available</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $filter === 'sold' ? 'active' : '' ?>" href="vehicles.php?filter=sold">Sold</a>
                                </li>
                            </ul>
                            <span class="pt-2"><?= count($vehicles) ?> vehicles found</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Make/Model</th>
                                        <th>Year</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= $vehicle['image_path'] ? '../' . $vehicle['image_path'] : '../img/cars/default.jpg' ?>" 
                                            class="vehicle-thumbnail" alt="<?= $vehicle['make'].' '.$vehicle['model'] ?>">
                                        </td>
                                        <td>
                                            <strong><?= sanitize($vehicle['make']) ?></strong>
                                            <div class="text-muted"><?= sanitize($vehicle['model']) ?></div>
                                        </td>
                                        <td><?= $vehicle['year'] ?></td>
                                        <td>$<?= number_format($vehicle['price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $vehicle['status'] === 'Sold' ? 'danger' : 
                                                ($vehicle['status'] === 'Reserved' ? 'warning' : 'success') 
                                            ?>">
                                                <?= $vehicle['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
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