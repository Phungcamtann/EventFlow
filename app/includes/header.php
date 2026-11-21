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
    <div class="py-1 text-white border-bottom border-dark border-opacity-25 ef-top-ticker">
        <div class="container d-flex justify-content-between align-items-center">
            <span class="text-white-50 text-uppercase fw-semibold d-none d-md-inline font-monospace">
                EVENTFLOW — NỀN TẢNG QUẢN LÝ & ĐIỂM DANH SỰ KIỆN CLB SINH VIÊN
            </span>
            <span class="text-white-50 text-uppercase fw-semibold d-inline d-md-none font-monospace">
                EVENTFLOW · UEH
            </span>
            <span class="text-uppercase fw-bold text-ef-sky font-monospace" style="font-size: 0.72rem;">
                ĐẠI HỌC KINH TẾ TP. HỒ CHÍ MINH <span class="text-white-50">✱</span> UEH
            </span>
        </div>
    </div>

    <!-- TOP NAVBAR -->
    <?php if (empty($hideNavbar)): ?>
    <nav class="navbar navbar-expand-lg ef-navbar sticky-top">
        <div class="container">
            <!-- Brand Logo: EventFlow -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url() ?>">
                <div class="ef-brand-icon">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-bold text-dark lh-1" style="font-family: 'Inter', sans-serif; font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em;">Event<span style="color: #2563eb;">Flow</span></span>
                    <span class="text-muted fw-semibold font-monospace" style="font-size: 0.65rem; letter-spacing: 0.06em;">CAMPUS EVENT PLATFORM · UEH</span>
                </div>
            </a>

            <!-- Mobile toggle button -->
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#efNavbarContent" aria-controls="efNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-2 text-dark"></i>
            </button>

            <!-- Navigation Links (Thống nhất Top-nav công chúng: Trang chủ | Câu Lạc Bộ | Sự kiện) -->
            <div class="collapse navbar-collapse" id="efNavbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-1">
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
                                        <?= htmlspecialchars($currentUser['MemberRole'] ?? $currentUser['RoleName']) ?>
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
                        <!-- Guest actions: Nút Đăng nhập nổi bật theo UAT 20 -->
                        <a href="<?= base_url('app/modules/auth/login.php') ?>" class="btn btn-primary rounded-pill px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm" style="font-size: 0.85rem;">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- FLASH MESSAGES ALERT -->
    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 rounded-3 d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($flash['message']) ?></div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- MAIN BODY WRAPPER -->
    <main class="main-content">