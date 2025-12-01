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
document.addEventListener('DOMContentLoaded', function() {
    // Load cart items
    fetch('backend/cart/items.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items.length > 0) {
                // Render cart items
                let cartItemsHtml = '';
                let subtotal = 0;
                
                data.items.forEach(item => {
                    const itemTotal = item.precio * item.cantidad;
                    subtotal += itemTotal;
                    
                    cartItemsHtml += `
                        <div class="card mb-3">
                            <div class="row g-0">
                                <div class="col-md-2">
                                    <img src="${item.imagen || 'assets/img/placeholder.jpg'}" class="img-fluid rounded-start" alt="${item.nombre}">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">${item.nombre}</h5>
                                        <p class="card-text">${item.descripcion || ''}</p>
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-sm btn-outline-secondary update-quantity" data-id="${item.id}" data-action="decrease">-</button>
                                            <span class="mx-2">${item.cantidad}</span>
                                            <button class="btn btn-sm btn-outline-secondary update-quantity" data-id="${item.id}" data-action="increase">+</button>
                                            <button class="btn btn-sm btn-danger ms-3 remove-item" data-id="${item.id}">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-center justify-content-end">
                                    <div class="text-end pe-3">
                                        <div class="fw-bold">$${itemTotal.toFixed(2)}</div>
                                        ${item.precio_original > item.precio ? 
                                          `<small class="text-muted text-decoration-line-through">$${item.precio_original.toFixed(2)}</small>` : ''
                                        }
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                document.getElementById('cart-items').innerHTML = cartItemsHtml;
                document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
                document.getElementById('total').textContent = `$${subtotal.toFixed(2)}`;
                
                // Add event listeners for quantity updates and removal
                document.querySelectorAll('.update-quantity').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.getAttribute('data-id');
                        const action = this.getAttribute('data-action');
                        updateCartItem(productId, action);
                    });
                });
                
                document.querySelectorAll('.remove-item').forEach(button => {
                    button.addEventListener('click', function() {
                        const productId = this.getAttribute('data-id');
                        removeCartItem(productId);
                    });
                });
                
            } else {
                document.getElementById('cart-items').innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x" style="font-size: 4rem; color: #6c757d;"></i>
                        <h4 class="mt-3">Tu carrito está vacío</h4>
                        <p class="text-muted">Aún no has agregado productos a tu carrito.</p>
                        <a href="index.php" class="btn btn-primary mt-2">Explorar Productos</a>
                    </div>
                `;
                
                // Hide the summary if cart is empty
                document.querySelector('.row.mt-4').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            document.getElementById('cart-items').innerHTML = `
                <div class="alert alert-danger">
                    Error al cargar el carrito. Por favor, recarga la página o inténtalo más tarde.
                </div>
            `;
        });
    
    // Function to update cart item quantity
    function updateCartItem(productId, action) {
        fetch('backend/cart/update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to reflect changes
                window.location.reload();
            } else {
                alert('Error al actualizar el carrito: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el carrito. Por favor, inténtalo de nuevo.');
        });
    }
    
    // Function to remove item from cart
    function removeCartItem(productId) {
        if (confirm('¿Estás seguro de que quieres eliminar este producto de tu carrito?')) {
            fetch('backend/cart/remove.php', {
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
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    alert('Error al eliminar el producto: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el producto. Por favor, inténtalo de nuevo.');
            });
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
