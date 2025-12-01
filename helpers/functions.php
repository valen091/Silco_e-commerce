<?php

/**
 * Get the base URL of the application
 */
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove 'public' from the path if present
    $scriptName = str_replace('/public', '', $scriptName);
    
    // Ensure there's no double slashes
    $basePath = rtrim($scriptName, '/');
    $path = ltrim($path, '/');
    
    return "{$protocol}://{$host}{$basePath}/{$path}";
}

/**
 * Generate a URL for the given path
 */
function url($path = '') {
    return base_url($path);
}

/**
 * Redirect to a URL
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . url($url), true, $statusCode);
    exit();
}

/**
 * Check if the user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the current user is a seller
 */
function isVendedor() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendedor';
}

/**
 * Set a flash message in the session
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Get flash messages and clear them from the session
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    
    // Filter out messages older than 10 minutes
    $now = time();
    return array_filter($messages, function($message) use ($now) {
        return ($now - $message['timestamp']) < 600; // 10 minutes
    });
}

/**
 * Display flash messages
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    if (empty($messages)) {
        return '';
    }
    
    $output = '<div class="flash-messages">';
    foreach ($messages as $message) {
        $alertClass = 'alert-' . $message['type'];
        $output .= sprintf(
            '<div class="alert %s alert-dismissible fade show" role="alert">%s' .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' .
            '</div>',
            $alertClass,
            htmlspecialchars($message['message'])
        );
    }
    $output .= '</div>';
    
    return $output;
}

/**
 * Get the appropriate badge class for order status
 */
function getStatusBadgeClass($status) {
    $statuses = [
        'pendiente' => 'bg-warning',
        'procesando' => 'bg-info',
        'enviado' => 'bg-primary',
        'completado' => 'bg-success',
        'cancelado' => 'bg-danger',
        'reembolsado' => 'bg-secondary',
        'fallido' => 'bg-dark'
    ];
    
    return $statuses[strtolower($status)] ?? 'bg-secondary';
}

/**
 * Format price with currency
 */
function formatPrice($price, $currency = '$') {
    return $currency . number_format($price, 0, ',', '.');
}

/**
 * Get the first error message for a field
 */
function getFieldError($field, $errors) {
    if (isset($errors[$field])) {
        if (is_array($errors[$field])) {
            return $errors[$field][0] ?? '';
        }
        return $errors[$field];
    }
    return '';
}

/**
 * Check if a field has an error
 */
function hasError($field, $errors) {
    return isset($errors[$field]) ? 'is-invalid' : '';
}

/**
 * Generate a CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get current date and time in MySQL format
 */
function now() {
    return date('Y-m-d H:i:s');
}

/**
 * Format date to a more readable format
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Get the user's IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    return $ip;
}

/**
 * Check if the request is an AJAX request
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Get the current URL
 */
function currentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get the previous URL from the session or referrer
 */
function previousUrl() {
    return $_SERVER['HTTP_REFERER'] ?? url();
}

/**
 * Check if the current route matches the given pattern
 */
function isRoute($pattern) {
    $currentRoute = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $pattern = trim($pattern, '/');
    
    // Convert URL parameters to regex pattern
    $pattern = preg_replace('/\//', '\/', $pattern);
    $pattern = preg_replace('/\{([^\}]+)\}/', '([^\/]+)', $pattern);
    
    return preg_match("/^{$pattern}$/", $currentRoute);
}

/**
 * Get the current route name
 */
function currentRoute() {
    return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
}

/**
 * Generate a URL for an asset
 */
function asset($path) {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Include a view file with data
 */
function view($view, $data = []) {
    extract($data);
    $viewFile = __DIR__ . '/../views/' . ltrim($view, '/') . '.php';
    
    if (file_exists($viewFile)) {
        ob_start();
        include $viewFile;
        return ob_get_clean();
    }
    
    throw new Exception("View [{$view}] not found.");
}

/**
 * Include a component with data
 */
function component($component, $data = []) {
    return view('components/' . $component, $data);
}

/**
 * Get old input value
 */
function old($field, $default = '') {
    return $_SESSION['_old_input'][$field] ?? $default;
}

/**
 * Set old input values
 */
function withInput() {
    $_SESSION['_old_input'] = $_POST;
}

/**
 * Clear old input values
 */
function clearOldInput() {
    unset($_SESSION['_old_input']);
}

/**
 * Get the authenticated user
 */
function auth() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    
    return null;
}

/**
 * Check if the authenticated user has a specific role
 */
function hasRole($role) {
    $user = auth();
    return $user && isset($user['role']) && $user['role'] === $role;
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = currentUrl();
        redirect('login');
    }
}

/**
 * Require a specific role
 */
function requireRole($role) {
    requireAuth();
    
    $user = auth();
    if ($user['role'] !== $role) {
        setFlashMessage('error', 'No tienes permiso para acceder a esta página.');
        redirect('');
    }
}

/**
 * Generate a slug from a string
 */
function slugify($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    // Trim
    $text = trim($text, '-');
    
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    
    // Lowercase
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Truncate a string to a specified length
 */
function truncate($string, $length = 100, $append = '...') {
    if (mb_strlen($string) <= $length) {
        return $string;
    }
    
    return mb_substr($string, 0, $length) . $append;
}
