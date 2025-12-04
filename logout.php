<?php
// Incluir configuración y clases necesarias
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/functions.php';

// Inicializar la sesión
$session = Session::getInstance();

// Limpiar token de recordar si existe
if ($session->isLoggedIn()) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Limpiar token de recordar en la base de datos
        $stmt = $conn->prepare("UPDATE usuarios SET remember_token = NULL, token_expires_at = NULL WHERE id = ?");
        $stmt->execute([$session->get('user_id')]);
    } catch (PDOException $e) {
        error_log("Error al limpiar token de recordar: " . $e->getMessage());
    }
}

// Destruir la sesión
$session->destroy();

// Limpiar todas las cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        
        // Eliminar la cookie estableciendo tiempo de expiración en el pasado
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
        setcookie($name, '', time() - 3600, '/', '.' . $_SERVER['HTTP_HOST']);
    }
}

// Redirigir a la página de login
header('Location: login.php');
exit();

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Set a new session just for the success message
session_start();
$_SESSION['success'] = 'Has cerrado sesión correctamente.';

// Get base URL dynamically
$base_url = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$redirect_url = $base_url . '/login.php';

// Ensure we're not redirecting to a different domain
if (!preg_match('/^\//', $redirect_url)) {
    $redirect_url = '/' . $redirect_url;
}

// Redirect to login page
header('Location: ' . $redirect_url, true, 302);
exit();
