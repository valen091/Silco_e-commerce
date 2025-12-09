<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Mis Pedidos</h1>
            
            <?php if (empty($pedidos)): ?>
                <div class="alert alert-info">
                    No has realizado ningún pedido aún. <a href="<?= BASE_URL ?>/index.php">Explora nuestros productos</a> para realizar tu primera compra.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th># Pedido</th>
                                <th>Fecha</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($pedido['id']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></td>
                                    <td><?= $pedido['total_productos'] ?> producto(s)</td>
                                    <td>$<?= number_format($pedido['total'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $pedido['estado'] === 'completado' ? 'success' : 
                                            ($pedido['estado'] === 'en_proceso' ? 'primary' : 
                                            ($pedido['estado'] === 'cancelado' ? 'danger' : 'secondary')) 
                                        ?>">
                                            <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/pedido-detalle.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            Ver Detalle
                                        </a>
                                        <?php if ($pedido['estado'] === 'pendiente' || $pedido['estado'] === 'en_proceso'): ?>
                                            <button class="btn btn-sm btn-outline-danger cancelar-pedido" data-id="<?= $pedido['id'] ?>">
                                                Cancelar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de pedidos" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina_actual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina_actual < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>">Siguiente</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <div class="text-end mt-3">
                    <p>Mostrando <?= count($pedidos) ?> de <?= $total_pedidos ?> pedidos</p>
                </div>
                
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Modal de confirmación de cancelación -->
<div class="modal fade" id="cancelarPedidoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar cancelación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas cancelar este pedido? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" id="confirmarCancelarPedido">Sí, cancelar pedido</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar la cancelación de pedido
    const cancelarBtns = document.querySelectorAll('.cancelar-pedido');
    const modal = new bootstrap.Modal(document.getElementById('cancelarPedidoModal'));
    let pedidoId = null;
    
    cancelarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            pedidoId = this.getAttribute('data-id');
            modal.show();
        });
    });
    
    document.getElementById('confirmarCancelarPedido').addEventListener('click', function() {
        if (!pedidoId) return;
        
        fetch(`<?= BASE_URL ?>/backend/orders/cancel.php?id=${pedidoId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al cancelar el pedido');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al procesar la solicitud');
        })
        .finally(() => {
            modal.hide();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
