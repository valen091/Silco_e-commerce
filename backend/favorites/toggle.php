<?php
// Start output buffering to catch any unwanted output
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, we'll handle them
ini_set('log_errors', 1);     // Log errors to the PHP error log

// Set error logging to a specific file
$logDir = __DIR__ . '/../../logs';
ini_set('error_log', $logDir . '/php_errors.log');

// Ensure logs directory exists
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log the start of the request
error_log('[' . date('Y-m-d H:i:s') . '] Favorites toggle request started - ' . json_encode($_POST));

try {
    // Load required files
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../includes/Database.php';
    require_once __DIR__ . '/../../includes/Session.php';

    // Set JSON header
    header('Content-Type: application/json; charset=utf-8');
    
    // Initialize response array
    $response = [
        'success' => false,
        'message' => 'Error desconocido',
        'debug' => []
    ];

    // Verify session
    $session = Session::getInstance();
    
    // Check if user is logged in
    if (!$session->isLoggedIn()) {
        throw new Exception('Debes iniciar sesión para guardar productos en favoritos.', 401);
    }

    // Verify request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido', 405);
    }

    // Get input data (support both JSON and form-data)
    $input = [];
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Formato de datos inválido: ' . json_last_error_msg(), 400);
        }
    } else {
        $input = $_POST;
    }
    
    // Log the received input
    error_log('Received input: ' . print_r($input, true));
    
    $producto_id = isset($input['producto_id']) ? (int)$input['producto_id'] : null;
    if (empty($producto_id)) {
        throw new Exception('ID de producto no proporcionado o inválido', 400);
    }

    // Get database connection
    try {
        $db = Database::getInstance()->getConnection();
        if (!$db) {
            throw new Exception('No se pudo obtener la conexión a la base de datos');
        }
        
        // Set PDO to throw exceptions on error
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        throw new Exception('Error de conexión con la base de datos', 500);
    }

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Debes iniciar sesión para realizar esta acción', 401);
    }
    
    $usuario_id = $_SESSION['user_id'];
    error_log('User ID from session: ' . $usuario_id);

    try {
        // Check if product exists and is active
        $stmt = $db->prepare("SELECT id, nombre FROM productos WHERE id = ? AND activo = 1");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            throw new Exception('Producto no encontrado o no disponible', 404);
        }

        // Check if product is already in favorites
        $stmt = $db->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$usuario_id, $producto_id]);
        $favorito = $stmt->fetch(PDO::FETCH_ASSOC);

        $db->beginTransaction();
        
        if ($favorito) {
            // Remove from favorites
            $stmt = $db->prepare("DELETE FROM favoritos WHERE id = ?");
            $stmt->execute([$favorito['id']]);
            $response = [
                'success' => true, 
                'action' => 'removed', 
                'message' => 'Producto eliminado de favoritos',
                'producto_id' => $producto_id
            ];
            error_log("Producto $producto_id eliminado de favoritos por el usuario $usuario_id");
        } else {
            // Add to favorites
            $stmt = $db->prepare("INSERT INTO favoritos (usuario_id, producto_id, fecha_agregado) VALUES (?, ?, NOW())");
            $stmt->execute([$usuario_id, $producto_id]);
            $response = [
                'success' => true, 
                'action' => 'added', 
                'message' => 'Producto añadido a favoritos',
                'producto_id' => $producto_id
            ];
            error_log("Producto $producto_id añadido a favoritos por el usuario $usuario_id");
        }
        
        $db->commit();

    } catch (PDOException $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        // Log detailed error information
        $errorInfo = $db->errorInfo();
        $errorMessage = sprintf(
            'Database error in toggle.php: %s\nSQL State: %s\nDriver Error: %s\nQuery: %s',
            $e->getMessage(),
            $errorInfo[0] ?? 'N/A',
            $errorInfo[2] ?? 'N/A',
            $stmt->queryString ?? 'N/A'
        );
        
        error_log($errorMessage);
        
        // Save detailed error to a file
        file_put_contents(
            __DIR__ . '/../../logs/db_errors.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $errorMessage . "\n\n",
            FILE_APPEND
        );
        
        throw new Exception('Error al procesar la solicitud en la base de datos: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    
    $errorDetails = [
        'message' => $e->getMessage(),
        'code' => $statusCode,
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    $response = [
        'success' => false, 
        'message' => $e->getMessage(),
        'code' => $statusCode,
        'debug' => $errorDetails
    ];
    
    // Log the error with more details
    error_log(sprintf(
        'Error in toggle.php: %s in %s on line %d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
}

// Clear any output that might have been generated
$output = ob_get_clean();
if (!empty($output)) {
    error_log('Unexpected output in toggle.php: ' . $output);
}

// Set the correct header
header('Content-Type: application/json; charset=utf-8');

// Send the response
$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Log the response for debugging
error_log('Response: ' . $jsonResponse);

echo $jsonResponse;
exit;
