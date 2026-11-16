# 📋 Pha 7: Cập nhật Nhận diện Thương hiệu & Tải lên Hình ảnh Sự kiện / CLB — Kế hoạch thực hiện

> **Mục tiêu:** Nâng cấp toàn diện bộ nhận diện thương hiệu và hình ảnh trực quan của EventFlow bao gồm: Cập nhật Logo chính thức của website, Dọn dẹp giao diện đăng nhập production (loại bỏ hộp tài khoản demo), Xây dựng tính năng tải lên & hiển thị hình ảnh banner sự kiện (thay thế thẻ màu đơn điệu), và Cấp quyền cho Club Manager / Board Member tải lên ảnh đại diện & ảnh bìa CLB từ nguồn ảnh thực tế.  
> **Phụ thuộc:** Pha 1 đến Pha 6 đã hoàn thành.  
> **Tham chiếu:** Ý kiến chỉ đạo từ người dùng, ảnh mẫu logo và thư mục ảnh thực tế `C:\Users\CYBORG\Desktop\EV_IMG`.

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)

#### Task 1: [SP7 - 01] — Cập nhật Logo chính thức & Dọn dẹp Trang Đăng nhập
- [x] Sao chép tệp logo chữ E (`media_1794844076122_cb30b559.png`) thành `assets/images/logo.png`.
- [x] Cập nhật logo hình ảnh và Favicon trong `app/includes/header.php`.
- [x] Cập nhật logo hình ảnh trong panel thương hiệu tại `app/modules/auth/login.php` và `app/modules/auth/register.php`.
- [x] Xóa bỏ hoàn toàn khối hộp thông tin tài khoản mẫu dùng thử ở chân form đăng nhập (`app/modules/auth/login.php`).

#### Task 2: [SP7 - 02] — Cập nhật Cơ sở dữ liệu & Thư mục Upload
- [x] Thêm cột `BannerImage VARCHAR(255) NULL` vào bảng `Event`.
- [x] Thêm cột `Avatar VARCHAR(255) NULL` và `BannerImage VARCHAR(255) NULL` vào bảng `Club`.
- [x] Cập nhật file DDL gốc `database/schema_and_seed.sql`.
- [x] Tạo các thư mục lưu trữ ảnh tải lên: `assets/uploads/events/` và `assets/uploads/club/`.
- [x] Sao chép và nạp dữ liệu mẫu từ thư mục `C:\Users\CYBORG\Desktop\EV_IMG`:
  - `ảnh đại diện clb.jpg` $\rightarrow$ gán làm Avatar CLB Tiếng Anh UEH (`assets/uploads/club/ueh_club_avatar.jpg`).
  - `861405673...jpg` $\rightarrow$ gán làm Banner CLB Tiếng Anh UEH (`assets/uploads/club/ueh_club_banner.jpg`).
  - Các tệp banner `857977...png`, `855217...jpg`, `695529...jpg` $\rightarrow$ gán làm Banner sự kiện cho 3 sự kiện mẫu.

#### Task 3: [SP7 - 03] — Nâng cấp Biểu mẫu & Hiển thị Banner Sự kiện
- [x] Nâng cấp biểu mẫu Tạo/Sửa sự kiện (`app/modules/events/form.php`):
  - Hỗ trợ `enctype="multipart/form-data"`.
  - Ô chọn tệp ảnh tải lên kèm xem trước tức thời (Live Preview).
  - Xử lý kiểm tra an toàn: dung lượng $\le 5\text{MB}$, đuôi file `.jpg`, `.jpeg`, `.png`, `.webp`, lưu file với tên ngẫu nhiên.
- [x] Nâng cấp Thẻ Card sự kiện tại Trang chủ (`index.php`):
  - Thay thế thanh màu SPEAK / DEBATE / CULTURE bằng ảnh banner sự kiện 16:9 (`object-fit: cover`).
  - Gắn badge trạng thái nổi trên góc ảnh banner.
  - Hỗ trợ banner dự phòng (fallback) nếu sự kiện chưa tải ảnh lên.
- [x] Nâng cấp Trang chi tiết sự kiện (`app/modules/events/detail.php`):
  - Hiển thị banner lớn toàn cảnh sự kiện ở đầu trang chi tiết.
- [x] Nâng cấp Danh sách quản lý sự kiện (`manage.php`) và Sự kiện của tôi (`my_events.php`):
  - Hiển thị thumbnail hình ảnh sự kiện ở cột đầu tiên.

#### Task 4: [SP7 - 04] — Phân quyền & Tải lên Hình ảnh CLB
- [x] Mở rộng quyền truy cập `app/modules/club/edit.php`: Cho phép `Club Manager` và thành viên Ban điều hành (`Board Member`, trạng thái `Active`).
- [x] Cập nhật giao diện `app/modules/club/edit.php`:
  - Hỗ trợ tải lên Ảnh đại diện CLB (`Avatar`) và Ảnh bìa CLB (`BannerImage`) kèm xem trước preview.
- [x] Cập nhật giao diện hiển thị `app/modules/club/info.php`:
  - Hiển thị ảnh đại diện sắc nét và ảnh bìa CLB.
  - Mở nút Chỉnh sửa cho cả Club Manager và Board Member.

---

## 2. Tiêu chí hoàn thành (Definition of Done)

- [x] Logo website hiển thị đồng bộ trên Navbar, Login, Register và Tab trình duyệt (Favicon).
- [x] Màn hình đăng nhập sạch sẽ, không còn khối tài khoản dùng thử.
- [x] Club Manager tải được ảnh banner khi tạo/sửa sự kiện, có xem trước trước khi lưu.
- [x] Thẻ card sự kiện ở trang chủ hiển thị ảnh banner bắt mắt, độ nhận diện cao.
- [x] Trang chi tiết sự kiện và trang thông tin CLB hiển thị ảnh đại diện và banner thực tế.
- [x] Club Manager và Board Member có quyền cập nhật ảnh cho CLB.
- [x] 100% PHP syntax pass (`php -l`).
- [x] **TUYỆT ĐỐI KHÔNG TỰ COMMIT GIT** theo đúng chỉ đạo của người dùng.
