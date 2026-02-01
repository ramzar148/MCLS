<?php
/**
 * Simple Authentication Override for Local Testing
 * This file modifies the authentication to work without Active Directory
 */

if (!defined('LOCAL_TESTING')) {
    return; // Only load if local testing is enabled
}

class LocalAuthOverride {
    private $test_users = [
        'admin' => [
            'id' => 1,
            'username' => 'admin',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@test.local',
            'role' => 'admin',
            'department_id' => 1
        ],
        'manager' => [
            'id' => 2,
            'username' => 'manager',
            'first_name' => 'Test',
            'last_name' => 'Manager',
            'email' => 'manager@test.local',
            'role' => 'manager',
            'department_id' => 1
        ],
        'technician' => [
            'id' => 3,
            'username' => 'technician',
            'first_name' => 'Test',
            'last_name' => 'Technician',
            'email' => 'technician@test.local',
            'role' => 'technician',
            'department_id' => 2
        ],
        'user' => [
            'id' => 4,
            'username' => 'user',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@test.local',
            'role' => 'user',
            'department_id' => 3
        ]
    ];
    
    public function authenticate($username, $password) {
        // For local testing, accept any user with the test password
        if ($password === LOCAL_TEST_PASSWORD && isset($this->test_users[$username])) {
            return $this->test_users[$username];
        }
        
        return false;
    }
    
    public function getUserInfo($username) {
        return $this->test_users[$username] ?? false;
    }
}

// Override the ActiveDirectoryAuth class for local testing
if (defined('LOCAL_TESTING') && LOCAL_TESTING) {
    class ActiveDirectoryAuth extends LocalAuthOverride {
        // Inherit all methods from LocalAuthOverride
    }
}
?>