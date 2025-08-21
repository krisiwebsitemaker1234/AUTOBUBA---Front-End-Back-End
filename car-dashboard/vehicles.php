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

// Get current user data
$user = currentUser();

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
        if (isset($_FILES['image'])) {
            $imagePath = handleImageUpload($_FILES['image']);
        }

        if (empty($make) || empty($model) || $year < 1900) {
            $error_message = "Please fill required fields (Make, Model, Year)";
        } else {
            $result = addVehicle([
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'price' => $price,
                'mileage' => $mileage,
                'color' => $color,
                'vin' => $vin,
                'fuel_type' => $fuel_type,
                'body_style' => $body_style,
                'description' => $description,
                'image_path' => $imagePath
            ]);
            
            if ($result['success']) {
                $success_message = "Vehicle added successfully!";
            } else {
                $error_message = $result['message'];
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
        $imagePath = null;

        // Handle image upload
        if (isset($_FILES['image'])) {
            $imagePath = handleImageUpload($_FILES['image']);
            if ($imagePath) {
                // Delete old image if exists
                $currentImage = getVehicleImage($id);
                if ($currentImage && file_exists($currentImage) && $currentImage !== 'img/cars/default.jpg') {
                    unlink($currentImage);
                }
            }
        }

        if (empty($make) || empty($model) || $year < 1900) {
            $error_message = "Please fill required fields (Make, Model, Year)";
        } else {
            $updateData = [
                'make' => $make,
                'model' => $model,
                'year' => $year,
                'price' => $price,
                'mileage' => $mileage,
                'color' => $color,
                'vin' => $vin,
                'fuel_type' => $fuel_type,
                'body_style' => $body_style,
                'description' => $description
            ];
            
            if ($imagePath) {
                $updateData['image_path'] = $imagePath;
            }

            $result = updateVehicle($id, $updateData);
            
            if ($result['success']) {
                $success_message = "Vehicle updated successfully!";
            } else {
                $error_message = $result['message'];
            }
        }
    } elseif (isset($_POST['delete_vehicle'])) {
        $id = intval($_POST['vehicle_id']);
        // Delete associated image
        $currentImage = getVehicleImage($id);
        if ($currentImage && file_exists($currentImage) && $currentImage !== 'img/cars/default.jpg') {
            unlink($currentImage);
        }
        
        $result = deleteVehicle($id);
        
        if ($result['success']) {
            $success_message = "Vehicle deleted successfully!";
        } else {
            $error_message = $result['message'];
        }
    }
}

function handleImageUpload($file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Use relative path from car-dashboard to the main img folder
        $uploadDir = '../img/cars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            $filename = uniqid() . '_' . basename($file['name']);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Return the path relative to the main AUTOBUBA folder for database storage
                return 'img/cars/' . $filename;
            }
        }
    }
    return null;
}

// vehicles.php functions
function getVehicles() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM vehicles WHERE status != 'sold' ORDER BY year DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return [];
    }
}

function validateVehicleData($data) {
    $errors = [];

    // Required fields
    if (empty(trim($data['make']))) {
        $errors[] = "Make is required";
    }
    if (empty(trim($data['model']))) {
        $errors[] = "Model is required";
    }
    if (empty($data['year']) || $data['year'] < 1900 || $data['year'] > date('Y') + 1) {
        $errors[] = "Valid year is required";
    }

    // Optional fields validation
    if (!empty($data['price']) && !is_numeric($data['price'])) {
        $errors[] = "Price must be a number";
    }
    if (!empty($data['mileage']) && !is_numeric($data['mileage'])) {
        $errors[] = "Mileage must be a number";
    }
    if (!empty($data['fuel_type']) && !in_array($data['fuel_type'], ['Gasoline', 'Diesel', 'Electric', 'Hybrid', 'Other'])) {
        $errors[] = "Invalid fuel type";
    }
    if (!empty($data['body_style']) && !in_array($data['body_style'], ['Sedan', 'SUV', 'Truck', 'Coupe', 'Convertible', 'Hatchback', 'Minivan', 'Other'])) {
        $errors[] = "Invalid body style";
    }

    return $errors;
}

