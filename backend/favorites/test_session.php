<?php
// Start session with the correct name
session_name('silco_session');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set the correct path for error log
$errorLogPath = dirname(dirname(dirname(__DIR__))) . '/error_log.txt';
ini_set('error_log', $errorLogPath);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Test database connection
try {
    // Set the correct paths
    $rootDir = 'C:\\xampp\\htdocs\\Silco';
    
    // Include config and database files
    $configPath = $rootDir . '\\config.php';
    $databasePath = $rootDir . '\\includes\\Database.php';
    
    // Debug paths
    error_log('Looking for config at: ' . $configPath);
    error_log('Looking for Database at: ' . $databasePath);
    
    if (!file_exists($configPath)) {
        throw new Exception('Config file not found at: ' . $configPath);
    }
    
    if (!file_exists($databasePath)) {
        throw new Exception('Database class not found at: ' . $databasePath);
    }
    
    require_once $configPath;
    require_once $databasePath;
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Test query
    $stmt = $conn->query("SELECT 1 as test");
    $dbTest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if favoritos table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'favoritos'")->rowCount() > 0;
    
    // Response data
    $response = [
        'success' => true,
        'session' => [
            'id' => session_id(),
            'status' => session_status(),
            'data' => $_SESSION
        ],
        'database' => [
            'connection' => $dbTest ? 'success' : 'failed',
            'favoritos_table_exists' => $tableExists
        ]
    ];
    
    if (!$tableExists) {
        $response['database']['error'] = 'The favoritos table does not exist in the database';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
