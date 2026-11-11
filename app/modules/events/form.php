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

// Sự kiện đã công bố thì không đưa ngược về bản nháp (tránh ẩn sự kiện đã có người đăng ký)
$canChooseStatus = !$isEditMode || $evt['Status'] === 'Draft';

$errors = [];
$form = [
    'EventName'            => $isEditMode ? $evt['EventName'] : '',
    'Description'          => $isEditMode ? (string)$evt['Description'] : '',
    'Location'             => $isEditMode ? $evt['Location'] : '',
    'StartTime'            => $isEditMode ? event_dt_to_input($evt['StartTime']) : '',
    'EndTime'              => $isEditMode ? event_dt_to_input($evt['EndTime']) : '',
    'RegistrationDeadline' => $isEditMode ? event_dt_to_input($evt['RegistrationDeadline']) : '',
    'MaxCapacity'          => ($isEditMode && $evt['MaxCapacity'] !== null) ? (string)$evt['MaxCapacity'] : '',
    'Status'               => $isEditMode ? $evt['Status'] : 'Published',
];

// ---------------------------------------------------------------------------
// Xử lý POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['EventName']            = trim($_POST['event_name'] ?? '');
    $form['Description']          = trim($_POST['description'] ?? '');
    $form['Location']             = trim($_POST['location'] ?? '');
    $form['StartTime']            = trim($_POST['start_time'] ?? '');
    $form['EndTime']              = trim($_POST['end_time'] ?? '');
    $form['RegistrationDeadline'] = trim($_POST['registration_deadline'] ?? '');
    $form['MaxCapacity']          = trim($_POST['max_capacity'] ?? '');
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
                                StartTime = :st, EndTime = :et, RegistrationDeadline = :dl,
                                MaxCapacity = :cap, Status = :status
                            WHERE EventID = :id AND ClubID = :c
                        ");
                        $up->execute([
                            ':name'   => $form['EventName'],
                            ':descr'  => $form['Description'] === '' ? null : $form['Description'],
                            ':loc'    => $form['Location'],
                            ':st'     => $start,
                            ':et'     => $end,
                            ':dl'     => $deadline,
                            ':cap'    => $capacity,
                            ':status' => $form['Status'],
                            ':id'     => $eventId,
                            ':c'      => $clubId,
                        ]);
                        $pdo->commit();
                        set_flash('success', 'Cập nhật sự kiện thành công!');
                        header('Location: ' . $manageUrl);
                        exit;
                    }
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO Event
                            (ClubID, EventName, Description, Location, StartTime, EndTime, RegistrationDeadline, MaxCapacity, Status)
                        VALUES (:c, :name, :descr, :loc, :st, :et, :dl, :cap, :status)
                    ");
                    $ins->execute([
                        ':c'      => $clubId,
                        ':name'   => $form['EventName'],
                        ':descr'  => $form['Description'] === '' ? null : $form['Description'],
                        ':loc'    => $form['Location'],
                        ':st'     => $start,
                        ':et'     => $end,
                        ':dl'     => $deadline,
                        ':cap'    => $capacity,
                        ':status' => $form['Status'],
                    ]);
                    $pdo->commit();
                    set_flash('success', 'Tạo sự kiện thành công!');
                    header('Location: ' . $manageUrl);
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

                    <form method="POST" action="<?= $h($_SERVER['REQUEST_URI']) ?>" id="eventForm" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label for="event_name" class="form-label fw-bold text-dark">Tên sự kiện <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="event_name" name="event_name" maxlength="200"
                                   value="<?= $h($form['EventName']) ?>" required>
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
                            <div class="col-md-4">
                                <label for="start_time" class="form-label fw-bold text-dark">Thời gian bắt đầu <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control rounded-3" id="start_time" name="start_time"
                                       value="<?= $h($form['StartTime']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_time" class="form-label fw-bold text-dark">Thời gian kết thúc <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control rounded-3" id="end_time" name="end_time"
                                       value="<?= $h($form['EndTime']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="registration_deadline" class="form-label fw-bold text-dark">Hạn chót đăng ký <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control rounded-3" id="registration_deadline" name="registration_deadline"
                                       value="<?= $h($form['RegistrationDeadline']) ?>" required>
                            </div>
                            <div class="col-12 form-text text-muted mt-0">
                                Hạn chót đăng ký phải nhỏ hơn hoặc bằng thời gian bắt đầu; thời gian kết thúc phải sau thời gian bắt đầu.
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

        if (msgs.length) {
            e.preventDefault();
            box.innerHTML = '<div class="fw-bold mb-1">Vui lòng kiểm tra lại:</div><ul class="mb-0 ps-3"><li>' + msgs.join('</li><li>') + '</li></ul>';
            box.classList.remove('d-none');
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            box.classList.add('d-none');
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
