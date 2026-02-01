<?php
require_once 'bootstrap.php';
require_once 'config/database.php';

$session = new SessionManager();
$auth = new ActiveDirectoryAuth();
$error_message = '';
$success_message = '';
$redirect_url = $_GET['redirect'] ?? 'dashboard.php';

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success_message = 'You have been successfully logged out.';
}

// Check if user is already logged in
if ($session->isAuthenticated()) {
    header('Location: ' . $redirect_url);
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        // Check if we're in local testing mode
        if (defined('LOCAL_TESTING') && LOCAL_TESTING && defined('SKIP_AD_AUTH') && SKIP_AD_AUTH) {
            // Direct local authentication bypass
            try {
                $database = new Database();
                $pdo = $database->getConnection();
                
                // First, get user with department information
                $stmt = $pdo->prepare("
                    SELECT u.*, d.name as department_name, d.code as department_code
                    FROM users u 
                    LEFT JOIN departments d ON u.department_id = d.id 
                    WHERE u.ad_username = ? AND u.status = 'active'
                ");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Local testing mode - accept any non-empty password
                    $user_data = [
                        'id' => $user['id'],
                        'username' => $user['ad_username'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'department_id' => $user['department_id'],
                        'department_name' => $user['department_name'],
                        'role' => $user['role'],
                        'employee_id' => $user['employee_id']
                    ];
                    
                    // If coordinator, fetch coordinator data
                    if ($user['role'] === 'coordinator') {
                        $stmt = $pdo->prepare("
                            SELECT * FROM regional_coordinators 
                            WHERE user_id = ? AND status = 'active'
                        ");
                        $stmt->execute([$user['id']]);
                        $coordinator = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($coordinator) {
                            $user_data['coordinator_data'] = $coordinator;
                        }
                    }
                    
                    // Update last login time
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    if ($session->login($user_data)) {
                        // Simple redirect - all users go to dashboard, which handles role-specific content
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error_message = 'Login failed. Please try again.';
                    }
                } else {
                    // Create test user if it doesn't exist and is a valid test username
                    if (in_array($username, ['admin', 'manager', 'technician', 'user'])) {
                        // Get default department
                        $stmt = $pdo->prepare("SELECT id FROM departments WHERE code = 'IT' LIMIT 1");
                        $stmt->execute();
                        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
                        $dept_id = $dept ? $dept['id'] : 1;
                        
                        $role = $username === 'admin' ? 'admin' : $username;
                        $stmt = $pdo->prepare("
                            INSERT INTO users (ad_username, first_name, last_name, email, role, status, department_id, employee_id) 
                            VALUES (?, ?, ?, ?, ?, 'active', ?, ?)
                        ");
                        $success = $stmt->execute([
                            $username,
                            ucfirst($username),
                            'User',
                            $username . '@dffe.gov.za',
                            $role,
                            $dept_id,
                            strtoupper($username) . '001'
                        ]);
                        
                        if ($success) {
                            $error_message = 'User account created successfully! Please log in again.';
                        } else {
                            $error_message = 'Failed to create user account.';
                        }
                    } else {
                        $error_message = 'User not found. Use: admin, manager, technician, or user';
                    }
                }
            } catch (Exception $e) {
                $error_message = 'Database error: ' . $e->getMessage();
                error_log("Login database error: " . $e->getMessage());
            }
        } else {
            // Attempt Active Directory authentication
            $user_data = $auth->authenticate($username, $password);
            
            if ($user_data) {
                // Login successful
                if ($session->login($user_data)) {
                    header('Location: ' . $redirect_url);
                    exit;
                } else {
                    $error_message = 'Login failed. Please try again.';
                }
            } else {
                $error_message = 'Invalid username or password.';
                error_log("Failed login attempt for user: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--primary-green-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .login-container {
            background: var(--pure-white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xxl);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-lg);
            color: var(--pure-white);
            font-size: 2rem;
            font-weight: bold;
        }
        
        .login-title {
            color: var(--charcoal);
            margin-bottom: var(--spacing-sm);
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .login-subtitle {
            color: var(--medium-gray);
            margin-bottom: var(--spacing-xl);
            font-size: 0.9rem;
        }
        
        .login-form .form-group {
            margin-bottom: var(--spacing-lg);
            text-align: left;
        }
        
        .login-form .form-control {
            padding: var(--spacing-md);
            font-size: 1rem;
        }
        
        .login-btn {
            width: 100%;
            padding: var(--spacing-md);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: var(--spacing-lg);
        }
        
        .login-footer {
            color: var(--medium-gray);
            font-size: 0.8rem;
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-lg);
            border-top: 1px solid var(--light-gray);
        }
        
        .login-error {
            background: rgba(211, 47, 47, 0.1);
            border: 1px solid var(--status-critical);
            color: var(--status-critical);
            padding: var(--spacing-md);
            border-radius: var(--border-radius-md);
            margin-bottom: var(--spacing-lg);
            font-size: 0.9rem;
        }
        
        .domain-info {
            background: rgba(30, 107, 62, 0.1);
            border: 1px solid var(--primary-green);
            color: var(--primary-green);
            padding: var(--spacing-sm);
            border-radius: var(--border-radius-sm);
            margin-bottom: var(--spacing-lg);
            font-size: 0.8rem;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: var(--spacing-md);
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">🌿</div>
        
        <h1 class="login-title"><?php echo APP_NAME; ?></h1>
        <p class="login-subtitle"><?php echo APP_DEPARTMENT; ?></p>
        
        <?php if (!empty($success_message)): ?>
            <div class="login-success" style="background: rgba(76, 175, 80, 0.1); border: 1px solid #4caf50; color: #2e7d32; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="login-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="domain-info">
            <strong>Active Directory Login:</strong> Use your domain credentials (username only, without @domain)
        </div>
        
        <form method="POST" class="login-form" autocomplete="on">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary login-btn">
                <span>🔐</span> Login to System
            </button>
        </form>
        
        <div class="login-footer">
            <p><strong>System Information</strong></p>
            <p>Version <?php echo APP_VERSION; ?> | For authorized users only</p>
            <p>Contact IT Support for login issues</p>
        </div>
    </div>
    
    <script>
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Handle form submission with loading state
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const button = this.querySelector('.login-btn');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<span>⏳</span> Authenticating...';
            button.disabled = true;
            
            // Re-enable button if there's an error (form will reload)
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 5000);
        });
        
        // Clear error message on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', () => {
                const errorDiv = document.querySelector('.login-error');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>