<?php
// Create equipment_categories table
require_once 'bootstrap.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create equipment_categories table
    $sql = "CREATE TABLE IF NOT EXISTS equipment_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        parent_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_parent (parent_id)
    )";
    
    $pdo->exec($sql);
    echo "✅ equipment_categories table created successfully!\n";
    
    // Insert default categories
    $sql = "INSERT IGNORE INTO equipment_categories (id, name, description) VALUES
        (1, 'IT Equipment', 'Computers, servers, network equipment'),
        (2, 'Office Equipment', 'Printers, copiers, scanners'),
        (3, 'Facilities', 'HVAC, electrical, plumbing'),
        (4, 'Vehicles', 'Cars, trucks, heavy machinery'),
        (5, 'Furniture', 'Desks, chairs, cabinets'),
        (6, 'Laboratory Equipment', 'Scientific and testing equipment'),
        (7, 'Safety Equipment', 'Fire safety, security systems'),
        (8, 'Communication Equipment', 'Phones, radios, video conferencing')";
    
    $pdo->exec($sql);
    echo "✅ Default equipment categories inserted!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
