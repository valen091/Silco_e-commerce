// Toggle product status
document.querySelectorAll('.toggle-status').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.id;
        const newStatus = this.dataset.status;
        const button = this;
        
        fetch(`/api/vendedor/productos/${productId}/estado`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ activo: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update button appearance
                const icon = button.querySelector('i');
                if (newStatus === '1') {
                    button.classList.remove('btn-outline-success');
                    button.classList.add('btn-outline-warning');
                    button.setAttribute('title', 'Desactivar');
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    button.dataset.status = '0';
                    
                    // Update status badge if exists
                    const statusBadge = button.closest('tr').querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-success';
                        statusBadge.textContent = 'Activo';
                    }
                } else {
                    button.classList.remove('btn-outline-warning');
                    button.classList.add('btn-outline-success');
                    button.setAttribute('title', 'Activar');
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    button.dataset.status = '1';
                    
                    // Update status badge if exists
                    const statusBadge = button.closest('tr').querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = 'badge bg-secondary';
                        statusBadge.textContent = 'Inactivo';
                    }
                }
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'El estado del producto ha sido actualizado',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(data.message || 'Error al actualizar el estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar el estado del producto: ' + error.message
            });
        });
    });
});

// Delete product
const deleteModal = document.getElementById('deleteModal');
if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const productId = button.getAttribute('data-id');
        const deleteForm = document.getElementById('deleteForm');
        deleteForm.action = `/vendedor/productos/${productId}/eliminar`;
    });

    // Handle form submission
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch(this.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(deleteModal);
                modal.hide();
                
                // Remove product row
                const row = document.querySelector(`button[data-id="${data.product_id}"]`).closest('tr');
                if (row) {
                    row.remove();
                }
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminado!',
                    text: 'El producto ha sido eliminado correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(data.message || 'Error al eliminar el producto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo eliminar el producto: ' + error.message
            });
        });
    });
}

// Image preview for product images
document.addEventListener('DOMContentLoaded', function() {
    // Main image preview
    const mainImageInput = document.getElementById('imagen_principal');
    if (mainImageInput) {
        mainImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImage');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Gallery images preview
    const galleryInput = document.getElementById('imagenes_adicionales');
    if (galleryInput) {
        galleryInput.addEventListener('change', function(e) {
            const files = e.target.files;
            const previewContainer = document.getElementById('galleryPreview');
            
            if (previewContainer) {
                previewContainer.innerHTML = ''; // Clear previous previews
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'col-6 col-sm-4 col-md-3 mb-3';
                            preview.innerHTML = `
                                <div class="position-relative">
                                    <img src="${e.target.result}" class="img-thumbnail w-100" style="height: 120px; object-fit: cover;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle" onclick="this.closest('.col-6').remove()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            previewContainer.appendChild(preview);
                        }
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    }
});

// Handle stock status changes
document.querySelectorAll('.stock-status').forEach(select => {
    select.addEventListener('change', function() {
        const productId = this.dataset.productId;
        const status = this.value;
        
        fetch(`/api/vendedor/productos/${productId}/stock-status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al actualizar el estado de stock');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar el estado de stock: ' + error.message
            });
        });
    });
});

// Add to cart functionality for product list
document.addEventListener('click', function(e) {
    if (e.target.closest('.add-to-cart')) {
        e.preventDefault();
        const button = e.target.closest('.add-to-cart');
        const productId = button.dataset.id;
        const quantity = button.dataset.quantity || 1;
        
        fetch('/carrito/agregar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                producto_id: productId,
                cantidad: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count
                const cartCount = document.getElementById('cartCount');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: '¡Producto agregado!',
                    text: 'El producto se ha añadido al carrito',
                    timer: 1500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
            } else {
                throw new Error(data.message || 'Error al agregar al carrito');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo agregar el producto al carrito: ' + error.message
            });
        });
    }
});
