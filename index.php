<?php
// Iniciar la sesión al principio del script
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Session.php';

// Inicializar la sesión
$session = Session::getInstance();

// Debug: Verificar estado de la sesión
error_log('Index - Session status: ' . session_status());
error_log('Index - Session ID: ' . session_id());
error_log('Index - Session data: ' . print_r($_SESSION, true));

error_log('Index - isLoggedIn: ' . ($session->isLoggedIn() ? 'true' : 'false'));

require_once __DIR__ . '/includes/header.php';

// Obtener categorías únicas
$categories_query = "SELECT DISTINCT id, nombre FROM categorias ORDER BY nombre";
$categories_stmt = $db->query($categories_query);
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured products
$featured_query = "SELECT p.*, c.nombre as categoria_nombre,
                  (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal
                  FROM productos p 
                  JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.activo = 1 
                  ORDER BY p.fecha_creacion DESC 
                  LIMIT 8";
$featured_stmt = $db->query($featured_query);
$featured_products = $featured_stmt->fetchAll();

// Get products on sale
$sale_query = "SELECT p.*, c.nombre as categoria_nombre,
              (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal
              FROM productos p 
              JOIN categorias c ON p.categoria_id = c.id 
              WHERE p.precio_oferta IS NOT NULL AND p.activo = 1 
              ORDER BY (p.precio - p.precio_oferta) / p.precio * 100 DESC 
              LIMIT 4";
$sale_stmt = $db->query($sale_query);
$sale_products = $sale_stmt->fetchAll();
?>

<!-- Filter Sidebar Toggle Button (Mobile) -->
<div class="container-fluid mt-3 d-lg-none">
    <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterSidebar">
        <i class="bi bi-funnel"></i> Filtros
    </button>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Filter Sidebar -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <!-- Price Range -->
                    <div class="mb-4">
                        <h6>Rango de Precio</h6>
                        <div class="d-flex justify-content-between">
                            <input type="number" class="form-control form-control-sm me-2" id="minPrice" placeholder="Mín">
                            <input type="number" class="form-control form-control-sm" id="maxPrice" placeholder="Máx">
                        </div>
                    </div>
                    
                    <!-- Categories -->
                    <div class="mb-4">
                        <h6>Categorías</h6>
                        <div class="form-check">
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check">
                                    <input class="form-check-input category-filter" type="checkbox" value="<?= $category['id'] ?>" id="cat-<?= $category['id'] ?>">
                                    <label class="form-check-label" for="cat-<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['nombre']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Availability -->
                    <div class="mb-4">
                        <h6>Disponibilidad</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="in_stock" id="inStock">
                            <label class="form-check-label" for="inStock">
                                En stock
                            </label>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary w-100" id="applyFilters">Aplicar Filtros</button>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Hero Section -->
            <div class="hero mb-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Bienvenido a Silco</h1>
        <p class="lead mb-4">Descubre los productos de mayor calidad</p>
        <a href="#categorias" class="btn btn-outline-light btn-lg px-4">Explorar categorías</a>
    </div>
</div>

            </div> <!-- Close hero -->
            
            <!-- Featured Products -->
            <section class="featured-products mb-5">
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
                            <button class="btn btn-link favorite-btn position-absolute top-0 start-0 p-2" 
                                    data-product-id="<?= $product['id'] ?>" 
                                    data-bs-toggle="tooltip" 
                                    title="Añadir a favoritos">
                                <i class="bi bi-heart fs-5"></i>
                            </button>
                            <img src="<?= formatImageUrl($product['imagen_principal'] ?? '') ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($product['nombre']) ?>"
                                 onerror="this.onerror=null; this.src='<?= APP_URL ?>/assets/img/placeholder.jpg';">
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
            <?php foreach ($sale_products as $sale_product): ?>
                <div class="col-md-3 col-6 mb-4">
                    <div class="card h-100">
                        <span class="badge bg-danger badge-offer">
                            -<?= number_format((($sale_product['precio'] - $sale_product['precio_oferta']) / $sale_product['precio']) * 100, 0) ?>%
                        </span>
                        <img src="<?= formatImageUrl($sale_product['imagen_principal'] ?? '') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($sale_product['nombre']) ?>"
                             onerror="this.onerror=null; this.src='<?= APP_URL ?>/assets/img/placeholder.jpg';">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($sale_product['nombre']) ?></h5>
                            <div>
                                <span class="price">$<?= number_format($sale_product['precio_oferta'], 2) ?></span>
                                <span class="old-price">$<?= number_format($sale_product['precio'], 2) ?></span>
                            </div>
                            <button class="btn btn-primary w-100 mt-2 add-to-cart" data-id="<?= $sale_product['id'] ?>">
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

                </div> <!-- Close container -->
            </section>
        </div> <!-- Close col-lg-9 -->
    </div> <!-- Close row -->
</div> <!-- Close container-fluid -->

<!-- Mobile Offcanvas Filter -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Filtros</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Price Range -->
        <div class="mb-4">
            <h6>Rango de Precio</h6>
            <div class="d-flex justify-content-between">
                <input type="number" class="form-control form-control-sm me-2" id="mobileMinPrice" placeholder="Mín">
                <input type="number" class="form-control form-control-sm" id="mobileMaxPrice" placeholder="Máx">
            </div>
        </div>
        
        <!-- Categories -->
        <div class="mb-4">
            <h6>Categorías</h6>
            <div class="form-check">
                <?php foreach ($categories as $category): ?>
                    <div class="form-check">
                        <input class="form-check-input mobile-category-filter" type="checkbox" 
                               value="<?= $category['id'] ?>" id="mobile-cat-<?= $category['id'] ?>">
                        <label class="form-check-label" for="mobile-cat-<?= $category['id'] ?>">
                            <?= htmlspecialchars($category['nombre']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Availability -->
        <div class="mb-4">
            <h6>Disponibilidad</h6>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="in_stock" id="mobileInStock">
                <label class="form-check-label" for="mobileInStock">
                    En stock
                </label>
            </div>
        </div>
        
        <button class="btn btn-primary w-100" id="applyMobileFilters">Aplicar Filtros</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Current filters state
    let currentFilters = {
        minPrice: null,
        maxPrice: null,
        inStock: false,
        categories: []
    };

    // Format price
    function formatPrice(price) {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS'
        }).format(price);
    }

    // Load products with current filters
    function loadFilteredProducts() {
        const productsContainer = document.querySelector('.featured-products .row');
        if (!productsContainer) return;
        
        const loadingHtml = `
            <div class="col-12 text-center my-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando productos...</p>
            </div>`;
        
        // Show loading state
        productsContainer.innerHTML = loadingHtml;

        // Build query string
        const params = new URLSearchParams();
        if (currentFilters.minPrice !== null) params.append('min_price', currentFilters.minPrice);
        if (currentFilters.maxPrice !== null) params.append('max_price', currentFilters.maxPrice);
        if (currentFilters.inStock) params.append('in_stock', 'true');
        if (currentFilters.categories.length > 0) {
            params.append('categories', JSON.stringify(currentFilters.categories));
        }
        params.append('page', 1); // Always load first page when applying filters

        // Fetch products
        fetch(`/Silco/backend/products/filter.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar los productos');
                }
                
                // Render products
                if (data.products.length === 0) {
                    productsContainer.innerHTML = `
                        <div class="col-12 text-center my-5">
                            <i class="bi bi-search display-4 text-muted"></i>
                            <h4 class="mt-3">No se encontraron productos</h4>
                            <p class="text-muted">Intenta con otros filtros de búsqueda</p>
                            <button class="btn btn-primary mt-2" onclick="clearFilters()">Limpiar filtros</button>
                        </div>`;
                    return;
                }

                let productsHtml = '';
                data.products.forEach(product => {
                    const price = product.precio_oferta || product.precio;
                    const oldPrice = product.precio_oferta ? 
                        `<span class="old-price">${formatPrice(product.precio)}</span>` : '';
                    const discountBadge = product.precio_oferta ? 
                        `<span class="badge bg-danger badge-offer">
                            -${Math.round((product.precio - product.precio_oferta) / product.precio * 100)}%
                        </span>` : '';
                    
                    productsHtml += `
                    <div class="col-md-3 col-6 mb-4">
                        <div class="card h-100">
                            ${discountBadge}
                            <button class="btn btn-link favorite-btn position-absolute top-0 start-0 p-2" 
                                    data-product-id="${product.id}" 
                                    data-bs-toggle="tooltip" 
                                    title="Añadir a favoritos">
                                <i class="bi bi-heart fs-5"></i>
                            </button>
                            <img src="${product.imagen_principal || '/Silco/assets/img/placeholder.jpg'}" 
                                 class="card-img-top" 
                                 alt="${product.nombre}"
                                 onerror="this.onerror=null; this.src='/Silco/assets/img/placeholder.jpg'">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2">${product.categoria_nombre || 'Sin categoría'}</span>
                                <h5 class="card-title">${product.nombre}</h5>
                                <p class="card-text text-truncate">${product.descripcion || ''}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="price">${formatPrice(price)}</span>
                                        ${oldPrice}
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary add-to-cart" data-id="${product.id}">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <a href="/Silco/producto.php?id=${product.id}" class="stretched-link"></a>
                        </div>
                    </div>`;
                });

                productsContainer.innerHTML = productsHtml;
                
                // Update pagination if needed
                updatePagination(data.pagination);
            })
            .catch(error => {
                console.error('Error:', error);
                productsContainer.innerHTML = `
                    <div class="col-12 text-center my-5">
                        <i class="bi bi-exclamation-triangle text-danger display-4"></i>
                        <h4 class="mt-3">Error al cargar los productos</h4>
                        <p class="text-muted">${error.message || 'Intenta nuevamente más tarde'}</p>
                        <button class="btn btn-primary mt-2" onclick="window.location.reload()">Recargar</button>
                    </div>`;
            });
    }

    // Update pagination controls
    function updatePagination(pagination) {
        const paginationEl = document.getElementById('pagination');
        if (!paginationEl) return;
        
        let paginationHtml = '';
        const totalPages = pagination.total_pages;
        const currentPage = pagination.current_page;
        
        if (totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }
        
        // Previous button
        paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Anterior">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Siguiente">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>`;
        
        paginationEl.innerHTML = paginationHtml;
        
        // Add event listeners to pagination links
        document.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (!isNaN(page) && page >= 1 && page <= totalPages) {
                    currentFilters.page = page;
                    loadFilteredProducts();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });
    }

    // Clear all filters
    window.clearFilters = function() {
        // Reset filter inputs
        document.getElementById('minPrice').value = '';
        document.getElementById('maxPrice').value = '';
        document.getElementById('inStock').checked = false;
        document.querySelectorAll('.category-filter').forEach(cb => cb.checked = false);
        
        // Reset mobile filters
        document.getElementById('mobileMinPrice').value = '';
        document.getElementById('mobileMaxPrice').value = '';
        document.getElementById('mobileInStock').checked = false;
        document.querySelectorAll('.mobile-category-filter').forEach(cb => cb.checked = false);
        
        // Reset current filters
        currentFilters = {
            minPrice: null,
            maxPrice: null,
            inStock: false,
            categories: []
        };
        
        // Reload products
        loadFilteredProducts();
    };

    // Apply filters from form
    function applyFilters() {
        currentFilters = {
            minPrice: document.getElementById('minPrice').value || null,
            maxPrice: document.getElementById('maxPrice').value || null,
            inStock: document.getElementById('inStock').checked,
            categories: Array.from(document.querySelectorAll('.category-filter:checked')).map(cb => cb.value)
        };
        
        loadFilteredProducts();
    }

    // Event listeners for desktop filters
    document.getElementById('applyFilters')?.addEventListener('click', applyFilters);
    
    // Event listeners for mobile filters
    document.getElementById('applyMobileFilters')?.addEventListener('click', function() {
        // Sync mobile filters with desktop
        document.getElementById('minPrice').value = document.getElementById('mobileMinPrice').value;
        document.getElementById('maxPrice').value = document.getElementById('mobileMaxPrice').value;
        document.getElementById('inStock').checked = document.getElementById('mobileInStock').checked;
        
        // Sync category checkboxes
        document.querySelectorAll('.mobile-category-filter').forEach(mobileCb => {
            const id = mobileCb.id.replace('mobile-', '');
            const desktopCb = document.getElementById(id);
            if (desktopCb) {
                desktopCb.checked = mobileCb.checked;
            }
        });
        
        // Apply filters and close offcanvas
        applyFilters();
        const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('filterSidebar'));
        offcanvas.hide();
    });
    
    // Sync mobile and desktop filters when offcanvas is shown
    document.getElementById('filterSidebar')?.addEventListener('show.bs.offcanvas', function() {
        document.getElementById('mobileMinPrice').value = document.getElementById('minPrice').value;
        document.getElementById('mobileMaxPrice').value = document.getElementById('maxPrice').value;
        document.getElementById('mobileInStock').checked = document.getElementById('inStock').checked;
        
        // Sync category checkboxes
        document.querySelectorAll('.category-filter').forEach(desktopCb => {
            const id = 'mobile-' + desktopCb.id;
            const mobileCb = document.getElementById(id);
            if (mobileCb) {
                mobileCb.checked = desktopCb.checked;
            }
        });
    });
    
    // Initialize filters
    loadFilteredProducts();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
