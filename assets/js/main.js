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
            const productId = button.getAttribute('data-product-id');
            if (!productId) {
                console.error('No data-product-id attribute found on favorite button');
                return;
            }
            toggleFavorite(productId, button);
        }
    });

    // Search functionality
    const searchInput = document.querySelector('input[name="q"]');
    const searchForm = document.querySelector('form[role="search"]');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
        
        // Prevent form submission if we're showing search suggestions
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                if (searchInput.value.trim() === '') {
                    e.preventDefault();
                }
            });
        }
    }
});

// Add product to cart
function addToCart(productId, quantity = 1) {
    // Ensure productId is a valid number
    productId = parseInt(productId);
    if (isNaN(productId) || productId <= 0) {
        console.error('Invalid product ID:', productId);
        showAlert('ID de producto no válido', 'danger');
        return;
    }
    
    // Debug: Log the product ID and quantity
    console.log('Adding to cart - Product ID:', productId, 'Quantity:', quantity);
    
    // Get the base URL - use window.APP_URL or fallback to relative path
    // Use relative path to the cart endpoint
    const url = '/Silco/backend/cart/add.php';
    
    // Debug: Log the URL being called
    console.log('Calling API endpoint:', url);
    
    // Show loading state
    const addToCartBtn = document.querySelector(`[onclick*="addToCart(${productId}"]`);
    const originalText = addToCartBtn ? addToCartBtn.innerHTML : '';
    if (addToCartBtn) {
        addToCartBtn.disabled = true;
        addToCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Agregando...';
    }
    
    fetch(url, {
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
        console.error('Error details:', {
            error: error,
            message: error.message,
            status: error.status,
            url: url,
            productId: productId,
            quantity: quantity
        });
        
        // Show more specific error message
        if (error.status === 404) {
            showAlert('No se pudo encontrar el servicio del carrito. Por favor, recarga la página e intenta nuevamente.', 'danger');
        } else if (error.status === 403) {
            showAlert('Debes iniciar sesión para agregar productos al carrito', 'warning');
            // Optionally redirect to login
            setTimeout(() => {
                window.location.href = `${window.APP_URL || ''}/login.php?redirect=${encodeURIComponent(window.location.pathname)}`;
            }, 1500);
        } else {
            showAlert('Error al conectar con el servidor. Por favor, inténtalo de nuevo más tarde.', 'danger');
        }
    })
    .finally(() => {
        // Restore button state
        if (addToCartBtn) {
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = originalText || 'Agregar al carrito';
        }
    });
}

// Toggle favorite status
function toggleFavorite(productId, element) {
    // Validate element
    if (!element || !element.nodeType) {
        console.error('Invalid element provided to toggleFavorite');
        return;
    }

    // Save original button state
    const originalHTML = element.innerHTML;
    const isFavorite = element.getAttribute('data-in-favorites') === 'true';
    const buttonText = element.querySelector('.favorite-text') || element;
    
    // Show loading state
    element.disabled = true;
    if (buttonText) {
        buttonText.textContent = 'Procesando...';
    }

    // Create JSON data
    const data = {
        producto_id: productId
    };

    // Get CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Prepare headers
    const headers = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    // Add CSRF token if available
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    // Make the request
    fetch(`${window.APP_URL || ''}/backend/favorites/toggle.php`, {
        method: 'POST',
        headers: headers,
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(async response => {
        if (!response.ok) {
            const errorText = await response.text();
            try {
                const errorData = JSON.parse(errorText);
                throw new Error(errorData.message || 'Error en la solicitud');
            } catch (e) {
                throw new Error(errorText || 'Error en la solicitud');
            }
        }
        return response.json();
    })
    .then(data => {
        if (!data) {
            throw new Error('No se recibió respuesta del servidor');
        }

        if (data.success) {
            const action = data.action || 'toggled';
            const isNowFavorite = action === 'added';
            const icon = element.querySelector('i') || element;
            const text = element.querySelector('.favorite-text') || element;
            
            // Update the clicked button
            if (icon) {
                if (isNowFavorite) {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill', 'text-danger');
                } else {
                    icon.classList.remove('bi-heart-fill', 'text-danger');
                    icon.classList.add('bi-heart');
                }
            }
            
            if (text) {
                text.textContent = isNowFavorite ? 'En favoritos' : 'Añadir a favoritos';
            }
            
            element.setAttribute('data-in-favorites', isNowFavorite ? 'true' : 'false');
            
            // Update all instances of this favorite button on the page
            document.querySelectorAll(`[data-product-id="${productId}"][data-favorite-button]`).forEach(btn => {
                if (btn !== element) {
                    const btnIcon = btn.querySelector('i') || btn;
                    const btnText = btn.querySelector('.favorite-text') || btn;
                    
                    if (btnIcon) {
                        if (isNowFavorite) {
                            btnIcon.classList.remove('bi-heart');
                            btnIcon.classList.add('bi-heart-fill', 'text-danger');
                        } else {
                            btnIcon.classList.remove('bi-heart-fill', 'text-danger');
                            btnIcon.classList.add('bi-heart');
                        }
                    }
                    
                    if (btnText) {
                        btnText.textContent = isNowFavorite ? 'En favoritos' : 'Añadir a favoritos';
                    }
                    
                    btn.setAttribute('data-in-favorites', isNowFavorite ? 'true' : 'false');
                }
            });
            
            // Show success message
            if (data.message) {
                showAlert(data.message, 'success');
            }
            
            // Update favorites count
            updateFavoritesCount();
            
        } else if (data.redirect) {
            // Handle redirect if user needs to log in
            window.location.href = data.redirect;
        } else {
            throw new Error(data.message || 'Error desconocido al actualizar favoritos');
        }
    })
    .catch(error => {
        console.error('Error en toggleFavorite:', error);
        showAlert('Error al actualizar favoritos: ' + (error.message || 'Error desconocido'), 'danger');
    })
    .finally(() => {
        // Always re-enable the button
        element.disabled = false;
        
        // If the button doesn't have the updated state, restore original HTML
        if (!element.hasAttribute('data-in-favorites')) {
            element.innerHTML = originalHTML;
        }
    });
}

// Update favorites count in the header
function updateFavoritesCount() {
    fetch(`${window.APP_URL || ''}/backend/favorites/count.php`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.count !== undefined) {
            const favoritesCountElements = document.querySelectorAll('.favorites-count');
            favoritesCountElements.forEach(element => {
                element.textContent = data.count;
                element.style.display = data.count > 0 ? 'inline-block' : 'none';
            });
        }
    })
    .catch(error => {
        console.error('Error updating favorites count:', error);
    });
}

