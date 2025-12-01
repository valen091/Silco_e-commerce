<?php
require_once '../includes/functions.php';

// Verificar autenticación y rol de vendedor
if (!isLoggedIn() || !isVendedor()) {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

// Obtener estadísticas del vendedor
try {
    // Total de productos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ?");
    $stmt->execute([$user_id]);
    $total_productos = $stmt->fetch()['total'];
    
    // Total de pedidos
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT p.id) as total 
                           FROM pedidos p 
                           JOIN items_pedido ip ON p.id = ip.pedido_id 
                           JOIN productos pr ON ip.producto_id = pr.id 
                           WHERE pr.vendedor_id = ?");
    $stmt->execute([$user_id]);
    $total_pedidos = $stmt->fetch()['total'];
    
    // Ingresos totales
    $stmt = $conn->prepare("SELECT COALESCE(SUM(ip.cantidad * ip.precio_unitario), 0) as total 
                           FROM items_pedido ip 
                           JOIN productos p ON ip.producto_id = p.id 
                           JOIN pedidos ped ON ip.pedido_id = ped.id 
                           WHERE p.vendedor_id = ? AND ped.estado != 'cancelado'");
    $stmt->execute([$user_id]);
    $ingresos = $stmt->fetch()['total'];
    
    // Productos con bajo stock
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ? AND stock <= 5 AND stock > 0");
    $stmt->execute([$user_id]);
    $bajo_stock = $stmt->fetch()['total'];
    
    // Productos agotados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE vendedor_id = ? AND stock = 0");
    $stmt->execute([$user_id]);
    $agotados = $stmt->fetch()['total'];
    
    // Últimos pedidos
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
    
} catch (PDOException $e) {
    error_log("Error en el panel del vendedor: " . $e->getMessage());
    $error = "Error al cargar los datos del panel. Por favor, inténtalo de nuevo más tarde.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Vendedor - Silco</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-sidebar">
                <div class="user-panel">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Vendedor'); ?></h4>
                        <small>Vendedor</small>
                    </div>
                </div>
                
                <nav class="dashboard-nav">
                    <ul>
                        <li class="active">
                            <a href="panel.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        </li>
                        <li>
                            <a href="productos.php"><i class="fas fa-box"></i> Productos</a>
                        </li>
                        <li>
                            <a href="pedidos.php"><i class="fas fa-shopping-cart"></i> Pedidos</a>
                        </li>
                        <li>
                            <a href="resenas.php"><i class="fas fa-star"></i> Reseñas</a>
                        </li>
                        <li>
                            <a href="estadisticas.php"><i class="fas fa-chart-line"></i> Estadísticas</a>
                        </li>
                        <li>
                            <a href="configuracion.php"><i class="fas fa-cog"></i> Configuración</a>
                        </li>
                        <li>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <div class="dashboard-main">
                <div class="dashboard-header">
                    <h1>Panel de Vendedor</h1>
                    <div class="header-actions">
                        <a href="nuevo-producto.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Producto
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Resumen de estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_productos; ?></h3>
                            <p>Productos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_pedidos; ?></h3>
                            <p>Pedidos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$<?php echo number_format($ingresos, 2); ?></h3>
                            <p>Ingresos Totales</p>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $bajo_stock; ?></h3>
                            <p>Productos con bajo stock</p>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos pedidos -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Últimos Pedidos</h2>
                        <a href="pedidos.php" class="btn btn-link">Ver todos</a>
                    </div>
                    
                    <?php if (!empty($ultimos_pedidos)): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Pedido</th>
                                        <th>Fecha</th>
                                        <th>Productos</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos_pedidos as $pedido): ?>
                                        <tr>
                                            <td>#<?php echo $pedido['id']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?></td>
                                            <td><?php echo htmlspecialchars($pedido['productos']); ?></td>
                                            <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($pedido['estado']); ?>">
                                                    <?php echo ucfirst($pedido['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="ver-pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-icon">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>No hay pedidos recientes</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Productos con bajo stock -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Productos con Bajo Stock</h2>
                        <a href="productos.php?stock=bajo" class="btn btn-link">Ver todos</a>
                    </div>
                    
                    <?php
                    try {
                        $stmt = $conn->prepare("SELECT id, nombre, stock, precio FROM productos 
                                              WHERE vendedor_id = ? AND stock <= 5 AND stock > 0 
                                              ORDER BY stock ASC LIMIT 5");
                        $stmt->execute([$user_id]);
                        $productos_bajo_stock = $stmt->fetchAll();
                        
                        if (!empty($productos_bajo_stock)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Stock</th>
                                            <th>Precio</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos_bajo_stock as $producto): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                <td>
                                                    <span class="badge badge-warning">
                                                        <?php echo $producto['stock']; ?> unidades
                                                    </span>
                                                </td>
                                                <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                                <td>
                                                    <a href="editar-producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-icon">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>¡Todo en orden! No hay productos con bajo stock.</p>
                            </div>
                        <?php endif; 
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-warning">Error al cargar productos con bajo stock.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
