<?php
/**
 * Formulario de Producto - Panel de Vendedor
 * 
 * Este archivo maneja la creación de nuevos productos por parte de los vendedores.
 * Incluye validación, subida de imágenes y manejo de sesiones.
 */

// Incluir configuración primero para asegurar que la sesión se configure correctamente
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../helpers/UploadHelper.php';

// Iniciar sesión si no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Verificar autenticación
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Verificar si el usuario es vendedor
if (!isVendedor()) {
    header('Location: /acceso-denegado.php');
    exit();
}

// Inicializar conexión a la base de datos
$db = Database::getInstance();
$conn = $db->getConnection();

// Función para generar un slug a partir de un texto
function generarSlug($texto) {
    $texto = preg_replace('~[^\pL\d]+~u', '-', $texto);
    $texto = preg_replace('~[^-\w]+~', '', $texto);
    $texto = strtolower(trim($texto, '-'));
    $texto = preg_replace('~-+~', '-', $texto);
    return $texto . '-' . time();
}

// Obtener categorías para el select
$categorias = [];
try {
    $stmt = $conn->query("SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar categorías: " . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar las categorías. Por favor, intente nuevamente.';
}

// Procesar el formulario cuando se envía
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $precio_descuento = !empty($_POST['precio_descuento']) ? (float)$_POST['precio_descuento'] : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $estado_stock = in_array($_POST['estado_stock'] ?? '', ['disponible', 'ultimo', 'agotado']) 
        ? $_POST['estado_stock'] 
        : 'disponible';
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = 'El nombre del producto es obligatorio';
    } elseif (strlen($nombre) > 255) {
        $errores[] = 'El nombre no puede tener más de 255 caracteres';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es obligatoria';
    }
    
    if ($precio <= 0) {
        $errores[] = 'El precio debe ser mayor a 0';
    }
    
    if ($precio_descuento !== null && $precio_descuento >= $precio) {
        $errores[] = 'El precio de descuento debe ser menor al precio normal';
    }
    
    if ($stock < 0) {
        $errores[] = 'El stock no puede ser negativo';
    }
    
    if (empty($categoria_id)) {
        $errores[] = 'Debe seleccionar una categoría';
    }
    
    // Procesar imágenes si no hay errores
    $imagenes = [];
    if (empty($errores) && !empty($_FILES['imagenes']['name'][0])) {
        $uploadHelper = new UploadHelper('productos');
        
        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['imagenes']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['imagenes']['name'][$key],
                    'type' => $_FILES['imagenes']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['imagenes']['error'][$key],
                    'size' => $_FILES['imagenes']['size'][$key]
                ];
                
                try {
                    $imagenes[] = $uploadHelper->upload($file);
                } catch (Exception $e) {
                    $errores[] = 'Error al subir la imagen ' . ($key + 1) . ': ' . $e->getMessage();
                }
            }
        }
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        try {
            $conn->beginTransaction();
            
            // Generar slug
            $slug = generarSlug($nombre);
            
            // Insertar producto en la base de datos
            $stmt = $conn->prepare("
                INSERT INTO productos (
                    nombre, slug, descripcion, precio, precio_descuento, 
                    stock, estado_stock, categoria_id, vendedor_id, fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $nombre,
                $slug,
                $descripcion,
                $precio,
                $precio_descuento,
                $stock,
                $estado_stock,
                $categoria_id,
                $_SESSION['user_id']
            ]);
            
            $producto_id = $conn->lastInsertId();
            
            // Guardar imágenes si hay
            if (!empty($imagenes)) {
                $stmt = $conn->prepare("
                    INSERT INTO producto_imagenes (producto_id, imagen, orden) 
                    VALUES (?, ?, ?)
                ");
                
                foreach ($imagenes as $orden => $imagen) {
                    $stmt->execute([$producto_id, $imagen, $orden + 1]);
                }
            }
            
            $conn->commit();
            
            // Redirigir al panel con mensaje de éxito
            $_SESSION['success'] = 'Producto agregado correctamente';
            header('Location: panel.php');
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Error al guardar el producto: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar el producto. Por favor, intente nuevamente.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Panel de Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            z-index: 10;
        }
        
        .img-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        
        .was-validated .form-control:invalid ~ .invalid-feedback,
        .was-validated .form-control:invalid ~ .invalid-tooltip,
        .form-control.is-invalid ~ .invalid-feedback,
        .form-control.is-invalid ~ .invalid-tooltip {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Agregar Nuevo Producto</h4>
                            <a href="panel.php" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left me-1"></i> Volver al Panel
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="productoForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php 
                                    echo htmlspecialchars($_POST['descripcion'] ?? ''); 
                                ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="0.01" class="form-control" 
                                               id="precio" name="precio" required
                                               value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="precio_descuento" class="form-label">Precio de descuento (opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="0" class="form-control" 
                                               id="precio_descuento" name="precio_descuento"
                                               value="<?php echo htmlspecialchars($_POST['precio_descuento'] ?? ''); ?>">
                                    </div>
                                    <div class="form-text">Dejar en blanco si no hay descuento</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="stock" class="form-label">Stock disponible <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control" id="stock" name="stock" required
                                           value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="estado_stock" class="form-label">Estado del stock</label>
                                    <select class="form-select" id="estado_stock" name="estado_stock">
                                        <option value="disponible" <?php echo ($_POST['estado_stock'] ?? 'disponible') === 'disponible' ? 'selected' : ''; ?>>Disponible</option>
                                        <option value="ultimo" <?php echo ($_POST['estado_stock'] ?? '') === 'ultimo' ? 'selected' : ''; ?>>Últimas unidades</option>
                                        <option value="agotado" <?php echo ($_POST['estado_stock'] ?? '') === 'agotado' ? 'selected' : ''; ?>>Agotado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>"
                                            <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Imágenes del producto</label>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" id="imagenes" name="imagenes[]" multiple 
                                                   accept="image/*">
                                            <div class="form-text">Puedes seleccionar varias imágenes. Formatos: JPG, PNG, GIF, WEBP</div>
                                        </div>
                                        
                                        <div class="preview-container">
                                            <h6 class="mt-3 mb-2">Vista previa:</h6>
                                            <div class="img-container" id="imagePreview">
                                                <!-- Las imágenes seleccionadas aparecerán aquí -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Guardar Producto
                                </button>
                                <a href="panel.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vista previa de imágenes
        document.getElementById('imagenes').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = ''; // Limpiar previsualizaciones anteriores
            
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'img-preview-container';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-img';
                        
                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'remove-img';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.onclick = function() {
                            // Eliminar la imagen del input file
                            const dt = new DataTransfer();
                            const input = document.getElementById('imagenes');
                            const { files } = input;
                            
                            for (let j = 0; j < files.length; j++) {
                                if (j !== i) {
                                    dt.items.add(files[j]);
                                }
                            }
                            
                            input.files = dt.files;
                            imgContainer.remove();
                        };
                        
                        imgContainer.appendChild(img);
                        imgContainer.appendChild(removeBtn);
                        preview.appendChild(imgContainer);
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
        
        // Validación de formulario
        document.getElementById('productoForm').addEventListener('submit', function(e) {
            const precio = parseFloat(document.getElementById('precio').value);
            const precioDescuento = document.getElementById('precio_descuento').value;
            
            if (precioDescuento && parseFloat(precioDescuento) >= precio) {
                e.preventDefault();
                alert('El precio de descuento debe ser menor al precio normal');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h5 class="card-title mb-0">Imágenes del producto</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="imagenes" class="form-label">Subir imágenes</label>
                                                <input class="form-control" type="file" id="imagenes" name="imagenes[]" multiple 
                                                       accept="image/*">
                                                <div class="form-text">Puedes seleccionar varias imágenes. Formatos: JPG, PNG, GIF, WEBP</div>
                                            </div>
                                            
                                            <div class="preview-container">
                                                <h6 class="mt-3 mb-2">Vista previa:</h6>
                                                <div class="img-container" id="imagePreview">
                                                    <!-- Las imágenes seleccionadas aparecerán aquí -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i> Guardar Producto
                                        </button>
                                        <a href="panel.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vista previa de imágenes
        document.getElementById('imagenes').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = ''; // Limpiar previsualizaciones anteriores
            
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'img-preview-container';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-img';
                        
                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'remove-img';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.onclick = function() {
                            // Eliminar la imagen del input file
                            const dt = new DataTransfer();
                            const input = document.getElementById('imagenes');
                            const { files } = input;
                            
                            for (let j = 0; j < files.length; j++) {
                                if (j !== i) {
                                    dt.items.add(files[j]);
                                }
                            }
                            
                            input.files = dt.files;
                            imgContainer.remove();
                        };
                        
                        imgContainer.appendChild(img);
                        imgContainer.appendChild(removeBtn);
                        preview.appendChild(imgContainer);
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
        
        // Validación de formulario
        document.getElementById('productoForm').addEventListener('submit', function(e) {
            const precio = parseFloat(document.getElementById('precio').value);
            const precioDescuento = document.getElementById('precio_descuento').value;
            
            if (precioDescuento && parseFloat(precioDescuento) >= precio) {
                e.preventDefault();
                alert('El precio de descuento debe ser menor al precio normal');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
