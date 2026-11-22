<?php
/**
 * EventFlow — SCR12: Unified Event Console (Redirect to Workspace Console Block)
 * Giữ vững kiến trúc Single Workspace Shell với Midnight Navy Left Sidebar.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';

require_login();

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_GET['event_id'] ?? 1);
header("Location: " . base_url("app/modules/dashboard/index.php?event_id={$eventId}#event-console"));
exit;