function addVehicle($data) {
    $validationErrors = validateVehicleData($data);
    if (!empty($validationErrors)) {
        return ['success' => false, 'message' => implode(', ', $validationErrors)];
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO vehicles 
            (make, model, year, price, mileage, color, vin, fuel_type, body_style, description, image_path)
            VALUES (
                :make, 
                :model, 
                :year, 
                :price, 
                :mileage, 
                :color, 
                :vin,
                :fuel_type,
                :body_style,
                :description,
                :image_path
            )");

        $stmt->execute([
            ':make'        => htmlspecialchars(trim($data['make'])),
            ':model'       => htmlspecialchars(trim($data['model'])),
            ':year'        => intval($data['year']),
            ':price'       => !empty($data['price']) ? floatval($data['price']) : null,
            ':mileage'     => !empty($data['mileage']) ? intval($data['mileage']) : null,
            ':color'       => !empty($data['color']) ? htmlspecialchars(trim($data['color'])) : null,
            ':vin'         => !empty($data['vin']) ? htmlspecialchars(trim($data['vin'])) : null,
            ':fuel_type'   => !empty($data['fuel_type']) ? htmlspecialchars(trim($data['fuel_type'])) : null,
            ':body_style'  => !empty($data['body_style']) ? htmlspecialchars(trim($data['body_style'])) : null,
            ':description' => !empty($data['description']) ? htmlspecialchars(trim($data['description'])) : null,
            ':image_path'  => !empty($data['image_path']) ? $data['image_path'] : 'img/cars/default.jpg'
        ]);

        return ['success' => true, 'vehicle_id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add vehicle: ' . $e->getMessage()];
    }
}

function updateVehicle($id, $data) {
    $validationErrors = validateVehicleData($data);
    if (!empty($validationErrors)) {
        return ['success' => false, 'message' => implode(', ', $validationErrors)];
    }

    global $pdo;
    try {
        $setParts = [];
        $params = [':id' => intval($id)];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $stmt = $pdo->prepare("UPDATE vehicles SET " . implode(', ', $setParts) . " WHERE id = :id");
        $stmt->execute($params);

        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update vehicle'];
    }
}

function deleteVehicle($id) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Delete ALL child records first
        $pdo->prepare("DELETE FROM calendar_events WHERE vehicle_id = ?")
            ->execute([intval($id)]);
        $pdo->prepare("DELETE FROM rentals WHERE vehicle_id = ?")
            ->execute([intval($id)]);

        // Delete the vehicle
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([intval($id)]);
        
        $pdo->commit();
        
        $success = $stmt->rowCount() > 0;
        return [
            'success' => $success,
            'message' => $success 
                ? 'Vehicle deleted successfully' 
                : 'No vehicle found with that ID'
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Failed to delete vehicle: ' . $e->getMessage()
        ];
    }
}

function getVehicleImage($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

$vehicles = getVehicles();
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
                min-width: 800px;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: row;
                gap: 5px;
            }
        }
        
        /* Form responsive styles */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
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
                <div class="page-header mb-4">
                    <h1 class="mb-1">Vehicle Inventory</h1>
                    <p class="text-muted">Manage your vehicle listings</p>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <a href="all_vehicles.php" class="btn btn-primary">
                            <i class="fas fa-car me-2"></i>View All Vehicles
                        </a>
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

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title mb-0">Vehicle List</h5>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
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
                                                    <i class="fas fa-check text-success"></i>
                                                    <img src="../<?= htmlspecialchars($vehicle['image_path']) ?>" 
                                                        class="img-thumbnail" 
                                                        style="max-width: 80px; max-height: 60px;"
                                                        onerror="this.src='../img/cars/default.jpg'">
                                                <?php else: ?>
                                                    <i class="fas fa-info-circle text-info"></i>
                                                    <span class="text-muted">(Default)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <i class="fas fa-times text-danger"></i>
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
                                            <div class="action-buttons">
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
                                            </div>
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
        <div class="modal-dialog modal-lg">
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
        <div class="modal-dialog modal-lg">
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
                            <div class="text-center mb-2">
                                <img id="currentImagePreview" src="/placeholder.svg" class="img-fluid mb-2" style="max-height: 150px; display: none;">
                            </div>
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
    });
    </script>
</body>
</html>
