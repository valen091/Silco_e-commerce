<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Obtener el ID del usuario actual
$usuario_id = $_SESSION['user_id'];

// Obtener los productos favoritos del usuario
$favoritos = [];
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT p.*, 
               (SELECT MIN(precio) FROM variaciones WHERE producto_id = p.id) as precio_desde,
               (SELECT ruta FROM imagenes WHERE producto_id = p.id ORDER BY orden ASC LIMIT 1) as imagen
        FROM productos p
        INNER JOIN favoritos f ON p.id = f.producto_id
        WHERE f.usuario_id = ? AND p.activo = 1
        ORDER BY f.fecha_agregado DESC
    ");
    $stmt->execute([$usuario_id]);
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Verificar los productos obtenidos
    error_log("Productos favoritos obtenidos: " . print_r($favoritos, true));
    
} catch (PDOException $e) {
    error_log("Error al obtener favoritos: " . $e->getMessage());
    $error = "Error al cargar la lista de favoritos. Por favor, inténtalo de nuevo más tarde.";
}

$page_title = "Mis Favoritos";
include 'includes/header.php';
?>

<!-- Contenido de la página de favoritos -->
<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Mis Favoritos</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (empty($favoritos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-heart" style="font-size: 4rem; color: #6c757d;"></i>
                    <h3 class="mt-3">¡Aún no tienes favoritos!</h3>
                    <p class="lead text-muted mb-4">Guarda tus productos favoritos para verlos aquí.</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-shop"></i> Explorar Productos
                    </a>
                </div>
            <?php else: ?>
                <div class="row" id="favorites-container">
                    <?php foreach ($favoritos as $producto): ?>
                        <div class="col-md-4 mb-4" id="product-<?php echo $producto['id']; ?>">
                            <div class="card h-100">
                                <?php if (!empty($producto['imagen'])): ?>
                                    <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-image" style="font-size: 3rem; color: #6c757d;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                    <?php if (isset($producto['precio_desde'])): ?>
                                        <p class="h5 text-primary">
                                            Desde $<?php echo number_format($producto['precio_desde'], 2); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($producto['descripcion']), 0, 100); ?>...</p>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            Ver Detalles
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm remove-favorite" data-product-id="<?php echo $producto['id']; ?>">
                                            <i class="bi bi-heart-fill"></i> Quitar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar mensaje de carga solo si hay productos
    const container = document.getElementById('favorites-container');
    const noFavorites = document.getElementById('no-favorites');
    
    // Handle remove from favorites
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.remove-favorite')) {
            const button = e.target.closest('.remove-favorite');
            const productId = button.getAttribute('data-product-id');
            
            // Show loading state
            const originalContent = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            try {
                const response = await fetch('backend/favorites/remove.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        producto_id: productId
                    }),
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Error al eliminar de favoritos');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove the product card from the UI
                    const productCard = document.getElementById(`product-${productId}`);
                    if (productCard) {
                        productCard.style.opacity = '0';
                        setTimeout(() => {
                            productCard.remove();
                            
                            // Check if there are no more favorites
                            const remainingProducts = document.querySelectorAll('#favorites-container > div').length;
                            if (remainingProducts === 0) {
                                // Show empty state
                                const emptyState = `
                                    <div class="col-12 text-center py-5">
                                        <i class="bi bi-heart" style="font-size: 4rem; color: #6c757d;"></i>
                                        <h3 class="mt-3">¡Aún no tienes favoritos!</h3>
                                        <p class="lead text-muted mb-4">Guarda tus productos favoritos para verlos aquí.</p>
                                        <a href="index.php" class="btn btn-primary">
                                            <i class="bi bi-shop"></i> Explorar Productos
                                        </a>
                                    </div>
                                `;
                                container.innerHTML = emptyState;
                            }
                        }, 300);
                    }
                    
                    // Show success message
                    showToast('Producto eliminado de favoritos', 'success');
                    
                    // Update favorites count in the header
                    updateFavoritesCount();
                } else {
                    throw new Error(data.message || 'Error al eliminar de favoritos');
                }
            } catch (error) {
                console.error('Error removing from favorites:', error);
                showToast(error.message || 'Error al eliminar de favoritos', 'error');
                button.disabled = false;
                button.innerHTML = originalContent;
            }
        }
    });
    
    // Function to show toast notifications
    function showToast(message, type = 'info') {
        // Check if toast container exists, if not create it
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.role = 'alert';
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        // Add delay for auto-hide
        const delay = type === 'success' ? 3000 : 5000;
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Initialize and show the toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: delay
        });
        
        bsToast.show();
        
        // Remove the toast from DOM after it's hidden
        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });
    }
    
    // Function to update favorites count in the header
    async function updateFavoritesCount() {
        try {
            const response = await fetch('backend/favorites/count.php', {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                const countElements = document.querySelectorAll('.favorites-count');
                countElements.forEach(el => {
                    if (data.count > 0) {
                        el.textContent = data.count;
                        el.style.display = 'inline-block';
                    } else {
                        el.style.display = 'none';
                    }
    if (hasFavorites === 'true') {
        container.classList.remove('d-none');
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p>Cargando tus favoritos...</p>
            </div>
        `;
    }
    
    // Cargar favoritos
    fetch('backend/favorites/list.php')
        .then(response => response.json())
        .then(data => {
            // Limpiar el contenedor
            container.innerHTML = '';
            
            if (data.success && data.products && data.products.length > 0) {
                container.innerHTML = ''; // Clear loading spinner
                
                data.products.forEach(product => {
                    const productCard = `
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <img src="${product.imagen || 'assets/img/placeholder.jpg'}" class="card-img-top" alt="${product.nombre}" style="height: 200px; object-fit: cover;">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">${product.nombre}</h5>
                                    <p class="card-text">${product.descripcion ? product.descripcion.substring(0, 100) + '...' : ''}</p>
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h5 mb-0">$${product.precio.toFixed(2)}</span>
                                            ${product.precio_original > product.precio ? 
                                                `<small class="text-muted text-decoration-line-through">$${product.precio_original.toFixed(2)}</small>` : ''
                                            }
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="d-flex justify-content-between">
                                        <a href="producto.php?id=${product.id}" class="btn btn-outline-primary">Ver Detalles</a>
                                        <button class="btn btn-outline-danger remove-favorite" data-product-id="${product.id}">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', productCard);
                });
                
                // Mostrar el contenedor de favoritos
                container.classList.remove('d-none');
                noFavorites.classList.add('d-none');
                localStorage.setItem('hasFavorites', 'true');
                
                // Add event listeners for remove buttons
                document.querySelectorAll('.remove-favorite').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.getAttribute('data-product-id');
                        removeFromFavorites(productId, this);
                    });
                });
                
            } else {
                // No favorites
                container.classList.add('d-none');
                noFavorites.classList.remove('d-none');
                localStorage.setItem('hasFavorites', 'false');
            }
        })
        .catch(error => {
            console.error('Error loading favorites:', error);
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        Error al cargar los favoritos. Por favor, recarga la página o inténtalo más tarde.
                    </div>
                </div>
            `;
        });
    
    // Function to remove item from favorites
    function removeFromFavorites(productId, button) {
        const card = button.closest('.col-md-4');
        
        fetch('backend/favorites/remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the card with animation
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    
                    // Check if there are no more favorites
                    if (document.querySelectorAll('.col-md-4').length === 0) {
                        document.getElementById('favorites-container').classList.add('d-none');
                        document.getElementById('no-favorites').classList.remove('d-none');
                    }
                }, 300);
                
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success';
                alert.textContent = 'Producto eliminado de favoritos';
                document.querySelector('.container.my-5').insertBefore(alert, document.querySelector('h2').nextSibling);
                
                // Remove the alert after 3 seconds
                setTimeout(() => {
                    alert.remove();
                }, 3000);
                
            } else {
                alert('Error al eliminar de favoritos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar de favoritos. Por favor, inténtalo de nuevo.');
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
