<?php
// Habilitar mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configuración primero
require_once __DIR__ . '/../config.php';

// Incluir el manejador de sesión
require_once __DIR__ . '/../includes/Session.php';

// Inicializar la sesión
$session = Session::getInstance();

// Incluir funciones
require_once __DIR__ . '/../includes/functions.php';

// Debug: Mostrar información de sesión
error_log('=== Acceso a panel.php ===');
error_log('Session ID: ' . session_id());
error_log('Is Logged In: ' . (isLoggedIn() ? 'Yes' : 'No'));
error_log('Session data: ' . print_r($_SESSION, true));

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    error_log('Usuario no autenticado. Redirigiendo a login.');
    $_SESSION['redirect_after_login'] = '/Silco/vendedor/panel.php';
    header('Location: /Silco/login.php');
    exit();
}

// Verificar si el usuario es vendedor
if (!isVendedor()) {
    error_log('Usuario no es vendedor. User ID: ' . ($_SESSION['user_id'] ?? 'No definido'));
    $_SESSION['error'] = 'No tienes permiso para acceder al panel de vendedor.';
    header('Location: /Silco/perfil.php');
    exit();
}

// Actualizar la actividad de la sesión
$session->set('last_activity', time());

// Si llegamos aquí, el usuario está autenticado y es vendedor
error_log('Acceso concedido al panel de vendedor. User ID: ' . $_SESSION['user_id']);

// Inicializar variables para las estadísticas
$total_productos = 0;
$total_pedidos = 0;
$ingresos = 0;
$bajo_stock = 0;
$agotados = 0;
$ultimos_pedidos = [];
$productos_bajo_stock = [];
$error = '';

