            </div><!-- /.container-fluid -->
        </div><!-- /.dashboard-main -->
    </div><!-- /.dashboard-container -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/vendedor.js"></script>
    
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.dashboard-sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
                });
                
                // Check localStorage for sidebar state
                if (localStorage.getItem('sidebarCollapsed') === 'true') {
                    document.body.classList.add('sidebar-collapsed');
                }
            }
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Add active class to current nav item
            const currentPage = window.location.pathname.split('/').pop() || 'panel.php';
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
                if (link.getAttribute('href').includes(currentPage)) {
                    link.classList.add('active');
                }
            });
            
            // Update cart count
            updateCartCount();
            
            // Show loading state for all forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                    }
                });
            });
        });
        
        // Update cart count function
        function updateCartCount() {
            fetch('<?php echo $base_url; ?>/backend/cart/count.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar el carrito');
                }
                return response.json();
            })
            .then(data => {
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count || '0';
                    cartCount.style.display = data.count > 0 ? 'inline-flex' : 'none';
                }
            })
            .catch(error => {
                console.error('Error actualizando el carrito:', error);
            });
        }
        
        // Toast notifications
        function showToast(type, message) {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
        // Show any server-side messages
        <?php if (isset($_SESSION['success'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('success', '<?php echo addslashes($_SESSION["success"]); ?>');
                <?php unset($_SESSION['success']); ?>
            });
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('danger', '<?php echo addslashes($_SESSION["error"]); ?>');
                <?php unset($_SESSION['error']); ?>
            });
        <?php endif; ?>
        
        // Handle AJAX form submissions
        function handleFormSubmit(form, successCallback = null) {
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
            }
            
            fetch(form.action, {
                method: form.method,
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message || 'Operación exitosa');
                    if (typeof successCallback === 'function') {
                        successCallback(data);
                    } else if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                } else {
                    showToast('danger', data.message || 'Error al procesar la solicitud');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('danger', 'Error de conexión. Por favor, intente de nuevo.');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
            
            return false;
        }
            updateCartCount();
            
            // Actualizar cada 30 segundos por si hay cambios desde otro dispositivo
            setInterval(updateCartCount, 30000);
        });
    </script>
</body>
</html>
