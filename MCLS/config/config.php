<?php
/**
 * MCLS - Maintenance Call Logging System
 * Department of Forestry, Fisheries and the Environment
 * Configuration File
 */

// Development mode for email notifications (logs instead of sending)
define('DEVELOPMENT_MODE', true);

// Check for local testing configuration override
if (file_exists(__DIR__ . '/local_config.php')) {
    require_once __DIR__ . '/local_config.php';
    if (defined('LOCAL_TESTING') && LOCAL_TESTING) {
        require_once __DIR__ . '/local_auth.php';
        return; // Skip the rest of this file
    }
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcls_db');
define('DB_USER', 'mcls_user');
define('DB_PASS', 'mcls_password');
define('DB_CHARSET', 'utf8mb4');

// Active Directory Configuration
define('AD_SERVER', 'ldap://your-domain-controller.local');
define('AD_PORT', 389);
define('AD_DOMAIN', 'YOURDOMAIN');
define('AD_BASE_DN', 'DC=yourdomain,DC=local');
define('AD_USERNAME', 'service_account@yourdomain.local');
define('AD_PASSWORD', 'service_account_password');

// Application Configuration
define('APP_NAME', 'MCLS - Maintenance Call Logging System');
define('APP_VERSION', '1.0.0');
define('APP_DEPARTMENT', 'Department of Forestry, Fisheries and the Environment');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('TIMEZONE', 'Africa/Johannesburg');

// Security Configuration
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_REGENERATE_TIME', 300); // 5 minutes

// Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Logging Configuration
define('LOG_LEVEL', 'INFO');
define('LOG_FILE', __DIR__ . '/../logs/app.log');
define('AUDIT_LOG_FILE', __DIR__ . '/../logs/audit.log');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Set Timezone
date_default_timezone_set(TIMEZONE);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>