# 📁 Pha 3: Chức năng Tài khoản Quản trị — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành  
> **Ngày bắt đầu:** 04/11/2026  
> **Ngày hoàn thành:** 04/11/2026  
> **Gợi ý commit message cho user:** `[Pha 3] feat: hoàn thành chức năng quản trị tài khoản SCR16 và tinh chỉnh hiển thị vai trò 2 tầng`

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 1 |
| File chỉnh sửa | 2 |
| File xóa | 0 |
| **Tổng** | **3** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `admin/accounts.php` | [SCR16] Quản trị tài khoản & quyền hệ thống cho System Administrator (FR17, PB04) | ✅ |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| 1 | `includes/header.php` | Hiển thị rõ vai trò ủy quyền Tầng 2 (Event Supporter / Board Member) trên Navbar | ✅ |
| 2 | `auth/login.php` | Thêm hỗ trợ chuyển đổi tài khoản khi đang có phiên đăng nhập | ✅ |

### Xóa (nếu có)
*(Không có)*

---

## Ghi chú
- Đã test tĩnh cú pháp (Static check qua `php -l`): 100% không phát sinh lỗi.
- Đã tuân thủ quy tắc: Không tự động commit mã nguồn, để người dùng tự duyệt commit.


### Xóa (nếu có)
*(Không có)*

---

## Ghi chú
- Tuân thủ quy tắc: Chỉ test tĩnh và để người dùng tự duyệt commit.
