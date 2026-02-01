<?php
// Create audit_log table
require_once 'bootstrap.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        table_name VARCHAR(100) NOT NULL,
        record_id INT,
        action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_table_record (table_name, record_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )";
    
    $pdo->exec($sql);
    echo "✅ audit_log table created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
