# 🧪 Pha 5: Bộ Kịch Bản Kiểm Thử (Test Cases) — Quản lý Sự kiện, Đăng ký, Điểm danh & Thống kê

> **Dự án:** EventFlow — Quản lý Hoạt động và Thành viên CLB Tiếng Anh Sinh Viên  
> **Phân hệ:** Quản lý Sự kiện, Đăng ký, Điểm danh & Thống kê (`SCR08` – `SCR15`)  
> **Người thực hiện:** QA / Tester (Tiêu Lâm Định Quốc)  
> **Ngày lập:** 11/11/2026  
> **Trạng thái:** 🟢 Hoàn thành 100% — Sẵn sàng nghiệm thu UAT  

---

## 1. Danh Sách Tài Khoản Mẫu Phục Vụ Kiểm Thử (Seed Accounts)

| Vai trò kiểm thử | Email đăng nhập | Mật khẩu mặc định | Ghi chú quyền hạn |
|:---|:---|:---|:---|
| **Club Manager** | `manager@eventflow.local` | `Password@123` | Quản trị CLB, tạo/sửa/hủy sự kiện, xem danh sách đăng ký, điểm danh và xem báo cáo thống kê |
| **Event Supporter** | `supporter@eventflow.local` | `Password@123` | Thành viên ủy quyền (Active), được quyền xem danh sách đăng ký và điểm danh |
| **Club Member** | `member@eventflow.local` | `Password@123` | Thành viên CLB thông thường, đăng ký/hủy tham gia sự kiện, xem hoạt động cá nhân |
| **Event Participant**| `student@eventflow.local` | `Password@123` | Sinh viên đăng ký tham gia sự kiện, xem hoạt động cá nhân |
| **System Admin** | `admin@eventflow.local` | `Password@123` | Quản trị tài khoản hệ thống (SCR16) |

---

## 2. Bảng Kịch Bản Kiểm Thử Chi Tiết (Test Cases)

### Nhóm 1: Danh Sách Sự Kiện Công Khai (`SCR08` — `index.php` / FR10, PB13)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_01** | Xem danh sách sự kiện công khai | Đã đăng nhập tài khoản bất kỳ | 1. Truy cập `index.php`.<br>2. Quan sát danh sách sự kiện hiển thị. | Không | Hiển thị các sự kiện có trạng thái `Published` và `Cancelled`. Thấy rõ tên, thời gian, địa điểm, CLB tổ chức, số lượng đã đăng ký. | ⬜ Pass |
| **TC_SP5_02** | Hiển thị trạng thái động và cảnh báo | CSDL có sự kiện gần hết hạn hoặc sắp hết chỗ | 1. Tạo sự kiện còn $\le 5$ chỗ trống.<br>2. Tạo sự kiện hạn chót $\le 48\text{h}$.<br>3. Xem trang `index.php`. | Dữ liệu mẫu sự kiện | Sự kiện hiển thị badge cảnh báo "Chỉ còn X chỗ" hoặc "Sắp hết hạn đăng ký". Trạng thái hiển thị đúng "Đang mở đăng ký" hoặc "Đã đóng đăng ký". | ⬜ Pass |
| **TC_SP5_03** | Chặn khách chưa đăng nhập | Chưa đăng nhập hệ thống | 1. Xóa cookie session hoặc mở ẩn danh.<br>2. Truy cập trực tiếp `index.php`. | URL `index.php` | Hệ thống chuyển hướng (redirect) về trang đăng nhập `app/modules/auth/login.php`. | ⬜ Pass |

---

