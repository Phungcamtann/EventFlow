# 📋 Pha 4: Giao diện Thông tin CLB & Quản lý Danh sách Thành viên — Kế hoạch thực hiện

> **Mục tiêu:** Xây dựng hoàn chỉnh phân hệ CLB & Thành viên bao gồm: Xem thông tin công khai CLB (`SCR04` - `club/info.php`), Chỉnh sửa hồ sơ CLB dành cho Club Manager (`SCR05` - `club/edit.php`), Xem & Tìm kiếm danh sách thành viên CLB (`SCR06` - `club/members.php`), và Thêm/Sửa vai trò & trạng thái thành viên (`SCR07` - `club/member_form.php`).  
> **Phụ thuộc:** Pha 1, Pha 2 và Pha 3 đã hoàn thành.  
> **Tham chiếu:** `docs/eventflow_readme.md`, `docs/eventflow-design-rules.md`, `docs/eventflow_vibe_code_design_specification.html` (SCR04, SCR05, SCR06, SCR07 / FR05, FR06, FR07, FR08 / PB06, PB07, PB08, PB09, PB10 / BR03).

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)
- [x] **`SCR04` - Xem thông tin CLB công khai (`club/info.php` - FR06, PB06, UC05):**
  - Mọi người dùng và khách đều có thể xem.
  - Hiển thị đầy đủ: Tên CLB (`ClubName`), Giới thiệu & Lĩnh vực hoạt động (`Description`), Thông tin liên hệ (`ContactInfo`: Email, Fanpage, Cơ sở), Thông tin Chủ nhiệm CLB (`Manager`: Họ tên, Email, MSSV).
  - Thống kê tóm tắt: Tổng số thành viên CLB (`Active`), Tổng số sự kiện đã tổ chức.
  - Nút "Chỉnh sửa thông tin CLB" chỉ hiển thị cho `Club Manager`.
  - Nút "Quản lý danh sách thành viên" hiển thị cho `Club Manager`.
- [x] **`SCR05` - Quản lý / Chỉnh sửa thông tin CLB (`club/edit.php` - FR05, PB07, UC17):**
  - Quyền truy cập: Chỉ dành riêng cho `Club Manager` (`RoleID = 2`). Các vai trò khác bị chặn HTTP 403 Forbidden.
  - Form chỉnh sửa: Tên CLB (`ClubName`), Giới thiệu (`Description`), Thông tin liên hệ (`ContactInfo`).
  - Kiểm tra hợp lệ dữ liệu, bảo vệ CSRF.
  - Cập nhật vào bảng `Club`, hiển thị thông báo flash thành công và quay lại `club/info.php`.
- [x] **`SCR06` - Danh sách thành viên CLB (`club/members.php` - FR07, PB08, UC06):**
  - Quyền truy cập: `Club Manager` (kiểm tra `require_role('Club Manager')`).
  - Bảng danh sách thành viên (`Club_Member` join `Account`):
    - Mã TV (`MemberID`), Họ và tên (`FullName`), MSSV (`StudentCode`), Email, Vai trò nội bộ (`MemberRole`: `'Board Member'`, `'Event Supporter'`, `'Member'`), Trạng thái (`Status`: `'Active'`, `'Inactive'`), Ngày gia nhập (`JoinedDate`).
  - Thanh tìm kiếm nhanh theo Họ tên, MSSV hoặc Email.
  - Bộ lọc theo Vai trò nội bộ (`MemberRole`) và Trạng thái (`Status`).
  - Nút mở Form Thêm thành viên mới hoặc Sửa thông tin thành viên (`SCR07`).
- [x] **`SCR07` - Thêm / Sửa thành viên CLB (`club/member_form.php` - FR07, FR08, PB09, PB10, UC06):**
  - Quyền truy cập: Chỉ dành cho `Club Manager`.
  - Chế độ Thêm mới (`action=add`):
    - Chọn tài khoản người dùng (`Account`) có sẵn trên hệ thống chưa tham gia CLB (hoặc nhập Email/MSSV để gán vào CLB).
    - Phân vai trò nội bộ ban đầu (`MemberRole`): `'Member'`, `'Board Member'`, hoặc `'Event Supporter'` (vai trò ủy quyền phục vụ hỗ trợ sự kiện/check-in).
    - Trạng thái mặc định: `'Active'`.
    - Ngày tham gia (`JoinedDate`): Mặc định ngày hiện tại.
  - Chế độ Chỉnh sửa (`action=edit`):
    - Cập nhật `MemberRole`: `'Member'`, `'Board Member'`, hoặc `'Event Supporter'`.
    - Cập nhật `Status`: `'Active'` hoặc `'Inactive'`.
    - **Ràng buộc nghiệp vụ `BR03` (Bảo vệ Club Manager cuối cùng):** Chặn thao tác tự gỡ quyền hoặc chuyển trạng thái `Inactive` đối với tài khoản giữ quyền quản lý CLB duy nhất còn lại.

