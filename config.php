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
        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? true : false;
        $domain = $_SERVER['HTTP_HOST'];
        
        // Si estamos en localhost, no establecemos el dominio
        if ($domain === 'localhost' || $domain === '127.0.0.1') {
            $domain = '';
        }
        
        $params = [
            'lifetime' => 86400 * 30, // 30 días
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        // Establecer parámetros de la cookie de sesión
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            session_set_cookie_params($params);
        } else {
            session_set_cookie_params(
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
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
