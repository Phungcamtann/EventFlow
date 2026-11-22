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
 * Suy ra trạng thái hiển thị của sự kiện theo thời gian thực (State Machine - UAT 19).
 *
 * 7 trạng thái chuẩn hóa:
 * 1. draft:        'Bản nháp'          (Status = 'Draft')
 * 2. cancelled:    'Đã hủy'            (Status = 'Cancelled')
 * 3. finished:     'Đã kết thúc'       (now >= EndTime)
 * 4. ongoing:      'Đang diễn ra'      (StartTime <= now < EndTime)
 * 5. closed:       'Đã đóng đăng ký'   (now > RegistrationDeadline HOẶC hết chỗ)
 * 6. upcoming:     'Sắp mở đăng ký'    (now < RegistrationStartTime)
 * 7. open:         'Đang mở đăng ký'   (đang trong giai đoạn nhận đăng ký)
 *
 * @param array  $evt        Dòng Event (cần: Status, StartTime, EndTime, RegistrationStartTime, RegistrationDeadline, MaxCapacity)
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

    $startTime = !empty($evt['StartTime']) ? $evt['StartTime'] : null;
    $endTime   = !empty($evt['EndTime']) ? $evt['EndTime'] : null;
    $regStart  = !empty($evt['RegistrationStartTime']) ? $evt['RegistrationStartTime'] : null;
    $deadline  = !empty($evt['RegistrationDeadline']) ? $evt['RegistrationDeadline'] : null;

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
        $state['css_class']     = 'badge-draft';
        $state['closed_reason'] = 'draft';
    } elseif ($endTime !== null && $now >= $endTime) {
        $state['key']           = 'finished';
        $state['label']         = 'Đã kết thúc';
        $state['css_class']     = 'badge-finished';
        $state['closed_reason'] = 'ended';
    } elseif ($startTime !== null && $now >= $startTime) {
        $state['key']           = 'ongoing';
        $state['label']         = 'Đang diễn ra';
        $state['css_class']     = 'badge-ongoing';
        $state['closed_reason'] = 'started';
    } elseif (($deadline !== null && $now > $deadline) || ($seatsLeft !== null && $seatsLeft === 0)) {
        $state['key']           = 'closed';
        $state['label']         = 'Đã đóng đăng ký';
        $state['css_class']     = 'badge-closed';
        $state['closed_reason'] = ($seatsLeft !== null && $seatsLeft === 0) ? 'full' : 'deadline';
    } elseif ($regStart !== null && $now < $regStart) {
        $state['key']           = 'upcoming';
        $state['label']         = 'Sắp mở đăng ký';
        $state['css_class']     = 'badge-upcoming';
        $state['closed_reason'] = 'upcoming';
    } else {
        $state['is_open'] = true;

        if ($seatsLeft !== null && $seatsLeft <= EVENT_LOW_SEATS_THRESHOLD) {
            $state['highlights'][] = "Chỉ còn {$seatsLeft} chỗ";
        }
        if ($deadline !== null) {
            $hoursLeft = (strtotime($deadline) - strtotime($now)) / 3600;
            if ($hoursLeft > 0 && $hoursLeft <= EVENT_DEADLINE_SOON_HOURS) {
                $state['highlights'][] = 'Sắp hết hạn đăng ký';
            }
        }
    }

    $state['state'] = $state['key'];

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

/**
 * Kiểm tra xem tài khoản có quyền mở bàn điểm danh / điều hành sự kiện không (UAT 08, 11 - RBAC Tầng 3).
 * Quyền điểm danh:
 * 1. System Admin
 * 2. Club Manager (President / Vice President của CLB sở hữu sự kiện)
 * 3. Hoặc cán bộ được chỉ định trong Event_Staff của sự kiện đó
 */
