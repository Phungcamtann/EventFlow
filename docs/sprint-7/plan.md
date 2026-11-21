# 📋 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Kế hoạch thực hiện (Plan)

> **Mục tiêu:** Xử lý các hạng mục phản hồi từ đợt kiểm thử chấp nhận người dùng (UAT Nhóm 8) theo tài liệu **Blueprint v2.0**, chuẩn hóa CSDL 11 bảng, mô phỏng cơ cấu thực tế CLB Tiếng Anh UEH (BELL Club) gồm 4 phòng ban và 10 cán bộ lãnh đạo.  
> **Nhánh Git:** `Sprint-7`  
> **Tài liệu tham chiếu:** *EventFlow Vibe Code Blueprint & Architecture v2.0* (`docs/eventflow_vibe_code_design_specification.html`).

---

## 1. Phân bổ công việc & Trạng thái triển khai

### Giai đoạn 1: Các mục đã hoàn thành & đã commit (Pushed)
- [x] **[UAT 01]** Khắc phục lỗi giao diện trang chủ sát mép và cắt chữ (`commit 9d57c39`).
- [x] **[UAT 03]** Lọc bỏ sự kiện đã qua và sự kiện đã hủy khỏi danh sách "Sự kiện sắp diễn ra" (`commit 0e1e431`, `7ba3769`).
- [x] **[UAT 04]** Tối ưu hóa toàn diện giao diện Mobile, navbar collapse, touch target 44px (`commit 9f2cd57`).
- [x] **[UAT 12]** Khóa trường MSSV trong hồ sơ cá nhân sang chế độ chỉ đọc (Readonly) bảo vệ định danh (`commit bfc5a74`).
- [x] **[UAT 07]** Tìm & lọc thành viên bằng MSSV hoặc Email tức thời theo sự kiện `input` (Zero-delay + `AbortController`) ở cả 2 màn hình Thêm thành viên và Danh sách thành viên (`commit 16dbd8b`).
- [x] **[UAT 17]** Cập nhật dữ liệu điểm danh theo thời gian thực (Realtime DOM & Background Polling Sync 3s) tại `app/modules/attendance/checkin.php` (`commit b75cea1`).
- [x] **[UAT 19]** Xây dựng State Machine 7 trạng thái sự kiện theo thời gian thực + Giờ mở đăng ký (`commit 722a078`).

---

### Giai đoạn 2: Triển khai 4 Phase Sprint 7 (Blueprint v2.0)

#### 🚀 Phase 1: Chuẩn Hóa CSDL 11 Bảng & Cơ Cấu BELL CLUB UEH (UAT 05) — ĐÃ HOÀN THÀNH (SẴN SÀNG COMMIT)
- [x] Nâng cấp tài liệu kỹ thuật lên **Blueprint v2.0** (`docs/eventflow_vibe_code_design_specification.html`).
- [x] **Chạy Migration CSDL 11 bảng quan hệ (MySQL InnoDB):**
  - [x] Tạo mới 4 bảng: `Department` (Phòng ban), `Event_Staff` (Phân công BTC sự kiện), `Recruitment_Campaign` (Đợt tuyển quân), `Recruitment_Application` (Hồ sơ ứng tuyển).
  - [x] Bổ sung các cột mới vào các bảng hiện có:
    - `Account`: Thêm `Phone`, `Cohort` (K48-K52), `Major`, `AvatarUrl`, `PasswordMustChange`.
    - `Club`: Thêm `ShortName`, `Vision`, `Mission`, `Email`, `Phone`, `FacebookUrl`, `Address`.
    - `Club_Member`: Thêm `DepartmentID`, mở rộng `MemberRole` ('President', 'Vice President', 'Head', 'Vice Head', 'Member'), `Bio`.
    - `Event`: Thêm `MinStaffRequired` (mặc định 2).
    - `Event_Registration`: Thêm `RegistrationType` ('Member', 'Guest'), `GuestName`, `GuestEmail`, `GuestPhone`, `GuestStudentCode`, cho phép `AccountID NULL`.
