<?php
/**
 * EventFlow — SCR06: Danh sách & Quản lý Thành viên CLB
 * Đáp ứng: FR07, PB08, UC06
 * Phân quyền: Dành riêng cho Club Manager
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session_auth.php';

// Kiểm tra quyền Club Manager
require_role('Club Manager');

$pdo = get_db_connection();

// Thông số tìm kiếm & bộ lọc
$search = trim($_GET['search'] ?? '');
$filterRole = trim($_GET['role'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Xây dựng câu truy vấn động
$sql = "
    SELECT 
        cm.MemberID,
        cm.AccountID,
        cm.ClubID,
        cm.MemberRole,
        cm.Status,
        cm.JoinedDate,
        a.FullName,
        a.StudentCode,
        a.Email,
        a.IsActive AS AccountIsActive,
        r.RoleName AS SystemRoleName,
        (c.ManagerID = a.AccountID) AS IsClubManager
    FROM Club_Member cm
    JOIN Account a ON cm.AccountID = a.AccountID
    JOIN Role r ON a.RoleID = r.RoleID
    LEFT JOIN Club c ON c.ClubID = cm.ClubID
    WHERE cm.ClubID = 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND (a.FullName LIKE :search OR a.StudentCode LIKE :searchCode OR a.Email LIKE :searchEmail)";
    $params[':search'] = '%' . $search . '%';
    $params[':searchCode'] = '%' . $search . '%';
    $params[':searchEmail'] = '%' . $search . '%';
}

if ($filterRole !== '' && in_array($filterRole, ['Board Member', 'Event Supporter', 'Member'], true)) {
    $sql .= " AND cm.MemberRole = :role";
    $params[':role'] = $filterRole;
}

if ($filterStatus !== '' && in_array($filterStatus, ['Active', 'Inactive'], true)) {
    $sql .= " AND cm.Status = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY (c.ManagerID = a.AccountID) DESC, FIELD(cm.MemberRole, 'Board Member', 'Event Supporter', 'Member'), cm.MemberID ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Thống kê nhanh
$statStmt = $pdo->query("
    SELECT 
        COUNT(*) AS Total,
        SUM(CASE WHEN Status = 'Active' THEN 1 ELSE 0 END) AS ActiveCount,
        SUM(CASE WHEN MemberRole = 'Event Supporter' AND Status = 'Active' THEN 1 ELSE 0 END) AS SupporterCount,
        SUM(CASE WHEN MemberRole = 'Board Member' AND Status = 'Active' THEN 1 ELSE 0 END) AS BoardCount
    FROM Club_Member 
    WHERE ClubID = 1
");
$stats = $statStmt->fetch();

$pageTitle = "Danh sách thành viên CLB — EventFlow";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('club/info.php') ?>" class="text-decoration-none text-muted">Thông tin CLB</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Danh sách thành viên</li>
        </ol>
    </nav>

    <!-- Header & Action -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1 text-dark">Quản lý Thành viên CLB</h1>
            <p class="text-muted small mb-0">Theo dõi danh sách, phân vai trò nội bộ và ủy quyền cán bộ hỗ trợ sự kiện.</p>
        </div>
        <div>
            <a href="<?= base_url('club/member_form.php?action=add') ?>" class="btn btn-primary shadow-sm fw-semibold">
                <i class="bi bi-person-plus-fill me-1"></i> Thêm thành viên mới
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="small text-muted mb-1">Tổng thành viên</div>
                <div class="h4 fw-bold text-dark mb-0"><?= number_format((int)$stats['Total']) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="small text-muted mb-1">Đang hoạt động</div>
                <div class="h4 fw-bold text-success mb-0"><?= number_format((int)$stats['ActiveCount']) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="small text-muted mb-1">Ban điều hành</div>
                <div class="h4 fw-bold text-primary mb-0"><?= number_format((int)$stats['BoardCount']) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="small text-muted mb-1">Hỗ trợ sự kiện (Check-in)</div>
                <div class="h4 fw-bold text-info mb-0"><?= number_format((int)$stats['SupporterCount']) ?></div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="row g-3">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control border-start-0 ps-0" 
                            placeholder="Tìm kiếm theo họ tên, MSSV, email..." 
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>
                </div>

                <div class="col-sm-6 col-md-3 col-lg-3">
                    <select name="role" class="form-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="Board Member" <?= $filterRole === 'Board Member' ? 'selected' : '' ?>>Ban Điều Hành</option>
                        <option value="Event Supporter" <?= $filterRole === 'Event Supporter' ? 'selected' : '' ?>>Hỗ Trợ Sự Kiện (Ủy quyền)</option>
                        <option value="Member" <?= $filterRole === 'Member' ? 'selected' : '' ?>>Thành Viên Thường</option>
                    </select>
                </div>

                <div class="col-sm-6 col-md-3 col-lg-2">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="Active" <?= $filterStatus === 'Active' ? 'selected' : '' ?>>Active (Hoạt động)</option>
                        <option value="Inactive" <?= $filterStatus === 'Inactive' ? 'selected' : '' ?>>Inactive (Tạm ngưng)</option>
                    </select>
                </div>

                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Lọc
                    </button>
                    <?php if ($search !== '' || $filterRole !== '' || $filterStatus !== ''): ?>
                        <a href="<?= base_url('club/members.php') ?>" class="btn btn-outline-secondary">
                            Đặt lại
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Member Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom border-light p-3 p-md-4 d-flex justify-content-between align-items-center">
            <h2 class="h5 fw-bold mb-0 text-dark">
                Danh sách thành viên (<?= count($members) ?> kết quả)
            </h2>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4" style="width: 60px;">#</th>
                        <th>Thành viên</th>
                        <th>Email liên lạc</th>
                        <th>Vai trò CLB (Tầng 2)</th>
                        <th>Trạng thái</th>
                        <th>Ngày tham gia</th>
                        <th class="text-end pe-4" style="width: 120px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                Không tìm thấy thành viên nào phù hợp với điều kiện tìm kiếm.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $index => $m): ?>
                            <tr>
                                <td class="ps-4 text-muted small fw-semibold">
                                    <?= $index + 1 ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="ef-avatar-circle" style="width: 40px; height: 40px; font-size: 0.9rem; background-color: <?= $m['IsClubManager'] ? '#312E81' : '#4F46E5' ?>;">
                                            <?= htmlspecialchars(mb_substr($m['FullName'], 0, 1, 'UTF-8')) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                                <?= htmlspecialchars($m['FullName']) ?>
                                                <?php if ($m['IsClubManager']): ?>
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;" title="Chủ nhiệm CLB phụ trách">
                                                        <i class="bi bi-star-fill"></i> Chủ nhiệm
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="small text-muted">
                                                MSSV: <?= htmlspecialchars($m['StudentCode'] ?? 'Chưa cập nhật') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark small"><?= htmlspecialchars($m['Email']) ?></span>
                                </td>
                                <td>
                                    <?php if ($m['MemberRole'] === 'Board Member'): ?>
                                        <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-award-fill me-1"></i> Ban Điều Hành
                                        </span>
                                    <?php elseif ($m['MemberRole'] === 'Event Supporter'): ?>
                                        <span class="badge bg-info-subtle text-info fw-semibold px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-clipboard-check-fill me-1"></i> Hỗ Trợ Sự Kiện
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary fw-normal px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-person me-1"></i> Thành Viên
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($m['Status'] === 'Active'): ?>
                                        <span class="badge bg-success-subtle text-success fw-semibold px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-check-circle-fill me-1"></i> Đang hoạt động
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger fw-semibold px-2.5 py-1.5 rounded-pill">
                                            <i class="bi bi-dash-circle-fill me-1"></i> Tạm ngưng
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <?= !empty($m['JoinedDate']) ? date('d/m/Y', strtotime($m['JoinedDate'])) : '—' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a 
                                        href="<?= base_url('club/member_form.php?id=' . $m['MemberID']) ?>" 
                                        class="btn btn-sm btn-outline-primary fw-semibold"
                                        title="Chỉnh sửa vai trò & trạng thái"
                                    >
                                        <i class="bi bi-pencil-square me-1"></i> Sửa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
