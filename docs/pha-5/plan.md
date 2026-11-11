# 📋 Pha 5: Quản lý Sự kiện, Đăng ký & Điểm danh — Kế hoạch thực hiện

> **Mục tiêu:** Xây dựng hoàn chỉnh phân hệ Quản lý Sự kiện, Đăng ký tham gia, Điểm danh (Check-in) và Báo cáo Thống kê bao gồm: Xem danh sách sự kiện (`SCR08` - `index.php`), Chi tiết sự kiện & Đăng ký/Hủy đăng ký (`SCR09` - `events/detail.php`), Biểu mẫu Tạo/Sửa sự kiện (`SCR10` - `events/form.php`), Quản lý danh sách sự kiện & Hủy sự kiện (`SCR11` - `events/manage.php`), Hoạt động của tôi & Lịch sử tham gia (`SCR12` - `events/my_events.php`), Quản lý danh sách đăng ký theo sự kiện (`SCR13` - `attendance/registrations.php`), Điểm danh & Bỏ điểm danh (`SCR14` - `attendance/checkin.php`), và Báo cáo thống kê hoạt động cơ bản (`SCR15` - `attendance/stats.php`).  
> **Phụ thuộc:** Pha 1, Pha 2, Pha 3 và Pha 4 đã hoàn thành.  
> **Tham chiếu:** `docs/eventflow_readme.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html` (SCR08–SCR15 / FR09, FR10, FR12, FR13, FR14, FR15, FR16 / PB11–PB20 / BR04, BR05, BR06 / NFR01, NFR02, NFR03, NFR05, NFR07).

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)

Phân bổ theo các User Story / Task trên Jira của Sprint 5:

#### Task 1: `[SP5 - 01]` & `[SP5 - 03]` — Tạo, Cập nhật và Hủy sự kiện (`SCR10`, `SCR11` / FR09, PB11, PB12, BR04, BR05)
- [x] **`SCR10` - Biểu mẫu Tạo / Sửa sự kiện (`events/form.php`):**
  - Quyền truy cập: `Club Manager` (`require_role('Club Manager')`).
  - Gắn sự kiện tự động với CLB mà tài khoản hiện tại phụ trách.
  - Kiểm tra tính hợp lệ dữ liệu (Validation): Tên sự kiện, Địa điểm, Thời gian bắt đầu/kết thúc, Hạn đăng ký.
  - **Ràng buộc thời gian nghiệp vụ (BR04):** `EndTime > StartTime` và `RegistrationDeadline <= StartTime`.
  - **Ràng buộc sức chứa:** Khóa dòng bằng `SELECT ... FOR UPDATE`. Khi chỉnh sửa, không được giảm `MaxCapacity` nhỏ hơn số lượng người đang đăng ký thực tế (`RegisteredCount`); Không cho phép chỉnh sửa sự kiện đã bị hủy (`Cancelled`).
  - Hỗ trợ lưu ở chế độ Bản nháp (`Draft`) hoặc Công bố ngay (`Published`).
- [x] **`SCR11` - Quản lý danh sách sự kiện nội bộ & Hủy sự kiện (`events/manage.php`):**
  - Hiển thị toàn bộ các sự kiện của CLB (bao gồm cả `Draft`, `Published`, `Cancelled`) sắp xếp theo thời gian mới nhất.
  - Nút Hủy sự kiện (`action=cancel_event`): Chỉ hủy sự kiện `Published` và chưa kết thúc (`NOW() < EndTime`). Dùng Transaction + `FOR UPDATE`.
  - **Bảo toàn dữ liệu nghiệp vụ (BR05):** Chuyển `Event.Status = 'Cancelled'`, tuyệt đối giữ nguyên toàn bộ lịch sử `Event_Registration` và `Attendance`.
  - Bổ sung nút truy cập nhanh: Thống kê (`stats.php`) và Điểm danh (`checkin.php`).

