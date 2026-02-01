-- MCLS Database Schema - Simplified for Local Installation
-- Tables created in dependency order

CREATE TABLE IF NOT EXISTS priority_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    color_code VARCHAR(7),
    response_time_hours INT,
    sort_order INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    manager_id INT,
    location VARCHAR(200),
    budget_code VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_username VARCHAR(100) UNIQUE NOT NULL,
    employee_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    department_id INT,
    role ENUM('admin', 'manager', 'technician', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS equipment_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    department_id INT,
    location VARCHAR(200),
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    status ENUM('active', 'maintenance', 'repair', 'decommissioned') DEFAULT 'active',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS maintenance_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority_id INT NOT NULL,
    equipment_id INT,
    location VARCHAR(200),
    reported_by INT NOT NULL,
    assigned_to INT,
    department_id INT,
    status ENUM('open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    closed_at DATETIME
);

CREATE TABLE IF NOT EXISTS work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_call_id INT NOT NULL,
    work_order_number VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    parts_required TEXT,
    assigned_technician INT,
    status ENUM('pending', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    scheduled_date DATETIME,
    completed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_call_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_call_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    data_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO priority_levels (name, description, color_code, response_time_hours, sort_order) VALUES
('Critical', 'System down, critical safety issue', '#dc3545', 1, 1),
('High', 'Major functionality affected', '#fd7e14', 4, 2),
('Medium', 'Minor functionality affected', '#ffc107', 24, 3),
('Low', 'Enhancement or non-urgent issue', '#28a745', 72, 4);

INSERT IGNORE INTO departments (name, code, description, location, status) VALUES
('Forestry Services', 'FOR', 'Forest management and conservation', 'Pretoria Head Office', 'active'),
('Fisheries Management', 'FISH', 'Marine and inland fisheries oversight', 'Cape Town Regional Office', 'active'),
('Environmental Affairs', 'ENV', 'Environmental protection and compliance', 'Johannesburg Regional Office', 'active'),
('IT Services', 'IT', 'Information Technology support', 'Pretoria Head Office', 'active');

INSERT IGNORE INTO equipment_categories (name, description, sort_order) VALUES
('Computer Equipment', 'Desktops, laptops, servers', 1),
('Office Equipment', 'Printers, scanners, phones', 2),
('Field Equipment', 'GPS devices, measuring tools', 3),
('Vehicles', 'Cars, trucks, boats', 4),
('Safety Equipment', 'Safety gear and protective equipment', 5);

INSERT IGNORE INTO users (ad_username, employee_id, first_name, last_name, email, department_id, role, status) VALUES
('admin', 'EMP001', 'System', 'Administrator', 'admin@dffe.gov.za', 4, 'admin', 'active'),
('j.smith', 'EMP002', 'John', 'Smith', 'john.smith@dffe.gov.za', 1, 'manager', 'active'),
('m.jones', 'EMP003', 'Mary', 'Jones', 'mary.jones@dffe.gov.za', 2, 'technician', 'active');

INSERT IGNORE INTO system_settings (setting_key, setting_value, description, category) VALUES
('site_name', 'DFFE Maintenance Call System', 'Name of the application', 'general'),
('maintenance_email', 'maintenance@dffe.gov.za', 'Email for maintenance notifications', 'notifications'),
('max_file_size', '10485760', 'Maximum file upload size in bytes', 'uploads'),
('session_timeout', '3600', 'Session timeout in seconds', 'security');