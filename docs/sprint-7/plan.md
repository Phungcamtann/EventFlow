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

#### 🚀 Phase 2: Dual-Portal Layout & Lịch Tương Tác Notion Calendar (UAT 18, 20, 06, 13) — ĐÃ HOÀN THÀNH (Đã commit `c42e97d`, `bb34408`)
- [x] **Public Portal & Top Navigation (`app/includes/header.php`, `app/includes/footer.php`):**
  - [x] Chuẩn hóa thanh điều hướng công khai: Trang chủ | Câu Lạc Bộ | Sự kiện | Đăng nhập / Quản lý CLB.
  - [x] Loại bỏ hoàn toàn các nút truy cập trùng lặp ngoài menu: Không để nút Sửa CLB hoặc Điểm danh rải rác trên Top-nav (UAT 06, 13).
  - [x] Tối ưu trải nghiệm Member: Không có nút "Sự kiện của tôi" trên Top Nav; truy cập qua Avatar mở trang truyền thống không Left-Sidebar.
- [x] **Đại tu Trang chủ Công chúng (`index.php`):**
  - [x] Mở quyền truy cập tự do cho khách vãng lai (không bắt buộc đăng nhập).
  - [x] Banner Slider (Bootstrap Carousel) trình chiếu 3 slide hoạt động nổi bật của CLB BELL UEH.
  - [x] Khối 4 Sự Kiện Hot (4 cards dạng lưới hiển thị danh mục, thời gian, địa điểm, trạng thái thời gian thực và CTA Xem chi tiết).
  - [x] Mini Dot Calendar Widget: Khối lịch tháng thu nhỏ có dấu chấm tròn màu xanh dưới các ngày có sự kiện.
  - [x] Nút điều hướng "Xem tất cả sự kiện →" dẫn đến trang danh mục công khai.
- [x] **Trang Danh mục Sự kiện Công khai (`app/modules/events/index.php`):**
  - [x] Hiển thị danh mục sự kiện công khai với bộ lọc trạng thái (Đang mở ĐK, Sắp mở ĐK...), tìm kiếm theo tên và phòng ban phụ trách.
- [x] **Mô-đun Lịch Sự Kiện Notion Calendar (`app/modules/events/calendar.php`) (UAT 18):**
  - [x] Loại bỏ toàn bộ từ ngữ "Notion", chuẩn hóa thành "Lịch Sự Kiện" và "Lịch Hoạt Động CLB".
  - [x] Chế độ Lịch Tháng (Event Pills) và Lịch Tuần (Timeline 07:00 – 22:00 có vạch đỏ giờ thực).
- [x] **Khung Giao Diện Workspace & Single Container (`app/modules/dashboard/index.php`):**
  - [x] Left-Sidebar Navigation trượt nổi phong cách Midnight Navy.
  - [x] Hợp nhất mục "Thông tin CLB" (gộp hồ sơ tổng quan và form cập nhật vào một khối).
  - [x] Xóa bỏ nút "Ra Cổng Công Chúng" và nút "Mở trang độc lập".
- [x] **Đã commit:** `c42e97d SP7_Fix_UAT18_20_06_13_Part1` và `bb34408 SP7_Fix_UAT18_20_06_13_Part2`.

---

#### 🚀 Phase 3: Unified Event Console & Staffing RBAC & My Events Dual-Tab (UAT 14, 15, 16, 08, 11, 10) — ĐÃ HOÀN THÀNH (Đã commit `b57125d`)
- [x] **Xây dựng Unified Event Console (`app/modules/dashboard/index.php#event-console` & `console.php`):**
  - [x] Nhúng trực tiếp khối Điều Hành Console vào Workspace Single Container giữ trọn vẹn Left Menu Midnight Navy và Top-Nav.
  - [x] Hợp nhất 3 tabs cốt lõi dàn đều 33.33%: Thông tin & Chỉnh sửa (Tab 1), Điểm danh Realtime Polling 3s (Tab 2), Ban Tổ Chức & Phân công cán bộ (Tab 3).
  - [x] Loại bỏ nút cây bút trùng lặp ở bảng sự kiện (chỉ giữ nút Sliders Console và Con Mắt xem công khai).
  - [x] Loại bỏ nút "+ Tạo sự kiện mới" trùng lặp trên header của bảng sự kiện.
  - [x] Giữ nguyên active xanh trên Left Menu "Quản lý Sự kiện" khi mở Console.
  - [x] Xuất dữ liệu điểm danh sang file CSV (UTF-8 BOM hỗ trợ Excel tiếng Việt).
  - [x] Bàn điểm danh bổ sung Walk-in check-in tích hợp tra cứu tức thì (Modal popup AJAX).
- [x] **Tính năng mới: Điểm danh ca trực Ban Tổ Chức (Staff Shift Check-in):**
  - [x] Tích hợp trực tiếp vào Tab 3 Console: Băng kiểm diện quân số trực ca, nút "Điểm danh tất cả", và nút Check-in ca trực 1-click cho từng cán bộ.
  - [x] Badge hiển thị sĩ số có mặt ngay trên tiêu đề Tab 3 (`X/Y có mặt`).
  - [x] Hiển thị nhãn `✓ Đã trực ca` tại trang "Sự kiện của tôi" (Tab Sự kiện tôi điều hành).
- [x] **Siết chặt RBAC tầng 3:**
  - [x] Chỉ cán bộ được chỉ định trong `Event_Staff` hoặc Club Manager / Admin của sự kiện X mới được mở bàn điểm danh và check-in cho sự kiện X.
  - [x] Chặn trái quyền ở cả `console.php` và `checkin.php`.
- [x] **Ràng buộc tối thiểu 2 cán bộ BĐH (UAT 10):**
  - [x] Bắt buộc chọn $\ge 2$ cán bộ BĐH khi tạo sự kiện mới ở cả `form.php` và `dashboard/index.php`.
  - [x] Khóa chức năng xóa cán bộ nếu sự kiện chỉ còn đúng 2 cán bộ BĐH phụ trách.
- [x] **Tách "Sự kiện của tôi" thành 2 tabs rõ ràng:**
  - [x] "Sự kiện tôi điều hành / hỗ trợ" (Event_Staff) với nút mở bàn điều hành Console và trạng thái trực ca.
  - [x] "Sự kiện tôi tham gia" (Participant) với trạng thái điểm danh thực tế.
  - [x] Áp dụng đồng bộ ở cả `app/modules/events/my_events.php` và `app/modules/dashboard/index.php#my-events`.

#### ⏳ Phase 4: Đăng Ký Khách Vãng Lai & Phễu Tuyển Quân Cấp Tài Khoản Tự Động (UAT 02, 09)
- [ ] Đăng ký sự kiện khách vãng lai (Họ tên, MSSV, SĐT, Email) chống trùng lặp & bảo vệ quota.
- [ ] Phễu Tuyển quân: Form nộp hồ sơ CV PDF, không gian chấm điểm phỏng vấn, nút "Duyệt" tự động sinh tài khoản `Ueh@<MSSV>`.
