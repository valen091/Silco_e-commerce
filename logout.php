<?php
require_once 'includes/functions.php';
require_once 'includes/config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'use_strict_mode' => true,
        'use_cookies' => 1,
        'cookie_httponly' => 1,
        'cookie_samesite' => 'Lax'
    ]);
}

// Clear remember token from database if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=silco_db;charset=utf8", 'root', 'silco');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $db->prepare("UPDATE usuarios SET remember_token = NULL, token_expires_at = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Error clearing remember token: " . $e->getMessage());
    }
}

// Clear remember me cookie
setcookie('remember_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page
redirect('index.php');
