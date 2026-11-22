<?php
/**
 * EventFlow — SCR14: Điểm danh người tham gia (gộp danh sách đăng ký và điểm danh)
 * Đáp ứng: FR15, PB18, PB19, UC12
 * Phân quyền: Club Manager hoặc thành viên CLB được ủy quyền (Board Member / Event Supporter, Active).
 *
 * - Không có ?event_id: chọn sự kiện thuộc CLB của bạn.
 * - Có ?event_id=N: danh sách người đăng ký, lọc nhanh theo họ tên hoặc MSSV,
 *   bấm "Điểm danh" hoặc "Bỏ điểm danh" (chỉ khi sự kiện chưa kết thúc).
 * - Hỗ trợ Điểm danh bổ sung (Walk-in check-in) cho sinh viên chưa đăng ký trước.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';
require_once __DIR__ . '/_scope.php';

require_login();

$pdo      = get_db_connection();
$h        = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$clubIds  = att_allowed_club_ids($pdo);
$pageUrl  = base_url('app/modules/attendance/checkin.php');
$eventId  = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)($_POST['event_id'] ?? 0);
$eventUrl = $pageUrl . '?event_id=' . $eventId;
$userId   = (int)current_user_id();

// 1. Đối với tài khoản Ban Điều Hành: chuyển thẳng vào trang Quản lý Console, không cần trang điểm danh riêng lẻ
$isBDH = is_club_manager() || (isset($_SESSION['user']['MemberRole']) && in_array($_SESSION['user']['MemberRole'], ['President', 'Vice President', 'Board Member'], true));
if ($isBDH) {
    if ($eventId > 0) {
        header("Location: " . base_url("app/modules/dashboard/index.php?event_id={$eventId}&console_tab=c-tab-checkin#event-console"));
    } else {
        header("Location: " . base_url("app/modules/dashboard/index.php#events"));
    }
    exit;
}

// 2. Đối với các thành viên khác: CHỈ những thành viên được phân công vai trò Check-in Lead mới được mở bàn điểm danh
$leadEvtIds = user_checkin_lead_event_ids($pdo, $userId);
if ($eventId > 0) {
    if (!is_user_checkin_lead($pdo, $eventId, $userId)) {
        render_forbidden_page('Chức năng Bàn Điểm Danh chỉ dành cho thành viên được phân công vai trò Trưởng Bàn Điểm Danh (Check-in Lead) của sự kiện này.');
    }
} else {
    if (empty($leadEvtIds)) {
        render_forbidden_page('Bạn chưa được phân công vai trò Trưởng Bàn Điểm Danh (Check-in Lead) cho sự kiện nào của CLB.');
    }
    if (count($leadEvtIds) === 1) {
        header("Location: " . base_url("app/modules/attendance/checkin.php?event_id=" . $leadEvtIds[0]));
        exit;
    }
}

if ($clubIds === []) {
    render_forbidden_page('Tài khoản của bạn chưa được gán cho CLB nào để điểm danh.');
}

/**
 * Điểm danh một người có sẵn trong danh sách đăng ký. Trả về [bool, thông báo].
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

/**
 * Lấy số lượng thống kê điểm danh hiện tại của sự kiện (Total & Checked)
 */
if (!function_exists('att_event_stats')) {
    function att_event_stats(PDO $pdo, int $eventId): array {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS Total,
                SUM(CASE WHEN att.AttendanceID IS NOT NULL THEN 1 ELSE 0 END) AS Checked
            FROM Event_Registration r
            LEFT JOIN Attendance att ON att.RegID = r.RegID
            WHERE r.EventID = :e AND r.Status = 'Registered'
        ");
        $stmt->execute([':e' => $eventId]);
        $row = $stmt->fetch();
        return [
            'total'   => (int)($row['Total'] ?? 0),
            'checked' => (int)($row['Checked'] ?? 0)
        ];
    }
}

