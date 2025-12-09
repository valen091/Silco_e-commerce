<?php
// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Incluir cabecera
$page_title = '¡Gracias por tu compra!';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">¡Pago Completado con Éxito!</h4>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-4">¡Gracias por tu compra!</h2>
                    <p class="lead">Tu pago ha sido procesado correctamente.</p>
                    <p>Hemos enviado un correo electrónico con los detalles de tu pedido a <strong><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></strong>.</p>
                    
                    <div class="mt-5">
                        <a href="mis-pedidos.php" class="btn btn-primary me-3">
                            <i class="fas fa-box me-2"></i>Ver mis pedidos
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Volver al inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Limpiar el carrito después de una compra exitosa
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

include 'includes/footer.php';
?>