### Nhóm 2: Chi Tiết Sự Kiện & Đăng Ký / Hủy Đăng Ký (`SCR09` — `detail.php` / FR10, FR12, PB14, PB15)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_04** | Xem chi tiết sự kiện thành công | Đã đăng nhập | 1. Tại `index.php`, bấm nút "Xem chi tiết" của 1 sự kiện. | `id={EventID}` hợp lệ | Hiển thị đầy đủ thông tin chi tiết: Tên, Mô tả, Địa điểm, Thời gian bắt đầu/kết thúc, Hạn đăng ký, Thanh tiến độ chỗ trống. | ⬜ Pass |
| **TC_SP5_05** | Đăng ký sự kiện thành công | Đăng nhập tài khoản `student@...` hoặc `member@...` chưa đăng ký sự kiện | 1. Vào chi tiết sự kiện đang mở đăng ký.<br>2. Nhấn nút "Đăng ký tham gia". | Action: `register` | Hệ thống ghi nhận đăng ký thành công, thông báo flash xanh. Nút chuyển sang "Hủy đăng ký", số chỗ đã đăng ký tăng lên 1. | ⬜ Pass |
| **TC_SP5_06** | Chặn đăng ký trùng lặp | Tài khoản đã đăng ký sự kiện này trước đó | 1. Mở trang chi tiết sự kiện đã đăng ký.<br>2. Cố gắng gửi request POST `register`. | Action: `register` | Bị từ chối với thông báo "Bạn đã đăng ký sự kiện này rồi." | ⬜ Pass |
| **TC_SP5_07** | Chặn đăng ký khi sự kiện hết chỗ | Sự kiện đã đủ `MaxCapacity` | 1. Truy cập sự kiện có `RegisteredCount == MaxCapacity`. | Action: `register` | Nút đăng ký bị vô hiệu hóa, trạng thái ghi "Đã đóng đăng ký". Gửi POST bị báo lỗi "Sự kiện đã hết chỗ." | ⬜ Pass |
| **TC_SP5_08** | Chặn đăng ký khi quá hạn chót | Thời gian hiện tại $> RegistrationDeadline$ | 1. Truy cập sự kiện có hạn chót trong quá khứ. | Action: `register` | Nút đăng ký bị vô hiệu hóa, hiển thị "Đã đóng đăng ký". Gửi POST bị báo lỗi "Đã quá hạn đăng ký sự kiện." | ⬜ Pass |
| **TC_SP5_09** | Hủy đăng ký thành công | Tài khoản đã đăng ký, sự kiện chưa diễn ra | 1. Vào chi tiết sự kiện đã đăng ký.<br>2. Nhấn nút "Hủy đăng ký". | Action: `cancel_registration` | Hủy thành công, thông báo flash. Bản ghi trong `Event_Registration` chuyển `Status = 'Cancelled'` (không bị xóa). Nút đổi lại "Đăng ký tham gia". | ⬜ Pass |
| **TC_SP5_10** | Chặn hủy đăng ký khi đã check-in | Đã được điểm danh trong bảng `Attendance` | 1. Mở trang chi tiết sự kiện đã được check-in. | Action: `cancel_registration` | Nút hủy không khả dụng. Gửi POST bị chặn: "Bạn đã được điểm danh, không thể hủy đăng ký." | ⬜ Pass |
| **TC_SP5_11** | Tái kích hoạt đăng ký lại | Tài khoản đã từng hủy đăng ký trước đó | 1. Vào sự kiện vừa hủy.<br>2. Bấm "Đăng ký tham gia" lại. | Action: `register` | Đăng ký lại thành công! Dòng cũ trong `Event_Registration` cập nhật lại `Status = 'Registered'`, không bị lỗi duplicate key. | ⬜ Pass |
| **TC_SP5_12** | Phân quyền vai trò đăng ký | Đăng nhập tài khoản `Club Manager` hoặc `Admin` | 1. Vào trang chi tiết sự kiện `detail.php`. | Vai trò không phải Participant/Member | Không hiển thị nút Đăng ký, chỉ hiển thị thông tin sự kiện. | ⬜ Pass |

---

