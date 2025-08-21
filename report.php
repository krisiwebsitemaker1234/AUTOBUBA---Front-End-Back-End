<?php
include_once 'config.php';

// Initialize variables
$user_data = null;
$notifications = ['orders' => 0, 'bookings' => 0, 'messages' => 0];
$success_message = '';
$error_message = '';

// Helper function for input sanitization
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        global $conn;
        if (!isset($conn)) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        return mysqli_real_escape_string($conn, trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
    }
}

// Get user data if logged in
if (isset($_SESSION['user_id'])) {
    try {
        $user_stmt = $conn->prepare("SELECT full_name, email, profile_image, role FROM users WHERE id = ? AND is_active = 1");
        $user_stmt->bind_param("i", $_SESSION['user_id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
        }
        $user_stmt->close();

        // Get notification counts
        $orders_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status != 'cancelled'");
        $orders_stmt->bind_param("i", $_SESSION['user_id']);
        $orders_stmt->execute();
        $orders_count = $orders_stmt->get_result()->fetch_assoc()['count'];
        $orders_stmt->close();

        $bookings_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status != 'cancelled'");
        $bookings_stmt->bind_param("i", $_SESSION['user_id']);
        $bookings_stmt->execute();
        $bookings_count = $bookings_stmt->get_result()->fetch_assoc()['count'];
        $bookings_stmt->close();

        $notifications = ['orders' => $orders_count, 'bookings' => $bookings_count, 'messages' => 0];
        
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $category = sanitize_input($_POST['category'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Name must be at least 2 characters long.";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid email address.";
        }
        
        if (empty($category)) {
            $errors[] = "Please select a problem category.";
        }
        
        if (empty($description) || strlen($description) < 10) {
            $errors[] = "Description must be at least 10 characters long.";
        }
        
        if (empty($errors)) {
            // Insert report into database
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $insert_stmt = $conn->prepare("
                INSERT INTO reports (user_id, name, email, category, description, status, priority, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', 'medium', NOW())
            ");
            
            $insert_stmt->bind_param("issss", $user_id, $name, $email, $category, $description);
            
            if ($insert_stmt->execute()) {
                $report_id = $conn->insert_id;
                $success_message = "Thank you for your report! Your report ID is #$report_id. We'll review it and get back to you soon.";
                
                // Clear form data
                $_POST = [];
                
            } else {
                throw new Exception("Failed to save report: " . $conn->error);
            }
            
            $insert_stmt->close();
            
        } else {
            $error_message = implode('<br>', $errors);
        }
        
    } catch (Exception $e) {
        error_log("Error processing report: " . $e->getMessage());
        $error_message = "An error occurred while processing your report. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="zxx">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <title>AUTOBUBA - Report a Problem</title>
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #2d3436;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --white-color: #ffffff;
            --success-color: #28a745;
            --error-color: #dc3545;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        /* Report Form Styles */
        .report-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .report-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .report-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), #c82333);
        }

        .report-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .report-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .report-header p {
            color: var(--gray-color);
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .report-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            background: #fafafa;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), #c82333);
            color: white;
            border: none;
            padding: 18px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
            background: linear-gradient(135deg, #c82333, #a71e2a);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .success-message {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: slideInDown 0.5s ease;
        }

        .error-message {
            background: linear-gradient(135deg, var(--error-color), #c82333);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: slideInDown 0.5s ease;
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

        /* Form validation styles */
        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: var(--error-color);
            background-color: #fff5f5;
        }

        .form-group .error-text {
            color: var(--error-color);
            font-size: 0.875rem;
            margin-top: 5px;
            display: none;
        }

        .form-group.error .error-text {
            display: block;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .report-container {
                margin: 20px;
                padding: 30px 25px;
            }

            .report-header h1 {
                font-size: 2rem;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 12px 15px;
            }
        }

        @media (max-width: 575px) {
            .report-container {
                margin: 10px;
                padding: 25px 20px;
            }

            .report-header h1 {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    <meta charset="UTF-8">
    <meta name="description" content="AUTOBUBA - Report a Problem">
    <meta name="keywords" content="AUTOBUBA, report, problem, support">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://kit.fontawesome.com/dfba5e2447.js" crossorigin="anonymous"></script>
    <title>AUTOBUBA - Report a Problem</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="css/elegant-icons.css" type="text/css">
    <link rel="stylesheet" href="css/nice-select.css" type="text/css">
    <link rel="stylesheet" href="css/magnific-popup.css" type="text/css">
    <link rel="stylesheet" href="css/jquery-ui.min.css" type="text/css">
    <link rel="stylesheet" href="css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="css/style.css" type="text/css">
    <link rel="stylesheet" href="loading.css">
</head>

<body>
    <!-- Loading screen and header code here (same as before) -->
    
    <!-- Report Section -->
    <section class="report-section">
        <div class="container">
            <div class="report-container">
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <i class="fa fa-check-circle"></i>
                        <?= htmlspecialchars($success_message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fa fa-exclamation-circle"></i>
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <div class="report-header">
                    <h1>
                        <i class="fa-solid fa-flag"></i>
                        <span>Report a Problem</span>
                    </h1>
                    <p>
                        We value your feedback and want to help resolve any issues you're experiencing. Please provide as much detail as possible so we can assist you effectively.
                    </p>
                </div>

                <form class="report-form" method="POST" action="" id="reportForm">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" placeholder="John Doe" required 
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : (isset($user_data['full_name']) ? htmlspecialchars($user_data['full_name']) : '') ?>">
                        <div class="error-text">Please enter your full name</div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($user_data['email']) ? htmlspecialchars($user_data['email']) : '') ?>">
                        <div class="error-text">Please enter a valid email address</div>
                    </div>

                    <div class="form-group">
                        <label for="category">Problem Category *</label>
                        <select id="category" name="category" required>
                            <option value="">-- Select a category --</option>
                            <option value="bug" <?= (isset($_POST['category']) && $_POST['category'] === 'bug') ? 'selected' : '' ?>>Bug / Glitch</option>
                            <option value="ui" <?= (isset($_POST['category']) && $_POST['category'] === 'ui') ? 'selected' : '' ?>>UI Issue</option>
                            <option value="performance" <?= (isset($_POST['category']) && $_POST['category'] === 'performance') ? 'selected' : '' ?>>Performance Issue</option>
                            <option value="feature" <?= (isset($_POST['category']) && $_POST['category'] === 'feature') ? 'selected' : '' ?>>Feature Request</option>
                            <option value="account" <?= (isset($_POST['category']) && $_POST['category'] === 'account') ? 'selected' : '' ?>>Account Issue</option>
                            <option value="payment" <?= (isset($_POST['category']) && $_POST['category'] === 'payment') ? 'selected' : '' ?>>Payment Issue</option>
                            <option value="feedback" <?= (isset($_POST['category']) && $_POST['category'] === 'feedback') ? 'selected' : '' ?>>General Feedback</option>
                            <option value="other" <?= (isset($_POST['category']) && $_POST['category'] === 'other') ? 'selected' : '' ?>>Other</option>
                        </select>
                        <div class="error-text">Please select a problem category</div>
                    </div>

                    <div class="form-group">
                        <label for="description">Describe the issue *</label>
                        <textarea id="description" name="description" placeholder="Please provide detailed information about the problem you're experiencing. Include steps to reproduce the issue if applicable..." rows="6" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                        <div class="error-text">Please provide a detailed description (minimum 10 characters)</div>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fa fa-paper-plane"></i>
                        <span>Submit Report</span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer and scripts here (same as before) -->
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('reportForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Name validation
            const name = document.getElementById('name');
            const nameGroup = name.closest('.form-group');
            if (name.value.trim().length < 2) {
                nameGroup.classList.add('error');
                isValid = false;
            } else {
                nameGroup.classList.remove('error');
            }
            
            // Email validation
            const email = document.getElementById('email');
            const emailGroup = email.closest('.form-group');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value.trim())) {
                emailGroup.classList.add('error');
                isValid = false;
            } else {
                emailGroup.classList.remove('error');
            }
            
            // Category validation
            const category = document.getElementById('category');
            const categoryGroup = category.closest('.form-group');
            if (!category.value) {
                categoryGroup.classList.add('error');
                isValid = false;
            } else {
                categoryGroup.classList.remove('error');
            }
            
            // Description validation
            const description = document.getElementById('description');
            const descriptionGroup = description.closest('.form-group');
            if (description.value.trim().length < 10) {
                descriptionGroup.classList.add('error');
                isValid = false;
            } else {
                descriptionGroup.classList.remove('error');
            }
            
            return isValid;
        }
        
        // Real-time validation
        document.getElementById('name').addEventListener('blur', validateForm);
        document.getElementById('email').addEventListener('blur', validateForm);
        document.getElementById('category').addEventListener('change', validateForm);
        document.getElementById('description').addEventListener('blur', validateForm);
        
        // Form submission
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }
                
                // Add loading state to submit button
                submitBtn.innerHTML = '<div class="spinner"></div> <span>Submitting...</span>';
                submitBtn.disabled = true;
            });
        }
        
        // Auto-hide success/error messages
        const successMessage = document.querySelector('.success-message');
        const errorMessage = document.querySelector('.error-message');
        
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 500);
            }, 5000);
        }
        
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.opacity = '0';
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 500);
            }, 8000);
        }
    });
    </script>
</body>
</html>