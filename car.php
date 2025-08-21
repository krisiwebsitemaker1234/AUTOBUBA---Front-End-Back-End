<?php
include_once 'config.php';

// Check if this is an AJAX request
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

// Get user data if logged in
$user_data = null;
$notifications = ['orders' => 0, 'bookings' => 0, 'messages' => 0];

if (isset($_SESSION['user_id'])) {
    $user_stmt = $conn->prepare("SELECT full_name, email, profile_image, role FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $_SESSION['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    if ($user_result) {
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
}

if ($isAjax) {
    // Handle AJAX filter request
    $whereConditions = [];
    $params = [];
    $types = "";

    // Base query - exclude sold cars
    $baseQuery = "FROM vehicles WHERE status != 'Sold'";

    // Process search filter
    if (!empty($_POST['search'])) {
        $search = '%' . $_POST['search'] . '%';
        $whereConditions[] = "(make LIKE ? OR model LIKE ? OR CONCAT(make, ' ', model) LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }

    // Process price filter
    if (!empty($_POST['max_price'])) {
        $maxPrice = (int)$_POST['max_price'];
        $whereConditions[] = "price <= ?";
        $params[] = $maxPrice;
        $types .= "i";
    }

    // Process brand filter
    if (!empty($_POST['brands']) && is_array($_POST['brands'])) {
        $brandPlaceholders = str_repeat('?,', count($_POST['brands']) - 1) . '?';
        $whereConditions[] = "make IN ($brandPlaceholders)";
        foreach ($_POST['brands'] as $brand) {
            $params[] = $brand;
            $types .= "s";
        }
    }

    // Build WHERE clause
    $whereClause = "";
    if (!empty($whereConditions)) {
        $whereClause = " AND " . implode(" AND ", $whereConditions);
    }

    // Process sorting
    $orderBy = "ORDER BY id DESC"; // Default sorting
    if (!empty($_POST['sort'])) {
        switch ($_POST['sort']) {
            case 'price-low':
                $orderBy = "ORDER BY price ASC";
                break;
            case 'price-high':
                $orderBy = "ORDER BY price DESC";
                break;
            case 'newest':
                $orderBy = "ORDER BY year DESC";
                break;
            case 'oldest':
                $orderBy = "ORDER BY year ASC";
                break;
            default:
                $orderBy = "ORDER BY id DESC";
        }
    }

    // Process pagination - Maximum 25 cars per page
    $limit = isset($_POST['show']) ? min((int)$_POST['show'], 25) : 9;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $totalRows = $totalResult->fetch_assoc()['total'];
    $totalPages = ceil($totalRows / $limit);

    // Get vehicles with filters
    $vehicleQuery = "SELECT * " . $baseQuery . $whereClause . " " . $orderBy . " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($vehicleQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Generate HTML for cars
    $carsHtml = '';
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $badge_type = ($row['status'] == 'Reserved') ? 'For Rent' : 'For Sale';
            $price_display = ($row['status'] == 'Reserved') ? 
                '$'.number_format($row['price'], 2)." <span>/mo</span>" : 
                '$'.number_format($row['price'], 2);
            
            $car_type = ($row['status'] == 'Reserved') ? 'rent' : 'sale';
            
            // Check if car is not in stock (only rented cars)
            $isRented = ($row['status'] == 'Reserved' || $row['status'] == 'Rented');
            $notInStock = $isRented; // Only rented cars are not in stock
            
            // Handle image path properly
            $image_path = $row['image_path'];
            if (empty($image_path) || $image_path == 'img/cars/default.jpg' || !file_exists($image_path)) {
                $image_path = 'img/cars/car-1.jpg'; // fallback image
            }

            $carsHtml .= '
<div class="col-lg-6 col-md-12 mb-4 car-item">
    <div class="car__item h-100 ' . ($notInStock ? 'not-in-stock' : '') . '">
        <div class="car__item__pic">
            <img src="'.$image_path.'" 
                 alt="'.$row['make'].' '.$row['model'].'" 
                 class="img-fluid w-100"
                 style="height: 220px; object-fit: cover;"
                 onerror="this.src=\'img/cars/car-1.jpg\'">
            ' . ($notInStock ? '
            <div class="not-in-stock-overlay">
                <div class="not-in-stock-label">
                    <i class="fa fa-ban"></i>
                    <span>NOT IN STOCK</span>
                    <small>Currently Rented</small>
                </div>
            </div>
            ' : '') . '
            <div class="car__item__pic__overlay">
                <a href="car-details.php?id='.$row['id'].'" class="btn btn-primary view-more-btn">
                    <i class="fa fa-eye"></i> View Details
                </a>
            </div>
        </div>
        <div class="label-date">'.$row['year'].'</div>
        <div class="car__item__text">
            <div class="car__item__text__inner">
                <h5><a href="car-details.php?id='.$row['id'].'">'.$row['make'].' '.$row['model'].'</a></h5>
                <div class="car-specs">
                    <div class="car-spec">
                        <i class="fa fa-road"></i>
                        <span>'.number_format($row['mileage']).'</span>
                        <small data-translate="miles">miles</small>
                    </div>
                    <div class="car-spec">
                        <i class="fa fa-cog"></i>
                        <span data-translate="Auto">Auto</span>
                        <small data-translate="trans">trans</small>
                    </div>
                    <div class="car-spec">
                        <i class="fa fa-tachometer-alt"></i>
                        <span>'.($row['horsepower'] ?? '300').'</span>
                        <small data-translate="hp">hp</small>
                    </div>
                </div>
            </div>
            <div class="car__item__price">
                <div>
                    <span class="car-option '.strtolower($car_type).'" data-translate="'.$badge_type.'">'.$badge_type.'</span>
                    <h6 class="mt-2">'.$price_display.'</h6>
                </div>
                <div class="btn-group">
                    <a href="#" class="btn btn-outline-danger add-to-cart-btn ' . ($notInStock ? 'disabled' : '') . '" 
                       data-id="'.$row['id'].'"
                       data-name="'.$row['make'].' '.$row['model'].'" 
                       data-price="'.$row['price'].'"
                       data-img="'.$image_path.'"
                       data-type="'.$car_type.'"
                       ' . ($notInStock ? 'onclick="return false;" title="Not available"' : '') . '>
                        <i class="fa fa-cart-plus"></i> <span data-translate="Add to Cart">Add to Cart</span>
                    </a>';
            
            // Test Drive Button - Check if user is logged in and car is in stock
            if (isset($_SESSION['user_id']) && !$notInStock) {
                $carsHtml .= '<a href="book-test-drive.php?car_id='.$row['id'].'" class="btn btn-success test-drive-btn">
                                <i class="fa fa-car"></i> <span data-translate="Test Drive">Test Drive</span>
                              </a>';
            } else if (!$notInStock) {
                $carsHtml .= '<a href="login.php?redirect='.urlencode('book-test-drive.php?car_id='.$row['id']).'" class="btn btn-success test-drive-btn">
                                <i class="fa fa-car"></i> <span data-translate="Test Drive">Test Drive</span>
                              </a>';
            } else {
                $carsHtml .= '<a href="#" class="btn btn-secondary test-drive-btn disabled" onclick="return false;" title="Not available">
                                <i class="fa fa-ban"></i> <span data-translate="Unavailable">Unavailable</span>
                              </a>';
            }
            
            $carsHtml .= '
                </div>
            </div>
        </div>
    </div>
</div>';
        }
    } else {
        $carsHtml = '
        <div class="col-12 text-center py-5">
            <h4 data-translate="No vehicles found">No vehicles found</h4>
            <p>Please try different search criteria or check back later.</p>
            <button onclick="clearFilters()" class="btn btn-danger">Clear Filters</button>
        </div>';
    }

    // Generate enhanced pagination HTML
    $paginationHtml = '';
    if ($totalPages > 1) {
        $paginationHtml = '<div class="row mt-5"><div class="col-12"><nav aria-label="Car pagination" class="pagination-wrapper">';
        $paginationHtml .= '<ul class="pagination justify-content-center">';
        
        // First page button
        if ($page > 1) {
            $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)" title="First page"><i class="fa fa-angle-double-left"></i></a></li>';
            $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" onclick="changePage('.($page-1).')" title="Previous page"><i class="fa fa-angle-left"></i></a></li>';
        }
        
        // Page numbers with smart pagination
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        // Adjust if we're near the beginning or end
        if ($endPage - $startPage < 4) {
            if ($startPage == 1) {
                $endPage = min($totalPages, $startPage + 4);
            } else {
                $startPage = max(1, $endPage - 4);
            }
        }
        
        // Add ellipsis if needed at the beginning
        if ($startPage > 1) {
            $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>';
            if ($startPage > 2) {
                $paginationHtml .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Page numbers
        for ($i = $startPage; $i <= $endPage; $i++) {
            $active = $i == $page ? 'active' : '';
            $paginationHtml .= '<li class="page-item '.$active.'"><a class="page-link" href="#" onclick="changePage('.$i.')">'.$i.'</a></li>';
        }
        
        // Add ellipsis if needed at the end
        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $paginationHtml .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" onclick="changePage('.$totalPages.')">'.$totalPages.'</a></li>';
        }
        
        // Next and last page buttons
        if ($page < $totalPages) {
            $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" onclick="changePage('.($page+1).')" title="Next page"><i class="fa fa-angle-right"></i></a></li>';
            $paginationHtml .= '<li class="page-item"><a class="page-link" href="#" onclick="changePage('.$totalPages.')" title="Last page"><i class="fa fa-angle-double-right"></i></a></li>';
        }
        
        $paginationHtml .= '</ul>';
        
        // Add pagination info
        $paginationHtml .= '<div class="pagination-info text-center mt-3">';
        $paginationHtml .= '<small class="text-muted">Showing page '.$page.' of '.$totalPages.' ('.$totalRows.' total vehicles)</small>';
        $paginationHtml .= '</div>';
        
        $paginationHtml .= '</nav></div></div>';
    }

    // Return JSON response
    echo json_encode([
        'success' => true,
        'cars' => $carsHtml,
        'pagination' => $paginationHtml,
        'total' => $totalRows,
        'showing' => min($limit, $totalRows),
        'page' => $page,
        'totalPages' => $totalPages
    ]);
    exit;
}