#### Task 2: `[SP5 - 02]` & `[SP5 - 04]` — Danh sách, Chi tiết sự kiện & Đăng ký / Hủy đăng ký (`SCR08`, `SCR09`, `SCR12` / FR10, FR12, FR13, PB13, PB14, PB15, PB16)
- [x] **`SCR08` - Danh sách sự kiện công khai (`index.php`):**
  - Hiển thị danh sách sự kiện kèm trạng thái động suy luận từ `event_state()`: *Đang mở đăng ký, Đã đóng đăng ký, Đã hủy*.
  - Hiển thị cảnh báo khẩn cấp: "Chỉ còn X chỗ" ($\le 5$ chỗ), "Sắp hết hạn đăng ký" ($\le 48\text{h}$).
  - Hiển thị tỷ lệ đăng ký (`RegisteredCount / MaxCapacity`).
- [x] **`SCR09` - Chi tiết sự kiện, Đăng ký & Hủy đăng ký (`events/detail.php`):**
  - Quyền Đăng ký/Hủy: Dành cho `Club Member` và `Event Participant`.
  - Đăng ký (`action=register`): Khóa dòng bằng `SELECT ... FOR UPDATE` trên bảng `Event` và `Event_Registration`. Kiểm tra hạn chót, kiểm tra `MaxCapacity` chống race condition (`NFR01`). Tái kích hoạt dòng cũ nếu từng hủy đăng ký.
  - Hủy đăng ký (`action=cancel_registration`): Cho phép hủy trước giờ bắt đầu sự kiện và chưa được điểm danh. Chuyển `Status = 'Cancelled'` bảo toàn dữ liệu.
- [x] **`SCR12` - Hoạt động của tôi & Lịch sử tham gia (`events/my_events.php`):**
  - Hiển thị danh sách sự kiện cá nhân đã đăng ký và nhãn trạng thái tham dự rõ ràng (`NFR05`): *Đã đăng ký, Đã tham dự, Vắng mặt, Đã hủy đăng ký, Sự kiện đã hủy*.

#### Task 3: `[SP5 - 05]` & `[SP5 - 06]` — Danh sách đăng ký, Điểm danh và Báo cáo thống kê (`SCR13`, `SCR14`, `SCR15` / FR14, FR15, FR16, PB17, PB18, PB19, PB20, BR06)
- [x] **`SCR13` - Danh sách đăng ký theo sự kiện (`attendance/registrations.php`):**
  - Quyền: `Club Manager` & `Authorized Club Member` (`require_authorized_club_member()`).
  - Kiểm soát phạm vi CLB qua `_scope.php`.
  - Chế độ chọn sự kiện và chế độ xem chi tiết danh sách người đăng ký.
  - 4 Thẻ KPI: Đăng ký hợp lệ, Sức chứa tối đa, Số chỗ còn trống, Đã điểm danh.
  - Bảng danh sách chi tiết: Họ tên, MSSV, Email, Ngày đăng ký, Trạng thái đăng ký, Trạng thái điểm danh.
  - Bộ lọc radio (Tất cả / Hợp lệ / Đã hủy) và ô tìm kiếm tức thời Client JS không dấu.
  - Nút chuyển tiếp nhanh sang màn hình Điểm danh `checkin.php`.
- [x] **`SCR14` - Điểm danh (Check-in) sự kiện (`attendance/checkin.php`):**
  - Tìm kiếm nhanh tức thời theo Họ tên hoặc MSSV ($\le 2\text{s}$ đạt `NFR03`).
  - Thao tác Điểm danh (`action=checkin`): Ghi vào bảng `Attendance` với `AttendanceStatus = 1`, lưu `CheckedInBy`.
  - Thao tác Bỏ điểm danh (`action=undo`): Cho phép nhân sự hoàn tác khi bấm nhầm.
  - Chống điểm danh trùng lặp ở cả tầng ứng dụng và ràng buộc `UNIQUE(RegID)`.
