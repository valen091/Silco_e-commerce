<?php
// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario es un vendedor
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'vendedor') {
    // Redirigir al login si no es un vendedor
    header('Location: /Silco/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Definir la ruta base
$base_url = '/Silco';

// Incluir el encabezado
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-md-9">
            <h2>Panel de Vendedor</h2>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bienvenido al Panel de Vendedor</h5>
                    <p class="card-text">
                        Desde aquí podrás gestionar tus productos, pedidos y perfil de vendedor.
                        Utiliza el menú lateral para navegar entre las diferentes secciones.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
require_once __DIR__ . '/includes/footer.php';
?>
