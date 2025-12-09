/**
 * Handles favorite functionality across the site
 */

// Base URL for API endpoints
const API_BASE_URL = '/Silco/backend/favorites';

// Favorites panel elements
let favoritesPanel = null;
let favoritesList = null;
let noFavoritesMessage = null;

// Initialize favorites panel
document.addEventListener('DOMContentLoaded', function() {
    favoritesPanel = new bootstrap.Offcanvas(document.getElementById('favoritesPanel'));
    favoritesList = document.getElementById('favorites-list');
    noFavoritesMessage = document.getElementById('no-favorites-message');
    
    // Toggle favorites panel
    const favoritesToggle = document.getElementById('favorites-toggle');
    if (favoritesToggle) {
        favoritesToggle.addEventListener('click', function() {
            loadFavorites();
            favoritesPanel.toggle();
        });
    }
    
    // Update favorites count on page load
    updateFavoritesCountAndPanel();
    
    // Listen for custom event when favorites are updated
    document.addEventListener('favorites:updated', updateFavoritesCountAndPanel);
});

// Load favorites into the panel
async function loadFavorites() {
    try {
        const response = await fetch(API_BASE_URL + '/list.php', {
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar favoritos');
        }
        
        const data = await response.json();
        
        // Clear current list
        favoritesList.innerHTML = '';
        
        if (!data.products || data.products.length === 0) {
            noFavoritesMessage.style.display = 'block';
            return;
        }
        
        noFavoritesMessage.style.display = 'none';
        
        // Add each favorite to the list
        data.products.forEach(item => {
            const favoriteItem = document.createElement('a');
            favoriteItem.href = `producto.php?id=${item.id}`;
            favoriteItem.className = 'list-group-item list-group-item-action';
            favoriteItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <img src="${item.imagen || 'assets/img/placeholder.png'}" 
                         alt="${item.nombre}" 
                         class="rounded me-3" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${item.nombre}</h6>
                        <small class="text-muted">$${parseFloat(item.precio_desde).toFixed(2)}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger remove-favorite" 
                            data-product-id="${item.id}"
                            onclick="event.stopPropagation(); event.preventDefault(); removeFromFavorites(${item.id}, this);">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `;
            favoritesList.appendChild(favoriteItem);
        });
        
    } catch (error) {
        console.error('Error loading favorites:', error);
        showToast('Error al cargar los favoritos', 'danger');
    }
}

// Update favorites count and panel
async function updateFavoritesCountAndPanel() {
    try {
        const data = await updateFavoritesCount();
        const count = data?.count || 0;
        
        // Update all instances of favorites count
        document.querySelectorAll('.favorites-count').forEach(element => {
            element.textContent = count;
            element.style.display = count > 0 ? 'inline-block' : 'none';
        });
        
        // Update the floating button count
        const floatingCount = document.getElementById('favorites-count');
        if (floatingCount) {
            floatingCount.textContent = count;
            floatingCount.style.display = count > 0 ? 'block' : 'none';
        }
        
        // If panel is open, refresh the list
        if (favoritesPanel && favoritesPanel._isShown) {
            await loadFavorites();
        }
    } catch (error) {
        console.error('Error updating favorites count and panel:', error);
    }
}

// Update favorites count function
async function updateFavoritesCount() {
    try {
        console.log('Updating favorites count...');
        const response = await fetch(API_BASE_URL + '/count.php', {
            credentials: 'include',
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Favorites count data:', data);
        
        const countElements = document.querySelectorAll('.favorites-count');
        console.log('Found count elements:', countElements.length);
        
        countElements.forEach(element => {
            if (element) {
                element.textContent = data.count || 0;
                element.style.display = (data.count > 0) ? 'inline-block' : 'none';
            }
        });
        
        return data;
    } catch (error) {
        console.error('Error in updateFavoritesCount:', error);
        // Update UI to show error state if needed
        const countElements = document.querySelectorAll('.favorites-count');
        countElements.forEach(element => {
            if (element) {
                element.textContent = '0';
                element.style.display = 'none';
            }
        });
        throw error;
    }
}

// Initialize when DOM is loaded
function initializeFavorites() {
    console.log('Initializing favorites...');
    // No need to catch here as updateFavoritesCount handles its own errors
    updateFavoritesCount();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFavorites);
} else {
    // If DOM is already loaded, run immediately
    initializeFavorites();
}

// Show toast notification
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
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Add favorites count update after adding/removing
document.addEventListener('favorites:updated', function() {
    // updateFavoritesCount handles its own errors
    updateFavoritesCount();
});

document.addEventListener('DOMContentLoaded', function() {
    // Handle favorite buttons
    document.addEventListener('click', function(e) {
        const favoriteBtn = e.target.closest('.favorite-btn');
        if (!favoriteBtn) return;
        
        e.preventDefault();
        
        // Check if user is logged in by checking for the user ID in the data attribute
        if (!favoriteBtn.dataset.userId) {
            window.location.href = '/Silco/login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
            return;
        }
        
        const productId = favoriteBtn.getAttribute('data-product-id');
        if (!productId) return;
        
        const isFavorite = favoriteBtn.classList.contains('active');
        
        if (isFavorite) {
            removeFromFavorites(productId, favoriteBtn);
        } else {
            addToFavorites(productId, favoriteBtn);
        }
    });
    
    // Initialize favorite buttons state on page load
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    if (favoriteButtons.length > 0) {
        const productIds = Array.from(new Set(Array.from(favoriteButtons).map(btn => btn.getAttribute('data-product-id'))));
        if (productIds.length > 0) {
            checkFavoritesStatus(productIds);
        }
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Add a product to favorites
 */
async function addToFavorites(productId, button) {
    if (!button) {
        button = document.querySelector(`.favorite-btn[data-product-id="${productId}"]`);
    }
    
    const originalHtml = button ? button.innerHTML : '';
    
    try {
        if (button) {
            button.disabled = true;
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm';
            spinner.setAttribute('role', 'status');
            spinner.setAttribute('aria-hidden', 'true');
            button.innerHTML = '';
            button.appendChild(spinner);
        }
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };
        
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        console.log('Sending request to add favorite:', { productId });
        
        const response = await fetch('/Silco/backend/favorites/add.php', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                producto_id: productId
            }),
            credentials: 'include' // Changed from 'same-origin' to 'include' to include cookies
        });
        
        console.log('Response status:', response.status);
        
        // Get response text first to handle both JSON and text responses
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        // Check if the request was successful
        if (!response.ok) {
            let errorMessage = 'Error en la solicitud';
            try {
                if (responseText) {
                    try {
                        const jsonError = JSON.parse(responseText);
                        errorMessage = jsonError.message || errorMessage;
                    } catch (e) {
                        errorMessage = responseText || errorMessage;
                    }
                }
                console.error('Server error:', errorMessage);
            } catch (e) {
                console.error('Error parsing error response:', e);
            }
            throw new Error(errorMessage);
        }
        
        // Try to parse the response as JSON
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('Parsed response data:', data);
        } catch (e) {
            console.error('Error parsing JSON response:', e, 'Response text:', responseText);
            throw new Error('Error al procesar la respuesta del servidor');
        }
        
        if (data.success) {
            // Update all buttons for this product
            const buttons = document.querySelectorAll(`.favorite-btn[data-product-id="${productId}"]`);
            buttons.forEach(btn => {
                btn.classList.add('active');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                }
                
                // Update text if it exists
                const textSpan = btn.querySelector('.favorite-text');
                if (textSpan) {
                    textSpan.textContent = 'En favoritos';
                }
                
                // Update tooltip
                if (btn.hasAttribute('data-bs-toggle')) {
                    btn.setAttribute('title', 'Eliminar de favoritos');
                    const tooltip = bootstrap.Tooltip.getInstance(btn);
                    if (tooltip) {
                        tooltip.setContent({ '.tooltip-inner': 'Eliminar de favoritos' });
                    }
                }
            });
            
            // Show success message
            showToast('Producto agregado a favoritos', 'success');
            // Dispatch custom event to update favorites count
            document.dispatchEvent(new Event('favorites:updated'));
            return true;
        } else {
            throw new Error(data.message || 'Error al agregar a favoritos');
        }
    } catch (error) {
        console.error('Error adding to favorites:', error);
        showToast('Error al agregar a favoritos: ' + (error.message || 'Intente nuevamente'), 'error');
        return false;
    } finally {
        if (button) {
            button.disabled = false;
            // The button content is updated in the success handler
        }
    }
}

/**
 * Remove a product from favorites
 */
async function removeFromFavorites(productId, button) {
    if (!button) {
        button = document.querySelector(`.favorite-btn[data-product-id="${productId}"]`);
    }
    
    const originalHtml = button ? button.innerHTML : '';
    
    try {
        if (button) {
            button.disabled = true;
            const spinner = document.createElement('span');
            spinner.className = 'spinner-border spinner-border-sm';
            spinner.setAttribute('role', 'status');
            spinner.setAttribute('aria-hidden', 'true');
            button.innerHTML = '';
            button.appendChild(spinner);
        }
        
        const response = await fetch('/Silco/backend/favorites/remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                producto_id: productId
            }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update all buttons for this product
            const buttons = document.querySelectorAll(`.favorite-btn[data-product-id="${productId}"]`);
            buttons.forEach(btn => {
                btn.classList.remove('active');
                
                // Update icon to outline heart
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                }
                
                // Update text if it exists
                const textSpan = btn.querySelector('.favorite-text');
                if (textSpan) {
                    textSpan.textContent = 'Añadir a favoritos';
                }
                
                // Update tooltip
                if (btn.hasAttribute('data-bs-toggle')) {
                    btn.setAttribute('title', 'Añadir a favoritos');
                    const tooltip = bootstrap.Tooltip.getInstance(btn);
                    if (tooltip) {
                        tooltip.setContent({ '.tooltip-inner': 'Añadir a favoritos' });
                    }
                }
                
                // If we're on the favorites page, remove the product card
                if (window.location.pathname.includes('favoritos.php')) {
                    const card = btn.closest('.col-md-4, .product-card');
                    if (card) {
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            
                            // Check if there are no more favorites
                            const container = document.getElementById('favorites-container');
                            const noFavorites = document.getElementById('no-favorites');
                            
                            if (container && noFavorites && container.children.length === 0) {
                                container.classList.add('d-none');
                                noFavorites.classList.remove('d-none');
                            }
                        }, 300);
                    }
                }
            });
            
            // Show success message
            showToast('Producto eliminado de favoritos', 'success');
            // Dispatch custom event to update favorites count
            document.dispatchEvent(new Event('favorites:updated'));
            return true;
        } else {
            throw new Error(data.message || 'Error al eliminar de favoritos');
        }
    } catch (error) {
        console.error('Error removing from favorites:', error);
        showToast('Error al eliminar de favoritos: ' + (error.message || 'Intente nuevamente'), 'error');
        return false;
    } finally {
        if (button) {
            button.disabled = false;
            // The button content is updated in the success handler
        }
    }
}

/**
 * Check if products are in favorites and update button states
 */
async function checkFavoritesStatus(productIds) {
    if (!productIds || productIds.length === 0) {
        console.log('No product IDs provided to checkFavoritesStatus');
        return;
    }
    
    // Ensure productIds is an array of numbers
    const cleanProductIds = Array.isArray(productIds) 
        ? productIds.map(id => parseInt(id, 10)).filter(id => !isNaN(id))
        : [];
        
    if (cleanProductIds.length === 0) {
        console.log('No valid product IDs to check');
        return;
    }
    
    try {
        console.log('Checking favorites status for product IDs:', cleanProductIds);
        
        const response = await fetch('/Silco/backend/favorites/check.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_ids: cleanProductIds
            }),
            credentials: 'same-origin'
        });

        // Log response status and headers for debugging
        console.log('Response status:', response.status, response.statusText);
        console.log('Response headers:', [...response.headers.entries()]);

        // Get response text first to handle both JSON and non-JSON responses
        const responseText = await response.text();
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            console.error('Response text:', responseText);
            throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}`);
        }
        
        if (!response.ok) {
            const errorMsg = data && data.message 
                ? `Server error: ${data.message}` 
                : `HTTP error! status: ${response.status}`;
            throw new Error(errorMsg);
        }
        
        if (!data || typeof data !== 'object') {
            throw new Error('Invalid response format');
        }
        
        if (data.success && Array.isArray(data.favorites)) {
            console.log('Found favorites:', data.favorites);
            
            // Update button states for each favorite
            data.favorites.forEach(productId => {
                const safeProductId = String(productId);
                const buttons = document.querySelectorAll(`.favorite-btn[data-product-id="${safeProductId}"]`);
                
                console.log(`Updating ${buttons.length} buttons for product ${safeProductId}`);
                
                buttons.forEach(button => {
                    if (!button || !(button instanceof HTMLElement)) return;
                    
                    button.classList.add('active');
                    button.setAttribute('title', 'Eliminar de favoritos');
                    
                    // Update icon to filled heart
                    const icon = button.querySelector('i');
                    if (icon) {
                        if (icon.classList.contains('bi-heart')) {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill');
                        }
                    } else {
                        // If there's no icon, update the button content
                        button.innerHTML = button.innerHTML.replace('bi-heart', 'bi-heart-fill');
                    }
                });
            });
        } else {
            console.log('No favorites found or invalid response format');
        }
    } catch (error) {
        console.error('Error checking favorites status:', {
            error: error.message,
            stack: error.stack,
            productIds: cleanProductIds
        });
        
        // Don't show error to user as this is a background check
        // but log it for debugging
        if (window.console && console.error) {
            console.error('Favorites check failed:', error);
        }
    }
}

/**
 * Show a toast notification
 */
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
    
    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.style.marginBottom = '10px';
    
    // Add close button
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s';
        
        setTimeout(() => {
            toast.remove();
            
            // Remove container if no more toasts
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, 500);
    }, 3000);
    
    // Add click handler to close button
    const closeBtn = toast.querySelector('[data-bs-dismiss="toast"]');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            toast.remove();
            
            // Remove container if no more toasts
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        });
    }
}

async function checkFavoritesStatus(productIds) {
    try {
        console.log('Checking favorites status for product IDs:', productIds);
        
        const response = await fetch('/Silco/backend/favorites/check.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ product_ids: productIds }),
            credentials: 'same-origin'
        });

        console.log('Response status:', response.status);
        
        // First get the response as text
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        // Then try to parse it as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
        }

        if (!response.ok) {
            throw new Error(data.message || 'Server error');
        }

        return data.favorites || [];

    } catch (error) {
        console.error('Error checking favorites status:', {
            error: error.message, 
            stack: error.stack,
            productIds 
        });
        throw error;
    }
}