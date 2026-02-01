-- MCLS Database Schema
-- Department of Forestry, Fisheries and the Environment
-- Maintenance Call Logging System

-- Note: Database creation is handled by the installer

-- Priority levels (create first as it's referenced by other tables)
CREATE TABLE IF NOT EXISTS priority_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    color_code VARCHAR(7), -- Hex color
    response_time_hours INT,
    sort_order INT DEFAULT 0
);

-- Departments table (create early as it's referenced by users and equipment)
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_manager (manager_id)
);

-- Users table (linked to Active Directory)
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ad_username (ad_username),
    INDEX idx_employee_id (employee_id),
    INDEX idx_department (department_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Equipment categories
CREATE TABLE IF NOT EXISTS equipment_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id)
);

-- Equipment table
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    location VARCHAR(200),
    department_id INT,
    status ENUM('operational', 'maintenance', 'repair', 'decommissioned') DEFAULT 'operational',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_tag (asset_tag),
    INDEX idx_category (category_id),
    INDEX idx_department (department_id),
    INDEX idx_status (status),
    INDEX idx_location (location)
);

-- Maintenance calls table
CREATE TABLE IF NOT EXISTS maintenance_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    equipment_id INT,
    location VARCHAR(200),
    reported_by INT NOT NULL,
    assigned_to INT,
    priority_id INT,
    status ENUM('open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'closed', 'cancelled') DEFAULT 'open',
    reported_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    scheduled_date DATETIME,
    started_date DATETIME,
    completed_date DATETIME,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    cost DECIMAL(10,2),
    resolution_notes TEXT,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_call_number (call_number),
    INDEX idx_equipment (equipment_id),
    INDEX idx_reported_by (reported_by),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_priority (priority_id),
    INDEX idx_status (status),
    INDEX idx_reported_date (reported_date)
);

-- Work orders table
CREATE TABLE IF NOT EXISTS work_orders (
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
);

-- Comments/Updates table
CREATE TABLE IF NOT EXISTS call_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_call_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_maintenance_call (maintenance_call_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
);

-- File attachments table
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_call_id INT,
    work_order_id INT,
    comment_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_maintenance_call (maintenance_call_id),
    INDEX idx_work_order (work_order_id),
    INDEX idx_comment (comment_id),
    INDEX idx_uploaded_by (uploaded_by)
);

-- Audit log table
CREATE TABLE IF NOT EXISTS audit_log (
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
);

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    data_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insert default data
INSERT IGNORE INTO priority_levels (name, description, color_code, response_time_hours, sort_order) VALUES
('Critical', 'System down, security breach, safety hazard', '#FF0000', 1, 1),
('High', 'Significant impact on operations', '#FF8000', 4, 2),
('Medium', 'Moderate impact, workaround available', '#FFFF00', 24, 3),
('Low', 'Minor issue, can be scheduled', '#008000', 72, 4);

INSERT IGNORE INTO equipment_categories (name, description) VALUES
('IT Equipment', 'Computers, servers, network equipment'),
('Office Equipment', 'Printers, scanners, office furniture'),
('Vehicles', 'Government vehicles and fleet'),
('Security Systems', 'CCTV, access control, alarms'),
('HVAC', 'Heating, ventilation, air conditioning'),
('Electrical', 'Electrical systems and fixtures'),
('Plumbing', 'Water systems and fixtures'),
('Grounds', 'Landscaping and outdoor equipment');

INSERT IGNORE INTO system_settings (setting_key, setting_value, description, data_type) VALUES
('maintenance_call_prefix', 'MC', 'Prefix for maintenance call numbers', 'string'),
('work_order_prefix', 'WO', 'Prefix for work order numbers', 'string'),
('auto_assignment', 'false', 'Enable automatic assignment of calls', 'boolean'),
('notification_email', 'true', 'Send email notifications', 'boolean'),
('max_file_size', '5242880', 'Maximum file upload size in bytes', 'integer'),
('session_timeout', '3600', 'Session timeout in seconds', 'integer');