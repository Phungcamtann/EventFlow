# 📁 Pha 7: Cập nhật Nhận diện Thương hiệu & Tải lên Hình ảnh Sự kiện / CLB — Danh sách File

> **Trạng thái:** 🟢 Hoàn thành 100%  
> **Ngày thực hiện:** 16/11/2026  
> **Mục tiêu:** Cập nhật Logo website, xóa thông tin tài khoản demo ở trang đăng nhập, hỗ trợ upload ảnh banner sự kiện, ảnh đại diện và ảnh bìa CLB với phân quyền linh hoạt cho Club Manager & Ban Điều Hành (Board Member).  

---

## Tổng kết

| Loại | Số lượng |
|---|---|
| File / Thư mục tạo mới | 8 |
| File chỉnh sửa | 10 |
| File xóa | 0 |
| **Tổng** | **18** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file / thư mục | Mô tả | Trạng thái |
|---|---|---|:---:|
| 1 | `assets/images/logo.png` | Logo chính thức biểu tượng chữ "E" bo tròn của EventFlow | ✅ Đã lưu |
| 2 | `assets/uploads/events/` | Thư mục lưu trữ hình ảnh banner sự kiện tải lên | ✅ Đã tạo |
| 3 | `assets/uploads/club/` | Thư mục lưu trữ ảnh đại diện và ảnh bìa CLB tải lên | ✅ Đã tạo |
| 4 | `assets/uploads/events/event_speaking.png` | Banner ảnh mẫu sự kiện English Speaking Circle | ✅ Đã lưu |
| 5 | `assets/uploads/events/event_debate.jpg` | Banner ảnh mẫu sự kiện Debate Lab: Speak Up | ✅ Đã lưu |
| 6 | `assets/uploads/events/event_culture.jpg` | Banner ảnh mẫu sự kiện Cultural Exchange Day | ✅ Đã lưu |
| 7 | `assets/uploads/events/default_banner.jpg` | Banner mặc định dự phòng cho sự kiện | ✅ Đã lưu |
| 8 | `assets/uploads/club/ueh_club_avatar.jpg` | Ảnh đại diện chính thức CLB Tiếng Anh UEH (Bell Club) | ✅ Đã lưu |
| 9 | `assets/uploads/club/ueh_club_banner.jpg` | Ảnh bìa chính thức CLB Tiếng Anh UEH (Bell Club) | ✅ Đã lưu |
| 10 | `docs/pha-7/plan.md` | Bản kế hoạch chi tiết, phạm vi và tiêu chí nghiệm thu Pha 7 | ✅ Hoàn thành |
| 11 | `docs/pha-7/files.md` | Bản tổng hợp danh sách file thay đổi trong Pha 7 | ✅ Hoàn thành |
| 12 | `docs/pha-7/test_cases.md` | Bộ kịch bản kiểm thử giao diện & chức năng tải ảnh Pha 7 | ✅ Hoàn thành |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---|---|:---:|
| 1 | `assets/css/style.css` | Thêm class `.ef-logo-img` định dạng kích thước logo thương hiệu chuẩn xác | ✅ Hoàn thành |
| 2 | `app/includes/header.php` | Thêm Favicon logo vào thẻ `<head>`; thay thế logo văn bản bằng thẻ `<img>` logo; bổ sung nút "Cập nhật CLB" trên Top Navbar cho Board Member | ✅ Hoàn thành |
| 3 | `app/includes/session_auth.php` | Bổ sung hàm kiểm tra quyền `can_manage_club_profile()` cho phép cả `Club Manager` và `Board Member` (Active) quản lý hồ sơ CLB | ✅ Hoàn thành |
| 4 | `app/modules/auth/login.php` | Thay logo chữ E bằng ảnh logo thật; Xóa hoàn toàn hộp thông tin tài khoản demo mẫu ở chân form đăng nhập | ✅ Hoàn thành |
| 5 | `app/modules/auth/register.php` | Thay thế logo chữ E bằng ảnh logo thật ở panel thương hiệu | ✅ Hoàn thành |
| 6 | `index.php` | Nâng cấp thẻ Card sự kiện: hiển thị ảnh banner tỷ lệ 16:9 sắc nét, gắn chip Category và badge trạng thái; fallback ảnh mặc định an toàn | ✅ Hoàn thành |
| 7 | `app/modules/events/detail.php` | Hiển thị ảnh banner sự kiện kích thước lớn ở đầu khung nội dung sự kiện | ✅ Hoàn thành |
| 8 | `app/modules/events/form.php` | Thêm `enctype="multipart/form-data"`; trường upload ảnh banner sự kiện, xác thực đuôi file, dung lượng $\le 5\text{MB}$, MIME type, lưu CSDL; live preview bằng JS | ✅ Hoàn thành |
| 9 | `app/modules/events/manage.php` | Hiển thị thumbnail hình ảnh banner sự kiện trong bảng quản lý của Club Manager | ✅ Hoàn thành |
| 10 | `app/modules/events/my_events.php` | Hiển thị thumbnail hình ảnh banner sự kiện trong bảng Sự kiện của tôi | ✅ Hoàn thành |
| 11 | `app/modules/club/info.php` | Hiển thị ảnh đại diện (`Avatar`) CLB thực tế (88x88px); hiển thị ảnh bìa CLB làm nền header; mở nút "Chỉnh sửa thông tin" cho cả Manager và Board Member | ✅ Hoàn thành |
| 12 | `app/modules/club/edit.php` | Nới lỏng quyền truy cập cho Club Manager & Board Member; thêm form upload `Avatar` và `BannerImage` CLB kèm xem trước preview JS và xử lý lưu DB | ✅ Hoàn thành |
| 13 | `database/schema_and_seed.sql` | Thêm cột `BannerImage` vào bảng `Event`; thêm cột `Avatar` và `BannerImage` vào bảng `Club`; cập nhật dữ liệu seed mẫu | ✅ Hoàn thành |

---

## Kiểm thử cú pháp tĩnh (PHP Lint)

```
index.php                               -> No syntax errors detected
app/includes/header.php                 -> No syntax errors detected
app/includes/session_auth.php           -> No syntax errors detected
app/modules/auth/login.php              -> No syntax errors detected
app/modules/auth/register.php           -> No syntax errors detected
app/modules/events/form.php             -> No syntax errors detected
app/modules/events/detail.php           -> No syntax errors detected
app/modules/events/manage.php           -> No syntax errors detected
app/modules/events/my_events.php        -> No syntax errors detected
app/modules/club/info.php               -> No syntax errors detected
app/modules/club/edit.php               -> No syntax errors detected
```
*Kết quả: 100% tệp tin PHP vượt qua kiểm tra cú pháp, không phát sinh bất kỳ lỗi nào.*  
*Lưu ý: Không thực hiện commit Git tự động theo đúng chỉ dẫn của người dùng.*
