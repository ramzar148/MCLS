<?php
/**
 * Local Testing Installation Script
 * Automatically sets up MCLS for local XAMPP testing
 */

// Check if running on localhost
if (!in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])) {
    die('This installer is only for local development environments.');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>MCLS Local Testing Installer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .step { background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #1e6b3e; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #155230; }
    </style>
</head>
<body>";

echo "<h1>🌿 MCLS Local Testing Installer</h1>";
echo "<p>This script will set up the Maintenance Call Logging System for local testing on XAMPP.</p>";

// Configuration for local testing
$config = [
    'db_host' => 'localhost',
    'db_name' => 'mcls_db',
    'db_user' => 'root',
    'db_pass' => '', // Default XAMPP MySQL password is empty
    'base_url' => 'http://localhost/MCLS/',
    'admin_user' => 'admin',
    'admin_pass' => 'test123'
];

$steps_completed = 0;
$total_steps = 6;

// Step 1: Check Requirements
echo "<div class='step'>";
echo "<h2>Step 1: Checking Requirements</h2>";

$requirements_met = true;
$checks = [
    'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
    'JSON Extension' => extension_loaded('json'),
    'OpenSSL Extension' => extension_loaded('openssl'),
    'XAMPP Environment' => (strpos($_SERVER['DOCUMENT_ROOT'], 'xampp') !== false || strpos($_SERVER['DOCUMENT_ROOT'], 'htdocs') !== false)
];

foreach ($checks as $check => $status) {
    $class = $status ? 'success' : 'error';
    echo "<div class='$class'>$check: " . ($status ? '✓ OK' : '✗ FAILED') . "</div>";
    if (!$status) $requirements_met = false;
}

if ($requirements_met) {
    echo "<div class='success'>✓ All requirements met!</div>";
    $steps_completed++;
} else {
    echo "<div class='error'>✗ Please fix the requirements above before continuing.</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 2: Database Connection Test
echo "<div class='step'>";
echo "<h2>Step 2: Testing Database Connection</h2>";

try {
    $dsn = "mysql:host={$config['db_host']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✓ MySQL connection successful!</div>";
    $steps_completed++;
} catch (PDOException $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>Make sure XAMPP MySQL is running!</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 3: Create Database
echo "<div class='step'>";
echo "<h2>Step 3: Creating Database</h2>";

try {
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['db_name']}'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='warning'>Database '{$config['db_name']}' already exists. Continuing with existing database.</div>";
    } else {
        $pdo->exec("CREATE DATABASE {$config['db_name']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div class='success'>✓ Database '{$config['db_name']}' created successfully!</div>";
    }
    
    // Switch to the database
    $pdo->exec("USE {$config['db_name']}");
    $steps_completed++;
} catch (PDOException $e) {
    echo "<div class='error'>✗ Database creation failed: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 4: Create Tables
echo "<div class='step'>";
echo "<h2>Step 4: Creating Database Tables</h2>";

try {
    // Read and execute schema
    $schema_file = __DIR__ . '/database/schema_simple.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }
    
    $schema = file_get_contents($schema_file);
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Clean up the schema and split into statements
    $schema = preg_replace('/--.*$/m', '', $schema); // Remove comments
    $schema = preg_replace('/\/\*.*?\*\//s', '', $schema); // Remove block comments
    
    // Split by semicolon and filter
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $schema)));
    
    $executed = 0;
    $failed = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && strlen($statement) > 10) { // Skip very short statements
            try {
                $pdo->exec($statement . ';');
                $executed++;
            } catch (PDOException $e) {
                // Ignore "table already exists" and "duplicate entry" errors for IGNORE statements
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<div class='warning'>Warning: " . substr($e->getMessage(), 0, 100) . "...</div>";
                    $failed++;
                }
            }
        }
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    if ($executed > 0) {
        echo "<div class='success'>✓ Database schema created successfully! ($executed statements executed)</div>";
        $steps_completed++;
    } else {
        throw new Exception("No SQL statements were executed. Check schema file format.");
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Schema creation failed: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 5: Create Test Data
echo "<div class='step'>";
echo "<h2>Step 5: Creating Test Data</h2>";

try {
    // Insert test users
    $test_users = [
        ['admin', 'System', 'Administrator', 'admin@test.local', 'admin'],
        ['manager', 'Test', 'Manager', 'manager@test.local', 'manager'],
        ['technician', 'Test', 'Technician', 'technician@test.local', 'technician'],
        ['user', 'Test', 'User', 'user@test.local', 'user']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (ad_username, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
    foreach ($test_users as $user) {
        $stmt->execute($user);
    }
    
    // Insert test departments
    $test_departments = [
        ['Information Technology', 'IT', 'IT Department'],
        ['Facilities Management', 'FAC', 'Facilities and Maintenance'],
        ['Administration', 'ADM', 'Administration Department'],
        ['Environmental Services', 'ENV', 'Environmental and Forestry Services']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name, code, description, status) VALUES (?, ?, ?, 'active')");
    foreach ($test_departments as $dept) {
        $stmt->execute($dept);
    }
    
    // Insert test equipment
    $test_equipment = [
        ['IT001', 'Dell Desktop Computer', 'Main office desktop computer', 1, 'Office 101', 1],
        ['IT002', 'HP LaserJet Printer', 'Network laser printer', 2, 'Office 102', 1],
        ['FAC001', 'HVAC Unit A', 'Main building air conditioning', 5, 'Roof Level 1', 2],
        ['FAC002', 'Emergency Generator', 'Backup power generator', 6, 'Basement', 2],
        ['ENV001', 'Water Quality Tester', 'Portable water testing equipment', 1, 'Field Office', 4]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO equipment (asset_tag, name, description, category_id, location, department_id, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    foreach ($test_equipment as $equipment) {
        $stmt->execute($equipment);
    }
    
    // Generate sample maintenance calls
    $sample_calls = [
        ['Computer not starting', 'Desktop computer in Office 101 fails to boot up', 1, 1, 2, 'open'],
        ['Printer jam error', 'HP printer showing constant paper jam error', 2, 2, 3, 'assigned'],
        ['HVAC not cooling', 'Air conditioning unit not providing adequate cooling', 3, 3, 1, 'in_progress'],
        ['Generator maintenance', 'Scheduled monthly maintenance for backup generator', 4, 4, 4, 'open'],
        ['Water test equipment calibration', 'Annual calibration required for water testing device', 5, 5, 3, 'open']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO maintenance_calls (call_number, title, description, equipment_id, reported_by, priority_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    for ($i = 0; $i < count($sample_calls); $i++) {
        $call = $sample_calls[$i];
        $call_number = 'MC' . date('Ym') . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        $stmt->execute([$call_number, $call[0], $call[1], $call[2], $call[3], $call[4], $call[5]]);
    }
    
    echo "<div class='success'>✓ Test data created successfully!</div>";
    echo "<div class='code'>
    Test Users Created:<br>
    • admin (Administrator) - Password: admin123<br>
    • manager (Manager) - Password: admin123<br>
    • technician (Technician) - Password: admin123<br>
    • user (User) - Password: admin123<br>
    <br>Note: In local testing mode, use any password for login.
    </div>";
    $steps_completed++;
} catch (Exception $e) {
    echo "<div class='error'>✗ Test data creation failed: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 6: Create Local Configuration
echo "<div class='step'>";
echo "<h2>Step 6: Creating Local Configuration</h2>";

try {
    $local_config = "<?php
/**
 * Local Testing Configuration
 * This file is automatically generated for local XAMPP testing
 */

// Override main configuration for local testing
define('LOCAL_TESTING', true);
define('SKIP_AD_AUTH', true);

// Database Configuration (Local)
define('DB_HOST', '{$config['db_host']}');
define('DB_NAME', '{$config['db_name']}');
define('DB_USER', '{$config['db_user']}');
define('DB_PASS', '{$config['db_pass']}');
define('DB_CHARSET', 'utf8mb4');

// Disable Active Directory for local testing
define('AD_SERVER', '');
define('AD_PORT', 389);
define('AD_DOMAIN', 'LOCAL');
define('AD_BASE_DN', '');
define('AD_USERNAME', '');
define('AD_PASSWORD', '');

// Application Configuration
define('APP_NAME', 'MCLS - Maintenance Call Logging System');
define('APP_VERSION', '1.0.0');
define('APP_DEPARTMENT', 'Department of Forestry, Fisheries and the Environment');
define('SESSION_TIMEOUT', 3600);
define('TIMEZONE', 'Africa/Johannesburg');

// Security Configuration (Local Testing)
define('ENCRYPTION_KEY', 'local-testing-key-not-secure-123456');
define('PASSWORD_MIN_LENGTH', 3); // Relaxed for testing
define('SESSION_REGENERATE_TIME', 300);

// Local test password
define('LOCAL_TEST_PASSWORD', '{$config['admin_pass']}');

// Upload Configuration
define('UPLOAD_MAX_SIZE', 5242880);
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Logging Configuration
define('LOG_LEVEL', 'DEBUG');
define('LOG_FILE', __DIR__ . '/../logs/app.log');
define('AUDIT_LOG_FILE', __DIR__ . '/../logs/audit.log');

// Error Reporting (Enable for testing)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Set Timezone
date_default_timezone_set(TIMEZONE);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Disabled for HTTP local testing
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>";
    
    file_put_contents(__DIR__ . '/config/local_config.php', $local_config);
    
    // Create logs directory
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    echo "<div class='success'>✓ Local configuration created successfully!</div>";
    $steps_completed++;
} catch (Exception $e) {
    echo "<div class='error'>✗ Configuration creation failed: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Final Summary
echo "<div class='step success'>";
echo "<h2>🎉 Installation Complete!</h2>";
echo "<p>MCLS has been successfully installed for local testing. ($steps_completed/$total_steps steps completed)</p>";

echo "<h3>🔗 Quick Access Links:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='{$config['base_url']}' class='btn'>🏠 Go to MCLS Dashboard</a>";
echo "<a href='{$config['base_url']}login.php' class='btn'>🔐 Login Page</a>";
echo "<a href='{$config['base_url']}maintenance_calls/index.php' class='btn'>📋 Maintenance Calls</a>";
echo "</div>";

echo "<h3>📝 Test Credentials:</h3>";
echo "<div class='code'>";
echo "Username: admin<br>";
echo "Password: test123<br><br>";
echo "Other test users: manager, technician, user (all with password: test123)";
echo "</div>";

echo "<h3>📚 Next Steps:</h3>";
echo "<ol>";
echo "<li>Click the 'Go to MCLS Dashboard' button above</li>";
echo "<li>Login with the admin credentials</li>";
echo "<li>Explore the system features</li>";
echo "<li>Review the LOCAL_TESTING.md file for detailed testing instructions</li>";
echo "<li>Check the sample data that was created</li>";
echo "</ol>";

echo "<h3>🗂️ Important Files:</h3>";
echo "<ul>";
echo "<li><strong>Configuration:</strong> /config/local_config.php</li>";
echo "<li><strong>Database:</strong> mcls_db (in phpMyAdmin)</li>";
echo "<li><strong>Testing Guide:</strong> LOCAL_TESTING.md</li>";
echo "<li><strong>Installation Guide:</strong> INSTALLATION.md</li>";
echo "</ul>";

echo "</div>";

echo "</body></html>";
?>