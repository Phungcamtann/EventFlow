<?php
/**
 * EventFlow — SCR05: Danh Mục Sự Kiện Công Khai CLB BELL UEH
 * Đáp ứng: UAT 02 (Công khai cho khách & sinh viên), UAT 03 (Lọc sự kiện hợp lệ), UAT 19 (State Machine 7 trạng thái)
 * Font: Inter/Roboto, Palette: Unified Blue Theme
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

$pageTitle = 'Danh sách Sự kiện — CLB Tiếng Anh BELL UEH';
require_once __DIR__ . '/../../includes/header.php';

$pdo = get_db_connection();
$now = event_now($pdo);

// Nhận tham số tìm kiếm & lọc
$search = trim($_GET['search'] ?? '');
$filterState = trim($_GET['state'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');

// Truy vấn danh sách sự kiện Published
$sql = "
    SELECT e.*, c.ClubName,
           (SELECT COUNT(*) FROM Event_Registration r
             WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
    FROM Event e
    LEFT JOIN Club c ON e.ClubID = c.ClubID
    WHERE e.Status = 'Published'
";
$params = [];

if ($search !== '') {
    $sql .= " AND (e.EventName LIKE :search OR e.Description LIKE :searchDesc OR e.Location LIKE :searchLoc)";
    $params[':search'] = '%' . $search . '%';
    $params[':searchDesc'] = '%' . $search . '%';
    $params[':searchLoc'] = '%' . $search . '%';
}

$sql .= " ORDER BY CASE WHEN e.EndTime >= :now THEN 0 ELSE 1 END, e.StartTime ASC";
$params[':now'] = $now;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rawEvents = $stmt->fetchAll();

// Xử lý trạng thái theo State Machine (UAT 19)
$events = [];
foreach ($rawEvents as $evt) {
    $state = event_state($evt, (int)$evt['RegisteredCount'], $now);
    
    // Áp dụng bộ lọc trạng thái nếu người dùng chọn
    if ($filterState !== '' && $state['state'] !== $filterState) {
        continue;
    }

    // Phân loại danh mục
    $titleUpper = mb_strtoupper($evt['EventName'], 'UTF-8');
    if (str_contains($titleUpper, 'DEBATE')) $cat = 'DEBATE';
    elseif (str_contains($titleUpper, 'WORKSHOP') || str_contains($titleUpper, 'IELTS')) $cat = 'WORKSHOP';
    elseif (str_contains($titleUpper, 'CULTURE') || str_contains($titleUpper, 'GALA')) $cat = 'CULTURE';
    else $cat = 'SPEAK';

    if ($filterCategory !== '' && $cat !== $filterCategory) {
        continue;
    }

    $evt['processed_state'] = $state;
    $evt['category'] = $cat;
    $events[] = $evt;
}
?>

<div class="bg-ef-canvas py-4" style="min-height: 100vh;">
    <div class="container">

        <!-- Breadcrumb & Top Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Sự kiện CLB BELL UEH</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('app/modules/events/calendar.php') ?>" class="btn btn-sm btn-ef-outline">
                    <i class="bi bi-calendar3 me-1"></i> Xem dạng Lịch Notion
                </a>
            </div>
        </div>

        <!-- Hero Header -->
        <div class="mb-4">
            <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">
                OFFICIAL ACTIVITIES & WORKSHOPS
            </span>
            <h1 class="h2 fw-bold text-dark mt-1 mb-2">Khám Phá Hoạt Động & Sự Kiện</h1>
            <p class="text-secondary small mb-0" style="max-width: 650px;">
                Theo dõi lịch trình các buổi sinh hoạt Speaking Group, Workshop chuyên đề và sự kiện học thuật tiếng Anh do CLB BELL UEH tổ chức.
            </p>
        </div>

        <!-- Filter & Search Controls -->
        <div class="ef-card-editorial p-3 mb-4">
            <form method="GET" action="" class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Tìm tên sự kiện, địa điểm, chủ đề..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <!-- State Filter -->
                <div class="col-6 col-md-3">
                    <select name="state" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="REG_OPEN" <?= $filterState === 'REG_OPEN' ? 'selected' : '' ?>>Đang mở đăng ký</option>
                        <option value="UPCOMING" <?= $filterState === 'UPCOMING' ? 'selected' : '' ?>>Sắp mở đăng ký</option>
                        <option value="REG_CLOSED" <?= $filterState === 'REG_CLOSED' ? 'selected' : '' ?>>Đã đóng đăng ký</option>
                        <option value="HAPPENING" <?= $filterState === 'HAPPENING' ? 'selected' : '' ?>>Đang diễn ra</option>
                        <option value="ENDED" <?= $filterState === 'ENDED' ? 'selected' : '' ?>>Đã kết thúc</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-6 col-md-2">
                    <select name="category" class="form-select">
                        <option value="">-- Danh mục --</option>
                        <option value="SPEAK" <?= $filterCategory === 'SPEAK' ? 'selected' : '' ?>>Speaking</option>
                        <option value="DEBATE" <?= $filterCategory === 'DEBATE' ? 'selected' : '' ?>>Debate</option>
                        <option value="WORKSHOP" <?= $filterCategory === 'WORKSHOP' ? 'selected' : '' ?>>Workshop</option>
                        <option value="CULTURE" <?= $filterCategory === 'CULTURE' ? 'selected' : '' ?>>Culture/Gala</option>
                    </select>
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-ef-primary w-100 btn-sm">
                        Lọc
                    </button>
                    <?php if ($search !== '' || $filterState !== '' || $filterCategory !== ''): ?>
                        <a href="<?= base_url('app/modules/events/index.php') ?>" class="btn btn-outline-secondary btn-sm" title="Xóa bộ lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Event Cards Grid -->
        <?php if (empty($events)): ?>
            <div class="ef-card-editorial p-5 text-center text-muted">
                <i class="bi bi-calendar-x fs-1 text-secondary mb-3 d-block"></i>
                <h5 class="fw-bold text-dark mb-1">Không tìm thấy sự kiện nào</h5>
                <p class="small mb-3">Vui lòng thử tìm kiếm với từ khóa khác hoặc điều chỉnh lại bộ lọc trạng thái.</p>
                <a href="<?= base_url('app/modules/events/index.php') ?>" class="btn btn-sm btn-outline-primary">
                    Xem toàn bộ sự kiện
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php 
                $catBadges = [
                    'SPEAK'   => ['tag' => 'SPEAKING', 'class' => 'badge-ef-blue'],
                    'DEBATE'  => ['tag' => 'DEBATE',   'class' => 'badge-ef-indigo'],
                    'WORKSHOP'=> ['tag' => 'WORKSHOP', 'class' => 'badge-ef-sky'],
                    'CULTURE' => ['tag' => 'CULTURE',  'class' => 'badge-ef-teal'],
                ];

                foreach ($events as $evt): 
                    $state = $evt['processed_state'];
                    $catMeta = $catBadges[$evt['category']] ?? ['tag' => 'GENERAL', 'class' => 'badge-ef-blue'];
                    $dBadge = date('d/m/Y', strtotime($evt['StartTime']));
                    $timeStr = date('H:i', strtotime($evt['StartTime'])) . ' - ' . date('H:i', strtotime($evt['EndTime']));
                    $seatsText = event_seats_text($state);
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="ef-card-editorial h-100 p-4 d-flex flex-column justify-content-between position-relative">
                            <div>
                                <!-- Top Badges -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge <?= $catMeta['class'] ?> rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.7rem;">
                                        <?= $catMeta['tag'] ?>
                                    </span>
                                    <span class="badge-pill-custom <?= $state['css_class'] ?>" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars($state['label']) ?>
                                    </span>
                                </div>

                                <!-- Event Title -->
                                <h3 class="h5 fw-bold text-dark mb-2 lh-base" style="min-height: 52px;">
                                    <?= htmlspecialchars($evt['EventName']) ?>
                                </h3>

                                <!-- Description Excerpt -->
                                <p class="text-secondary small mb-3 lh-base text-truncate-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 40px;">
                                    <?= htmlspecialchars($evt['Description']) ?>
                                </p>

                                <!-- Meta Info List -->
                                <div class="small text-secondary d-flex flex-column gap-1.5 mb-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-calendar3 text-ef-primary"></i>
                                        <span class="font-monospace"><?= $dBadge ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-clock text-ef-primary"></i>
                                        <span><?= $timeStr ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-truncate" title="<?= htmlspecialchars($evt['Location']) ?>">
                                        <i class="bi bi-geo-alt text-ef-primary flex-shrink-0"></i>
                                        <span class="text-truncate"><?= htmlspecialchars($evt['Location']) ?></span>
                                    </div>
                                    <?php if ($seatsText !== null): ?>
                                        <div class="d-flex align-items-center gap-2 font-monospace" style="font-size: 0.78rem;">
                                            <i class="bi bi-people text-ef-primary"></i>
                                            <span>Đã đăng ký: <strong><?= htmlspecialchars($seatsText) ?></strong></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Bottom Action -->
                            <div class="pt-3 border-top border-light d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">
                                    BELL CLUB UEH
                                </span>
                                <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$evt['EventID']) ?>" class="btn btn-sm btn-ef-primary">
                                    Chi tiết & Đăng ký <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
