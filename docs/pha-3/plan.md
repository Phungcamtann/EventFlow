# 📋 Pha 3: Chức năng Tài khoản Quản trị (System Administrator) — Kế hoạch thực hiện

> **Mục tiêu:** Xây dựng hoàn chỉnh màn hình Quản trị Tài khoản & Quyền Hệ thống (`SCR16` - `admin/accounts.php`), giải quyết triệt để lỗi 404 Not Found khi Admin đăng nhập; cho phép System Admin xem danh sách, tìm kiếm, lọc vai trò, tạo tài khoản mới, cập nhật vai trò hệ thống (`RoleID`) và Khóa / Mở khóa tài khoản (`IsActive`). Đồng thời tinh chỉnh hiển thị vai trò 2 tầng trên Navbar để phân biệt rõ ràng Supporter / Board Member.  
> **Phụ thuộc:** Pha 1 và Pha 2 đã hoàn thành.  
> **Tham chiếu:** `docs/eventflow_readme.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html` (SCR16, FR17, PB04, UC14, BR02).

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)
- [ ] Xây dựng màn hình `admin/accounts.php` (`SCR16` - FR17, PB04):
  - Kiểm tra quyền nghiêm ngặt: Chỉ duy nhất `System Administrator` (`RoleID = 1`) được truy cập. Các vai trò khác bị chặn HTTP 403 Forbidden.
  - Bảng danh sách tài khoản (`Account` join `Role`):
    - Các cột: Mã TK (`AccountID`), Họ và tên (`FullName`), MSSV (`StudentCode`), Email, Vai trò hệ thống (`RoleName` - Badge), Trạng thái (`IsActive` - Badge Kích hoạt / Bị khóa), Ngày tạo, Thao tác.
  - Thanh tìm kiếm nhanh (theo Họ tên, MSSV, Email) và bộ lọc theo Vai trò (`RoleID`).
  - Thống kê tóm tắt nhanh (Tổng số tài khoản, Quản trị viên, Quản lý CLB, Thành viên CLB, Người tham gia).
  - Modal / Form thêm tài khoản mới từ trang Admin (FullName, Email, StudentCode, RoleID, Mật khẩu ≥ 8 ký tự).
  - Modal / Form cập nhật vai trò hệ thống (`RoleID`).
  - Chức năng Khóa / Mở khóa tài khoản (`IsActive` = 0 hoặc 1).
  - Ràng buộc nghiệp vụ `BR02`: **Chặn Admin tự khóa hoặc tự giáng quyền của chính mình**.
- [ ] Tinh chỉnh hiển thị vai trò trên Navbar (`includes/header.php`):
  - Hiển thị tường minh vai trò ủy quyền Tầng 2: Nếu người dùng là `Club Member` có `MemberRole` là `'Event Supporter'` hoặc `'Board Member'`, hiển thị rõ nhãn `Event Supporter` / `Board Member` để người dùng không bị nhầm lẫn.
- [ ] Tinh chỉnh `auth/login.php`:
  - Nếu đã đăng nhập, hiển thị thông báo rõ ràng kèm nút "Đăng xuất để đổi tài khoản" thay vì âm thầm redirect khiến người dùng nhầm lẫn phiên làm việc cũ.

### Không bao gồm (Out of scope)
- Quản lý hồ sơ CLB & Danh sách thành viên CLB (`SCR04` - `SCR07`) → Thực hiện ở **Pha 4**.

---

## 2. Tasks chi tiết

### Task 1: Xây dựng màn hình Quản trị Tài khoản (`admin/accounts.php`)
- **File:** `admin/accounts.php`
- **Logic:**
  1. Gọi `require_role('System Administrator')`.
  2. Xử lý các action POST (có kiểm tra CSRF token):
     - `create_account`: Validate dữ liệu, chống trùng Email/MSSV, băm mật khẩu `password_hash()`, insert vào `Account`.
     - `update_role`: Kiểm tra nếu `target_account_id == current_user_id()`, từ chối (BR02). Cập nhật `RoleID`.
     - `toggle_status`: Kiểm tra nếu `target_account_id == current_user_id()`, từ chối (BR02). Cập nhật `IsActive = 1 - current_status`.
  3. Xử lý truy vấn GET:
     - Nhận tham số tìm kiếm `search` và lọc `role_id`.
     - Truy vấn danh sách tài khoản theo điều kiện.
  4. Hiển thị UI chuẩn Design Rules Bootstrap 5.3:
     - Card KPI tóm tắt tài khoản.
     - Card bảng dữ liệu có phân trang/tìm kiếm.
     - Các modal: Thêm tài khoản, Đổi vai trò, Xác nhận Khóa/Mở khóa.

### Task 2: Cập nhật hiển thị vai trò và điều hướng phiên làm việc
- **File:** `includes/header.php`, `auth/login.php`
- **Logic:**
  - `header.php`: Hiển thị badge kết hợp: nếu `RoleName === 'Club Member'` và có `MemberRole` là `'Event Supporter'`, badge hiện `Event Supporter` (hoặc `Club Member · Supporter`).
  - `auth/login.php`: Nếu người dùng truy cập trang login khi đang có phiên làm việc, hiển thị banner nhắc nhở: *"Bạn đang đăng nhập với tài khoản: [Tên - Vai trò]. Bấm vào đây để Đăng xuất nếu muốn đổi tài khoản khác."*

---

## 3. Cấu trúc file dự kiến

```text
EventFlow/
├── admin/
│   └── accounts.php        ← [SCR16] Quản trị tài khoản & phân quyền hệ thống (FR17, PB04)
├── includes/
│   └── header.php          ← Cập nhật hiển thị vai trò Tầng 2 chi tiết
├── auth/
│   └── login.php           ← Hỗ trợ chuyển đổi tài khoản thuận tiện
└── docs/
    └── pha-3/
        ├── plan.md
        └── files.md
```

---

## 4. Tiêu chí hoàn thành (Definition of Done)
- [ ] Admin đăng nhập vào `admin/accounts.php` thành công, không còn lỗi 404 Not Found.
- [ ] System Admin có thể xem, tìm kiếm, lọc danh sách tài khoản.
- [ ] Thêm được tài khoản mới với đầy đủ thông tin hợp lệ.
- [ ] Đổi được vai trò người dùng (`RoleID`).
- [ ] Khóa / Mở khóa được tài khoản, không cho phép Admin tự khóa chính mình.
- [ ] Tài khoản Supporter đăng nhập hiển thị rõ ràng tư cách Supporter trên Navbar.
- [ ] Kiểm tra tĩnh (Static Check) toàn bộ code qua `php -l` không phát sinh lỗi cú pháp.
- [ ] Để người dùng tự review và commit.
