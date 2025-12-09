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
$db = Database::getInstance()->getConnection();

// Verificar si el producto está en favoritos del usuario
$en_favoritos = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND producto_id = ?");
    $stmt->execute([$_SESSION['user_id'], $producto_id]);
    $en_favoritos = $stmt->fetch() !== false;
}

// Obtener información del producto incluyendo el vendedor
$query = "SELECT p.*, c.nombre as categoria_nombre, p.vendedor_id 
          FROM productos p 
          JOIN categorias c ON p.categoria_id = c.id 
          WHERE p.id = ? AND p.activo = 1";
$stmt = $db->prepare($query);
$stmt->execute([$producto_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si el usuario actual es el vendedor del producto
$es_vendedor_del_producto = false;
if (isset($_SESSION['user_id'])) {
    // Verificar si el usuario es el vendedor del producto
    if (isset($producto['vendedor_id']) && $_SESSION['user_id'] == $producto['vendedor_id']) {
        $es_vendedor_del_producto = true;
    }
    // Si el usuario es administrador, también puede editar
    else if (isset($_SESSION['es_admin']) && $_SESSION['es_admin']) {
        $es_vendedor_del_producto = true;
    }
}

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
    $imagenes[] = ['imagen_url' => APP_URL . '/assets/img/placeholder.jpg'];
} else {
    // Asegurarse de que todas las URLs de imágenes tengan la ruta completa
    foreach ($imagenes as &$imagen) {
        if (!empty($imagen['imagen_url']) && strpos($imagen['imagen_url'], 'http') !== 0) {
            $imagen['imagen_url'] = APP_URL . '/uploads/productos/' . basename($imagen['imagen_url']);
        }
    }
    unset($imagen); // Romper la referencia
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
$page_title = $producto['nombre'] . ' - ' . APP_NAME;

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
                    <?php 
                    $mainImage = $imagenes[0]['imagen_url'];
                    if (!empty($mainImage) && strpos($mainImage, 'http') !== 0 && strpos($mainImage, '/') !== 0) {
                        $mainImage = APP_URL . '/uploads/productos/' . $mainImage;
                    } elseif (strpos($mainImage, '/') === 0) {
                        $mainImage = APP_URL . $mainImage;
                    }
                    ?>
                    <img src="<?= htmlspecialchars($mainImage) ?>" 
                         class="img-fluid rounded" 
                         alt="<?= htmlspecialchars($producto['nombre']) ?>"
                         id="mainProductImage"
                         onerror="this.onerror=null; this.src='<?= APP_URL ?>/assets/img/placeholder.jpg';">
                </div>
                <?php if (count($imagenes) > 1): ?>
                <div class="thumbnail-container d-flex flex-wrap gap-2">
                    <?php foreach ($imagenes as $index => $imagen): ?>
                        <div class="thumbnail" style="width: 80px; cursor: pointer;">
                            <?php 
                            $thumbImage = $imagen['imagen_url'];
                            if (!empty($thumbImage) && strpos($thumbImage, 'http') !== 0 && strpos($thumbImage, '/') !== 0) {
                                $thumbImage = APP_URL . '/uploads/productos/' . $thumbImage;
                            } elseif (strpos($thumbImage, '/') === 0) {
                                $thumbImage = APP_URL . $thumbImage;
                            }
                            ?>
                            <img src="<?= htmlspecialchars($thumbImage) ?>" 
                                 class="img-thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                                 alt=""
                                 onerror="this.onerror=null; this.src='<?= APP_URL ?>/assets/img/placeholder.jpg';"
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
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h1 class="mb-0"><?= htmlspecialchars($producto['nombre']) ?></h1>
                <div class="d-flex gap-2">
                    <?php 
                    // Verificar si el usuario es el vendedor de este producto
                    $es_vendedor_del_producto = false;
                    if (isset($_SESSION['user_id']) && isset($producto['vendedor_id'])) {
                        $es_vendedor_del_producto = ($_SESSION['user_id'] == $producto['vendedor_id']);
                    }
                    
                    if ($es_vendedor_del_producto): 
                    ?>
                        <a href="/Silco/vendedor/editar-producto.php?id=<?= $producto_id ?>" 
                           class="btn btn-outline-primary d-flex align-items-center"
                           title="Editar producto">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </a>
                        <a href="/Silco/vendedor/productos.php" 
                           class="btn btn-outline-secondary d-flex align-items-center"
                           title="Volver a mis productos">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!$es_vendedor_del_producto && isLoggedIn()): ?>
                        <button class="btn btn-outline-danger d-flex align-items-center"
                                data-bs-toggle="modal" data-bs-target="#reportarProductoModal"
                                title="Reportar este producto">
                            <i class="bi bi-flag"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <div class="d-flex align-items-center">
                    <?php if ($producto['precio_oferta']): ?>
                        <span class="h3 text-danger me-2">$<?= number_format($producto['precio_oferta'], 2) ?></span>
                        <span class="text-muted text-decoration-line-through me-2">$<?= number_format($producto['precio'], 2) ?></span>
                        <span class="badge bg-danger">
                            <?= number_format((($producto['precio'] - $producto['precio_oferta']) / $producto['precio'] * 100), 0) ?>% OFF
                        </span>
                    <?php else: ?>
                        <span class="h3">$<?= number_format($producto['precio'], 2) ?></span>
                    <?php endif; ?>
                </div>
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
                <button class="btn btn-outline-<?= $en_favoritos ? 'danger' : 'secondary' ?> favorite-btn" 
                        data-product-id="<?= $producto['id'] ?>"
                        data-in-favorites="<?= $en_favoritos ? 'true' : 'false' ?>"
                        data-bs-toggle="tooltip" 
                        title="<?= $en_favoritos ? 'Eliminar de favoritos' : 'Añadir a favoritos' ?>">
                    <i class="bi bi-heart<?= $en_favoritos ? '-fill' : '' ?>"></i>
                    <span class="favorite-text ms-1"><?= $en_favoritos ? 'En favoritos' : 'Añadir a favoritos' ?></span>
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
                    <button class="nav-link active" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab" aria-controls="specs" aria-selected="true">Especificaciones</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">Reseñas</button>
                </li>
            </ul>
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="productTabsContent">
                <div class="tab-pane fade show active" id="specs" role="tabpanel" aria-labelledby="specs-tab">
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
                                <th scope="row">Peso</th>
                                <td>
                                    <?php if (!empty($producto['peso_gramos'])): ?>
                                        <?= htmlspecialchars($producto['peso_gramos'] . ' g') ?>
                                    <?php else: ?>
                                        No especificado
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Dimensiones</th>
                                <td>
                                    <?php if (!empty($producto['largo_mm']) && !empty($producto['ancho_mm']) && !empty($producto['alto_mm'])): ?>
                                        <?= htmlspecialchars($producto['largo_mm']) ?> x <?= htmlspecialchars($producto['ancho_mm']) ?> x <?= htmlspecialchars($producto['alto_mm']) ?> mm
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
                                -<?= number_format((($relacionado['precio'] - $relacionado['precio_oferta']) / $relacionado['precio'] * 100), 0) ?>%
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

<!-- Modal para reportar producto -->
<div class="modal fade" id="reportarProductoModal" tabindex="-1" aria-labelledby="reportarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportarProductoModalLabel">Reportar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formReportarProducto">
                <div class="modal-body">
                    <p>Por favor, selecciona el motivo del reporte:</p>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="motivo" id="motivo1" value="contenido_inadecuado" checked>
                            <label class="form-check-label" for="motivo1">
                                Contenido inadecuado
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="motivo" id="motivo2" value="informacion_falsa">
                            <label class="form-check-label" for="motivo2">
                                Información falsa o engañosa
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="motivo" id="motivo3" value="producto_ilegal">
                            <label class="form-check-label" for="motivo3">
                                Producto ilegal o prohibido
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="motivo" id="motivo4" value="otro">
                            <label class="form-check-label" for="motivo4">
                                Otro motivo
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionReporte" class="form-label">Descripción (opcional)</label>
                        <textarea class="form-control" id="descripcionReporte" rows="3" placeholder="Proporciona más detalles sobre el problema"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Enviar reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Manejar el envío del formulario de reporte
document.getElementById('formReportarProducto').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const motivo = document.querySelector('input[name="motivo"]:checked').value;
    const descripcion = document.getElementById('descripcionReporte').value;
    
    // Aquí iría la lógica para enviar el reporte al servidor
    // Por ejemplo, usando fetch() para hacer una petición AJAX
    
    // Mostrar mensaje de éxito
    const modal = bootstrap.Modal.getInstance(document.getElementById('reportarProductoModal'));
    modal.hide();
    
    showToast('Reporte enviado correctamente. Gracias por tu colaboración.', 'success');
});

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
                
                // Mostrar notificación de éxito
                showToast('Producto añadido al carrito', 'success');
            } else {
                // Mostrar notificación de error
                showToast('Error: ' + (data.message || 'No se pudo agregar al carrito'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al conectar con el servidor', 'danger');
        });
    });
});

