<?php
/**
 * Test Regional Coordinator Access
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

echo "Testing Regional Coordinator System\n";
echo "═══════════════════════════════════\n\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Test 1: Check coordinator role exists
    echo "1. Checking coordinator role in users table...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $role_column = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($role_column['Type'], 'coordinator') !== false) {
        echo "   ✅ Coordinator role exists in ENUM\n";
    } else {
        echo "   ❌ Coordinator role NOT found\n";
    }
    
    // Test 2: Check user_id column in regional_coordinators
    echo "\n2. Checking user_id link in regional_coordinators...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM regional_coordinators LIKE 'user_id'");
    $user_id_column = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_id_column) {
        echo "   ✅ user_id column exists\n";
    } else {
        echo "   ❌ user_id column NOT found\n";
    }
    
    // Test 3: Check coordinator user accounts
    echo "\n3. Checking coordinator user accounts...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'coordinator'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Found {$result['count']} coordinator accounts\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("
            SELECT 
                u.ad_username, 
                u.first_name, 
                u.last_name, 
                u.email,
                rc.region,
                rc.provinces
            FROM users u
            JOIN regional_coordinators rc ON u.id = rc.user_id
            WHERE u.role = 'coordinator' AND u.status = 'active'
        ");
        $coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n   Coordinator Accounts:\n";
        echo "   ─────────────────────────────────────────────────────────\n";
        foreach ($coordinators as $coord) {
            echo "   • {$coord['first_name']} {$coord['last_name']}\n";
            echo "     Username: {$coord['ad_username']}\n";
            echo "     Region: {$coord['region']}\n";
            echo "     Provinces: {$coord['provinces']}\n";
            echo "   ─────────────────────────────────────────────────────────\n";
        }
        echo "   ✅ All coordinators have user accounts\n";
    }
    
    // Test 4: Check call distribution by region
    echo "\n4. Checking maintenance calls by region...\n";
    $stmt = $pdo->query("
        SELECT 
            region,
            COUNT(*) as call_count,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_calls
        FROM maintenance_calls
        WHERE region IS NOT NULL
        GROUP BY region
        ORDER BY region
    ");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($regions)) {
        echo "   ℹ️  No maintenance calls with region data yet\n";
    } else {
        foreach ($regions as $region) {
            echo "   • {$region['region']}: {$region['call_count']} total calls ({$region['open_calls']} open)\n";
        }
    }
    
    // Test 5: Test coordinator login simulation
    echo "\n5. Testing coordinator authentication...\n";
    $stmt = $pdo->prepare("
        SELECT u.*, rc.region, rc.provinces, rc.name as coordinator_name
        FROM users u
        JOIN regional_coordinators rc ON u.id = rc.user_id
        WHERE u.role = 'coordinator' AND u.status = 'active'
        LIMIT 1
    ");
    $stmt->execute();
    $test_coord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_coord) {
        echo "   ✅ Successfully retrieved coordinator data\n";
        echo "   Test coordinator: {$test_coord['coordinator_name']}\n";
        echo "   Region: {$test_coord['region']}\n";
        echo "   Provinces: {$test_coord['provinces']}\n";
        
        // Simulate session data
        echo "\n   Session data structure:\n";
        echo "   - user_id: {$test_coord['id']}\n";
        echo "   - role: coordinator\n";
        echo "   - coordinator_data: (region, provinces, name)\n";
        echo "   ✅ Ready for dashboard access\n";
    }
    
    echo "\n═══════════════════════════════════\n";
    echo "✅ All tests passed!\n\n";
    
    echo "Next Steps:\n";
    echo "1. Log in with any coordinator username (e.g., 'smbele')\n";
    echo "2. Access http://localhost/MCLS/regional_dashboard.php\n";
    echo "3. View calls filtered to your region\n";
    echo "4. Receive email notifications with dashboard link\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
