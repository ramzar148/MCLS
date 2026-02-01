<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'classes/SessionManager.php';

$session = new SessionManager();

echo "<h1>🔍 Role Debug Test</h1>";

if ($session->isAuthenticated()) {
    $user = $session->getCurrentUser();
    echo "<h2>Current Session Data:</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Username</td><td>" . ($user['username'] ?? 'Not set') . "</td></tr>";
    echo "<tr><td>Role</td><td><strong>" . ($user['role'] ?? 'Not set') . "</strong></td></tr>";
    echo "<tr><td>Full Name</td><td>" . ($user['full_name'] ?? 'Not set') . "</td></tr>";
    echo "<tr><td>Department</td><td>" . ($user['department_name'] ?? 'Not set') . "</td></tr>";
    echo "</table>";
    
    echo "<h2>Role Permissions Test:</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Required Role</th><th>Has Permission</th></tr>";
    
    $roles_to_test = ['admin', 'manager', 'technician', 'user'];
    foreach ($roles_to_test as $role) {
        $has_permission = $session->hasRole($role) ? '✅ YES' : '❌ NO';
        $color = $session->hasRole($role) ? 'green' : 'red';
        echo "<tr><td>{$role}</td><td style='color: {$color};'><strong>{$has_permission}</strong></td></tr>";
    }
    echo "</table>";
    
    echo "<h2>Raw Session Data:</h2>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto;'>";
    print_r($_SESSION);
    echo "</pre>";
    
} else {
    echo "<div style='color: red; padding: 20px; background: #f8d7da; border-radius: 8px;'>";
    echo "<strong>❌ No active session</strong><br>";
    echo "Please <a href='login.php'>login first</a> to test role permissions.";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

table {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    width: 100%;
}

th, td {
    padding: 12px;
    text-align: left;
}

th {
    background: #1e6b3e;
    color: white;
}

h1, h2 {
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
    <a href="login.php" style="background: #1e6b3e; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin-right: 10px;">
        🔑 Login
    </a>
    <a href="logout.php" style="background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px;">
        🚪 Logout
    </a>
</div>