# 📋 Pha 2: Chức năng & Giao diện Đăng Ký, Đăng Nhập & Phân Quyền — Kế hoạch thực hiện

> **Mục tiêu:** Xây dựng hoàn chỉnh luồng xác thực và phân quyền người dùng bao gồm màn hình Đăng ký (`SCR01`), Đăng nhập bố cục 2 cột bám sát ảnh mẫu Figma 01 (`SCR02`), Đăng xuất (`auth/logout.php`), tích hợp bảo mật CSRF và phân quyền RBAC 2 tầng.  
> **Phụ thuộc:** Pha 1 đã hoàn thành (CSDL, PDO config, `session_auth.php`, Base CSS).  
> **Tham chiếu:** `docs/eventflow_readme.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html`, ảnh mẫu `01 · Đăng nhập`.

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)
- [ ] Xây dựng giao diện và xử lý Đăng ký tài khoản (`SCR01` - `auth/register.php`):
  - Form đăng ký: Họ tên (`FullName`), Email, MSSV (`StudentCode`), Mật khẩu (≥ 8 ký tự), Xác nhận mật khẩu.
  - Kiểm tra tính hợp lệ dữ liệu (Validate client-side + server-side).
  - Kiểm tra chống trùng Email và MSSV trong bảng `Account`.
  - Mặc định gán vai trò `Event Participant` (`RoleID = 4`) và `IsActive = 1`.
  - Mã hóa mật khẩu an toàn với `password_hash(PASSWORD_BCRYPT)`.
  - Thông báo đăng ký thành công và chuyển hướng về trang Đăng nhập.
- [ ] Xây dựng giao diện và xử lý Đăng nhập (`SCR02` - `auth/login.php`):
  - Bố cục 2 cột bám sát chuẩn ảnh Figma 01:
    - Cột trái: Panel tím thương hiệu (`.ef-login-brand-panel`), Logo EventFlow, slogan "Gắn kết mọi hoạt động.", thẻ card sự kiện nổi bật (`English Speaking Circle`, badge 14, nhãn "Còn chỗ tham gia").
    - Cột phải: Form đăng nhập "Chào mừng trở lại", nhận diện linh hoạt bằng **Email hoặc Mã số sinh viên (MSSV)**, ô nhập mật khẩu, nút "Đăng nhập", liên kết chuyển qua Đăng ký.
  - Logic xác thực server-side:
    - Kiểm tra CSRF token hợp lệ.
    - Truy vấn tài khoản theo Email hoặc StudentCode.
    - Xác thực mật khẩu bằng `password_verify()`.
    - Kiểm tra trạng thái khóa tài khoản (`IsActive = 1`). Nếu `IsActive = 0`, từ chối đăng nhập và báo rõ: *"Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Quản trị viên hệ thống."*
    - Truy vấn thông tin vai trò Tầng 1 (`Role.RoleName`) và thông tin thành viên CLB Tầng 2 (`Club_Member.MemberRole`, `Club_Member.Status`).
    - Nạp thông tin định danh vào phiên qua `login_user()`.
    - Điều hướng thông minh sau đăng nhập: System Admin chuyển đến trang Quản trị, Manager/Member/Participant chuyển đến Trang chủ khám phá sự kiện.
- [ ] Xây dựng xử lý Đăng xuất an toàn (`auth/logout.php`):
  - Hủy phiên làm việc, xóa cookie phiên an toàn qua `logout_user()`.
  - Chuyển hướng về trang đăng nhập kèm thông báo flash.
- [ ] Kiểm tra tĩnh (Static Syntax & Code Inspection) toàn bộ các file PHP tạo ra.

### Không bao gồm (Out of scope)
- Màn hình Quản trị Tài khoản Hệ thống (`SCR16`) → Thực hiện ở **Pha 3**.
- Màn hình Hồ sơ CLB & Quản lý Thành viên (`SCR04` - `SCR07`) → Thực hiện ở **Pha 4**.

---

## 2. Tasks chi tiết

