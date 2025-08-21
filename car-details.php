<?php
include_once 'config.php';

// Get car ID from URL parameter
$car_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch car details
$car = [];
if ($car_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
}

if (empty($car)) {
    die("Car not found");
}

// UPDATED: Always show "Available" regardless of actual status
$status_text = 'Available';
$price_display = '$' . number_format($car['price'], 2);

// Handle image path
$image_path = $car['image_path'] ?? 'img/cars/default.jpg';
if (!file_exists($image_path)) {
    $image_path = 'img/cars/default.jpg';
}

// UPDATED: Fetch similar vehicles (same make) - only available vehicles
$similar_cars = [];
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE make = ? AND id != ? AND status IN ('In Stock', 'Reserved') LIMIT 3");
$stmt->bind_param("si", $car['make'], $car_id);
$stmt->execute();
$similar_result = $stmt->get_result();
while ($row = $similar_result->fetch_assoc()) {
    $similar_cars[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUTOBUBA <?= htmlspecialchars($car['make'] ?? '') ?> <?= htmlspecialchars($car['model'] ?? '') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Car Details Page Styles */
        body {
            background-color: #f8f9fa;
        }
        
        .breadcrumb-section {
            padding: 20px 0;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        
        .car-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #212529;
        }
        
        .car-subtitle {
            color: #6c757d;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .car-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .car-main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .car-image-container:hover .car-main-image {
            transform: scale(1.03);
        }
        
        /* Elegant status indicator */
        .status-indicator {
            position: absolute;
            top: 20px;
            right: -60px;
            background: #d90429;
            color: white;
            padding: 8px 60px;
            font-weight: 600;
            transform: rotate(45deg);
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .price-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border-top: 5px solid #d90429;
        }
        
        .price-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #d90429;
            margin-bottom: 25px;
        }
        
        .price-label {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .action-btn {
            padding: 14px 20px;
            font-weight: 600;
            margin-bottom: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .spec-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .spec-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .spec-label {
            font-weight: 600;
            color: #212529;
        }
        
        .spec-value {
            color: #6c757d;
        }
        
        .section-divider {
            height: 1px;
            background: #e9ecef;
            margin: 60px 0;
        }
        
        .similar-cars-section {
            margin: 60px 0;
        }
        
        .similar-car-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .similar-car-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .similar-car-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        
        .similar-car-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
            height: calc(100% - 200px);
        }
        
        .similar-car-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #212529;
        }
        
        .similar-car-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #d90429;
            margin-bottom: 20px;
        }
        
        .similar-car-specs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: #6c757d;
        }
        
        .contact-form input,
        .contact-form textarea {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e1e1e1;
            transition: all 0.3s ease;
        }
        
        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #d90429;
            box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
        }
        
        .contact-info-item {
            padding: 15px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .contact-info-item:last-child {
            border-bottom: none;
        }
        
        .contact-icon {
            width: 40px;
            height: 40px;
            background: rgba(217, 4, 41, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d90429;
            margin-right: 15px;
        }
        
        .status-tag {
            display: inline-block;
            margin-left: 15px;
            padding: 5px 12px;
            background-color: #f8f9fa;
            border-left: 3px solid #d90429;
            font-size: 0.9rem;
            font-weight: 600;
            color: #d90429;
            vertical-align: middle;
        }

        /* Car Description Section - White Background with Effects */
        .car-description {
        margin-top: 2rem;
        padding: 2rem;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        }

        /* Subtle gradient overlay */
        .car-description::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #d90429, #ff6b6b, #d90429);
        background-size: 200% 100%;
        animation: gradientShift 3s ease-in-out infinite;
        }

        /* Hover effect */
        .car-description:hover {
        transform: translateY(-2px);
        box-shadow: 
            0 10px 15px -3px rgba(0, 0, 0, 0.1),
            0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Heading styling */
        .car-description h4 {
        color: #1a202c;
        font-size: 1.375rem;
        font-weight: 700;
        margin-bottom: 1.25rem;
        position: relative;
        padding-left: 1rem;
        letter-spacing: -0.025em;
        }

        /* Icon before heading */
        .car-description h4::before {
        content: '📄';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2rem;
        filter: grayscale(0.3);
        }

        /* Underline effect */
        .car-description h4::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 1rem;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, #d90429, transparent);
        border-radius: 2px;
        opacity: 0.8;
        }

        /* Description text */
        .car-description p {
        color: #4a5568;
        font-size: 1rem;
        line-height: 1.75;
        margin: 0;
        text-align: justify;
        hyphens: auto;
        position: relative;
        padding-left: 1rem;
        }

        /* Subtle left border for text */
        .car-description p::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(180deg, #d90429, transparent);
        border-radius: 1px;
        opacity: 0.3;
        }

        /* Floating elements for visual interest */
        .car-description::after {
        content: '';
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
        background: radial-gradient(circle, rgba(217, 4, 41, 0.1), transparent);
        border-radius: 50%;
        pointer-events: none;
        }

        /* Animation for gradient */
        @keyframes gradientShift {
        0%, 100% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        }

        /* Responsive design */
        @media (max-width: 768px) {
        .car-description {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-radius: 8px;
        }
        
        .car-description h4 {
            font-size: 1.25rem;
            padding-left: 0.75rem;
        }
        
        .car-description h4::after {
            left: 0.75rem;
            width: 50px;
        }
        
        .car-description p {
            font-size: 0.95rem;
            padding-left: 0.75rem;
            text-align: left;
        }
        }

        /* Alternative style with glass effect */
        .car-description-glass {
        margin-top: 2rem;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 
            0 8px 32px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.5);
        position: relative;
        overflow: hidden;
        }

        /* Subtle pattern overlay */
        .car-description-pattern {
        margin-top: 2rem;
        padding: 2rem;
        background: #ffffff;
        background-image: 
            radial-gradient(circle at 1px 1px, rgba(217, 4, 41, 0.03) 1px, transparent 0);
        background-size: 20px 20px;
        border-radius: 12px;
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        }

        .car-description-pattern:hover {
        background-image: 
            radial-gradient(circle at 1px 1px, rgba(217, 4, 41, 0.05) 1px, transparent 0);
        }
        
        /* Responsive adjustments */
        @media (max-width: 991px) {
            .car-main-image {
                height: 400px;
            }
            
            .car-title {
                font-size: 2.2rem;
            }
            
            .price-value {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 767px) {
            .car-main-image {
                height: 300px;
            }
            
            .car-title {
                font-size: 1.8rem;
            }
            
            .price-value {
                font-size: 2rem;
            }
            
            .spec-card, .price-card {
                padding: 20px;
            }
            
            .status-indicator {
                top: 15px;
                right: -65px;
                padding: 5px 60px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Breadcrumb Section -->
    <section class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="car.html" class="text-decoration-none">Cars</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($car['make'] ?? '') ?> <?= htmlspecialchars($car['model'] ?? '') ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Car Details Section -->
    <section class="car-details-section">
        <div class="container">
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Car Title with Status Tag -->
                    <div class="d-flex align-items-center flex-wrap">
                        <h1 class="car-title"><?= htmlspecialchars($car['make'] ?? '') ?> <?= htmlspecialchars($car['model'] ?? '') ?></h1>
                        <span class="status-tag">Available</span>
                    </div>
                    <p class="car-subtitle"><?= htmlspecialchars($car['year'] ?? '') ?> • <?= number_format($car['mileage'] ?? 0) ?> miles • <?= htmlspecialchars($car['horsepower'] ?? '') ?> HP</p>
                    
                    <!-- Main Image with Ribbon -->
                    <div class="car-image-container">
                        <div class="status-indicator">Available</div>
                        <img src="<?= $image_path ?>" class="car-main-image" alt="<?= htmlspecialchars($car['make'] ?? '') ?> <?= htmlspecialchars($car['model'] ?? '') ?>">
                    </div>
                    
                    <!-- Car Description -->
                    <div class="car-description-glass">
                        <h4>Description</h4>
                        <p><?= htmlspecialchars($car['description'] ?? 'No description available.') ?></p>
                    </div>
                    
                    <!-- Technical Specifications -->
                    <div class="spec-card mt-5">
                        <h2 class="spec-title">Vehicle Specifications</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Make</span>
                                    <span class="spec-value"><?= htmlspecialchars($car['make'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Model</span>
                                    <span class="spec-value"><?= htmlspecialchars($car['model'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Year</span>
                                    <span class="spec-value"><?= htmlspecialchars($car['year'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Mileage</span>
                                    <span class="spec-value"><?= number_format($car['mileage'] ?? 0) ?> miles</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Horsepower</span>
                                    <span class="spec-value"><?= htmlspecialchars($car['horsepower'] ?? 'N/A') ?> HP</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Transmission</span>
                                    <span class="spec-value">Automatic</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Fuel Type</span>
                                    <span class="spec-value">Gasoline</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="spec-item">
                                    <span class="spec-label">Body Style</span>
                                    <span class="spec-value">Sedan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Price Card -->
                    <div class="price-card">
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="price-label">Price</span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                                    Available
                                </span>
                            </div>
                            <div class="price-value"><?= $price_display ?></div>
                        </div>
                        
                        
                        <div class="d-grid gap-3">
                            <a href="Book a Test Drive.html" class="btn btn-danger action-btn">
                                <i class="fas fa-car me-2"></i> Schedule Test Drive
                            </a>
                            
                            <button class="btn btn-outline-danger action-btn add-to-cart-btn"
                                    data-id="<?= $car['id'] ?>"
                                    data-name="<?= htmlspecialchars($car['make'] ?? '') . ' ' . htmlspecialchars($car['model'] ?? '') ?>"
                                    data-price="<?= $car['price'] ?>"
                                    data-img="<?= $image_path ?>"
                                    data-type="<?= strtolower($car['status'] ?? '') ?>">
                                <i class="fas fa-cart-plus me-2"></i> Add to Cart
                            </button>
                            
                            <button class="btn btn-outline-secondary action-btn" id="shareButton">
                                <i class="fas fa-share-alt me-2"></i> Share Vehicle
                            </button>
                        </div>
                    </div>
                    
                    <!-- Contact Card -->
                    <div class="spec-card">
                        <h3 class="spec-title">Contact Us</h3>
                        <div class="mb-4">
                            <div class="d-flex align-items-center contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <span>+355 69 311 3209</span>
                            </div>
                            <div class="d-flex align-items-center contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span>autobuba2025@gmail.com</span>
                            </div>
                            <div class="d-flex align-items-center contact-info-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <span>123 Auto Street, Tirana, Albania</span>
                            </div>
                        </div>
                        
                        <form class="contact-form">
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Your Name">
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Your Email">
                            </div>
                            <div class="mb-3">
                                <input type="tel" class="form-control" placeholder="Your Phone">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger action-btn">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- UPDATED: Similar Cars Section with "Available" badges -->
            <?php if (!empty($similar_cars)): ?>
            <div class="section-divider"></div>
            
            <div class="similar-cars-section">
                <h2 class="spec-title">Similar Vehicles</h2>
                <div class="row">
                    <?php foreach ($similar_cars as $similar): 
                        $similar_price = '$' . number_format($similar['price'], 2);
                        
                        $similar_img = $similar['image_path'] ?? 'img/cars/default.jpg';
                        if (!file_exists($similar_img)) {
                            $similar_img = 'img/cars/default.jpg';
                        }
                    ?>
                    <div class="col-md-4 mb-4">
                        <div class="similar-car-card">
                            <!-- UPDATED: Show "Available" instead of status-specific text -->
                            <div class="status-indicator" style="transform: rotate(45deg); top: 15px; right: -65px; padding: 5px 60px; font-size: 0.8rem;">
                                Available
                            </div>
                            <img src="<?= $similar_img ?>" class="similar-car-img" alt="<?= htmlspecialchars($similar['make'] ?? '') ?> <?= htmlspecialchars($similar['model'] ?? '') ?>">
                            <div class="similar-car-body">
                                <h3 class="similar-car-title"><?= htmlspecialchars($similar['year'] ?? '') ?> <?= htmlspecialchars($similar['make'] ?? '') ?> <?= htmlspecialchars($similar['model'] ?? '') ?></h3>
                                <div class="similar-car-price"><?= $similar_price ?></div>
                                <div class="similar-car-specs">
                                    <span><i class="fas fa-tachometer-alt me-1"></i> <?= number_format($similar['mileage'] ?? 0) ?> mi</span>
                                    <span><i class="fas fa-cog me-1"></i> Auto</span>
                                    <span><i class="fas fa-horse me-1"></i> <?= htmlspecialchars($similar['horsepower'] ?? 'N/A') ?> HP</span>
                                </div>
                                <div class="d-grid mt-auto">
                                    <a href="car-details.php?id=<?= $similar['id'] ?>" class="btn btn-outline-danger action-btn">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/cart.js"></script>
    
    <script>
        // Calculate monthly payment
        function calculatePayment() {
            const price = <?= $car['price'] ?>;
            const downPayment = parseFloat(document.getElementById('downPayment').value.replace(/,/g, ''));
            const loanTerm = parseInt(document.getElementById('loanTerm').value);
            const interestRate = parseFloat(document.getElementById('interestRate').value) / 100 / 12;
            
            const principal = price - downPayment;
            const x = Math.pow(1 + interestRate, loanTerm);
            const monthly = (principal * x * interestRate) / (x - 1);
            
            document.getElementById('monthlyPayment').innerHTML = `
                <div class="alert alert-info">
                    <strong>Estimated Monthly Payment:</strong> $${monthly.toFixed(2)}
                </div>
            `;
        }
        
        // Share functionality
        document.getElementById('shareButton').addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= htmlspecialchars($car['year'] ?? '') ?> <?= htmlspecialchars($car['make'] ?? '') ?> <?= htmlspecialchars($car['model'] ?? '') ?>',
                    text: 'Check out this <?= htmlspecialchars($car['year'] ?? '') ?> <?= htmlspecialchars($car['make'] ?? '') ?> <?= htmlspecialchars($car['model'] ?? '') ?> on AUTOBUBA!',
                    url: window.location.href
                })
                .catch(error => console.log('Error sharing:', error));
            } else {
                // Fallback for browsers that don't support Web Share API
                const url = window.location.href;
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = url;
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                alert('Link copied to clipboard!');
            }
        });
    </script>
</body>
</html>