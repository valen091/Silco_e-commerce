<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <div class="d-flex justify-content-center mb-2">
                <img src="<?= !empty($usuario_actual['foto_perfil']) ? $usuario_actual['foto_perfil'] : BASE_URL . '/assets/img/default-avatar.png' ?>" 
                     class="rounded-circle" 
                     alt="Foto de perfil" 
                     style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
            </div>
            <h6 class="mb-1"><?= htmlspecialchars($usuario_actual['nombre'] ?? 'Vendedor') ?></h6>
            <small class="text-muted"><?= htmlspecialchars($usuario_actual['nombre_tienda'] ?? 'Mi Tienda') ?></small>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/dashboard') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/dashboard">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/productos') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/productos">
                    <i class="fas fa-box me-2"></i>
                    Productos
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/pedidos') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/pedidos">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Pedidos
                    <?php if (isset($estadisticas['pedidos_pendientes']) && $estadisticas['pedidos_pendientes'] > 0): ?>
                        <span class="badge bg-danger rounded-pill float-end"><?= $estadisticas['pedidos_pendientes'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/ventas') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/ventas">
                    <i class="fas fa-chart-line me-2"></i>
                    Ventas
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/clientes') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/clientes">
                    <i class="fas fa-users me-2"></i>
                    Clientes
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/cupones') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/cupones">
                    <i class="fas fa-tags me-2"></i>
                    Cupones
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/resenas') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/resenas">
                    <i class="fas fa-star me-2"></i>
                    Reseñas
                </a>
            </li>
            
            <li class="nav-item mt-3 border-top pt-2">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/perfil') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/perfil">
                    <i class="fas fa-user-edit me-2"></i>
                    Mi Perfil
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/vendedor/configuracion') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>/vendedor/configuracion">
                    <i class="fas fa-cog me-2"></i>
                    Configuración
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>/vendedor/cerrar-sesion">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Cerrar Sesión
                </a>
            </li>
        </ul>
        
        <div class="mt-4 p-3 bg-white rounded border">
            <h6 class="text-muted text-uppercase small mb-3">Espacio de Almacenamiento</h6>
            <div class="progress mb-2" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between small">
                <span>25% usado</span>
                <span>250 MB de 1 GB</span>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
    overflow-y: auto;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
    padding: 0.5rem 1rem;
    margin: 0.1rem 0.5rem;
    border-radius: 0.25rem;
}

.sidebar .nav-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.sidebar .nav-link.active {
    color: #fff;
    background-color: #0d6efd;
}

.sidebar .nav-link i {
    width: 20px;
    text-align: center;
}

.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: 0.5rem;
    overflow-x: hidden;
    overflow-y: auto;
}
</style>
