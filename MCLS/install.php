<?php
/**
 * MCLS Installation Script
 * Automated setup for Department of Forestry, Fisheries and the Environment
 */

// Prevent direct access in production
if (!defined('MCLS_INSTALL_MODE')) {
    die('Access denied. This script can only be run during installation.');
}

class MCLSInstaller {
    private $config = [];
    private $errors = [];
    private $steps = [];
    
    public function __construct() {
        $this->initializeSteps();
    }
    
    private function initializeSteps() {
        $this->steps = [
            'requirements' => 'Check System Requirements',
            'config' => 'Create Configuration Files',
            'database' => 'Setup Database',
            'admin' => 'Create Admin User',
            'data' => 'Install Default Data',
            'security' => 'Configure Security',
            'complete' => 'Complete Installation'
        ];
    }
    
    public function run($step = null) {
        if (!$step) {
            $step = $_GET['step'] ?? 'requirements';
        }
        
        if (!array_key_exists($step, $this->steps)) {
            $step = 'requirements';
        }
        
        $method = 'step' . ucfirst($step);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        
        return $this->stepRequirements();
    }
    
    public function stepRequirements() {
        $requirements = $this->checkRequirements();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($requirements['all_passed']) {
                header('Location: install.php?step=config');
                exit;
            }
        }
        
