# 📁 Pha 5: Quản lý Sự kiện, Đăng ký & Điểm danh — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành 100%  
> **Ngày bắt đầu:** 06/11/2026  
> **Ngày hoàn thành:** 11/11/2026  
> **Phạm vi hoàn thành:** Bao phủ trọn vẹn từ `SCR08` đến `SCR15`  

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 12 |
| File chỉnh sửa | 4 |
| File xóa | 0 |
| **Tổng** | **16** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `app/includes/event_helpers.php` | Module helper tính trạng thái sự kiện, số chỗ, thời gian MySQL và kiểm tra dữ liệu | ✅ Hoàn thành |
| 2 | `app/modules/events/detail.php` | [SCR09] Màn hình Chi tiết sự kiện, Đăng ký & Hủy đăng ký bảo toàn dữ liệu (FR10, FR12, PB14, PB15) | ✅ Hoàn thành |
| 3 | `app/modules/events/form.php` | [SCR10] Biểu mẫu Tạo mới & Chỉnh sửa sự kiện kèm ràng buộc BR04 & kiểm soát sức chứa (FR09, PB11, PB12) | ✅ Hoàn thành |
| 4 | `app/modules/events/manage.php` | [SCR11] Quản lý danh sách sự kiện nội bộ & Thao tác Hủy sự kiện bảo toàn dữ liệu BR05 (FR09, PB11, PB12) | ✅ Hoàn thành |
| 5 | `app/modules/events/my_events.php` | [SCR12] Hoạt động của tôi: xem danh sách đã đăng ký & kết quả tham dự thực tế (FR12, FR13, PB16) | ✅ Hoàn thành |
| 6 | `app/modules/attendance/_scope.php` | Module xác định phạm vi CLB được phép thao tác điểm danh và xem danh sách đăng ký | ✅ Hoàn thành |
| 7 | `app/modules/attendance/checkin.php` | [SCR14] Màn hình Điểm danh & Bỏ điểm danh (Check-in/Undo) với bộ lọc tức thời client JS (FR15, PB18, PB19) | ✅ Hoàn thành |
| 8 | `app/modules/attendance/registrations.php` | [SCR13] Danh sách đăng ký theo sự kiện, 4 thẻ tóm tắt số chỗ & bộ lọc trạng thái (FR14, PB17, UC11) | ✅ Hoàn thành |
| 9 | `app/modules/attendance/stats.php` | [SCR15] Báo cáo Thống kê hoạt động cơ bản theo công thức BR06 (FR16, PB20, UC13) | ✅ Hoàn thành |
| 10 | `docs/pha-5/plan.md` | Kế hoạch chi tiết, phạm vi và tiêu chí hoàn thành của Pha 5 | ✅ Hoàn thành |
| 11 | `docs/pha-5/files.md` | Tài liệu tổng hợp danh sách file tạo mới/chỉnh sửa và kiểm tra chất lượng Pha 5 | ✅ Hoàn thành |
| 12 | `docs/pha-5/test_cases.md` | Bộ kịch bản kiểm thử toàn diện cho phân hệ Sự kiện, Đăng ký, Điểm danh & Thống kê | ✅ Hoàn thành |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| 1 | `index.php` | [SCR08] Kết nối CSDL lấy danh sách sự kiện, tích hợp helper tính trạng thái động & cảnh báo sắp hết chỗ/hạn | ✅ Hoàn thành |
| 2 | `app/includes/header.php` | Thêm liên kết "Sự kiện của tôi", khôi phục liên kết dropdown "Báo cáo thống kê" và "Điểm danh sự kiện" cho Club Manager | ✅ Hoàn thành |
| 3 | `app/modules/events/manage.php` | Bổ sung nút truy cập nhanh Báo cáo thống kê ở đầu trang và nút Điểm danh sự kiện trực tiếp trên từng dòng | ✅ Hoàn thành |
| 4 | `assets/css/style.css` | Bổ sung class `.badge-cancelled` cho nhãn sự kiện đã hủy | ✅ Hoàn thành |

### Xóa (nếu có)
*(Không có)*

---

## Ghi chú & Kiểm thử tĩnh

- **Kiểm tra cú pháp PHP tĩnh (`php.exe -l`):**
  - `index.php`: `No syntax errors detected`
  - `app/includes/header.php`: `No syntax errors detected`
  - `app/includes/event_helpers.php`: `No syntax errors detected`
  - `app/modules/events/detail.php`: `No syntax errors detected`
  - `app/modules/events/form.php`: `No syntax errors detected`
  - `app/modules/events/manage.php`: `No syntax errors detected`
  - `app/modules/events/my_events.php`: `No syntax errors detected`
  - `app/modules/attendance/_scope.php`: `No syntax errors detected`
  - `app/modules/attendance/checkin.php`: `No syntax errors detected`
  - `app/modules/attendance/registrations.php`: `No syntax errors detected`
  - `app/modules/attendance/stats.php`: `No syntax errors detected`

- **Quy tắc nghiệp vụ được bảo đảm trọn vẹn:**
  - `BR04`: Ràng buộc thời gian tạo/sửa sự kiện (`RegistrationDeadline <= StartTime < EndTime`) và kiểm soát sức chứa `MaxCapacity`.
  - `BR05`: Hủy sự kiện bảo toàn dữ liệu (chuyển `Status = 'Cancelled'`, giữ nguyên toàn bộ lịch sử đăng ký và điểm danh).
  - `BR06`: Công thức thống kê chuẩn hóa (Đăng ký, Có mặt, Vắng mặt, Tỷ lệ tham dự %).
  - `NFR01` (Toàn vẹn dữ liệu): Sử dụng Transaction và `SELECT ... FOR UPDATE` khi xử lý Đăng ký và Check-in, ngăn ngừa hiện tượng vượt quá `MaxCapacity`.
  - Phân quyền RBAC 2 tầng: Kiểm soát chặt chẽ giữa vai trò hệ thống (`Role`) và vai trò nội bộ CLB (`Club_Member.MemberRole`).
