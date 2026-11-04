<?php
/**
 * EventFlow — Quản trị Tài khoản & Quyền Hệ thống [SCR16]
 * Dành riêng cho System Administrator (Actor A4)
 * Đáp ứng: FR17, UC14, PB04
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

// Kiểm tra quyền nghiêm ngặt: Chỉ System Administrator mới được phép truy cập
require_role('System Administrator');

$pdo = get_db_connection();
$errors = [];
$successMessage = '';

// Lấy danh sách tất cả vai trò hệ thống để đổ vào dropdown
$roles = $pdo->query('SELECT * FROM Role ORDER BY RoleID ASC')->fetchAll();

// ============================================================================
// XỬ LÝ CÁC HÀNH ĐỘNG POST (Thêm mới, Đổi vai trò, Khóa/Mở khóa)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã bảo mật CSRF không hợp lệ hoặc đã hết hạn.');
        header('Location: ' . base_url('admin/accounts.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. THÊM TÀI KHOẢN MỚI
    if ($action === 'create') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $studentCode = trim($_POST['student_code'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 4);
        $password = $_POST['password'] ?? '';

        if ($fullName === '') {
            $errors[] = 'Họ và tên không được để trống.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
        }

        if (empty($errors)) {
            // Kiểm tra trùng Email
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM Account WHERE Email = :email');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Email này đã tồn tại trong hệ thống.';
            }

            // Kiểm tra trùng MSSV nếu có nhập
            if ($studentCode !== '') {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Account WHERE StudentCode = :studentCode');
                $stmt->execute(['studentCode' => $studentCode]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Mã số sinh viên này đã tồn tại trong hệ thống.';
                }
            }

            if (empty($errors)) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $finalStudentCode = ($studentCode !== '') ? $studentCode : null;

                $stmt = $pdo->prepare('
                    INSERT INTO Account (Email, PasswordHash, FullName, StudentCode, RoleID, IsActive)
                    VALUES (:email, :passwordHash, :fullName, :studentCode, :roleId, 1)
                ');
                $stmt->execute([
                    'email'        => $email,
                    'passwordHash' => $passwordHash,
                    'fullName'     => $fullName,
                    'studentCode'  => $finalStudentCode,
                    'roleId'       => $roleId
                ]);

                set_flash('success', "Đã tạo mới thành công tài khoản [{$fullName}].");
                header('Location: ' . base_url('admin/accounts.php'));
                exit;
            }
        }
    }

    // 2. CẬP NHẬT VAI TRÒ HỆ THỐNG
    elseif ($action === 'update_role') {
        $accountId = (int)($_POST['account_id'] ?? 0);
        $newRoleId = (int)($_POST['new_role_id'] ?? 0);

        // Quy tắc BR02: Chặn Admin tự giáng quyền của chính mình
        if ($accountId === current_user_id() && $newRoleId !== 1) {
            set_flash('danger', 'Quy tắc BR02: Bạn không thể tự hạ quyền Quản trị viên của chính tài khoản mình đang đăng nhập.');
            header('Location: ' . base_url('admin/accounts.php'));
            exit;
        }

        if ($accountId > 0 && $newRoleId > 0) {
            $stmt = $pdo->prepare('UPDATE Account SET RoleID = :roleId WHERE AccountID = :accountId');
            $stmt->execute(['roleId' => $newRoleId, 'accountId' => $accountId]);

            set_flash('success', "Đã cập nhật vai trò hệ thống cho tài khoản #{$accountId}.");
            header('Location: ' . base_url('admin/accounts.php'));
            exit;
        }
    }

    // 3. KHÓA / MỞ KHÓA TÀI KHOẢN (IS_ACTIVE)
    elseif ($action === 'toggle_status') {
        $accountId = (int)($_POST['account_id'] ?? 0);
        $currentStatus = (int)($_POST['current_status'] ?? 1);
        $newStatus = ($currentStatus === 1) ? 0 : 1;

        // Quy tắc BR02: Tuyệt đối không cho phép Admin tự khóa tài khoản của chính mình
        if ($accountId === current_user_id()) {
            set_flash('danger', 'Quy tắc BR02: Bạn không thể tự khóa tài khoản Quản trị viên của chính mình.');
            header('Location: ' . base_url('admin/accounts.php'));
            exit;
        }

        if ($accountId > 0) {
            $stmt = $pdo->prepare('UPDATE Account SET IsActive = :status WHERE AccountID = :accountId');
            $stmt->execute(['status' => $newStatus, 'accountId' => $accountId]);

            $statusText = ($newStatus === 1) ? 'Mở khóa' : 'Khóa';
            set_flash('success', "Đã {$statusText} thành công tài khoản #{$accountId}.");
            header('Location: ' . base_url('admin/accounts.php'));
            exit;
        }
    }
}

// ============================================================================
// LẤY DỮ LIỆU HIỂN THỊ (Bộ lọc, Tìm kiếm & Thống kê)
// ============================================================================
$search = trim($_GET['search'] ?? '');
$filterRole = !empty($_GET['role_id']) ? (int)$_GET['role_id'] : 0;

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(a.FullName LIKE :s_name OR a.Email LIKE :s_email OR a.StudentCode LIKE :s_code)';
    $params['s_name']  = "%{$search}%";
    $params['s_email'] = "%{$search}%";
    $params['s_code']  = "%{$search}%";
}

if ($filterRole > 0) {
    $whereClauses[] = 'a.RoleID = :filter_role';
    $params['filter_role'] = $filterRole;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$stmt = $pdo->prepare("
    SELECT a.*, r.RoleName 
    FROM Account a
    INNER JOIN Role r ON a.RoleID = r.RoleID
    {$whereSql}
    ORDER BY a.AccountID ASC
");
$stmt->execute($params);
$accounts = $stmt->fetchAll();

// Thống kê số lượng theo vai trò
$stats = [
    'total'        => (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn(),
    'admin'        => (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE RoleID = 1')->fetchColumn(),
    'manager'      => (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE RoleID = 2')->fetchColumn(),
    'member'       => (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE RoleID = 3')->fetchColumn(),
    'participant'  => (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE RoleID = 4')->fetchColumn(),
    'inactive'     => (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE IsActive = 0')->fetchColumn()
];

$pageTitle = 'Quản trị tài khoản & Quyền hệ thống';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- PAGE HEADER -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger-subtle text-danger font-mono px-2 py-1 rounded-pill small">SCR16 · FR17</span>
                <span class="text-secondary small">System Administrator</span>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">Quản trị tài khoản & Quyền hệ thống</h1>
            <p class="text-secondary small mb-0">Quản lý người dùng, cấp đổi vai trò hệ thống và trạng thái hoạt động.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                <i class="bi bi-person-plus-fill me-1"></i> Thêm tài khoản mới
            </button>
        </div>
    </div>

    <!-- HIỂN THỊ LỖI NẾU CÓ -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger border-0 rounded-3 shadow-sm py-2 px-3 mb-4">
            <div class="fw-semibold small mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i> Đã xảy ra lỗi:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- STATS / KPI CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm h-100">
                <div class="text-secondary small">Tổng tài khoản</div>
                <div class="h3 fw-bold text-dark mb-0 mt-1"><?= $stats['total'] ?></div>
                <small class="text-muted mt-1" style="font-size: 0.75rem;">
                    Bị khóa: <strong class="text-danger"><?= $stats['inactive'] ?></strong>
                </small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-start border-primary border-4">
                <div class="text-secondary small">Club Manager</div>
                <div class="h3 fw-bold text-primary mb-0 mt-1"><?= $stats['manager'] ?></div>
                <small class="text-muted mt-1" style="font-size: 0.75rem;">Ban chủ nhiệm CLB</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-start border-success border-4">
                <div class="text-secondary small">Club Member</div>
                <div class="h3 fw-bold text-success mb-0 mt-1"><?= $stats['member'] ?></div>
                <small class="text-muted mt-1" style="font-size: 0.75rem;">Thành viên chính thức</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm h-100 border-start border-info border-4">
                <div class="text-secondary small">Event Participant</div>
                <div class="h3 fw-bold text-info mb-0 mt-1"><?= $stats['participant'] ?></div>
                <small class="text-muted mt-1" style="font-size: 0.75rem;">Người tham gia vãng lai</small>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR CARD -->
    <div class="card p-3 border-0 shadow-sm mb-4">
        <form method="GET" action="<?= base_url('admin/accounts.php') ?>" class="row g-2 align-items-center">
            <div class="col-md-6 col-lg-7">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm theo Họ tên, Email hoặc MSSV...">
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <select name="role_id" class="form-select">
                    <option value="0">-- Tất cả vai trò --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['RoleID'] ?>" <?= ($filterRole === (int)$r['RoleID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['RoleName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    Lọc
                </button>
                <?php if ($search !== '' || $filterRole > 0): ?>
                    <a href="<?= base_url('admin/accounts.php') ?>" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ACCOUNTS TABLE CARD -->
    <div class="card border-0 shadow-sm overflow-hidden mb-5">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0">Danh sách tài khoản hệ thống (<?= count($accounts) ?>)</h2>
            <span class="badge bg-light text-secondary border font-mono small">
                Đang quản lý: <?= $stats['total'] ?> tài khoản
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small text-uppercase font-mono">
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>Họ và tên / MSSV</th>
                        <th>Email</th>
                        <th>Vai trò hệ thống</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-end" style="width: 180px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Không tìm thấy tài khoản nào khớp với điều kiện tìm kiếm.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): 
                            $isCurrentAdmin = ((int)$acc['AccountID'] === current_user_id());
                            // Định nghĩa màu sắc badge theo vai trò
                            $roleBadgeClass = match ((int)$acc['RoleID']) {
                                1 => 'bg-purple-100 text-purple-800 border-purple-200',
                                2 => 'bg-amber-100 text-amber-800 border-amber-200',
                                3 => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                default => 'bg-sky-100 text-sky-800 border-sky-200'
                            };
                        ?>
                            <tr>
                                <td class="font-mono small text-muted fw-bold">#<?= $acc['AccountID'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        <?= htmlspecialchars($acc['FullName']) ?>
                                        <?php if ($isCurrentAdmin): ?>
                                            <span class="badge bg-danger-subtle text-danger rounded-pill" style="font-size: 0.65rem;">Bạn</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-secondary font-mono">
                                        <?= !empty($acc['StudentCode']) ? htmlspecialchars($acc['StudentCode']) : '—' ?>
                                    </small>
                                </td>
                                <td class="font-mono small"><?= htmlspecialchars($acc['Email']) ?></td>
                                <td>
                                    <span class="badge rounded-pill border px-2 py-1 small fw-semibold <?= $roleBadgeClass ?>" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($acc['RoleName']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ((int)$acc['IsActive'] === 1): ?>
                                        <span class="badge-pill-custom badge-open">
                                            <i class="bi bi-check-circle-fill"></i> Hoạt động
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-pill-custom badge-absent">
                                            <i class="bi bi-slash-circle-fill"></i> Đã khóa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted font-mono">
                                    <?= date('d/m/Y', strtotime($acc['CreatedAt'])) ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Nút đổi vai trò -->
                                        <button type="button" class="btn btn-outline-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editRoleModal" 
                                                data-id="<?= $acc['AccountID'] ?>" 
                                                data-name="<?= htmlspecialchars($acc['FullName']) ?>" 
                                                data-role="<?= $acc['RoleID'] ?>"
                                                title="Đổi vai trò hệ thống">
                                            <i class="bi bi-shield-shaded"></i> Vai trò
                                        </button>

                                        <!-- Nút Khóa / Mở khóa tài khoản -->
                                        <?php if ($isCurrentAdmin): ?>
                                            <button type="button" class="btn btn-outline-secondary" disabled title="Quy tắc BR02: Không thể tự khóa chính mình">
                                                <i class="bi bi-lock-fill"></i>
                                            </button>
                                        <?php else: ?>
                                            <form method="POST" action="<?= base_url('admin/accounts.php') ?>" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn <?= ((int)$acc['IsActive'] === 1) ? 'KHÓA' : 'MỞ KHÓA' ?> tài khoản này không?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="account_id" value="<?= $acc['AccountID'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $acc['IsActive'] ?>">
                                                
                                                <?php if ((int)$acc['IsActive'] === 1): ?>
                                                    <button type="submit" class="btn btn-outline-danger" title="Khóa tài khoản">
                                                        <i class="bi bi-lock-fill"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-outline-success" title="Mở khóa tài khoản">
                                                        <i class="bi bi-unlock-fill"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: THÊM TÀI KHOẢN MỚI
     ========================================================================== -->
<div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('admin/accounts.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold" id="createAccountModalLabel">Thêm tài khoản hệ thống mới</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" placeholder="Ví dụ: Lê Thị Mai" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" placeholder="mai.le@st.ueh.edu.vn" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mã số sinh viên (MSSV)</label>
                        <input type="text" class="form-control" name="student_code" placeholder="Ví dụ: 31241028888">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vai trò hệ thống <span class="text-danger">*</span></label>
                        <select class="form-select" name="role_id" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['RoleID'] ?>" <?= ((int)$r['RoleID'] === 4) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['RoleName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Mật khẩu ban đầu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" placeholder="Tối thiểu 8 ký tự" minlength="8" required value="Password@123">
                        <div class="form-text small">Mặc định gợi ý: <code>Password@123</code></div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary px-4">Tạo tài khoản</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: ĐỔI VAI TRÒ HỆ THỐNG
     ========================================================================== -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form method="POST" action="<?= base_url('admin/accounts.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="account_id" id="editRoleAccountId" value="">

                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold" id="editRoleModalLabel">Cấp đổi vai trò hệ thống</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-secondary mb-3">
                        Đang điều chỉnh quyền cho tài khoản: <strong id="editRoleAccountName" class="text-dark"></strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Vai trò hệ thống mới <span class="text-danger">*</span></label>
                        <select class="form-select" name="new_role_id" id="editRoleSelect" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['RoleID'] ?>">
                                    <?= htmlspecialchars($r['RoleName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small border-0 rounded-3 mb-0">
                        <i class="bi bi-shield-exclamation me-1"></i> Lưu ý: Thay đổi vai trò hệ thống sẽ ảnh hưởng ngay lập tức đến quyền truy cập vào các module của người dùng.
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Lưu vai trò</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script gán dữ liệu vào Modal Đổi vai trò -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editRoleModal = document.getElementById('editRoleModal');
    if (editRoleModal) {
        editRoleModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const accountId = button.getAttribute('data-id');
            const accountName = button.getAttribute('data-name');
            const roleId = button.getAttribute('data-role');

            document.getElementById('editRoleAccountId').value = accountId;
            document.getElementById('editRoleAccountName').textContent = accountName;
            document.getElementById('editRoleSelect').value = roleId;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
