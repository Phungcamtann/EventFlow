<?php
/**
 * EventFlow — SCR05: Chỉnh sửa hồ sơ thông tin Câu lạc bộ
 * Đáp ứng: FR05, PB07, UC17
 * Phân quyền: Dành cho Club Manager (RoleID = 2) HOẶC Ban Điều Hành (Board Member, Active)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';

// Kiểm tra quyền Club Manager hoặc Ban điều hành (Board Member)
require_login();
if (!can_manage_club_profile()) {
    set_flash('danger', 'Bạn không có quyền truy cập chức năng chỉnh sửa thông tin CLB.');
    header('Location: ' . base_url('app/modules/club/info.php'));
    exit;
}

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

        // Validation text
        if ($clubName === '') {
            $errors[] = "Tên câu lạc bộ không được để trống.";
        } elseif (mb_strlen($clubName, 'UTF-8') > 150) {
            $errors[] = "Tên câu lạc bộ không được vượt quá 150 ký tự.";
        }

        if (mb_strlen($contactInfo, 'UTF-8') > 255) {
            $errors[] = "Thông tin liên hệ không được vượt quá 255 ký tự.";
        }

        $avatarPath = $club['Avatar'] ?? null;
        $bannerPath = $club['BannerImage'] ?? null;
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        // Xử lý tải lên Avatar CLB
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['avatar']['tmp_name'];
            $fileName = $_FILES['avatar']['name'];
            $fileSize = $_FILES['avatar']['size'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'Định dạng ảnh đại diện không hợp lệ (chỉ chấp nhận JPG, JPEG, PNG, WEBP).';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'Dung lượng ảnh đại diện không được vượt quá 5MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($fileTmp);
                if (!in_array($mime, $allowedMimes, true)) {
                    $errors[] = 'Tệp ảnh đại diện không phải định dạng hình ảnh hợp lệ.';
                } else {
                    $uploadDir = __DIR__ . '/../../../assets/uploads/club/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = 'club_avatar_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                        $avatarPath = 'assets/uploads/club/' . $newFileName;
                    } else {
                        $errors[] = 'Không thể lưu ảnh đại diện đã tải lên.';
                    }
                }
            }
        }

        // Xử lý tải lên Banner CLB
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['banner_image']['tmp_name'];
            $fileName = $_FILES['banner_image']['name'];
            $fileSize = $_FILES['banner_image']['size'];
            $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                $errors[] = 'Định dạng ảnh bìa/banner không hợp lệ (chỉ chấp nhận JPG, JPEG, PNG, WEBP).';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'Dung lượng ảnh bìa/banner không được vượt quá 5MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($fileTmp);
                if (!in_array($mime, $allowedMimes, true)) {
                    $errors[] = 'Tệp ảnh bìa/banner không phải định dạng hình ảnh hợp lệ.';
                } else {
                    $uploadDir = __DIR__ . '/../../../assets/uploads/club/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFileName = 'club_banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                        $bannerPath = 'assets/uploads/club/' . $newFileName;
                    } else {
                        $errors[] = 'Không thể lưu ảnh bìa/banner đã tải lên.';
                    }
                }
            }
        }

        if (empty($errors)) {
            $updateStmt = $pdo->prepare("
                UPDATE Club 
                SET ClubName = :clubName,
                    Description = :description,
                    ContactInfo = :contactInfo,
                    Avatar = :avatar,
                    BannerImage = :banner
                WHERE ClubID = :clubId
            ");
            $updateStmt->execute([
                ':clubName'    => $clubName,
                ':description' => $description,
                ':contactInfo' => $contactInfo,
                ':avatar'      => $avatarPath,
                ':banner'      => $bannerPath,
                ':clubId'      => $club['ClubID']
            ]);

            set_flash('success', 'Cập nhật thông tin Câu lạc bộ thành công!');
            header('Location: ' . base_url('app/modules/club/info.php'));
            exit;
        }
    }
}

$pageTitle = "Chỉnh sửa thông tin CLB — EventFlow";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('app/modules/club/info.php') ?>" class="text-decoration-none text-muted">Thông tin CLB</a></li>
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
                                Dành cho Club Manager & Ban Điều Hành
                            </span>
                            <h1 class="h4 fw-bold mb-1 text-dark">Chỉnh sửa hồ sơ Câu lạc bộ</h1>
                            <p class="text-muted small mb-0">Cập nhật tên, mô tả hoạt động, ảnh đại diện và ảnh bìa chính thức của CLB.</p>
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

                    <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>" enctype="multipart/form-data" novalidate>
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

                        <!-- Ảnh đại diện CLB (Avatar) -->
                        <div class="mb-4">
                            <label for="avatar" class="form-label fw-bold text-dark">Ảnh đại diện CLB (Avatar)</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <?php if (!empty($club['Avatar']) && file_exists(__DIR__ . '/../../../' . $club['Avatar'])): ?>
                                    <img src="<?= base_url($club['Avatar']) ?>" id="currentAvatarPreview" alt="Avatar hiện tại" class="rounded-4 border shadow-sm object-fit-cover" style="width: 80px; height: 80px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center rounded-4 bg-primary text-white" style="width: 80px; height: 80px;">
                                        <i class="bi bi-mortarboard-fill fs-2"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control rounded-3" id="avatar" name="avatar" accept="image/png, image/jpeg, image/jpg, image/webp">
                                    <div class="form-text text-muted">Định dạng JPG, PNG, WEBP; Tối đa 5MB; Tỷ lệ 1:1 vuông (khuyến nghị 400x400px trở lên).</div>
                                </div>
                            </div>
                            <div id="newAvatarPreviewContainer" class="d-none mt-2">
                                <span class="small text-success fw-semibold"><i class="bi bi-eye me-1"></i> Ảnh đại diện mới: </span>
                                <img id="newAvatarPreview" src="#" alt="Preview Avatar" class="rounded-4 border shadow-sm mt-1" style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                        </div>

                        <!-- Ảnh bìa CLB (Banner) -->
                        <div class="mb-4">
                            <label for="banner_image" class="form-label fw-bold text-dark">Ảnh bìa CLB (Banner)</label>
                            <?php if (!empty($club['BannerImage']) && file_exists(__DIR__ . '/../../../' . $club['BannerImage'])): ?>
                                <div class="mb-2">
                                    <img src="<?= base_url($club['BannerImage']) ?>" alt="Banner hiện tại" class="rounded-3 border w-100" style="max-height: 150px; object-fit: cover;">
                                    <div class="small text-muted mt-1">Ảnh bìa hiện tại (tải ảnh mới lên nếu muốn thay đổi)</div>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control rounded-3" id="banner_image" name="banner_image" accept="image/png, image/jpeg, image/jpg, image/webp">
                            <div class="form-text text-muted">Định dạng JPG, PNG, WEBP; Tối đa 5MB; Tỷ lệ ngang (khuyến nghị 1200x400px trở lên).</div>
                            <div id="newBannerPreviewContainer" class="d-none mt-2">
                                <span class="small text-success fw-semibold"><i class="bi bi-eye me-1"></i> Xem trước ảnh bìa mới: </span>
                                <img id="newBannerPreview" src="#" alt="Preview Banner" class="rounded-3 border w-100 mt-1" style="max-height: 150px; object-fit: cover;">
                            </div>
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
                            <a href="<?= base_url('app/modules/club/info.php') ?>" class="btn btn-outline-secondary">
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

<script>
(function() {
    function setupPreview(inputId, containerId, imgId) {
        var input = document.getElementById(inputId);
        var container = document.getElementById(containerId);
        var img = document.getElementById(imgId);
        if (input && container && img) {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        img.src = e.target.result;
                        container.classList.remove('d-none');
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    container.classList.add('d-none');
                }
            });
        }
    }
    setupPreview('avatar', 'newAvatarPreviewContainer', 'newAvatarPreview');
    setupPreview('banner_image', 'newBannerPreviewContainer', 'newBannerPreview');
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
