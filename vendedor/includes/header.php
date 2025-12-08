<?php
// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir la URL base
$base_url = '/Silco';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Vendedor - Silco</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    
    <style>
        .dashboard-sidebar {
            min-height: 100vh;
            background: #343a40;
            color: #fff;
            padding: 20px 0;
        }
        .user-panel {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #4b545c;
        }
        .user-avatar {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .sidebar-nav {
            padding: 20px 0;
        }
        .nav-item {
            margin-bottom: 5px;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            display: flex;
            align-items: center;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }
        .nav-link i {
            width: 24px;
            margin-right: 10px;
            text-align: center;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar se incluirá aquí -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Contenido principal -->
        <div class="main-content w-100">
            <!-- Navbar superior -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
                <div class="container-fluid">
                    <button class="btn btn-link text-white" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="d-flex align-items-center">
                        <a href="<?php echo $base_url; ?>/carrito.php" class="btn btn-outline-light me-2">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count">
                                0
                            </span>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>/mi-cuenta.php"><i class="fas fa-user-cog me-2"></i>Mi Cuenta</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Contenido de la página -->
            <div class="container-fluid">
