<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

require_role('Club Manager');

$pdo       = get_db_connection();
$h         = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$manageUrl = base_url('app/modules/events/manage.php');

$club = event_manager_club($pdo, (int)current_user_id());
if (!$club) {
    set_flash('danger', 'Tài khoản của bạn chưa phụ trách CLB nào.');
    header('Location: ' . base_url('index.php'));
    exit;
}
$clubId = (int)$club['ClubID'];

/**
 * Hủy sự kiện: chỉ đổi Event.Status → 'Cancelled'.
 * KHÔNG xóa / sửa Event_Registration hay Attendance (bảo toàn dữ liệu đăng ký — FR09).
 * Quy tắc tạm: chỉ hủy sự kiện đã công bố và chưa kết thúc.
 */
function event_cancel(PDO $pdo, int $eventId, int $clubId): array {
    $fail = static function (string $msg) use ($pdo): array {
        $pdo->rollBack();
        return [false, $msg];
    };

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id AND ClubID = :c FOR UPDATE");
        $stmt->execute([':id' => $eventId, ':c' => $clubId]);
        $evt = $stmt->fetch();

        if (!$evt) {
            return $fail('Không tìm thấy sự kiện.');
        }
        if ($evt['Status'] === 'Cancelled') {
            return $fail('Sự kiện này đã được hủy trước đó.');
        }
        if ($evt['Status'] !== 'Published') {
            return $fail('Chỉ hủy được sự kiện đã công bố.');
        }
        if (event_now($pdo) >= $evt['EndTime']) {
            return $fail('Sự kiện đã kết thúc, không thể hủy.');
        }

        $up = $pdo->prepare("UPDATE Event SET Status = 'Cancelled' WHERE EventID = :id");
        $up->execute([':id' => $eventId]);

        $pdo->commit();
        return [true, 'Đã hủy sự kiện. Dữ liệu đăng ký được giữ nguyên.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[EventFlow] cancel event failed: ' . $e->getMessage());
        return [false, 'Không thể xử lý yêu cầu. Vui lòng thử lại.'];
    }
}

// ---------------------------------------------------------------------------
// Xử lý POST: hủy sự kiện (Post/Redirect/Get)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.');
    } elseif (($_POST['action'] ?? '') === 'cancel_event') {
        [$ok, $msg] = event_cancel($pdo, (int)($_POST['event_id'] ?? 0), $clubId);
        set_flash($ok ? 'success' : 'danger', $msg);
    } else {
        set_flash('danger', 'Thao tác không hợp lệ.');
    }
    header('Location: ' . $manageUrl);
    exit;
}

// ---------------------------------------------------------------------------
// Dữ liệu hiển thị
// ---------------------------------------------------------------------------
$now  = event_now($pdo);
$stmt = $pdo->prepare("
    SELECT e.*,
           (SELECT COUNT(*) FROM Event_Registration r
             WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
    FROM Event e
    WHERE e.ClubID = :c
    ORDER BY e.StartTime DESC
");
$stmt->execute([':c' => $clubId]);
$events = $stmt->fetchAll();

$pageTitle = 'Quản lý sự kiện — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý sự kiện</h1>
            <p class="text-muted small mb-0"><?= $h($club['ClubName']) ?> — gồm cả bản nháp và sự kiện đã hủy.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('app/modules/attendance/stats.php') ?>" class="btn btn-outline-primary fw-semibold">
                <i class="bi bi-bar-chart-line me-1"></i> Báo cáo thống kê
            </a>
            <a href="<?= base_url('app/modules/events/form.php') ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-plus-circle me-1"></i> Tạo sự kiện mới
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <?php if (empty($events)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Chưa có sự kiện nào. Hãy tạo sự kiện đầu tiên của CLB.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sự kiện</th>
                            <th>Thời gian</th>
                            <th>Địa điểm</th>
                            <th class="text-nowrap">Đã đăng ký</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $evt):
                        $state     = event_state($evt, (int)$evt['RegisteredCount'], $now);
                        $isEnded   = $now >= $evt['EndTime'];
                        $canEdit   = $evt['Status'] !== 'Cancelled';
                        $canCancel = $evt['Status'] === 'Published' && !$isEnded;
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold text-dark"><?= $h($evt['EventName']) ?></div>
                                <?php if ($isEnded && $evt['Status'] === 'Published'): ?>
                                    <div class="small text-muted">Đã kết thúc</div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-nowrap">
                                <?= $h(event_fmt_dt($evt['StartTime'])) ?><br>
                                <span class="text-muted">đến <?= $h(event_fmt_dt($evt['EndTime'])) ?></span>
                            </td>
                            <td class="small"><?= $h($evt['Location']) ?></td>
                            <td class="font-mono small text-nowrap"><?= $h(event_seats_text($state)) ?></td>
                            <td><span class="badge-pill-custom <?= $h($state['css_class']) ?>"><?= $h($state['label']) ?></span></td>
                            <td class="text-end pe-4 text-nowrap">
                                <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$evt['EventID']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                <a href="<?= base_url('app/modules/attendance/checkin.php?event_id=' . (int)$evt['EventID']) ?>"
                                   class="btn btn-sm btn-outline-success" title="Điểm danh sự kiện"><i class="bi bi-clipboard-check"></i></a>
                                <?php if ($canEdit): ?>
                                    <a href="<?= base_url('app/modules/events/form.php?id=' . (int)$evt['EventID']) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                <?php endif; ?>
                                <?php if ($canCancel): ?>
                                    <form method="POST" action="<?= $h($manageUrl) ?>" class="d-inline"
                                          data-name="<?= $h($evt['EventName']) ?>"
                                          onsubmit="return confirm('Hủy sự kiện &quot;' + this.dataset.name + '&quot;?\nDữ liệu đăng ký vẫn được giữ nhưng sự kiện sẽ chuyển sang trạng thái Đã hủy và không thể hoàn tác.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel_event">
                                        <input type="hidden" name="event_id" value="<?= (int)$evt['EventID'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hủy sự kiện">
                                            <i class="bi bi-x-circle"></i> Hủy
                                        </button>
                                    </form>
                                <?php endif; ?>
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
