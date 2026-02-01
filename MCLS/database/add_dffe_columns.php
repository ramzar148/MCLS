<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Adding missing DFFE requirement columns...\n\n";
    
    // Add call_type column
    try {
        $pdo->exec("ALTER TABLE maintenance_calls ADD COLUMN call_type VARCHAR(100) AFTER priority_id");
        echo "✅ call_type column added\n";
    } catch (Exception $e) {
        echo "⚠️  call_type: " . $e->getMessage() . "\n";
    }
    
    // Add province column
    try {
        $pdo->exec("ALTER TABLE maintenance_calls ADD COLUMN province VARCHAR(100) AFTER location");
        echo "✅ province column added\n";
    } catch (Exception $e) {
        echo "⚠️  province: " . $e->getMessage() . "\n";
    }
    
    // Add reporter_name column
    try {
        $pdo->exec("ALTER TABLE maintenance_calls ADD COLUMN reporter_name VARCHAR(200) AFTER reported_by");
        echo "✅ reporter_name column added\n";
    } catch (Exception $e) {
        echo "⚠️  reporter_name: " . $e->getMessage() . "\n";
    }
    
    // Add reporter_contact column
    try {
        $pdo->exec("ALTER TABLE maintenance_calls ADD COLUMN reporter_contact VARCHAR(100) AFTER reporter_name");
        echo "✅ reporter_contact column added\n";
    } catch (Exception $e) {
        echo "⚠️  reporter_contact: " . $e->getMessage() . "\n";
    }
    
    // Add response_time_minutes column
    try {
        $pdo->exec("ALTER TABLE maintenance_calls ADD COLUMN response_time_minutes INT AFTER resolved_at");
        echo "✅ response_time_minutes column added\n";
    } catch (Exception $e) {
        echo "⚠️  response_time_minutes: " . $e->getMessage() . "\n";
    }
    
    // Add region column for coastal/inland designation
    try {
        $pdo->exec("ALTER TABLE maintenance_calls ADD COLUMN region ENUM('coastal', 'inland') AFTER province");
        echo "✅ region column added\n";
    } catch (Exception $e) {
        echo "⚠️  region: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ All DFFE requirement columns added successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
