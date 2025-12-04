<?php
// Start session first
require_once __DIR__ . '/Session.php';
$session = Session::getInstance();

// Incluir configuración global
require_once __DIR__ . '/../config.php';

// Definir la URL base si no está definida
if (!defined('BASE_URL')) {
    define('BASE_URL', APP_URL);
}

// Mostrar errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database connection
$db = null;
$categories = [];

try {
    // Cargar configuración de la base de datos
    require_once __DIR__ . '/../config/database.php';
    
    // Create database connection
    $database = new DatabaseConfig();
    $db = $database->connect();
    
    // Get categories for navigation
    $categories_query = "SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre";
    $categories_stmt = $db->query($categories_query);
    $categories = $categories_stmt ? $categories_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // Don't show database errors to users in production
    if (isset($_SERVER['ENVIRONMENT']) && $_SERVER['ENVIRONMENT'] === 'development') {
        die("Database connection failed: " . $e->getMessage());
    }
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    if (isset($_SERVER['ENVIRONMENT']) && $_SERVER['ENVIRONMENT'] === 'development') {
        die("An error occurred: " . $e->getMessage());
    }
}

// Helper function to check if user is a seller
function isSeller() {
    // Check both possible session variables that might indicate a seller
    $is_seller = isset($_SESSION['es_vendedor']) && $_SESSION['es_vendedor'] == 1;
    $is_seller = $is_seller || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendedor');
    
    // Debug information
    if (isset($_SESSION['user_id'])) {
        error_log('User ID: ' . $_SESSION['user_id'] . ' - Is Seller: ' . ($is_seller ? 'Yes' : 'No'));
        error_log('Session data: ' . print_r($_SESSION, true));
    }
    
    return $is_seller;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Silco - Tu tienda en línea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        // Initialize cart when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetch('<?= BASE_URL ?>/backend/cart/init.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    const cartCount = data.cart_count || 0;
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = cartCount;
                        cartCountElement.style.display = cartCount > 0 ? 'inline-block' : 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error initializing cart:', error);
                // Optionally show a user-friendly message or retry logic can be added here
            });
        });
    </script>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/index.php">Silco</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                            <i class="bi bi-house-door"></i> Inicio
                        </a>
                    </li>
                    <?php if (!empty($categories)): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-grid"></i> Categorías
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                                <?php foreach ($categories as $category): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/categoria.php?id=<?= $category['id'] ?>">
                                            <?= htmlspecialchars($category['nombre']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ofertas.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/ofertas.php">
                            <i class="bi bi-tag"></i> Ofertas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contacto.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contacto.php">
                            <i class="bi bi-envelope"></i> Contacto
                        </a>
                    </li>
                    <?php if (isSeller()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>/vendedor/panel.php">
                            <i class="bi bi-shop"></i> Panel Vendedor
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <?php if (!isset($hideSearch) || !$hideSearch): ?>
                <!-- Barra de búsqueda -->
                <form class="d-flex me-3" action="<?= BASE_URL ?>/buscar.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Buscar productos..." aria-label="Buscar" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                        <button class="btn btn-light border" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Mi Cuenta') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/mi-cuenta.php">
                                        <i class="bi bi-person me-2"></i>Mi perfil
                                    </a>
                                </li>
                                <?php if (isSeller()): ?>
                                <li>
                                    <a class="dropdown-item" href="/Silco/vendedor/panel.php">
                                        <i class="bi bi-speedometer2 me-2"></i> Panel de Vendedor
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider">
                                <?php endif; ?>
                                <li>
                                        <a class="dropdown-item" href="<?= BASE_URL ?>/ser-vendedor.php">
                                            <i class="bi bi-shop me-2"></i>Ser vendedor
                                        </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/mis-pedidos.php">
                                        <i class="bi bi-box-seam me-2"></i>Mis pedidos
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= BASE_URL ?>/favoritos.php">
                                        <i class="bi bi-heart me-2"></i>Favoritos
                                        <span class="badge bg-primary rounded-pill ms-1" id="favorites-count">0</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?= BASE_URL ?>/carrito.php">
                                <i class="bi bi-cart3"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-count" style="font-size: 0.65em;">0</span>
                                <span class="d-none d-md-inline ms-1">Carrito</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/login.php?force_login=1">
                                <i class="bi bi-box-arrow-in-right" style="color: #1a73e8;"></i> Iniciar sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light ms-2" href="<?= BASE_URL ?>/register.php">
                                <i class="bi bi-person-plus me-1"></i>Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <?php if (!isset($hideCartInit) || !$hideCartInit): ?>
    <script>
        // Initialize cart when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetch('<?= BASE_URL ?>/backend/cart/init.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    const cartCount = data.cart_count || 0;
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = cartCount;
                        cartCountElement.style.display = cartCount > 0 ? 'inline-block' : 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error initializing cart:', error);
            });
        });
    </script>
    <?php endif; ?>

    <div class="container my-4">
