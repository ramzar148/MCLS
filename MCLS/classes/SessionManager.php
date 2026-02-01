<?php
/**
 * Session Management Class
 * Handles user sessions, authentication state, and security
 */

if (!class_exists('SessionManager')) {
class SessionManager {
    
    public function __construct() {
        $this->initializeSession();
    }
    
    /**
     * Initialize secure session
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
            
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_TIME) {
            $this->regenerateSession();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout();
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Regenerate session ID for security
     */
    private function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Login user and create session
     * @param array $user_data
     * @return bool
     */
    public function login($user_data) {
        try {
            // Clear any existing session data
            session_unset();
            
            // Store user data in session
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['full_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            $_SESSION['first_name'] = $user_data['first_name'];
            $_SESSION['last_name'] = $user_data['last_name'];
            $_SESSION['email'] = $user_data['email'];
            $_SESSION['role'] = $user_data['role'];
            $_SESSION['department_id'] = $user_data['department_id'] ?? null;
            $_SESSION['department_name'] = $user_data['department_name'] ?? null;
            $_SESSION['employee_id'] = $user_data['employee_id'] ?? null;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regeneration'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['is_authenticated'] = true;
            
            // If user is a coordinator, fetch coordinator data
            if ($user_data['role'] === 'coordinator') {
                $_SESSION['coordinator_data'] = $user_data['coordinator_data'] ?? null;
            }
            
            // Generate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Log successful login
            $this->logSessionEvent('LOGIN_SUCCESS');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Session login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Logout user and destroy session
     */
    public function logout() {
        if ($this->isAuthenticated()) {
            $this->logSessionEvent('LOGOUT');
        }
        
        // Clear session data
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Redirect to login page
        header('Location: login.php');
        exit;
    }
    
    /**
     * Check if user is authenticated
     * @return bool
     */
    public function isAuthenticated() {
        return isset($_SESSION['is_authenticated']) && $_SESSION['is_authenticated'] === true;
    }
    
    /**
     * Get current user data
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'user',
            'department_id' => $_SESSION['department_id'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }
    
    /**
     * Check if user has required role
     * @param string $required_role
     * @return bool
     */
    /**
     * Check if user has exact role (not hierarchical)
     * @param string $required_role
     * @return bool
     */
    public function isRole($required_role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $user_role = $_SESSION['role'] ?? 'user';
        return $user_role === $required_role;
    }
    
    /**
     * Check if user has role with hierarchy (admin can access all levels)
     * @param string $required_role
     * @return bool
     */
    public function hasRole($required_role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $user_role = $_SESSION['role'] ?? 'user';
        
        // Define role hierarchy (higher roles include lower role permissions)
        $role_hierarchy = [
            'admin' => ['admin', 'manager', 'coordinator', 'technician', 'user'],
            'manager' => ['manager', 'coordinator', 'technician', 'user'],
            'coordinator' => ['coordinator', 'user'],
            'technician' => ['technician', 'user'],
            'user' => ['user']
        ];
        
        // Check if user's role has permission for the required role
        return isset($role_hierarchy[$user_role]) && 
               in_array($required_role, $role_hierarchy[$user_role]);
    }
    
    /**
     * Generate CSRF token
     * @return string
     */
    public function getCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Require authentication for page access
     * @param string $required_role Optional role requirement
     */
    public function requireAuth($required_role = null) {
        if (!$this->isAuthenticated()) {
            header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        if ($required_role && !$this->hasRole($required_role)) {
            header('Location: access_denied.php');
            exit;
        }
        
        // Check for session hijacking
        if (!$this->validateSession()) {
            $this->logout();
        }
    }
    
    /**
     * Validate session integrity
     * @return bool
     */
    private function validateSession() {
        // Check IP address consistency (optional, can cause issues with load balancers)
        $ip_check = true;
        if (isset($_SESSION['ip_address']) && !empty($_SESSION['ip_address'])) {
            $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($_SESSION['ip_address'] !== $current_ip) {
                // Log potential session hijacking
                error_log("Session IP mismatch: {$_SESSION['ip_address']} vs {$current_ip}");
                $ip_check = false;
            }
        }
        
        // Check user agent consistency
        $ua_check = true;
        if (isset($_SESSION['user_agent']) && !empty($_SESSION['user_agent'])) {
            $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $current_ua) {
                error_log("Session User-Agent mismatch");
                $ua_check = false;
            }
        }
        
        return $ip_check && $ua_check;
    }
    
    /**
     * Log session events for auditing
     * @param string $event
     * @param array $additional_data
     */
    private function logSessionEvent($event, $additional_data = []) {
        try {
            // Check if Database class is available
            if (!class_exists('Database')) {
                return; // Skip logging if Database class not loaded
            }
            
            $db = new Database();
            
            $event_data = array_merge([
                'event' => $event,
                'session_id' => session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ], $additional_data);
            
            $db->execute(
                "INSERT INTO audit_log (
                    user_id, table_name, action, new_values, 
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $_SESSION['user_id'] ?? null,
                    'session',
                    $event,
                    json_encode($event_data),
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
            
        } catch (Exception $e) {
            error_log("Session event logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get session timeout warning time (in seconds before timeout)
     * @return int
     */
    public function getTimeoutWarning() {
        if (!$this->isAuthenticated()) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = SESSION_TIMEOUT - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Extend session (for AJAX calls)
     */
    public function extendSession() {
        if ($this->isAuthenticated()) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
    
    /**
     * Get all active sessions for a user (admin function)
     * @param int $user_id
     * @return array
     */
    public function getActiveUserSessions($user_id) {
        // This would require a sessions table to track active sessions
        // For now, return empty array
        return [];
    }
    
    /**
     * Force logout of specific session (admin function)
     * @param string $session_id
     * @return bool
     */
    public function forceLogout($session_id) {
        // This would require a sessions table
        // For now, return false
        return false;
    }
}
} // End class_exists check
?>