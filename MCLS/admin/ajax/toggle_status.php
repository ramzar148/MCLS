<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

// Only admins can toggle user status
if (!$session->hasRole('admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Only administrators can update user status'
    ]);
    exit;
}

$current_user = $session->getCurrentUser();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = (int)($input['user_id'] ?? 0);
    $status = $input['status'] ?? '';
    
    if (!$user_id || !in_array($status, ['active', 'inactive'])) {
        throw new Exception('Invalid request');
    }
    
    // Prevent admin from deactivating themselves
    if ($user_id == $current_user['id']) {
        throw new Exception('Cannot deactivate your own account');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
