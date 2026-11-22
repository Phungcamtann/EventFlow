<?php
/**
 * EventFlow — Header & Top Navbar Component
 * Bám sát Thiết kế Bootstrap 5.3 & Prototype Figma (Màn hình 02)
 */
require_once __DIR__ . '/session_auth.php';

$currentUser = current_user();
$flash = get_flash();
$title = !empty($pageTitle) ? $pageTitle . ' — EventFlow' : 'EventFlow — Quản lý Hoạt động CLB Tiếng Anh Sinh Viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo.png') ?>">

    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Design System CSS (Unified Blue Theme & Core Styles) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/theme-blue.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme-blue.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
</head>
<body>

    <!-- TOP UTILITY TICKER STRIP -->
    <div class="py-1 border-bottom ef-top-ticker">
        <div class="container d-flex justify-content-between align-items-center">
            <span class="text-uppercase fw-semibold d-none d-md-inline font-monospace" style="color: #64748b; font-size: 0.72rem; letter-spacing: 0.06em;">
                EVENTFLOW — NỀN TẢNG QUẢN LÝ & ĐIỂM DANH SỰ KIỆN CLB SINH VIÊN
            </span>
            <span class="text-uppercase fw-semibold d-inline d-md-none font-monospace" style="color: #64748b; font-size: 0.72rem; letter-spacing: 0.06em;">
                EVENTFLOW · UEH
            </span>
            <span class="text-uppercase fw-bold font-monospace" style="font-size: 0.72rem; color: #2563eb; letter-spacing: 0.05em;">
                ĐẠI HỌC KINH TẾ TP. HỒ CHÍ MINH <span style="color: #94a3b8;">✱</span> UEH
            </span>
        </div>
    </div>

    <!-- TOP NAVBAR -->
    <?php if (empty($hideNavbar)): ?>
    <nav class="navbar navbar-expand-lg ef-navbar sticky-top">
        <div class="container">
            <!-- Brand Logo: EventFlow (Logo chữ E cũ + Tên thương hiệu & Slogan) -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url() ?>">
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="EventFlow" class="rounded-3 shadow-xs" style="width: 38px; height: 38px; object-fit: contain;">
                <div class="d-flex flex-column">
                    <span class="fw-bold text-dark lh-1" style="font-family: 'Inter', sans-serif; font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em;">Event<span style="color: #2563eb;">Flow</span></span>
                    <span class="text-muted fw-semibold font-monospace" style="font-size: 0.65rem; letter-spacing: 0.06em;">CAMPUS EVENT PLATFORM · UEH</span>
                </div>
            </a>

            <!-- Mobile toggle button -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#efNavbarContent" aria-controls="efNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <!-- Navigation Links (Căn giữa navbar-nav mx-auto: 3 links cho khách / 4 links cho Cán bộ) -->
            <div class="collapse navbar-collapse" id="efNavbarContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-2">
                    <li class="nav-item">
                        <a class="nav-link ef-nav-link" href="<?= base_url('index.php') ?>">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ef-nav-link" href="<?= base_url('app/modules/club/info.php') ?>">Câu Lạc Bộ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link ef-nav-link" href="<?= base_url('app/modules/events/index.php') ?>">Sự kiện</a>
                    </li>

                    <?php if (is_logged_in()): ?>
                        <!-- Lối tắt trực tiếp vào Không Gian Quản Lý CLB dành cho Ban Điều Hành -->
                        <?php if (is_club_manager() || is_authorized_club_member()): ?>
                            <li class="nav-item">
                                <a class="nav-link ef-nav-link text-primary fw-bold d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill bg-primary-subtle ms-lg-2" href="<?= base_url('app/modules/dashboard/index.php#stats') ?>">
                                    <i class="bi bi-sliders text-primary"></i> Quản lý CLB
                                </a>
                            </li>
                        <?php elseif (is_system_admin()): ?>
                            <li class="nav-item">
                                <a class="nav-link ef-nav-link text-danger fw-semibold d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill bg-danger-subtle ms-lg-2" href="<?= base_url('app/modules/dashboard/index.php#admin-accounts') ?>">
                                    <i class="bi bi-shield-lock-fill me-1 text-danger"></i> Quản trị tài khoản
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <!-- Right Action Bar -->
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                    <?php if (is_logged_in()): ?>
                        <!-- Role Badge Pill (Hiển thị rõ cả vai trò ủy quyền Tầng 2 nếu có) -->
                        <span class="ef-role-pill">
                            <?php
                            if ($currentUser['RoleName'] === 'Club Member' && !empty($currentUser['MemberRole'])) {
                                echo htmlspecialchars($currentUser['MemberRole']);
                            } else {
                                echo htmlspecialchars($currentUser['RoleName']);
                            }
                            ?>
                        </span>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button class="btn p-0 border-0 d-flex align-items-center gap-2 dropdown-toggle shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="ef-avatar-circle">
                                    <?php
                                    // Lấy 2 ký tự đầu của tên
                                    $nameParts = explode(' ', trim($currentUser['FullName']));
                                    $initials = '';
                                    if (count($nameParts) >= 2) {
                                        $initials = mb_substr($nameParts[count($nameParts) - 2], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1);
                                    } else {
                                        $initials = mb_substr($currentUser['FullName'], 0, 2);
                                    }
                                    echo strtoupper(htmlspecialchars($initials));
                                    ?>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-md rounded-3 mt-2 py-2" style="min-width: 240px;">
                                <li class="px-3 py-2 border-bottom mb-1">
                                    <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($currentUser['FullName']) ?></div>
                                    <small class="text-secondary text-truncate d-block">
                                        <?= htmlspecialchars($currentUser['Email']) ?>
                                    </small>
                                    <small class="badge bg-light text-primary border rounded-pill mt-1 font-mono">
                                        <?= htmlspecialchars(format_club_member_role($currentUser['MemberRole'] ?? $currentUser['RoleName'])) ?>
                                    </small>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="<?= (is_club_manager() || is_authorized_club_member() || is_system_admin()) ? base_url('app/modules/dashboard/index.php#profile') : base_url('app/modules/profile/index.php') ?>">
                                        <i class="bi bi-person me-2 text-primary"></i> Hồ sơ cá nhân
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="<?= (is_club_manager() || is_authorized_club_member() || is_system_admin()) ? base_url('app/modules/dashboard/index.php#my-events') : base_url('app/modules/events/my_events.php') ?>">
                                        <i class="bi bi-bookmark-check me-2 text-primary"></i> Sự kiện của tôi
                                    </a>
                                </li>
                                <?php if (is_club_manager() || is_authorized_club_member()): ?>
                                    <li>
                                        <a class="dropdown-item py-2 text-primary fw-semibold" href="<?= base_url('app/modules/dashboard/index.php#stats') ?>">
                                            <i class="bi bi-sliders me-2"></i> Quản lý CLB
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (is_system_admin()): ?>
                                    <li>
                                        <a class="dropdown-item py-2 text-danger" href="<?= base_url('app/modules/dashboard/index.php#admin-accounts') ?>">
                                            <i class="bi bi-shield-lock me-2"></i> Quản trị tài khoản
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger" href="<?= base_url('app/modules/auth/logout.php') ?>">
                                        <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Guest actions: Nút Đăng nhập chuẩn hóa khoảng cách icon & text -->
                        <a href="<?= base_url('app/modules/auth/login.php') ?>" class="btn btn-ef-primary rounded-pill px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 0.875rem;">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Đăng nhập</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- FLOATING TOAST NOTIFICATION (AUTO DISMISS 3.5S WITH PROGRESS BAR) -->
    <?php if ($flash): ?>
    <div id="efToastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1090; margin-top: 52px;">
        <div id="efToast" class="toast show align-items-center border-0 shadow-lg bg-white rounded-3 overflow-hidden" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 320px; max-width: 420px; border: 1px solid #e2e8f0 !important;">
            <div class="d-flex align-items-center p-3">
                <div class="me-3 fs-4 text-<?= htmlspecialchars($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'primary')) ?>">
                    <i class="bi <?= $flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : ($flash['type'] === 'warning' ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill') ?>"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold text-dark fs-6 mb-0.5">Thông báo hệ thống</div>
                    <div class="text-secondary small"><?= htmlspecialchars($flash['message']) ?></div>
                </div>
                <button type="button" class="btn-close ms-2 me-0 shadow-none" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="ef-toast-progress-wrapper" style="height: 3px; background: #e2e8f0; width: 100%;">
                <div class="ef-toast-progress-bar bg-<?= htmlspecialchars($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'primary')) ?>" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.getElementById('efToast');
        if (toastEl) {
            setTimeout(function() {
                toastEl.classList.remove('show');
                const container = document.getElementById('efToastContainer');
                if (container) container.remove();
            }, 3500);
        }
    });
    </script>
    <?php endif; ?>

    <!-- MAIN BODY WRAPPER -->
    <main class="main-content">