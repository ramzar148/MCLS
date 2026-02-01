<?php
require_once 'bootstrap.php';

echo "<h1>🌿 MCLS Login Test</h1>";

// Check configuration
echo "<h2>Configuration Status:</h2>";
echo "LOCAL_TESTING: " . (defined('LOCAL_TESTING') ? (LOCAL_TESTING ? 'YES' : 'NO') : 'NOT_DEFINED') . "<br>";
echo "SKIP_AD_AUTH: " . (defined('SKIP_AD_AUTH') ? (SKIP_AD_AUTH ? 'YES' : 'NO') : 'NOT_DEFINED') . "<br>";

// Test authentication
if (isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Testing Login:</h2>";
    echo "Username: " . htmlspecialchars($username) . "<br>";
    
    try {
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo "Creating ActiveDirectoryAuth object...<br>";
        $auth = new ActiveDirectoryAuth();
        
        echo "Attempting authentication...<br>";
        $result = $auth->authenticate($username, $password);
        
        if ($result) {
            echo "<div style='color: green; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "✓ Authentication successful!<br>";
            echo "User data: <pre>" . print_r($result, true) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "✗ Authentication failed!<br>";
            echo "Check the error log for more details.<br>";
            
            // Try to show recent error log entries
            $log_file = ini_get('error_log');
            if (!$log_file) {
                $log_file = __DIR__ . '/../../logs/php_error.log'; // XAMPP default
            }
            if (!file_exists($log_file)) {
                $log_file = 'C:/xampp/php/logs/php_error_log'; // Another common location
            }
            
            echo "Error log location: " . $log_file . "<br>";
            
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                $lines = explode("\n", $log_content);
                $recent_lines = array_slice($lines, -20); // Last 20 lines
                $local_auth_lines = array_filter($recent_lines, function($line) {
                    return strpos($line, 'LOCAL AUTH:') !== false;
                });
                
                if ($local_auth_lines) {
                    echo "<strong>Recent LOCAL AUTH log entries:</strong><br>";
                    echo "<pre style='max-height: 200px; overflow-y: scroll; background: #f8f9fa; padding: 10px;'>";
                    echo implode("\n", $local_auth_lines);
                    echo "</pre>";
                }
            } else {
                echo "Error log file not found at expected locations.<br>";
            }
            
            echo "</div>";
        }
        
        // Test database connection directly
        echo "<h3>Testing Database Connection:</h3>";
        require_once 'config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Users in database: " . $count['count'] . "<br>";
        
        $stmt = $pdo->prepare("SELECT ad_username, role FROM users WHERE ad_username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo "Found user: " . $user['ad_username'] . " with role: " . $user['role'] . "<br>";
        } else {
            echo "User '$username' not found in database<br>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
}
?>

<form method="POST" style="margin: 20px 0;">
    <h2>Test Login:</h2>
    <p>Try these usernames: <strong>admin</strong>, <strong>manager</strong>, <strong>technician</strong>, or <strong>user</strong></p>
    <p>Password: Any password will work (try <strong>admin123</strong>)</p>
    
    <input type="text" name="username" placeholder="Username" required style="padding: 8px; margin: 5px;"><br>
    <input type="password" name="password" placeholder="Password" required style="padding: 8px; margin: 5px;"><br>
    <button type="submit" name="test_login" style="padding: 10px 20px; background: #28a745; color: white; border: none; margin: 5px;">Test Login</button>
</form>

<hr>
<p><a href="login.php">Go to actual login page</a></p>
<p><a href="index.php">Go to main page</a></p>