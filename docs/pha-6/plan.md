# 📋 Pha 6: Hoàn thiện cốt lõi MVP (Hồ sơ cá nhân & Xử lý ngoại lệ Điểm danh) — Kế hoạch thực hiện

> **Mục tiêu:** Xây dựng hoàn chỉnh 100% phạm vi chức năng MVP còn thiếu bao gồm: Màn hình Hồ sơ cá nhân & Tư cách thành viên (`SCR03` - `app/modules/profile/index.php`), giải quyết triệt để lỗi 404 trên Navbar; và Xử lý ngoại lệ Thiếu đăng ký tại chỗ (Walk-in Check-in) trên Màn hình Điểm danh sự kiện (`SCR14` - `app/modules/attendance/checkin.php`).  
> **Phụ thuộc:** Pha 1 đến Pha 5 đã hoàn thành.  
> **Tham chiếu:** `README.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html`, Bảng 4.2 Product Scope (PS03, PS09 / FR04, FR15 / PB05, PB19 / BR01, BR02, BR03, BR04, BR05, BR06 / NFR01, NFR02, NFR03, NFR05, NFR07).

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)

#### Task 1: [SP6 - 01] — Xây dựng phân hệ Hồ sơ cá nhân & Tư cách thành viên (`SCR03` - FR04, PB05, UC04)
- [ ] Xây dựng file `app/modules/profile/index.php`:
  - Kiểm tra xác thực bắt buộc: `require_login()`.
  - Truy vấn CSDL thông tin tài khoản đang đăng nhập (`Account` join `Role`).
  - Truy vấn thông tin tư cách thành viên CLB (`Club_Member` join `Club`).
  - **Khối 1: Thẻ tổng quan người dùng (Identity Card):**
    - Avatar dạng chữ viết tắt (Initials circle) đồng bộ với Navbar.
    - Họ và tên, Email, MSSV (hoặc nhãn "Chưa cập nhật").
    - Badge vai trò hệ thống (`RoleName`: System Administrator / Club Manager / Club Member / Event Participant).
    - Badge tư cách thành viên CLB (nếu có): Tên CLB, Vai trò nội bộ (`MemberRole`: Board Member / Event Supporter / Member), Trạng thái (`Status`: Active / Inactive), Ngày gia nhập (`JoinedDate`).
    - Ngày khởi tạo tài khoản (`CreatedAt`).
  - **Khối 2: Biểu mẫu Cập nhật Thông tin cơ bản (`action=update_profile`):**
    - Cho phép sửa Họ và tên (`FullName`).
    - Cho phép cập nhật / chỉnh sửa Mã số sinh viên (`StudentCode`).
    - Email hiển thị ở chế độ chỉ đọc (`readonly`).
    - Kiểm tra hợp lệ dữ liệu: Họ tên không rỗng; MSSV không được trùng với tài khoản khác trong hệ thống.
    - Cập nhật lại `$_SESSION['user']` sau khi lưu thành công để đồng bộ tức thời trên Navbar và toàn hệ thống.
  - **Khối 3: Biểu mẫu Đổi mật khẩu (`action=change_password`):**
    - Nhập Mật khẩu hiện tại (`current_password`): Xác minh bằng `password_verify()`.
    - Nhập Mật khẩu mới (`new_password`): Yêu cầu tối thiểu 8 ký tự, phải khác mật khẩu hiện tại.
    - Xác nhận mật khẩu mới (`confirm_password`): Phải khớp 100% với mật khẩu mới.
    - Băm mật khẩu an toàn với `password_hash(PASSWORD_BCRYPT)` và cập nhật vào `Account`.
  - Bảo vệ CSRF Token cho toàn bộ các POST request.

