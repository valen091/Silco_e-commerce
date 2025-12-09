<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Session.php';

// Inicializar la sesión
$session = Session::getInstance();

// Verificar si se proporcionó un término de búsqueda
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    header('Location: /Silco/');
    exit();
}

$db = Database::getInstance()->getConnection();

// Configuración de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Filtros
$filtros = [
    'precio_min' => isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : null,
    'precio_max' => isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : null,
    'orden' => $_GET['orden'] ?? 'relevancia',
    'categoria' => isset($_GET['categoria']) ? (int)$_GET['categoria'] : null
];

// Construir la consulta de búsqueda
$search_terms = explode(' ', $query);
$search_conditions = [];
$params = [];

// Añadir condiciones de búsqueda para cada término
foreach ($search_terms as $term) {
    if (strlen($term) >= 2) {
        $search_conditions[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $search_term = "%$term%";
        $params = array_merge($params, [$search_term, $search_term]);
    }
}

if (empty($search_conditions)) {
    $search_conditions[] = "1=0"; // No mostrar resultados si no hay términos de búsqueda válidos
}

// Consulta base
$base_query = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.precio_oferta, p.stock, p.categoria_id, 
              p.activo, p.fecha_creacion, p.fecha_actualizacion,
              c.nombre as categoria_nombre,
              (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal
              FROM productos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.activo = 1 AND " . implode(' AND ', $search_conditions);

$filtered_query = $base_query;
$count_query = "SELECT COUNT(*) as total FROM ($filtered_query) as count_table";

// Aplicar filtros adicionales
if ($filtros['precio_min'] !== null) {
    $filtered_query .= " AND (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) >= ?";
    $params[] = $filtros['precio_min'];
}

if ($filtros['precio_max'] !== null) {
    $filtered_query .= " AND (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) <= ?";
    $params[] = $filtros['precio_max'];
}

if ($filtros['categoria']) {
    $filtered_query .= " AND p.categoria_id = ?";
    $params[] = $filtros['categoria'];
}

// Ordenar resultados
switch ($filtros['orden']) {
    case 'precio_asc':
        $filtered_query .= " ORDER BY (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) ASC";
        break;
    case 'precio_desc':
        $filtered_query .= " ORDER BY (CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) DESC";
        break;
    case 'nombre_asc':
        $filtered_query .= " ORDER BY p.nombre ASC";
        break;
    case 'nombre_desc':
        $filtered_query .= " ORDER BY p.nombre DESC";
        break;
    case 'relevancia':
    default:
        // Ordenar por relevancia (más coincidencias primero)
        $filtered_query .= " ORDER BY \n            (p.nombre LIKE ?) DESC,\n            (p.descripcion LIKE ?) DESC,\n            p.fecha_creacion DESC";
        $params = array_merge($params, ["%$query%", "%$query%"]);
        break;
}

// Contar resultados totales
$count_query = "SELECT COUNT(*) as total FROM ($filtered_query) as count_table";
$count_stmt = $db->prepare($count_query);

// Bind parameters for count query
foreach ($params as $i => $param) {
    $paramType = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $count_stmt->bindValue($i + 1, $param, $paramType);
}

$count_stmt->execute();
$total_resultados = $count_stmt->fetch()['total'];
$total_paginas = ceil($total_resultados / $per_page);

// Crear una copia de los parámetros para la consulta principal
$main_params = $params;

// Añadir parámetros de paginación a la copia
$main_params[] = (int)$per_page;
$main_params[] = (int)$offset;

// Aplicar paginación a la consulta principal
$filtered_query .= " LIMIT ? OFFSET ?";

// Ejecutar consulta de productos
try {
    $stmt = $db->prepare($filtered_query);
    
    // Bind all parameters including pagination
    $paramIndex = 1;
    foreach ($main_params as $param) {
        $paramType = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($paramIndex++, $param, $paramType);
    }
    
    // Bind pagination parameters (as integers)
    $stmt->bindValue($paramIndex++, (int)$per_page, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, (int)$offset, PDO::PARAM_INT);
    
    // Execute the statement
    $stmt->execute();
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error en la consulta de búsqueda: ' . $e->getMessage());
    $productos = [];
    $total_resultados = 0;
    $total_paginas = 0;
}

// Obtener categorías para el filtro
$categorias = [];
try {
    $categorias_query = "SELECT DISTINCT c.id, c.nombre 
                        FROM categorias c 
                        JOIN productos p ON c.id = p.categoria_id 
                        WHERE p.activo = 1 
                        ORDER BY c.nombre";
    $categorias_stmt = $db->query($categorias_query);
    $categorias = $categorias_stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error al obtener categorías: ' . $e->getMessage());
}

// Obtener precios mínimos y máximos para los filtros
$precios = ['min_precio' => 0, 'max_precio' => 1000]; // Valores por defecto
try {
    $precios_query = "SELECT 
        MIN(CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) as min_precio,
        MAX(CASE WHEN p.precio_oferta IS NOT NULL THEN p.precio_oferta ELSE p.precio END) as max_precio
        FROM productos p 
        WHERE p.activo = 1";
    $precios_stmt = $db->query($precios_query);
    $precios_result = $precios_stmt->fetch();
    if ($precios_result) {
        $precios = $precios_result;
    }
} catch (PDOException $e) {
    error_log('Error al obtener precios: ' . $e->getMessage());
}

// Establecer el título de la página
$page_title = "Búsqueda: " . htmlspecialchars($query) . ' - ' . APP_NAME;

// Incluir el encabezado
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/Silco/">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Búsqueda: <?= htmlspecialchars($query) ?></li>
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
                    <form id="filtros-form" method="get" action="/Silco/buscar.php">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label">Precio</label>
                            <div class="row g-2">
                                <div class="col">
                                    <input type="number" class="form-control" id="precio_min" name="precio_min" 
                                           placeholder="Mín" min="0" step="0.01" 
                                           value="<?= $filtros['precio_min'] ?? '' ?>">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" id="precio_max" name="precio_max" 
                                           placeholder="Máx" min="0" step="0.01" 
                                           value="<?= $filtros['precio_max'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="form-text">
                                Rango: $<?= number_format($precios['min_precio'] ?? 0, 2) ?> - $<?= number_format($precios['max_precio'] ?? 0, 2) ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria" name="categoria">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" 
                                        <?= $filtros['categoria'] == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="orden" class="form-label">Ordenar por</label>
                            <select class="form-select" id="orden" name="orden">
                                <option value="relevancia" <?= $filtros['orden'] === 'relevancia' ? 'selected' : '' ?>>Más relevantes</option>
                                <option value="precio_asc" <?= $filtros['orden'] === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
                                <option value="precio_desc" <?= $filtros['orden'] === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
                                <option value="nombre_asc" <?= $filtros['orden'] === 'nombre_asc' ? 'selected' : '' ?>>Nombre: A-Z</option>
                                <option value="nombre_desc" <?= $filtros['orden'] === 'nombre_desc' ? 'selected' : '' ?>>Nombre: Z-A</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Aplicar filtros</button>
                        <?php if (isset($_GET['precio_min']) || isset($_GET['precio_max']) || isset($_GET['categoria']) || (isset($_GET['orden']) && $_GET['orden'] !== 'relevancia')): ?>
                            <a href="?q=<?= urlencode($query) ?>" class="btn btn-outline-secondary w-100 mt-2">Limpiar filtros</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Resultados de búsqueda -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Resultados de búsqueda</h1>
                <div class="d-none d-md-block">
                    <span class="text-muted"><?= $total_resultados ?> productos encontrados</span>
                </div>
            </div>
            
            <div class="alert alert-info mb-4">
                Mostrando resultados para: <strong><?= htmlspecialchars($query) ?></strong>
            </div>
            
            <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-search" style="font-size: 3rem; color: #6c757d;"></i>
                    </div>
                    <h4>No se encontraron productos</h4>
                    <p class="text-muted">Intenta con otros términos de búsqueda o ajusta los filtros.</p>
                    <a href="/Silco/" class="btn btn-primary mt-2">Volver al inicio</a>
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
                                
                                <img src="<?= htmlspecialchars($producto['imagen_principal'] ?: 'assets/img/placeholder.jpg') ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($producto['nombre']) ?>">
                                     
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
                    <nav aria-label="Paginación de resultados">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&laquo;</span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_paginas, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;
                            
                            if ($end_page < $total_paginas) {
                                if ($end_page < $total_paginas - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $total_paginas])) . '">' . $total_paginas . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Siguiente">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-hidden="true">&raquo;</span>
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
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Actualizar la URL con los parámetros de búsqueda al cambiar el orden
    const ordenSelect = document.getElementById('orden');
    if (ordenSelect) {
        ordenSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Actualizar la URL con los parámetros de búsqueda al cambiar la categoría
    const categoriaSelect = document.getElementById('categoria');
    if (categoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});
</script>
