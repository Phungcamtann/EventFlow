# 🧪 Pha 6: Bộ Kịch Bản Kiểm Thử (Test Cases) — Hoàn thiện Cốt lõi MVP (Hồ sơ cá nhân & Điểm danh bổ sung)

> **Dự án:** EventFlow — Quản lý Hoạt động và Thành viên CLB Tiếng Anh Sinh Viên  
> **Phân hệ:** Hồ sơ cá nhân & Điểm danh bổ sung (`SCR03`, `SCR14` / FR04, FR15 / PB05, PB19)  
> **Người thực hiện:** QA / Tester (Tiêu Lâm Định Quốc)  
> **Ngày lập:** 16/11/2026  
> **Trạng thái:** 🟢 Hoàn thành 100% — Sẵn sàng nghiệm thu UAT  

---

## 1. Danh Sách Tài Khoản Mẫu Phục Vụ Kiểm Thử (Seed Accounts)

| Vai trò kiểm thử | Email đăng nhập | Mật khẩu mặc định | Ghi chú quyền hạn |
|:---|:---|:---|:---|
| **Club Manager** | `manager@eventflow.local` | `Password@123` | Chủ nhiệm CLB, quản lý hồ sơ CLB, thành viên, sự kiện và điểm danh |
| **Event Supporter** | `supporter@eventflow.local` | `Password@123` | Thành viên ủy quyền (Active), có quyền xem danh sách đăng ký và điểm danh walk-in |
| **Club Member** | `member@eventflow.local` | `Password@123` | Thành viên CLB chính thức, quản lý hồ sơ cá nhân, xem tư cách thành viên |
| **Event Participant**| `student@eventflow.local` | `Password@123` | Sinh viên đăng ký tham gia, quản lý hồ sơ cá nhân |
| **System Admin** | `admin@eventflow.local` | `Password@123` | Quản trị tài khoản hệ thống |

---

## 2. Bảng Kịch Bản Kiểm Thử Chi Tiết (Test Cases)

