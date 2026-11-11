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

/**
 * Hủy sự kiện: chỉ đổi Event.Status → 'Cancelled'.
 * KHÔNG xóa / sửa Event_Registration hay Attendance (bảo toàn dữ liệu đăng ký — FR09).
 * Quy tắc tạm: chỉ hủy sự kiện đã công bố và chưa kết thúc.
 */
function event_cancel(PDO $pdo, int $eventId, int $clubId): array {
    $fail = static function (string $msg) use ($pdo): array {
        $pdo->rollBack();
        return [false, $msg];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id AND ClubID = :c FOR UPDATE");
        $stmt->execute([':id' => $eventId, ':c' => $clubId]);
        $evt = $stmt->fetch();

        if (!$evt) {
            return $fail('Không tìm thấy sự kiện.');
        }
        if ($evt['Status'] === 'Cancelled') {
            return $fail('Sự kiện này đã được hủy trước đó.');
        }
        if ($evt['Status'] !== 'Published') {
            return $fail('Chỉ hủy được sự kiện đã công bố.');
        }
        if (event_now($pdo) >= $evt['EndTime']) {
            return $fail('Sự kiện đã kết thúc, không thể hủy.');
        }

        $up = $pdo->prepare("UPDATE Event SET Status = 'Cancelled' WHERE EventID = :id");
        $up->execute([':id' => $eventId]);

        $pdo->commit();
        return [true, 'Đã hủy sự kiện. Dữ liệu đăng ký được giữ nguyên.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] cancel event failed: ' . $e->getMessage());
        return [false, 'Không thể xử lý yêu cầu. Vui lòng thử lại.'];
    }
}

