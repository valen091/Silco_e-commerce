<?php require_once __DIR__ . '/../../../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../../includes/sidebar-vendedor.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Pedidos</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportOrders">
                            <i class="fas fa-file-export me-1"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="<?= BASE_URL ?>/vendedor/pedidos" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente" <?= (isset($_GET['estado']) && $_GET['estado'] === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="procesando" <?= (isset($_GET['estado']) && $_GET['estado'] === 'procesando') ? 'selected' : '' ?>>En proceso</option>
                                    <option value="enviado" <?= (isset($_GET['estado']) && $_GET['estado'] === 'enviado') ? 'selected' : '' ?>>Enviado</option>
                                    <option value="entregado" <?= (isset($_GET['estado']) && $_GET['estado'] === 'entregado') ? 'selected' : '' ?>>Entregado</option>
                                    <option value="cancelado" <?= (isset($_GET['estado']) && $_GET['estado'] === 'cancelado') ? 'selected' : '' ?>>Cancelado</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="busqueda" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                                           placeholder="N° de pedido o cliente" 
                                           value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                                <a href="<?= BASE_URL ?>/vendedor/pedidos" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card border-start border-primary border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Total de Pedidos</h6>
                                    <h3 class="mb-0"><?= $estadisticas['total_pedidos'] ?? 0 ?></h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-shopping-bag text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card border-start border-warning border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Pendientes</h6>
                                    <h3 class="mb-0"><?= $estadisticas['pendientes'] ?? 0 ?></h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-clock text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card border-start border-info border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">En Proceso</h6>
                                    <h3 class="mb-0"><?= $estadisticas['en_proceso'] ?? 0 ?></h3>
                                </div>
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-truck-loading text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card border-start border-success border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted">Entregados</h6>
                                    <h3 class="mb-0"><?= $estadisticas['entregados'] ?? 0 ?></h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (!empty($pedidos['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Productos</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos['data'] as $pedido): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">#<?= $pedido['numero_pedido'] ?></div>
                                                <small class="text-muted">
                                                    <?= $pedido['metodo_pago'] ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-2">
                                                        <span class="avatar-initial rounded-circle bg-primary text-white">
                                                            <?= strtoupper(substr($pedido['nombre_cliente'] ?? 'U', 0, 1)) ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($pedido['nombre_cliente'] ?? 'Usuario') ?></div>
                                                        <small class="text-muted"><?= $pedido['email_cliente'] ?? '' ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?>
                                                <div class="text-muted small">
                                                    <?= date('H:i', strtotime($pedido['fecha_pedido'])) ?>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex">
                                                    <?php 
                                                    $productos = array_slice($pedido['productos'] ?? [], 0, 3);
                                                    $masProductos = count($pedido['productos'] ?? []) - 3;
                                                    
                                                    foreach ($productos as $producto): 
                                                    ?>
                                                        <div class="avatar me-1">
                                                            <img src="<?= !empty($producto['imagen']) ? BASE_URL . $producto['imagen'] : BASE_URL . '/assets/img/no-image.jpg' ?>" 
                                                                 alt="<?= htmlspecialchars($producto['nombre'] ?? '') ?>" 
                                                                 class="rounded" 
                                                                 style="width: 32px; height: 32px; object-fit: cover;">
                                                        </div>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if ($masProductos > 0): ?>
                                                        <div class="avatar">
                                                            <span class="avatar-initial rounded bg-light text-dark">
                                                                +<?= $masProductos ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= count($pedido['productos'] ?? []) ?> producto<?= count($pedido['productos'] ?? []) !== 1 ? 's' : '' ?>
                                                </small>
                                            </td>
                                            <td class="align-middle">
                                                <div class="fw-bold">$<?= number_format($pedido['total'], 2) ?></div>
                                                <small class="text-<?= $pedido['estado_pago'] === 'pagado' ? 'success' : ($pedido['estado_pago'] === 'pendiente' ? 'warning' : 'danger') ?>">
                                                    <?= ucfirst($pedido['estado_pago']) ?>
                                                </small>
                                            </td>
                                            <td class="align-middle">
                                                <span class="badge bg-<?= getEstadoPedidoBadgeClass($pedido['estado']) ?>">
                                                    <?= ucfirst($pedido['estado']) ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" 
                                                            id="dropdownMenuButton<?= $pedido['id'] ?>" 
                                                            data-bs-toggle="dropdown" 
                                                            aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $pedido['id'] ?>">
                                                        <li>
                                                            <a class="dropdown-item" 
                                                               href="<?= BASE_URL ?>/vendedor/pedidos/ver/<?= $pedido['id'] ?>">
                                                                <i class="fas fa-eye me-2"></i>Ver Detalles
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" 
                                                               href="<?= BASE_URL ?>/vendedor/pedidos/editar/<?= $pedido['id'] ?>">
                                                                <i class="fas fa-edit me-2"></i>Editar
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" 
                                                               href="#"
                                                               onclick="return confirmAction('¿Estás seguro de cancelar este pedido?', '<?= BASE_URL ?>/vendedor/pedidos/cancelar/<?= $pedido['id'] ?>')">
                                                                <i class="fas fa-times-circle me-2"></i>Cancelar Pedido
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($pedidos['total_paginas'] > 1): ?>
                            <nav class="px-3 py-2">
                                <ul class="pagination justify-content-end mb-0">
                                    <?php if ($pedidos['pagina_actual'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?pagina=<?= $pedidos['pagina_actual'] - 1 ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                                &laquo; Anterior
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pedidos['total_paginas']; $i++): ?>
                                        <li class="page-item <?= $i == $pedidos['pagina_actual'] ? 'active' : '' ?>">
                                            <a class="page-link" 
                                               href="?pagina=<?= $i ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pedidos['pagina_actual'] < $pedidos['total_paginas']): ?>
                                        <li class="page-item">
                                            <a class="page-link" 
                                               href="?pagina=<?= $pedidos['pagina_actual'] + 1 ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                                Siguiente &raquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5>No se encontraron pedidos</h5>
                            <p class="text-muted">
                                <?php if (!empty($_GET)): ?>
                                    No hay pedidos que coincidan con los filtros seleccionados.
                                <?php else: ?>
                                    Aún no tienes pedidos registrados.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exportar Pedidos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="exportForm" action="<?= BASE_URL ?>/vendedor/pedidos/exportar" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="filtros" value="<?= htmlspecialchars(json_encode($_GET)) ?>">
                    
                    <div class="mb-3">
                        <label for="formato" class="form-label">Formato de exportación</label>
                        <select class="form-select" id="formato" name="formato" required>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rango de fechas</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="date" class="form-control" name="fecha_inicio" 
                                       value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                                <div class="form-text">Fecha de inicio</div>
                            </div>
                            <div class="col">
                                <input type="date" class="form-control" name="fecha_fin" 
                                       value="<?= date('Y-m-d') ?>">
                                <div class="form-text">Fecha de fin</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="soloActivos" name="solo_activos" checked>
                        <label class="form-check-label" for="soloActivos">Solo pedidos activos</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i> Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$scripts = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    BASE_URL . '/assets/js/vendedor/pedidos.js'
];
?>

<script>
// Confirm action before proceeding
function confirmAction(message, url) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    
    return false;
}

// Open export modal
document.addEventListener('DOMContentLoaded', function() {
    const exportButton = document.getElementById('exportOrders');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
            exportModal.show();
        });
    }
    
    // Initialize date range picker if needed
    if (typeof daterangepicker !== 'undefined') {
        $('input[name="fecha_rango"]').daterangepicker({
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Aplicar',
                cancelLabel: 'Cancelar',
                fromLabel: 'Desde',
                toLabel: 'Hasta',
                customRangeLabel: 'Personalizado',
                daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                firstDay: 1
            },
            opens: 'left',
            autoUpdateInput: false,
            ranges: {
                'Hoy': [moment(), moment()],
                'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
                'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
                'Este mes': [moment().startOf('month'), moment().endOf('month')],
                'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        
        $('input[name="fecha_rango"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            
            // Update hidden inputs
            $('input[name="fecha_desde"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="fecha_hasta"]').val(picker.endDate.format('YYYY-MM-DD'));
            
            // Submit the form
            $('#filterForm').submit();
        });
        
        $('input[name="fecha_rango"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $('input[name="fecha_desde"], input[name="fecha_hasta"]').val('');
            $('#filterForm').submit();
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
