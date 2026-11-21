# 📁 Sprint 7: Tối ưu hóa & Khắc phục lỗi UAT Nhóm 8 — Danh sách File (Files)

> **Nhánh Git:** `Sprint-7`  
> **Trạng thái:** 🟡 Đang hoàn thiện các hạng mục Quick-wins  
> **Cập nhật:** 21/11/2026  

---

## 1. Tổng hợp tệp tin tác động trong Sprint 7

| # | Đường dẫn file | Mô tả nội dung tác động | Mã UAT liên quan | Trạng thái |
|---|---|---|---|:---:|
| 1 | `app/modules/auth/login.php` | Thêm class chống tràn chữ phụ đề, bo tròn thẻ preview sự kiện responsive | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 2 | `app/modules/auth/register.php` | Thêm class chống tràn chữ phụ đề, fix padding & responsive card | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 3 | `assets/css/style.css` | Bổ sung class `.ef-auth-subtitle`, `overflow-x: hidden`, breakpoint responsive navbar & nút bấm | UAT 01, UAT 04 | ✅ Đã push (`9d57c39`, `9f2cd57`) |
| 4 | `index.php` | Lọc sự kiện theo `EndTime >= NOW()` và `Status = 'Published'`, chuẩn hóa nhãn trạng thái | UAT 03 | ✅ Đã push (`0e1e431`, `7ba3769`) |
| 5 | `app/modules/profile/index.php` | Khóa trường `StudentCode` (MSSV) chỉ đọc và loại bỏ cập nhật trường này ở backend | UAT 12 | ✅ Đã push (`bfc5a74`) |
| 6 | `app/modules/club/member_form.php` | Tích hợp AJAX search tức thì theo sự kiện `input` (sử dụng `AbortController` chống race condition) theo MSSV/Email và hiển thị card đối chiếu thông tin | UAT 07 | 🟢 Hoàn thành (Chờ test & commit) |
| 7 | `app/modules/club/members.php` | Tích hợp lọc & tìm kiếm thành viên tức thì theo sự kiện `input` (dùng `AbortController`) không độ trễ, không tải lại trang | UAT 07+ | 🟢 Hoàn thành (Chờ test & commit) |
| 8 | `docs/sprint-7/plan.md` | Kế hoạch triển khai Sprint 7 và các hạng mục UAT | - | ✅ Đã cập nhật |
| 9 | `docs/sprint-7/files.md` | Danh sách theo dõi thay đổi tệp tin trong Sprint 7 | - | ✅ Đã cập nhật |
| 10 | `app/modules/attendance/checkin.php` | Tối ưu hóa realtime DOM toggle điểm danh không tải lại trang | UAT 17 | ⏳ Kế hoạch tiếp theo |
