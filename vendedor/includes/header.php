<?php
// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../includes/Session.php';
    $session = Session::getInstance();
}

// Verificar si el usuario está autenticado y es vendedor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vendedor') {
    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_after_login'] = '/Silco/vendedor/panel.php';
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Obtener información del usuario
$user_name = $_SESSION['user_nombre'] ?? 'Vendedor';
$user_email = $_SESSION['user_email'] ?? '';
$user_initial = strtoupper(substr($user_name, 0, 1));

// Definir la URL base
$base_url = APP_URL; // Usar la constante APP_URL del config.php
$current_page = basename($_SERVER['PHP_SELF']);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/vendedor.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo $base_url; ?>/assets/images/favicon.ico">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gray-color: #95a5a6;
            --border-color: #e0e6ed;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        /* Barra lateral */
        .dashboard-sidebar {
            width: 280px;
            background: #1a237e;
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Contenido principal */
        .dashboard-main {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        
        /* Barra superior */
        .navbar {
            background-color: #fff !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Panel de usuario en el sidebar */
        .user-panel {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: #0d47a1;
            margin-bottom: 1rem;
        }
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #495057;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
        }
        .sidebar-nav {
            padding: 15px 0;
        }
        .nav-item {
            margin: 0;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            border-left: 3px solid #64b5f6;
            padding-left: 17px;
            transition: all 0.3s ease;
        }
        .nav-link i {
            width: 24px;
            font-size: 1.1rem;
            opacity: 0.9;
            margin-right: 10px;
            text-align: center;
        }
        .main-content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="user-panel">
                <div class="user-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h6 class="mb-1"><?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Vendedor'); ?></h6>
                <small class="text-white-50">Panel de Vendedor</small>
            </div>
            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'panel.php' ? 'active' : '' ?>" href="<?php echo $base_url; ?>/vendedor/panel.php">
                            <i class="fas fa-tachometer-alt"></i> Resumen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : '' ?>" href="<?php echo $base_url; ?>/vendedor/productos.php">
                            <i class="fas fa-box"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : '' ?>" href="<?php echo $base_url; ?>/vendedor/pedidos.php">
                            <i class="fas fa-shopping-bag"></i> Pedidos
                        </a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-warning" href="<?php echo APP_URL; ?>/index.php">
                            <i class="fas fa-home"></i> Volver al Sitio
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Contenido principal -->
        <div class="dashboard-main">
            <!-- Navbar superior -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                <div class="container-fluid">
                    <button class="btn btn-link text-dark" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/mi-cuenta.php"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/index.php"><i class="fas fa-home me-2"></i>Ir al Inicio</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Contenido de la página -->
            <div class="container-fluid">
