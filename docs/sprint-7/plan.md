# 📋 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Kế hoạch thực hiện (Plan)

> **Mục tiêu:** Xử lý các hạng mục phản hồi từ đợt kiểm thử chấp nhận người dùng (UAT Nhóm 8), bao gồm hoàn thiện giao diện responsive, lọc dữ liệu chính xác, khóa trường dữ liệu định danh, cải tiến trải nghiệm tìm kiếm thành viên và tối ưu hóa cập nhật điểm danh tức thì (realtime DOM).  
> **Nhánh Git:** `Sprint-7`  
> **Tài liệu tham chiếu:** Báo cáo *Tổng hợp nhận xét UAT nhóm 8 (UAT 01 đến UAT 20)*.

---

## 1. Phân bổ công việc & Trạng thái triển khai

### Giai đoạn 1: Các mục đã hoàn thành & đã commit (Pushed)
- [x] **[UAT 01]** Khắc phục lỗi giao diện trang chủ sát mép và cắt chữ (`commit 9d57c39`).
- [x] **[UAT 03]** Lọc bỏ sự kiện đã qua và sự kiện đã hủy khỏi danh sách "Sự kiện sắp diễn ra" (`commit 0e1e431`, `7ba3769`).
- [x] **[UAT 04]** Tối ưu hóa toàn diện giao diện Mobile, navbar collapse, touch target 44px (`commit 9f2cd57`).
- [x] **[UAT 12]** Khóa trường MSSV trong hồ sơ cá nhân sang chế độ chỉ đọc (Readonly) bảo vệ định danh (`commit bfc5a74`).
- [x] **[UAT 07]** Tìm & lọc thành viên bằng MSSV hoặc Email tức thì theo sự kiện `input` (Zero-delay + `AbortController`) ở cả 2 màn hình Thêm thành viên và Danh sách thành viên (`commit 16dbd8b`).
- [x] **[UAT 17]** Cập nhật dữ liệu điểm danh theo thời gian thực (Realtime DOM & Background Polling Sync 3s) tại `app/modules/attendance/checkin.php` (`commit b75cea1`).

### Giai đoạn 2: Chuẩn hóa Máy trạng thái sự kiện đa tầng theo thời gian thực (UAT 19 - Đang thực hiện)
- [x] **[UAT 19]** Xây dựng State Machine 7 trạng thái sự kiện theo thời gian thực:
  - [x] `Bản nháp`: Sự kiện ở trạng thái `Draft`, chưa hiển thị cho người dùng ngoài CLB.
  - [x] `Sắp mở đăng ký`: Sự kiện đã công bố nhưng có hẹn giờ `RegistrationStartTime > NOW()`.
  - [x] `Đang mở đăng ký`: Trong giai đoạn nhận đăng ký (chưa quá hạn và còn chỗ).
  - [x] `Đã đóng đăng ký`: Quá hạn `RegistrationDeadline` hoặc đã đủ số lượng `MaxCapacity`.
  - [x] `Đang diễn ra`: Thời gian thực tế đang nằm giữa `StartTime` và `EndTime`.
  - [x] `Đã kết thúc`: Thời gian thực tế đã qua `EndTime`.
  - [x] `Đã hủy`: Sự kiện có trạng thái `Cancelled`.
  - [x] Nâng cấp database: Thêm cột `RegistrationStartTime` (DATETIME NULL) vào bảng `Event` và file `schema_and_seed.sql`.
  - [x] Cập nhật form Tạo/Sửa sự kiện (`form.php`): Cho phép Manager đặt thời gian mở đăng ký hoặc để trống mở ngay.
  - [x] Đồng bộ giao diện & nhãn badge trên toàn bộ hệ thống: `index.php`, `detail.php`, `manage.php`, `checkin.php`, `registrations.php`, `stats.php`.
  - [x] Bổ sung bộ CSS badge chuẩn: `.badge-upcoming`, `.badge-ongoing`, `.badge-finished`, `.badge-cancelled`, `.badge-draft` tại `assets/css/style.css`.
  - [x] Sẵn sàng kiểm thử thủ công và chờ chốt để commit `SP7_Fix_UAT19`.

### Giai đoạn 3: Tạm hoãn để bổ sung dữ liệu & Chốt nghiệp vụ
- [ ] **[UAT 05]** Bổ sung đầy đủ cơ cấu Ban điều hành CLB (Chủ nhiệm, Phó chủ nhiệm, Trưởng các ban).
- [ ] **Các mục cần thảo luận & thống nhất:** UAT 02 (Khách vãng lai), UAT 06 & 13 (Điều hướng & Sitemap), UAT 08 & 11 (Phân quyền Staff sự kiện), UAT 09 & 10 (Role Manager/Admin), UAT 14-16 (Gộp màn hình Quản lý & Điểm danh), UAT 18 (Giao diện lịch), UAT 20 (Branding CLB).
