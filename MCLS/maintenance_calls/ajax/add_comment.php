<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

$current_user = $session->getCurrentUser();

try {
    $call_id = (int)($_POST['call_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if (!$call_id || !$comment) {
        throw new Exception('Invalid request');
    }
    
    // Verify call exists
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("SELECT id, status FROM maintenance_calls WHERE id = ?");
    $stmt->execute([$call_id]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        throw new Exception('Call not found');
    }
    
    if ($call['status'] === 'closed' || $call['status'] === 'cancelled') {
        throw new Exception('Cannot comment on closed/cancelled calls');
    }
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO call_comments (call_id, user_id, comment, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$call_id, $current_user['id'], $comment]);
    
    // Get the inserted comment with user info
    $comment_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT cc.*, CONCAT(u.first_name, ' ', u.last_name) as commenter_name, u.role
        FROM call_comments cc
        LEFT JOIN users u ON cc.user_id = u.id
        WHERE cc.id = ?
    ");
    $stmt->execute([$comment_id]);
    $new_comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Comment added successfully',
        'comment' => $new_comment
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
