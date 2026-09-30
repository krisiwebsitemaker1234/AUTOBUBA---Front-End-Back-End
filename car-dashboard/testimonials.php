<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: access-denied.php");
    exit();
}

// Handle Add Testimonial submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_testimonial'])) {
    try {
        // Validate required fields
        $required = ['name', 'message', 'rating'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $data = [
            'name' => filter_var($_POST['name'], FILTER_SANITIZE_STRING),
            'position' => filter_var($_POST['position'] ?? '', FILTER_SANITIZE_STRING),
            'company' => filter_var($_POST['company'] ?? '', FILTER_SANITIZE_STRING),
            'message' => filter_var($_POST['message'], FILTER_SANITIZE_STRING),
            'rating' => (int)$_POST['rating'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate rating
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new Exception("Rating must be between 1 and 5");
        }

        // Handle image upload
        $image_path = 'img/testimonial/default.png';
        if (isset($_FILES['testimonial_image']) && $_FILES['testimonial_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'img/testimonial/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Validate file type and size
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['testimonial_image']['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP files are allowed.');
            }
            
            if ($_FILES['testimonial_image']['size'] > $max_size) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }
            
            $file_extension = pathinfo($_FILES['testimonial_image']['name'], PATHINFO_EXTENSION);
            $image_path = $upload_dir . uniqid('testimonial_') . '.' . $file_extension;
            
            if (!move_uploaded_file($_FILES['testimonial_image']['tmp_name'], $image_path)) {
                throw new Exception('Failed to upload image file');
            }
        }

        // Insert testimonial into database
        $stmt = $pdo->prepare("INSERT INTO testimonials 
            (name, position, company, message, rating, image_path, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['position'],
            $data['company'],
            $data['message'],
            $data['rating'],
            $image_path,
            $data['is_active']
        ]);

        $_SESSION['success_message'] = 'Testimonial added successfully!';
        header('Location: testimonials.php');
        exit;

    } catch (Throwable $e) {
        // Delete uploaded file if it exists and there was an error
        if (isset($image_path) && $image_path !== 'img/testimonial/default.png' && file_exists($image_path)) {
            unlink($image_path);
        }
        $_SESSION['error_message'] = 'Error adding testimonial: ' . $e->getMessage();
        header('Location: testimonials.php');
        exit;
    }
}

// Handle AJAX edit requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_testimonial'])) {
    try {
        $required = ['testimonial_id', 'name', 'message', 'rating'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $data = [
            'testimonial_id' => (int)$_POST['testimonial_id'],
            'name' => filter_var($_POST['name'], FILTER_SANITIZE_STRING),
            'position' => filter_var($_POST['position'] ?? '', FILTER_SANITIZE_STRING),
            'company' => filter_var($_POST['company'] ?? '', FILTER_SANITIZE_STRING),
            'message' => filter_var($_POST['message'], FILTER_SANITIZE_STRING),
            'rating' => (int)$_POST['rating'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate rating
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            throw new Exception("Rating must be between 1 and 5");
        }

        // Get current image path
        $stmt = $pdo->prepare("SELECT image_path FROM testimonials WHERE id = ?");
        $stmt->execute([$data['testimonial_id']]);
        $current_image = $stmt->fetchColumn();

        $image_path = $current_image;

        // Handle new image upload
        if (isset($_FILES['testimonial_image']) && $_FILES['testimonial_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'img/testimonial/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($_FILES['testimonial_image']['type'], $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP files are allowed.');
            }
            
            if ($_FILES['testimonial_image']['size'] > $max_size) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }
            
            $file_extension = pathinfo($_FILES['testimonial_image']['name'], PATHINFO_EXTENSION);
            $new_image_path = $upload_dir . uniqid('testimonial_') . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['testimonial_image']['tmp_name'], $new_image_path)) {
                // Delete old image if it's not the default
                if ($current_image && $current_image !== 'img/testimonial/default.png' && file_exists($current_image)) {
                    unlink($current_image);
                }
                $image_path = $new_image_path;
            }
        }

        // Update testimonial
        $stmt = $pdo->prepare("UPDATE testimonials SET
            name = ?, position = ?, company = ?, message = ?, rating = ?, image_path = ?, is_active = ?
            WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['position'],
            $data['company'],
            $data['message'],
            $data['rating'],
            $image_path,
            $data['is_active'],
            $data['testimonial_id']
        ]);

        // Get updated testimonial data
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
        $stmt->execute([$data['testimonial_id']]);
        $updatedTestimonial = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'testimonial' => $updatedTestimonial
        ]);
        exit;

    } catch (Throwable $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle delete testimonial requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_testimonial'])) {
    try {
        $testimonial_id = (int)$_POST['testimonial_id'];
        
        if (!$testimonial_id) {
            throw new Exception("Invalid testimonial ID");
        }

        // Get the image path before deleting
        $stmt = $pdo->prepare("SELECT image_path FROM testimonials WHERE id = ?");
        $stmt->execute([$testimonial_id]);
        $image_path = $stmt->fetchColumn();

        if (!$image_path) {
            throw new Exception("Testimonial not found");
        }

        // Delete the testimonial record
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
        $stmt->execute([$testimonial_id]);

        // Delete the image file if it's not the default
        if ($image_path && $image_path !== 'img/testimonial/default.png' && file_exists($image_path)) {
            unlink($image_path);
        }

        $_SESSION['success_message'] = 'Testimonial deleted successfully!';
        header('Location: testimonials.php');
        exit;

    } catch (Throwable $e) {
        $_SESSION['error_message'] = 'Error deleting testimonial: ' . $e->getMessage();
        header('Location: testimonials.php');
        exit;
    }
}

