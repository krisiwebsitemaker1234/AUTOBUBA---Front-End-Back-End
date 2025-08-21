<?php
include_once 'config.php';

// Initialize variables
$whereClauses = [];
$type = isset($_GET['type']) ? $_GET['type'] : 'buy';

// Other filters
if(!empty($_GET['year'])) {
    $year = $conn->real_escape_string($_GET['year']);
    $whereClauses[] = "v.year = '$year'";
}

if(!empty($_GET['brand'])) {
    $brand = $conn->real_escape_string($_GET['brand']);
    $whereClauses[] = "v.make = '$brand'";
}

if(!empty($_GET['model'])) {
    $model = $conn->real_escape_string($_GET['model']);
    $whereClauses[] = "v.model = '$model'";
}

if(!empty($_GET['mileage'])) {
    $mileage = $conn->real_escape_string($_GET['mileage']);
    $whereClauses[] = "v.mileage <= '$mileage'";
}

if(!empty($_GET['min_price']) || !empty($_GET['max_price'])) {
    $minPrice = !empty($_GET['min_price']) ? $conn->real_escape_string($_GET['min_price']) : 0;
    $maxPrice = !empty($_GET['max_price']) ? $conn->real_escape_string($_GET['max_price']) : 1000000;
    $whereClauses[] = "v.price BETWEEN '$minPrice' AND '$maxPrice'";
}

// UPDATED: Build query excluding vehicles in active rentals/sales
$where = count($whereClauses) > 0 ? 'AND ' . implode(' AND ', $whereClauses) : '';

$sql = "
    SELECT v.* FROM vehicles v 
    WHERE v.status IN ('In Stock', 'Reserved') 
    AND v.id NOT IN (
        SELECT DISTINCT vehicle_id FROM rentals 
        WHERE status IN ('active', 'pending', 'confirmed') 
        AND vehicle_id IS NOT NULL
    )
    AND v.id NOT IN (
        SELECT DISTINCT vehicle_id FROM sales 
        WHERE status IN ('active', 'pending', 'confirmed', 'processing') 
        AND vehicle_id IS NOT NULL
    )
    $where
    ORDER BY v.added_at DESC 
    LIMIT 12
";

$result = $conn->query($sql);

// Display results
if($result && $result->num_rows > 0) {
    while($car = $result->fetch_assoc()) {
        // Handle image path
        $image_path = $car['image_path'] ?? 'img/cars/car-1.jpg';
        $car_images = [
            'img/cars/car-1.jpg',
            'img/cars/car-2.jpg',
            'img/cars/car-3.jpg',
            'img/cars/car-4.jpg'
        ];

        // Fix image path if needed
        $image_path = str_replace('images/', 'img/cars/', $image_path);
        
        if (!file_exists($image_path)) {
            $image_path = $car_images[array_rand($car_images)];
        }

        // Handle null values with proper defaults
        $make = $car['make'] ?? 'Unknown';
        $model = $car['model'] ?? 'Model';
        $year = $car['year'] ?? 'N/A';
        $mileage = $car['mileage'] ?? 0;
        $horsepower = $car['horsepower'] ?? 'N/A';
        $price = $car['price'] ?? 0;
        $car_id = $car['id'] ?? 0;
        ?>
        <div class="col">
            <div class="car__item">
                <div class="car__item__pic position-relative">
                    <img src="<?= htmlspecialchars($image_path) ?>" 
                         alt="<?= htmlspecialchars($make . ' ' . $model) ?>" 
                         class="img-fluid w-100"
                         style="height: 180px; object-fit: cover;">
                    <span class="car-option-badge">Available</span>
                </div>
                <div class="car__item__text">
                    <div class="car__item__text__inner">
                        <span class="badge bg-light text-dark px-2 py-1 rounded-2 mb-2">
                            <?= htmlspecialchars($year) ?>
                        </span>
                        <h5>
                            <a href="car-details.php?id=<?= $car_id ?>">
                                <?= htmlspecialchars($make . ' ' . $model) ?>
                            </a>
                        </h5>
                        <div class="car-specs">
                            <div class="car-spec">
                                <span><?= number_format($mileage) ?></span> mi
                            </div>
                            <div class="car-spec">Auto</div>
                            <div class="car-spec">
                                <span><?= htmlspecialchars($horsepower) ?></span> hp
                            </div>
                        </div>
                    </div>
                    <div class="car__item__price">
                        <h6>$<?= number_format($price, 2) ?></h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="car-actions d-flex gap-2">
                                <!-- Add To Cart -->
                                <button class="btn btn-sm btn-outline-danger add-to-cart-btn" 
                                        title="Add to Cart"
                                        data-id="<?= $car_id ?>"
                                        data-name="<?= htmlspecialchars($make . ' ' . $model) ?>"
                                        data-price="<?= $price ?>"
                                        data-img="<?= htmlspecialchars($image_path) ?>"
                                        data-type="purchase">
                                    <i class="fa fa-cart-plus"></i>
                                </button>
                                
                                <!-- Test Drive Button -->
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="book-test-drive.php?car_id=<?= $car_id ?>" 
                                       class="btn btn-sm btn-success test-drive-btn"
                                       title="Book Test Drive">
                                        <i class="fa fa-car"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?= urlencode('book-test-drive.php?car_id=' . $car_id) ?>" 
                                       class="btn btn-sm btn-success test-drive-btn"
                                       title="Login to Book Test Drive">
                                        <i class="fa fa-car"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- View More Button -->
                            <a href="car-details.php?id=<?= $car_id ?>" 
                               class="btn btn-sm btn-light view-more">
                                View More
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    echo '
<div class="pricing-notice bg-light border rounded text-center">
    <div class="pricing-notice-icon mb-2 w-15">
        <i class="fa fa-info-circle"></i>
    </div>
    <h5 class="">Purchase Pricing Information</h5>
    <p class="">
        The prices displayed above are for <span class="highlight">vehicle purchase only</span>. 
        If you are interested in rental options and pricing, please check your 
        <span class="highlight">cart in the navigation bar</span>.
    </p>
</div>';
} else {
    ?>
    <div class="col-12 text-center py-5">
        <div class="alert alert-info">
            <i class="fa fa-search fa-3x mb-3"></i>
            <h4 data-translate="No vehicles found">No vehicles found</h4>
            <p data-translate="Try different search criteria">Try different search criteria</p>
        </div>
    </div>
    <?php
}
?>
