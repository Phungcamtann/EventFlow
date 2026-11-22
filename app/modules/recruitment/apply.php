<?php
/**
 * EventFlow — Cổng Đăng Ký Tuyển Quân BELL Club (UAT 02)
 * Điều hướng tới Giao diện Trượt Ứng Tuyển tại Trang Đăng Nhập
 */
require_once __DIR__ . '/../../includes/session_auth.php';
header('Location: ' . base_url('app/modules/auth/login.php?mode=apply'));
exit;
