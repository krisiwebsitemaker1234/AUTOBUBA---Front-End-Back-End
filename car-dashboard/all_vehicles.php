<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

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

// Handle vehicle actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_vehicle'])) {
        $make = trim($_POST['make']);
        $model = trim($_POST['model']);
        $year = intval($_POST['year']);
        $price = floatval($_POST['price']);
        $mileage = intval($_POST['mileage']);
        $color = trim($_POST['color']);
        $vin = trim($_POST['vin']);
        $fuel_type = trim($_POST['fuel_type']);
        $body_style = trim($_POST['body_style']);
        $description = trim($_POST['description']);
        $imagePath = '';

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../img/cars/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            
            if (in_array($fileType, $allowedTypes)) {
                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'img/cars/' . $filename;
                }
            }
        }

        if (empty($make) || empty($model) || $year < 1900) {
            $error_message = "Please fill required fields (Make, Model, Year)";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicles 
                    (make, model, year, price, mileage, color, vin, fuel_type, body_style, description, image_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $make, $model, $year, $price, $mileage, $color, $vin, 
                    $fuel_type, $body_style, $description, $imagePath
                ]);
                
                $success_message = "Vehicle added successfully!";
            } catch (PDOException $e) {
                $error_message = "Error adding vehicle: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_vehicle'])) {
        $id = intval($_POST['vehicle_id']);
        $make = trim($_POST['make']);
        $model = trim($_POST['model']);
        $year = intval($_POST['year']);
        $price = floatval($_POST['price']);
        $mileage = intval($_POST['mileage']);
        $color = trim($_POST['color']);
        $vin = trim($_POST['vin']);
        $fuel_type = trim($_POST['fuel_type']);
        $body_style = trim($_POST['body_style']);
        $description = trim($_POST['description']);

        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../img/cars/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            
            if (in_array($fileType, $allowedTypes)) {
                $filename = uniqid() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'img/cars/' . $filename;
                    
                    // Delete old image if exists
                    $stmt = $pdo->prepare("SELECT image_path FROM vehicles WHERE id = ?");
                    $stmt->execute([$id]);
                    $oldImage = $stmt->fetchColumn();
                    if ($oldImage && file_exists('../' . $oldImage)) {
                        unlink('../' . $oldImage);
                    }
                }
            }
        }

        if (empty($make) || empty($model) || $year < 1900) {
            $error_message = "Please fill required fields (Make, Model, Year)";
        } else {
            try {
                if ($imagePath) {
                    $stmt = $pdo->prepare("UPDATE vehicles SET 
                        make = ?, model = ?, year = ?, price = ?, mileage = ?, color = ?, 
                        vin = ?, fuel_type = ?, body_style = ?, description = ?, image_path = ?
                        WHERE id = ?");
                    $stmt->execute([
                        $make, $model, $year, $price, $mileage, $color, $vin, 
                        $fuel_type, $body_style, $description, $imagePath, $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE vehicles SET 
                        make = ?, model = ?, year = ?, price = ?, mileage = ?, color = ?, 
                        vin = ?, fuel_type = ?, body_style = ?, description = ?
                        WHERE id = ?");
                    $stmt->execute([
                        $make, $model, $year, $price, $mileage, $color, $vin, 
                        $fuel_type, $body_style, $description, $id
                    ]);
                }
                
                $success_message = "Vehicle updated successfully!";
            } catch (PDOException $e) {
                $error_message = "Error updating vehicle: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_vehicle'])) {
        $id = intval($_POST['vehicle_id']);
        
        try {
            // Get image path before deletion
            $stmt = $pdo->prepare("SELECT image_path FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $imagePath = $stmt->fetchColumn();
            
            // Delete vehicle
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete image file if exists
            if ($imagePath && file_exists('../' . $imagePath)) {
                unlink('../' . $imagePath);
            }
            
            $success_message = "Vehicle deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting vehicle: " . $e->getMessage();
        }
    }
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$make_filter = isset($_GET['make']) ? $_GET['make'] : '';
$model_filter = isset($_GET['model']) ? $_GET['model'] : '';
$min_year = isset($_GET['min_year']) ? $_GET['min_year'] : '';
$max_year = isset($_GET['max_year']) ? $_GET['max_year'] : '';
$min_price = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$color_filter = isset($_GET['color']) ? $_GET['color'] : '';
$fuel_type_filter = isset($_GET['fuel_type']) ? $_GET['fuel_type'] : '';

// Build query
$query = "SELECT * FROM vehicles WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (make LIKE ? OR model LIKE ? OR color LIKE ? OR vin LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($make_filter)) {
    $query .= " AND make LIKE ?";
    $params[] = "%$make_filter%";
}

if (!empty($model_filter)) {
    $query .= " AND model LIKE ?";
    $params[] = "%$model_filter%";
}

if (!empty($min_year) && is_numeric($min_year)) {
    $query .= " AND year >= ?";
    $params[] = $min_year;
}

if (!empty($max_year) && is_numeric($max_year)) {
    $query .= " AND year <= ?";
    $params[] = $max_year;
}

if (!empty($min_price) && is_numeric($min_price)) {
    $query .= " AND price >= ?";
    $params[] = $min_price;
}

if (!empty($max_price) && is_numeric($max_price)) {
    $query .= " AND price <= ?";
    $params[] = $max_price;
}

if (!empty($color_filter)) {
    $query .= " AND color LIKE ?";
    $params[] = "%$color_filter%";
}

if (!empty($fuel_type_filter)) {
    $query .= " AND fuel_type = ?";
    $params[] = $fuel_type_filter;
}

$query .= " ORDER BY year DESC, make ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error loading vehicles";
    $vehicles = [];
}

// Get stats
/*$stats_query = "SELECT 
    COUNT(*) as total_vehicles,
    AVG(price) as avg_price,
    MIN(year) as oldest_year,
    MAX(year) as newest_year
    FROM vehicles";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Inventory | <?= SITE_NAME ?></title>
    <link rel="icon" href="img/autobuba logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/topnav.php'; ?>

            <div class="content">
                <div class="page-header mb-4">
                    <h1 class="mb-1">Vehicle Inventory</h1>
                    <p class="text-muted">Manage your vehicle listings</p>
                    <div class="mt-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                            <i class="fas fa-plus me-2"></i>Add Vehicle
                        </button>
                    </div>
                </div>

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-filter me-2"></i>Search Filters</h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" placeholder="Search vehicles..." 
                                    value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label">Make</label>
                                <input type="text" class="form-control" name="make" placeholder="Make" 
                                    value="<?= htmlspecialchars($make_filter) ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Min Year</label>
                                <input type="number" class="form-control" name="min_year" placeholder="Min Year" 
                                    value="<?= htmlspecialchars($min_year) ?>">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label">Max Year</label>
                                <input type="number" class="form-control" name="max_year" placeholder="Max Year" 
                                    value="<?= htmlspecialchars($max_year) ?>">
                            </div>
                            <div class="col-md-2 col-sm-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Vehicle List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Make</th>
                                        <th>Model</th>
                                        <th>Year</th>
                                        <th>Price</th>
                                        <th>Mileage</th>
                                        <th>Color</th>
                                        <th>VIN</th>
                                        <th>Fuel Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($vehicle['image_path'])): ?>
                                                <?php if ($vehicle['image_path'] !== 'img/cars/default.jpg'): ?>
                                                    <img src="../<?= htmlspecialchars($vehicle['image_path']) ?>" 
                                                        class="img-thumbnail" 
                                                        style="max-width: 80px; max-height: 60px;"
                                                        onerror="this.src='../img/cars/default.jpg'">
                                                <?php else: ?>
                                                    <span class="text-muted">(Default)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($vehicle['make']) ?></td>
                                        <td><?= htmlspecialchars($vehicle['model']) ?></td>
                                        <td><?= $vehicle['year'] ?></td>
                                        <td>$<?= number_format($vehicle['price'], 2) ?></td>
                                        <td><?= number_format($vehicle['mileage']) ?> mi</td>
                                        <td><?= htmlspecialchars($vehicle['color']) ?></td>
                                        <td><?= htmlspecialchars($vehicle['vin']) ?></td>
                                        <td><?= htmlspecialchars($vehicle['fuel_type'] ?? 'N/A') ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning edit-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editVehicleModal"
                                                    data-id="<?= $vehicle['id'] ?>"
                                                    data-make="<?= htmlspecialchars($vehicle['make']) ?>"
                                                    data-model="<?= htmlspecialchars($vehicle['model']) ?>"
                                                    data-year="<?= $vehicle['year'] ?>"
                                                    data-price="<?= $vehicle['price'] ?>"
                                                    data-mileage="<?= $vehicle['mileage'] ?>"
                                                    data-color="<?= htmlspecialchars($vehicle['color']) ?>"
                                                    data-vin="<?= htmlspecialchars($vehicle['vin']) ?>"
                                                    data-fuel_type="<?= htmlspecialchars($vehicle['fuel_type'] ?? '') ?>"
                                                    data-body_style="<?= htmlspecialchars($vehicle['body_style'] ?? '') ?>"
                                                    data-description="<?= htmlspecialchars($vehicle['description'] ?? '') ?>"
                                                    data-image="<?= htmlspecialchars($vehicle['image_path'] ?? '') ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="vehicle_id" value="<?= $vehicle['id'] ?>">
                                                <button type="submit" name="delete_vehicle" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Make *</label>
                            <input type="text" class="form-control" name="make" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Model *</label>
                            <input type="text" class="form-control" name="model" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year *</label>
                                <input type="number" class="form-control" name="year" min="1900" max="<?= date('Y') + 1 ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="price" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mileage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="mileage">
                                    <span class="input-group-text">mi</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">VIN</label>
                            <input type="text" class="form-control" name="vin">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fuel Type</label>
                                <select class="form-select" name="fuel_type">
                                    <option value="Gasoline">Gasoline</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Body Style</label>
                                <select class="form-select" name="body_style">
                                    <option value="Sedan">Sedan</option>
                                    <option value="SUV">SUV</option>
                                    <option value="Truck">Truck</option>
                                    <option value="Coupe">Coupe</option>
                                    <option value="Convertible">Convertible</option>
                                    <option value="Hatchback">Hatchback</option>
                                    <option value="Minivan">Minivan</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_vehicle" class="btn btn-primary">Add Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="vehicle_id" id="edit_vehicle_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Vehicle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Make *</label>
                            <input type="text" class="form-control" name="make" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Model *</label>
                            <input type="text" class="form-control" name="model" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year *</label>
                                <input type="number" class="form-control" name="year" min="1900" max="<?= date('Y') + 1 ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="price" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mileage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="mileage">
                                    <span class="input-group-text">mi</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">VIN</label>
                            <input type="text" class="form-control" name="vin">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fuel Type</label>
                                <select class="form-select" name="fuel_type">
                                    <option value="Gasoline">Gasoline</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Body Style</label>
                                <select class="form-select" name="body_style">
                                    <option value="Sedan">Sedan</option>
                                    <option value="SUV">SUV</option>
                                    <option value="Truck">Truck</option>
                                    <option value="Coupe">Coupe</option>
                                    <option value="Convertible">Convertible</option>
                                    <option value="Hatchback">Hatchback</option>
                                    <option value="Minivan">Minivan</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <img id="currentImagePreview" src="/placeholder.svg" class="img-fluid mb-2" style="max-height: 150px; display: none;">
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_vehicle" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit vehicle modal handler
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = document.querySelector('#editVehicleModal');
                
                // Populate all edit form fields
                modal.querySelector('#edit_vehicle_id').value = this.dataset.id;
                modal.querySelector('[name="make"]').value = this.dataset.make;
                modal.querySelector('[name="model"]').value = this.dataset.model;
                modal.querySelector('[name="year"]').value = this.dataset.year;
                modal.querySelector('[name="price"]').value = this.dataset.price;
                modal.querySelector('[name="mileage"]').value = this.dataset.mileage;
                modal.querySelector('[name="color"]').value = this.dataset.color;
                modal.querySelector('[name="vin"]').value = this.dataset.vin;
                modal.querySelector('[name="fuel_type"]').value = this.dataset.fuel_type;
                modal.querySelector('[name="body_style"]').value = this.dataset.body_style;
                modal.querySelector('[name="description"]').value = this.dataset.description;
                
                // Handle image preview
                const imgPreview = modal.querySelector('#currentImagePreview');
                if(this.dataset.image && this.dataset.image !== 'img/cars/default.jpg') {
                    imgPreview.src = '../' + this.dataset.image;
                    imgPreview.style.display = 'block';
                } else {
                    imgPreview.style.display = 'none';
                }
            });
        });

        // Add vehicle form reset handler
        document.getElementById('addVehicleModal').addEventListener('show.bs.modal', function () {
            this.querySelector('form').reset();
        });

        // Prevent form submission if invalid (for both forms)
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            }, false);
        });
    });
    </script>
</body>
</html>
