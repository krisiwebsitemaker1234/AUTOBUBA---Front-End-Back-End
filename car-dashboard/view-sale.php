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

if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit;
}

$sale = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, v.make, v.model, v.year, c.name AS customer_name, c.email, c.phone 
        FROM sales s
        JOIN vehicles v ON s.vehicle_id = v.id
        JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching sale: " . $e->getMessage());
    header("Location: sales.php?error=SaleNotFound");
    exit;
}

if (!$sale) {
    header("Location: sales.php?error=SaleNotFound");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sale Details | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" href="img/quolywheels logo.png">
</head>
<body class="bg-light">
    <?php include 'includes/sidebar.php'; ?>
    
    <main style="margin-left: 250px; padding: 20px;">
        <?php include 'includes/topnav.php'; ?>

        <div class="container mt-4">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h2 class="h4 mb-0">Sale Details</h2>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9"><?= date('M j, Y', strtotime($sale['sale_date'])) ?></dd>

                        <dt class="col-sm-3">Customer</dt>
                        <dd class="col-sm-9">
                            <?= htmlspecialchars($sale['customer_name']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($sale['email']) ?><br>
                            <?= htmlspecialchars($sale['phone']) ?></small>
                        </dd>

                        <dt class="col-sm-3">Vehicle</dt>
                        <dd class="col-sm-9">
                            <?= htmlspecialchars("{$sale['year']} {$sale['make']} {$sale['model']}") ?>
                        </dd>

                        <dt class="col-sm-3">Sale Price</dt>
                        <dd class="col-sm-9">$<?= number_format($sale['sale_price'], 2) ?></dd>

                        <dt class="col-sm-3">Payment Method</dt>
                        <dd class="col-sm-9"><?= ucfirst($sale['payment_method']) ?></dd>
                    </dl>
                    <a href="sales.php" class="btn btn-danger">Back to Sales</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>