### Nhóm 1: Hồ Sơ Cá Nhân & Xem Tư Cách Thành Viên (`SCR03` — `app/modules/profile/index.php` / FR04, PB05, UC04)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP6_01** | Chặn khách chưa đăng nhập | Chưa đăng nhập hệ thống | 1. Xóa session/cookie.<br>2. Truy cập trực tiếp `app/modules/profile/index.php`. | URL `profile/index.php` | Chuyển hướng về trang đăng nhập `app/modules/auth/login.php` kèm cảnh báo vàng: "Vui lòng đăng nhập để tiếp tục." | ⬜ Pass |
| **TC_SP6_02** | Xem thông tin hồ sơ và tư cách CLB | Đăng nhập tài khoản `member@eventflow.local` | 1. Nhấn vào avatar trên Navbar $\rightarrow$ chọn "Hồ sơ cá nhân".<br>2. Quan sát thông tin hiển thị. | Không | Hiển thị đúng Họ tên, Email, MSSV, vai trò hệ thống `Club Member`, thẻ tư cách CLB "UEH English Club", vai trò nội bộ `Member`, ngày gia nhập và trạng thái `Active`. | ⬜ Pass |
| **TC_SP6_03** | Cập nhật Họ và tên thành công | Đã đăng nhập tài khoản cá nhân | 1. Tại tab Thông tin cá nhân, sửa trường Họ và tên.<br>2. Bấm "Lưu thay đổi". | Họ tên: "Nguyễn Văn Test" | Lưu thành công vào bảng `Account`, đồng bộ session `FullName`. Tên và avatar initials trên Navbar cập nhật tức thời, thông báo flash xanh. | ⬜ Pass |
| **TC_SP6_04** | Cập nhật Mã số sinh viên (MSSV) | Đã đăng nhập tài khoản cá nhân | 1. Nhập MSSV mới chưa có ai sử dụng.<br>2. Bấm "Lưu thay đổi". | MSSV: `31241029999` | Lưu thành công vào bảng `Account`, badge MSSV hiển thị chính xác. | ⬜ Pass |
| **TC_SP6_05** | Chặn cập nhật trùng MSSV với tài khoản khác | Hệ thống đã có tài khoản mang MSSV `31241020320` | 1. Nhập MSSV `31241020320`.<br>2. Bấm "Lưu thay đổi". | MSSV trùng | Hệ thống từ chối cập nhật, hiển thị thông báo lỗi đỏ: "Mã số sinh viên này đã được sử dụng bởi một tài khoản khác." | ⬜ Pass |
| **TC_SP6_06** | Chặn bỏ trống Họ và tên | Đã đăng nhập | 1. Xóa trắng ô Họ và tên.<br>2. Bấm "Lưu thay đổi". | FullName = rỗng | Bị chặn và hiển thị lỗi: "Họ và tên không được để trống." | ⬜ Pass |
| **TC_SP6_07** | Đổi mật khẩu thành công | Đã đăng nhập | 1. Nhập Mật khẩu hiện tại `Password@123`.<br>2. Nhập Mật khẩu mới `NewPassword@123` ($\ge 8$ ký tự).<br>3. Xác nhận mật khẩu mới khớp.<br>4. Bấm "Cập nhật mật khẩu". | Đúng MK cũ, MK mới $\ge 8$ ký tự | Cập nhật thành công vào CSDL với hash mới. Thông báo flash xanh. Đăng xuất và đăng nhập lại bằng mật khẩu mới thành công. | ⬜ Pass |
| **TC_SP6_08** | Chặn đổi mật khẩu khi sai MK hiện tại | Đã đăng nhập | 1. Nhập sai MK hiện tại: `WrongPass123`.<br>2. Nhập MK mới hợp lệ.<br>3. Bấm "Cập nhật mật khẩu". | Sai MK hiện tại | Từ chối cập nhật, hiển thị lỗi đỏ: "Mật khẩu hiện tại không chính xác." | ⬜ Pass |
| **TC_SP6_09** | Chặn đổi mật khẩu khi MK mới $< 8$ ký tự hoặc không khớp | Đã đăng nhập | 1. Nhập MK mới `12345` ($< 8$ ký tự) hoặc xác nhận mật khẩu gõ sai. | MK mới ngắn / không khớp | Báo lỗi tương ứng: "Mật khẩu mới phải có ít nhất 8 ký tự." hoặc "Mật khẩu xác nhận không khớp với mật khẩu mới." | ⬜ Pass |
| **TC_SP6_10** | Chặn đổi mật khẩu trùng MK cũ | Đã đăng nhập | 1. Nhập MK mới giống y hệt MK hiện tại. | MK mới = MK cũ | Báo lỗi: "Mật khẩu mới không được trùng với mật khẩu hiện tại." | ⬜ Pass |
| **TC_SP6_11** | Chống CSRF cho form hồ sơ | Đã đăng nhập | 1. Giả lập submit form cập nhật hồ sơ / đổi mật khẩu không kèm CSRF token hợp lệ. | CSRF token rỗng / sai | Từ chối xử lý, báo lỗi bảo mật: "Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn." | ⬜ Pass |

---

