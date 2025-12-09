<?php
// Incluir archivos necesarios
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/Database.php';

// Obtener conexión a la base de datos
try {
    $db = Database::getInstance();
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// Obtener productos con sus imágenes
$query = "SELECT p.id, p.nombre, 
         (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal
         FROM productos p 
         WHERE p.activo = 1
         LIMIT 10";
$stmt = $db->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para verificar si la imagen existe
function imageExists($path) {
    $fullPath = __DIR__ . '/' . ltrim($path, '/');
    return file_exists($fullPath) ? '✅ Existe' : '❌ No existe';
}

// Mostrar resultados
echo "<h2>Verificación de imágenes de productos</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Producto</th><th>Ruta de la imagen</th><th>Estado</th><th>Vista previa</th></tr>";

foreach ($products as $product) {
    $imagePath = $product['imagen_principal'] ?? '';
    $status = '';
    $preview = '';
    
    if (empty($imagePath)) {
        $status = '❌ No tiene imagen';
    } else {
        $status = imageExists($imagePath);
        if ($status === '✅ Existe') {
            $preview = "<img src='/{$imagePath}' style='max-width: 100px; max-height: 100px;' />";
        }
    }
    
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>" . htmlspecialchars($product['nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($imagePath) . "</td>";
    echo "<td>{$status}</td>";
    echo "<td>{$preview}</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar permisos del directorio de uploads
$uploadsDir = __DIR__ . '/uploads';
$isWritable = is_writable($uploadsDir) ? '✅ Escribible' : '❌ No escribible';
$isReadable = is_readable($uploadsDir) ? '✅ Legible' : '❌ No legible';

$productosDir = __DIR__ . '/uploads/productos';
$productosIsWritable = is_writable($productosDir) ? '✅ Escribible' : '❌ No escribible';
$productosIsReadable = is_readable($productosDir) ? '✅ Legible' : '❌ No legible';

echo "<h3>Permisos de directorios</h3>";
echo "<ul>";
echo "<li>Directorio uploads: {$isReadable}, {$isWritable}</li>";
echo "<li>Directorio uploads/productos: {$productosIsReadable}, {$productosIsWritable}</li>";
echo "</ul>";

// Mostrar contenido del directorio de productos
echo "<h3>Archivos en uploads/productos/</h3>";
$files = glob(__DIR__ . '/uploads/productos/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if (empty($files)) {
    echo "No se encontraron archivos de imagen en el directorio de productos.";
} else {
    echo "<ul>";
    foreach ($files as $file) {
        $filename = basename($file);
        echo "<li>{$filename} (" . filesize($file) . " bytes) - ";
        echo "<a href='/uploads/productos/{$filename}' target='_blank'>Ver</a></li>";
    }
    echo "</ul>";
}
?>
