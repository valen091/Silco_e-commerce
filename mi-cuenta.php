<?php
// Iniciar sesión al principio del script
require_once __DIR__ . '/config.php';
configureSession();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Incluir header después de verificar la sesión
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $password = $_POST['password'] ?? '';
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Validate current password if changing password
        if (!empty($nueva_password)) {
            $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!password_verify($password, $user['password'])) {
                throw new Exception("La contraseña actual es incorrecta.");
            }
            
            if ($nueva_password !== $confirmar_password) {
                throw new Exception("Las contraseñas no coinciden.");
            }
            
            if (strlen($nueva_password) < 8) {
                throw new Exception("La nueva contraseña debe tener al menos 8 caracteres.");
            }
            
            // Update password
            $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
        }
        
        // Update user profile
        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?");
        $stmt->execute([$nombre, $email, $telefono, $direccion, $user_id]);
        
        // Update session
        $_SESSION['user_nombre'] = $nombre;
        $_SESSION['user_email'] = $email;
        
        $conn->commit();
        $success_message = "Perfil actualizado correctamente.";
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error_message = $e->getMessage();
    }
}

// Get current user data
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("Usuario no encontrado.");
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar-circle" style="width: 100px; height: 100px; background-color: #f0f0f0; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #6c757d;">
                            <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                        </div>
                    </div>
                    <h5 class="card-title mb-1"><?= htmlspecialchars($user['nombre']) ?></h5>
                    <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <div class="list-group list-group-flush">
                        <a href="mi-cuenta" class="list-group-item list-group-item-action active">
                            <i class="bi bi-person me-2"></i> Mi Perfil
                        </a>
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>/mis-pedidos.php">
                            <i class="bi bi-box-seam me-2"></i> Mis Pedidos
                        </a>
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>/favoritos.php">
                            <i class="bi bi-heart me-2"></i> Favoritos
                        </a>
                        <?php if (isset($_SESSION['es_vendedor']) && $_SESSION['es_vendedor']): ?>
                        <a class="nav-link text-warning" href="<?= BASE_URL ?>/vendedor/panel.php">
                            <i class="bi bi-shop"></i> Panel Vendedor
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mi Perfil</h5>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($user['nombre']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" 
                                      rows="3"><?= htmlspecialchars($user['direccion'] ?? '') ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        <h6 class="mb-3">Cambiar Contraseña</h6>
                        <div class="alert alert-info">
                            <small>Deja estos campos en blanco si no deseas cambiar la contraseña.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nueva_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="nueva_password" name="nueva_password">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_password" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_password" name="confirmar_password">
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
