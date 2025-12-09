<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';

// Inicializar la sesión
$session = Session::getInstance();

// Verificar si el usuario está autenticado
if (!$session->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para agregar productos al carrito']);
    exit();
}

// Obtener datos de la solicitud
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

// Validar el ID del producto
if ($productId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de producto no válido']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar si el producto existe y está activo
    $stmt = $conn->prepare("SELECT id, precio, stock FROM productos WHERE id = ? AND activo = 1");
    $stmt->execute([$productId]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        throw new Exception('El producto no existe o no está disponible');
    }
    
    // Verificar stock disponible
    if ($producto['stock'] < $quantity) {
        throw new Exception('No hay suficiente stock disponible');
    }
    
    // Verificar si el producto ya está en el carrito del usuario
    $stmt = $conn->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$session->get('user_id'), $productId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        // Actualizar cantidad si el producto ya está en el carrito
        $nuevaCantidad = $item['cantidad'] + $quantity;
        
        // Verificar nuevamente el stock
        if ($producto['stock'] < $nuevaCantidad) {
            throw new Exception('No hay suficiente stock disponible para la cantidad solicitada');
        }
        
        $stmt = $conn->prepare("UPDATE carrito SET cantidad = ?, fecha_actualizacion = NOW() WHERE id = ?");
        $stmt->execute([$nuevaCantidad, $item['id']]);
    } else {
        // Agregar nuevo producto al carrito
        $stmt = $conn->prepare("INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)");
        $stmt->execute([
            $session->get('user_id'),
            $productId,
            $quantity
        ]);
    }
    
    // Obtener el nuevo conteo de artículos en el carrito
    $stmt = $conn->prepare("SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$session->get('user_id')]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartCount = (int)$result['total'] ?? 0;
    
    // Respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado al carrito',
        'cart_count' => $cartCount
    ]);
    
} catch (Exception $e) {
    // Manejar errores
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
