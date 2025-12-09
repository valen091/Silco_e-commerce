<?php
// Start session with the correct name
session_name('silco_session');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../error_log.txt');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data) $response = array_merge($response, $data);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(); // Stop script execution after sending the response
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(false, 'Debes iniciar sesión para agregar a favoritos', [], 401);
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'Datos de entrada no válidos', [], 400);
    }

    $product_id = $data['product_id'] ?? null;

    if (!$product_id || !is_numeric($product_id)) {
        sendJsonResponse(false, 'ID de producto no válido', [], 400);
    }

    // Include database connection
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../includes/Database.php';
    require_once __DIR__ . '/../../../includes/functions.php';

    $db = Database::getInstance();
    $conn = $db->getConnection();
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->beginTransaction();

    try {
        // Verify if product exists and is active
        $stmt = $conn->prepare("SELECT id, nombre, activo FROM productos WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $conn->rollBack();
            sendJsonResponse(false, 'El producto no existe', null, 404);
        }
        
        if ($product['activo'] != 1) {
            $conn->rollBack();
            sendJsonResponse(false, 'El producto no está disponible actualmente', null, 400);
        }

        // Check if already in favorites
        $stmt = $conn->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        if ($stmt->fetch()) {
            $conn->commit();
            sendJsonResponse(true, 'El producto ya está en tus favoritos', ['inFavorites' => true]);
        }

        // Add to favorites
        $stmt = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id, fecha_agregado) VALUES (?, ?, NOW())");
        
        if ($stmt->execute([$user_id, $product_id])) {
            $conn->commit();
            sendJsonResponse(true, 'Producto agregado a favoritos', ['favorited' => true]);
        } else {
            throw new Exception('Error al agregar a favoritos');
        }

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database Error in add.php: " . $e->getMessage());
    sendJsonResponse(
        false, 
        'Error de base de datos al agregar a favoritos', 
        ['error_id' => uniqid('ERR_')], 
        500
    );
} catch (Exception $e) {
    error_log("Error in add.php: " . $e->getMessage());
    sendJsonResponse(
        false, 
        'Error al procesar la solicitud: ' . $e->getMessage(), 
        ['error_id' => uniqid('ERR_')], 
        500
    );
}