### Task 1: Xây dựng màn hình Đăng ký tài khoản (`auth/register.php`)
- **Mô tả:** Xây dựng form đăng ký tài khoản mới cho sinh viên và xử lý lưu vào CSDL.
- **File:** `auth/register.php`
- **Logic:**
  1. Nếu đã đăng nhập: Chuyển hướng về `index.php`.
  2. Phương thức GET: Hiển thị form đăng ký căn giữa trang (`col-lg-6`), gồm các trường `FullName`, `Email`, `StudentCode`, `Password`, `ConfirmPassword`, CSRF token hidden input.
  3. Phương thức POST:
     - Xác thực CSRF token qua `verify_csrf_token()`.
     - Kiểm tra dữ liệu: các trường bắt buộc, email đúng định dạng, mật khẩu ≥ 8 ký tự, mật khẩu xác nhận trùng khớp.
     - Kiểm tra trong CSDL: Email hoặc StudentCode đã tồn tại chưa (`SELECT COUNT(*) FROM Account WHERE Email = ? OR StudentCode = ?`).
     - Nếu đã tồn tại: Báo lỗi cụ thể cho người dùng.
     - Nếu hợp lệ: Băm mật khẩu `password_hash()`, thực thi câu lệnh `INSERT INTO Account (Email, PasswordHash, FullName, StudentCode, RoleID, IsActive) VALUES (?, ?, ?, ?, 4, 1)`.
     - Thiết lập flash message thành công và chuyển hướng sang `auth/login.php`.

### Task 2: Xây dựng màn hình Đăng nhập (`auth/login.php`)
- **Mô tả:** Xây dựng giao diện đăng nhập 2 cột theo ảnh Figma 01 và xử lý phiên làm việc.
- **File:** `auth/login.php`
- **Logic:**
  1. Nếu đã đăng nhập: Chuyển hướng về `index.php`.
  2. Phương thức GET: Hiển thị layout 2 cột:
     - Bên trái: Card thương hiệu màu tím đậm, hình tròn trang trí, logo, slogan, card sự kiện xem trước.
     - Bên phải: Form đăng nhập "Chào mừng trở lại", trường `identifier` (Email / MSSV), `password`, nút Đăng nhập, link Đăng ký.
  3. Phương thức POST:
     - Xác thực CSRF token.
     - Lấy tài khoản: `SELECT a.*, r.RoleName FROM Account a JOIN Role r ON a.RoleID = r.RoleID WHERE a.Email = :id OR a.StudentCode = :id LIMIT 1`.
     - Nếu không tìm thấy hoặc mật khẩu không khớp: Báo lỗi *"Email/MSSV hoặc mật khẩu không chính xác."*
     - Nếu `IsActive == 0`: Báo lỗi *"Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Quản trị viên hệ thống."*
     - Nếu hợp lệ: Lấy thêm quyền nội bộ CLB từ `Club_Member` (nếu có) bằng `SELECT MemberRole, Status, ClubID FROM Club_Member WHERE AccountID = :accId LIMIT 1`.
     - Gọi `login_user($account, $clubMember)`.
     - Điều hướng tương ứng vai trò.

### Task 3: Xây dựng chức năng Đăng xuất (`auth/logout.php`)
- **Mô tả:** Xử lý hủy phiên đăng nhập an toàn.
- **File:** `auth/logout.php`
- **Logic:**
  1. Gọi `logout_user()`.
  2. Thiết lập thông báo flash *"Bạn đã đăng xuất thành công."*
  3. Chuyển hướng về `auth/login.php`.

---

## 3. Cấu trúc file dự kiến

```text
EventFlow/
├── auth/
│   ├── register.php        ← [SCR01] Đăng ký tài khoản (FR01, PB01)
│   ├── login.php           ← [SCR02] Đăng nhập 2 cột theo mẫu Figma 01 (FR02, PB02, PB03)
│   └── logout.php          ← Xử lý đăng xuất an toàn
└── docs/
    └── pha-2/
        ├── plan.md
        └── files.md
```

---

## 4. Tiêu chí hoàn thành (Definition of Done)
- [ ] Giao diện Đăng nhập bám sát thiết kế Figma 01 (2 cột, nền tím thương hiệu, thẻ sự kiện mẫu, form sạch sẽ).
- [ ] Kiểm tra tĩnh (Static Check): Toàn bộ file PHP qua kiểm tra `php -l` không có lỗi cú pháp.
- [ ] Đăng ký kiểm tra đầy đủ mật khẩu ≥ 8 ký tự, chống trùng lặp Email và MSSV.
- [ ] Đăng nhập hỗ trợ cả Email và MSSV, kiểm tra đúng trạng thái `IsActive`.
- [ ] RBAC 2 tầng được lưu trữ chuẩn xác vào Session sau khi đăng nhập.
- [ ] Hoàn thành tài liệu `docs/pha-2/files.md` và không tự tiện commit (để user tự duyệt và commit).
