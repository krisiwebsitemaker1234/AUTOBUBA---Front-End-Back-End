<?php
include_once 'config.php';

$message = '';
$message_type = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    $message = "Invalid verification link. Please check your email for the correct verification link.";
    $message_type = 'error';
} else {
    // Check if token exists and is valid
    $stmt = $conn->prepare("SELECT id, email, full_name, email_verified, created_at FROM users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if token is not expired (24 hours)
        $created_time = strtotime($user['created_at']);
        $current_time = time();
        $time_diff = $current_time - $created_time;
        
        if ($time_diff > 86400) { // 24 hours = 86400 seconds
            $message = "Verification link has expired. Please register again or request a new verification email.";
            $message_type = 'error';
        } elseif ($user['email_verified'] == 1) {
            $message = "Your email is already verified! You can now login to your account.";
            $message_type = 'success';
        } else {
            // Verify the email
            $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, status = 'active' WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            
            if ($update_stmt->execute()) {
                $message = "Congratulations! Your email has been verified successfully. Welcome to QUOLYWHEELS!";
                $message_type = 'success';
            } else {
                $message = "Failed to verify email. Please try again or contact support.";
                $message_type = 'error';
            }
            $update_stmt->close();
        }
    } else {
        $message = "Invalid or expired verification token. Please check your email for the correct link.";
        $message_type = 'error';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - QUOLYWHEELS</title>
    <link rel="icon" href="img/quolywheels logo.png" type="image/png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #b02a37 35%, #ffffff 65%, #f8f9fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verify-container {
            background: white;
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(220, 53, 69, 0.15),
                0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            text-align: center;
            border: 2px solid rgba(220, 53, 69, 0.1);
        }

        .verify-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 50%, #b02a37 100%);
            color: white;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .verify-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-30px) translateY(-30px); }
        }

        .verify-header .content {
            position: relative;
            z-index: 2;
        }

        .verify-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .verify-icon.success {
            color: #28a745;
        }

        .verify-icon.error {
            color: #dc3545;
        }

        .verify-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .verify-body {
            padding: 3rem 2rem;
        }

        .message {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            color: #333;
        }

        .message.success {
            color: #28a745;
        }

        .message.error {
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
            text-decoration: none;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo img {
            max-height: 60px;
            filter: brightness(0) invert(1);
        }

        .resend-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #dc3545;
        }

        .resend-section h4 {
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .resend-section p {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .verify-container {
                margin: 10px;
                border-radius: 20px;
            }

            .verify-header {
                padding: 2rem 1.5rem;
            }

            .verify-header h1 {
                font-size: 2rem;
            }

            .verify-body {
                padding: 2rem 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <div class="content">
                <div class="logo">
                    <img src="img/logo zoom.png" alt="QUOLYWHEELS" class="img-fluid">
                </div>
                <div class="verify-icon <?= $message_type ?>">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php endif; ?>
                </div>
                <h1>Email Verification</h1>
            </div>
        </div>

        <div class="verify-body">
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>

            <div class="action-buttons">
                <?php if ($message_type === 'success'): ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Login Now
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-2"></i>
                        Go to Homepage
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>
                        Register Again
                    </a>
                    <a href="resend-verification.php" class="btn btn-secondary">
                        <i class="fas fa-envelope me-2"></i>
                        Resend Verification
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($message_type === 'error'): ?>
                <div class="resend-section">
                    <h4><i class="fas fa-info-circle me-2"></i>Need Help?</h4>
                    <p>If you're having trouble with email verification:</p>
                    <ul class="text-start">
                        <li>Check your spam/junk folder</li>
                        <li>Make sure you clicked the most recent verification link</li>
                        <li>Verification links expire after 24 hours</li>
                        <li>Contact our support team if the problem persists</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
