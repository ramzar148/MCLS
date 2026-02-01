<?php
/**
 * MCLS Main Index File
 * Handles routing and authentication
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if config file exists
    if (!file_exists('config/config.php')) {
        die('Configuration file not found. Please run <a href="install_local.php">install_local.php</a> first.');
    }
    
    require_once 'bootstrap.php';
    
    // Check if required classes exist
    if (!file_exists('config/database.php')) {
        die('Database configuration not found. Please run the installer.');
    }
    
    require_once 'config/database.php';

    // Initialize session manager
    $session = new SessionManager();

    // Check if user is authenticated
    if (!$session->isAuthenticated()) {
        header('Location: login.php');
        exit;
    }

    // Get page parameter
    $page = $_GET['page'] ?? 'dashboard';

    // Route to appropriate page
    switch ($page) {
        case 'dashboard':
        case '':
            if (file_exists('dashboard.php')) {
                include 'dashboard.php';
            } else {
                echo '<h1>Welcome to MCLS</h1><p>Dashboard file not found. <a href="install_local.php">Run installer</a></p>';
            }
            break;
            
        case 'maintenance_calls':
            if (file_exists('maintenance_calls/index.php')) {
                include 'maintenance_calls/index.php';
            } else {
                echo '<h1>Maintenance Calls</h1><p>Module not found.</p>';
            }
            break;
            
        case 'equipment':
            if (file_exists('equipment/index.php')) {
                include 'equipment/index.php';
            } else {
                echo '<h1>Equipment</h1><p>Module not found.</p>';
            }
            break;
            
        case 'reports':
            if (file_exists('reports/index.php')) {
                include 'reports/index.php';
            } else {
                echo '<h1>Reports</h1><p>Module not found.</p>';
            }
            break;
            
        case 'admin':
            $session->requireAuth('admin');
            if (file_exists('admin/index.php')) {
                include 'admin/index.php';
            } else {
                echo '<h1>Admin</h1><p>Module not found.</p>';
            }
            break;
            
        default:
            http_response_code(404);
            echo '<h1>Page Not Found</h1><p>The requested page was not found.</p>';
            echo '<a href="dashboard.php">Return to Dashboard</a>';
            break;
    }
    
} catch (Exception $e) {
    echo '<h1>System Error</h1>';
    echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="diagnostic.php">Run System Diagnostic</a></p>';
    echo '<p><a href="install_local.php">Run Installer</a></p>';
}
?>