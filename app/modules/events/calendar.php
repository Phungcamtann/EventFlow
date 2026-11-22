<?php
/**
 * EventFlow — SCR07: Lịch Sự Kiện Tương Tác Notion Calendar
 * Đáp ứng: UAT 18 (Lịch Tháng Event Pills & Lịch Tuần Timeline 07:00-22:00 có vạch đỏ giờ thực)
 * Tương thích Dual-Portal: Hoạt động mượt mà ở cả Cổng công khai và Không gian Workspace BĐH
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';
require_once __DIR__ . '/../../includes/event_helpers.php';

$pageTitle = 'Lịch Sự Kiện — CLB BELL UEH';
require_once __DIR__ . '/../../includes/header.php';

$pdo = get_db_connection();
$now = event_now($pdo);

// Lấy toàn bộ sự kiện Published của CLB
$stmt = $pdo->prepare("
    SELECT e.EventID, e.EventName, e.Description, e.Location, e.StartTime, e.EndTime, 
           e.RegistrationStartTime, e.RegistrationDeadline, e.MaxCapacity, e.Status,
           c.ClubName,
           (SELECT COUNT(*) FROM Event_Registration r WHERE r.EventID = e.EventID AND r.Status = 'Registered') AS RegisteredCount
    FROM Event e
    LEFT JOIN Club c ON e.ClubID = c.ClubID
    WHERE e.Status = 'Published'
    ORDER BY e.StartTime ASC
");
$stmt->execute();
$events = $stmt->fetchAll();

// Xử lý dữ liệu sang mảng JSON cho Calendar Engine
$calendarEvents = [];
foreach ($events as $evt) {
    $state = event_state($evt, (int)$evt['RegisteredCount'], $now);
    
    // Gán Emoji & Nhãn theo chủ đề
    $titleUpper = mb_strtoupper($evt['EventName'], 'UTF-8');
    if (str_contains($titleUpper, 'SPEAK')) {
        $emoji = '🎤';
        $sub = 'Speaking Group';
        $color = '#dbeafe'; // soft blue
        $border = '#2563eb';
    } elseif (str_contains($titleUpper, 'DEBATE')) {
        $emoji = '⚖️';
        $sub = 'Debate Lab';
        $color = '#e0e7ff'; // soft indigo
        $border = '#4f46e5';
    } elseif (str_contains($titleUpper, 'WORKSHOP') || str_contains($titleUpper, 'IELTS')) {
        $emoji = '💡';
        $sub = 'Specialist Workshop';
        $color = '#fef3c7'; // soft amber
        $border = '#d97706';
    } elseif (str_contains($titleUpper, 'CULTURE') || str_contains($titleUpper, 'GALA')) {
        $emoji = '🎉';
        $sub = 'Culture & Gala';
        $color = '#ccfbf1'; // soft teal
        $border = '#0d9488';
    } else {
        $emoji = '📅';
        $sub = 'Hoạt động CLB';
        $color = '#f1f5f9';
        $border = '#475569';
    }

    $calendarEvents[] = [
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
?>

<div class="bg-ef-canvas py-4" style="min-height: 100vh;">
    <div class="container-fluid px-lg-5">

        <!-- Top Navigation / Breadcrumbs -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('app/modules/events/index.php') ?>" class="text-decoration-none text-muted">Sự kiện</a></li>
                    <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">Lịch Sự Kiện</li>
                </ol>
            </nav>
        </div>

        <!-- ==========================================
             NOTION CALENDAR CONTROL BAR
             ========================================== -->
        <div class="ef-card-editorial p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- Left: Month/Week Heading & Navigation -->
                <div class="d-flex align-items-center gap-3">
                    <h2 class="h4 fw-bold text-dark mb-0 font-monospace" id="calPeriodTitle">
                        November 2026
                    </h2>
                    <div class="btn-group shadow-2xs" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2.5" id="btnPrev" title="Kỳ trước">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" id="btnToday">
                            Today
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2.5" id="btnNext" title="Kỳ tiếp theo">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Right: Mode Switcher (Month vs Week) & Filters -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Toggle Month vs Week -->
                    <div class="btn-group p-1 bg-light rounded-pill border" role="group">
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold active" id="btnModeMonth" onclick="switchMode('month')">
                            <i class="bi bi-grid-3x3 me-1"></i> Lịch Tháng
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-secondary" id="btnModeWeek" onclick="switchMode('week')">
                            <i class="bi bi-calendar-week me-1"></i> Lịch Tuần
                        </button>
                    </div>

                    <!-- Category / Department Filter -->
                    <select id="calCategoryFilter" class="form-select form-select-sm rounded-pill" style="width: auto;">
                        <option value="ALL">Tất cả hoạt động</option>
                        <option value="SPEAK">🎤 Speaking Group</option>
                        <option value="DEBATE">⚖️ Debate Lab</option>
                        <option value="WORKSHOP">💡 Workshop</option>
                        <option value="CULTURE">🎉 Culture & Gala</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ==========================================
             VIEW 1: NOTION MONTH GRID VIEW (PILLS)
             ========================================== -->
        <div id="monthViewWrapper" class="ef-card-editorial p-0 overflow-hidden shadow-sm mb-5">
            <!-- 7 Column Weekday Header -->
            <div class="row g-0 text-center border-bottom bg-light fw-bold text-secondary font-monospace" style="font-size: 0.78rem;">
                <div class="col py-2.5 border-end">SUN</div>
                <div class="col py-2.5 border-end">MON</div>
                <div class="col py-2.5 border-end">TUE</div>
                <div class="col py-2.5 border-end">WED</div>
                <div class="col py-2.5 border-end">THU</div>
                <div class="col py-2.5 border-end">FRI</div>
                <div class="col py-2.5">SAT</div>
            </div>

            <!-- Month Calendar Grid Container (Generated by JS) -->
            <div id="monthGridContainer" class="month-grid">
                <!-- Dynamically rendered by JavaScript -->
            </div>
        </div>

        <!-- ==========================================
             VIEW 2: NOTION WEEK TIMELINE VIEW (07:00 – 22:00)
             ========================================== -->
        <div id="weekViewWrapper" class="ef-card-editorial p-0 overflow-hidden shadow-sm mb-5 d-none">
            <!-- 7 Column Day Header -->
            <div class="row g-0 text-center border-bottom bg-light fw-bold" id="weekDaysHeader" style="font-size: 0.82rem;">
                <!-- 1 Column for time gutter + 7 columns for Mon-Sun -->
            </div>

            <!-- Timeline Body Container -->
            <div class="week-timeline-container position-relative overflow-auto" style="height: 720px;">
                <div class="week-timeline-inner position-relative" style="min-width: 800px; height: 1050px;">
                    <!-- Hourly Grid Background Lines (07:00 to 22:00 = 15 hours * 70px) -->
                    <div id="weekGridLines"></div>

                    <!-- Red Line Indicator (Current Time Bar) -->
                    <div id="currentTimeIndicator" class="current-time-line d-none">
                        <span class="current-time-pill font-monospace" id="currentTimeLabel">10:15 AM</span>
                    </div>

                    <!-- Week Day Event Columns -->
                    <div class="row g-0 h-100 position-absolute top-0 start-0 w-100" id="weekColumnsContainer">
                        <!-- 8 columns: Time column + 7 day columns -->
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ==========================================
     QUICK EVENT PREVIEW MODAL (NOTION POPUP)
     ========================================== -->
<div class="modal fade" id="eventPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom py-3 px-4" id="modalHeaderBg">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4" id="modalEmoji">🎤</span>
                    <div>
                        <span class="badge rounded-pill font-monospace" id="modalSubBadge" style="font-size: 0.7rem;">Chuyên đề</span>
                        <span class="badge rounded-pill font-monospace" id="modalStateBadge" style="font-size: 0.7rem;">Đang mở đăng ký</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <h4 class="h5 fw-bold text-dark mb-3" id="modalTitle">Tiêu đề sự kiện</h4>
                <p class="text-secondary small mb-4 lh-base" id="modalDesc">Mô tả sự kiện tóm tắt...</p>

                <div class="p-3 rounded-3 bg-light border border-light small text-secondary d-flex flex-column gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-ef-primary"></i>
                        <span class="fw-semibold text-dark" id="modalDate">20/11/2026</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock text-ef-primary"></i>
                        <span id="modalTime">14:00 - 17:00</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt text-ef-primary"></i>
                        <span id="modalLocation">UEH Cơ sở B</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people text-ef-primary"></i>
                        <span>Đã đăng ký: <strong id="modalSeats">25 / 100</strong> sinh viên</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light py-2.5 px-4 d-flex justify-content-between">
                <span class="small text-muted font-monospace">BELL CLUB UEH</span>
                <a href="#" id="modalDetailLink" class="btn btn-sm btn-ef-primary">
                    Xem chi tiết & Đăng ký <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     CSS CHO NOTION CALENDAR (UAT 18)
     ========================================== -->
<style>
/* Month View Grid */
.month-grid-row {
    display: flex;
    border-bottom: 1px solid var(--ef-border, #e2e8f0);
}
.month-day-cell {
    flex: 1;
    min-height: 120px;
    border-right: 1px solid var(--ef-border, #e2e8f0);
    padding: 8px;
    position: relative;
    background-color: #ffffff;
    transition: background-color 0.15s ease;
}
.month-day-cell:last-child {
    border-right: none;
}
.month-day-cell.other-month {
    background-color: #f8fafc;
    opacity: 0.45;
}
.month-day-cell:hover:not(.other-month) {
    background-color: #f1f5f9;
}
.month-day-number {
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
}
.month-day-number.is-today {
    background-color: #2563eb;
    color: #ffffff !important;
    font-weight: 700;
}

/* Notion Event Pill */
.notion-event-pill {
    display: block;
    padding: 3px 6px;
    margin-bottom: 4px;
    border-radius: 6px;
    font-size: 0.72rem;
    line-height: 1.3;
    text-decoration: none;
    color: #0f172a;
    border-left: 3px solid #2563eb;
    background-color: #eff6ff;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    transition: all 0.15s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}
.notion-event-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(37, 99, 235, 0.15);
    color: #1e40af;
}
.notion-event-pill strong {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.notion-event-pill .pill-sub {
    font-size: 0.65rem;
    color: #64748b;
    display: block;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Week Timeline Grid */
.time-gutter-col {
    width: 65px;
    flex-shrink: 0;
    border-right: 1px solid var(--ef-border, #e2e8f0);
}
.week-day-col {
    flex: 1;
    position: relative;
    border-right: 1px solid var(--ef-border, #e2e8f0);
}
.week-day-col:last-child {
    border-right: none;
}
.hourly-row {
    height: 70px;
    border-bottom: 1px dashed rgba(226, 232, 240, 0.8);
    position: relative;
}
.time-label {
    font-size: 0.72rem;
    font-family: var(--ef-font-mono, monospace);
    color: #94a3b8;
    position: absolute;
    top: -9px;
    left: 8px;
}

/* Red Current Time Bar (UAT 18 Mockup) */
.current-time-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background-color: #ef4444;
    z-index: 10;
    pointer-events: none;
}
.current-time-pill {
    position: absolute;
    left: 4px;
    top: -10px;
    background-color: #ef4444;
    color: #ffffff;
    font-size: 0.65rem;
    padding: 1px 5px;
    border-radius: 4px;
    font-weight: 700;
}

/* Timeline Event Block */
.timeline-event-block {
    position: absolute;
    left: 4px;
    right: 4px;
    border-radius: 8px;
    padding: 6px 8px;
    font-size: 0.74rem;
    line-height: 1.25;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    border-left: 3px solid #2563eb;
    background: #eff6ff;
    transition: all 0.2s ease;
    z-index: 5;
}
.timeline-event-block:hover {
    transform: scale(1.02);
    z-index: 8;
    box-shadow: 0 6px 14px rgba(37, 99, 235, 0.2);
}
</style>

<!-- ==========================================
     EMBEDDED JSON DATA & NOTION ENGINE SCRIPT
     ========================================== -->
<script id="calendar-raw-data" type="application/json">
<?= json_encode($calendarEvents, JSON_UNESCAPED_UNICODE) ?>
</script>

<script>
// NOTION CALENDAR ENGINE
(function() {
    let rawEvents = [];
    try {
        const dataEl = document.getElementById('calendar-raw-data');
        if (dataEl && dataEl.textContent) {
            rawEvents = JSON.parse(dataEl.textContent);
        }
    } catch (e) {
        console.error('Lỗi phân tích dữ liệu lịch sự kiện:', e);
        rawEvents = [];
    }
    
    // Trạng thái ngày được chọn (mặc định lấy tháng 11/2026)
    let currentViewDate = new Date(2026, 10, 21); // Tháng 11 (0-indexed = 10)
    let currentMode = 'month'; // 'month' | 'week'
    let currentFilter = 'ALL';
    let previewModalInstance = null;

    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    // Chuyển đổi chế độ xem Lịch Tháng vs Lịch Tuần
    window.switchMode = function(mode) {
        currentMode = mode;
        const btnM = document.getElementById('btnModeMonth');
        const btnW = document.getElementById('btnModeWeek');
        const monthWrapper = document.getElementById('monthViewWrapper');
        const weekWrapper = document.getElementById('weekViewWrapper');

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
        renderCalendar();
    };

    // Khởi tạo các sự kiện điều hướng khi DOM sẵn sàng
    function initCalendarEvents() {
        renderCalendar();

        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const btnToday = document.getElementById('btnToday');
        const catFilter = document.getElementById('calCategoryFilter');

        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                if (currentMode === 'month') {
                    currentViewDate.setMonth(currentViewDate.getMonth() - 1);
                } else {
                    currentViewDate.setDate(currentViewDate.getDate() - 7);
                }
                renderCalendar();
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                if (currentMode === 'month') {
                    currentViewDate.setMonth(currentViewDate.getMonth() + 1);
                } else {
                    currentViewDate.setDate(currentViewDate.getDate() + 7);
                }
                renderCalendar();
            });
        }

        if (btnToday) {
            btnToday.addEventListener('click', () => {
                currentViewDate = new Date(2026, 10, 21);
                renderCalendar();
            });
        }

        if (catFilter) {
            catFilter.addEventListener('change', (e) => {
                currentFilter = e.target.value;
                renderCalendar();
            });
        }

        // Cập nhật vạch đỏ thời gian thực mỗi phút
        setInterval(updateRedTimeLine, 60000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendarEvents);
    } else {
        initCalendarEvents();
    }

    function filterEvents(events) {
        if (currentFilter === 'ALL') return events;
        return events.filter(e => {
            const upper = e.title.toUpperCase();
            if (currentFilter === 'DEBATE') return upper.includes('DEBATE');
            if (currentFilter === 'WORKSHOP') return upper.includes('WORKSHOP') || upper.includes('IELTS');
            if (currentFilter === 'CULTURE') return upper.includes('CULTURE') || upper.includes('GALA');
            if (currentFilter === 'SPEAK') return upper.includes('SPEAK');
            return true;
        });
    }

    function renderCalendar() {
        const filtered = filterEvents(rawEvents);
        if (currentMode === 'month') {
            renderMonthView(filtered);
        } else {
            renderWeekView(filtered);
        }
    }

    // ==========================================
    // 1. RENDER LỊCH THÁNG (MONTH VIEW)
    // ==========================================
    function renderMonthView(events) {
        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();
        document.getElementById('calPeriodTitle').textContent = `${monthNames[month]} ${year}`;

        const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 = Sun
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        const grid = document.getElementById('monthGridContainer');
        grid.innerHTML = '';

        const today = new Date();
        const isCurrentMonth = (today.getFullYear() === year && today.getMonth() === month);
        const todayDate = today.getDate();

        let dayCounter = 1;
        let nextMonthDay = 1;
        let totalCells = 35;
        if (firstDayOfMonth + daysInMonth > 35) {
            totalCells = 42;
        }

        let currentRow = null;
        for (let i = 0; i < totalCells; i++) {
            if (i % 7 === 0) {
                currentRow = document.createElement('div');
                currentRow.className = 'month-grid-row';
                grid.appendChild(currentRow);
            }

            const cell = document.createElement('div');
            cell.className = 'month-day-cell';

            let cellDate = null;
            let displayNum = '';
            let isOther = false;

            if (i < firstDayOfMonth) {
                // Ngày tháng trước
                displayNum = daysInPrevMonth - (firstDayOfMonth - i - 1);
                isOther = true;
                cell.classList.add('other-month');
            } else if (dayCounter <= daysInMonth) {
                // Ngày trong tháng
                displayNum = dayCounter;
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayCounter).padStart(2, '0')}`;
                cellDate = dateStr;

                const numSpan = document.createElement('div');
                numSpan.className = 'month-day-number';
                if (isCurrentMonth && dayCounter === todayDate) {
                    numSpan.classList.add('is-today');
                }
                numSpan.textContent = displayNum;
                cell.appendChild(numSpan);

                // Lọc sự kiện trong ngày
                const dayEvts = events.filter(e => e.startDate === dateStr);
                dayEvts.forEach(evt => {
                    const pill = document.createElement('div');
                    pill.className = 'notion-event-pill';
                    pill.style.backgroundColor = evt.bgColor;
                    pill.style.borderLeftColor = evt.borderColor;
                    pill.innerHTML = `<strong>${evt.emoji} ${evt.title}</strong><span class="pill-sub">${evt.startTime} · ${evt.subtitle}</span>`;
                    pill.addEventListener('click', () => openPreviewModal(evt));
                    cell.appendChild(pill);
                });

                dayCounter++;
            } else {
                // Ngày tháng sau
                displayNum = nextMonthDay++;
                isOther = true;
                cell.classList.add('other-month');
            }

            if (isOther) {
                const numSpan = document.createElement('div');
                numSpan.className = 'month-day-number text-muted';
                numSpan.textContent = displayNum;
                cell.appendChild(numSpan);
            }

            currentRow.appendChild(cell);
        }
    }

    // ==========================================
    // 2. RENDER LỊCH TUẦN (WEEK TIMELINE VIEW)
    // ==========================================
    function renderWeekView(events) {
        // Tìm ngày Thứ Hai của tuần hiện tại
        const d = new Date(currentViewDate);
        const dayOfWeek = d.getDay(); // 0: Sun, 1: Mon...
        const diffToMon = (dayOfWeek === 0 ? -6 : 1 - dayOfWeek);
        const monday = new Date(d);
        monday.setDate(d.getDate() + diffToMon);

        // 7 ngày trong tuần: Mon -> Sun
        const weekDates = [];
        for (let i = 0; i < 7; i++) {
            const nextD = new Date(monday);
            nextD.setDate(monday.getDate() + i);
            weekDates.push(nextD);
        }

        const startWeekStr = `${monthNames[weekDates[0].getMonth()]} ${weekDates[0].getDate()}`;
        const endWeekStr = `${monthNames[weekDates[6].getMonth()]} ${weekDates[6].getDate()}, ${weekDates[6].getFullYear()}`;
        document.getElementById('calPeriodTitle').textContent = `${startWeekStr} – ${endWeekStr}`;

        // Header 7 ngày
        const header = document.getElementById('weekDaysHeader');
        header.innerHTML = '<div class="time-gutter-col py-2 border-end text-muted font-monospace small">TIME</div>';

        const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        weekDates.forEach((wDate, idx) => {
            const dateStr = `${wDate.getFullYear()}-${String(wDate.getMonth() + 1).padStart(2, '0')}-${String(wDate.getDate()).padStart(2, '0')}`;
            const isToday = (dateStr === todayStr);

            const dayCol = document.createElement('div');
            dayCol.className = 'col py-2 border-end';
            if (isToday) dayCol.classList.add('bg-primary-subtle');

            dayCol.innerHTML = `
                <div class="text-uppercase text-secondary font-monospace" style="font-size: 0.7rem;">${dayNames[idx]}</div>
                <div class="fw-bold ${isToday ? 'text-primary fs-5' : 'text-dark fs-6'}">${wDate.getDate()}</div>
            `;
            header.appendChild(dayCol);
        });

        // Render Background Hourly Lines (07:00 – 22:00 = 15 tiếng)
        const linesContainer = document.getElementById('weekGridLines');
        linesContainer.innerHTML = '';
        for (let h = 7; h <= 21; h++) {
            const line = document.createElement('div');
            line.className = 'hourly-row';
            const displayHour = h < 12 ? `${h} AM` : (h === 12 ? '12 PM' : `${h - 12} PM`);
            line.innerHTML = `<span class="time-label">${displayHour}</span>`;
            linesContainer.appendChild(line);
        }

        // Render Event Columns
        const colsContainer = document.getElementById('weekColumnsContainer');
        colsContainer.innerHTML = '<div class="time-gutter-col h-100"></div>';

        const totalMinutes = (22 - 7) * 60; // 15 hours = 900 minutes

        weekDates.forEach((wDate, colIdx) => {
            const dateStr = `${wDate.getFullYear()}-${String(wDate.getMonth() + 1).padStart(2, '0')}-${String(wDate.getDate()).padStart(2, '0')}`;
            const col = document.createElement('div');
            col.className = 'week-day-col h-100';

            // Sự kiện trong ngày này
            const dayEvts = events.filter(e => e.startDate === dateStr);
            dayEvts.forEach(evt => {
                // Tính toán vị trí top & height
                const startParts = evt.startTime.split(':').map(Number);
                const endParts = evt.endTime.split(':').map(Number);

                const startMin = Math.max(0, (startParts[0] - 7) * 60 + startParts[1]);
                const endMin = Math.min(totalMinutes, (endParts[0] - 7) * 60 + endParts[1]);
                const duration = Math.max(35, endMin - startMin);

                const topPercent = (startMin / totalMinutes) * 100;
                const heightPercent = (duration / totalMinutes) * 100;

                const block = document.createElement('div');
                block.className = 'timeline-event-block';
                block.style.top = `${topPercent}%`;
                block.style.height = `${heightPercent}%`;
                block.style.backgroundColor = evt.bgColor;
                block.style.borderLeftColor = evt.borderColor;

                block.innerHTML = `
                    <div class="fw-bold text-dark text-truncate">${evt.emoji} ${evt.title}</div>
                    <div class="font-monospace text-muted" style="font-size: 0.65rem;">${evt.startTime} - ${evt.endTime}</div>
                    <div class="text-truncate text-secondary" style="font-size: 0.65rem;">${evt.location}</div>
                `;
                block.addEventListener('click', () => openPreviewModal(evt));
                col.appendChild(block);
            });

            colsContainer.appendChild(col);
        });

        updateRedTimeLine();
    }

    // ==========================================
    // 3. VẠCH ĐỎ THỜI GIAN THỰC (CURRENT TIME BAR)
    // ==========================================
    function updateRedTimeLine() {
        const ind = document.getElementById('currentTimeIndicator');
        if (!ind) return;

        const now = new Date();
        const currentHour = now.getHours();
        const currentMin = now.getMinutes();

        // Chỉ hiển thị từ 07:00 đến 22:00
        if (currentHour < 7 || currentHour >= 22) {
            ind.classList.add('d-none');
            return;
        }

        const totalMinutes = (22 - 7) * 60;
        const passedMinutes = (currentHour - 7) * 60 + currentMin;
        const topPercent = (passedMinutes / totalMinutes) * 100;

        ind.classList.remove('d-none');
        ind.style.top = `${topPercent}%`;

        // Định dạng giờ hiển thị
        const ampm = currentHour >= 12 ? 'PM' : 'AM';
        const formattedHour = currentHour % 12 || 12;
        const formattedMin = String(currentMin).padStart(2, '0');
        document.getElementById('currentTimeLabel').textContent = `${formattedHour}:${formattedMin} ${ampm}`;
    }

    // ==========================================
    // 4. MODAL XEM NHANH SỰ KIỆN
    // ==========================================
    function openPreviewModal(evt) {
        document.getElementById('modalEmoji').textContent = evt.emoji;
        document.getElementById('modalSubBadge').textContent = evt.subtitle;
        document.getElementById('modalSubBadge').style.backgroundColor = evt.bgColor;
        document.getElementById('modalSubBadge').style.color = evt.borderColor;

        const stateBadge = document.getElementById('modalStateBadge');
        stateBadge.textContent = evt.stateLabel;
        stateBadge.className = `badge rounded-pill font-monospace ${evt.stateClass}`;

        document.getElementById('modalTitle').textContent = evt.title;
        document.getElementById('modalDesc').textContent = evt.description || 'Chưa có mô tả chi tiết cho sự kiện này.';
        document.getElementById('modalDate').textContent = evt.startDate;
        document.getElementById('modalTime').textContent = `${evt.startTime} – ${evt.endTime}`;
        document.getElementById('modalLocation').textContent = evt.location;
        document.getElementById('modalSeats').textContent = `${evt.registered} / ${evt.capacity}`;

        const detailLink = document.getElementById('modalDetailLink');
        detailLink.href = `<?= base_url('app/modules/events/detail.php?id=') ?>${evt.id}`;

        const modalEl = document.getElementById('eventPreviewModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (!previewModalInstance) {
                previewModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            previewModalInstance.show();
        }
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
