<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Session.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get filter parameters
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$inStock = isset($_GET['in_stock']) ? $_GET['in_stock'] === 'true' : false;
$categories = isset($_GET['categories']) ? json_decode($_GET['categories']) : [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

try {
    $db = Database::getInstance()->getConnection();
    
    // Base query
    $query = "SELECT p.*, c.nombre as categoria_nombre,
             (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal,
             (p.stock > 0) as en_stock
             FROM productos p 
             JOIN categorias c ON p.categoria_id = c.id 
             WHERE p.activo = 1";
    
    $params = [];
    $conditions = [];
    
    // Add price filter
    if ($minPrice !== null) {
        $conditions[] = "p.precio >= ?";
        $params[] = $minPrice;
    }
    
    if ($maxPrice !== null) {
        $conditions[] = "p.precio <= ?";
        $params[] = $maxPrice;
    }
    
    // Add stock filter
    if ($inStock) {
        $conditions[] = "p.stock > 0";
    }
    
    // Add category filter
    if (!empty($categories)) {
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $conditions[] = "p.categoria_id IN ($placeholders)";
        $params = array_merge($params, $categories);
    }
    
    // Add conditions to query
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    // Add sorting and pagination
    $query .= " ORDER BY p.fecha_creacion DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    // Get filtered products
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = str_replace(
        "SELECT p.*, c.nombre as categoria_nombre, (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal, (p.stock > 0) as en_stock",
        "SELECT COUNT(*)",
        $query
    );
    $countQuery = preg_replace('/LIMIT \? OFFSET \?$/', '', $countQuery);
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, -2));
    $totalProducts = $countStmt->fetchColumn();
    
    // Format response
    $response = [
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => (int)$totalProducts,
            'total_pages' => ceil($totalProducts / $perPage)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al filtrar productos: ' . $e->getMessage()
    ]);
}
