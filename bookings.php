<?php
include_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$user_stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get user's bookings
$bookings_query = "
    SELECT 
        id,
        first_name,
        last_name,
        email,
        phone,
        car_model,
        car_color,
        location,
        preferred_date,
        time_slot,
        status,
        created_at,
        updated_at
    FROM test_drive_bookings 
    WHERE user_id = ? 
    ORDER BY preferred_date DESC, created_at DESC
";

$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$bookings_stmt->close();

// Get booking statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM test_drive_bookings 
    WHERE user_id = ?
";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/quolywheels logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>QUOLYWHEELS - My Bookings</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #d90429, #ef233c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo a {
            text-decoration: none;
            color: inherit;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #d90429, #ef233c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Navigation */
        .nav-links {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(135deg, #d90429, #ef233c);
            color: white;
            transform: translateY(-2px);
        }

        /* Page Title */
        .page-title {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .page-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #333, #666);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.pending { background: linear-gradient(135deg, #ffa726, #ff9800); }
        .stat-icon.confirmed { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.completed { background: linear-gradient(135deg, #17a2b8, #138496); }
        .stat-icon.cancelled { background: linear-gradient(135deg, #dc3545, #c82333); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
        }

        /* Bookings Section */
        .bookings-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .book-new-btn {
            background: linear-gradient(135deg, #d90429, #ef233c);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .book-new-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Contact Info Banner */
        .contact-banner {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .contact-banner h3 {
            margin-bottom: 10px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .contact-banner p {
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .contact-item i {
            font-size: 1.1rem;
        }

        /* Booking Cards */
        .bookings-grid {
            display: grid;
            gap: 20px;
        }

        .booking-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #ddd;
            transition: all 0.3s ease;
            position: relative;
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .booking-card.pending { border-left-color: #ffa726; }
        .booking-card.confirmed { border-left-color: #28a745; }
        .booking-card.completed { border-left-color: #17a2b8; }
        .booking-card.cancelled { border-left-color: #dc3545; }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .booking-car {
            flex: 1;
        }

        .car-model {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .car-color {
            color: #666;
            font-weight: 500;
        }

        .booking-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(255, 167, 38, 0.1);
            color: #ff9800;
        }

        .status-confirmed {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-completed {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .detail-icon {
            width: 20px;
            color: #d90429;
        }

        .booking-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }

        .btn-view {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .btn-view:hover {
            background: #17a2b8;
            color: white;
            text-decoration: none;
        }

        /* Change Notice */
        .change-notice {
            background: rgba(255, 167, 38, 0.1);
            border: 1px solid rgba(255, 167, 38, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e65100;
        }

        .change-notice i {
            font-size: 1.2rem;
        }

        .change-notice-text {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-text {
            margin-bottom: 30px;
            line-height: 1.6;
        }

        /* Loading and Messages */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #d90429;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .message.error {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .message.show {
            display: flex;
            animation: slideInDown 0.3s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-overlay.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideInUp 0.3s ease;
        }

        .modal-header {
            padding: 25px 30px 20px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: #f8f9fa;
            color: #333;
        }

        .modal-body {
            padding: 30px;
        }

        /* Detail Item Styles */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-value {
            color: #666;
            font-weight: 500;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }

            .booking-actions {
                flex-wrap: wrap;
                justify-content: center;
            }

            .action-btn {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }

            .modal-content {
                margin: 20px;
                max-width: calc(100vw - 40px);
            }
            
            .modal-header,
            .modal-body {
                padding: 20px;
            }

            .contact-info {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <a href="index.php">QUOLYWHEELS</a>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($user_data['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user_data['full_name'] ?? 'User') ?></div>
                    <div style="font-size: 0.9rem; color: #666;"><?= htmlspecialchars($user_data['email'] ?? '') ?></div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="bookings.php" class="nav-link active">
                <i class="fas fa-calendar-alt"></i>
                My Bookings
            </a>
            <a href="book-test-drive.php" class="nav-link">
                <i class="fas fa-plus"></i>
                Book Test Drive
            </a>
            <a href="profile.php" class="nav-link">
                <i class="fas fa-user"></i>
                Profile
            </a>
        </div>

        <!-- Contact Banner -->
        <div class="contact-banner">
            <h3><i class="fas fa-phone-alt"></i> Need to Make Changes?</h3>
            <p>To cancel or reschedule your test drive appointment, please contact our customer service team</p>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span> +355 69 311 3209</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>support@quolywheels.com</span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span>Mon-Sat 9AM-7PM</span>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div class="message success" id="successMessage">
            <i class="fas fa-check-circle"></i>
            <span id="successText"></span>
        </div>

        <div class="message error" id="errorMessage">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="errorText"></span>
        </div>

        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="fas fa-calendar-check"></i> My Test Drive Bookings</h1>
            <p>View your test drive appointments and booking history</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number"><?= $stats['total_bookings'] ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= $stats['pending_bookings'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon confirmed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['confirmed_bookings'] ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div class="stat-number"><?= $stats['completed_bookings'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Bookings Section -->
        <div class="bookings-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-list"></i>
                    Your Bookings
                </h2>
                <a href="book-test-drive.php" class="book-new-btn">
                    <i class="fas fa-plus"></i>
                    Book New Test Drive
                </a>
            </div>

            <div class="bookings-grid">
                <?php if (empty($bookings)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div class="empty-title">No Bookings Yet</div>
                        <div class="empty-text">
                            You haven't made any test drive bookings yet.<br>
                            Start by booking your first test drive to experience our amazing vehicles!
                        </div>
                        <a href="book-test-drive.php" class="book-new-btn">
                            <i class="fas fa-car"></i>
                            Book Your First Test Drive
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card <?= $booking['status'] ?>" data-booking-id="<?= $booking['id'] ?>">
                            <div class="booking-header">
                                <div class="booking-car">
                                    <div class="car-model">
                                        <i class="fas fa-car"></i>
                                        <?= htmlspecialchars($booking['car_model']) ?>
                                    </div>
                                    <div class="car-color">
                                        <i class="fas fa-palette"></i>
                                        <?= htmlspecialchars($booking['car_color']) ?>
                                    </div>
                                </div>
                                <div class="booking-status status-<?= $booking['status'] ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </div>
                            </div>

                            <div class="booking-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar detail-icon"></i>
                                    <span><?= date('M j, Y', strtotime($booking['preferred_date'])) ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock detail-icon"></i>
                                    <span><?= ucfirst($booking['time_slot']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt detail-icon"></i>
                                    <span><?= ucfirst(str_replace('_', ' ', $booking['location'])) ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-phone detail-icon"></i>
                                    <span><?= htmlspecialchars($booking['phone']) ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-plus detail-icon"></i>
                                    <span>Booked on <?= date('M j, Y', strtotime($booking['created_at'])) ?></span>
                                </div>
                                <?php if ($booking['updated_at'] && $booking['updated_at'] !== $booking['created_at']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-edit detail-icon"></i>
                                    <span>Updated <?= date('M j, Y', strtotime($booking['updated_at'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php 
                            $booking_date = new DateTime($booking['preferred_date']);
                            $today = new DateTime();
                            if (($booking['status'] === 'pending' || $booking['status'] === 'confirmed') && $booking_date > $today): ?>
                                <div class="change-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <div class="change-notice-text">
                                        To cancel or reschedule this appointment, please call us at  +355 69 311 3209
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="booking-actions">
                                <button class="action-btn btn-view" data-booking-id="<?= $booking['id'] ?>" data-action="view">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal-overlay" id="detailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Booking Details</h3>
                <button class="modal-close" data-modal="detailsModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    // Utility functions
    function showMessage(type, text) {
        const messageDiv = document.getElementById(type + 'Message');
        const textSpan = document.getElementById(type + 'Text');
        textSpan.textContent = text;
        messageDiv.classList.add('show');
        
        setTimeout(() => {
            messageDiv.classList.remove('show');
        }, 5000);
    }

    function showLoading(show = true) {
        const overlay = document.getElementById('loadingOverlay');
        overlay.style.display = show ? 'flex' : 'none';
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    // AJAX helper function
    function makeRequest(url, data) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', text);
                    throw new Error('Invalid JSON response');
                }
            })
            .then(resolve)
            .catch(reject);
        });
    }

    // View booking details function
    function handleViewDetails(bookingId) {
        showLoading(true);

        makeRequest('get-booking-details.php', { booking_id: bookingId })
            .then(data => {
                if (data.success) {
                    const booking = data.booking;
                    
                    // Build the details HTML
                    const detailsHTML = `
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-hashtag"></i>
                                Booking ID
                            </div>
                            <div class="detail-value">#${booking.id}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-user"></i>
                                Customer Name
                            </div>
                            <div class="detail-value">${booking.first_name} ${booking.last_name}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </div>
                            <div class="detail-value">${booking.email}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-phone"></i>
                                Phone
                            </div>
                            <div class="detail-value">${booking.phone}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-car"></i>
                                Vehicle
                            </div>
                            <div class="detail-value">${booking.car_model} (${booking.car_color})</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-calendar"></i>
                                Date & Time
                            </div>
                            <div class="detail-value">${booking.formatted_date} - ${booking.time_range}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Location
                            </div>
                            <div class="detail-value">${booking.location_full}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-info-circle"></i>
                                Status
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-${booking.status}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-envelope-open"></i>
                                Newsletter
                            </div>
                            <div class="detail-value">${booking.newsletter_subscription ? 'Subscribed' : 'Not subscribed'}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-plus"></i>
                                Booked On
                            </div>
                            <div class="detail-value">${booking.formatted_created}</div>
                        </div>
                        
                        ${booking.formatted_updated ? `
                        <div class="detail-row">
                            <div class="detail-label">
                                <i class="fas fa-edit"></i>
                                Last Updated
                            </div>
                            <div class="detail-value">${booking.formatted_updated}</div>
                        </div>
                        ` : ''}

                        <div style="background: rgba(255, 167, 38, 0.1); border: 1px solid rgba(255, 167, 38, 0.3); border-radius: 10px; padding: 15px; margin-top: 20px; display: flex; align-items: center; gap: 10px; color: #e65100;">
                            <i class="fas fa-phone-alt" style="font-size: 1.2rem;"></i>
                            <div>
                                <strong>Need to make changes?</strong><br>
                                <span style="font-size: 0.9rem;">Call us at (555) 123-CARS or email support@quolywheels.com</span>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('detailsModalBody').innerHTML = detailsHTML;
                    openModal('detailsModal');
                } else {
                    showMessage('error', data.message || 'Failed to load booking details');
                }
            })
            .catch(error => {
                console.error('Details error:', error);
                showMessage('error', 'An error occurred while loading booking details: ' + error.message);
            })
            .finally(() => {
                showLoading(false);
            });
    }

    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up event listeners...');
        
        // Event delegation for action buttons
        document.addEventListener('click', function(e) {
            const target = e.target.closest('[data-action]');
            if (!target) return;

            const action = target.getAttribute('data-action');
            const bookingId = target.getAttribute('data-booking-id');

            console.log('Action clicked:', action, 'Booking ID:', bookingId);

            if (action === 'view') {
                handleViewDetails(bookingId);
            }
        });

        // Modal close buttons
        document.addEventListener('click', function(e) {
            const target = e.target.closest('[data-modal]');
            if (!target) return;

            const modalId = target.getAttribute('data-modal');
            closeModal(modalId);
        });

        // Close modals when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                    closeModal(modal.id);
                });
            }
        });

        // Add smooth animations on page load
        const cards = document.querySelectorAll('.booking-card, .stat-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        console.log('Event listeners set up successfully');
    });
</script>
</body>
</html>
