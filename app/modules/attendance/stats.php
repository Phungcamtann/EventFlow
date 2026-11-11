<?php
/**
 * EventFlow — SCR15: Thống kê hoạt động cơ bản (FR16, PB20, UC13, BR06)
 * Quyền truy cập: Chỉ dành riêng cho Club Manager
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';
require_once __DIR__ . '/_scope.php';

require_role('Club Manager');

$pdo = get_db_connection();
$h   = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// Lấy CLB mà tài khoản hiện tại quản lý
$club = event_manager_club($pdo, (int)current_user_id());
if (!$club) {
    set_flash('danger', 'Tài khoản của bạn chưa phụ trách CLB nào.');
    header('Location: ' . base_url('index.php'));
    exit;
}
$clubId   = (int)$club['ClubID'];
$statsUrl = base_url('app/modules/attendance/stats.php');

// ---------------------------------------------------------------------------
// 1. Số liệu tổng quan (Overview KPIs)
// ---------------------------------------------------------------------------

// Tổng số thành viên CLB đang hoạt động
$memberStmt = $pdo->prepare("SELECT COUNT(*) FROM Club_Member WHERE ClubID = :c AND Status = 'Active'");
$memberStmt->execute([':c' => $clubId]);
$totalMembers = (int)$memberStmt->fetchColumn();

// Tổng số sự kiện của CLB
$eventStmt = $pdo->prepare("SELECT COUNT(*) FROM Event WHERE ClubID = :c");
$eventStmt->execute([':c' => $clubId]);
$totalEvents = (int)$eventStmt->fetchColumn();

// Tổng số lượt đăng ký hợp lệ (Status = 'Registered')
$regStmt = $pdo->prepare("
    SELECT COUNT(r.RegID)
    FROM Event_Registration r
    JOIN Event e ON e.EventID = r.EventID
    WHERE e.ClubID = :c AND r.Status = 'Registered'
");
$regStmt->execute([':c' => $clubId]);
$totalRegistrations = (int)$regStmt->fetchColumn();

// Tổng số lượt tham dự thực tế (AttendanceStatus = 1)
$attStmt = $pdo->prepare("
    SELECT COUNT(a.AttendanceID)
    FROM Attendance a
    JOIN Event_Registration r ON r.RegID = a.RegID
    JOIN Event e ON e.EventID = r.EventID
    WHERE e.ClubID = :c AND a.AttendanceStatus = 1
");
$attStmt->execute([':c' => $clubId]);
$totalAttendances = (int)$attStmt->fetchColumn();

// Tỷ lệ có mặt trung bình toàn CLB
$overallRate = $totalRegistrations > 0
    ? round(($totalAttendances / $totalRegistrations) * 100, 1)
    : 0;

// ---------------------------------------------------------------------------
// 2. Bộ lọc & Danh sách thống kê từng sự kiện
// ---------------------------------------------------------------------------
$statusFilter = trim($_GET['status'] ?? 'all');
$keyword      = trim($_GET['q'] ?? '');

$sql = "
    SELECT e.*,
           (SELECT COUNT(*) FROM Event_Registration r
            WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount,
           (SELECT COUNT(*) FROM Event_Registration r
            JOIN Attendance a ON a.RegID = r.RegID
            WHERE r.EventID = e.EventID AND a.AttendanceStatus = 1) AS AttendedCount
    FROM Event e
    WHERE e.ClubID = :c
";
$params = [':c' => $clubId];

if ($statusFilter !== 'all' && in_array($statusFilter, ['Published', 'Draft', 'Cancelled'], true)) {
    $sql .= " AND e.Status = :status";
    $params[':status'] = $statusFilter;
}

if ($keyword !== '') {
    $sql .= " AND e.EventName LIKE :kw";
    $params[':kw'] = att_like($keyword);
}

$sql .= " ORDER BY e.StartTime DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventStats = $stmt->fetchAll();

$pageTitle = 'Báo cáo thống kê hoạt động — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Header tiêu đề -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= base_url('app/modules/events/manage.php') ?>" class="text-decoration-none text-muted">Quản lý sự kiện</a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Báo cáo thống kê</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-1">Thống kê hoạt động sự kiện</h1>
            <p class="text-muted small mb-0"><?= $h($club['ClubName']) ?> — Phân tích chi tiết lượt đăng ký, điểm danh và tỷ lệ tham dự.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('app/modules/events/manage.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-calendar-event me-1"></i> Quản lý sự kiện
            </a>
            <a href="<?= base_url('app/modules/attendance/checkin.php') ?>" class="btn btn-outline-primary">
                <i class="bi bi-clipboard-check me-1"></i> Điểm danh sự kiện
            </a>
        </div>
    </div>

    <!-- 4 Thẻ KPI Tổng quan -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary fs-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Thành viên CLB</div>
                        <div class="h3 fw-bold mb-0 font-mono"><?= $totalMembers ?></div>
                        <div class="small text-success"><i class="bi bi-check-circle me-1"></i>Đang hoạt động</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-info-subtle text-info fs-3">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Tổng số sự kiện</div>
                        <div class="h3 fw-bold mb-0 font-mono"><?= $totalEvents ?></div>
                        <div class="small text-muted">Đã thiết lập</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-warning-subtle text-warning-emphasis fs-3">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Lượt đăng ký / Có mặt</div>
                        <div class="h3 fw-bold mb-0 font-mono"><?= $totalAttendances ?> / <?= $totalRegistrations ?></div>
                        <div class="small text-muted">Thực tế tham dự</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-success-subtle text-success fs-3">
                        <i class="bi bi-pie-chart"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Tỷ lệ tham dự CLB</div>
                        <div class="h3 fw-bold mb-0 font-mono"><?= $overallRate ?>%</div>
                        <div class="small text-success">Trung bình toàn bộ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thanh lọc & tìm kiếm -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="<?= $h($statsUrl) ?>" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" name="q" value="<?= $h($keyword) ?>" class="form-control border-start-0" placeholder="Tìm kiếm theo tên sự kiện...">
                </div>
            </div>

            <div class="col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái sự kiện</option>
                    <option value="Published" <?= $statusFilter === 'Published' ? 'selected' : '' ?>>Đang công bố (Published)</option>
                    <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Bản nháp (Draft)</option>
                    <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Đã hủy (Cancelled)</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Lọc
                </button>
                <?php if ($statusFilter !== 'all' || $keyword !== ''): ?>
                    <a href="<?= $h($statsUrl) ?>" class="btn btn-outline-secondary" title="Đặt lại bộ lọc">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Bảng Thống kê chi tiết theo sự kiện -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="card-header bg-white border-bottom border-light p-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Bảng thống kê chi tiết sự kiện</h5>
            <span class="badge bg-light text-secondary border"><?= count($eventStats) ?> sự kiện</span>
        </div>

        <?php if (empty($eventStats)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-bar-chart fs-1 d-block mb-2 text-secondary"></i>
                Không tìm thấy dữ liệu sự kiện nào phù hợp với bộ lọc.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sự kiện</th>
                            <th>Thời gian</th>
                            <th class="text-center">Sức chứa</th>
                            <th class="text-center">Đăng ký</th>
                            <th class="text-center text-success">Có mặt</th>
                            <th class="text-center text-danger">Vắng mặt</th>
                            <th style="min-width: 170px;">Tỷ lệ tham dự</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($eventStats as $evt):
                        $reg  = (int)$evt['RegisteredCount'];
                        $att  = (int)$evt['AttendedCount'];
                        $abs  = max(0, $reg - $att);
                        $rate = $reg > 0 ? round(($att / $reg) * 100, 1) : 0;

                        // Màu sắc tỷ lệ tham dự
                        if ($rate >= 80) {
                            $barClass  = 'bg-success';
                            $textClass = 'text-success';
                        } elseif ($rate >= 50) {
                            $barClass  = 'bg-warning';
                            $textClass = 'text-warning-emphasis';
                        } else {
                            $barClass  = 'bg-danger';
                            $textClass = 'text-danger';
                        }

                        $capText = $evt['MaxCapacity'] !== null ? (int)$evt['MaxCapacity'] : '∞';
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold text-dark"><?= $h($evt['EventName']) ?></div>
                                <div class="small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i><?= $h($evt['Location']) ?>
                                    · <span class="badge badge-sm <?= $evt['Status'] === 'Published' ? 'bg-success-subtle text-success' : ($evt['Status'] === 'Cancelled' ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary') ?>">
                                        <?= $evt['Status'] ?>
                                    </span>
                                </div>
                            </td>
                            <td class="small text-nowrap">
                                <?= $h(event_fmt_dt($evt['StartTime'])) ?><br>
                                <span class="text-muted">đến <?= $h(event_fmt_dt($evt['EndTime'])) ?></span>
                            </td>
                            <td class="text-center font-mono small"><?= $capText ?></td>
                            <td class="text-center font-mono fw-semibold"><?= $reg ?></td>
                            <td class="text-center font-mono text-success fw-bold"><?= $att ?></td>
                            <td class="text-center font-mono text-danger"><?= $abs ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= $rate ?>%;" aria-valuenow="<?= $rate ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="font-mono small fw-bold <?= $textClass ?>" style="min-width: 48px; text-align: right;"><?= $rate ?>%</span>
                                </div>
                            </td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="<?= base_url('app/modules/attendance/checkin.php?event_id=' . (int)$evt['EventID']) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Điểm danh / Xem danh sách">
                                    <i class="bi bi-clipboard-check me-1"></i>Điểm danh
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
