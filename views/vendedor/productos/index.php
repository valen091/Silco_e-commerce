<?php require_once __DIR__ . '/../../../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../../includes/sidebar-vendedor.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mis Productos</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= BASE_URL ?>/vendedor/productos/nuevo" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Producto
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="<?= BASE_URL ?>/vendedor/productos">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="busqueda" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                           value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>" 
                                           placeholder="Nombre o descripción">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>" 
                                            <?= (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" <?= (isset($_GET['estado']) && $_GET['estado'] === 'activo') ? 'selected' : '' ?>>Activos</option>
                                    <option value="inactivo" <?= (isset($_GET['estado']) && $_GET['estado'] === 'inactivo') ? 'selected' : '' ?>>Inactivos</option>
                                    <option value="agotado" <?= (isset($_GET['estado']) && $_GET['estado'] === 'agotado') ? 'selected' : '' ?>>Agotados</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (!empty($productos['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Ventas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos['data'] as $producto): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($producto['imagen_principal'])): ?>
                                                        <img src="<?= BASE_URL . $producto['imagen_principal'] ?>" 
                                                             alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                                             class="img-thumbnail me-3" 
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center me-3" 
                                                             style="width: 50px; height: 50px;">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($producto['nombre']) ?></div>
                                                        <small class="text-muted">SKU: <?= $producto['sku'] ?? 'N/A' ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría') ?>
                                            </td>
                                            <td class="align-middle">
                                                <span class="fw-bold">$<?= number_format($producto['precio'], 2) ?></span>
                                                <?php if ($producto['precio_descuento'] > 0): ?>
                                                    <div>
                                                        <small class="text-decoration-line-through text-muted">
                                                            $<?= number_format($producto['precio_descuento'], 2) ?>
                                                        </small>
                                                        <span class="badge bg-danger ms-1">
                                                            <?= number_format((($producto['precio_descuento'] - $producto['precio']) / $producto['precio_descuento']) * 100, 0) ?>%
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge bg-<?= $producto['stock'] > 0 ? ($producto['stock'] > 5 ? 'success' : 'warning') : 'danger' ?>">
                                                    <?= $producto['stock'] ?> unidades
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge bg-<?= $producto['activo'] ? 'success' : 'secondary' ?>">
                                                    <?= $producto['activo'] ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <?= $producto['total_ventas'] ?? 0 ?>
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/producto/<?= $producto['slug'] ?>" 
                                                       class="btn btn-outline-secondary" 
                                                       target="_blank" 
                                                       title="Ver en tienda">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/vendedor/productos/editar/<?= $producto['id'] ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-<?= $producto['activo'] ? 'warning' : 'success' ?> toggle-status" 
                                                            data-id="<?= $producto['id'] ?>"
                                                            data-status="<?= $producto['activo'] ? 0 : 1 ?>"
                                                            title="<?= $producto['activo'] ? 'Desactivar' : 'Activar' ?>">
                                                        <i class="fas fa-<?= $producto['activo'] ? 'eye-slash' : 'eye' ?>"></i>
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger delete-product" 
                                                            data-id="<?= $producto['id'] ?>"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($productos['total_paginas'] > 1): ?>
                            <nav class="px-3 py-2">
                                <ul class="pagination justify-content-end mb-0">
                                    <?php if ($productos['pagina_actual'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?pagina=<?= $productos['pagina_actual'] - 1 ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                                &laquo; Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $productos['total_paginas']; $i++): ?>
                                        <li class="page-item <?= $i == $productos['pagina_actual'] ? 'active' : '' ?>">
                                            <a class="page-link" 
                                               href="?pagina=<?= $i ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($productos['pagina_actual'] < $productos['total_paginas']): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?pagina=<?= $productos['pagina_actual'] + 1 ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                                Siguiente &raquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                            <h5>No se encontraron productos</h5>
                            <p class="text-muted">
                                <?php if (!empty($_GET)): ?>
                                    No hay productos que coincidan con los filtros seleccionados.
                                <?php else: ?>
                                    Aún no has agregado ningún producto. ¡Comienza a vender ahora!
                                <?php endif; ?>
                            </p>
                            <a href="<?= BASE_URL ?>/vendedor/productos/nuevo" class="btn btn-primary mt-2">
                                <i class="fas fa-plus me-1"></i> Agregar primer producto
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" action="">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
$scripts = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    BASE_URL . '/assets/js/vendedor/productos.js'
];
?>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
