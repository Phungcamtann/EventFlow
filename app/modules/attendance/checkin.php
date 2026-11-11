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
function att_checkin(PDO $pdo, int $eventId, int $regId, int $staffId): array {
    $rollback = static function (string $msg) use ($pdo): array {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, $msg];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT r.RegID, r.Status AS RegStatus, e.Status AS EventStatus, e.EndTime
            FROM Event_Registration r
            JOIN Event e ON e.EventID = r.EventID
            WHERE r.RegID = :r AND r.EventID = :e
            FOR UPDATE
        ");
        $stmt->execute([':r' => $regId, ':e' => $eventId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $rollback('Không tìm thấy đăng ký của sự kiện này.');
        }
        if ($row['RegStatus'] !== 'Registered') {
            return $rollback('Người này đã hủy đăng ký nên không thể điểm danh.');
        }
        if ($row['EventStatus'] !== 'Published') {
            return $rollback('Sự kiện không ở trạng thái công bố.');
        }
        if (event_now($pdo) >= $row['EndTime']) {
            return $rollback('Sự kiện đã kết thúc, không thể điểm danh.');
        }

        $chk = $pdo->prepare("SELECT AttendanceID FROM Attendance WHERE RegID = :r LIMIT 1");
        $chk->execute([':r' => $regId]);
        if ($chk->fetch()) {
            return $rollback('Người này đã được điểm danh rồi.');
        }

        $ins = $pdo->prepare("INSERT INTO Attendance (RegID, AttendanceStatus, CheckedInBy) VALUES (:r, 1, :s)");
        $ins->execute([':r' => $regId, ':s' => $staffId]);

        $pdo->commit();
        return [true, 'Đã điểm danh thành công.'];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return [false, 'Người này đã được điểm danh rồi.'];
        }
        error_log('[EventFlow] check-in failed: ' . $e->getMessage());
        return [false, 'Không thể điểm danh. Vui lòng thử lại.'];
    }
}

/**
 * Bỏ điểm danh (khi bấm nhầm). Chỉ xóa dòng Attendance, không đụng đăng ký.
 * Chỉ làm được khi sự kiện chưa kết thúc.
 */
function att_undo(PDO $pdo, int $eventId, int $regId): array {
    $rollback = static function (string $msg) use ($pdo): array {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, $msg];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT att.AttendanceID, e.EndTime
            FROM Attendance att
            JOIN Event_Registration r ON r.RegID = att.RegID
            JOIN Event e ON e.EventID = r.EventID
            WHERE att.RegID = :r AND r.EventID = :e
            FOR UPDATE
        ");
        $stmt->execute([':r' => $regId, ':e' => $eventId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $rollback('Người này chưa được điểm danh.');
        }
        if (event_now($pdo) >= $row['EndTime']) {
            return $rollback('Sự kiện đã kết thúc, không thể bỏ điểm danh.');
        }

        $del = $pdo->prepare("DELETE FROM Attendance WHERE AttendanceID = :id");
        $del->execute([':id' => (int)$row['AttendanceID']]);

        $pdo->commit();
        return [true, 'Đã bỏ điểm danh.'];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] undo check-in failed: ' . $e->getMessage());
        return [false, 'Không thể bỏ điểm danh. Vui lòng thử lại.'];
    }
}

