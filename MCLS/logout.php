<?php
require_once 'config/config.php';
require_once 'classes/SessionManager.php';

$session = new SessionManager();

// Clear session and redirect to login
$session->logout();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>