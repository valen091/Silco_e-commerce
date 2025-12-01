<?php
require_once __DIR__ . '/includes/header.php';

// Get featured products
$featured_query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM productos p 
                  JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.activo = 1 
                  ORDER BY p.fecha_creacion DESC 
                  LIMIT 8";
$featured_stmt = $db->query($featured_query);
$featured_products = $featured_stmt->fetchAll();

// Get products on sale
$sale_query = "SELECT p.*, c.nombre as categoria_nombre 
              FROM productos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.precio_oferta IS NOT NULL AND p.activo = 1 
              ORDER BY (p.precio - p.precio_oferta) / p.precio * 100 DESC 
              LIMIT 4";
$sale_stmt = $db->query($sale_query);
$sale_products = $sale_stmt->fetchAll();
?>

<!-- Hero Section -->
<div class="hero mb-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Bienvenido a Silco</h1>
        <p class="lead mb-4">Descubre las mejores ofertas en productos de calidad</p>
        <a href="#ofertas" class="btn btn-primary btn-lg px-4 me-2">Ver ofertas</a>
        <a href="#categorias" class="btn btn-outline-light btn-lg px-4">Explorar categorías</a>
    </div>
</div>

<!-- Featured Products -->
<section class="mb-5">
    <div class="container">
        <h2 class="section-title">Productos Destacados</h2>
        <div class="row">
            <?php if (count($featured_products) > 0): ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-md-3 col-6 mb-4">
                        <div class="card h-100">
                            <?php if ($product['precio_oferta']): ?>
                                <span class="badge bg-danger badge-offer">
                                    -<?= number_format((($product['precio'] - $product['precio_oferta']) / $product['precio']) * 100, 0) ?>%
                                </span>
                            <?php endif; ?>
                            <button class="btn btn-link favorite-btn position-absolute top-0 start-0 p-2" data-id="<?= $product['id'] ?>" data-bs-toggle="tooltip" title="Añadir a favoritos">
                                <i class="bi bi-heart fs-5"></i>
                            </button>
                            <img src="<?= htmlspecialchars($product['imagen_principal'] ?: 'assets/img/placeholder.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['nombre']) ?>">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2"><?= htmlspecialchars($product['categoria_nombre']) ?></span>
                                <h5 class="card-title"><?= htmlspecialchars($product['nombre']) ?></h5>
                                <p class="card-text text-truncate"><?= htmlspecialchars($product['descripcion']) ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="price">$<?= number_format($product['precio_oferta'] ?: $product['precio'], 2) ?></span>
                                        <?php if ($product['precio_oferta']): ?>
                                            <span class="old-price">$<?= number_format($product['precio'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary add-to-cart" data-id="<?= $product['id'] ?>">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <a href="/Silco/producto.php?id=<?= $product['id'] ?>" class="stretched-link"></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay productos destacados disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section id="categorias" class="mb-5">
    <div class="container">
        <h2 class="section-title">Categorías</h2>
        <div class="row g-4">
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-2 col-4">
                        <a href="/Silco/categoria.php?id=<?= $category['id'] ?>" class="text-decoration-none text-dark">
                            <div class="card h-100 text-center p-3">
                                <i class="bi bi-tag fs-1 mb-2"></i>
                                <h6 class="mb-0"><?= htmlspecialchars($category['nombre']) ?></h6>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay categorías disponibles.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Special Offers -->
<?php if (!empty($sale_products)): ?>
<section id="ofertas" class="mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-title mb-0">Ofertas Especiales</h2>
            <a href="/Silco/ofertas.php" class="btn btn-outline-primary btn-sm">Ver todas las ofertas</a>
        </div>
        <div class="row">
            <?php foreach ($sale_products as $product): ?>
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100">
                        <span class="badge bg-danger badge-offer">
                            -<?= number_format((($product['precio'] - $product['precio_oferta']) / $product['precio']) * 100, 0) ?>%
                        </span>
                        <img src="<?= htmlspecialchars($product['imagen_principal'] ?: 'assets/img/placeholder.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['nombre']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['nombre']) ?></h5>
                            <div>
                                <span class="price">$<?= number_format($product['precio_oferta'], 2) ?></span>
                                <span class="old-price">$<?= number_format($product['precio'], 2) ?></span>
                            </div>
                            <button class="btn btn-primary w-100 mt-2 add-to-cart" data-id="<?= $product['id'] ?>">
                                <i class="bi bi-cart-plus"></i> Añadir al carrito
                            </button>
                        </div>
                        <a href="/Silco/producto.php?id=<?= $product['id'] ?>" class="stretched-link"></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="bg-light py-5 mb-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-truck fs-1 text-primary mb-3"></i>
                    <h5>Envío Rápido</h5>
                    <p class="mb-0">Entregas rápidas a todo el país</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-shield-check fs-1 text-primary mb-3"></i>
                    <h5>Pago Seguro</h5>
                    <p class="mb-0">Múltiples métodos de pago</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="p-4 bg-white rounded shadow-sm">
                    <i class="bi bi-headset fs-1 text-primary mb-3"></i>
                    <h5>Soporte 24/7</h5>
                    <p class="mb-0">Estamos aquí para ayudarte</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
