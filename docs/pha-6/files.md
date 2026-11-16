# 📁 Pha 6: Hoàn thiện Cốt lõi MVP (Hồ sơ cá nhân & Điểm danh bổ sung) — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành 100%  
> **Ngày bắt đầu:** 16/11/2026  
> **Ngày hoàn thành:** 16/11/2026  
> **Phạm vi hoàn thành:** Bao phủ trọn vẹn `SCR03` (Hồ sơ cá nhân & Tư cách thành viên) và nâng cấp `SCR14` (Điểm danh bổ sung Walk-in Check-in)  

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 4 |
| File chỉnh sửa | 1 |
| File xóa | 0 |
| **Tổng** | **5** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `app/modules/profile/index.php` | [SCR03] Màn hình Hồ sơ cá nhân, Xem tư cách thành viên CLB, Cập nhật họ tên/MSSV và Đổi mật khẩu an toàn (FR04, PB05, UC04) | ✅ Hoàn thành |
| 2 | `docs/pha-6/plan.md` | Kế hoạch chi tiết, mục tiêu, phạm vi và tiêu chí nghiệm thu của Pha 6 | ✅ Hoàn thành |
| 3 | `docs/pha-6/files.md` | Tài liệu tổng hợp danh sách file tạo mới/chỉnh sửa và kiểm tra chất lượng Pha 6 | ✅ Hoàn thành |
| 4 | `docs/pha-6/test_cases.md` | Bộ kịch bản kiểm thử toàn diện (20 Test Cases) bao phủ Hồ sơ cá nhân và Điểm danh bổ sung | ✅ Hoàn thành |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| 1 | `app/modules/attendance/checkin.php` | [SCR14] Xây dựng logic Backend `att_walkin_checkin()` có Transaction PDO + `FOR UPDATE` bảo toàn sức chứa (`NFR01`); bổ sung nút mở modal, giao diện Modal tìm kiếm nhanh sinh viên và bộ lọc client JS | ✅ Hoàn thành |

### Xóa (nếu có)
*(Không có)*

---

## Ghi chú & Kiểm thử tĩnh (Static Syntax & Code Inspection)

- **Kiểm tra cú pháp PHP tĩnh (`C:\xampp\php\php.exe -l`):**
  - `app/modules/profile/index.php`: `No syntax errors detected`
  - `app/modules/attendance/checkin.php`: `No syntax errors detected`
  - Toàn bộ 23 file PHP trong hệ thống: `100% No syntax errors detected`

- **Quy tắc nghiệp vụ được bảo đảm:**
  - `BR01` & `BR02`: Bảo vệ định danh, mật khẩu băm chuẩn `PASSWORD_BCRYPT`, ràng buộc MSSV duy nhất.
  - `BR04` & `NFR01` (Toàn vẹn dữ liệu): Xử lý Điểm danh bổ sung walk-in được bọc trong Transaction PDO và khóa dòng `SELECT ... FOR UPDATE` trên bảng `Event`, chặn triệt để tình trạng vượt quá sức chứa tối đa (`MaxCapacity`).
  - `PS03` (Hồ sơ cá nhân): Khắc phục dứt điểm lỗi HTTP 404 Not Found từ Top Navbar khi người dùng truy cập mục "Hồ sơ cá nhân".
  - Phân quyền RBAC 2 tầng: Kiểm soát chặt chẽ quyền truy cập và bảo vệ CSRF token cho mọi form thao tác dữ liệu.
