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

// Mở trang chi tiết công khai (Khách vãng lai truy cập không bắt buộc đăng nhập)
$pdo         = get_db_connection();
$eventId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId      = is_logged_in() ? (int)current_user_id() : 0;
$detailUrl   = base_url('app/modules/events/detail.php?id=' . $eventId);
$canRegister = is_logged_in() && has_role(['Club Member', 'Event Participant']);
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

/**
 * Đăng ký sự kiện dành cho Khách vãng lai / Sinh viên chưa có tài khoản (UAT 02 / Luồng NV 3).
 * Kiểm tra chống trùng MSSV/Email và bảo vệ MaxCapacity bằng khóa dòng FOR UPDATE.
 */
function event_register_guest(PDO $pdo, int $eventId, string $fullName, string $studentCode, string $email, string $phone): array {
    $fail = static function (string $msg) use ($pdo): array {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, $msg];
    };

    $fullName    = trim($fullName);
    $studentCode = strtoupper(trim($studentCode));
    $email       = strtolower(trim($email));
    $phone       = trim($phone);

    if ($fullName === '' || $studentCode === '' || $email === '' || $phone === '') {
        return [false, 'Vui lòng điền đầy đủ tất cả các trường thông tin.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Định dạng email không hợp lệ.'];
    }
    if (!preg_match('/^[0-9]{9,11}$/', preg_replace('/[^\d]/', '', $phone))) {
        return [false, 'Số điện thoại không hợp lệ (yêu cầu 9 - 11 chữ số).'];
    }

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

        // 1. Chống trùng với lượt đăng ký khách vãng lai đã có
        $dupGuestStmt = $pdo->prepare("
            SELECT RegID, Status FROM Event_Registration
            WHERE EventID = :e 
              AND RegistrationType = 'Guest'
              AND (GuestStudentCode = :code OR LOWER(GuestEmail) = :email)
            FOR UPDATE
        ");
        $dupGuestStmt->execute([':e' => $eventId, ':code' => $studentCode, ':email' => $email]);
        $dupGuest = $dupGuestStmt->fetch();

        if ($dupGuest && $dupGuest['Status'] === 'Registered') {
            return $fail('MSSV hoặc Email này đã được đăng ký cho sự kiện này trước đó.');
        }

        // 2. Chống trùng với thành viên có tài khoản đã đăng ký
        $dupAccStmt = $pdo->prepare("
            SELECT r.RegID, r.Status 
            FROM Event_Registration r
            JOIN Account a ON a.AccountID = r.AccountID
            WHERE r.EventID = :e 
              AND (a.StudentCode = :code OR LOWER(a.Email) = :email)
              AND r.Status = 'Registered'
            FOR UPDATE
        ");
        $dupAccStmt->execute([':e' => $eventId, ':code' => $studentCode, ':email' => $email]);
        if ($dupAccStmt->fetch()) {
            return $fail('Sinh viên có MSSV hoặc Email này đã đăng ký sự kiện bằng tài khoản hệ thống.');
        }

        // 3. Kiểm tra số chỗ còn lại (bảo vệ capacity)
        $cntStmt = $pdo->prepare("
            SELECT COUNT(*) FROM Event_Registration
            WHERE EventID = :e AND Status = 'Registered'
        ");
        $cntStmt->execute([':e' => $eventId]);
        $registered = (int)$cntStmt->fetchColumn();

        if ($evt['MaxCapacity'] !== null && $registered >= (int)$evt['MaxCapacity']) {
            return $fail('Rất tiếc, sự kiện đã hết chỗ.');
        }

        // 4. Lưu lượt đăng ký
        if ($dupGuest) {
            $up = $pdo->prepare("
                UPDATE Event_Registration 
                SET Status = 'Registered', 
                    GuestName = :name, 
                    GuestPhone = :phone, 
                    GuestEmail = :email,
                    RegDate = NOW()
                WHERE RegID = :id
            ");
            $up->execute([
                ':name'  => $fullName,
                ':phone' => $phone,
                ':email' => $email,
                ':id'    => $dupGuest['RegID']
            ]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO Event_Registration 
                    (EventID, AccountID, RegistrationType, GuestName, GuestEmail, GuestPhone, GuestStudentCode, RegDate, Status)
                VALUES 
                    (:e, NULL, 'Guest', :name, :email, :phone, :code, NOW(), 'Registered')
            ");
            $ins->execute([
                ':e'     => $eventId,
                ':name'  => $fullName,
                ':email' => $email,
                ':phone' => $phone,
                ':code'  => $studentCode
            ]);
        }

        $pdo->commit();
        return [true, 'Đăng ký thành công! Thông tin của bạn đã được ghi nhận vào danh sách tham gia.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] guest register failed: ' . $e->getMessage());
        return [false, 'Không thể xử lý yêu cầu. Vui lòng thử lại sau.'];
    }
}

// ---------------------------------------------------------------------------
// Xử lý POST (Post/Redirect/Get)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif ($action === 'guest_register') {
        $gName  = trim($_POST['guest_name'] ?? '');
        $gCode  = trim($_POST['guest_student_code'] ?? '');
        $gEmail = trim($_POST['guest_email'] ?? '');
        $gPhone = trim($_POST['guest_phone'] ?? '');

        [$ok, $msg] = event_register_guest($pdo, $eventId, $gName, $gCode, $gEmail, $gPhone);
        set_flash($ok ? 'success' : 'danger', $msg);
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
                <?php 
                $detailBanner = !empty($evt['BannerImage']) ? $evt['BannerImage'] : 'assets/uploads/events/default_banner.jpg';
                $detailBannerExists = file_exists(__DIR__ . '/../../../' . $detailBanner);
                ?>
                <?php if ($detailBannerExists): ?>
                    <div class="position-relative overflow-hidden" style="max-height: 280px; background-color: #f1f5f9;">
                        <img src="<?= base_url($detailBanner) ?>" alt="<?= $h($evt['EventName']) ?>" class="w-100" style="height: 260px; object-fit: cover;">
                    </div>
                <?php endif; ?>

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
                            <div class="small text-muted"><i class="bi bi-calendar-event me-1 text-primary"></i> Thời gian diễn ra</div>
                            <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['StartTime'])) ?> — <?= $h(event_fmt_dt($evt['EndTime'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="small text-muted"><i class="bi bi-geo-alt me-1 text-primary"></i> Địa điểm</div>
                            <div class="fw-semibold text-dark"><?= $h($evt['Location']) ?></div>
                        </div>
                        <?php if (!empty($evt['RegistrationStartTime'])): ?>
                            <div class="col-sm-6">
                                <div class="small text-muted"><i class="bi bi-calendar-plus me-1 text-primary"></i> Mở đăng ký</div>
                                <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['RegistrationStartTime'])) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted"><i class="bi bi-hourglass-split me-1 text-primary"></i> Hạn chót đăng ký</div>
                                <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['RegistrationDeadline'])) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="col-sm-6">
                                <div class="small text-muted"><i class="bi bi-hourglass-split me-1 text-primary"></i> Hạn chót đăng ký</div>
                                <div class="fw-semibold text-dark"><?= $h(event_fmt_dt($evt['RegistrationDeadline'])) ?></div>
                            </div>
                        <?php endif; ?>
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

                    <?php if ($userId === 0): ?>
                        <?php if ($state['is_open']): ?>
                            <button type="button" class="btn btn-primary w-100 fw-semibold shadow-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalGuestRegister">
                                <i class="bi bi-pencil-square me-1"></i> Đăng ký tham gia (Khách vãng lai)
                            </button>
                            <div class="text-center small text-muted mb-3">
                                Đã có tài khoản? <a href="<?= base_url('app/modules/auth/login.php') ?>" class="text-primary text-decoration-none fw-semibold">Đăng nhập tại đây</a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border small mb-0">
                                <i class="bi bi-info-circle me-1"></i> Cổng đăng ký sự kiện hiện đang đóng.
                            </div>
                        <?php endif; ?>
                    <?php elseif (!$canRegister): ?>
                        <div class="alert alert-light border small mb-0">
                            <i class="bi bi-info-circle me-1"></i> Chỉ thành viên CLB và sinh viên tham gia mới đăng ký được sự kiện.
                        </div>
                    <?php elseif ($state['key'] === 'cancelled'): ?>
                        <div class="alert alert-danger small mb-0 mt-3">
                            <i class="bi bi-x-octagon me-1"></i> Sự kiện này đã bị hủy.
                            <?= $isRegistered ? 'Thông tin đăng ký của bạn vẫn được lưu lại.' : '' ?>
                        </div>
                    <?php elseif ($state['key'] === 'draft'): ?>
                        <div class="alert alert-secondary small mb-0 mt-3">
                            <i class="bi bi-file-earmark-text me-1"></i> Sự kiện đang ở chế độ bản nháp (chưa mở cho người dùng).
                        </div>
                    <?php elseif ($state['key'] === 'upcoming'): ?>
                        <div class="alert alert-primary-subtle border border-primary-subtle small mb-0 mt-3 text-primary">
                            <i class="bi bi-clock-history me-1"></i> Cổng đăng ký sẽ chính thức mở vào lúc <strong><?= $h(event_fmt_dt($evt['RegistrationStartTime'])) ?></strong>.
                        </div>
                    <?php elseif ($state['key'] === 'ongoing'): ?>
                        <div class="alert alert-info-subtle border border-info-subtle small mb-0 mt-3 text-info-emphasis">
                            <i class="bi bi-broadcast me-1"></i> Sự kiện đang diễn ra.
                        </div>
                    <?php elseif ($state['key'] === 'finished'): ?>
                        <div class="alert alert-secondary small mb-0 mt-3">
                            <i class="bi bi-calendar-check me-1"></i> Sự kiện đã kết thúc.
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

