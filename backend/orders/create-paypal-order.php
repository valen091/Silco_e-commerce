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

// Obtener datos del carrito
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Obtener los items del carrito
    $stmt = $conn->prepare("
        SELECT c.*, p.nombre, p.precio, p.stock 
        FROM carrito c 
        JOIN productos p ON c.producto_id = p.id 
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$session->get('user_id')]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'El carrito está vacío']);
        exit();
    }
    
    // Calcular totales
    $subtotal = 0;
    $items_paypal = [];
    
    foreach ($items as $item) {
        $item_total = $item['precio'] * $item['cantidad'];
        $subtotal += $item_total;
        
        $items_paypal[] = [
            'name' => $item['nombre'],
            'quantity' => (string)$item['cantidad'],
            'unit_amount' => [
                'currency_code' => 'UYU',
                'value' => (string)number_format($item['precio'], 2, '.', '')
            ],
            'description' => substr($item['nombre'], 0, 127) // Ensure description is not too long
        ];
    }
    
    // Configurar la orden de PayPal
    $orderData = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'amount' => [
                    'currency_code' => 'UYU',
                    'value' => (string)number_format($subtotal, 2, '.', ''),
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'UYU',
                            'value' => (string)number_format($subtotal, 2, '.', '')
                        ]
                    ]
                ],
                'items' => $items_paypal,
                'description' => 'Compra en Silco',
                'custom_id' => 'CART-' . $session->get('user_id') . '-' . time(),
                'invoice_id' => 'INV-' . $session->get('user_id') . '-' . time(),
            ]
        ],
        'application_context' => [
            'brand_name' => 'Silco',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action' => 'PAY_NOW',
            'return_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/checkout.php?success=true',
            'cancel_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/checkout.php?canceled=true'
        ]
    ];
    
    // Ensure JSON encoding works correctly
    $jsonData = json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonData === false) {
        throw new Exception('Error encoding order data: ' . json_last_error_msg());
    }
    
    // Log the request data for debugging
    error_log('Sending to PayPal: ' . $jsonData);
    
    // Inicializar cURL para crear la orden en PayPal
    $ch = curl_init('https://api-m.sandbox.paypal.com/v2/checkout/orders');
    
    // Get PayPal credentials from environment variables with fallback to sandbox credentials
    $paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: 'AR-ujDlOqtzzwZOVxEbxc2BkagyofmF8RPhN2OYGhUYMVctjqjfRqqykmhTbTwlpbe6oKMb-9eJJ5f-a';
    $paypalSecret = getenv('PAYPAL_SECRET') ?: 'EJWSZjP15Clwewvxm12cCl8YMBFP1Fr0E6Hk8_DStBJXTdXiSU_35XEFYic2zes3h6VB_O_xBSJK2Mxa';
    
    if (empty($paypalClientId) || empty($paypalSecret)) {
        throw new Exception('PayPal credentials not configured');
    }
    
    // For debugging - remove in production
    error_log('Using PayPal Client ID: ' . substr($paypalClientId, 0, 10) . '...');
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($paypalClientId . ':' . $paypalSecret)
    ]);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    // Get more detailed error info if available
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);

    // Log the complete response and request info for debugging
    error_log('PayPal API Response ('.$httpCode.'): ' . $response);
    error_log('cURL Info: ' . print_r($curlInfo, true));
    error_log('Request Headers: ' . print_r($orderData, true));

    if ($curlError) {
        error_log("cURL Error: " . $curlError);
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión con PayPal: ' . $curlError]);
        exit();
    }

    $responseData = json_decode($response, true);
    $jsonError = json_last_error();

    if ($httpCode >= 400 || $jsonError !== JSON_ERROR_NONE) {
        $errorDetails = [
            'http_code' => $httpCode,
            'response' => $response,
            'json_error' => $jsonError !== JSON_ERROR_NONE ? json_last_error_msg() : null,
            'response_data' => $responseData
        ];
        
        error_log('PayPal API Error Details: ' . print_r($errorDetails, true));
        
        $errorMsg = 'Error al procesar el pago';
        if (is_array($responseData) && isset($responseData['details'])) {
            $errorMsg = '';
            foreach ($responseData['details'] as $detail) {
                $errorMsg .= $detail['issue'] . ': ' . $detail['description'] . '; ';
            }
        } elseif (isset($responseData['message'])) {
            $errorMsg = $responseData['message'];
        }
        
        http_response_code(400);
        echo json_encode([
            'error' => 'Error de PayPal',
            'message' => trim($errorMsg),
            'details' => $responseData['details'] ?? null
        ]);
        exit();
    }

    // If we get here, the order was created successfully
    echo json_encode($responseData);
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al procesar la orden',
        'message' => $e->getMessage()
    ]);
}
