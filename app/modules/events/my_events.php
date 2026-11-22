<?php
/**
 * EventFlow — SCR13: Sự kiện của tôi (Dual-Tab Navigation)
 * Đáp ứng: UAT 10 (Tách rõ 2 tab: "Sự kiện tôi điều hành/hỗ trợ" vs "Sự kiện tôi tham gia")
 * Font: Inter/Roboto, Theme Blue
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_login();

// Nếu là Cán bộ BĐH hoặc Admin và truy cập từ ngoài: chuyển vào Workspace nếu muốn, nhưng nếu truy cập trực tiếp thì vẫn phục vụ trang độc lập
if ((is_club_manager() || is_authorized_club_member() || is_system_admin()) && !isset($_GET['view'])) {
    header('Location: ' . base_url('app/modules/dashboard/index.php#my-events'));
    exit;
}

$pdo    = get_db_connection();
$h      = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$userId = (int)current_user_id();
$now    = event_now($pdo);

// 1. Tab 1: Sự kiện tôi điều hành / hỗ trợ (Event_Staff)
$staffStmt = $pdo->prepare("
    SELECT es.StaffRole, es.AssignedAt, es.CheckInAt,
           e.EventID, e.EventName, e.Location, e.StartTime, e.EndTime, e.Status AS EventStatus, e.BannerImage, e.MaxCapacity,
           c.ClubName,
           (SELECT COUNT(*) FROM Event_Registration r WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
    FROM Event_Staff es
    JOIN Event e ON e.EventID = es.EventID
    LEFT JOIN Club c ON c.ClubID = e.ClubID
    WHERE es.AccountID = :uid
    ORDER BY e.StartTime DESC
");
$staffStmt->execute([':uid' => $userId]);
$myStaffEvents = $staffStmt->fetchAll();

// 2. Tab 2: Sự kiện tôi tham gia (Event_Registration)
$regStmt = $pdo->prepare("
    SELECT r.RegID, r.Status AS RegStatus, r.RegDate,
           e.EventID, e.EventName, e.Location, e.StartTime, e.EndTime, e.Status AS EventStatus, e.BannerImage,
           c.ClubName,
           a.AttendanceStatus, a.CheckInAt
    FROM Event_Registration r
    JOIN Event e ON e.EventID = r.EventID
    LEFT JOIN Club c ON c.ClubID = e.ClubID
    LEFT JOIN Attendance a ON a.RegID = r.RegID
    WHERE r.AccountID = :uid
    ORDER BY e.StartTime DESC
");
$regStmt->execute([':uid' => $userId]);
$myRegistrations = $regStmt->fetchAll();

/**
 * Trạng thái tham dự hiển thị cho một đăng ký.
 */
function my_event_status(array $row, string $now): array {
    if ($row['RegStatus'] === 'Cancelled') {
        return ['Đã hủy đăng ký', 'bg-secondary-subtle text-secondary'];
    }
    if ($row['AttendanceStatus'] !== null && (int)$row['AttendanceStatus'] === 1) {
        return ['Đã tham dự', 'bg-success-subtle text-success'];
    }
    if ($row['AttendanceStatus'] !== null && (int)$row['AttendanceStatus'] === 0) {
        return ['Vắng mặt', 'bg-danger-subtle text-danger'];
    }
    if ($row['EventStatus'] === 'Cancelled') {
        return ['Sự kiện đã hủy', 'bg-danger-subtle text-danger'];
    }
    if ($now >= $row['EndTime']) {
        return ['Vắng mặt', 'bg-danger-subtle text-danger'];
    }
    return ['Đã đăng ký', 'bg-primary-subtle text-primary'];
}

