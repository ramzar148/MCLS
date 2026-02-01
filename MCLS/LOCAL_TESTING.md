# MCLS Local Testing Guide
## Testing Links and Setup for XAMPP Environment

### 🖥️ **Local Environment Setup**

#### **XAMPP Configuration**
1. **Start XAMPP Services**:
   - Apache Web Server
   - MySQL Database
   - (Optional) FileZilla FTP Server

2. **Verify XAMPP is Running**:
   - Apache: `http://localhost/dashboard/`
   - MySQL: `http://localhost/phpmyadmin/`

### 🔗 **MCLS Testing Links**

#### **Main Application URLs**
```
Base URL: http://localhost/MCLS/
```

#### **Core System Pages**
- **Login Page**: `http://localhost/MCLS/login.php`
- **Dashboard**: `http://localhost/MCLS/dashboard.php`
- **Main Index**: `http://localhost/MCLS/index.php`

#### **Maintenance Calls Module**
- **All Calls**: `http://localhost/MCLS/maintenance_calls/index.php`
- **Create New Call**: `http://localhost/MCLS/maintenance_calls/create.php`
- **View Call**: `http://localhost/MCLS/maintenance_calls/view.php?id=1`
- **Edit Call**: `http://localhost/MCLS/maintenance_calls/edit.php?id=1`

#### **Work Orders Module**
- **All Work Orders**: `http://localhost/MCLS/work_orders/index.php`
- **Create Work Order**: `http://localhost/MCLS/work_orders/create.php`
- **View Work Order**: `http://localhost/MCLS/work_orders/view.php?id=1`

#### **Equipment Module**
- **Equipment List**: `http://localhost/MCLS/equipment/index.php`
- **Add Equipment**: `http://localhost/MCLS/equipment/create.php`
- **Equipment Details**: `http://localhost/MCLS/equipment/view.php?id=1`

#### **Reports Module**
- **Reports Dashboard**: `http://localhost/MCLS/reports/index.php`
- **Call Statistics**: `http://localhost/MCLS/reports/statistics.php`
- **Department Report**: `http://localhost/MCLS/reports/departments.php`

#### **Administration Module**
- **Admin Panel**: `http://localhost/MCLS/admin/index.php`
- **User Management**: `http://localhost/MCLS/admin/users.php`
- **System Settings**: `http://localhost/MCLS/admin/settings.php`
- **Audit Log**: `http://localhost/MCLS/admin/audit.php`

#### **API Endpoints**
- **Session Check**: `http://localhost/MCLS/api/session-check.php`
- **CSRF Token**: `http://localhost/MCLS/api/csrf-token.php`
- **Extend Session**: `http://localhost/MCLS/api/extend-session.php`

#### **AJAX Endpoints**
- **Assign Call**: `http://localhost/MCLS/maintenance_calls/ajax/assign.php`
- **Delete Call**: `http://localhost/MCLS/maintenance_calls/ajax/delete.php`
- **Update Status**: `http://localhost/MCLS/maintenance_calls/ajax/status.php`

#### **Utility Pages**
- **Logout**: `http://localhost/MCLS/logout.php`
- **Profile**: `http://localhost/MCLS/profile.php`
- **Settings**: `http://localhost/MCLS/settings.php`

### 🗄️ **Database Setup for Local Testing**

#### **1. Create Database Using phpMyAdmin**
```
URL: http://localhost/phpmyadmin/
```

#### **2. Execute Database Schema**
1. Open phpMyAdmin
2. Create new database: `mcls_db`
3. Import the schema file: `/MCLS/database/schema.sql`

#### **3. Quick Database Setup SQL**
```sql
-- Create database
CREATE DATABASE mcls_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Select the database
USE mcls_db;

-- Import the schema (copy from /database/schema.sql)
-- Or use phpMyAdmin import feature
```

### 👤 **Test User Accounts**

Since Active Directory may not be available locally, you can create test users directly in the database:

```sql
-- Insert test users for local testing
INSERT INTO users (ad_username, first_name, last_name, email, role, status) VALUES
('admin', 'System', 'Administrator', 'admin@test.local', 'admin', 'active'),
('manager', 'Test', 'Manager', 'manager@test.local', 'manager', 'active'),
('technician', 'Test', 'Technician', 'technician@test.local', 'technician', 'active'),
('user', 'Test', 'User', 'user@test.local', 'user', 'active');

-- Insert test departments
INSERT INTO departments (name, code, description, status) VALUES
('Information Technology', 'IT', 'IT Department', 'active'),
('Facilities', 'FAC', 'Facilities Management', 'active'),
('Administration', 'ADM', 'Administration', 'active');

-- Insert test equipment
INSERT INTO equipment (asset_tag, name, description, category_id, location, department_id, status) VALUES
('IT001', 'Dell Desktop Computer', 'Main office desktop', 1, 'Office 101', 1, 'operational'),
('FAC001', 'HVAC Unit A', 'Main building air conditioning', 5, 'Roof Level 1', 2, 'operational'),
('IT002', 'HP Printer', 'Network laser printer', 2, 'Office 102', 1, 'operational');
```

### 🔧 **Local Configuration Modifications**

#### **Disable Active Directory for Local Testing**
Create a local configuration override:

