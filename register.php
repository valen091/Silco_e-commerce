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
            $db = Database::getInstance();
            $conn = $db->getConnection();

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hide search bar on register page */
        .search-container {
            display: none !important;
        }
        .auth-form {
            max-width: 500px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="tel"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #1a73e8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        .checkbox {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .checkbox input[type="checkbox"] {
            margin-right: 10px;
            width: auto;
        }
        .btn-primary {
            background: #1a73e8;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background: #1557b0;
        }
        .form-actions {
            text-align: center;
            margin-top: 25px;
        }
        .form-actions a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 500;
        }
        .form-actions a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-danger {
            background-color: #fde7e9;
            color: #d32f2f;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        h1 {
            text-align: center;
            color: #1a73e8;
            margin: 30px 0;
        }
        .required {
            color: #d32f2f;
        }
    </style>
</head>
<body class="login-page">
    <?php 
    // Set a flag to hide search in header
    $hideSearch = true;
    include 'includes/header.php'; 
    ?>
    
    <main class="container">
        <h1><i class="fas fa-user-plus" style="color: #1a73e8; margin-right: 10px;"></i>Registro de Usuario</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido <span class="required">*</span></label>
                <input type="text" id="apellido" name="apellido" required
                       value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico <span class="required">*</span></label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" required minlength="8">
                <small>Mínimo 8 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña <span class="required">*</span></label>
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
