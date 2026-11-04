<?php
/**
 * EventFlow — Quản lý Phiên (Session), Bảo mật (CSRF, XSS) và Phân quyền RBAC 2 Tầng
 * Bám sát Hồ sơ Dự án & Bản Thiết Kế Kỹ Thuật (Tab 3: RBAC Matrix)
 */

if (session_status() === PHP_SESSION_NONE) {
    // Cấu hình cookie session an toàn
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Trả về đường dẫn tuyệt đối đầy đủ của ứng dụng (Full Base URL)
 * Đảm bảo trình duyệt luôn giữ đúng scheme, host và thư mục dự án (/EventFlow/)
 * Không bao giờ bị mất tiền tố hay biến dạng URL khi click chuyển trang
 */
function base_url(string $path = ''): string {
    // 1. Xác định giao thức và host hiện tại của trình duyệt
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // 2. Xác định base path trong URL
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $requestUri = str_replace('\\', '/', $_SERVER['REQUEST_URI'] ?? '');
    
    $basePath = '';
    // Kiểm tra tiền tố thư mục dự án EventFlow (không phân biệt hoa thường)
    if (preg_match('#/(EventFlow|eventflow)#i', $scriptName, $m)) {
        $basePath = '/' . $m[1];
    } elseif (preg_match('#/(EventFlow|eventflow)#i', $requestUri, $m)) {
        $basePath = '/' . $m[1];
    } else {
        // Fallback: tính toán từ thư mục của script hiện tại
        $dir = str_replace('\\', '/', dirname($scriptName));
        // Cắt bỏ phần đường dẫn con thuộc kiến trúc app/modules/...
        $dir = preg_replace('#/app(/modules)?(/[a-zA-Z0-9_-]+)?$#', '', $dir);
        $dir = rtrim($dir, '/');
        $basePath = ($dir === '' || $dir === '.' || $dir === '/') ? '' : $dir;
    }

    $cleanPath = ltrim($path, '/');
    $relUrl = $basePath . ($cleanPath !== '' ? '/' . $cleanPath : '');
    
    // Trả về URL tuyệt đối đầy đủ (Full URL: http://localhost/EventFlow/...)
    return "{$scheme}://{$host}{$relUrl}";
}

/**
 * Tạo hoặc lấy mã CSRF Token hiện tại của phiên
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Sinh mã input hidden CSRF Token cho HTML Form
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Xác thực CSRF Token khi submit form (POST)
 */
function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Thiết lập Flash Message lưu vào Session để hiển thị ở trang tiếp theo
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Lấy Flash Message và xóa khỏi Session
 */
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['AccountID']);
}

/**
 * Lấy thông tin tài khoản đang đăng nhập từ Session
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Lấy ID của tài khoản đang đăng nhập
 */
function current_user_id(): ?int {
    return isset($_SESSION['user']['AccountID']) ? (int)$_SESSION['user']['AccountID'] : null;
}

/**
 * Đăng nhập người dùng và nạp đầy đủ thông tin quyền 2 tầng vào Session
 */
function login_user(array $accountData, ?array $clubMemberData = null): void {
    // Chống Session Fixation
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'AccountID'   => (int)$accountData['AccountID'],
        'Email'       => $accountData['Email'],
        'FullName'    => $accountData['FullName'],
        'StudentCode' => $accountData['StudentCode'] ?? null,
        'RoleID'      => (int)$accountData['RoleID'],
        'RoleName'    => $accountData['RoleName'] ?? 'Event Participant',
        'IsActive'    => (int)$accountData['IsActive'],
        // Tầng 2: Thông tin thành viên CLB (nếu có)
        'MemberRole'  => $clubMemberData['MemberRole'] ?? null,
        'ClubStatus'  => $clubMemberData['Status'] ?? null,
        'ClubID'      => $clubMemberData['ClubID'] ?? null
    ];
}

/**
 * Đăng xuất an toàn và xóa toàn bộ dữ liệu phiên
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Kiểm tra vai trò Tầng 1 (System Role)
 * @param string|array $roleNames Ví dụ: 'System Administrator', hoặc ['Club Manager', 'Club Member']
 */
function has_role($roleNames): bool {
    if (!is_logged_in()) {
        return false;
    }
    $currentUserRole = $_SESSION['user']['RoleName'] ?? '';
    if (is_array($roleNames)) {
        return in_array($currentUserRole, $roleNames, true);
    }
    return $currentUserRole === $roleNames;
}

/**
 * Kiểm tra xem có phải System Administrator không
 */
function is_system_admin(): bool {
    return has_role('System Administrator');
}

/**
 * Kiểm tra xem có phải Club Manager không
 */
function is_club_manager(): bool {
    return has_role('Club Manager');
}

/**
 * Kiểm tra quyền Tầng 2 (Authorized Club Member)
 * Điều kiện: Club Manager HOẶC (Club Member có Status = 'Active' VÀ MemberRole IN ('Board Member', 'Event Supporter'))
 */
function is_authorized_club_member(): bool {
    if (!is_logged_in()) {
        return false;
    }
    // Club Manager luôn có quyền quản lý và hỗ trợ
    if (is_club_manager()) {
        return true;
    }
    // Nếu là Club Member, kiểm tra tầng 2
    $memberRole = $_SESSION['user']['MemberRole'] ?? '';
    $clubStatus = $_SESSION['user']['ClubStatus'] ?? '';
    
    return ($clubStatus === 'Active') && in_array($memberRole, ['Board Member', 'Event Supporter'], true);
}

/**
 * Yêu cầu đăng nhập trước khi truy cập trang
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
        header('Location: ' . base_url('app/modules/auth/login.php'));
        exit;
    }
    // Kiểm tra xem tài khoản có bị Admin khóa không
    if (empty($_SESSION['user']['IsActive'])) {
        logout_user();
        set_flash('danger', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Quản trị viên hệ thống.');
        header('Location: ' . base_url('app/modules/auth/login.php'));
        exit;
    }
}

/**
 * Yêu cầu quyền hệ thống cụ thể (Tầng 1)
 */
function require_role($roleNames): void {
    require_login();
    if (!has_role($roleNames)) {
        render_forbidden_page('Bạn không có quyền truy cập chức năng này (Yêu cầu vai trò hệ thống phù hợp).');
    }
}

/**
 * Yêu cầu quyền thành viên được ủy quyền (Tầng 2 - Dành cho Check-in SCR14 & Danh sách đăng ký SCR13)
 */
function require_authorized_club_member(): void {
    require_login();
    if (!is_authorized_club_member()) {
        render_forbidden_page('Chức năng này chỉ dành cho Ban chủ nhiệm hoặc Thành viên CLB được phân quyền ủy quyền hỗ trợ sự kiện.');
    }
}

/**
 * Hiển thị trang lỗi 403 Forbidden chuẩn giao diện
 */
function render_forbidden_page(string $message = 'Bạn không có quyền truy cập tài nguyên này.'): void {
    http_response_code(403);
    $pageTitle = '403 — Không có quyền truy cập';
    require_once __DIR__ . '/header.php';
    ?>
    <div class="container py-5 text-center">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card p-5 shadow-sm border-0 text-center">
                    <div class="mb-4">
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill font-mono fs-6">403 FORBIDDEN</span>
                    </div>
                    <h2 class="fw-bold text-dark mb-2">Truy cập bị từ chối</h2>
                    <p class="text-secondary mb-4"><?= htmlspecialchars($message) ?></p>
                    <div>
                        <a href="<?= base_url() ?>" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-house-door me-1"></i> Về trang chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/footer.php';
    exit;
}
