<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'use_strict_mode' => true,
        'use_cookies' => 1,
        'cookie_httponly' => 1,
        'cookie_samesite' => 'Lax'
    ]);
}

require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Token de seguridad inválido.';
    } else {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $apellido = sanitize($_POST['apellido'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $es_vendedor = isset($_POST['es_vendedor']) ? 1 : 0;
        $telefono = sanitize($_POST['telefono'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');
        $ciudad = sanitize($_POST['ciudad'] ?? '');
        $codigo_postal = sanitize($_POST['codigo_postal'] ?? '');
        $pais = sanitize($_POST['pais'] ?? '');

        // Validaciones
        if (empty($nombre) || empty($apellido) || !$email || empty($password) || empty($confirm_password)) {
            $error = 'Todos los campos obligatorios deben ser completados.';
        } elseif ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } else {
            $db = new Database();
            $conn = $db->connect();

            try {
                // Verificar si el correo ya existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'El correo electrónico ya está registrado.';
                } else {
                    // Crear el usuario
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, password, telefono, direccion, ciudad, codigo_postal, pais, es_vendedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$nombre, $apellido, $email, $hashed_password, $telefono, $direccion, $ciudad, $codigo_postal, $pais, $es_vendedor])) {
                        $success = '¡Registro exitoso! Por favor inicia sesión.';
                        // Redirigir después de 2 segundos
                        header("refresh:2;url=login.php");
                    } else {
                        $error = 'Error al registrar el usuario. Por favor, inténtalo de nuevo.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error en registro: " . $e->getMessage());
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
    <title>Registro - Silco</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Registro de Usuario</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido *</label>
                <input type="text" id="apellido" name="apellido" required
                       value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico *</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small>Mínimo 8 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono"
                       value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion"
                       value="<?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="ciudad">Ciudad</label>
                    <input type="text" id="ciudad" name="ciudad"
                           value="<?php echo htmlspecialchars($_POST['ciudad'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="codigo_postal">Código Postal</label>
                    <input type="text" id="codigo_postal" name="codigo_postal"
                           value="<?php echo htmlspecialchars($_POST['codigo_postal'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="pais">País</label>
                    <input type="text" id="pais" name="pais"
                           value="<?php echo htmlspecialchars($_POST['pais'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group checkbox">
                <input type="checkbox" id="es_vendedor" name="es_vendedor" value="1"
                       <?php echo (isset($_POST['es_vendedor']) && $_POST['es_vendedor']) ? 'checked' : ''; ?>>
                <label for="es_vendedor">Deseo registrarme como vendedor</label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrarse</button>
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
            </div>
        </form>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
