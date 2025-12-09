<?php
// Start output buffering
while (ob_get_level()) ob_end_clean();
ob_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../error_log.txt');

// Set default timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Start session with proper configuration
session_name('silco_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 hours
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Function to clean all output buffers
function cleanOutputBuffers() {
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
}

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = null, $statusCode = 200) {
    cleanOutputBuffers();
    
    // Set headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    http_response_code($statusCode);
    
    // Prepare response
    $response = ['success' => $success];
    if ($message !== '') $response['message'] = $message;
    if ($data !== null) $response = array_merge($response, $data);
    
    // Encode and send response
    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{"success":false,"message":"Error encoding JSON response"}';
    }
    
    echo $json;
    exit();
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(true, '', ['favorites' => []]);
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = [];
    }

    $product_ids = $data['product_ids'] ?? [];

    if (empty($product_ids)) {
        sendJsonResponse(true, '', ['favorites' => []]);
    }

    // Convert all product IDs to integers for safety
    $product_ids = array_map('intval', $product_ids);
    $product_ids = array_filter($product_ids); // Remove any zeros

    if (empty($product_ids)) {
        sendJsonResponse(true, '', ['favorites' => []]);
    }

    // Include database connection
    try {
        // Include the configuration file
        $configFile = dirname(dirname(dirname(__DIR__))) . '/config.php';
        if (!file_exists($configFile)) {
            throw new Exception('Config file not found at: ' . $configFile);
        }
        require_once $configFile;
        
        // Include the database class
        $dbFile = dirname(dirname(dirname(__DIR__))) . '/includes/Database.php';
        if (!file_exists($dbFile)) {
            throw new Exception('Database class not found at: ' . $dbFile);
        }
        require_once $dbFile;
        
        // Get database instance and connection
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Test the connection with a simple query
        $conn->query('SELECT 1');
    } catch (PDOException $e) {
        error_log('Database PDO Error: ' . $e->getMessage());
        throw new Exception('Database error: ' . $e->getMessage(), $e->getCode(), $e);
    } catch (Exception $e) {
        error_log('Database connection error: ' . $e->getMessage());
        throw new Exception('Error de conexión con la base de datos: ' . $e->getMessage(), $e->getCode(), $e);
    }
    
    // Check which products are in favorites
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $query = "SELECT producto_id FROM favoritos WHERE usuario_id = ? AND producto_id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    // Prepare parameters (user_id + product_ids)
    $params = array_merge([$_SESSION['user_id']], $product_ids);
    
    // Execute with parameters
    $stmt->execute($params);
    
    // Fetch all favorite product IDs
    $favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Return the list of favorite product IDs
    sendJsonResponse(true, '', ['favorites' => $favorites]);

} catch (Throwable $e) {
    error_log('Error in check.php: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Ensure no output has been sent
    cleanOutputBuffers();
    
    // Send JSON error response
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    exit();
}

// Make sure no other output is sent
cleanOutputBuffers();