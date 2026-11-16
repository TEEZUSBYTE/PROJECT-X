<?php
// logger.php
require_once 'db.php';

function logActivity($pdo, $userId, $actionType, $details) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $sql = "INSERT INTO audit_logs (user_id, action_type, ip_address, details, timestamp) 
            VALUES (:user_id, :action_type, :ip_address, :details, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id'     => $userId,
        'action_type' => $actionType,
        'ip_address'  => $ipAddress,
        'details'     => $details
    ]);
}
?>