- [x] **Nạp Seed Data chuẩn cho BELL CLUB UEH:**
  - [x] Cập nhật hồ sơ CLB: Tên đầy đủ, Tầm nhìn, Sứ mệnh, Liên hệ, Fanpage, Địa chỉ Cơ sở B.
  - [x] Tạo 4 phòng ban chuyên môn:
    1. `Human Resources (HR)`: Chăm sóc thành viên, gắn kết, tuyển quân, nội bộ.
    2. `Specialist (SPEC)`: Học thuật, Speaking Group (SGP/SGE), No Vietnamese Day.
    3. `Communications (COMS)`: Truyền thông số, hình ảnh, bài viết, video.
    4. `External Relations (ER)`: Quan hệ đối ngoại, nhà tài trợ, diễn giả.
  - [x] Tạo 10 tài khoản & gán thành viên ban lãnh đạo thực tế từ PDF:
    1. **Nguyễn Minh Anh**: President (Chủ nhiệm CLB) - Ban Điều Hành
    2. **Trần Hoàng Nam**: Vice President (Phó Chủ nhiệm CLB) - Ban Điều Hành
    3. **Phạm Thu Trang**: Head of Human Resources
    4. **Nguyễn Văn Bình**: Vice Head of Human Resources
    5. **Trần Minh Khang**: Head of Specialist
    6. **Lê Thảo Vy**: Vice Head of Specialist
    7. **Nguyễn Hoàng Long**: Head of Communications
    8. **Phạm Ngọc Mai**: Vice Head of Communications
    9. **Võ Thành Đạt**: Head of External Relations
    10. **Đặng Khánh Linh**: Vice Head of External Relations
    *(Mật khẩu mặc định toàn bộ tài khoản: `password123`)*
  - [x] Nạp phân công ban tổ chức (`Event_Staff`) mẫu đảm bảo tối thiểu 2 cán bộ lãnh đạo cho các sự kiện hiện có.
- [x] **Nâng cấp giao diện Trang thông tin CLB (`app/modules/club/info.php`):**
  - [x] Áp dụng phong cách bố cục tinh gọn, hiện đại lấy cảm hứng từ cấu trúc phân mục của `bellclubueh.net` nhưng **đồng bộ trọn vẹn theo hệ màu Xanh EventFlow (`assets/css/theme-blue.css`)**, giữ nguyên nhận diện nền tảng **EventFlow**.
  - [x] Giữ nguyên Logo & Định danh hệ thống **EventFlow** trên toàn bộ Header, Top Ticker và Footer.
  - [x] Hiển thị Khối Ban Điều Hành (Executive Board) gồm Chủ nhiệm & Phó Chủ nhiệm dạng thẻ Polaroid với điểm nhấn xanh.
  - [x] Hiển thị 4 Khối Phòng Ban Chuyên Môn (HR, SPEC, COMS, ER) với badge mã phòng, mô tả vai trò, hoạt động trọng tâm và cán bộ phụ trách (Head, Vice Head) theo bảng màu xanh công nghệ.
  - [x] Khối Tinh Thần B.E.L.L: Bonding, English, Learning, Liveliness.
  - [x] Hiển thị đầy đủ Tầm nhìn, Sứ mệnh, Địa điểm Cơ sở B, Kênh liên hệ Fanpage và Banner Tuyển Quân Gen 27.
  - [x] Tối ưu quyền hạn: Chỉ Chủ nhiệm / Phó chủ nhiệm mới hiển thị nút quản trị CLB.
- [x] Cập nhật giao diện `members.php` và `member_form.php` hỗ trợ lọc và chỉ định phòng ban cùng 10 chức danh lãnh đạo.
- [ ] **Kiểm thử thủ công và chờ người dùng duyệt để commit `SP7_Fix_UAT05`.**

---

#### ⏳ Phase 2: Dual-Portal Navigation & Lịch Tương Tác Notion Calendar (UAT 18, 20, 06, 13)
- [ ] Xây dựng Public Top-nav, Banner Slider, Khối 4 Sự kiện Hot, Footer Fanpage.
- [ ] Xây dựng Workspace Left-Sidebar Navigation cố định hiện đại. Dọn sạch các nút truy cập trùng lặp.
- [ ] Xây dựng Notion Calendar (`calendar.php`) với 2 chế độ: Lịch Tháng (Event pills) & Lịch Tuần (Timeline 07:00-22:00 có vạch đỏ giờ thực).

#### ⏳ Phase 3: Unified Event Console & Staffing RBAC & My Events Dual-Tab (UAT 14, 15, 16, 08, 11, 10)
- [ ] Hợp nhất `console.php` với 4 tabs: Tổng quan, Điểm danh Realtime Polling 3s, Phân công BTC (ràng buộc min 2 BĐH), Thống kê.
- [ ] Siết chặt RBAC điểm danh: Chỉ nhân sự trong `Event_Staff` của sự kiện X mới được check-in sự kiện X.
- [ ] Tách "Sự kiện của tôi" thành 2 tabs: Quản lý/Hỗ trợ vs Tham gia.

#### ⏳ Phase 4: Đăng Ký Khách Vãng Lai & Phễu Tuyển Quân Cấp Tài Khoản Tự Động (UAT 02, 09)
- [ ] Đăng ký sự kiện khách vãng lai (Họ tên, MSSV, SĐT, Email) chống trùng lặp & bảo vệ quota.
- [ ] Phễu Tuyển quân: Form nộp hồ sơ CV PDF, không gian chấm điểm phỏng vấn, nút "Duyệt" tự động sinh tài khoản `Ueh@<MSSV>`.
