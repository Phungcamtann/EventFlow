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

#### 🚀 Phase 1: Chuẩn Hóa CSDL 11 Bảng & Cơ Cấu BELL CLUB UEH (UAT 05) — ĐÃ HOÀN THÀNH (Đã commit `f2d08b6`)
- [x] Nâng cấp tài liệu kỹ thuật lên **Blueprint v2.0** (`docs/eventflow_vibe_code_design_specification.html`).
- [x] **Chạy Migration CSDL 11 bảng quan hệ (MySQL InnoDB):**
  - [x] Tạo mới 4 bảng: `Department`, `Event_Staff`, `Recruitment_Campaign`, `Recruitment_Application`.
  - [x] Bổ sung các cột mới vào các bảng hiện có (`Account`, `Club`, `Club_Member`, `Event`, `Event_Registration`).
- [x] **Nạp Seed Data chuẩn cho BELL CLUB UEH:**
  - [x] Cập nhật hồ sơ CLB, 4 phòng ban (`HR`, `SPEC`, `COMS`, `ER`) và 10 cán bộ lãnh đạo thực tế từ PDF.
- [x] **Nâng cấp giao diện Trang thông tin CLB (`app/modules/club/info.php`):**
  - [x] Thiết kế theo phong cách hiện đại tham khảo từ `bellclubueh.net`, đồng bộ bảng màu Xanh EventFlow (`assets/css/theme-blue.css`) và font chuẩn Inter/Roboto.
  - [x] Hiển thị Ban Điều Hành (Polaroid cards), 4 Phòng Ban (HR, SPEC, COMS, ER), Giá trị B.E.L.L, Banner Tuyển quân Gen 27.
- [x] Thống nhất bộ font Inter/Roboto toàn hệ thống và giữ nguyên nhận diện nền tảng EventFlow.
- [x] **Đã commit:** `f2d08b6 SP7_Fix_UAT05`.

---

#### 🚀 Phase 2: Dual-Portal Layout & Lịch Tương Tác Notion Calendar (UAT 18, 20, 06, 13) — ĐANG TRIỂN KHAI
- [ ] **Public Portal & Top Navigation (`app/includes/header.php`, `app/includes/footer.php`):**
  - [ ] Chuẩn hóa thanh điều hướng công khai: Trang chủ | Câu Lạc Bộ | Sự kiện | Lịch sự kiện | Đăng nhập / Bàn làm việc BĐH.
  - [ ] Loại bỏ hoàn toàn các nút truy cập trùng lặp ngoài menu: Không để nút Sửa CLB hoặc Điểm danh rải rác trên Top-nav (UAT 06, 13).
  - [ ] Nút "Sửa thông tin CLB" chỉ hiển thị duy nhất trên `app/modules/club/info.php` dạng nút hành động nổi khi đăng nhập là President hoặc Vice President.
- [ ] **Đại tu Trang chủ Công chúng (`index.php`):**
  - [ ] Mở quyền truy cập tự do cho khách vãng lai (không bắt buộc đăng nhập).
  - [ ] Banner Slider (Bootstrap Carousel) trình chiếu 3 slide hoạt động nổi bật của CLB BELL UEH.
  - [ ] Khối 4 Sự Kiện Hot (4 cards dạng lưới hiển thị danh mục, thời gian, địa điểm, trạng thái thời gian thực và CTA Xem chi tiết).
  - [ ] Mini Dot Calendar Widget: Khối lịch tháng thu nhỏ có dấu chấm tròn màu xanh dưới các ngày có sự kiện.
  - [ ] Nút điều hướng "Xem tất cả sự kiện →" dẫn đến trang danh mục công khai.
- [ ] **Trang Danh mục Sự kiện Công khai (`app/modules/events/index.php`):**
  - [ ] Hiển thị danh mục sự kiện công khai với bộ lọc trạng thái (Đang mở ĐK, Sắp mở ĐK...), tìm kiếm theo tên và phòng ban phụ trách.
- [ ] **Mô-đun Lịch Sự Kiện Notion Calendar (`app/modules/events/calendar.php`) (UAT 18):**
  - [ ] Thanh điều khiển: Tiêu đề Tháng/Năm, chuyển tháng/tuần `< >`, nút `Today` quay về hôm nay, nút toggle chế độ xem `Tháng` / `Tuần`.
  - [ ] **Chế độ Lịch Tháng (Month View Grid):**
    - Lưới 7 cột (Sun – Sat), ô ngày có số ngày và danh sách **Event Pills** mang phong cách Notion (Emoji icon, tên rút gọn, pill màu pastel theo phòng ban/trạng thái).
    - Bấm vào pill mở Modal/Offcanvas xem nhanh thông tin sự kiện.
  - [ ] **Chế độ Lịch Tuần (Week / Timeline View):**
    - Trục ngang 7 ngày trong tuần được chọn (đánh dấu ngày Hôm nay nổi bật).
    - Trục dọc từ **07:00 đến 22:00** (15 khung giờ).
    - **Vạch đỏ thời gian thực (Current Time Red Line):** Vạch ngang màu đỏ định vị chính xác vị trí thời gian hiện tại kèm nhãn giờ trực tiếp (VD: `10:15 AM`).
    - Các khối sự kiện được căn vị trí `top` và `height` theo `StartTime` và `EndTime`, màu sắc trực quan theo phòng ban.
  - [ ] Tích hợp API/Dữ liệu JSON tức thời giúp chuyển đổi tháng/tuần không độ trễ.
- [ ] **Khung Giao Diện Workspace (Left-Sidebar) (`app/includes/workspace_layout.php`) & Dashboard (`app/modules/dashboard/index.php`):**
  - [ ] Thanh điều hướng cố định bên trái (Modern Left-Sidebar Navigation) cho Ban Điều Hành: Dashboard, Lịch Notion, Quản trị Sự kiện, Nhân sự & Phòng ban, Tuyển quân, Tài khoản.
  - [ ] Trang Dashboard BĐH (`app/modules/dashboard/index.php`) tổng hợp chỉ số, lối tắt nghiệp vụ và widget Mini Dot Calendar.
- [ ] **Kiểm thử thủ công và chờ người dùng duyệt để commit `SP7_Fix_UAT18_20_06_13`.**

#### ⏳ Phase 3: Unified Event Console & Staffing RBAC & My Events Dual-Tab (UAT 14, 15, 16, 08, 11, 10)
- [ ] Hợp nhất `console.php` với 4 tabs: Tổng quan, Điểm danh Realtime Polling 3s, Phân công BTC (ràng buộc min 2 BĐH), Thống kê.
- [ ] Siết chặt RBAC điểm danh: Chỉ nhân sự trong `Event_Staff` của sự kiện X mới được check-in sự kiện X.
- [ ] Tách "Sự kiện của tôi" thành 2 tabs: Quản lý/Hỗ trợ vs Tham gia.

#### ⏳ Phase 4: Đăng Ký Khách Vãng Lai & Phễu Tuyển Quân Cấp Tài Khoản Tự Động (UAT 02, 09)
- [ ] Đăng ký sự kiện khách vãng lai (Họ tên, MSSV, SĐT, Email) chống trùng lặp & bảo vệ quota.
- [ ] Phễu Tuyển quân: Form nộp hồ sơ CV PDF, không gian chấm điểm phỏng vấn, nút "Duyệt" tự động sinh tài khoản `Ueh@<MSSV>`.
