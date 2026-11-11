# 📋 Pha 5: Quản lý Sự kiện, Đăng ký & Điểm danh — Kế hoạch thực hiện

> **Mục tiêu:** Xây dựng hoàn chỉnh phân hệ Quản lý Sự kiện, Đăng ký tham gia và Điểm danh (Check-in) bao gồm: Xem danh sách sự kiện (`SCR08` - `index.php`), Chi tiết sự kiện & Đăng ký/Hủy đăng ký (`SCR09` - `events/detail.php`), Biểu mẫu Tạo/Sửa sự kiện (`SCR10` - `events/form.php`), Quản lý danh sách sự kiện & Hủy sự kiện (`SCR11` - `events/manage.php`), Hoạt động của tôi & Lịch sử tham gia (`SCR12` - `events/my_events.php`), và Quản lý danh sách đăng ký & Điểm danh/Bỏ điểm danh (`SCR13` & `SCR14` - `attendance/checkin.php`).  
> **Phụ thuộc:** Pha 1, Pha 2, Pha 3 và Pha 4 đã hoàn thành.  
> **Tham chiếu:** `docs/eventflow_readme.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html` (SCR08–SCR14 / FR09, FR10, FR12, FR13, FR14, FR15 / PB11–PB19 / NFR01, NFR02, NFR03, NFR05, NFR07).

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)

Phân bổ theo 3 User Story / Task trên Jira của Sprint 5:

#### Task 1: `[SP5 - 02]` (Jira: `G8-85`) — Giao diện Danh sách & Chi tiết sự kiện (`SCR08`, `SCR09`)
- [x] **`SCR08` - Danh sách sự kiện công khai (`index.php` - FR10, PB13, UC08):**
  - Hiển thị các sự kiện có trạng thái `Published` và `Cancelled` được sắp xếp theo thời gian diễn ra (`StartTime ASC`).
  - Tự động suy luận trạng thái động của sự kiện thông qua helper `event_state()`:
    - `Đang mở đăng ký`: Khi sự kiện `Published`, còn trong hạn đăng ký (`NOW() <= RegistrationDeadline`) và còn chỗ trống (`RegisteredCount < MaxCapacity`).
    - `Đã đóng đăng ký`: Khi quá hạn đăng ký hoặc đã đủ số lượng người đăng ký tối đa.
    - `Đã hủy`: Khi sự kiện có `Status = 'Cancelled'`.
  - Hiển thị nhãn cảnh báo khẩn cấp: "Chỉ còn X chỗ" (khi chỗ còn lại $\le 5$), "Sắp hết hạn đăng ký" (khi hạn chót $\le 48\text{ giờ}$).
  - Hiển thị tỷ lệ/số lượng đã đăng ký (`RegisteredCount / MaxCapacity`).
  - Nút xem chi tiết dẫn đến màn hình `SCR09`.

- [x] **`SCR09` - Chi tiết sự kiện, Đăng ký & Hủy đăng ký (`events/detail.php` - FR10, FR12, PB14, PB15, UC08, UC09):**
  - Mọi người dùng đã đăng nhập đều có thể xem chi tiết thông tin sự kiện.
  - Quyền Đăng ký / Hủy đăng ký: Dành riêng cho `Club Member` và `Event Participant` (`has_role(['Club Member', 'Event Participant'])`).
  - Thao tác Đăng ký (`action=register`):
    - Khóa dòng bằng `SELECT ... FOR UPDATE` trên bảng `Event` và `Event_Registration` đảm bảo không vượt sức chứa khi nhiều người cùng đăng ký (`NFR01`).
    - Kiểm tra điều kiện: Chưa bị hủy, chưa quá hạn đăng ký, còn chỗ trống, chưa đăng ký trước đó.
    - Chống trùng lặp: Nếu người dùng đã từng đăng ký rồi hủy, hệ thống tái kích hoạt bản ghi bằng cách chuyển `Status` về `'Registered'` thay vì `INSERT` mới (tránh vi phạm ràng buộc `UNIQUE(EventID, AccountID)`).
  - Thao tác Hủy đăng ký (`action=cancel_registration`):
    - Chỉ cho phép hủy trước giờ sự kiện bắt đầu (`NOW() < StartTime`) và người dùng chưa được điểm danh trong bảng `Attendance`.
    - Bảo toàn dữ liệu: Cập nhật `Status = 'Cancelled'`, tuyệt đối không `DELETE` bản ghi (`FR12`).
  - Thanh tiến độ (Progress Bar) trực quan thể hiện mức độ lấp đầy chỗ trống.

