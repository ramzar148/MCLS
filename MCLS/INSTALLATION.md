# MCLS - Maintenance Call Logging System
## Installation and Setup Guide
### Department of Forestry, Fisheries and the Environment

## System Requirements

### Server Requirements
- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **PHP**: 7.4+ (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Extensions**: php-ldap, php-pdo, php-mysql, php-json, php-openssl
- **Memory**: Minimum 512MB RAM
- **Storage**: Minimum 2GB free space

### Active Directory Requirements
- Windows Server 2008 R2+ with Active Directory Domain Services
- LDAP service running on port 389 (or custom port)
- Service account with read permissions for user directory
- Network connectivity between web server and domain controller

### Client Requirements (14-inch Laptop Optimized)
- **Screen Resolution**: 1366x768 minimum (optimized for 14-inch displays)
- **Browser**: Chrome 80+, Firefox 75+, Edge 80+, Safari 13+
- **JavaScript**: Must be enabled
- **Cookies**: Must be enabled

## Installation Steps

### 1. Database Setup

```sql
-- Create database and user
CREATE DATABASE mcls_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mcls_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON mcls_db.* TO 'mcls_user'@'localhost';
FLUSH PRIVILEGES;

-- Import schema
SOURCE /path/to/MCLS/database/schema.sql;
```

### 2. Web Server Configuration

#### Apache Virtual Host Example
```apache
<VirtualHost *:443>
    ServerName mcls.yourdomain.local
    DocumentRoot /var/www/html/MCLS
    
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    
    <Directory /var/www/html/MCLS>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/mcls_error.log
    CustomLog ${APACHE_LOG_DIR}/mcls_access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName mcls.yourdomain.local
    Redirect permanent / https://mcls.yourdomain.local/
</VirtualHost>
```

### 3. Configuration Setup

#### Edit `/config/config.php`
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcls_db');
define('DB_USER', 'mcls_user');
define('DB_PASS', 'your_secure_password');

// Active Directory Configuration
define('AD_SERVER', 'ldap://your-dc.yourdomain.local');
define('AD_PORT', 389);
define('AD_DOMAIN', 'YOURDOMAIN');
define('AD_BASE_DN', 'DC=yourdomain,DC=local');
define('AD_USERNAME', 'service_account@yourdomain.local');
define('AD_PASSWORD', 'service_account_password');

// Security Configuration - CHANGE THESE!
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');
```

### 4. File Permissions

```bash
# Set correct ownership
chown -R www-data:www-data /var/www/html/MCLS

# Set directory permissions
find /var/www/html/MCLS -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/html/MCLS -type f -exec chmod 644 {} \;

# Create and secure log directory
mkdir -p /var/www/html/MCLS/logs
chmod 750 /var/www/html/MCLS/logs
chown www-data:www-data /var/www/html/MCLS/logs

# Create uploads directory (if needed)
mkdir -p /var/www/html/MCLS/uploads
chmod 755 /var/www/html/MCLS/uploads
chown www-data:www-data /var/www/html/MCLS/uploads
```

### 5. Active Directory Setup

#### Service Account Creation
```powershell
# Create service account in AD
New-ADUser -Name "MCLS Service Account" `
           -SamAccountName "svc-mcls" `
           -UserPrincipalName "svc-mcls@yourdomain.local" `
           -Description "Service account for MCLS application" `
           -PasswordNeverExpires $true `
           -CannotChangePassword $true `
           -AccountPassword (ConvertTo-SecureString "SecurePassword123!" -AsPlainText -Force) `
           -Enabled $true

# Add to appropriate groups if needed
Add-ADGroupMember -Identity "Domain Users" -Members "svc-mcls"
```

#### Security Groups (Optional)
```powershell
# Create MCLS-specific groups
New-ADGroup -Name "MCLS_Users" -GroupScope Global -GroupCategory Security -Description "MCLS System Users"
New-ADGroup -Name "MCLS_Technicians" -GroupScope Global -GroupCategory Security -Description "MCLS Technicians"
New-ADGroup -Name "MCLS_Managers" -GroupScope Global -GroupCategory Security -Description "MCLS Managers"
New-ADGroup -Name "MCLS_Administrators" -GroupScope Global -GroupCategory Security -Description "MCLS Administrators"
```

### 6. PHP Configuration

#### Required PHP Extensions
```ini
# php.ini additions
extension=ldap
extension=pdo
extension=pdo_mysql
extension=json
extension=openssl
extension=mbstring
extension=fileinfo

# Security settings
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Upload settings
upload_max_filesize = 5M
post_max_size = 5M
max_file_uploads = 10

# Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.gc_maxlifetime = 3600
```

### 7. SSL Certificate Setup

#### Self-Signed Certificate (Development)
```bash
openssl req -x509 -newkey rsa:4096 -keyout mcls-private.key -out mcls-certificate.crt -days 365 -nodes
```

#### Let's Encrypt (Production)
```bash
certbot --apache -d mcls.yourdomain.local
```

## Post-Installation Configuration

### 1. Initial Admin User

After installation, create the first admin user in the database:

```sql
-- Insert initial admin user (replace with actual AD username)
INSERT INTO users (
    ad_username, first_name, last_name, email, role, status
) VALUES (
    'admin.user', 'Admin', 'User', 'admin@yourdomain.local', 'admin', 'active'
);

-- Insert default departments
INSERT INTO departments (name, code, description, status) VALUES
('Information Technology', 'IT', 'IT Department', 'active'),
('Facilities Management', 'FM', 'Facilities and Maintenance', 'active'),
('Human Resources', 'HR', 'Human Resources', 'active'),
('Finance', 'FIN', 'Finance Department', 'active');
```

### 2. System Settings

Configure system settings through the admin panel or directly in database:

```sql
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('company_name', 'Department of Forestry, Fisheries and the Environment', 'Organization name'),
('company_logo', '/assets/images/logo.png', 'Path to company logo'),
('timezone', 'Africa/Johannesburg', 'System timezone'),
('date_format', 'Y-m-d', 'Default date format'),
('currency_symbol', 'R', 'Currency symbol'),
('notification_email', 'mcls-admin@yourdomain.local', 'System notification email'),
('backup_enabled', 'true', 'Enable automatic backups'),
('maintenance_mode', 'false', 'System maintenance mode');
```

### 3. Equipment Categories and Priorities

```sql
-- Additional equipment categories
INSERT INTO equipment_categories (name, description) VALUES
('Laboratory Equipment', 'Scientific and lab equipment'),
('Field Equipment', 'Equipment used in field operations'),
('Communication Systems', 'Radio, phone, and communication equipment'),
('Safety Equipment', 'Safety and emergency equipment');

-- Customize priority levels if needed
UPDATE priority_levels SET 
    description = 'Immediate response required - system down or safety hazard',
    response_time_hours = 1 
WHERE name = 'Critical';
```

## Testing the Installation

### 1. Basic Functionality Test
1. Navigate to `https://mcls.yourdomain.local`
2. Login with AD credentials
3. Create a test maintenance call
4. Verify dashboard statistics
5. Test user role permissions

### 2. Active Directory Integration Test
1. Test login with different user types
2. Verify user information synchronization
3. Test group membership mapping
4. Verify session management

### 3. Database Connectivity Test
```php
<?php
// test_db.php - Remove after testing
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $db = new Database();
    $stmt = $db->execute("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Database connection successful. User count: " . $result['count'];
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
```

## Maintenance and Monitoring

### 1. Log Files to Monitor
- `/var/log/apache2/mcls_error.log` - Web server errors
- `/MCLS/logs/app.log` - Application logs
- `/MCLS/logs/audit.log` - Audit trail
- `/var/log/mysql/error.log` - Database errors

### 2. Regular Maintenance Tasks
```bash
# Weekly log rotation
logrotate /etc/logrotate.d/mcls

# Monthly database optimization
mysql -u mcls_user -p mcls_db -e "OPTIMIZE TABLE maintenance_calls, users, audit_log;"

# Backup script (run daily)
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u mcls_user -p mcls_db > /backups/mcls_backup_$DATE.sql
gzip /backups/mcls_backup_$DATE.sql
find /backups -name "mcls_backup_*.sql.gz" -mtime +30 -delete
```

### 3. Security Monitoring
- Monitor failed login attempts in audit log
- Regular security updates for OS and PHP
- SSL certificate renewal
- Active Directory service account password rotation

## Troubleshooting

### Common Issues

#### LDAP Connection Issues
```bash
# Test LDAP connectivity
ldapsearch -x -H ldap://your-dc.local -D "service_account@domain.local" -W -b "dc=domain,dc=local"
```

#### Permission Issues
```bash
# Fix file permissions
chown -R www-data:www-data /var/www/html/MCLS
chmod -R 755 /var/www/html/MCLS
```

#### Database Connection Issues
```bash
# Test MySQL connectivity
mysql -u mcls_user -p -h localhost mcls_db
```

### Support Contacts
- **System Administrator**: IT Support Team
- **Application Support**: MCLS Development Team
- **Active Directory**: Network Administration
- **Database**: Database Administration Team

## Security Considerations

### 1. Network Security
- Use HTTPS only (no HTTP)
- Firewall rules restricting access
- VPN requirement for external access
- Network segmentation

### 2. Application Security
- Regular security updates
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF token validation

### 3. Data Protection
- Regular encrypted backups
- Data retention policies
- Access logging and monitoring
- Personal data protection compliance

### 4. User Access Control
- Role-based permissions
- Regular access reviews
- Account deactivation procedures
- Strong password policies

## Performance Optimization

### 1. Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_calls_status_date ON maintenance_calls(status, reported_date);
CREATE INDEX idx_calls_assigned_user ON maintenance_calls(assigned_to, status);
CREATE INDEX idx_audit_user_date ON audit_log(user_id, created_at);
```

### 2. Caching Configuration
```apache
# Enable compression
LoadModule deflate_module modules/mod_deflate.so

# Cache static assets
<LocationMatch "\.(css|js|png|jpg|jpeg|gif|ico)$">
    ExpiresActive On
    ExpiresDefault "access plus 1 month"
</LocationMatch>
```

### 3. PHP Optimization
```ini
# php.ini optimization
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

This completes the comprehensive installation and setup guide for the MCLS system. The system is now ready for deployment in a government environment with proper security, monitoring, and maintenance procedures.