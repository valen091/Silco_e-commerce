<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
$session = Session::getInstance();

// Function to test database connection
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        return [
            'success' => true,
            'message' => '✅ Conexión a la base de datos exitosa',
            'connection' => $conn
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => '❌ Error de conexión a la base de datos: ' . $e->getMessage()
        ];
    }
}

// Function to test user authentication
function testUserLogin($email, $password) {
    $result = [];
    
    // Test database connection first
    $dbTest = testDatabaseConnection();
    $result['database'] = $dbTest;
    
    if (!$dbTest['success']) {
        return $result;
    }
    
    $conn = $dbTest['connection'];
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, nombre, email, password, es_vendedor FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $result['user'] = [
                'success' => false,
                'message' => '❌ No se encontró ningún usuario con el correo: ' . htmlspecialchars($email)
            ];
            return $result;
        }
        
        $result['user'] = [
            'success' => true,
            'message' => '✅ Usuario encontrado: ' . htmlspecialchars($user['email']),
            'user_id' => $user['id'],
            'is_vendor' => (bool)$user['es_vendedor']
        ];
        
        // Test password
        $passwordMatch = password_verify($password, $user['password']);
        $result['password'] = [
            'success' => $passwordMatch,
            'message' => $passwordMatch 
                ? '✅ Contraseña correcta' 
                : '❌ Contraseña incorrecta',
            'stored_hash' => $user['password']
        ];
        
        // If password is correct, test session
        if ($passwordMatch) {
            $session = Session::getInstance();
            
            // Set session data
            $session->set('user_id', $user['id']);
            $session->set('user_email', $user['email']);
            $session->set('user_name', $user['nombre']);
            $session->set('user_role', $user['es_vendedor'] ? 'vendedor' : 'cliente');
            
            // Verify session data
            $sessionData = [
                'user_id' => $session->get('user_id'),
                'user_email' => $session->get('user_email'),
                'user_name' => $session->get('user_name'),
                'user_role' => $session->get('user_role'),
                'session_id' => session_id()
            ];
            
            $result['session'] = [
                'success' => !empty($sessionData['user_id']),
                'message' => !empty($sessionData['user_id']) 
                    ? '✅ Sesión iniciada correctamente' 
                    : '❌ Error al iniciar sesión',
                'data' => $sessionData
            ];
        }
        
    } catch (Exception $e) {
        $result['error'] = [
            'success' => false,
            'message' => '❌ Error: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
    
    return $result;
}

// Process form submission
$testResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $testResults = testUserLogin($email, $password);
    } else {
        $testResults['error'] = [
            'success' => false,
            'message' => '❌ Por favor ingresa un correo y contraseña'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depuración de Inicio de Sesión - Silco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .result-box { 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 5px;
            border-left: 5px solid #ddd;
        }
        .success { 
            background-color: #d4edda; 
            border-color: #28a745;
        }
        .error { 
            background-color: #f8d7da; 
            border-color: #dc3545;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4">Depuración de Inicio de Sesión</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Probar Inicio de Sesión</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Probar Inicio de Sesión</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Resultados de la Prueba</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($testResults)): ?>
                            <?php foreach ($testResults as $key => $test): ?>
                                <?php if (is_array($test) && isset($test['success'])): ?>
                                    <div class="result-box <?php echo $test['success'] ? 'success' : 'error'; ?>">
                                        <h6><?php echo $test['message']; ?></h6>
                                        <?php if (isset($test['data'])): ?>
                                            <pre class="mt-2 mb-0"><?php print_r($test['data']); ?></pre>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Ingresa tus credenciales para probar el inicio de sesión.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="card">
                <div class="card-header">
                    <h5>Información de la Sesión Actual</h5>
                </div>
                <div class="card-body">
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
