<?php
/**
 * COMPLETE SYSTEM RESET
 * This will delete database and prepare for fresh rebuild
 */

require_once 'config/config.php';

echo "<!DOCTYPE html><html><head><title>System Reset</title>";
echo "<style>
body { font-family: Arial; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #dc3545; }
h2 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
.step { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.warning { color: #ffc107; font-weight: bold; }
.btn { display: inline-block; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
.btn-success { background: #28a745; }
</style></head><body>";

echo "<h1>⚠️ COMPLETE SYSTEM RESET</h1>";
echo "<p class='warning'>This will DELETE the entire database and all user data!</p>";

try {
    // Connect to MySQL server (not specific database)
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='step'>";
    echo "<h2>Step 1: Drop Existing Database</h2>";
    
    // Drop database if exists
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    echo "<p class='success'>✅ Database '" . DB_NAME . "' dropped</p>";
    
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Step 2: Database Reset Complete</h2>";
    echo "<p class='success'>✅ Database completely removed</p>";
    echo "<p>Ready for fresh installation</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Run the installation script to create fresh database</li>";
    echo "<li>Fresh user accounts will be created</li>";
    echo "<li>All role assignments will be clean and simple</li>";
    echo "</ol>";
    echo "<a href='install.php' class='btn btn-success'>▶️ Run Fresh Installation</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step'>";
    echo "<h2 class='error'>❌ Error</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>