// Update cart count in the UI
function updateCartCount() {
    // Use relative path from site root to avoid issues with subdirectories
    fetch('/Silco/backend/cart/count.php', {
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
    const searchResults = document.getElementById('searchResults');
    
    if (query.length < 2) {
        if (searchResults) {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
        }
        return;
    }
    
    fetch(`${window.location.origin}/Silco/backend/search/suggest.php?q=${encodeURIComponent(query)}`)
        .then(handleResponse)
        .then(data => {
            if (!searchResults) {
                createSearchResultsContainer();
            }
            
            const resultsContainer = document.getElementById('searchResults');
            resultsContainer.innerHTML = '';
            
            if (data.products && data.products.length > 0) {
                data.products.forEach(product => {
                    const resultItem = createSearchResultItem(product);
                    resultsContainer.appendChild(resultItem);
                });
                
                // Add view all results link
                const viewAllLink = document.createElement('a');
                viewAllLink.href = `/Silco/buscar.php?q=${encodeURIComponent(query)}`;
                viewAllLink.className = 'dropdown-item text-center fw-bold';
                viewAllLink.textContent = 'Ver todos los resultados';
                resultsContainer.appendChild(viewAllLink);
                
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = `
                    <div class="dropdown-item">
                        <div class="text-muted text-center py-2">
                            No se encontraron productos
                        </div>
                    </div>
                `;
                resultsContainer.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error en la búsqueda:', error);
        });
}

// Create search result item element
function createSearchResultItem(product) {
    const item = document.createElement('a');
    item.href = `/Silco/producto.php?id=${product.id}`;
    item.className = 'dropdown-item d-flex align-items-center py-2';
    
    const imageUrl = product.imagen_principal || 'assets/img/placeholder.jpg';
    const price = product.precio_oferta || product.precio;
    const oldPrice = product.precio_oferta ? product.precio : null;
    
    item.innerHTML = `
        <div class="flex-shrink-0 me-3" style="width: 50px; height: 50px; overflow: hidden;">
            <img src="${imageUrl}" alt="${product.nombre}" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between">
                <h6 class="mb-0">${product.nombre}</h6>
                <div class="text-end">
                    <div class="fw-bold text-primary">$${price.toFixed(2)}</div>
                    ${oldPrice ? `<small class="text-muted text-decoration-line-through">$${oldPrice.toFixed(2)}</small>` : ''}
                </div>
            </div>
            <small class="text-muted">${product.categoria_nombre || ''}</small>
        </div>
    `;
    
    return item;
}

// Create search results container if it doesn't exist
function createSearchResultsContainer() {
    const searchForm = document.querySelector('form[role="search"]');
    if (!searchForm) return;
    
    const container = document.createElement('div');
    container.id = 'searchResults';
    container.className = 'dropdown-menu w-100 show';
    container.style.display = 'none';
    container.style.maxHeight = '400px';
    container.style.overflowY = 'auto';
    container.style.position = 'absolute';
    container.style.top = '100%';
    container.style.left = '0';
    container.style.zIndex = '1000';
    
    // Close results when clicking outside
    document.addEventListener('click', function closeResults(e) {
        if (!searchForm.contains(e.target) && !container.contains(e.target)) {
            container.style.display = 'none';
        }
    });
    
    searchForm.parentNode.insertBefore(container, searchForm.nextSibling);
    return container;
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
