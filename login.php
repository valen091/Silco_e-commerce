<?php
session_start([
    'use_strict_mode' => true,
    'use_cookies' => 1,
    'cookie_httponly' => 1,
    'cookie_samesite' => 'Lax'
]);

require_once 'includes/functions.php';

// Si el usuario ya está autenticado, redirigir según su rol
if (isLoggedIn()) {
    if (isVendedor()) {
        redirect('vendedor/panel.php');
    } else {
        redirect('perfil.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido.';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (!$email || empty($password)) {
            $error = 'Por favor, ingresa tu correo electrónico y contraseña.';
        } else {
            $db = new Database();
            $conn = $db->connect();

            try {
                $stmt = $conn->prepare("SELECT id, password, es_vendedor FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Iniciar sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['es_vendedor'] = (bool)$user['es_vendedor'];
                    
                    // Recordar usuario si seleccionó "Recordarme"
                    if ($remember) {
                        try {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            // Verificar si las columnas existen antes de intentar actualizar
                            $stmt = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'remember_token'");
                            if ($stmt->rowCount() === 0) {
                                // Si no existen las columnas, crearlas
                                $conn->exec("ALTER TABLE usuarios 
                                    ADD COLUMN remember_token VARCHAR(100) NULL,
                                    ADD COLUMN token_expires_at DATETIME NULL");
                            }
                            
                            // Guardar token en la base de datos
                            $stmt = $conn->prepare("UPDATE usuarios SET remember_token = ?, token_expires_at = ? WHERE id = ?");
                            $stmt->execute([$token, $expires, $user['id']]);
                            
                            // Establecer cookie
                            setcookie('remember_token', $token, [
                                'expires' => strtotime('+30 days'),
                                'path' => '/',
                                'domain' => '',
                                'secure' => true,
                                'httponly' => true,
                                'samesite' => 'Lax'
                            ]);
                        } catch (PDOException $e) {
                            error_log("Error al procesar 'Recordarme': " . $e->getMessage());
                            // Continuar con el inicio de sesión aunque falle el 'Recordarme'
                        }
                    }
                    
                    // Redirigir a la página principal después del login exitoso
                    redirect('index.php');
                } else {
                    $error = 'Correo electrónico o contraseña incorrectos.';
                }
            } catch (PDOException $e) {
                error_log("Error en inicio de sesión: " . $e->getMessage());
                $error = 'Error en el servidor. Por favor, inténtalo más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Silco</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Iniciar Sesión</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
                <div class="text-right">
                    <a href="recuperar-contrasena.php">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            
            <div class="form-group checkbox">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Recordarme</label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
                <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
            </div>
            
            <div class="social-login">
                <p>O inicia sesión con:</p>
                <div class="social-buttons">
                    <a href="auth/google" class="btn btn-social btn-google">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="auth/facebook" class="btn btn-social btn-facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>
            </div>
        </form>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
