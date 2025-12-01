<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<div class="container my-5">
    <h2>Mis Favoritos</h2>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <div class="row mt-4" id="favorites-container">
        <!-- Favorites will be loaded here via JavaScript -->
        <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando tus favoritos...</p>
        </div>
    </div>
    
    <div class="row mt-4 d-none" id="no-favorites">
        <div class="col-12 text-center">
            <i class="bi bi-heart" style="font-size: 4rem; color: #6c757d;"></i>
            <h4 class="mt-3">No tienes productos favoritos</h4>
            <p class="text-muted">Agrega productos a tus favoritos para verlos aquí.</p>
            <a href="index.php" class="btn btn-primary mt-2">Explorar Productos</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load favorites
    fetch('backend/favorites/list.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('favorites-container');
            const noFavorites = document.getElementById('no-favorites');
            
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
