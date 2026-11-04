<?php
/**
 * EventFlow — Màn hình Đăng ký tài khoản [SCR01]
 * Đáp ứng: FR01, UC01, PB01
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

// Nếu người dùng đã đăng nhập, chuyển hướng về trang chủ
if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$errors = [];
$fullName = '';
$email = '';
$studentCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra CSRF Token
    if (!verify_csrf_token()) {
        $errors[] = 'Mã bảo mật CSRF không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } else {
        // 2. Lấy và làm sạch dữ liệu đầu vào
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $studentCode = trim($_POST['student_code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // 3. Kiểm tra tính hợp lệ dữ liệu
        if ($fullName === '') {
            $errors[] = 'Vui lòng nhập họ và tên của bạn.';
        } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors[] = 'Họ và tên phải từ 2 đến 100 ký tự.';
        }

        if ($email === '') {
            $errors[] = 'Vui lòng nhập địa chỉ email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Địa chỉ email không đúng định dạng.';
        } elseif (mb_strlen($email) > 100) {
            $errors[] = 'Địa chỉ email không được vượt quá 100 ký tự.';
        }

        if ($studentCode !== '') {
            if (mb_strlen($studentCode) > 20) {
                $errors[] = 'Mã số sinh viên (MSSV) không được vượt quá 20 ký tự.';
            }
        }

        // Quy tắc BR01: Mật khẩu phải từ 8 ký tự trở lên
        if (strlen($password) < 8) {
            $errors[] = 'Mật khẩu phải chứa ít nhất 8 ký tự.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không trùng khớp.';
        }

        // 4. Nếu không có lỗi định dạng, kiểm tra trùng lặp trong CSDL
        if (empty($errors)) {
            try {
                $pdo = get_db_connection();

                // Kiểm tra trùng Email
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Account WHERE Email = :email');
                $stmt->execute(['email' => $email]);
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = 'Địa chỉ email này đã được sử dụng bởi một tài khoản khác.';
                }

                // Kiểm tra trùng MSSV nếu có nhập
                if ($studentCode !== '') {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Account WHERE StudentCode = :studentCode');
                    $stmt->execute(['studentCode' => $studentCode]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Mã số sinh viên này đã được đăng ký trên hệ thống.';
                    }
                }

                // 5. Thêm tài khoản mới nếu hợp lệ hoàn toàn
                if (empty($errors)) {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $finalStudentCode = ($studentCode !== '') ? $studentCode : null;

                    // Mặc định vai trò 4: 'Event Participant', IsActive: 1
                    $stmt = $pdo->prepare('
                        INSERT INTO Account (Email, PasswordHash, FullName, StudentCode, RoleID, IsActive) 
                        VALUES (:email, :passwordHash, :fullName, :studentCode, 4, 1)
                    ');
                    $stmt->execute([
                        'email'        => $email,
                        'passwordHash' => $passwordHash,
                        'fullName'     => $fullName,
                        'studentCode'  => $finalStudentCode
                    ]);

                    set_flash('success', 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.');
                    header('Location: ' . base_url('auth/login.php'));
                    exit;
                }
            } catch (PDOException $e) {
                error_log('[EventFlow Register Error] ' . $e->getMessage());
                $errors[] = 'Có lỗi xảy ra trong quá trình lưu dữ liệu. Vui lòng thử lại sau.';
            }
        }
    }
}

$pageTitle = 'Đăng ký tài khoản';
$hideNavbar = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4 py-md-5">
    <div class="row justify-content-center align-items-stretch g-0 rounded-4 overflow-hidden shadow-lg border" style="min-height: 600px;">
        <!-- CỘT TRÁI: BANNER THƯƠNG HIỆU THEO FIGMA (Tương tự Trang Đăng nhập) -->
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

                <!-- Bottom Event Preview Card -->
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

        <!-- CỘT PHẢI: FORM ĐĂNG KÝ CÓ CUỘN (OVERFLOW-Y AUTO) -->
        <div class="col-lg-6 bg-white p-4 p-md-5 d-flex flex-column justify-content-center" style="max-height: 85vh; overflow-y: auto;">
            <div style="max-width: 440px; margin: 0 auto; width: 100%;">
                <div class="mb-4">
                    <h1 class="h3 fw-bold text-dark mb-2">Tạo tài khoản mới</h1>
                    <p class="text-secondary small mb-0">Đăng ký để tham gia các hoạt động cùng EventFlow.</p>
                </div>

                <!-- Hiển thị lỗi nếu có -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger border-0 rounded-3 shadow-none py-2 px-3 mb-4">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
                            <span class="fw-semibold small">Vui lòng kiểm tra lại:</span>
                        </div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= base_url('auth/register.php') ?>" id="registerForm" novalidate>
                    <?= csrf_field() ?>

                    <!-- Họ và tên -->
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="full_name" name="full_name" value="<?= htmlspecialchars($fullName) ?>" placeholder="Ví dụ: Nguyễn Văn An" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="name@st.ueh.edu.vn" required>
                        </div>
                        <div class="form-text small text-muted">Ưu tiên sử dụng email sinh viên để dễ dàng đối chiếu.</div>
                    </div>

                    <!-- Mã số sinh viên -->
                    <div class="mb-3">
                        <label for="student_code" class="form-label">Mã số sinh viên (MSSV)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-card-text"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="student_code" name="student_code" value="<?= htmlspecialchars($studentCode) ?>" placeholder="Ví dụ: 31241020001">
                        </div>
                        <div class="form-text small text-muted">Dùng để tra cứu nhanh khi check-in tại sự kiện.</div>
                    </div>

                    <!-- Mật khẩu -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Tối thiểu 8 ký tự" minlength="8" required>
                        </div>
                    </div>

                    <!-- Xác nhận mật khẩu -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu ở trên" minlength="8" required>
                        </div>
                    </div>

                    <!-- Nút Submit -->
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                            <i class="bi bi-person-plus me-1"></i> Hoàn tất đăng ký
                        </button>
                    </div>

                    <!-- Chuyển sang đăng nhập -->
                    <div class="text-center text-muted small pb-2">
                        Đã có tài khoản? 
                        <a href="<?= base_url('auth/login.php') ?>" class="text-primary fw-semibold ms-1">Đăng nhập ngay</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
