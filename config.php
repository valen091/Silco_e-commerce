<?php
// Configuración de la aplicación
define('APP_NAME', 'Silco');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/Silco');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'silco_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de sesión
function configureSession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Configuración de la sesión
        session_name('silco_session');
        
        // Configuración de cookies de sesión
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $params = [
            'lifetime' => 86400, // 24 horas
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        // Establecer parámetros de la cookie de sesión
        session_set_cookie_params($params);
        
        // Iniciar la sesión
        if (!session_start()) {
            error_log('No se pudo iniciar la sesión');
            return false;
        }
        
        return true;
    }
    return false; // La sesión ya estaba activa
}

// Incluir funciones de ayuda
require_once __DIR__ . '/includes/functions.php';

// Incluir configuración de la base de datos
require_once __DIR__ . '/config/database.php';
