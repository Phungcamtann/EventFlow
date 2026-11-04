<?php
/**
 * EventFlow — Màn hình Đăng nhập [SCR02]
 * Đáp ứng: FR02, UC02, PB02, PB03
 * Thiết kế bám sát chuẩn Prototype Figma (Màn hình 01 · Đăng nhập)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

// Thông tin tài khoản hiện tại (nếu đang có phiên đăng nhập)
$loggedInUser = is_logged_in() ? current_user() : null;

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra CSRF Token
    if (!verify_csrf_token()) {
        $errors[] = 'Mã bảo mật CSRF không hợp lệ hoặc phiên đã hết hạn. Vui lòng thử lại.';
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '') {
            $errors[] = 'Vui lòng nhập Email hoặc Mã số sinh viên (MSSV).';
        }
        if ($password === '') {
            $errors[] = 'Vui lòng nhập mật khẩu.';
        }

        if (empty($errors)) {
            try {
                $pdo = get_db_connection();

                // 2. Tìm tài khoản theo Email HOẶC MSSV (StudentCode) kèm tên vai trò
                // Dùng 2 named parameter khác nhau để tương thích với PDO native prepare
                $stmt = $pdo->prepare('
                    SELECT a.*, r.RoleName 
                    FROM Account a 
                    INNER JOIN Role r ON a.RoleID = r.RoleID 
                    WHERE (a.Email = :emailId OR a.StudentCode = :codeId)
                    LIMIT 1
                ');
                $stmt->execute([
                    'emailId' => $identifier,
                    'codeId'  => $identifier
                ]);
                $account = $stmt->fetch();

                if (!$account) {
                    $errors[] = 'Email/MSSV hoặc mật khẩu không chính xác.';
                } else {
                    // 3. Kiểm tra mật khẩu
                    if (!password_verify($password, $account['PasswordHash'])) {
                        $errors[] = 'Email/MSSV hoặc mật khẩu không chính xác.';
                    } else {
                        // 4. Kiểm tra trạng thái kích hoạt tài khoản (BR02)
                        if ((int)$account['IsActive'] !== 1) {
                            $errors[] = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Quản trị viên hệ thống để được hỗ trợ.';
                        } else {
                            // 5. Kiểm tra thông tin tầng 2: Tư cách thành viên CLB (nếu có)
                            $clubMemberStmt = $pdo->prepare('
                                SELECT MemberRole, Status, ClubID 
                                FROM Club_Member 
                                WHERE AccountID = :accountId 
                                LIMIT 1
                            ');
                            $clubMemberStmt->execute(['accountId' => $account['AccountID']]);
                            $clubMember = $clubMemberStmt->fetch() ?: null;

                            // 6. Đăng nhập và lưu thông tin vào Session
                            login_user($account, $clubMember);

                            set_flash('success', 'Chào mừng trở lại, ' . $account['FullName'] . '!');

                            // 7. Điều hướng theo vai trò người dùng
                            if ($account['RoleName'] === 'System Administrator') {
                                header('Location: ' . base_url('admin/accounts.php'));
                            } else {
                                header('Location: ' . base_url('index.php'));
                            }
                            exit;
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log('[EventFlow Login Error] ' . $e->getMessage());
                $errors[] = 'Đã xảy ra lỗi khi xác thực tài khoản. Vui lòng thử lại sau.';
            }
        }
    }
}

$pageTitle = 'Đăng nhập';
$hideNavbar = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center align-items-stretch g-0 rounded-4 overflow-hidden shadow-lg border">
        <!-- CỘT TRÁI: BANNER THƯƠNG HIỆU THEO FIGMA 01 -->
        <div class="col-lg-6 d-none d-lg-block">
            <div class="ef-login-brand-panel">
                <div class="ef-login-brand-circle-1"></div>
                <div class="ef-login-brand-circle-2"></div>

                <!-- Top Brand Info -->
                <div class="position-relative z-1">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <div class="ef-logo-icon bg-white text-primary">E</div>
                        <span class="fs-4 fw-bold text-white tracking-tight">EventFlow</span>
                    </div>

                    <div class="mb-4">
                        <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-3 py-2 rounded-pill font-mono small fw-normal">
                            Dành cho câu lạc bộ sinh viên
                        </span>
                    </div>

                    <h2 class="display-6 fw-bold text-white mb-3" style="line-height: 1.25;">
                        Gắn kết mọi<br>hoạt động.
                    </h2>
                    <p class="text-white-50 fs-6 mb-0" style="max-width: 420px; line-height: 1.6;">
                        Khám phá sự kiện thú vị, đăng ký dễ dàng và cùng tạo nên những trải nghiệm đáng nhớ.
                    </p>
                </div>

                <!-- Bottom Event Preview Card (Theo đúng Figma 01) -->
                <div class="position-relative z-1 mt-5">
                    <div class="card bg-white p-3 border-0 shadow-sm rounded-4 text-dark">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge bg-light text-secondary rounded-pill font-mono small mb-2 px-2 py-1" style="font-size: 0.7rem;">
                                    SỰ KIỆN SẮP DIỄN RA
                                </span>
                                <h3 class="h6 fw-bold text-dark mb-1">English Speaking Circle</h3>
                                <p class="text-secondary small mb-2">Thứ bảy, 14.11.2026 · 09:00</p>
                                <span class="badge-pill-custom badge-open" style="font-size: 0.725rem;">
                                    Còn chỗ tham gia
                                </span>
                            </div>
                            <div class="bg-primary-subtle text-primary fw-bold rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; font-size: 1.35rem;">
                                14
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: FORM ĐĂNG NHẬP THEO FIGMA 01 -->
        <div class="col-lg-6 bg-white p-4 p-md-5 d-flex flex-column justify-content-center">
            <div style="max-width: 420px; margin: 0 auto; width: 100%;">
                <div class="mb-4">
                    <h1 class="h3 fw-bold text-dark mb-2">Chào mừng trở lại</h1>
                    <p class="text-secondary small mb-0">Đăng nhập để tiếp tục hành trình cùng EventFlow.</p>
                </div>

                <!-- Thông báo nếu đang có phiên đăng nhập của tài khoản khác -->
                <?php if ($loggedInUser): ?>
                    <div class="alert alert-info border-0 rounded-3 py-2 px-3 mb-4 small d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-person-check-fill me-1"></i> Đang đăng nhập: <strong><?= htmlspecialchars($loggedInUser['FullName']) ?></strong> (<?= htmlspecialchars($loggedInUser['RoleName']) ?>)
                        </div>
                        <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-sm btn-outline-danger py-0 px-2 text-nowrap ms-2" style="font-size: 0.75rem;">
                            Đăng xuất
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Hiển thị thông báo lỗi nếu có -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 rounded-3 shadow-none py-2 px-3 mb-4">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                            <span class="fw-semibold small">Lỗi đăng nhập:</span>
                        </div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('auth/login.php') ?>" novalidate>
                    <?= csrf_field() ?>

                    <!-- Email hoặc Mã sinh viên -->
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Email hoặc mã sinh viên</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" value="<?= htmlspecialchars($identifier) ?>" placeholder="nhap.email@example.com" required autofocus>
                    </div>

                    <!-- Mật khẩu -->
                    <div class="mb-2">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required>
                    </div>

                    <!-- Quên mật khẩu? (Right-aligned theo Figma) -->
                    <div class="d-flex justify-content-end mb-4">
                        <a href="javascript:void(0)" onclick="alert('Tính năng đặt lại mật khẩu trực tuyến (FR03) thuộc giai đoạn sau. Trong phiên bản MVP, vui lòng liên hệ Ban quản trị qua email: admin@ueh.edu.vn để được hỗ trợ cấp lại mật khẩu.')" class="small text-secondary text-decoration-none">
                            Quên mật khẩu?
                        </a>
                    </div>

                    <!-- Nút Đăng nhập (Primary button full-width theo Figma 01) -->
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            Đăng nhập
                        </button>
                    </div>

                    <!-- Link Đăng ký tài khoản (Figma 01 footer) -->
                    <div class="text-center text-muted small">
                        Chưa có tài khoản? 
                        <a href="<?= base_url('auth/register.php') ?>" class="text-primary fw-semibold ms-1">Đăng ký tài khoản</a>
                    </div>
                </form>

                <!-- Box thông tin tài khoản demo nhanh cho người dùng/giám khảo test -->
                <div class="mt-5 pt-4 border-top">
                    <p class="small text-muted fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i> Tài khoản mẫu dùng thử (Mật khẩu: <code>Password@123</code>):</p>
                    <div class="d-flex flex-wrap gap-1 small">
                        <span class="badge bg-light text-dark border font-mono">admin@ueh.edu.vn (Admin)</span>
                        <span class="badge bg-light text-dark border font-mono">manager@st.ueh.edu.vn (Manager)</span>
                        <span class="badge bg-light text-dark border font-mono">supporter@st.ueh.edu.vn (Supporter)</span>
                        <span class="badge bg-light text-dark border font-mono">minhan@st.ueh.edu.vn (Member)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
