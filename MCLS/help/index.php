<?php
$page_title = 'User Manual';
require_once '../bootstrap.php';

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();
$user_role = $current_user['role'];

require_once '../includes/header.php';
?>

<div class="app-main">
    <div class="page-header">
        <h1>📚 User Manual</h1>
        <p>Help and guidance for using the Maintenance Call Logging System</p>
    </div>

    <div class="manual-content">
        <!-- Role-Specific Manual Content -->
        <?php if ($user_role === 'admin'): ?>
            <?php include 'manual_admin.php'; ?>
        <?php elseif ($user_role === 'manager'): ?>
            <?php include 'manual_manager.php'; ?>
        <?php elseif ($user_role === 'technician'): ?>
            <?php include 'manual_technician.php'; ?>
        <?php else: ?>
            <?php include 'manual_user.php'; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.manual-content {
    max-width: 900px;
}

.manual-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 24px;
    margin-bottom: 20px;
}

.manual-section h2 {
    color: var(--primary-color);
    font-size: 1.5rem;
    margin-bottom: 16px;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 8px;
}

.manual-section h3 {
    color: #495057;
    font-size: 1.2rem;
    margin-top: 20px;
    margin-bottom: 12px;
}

.manual-section p {
    line-height: 1.6;
    margin-bottom: 12px;
}

.manual-section ul, .manual-section ol {
    margin-left: 20px;
    margin-bottom: 16px;
}

.manual-section li {
    margin-bottom: 8px;
    line-height: 1.6;
}

.manual-section code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    color: #e83e8c;
}

.manual-section .note {
    background: #e7f3ff;
    border-left: 4px solid #0066cc;
    padding: 12px 16px;
    margin: 16px 0;
    border-radius: 4px;
}

.manual-section .warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px 16px;
    margin: 16px 0;
    border-radius: 4px;
}

.screenshot {
    max-width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin: 16px 0;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}

.quick-link {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 16px;
    text-align: center;
    text-decoration: none;
    color: #495057;
    transition: all 0.2s;
}

.quick-link:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.quick-link .icon {
    font-size: 2rem;
    margin-bottom: 8px;
}

.quick-link .label {
    font-weight: 600;
}
</style>

<?php require_once '../includes/footer.php'; ?>
