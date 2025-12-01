<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h5 mb-0">
                            <i class="fas fa-user-edit me-2"></i>
                            <?= !empty($perfil['nombre_tienda']) ? 'Editar Perfil' : 'Completar Perfil de Vendedor' ?>
                        </h1>
                        <a href="<?= url('vendedor/perfil') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($errores['general'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $errores['general'] ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('vendedor/perfil/guardar') ?>" method="POST" enctype="multipart/form-data" id="perfilForm">
                        <div class="row">
                            <div class="col-md-4">
                                <!-- Profile Picture Upload -->
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <div class="mb-3 position-relative">
                                            <?php if (!empty($perfil['foto_perfil'])): ?>
                                                <img id="previewImage" 
                                                     src="<?= htmlspecialchars($perfil['foto_perfil']) ?>" 
                                                     alt="Foto de perfil" 
                                                     class="img-fluid rounded-circle mb-3" 
                                                     style="width: 200px; height: 200px; object-fit: cover;">
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger position-absolute" 
                                                        style="top: 10px; right: 25px;"
                                                        onclick="document.getElementById('foto_perfil').value = ''; document.getElementById('previewImage').src = '<?= url('assets/img/default-avatar.png') ?>';">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <img id="previewImage" 
                                                     src="<?= url('assets/img/default-avatar.png') ?>" 
                                                     alt="Foto de perfil" 
                                                     class="img-fluid rounded-circle mb-3" 
                                                     style="width: 200px; height: 200px; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="foto_perfil" class="form-label">Cambiar foto de perfil</label>
                                            <input class="form-control form-control-sm" 
                                                   type="file" 
                                                   id="foto_perfil" 
                                                   name="foto_perfil" 
                                                   accept="image/*"
                                                   onchange="previewFile(this)">
                                            <?php if (isset($errores['foto_perfil'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= $errores['foto_perfil'] ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-text">Formatos: JPG, PNG o GIF. Máx. 5MB</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Store Information -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Redes Sociales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="facebook" class="form-label">
                                                <i class="fab fa-facebook text-primary me-2"></i> Facebook
                                            </label>
                                            <input type="url" 
                                                   class="form-control form-control-sm <?= isset($errores['redes_sociales.facebook']) ? 'is-invalid' : '' ?>" 
                                                   id="facebook" 
                                                   name="redes_sociales[facebook]" 
                                                   value="<?= htmlspecialchars($perfil['redes_sociales']['facebook'] ?? '') ?>"
                                                   placeholder="https://facebook.com/tu-tienda">
                                            <?php if (isset($errores['redes_sociales.facebook'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['redes_sociales.facebook'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="instagram" class="form-label">
                                                <i class="fab fa-instagram text-danger me-2"></i> Instagram
                                            </label>
                                            <input type="url" 
                                                   class="form-control form-control-sm <?= isset($errores['redes_sociales.instagram']) ? 'is-invalid' : '' ?>" 
                                                   id="instagram" 
                                                   name="redes_sociales[instagram]" 
                                                   value="<?= htmlspecialchars($perfil['redes_sociales']['instagram'] ?? '') ?>"
                                                   placeholder="https://instagram.com/tu-tienda">
                                            <?php if (isset($errores['redes_sociales.instagram'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['redes_sociales.instagram'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="twitter" class="form-label">
                                                <i class="fab fa-twitter text-info me-2"></i> Twitter
                                            </label>
                                            <input type="url" 
                                                   class="form-control form-control-sm <?= isset($errores['redes_sociales.twitter']) ? 'is-invalid' : '' ?>" 
                                                   id="twitter" 
                                                   name="redes_sociales[twitter]" 
                                                   value="<?= htmlspecialchars($perfil['redes_sociales']['twitter'] ?? '') ?>"
                                                   placeholder="https://twitter.com/tu-tienda">
                                            <?php if (isset($errores['redes_sociales.twitter'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['redes_sociales.twitter'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-0">
                                            <label for="tiktok" class="form-label">
                                                <i class="fab fa-tiktok me-2"></i> TikTok
                                            </label>
                                            <input type="url" 
                                                   class="form-control form-control-sm <?= isset($errores['redes_sociales.tiktok']) ? 'is-invalid' : '' ?>" 
                                                   id="tiktok" 
                                                   name="redes_sociales[tiktok]" 
                                                   value="<?= htmlspecialchars($perfil['redes_sociales']['tiktok'] ?? '') ?>"
                                                   placeholder="https://tiktok.com/@tutienda">
                                            <?php if (isset($errores['redes_sociales.tiktok'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['redes_sociales.tiktok'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <!-- Store Information -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Información de la Tienda</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nombre_tienda" class="form-label">
                                                    Nombre de la Tienda <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control <?= isset($errores['nombre_tienda']) ? 'is-invalid' : '' ?>" 
                                                       id="nombre_tienda" 
                                                       name="nombre_tienda" 
                                                       value="<?= htmlspecialchars($perfil['nombre_tienda'] ?? '') ?>"
                                                       required>
                                                <?php if (isset($errores['nombre_tienda'])): ?>
                                                    <div class="invalid-feedback">
                                                        <?= $errores['nombre_tienda'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="rut_documento" class="form-label">
                                                    RUT / Documento <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control <?= isset($errores['rut_documento']) ? 'is-invalid' : '' ?>" 
                                                       id="rut_documento" 
                                                       name="rut_documento" 
                                                       value="<?= htmlspecialchars($perfil['rut_documento'] ?? '') ?>"
                                                       required>
                                                <?php if (isset($errores['rut_documento'])): ?>
                                                    <div class="invalid-feedback">
                                                        <?= $errores['rut_documento'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email_empresa" class="form-label">
                                                Correo Electrónico de la Empresa <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" 
                                                   class="form-control <?= isset($errores['email_empresa']) ? 'is-invalid' : '' ?>" 
                                                   id="email_empresa" 
                                                   name="email_empresa" 
                                                   value="<?= htmlspecialchars($perfil['email_empresa'] ?? '') ?>"
                                                   required>
                                            <?php if (isset($errores['email_empresa'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['email_empresa'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="telefono_contacto" class="form-label">
                                                Teléfono de Contacto
                                            </label>
                                            <input type="tel" 
                                                   class="form-control <?= isset($errores['telefono_contacto']) ? 'is-invalid' : '' ?>" 
                                                   id="telefono_contacto" 
                                                   name="telefono_contacto" 
                                                   value="<?= htmlspecialchars($perfil['telefono_contacto'] ?? '') ?>">
                                            <?php if (isset($errores['telefono_contacto'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['telefono_contacto'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="descripcion_tienda" class="form-label">
                                                Descripción de la Tienda
                                            </label>
                                            <textarea class="form-control <?= isset($errores['descripcion_tienda']) ? 'is-invalid' : '' ?>" 
                                                      id="descripcion_tienda" 
                                                      name="descripcion_tienda" 
                                                      rows="4"><?= htmlspecialchars($perfil['descripcion_tienda'] ?? '') ?></textarea>
                                            <?php if (isset($errores['descripcion_tienda'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['descripcion_tienda'] ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="form-text">
                                                Cuéntales a tus clientes sobre tu tienda, productos y lo que los hace especiales.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Ubicación</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="direccion_tienda" class="form-label">
                                                Dirección de la Tienda
                                            </label>
                                            <input type="text" 
                                                   class="form-control <?= isset($errores['direccion_tienda']) ? 'is-invalid' : '' ?>" 
                                                   id="direccion_tienda" 
                                                   name="direccion_tienda" 
                                                   value="<?= htmlspecialchars($perfil['direccion_tienda'] ?? '') ?>">
                                            <?php if (isset($errores['direccion_tienda'])): ?>
                                                <div class="invalid-feedback">
                                                    <?= $errores['direccion_tienda'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="ciudad_tienda" class="form-label">
                                                    Ciudad
                                                </label>
                                                <input type="text" 
                                                       class="form-control <?= isset($errores['ciudad_tienda']) ? 'is-invalid' : '' ?>" 
                                                       id="ciudad_tienda" 
                                                       name="ciudad_tienda" 
                                                       value="<?= htmlspecialchars($perfil['ciudad_tienda'] ?? '') ?>">
                                                <?php if (isset($errores['ciudad_tienda'])): ?>
                                                    <div class="invalid-feedback">
                                                        <?= $errores['ciudad_tienda'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="pais_tienda" class="form-label">
                                                    País
                                                </label>
                                                <select class="form-select <?= isset($errores['pais_tienda']) ? 'is-invalid' : '' ?>" 
                                                        id="pais_tienda" 
                                                        name="pais_tienda">
                                                    <option value="">Seleccionar país</option>
                                                    <option value="Chile" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'Chile') ? 'selected' : '' ?>>Chile</option>
                                                    <option value="Argentina" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'Argentina') ? 'selected' : '' ?>>Argentina</option>
                                                    <option value="Perú" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'Perú') ? 'selected' : '' ?>>Perú</option>
                                                    <option value="Colombia" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'Colombia') ? 'selected' : '' ?>>Colombia</option>
                                                    <option value="México" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'México') ? 'selected' : '' ?>>México</option>
                                                    <option value="España" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'España') ? 'selected' : '' ?>>España</option>
                                                    <option value="Estados Unidos" <?= (isset($perfil['pais_tienda']) && $perfil['pais_tienda'] === 'Estados Unidos') ? 'selected' : '' ?>>Estados Unidos</option>
                                                    <option value="Otro" <?= (isset($perfil['pais_tienda']) && !in_array($perfil['pais_tienda'], ['Chile', 'Argentina', 'Perú', 'Colombia', 'México', 'España', 'Estados Unidos'])) ? 'selected' : '' ?>>Otro</option>
                                                </select>
                                                <?php if (isset($errores['pais_tienda'])): ?>
                                                    <div class="invalid-feedback">
                                                        <?= $errores['pais_tienda'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div id="otroPaisContainer" class="mb-3" style="display: none;">
                                            <label for="otro_pais" class="form-label">
                                                Especificar país
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="otro_pais" 
                                                   name="otro_pais" 
                                                   value="<?= (isset($perfil['pais_tienda']) && !in_array($perfil['pais_tienda'], ['Chile', 'Argentina', 'Perú', 'Colombia', 'México', 'España', 'Estados Unidos', ''])) ? htmlspecialchars($perfil['pais_tienda']) : '' ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="<?= url('vendedor/perfil') ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide custom country field
function toggleOtroPais() {
    const paisTienda = document.getElementById('pais_tienda');
    const otroPaisContainer = document.getElementById('otroPaisContainer');
    const otroPaisInput = document.getElementById('otro_pais');
    
    if (paisTienda.value === 'Otro') {
        otroPaisContainer.style.display = 'block';
        otroPaisInput.setAttribute('name', 'pais_tienda');
    } else {
        otroPaisContainer.style.display = 'none';
        otroPaisInput.removeAttribute('name');
    }
}

// Preview image before upload
function previewFile(input) {
    const file = input.files[0];
    const preview = document.getElementById('previewImage');
    const reader = new FileReader();
    
    reader.onloadend = function() {
        preview.src = reader.result;
    }
    
    if (file) {
        reader.readAsDataURL(file);
    } else {
        preview.src = "<?= url('assets/img/default-avatar.png') ?>";
    }
}

// Initialize form validation
document.addEventListener('DOMContentLoaded', function() {
    // Initialize country field
    toggleOtroPais();
    document.getElementById('pais_tienda').addEventListener('change', toggleOtroPais);
    
    // Initialize form validation
    const form = document.getElementById('perfilForm');
    form.addEventListener('submit', function(event) {
        let valid = true;
        
        // Validate required fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Validate email format
        const emailField = document.getElementById('email_empresa');
        if (emailField && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
            emailField.classList.add('is-invalid');
            if (!emailField.nextElementSibling || !emailField.nextElementSibling.classList.contains('invalid-feedback')) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = 'Por favor ingrese un correo electrónico válido';
                emailField.parentNode.insertBefore(errorDiv, emailField.nextSibling);
            }
            valid = false;
        }
        
        if (!valid) {
            event.preventDefault();
            event.stopPropagation();
            
            // Scroll to first error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    // Clear error when user types in a field
    form.querySelectorAll('input, textarea, select').forEach(function(field) {
        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