### Nhóm 2: Điểm Danh Bổ Sung Walk-in Check-in (`SCR14` — `app/modules/attendance/checkin.php` / FR15, PB19, UC12, NFR01)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP6_12** | Hiển thị nút & Modal Điểm danh bổ sung | Đăng nhập `Club Manager` hoặc `Event Supporter`, sự kiện `Published` chưa kết thúc | 1. Truy cập màn hình điểm danh 1 sự kiện `checkin.php?event_id=1`.<br>2. Quan sát góc trên bên phải. | Sự kiện đang mở | Nút "Điểm danh bổ sung (Chưa đăng ký)" màu xanh lá hiển thị rõ ràng. Nhấn nút mở ra modal Bootstrap tra cứu nhanh sinh viên. | ⬜ Pass |
| **TC_SP6_13** | Tra cứu nhanh sinh viên trong modal | Đang mở modal Điểm danh bổ sung | 1. Gõ tên hoặc MSSV hoặc Email của sinh viên vào ô tìm kiếm. | Từ khóa: tên / MSSV | Bảng kết quả lọc tức thời không dấu ($\le 1\text{s}$), hiển thị đúng các sinh viên khớp từ khóa kèm trạng thái đăng ký hiện tại. | ⬜ Pass |
| **TC_SP6_14** | Điểm danh bổ sung thành công (Chưa từng đăng ký) | Sinh viên chưa từng đăng ký sự kiện, sự kiện còn chỗ trống | 1. Tại modal, tìm sinh viên chưa đăng ký.<br>2. Nhấn nút "Điểm danh ngay". | Action: `walkin_checkin` | Hệ thống tự động tạo bản ghi `Event_Registration` với `Status = 'Registered'` và ghi ngay bản ghi `Attendance` với `AttendanceStatus = 1`. Thông báo flash xanh, tự động highlight dòng người vừa được điểm danh. | ⬜ Pass |
| **TC_SP6_15** | Điểm danh bổ sung cho người từng hủy đăng ký | Sinh viên đã từng hủy đăng ký (`Status = 'Cancelled'`) | 1. Tại modal, chọn sinh viên có trạng thái "Đã hủy đăng ký".<br>2. Nhấn "Điểm danh ngay". | Action: `walkin_checkin` | Kích hoạt lại dòng đăng ký cũ thành `Status = 'Registered'` (không bị lỗi trùng khóa UNIQUE), ghi nhận điểm danh thành công. | ⬜ Pass |
| **TC_SP6_16** | Chặn điểm danh bổ sung khi sự kiện hết chỗ | Sự kiện đã đủ số lượng tham dự tối đa (`RegisteredCount == MaxCapacity`) | 1. Sự kiện có `MaxCapacity = 30`, hiện đã có 30 người đăng ký.<br>2. Cố gắng bấm "Điểm danh ngay" cho người bổ sung. | Action: `walkin_checkin` | Transaction rollback, từ chối với thông báo: "Sự kiện đã đủ số lượng tham dự tối đa (30 chỗ). Không thể nhận thêm đăng ký bổ sung." (Đạt chuẩn `NFR01`). | ⬜ Pass |
| **TC_SP6_17** | Chặn điểm danh trùng cho người đã check-in | Sinh viên đã được điểm danh trước đó | 1. Kiểm tra danh sách ứng viên trong modal. | Người đã có mặt | Sinh viên đã điểm danh không xuất hiện trong danh sách bổ sung. Nếu cố tình gửi POST giả lập sẽ bị từ chối: "Sinh viên [...] đã được điểm danh trước đó." | ⬜ Pass |
| **TC_SP6_18** | Chặn điểm danh bổ sung tài khoản bị khóa | Tài khoản sinh viên có `IsActive = 0` | 1. Admin đã khóa tài khoản sinh viên X.<br>2. Gửi request điểm danh bổ sung cho sinh viên X. | Tài khoản `IsActive = 0` | Hệ thống từ chối: "Tài khoản sinh viên này đang bị khóa trong hệ thống." | ⬜ Pass |
| **TC_SP6_19** | Chặn điểm danh bổ sung khi sự kiện kết thúc | Sự kiện có `EndTime` trong quá khứ hoặc trạng thái `Cancelled` | 1. Mở trang điểm danh sự kiện đã kết thúc/hủy. | Sự kiện quá giờ | Nút "Điểm danh bổ sung" bị ẩn. Nếu cố tình gửi request POST sẽ bị chặn: "Sự kiện đã kết thúc, không thể điểm danh." | ⬜ Pass |
| **TC_SP6_20** | Phân quyền truy cập chức năng điểm danh | Đăng nhập tài khoản `Event Participant` hoặc `Member` thông thường | 1. Cố tình truy cập URL `app/modules/attendance/checkin.php`. | Tài khoản không có quyền ủy quyền | Bị chặn truy cập với trang lỗi HTTP 403 Forbidden chuẩn giao diện. | ⬜ Pass |
