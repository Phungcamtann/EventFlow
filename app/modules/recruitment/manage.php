<?php
/**
 * EventFlow — SCR13: Quản lý Tuyển quân & Phê duyệt Tân Thành Viên (UAT 02, 09)
 * Không gian quản lý dành riêng cho Trưởng phòng HR & President / Ban Chủ Nhiệm.
 * Tích hợp trực tiếp vào Single-Container Workspace: #recruitment
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session_auth.php';

require_login();

if (!can_manage_recruitment()) {
    set_flash('danger', 'Bạn không có quyền truy cập không gian Quản trị Tuyển quân.');
    header('Location: ' . base_url('app/modules/dashboard/index.php#stats'));
    exit;
}

// Chuyển hướng về tab #recruitment trong Không gian làm việc chung (Workspace Container)
header('Location: ' . base_url('app/modules/dashboard/index.php#recruitment'));
exit;
