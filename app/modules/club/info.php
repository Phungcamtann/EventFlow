<?php
/**
 * EventFlow — SCR04: Thông Tin & Cơ Cấu Câu Lạc Bộ BELL UEH
 * Thiết kế UI/UX tham khảo cách trình bày từ https://www.bellclubueh.net/
 * Áp dụng hệ thống màu thống nhất EventFlow Blue (#2563eb / #0b192c / #f8fafc)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';

$pdo = get_db_connection();

// 1. Lấy thông tin CLB mặc định (ClubID = 1 - BELL Club UEH)
$stmt = $pdo->prepare("
    SELECT c.*, 
           a.FullName AS ManagerName, 
           a.Email AS ManagerEmail, 
           a.StudentCode AS ManagerStudentCode,
           a.Phone AS ManagerPhone
    FROM Club c
    LEFT JOIN Account a ON c.ManagerID = a.AccountID
    WHERE c.ClubID = 1
    LIMIT 1
");
$stmt->execute();
$club = $stmt->fetch();

if (!$club) {
    die("Không tìm thấy dữ liệu Câu lạc bộ BELL UEH. Vui lòng kiểm tra CSDL.");
}

// 2. Thống kê chỉ số hoạt động
$memberCountStmt = $pdo->prepare("SELECT COUNT(*) FROM Club_Member WHERE ClubID = :clubId AND Status = 'Active'");
$memberCountStmt->execute([':clubId' => $club['ClubID']]);
$totalMembers = (int)$memberCountStmt->fetchColumn();

$eventCountStmt = $pdo->prepare("SELECT COUNT(*) FROM Event WHERE ClubID = :clubId");
$eventCountStmt->execute([':clubId' => $club['ClubID']]);
$totalEvents = (int)$eventCountStmt->fetchColumn();

// 3. Lấy danh sách Ban Điều Hành (President & Vice President)
$execStmt = $pdo->prepare("
    SELECT cm.*, a.FullName, a.Email, a.StudentCode, a.Phone, a.Cohort, a.Major, a.AvatarUrl
    FROM Club_Member cm
    JOIN Account a ON cm.AccountID = a.AccountID
    WHERE cm.ClubID = :clubId 
      AND cm.Status = 'Active' 
      AND cm.MemberRole IN ('President', 'Vice President', 'Board Member')
    ORDER BY CASE 
        WHEN cm.MemberRole = 'President' THEN 1 
        WHEN cm.MemberRole = 'Vice President' THEN 2 
        ELSE 3 
    END, cm.MemberID ASC
");
$execStmt->execute([':clubId' => $club['ClubID']]);
$execMembers = $execStmt->fetchAll();

// 4. Lấy danh sách 4 Phòng ban và Cán bộ phụ trách (Head & Vice Head)
$deptStmt = $pdo->prepare("
    SELECT d.DepartmentID, d.DepartmentName, d.DepartmentCode, d.Description, d.KeyActivities, d.OrderIndex,
           cm.MemberRole, a.FullName, a.Email, a.StudentCode, a.Phone, a.Cohort, a.Major, a.AvatarUrl, cm.Bio
    FROM Department d
    LEFT JOIN Club_Member cm ON d.DepartmentID = cm.DepartmentID AND cm.Status = 'Active'
    LEFT JOIN Account a ON cm.AccountID = a.AccountID
    WHERE d.ClubID = :clubId
    ORDER BY d.OrderIndex ASC, 
             CASE 
                WHEN cm.MemberRole = 'Head' THEN 1 
                WHEN cm.MemberRole = 'Vice Head' THEN 2 
                ELSE 3 
             END, cm.MemberID ASC
");
$deptStmt->execute([':clubId' => $club['ClubID']]);
$deptRows = $deptStmt->fetchAll();

// Gom nhóm cán bộ theo từng phòng ban
$departments = [];
foreach ($deptRows as $row) {
    $did = $row['DepartmentID'];
    if (!isset($departments[$did])) {
        $departments[$did] = [
            'DepartmentID'   => $row['DepartmentID'],
            'DepartmentName' => $row['DepartmentName'],
            'DepartmentCode' => $row['DepartmentCode'],
            'Description'    => $row['Description'],
            'KeyActivities'  => $row['KeyActivities'],
            'Officers'       => []
        ];
    }
    if (!empty($row['FullName'])) {
        $departments[$did]['Officers'][] = [
            'FullName'    => $row['FullName'],
            'MemberRole'  => $row['MemberRole'],
            'Email'       => $row['Email'],
            'StudentCode' => $row['StudentCode'],
            'Phone'       => $row['Phone'],
            'Cohort'      => $row['Cohort'],
            'Major'       => $row['Major'],
            'AvatarUrl'   => $row['AvatarUrl'],
            'Bio'         => $row['Bio']
        ];
    }
}

// 5. Kiểm tra chiến dịch tuyển quân đang mở
$campaignStmt = $pdo->prepare("
    SELECT * FROM Recruitment_Campaign 
    WHERE ClubID = :clubId AND Status = 'Open' 
    ORDER BY CampaignID DESC LIMIT 1
");
$campaignStmt->execute([':clubId' => $club['ClubID']]);
$activeCampaign = $campaignStmt->fetch();

$pageTitle = "BELL Club UEH — Business English for Learners and Lovers";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="py-4 bg-ef-canvas" style="min-height: 100vh;">
    <div class="container">

        <!-- Breadcrumb & Top Tag -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Giới thiệu BELL Club UEH</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-ef-blue rounded-pill px-3 py-1 font-monospace" style="font-size: 0.72rem;">
                    EST. 1995 ✱ 30+ YEARS OF EXCELLENCE
                </span>
            </div>
        </div>

        <!-- HERO SECTION (EDITORIAL STYLE WITH UNIFIED BLUE THEME) -->
        <div class="card border-0 rounded-4 overflow-hidden mb-5 text-white shadow-lg ef-hero-navy">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge rounded-pill px-3 py-1 fw-bold font-monospace badge-ef-sky" style="font-size: 0.75rem;">
                                OFFICIAL UEH STUDENT CLUB
                            </span>
                            <span class="badge rounded-pill bg-white text-dark px-3 py-1 fw-semibold font-monospace" style="font-size: 0.75rem;">
                                GENERATION 27
                            </span>
                        </div>
                        
                        <h1 class="display-5 fw-bold mb-3 text-white font-display" style="letter-spacing: -0.02em; line-height: 1.15;">
                            Where English meets <span class="fst-italic text-ef-sky fw-semibold">real impact.</span>
                        </h1>
                        
                        <p class="text-white-50 fs-6 mb-4 lh-base" style="max-width: 650px;">
                            <strong>Business English for Learners and Lovers</strong> — Câu lạc bộ Tiếng Anh tiên phong tại Đại học Kinh tế TP. Hồ Chí Minh từ năm 1995. Nơi nuôi dưỡng kỹ năng học thuật, bản lĩnh tranh biện và tinh thần gắn kết gia đình.
                        </p>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <a href="#departments" class="btn btn-ef-primary shadow-sm">
                                <i class="bi bi-diagram-3-fill"></i> Khám phá 4 Phòng Ban
                            </a>
                            <a href="#spirit" class="btn btn-ef-dark text-white border-secondary">
                                <i class="bi bi-stars"></i> Tinh thần B.E.L.L
                            </a>
                            <?php if (can_manage_club_profile()): ?>
                                <a href="<?= base_url('app/modules/club/edit.php') ?>" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold ms-md-auto">
                                    <i class="bi bi-pencil-square me-1"></i> Quản trị CLB
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column: Visual Stat Callout -->
                    <div class="col-lg-4 text-center text-lg-end">
                        <div class="d-inline-block text-start p-4 rounded-4" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); backdrop-filter: blur(8px);">
                            <div class="mb-3">
                                <div class="text-white-50 font-monospace small mb-1">DẤU ẤN LỊCH SỬ</div>
                                <div class="display-6 fw-bold text-white font-display mb-0">30+ <span class="fs-5 fw-normal font-sans text-white-50">Năm phát triển</span></div>
                            </div>
                            <div class="mb-3">
                                <div class="text-white-50 font-monospace small mb-1">CƠ CẤU CHUYÊN MÔN</div>
                                <div class="h3 fw-bold mb-0 font-display text-ef-sky">04 <span class="fs-6 fw-normal font-sans text-white">Phòng ban · 10 Cán bộ</span></div>
                            </div>
                            <div>
                                <div class="text-white-50 font-monospace small mb-1">CỘNG ĐỒNG BELLERS</div>
                                <div class="h3 fw-bold mb-0 text-white font-display"><?= number_format($totalMembers) ?>+ <span class="fs-6 fw-normal font-sans text-white-50">Thành viên hoạt động</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Quote Bar -->
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex flex-wrap justify-content-between align-items-center gap-2 small text-white-50">
                    <span class="fst-italic text-ef-sky" style="font-size: 0.95rem;">
                        "Four departments · One BELL family · ONCE A BELLER, ALWAYS A BELLER"
                    </span>
                    <span class="font-monospace text-uppercase" style="font-size: 0.72rem;">
                        Trụ sở: UEH Cơ sở B — 279 Nguyễn Tri Phương, Q.10, TP.HCM
                    </span>
                </div>
            </div>
        </div>

        <!-- SECTION 1: B.E.L.L. SPIRIT CARDS (CORE VALUES WITH UNIFIED BLUE THEME) -->
        <div id="spirit" class="mb-5">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">01 / CORE VALUES</span>
            </div>
            <h2 class="h3 fw-bold text-dark font-display mb-4">
                Tinh thần & Giá trị cốt lõi <span class="fst-italic text-ef-primary fw-bold">B.E.L.L</span>
            </h2>

            <div class="row g-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="ef-card-editorial p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="display-6 fw-bold font-display text-ef-primary">B</span>
                                <span class="badge badge-ef-blue rounded-pill px-2.5 py-1">GẮN KẾT</span>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-2">Bonding</h3>
                            <p class="text-muted small mb-0 lh-base">
                                Tạo dựng mối liên kết bền chặt, sự thấu hiểu và sẻ chia chân thành như những người thân trong một gia đình lớn.
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-top border-light font-monospace small text-muted">
                            Family & Connection
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="ef-card-editorial p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="display-6 fw-bold font-display text-dark">E</span>
                                <span class="badge badge-ef-sky rounded-pill px-2.5 py-1">NGÔN NGỮ</span>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-2">English</h3>
                            <p class="text-muted small mb-0 lh-base">
                                Thực hành tiếng Anh phản xạ, tự tin và học thuật thông qua các buổi sinh hoạt chuyên sâu và thử thách hàng tháng.
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-top border-light font-monospace small text-muted">
                            Active Practice
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="ef-card-editorial p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="display-6 fw-bold font-display text-ef-primary">L</span>
                                <span class="badge badge-ef-indigo rounded-pill px-2.5 py-1">HỌC HỎI</span>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-2">Learning</h3>
                            <p class="text-muted small mb-0 lh-base">
                                Môi trường tôi luyện kỹ năng mềm, tư duy lãnh đạo, quản trị dự án và năng lực thích ứng với thời đại mới.
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-top border-light font-monospace small text-muted">
                            Skills & Growth
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <div class="ef-card-editorial p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="display-6 fw-bold font-display text-dark">L</span>
                                <span class="badge badge-ef-teal rounded-pill px-2.5 py-1">NHIỆT HUYẾT</span>
                            </div>
                            <h3 class="h5 fw-bold text-dark mb-2">Liveliness</h3>
                            <p class="text-muted small mb-0 lh-base">
                                Năng lượng tích cực, sự sôi động và nhiệt huyết của tuổi trẻ luôn hiện diện trong mọi chương trình hành động.
                            </p>
                        </div>
                        <div class="pt-3 mt-3 border-top border-light font-monospace small text-muted">
                            Positive Energy
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN LAYOUT: LEFT CONTENT & RIGHT SIDEBAR -->
        <div class="row g-4">
            <!-- Left Main Column -->
            <div class="col-lg-8">
                
                <!-- SECTION 2: EXECUTIVE BOARD (THE PEOPLE BEHIND BELL) -->
                <div class="ef-card-editorial p-4 p-md-5 mb-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">02 / LEADERSHIP</span>
                            <h2 class="h3 fw-bold text-dark font-display mb-0 mt-1">
                                Ban Điều Hành CLB <span class="fst-italic text-muted" style="font-size: 1.05rem;">(Executive Board)</span>
                            </h2>
                        </div>
                        <?php if (is_club_manager()): ?>
                            <a href="<?= base_url('app/modules/club/members.php') ?>" class="btn btn-sm btn-ef-outline">
                                Quản lý danh sách <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($execMembers)): ?>
                        <div class="p-4 text-center text-muted bg-light rounded-4 small">
                            Chưa có thông tin ban điều hành được cập nhật.
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($execMembers as $em): ?>
                                <?php 
                                $isPresident = ($em['MemberRole'] === 'President');
                                $roleLabel = $isPresident ? 'Chủ nhiệm CLB (President)' : 'Phó Chủ nhiệm CLB (Vice President)';
                                $initial = mb_substr($em['FullName'], 0, 1, 'UTF-8');
                                ?>
                                <div class="col-md-6">
                                    <div class="p-4 rounded-4 border bg-white h-100 shadow-sm d-flex flex-column justify-content-between position-relative overflow-hidden" 
                                         style="border-top: 4px solid <?= $isPresident ? '#2563eb' : '#0f172a' ?> !important;">
                                        
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                                     style="width: 54px; height: 54px; font-size: 1.35rem; background: <?= $isPresident ? 'linear-gradient(135deg, #2563eb, #1d4ed8)' : '#0f172a' ?>; font-family: 'Inter', sans-serif;">
                                                    <?= htmlspecialchars($initial) ?>
                                                </div>
                                                <span class="badge <?= $isPresident ? 'badge-ef-blue' : 'badge-ef-sky' ?> rounded-pill px-3 py-1 font-monospace" style="font-size: 0.72rem;">
                                                    <?= $isPresident ? '★ PRESIDENT' : '◆ VICE PRESIDENT' ?>
                                                </span>
                                            </div>

                                            <h3 class="h5 fw-bold text-dark font-display mb-1"><?= htmlspecialchars($em['FullName']) ?></h3>
                                            <div class="text-muted small font-monospace mb-2">
                                                MSSV: <?= htmlspecialchars($em['StudentCode'] ?? '—') ?> · <?= htmlspecialchars($em['Cohort'] ?? 'K48') ?>
                                            </div>
                                            <div class="small text-secondary mb-3">
                                                <i class="bi bi-mortarboard text-ef-primary me-1"></i> <?= htmlspecialchars($em['Major'] ?? 'Chuyên ngành UEH') ?>
                                            </div>

                                            <?php if (!empty($em['Bio'])): ?>
                                                <div class="p-2.5 rounded-3 bg-light text-secondary small fst-italic lh-base mb-3" style="font-size: 0.82rem;">
                                                    "<?= htmlspecialchars($em['Bio']) ?>"
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="pt-2 border-top border-light small text-muted d-flex align-items-center gap-1.5">
                                            <i class="bi bi-envelope-fill text-muted"></i>
                                            <a href="mailto:<?= htmlspecialchars($em['Email']) ?>" class="text-decoration-none text-muted text-truncate">
                                                <?= htmlspecialchars($em['Email']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 3: THE FOUR DEPARTMENTS (UNIFIED BLUE THEME) -->
                <div id="departments" class="ef-card-editorial p-4 p-md-5 mb-5">
                    <div class="mb-4">
                        <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">03 / DEPARTMENTS</span>
                        <h2 class="h3 fw-bold text-dark font-display mb-1 mt-1">
                            Cơ Cấu 4 Phòng Ban Chuyên Môn
                        </h2>
                        <p class="text-muted small mb-0">
                            Bốn bộ phận cấu thành nên gia đình BELL, đảm nhận các mảng nghiệp vụ học thuật, truyền thông, đối ngoại và nhân sự.
                        </p>
                    </div>

                    <?php if (empty($departments)): ?>
                        <div class="p-4 text-center text-muted bg-light rounded-4 small">
                            Chưa có dữ liệu phòng ban.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-4">
                            <?php 
                            $deptIndex = 1;
                            foreach ($departments as $dept): 
                                $code = strtoupper($dept['DepartmentCode']);
                                $theme = match($code) {
                                    'HR'   => ['tag' => 'CHĂM SÓC & NỘI BỘ', 'border' => '#2563eb', 'badgeBg' => '#eff6ff', 'badgeColor' => '#1d4ed8'],
                                    'SPEC' => ['tag' => 'HỌC THUẬT & TIẾNG ANH', 'border' => '#4f46e5', 'badgeBg' => '#eef2ff', 'badgeColor' => '#4338ca'],
                                    'COMS' => ['tag' => 'TRUYỀN THÔNG & HÌNH ẢNH', 'border' => '#0284c7', 'badgeBg' => '#f0f9ff', 'badgeColor' => '#0369a1'],
                                    'ER'   => ['tag' => 'ĐỐI NGOẠI & TÀI TRỢ', 'border' => '#0d9488', 'badgeBg' => '#f0fdfa', 'badgeColor' => '#0f766e'],
                                    default => ['tag' => 'CHUYÊN MÔN', 'border' => '#0f172a', 'badgeBg' => '#f1f5f9', 'badgeColor' => '#334155'],
                                };
                            ?>
                                <div class="p-4 rounded-4 border bg-white shadow-2xs position-relative" style="border-left: 4px solid <?= $theme['border'] ?> !important;">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-monospace small fw-bold text-muted">0<?= $deptIndex++ ?> /</span>
                                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" style="background-color: <?= $theme['badgeBg'] ?>; color: <?= $theme['badgeColor'] ?>; font-size: 0.72rem;">
                                                PHÒNG <?= htmlspecialchars($code) ?> · <?= $theme['tag'] ?>
                                            </span>
                                        </div>
                                        <span class="font-monospace small text-muted">BELL UEH</span>
                                    </div>

                                    <h3 class="h4 fw-bold text-dark font-display mb-2">
                                        <?= htmlspecialchars($dept['DepartmentName']) ?>
                                    </h3>

                                    <p class="text-secondary small mb-3 lh-base">
                                        <?= htmlspecialchars($dept['Description']) ?>
                                    </p>

                                    <!-- Key Activities Checklist (with blue asterisk bullet) -->
                                    <?php if (!empty($dept['KeyActivities'])): ?>
                                        <div class="p-3 rounded-3 bg-light border border-light mb-3">
                                            <div class="fw-bold small text-dark mb-2 font-display">
                                                Hoạt động & Dự án trọng tâm:
                                            </div>
                                            <div class="row g-2 small text-secondary">
                                                <?php 
                                                $activities = array_filter(array_map('trim', explode("\n", $dept['KeyActivities'])));
                                                foreach ($activities as $act): 
                                                ?>
                                                    <div class="col-md-6 d-flex align-items-start gap-2">
                                                        <span class="text-ef-primary fw-bold">✱</span>
                                                        <span><?= htmlspecialchars($act) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Officers Team -->
                                    <div class="pt-3 border-top border-light">
                                        <div class="small fw-bold text-muted mb-2 font-monospace text-uppercase" style="font-size: 0.72rem;">
                                            Cán bộ phụ trách phòng ban:
                                        </div>
                                        <div class="row g-2">
                                            <?php foreach ($dept['Officers'] as $off): ?>
                                                <?php 
                                                $isHead = ($off['MemberRole'] === 'Head');
                                                $badgeText = $isHead ? 'Trưởng phòng (Head)' : 'Phó phòng (Vice Head)';
                                                ?>
                                                <div class="col-md-6">
                                                    <div class="p-2.5 rounded-3 border bg-white d-flex align-items-center gap-2.5 shadow-2xs">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0" 
                                                             style="width: 40px; height: 40px; font-size: 0.95rem; background: <?= $isHead ? '#2563eb' : '#64748b' ?>; font-family: 'Inter', sans-serif;">
                                                            <?= htmlspecialchars(mb_substr($off['FullName'], 0, 1, 'UTF-8')) ?>
                                                        </div>
                                                        <div class="overflow-hidden">
                                                            <div class="fw-bold text-dark text-truncate small"><?= htmlspecialchars($off['FullName']) ?></div>
                                                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                                <span class="badge <?= $isHead ? 'badge-ef-blue' : 'bg-light text-secondary border' ?>" style="font-size: 0.68rem;">
                                                                    <?= $badgeText ?>
                                                                </span>
                                                                <span class="text-muted font-monospace" style="font-size: 0.7rem;">
                                                                    <?= htmlspecialchars($off['Cohort'] ?? '') ?> · <?= htmlspecialchars($off['Major'] ?? '') ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 4: OUR STORY / HISTORICAL OVERVIEW -->
                <div class="ef-card-editorial p-4 p-md-5">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">04 / OUR STORY</span>
                    </div>
                    <h2 class="h3 fw-bold text-dark font-display mb-3">
                        Hành trình hơn 30 năm phát triển tại UEH
                    </h2>
                    <div class="text-secondary lh-lg mb-0 small" style="white-space: pre-line; font-size: 0.92rem;">
                        <?= htmlspecialchars($club['Description']) ?>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar Column -->
            <div class="col-lg-4">
                
                <!-- Recruitment Campaign Card (Make It Happen) -->
                <?php if ($activeCampaign): ?>
                    <div class="card border-0 rounded-4 mb-4 text-white p-4 shadow-md ef-hero-navy" style="border: 1px solid rgba(37, 99, 235, 0.4) !important;">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge badge-ef-blue rounded-pill px-3 py-1 font-monospace">RECRUITMENT</span>
                            <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 font-monospace">GEN 27</span>
                        </div>
                        <h3 class="h5 fw-bold text-white font-display mb-2">
                            <?= htmlspecialchars($activeCampaign['Title']) ?>
                        </h3>
                        <p class="text-white-50 small mb-3 lh-base">
                            Cơ hội gia nhập 4 phòng ban chuyên môn của BELL UEH. Nhận hồ sơ đến hết <strong><?= date('d/m/Y', strtotime($activeCampaign['EndDate'])) ?></strong>.
                        </p>
                        <a href="<?= base_url('index.php') ?>" class="btn btn-ef-primary w-100 shadow-sm">
                            <i class="bi bi-send-check-fill"></i> Nộp Đơn Ứng Tuyển Ngay
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Contact & Headquarters Card -->
                <div class="ef-card-editorial p-4 mb-4">
                    <h4 class="h6 fw-bold text-uppercase font-monospace tracking-wider text-muted mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt-fill text-ef-primary"></i> Trụ sở & Kênh liên hệ
                    </h4>

                    <div class="d-flex flex-column gap-3 small text-secondary">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: var(--ef-blue-50); color: var(--ef-blue-600);">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">ĐỊA ĐIỂM SINH HOẠT</div>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($club['Address']) ?></div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: var(--ef-blue-50); color: var(--ef-blue-600);">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">EMAIL CHÍNH THỨC</div>
                                <div class="fw-semibold">
                                    <a href="mailto:<?= htmlspecialchars($club['Email'] ?: 'bellclub@ueh.edu.vn') ?>" class="text-decoration-none text-dark hover-blue">
                                        <?= htmlspecialchars($club['Email'] ?: 'bellclub@ueh.edu.vn') ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: var(--ef-blue-50); color: var(--ef-blue-600);">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">HOTLINE BAN ĐIỀU HÀNH</div>
                                <div class="fw-semibold text-dark font-monospace"><?= htmlspecialchars($club['Phone'] ?: '02838551779') ?></div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: var(--ef-blue-50); color: var(--ef-blue-600);">
                                <i class="bi bi-facebook"></i>
                            </div>
                            <div>
                                <div class="text-muted font-monospace" style="font-size: 0.72rem;">FANPAGE FACEBOOK</div>
                                <div class="fw-semibold">
                                    <a href="<?= htmlspecialchars($club['FacebookUrl'] ?: 'https://facebook.com/bellueh') ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-ef-primary">
                                        facebook.com/bellueh <i class="bi bi-box-arrow-up-right fs-8"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Explore Events Banner -->
                <div class="ef-card-editorial p-4 text-center">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background-color: var(--ef-blue-50); color: var(--ef-blue-600);">
                        <i class="bi bi-calendar2-event fs-3"></i>
                    </div>
                    <h4 class="h6 fw-bold text-dark font-display mb-2">Tham Gia Cùng BELL</h4>
                    <p class="small text-muted mb-3">
                        Khám phá các buổi Speaking Circle định kỳ, Workshop tranh biện và sự kiện tiếng Anh tại UEH.
                    </p>
                    <a href="<?= base_url('index.php') ?>" class="btn btn-ef-dark w-100">
                        Xem lịch sự kiện <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
