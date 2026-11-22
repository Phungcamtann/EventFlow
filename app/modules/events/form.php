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

$eventId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditMode = $eventId > 0;
$evt        = null;
$registered = 0;

if ($isEditMode) {
    $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id AND ClubID = :c LIMIT 1");
    $stmt->execute([':id' => $eventId, ':c' => $clubId]);
    $evt = $stmt->fetch();

    if (!$evt) {
        set_flash('danger', 'Không tìm thấy sự kiện cần chỉnh sửa.');
        header('Location: ' . $manageUrl);
        exit;
    }
    if ($evt['Status'] === 'Cancelled') {
        set_flash('warning', 'Sự kiện đã bị hủy nên không thể chỉnh sửa.');
        header('Location: ' . $manageUrl);
        exit;
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM Event_Registration WHERE EventID = :e AND Status = 'Registered'");
    $cnt->execute([':e' => $eventId]);
    $registered = (int)$cnt->fetchColumn();
}

// Lấy danh sách cán bộ BĐH để phân công vào Event_Staff (UAT 10)
$staffStmt = $pdo->prepare("
    SELECT cm.AccountID, cm.MemberRole, a.FullName, a.StudentCode, d.DepartmentName, d.DepartmentCode
    FROM Club_Member cm
    JOIN Account a ON a.AccountID = cm.AccountID
    LEFT JOIN Department d ON d.DepartmentID = cm.DepartmentID
    WHERE cm.ClubID = :c AND cm.Status = 'Active'
    ORDER BY CASE WHEN cm.MemberRole = 'President' THEN 1 
                  WHEN cm.MemberRole = 'Vice President' THEN 2 
                  WHEN cm.MemberRole = 'Head' THEN 3 
                  WHEN cm.MemberRole = 'Vice Head' THEN 4 
                  ELSE 5 END, a.FullName ASC
");
$staffStmt->execute([':c' => $clubId]);
$availableStaff = $staffStmt->fetchAll();

$currentStaffIds = [];
if ($isEditMode) {
    $csStmt = $pdo->prepare("SELECT AccountID FROM Event_Staff WHERE EventID = :e");
    $csStmt->execute([':e' => $eventId]);
    $currentStaffIds = array_map('intval', $csStmt->fetchAll(PDO::FETCH_COLUMN));
} else {
    $currentStaffIds = [(int)current_user_id()];
}

// Sự kiện đã công bố thì không đưa ngược về bản nháp (tránh ẩn sự kiện đã có người đăng ký)
$canChooseStatus = !$isEditMode || $evt['Status'] === 'Draft';

$errors = [];
$form = [
    'EventName'            => $isEditMode ? $evt['EventName'] : '',
    'Description'          => $isEditMode ? (string)$evt['Description'] : '',
    'Location'             => $isEditMode ? $evt['Location'] : '',
    'StartTime'             => $isEditMode ? event_dt_to_input($evt['StartTime']) : '',
    'EndTime'               => $isEditMode ? event_dt_to_input($evt['EndTime']) : '',
    'RegistrationStartTime' => ($isEditMode && !empty($evt['RegistrationStartTime'])) ? event_dt_to_input($evt['RegistrationStartTime']) : '',
    'RegistrationDeadline'  => $isEditMode ? event_dt_to_input($evt['RegistrationDeadline']) : '',
    'MaxCapacity'           => ($isEditMode && $evt['MaxCapacity'] !== null) ? (string)$evt['MaxCapacity'] : '',
    'Status'                => $isEditMode ? $evt['Status'] : 'Published',
];

// ---------------------------------------------------------------------------
// Xử lý POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['EventName']             = trim($_POST['event_name'] ?? '');
    $form['Description']           = trim($_POST['description'] ?? '');
    $form['Location']              = trim($_POST['location'] ?? '');
    $form['StartTime']             = trim($_POST['start_time'] ?? '');
    $form['EndTime']               = trim($_POST['end_time'] ?? '');
    $form['RegistrationStartTime'] = trim($_POST['registration_start_time'] ?? '');
    $form['RegistrationDeadline']  = trim($_POST['registration_deadline'] ?? '');
    $form['MaxCapacity']           = trim($_POST['max_capacity'] ?? '');
    if ($canChooseStatus) {
        $form['Status'] = trim($_POST['status'] ?? 'Published');
    }

    if (!verify_csrf_token()) {
        $errors[] = 'Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        // --- Trường bắt buộc ---
        if ($form['EventName'] === '') {
            $errors[] = 'Vui lòng nhập tên sự kiện.';
        } elseif (event_strlen($form['EventName']) > 200) {
            $errors[] = 'Tên sự kiện tối đa 200 ký tự.';
        }

        if ($form['Location'] === '') {
            $errors[] = 'Vui lòng nhập địa điểm.';
        } elseif (event_strlen($form['Location']) > 255) {
            $errors[] = 'Địa điểm tối đa 255 ký tự.';
        }

        if (event_strlen($form['Description']) > 5000) {
            $errors[] = 'Mô tả tối đa 5000 ký tự.';
        }

        $start    = $form['StartTime'] === '' ? null : event_input_to_dt($form['StartTime']);
        $end      = $form['EndTime'] === '' ? null : event_input_to_dt($form['EndTime']);
        $regStart = $form['RegistrationStartTime'] === '' ? null : event_input_to_dt($form['RegistrationStartTime']);
        $deadline = $form['RegistrationDeadline'] === '' ? null : event_input_to_dt($form['RegistrationDeadline']);

        if ($form['StartTime'] === '') {
            $errors[] = 'Vui lòng chọn thời gian bắt đầu.';
        } elseif ($start === null) {
            $errors[] = 'Thời gian bắt đầu không hợp lệ.';
        }
        if ($form['EndTime'] === '') {
            $errors[] = 'Vui lòng chọn thời gian kết thúc.';
        } elseif ($end === null) {
            $errors[] = 'Thời gian kết thúc không hợp lệ.';
        }
        if ($form['RegistrationDeadline'] === '') {
            $errors[] = 'Vui lòng chọn hạn chót đăng ký.';
        } elseif ($deadline === null) {
            $errors[] = 'Hạn chót đăng ký không hợp lệ.';
        }

        // --- Ràng buộc thời gian ---
        if ($form['RegistrationStartTime'] !== '' && $regStart === null) {
            $errors[] = 'Thời gian mở đăng ký không hợp lệ.';
        }
        if ($regStart !== null && $deadline !== null && $regStart >= $deadline) {
            $errors[] = 'Thời gian mở đăng ký phải diễn ra trước hạn chót đăng ký.';
        }
        if ($regStart !== null && $start !== null && $regStart >= $start) {
            $errors[] = 'Thời gian mở đăng ký phải diễn ra trước thời gian bắt đầu sự kiện.';
        }
        if ($start !== null && $end !== null && $end <= $start) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        }
        if ($start !== null && $deadline !== null && $deadline > $start) {
            $errors[] = 'Hạn đăng ký phải nhỏ hơn hoặc bằng thời gian bắt đầu sự kiện.';
        }

        // --- Số chỗ: để trống = không giới hạn ---
        $capacity = null;
        if ($form['MaxCapacity'] !== '') {
            $capacity = filter_var($form['MaxCapacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
            if ($capacity === false) {
                $errors[] = 'Số chỗ tối đa phải là số nguyên từ 1 đến 100000 (hoặc để trống nếu không giới hạn).';
                $capacity = null;
            }
        }

        if (!in_array($form['Status'], ['Draft', 'Published'], true)) {
            $errors[] = 'Trạng thái sự kiện không hợp lệ.';
        }

        // --- Kiểm tra phân công tối thiểu 2 cán bộ BĐH (UAT 10) ---
        $staffIds = array_map('intval', $_POST['staff_ids'] ?? []);
        if (!$isEditMode && count($staffIds) < 2) {
            $errors[] = 'Vui lòng phân công tối thiểu 2 cán bộ Ban Điều Hành phụ trách sự kiện.';
        } elseif ($isEditMode && !empty($_POST['staff_ids']) && count($staffIds) < 2) {
            $errors[] = 'Sự kiện phải có tối thiểu 2 cán bộ Ban Điều Hành phụ trách.';
        }

        // --- Xử lý tải lên Banner sự kiện ---
        $bannerPath = $isEditMode ? ($evt['BannerImage'] ?? null) : null;
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['banner_image']['tmp_name'];
            $fileName = $_FILES['banner_image']['name'];
            $fileSize = $_FILES['banner_image']['size'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'Định dạng hình ảnh banner không hợp lệ (chỉ chấp nhận JPG, JPEG, PNG, WEBP).';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'Dung lượng ảnh banner không được vượt quá 5MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($fileTmp);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($mime, $allowedMimes, true)) {
                    $errors[] = 'Tệp tải lên không phải là định dạng hình ảnh hợp lệ.';
                } else {
                    $uploadDir = __DIR__ . '/../../../assets/uploads/events/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmp, $destPath)) {
                        $bannerPath = 'assets/uploads/events/' . $newFileName;
                    } else {
                        $errors[] = 'Không thể lưu hình ảnh banner đã tải lên. Vui lòng thử lại.';
                    }
                }
            }
        }

        // --- Lưu ---
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                if ($isEditMode) {
                    // Khóa dòng Event để không lệch với các lượt đăng ký đang diễn ra
                    $lock = $pdo->prepare("SELECT Status FROM Event WHERE EventID = :id AND ClubID = :c FOR UPDATE");
                    $lock->execute([':id' => $eventId, ':c' => $clubId]);
                    $cur = $lock->fetch();

                    if (!$cur || $cur['Status'] === 'Cancelled') {
                        $pdo->rollBack();
                        set_flash('warning', 'Sự kiện không còn khả dụng để chỉnh sửa.');
                        header('Location: ' . $manageUrl);
                        exit;
                    }

                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM Event_Registration WHERE EventID = :e AND Status = 'Registered'");
                    $cnt->execute([':e' => $eventId]);
                    $registered = (int)$cnt->fetchColumn();

                    if ($capacity !== null && $capacity < $registered) {
                        $pdo->rollBack();
                        $errors[] = "Số chỗ tối đa không được nhỏ hơn số người đã đăng ký hiện tại ({$registered}).";
                    } else {
                        $up = $pdo->prepare("
                            UPDATE Event
                            SET EventName = :name, Description = :descr, Location = :loc,
                                StartTime = :st, EndTime = :et, RegistrationStartTime = :rst, RegistrationDeadline = :dl,
                                MaxCapacity = :cap, Status = :status, BannerImage = :banner
                            WHERE EventID = :id AND ClubID = :c
                        ");
                        $up->execute([
                            ':name'   => $form['EventName'],
                            ':descr'  => $form['Description'] === '' ? null : $form['Description'],
                            ':loc'    => $form['Location'],
                            ':st'     => $start,
                            ':et'     => $end,
                            ':rst'    => $regStart,
                            ':dl'     => $deadline,
                            ':cap'    => $capacity,
                            ':status' => $form['Status'],
                            ':banner' => $bannerPath,
                            ':id'     => $eventId,
                            ':c'      => $clubId,
                        ]);

                        // Cập nhật Event_Staff nếu có thay đổi
                        if (!empty($staffIds)) {
                            $delStaff = $pdo->prepare("DELETE FROM Event_Staff WHERE EventID = :eid");
                            $delStaff->execute([':eid' => $eventId]);

                            $insStaff = $pdo->prepare("
                                INSERT INTO Event_Staff (EventID, AccountID, StaffRole, AssignedBy, AssignedAt)
                                VALUES (:eid, :aid, :role, :by, NOW())
                            ");
                            $roleMap = ['Organizer', 'Checkin_Lead', 'Support', 'MC', 'Logistics'];
                            $idx = 0;
                            foreach ($staffIds as $aid) {
                                $r = $roleMap[$idx] ?? 'Support';
                                $insStaff->execute([
                                    ':eid'  => $eventId,
                                    ':aid'  => $aid,
                                    ':role' => $r,
                                    ':by'   => (int)current_user_id()
                                ]);
                                $idx++;
                            }
                        }

                        $pdo->commit();
                        set_flash('success', 'Cập nhật sự kiện thành công!');
                        header('Location: ' . base_url('app/modules/events/console.php?id=' . $eventId));
                        exit;
                    }
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO Event
                            (ClubID, EventName, Description, Location, StartTime, EndTime, RegistrationStartTime, RegistrationDeadline, MaxCapacity, Status, BannerImage)
                        VALUES (:c, :name, :descr, :loc, :st, :et, :rst, :dl, :cap, :status, :banner)
                    ");
                    $ins->execute([
                        ':c'      => $clubId,
                        ':name'   => $form['EventName'],
                        ':descr'  => $form['Description'] === '' ? null : $form['Description'],
                        ':loc'    => $form['Location'],
                        ':st'     => $start,
                        ':et'     => $end,
                        ':rst'    => $regStart,
                        ':dl'     => $deadline,
                        ':cap'    => $capacity,
                        ':status' => $form['Status'],
                        ':banner' => $bannerPath,
                    ]);
                    $newEventId = (int)$pdo->lastInsertId();

                    // Lưu Event_Staff cho sự kiện mới (tối thiểu 2 cán bộ BĐH - UAT 10)
                    $insStaff = $pdo->prepare("
                        INSERT INTO Event_Staff (EventID, AccountID, StaffRole, AssignedBy, AssignedAt)
                        VALUES (:eid, :aid, :role, :by, NOW())
                    ");
                    $roleMap = ['Organizer', 'Checkin_Lead', 'Support', 'MC', 'Logistics'];
                    $idx = 0;
                    foreach ($staffIds as $aid) {
                        $r = $roleMap[$idx] ?? 'Support';
                        $insStaff->execute([
                            ':eid'  => $newEventId,
                            ':aid'  => $aid,
                            ':role' => $r,
                            ':by'   => (int)current_user_id()
                        ]);
                        $idx++;
                    }

                    $pdo->commit();
                    set_flash('success', 'Tạo sự kiện và phân công cán bộ thành công!');
                    header('Location: ' . base_url('app/modules/events/console.php?id=' . $newEventId));
                    exit;
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[EventFlow] save event failed: ' . $e->getMessage());
                $errors[] = 'Không thể lưu sự kiện. Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = ($isEditMode ? 'Chỉnh sửa sự kiện' : 'Tạo sự kiện mới') . ' — EventFlow';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= $h($manageUrl) ?>" class="text-decoration-none text-muted">Quản lý sự kiện</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                <?= $isEditMode ? 'Chỉnh sửa sự kiện' : 'Tạo sự kiện mới' ?>
            </li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom border-light p-4">
                    <h1 class="h4 fw-bold mb-1 text-dark"><?= $isEditMode ? 'Cập nhật thông tin sự kiện' : 'Tạo sự kiện mới' ?></h1>
                    <p class="text-muted small mb-0">CLB: <?= $h($club['ClubName']) ?></p>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 mb-4" role="alert">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Vui lòng kiểm tra lại:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= $h($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div id="clientErrors" class="alert alert-danger rounded-3 mb-4 d-none" role="alert"></div>

                    <?php if ($isEditMode && $registered > 0): ?>
                        <div class="alert alert-info border-0 rounded-3 mb-4 small">
                            <i class="bi bi-info-circle me-1"></i>
                            Hiện có <strong><?= $registered ?></strong> người đã đăng ký. Số chỗ tối đa không được nhỏ hơn con số này.
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= $h($_SERVER['REQUEST_URI']) ?>" id="eventForm" enctype="multipart/form-data" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label for="event_name" class="form-label fw-bold text-dark">Tên sự kiện <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="event_name" name="event_name" maxlength="200"
                                   value="<?= $h($form['EventName']) ?>" required>
                        </div>

                        <!-- Banner Image Upload -->
                        <div class="mb-4">
                            <label for="banner_image" class="form-label fw-bold text-dark">Hình ảnh / Banner sự kiện</label>
                            <?php if ($isEditMode && !empty($evt['BannerImage']) && file_exists(__DIR__ . '/../../../' . $evt['BannerImage'])): ?>
                                <div class="mb-2">
                                    <div class="small text-muted mb-1">Banner hiện tại:</div>
                                    <img src="<?= base_url($evt['BannerImage']) ?>" alt="Banner hiện tại" class="rounded-3 border" style="max-height: 160px; max-width: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control rounded-3" id="banner_image" name="banner_image" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <div class="form-text text-muted">Hỗ trợ định dạng JPG, PNG, WEBP. Dung lượng tối đa 5MB. Kích thước khuyến nghị tỷ lệ ~ 16:9 hoặc 21:9.</div>
                            <div id="newBannerPreviewContainer" class="mt-2 d-none">
                                <div class="small text-success fw-semibold mb-1"><i class="bi bi-eye me-1"></i> Xem trước banner mới:</div>
                                <img id="newBannerPreview" src="#" alt="Xem trước banner" class="rounded-3 border" style="max-height: 160px; max-width: 100%; object-fit: cover;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold text-dark">Mô tả</label>
                            <textarea class="form-control rounded-3" id="description" name="description" rows="5" maxlength="5000"><?= $h($form['Description']) ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="location" class="form-label fw-bold text-dark">Địa điểm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="location" name="location" maxlength="255"
                                   value="<?= $h($form['Location']) ?>" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label fw-bold text-dark">Thời gian bắt đầu <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control rounded-3" id="start_time" name="start_time"
                                       value="<?= $h($form['StartTime']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_time" class="form-label fw-bold text-dark">Thời gian kết thúc <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control rounded-3" id="end_time" name="end_time"
                                       value="<?= $h($form['EndTime']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="registration_start_time" class="form-label fw-bold text-dark">Thời gian mở đăng ký</label>
                                <input type="datetime-local" class="form-control rounded-3" id="registration_start_time" name="registration_start_time"
                                       value="<?= $h($form['RegistrationStartTime']) ?>">
                                <div class="form-text text-muted">Để trống nếu mở đăng ký ngay khi công bố (hoặc đặt thời điểm tương lai để sự kiện ở trạng thái "Sắp mở đăng ký").</div>
                            </div>
                            <div class="col-md-6">
                                <label for="registration_deadline" class="form-label fw-bold text-dark">Hạn chót đăng ký <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control rounded-3" id="registration_deadline" name="registration_deadline"
                                       value="<?= $h($form['RegistrationDeadline']) ?>" required>
                                <div class="form-text text-muted">Phải trước hoặc bằng thời gian bắt đầu sự kiện.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="max_capacity" class="form-label fw-bold text-dark">Số chỗ tối đa</label>
                                <input type="number" class="form-control rounded-3" id="max_capacity" name="max_capacity" min="1" max="100000" step="1"
                                       value="<?= $h($form['MaxCapacity']) ?>" placeholder="Để trống nếu không giới hạn">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-bold text-dark">Trạng thái</label>
                                <?php if ($canChooseStatus): ?>
                                    <select class="form-select rounded-3" id="status" name="status">
                                        <option value="Published" <?= $form['Status'] === 'Published' ? 'selected' : '' ?>>Công bố ngay</option>
                                        <option value="Draft" <?= $form['Status'] === 'Draft' ? 'selected' : '' ?>>Lưu bản nháp (chưa hiển thị cho người dùng)</option>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="form-control rounded-3" value="Đã công bố" disabled>
                                    <div class="form-text text-muted">Muốn dừng sự kiện, hãy dùng chức năng "Hủy sự kiện" ở trang quản lý.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Phân công Ban Tổ Chức -->
                        <div class="mb-4 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i class="bi bi-people-fill text-primary me-2"></i>Phân công Cán bộ Ban Điều Hành phụ trách <span class="text-danger">*</span>
                                </label>
                                <span class="badge bg-primary-subtle text-primary font-monospace" style="font-size: 0.75rem;">
                                    Tối thiểu 2 cán bộ Ban Điều Hành
                                </span>
                            </div>
                            <p class="text-muted small mb-2">Chọn các cán bộ được cấp quyền điều hành và mở bàn điểm danh cho sự kiện này:</p>

                            <!-- Search box for staff -->
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control border-start-0" id="formStaffSearchInput" placeholder="Lọc theo tên cán bộ, vai trò..." oninput="filterFormStaff(this.value)">
                            </div>

                            <div class="row g-2 overflow-y-auto p-2 rounded-2 bg-white border" id="formStaffListWrapper" style="max-height: 260px;">
                                <?php foreach ($availableStaff as $stf): 
                                    $checked = in_array((int)$stf['AccountID'], $currentStaffIds, true);
                                ?>
                                    <div class="col-md-6 form-staff-card" data-text="<?= htmlspecialchars(mb_strtolower($stf['FullName'] . ' ' . $stf['MemberRole'] . ' ' . ($stf['DepartmentCode'] ?? ''), 'UTF-8')) ?>">
                                        <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between h-100">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input staff-checkbox" type="checkbox" name="staff_ids[]" value="<?= (int)$stf['AccountID'] ?>" id="staff_<?= (int)$stf['AccountID'] ?>" <?= $checked ? 'checked' : '' ?>>
                                                <label class="form-check-label ps-2" for="staff_<?= (int)$stf['AccountID'] ?>" style="cursor: pointer;">
                                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($stf['FullName']) ?></div>
                                                    <div class="text-muted font-monospace" style="font-size: 0.72rem;">
                                                        <?= htmlspecialchars($stf['StudentCode'] ?? '') ?> · <?= htmlspecialchars($stf['DepartmentCode'] ?? 'BĐH') ?>
                                                    </div>
                                                </label>
                                            </div>
                                            <span class="badge bg-white text-secondary border font-monospace" style="font-size: 0.68rem;">
                                                <?= htmlspecialchars($stf['MemberRole']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                            <a href="<?= $h($manageUrl) ?>" class="btn btn-outline-secondary">Quay lại</a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i>
                                <?= $isEditMode ? 'Lưu thay đổi' : 'Tạo sự kiện' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Kiểm tra nhanh phía trình duyệt; máy chủ vẫn kiểm tra lại toàn bộ.
(function () {
    var form = document.getElementById('eventForm');
    var box = document.getElementById('clientErrors');
    if (!form || !box) return;

    form.addEventListener('submit', function (e) {
        var f = form.elements, msgs = [];
        var s = f['start_time'].value, en = f['end_time'].value, d = f['registration_deadline'].value;

        if (!f['event_name'].value.trim()) msgs.push('Vui lòng nhập tên sự kiện.');
        if (!f['location'].value.trim()) msgs.push('Vui lòng nhập địa điểm.');
        if (!s) msgs.push('Vui lòng chọn thời gian bắt đầu.');
        if (!en) msgs.push('Vui lòng chọn thời gian kết thúc.');
        if (!d) msgs.push('Vui lòng chọn hạn chót đăng ký.');
        if (s && en && en <= s) msgs.push('Thời gian kết thúc phải sau thời gian bắt đầu.');
        if (s && d && d > s) msgs.push('Hạn đăng ký phải nhỏ hơn hoặc bằng thời gian bắt đầu sự kiện.');

        var staffChecks = form.querySelectorAll('input[name="staff_ids[]"]:checked');
        if (staffChecks && staffChecks.length < 2) {
            msgs.push('Vui lòng phân công tối thiểu 2 cán bộ Ban Điều Hành phụ trách sự kiện.');
        }

        if (msgs.length) {
            e.preventDefault();
            box.innerHTML = '<div class="fw-bold mb-1">Vui lòng kiểm tra lại:</div><ul class="mb-0 ps-3"><li>' + msgs.join('</li><li>') + '</li></ul>';
            box.classList.remove('d-none');
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            box.classList.add('d-none');
        }
    });

    function filterFormStaff(val) {
        var q = (val || '').toLowerCase().trim();
        document.querySelectorAll('.form-staff-card').forEach(function(c) {
            var t = c.getAttribute('data-text') || '';
            c.style.display = (q === '' || t.includes(q)) ? '' : 'none';
        });
    }
    window.filterFormStaff = filterFormStaff;

    var bannerInput = document.getElementById('banner_image');
    var bannerPrevBox = document.getElementById('newBannerPreviewContainer');
    var bannerPrevImg = document.getElementById('newBannerPreview');
    if (bannerInput && bannerPrevImg) {
        bannerInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    bannerPrevImg.src = e.target.result;
                    bannerPrevBox.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                bannerPrevBox.classList.add('d-none');
            }
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