### Nhóm 3: Biểu Mẫu Tạo & Chỉnh Sửa Sự Kiện (`SCR10` — `form.php` / FR09, PB11, PB12, BR04)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_13** | Tạo sự kiện mới hợp lệ | Đăng nhập `Club Manager` | 1. Vào `app/modules/events/form.php`.<br>2. Nhập đầy đủ thông tin hợp lệ.<br>3. Chọn trạng thái `Published`.<br>4. Nhấn "Lưu sự kiện". | Tên sự kiện, Địa điểm, Thời gian hợp lệ, Sức chứa: 50 | Lưu thành công vào bảng `Event`, hiển thị thông báo flash xanh tại màn hình `manage.php`. | ⬜ Pass |
| **TC_SP5_14** | Bỏ trống trường bắt buộc | Đăng nhập `Club Manager` | 1. Vào form tạo mới.<br>2. Bỏ trống Tên hoặc Địa điểm.<br>3. Nhấn Lưu. | Tên rỗng | Báo lỗi validation đỏ: "Vui lòng nhập tên sự kiện.", "Vui lòng nhập địa điểm." | ⬜ Pass |
| **TC_SP5_15** | Ràng buộc thời gian kết thúc (BR04) | Đăng nhập `Club Manager` | 1. Nhập `EndTime` nhỏ hơn hoặc bằng `StartTime`.<br>2. Nhấn Lưu. | End: `10:00`, Start: `11:00` | Báo lỗi: "Thời gian kết thúc phải sau thời gian bắt đầu." | ⬜ Pass |
| **TC_SP5_16** | Ràng buộc hạn chót đăng ký (BR04) | Đăng nhập `Club Manager` | 1. Nhập `RegistrationDeadline` sau `StartTime`.<br>2. Nhấn Lưu. | Deadline: `12:00`, Start: `10:00` | Báo lỗi: "Hạn đăng ký phải nhỏ hơn hoặc bằng thời gian bắt đầu sự kiện." | ⬜ Pass |
| **TC_SP5_17** | Chỉnh sửa sự kiện thành công | Đăng nhập `Club Manager`, sự kiện chưa hủy | 1. Tại `manage.php`, bấm nút Chỉnh sửa.<br>2. Sửa địa điểm hoặc mô tả.<br>3. Nhấn Lưu cập nhật. | Địa điểm mới: "Hội trường A" | Cập nhật thành công vào CSDL, chuyển hướng về `manage.php`. | ⬜ Pass |
| **TC_SP5_18** | Chặn giảm sức chứa dưới số đã đăng ký | Sự kiện đã có 10 người đăng ký | 1. Chỉnh sửa sự kiện.<br>2. Nhập `MaxCapacity = 5`.<br>3. Nhấn Lưu. | `MaxCapacity = 5` (< 10) | Báo lỗi: "Số chỗ tối đa không được nhỏ hơn số người đã đăng ký hiện tại (10)." | ⬜ Pass |
| **TC_SP5_19** | Chặn sửa sự kiện đã hủy | Sự kiện có `Status = 'Cancelled'` | 1. Cố ý truy cập `form.php?id={CancelledEventID}`. | ID sự kiện đã hủy | Chuyển hướng về `manage.php` kèm cảnh báo "Sự kiện đã bị hủy nên không thể chỉnh sửa." | ⬜ Pass |

---

