<?php
require_once 'bootstrap.php';
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "priority_levels table structure:\n";
$stmt = $pdo->query('DESCRIBE priority_levels');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}
