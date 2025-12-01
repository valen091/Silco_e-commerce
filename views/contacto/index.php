<?php
$pageTitle = "Contacto - " . APP_NAME;
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h1 class="h3 mb-0">Contáctanos</h1>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['mensaje_exito'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_SESSION['mensaje_exito']) ?>
                            <?php unset($_SESSION['mensaje_exito']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['errores_contacto'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errores_contacto'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 border-0 text-center p-3">
                                <div class="card-body">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-map-marker-alt fa-lg text-primary"></i>
                                    </div>
                                    <h5 class="h6 mb-1">Ubicación</h5>
                                    <p class="text-muted small mb-0">San José, Uruguay</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 border-0 text-center p-3">
                                <div class="card-body">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-envelope fa-lg text-primary"></i>
                                    </div>
                                    <h5 class="h6 mb-1">Email</h5>
                                    <p class="text-muted small mb-0">silco@gmail.com</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 border-0 text-center p-3">
                                <div class="card-body">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-phone-alt fa-lg text-primary"></i>
                                    </div>
                                    <h5 class="h6 mb-1">Teléfono</h5>
                                    <p class="text-muted small mb-0">+598 92 673 601</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 border-0 text-center p-3">
                                <div class="card-body">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-clock fa-lg text-primary"></i>
                                    </div>
                                    <h5 class="h6 mb-1">Horario</h5>
                                    <p class="text-muted small mb-0">Lun-Vie: 9:00-18:00<br>Sáb: 9:00-13:00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <h4 class="h5 mb-4">Envíanos un mensaje</h4>
                            <form action="<?= htmlspecialchars(BASE_URL) ?>/contacto/enviar" method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nombre" class="form-label small fw-bold">Nombre completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="nombre" name="nombre" 
                                               value="<?= htmlspecialchars($_SESSION['datos_contacto']['nombre'] ?? '') ?>" 
                                               required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label small fw-bold">Correo electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control form-control-sm" id="email" name="email" 
                                               value="<?= htmlspecialchars($_SESSION['datos_contacto']['email'] ?? '') ?>" 
                                               required>
                                    </div>
                                    <div class="col-12">
                                        <label for="asunto" class="form-label small fw-bold">Asunto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="asunto" name="asunto" 
                                               value="<?= htmlspecialchars($_SESSION['datos_contacto']['asunto'] ?? '') ?>" 
                                               required>
                                    </div>
                                    <div class="col-12">
                                        <label for="mensaje" class="form-label small fw-bold">Mensaje <span class="text-danger">*</span></label>
                                        <textarea class="form-control form-control-sm" id="mensaje" name="mensaje" rows="4" required><?= htmlspecialchars($_SESSION['datos_contacto']['mensaje'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-sm px-4">
                                            <i class="fas fa-paper-plane me-2"></i> Enviar Mensaje
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="h-100 bg-light rounded p-4">
                                <h4 class="h5 mb-4">Nuestra ubicación</h4>
                                <div class="ratio ratio-16x9 mb-4">
                                    <iframe 
                                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3343.0279999999993!2d-56.3375!3d-34.9011!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzTCsDU0JzA0LjAiUyA1NsKwMjAnMTUuMCJX!5e0!3m2!1sen!2suy!4v1234567890123" 
                                        style="border:0;" 
                                        allowfullscreen="" 
                                        loading="lazy">
                                    </iframe>
                                </div>
                                <p class="small mb-0">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i> San José, Uruguay<br>
                                    <i class="fas fa-phone-alt text-primary me-2"></i> +598 92 673 601<br>
                                    <i class="fas fa-envelope text-primary me-2"></i> silco@gmail.com
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
unset($_SESSION['errores_contacto']);
unset($_SESSION['datos_contacto']);
require_once 'includes/footer.php'; 
?>
