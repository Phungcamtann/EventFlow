# 📁 Pha 5: Quản lý Sự kiện, Đăng ký & Điểm danh — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành  
> **Ngày bắt đầu:** 06/11/2026  
> **Ngày hoàn thành:** 11/11/2026  
> **Gợi ý commit message cho user:** `[Pha 5] feat: hoàn thành phân hệ sự kiện, đăng ký và điểm danh (SCR08 - SCR14)`

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File tạo mới | 10 |
| File chỉnh sửa | 3 |
| File xóa | 0 |
| **Tổng** | **13** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `app/includes/event_helpers.php` | Module helper tính trạng thái sự kiện, số chỗ, thời gian MySQL và kiểm tra dữ liệu | ✅ Hoàn thành |
| 2 | `app/modules/events/detail.php` | [SCR09] Màn hình Chi tiết sự kiện, Đăng ký & Hủy đăng ký bảo toàn dữ liệu (FR10, FR12, PB14, PB15) | ✅ Hoàn thành |
| 3 | `app/modules/events/form.php` | [SCR10] Biểu mẫu Tạo mới & Chỉnh sửa sự kiện kèm ràng buộc thời gian & số chỗ (FR09, PB11, PB12) | ✅ Hoàn thành |
| 4 | `app/modules/events/manage.php` | [SCR11] Quản lý danh sách sự kiện nội bộ & Thao tác Hủy sự kiện của Club Manager (FR09, PB11, PB12) | ✅ Hoàn thành |
| 5 | `app/modules/events/my_events.php` | [SCR12] Hoạt động của tôi: xem danh sách đã đăng ký & kết quả tham dự (FR12, FR13, PB16) | ✅ Hoàn thành |
| 6 | `app/modules/attendance/_scope.php` | Module xác định phạm vi CLB được phép thao tác điểm danh cho Manager và Supporter | ✅ Hoàn thành |
| 7 | `app/modules/attendance/checkin.php` | [SCR13, SCR14] Danh sách đăng ký & Điểm danh / Bỏ điểm danh với bộ lọc tức thời (FR14, FR15, PB17, PB18, PB19) | ✅ Hoàn thành |
| 8 | `docs/pha-5/plan.md` | Kế hoạch chi tiết, phạm vi và tiêu chí hoàn thành của Pha 5 | ✅ Hoàn thành |
| 9 | `docs/pha-5/files.md` | Tài liệu tổng hợp danh sách file tạo mới/chỉnh sửa và kiểm tra chất lượng Pha 5 | ✅ Hoàn thành |
| 10 | `docs/pha-5/test_cases.md` | Bộ 31 kịch bản kiểm thử toàn diện cho phân hệ Sự kiện, Đăng ký & Điểm danh | ✅ Hoàn thành |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| 1 | `index.php` | [SCR08] Kết nối CSDL lấy danh sách sự kiện, tích hợp helper tính trạng thái động & cảnh báo sắp hết chỗ/hạn | ✅ Hoàn thành |
| 2 | `app/includes/header.php` | Thêm liên kết "Sự kiện của tôi", cập nhật liên kết dropdown Điểm danh sự kiện cho Quản trị viên và Thành viên hỗ trợ | ✅ Hoàn thành |
| 3 | `assets/css/style.css` | Bổ sung class `.badge-cancelled` cho nhãn sự kiện đã hủy | ✅ Hoàn thành |

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

- **Quy tắc nghiệp vụ được bảo đảm:**
  - `NFR01` (Toàn vẹn dữ liệu): Sử dụng Transaction và `SELECT ... FOR UPDATE` khi xử lý Đăng ký và Check-in, ngăn ngừa hiện tượng vượt quá `MaxCapacity` khi có đăng ký đồng thời.
  - `FR09 & FR12` (Bảo toàn dữ liệu): Hủy sự kiện hoặc hủy đăng ký chỉ chuyển trạng thái sang `Cancelled`, không xóa bản ghi khỏi CSDL.
  - Ràng buộc thời gian: `EndTime > StartTime` và `RegistrationDeadline <= StartTime`.
  - Phân quyền RBAC 2 tầng: Tích hợp đầy đủ kiểm tra vai trò hệ thống (`Role`) và vai trò nội bộ CLB (`Club_Member.MemberRole`).
  - Bảo mật: 100% truy vấn dùng PDO Prepared Statements chống SQL Injection, xác thực CSRF Token trên mọi form POST, dùng `htmlspecialchars()` chống XSS.
