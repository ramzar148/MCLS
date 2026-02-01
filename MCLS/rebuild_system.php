<?php
/**
 * COMPLETE SYSTEM REBUILD
 * Drops database and recreates everything from scratch
 */

// Allow installation mode
define('ALLOW_REINSTALL', true);

require_once 'config/config.php';

echo "<!DOCTYPE html><html><head><title>System Rebuild</title>";
echo "<style>
body { font-family: Arial; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #1e6b3e; }
h2 { color: #333; border-bottom: 2px solid #1e6b3e; padding-bottom: 10px; margin-top: 30px; }
.step { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.success { color: #28a745; }
.error { color: #dc3545; }
.info { color: #0066cc; }
ul { line-height: 1.8; }
.btn { display: inline-block; padding: 15px 30px; background: #1e6b3e; color: white; text-decoration: none; border-radius: 6px; margin: 20px 5px; font-weight: bold; }
</style></head><body>";

echo "<h1>🔄 COMPLETE SYSTEM REBUILD</h1>";

try {
    // Connect to MySQL
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // STEP 1: Drop existing database
    echo "<div class='step'>";
    echo "<h2>Step 1: Dropping Existing Database</h2>";
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    echo "<p class='success'>✅ Database '" . DB_NAME . "' dropped successfully</p>";
    echo "</div>";
    
    // STEP 2: Create fresh database
    echo "<div class='step'>";
    echo "<h2>Step 2: Creating Fresh Database</h2>";
    $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='success'>✅ Database '" . DB_NAME . "' created successfully</p>";
    $pdo->exec("USE " . DB_NAME);
    echo "</div>";
    
    // STEP 3: Create tables
    echo "<div class='step'>";
    echo "<h2>Step 3: Creating Database Tables</h2>";
    
    // Users table
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ad_username VARCHAR(100) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            role ENUM('admin', 'manager', 'technician', 'user') NOT NULL DEFAULT 'user',
            status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
            department_id INT NULL,
            employee_id VARCHAR(50) NULL,
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Users table created</p>";
    
    // Departments table
    $pdo->exec("
        CREATE TABLE departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            code VARCHAR(50) UNIQUE NOT NULL,
            description TEXT NULL,
            manager_id INT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Departments table created</p>";
    
    // Priority levels table
    $pdo->exec("
        CREATE TABLE priority_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            level INT NOT NULL,
            color_code VARCHAR(20) NOT NULL,
            response_time_hours INT NOT NULL
        )
    ");
    echo "<p class='success'>✅ Priority levels table created</p>";
    
    // Maintenance calls table
    $pdo->exec("
        CREATE TABLE maintenance_calls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            call_number VARCHAR(50) UNIQUE NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled') DEFAULT 'open',
            priority_id INT NOT NULL,
            reported_by INT NOT NULL,
            assigned_to INT NULL,
            department_id INT NOT NULL,
            location VARCHAR(255) NULL,
            equipment_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            FOREIGN KEY (priority_id) REFERENCES priority_levels(id),
            FOREIGN KEY (reported_by) REFERENCES users(id),
            FOREIGN KEY (assigned_to) REFERENCES users(id),
            FOREIGN KEY (department_id) REFERENCES departments(id)
        )
    ");
    echo "<p class='success'>✅ Maintenance calls table created</p>";
    
    // Equipment table
    $pdo->exec("
        CREATE TABLE equipment (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            asset_number VARCHAR(100) UNIQUE NULL,
            category VARCHAR(100) NULL,
            location VARCHAR(255) NULL,
            status ENUM('active', 'maintenance', 'retired') DEFAULT 'active',
            department_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p class='success'>✅ Equipment table created</p>";
    
    echo "</div>";
    
    // STEP 4: Insert default data
    echo "<div class='step'>";
    echo "<h2>Step 4: Inserting Default Data</h2>";
    
    // Insert department
    $pdo->exec("
        INSERT INTO departments (id, name, code, description) VALUES 
        (1, 'Information Technology', 'IT', 'IT Department')
    ");
    echo "<p class='success'>✅ Default department created</p>";
    
    // Insert priority levels
    $pdo->exec("
        INSERT INTO priority_levels (name, level, color_code, response_time_hours) VALUES
        ('Low', 1, '#28a745', 72),
        ('Medium', 2, '#ffc107', 24),
        ('High', 3, '#fd7e14', 8),
        ('Critical', 4, '#dc3545', 2)
    ");
    echo "<p class='success'>✅ Priority levels created</p>";
    
    // Insert test users with CLEAR roles
    $pdo->exec("
        INSERT INTO users (ad_username, first_name, last_name, email, role, status, department_id, employee_id) VALUES
        ('admin', 'System', 'Administrator', 'admin@dffe.gov.za', 'admin', 'active', 1, 'ADMIN001'),
        ('manager', 'Department', 'Manager', 'manager@dffe.gov.za', 'manager', 'active', 1, 'MGR001'),
        ('technician', 'Field', 'Technician', 'technician@dffe.gov.za', 'technician', 'active', 1, 'TECH001'),
        ('user', 'General', 'User', 'user@dffe.gov.za', 'user', 'active', 1, 'USER001')
    ");
    echo "<p class='success'>✅ Test users created (admin, manager, technician, user)</p>";
    
    // Insert sample maintenance calls
    $pdo->exec("
        INSERT INTO maintenance_calls (call_number, title, description, status, priority_id, reported_by, department_id, created_at) VALUES
        ('MC2025110001', 'Computer not starting', 'Desktop computer in office 101 fails to boot', 'open', 3, 1, 1, NOW()),
        ('MC2025110002', 'Printer jam error', 'Office printer continuously jams on page 2', 'assigned', 2, 1, 1, NOW()),
        ('MC2025110003', 'HVAC not cooling', 'Air conditioning system not producing cold air', 'in_progress', 4, 1, 1, NOW()),
        ('MC2025110004', 'Network connection down', 'Network connection lost in Building A', 'open', 4, 2, 1, NOW()),
        ('MC2025110005', 'Water leak in server room', 'Small water leak detected near server rack', 'open', 4, 1, 1, NOW())
    ");
    echo "<p class='success'>✅ Sample maintenance calls created</p>";
    
    echo "</div>";
    
    // STEP 5: Verify everything
    echo "<div class='step'>";
    echo "<h2>Step 5: Verification</h2>";
    
    $tables = ['users', 'departments', 'priority_levels', 'maintenance_calls', 'equipment'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p class='info'>📊 Table '$table': $count records</p>";
    }
    
    echo "</div>";
    
    // SUCCESS
    echo "<div class='step' style='background: #d4edda; border: 2px solid #28a745;'>";
    echo "<h2 class='success'>✅ System Rebuild Complete!</h2>";
    echo "<p><strong>Test Users Created:</strong></p>";
    echo "<ul>";
    echo "<li><strong>admin</strong> / admin123 - Full system access</li>";
    echo "<li><strong>manager</strong> / admin123 - Department management</li>";
    echo "<li><strong>technician</strong> / admin123 - Field work and maintenance</li>";
    echo "<li><strong>user</strong> / admin123 - Basic user access</li>";
    echo "</ul>";
    echo "<p><a href='login.php' class='btn'>🔑 Go to Login Page</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='step' style='background: #f8d7da; border: 2px solid #dc3545;'>";
    echo "<h2 class='error'>❌ Error During Rebuild</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>