<?php
// Iniciar la sesión primero
require_once __DIR__ . '/../config.php';

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

// Categorías fijas para el formulario
$categorias = [
    ['id' => 1, 'nombre' => 'Electrodomésticos'],
    ['id' => 2, 'nombre' => 'Moda'],
    ['id' => 3, 'nombre' => 'Salud y Belleza'],
    ['id' => 4, 'nombre' => 'Herramientas'],
    ['id' => 5, 'nombre' => 'Cosas para el Hogar']
];

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
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $condicion = $_POST['condicion'] ?? 'nuevo';
    $activo = 1; // Por defecto activo
    
    // Validaciones
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre del producto es obligatorio.';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción del producto es obligatoria.';
    }
    
    if ($categoria_id <= 0) {
        $errores[] = 'Debes seleccionar una categoría válida.';
    }
    
    if ($stock < 0) {
        $errores[] = 'El stock no puede ser negativo.';
    }
    
    // Procesar imágenes
    $imagenes = [];
    if (isset($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
        $total_imagenes = count($_FILES['imagenes']['name']);
        $upload_dir = '../uploads/productos/';
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        for ($i = 0; $i < $total_imagenes; $i++) {
            if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['imagenes']['tmp_name'][$i];
                $name = basename($_FILES['imagenes']['name'][$i]);
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $nuevo_nombre = uniqid('img_') . '.' . $extension;
                $ruta_destino = $upload_dir . $nuevo_nombre;
                
                // Validar tipo de archivo
                $tipo_valido = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($extension, $tipo_valido)) {
                    $errores[] = 'El archivo ' . $name . ' no es una imagen válida. Formatos aceptados: ' . implode(', ', $tipo_valido);
                    continue;
                }
                
                // Validar tamaño (máx 5MB)
                if ($_FILES['imagenes']['size'][$i] > 5 * 1024 * 1024) {
                    $errores[] = 'La imagen ' . $name . ' es demasiado grande. El tamaño máximo permitido es 5MB.';
                    continue;
                }
                
                // Mover el archivo
                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $imagenes[] = str_replace('../', '', $ruta_destino);
                } else {
                    $errores[] = 'Error al subir la imagen ' . $name;
                }
            }
        }
    } else {
        $errores[] = 'Debes subir al menos una imagen del producto.';
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        try {
            $conn->beginTransaction();
            
            // Insertar el producto
            $stmt = $conn->prepare("
                INSERT INTO productos (
                    nombre, descripcion, categoria_id, 
                    stock, condicion, activo, vendedor_id, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $nombre, 
                $descripcion, 
                $categoria_id, 
                $stock, 
                $condicion, 
                $activo, 
                $user_id
            ]);
            
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
            
            $_SESSION['success'] = '¡Producto creado exitosamente!';
            // Redirigir a la lista de productos del vendedor
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/productos.php');
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Error al crear producto: " . $e->getMessage());
            $errores[] = 'Error al guardar el producto. Por favor, inténtalo de nuevo.';
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
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .dropzone:hover {
            border-color: #999;
        }
        .dropzone i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .dropzone p {
            margin: 0;
            color: #6c757d;
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
                
                <form action="nuevo-producto.php" method="POST" enctype="multipart/form-data" id="productoForm">
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
                                            <?php foreach ($categorias as $categoria): ?>
                                                <option value="<?php echo $categoria['id']; ?>"
                                                    <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="condicion" class="form-label">Condición *</label>
                                        <select class="form-select" id="condicion" name="condicion" required>
                                            <option value="nuevo" <?php echo (isset($_POST['condicion']) && $_POST['condicion'] === 'nuevo') ? 'selected' : ''; ?>>Nuevo</option>
                                            <option value="usado" <?php echo (isset($_POST['condicion']) && $_POST['condicion'] === 'usado') ? 'selected' : ''; ?>>Usado</option>
                                        </select>
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
                                <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*" style="display: none;">
                                
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Cantidad en Stock *</label>
                                        <input type="number" class="form-control" id="stock" name="stock" required
                                               min="0" value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="productos.php" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-times me-1"></i> Cancelar
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
                imageFiles.forEach(file => {
                    dataTransfer.items.add(file);
                });
                
                const fileInput = document.getElementById('imagenes');
                fileInput.files = dataTransfer.files;
            }
            
            // Actualizar el orden de las imágenes
            function updateImageOrder() {
                const previewItems = document.querySelectorAll('.preview-item');
                const order = [];
                
                previewItems.forEach((item, index) => {
                    const filename = item.getAttribute('data-filename');
                    order.push(filename);
                    
                    // Actualizar número de orden
                    const orderBadge = item.querySelector('.image-order');
                    if (orderBadge) {
                        orderBadge.textContent = index + 1;
                    }
                });
                
                document.getElementById('imagenes_orden').value = order.join(',');
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
