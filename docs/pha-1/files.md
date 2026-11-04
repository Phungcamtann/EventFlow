# 📁 Pha 1: Nền tảng Kỹ thuật, CSDL & Khung Layout Chuẩn — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành  
> **Ngày bắt đầu:** 04/11/2026  
> **Ngày hoàn thành:** 04/11/2026  
> **Commit hash:** `950e7d7`

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 8 |
| File chỉnh sửa | 0 |
| File xóa | 0 (đã dọn dẹp các thư mục rỗng cũ) |
| **Tổng** | **8** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `database/schema_and_seed.sql` | Kịch bản tạo 7 bảng InnoDB chuẩn 3NF và nạp dữ liệu mẫu ban đầu | ✅ |
| 2 | `config/database.php` | Cấu hình và hàm kết nối CSDL MySQL qua PHP PDO (hỗ trợ cả port 3307 XAMPP và 3306) | ✅ |
| 3 | `includes/session_auth.php` | Khởi tạo Session an toàn, kiểm tra CSRF token, các hàm phân quyền RBAC 2 tầng | ✅ |
| 4 | `assets/css/style.css` | Hệ thống Design Rules CSS, bảng màu Indigo/Emerald/Coral, cards, badges bám sát Figma | ✅ |
| 5 | `assets/js/app.js` | Javascript bổ trợ tương tác UI, flash alert, CSRF tự động | ✅ |
| 6 | `includes/header.php` | Navbar top responsive Bootstrap 5.3 hiển thị theo vai trò người dùng | ✅ |
| 7 | `includes/footer.php` | Chân trang website và nạp các thư viện client-side | ✅ |
| 8 | `index.php` | Trang chủ điều hướng và hiển thị giao diện chào đón theo mẫu Figma 02 | ✅ |

### Chỉnh sửa
*(Không có)*

### Xóa (nếu có)
- Đã dọn dẹp các thư mục rỗng mẫu cũ không thuộc dự án: `backend/`, `frontend/`, `tests/`.

---

## Ghi chú
- Tất cả các trường dữ liệu và Enum đều tuân thủ 100% chuẩn Tiếng Anh theo tài liệu `eventflow_vibe_code_design_specification.html`.
- Mật khẩu mặc định cho các tài khoản seed data là `Password@123`.
