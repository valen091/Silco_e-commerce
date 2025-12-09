<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

/**
 * Formatea una URL de imagen para asegurar que tenga la ruta correcta
 * 
 * @param string $path Ruta de la imagen
 * @return string URL formateada de la imagen
 */
function formatImageUrl($path) {
    if (empty($path)) {
        return APP_URL . '/assets/img/placeholder.jpg';
    }
    
    // Si la ruta ya es una URL completa, devolverla tal cual
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    
    // Eliminar cualquier barra inicial para evitar dobles barras
    $path = ltrim($path, '/');
    
    // Si la ruta ya incluye 'Silco/', limpiarla
    if (strpos($path, 'Silco/') === 0) {
        $path = substr($path, 6);
    }
    
    // Asegurar que la ruta no comience con /
    $path = ltrim($path, '/');
    
    // Si la ruta ya comienza con assets/, devolver la URL completa
    if (strpos($path, 'assets/') === 0) {
        return APP_URL . '/' . $path;
    }
    
    // Si la ruta ya comienza con 'uploads/productos/', devolver la URL completa
    if (strpos($path, 'uploads/productos/') === 0) {
        return APP_URL . '/' . $path;
    }
    
    // Verificar si la ruta es un archivo que existe en uploads/productos/
    if (file_exists(__DIR__ . '/../uploads/productos/' . $path)) {
        return APP_URL . '/uploads/productos/' . $path;
    }
    
    // Verificar si la ruta es un archivo que existe en la raíz de uploads
    if (file_exists(__DIR__ . '/../uploads/' . $path)) {
        return APP_URL . '/uploads/' . $path;
    }
    
    // Si no se encuentra la imagen, devolver el placeholder
    return APP_URL . '/assets/img/placeholder.jpg';
}

// Obtener el ID del usuario actual
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Obtener información del usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT id, nombre, apellido, email, es_vendedor FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener usuario: " . $e->getMessage());
        return null;
    }
}

// Generar token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
