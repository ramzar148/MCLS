<?php
// Create work_orders table
require_once 'bootstrap.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS work_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        work_order_number VARCHAR(50) UNIQUE NOT NULL,
        maintenance_call_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        assigned_to INT,
        status ENUM('pending', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        approved_by INT,
        approved_date DATETIME,
        scheduled_start DATETIME,
        scheduled_end DATETIME,
        actual_start DATETIME,
        actual_end DATETIME,
        materials_needed TEXT,
        tools_required TEXT,
        safety_requirements TEXT,
        estimated_cost DECIMAL(10,2),
        actual_cost DECIMAL(10,2),
        completion_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_work_order_number (work_order_number),
        INDEX idx_maintenance_call (maintenance_call_id),
        INDEX idx_assigned_to (assigned_to),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($sql);
    echo "✅ work_orders table created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
