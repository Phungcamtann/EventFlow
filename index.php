<?php
/**
 * EventFlow — Trang chủ / Khám phá sự kiện
 * Bám sát Thiết kế Prototype Figma 02 (Danh sách sự kiện)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session_auth.php';

// Yêu cầu đăng nhập trước khi vào trang chủ
if (!is_logged_in()) {
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

$pageTitle = 'Khám phá sự kiện';
require_once __DIR__ . '/includes/header.php';

// Lấy danh sách sự kiện từ CSDL (nếu có kết nối)
$events = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("
        SELECT e.*, c.ClubName 
        FROM Event e 
        LEFT JOIN Club c ON e.ClubID = c.ClubID 
        WHERE e.Status = 'Published'
        ORDER BY e.StartTime ASC
    ");
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    // Nếu chưa khởi tạo DB, hiển thị mảng tĩnh minh họa theo đúng Prototype Figma
    $events = [
        [
            'EventID' => 1,
            'EventName' => 'English Speaking Circle',
            'Category' => 'SPEAK',
            'HeaderClass' => 'header-speak',
            'DateBadge' => '14 TH11',
            'Time' => '18:00 - 20:30',
            'Location' => 'UEH Cơ sở B - Phòng B1.302',
            'StatusBadge' => 'Đang mở đăng ký',
            'BadgeClass' => 'badge-open'
        ],
        [
            'EventID' => 2,
            'EventName' => 'Debate Lab: Speak Up',
            'Category' => 'DEBATE',
            'HeaderClass' => 'header-debate',
            'DateBadge' => '21 TH11',
            'Time' => '14:00 - 17:00',
            'Location' => 'UEH Cơ sở B - Hội trường B1.302',
            'StatusBadge' => 'Đang mở đăng ký',
            'BadgeClass' => 'badge-open'
        ],
        [
            'EventID' => 3,
            'EventName' => 'Cultural Exchange Day',
            'Category' => 'CULTURE',
            'HeaderClass' => 'header-culture',
            'DateBadge' => '05 TH12',
            'Time' => '08:30 - 11:30',
            'Location' => 'Sảnh B1 - UEH Nguyễn Tri Phương',
            'StatusBadge' => 'Sắp mở đăng ký',
            'BadgeClass' => 'badge-closed'
        ]
    ];
}
?>

<div class="container py-4">
    <!-- HERO BANNER (Figma 02) -->
    <div class="ef-hero-banner mb-5">
        <div class="ef-hero-circle"></div>
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill font-mono mb-3 shadow-sm text-uppercase fw-semibold" style="font-size: 0.75rem;">
                    Hoạt động nổi bật
                </span>
                <h1 class="display-6 fw-bold text-dark mb-2">Tìm sự kiện dành cho bạn</h1>
                <p class="text-secondary fs-6 mb-4" style="max-width: 600px;">
                    Học hỏi, kết nối và cùng phát triển trong cộng đồng CLB Tiếng Anh sinh viên UEH.
                </p>
                <a href="#events-section" class="btn btn-primary btn-lg px-4 shadow-sm">
                    Khám phá ngay <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- SỰ KIỆN SẮP DIỄN RA SECTION -->
    <div id="events-section" class="mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
            <div>
                <h2 class="h3 fw-bold mb-1">Sự kiện sắp diễn ra</h2>
                <p class="text-secondary small mb-0">Theo dõi thông tin và đăng ký tham gia các hoạt động giao lưu tiếng Anh.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('club/info.php') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-info-circle me-1"></i> Về CLB
                </a>
            </div>
        </div>

        <!-- GRID 3 CỘT (g-4 theo Design Rules) -->
        <div class="row g-4">
            <?php foreach ($events as $index => $evt): 
                $cat = $evt['Category'] ?? ($index === 0 ? 'SPEAK' : ($index === 1 ? 'DEBATE' : 'CULTURE'));
                $hClass = $evt['HeaderClass'] ?? ($index === 0 ? 'header-speak' : ($index === 1 ? 'header-debate' : 'header-culture'));
                $dBadge = $evt['DateBadge'] ?? (!empty($evt['StartTime']) ? date('d \T\Hm', strtotime($evt['StartTime'])) : '14 TH11');
                $timeStr = $evt['Time'] ?? (!empty($evt['StartTime']) ? date('H:i', strtotime($evt['StartTime'])) . ' - ' . date('H:i', strtotime($evt['EndTime'])) : '');
                $locStr = $evt['Location'] ?? 'UEH Cơ sở B';
                $badgeText = $evt['StatusBadge'] ?? 'Đang mở đăng ký';
                $bClass = $evt['BadgeClass'] ?? 'badge-open';
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-hover h-100 overflow-hidden border">
                        <!-- Top Banner Card with Category & Circle -->
                        <div class="event-card-header <?= $hClass ?>">
                            <div class="header-circle"></div>
                            <span><?= htmlspecialchars($cat) ?></span>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <!-- Date Badge & Status Badge -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill small font-mono">
                                        <i class="bi bi-calendar3 me-1"></i> <?= htmlspecialchars($dBadge) ?>
                                    </span>
                                    <span class="badge-pill-custom <?= $bClass ?>">
                                        <?= htmlspecialchars($badgeText) ?>
                                    </span>
                                </div>

                                <h3 class="h5 fw-bold text-dark mb-2">
                                    <?= htmlspecialchars($evt['EventName']) ?>
                                </h3>

                                <div class="text-secondary small d-flex flex-column gap-1 mb-4">
                                    <div><i class="bi bi-clock me-1 text-primary"></i> <?= htmlspecialchars($timeStr) ?></div>
                                    <div class="text-truncate"><i class="bi bi-geo-alt me-1 text-primary"></i> <?= htmlspecialchars($locStr) ?></div>
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex justify-content-end">
                                <a href="<?= base_url('events/detail.php?id=' . (int)$evt['EventID']) ?>" class="btn btn-outline-primary btn-sm px-3">
                                    Xem chi tiết <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
