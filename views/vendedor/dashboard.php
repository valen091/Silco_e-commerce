<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/sidebar-vendedor.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Panel de Control</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="<?= BASE_URL ?>/vendedor/productos/nuevo" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Ventas Totales</h6>
                                    <h2 class="mb-0">$<?= number_format($estadisticas['ventas_totales'] ?? 0, 2) ?></h2>
                                </div>
                                <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark"><?= $estadisticas['total_ventas'] ?? 0 ?> ventas</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Productos</h6>
                                    <h2 class="mb-0"><?= $estadisticas['total_productos'] ?? 0 ?></h2>
                                </div>
                                <i class="fas fa-boxes fa-3x opacity-50"></i>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark"><?= $estadisticas['productos_activos'] ?? 0 ?> activos</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Pedidos Pendientes</h6>
                                    <h2 class="mb-0"><?= $estadisticas['pedidos_pendientes'] ?? 0 ?></h2>
                                </div>
                                <i class="fas fa-clock fa-3x opacity-50"></i>
                            </div>
                            <div class="mt-2">
                                <a href="<?= BASE_URL ?>/vendedor/pedidos?estado=pendiente" class="text-white">Ver detalles</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Valoración</h6>
                                    <h2 class="mb-0"><?= number_format($estadisticas['valoracion_promedio'] ?? 0, 1) ?>/5</h2>
                                </div>
                                <i class="fas fa-star fa-3x opacity-50"></i>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark"><?= $estadisticas['total_valoraciones'] ?? 0 ?> reseñas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pedidos Recientes</h5>
                    <a href="<?= BASE_URL ?>/vendedor/pedidos" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($pedidos_recientes['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pedido #</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos_recientes['data'] as $pedido): ?>
                                        <tr>
                                            <td>#<?= $pedido['numero_pedido'] ?></td>
                                            <td><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></td>
                                            <td><?= htmlspecialchars($pedido['nombre_cliente']) ?></td>
                                            <td>$<?= number_format($pedido['total'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getEstadoPedidoBadgeClass($pedido['estado']) ?>">
                                                    <?= ucfirst($pedido['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/vendedor/pedidos/ver/<?= $pedido['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay pedidos recientes</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Productos con Bajo Stock</h5>
                    <a href="<?= BASE_URL ?>/vendedor/productos?estado_stock=bajo_stock" class="btn btn-sm btn-outline-warning">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($productos_bajo_stock['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Stock</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos_bajo_stock['data'] as $producto): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($producto['imagen_principal'])): ?>
                                                        <img src="<?= BASE_URL . $producto['imagen_principal'] ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="img-thumbnail me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($producto['nombre']) ?></div>
                                                        <small class="text-muted">Código: <?= $producto['codigo'] ?? 'N/A' ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge bg-<?= $producto['stock'] > 0 ? 'warning' : 'danger' ?>">
                                                    <?= $producto['stock'] ?> unidades
                                                </span>
                                            </td>
                                            <td class="align-middle">$<?= number_format($producto['precio'], 2) ?></td>
                                            <td class="align-middle">
                                                <?php if ($producto['stock'] == 0): ?>
                                                    <span class="badge bg-danger">Agotado</span>
                                                <?php elseif ($producto['stock'] <= 5): ?>
                                                    <span class="badge bg-warning">Últimas unidades</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Disponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle">
                                                <a href="<?= BASE_URL ?>/vendedor/productos/editar/<?= $producto['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">¡Todo en orden! No hay productos con bajo stock.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
