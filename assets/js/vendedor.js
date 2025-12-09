/**
 * Vendor Dashboard JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.dashboard-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-open');
            sidebar.classList.toggle('show');
            
            if (sidebarOverlay) {
                if (document.body.classList.contains('sidebar-open')) {
                    sidebarOverlay.classList.add('show');
                } else {
                    setTimeout(() => {
                        sidebarOverlay.classList.remove('show');
                    }, 300);
                }
            }
        });
    }
    
    // Close sidebar when clicking on overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            document.body.classList.remove('sidebar-open');
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Set active nav item
    const currentPage = window.location.pathname.split('/').pop() || 'panel.php';
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        if (link.getAttribute('href').includes(currentPage)) {
            link.classList.add('active');
            const parent = link.closest('.has-submenu');
            if (parent) {
                parent.classList.add('show');
                const submenu = parent.querySelector('.submenu');
                if (submenu) submenu.style.display = 'block';
            }
        }
    });
    
    // Toggle submenu
    document.querySelectorAll('.has-submenu > a').forEach(item => {
        item.addEventListener('click', function(e) {
            if (window.innerWidth > 991) return; // Only on mobile
            
            const parent = this.parentElement;
            const wasActive = parent.classList.contains('show');
            
            // Close all other open submenus
            document.querySelectorAll('.has-submenu').forEach(el => {
                if (el !== parent) {
                    el.classList.remove('show');
                    const submenu = el.querySelector('.submenu');
                    if (submenu) submenu.style.display = 'none';
                }
            });
            
            // Toggle current
            if (!wasActive) {
                parent.classList.add('show');
                const submenu = this.nextElementSibling;
                if (submenu && submenu.classList.contains('submenu')) {
                    submenu.style.display = 'block';
                }
            } else {
                parent.classList.remove('show');
                const submenu = this.nextElementSibling;
                if (submenu && submenu.classList.contains('submenu')) {
                    submenu.style.display = 'none';
                }
            }
            
            e.preventDefault();
            e.stopPropagation();
        });
    });
    
    // Handle window resize
    function handleResize() {
        if (window.innerWidth > 991) {
            // Desktop
            document.body.classList.remove('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            
            // Show all submenus on desktop
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.style.display = 'block';
            });
        } else {
            // Mobile - hide all submenus by default
            document.querySelectorAll('.has-submenu').forEach(item => {
                if (!item.classList.contains('show')) {
                    const submenu = item.querySelector('.submenu');
                    if (submenu) submenu.style.display = 'none';
                }
            });
        }
    }
    
    // Initial call
    handleResize();
    
    // Add resize listener
    window.addEventListener('resize', handleResize);
    
    // Show loading state for buttons with loading class
    document.querySelectorAll('.btn-loading').forEach(button => {
        button.addEventListener('click', function() {
            this.setAttribute('data-original-text', this.innerHTML);
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        });
    });
    
    // Initialize DataTables if present
    if (typeof $.fn.DataTable === 'function') {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            drawCallback: function() {
                // Re-initialize tooltips after table draw
                const tooltipTriggerList = [].slice.call(this.api().table().container().querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });
    }
    
    // Show toast notifications
    window.showToast = function(type, message) {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;
        
        const toastId = 'toast-' + Date.now();
        let icon = '';
        
        switch(type) {
            case 'success':
                icon = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'error':
                icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                break;
            case 'warning':
                icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'info':
            default:
                icon = '<i class="fas fa-info-circle me-2"></i>';
        }
        
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${icon}${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 5000
        });
        
        bsToast.show();
        
        // Remove toast from DOM after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };
    
    // Show any queued messages
    <?php if (isset($_SESSION['success'])): ?>
        showToast('success', '<?php echo addslashes($_SESSION['success']); ?>');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        showToast('error', '<?php echo addslashes($_SESSION['error']); ?>');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning'])): ?>
        showToast('warning', '<?php echo addslashes($_SESSION['warning']); ?>');
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['info'])): ?>
        showToast('info', '<?php echo addslashes($_SESSION['info']); ?>');
        <?php unset($_SESSION['info']); ?>
    <?php endif; ?>
});

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('es-AR', options);
}

// Confirm before action
function confirmAction(message, callback) {
    if (confirm(message || '¿Estás seguro de realizar esta acción?')) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
}
