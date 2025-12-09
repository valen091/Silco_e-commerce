<?php
// Configuración regional para Uruguay
setlocale(LC_ALL, 'es_UY.UTF-8', 'es_UY', 'es_ES', 'es', 'es-ES', 'es-AR');

date_default_timezone_set('America/Montevideo');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

// Crear instancia de la base de datos
$database = new DatabaseConfig();
$pdo = $database->connect();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Formatear teléfono si es necesario
if (!empty($user['telefono'])) {
    $user['telefono'] = preg_replace('/[^0-9]/', '', $user['telefono']);
    if (strlen($user['telefono']) > 8) {
        $user['telefono'] = substr($user['telefono'], -8);
    }
    $user['telefono'] = '+598 ' . substr($user['telefono'], 0, 1) . ' ' . 
                        substr($user['telefono'], 1, 3) . ' ' . 
                        substr($user['telefono'], 4);
}

// Obtener items del carrito
$cart_items = [];
$subtotal = 0;
$total = 0;

// Obtener los items del carrito usando el user_id
$stmt = $pdo->prepare("
    SELECT c.*, p.nombre, p.precio, 
           (SELECT imagen_url FROM imagenes_producto WHERE producto_id = p.id LIMIT 1) as imagen, 
           p.stock 
    FROM carrito c 
    JOIN productos p ON c.producto_id = p.id 
    WHERE c.usuario_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calcular totales
foreach ($cart_items as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$total = $subtotal; // Por ahora sin impuestos ni envío
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Información de Envío</h4>
                </div>
                <div class="card-body">
                    <form id="checkout-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ciudad" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="ciudad" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="codigo_postal" class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="codigo_postal" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="provincia" class="form-label">Departamento</label>
                                <select class="form-select" id="provincia" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Artigas">Artigas</option>
                                    <option value="Canelones">Canelones</option>
                                    <option value="Cerro Largo">Cerro Largo</option>
                                    <option value="Colonia">Colonia</option>
                                    <option value="Durazno">Durazno</option>
                                    <option value="Flores">Flores</option>
                                    <option value="Florida">Florida</option>
                                    <option value="Lavalleja">Lavalleja</option>
                                    <option value="Maldonado">Maldonado</option>
                                    <option value="Montevideo">Montevideo</option>
                                    <option value="Paysandú">Paysandú</option>
                                    <option value="Río Negro">Río Negro</option>
                                    <option value="Rivera">Rivera</option>
                                    <option value="Rocha">Rocha</option>
                                    <option value="Salto">Salto</option>
                                    <option value="San José">San José</option>
                                    <option value="Soriano">Soriano</option>
                                    <option value="Tacuarembó">Tacuarembó</option>
                                    <option value="Treinta y Tres">Treinta y Tres</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="save-address">
                            <label class="form-check-label" for="save-address">
                                Guardar información para futuras compras
                            </label>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Método de Pago</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <p class="mb-4">Haz clic en el botón para pagar con PayPal</p>
                        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" id="paypal-form">
                            <input type="hidden" name="cmd" value="_xclick">
                            <input type="hidden" name="business" value="SilcoEcommerce@silco.com"> <!-- REEMPLAZA CON TU EMAIL DE PAYPAL -->
                            <input type="hidden" name="item_name" value="Compra en <?= htmlspecialchars($config['site_name'] ?? 'Silco') ?>">
                            <input type="hidden" name="currency_code" value="UYU">
                            <input type="hidden" name="amount" id="paypal-amount" value="<?= number_format($total, 2, '.', '') ?>">
                            <input type="hidden" name="no_note" value="1">
                            <input type="hidden" name="lc" value="ES_UY">
                            <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest">
                            <?php
                            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                            $returnUrl = $baseUrl . '/gracias.php';
                            $cancelUrl = $baseUrl . '/checkout.php';
                            $notifyUrl = $baseUrl . '/ipn.php';
                            ?>
                            <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>">
                            <input type="hidden" name="cancel_return" value="<?= htmlspecialchars($cancelUrl) ?>">
                            <input type="hidden" name="notify_url" value="<?= htmlspecialchars($notifyUrl) ?>">
                            <button type="submit" class="btn btn-primary btn-lg w-100" style="background-color: #0070ba; border-color: #0070ba;">
                                <i class="fab fa-paypal me-2"></i>Pagar con PayPal
                            </button>
                        </form>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <div>
                                <strong>¿Necesitas ayuda con el pago?</strong>
                                <p class="mb-1">Si tienes problemas con PayPal, contáctanos a <a href="mailto:soporte@silco.com" class="alert-link">soporte@silco.com</a></p>
                                <p class="mb-0">O llámanos al <a href="tel:+59892673601" class="alert-link">+598 92 673 601</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Resumen del Pedido</h5>
                </div>
                <div class="card-body">
                    <?php if (count($cart_items) > 0): ?>
                        <div class="mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <span class="fw-bold"><?= $item['cantidad'] ?>x</span> 
                                        <?= htmlspecialchars($item['nombre']) ?>
                                    </div>
                                    <div>
                                        $<?= number_format($item['precio'] * $item['cantidad'], 2, ',', '.') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <span>$<?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Envío:</span>
                                <span>Se calcula al finalizar</span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>Total:</span>
                            <span id="total">$U <?= number_format($total, 0, ',', '.') ?></span>
                        </div>
                        
                        <div id="paypal-button-container" class="mt-3">
                            <!-- PayPal button will be rendered here -->
                            <div id="paypal-button"></div>
                        </div>
                        <p class="text-muted small mt-2">
                            Serás redirigido a PayPal para completar tu pago de manera segura.
                        </p>
                        
                        <p class="text-muted small mt-2">
                            Al realizar el pedido, aceptas nuestros <a href="#" class="text-decoration-none">términos y condiciones</a> y <a href="#" class="text-decoration-none">políticas de privacidad</a>.
                        </p>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No hay productos en tu carrito.
                        </div>
                        <a href="index.php" class="btn btn-primary w-100">Seguir Comprando</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Contacto</h5>
                </div>
                <div class="card-body">
                <h6 class="card-title">¿Necesitas ayuda?</h6>
                <p class="card-text small">
                    <i class="bi bi-envelope me-2"></i> <a href="mailto:info@silco.com.uy" class="text-decoration-none">info@silco.com.uy</a><br>
                    <i class="bi bi-telephone me-2"></i> <a href="tel:+59892673601" class="text-decoration-none">+598 92 673 601</a>
                </p>
                <p class="card-text small">
                    <i class="bi bi-clock me-2"></i> Lunes a Viernes de 9:00 a 18:00 hs<br>
                <i class="bi bi-geo-alt me-2"></i> San José de Mayo, Uruguay
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle payment method forms
    document.querySelectorAll('input[name="payment-method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Hide all payment forms
            document.querySelectorAll('#credit-card-form, #transfer-details, #paypal-button-container').forEach(el => {
                el.style.display = 'none';
            });
            
            // Show selected payment form
            if (this.id === 'credit-card') {
                document.getElementById('credit-card-form').style.display = 'block';
            } else if (this.id === 'transfer') {
                document.getElementById('transfer-details').style.display = 'block';
            } else if (this.id === 'paypal') {
                const container = document.getElementById('paypal-button-container');
                container.style.display = 'block';
                loadPayPalSDK();
            } else if (this.id === 'mercadopago') {
                // Aquí iría la integración con MercadoPago
                alert('Serás redirigido a MercadoPago para completar el pago.');
            }
        });
    });
    
    // Initialize PayPal when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize PayPal if it's the default selected payment method
        const paypalRadio = document.querySelector('input[value="paypal"]');
        if (paypalRadio && paypalRadio.checked) {
            initPayPalButton();
        }
        
        // Handle payment method changes
        document.querySelectorAll('input[name="payment-method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'paypal' && this.checked) {
                    initPayPalButton();
                }
            });
        });
    });
    // Cargar SDK de PayPal
    function loadPayPalSDK() {
        return new Promise((resolve, reject) => {
            // Verificar si ya está cargado
            if (window.paypal) {
                resolve();
                return;
            }

            // Crear script de PayPal
            const script = document.createElement('script');
            script.src = 'https://www.paypal.com/sdk/js?client-id=AR-ujDlOqtzzwZOVxEbxc2BkagyofmF8RPhN2OYGhUYMVctjqjfRqqykmhTbTwlpbe6oKMb-9eJJ5f-a&currency=UYU&intent=capture';
            script.async = true;
            
            script.onload = () => {
                if (window.paypal) {
                    resolve();
                } else {
                    reject(new Error('No se pudo cargar el SDK de PayPal'));
                }
            };
            
            script.onerror = () => {
                reject(new Error('Error al cargar el SDK de PayPal'));
            };
            
            document.head.appendChild(script);
        });
    }

    function initPayPalButton() {
        const container = document.getElementById('paypal-button-container');
        if (!container) {
            console.error('Contenedor de PayPal no encontrado');
            return;
        }
        
        // Clear container
        container.innerHTML = '';
        
        loadPayPalSDK().then(() => {
            // Limpiar contenedor
            container.innerHTML = '';
            
            // Crear botón de PayPal
            const buttons = paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'blue',
                    shape: 'rect',
                    label: 'paypal',
                    tagline: false,
                    height: 48
                },
                createOrder: function(data, actions) {
                    // Validación básica del formulario
                    const requiredFields = ['nombre', 'apellido', 'direccion', 'ciudad', 'codigo_postal', 'provincia', 'telefono', 'email'];
                    let isValid = true;
                    
                    // Resetear estados de error
                    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                
                // Validar campos requeridos
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field || !field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                        // Agregar mensaje de error si no existe
                        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = 'Este campo es obligatorio';
                            field.parentNode.insertBefore(errorDiv, field.nextSibling);
                        }
                    }
                });
                
                // Validar email si existe
                const emailField = document.getElementById('email');
                if (emailField && emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                    const errorDiv = emailField.nextElementSibling;
                    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                        errorDiv.textContent = 'Por favor ingresa un email válido';
                    }
                }
                
                if (!isValid) {
                    const firstInvalid = document.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstInvalid.focus();
                    }
                    return false; // Evita que PayPal muestre su propio mensaje de error
                }
                
                // Mostrar cargador
                const paypalButtons = document.querySelectorAll('.paypal-buttons');
                paypalButtons.forEach(btn => {
                    btn.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Procesando...</span></div>';
                });
                
                // Obtener el carrito y calcular total
                return fetch('backend/orders/create-paypal-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'  // Para identificar peticiones AJAX
                    },
                    credentials: 'same-origin',  // Incluir cookies de sesión
                    body: JSON.stringify({
                        cartId: '<?= $_COOKIE["cart_id"] ?? "" ?>',
                        shippingInfo: {
                            nombre: document.getElementById('nombre').value,
                            apellido: document.getElementById('apellido').value,
                            direccion: document.getElementById('direccion').value,
                            ciudad: document.getElementById('ciudad').value,
                            codigo_postal: document.getElementById('codigo_postal').value,
                            provincia: document.getElementById('provincia').value,
                            telefono: document.getElementById('telefono').value,
                            email: document.getElementById('email').value,
                            save_address: document.getElementById('save-address').checked
                        }
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Error al crear la orden de PayPal');
                        });
                    }
                    return response.json();
                })
                .then(order => {
                    if (order.error) {
                        throw new Error(order.error);
                    }
                    return order.id;
                })
                .catch(error => {
                    // Restaurar el botón de PayPal
                    const paypalContainer = document.getElementById('paypal-button-container');
                    paypalContainer.innerHTML = '<div id="paypal-button"></div>';
                    renderPayPalButtons();
                    
                    console.error('Error creating order:', error);
                    throw error; // Re-lanzar para que lo maneje onError
                });
            },
            
            onApprove: function(data, actions) {
                // Mostrar cargador
                const paypalButtons = document.querySelectorAll('.paypal-buttons');
                paypalButtons.forEach(btn => {
                    btn.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Procesando...</span></div>';
                });
                
                // Get the order details first
                return fetch(`/backend/orders/capture-paypal-order.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        orderID: data.orderID
                    }),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Error al capturar el pago');
                        });
                    }
                    return response.json();
                })
                .then(orderData => {
                    if (orderData.error) {
                        throw new Error(orderData.error);
                    }
                    // Redirect to confirmation page with order ID
                    window.location.href = `confirmacion-pedido.php?order_id=${orderData.id}&status=completed`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Restore PayPal button
                    const paypalContainer = document.getElementById('paypal-button-container');
                    paypalContainer.innerHTML = '<div id="paypal-button"></div>';
                    renderPayPalButtons();
                    
                    // Show error message
                    showPayPalError('Error al procesar el pago: ' + (error.message || 'Error desconocido'));
                });
            },
            
                onError: function(err) {
                    console.error('PayPal Error:', err);
                    
                    // More descriptive error messages
                    let errorMessage = 'Ocurrió un error al procesar el pago con PayPal. ';
                    
                    if (err.message && err.message.includes('popup')) {
                        errorMessage = 'El navegador bloqueó la ventana emergente de PayPal. Por favor, desactiva el bloqueador de ventanas emergentes para este sitio y vuelve a intentarlo.';
                    } else if (err.message && (err.message.includes('blocked') || err.message.includes('popup closed'))) {
                        errorMessage = 'La ventana de pago se cerró inesperadamente. Por favor, verifica las configuraciones de tu navegador y vuelve a intentarlo.';
                    } else if (err.message && err.message.includes('network')) {
                        errorMessage = 'Error de conexión con PayPal. Por favor, verifica tu conexión a internet e intenta nuevamente.';
                    } else if (err.message && err.message.includes('security')) {
                        errorMessage = 'Error de seguridad en la conexión con PayPal. Por favor, verifica que la fecha y hora de tu dispositivo sean correctas.';
                    } else if (err.message) {
                        errorMessage += err.message;
                    } else {
                        errorMessage += 'Por favor, inténtalo de nuevo o elige otro método de pago.';
                    }
                    
                    showPayPalError(errorMessage);
                    
                    // Reset PayPal button
                    const container = document.getElementById('paypal-button-container');
                    if (container) {
                        container.innerHTML = '<div id="paypal-button"></div>';
                        renderPayPalButtons();
                    }
                },
            
                onCancel: function(data) {
                    console.log('El usuario canceló el pago');
                    showPayPalError('Has cancelado el proceso de pago. Si fue un error, puedes intentarlo de nuevo.');
                    
                    // Reset PayPal button
                    const container = document.getElementById('paypal-button-container');
                    if (container) {
                        container.innerHTML = '<div id="paypal-button"></div>';
                        renderPayPalButtons();
                    }
                },
                
                // Add onClick handler for better user feedback
                onClick: function(data, actions) {
                    // Validate form before showing PayPal popup
                    const requiredFields = ['nombre', 'apellido', 'direccion', 'ciudad', 'codigo_postal', 'provincia', 'telefono', 'email'];
                    let isValid = true;
                    
                    // Reset error states
                    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    
                    // Validate required fields
                    requiredFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field && !field.value.trim()) {
                            isValid = false;
                            field.classList.add('is-invalid');
                            if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'invalid-feedback';
                                errorDiv.textContent = 'Este campo es obligatorio';
                                field.parentNode.insertBefore(errorDiv, field.nextSibling);
                            }
                        }
                    });
                    
                    // Validate email format
                    const emailField = document.getElementById('email');
                    if (emailField && emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                        isValid = false;
                        emailField.classList.add('is-invalid');
                        const errorDiv = emailField.nextElementSibling;
                        if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                            errorDiv.textContent = 'Por favor ingresa un email válido';
                        }
                    }
                    
                    if (!isValid) {
                        const firstInvalid = document.querySelector('.is-invalid');
                        if (firstInvalid) {
                            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstInvalid.focus();
                        }
                        // Prevent PayPal popup from showing
                        return false;
                    }
                    
                    // Show loading state
                    const container = document.getElementById('paypal-button-container');
                    if (container) {
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando PayPal...</span>
                                </div>
                                <p class="mt-2">Preparando el pago seguro...</p>
                            </div>`;
                    }
                    
                    return true; // Allow PayPal popup to show
                }
            }).render('#paypal-button-container');
                
                console.log('Botón de PayPal renderizado exitosamente');
            })
            .catch(error => {
                console.error('Error al cargar PayPal:', error);
                showPayPalError('Error al cargar el servicio de pago. Por favor, intenta de nuevo.');
            });
    }
            
            console.error('Error en el botón de PayPal:', error);
            showPayPalError('Error al cargar el servicio de pago. Por favor, intenta de nuevo.');
        }
    }

    // Función para mostrar errores de PayPal
    function showPayPalError(message) {
        const container = document.getElementById('paypal-button-container');
        if (!container) return;
        
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
                <button class="btn btn-sm btn-outline-danger ms-2" onclick="renderPayPalButtons()">
                    <i class="bi bi-arrow-clockwise"></i> Reintentar
                </button>
            </div>`;
    }

    // Inicializar PayPal al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar PayPal si está seleccionado por defecto
        const paypalRadio = document.querySelector('input[value="paypal"]');
        if (paypalRadio && paypalRadio.checked) {
            initPayPalButton();
        }
        
        // Manejar cambio de método de pago
        document.querySelectorAll('input[name="payment-method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'paypal' && this.checked) {
                    initPayPalButton();
                }
            });
        });
    });
    
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
