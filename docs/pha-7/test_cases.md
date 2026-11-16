# 🧪 Pha 7: Kịch bản Kiểm thử Thủ công (Test Cases) — Nhận diện Thương hiệu & Tải lên Hình ảnh

> **Mục tiêu:** Hướng dẫn chi tiết các bước kiểm thử chức năng và giao diện cho Pha 7, đảm bảo tính thẩm mỹ, độ nhận diện thương hiệu cao, tính năng tải ảnh ổn định, an toàn và phân quyền chính xác.

---

## Danh mục Kịch bản Kiểm thử

| Nhóm | Mã Kịch bản | Tên Kịch bản | Đối tượng kiểm thử |
|---|---|---|---|
| **1. Logo & Giao diện Đăng nhập** | `TC7-01` | Kiểm tra Favicon và Logo trên Top Navbar | Toàn bộ các trang |
| | `TC7-02` | Kiểm tra Logo thương hiệu ở Panel Đăng nhập & Đăng ký | Trang Đăng nhập & Đăng ký |
| | `TC7-03` | Kiểm tra Dọn dẹp Hộp thông tin tài khoản demo mẫu | Trang Đăng nhập |
| **2. Hình ảnh Banner Sự kiện** | `TC7-04` | Kiểm tra Hiển thị Banner trên Thẻ Card Sự kiện (Trang chủ) | Trang chủ (`index.php`) |
| | `TC7-05` | Kiểm tra Hiển thị Banner lớn ở Trang Chi tiết sự kiện | Chi tiết sự kiện (`detail.php`) |
| | `TC7-06` | Kiểm tra Xem trước (Live Preview) khi chọn file ảnh Banner | Form Tạo/Sửa sự kiện (`form.php`) |
| | `TC7-07` | Kiểm tra Tải lên thành công ảnh Banner mới khi Tạo/Sửa sự kiện | Form Sự kiện (`form.php`) |
| | `TC7-08` | Kiểm tra Ràng buộc bảo mật khi Upload (Dung lượng > 5MB hoặc sai định dạng) | Form Sự kiện (`form.php`) |
| | `TC7-09` | Kiểm tra Thumbnail sự kiện trên Bảng Quản lý & Sự kiện của tôi | `manage.php` & `my_events.php` |
| **3. Ảnh Đại diện & Ảnh Bìa CLB** | `TC7-10` | Kiểm tra Hiển thị Ảnh đại diện & Ảnh bìa tại Trang Thông tin CLB | Thông tin CLB (`info.php`) |
| | `TC7-11` | Kiểm tra Phân quyền Chỉnh sửa CLB dành cho Club Manager | Chỉnh sửa CLB (`edit.php`) |
| | `TC7-12` | Kiểm tra Phân quyền Chỉnh sửa CLB dành cho Board Member (Ban Điều Hành) | Chỉnh sửa CLB (`edit.php`) |
| | `TC7-13` | Kiểm tra Chặn truy cập Chỉnh sửa CLB đối với Member thông thường | Chỉnh sửa CLB (`edit.php`) |
| | `TC7-14` | Kiểm tra Tải lên và Cập nhật thành công Avatar & Banner mới cho CLB | Chỉnh sửa CLB (`edit.php`) |

---

## Chi tiết từng Kịch bản Kiểm thử

### Nhóm 1: Logo & Giao diện Đăng nhập

#### `TC7-01`: Kiểm tra Favicon và Logo trên Top Navbar
- **Mục đích:** Đảm bảo logo chữ E bo tròn hiển thị rõ nét trên thanh điều hướng và tab trình duyệt.
- **Các bước thực hiện:**
  1. Truy cập vào bất kỳ trang nào của hệ thống (ví dụ: `http://localhost/EventFlow/index.php` hoặc `http://localhost/EventFlow/app/modules/club/info.php`).
  2. Quan sát biểu tượng tab trên trình duyệt (Favicon).
  3. Quan sát logo góc trái thanh Top Navbar cạnh chữ `EventFlow`.
