# 📁 Pha 2: Chức năng & Giao diện Đăng Ký, Đăng Nhập & Phân Quyền — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành  
> **Ngày bắt đầu:** 04/11/2026  
> **Ngày hoàn thành:** 04/11/2026  
> **Gợi ý commit message cho user:** `[Pha 2] feat: hoàn thành giao diện và chức năng đăng ký, đăng nhập 2 cột Figma 01 và phân quyền RBAC`

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 3 |
| File chỉnh sửa | 5 |
| File xóa | 0 |
| **Tổng** | **8** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `auth/register.php` | [SCR01] Giao diện & logic đăng ký tài khoản mới (FR01, PB01) | ✅ |
| 2 | `auth/login.php` | [SCR02] Giao diện đăng nhập 2 cột theo ảnh Figma 01 & logic xác thực phiên (FR02, PB02, PB03) | ✅ |
| 3 | `auth/logout.php` | Xử lý hủy phiên đăng xuất an toàn | ✅ |

### Chỉnh sửa
| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| 1 | `index.php` | Điều chỉnh luồng: Mặc định chuyển hướng sang `auth/login.php` nếu chưa đăng nhập | ✅ |
| 2 | `includes/header.php` | Thêm cờ `$hideNavbar` để ẩn Navbar trên trang đăng nhập và đăng ký | ✅ |
| 3 | `auth/login.php` | Bật cờ ẩn top-nav, tối ưu hóa giao diện | ✅ |
| 4 | `auth/register.php` | Đưa về bố cục 2 khối chuẩn Figma 01 (bên trái banner thương hiệu, bên phải form có bật cuộn `overflow-y`), ẩn top-nav | ✅ |
| 5 | `assets/css/style.css` | Tinh chỉnh thanh cuộn tinh tế (custom scrollbar) cho khối đăng ký | ✅ |

### Xóa (nếu có)
*(Không có)*

---

## Ghi chú
- Đã kiểm tra tĩnh cú pháp (Static syntax check qua `php -l`): 100% không phát sinh lỗi.
- Đã tuân thủ quy tắc người dùng: Không tự động commit mã nguồn.


### Xóa (nếu có)
*(Không có)*

---

## Ghi chú
- Mật khẩu đăng ký yêu cầu tối thiểu 8 ký tự.
- Giao diện đăng nhập tuân thủ chính xác thiết kế Figma 01 do người dùng cung cấp.
- Tuân thủ quy tắc mới: Chỉ test tĩnh và để người dùng tự duyệt commit.