- [x] **`SCR15` - Báo cáo Thống kê hoạt động cơ bản (`attendance/stats.php`):**
  - Quyền truy cập: Dành riêng cho `Club Manager`.
  - 4 Thẻ KPI Tổng quan CLB: Thành viên CLB đang hoạt động, Tổng số sự kiện, Tổng lượt đăng ký / có mặt, Tỷ lệ tham dự trung bình toàn CLB (%).
  - **Công thức Thống kê chuẩn hóa (BR06):**
    - `Số đăng ký` = `COUNT(RegID WHERE Status='Registered')`
    - `Số có mặt` = `COUNT(AttendanceID WHERE AttendanceStatus=1)`
    - `Số vắng mặt` = `Số đăng ký - Số có mặt`
    - `Tỷ lệ tham dự (%)` = `(Số có mặt / Số đăng ký) * 100%`
  - Bảng thống kê chi tiết từng sự kiện kèm thanh Progress Bar trực quan 3 màu (Xanh $\ge 80\%$, Vàng $50-79\%$, Đỏ $< 50\%$).
  - Bộ lọc theo trạng thái sự kiện (`All`, `Published`, `Draft`, `Cancelled`) và tìm kiếm theo tên sự kiện.

### Không bao gồm (Out of scope)
- Check-in bằng mã QR (`PB26`) và gửi email tự động (`PB27`) → Nằm ngoài phạm vi MVP.

---

## 2. Tasks chi tiết

### Task 1: Module dùng chung & Phân tích trạng thái sự kiện (`includes/event_helpers.php`)
- **File:** `app/includes/event_helpers.php`
- Cung cấp: `event_now(PDO $pdo)` (giờ MySQL chuẩn), `event_state()`, `event_seats_text()`, `event_fmt_dt()`, `event_manager_club()`, `event_input_to_dt()`.

### Task 2: Giao diện Danh sách sự kiện (`index.php` - SCR08)
- **File:** `index.php`
- Hiển thị danh sách sự kiện công khai, tích hợp `event_state()` tính toán trạng thái động và cảnh báo khẩn cấp.

### Task 3: Chi tiết sự kiện & Đăng ký / Hủy đăng ký (`app/modules/events/detail.php` - SCR09)
- **File:** `app/modules/events/detail.php`
- Logic Đăng ký & Hủy đăng ký với Transaction PDO + `SELECT ... FOR UPDATE` bảo đảm `NFR01` và bảo toàn dữ liệu.

### Task 4: Biểu mẫu Tạo và Chỉnh sửa sự kiện (`app/modules/events/form.php` - SCR10)
- **File:** `app/modules/events/form.php`
- Validation dữ liệu, ràng buộc BR04 (`EndTime > StartTime`, `RegistrationDeadline <= StartTime`), ràng buộc sức chứa.

### Task 5: Quản lý sự kiện nội bộ & Hủy sự kiện (`app/modules/events/manage.php` - SCR11)
- **File:** `app/modules/events/manage.php`
- Danh sách sự kiện nội bộ, thao tác Hủy sự kiện bảo toàn dữ liệu (BR05), nút truy cập Thống kê và Điểm danh.

### Task 6: Hoạt động của tôi & Lịch sử tham gia (`app/modules/events/my_events.php` - SCR12)
- **File:** `app/modules/events/my_events.php`
- Theo dõi sự kiện cá nhân đã đăng ký, hiển thị đúng 5 trạng thái tham dự.

### Task 7: Danh sách Đăng ký theo sự kiện (`app/modules/attendance/registrations.php` - SCR13)
- **File:** `app/modules/attendance/registrations.php`
- Xem chi tiết danh sách người đăng ký theo sự kiện, 4 thẻ tóm tắt số chỗ, lọc theo trạng thái (Hợp lệ/Đã hủy), tìm kiếm tức thời client JS không dấu.

### Task 8: Điểm danh sự kiện & Xử lý ngoại lệ (`app/modules/attendance/checkin.php` - SCR14)
- **File:** `app/modules/attendance/checkin.php`
- Điểm danh (check-in) và bỏ điểm danh (undo) với bộ lọc tìm kiếm tức thời $\le 2\text{s}$, liên kết hai chiều với `registrations.php`.

### Task 9: Báo cáo Thống kê hoạt động cơ bản (`app/modules/attendance/stats.php` - SCR15)
- **File:** `app/modules/attendance/stats.php`
- Phân quyền Club Manager, 4 thẻ KPI Dashboard, bảng thống kê chi tiết từng sự kiện theo công thức BR06 kèm thanh tiến độ 3 màu.

