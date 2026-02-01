<?php
/**
 * Add Regional Coordinator Role and User Linking
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Setting up Regional Coordinator user access...\n\n";
    
    // Step 1: Add user_id column to regional_coordinators
    echo "1. Adding user_id column to regional_coordinators...\n";
    try {
        $pdo->exec("ALTER TABLE regional_coordinators ADD COLUMN user_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE regional_coordinators ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "   ✅ user_id column added\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   ℹ️  user_id column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Add 'coordinator' role to users table ENUM
    echo "\n2. Adding 'coordinator' role to users table...\n";
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'technician', 'user', 'coordinator') NOT NULL DEFAULT 'user'");
        echo "   ✅ coordinator role added to ENUM\n";
    } catch (Exception $e) {
        echo "   ℹ️  coordinator role may already exist: " . $e->getMessage() . "\n";
    }
    
    // Step 3: Create user accounts for regional coordinators
    echo "\n3. Creating user accounts for regional coordinators...\n";
    
    $coordinators = [
        ['name' => 'Sarah Mbele', 'email' => 'sarah.mbele@dffe.gov.za', 'username' => 'smbele', 'employee_id' => 'RC001'],
        ['name' => 'Thabo Nkosi', 'email' => 'thabo.nkosi@dffe.gov.za', 'username' => 'tnkosi', 'employee_id' => 'RC002'],
        ['name' => 'Nomvula Dlamini', 'email' => 'nomvula.dlamini@dffe.gov.za', 'username' => 'ndlamini', 'employee_id' => 'RC003'],
        ['name' => 'Peter van der Walt', 'email' => 'peter.vanderwaldt@dffe.gov.za', 'username' => 'pvdwaldt', 'employee_id' => 'RC004'],
        ['name' => 'John Sithole', 'email' => 'john.sithole@dffe.gov.za', 'username' => 'jsithole', 'employee_id' => 'RC005'],
        ['name' => 'Martha Mokwena', 'email' => 'martha.mokwena@dffe.gov.za', 'username' => 'mmokwena', 'employee_id' => 'RC006'],
        ['name' => 'David Mahlangu', 'email' => 'david.mahlangu@dffe.gov.za', 'username' => 'dmahlangu', 'employee_id' => 'RC007'],
        ['name' => 'Grace Mokoena', 'email' => 'grace.mokoena@dffe.gov.za', 'username' => 'gmokoena', 'employee_id' => 'RC008']
    ];
    
    foreach ($coordinators as $coord) {
        $names = explode(' ', $coord['name']);
        $first_name = $names[0];
        $last_name = $names[count($names) - 1];
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$coord['email']]);
        $existing_user = $stmt->fetch();
        
        if (!$existing_user) {
            // Create user account (no department_id for coordinators, no password for AD auth)
            $stmt = $pdo->prepare("
                INSERT INTO users (ad_username, employee_id, first_name, last_name, email, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'coordinator', 'active', NOW())
            ");
            $stmt->execute([
                $coord['username'],
                $coord['employee_id'],
                $first_name,
                $last_name,
                $coord['email']
            ]);
            $user_id = $pdo->lastInsertId();
            echo "   ✅ Created user account for {$coord['name']} (AD username: {$coord['username']})\n";
        } else {
            $user_id = $existing_user['id'];
            // Update role to coordinator if not already
            $pdo->prepare("UPDATE users SET role = 'coordinator' WHERE id = ?")->execute([$user_id]);
            echo "   ℹ️  Updated existing user {$coord['name']} to coordinator role\n";
        }
        
        // Link user to regional_coordinators table
        $stmt = $pdo->prepare("UPDATE regional_coordinators SET user_id = ? WHERE email = ?");
        $stmt->execute([$user_id, $coord['email']]);
    }
    
    echo "\n✅ Regional Coordinator access setup complete!\n\n";
    echo "Coordinator Login Credentials (AD Authentication):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($coordinators as $coord) {
        echo "  AD Username: {$coord['username']}\n";
        echo "  Employee ID: {$coord['employee_id']}\n";
        echo "  Name: {$coord['name']}\n";
        echo "  ─────────────────────────────────\n";
    }
    echo "\n✅ Coordinators can now log in with their AD credentials!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
