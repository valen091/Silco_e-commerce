<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Session.php';

// Inicializar la sesión
$session = Session::getInstance();

// Verificar si se proporcionó un ID de categoría
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

$categoria_id = (int)$_GET['id'];
$db = Database::getInstance()->getConnection();

// Obtener información de la categoría
$query = "SELECT * FROM categorias WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

// Asegurarse de que APP_URL esté definido
if (!defined('APP_URL')) {
    define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/Silco');
}

// Obtener productos de la categoría
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Filtros y ordenamiento
$filtros = [
    'precio_min' => isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : null,
    'precio_max' => isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : null,
    'orden' => $_GET['orden'] ?? 'recientes',
    'busqueda' => $_GET['q'] ?? ''
];

// Obtener rango de precios para la categoría
$precio_query = "SELECT 
    MIN(precio) as min_precio, 
    MAX(precio) as max_precio 
FROM productos 
WHERE categoria_id = ? AND activo = 1";
$precio_stmt = $db->prepare($precio_query);
$precio_stmt->execute([$categoria_id]);
$rango_precios = $precio_stmt->fetch(PDO::FETCH_ASSOC);

// Valores predeterminados si no hay productos
$precio_min = $rango_precios['min_precio'] ?? 0;
$precio_max = $rango_precios['max_precio'] ?? 1000;

// Construir la consulta base
$query = "SELECT 
    p.id, 
    p.nombre, 
    p.descripcion, 
    p.precio, 
    p.precio_oferta,
    p.imagen_principal,
    p.fecha_creacion,
    cat.nombre as categoria_nombre,
    AVG(COALESCE(c.puntuacion, 0)) as puntuacion_promedio,
    COUNT(DISTINCT c.id) as total_resenas
FROM productos p
LEFT JOIN comentarios c ON p.id = c.producto_id
LEFT JOIN categorias cat ON p.categoria_id = cat.id
WHERE p.categoria_id = ? AND p.activo = 1";

$params = [$categoria_id];

// Aplicar búsqueda
if (!empty($filtros['busqueda'])) {
    $query .= " AND (p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda)";
    $params[':busqueda'] = "%{$filtros['busqueda']}%";
}

// Aplicar filtros de precio
if ($filtros['precio_min'] !== null) {
    $query .= " AND p.precio >= :precio_min";
    $params[':precio_min'] = $filtros['precio_min'];
    $params[] = $filtros['precio_min'];
}

if ($filtros['precio_max'] !== null) {
    $query .= " AND (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) <= ?";
    $params[] = $filtros['precio_max'];
}

// Ordenar
switch ($filtros['orden']) {
    case 'precio_asc':
        $query .= " ORDER BY (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) ASC";
        break;
    case 'precio_desc':
        $query .= " ORDER BY (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) DESC";
        break;
    case 'nombre_asc':
        $query .= " ORDER BY p.nombre ASC";
        break;
    case 'nombre_desc':
        $query .= " ORDER BY p.nombre DESC";
        break;
    case 'recientes':
    default:
        $query .= " ORDER BY p.fecha_creacion DESC";
        break;
}

// Primero obtenemos el conteo total de productos
$count_query = "SELECT COUNT(DISTINCT p.id) as total 
                FROM productos p 
                LEFT JOIN comentarios c ON p.id = c.producto_id 
                WHERE p.categoria_id = ? AND p.activo = 1";
                
// Aplicar filtros de búsqueda si existen
if (!empty($filtros['busqueda'])) {
    $count_query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
}

// Aplicar filtros de precio
if ($filtros['precio_min'] !== null) {
    $count_query .= " AND p.precio >= ?";
}

if ($filtros['precio_max'] !== null) {
    $count_query .= " AND (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) <= ?";
}

$stmt = $db->prepare($count_query);

// Construir parámetros para la consulta de conteo
$count_params = [$categoria_id];

