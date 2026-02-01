<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/SessionManager.php';

$session = new SessionManager();
$current_user = $session->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <meta name="description" content="Maintenance Call Logging System for <?php echo APP_DEPARTMENT; ?>">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo '/MCLS/assets/css/styles.css'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo '/MCLS/assets/images/favicon.ico'; ?>" type="image/x-icon">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-brand">
                <button class="sidebar-toggle btn btn-outline" style="margin-right: 16px;">☰</button>
                <span>🌿</span>
                <div>
                    <div style="font-size: 1.2rem; font-weight: 600;"><?php echo APP_NAME; ?></div>
                    <div style="font-size: 0.8rem; opacity: 0.9;"><?php echo APP_DEPARTMENT; ?></div>
                </div>
            </div>
            
            <nav class="header-nav">
                <!-- Session timeout indicator -->
                <div id="session-indicator" class="session-timer" style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                    <span id="session-time"></span>
                </div>
                
                <!-- User menu -->
                <div class="header-user user-menu-button" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['full_name'] ?? 'U', 0, 2)); ?>
                    </div>
                    <div class="user-info">
                        <div style="font-size: 0.9rem; font-weight: 500;">
                            <?php echo htmlspecialchars($current_user['full_name'] ?? 'User'); ?>
                        </div>
                        <div style="font-size: 0.7rem; opacity: 0.8;">
                            <?php echo ucfirst($current_user['role'] ?? 'user'); ?>
                        </div>
                    </div>
                    <span style="margin-left: 8px;">▼</span>
                </div>
                
                <!-- User dropdown menu -->
                <div id="user-menu" class="user-dropdown" style="display: none;">
                    <a href="/MCLS/profile.php">👤 My Profile</a>
                    <a href="/MCLS/settings.php">⚙️ Settings</a>
                    <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(255,255,255,0.2);">
                    <a href="/MCLS/logout.php">🚪 Logout</a>
                </div>
            </nav>
        </header>
        
        <!-- Sidebar -->
        <aside class="app-sidebar">
            <nav>
                <ul class="sidebar-nav">
                    <li class="nav-item">
                        <a href="/MCLS/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">🏠</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <?php if ($session->hasRole('coordinator')): ?>
                    <li class="nav-item">
                        <a href="/MCLS/regional_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'regional_dashboard.php' ? 'active' : ''; ?>">
                            <span class="nav-icon">🗺️</span>
                            <span class="nav-text">Regional Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a href="/MCLS/maintenance_calls/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'maintenance_calls') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">📋</span>
                            <span class="nav-text">Maintenance Calls</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/work_orders/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'work_orders') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">🔧</span>
                            <span class="nav-text">Work Orders</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/equipment/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'equipment') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">⚙️</span>
                            <span class="nav-text">Equipment</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/reports/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">📊</span>
                            <span class="nav-text">Reports</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/help/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'help') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">📚</span>
                            <span class="nav-text">User Manual</span>
                        </a>
                    </li>
                    
                    <?php if ($session->hasRole('manager')): ?>
                    <li class="nav-item">
                        <a href="/MCLS/departments/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'departments') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">🏢</span>
                            <span class="nav-text">Departments</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($session->hasRole('admin')): ?>
                    <li class="nav-item">
                        <a href="/MCLS/admin/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : ''; ?>">
                            <span class="nav-icon">👥</span>
                            <span class="nav-text">Administration</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/admin/users.php" class="nav-link">
                            <span class="nav-icon">👤</span>
                            <span class="nav-text">User Management</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/admin/regional_coordinators.php" class="nav-link">
                            <span class="nav-icon">📧</span>
                            <span class="nav-text">Regional Coordinators</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="/MCLS/admin/audit.php" class="nav-link">
                            <span class="nav-icon">📜</span>
                            <span class="nav-text">Audit Log</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
        
        <div class="app-content">
            <!-- Main content area -->
            <main class="app-main" id="main-content">
                <?php
                // Display flash messages
                if (isset($_SESSION['flash_message'])) {
                    $flash = $_SESSION['flash_message'];
                    echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['message']) . '</div>';
                    unset($_SESSION['flash_message']);
                }
                ?>
                
                <!-- Page content will be inserted here -->

<style>
.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--charcoal);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    z-index: 1001;
    margin-top: 8px;
    overflow: hidden;
}

.user-dropdown a {
    display: block;
    padding: var(--spacing-md);
    color: var(--pure-white);
    text-decoration: none;
    transition: var(--transition-fast);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.user-dropdown a:hover {
    background: var(--primary-green);
}

.user-dropdown a:last-child {
    border-bottom: none;
}

.header-user {
    position: relative;
    cursor: pointer;
}

#session-indicator {
    font-family: var(--font-family-mono);
}

@media (max-width: 768px) {
    .header-brand {
        flex: 1;
    }
    
    .header-nav {
        gap: var(--spacing-sm);
    }
    
    .user-info {
        display: none;
    }
}
</style>

<script>
// User menu toggle
function toggleUserMenu() {
    const menu = document.getElementById('user-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Close user menu when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('user-menu');
    const headerUser = document.querySelector('.user-menu-button');
    
    if (headerUser && !headerUser.contains(e.target)) {
        userMenu.style.display = 'none';
    }
});

// Session timeout indicator
function updateSessionIndicator() {
    fetch('/MCLS/api/session-check.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const indicator = document.getElementById('session-time');
        if (data.authenticated) {
            const minutes = Math.floor(data.timeRemaining / 60);
            const seconds = data.timeRemaining % 60;
            indicator.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (data.timeRemaining < 300) { // 5 minutes
                indicator.style.color = '#FF8000';
            } else if (data.timeRemaining < 120) { // 2 minutes
                indicator.style.color = '#FF0000';
            } else {
                indicator.style.color = '#FFFFFF';
            }
        }
    })
    .catch(error => {
        console.error('Session check failed:', error);
    });
}

// Update session indicator every 30 seconds
setInterval(updateSessionIndicator, 30000);
updateSessionIndicator();
</script>