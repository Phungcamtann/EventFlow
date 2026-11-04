<?php
/**
 * EventFlow — Xử lý Đăng xuất
 * Hủy toàn bộ phiên làm việc và điều hướng về trang đăng nhập
 */
require_once __DIR__ . '/../../includes/session_auth.php';

logout_user();

// Khởi tạo lại session để gửi thông báo flash
session_start();
set_flash('info', 'Bạn đã đăng xuất thành công khỏi hệ thống EventFlow.');

header('Location: ' . base_url('app/modules/auth/login.php'));
exit;