        return $this->renderTemplate('requirements', [
            'requirements' => $requirements,
            'next_step' => 'config'
        ]);
    }
    
    public function stepConfig() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = $this->validateConfig($_POST);
            
            if (empty($this->errors)) {
                $this->config = $config;
                $_SESSION['install_config'] = $config;
                
                if ($this->createConfigFile($config)) {
                    header('Location: install.php?step=database');
                    exit;
                } else {
                    $this->errors[] = 'Failed to create configuration file';
                }
            }
        }
        
        return $this->renderTemplate('config', [
            'config' => $this->config,
            'errors' => $this->errors,
            'next_step' => 'database'
        ]);
    }
    
    public function stepDatabase() {
        $config = $_SESSION['install_config'] ?? [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->setupDatabase($config)) {
                header('Location: install.php?step=admin');
                exit;
            }
        }
        
        $db_status = $this->testDatabaseConnection($config);
        
        return $this->renderTemplate('database', [
            'config' => $config,
            'db_status' => $db_status,
            'errors' => $this->errors,
            'next_step' => 'admin'
        ]);
    }
    
    public function stepAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $admin_data = $_POST;
            
            if ($this->createAdminUser($admin_data)) {
                header('Location: install.php?step=data');
                exit;
            }
        }
        
        return $this->renderTemplate('admin', [
            'errors' => $this->errors,
            'next_step' => 'data'
        ]);
    }
    
    public function stepData() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->installDefaultData()) {
                header('Location: install.php?step=security');
                exit;
            }
        }
        
        return $this->renderTemplate('data', [
            'errors' => $this->errors,
            'next_step' => 'security'
        ]);
    }
    
    public function stepSecurity() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->configureSecurity()) {
                header('Location: install.php?step=complete');
                exit;
            }
        }
        
        $security_checks = $this->checkSecurity();
        
        return $this->renderTemplate('security', [
            'security_checks' => $security_checks,
            'errors' => $this->errors,
            'next_step' => 'complete'
        ]);
    }
    
    public function stepComplete() {
        // Create installation lock file
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        
        // Clear installation session data
        unset($_SESSION['install_config']);
        
        return $this->renderTemplate('complete', [
            'app_url' => $this->getAppUrl()
        ]);
    }
    
    private function checkRequirements() {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version (7.4+)',
                'required' => '7.4.0',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '7.4.0', '>=')
            ],
            'php_extensions' => [
                'name' => 'PHP Extensions',
                'required' => ['pdo', 'pdo_mysql', 'ldap', 'json', 'openssl', 'mbstring'],
                'current' => [],
                'passed' => true
            ],
            'file_permissions' => [
                'name' => 'File Permissions',
                'required' => 'Read/Write access to application directory',
                'current' => '',
                'passed' => is_writable(__DIR__)
            ],
            'web_server' => [
                'name' => 'Web Server',
                'required' => 'Apache with mod_rewrite',
                'current' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'passed' => isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false
            ]
        ];
        
        // Check PHP extensions
        foreach ($requirements['php_extensions']['required'] as $ext) {
            $loaded = extension_loaded($ext);
            $requirements['php_extensions']['current'][$ext] = $loaded;
            if (!$loaded) {
                $requirements['php_extensions']['passed'] = false;
            }
        }
        
        // Check overall status
        $requirements['all_passed'] = array_reduce($requirements, function($carry, $req) {
            return $carry && $req['passed'];
        }, true);
        
        return $requirements;
    }
    
    private function validateConfig($data) {
        $config = [];
        $required_fields = [
            'db_host', 'db_name', 'db_user', 'db_pass',
            'ad_server', 'ad_domain', 'ad_base_dn', 'ad_username', 'ad_password'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $this->errors[] = "Field '{$field}' is required";
            } else {
                $config[$field] = trim($data[$field]);
            }
        }
        
        // Generate encryption key if not provided
        if (empty($data['encryption_key'])) {
            $config['encryption_key'] = bin2hex(random_bytes(16));
        } else {
            $config['encryption_key'] = $data['encryption_key'];
        }
        
        // Validate database connection
        if (empty($this->errors)) {
            if (!$this->testDatabaseConnection($config)) {
                $this->errors[] = 'Database connection failed';
            }
        }
        
        return $config;
    }
    
    private function testDatabaseConnection($config) {
        try {
            $dsn = "mysql:host={$config['db_host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Database connection error: " . $e->getMessage();
            return false;
        }
    }
    
    private function createConfigFile($config) {
        $config_content = "<?php\n";
        $config_content .= "/**\n";
        $config_content .= " * MCLS Configuration File\n";
        $config_content .= " * Generated on " . date('Y-m-d H:i:s') . "\n";
        $config_content .= " */\n\n";
        
        // Database Configuration
        $config_content .= "// Database Configuration\n";
        $config_content .= "define('DB_HOST', '" . addslashes($config['db_host']) . "');\n";
        $config_content .= "define('DB_NAME', '" . addslashes($config['db_name']) . "');\n";
        $config_content .= "define('DB_USER', '" . addslashes($config['db_user']) . "');\n";
        $config_content .= "define('DB_PASS', '" . addslashes($config['db_pass']) . "');\n";
        $config_content .= "define('DB_CHARSET', 'utf8mb4');\n\n";
        
        // Active Directory Configuration
        $config_content .= "// Active Directory Configuration\n";
        $config_content .= "define('AD_SERVER', '" . addslashes($config['ad_server']) . "');\n";
        $config_content .= "define('AD_PORT', 389);\n";
        $config_content .= "define('AD_DOMAIN', '" . addslashes($config['ad_domain']) . "');\n";
        $config_content .= "define('AD_BASE_DN', '" . addslashes($config['ad_base_dn']) . "');\n";
        $config_content .= "define('AD_USERNAME', '" . addslashes($config['ad_username']) . "');\n";
        $config_content .= "define('AD_PASSWORD', '" . addslashes($config['ad_password']) . "');\n\n";
        
        // Application Configuration
        $config_content .= "// Application Configuration\n";
        $config_content .= "define('APP_NAME', 'MCLS - Maintenance Call Logging System');\n";
        $config_content .= "define('APP_VERSION', '1.0.0');\n";
        $config_content .= "define('APP_DEPARTMENT', 'Department of Forestry, Fisheries and the Environment');\n";
        $config_content .= "define('SESSION_TIMEOUT', 3600);\n";
        $config_content .= "define('TIMEZONE', 'Africa/Johannesburg');\n\n";
        
        // Security Configuration
        $config_content .= "// Security Configuration\n";
        $config_content .= "define('ENCRYPTION_KEY', '" . $config['encryption_key'] . "');\n";
        $config_content .= "define('PASSWORD_MIN_LENGTH', 8);\n";
        $config_content .= "define('SESSION_REGENERATE_TIME', 300);\n\n";
        
        // Additional configuration...
        $config_content .= file_get_contents(__DIR__ . '/config/config_template.php');
        
        return file_put_contents(__DIR__ . '/config/config.php', $config_content) !== false;
    }
    
    private function setupDatabase($config) {
        try {
            // Connect to MySQL server
            $dsn = "mysql:host={$config['db_host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Switch to the new database
            $pdo->exec("USE `{$config['db_name']}`");
            
            // Execute schema file
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            $pdo->exec($schema);
            
            return true;
            
        } catch (PDOException $e) {
            $this->errors[] = "Database setup error: " . $e->getMessage();
            return false;
        }
    }
    
    private function createAdminUser($data) {
        $required_fields = ['username', 'first_name', 'last_name', 'email'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $this->errors[] = "Field '{$field}' is required";
                return false;
            }
        }
        
        try {
            require_once __DIR__ . '/config/config.php';
            require_once __DIR__ . '/config/database.php';
            
            $db = new Database();
            $stmt = $db->execute(
                "INSERT INTO users (ad_username, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')",
                [$data['username'], $data['first_name'], $data['last_name'], $data['email']]
            );
            
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = "Admin user creation error: " . $e->getMessage();
            return false;
        }
    }
    
    private function installDefaultData() {
        try {
            require_once __DIR__ . '/config/config.php';
            require_once __DIR__ . '/config/database.php';
            
            $db = new Database();
            
            // Insert default departments
            $departments = [
                ['Information Technology', 'IT', 'IT Department'],
                ['Facilities Management', 'FM', 'Facilities and Maintenance'],
                ['Human Resources', 'HR', 'Human Resources'],
                ['Finance', 'FIN', 'Finance Department'],
                ['Environmental Services', 'ENV', 'Environmental Services'],
                ['Forestry Operations', 'FOR', 'Forestry Operations'],
                ['Fisheries Management', 'FISH', 'Fisheries Management']
            ];
            
            foreach ($departments as $dept) {
                $db->execute(
                    "INSERT IGNORE INTO departments (name, code, description, status) VALUES (?, ?, ?, 'active')",
                    $dept
                );
            }
            
            // Insert system settings
            $settings = [
                ['company_name', 'Department of Forestry, Fisheries and the Environment', 'Organization name'],
                ['timezone', 'Africa/Johannesburg', 'System timezone'],
                ['date_format', 'Y-m-d', 'Default date format'],
                ['currency_symbol', 'R', 'Currency symbol'],
                ['maintenance_call_prefix', 'MC', 'Prefix for maintenance call numbers'],
                ['work_order_prefix', 'WO', 'Prefix for work order numbers'],
                ['auto_assignment', 'false', 'Enable automatic assignment of calls'],
                ['notification_email', 'true', 'Send email notifications']
            ];
            
            foreach ($settings as $setting) {
                $db->execute(
                    "INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)",
                    $setting
                );
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = "Default data installation error: " . $e->getMessage();
            return false;
        }
    }
    
    private function configureSecurity() {
        // Create logs directory
        $logs_dir = __DIR__ . '/logs';
        if (!is_dir($logs_dir)) {
            mkdir($logs_dir, 0750, true);
        }
        
        // Create .htaccess for logs directory
        $htaccess_content = "Order deny,allow\nDeny from all\n";
        file_put_contents($logs_dir . '/.htaccess', $htaccess_content);
        
        // Create uploads directory
        $uploads_dir = __DIR__ . '/uploads';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
        }
        
        // Set security headers in main .htaccess if not present
        $htaccess_file = __DIR__ . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            copy(__DIR__ . '/.htaccess.example', $htaccess_file);
        }
        
        return true;
    }
    
    private function checkSecurity() {
        return [
            'https' => [
                'name' => 'HTTPS Enabled',
                'status' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'recommendation' => 'Enable HTTPS for secure communication'
            ],
            'file_permissions' => [
                'name' => 'File Permissions',
                'status' => !is_writable(__DIR__ . '/config/config.php'),
                'recommendation' => 'Make config files read-only after installation'
            ],
            'error_reporting' => [
                'name' => 'Error Reporting',
                'status' => !ini_get('display_errors'),
                'recommendation' => 'Disable error display in production'
            ],
            'session_security' => [
                'name' => 'Session Security',
                'status' => ini_get('session.cookie_httponly'),
                'recommendation' => 'Enable secure session cookies'
            ]
        ];
    }
    
    private function getAppUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['REQUEST_URI']);
        return $protocol . '://' . $host . $path;
    }
    
    private function renderTemplate($template, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/install/templates/{$template}.php";
        return ob_get_clean();
    }
}

// Start session for installation process
session_start();

// Check if already installed
if (file_exists(__DIR__ . '/.installed') && !isset($_GET['force'])) {
    die('MCLS is already installed. Delete .installed file to reinstall.');
}

// Define installation mode
define('MCLS_INSTALL_MODE', true);

// Create installer instance and run
$installer = new MCLSInstaller();
echo $installer->run();
?>