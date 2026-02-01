<?php
require_once '../bootstrap.php';
require_once '../config/database.php';
require_once '../classes/MaintenanceCall.php';

$session = new SessionManager();
$session->requireAuth();

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get filters from URL
    $filters = [
        'status' => $_GET['status'] ?? '',
        'priority' => $_GET['priority'] ?? '',
        'department' => $_GET['department'] ?? '',
        'assigned' => $_GET['assigned'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Get maintenance calls
    $maintenanceCall = new MaintenanceCall($pdo);
    $calls = $maintenanceCall->getAll($filters);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=maintenance_calls_' . date('Y-m-d') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add column headers
    fputcsv($output, [
        'Call Number',
        'Title',
        'Description',
        'Location',
        'Status',
        'Priority',
        'Department',
        'Equipment',
        'Asset Number',
        'Reported By',
        'Assigned To',
        'Reported Date',
        'Resolved Date',
        'Days Open'
    ]);
    
    // Add data rows
    foreach ($calls as $call) {
        fputcsv($output, [
            $call['call_number'],
            $call['title'],
            $call['description'],
            $call['location'],
            ucfirst($call['status']),
            $call['priority_name'] ?? '',
            $call['department_name'] ?? '',
            $call['equipment_name'] ?? '',
            $call['asset_number'] ?? '',
            $call['reported_by_name'],
            $call['assigned_to_name'] ?? 'Unassigned',
            date('Y-m-d H:i', strtotime($call['reported_date'])),
            $call['resolved_at'] ? date('Y-m-d H:i', strtotime($call['resolved_at'])) : '',
            $call['days_open']
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    die('Export error: ' . $e->getMessage());
}
