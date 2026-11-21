<?php
/**
 * EventFlow — SCR08: Khung Giao Diện Workspace (Left-Sidebar Navigation)
 * Đáp ứng: UAT 20 (Thanh điều hướng Left-Sidebar hiện đại, tinh gọn cho Ban Điều Hành), UAT 06, 13 (Không lặp nút)
 * Font: Inter/Roboto, Palette: Unified Blue Theme
 */

require_once __DIR__ . '/session_auth.php';

function workspace_header(string $pageTitle, string $activeMenu = 'dashboard', array $breadcrumbs = []): void {
    // Kiểm tra đăng nhập
    require_login();

    // Chỉ cho phép Club Manager, Ban Điều Hành ủy quyền, hoặc System Admin
    if (!is_club_manager() && !is_authorized_club_member() && !is_system_admin()) {
        set_flash('danger', 'Bạn không có quyền truy cập Không gian Quản lý CLB.');
        header('Location: ' . base_url('index.php'));
        exit;
    }

    $currentUser = current_user();
    $flash = get_flash();

    // Lấy 2 ký tự tên
    $nameParts = explode(' ', trim($currentUser['FullName']));
    $initials = '';
    if (count($nameParts) >= 2) {
        $initials = mb_substr($nameParts[count($nameParts) - 2], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1);
    } else {
        $initials = mb_substr($currentUser['FullName'], 0, 2);
    }
    $initials = strtoupper(htmlspecialchars($initials));

    $roleDisplay = $currentUser['MemberRole'] ?: $currentUser['RoleName'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Workspace EventFlow</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/logo.png') ?>">

    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts Inter & Roboto -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto:wght@400;500;700&family=JetBrains+Mono:wght@400;500;600&display=swap">

    <!-- Design Tokens CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/theme-blue.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/theme-blue.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">

    <style>
    /* WORKSPACE MODERN LAYOUT STYLES */
    body {
        background-color: var(--ef-canvas, #f8fafc);
        font-family: var(--ef-font-sans, 'Inter', sans-serif);
        margin: 0;
        overflow-x: hidden;
    }
    .workspace-wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
    }
    /* Hiệu ứng trượt nổi ra (Slide-in) cho Left Menu theo yêu cầu UAT */
    @keyframes sidebarSlideIn {
        from {
            transform: translateX(-100%);
            opacity: 0.5;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    .workspace-sidebar {
        width: 270px;
        background-color: #0b192c; /* Midnight Navy thống nhất giao diện EventFlow */
        color: #ffffff;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        transition: transform 0.32s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
        animation: sidebarSlideIn 0.32s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        z-index: 1040;
        box-shadow: 4px 0 24px rgba(11, 25, 44, 0.15);
    }
    .workspace-brand {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .workspace-user-card {
        padding: 0.85rem 1.15rem;
        margin: 0.85rem 0.85rem 0.5rem 0.85rem;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
    }
    .workspace-nav {
        padding: 0.65rem 0.75rem;
        flex-grow: 1;
        overflow-y: auto;
    }
    .workspace-nav-group {
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        padding: 0.75rem 0.75rem 0.35rem 0.75rem;
        font-family: var(--ef-font-mono, monospace);
    }
    .workspace-nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.65rem 0.85rem;
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.86rem;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s ease;
        margin-bottom: 3px;
        cursor: pointer;
    }
    .workspace-nav-link:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.08);
    }
    .workspace-nav-link.active {
        color: #ffffff;
        background: linear-gradient(90deg, rgba(37, 99, 235, 0.35), rgba(37, 99, 235, 0.15));
        font-weight: 600;
        border-left: 3px solid #38bdf8;
    }
    .workspace-main {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background-color: var(--ef-canvas, #f8fafc);
    }
    .workspace-topbar {
        background-color: #ffffff;
        border-bottom: 1px solid var(--ef-border, #e2e8f0);
        padding: 0.85rem 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 1020;
    }
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 1020;
    }

    /* Responsive Mobile Behavior */
    @media (max-width: 991.98px) {
        .workspace-sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: -260px;
        }
        .workspace-sidebar.show {
            left: 0;
        }
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1030;
        }
        .sidebar-backdrop.show {
            display: block;
        }
    }
    </style>
</head>
<body>

<div class="workspace-wrapper">
    <!-- Mobile Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

    <!-- ==========================================
         LEFT-SIDEBAR NAVIGATION (UAT 20 & 06, 13)
         ========================================== -->
    <aside class="workspace-sidebar" id="workspaceSidebar">
        <!-- Brand Header -->
        <div class="workspace-brand d-flex align-items-center justify-content-between">
            <a href="<?= base_url('app/modules/dashboard/index.php') ?>" class="d-flex align-items-center gap-2 text-decoration-none">
                <div class="ef-brand-icon shadow-sm" style="width: 32px; height: 32px; font-size: 0.95rem;">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <div class="d-flex flex-column">
                    <span class="fw-bold text-white lh-1" style="font-size: 1.15rem; font-weight: 800; letter-spacing: -0.02em;">
                        Event<span style="color: #60a5fa;">Flow</span>
                    </span>
                    <span class="text-white-50 font-monospace" style="font-size: 0.6rem; letter-spacing: 0.08em;">
                        QUẢN LÝ CLB · BĐH
                    </span>
                </div>
            </a>
            <button class="btn btn-sm btn-link text-white-50 p-0 d-lg-none" onclick="toggleSidebar()">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <!-- User Snapshot Card -->
        <div class="workspace-user-card d-flex align-items-center gap-2.5">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0" 
                 style="width: 36px; height: 36px; font-size: 0.85rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                <?= $initials ?>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-white small text-truncate"><?= htmlspecialchars($currentUser['FullName']) ?></div>
                <div class="badge badge-ef-sky font-monospace" style="font-size: 0.62rem;">
                    <?= htmlspecialchars($roleDisplay) ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Navigation Groups (Hỗ trợ mở Khối nội dung trực tiếp không giật trang) -->
        <nav class="workspace-nav">
            <!-- Group 1: Tổng quan -->
            <div class="workspace-nav-group">TỔNG QUAN</div>
            <a href="<?= base_url('app/modules/dashboard/index.php#stats') ?>" class="workspace-nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" data-block="stats" onclick="switchWorkspaceBlock('stats', event)">
                <i class="bi bi-speedometer2"></i> Thống kê & Tổng quan
            </a>
            <a href="<?= base_url('app/modules/dashboard/index.php#calendar') ?>" class="workspace-nav-link <?= $activeMenu === 'calendar' ? 'active' : '' ?>" data-block="calendar" onclick="switchWorkspaceBlock('calendar', event)">
                <i class="bi bi-calendar3"></i> Lịch Sự Kiện
            </a>

            <!-- Group 2: Quản trị Sự kiện -->
            <div class="workspace-nav-group mt-2">QUẢN TRỊ SỰ KIỆN</div>
            <a href="<?= base_url('app/modules/dashboard/index.php#events') ?>" class="workspace-nav-link <?= $activeMenu === 'events_manage' ? 'active' : '' ?>" data-block="events" onclick="switchWorkspaceBlock('events', event)">
                <i class="bi bi-calendar-event"></i> Quản lý Sự kiện
            </a>
            <a href="<?= base_url('app/modules/dashboard/index.php#create-event') ?>" class="workspace-nav-link <?= $activeMenu === 'event_create' ? 'active' : '' ?>" data-block="create-event" onclick="switchWorkspaceBlock('create-event', event)">
                <i class="bi bi-plus-circle"></i> Tạo Sự kiện mới
            </a>
            <a href="<?= base_url('app/modules/dashboard/index.php#my-events') ?>" class="workspace-nav-link <?= $activeMenu === 'my_events' ? 'active' : '' ?>">
                <i class="bi bi-bookmark-check"></i> Sự kiện của tôi
            </a>

            <!-- Group 3: Nhân sự & Nội bộ -->
            <div class="workspace-nav-group mt-2">TỔ CHỨC & NHÂN SỰ</div>
            <a href="<?= base_url('app/modules/dashboard/index.php#members') ?>" class="workspace-nav-link <?= $activeMenu === 'members' ? 'active' : '' ?>" data-block="members" onclick="switchWorkspaceBlock('members', event)">
                <i class="bi bi-people-fill"></i> Thành viên & 4 Ban
            </a>
            <a href="<?= base_url('app/modules/dashboard/index.php#club-info') ?>" class="workspace-nav-link <?= $activeMenu === 'club_info' ? 'active' : '' ?>">
                <i class="bi bi-building"></i> Thông tin CLB
            </a>

            <!-- Group 4: Hệ thống & Điều hướng -->
            <div class="workspace-nav-group mt-2">HỆ THỐNG</div>
            <?php if (is_system_admin()): ?>
                <a href="<?= base_url('app/modules/dashboard/index.php#admin-accounts') ?>" class="workspace-nav-link text-danger <?= $activeMenu === 'accounts' ? 'active' : '' ?>">
                    <i class="bi bi-shield-lock-fill"></i> Quản trị tài khoản
                </a>
            <?php endif; ?>
            <a href="<?= base_url('app/modules/dashboard/index.php#profile') ?>" class="workspace-nav-link <?= $activeMenu === 'profile' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Hồ sơ cá nhân
            </a>
            <a href="<?= base_url('app/modules/auth/logout.php') ?>" class="workspace-nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </nav>
    </aside>

    <!-- ==========================================
         MAIN CONTENT REGION
         ========================================== -->
    <div class="workspace-main">
        <!-- Topbar -->
        <header class="workspace-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" onclick="toggleSidebar()">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="<?= base_url('app/modules/dashboard/index.php') ?>" class="text-decoration-none text-muted">Workspace</a></li>
                        <?php foreach ($breadcrumbs as $label => $url): ?>
                            <?php if ($url): ?>
                                <li class="breadcrumb-item"><a href="<?= $url ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($label) ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= htmlspecialchars($label) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (empty($breadcrumbs)): ?>
                            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?= htmlspecialchars($pageTitle) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>

            <div class="d-flex align-items-center gap-2.5">
                <span class="badge bg-light text-secondary border font-monospace d-none d-sm-inline" style="font-size: 0.72rem;">
                    <i class="bi bi-clock me-1 text-primary"></i> <?= date('d/m/Y') ?>
                </span>
                <a href="<?= base_url('index.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-globe2 me-1"></i> Cổng Công Khai
                </a>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-2xs" 
                     style="width: 32px; height: 32px; font-size: 0.8rem; background: #0f172a;">
                    <?= $initials ?>
                </div>
            </div>
        </header>

        <!-- Flash Alert -->
        <?php if ($flash): ?>
            <div class="px-4 px-lg-5 pt-3">
                <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 rounded-3 d-flex align-items-center mb-0" role="alert">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div><?= htmlspecialchars($flash['message']) ?></div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Body -->
        <main class="p-4 p-lg-5 flex-grow-1">
<?php
}