### Nhóm 4: Quản Lý Sự Kiện Nội Bộ & Hủy Sự Kiện (`SCR11` — `manage.php` / FR09, PB11, PB12, BR05)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_20** | Xem danh sách sự kiện nội bộ | Đăng nhập `Club Manager` | 1. Chọn menu "Quản lý sự kiện nội bộ". | URL `events/manage.php` | Hiển thị toàn bộ sự kiện của CLB (Draft, Published, Cancelled) kèm số lượng đăng ký thực tế. | ⬜ Pass |
| **TC_SP5_21** | Hủy sự kiện bảo toàn dữ liệu (BR05) | Đăng nhập `Club Manager`, sự kiện chưa kết thúc | 1. Bấm nút "Hủy sự kiện".<br>2. Xác nhận hộp thoại hủy. | Action: `cancel_event` | `Event.Status` chuyển thành `'Cancelled'`. Bản ghi trong `Event_Registration` và `Attendance` được giữ nguyên vẹn 100%. | ⬜ Pass |
| **TC_SP5_22** | Chặn hủy sự kiện đã kết thúc | Sự kiện có `EndTime <= NOW()` | 1. Quan sát dòng sự kiện đã kết thúc.<br>2. Cố gửi request hủy. | Sự kiện quá khứ | Nút hủy bị ẩn. Gửi request trực tiếp bị chặn: "Sự kiện đã kết thúc, không thể hủy." | ⬜ Pass |

---

### Nhóm 5: Hoạt Động Của Tôi (`SCR12` — `my_events.php` / FR12, FR13, PB16)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_23** | Xem lịch sử sự kiện đã đăng ký | Đã đăng nhập tài khoản có đăng ký sự kiện | 1. Bấm menu "Sự kiện của tôi" trên thanh Topbar. | URL `events/my_events.php` | Hiển thị danh sách các sự kiện người dùng từng đăng ký, ngày đăng ký, thời gian và địa điểm. | ⬜ Pass |
| **TC_SP5_24** | Hiển thị chính xác nhãn trạng thái | Các sự kiện với các kịch bản khác nhau | 1. Quan sát cột Trạng thái tại bảng `my_events.php`. | Dữ liệu đa dạng | Hiển thị chính xác các nhãn:<br>- "Đã đăng ký" (màu xanh dương)<br>- "Đã tham dự" (màu xanh lá)<br>- "Vắng mặt" (màu đỏ)<br>- "Đã hủy đăng ký" (màu xám)<br>- "Sự kiện đã hủy" (màu đỏ) | ⬜ Pass |

---

### Nhóm 6: Danh Sách Đăng Ký Theo Sự Kiện (`SCR13` — `registrations.php` / FR14, PB17, UC11)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_25** | Xem danh sách chọn sự kiện | Đăng nhập `Club Manager` hoặc `Event Supporter` | 1. Truy cập `attendance/registrations.php`. | URL không có `event_id` | Hiển thị danh sách các sự kiện của CLB, số đăng ký, số đã hủy, sức chứa và nút "Xem danh sách". | ⬜ Pass |
| **TC_SP5_26** | Xem chi tiết danh sách người đăng ký | Đã chọn 1 sự kiện cụ thể | 1. Bấm "Xem danh sách" của sự kiện. | URL `registrations.php?event_id=X` | Hiển thị đầy đủ thông tin: Họ tên, MSSV, Email, Ngày đăng ký, Trạng thái đăng ký và Trạng thái điểm danh. | ⬜ Pass |
| **TC_SP5_27** | Hiển thị 4 thẻ KPI tóm tắt số chỗ | Vào trang đăng ký của sự kiện | 1. Quan sát 4 thẻ KPI đầu trang. | Dữ liệu sự kiện | Hiển thị chính xác: Đăng ký hợp lệ, Sức chứa tối đa, Số chỗ còn trống, và Số người đã điểm danh. | ⬜ Pass |
| **TC_SP5_28** | Lọc theo trạng thái đăng ký | Danh sách có cả người đăng ký và người đã hủy | 1. Bấm radio "Hợp lệ".<br>2. Bấm radio "Đã hủy".<br>3. Bấm radio "Tất cả". | Bộ lọc trạng thái | Bảng lọc đúng đối tượng: Chỉ hiện người đang giữ chỗ, chỉ hiện người đã hủy, hoặc hiện tất cả. | ⬜ Pass |
| **TC_SP5_29** | Tìm kiếm tức thời không dấu client JS | Danh sách có nhiều người tham gia | 1. Gõ tên không dấu (vd: `nguyen`).<br>2. Gõ MSSV hoặc Email. | Từ khóa tìm kiếm | Bảng tự động ẩn các dòng không khớp ngay tức thì không tải lại trang ($\le 1\text{s}$ đạt NFR03). | ⬜ Pass |
| **TC_SP5_30** | Nút chuyển nhanh sang Điểm danh | Đang ở trang danh sách đăng ký | 1. Nhấn nút "Chuyển sang Điểm danh". | URL button | Chuyển hướng chính xác sang `checkin.php?event_id=X` để tiến hành check-in. | ⬜ Pass |

