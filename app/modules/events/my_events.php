<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_login();

$pdo    = get_db_connection();
$h      = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$userId = (int)current_user_id();
$now    = event_now($pdo);

$stmt = $pdo->prepare("
    SELECT r.RegID, r.Status AS RegStatus, r.RegDate,
           e.EventID, e.EventName, e.Location, e.StartTime, e.EndTime, e.Status AS EventStatus,
           c.ClubName,
           a.AttendanceStatus
    FROM Event_Registration r
    JOIN Event e ON e.EventID = r.EventID
    LEFT JOIN Club c ON c.ClubID = e.ClubID
    LEFT JOIN Attendance a ON a.RegID = r.RegID
    WHERE r.AccountID = :uid
    ORDER BY e.StartTime DESC
");
$stmt->execute([':uid' => $userId]);
$rows = $stmt->fetchAll();

/**
 * Trạng thái tham dự hiển thị cho một đăng ký.
 * Trả về [nhãn, class badge].
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
        // Đã kết thúc, đăng ký còn hiệu lực nhưng không có dòng điểm danh
        return ['Vắng mặt', 'bg-danger-subtle text-danger'];
    }
    return ['Đã đăng ký', 'bg-primary-subtle text-primary'];
}

$pageTitle = 'Sự kiện của tôi — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-dark mb-1">Sự kiện của tôi</h1>
        <p class="text-muted small mb-0">Các sự kiện bạn đã đăng ký và trạng thái tham dự.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <?php if (empty($rows)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Bạn chưa đăng ký sự kiện nào.
                <div class="mt-3">
                    <a href="<?= $h(base_url('index.php')) ?>" class="btn btn-outline-primary btn-sm">Khám phá sự kiện</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sự kiện</th>
                            <th>Thời gian</th>
                            <th>Địa điểm</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row):
                        [$label, $class] = my_event_status($row, $now);
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold text-dark"><?= $h($row['EventName']) ?></div>
                                <?php if (!empty($row['ClubName'])): ?>
                                    <div class="small text-muted"><?= $h($row['ClubName']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-nowrap">
                                <?= $h(event_fmt_dt($row['StartTime'])) ?><br>
                                <span class="text-muted">đến <?= $h(event_fmt_dt($row['EndTime'])) ?></span>
                            </td>
                            <td class="small"><?= $h($row['Location']) ?></td>
                            <td class="small text-nowrap"><?= $h(event_fmt_dt($row['RegDate'])) ?></td>
                            <td><span class="badge rounded-pill px-3 py-2 <?= $h($class) ?>"><?= $h($label) ?></span></td>
                            <td class="text-end pe-4">
                                <a href="<?= $h(base_url('app/modules/events/detail.php?id=' . (int)$row['EventID'])) ?>"
                                   class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
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
