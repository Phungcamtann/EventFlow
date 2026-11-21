<?php
/**
 * EventFlow — SCR07: Thêm / Sửa thành viên CLB, phân quyền Tầng 2 & áp dụng BR03
 * Đáp ứng: FR07, FR08, PB09, PB10, UC06, BR03
 * Phân quyền: Dành riêng cho Club Manager
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';

// Kiểm tra quyền Club Manager
require_role('Club Manager');

$pdo = get_db_connection();

// Xử lý AJAX tra cứu tài khoản theo MSSV hoặc Email (UAT 07)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search_account') {
    header('Content-Type: application/json; charset=utf-8');
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập MSSV hoặc Email để tìm kiếm.',
            'data'    => []
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            a.AccountID,
            a.FullName,
            a.StudentCode,
            a.Email,
            a.RoleID,
            r.RoleName,
            cm.MemberID,
            cm.MemberRole,
            cm.Status AS ClubMemberStatus
        FROM Account a
        JOIN Role r ON a.RoleID = r.RoleID
        LEFT JOIN Club_Member cm ON (cm.AccountID = a.AccountID AND cm.ClubID = 1)
        WHERE a.IsActive = 1
          AND (a.StudentCode = :exact_code OR a.Email = :exact_email OR a.StudentCode LIKE :like_code OR a.Email LIKE :like_email OR a.FullName LIKE :like_name)
        ORDER BY 
            CASE 
                WHEN a.StudentCode = :exact_code2 THEN 1
                WHEN a.Email = :exact_email2 THEN 2
                WHEN a.StudentCode LIKE :like_code2 THEN 3
                ELSE 4
            END,
            a.FullName ASC
        LIMIT 10
    ");
    $like = '%' . $q . '%';
    $stmt->execute([
        ':exact_code'   => $q,
        ':exact_email'  => $q,
        ':like_code'    => $like,
        ':like_email'   => $like,
        ':like_name'    => $like,
        ':exact_code2'  => $q,
        ':exact_email2' => $q,
        ':like_code2'   => $like
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy tài khoản nào khớp với từ khóa "' . htmlspecialchars($q) . '". Vui lòng kiểm tra lại MSSV hoặc Email.',
            'data'    => []
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'data'    => $results
        ]);
    }
    exit;
}

$memberId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEditMode = !empty($memberId);

$memberData = null;
$isManager = false;
$availableAccounts = [];
$selectedAccount = null;
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
        header('Location: ' . base_url('app/modules/club/members.php'));
        exit;
    }

    $isManager = (bool)$memberData['IsClubManager'];
} else {
    // Chế độ Add: Nếu trước đó đã chọn account trong POST, khôi phục lại thông tin đối chiếu
    $selAccId = (int)($_POST['account_id'] ?? 0);
    if ($selAccId > 0) {
        $selStmt = $pdo->prepare("
            SELECT a.AccountID, a.FullName, a.StudentCode, a.Email, r.RoleName
            FROM Account a
            JOIN Role r ON a.RoleID = r.RoleID
            WHERE a.AccountID = :id AND a.IsActive = 1
        ");
        $selStmt->execute([':id' => $selAccId]);
        $selectedAccount = $selStmt->fetch();
    }
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
            // Bảo vệ Club Manager cuối cùng
            if ($isManager && $status === 'Inactive') {
                $errors[] = "Không thể vô hiệu hóa (Inactive) tài khoản Chủ nhiệm CLB phụ trách duy nhất.";
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
                header('Location: ' . base_url('app/modules/club/members.php'));
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
                    header('Location: ' . base_url('app/modules/club/members.php'));
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
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('app/modules/club/members.php') ?>" class="text-decoration-none text-muted">Danh sách thành viên</a></li>
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
                                <?= $isEditMode ? 'Chỉnh sửa vai trò & trạng thái' : 'Thêm thành viên vào CLB' ?>
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
                                <strong class="d-block text-dark">Lưu ý quản trị</strong>
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
                                <label class="form-label fw-bold text-dark">
                                    Tìm & Thêm tài khoản thành viên <span class="text-danger">*</span>
                                </label>

                                <!-- Khung Tìm kiếm theo MSSV hoặc Email (UAT 07) -->
                                <div id="search_box_container" class="<?= !empty($selectedAccount) ? 'd-none' : '' ?>">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input 
                                            type="text" 
                                            id="search_keyword" 
                                            class="form-control border-start-0 ps-0 form-control-lg fs-6" 
                                            placeholder="Nhập MSSV (VD: 31241020999) hoặc Email sinh viên để tìm kiếm..."
                                            autocomplete="off"
                                        >
                                        <button class="btn btn-primary px-4 fw-semibold" type="button" id="btn_search">
                                            <span id="btn_search_text"><i class="bi bi-search me-1"></i> Tìm kiếm</span>
                                            <span id="btn_search_spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </button>
                                    </div>
                                    <div class="form-text text-muted mt-2">
                                        <i class="bi bi-info-circle me-1"></i> Nhập chính xác MSSV hoặc địa chỉ Email của sinh viên để tra cứu thông tin đối chiếu trước khi thêm.
                                    </div>

                                    <!-- Thông báo lỗi khi không tìm thấy / từ khóa không hợp lệ -->
                                    <div id="search_alert" class="alert alert-warning border-0 rounded-3 mt-3 d-none">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                                            <span id="search_alert_msg"></span>
                                        </div>
                                    </div>

                                    <!-- Danh sách kết quả tìm kiếm -->
                                    <div id="search_results_container" class="mt-3 d-none">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="small fw-bold text-muted text-uppercase">Kết quả tìm kiếm:</span>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill" id="search_results_count">0 kết quả</span>
                                        </div>
                                        <div class="list-group rounded-3 shadow-sm border" id="search_results_list"></div>
                                    </div>
                                </div>

                                <!-- Hidden Input gửi AccountID khi submit form -->
                                <input type="hidden" name="account_id" id="account_id" value="<?= !empty($selectedAccount) ? (int)$selectedAccount['AccountID'] : '' ?>">

                                <!-- Khung đối chiếu thông tin tài khoản đã chọn (Verification Card) -->
                                <div id="selected_user_card" class="card border border-success-subtle bg-success-subtle bg-opacity-10 rounded-3 p-3 mt-2 <?= empty($selectedAccount) ? 'd-none' : '' ?>">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle bg-primary text-white fw-bold rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; font-size: 1.25rem;" id="preview_avatar">
                                                <?= !empty($selectedAccount) ? strtoupper(mb_substr($selectedAccount['FullName'], 0, 1)) : 'U' ?>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <h5 class="mb-0 fw-bold text-dark" id="preview_fullname">
                                                        <?= !empty($selectedAccount) ? htmlspecialchars($selectedAccount['FullName']) : '' ?>
                                                    </h5>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small" id="preview_role">
                                                        <?= !empty($selectedAccount) ? htmlspecialchars($selectedAccount['RoleName']) : '' ?>
                                                    </span>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <span class="me-3">
                                                        <i class="bi bi-person-badge text-primary me-1"></i> MSSV: 
                                                        <strong class="text-dark font-mono" id="preview_code">
                                                            <?= !empty($selectedAccount['StudentCode']) ? htmlspecialchars($selectedAccount['StudentCode']) : 'Chưa cập nhật' ?>
                                                        </strong>
                                                    </span>
                                                    <span>
                                                        <i class="bi bi-envelope text-primary me-1"></i> Email: 
                                                        <strong class="text-dark" id="preview_email">
                                                            <?= !empty($selectedAccount) ? htmlspecialchars($selectedAccount['Email']) : '' ?>
                                                        </strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-outline-danger btn-sm px-3" id="btn_clear_selection">
                                                <i class="bi bi-arrow-repeat me-1"></i> Chọn người khác
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top border-success-subtle text-success small d-flex align-items-center gap-1">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Thông tin tài khoản đối chiếu hợp lệ. Vui lòng chọn vai trò nội bộ và bấm xác nhận bên dưới.</span>
                                    </div>
                                </div>
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
                                    Hỗ Trợ Sự Kiện (Event Supporter — Được ủy quyền Điểm danh)
                                </option>
                                <option value="Board Member" <?= $currentRole === 'Board Member' ? 'selected' : '' ?>>
                                    Ban Điều Hành CLB (Board Member)
                                </option>
                            </select>
                            <div class="form-text text-muted">
                                <span class="text-primary fw-semibold"><i class="bi bi-info-circle me-1"></i> Lưu ý phân quyền:</span> Thành viên có vai trò <strong>Event Supporter</strong> hoặc <strong>Board Member</strong> ở trạng thái <em>Active</em> sẽ được cấp quyền truy cập tính năng Quản lý danh sách đăng ký và Điểm danh sự kiện.
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
                                    Inactive — Tạm ngưng hoạt động / Rời CLB <?= ($isEditMode && $isManager) ? '(Bị khóa để bảo vệ Chủ nhiệm CLB)' : '' ?>
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
                            <a href="<?= base_url('app/modules/club/members.php') ?>" class="btn btn-outline-secondary">
                                Quay lại danh sách
                            </a>
                            <button 
                                type="submit" 
                                id="btn_submit_member"
                                class="btn btn-primary px-4 fw-semibold"
                                <?= (!$isEditMode && empty($selectedAccount)) ? 'disabled' : '' ?>
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

<?php if (!$isEditMode): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchKeyword = document.getElementById('search_keyword');
    const btnSearch = document.getElementById('btn_search');
    const btnSearchText = document.getElementById('btn_search_text');
    const btnSearchSpinner = document.getElementById('btn_search_spinner');
    const searchAlert = document.getElementById('search_alert');
    const searchAlertMsg = document.getElementById('search_alert_msg');
    const searchResultsContainer = document.getElementById('search_results_container');
    const searchResultsList = document.getElementById('search_results_list');
    const searchResultsCount = document.getElementById('search_results_count');
    const searchBoxContainer = document.getElementById('search_box_container');
    
    const accountIdInput = document.getElementById('account_id');
    const selectedUserCard = document.getElementById('selected_user_card');
    const previewAvatar = document.getElementById('preview_avatar');
    const previewFullname = document.getElementById('preview_fullname');
    const previewRole = document.getElementById('preview_role');
    const previewCode = document.getElementById('preview_code');
    const previewEmail = document.getElementById('preview_email');
    const btnClearSelection = document.getElementById('btn_clear_selection');
    const btnSubmitMember = document.getElementById('btn_submit_member');

    let searchAbortController = null;

    function showAlert(msg) {
        searchAlertMsg.textContent = msg;
        searchAlert.classList.remove('d-none');
        searchResultsContainer.classList.add('d-none');
    }

    function hideAlert() {
        searchAlert.classList.add('d-none');
    }

    function setLoading(isLoading) {
        if (isLoading) {
            btnSearch.disabled = true;
            btnSearchText.classList.add('d-none');
            btnSearchSpinner.classList.remove('d-none');
        } else {
            btnSearch.disabled = false;
            btnSearchText.classList.remove('d-none');
            btnSearchSpinner.classList.add('d-none');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function performSearch() {
        const query = searchKeyword.value.trim();
        hideAlert();

        if (query.length < 2) {
            if (searchAbortController) searchAbortController.abort();
            searchResultsContainer.classList.add('d-none');
            return;
        }

        // Hủy request trước đó nếu chưa hoàn tất để tránh trùng lặp kết quả
        if (searchAbortController) {
            searchAbortController.abort();
        }
        searchAbortController = new AbortController();

        setLoading(true);

        try {
            const url = '<?= base_url("app/modules/club/member_form.php") ?>?ajax=search_account&q=' + encodeURIComponent(query);
            const response = await fetch(url, { signal: searchAbortController.signal });
            const data = await response.json();

            setLoading(false);

            if (!data.success || !data.data || data.data.length === 0) {
                showAlert(data.message || 'Không tìm thấy tài khoản nào phù hợp.');
                return;
            }

            // Render danh sách kết quả
            renderSearchResults(data.data);

        } catch (error) {
            if (error.name === 'AbortError') {
                return; // Bị hủy do người dùng gõ tiếp
            }
            setLoading(false);
            showAlert('Đã có lỗi xảy ra khi kết nối máy chủ. Vui lòng thử lại.');
            console.error('Search error:', error);
        }
    }

    function renderSearchResults(users) {
        searchResultsList.innerHTML = '';
        searchResultsCount.textContent = users.length + ' kết quả';

        users.forEach(user => {
            const isAlreadyMember = Boolean(user.MemberID);
            const studentCode = user.StudentCode ? escapeHtml(user.StudentCode) : 'Chưa cập nhật';
            const initial = user.FullName ? escapeHtml(user.FullName.charAt(0).toUpperCase()) : 'U';

            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action p-3 d-flex align-items-center justify-content-between flex-wrap gap-2';

            let statusBadge = '';
            let actionBtn = '';

            if (isAlreadyMember) {
                statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Đã trong CLB (${escapeHtml(user.MemberRole || 'Thành viên')})</span>`;
                actionBtn = `<button type="button" class="btn btn-sm btn-secondary disabled" disabled>Đã là thành viên</button>`;
            } else {
                statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Chưa tham gia</span>`;
                actionBtn = `<button type="button" class="btn btn-sm btn-primary fw-semibold select-user-btn"><i class="bi bi-check2 me-1"></i>Chọn người này</button>`;
            }

            item.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                        ${initial}
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong class="text-dark">${escapeHtml(user.FullName)}</strong>
                            <span class="badge bg-light text-muted border small">${escapeHtml(user.RoleName)}</span>
                            ${statusBadge}
                        </div>
                        <div class="small text-muted mt-1">
                            <span class="me-3"><i class="bi bi-person-badge text-primary me-1"></i>MSSV: <span class="font-mono">${studentCode}</span></span>
                            <span><i class="bi bi-envelope text-primary me-1"></i>${escapeHtml(user.Email)}</span>
                        </div>
                    </div>
                </div>
                <div>
                    ${actionBtn}
                </div>
            `;

            if (!isAlreadyMember) {
                const btn = item.querySelector('.select-user-btn');
                btn.addEventListener('click', () => selectUser(user));
            }

            searchResultsList.appendChild(item);
        });

        searchResultsContainer.classList.remove('d-none');
    }

    function selectUser(user) {
        accountIdInput.value = user.AccountID;
        previewAvatar.textContent = user.FullName ? user.FullName.charAt(0).toUpperCase() : 'U';
        previewFullname.textContent = user.FullName;
        previewRole.textContent = user.RoleName;
        previewCode.textContent = user.StudentCode ? user.StudentCode : 'Chưa cập nhật';
        previewEmail.textContent = user.Email;

        searchBoxContainer.classList.add('d-none');
        searchResultsContainer.classList.add('d-none');
        selectedUserCard.classList.remove('d-none');

        if (btnSubmitMember) {
            btnSubmitMember.disabled = false;
        }
    }

    btnClearSelection.addEventListener('click', function() {
        accountIdInput.value = '';
        selectedUserCard.classList.add('d-none');
        searchBoxContainer.classList.remove('d-none');
        searchResultsContainer.classList.add('d-none');
        hideAlert();
        searchKeyword.value = '';
        searchKeyword.focus();

        if (btnSubmitMember) {
            btnSubmitMember.disabled = true;
        }
    });

    btnSearch.addEventListener('click', performSearch);
    searchKeyword.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    // Tự động tìm kiếm tức thì theo sự kiện thay đổi (input event) không độ trễ
    searchKeyword.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length === 0) {
            if (searchAbortController) searchAbortController.abort();
            hideAlert();
            searchResultsContainer.classList.add('d-none');
            return;
        }
        if (query.length < 2) {
            if (searchAbortController) searchAbortController.abort();
            hideAlert();
            searchResultsContainer.classList.add('d-none');
            return;
        }
        performSearch();
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
