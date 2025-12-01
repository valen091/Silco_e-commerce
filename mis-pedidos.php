<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/includes/config/database.php';
$user_id = $_SESSION['user_id'];

// Get user's orders
$orders = [];
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM items_pedido WHERE pedido_id = p.id) as items_count,
               (SELECT SUM(cantidad * precio_unitario) FROM items_pedido WHERE pedido_id = p.id) as total
        FROM pedidos p
        WHERE usuario_id = ?
        ORDER BY p.fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = "Error al cargar los pedidos: " . $e->getMessage();
    error_log($error_message);
}
?>

<div class="container my-5">
    <h2>Mis Pedidos</h2>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <?php if (empty($orders)): ?>
        <div class="text-center my-5 py-5">
            <i class="bi bi-box-seam" style="font-size: 4rem; color: #6c757d;"></i>
            <h4 class="mt-3">No hay pedidos realizados</h4>
            <p class="text-muted">Aún no has realizado ningún pedido en nuestra tienda.</p>
            <a href="index.php" class="btn btn-primary mt-2">Explorar Productos</a>
        </div>
    <?php else: ?>
        <div class="table-responsive mt-4">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>N° de Pedido</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                            <td><?= date('d/m/Y', strtotime($order['fecha_creacion'])) ?></td>
                            <td><?= $order['items_count'] ?> producto(s)</td>
                            <td>$<?= number_format($order['total'], 2) ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                switch (strtolower($order['estado'])) {
                                    case 'pendiente':
                                        $status_class = 'bg-warning';
                                        break;
                                    case 'en_proceso':
                                        $status_class = 'bg-info';
                                        break;
                                    case 'enviado':
                                        $status_class = 'bg-primary';
                                        break;
                                    case 'entregado':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'cancelado':
                                        $status_class = 'bg-danger';
                                        break;
                                    default:
                                        $status_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $status_class ?>"><?= ucfirst(str_replace('_', ' ', $order['estado'])) ?></span>
                            </td>
                            <td>
                                <a href="detalle-pedido.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <?php if (strtolower($order['estado']) === 'pendiente'): ?>
                                    <button class="btn btn-sm btn-outline-danger cancel-order" data-order-id="<?= $order['id'] ?>">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle order cancellation
    document.querySelectorAll('.cancel-order').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            if (confirm('¿Estás seguro de que deseas cancelar este pedido?')) {
                fetch('backend/orders/cancel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error al cancelar el pedido: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cancelar el pedido. Por favor, inténtalo de nuevo.');
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
