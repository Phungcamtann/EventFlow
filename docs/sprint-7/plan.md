# 📋 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Kế hoạch thực hiện (Plan)

> **Mục tiêu:** Xử lý các hạng mục phản hồi từ đợt kiểm thử chấp nhận người dùng (UAT Nhóm 8), bao gồm hoàn thiện giao diện responsive, lọc dữ liệu chính xác, khóa trường dữ liệu định danh, cải tiến trải nghiệm tìm kiếm thành viên và tối ưu hóa cập nhật điểm danh tức thì (realtime DOM).  
> **Nhánh Git:** `Sprint-7`  
> **Tài liệu tham chiếu:** Báo cáo *Tổng hợp nhận xét UAT nhóm 8 (UAT 01 đến UAT 20)*.

---

## 1. Phân bổ công việc & Trạng thái triển khai

### Giai đoạn 1: Các mục đã hoàn thành (Pushed)
- [x] **[UAT 01]** Khắc phục lỗi giao diện trang chủ sát mép và cắt chữ (`commit 9d57c39`).
- [x] **[UAT 03]** Lọc bỏ sự kiện đã qua và sự kiện đã hủy khỏi danh sách "Sự kiện sắp diễn ra" (`commit 0e1e431`, `7ba3769`).
- [x] **[UAT 04]** Tối ưu hóa toàn diện giao diện Mobile, navbar collapse, touch target 44px (`commit 9f2cd57`).
- [x] **[UAT 12]** Khóa trường MSSV trong hồ sơ cá nhân sang chế độ chỉ đọc (Readonly) bảo vệ định danh (`commit bfc5a74`).

### Giai đoạn 2: Triển khai cải tiến thao tác & Realtime (Đang thực hiện)
- [x] **[UAT 07]** Tìm & lọc thành viên bằng MSSV hoặc Email tức thì theo sự kiện `input` (Zero-delay + `AbortController`) ở cả 2 màn hình:
  - [x] **Màn hình Thêm thành viên** (`app/modules/club/member_form.php`):
    - Lắng nghe trực tiếp sự kiện thay đổi `input` tại ô tìm kiếm: Fetch ngay lập tức khi gõ phím.
    - Dùng `AbortController` tự động hủy request cũ khi người dùng gõ tiếp, triệt tiêu xung đột dữ liệu.
    - Hiển thị card đối chiếu thông tin người dùng (Họ tên, MSSV, Email, Vai trò hệ thống, Trạng thái thành viên CLB) trước khi xác nhận.
    - Cảnh báo rõ ràng nếu tài khoản đã thuộc CLB hoặc không tìm thấy.
  - [x] **Màn hình Danh sách thành viên** (`app/modules/club/members.php`):
    - Tự động lọc bảng dữ liệu tức thì theo sự kiện `input` (dùng `AbortController`) hoặc thay đổi dropdown Vai trò / Trạng thái mà không cần tải lại trang.
    - Cập nhật số đếm kết quả và nội dung bảng tức thì.
  - [x] Hoàn tất code, sẵn sàng kiểm thử thủ công và chờ chốt để commit `SP7_Fix_UAT07`.
- [ ] **[UAT 17]** Cập nhật dữ liệu điểm danh theo thời gian thực (Realtime DOM mà không cần reload trang) tại `app/modules/attendance/checkin.php`:
  - Xử lý toggle Điểm danh / Bỏ điểm danh qua AJAX fetch.
  - Cập nhật tức thời badge trạng thái (Đã điểm danh / Chưa điểm danh), thời gian check-in và bộ đếm tổng số người điểm danh trên thanh tiến độ.

### Giai đoạn 3: Tạm hoãn để bổ sung dữ liệu & Chốt nghiệp vụ
- [ ] **[UAT 05]** Bổ sung đầy đủ cơ cấu Ban điều hành CLB (Chủ nhiệm, Phó chủ nhiệm, Trưởng các ban).
- [ ] **Các mục cần thảo luận & thống nhất:** UAT 02 (Khách vãng lai), UAT 06 & 13 (Điều hướng & Sitemap), UAT 08 & 11 (Phân quyền Staff sự kiện), UAT 09 & 10 (Role Manager/Admin), UAT 14-16 (Gộp màn hình Quản lý & Điểm danh), UAT 18 (Giao diện lịch), UAT 19 (Chuẩn hóa trạng thái), UAT 20 (Branding CLB).