---

### Nhóm 7: Điểm Danh Sự Kiện & Xử Lý Ngoại Lệ (`SCR14` — `checkin.php` / FR15, PB18, PB19, UC12)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_31** | Phân quyền điểm danh 2 tầng | Đăng nhập lần lượt các tài khoản | 1. Truy cập `attendance/checkin.php`. | Các vai trò khác nhau | - `Club Manager` & `Event Supporter`: Vào thành công.<br>- `Club Member` & `Participant`: Bị chặn thông báo 403 Forbidden. | ⬜ Pass |
| **TC_SP5_32** | Lọc tìm kiếm tức thời không dấu | Vào trang điểm danh của 1 sự kiện | 1. Nhập họ tên không dấu hoặc có dấu vào ô tìm kiếm.<br>2. Nhập thử MSSV. | Ô tìm kiếm: `nguyen` hoặc `3124...` | Bảng tự động lọc tức thời $\le 1\text{s}$ (đạt NFR03). | ⬜ Pass |
| **TC_SP5_33** | Điểm danh thành công (Check-in) | Người tham gia chưa điểm danh, sự kiện chưa kết thúc | 1. Bấm nút "Điểm danh" tại dòng người tham gia. | Action: `checkin`, `reg_id` | Ghi nhận vào bảng `Attendance` (`AttendanceStatus = 1`), dòng được highlight màu xanh, số lượng đã điểm danh tăng lên. | ⬜ Pass |
| **TC_SP5_34** | Bỏ điểm danh thành công (Undo) | Người tham gia đã được điểm danh trước đó | 1. Bấm nút "Bỏ điểm danh" trên dòng đã điểm danh. | Action: `undo`, `reg_id` | Xóa bản ghi khỏi bảng `Attendance`, trạng thái đổi lại "Chưa điểm danh", số người đã điểm danh giảm 1. | ⬜ Pass |
| **TC_SP5_35** | Chặn điểm danh khi sự kiện đã kết thúc | Sự kiện có `EndTime <= NOW()` | 1. Vào trang điểm danh sự kiện đã qua. | Sự kiện quá khứ | Hiển thị cảnh báo màu vàng "Sự kiện không còn mở điểm danh...". Các nút điểm danh bị ẩn. | ⬜ Pass |

---

