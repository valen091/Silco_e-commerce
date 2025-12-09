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
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para ver el carrito']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get cart items with product details
    $stmt = $conn->prepare("
        SELECT c.id, c.producto_id, c.cantidad, 
               p.nombre, p.precio, p.precio * c.cantidad as subtotal, p.stock,
               CONCAT('uploads/productos/', (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1)) as imagen
        FROM carrito c
        JOIN productos p ON c.producto_id = p.id
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$session->get('user_id')]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no items in cart, return empty array
    if (empty($items)) {
        echo json_encode([
            'success' => true,
            'items' => [],
            'subtotal' => '0.00',
            'total' => '0.00'
        ]);
        exit();
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($items as &$item) {
        $item['subtotal'] = (float)$item['precio'] * (int)$item['cantidad'];
        $subtotal += $item['subtotal'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'subtotal' => number_format($subtotal, 2, '.', ''),
        'total' => number_format($subtotal, 2, '.', '') // Add shipping here if needed
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar el carrito: ' . $e->getMessage()]);
}
