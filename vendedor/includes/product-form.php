<?php
// This file contains the product form used in both new and edit product pages
if (!isset($producto)) {
    $producto = [
        'id' => '',
        'nombre' => '',
        'descripcion' => '',
        'categoria_id' => '',
        'precio' => '',
        'precio_oferta' => '',
        'stock' => 1,
        'condicion' => 'nuevo',
        'activo' => 1,
        'destacado' => 0,
        'imagen_principal' => '',
        'imagenes' => []
    ];
    $form_action = 'nuevo-producto.php';
    $form_title = 'Nuevo Producto';
    $form_submit = 'Agregar Producto';
} else {
    $form_action = 'editar-producto.php?id=' . $producto['id'];
    $form_title = 'Editar Producto';
    $form_submit = 'Guardar Cambios';
}
?>

<div class="card mb-4">
    <div class="card-header">
        <h3>Información Básica</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required 
                           value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?php 
                        echo htmlspecialchars($producto['descripcion']); 
                    ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                        <?php echo ($producto['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="condicion" class="form-label">Condición <span class="text-danger">*</span></label>
                            <select class="form-select" id="condicion" name="condicion" required>
                                <option value="nuevo" <?php echo ($producto['condicion'] == 'nuevo') ? 'selected' : ''; ?>>Nuevo</option>
                                <option value="usado" <?php echo ($producto['condicion'] == 'usado') ? 'selected' : ''; ?>>Usado</option>
                                <option value="reacondicionado" <?php echo ($producto['condicion'] == 'reacondicionado') ? 'selected' : ''; ?>>Reacondicionado</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5 class="card-title">Publicación</h5>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                    <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activo">Producto activo</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="destacado" name="destacado" value="1"
                                    <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="destacado">Destacar producto</label>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="guardar" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> <?php echo $form_submit; ?>
                            </button>
                            <a href="productos.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3>Precio e Inventario</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="precio" name="precio" 
                               step="0.01" min="0" required 
                               value="<?php echo htmlspecialchars($producto['precio']); ?>">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="precio_oferta" class="form-label">Precio de oferta (opcional)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="precio_oferta" name="precio_oferta" 
                               step="0.01" min="0"
                               value="<?php echo htmlspecialchars($producto['precio_oferta'] ?? ''); ?>">
                    </div>
                    <div class="form-text">Dejar en blanco si no hay oferta</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label for="stock" class="form-label">Cantidad en stock <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="stock" name="stock" 
                           min="0" required value="<?php echo htmlspecialchars($producto['stock']); ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3>Imágenes del Producto</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Imágenes actuales</label>
            <div id="imagenes-actuales" class="row g-3">
                <?php if (!empty($producto['imagen_principal'])): ?>
                    <div class="col-6 col-md-3">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($producto['imagen_principal']); ?>" 
                                 class="card-img-top" alt="Imagen principal">
                            <div class="card-body p-2 text-center">
                                <span class="badge bg-primary">Principal</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($producto['imagenes'])): ?>
                    <?php foreach ($producto['imagenes'] as $imagen): ?>
                        <div class="col-6 col-md-3">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($imagen); ?>" 
                                     class="card-img-top" alt="Imagen del producto">
                                <div class="card-body p-2 text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-imagen" 
                                            data-imagen="<?php echo htmlspecialchars($imagen); ?>">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="imagenes" class="form-label">Agregar imágenes (Máx. 10)</label>
            <input type="file" class="form-control" id="imagenes" name="imagenes[]" multiple 
                   accept="image/*" data-max-files="10">
            <div class="form-text">La primera imagen será la principal. Arrastra para cambiar el orden.</div>
        </div>
        
        <div id="preview-container" class="row g-3 mb-3">
            <!-- Aquí se mostrarán las previsualizaciones de las imágenes -->
        </div>
    </div>
</div>

<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

<div class="d-flex justify-content-between">
    <a href="productos.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver al listado
    </a>
    <div>
        <button type="submit" name="guardar" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> <?php echo $form_submit; ?>
        </button>
    </div>
</div>
