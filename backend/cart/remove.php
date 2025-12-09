<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Start session
$session = Session::getInstance();

// Check if user is logged in
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para modificar el carrito']);
    exit();
}

// Get request data
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

// Validate input
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de ítem inválido']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Delete item from cart
    $stmt = $conn->prepare("DELETE FROM carrito WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$itemId, $session->get('user_id')]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('El ítem no existe en tu carrito');
    }
    
    // Get updated cart count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$session->get('user_id')]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto eliminado del carrito',
        'cart_count' => (int)$count
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
