<?php
session_start();
require_once __DIR__ . '/../../../includes/config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pedido no proporcionado']);
    exit();
}

try {
    // Begin transaction
    $db->beginTransaction();
    
    // Verify the order belongs to the user and is in a cancellable state
    $stmt = $db->prepare("
        SELECT id, estado 
        FROM pedidos 
        WHERE id = ? AND usuario_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Pedido no encontrado o no autorizado');
    }
    
    if (strtolower($order['estado']) !== 'pendiente') {
        throw new Exception('Solo se pueden cancelar pedidos en estado "Pendiente"');
    }
    
    // Update order status to cancelled
    $stmt = $db->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?");
    $stmt->execute([$order_id]);
    
    // If you need to return items to inventory, you would do it here
    // $stmt = $db->prepare("UPDATE productos p 
    //     JOIN items_pedido ip ON p.id = ip.producto_id 
    //     SET p.stock = p.stock + ip.cantidad 
    //     WHERE ip.pedido_id = ?");
    // $stmt->execute([$order_id]);
    
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Pedido cancelado correctamente']);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error al cancelar el pedido: ' . $e->getMessage()
    ]);
}
?>
