<?php
/**
 * EventFlow — SCR04: Xem thông tin công khai Câu lạc bộ
 * Đáp ứng: FR06, PB06, UC05
 * Thiết kế giao diện: Bootstrap 5.3 chuẩn Figma, font Inter, màu chủ đạo Indigo/Emerald
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

$pdo = get_db_connection();

// Lấy thông tin CLB mặc định (ClubID = 1 trong phạm vi MVP)
$stmt = $pdo->prepare("
    SELECT c.*, a.FullName AS ManagerName, a.Email AS ManagerEmail, a.StudentCode AS ManagerStudentCode
    FROM Club c
    JOIN Account a ON c.ManagerID = a.AccountID
    WHERE c.ClubID = 1
    LIMIT 1
");
$stmt->execute();
$club = $stmt->fetch();

if (!$club) {
    die("Không tìm thấy dữ liệu Câu lạc bộ. Vui lòng kiểm tra CSDL.");
}

// Thống kê thành viên đang hoạt động
$memberCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM Club_Member 
    WHERE ClubID = :clubId AND Status = 'Active'
");
$memberCountStmt->execute([':clubId' => $club['ClubID']]);
$totalMembers = (int)$memberCountStmt->fetchColumn();

// Thống kê sự kiện đã công bố
$eventCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM Event 
    WHERE ClubID = :clubId AND Status = 'Published'
");
$eventCountStmt->execute([':clubId' => $club['ClubID']]);
$totalEvents = (int)$eventCountStmt->fetchColumn();

// Lấy danh sách Ban điều hành (Board Member)
$boardStmt = $pdo->prepare("
    SELECT cm.*, a.FullName, a.Email, a.StudentCode
    FROM Club_Member cm
    JOIN Account a ON cm.AccountID = a.AccountID
    WHERE cm.ClubID = :clubId AND cm.Status = 'Active' AND cm.MemberRole = 'Board Member'
    ORDER BY cm.MemberID ASC
");
$boardStmt->execute([':clubId' => $club['ClubID']]);
$boardMembers = $boardStmt->fetchAll();

$pageTitle = htmlspecialchars($club['ClubName']) . " — EventFlow";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Thông tin CLB</li>
        </ol>
    </nav>

    <!-- Club Banner Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background: linear-gradient(135deg, #4338CA 0%, #312E81 100%);">
        <div class="card-body p-4 p-md-5 text-white">
            <div class="row align-items-center g-4">
                <div class="col-md-auto text-center text-md-start">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 shadow-sm" style="width: 88px; height: 88px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.25);">
                        <i class="bi bi-mortarboard-fill fs-1 text-white"></i>
                    </div>
                </div>
                <div class="col-md">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge rounded-pill bg-white text-primary px-3 py-1 fw-bold fs-7">CLB Sinh Viên UEH</span>
                        <span class="badge rounded-pill bg-success-subtle text-success px-3 py-1 fw-semibold fs-7">
                            <i class="bi bi-patch-check-fill me-1"></i> Chính thức
                        </span>
                    </div>
                    <h1 class="h2 fw-bold mb-2 text-white"><?= htmlspecialchars($club['ClubName']) ?></h1>
                    <p class="mb-0 text-white-50" style="max-width: 700px;">
                        Nền tảng giao lưu học thuật, phát triển kỹ năng tiếng Anh và kết nối sinh viên Đại học Kinh tế TP. Hồ Chí Minh.
                    </p>
                </div>
                <div class="col-md-auto text-md-end">
                    <?php if (is_club_manager()): ?>
                        <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                            <a href="<?= base_url('club/edit.php') ?>" class="btn btn-light shadow-sm fw-semibold">
                                <i class="bi bi-pencil-square me-1 text-primary"></i> Chỉnh sửa thông tin
                            </a>
                            <a href="<?= base_url('club/members.php') ?>" class="btn btn-outline-light fw-semibold">
                                <i class="bi bi-people me-1"></i> Quản lý thành viên
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="row g-3 mt-4 pt-3 border-top border-white border-opacity-10 text-center text-md-start">
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="text-white-50 small mb-1">Thành viên hoạt động</div>
                    <div class="h4 fw-bold mb-0 text-white"><?= number_format($totalMembers) ?> <span class="fs-6 fw-normal text-white-50">thành viên</span></div>
                </div>
                <div class="col-6 col-sm-4 col-md-3">
                    <div class="text-white-50 small mb-1">Sự kiện đã tổ chức</div>
                    <div class="h4 fw-bold mb-0 text-white"><?= number_format($totalEvents) ?> <span class="fs-6 fw-normal text-white-50">sự kiện</span></div>
                </div>
                <div class="col-12 col-sm-4 col-md-4">
                    <div class="text-white-50 small mb-1">Chủ nhiệm phụ trách</div>
                    <div class="h6 fw-bold mb-0 text-white"><?= htmlspecialchars($club['ManagerName']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
        <!-- Left: Description & Leadership Team -->
        <div class="col-lg-8">
            <!-- About Section -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h3 class="h5 fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
                        <i class="bi bi-info-circle-fill text-primary"></i> Giới thiệu về Câu lạc bộ
                    </h3>
                    <div class="text-secondary lh-lg mb-0" style="white-space: pre-line;">
                        <?= htmlspecialchars($club['Description']) ?>
                    </div>
                </div>
            </div>

            <!-- Board of Management -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 fw-bold mb-0 d-flex align-items-center gap-2 text-dark">
                            <i class="bi bi-award-fill text-warning"></i> Ban Điều Hành CLB
                        </h3>
                        <?php if (is_club_manager()): ?>
                            <a href="<?= base_url('club/members.php') ?>" class="text-decoration-none small fw-semibold text-primary">
                                Xem tất cả thành viên <i class="bi bi-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($boardMembers)): ?>
                        <div class="p-3 text-center text-muted bg-light rounded-3">
                            Chưa có thông tin ban điều hành được cập nhật.
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($boardMembers as $bm): ?>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-3 border bg-light-subtle d-flex align-items-center gap-3">
                                        <div class="ef-avatar-circle" style="width: 44px; height: 44px; font-size: 1rem; background-color: var(--primary-700);">
                                            <?= htmlspecialchars(mb_substr($bm['FullName'], 0, 1, 'UTF-8')) ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($bm['FullName']) ?></div>
                                            <div class="small text-muted text-truncate"><?= htmlspecialchars($bm['Email']) ?></div>
                                            <span class="badge bg-primary-subtle text-primary mt-1" style="font-size: 0.7rem;">
                                                <?= htmlspecialchars($bm['MemberRole']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Contact & Quick Links -->
        <div class="col-lg-4">
            <!-- Contact Info Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold text-uppercase tracking-wider text-muted mb-3">
                        Thông tin liên hệ
                    </h3>
                    
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-primary-subtle p-2 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Email liên hệ</div>
                                <div class="fw-semibold text-dark text-break">
                                    <?php
                                    // Parse email từ ContactInfo hoặc ManagerEmail
                                    preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $club['ContactInfo'], $matches);
                                    $contactEmail = $matches[0] ?? $club['ManagerEmail'];
                                    ?>
                                    <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="text-decoration-none text-dark hover-primary">
                                        <?= htmlspecialchars($contactEmail) ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-success-subtle p-2 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Địa điểm sinh hoạt</div>
                                <div class="fw-semibold text-dark">
                                    UEH Cơ sở B — 279 Nguyễn Tri Phương, Q.10, TP.HCM
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-info-subtle p-2 text-info d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                <i class="bi bi-globe"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Kênh truyền thông</div>
                                <div class="fw-semibold text-dark">
                                    <?= htmlspecialchars($club['ContactInfo']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-light-subtle">
                <div class="card-body p-4 text-center">
                    <div class="mb-3 text-primary">
                        <i class="bi bi-calendar2-event fs-1"></i>
                    </div>
                    <h4 class="h6 fw-bold mb-2">Khám phá hoạt động CLB</h4>
                    <p class="small text-muted mb-3">
                        Xem các buổi sinh hoạt Speaking Circle, Workshop Debate và sự kiện sắp tới.
                    </p>
                    <a href="<?= base_url('index.php') ?>" class="btn btn-primary w-100 fw-semibold">
                        Xem lịch sự kiện <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
