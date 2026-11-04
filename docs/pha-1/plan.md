# 📋 Pha 1: Nền tảng Kỹ thuật, CSDL & Khung Layout Chuẩn — Kế hoạch thực hiện

> **Mục tiêu:** Thiết lập cấu trúc dự án PHP Monolith, khởi tạo CSDL MySQL 7 bảng chuẩn 3NF kèm dữ liệu mẫu, xây dựng module kết nối PDO, module quản lý Session & RBAC 2 tầng, và khung giao diện chuẩn (Header, Footer, CSS) bám sát các ảnh mẫu Figma của người dùng.  
> **Phụ thuộc:** Không (Pha khởi đầu)  
> **Tham chiếu:** `docs/eventflow_readme.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html`, các ảnh chụp Prototype Figma (`01 · Đăng nhập`, `02 · Danh sách sự kiện`, `03 · Chi tiết sự kiện`, `05 · Sự kiện của tôi`).

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)
- [ ] Dọn dẹp các thư mục rỗng cũ không thuộc dự án (`backend/`, `frontend/`, `tests/`).
- [ ] Tạo file DDL và Seed CSDL hoàn chỉnh: `database/schema_and_seed.sql` (7 bảng InnoDB: Role, Account, Club, Club_Member, Event, Event_Registration, Attendance).
- [ ] Xây dựng module kết nối CSDL PDO: `config/database.php` (hỗ trợ UTF-8 mb4, báo lỗi ngoại lệ Exception, biến môi trường XAMPP chuẩn).
- [ ] Xây dựng module Session & Bảo mật & RBAC 2 tầng: `includes/session_auth.php` (Session Cookie HttpOnly, CSRF Token helper, các hàm kiểm tra đăng nhập và phân quyền theo RoleID & MemberRole, flash message).
- [ ] Xây dựng Style CSS chung: `assets/css/style.css` (bám sát tỷ lệ màu 60-30-10, bảng màu Indigo `--primary-600: #4F46E5`, thẻ card bo góc `--radius-lg: 16px`, badge pill, nút bấm, layout theo ảnh mẫu Figma).
- [ ] Xây dựng Header chuẩn: `includes/header.php` (Logo EventFlow, thanh điều hướng linh hoạt theo trạng thái đăng nhập và vai trò, badge vai trò, avatar tròn).
- [ ] Xây dựng Footer chuẩn: `includes/footer.php` (Chân trang chuyên nghiệp, tích hợp Bootstrap 5.3 CDN và script xử lý).
- [ ] Xây dựng Client JS chung: `assets/js/app.js` (Hỗ trợ tự động thêm CSRF vào request, validation form, toast thông báo).
- [ ] Tạo trang đích chuyển hướng ban đầu: `index.php`.

### Không bao gồm (Out of scope)
- Form và logic xử lý Đăng ký / Đăng nhập chi tiết (`SCR01`, `SCR02`) → Thực hiện ở **Pha 2**.
- Màn hình Quản trị Tài khoản Hệ thống (`SCR16`) → Thực hiện ở **Pha 3**.
- Màn hình Hồ sơ CLB & Quản lý Thành viên (`SCR04` - `SCR07`) → Thực hiện ở **Pha 4**.

---

## 2. Tasks chi tiết

### Task 1: Khởi tạo CSDL và Dữ liệu mẫu (Database DDL & Seed)
- **Mô tả:** Tạo file SQL chuẩn gồm 7 bảng quan hệ chuẩn 3NF và dữ liệu khởi tạo bám sát Data Dictionary Tuần 3 và Prototype.
- **File liên quan:** `database/schema_and_seed.sql`
- **Logic:**
  1. Tạo CSDL `eventflow_db` với character set `utf8mb4`.
  2. Tạo 7 bảng: `Role`, `Account`, `Club`, `Club_Member`, `Event`, `Event_Registration`, `Attendance`.
  3. Thêm các chỉ mục (Index) và ràng buộc khóa ngoại (Foreign Keys) với cơ chế bảo vệ tham chiếu.
  4. Chèn seed data: 4 vai trò, 5 tài khoản mẫu (Admin, Club Manager, Supporter, Member, Student - mật khẩu mặc định `Password@123`), 1 CLB UEH English Club, 3 thành viên CLB, 3 sự kiện mẫu.

### Task 2: Module cấu hình & kết nối PDO CSDL
- **Mô tả:** Viết hàm trả về đối tượng `PDO` duy nhất (Singleton hoặc hàm kết nối tái sử dụng).
- **File liên quan:** `config/database.php`
- **Logic:** Cấu hình `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`, thiết lập `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`.

