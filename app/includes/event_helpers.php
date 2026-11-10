<?php
/**
 * EventFlow — Helper dùng chung cho module Sự kiện (SCR08, SCR09, SCR11, SCR12)
 *
 * Event.Status trong DB chỉ có 'Draft' | 'Published' | 'Cancelled'.
 * Trạng thái "Đang mở đăng ký" / "Đã đóng đăng ký" được SUY RA từ:
 *   Status + RegistrationDeadline + MaxCapacity + số lượt đăng ký 'Registered'.
 *
 * Mọi so sánh thời gian dùng chuỗi DATETIME 'Y-m-d H:i:s' và $now lấy từ MySQL (SELECT NOW()),
 * để không phụ thuộc múi giờ của PHP.
 */

// Ngưỡng hiển thị nhãn "thay đổi nổi bật" (có thể chỉnh theo yêu cầu BA)
const EVENT_LOW_SEATS_THRESHOLD = 5;    // còn <= 5 chỗ → "Chỉ còn N chỗ"
const EVENT_DEADLINE_SOON_HOURS = 48;   // còn <= 48 giờ tới hạn → "Sắp hết hạn đăng ký"

/**
 * Thời gian hiện tại theo đồng hồ MySQL ('Y-m-d H:i:s')
 */
function event_now(PDO $pdo): string {
    return (string)$pdo->query('SELECT NOW()')->fetchColumn();
}

/**
 * Suy ra trạng thái hiển thị của sự kiện.
 *
 * @param array  $evt        Dòng Event (cần: Status, RegistrationDeadline, MaxCapacity)
 * @param int    $registered Số lượt đăng ký có Status = 'Registered'
 * @param string $now        Kết quả event_now()
 * @return array{
 *   key:string, label:string, css_class:string, closed_reason:?string, is_open:bool,
 *   capacity:?int, registered:int, seats_left:?int, highlights:string[]
 * }
 */
function event_state(array $evt, int $registered, string $now): array {
    $capacity  = ($evt['MaxCapacity'] ?? null) === null ? null : (int)$evt['MaxCapacity'];
    $seatsLeft = $capacity === null ? null : max(0, $capacity - $registered);
    $status    = $evt['Status'] ?? '';

    $state = [
        'key'           => 'open',
        'label'         => 'Đang mở đăng ký',
        'css_class'     => 'badge-open',
        'closed_reason' => null,
        'is_open'       => false,
        'capacity'      => $capacity,
        'registered'    => $registered,
        'seats_left'    => $seatsLeft,
        'highlights'    => [],
    ];

    if ($status === 'Cancelled') {
        $state['key']           = 'cancelled';
        $state['label']         = 'Đã hủy';
        $state['css_class']     = 'badge-cancelled';
        $state['closed_reason'] = 'cancelled';
    } elseif ($status === 'Draft') {
        $state['key']           = 'draft';
        $state['label']         = 'Bản nháp';
        $state['css_class']     = 'badge-closed';
        $state['closed_reason'] = 'draft';
    } elseif ($now > $evt['RegistrationDeadline']) {
        $state['key']           = 'closed';
        $state['label']         = 'Đã đóng đăng ký';
        $state['css_class']     = 'badge-closed';
        $state['closed_reason'] = 'deadline';
    } elseif ($seatsLeft === 0) {
        $state['key']           = 'closed';
        $state['label']         = 'Đã đóng đăng ký';
        $state['css_class']     = 'badge-closed';
        $state['closed_reason'] = 'full';
    } else {
        $state['is_open'] = true;

        if ($seatsLeft !== null && $seatsLeft <= EVENT_LOW_SEATS_THRESHOLD) {
            $state['highlights'][] = "Chỉ còn {$seatsLeft} chỗ";
        }
        $hoursLeft = (strtotime($evt['RegistrationDeadline']) - strtotime($now)) / 3600;
        if ($hoursLeft <= EVENT_DEADLINE_SOON_HOURS) {
            $state['highlights'][] = 'Sắp hết hạn đăng ký';
        }
    }

    return $state;
}

/**
 * "12 / 40" hoặc "12 / Không giới hạn"
 */
function event_seats_text(array $state): string {
    $cap = $state['capacity'] === null ? 'Không giới hạn' : (string)$state['capacity'];
    return $state['registered'] . ' / ' . $cap;
}

/**
 * Định dạng DATETIME của MySQL sang 'dd/mm/yyyy HH:ii'
 */
function event_fmt_dt(?string $datetime): string {
    $ts = $datetime ? strtotime($datetime) : false;
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

/**
 * Lấy thông tin CLB mà tài khoản hiện tại quản lý.
 */
function event_manager_club(PDO $pdo, int $accountId): ?array {
    $stmt = $pdo->prepare("
        SELECT ClubID, ClubName
        FROM Club
        WHERE ManagerID = :accountId
        LIMIT 1
    ");
    $stmt->execute([':accountId' => $accountId]);
    $club = $stmt->fetch();

    return $club ?: null;
}
/**
 * Giá trị datetime-local ('Y-m-d\TH:i') → DATETIME MySQL. Trả về null nếu rỗng hoặc sai (vd 31/02).
 */
function event_input_to_dt(string $value): ?string {
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    $err = DateTime::getLastErrors();
    if (!$d || ($err !== false && ($err['warning_count'] > 0 || $err['error_count'] > 0))) {
        return null;
    }
    return $d->format('Y-m-d H:i:00');
}

/**
 * DATETIME MySQL → giá trị cho input datetime-local
 */
function event_dt_to_input(?string $datetime): string {
    $ts = $datetime ? strtotime($datetime) : false;
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

/**
 * Đếm ký tự UTF-8 (có mbstring thì dùng mb_strlen, không thì dùng regex)
 */
function event_strlen(string $value): int {
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : (int)preg_match_all('/./us', $value);
}