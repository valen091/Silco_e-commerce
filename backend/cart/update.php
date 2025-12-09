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
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validate input
if ($itemId <= 0 || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Get cart item with product details
    $stmt = $conn->prepare("
        SELECT c.*, p.precio, p.stock 
        FROM carrito c
        JOIN productos p ON c.producto_id = p.id
        WHERE c.id = ? AND c.usuario_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$itemId, $session->get('user_id')]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('El ítem no existe en tu carrito');
    }
    
    // Check stock
    if ($quantity > $item['stock']) {
        throw new Exception('No hay suficiente stock disponible');
    }
    
    // Update quantity
    $stmt = $conn->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$quantity, $itemId, $session->get('user_id')]);
    
    // Commit transaction
    $conn->commit();
    
    // Get updated cart
    $stmt = $conn->prepare("
        SELECT c.cantidad, p.precio, p.precio * c.cantidad as subtotal
        FROM carrito c
        JOIN productos p ON c.producto_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$itemId]);
    $updatedItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cantidad actualizada',
        'item' => [
            'id' => $itemId,
            'cantidad' => (int)$updatedItem['cantidad'],
            'subtotal' => number_format($updatedItem['subtotal'], 2, '.', '')
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