#### Task 2: `[SP5 - 03]` (Jira: `G8-86`) — Biểu mẫu Tạo, Chỉnh sửa và Hủy sự kiện (`SCR10`, `SCR11`)
- [x] **`SCR10` - Biểu mẫu Tạo / Sửa sự kiện (`events/form.php` - FR09, PB11, PB12, UC07):**
  - Quyền truy cập: Dành riêng cho `Club Manager` (`require_role('Club Manager')`).
  - Tự động gắn sự kiện với CLB mà tài khoản hiện tại phụ trách (`Club.ManagerID = AccountID`).
  - Kiểm tra tính hợp lệ dữ liệu chặt chẽ (Validation):
    - Tên sự kiện: Bắt buộc, không quá 200 ký tự.
    - Địa điểm: Bắt buộc, không quá 255 ký tự.
    - Thời gian bắt đầu (`StartTime`), kết thúc (`EndTime`), hạn đăng ký (`RegistrationDeadline`): Bắt buộc, định dạng hợp lệ.
    - **Ràng buộc thời gian nghiệp vụ:** Thời gian kết thúc phải sau thời gian bắt đầu (`EndTime > StartTime`); Hạn đăng ký phải trước hoặc bằng thời gian bắt đầu sự kiện (`RegistrationDeadline <= StartTime`).
    - Số lượng chỗ tối đa (`MaxCapacity`): Để trống nếu không giới hạn; nếu có thì phải là số nguyên dương $\ge 1$.
    - **Ràng buộc khi Chỉnh sửa:** Khóa dòng bằng `FOR UPDATE`. Không được giảm `MaxCapacity` nhỏ hơn số lượng người đã đăng ký thực tế hiện tại; Không cho phép chỉnh sửa sự kiện đã bị hủy (`Cancelled`).
  - Hỗ trợ lưu ở 2 chế độ: Bản nháp (`Draft`) hoặc Công bố ngay (`Published`). Khi sự kiện đã công bố, không cho chuyển ngược về bản nháp.

- [x] **`SCR11` - Quản lý danh sách sự kiện & Hủy sự kiện (`events/manage.php` - FR09, PB11, PB12, UC07):**
  - Quyền truy cập: Chỉ dành cho `Club Manager`.
  - Hiển thị toàn bộ các sự kiện của CLB (bao gồm cả `Draft`, `Published`, `Cancelled`) sắp xếp theo thời gian mới nhất.
  - Hiển thị chỉ số đăng ký thực tế trên từng sự kiện.
  - Thao tác Hủy sự kiện (`action=cancel_event`):
    - Chỉ áp dụng cho sự kiện `Published` và chưa kết thúc (`NOW() < EndTime`).
    - Dùng Transaction + `SELECT ... FOR UPDATE`.
    - Cập nhật trạng thái sự kiện thành `Status = 'Cancelled'`.
    - **Bảo toàn dữ liệu nghiệp vụ:** Không xóa hay sửa các bản ghi `Event_Registration` và `Attendance` đã có trước đó.

#### Task 3: `[SP5 - 06]` (Jira: `G8-89`) — Giao diện Đăng ký, Hoạt động cá nhân và Điểm danh (`SCR12`, `SCR13`, `SCR14`)
- [x] **`SCR12` - Sự kiện của tôi & Lịch sử tham gia (`events/my_events.php` - FR13, PB16, UC10):**
  - Mọi người dùng đã đăng nhập đều có thể theo dõi danh sách sự kiện mà bản thân đã đăng ký.
  - Hiển thị thông tin tổng quan: Tên sự kiện, CLB tổ chức, thời gian diễn ra, địa điểm, ngày đăng ký.
  - Nhãn trạng thái tham dự rõ ràng và chính xác theo nghiệp vụ (`NFR05`):
    - `Đã đăng ký`: Đăng ký thành công, sự kiện chưa diễn ra/chưa kết thúc.
    - `Đã tham dự`: Đã được ban tổ chức check-in thành công (`AttendanceStatus = 1`).
    - `Vắng mặt`: Sự kiện đã kết thúc nhưng không có bản ghi điểm danh, hoặc điểm danh ghi nhận vắng (`AttendanceStatus = 0`).
    - `Đã hủy đăng ký`: Người dùng đã chủ động hủy đăng ký trước đó.
    - `Sự kiện đã hủy`: Sự kiện bị ban chủ nhiệm hủy bỏ.
  - Nút điều hướng nhanh xem chi tiết từng sự kiện.