// Handle toggle active status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    try {
        $testimonial_id = (int)$_POST['testimonial_id'];
        $new_status = (int)$_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE testimonials SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $testimonial_id]);
        
        $_SESSION['success_message'] = 'Testimonial status updated successfully!';
        header('Location: testimonials.php');
        exit;
        
    } catch (Throwable $e) {
        $_SESSION['error_message'] = 'Error updating status: ' . $e->getMessage();
        header('Location: testimonials.php');
        exit;
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    $query = "SELECT * FROM testimonials WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR company LIKE ? OR message LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($status_filter !== '') {
        $query .= " AND is_active = ?";
        $params[] = (int)$status_filter;
    }

    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Unable to load testimonials data.";
    $testimonials = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="icon" href="img/quolywheels logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                min-width: 900px;
            }
            
            .btn-group {
                display: flex;
                flex-direction: row;
                gap: 2px;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }

        /* Custom Modal Styles */
        .custom-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .custom-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .custom-modal-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
            position: relative;
        }

        .custom-modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .custom-modal-header.bg-danger {
            background-color: #dc3545 !important;
            color: white;
        }

        .custom-modal-header.bg-primary {
            background-color: #0d6efd !important;
            color: white;
        }

        .custom-modal-body {
            padding: 1.5rem;
        }

        .custom-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .custom-modal-close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: white;
            z-index: 10;
        }

        .custom-modal-close:hover {
            opacity: 0.7;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .table-hover tbody tr:hover { 
            background-color: rgba(255, 77, 77, 0.05); 
        }

        body.modal-open {
            overflow: hidden;
        }

        /* Testimonial image styles */
        .testimonial-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Rating stars */
        .rating-stars {
            color: #ffc107;
        }

        /* Status badge */
        .status-badge {
            cursor: pointer;
        }

        /* Image preview */
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }

        /* File upload area */
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: #dc3545;
            background-color: #f8f9fa;
        }

        .file-upload-area.dragover {
            border-color: #dc3545;
            background-color: #fff5f5;
        }
    </style>