### Không bao gồm (Out of scope)
- Phân hệ Quản lý sự kiện, Đăng ký & Điểm danh sự kiện (`SCR08` - `SCR15`) → Thực hiện ở các pha tiếp theo.

---

## 2. Tasks chi tiết

### Task 1: Xây dựng màn hình Xem thông tin CLB công khai (`club/info.php`)
- **File:** `club/info.php`
- **Logic:**
  1. Truy vấn thông tin CLB (ID = 1 theo phạm vi MVP 1 CLB) kèm thông tin Chủ nhiệm CLB từ bảng `Account`.
  2. Đếm tổng số thành viên đang hoạt động (`Status = 'Active'`) từ bảng `Club_Member`.
  3. Đếm tổng số sự kiện đã công bố từ bảng `Event`.
  4. Hiển thị UI sang trọng: Banner giới thiệu, thông tin liên hệ, thẻ ban chủ nhiệm, thống kê hoạt động, nút điều hướng quản trị nếu người xem là Club Manager.

### Task 2: Xây dựng màn hình Chỉnh sửa thông tin CLB (`club/edit.php`)
- **File:** `club/edit.php`
- **Logic:**
  1. Gọi `require_role('Club Manager')`.
  2. GET: Lấy thông tin hiện tại của CLB đổ vào form.
  3. POST: Xác thực CSRF, validate `ClubName` (không để trống, tối đa 150 ký tự), cập nhật bảng `Club`.
  4. Chuyển hướng về `club/info.php` kèm thông báo flash thành công.

### Task 3: Xây dựng màn hình Danh sách thành viên CLB (`club/members.php`)
- **File:** `club/members.php`
- **Logic:**
  1. Gọi `require_role('Club Manager')`.
  2. GET: Xử lý tìm kiếm (`search`) và bộ lọc (`role`, `status`).
  3. Truy vấn `Club_Member` join `Account` với điều kiện tìm kiếm.
  4. Hiển thị bảng danh sách thành viên với badge vai trò nội bộ và badge trạng thái, nút chuyển sang form sửa thành viên.

### Task 4: Xây dựng màn hình Thêm / Sửa thành viên CLB (`club/member_form.php`)
- **File:** `club/member_form.php`
- **Logic:**
  1. Gọi `require_role('Club Manager')`.
  2. Xử lý Thêm mới: Lấy danh sách Account chưa thuộc CLB để chọn, insert vào `Club_Member`.
  3. Xử lý Chỉnh sửa:
     - Kiểm tra ràng buộc BR03: Nếu thành viên là Manager của CLB (`Club.ManagerID == AccountID`), không cho phép chuyển sang `Inactive`.
     - Cập nhật `MemberRole` và `Status`.
  4. Chuyển hướng về `club/members.php` kèm thông báo flash thành công.

---

## 3. Cấu trúc file dự kiến

```text
EventFlow/
├── club/
│   ├── info.php            ← [SCR04] Xem thông tin công khai CLB (FR06, PB06)
│   ├── edit.php            ← [SCR05] Quản lý & chỉnh sửa thông tin CLB (FR05, PB07)
│   ├── members.php         ← [SCR06] Danh sách & tìm kiếm thành viên CLB (FR07, PB08)
│   └── member_form.php     ← [SCR07] Thêm / Sửa thành viên (vai trò & trạng thái) (FR07, FR08, PB09, PB10)
└── docs/
    └── pha-4/
        ├── plan.md
        └── files.md
```

---

## 4. Tiêu chí hoàn thành (Definition of Done)
- [x] Bất kỳ người dùng nào cũng có thể vào xem thông tin CLB tại `club/info.php`.
- [x] Club Manager có thể chỉnh sửa thông tin CLB và dữ liệu được lưu tức thì vào CSDL.
- [x] Club Manager có thể xem, tìm kiếm và lọc danh sách thành viên CLB.
- [x] Thêm thành viên mới vào CLB thành công, phân đúng các vai trò nội bộ: `Member`, `Board Member`, `Event Supporter`.
- [x] Cập nhật vai trò và trạng thái thành viên tuân thủ nghiêm ngặt quy tắc BR03 (bảo vệ Manager).
- [x] 100% các file kiểm tra tĩnh qua `php -l` không phát sinh lỗi cú pháp.
- [x] Bàn giao để người dùng tự duyệt và commit.