function can_user_checkin_event(PDO $pdo, int $eventId, int $accountId): bool {
    if (is_system_admin()) {
        return true;
    }

    $stmt = $pdo->prepare("
        SELECT e.ClubID, c.ManagerID, cm.MemberRole
        FROM Event e
        JOIN Club c ON e.ClubID = c.ClubID
        LEFT JOIN Club_Member cm ON cm.ClubID = e.ClubID AND cm.AccountID = :aid AND cm.Status = 'Active'
        WHERE e.EventID = :eid
        LIMIT 1
    ");
    $stmt->execute([':eid' => $eventId, ':aid' => $accountId]);
    $ev = $stmt->fetch();
    if (!$ev) {
        return false;
    }

    // Nếu là Club Manager chính thức hoặc President/Vice President trong Club_Member
    if ((int)$ev['ManagerID'] === $accountId || in_array($ev['MemberRole'], ['President', 'Vice President'], true)) {
        return true;
    }

    // Kiểm tra xem có nằm trong bảng Event_Staff của sự kiện này không
    $staffStmt = $pdo->prepare("
        SELECT EventStaffID, StaffRole 
        FROM Event_Staff 
        WHERE EventID = :eid AND AccountID = :aid
        LIMIT 1
    ");
    $staffStmt->execute([':eid' => $eventId, ':aid' => $accountId]);
    $staff = $staffStmt->fetch();

    return !empty($staff);
}

/**
 * Lấy vai trò cụ thể của cán bộ trong sự kiện (hoặc null nếu không phải staff)
 */
function get_user_event_staff_role(PDO $pdo, int $eventId, int $accountId): ?string {
    $staffStmt = $pdo->prepare("
        SELECT StaffRole 
        FROM Event_Staff 
        WHERE EventID = :eid AND AccountID = :aid
        LIMIT 1
    ");
    $staffStmt->execute([':eid' => $eventId, ':aid' => $accountId]);
    $role = $staffStmt->fetchColumn();
    return $role ?: null;
}

/**
 * Kiểm tra xem người dùng có phải là Trưởng Bàn Điểm Danh (Check-in Lead) của sự kiện không
 */
function is_user_checkin_lead(PDO $pdo, int $eventId, int $accountId): bool {
    if ($eventId <= 0 || $accountId <= 0) {
        return false;
    }
    $role = get_user_event_staff_role($pdo, $eventId, $accountId);
    if (!$role) {
        return false;
    }
    $norm = strtolower(str_replace([' ', '-', '_'], '', $role));
    return in_array($norm, ['checkinlead', 'checkin', 'truongbandiemdanh'], true);
}

/**
 * Danh sách EventID mà người dùng được phân công làm Check-in Lead
 */
function user_checkin_lead_event_ids(PDO $pdo, int $accountId): array {
    if ($accountId <= 0) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT DISTINCT e.EventID
        FROM Event_Staff es
        JOIN Event e ON e.EventID = es.EventID
        WHERE es.AccountID = :a
          AND LOWER(REPLACE(REPLACE(es.StaffRole, '-', ''), '_', '')) IN ('checkinlead', 'checkin', 'truongbandiemdanh')
    ");
    $stmt->execute([':a' => $accountId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Lấy danh sách ban tổ chức / cán bộ phụ trách sự kiện (Event_Staff)
 */
function get_event_staff_list(PDO $pdo, int $eventId): array {
    $stmt = $pdo->prepare("
        SELECT es.*, a.FullName, a.StudentCode, a.Email, a.Phone, a.Major, a.Cohort,
               d.DepartmentName, d.DepartmentCode, cm.MemberRole
        FROM Event_Staff es
        JOIN Account a ON a.AccountID = es.AccountID
        LEFT JOIN Event e ON e.EventID = es.EventID
        LEFT JOIN Club_Member cm ON cm.AccountID = a.AccountID AND cm.ClubID = e.ClubID
        LEFT JOIN Department d ON d.DepartmentID = cm.DepartmentID
        WHERE es.EventID = :eid
        ORDER BY CASE WHEN es.StaffRole = 'Organizer' THEN 1 
                      WHEN es.StaffRole = 'Checkin_Lead' THEN 2 
                      WHEN es.StaffRole = 'MC' THEN 3
                      WHEN es.StaffRole = 'Support' THEN 4
                      ELSE 5 END, 
                 a.FullName ASC
    ");
    $stmt->execute([':eid' => $eventId]);
    return $stmt->fetchAll();
}

/**
 * Chuẩn hóa tên tiếng Việt cho chức vụ / vai trò thành viên CLB & vai trò hệ thống
 */
if (!function_exists('format_club_member_role')) {
    function format_club_member_role(?string $role): string {
        if (empty($role)) {
            return 'Thành viên';
        }
        $r = trim($role);
        return match(strtolower($r)) {
            'president'                           => 'Chủ nhiệm CLB',
            'vice president', 'vice_president'    => 'Phó Chủ nhiệm',
            'head'                                => 'Trưởng phòng',
            'vice head', 'vice_head', 'vice-head' => 'Phó phòng',
            'board member', 'board_member'        => 'Ủy viên BĐH',
            'event supporter', 'event_supporter'  => 'CTV Sự kiện',
            'member'                              => 'Thành viên',
            'club manager', 'club_manager'        => 'Ban Chủ Nhiệm',
            'admin'                               => 'Quản trị viên',
            'student'                             => 'Sinh viên',
            default                               => htmlspecialchars($role),
        };
    }
}

/**
 * Điểm danh vãng lai (Walk-in Check-in)
 * Lưu thông tin người tham gia (kể cả chưa có tài khoản trong hệ thống) và ghi nhận điểm danh ngay lập tức.
 * 
 * @return array [bool $ok, string $msg, int $regId, ?array $attendeeInfo]
 */
function att_walkin_checkin(PDO $pdo, int $eventId, array $guestData, int $staffId): array {
    $startedTx = false;
    $rollback = static function (string $msg) use ($pdo, &$startedTx): array {
        if ($startedTx && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [false, $msg, 0, null];
    };

    $fullName = trim($guestData['fullname'] ?? '');
    $email = trim($guestData['email'] ?? '');
    $studentCode = trim($guestData['student_code'] ?? '');
    $phone = trim($guestData['phone'] ?? '');

    if ($fullName === '') {
        return [false, 'Vui lòng nhập họ và tên người tham dự.', 0, null];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Vui lòng nhập địa chỉ email hợp lệ (ví dụ: hoten@gmail.com hoặc mssv@st.ueh.edu.vn).', 0, null];
    }

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTx = true;
        }

        $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :e FOR UPDATE");
        $stmt->execute([':e' => $eventId]);
        $evt = $stmt->fetch();

        if (!$evt) {
            return $rollback('Không tìm thấy sự kiện.');
        }
        if ($evt['Status'] !== 'Published') {
            return $rollback('Sự kiện không ở trạng thái công bố.');
        }
        if (event_now($pdo) >= $evt['EndTime']) {
            return $rollback('Sự kiện đã kết thúc, không thể điểm danh.');
        }

        // 1. Kiểm tra xem người này có tài khoản sinh viên/thành viên trong hệ thống Account không
        if ($studentCode !== '') {
            $accStmt = $pdo->prepare("
                SELECT AccountID, FullName, StudentCode, Email, Phone 
                FROM Account 
                WHERE StudentCode = :code OR Email = :email 
                LIMIT 1
            ");
            $accStmt->execute([':code' => $studentCode, ':email' => $email]);
        } else {
            $accStmt = $pdo->prepare("
                SELECT AccountID, FullName, StudentCode, Email, Phone 
                FROM Account 
                WHERE Email = :email 
                LIMIT 1
            ");
            $accStmt->execute([':email' => $email]);
        }
        $targetUser = $accStmt->fetch();

        $targetAccountId = $targetUser ? (int)$targetUser['AccountID'] : null;
        $regType = $targetAccountId ? 'Member' : 'Guest';

        // 2. Tra cứu đăng ký hiện có của sự kiện này
        if ($targetAccountId) {
            $regStmt = $pdo->prepare("
                SELECT RegID, Status FROM Event_Registration
                WHERE EventID = :e AND AccountID = :a
                FOR UPDATE
            ");
            $regStmt->execute([':e' => $eventId, ':a' => $targetAccountId]);
        } else {
            if ($studentCode !== '') {
                $regStmt = $pdo->prepare("
                    SELECT RegID, Status FROM Event_Registration
                    WHERE EventID = :e AND (GuestStudentCode = :code OR GuestEmail = :email)
                    FOR UPDATE
                ");
                $regStmt->execute([':e' => $eventId, ':code' => $studentCode, ':email' => $email]);
            } else {
                $regStmt = $pdo->prepare("
                    SELECT RegID, Status FROM Event_Registration
                    WHERE EventID = :e AND GuestEmail = :email
                    FOR UPDATE
                ");
                $regStmt->execute([':e' => $eventId, ':email' => $email]);
            }
        }
        $reg = $regStmt->fetch();

        $regId = 0;
        if ($reg && $reg['Status'] === 'Registered') {
            $regId = (int)$reg['RegID'];
            $chk = $pdo->prepare("SELECT AttendanceID FROM Attendance WHERE RegID = :r LIMIT 1");
            $chk->execute([':r' => $regId]);
            if ($chk->fetch()) {
                return $rollback("Người tham dự [{$fullName}] đã được điểm danh trước đó rồi.");
            }
        } else {
            // Kiểm tra số lượng tối đa MaxCapacity
            $cntStmt = $pdo->prepare("
                SELECT COUNT(*) FROM Event_Registration
                WHERE EventID = :e AND Status = 'Registered'
            ");
            $cntStmt->execute([':e' => $eventId]);
            $registeredCount = (int)$cntStmt->fetchColumn();

            if ($evt['MaxCapacity'] !== null && (int)$evt['MaxCapacity'] > 0 && $registeredCount >= (int)$evt['MaxCapacity']) {
                return $rollback('Sự kiện đã đủ số lượng tham dự tối đa (' . $evt['MaxCapacity'] . ' chỗ). Không thể nhận thêm đăng ký vãng lai.');
            }

            if ($reg) {
                // Đã từng có đăng ký nhưng bị hủy: cập nhật lại thông tin và kích hoạt lại
                $up = $pdo->prepare("
                    UPDATE Event_Registration 
                    SET Status = 'Registered', 
                        RegistrationType = :regType,
                        GuestName = :name,
                        GuestEmail = :email,
                        GuestStudentCode = :code,
                        GuestPhone = :phone,
                        RegDate = NOW() 
                    WHERE RegID = :r
                ");
                $up->execute([
                    ':regType' => $regType,
                    ':name'    => $targetAccountId ? null : $fullName,
                    ':email'   => $targetAccountId ? null : $email,
                    ':code'    => $targetAccountId ? null : $studentCode,
                    ':phone'   => $targetAccountId ? null : $phone,
                    ':r'       => $reg['RegID']
                ]);
                $regId = (int)$reg['RegID'];
            } else {
                // Tạo mới dòng đăng ký
                $insReg = $pdo->prepare("
                    INSERT INTO Event_Registration (
                        EventID, AccountID, RegistrationType, GuestName, GuestEmail, GuestStudentCode, GuestPhone, RegDate, Status
                    ) VALUES (
                        :e, :a, :regType, :name, :email, :code, :phone, NOW(), 'Registered'
                    )
                ");
                $insReg->execute([
                    ':e'       => $eventId,
                    ':a'       => $targetAccountId,
                    ':regType' => $regType,
                    ':name'    => $targetAccountId ? null : $fullName,
                    ':email'   => $targetAccountId ? null : $email,
                    ':code'    => $targetAccountId ? null : $studentCode,
                    ':phone'   => $targetAccountId ? null : $phone
                ]);
                $regId = (int)$pdo->lastInsertId();
            }
        }

        // 3. Ghi nhận điểm danh vào bảng Attendance
        $insAtt = $pdo->prepare("
            INSERT INTO Attendance (RegID, AttendanceStatus, CheckInAt, CheckedInBy)
            VALUES (:r, 1, NOW(), :s)
            ON DUPLICATE KEY UPDATE AttendanceStatus = 1, CheckInAt = NOW(), CheckedInBy = :s2
        ");
        $insAtt->execute([':r' => $regId, ':s' => $staffId, ':s2' => $staffId]);

        if ($startedTx && $pdo->inTransaction()) {
            $pdo->commit();
        }

        $attendeeInfo = [
            'reg_id'        => $regId,
            'fullname'      => $fullName,
            'student_code'  => $studentCode,
            'email'         => $email,
            'phone'         => $phone,
            'is_guest'      => empty($targetAccountId),
            'reg_type'      => $regType,
            'is_checked'    => true,
            'checked_in_at' => date('H:i d/m')
        ];

        return [true, "Điểm danh vãng lai thành công cho [{$fullName}].", $regId, $attendeeInfo];
    } catch (Throwable $e) {
        return $rollback('Lỗi xử lý điểm danh vãng lai: ' . $e->getMessage());
    }
}

/**
 * Thống kê tổng số đăng ký và số lượng đã điểm danh của sự kiện
 */
if (!function_exists('att_event_stats')) {
    function att_event_stats(PDO $pdo, int $eventId): array {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS Total,
                SUM(CASE WHEN att.AttendanceID IS NOT NULL THEN 1 ELSE 0 END) AS Checked
            FROM Event_Registration r
            LEFT JOIN Attendance att ON att.RegID = r.RegID
            WHERE r.EventID = :e AND r.Status = 'Registered'
        ");
        $stmt->execute([':e' => $eventId]);
        $row = $stmt->fetch();
        return [
            'total'   => (int)($row['Total'] ?? 0),
            'checked' => (int)($row['Checked'] ?? 0)
        ];
    }
}
