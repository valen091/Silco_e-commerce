<?php if (!defined('SIDEBAR_LOADED')): ?>
<?php define('SIDEBAR_LOADED', true); ?>
<div class="dashboard-sidebar">
    <div class="user-panel">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?></div>
            <div class="user-role">Vendedor</div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="panel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'panel.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="productos.php" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['productos.php', 'nuevo-producto.php', 'editar-producto.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Productos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="pedidos.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pedidos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="resenas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'resenas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span>Reseñas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="estadisticas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'estadisticas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Estadísticas</span>
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a href="../mi-cuenta.php" class="nav-link">
                    <i class="fas fa-user-cog"></i>
                    <span>Configuración</span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>
