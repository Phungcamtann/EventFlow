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
                    <i class="bi bi-calendar3 me-1"></i> Xem lịch sự kiện
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

        <!-- Filter & Search Controls (Tìm kiếm Realtime + Bộ lọc Thu gọn) -->
        <div class="ef-card-editorial p-3 mb-4">
            <form method="GET" action="" id="eventsFilterForm">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <!-- Search Input (Realtime + Expanded) -->
                    <div class="flex-grow-1" style="min-width: 260px;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="search" id="eventCatalogSearch" class="form-control border-start-0" 
                                   placeholder="Tìm tên sự kiện, chủ đề, địa điểm..." 
                                   value="<?= htmlspecialchars($search) ?>" oninput="filterEventsRealtime()">
                        </div>
                    </div>

                    <!-- Collapsible Funnel Filter Button -->
                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1.5" data-bs-toggle="collapse" data-bs-target="#filterFunnelCollapse" aria-expanded="<?= ($filterState !== '' || $filterCategory !== '') ? 'true' : 'false' ?>">
                        <i class="bi bi-funnel"></i>
                        <span>Bộ lọc</span>
                        <?php if ($filterState !== '' || $filterCategory !== ''): ?>
                            <span class="badge bg-primary rounded-pill ms-1">Đang lọc</span>
                        <?php endif; ?>
                    </button>

                    <button type="submit" class="btn btn-ef-primary px-3">
                        Lọc
                    </button>
                    <?php if ($search !== '' || $filterState !== '' || $filterCategory !== ''): ?>
                        <a href="<?= base_url('app/modules/events/index.php') ?>" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Collapsible Funnel Drawer -->
                <div class="collapse <?= ($filterState !== '' || $filterCategory !== '') ? 'show' : '' ?> pt-3 mt-3 border-top" id="filterFunnelCollapse">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label small fw-bold text-dark">Trạng thái sự kiện</label>
                            <select name="state" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- Tất cả trạng thái --</option>
                                <option value="REG_OPEN" <?= $filterState === 'REG_OPEN' ? 'selected' : '' ?>>Đang mở đăng ký</option>
                                <option value="UPCOMING" <?= $filterState === 'UPCOMING' ? 'selected' : '' ?>>Sắp mở đăng ký</option>
                                <option value="REG_CLOSED" <?= $filterState === 'REG_CLOSED' ? 'selected' : '' ?>>Đã đóng đăng ký</option>
                                <option value="HAPPENING" <?= $filterState === 'HAPPENING' ? 'selected' : '' ?>>Đang diễn ra</option>
                                <option value="ENDED" <?= $filterState === 'ENDED' ? 'selected' : '' ?>>Đã kết thúc</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label small fw-bold text-dark">Danh mục / Phân loại</label>
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- Tất cả danh mục --</option>
                                <option value="SPEAK" <?= $filterCategory === 'SPEAK' ? 'selected' : '' ?>>Speaking Group</option>
                                <option value="DEBATE" <?= $filterCategory === 'DEBATE' ? 'selected' : '' ?>>Debate Masterclass</option>
                                <option value="WORKSHOP" <?= $filterCategory === 'WORKSHOP' ? 'selected' : '' ?>>Workshop & Học thuật</option>
                                <option value="CULTURE" <?= $filterCategory === 'CULTURE' ? 'selected' : '' ?>>Văn hóa & Gala</option>
                            </select>
                        </div>
                    </div>
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
            <div class="row g-4" id="eventCatalogGrid">
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
                    <div class="col-md-6 col-lg-4 event-catalog-card" data-title="<?= htmlspecialchars(mb_strtolower($evt['EventName'], 'UTF-8')) ?>" data-loc="<?= htmlspecialchars(mb_strtolower($evt['Location'], 'UTF-8')) ?>">
                        <div class="ef-card-editorial h-100 overflow-hidden d-flex flex-column justify-content-between position-relative">
                            <div>
                                <!-- Banner Thumbnail Image (Khôi phục hiển thị ảnh banner) -->
                                <div class="position-relative overflow-hidden" style="height: 160px; background: linear-gradient(135deg, #1e3a8a, #0b192c);">
                                    <?php if (!empty($evt['BannerImage']) && file_exists(__DIR__ . '/../../../' . $evt['BannerImage'])): ?>
                                        <img src="<?= base_url($evt['BannerImage']) ?>" alt="<?= htmlspecialchars($evt['EventName']) ?>" class="w-100 h-100 object-fit-cover">
                                    <?php else: ?>
                                        <!-- Gradient Pattern Thumbnail with Category Icon -->
                                        <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white" style="background: radial-gradient(circle at 80% 20%, rgba(37,99,235,0.45) 0%, rgba(15,23,42,0.95) 100%);">
                                            <i class="bi <?= $evt['category'] === 'DEBATE' ? 'bi-chat-square-quote-fill' : ($evt['category'] === 'WORKSHOP' ? 'bi-mortarboard-fill' : ($evt['category'] === 'CULTURE' ? 'bi-stars' : 'bi-mic-fill')) ?> fs-1 text-white-50 mb-1"></i>
                                            <span class="font-monospace small text-white-50" style="font-size: 0.72rem; letter-spacing: 0.1em;"><?= $catMeta['tag'] ?> · BELL UEH</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="position-absolute top-0 end-0 m-2.5">
                                        <span class="badge-pill-custom <?= $state['css_class'] ?>" style="font-size: 0.7rem; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                                            <?= htmlspecialchars($state['label']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="p-4">
                                    <!-- Category Tag -->
                                    <div class="mb-2">
                                        <span class="badge <?= $catMeta['class'] ?> rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.68rem;">
                                            <?= $catMeta['tag'] ?>
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
                                    <div class="small text-secondary d-flex flex-column gap-2 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar3 text-ef-primary me-2"></i>
                                            <span class="font-monospace"><?= $dBadge ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-clock text-ef-primary me-2"></i>
                                            <span><?= $timeStr ?></span>
                                        </div>
                                        <div class="d-flex align-items-center text-truncate" title="<?= htmlspecialchars($evt['Location']) ?>">
                                            <i class="bi bi-geo-alt text-ef-primary me-2 flex-shrink-0"></i>
                                            <span class="text-truncate"><?= htmlspecialchars($evt['Location']) ?></span>
                                        </div>
                                        <?php if ($seatsText !== null): ?>
                                            <div class="d-flex align-items-center font-monospace" style="font-size: 0.78rem;">
                                                <i class="bi bi-people text-ef-primary me-2"></i>
                                                <span>Đã đăng ký: <strong><?= htmlspecialchars($seatsText) ?></strong></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Action -->
                            <div class="p-4 pt-0">
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
                    </div>
                <?php endforeach; ?>
            </div>

            <script>
            function filterEventsRealtime() {
                const q = document.getElementById('eventCatalogSearch').value.toLowerCase().trim();
                const cards = document.querySelectorAll('.event-catalog-card');
                cards.forEach(c => {
                    const title = c.getAttribute('data-title') || '';
                    const loc = c.getAttribute('data-loc') || '';
                    if (q === '' || title.includes(q) || loc.includes(q)) {
                        c.style.display = '';
                    } else {
                        c.style.display = 'none';
                    }
                });
            }
            </script>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