// ---------------------------------------------------------------------------
// POST: điểm danh / bỏ điểm danh / điểm danh bổ sung walk-in
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evt = att_load_event($pdo, $eventId, $clubIds);
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || isset($_POST['ajax'])
           || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!$evt) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện hoặc bạn không thuộc CLB tổ chức sự kiện này.']);
            exit;
        }
        set_flash('danger', 'Không tìm thấy sự kiện hoặc bạn không thuộc CLB tổ chức sự kiện này.');
        header('Location: ' . $pageUrl);
        exit;
    }

    if (!can_user_checkin_event($pdo, $eventId, (int)current_user_id())) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Bạn không thuộc ban tổ chức (Event Staff) của sự kiện này nên không có quyền điểm danh.']);
            exit;
        }
        render_forbidden_page('Bạn không được chỉ định trong Ban Tổ Chức (Event Staff) của sự kiện này nên không có quyền mở bàn điểm danh.');
    }

    $action = $_POST['action'] ?? '';
    $regId  = (int)($_POST['reg_id'] ?? 0);
    $last   = '';

    if (!verify_csrf_token()) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng tải lại trang.']);
            exit;
        }
        set_flash('danger', 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif ($action === 'checkin') {
        [$ok, $msg] = att_checkin($pdo, $eventId, $regId, (int)current_user_id());
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            $checkInAt = null;
            if ($ok) {
                $timeStmt = $pdo->prepare("SELECT CheckInAt FROM Attendance WHERE RegID = :r LIMIT 1");
                $timeStmt->execute([':r' => $regId]);
                $timeVal = $timeStmt->fetchColumn();
                $checkInAt = $timeVal ? event_fmt_dt($timeVal) : date('H:i d/m/Y');
            }
            $stats = att_event_stats($pdo, $eventId);
            echo json_encode([
                'success'       => $ok,
                'message'       => $msg,
                'action'        => 'checkin',
                'reg_id'        => $regId,
                'checked_in_at' => $checkInAt,
                'stats'         => $stats
            ]);
            exit;
        }
        set_flash($ok ? 'success' : 'danger', $msg);
        $last = $ok ? '&last=' . $regId : '';
    } elseif ($action === 'undo') {
        [$ok, $msg] = att_undo($pdo, $eventId, $regId);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            $stats = att_event_stats($pdo, $eventId);
            echo json_encode([
                'success' => $ok,
                'message' => $msg,
                'action'  => 'undo',
                'reg_id'  => $regId,
                'stats'   => $stats
            ]);
            exit;
        }
        set_flash($ok ? 'success' : 'danger', $msg);
    } elseif ($action === 'walkin_checkin') {
        $guestData = [
            'fullname'     => trim($_POST['guest_name'] ?? $_POST['fullname'] ?? ''),
            'student_code' => trim($_POST['guest_student_code'] ?? $_POST['student_code'] ?? ''),
            'email'        => trim($_POST['guest_email'] ?? $_POST['email'] ?? ''),
            'phone'        => trim($_POST['guest_phone'] ?? $_POST['phone'] ?? ''),
        ];
        [$ok, $msg, $newRegId, $attendeeInfo] = att_walkin_checkin($pdo, $eventId, $guestData, (int)current_user_id());
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            $stats = att_event_stats($pdo, $eventId);
            echo json_encode([
                'success' => $ok,
                'message' => $msg,
                'action'  => 'walkin_checkin',
                'reg_id'  => $newRegId,
                'user'    => $attendeeInfo,
                'stats'   => $stats
            ]);
            exit;
        }
        set_flash($ok ? 'success' : 'danger', $msg);
        $last = $ok && $newRegId > 0 ? '&last=' . $newRegId : '';
    } else {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            exit;
        }
        set_flash('danger', 'Thao tác không hợp lệ.');
    }

    header('Location: ' . $eventUrl . $last);
    exit;
}

