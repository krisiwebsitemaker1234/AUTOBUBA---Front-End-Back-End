<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAuth();

if (!isset($_GET['id'])) {
    header("Location: sales.php");
    exit;
}

// ADMIN ACCESS CONTROL - Only allow admin users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admin users to a different page or show access denied
    header("Location: access-denied.php");
    exit();
}

$sale = [];
try {
    $stmt = $pdo->prepare("
        SELECT s.*, v.make, v.model, v.year, v.vin, 
               c.name AS customer_name, c.address, c.city, c.state, c.zip 
        FROM sales s
        JOIN vehicles v ON s.vehicle_id = v.id
        JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error generating invoice");
}

if (!$sale) die("Invalid sale record");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $sale['id'] ?> | <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="icon" href="img/quolywheels logo.png">
    <style>
        /* Base styles */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }

        .invoice-wrapper {
            background: white;
            width: 100%;
            max-width: 800px;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            padding: 30px 40px;
            border-bottom: 2px solid #e74c3c;
        }

        .company-info {
            text-align: right;
            color: #2c3e50;
        }

        .invoice-meta {
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
        }

        .bill-to {
            flex: 1;
            color: #34495e;
        }

        .invoice-details {
            color: #34495e;
            text-align: right;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table th {
            background: #e74c3c;
            color: white;
            padding: 15px 40px;
            text-align: left;
        }
        .details-table td {
            padding: 15px 40px;
            border-bottom: 1px solid #ecf0f1;
            color: #2c3e50;
        }

        .total-section {
            padding: 25px 40px;
            background: #f8f9fa;
            text-align: right;
        }
        .total-amount {
            color: #e74c3c;
            font-size: 1.5em;
            font-weight: bold;
        }

        .footer {
            padding: 30px 40px;
            border-top: 1px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .no-print {
            padding: 30px 40px;
            background: #f8f9fa;
            border-top: 1px solid #ecf0f1;
        }
        
        /* Print-specific styles */
        @media print {
            @page {
                margin: 0;
                size: 8.5in 11in;
            }
            body { 
                background: white !important;
                margin: 0.5in !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .invoice-wrapper {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .text-danger { color: #dc3545 !important; }
            .text-muted { color: #6c757d !important; }
            
            /* Hide URL and page info */
            ::-webkit-scrollbar { display: none; }
            ::file-selector-button { display: none; }
            ::-webkit-scrollbar-button { display: none; }
        }

        .invoice-label {
            font-size: 0.85em; 
            color: #7f8c8d;
            margin-bottom: 4px;
        }
        
        .customer-location {
            margin-top: 5px;
            color: #7f8c8d;
        }
        
        .btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary {
            background: transparent;
            color: #7f8c8d;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        <div class="header-section">
            <div>
                <h1 style="font-size: 2.2em; margin: 0; color: #e74c3c">INVOICE</h1>
                <p style="color: #7f8c8d; margin: 5px 0 0 2px">#<?= $sale['id'] ?></p>
            </div>
            <div class="company-info">
                <h2 style="margin: 0 0 5px 0; color: #2c3e50"><?= htmlspecialchars(SITE_NAME) ?></h2>
                <p style="margin: 3px 0">123 Auto Mall Blvd</p>
                <p style="margin: 3px 0">New York, NY 10001</p>
                <p style="margin: 3px 0">Phone: (555) 123-4567</p>
            </div>
        </div>

        <div class="invoice-meta">
            <div class="bill-to">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50">Bill To:</h3>
                <div>
                    <strong style="font-size: 1.1em; color: #2c3e50"><?= htmlspecialchars($sale['customer_name']) ?></strong>
                    <div class="customer-location">
                        <?= htmlspecialchars($sale['address'] ?? '') ?><br>
                        <?= htmlspecialchars($sale['city'] ?? '') ?>, 
                        <?= htmlspecialchars($sale['state'] ?? '') ?> 
                        <?= htmlspecialchars($sale['zip'] ?? '') ?>
                    </div>
                </div>
            </div>
            <div class="invoice-details">
                <div>
                    <div class="invoice-label">Invoice Date:</div>
                    <strong><?= date('M j, Y', strtotime($sale['sale_date'])) ?></strong>
                </div>
                <div style="margin-top: 10px">
                    <div class="invoice-label">Due Date:</div>
                    <strong><?= date('M j, Y', strtotime($sale['sale_date'] . '+7 days')) ?></strong>
                </div>
                <div style="margin-top: 10px">
                    <div class="invoice-label">Payment Method:</div>
                    <strong><?= ucfirst($sale['payment_method']) ?></strong>
                </div>
            </div>
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>VIN</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong style="color: #2c3e50"><?= htmlspecialchars("{$sale['year']} {$sale['make']} {$sale['model']}") ?></strong>
                    </td>
                    <td style="color: #7f8c8d"><?= htmlspecialchars($sale['vin']) ?></td>
                    <td style="color: #e74c3c; font-weight: bold">$<?= number_format($sale['sale_price'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-amount">
                Total Due: $<?= number_format($sale['sale_price'], 2) ?>
            </div>
        </div>

        <div class="footer">
            <p style="margin: 0">Thank you for choosing <?= htmlspecialchars(SITE_NAME) ?>!</p>
            <p style="margin: 5px 0 0 0; font-size: 0.9em">
                Payment terms: Net 7 days | Late fee: 1.5% monthly interest
            </p>
        </div>

        <div class="no-print">
            <a href="#" onclick="window.print(); return false;" class="btn">Print Invoice</a>
            <a href="sales.php" class="btn btn-secondary">Back to Sales</a>
        </div>
    </div>
</body>
</html>