```php
// Create: /MCLS/config/local_config.php
<?php
// Local testing configuration - disable AD authentication

define('LOCAL_TESTING', true);
define('SKIP_AD_AUTH', true);

// Simple password for all test users (for local testing only)
define('LOCAL_TEST_PASSWORD', 'test123');

// Override AD settings for local testing
if (LOCAL_TESTING) {
    // Disable LDAP requirements
    define('AD_SERVER', '');
    define('AD_PORT', 389);
    define('AD_DOMAIN', 'LOCAL');
    define('AD_BASE_DN', '');
    define('AD_USERNAME', '');
    define('AD_PASSWORD', '');
}
?>
```

#### **Modified Login for Local Testing**
For local testing without Active Directory:

1. **Username**: Use any of the test usernames (`admin`, `manager`, `technician`, `user`)
2. **Password**: Use `test123` (or whatever you set in LOCAL_TEST_PASSWORD)

### 📱 **Testing Scenarios**

#### **1. Basic Functionality Test**
```
1. Navigate to: http://localhost/MCLS/
2. Login with: admin / test123
3. Verify dashboard loads
4. Check navigation menu
5. Test responsive design (resize browser)
```

#### **2. Maintenance Call Workflow Test**
```
1. Go to: http://localhost/MCLS/maintenance_calls/create.php
2. Create a new maintenance call
3. Navigate to: http://localhost/MCLS/maintenance_calls/index.php
4. Verify call appears in list
5. Test filters and search
6. Open call details
7. Test status updates
```

#### **3. User Role Testing**
```
Login as different users to test permissions:
- admin: Full access to all features
- manager: Management features + reports
- technician: Call management + equipment
- user: Basic call creation and viewing
```

#### **4. Responsive Design Testing**
```
Test different screen sizes:
- 1366x768 (14-inch laptop target)
- 1920x1080 (larger screens)
- Mobile sizes (768px and below)
```

### 🛠️ **Troubleshooting Local Setup**

#### **Common Issues and Solutions**

1. **"Page not found" errors**
   ```
   Problem: Apache mod_rewrite not enabled
   Solution: Enable mod_rewrite in XAMPP control panel
   ```

2. **Database connection errors**
   ```
   Problem: MySQL not running or wrong credentials
   Solution: Check XAMPP MySQL service and config.php settings
   ```

3. **LDAP errors (if not disabled)**
   ```
   Problem: php-ldap extension not installed
   Solution: Enable php_ldap in php.ini or disable AD auth for local testing
   ```

4. **Permission errors**
   ```
   Problem: File/folder permissions
   Solution: Ensure XAMPP has read/write access to MCLS folder
   ```

5. **Session issues**
   ```
   Problem: Session data not persisting
   Solution: Check PHP session configuration in php.ini
   ```

### 📊 **Testing Checklist**

#### **✅ Core Functionality**
- [ ] Login system works
- [ ] Dashboard displays correctly
- [ ] Navigation menu functions
- [ ] User permissions enforced
- [ ] Session management works
- [ ] Logout functions properly

#### **✅ Maintenance Calls**
- [ ] Create new call
- [ ] View call list with filters
- [ ] Search functionality
- [ ] Edit existing call
- [ ] Update call status
- [ ] Add comments
- [ ] Assign technicians
- [ ] File attachments (if implemented)

#### **✅ User Interface**
- [ ] Responsive design on 14-inch display
- [ ] Government color scheme appears correctly
- [ ] All buttons and forms functional
- [ ] Error messages display properly
- [ ] Success notifications work
- [ ] Loading states visible

#### **✅ Data Management**
- [ ] Database operations complete successfully
- [ ] Data validation works
- [ ] Audit trail records actions
- [ ] Export functions work
- [ ] Pagination functions correctly

#### **✅ Security Features**
- [ ] CSRF protection active
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] Session timeout works
- [ ] Access control enforced

### 🚀 **Performance Testing**

#### **Local Performance URLs**
```
Test these pages for load time and responsiveness:
- http://localhost/MCLS/dashboard.php
- http://localhost/MCLS/maintenance_calls/index.php?search=test
- http://localhost/MCLS/reports/statistics.php
```

#### **Browser Developer Tools**
Use F12 developer tools to check:
- Network tab for load times
- Console for JavaScript errors
- Application tab for session data
- Performance tab for rendering issues

### 📝 **Test Data Generation**

You can use this SQL to generate sample data for testing:

```sql
-- Generate sample maintenance calls
INSERT INTO maintenance_calls (call_number, title, description, reported_by, priority_id, status, reported_date) 
SELECT 
    CONCAT('MC', DATE_FORMAT(NOW(), '%Y%m'), LPAD(ROW_NUMBER() OVER(), 4, '0')),
    CONCAT('Test Call ', ROW_NUMBER() OVER()),
    'This is a test maintenance call for system testing purposes.',
    1, -- reported_by (admin user)
    FLOOR(1 + RAND() * 4), -- random priority
    ELT(FLOOR(1 + RAND() * 4), 'open', 'assigned', 'in_progress', 'resolved'), -- random status
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY) -- random date within last 30 days
FROM 
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t1,
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t2
LIMIT 25;
```

This comprehensive testing guide provides all the necessary links and instructions to thoroughly test your MCLS system in a local XAMPP environment!