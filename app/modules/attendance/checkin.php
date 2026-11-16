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

require_authorized_club_member();

$pdo      = get_db_connection();
$h        = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$clubIds  = att_allowed_club_ids($pdo);
$pageUrl  = base_url('app/modules/attendance/checkin.php');
$eventId  = isset($_GET['event_id']) ? (int)$_GET['event_id'] : (int)($_POST['event_id'] ?? 0);
$eventUrl = $pageUrl . '?event_id=' . $eventId;

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
 * Điểm danh bổ sung (Walk-in check-in) cho người chưa đăng ký trước hoặc từng hủy (PS09, FR15, PB19).
 * Trả về [bool, thông báo, int regId].
 * Khóa dòng sự kiện bằng SELECT ... FOR UPDATE để bảo đảm không vượt MaxCapacity (NFR01).
 */
function att_walkin_checkin(PDO $pdo, int $eventId, int $targetAccountId, int $staffId): array {
    $rollback = static function (string $msg) use ($pdo): array {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, $msg, 0];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :e FOR UPDATE");
        $stmt->execute([':e' => $eventId]);
        $evt = $stmt->fetch();

        if (!$evt) {
            return $rollback('Không tìm thấy sự kiện.');
        }
        if ($evt['Status'] !== 'Published') {
            return $rollback('Sự kiện không ở trạng thái công bố.');
        }
        if (event_now($pdo) >= $evt['EndTime']) {
            return $rollback('Sự kiện đã kết thúc, không thể điểm danh.');
        }

        $accStmt = $pdo->prepare("SELECT AccountID, FullName, StudentCode, IsActive FROM Account WHERE AccountID = :a");
        $accStmt->execute([':a' => $targetAccountId]);
        $targetUser = $accStmt->fetch();

        if (!$targetUser) {
            return $rollback('Không tìm thấy tài khoản sinh viên.');
        }
        if (empty($targetUser['IsActive'])) {
            return $rollback('Tài khoản sinh viên này đang bị khóa trong hệ thống.');
        }

        $regStmt = $pdo->prepare("
            SELECT RegID, Status FROM Event_Registration
            WHERE EventID = :e AND AccountID = :a
            FOR UPDATE
        ");
        $regStmt->execute([':e' => $eventId, ':a' => $targetAccountId]);
        $reg = $regStmt->fetch();

        $regId = 0;
        if ($reg && $reg['Status'] === 'Registered') {
            $regId = (int)$reg['RegID'];
            $chk = $pdo->prepare("SELECT AttendanceID FROM Attendance WHERE RegID = :r LIMIT 1");
            $chk->execute([':r' => $regId]);
            if ($chk->fetch()) {
                return $rollback('Sinh viên [' . $targetUser['FullName'] . '] đã được điểm danh trước đó.');
            }
        } else {
            // Kiểm tra MaxCapacity
            $cntStmt = $pdo->prepare("
                SELECT COUNT(*) FROM Event_Registration
                WHERE EventID = :e AND Status = 'Registered'
            ");
            $cntStmt->execute([':e' => $eventId]);
            $registeredCount = (int)$cntStmt->fetchColumn();

            if ($evt['MaxCapacity'] !== null && $registeredCount >= (int)$evt['MaxCapacity']) {
                return $rollback('Sự kiện đã đủ số lượng tham dự tối đa (' . $evt['MaxCapacity'] . ' chỗ). Không thể nhận thêm đăng ký bổ sung.');
            }

            if ($reg) {
                // Tái kích hoạt dòng đã Cancelled
                $up = $pdo->prepare("UPDATE Event_Registration SET Status = 'Registered', RegDate = NOW() WHERE RegID = :r");
                $up->execute([':r' => $reg['RegID']]);
                $regId = (int)$reg['RegID'];
            } else {
                // Tạo mới dòng đăng ký
                $insReg = $pdo->prepare("
                    INSERT INTO Event_Registration (EventID, AccountID, RegDate, Status)
                    VALUES (:e, :a, NOW(), 'Registered')
                ");
                $insReg->execute([':e' => $eventId, ':a' => $targetAccountId]);
                $regId = (int)$pdo->lastInsertId();
            }
        }

        // Ghi nhận điểm danh
        $insAtt = $pdo->prepare("
            INSERT INTO Attendance (RegID, AttendanceStatus, CheckInAt, CheckedInBy)
            VALUES (:r, 1, NOW(), :s)
        ");
        $insAtt->execute([':r' => $regId, ':s' => $staffId]);

        $pdo->commit();
        return [true, "Đã ghi nhận đăng ký bổ sung và điểm danh thành công cho sinh viên [{$targetUser['FullName']}].", $regId];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] walk-in check-in failed: ' . $e->getMessage());
        return [false, 'Không thể xử lý điểm danh bổ sung: ' . $e->getMessage(), 0];
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
// POST: điểm danh / bỏ điểm danh / điểm danh bổ sung walk-in
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
    } elseif ($action === 'walkin_checkin') {
        $targetAccountId = (int)($_POST['account_id'] ?? 0);
        [$ok, $msg, $newRegId] = att_walkin_checkin($pdo, $eventId, $targetAccountId, (int)current_user_id());
        set_flash($ok ? 'success' : 'danger', $msg);
        $last = $ok && $newRegId > 0 ? '&last=' . $newRegId : '';
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
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Điểm danh sự kiện</h1>
                <p class="text-muted small mb-0">Chọn sự kiện để điểm danh người tham gia.</p>
            </div>
            <a href="<?= base_url('app/modules/attendance/registrations.php') ?>" class="btn btn-outline-primary">
                <i class="bi bi-people me-1"></i> Quản lý danh sách đăng ký
            </a>
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
// ---------------------------------------------------------------------------
$evt = att_load_event($pdo, $eventId, $clubIds);
if (!$evt) {
    set_flash('danger', 'Không tìm thấy sự kiện hoặc bạn không thuộc CLB tổ chức sự kiện này.');
    header('Location: ' . $pageUrl);
    exit;
}

$now        = event_now($pdo);
$canCheckIn = $evt['Status'] === 'Published' && $now < $evt['EndTime'];
$last       = (int)($_GET['last'] ?? 0);

$stmt = $pdo->prepare("
    SELECT r.RegID, a.FullName, a.StudentCode,
           att.AttendanceStatus, att.CheckInAt
    FROM Event_Registration r
    JOIN Account a ON a.AccountID = r.AccountID
    LEFT JOIN Attendance att ON att.RegID = r.RegID
    WHERE r.EventID = :e AND r.Status = 'Registered'
    ORDER BY a.FullName ASC
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

// Danh sách ứng viên tiềm năng cho Điểm danh bổ sung Walk-in (tài khoản active chưa điểm danh)
$unregisteredCandidates = [];
if ($canCheckIn) {
    $candStmt = $pdo->prepare("
        SELECT a.AccountID, a.FullName, a.StudentCode, a.Email,
               r.RegID, r.Status AS RegStatus,
               att.AttendanceStatus
        FROM Account a
        LEFT JOIN Event_Registration r ON r.EventID = :e AND r.AccountID = a.AccountID
        LEFT JOIN Attendance att ON att.RegID = r.RegID
        WHERE a.IsActive = 1
        ORDER BY a.FullName ASC
    ");
    $candStmt->execute([':e' => $eventId]);
    $allAccounts = $candStmt->fetchAll();

    foreach ($allAccounts as $acc) {
        if ($acc['AttendanceStatus'] === null) {
            $unregisteredCandidates[] = $acc;
        }
    }
}

$pageTitle = 'Điểm danh — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $h($pageUrl) ?>" class="text-decoration-none text-muted">Chọn sự kiện</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= $h($evt['EventName']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1"><?= $h($evt['EventName']) ?></h1>
            <p class="text-muted small mb-0"><?= $h(event_fmt_dt($evt['StartTime'])) ?> · <?= $h($evt['Location']) ?></p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 text-md-end">
            <a href="<?= base_url('app/modules/attendance/registrations.php?event_id=' . $eventId) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-people me-1"></i> Danh sách đăng ký
            </a>
            <?php if ($canCheckIn): ?>
                <button type="button" class="btn btn-outline-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#walkinModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Điểm danh bổ sung (Chưa đăng ký)
                </button>
            <?php endif; ?>
            <div class="ms-lg-2">
                <div class="small text-muted">Đã điểm danh</div>
                <div class="h4 fw-bold mb-0 font-mono"><?= $checked ?> / <?= $total ?> <span class="fs-6 fw-normal text-muted">người đăng ký</span></div>
            </div>
        </div>
    </div>

    <?php if (!$canCheckIn): ?>
        <div class="alert alert-warning rounded-3">
            Sự kiện không còn mở điểm danh (đã hủy hoặc đã kết thúc). Bạn chỉ có thể xem danh sách.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <label for="q" class="visually-hidden">Tìm theo họ tên hoặc MSSV</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" id="q" class="form-control border-start-0" autocomplete="off"
                   placeholder="Gõ họ tên hoặc MSSV để lọc nhanh người tham gia..." autofocus>
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
                        <tr data-name="<?= $h($r['FullName']) ?>" data-mssv="<?= $h($r['StudentCode'] ?? '') ?>"
                            class="<?= $isLast ? 'table-success' : '' ?>">
                            <td class="ps-4 fw-semibold text-dark"><?= $h($r['FullName']) ?></td>
                            <td class="font-mono small"><?= $h($r['StudentCode'] ?? '—') ?></td>
                            <td>
                                <?php if ($done): ?>
                                    <span class="badge rounded-pill px-3 py-2 bg-success-subtle text-success">
                                        Đã điểm danh · <?= $h(event_fmt_dt($r['CheckInAt'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill px-3 py-2 bg-warning-subtle text-warning-emphasis">Chưa điểm danh</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (!$canCheckIn): ?>
                                    <span class="text-muted small">—</span>
                                <?php elseif ($done): ?>
                                    <form method="POST" action="<?= $h($eventUrl) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="undo">
                                        <input type="hidden" name="reg_id" value="<?= (int)$r['RegID'] ?>">
                                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Bỏ điểm danh</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= $h($eventUrl) ?>" class="d-inline">
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
<!-- Modal Điểm danh bổ sung (Walk-in Check-in) -->
<div class="modal fade" id="walkinModal" tabindex="-1" aria-labelledby="walkinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 pt-4 pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-3 bg-success-subtle text-success">
                        <i class="bi bi-person-plus-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="walkinModalLabel">Điểm danh bổ sung tại chỗ</h5>
                        <small class="text-muted">Dành cho sinh viên chưa đăng ký trước hoặc chưa có tên trong danh sách điểm danh</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="walkinQuery" class="form-label fw-semibold small text-dark">Tra cứu nhanh sinh viên</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="walkinQuery" class="form-control border-start-0" autocomplete="off"
                               placeholder="Nhập Họ tên, MSSV hoặc Email để tìm kiếm...">
                    </div>
                    <div class="form-text small">Tìm trong danh sách tất cả tài khoản sinh viên đang hoạt động trên hệ thống.</div>
                </div>

                <div class="table-responsive rounded-3 border" style="max-height: 340px;">
                    <table class="table table-hover align-middle mb-0" id="walkinTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Họ và tên</th>
                                <th>MSSV</th>
                                <th>Email</th>
                                <th>Tình trạng đăng ký</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($unregisteredCandidates)): ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-muted">
                                    Tất cả sinh viên đều đã được điểm danh!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($unregisteredCandidates as $cand):
                                $statusLabel = 'Chưa đăng ký';
                                $statusClass = 'bg-secondary-subtle text-secondary';
                                if ($cand['RegStatus'] === 'Registered') {
                                    $statusLabel = 'Đã đăng ký (chưa điểm danh)';
                                    $statusClass = 'bg-primary-subtle text-primary';
                                } elseif ($cand['RegStatus'] === 'Cancelled') {
                                    $statusLabel = 'Đã hủy đăng ký';
                                    $statusClass = 'bg-warning-subtle text-warning-emphasis';
                                }
                            ?>
                                <tr data-name="<?= $h($cand['FullName']) ?>"
                                    data-mssv="<?= $h($cand['StudentCode'] ?? '') ?>"
                                    data-email="<?= $h($cand['Email']) ?>">
                                    <td class="ps-3 fw-semibold text-dark"><?= $h($cand['FullName']) ?></td>
                                    <td class="font-mono small"><?= $h($cand['StudentCode'] ?? '—') ?></td>
                                    <td class="small text-muted"><?= $h($cand['Email']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill px-2 py-1 <?= $statusClass ?> small">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <form method="POST" action="<?= $h($eventUrl) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="walkin_checkin">
                                            <input type="hidden" name="account_id" value="<?= (int)$cand['AccountID'] ?>">
                                            <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle me-1"></i> Điểm danh ngay
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="walkinNoMatch" class="p-3 text-center text-muted small" hidden>
                    Không tìm thấy sinh viên nào khớp với từ khóa tìm kiếm.
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// 1. Lọc trực tiếp bảng điểm danh chính
(function () {
    var input = document.getElementById('q');
    var table = document.getElementById('regTable');
    var noMatch = document.getElementById('noMatch');
    if (!input || !table) return;

    function norm(s) {
        return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'd').toLowerCase().trim();
    }

    input.addEventListener('input', function () {
        var q = norm(input.value), shown = 0;
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            var hit = !q || norm(tr.dataset.name).indexOf(q) !== -1 || norm(tr.dataset.mssv).indexOf(q) !== -1;
            tr.hidden = !hit;
            if (hit) shown++;
        });
        if (noMatch) noMatch.hidden = shown > 0;
    });

    var highlighted = table.querySelector('tr.table-success');
    if (highlighted) highlighted.scrollIntoView({ block: 'center' });
})();

// 2. Lọc trực tiếp trong modal Điểm danh bổ sung Walk-in
(function () {
    var wInput = document.getElementById('walkinQuery');
    var wTable = document.getElementById('walkinTable');
    var wNoMatch = document.getElementById('walkinNoMatch');
    if (!wInput || !wTable) return;

    function norm(s) {
        return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'd').toLowerCase().trim();
    }

    wInput.addEventListener('input', function () {
        var q = norm(wInput.value), shown = 0;
        wTable.querySelectorAll('tbody tr').forEach(function (tr) {
            if (!tr.dataset.name) return;
            var hit = !q || norm(tr.dataset.name).indexOf(q) !== -1 ||
                      norm(tr.dataset.mssv).indexOf(q) !== -1 ||
                      norm(tr.dataset.email).indexOf(q) !== -1;
            tr.hidden = !hit;
            if (hit) shown++;
        });
        if (wNoMatch) wNoMatch.hidden = shown > 0;
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
