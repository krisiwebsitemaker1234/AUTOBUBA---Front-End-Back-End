<?php
require_once 'config.php';

class DB {
    private static $instance = null;
    private $pdo;  // Remove the invalid 'global $pdo' and make this private

    private function __construct() {
        try {
            $this->pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DB();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Initialize $pdo as a global variable for backward compatibility
$pdo = DB::getInstance()->getConnection();
$db = $pdo; // Now both variables exist

/**
 * Helper function to execute queries (matches usage in functions.php)
 */
function query($sql, $params = []) {
    global $pdo;  // Uses the global $pdo initialized above
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch a single row (matches usage in functions.php)
 */
function getSingle($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Fetch all rows (matches usage in functions.php)
 */
function getAll($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}
?>