// ---------------------------------------------------------------------------
// GET 1: chưa chọn sự kiện → danh sách sự kiện mà người này là Check-in Lead
// ---------------------------------------------------------------------------
if ($eventId === 0) {
    $now = event_now($pdo);
    $in = implode(',', array_map('intval', $leadEvtIds));
    $events = $pdo->query("
        SELECT e.*,
               (SELECT COUNT(*) FROM Event_Registration r
                 WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
        FROM Event e
        WHERE e.EventID IN ({$in}) AND e.Status <> 'Draft'
        ORDER BY e.StartTime DESC
    ")->fetchAll();

    $pageTitle = 'Bàn điểm danh — EventFlow';
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Bàn Điểm Danh Sự Kiện</h1>
                <p class="text-muted small mb-0">Danh sách các sự kiện bạn được phân công phụ trách Trưởng Bàn Điểm Danh (Check-in Lead).</p>
            </div>
            <a href="<?= base_url('app/modules/events/my_events.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-bookmark-check me-1"></i> Sự kiện của tôi
            </a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <?php if (empty($events)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-calendar-check fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    Bạn hiện chưa được phân công phụ trách điểm danh cho sự kiện nào.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Sự kiện</th>
                                <th>Thời gian</th>
                                <th>Đã đăng ký</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $e): 
                            $st = event_state($e, (int)$e['RegisteredCount'], $now);
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-dark"><?= $h($e['EventName']) ?></td>
                                <td class="small text-nowrap"><?= $h(event_fmt_dt($e['StartTime'])) ?></td>
                                <td class="font-mono small"><?= (int)$e['RegisteredCount'] ?></td>
                                <td><span class="badge-pill-custom <?= $h($st['css_class']) ?>"><?= $h($st['label']) ?></span></td>
                                <td class="text-end pe-4">
                                    <?php if ($e['Status'] !== 'Cancelled'): ?>
                                        <a href="<?= $h($pageUrl . '?event_id=' . (int)$e['EventID']) ?>" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold shadow-xs">
                                            <i class="bi bi-qr-code-scan me-1"></i> Mở Bàn Điểm Danh
                                        </a>
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
// ---------------------------------------------------------------------------
$evt = att_load_event($pdo, $eventId, $clubIds);
if (!$evt) {
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
        exit;
    }
    set_flash('danger', 'Không tìm thấy sự kiện hoặc bạn không thuộc CLB tổ chức sự kiện này.');
    header('Location: ' . $pageUrl);
    exit;
}

// Xử lý AJAX Polling trạng thái điểm danh thời gian thực (UAT 17)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_attendance_status') {
    session_write_close();
    header('Content-Type: application/json; charset=utf-8');
    $stmt = $pdo->prepare("
        SELECT r.RegID, r.AccountID, r.RegistrationType,
               COALESCE(a.FullName, r.GuestName) AS FullName,
               COALESCE(a.StudentCode, r.GuestStudentCode) AS StudentCode,
               COALESCE(a.Email, r.GuestEmail) AS Email,
               att.AttendanceStatus, att.CheckInAt
        FROM Event_Registration r
        LEFT JOIN Account a ON a.AccountID = r.AccountID
        LEFT JOIN Attendance att ON att.RegID = r.RegID
        WHERE r.EventID = :e AND r.Status = 'Registered'
        ORDER BY FullName ASC
    ");
    $stmt->execute([':e' => $eventId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($rows);
    $checked = 0;
    $items = [];
    foreach ($rows as $row) {
        $isDone = !empty($row['AttendanceStatus']);
        if ($isDone) $checked++;
        $items[$row['RegID']] = [
            'reg_id'        => (int)$row['RegID'],
            'fullname'      => $row['FullName'],
            'student_code'  => $row['StudentCode'] ?? '',
            'is_checked'    => $isDone,
            'checked_in_at' => $isDone && !empty($row['CheckInAt']) ? event_fmt_dt($row['CheckInAt']) : null
        ];
    }

    echo json_encode([
        'success' => true,
        'total'   => $total,
        'checked' => $checked,
        'items'   => $items
    ]);
    exit;
}

$now        = event_now($pdo);
$canCheckIn = $evt['Status'] === 'Published' && $now < $evt['EndTime'];
$last       = (int)($_GET['last'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.RegID, r.AccountID, r.RegistrationType,
           COALESCE(a.FullName, r.GuestName) AS FullName,
           COALESCE(a.StudentCode, r.GuestStudentCode) AS StudentCode,
           COALESCE(a.Email, r.GuestEmail) AS Email,
           att.AttendanceStatus, att.CheckInAt
    FROM Event_Registration r
    LEFT JOIN Account a ON a.AccountID = r.AccountID
    LEFT JOIN Attendance att ON att.RegID = r.RegID
    WHERE r.EventID = :e AND r.Status = 'Registered'
    ORDER BY FullName ASC
");
$stmt->execute([':e' => $eventId]);
$regs = $stmt->fetchAll();

$total   = count($regs);
$checked = 0;
foreach ($regs as $r) {
    if ($r['AttendanceStatus'] !== null) {
        $checked++;
    }
}

$pageTitle = 'Điểm danh — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('app/modules/events/my_events.php') ?>" class="text-decoration-none text-muted">Sự kiện của tôi</a></li>
            <?php if (count($leadEvtIds) > 1): ?>
                <li class="breadcrumb-item"><a href="<?= $h($pageUrl) ?>" class="text-decoration-none text-muted">Chọn sự kiện</a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= $h($evt['EventName']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1.5">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1" style="font-size: 0.72rem;">
                    <i class="bi bi-shield-check me-1"></i>Check-in Lead Desk
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2.5 py-1 d-inline-flex align-items-center gap-1.5" style="font-size: 0.72rem;">
                    <span class="spinner-grow spinner-grow-sm text-success" style="width: 0.45rem; height: 0.45rem;"></span> Realtime Active
                </span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= $h($evt['EventName']) ?></h1>
            <p class="text-muted small mb-0">
                <i class="bi bi-clock me-1 text-primary"></i><?= $h(event_fmt_dt($evt['StartTime'])) ?> · 
                <i class="bi bi-geo-alt me-1 text-primary"></i><?= $h($evt['Location']) ?>
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 text-md-end">
            <?php if ($canCheckIn): ?>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#walkinModal">
                    <i class="bi bi-person-plus-fill me-1"></i> + Điểm danh vãng lai
                </button>
            <?php endif; ?>
            <div class="ms-lg-2 bg-light px-3 py-2 rounded-3 border">
                <div class="text-muted font-monospace" style="font-size: 0.68rem; letter-spacing: 0.05em;">TIẾN ĐỘ ĐIỂM DANH</div>
                <div class="h5 fw-bold mb-0 font-mono text-dark">
                    <span id="checkedCount" class="text-success"><?= $checked ?></span> / <span id="totalCount"><?= $total ?></span> 
                    <span class="fs-6 fw-normal text-muted" style="font-size: 0.78rem !important;">người</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$canCheckIn): ?>
        <div class="alert alert-warning rounded-3 shadow-xs">
            <i class="bi bi-exclamation-triangle-fill me-1.5"></i> Sự kiện không còn mở điểm danh (đã hủy hoặc đã kết thúc). Bạn chỉ có thể xem danh sách.
        </div>
    <?php endif; ?>

    <!-- Realtime Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6 col-lg-7">
                <label for="q" class="visually-hidden">Tìm kiếm người tham gia</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="search" id="q" class="form-control border-start-0 border-end-0 ps-0 fs-6 shadow-none" autocomplete="off"
                           placeholder="Gõ họ tên, MSSV hoặc email để tìm kiếm realtime..." autofocus>
                    <button type="button" id="clearSearchBtn" class="btn bg-white border border-start-0 text-muted d-none" title="Xóa tìm kiếm">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-5 d-flex align-items-center justify-content-md-end gap-1.5 flex-wrap">
                <div class="btn-group btn-group-sm" role="group" id="filterStatusGroup">
                    <button type="button" class="btn btn-outline-primary active" data-filter="all">Tất cả (<span class="count-all"><?= $total ?></span>)</button>
                    <button type="button" class="btn btn-outline-secondary" data-filter="uncheck">Chưa điểm danh (<span class="count-uncheck"><?= $total - $checked ?></span>)</button>
                    <button type="button" class="btn btn-outline-success" data-filter="checked">Đã điểm danh (<span class="count-checked"><?= $checked ?></span>)</button>
                </div>
                <span class="badge bg-light text-secondary border font-monospace ms-md-2" id="searchResultsBadge" style="font-size: 0.75rem;">
                    Hiển thị <span id="matchCount"><?= $total ?></span> / <?= $total ?>
                </span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <?php if (empty($regs)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-secondary"></i>
                Chưa có người đăng ký còn hiệu lực.
                <?php if ($canCheckIn): ?>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#walkinModal">
                            <i class="bi bi-person-plus-fill me-1"></i> Điểm danh bổ sung ngay
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="regTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Họ và tên</th>
                            <th>MSSV</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($regs as $r):
                        $done   = $r['AttendanceStatus'] !== null;
                        $isLast = (int)$r['RegID'] === $last;
                    ?>
                        <tr data-reg-id="<?= (int)$r['RegID'] ?>"
                            data-name="<?= $h($r['FullName']) ?>" 
                            data-mssv="<?= $h($r['StudentCode'] ?? '') ?>"
                            data-email="<?= $h($r['Email'] ?? '') ?>"
                            data-done="<?= $done ? '1' : '0' ?>"
                            class="<?= $isLast ? 'table-success' : '' ?>">
                            <td class="ps-4">
                                <div class="fw-semibold text-dark">
                                    <?= $h($r['FullName']) ?>
                                    <?php if (empty($r['AccountID']) || ($r['RegistrationType'] ?? '') === 'Guest'): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill font-sans ms-1" style="font-size: 0.65rem;">Vãng lai</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($r['Email'])): ?>
                                    <small class="text-muted font-monospace" style="font-size: 0.74rem;"><?= $h($r['Email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="font-mono small"><?= $h($r['StudentCode'] ?? '—') ?></td>
                            <td class="status-cell">
                                <?php if ($done): ?>
                                    <span class="badge rounded-pill px-3 py-2 bg-success-subtle text-success">
                                        Đã điểm danh · <?= $h(event_fmt_dt($r['CheckInAt'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill px-3 py-2 bg-warning-subtle text-warning-emphasis">Chưa điểm danh</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4 action-cell">
                                <?php if (!$canCheckIn): ?>
                                    <span class="text-muted small">—</span>
                                <?php elseif ($done): ?>
                                    <form method="POST" action="<?= $h($eventUrl) ?>" class="d-inline att-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="undo">
                                        <input type="hidden" name="reg_id" value="<?= (int)$r['RegID'] ?>">
                                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Bỏ điểm danh</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= $h($eventUrl) ?>" class="d-inline att-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="checkin">
                                        <input type="hidden" name="reg_id" value="<?= (int)$r['RegID'] ?>">
                                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-check2 me-1"></i>Điểm danh
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="noMatch" class="p-4 text-center text-muted" hidden>Không có người nào khớp với từ khóa.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canCheckIn): ?>
<!-- MODAL ĐIỂM DANH VÃNG LAI (WALK-IN CHECK-IN) -->
<div class="modal fade" id="walkinModal" tabindex="-1" aria-labelledby="walkinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-light d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success me-3 flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="bi bi-person-plus-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-6" id="walkinModalLabel">Điểm Danh Vãng Lai</h5>
                        <small class="text-muted d-block" style="font-size: 0.8rem; margin-top: 2px;">Ghi nhận tham dự và lưu thông tin đăng ký tại chỗ</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="walkinForm" method="POST" action="<?= $h($eventUrl) ?>">
                <div class="modal-body p-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="walkin_checkin">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">

                    <div class="mb-3">
                        <label for="guest_name" class="form-label small fw-semibold text-dark mb-1">
                            Họ và tên người tham dự <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-person"></i></span>
                            <input type="text" id="guest_name" name="guest_name" class="form-control border-start-0" placeholder="Ví dụ: Nguyễn Văn An" required autocomplete="off">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="guest_student_code" class="form-label small fw-semibold text-dark mb-1">
                                Mã số sinh viên (MSSV)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-card-text"></i></span>
                                <input type="text" id="guest_student_code" name="guest_student_code" class="form-control border-start-0 font-monospace" placeholder="31241029999" autocomplete="off">
                            </div>
                            <div class="form-text small text-muted" style="font-size: 0.72rem;">Không bắt buộc nếu là khách ngoài</div>
                        </div>
                        <div class="col-md-6">
                            <label for="guest_phone" class="form-label small fw-semibold text-dark mb-1">
                                Số điện thoại
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                <input type="tel" id="guest_phone" name="guest_phone" class="form-control border-start-0 font-monospace" placeholder="0901234567" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="guest_email" class="form-label small fw-semibold text-dark mb-1">
                            Địa chỉ Email <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="guest_email" name="guest_email" class="form-control border-start-0" placeholder="Ví dụ: an.nguyen@st.ueh.edu.vn" required autocomplete="off">
                        </div>
                        <div class="form-text small text-muted" style="font-size: 0.72rem;">Dùng để đối soát hoặc tự động liên kết tài khoản nếu đã có trên hệ thống.</div>
                    </div>

                    <div class="alert alert-info py-2 px-3 rounded-3 small mb-0 d-flex align-items-center gap-2 border-0 bg-info-subtle text-info-emphasis">
                        <i class="bi bi-info-circle-fill text-info flex-shrink-0"></i>
                        <span>Người tham dự sẽ được bổ sung vào danh sách đăng ký và tự động ghi nhận điểm danh ngay lập tức.</span>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitWalkin" class="btn btn-success px-4 fw-semibold shadow-xs">
                        <i class="bi bi-check2-circle me-1"></i> Điểm danh ngay
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    </div>
</div>
<?php endif; ?>

<!-- Toast thông báo kết quả điểm danh realtime -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="attToast" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="attToastBody">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <span id="attToastMsg">Thao tác thành công.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
// 1. Lọc trực tiếp realtime bảng điểm danh chính
(function () {
    var input = document.getElementById('q');
    var clearBtn = document.getElementById('clearSearchBtn');
    var table = document.getElementById('regTable');
    var noMatch = document.getElementById('noMatch');
    var matchCountEl = document.getElementById('matchCount');
    var filterGroup = document.getElementById('filterStatusGroup');
    var currentFilter = 'all';

    if (!table) return;

    function norm(s) {
        return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'd').toLowerCase().trim();
    }

    function applyFilter() {
        var q = input ? norm(input.value) : '';
        if (clearBtn) {
            clearBtn.classList.toggle('d-none', !input || input.value.length === 0);
        }
        var total = 0, shown = 0;
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            total++;
            var name = norm(tr.dataset.name);
            var mssv = norm(tr.dataset.mssv);
            var email = norm(tr.dataset.email);
            var done = tr.dataset.done === '1';

            var matchSearch = !q || name.indexOf(q) !== -1 || mssv.indexOf(q) !== -1 || email.indexOf(q) !== -1;
            var matchStatus = (currentFilter === 'all') ||
                              (currentFilter === 'uncheck' && !done) ||
                              (currentFilter === 'checked' && done);

            var hit = matchSearch && matchStatus;
            tr.hidden = !hit;
            if (hit) shown++;
        });

        if (matchCountEl) matchCountEl.textContent = shown;
        if (noMatch) noMatch.hidden = shown > 0;
    }

    if (input) {
        input.addEventListener('input', applyFilter);
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (input) {
                input.value = '';
                input.focus();
                applyFilter();
            }
        });
    }

    if (filterGroup) {
        filterGroup.querySelectorAll('button').forEach(function(btn) {
            btn.addEventListener('click', function() {
                filterGroup.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter || 'all';
                applyFilter();
            });
        });
    }

    window.reapplyCheckinFilter = applyFilter;

    var highlighted = table.querySelector('tr.table-success');
    if (highlighted) highlighted.scrollIntoView({ block: 'center' });
})();


// 3. Realtime AJAX Check-in & Background Polling Sync (UAT 17)
(function() {
    const regTable = document.getElementById('regTable');
    const walkinTable = document.getElementById('walkinTable');
    const checkedCountEl = document.getElementById('checkedCount');
    const totalCountEl = document.getElementById('totalCount');
    const eventUrl = '<?= $h($eventUrl) ?>';
    const csrfToken = '<?= csrf_token() ?>';
    const canCheckIn = <?= $canCheckIn ? 'true' : 'false' ?>;

    if (!canCheckIn) return;

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('attToast');
        const toastMsg = document.getElementById('attToastMsg');
        if (!toastEl || !toastMsg) return;
        toastMsg.textContent = message;
        toastEl.className = `toast align-items-center text-white border-0 shadow ${type === 'success' ? 'bg-success' : 'bg-danger'}`;
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
    }

    function updateStatsCounter(checked, total) {
        if (checkedCountEl) checkedCountEl.textContent = checked;
        if (totalCountEl) totalCountEl.textContent = total;
        const uncheck = total - checked;
        document.querySelectorAll('.count-all').forEach(el => el.textContent = total);
        document.querySelectorAll('.count-uncheck').forEach(el => el.textContent = uncheck);
        document.querySelectorAll('.count-checked').forEach(el => el.textContent = checked);
        if (window.reapplyCheckinFilter) window.reapplyCheckinFilter();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateRowStatus(regId, isChecked, checkInAt) {
        const tr = document.querySelector(`tr[data-reg-id="${regId}"]`);
        if (!tr) return;

        tr.setAttribute('data-done', isChecked ? '1' : '0');
        const statusCell = tr.querySelector('.status-cell');
        const actionCell = tr.querySelector('.action-cell');

        if (isChecked) {
            tr.classList.add('table-success');
            setTimeout(() => tr.classList.remove('table-success'), 1500);

            if (statusCell) {
                statusCell.innerHTML = `<span class="badge rounded-pill px-3 py-2 bg-success-subtle text-success">Đã điểm danh · ${escapeHtml(checkInAt || 'Vừa xong')}</span>`;
            }
            if (actionCell) {
                actionCell.innerHTML = `
                    <form method="POST" action="${eventUrl}" class="d-inline att-form">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <input type="hidden" name="action" value="undo">
                        <input type="hidden" name="reg_id" value="${regId}">
                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Bỏ điểm danh</button>
                    </form>
                `;
            }
        } else {
            tr.classList.remove('table-success');
            if (statusCell) {
                statusCell.innerHTML = `<span class="badge rounded-pill px-3 py-2 bg-warning-subtle text-warning-emphasis">Chưa điểm danh</span>`;
            }
            if (actionCell) {
                actionCell.innerHTML = `
                    <form method="POST" action="${eventUrl}" class="d-inline att-form">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <input type="hidden" name="action" value="checkin">
                        <input type="hidden" name="reg_id" value="${regId}">
                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-check2 me-1"></i>Điểm danh
                        </button>
                    </form>
                `;
            }
        }
    }

    function insertRowIntoTable(user) {
        let tbody = document.querySelector('#regTable tbody');
        if (!tbody) {
            const emptyNotice = document.querySelector('.card.border-0.shadow-sm.rounded-4.overflow-hidden .p-5.text-center');
            if (emptyNotice) {
                const parent = emptyNotice.parentElement;
                emptyNotice.remove();
                const div = document.createElement('div');
                div.className = 'table-responsive';
                div.innerHTML = `
                    <table class="table align-middle mb-0" id="regTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Họ và tên</th>
                                <th>MSSV</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                `;
                parent.appendChild(div);
                tbody = div.querySelector('tbody');
            }
        }
        if (!tbody) return;

        const existing = document.querySelector(`tr[data-reg-id="${user.reg_id}"]`);
        if (existing) {
            updateRowStatus(user.reg_id, user.is_checked, user.checked_in_at);
            return;
        }

        const tr = document.createElement('tr');
        tr.setAttribute('data-reg-id', user.reg_id);
        tr.setAttribute('data-name', user.fullname);
        tr.setAttribute('data-mssv', user.student_code || '');
        tr.setAttribute('data-email', user.email || '');
        tr.setAttribute('data-done', user.is_checked ? '1' : '0');
        tr.className = 'table-success';
        setTimeout(() => tr.classList.remove('table-success'), 2000);

        tr.innerHTML = `
            <td class="ps-4">
                <div class="fw-semibold text-dark">
                    ${escapeHtml(user.fullname)} 
                    ${user.is_guest ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill font-sans ms-1" style="font-size: 0.65rem;">Vãng lai</span>' : ''}
                </div>
                ${user.email ? `<small class="text-muted font-monospace" style="font-size: 0.74rem;">${escapeHtml(user.email)}</small>` : ''}
            </td>
            <td class="font-mono small">${escapeHtml(user.student_code || '—')}</td>
            <td class="status-cell">
                <span class="badge rounded-pill px-3 py-2 bg-success-subtle text-success">Đã điểm danh · ${escapeHtml(user.checked_in_at || 'Vừa xong')}</span>
            </td>
            <td class="text-end pe-4 action-cell">
                <form method="POST" action="${eventUrl}" class="d-inline att-form">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="action" value="undo">
                    <input type="hidden" name="reg_id" value="${user.reg_id}">
                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Bỏ điểm danh</button>
                </form>
            </td>
        `;
        tbody.prepend(tr);
    }

    // Bắt sự kiện submit Điểm danh / Bỏ điểm danh tại bảng chính
    if (regTable) {
        regTable.addEventListener('submit', async function(e) {
            const form = e.target.closest('form.att-form');
            if (!form) return;
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            if (!button || button.disabled) return;
            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

            const formData = new FormData(form);
            formData.append('ajax', '1');
            const targetUrl = form.getAttribute('action') || window.location.href;

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    throw new Error(`Máy chủ phản hồi mã lỗi HTTP ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    updateRowStatus(result.reg_id, result.action === 'checkin', result.checked_in_at);
                    if (result.stats) {
                        updateStatsCounter(result.stats.checked, result.stats.total);
                    }
                    showToast(result.message, 'success');
                } else {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                    showToast(result.message || 'Lỗi khi cập nhật điểm danh.', 'danger');
                }
            } catch (err) {
                button.disabled = false;
                button.innerHTML = originalHtml;
                showToast(err.message || 'Lỗi kết nối máy chủ.', 'danger');
                console.error('Check-in error:', err);
            }
        });
    }

    // Bắt sự kiện submit form Điểm danh vãng lai trong Modal
    const walkinForm = document.getElementById('walkinForm');
    const walkinModalEl = document.getElementById('walkinModal');
    if (walkinForm) {
        walkinForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const button = document.getElementById('btnSubmitWalkin');
            if (!button || button.disabled) return;
            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Đang lưu...';

            const formData = new FormData(walkinForm);
            formData.append('ajax', '1');
            const targetUrl = walkinForm.getAttribute('action') || window.location.href;

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) {
                    throw new Error(`Máy chủ phản hồi mã lỗi HTTP ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    // Chèn người tham gia vào bảng chính
                    if (result.user) {
                        insertRowIntoTable({
                            reg_id: result.reg_id,
                            fullname: result.user.fullname,
                            student_code: result.user.student_code,
                            email: result.user.email,
                            is_checked: true,
                            checked_in_at: result.user.checked_in_at,
                            is_guest: result.user.is_guest
                        });
                    }

                    if (result.stats) {
                        updateStatsCounter(result.stats.checked, result.stats.total);
                    }
                    showToast(result.message, 'success');

                    // Reset form và đóng modal
                    walkinForm.reset();
                    if (walkinModalEl) {
                        const modalInstance = bootstrap.Modal.getInstance(walkinModalEl);
                        if (modalInstance) modalInstance.hide();
                    }
                } else {
                    showToast(result.message || 'Lỗi khi điểm danh vãng lai.', 'danger');
                }
            } catch (err) {
                showToast(err.message || 'Lỗi kết nối máy chủ.', 'danger');
                console.error('Walk-in error:', err);
            } finally {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        });
    }

    // Polling Realtime đồng bộ giữa nhiều người điểm danh cùng lúc (mỗi 3 giây)
    setInterval(async function() {
        try {
            const pollUrl = new URL(window.location.href);
            pollUrl.searchParams.set('ajax', 'get_attendance_status');
            const response = await fetch(pollUrl.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) return;
            const result = await response.json();
            if (result.success) {
                updateStatsCounter(result.checked, result.total);
                for (const regId in result.items) {
                    const item = result.items[regId];
                    const tr = document.querySelector(`tr[data-reg-id="${regId}"]`);
                    if (tr) {
                        const currentlyDone = tr.getAttribute('data-done') === '1';
                        if (currentlyDone !== item.is_checked) {
                            updateRowStatus(item.reg_id, item.is_checked, item.checked_in_at);
                        }
                    } else if (item.is_checked) {
                        insertRowIntoTable(item);
                    }
                }
            }
        } catch (e) {
            // Polling im lặng
        }
    }, 3000);
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
