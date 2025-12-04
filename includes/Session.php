<?php
class Session {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            $this->startSession();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function startSession() {
        // Don't start a session if headers already sent
        if (headers_sent()) {
            error_log('Headers already sent when trying to start session');
            return false;
        }

        // Set session name
        $sessionName = 'silco_session';
        
        // Set secure session parameters
        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        $httpOnly = true;
        $lifetime = 60 * 60 * 24 * 30; // 30 days
        
        // Set session cookie parameters
        $cookieParams = session_get_cookie_params();
        
        // Always set cookie parameters to ensure consistency
        $cookieParams['lifetime'] = $lifetime;
        $cookieParams['path'] = '/';
        $cookieParams['domain'] = $_SERVER['HTTP_HOST'];
        $cookieParams['secure'] = $secure;
        $cookieParams['httponly'] = $httpOnly;
        $cookieParams['samesite'] = 'Lax';
        
        session_set_cookie_params($cookieParams);
        
        // Set session configuration
        ini_set('session.gc_maxlifetime', $lifetime);
        ini_set('session.cookie_lifetime', $lifetime);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', $secure ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        
        // Set session name
        session_name($sessionName);
        
        // Start the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize session data if it doesn't exist
        if (!isset($_SESSION['initialized'])) {
            session_regenerate_id(true);
            $_SESSION['initialized'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['created'] = time();
            error_log('New session initialized');
        }
        
        // Regenerate session ID periodically to prevent session fixation
        $timeout = 1800; // 30 minutes
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_regenerate_id(true);
            $_SESSION['last_activity'] = time();
            error_log('Session regenerated due to inactivity timeout');
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Debug log
        error_log('Session active - ID: ' . session_id());
        error_log('Session data: ' . print_r($_SESSION, true));
        
        return true;
        
        // Regenerate session ID to prevent session fixation
        if (empty($_SESSION['last_activity'])) {
            session_regenerate_id(true);
            $this->set('last_activity', time());
            $this->regenerateCSRFToken();
        }
    }
    
    public function regenerateCSRFToken() {
        $this->set('csrf_token', bin2hex(random_bytes(32)));
        return $this->get('csrf_token');
    }
    
    public function validateCSRFToken($token) {
        $storedToken = $this->get('csrf_token');
        return $token && $storedToken && hash_equals($storedToken, $token);
    }
    
    public function getCSRFToken() {
        if (!$this->has('csrf_token')) {
            return $this->regenerateCSRFToken();
        }
        return $this->get('csrf_token');
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }
    
    public function destroy() {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    public function isLoggedIn() {
        return $this->has('user_id') && $this->has('user_role');
    }
    
    public function loginUser($user) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Set user data in session
        $this->set('user_id', $user['id']);
        $this->set('user_email', $user['email']);
        $this->set('user_name', $user['nombre']);
        $this->set('user_nombre', $user['nombre']); // Asegurarse de que user_nombre también esté establecido
        $this->set('user_role', $user['es_vendedor'] ? 'vendedor' : 'cliente');
        $this->set('es_vendedor', $user['es_vendedor']);
        $this->set('last_activity', time());
        $this->set('last_regeneration', time());
        
        // Update session cookie with new expiration
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => time() + (60 * 60 * 24 * 30), // 30 days
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    public function requireLogin($redirect = 'login.php') {
        if (!$this->isLoggedIn()) {
            $this->set('redirect_after_login', $_SERVER['REQUEST_URI']);
            header('Location: ' . $redirect);
            exit();
        }
    }
    
    public function requireRole($role, $redirect = 'index.php') {
        $this->requireLogin();
        
        $userRole = $this->get('user_role');
        $hasAccess = false;
        
        if (is_array($role)) {
            $hasAccess = in_array($userRole, $role);
        } else {
            $hasAccess = ($userRole === $role);
        }
        
        if (!$hasAccess) {
            header('Location: ' . $redirect);
            exit();
        }
    }
}
