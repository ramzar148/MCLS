<?php
// Load all required classes
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/SessionManager.php';

echo "<h1>🧪 User Role Login Test</h1>";
echo "<p>Test all user types and their landing pages</p>";

// Test accounts to create/verify
$test_users = [
    'admin' => ['role' => 'admin', 'name' => 'System Administrator'],
    'manager' => ['role' => 'manager', 'name' => 'Department Manager'],
    'technician' => ['role' => 'technician', 'name' => 'Field Technician'],
    'user' => ['role' => 'user', 'name' => 'General User']
];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "<h2>User Account Status:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Username</th><th>Role</th><th>Name</th><th>Status</th><th>Test Login</th></tr>";
    
    foreach ($test_users as $username => $info) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE ad_username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<tr>";
            echo "<td>{$username}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td style='color: green;'>✓ Exists</td>";
            echo "<td><a href='login.php' target='_blank' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;'>Test Login</a></td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td>{$username}</td>";
            echo "<td>{$info['role']}</td>";
            echo "<td>{$info['name']}</td>";
            echo "<td style='color: red;'>✗ Missing</td>";
            echo "<td>Will be created on first login</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // Show login instructions
    echo "<h2>Test Instructions:</h2>";
    echo "<div style='background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<ol>";
    echo "<li><strong>Go to the login page:</strong> <a href='login.php' target='_blank'>http://localhost/MCLS/login.php</a></li>";
    echo "<li><strong>Try each username:</strong> admin, manager, technician, user</li>";
    echo "<li><strong>Use any password</strong> (try: admin123)</li>";
    echo "<li><strong>Verify role-specific dashboard content</strong></li>";
    echo "<li><strong>Test navigation permissions</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    // Show expected behavior
    echo "<h2>Expected Behavior by Role:</h2>";
    echo "<div class='role-expectations' style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
    
    foreach ($test_users as $username => $info) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
        echo "<h3>👤 {$info['role']} ({$username})</h3>";
        
        switch ($info['role']) {
            case 'admin':
                echo "<ul>";
                echo "<li>✓ Full system access</li>";
                echo "<li>✓ User management</li>";
                echo "<li>✓ System administration</li>";
                echo "<li>✓ All statistics visible</li>";
                echo "<li>✓ Can access all modules</li>";
                echo "</ul>";
                break;
                
            case 'manager':
                echo "<ul>";
                echo "<li>✓ Department management</li>";
                echo "<li>✓ Department-specific stats</li>";
                echo "<li>✓ Work order management</li>";
                echo "<li>✓ Reports access</li>";
                echo "<li>✗ Limited admin access</li>";
                echo "</ul>";
                break;
                
            case 'technician':
                echo "<ul>";
                echo "<li>✓ Personal task view</li>";
                echo "<li>✓ Maintenance call access</li>";
                echo "<li>✓ Equipment management</li>";
                echo "<li>✗ No admin access</li>";
                echo "<li>✗ Limited reporting</li>";
                echo "</ul>";
                break;
                
            case 'user':
                echo "<ul>";
                echo "<li>✓ Can report issues</li>";
                echo "<li>✓ View own calls</li>";
                echo "<li>✓ Basic dashboard</li>";
                echo "<li>✗ No management access</li>";
                echo "<li>✗ No admin access</li>";
                echo "</ul>";
                break;
        }
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 8px;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

table {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

th, td {
    padding: 12px;
    text-align: left;
}

h1, h2, h3 {
    color: #1e6b3e;
}

.role-expectations > div {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

ul {
    margin: 0;
    padding-left: 20px;
}

li {
    margin: 5px 0;
}
</style>

<div style="text-align: center; margin: 40px 0;">
    <a href="login.php" style="background: #1e6b3e; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 1.2em;">
        🚀 Start Testing Login
    </a>
</div>