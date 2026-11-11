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

// ---------------------------------------------------------------------------
// POST: điểm danh / bỏ điểm danh (Post/Redirect/Get)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evt = att_load_event($pdo, $eventId, $clubIds);
    if (!$evt) {
        set_flash('danger', 'Không tìm thấy sự kiện hoặc bạn không thuộc CLB tổ chức sự kiện này.');
        header('Location: ' . $pageUrl);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $regId  = (int)($_POST['reg_id'] ?? 0);
    $last   = '';

    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif ($action === 'checkin') {
        [$ok, $msg] = att_checkin($pdo, $eventId, $regId, (int)current_user_id());
        set_flash($ok ? 'success' : 'danger', $msg);
        $last = $ok ? '&last=' . $regId : '';
    } elseif ($action === 'undo') {
        [$ok, $msg] = att_undo($pdo, $eventId, $regId);
        set_flash($ok ? 'success' : 'danger', $msg);
    } else {
        set_flash('danger', 'Thao tác không hợp lệ.');
    }

    header('Location: ' . $eventUrl . $last);
    exit;
}

// ---------------------------------------------------------------------------
// GET 1: chưa chọn sự kiện → danh sách sự kiện của CLB
// ---------------------------------------------------------------------------
if ($eventId === 0) {
    $in = implode(',', array_map('intval', $clubIds));
    $events = $pdo->query("
        SELECT e.EventID, e.EventName, e.StartTime, e.Location, e.Status,
               (SELECT COUNT(*) FROM Event_Registration r
                 WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
        FROM Event e
        WHERE e.ClubID IN ({$in}) AND e.Status <> 'Draft'
        ORDER BY e.StartTime DESC
    ")->fetchAll();

    $pageTitle = 'Điểm danh — EventFlow';
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="container py-4">
        <div class="mb-4">
            <h1 class="h3 fw-bold text-dark mb-1">Điểm danh sự kiện</h1>
            <p class="text-muted small mb-0">Chọn sự kiện để điểm danh người tham gia.</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <?php if (empty($events)): ?>
                <div class="p-5 text-center text-muted">Chưa có sự kiện nào của CLB.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Sự kiện</th>
                                <th>Thời gian</th>
                                <th>Đã đăng ký</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $e): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-dark"><?= $h($e['EventName']) ?></td>
                                <td class="small text-nowrap"><?= $h(event_fmt_dt($e['StartTime'])) ?></td>
                                <td class="font-mono small"><?= (int)$e['RegisteredCount'] ?></td>
                                <td class="small"><?= $e['Status'] === 'Cancelled' ? 'Đã hủy' : 'Đang công bố' ?></td>
                                <td class="text-end pe-4">
                                    <?php if ($e['Status'] !== 'Cancelled'): ?>
                                        <a href="<?= $h($pageUrl . '?event_id=' . (int)$e['EventID']) ?>" class="btn btn-sm btn-primary">Điểm danh</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// GET 2: điểm danh một sự kiện
