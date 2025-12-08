<?php
/**
 * Router for Silco E-commerce
 * Handles all incoming requests and routes them to the appropriate controller
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'use_strict_mode' => true,
        'use_cookies' => 1,
        'cookie_httponly' => 1,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => isset($_SERVER['HTTPS'])
    ]);
}

// Load helper functions
require_once __DIR__ . '/helpers/functions.php';

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Simple router class
class Router {
    protected $routes = [];
    protected $notFoundCallback;
    
    public function get($route, $handler) {
        $this->addRoute('GET', $route, $handler);
    }
    
    public function post($route, $handler) {
        $this->addRoute('POST', $route, $handler);
    }
    
    public function put($route, $handler) {
        $this->addRoute('PUT', $route, $handler);
    }
    
    public function delete($route, $handler) {
        $this->addRoute('DELETE', $route, $handler);
    }
    
    public function addRoute($method, $route, $handler) {
        $this->routes[] = [
            'method' => $method,
            'route' => $route,
            'handler' => $handler
        ];
    }
    
    public function setNotFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = trim($uri, '/');
        
        // Handle base path
        $basePath = str_replace('/public', '', dirname($_SERVER['SCRIPT_NAME']));
        $uri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $uri);
        $uri = trim($uri, '/');
        
        // Check for matching route
        foreach ($this->routes as $route) {
            // Convert route to regex pattern
            $pattern = $this->convertToRegex($route['route']);
            
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                // Remove the full match from matches
                array_shift($matches);
                
                // Call the handler
                $handler = $route['handler'];
                
                // If handler is a string in format 'Controller@method'
                if (is_string($handler) && strpos($handler, '@') !== false) {
                    list($controller, $method) = explode('@', $handler);
                    $controller = 'App\\Controllers\\' . $controller;
                    
                    if (class_exists($controller)) {
                        $controllerInstance = new $controller();
                        
                        if (method_exists($controllerInstance, $method)) {
                            // Call the controller method with parameters
                            call_user_func_array([$controllerInstance, $method], $matches);
                            return;
                        }
                    }
                } 
                // If handler is a callable
                elseif (is_callable($handler)) {
                    call_user_func_array($handler, $matches);
                    return;
                }
            }
        }
        
        // No route found
        if (is_callable($this->notFoundCallback)) {
            call_user_func($this->notFoundCallback);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo '404 Not Found';
        }
    }
    
    protected function convertToRegex($route) {
        // Escape forward slashes
        $pattern = preg_replace('/\//', '\/', $route);
        
        // Convert route parameters to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^\/]+)', $pattern);
        
        // Add start and end anchors
        return '/^' . $pattern . '$/';
    }
}

// Create a new router instance
$router = new Router();

// Include route files
require_once __DIR__ . '/routes/web.php';

// Set 404 handler
$router->setNotFound(function() {
    http_response_code(404);
    if (file_exists(__DIR__ . '/404.php')) {
        require __DIR__ . '/404.php';
    } else {
        header('Content-Type: text/plain');
        echo '404 - Página no encontrada';
    }
});

// Add protected routes middleware
$protected_routes = ['mi-cuenta', 'mis-pedidos', 'favoritos', 'panel-vendedor', 'vendedor', 
                   'vendedor/panel', 'vendedor/productos', 'vendedor/nuevo-producto'];

$current_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$basePath = str_replace('/public', '', dirname($_SERVER['SCRIPT_NAME']));
$current_uri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $current_uri);
$current_uri = trim($current_uri, '/');

// Check if current route is protected
if (in_array($current_uri, $protected_routes)) {
    requireAuth();
}

// Dispatch the request
$router->dispatch();
