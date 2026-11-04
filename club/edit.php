<?php
/**
 * EventFlow — SCR05: Chỉnh sửa hồ sơ thông tin Câu lạc bộ
 * Đáp ứng: FR05, PB07, UC17
 * Phân quyền: Dành riêng cho Club Manager (RoleID = 2)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

// Kiểm tra quyền Club Manager
require_role('Club Manager');

$pdo = get_db_connection();

// Lấy thông tin CLB hiện tại (ClubID = 1)
$stmt = $pdo->prepare("
    SELECT c.*, a.FullName AS ManagerName, a.Email AS ManagerEmail
    FROM Club c
    JOIN Account a ON c.ManagerID = a.AccountID
    WHERE c.ClubID = 1
    LIMIT 1
");
$stmt->execute();
$club = $stmt->fetch();

if (!$club) {
    die("Không tìm thấy thông tin Câu lạc bộ.");
}

$errors = [];
$clubName = $club['ClubName'];
$description = $club['Description'];
$contactInfo = $club['ContactInfo'];

// Xử lý lưu form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $errors[] = "Yêu cầu không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng tải lại trang.";
    } else {
        $clubName = trim($_POST['club_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contactInfo = trim($_POST['contact_info'] ?? '');

        // Validation
        if ($clubName === '') {
            $errors[] = "Tên câu lạc bộ không được để trống.";
        } elseif (mb_strlen($clubName, 'UTF-8') > 150) {
            $errors[] = "Tên câu lạc bộ không được vượt quá 150 ký tự.";
        }

        if (mb_strlen($contactInfo, 'UTF-8') > 255) {
            $errors[] = "Thông tin liên hệ không được vượt quá 255 ký tự.";
        }

        if (empty($errors)) {
            $updateStmt = $pdo->prepare("
                UPDATE Club 
                SET ClubName = :clubName,
                    Description = :description,
                    ContactInfo = :contactInfo
                WHERE ClubID = :clubId
            ");
            $updateStmt->execute([
                ':clubName'    => $clubName,
                ':description' => $description,
                ':contactInfo' => $contactInfo,
                ':clubId'      => $club['ClubID']
            ]);

            set_flash('success', 'Cập nhật thông tin Câu lạc bộ thành công!');
            header('Location: ' . base_url('club/info.php'));
            exit;
        }
    }
}

$pageTitle = "Chỉnh sửa thông tin CLB — EventFlow";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('club/info.php') ?>" class="text-decoration-none text-muted">Thông tin CLB</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Chỉnh sửa hồ sơ</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom border-light p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill mb-2 fw-semibold">
                                Dành cho Club Manager
                            </span>
                            <h1 class="h4 fw-bold mb-1 text-dark">Chỉnh sửa hồ sơ Câu lạc bộ</h1>
                            <p class="text-muted small mb-0">Cập nhật tên, mô tả hoạt động và thông tin liên lạc chính thức của CLB.</p>
                        </div>
                        <div class="d-none d-sm-block text-primary">
                            <i class="bi bi-gear-wide-connected fs-1"></i>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-3 mb-4" role="alert">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Không thể lưu thông tin:</div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" novalidate>
                        <?= csrf_field() ?>

                        <!-- Tên Câu Lạc Bộ -->
                        <div class="mb-4">
                            <label for="club_name" class="form-label fw-bold text-dark">
                                Tên Câu lạc bộ <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg fs-6 rounded-3" 
                                id="club_name" 
                                name="club_name" 
                                value="<?= htmlspecialchars($clubName) ?>" 
                                maxlength="150" 
                                required
                                placeholder="Ví dụ: UEH English Club (Bell Club)"
                            >
                            <div class="form-text text-muted">Tên chính thức hiển thị công khai trên hệ thống (tối đa 150 ký tự).</div>
                        </div>

                        <!-- Giới thiệu / Mô tả hoạt động -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold text-dark">
                                Giới thiệu & Lĩnh vực hoạt động
                            </label>
                            <textarea 
                                class="form-control rounded-3" 
                                id="description" 
                                name="description" 
                                rows="6" 
                                placeholder="Mô tả mục tiêu, sứ mệnh, các hoạt động định kỳ và giá trị mang lại cho sinh viên..."
                            ><?= htmlspecialchars($description) ?></textarea>
                            <div class="form-text text-muted">Mô tả chi tiết giúp sinh viên hiểu rõ hơn về văn hóa và nội dung sinh hoạt của CLB.</div>
                        </div>

                        <!-- Thông tin liên hệ -->
                        <div class="mb-4">
                            <label for="contact_info" class="form-label fw-bold text-dark">
                                Thông tin liên hệ công khai
                            </label>
                            <input 
                                type="text" 
                                class="form-control rounded-3" 
                                id="contact_info" 
                                name="contact_info" 
                                value="<?= htmlspecialchars($contactInfo) ?>" 
                                maxlength="255"
                                placeholder="Ví dụ: Email: englishclub@ueh.edu.vn | Fanpage: fb.com/uehenglishclub | Cơ sở B"
                            >
                            <div class="form-text text-muted">Địa chỉ email hỗ trợ, trang Fanpage, cơ sở sinh hoạt (tối đa 255 ký tự).</div>
                        </div>

                        <!-- Thông tin người quản lý phụ trách (Chỉ đọc) -->
                        <div class="p-3 rounded-3 bg-light border mb-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="small text-muted">Chủ nhiệm CLB hiện tại</div>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($club['ManagerName']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($club['ManagerEmail']) ?></div>
                                </div>
                                <div>
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <i class="bi bi-lock-fill me-1"></i> Quản lý bởi Hệ thống
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
                            <a href="<?= base_url('club/info.php') ?>" class="btn btn-outline-secondary">
                                Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle me-1"></i> Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
