# 📄 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Danh sách File (Files)

> **Nhánh Git:** `Sprint-7`  
> **Trạng thái:** 🟢 Đã hoàn thành Phase 1 (UAT 05) — Chờ xác nhận để commit `SP7_Fix_UAT05`  
> **Cập nhật:** 21/11/2026  

---

## 1. Tổng hợp tệp tin tác động trong Sprint 7

| # | Đường dẫn file | Mô tả nội dung tác động | Mã UAT liên quan | Trạng thái |
|---|---|---|---|:---:|
| 1 | `app/modules/auth/login.php` | Thêm class chống tràn chữ phụ đề, bo tròn thẻ preview sự kiện responsive | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 2 | `app/modules/auth/register.php` | Thêm class chống tràn chữ phụ đề, fix padding & responsive card | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 3 | `assets/css/theme-blue.css` | Tệp hệ thống biến màu & component CSS chuẩn hóa tông Xanh thống nhất (Royal Blue `#2563eb`, Midnight Navy `#0b192c`, Sky `#38bdf8`, Slate-50 Canvas `#f8fafc`), định nghĩa nút `.btn-ef-primary`, thẻ `.ef-card-editorial`, huy hiệu `.badge-ef-blue` | UAT 05, 20 | 🟢 Mới tạo |
| 4 | `assets/css/style.css` | Tích hợp import `theme-blue.css`, chuẩn hóa biến toàn cục và lớp utility sang tông xanh EventFlow đồng bộ | UAT 01, 04, 05, 19, 20 | 🟢 Sẵn sàng commit |
| 4 | `index.php` | Lọc sự kiện theo `EndTime >= NOW()` và `Status = 'Published'`, chuẩn hóa nhãn trạng thái | UAT 03, UAT 19 | ✅ Đã push (`0e1e431`, `7ba3769`, `722a078`) |
| 5 | `app/modules/profile/index.php` | Khóa trường `StudentCode` (MSSV) chỉ đọc và loại bỏ cập nhật trường này ở backend | UAT 12 | ✅ Đã push (`bfc5a74`) |
| 6 | `app/modules/club/member_form.php` | Bổ sung trường chọn Phòng ban (`DepartmentID`) và cập nhật 10 chức danh nội bộ mới | UAT 05, 07 | 🟢 Sẵn sàng commit |
| 7 | `app/modules/club/members.php` | Thêm cột hiển thị Phòng ban, bộ lọc theo Phòng ban & 10 chức vụ lãnh đạo mới, cập nhật render AJAX | UAT 05, 07 | 🟢 Sẵn sàng commit |
| 8 | `app/modules/attendance/checkin.php` | Tích hợp AJAX Check-in/Undo/Walk-in cập nhật DOM tức thì + Polling Realtime Background Sync (3s) | UAT 17, UAT 19 | ✅ Đã push (`b75cea1`, `722a078`) |
| 9 | `app/includes/event_helpers.php` | Nâng cấp hàm `event_state()` thành State Machine 7 trạng thái theo thời gian thực | UAT 19 | ✅ Đã push (`722a078`) |
| 10 | `app/modules/events/form.php` | Thêm trường `RegistrationStartTime` (Thời gian mở đăng ký), kiểm tra logic ràng buộc và lưu DB | UAT 19 | ✅ Đã push (`722a078`) |
| 11 | `app/modules/events/detail.php` | Hiển thị badge trạng thái chuẩn hóa, thời gian mở/đóng đăng ký | UAT 19 | ✅ Đã push (`722a078`) |
| 12 | `app/modules/events/manage.php` | Cập nhật nhãn trạng thái đồng bộ theo State Machine thời gian thực | UAT 19 | ✅ Đã push (`722a078`) |
| 13 | `app/modules/attendance/registrations.php` | Cập nhật nhãn trạng thái đồng bộ State Machine | UAT 19 | ✅ Đã push (`722a078`) |
| 14 | `app/modules/attendance/stats.php` | Đồng bộ nhãn trạng thái và chuẩn hóa text bộ lọc "Đã công bố" | UAT 19 | ✅ Đã push (`722a078`) |
| 15 | `docs/eventflow_vibe_code_design_specification.html` | Blueprint v2.0 (Dual-Portal, 11 bảng CSDL, 3-tier RBAC, Notion Calendar, lộ trình 4 Phase Sprint 7) | UAT 01-20 | 🟢 Sẵn sàng commit |
| 16 | `database/migrations/migration_uat05.php` | Script migration CSDL: Tạo 4 bảng mới (`Department`, `Event_Staff`, `Recruitment_Campaign`, `Recruitment_Application`) và bổ sung cột cho các bảng hiện có | UAT 05 | 🟢 Sẵn sàng commit |
| 17 | `database/schema_and_seed.sql` | Schema CSDL 11 bảng và seed data chuẩn BELL CLUB UEH (4 phòng ban, 10 lãnh đạo thực tế từ PDF) | UAT 05 | 🟢 Sẵn sàng commit |
| 18 | `app/modules/club/info.php` | Đại tu toàn diện theo phong cách `bellclubueh.net`: Ticker Bar, Hero Beller, B.E.L.L Spirit Acronym, Ban Điều Hành (Chủ nhiệm & Phó Chủ nhiệm), 4 Khối phòng ban (HR, SPEC, COMS, ER) với Head/Vice Head | UAT 05, 06, 13, 20 | 🟢 Sẵn sàng commit |
| 19 | `app/includes/header.php` | Tích hợp Top Ticker Strip, logo typography & brand colors của BELL CLUB UEH | UAT 05, 20 | 🟢 Sẵn sàng commit |
| 20 | `app/includes/footer.php` | Chân trang 3 cột hiện đại với slogan *"Four departments · One BELL family"*, kênh truyền thông, bản quyền | UAT 05, 20 | 🟢 Sẵn sàng commit |
| 21 | `app/includes/session_auth.php` | Cập nhật hàm phân quyền nội bộ CLB hỗ trợ các vai trò `President`, `Vice President`, `Head`, `Vice Head` | UAT 05, 08 | 🟢 Sẵn sàng commit |
| 22 | `docs/sprint-7/plan.md` | Cập nhật tiến độ Phase 1 hoàn tất | UAT 05 | 🟢 Sẵn sàng commit |
| 23 | `docs/sprint-7/files.md` | Danh mục theo dõi tệp tin Sprint 7 | UAT 05 | 🟢 Sẵn sàng commit |