### Nhóm 8: Báo Cáo Thống Kê Hoạt Động Cơ Bản (`SCR15` — `stats.php` / FR16, PB20, UC13, BR06)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_36** | Phân quyền truy cập Báo cáo Thống kê | Đăng nhập các tài khoản khác nhau | 1. Truy cập `attendance/stats.php`. | Các vai trò hệ thống | - `Club Manager`: Truy cập thành công.<br>- Các vai trò khác (`Supporter`, `Member`, `Student`, `Admin`): Bị chặn 403 Forbidden. | ⬜ Pass |
| **TC_SP5_37** | Hiển thị 4 thẻ KPI Dashboard toàn CLB | Đăng nhập `Club Manager` | 1. Mở trang Báo cáo thống kê. | URL `attendance/stats.php` | Hiển thị chính xác: Tổng thành viên Active, Tổng số sự kiện, Tổng đăng ký / có mặt, và Tỷ lệ tham dự trung bình toàn CLB (%). | ⬜ Pass |
| **TC_SP5_38** | Kiểm tra công thức thống kê BR06 | CSDL có sự kiện với số đăng ký và điểm danh thực tế | 1. Đối chiếu số liệu từng dòng trong bảng chi tiết sự kiện. | Dữ liệu CSDL | Tính toán chính xác 100% theo BR06:<br>- `Số đăng ký` = `COUNT(RegID WHERE Status='Registered')`<br>- `Số có mặt` = `COUNT(AttendanceID WHERE AttendanceStatus=1)`<br>- `Số vắng mặt` = `Số đăng ký - Số có mặt`<br>- `Tỷ lệ %` = `(Có mặt / Đăng ký) * 100%`. | ⬜ Pass |
| **TC_SP5_39** | Màu sắc thanh tiến độ Progress Bar | Các sự kiện với tỷ lệ tham dự khác nhau | 1. Quan sát thanh tiến độ của từng sự kiện. | Tỷ lệ tham dự đa dạng | - Tỷ lệ $\ge 80\%$: Thanh màu xanh lá (`bg-success`).<br>- Tỷ lệ $50\% - 79\%$: Thanh màu vàng (`bg-warning`).<br>- Tỷ lệ $< 50\%$: Thanh màu đỏ (`bg-danger`). | ⬜ Pass |
| **TC_SP5_40** | Lọc sự kiện theo trạng thái trong thống kê | Có sự kiện Published, Draft, Cancelled | 1. Chọn lọc "Đang công bố".<br>2. Chọn lọc "Bản nháp".<br>3. Chọn lọc "Đã hủy". | Dropdown trạng thái | Bảng chỉ hiển thị đúng các sự kiện có trạng thái được chọn. | ⬜ Pass |
| **TC_SP5_41** | Tìm kiếm sự kiện theo tên trong thống kê | Nhập từ khóa tên sự kiện | 1. Nhập từ khóa vào ô tìm kiếm.<br>2. Nhấn nút "Lọc". | Tên sự kiện | Bảng hiển thị chính xác các sự kiện có tên chứa từ khóa tìm kiếm. | ⬜ Pass |

---

### Nhóm 9: Bảo Mật & Toàn Vẹn Hệ Thống (Security & Integrity)

| Mã TC | Tên ca kiểm thử | Tiền điều kiện | Các bước thực hiện | Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
|:---|:---|:---|:---|:---|:---|:---:|
| **TC_SP5_42** | Bảo vệ chống CSRF | Bất kỳ biểu mẫu POST nào (Form, Detail, Checkin, Cancel) | 1. Dùng DevTools sửa hoặc xóa trường `_csrf_token`.<br>2. Submit form. | Token rỗng hoặc không khớp | Thao tác bị từ chối, báo lỗi: "Mã xác thực không hợp lệ hoặc phiên làm việc đã hết hạn." | ⬜ Pass |
| **TC_SP5_43** | Chống XSS qua dữ liệu nhập | Form tạo/sửa sự kiện | 1. Nhập tên sự kiện chứa thẻ `<script>alert('xss')</script>`.<br>2. Lưu và xem tại trang chủ, chi tiết, quản lý và thống kê. | Mã HTML/script | Trình duyệt hiển thị nguyên văn chuỗi văn bản, script không bị thực thi (được mã hóa qua `htmlspecialchars()`). | ⬜ Pass |
| **TC_SP5_44** | Chống Race Condition khi đăng ký đồng thời (NFR01) | Sự kiện chỉ còn đúng 1 chỗ trống duy nhất | 1. 2 tài khoản cùng bấm "Đăng ký" tại cùng 1 giây. | 2 request đồng thời | Cơ chế `SELECT ... FOR UPDATE` đảm bảo chỉ 1 người đăng ký thành công, người còn lại nhận thông báo "Sự kiện đã hết chỗ." Không bao giờ vượt `MaxCapacity`. | ⬜ Pass |
