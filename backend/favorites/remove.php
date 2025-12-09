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
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'Debes iniciar sesión para eliminar productos de favoritos', null, 401);
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Datos de entrada no válidos', null, 400);
    }

    $producto_id = $data['producto_id'] ?? null;

    if (!$producto_id || !is_numeric($producto_id)) {
        sendJsonResponse(false, 'ID de producto no válido', null, 400);
    }

    $usuario_id = (int)$_SESSION['user_id'];
    $producto_id = (int)$producto_id;
    
    // Include required files
    $rootPath = realpath(__DIR__ . '/../../');
    $configPath = $rootPath . '/config.php';
    $dbPath = $rootPath . '/includes/Database.php';
    
    if (!file_exists($configPath) || !file_exists($dbPath)) {
        sendJsonResponse(false, 'Error de configuración del sistema', null, 500);
    }
    
    require_once $configPath;
    require_once $dbPath;
    
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Get product info before removing
        $stmt = $conn->prepare("SELECT p.id, p.nombre FROM productos p WHERE p.id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            $conn->rollBack();
            sendJsonResponse(false, 'El producto no existe', null, 404);
        }
        
        // First, check if the product exists in favorites
        $stmt = $conn->prepare("SELECT p.nombre FROM favoritos f 
                              JOIN productos p ON p.id = f.producto_id 
                              WHERE f.usuario_id = ? AND f.producto_id = ?");
        $stmt->execute([$usuario_id, $producto_id]);
        $favorite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$favorite) {
            $conn->commit();
            sendJsonResponse(true, 'El producto no estaba en tus favoritos', [
                'inFavorites' => false,
                'product' => [
                    'id' => $producto_id,
                    'nombre' => $producto ? $producto['nombre'] : 'Producto desconocido'
                ]
            ]);
        }
        
        // Remove from favorites
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$usuario_id, $producto_id]);
        $rowsAffected = $stmt->rowCount();
        
        if ($rowsAffected === 0) {
            throw new Exception('No se pudo eliminar el producto de favoritos');
        }
        
        // Get updated favorites count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $conn->commit();
        
        sendJsonResponse(true, 'Producto eliminado de favoritos', [
            'count' => $count,
            'inFavorites' => false,
            'product' => [
                'id' => $producto_id,
                'nombre' => $producto['nombre']
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database Error in remove.php: " . $e->getMessage());
    sendJsonResponse(
        false, 
        'Error de base de datos al eliminar de favoritos', 
        ['error_id' => uniqid('ERR_')], 
        500
    );
} catch (Exception $e) {
    error_log("Error in remove.php: " . $e->getMessage());
    sendJsonResponse(
        false, 
        'Error al procesar la solicitud: ' . $e->getMessage(), 
        ['error_id' => uniqid('ERR_')], 
        500
    );
}
