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

### Giai đoạn 2: Triển khai cải tiến Realtime Điểm danh (Đang thực hiện)
- [x] **[UAT 17]** Cập nhật dữ liệu điểm danh theo thời gian thực (Realtime DOM mà không cần reload trang) tại `app/modules/attendance/checkin.php`:
  - [x] Xử lý thao tác **Điểm danh / Bỏ điểm danh** qua AJAX POST: cập nhật tức thì badge trạng thái, thời gian check-in, đổi nút bấm và bộ đếm tổng người đăng ký (`checked / total`).
  - [x] Xử lý thao tác **Điểm danh bổ sung Walk-in** qua AJAX: tự động cập nhật modal và chèn dòng người tham gia mới vào bảng chính tức thì kèm nhãn "Bổ sung".
  - [x] Thêm **Toast notification** góc màn hình hiển thị kết quả thao tác tức thì và hiệu ứng highlight xanh lá dịu mắt.
  - [x] Tích hợp **Background Polling Realtime Sync (mỗi 3 giây)**: tự động đồng bộ dữ liệu mới nhất nếu có nhiều cán bộ/staff cùng điểm danh trên nhiều thiết bị khác nhau (kèm `session_write_close()` chống lock luồng PHP).
  - [x] Khắc phục triệt để lỗi "Lỗi kết nối máy chủ": do DOM Property Shadowing của thẻ `<input name="action">` đè thuộc tính `form.action` thành `[object HTMLInputElement]`; đã thay thế bằng `form.getAttribute('action') || window.location.href` an toàn tuyệt đối.
  - [x] Sẵn sàng kiểm thử thủ công và chờ chốt để commit `SP7_Fix_UAT17`.

### Giai đoạn 3: Tạm hoãn để bổ sung dữ liệu & Chốt nghiệp vụ
- [ ] **[UAT 05]** Bổ sung đầy đủ cơ cấu Ban điều hành CLB (Chủ nhiệm, Phó chủ nhiệm, Trưởng các ban).
- [ ] **Các mục cần thảo luận & thống nhất:** UAT 02 (Khách vãng lai), UAT 06 & 13 (Điều hướng & Sitemap), UAT 08 & 11 (Phân quyền Staff sự kiện), UAT 09 & 10 (Role Manager/Admin), UAT 14-16 (Gộp màn hình Quản lý & Điểm danh), UAT 18 (Giao diện lịch), UAT 19 (Chuẩn hóa trạng thái), UAT 20 (Branding CLB).
