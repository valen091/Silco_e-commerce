                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Silco</h5>
                    <p>Tu tienda en línea de confianza para encontrar los mejores productos al mejor precio.</p>
                </div>
                <div class="col-md-4">
                    <h5>Contacto</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-envelope"></i> slicoRRHH@silco.com</li>
                        <li><i class="bi bi-telephone"></i> +598 92 673 601</li>
                        <li><i class="bi bi-geo-alt"></i> San Jose De Mayo, Uruguay</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Silco</p>
            </div>
        </div>
    </footer>

    <!-- Favorites Panel Toggle Button -->
    <button class="btn btn-primary position-fixed" id="favorites-toggle" style="bottom: 20px; right: 20px; z-index: 1000; border-radius: 50%; width: 60px; height: 60px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
        <i class="bi bi-heart-fill"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="favorites-count">0</span>
    </button>

    <!-- Favorites Panel -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="favoritesPanel" aria-labelledby="favoritesPanelLabel">
        <div class="offcanvas-header bg-light">
            <h5 class="offcanvas-title" id="favoritesPanelLabel">
                <i class="bi bi-heart-fill text-danger me-2"></i>Mis Favoritos
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div id="favorites-list" class="list-group list-group-flush">
                <!-- Favoritos se cargarán aquí dinámicamente -->
                <div class="text-center py-5" id="no-favorites-message">
                    <i class="bi bi-heart text-muted" style="font-size: 2.5rem;"></i>
                    <p class="text-muted mt-2 mb-0">No tienes favoritos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <!-- Toasts will be inserted here -->
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/favorites.js"></script>
    <script>
        // Toggle categories panel on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const toggleCategories = document.getElementById('toggleCategories');
            const categoriesPanel = document.getElementById('categoriesPanel');
            const mainContent = document.getElementById('mainContent');
            
            // Toggle sidebar on mobile
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    categoriesPanel.classList.toggle('show');
                    document.body.classList.toggle('sidebar-open');
                });
            }
            
            // Toggle categories panel on desktop
            if (toggleCategories) {
                toggleCategories.addEventListener('click', function() {
                    categoriesPanel.classList.toggle('collapsed');
                    mainContent.classList.toggle('col-lg-12');
                    mainContent.classList.toggle('col-lg-10');
                    
                    // Toggle icon
                    const icon = this.querySelector('i');
                    if (categoriesPanel.classList.contains('collapsed')) {
                        icon.classList.remove('bi-chevron-left');
                        icon.classList.add('bi-chevron-right');
                    } else {
                        icon.classList.remove('bi-chevron-right');
                        icon.classList.add('bi-chevron-left');
                    }
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 991.98 && 
                    !event.target.closest('#categoriesPanel') && 
                    !event.target.closest('#sidebarToggle') &&
                    categoriesPanel.classList.contains('show')) {
                    categoriesPanel.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            });
            
            // Handle window resize
            function handleResize() {
                if (window.innerWidth > 991.98) {
                    categoriesPanel.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            }
            
            window.addEventListener('resize', handleResize);
        });
    </script>
    <script>
        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;
            
            const toastId = 'toast-' + Date.now();
            const iconMap = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            
            const icon = iconMap[type] || 'info-circle';
            
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.className = `toast show align-items-center text-white bg-${type} border-0`;
            toast.role = 'alert';
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${icon} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Auto-remove toast after 5 seconds
            setTimeout(() => {
                const bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
                toast.addEventListener('hidden.bs.toast', function() {
                    toast.remove();
                });
            }, 5000);
        }
    </script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
