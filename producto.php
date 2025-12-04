<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/functions.php';

// Inicializar la sesión
$session = Session::getInstance();

// Verificar si se proporcionó un ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

$producto_id = (int)$_GET['id'];
$db = Database::getInstance();

// Obtener información del producto
$query = "SELECT p.*, c.nombre as categoria_nombre 
          FROM productos p 
          JOIN categorias c ON p.categoria_id = c.id 
          WHERE p.id = ? AND p.activo = 1";
$stmt = $db->prepare($query);
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();

// Si el producto no existe o no está activo, mostrar error 404
if (!$producto) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

// Obtener imágenes del producto
$query = "SELECT * FROM imagenes_producto 
          WHERE producto_id = ? 
          ORDER BY orden ASC";
$stmt = $db->prepare($query);
$stmt->execute([$producto_id]);
$imagenes = $stmt->fetchAll();

// Si no hay imágenes, usar una imagen por defecto
if (empty($imagenes)) {
    $imagenes[] = ['imagen_url' => 'assets/img/placeholder.jpg'];
}

// Obtener productos relacionados
$query = "SELECT p.*, 
          (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen_principal
          FROM productos p 
          WHERE p.categoria_id = ? AND p.id != ? AND p.activo = 1 
          ORDER BY RAND() 
          LIMIT 4";
$stmt = $db->prepare($query);
$stmt->execute([$producto['categoria_id'], $producto_id]);
$productos_relacionados = $stmt->fetchAll();

// Establecer el título de la página
$page_title = $producto['nombre'] . ' - ' . SITE_NAME;

// Incluir el encabezado
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/Silco/">Inicio</a></li>
            <li class="breadcrumb-item"><a href="/Silco/categoria.php?id=<?= $producto['categoria_id'] ?>"><?= htmlspecialchars($producto['categoria_nombre']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($producto['nombre']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Galería de imágenes -->
        <div class="col-lg-6">
            <div class="product-gallery">
                <div class="main-image mb-3">
                    <img src="<?= htmlspecialchars($imagenes[0]['imagen_url']) ?>" 
                         class="img-fluid rounded" 
                         alt="<?= htmlspecialchars($producto['nombre']) ?>"
                         id="mainProductImage">
                </div>
                <?php if (count($imagenes) > 1): ?>
                <div class="thumbnail-container d-flex flex-wrap gap-2">
                    <?php foreach ($imagenes as $index => $imagen): ?>
                        <div class="thumbnail" style="width: 80px; cursor: pointer;">
                            <img src="<?= htmlspecialchars($imagen['imagen_url']) ?>" 
                                 class="img-thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                                 alt=""
                                 onclick="document.getElementById('mainProductImage').src = this.src;
                                          document.querySelectorAll('.thumbnail img').forEach(img => img.classList.remove('border-primary'));
                                          this.classList.add('border-primary');">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información del producto -->
        <div class="col-lg-6">
            <h1 class="mb-3"><?= htmlspecialchars($producto['nombre']) ?></h1>
            
            <div class="d-flex align-items-center mb-3">
                <?php if ($producto['precio_oferta']): ?>
                    <span class="h3 text-danger me-2">$<?= number_format($producto['precio_oferta'], 2) ?></span>
                    <span class="text-muted text-decoration-line-through me-2">$<?= number_format($producto['precio'], 2) ?></span>
                    <span class="badge bg-danger">
                        <?= number_format((($producto['precio'] - $producto['precio_oferta']) / $producto['precio']) * 100, 0) ?>% OFF
                    </span>
                <?php else: ?>
                    <span class="h3">$<?= number_format($producto['precio'], 2) ?></span>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <div class="text-warning me-2">
                        <?php
                        $rating = $producto['valoracion'] ?? 5;
                        $fullStars = floor($rating);
                        $hasHalfStar = $rating - $fullStars >= 0.5;
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        
                        // Estrellas llenas
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="bi bi-star-fill"></i>';
                        }
                        
                        // Media estrella
                        if ($hasHalfStar) {
                            echo '<i class="bi bi-star-half"></i>';
                        }
                        
                        // Estrellas vacías
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="bi bi-star"></i>';
                        }
                        ?>
                    </div>
                    <a href="#reseñas" class="text-decoration-none ms-2">
                        <span class="text-muted">(Ver reseñas)</span>
                    </a>
                </div>
            </div>

            <div class="mb-4">
                <h5>Descripción</h5>
                <p class="mb-0"><?= nl2br(htmlspecialchars($producto['descripcion'])) ?></p>
            </div>

            <?php if ($producto['stock'] > 0): ?>
                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle-fill"></i> En stock (<?= $producto['stock'] ?> disponibles)
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-4">
                    <i class="bi bi-exclamation-triangle-fill"></i> Agotado
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="cantidad" class="form-label">Cantidad</label>
                    <input type="number" class="form-control" id="cantidad" value="1" min="1" max="<?= $producto['stock'] ?>">
                </div>
                <div class="col-md-9 d-flex align-items-end">
                    <button class="btn btn-primary btn-lg w-100 add-to-cart" 
                            data-id="<?= $producto['id'] ?>"
                            <?= $producto['stock'] <= 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-cart-plus"></i> Añadir al carrito
                    </button>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="addToWishlist" data-id="<?= $producto['id'] ?>">
                    <i class="bi bi-heart"></i> Añadir a favoritos
                </button>
                <button class="btn btn-outline-secondary" id="shareProduct">
                    <i class="bi bi-share"></i> Compartir
                </button>
            </div>

            <hr class="my-4">

            <div class="d-flex flex-wrap gap-2">
                <div class="d-flex align-items-center me-3">
                    <i class="bi bi-truck me-2 text-muted"></i>
                    <small class="text-muted">Envío a todo el país</small>
                </div>
                <div class="d-flex align-items-center me-3">
                    <i class="bi bi-arrow-return-left me-2 text-muted"></i>
                    <small class="text-muted">Devolución gratuita</small>
                </div>
                <div class="d-flex align-items-center">
                    <i class="bi bi-shield-check me-2 text-muted"></i>
                    <small class="text-muted">Pago seguro</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de detalles adicionales -->
    <div class="row mt-5">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Detalles</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab" aria-controls="specs" aria-selected="false">Especificaciones</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reseñas</button>
                </li>
            </ul>
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="productTabsContent">
                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                    <?= nl2br(htmlspecialchars($producto['descripcion_larga'] ?? 'No hay detalles adicionales disponibles.')) ?>
                </div>
                <div class="tab-pane fade" id="specs" role="tabpanel" aria-labelledby="specs-tab">
                    <table class="table">
                        <tbody>
                            <tr>
                                <th scope="row" style="width: 200px;">Categoría</th>
                                <td><?= htmlspecialchars($producto['categoria_nombre']) ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Marca</th>
                                <td><?= htmlspecialchars($producto['marca'] ?? 'No especificada') ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Modelo</th>
                                <td><?= htmlspecialchars($producto['modelo'] ?? 'No especificado') ?></td>
                            </tr>
                            <tr>
                                <th scope="row">SKU</th>
                                <td><?= htmlspecialchars($producto['sku'] ?? 'No disponible') ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Peso</th>
                                <td><?= $producto['peso'] ? htmlspecialchars($producto['peso'] . ' kg') : 'No especificado' ?></td>
                            </tr>
                            <tr>
                                <th scope="row">Dimensiones</th>
                                <td>
                                    <?php if ($producto['largo'] && $producto['ancho'] && $producto['alto']): ?>
                                        <?= htmlspecialchars($producto['largo']) ?> x <?= htmlspecialchars($producto['ancho']) ?> x <?= htmlspecialchars($producto['alto']) ?> cm
                                    <?php else: ?>
                                        No especificadas
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
                    <div id="reseñas">
                        <h5>Reseñas de clientes</h5>
                        <p>Este producto aún no tiene reseñas. ¡Sé el primero en opinar!</p>
                        
                        <div class="mt-4">
                            <h6>Escribe una reseña</h6>
                            <form id="reviewForm">
                                <div class="mb-3">
                                    <label for="rating" class="form-label">Calificación</label>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star-fill star" data-rating="<?= $i ?>"></i>
                                        <?php endfor; ?>
                                        <input type="hidden" name="rating" id="rating" value="5" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="reviewTitle" class="form-label">Título</label>
                                    <input type="text" class="form-control" id="reviewTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="reviewText" class="form-label">Tu reseña</label>
                                    <textarea class="form-control" id="reviewText" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Enviar reseña</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos relacionados -->
    <?php if (!empty($productos_relacionados)): ?>
    <section class="mt-5">
        <h3 class="mb-4">Productos relacionados</h3>
        <div class="row">
            <?php foreach ($productos_relacionados as $relacionado): ?>
                <div class="col-lg-3 col-md-4 col-6 mb-4">
                    <div class="card h-100">
                        <?php if ($relacionado['precio_oferta']): ?>
                            <span class="badge bg-danger badge-offer">
                                -<?= number_format((($relacionado['precio'] - $relacionado['precio_oferta']) / $relacionado['precio']) * 100, 0) ?>%
                            </span>
                        <?php endif; ?>
                        <button class="btn btn-link favorite-btn position-absolute top-0 start-0 p-2" data-id="<?= $relacionado['id'] ?>" data-bs-toggle="tooltip" title="Añadir a favoritos">
                            <i class="bi bi-heart fs-5"></i>
                        </button>
                        <img src="<?= htmlspecialchars($relacionado['imagen_principal'] ?: 'assets/img/placeholder.jpg') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($relacionado['nombre']) ?>">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/Silco/producto.php?id=<?= $relacionado['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($relacionado['nombre']) ?>
                                </a>
                            </h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($relacionado['precio_oferta']): ?>
                                        <span class="price">$<?= number_format($relacionado['precio_oferta'], 2) ?></span>
                                        <span class="old-price">$<?= number_format($relacionado['precio'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="price">$<?= number_format($relacionado['precio'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-sm btn-outline-primary add-to-cart" data-id="<?= $relacionado['id'] ?>">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                        <a href="/Silco/producto.php?id=<?= $relacionado['id'] ?>" class="stretched-link"></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Script para manejar la galería de imágenes
const mainImage = document.getElementById('mainProductImage');
const thumbnails = document.querySelectorAll('.thumbnail img');

thumbnails.forEach(thumbnail => {
    thumbnail.addEventListener('click', function() {
        mainImage.src = this.src;
        thumbnails.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});

// Script para manejar las estrellas de calificación
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        ratingInput.value = rating;
        
        stars.forEach((s, index) => {
            if (index < rating) {
                s.classList.add('text-warning');
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill');
            } else {
                s.classList.remove('text-warning');
                s.classList.add('bi-star');
                s.classList.remove('bi-star-fill');
            }
        });
    });
});

// Script para manejar el formulario de reseñas
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Aquí iría el código para enviar la reseña al servidor
    alert('Gracias por tu reseña. Será publicada después de ser revisada.');
    this.reset();
});

// Script para manejar el botón de añadir al carrito
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const productId = this.getAttribute('data-id');
        const quantity = document.getElementById('cantidad')?.value || 1;
        
        // Aquí iría el código para añadir el producto al carrito
        fetch('/Silco/backend/cart/add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar el contador del carrito
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }
                
                // Mostrar notificación
                alert('Producto añadido al carrito');
            } else {
                alert('Error al añadir el producto al carrito: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al conectar con el servidor');
        });
    });
});

// Script para manejar el botón de añadir a favoritos
document.getElementById('addToWishlist')?.addEventListener('click', function() {
    const productId = this.getAttribute('data-id');
    
    // Aquí iría el código para añadir el producto a la lista de deseos
    fetch('/Silco/backend/wishlist/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Producto añadido a favoritos');
        } else {
            alert('Error al añadir a favoritos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al conectar con el servidor');
    });
});

// Script para el botón de compartir
document.getElementById('shareProduct')?.addEventListener('click', function() {
    if (navigator.share) {
        navigator.share({
            title: '<?= addslashes($producto['nombre']) ?>',
            text: 'Mira este producto: <?= addslashes($producto['nombre']) ?>',
            url: window.location.href,
        })
        .then(() => console.log('Contenido compartido exitosamente'))
        .catch((error) => console.log('Error al compartir:', error));
    } else {
        // Fallback para navegadores que no soportan la Web Share API
        const url = window.location.href;
        const tempInput = document.createElement('input');
        document.body.appendChild(tempInput);
        tempInput.value = url;
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        alert('¡Enlace copiado al portapapeles!');
    }
});
</script>