### Task 10: Tích hợp Điều hướng Header & Style CSS
- **Files:** `app/includes/header.php`, `assets/css/style.css`
- Bổ sung liên kết Báo cáo thống kê, Sự kiện của tôi, class `.badge-cancelled`.

---

## 3. Cấu trúc file hoàn thiện Pha 5

```text
EventFlow/
├── app/
│   ├── includes/
│   │   ├── event_helpers.php       ← Helper tính trạng thái sự kiện, thời gian MySQL, số chỗ
│   │   └── header.php              ← Điều hướng topbar (thêm Sự kiện của tôi, menu Thống kê & Điểm danh)
│   └── modules/
│       ├── attendance/
│       │   ├── _scope.php          ← Helper xác thực phạm vi CLB được phép điểm danh / xem đăng ký
│       │   ├── checkin.php         ← [SCR14] Màn hình Điểm danh & Bỏ điểm danh (Check-in / Undo)
│       │   ├── registrations.php   ← [SCR13] Danh sách đăng ký theo sự kiện, đếm số chỗ & bộ lọc
│       │   └── stats.php           ← [SCR15] Báo cáo Thống kê hoạt động cơ bản theo công thức BR06
│       └── events/
│           ├── detail.php          ← [SCR09] Chi tiết sự kiện & Đăng ký / Hủy đăng ký
│           ├── form.php            ← [SCR10] Biểu mẫu Tạo và Chỉnh sửa sự kiện
│           ├── manage.php          ← [SCR11] Quản lý danh sách sự kiện nội bộ & Hủy sự kiện
│           └── my_events.php       ← [SCR12] Hoạt động của tôi & Lịch sử tham gia
├── assets/
│   └── css/
│       └── style.css               ← Bổ sung kiểu dáng badge-cancelled
├── docs/
│   └── pha-5/
│       ├── plan.md                 ← Kế hoạch chi tiết Pha 5
│       ├── files.md                ← Danh sách file và nghiệm thu Pha 5
│       └── test_cases.md           ← Bộ kịch bản kiểm thử toàn diện Pha 5
└── index.php                       ← [SCR08] Danh sách sự kiện công khai & thẻ trạng thái
```

---

## 4. Tiêu chí hoàn thành (Definition of Done)

- [x] Hiển thị danh sách sự kiện công khai với nhãn trạng thái và cảnh báo hạn chót / chỗ trống chính xác (`SCR08`).
- [x] Người tham gia đăng ký và hủy đăng ký thành công trên màn hình chi tiết, không vượt quá sức chứa tối đa (`SCR09`).
- [x] Club Manager tạo mới và chỉnh sửa sự kiện thành công với đầy đủ kiểm tra hợp lệ thời gian và số chỗ (`SCR10`).
- [x] Club Manager hủy sự kiện thành công, bảo toàn toàn bộ dữ liệu đăng ký và điểm danh trước đó (`SCR11`).
- [x] Người dùng theo dõi được lịch sử các sự kiện đã đăng ký và kết quả tham dự thực tế (`SCR12`).
- [x] Ban chủ nhiệm và thành viên được ủy quyền xem danh sách đăng ký theo sự kiện với bộ lọc và tìm kiếm không dấu (`SCR13`).
- [x] Ban chủ nhiệm và thành viên được ủy quyền thực hiện điểm danh và bỏ điểm danh chính xác, phản hồi tức thời $\le 2\text{s}$ (`SCR14`).
- [x] Club Manager xem báo cáo thống kê hoạt động sự kiện đầy đủ KPI và bảng chi tiết theo công thức BR06 (`SCR15`).
- [x] 100% các file PHP được kiểm tra cú pháp tĩnh qua `php -l` không phát sinh lỗi.
- [x] Toàn bộ hồ sơ tài liệu `plan.md`, `files.md`, `test_cases.md` trong `docs/pha-5/` được hoàn thiện trọn vẹn.