- [x] **`SCR13` & `SCR14` - Danh sách đăng ký & Điểm danh sự kiện (`attendance/checkin.php` & `_scope.php` - FR14, FR15, PB17, PB18, PB19, UC11, UC12):**
  - Phân quyền RBAC 2 tầng (`require_authorized_club_member()`):
    - Cho phép `Club Manager` và thành viên CLB được ủy quyền (`Board Member`, `Event Supporter`) có trạng thái `Active`.
    - Kiểm soát phạm vi CLB thông qua `_scope.php`: Chỉ được phép điểm danh cho sự kiện thuộc CLB mà mình quản lý hoặc là thành viên ủy quyền.
  - Chế độ 1: Chọn sự kiện thuộc CLB đang mở điểm danh.
  - Chế độ 2: Danh sách người đăng ký và thao tác Check-in:
    - Thống kê tỷ lệ điểm danh theo thời gian thực: `Số người đã điểm danh / Tổng số người đăng ký`.
    - Bộ lọc tức thời phía Client bằng JavaScript (tìm kiếm theo Họ tên hoặc MSSV, không phân biệt hoa thường, tự động loại bỏ dấu tiếng Việt) giúp nhân viên check-in dưới 2 giây (`NFR03`).
    - Thao tác Điểm danh (`action=checkin`): Ghi nhận vào bảng `Attendance` với `AttendanceStatus = 1`, lưu người thực hiện `CheckedInBy = current_user_id()`, tự động cuộn đến dòng vừa check-in và đổi màu highlight xanh.
    - Thao tác Bỏ điểm danh (`action=undo`): Cho phép nhân sự hủy check-in nếu lỡ bấm nhầm khi sự kiện chưa kết thúc.
    - Chống điểm danh trùng lặp ở cả tầng ứng dụng (kiểm tra trước) và tầng CSDL thông qua ràng buộc `UNIQUE(RegID)`.

### Không bao gồm (Out of scope)
- Báo cáo thống kê hoạt động sự kiện nâng cao (`SCR15` - FR16, PB20) → Sẽ hoàn thiện ở pha tổng kết tiếp theo.
- Check-in bằng mã QR (`PB26`) và gửi email tự động (`PB27`) → Nằm ngoài phạm vi MVP.

---

## 2. Tasks chi tiết

### Task 1: Module dùng chung & Phân tích trạng thái sự kiện (`includes/event_helpers.php`)
- **File:** `app/includes/event_helpers.php`
- **Mục đích:** Cung cấp các hàm dùng chung độc lập cho toàn bộ phân hệ sự kiện.
- **Hàm cốt lõi:**
  1. `event_now(PDO $pdo)`: Lấy thời gian hiện tại trực tiếp từ MySQL (`SELECT NOW()`) để loại bỏ sự sai lệch múi giờ giữa máy chủ web PHP và cơ sở dữ liệu.
  2. `event_state(array $evt, int $registered, string $now)`: Trả về trạng thái chuẩn hóa, màu CSS badge, lý do đóng và các cảnh báo khẩn cấp (sắp hết hạn, sắp hết chỗ).
  3. `event_seats_text(array $state)`: Trả về chuỗi hiển thị số chỗ chuẩn format (vd: `12 / 40` hoặc `12 / Không giới hạn`).
  4. `event_fmt_dt(?string $datetime)`: Định dạng ngày giờ hiển thị sang `dd/mm/yyyy HH:ii`.
  5. `event_manager_club(PDO $pdo, int $accountId)`: Truy vấn CLB do tài khoản quản lý.
  6. `event_input_to_dt()` & `event_dt_to_input()`: Chuyển đổi định dạng HTML5 datetime-local sang MySQL DATETIME và ngược lại.

### Task 2: Giao diện Danh sách sự kiện (`index.php` - SCR08)
- **File:** `index.php`
- **Logic:**
  1. Yêu cầu đăng nhập (`require_login()`).
  2. Truy vấn danh sách sự kiện từ bảng `Event` kết hợp đếm `RegisteredCount` từ bảng `Event_Registration`.
  3. Áp dụng `event_state()` để phân loại trạng thái hiển thị động và các badge cảnh báo.
  4. Bổ sung nút liên kết chuyển tiếp tới màn hình `SCR09`.

