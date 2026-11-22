# 📄 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Danh sách File (Files)

> **Nhánh Git:** `Sprint-7`  
> **Trạng thái:** 🟡 Đang thực hiện Phase 2: Dual-Portal Layout & Lịch Tương Tác Notion Calendar (UAT 18, 20, 06, 13)  
> **Cập nhật:** 21/11/2026  

---

## 1. Tổng hợp tệp tin tác động trong Sprint 7

| # | Đường dẫn file | Mô tả nội dung tác động | Mã UAT liên quan | Trạng thái |
|---|---|---|---|:---:|
| 1 | `assets/css/theme-blue.css` | Tệp hệ thống biến màu & component CSS chuẩn hóa tông Xanh thống nhất (Royal Blue `#2563eb`, Midnight Navy `#0b192c`, Sky `#38bdf8`, Slate-50 Canvas `#f8fafc`), định nghĩa nút `.btn-ef-primary`, thẻ `.ef-card-editorial`, huy hiệu `.badge-ef-blue`, font Inter/Roboto | UAT 05, 20 | ✅ Đã commit (`f2d08b6`) |
| 2 | `assets/css/style.css` | Tích hợp import `theme-blue.css`, chuẩn hóa biến toàn cục và lớp utility sang tông xanh EventFlow đồng bộ, hỗ trợ layout Notion Calendar & Workspace | UAT 01, 04, 05, 18, 19, 20 | 🟡 Đang cập nhật |
| 3 | `app/includes/header.php` | Chuẩn hóa thanh điều hướng công khai (Trang chủ, Câu Lạc Bộ, Sự kiện, Lịch sự kiện, Cổng đăng nhập/Workspace), dọn sạch nút lặp Sửa CLB/Điểm danh | UAT 06, 13, 20 | 🟡 Đang cập nhật |
| 4 | `app/includes/footer.php` | Chân trang EventFlow nền tảng UEH với slogan, kênh Fanpage Facebook chính thức | UAT 05, 20 | ✅ Đã commit (`f2d08b6`) |
| 5 | `index.php` | Mở truy cập công khai cho khách vãng lai, bổ sung Banner Slider (Carousel), Khối 4 Sự kiện Hot, Mini Dot Calendar widget, liên kết Fanpage | UAT 02, 18, 20 | 🟡 Đang cập nhật |
| 6 | `app/modules/events/index.php` | SCR05: Danh mục sự kiện công khai với bộ lọc trạng thái và tìm kiếm AJAX | UAT 02, 19, 20 | 🟡 Đang cập nhật |
| 7 | `app/modules/events/calendar.php` | SCR07: Giao diện Lịch sự kiện Notion Calendar tương tác cao: Lịch Tháng (Event pills) & Lịch Tuần (Timeline 07:00-22:00 có vạch đỏ giờ thực) | UAT 18, 20 | 🟡 Đang tạo mới |
| 8 | `app/includes/workspace_layout.php` | SCR08: Khung giao diện Workspace (Left-Sidebar Navigation cố định) dành cho Ban Điều Hành & Thành viên CLB | UAT 20, 06, 13 | 🟡 Đang tạo mới |
| 9 | `app/modules/dashboard/index.php` | SCR09: Workspace Dashboard tổng hợp chỉ số hoạt động, lối tắt quản trị và widget Mini Dot Calendar | UAT 18, 20 | 🟡 Đang tạo mới |
| 10 | `app/modules/club/info.php` | Hiển thị thông tin CLB & 4 phòng ban, nút Sửa CLB chỉ hiển thị duy nhất tại đây khi đăng nhập BĐH | UAT 05, 06, 13, 20 | ✅ Đã commit (`f2d08b6`) |
| 11 | `docs/sprint-7/plan.md` | Cập nhật kế hoạch chi tiết Phase 2 & Phase 3 | UAT 18, 20, 06, 13, 14, 15, 16, 08, 11, 10 | ✅ Đã cập nhật |
| 12 | `docs/sprint-7/files.md` | Cập nhật danh mục theo dõi tệp tin Phase 2 & Phase 3 | UAT 18, 20, 06, 13, 14, 15, 16, 08, 11, 10 | ✅ Đã cập nhật |
| 13 | `app/modules/events/console.php` | SCR12: Unified Event Console hợp nhất 4 tabs (Tổng quan, Điểm danh realtime polling 3s, Ban tổ chức, Thống kê), xuất CSV và Walk-in check-in | UAT 14, 15, 16, 08, 11, 17 | 🟢 Sẵn sàng duyệt |
| 14 | `app/modules/events/my_events.php` | SCR13: Tách rõ 2 tab "Sự kiện tôi điều hành/hỗ trợ" (Event_Staff) vs "Sự kiện tôi tham gia" (Participant) | UAT 10 | 🟢 Sẵn sàng duyệt |
| 15 | `app/modules/events/form.php` | SCR11: Ràng buộc bắt buộc chọn tối thiểu 2 cán bộ BĐH khi tạo sự kiện mới, lưu vào `Event_Staff` | UAT 10 | 🟢 Sẵn sàng duyệt |
| 16 | `app/includes/event_helpers.php` | Bổ sung hàm kiểm tra quyền RBAC tầng 3 `can_user_checkin_event()` và lấy danh sách Event Staff | UAT 08, 11 | 🟢 Sẵn sàng duyệt |
| 17 | `app/modules/attendance/checkin.php` | Siết chặt RBAC Tầng 3, chỉ cho phép cán bộ trong `Event_Staff` hoặc Manager của sự kiện mở bàn điểm danh | UAT 08, 11, 17 | 🟢 Sẵn sàng duyệt |
| 18 | `app/modules/attendance/stats.php` | Bổ sung nút truy cập trực tiếp vào Event Console cho từng sự kiện | UAT 14, 16 | 🟢 Sẵn sàng duyệt |
| 19 | `database/schema_and_seed.sql` | Nạp dữ liệu mẫu ban tổ chức `Event_Staff` cho toàn bộ 5 sự kiện đạt chuẩn tối thiểu 2 BĐH | UAT 10 | 🟢 Sẵn sàng duyệt |
