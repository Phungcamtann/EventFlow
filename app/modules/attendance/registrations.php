<?php
/**
 * EventFlow — SCR13: Danh sách đăng ký theo sự kiện (FR14, PB17, UC11)
 * Phân quyền: Club Manager hoặc thành viên CLB được ủy quyền (Board Member / Event Supporter, Active).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';
require_once __DIR__ . '/_scope.php';

require_authorized_club_member();

$pdo     = get_db_connection();
$h       = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$clubIds = att_allowed_club_ids($pdo);
$pageUrl = base_url('app/modules/attendance/registrations.php');
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($clubIds === []) {
    render_forbidden_page('Tài khoản của bạn chưa được gán cho CLB nào để xem danh sách đăng ký.');
}

// ---------------------------------------------------------------------------
// TRƯỜNG HỢP 1: Chưa chọn sự kiện -> Hiển thị danh sách sự kiện để chọn
// ---------------------------------------------------------------------------
if ($eventId === 0) {
    $in = implode(',', array_map('intval', $clubIds));
    $events = $pdo->query("
        SELECT e.EventID, e.EventName, e.StartTime, e.EndTime, e.Location, e.Status, e.MaxCapacity,
               (SELECT COUNT(*) FROM Event_Registration r
                 WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount,
               (SELECT COUNT(*) FROM Event_Registration r
                 WHERE r.EventID = e.EventID AND r.Status = 'Cancelled') AS CancelledCount
        FROM Event e
        WHERE e.ClubID IN ({$in})
        ORDER BY e.StartTime DESC
    ")->fetchAll();

    $pageTitle = 'Danh sách đăng ký sự kiện — EventFlow';
    require_once __DIR__ . '/../../includes/header.php';
    ?>
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Danh sách đăng ký</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-1">Danh sách đăng ký theo sự kiện</h1>
                <p class="text-muted small mb-0">Chọn sự kiện để xem chi tiết danh sách người đăng ký và số chỗ còn lại.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('app/modules/attendance/checkin.php') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-clipboard-check me-1"></i> Màn hình điểm danh
                </a>
                <?php if (is_club_manager()): ?>
                    <a href="<?= base_url('app/modules/attendance/stats.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-bar-chart-line me-1"></i> Báo cáo thống kê
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <?php if (empty($events)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                    Chưa có sự kiện nào trong CLB của bạn.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Sự kiện</th>
                                <th>Thời gian</th>
                                <th class="text-center">Sức chứa</th>
                                <th class="text-center">Đang đăng ký</th>
                                <th class="text-center text-muted">Đã hủy</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($events as $e):
                            $reg = (int)$e['RegisteredCount'];
                            $cap = $e['MaxCapacity'] !== null ? (int)$e['MaxCapacity'] : null;
                            $capStr = $cap !== null ? $cap : '∞';
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><?= $h($e['EventName']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= $h($e['Location']) ?></div>
                                </td>
                                <td class="small text-nowrap">
                                    <?= $h(event_fmt_dt($e['StartTime'])) ?><br>
                                    <span class="text-muted">đến <?= $h(event_fmt_dt($e['EndTime'])) ?></span>
                                </td>
                                <td class="text-center font-mono small"><?= $capStr ?></td>
                                <td class="text-center font-mono fw-bold text-primary"><?= $reg ?></td>
                                <td class="text-center font-mono small text-muted"><?= (int)$e['CancelledCount'] ?></td>
                                <td>
                                    <?php if ($e['Status'] === 'Published'): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">Đang công bố</span>
                                    <?php elseif ($e['Status'] === 'Cancelled'): ?>
                                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1">Đã hủy</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Bản nháp</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="<?= $h($pageUrl . '?event_id=' . (int)$e['EventID']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-people me-1"></i>Xem danh sách
                                    </a>
                                    <a href="<?= base_url('app/modules/attendance/checkin.php?event_id=' . (int)$e['EventID']) ?>" class="btn btn-sm btn-outline-success ms-1" title="Điểm danh">
                                        <i class="bi bi-clipboard-check"></i>
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
    <?php
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// ---------------------------------------------------------------------------
// TRƯỜNG HỢP 2: Đã chọn sự kiện -> Chi tiết danh sách người đăng ký
// ---------------------------------------------------------------------------
$evt = att_load_event($pdo, $eventId, $clubIds);
if (!$evt) {
    set_flash('danger', 'Không tìm thấy sự kiện hoặc bạn không có quyền xem danh sách của sự kiện này.');
    header('Location: ' . $pageUrl);
    exit;
}

$now = event_now($pdo);

// Lấy toàn bộ người đăng ký (bao gồm cả 'Registered' và 'Cancelled')
$stmt = $pdo->prepare("
    SELECT r.RegID, r.RegDate, r.Status AS RegStatus,
           a.AccountID, a.FullName, a.StudentCode, a.Email,
           att.AttendanceID, att.AttendanceStatus, att.CheckInAt
    FROM Event_Registration r
    JOIN Account a ON a.AccountID = r.AccountID
    LEFT JOIN Attendance att ON att.RegID = r.RegID
    WHERE r.EventID = :e
    ORDER BY r.RegDate DESC
");
$stmt->execute([':e' => $eventId]);
$registrations = $stmt->fetchAll();

// Đếm các chỉ số
$validCount     = 0;
$cancelledCount = 0;
$attendedCount  = 0;

foreach ($registrations as $r) {
    if ($r['RegStatus'] === 'Registered') {
        $validCount++;
        if ($r['AttendanceStatus'] !== null && (int)$r['AttendanceStatus'] === 1) {
            $attendedCount++;
        }
    } else {
        $cancelledCount++;
    }
}

$capacity = $evt['MaxCapacity'] !== null ? (int)$evt['MaxCapacity'] : null;
$seatsLeft = $capacity !== null ? max(0, $capacity - $validCount) : null;

$pageTitle = 'Danh sách đăng ký: ' . $evt['EventName'] . ' — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $h($pageUrl) ?>" class="text-decoration-none text-muted">Chọn sự kiện</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?= $h($evt['EventName']) ?></li>
        </ol>
    </nav>

    <!-- Header & Thao tác chuyển hướng -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold text-dark mb-0"><?= $h($evt['EventName']) ?></h1>
                <?php if ($evt['Status'] === 'Published'): ?>
                    <span class="badge bg-success-subtle text-success rounded-pill">Đang công bố</span>
                <?php elseif ($evt['Status'] === 'Cancelled'): ?>
                    <span class="badge bg-danger-subtle text-danger rounded-pill">Đã hủy</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill">Bản nháp</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                <i class="bi bi-calendar-event me-1"></i><?= $h(event_fmt_dt($evt['StartTime'])) ?> ·
                <i class="bi bi-geo-alt me-1"></i><?= $h($evt['Location']) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= $h($pageUrl) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Đổi sự kiện
            </a>
            <a href="<?= base_url('app/modules/attendance/checkin.php?event_id=' . $eventId) ?>" class="btn btn-primary fw-semibold">
                <i class="bi bi-clipboard-check me-1"></i> Chuyển sang Điểm danh
            </a>
        </div>
    </div>

    <!-- 4 Thẻ KPI Tóm tắt đăng ký sự kiện -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Đăng ký hợp lệ</div>
                <div class="h3 fw-bold mb-0 text-primary font-mono"><?= $validCount ?></div>
                <div class="small text-muted">Đang giữ chỗ</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Sức chứa tối đa</div>
                <div class="h3 fw-bold mb-0 text-dark font-mono"><?= $capacity !== null ? $capacity : 'Không giới hạn' ?></div>
                <div class="small text-muted">Quy mô sự kiện</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Số chỗ còn trống</div>
                <div class="h3 fw-bold mb-0 font-mono <?= ($seatsLeft !== null && $seatsLeft <= 5) ? 'text-danger' : 'text-success' ?>">
                    <?= $seatsLeft !== null ? $seatsLeft : 'Vô hạn' ?>
                </div>
                <div class="small text-muted"><?= ($seatsLeft === 0) ? 'Đã hết chỗ' : 'Còn khả dụng' ?></div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <div class="text-muted small">Đã điểm danh</div>
                <div class="h3 fw-bold mb-0 text-success font-mono"><?= $attendedCount ?> / <?= $validCount ?></div>
                <div class="small text-muted">Có mặt thực tế</div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc & Ô tìm kiếm nhanh -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="row g-3 align-items-center">
            <div class="col-md-7">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" id="searchReg" class="form-control border-start-0"
                           placeholder="Tìm kiếm theo Họ tên, MSSV hoặc Email..." autocomplete="off" autofocus>
                </div>
            </div>
            <div class="col-md-5 d-flex justify-content-md-end gap-2">
                <div class="btn-group w-100 w-md-auto" role="group">
                    <input type="radio" class="btn-check" name="statusFilter" id="filterAll" value="all" checked>
                    <label class="btn btn-outline-secondary btn-sm" for="filterAll">Tất cả (<?= count($registrations) ?>)</label>

                    <input type="radio" class="btn-check" name="statusFilter" id="filterValid" value="registered">
                    <label class="btn btn-outline-primary btn-sm" for="filterValid">Hợp lệ (<?= $validCount ?>)</label>

                    <input type="radio" class="btn-check" name="statusFilter" id="filterCancelled" value="cancelled">
                    <label class="btn btn-outline-danger btn-sm" for="filterCancelled">Đã hủy (<?= $cancelledCount ?>)</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng danh sách người đăng ký -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <?php if (empty($registrations)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                Sự kiện này chưa có người đăng ký nào.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="regListTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Họ và tên</th>
                            <th>MSSV</th>
                            <th>Email</th>
                            <th>Thời gian đăng ký</th>
                            <th>Trạng thái đăng ký</th>
                            <th class="text-end pe-4">Điểm danh</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $idx = 1;
                    foreach ($registrations as $r):
                        $isCancelled = $r['RegStatus'] === 'Cancelled';
                        $hasAttended = $r['AttendanceStatus'] !== null && (int)$r['AttendanceStatus'] === 1;
                    ?>
                        <tr data-name="<?= $h($r['FullName']) ?>"
                            data-mssv="<?= $h($r['StudentCode'] ?? '') ?>"
                            data-email="<?= $h($r['Email']) ?>"
                            data-status="<?= $isCancelled ? 'cancelled' : 'registered' ?>"
                            class="<?= $isCancelled ? 'table-light text-muted' : '' ?>">
                            <td class="ps-4 text-muted small font-mono"><?= $idx++ ?></td>
                            <td>
                                <div class="fw-semibold <?= $isCancelled ? 'text-secondary text-decoration-line-through' : 'text-dark' ?>">
                                    <?= $h($r['FullName']) ?>
                                </div>
                            </td>
                            <td class="font-mono small"><?= $h($r['StudentCode'] ?? '—') ?></td>
                            <td class="small text-muted"><?= $h($r['Email']) ?></td>
                            <td class="small text-nowrap"><?= $h(event_fmt_dt($r['RegDate'])) ?></td>
                            <td>
                                <?php if ($isCancelled): ?>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">Đã hủy đăng ký</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1">Đã đăng ký</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4 text-nowrap">
                                <?php if ($isCancelled): ?>
                                    <span class="small text-muted">—</span>
                                <?php elseif ($hasAttended): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                                        <i class="bi bi-check2-circle me-1"></i>Có mặt (<?= $h(event_fmt_dt($r['CheckInAt'])) ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1">Chưa điểm danh</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="noRegMatch" class="p-4 text-center text-muted" hidden>
                Không có người đăng ký nào khớp với bộ lọc hoặc từ khóa tìm kiếm.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Lọc dữ liệu tức thời trên client bằng JavaScript (không tải lại trang)
(function () {
    var searchInput = document.getElementById('searchReg');
    var table = document.getElementById('regListTable');
    var noMatch = document.getElementById('noRegMatch');
    var filterRadios = document.querySelectorAll('input[name="statusFilter"]');
    if (!table) return;

    function norm(s) {
        return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'd').toLowerCase().trim();
    }

    function applyFilters() {
        var q = searchInput ? norm(searchInput.value) : '';
        var selectedStatus = 'all';
        filterRadios.forEach(function (r) {
            if (r.checked) selectedStatus = r.value;
        });

        var shown = 0;
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            var name = norm(tr.dataset.name);
            var mssv = norm(tr.dataset.mssv);
            var email = norm(tr.dataset.email);
            var status = tr.dataset.status;

            var matchSearch = !q || name.indexOf(q) !== -1 || mssv.indexOf(q) !== -1 || email.indexOf(q) !== -1;
            var matchStatus = (selectedStatus === 'all') || (status === selectedStatus);

            var visible = matchSearch && matchStatus;
            tr.hidden = !visible;
            if (visible) shown++;
        });

        if (noMatch) noMatch.hidden = shown > 0;
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    filterRadios.forEach(function (r) {
        r.addEventListener('change', applyFilters);
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
