/**
 * Product Form Handler
 * Handles product form functionality including image uploads and previews
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productoForm');
    const imageInput = document.getElementById('imagenes');
    const imagePreview = document.getElementById('imagePreview');
    const dropzone = document.getElementById('imageDropzone');
    const maxFiles = 10;
    let files = [];

    // Initialize TinyMCE if it exists
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#descripcion',
            height: 300,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px }'
        });
    }

    // Handle drag and drop
    if (dropzone) {
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dropzone.addEventListener('drop', handleDrop, false);
        
        // Handle click on dropzone
        dropzone.addEventListener('click', () => {
            imageInput.click();
        });
    }

    // Handle file selection via input
    if (imageInput) {
        imageInput.addEventListener('change', handleFileSelect, false);
    }

    // Handle form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            // Update the hidden field with the order of images
            const order = Array.from(document.querySelectorAll('.preview-item')).map(el => el.dataset.id);
            document.getElementById('imagenes_orden').value = JSON.stringify(order);
            
            // Validate form
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            }
        });
    }

    // Prevent default drag behaviors
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop zone
    function highlight() {
        dropzone.classList.add('bg-light');
    }

    // Unhighlight drop zone
    function unhighlight() {
        dropzone.classList.remove('bg-light');
    }

    // Handle dropped files
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const droppedFiles = dt.files;
        handleFiles(droppedFiles);
    }

    // Handle file selection via input
    function handleFileSelect(e) {
        const selectedFiles = e.target.files;
        handleFiles(selectedFiles);
    }

    // Process selected files
    function handleFiles(selectedFiles) {
        // Convert FileList to array and filter out non-image files
        const newFiles = Array.from(selectedFiles).filter(file => file.type.startsWith('image/'));
        
        // Check total file count
        if (files.length + newFiles.length > maxFiles) {
            showAlert(`Solo puedes subir un máximo de ${maxFiles} imágenes.`, 'warning');
            return;
        }
        
        // Add new files to the files array
        files = [...files, ...newFiles];
        
        // Update preview
        updatePreview();
        
        // Update the file input
        updateFileInput();
    }

    // Update the file input with the current files
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        files.forEach(file => dataTransfer.items.add(file));
        imageInput.files = dataTransfer.files;
    }

    // Update the image preview
    function updatePreview() {
        // Clear existing preview
        imagePreview.innerHTML = '';
        
        // Add each file to the preview
        files.forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.dataset.id = file.name;
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}" class="img-thumbnail">
                    <button type="button" class="btn btn-sm btn-danger remove-image" data-index="${index}">
                        <i class="fas fa-times"></i>
                    </button>
                    <span class="badge bg-primary image-order">${index + 1}</span>
                    <div class="btn-group btn-group-sm image-actions">
                        <button type="button" class="btn btn-outline-secondary move-up" title="Mover arriba">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary move-down" title="Mover abajo">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary set-main" title="Establecer como principal">
                            <i class="fas fa-star"></i>
                        </button>
                    </div>
                `;
                
                imagePreview.appendChild(previewItem);
                
                // Add event listeners for the buttons
                const removeBtn = previewItem.querySelector('.remove-image');
                const moveUpBtn = previewItem.querySelector('.move-up');
                const moveDownBtn = previewItem.querySelector('.move-down');
                const setMainBtn = previewItem.querySelector('.set-main');
                
                removeBtn.addEventListener('click', () => removeImage(index));
                moveUpBtn.addEventListener('click', () => moveImage(index, 'up'));
                moveDownBtn.addEventListener('click', () => moveImage(index, 'down'));
                setMainBtn.addEventListener('click', () => setAsMain(index));
                
                // Make the preview item sortable
                previewItem.draggable = true;
                previewItem.addEventListener('dragstart', handleDragStart);
                previewItem.addEventListener('dragover', handleDragOver);
                previewItem.addEventListener('drop', handleDropItem);
                previewItem.addEventListener('dragend', handleDragEnd);
            };
            
            reader.readAsDataURL(file);
        });
        
        // Update the order numbers
        updateOrderNumbers();
    }
    
    // Remove an image
    function removeImage(index) {
        files.splice(index, 1);
        updatePreview();
        updateFileInput();
    }
    
    // Move an image up or down in the order
    function moveImage(index, direction) {
        if (direction === 'up' && index > 0) {
            // Swap with previous item
            [files[index], files[index - 1]] = [files[index - 1], files[index]];
            updatePreview();
            updateFileInput();
        } else if (direction === 'down' && index < files.length - 1) {
            // Swap with next item
            [files[index], files[index + 1]] = [files[index + 1], files[index]];
            updatePreview();
            updateFileInput();
        }
    }
    
    // Set an image as the main image
    function setAsMain(index) {
        if (index > 0) {
            // Move the selected image to the beginning of the array
            const [movedItem] = files.splice(index, 1);
            files.unshift(movedItem);
            updatePreview();
            updateFileInput();
        }
    }
    
    // Update the order numbers displayed on the preview items
    function updateOrderNumbers() {
        const orderNumbers = document.querySelectorAll('.image-order');
        orderNumbers.forEach((el, index) => {
            el.textContent = index + 1;
        });
    }
    
    // Drag and drop functions for reordering
    let draggedItem = null;
    
    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }
    
    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    function handleDropItem(e) {
        e.stopPropagation();
        
        if (draggedItem !== this) {
            // Get all preview items
            const items = Array.from(document.querySelectorAll('.preview-item'));
            const fromIndex = items.indexOf(draggedItem);
            const toIndex = items.indexOf(this);
            
            if (fromIndex < toIndex) {
                // Move down
                this.parentNode.insertBefore(draggedItem, this.nextSibling);
            } else {
                // Move up
                this.parentNode.insertBefore(draggedItem, this);
            }
            
            // Update the files array to match the new order
            const newFiles = [];
            const newItems = document.querySelectorAll('.preview-item');
            
            newItems.forEach(item => {
                const index = parseInt(item.querySelector('.remove-image').dataset.index);
                newFiles.push(files[index]);
            });
            
            files = newFiles;
            updateOrderNumbers();
            updateFileInput();
        }
        
        return false;
    }
    
    function handleDragEnd() {
        this.classList.remove('dragging');
        return false;
    }
    
    // Validate the form before submission
    function validateForm() {
        // Check if at least one image is uploaded
        if (files.length === 0) {
            showAlert('Debes subir al menos una imagen del producto.', 'danger');
            return false;
        }
        
        // Add any additional validation here
        
        return true;
    }
    
    // Show an alert message
    function showAlert(message, type = 'info') {
        // Remove any existing alerts
        const existingAlert = document.querySelector('.alert-dismissible');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.dashboard-main');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
    }
});
