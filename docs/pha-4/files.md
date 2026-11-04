# 📁 Pha 4: Giao diện Thông tin CLB & Quản lý Danh sách Thành viên — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành  
> **Ngày bắt đầu:** 04/11/2026  
> **Ngày hoàn thành:** 04/11/2026  
> **Gợi ý commit message cho user:** `[Pha 4] feat: hoàn thành phân hệ thông tin CLB và quản lý thành viên (SCR04, SCR05, SCR06, SCR07)`

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 4 |
| File chỉnh sửa | 0 |
| File xóa | 0 |
| **Tổng** | **4** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `club/info.php` | [SCR04] Xem thông tin công khai CLB, số liệu hoạt động, ban chủ nhiệm (FR06, PB06, UC05) | ✅ Hoàn thành |
| 2 | `club/edit.php` | [SCR05] Chỉnh sửa hồ sơ thông tin CLB dành cho Club Manager (FR05, PB07, UC17) | ✅ Hoàn thành |
| 3 | `club/members.php` | [SCR06] Danh sách, tìm kiếm & bộ lọc thành viên CLB dành cho Club Manager (FR07, PB08, UC06) | ✅ Hoàn thành |
| 4 | `club/member_form.php` | [SCR07] Biểu mẫu Thêm/Sửa thành viên, phân quyền Tầng 2 & trạng thái kèm ràng buộc BR03 (FR07, FR08, PB09, PB10, BR03) | ✅ Hoàn thành |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| *(Chưa có)* | | | |

### Xóa (nếu có)
*(Không có)*

---

## Ghi chú & Kiểm thử tĩnh
- **Kiểm tra cú pháp PHP tĩnh (`php.exe -l`):**
  - `club/info.php`: `No syntax errors detected`
  - `club/edit.php`: `No syntax errors detected`
  - `club/members.php`: `No syntax errors detected`
  - `club/member_form.php`: `No syntax errors detected`
- **Quy tắc nghiệp vụ được bảo đảm:**
  - `BR03`: Bảo vệ Club Manager phụ trách cuối cùng (không cho phép vô hiệu hóa Inactive hoặc tự gỡ quyền).
  - RBAC Tầng 2: Hỗ trợ 3 vai trò nội bộ `Board Member`, `Event Supporter`, `Member`.
- **Tuân thủ quy tắc người dùng:**
  - Không tự ý commit mã nguồn (chờ người dùng nghiệm thu và tự commit).
  - Chỉ thực hiện kiểm tra tĩnh (static check), không chạy background task hay daemon ngầm.
