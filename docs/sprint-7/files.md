# 📁 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Danh sách File (Files)

> **Nhánh Git:** `Sprint-7`  
> **Trạng thái:** 🟡 Hoàn tất UAT 07 & UAT 17  
> **Cập nhật:** 21/11/2026  

---

## 1. Tổng hợp tệp tin tác động trong Sprint 7

| # | Đường dẫn file | Mô tả nội dung tác động | Mã UAT liên quan | Trạng thái |
|---|---|---|---|:---:|
| 1 | `app/modules/auth/login.php` | Thêm class chống tràn chữ phụ đề, bo tròn thẻ preview sự kiện responsive | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 2 | `app/modules/auth/register.php` | Thêm class chống tràn chữ phụ đề, fix padding & responsive card | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 3 | `assets/css/style.css` | Bổ sung class `.ef-auth-subtitle`, `overflow-x: hidden`, breakpoint responsive navbar & nút bấm | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 4 | `index.php` | Lọc sự kiện theo `EndTime >= NOW()` và `Status = 'Published'`, chuẩn hóa nhãn trạng thái | UAT 03 | ✅ Đã push (`0e1e431`, `7ba3769`) |
| 5 | `app/modules/profile/index.php` | Khóa trường `StudentCode` (MSSV) chỉ đọc và loại bỏ cập nhật trường này ở backend | UAT 12 | ✅ Đã push (`bfc5a74`) |
| 6 | `app/modules/club/member_form.php` | Tích hợp AJAX search tức thì theo sự kiện `input` (sử dụng `AbortController` chống race condition) theo MSSV/Email và hiển thị card đối chiếu thông tin | UAT 07 | ✅ Đã push (`16dbd8b`) |
| 7 | `app/modules/club/members.php` | Tích hợp lọc & tìm kiếm thành viên tức thì theo sự kiện `input` (dùng `AbortController`) không độ trễ, không tải lại trang | UAT 07 | ✅ Đã push (`16dbd8b`) |
| 8 | `app/modules/attendance/checkin.php` | Tích hợp AJAX Check-in/Undo/Walk-in cập nhật DOM tức thì + Polling Realtime Background Sync (3s) đồng bộ giữa nhiều người dùng; hiển thị trạng thái theo State Machine | UAT 17, UAT 19 | ✅ Đã push (`b75cea1`), 🟡 Đang sửa UAT 19 |
| 9 | `app/includes/event_helpers.php` | Nâng cấp hàm `event_state()` thành State Machine 7 trạng thái theo thời gian thực (Bản nháp, Sắp mở đăng ký, Đang mở đăng ký, Đã đóng đăng ký, Đang diễn ra, Đã kết thúc, Đã hủy) | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 10 | `assets/css/style.css` | Bổ sung bảng màu badge trạng thái mới: `.badge-upcoming`, `.badge-ongoing`, `.badge-finished`, `.badge-cancelled`, `.badge-draft` | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 11 | `app/modules/events/form.php` | Thêm trường `RegistrationStartTime` (Thời gian mở đăng ký), kiểm tra logic ràng buộc và lưu DB | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 12 | `app/modules/events/detail.php` | Hiển thị badge trạng thái chuẩn hóa, thời gian mở/đóng đăng ký và thông báo phù hợp theo 7 trạng thái | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 13 | `app/modules/events/manage.php` | Cập nhật nhãn trạng thái đồng bộ theo State Machine thời gian thực | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 14 | `app/modules/attendance/registrations.php` | Cập nhật nhãn trạng thái đồng bộ State Machine thay cho nhãn "Đang công bố" chung chung | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 15 | `app/modules/attendance/stats.php` | Đồng bộ nhãn trạng thái và chuẩn hóa text bộ lọc "Đã công bố" | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 16 | `database/schema_and_seed.sql` | Bổ sung cột `RegistrationStartTime DATETIME NULL` vào bảng `Event` | UAT 19 | 🟢 Hoàn thành (Chờ test & commit) |
| 17 | `docs/sprint-7/plan.md` | Kế hoạch triển khai Sprint 7 và các hạng mục UAT | - | ✅ Đã cập nhật |
| 18 | `docs/sprint-7/files.md` | Danh sách theo dõi thay đổi tệp tin trong Sprint 7 | - | ✅ Đã cập nhật |
