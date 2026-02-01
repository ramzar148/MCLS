<?php
/**
 * Create Regional Coordinators and Notification Tables
 * DFFE Requirement: Regional notification system for coastal/inland regions
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Creating regional notification tables...\n\n";
    
    // Create regional_coordinators table
    $sql = "CREATE TABLE IF NOT EXISTS regional_coordinators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        region ENUM('coastal', 'inland') NOT NULL,
        provinces TEXT COMMENT 'Comma-separated list of provinces covered',
        position VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_region (region),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ regional_coordinators table created\n";
    
    // Create notification_log table
    $sql = "CREATE TABLE IF NOT EXISTS notification_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        maintenance_call_id INT NOT NULL,
        notification_type ENUM('new_call', 'assignment', 'status_change', 'completion') NOT NULL,
        recipient_type ENUM('coordinator', 'reporter', 'technician') NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        recipient_name VARCHAR(200),
        subject VARCHAR(500),
        message TEXT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('sent', 'failed', 'pending') DEFAULT 'sent',
        error_message TEXT,
        FOREIGN KEY (maintenance_call_id) REFERENCES maintenance_calls(id) ON DELETE CASCADE,
        INDEX idx_call_id (maintenance_call_id),
        INDEX idx_type (notification_type),
        INDEX idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ notification_log table created\n";
    
    // Insert sample regional coordinators (4 coastal, 4 inland)
    echo "\nInserting regional coordinators...\n";
    
    $coordinators = [
        // Coastal Region (Western Cape, Eastern Cape, KwaZulu-Natal)
        [
            'name' => 'Sarah Mbele',
            'email' => 'sarah.mbele@dffe.gov.za',
            'phone' => '021-819-2001',
            'region' => 'coastal',
            'provinces' => 'Western Cape',
            'position' => 'Regional Coordinator - Western Cape'
        ],
        [
            'name' => 'Thabo Nkosi',
            'email' => 'thabo.nkosi@dffe.gov.za',
            'phone' => '043-726-7001',
            'region' => 'coastal',
            'provinces' => 'Eastern Cape',
            'position' => 'Regional Coordinator - Eastern Cape'
        ],
        [
            'name' => 'Nomvula Dlamini',
            'email' => 'nomvula.dlamini@dffe.gov.za',
            'phone' => '031-336-2700',
            'region' => 'coastal',
            'provinces' => 'KwaZulu-Natal',
            'position' => 'Regional Coordinator - KZN'
        ],
        [
            'name' => 'Peter van der Walt',
            'email' => 'peter.vanderwaldt@dffe.gov.za',
            'phone' => '021-819-2002',
            'region' => 'coastal',
            'provinces' => 'Western Cape,Eastern Cape,KwaZulu-Natal',
            'position' => 'Senior Regional Manager - Coastal'
        ],
        
        // Inland Region (Gauteng, Limpopo, Mpumalanga, North West, Free State, Northern Cape)
        [
            'name' => 'John Sithole',
            'email' => 'john.sithole@dffe.gov.za',
            'phone' => '012-399-9000',
            'region' => 'inland',
            'provinces' => 'Gauteng',
            'position' => 'Regional Coordinator - Gauteng'
        ],
        [
            'name' => 'Martha Mokwena',
            'email' => 'martha.mokwena@dffe.gov.za',
            'phone' => '015-291-1600',
            'region' => 'inland',
            'provinces' => 'Limpopo',
            'position' => 'Regional Coordinator - Limpopo'
        ],
        [
            'name' => 'David Mahlangu',
            'email' => 'david.mahlangu@dffe.gov.za',
            'phone' => '013-759-7000',
            'region' => 'inland',
            'provinces' => 'Mpumalanga',
            'position' => 'Regional Coordinator - Mpumalanga'
        ],
        [
            'name' => 'Grace Mokoena',
            'email' => 'grace.mokoena@dffe.gov.za',
            'phone' => '012-399-9001',
            'region' => 'inland',
            'provinces' => 'Gauteng,Limpopo,Mpumalanga,North West,Free State,Northern Cape',
            'position' => 'Senior Regional Manager - Inland'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO regional_coordinators 
        (name, email, phone, region, provinces, position, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')");
    
    foreach ($coordinators as $coord) {
        $stmt->execute([
            $coord['name'],
            $coord['email'],
            $coord['phone'],
            $coord['region'],
            $coord['provinces'],
            $coord['position']
        ]);
        echo "  ✅ Added: {$coord['name']} ({$coord['region']})\n";
    }
    
    echo "\n✅ All regional notification tables created successfully!\n";
    echo "✅ 8 regional coordinators added (4 coastal, 4 inland)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