### Task 3: Module quản trị Phiên, CSRF & Phân quyền RBAC 2 tầng
- **Mô tả:** Quản lý toàn bộ cơ chế bảo mật phiên và ủy quyền phía Server.
- **File liên quan:** `includes/session_auth.php`
- **Logic:**
  1. `init_session()`: Bật session an toàn với `cookie_httponly = true`, `cookie_samesite = 'Lax'`.
  2. `csrf_token()` & `verify_csrf_token()`: Sinh và xác minh token CSRF.
  3. `is_logged_in()`, `current_user()`: Lấy thông tin tài khoản hiện tại từ Session.
  4. `has_role($role_name)` / `require_role($roles)`: Kiểm tra vai trò tầng 1 (System Role).
  5. `is_authorized_club_member()`: Kiểm tra quyền tầng 2 (Thành viên CLB có `Status = 'Active'` và `MemberRole IN ('Board Member', 'Event Supporter')` hoặc là `Club Manager`).
  6. `set_flash()`, `get_flash()`: Quản lý thông báo toast/alert chuyển trang.

### Task 4: Hệ thống CSS và UI Theme bám sát Prototype Figma
- **Mô tả:** Xây dựng CSS toàn diện dựa trên `eventflow-design-rules.md` và các ảnh chụp màn hình UI Figma người dùng cung cấp.
- **File liên quan:** `assets/css/style.css`
- **Thiết kế:**
  1. Hệ màu: Nền `#F8F9FC`, Card `#FFFFFF`, Navbar `#1E1B4B` (hoặc Dark/Light theo Figma 02), Brand `#4F46E5`, Accent `#10B981`.
  2. Font: Google Font `Inter` kết hợp `JetBrains Mono`.
  3. Badge pills (ví dụ: Badge `Club Member` nền xanh nhạt viền xanh ngọc, `Đang mở đăng ký`, `Sắp diễn ra`, `Đã đăng ký`).
  4. Nút bấm bo góc 10px (`--radius-md`), card bo góc 16px (`--radius-lg`), shadow mềm (`0 1px 3px rgba(0,0,0,0.08)` và hover shadow).

### Task 5: Component Header và Footer dùng chung
- **Mô tả:** Tạo Header (Navbar) responsive và Footer có thể nạp ở mọi trang.
- **File liên quan:** `includes/header.php`, `includes/footer.php`, `assets/js/app.js`
- **Giao diện:**
  1. Header: Logo EventFlow (icon vuông bo góc tím chữ E trắng + text EventFlow đậm nét), các liên kết: "Khám phá", "Sự kiện", "Sự kiện của tôi", badge vai trò của user (nếu đã login), avatar tròn chữ cái viết tắt (ví dụ `AN`), dropdown menu (Hồ sơ, Quản trị - nếu là Admin/Manager, Đăng xuất). Nếu chưa đăng nhập: hiện nút "Đăng nhập" và "Đăng ký".
  2. Footer: Thông tin bản quyền CLB Tiếng Anh UEH, nhóm thực hiện Nhóm 8, nạp thư viện Bootstrap 5.3 và `assets/js/app.js`.

### Task 6: Trang chủ điều hướng (Landing / Routing)
- **File liên quan:** `index.php`
- **Logic:** Trang chủ thân thiện chào đón người dùng, hiển thị banner giới thiệu EventFlow theo thiết kế Figma 02, dẫn link đến Đăng nhập, Đăng ký hoặc Sự kiện.

---

## 3. Cấu trúc file dự kiến

```text
EventFlow/
├── config/
│   └── database.php
├── includes/
│   ├── session_auth.php
│   ├── header.php
│   └── footer.php
├── database/
│   └── schema_and_seed.sql
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── docs/
│   └── pha-1/
│       ├── plan.md
│       └── files.md
└── index.php
```

---

## 4. Tiêu chí hoàn thành (Definition of Done)
- [ ] Database schema khởi tạo thành công trên MySQL, có đủ 7 bảng quan hệ và seed data mẫu.
- [ ] Module `database.php` kết nối được tới CSDL PDO mà không phát sinh lỗi cú pháp.
- [ ] `session_auth.php` có đầy đủ các hàm hỗ trợ xác thực và phân quyền 2 tầng.
- [ ] Header và Footer hiển thị đẹp mắt, chuẩn tỉ lệ, tương thích responsive trên cả mobile và desktop.
- [ ] CSS áp dụng chuẩn xác hệ màu, typography và components theo đúng các ảnh Figma người dùng gửi.
- [ ] `files.md` được cập nhật đầy đủ và commit chuẩn format `[Pha 1]`.
