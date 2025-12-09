<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get first 5 products with their image paths
$stmt = $conn->query("SELECT id, nombre, imagen_principal FROM productos LIMIT 5");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Product Image Debug</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Imagen Path</th><th>File Exists</th><th>Image</th></tr>";

foreach ($products as $product) {
    $imagePath = '';
    $fileExists = 'No';
    $imageTag = 'N/A';
    
    if (!empty($product['imagen_principal'])) {
        // Try direct path first
        $imagePath = '/Silco/uploads/productos/' . basename($product['imagen_principal']);
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
        
        if (!file_exists($fullPath)) {
            // Try alternative path
            $imagePath = '/Silco/' . ltrim($product['imagen_principal'], '/');
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
        }
        
        if (file_exists($fullPath)) {
            $fileExists = 'Yes';
            $imageTag = sprintf(
                '<img src="%s" style="max-width: 100px; max-height: 100px;">',
                htmlspecialchars($imagePath)
            );
        }
    }
    
    echo sprintf(
        '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        htmlspecialchars($product['id']),
        htmlspecialchars($product['nombre']),
        htmlspecialchars($product['imagen_principal']),
        $fileExists,
        $imageTag
    );
}

echo "</table>";
?>
