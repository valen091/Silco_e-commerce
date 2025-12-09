<?php
// Start output buffering
while (ob_get_level()) ob_end_clean();
ob_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
$logFile = __DIR__ . '/../../../silco_errors.log';
ini_set('error_log', $logFile);

// Clear the log file if it gets too large
if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) { // 5MB
    file_put_contents($logFile, '');
}

// Set default timezone
date_default_timezone_set('America/Montevideo');

// Start session with proper configuration
session_name('silco_session');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 hours
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'use_strict_mode' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers if needed
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// Log function to write to error log
function logError($message, $data = null) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($data !== null) {
        $logMessage .= ' ' . (is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    error_log($logMessage);
    return $logMessage;
}

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
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    // Output the response
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // Log the start of the request
    logError('Starting favorites list request', [
        'session_id' => session_id(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'session_data' => $_SESSION
    ]);
    // Debug session and user ID
    console_log(['Session Data' => $_SESSION]);
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'No autorizado. User ID not found in session.', ['session' => $_SESSION], 401);
    }
    
    $user_id = (int)$_SESSION['user_id'];
    console_log(['User ID' => $user_id]);

    // Include required files using absolute paths
    $rootPath = realpath(__DIR__ . '/../../');
    $configPath = $rootPath . '/config.php';
    $dbPath = $rootPath . '/includes/Database.php';
    $functionsPath = $rootPath . '/includes/functions.php';
    
    if (!file_exists($configPath)) {
        $error = "Config file not found at: $configPath";
        error_log($error);
        sendJsonResponse(false, 'Configuration error', ['error' => $error], 500);
    }
    
    if (!file_exists($dbPath)) {
        $error = "Database file not found at: $dbPath";
        error_log($error);
        sendJsonResponse(false, 'Database error', ['error' => $error], 500);
    }
    
    if (!file_exists($functionsPath)) {
        $error = "Functions file not found at: $functionsPath";
        error_log($error);
        sendJsonResponse(false, 'Functions error', ['error' => $error], 500);
    }
    
    require_once $configPath;
    require_once $dbPath;
    require_once $functionsPath;
    
    // Verify required constants are defined
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        $error = 'Database configuration is incomplete. Please check your config.php file.';
        error_log($error);
        sendJsonResponse(false, 'Configuration error', ['error' => $error], 500);
    }
    
    console_log(['Included files' => [
        'config.php' => file_exists($configPath) ? 'Found' : 'Not found',
        'Database.php' => file_exists($dbPath) ? 'Found' : 'Not found',
        'functions.php' => file_exists($functionsPath) ? 'Found' : 'Not found'
    ]]);

    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user_id = (int)$_SESSION['user_id'];

    // First, verify the user exists
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, 'Usuario no encontrado', [], 404);
    }

    // Get user's favorite products with LEFT JOIN to handle cases where there are no images
    $query = "
        SELECT 
            p.*, 
            ip.imagen,
            (SELECT MIN(precio) FROM variaciones WHERE producto_id = p.id) as precio_desde
        FROM 
            favoritos f
        INNER JOIN 
            productos p ON p.id = f.producto_id AND p.activo = 1
        LEFT JOIN 
            (SELECT producto_id, MIN(imagen) as imagen 
             FROM imagenes_productos 
             GROUP BY producto_id) ip ON ip.producto_id = p.id
        WHERE 
            f.usuario_id = :user_id
        ORDER BY 
            f.fecha_creacion DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    sendJsonResponse(true, '', ['products' => $products]);
    
} catch (PDOException $e) {
    $errorMessage = logError('Database Error in list.php: ', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    sendJsonResponse(
        false, 
        'Error de base de datos', 
        [
            'error' => $e->getMessage(),
            'error_id' => uniqid('ERR_')
        ], 
        500
    );
} catch (Exception $e) {
    $errorMessage = logError('General Error in list.php: ', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    sendJsonResponse(
        false, 
        'Error al cargar los favoritos', 
        [
            'error' => $e->getMessage(),
            'error_id' => uniqid('ERR_')
        ], 
        500
    );
}