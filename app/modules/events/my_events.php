<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_login();

$pdo    = get_db_connection();
$h      = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$userId = (int)current_user_id();
$now    = event_now($pdo);

$stmt = $pdo->prepare("
    SELECT r.RegID, r.Status AS RegStatus, r.RegDate,
           e.EventID, e.EventName, e.Location, e.StartTime, e.EndTime, e.Status AS EventStatus,
           c.ClubName,
           a.AttendanceStatus
    FROM Event_Registration r
    JOIN Event e ON e.EventID = r.EventID
    LEFT JOIN Club c ON c.ClubID = e.ClubID
    LEFT JOIN Attendance a ON a.RegID = r.RegID
    WHERE r.AccountID = :uid
    ORDER BY e.StartTime DESC
");
$stmt->execute([':uid' => $userId]);
$rows = $stmt->fetchAll();

