<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Database.php';

// Inicializar la sesión
$session = Session::getInstance();

// Verificar autenticación
if (!isLoggedIn() || !isVendedor()) {
    $_SESSION['error'] = 'Debes iniciar sesión como vendedor para realizar esta acción.';
    header('Location: /Silco/login.php');
    exit();
}

// Verificar si se proporcionó un ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de producto no válido.';
    header('Location: productos.php');
    exit();
}

$producto_id = (int)$_GET['id'];
$vendedor_id = $_SESSION['user_id'];

try {
    // Obtener conexión a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar que el producto pertenezca al vendedor
    $stmt = $conn->prepare("SELECT id, vendedor_id FROM productos WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        $_SESSION['error'] = 'El producto no existe o ya ha sido eliminado.';
        header('Location: productos.php');
        exit();
    }
    
    // Verificar que el vendedor sea el dueño del producto
    if ($producto['vendedor_id'] != $vendedor_id) {
        $_SESSION['error'] = 'No tienes permiso para eliminar este producto.';
        header('Location: productos.php');
        exit();
    }
    
    // Iniciar transacción para asegurar la integridad de los datos
    $conn->beginTransaction();
    
    try {
        // 1. Obtener las imágenes del producto para eliminarlas del servidor
        $stmt = $conn->prepare("SELECT imagen_url FROM imagenes_producto WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        $imagenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 2. Eliminar las entradas de las imágenes en la base de datos
        $stmt = $conn->prepare("DELETE FROM imagenes_producto WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        
        // 3. Eliminar el producto de favoritos
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        
        // 4. Eliminar el producto del carrito de los usuarios
        $stmt = $conn->prepare("DELETE FROM carrito WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        
        // 5. Finalmente, eliminar el producto
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ? AND vendedor_id = ?");
        $stmt->execute([$producto_id, $vendedor_id]);
        
        // Confirmar la transacción
        $conn->commit();
        
        // Eliminar las imágenes del servidor
        foreach ($imagenes as $imagen) {
            $ruta_imagen = __DIR__ . '/../' . $imagen;
            if (file_exists($ruta_imagen)) {
                @unlink($ruta_imagen);
            }
        }
        
        $_SESSION['success'] = 'El producto ha sido eliminado correctamente.';
        
    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Error al eliminar el producto: " . $e->getMessage());
    $_SESSION['error'] = 'Ocurrió un error al intentar eliminar el producto. Por favor, inténtalo de nuevo.';
} catch (Exception $e) {
    error_log("Error inesperado al eliminar el producto: " . $e->getMessage());
    $_SESSION['error'] = 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo.';
}

// Redirigir de vuelta a la lista de productos
header('Location: productos.php');
exit();