### Task 3: Chi tiết sự kiện & Đăng ký / Hủy đăng ký (`app/modules/events/detail.php` - SCR09)
- **File:** `app/modules/events/detail.php`
- **Logic:**
  1. Kiểm tra quyền và tải thông tin chi tiết sự kiện theo `id`.
  2. Xử lý POST Đăng ký (`action=register`):
     - Mở transaction, áp dụng `SELECT ... FOR UPDATE` cho bản ghi `Event`.
     - Kiểm tra trạng thái sự kiện, hạn chót đăng ký và sức chứa tối đa.
     - Cập nhật dòng đăng ký cũ hoặc thêm mới bản ghi vào `Event_Registration`.
  3. Xử lý POST Hủy đăng ký (`action=cancel_registration`):
     - Mở transaction, kiểm tra thời gian sự kiện chưa diễn ra và chưa có bản ghi `Attendance`.
     - Cập nhật `Status = 'Cancelled'` vào `Event_Registration`.
  4. Render giao diện chi tiết: Card thông tin, ngày giờ, địa điểm, thanh tiến độ sức chứa, nút Đăng ký / Hủy đăng ký tương ứng với trạng thái của người dùng.

### Task 4: Biểu mẫu Tạo và Chỉnh sửa sự kiện (`app/modules/events/form.php` - SCR10)
- **File:** `app/modules/events/form.php`
- **Logic:**
  1. Kiểm tra vai trò `Club Manager`, lấy `ClubID` tương ứng.
  2. Xác định chế độ Tạo mới (`id=0`) hoặc Chỉnh sửa (`id>0`).
  3. Xử lý POST:
     - Kiểm tra CSRF token.
     - Kiểm tra dữ liệu bắt buộc (Tên sự kiện, địa điểm, thời gian).
     - Kiểm tra ràng buộc thời gian: `EndTime > StartTime` và `RegistrationDeadline <= StartTime`.
     - Kiểm tra ràng buộc sức chứa: `MaxCapacity >= RegisteredCount` khi chỉnh sửa.
     - Mở transaction, thực hiện `INSERT` hoặc `UPDATE` vào bảng `Event`.
     - Chuyển hướng về `events/manage.php` kèm thông báo flash thành công.

### Task 5: Quản lý sự kiện nội bộ & Hủy sự kiện (`app/modules/events/manage.php` - SCR11)
- **File:** `app/modules/events/manage.php`
- **Logic:**
  1. Kiểm tra vai trò `Club Manager`.
  2. Xử lý POST Hủy sự kiện (`action=cancel_event`):
     - Kiểm tra sự kiện chưa kết thúc và đang ở trạng thái `Published`.
     - Mở transaction, cập nhật `Event.Status = 'Cancelled'`.
     - Giữ nguyên toàn bộ dữ liệu đăng ký và điểm danh liên quan.
  3. Hiển thị bảng danh sách sự kiện của CLB, các nút thao tác Tạo mới, Chỉnh sửa và Hủy sự kiện kèm hộp thoại xác nhận modal/alert.

### Task 6: Hoạt động của tôi & Lịch sử tham gia (`app/modules/events/my_events.php` - SCR12)
- **File:** `app/modules/events/my_events.php`
- **Logic:**
  1. Lấy `AccountID` từ Session của người dùng hiện tại.
  2. Truy vấn kết hợp `Event_Registration` với `Event`, `Club` và `Attendance`.
  3. Áp dụng hàm `my_event_status()` để xác định chuẩn xác trạng thái: Đã đăng ký, Đã tham dự, Vắng mặt, Đã hủy đăng ký, Sự kiện đã hủy.
  4. Hiển thị dạng bảng trực quan, có link chuyển đến chi tiết từng sự kiện.

### Task 7: Phạm vi phân quyền & Điểm danh sự kiện (`app/modules/attendance/` - SCR13, SCR14)
- **Files:** `app/modules/attendance/_scope.php`, `app/modules/attendance/checkin.php`
- **Logic:**
  1. `_scope.php`: Xác định danh sách `ClubID` mà tài khoản hiện tại được ủy quyền thao tác (dựa vào `ManagerID` hoặc `MemberRole` là `Board Member` / `Event Supporter` đang `Active`).
  2. `checkin.php`:
     - Nếu chưa chọn sự kiện: Hiển thị danh sách các sự kiện đang công bố thuộc CLB.
     - Khi đã chọn sự kiện: Hiển thị danh sách tất cả thành viên có đăng ký hợp lệ (`Status = 'Registered'`).
     - Tích hợp ô lọc trực tiếp bằng JavaScript trên Client (bỏ dấu tiếng Việt, tìm theo tên hoặc MSSV).
     - Xử lý POST Điểm danh (`action=checkin`): Tạo bản ghi `Attendance` với `AttendanceStatus = 1`, ghi nhận `CheckedInBy`.
     - Xử lý POST Bỏ điểm danh (`action=undo`): Xóa bản ghi `Attendance` nếu thao tác nhầm.