- **Kết quả mong đợi:**
  - Tab trình duyệt hiển thị biểu tượng logo `logo.png`.
  - Navbar hiển thị logo kích thước chuẩn `32x32px`, viền bo góc tròn mượt mà, không bị méo tỷ lệ.

#### `TC7-02`: Kiểm tra Logo thương hiệu ở Panel Đăng nhập & Đăng ký
- **Mục đích:** Kiểm tra logo thương hiệu tại giao diện xác thực người dùng.
- **Các bước thực hiện:**
  1. Truy cập `http://localhost/EventFlow/app/modules/auth/login.php` (hoặc nhấn Đăng xuất).
  2. Quan sát panel xanh bên trái.
  3. Nhấp sang `Đăng ký tài khoản` (`app/modules/auth/register.php`) và quan sát panel tương tự.
- **Kết quả mong đợi:**
  - Panel bên trái hiển thị ảnh logo `logo.png` thật thay thế cho chữ E trắng thô sơ trước đây.
  - Hình ảnh rõ nét, cân đối với chữ `EventFlow`.

#### `TC7-03`: Kiểm tra Dọn dẹp Hộp thông tin tài khoản demo mẫu
- **Mục đích:** Đảm bảo màn hình đăng nhập sạch sẽ, chuyên nghiệp, đạt chuẩn production.
- **Các bước thực hiện:**
  1. Truy cập `http://localhost/EventFlow/app/modules/auth/login.php`.
  2. Cuộn xuống chân form đăng nhập dưới dòng "Chưa có tài khoản? Đăng ký tài khoản".
- **Kết quả mong đợi:**
  - Khối chứa các badge tài khoản `admin@ueh.edu.vn`, `manager@st.ueh.edu.vn`, `supporter@st.ueh.edu.vn`, `minhan@st.ueh.edu.vn` và dòng chữ gợi ý mật khẩu **đã biến mất hoàn toàn**.
  - Form đăng nhập gọn gàng, tinh tế và tập trung vào trải nghiệm của người dùng.

---

### Nhóm 2: Hình ảnh Banner Sự kiện

#### `TC7-04`: Kiểm tra Hiển thị Banner trên Thẻ Card Sự kiện (Trang chủ)
- **Mục đích:** Đảm bảo các thẻ sự kiện có ảnh bìa chân thực, tăng tính nhận diện và thẩm mỹ.
- **Các bước thực hiện:**
  1. Đăng nhập với bất kỳ tài khoản nào và truy cập Trang chủ `http://localhost/EventFlow/index.php`.
  2. Quan sát danh sách thẻ card sự kiện.
- **Kết quả mong đợi:**
  - Thay vì thanh nền màu trơn SPEAK, DEBATE, CULTURE đơn điệu, mỗi thẻ sự kiện hiển thị hình ảnh banner chuyên nghiệp đã nạp từ thư mục `EV_IMG`.
  - Hình ảnh có chiều cao `180px`, cắt vừa vặn (`object-fit: cover`).
  - Phía góc trên bên trái của ảnh có chip danh mục (ví dụ `SPEAK`, `DEBATE`, `CULTURE`) nổi trên nền tối mờ giúp người dùng dễ nhận biết.
  - Phía dưới ảnh có badge ngày tháng và badge trạng thái đăng ký rõ ràng.

#### `TC7-05`: Kiểm tra Hiển thị Banner lớn ở Trang Chi tiết sự kiện
- **Mục đích:** Kiểm tra ảnh bìa toàn cảnh khi xem chi tiết.
- **Các bước thực hiện:**
  1. Tại Trang chủ, nhấn "Xem chi tiết" vào sự kiện `English Speaking Circle` (EventID = 1).
- **Kết quả mong đợi:**
  - Đầu thẻ thông tin sự kiện hiển thị ảnh banner lớn sắc nét.
  - Chiều cao `260px`, hiển thị hài hòa cùng tiêu đề sự kiện, thời gian và địa điểm.

