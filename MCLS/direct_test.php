<?php
// Simple direct test without bootstrap
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🌿 Direct Authentication Test</h1>";

// Load configuration manually
require_once 'config/config.php';

echo "<h2>Configuration:</h2>";
echo "LOCAL_TESTING: " . (defined('LOCAL_TESTING') ? (LOCAL_TESTING ? 'YES' : 'NO') : 'NOT_DEFINED') . "<br>";
echo "SKIP_AD_AUTH: " . (defined('SKIP_AD_AUTH') ? (SKIP_AD_AUTH ? 'YES' : 'NO') : 'NOT_DEFINED') . "<br>";

// Test simple local authentication without using the class
if (isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Direct Database Authentication Test:</h2>";
    echo "Username: " . htmlspecialchars($username) . "<br>";
    
    try {
        // Direct database connection
        require_once 'config/database.php';
        $database = new Database();
        $pdo = $database->getConnection();
        echo "✓ Database connected<br>";
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ad_username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✓ User found: " . $user['ad_username'] . " (Role: " . $user['role'] . ")<br>";
            
            // In local testing mode, accept any password
            if (defined('LOCAL_TESTING') && LOCAL_TESTING && strlen($password) > 0) {
                echo "<div style='color: green; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
                echo "✓ Direct authentication successful!<br>";
                echo "User details:<br>";
                echo "- Username: " . $user['ad_username'] . "<br>";
                echo "- Name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
                echo "- Email: " . $user['email'] . "<br>";
                echo "- Role: " . $user['role'] . "<br>";
                echo "</div>";
                
                // Test session creation
                require_once 'classes/SessionManager.php';
                $session = new SessionManager();
                
                $user_data = [
                    'id' => $user['id'],
                    'username' => $user['ad_username'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'department_id' => $user['department_id'],
                    'role' => $user['role'],
                    'employee_id' => $user['employee_id']
                ];
                
                if ($session->login($user_data)) {
                    echo "<div style='color: green; background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
                    echo "✓ Session created successfully! You can now access the system.<br>";
                    echo "<a href='dashboard.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Go to Dashboard</a>";
                    echo "</div>";
                } else {
                    echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
                    echo "✗ Session creation failed";
                    echo "</div>";
                }
                
            } else {
                echo "✗ Password validation failed (LOCAL_TESTING mode requires non-empty password)<br>";
            }
        } else {
            echo "✗ User not found in database<br>";
            
            // Try to create the user if it's a valid test user
            if (in_array($username, ['admin', 'manager', 'technician', 'user'])) {
                echo "Creating test user...<br>";
                $role = $username === 'admin' ? 'admin' : $username;
                $stmt = $pdo->prepare("INSERT IGNORE INTO users (ad_username, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $success = $stmt->execute([
                    $username,
                    ucfirst($username),
                    'User',
                    $username . '@test.local',
                    $role
                ]);
                
                if ($success) {
                    echo "✓ User created successfully! Try logging in again.<br>";
                } else {
                    echo "✗ Failed to create user<br>";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . " Line: " . $e->getLine();
        echo "</div>";
    }
}
?>

<form method="POST" style="margin: 20px 0;">
    <h2>Direct Authentication Test:</h2>
    <p>Try these usernames: <strong>admin</strong>, <strong>manager</strong>, <strong>technician</strong>, or <strong>user</strong></p>
    <p>Password: Any password will work</p>
    
    <input type="text" name="username" placeholder="Username" required style="padding: 8px; margin: 5px;"><br>
    <input type="password" name="password" placeholder="Password" required style="padding: 8px; margin: 5px;"><br>
    <button type="submit" name="test_login" style="padding: 10px 20px; background: #28a745; color: white; border: none; margin: 5px;">Test Direct Login</button>
</form>

<hr>
<p><strong>This test bypasses the ActiveDirectoryAuth class and tests authentication directly.</strong></p>
<p><a href="login.php">Go to actual login page</a></p>
<p><a href="dashboard.php">Go to dashboard (if logged in)</a></p>