// Conectar a la base de datos
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Verificar que el usuario sigue siendo vendedor
    $stmt = $conn->prepare("SELECT es_vendedor FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['es_vendedor']) {
        $_SESSION['error'] = 'Tu cuenta de vendedor ha sido deshabilitada.';
        header('Location: /perfil.php');
        exit();
    }
    
    // Obtener estadísticas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ?");
    $stmt->execute([$user_id]);
    $total_productos = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT p.id) as total 
                           FROM pedidos p 
                           JOIN items_pedido ip ON p.id = ip.pedido_id 
                           JOIN productos pr ON ip.producto_id = pr.id 
                           WHERE pr.vendedor_id = ?");
    $stmt->execute([$user_id]);
    $total_pedidos = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) as total 
                           FROM items_pedido ip 
                           JOIN productos p ON ip.producto_id = p.id 
                           JOIN pedidos ped ON ip.pedido_id = ped.id 
                           WHERE p.vendedor_id = ? AND ped.estado != 'cancelado'");
    $stmt->execute([$user_id]);
    $ingresos = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ? AND stock <= 5 AND stock > 0");
    $stmt->execute([$user_id]);
    $bajo_stock = $stmt->fetch()['total'];
    
    // Obtener productos con bajo stock
    $stmt = $conn->prepare("SELECT id, nombre, stock, imagen_principal 
                           FROM productos 
                           WHERE vendedor_id = ? AND stock <= 5 AND stock > 0 
                           ORDER BY stock ASC 
                           LIMIT 5");
    $stmt->execute([$user_id]);
    $productos_bajo_stock = $stmt->fetchAll();
    
    // Obtener últimos pedidos
    $stmt = $conn->prepare("SELECT p.id, p.fecha_creacion, p.estado, 
                           GROUP_CONCAT(pr.nombre SEPARATOR ', ') as productos,
                           SUM(ip.cantidad * ip.precio_unitario) as total
                           FROM pedidos p
                           JOIN items_pedido ip ON p.id = ip.pedido_id
                           JOIN productos pr ON ip.producto_id = pr.id
                           WHERE pr.vendedor_id = ?
                           GROUP BY p.id, p.fecha_creacion, p.estado
                           ORDER BY p.fecha_creacion DESC
                           LIMIT 5");
    $stmt->execute([$user_id]);
    $ultimos_pedidos = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error en el panel del vendedor: " . $e->getMessage());
    $error = "Error al cargar los datos del panel. Por favor, inténtalo de nuevo más tarde.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Restringido - Silco</title>
    
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
    
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            color: #333;
        }
        
        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            background: #fff;
            border: 1px solid #e3e6f0;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
            position: absolute;
            right: 1rem;
            top: 1rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .card {
            border: 1px solid #e3e6f0;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1.5rem 0.5rem rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        
        .card-header h5 {
            font-weight: 600;
            color: #4e73df;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            border-top: none;
            padding: 1rem 0.75rem;
            background-color: #f8f9fc;
        }
        
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-color: #eaecf4;
        }
        
        .btn {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.7em;
        }
        
        .badge-warning {
            color: #000;
            background-color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .progress {
            height: 0.5rem;
            border-radius: 0.25rem;
            background-color: #eaecf4;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
            <!-- Main Content -->
            <div class="dashboard-main">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">Panel de Control</h1>
                        <p class="text-muted mb-0">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Vendedor'); ?></p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-outline-secondary me-2" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                            <a href="/Silco/vendedor/productos" class="btn btn-primary">
                                <i class="fas fa-boxes me-2"></i>Gestionar Productos
                            </a>
                            <a href="/Silco/vendedor/productos/nuevo" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-plus me-2"></i>Nuevo Producto
                            </a>
                        <button class="btn btn-outline-secondary d-lg-none ms-2" id="sidebarToggleMobile">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
                
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stats Overview -->
                <div class="stats-overview">
                
                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?php echo number_format($total_productos); ?></h3>
                            <p class="stat-label">Productos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?php echo number_format($total_pedidos); ?></h3>
                            <p class="stat-label">Pedidos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value">$<?php echo number_format($ingresos, 2); ?></h3>
                            <p class="stat-label">Ingresos Totales</p>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?php echo $bajo_stock; ?></h3>
                            <p class="stat-label">Bajo Stock</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders & Low Stock -->
                <div class="row g-4">
                <!-- Últimos Pedidos -->
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-white border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Últimos Pedidos</h5>
                                <a href="pedidos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ultimos_pedidos)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                                    </div>
                                    <h5>No hay pedidos recientes</h5>
                                    <p class="text-muted">Tus pedidos aparecerán aquí</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th># Pedido</th>
                                                <th>Fecha</th>
                                                <th>Productos</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimos_pedidos as $pedido): ?>
                                                <tr>
                                                    <td>#<?php echo str_pad($pedido['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></td>
                                                    <td>
                                                        <?php 
                                                        $productos = explode(', ', $pedido['productos']);
                                                        echo count($productos) . ' ' . (count($productos) === 1 ? 'producto' : 'productos');
                                                        ?>
                                                    </td>
                                                    <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                                    <td>
                                                        <?php 
                                                        $estado_class = [
                                                            'pendiente' => 'warning',
                                                            'procesando' => 'info',
                                                            'enviado' => 'primary',
                                                            'entregado' => 'success',
                                                            'cancelado' => 'danger'
                                                        ][$pedido['estado']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $estado_class; ?>">
                                                            <?php echo ucfirst($pedido['estado']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="pedidos/ver.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            Ver
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?= BASE_URL ?>/vendedor/pedidos" class="btn btn-sm btn-outline-primary">Ver todos los pedidos</a>
            </div>
        </div>
    </div>
    
    <!-- Productos con Bajo Stock -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Bajo Stock</h5>
                    <a href="/Silco/vendedor/productos?stock=bajo" class="btn btn-sm btn-outline-warning">Ver todos</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($productos_bajo_stock)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5>¡Todo en orden!</h5>
                        <p class="text-muted">No hay productos con bajo stock.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($productos_bajo_stock as $producto): 
                            $porcentaje = min(100, ($producto['stock'] / 5) * 100);
                            $clase = $producto['stock'] <= 2 ? 'danger' : 'warning';
                        ?>
                            <div class="list-group-item border-0 px-0 py-3">
                                <div class="d-flex align-items-center mb-2">
                                    <?php if (!empty($producto['imagen_principal'])): ?>
                                        <img src="<?= htmlspecialchars($producto['imagen_principal']) ?>" 
                                             class="rounded me-3" width="40" height="40" 
                                             alt="<?= htmlspecialchars($producto['nombre']) ?>">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?= htmlspecialchars($producto['nombre']) ?></h6>
                                        <small class="text-muted">Stock: <?= $producto['stock'] ?> unidades</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="setProductId(<?= $producto['id'] ?>, '<?= addslashes($producto['nombre']) ?>', <?= $producto['stock'] ?>)"
                                            data-bs-toggle="modal" data-bs-target="#actualizarStockModal">
                                        <i class="fas fa-plus"></i> Stock
                                    </button>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div class="progress-bar bg-<?= $clase ?>" 
                                         role="progressbar" 
                                         style="width: <?= $porcentaje ?>%" 
                                         aria-valuenow="<?= $porcentaje ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="/Silco/vendedor/productos?stock=bajo" class="btn btn-sm btn-outline-warning">Gestionar stock</a>
            </div>
        </div>
    </div>
                                            </tr>';
                                    }
                                    
                                    echo '      </tbody>
                                        </table>
                                    </div>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-warning">No se pudieron cargar los productos con bajo stock.</div>';
                                error_log("Error al cargar productos con bajo stock: " . $e->getMessage());
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de Estadísticas -->
            <div class="row g-4 mb-4">
                <!-- Total de Productos -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-box fa-lg"></i>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary">Total</span>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($total_productos); ?></h3>
                            <p class="text-muted mb-0">Productos</p>
                        </div>
                    </div>
                </div>

                <!-- Total de Pedidos -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-circle bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-shopping-cart fa-lg"></i>
                                </div>
                                <span class="badge bg-success bg-opacity-10 text-success">Activos</span>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($total_pedidos); ?></h3>
                            <p class="text-muted mb-0">Pedidos</p>
                        </div>
                    </div>
                </div>

                <!-- Ingresos Totales -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-circle bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-dollar-sign fa-lg"></i>
                                </div>
                                <span class="badge bg-info bg-opacity-10 text-info">30 días</span>
                            </div>
                            <h3 class="mb-1">$<?php echo number_format($ingresos, 2); ?></h3>
                            <p class="text-muted mb-0">Ingresos</p>
                        </div>
                    </div>
                </div>

                <!-- Productos Agotados -->
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="icon-circle bg-danger bg-opacity-10 text-danger">
                                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                                </div>
                                <span class="badge bg-danger bg-opacity-10 text-danger">Alerta</span>
                            </div>
                            <h3 class="mb-1"><?php echo number_format($agotados); ?></h3>
                            <p class="text-muted mb-0">Agotados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Contenido Principal -->
            <div class="row g-4">
                <!-- Últimos Pedidos -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Últimos Pedidos</h5>
                                <a href="pedidos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ultimos_pedidos)): ?>
                                <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-4">No hay pedidos recientes</p>
                                 
                                <!-- Tarjeta mejorada de Bajo Stock -->
                                <div class="card border-warning shadow-sm mb-4">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">Productos con bajo stock</h6>
                                                    <div class="d-flex align-items-center">
                                                        <span class="h4 mb-0 me-2"><?php echo $bajo_stock; ?></span>
                                                        <span class="text-muted small">producto<?php echo $bajo_stock != 1 ? 's' : ''; ?></span>
                                                        <?php if ($bajo_stock > 0): ?>
                                                            <span class="badge bg-danger ms-2">¡Revisar!</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">Menos de 5 unidades en stock</small>
                                                </div>
                                            </div>
                                            <a href="productos.php?stock=bajo" class="btn btn-sm btn-outline-warning">
                                                Ver <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                        
                                        <?php if (!empty($productos_bajo_stock)): ?>
                                            <div class="mt-3 pt-3 border-top">
                                                <h6 class="small text-uppercase text-muted mb-2">Productos críticos</h6>
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($productos_bajo_stock as $producto): ?>
                                                        <a href="editar-producto.php?id=<?= $producto['id'] ?>" class="list-group-item list-group-item-action border-0 px-0 py-2">
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($producto['imagen_principal'])): ?>
                                                                    <img src="<?= htmlspecialchars($producto['imagen_principal']) ?>" 
                                                                         class="rounded me-2" width="32" height="32" 
                                                                         alt="<?= htmlspecialchars($producto['nombre']) ?>">
                                                                <?php else: ?>
                                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" 
                                                                         style="width: 32px; height: 32px;">
                                                                        <i class="fas fa-box text-muted"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="flex-grow-1 text-truncate">
                                                                    <?= htmlspecialchars($producto['nombre']) ?>
                                                                </div>
                                                                <span class="badge bg-<?= $producto['stock'] < 3 ? 'danger' : 'warning' ?>-subtle text-<?= $producto['stock'] < 3 ? 'danger' : 'warning' ?> ms-2">
                                                                    <?= $producto['stock'] ?> uds.
                                                                </span>
                                                            </div>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Fin de tarjeta mejorada -->
                            </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Pedido #</th>
                                                <th>Fecha</th>
                                                <th>Productos</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ultimos_pedidos as $pedido): 
                                                $estado_clase = [
                                                    'pendiente' => 'warning',
                                                    'procesando' => 'info',
                                                    'enviado' => 'primary',
                                                    'entregado' => 'success',
                                                    'cancelado' => 'danger'
                                                ][$pedido['estado']] ?? 'secondary';
                                            ?>
                                                <tr>
                                                    <td>#<?= $pedido['id'] ?></td>
                                                    <td><?= date('d/m/Y', strtotime($pedido['fecha_creacion'])) ?></td>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 200px;" 
                                                             data-bs-toggle="tooltip" 
                                                             title="<?= htmlspecialchars($pedido['productos']) ?>">
                                                            <?= htmlspecialchars($pedido['productos']) ?>
                                                        </div>
                                                    </td>
                                                    <td>$<?= number_format($pedido['total'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $estado_clase ?> text-capitalize">
                                                            <?= $pedido['estado'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="<?= BASE_URL ?>/vendedor/pedidos/ver/<?= $pedido['id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <div class="card-footer bg-white border-0">
                                <a href="pedidos.php" class="btn btn-sm btn-outline-primary">Ver todos los pedidos</a>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para actualizar stock -->
<div class="modal fade" id="actualizarStockModal" tabindex="-1" aria-labelledby="actualizarStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actualizarStockModalLabel">Actualizar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formActualizarStock" method="POST" action="actualizar_stock.php">
                <div class="modal-body">
                    <input type="hidden" name="producto_id" id="producto_id">
                    <div class="mb-3">
                        <label for="producto_nombre" class="form-label">Producto</label>
                        <input type="text" class="form-control" id="producto_nombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="stock_actual" class="form-label">Stock Actual</label>
                        <input type="number" class="form-control" id="stock_actual" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="cantidad_agregar" class="form-label">Cantidad a agregar</label>
                        <input type="number" class="form-control" id="cantidad_agregar" name="cantidad" min="1" required>
                        <div class="form-text">Ingrese la cantidad que desea agregar al stock actual.</div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        El nuevo stock será: <span id="nuevo_stock" class="fw-bold">0</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toast-container" class="toast-container"></div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Mostrar notificaciones
    <?php if (isset($_SESSION['success'])): ?>
        toastr.success('<?php echo addslashes($_SESSION['success']); ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        toastr.error('<?php echo addslashes($_SESSION['error']); ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    // Función para el modal de actualizar stock
    function setProductId(id, nombre, stockActual) {
        document.getElementById('producto_id').value = id;
        document.getElementById('producto_nombre').value = nombre;
        document.getElementById('stock_actual').value = stockActual;
        document.getElementById('cantidad_agregar').value = '';
        document.getElementById('nuevo_stock').textContent = stockActual;
        
        // Actualizar el nuevo stock cuando cambie la cantidad
        document.getElementById('cantidad_agregar').addEventListener('input', function() {
            const cantidad = parseInt(this.value) || 0;
            const nuevoStock = parseInt(stockActual) + cantidad;
            document.getElementById('nuevo_stock').textContent = nuevoStock;
        });
    }
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Toggle sidebar en móviles
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.dashboard-sidebar').classList.toggle('show');
    });
    
    // Cerrar sidebar al hacer clic en un enlace en móviles
    document.querySelectorAll('.dashboard-nav a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                document.querySelector('.dashboard-sidebar').classList.remove('show');
            }
        });
</script>
</body>
</html>
            const sidebar = document.querySelector('.dashboard-sidebar');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('sidebar-open');
                    sidebar.classList.toggle('show');
                    
                    if (document.body.classList.contains('sidebar-open')) {
                        sidebarOverlay.classList.add('show');
                    } else {
                        setTimeout(() => {
                            sidebarOverlay.classList.remove('show');
                        }, 300);
                    }
                });
            }
            
            // Close sidebar when clicking on overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    document.body.classList.remove('sidebar-open');
                    if (sidebar) sidebar.classList.remove('show');
                    this.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
