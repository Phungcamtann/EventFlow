<?php
/**
 * EventFlow — Hồ sơ cá nhân & Xem tư cách thành viên
 * Phân quyền: Mọi người dùng đã đăng nhập (System Administrator, Club Manager, Club Member, Event Participant)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_login();

// Nếu là Cán bộ BĐH hoặc Admin: Điều hướng vào Single Workspace Container
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (is_club_manager() || is_authorized_club_member() || is_system_admin())) {
    header('Location: ' . base_url('app/modules/dashboard/index.php#profile'));
    exit;
}

$pdo = get_db_connection();
$userId = (int)current_user_id();
$profileUrl = base_url('app/modules/profile/index.php');
$h = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

$profileErrors = [];
$passwordErrors = [];

// ============================================================================
// XỬ LÝ POST REQUEST: CẬP NHẬT HỒ SƠ & ĐỔI MẬT KHẨU
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.');
        header('Location: ' . $profileUrl);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. CẬP NHẬT THÔNG TIN CÁ NHÂN
    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');

        if ($fullName === '') {
            $profileErrors[] = 'Họ và tên không được để trống.';
        } elseif (mb_strlen($fullName) > 100) {
            $profileErrors[] = 'Họ và tên không được vượt quá 100 ký tự.';
        }

        if (empty($profileErrors)) {
            $upStmt = $pdo->prepare('UPDATE Account SET FullName = :name WHERE AccountID = :id');
            $upStmt->execute([
                ':name' => $fullName,
                ':id'   => $userId
            ]);

            // Cập nhật lại session hiện tại
            $_SESSION['user']['FullName'] = $fullName;

            set_flash('success', 'Cập nhật thông tin cá nhân thành công.');
            header('Location: ' . $profileUrl);
            exit;
        }
    }

    // 2. ĐỔI MẬT KHẨU
    elseif ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $passwordErrors[] = 'Vui lòng nhập mật khẩu hiện tại.';
        }
        if (strlen($newPassword) < 8) {
            $passwordErrors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
        }
        if ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'Mật khẩu xác nhận không khớp với mật khẩu mới.';
        }
        if ($newPassword !== '' && $currentPassword !== '' && $newPassword === $currentPassword) {
            $passwordErrors[] = 'Mật khẩu mới không được trùng với mật khẩu hiện tại.';
        }

        if (empty($passwordErrors)) {
            // Lấy hash mật khẩu hiện tại từ DB
            $accStmt = $pdo->prepare('SELECT PasswordHash FROM Account WHERE AccountID = :id LIMIT 1');
            $accStmt->execute([':id' => $userId]);
            $accRow = $accStmt->fetch();

            if (!$accRow || !password_verify($currentPassword, $accRow['PasswordHash'])) {
                $passwordErrors[] = 'Mật khẩu hiện tại không chính xác.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $pwdStmt = $pdo->prepare('UPDATE Account SET PasswordHash = :hash WHERE AccountID = :id');
                $pwdStmt->execute([':hash' => $newHash, ':id' => $userId]);

                set_flash('success', 'Đổi mật khẩu thành công. Vui lòng sử dụng mật khẩu mới cho các lần đăng nhập tiếp theo.');
                header('Location: ' . $profileUrl);
                exit;
            }
        }
    }
}

// ============================================================================
// LẤY DỮ LIỆU ĐẦY ĐỦ CỦA NGƯỜI DÙNG TỪ CSDL
// ============================================================================
$stmt = $pdo->prepare('
    SELECT a.*, r.RoleName
    FROM Account a
    JOIN Role r ON a.RoleID = r.RoleID
    WHERE a.AccountID = :id
    LIMIT 1
');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('danger', 'Không tìm thấy thông tin tài khoản.');
    header('Location: ' . base_url('index.php'));
    exit;
}

// Truy vấn thông tin tư cách thành viên CLB (nếu có)
$clubMemberStmt = $pdo->prepare('
    SELECT cm.*, c.ClubName, c.ContactInfo
    FROM Club_Member cm
    JOIN Club c ON cm.ClubID = c.ClubID
    WHERE cm.AccountID = :id
    LIMIT 1
');
$clubMemberStmt->execute([':id' => $userId]);
$membership = $clubMemberStmt->fetch();

// Thống kê nhanh hoạt động cá nhân
$regCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM Event_Registration 
    WHERE AccountID = :id AND Status = 'Registered'
");
$regCountStmt->execute([':id' => $userId]);
$totalRegisteredEvents = (int)$regCountStmt->fetchColumn();

$attendedCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM Attendance a
    JOIN Event_Registration r ON a.RegID = r.RegID
    WHERE r.AccountID = :id AND a.AttendanceStatus = 1
");
$attendedCountStmt->execute([':id' => $userId]);
$totalAttendedEvents = (int)$attendedCountStmt->fetchColumn();

// Tính avatar viết tắt
$nameParts = explode(' ', trim($user['FullName']));
$initials = '';
if (count($nameParts) >= 2) {
    $initials = mb_substr($nameParts[count($nameParts) - 2], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1);
} else {
    $initials = mb_substr($user['FullName'], 0, 2);
}
$initials = strtoupper($initials);

$startInEditMode = !empty($profileErrors);

$pageTitle = 'Hồ sơ cá nhân — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Hồ sơ cá nhân</li>
        </ol>
    </nav>

    <!-- Header Tiêu đề -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-secondary small">Quản lý tài khoản cá nhân</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Hồ sơ cá nhân & Tư cách thành viên</h1>
            <p class="text-muted small mb-0">Xem và cập nhật thông tin định danh, kiểm tra vai trò CLB và quản lý mật khẩu.</p>
        </div>
        <?php if (!is_system_admin()): ?>
        <div>
            <a href="<?= base_url('app/modules/events/my_events.php') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-calendar3 me-1"></i> Sự kiện của tôi
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- CỘT TRÁI: THẺ ĐỊNH DANH & TƯ CÁCH THÀNH VIÊN CLB -->
        <div class="col-lg-4">
            <!-- Thẻ tổng quan người dùng -->
            <div class="card border-0 shadow-sm rounded-4 text-center p-4 mb-4 bg-white">
                <div class="d-flex justify-content-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm"
                         style="width: 84px; height: 84px; background: linear-gradient(135deg, #4F46E5 0%, #312E81 100%); color: #ffffff; font-size: 2rem;">
                        <?= $h($initials) ?>
                    </div>
                </div>

                <h2 class="h5 fw-bold text-dark mb-1" id="displayHeaderName"><?= $h($user['FullName']) ?></h2>
                <p class="text-muted small mb-3"><?= $h($user['Email']) ?></p>

                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill font-mono">
                        <i class="bi bi-shield-check me-1"></i> <?= $h($user['RoleName']) ?>
                    </span>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill font-mono" id="badgeStudentCode" style="<?= empty($user['StudentCode']) ? 'display:none;' : '' ?>">
                        <i class="bi bi-person-badge me-1"></i> <span id="textStudentCodeBadge"><?= $h($user['StudentCode']) ?></span>
                    </span>
                </div>

                <hr class="my-3 opacity-25">

                <div class="row text-center g-2">
                    <div class="col-6">
                        <div class="p-2 rounded-3 bg-light">
                            <div class="small text-muted">Đã đăng ký</div>
                            <div class="h5 fw-bold text-primary font-mono mb-0"><?= $totalRegisteredEvents ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded-3 bg-light">
                            <div class="small text-muted">Đã tham dự</div>
                            <div class="h5 fw-bold text-success font-mono mb-0"><?= $totalAttendedEvents ?></div>
                        </div>
                    </div>
                </div>

                <div class="text-start mt-4 pt-3 border-top small text-muted">
                    <div class="mb-2">
                        <i class="bi bi-calendar-check me-2 text-primary"></i> Ngày tạo tài khoản:
                        <span class="text-dark fw-medium"><?= $h(event_fmt_dt($user['CreatedAt'])) ?></span>
                    </div>
                    <div>
                        <i class="bi bi-circle-fill me-2 <?= $user['IsActive'] ? 'text-success' : 'text-danger' ?>" style="font-size: 0.65rem;"></i> Trạng thái tài khoản:
                        <span class="fw-semibold <?= $user['IsActive'] ? 'text-success' : 'text-danger' ?>">
                            <?= $user['IsActive'] ? 'Đang hoạt động' : 'Bị khóa' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Thẻ Tư cách thành viên CLB -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div>
                        <h3 class="h6 fw-bold text-dark mb-0">Tư cách thành viên CLB</h3>
                        <small class="text-muted">Thông tin sinh hoạt nội bộ</small>
                    </div>
                </div>

                <?php if ($membership): ?>
                    <div class="p-3 rounded-3 border bg-light mb-3">
                        <div class="fw-bold text-dark mb-1">
                            <i class="bi bi-mortarboard me-1 text-primary"></i> <?= $h($membership['ClubName']) ?>
                        </div>
                        <div class="small text-muted mb-2">
                            <?= $h($membership['ContactInfo'] ?? 'Câu lạc bộ Tiếng Anh UEH') ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 font-mono">
                                <?= $h(format_club_member_role($membership['MemberRole'])) ?>
                            </span>
                            <?php if ($membership['Status'] === 'Active'): ?>
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">Đang hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Tạm ngừng</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($membership['JoinedDate'])): ?>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-clock-history me-1"></i> Ngày gia nhập: <strong><?= date('d/m/Y', strtotime($membership['JoinedDate'])) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (in_array($membership['MemberRole'], ['Board Member', 'Event Supporter'], true)): ?>
                        <div class="alert alert-info py-2 px-3 small rounded-3 mb-0">
                            <i class="bi bi-info-circle-fill me-1"></i> Bạn được ủy quyền hỗ trợ công tác điểm danh và quản lý danh sách đăng ký sự kiện.
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="p-4 rounded-3 border border-dashed text-center text-muted">
                        <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                        <p class="small mb-2">Bạn hiện đang tham gia với tư cách <strong>Khách tham dự sự kiện (Event Participant)</strong>.</p>
                        <p class="small text-secondary mb-0">Để trở thành Thành viên chính thức của CLB, vui lòng liên hệ Ban chủ nhiệm trong các đợt tuyển thành viên mới.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CỘT PHẢI: FORM CẬP NHẬT THÔNG TIN & FORM ĐỔI MẬT KHẨU -->
        <div class="col-lg-8">
            <!-- Khối 1: Thông tin cá nhân (Có nút chỉnh sửa ở góc trên cùng bên phải) -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white" id="cardProfileInfo">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded-3 bg-primary-subtle text-primary">
                            <i class="bi bi-person-lines-fill fs-5"></i>
                        </div>
                        <div>
                            <h3 class="h5 fw-bold text-dark mb-0">Thông tin cá nhân</h3>
                            <small class="text-muted">Họ tên, mã số sinh viên và địa chỉ email định danh</small>
                        </div>
                    </div>
                    <!-- Nút bật/tắt chế độ chỉnh sửa -->
                    <div>
                        <button type="button" id="btnEditProfile" class="btn btn-sm btn-outline-primary <?= $startInEditMode ? 'd-none' : '' ?>">
                            <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
                        </button>
                        <button type="button" id="btnCancelEditProfile" class="btn btn-sm btn-outline-secondary <?= $startInEditMode ? '' : 'd-none' ?>">
                            <i class="bi bi-x-lg me-1"></i> Hủy
                        </button>
                    </div>
                </div>

                <?php if (!empty($profileErrors)): ?>
                    <div class="alert alert-danger rounded-3 mb-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($profileErrors as $err): ?>
                                <li><?= $h($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- 1. CHẾ ĐỘ XEM THÔNG TIN (VIEW MODE) -->
                <div id="profileViewMode" class="<?= $startInEditMode ? 'd-none' : '' ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <small class="text-muted d-block mb-1">Họ và tên</small>
                                <span class="fw-bold text-dark fs-6" id="viewFullName"><?= $h($user['FullName']) ?></span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <small class="text-muted d-block mb-1">Mã số sinh viên (MSSV)</small>
                                <span class="fw-bold font-mono text-dark fs-6" id="viewStudentCode">
                                    <?= !empty($user['StudentCode']) ? $h($user['StudentCode']) : '<span class="text-muted fst-italic fw-normal">Chưa cập nhật</span>' ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded-3 bg-light border">
                                <small class="text-muted d-block mb-1">Địa chỉ Email</small>
                                <span class="fw-bold text-dark fs-6"><?= $h($user['Email']) ?></span>
                                <small class="text-muted d-block mt-1">Email là tên đăng nhập định danh duy nhất và không thể thay đổi.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. CHẾ ĐỘ CHỈNH SỬA (EDIT MODE FORM) -->
                <form method="POST" action="<?= $profileUrl ?>" id="profileEditForm" class="<?= $startInEditMode ? '' : 'd-none' ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fullName" class="form-label fw-semibold small text-dark">
                                Họ và tên <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="fullName" name="full_name"
                                   value="<?= $h($user['FullName']) ?>" required maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label for="studentCode" class="form-label fw-semibold small text-dark">
                                Mã số sinh viên (MSSV) <span class="badge bg-light text-muted border ms-1">Cố định</span>
                            </label>
                            <input type="text" class="form-control font-mono bg-light" id="studentCode"
                                   value="<?= $h($user['StudentCode'] ?? '') ?>" readonly>
                            <div class="form-text small">MSSV dùng để tra cứu nhanh khi tham gia điểm danh tại hội trường.</div>
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label fw-semibold small text-dark">
                                Địa chỉ Email <span class="badge bg-light text-muted border ms-1">Cố định</span>
                            </label>
                            <input type="email" class="form-control bg-light" id="email" value="<?= $h($user['Email']) ?>" readonly>
                            <div class="form-text small">Email là tên đăng nhập định danh duy nhất của tài khoản và không thể tự thay đổi.</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3" id="btnCancelEditBottom">
                            Hủy
                        </button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Khối 2: Đổi Mật khẩu (Có icon con mắt ẩn/hiện mật khẩu) -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded-3 bg-warning-subtle text-warning-emphasis">
                            <i class="bi bi-key-fill fs-5"></i>
                        </div>
                        <div>
                            <h3 class="h5 fw-bold text-dark mb-0">Đổi mật khẩu</h3>
                            <small class="text-muted">Thay đổi mật khẩu đăng nhập để bảo vệ an toàn cho tài khoản</small>
                        </div>
                    </div>
                </div>

                <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger rounded-3 mb-3">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($passwordErrors as $err): ?>
                                <li><?= $h($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= $profileUrl ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="currentPassword" class="form-label fw-semibold small text-dark">
                                Mật khẩu hiện tại <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required autocomplete="current-password" placeholder="Nhập mật khẩu đang dùng">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="currentPassword" tabindex="-1" title="Hiện/ẩn mật khẩu">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="newPassword" class="form-label fw-semibold small text-dark">
                                Mật khẩu mới <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="8" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="newPassword" tabindex="-1" title="Hiện/ẩn mật khẩu">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text small">Tối thiểu 8 ký tự, kết hợp chữ, số và ký tự đặc biệt.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="confirmPassword" class="form-label fw-semibold small text-dark">
                                Xác nhận mật khẩu mới <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required minlength="8" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
                                <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="confirmPassword" tabindex="-1" title="Hiện/ẩn mật khẩu">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-danger px-4">
                            <i class="bi bi-shield-lock me-1"></i> Cập nhật mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle giữa Chế độ xem và Chế độ chỉnh sửa thông tin cá nhân
(function () {
    const btnEdit = document.getElementById('btnEditProfile');
    const btnCancel = document.getElementById('btnCancelEditProfile');
    const btnCancelBottom = document.getElementById('btnCancelEditBottom');
    const viewMode = document.getElementById('profileViewMode');
    const editForm = document.getElementById('profileEditForm');

    function showEditMode() {
        if (viewMode && editForm) {
            viewMode.classList.add('d-none');
            editForm.classList.remove('d-none');
        }
        if (btnEdit && btnCancel) {
            btnEdit.classList.add('d-none');
            btnCancel.classList.remove('d-none');
        }
    }

    function showViewMode() {
        if (viewMode && editForm) {
            editForm.classList.add('d-none');
            viewMode.classList.remove('d-none');
        }
        if (btnEdit && btnCancel) {
            btnCancel.classList.add('d-none');
            btnEdit.classList.remove('d-none');
        }
    }

    if (btnEdit) btnEdit.addEventListener('click', showEditMode);
    if (btnCancel) btnCancel.addEventListener('click', showViewMode);
    if (btnCancelBottom) btnCancelBottom.addEventListener('click', showViewMode);
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
