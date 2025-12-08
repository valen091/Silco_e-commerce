            </div><!-- Cierre del container-fluid -->
        </div><!-- Cierre del main-content -->
    </div><!-- Cierre del d-flex -->

    <!-- Bootstrap JS y dependencias -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (necesario para algunos plugins) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
    
    <script>
        // Toggle del sidebar en móviles
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.dashboard-sidebar').classList.toggle('d-none');
        });
        
        // Actualizar el contador del carrito
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
                    cartCount.style.display = data.count > 0 ? 'inline-block' : 'none';
                }
            })
            .catch(error => {
                console.error('Error actualizando el carrito:', error);
            });
        }
        
        // Actualizar el contador al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // Actualizar cada 30 segundos por si hay cambios desde otro dispositivo
            setInterval(updateCartCount, 30000);
        });
    </script>
</body>
</html>
