<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../helpers/UploadHelper.php';

// Inicializar la sesión
$session = Session::getInstance();

// Verificar autenticación
if (!isLoggedIn() || !isVendedor()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /Silco/login.php');
    exit();
}

// Obtener conexión a la base de datos
$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Verificar si se proporcionó un ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de producto no válido.';
    header('Location: productos.php');
    exit();
}

$producto_id = (int)$_GET['id'];

// Obtener el producto para asegurarnos de que pertenece al vendedor
try {
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([$producto_id, $user_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        $_SESSION['error'] = 'Producto no encontrado o no tienes permiso para editarlo.';
        header('Location: productos.php');
        exit();
    }
    
    // Establecer valores por defecto si no existen
    $producto['destacado'] = $producto['destacado'] ?? 0;
    $producto['envio_gratis'] = $producto['envio_gratis'] ?? 0;
    
    // Obtener las imágenes del producto
    $stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden");
    $stmt->execute([$producto_id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar las imágenes al array del producto
    $producto['imagenes'] = array_column($imagenes, 'imagen_url');
    if (count($producto['imagenes']) > 0) {
        $producto['imagen_principal'] = $producto['imagenes'][0];
    }
    
} catch (PDOException $e) {
    error_log("Error al obtener el producto: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar el producto. Por favor, inténtalo de nuevo.';
    header('Location: productos.php');
    exit();
}

// Obtener categorías de la base de datos
try {
    // Verificar si la tabla categorías existe
    $tableExists = $conn->query("SHOW TABLES LIKE 'categorias'")->rowCount() > 0;
    
    if ($tableExists) {
        // Usar la misma consulta que en nuevo-producto.php (sin el filtro activo = 1)
        $stmt = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Verificar categorías obtenidas
        error_log("Categorías obtenidas: " . print_r($categorias, true));
        
        if (empty($categorias)) {
            error_log('No se encontraron categorías en la base de datos');
            $_SESSION['error'] = 'No se encontraron categorías disponibles. Por favor, contacte al administrador.';
        }
    } else {
        error_log("La tabla 'categorias' no existe en la base de datos");
        $categorias = [];
        $_SESSION['error'] = 'La tabla de categorías no existe en la base de datos.';
    }
} catch (PDOException $e) {
    error_log("Error al obtener categorías: " . $e->getMessage());
    $categorias = [];
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Token de seguridad inválido.';
        header('Location: editar-producto.php?id=' . $producto_id);
        exit();
    }
    
    // Obtener y validar datos del formulario
    $datos = [
        'id' => $producto_id,
        'nombre' => trim($_POST['nombre']),
        'descripcion' => trim($_POST['descripcion']),
        'precio' => (float)$_POST['precio'],
        'stock' => (int)$_POST['stock'],
        'categoria_id' => (int)$_POST['categoria_id'],
        'condicion' => $_POST['condicion'] ?? 'nuevo',
        'marca' => trim($_POST['marca'] ?? ''),
        'modelo' => trim($_POST['modelo'] ?? ''),
        'sku' => trim($_POST['sku'] ?? ''),
    ];
    
    // Validaciones
    $errores = [];
    
    if (empty($datos['nombre'])) {
        $errores[] = 'El nombre del producto es obligatorio.';
    }
    
    if (empty($datos['descripcion'])) {
        $errores[] = 'La descripción del producto es obligatoria.';
    }
    
    if ($datos['precio'] <= 0) {
        $errores[] = 'El precio debe ser mayor a 0.';
    }
    
    if ($datos['stock'] < 0) {
        $errores[] = 'El stock no puede ser negativo.';
    }
    
    if ($datos['categoria_id'] <= 0) {
        $errores[] = 'Debes seleccionar una categoría válida.';
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errores)) {
        try {
            $conn->beginTransaction();
            
            // Actualizar el producto
            $query = "UPDATE productos SET 
                     nombre = :nombre,
                     descripcion = :descripcion,
                     precio = :precio,
                     stock = :stock,
                     categoria_id = :categoria_id,
                     condicion = :condicion,
                     marca = :marca,
                     modelo = :modelo,
                     sku = :sku,
                     fecha_actualizacion = NOW()
                     WHERE id = :id AND vendedor_id = :vendedor_id";
            
            $stmt = $conn->prepare($query);
            $datos['vendedor_id'] = $user_id;
            
            if (!$stmt->execute($datos)) {
                throw new Exception('Error al actualizar el producto.');
            }
            
            // Procesar imágenes si se subieron
            if (!empty($_FILES['imagenes']['name'][0])) {
                $uploadHelper = new UploadHelper('imagenes', 'productos');
                $resultado = $uploadHelper->uploadMultiple();
                
                if ($resultado['success']) {
                    // Eliminar imágenes existentes si se solicita
                    if (isset($_POST['imagenes_eliminadas']) && is_array($_POST['imagenes_eliminadas'])) {
                        foreach ($_POST['imagenes_eliminadas'] as $imagen_eliminada) {
                            // Eliminar del servidor
                            $ruta_imagen = $_SERVER['DOCUMENT_ROOT'] . parse_url($imagen_eliminada, PHP_URL_PATH);
                            if (file_exists($ruta_imagen)) {
                                unlink($ruta_imagen);
                            }
                            
                            // Eliminar de la base de datos
                            $stmt = $conn->prepare("DELETE FROM imagenes_producto WHERE producto_id = ? AND imagen_url = ?");
                            $stmt->execute([$producto_id, $imagen_eliminada]);
                        }
                    }
                    
                    // Insertar nuevas imágenes
                    $orden = 1;
                    $stmt = $conn->prepare("SELECT COALESCE(MAX(orden), 0) as max_orden FROM imagenes_producto WHERE producto_id = ?");
                    $stmt->execute([$producto_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $orden = $result['max_orden'] + 1;
                    
                    $stmt = $conn->prepare("INSERT INTO imagenes_producto (producto_id, imagen_url, orden, fecha_creacion) VALUES (?, ?, ?, NOW())");
                    
                    foreach ($resultado['paths'] as $imagen) {
                        $stmt->execute([$producto_id, $imagen, $orden++]);
                    }
                } else {
                    throw new Exception('Error al subir las imágenes: ' . $resultado['error']);
                }
            }
            
            // Actualizar el orden de las imágenes si se especificó
            if (isset($_POST['imagen_orden']) && is_array($_POST['imagen_orden'])) {
                $stmt = $conn->prepare("UPDATE imagenes_producto SET orden = ? WHERE producto_id = ? AND imagen_url = ?");
                $orden = 1;
                foreach ($_POST['imagen_orden'] as $imagen_url) {
                    $stmt->execute([$orden++, $producto_id, $imagen_url]);
                }
            }
            
            $conn->commit();
            $_SESSION['success'] = 'El producto ha sido actualizado correctamente.';
            header('Location: editar-producto.php?id=' . $producto_id);
            exit();
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error al actualizar el producto: " . $e->getMessage());
            $errores[] = 'Ocurrió un error al actualizar el producto. Por favor, inténtalo de nuevo. ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel de Vendedor - Silco</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet">
    <style>
        /* Estilos generales */
        body {
            background-color: #f8f9fa;
        }
        .dashboard-header {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .form-control, .form-select {
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn {
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
        }
        .btn-primary:hover {
            background-color: #3a56d4;
            border-color: #3a56d4;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #dee2e6;
        }
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        
        /* Estilos para las imágenes */
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .preview-item {
            position: relative;
            width: 100%;
            padding-bottom: 100%;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            overflow: hidden;
            background: #fff;
            transition: all 0.2s ease;
        }
        .preview-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .preview-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-image {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 1.5rem;
            height: 1.5rem;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .preview-item:hover .remove-image {
            opacity: 1;
        }
        .preview-item .sort-handle {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 1.5rem;
            height: 1.5rem;
            background: rgba(13, 110, 253, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: move;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .preview-item:hover .sort-handle {
            opacity: 1;
        }
        .preview-item .image-order {
            position: absolute;
            bottom: 0.5rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        /* Estilos para el área de arrastrar y soltar */
        .dropzone {
            border: 2px dashed #dee2e6;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            margin: 1.5rem 0;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        .dropzone:hover {
            border-color: #4361ee;
            background: #f0f4ff;
        }
        .dropzone i {
            font-size: 2.5rem;
            color: #4361ee;
            margin-bottom: 0.75rem;
            display: block;
        }
        .dropzone p {
            margin: 0.25rem 0;
            color: #6c757d;
        }
        .dropzone p.small {
            font-size: 0.875rem;
            color: #adb5bd;
        }
        
        /* Estilos para el formulario */
        .form-section {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }
        .form-text {
            font-size: 0.8125rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        /* Estilos para los botones de acción */
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-main">
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1>Editar Producto</h1>
                            <p class="text-muted mb-0">Actualiza la información de tu producto</p>
                        </div>
                        <div class="header-actions">
                            <a href="producto.php?id=<?php echo $producto_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Volver al Producto
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($errores)): ?>
                    <div class="alert alert-danger">
                        <h5>Por favor, corrige los siguientes errores:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="/Silco/vendedor/editar-producto.php?id=<?php echo $producto_id; ?>" method="POST" enctype="multipart/form-data" id="productoForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle me-2"></i>Información Básica</h3>
                    </div>
                    <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                               value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                        <div class="form-text">Un título claro y descriptivo ayuda a los compradores a encontrar tu artículo.</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="precio" class="form-label">Precio *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" class="form-control" id="precio" name="precio" 
                                                           step="0.01" min="0" required 
                                                           value="<?php echo number_format($producto['precio'], 2, '.', ''); ?>">
                                                </div>
                                                <div class="form-text">Precio de venta al público.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="precio_oferta" class="form-label">Precio de oferta</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" class="form-control" id="precio_oferta" 
                                                           name="precio_oferta" step="0.01" min="0"
                                                           value="<?php echo $producto['precio_oferta'] ? number_format($producto['precio_oferta'], 2, '.', '') : ''; ?>">
                                                </div>
                                                <div class="form-text">Opcional. Precio con descuento.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="stock" class="form-label">Cantidad en Stock *</label>
                                                <input type="number" class="form-control" id="stock" name="stock" 
                                                       min="0" required 
                                                       value="<?php echo (int)$producto['stock']; ?>">
                                                <div class="form-text">Cantidad disponible para la venta.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="categoria_id" class="form-label">Categoría *</label>
                                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                                    <?php if (!empty($categorias)): ?>
                                                        <option value="">Selecciona una categoría</option>
                                                        <?php foreach ($categorias as $categoria): ?>
                                                            <option value="<?php echo $categoria['id']; ?>" 
                                                                <?php echo (isset($producto['categoria_id']) && $producto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="">No hay categorías disponibles</option>
                                                    <?php endif; ?>
                                                </select>
                                                <div class="form-text">Selecciona la categoría principal del producto.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción *</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php 
                                            echo htmlspecialchars($producto['descripcion']); 
                                        ?></textarea>
                                        <div class="form-text">Describe tu producto con el mayor detalle posible. Incluye características, materiales, dimensiones, etc.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <!-- Sección de Categorías -->
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Categorías del Producto</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($categorias)): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Categoría principal *</label>
                                                    <select class="form-select" name="categoria_id" required>
                                                        <option value="">Selecciona una categoría</option>
                                                        <?php foreach ($categorias as $categoria): ?>
                                                            <option value="<?php echo $categoria['id']; ?>" 
                                                                <?php echo ($producto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="form-text">Selecciona la categoría principal para este producto.</div>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    No hay categorías disponibles. Por favor, crea categorías primero.
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Aquí puedes agregar más categorías si es necesario -->
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="activo" name="activo" 
                                                       value="1" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="activo">Producto activo</label>
                                                <div class="form-text">Desactiva para ocultar temporalmente el producto.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" name="marca" 
                                               value="<?php echo htmlspecialchars($producto['marca'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" 
                                               value="<?php echo htmlspecialchars($producto['modelo'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU</label>
                                        <input type="text" class="form-control" id="sku" name="sku" 
                                               value="<?php echo htmlspecialchars($producto['sku'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="peso" class="form-label">Peso (kg)</label>
                                                <input type="number" step="0.01" class="form-control" id="peso" name="peso" 
                                                       value="<?php echo htmlspecialchars($producto['peso'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="dimensiones" class="form-label">Dimensiones (LxAxA cm)</label>
                                                <input type="text" class="form-control" id="dimensiones" name="dimensiones" 
                                                       value="<?php echo htmlspecialchars($producto['dimensiones'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="condicion" class="form-label">Condición *</label>
                                        <select class="form-select" id="condicion" name="condicion" required>
                                            <option value="nuevo" <?php echo ($producto['condicion'] ?? 'nuevo') === 'nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                                            <option value="usado" <?php echo ($producto['condicion'] ?? '') === 'usado' ? 'selected' : ''; ?>>Usado</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                               <?php echo ($producto['activo'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo">Producto activo</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Imágenes del Producto</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Imágenes actuales</label>
                                <div id="imagePreview" class="preview-container">
                                    <?php foreach ($imagenes as $index => $imagen): ?>
                                        <div class="preview-item" data-id="<?php echo $imagen['id']; ?>">
                                            <?php 
                                            $imagePath = $imagen['imagen_url'];
                                            if (strpos($imagePath, 'http') !== 0) {
                                                $imagePath = '/Silco/' . ltrim($imagePath, '/');
                                            }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Imagen del producto">
                                            <button type="button" class="remove-image" data-id="<?php echo $imagen['id']; ?>">
                                                &times;
                                            </button>
                                            <div class="sort-handle">
                                                <i class="fas fa-arrows-alt"></i>
                                            </div>
                                            <div class="image-order"><?php echo $index + 1; ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" id="imagenes_orden" name="imagenes_orden" 
                                       value="<?php echo implode(',', array_column($imagenes, 'id')); ?>">
                                
                                <div class="mt-4">
                                    <label class="form-label">Agregar más imágenes</label>
                                    <div class="dropzone" id="imageDropzone">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Arrastra y suelta las imágenes aquí o haz clic para seleccionar</p>
                                        <p class="small">Puedes subir hasta 10 imágenes. Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB por imagen.</p>
                                    </div>
                                    <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" style="display: none;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="form-actions">
                    <a href="productos.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                    <div>
                        <a href="#" class="btn btn-outline-danger me-2" data-bs-toggle="modal" data-bs-target="#confirmarEliminar">
                            <i class="fas fa-trash-alt me-2"></i> Eliminar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
                
                <!-- Modal de confirmación de eliminación -->
                <div class="modal fade" id="confirmarEliminar" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirmar eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.</p>
                                <p class="fw-bold"><?php echo htmlspecialchars($producto['nombre']); ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <a href="/Silco/vendedor/eliminar-producto.php?id=<?php echo $producto_id; ?>" class="btn btn-danger">
                                    <i class="fas fa-trash-alt me-2"></i> Sí, eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        $(document).ready(function() {
            // Variables globales
            let imageFiles = [];
            const maxFiles = 10;
            
            // Inicializar Sortable para el contenedor de previsualización
            const previewContainer = document.getElementById('imagePreview');
            const sortable = new Sortable(previewContainer, {
                animation: 150,
                onEnd: updateImageOrder
            });
            
            // Manejar clic en el área de carga
            $('#imageDropzone').on('click', function() {
                $('#imagenes').click();
            });
            
            // Manejar cambio en el input de archivo
            $('#imagenes').on('change', function(e) {
                handleFiles(e.target.files);
                $(this).val(''); // Resetear el input para permitir cargar la misma imagen otra vez
            });
            
            // Manejar arrastrar y soltar
            const dropzone = document.getElementById('imageDropzone');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropzone.classList.add('bg-light');
            }
            
            function unhighlight() {
                dropzone.classList.remove('bg-light');
            }
            
            dropzone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFiles(files);
            }
            
            // Procesar archivos seleccionados
            function handleFiles(files) {
                if (files.length === 0) return;
                
                // Verificar límite de archivos
                const currentImages = $('.preview-item').length;
                if (currentImages + files.length > maxFiles) {
                    alert(`Solo puedes subir un máximo de ${maxFiles} imágenes.`);
                    return;
                }
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // Verificar tipo de archivo
                    if (!file.type.match('image.*')) {
                        alert(`El archivo ${file.name} no es una imagen válida.`);
                        continue;
                    }
                    
                    // Verificar tamaño (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`El archivo ${file.name} es demasiado grande. Tamaño máximo: 5MB`);
                        continue;
                    }
                    
                    // Agregar a la lista de archivos
                    imageFiles.push(file);
                    
                    // Mostrar previsualización
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        previewItem.setAttribute('data-filename', file.name);
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = file.name;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'remove-image';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.onclick = function() {
                            removeImage(file.name);
                        };
                        
                        const sortHandle = document.createElement('div');
                        sortHandle.className = 'sort-handle';
                        sortHandle.innerHTML = '<i class="fas fa-arrows-alt"></i>';
                        
                        const orderBadge = document.createElement('div');
                        orderBadge.className = 'image-order';
                        orderBadge.textContent = $('.preview-item').length + 1;
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        previewItem.appendChild(sortHandle);
                        previewItem.appendChild(orderBadge);
                        
                        document.getElementById('imagePreview').appendChild(previewItem);
                        
                        // Actualizar orden
                        updateImageOrder();
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                // Actualizar el input de archivo para el formulario
                updateFileInput();
            }
            
            // Eliminar imagen existente
            $(document).on('click', '.remove-image', function(e) {
                e.preventDefault();
                
                const imageId = $(this).data('id');
                const previewItem = $(this).closest('.preview-item');
                
                if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
                    if (imageId) {
                        // Es una imagen existente, marcar para eliminación
                        let deletedImages = $('#deleted_images').val() || '';
                        if (deletedImages) deletedImages += ',';
                        deletedImages += imageId;
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'deleted_images',
                            name: 'deleted_images',
                            value: deletedImages
                        }).appendTo('form');
                    }
                    
                    // Eliminar la previsualización
                    previewItem.remove();
                    updateImageOrder();
                }
            });
            
            // Actualizar orden de imágenes
            function updateImageOrder() {
                const order = [];
                $('.preview-item').each(function(index) {
                    const id = $(this).data('id');
                    if (id) {
                        order.push(id);
                    }
                    $(this).find('.image-order').text(index + 1);
                });
                
                $('#imagenes_orden').val(order.join(','));
            }
            
            // Actualizar el input de archivo para el formulario
            function updateFileInput() {
                const dataTransfer = new DataTransfer();
                imageFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                $('#imagenes')[0].files = dataTransfer.files;
            }
            
            // Validar formulario antes de enviar
            $('#productoForm').on('submit', function(e) {
                const hasExistingImages = $('.preview-item[data-id]').length > 0;
                const hasNewImages = $('.preview-item:not([data-id])').length > 0;
                const hasImageFiles = $('#imagenes')[0].files.length > 0;
                
                if (!hasExistingImages && !hasNewImages && !hasImageFiles) {
                    e.preventDefault();
                    alert('Debes subir al menos una imagen del producto.');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>