#### Task 2: [SP6 - 02] — Bổ sung Xử lý Ngoại lệ Thiếu đăng ký tại chỗ (Walk-in Check-in) trên Màn hình Điểm danh (`SCR14` - FR15, PB19, UC12, NFR01)
- [ ] Nâng cấp màn hình `app/modules/attendance/checkin.php`:
  - Quyền thao tác: `Club Manager` và `Authorized Club Member` (`require_authorized_club_member()`).
  - Chỉ áp dụng khi đã chọn sự kiện cụ thể (`event_id > 0`) và sự kiện đang mở điểm danh (`Published`, `NOW() < EndTime`).
  - **Giao diện:**
    - Bổ sung nút **"Điểm danh bổ sung (Chưa đăng ký trước)"** trên thanh tiêu đề/hành động.
    - Thiết kế Modal Bootstrap: Hỗ trợ ô tìm kiếm sinh viên theo Tên, Email hoặc MSSV.
    - Hiển thị danh sách kết quả tìm kiếm kèm thông tin định danh và nút bấm nhanh **"Đăng ký & Điểm danh ngay"**.
  - **Logic Backend (`action=walkin_checkin`):**
    - Bảo vệ CSRF Token.
    - Sử dụng Transaction PDO + `SELECT ... FOR UPDATE` trên bảng `Event` để đảm bảo toàn vẹn dữ liệu (`NFR01`).
    - Kiểm tra sức chứa tối đa (`MaxCapacity`): Nếu sự kiện có giới hạn và số lượng đăng ký hợp lệ đã đạt trần $\rightarrow$ Từ chối thao tác với thông báo *"Sự kiện đã hết chỗ, không thể nhận thêm người tham gia tại chỗ."*
    - Xử lý bảng `Event_Registration`:
      - Nếu sinh viên đã có bản ghi `Status = 'Registered'`: Báo *"Sinh viên này đã có tên trong danh sách đăng ký, vui lòng điểm danh trực tiếp tại bảng tra cứu."*
      - Nếu sinh viên đã từng hủy (`Status = 'Cancelled'`): Kích hoạt lại bản ghi cũ `Status = 'Registered'`, `RegDate = NOW()`.
      - Nếu sinh viên chưa từng đăng ký: INSERT bản ghi mới vào `Event_Registration` với `Status = 'Registered'`.
    - Xử lý bảng `Attendance`: INSERT bản ghi điểm danh với `AttendanceStatus = 1`, `CheckedInBy = current_user_id()`.
    - Commit Transaction và tự động cuộn (scroll) / highlight dòng người vừa được điểm danh.

---

## 2. Tasks chi tiết

### Task 1: Xây dựng `app/modules/profile/index.php`
- **File:** `app/modules/profile/index.php`
- **Logic:**
  1. Gán tiêu đề trang `$pageTitle = 'Hồ sơ cá nhân — EventFlow'`.
  2. Nạp `header.php` và `footer.php`.
  3. Xử lý POST request:
     - `update_profile`: Cập nhật FullName, StudentCode (kiểm tra UNIQUE MSSV). Đồng bộ `$_SESSION['user']`.
     - `change_password`: Xác minh mật khẩu cũ, kiểm tra độ dài $\ge 8$, băm mật khẩu mới, cập nhật CSDL.
  4. Hiển thị UI chia cột (Cột trái: Thông tin tổng quan và tư cách CLB; Cột phải: Form cập nhật thông tin và Form đổi mật khẩu).

### Task 2: Nâng cấp `app/modules/attendance/checkin.php`
- **File:** `app/modules/attendance/checkin.php`
- **Logic:**
  1. Thêm hàm `att_walkin_checkin(PDO $pdo, int $eventId, int $targetAccountId, int $staffId): array`.
  2. Bổ sung Modal "Điểm danh bổ sung" trên giao diện HTML.
  3. Bổ sung script tìm kiếm tài khoản sinh viên tức thời qua danh sách tài khoản hợp lệ hoặc API filter nội bộ.
  4. Bổ sung xử lý POST `action === 'walkin_checkin'`.

---

## 3. Cấu trúc file dự kiến Pha 6

```text
EventFlow/
├── app/
│   └── modules/
│       ├── profile/
│       │   └── index.php             ← [SCR03] MỚI: Hồ sơ cá nhân & Tư cách thành viên (FR04, PB05)
│       └── attendance/
│           └── checkin.php           ← [SCR14] CẬP NHẬT: Thêm modal & logic Điểm danh bổ sung walk-in
└── docs/
    └── pha-6/
        ├── plan.md                   ← Kế hoạch chi tiết Pha 6
        ├── files.md                  ← Danh sách file và nghiệm thu Pha 6
        └── test_cases.md             ← Bộ kịch bản kiểm thử toàn diện Pha 6
```

---

## 4. Tiêu chí hoàn thành (Definition of Done)

- [ ] Người dùng truy cập được trang Hồ sơ cá nhân từ Navbar không còn lỗi 404 (`SCR03`).
- [ ] Xem đầy đủ thông tin định danh và tư cách thành viên CLB.
- [ ] Cập nhật thành công Họ tên, MSSV và kiểm tra chống trùng MSSV chính xác.
- [ ] Đổi mật khẩu thành công khi nhập đúng mật khẩu hiện tại và mật khẩu mới $\ge 8$ ký tự.
- [ ] Club Manager và Supporter thực hiện được Điểm danh bổ sung cho sinh viên vãng lai chưa đăng ký trước tại `SCR14`.
- [ ] Logic Walk-in check-in kiểm soát chặt chẽ sức chứa `MaxCapacity`, không vượt quá quy định (`NFR01`).
- [ ] 100% các file PHP vượt qua kiểm tra cú pháp tĩnh (`php -l`) không có lỗi.
- [ ] Hoàn thành trọn bộ hồ sơ `plan.md`, `files.md`, `test_cases.md` trong thư mục `docs/pha-6/`.