#### `TC7-06`: Kiểm tra Xem trước (Live Preview) khi chọn file ảnh Banner
- **Mục đích:** Người tạo/sửa sự kiện có thể kiểm tra hình ảnh trước khi lưu.
- **Các bước thực hiện:**
  1. Đăng nhập tài khoản Club Manager (`manager@st.ueh.edu.vn`).
  2. Truy cập `app/modules/events/form.php` (Tạo sự kiện mới) hoặc `form.php?id=1` (Chỉnh sửa).
  3. Tại trường "Hình ảnh / Banner sự kiện", nhấn "Choose File" và chọn một ảnh bất kỳ từ máy tính.
- **Kết quả mong đợi:**
  - Ngay khi vừa chọn file, khung "Xem trước banner mới" lập tức xuất hiện bên dưới và hiển thị đúng bức ảnh vừa chọn (không cần reload trang).

#### `TC7-07`: Kiểm tra Tải lên thành công ảnh Banner mới khi Tạo/Sửa sự kiện
- **Mục đích:** Đảm bảo ảnh tải lên được lưu trữ an toàn trong `assets/uploads/events/` và cập nhật đường dẫn vào MySQL.
- **Các bước thực hiện:**
  1. Tại trang Sửa sự kiện `English Speaking Circle` (`form.php?id=1`), chọn một tệp ảnh mới.
  2. Nhấn "Lưu thay đổi".
  3. Chuyển ra Trang chủ hoặc xem lại sự kiện.
- **Kết quả mong đợi:**
  - Hệ thống thông báo "Cập nhật sự kiện thành công!".
  - Sự kiện lập tức hiển thị ảnh banner mới tại Trang chủ và Trang chi tiết.
  - Trong thư mục `assets/uploads/events/` xuất hiện file mới có định dạng `event_<timestamp>_<hash>.<ext>`.

#### `TC7-08`: Kiểm tra Ràng buộc bảo mật khi Upload
- **Mục đích:** Chặn các tệp tin không hợp lệ hoặc dung lượng vượt mức quy định.
- **Các bước thực hiện:**
  1. Thử chọn một file không phải ảnh (ví dụ file `.txt`, `.pdf`, `.zip`) hoặc file ảnh dung lượng $> 5\text{MB}$.
  2. Nhấn gửi form.
- **Kết quả mong đợi:**
  - Hệ thống từ chối tải lên và thông báo lỗi rõ ràng: *"Định dạng hình ảnh banner không hợp lệ..."* hoặc *"Dung lượng ảnh banner không được vượt quá 5MB."*
  - CSDL không bị ảnh hưởng.

#### `TC7-09`: Kiểm tra Thumbnail sự kiện trên Bảng Quản lý & Sự kiện của tôi
- **Mục đích:** Giúp người quản lý và người tham gia nhận diện nhanh sự kiện qua ảnh thu nhỏ.
- **Các bước thực hiện:**
  1. Truy cập `app/modules/events/manage.php` (với tài khoản Club Manager).
  2. Truy cập `app/modules/events/my_events.php` (với tài khoản sinh viên đã đăng ký sự kiện).
- **Kết quả mong đợi:**
  - Ở cột đầu tiên cạnh tên sự kiện có thumbnail thu nhỏ bo góc gọn gàng (`48x32px`).

---

### Nhóm 3: Ảnh Đại diện & Ảnh Bìa CLB

#### `TC7-10`: Kiểm tra Hiển thị Ảnh đại diện & Ảnh bìa tại Trang Thông tin CLB
- **Mục đích:** Đảm bảo trang giới thiệu CLB có nhận diện trực quan sinh động.
- **Các bước thực hiện:**
  1. Truy cập `http://localhost/EventFlow/app/modules/club/info.php`.
- **Kết quả mong đợi:**
  - Khung banner lớn của CLB có nền là hình ảnh bìa câu lạc bộ với lớp phủ gradient tối sang trọng, tôn lên các dòng chữ màu trắng.
  - Ô vuông biểu tượng CLB hiển thị ảnh đại diện chính thức của Bell Club (`ueh_club_avatar.jpg`) kích thước `88x88px`, viền trắng nổi bật.

