<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/Session.php';

header('Content-Type: application/json');

// Verificar si se proporcionó un término de búsqueda
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó un término de búsqueda']);
    exit();
}

$query = trim($_GET['q']);
$db = Database::getInstance()->getConnection();

// Buscar productos que coincidan con el término de búsqueda
$search_terms = explode(' ', $query);
$search_conditions = [];
$params = [];

// Añadir condiciones de búsqueda para cada término
foreach ($search_terms as $term) {
    if (strlen($term) >= 2) {
        $search_conditions[] = "(p.nombre LIKE ? OR p.descripcion LIKE ? OR p.palabras_clave LIKE ?)";
        $search_term = "%$term%";
        $params = array_merge($params, [$search_term, $search_term, $search_term]);
    }
}

if (empty($search_conditions)) {
    echo json_encode(['success' => true, 'products' => []]);
    exit();
}

// Consulta para obtener sugerencias de productos
$sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.precio_oferta, 
        c.nombre as categoria_nombre,
        (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal
        FROM productos p 
        JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.activo = 1 AND (" . implode(' OR ', $search_conditions) . ")
        ORDER BY 
            (p.nombre LIKE ?) DESC,
            (p.descripcion LIKE ?) DESC,
            (p.palabras_clave LIKE ?) DESC
        LIMIT 5";

// Añadir los parámetros para el ordenamiento
$params = array_merge($params, ["%$query%", "%$query%", "%$query%"]);

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $productos
    ]);
} catch (PDOException $e) {
    error_log('Error en la búsqueda: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al realizar la búsqueda',
        'error' => $e->getMessage()
    ]);
}
