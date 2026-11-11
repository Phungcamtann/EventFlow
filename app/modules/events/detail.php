<?php
/**
 * EventFlow — SCR09: Chi tiết sự kiện, Đăng ký & Hủy đăng ký
 * Đáp ứng: FR10, FR12, PB14, PB15, UC08, UC09
 * Phân quyền: mọi tài khoản đã đăng nhập được xem;
 *             chỉ 'Club Member' / 'Event Participant' được đăng ký và hủy đăng ký.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_login();

$pdo         = get_db_connection();
$eventId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId      = current_user_id();
$detailUrl   = base_url('app/modules/events/detail.php?id=' . $eventId);
$canRegister = has_role(['Club Member', 'Event Participant']);
$h           = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/**
 * Đăng ký sự kiện (FR12). Trả về [bool thành công, string thông báo].
 * Khóa dòng Event bằng SELECT ... FOR UPDATE để các lượt đăng ký đồng thời không vượt MaxCapacity (NFR01).
 * UNIQUE(EventID, AccountID): nếu từng hủy thì UPDATE lại dòng cũ, không INSERT mới.
 */
function event_register_account(PDO $pdo, int $eventId, int $accountId): array {
    $fail = static function (string $msg) use ($pdo): array {
        $pdo->rollBack();
        return [false, $msg];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id FOR UPDATE");
        $stmt->execute([':id' => $eventId]);
        $evt = $stmt->fetch();

        if (!$evt || $evt['Status'] === 'Draft') {
            return $fail('Không tìm thấy sự kiện.');
        }
        if ($evt['Status'] === 'Cancelled') {
            return $fail('Sự kiện đã bị hủy, không thể đăng ký.');
        }
        if (event_now($pdo) > $evt['RegistrationDeadline']) {
            return $fail('Đã quá hạn đăng ký sự kiện.');
        }

        $regStmt = $pdo->prepare("
            SELECT RegID, Status FROM Event_Registration
            WHERE EventID = :e AND AccountID = :a
            FOR UPDATE
        ");
        $regStmt->execute([':e' => $eventId, ':a' => $accountId]);
        $reg = $regStmt->fetch();

        if ($reg && $reg['Status'] === 'Registered') {
            return $fail('Bạn đã đăng ký sự kiện này rồi.');
        }

        $cntStmt = $pdo->prepare("
            SELECT COUNT(*) FROM Event_Registration
            WHERE EventID = :e AND Status = 'Registered'
        ");
        $cntStmt->execute([':e' => $eventId]);
        $registered = (int)$cntStmt->fetchColumn();

        if ($evt['MaxCapacity'] !== null && $registered >= (int)$evt['MaxCapacity']) {
            return $fail('Sự kiện đã hết chỗ.');
        }

        if ($reg) {
            $up = $pdo->prepare("
                UPDATE Event_Registration SET Status = 'Registered', RegDate = NOW()
                WHERE RegID = :id
            ");
            $up->execute([':id' => $reg['RegID']]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO Event_Registration (EventID, AccountID, Status)
                VALUES (:e, :a, 'Registered')
            ");
            $ins->execute([':e' => $eventId, ':a' => $accountId]);
        }

        $pdo->commit();
        return [true, 'Đăng ký thành công!'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] register failed: ' . $e->getMessage());
        return [false, 'Không thể xử lý yêu cầu. Vui lòng thử lại.'];
    }
}

/**
 * Hủy đăng ký (FR12). Chỉ đổi Status → 'Cancelled', không xóa dòng (bảo toàn dữ liệu).
 * Quy tắc tạm: được hủy đến trước giờ bắt đầu, và chưa được điểm danh.
 */
function event_cancel_registration(PDO $pdo, int $eventId, int $accountId): array {
    $fail = static function (string $msg) use ($pdo): array {
        $pdo->rollBack();
        return [false, $msg];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id FOR UPDATE");
        $stmt->execute([':id' => $eventId]);
        $evt = $stmt->fetch();

        if (!$evt || $evt['Status'] === 'Draft') {
            return $fail('Không tìm thấy sự kiện.');
        }
        if ($evt['Status'] === 'Cancelled') {
            return $fail('Sự kiện đã bị hủy, không thể thay đổi đăng ký.');
        }
        if (event_now($pdo) >= $evt['StartTime']) {
            return $fail('Sự kiện đã bắt đầu, không thể hủy đăng ký.');
        }

        $regStmt = $pdo->prepare("
            SELECT RegID, Status FROM Event_Registration
            WHERE EventID = :e AND AccountID = :a
            FOR UPDATE
        ");
        $regStmt->execute([':e' => $eventId, ':a' => $accountId]);
        $reg = $regStmt->fetch();

        if (!$reg || $reg['Status'] !== 'Registered') {
            return $fail('Bạn chưa đăng ký sự kiện này.');
        }

        $attStmt = $pdo->prepare("SELECT COUNT(*) FROM Attendance WHERE RegID = :r");
        $attStmt->execute([':r' => $reg['RegID']]);
        if ((int)$attStmt->fetchColumn() > 0) {
            return $fail('Bạn đã được điểm danh, không thể hủy đăng ký.');
        }

        $up = $pdo->prepare("UPDATE Event_Registration SET Status = 'Cancelled' WHERE RegID = :id");
        $up->execute([':id' => $reg['RegID']]);

        $pdo->commit();
        return [true, 'Hủy đăng ký thành công.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] cancel registration failed: ' . $e->getMessage());
        return [false, 'Không thể xử lý yêu cầu. Vui lòng thử lại.'];
    }
}

// ---------------------------------------------------------------------------
// Xử lý POST (Post/Redirect/Get)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif (!$canRegister) {
        set_flash('danger', 'Tài khoản của bạn không có quyền đăng ký sự kiện.');
    } elseif ($action === 'register') {
        [$ok, $msg] = event_register_account($pdo, $eventId, $userId);
        set_flash($ok ? 'success' : 'danger', $msg);
    } elseif ($action === 'cancel_registration') {
        [$ok, $msg] = event_cancel_registration($pdo, $eventId, $userId);
        set_flash($ok ? 'success' : 'danger', $msg);
    } else {
        set_flash('danger', 'Thao tác không hợp lệ.');
    }

    header('Location: ' . $detailUrl);
    exit;
}

// ---------------------------------------------------------------------------
// Nạp dữ liệu hiển thị
// ---------------------------------------------------------------------------
$evtStmt = $pdo->prepare("
    SELECT e.*, c.ClubName
    FROM Event e
    LEFT JOIN Club c ON c.ClubID = e.ClubID
    WHERE e.EventID = :id
    LIMIT 1
");
$evtStmt->execute([':id' => $eventId]);
$evt = $evtStmt->fetch();

// Bản nháp chỉ Club Manager được xem
if (!$evt || ($evt['Status'] === 'Draft' && !is_club_manager())) {
    set_flash('danger', 'Không tìm thấy sự kiện.');
    header('Location: ' . base_url('index.php'));
    exit;
}

$now = event_now($pdo);

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM Event_Registration WHERE EventID = :e AND Status = 'Registered'");
$cntStmt->execute([':e' => $eventId]);
$state = event_state($evt, (int)$cntStmt->fetchColumn(), $now);

// Đăng ký của người dùng hiện tại (chỉ vai trò được phép đăng ký)
$myReg    = null;
$attended = false;
if ($canRegister) {
    $myStmt = $pdo->prepare("
        SELECT r.RegID, r.Status, r.RegDate, a.AttendanceStatus
        FROM Event_Registration r
        LEFT JOIN Attendance a ON a.RegID = r.RegID
        WHERE r.EventID = :e AND r.AccountID = :a
        LIMIT 1
    ");
    $myStmt->execute([':e' => $eventId, ':a' => $userId]);
    $myReg    = $myStmt->fetch() ?: null;
    $attended = $myReg && $myReg['AttendanceStatus'] !== null && (int)$myReg['AttendanceStatus'] === 1;
}

$isRegistered   = $myReg && $myReg['Status'] === 'Registered';
$hasStarted     = $now >= $evt['StartTime'];
$canRegisterNow = $canRegister && !$isRegistered && $state['is_open'];
$canCancelReg   = $isRegistered && $evt['Status'] === 'Published' && !$hasStarted && !$attended;

if ($attended) {
    $myLabel = 'Đã tham dự';       $myClass = 'bg-success-subtle text-success';
} elseif ($isRegistered) {
    $myLabel = 'Đã đăng ký';       $myClass = 'bg-primary-subtle text-primary';
} elseif ($myReg) {
    $myLabel = 'Đã hủy đăng ký';   $myClass = 'bg-secondary-subtle text-secondary';
} else {
    $myLabel = 'Chưa đăng ký';     $myClass = 'bg-light text-secondary border';
}

$pctFull = ($state['capacity'] !== null && $state['capacity'] > 0)
    ? min(100, (int)round($state['registered'] / $state['capacity'] * 100))
    : 0;
$barClass = $state['closed_reason'] === 'full' ? 'bg-danger'
    : (!empty($state['highlights']) && $state['seats_left'] !== null && $state['seats_left'] <= EVENT_LOW_SEATS_THRESHOLD ? 'bg-warning' : 'bg-success');

$pageTitle = $evt['EventName'] . ' — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Danh sách sự kiện</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= $h($evt['EventName']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- THÔNG TIN SỰ KIỆN -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white border-bottom border-light p-4">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge-pill-custom <?= $h($state['css_class']) ?>"><?= $h($state['label']) ?></span>
                        <?php foreach ($state['highlights'] as $hl): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1">
                                <i class="bi bi-exclamation-circle me-1"></i><?= $h($hl) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <h1 class="h3 fw-bold text-dark mb-1"><?= $h($evt['EventName']) ?></h1>
                    <?php if (!empty($evt['ClubName'])): ?>
                        <p class="text-muted small mb-0"><i class="bi bi-people me-1"></i><?= $h($evt['ClubName']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="bi bi-calendar-event me-1 text-primary"></i> Bắt đầu</div>
                            <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['StartTime'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="bi bi-calendar-check me-1 text-primary"></i> Kết thúc</div>
                            <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['EndTime'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="bi bi-geo-alt me-1 text-primary"></i> Địa điểm</div>
                            <div class="fw-semibold text-dark"><?= $h($evt['Location']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="bi bi-hourglass-split me-1 text-primary"></i> Hạn đăng ký</div>
                            <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['RegistrationDeadline'])) ?></div>
                        </div>
                    </div>

                    <h2 class="h6 fw-bold text-dark">Mô tả sự kiện</h2>
                    <?php if (!empty(trim((string)$evt['Description']))): ?>
                        <p class="text-secondary mb-0"><?= nl2br($h($evt['Description'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted fst-italic mb-0">Chưa có mô tả.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SỐ CHỖ & ĐĂNG KÝ -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold text-dark mb-3">Đăng ký tham gia</h2>

                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Đã đăng ký</span>
                        <span class="fw-semibold font-mono"><?= $h(event_seats_text($state)) ?></span>
                    </div>
                    <?php if ($state['capacity'] !== null): ?>
                        <div class="progress mb-1" style="height: 8px;" role="progressbar"
                             aria-valuenow="<?= $pctFull ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar <?= $barClass ?>" style="width: <?= $pctFull ?>%"></div>
                        </div>
                        <div class="small text-muted mb-3">Còn lại <strong><?= (int)$state['seats_left'] ?></strong> chỗ</div>
                    <?php else: ?>
                        <div class="small text-muted mb-3">Không giới hạn số chỗ</div>
                    <?php endif; ?>

                    <?php if ($canRegister): ?>
                        <div class="mb-3">
                            <div class="small text-muted mb-1">Trạng thái của bạn</div>
                            <span class="badge <?= $myClass ?> rounded-pill px-3 py-1"><?= $h($myLabel) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($canRegisterNow): ?>
                        <form method="POST" action="<?= $h($detailUrl) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="register">
                            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> Đăng ký tham gia
                            </button>
                        </form>
                    <?php elseif ($canCancelReg): ?>
                        <form method="POST" action="<?= $h($detailUrl) ?>"
                              onsubmit="return confirm('Bạn chắc chắn muốn hủy đăng ký sự kiện này?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="cancel_registration">
                            <button type="submit" class="btn btn-outline-danger w-100 fw-semibold">
                                <i class="bi bi-x-circle me-1"></i> Hủy đăng ký
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php
                    // Thông báo khi không thể đăng ký
                    if (!$canRegister): ?>
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-info-circle me-1"></i> Chỉ thành viên CLB và sinh viên tham gia mới đăng ký được sự kiện.
                        </div>
                    <?php elseif ($state['closed_reason'] === 'cancelled'): ?>
                        <div class="alert alert-danger small mb-0 mt-3">
                            <i class="bi bi-x-octagon me-1"></i> Sự kiện này đã bị hủy.
                            <?= $isRegistered ? 'Thông tin đăng ký của bạn vẫn được lưu lại.' : '' ?>
                        </div>
                    <?php elseif (!$isRegistered && $state['closed_reason'] === 'deadline'): ?>
                        <div class="alert alert-secondary small mb-0 mt-3">
                            <i class="bi bi-clock-history me-1"></i> Đã hết hạn đăng ký (<?= $h(event_fmt_dt($evt['RegistrationDeadline'])) ?>).
                        </div>
                    <?php elseif (!$isRegistered && $state['closed_reason'] === 'full'): ?>
                        <div class="alert alert-warning small mb-0 mt-3">
                            <i class="bi bi-people-fill me-1"></i> Sự kiện đã hết chỗ.
                        </div>
                    <?php elseif ($isRegistered && !$canCancelReg && $evt['Status'] === 'Published'): ?>
                        <div class="alert alert-light border small mb-0 mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            <?= $attended ? 'Bạn đã được điểm danh tại sự kiện này.' : 'Sự kiện đã bắt đầu nên không thể hủy đăng ký.' ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
