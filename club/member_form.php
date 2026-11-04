<?php
/**
 * EventFlow — SCR07: Thêm / Sửa thành viên CLB, phân quyền Tầng 2 & áp dụng BR03
 * Đáp ứng: FR07, FR08, PB09, PB10, UC06, BR03
 * Phân quyền: Dành riêng cho Club Manager
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

// Kiểm tra quyền Club Manager
require_role('Club Manager');

$pdo = get_db_connection();

$memberId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEditMode = !empty($memberId);

$memberData = null;
$isManager = false;
$availableAccounts = [];
$errors = [];

// Nếu là chế độ Edit
if ($isEditMode) {
    $stmt = $pdo->prepare("
        SELECT 
            cm.*,
            a.FullName,
            a.StudentCode,
            a.Email,
            a.RoleID,
            r.RoleName,
            (c.ManagerID = a.AccountID) AS IsClubManager
        FROM Club_Member cm
        JOIN Account a ON cm.AccountID = a.AccountID
        JOIN Role r ON a.RoleID = r.RoleID
        LEFT JOIN Club c ON c.ClubID = cm.ClubID
        WHERE cm.MemberID = :id AND cm.ClubID = 1
        LIMIT 1
    ");
    $stmt->execute([':id' => $memberId]);
    $memberData = $stmt->fetch();

    if (!$memberData) {
        set_flash('danger', 'Không tìm thấy thông tin thành viên cần chỉnh sửa.');
        header('Location: ' . base_url('club/members.php'));
        exit;
    }

    $isManager = (bool)$memberData['IsClubManager'];
} else {
    // Chế độ Add: Lấy danh sách các Account đang hoạt động chưa tham gia CLB
    $accStmt = $pdo->query("
        SELECT a.AccountID, a.FullName, a.StudentCode, a.Email, r.RoleName
        FROM Account a
        JOIN Role r ON a.RoleID = r.RoleID
        WHERE a.AccountID NOT IN (SELECT AccountID FROM Club_Member WHERE ClubID = 1)
          AND a.IsActive = 1
        ORDER BY a.FullName ASC
    ");
    $availableAccounts = $accStmt->fetchAll();
}

// Xử lý Form Submit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = "Mã xác thực CSRF không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.";
    } else {
        $memberRole = trim($_POST['member_role'] ?? 'Member');
        $status = trim($_POST['status'] ?? 'Active');
        $joinedDate = trim($_POST['joined_date'] ?? date('Y-m-d'));

        // Kiểm tra hợp lệ dữ liệu vai trò & trạng thái
        if (!in_array($memberRole, ['Member', 'Board Member', 'Event Supporter'], true)) {
            $errors[] = "Vai trò nội bộ không hợp lệ.";
        }

        if (!in_array($status, ['Active', 'Inactive'], true)) {
            $errors[] = "Trạng thái thành viên không hợp lệ.";
        }

        if ($joinedDate === '' || !strtotime($joinedDate)) {
            $errors[] = "Ngày tham gia không đúng định dạng.";
        }

        if ($isEditMode) {
            // RÀNG BUỘC NGHIỆP VỤ BR03: Bảo vệ Club Manager cuối cùng
            if ($isManager && $status === 'Inactive') {
                $errors[] = "Quy tắc BR03: Không thể vô hiệu hóa (Inactive) tài khoản Chủ nhiệm CLB phụ trách duy nhất.";
            }

            if (empty($errors)) {
                $updateStmt = $pdo->prepare("
                    UPDATE Club_Member 
                    SET MemberRole = :role,
                        Status = :status,
                        JoinedDate = :joinedDate
                    WHERE MemberID = :memberId AND ClubID = 1
                ");
                $updateStmt->execute([
                    ':role'       => $memberRole,
                    ':status'     => $status,
                    ':joinedDate' => $joinedDate,
                    ':memberId'   => $memberId
                ]);

                // Nếu là tài khoản đang đăng nhập của chính Manager và đổi thông tin, đồng bộ session
                if ($memberData['AccountID'] == current_user_id()) {
                    $_SESSION['user']['MemberRole'] = $memberRole;
                    $_SESSION['user']['ClubStatus'] = $status;
                }

                set_flash('success', "Cập nhật thành viên '{$memberData['FullName']}' thành công!");
                header('Location: ' . base_url('club/members.php'));
                exit;
            }
        } else {
            // Chế độ Thêm mới
            $accountId = (int)($_POST['account_id'] ?? 0);

            if ($accountId <= 0) {
                $errors[] = "Vui lòng chọn tài khoản sinh viên cần thêm vào CLB.";
            } else {
                // Kiểm tra xem tài khoản đã tồn tại trong CLB chưa
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Club_Member WHERE AccountID = :accId AND ClubID = 1");
                $checkStmt->execute([':accId' => $accountId]);
                if ((int)$checkStmt->fetchColumn() > 0) {
                    $errors[] = "Tài khoản này đã là thành viên của CLB.";
                }
            }

            if (empty($errors)) {
                $pdo->beginTransaction();
                try {
                    // 1. Thêm vào bảng Club_Member
                    $insertStmt = $pdo->prepare("
                        INSERT INTO Club_Member (AccountID, ClubID, MemberRole, Status, JoinedDate)
                        VALUES (:accId, 1, :role, :status, :joinedDate)
                    ");
                    $insertStmt->execute([
                        ':accId'      => $accountId,
                        ':role'       => $memberRole,
                        ':status'     => $status,
                        ':joinedDate' => $joinedDate
                    ]);

                    // 2. Nếu Role hệ thống hiện tại là 'Event Participant' (RoleID = 4), nâng lên 'Club Member' (RoleID = 3)
                    $upgradeStmt = $pdo->prepare("
                        UPDATE Account 
                        SET RoleID = 3 
                        WHERE AccountID = :accId AND RoleID = 4
                    ");
                    $upgradeStmt->execute([':accId' => $accountId]);

                    $pdo->commit();

                    set_flash('success', 'Thêm thành viên mới vào CLB thành công!');
                    header('Location: ' . base_url('club/members.php'));
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "Lỗi khi lưu dữ liệu: " . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = ($isEditMode ? "Chỉnh sửa thành viên" : "Thêm thành viên mới") . " — EventFlow";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('club/members.php') ?>" class="text-decoration-none text-muted">Danh sách thành viên</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                <?= $isEditMode ? 'Chỉnh sửa thành viên' : 'Thêm thành viên mới' ?>
            </li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom border-light p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill mb-2 fw-semibold">
                                <?= $isEditMode ? 'SCR07: Chỉnh sửa vai trò & trạng thái' : 'SCR07: Thêm thành viên vào CLB' ?>
                            </span>
                            <h1 class="h4 fw-bold mb-1 text-dark">
                                <?= $isEditMode ? 'Cập nhật thành viên CLB' : 'Thêm thành viên mới' ?>
                            </h1>
                            <p class="text-muted small mb-0">
                                Quản lý phân quyền Tầng 2 (Ủy quyền hỗ trợ điểm danh sự kiện) theo quy chuẩn hệ thống.
                            </p>
                        </div>
                        <div class="text-primary d-none d-sm-block">
                            <i class="bi bi-person-gear fs-1"></i>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 mb-4" role="alert">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Lỗi xử lý:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($isEditMode && $isManager): ?>
                        <div class="alert alert-warning border-0 rounded-3 mb-4 d-flex align-items-center gap-3">
                            <i class="bi bi-shield-exclamation fs-3 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Quy tắc bảo vệ người quản trị (BR03)</strong>
                                <span class="small text-secondary">
                                    Tài khoản này là Chủ nhiệm CLB phụ trách chính. Hệ thống ngăn chặn việc chuyển tài khoản sang trạng thái <strong>Inactive</strong>.
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" novalidate>
                        <?= csrf_field() ?>

                        <!-- Chọn Tài khoản (Add mode) hoặc Hiển thị thông tin (Edit mode) -->
                        <?php if ($isEditMode): ?>
                            <div class="p-3 bg-light rounded-3 border mb-4">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="small text-muted">Họ và tên thành viên</div>
                                        <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($memberData['FullName']) ?></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="small text-muted">Mã số sinh viên (MSSV)</div>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($memberData['StudentCode'] ?? 'Chưa cập nhật') ?></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="small text-muted">Email liên lạc</div>
                                        <div class="text-dark small"><?= htmlspecialchars($memberData['Email']) ?></div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="small text-muted">Vai trò hệ thống (Tầng 1)</div>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <?= htmlspecialchars($memberData['RoleName']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <label for="account_id" class="form-label fw-bold text-dark">
                                    Chọn tài khoản người dùng <span class="text-danger">*</span>
                                </label>
                                <?php if (empty($availableAccounts)): ?>
                                    <div class="p-3 bg-light text-muted rounded-3 border">
                                        <i class="bi bi-info-circle me-1"></i> Hiện tại tất cả các tài khoản trên hệ thống đều đã thuộc CLB.
                                    </div>
                                <?php else: ?>
                                    <select name="account_id" id="account_id" class="form-select form-select-lg fs-6 rounded-3" required>
                                        <option value="">-- Chọn sinh viên cần thêm vào CLB --</option>
                                        <?php foreach ($availableAccounts as $acc): ?>
                                            <option value="<?= $acc['AccountID'] ?>">
                                                <?= htmlspecialchars($acc['FullName']) ?> 
                                                <?= !empty($acc['StudentCode']) ? ' - MSSV: ' . htmlspecialchars($acc['StudentCode']) : '' ?>
                                                (<?= htmlspecialchars($acc['Email']) ?>) [<?= htmlspecialchars($acc['RoleName']) ?>]
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-muted">
                                        Chọn tài khoản đã đăng ký trên hệ thống. Nếu tài khoản có vai trò 'Event Participant', hệ thống sẽ tự động chuyển thành 'Club Member'.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Vai trò nội bộ CLB (Tầng 2: MemberRole) -->
                        <div class="mb-4">
                            <label for="member_role" class="form-label fw-bold text-dark">
                                Vai trò nội bộ CLB (Tầng 2) <span class="text-danger">*</span>
                            </label>
                            <?php $currentRole = $isEditMode ? $memberData['MemberRole'] : 'Member'; ?>
                            <select name="member_role" id="member_role" class="form-select rounded-3" required>
                                <option value="Member" <?= $currentRole === 'Member' ? 'selected' : '' ?>>
                                    Thành Viên Thường (Member)
                                </option>
                                <option value="Event Supporter" <?= $currentRole === 'Event Supporter' ? 'selected' : '' ?>>
                                    Hỗ Trợ Sự Kiện (Event Supporter — Được ủy quyền Điểm danh Check-in SCR14)
                                </option>
                                <option value="Board Member" <?= $currentRole === 'Board Member' ? 'selected' : '' ?>>
                                    Ban Điều Hành CLB (Board Member)
                                </option>
                            </select>
                            <div class="form-text text-muted">
                                <span class="text-primary fw-semibold"><i class="bi bi-info-circle me-1"></i> Lưu ý phân quyền:</span> Thành viên có vai trò <strong>Event Supporter</strong> hoặc <strong>Board Member</strong> ở trạng thái <em>Active</em> sẽ được cấp quyền truy cập tính năng Quản lý danh sách đăng ký (SCR13) và Điểm danh Check-in (SCR14).
                            </div>
                        </div>

                        <!-- Trạng thái hoạt động (Status) -->
                        <div class="mb-4">
                            <label for="status" class="form-label fw-bold text-dark">
                                Trạng thái thành viên <span class="text-danger">*</span>
                            </label>
                            <?php $currentStatus = $isEditMode ? $memberData['Status'] : 'Active'; ?>
                            <select name="status" id="status" class="form-select rounded-3" required>
                                <option value="Active" <?= $currentStatus === 'Active' ? 'selected' : '' ?>>
                                    Active — Đang sinh hoạt bình thường
                                </option>
                                <option value="Inactive" <?= $currentStatus === 'Inactive' ? 'selected' : '' ?> <?= ($isEditMode && $isManager) ? 'disabled' : '' ?>>
                                    Inactive — Tạm ngưng hoạt động / Rời CLB <?= ($isEditMode && $isManager) ? '(Bị khóa theo BR03)' : '' ?>
                                </option>
                            </select>
                            <div class="form-text text-muted">
                                Thành viên ở trạng thái <em>Inactive</em> sẽ mất toàn bộ quyền ủy quyền nội bộ trong CLB.
                            </div>
                        </div>

                        <!-- Ngày tham gia (JoinedDate) -->
                        <div class="mb-4">
                            <label for="joined_date" class="form-label fw-bold text-dark">
                                Ngày tham gia CLB
                            </label>
                            <?php 
                            $currentDate = $isEditMode && !empty($memberData['JoinedDate']) 
                                ? $memberData['JoinedDate'] 
                                : date('Y-m-d'); 
                            ?>
                            <input 
                                type="date" 
                                name="joined_date" 
                                id="joined_date" 
                                class="form-control rounded-3" 
                                value="<?= htmlspecialchars($currentDate) ?>"
                                required
                            >
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                            <a href="<?= base_url('club/members.php') ?>" class="btn btn-outline-secondary">
                                Quay lại danh sách
                            </a>
                            <button 
                                type="submit" 
                                class="btn btn-primary px-4 fw-semibold"
                                <?= (!$isEditMode && empty($availableAccounts)) ? 'disabled' : '' ?>
                            >
                                <i class="bi bi-check2-circle me-1"></i>
                                <?= $isEditMode ? 'Cập nhật thông tin' : 'Xác nhận thêm thành viên' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
