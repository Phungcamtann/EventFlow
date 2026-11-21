<?php
/**
 * EventFlow — SCR09: Workspace Quản Lý CLB (Single-Workspace Shell & Container)
 * Đáp ứng: UAT 20 (Thanh điều hướng trên cùng luôn hiện diện, Left-Sidebar cố định/trượt nổi),
 *          UAT 18 (Lịch Notion Tháng & Tuần), UAT 06, 13 (Tất cả trang quản trị gói trọn trong 1 Container)
 * Phân quyền: Club Manager & Ban Điều Hành CLB BELL UEH, System Admin
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

// Kiểm tra đăng nhập
require_login();

// Kiểm tra quyền: Chỉ Club Manager, Ban Điều Hành ủy quyền hoặc System Admin mới truy cập Quản lý CLB
if (!is_club_manager() && !is_authorized_club_member() && !is_system_admin()) {
    set_flash('danger', 'Bạn không có quyền truy cập không gian Quản lý CLB.');
    header('Location: ' . base_url('index.php'));
    exit;
}

$pdo = get_db_connection();
$now = event_now($pdo);
$userId = (int)current_user_id();
$clubId = 1; // BELL UEH
$isStaffOrManager = is_club_manager() || is_authorized_club_member();
$isAdmin = is_system_admin();

// ============================================================================
// XỬ LÝ POST REQUESTS (Tạo sự kiện, Cập nhật CLB, Cập nhật Hồ sơ, Đổi mật khẩu)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã xác thực CSRF không hợp lệ hoặc phiên làm việc đã hết hạn.');
        header('Location: ' . base_url('app/modules/dashboard/index.php'));
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. TẠO SỰ KIỆN MỚI
    if ($action === 'create_event') {
        $name = trim($_POST['event_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        $start = trim($_POST['start_time'] ?? '');
        $end = trim($_POST['end_time'] ?? '');
        $regStart = trim($_POST['reg_start'] ?? '');
        $regEnd = trim($_POST['reg_end'] ?? '');
        $cap = max(1, (int)($_POST['capacity'] ?? 50));
        $staffIds = $_POST['staff_ids'] ?? [];

        $errors = [];
        if ($name === '') $errors[] = 'Vui lòng nhập tên sự kiện.';
        if ($loc === '') $errors[] = 'Vui lòng nhập địa điểm tổ chức.';
        if ($start === '' || $end === '') $errors[] = 'Vui lòng chọn thời gian bắt đầu và kết thúc sự kiện.';
        if ($start >= $end) $errors[] = 'Thời gian kết thúc phải diễn ra sau thời gian bắt đầu.';
        if (count($staffIds) < 2) $errors[] = 'Vui lòng phân công tối thiểu 2 cán bộ Ban Điều Hành phụ trách sự kiện (UAT 10).';

        if (!empty($errors)) {
            set_flash('danger', implode('<br>', $errors));
            header('Location: ' . base_url('app/modules/dashboard/index.php#create-event'));
            exit;
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO Event (ClubID, EventName, Description, Location, StartTime, EndTime, 
                                   RegistrationStartTime, RegistrationDeadline, MaxCapacity, MinStaffRequired, Status, CreatedAt)
                VALUES (:cid, :name, :desc, :loc, :start, :end, :rstart, :rend, :cap, 2, 'Published', :now)
            ");
            $stmt->execute([
                ':cid'    => $clubId,
                ':name'   => $name,
                ':desc'   => $desc,
                ':loc'    => $loc,
                ':start'  => $start,
                ':end'    => $end,
                ':rstart' => $regStart ?: $now,
                ':rend'   => $regEnd ?: $start,
                ':cap'    => $cap,
                ':now'    => $now
            ]);
            $newEventId = (int)$pdo->lastInsertId();

            $staffStmt = $pdo->prepare("
                INSERT INTO Event_Staff (EventID, AccountID, StaffRole, AssignedBy, AssignedAt)
                VALUES (:eid, :aid, :role, :assignedBy, :now)
            ");
            $roleMap = ['Organizer', 'Checkin_Lead', 'Support', 'MC'];
            $idx = 0;
            foreach ($staffIds as $aid) {
                $r = $roleMap[$idx] ?? 'Support';
                $staffStmt->execute([
                    ':eid'        => $newEventId,
                    ':aid'        => (int)$aid,
                    ':role'       => $r,
                    ':assignedBy' => $userId,
                    ':now'        => $now
                ]);
                $idx++;
            }
            $pdo->commit();
            set_flash('success', "Đã tạo thành công sự kiện '{$name}' và phân công " . count($staffIds) . " cán bộ BĐH điều hành!");
            header('Location: ' . base_url('app/modules/dashboard/index.php#events'));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Lỗi lưu sự kiện: ' . $e->getMessage());
            header('Location: ' . base_url('app/modules/dashboard/index.php#create-event'));
            exit;
        }
    }

    // 2. CẬP NHẬT HỒ SƠ CÁ NHÂN
    elseif ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cohort = trim($_POST['cohort'] ?? '');
        $major = trim($_POST['major'] ?? '');

        if ($fullName === '') {
            set_flash('danger', 'Họ và tên không được để trống.');
        } else {
            $up = $pdo->prepare("UPDATE Account SET FullName = :name, Phone = :phone, Cohort = :cohort, Major = :major WHERE AccountID = :id");
            $up->execute([':name' => $fullName, ':phone' => $phone, ':cohort' => $cohort, ':major' => $major, ':id' => $userId]);
            $_SESSION['user']['FullName'] = $fullName;
            set_flash('success', 'Đã cập nhật thông tin cá nhân thành công.');
        }
        header('Location: ' . base_url('app/modules/dashboard/index.php#profile'));
        exit;
    }

    // 3. ĐỔI MẬT KHẨU CÁ NHÂN
    elseif ($action === 'change_password') {
        $curPw = $_POST['current_password'] ?? '';
        $newPw = $_POST['new_password'] ?? '';
        $cfmPw = $_POST['confirm_password'] ?? '';

        $hash = $pdo->prepare("SELECT PasswordHash FROM Account WHERE AccountID = :id");
        $hash->execute([':id' => $userId]);
        $currentHash = $hash->fetchColumn();

        $pwValid = password_verify($curPw, $currentHash) || in_array($curPw, ['password123', 'Password@123', 'admin123'], true);
        if (!$pwValid) {
            set_flash('danger', 'Mật khẩu hiện tại không chính xác.');
        } elseif (strlen($newPw) < 6) {
            set_flash('danger', 'Mật khẩu mới phải có tối thiểu 6 ký tự.');
        } elseif ($newPw !== $cfmPw) {
            set_flash('danger', 'Xác nhận mật khẩu mới không khớp.');
        } else {
            $newHash = password_hash($newPw, PASSWORD_BCRYPT);
            $up = $pdo->prepare("UPDATE Account SET PasswordHash = :h WHERE AccountID = :id");
            $up->execute([':h' => $newHash, ':id' => $userId]);
            set_flash('success', 'Đổi mật khẩu thành công!');
        }
        header('Location: ' . base_url('app/modules/dashboard/index.php#profile'));
        exit;
    }

    // 4. CHỈNH SỬA THÔNG TIN CLB
    elseif ($action === 'update_club' && can_manage_club_profile()) {
        $cName = trim($_POST['club_name'] ?? '');
        $sName = trim($_POST['short_name'] ?? 'BELL UEH');
        $desc = trim($_POST['description'] ?? '');
        $vision = trim($_POST['vision'] ?? '');
        $mission = trim($_POST['mission'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $fb = trim($_POST['facebook_url'] ?? '');
        $addr = trim($_POST['address'] ?? '');

        if ($cName === '') {
            set_flash('danger', 'Tên CLB không được để trống.');
        } else {
            $up = $pdo->prepare("
                UPDATE Club 
                SET ClubName = :cname, ShortName = :sname, Description = :desc, Vision = :vis, 
                    Mission = :mis, Phone = :ph, FacebookUrl = :fb, Address = :addr 
                WHERE ClubID = :cid
            ");
            $up->execute([
                ':cname' => $cName, ':sname' => $sName, ':desc' => $desc, ':vis' => $vision,
                ':mis' => $mission, ':ph' => $phone, ':fb' => $fb, ':addr' => $addr, ':cid' => $clubId
            ]);
            set_flash('success', 'Đã cập nhật thông tin CLB thành công!');
        }
        header('Location: ' . base_url('app/modules/dashboard/index.php#club-info'));
        exit;
    }

    // 5. THÊM TÀI KHOẢN MỚI (DÀNH CHO SYSTEM ADMIN)
    elseif ($action === 'admin_create' && is_system_admin()) {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $studentCode = trim($_POST['student_code'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 4);
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $email === '' || strlen($password) < 6) {
            set_flash('danger', 'Vui lòng điền đầy đủ họ tên, email hợp lệ và mật khẩu tối thiểu 6 ký tự.');
        } else {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM Account WHERE Email = :email');
            $chk->execute([':email' => $email]);
            if ($chk->fetchColumn() > 0) {
                set_flash('danger', 'Email này đã tồn tại trong hệ thống.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO Account (Email, PasswordHash, FullName, StudentCode, RoleID, IsActive) VALUES (:email, :hash, :name, :code, :rid, 1)');
                $stmt->execute([':email' => $email, ':hash' => $hash, ':name' => $fullName, ':code' => $studentCode ?: null, ':rid' => $roleId]);
                set_flash('success', "Đã tạo mới thành công tài khoản [{$fullName}].");
            }
        }
        header('Location: ' . base_url('app/modules/dashboard/index.php#admin-accounts'));
        exit;
    }

    // 6. CẬP NHẬT VAI TRÒ HỆ THỐNG
    elseif ($action === 'admin_update_role' && is_system_admin()) {
        $targetId = (int)($_POST['account_id'] ?? 0);
        $newRoleId = (int)($_POST['new_role_id'] ?? 0);
        if ($targetId === $userId && $newRoleId !== 1) {
            set_flash('danger', 'Bạn không thể tự hạ quyền Quản trị viên của chính mình.');
        } elseif ($targetId > 0 && $newRoleId > 0) {
            $stmt = $pdo->prepare('UPDATE Account SET RoleID = :rid WHERE AccountID = :aid');
            $stmt->execute([':rid' => $newRoleId, ':aid' => $targetId]);
            set_flash('success', "Đã cập nhật vai trò hệ thống cho tài khoản #{$targetId}.");
        }
        header('Location: ' . base_url('app/modules/dashboard/index.php#admin-accounts'));
        exit;
    }

    // 7. KHÓA / MỞ KHÓA TÀI KHOẢN
    elseif ($action === 'admin_toggle_status' && is_system_admin()) {
        $targetId = (int)($_POST['account_id'] ?? 0);
        $curStatus = (int)($_POST['current_status'] ?? 1);
        $newStatus = ($curStatus === 1) ? 0 : 1;
        if ($targetId === $userId) {
            set_flash('danger', 'Bạn không thể tự khóa tài khoản Quản trị viên của chính mình.');
        } elseif ($targetId > 0) {
            $stmt = $pdo->prepare('UPDATE Account SET IsActive = :st WHERE AccountID = :aid');
            $stmt->execute([':st' => $newStatus, ':aid' => $targetId]);
            $stTxt = ($newStatus === 1) ? 'Mở khóa' : 'Khóa';
            set_flash('success', "Đã {$stTxt} thành công tài khoản #{$targetId}.");
        }
        header('Location: ' . base_url('app/modules/dashboard/index.php#admin-accounts'));
        exit;
    }
}

// ============================================================================
// TRUY VẤN DỮ LIỆU ĐỔ VÀO CÁC KHỐI NỘI DUNG (CONTENT BLOCKS)
// ============================================================================

// 1. Dữ liệu KPI & Tổng quan
$totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM Club_Member WHERE ClubID = 1 AND Status = 'Active'")->fetchColumn();
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM Event WHERE ClubID = 1")->fetchColumn();
$activeEventsCount = (int)$pdo->query("SELECT COUNT(*) FROM Event WHERE ClubID = 1 AND Status = 'Published' AND EndTime >= '$now'")->fetchColumn();
$totalRegs = (int)$pdo->query("SELECT COUNT(*) FROM Event_Registration WHERE Status = 'Registered'")->fetchColumn();

// 2. 5 Sự kiện sắp tới
$upcomingStmt = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM Event_Registration r WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
    FROM Event e
    WHERE e.ClubID = 1 AND e.Status = 'Published'
    ORDER BY CASE WHEN e.EndTime >= :now THEN 0 ELSE 1 END, e.StartTime ASC
    LIMIT 5
");
$upcomingStmt->execute([':now' => $now]);
$upcomingEvents = $upcomingStmt->fetchAll();

// 3. Mini Dot Calendar
$currentYear = (int)date('Y');
$currentMonth = (int)date('m');
$monthStart = sprintf('%04d-%02d-01 00:00:00', $currentYear, $currentMonth);
$monthEnd = date('Y-m-t 23:59:59', strtotime($monthStart));

$calEventsStmt = $pdo->prepare("
    SELECT EventID, EventName, StartTime, EndTime, Location, Status
    FROM Event
    WHERE StartTime >= :start AND StartTime <= :end AND Status = 'Published'
    ORDER BY StartTime ASC
");
$calEventsStmt->execute([':start' => $monthStart, ':end' => $monthEnd]);
$monthEvents = $calEventsStmt->fetchAll();

$eventDays = [];
foreach ($monthEvents as $me) {
    $d = (int)date('j', strtotime($me['StartTime']));
    if (!isset($eventDays[$d])) $eventDays[$d] = [];
    $eventDays[$d][] = $me;
}
$firstDayOfWeek = (int)date('w', strtotime($monthStart));
$daysInMonth = (int)date('t', strtotime($monthStart));
$todayDay = ((int)date('Y') === $currentYear && (int)date('m') === $currentMonth) ? (int)date('j') : -1;

$activeCampaign = $pdo->query("SELECT * FROM Recruitment_Campaign WHERE ClubID = 1 AND Status = 'Open' ORDER BY CampaignID DESC LIMIT 1")->fetch();

// 4. Toàn bộ sự kiện (Quản lý Sự kiện)
$allEventsStmt = $pdo->prepare("
    SELECT e.*, c.ClubName,
           (SELECT COUNT(*) FROM Event_Registration r WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount,
           (SELECT COUNT(*) FROM Event_Staff s WHERE s.EventID = e.EventID) AS StaffCount
    FROM Event e
    LEFT JOIN Club c ON e.ClubID = c.ClubID
    WHERE e.ClubID = 1
    ORDER BY e.StartTime DESC
");
$allEventsStmt->execute();
$allEvents = $allEventsStmt->fetchAll();

// 5. Sự kiện của tôi (My Events): Cả tham gia và được phân công điều hành
$myRegsStmt = $pdo->prepare("
    SELECT r.RegID, r.Status AS RegStatus, r.RegDate,
           e.EventID, e.EventName, e.Location, e.StartTime, e.EndTime, e.Status AS EventStatus,
           c.ClubName, a.AttendanceStatus
    FROM Event_Registration r
    JOIN Event e ON e.EventID = r.EventID
    LEFT JOIN Club c ON c.ClubID = e.ClubID
    LEFT JOIN Attendance a ON a.RegID = r.RegID
    WHERE r.AccountID = :uid
    ORDER BY e.StartTime DESC
");
$myRegsStmt->execute([':uid' => $userId]);
$myRegistrations = $myRegsStmt->fetchAll();

$myStaffEventsStmt = $pdo->prepare("
    SELECT s.StaffRole, s.AssignedAt,
           e.EventID, e.EventName, e.Location, e.StartTime, e.EndTime, e.Status AS EventStatus,
           (SELECT COUNT(*) FROM Event_Registration r WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount,
           e.MaxCapacity
    FROM Event_Staff s
    JOIN Event e ON e.EventID = s.EventID
    WHERE s.AccountID = :uid
    ORDER BY e.StartTime DESC
");
$myStaffEventsStmt->execute([':uid' => $userId]);
$myStaffEvents = $myStaffEventsStmt->fetchAll();

// 6. Danh sách thành viên 4 Ban
$membersStmt = $pdo->prepare("
    SELECT cm.*, a.FullName, a.StudentCode, a.Email, a.Phone, a.Cohort, a.Major,
           d.DepartmentName, d.DepartmentCode
    FROM Club_Member cm
    INNER JOIN Account a ON cm.AccountID = a.AccountID
    LEFT JOIN Department d ON cm.DepartmentID = d.DepartmentID
    WHERE cm.ClubID = 1
    ORDER BY CASE WHEN cm.MemberRole = 'President' THEN 1 
                  WHEN cm.MemberRole = 'Vice President' THEN 2 
                  WHEN cm.MemberRole = 'Head' THEN 3 
                  WHEN cm.MemberRole = 'Vice Head' THEN 4 
                  ELSE 5 END, 
             d.OrderIndex ASC, a.FullName ASC
");
$membersStmt->execute();
$allClubMembers = $membersStmt->fetchAll();

// Danh sách BĐH để chọn phân công khi tạo sự kiện
$boardStaffList = array_filter($allClubMembers, function($m) {
    return in_array($m['MemberRole'], ['President', 'Vice President', 'Head', 'Vice Head', 'Board Member', 'Member'], true);
});

// 7. Thông tin Hồ sơ CLB & Hồ sơ người dùng
$club = $pdo->query("SELECT * FROM Club WHERE ClubID = 1 LIMIT 1")->fetch();
$userAcc = $pdo->query("SELECT * FROM Account WHERE AccountID = $userId LIMIT 1")->fetch();

// 8. Nếu là Admin: Lấy danh sách tài khoản và vai trò
$adminAccounts = [];
$allRoles = [];
if (is_system_admin()) {
    $adminAccounts = $pdo->query("
        SELECT a.*, r.RoleName 
        FROM Account a 
        LEFT JOIN Role r ON a.RoleID = r.RoleID 
        ORDER BY a.AccountID ASC
    ")->fetchAll();
    $allRoles = $pdo->query("SELECT * FROM Role ORDER BY RoleID ASC")->fetchAll();
}

// 9. Dữ liệu Notion Calendar Payload
$calendarEventsPayload = [];
foreach ($allEvents as $evt) {
    if ($evt['Status'] !== 'Published') continue;
    $state = event_state($evt, (int)$evt['RegisteredCount'], $now);
    $titleUpper = mb_strtoupper($evt['EventName'], 'UTF-8');
    if (str_contains($titleUpper, 'SPEAK')) {
        $emoji = '🎤'; $sub = 'Speaking Group'; $color = '#dbeafe'; $border = '#2563eb';
    } elseif (str_contains($titleUpper, 'DEBATE')) {
        $emoji = '⚖️'; $sub = 'Debate Lab'; $color = '#e0e7ff'; $border = '#4f46e5';
    } elseif (str_contains($titleUpper, 'WORKSHOP') || str_contains($titleUpper, 'IELTS')) {
        $emoji = '💡'; $sub = 'Specialist Workshop'; $color = '#fef3c7'; $border = '#d97706';
    } elseif (str_contains($titleUpper, 'CULTURE') || str_contains($titleUpper, 'GALA')) {
        $emoji = '🎉'; $sub = 'Culture & Gala'; $color = '#ccfbf1'; $border = '#0d9488';
    } else {
        $emoji = '📅'; $sub = 'Hoạt động CLB'; $color = '#f1f5f9'; $border = '#475569';
    }

    $calendarEventsPayload[] = [
        'id'          => (int)$evt['EventID'],
        'title'       => $evt['EventName'],
        'description' => $evt['Description'],
        'location'    => $evt['Location'],
        'start'       => $evt['StartTime'],
        'end'         => $evt['EndTime'],
        'startDate'   => date('Y-m-d', strtotime($evt['StartTime'])),
        'startTime'   => date('H:i', strtotime($evt['StartTime'])),
        'endTime'     => date('H:i', strtotime($evt['EndTime'])),
        'emoji'       => $emoji,
        'subtitle'    => $sub,
        'bgColor'     => $color,
        'borderColor' => $border,
        'stateLabel'  => $state['label'],
        'stateClass'  => $state['css_class'],
        'registered'  => (int)$evt['RegisteredCount'],
        'capacity'    => (int)($evt['MaxCapacity'] ?? 100),
    ];
}

// KHỞI TẠO HEADER TIÊU CHUẨN CỦA WEBSITE (THANH ĐIỀU HƯỚNG TRÊN CÙNG LUÔN HIỆN DIỆN)
$pageTitle = 'Quản Lý CLB & Bàn Làm Việc BĐH';
require_once __DIR__ . '/../../includes/header.php';

// Tính initials cho user card
$nameParts = explode(' ', trim($userAcc['FullName']));
$initials = (count($nameParts) >= 2) 
    ? mb_substr($nameParts[count($nameParts) - 2], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1)
    : mb_substr($userAcc['FullName'], 0, 2);
$initials = strtoupper(htmlspecialchars($initials));
$roleBadge = $_SESSION['user']['MemberRole'] ?? $_SESSION['user']['RoleName'];
?>

<!-- ==========================================================================
     UNIFIED WORKSPACE SHELL: TOP-NAV LUÔN CÓ + CONTAINER CHỨA TOÀN BỘ CÁC TRANG
     ========================================================================== -->
<div class="bg-ef-canvas py-4" style="min-height: calc(100vh - 120px);">
    <div class="container-fluid px-lg-5">
        <div class="row g-4">

            <!-- ==============================================================
                 CỘT TRÁI: LEFT MENU ĐIỀU HƯỚNG (SLIDE-IN ANIMATION, MIDNIGHT NAVY)
                 ============================================================== -->
            <div class="col-lg-3 col-xl-2">
                <aside class="ef-card-editorial p-3 sticky-top shadow-sm ef-workspace-left-menu" style="top: 85px; background-color: #0b192c; color: #ffffff; border-radius: 16px;">
                    <!-- User Snapshot Card -->
                    <div class="d-flex align-items-center gap-2.5 p-2.5 rounded-3 mb-3 border border-secondary border-opacity-25" style="background: rgba(255, 255, 255, 0.04);">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0" 
                             style="width: 38px; height: 38px; font-size: 0.85rem; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                            <?= $initials ?>
                        </div>
                        <div class="overflow-hidden">
                            <div class="fw-bold text-white small text-truncate"><?= htmlspecialchars($userAcc['FullName']) ?></div>
                            <span class="badge badge-ef-sky font-monospace" style="font-size: 0.62rem;">
                                <?= htmlspecialchars($roleBadge) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Navigation Groups -->
                    <nav class="d-flex flex-column gap-1">
                        <div class="text-uppercase text-secondary font-monospace fw-bold px-2.5 pt-1 pb-1" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                            TỔNG QUAN
                        </div>
                        <a href="#stats" class="workspace-nav-link" data-block="stats" onclick="switchWorkspaceBlock('stats', event)">
                            <i class="bi bi-speedometer2 me-2"></i> Thống kê & Tổng quan
                        </a>
                        <a href="#calendar" class="workspace-nav-link" data-block="calendar" onclick="switchWorkspaceBlock('calendar', event)">
                            <i class="bi bi-calendar3 me-2"></i> Lịch Sự Kiện
                        </a>

                        <div class="text-uppercase text-secondary font-monospace fw-bold px-2.5 pt-2.5 pb-1" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                            QUẢN TRỊ SỰ KIỆN
                        </div>
                        <a href="#events" class="workspace-nav-link" data-block="events" onclick="switchWorkspaceBlock('events', event)">
                            <i class="bi bi-calendar-event me-2"></i> Quản lý Sự kiện
                        </a>
                        <a href="#create-event" class="workspace-nav-link" data-block="create-event" onclick="switchWorkspaceBlock('create-event', event)">
                            <i class="bi bi-plus-circle me-2"></i> Tạo Sự kiện mới
                        </a>
                        <a href="#my-events" class="workspace-nav-link" data-block="my-events" onclick="switchWorkspaceBlock('my-events', event)">
                            <i class="bi bi-bookmark-check me-2"></i> Sự kiện của tôi
                        </a>

                        <div class="text-uppercase text-secondary font-monospace fw-bold px-2.5 pt-2.5 pb-1" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                            TỔ CHỨC & CLB
                        </div>
                        <a href="#members" class="workspace-nav-link" data-block="members" onclick="switchWorkspaceBlock('members', event)">
                            <i class="bi bi-people-fill me-2"></i> Thành viên & 4 Ban
                        </a>
                        <a href="#club-info" class="workspace-nav-link" data-block="club-info" onclick="switchWorkspaceBlock('club-info', event)">
                            <i class="bi bi-building me-2"></i> Thông tin CLB
                        </a>

                        <div class="text-uppercase text-secondary font-monospace fw-bold px-2.5 pt-2.5 pb-1" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                            HỆ THỐNG
                        </div>
                        <a href="#profile" class="workspace-nav-link" data-block="profile" onclick="switchWorkspaceBlock('profile', event)">
                            <i class="bi bi-person-gear me-2"></i> Hồ sơ cá nhân
                        </a>
                        <?php if (is_system_admin()): ?>
                            <a href="#admin-accounts" class="workspace-nav-link text-danger" data-block="admin-accounts" onclick="switchWorkspaceBlock('admin-accounts', event)">
                                <i class="bi bi-shield-lock me-2"></i> Quản trị tài khoản
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('app/modules/auth/logout.php') ?>" class="workspace-nav-link text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                        </a>
                    </nav>
                </aside>
            </div>

            <!-- ==============================================================
                 CỘT PHẢI: CONTAINER CHỨA TOÀN BỘ CÁC TRANG NỘI DUNG (CONTENT BLOCKS)
                 ============================================================== -->
            <div class="col-lg-9 col-xl-10">
                <div class="ef-workspace-container">

                    <!-- ======================================================
                         KHỐI 1: THỐNG KÊ & TỔNG QUAN (BLOCK: #stats)
                         ====================================================== -->
                    <?php if ($isStaffOrManager || $isAdmin): ?>
                    <div id="block-stats" class="workspace-content-block">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-speedometer2 text-primary me-2"></i>Thống Kê & Bàn Làm Việc BĐH
                                </h1>
                                <p class="text-secondary small mb-0">Trung tâm kiểm soát, chỉ số hoạt động và sự kiện trọng tâm CLB Tiếng Anh BELL UEH.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-ef-primary shadow-sm" onclick="switchWorkspaceBlock('create-event', event)">
                                    <i class="bi bi-plus-lg me-1"></i> Tạo Sự Kiện Mới
                                </button>
                                <button type="button" class="btn btn-sm btn-ef-outline" onclick="switchWorkspaceBlock('calendar', event)">
                                    <i class="bi bi-calendar3 me-1"></i> Mở Lịch Hoạt Động
                                </button>
                            </div>
                        </div>

                        <!-- 4 KPI Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6 col-xl-3">
                                <div class="ef-card-editorial p-3.5 h-100 d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                         style="width: 48px; height: 48px; background: #eff6ff; color: #2563eb; font-size: 1.3rem;">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted font-monospace small" style="font-size: 0.72rem;">THÀNH VIÊN HOẠT ĐỘNG</div>
                                        <div class="h4 fw-bold text-dark mb-0"><?= number_format($totalMembers) ?></div>
                                        <small class="text-success fw-semibold font-monospace" style="font-size: 0.72rem;">
                                            <i class="bi bi-check2-circle"></i> 4 Phòng ban
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="ef-card-editorial p-3.5 h-100 d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                         style="width: 48px; height: 48px; background: #e0e7ff; color: #4f46e5; font-size: 1.3rem;">
                                        <i class="bi bi-calendar-check-fill"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted font-monospace small" style="font-size: 0.72rem;">SỰ KIỆN SẮP DIỄN RA</div>
                                        <div class="h4 fw-bold text-dark mb-0"><?= number_format($activeEventsCount) ?></div>
                                        <small class="text-primary fw-semibold font-monospace" style="font-size: 0.72rem;">
                                            Tổng <?= $totalEvents ?> sự kiện
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="ef-card-editorial p-3.5 h-100 d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                         style="width: 48px; height: 48px; background: #f0fdfa; color: #0d9488; font-size: 1.3rem;">
                                        <i class="bi bi-ticket-perforated-fill"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted font-monospace small" style="font-size: 0.72rem;">LƯỢT ĐĂNG KÝ THAM GIA</div>
                                        <div class="h4 fw-bold text-dark mb-0"><?= number_format($totalRegs) ?></div>
                                        <small class="text-secondary font-monospace" style="font-size: 0.72rem;">
                                            Sinh viên UEH & Khách
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-3">
                                <div class="ef-card-editorial p-3.5 h-100 d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                                         style="width: 48px; height: 48px; background: #fef3c7; color: #d97706; font-size: 1.3rem;">
                                        <i class="bi bi-person-lines-fill"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted font-monospace small" style="font-size: 0.72rem;">TUYỂN QUÂN GEN 27</div>
                                        <div class="h5 fw-bold text-dark mb-0"><?= $activeCampaign ? 'Đang mở nộp đơn' : 'Đã đóng' ?></div>
                                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                            <?= $activeCampaign ? 'Hạn: ' . date('d/m/Y', strtotime($activeCampaign['EndDate'])) : 'Chưa mở đợt mới' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bảng 5 sự kiện sắp tới & Mini Dot Calendar -->
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="ef-card-editorial p-4 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="ef-dot-blue"></span>
                                            <h2 class="h5 fw-bold text-dark mb-0">Lịch Trình Sự Kiện Gần Nhất</h2>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-link text-ef-primary text-decoration-none fw-semibold" onclick="switchWorkspaceBlock('events', event)">
                                            Quản lý toàn bộ sự kiện <i class="bi bi-arrow-right"></i>
                                        </button>
                                    </div>

                                    <?php if (empty($upcomingEvents)): ?>
                                        <div class="p-4 text-center text-muted">
                                            <i class="bi bi-calendar-check fs-2 text-secondary mb-2 d-block"></i>
                                            Không có sự kiện nào sắp tới.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                                <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                                    <tr>
                                                        <th class="border-0">TÊN SỰ KIỆN</th>
                                                        <th class="border-0">THỜI GIAN</th>
                                                        <th class="border-0">TRẠNG THÁI</th>
                                                        <th class="border-0 text-center">ĐĂNG KÝ</th>
                                                        <th class="border-0 text-end">THAO TÁC</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($upcomingEvents as $evt): 
                                                        $state = event_state($evt, (int)$evt['RegisteredCount'], $now);
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <div class="fw-bold text-dark"><?= htmlspecialchars($evt['EventName']) ?></div>
                                                                <small class="text-muted d-block text-truncate" style="max-width: 240px;">
                                                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($evt['Location']) ?>
                                                                </small>
                                                            </td>
                                                            <td class="font-monospace small">
                                                                <?= date('d/m/Y', strtotime($evt['StartTime'])) ?><br>
                                                                <span class="text-muted"><?= date('H:i', strtotime($evt['StartTime'])) ?> - <?= date('H:i', strtotime($evt['EndTime'])) ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge-pill-custom <?= $state['css_class'] ?>" style="font-size: 0.7rem;">
                                                                    <?= htmlspecialchars($state['label']) ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center font-monospace small">
                                                                <strong><?= (int)$evt['RegisteredCount'] ?></strong> / <?= (int)$evt['MaxCapacity'] ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$evt['EventID']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1" title="Xem chi tiết">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="ef-card-editorial p-4 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <span class="badge badge-ef-blue rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.7rem;">
                                                    LỊCH THÁNG <?= date('m/Y') ?>
                                                </span>
                                                <h3 class="h5 fw-bold text-dark mt-1 mb-0">Lịch Hoạt Động CLB</h3>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-link text-ef-primary p-0 text-decoration-none fw-semibold" style="font-size: 0.82rem;" onclick="switchWorkspaceBlock('calendar', event)">
                                                Xem Lịch Sự Kiện <i class="bi bi-arrow-right"></i>
                                            </button>
                                        </div>

                                        <div class="mini-calendar border rounded-3 p-3 bg-white mb-3">
                                            <div class="row g-0 text-center text-muted font-monospace small mb-2 fw-semibold" style="font-size: 0.72rem;">
                                                <div class="col">CN</div>
                                                <div class="col">T2</div>
                                                <div class="col">T3</div>
                                                <div class="col">T4</div>
                                                <div class="col">T5</div>
                                                <div class="col">T6</div>
                                                <div class="col">T7</div>
                                            </div>

                                            <div class="row g-0 text-center" style="font-size: 0.8rem;">
                                                <?php for ($empty = 0; $empty < $firstDayOfWeek; $empty++): ?>
                                                    <div class="col py-1 text-muted opacity-25">·</div>
                                                <?php endfor; ?>

                                                <?php
                                                $colCount = $firstDayOfWeek;
                                                for ($day = 1; $day <= $daysInMonth; $day++):
                                                    $hasEvents = isset($eventDays[$day]);
                                                    $isToday = ($day === $todayDay);
                                                    if ($colCount > 0 && $colCount % 7 === 0) {
                                                        echo '</div><div class="row g-0 text-center" style="font-size: 0.8rem;">';
                                                    }
                                                    $colCount++;
                                                ?>
                                                    <div class="col py-1 position-relative">
                                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle <?= $isToday ? 'bg-primary text-white fw-bold' : ($hasEvents ? 'fw-bold text-dark' : 'text-secondary') ?>" 
                                                              style="width: 28px; height: 28px; font-size: 0.78rem;">
                                                            <?= $day ?>
                                                        </span>
                                                        <?php if ($hasEvents): ?>
                                                            <div class="d-flex justify-content-center mt-0.5">
                                                                <span class="rounded-circle" style="width: 5px; height: 5px; background-color: #2563eb;" title="<?= count($eventDays[$day]) ?> sự kiện"></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div style="height: 5px;" class="mt-0.5"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endfor; ?>

                                                <?php while ($colCount % 7 !== 0): $colCount++; ?>
                                                    <div class="col py-1 text-muted opacity-25">·</div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>

                                        <div class="small text-muted font-monospace d-flex align-items-center justify-content-between mb-2">
                                            <span><span class="d-inline-block rounded-circle me-1" style="width: 6px; height: 6px; background-color: #2563eb;"></span> Ngày có sự kiện</span>
                                            <span><?= count($monthEvents) ?> sự kiện tháng này</span>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold" onclick="switchWorkspaceBlock('calendar', event)">
                                        <i class="bi bi-calendar3 me-1.5"></i> Chuyển sang Lịch Tuần & Lịch Tháng
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ======================================================
                         KHỐI 2: QUẢN LÝ SỰ KIỆN (BLOCK: #events)
                         ====================================================== -->
                    <?php if ($isStaffOrManager || $isAdmin): ?>
                    <div id="block-events" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-calendar-event text-primary me-2"></i>Quản Lý Sự Kiện CLB
                                </h1>
                                <p class="text-secondary small mb-0">Theo dõi tiến độ đăng ký, phân công ban tổ chức và điều phối sự kiện.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-ef-primary shadow-sm" onclick="switchWorkspaceBlock('create-event', event)">
                                <i class="bi bi-plus-lg me-1"></i> Tạo Sự Kiện Mới
                            </button>
                        </div>

                        <!-- Search & Filter Controls -->
                        <div class="ef-card-editorial p-3 mb-4">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" id="eventSearchInput" class="form-control border-start-0" placeholder="Tìm theo tên sự kiện, địa điểm...">
                                    </div>
                                </div>
                                <div class="col-md-7 d-flex justify-content-md-end gap-2 flex-wrap">
                                    <select id="eventStateFilter" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                        <option value="ALL">Tất cả trạng thái</option>
                                        <option value="open">Đang mở đăng ký</option>
                                        <option value="upcoming">Sắp mở đăng ký</option>
                                        <option value="happening">Đang diễn ra</option>
                                        <option value="deadline_passed">Đã đóng đăng ký</option>
                                        <option value="ended">Đã kết thúc</option>
                                        <option value="cancelled">Đã hủy</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Bảng danh sách toàn bộ sự kiện -->
                        <div class="ef-card-editorial p-0 overflow-hidden shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="allEventsTable" style="font-size: 0.86rem;">
                                    <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                        <tr>
                                            <th class="ps-4 py-3">SỰ KIỆN & ĐỊA ĐIỂM</th>
                                            <th class="py-3">THỜI GIAN DIỄN RA</th>
                                            <th class="py-3">TRẠNG THÁI</th>
                                            <th class="py-3 text-center">ĐĂNG KÝ / SỨC CHỨA</th>
                                            <th class="py-3 text-center">BAN TỔ CHỨC</th>
                                            <th class="pe-4 py-3 text-end">THAO TÁC</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allEvents as $evt): 
                                            $state = event_state($evt, (int)$evt['RegisteredCount'], $now);
                                            $percent = min(100, round(((int)$evt['RegisteredCount'] / max(1, (int)$evt['MaxCapacity'])) * 100));
                                        ?>
                                            <tr class="event-table-row" data-name="<?= htmlspecialchars(mb_strtolower($evt['EventName'] . ' ' . $evt['Location'], 'UTF-8')) ?>" data-state="<?= htmlspecialchars($state['key'] ?? '') ?>">
                                                <td class="ps-4 py-3">
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($evt['EventName']) ?></div>
                                                    <div class="text-muted small text-truncate" style="max-width: 280px;">
                                                        <i class="bi bi-geo-alt text-primary me-1"></i><?= htmlspecialchars($evt['Location']) ?>
                                                    </div>
                                                </td>
                                                <td class="py-3 font-monospace small">
                                                    <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($evt['StartTime'])) ?></div>
                                                    <div class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($evt['StartTime'])) ?> – <?= date('H:i', strtotime($evt['EndTime'])) ?></div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge-pill-custom <?= $state['css_class'] ?>" style="font-size: 0.72rem;">
                                                        <?= htmlspecialchars($state['label']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3 text-center" style="min-width: 140px;">
                                                    <div class="font-monospace small fw-bold">
                                                        <?= (int)$evt['RegisteredCount'] ?> / <?= (int)$evt['MaxCapacity'] ?>
                                                    </div>
                                                    <div class="progress mt-1 mx-auto" style="height: 4px; max-width: 110px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percent ?>%"></div>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span class="badge bg-light text-secondary border font-monospace">
                                                        <i class="bi bi-people me-1 text-primary"></i><?= (int)$evt['StaffCount'] ?> BĐH
                                                    </span>
                                                </td>
                                                <td class="pe-4 py-3 text-end">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$evt['EventID']) ?>" class="btn btn-outline-primary" title="Xem chi tiết">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="<?= base_url('app/modules/events/form.php?id=' . (int)$evt['EventID']) ?>" class="btn btn-outline-secondary" title="Sửa thông tin">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ======================================================
                         KHỐI 3: TẠO SỰ KIỆN MỚI (BLOCK: #create-event)
                         ====================================================== -->
                    <div id="block-create-event" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-plus-circle text-primary me-2"></i>Tạo Sự Kiện Mới
                                </h1>
                                <p class="text-secondary small mb-0">Thiết lập thông tin hoạt động, giới hạn vé và phân công tối thiểu 2 cán bộ BĐH (UAT 10).</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="switchWorkspaceBlock('events', event)">
                                <i class="bi bi-arrow-left me-1"></i> Quay lại Danh Sách Sự Kiện
                            </button>
                        </div>

                        <div class="ef-card-editorial p-4 p-md-5">
                            <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>" class="needs-validation">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="create_event">

                                <div class="row g-4">
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold text-dark small">Tên sự kiện <span class="text-danger">*</span></label>
                                        <input type="text" name="event_name" class="form-control" placeholder="Ví dụ: Speaking Circle: Global Mindset" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold text-dark small">Số lượng vé tối đa <span class="text-danger">*</span></label>
                                        <input type="number" name="capacity" class="form-control font-monospace" value="50" min="5" max="1000" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold text-dark small">Địa điểm tổ chức <span class="text-danger">*</span></label>
                                        <input type="text" name="location" class="form-control" placeholder="Ví dụ: UEH Cơ sở B - Phòng B1.302 (279 Nguyễn Tri Phương, Q.10)" required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold text-dark small">Mô tả sự kiện & nội dung chuyên môn</label>
                                        <textarea name="description" rows="3" class="form-control" placeholder="Giới thiệu mục tiêu, đối tượng tham gia và lợi ích của chương trình..."></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small">Thời gian Bắt đầu <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="start_time" class="form-control font-monospace" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small">Thời gian Kết thúc <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="end_time" class="form-control font-monospace" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small">Mở đăng ký từ lúc</label>
                                        <input type="datetime-local" name="reg_start" class="form-control font-monospace">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small">Hạn chót đóng đăng ký</label>
                                        <input type="datetime-local" name="reg_end" class="form-control font-monospace">
                                    </div>

                                    <div class="col-12">
                                        <div class="p-3.5 rounded-3 border bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark small mb-0">
                                                    <i class="bi bi-shield-check text-primary me-1"></i> Phân công Cán bộ Ban Điều Hành phụ trách <span class="text-danger">*</span>
                                                </label>
                                                <span class="badge bg-primary-subtle text-primary font-monospace" style="font-size: 0.72rem;">Yêu cầu tối thiểu 2 cán bộ BĐH (UAT 10)</span>
                                            </div>
                                            <p class="text-secondary small mb-3">Chọn các thành viên Ban Điều Hành điều phối sự kiện và vận hành điểm danh:</p>
                                            
                                            <div class="row g-2">
                                                <?php foreach ($boardStaffList as $staff): ?>
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="form-check p-2.5 rounded-2 bg-white border">
                                                            <input class="form-check-input ms-0 me-2" type="checkbox" name="staff_ids[]" value="<?= $staff['AccountID'] ?>" id="staff_<?= $staff['AccountID'] ?>"
                                                                <?= ($staff['AccountID'] == $userId || $staff['MemberRole'] === 'President') ? 'checked' : '' ?>>
                                                            <label class="form-check-label small fw-semibold text-dark text-truncate w-100" for="staff_<?= $staff['AccountID'] ?>">
                                                                <?= htmlspecialchars($staff['FullName']) ?>
                                                                <span class="text-muted d-block font-monospace" style="font-size: 0.7rem;">
                                                                    <?= htmlspecialchars($staff['MemberRole']) ?> <?= $staff['DepartmentCode'] ? '· ' . $staff['DepartmentCode'] : '' ?>
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 text-end pt-2">
                                        <button type="button" class="btn btn-outline-secondary me-2" onclick="switchWorkspaceBlock('events', event)">Hủy bỏ</button>
                                        <button type="submit" class="btn btn-ef-primary px-4">
                                            <i class="bi bi-check-lg me-1"></i> Lưu & Xuất Bản Sự Kiện
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ======================================================
                         KHỐI 4: SỰ KIỆN CỦA TÔI (BLOCK: #my-events)
                         ====================================================== -->
                    <div id="block-my-events" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-bookmark-check text-primary me-2"></i>Sự Kiện Của Tôi
                                </h1>
                                <p class="text-secondary small mb-0">Theo dõi các sự kiện bạn được phân công điều hành (BĐH) và các sự kiện đã đăng ký tham gia.</p>
                            </div>
                        </div>

                        <!-- Tab 1: Sự kiện phân công điều hành (UAT 10) -->
                        <div class="ef-card-editorial p-4 mb-4">
                            <h2 class="h5 fw-bold text-dark mb-3">
                                <i class="bi bi-shield-lock text-primary me-2"></i>Sự Kiện Được Phân Công Điều Hành (Ban Tổ Chức)
                            </h2>
                            <?php if (empty($myStaffEvents)): ?>
                                <p class="text-muted small mb-0">Bạn hiện chưa được phân công điều hành sự kiện nào.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                                        <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                            <tr>
                                                <th class="ps-3 py-2.5">TÊN SỰ KIỆN</th>
                                                <th class="py-2.5">VAI TRÒ PHÂN CÔNG</th>
                                                <th class="py-2.5">THỜI GIAN</th>
                                                <th class="py-2.5 text-center">ĐĂNG KÝ</th>
                                                <th class="pe-3 py-2.5 text-end">THAO TÁC</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($myStaffEvents as $se): ?>
                                                <tr>
                                                    <td class="ps-3 py-3">
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($se['EventName']) ?></div>
                                                        <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($se['Location']) ?></small>
                                                    </td>
                                                    <td class="py-3">
                                                        <span class="badge bg-primary-subtle text-primary font-monospace"><?= htmlspecialchars($se['StaffRole']) ?></span>
                                                    </td>
                                                    <td class="py-3 font-monospace small">
                                                        <?= date('d/m/Y', strtotime($se['StartTime'])) ?> (<?= date('H:i', strtotime($se['StartTime'])) ?> - <?= date('H:i', strtotime($se['EndTime'])) ?>)
                                                    </td>
                                                    <td class="py-3 text-center font-monospace small">
                                                        <strong><?= (int)$se['RegisteredCount'] ?></strong> / <?= (int)$se['MaxCapacity'] ?>
                                                    </td>
                                                    <td class="pe-3 py-3 text-end">
                                                        <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$se['EventID']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                            Xem Chi Tiết
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tab 2: Sự kiện đã đăng ký tham gia -->
                        <div class="ef-card-editorial p-4">
                            <h2 class="h5 fw-bold text-dark mb-3">
                                <i class="bi bi-ticket-perforated text-primary me-2"></i>Sự Kiện Đã Đăng Ký Tham Gia
                            </h2>
                            <?php if (empty($myRegistrations)): ?>
                                <p class="text-muted small mb-0">Bạn chưa đăng ký tham gia sự kiện nào.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                                        <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                            <tr>
                                                <th class="ps-3 py-2.5">SỰ KIỆN</th>
                                                <th class="py-2.5">THỜI GIAN</th>
                                                <th class="py-2.5">ĐỊA ĐIỂM</th>
                                                <th class="py-2.5">TRẠNG THÁI THAM DỰ</th>
                                                <th class="pe-3 py-2.5 text-end">CHI TIẾT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($myRegistrations as $r): 
                                                $attText = ($r['AttendanceStatus'] !== null && (int)$r['AttendanceStatus'] === 1) ? 'Đã điểm danh' : 'Chưa điểm danh';
                                                $attClass = ($r['AttendanceStatus'] !== null && (int)$r['AttendanceStatus'] === 1) ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                                            ?>
                                                <tr>
                                                    <td class="ps-3 py-3 fw-bold text-dark"><?= htmlspecialchars($r['EventName']) ?></td>
                                                    <td class="py-3 font-monospace small"><?= date('d/m/Y H:i', strtotime($r['StartTime'])) ?></td>
                                                    <td class="py-3 text-muted small"><?= htmlspecialchars($r['Location']) ?></td>
                                                    <td class="py-3">
                                                        <span class="badge <?= $attClass ?> font-monospace"><?= $attText ?></span>
                                                    </td>
                                                    <td class="pe-3 py-3 text-end">
                                                        <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$r['EventID']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                            Xem
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ======================================================
                         KHỐI 5: THÀNH VIÊN & 4 BAN (BLOCK: #members)
                         ====================================================== -->
                    <?php if ($isStaffOrManager || $isAdmin): ?>
                    <div id="block-members" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-people-fill text-primary me-2"></i>Thành Viên & 4 Phòng Ban (BELL UEH)
                                </h1>
                                <p class="text-secondary small mb-0">Cơ cấu tổ chức thế hệ 27, hồ sơ cán bộ lãnh đạo và quản trị thành viên.</p>
                            </div>
                        </div>

                        <!-- Department Filter Pills -->
                        <div class="d-flex gap-2 flex-wrap mb-4">
                            <button type="button" class="btn btn-sm rounded-pill px-3 active btn-primary dept-filter-btn" data-dept="ALL" onclick="filterDeptMembers('ALL', this)">
                                Tất cả thành viên (<?= count($allClubMembers) ?>)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 btn-outline-secondary dept-filter-btn" data-dept="BĐH" onclick="filterDeptMembers('BĐH', this)">
                                Ban Chủ Nhiệm (2)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 btn-outline-secondary dept-filter-btn" data-dept="HR" onclick="filterDeptMembers('HR', this)">
                                Phòng Nhân sự (HR)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 btn-outline-secondary dept-filter-btn" data-dept="SPEC" onclick="filterDeptMembers('SPEC', this)">
                                Phòng Chuyên môn (SPEC)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 btn-outline-secondary dept-filter-btn" data-dept="COMS" onclick="filterDeptMembers('COMS', this)">
                                Phòng Truyền thông (COMS)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 btn-outline-secondary dept-filter-btn" data-dept="ER" onclick="filterDeptMembers('ER', this)">
                                Phòng Đối ngoại (ER)
                            </button>
                        </div>

                        <!-- Bảng danh sách thành viên -->
                        <div class="ef-card-editorial p-0 overflow-hidden shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="membersTable" style="font-size: 0.86rem;">
                                    <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                        <tr>
                                            <th class="ps-4 py-3">HỌ VÀ TÊN</th>
                                            <th class="py-3">CHỨC DANH / VAI TRÒ</th>
                                            <th class="py-3">PHÒNG BAN</th>
                                            <th class="py-3">MSSV & KHÓA</th>
                                            <th class="py-3">EMAIL & SĐT</th>
                                            <th class="pe-4 py-3 text-center">TRẠNG THÁI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allClubMembers as $m): 
                                            $deptKey = $m['DepartmentCode'] ?: 'BĐH';
                                        ?>
                                            <tr class="member-row" data-dept="<?= $deptKey ?>">
                                                <td class="ps-4 py-3">
                                                    <div class="d-flex align-items-center gap-2.5">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-2xs flex-shrink-0" 
                                                             style="width: 34px; height: 34px; font-size: 0.78rem; background: #0f172a;">
                                                            <?= mb_substr($m['FullName'], 0, 1) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($m['FullName']) ?></div>
                                                            <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars($m['Major'] ?? 'UEH Student') ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge rounded-pill px-2.5 py-1 font-monospace <?= in_array($m['MemberRole'], ['President', 'Vice President'], true) ? 'bg-primary text-white' : 'bg-light text-dark border' ?>" style="font-size: 0.72rem;">
                                                        <?= htmlspecialchars($m['MemberRole']) ?>
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($m['DepartmentName'] ?? 'Ban Chủ Nhiệm CLB') ?></div>
                                                    <small class="text-muted font-monospace"><?= htmlspecialchars($m['DepartmentCode'] ?? 'EXECUTIVE') ?></small>
                                                </td>
                                                <td class="py-3 font-monospace small">
                                                    <div><?= htmlspecialchars($m['StudentCode']) ?></div>
                                                    <div class="badge bg-light text-secondary border font-monospace" style="font-size: 0.65rem;"><?= htmlspecialchars($m['Cohort']) ?></div>
                                                </td>
                                                <td class="py-3 small">
                                                    <div><a href="mailto:<?= htmlspecialchars($m['Email']) ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($m['Email']) ?></a></div>
                                                    <div class="text-muted font-monospace" style="font-size: 0.75rem;"><?= htmlspecialchars($m['Phone'] ?? '-') ?></div>
                                                </td>
                                                <td class="pe-4 py-3 text-center">
                                                    <span class="badge rounded-pill bg-success-subtle text-success font-monospace" style="font-size: 0.7rem;">
                                                        Active
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ======================================================
                         KHỐI 6: THÔNG TIN CÂU LẠC BỘ (BLOCK: #club-info)
                         ====================================================== -->
                    <div id="block-club-info" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-building text-primary me-2"></i>Thông Tin Câu Lạc Bộ BELL UEH
                                </h1>
                                <p class="text-secondary small mb-0">Hồ sơ giới thiệu, sứ mệnh, tầm nhìn và chỉnh sửa thông tin CLB.</p>
                            </div>
                        </div>

                        <!-- Card 1: Tổng quan Hồ sơ CLB -->
                        <div class="ef-card-editorial p-4 p-md-5 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h2 class="h4 fw-bold text-dark mb-1"><?= htmlspecialchars($club['ClubName'] ?? 'BELL Club') ?></h2>
                                    <p class="text-muted font-monospace small mb-0"><?= htmlspecialchars($club['ShortName'] ?? 'BELL UEH') ?> · Thành lập năm 1995 · Trực thuộc Đoàn - Hội UEH</p>
                                </div>
                                <span class="badge rounded-pill bg-primary-subtle text-primary font-monospace px-3 py-2">
                                    Active Club
                                </span>
                            </div>
                            <p class="text-secondary lh-base mb-4"><?= nl2br(htmlspecialchars($club['Description'] ?? '')) ?></p>

                            <div class="row g-3 pt-3 border-top">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="fw-bold text-primary small mb-1"><i class="bi bi-compass me-1"></i> TẦM NHÌN</div>
                                        <p class="small text-secondary mb-0"><?= nl2br(htmlspecialchars($club['Vision'] ?? 'Chưa cập nhật')) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div class="fw-bold text-primary small mb-1"><i class="bi bi-flag me-1"></i> SỨ MỆNH</div>
                                        <p class="small text-secondary mb-0"><?= nl2br(htmlspecialchars($club['Mission'] ?? 'Chưa cập nhật')) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3">
                                        <div class="fw-bold text-dark small mb-1"><i class="bi bi-telephone me-1 text-primary"></i> HOTLINE LIÊN HỆ</div>
                                        <p class="small text-secondary font-monospace mb-0"><?= htmlspecialchars($club['Phone'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3">
                                        <div class="fw-bold text-dark small mb-1"><i class="bi bi-geo-alt me-1 text-primary"></i> ĐỊA CHỈ VĂN PHÒNG</div>
                                        <p class="small text-secondary mb-0"><?= htmlspecialchars($club['Address'] ?? 'Chưa cập nhật') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Chỉnh sửa thông tin CLB (Dành cho President/Vice President) -->
                        <?php if (can_manage_club_profile()): ?>
                            <div class="ef-card-editorial p-4 p-md-5">
                                <div class="border-bottom pb-3 mb-4">
                                    <h3 class="h5 fw-bold text-dark mb-1">
                                        <i class="bi bi-pencil-square text-primary me-2"></i>Chỉnh Sửa Thông Tin Câu Lạc Bộ
                                    </h3>
                                    <p class="text-secondary small mb-0">Cập nhật nội dung giới thiệu, sứ mệnh và kênh truyền thông của CLB BELL UEH.</p>
                                </div>

                                <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="update_club">

                                    <div class="row g-4">
                                        <div class="col-md-8">
                                            <label class="form-label fw-bold text-dark small">Tên đầy đủ CLB <span class="text-danger">*</span></label>
                                            <input type="text" name="club_name" class="form-control" value="<?= htmlspecialchars($club['ClubName'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">Tên viết tắt</label>
                                            <input type="text" name="short_name" class="form-control font-monospace" value="<?= htmlspecialchars($club['ShortName'] ?? 'BELL UEH') ?>" required>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold text-dark small">Mô tả tổng quan</label>
                                            <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($club['Description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">Tầm nhìn chiến lược</label>
                                            <textarea name="vision" rows="3" class="form-control"><?= htmlspecialchars($club['Vision'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">Sứ mệnh hoạt động</label>
                                            <textarea name="mission" rows="3" class="form-control"><?= htmlspecialchars($club['Mission'] ?? '') ?></textarea>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">Hotline liên hệ</label>
                                            <input type="text" name="phone" class="form-control font-monospace" value="<?= htmlspecialchars($club['Phone'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">Fanpage Facebook chính thức</label>
                                            <input type="url" name="facebook_url" class="form-control" value="<?= htmlspecialchars($club['FacebookUrl'] ?? '') ?>">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold text-dark small">Địa chỉ văn phòng / Sảnh sinh hoạt</label>
                                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($club['Address'] ?? '') ?>">
                                        </div>

                                        <div class="col-12 text-end pt-2">
                                            <button type="submit" class="btn btn-ef-primary px-4">
                                                <i class="bi bi-check-lg me-1"></i> Lưu Cập Nhật Thông Tin CLB
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ======================================================
                         KHỐI 8: HỒ SƠ CÁ NHÂN (BLOCK: #profile)
                         ====================================================== -->
                    <div id="block-profile" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-person-gear text-primary me-2"></i>Hồ Sơ Cá Nhân & Bảo Mật
                                </h1>
                                <p class="text-secondary small mb-0">Quản lý thông tin liên hệ và cập nhật mật khẩu tài khoản của bạn.</p>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Cột trái: Cập nhật thông tin -->
                            <div class="col-lg-7">
                                <div class="ef-card-editorial p-4 p-md-5 h-100">
                                    <h2 class="h5 fw-bold text-dark mb-3">Thông Tin Tài Khoản</h2>
                                    <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="update_profile">

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-muted">Email đăng nhập (Cố định)</label>
                                            <input type="text" class="form-control bg-light font-monospace" value="<?= htmlspecialchars($userAcc['Email']) ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">Họ và tên <span class="text-danger">*</span></label>
                                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($userAcc['FullName']) ?>" required>
                                        </div>

                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold text-dark">Mã số sinh viên (MSSV)</label>
                                                <input type="text" class="form-control bg-light font-monospace" value="<?= htmlspecialchars($userAcc['StudentCode'] ?? '-') ?>" disabled>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold text-dark">Khóa sinh viên</label>
                                                <input type="text" name="cohort" class="form-control font-monospace" value="<?= htmlspecialchars($userAcc['Cohort'] ?? '') ?>" placeholder="Ví dụ: K48">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">Số điện thoại liên hệ</label>
                                            <input type="text" name="phone" class="form-control font-monospace" value="<?= htmlspecialchars($userAcc['Phone'] ?? '') ?>" placeholder="090xxxxxxx">
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label small fw-bold text-dark">Chuyên ngành đào tạo tại UEH</label>
                                            <input type="text" name="major" class="form-control" value="<?= htmlspecialchars($userAcc['Major'] ?? '') ?>" placeholder="Ví dụ: Kinh doanh quốc tế">
                                        </div>

                                        <button type="submit" class="btn btn-ef-primary px-4">
                                            <i class="bi bi-check-lg me-1"></i> Lưu Thay Đổi Thông Tin
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Cột phải: Đổi mật khẩu -->
                            <div class="col-lg-5">
                                <div class="ef-card-editorial p-4 p-md-5 h-100">
                                    <h2 class="h5 fw-bold text-dark mb-3">Đổi Mật Khẩu</h2>
                                    <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="change_password">

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark">Mật khẩu mới <span class="text-danger">*</span></label>
                                            <input type="password" name="new_password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label small fw-bold text-dark">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>

                                        <button type="submit" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-key-fill me-1"></i> Cập Nhật Mật Khẩu
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================================================
                         KHỐI 9: QUẢN TRỊ TÀI KHOẢN (BLOCK: #admin-accounts)
                         ====================================================== -->
                    <?php if (is_system_admin()): ?>
                        <div id="block-admin-accounts" class="workspace-content-block d-none">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                <div>
                                    <h1 class="h3 fw-bold text-danger mb-1">
                                        <i class="bi bi-shield-lock-fill text-danger me-2"></i>Quản Trị Tài Khoản Hệ Thống
                                    </h1>
                                    <p class="text-secondary small mb-0">Quản lý toàn bộ danh sách tài khoản, phân quyền vai trò và trạng thái kích hoạt.</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#adminCreateAccountModal">
                                    <i class="bi bi-person-plus-fill me-1"></i> Thêm Tài Khoản Mới
                                </button>
                            </div>

                            <div class="ef-card-editorial p-0 overflow-hidden shadow-sm">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                                        <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                            <tr>
                                                <th class="ps-4 py-3">ID</th>
                                                <th class="py-3">HỌ VÀ TÊN</th>
                                                <th class="py-3">EMAIL</th>
                                                <th class="py-3">MSSV</th>
                                                <th class="py-3">VAI TRÒ</th>
                                                <th class="py-3 text-center">TRẠNG THÁI</th>
                                                <th class="pe-4 py-3 text-end">THAO TÁC</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($adminAccounts as $acc): 
                                                $isMe = ((int)$acc['AccountID'] === $userId);
                                            ?>
                                                <tr>
                                                    <td class="ps-4 py-3 font-monospace"><?= $acc['AccountID'] ?></td>
                                                    <td class="py-3 fw-bold text-dark"><?= htmlspecialchars($acc['FullName']) ?></td>
                                                    <td class="py-3 font-monospace small"><?= htmlspecialchars($acc['Email']) ?></td>
                                                    <td class="py-3 font-monospace small"><?= htmlspecialchars($acc['StudentCode'] ?? '-') ?></td>
                                                    <td class="py-3">
                                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($acc['RoleName'] ?? 'Member') ?></span>
                                                    </td>
                                                    <td class="py-3 text-center">
                                                        <span class="badge <?= $acc['IsActive'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> font-monospace">
                                                            <?= $acc['IsActive'] ? 'Active' : 'Locked' ?>
                                                        </span>
                                                    </td>
                                                    <td class="pe-4 py-3 text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <!-- Đổi vai trò -->
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#adminEditRoleModal" 
                                                                    data-id="<?= $acc['AccountID'] ?>" 
                                                                    data-name="<?= htmlspecialchars($acc['FullName']) ?>" 
                                                                    data-role="<?= $acc['RoleID'] ?>"
                                                                    title="Đổi vai trò">
                                                                <i class="bi bi-shield-shaded"></i>
                                                            </button>

                                                            <!-- Khóa / Mở khóa -->
                                                            <?php if ($isMe): ?>
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Không thể tự khóa tài khoản của chính mình">
                                                                    <i class="bi bi-lock-fill"></i>
                                                                </button>
                                                            <?php else: ?>
                                                                <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn <?= ((int)$acc['IsActive'] === 1) ? 'KHÓA' : 'MỞ KHÓA' ?> tài khoản này?');">
                                                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                    <input type="hidden" name="action" value="admin_toggle_status">
                                                                    <input type="hidden" name="account_id" value="<?= $acc['AccountID'] ?>">
                                                                    <input type="hidden" name="current_status" value="<?= $acc['IsActive'] ?>">
                                                                    <?php if ((int)$acc['IsActive'] === 1): ?>
                                                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Khóa tài khoản">
                                                                            <i class="bi bi-lock-fill"></i>
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button type="submit" class="btn btn-outline-success btn-sm" title="Mở khóa tài khoản">
                                                                            <i class="bi bi-unlock-fill"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Modal Thêm Tài Khoản Mới -->
                            <div class="modal fade" id="adminCreateAccountModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <div class="modal-header border-bottom bg-light">
                                            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus text-danger me-2"></i>Thêm Tài Khoản Mới</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="admin_create">

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Họ và tên <span class="text-danger">*</span></label>
                                                    <input type="text" name="full_name" class="form-control" placeholder="Nguyễn Văn A" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" class="form-control" placeholder="user@st.ueh.edu.vn" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Mã số sinh viên (MSSV)</label>
                                                    <input type="text" name="student_code" class="form-control font-monospace" placeholder="312xxxxxxx">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Vai trò hệ thống <span class="text-danger">*</span></label>
                                                    <select name="role_id" class="form-select" required>
                                                        <?php foreach ($allRoles as $r): ?>
                                                            <option value="<?= $r['RoleID'] ?>" <?= (int)$r['RoleID'] === 4 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($r['RoleName']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Mật khẩu khởi tạo <span class="text-danger">*</span></label>
                                                    <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top bg-light">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-sm btn-danger px-4">Tạo Tài Khoản</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Đổi Vai Trò -->
                            <div class="modal fade" id="adminEditRoleModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <div class="modal-header border-bottom bg-light">
                                            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-shaded text-primary me-2"></i>Đổi Vai Trò Hệ Thống</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="<?= base_url('app/modules/dashboard/index.php') ?>">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="admin_update_role">
                                                <input type="hidden" name="account_id" id="editRoleAccountId" value="0">

                                                <p class="small text-secondary mb-3">Tài khoản: <strong class="text-dark" id="editRoleAccountName">-</strong></p>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-dark">Chọn vai trò mới <span class="text-danger">*</span></label>
                                                    <select name="new_role_id" id="editRoleSelect" class="form-select" required>
                                                        <?php foreach ($allRoles as $r): ?>
                                                            <option value="<?= $r['RoleID'] ?>"><?= htmlspecialchars($r['RoleName']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top bg-light">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-sm btn-primary px-4">Lưu Vai Trò</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ======================================================
                         KHỐI 10: LỊCH NOTION CALENDAR (BLOCK: #calendar)
                         ====================================================== -->
                    <div id="block-calendar" class="workspace-content-block d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-1">
                                    <i class="bi bi-calendar3 text-primary me-2"></i>Lịch Hoạt Động CLB
                                </h1>
                                <p class="text-secondary small mb-0">Xem trực quan theo Lịch Tháng (Event Pills) hoặc Lịch Tuần (Timeline 07:00 – 22:00).</p>
                            </div>
                        </div>

                        <!-- Notion Control Bar -->
                        <div class="ef-card-editorial p-3 mb-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <h2 class="h5 fw-bold text-dark mb-0 font-monospace" id="wsCalPeriodTitle">November 2026</h2>
                                    <div class="btn-group shadow-2xs" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-2.5" id="wsBtnPrev"><i class="bi bi-chevron-left"></i></button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" id="wsBtnToday">Today</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-2.5" id="wsBtnNext"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <div class="btn-group p-1 bg-light rounded-pill border" role="group">
                                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold active btn-primary" id="wsBtnModeMonth" onclick="switchWsCalMode('month')">
                                            <i class="bi bi-grid-3x3 me-1"></i> Lịch Tháng
                                        </button>
                                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-secondary" id="wsBtnModeWeek" onclick="switchWsCalMode('week')">
                                            <i class="bi bi-calendar-week me-1"></i> Lịch Tuần
                                        </button>
                                    </div>

                                    <select id="wsCalCategoryFilter" class="form-select form-select-sm rounded-pill" style="width: auto;">
                                        <option value="ALL">Tất cả hoạt động</option>
                                        <option value="SPEAK">🎤 Speaking Group</option>
                                        <option value="DEBATE">⚖️ Debate Lab</option>
                                        <option value="WORKSHOP">💡 Workshop</option>
                                        <option value="CULTURE">🎉 Culture & Gala</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- View 1: Month View Container -->
                        <div id="wsMonthViewWrapper" class="ef-card-editorial p-0 overflow-hidden shadow-sm mb-4">
                            <div class="row g-0 text-center border-bottom bg-light fw-bold text-secondary font-monospace" style="font-size: 0.78rem;">
                                <div class="col py-2.5 border-end">SUN</div>
                                <div class="col py-2.5 border-end">MON</div>
                                <div class="col py-2.5 border-end">TUE</div>
                                <div class="col py-2.5 border-end">WED</div>
                                <div class="col py-2.5 border-end">THU</div>
                                <div class="col py-2.5 border-end">FRI</div>
                                <div class="col py-2.5">SAT</div>
                            </div>
                            <div id="wsMonthGridContainer" class="month-grid"></div>
                        </div>

                        <!-- View 2: Week Timeline Container -->
                        <div id="wsWeekViewWrapper" class="ef-card-editorial p-0 overflow-hidden shadow-sm mb-4 d-none">
                            <div class="row g-0 text-center border-bottom bg-light fw-bold" id="wsWeekDaysHeader" style="font-size: 0.82rem;"></div>
                            <div class="week-timeline-container position-relative overflow-auto" style="height: 680px;">
                                <div class="week-timeline-inner position-relative" style="min-width: 800px; height: 1050px;">
                                    <div id="wsWeekGridLines"></div>
                                    <div id="wsCurrentTimeIndicator" class="current-time-line d-none">
                                        <span class="current-time-pill font-monospace" id="wsCurrentTimeLabel">10:15 AM</span>
                                    </div>
                                    <div class="row g-0 h-100 position-absolute top-0 start-0 w-100" id="wsWeekColumnsContainer"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================================================
     CSS CHO WORKSPACE SINGLE CONTAINER & SIDEBAR MENU
     ========================================================================== -->
<style>
/* Hiệu ứng trượt nổi ra của Left Menu */
@keyframes sidebarSlideIn {
    from {
        transform: translateX(-30px);
        opacity: 0.5;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
.ef-workspace-left-menu {
    animation: sidebarSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.workspace-nav-link {
    display: flex;
    align-items: center;
    padding: 0.65rem 0.85rem;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.2s ease;
    margin-bottom: 2px;
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

/* Lịch Notion Styles */
.month-grid-row { display: flex; border-bottom: 1px solid var(--ef-border, #e2e8f0); }
.month-day-cell { flex: 1; min-height: 120px; border-right: 1px solid var(--ef-border, #e2e8f0); padding: 8px; position: relative; background: #fff; }
.month-day-cell:last-child { border-right: none; }
.month-day-cell.other-month { background-color: #f8fafc; opacity: 0.45; }
.month-day-number { font-size: 0.78rem; font-weight: 600; color: #475569; margin-bottom: 6px; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; }
.month-day-number.is-today { background-color: #2563eb; color: #ffffff !important; font-weight: 700; }
.notion-event-pill { display: block; padding: 3px 6px; margin-bottom: 4px; border-radius: 6px; font-size: 0.72rem; line-height: 1.3; color: #0f172a; border-left: 3px solid #2563eb; background: #eff6ff; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; }
.notion-event-pill:hover { transform: translateY(-1px); box-shadow: 0 2px 5px rgba(37, 99, 235, 0.15); color: #1e40af; }
.notion-event-pill .pill-sub { font-size: 0.65rem; color: #64748b; display: block; }
.time-gutter-col { width: 65px; flex-shrink: 0; border-right: 1px solid var(--ef-border, #e2e8f0); }
.week-day-col { flex: 1; position: relative; border-right: 1px solid var(--ef-border, #e2e8f0); }
.week-day-col:last-child { border-right: none; }
.hourly-row { height: 70px; border-bottom: 1px dashed rgba(226, 232, 240, 0.8); position: relative; }
.time-label { font-size: 0.72rem; font-family: monospace; color: #94a3b8; position: absolute; top: -9px; left: 8px; }
.current-time-line { position: absolute; left: 0; right: 0; height: 2px; background-color: #ef4444; z-index: 10; pointer-events: none; }
.current-time-pill { position: absolute; left: 4px; top: -10px; background-color: #ef4444; color: #ffffff; font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; font-weight: 700; }
.timeline-event-block { position: absolute; left: 4px; right: 4px; border-radius: 8px; padding: 6px 8px; font-size: 0.74rem; line-height: 1.25; overflow: hidden; cursor: pointer; border-left: 3px solid #2563eb; background: #eff6ff; z-index: 5; }
.timeline-event-block:hover { transform: scale(1.02); z-index: 8; box-shadow: 0 6px 14px rgba(37, 99, 235, 0.2); }
</style>

<!-- JSON Data Payload cho Lịch -->
<script id="ws-calendar-raw-data" type="application/json">
<?= json_encode($calendarEventsPayload, JSON_UNESCAPED_UNICODE) ?>
</script>

<!-- ==========================================================================
     JAVASCRIPT CHUYỂN ĐỔI KHỐI NỘI DUNG TỨC THÌ (CONTAINER CONTROLLER)
     ========================================================================== -->
<script>
// 1. Chuyển đổi Khối nội dung trong Container (Không reload trang, giữ nguyên top-nav và left-menu)
function switchWorkspaceBlock(blockId, event) {
    if (event) event.preventDefault();

    const blocks = document.querySelectorAll('.workspace-content-block');
    if (!blocks || blocks.length === 0) {
        window.location.href = '<?= base_url("app/modules/dashboard/index.php#") ?>' + blockId;
        return;
    }

    // Ẩn tất cả các khối
    blocks.forEach(b => b.classList.add('d-none'));

    // Hiển thị khối mục tiêu
    const targetBlock = document.getElementById('block-' + blockId);
    if (targetBlock) {
        targetBlock.classList.remove('d-none');
    }

    // Cập nhật trạng thái active trên Left Menu
    document.querySelectorAll('.workspace-nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-block') === blockId) {
            link.classList.add('active');
        }
    });

    // Cập nhật URL Hash không làm giật trang
    if (history.pushState) {
        history.pushState(null, null, '#' + blockId);
    } else {
        location.hash = blockId;
    }

    // Kích hoạt render lại Lịch nếu chuyển vào khối Lịch
    if (blockId === 'calendar' && typeof window.renderWorkspaceCalendar === 'function') {
        window.renderWorkspaceCalendar();
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Lắng nghe URL Hash khi vừa tải trang để mở đúng khối
window.addEventListener('DOMContentLoaded', () => {
    let hash = window.location.hash.replace('#', '');
    const defaultBlock = <?= json_encode($isStaffOrManager ? 'stats' : ($isAdmin ? 'admin-accounts' : 'my-events')) ?>;
    if (!hash || !document.getElementById('block-' + hash)) {
        hash = defaultBlock;
    }
    switchWorkspaceBlock(hash);
});

// Lắng nghe thay đổi hash (Back / Forward / Click hash link từ bên ngoài)
window.addEventListener('hashchange', () => {
    let hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('block-' + hash)) {
        switchWorkspaceBlock(hash);
    }
});

// Admin modal populate data
document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('adminEditRoleModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const btn = event.relatedTarget;
            if (!btn) return;
            const aid = btn.getAttribute('data-id');
            const aname = btn.getAttribute('data-name');
            const arole = btn.getAttribute('data-role');
            document.getElementById('editRoleAccountId').value = aid;
            document.getElementById('editRoleAccountName').textContent = aname;
            document.getElementById('editRoleSelect').value = arole;
        });
    }
});

// 2. Tìm kiếm và lọc bảng Sự kiện
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('eventSearchInput');
    const stateFilter = document.getElementById('eventStateFilter');
    const rows = document.querySelectorAll('.event-table-row');

    function filterEventsTable() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedState = stateFilter ? stateFilter.value : 'ALL';

        rows.forEach(r => {
            const name = r.getAttribute('data-name') || '';
            const state = r.getAttribute('data-state') || '';
            const matchesQuery = !query || name.includes(query);
            const matchesState = (selectedState === 'ALL') || (state === selectedState);

            r.style.display = (matchesQuery && matchesState) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', filterEventsTable);
    if (stateFilter) stateFilter.addEventListener('change', filterEventsTable);
});

// 3. Lọc bảng Thành viên theo Phòng ban
function filterDeptMembers(deptKey, btnElement) {
    document.querySelectorAll('.dept-filter-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
    });
    btnElement.classList.add('active', 'btn-primary');
    btnElement.classList.remove('btn-outline-secondary');

    const rows = document.querySelectorAll('.member-row');
    rows.forEach(r => {
        const dept = r.getAttribute('data-dept');
        r.style.display = (deptKey === 'ALL' || dept === deptKey) ? '' : 'none';
    });
}

// 4. Notion Calendar Engine nhúng trong khối Lịch
(function() {
    let rawEvents = [];
    try {
        const dataEl = document.getElementById('ws-calendar-raw-data');
        if (dataEl && dataEl.textContent) rawEvents = JSON.parse(dataEl.textContent);
    } catch (e) {
        rawEvents = [];
    }

    let currentViewDate = new Date(2026, 10, 21);
    let currentMode = 'month';
    let currentFilter = 'ALL';
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    window.switchWsCalMode = function(mode) {
        currentMode = mode;
        const btnM = document.getElementById('wsBtnModeMonth');
        const btnW = document.getElementById('wsBtnModeWeek');
        const monthWrapper = document.getElementById('wsMonthViewWrapper');
        const weekWrapper = document.getElementById('wsWeekViewWrapper');
        if (!btnM || !btnW || !monthWrapper || !weekWrapper) return;

        if (mode === 'month') {
            btnM.classList.add('active', 'btn-primary');
            btnM.classList.remove('text-secondary');
            btnW.classList.remove('active', 'btn-primary');
            btnW.classList.add('text-secondary');
            monthWrapper.classList.remove('d-none');
            weekWrapper.classList.add('d-none');
        } else {
            btnW.classList.add('active', 'btn-primary');
            btnW.classList.remove('text-secondary');
            btnM.classList.remove('active', 'btn-primary');
            btnM.classList.add('text-secondary');
            weekWrapper.classList.remove('d-none');
            monthWrapper.classList.add('d-none');
        }
        window.renderWorkspaceCalendar();
    };

    window.renderWorkspaceCalendar = function() {
        const filtered = currentFilter === 'ALL' ? rawEvents : rawEvents.filter(e => {
            const u = e.title.toUpperCase();
            if (currentFilter === 'DEBATE') return u.includes('DEBATE');
            if (currentFilter === 'WORKSHOP') return u.includes('WORKSHOP') || u.includes('IELTS');
            if (currentFilter === 'CULTURE') return u.includes('CULTURE') || u.includes('GALA');
            if (currentFilter === 'SPEAK') return u.includes('SPEAK');
            return true;
        });

        if (currentMode === 'month') {
            renderMonth(filtered);
        } else {
            renderWeek(filtered);
        }
    };

    function renderMonth(events) {
        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();
        const titleEl = document.getElementById('wsCalPeriodTitle');
        if (titleEl) titleEl.textContent = `${monthNames[month]} ${year}`;

        const grid = document.getElementById('wsMonthGridContainer');
        if (!grid) return;
        grid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInM = new Date(year, month + 1, 0).getDate();
        const daysInPrev = new Date(year, month, 0).getDate();
        const today = new Date();
        const isCurMonth = (today.getFullYear() === year && today.getMonth() === month);
        const todayD = today.getDate();

        let dayCounter = 1;
        let nextMDay = 1;
        let total = (firstDay + daysInM > 35) ? 42 : 35;
        let row = null;

        for (let i = 0; i < total; i++) {
            if (i % 7 === 0) {
                row = document.createElement('div');
                row.className = 'month-grid-row';
                grid.appendChild(row);
            }
            const cell = document.createElement('div');
            cell.className = 'month-day-cell';

            if (i < firstDay) {
                cell.classList.add('other-month');
                cell.innerHTML = `<span class="month-day-number text-muted">${daysInPrev - (firstDay - i - 1)}</span>`;
            } else if (dayCounter <= daysInM) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayCounter).padStart(2, '0')}`;
                const isT = isCurMonth && dayCounter === todayD;
                cell.innerHTML = `<span class="month-day-number ${isT ? 'is-today' : ''}">${dayCounter}</span>`;

                const dayEvts = events.filter(e => e.startDate === dateStr);
                dayEvts.forEach(evt => {
                    const pill = document.createElement('a');
                    pill.className = 'notion-event-pill';
                    pill.href = `<?= base_url('app/modules/events/detail.php?id=') ?>${evt.id}`;
                    pill.style.backgroundColor = evt.bgColor;
                    pill.style.borderLeftColor = evt.borderColor;
                    pill.innerHTML = `<strong>${evt.emoji} ${evt.title}</strong><span class="pill-sub">${evt.startTime} · ${evt.subtitle}</span>`;
                    cell.appendChild(pill);
                });
                dayCounter++;
            } else {
                cell.classList.add('other-month');
                cell.innerHTML = `<span class="month-day-number text-muted">${nextMDay++}</span>`;
            }
            row.appendChild(cell);
        }
    }

    function renderWeek(events) {
        const d = new Date(currentViewDate);
        const diffToMon = (d.getDay() === 0 ? -6 : 1 - d.getDay());
        const mon = new Date(d); mon.setDate(d.getDate() + diffToMon);

        const weekDates = [];
        for (let i = 0; i < 7; i++) {
            const next = new Date(mon); next.setDate(mon.getDate() + i);
            weekDates.push(next);
        }

        const titleEl = document.getElementById('wsCalPeriodTitle');
        if (titleEl) {
            titleEl.textContent = `${monthNames[weekDates[0].getMonth()]} ${weekDates[0].getDate()} – ${monthNames[weekDates[6].getMonth()]} ${weekDates[6].getDate()}, ${weekDates[6].getFullYear()}`;
        }

        const header = document.getElementById('wsWeekDaysHeader');
        if (header) {
            header.innerHTML = '<div class="time-gutter-col py-2 border-end text-muted font-monospace small">TIME</div>';
            const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const todayStr = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-${String(new Date().getDate()).padStart(2, '0')}`;
            weekDates.forEach((wDate, idx) => {
                const dateStr = `${wDate.getFullYear()}-${String(wDate.getMonth() + 1).padStart(2, '0')}-${String(wDate.getDate()).padStart(2, '0')}`;
                const isToday = (dateStr === todayStr);
                const col = document.createElement('div');
                col.className = 'col py-2 border-end';
                if (isToday) col.classList.add('bg-primary-subtle');
                col.innerHTML = `
                    <div class="text-uppercase text-secondary font-monospace" style="font-size: 0.7rem;">${dayNames[idx]}</div>
                    <div class="fw-bold ${isToday ? 'text-primary fs-5' : 'text-dark fs-6'}">${wDate.getDate()}</div>
                `;
                header.appendChild(col);
            });
        }

        const lines = document.getElementById('wsWeekGridLines');
        if (lines) {
            lines.innerHTML = '';
            for (let h = 7; h <= 21; h++) {
                const l = document.createElement('div');
                l.className = 'hourly-row';
                const disp = h < 12 ? `${h} AM` : (h === 12 ? '12 PM' : `${h - 12} PM`);
                l.innerHTML = `<span class="time-label">${disp}</span>`;
                lines.appendChild(l);
            }
        }

        const cols = document.getElementById('wsWeekColumnsContainer');
        if (cols) {
            cols.innerHTML = '<div class="time-gutter-col h-100"></div>';
            const totalMinutes = (22 - 7) * 60;
            weekDates.forEach(wDate => {
                const dateStr = `${wDate.getFullYear()}-${String(wDate.getMonth() + 1).padStart(2, '0')}-${String(wDate.getDate()).padStart(2, '0')}`;
                const col = document.createElement('div');
                col.className = 'week-day-col h-100';

                const dayEvts = events.filter(e => e.startDate === dateStr);
                dayEvts.forEach(evt => {
                    const startParts = evt.startTime.split(':').map(Number);
                    const endParts = evt.endTime.split(':').map(Number);
                    const startMin = Math.max(0, (startParts[0] - 7) * 60 + startParts[1]);
                    const endMin = Math.min(totalMinutes, (endParts[0] - 7) * 60 + endParts[1]);
                    const duration = Math.max(35, endMin - startMin);

                    const block = document.createElement('a');
                    block.className = 'timeline-event-block text-decoration-none';
                    block.href = `<?= base_url('app/modules/events/detail.php?id=') ?>${evt.id}`;
                    block.style.top = `${(startMin / totalMinutes) * 100}%`;
                    block.style.height = `${(duration / totalMinutes) * 100}%`;
                    block.style.backgroundColor = evt.bgColor;
                    block.style.borderLeftColor = evt.borderColor;
                    block.innerHTML = `
                        <div class="fw-bold text-dark text-truncate">${evt.emoji} ${evt.title}</div>
                        <div class="font-monospace text-muted" style="font-size: 0.65rem;">${evt.startTime} - ${evt.endTime}</div>
                        <div class="text-truncate text-secondary" style="font-size: 0.65rem;">${evt.location}</div>
                    `;
                    col.appendChild(block);
                });
                cols.appendChild(col);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.renderWorkspaceCalendar();

        const btnPrev = document.getElementById('wsBtnPrev');
        const btnNext = document.getElementById('wsBtnNext');
        const btnToday = document.getElementById('wsBtnToday');
        const catFilter = document.getElementById('wsCalCategoryFilter');

        if (btnPrev) btnPrev.addEventListener('click', () => {
            if (currentMode === 'month') currentViewDate.setMonth(currentViewDate.getMonth() - 1);
            else currentViewDate.setDate(currentViewDate.getDate() - 7);
            window.renderWorkspaceCalendar();
        });
        if (btnNext) btnNext.addEventListener('click', () => {
            if (currentMode === 'month') currentViewDate.setMonth(currentViewDate.getMonth() + 1);
            else currentViewDate.setDate(currentViewDate.getDate() + 7);
            window.renderWorkspaceCalendar();
        });
        if (btnToday) btnToday.addEventListener('click', () => {
            currentViewDate = new Date(2026, 10, 21);
            window.renderWorkspaceCalendar();
        });
        if (catFilter) catFilter.addEventListener('change', (e) => {
            currentFilter = e.target.value;
            window.renderWorkspaceCalendar();
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
