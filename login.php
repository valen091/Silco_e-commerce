<?php
// Incluir configuración y clases necesarias
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/Session.php';
    require_once __DIR__ . '/includes/functions.php';

    // Inicializar la instancia de sesión (esto manejará la sesión automáticamente)
    $session = Session::getInstance();
    
    // Forzar la visualización de la página de inicio de sesión si se solicita
    $forceLogin = isset($_GET['force_login']) && $_GET['force_login'] == '1';
    $error = '';
    $email = '';
    
    // Debug: Verificar estado de la sesión
    error_log('Session status: ' . session_status());
    error_log('Session ID: ' . session_id());
    error_log('Session data: ' . print_r($_SESSION, true));
    
    // Generar un nuevo token CSRF si no existe
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
        }
    }

    // Manejar cierre de sesión si se solicita
    if (isset($_GET['logout']) && $_GET['logout'] == '1') {
        $session->destroy();
        header('Location: login.php');
        exit;
    }
    
    // Solo verificar redirección si no es una solicitud POST y no se está forzando el login
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$forceLogin) {
        // Si el usuario ya está autenticado, redirigir al inicio
        if ($session->isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }

    // Procesar el formulario de inicio de sesión
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Debug: Log the tokens for verification
        error_log('Posted CSRF Token: ' . $csrfToken);
        error_log('Session CSRF Token: ' . ($_SESSION['csrf_token'] ?? 'NOT SET'));
        
        if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            $error = 'Error de seguridad. Por favor, recarga la página e intenta de nuevo.';
            error_log("CSRF token validation failed");
            error_log('CSRF Token Mismatch - Session: ' . ($_SESSION['csrf_token'] ?? 'Not set') . ' vs Posted: ' . $csrfToken);
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Por favor ingresa tu correo y contraseña.';
            } else {
                try {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();

                    // Buscar usuario por email
                    $stmt = $conn->prepare("SELECT id, nombre, email, password, es_vendedor FROM usuarios WHERE email = ? LIMIT 1");
                    if (!$stmt->execute([$email])) {
                        throw new Exception("Error al ejecutar la consulta de usuario");
                    }

                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($password, $user['password'])) {
                        error_log("User authenticated successfully: " . $user['email']);
                        
                        // Iniciar sesión
                        if (method_exists($session, 'loginUser')) {
                            $session->loginUser($user);
                            error_log("Session loginUser method called");
                        } else {
                            // Fallback si el método loginUser no existe
                            // Set session variables that match what header.php expects
                            $userRole = $user['es_vendedor'] ? 'vendedor' : 'cliente';
                            
                            // Set in both $_SESSION and session object for compatibility
                            $_SESSION = array_merge($_SESSION, [
                                'user_id' => $user['id'],
                                'user_email' => $user['email'],
                                'user_name' => $user['nombre'],
                                'user_nombre' => $user['nombre'],
                                'user_role' => $userRole,
                                'es_vendedor' => $user['es_vendedor']  // Some parts might check this directly
                            ]);
                            
                            // Also set them in the session object for compatibility
                            $session->set('user_id', $user['id']);
                            $session->set('user_email', $user['email']);
                            $session->set('user_name', $user['nombre']);
                            $session->set('user_nombre', $user['nombre']);
                            $session->set('user_role', $userRole);
                            $session->set('es_vendedor', $user['es_vendedor']);
                            
                            // Debug log
                            error_log('Session after login: ' . print_r($_SESSION, true));
                            error_log("Session set manually - User ID: " . $user['id']);
                        }
                        
                        // Debug session data
                        error_log("Session data after login: " . print_r($_SESSION, true));

                        // Redirigir según el rol
                        $userRole = ($user['es_vendedor'] == 1) ? 'vendedor' : 'cliente';
                        $session->set('user_role', $userRole);
                        
                        // Actualizar la cookie de sesión manualmente para asegurar la persistencia
                        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                        setcookie(
                            session_name(),
                            session_id(),
                            [
                                'expires' => time() + (60 * 60 * 24 * 30), // 30 días
                                'path' => '/',
                                'domain' => $_SERVER['HTTP_HOST'],
                                'secure' => $secure,
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]
                        );

                        // Por defecto, redirigir al inicio
                        $redirectTo = 'index.php';
                        
                        // Si el usuario venía de una URL específica y es válida, usarla
                        if ($session->has('redirect_after_login')) {
                            $savedRedirect = $session->get('redirect_after_login');
                            $session->remove('redirect_after_login');
                            
                            // Validar que la URL de redirección sea segura
                            if (preg_match('/^[a-zA-Z0-9\/\-_.]+$/', $savedRedirect)) {
                                // Verificar si la redirección es a una sección de vendedor
                                if (strpos($savedRedirect, 'vendedor/') === 0) {
                                    // Solo permitir acceso a secciones de vendedor si el usuario es vendedor
                                    if ($userRole === 'vendedor') {
                                        $redirectTo = $savedRedirect;
                                    } else {
                                        $redirectTo = 'index.php';
                                        $_SESSION['error'] = 'No tienes permiso para acceder a la sección de vendedor.';
                                    }
                                } else {
                                    // Para otras redirecciones, permitir si son seguras
                                    $redirectTo = $savedRedirect;
                                }
                            }
                        }

                        // Validar URL de redirección
                        if (!preg_match('/^[a-zA-Z0-9\/\-_.]+$/', $redirectTo)) {
                            $redirectTo = 'index.php';
                        }
                        
                        // Debug: Verificar datos de sesión antes de redirigir
                        error_log('Before redirect - Session data: ' . print_r($_SESSION, true));
                        
                        // Redirigir
                        header('Location: ' . $redirectTo);
                        exit();
                    } else {
                        $error = 'Correo o contraseña incorrectos.';
                        // Pequeño retraso para evitar ataques de fuerza bruta
                        usleep(rand(100000, 2000000));
                    }
                } catch (Exception $e) {
                    error_log("Error en el inicio de sesión: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
    }
    
    // Usar el token CSRF existente o generar uno nuevo
    $csrfToken = $_SESSION['csrf_token'] ?? '';
    if (empty($csrfToken)) {
        $csrfToken = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrfToken;
    }
    
} catch (Exception $e) {
    error_log("Error crítico en login.php: " . $e->getMessage());
    $error = 'Ocurrió un error inesperado. Por favor, inténtalo de nuevo más tarde.';
    $csrfToken = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesi&oacute;n - Silco</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Ignore errors from browser extensions */
        .ignore-errors {
            display: none;
        }
        
        /* Hide search bar on login page */
        .search-container {
            display: none !important;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background: #1a73e8;
            color: white;
            padding: 12px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            background-color: #1a73e8 !important;
        }
        .input-group-text {
            cursor: pointer;
            background-color: #f8f9fa;
        }
        .btn:hover {
            background: #1557b0;
        }
        .error-message {
            color: #d32f2f;
            background-color: #fde7e9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        .login-links {
            margin-top: 15px;
            text-align: center;
        }
        .login-links a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 500;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
        .hero-section {
            background-color: #1a73e8;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 20px;
            padding: 20px;
            text-align: center;
        }
        
        .error-ignore {
            display: none;
        }
    </style>
    <script>
    // Ignore errors from browser extensions
    window.addEventListener('error', function(e) {
        if (e.filename && 
            (e.filename.includes('contentLogger.js') || 
             e.filename.includes('getImage.js') ||
             e.filename.includes('hero-bg.jpg'))) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    </script>
</head>
<body class="login-page">
    <?php 
    // Set flags to modify header behavior
    $hideSearch = true;
    $hideCartInit = true;
    include 'includes/header.php'; 
    ?>
    
    <main class="login-container">
        <h2 style="text-align: center; margin-bottom: 25px; color: #1a73e8;">
            <i class="fas fa-sign-in-alt" style="color: #1a73e8; margin-right: 10px;"></i>Iniciar Sesi&oacute;n
        </h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <div class="form-group">
                <label for="email">Correo Electr&oacute;nico</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password">Contrase&ntilde;a</label>
                    <a href="forgot-password.php" class="small">¿Olvidaste tu contraseña?</a>
                </div>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <span class="input-group-text toggle-password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn">Iniciar Sesi&oacute;n</button>
            </div>
            
            <div class="login-links text-center mt-4">
                <p class="mb-0">¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
        </form>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Evitar envíos múltiples del formulario
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Iniciando sesión...';
                }
            });
        }
        
        // Mostrar/ocultar contraseña
        const togglePassword = document.querySelector('.toggle-password');
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const passwordInput = document.querySelector('#password');
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        }
    });
    </script>
</body>
</html>