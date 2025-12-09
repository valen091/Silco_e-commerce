<?php
require_once '../includes/functions.php';

// Verificar autenticación y rol de vendedor
if (!isLoggedIn() || !isVendedor()) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Manejar eliminación de producto
if (isset($_POST['eliminar_producto'])) {
    $producto_id = (int)$_POST['producto_id'];
    
    try {
        // Verificar que el producto pertenece al vendedor
        $stmt = $conn->prepare("SELECT id FROM productos WHERE id = ? AND vendedor_id = ?");
        $stmt->execute([$producto_id, $user_id]);
        
        if ($stmt->fetch()) {
            // Eliminar imágenes del producto primero
            $stmt = $conn->prepare("DELETE FROM imagenes_producto WHERE producto_id = ?");
            $stmt->execute([$producto_id]);
            
            // Luego eliminar el producto
            $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$producto_id]);
            
            $_SESSION['success'] = 'Producto eliminado correctamente.';
        } else {
            $_SESSION['error'] = 'No tienes permiso para eliminar este producto.';
        }
    } catch (PDOException $e) {
        error_log("Error al eliminar producto: " . $e->getMessage());
        $_SESSION['error'] = 'Error al eliminar el producto. Por favor, inténtalo de nuevo.';
    }
    
    header('Location: productos.php');
    exit();
}

// Obtener parámetros de búsqueda y filtrado
$busqueda = $_GET['buscar'] ?? '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$stock = $_GET['stock'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta base
$query = "SELECT p.*, c.nombre as categoria_nombre, 
          (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id ORDER BY orden LIMIT 1) as imagen_principal
          FROM productos p 
          LEFT JOIN categorias c ON p.categoria_id = c.id 
          WHERE p.vendedor_id = ?";
$params = [$user_id];

