<?php
/**
 * EventFlow — SCR01: Cổng Thông Tin Công Chúng & Trang Chủ CLB BELL UEH
 * Đáp ứng: UAT 02 (Công khai không cần login), UAT 18 (Mini dot calendar), UAT 19 (State Machine 7 trạng thái), UAT 20 (Banner Slider, 4 sự kiện Hot, Footer Fanpage)
 * Font: Inter/Roboto chuẩn hóa toàn hệ thống
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/includes/session_auth.php';
require_once __DIR__ . '/app/includes/event_helpers.php';

$pageTitle = 'Trang chủ — Cổng thông tin sự kiện CLB BELL UEH';
require_once __DIR__ . '/app/includes/header.php';

$pdo = get_db_connection();
$now = event_now($pdo);

// 1. Lấy tối đa 4 sự kiện nổi bật / sắp diễn ra (Published)
$hotEventsStmt = $pdo->prepare("
    SELECT e.*, c.ClubName,
           (SELECT COUNT(*) FROM Event_Registration r
             WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
    FROM Event e
    LEFT JOIN Club c ON e.ClubID = c.ClubID
    WHERE e.Status = 'Published'
    ORDER BY CASE WHEN e.EndTime >= :now THEN 0 ELSE 1 END,
             e.StartTime ASC
    LIMIT 4
");
$hotEventsStmt->execute([':now' => $now]);
$hotEvents = $hotEventsStmt->fetchAll();

// 2. Lấy dữ liệu sự kiện trong tháng hiện tại phục vụ Mini Dot Calendar Widget
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

// Gom nhóm các ngày có sự kiện: [ngày => danh sách sự kiện]
$eventDays = [];
foreach ($monthEvents as $me) {
    $d = (int)date('j', strtotime($me['StartTime']));
    if (!isset($eventDays[$d])) {
        $eventDays[$d] = [];
    }
    $eventDays[$d][] = $me;
}

// Tính toán ma trận ngày cho Mini Calendar
$firstDayOfWeek = (int)date('w', strtotime($monthStart)); // 0: Sun, 1: Mon...
$daysInMonth = (int)date('t', strtotime($monthStart));
$todayDay = ((int)date('Y') === $currentYear && (int)date('m') === $currentMonth) ? (int)date('j') : -1;
?>

<div class="bg-ef-canvas py-4" style="min-height: 100vh;">
    <div class="container">

        <!-- ==========================================
             1. HERO BANNER SLIDER
             ========================================== -->
        <div id="homeBannerCarousel" class="carousel slide carousel-fade mb-5 shadow-lg rounded-4 overflow-hidden" data-bs-ride="carousel" data-bs-interval="6000">
            <!-- Carousel Indicators -->
            <div class="carousel-indicators mb-3">
                <button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#homeBannerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>

            <!-- Carousel Slides -->
            <div class="carousel-inner">
                <!-- Slide 1: Welcome & BELL Club UEH -->
                <div class="carousel-item active position-relative">
                    <img src="<?= base_url('assets/images/banners/banner_slide_1.webp') ?>" class="d-block w-100 h-auto" alt="Business English for Learners and Lovers">
                    <div class="position-absolute d-flex gap-2 gap-md-3" style="left: 4.5%; bottom: 12%; z-index: 5;">
                        <a href="#hot-events" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 175px; height: 44px;" title="Khám phá Sự kiện Hot"></a>
                        <a href="<?= base_url('app/modules/club/info.php#departments') ?>" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 185px; height: 44px;" title="Giới thiệu 4 Phòng Ban"></a>
                    </div>
                </div>

                <!-- Slide 2: Hoạt động học thuật & Debate Lab -->
                <div class="carousel-item position-relative">
                    <img src="<?= base_url('assets/images/banners/banner_slide_2.webp') ?>" class="d-block w-100 h-auto" alt="Speaking Circle & Debate Lab">
                    <div class="position-absolute d-flex gap-2 gap-md-3" style="left: 4.5%; bottom: 12%; z-index: 5;">
                        <a href="<?= base_url('app/modules/events/calendar.php') ?>" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 160px; height: 44px;" title="Xem Lịch Sự Kiện"></a>
                        <a href="<?= base_url('app/modules/events/index.php') ?>" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 145px; height: 44px;" title="Tất cả sự kiện"></a>
                    </div>
                </div>

                <!-- Slide 3: EventFlow Platform -->
                <div class="carousel-item position-relative">
                    <img src="<?= base_url('assets/images/banners/banner_slide_3.webp') ?>" class="d-block w-100 h-auto" alt="Quản Lý Sự Kiện & Điểm Danh Realtime">
                    <div class="position-absolute d-flex gap-2 gap-md-3" style="left: 4.5%; bottom: 12%; z-index: 5;">
                        <?php if (is_logged_in()): ?>
                            <a href="<?= base_url('app/modules/dashboard/index.php') ?>" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 165px; height: 44px;" title="Vào Quản Lý CLB"></a>
                        <?php else: ?>
                            <a href="<?= base_url('app/modules/auth/login.php') ?>" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 165px; height: 44px;" title="Đăng nhập hệ thống"></a>
                        <?php endif; ?>
                        <a href="<?= base_url('app/modules/club/info.php') ?>" class="btn btn-transparent p-0 d-inline-block rounded-pill" style="width: 150px; height: 44px;" title="Giới thiệu CLB"></a>
                    </div>
                </div>
            </div>

            <!-- Carousel Controls (Đưa xuống góc dưới bên phải tinh tế, không che lấp chữ) -->
            <div class="position-absolute bottom-0 end-0 m-4 d-flex gap-2" style="z-index: 10;">
                <button class="btn btn-dark btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm" type="button" data-bs-target="#homeBannerCarousel" data-bs-slide="prev" style="width: 38px; height: 38px; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(255, 255, 255, 0.25);" aria-label="Previous slide">
                    <i class="bi bi-chevron-left text-white fs-6"></i>
                </button>
                <button class="btn btn-dark btn-sm rounded-circle d-flex align-items-center justify-content-center shadow-sm" type="button" data-bs-target="#homeBannerCarousel" data-bs-slide="next" style="width: 38px; height: 38px; background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(255, 255, 255, 0.25);" aria-label="Next slide">
                    <i class="bi bi-chevron-right text-white fs-6"></i>
                </button>
            </div>
        </div>


        <!-- ==========================================
             2. KHỐI SỰ KIỆN HOT (NỔI BẬT & SẮP DIỄN RA)
             ========================================== -->
        <div id="hot-events" class="mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">
                        HOT & UPCOMING EVENTS
                    </span>
                    <h2 class="h3 fw-bold text-dark mb-0 mt-1">
                        Sự Kiện Nổi Bật & Sắp Diễn Ra
                    </h2>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= base_url('app/modules/events/calendar.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="bi bi-calendar3 me-1"></i> Xem Lịch Sự Kiện
                    </a>
                    <a href="<?= base_url('app/modules/events/index.php') ?>" class="btn btn-ef-outline btn-sm">
                        Xem tất cả sự kiện <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <?php if (empty($hotEvents)): ?>
                <div class="ef-card-editorial p-5 text-center text-muted">
                    <i class="bi bi-calendar-x fs-1 text-secondary mb-3 d-block"></i>
                    <p class="mb-0">Hiện chưa có sự kiện nào được công bố.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php 
                    $catColors = [
                        'SPEAK'   => ['tag' => 'SPEAKING', 'badge' => 'badge-ef-blue'],
                        'DEBATE'  => ['tag' => 'DEBATE',   'badge' => 'badge-ef-indigo'],
                        'WORKSHOP'=> ['tag' => 'WORKSHOP', 'badge' => 'badge-ef-sky'],
                        'CULTURE' => ['tag' => 'CULTURE',  'badge' => 'badge-ef-teal'],
                    ];

                    foreach ($hotEvents as $idx => $evt): 
                        // Phân loại danh mục
                        $titleUpper = mb_strtoupper($evt['EventName'], 'UTF-8');
                        if (str_contains($titleUpper, 'DEBATE')) $cat = 'DEBATE';
                        elseif (str_contains($titleUpper, 'WORKSHOP') || str_contains($titleUpper, 'IELTS')) $cat = 'WORKSHOP';
                        elseif (str_contains($titleUpper, 'CULTURE') || str_contains($titleUpper, 'GALA')) $cat = 'CULTURE';
                        else $cat = 'SPEAK';

                        $catStyle = $catColors[$cat];

                        // Phân tích trạng thái theo State Machine (UAT 19)
                        $state = event_state($evt, (int)$evt['RegisteredCount'], $now);
                        $dBadge = date('d/m/Y', strtotime($evt['StartTime']));
                        $timeStr = date('H:i', strtotime($evt['StartTime'])) . ' - ' . date('H:i', strtotime($evt['EndTime']));
                        $seatsText = event_seats_text($state);
                    ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="ef-card-editorial h-100 overflow-hidden d-flex flex-column justify-content-between position-relative">
                                <div>
                                    <!-- Banner Thumbnail Header -->
                                    <div class="position-relative overflow-hidden" style="height: 140px; background: linear-gradient(135deg, #1e3a8a, #0b192c);">
                                        <?php if (!empty($evt['BannerImage']) && file_exists(__DIR__ . '/' . $evt['BannerImage'])): ?>
                                            <img src="<?= base_url($evt['BannerImage']) ?>" alt="<?= htmlspecialchars($evt['EventName']) ?>" class="w-100 h-100 object-fit-cover">
                                        <?php else: ?>
                                            <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white" style="background: radial-gradient(circle at 80% 20%, rgba(37,99,235,0.45) 0%, rgba(15,23,42,0.95) 100%);">
                                                <i class="bi <?= $cat === 'DEBATE' ? 'bi-chat-square-quote-fill' : ($cat === 'WORKSHOP' ? 'bi-mortarboard-fill' : ($cat === 'CULTURE' ? 'bi-stars' : 'bi-mic-fill')) ?> fs-2 text-white-50 mb-1"></i>
                                                <span class="font-monospace text-white-50" style="font-size: 0.65rem; letter-spacing: 0.08em;"><?= $catStyle['tag'] ?> · BELL</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge-pill-custom <?= $state['css_class'] ?>" style="font-size: 0.65rem; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                                                <?= htmlspecialchars($state['label']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <!-- Category Tag -->
                                        <div class="mb-2">
                                            <span class="badge <?= $catStyle['badge'] ?> rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.68rem;">
                                                <?= $catStyle['tag'] ?>
                                            </span>
                                        </div>

                                        <!-- Event Title -->
                                        <h3 class="h6 fw-bold text-dark mb-2 lh-base" style="min-height: 44px;">
                                            <?= htmlspecialchars($evt['EventName']) ?>
                                        </h3>

                                        <!-- Meta Info -->
                                        <div class="small text-secondary d-flex flex-column gap-2 mb-0">
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
                                                <div class="d-flex align-items-center font-monospace" style="font-size: 0.75rem;">
                                                    <i class="bi bi-people text-ef-primary me-2"></i>
                                                    <span>Đã đăng ký: <strong><?= htmlspecialchars($seatsText) ?></strong></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <div class="px-4 pb-4 pt-0">
                                    <div class="pt-3 border-top border-light">
                                        <a href="<?= base_url('app/modules/events/detail.php?id=' . (int)$evt['EventID']) ?>" class="btn btn-sm btn-outline-primary w-100 rounded-pill fw-semibold">
                                            Chi tiết & Đăng ký <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- ==========================================
             3. KHỐI LIÊN KẾT: MINI DOT CALENDAR & CLB
             ========================================== -->
        <div class="row g-4 mb-5">
            <!-- Col Left: Giới thiệu CLB & 4 Phòng Ban Teaser -->
            <div class="col-lg-7">
                <div class="ef-card-editorial p-4 p-md-5 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-uppercase font-monospace fw-bold small text-ef-primary" style="letter-spacing: 0.12em;">
                            FOUR DEPARTMENTS · ONE BELL FAMILY
                        </span>
                        <h2 class="h3 fw-bold text-dark mt-1 mb-3">
                            Cơ Cấu 4 Phòng Ban Chuyên Môn
                        </h2>
                        <p class="text-secondary small mb-4 lh-base">
                            Mỗi ban đảm nhận một sứ mệnh đặc thù, cùng nhau xây dựng nên môi trường học thuật tiếng Anh năng động và gắn kết hàng đầu tại UEH:
                        </p>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 border bg-light h-100" style="border-left: 3px solid #2563eb !important;">
                                    <div class="fw-bold text-dark small mb-1">Phòng Nhân sự (HR)</div>
                                    <div class="text-muted small" style="font-size: 0.8rem;">Gắn kết nội bộ, tuyển quân và chăm sóc toàn diện thành viên.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 border bg-light h-100" style="border-left: 3px solid #4f46e5 !important;">
                                    <div class="fw-bold text-dark small mb-1">Phòng Chuyên môn (SPEC)</div>
                                    <div class="text-muted small" style="font-size: 0.8rem;">Học thuật, Speaking Group, Debate Lab và nâng tầm tiếng Anh.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 border bg-light h-100" style="border-left: 3px solid #0284c7 !important;">
                                    <div class="fw-bold text-dark small mb-1">Phòng Truyền thông (COMS)</div>
                                    <div class="text-muted small" style="font-size: 0.8rem;">Sáng tạo nội dung số, ấn phẩm hình ảnh và câu chuyện thương hiệu.</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 border bg-light h-100" style="border-left: 3px solid #0d9488 !important;">
                                    <div class="fw-bold text-dark small mb-1">Phòng Đối ngoại (ER)</div>
                                    <div class="text-muted small" style="font-size: 0.8rem;">Kết nối nhà tài trợ, diễn giả chuyên gia và đối tác doanh nghiệp.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <a href="<?= base_url('app/modules/club/info.php') ?>" class="btn btn-ef-primary btn-sm">
                            <i class="bi bi-people-fill"></i> Xem Đội Ngũ Cán Bộ & Ban Điều Hành
                        </a>
                        <a href="<?= base_url('app/modules/club/info.php#spirit') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                            Tinh thần B.E.L.L
                        </a>
                    </div>
                </div>
            </div>

            <!-- Col Right: Mini Dot Calendar Widget -->
            <div class="col-lg-5">
                <div class="ef-card-editorial p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Header Calendar Widget -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="badge badge-ef-blue rounded-pill px-2.5 py-1 font-monospace" style="font-size: 0.7rem;">
                                    LỊCH THÁNG <?= date('m/Y') ?>
                                </span>
                                <h3 class="h5 fw-bold text-dark mt-1 mb-0">Lịch Hoạt Động CLB</h3>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="mini-calendar border rounded-3 p-3 bg-white mb-3">
                            <!-- Weekday Headers -->
                            <div class="row g-0 text-center text-muted font-monospace small mb-2 fw-semibold" style="font-size: 0.72rem;">
                                <div class="col">CN</div>
                                <div class="col">T2</div>
                                <div class="col">T3</div>
                                <div class="col">T4</div>
                                <div class="col">T5</div>
                                <div class="col">T6</div>
                                <div class="col">T7</div>
                            </div>

                            <!-- Days Grid -->
                            <div class="row g-0 text-center" style="font-size: 0.8rem;">
                                <?php
                                // Ô trống đầu tháng
                                for ($empty = 0; $empty < $firstDayOfWeek; $empty++):
                                ?>
                                    <div class="col py-1 text-muted opacity-25">·</div>
                                <?php endfor; ?>

                                <?php
                                // Các ngày trong tháng
                                $colCount = $firstDayOfWeek;
                                for ($day = 1; $day <= $daysInMonth; $day++):
                                    $hasEvents = isset($eventDays[$day]);
                                    $isToday = ($day === $todayDay);
                                    
                                    // Bắt đầu dòng mới nếu đã đủ 7 cột
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
                                            <!-- Chấm tròn màu xanh báo hiệu ngày có sự kiện (Mini Dot Calendar) -->
                                            <div class="d-flex justify-content-center mt-0.5">
                                                <span class="rounded-circle" style="width: 5px; height: 5px; background-color: #2563eb;" title="<?= count($eventDays[$day]) ?> sự kiện"></span>
                                            </div>
                                        <?php else: ?>
                                            <div style="height: 5px;" class="mt-0.5"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>

                                <?php
                                // Ô trống cuối tháng cho chẵn dòng
                                while ($colCount % 7 !== 0):
                                    $colCount++;
                                ?>
                                    <div class="col py-1 text-muted opacity-25">·</div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- Gợi ý sự kiện trong tháng -->
                        <div class="small text-muted font-monospace d-flex align-items-center justify-content-between mb-2">
                            <span><span class="d-inline-block rounded-circle me-1" style="width: 6px; height: 6px; background-color: #2563eb;"></span> Ngày có sự kiện</span>
                            <span><?= count($monthEvents) ?> sự kiện tháng này</span>
                        </div>
                    </div>

                    <a href="<?= base_url('app/modules/events/calendar.php') ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                        <i class="bi bi-calendar3 me-1.5"></i> Xem lịch sự kiện
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/app/includes/footer.php'; ?>