</head>
<body class="bg-light">
    <div class="dashboard">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topnav.php'; ?>

            <div class="container-fluid mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h1 class="h3 mb-0 text-danger"><i class="fas fa-quote-right me-2"></i>Testimonials Management</h1>
                    <div>
                        <button class="btn btn-danger" onclick="openModal('addTestimonialModal')">
                            <i class="fas fa-plus me-2"></i> Add Testimonial
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Filter Form -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-6 col-sm-12">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name, company, or message" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <button type="submit" class="btn btn-danger w-100">Filter</button>
                    </div>
                </form>

                <!-- Testimonials Table -->
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title mb-0">Testimonials</h5>
                        <div class="d-flex gap-2 mt-2 mt-md-0">
                            <div class="input-group">
                                <input type="text" id="tableSearch" class="form-control" placeholder="Quick search...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($testimonials)): ?>
                            <div class="alert alert-info mb-0 m-3">No testimonials found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Company</th>
                                            <th>Rating</th>
                                            <th>Message</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testimonials as $testimonial): ?>
                                        <tr data-id="<?= $testimonial['id'] ?>">
                                            <td>
                                                <img src="<?= htmlspecialchars($testimonial['image_path']) ?>" 
                                                     alt="<?= htmlspecialchars($testimonial['name']) ?>" 
                                                     class="testimonial-image"
                                                     onerror="this.src='img/testimonial/default.png'">
                                            </td>
                                            <td><?= htmlspecialchars($testimonial['name']) ?></td>
                                            <td><?= htmlspecialchars($testimonial['position'] ?: 'N/A') ?></td>
                                            <td><?= htmlspecialchars($testimonial['company'] ?: 'N/A') ?></td>
                                            <td>
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star<?= $i <= $testimonial['rating'] ? '' : '-o' ?>"></i>
                                                    <?php endfor; ?>
                                                    <small class="text-muted">(<?= $testimonial['rating'] ?>)</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                                     title="<?= htmlspecialchars($testimonial['message']) ?>">
                                                    <?= htmlspecialchars(substr($testimonial['message'], 0, 50)) ?>...
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="testimonial_id" value="<?= $testimonial['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= $testimonial['is_active'] ? 0 : 1 ?>">
                                                    <button type="submit" name="toggle_status" 
                                                            class="btn btn-sm status-badge <?= $testimonial['is_active'] ? 'btn-success' : 'btn-secondary' ?>"
                                                            title="Click to toggle status">
                                                        <?= $testimonial['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?= htmlspecialchars(date('M j, Y', strtotime($testimonial['created_at']))) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($testimonial)) ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                                                        <input type="hidden" name="testimonial_id" value="<?= $testimonial['id'] ?>">
                                                        <button type="submit" name="delete_testimonial" class="btn btn-sm btn-outline-danger" title="Delete">
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
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="text-muted small">
                                Total: <?= count($testimonials) ?> testimonials
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Testimonial Modal -->
            <div id="addTestimonialModal" class="custom-modal">
                <div class="custom-modal-content">
                    <div class="custom-modal-header bg-danger">
                        <h5><i class="fas fa-plus me-2"></i>Add New Testimonial</h5>
                        <button class="custom-modal-close" onclick="closeModal('addTestimonialModal')">&times;</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="addTestimonialForm">
                        <div class="custom-modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" placeholder="e.g., CEO, Manager">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Company</label>
                                    <input type="text" class="form-control" name="company" placeholder="Company name">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Rating *</label>
                                    <select class="form-select" name="rating" required>
                                        <option value="">Select Rating</option>
                                        <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                                        <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                                        <option value="3">⭐⭐⭐ (3 stars)</option>
                                        <option value="2">⭐⭐ (2 stars)</option>
                                        <option value="1">⭐ (1 star)</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Message *</label>
                                    <textarea class="form-control" name="message" rows="4" required 
                                              placeholder="Enter the testimonial message..."></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Profile Image</label>
                                    <div class="file-upload-area" onclick="document.getElementById('add_image_input').click()">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="mb-0">Click to upload image or drag and drop</p>
                                        <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>
                                    <input type="file" id="add_image_input" name="testimonial_image" 
                                           accept="image/*" style="display: none;" onchange="previewImage(this, 'add_image_preview')">
                                    <img id="add_image_preview" class="image-preview" style="display: none;">
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked>
                                        <label class="form-check-label" for="add_is_active">
                                            Active (visible on website)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('addTestimonialModal')">Close</button>
                            <button type="submit" name="add_testimonial" class="btn btn-danger">
                                <i class="fas fa-save me-2"></i>Save Testimonial
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Testimonial Modal -->
            <div id="editTestimonialModal" class="custom-modal">
                <div class="custom-modal-content">
                    <div class="custom-modal-header bg-primary">
                        <h5><i class="fas fa-edit me-2"></i>Edit Testimonial</h5>
                        <button class="custom-modal-close" onclick="closeModal('editTestimonialModal')">&times;</button>
                    </div>
                    <form id="editTestimonialForm" enctype="multipart/form-data">
                        <div class="custom-modal-body">
                            <input type="hidden" name="edit_testimonial" value="1">
                            <input type="hidden" name="testimonial_id" id="edit_testimonial_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Position</label>
                                    <input type="text" class="form-control" name="position" id="edit_position">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Company</label>
                                    <input type="text" class="form-control" name="company" id="edit_company">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Rating *</label>
                                    <select class="form-select" name="rating" id="edit_rating" required>
                                        <option value="5">⭐⭐⭐⭐⭐ (5 stars)</option>
                                        <option value="4">⭐⭐⭐⭐ (4 stars)</option>
                                        <option value="3">⭐⭐⭐ (3 stars)</option>
                                        <option value="2">⭐⭐ (2 stars)</option>
                                        <option value="1">⭐ (1 star)</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Message *</label>
                                    <textarea class="form-control" name="message" id="edit_message" rows="4" required></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Profile Image</label>
                                    <div class="file-upload-area" onclick="document.getElementById('edit_image_input').click()">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="mb-0">Click to upload new image or drag and drop</p>
                                        <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                    </div>
                                    <input type="file" id="edit_image_input" name="testimonial_image" 
                                           accept="image/*" style="display: none;" onchange="previewImage(this, 'edit_image_preview')">
                                    <img id="edit_image_preview" class="image-preview" style="display: none;">
                                    <div id="current_image_container" class="mt-2">
                                        <small class="text-muted">Current image:</small><br>
                                        <img id="current_image" class="image-preview">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                                        <label class="form-check-label" for="edit_is_active">
                                            Active (visible on website)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editTestimonialModal')">Close</button>
                            <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                                <i class="fas fa-save me-2"></i>Update Testimonial
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Custom Modal System
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            document.body.classList.add('modal-open');
            modal.classList.add('show');
            
            setTimeout(() => {
                const firstInput = modal.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
            
            // Reset form and preview
            const form = modal.querySelector('form');
            if (form) form.reset();
            
            const preview = modal.querySelector('.image-preview');
            if (preview) preview.style.display = 'none';
        }
    }
    
    function openEditModal(testimonialData) {
        // Populate form fields
        document.getElementById('edit_testimonial_id').value = testimonialData.id;
        document.getElementById('edit_name').value = testimonialData.name;
        document.getElementById('edit_position').value = testimonialData.position || '';
        document.getElementById('edit_company').value = testimonialData.company || '';
        document.getElementById('edit_rating').value = testimonialData.rating;
        document.getElementById('edit_message').value = testimonialData.message;
        document.getElementById('edit_is_active').checked = testimonialData.is_active == 1;
        
        // Show current image
        const currentImage = document.getElementById('current_image');
        currentImage.src = testimonialData.image_path;
        currentImage.onerror = function() { this.src = 'img/testimonial/default.png'; };
        
        // Hide new image preview
        document.getElementById('edit_image_preview').style.display = 'none';
        
        openModal('editTestimonialModal');
    }
    
    // Image preview function
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('custom-modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.custom-modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
    
    // Handle edit form submission
    document.getElementById('editTestimonialForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('editSubmitBtn');
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            
            const formData = new FormData(this);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Unknown error occurred');
            }
            
            closeModal('editTestimonialModal');
            
            // Show success message and reload
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                Testimonial updated successfully! Refreshing page...
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.card'));
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
        } catch (error) {
            console.error('Error:', error);
            alert(`Operation failed: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Table search functionality
    document.getElementById('tableSearch').addEventListener('keyup', function() {
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
    
    // Enhanced delete confirmation
    function confirmDelete(event) {
        event.preventDefault();
        
        const form = event.target;
        const testimonialRow = form.closest('tr');
        const name = testimonialRow.cells[1].textContent.trim();
        const company = testimonialRow.cells[3].textContent.trim();
        
        const confirmMessage = `Are you sure you want to delete this testimonial?\\n\\nName: ${name}\\nCompany: ${company}\\n\\nThis action cannot be undone.`;
        
        if (confirm(confirmMessage)) {
            const deleteBtn = form.querySelector('button[name="delete_testimonial"]');
            const originalContent = deleteBtn.innerHTML;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
            
            form.submit();
        }
        
        return false;
    }
    
    // Drag and drop functionality for file uploads
    document.querySelectorAll('.file-upload-area').forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = this.parentElement.querySelector('input[type="file"]');
                input.files = files;
                
                // Trigger preview
                const previewId = input.getAttribute('onchange').match(/'([^']+)'/)[1];
                previewImage(input, previewId);
            }
        });
    });
    
    console.log('Testimonials management system loaded successfully');
    </script>
</body>
</html>
