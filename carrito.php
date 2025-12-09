<?php
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <h2>Tu Carrito de Compras</h2>
    <div id="cart-items" class="mt-4">
        <!-- Cart items will be loaded here via JavaScript -->
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p>Cargando tu carrito...</p>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-6 offset-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resumen del Pedido</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Envío:</span>
                        <span id="shipping">Calculado al finalizar</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <a href="checkout.php" class="btn btn-primary btn-lg">Proceder al Pago</a>
                        <a href="index.php" class="btn btn-outline-secondary">Seguir Comprando</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to format price
function formatPrice(price) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(price);
}

document.addEventListener('DOMContentLoaded', function() {
    // Load cart items
    function loadCart() {
        fetch('backend/cart/items.php')
            .then(response => response.json())
            .then(data => {
                const cartItems = document.getElementById('cart-items');
                
                if (!data.success || !data.items || data.items.length === 0) {
                    cartItems.innerHTML = `
                        <div class="alert alert-info">
                            <i class="bi bi-cart-x"></i> Tu carrito está vacío
                        </div>
                        <a href="index.php" class="btn btn-primary">Seguir Comprando</a>
                    `;
                    document.querySelector('.row.mt-4').style.display = 'none';
                    return;
                }
                
                // Render cart items
                cartItems.innerHTML = data.items.map(item => `
                    <div class="card mb-3" id="cart-item-${item.id}">
                        <div class="row g-0">
                            <div class="col-md-2">
                                <img src="${item.imagen || 'https://via.placeholder.com/100'}" 
                                     class="img-fluid rounded-start" 
                                     alt="${item.nombre}" 
                                     style="width: 100%; height: 120px; object-fit: cover;">
                            </div>
                            <div class="col-md-6">
                                <div class="card-body">
                                    <h5 class="card-title">${item.nombre}</h5>
                                    <p class="card-text text-muted">${formatPrice(item.precio)} c/u</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card-body">
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                onclick="const input = this.nextElementSibling; let newVal = parseInt(input.value) - 1; if (newVal >= 1) { input.value = newVal; updateQuantity(${item.id}, newVal, { target: input }); }">-</button>
                                        <input type="number" 
                                               id="quantity-${item.id}" 
                                               name="quantity" 
                                               class="form-control text-center" 
                                               value="${item.cantidad}" 
                                               min="1" 
                                               max="${item.stock || 100}"
                                               onchange="updateQuantity(${item.id}, this.value, { target: this })">
                                        <button class="btn btn-outline-secondary btn-sm" type="button" 
                                                onclick="const input = this.previousElementSibling; let newVal = parseInt(input.value) + 1; if (newVal <= (${item.stock || 100})) { input.value = newVal; updateQuantity(${item.id}, newVal, { target: input }); }">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="card-body d-flex align-items-center h-100">
                                    <button type="button" class="btn btn-link text-danger" 
                                            onclick="showConfirmation(${item.id}, this)"
                                            title="Eliminar del carrito">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Subtotal:</span>
                                <strong>${formatPrice(item.subtotal)}</strong>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                // Update totals
                document.getElementById('subtotal').textContent = formatPrice(data.subtotal);
                document.getElementById('total').textContent = formatPrice(data.total);
                
                // Update cart count in navbar if element exists
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = data.items.length;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('cart-items').innerHTML = `
                    <div class="alert alert-danger">
                        Error al cargar el carrito. Por favor, intente nuevamente.
                    </div>
                `;
            });
    }
    
    // Load favorites count if user is logged in
    function loadFavoritesCount() {
        const favoritesCountEl = document.getElementById('favorites-count');
        if (!favoritesCountEl) return;
        
        fetch('backend/favorites/count.php', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    favoritesCountEl.style.display = 'none';
                    return null;
                }
                throw new Error('Failed to fetch favorites count');
            }
            return response.json().catch(() => {
                throw new Error('Invalid JSON response');
            });
        })
        .then(data => {
            if (data && data.success) {
                favoritesCountEl.textContent = data.count;
                favoritesCountEl.style.display = data.count > 0 ? 'inline-block' : 'none';
            } else {
                favoritesCountEl.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading favorites count:', error);
            // Hide the badge on error to prevent confusion
            favoritesCountEl.style.display = 'none';
        });
    }
    
    // Initial load
    loadCart();
    loadFavoritesCount();
    
    // Variable to track if an update is in progress
    let isUpdating = false;
    
    // Function to update quantity
    window.updateQuantity = function(itemId, newQuantity, event) {
        // Prevent multiple simultaneous updates
        if (isUpdating) return;
        
        // Prevent default button behavior if it's a button click
        if (event && event.preventDefault) {
            event.preventDefault();
        }
        
        // Get the input element
        const input = event && event.target && event.target.tagName === 'INPUT' 
            ? event.target 
            : document.querySelector(`#cart-item-${itemId} input[type="number"]`);
            
        // If the event is from an input field, get the value directly
        if (input) {
            newQuantity = parseInt(input.value || 1);
        } else {
            // For button clicks, adjust the quantity
            newQuantity = Math.max(1, parseInt(newQuantity) || 1);
        }
        
        // Update the input field if it exists and we have a new value
        if (input) {
            input.value = newQuantity;
        }
        
        // Disable the buttons and input during the request
        const buttons = document.querySelectorAll(`#cart-item-${itemId} button`);
        buttons.forEach(btn => {
            btn.disabled = true;
        });
        if (input) input.disabled = true;
        
        // Show loading state
        isUpdating = true;
        
        fetch('backend/cart/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}&quantity=${newQuantity}`
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Expected JSON, got:', text);
                throw new Error('Respuesta del servidor no válida');
            }
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Error en la petición');
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update the UI with the new data
                loadCart();
                // Show success message
                if (typeof showToast === 'function') {
                    showToast('Cantidad actualizada correctamente', 'success');
                }
            } else {
                throw new Error(data.message || 'Error al actualizar la cantidad');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast(error.message || 'Error al actualizar la cantidad', 'danger');
            }
            // Revert the UI
            loadCart();
        })
        .finally(() => {
            // Re-enable the buttons and input
            buttons.forEach(btn => {
                btn.disabled = false;
            });
            if (input) input.disabled = false;
            isUpdating = false;
        });
    };
    
    // Function to show confirmation dialog (attached to window for global access)
    window.showConfirmation = function(itemId, button) {
        // Create toast element
        const toastHTML = `
            <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                <div id="confirmationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-danger text-white">
                        <strong class="me-auto">Confirmar eliminación</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                    </div>
                    <div class="toast-body">
                        <p>¿Estás seguro de que deseas eliminar este producto del carrito?</p>
                        <div class="mt-2 pt-2 border-top">
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmRemoval('${itemId}', this)">
                                Eliminar
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="toast">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove any existing toast
        const existingToast = document.getElementById('confirmationToast');
        if (existingToast) {
            existingToast.remove();
        }
        
        // Add new toast to the document
        document.body.insertAdjacentHTML('beforeend', toastHTML);
        
        // Initialize and show the toast
        const toastEl = document.getElementById('confirmationToast');
        const toast = new bootstrap.Toast(toastEl, { autohide: false });
        toast.show();
        
        // Store the button for later use
        toastEl._button = button;
        toastEl._itemId = itemId;
    }
    
    // Function to handle confirmed removal
    window.confirmRemoval = function(itemId, button) {
        // Get the toast element
        const toastEl = button.closest('.toast');
        const originalButton = toastEl._button;
        
        // Close the toast
        const toast = bootstrap.Toast.getInstance(toastEl);
        if (toast) {
            toast.hide();
        }
        
        // Call the actual removal function
        removeItem(itemId, originalButton);
    }
    
    // Function to remove item
    window.removeItem = function(itemId, button) {
        
        // Show loading state
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        // Remove item via API
        fetch('backend/cart/remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `item_id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the item from the DOM
                const item = document.getElementById(`cart-item-${itemId}`);
                if (item) {
                    item.remove();
                }
                // Update cart count in navbar
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }
                // Reload cart to update totals
                loadCart();
            } else {
                alert(data.message || 'Error al eliminar el producto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el producto');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalContent;
        });
    };
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
