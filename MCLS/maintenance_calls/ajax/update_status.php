<?php
require_once '../../bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$session = new SessionManager();
$session->requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$call_id = (int)($data['call_id'] ?? 0);
$status = $data['status'] ?? '';
$current_user = $session->getCurrentUser();

if (!$call_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate status
$valid_statuses = ['open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if call exists and user has permission
    $stmt = $pdo->prepare("SELECT id, assigned_to, status FROM maintenance_calls WHERE id = ?");
    $stmt->execute([$call_id]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$call) {
        echo json_encode(['success' => false, 'message' => 'Call not found']);
        exit;
    }
    
    // Check permissions
    if ($session->hasRole('technician') && $call['assigned_to'] != $current_user['id']) {
        echo json_encode(['success' => false, 'message' => 'You can only update calls assigned to you']);
        exit;
    }
    
    // Build update query
    $sql = "UPDATE maintenance_calls SET status = ?, updated_at = NOW()";
    $params = [$status];
    
    // If marking as resolved, set resolved_at
    if ($status === 'resolved') {
        $sql .= ", resolved_at = NOW()";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $call_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
