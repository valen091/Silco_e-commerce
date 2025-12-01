<?php 
$isEdit = isset($producto) && !empty($producto['id']);
$title = $isEdit ? 'Editar Producto' : 'Nuevo Producto';
$formAction = $isEdit ? "/vendedor/productos/actualizar/{$producto['id']}" : '/vendedor/productos/guardar';
?>

<?php require_once __DIR__ . '/../../../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../../includes/sidebar-vendedor.php'; ?>
        
        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= $title ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= BASE_URL ?>/vendedor/productos" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a la lista
                    </a>
                </div>
            </div>

            <form id="productForm" action="<?= BASE_URL . $formAction ?>" method="POST" enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="_method" value="PUT">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Basic Information Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Información Básica</h5>
                            </div>
                            <div class="card-body">
                                <?php if (isset($errores['general'])): ?>
                                    <div class="alert alert-danger">
                                        <?= $errores['general'] ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        Nombre del Producto <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>" 
                                           required>
                                    <?php if (isset($errores['nombre'])): ?>
                                        <div class="invalid-feedback">
                                            <?= $errores['nombre'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        Descripción <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control <?= isset($errores['descripcion']) ? 'is-invalid' : '' ?>" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="5" 
                                              required><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
                                    <?php if (isset($errores['descripcion'])): ?>
                                        <div class="invalid-feedback">
                                            <?= $errores['descripcion'] ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="categoria_id" class="form-label">
                                                Categoría <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select <?= isset($errores['categoria_id']) ? 'is-invalid' : '' ?>" 
                                                    id="categoria_id" 
                                                    name="categoria_id" 
                                                    required>
                                                <option value="">Seleccione una categoría</option>
                                                <?php foreach ($categorias as $categoria): ?>
                                                    <option value="<?= $categoria['id'] ?>" 
                                                        <?= (isset($producto['categoria_id']) && $producto['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (isset($errores['categoria_id'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['categoria_id'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sku" class="form-label">
                                                SKU/Código
                                            </label>
                                            <input type="text" 
                                                   class="form-control <?= isset($errores['sku']) ? 'is-invalid' : '' ?>" 
                                                   id="sku" 
                                                   name="sku" 
                                                   value="<?= htmlspecialchars($producto['sku'] ?? '') ?>">
                                            <?php if (isset($errores['sku'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['sku'] ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-text">Dejar en blanco para generar automáticamente</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="precio" class="form-label">
                                                Precio <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" 
                                                       class="form-control <?= isset($errores['precio']) ? 'is-invalid' : '' ?>" 
                                                       id="precio" 
                                                       name="precio" 
                                                       min="0" 
                                                       step="0.01" 
                                                       value="<?= number_format($producto['precio'] ?? 0, 2, '.', '') ?>" 
                                                       required>
                                            </div>
                                            <?php if (isset($errores['precio'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= $errores['precio'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="precio_descuento" class="form-label">
                                                Precio de oferta
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" 
                                                       class="form-control <?= isset($errores['precio_descuento']) ? 'is-invalid' : '' ?>" 
                                                       id="precio_descuento" 
                                                       name="precio_descuento" 
                                                       min="0" 
                                                       step="0.01" 
                                                       value="<?= isset($producto['precio_descuento']) && $producto['precio_descuento'] > 0 ? number_format($producto['precio_descuento'], 2, '.', '') : '' ?>">
                                            </div>
                                            <?php if (isset($errores['precio_descuento'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= $errores['precio_descuento'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="stock" class="form-label">
                                                Stock <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" 
                                                   class="form-control <?= isset($errores['stock']) ? 'is-invalid' : '' ?>" 
                                                   id="stock" 
                                                   name="stock" 
                                                   min="0" 
                                                   value="<?= $producto['stock'] ?? 0 ?>" 
                                                   required>
                                            <?php if (isset($errores['stock'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['stock'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="peso" class="form-label">
                                                Peso (kg)
                                            </label>
                                            <div class="input-group">
                                                <input type="number" 
                                                       class="form-control <?= isset($errores['peso']) ? 'is-invalid' : '' ?>" 
                                                       id="peso" 
                                                       name="peso" 
                                                       min="0" 
                                                       step="0.01" 
                                                       value="<?= $producto['peso'] ?? '0.5' ?>">
                                                <span class="input-group-text">kg</span>
                                            </div>
                                            <?php if (isset($errores['peso'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= $errores['peso'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estado" class="form-label">
                                                Estado
                                            </label>
                                            <select class="form-select" id="estado" name="activo">
                                                <option value="1" <?= (!isset($producto['activo']) || $producto['activo'] == 1) ? 'selected' : '' ?>>Activo</option>
                                                <option value="0" <?= (isset($producto['activo']) && $producto['activo'] == 0) ? 'selected' : '' ?>>Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="palabras_clave" class="form-label">
                                        Palabras clave (etiquetas)
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="palabras_clave" 
                                           name="palabras_clave" 
                                           value="<?= htmlspecialchars($producto['palabras_clave'] ?? '') ?>">
                                    <div class="form-text">Separe las palabras clave con comas</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Images Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Imágenes del Producto</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="imagen_principal" class="form-label">
                                        Imagen Principal <span class="text-danger">*</span>
                                    </label>
                                    <input class="form-control <?= isset($errores['imagen_principal']) ? 'is-invalid' : '' ?>" 
                                           type="file" 
                                           id="imagen_principal" 
                                           name="imagen_principal" 
                                           accept="image/*" 
                                           <?= !$isEdit ? 'required' : '' ?>>
                                    <?php if (isset($errores['imagen_principal'])): ?>
                                        <div class="invalid-feedback">
                                            <?= $errores['imagen_principal'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Tamaño recomendado: 800x800px. Formatos: JPG, PNG, WEBP. Máx. 2MB
                                    </div>
                                    
                                    <?php if ($isEdit && !empty($producto['imagen_principal'])): ?>
                                        <div class="mt-3">
                                            <p class="mb-1">Imagen actual:</p>
                                            <img src="<?= BASE_URL . $producto['imagen_principal'] ?>" 
                                                 alt="Imagen actual" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 150px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="imagenes_adicionales" class="form-label">
                                        Imágenes Adicionales
                                    </label>
                                    <input class="form-control <?= isset($errores['imagenes_adicionales']) ? 'is-invalid' : '' ?>" 
                                           type="file" 
                                           id="imagenes_adicionales" 
                                           name="imagenes_adicionales[]" 
                                           multiple 
                                           accept="image/*">
                                    <?php if (isset($errores['imagenes_adicionales'])): ?>
                                        <div class="invalid-feedback">
                                            <?= $errores['imagenes_adicionales'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Puedes seleccionar varias imágenes. Tamaño recomendado: 800x800px. Máx. 2MB por imagen
                                    </div>
                                    
                                    <!-- Gallery Preview -->
                                    <div id="galleryPreview" class="row mt-3">
                                        <?php if ($isEdit && !empty($producto['imagenes_adicionales'])): ?>
                                            <?php foreach ($producto['imagenes_adicionales'] as $imagen): ?>
                                                <div class="col-6 col-sm-4 col-md-3 mb-3">
                                                    <div class="position-relative">
                                                        <img src="<?= BASE_URL . $imagen['ruta'] ?>" 
                                                             class="img-thumbnail w-100" 
                                                             style="height: 120px; object-fit: cover;">
                                                        <input type="hidden" name="imagenes_existentes[]" value="<?= $imagen['id'] ?>">
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 rounded-circle" 
                                                                onclick="this.closest('.col-6').remove()">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Configuración SEO</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="meta_titulo" class="form-label">
                                        Título para SEO
                                    </label>
                                    <input type="text" 
                                           class="form-control <?= isset($errores['meta_titulo']) ? 'is-invalid' : '' ?>" 
                                           id="meta_titulo" 
                                           name="meta_titulo" 
                                           value="<?= htmlspecialchars($producto['meta_titulo'] ?? '') ?>">
                                    <?php if (isset($errores['meta_titulo'])): ?>
                                        <div class="invalid-feedback">
                                            <?= $errores['meta_titulo'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Si se deja vacío, se usará el nombre del producto.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_descripcion" class="form-label">
                                        Descripción para SEO
                                    </label>
                                    <textarea class="form-control <?= isset($errores['meta_descripcion']) ? 'is-invalid' : '' ?>" 
                                              id="meta_descripcion" 
                                              name="meta_descripcion" 
                                              rows="3"><?= htmlspecialchars($producto['meta_descripcion'] ?? '') ?></textarea>
                                    <?php if (isset($errores['meta_descripcion'])): ?>
                                        <div class="invalid-feedback">
                                            <?= $errores['meta_descripcion'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Recomendado: 150-160 caracteres. Si se deja vacío, se usará la descripción corta.
                                    </div>
                                </div>
                                
                                <div class="mb-0">
                                    <label for="slug" class="form-label">
                                        URL amigable
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <?= rtrim(BASE_URL, '/') ?>/producto/
                                        </span>
                                        <input type="text" 
                                               class="form-control <?= isset($errores['slug']) ? 'is-invalid' : '' ?>" 
                                               id="slug" 
                                               name="slug" 
                                               value="<?= htmlspecialchars($producto['slug'] ?? '') ?>">
                                    </div>
                                    <?php if (isset($errores['slug'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?= $errores['slug'] ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Dejar en blanco para generar automáticamente a partir del nombre.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Publish Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Publicación</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" name="guardar" value="publicar" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        <?= $isEdit ? 'Actualizar' : 'Publicar' ?> producto
                                    </button>
                                    
                                    <button type="submit" name="guardar" value="borrador" class="btn btn-outline-secondary">
                                        <i class="far fa-file-alt me-1"></i>
                                        Guardar como borrador
                                    </button>
                                    
                                    <?php if ($isEdit): ?>
                                        <a href="#" 
                                           class="btn btn-outline-danger mt-2" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#deleteModal">
                                            <i class="fas fa-trash-alt me-1"></i> Eliminar producto
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" name="estado">
                                        <option value="publicado" <?= (!isset($producto['estado']) || $producto['estado'] === 'publicado') ? 'selected' : '' ?>>
                                            Publicado
                                        </option>
                                        <option value="borrador" <?= (isset($producto['estado']) && $producto['estado'] === 'borrador') ? 'selected' : '' ?>>
                                            Borrador
                                        </option>
                                        <option value="agotado" <?= (isset($producto['estado']) && $producto['estado'] === 'agotado') ? 'selected' : '' ?>>
                                            Agotado
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        Fecha de publicación
                                    </label>
                                    <input type="datetime-local" 
                                           class="form-control" 
                                           name="fecha_publicacion" 
                                           value="<?= isset($producto['fecha_publicacion']) ? date('Y-m-d\TH:i', strtotime($producto['fecha_publicacion'])) : date('Y-m-d\TH:i') ?>">
                                </div>
                                
                                <?php if ($isEdit): ?>
                                    <div class="alert alert-info mb-0">
                                        <small>
                                            <i class="fas fa-info-circle me-1"></i>
                                            Creado el: <?= date('d/m/Y H:i', strtotime($producto['fecha_creacion'])) ?>
                                            <?php if ($producto['fecha_actualizacion']): ?>
                                                <br>Última actualización: <?= date('d/m/Y H:i', strtotime($producto['fecha_actualizacion'])) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Categories Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Categorías</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Categoría principal</label>
                                    <select class="form-select" name="categoria_principal_id" required>
                                        <option value="">Seleccione una categoría</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= $categoria['id'] ?>" 
                                                <?= (isset($producto['categoria_principal_id']) && $producto['categoria_principal_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($categoria['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-0">
                                    <label class="form-label">Categorías adicionales</label>
                                    <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                        <?php 
                                        $categoriasSeleccionadas = isset($producto['categorias_adicionales']) ? 
                                            array_column($producto['categorias_adicionales'], 'id') : [];
                                        
                                        foreach ($categorias as $categoria): 
                                            if (isset($producto['categoria_principal_id']) && $categoria['id'] == $producto['categoria_principal_id']) {
                                                continue; // Skip the main category
                                            }
                                        ?>
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="categorias_adicionales[]" 
                                                       value="<?= $categoria['id'] ?>"
                                                       id="cat_<?= $categoria['id'] ?>"
                                                       <?= in_array($categoria['id'], $categoriasSeleccionadas) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="cat_<?= $categoria['id'] ?>">
                                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Image Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0">Vista Previa</h5>
                            </div>
                            <div class="card-body text-center">
                                <div id="imagePreview" class="mb-3">
                                    <?php if ($isEdit && !empty($producto['imagen_principal'])): ?>
                                        <img src="<?= BASE_URL . $producto['imagen_principal'] ?>" 
                                             alt="Vista previa" 
                                             id="previewImage" 
                                             class="img-fluid rounded"
                                             style="max-height: 200px;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                             style="height: 200px;">
                                            <div class="text-muted">
                                                <i class="fas fa-image fa-3x mb-2 d-block"></i>
                                                Vista previa de la imagen
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h5 id="previewTitle" class="mb-1"><?= htmlspecialchars($producto['nombre'] ?? 'Nombre del producto') ?></h5>
                                <div class="text-warning mb-2">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                    <span class="text-muted">(0)</span>
                                </div>
                                <h4 class="text-primary mb-0">
                                    $<span id="previewPrice"><?= number_format($producto['precio'] ?? 0, 2) ?></span>
                                    <?php if (isset($producto['precio_descuento']) && $producto['precio_descuento'] > 0): ?>
                                        <small class="text-decoration-line-through text-muted">
                                            $<?= number_format($producto['precio_descuento'], 2) ?>
                                        </small>
                                    <?php endif; ?>
                                </h4>
                                <?php if (isset($producto['stock']) && $producto['stock'] > 0): ?>
                                    <div class="text-success mt-2">
                                        <i class="fas fa-check-circle"></i> En stock (<?= $producto['stock'] ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="text-danger mt-2">
                                        <i class="fas fa-times-circle"></i> Sin stock
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($isEdit): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.</p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete">
                    <label class="form-check-label" for="confirmDelete">
                        Sí, deseo eliminar este producto permanentemente
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" action="<?= BASE_URL ?>/vendedor/productos/eliminar/<?= $producto['id'] ?>" method="POST">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" id="deleteButton" class="btn btn-danger" disabled>
                        <i class="fas fa-trash-alt me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php 
$scripts = [
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    'https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js',
    BASE_URL . '/assets/js/vendedor/productos.js'
];
?>

<script>
// Enable delete button when checkbox is checked
document.addEventListener('DOMContentLoaded', function() {
    const confirmDelete = document.getElementById('confirmDelete');
    const deleteButton = document.getElementById('deleteButton');
    
    if (confirmDelete && deleteButton) {
        confirmDelete.addEventListener('change', function() {
            deleteButton.disabled = !this.checked;
        });
    }
    
    // Update preview when title or price changes
    const titleInput = document.getElementById('nombre');
    const priceInput = document.getElementById('precio');
    const previewTitle = document.getElementById('previewTitle');
    const previewPrice = document.getElementById('previewPrice');
    
    if (titleInput && previewTitle) {
        titleInput.addEventListener('input', function() {
            previewTitle.textContent = this.value || 'Nombre del producto';
        });
    }
    
    if (priceInput && previewPrice) {
        priceInput.addEventListener('input', function() {
            const value = parseFloat(this.value) || 0;
            previewPrice.textContent = value.toFixed(2);
        });
    }
    
    // Image preview
    const imageInput = document.getElementById('imagen_principal');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImage');
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const previewDiv = document.getElementById('imagePreview');
                        if (previewDiv) {
                            previewDiv.innerHTML = `<img src="${e.target.result}" id="previewImage" class="img-fluid rounded" style="max-height: 200px;">`;
                        }
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Auto-generate slug from title
    const title = document.getElementById('nombre');
    const slug = document.getElementById('slug');
    
    if (title && slug) {
        title.addEventListener('blur', function() {
            if (!slug.value) {
                const slugValue = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove special chars
                    .replace(/\s+/g, '-') // Replace spaces with -
                    .replace(/--+/g, '-') // Replace multiple - with single -
                    .trim();
                slug.value = slugValue;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
