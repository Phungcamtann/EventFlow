<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_role('Club Manager');

$pdo       = get_db_connection();
$h         = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$manageUrl = base_url('app/modules/events/manage.php');

$club = event_manager_club($pdo, (int)current_user_id());
if (!$club) {
    set_flash('danger', 'Tài khoản của bạn chưa phụ trách CLB nào.');
    header('Location: ' . base_url('index.php'));
    exit;
}
$clubId = (int)$club['ClubID'];