<!-- Modal Đăng Ký Khách Vãng Lai (UAT 02) -->
<div class="modal fade" id="modalGuestRegister" tabindex="-1" aria-labelledby="modalGuestRegisterLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-bold" id="modalGuestRegisterLabel">
                    <i class="bi bi-pencil-square me-2"></i> Đăng Ký Tham Gia Sự Kiện
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= $h($detailUrl) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="guest_register">
                <div class="modal-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i> Dành cho sinh viên chưa có tài khoản EventFlow. Thông tin sẽ được dùng để đối soát và điểm danh tại bàn check-in.
                    </div>

                    <div class="mb-3">
                        <label for="guest_name" class="form-label small fw-semibold">Họ và tên sinh viên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="guest_name" name="guest_name" placeholder="Nguyễn Văn A" required>
                    </div>

                    <div class="mb-3">
                        <label for="guest_student_code" class="form-label small fw-semibold">Mã số sinh viên (MSSV) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-3" id="guest_student_code" name="guest_student_code" placeholder="VD: 31241020055" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="guest_email" class="form-label small fw-semibold">Email liên hệ <span class="text-danger">*</span></label>
                            <input type="email" class="form-control rounded-3" id="guest_email" name="guest_email" placeholder="email@st.ueh.edu.vn" required>
                        </div>
                        <div class="col-md-6">
                            <label for="guest_phone" class="form-label small fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control rounded-3" id="guest_phone" name="guest_phone" placeholder="VD: 0901234567" required>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-3 small text-muted">
                        <i class="bi bi-shield-check text-success me-1"></i> Cam kết bảo mật thông tin & kiểm tra chống trùng tự động theo sự kiện.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Xác Nhận Đăng Ký
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