#### `TC7-11`: Kiểm tra Phân quyền Chỉnh sửa CLB dành cho Club Manager
- **Mục đích:** Club Manager toàn quyền cập nhật thông tin và hình ảnh CLB.
- **Các bước thực hiện:**
  1. Đăng nhập tài khoản `manager@st.ueh.edu.vn`.
  2. Truy cập `app/modules/club/info.php`.
- **Kết quả mong đợi:**
  - Xuất hiện nút "Chỉnh sửa thông tin".
  - Nhấp vào nút dẫn đến trang `app/modules/club/edit.php` thành công, form mở đầy đủ các trường nhập liệu và 2 ô tải ảnh: `Ảnh đại diện CLB (Avatar)` và `Ảnh bìa CLB (Banner)`.

#### `TC7-12`: Kiểm tra Phân quyền Chỉnh sửa CLB dành cho Board Member (Ban Điều Hành)
- **Mục đích:** Thành viên Ban Điều Hành được ủy quyền cập nhật hồ sơ và hình ảnh CLB theo yêu cầu.
- **Các bước thực hiện:**
  1. Đăng xuất và đăng nhập tài khoản của thành viên Ban Điều Hành (hoặc tài khoản có `RoleName = 'Club Member'`, `MemberRole = 'Board Member'`, `Status = 'Active'`).
  2. Quan sát Top Navbar và trang `app/modules/club/info.php`.
  3. Thử nhấp vào nút "Chỉnh sửa thông tin" hoặc nút "Cập nhật CLB" trên Top Navbar.
- **Kết quả mong đợi:**
  - Thành viên Ban Điều Hành được cấp quyền truy cập vào `app/modules/club/edit.php`.
  - Tiêu đề badge trên form ghi nhận: *"Dành cho Club Manager & Ban Điều Hành"*.
  - Người dùng có thể chỉnh sửa mô tả, tải ảnh đại diện và ảnh bìa mới.

#### `TC7-13`: Kiểm tra Chặn truy cập Chỉnh sửa CLB đối với Member thông thường
- **Mục đích:** Bảo vệ dữ liệu CLB không bị can thiệp bởi sinh viên thông thường hoặc thành viên chưa được ủy quyền.
- **Các bước thực hiện:**
  1. Đăng nhập tài khoản `student@st.ueh.edu.vn` (Event Participant) hoặc tài khoản `Club Member` có `MemberRole = 'Member'`.
  2. Truy cập `app/modules/club/info.php`.
  3. Thử truy cập trực tiếp đường link `http://localhost/EventFlow/app/modules/club/edit.php`.
- **Kết quả mong đợi:**
  - Trên trang `info.php`, nút "Chỉnh sửa thông tin" không hiển thị.
  - Khi cố tình gõ URL trực tiếp, hệ thống lập tức chặn lại, điều hướng về `info.php` và hiển thị thông báo: *"Bạn không có quyền truy cập chức năng chỉnh sửa thông tin CLB."*

#### `TC7-14`: Kiểm tra Tải lên và Cập nhật thành công Avatar & Banner mới cho CLB
- **Mục đích:** Đảm bảo tính năng upload ảnh CLB hoạt động trơn tru.
- **Các bước thực hiện:**
  1. Sử dụng tài khoản Manager hoặc Board Member truy cập `app/modules/club/edit.php`.
  2. Chọn ảnh mới cho Avatar CLB $\rightarrow$ quan sát xem trước preview.
  3. Chọn ảnh mới cho Banner CLB $\rightarrow$ quan sát xem trước preview.
  4. Nhấn "Lưu thay đổi".
- **Kết quả mong đợi:**
  - Hệ thống báo: *"Cập nhật thông tin Câu lạc bộ thành công!"*.
  - Trang `info.php` hiển thị ngay lập tức Avatar và Banner mới.
  - Các tệp ảnh được lưu tại `assets/uploads/club/` với tên chuẩn `club_avatar_<timestamp>_<hash>.<ext>` và `club_banner_<timestamp>_<hash>.<ext>`.
