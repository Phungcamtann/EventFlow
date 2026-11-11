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

$eventId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $eventId > 0;
$evt        = null;
$registered = 0;

if ($isEditMode) {
    $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id AND ClubID = :c LIMIT 1");
    $stmt->execute([':id' => $eventId, ':c' => $clubId]);
    $evt = $stmt->fetch();

    if (!$evt) {
        set_flash('danger', 'Không tìm thấy sự kiện cần chỉnh sửa.');
        header('Location: ' . $manageUrl);
        exit;
    }
    if ($evt['Status'] === 'Cancelled') {
        set_flash('warning', 'Sự kiện đã bị hủy nên không thể chỉnh sửa.');
        header('Location: ' . $manageUrl);
        exit;
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM Event_Registration WHERE EventID = :e AND Status = 'Registered'");
    $cnt->execute([':e' => $eventId]);
    $registered = (int)$cnt->fetchColumn();
}

// Sự kiện đã công bố thì không đưa ngược về bản nháp (tránh ẩn sự kiện đã có người đăng ký)
$canChooseStatus = !$isEditMode || $evt['Status'] === 'Draft';

$errors = [];
$form = [
    'EventName'            => $isEditMode ? $evt['EventName'] : '',
    'Description'          => $isEditMode ? (string)$evt['Description'] : '',
    'Location'             => $isEditMode ? $evt['Location'] : '',
    'StartTime'            => $isEditMode ? event_dt_to_input($evt['StartTime']) : '',
    'EndTime'              => $isEditMode ? event_dt_to_input($evt['EndTime']) : '',
    'RegistrationDeadline' => $isEditMode ? event_dt_to_input($evt['RegistrationDeadline']) : '',
    'MaxCapacity'          => ($isEditMode && $evt['MaxCapacity'] !== null) ? (string)$evt['MaxCapacity'] : '',
    'Status'               => $isEditMode ? $evt['Status'] : 'Published',
];

// ---------------------------------------------------------------------------
// Xử lý POST
// ---------------------------------------------------------------------------