### Task 8: Cập nhật Điều hướng Header & Design System CSS
- **Files:** `app/includes/header.php`, `assets/css/style.css`
- **Nội dung:**
  1. Cập nhật menu Topbar: Thêm liên kết "Sự kiện của tôi", chỉnh sửa đường dẫn dropdown Quản trị và đường dẫn Hỗ trợ điểm danh cho thành viên ủy quyền trỏ chính xác về `checkin.php`.
  2. Cập nhật CSS: Bổ sung lớp kiểu dáng `.badge-cancelled` cho trạng thái sự kiện đã hủy.

---

## 3. Cấu trúc file dự kiến

```text
EventFlow/
├── app/
│   ├── includes/
│   │   ├── event_helpers.php       ← Helper tính trạng thái sự kiện, thời gian, số chỗ
│   │   └── header.php              ← Điều hướng topbar (thêm Sự kiện của tôi, menu điểm danh)
│   └── modules/
│       ├── attendance/
│       │   ├── _scope.php          ← Helper xác thực phạm vi CLB được phép điểm danh
│       │   └── checkin.php         ← [SCR13, SCR14] Danh sách đăng ký & Điểm danh / Bỏ điểm danh
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
│       └── files.md                ← Danh sách file và nghiệm thu Pha 5
└── index.php                       ← [SCR08] Danh sách sự kiện công khai & thẻ trạng thái
```

---

## 4. Điểm cần lưu ý & Ràng buộc kỹ thuật

1. **Toàn vẹn và nhất quán dữ liệu (NFR01):**
   - Đăng ký sự kiện sử dụng Transaction và `SELECT ... FOR UPDATE` để tránh race condition khi nhiều người dùng cùng đăng ký một lúc ở giới hạn số chỗ cuối cùng.
   - Chặn đăng ký trùng lặp ở cả tầng logic PHP và tầng cơ sở dữ liệu (`UNIQUE(EventID, AccountID)`).
2. **Bảo toàn dữ liệu (FR09, FR12):**
   - Khi hủy sự kiện hoặc hủy đăng ký, chỉ cập nhật trạng thái `Status = 'Cancelled'`, tuyệt đối không thực hiện xóa cứng (Hard Delete) để phục vụ tra cứu lịch sử và thống kê.
3. **Đồng bộ hóa thời gian:**
   - Sử dụng thời gian máy chủ CSDL qua hàm `event_now()` (`SELECT NOW()`) để đảm bảo các so sánh hạn chót, giờ bắt đầu và kết thúc luôn nhất quán.
4. **Phân quyền chặt chẽ 2 tầng (RBAC):**
   - Kiểm tra `Club Manager` khi quản lý sự kiện; Kiểm tra kết hợp vai trò nội bộ `Board Member` / `Event Supporter` kèm trạng thái `Active` đối với chức năng hỗ trợ điểm danh.
5. **Trải nghiệm người dùng mượt mà (NFR03, NFR04):**
   - Tìm kiếm check-in được tối ưu tức thời bằng JavaScript, không gây tải lại trang; hỗ trợ bỏ dấu tiếng Việt để thao tác điểm danh đạt dưới 2 giây.

---

## 5. Tiêu chí hoàn thành (Definition of Done)

- [x] Hiển thị danh sách sự kiện công khai với nhãn trạng thái và cảnh báo hạn chót / chỗ trống chính xác (`SCR08`).
- [x] Người tham gia đăng ký và hủy đăng ký thành công trên màn hình chi tiết, không vượt quá sức chứa tối đa (`SCR09`).
- [x] Club Manager tạo mới và chỉnh sửa sự kiện thành công với đầy đủ kiểm tra hợp lệ thời gian và số chỗ (`SCR10`).
- [x] Club Manager hủy sự kiện thành công, bảo toàn toàn bộ dữ liệu đăng ký và điểm danh trước đó (`SCR11`).
- [x] Người dùng theo dõi được lịch sử các sự kiện đã đăng ký và kết quả tham dự thực tế (`SCR12`).
- [x] Ban chủ nhiệm và thành viên được ủy quyền thực hiện điểm danh và bỏ điểm danh chính xác, hỗ trợ lọc nhanh tên/MSSV (`SCR13`, `SCR14`).
- [x] 100% các file PHP được kiểm tra cú pháp tĩnh qua `php -l` không phát sinh lỗi.
- [x] Hồ sơ tài liệu `plan.md` và `files.md` trong `docs/pha-5/` được cập nhật hoàn chỉnh, sẵn sàng nghiệm thu và bàn giao.
