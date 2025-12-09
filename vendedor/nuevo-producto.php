<?php
// Iniciar la sesión primero
require_once __DIR__ . '/../config.php';

// Función para obtener mensajes de error de subida
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el servidor.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo fue subido solo parcialmente.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se subió ningún archivo.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en el disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la carga del archivo.';
        default:
            return 'Error desconocido al subir el archivo.';
    }
}

// Debug: Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir el manejador de sesión
require_once __DIR__ . '/../includes/Session.php';

// Inicializar la sesión
$session = Session::getInstance();

// Incluir funciones
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Database.php';

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = '/Silco/vendedor/nuevo-producto.php';
    header('Location: /Silco/login.php');
    exit();
}

// Verificar si el usuario es vendedor
if (!isVendedor()) {
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección.';
    header('Location: /Silco/perfil.php');
    exit();
}

// Actualizar la actividad de la sesión
$session->set('last_activity', time());

// Obtener información del usuario
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id, email, es_vendedor FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Mostrar información de sesión en el log de errores
    error_log('Acceso a nuevo-producto.php - User ID: ' . $_SESSION['user_id']);
        
    // Verificar si el usuario es vendedor en la base de datos
    if ($user && !$user['es_vendedor']) {
        error_log('Usuario no es vendedor en la base de datos - User ID: ' . $_SESSION['user_id']);
        $_SESSION['error'] = 'No tienes permisos de vendedor en el sistema.';
        header('Location: /Silco/perfil.php');
        exit();
    }
} catch (Exception $e) {
    error_log('Error al conectar a la base de datos: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al verificar los permisos. Por favor, intente de nuevo.';
    header('Location: /Silco/error.php');
    exit();
}
error_log('Session data: ' . print_r($_SESSION, true));

// Verificar autenticación
if (!isLoggedIn()) {
    error_log('Usuario no autenticado. Redirigiendo a login.');
    // Usar ruta relativa para la redirección
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $_SESSION['redirect_after_login'] = '/Silco' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_NAME']);
    header('Location: /Silco/login.php');
    exit();
}

// Verificar rol de vendedor
if (!isVendedor()) {
    error_log('Usuario no tiene rol de vendedor. ID de usuario: ' . ($_SESSION['user_id'] ?? 'no definido'));
    $_SESSION['error'] = 'No tienes permiso para acceder a esta sección.';
    header('Location: panel.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener categorías desde la base de datos (misma consulta que en index.php)
$categorias = [];
try {
    // Usar la misma consulta que en index.php (sin el filtro activa = 1)
    $stmt = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Registrar las categorías obtenidas
    error_log('Categorías obtenidas: ' . print_r($categorias, true));
    
    if (empty($categorias)) {
        error_log('No se encontraron categorías en la base de datos');
        $_SESSION['error'] = 'No se encontraron categorías disponibles. Por favor, contacte al administrador.';
    }
} catch (PDOException $e) {
    error_log('Error al obtener categorías: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar las categorías. Por favor, intente de nuevo más tarde.';
    // Usar un array vacío para evitar errores en la vista
    $categorias = [];
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Token de seguridad inválido.';
        header('Location: nuevo-producto.php');
        exit();
    }
    
    // Obtener y validar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = isset($_POST['precio']) ? (float)str_replace(['$', ','], '', $_POST['precio']) : 0;
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $peso_gramos = isset($_POST['peso_gramos']) ? (int)$_POST['peso_gramos'] : 1000;
    $largo_mm = isset($_POST['largo_mm']) ? (int)$_POST['largo_mm'] : 100;
    $ancho_mm = isset($_POST['ancho_mm']) ? (int)$_POST['ancho_mm'] : 100;
    $alto_mm = isset($_POST['alto_mm']) ? (int)$_POST['alto_mm'] : 100;
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $condicion = $_POST['condicion'] ?? 'nuevo';
    $activo = 1; // Por defecto activo
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre del producto es obligatorio.';
    } elseif (strlen($nombre) > 255) {
        $errores[] = 'El nombre no puede tener más de 255 caracteres.';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción del producto es obligatoria.';
    }
    
    if ($categoria_id <= 0) {
        $errores[] = 'Debes seleccionar una categoría válida.';
    }
    
    if ($precio <= 0) {
        $errores[] = 'El precio debe ser mayor a 0.';
    }
    
    if ($stock < 0) {
        $errores[] = 'El stock no puede ser negativo.';
    }
    
    if ($peso_gramos <= 0) {
        $errores[] = 'El peso debe ser mayor a 0 gramos.';
    }
    
    if ($largo_mm <= 0 || $ancho_mm <= 0 || $alto_mm <= 0) {
        $errores[] = 'Todas las dimensiones deben ser mayores a 0 mm.';
    }
    
    // Inicializar array para imágenes
    $imagenes = [];
    $has_valid_images = false;
    
    // Verificar si se subieron imágenes
    if (isset($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
        // Filtrar elementos vacíos del array de archivos
        $total_imagenes = 0;
        foreach ($_FILES['imagenes']['name'] as $key => $name) {
            if (!empty($name) && $_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                $total_imagenes++;
            }
        }
        
        if ($total_imagenes === 0) {
            $errores[] = 'Debes subir al menos una imagen del producto.';
        } else {
            // Usar ruta absoluta para mayor confiabilidad
            $base_upload_dir = dirname(__DIR__) . '/uploads';
            $upload_dir = $base_upload_dir . '/productos/';
            
            // Crear directorio base si no existe
            if (!file_exists($base_upload_dir)) {
                if (!@mkdir($base_upload_dir, 0755, true)) {
                    $error = error_get_last();
                    $error_message = $error['message'] ?? 'Error desconocido';
                    error_log("Error al crear directorio base: " . $error_message);
                    $errores[] = 'No se pudo crear el directorio base de subidas. Error: ' . $error_message;
                    $errores[] = 'Ruta: ' . $base_upload_dir;
                }
            }
            
            // Crear directorio de productos si no existe
            if (empty($errores) && !file_exists($upload_dir)) {
                if (!@mkdir($upload_dir, 0755, true)) {
                    $error = error_get_last();
                    $error_message = $error['message'] ?? 'Error desconocido';
                    error_log("Error al crear directorio de productos: " . $error_message);
                    $errores[] = 'No se pudo crear el directorio de subida. Error: ' . $error_message;
                    $errores[] = 'Ruta: ' . $upload_dir;
                }
            }

            // Verificar permisos del directorio
            if (empty($errores) && !is_writable($upload_dir)) {
                // Intentar cambiar los permisos
                if (!@chmod($upload_dir, 0775)) {
                    $errores[] = 'El directorio no tiene permisos de escritura. Por favor, verifica los permisos de la carpeta: ' . $upload_dir;
                    $errores[] = 'Ejecuta en la terminal: chmod -R 775 ' . $upload_dir;
                }
            }
            
            // Procesar cada imagen solo si no hay errores
            if (empty($errores)) {
                for ($i = 0; $i < count($_FILES['imagenes']['name']); $i++) {
                    if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES['imagenes']['tmp_name'][$i];
                        $name = basename($_FILES['imagenes']['name'][$i]);
                        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $nuevo_nombre = uniqid('img_') . '.' . $extension;
                        $ruta_destino = $upload_dir . $nuevo_nombre;
                        
                        // Validar tipo de archivo
                        $tipo_valido = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if (!in_array($extension, $tipo_valido)) {
                            $errores[] = 'El archivo ' . htmlspecialchars($name) . ' no es una imagen válida. Formatos aceptados: ' . implode(', ', $tipo_valido);
                            continue;
                        }
                        
                        // Validar tamaño (máx 5MB)
                        if ($_FILES['imagenes']['size'][$i] > 5 * 1024 * 1024) {
                            $errores[] = 'La imagen ' . htmlspecialchars($name) . ' es demasiado grande. El tamaño máximo permitido es 5MB.';
                            continue;
                        }
                        
                        // Validar que sea una imagen real
                        $check = getimagesize($tmp_name);
                        if ($check === false) {
                            $errores[] = 'El archivo ' . htmlspecialchars($name) . ' no es una imagen válida.';
                            continue;
                        }
                        
                        // Intentar mover el archivo
                        if (move_uploaded_file($tmp_name, $ruta_destino)) {
                            $imagenes[] = $nuevo_nombre;
                            $has_valid_images = true;
                        } else {
                            $errores[] = 'Error al subir la imagen ' . htmlspecialchars($name) . '. Inténtalo de nuevo.';
                        }
                    } else if ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $errores[] = 'Error al subir la imagen: ' . getUploadErrorMessage($_FILES['imagenes']['error'][$i]);
                    }
                }
            }
            // Removido mensaje de error duplicado
        }
    } else {
        $errores[] = 'No se recibieron archivos o el formulario no se envió correctamente.';
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        try {
            $conn->beginTransaction();
            
            // Depuración: Registrar los datos que se intentan guardar
            error_log("Intentando guardar producto con los siguientes datos:");
            error_log("Nombre: " . $nombre);
            error_log("Precio: " . $precio);
            error_log("Categoría ID: " . $categoria_id);
            error_log("Stock: " . $stock);
            error_log("Peso (g): " . $peso_gramos);
            error_log("Dimensiones (LxAxH): " . $largo_mm . "x" . $ancho_mm . "x" . $alto_mm);
            error_log("Marca: " . $marca);
            error_log("Modelo: " . $modelo);
            error_log("SKU: " . $sku);
            error_log("Condición: " . $condicion);
            
            // Insertar el producto
            try {
                $sql = "
                    INSERT INTO productos (
                        vendedor_id, nombre, descripcion, precio, categoria_id, 
                        stock, peso_gramos, largo_mm, ancho_mm, alto_mm, 
                        activo, marca, modelo, sku, condicion, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ";
                
                error_log("Consulta SQL a ejecutar: " . $sql);
                
                $stmt = $conn->prepare($sql);
                
                $params = [
                    $user_id, 
                    $nombre, 
                    $descripcion, 
                    $precio, 
                    $categoria_id, 
                    $stock,
                    $peso_gramos,
                    $largo_mm,
                    $ancho_mm,
                    $alto_mm,
                    $activo,
                    $marca,
                    $modelo,
                    $sku,
                    $condicion
                ];
                
                error_log("Parámetros: " . print_r($params, true));
                
                $result = $stmt->execute($params);
                
                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Error al ejecutar la consulta: " . print_r($errorInfo, true));
                    throw new PDOException("Error al ejecutar la consulta: " . ($errorInfo[2] ?? "Error desconocido"));
                }
                
            } catch (PDOException $e) {
                error_log("Error en la consulta SQL: " . $e->getMessage());
                error_log("Código de error: " . $e->getCode());
                error_log("Archivo: " . $e->getFile() . " en la línea " . $e->getLine());
                error_log("Consulta: " . ($sql ?? "No se pudo obtener la consulta"));
                error_log("Parámetros: " . print_r($params ?? [], true));
                throw $e;
            }
            
            $producto_id = $conn->lastInsertId();
            
            // Guardar las imágenes
            if (!empty($imagenes)) {
                $stmt = $conn->prepare("
                    INSERT INTO imagenes_producto (producto_id, imagen_url) 
                    VALUES (?, ?)
                ");
                
                foreach ($imagenes as $imagen) {
                    $stmt->execute([
                        $producto_id,
                        $imagen
                    ]);
                }
            }
            
            $conn->commit();
            
            // Verificar que el producto se haya creado correctamente
            $verificar = $conn->query("SELECT * FROM productos WHERE id = " . $producto_id);
            $producto_creado = $verificar->fetch(PDO::FETCH_ASSOC);
            
            if ($producto_creado) {
                $_SESSION['success'] = '¡Producto creado exitosamente!';
                // Redirigir a la lista de productos del vendedor
                header('Location: productos.php');
                exit();
            } else {
                throw new Exception('El producto no se pudo crear correctamente.');
            }
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = "Error al crear producto: " . $e->getMessage();
            error_log($error_message);
            error_log("Error en el archivo: " . $e->getFile() . " en la línea " . $e->getLine());
            error_log("Código de error: " . $e->getCode());
            error_log("Trace: " . $e->getTraceAsString());
            
            // Mensaje de error más detallado para el usuario
            $errores[] = 'Error al guardar el producto. Por favor, verifica los datos e inténtalo de nuevo.';
            $errores[] = 'Detalles del error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Producto - Panel de Vendedor - Silco</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet">
    <style>
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .preview-item {
            position: relative;
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .preview-item .sort-handle {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: move;
        }
        .preview-item .image-order {
            position: absolute;
            bottom: 5px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        .dropzone {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .dropzone i {
            font-size: 2.5rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .preview-container {
            margin-top: 15px;
        }
        .preview-item {
            position: relative;
            display: inline-block;
            margin: 5px;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-image {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            line-height: 18px;
            text-align: center;
            cursor: pointer;
            padding: 0;
        }
        .preview-item .remove-image:hover {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-main">
                <div class="dashboard-header">
                    <h1>Nuevo Producto</h1>
                    <div class="header-actions">
                        <a href="productos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Productos
                        </a>
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
                
                <form action="/Silco/vendedor/nuevo-producto.php" method="POST" enctype="multipart/form-data" id="productoForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Información Básica</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre del Producto *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                                        <div class="form-text">Un título claro y descriptivo ayuda a los compradores a encontrar tu artículo.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="precio" class="form-label">Precio *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0.01" class="form-control" id="precio" name="precio" required
                                                   value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>">
                                        </div>
                                        <div class="form-text">Precio de venta del producto.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción *</label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php 
                                            echo htmlspecialchars($_POST['descripcion'] ?? ''); 
                                        ?></textarea>
                                        <div class="form-text">Describe tu producto con el mayor detalle posible. Incluye características, materiales, dimensiones, etc.</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="categoria_id" class="form-label">Categoría *</label>
                                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                                            <option value="">Selecciona una categoría</option>
                                            <?php 
                                            if (!empty($categorias)): 
                                                foreach ($categorias as $categoria): 
                                                    if (!empty(trim($categoria['nombre']))): // Solo mostrar si el nombre no está vacío
                                            ?>
                                                        <option value="<?php echo $categoria['id']; ?>"
                                                            <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars(trim($categoria['nombre'])); ?>
                                                        </option>
                                            <?php 
                                                    endif;
                                                endforeach; 
                                            else: 
                                            ?>
                                                <option value="" disabled>No hay categorías disponibles</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="condicion" class="form-label">Condición *</label>
                                        <select class="form-select" id="condicion" name="condicion" required>
                                            <option value="nuevo" <?php echo (isset($_POST['condicion']) && $_POST['condicion'] === 'nuevo') ? 'selected' : ''; ?>>Nuevo</option>
                                            <option value="usado" <?php echo (isset($_POST['condicion']) && $_POST['condicion'] === 'usado') ? 'selected' : ''; ?>>Usado</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" name="marca" 
                                               value="<?php echo htmlspecialchars($_POST['marca'] ?? ''); ?>">
                                        <div class="form-text">Ejemplo: Sony, Nike, Samsung, etc.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" 
                                               value="<?php echo htmlspecialchars($_POST['modelo'] ?? ''); ?>">
                                        <div class="form-text">Número o nombre específico del modelo.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU</label>
                                        <input type="text" class="form-control" id="sku" name="sku" 
                                               value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                                        <div class="form-text">Código único de identificación del producto.</div>
                                    </div>
                                    
                                    <input type="hidden" name="activo" value="1">
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
                                <label class="form-label">Subir imágenes *</label>
                                <div class="dropzone" id="imageDropzone">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Arrastra y suelta las imágenes aquí o haz clic para seleccionar</p>
                                    <p class="small">Puedes subir hasta 10 imágenes. Formatos: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB por imagen.</p>
                                </div>
                                <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" required>
                                
                                <div id="imagePreview" class="preview-container">
                                    <!-- Las imágenes seleccionadas se mostrarán aquí -->
                                </div>
                                <input type="hidden" id="imagenes_orden" name="imagenes_orden" value="">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3>Stock</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Cantidad en Stock *</label>
                                        <input type="number" class="form-control" id="stock" name="stock" required
                                               min="0" value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="peso_gramos" class="form-label">Peso (gramos) *</label>
                                        <div class="input-group">
                                            <input type="number" min="1" class="form-control" id="peso_gramos" name="peso_gramos" required
                                                   value="<?php echo htmlspecialchars($_POST['peso_gramos'] ?? '1000'); ?>">
                                            <span class="input-group-text">g</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Dimensiones (mm) *</label>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="input-group">
                                                    <span class="input-group-text">L</span>
                                                    <input type="number" min="1" class="form-control" id="largo_mm" name="largo_mm" required
                                                           value="<?php echo htmlspecialchars($_POST['largo_mm'] ?? '100'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-group">
                                                    <span class="input-group-text">A</span>
                                                    <input type="number" min="1" class="form-control" id="ancho_mm" name="ancho_mm" required
                                                           value="<?php echo htmlspecialchars($_POST['ancho_mm'] ?? '100'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-group">
                                                    <span class="input-group-text">H</span>
                                                    <input type="number" min="1" class="form-control" id="alto_mm" name="alto_mm" required
                                                           value="<?php echo htmlspecialchars($_POST['alto_mm'] ?? '100'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="productos.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar Producto
                        </button>
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
            
            // Función para actualizar el orden de las imágenes
            function updateImageOrder() {
                const items = document.querySelectorAll('.preview-item');
                const order = [];
                
                items.forEach((item, index) => {
                    const filename = item.getAttribute('data-filename');
                    if (filename) {
                        order.push(filename);
                        // Actualizar número de orden
                        const orderBadge = item.querySelector('.image-order');
                        if (orderBadge) {
                            orderBadge.textContent = index + 1;
                        }
                    }
                });
                
                // Actualizar el campo oculto con el orden de las imágenes
                document.getElementById('imagenes_orden').value = order.join(',');
            }
            
            // Inicializar Sortable para el contenedor de previsualización
            const previewContainer = document.getElementById('imagePreview');
            if (previewContainer) {
                const sortable = new Sortable(previewContainer, {
                    animation: 150,
                    onEnd: updateImageOrder
                });
            }
            
            // Configurar el dropzone
            $('#imageDropzone').on({
                'dragover dragenter': function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).addClass('bg-light');
                },
                'dragleave drop': function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $(this).removeClass('bg-light');
                },
                'drop': function(e) {
                    const dt = e.originalEvent.dataTransfer;
                    const files = dt.files;
                    handleFiles(files);
                },
                'click': function(e) {
                    e.preventDefault();
                    $('#imagenes').click();
                }
            });

            // Manejar cambio en el input de archivo
            $('#imagenes').on('change', function(e) {
                if (this.files && this.files.length > 0) {
                    handleFiles(this.files);
                }
            });
            
            // Función para manejar archivos seleccionados
            function handleFiles(files) {
                if (!files || files.length === 0) return;
                
                // Verificar límite de archivos
                const availableSlots = maxFiles - imageFiles.length;
                if (files.length > availableSlots) {
                    alert(`Solo puedes subir ${availableSlots} imágenes más. El límite es de ${maxFiles} imágenes.`);
                    return;
                }
                
                // Convertir FileList a Array
                const fileArray = Array.from(files);
                
                // Procesar cada archivo
                Array.from(files).forEach(file => {
                    // Verificar si es una imagen
                    if (!file.type.match('image.*')) {
                        alert(`El archivo ${file.name} no es una imagen válida.`);
                        return;
                    }
                    
                    // Verificar tamaño (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`El archivo ${file.name} es demasiado grande. Tamaño máximo: 5MB`);
                        return;
                    }
                    
                    // Agregar a la lista de archivos
                    imageFiles.push(file);
                    
                    // Mostrar previsualización
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        previewItem.setAttribute('data-filename', file.name);
                        
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Vista previa">
                            <button type="button" class="remove-image">&times;</button>
                            <div class="sort-handle"><i class="fas fa-arrows-alt"></i></div>
                            <div class="image-order">${imageFiles.length}</div>
                        `;
                        
                        // Agregar manejador de eventos para el botón de eliminar
                        previewItem.querySelector('.remove-image').addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            removeImage(file.name);
                        });
                        
                        document.getElementById('imagePreview').appendChild(previewItem);
                        updateFileInput();
                        updateImageOrder();
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
            
            // Función para eliminar una imagen de la vista previa
            function removeImage(filename) {
                // Eliminar de la lista de archivos
                imageFiles = imageFiles.filter(file => file.name !== filename);
                
                // Eliminar la previsualización
                const previewItem = document.querySelector(`.preview-item[data-filename="${filename}"]`);
                if (previewItem) {
                    previewItem.remove();
                }
                
                // Actualizar el input de archivo
                updateFileInput();
                updateImageOrder();
            }
            
            // Función auxiliar para prevenir comportamientos por defecto
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Procesar archivos seleccionados
            function handleFiles(files) {
                if (files.length === 0) return;
                
                // Verificar límite de archivos
                if (imageFiles.length + files.length > maxFiles) {
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
                        orderBadge.textContent = imageFiles.length;
                        
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
            
            // Eliminar imagen
            function removeImage(filename) {
                imageFiles = imageFiles.filter(file => file.name !== filename);
                
                // Eliminar la previsualización
                const previewItems = document.querySelectorAll('.preview-item');
                previewItems.forEach(item => {
                    if (item.getAttribute('data-filename') === filename) {
                        item.remove();
                    }
                });
                
                // Actualizar el input de archivo
                updateFileInput();
                updateImageOrder();
            }
            
            // Actualizar el input de archivo para el formulario
            function updateFileInput() {
                const dataTransfer = new DataTransfer();
                const order = [];
                
                // Agregar archivos al DataTransfer
                imageFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                
                // Actualizar el input de archivo
                const fileInput = document.getElementById('imagenes');
                fileInput.files = dataTransfer.files;
                
                // Actualizar el orden de las imágenes
                const previewItems = document.querySelectorAll('.preview-item');
                previewItems.forEach((item, index) => {
                    const filename = item.getAttribute('data-filename');
                    if (filename) {
                        order.push(filename);
                    }
                    
                    // Actualizar número de orden
                    const orderBadge = item.querySelector('.image-order');
                    if (orderBadge) {
                        orderBadge.textContent = index + 1;
                    }
                });
                
                // Actualizar el campo oculto con el orden de las imágenes
                document.getElementById('imagenes_orden').value = order.join(',');
                
                // Actualizar el contador de archivos
                const fileCount = imageFiles.length;
                const dropzoneText = $('.dropzone p:first');
                if (fileCount > 0) {
                    dropzoneText.text(`${fileCount} archivo(s) seleccionado(s)`);
                } else {
                    dropzoneText.text('Arrastra y suelta las imágenes aquí o haz clic para seleccionar');
                }
            }
            
            // Validar formulario antes de enviar
            $('#productoForm').on('submit', function(e) {
                if (imageFiles.length === 0) {
                    e.preventDefault();
                    alert('Por favor, sube al menos una imagen del producto.');
                    return false;
                }
                
                
                return true;
            });
        });
    </script>
</body>
</html>
