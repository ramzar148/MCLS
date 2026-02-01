<?php
/**
 * Active Directory Authentication Class
 * Handles LDAP authentication and user management
 */

if (!class_exists('ActiveDirectoryAuth')) {
class ActiveDirectoryAuth {
    private $ldap_connection;
    private $server;
    private $port;
    private $domain;
    private $base_dn;
    private $service_username;
    private $service_password;
    
    public function __construct() {
        $this->server = AD_SERVER;
        $this->port = AD_PORT;
        $this->domain = AD_DOMAIN;
        $this->base_dn = AD_BASE_DN;
        $this->service_username = AD_USERNAME;
        $this->service_password = AD_PASSWORD;
    }
    
    /**
     * Connect to Active Directory
     * @return bool
     */
    private function connect() {
        try {
            $this->ldap_connection = ldap_connect($this->server, $this->port);
            
            if (!$this->ldap_connection) {
                throw new Exception("Could not connect to LDAP server");
            }
            
            // Set LDAP options
            ldap_set_option($this->ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->ldap_connection, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($this->ldap_connection, LDAP_OPT_NETWORK_TIMEOUT, 10);
            
            return true;
            
        } catch (Exception $e) {
            error_log("LDAP Connection Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Authenticate user with Active Directory
     * @param string $username
     * @param string $password
     * @return array|false User data or false on failure
     */
    public function authenticate($username, $password) {
        error_log("AUTH: Starting authentication for user: $username");
        error_log("AUTH: LOCAL_TESTING defined: " . (defined('LOCAL_TESTING') ? 'YES' : 'NO'));
        error_log("AUTH: LOCAL_TESTING value: " . (defined('LOCAL_TESTING') ? (LOCAL_TESTING ? 'TRUE' : 'FALSE') : 'UNDEFINED'));
        error_log("AUTH: SKIP_AD_AUTH defined: " . (defined('SKIP_AD_AUTH') ? 'YES' : 'NO'));
        error_log("AUTH: SKIP_AD_AUTH value: " . (defined('SKIP_AD_AUTH') ? (SKIP_AD_AUTH ? 'TRUE' : 'FALSE') : 'UNDEFINED'));
        
        // Local testing mode - bypass Active Directory
        if (defined('LOCAL_TESTING') && LOCAL_TESTING && defined('SKIP_AD_AUTH') && SKIP_AD_AUTH) {
            error_log("AUTH: Using local authentication mode");
            return $this->authenticateLocal($username, $password);
        }
        
        error_log("AUTH: Using Active Directory authentication mode");
        
        if (!$this->connect()) {
            return false;
        }
        
        try {
            // Try to bind with user credentials
            $user_dn = $username . '@' . $this->domain;
            $bind = ldap_bind($this->ldap_connection, $user_dn, $password);
            
            if (!$bind) {
                error_log("LDAP Authentication failed for user: " . $username);
                return false;
            }
            
            // Get user information
            $user_info = $this->getUserInfo($username);
            
            if ($user_info) {
                // Update or create user in local database
                $this->syncUserToDatabase($user_info);
                
                // Log successful authentication
                $this->logAuditEvent($user_info['username'], 'LOGIN_SUCCESS');
                
                return $user_info;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("LDAP Authentication Error: " . $e->getMessage());
            return false;
        } finally {
            if ($this->ldap_connection) {
                ldap_close($this->ldap_connection);
            }
        }
    }
    
    /**
     * Local authentication for testing (bypasses Active Directory)
     * @param string $username
     * @param string $password
     * @return array|false
     */
    private function authenticateLocal($username, $password) {
        try {
            error_log("LOCAL AUTH: Starting authentication for user: $username");
            
            // Connect to local database using the Database class
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            $pdo = $database->getConnection();
            
            error_log("LOCAL AUTH: Database connection successful");
            
            // Check if user exists in local database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE ad_username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("LOCAL AUTH: User query executed, found: " . ($user ? 'YES' : 'NO'));
            
            if ($user) {
                error_log("LOCAL AUTH: User found - " . $user['ad_username'] . " with role: " . $user['role']);
                
                // In local testing mode, accept any password for existing users
                if ($password === 'admin123' || strlen($password) > 0) {
                    error_log("LOCAL AUTH: Password accepted, returning user data");
                    
                    return [
                        'username' => $user['ad_username'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'department' => $user['department_id'],
                        'role' => $user['role'],
                        'employee_id' => $user['employee_id']
                    ];
                } else {
                    error_log("LOCAL AUTH: Password rejected (empty or invalid)");
                }
            } else {
                error_log("LOCAL AUTH: User not found, checking if should create default user");
                
                // For testing, create default users if they don't exist
                if (in_array($username, ['admin', 'manager', 'technician', 'user'])) {
                    error_log("LOCAL AUTH: Creating default user: $username");
                    
                    $role = $username === 'admin' ? 'admin' : $username;
                    $stmt = $pdo->prepare("INSERT IGNORE INTO users (ad_username, first_name, last_name, email, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $success = $stmt->execute([
                        $username,
                        ucfirst($username),
                        'User',
                        $username . '@test.local',
                        $role
                    ]);
                    
                    error_log("LOCAL AUTH: User creation " . ($success ? 'successful' : 'failed'));
                    
                    // Return user data
                    return [
                        'username' => $username,
                        'first_name' => ucfirst($username),
                        'last_name' => 'User',
                        'email' => $username . '@test.local',
                        'department' => 1,
                        'role' => $role,
                        'employee_id' => strtoupper($username) . '001'
                    ];
                }
            }
            
            error_log("LOCAL AUTH: Authentication failed - no valid user found");
            return false;
            
        } catch (Exception $e) {
            error_log("Local Authentication Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Public test method to call authenticateLocal for debugging
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public function testLocalAuth($username, $password) {
        return $this->authenticateLocal($username, $password);
    }
    
    /**
     * Get user information from Active Directory
     * @param string $username
     * @return array|false
     */
    public function getUserInfo($username) {
        if (!$this->connect()) {
            return false;
        }
        
        try {
            // Bind with service account
            $bind = ldap_bind($this->ldap_connection, $this->service_username, $this->service_password);
            
            if (!$bind) {
                throw new Exception("Service account bind failed");
            }
            
            // Search for user
            $search_filter = "(&(objectClass=person)(sAMAccountName={$username}))";
            $attributes = [
                'sAMAccountName',
                'displayName',
                'givenName',
                'sn',
                'mail',
                'telephoneNumber',
                'department',
                'title',
                'employeeID',
                'memberOf',
                'whenCreated',
                'lastLogon'
            ];
            
            $search = ldap_search($this->ldap_connection, $this->base_dn, $search_filter, $attributes);
            
            if (!$search) {
                throw new Exception("LDAP search failed");
            }
            
            $entries = ldap_get_entries($this->ldap_connection, $search);
            
            if ($entries['count'] == 0) {
                return false;
            }
            
            $user = $entries[0];
            
            // Extract user information
            $user_info = [
                'username' => $this->getLdapValue($user, 'samaccountname'),
                'display_name' => $this->getLdapValue($user, 'displayname'),
                'first_name' => $this->getLdapValue($user, 'givenname'),
                'last_name' => $this->getLdapValue($user, 'sn'),
                'email' => $this->getLdapValue($user, 'mail'),
                'phone' => $this->getLdapValue($user, 'telephonenumber'),
                'department' => $this->getLdapValue($user, 'department'),
                'title' => $this->getLdapValue($user, 'title'),
                'employee_id' => $this->getLdapValue($user, 'employeeid'),
                'groups' => $this->extractGroups($user),
                'ad_created' => $this->convertAdTimestamp($this->getLdapValue($user, 'whencreated')),
                'last_logon' => $this->convertAdTimestamp($this->getLdapValue($user, 'lastlogon'))
            ];
            
            return $user_info;
            
        } catch (Exception $e) {
            error_log("LDAP User Info Error: " . $e->getMessage());
            return false;
        } finally {
            if ($this->ldap_connection) {
                ldap_close($this->ldap_connection);
            }
        }
    }
    
    /**
     * Get LDAP attribute value safely
     * @param array $entry
     * @param string $attribute
     * @return string
     */
    private function getLdapValue($entry, $attribute) {
        $attribute = strtolower($attribute);
        return isset($entry[$attribute][0]) ? $entry[$attribute][0] : '';
    }
    
    /**
     * Extract group memberships
     * @param array $user
     * @return array
     */
    private function extractGroups($user) {
        $groups = [];
        
        if (isset($user['memberof'])) {
            for ($i = 0; $i < $user['memberof']['count']; $i++) {
                $group_dn = $user['memberof'][$i];
                // Extract CN from DN
                if (preg_match('/CN=([^,]+)/', $group_dn, $matches)) {
                    $groups[] = $matches[1];
                }
            }
        }
        
        return $groups;
    }
    
    /**
     * Convert AD timestamp to MySQL datetime
     * @param string $ad_timestamp
     * @return string|null
     */
    private function convertAdTimestamp($ad_timestamp) {
        if (empty($ad_timestamp)) {
            return null;
        }
        
        // AD timestamps are in format YYYYMMDDHHMMSS.0Z
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $ad_timestamp, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . 
                   $matches[4] . ':' . $matches[5] . ':' . $matches[6];
        }
        
        return null;
    }
    
    /**
     * Sync user information to local database
     * @param array $user_info
     * @return bool
     */
    private function syncUserToDatabase($user_info) {
        try {
            $db = new Database();
            
            // Check if user exists
            $stmt = $db->execute(
                "SELECT id, role FROM users WHERE ad_username = ?",
                [$user_info['username']]
            );
            
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                // Update existing user
                $db->execute(
                    "UPDATE users SET 
                        first_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        last_login = NOW(),
                        updated_at = NOW()
                    WHERE ad_username = ?",
                    [
                        $user_info['first_name'],
                        $user_info['last_name'],
                        $user_info['email'],
                        $user_info['phone'],
                        $user_info['username']
                    ]
                );
                
                $user_info['id'] = $existing_user['id'];
                $user_info['role'] = $existing_user['role'];
                
            } else {
                // Create new user
                $role = $this->determineUserRole($user_info['groups']);
                $department_id = $this->getDepartmentId($user_info['department']);
                
                $stmt = $db->execute(
                    "INSERT INTO users (
                        ad_username, employee_id, first_name, last_name, 
                        email, phone, department_id, role, last_login
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $user_info['username'],
                        $user_info['employee_id'],
                        $user_info['first_name'],
                        $user_info['last_name'],
                        $user_info['email'],
                        $user_info['phone'],
                        $department_id,
                        $role
                    ]
                );
                
                $user_info['id'] = $db->lastInsertId();
                $user_info['role'] = $role;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Database sync error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Determine user role based on AD groups
     * @param array $groups
     * @return string
     */
    private function determineUserRole($groups) {
        // Define role mapping based on AD groups
        $role_mapping = [
            'MCLS_Administrators' => 'admin',
            'MCLS_Managers' => 'manager',
            'MCLS_Technicians' => 'technician',
            'Domain Admins' => 'admin',
            'IT_Department' => 'technician'
        ];
        
        foreach ($groups as $group) {
            if (isset($role_mapping[$group])) {
                return $role_mapping[$group];
            }
        }
        
        return 'user'; // Default role
    }
    
    /**
     * Get department ID by name
     * @param string $department_name
     * @return int|null
     */
    private function getDepartmentId($department_name) {
        if (empty($department_name)) {
            return null;
        }
        
        try {
            $db = new Database();
            $stmt = $db->execute(
                "SELECT id FROM departments WHERE name LIKE ? LIMIT 1",
                ['%' . $department_name . '%']
            );
            
            $result = $stmt->fetch();
            return $result ? $result['id'] : null;
            
        } catch (Exception $e) {
            error_log("Department lookup error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log audit event
     * @param string $username
     * @param string $action
     * @param array $details
     */
    private function logAuditEvent($username, $action, $details = []) {
        try {
            $db = new Database();
            
            // Get user ID
            $stmt = $db->execute(
                "SELECT id FROM users WHERE ad_username = ?",
                [$username]
            );
            $user = $stmt->fetch();
            $user_id = $user ? $user['id'] : null;
            
            $db->execute(
                "INSERT INTO audit_log (
                    user_id, table_name, action, new_values, 
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $user_id,
                    'authentication',
                    $action,
                    json_encode($details),
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
            
        } catch (Exception $e) {
            error_log("Audit logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user has required role
     * @param string $required_role
     * @param string $user_role
     * @return bool
     */
    public function hasRole($required_role, $user_role) {
        $role_hierarchy = [
            'user' => 1,
            'technician' => 2,
            'manager' => 3,
            'admin' => 4
        ];
        
        $required_level = $role_hierarchy[$required_role] ?? 0;
        $user_level = $role_hierarchy[$user_role] ?? 0;
        
        return $user_level >= $required_level;
    }
    
    /**
     * Get all AD groups for user management
     * @return array
     */
    public function getAllGroups() {
        if (!$this->connect()) {
            return [];
        }
        
        try {
            $bind = ldap_bind($this->ldap_connection, $this->service_username, $this->service_password);
            
            if (!$bind) {
                return [];
            }
            
            $search_filter = "(objectClass=group)";
            $attributes = ['cn', 'description'];
            
            $search = ldap_search($this->ldap_connection, $this->base_dn, $search_filter, $attributes);
            $entries = ldap_get_entries($this->ldap_connection, $search);
            
            $groups = [];
            for ($i = 0; $i < $entries['count']; $i++) {
                $groups[] = [
                    'name' => $this->getLdapValue($entries[$i], 'cn'),
                    'description' => $this->getLdapValue($entries[$i], 'description')
                ];
            }
            
            return $groups;
            
        } catch (Exception $e) {
            error_log("Group enumeration error: " . $e->getMessage());
            return [];
        } finally {
            if ($this->ldap_connection) {
                ldap_close($this->ldap_connection);
            }
        }
    }
}
} // End class_exists check
?>