// Asegurarse de que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar los botones de favoritos
    setupFavoriteButtons();
});

// Script para manejar los botones de favoritos
function setupFavoriteButtons() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Manejador para los botones de favoritos
    document.querySelectorAll('.favorite-btn').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Obtener el ID del producto, probando ambos atributos posibles
            const productId = this.getAttribute('data-product-id') || this.getAttribute('data-id');
            if (!productId) {
                console.error('No se encontró el ID del producto');
                showToast('Error: No se pudo identificar el producto', 'danger');
                return;
            }
            
            // Determinar si ya es favorito
            const isFavorite = this.getAttribute('data-in-favorites') === 'true';
            const icon = this.querySelector('i');
            const text = this.querySelector('.favorite-text');
            const button = this;
            
            // Mostrar estado de carga
            const originalHTML = this.innerHTML;
            const originalTitle = this.getAttribute('title');
            this.disabled = true;
            
            if (icon) {
                icon.className = 'spinner-border spinner-border-sm';
            } else {
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }
            
            try {
                // Ensure BASE_URL is defined
                const baseUrl = '<?= defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>';
                const response = await fetch(`${baseUrl}/backend/favorites/toggle.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        product_id: parseInt(productId),
                        csrf_token: '<?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>'
                    }),
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Error en la respuesta del servidor');
                }
                
                const data = await response.json();
                
                if (data && data.success) {
                    // Toggle UI state based on the action from the server
                    const newIsFavorite = data.is_favorite;
                    
                    // Actualizar el botón basado en la respuesta
                    button.setAttribute('data-in-favorites', newIsFavorite ? 'true' : 'false');
                    
                    // Actualizar icono y texto
                    if (icon) {
                        icon.className = newIsFavorite ? 'bi bi-heart-fill' : 'bi bi-heart';
                    }
                    if (text) {
                        text.textContent = newIsFavorite ? 'En favoritos' : 'Añadir a favoritos';
                    }
                    
                    // Actualizar tooltip
                    const tooltip = bootstrap.Tooltip.getInstance(button);
                    if (tooltip) {
                        button.setAttribute('title', newIsFavorite ? 'Eliminar de favoritos' : 'Añadir a favoritos');
                        tooltip._config.title = newIsFavorite ? 'Eliminar de favoritos' : 'Añadir a favoritos';
                        tooltip.update();
                        tooltip.dispose();
                        new bootstrap.Tooltip(button);
                    }
                
                    // Actualizar contador de favoritos si existe
                    const favoritesCount = document.querySelectorAll('.favorites-count');
                    if (favoritesCount.length > 0) {
                        fetch('<?= BASE_URL ?>/backend/favorites/count.php', {
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(countData => {
                            if (countData && countData.success) {
                                const count = parseInt(countData.count) || 0;
                                favoritesCount.forEach(element => {
                                    element.textContent = count > 0 ? `(${count})` : '';
                                    element.style.display = count > 0 ? 'inline' : 'none';
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error actualizando contador de favoritos:', error);
                        });
                    }
                } else {
                    const errorMsg = data && data.message ? data.message : 'Error desconocido';
                    console.error('Error en la respuesta del servidor:', errorMsg);
                    showToast('Error al actualizar favoritos: ' + errorMsg, 'danger');
                }
            } catch (error) {
                console.error('Error al procesar la respuesta:', error);
                showToast('Error al conectar con el servidor', 'danger');
            } finally {
                // Restaurar estado del botón
                if (button) {
                    button.disabled = false;
                    if (originalHTML) {
                        // No restaurar el HTML directamente, solo el estado
                        if (icon) {
                            // Restaurar ícono basado en el estado actual
                            const isFavorite = button.getAttribute('data-in-favorites') === 'true';
                            icon.className = isFavorite ? 'bi bi-heart-fill' : 'bi bi-heart';
                            
                            // Si hay texto, actualizarlo también
                            if (text) {
                                text.textContent = isFavorite ? 'En favoritos' : 'Añadir a favoritos';
                            }
                        } else {
                            // Si no hay ícono, restaurar el HTML completo
                            button.innerHTML = originalHTML;
                        }
                        
                        // Re-inicializar tooltip si existe
                        if (button.hasAttribute('data-bs-toggle')) {
                            const tooltip = bootstrap.Tooltip.getInstance(button);
                            if (tooltip) {
                                tooltip.dispose();
                            }
                            new bootstrap.Tooltip(button);
                        }
                    }
                }
            }
        }
        } catch (error) {
            console.error('Error al actualizar favoritos:', error);
            showToast(error.message || 'Error al conectar con el servidor', 'error');
        } finally {
            // Restore button state
            this.disabled = false;
            this.innerHTML = originalHTML;
        }
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
    this.disabled = false;
    this.innerHTML = originalHTML;
});

// Función para mostrar notificaciones
function showToast(message, type = 'info') {
    // Verificar si ya existe un contenedor de notificaciones
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    // Crear el toast
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    const toastBody = document.createElement('div');
    toastBody.className = 'd-flex';
    
    const toastContent = document.createElement('div');
    toastContent.className = 'toast-body';
    toastContent.textContent = message;
    
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close btn-close-white me-2 m-auto';
    closeButton.setAttribute('data-bs-dismiss', 'toast');
    closeButton.setAttribute('aria-label', 'Cerrar');
    
    toastBody.appendChild(toastContent);
    toastBody.appendChild(closeButton);
    toast.appendChild(toastBody);
    
    container.appendChild(toast);
    
    // Eliminar el toast después de 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            container.removeChild(toast);
            if (container.children.length === 0) {
                document.body.removeChild(container);
            }
        }, 300);
    }, 3000);
    
    // Cerrar al hacer clic en el botón
    closeButton.addEventListener('click', () => {
        toast.classList.remove('show');
        setTimeout(() => {
            container.removeChild(toast);
            if (container.children.length === 0) {
                document.body.removeChild(container);
            }
        }, 300);
    });
}

// Script para el botón de compartir
const shareButton = document.getElementById('shareProduct');
if (shareButton) {
    shareButton.addEventListener('click', function() {
        if (navigator.share) {
            navigator.share({
                title: '<?= addslashes($producto['nombre'] ?? '') ?>',
                text: 'Mira este producto: <?= addslashes($producto['nombre'] ?? '') ?>',
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
            showToast('¡Enlace copiado al portapapeles!', 'info');
        }
    });
}
</script>
