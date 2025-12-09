<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');

// Iniciar sesión
$session = Session::getInstance();

// Verificar si el usuario está logueado
if (!$session->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Por favor, inicia sesión.']);
    exit();
}

// Obtener el ID de la orden desde la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$orderID = $data['orderID'] ?? '';

if (empty($orderID)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de orden no proporcionado']);
    exit();
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // 1. First, get the order details to check its status
    $ch = curl_init("https://api-m.sandbox.paypal.com/v2/checkout/orders/{$orderID}");
    
    // Get PayPal credentials from environment variables with fallback to default sandbox credentials
    $paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: 'AR-ujDlOqtzzwZOVxEbxc2BkagyofmF8RPhN2OYGhUYMVctjqjfRqqykmhTbTwlpbe6oKMb-9eJJ5f-a';
    $paypalSecret = getenv('PAYPAL_SECRET') ?: 'EJWSZjP15Clwewvxm12cCl8YMBFP1Fr0E6Hk8_DStBJXTdXiSU_35XEFYic2zes3h6VB_O_xBSJK2Mxa';
    
    if (empty($paypalClientId) || empty($paypalSecret)) {
        throw new Exception('PayPal credentials not configured');
    }
    
    // For debugging - remove in production
    error_log('Using PayPal Client ID: ' . substr($paypalClientId, 0, 10) . '...');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($paypalClientId . ':' . $paypalSecret),
        'Prefer: return=representation'
    ]);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    
    if ($err) {
        curl_close($ch);
        throw new Exception('Error al conectar con PayPal: ' . $err);
    }
    
    $orderData = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMsg = $orderData['message'] ?? 'Error desconocido al obtener detalles de la orden';
        curl_close($ch);
        throw new Exception('PayPal API Error: ' . $errorMsg);
    }
    
    // 2. Capture the payment if not already captured
    if (strtoupper($orderData['status']) === 'APPROVED') {
        curl_close($ch);
        $ch = curl_init("https://api-m.sandbox.paypal.com/v2/checkout/orders/{$orderID}/capture");
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($paypalClientId . ':' . $paypalSecret),
            'Prefer: return=representation'
        ]);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        
        if ($err) {
            curl_close($ch);
            throw new Exception('Error al capturar el pago: ' . $err);
        }
        
        $captureData = json_decode($response, true);
        
        if ($httpCode !== 201) {
            $errorMsg = $captureData['message'] ?? 'Error desconocido al capturar el pago';
            curl_close($ch);
            throw new Exception('PayPal API Error: ' . $errorMsg);
        }
    } else if (strtoupper($orderData['status']) === 'COMPLETED') {
        // If the order is already completed, use its data
        $captureData = $orderData;
    } else {
        curl_close($ch);
        throw new Exception('La orden no está aprobada para captura. Estado: ' . $orderData['status']);
    }
    
    // 2. Get payment information
    $paymentID = '';
    $status = strtoupper($captureData['status'] ?? '');
    $amount = '0';
    
    // Extract payment details based on the response structure
    if (isset($captureData['purchase_units'][0]['payments']['captures'][0])) {
        $capture = $captureData['purchase_units'][0]['payments']['captures'][0];
        $paymentID = $capture['id'] ?? '';
        $amount = $capture['amount']['value'] ?? '0';
    } elseif (isset($captureData['purchase_units'][0]['payments']['authorizations'][0])) {
        // In case of authorized payments
        $auth = $captureData['purchase_units'][0]['payments']['authorizations'][0];
        $paymentID = $auth['id'] ?? '';
        $amount = $auth['amount']['value'] ?? '0';
        $status = 'AUTHORIZED';
    }
    
    // 3. Crear el pedido en la base de datos
    $userID = $session->get('user_id');
    $fecha = date('Y-m-d H:i:s');
    
    // Insertar el pedido
    $stmt = $conn->prepare("
        INSERT INTO pedidos (
            usuario_id, 
            fecha_pedido, 
            estado, 
            total, 
            metodo_pago, 
            id_transaccion, 
            estado_pago, 
            direccion_envio
        ) VALUES (?, ?, 'Pendiente', ?, 'PayPal', ?, ?, '')
    ");
    
    $stmt->execute([
        $userID,
        $fecha,
        $amount,
        $paymentID,
        $status
    ]);
    
    $orderID = $conn->lastInsertId();
    
    // 4. Obtener los items del carrito
    $stmt = $conn->prepare("
        SELECT c.*, p.nombre, p.precio 
        FROM carrito c 
        JOIN productos p ON c.producto_id = p.id 
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$userID]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Insertar los items del pedido y actualizar el stock
    $stmtItem = $conn->prepare("
        INSERT INTO detalles_pedido (
            pedido_id, 
            producto_id, 
            cantidad, 
            precio_unitario, 
            subtotal
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmtUpdateStock = $conn->prepare("
        UPDATE productos 
        SET stock = stock - ? 
        WHERE id = ?
    ");
    
    foreach ($cartItems as $item) {
        // Insertar detalle del pedido
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmtItem->execute([
            $orderID,
            $item['producto_id'],
            $item['cantidad'],
            $item['precio'],
            $subtotal
        ]);
        
        // Actualizar stock
        $stmtUpdateStock->execute([
            $item['cantidad'],
            $item['producto_id']
        ]);
    }
    
    // 6. Vaciar el carrito
    $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
    $stmt->execute([$userID]);
    
    // Confirmar la transacción
    $conn->commit();
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true,
        'id' => $orderID,
        'status' => $status,
        'payment_id' => $paymentID,
        'amount' => $amount
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al procesar el pago',
        'message' => $e->getMessage()
    ]);
}
