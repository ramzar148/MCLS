<?php
/**
 * MCLS Diagnostic Script
 * Run this first to check for issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>MCLS Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 MCLS System Diagnostic</h1>";

// Check 1: PHP Version
echo "<h2>1. PHP Environment</h2>";
echo "<div class='info'>PHP Version: " . PHP_VERSION . "</div>";

// Check 2: Required Extensions
echo "<h2>2. Required Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'mbstring'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $class = $loaded ? 'success' : 'error';
    echo "<div class='$class'>$ext: " . ($loaded ? '✓ Loaded' : '✗ Missing') . "</div>";
}

// Check 3: File Structure
echo "<h2>3. File Structure</h2>";
$required_files = [
    'config/config.php',
    'config/database.php',
    'classes/SessionManager.php',
    'assets/css/styles.css',
    'assets/js/app.js',
    'includes/header.php',
    'includes/footer.php'
];

foreach ($required_files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $class = $exists ? 'success' : 'error';
    echo "<div class='$class'>$file: " . ($exists ? '✓ Exists' : '✗ Missing') . "</div>";
}

// Check 4: Directory Permissions
echo "<h2>4. Directory Permissions</h2>";
$directories = [
    '.',
    'config',
    'classes',
    'assets',
    'includes'
];

foreach ($directories as $dir) {
    $readable = is_readable($dir);
    $writable = is_writable($dir);
    $class = ($readable && $writable) ? 'success' : 'warning';
    echo "<div class='$class'>$dir: " . 
         ($readable ? '✓ Readable ' : '✗ Not Readable ') . 
         ($writable ? '✓ Writable' : '✗ Not Writable') . "</div>";
}

// Check 5: Test Database Connection
echo "<h2>5. Database Connection Test</h2>";
try {
    $dsn = "mysql:host=localhost;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    echo "<div class='success'>✓ MySQL connection successful</div>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'mcls_db'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✓ Database 'mcls_db' exists</div>";
    } else {
        echo "<div class='warning'>⚠ Database 'mcls_db' does not exist</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
}

// Check 6: Configuration Files
echo "<h2>6. Configuration Check</h2>";
try {
    if (file_exists('config/config.php')) {
        $config_content = file_get_contents('config/config.php');
        if (strpos($config_content, '<?php') === 0) {
            echo "<div class='success'>✓ config.php syntax looks good</div>";
        } else {
            echo "<div class='error'>✗ config.php may have syntax issues</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Config check failed: " . $e->getMessage() . "</div>";
}

// Check 7: Apache Modules
echo "<h2>7. Apache Information</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    $required_modules = ['mod_rewrite', 'mod_ssl'];
    foreach ($required_modules as $module) {
        $loaded = in_array($module, $modules);
        $class = $loaded ? 'success' : 'warning';
        echo "<div class='$class'>$module: " . ($loaded ? '✓ Loaded' : '⚠ Not loaded') . "</div>";
    }
} else {
    echo "<div class='info'>Apache module information not available</div>";
}

// Recommendations
echo "<h2>🔧 Quick Fixes</h2>";
echo "<div class='info'>";
echo "<h3>If you're seeing Internal Server Error:</h3>";
echo "<ol>";
echo "<li><strong>Check Apache Error Log:</strong><br>";
echo "<div class='code'>C:\\xampp\\apache\\logs\\error.log</div></li>";
echo "<li><strong>Ensure XAMPP is fully started:</strong><br>";
echo "- Apache service running<br>- MySQL service running</li>";
echo "<li><strong>Check file permissions:</strong><br>";
echo "- Make sure all files are readable<br>- Check .htaccess file syntax</li>";
echo "<li><strong>Run the automatic installer:</strong><br>";
echo "<a href='install_local.php' style='color: #0066cc;'>http://localhost/MCLS/install_local.php</a></li>";
echo "</ol>";
echo "</div>";

echo "<h2>📋 Next Steps</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li>If all checks above are green, try accessing: <a href='login.php'>login.php</a></li>";
echo "<li>If you see errors above, run the installer: <a href='install_local.php'>install_local.php</a></li>";
echo "<li>If installer fails, check XAMPP services are running</li>";
echo "<li>Check Apache error log for specific error messages</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<strong>Debug Information:</strong><br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";
echo "</div>";

echo "</body></html>";
?>