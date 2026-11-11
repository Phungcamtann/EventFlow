<?php
/**
 * EventFlow — Điểm danh người tham gia (gộp danh sách đăng ký và điểm danh)
 * Phân quyền: Club Manager hoặc thành viên CLB được ủy quyền (Board Member / Event Supporter, Active).
 *
 * - Không có ?event_id: chọn sự kiện thuộc CLB của bạn.
 * - Có ?event_id=N: danh sách người đăng ký, lọc nhanh theo họ tên hoặc MSSV,
 *   bấm "Điểm danh" hoặc "Bỏ điểm danh" (chỉ khi sự kiện chưa kết thúc).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';
require_once __DIR__ . '/_scope.php';

require_authorized_club_member();

$pdo      = get_db_connection();
$h        = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$clubIds  = att_allowed_club_ids($pdo);
$pageUrl  = base_url('app/modules/attendance/checkin.php');
$eventId  = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)($_POST['event_id'] ?? 0);
$eventUrl = $pageUrl . '?event_id=' . $eventId;

if ($clubIds === []) {
    render_forbidden_page('Tài khoản của bạn chưa được gán cho CLB nào để điểm danh.');
}

/**
 * Điểm danh một người. Trả về [bool, thông báo].
 * Khóa dòng đăng ký (FOR UPDATE); Attendance.RegID là UNIQUE nên chặn trùng ở tầng DB.
 */
