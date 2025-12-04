// Función para actualizar el contador del carrito
function updateCartCount() {
    fetch('/Silco/backend/cart/count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(element => {
                    element.textContent = data.count;
                    element.style.display = data.count > 0 ? 'inline' : 'none';
                });
            }
        })
        .catch(error => console.error('Error al actualizar el contador del carrito:', error));
}

// Función para añadir un producto al carrito
function addToCart(productId, quantity = 1) {
    return fetch('/Silco/backend/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount();
            showNotification('Producto añadido al carrito', 'success');
            return true;
        } else {
            showNotification('Error: ' + data.message, 'error');
            return false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al conectar con el servidor', 'error');
        return false;
    });
}

// Función para actualizar la cantidad de un producto en el carrito
function updateCartItem(cartItemId, quantity) {
    return fetch('/Silco/backend/cart/update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_item_id=${cartItemId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount();
            if (data.removed) {
                document.querySelector(`.cart-item[data-id="${cartItemId}"]`).remove();
            } else {
                const quantityElement = document.querySelector(`.cart-item[data-id="${cartItemId}"] .quantity`);
                const subtotalElement = document.querySelector(`.cart-item[data-id="${cartItemId}"] .subtotal`);
                
                if (quantityElement) quantityElement.textContent = quantity;
                if (subtotalElement) subtotalElement.textContent = `$${data.subtotal.toFixed(2)}`;
                
                // Actualizar totales
                if (data.cart_total !== undefined) {
                    document.querySelector('.cart-subtotal').textContent = `$${data.cart_total.subtotal.toFixed(2)}`;
                    document.querySelector('.cart-total').textContent = `$${data.cart_total.total.toFixed(2)}`;
                }
            }
            
            if (data.cart_count !== undefined && data.cart_count === 0) {
                // Redirigir si el carrito está vacío
                window.location.href = '/Silco/carrito-vacio.php';
            }
            
            return true;
        } else {
            showNotification('Error: ' + data.message, 'error');
            return false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al actualizar el carrito', 'error');
        return false;
    });
}

// Función para eliminar un producto del carrito
function removeFromCart(cartItemId) {
    if (confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
        return fetch('/Silco/backend/cart/remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_item_id=${cartItemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                const cartItem = document.querySelector(`.cart-item[data-id="${cartItemId}"]`);
                if (cartItem) cartItem.remove();
                
                // Actualizar totales
                if (data.cart_total) {
                    document.querySelector('.cart-subtotal').textContent = `$${data.cart_total.subtotal.toFixed(2)}`;
                    document.querySelector('.cart-total').textContent = `$${data.cart_total.total.toFixed(2)}`;
                }
                
                if (data.cart_count !== undefined && data.cart_count === 0) {
                    // Redirigir si el carrito está vacío
                    window.location.href = '/Silco/carrito-vacio.php';
                }
                
                showNotification('Producto eliminado del carrito', 'success');
                return true;
            } else {
                showNotification('Error: ' + data.message, 'error');
                return false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error al eliminar el producto', 'error');
            return false;
        });
    }
    return Promise.resolve(false);
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    // Verificar si ya existe un contenedor de notificaciones
    let container = document.getElementById('notification-container');
    
    // Si no existe, crearlo
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Crear la notificación
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.role = 'alert';
    notification.style.marginBottom = '10px';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Agregar la notificación al contenedor
    container.appendChild(notification);
    
    // Eliminar la notificación después de 5 segundos
    setTimeout(() => {
        notification.classList.remove('show');
        notification.classList.add('fade');
        setTimeout(() => {
            notification.remove();
            // Si no hay más notificaciones, eliminar el contenedor
            if (container.children.length === 0) {
                container.remove();
            }
        }, 150);
    }, 5000);
}

// Inicializar eventos del carrito al cargar el documento
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar contador del carrito
    updateCartCount();
    
    // Manejar clic en botones de añadir al carrito
    document.addEventListener('click', function(e) {
        // Botón de añadir al carrito en la página de producto
        if (e.target.closest('.add-to-cart') || (e.target.classList.contains('add-to-cart'))) {
            e.preventDefault();
            const button = e.target.closest('.add-to-cart') || e.target;
            const productId = button.getAttribute('data-id');
            let quantity = 1;
            
            // Obtener la cantidad del selector de cantidad si existe
            const quantityInput = document.querySelector('input[name="quantity"]') || 
                                 document.getElementById('quantity') ||
                                 document.getElementById('cantidad');
            
            if (quantityInput) {
                quantity = parseInt(quantityInput.value) || 1;
            }
            
            addToCart(productId, quantity);
        }
        
        // Manejar botones de incrementar/disminuir cantidad en el carrito
        if (e.target.closest('.btn-increment') || e.target.closest('.btn-decrement')) {
            e.preventDefault();
            const button = e.target.closest('.btn-increment') || e.target.closest('.btn-decrement');
            const cartItemId = button.closest('.cart-item').getAttribute('data-id');
            const quantityElement = button.closest('.quantity-controls').querySelector('.quantity');
            let quantity = parseInt(quantityElement.textContent);
            
            if (button.classList.contains('btn-increment')) {
                quantity++;
            } else {
                quantity = Math.max(1, quantity - 1);
            }
            
            updateCartItem(cartItemId, quantity);
        }
        
        // Manejar botón de eliminar del carrito
        if (e.target.closest('.remove-from-cart') || (e.target.classList.contains('remove-from-cart'))) {
            e.preventDefault();
            const button = e.target.closest('.remove-from-cart') || e.target;
            const cartItemId = button.getAttribute('data-id');
            removeFromCart(cartItemId);
        }
    });
    
    // Manejar cambios en el selector de cantidad
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-select')) {
            const cartItemId = e.target.closest('.cart-item').getAttribute('data-id');
            const quantity = parseInt(e.target.value);
            
            if (quantity > 0) {
                updateCartItem(cartItemId, quantity);
            }
        }
    });
});

// Hacer las funciones disponibles globalmente
window.SilcoCart = {
    updateCartCount,
    addToCart,
    updateCartItem,
    removeFromCart,
    showNotification
};