if (!empty($filtros['busqueda'])) {
    $search_param = "%{$filtros['busqueda']}%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

if ($filtros['precio_min'] !== null) {
    $count_params[] = $filtros['precio_min'];
}

if ($filtros['precio_max'] !== null) {
    $count_params[] = $filtros['precio_max'];
}

$stmt->execute($count_params);
$total_productos = $stmt->fetch()['total'];
$total_paginas = ceil($total_productos / $per_page);

// Añadir agrupación y paginación
$query = "SELECT 
    p.id, 
    p.nombre, 
    p.descripcion, 
    p.precio, 
    p.precio_oferta,
    p.imagen_principal,
    p.fecha_creacion,
    cat.nombre as categoria_nombre,
    AVG(COALESCE(c.puntuacion, 0)) as puntuacion_promedio,
    COUNT(DISTINCT c.id) as total_resenas
FROM productos p
LEFT JOIN comentarios c ON p.id = c.producto_id
LEFT JOIN categorias cat ON p.categoria_id = cat.id
WHERE p.categoria_id = ? AND p.activo = 1
GROUP BY p.id, p.nombre, p.descripcion, p.precio, p.precio_oferta, p.imagen_principal, p.fecha_creacion, cat.nombre
ORDER BY " . ($filtros['orden'] === 'recientes' ? 'p.fecha_creacion DESC' : 'p.nombre ASC') . "
LIMIT ? OFFSET ?";

// Ejecutar consulta de productos
$stmt = $db->prepare($query);

// Bind parameters with proper types
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $value, $paramType);
}
// Bind pagination parameters as integers
$stmt->bindValue(count($params) + 1, (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$productos = $stmt->fetchAll();

// Obtener precios mínimos y máximos para los filtros
$precios_query = "SELECT 
    MIN(CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) as min_precio,
    MAX(CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) as max_precio
    FROM productos p 
    WHERE p.categoria_id = ? AND p.activo = 1";
$stmt = $db->prepare($precios_query);
$stmt->execute([$categoria_id]);
$precios = $stmt->fetch();

// Establecer el título de la página
$page_title = $categoria['nombre'] . ' - ' . APP_NAME;

// Incluir el encabezado
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/Silco/">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($categoria['nombre']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Filtros -->
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <form id="filtros-form" method="get" action="">
                        <input type="hidden" name="id" value="<?= $categoria_id ?>">
                        
                        <!-- Búsqueda -->
                        <div class="mb-4">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="busqueda" name="q" 
                                       value="<?= htmlspecialchars($filtros['busqueda']) ?>" 
                                       placeholder="Buscar productos...">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Rango de precios -->
                        <div class="mb-4">
                            <label class="form-label">Rango de precios</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" id="precio_min" name="precio_min" 
                                           min="0" step="0.01" placeholder="Mínimo" 
                                           value="<?= $filtros['precio_min'] !== null ? htmlspecialchars($filtros['precio_min']) : '' ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" id="precio_max" name="precio_max" 
                                           min="0" step="0.01" placeholder="Máximo"
                                           value="<?= $filtros['precio_max'] !== null ? htmlspecialchars($filtros['precio_max']) : '' ?>">
                                </div>
                            </div>
                            <div class="form-text text-muted">
                                Rango: $<?= number_format($precio_min, 2) ?> - $<?= number_format($precio_max, 2) ?>
                            </div>
                        </div>

                        <!-- Ordenar por -->
                        <div class="mb-3">
                            <label for="orden" class="form-label">Ordenar por</label>
                            <select class="form-select" id="orden" name="orden" onchange="this.form.submit()">
                                <option value="recientes" <?= $filtros['orden'] === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                                <option value="precio_asc" <?= $filtros['orden'] === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                                <option value="precio_desc" <?= $filtros['orden'] === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                                <option value="nombre_asc" <?= $filtros['orden'] === 'nombre_asc' ? 'selected' : '' ?>>Nombre: A-Z</option>
                                <option value="nombre_desc" <?= $filtros['orden'] === 'nombre_desc' ? 'selected' : '' ?>>Nombre: Z-A</option>
                                <option value="mejor_valorados" <?= $filtros['orden'] === 'mejor_valorados' ? 'selected' : '' ?>>Mejor valorados</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                            <a href="categoria.php?id=<?= $categoria_id ?>" class="btn btn-outline-secondary">Limpiar filtros</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Lista de productos -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?= htmlspecialchars($categoria['nombre']) ?></h1>
                <div class="d-none d-md-block">
                    <span class="text-muted"><?= $total_productos ?> productos</span>
                </div>
            </div>
            
            <?php if (!empty($filtros['busqueda'])): ?>
                <div class="alert alert-info mb-4">
                    Resultados de búsqueda para: <strong><?= htmlspecialchars($filtros['busqueda']) ?></strong>
                </div>
            <?php endif; ?>
            
            <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-search" style="font-size: 3rem; color: #6c757d;"></i>
                    </div>
                    <h4>No se encontraron productos</h4>
                    <p class="text-muted">Intenta con otros filtros o términos de búsqueda.</p>
                    <a href="?id=<?= $categoria_id ?>" class="btn btn-primary mt-2">Limpiar filtros</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                    <?php foreach ($productos as $producto): ?>
                        <div class="col">
                            <div class="card h-100 product-card">
                                <?php if ($producto['precio_oferta']): ?>
                                    <span class="badge bg-danger position-absolute" style="top: 10px; right: 10px;">
                                        -<?= number_format((($producto['precio'] - $producto['precio_oferta']) / $producto['precio']) * 100, 0) ?>%
                                    </span>
                                <?php endif; ?>
                                
                                <button class="btn btn-link favorite-btn position-absolute top-0 start-0 p-2" 
                                        data-product-id="<?= $producto['id'] ?>" 
                                        data-bs-toggle="tooltip" 
                                        title="Añadir a favoritos">
                                    <i class="bi bi-heart fs-5"></i>
                                </button>
                                
                                <?php 
                                // Inicializar la variable de imagen
                                $imagen_path = '';
                                $full_path = '';
                                
                                if (!empty($producto['imagen_principal'])) {
                                    // Si la ruta ya es una URL completa, usarla directamente
                                    if (filter_var($producto['imagen_principal'], FILTER_VALIDATE_URL)) {
                                        $imagen_path = $producto['imagen_principal'];
                                    } 
                                    // Si es una ruta relativa, construir la ruta correcta
                                    else {
                                        // Obtener solo el nombre del archivo
                                        $image_name = basename($producto['imagen_principal']);
                                        
                                        // Primero intentar con la ruta directa a uploads/productos
                                        $imagen_path = '/Silco/uploads/productos/' . $image_name;
                                        $full_path = $_SERVER['DOCUMENT_ROOT'] . $imagen_path;
                                        
                                        // Si no existe, intentar con la ruta relativa original
                                        if (!file_exists($full_path)) {
                                            $imagen_path = '/Silco/' . ltrim($producto['imagen_principal'], '/');
                                            $full_path = $_SERVER['DOCUMENT_ROOT'] . $imagen_path;
                                        }
                                        
                                        // Si aún no existe, intentar con la ruta base
                                        if (!file_exists($full_path)) {
                                            $imagen_path = $producto['imagen_principal'];
                                            $full_path = $_SERVER['DOCUMENT_ROOT'] . $imagen_path;
                                        }
                                    }
                                }
                                
                                if (isset($imagen_path) && !empty($imagen_path)) {
                                    $full_path = $_SERVER['DOCUMENT_ROOT'] . $imagen_path;
                                    $debug_info[] = 'Ruta alternativa: ' . $full_path;
                                    $debug_info[] = '¿Existe el archivo? ' . (file_exists($full_path) ? 'Sí' : 'No');
                                } else {
                                    $debug_info[] = 'No hay imagen principal definida para el producto';
                                }
                                
                                // Mostrar la imagen o un placeholder si no hay imagen
                                $image_exists = !empty($imagen_path) && 
                                    (filter_var($imagen_path, FILTER_VALIDATE_URL) || 
                                    (file_exists($full_path) && is_file($full_path)));
                                
                                // Mostrar información de depuración
                                if (isset($_GET['debug'])) {
                                    echo '<div class="debug-info bg-light p-2 small text-muted">';
                                    echo 'ID: ' . htmlspecialchars($producto['id']) . '<br>';
                                    echo 'Ruta en DB: ' . htmlspecialchars($producto['imagen_principal'] ?? 'No definida') . '<br>';
                                    echo 'Ruta intentada: ' . htmlspecialchars($imagen_path) . '<br>';
                                    echo '¿Existe?: ' . (file_exists($full_path) ? 'Sí' : 'No') . '<br>';
                                    echo '</div>';
                                }
                                    
                                if ($image_exists) {
                                    echo '<img src="' . htmlspecialchars($imagen_path) . '" class="card-img-top" alt="' . htmlspecialchars($producto['nombre']) . '" style="height: 200px; object-fit: cover; width: 100%;">';
                                } else {
                                    echo '<div class="card-img-top bg-light d-flex flex-column align-items-center justify-content-center text-center" style="height: 200px;">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        <small class="text-muted mt-2">Imagen no disponible</small>
                                        <small class="text-muted">' . htmlspecialchars(basename($producto['imagen_principal'] ?? '')) . '</small>
                                    </div>';
                                }
                                ?>
                                <div class="card-body">
                                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($producto['categoria_nombre']) ?></span>
                                    <h5 class="card-title"><?= htmlspecialchars($producto['nombre']) ?></h5>
                                    <p class="card-text text-truncate"><?= htmlspecialchars($producto['descripcion']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="price">$<?= number_format($producto['precio_oferta'] ?: $producto['precio'], 2) ?></span>
                                            <?php if ($producto['precio_oferta']): ?>
                                                <span class="old-price">$<?= number_format($producto['precio'], 2) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary add-to-cart" 
                                                data-id="<?= $producto['id'] ?>">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <a href="/Silco/producto.php?id=<?= $producto['id'] ?>" class="stretched-link"></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de productos">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Anterior</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Siguiente</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Siguiente</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Manejar el envío del formulario de filtros
    const filtrosForm = document.getElementById('filtros-form');
    if (filtrosForm) {
        filtrosForm.addEventListener('submit', function(e) {
            // Validar que el precio mínimo no sea mayor al máximo
            const precioMin = parseFloat(document.getElementById('precio_min').value);
            const precioMax = parseFloat(document.getElementById('precio_max').value);
            
            if (!isNaN(precioMin) && !isNaN(precioMax) && precioMin > precioMax) {
                e.preventDefault();
                alert('El precio mínimo no puede ser mayor al precio máximo');
                return false;
            }
            return true;
        });
    }
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Manejar el envío del formulario de filtros
    const filtrosForm = document.getElementById('filtros-form');
    if (filtrosForm) {
        filtrosForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obtener los valores del formulario
            const formData = new FormData(filtrosForm);
            const params = new URLSearchParams();
            
            // Agregar solo los campos con valor
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // Redirigir con los parámetros de búsqueda
            window.location.href = `?${params.toString()}`;
        });
    }
    
    // Actualizar la URL con los parámetros de búsqueda al cambiar el orden
    const ordenSelect = document.getElementById('orden');
    if (ordenSelect) {
        ordenSelect.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    }
});
</script>