// Aplicar filtros
if (!empty($busqueda)) {
    $query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if ($categoria) {
    $query .= " AND p.categoria_id = ?";
    $params[] = $categoria;
}

if ($stock === 'bajo') {
    $query .= " AND p.stock <= 5 AND p.stock > 0";
} elseif ($stock === 'agotado') {
    $query .= " AND p.stock = 0";
} elseif ($stock === 'en_stock') {
    $query .= " AND p.stock > 5";
}

// Ordenación
$orden = $_GET['orden'] ?? 'recientes';
switch ($orden) {
    case 'antiguos':
        $query .= " ORDER BY p.fecha_creacion ASC";
        break;
    case 'nombre_asc':
        $query .= " ORDER BY p.nombre ASC";
        break;
    case 'nombre_desc':
        $query .= " ORDER BY p.nombre DESC";
        break;
    case 'precio_asc':
        $query .= " ORDER BY p.precio ASC";
        break;
    case 'precio_desc':
        $query .= " ORDER BY p.precio DESC";
        break;
    case 'stock_asc':
        $query .= " ORDER BY p.stock ASC";
        break;
    case 'stock_desc':
        $query .= " ORDER BY p.stock DESC";
        break;
    default: // recientes
        $query .= " ORDER BY p.fecha_creacion DESC";
}

// Contar total de productos para paginación
$count_query = "SELECT COUNT(*) FROM productos p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.vendedor_id = ?";

$count_params = [$user_id];

if (!empty($busqueda)) {
    $count_query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $count_params[] = "%$buscar%";
    $count_params[] = "%$buscar%";
}

if ($categoria) {
    $count_query .= " AND p.categoria_id = ?";
    $count_params[] = $categoria;
}

if ($stock === 'bajo') {
    $count_query .= " AND p.stock <= 5 AND p.stock > 0";
} elseif ($stock === 'agotado') {
    $count_query .= " AND p.stock = 0";
} elseif ($stock === 'en_stock') {
    $count_query .= " AND p.stock > 5";
}

$stmt = $conn->prepare($count_query);
$stmt->execute($count_params);
$total_productos = $stmt->fetchColumn();
$total_paginas = max(1, ceil($total_productos / $por_pagina));

// Aplicar paginación
$query .= " LIMIT ? OFFSET ?";
$params[] = $por_pagina;
$params[] = $offset;

// Ejecutar consulta principal
$stmt = $conn->prepare($query);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Obtener categorías para el filtro
try {
    $categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
    error_log("Error al obtener categorías: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Productos - Panel de Vendedor - Silco</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-content">
            <?php include 'includes/sidebar.php'; ?>
            
            <div class="dashboard-main">
                <div class="dashboard-header">
                    <h1>Mis Productos</h1>
                    <div class="header-actions">
                        <a href="nuevo-producto.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Filtrar Productos</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="buscar" class="form-label">Buscar</label>
                                <input type="text" class="form-control" id="buscar" name="buscar" 
                                       value="<?php echo htmlspecialchars($busqueda); ?>" 
                                       placeholder="Nombre o descripción">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($categoria == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="stock" class="form-label">Estado de stock</label>
                                <select class="form-select" id="stock" name="stock">
                                    <option value="">Todos</option>
                                    <option value="en_stock" <?php echo ($stock === 'en_stock') ? 'selected' : ''; ?>>En stock</option>
                                    <option value="bajo" <?php echo ($stock === 'bajo') ? 'selected' : ''; ?>>Bajo stock</option>
                                    <option value="agotado" <?php echo ($stock === 'agotado') ? 'selected' : ''; ?>>Agotados</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de productos -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Lista de Productos</h3>
                        <div class="d-flex align-items-center">
                            <span class="me-2">Ordenar por:</span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="ordenDropdown" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php 
                                    $opciones_orden = [
                                        'recientes' => 'Más recientes primero',
                                        'antiguos' => 'Más antiguos primero',
                                        'nombre_asc' => 'Nombre (A-Z)',
                                        'nombre_desc' => 'Nombre (Z-A)',
                                        'precio_asc' => 'Precio (menor a mayor)',
                                        'precio_desc' => 'Precio (mayor a menor)',
                                        'stock_asc' => 'Stock (menor a mayor)',
                                        'stock_desc' => 'Stock (mayor a menor)'
                                    ];
                                    echo $opciones_orden[$orden] ?? 'Seleccionar';
                                    ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="ordenDropdown">
                                    <?php foreach ($opciones_orden as $valor => $texto): ?>
                                        <li>
                                            <a class="dropdown-item <?php echo ($orden === $valor) ? 'active' : ''; ?>" 
                                               href="?<?php echo http_build_query(array_merge($_GET, ['orden' => $valor, 'pagina' => 1])); ?>">
                                                <?php echo $texto; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($productos)): ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-box-open fa-4x text-muted"></i>
                                </div>
                                <h4>No se encontraron productos</h4>
                                <p class="text-muted">
                                    <?php 
                                    if ($busqueda || $categoria || $stock) {
                                        echo 'Intenta con otros criterios de búsqueda o ';
                                    }
                                    ?>
                                    <a href="nuevo-producto.php">agrega tu primer producto</a>.
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Categoría</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $producto): 
                                            $estado = '';
                                            $estado_clase = '';
                                            
                                            if ($producto['stock'] == 0) {
                                                $estado = 'Agotado';
                                                $estado_clase = 'danger';
                                            } elseif ($producto['stock'] <= 5) {
                                                $estado = 'Bajo stock';
                                                $estado_clase = 'warning';
                                            } else {
                                                $estado = 'En stock';
                                                $estado_clase = 'success';
                                            }
                                            
                                            $precio_oferta = !empty($producto['precio_oferta']) && $producto['precio_oferta'] < $producto['precio'] 
                                                          ? $producto['precio_oferta'] 
                                                          : null;
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($producto['imagen_principal'])): ?>
                                                            <img src="<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                                                 class="product-thumb me-3" 
                                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                                        <?php else: ?>
                                                            <div class="product-thumb bg-light d-flex align-items-center justify-content-center me-3">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                                            <small class="text-muted">ID: <?php echo $producto['id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                                <td>
                                                    <?php if ($precio_oferta): ?>
                                                        <div class="text-danger fw-bold">$<?php echo number_format($precio_oferta, 2); ?></div>
                                                        <div class="text-decoration-line-through text-muted small">$<?php echo number_format($producto['precio'], 2); ?></div>
                                                    <?php else: ?>
                                                        $<?php echo number_format($producto['precio'], 2); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $producto['stock'] > 0 ? 'success' : 'danger'; ?>">
                                                        <?php echo $producto['stock']; ?> unidades
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $estado_clase; ?>">
                                                        <?php echo $estado; ?>
                                                    </span>
                                                    <?php if (!$producto['activo']): ?>
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <!-- Edit Button -->
                                                        <a href="editar-producto.php?id=<?php echo $producto['id']; ?>" 
                                                           class="btn btn-sm btn-primary d-flex align-items-center" 
                                                           title="Editar producto">
                                                            <i class="fas fa-edit me-1"></i> Editar
                                                        </a>
                                                        
                                                        <!-- View Button -->
                                                        <a href="../producto.php?id=<?php echo $producto['id']; ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-outline-primary d-flex align-items-center" 
                                                           title="Ver en tienda">
                                                            <i class="fas fa-eye me-1"></i> Ver
                                                        </a>
                                                        
                                                        <!-- Delete Button -->
                                                        <form action="productos.php" method="POST" class="d-inline" 
                                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.');">
                                                            <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                                            <button type="submit" name="eliminar_producto" class="btn btn-sm btn-outline-danger d-flex align-items-center" 
                                                                    title="Eliminar producto">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($total_paginas > 1): ?>
                                <nav aria-label="Navegación de productos" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" 
                                               href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" 
                                               aria-label="Anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php 
                                        $inicio = max(1, min($pagina - 2, $total_paginas - 4));
                                        $fin = min($inicio + 4, $total_paginas);
                                        
                                        if ($inicio > 1) {
                                            echo '<li class="page-item">
                                                    <a class="page-link" href="?' . http_build_query(array_merge($_GET, ['pagina' => 1])) . '">1</a>
                                                  </li>';
                                            if ($inicio > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }
                                        
                                        for ($i = $inicio; $i <= $fin; $i++) {
                                            echo '<li class="page-item ' . ($pagina === $i ? 'active' : '') . '">
                                                    <a class="page-link" href="?'. http_build_query(array_merge($_GET, ['pagina' => $i])) .'">' . $i . '</a>
                                                  </li>';
                                        }
                                        
                                        if ($fin < $total_paginas) {
                                            if ($fin < $total_paginas - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item">
                                                    <a class="page-link" href="?'. http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) .'">' . $total_paginas . '</a>
                                                  </li>';
                                        }
                                        ?>
                                        
                                        <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" 
                                               href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" 
                                               aria-label="Siguiente">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Floating Action Button -->
    <button id="addProductFab" class="btn btn-primary btn-fab" title="Agregar Producto">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Sliding Panel -->
    <div class="sliding-panel" id="addProductPanel">
        <div class="sliding-panel-header">
            <h4>Agregar Nuevo Producto</h4>
            <button type="button" class="btn-close" id="closePanel"></button>
        </div>
        <div class="sliding-panel-body" id="productFormContainer">
            <!-- Form will be loaded here via AJAX -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando formulario...</p>
            </div>
        </div>
    </div>

    <style>
        /* Floating Action Button */
        .btn-fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        /* Sliding Panel */
        .sliding-panel {
            position: fixed;
            top: 0;
            right: -500px;
            width: 500px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            transition: right 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        }

        .sliding-panel.open {
            right: 0;
        }

        .sliding-panel-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }

        .sliding-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Overlay */
        .panel-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out;
        }

        .panel-overlay.visible {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 576px) {
            .sliding-panel {
                width: 100%;
                right: -100%;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        $(document).ready(function() {
            const panel = $('#addProductPanel');
            const overlay = $('<div class="panel-overlay"></div>').appendTo('body');
            const fab = $('#addProductFab');
            const closeBtn = $('#closePanel');
            const formContainer = $('#productFormContainer');

            // Load form via AJAX when panel is opened
            function loadProductForm() {
                formContainer.html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando formulario...</p>
                    </div>
                `);

                $.get('producto-form.php', function(data) {
                    formContainer.html(data);
                    // Reinitialize any necessary plugins or scripts
                    if (typeof initFormValidation === 'function') {
                        initFormValidation();
                    }
                }).fail(function() {
                    formContainer.html(`
                        <div class="alert alert-danger">
                            Error al cargar el formulario. Por favor, recarga la página o inténtalo de nuevo más tarde.
                        </div>
                    `);
                });
            }

            // Toggle panel
            function togglePanel() {
                panel.toggleClass('open');
                overlay.toggleClass('visible');
                
                if (panel.hasClass('open') && formContainer.find('form').length === 0) {
                    loadProductForm();
                }
            }

            // Event listeners
            fab.on('click', togglePanel);
            closeBtn.on('click', togglePanel);
            overlay.on('click', togglePanel);

            // Close panel when pressing Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && panel.hasClass('open')) {
                    togglePanel();
                }
            });

            // Handle form submission
            $(document).on('submit', '#productoForm', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                $.ajax({
                    url: 'nuevo-producto.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Show success message
                        const successMsg = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Producto creado exitosamente. Redirigiendo...
                            </div>
                        `;
                        formContainer.html(successMsg);
                        
                        // Reload the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error al guardar el producto. Por favor, inténtalo de nuevo.';
                        
                        // Try to parse error response
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                errorMsg = response.error;
                            } else if (response.errors) {
                                errorMsg = '<ul class="mb-0">' + 
                                    response.errors.map(err => `<li>${err}</li>`).join('') + 
                                    '</ul>';
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                        
                        formContainer.prepend(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${errorMsg}
                            </div>
                        `);
                        
                        // Scroll to top to show error
                        formContainer.scrollTop(0);
                    }
                });
            });
        });
    </script>
    
    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        <input type="hidden" name="producto_id" id="producto_id">
                        <input type="hidden" name="eliminar_producto" value="1">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Manejar la apertura del modal de eliminación
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            const deleteForm = document.getElementById('deleteForm');
            const productoIdInput = document.getElementById('producto_id');
            
            // Configurar el modal de Bootstrap
            const modal = new bootstrap.Modal(deleteModal);
            
            // Manejar clic en el botón de eliminar
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const productoId = this.getAttribute('data-id');
                    productoIdInput.value = productoId;
                    modal.show();
                });
            });
            
            // Manejar envío del formulario de eliminación
            deleteForm.addEventListener('submit', function(e) {
                // No es necesario hacer nada aquí, el formulario se enviará normalmente
                // y el servidor manejará la eliminación
            });
        });
    </script>
</body>
</html>
