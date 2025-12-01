// Main JavaScript for Silco E-commerce

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Update cart count on page load
    updateCartCount();
    
    // Add to cart functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart')) {
            e.preventDefault();
            const button = e.target.closest('.add-to-cart');
            const productId = button.dataset.id;
            addToCart(productId, 1);
        }
        
        // Toggle favorite
        if (e.target.closest('.favorite-btn')) {
            e.preventDefault();
            const button = e.target.closest('.favorite-btn');
            const productId = button.dataset.id;
            toggleFavorite(productId, button);
        }
    });

    // Search functionality
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }
});

// Add product to cart
function addToCart(productId, quantity = 1) {
    fetch('backend/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showAlert('Producto añadido al carrito', 'success');
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            showAlert(data.message || 'Error al agregar al carrito', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error de conexión', 'danger');
    });
}

// Toggle favorite status
function toggleFavorite(productId, element) {
    fetch('backend/favorites/toggle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            const icon = element.querySelector('i');
            if (data.is_favorite) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill', 'text-danger');
            } else {
                icon.classList.remove('bi-heart-fill', 'text-danger');
                icon.classList.add('bi-heart');
            }
            showAlert(data.message || 'Favoritos actualizados', 'success');
        } else if (data.redirect) {
            window.location.href = data.redirect;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al actualizar favoritos', 'danger');
    });
}

// Update cart count in the UI
function updateCartCount() {
    fetch('backend/cart/count.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.textContent = data.count || '0';
            cartCount.style.display = data.count > 0 ? 'inline-block' : 'none';
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
        // Hide cart count on error to prevent UI issues
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.style.display = 'none';
        }
    });
}

// Handle search input
function handleSearch(e) {
    const query = e.target.value.trim();
    if (query.length > 2) {
        fetch(`backend/search/suggest.php?q=${encodeURIComponent(query)}`)
            .then(handleResponse)
            .then(data => {
                // Implement search suggestions UI if needed
                console.log('Search suggestions:', data);
            });
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    // Remove any existing alerts
    const existingAlerts = document.querySelectorAll('.alert-dismissible');
    existingAlerts.forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.role = 'alert';
    alertDiv.style.zIndex = '1100';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Remove alert after 3 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 3000);
}

// Helper function to handle fetch responses
function handleResponse(response) {
    if (!response.ok) {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(text || 'Error en la solicitud');
            }
        });
    }
    return response.json();
}

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