$pageTitle = 'Sự kiện của tôi — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('app/modules/events/index.php') ?>" class="text-decoration-none text-muted">Sự kiện</a></li>
            <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Sự kiện của tôi</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-bookmark-check text-primary me-2"></i>Sự Kiện Của Tôi
            </h1>
            <p class="text-secondary small mb-0">Theo dõi các sự kiện bạn tham gia và các hoạt động bạn được phân công phụ trách.</p>
        </div>
    </div>

    <!-- Dual-Tab Navigation -->
    <ul class="nav nav-pills ef-tab-pills mb-4 p-1.5 bg-light rounded-pill border d-inline-flex" id="myEventsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-semibold <?= !empty($myStaffEvents) ? 'active' : '' ?>" id="staff-tab" data-bs-toggle="pill" data-bs-target="#tab-staff" type="button" role="tab" aria-selected="<?= !empty($myStaffEvents) ? 'true' : 'false' ?>">
                <i class="bi bi-person-badge me-1.5 text-primary"></i> Sự kiện tôi điều hành / hỗ trợ
                <span class="badge rounded-pill bg-primary ms-1 font-monospace"><?= count($myStaffEvents) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 fw-semibold <?= empty($myStaffEvents) ? 'active' : '' ?>" id="participant-tab" data-bs-toggle="pill" data-bs-target="#tab-participant" type="button" role="tab" aria-selected="<?= empty($myStaffEvents) ? 'true' : 'false' ?>">
                <i class="bi bi-ticket-perforated me-1.5 text-primary"></i> Sự kiện tôi tham gia
                <span class="badge rounded-pill bg-secondary ms-1 font-monospace"><?= count($myRegistrations) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="myEventsTabContent">
        <!-- ==============================================================
             TAB 1: SỰ KIỆN TÔI ĐIỀU HÀNH / HỖ TRỢ (EVENT_STAFF)
             ============================================================== -->
        <div class="tab-pane fade <?= !empty($myStaffEvents) ? 'show active' : '' ?>" id="tab-staff" role="tabpanel" aria-labelledby="staff-tab">
            <div class="ef-card-editorial p-0 overflow-hidden shadow-sm">
                <?php if (empty($myStaffEvents)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-person-badge fs-1 d-block mb-2 text-primary opacity-50"></i>
                        <h3 class="h6 fw-bold text-dark">Bạn chưa được phân công điều hành sự kiện nào</h3>
                        <p class="small text-secondary mb-0">Khi Ban Điều Hành CLB phân công bạn vào ban tổ chức (Event Staff), sự kiện sẽ xuất hiện tại đây.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                            <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                <tr>
                                    <th class="ps-4 py-3">TÊN SỰ KIỆN</th>
                                    <th class="py-3">VAI TRÒ PHÂN CÔNG</th>
                                    <th class="py-3">THỜI GIAN DIỄN RA</th>
                                    <th class="py-3 text-center">ĐĂNG KÝ / CHỖ</th>
                                    <th class="pe-4 py-3 text-end">THAO TÁC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myStaffEvents as $se): 
                                    $percent = min(100, round(((int)$se['RegisteredCount'] / max(1, (int)$se['MaxCapacity'])) * 100));
                                ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($se['BannerImage']) && file_exists(__DIR__ . '/../../../' . $se['BannerImage'])): ?>
                                                    <img src="<?= base_url($se['BannerImage']) ?>" alt="" class="rounded-2 border" style="width: 48px; height: 34px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= $h($se['EventName']) ?></div>
                                                    <small class="text-muted"><i class="bi bi-geo-alt me-1 text-primary"></i><?= $h($se['Location']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill bg-primary-subtle text-primary font-monospace px-2.5 py-1.5" style="font-size: 0.72rem;">
                                                <i class="bi bi-shield-check me-1"></i><?= $h($se['StaffRole']) ?>
                                            </span>
                                            <?php if (!empty($se['CheckInAt'])): ?>
                                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle font-monospace ms-1 px-2 py-1" style="font-size: 0.7rem;">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Đã trực ca
                                                </span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace ms-1 px-2 py-1" style="font-size: 0.7rem;">
                                                    Chưa trực ca
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 font-monospace small">
                                            <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($se['StartTime'])) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($se['StartTime'])) ?> – <?= date('H:i', strtotime($se['EndTime'])) ?></div>
                                        </td>
                                        <td class="py-3 text-center" style="min-width: 130px;">
                                            <div class="font-monospace small fw-bold">
                                                <?= (int)$se['RegisteredCount'] ?> / <?= (int)$se['MaxCapacity'] ?>
                                            </div>
                                            <div class="progress mt-1 mx-auto" style="height: 4px; max-width: 100px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percent ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="pe-4 py-3 text-end">
                                            <a href="<?= base_url('app/modules/events/console.php?id=' . (int)$se['EventID']) ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold shadow-2xs">
                                                <i class="bi bi-sliders me-1"></i> Điều Hành Console
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

        <!-- ==============================================================
             TAB 2: SỰ KIỆN TÔI THAM GIA (PARTICIPANT)
             ============================================================== -->
        <div class="tab-pane fade <?= empty($myStaffEvents) ? 'show active' : '' ?>" id="tab-participant" role="tabpanel" aria-labelledby="participant-tab">
            <div class="ef-card-editorial p-0 overflow-hidden shadow-sm">
                <?php if (empty($myRegistrations)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <h3 class="h6 fw-bold text-dark">Bạn chưa đăng ký tham gia sự kiện nào</h3>
                        <p class="small text-secondary mb-3">Khám phá các sự kiện hấp dẫn của CLB BELL UEH ngay hôm nay!</p>
                        <a href="<?= base_url('app/modules/events/index.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-compass me-1"></i> Khám phá Sự Kiện
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                            <thead class="bg-light text-muted font-monospace" style="font-size: 0.72rem;">
                                <tr>
                                    <th class="ps-4 py-3">SỰ KIỆN</th>
                                    <th class="py-3">THỜI GIAN</th>
                                    <th class="py-3">ĐỊA ĐIỂM</th>
                                    <th class="py-3">NGÀY ĐĂNG KÝ</th>
                                    <th class="py-3">TRẠNG THÁI ĐIỂM DANH</th>
                                    <th class="pe-4 py-3 text-end">THAO TÁC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myRegistrations as $row):
                                    [$label, $class] = my_event_status($row, $now);
                                ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($row['BannerImage']) && file_exists(__DIR__ . '/../../../' . $row['BannerImage'])): ?>
                                                    <img src="<?= base_url($row['BannerImage']) ?>" alt="" class="rounded-2 border" style="width: 48px; height: 34px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= $h($row['EventName']) ?></div>
                                                    <small class="text-muted"><?= $h($row['ClubName']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 font-monospace small">
                                            <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($row['StartTime'])) ?></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= date('H:i', strtotime($row['StartTime'])) ?> – <?= date('H:i', strtotime($row['EndTime'])) ?></div>
                                        </td>
                                        <td class="py-3 text-muted small"><?= $h($row['Location']) ?></td>
                                        <td class="py-3 font-monospace small text-muted"><?= $h(event_fmt_dt($row['RegDate'])) ?></td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill px-3 py-1.5 <?= $h($class) ?> font-monospace" style="font-size: 0.72rem;">
                                                <?= $h($label) ?>
                                            </span>
                                            <?php if (!empty($row['CheckInAt'])): ?>
                                                <div class="text-muted font-monospace" style="font-size: 0.68rem;">
                                                    Lúc <?= date('H:i d/m', strtotime($row['CheckInAt'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 py-3 text-end">
                                            <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$row['EventID']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                Xem chi tiết
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
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

