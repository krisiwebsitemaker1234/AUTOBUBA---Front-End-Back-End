<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Sanitize output data with improved handling for names and special characters
 */
function sanitize($data, $preserveSpaces = true) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    // Convert to string if not already
    $data = (string)$data;
    
    // Special handling for empty values
    if (trim($data) === '') {
        return '';
    }
    
    // Preserve legitimate spaces in names if requested
    $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5;
    $encoding = 'UTF-8';
    
    // Double encode to prevent XSS
    $sanitized = htmlspecialchars($data, $flags, $encoding, true);
    
    // Optionally normalize spaces (replace multiple spaces with single)
    if ($preserveSpaces) {
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
    }
    
    return $sanitized;
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    global $pdo;
    
    // Initialize default structure
    $stats = [
        'vehicles' => ['total' => 0, 'available' => 0, 'sold' => 0, 'reserved' => 0],
        'sales' => ['total_sales' => 0, 'total_revenue' => 0, 'monthly_revenue' => 0, 'yearly_revenue' => 0],
        'rentals' => ['total_rentals' => 0, 'active' => 0, 'completed' => 0, 'due_today' => 0],
        'customers' => ['total' => 0]
    ];

    try {
        // Vehicle Statistics
        $vehicleStats = getSingle("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'In Stock' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'Sold' THEN 1 ELSE 0 END) as sold,
                SUM(CASE WHEN status = 'Reserved' THEN 1 ELSE 0 END) as reserved
            FROM vehicles
        ");
        
        $stats['vehicles'] = $vehicleStats ?: $stats['vehicles'];
        
        // Sales Statistics
        $salesStats = getSingle("
            SELECT 
                COUNT(*) as total_sales,
                SUM(sale_price) as total_revenue,
                SUM(CASE WHEN MONTH(sale_date) = MONTH(CURRENT_DATE()) THEN sale_price ELSE 0 END) as monthly_revenue,
                SUM(CASE WHEN YEAR(sale_date) = YEAR(CURRENT_DATE()) THEN sale_price ELSE 0 END) as yearly_revenue
            FROM sales
        ");
        
        $stats['sales'] = $salesStats ?: $stats['sales'];
        
        // Rental Statistics
        $rentalStats = getSingle("
            SELECT 
                COUNT(*) as total_rentals,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN end_date = CURRENT_DATE() THEN 1 ELSE 0 END) as due_today
            FROM rentals
        ");
        
        $stats['rentals'] = $rentalStats ?: $stats['rentals'];
        
        // Customer Statistics
        $stats['customers'] = getSingle("SELECT COUNT(*) as total FROM customers") ?: $stats['customers'];

    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        // Return default structure with error flag
        $stats['error'] = true;
    }
    
    return $stats;
}

/**
 * Get recent transactions
 */
function getRecentTransactions($limit = 5) {
    try {
        return [
            'sales' => getAll("
                SELECT s.*, v.make, v.model, v.year, c.first_name, c.last_name, c.phone 
                FROM sales s
                JOIN vehicles v ON s.vehicle_id = v.id
                JOIN customers c ON s.customer_id = c.id
                ORDER BY s.sale_date DESC LIMIT ?", [$limit]),
            'rentals' => getAll("
                SELECT r.*, v.make, v.model, v.year, c.first_name, c.last_name, c.phone 
                FROM rentals r
                JOIN vehicles v ON r.vehicle_id = v.id
                JOIN customers c ON r.customer_id = c.id
                ORDER BY r.start_date DESC LIMIT ?", [$limit])
        ];
    } catch (PDOException $e) {
        error_log("Recent transactions error: " . $e->getMessage());
        return ['sales' => [], 'rentals' => []];
    }
}

/**
 * Get top performing models
 */
function getTopModels($limit = 5) {
    return getAll("
        SELECT 
            v.make, 
            v.model, 
            COUNT(s.id) as sales_count,
            ROUND(COUNT(s.id) * 100.0 / (SELECT COUNT(*) FROM sales), 1) as percentage
        FROM vehicles v
        LEFT JOIN sales s ON v.id = s.vehicle_id
        GROUP BY v.make, v.model
        ORDER BY sales_count DESC
        LIMIT ?", [$limit]);
}

/**
 * Get slow moving inventory
 */
function getSlowMovingInventory($limit = 5) {
    return getAll("
        SELECT 
            v.*,
            DATEDIFF(CURRENT_DATE, v.added_at) as days_in_stock
        FROM vehicles v
        WHERE v.status = 'In Stock'
        ORDER BY days_in_stock DESC
        LIMIT ?", [$limit]);
}

/**
 * Get due rentals
 */
function getDueRentals() {
    return getAll("
        SELECT 
            r.*,
            v.make, v.model, v.year,
            c.first_name, c.last_name, c.phone, c.email
        FROM rentals r
        JOIN vehicles v ON r.vehicle_id = v.id
        JOIN customers c ON r.customer_id = c.id
        WHERE r.end_date = CURRENT_DATE() AND r.status = 'Active'
    ");
}

/**
 * Get sales chart data for the dashboard
 * @param PDO|null $db Database connection (optional)
 * @param int $months Number of months to retrieve (default 12)
 * @return array
 */
function getSalesChartData(PDO $db = null, int $months = 12): array {
    try {
        // Use the provided DB connection or get one from your DB class
        $pdo = $db ?: DB::getInstance()->getConnection();
        
        $data = [];
        $currentDate = new DateTime();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = clone $currentDate;
            $date->modify("-$i months");
            
            $month = $date->format('m');
            $year = $date->format('Y');
            $monthName = $date->format('M Y');

            $query = "SELECT 
                        COUNT(*) as sales_count, 
                        COALESCE(SUM(sale_price), 0) as revenue
                      FROM sales 
                      WHERE MONTH(sale_date) = ? AND YEAR(sale_date) = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$month, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $data[] = [
                'month' => $monthName,
                'sales' => (int)($result['sales_count'] ?? 0),
                'revenue' => (float)($result['revenue'] ?? 0)
            ];
        }
        
        return $data;
    } catch (Exception $e) {
        error_log("Error in getSalesChartData: " . $e->getMessage());
        return [];
    }
}

/**
 * Format phone number to (XXX) XXX-XXXX format
 */
function formatPhoneNumber($phone) {
    // Remove all non-digit characters
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if we have enough digits
    if (strlen($cleaned) === 10) {
        return '('.substr($cleaned, 0, 3).') '.substr($cleaned, 3, 3).'-'.substr($cleaned, 6);
    }
    
    // Return original if formatting doesn't apply
    return $phone;
}

/**
 * Create a new calendar event
 */
function createEvent($data) {
    try {
        $pdo = DB::getInstance()->getConnection();
        
        $sql = "INSERT INTO calendar_events 
                (title, event_type, start_time, end_time, vehicle_id, customer_id, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['event_type'],
            $data['start_time'],
            $data['end_time'],
            $data['vehicle_id'] ?? null, // Optional for follow-ups
            $data['customer_id'],
            $data['status'] ?? 'pending',
            $data['notes'] ?? null
        ];
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error creating event: " . $e->getMessage());
        throw new Exception("Failed to create event: " . $e->getMessage());
    }
}

/**
 * Get calendar events formatted for FullCalendar
 * This is the function that works in index.php, so we'll use it directly
 */
function getCalendarEvents($start, $end) {
    try {
        $pdo = DB::getInstance()->getConnection();
        if (!$pdo) throw new Exception('No database connection');

        // Use a more inclusive query to catch all relevant events
        $sql = "SELECT * FROM calendar_events 
                WHERE (
                    (start_time BETWEEN ? AND ?) OR
                    (end_time BETWEEN ? AND ?) OR
                    (start_time <= ? AND end_time >= ?)
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end, $start, $end, $start, $end]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($stmt->rowCount() === 0) {
            error_log("No events found between $start and $end");
        } else {
            error_log("Found " . $stmt->rowCount() . " events between $start and $end");
        }

        $formattedEvents = [];
        foreach ($events as $event) {
            // Format dates in ISO8601 format for FullCalendar
            $formattedEvents[] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'start' => date('c', strtotime($event['start_time'])),
                'end' => date('c', strtotime($event['end_time'])),
                'extendedProps' => [
                    'type' => $event['event_type'],
                    'notes' => $event['notes']
                ]
            ];
        }

        return $formattedEvents;
    } catch (PDOException $e) {
        error_log("Database Error in getCalendarEvents: " . $e->getMessage());
        return [];
    }
}

/**
 * Update existing event
 */
function updateEvent($data) {
    try {
        $pdo = DB::getInstance()->getConnection();
        
        $sql = "UPDATE calendar_events SET
                title = ?,
                event_type = ?,
                start_time = ?,
                end_time = ?,
                notes = ?
                WHERE id = ?";
        
        $params = [
            $data['title'],
            $data['event_type'],
            $data['start_time'],
            $data['end_time'],
            $data['notes'] ?? null,
            $data['id']
        ];
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating event: " . $e->getMessage());
        throw new Exception("Failed to update event: " . $e->getMessage());
    }
}

/**
 * Delete event
 */
function deleteEvent($id) {
    try {
        $pdo = DB::getInstance()->getConnection();
        
        $sql = "DELETE FROM calendar_events WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error deleting event: " . $e->getMessage());
        throw new Exception("Failed to delete event: " . $e->getMessage());
    }
}

/**
 * Event color coding
 */
function getEventColor($type) {
    $colors = [
        'test_drive' => '#e11d48',  // Pink
        'service' => '#3b82f6',     // Blue
        'follow_up' => '#8b5cf6'    // Purple
    ];
    return $colors[$type] ?? '#6b7280'; 
}

/**
 * Fetches notification settings for a user from the database.
 */
function getUserNotificationSettings($userId) {
    global $pdo; // Ensure $pdo is defined in db.php
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?? [
            'email_all' => true,
            'app_all' => true,
            'push_enabled' => false
        ]; // Defaults if no settings exist
    } catch (PDOException $e) {
        error_log("Notification settings error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get new vehicle arrivals
 */
function getNewArrivals($limit = 5) {
    try {
        $pdo = DB::getInstance()->getConnection();
        
        $sql = "SELECT 
                    id, make, model, year, price, mileage, 
                    color, status, vin,
                    DATEDIFF(NOW(), added_at) AS days_ago
                FROM vehicles
                WHERE added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status = 'In Stock'
                ORDER BY added_at DESC
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("DB Error in getNewArrivals: " . $e->getMessage());
        return [];
    }
}

// ======================== SECURITY FUNCTIONS ========================
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}
?>