// Initial page load - get all vehicles with 25 max per page, excluding sold cars
$result = $conn->query("SELECT * FROM vehicles WHERE status != 'Sold' ORDER BY id DESC LIMIT 25");

// Get distinct brands for filter
$brandsQuery = "SELECT make, COUNT(*) as count FROM vehicles GROUP BY make ORDER BY make";
$brandsResult = $conn->query($brandsQuery);
$brands = [];
if ($brandsResult && $brandsResult->num_rows > 0) {
    while ($brand = $brandsResult->fetch_assoc()) {
        $brands[$brand['make']] = $brand['count'];
    }
}

// Get price range
$priceRangeQuery = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM vehicles";
$priceRangeResult = $conn->query($priceRangeQuery);
$priceRange = $priceRangeResult->fetch_assoc();
$minPrice = $priceRange['min_price'] ?? 0;
$maxPrice = $priceRange['max_price'] ?? 100000;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="AUTOBUBA - Premium Car Marketplace">
    <meta name="keywords" content="cars, marketplace, automotive, buy, rent">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="img/autobuba logo.png" type="image/png">
    <title data-translate="Cars">AUTOBUBA - Cars</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="loading.css">
    <link rel="stylesheet" href="css/cart.css">

    <style>
        /* Enhanced User Dropdown Styles */
        .user-dropdown {
            position: relative;
        }

        .user-dropdown .dropdown-menu {
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border-radius: 15px;
            padding: 0;
            min-width: 320px;
            overflow: hidden;
            margin-top: 10px;
        }

        .user-dropdown .dropdown-header {
            background: linear-gradient(135deg, #d90429, #c82333);
            color: white;
            padding: 1.5rem;
            border-radius: 0;
            margin: 0;
            border: none;
        }

        .user-dropdown .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-dropdown .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .user-dropdown .user-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .user-dropdown .user-details h6 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .user-dropdown .user-details small {
            opacity: 0.8;
            font-size: 0.85rem;
        }

        .user-dropdown .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            text-decoration: none;
        }

        .user-dropdown .dropdown-item:hover {
            background-color: #f8f9fa;
            border-left-color: #d90429;
            transform: translateX(5px);
            color: #d90429;
        }

        .user-dropdown .dropdown-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .user-dropdown .dropdown-item.text-danger:hover {
            background-color: #fff5f5;
            color: #dc3545;
        }

        .user-dropdown .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .user-dropdown .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #e9ecef;
        }

        /* User Button Styles */
        .user-btn {
            background: linear-gradient(135deg, #d90429, #c82333);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }

        .user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(217, 4, 41, 0.3);
            color: white;
        }

        .user-btn img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        /* Language dropdown styles */
        .language-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1000;
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0.125rem 0 0;
            font-size: 1rem;
            color: #212529;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0,0,0,.15);
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.25rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
        }

        .dropdown-item:hover, .dropdown-item:focus {
            color: #16181b;
            text-decoration: none;
            background-color: #f8f9fa;
        }

        .dropdown-item.active, .dropdown-item:active {
            color: #fff;
            text-decoration: none;
            background-color: #d90429;
        }

        .flag-icon {
            margin-right: 8px;
        }

        /* Enhanced header buttons */
        .header-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-buttons .nav-link {
            position: relative;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            color: #333;
        }

        .header-buttons .nav-link:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        }

        .toast-notification.error {
            background: #dc3545;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .test-drive-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .test-drive-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea085);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .add-to-cart-btn {
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Not in Stock Styles */
        .car__item.not-in-stock {
            position: relative;
            opacity: 0.8;
        }

        .not-in-stock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 10px 10px 0 0;
        }

        .not-in-stock-label {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            transform: rotate(-5deg);
            animation: pulse 2s infinite;
        }

        .not-in-stock-label i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .not-in-stock-label span {
            font-weight: 700;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .not-in-stock-label small {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        @keyframes pulse {
            0%, 100% {
                transform: rotate(-5deg) scale(1);
            }
            50% {
                transform: rotate(-5deg) scale(1.05);
            }
        }

        .car__item.not-in-stock .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #6c757d !important;
            border-color: #6c757d !important;
        }

        /* Enhanced Pagination Styles */
        .pagination-wrapper {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .pagination {
            margin-bottom: 0;
            gap: 0.5rem;
        }

        .pagination .page-item {
            margin: 0 2px;
        }

        .pagination .page-link {
            color: #6c757d;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 45px;
            text-align: center;
            background: white;
        }

        .pagination .page-link:hover {
            color: #d90429;
            background-color: #f8f9fa;
            border-color: #d90429;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(217, 4, 41, 0.2);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #d90429, #c82333);
            border-color: #d90429;
            color: white;
            box-shadow: 0 4px 15px rgba(217, 4, 41, 0.3);
            transform: translateY(-2px);
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background-color: #f8f9fa;
            border-color: #e9ecef;
            cursor: not-allowed;
        }

        .pagination-info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        /* Global Styles */
        :root {
            --primary-color: #dc3545;
            --secondary-color: #2d3436;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --white-color: #ffffff;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-color);
            background-color: #f5f5f5;
            height: fit-content;
        }

        a {
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }

        a:hover {
            color: var(--primary-color);
        }

        .btn {
            transition: var(--transition);
            font-weight: 500;
            border-radius: 4px;
        }

        .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        .btn-outline-danger {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-danger:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
        }

        /* Header Styles */
        .header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: var(--white-color);
            box-shadow: var(--shadow);
        }

        .navbar {
            padding: 15px 0;
        }

        .navbar-brand img {
            max-height: 50px;
        }

        .nav-link {
            position: relative;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            color: var(--dark-color) !important;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        .nav-link.underline::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1rem;
            right: 1rem;
            height: 3px;
            background-color: var(--primary-color);
        }

        /* Breadcrumb Styles */
        .breadcrumb-option {
            padding: 120px 0;
            background-size: cover;
            background-position: center;
            position: relative;
            z-index: 1;
        }

        .breadcrumb-option::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }

        .breadcrumb__text h2 {
            font-size: 48px;
            color: var(--white-color);
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .breadcrumb__links {
            display: flex;
            justify-content: center;
        }

        .breadcrumb__links a,
        .breadcrumb__links span {
            font-size: 16px;
            color: var(--white-color);
            margin-right: 26px;
            position: relative;
            display: inline-block;
        }

        .breadcrumb__links a:after {
            position: absolute;
            right: -16px;
            top: 0;
            content: "|";
            color: var(--white-color);
        }

        /* Filter Styles */
        .flex-item {
            padding: 20px;
            border-radius: 15px;
            box-shadow: 4px 4px 15px rgba(255, 0, 0, 0.5);
            background: white;
        }

        .item {
            min-height: 155px !important;
        }

        .item2 {
            padding: 20px;
            border-radius: 15px;
            box-shadow: 4px 4px 15px rgba(255, 0, 0, 0.5);
            background: white;
        }

        /* Price Range Slider */
        .price-range-wrap {
            padding: 0 5px;
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .range-slider {
            position: relative;
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            margin: 1.5rem 0;
        }

        .range-slider .price-slider {
            width: 100%;
            height: 5px;
            background: transparent;
            border-radius: 3px;
            outline: none;
            position: absolute;
            top: 0;
            left: 0;
            margin: 0;
            z-index: 2;
        }

        .range-slider .price-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .range-slider .price-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }

        .range-slider .price-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: none;
        }

        /* Brand Filter */
        .brand-filter .form-check {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: background 0.3s ease, transform 0.3s ease;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            cursor: pointer;
            gap: 0.75rem;
        }

        .brand-filter .form-check:hover {
            background: var(--light-color);
            transform: translateX(5px);
        }

        .brand-filter .form-check-input {
            margin-top: 0;
            margin-right: 0;
            flex-shrink: 0;
        }

        .brand-filter .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .brand-filter .form-check-label {
            flex-grow: 1;
            white-space: nowrap;
        }

        .brand-filter .badge {
            position: static;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }

        .brand-filter {
            max-height: 130px;
            overflow-y: auto;
        }

        /* Car Item Styles */
        .car__item {
            background: var(--white-color);
            border-radius: 10px;
            overflow: hidden;
            transition: var(--transition);
            height: 100%;
            box-shadow: var(--shadow);
        }

        .car__item:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        /* Image overlay for view more button */
        .car__item__pic {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .car__item__pic__overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .car__item:hover .car__item__pic__overlay {
            opacity: 1;
        }

        .view-more-btn {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .view-more-btn:hover {
            background-color: #c82333 !important;
            border-color: #bd2130 !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 53, 69, 0.3);
        }

        .label-date {
            position: absolute;
            left: 0;
            top: 20px;
            padding: 5px 15px;
            background: var(--primary-color);
            color: var(--white-color);
            font-weight: 600;
            border-radius: 0 4px 4px 0;
            z-index: 1;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .car__item__text {
            padding: 20px;
        }

        .car__item__text h5 {
            margin-bottom: 15px;
            font-weight: 700;
        }

        .car__item__text h5 a {
            color: var(--secondary-color);
            transition: color 0.3s ease;
        }

        .car__item__text h5 a:hover {
            color: var(--primary-color);
        }

        .car-specs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .car-spec {
            text-align: center;
            transition: transform 0.3s ease;
        }

        .car-spec:hover {
            transform: translateY(-5px);
        }

        .car-spec i {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-bottom: 5px;
        }

        .car-spec span {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .car-spec small {
            color: var(--gray-color);
            font-size: 0.75rem;
        }

        .car__item__price {
            border-top: 1px solid #e1e1e1;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .car-option {
            display: inline-block;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .car-option.sale {
            background: var(--primary-color);
            color: var(--white-color);
        }

        .car-option.rent {
            background: var(--secondary-color);
            color: var(--white-color);
        }

        .car__item__price h6 {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary-color);
            margin: 5px 0 0;
        }

        .car__item__price h6 span {
            font-size: 14px;
            color: var(--gray-color);
            font-weight: 400;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .btn-group .btn {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
        }

        /* Filter Options */
        .car__filter__option {
            background: var(--white-color);
            border-radius: 12px;
            padding: 1.5rem !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .car__filter__option:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .car__filter__option__item h6 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.75rem;
        }

        .custom-select {
            position: relative;
            width: 100%;
        }

        .custom-select select {
            width: 100%;
            height: 48px;
            padding: 0.75rem 2.5rem 0.75rem 1.25rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background-color: white;
            font-size: 0.95rem;
            color: var(--dark-color);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            transition: all 0.3s ease;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath fill='%236c757d' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 24px;
        }

        .custom-select select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
            outline: none;
        }

        /* Results info */
        .results-info {
            background: var(--white-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Cart Styles */
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 350px;
            max-width: 100%;
            height: 100%;
            background-color: white;
            z-index: 1001;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-y: auto;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e1e1;
        }

        .close-cart {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--gray-color);
            transition: color 0.3s ease;
        }

        .close-cart:hover {
            color: var(--primary-color);
        }

        .cart-body {
            margin-top: 20px;
        }

        .selected-car-item {
            display: flex;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e1e1e1;
        }

        .selected-car-img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
        }

        .selected-car-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .selected-car-info {
            flex-grow: 1;
        }

        .selected-car-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .selected-car-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 18px;
        }

        .selected-car-remove {
            background: none;
            border: none;
            color: var(--gray-color);
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .selected-car-remove:hover {
            color: var(--primary-color);
        }

        .empty-cart {
            text-align: center;
            padding: 40px 0;
        }

        .empty-cart i {
            font-size: 50px;
            color: #e1e1e1;
            margin-bottom: 15px;
        }

        .empty-cart p {
            color: var(--gray-color);
        }

        .cart-sidebar.show {
            display: block;
        }

        /* Responsive Styles */
        @media (max-width: 991px) {
            .breadcrumb-option {
                padding: 80px 0;
            }

            .breadcrumb__text h2 {
                font-size: 36px;
            }

            .btn-group {
                flex-direction: row;
            }

            .btn-group .btn {
                font-size: 0.75rem;
                padding: 0.3rem 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .breadcrumb-option {
                padding: 60px 0;
            }

            .breadcrumb__text h2 {
                font-size: 30px;
            }

            .car__item__pic {
                height: 180px;
            }

            .car__item__text h5 {
                font-size: 16px;
            }

            .car-spec i {
                font-size: 1rem;
            }

            .car-spec span {
                font-size: 0.8rem;
            }

            .car__item__price h6 {
                font-size: 16px;
            }

            .car__filter__option {
                padding: 1rem !important;
            }

            .car__filter__option__item h6 {
                font-size: 0.9rem;
            }

            .custom-select select {
                height: 42px;
                padding: 0.5rem 2rem 0.5rem 1rem;
                font-size: 0.9rem;
                background-size: 20px;
            }

            .user-dropdown .dropdown-menu {
                min-width: 280px;
                margin-right: 1rem;
            }
            
            .header-buttons {
                gap: 0.5rem;
            }

            .pagination .page-link {
                padding: 0.5rem 0.75rem;
                min-width: 40px;
                font-size: 0.9rem;
            }

            .pagination-wrapper {
                padding: 1rem;
            }
        }

        @media (max-width: 575px) {
            .breadcrumb-option {
                padding: 50px 0;
            }

            .breadcrumb__text h2 {
                font-size: 24px;
            }

            .car__filter__option {
                padding: 15px;
                margin-left: -15px;
                margin-right: -15px;
                border-radius: 0;
            }

            .car__item__pic {
                height: 160px;
            }

            .car__item__text {
                padding: 15px;
            }

            .btn-group {
                flex-direction: column;
            }

            .car__filter__option__item {
                margin-bottom: 1rem;
            }

            .car__filter__option__item:last-child {
                margin-bottom: 0;
            }

            .pagination .page-link {
                padding: 0.4rem 0.6rem;
                min-width: 35px;
                font-size: 0.8rem;
            }

            @media (max-width: 767.98px) {
                .sidebar-widget .flex-item, 
                .sidebar-widget .item, 
                .sidebar-widget .item2 {
                    width: 100% !important;
                    margin-right: 0 !important;
                    min-width: 100% !important;
                    margin-top: 25px !important;
                }
            }
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loading-container">
            <!-- Speedometer -->
            <div class="speedometer-container">
                <div class="speedometer">
                    <div class="speedometer-scale">
                        <div class="scale-mark mark-0"><span>0</span></div>
                        <div class="scale-mark mark-20"><span>20</span></div>
                        <div class="scale-mark mark-40"><span>40</span></div>
                        <div class="scale-mark mark-60"><span>60</span></div>
                        <div class="scale-mark mark-80"><span>80</span></div>
                        <div class="scale-mark mark-100"><span>100</span></div>
                    </div>
                    <div class="needle-container">
                        <div class="needle"></div>
                        <div class="needle-center"></div>
                    </div>
                    <div class="speed-display">
                        <span id="speed-value">0</span>
                        <span class="speed-unit">km/h</span>
                    </div>
                </div>
            </div>

            <!-- Loading Text -->
            <div class="loading-text">
                <span>L</span>
                <span>O</span>
                <span>A</span>
                <span>D</span>
                <span>I</span>
                <span>N</span>
                <span>G</span>
                <span>.</span>
                <span>.</span>
                <span>.</span>
            </div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div id="progress-bar" class="progress-bar"></div>
            </div>

            <!-- AUTOBUBA Text -->
            <div class="autobuba-text">AUTOBUBA</div>
        </div>
    </div>

    <!-- Header Section Begin -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand" href="./index.php">
                    <img src="img/logo zoom.png" alt="AUTOBUBA" class="img-fluid" style="max-height: 60px;">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4 underline" href="car.php"
                                data-translate="Cars">Cars</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4" href="blog.php"
                                data-translate="Blog">Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4" href="about.php"
                                data-translate="About">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-black text-decoration-none mx-4" href="work.php"
                                data-translate="Work">Work</a>
                        </li>
                    </ul>

                    <!-- Language Dropdown -->
                    <div class="language-dropdown mr-4">
                        <div class="dropdown">
                            <button class="btn btn-outline-danger dropdown-toggle" type="button" id="languageDropdown" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="flag-icon flag-icon-gb"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                                <li>
                                    <a class="dropdown-item language-option active" href="#" data-lang="en">
                                        <span class="flag-icon flag-icon-gb"></span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item language-option" href="#" data-lang="sq">
                                        <span class="flag-icon flag-icon-al"></span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="header-buttons">
                        <a href="book-test-drive.php" class="nav-link ml-3" data-bs-toggle="tooltip" title="Book Test Drive">
                            <i class="fa-solid fa-car-side"></i>
                        </a>
                        
                        <!-- Cart Button -->
                        <a id="cart" href="#" class="nav-link position-relative" data-bs-toggle="tooltip" title="Shopping Cart">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="cart-badge" id="cartBadge">0</span>
                        </a>
                        
                        <a href="report.php" class="nav-link" data-bs-toggle="tooltip" title="Report a problem">
                            <i class="fa-solid fa-flag"></i>
                        </a>

                        <!-- Enhanced User Dropdown -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            $user_name = $user_data['full_name'] ?: $_SESSION['username'];
                            $user_email = $user_data['email'];
                            $user_avatar = $user_data['profile_image'];
                            $user_role = $user_data['role'];
                            ?>
                            
                            <div class="user-dropdown">
                                <div class="dropdown">
                                    <button class="user-btn dropdown-toggle" type="button" id="userDropdown" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <?php if ($user_avatar): ?>
                                            <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar">
                                        <?php else: ?>
                                            <i class="fa-solid fa-user-circle fs-5"></i>
                                        <?php endif; ?>
                                        <span class="d-none d-md-inline"><?= htmlspecialchars(explode(' ', $user_name)[0]) ?></span>
                                    </button>
                                    
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                        <!-- User Header -->
                                        <li class="dropdown-header">
                                            <div class="user-info">
                                                <?php if ($user_avatar): ?>
                                                    <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="user-avatar">
                                                <?php else: ?>
                                                    <div class="user-avatar-placeholder">
                                                        <i class="fa-solid fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="user-details">
                                                    <h6><?= htmlspecialchars($user_name) ?></h6>
                                                    <small><?= htmlspecialchars($user_email) ?></small>
                                                    <br>
                                                    <small class="badge rounded-4 bg-light text-dark mt-2 mr-2"><?= ucfirst($user_role) ?></small>
                                                </div>
                                            </div>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- User Menu Items -->
                                        <li>
                                            <a class="dropdown-item" href="profile.php">
                                                <i class="fa-solid fa-user"></i>
                                                <span>My Profile</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="bookings.php">
                                                <i class="fa-solid fa-calendar-check"></i>
                                                <span>My Bookings</span>
                                                <?php if ($notifications['bookings'] > 0): ?>
                                                    <span class="notification-badge"><?= $notifications['bookings'] ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                        
                                        <?php if (in_array($user_role, ['admin', 'manager', 'sales'])): ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="car-dashboard/index.php">
                                                    <i class="fa-solid fa-tachometer-alt"></i>
                                                    <span>Admin Dashboard</span>
                                                </a>
                                                <a class="dropdown-item" href="view_applications.php">
                                                    <i class="fas fa-briefcase"></i>
                                                    <span>View Job Applications</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="settings.php">
                                                <i class="fa-solid fa-cog"></i>
                                                <span>Settings</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="logout.php">
                                                <i class="fa-solid fa-sign-out-alt"></i>
                                                <span>Logout</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <button onclick="window.open ('login.php')" class="user-btn">
                                <i class="fa-solid fa-user"></i>
                                <span data-translate="Log In">Log In</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Header Section End -->

    <!-- Breadcrumb Begin -->
    <div class="breadcrumb-option" style="background-image: url('img/breadcrumb-about_us.jpg');">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb__text">
                        <h2 data-translate="Car Listing">Car Listing</h2>
                        <div class="breadcrumb__links">
                            <a href="./index.php"><i class="fa fa-home"></i> <span
                                    data-translate="Home">Home</span></a>
                            <span data-translate="Cars">Cars</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Car Section Begin -->
    <section class="car spad">
        <div class="container">
            <!-- Filter Form -->
            <div class="car-sidebar">
                <h2 class="widget-title mb-2 text-center" style="margin-bottom: 70px !important" data-translate="Search Inventory">Search Inventory</h2>
                
                <div class="sidebar-widget">
                    <div class="d-flex flex-wrap justify-content-between mb-3 align-items: stretch; row gx-3 gy-2">
                        <!-- Search Filter -->
                        <div class="flex-item item col-12 col-md-4" style="margin-right: 15px; width: 30% !important;">
                            <h4 class="widget-title mb-2" data-translate="Search Inventory">Search for any car</h4>
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Search makes, models..."
                                       data-translate-placeholder="Search makes, models...">
                                <button class="btn btn-danger" type="button" onclick="applyFilters()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="flex-item col-12 col-md-4" style="min-width: 200px; width: 30%;">
                            <h4 class="widget-title mb-2" data-translate="Price Range">Price Range</h4>
                            <div class="price-range-wrap">
                                <div class="price-display">
                                    <span>$<span id="priceValue"><?= number_format($maxPrice) ?></span></span>
                                    <span>Max: $<?= number_format($maxPrice) ?></span>
                                </div>
                                <div class="range-slider">
                                    <input type="range" class="price-slider form-range" id="priceSlider"
                                           min="<?= $minPrice ?>" max="<?= $maxPrice ?>" step="1000"
                                           value="<?= $maxPrice ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div class="sidebar-widget item2 col-12 col-md-4" style="width: 35%;">
                            <h4 class="widget-title" data-translate="Car Brand">Car Brand</h4>
                            <div class="brand-filter">
                                <?php foreach($brands as $brand => $count): ?>
                                <div class="form-check">
                                    <input class="form-check-input brand-checkbox" type="checkbox" 
                                           id="brand<?= $brand ?>" value="<?= $brand ?>">
                                    <label class="form-check-label" for="brand<?= $brand ?>"><?= $brand ?></label>
                                    <span class="badge bg-danger"><?= $count ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Button -->
                <button class="btn btn-danger w-100 py-3 mt-3" onclick="applyFilters()" data-translate="Apply Filters" style="margin-bottom: 50px !important;">
                    Apply Filters
                </button>
            </div>

            <!-- Login Prompt for Non-logged Users -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <strong>Want to book a test drive?</strong> 
                            <a href="login.php" class="alert-link">Login</a> or 
                            <a href="register.php" class="alert-link">Register</a> to schedule your test drive appointment.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Results Info -->
            <div class="results-info" id="resultsInfo">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">
                            Showing <span id="showingCount">25</span> of <span id="totalCount">0</span> vehicles
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">Page <span id="currentPage">1</span> of <span id="totalPages">1</span></small>
                    </div>
                </div>
            </div>

            <!-- Car Listing Content -->
            <div class="col-lg-9 col-md-8" style="width: 100% !important;">
                <!-- Updated Filter Section -->
                <div class="car__filter__option mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-6 col-md-6">
                            <div class="car__filter__option__item">
                                <h6 class="mb-2 font-weight-bold" data-translate="Show On Page">Show On Page</h6>
                                <div class="custom-select">
                                    <select id="showOnPage" class="form-select">
                                        <option value="9" data-translate="9 Cars">9 Cars</option>
                                        <option value="15" data-translate="15 Cars">15 Cars</option>
                                        <option value="20" data-translate="20 Cars">20 Cars</option>
                                        <option value="25" data-translate="25 Cars" selected>25 Cars</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <div class="car__filter__option__item">
                                <h6 class="mb-2 font-weight-bold" data-translate="Sort By">Sort By</h6>
                                <div class="custom-select">
                                    <select id="sortBy" class="form-select">
                                        <option value="newest" data-translate="Newest First">Newest First</option>
                                        <option value="price-low" data-translate="Price: Lowest First">Price: Lowest First</option>
                                        <option value="price-high" data-translate="Price: Highest First">Price: Highest First</option>
                                        <option value="oldest" data-translate="Oldest First">Oldest First</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Car Grid -->
                <div class="row" id="carGrid">
                    <?php 
                    if ($result && $result->num_rows > 0):
                        while($row = $result->fetch_assoc()): 
                            $badge_type = ($row['status'] == 'Reserved') ? 'For Rent' : 'For Sale';
                            $price_display = ($row['status'] == 'Reserved') ? 
                                '$'.number_format($row['price'], 2)." <span>/mo</span>" : 
                                '$'.number_format($row['price'], 2);
                            
                            $car_type = ($row['status'] == 'Reserved') ? 'rent' : 'sale';
                            
                            // Check if car is not in stock (only rented cars)
                            $isRented = ($row['status'] == 'Reserved' || $row['status'] == 'Rented');
                            $notInStock = $isRented; // Only rented cars are not in stock
                            
                            // Handle image path properly
                            $image_path = $row['image_path'];
                            if (empty($image_path) || $image_path == 'img/cars/default.jpg' || !file_exists($image_path)) {
                                $image_path = 'img/cars/car-1.jpg'; // fallback image
                            }
                    ?>
<div class="col-lg-6 col-md-12 mb-4 car-item">
    <div class="car__item h-100 <?= $notInStock ? 'not-in-stock' : '' ?>">
        <div class="car__item__pic">
            <img src="<?= $image_path ?>" 
                 alt="<?= $row['make'].' '.$row['model'] ?>" 
                 class="img-fluid w-100"
                 style="height: 220px; object-fit: cover;"
                 onerror="this.src='img/cars/car-1.jpg'">
            <?php if ($notInStock): ?>
<div class="not-in-stock-overlay">
    <div class="not-in-stock-label">
        <i class="fa fa-ban"></i>
        <span>NOT IN STOCK</span>
        <small>Currently Rented</small>
    </div>
</div>
<?php endif; ?>
            <div class="car__item__pic__overlay">
                <a href="car-details.php?id=<?= $row['id'] ?>" class="btn btn-primary view-more-btn">
                    <i class="fa fa-eye"></i> View Details
                </a>
            </div>
        </div>
        <div class="label-date"><?= $row['year'] ?></div>
        <div class="car__item__text">
            <div class="car__item__text__inner">
                <h5><a href="car-details.php?id=<?= $row['id'] ?>"><?= $row['make'].' '.$row['model'] ?></a></h5>
                <div class="car-specs">
                    <div class="car-spec">
                        <i class="fa fa-road"></i>
                        <span><?= number_format($row['mileage']) ?></span>
                        <small data-translate="miles">miles</small>
                    </div>
                    <div class="car-spec">
                        <i class="fa fa-cog"></i>
                        <span data-translate="Auto">Auto</span>
                        <small data-translate="trans">trans</small>
                    </div>
                    <div class="car-spec">
                        <i class="fa fa-tachometer-alt"></i>
                        <span><?= $row['horsepower'] ?? '300' ?></span>
                        <small data-translate="hp">hp</small>
                    </div>
                </div>
            </div>
            <div class="car__item__price">
                <div>
                    <span class="car-option <?= strtolower($car_type) ?>" data-translate="<?= $badge_type ?>"><?= $badge_type ?></span>
                    <h6 class="mt-2"><?= $price_display ?></h6>
                </div>
                <div class="btn-group">
                    <a href="#" class="btn btn-outline-danger add-to-cart-btn <?= $notInStock ? 'disabled' : '' ?>" 
                       data-id="<?= $row['id'] ?>"
                       data-name="<?= $row['make'].' '.$row['model'] ?>" 
                       data-price="<?= $row['price'] ?>"
                       data-img="<?= $image_path ?>"
                       data-type="<?= $car_type ?>"
                       <?= $notInStock ? 'onclick="return false;" title="Not available"' : '' ?>>
                        <i class="fa fa-cart-plus"></i> <span data-translate="Add to Cart">Add to Cart</span>
                    </a>
                    
                    <!-- Test Drive Button - Check if user is logged in and car is in stock -->
                    <?php if (isset($_SESSION['user_id']) && !$notInStock): ?>
                        <a href="book-test-drive.php?car_id=<?= $row['id'] ?>" 
                           class="btn btn-success test-drive-btn"
                           title="Book Test Drive">
                            <i class="fa fa-car"></i> <span data-translate="Test Drive">Test Drive</span>
                        </a>
                    <?php elseif (!$notInStock): ?>
                        <a href="login.php?redirect=<?= urlencode('book-test-drive.php?car_id=' . $row['id']) ?>" 
                           class="btn btn-success test-drive-btn"
                           title="Login to Book Test Drive">
                            <i class="fa fa-car"></i> <span data-translate="Test Drive">Test Drive</span>
                        </a>
                    <?php else: ?>
                        <a href="#" class="btn btn-secondary test-drive-btn disabled" onclick="return false;" title="Not available">
                            <i class="fa fa-ban"></i> <span data-translate="Unavailable">Unavailable</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <div class="col-12 text-center py-5">
                        <h4 data-translate="No vehicles available">No vehicles available</h4>
                        <p>Please try different search criteria or check back later.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <div id="paginationContainer">
                    <!-- Pagination will be inserted here by JavaScript -->
                </div>
            </div>
        </div>
    </section>
    <!-- Car Section End -->

    <!-- Footer Section Begin -->
<footer class="footer set-bg" style="background: url(img/footer-bg.jpg);">
    <div class="container">
        <div class="row pt-5">
            <!-- Logo and Contact Section -->
            <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                <div class="footer__about">
                    <div class="footer__logo">
                        <img src="img/logo zoom.png" alt="Autobuba Logo" class="img-fluid">
                    </div>
                    <div class="footer__contact-box">
                        <p><i class="fa fa-envelope"></i> autobuba2025@gmail.com</p>
                        <p><i class="fa fa-phone"></i> +355 69 311 3209</p>
                    </div>
                    <div class="footer__social">
                        <a href="https://www.tiktok.com/@autobuba1" class="social-btn tiktok" target="_blank">
                            <i class="fa-brands fa-tiktok"></i>
                        </a>
                        <a href="https://www.instagram.com/auto_buba?igsh=MWg1b213Y3oxeG1ydg=="
                            class="social-btn instagram" target="_blank">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Navigation and Learn Sections - Side by Side on Mobile -->
            <div class="col-lg-8 col-md-6 col-12">
                <div class="row">
                    <!-- Navigation Section -->
                    <div class="col-6">
                        <div class="footer__widget">
                            <h5 data-translate="Navigation">Navigation</h5>
                            <ul class="list-unstyled footer-links">
                                <li><a href="contact.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="Contact Us">Contact Us</span>
                                </a></li>
                                <li><a href="car.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Cars">Cars</span>
                                </a></li>
                                <li><a href="blog.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Blog">Blog</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Learn Section -->
                    <div class="col-6">
                        <div class="footer__brand">
                            <h5 data-translate="Learn">Learn</h5>
                            <ul class="list-unstyled footer-links">
                                <li><a href="about.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="About Us">About Us</span>
                                </a></li>
                                <li><a href="#chooseUs" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Why Us?">Why Us?</span>
                                </a></li>
                                <li><a href="#trustedName" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="Who are we?">Who are we?</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer__bottom">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="copyright" data-translate="Copyright">
                        © Copyright 2025 QUOLYTECH. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Footer Section End -->

    <!-- Simple Phone Button -->
    <div class="phone-container">
        <div class="phone-logo" id="phoneLogo">
            <div class="phone-icon">
                <i class="fa-solid fa-phone"></i>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div class="modal" id="contactModal">
        <div class="modal-content">
            <h2 class="modal-title">Contact Information</h2>
            <p>Please call us at:</p>
            <div class="modal-number-container">
                <a href="tel:+69 69 690 6969" class="modal-number" id="phoneNumber">+355 69 311 3209</a>
                <button class="modal-copy-button" id="modalCopyButton">
                    <svg class="modal-copy-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path
                            d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z" />
                    </svg>
                </button>
                <span class="copy-tooltip" id="modalTooltip">Copied to clipboard!</span>
            </div>
            <button class="close-button" id="closeModal">Close</button>
        </div>
    </div>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartCars">
        <div class="cart-header">
            <h3 data-translate="Your Cart">Your Cart</h3>
            <button id="closeCartdisplay" class="close-cart">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="cart-body" id="selectedCars">
            <!-- Cart items will be inserted here dynamically -->
            <div class="empty-cart">
                <i class="fa fa-shopping-cart"></i>
                <p data-translate="Your cart is empty">Your cart is empty</p>
            </div>
        </div>
    </div>

    <!-- Js Plugins -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="loading.js"></script>

    <!-- Complete Cart JavaScript -->
    <script>
    let cart = JSON.parse(localStorage.getItem('autobuba_cart')) || [];

    // Clean up any corrupted cart items
    cart = cart.filter(item => {
        return item && 
               item.id && 
               item.name && 
               item.name !== 'Unknown Vehicle' && 
               item.price && 
               !isNaN(parseFloat(item.price));
    });

    // Save the cleaned cart back
    localStorage.setItem('autobuba_cart', JSON.stringify(cart));

    document.addEventListener('DOMContentLoaded', function() {
        updateCartCounter();
        
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if button is disabled
                if (this.classList.contains('disabled')) {
                    showToast('error', 'This vehicle is not available!');
                    return;
                }
                
                const vehicleId = this.dataset.id || '';
                const vehicleName = this.dataset.name || 'Unknown Vehicle';
                const vehiclePrice = this.dataset.price || '0';
                const vehicleImg = this.dataset.img || 'img/cars/default.jpg';
                const cartType = this.dataset.type || 'purchase';
                
                if (!vehicleId || !vehicleName || !vehiclePrice) {
                    showToast('error', 'Missing vehicle information!');
                    return;
                }
                
                const existingItem = cart.find(item => item.id === vehicleId);
                
                if (existingItem) {
                    showToast('error', 'Item is already in cart!');
                    return;
                }
                
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                const cartItem = {
                    id: vehicleId,
                    name: vehicleName,
                    price: parseFloat(vehiclePrice) || 0,
                    img: vehicleImg,
                    type: cartType
                };
                
                cart.push(cartItem);
                localStorage.setItem('autobuba_cart', JSON.stringify(cart));
                
                updateCartCounter();
                showToast('success', 'Item added to cart!');
                
                this.innerHTML = '<i class="fa fa-check"></i> Added';
                this.classList.remove('btn-outline-danger');
                this.classList.add('btn-success');
            });
        });
        
        const cartButton = document.getElementById('cart');
        if (cartButton) {
            cartButton.addEventListener('click', function(e) {
                e.preventDefault();
                showCartSidebar();
            });
        }
    });

    function updateCartCounter() {
        const cartBadge = document.getElementById('cartBadge');
        const cartCount = cart ? cart.length : 0;
        
        if (cartBadge) {
            if (cartCount > 0) {
                cartBadge.textContent = cartCount;
                cartBadge.style.display = 'flex';
            } else {
                cartBadge.style.display = 'none';
            }
        }
    }

    function showCartSidebar() {
        const cartSidebar = document.getElementById('cartCars');
        const cartBody = document.getElementById('selectedCars');
        
        if (!cartSidebar || !cartBody) {
            console.error('Cart elements not found');
            return;
        }
        
        cartBody.innerHTML = '';
        
        if (!cart || cart.length === 0) {
            cartBody.innerHTML = `
                <div class="empty-cart">
                    <i class="fa fa-shopping-cart"></i>
                    <p data-translate="Your cart is empty">Your cart is empty</p>
                </div>
            `;
        } else {
            const cartItemsHTML = cart.map(item => {
                const itemId = item.id || '';
                const itemName = item.name || 'Unknown Vehicle';
                const itemPrice = parseFloat(item.price) || 0;
                const itemImg = item.img || 'img/cars/default.jpg';
                
                return `
                    <div class="selected-car-item" data-id="${itemId}">
                        <div class="selected-car-img">
                            <img src="${itemImg}" alt="${itemName}" onerror="this.src='img/cars/default.jpg'">
                        </div>
                        <div class="selected-car-info">
                            <div class="selected-car-name">${itemName}</div>
                            <div class="selected-car-price">$${itemPrice.toLocaleString()}</div>
                        </div>
                        <button class="selected-car-remove" onclick="removeFromCart('${itemId}')" title="Remove from cart">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `;
            }).join('');
            
            cartBody.innerHTML = cartItemsHTML;
        }
        
        cartSidebar.style.display = 'flex';
        
        setTimeout(() => {
            cartSidebar.classList.add('show');
        }, 10);
    }

    function removeFromCart(vehicleId) {
        if (!vehicleId) {
            showToast('error', 'Invalid item ID');
            return;
        }
        
        const originalLength = cart.length;
        cart = cart.filter(item => item && item.id !== vehicleId);
        
        if (cart.length < originalLength) {
            localStorage.setItem('autobuba_cart', JSON.stringify(cart));
            updateCartCounter();
            showCartSidebar();
            
            const addButton = document.querySelector(`[data-id="${vehicleId}"]`);
            if (addButton && !addButton.classList.contains('disabled')) {
                addButton.innerHTML = '<i class="fa fa-cart-plus"></i> <span data-translate="Add to Cart">Add to Cart</span>';
                addButton.classList.remove('btn-success');
                addButton.classList.add('btn-outline-danger');
                addButton.disabled = false;
            }
            
            showToast('success', 'Item removed from cart!');
        } else {
            showToast('error', 'Item not found in cart');
        }
    }

    function closeCart() {
        const cartSidebar = document.getElementById('cartCars');
        
        if (cartSidebar) {
            cartSidebar.classList.remove('show');
        }
        
        setTimeout(() => {
            if (cartSidebar) {
                cartSidebar.style.display = 'none';
            }
        }, 300);
    }

    document.addEventListener('click', function(e) {
        if (e.target && (e.target.id === 'closeCartdisplay' || e.target.closest('#closeCartdisplay'))) {
            closeCart();
        }
    });

    function showToast(type, message) {
        const existingToasts = document.querySelectorAll('.toast-notification');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fa fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
            ${message || 'Action completed'}
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast && toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
    </script>

    <!-- Filter and AJAX Script -->
    <script>
    let currentPage = 1;
    let currentFilters = {};

    $(document).ready(function() {
        // Initialize price slider
        const priceSlider = document.getElementById("priceSlider");
        const priceValue = document.getElementById("priceValue");
        
        if (priceSlider && priceValue) {
            // Update on input
            priceSlider.addEventListener("input", function() {
                priceValue.textContent = new Intl.NumberFormat().format(this.value);
                updateSliderBackground(this);
            });
            
            // Set initial background
            updateSliderBackground(priceSlider);
        }

        // Handle show on page and sort by changes
        $('#showOnPage, #sortBy').on('change', function() {
            applyFilters();
        });

        // Load initial data
        loadInitialData();
    });

    // Update slider background to show filled portion
    function updateSliderBackground(slider) {
        const value = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
        slider.style.background = `linear-gradient(to right, #dc3545 0%, #dc3545 ${value}%, #e9ecef ${value}%, #e9ecef 100%)`;
    }

    // Apply filters function
    function applyFilters(page = 1) {
        currentPage = page;
        
        // Show loading overlay
        $('#loadingOverlay').show();
        
        // Get filter values
        const search = $('#searchInput').val();
        const maxPrice = $('#priceSlider').val();
        const selectedBrands = [];
        $('.brand-checkbox:checked').each(function() {
            selectedBrands.push($(this).val());
        });
        const show = Math.min(parseInt($('#showOnPage').val()), 25); // Ensure max 25 cars per page
        const sort = $('#sortBy').val();

        // Store current filters
        currentFilters = {
            search: search,
            max_price: maxPrice,
            brands: selectedBrands,
            show: show,
            sort: sort,
            page: page,
            ajax: '1'
        };

        // Make AJAX request
        $.ajax({
            url: 'car.php',
            type: 'POST',
            data: currentFilters,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update car grid
                    $('#carGrid').html(response.cars);
                    
                    // Update pagination
                    $('#paginationContainer').html(response.pagination);
                    
                    // Update results info
                    $('#showingCount').text(response.showing);
                    $('#totalCount').text(response.total);
                    $('#currentPage').text(response.page);
                    $('#totalPages').text(response.totalPages);
                    
                    // Re-initialize cart buttons for new content
                    initializeCartButtons();
                    
                    // Scroll to top of results
                    $('html, body').animate({
                        scrollTop: $('#resultsInfo').offset().top - 100
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('error', 'An error occurred while filtering. Please try again.');
            },
            complete: function() {
                // Hide loading overlay
                $('#loadingOverlay').hide();
            }
        });
    }

    // Re-initialize cart buttons after AJAX load
    function initializeCartButtons() {
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            // Remove existing event listeners
            button.replaceWith(button.cloneNode(true));
        });
        
        // Re-add event listeners
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check if button is disabled
                if (this.classList.contains('disabled')) {
                    showToast('error', 'This vehicle is not available!');
                    return;
                }
                
                const vehicleId = this.dataset.id || '';
                const vehicleName = this.dataset.name || 'Unknown Vehicle';
                const vehiclePrice = this.dataset.price || '0';
                const vehicleImg = this.dataset.img || 'img/cars/default.jpg';
                const cartType = this.dataset.type || 'purchase';
                
                if (!vehicleId || !vehicleName || !vehiclePrice) {
                    showToast('error', 'Missing vehicle information!');
                    return;
                }
                
                const existingItem = cart.find(item => item.id === vehicleId);
                
                if (existingItem) {
                    showToast('error', 'Item is already in cart!');
                    return;
                }
                
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                const cartItem = {
                    id: vehicleId,
                    name: vehicleName,
                    price: parseFloat(vehiclePrice) || 0,
                    img: vehicleImg,
                    type: cartType
                };
                
                cart.push(cartItem);
                localStorage.setItem('autobuba_cart', JSON.stringify(cart));
                
                updateCartCounter();
                showToast('success', 'Item added to cart!');
                
                this.innerHTML = '<i class="fa fa-check"></i> Added';
                this.classList.remove('btn-outline-danger');
                this.classList.add('btn-success');
            });
        });
    }

    // Change page function
    function changePage(page) {
        applyFilters(page);
    }

    // Clear filters function
    function clearFilters() {
        $('#searchInput').val('');
        $('#priceSlider').val($('#priceSlider').attr('max'));
        $('#priceValue').text(new Intl.NumberFormat().format($('#priceSlider').attr('max')));
        $('.brand-checkbox').prop('checked', false);
        $('#showOnPage').val('25');
        $('#sortBy').val('newest');
        updateSliderBackground(document.getElementById('priceSlider'));
        applyFilters(1);
    }

    // Load initial data
    function loadInitialData() {
        // Get total count for initial display
        $.ajax({
            url: 'car.php',
            type: 'POST',
            data: { ajax: '1', show: 25, sort: 'newest', page: 1 },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#totalCount').text(response.total);
                    $('#showingCount').text(response.showing);
                    $('#totalPages').text(response.totalPages);
                }
            }
        });
    }
    </script>

    <!-- Phone button functionality -->
    <script>
        const phoneLogo = document.getElementById('phoneLogo');
        const contactModal = document.getElementById('contactModal');
        const closeModal = document.getElementById('closeModal');
        const modalCopyButton = document.getElementById('modalCopyButton');
        const modalTooltip = document.getElementById('modalTooltip');

        if (phoneLogo && contactModal && closeModal) {
            // Open modal
            phoneLogo.addEventListener('click', function () {
                contactModal.style.display = 'flex';
            });

            // Close modal
            closeModal.addEventListener('click', function () {
                contactModal.style.display = 'none';
            });

            // Close modal when clicking outside
            window.addEventListener('click', function (e) {
                if (e.target === contactModal) {
                    contactModal.style.display = 'none';
                }
            }); 

            // Copy phone number
            if (modalCopyButton && modalTooltip) {
                modalCopyButton.addEventListener('click', function () {
                    const phoneNumber = document.getElementById('phoneNumber').textContent;
                    navigator.clipboard.writeText(phoneNumber);

                    modalTooltip.style.opacity = '1';
                    setTimeout(function () {
                        modalTooltip.style.opacity = '0';
                    }, 2000);
                });
            }
        }
    </script>

    <!-- Loading screen functionality -->
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                const loadingScreen = document.getElementById('loading-screen');
                if (loadingScreen) {
                    loadingScreen.style.opacity = '0';
                    setTimeout(function () {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }
            }, 1500);
        });
    </script>

    <!-- Language Translation Script -->
    <script>
    // Translation data
    const translations = {
      en: {
        // Header
        Cars: "Cars",
        Blog: "Blog",
        "Car Details": "Car Details",
        About: "About",
        Work: "Work",
        Cart: "Cart",
        Report: "Report",
        "Log In": "Log In",

        // Car Listing
        "Car Listing": "Car Listing",
        Home: "Home",
        "Search Inventory": "Search Inventory",
        "Search for any car": "Search for any car",
        "Price Range": "Price Range",
        "Car Brand": "Car Brand",
        "Apply Filters": "Apply Filters",
        "Show On Page": "Show On Page",
        "9 Cars": "9 Cars",
        "15 Cars": "15 Cars",
        "20 Cars": "20 Cars",
        "25 Cars": "25 Cars",
        "Sort By": "Sort By",
        "Newest First": "Newest First",
        "Price: Lowest First": "Price: Lowest First",
        "Price: Highest First": "Price: Highest First",
        "Oldest First": "Oldest First",
        "For Rent": "For Rent",
        "For Sale": "For Sale",
        "Add to Cart": "Add to Cart",
        "Test Drive": "Test Drive",
        "View Details": "View Details",
        Unavailable: "Unavailable",
        miles: "miles",
        Auto: "Auto",
        trans: "trans",
        hp: "hp",
        "No vehicles found": "No vehicles found",
        "No vehicles available": "No vehicles available",

        // Footer
        Navigation: "Navigation",
        "Contact Us": "Contact Us",
        Learn: "Learn",
        "Why Us?": "Why Us?",
        "Who are we?": "Who are we?",
        Hatchback: "Hatchback",
        Sedan: "Sedan",
        SUV: "SUV",
        "Copyright 2025 AUTOBUBA. All Rights Reserved.": "© Copyright 2025 QUOLYTECH. All Rights Reserved.",

        // Cart
        "Your Cart": "Your Cart",
        "Your cart is empty": "Your cart is empty",
      },
      sq: {
        // Header
        Cars: "Makinat",
        Blog: "Blog",
        "Car Details": "Detajet e Makinës",
        About: "Rreth Nesh",
        Work: "Puna",
        Cart: "Shporta",
        Report: "Raporto",
        "Log In": "Hyr",

        // Car Listing
        "Car Listing": "Lista e Makinave",
        Home: "Kryefaqja",
        "Search Inventory": "Kërko në Inventar",
        "Search for any car": "Kërko për çdo makinë",
        "Price Range": "Çmimet",
        "Car Brand": "Marka e Makinës",
        "Apply Filters": "Apliko Filtrat",
        "Show On Page": "Shfaq në Faqe",
        "9 Cars": "9 Makina",
        "15 Cars": "15 Makina",
        "20 Cars": "20 Makina",
        "25 Cars": "25 Makina",
        "Sort By": "Rendit Sipas",
        "Newest First": "Më të Rejat Së Pari",
        "Price: Lowest First": "Çmimi: Më të Lira Së Pari",
        "Price: Highest First": "Çmimi: Më të Shtrenjta Së Pari",
        "Oldest First": "Më të Vjetrat Së Pari",
        "For Rent": "Për Qira",
        "For Sale": "Për Shitje",
        "Add to Cart": "Shto në Shportë",
        "Test Drive": "Test Drive",
        "View Details": "Shiko Detajet",
        Unavailable: "Jo i Disponueshëm",
        miles: "km",
        Auto: "Automatik",
        trans: "trans",
        hp: "hp",
        "No vehicles found": "Nuk u gjetën makina",
        "No vehicles available": "Nuk ka makina të disponueshme",

        // Footer
        Navigation: "Navigimi",
        "Contact Us": "Na Kontaktoni",
        Learn: "Mësoni",
        "Why Us?": "Pse Ne?",
        "Who are we?": "Kush jemi ne?",
        Hatchback: "Hatchback",
        Sedan: "Sedan",
        SUV: "SUV",
        "Copyright 2025 AUTOBUBA. All Rights Reserved.": "© E drejta e autorit 2025 QUOLYTECH. Të gjitha të drejtat e rezervuara.",

        // Cart
        "Your Cart": "Shporta Juaj",
        "Your cart is empty": "Shporta juaj është bosh",
      },
    }

    // Language Manager Class
    class LanguageManager {
      constructor() {
        this.currentLanguage = this.getStoredLanguage() || "en"
        this.init()
      }

      init() {
        this.setupEventListeners()
        this.updateLanguageDisplay()
        this.translatePage()
      }

      getStoredLanguage() {
        return localStorage.getItem("autobuba_language")
      }

      setStoredLanguage(lang) {
        localStorage.setItem("autobuba_language", lang)
      }

      setupEventListeners() {
        // Language dropdown options
        const languageOptions = document.querySelectorAll(".language-option")
        languageOptions.forEach((option) => {
          option.addEventListener("click", (e) => {
            e.preventDefault()
            const selectedLang = option.getAttribute("data-lang")
            this.changeLanguage(selectedLang)
          })
        })

        // Also handle direct dropdown button clicks
        const dropdownButton = document.getElementById("languageDropdown")
        if (dropdownButton) {
          dropdownButton.addEventListener("click", () => {
            this.updateLanguageDisplay()
          })
        }
      }

      changeLanguage(lang) {
        if (translations[lang]) {
          this.currentLanguage = lang
          this.setStoredLanguage(lang)
          this.updateLanguageDisplay()
          this.translatePage()
          this.showLanguageChangeNotification(lang)
        }
      }

      updateLanguageDisplay() {
        // Update dropdown button
        const dropdownButton = document.getElementById("languageDropdown")
        const languageOptions = document.querySelectorAll(".language-option")

        if (dropdownButton) {
          // Update flag in dropdown button - only show flag, no text
          const flagClass = this.currentLanguage === "en" ? "flag-icon-gb" : "flag-icon-al"
          dropdownButton.innerHTML = `<span class="flag-icon ${flagClass}"></span>`
        }

        // Update active state in dropdown options
        languageOptions.forEach((option) => {
          const optionLang = option.getAttribute("data-lang")
          if (optionLang === this.currentLanguage) {
            option.classList.add("active")
          } else {
            option.classList.remove("active")
          }
        })
      }

      translatePage() {
        const elementsToTranslate = document.querySelectorAll("[data-translate]")
        const currentTranslations = translations[this.currentLanguage]

        elementsToTranslate.forEach((element) => {
          const key = element.getAttribute("data-translate")
          if (currentTranslations[key]) {
            // Handle different element types
            if (element.tagName === "INPUT" && (element.type === "submit" || element.type === "button")) {
              element.value = currentTranslations[key]
            } else if (element.tagName === "INPUT" && element.placeholder) {
              element.placeholder = currentTranslations[key]
            } else if (element.tagName === "OPTION") {
              element.textContent = currentTranslations[key]
            } else {
              // For regular elements, preserve HTML structure but translate text
              const innerHTML = element.innerHTML
              const hasHTML = innerHTML !== element.textContent

              if (hasHTML) {
                // If element contains HTML, only replace the text nodes
                this.translateElementWithHTML(element, currentTranslations[key])
              } else {
                // Simple text replacement
                element.textContent = currentTranslations[key]
              }
            }
          }
        })

        // Update page title
        if (currentTranslations["Cars"]) {
          document.title = currentTranslations["Cars"] + " - AUTOBUBA"
        }

        // Update HTML lang attribute
        document.documentElement.lang = this.currentLanguage === "en" ? "en" : "sq"
      }

      translateElementWithHTML(element, translation) {
        // For elements that contain HTML (like spans, icons), we need to be more careful
        const childNodes = Array.from(element.childNodes)
        const textNodes = childNodes.filter((node) => node.nodeType === Node.TEXT_NODE)

        if (textNodes.length === 1 && textNodes[0].textContent.trim()) {
          // If there's only one text node, replace it
          textNodes[0].textContent = translation
        } else {
          // For more complex cases, replace the entire content but preserve structure
          const tempDiv = document.createElement("div")
          tempDiv.innerHTML = element.innerHTML

          // Try to find and replace text content
          const walker = document.createTreeWalker(tempDiv, NodeFilter.SHOW_TEXT, null, false)

          let textNode
          while ((textNode = walker.nextNode())) {
            if (textNode.textContent.trim()) {
              textNode.textContent = translation
              break // Replace only the first significant text node
            }
          }

          element.innerHTML = tempDiv.innerHTML
        }
      }

      showLanguageChangeNotification(lang) {
        const langName = lang === "en" ? "English" : "Albanian"
        const message =
          lang === "en"
            ? `Language changed to ${langName}`
            : `Gjuha u ndryshua në ${langName === "English" ? "Anglisht" : "Shqip"}`

        // Remove existing notifications
        const existingNotifications = document.querySelectorAll(".language-notification")
        existingNotifications.forEach((notification) => notification.remove())

        // Create notification
        const notification = document.createElement("div")
        notification.className = "language-notification"
        notification.innerHTML = `
                <i class="fa fa-language me-2"></i>
                ${message}
            `

        // Style the notification
        Object.assign(notification.style, {
          position: "fixed",
          top: "20px",
          right: "20px",
          background: "#28a745",
          color: "white",
          padding: "12px 20px",
          borderRadius: "8px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
          zIndex: "9999",
          fontSize: "14px",
          fontWeight: "500",
          animation: "slideInRight 0.3s ease",
          maxWidth: "300px",
        })

        document.body.appendChild(notification)

        // Remove notification after 3 seconds
        setTimeout(() => {
          if (notification && notification.parentNode) {
            notification.style.animation = "slideOutRight 0.3s ease"
            setTimeout(() => {
              if (notification && notification.parentNode) {
                notification.remove()
              }
            }, 300)
          }
        }, 3000)
      }

      // Method to get current language
      getCurrentLanguage() {
        return this.currentLanguage
      }

      // Method to get translation for a specific key
      getTranslation(key) {
        return translations[this.currentLanguage][key] || key
      }
    }

    // Initialize language manager when DOM is loaded
    document.addEventListener("DOMContentLoaded", () => {
      window.languageManager = new LanguageManager()
    })

    // Add CSS animations for notifications
    const style = document.createElement("style")
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .language-notification {
            transition: all 0.3s ease;
        }
        
        /* Enhanced Language Dropdown Styles */
        .language-dropdown {
            position: relative;
        }
        
        .language-dropdown .dropdown-toggle {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px 12px;
            min-width: 50px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .language-dropdown .dropdown-toggle:hover {
            border-color: #d90429;
            box-shadow: 0 4px 12px rgba(217, 4, 41, 0.2);
            transform: translateY(-1px);
        }
        
        .language-dropdown .dropdown-toggle:focus {
            border-color: #d90429;
            box-shadow: 0 0 0 3px rgba(217, 4, 41, 0.1);
        }
        
        .language-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .language-dropdown .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            padding: 8px;
            min-width: 120px;
            margin-top: 8px;
            background: white;
            backdrop-filter: blur(10px);
        }
        
        .language-dropdown .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            margin: 2px 0;
            transition: all 0.2s ease;
            border: none;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }
        
        .language-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transform: translateX(3px);
            color: #d90429;
        }
        
        .language-dropdown .dropdown-item.active {
            background: linear-gradient(135deg, #d90429, #c82333);
            color: white;
            box-shadow: 0 2px 8px rgba(217, 4, 41, 0.3);
        }
        
        .language-dropdown .dropdown-item.active:hover {
            background: linear-gradient(135deg, #c82333, #b21e2f);
            transform: translateX(0);
        }
        
        .language-dropdown .flag-icon {
            font-size: 1.2em;
            margin: 0;
            width: 24px;
            height: 18px;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        /* Animation for dropdown appearance */
        .language-dropdown .dropdown-menu.show {
            animation: dropdownSlideIn 0.2s ease;
        }
        
        @keyframes dropdownSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .language-dropdown .dropdown-menu {
                min-width: 100px;
                margin-right: 10px;
            }
            
            .language-dropdown .dropdown-toggle {
                min-width: 45px;
                height: 38px;
                padding: 6px 10px;
            }
            
            .language-dropdown .flag-icon {
                width: 20px;
                height: 15px;
            }
        }
    `
    document.head.appendChild(style)
    </script>
</body>
</html>
