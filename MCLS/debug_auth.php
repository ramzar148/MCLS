<?php
require_once 'bootstrap.php';

// Enable direct error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🌿 MCLS Authentication Debug</h1>";

// Check configuration
echo "<h2>Configuration Status:</h2>";
echo "LOCAL_TESTING: " . (defined('LOCAL_TESTING') ? (LOCAL_TESTING ? 'YES' : 'NO') : 'NOT_DEFINED') . "<br>";
echo "SKIP_AD_AUTH: " . (defined('SKIP_AD_AUTH') ? (SKIP_AD_AUTH ? 'YES' : 'NO') : 'NOT_DEFINED') . "<br>";

// Debug authentication step by step
if (isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Step-by-Step Authentication Debug:</h2>";
    echo "Username: " . htmlspecialchars($username) . "<br>";
    echo "Password length: " . strlen($password) . "<br><br>";
    
    try {
        echo "1. Creating ActiveDirectoryAuth object...<br>";
        
        // Check if the class file has syntax errors
        $class_file = __DIR__ . '/classes/ActiveDirectoryAuth.php';
        echo "Checking syntax of: $class_file<br>";
        
        $syntax_check = `php -l "$class_file" 2>&1`;
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "✓ PHP syntax check passed<br>";
        } else {
            echo "✗ PHP syntax error found:<br>";
            echo "<pre style='background: #f8d7da; padding: 10px;'>$syntax_check</pre>";
        }
        
        $auth = new ActiveDirectoryAuth();
        echo "✓ Object created successfully<br><br>";
        
        echo "2. Checking constants inside authenticate method...<br>";
        echo "LOCAL_TESTING defined: " . (defined('LOCAL_TESTING') ? 'YES' : 'NO') . "<br>";
        echo "LOCAL_TESTING value: " . (defined('LOCAL_TESTING') ? (LOCAL_TESTING ? 'TRUE' : 'FALSE') : 'UNDEFINED') . "<br>";
        echo "SKIP_AD_AUTH defined: " . (defined('SKIP_AD_AUTH') ? 'YES' : 'NO') . "<br>";
        echo "SKIP_AD_AUTH value: " . (defined('SKIP_AD_AUTH') ? (SKIP_AD_AUTH ? 'TRUE' : 'FALSE') : 'UNDEFINED') . "<br><br>";
        
        echo "3. Testing local authentication via public method...<br>";
        $local_result = $auth->testLocalAuth($username, $password);
        
        if ($local_result) {
            echo "<div style='color: green; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "✓ Local authentication successful!<br>";
            echo "User data: <pre>" . print_r($local_result, true) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "✗ Local authentication failed!";
            echo "</div>";
        }
        
        echo "<br>4. Testing main authenticate method directly...<br>";
        $result = $auth->authenticate($username, $password);
        
        if ($result) {
            echo "<div style='color: green; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
            echo "✓ Main authentication successful!<br>";
            echo "User data: <pre>" . print_r($result, true) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
            echo "✗ Main authentication failed!<br>";
            echo "This suggests the issue is in the authenticateLocal method or the conditional logic.";
            echo "</div>";
        }
        
        echo "<br>5. Testing database direct access...<br>";
        require_once 'config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ad_username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✓ User found in database:<br>";
            echo "<pre>" . print_r($user, true) . "</pre>";
        } else {
            echo "✗ User not found in database<br>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
    }
}
?>

<form method="POST" style="margin: 20px 0;">
    <h2>Test Authentication:</h2>
    <p>Try these usernames: <strong>admin</strong>, <strong>manager</strong>, <strong>technician</strong>, or <strong>user</strong></p>
    <p>Password: Any password will work (try <strong>admin123</strong>)</p>
    
    <input type="text" name="username" placeholder="Username" required style="padding: 8px; margin: 5px;"><br>
    <input type="password" name="password" placeholder="Password" required style="padding: 8px; margin: 5px;"><br>
    <button type="submit" name="test_login" style="padding: 10px 20px; background: #28a745; color: white; border: none; margin: 5px;">Test Authentication</button>
</form>

<hr>
<p><a href="test_login.php">Go to simple test page</a></p>
<p><a href="login.php">Go to actual login page</a></p>
<p><a href="index.php">Go to main page</a></p>