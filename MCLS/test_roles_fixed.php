<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/SessionManager.php';

$session = new SessionManager();

echo "<h1>🔧 Role System Test - FIXED</h1>";

if ($session->isAuthenticated()) {
    $user = $session->getCurrentUser();
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>✅ Current User Session:</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Username</td><td><strong>" . ($user['username'] ?? 'Not set') . "</strong></td></tr>";
    echo "<tr><td>Role</td><td><strong>" . ($user['role'] ?? 'Not set') . "</strong></td></tr>";
    echo "<tr><td>Full Name</td><td>" . ($user['full_name'] ?? 'Not set') . "</td></tr>";
    echo "<tr><td>Department</td><td>" . ($user['department_name'] ?? 'Not set') . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";
    
    // Exact Role Test
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #28a745;'>";
    echo "<h3>🎯 Exact Role Check (isRole)</h3>";
    echo "<p><em>This should show TRUE only for your actual role</em></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Role</th><th>Result</th></tr>";
    
    $roles_to_test = ['admin', 'manager', 'technician', 'user'];
    foreach ($roles_to_test as $role) {
        $is_exact = $session->isRole($role);
        $result = $is_exact ? '✅ TRUE' : '❌ FALSE';
        $color = $is_exact ? 'green' : 'red';
        $bg = $is_exact ? '#d4edda' : '#f8d7da';
        echo "<tr style='background: {$bg};'><td>{$role}</td><td style='color: {$color}; font-weight: bold;'>{$result}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Hierarchical Role Test
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #007bff;'>";
    echo "<h3>🏗️ Hierarchical Role Check (hasRole)</h3>";
    echo "<p><em>Admin should see TRUE for all, Manager for manager/tech/user, etc.</em></p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Role</th><th>Result</th></tr>";
    
    foreach ($roles_to_test as $role) {
        $has_permission = $session->hasRole($role);
        $result = $has_permission ? '✅ TRUE' : '❌ FALSE';
        $color = $has_permission ? 'green' : 'red';
        $bg = $has_permission ? '#d4edda' : '#f8d7da';
        echo "<tr style='background: {$bg};'><td>{$role}</td><td style='color: {$color}; font-weight: bold;'>{$result}</td></tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>📋 Expected Dashboard Behavior:</h3>";
    $current_role = $user['role'] ?? 'unknown';
    switch ($current_role) {
        case 'admin':
            echo "<p><strong>Admin Dashboard:</strong> You should see system statistics, user management options, and administrative controls.</p>";
            break;
        case 'manager':
            echo "<p><strong>Manager Dashboard:</strong> You should see department-specific statistics and management tools.</p>";
            break;
        case 'technician':
            echo "<p><strong>Technician Dashboard:</strong> You should see personal task counts and maintenance-focused tools.</p>";
            break;
        case 'user':
            echo "<p><strong>User Dashboard:</strong> You should see basic statistics and issue reporting options.</p>";
            break;
        default:
            echo "<p><strong>Unknown Role:</strong> Please check your user configuration.</p>";
    }
    echo "</div>";
    
} else {
    echo "<div style='color: red; padding: 20px; background: #f8d7da; border-radius: 8px;'>";
    echo "<strong>❌ No active session</strong><br>";
    echo "Please <a href='login.php'>login first</a> to test the role system.";
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
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

th, td {
    padding: 8px 12px;
    text-align: left;
}

th {
    background: #495057;
    color: white;
}

h1, h2, h3 {
    color: #1e6b3e;
}

a {
    color: #1e6b3e;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>

<div style="text-align: center; margin: 40px 0;">
    <a href="dashboard.php" style="background: #1e6b3e; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin-right: 10px;">
        🏠 Dashboard
    </a>
    <a href="login.php" style="background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin-right: 10px;">
        🔑 Login
    </a>
    <a href="logout.php" style="background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px;">
        🚪 Logout
    </a>
</div>