function workspace_footer(): void {
?>
        </main>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('workspaceSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.toggle('show');
    if (backdrop) backdrop.classList.toggle('show');
}

// Chuyển đổi khối nội dung trong không gian Quản lý CLB tức thì (không giật/reload trang)
function switchWorkspaceBlock(blockId, event) {
    const blocks = document.querySelectorAll('.workspace-content-block');
    if (!blocks || blocks.length === 0) {
        // Nếu đang ở trang độc lập khác thì điều hướng về dashboard kèm hash
        window.location.href = '<?= base_url("app/modules/dashboard/index.php#") ?>' + blockId;
        return;
    }

    if (event) event.preventDefault();

    // Ẩn toàn bộ các khối nội dung
    blocks.forEach(b => b.classList.add('d-none'));

    // Hiển thị khối mục tiêu
    const targetBlock = document.getElementById('block-' + blockId);
    if (targetBlock) {
        targetBlock.classList.remove('d-none');
    }

    // Cập nhật trạng thái active trên Left-Sidebar
    document.querySelectorAll('.workspace-nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-block') === blockId) {
            link.classList.add('active');
        }
    });

    // Cập nhật URL Hash mượt mà
    if (history.pushState) {
        history.pushState(null, null, '#' + blockId);
    } else {
        location.hash = blockId;
    }

    // Đóng sidebar nếu đang xem trên mobile
    const sidebar = document.getElementById('workspaceSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar && sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    }

    // Nếu chuyển sang khối Lịch, kích hoạt lại engine render
    if (blockId === 'calendar' && typeof window.renderWorkspaceCalendar === 'function') {
        window.renderWorkspaceCalendar();
    }
}

// Lắng nghe hash khi tải trang để mở đúng khối nội dung
window.addEventListener('DOMContentLoaded', () => {
    const blocks = document.querySelectorAll('.workspace-content-block');
    if (blocks && blocks.length > 0) {
        let hash = window.location.hash.replace('#', '');
        if (!hash || !document.getElementById('block-' + hash)) {
            hash = 'stats';
        }
        switchWorkspaceBlock(hash);
    }
});
</script>
</body>
</html>
<?php
}
