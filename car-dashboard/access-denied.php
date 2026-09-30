<?php
include_once 'config.php';

// If user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'customer';
$user_name = $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/quolywheels logo.png">
    <title>Access Denied - QUOLYWHEELS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 35%, #ffffff 65%, #f8f9fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        .access-denied-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .access-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
        }
        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
        }
        .user-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="access-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h2 class="mb-3 text-danger">Access Denied</h2>
        
        <div class="user-info">
            <p class="mb-1"><strong>Current User:</strong> <?= htmlspecialchars($user_name) ?></p>
            <p class="mb-0"><strong>Role:</strong> <span class="badge bg-secondary"><?= ucfirst($user_role) ?></span></p>
        </div>
        
        <p class="text-muted mb-4">
            Sorry, you don't have permission to access the admin dashboard. 
            This area is restricted to administrators only.
        </p>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <a href="customer-dashboard.php" class="btn btn-primary">
                <i class="fas fa-user me-2"></i>Customer Dashboard
            </a>
            <a href="logout.php" class="btn btn-outline-secondary">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                Need admin access? Contact your system administrator.
            </small>
        </div>
    </div>
</body>
</html>