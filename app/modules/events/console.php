<?php
/**
 * EventFlow — SCR12: Unified Event Console (Redirect to Workspace Console Block)
 * Giữ vững kiến trúc Single Workspace Shell với Midnight Navy Left Sidebar.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';

require_login();

// Chỉ Club Manager, Ban Điều Hành ủy quyền hoặc Admin mới có quyền truy cập Console
if (!is_club_manager() && !is_authorized_club_member() && !is_system_admin()) {
    set_flash('danger', 'Chức năng Điều Hành Sự Kiện (Console) chỉ dành cho Ban Điều Hành CLB.');
    header('Location: ' . base_url('app/modules/events/my_events.php'));
    exit;
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_GET['event_id'] ?? 1);
header("Location: " . base_url("app/modules/dashboard/index.php?event_id={$eventId}#event-console"));
exit;
