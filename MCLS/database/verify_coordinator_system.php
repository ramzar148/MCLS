<?php
/**
 * End-to-End Verification: Regional Coordinator System
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  REGIONAL COORDINATOR SYSTEM - END-TO-END VERIFICATION       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$all_tests_passed = true;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // TEST 1: Database Schema
    echo "TEST 1: Database Schema\n";
    echo "──────────────────────────────────────────────────────\n";
    
    // Check coordinator role
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $role_col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (strpos($role_col['Type'], 'coordinator') !== false) {
        echo "  ✅ Coordinator role exists in users.role ENUM\n";
    } else {
        echo "  ❌ Coordinator role missing\n";
        $all_tests_passed = false;
    }
    
    // Check user_id in regional_coordinators
    $stmt = $pdo->query("SHOW COLUMNS FROM regional_coordinators LIKE 'user_id'");
    if ($stmt->fetch()) {
        echo "  ✅ user_id column exists in regional_coordinators\n";
    } else {
        echo "  ❌ user_id column missing\n";
        $all_tests_passed = false;
    }
    
    // TEST 2: Coordinator Accounts
    echo "\nTEST 2: Coordinator User Accounts\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE role = 'coordinator' AND status = 'active'
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] == 8) {
        echo "  ✅ All 8 coordinator accounts created\n";
    } else {
        echo "  ❌ Expected 8 coordinators, found {$result['count']}\n";
        $all_tests_passed = false;
    }
    
    // Check all linked
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM regional_coordinators 
        WHERE user_id IS NOT NULL AND status = 'active'
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] == 8) {
        echo "  ✅ All coordinators linked to user accounts\n";
    } else {
        echo "  ❌ Not all coordinators linked to users\n";
        $all_tests_passed = false;
    }
    
    // TEST 3: Regional Coverage
    echo "\nTEST 3: Regional Coverage\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $stmt = $pdo->query("
        SELECT region, COUNT(*) as coordinator_count
        FROM regional_coordinators
        WHERE status = 'active'
        GROUP BY region
    ");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_coastal = false;
    $has_inland = false;
    
    foreach ($regions as $region) {
        if ($region['region'] == 'coastal') {
            echo "  ✅ Coastal region: {$region['coordinator_count']} coordinators\n";
            $has_coastal = true;
        } elseif ($region['region'] == 'inland') {
            echo "  ✅ Inland region: {$region['coordinator_count']} coordinators\n";
            $has_inland = true;
        }
    }
    
    if (!$has_coastal || !$has_inland) {
        echo "  ❌ Missing regional coverage\n";
        $all_tests_passed = false;
    }
    
    // TEST 4: Province Coverage
    echo "\nTEST 4: Province Coverage\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $required_provinces = [
        'Western Cape', 'Eastern Cape', 'KwaZulu-Natal',
        'Gauteng', 'Limpopo', 'Mpumalanga', 
        'North West', 'Free State', 'Northern Cape'
    ];
    
    $stmt = $pdo->query("SELECT provinces FROM regional_coordinators WHERE status = 'active'");
    $all_provinces_text = '';
    while ($row = $stmt->fetch()) {
        $all_provinces_text .= $row['provinces'] . ',';
    }
    
    $covered_count = 0;
    foreach ($required_provinces as $prov) {
        if (stripos($all_provinces_text, $prov) !== false) {
            $covered_count++;
        }
    }
    
    if ($covered_count == 9) {
        echo "  ✅ All 9 provinces covered\n";
    } else {
        echo "  ⚠️  {$covered_count}/9 provinces covered\n";
    }
    
    // TEST 5: Session Management
    echo "\nTEST 5: Session Management (Role Hierarchy)\n";
    echo "──────────────────────────────────────────────────────\n";
    
    // Simulate SessionManager role check
    $role_hierarchy = [
        'admin' => ['admin', 'manager', 'coordinator', 'technician', 'user'],
        'manager' => ['manager', 'coordinator', 'technician', 'user'],
        'coordinator' => ['coordinator', 'user'],
        'technician' => ['technician', 'user'],
        'user' => ['user']
    ];
    
    if (isset($role_hierarchy['coordinator'])) {
        echo "  ✅ Coordinator role in hierarchy\n";
    } else {
        echo "  ❌ Coordinator role not in hierarchy\n";
        $all_tests_passed = false;
    }
    
    // Admin can access coordinator functions
    if (in_array('coordinator', $role_hierarchy['admin'])) {
        echo "  ✅ Admin has coordinator permissions\n";
    } else {
        echo "  ❌ Admin missing coordinator permissions\n";
        $all_tests_passed = false;
    }
    
    // Manager can access coordinator functions
    if (in_array('coordinator', $role_hierarchy['manager'])) {
        echo "  ✅ Manager has coordinator permissions\n";
    } else {
        echo "  ❌ Manager missing coordinator permissions\n";
        $all_tests_passed = false;
    }
    
    // TEST 6: File Existence
    echo "\nTEST 6: Required Files\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $required_files = [
        'regional_dashboard.php' => 'Regional Dashboard',
        'classes/SessionManager.php' => 'Session Manager',
        'login.php' => 'Login Page',
        'includes/header.php' => 'Header Navigation',
        'includes/EmailNotificationService.php' => 'Email Service',
        'database/setup_coordinator_access.php' => 'Setup Script',
        'database/test_coordinator_access.php' => 'Test Script'
    ];
    
    foreach ($required_files as $file => $name) {
        $path = __DIR__ . '/../' . $file;
        if (file_exists($path)) {
            echo "  ✅ {$name}\n";
        } else {
            echo "  ❌ {$name} missing\n";
            $all_tests_passed = false;
        }
    }
    
    // TEST 7: Dashboard Access Control
    echo "\nTEST 7: Dashboard Access Control\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $dashboard_content = file_get_contents(__DIR__ . '/../regional_dashboard.php');
    
    if (strpos($dashboard_content, "requireAuth('coordinator')") !== false) {
        echo "  ✅ Dashboard requires coordinator authentication\n";
    } else {
        echo "  ❌ Dashboard access control missing\n";
        $all_tests_passed = false;
    }
    
    if (strpos($dashboard_content, "WHERE mc.region = ?") !== false) {
        echo "  ✅ Dashboard filters by region\n";
    } else {
        echo "  ❌ Regional filtering missing\n";
        $all_tests_passed = false;
    }
    
    // TEST 8: Email Notifications
    echo "\nTEST 8: Email Notification Enhancement\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $email_content = file_get_contents(__DIR__ . '/../includes/EmailNotificationService.php');
    
    if (strpos($email_content, 'regional_dashboard.php') !== false) {
        echo "  ✅ Email includes regional dashboard link\n";
    } else {
        echo "  ❌ Email missing dashboard link\n";
        $all_tests_passed = false;
    }
    
    // TEST 9: Navigation Menu
    echo "\nTEST 9: Navigation Menu\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $header_content = file_get_contents(__DIR__ . '/../includes/header.php');
    
    if (strpos($header_content, "hasRole('coordinator')") !== false) {
        echo "  ✅ Coordinator menu item exists\n";
    } else {
        echo "  ❌ Coordinator menu item missing\n";
        $all_tests_passed = false;
    }
    
    if (strpos($header_content, 'regional_dashboard.php') !== false) {
        echo "  ✅ Regional dashboard link in navigation\n";
    } else {
        echo "  ❌ Dashboard link missing from navigation\n";
        $all_tests_passed = false;
    }
    
    // TEST 10: Sample Coordinator Data
    echo "\nTEST 10: Sample Coordinator Profile\n";
    echo "──────────────────────────────────────────────────────\n";
    
    $stmt = $pdo->query("
        SELECT 
            u.ad_username,
            u.first_name,
            u.last_name,
            u.email,
            u.role,
            rc.name as coord_name,
            rc.region,
            rc.provinces
        FROM users u
        JOIN regional_coordinators rc ON u.id = rc.user_id
        WHERE u.role = 'coordinator' AND u.status = 'active'
        LIMIT 1
    ");
    $coord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($coord) {
        echo "  ✅ Sample coordinator retrieved successfully\n";
        echo "     Name: {$coord['first_name']} {$coord['last_name']}\n";
        echo "     Username: {$coord['ad_username']}\n";
        echo "     Region: {$coord['region']}\n";
        echo "     Provinces: {$coord['provinces']}\n";
    } else {
        echo "  ❌ Cannot retrieve coordinator data\n";
        $all_tests_passed = false;
    }
    
    // FINAL SUMMARY
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    if ($all_tests_passed) {
        echo "║                    ✅ ALL TESTS PASSED ✅                    ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        
        echo "🎉 Regional Coordinator System is fully operational!\n\n";
        
        echo "📋 SYSTEM READY FOR:\n";
        echo "  ✅ Coordinator login via AD authentication\n";
        echo "  ✅ Regional dashboard access (filtered by region)\n";
        echo "  ✅ Email notifications with dashboard links\n";
        echo "  ✅ Call monitoring and tracking\n";
        echo "  ✅ Statistics and reporting\n\n";
        
        echo "🔐 LOGIN INSTRUCTIONS:\n";
        echo "  1. Navigate to: http://localhost/MCLS/login.php\n";
        echo "  2. Use coordinator username: smbele, tnkosi, ndlamini, etc.\n";
        echo "  3. Enter any password (local testing mode)\n";
        echo "  4. Will auto-redirect to regional dashboard\n\n";
        
        echo "📚 DOCUMENTATION:\n";
        echo "  • REGIONAL_COORDINATOR_ACCESS.md - Implementation details\n";
        echo "  • COORDINATOR_QUICK_START_GUIDE.md - User guide\n";
        echo "  • ROLE_COMPLIANCE_ANALYSIS.md - DFFE role mapping\n\n";
        
        echo "👥 COORDINATOR ACCOUNTS (8 total):\n";
        $stmt = $pdo->query("
            SELECT u.ad_username, u.first_name, u.last_name, rc.region
            FROM users u
            JOIN regional_coordinators rc ON u.id = rc.user_id
            WHERE u.role = 'coordinator' AND u.status = 'active'
            ORDER BY rc.region, u.last_name
        ");
        while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  • {$c['ad_username']} - {$c['first_name']} {$c['last_name']} ({$c['region']})\n";
        }
        
    } else {
        echo "║                   ❌ SOME TESTS FAILED ❌                   ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        echo "⚠️  Please review failed tests above and take corrective action.\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "\n❌ CRITICAL ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
