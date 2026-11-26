# Tài liệu Kiểm thử & Đảm bảo Chất lượng (QA/QC) — EventFlow

Tài liệu tổng hợp chiến lược kiểm thử, kịch bản kiểm thử (Test Cases, UAT, E2E), nhật ký lỗi (Defect Log) và kết quả nghiệm thu sản phẩm **EventFlow – Website quản lý hoạt động và thành viên CLB Tiếng Anh sinh viên (CLB BELL UEH)** qua 2 vòng kiểm thử từ **MVP v1** đến **MVP v2**.

---

## 1. Liên kết tài liệu & Minh chứng Kiểm thử (Phụ lục D)

* **Tài liệu Kế hoạch & Bộ Test Cases chi tiết trên Repo:** `./test_cases.md`
* **Functional & Non-functional Testing (44 Test Cases):** [\[NHÓM 8\] Functional & NFunctional Testing](https://docs.google.com/spreadsheets/d/1qeamFfkDQTMZuA1eIDAPwfEAU96yMFuJmd5xQidSmj4/edit?usp=sharing)
* **Test UAT & Nhật ký lỗi / Retest (7 kịch bản UAT + Log Feedback UAT & chỉnh sửa):** [\[NHÓM 8\] QUẢN LÝ KIỂM THỬ UAT](https://docs.google.com/spreadsheets/d/1izoRf0T1hPnP9WbBL_gzJtrs9K0TB8AAKOuzXmGsoQM/edit?usp=sharing)
* **Kế hoạch UAT & Kết quả phản hồi (UAT Feedback):** [KẾ HOẠCH KIỂM THỬ UAT & KẾT QUẢ PHẢN HỒI](https://docs.google.com/document/d/1rtdk-9C4jqHPagysSEo0g5IktKbO7nNkn7RgnvBXXc4/edit?usp=sharing)
* **End-to-End Testing (8 luồng nghiệp vụ xuyên suốt):** [E2E_Test_Cases_EventFlow.xlsx](https://docs.google.com/spreadsheets/d/1MbParN5cqtYHQDimqMc9BXX75F3SPnJz/edit?usp=sharing&ouid=113634494751880940044&rtpof=true&sd=true)
* **Biên bản họp các Sprint (Phụ lục E - Tham khảo):** [SPRINT MEETING RECAP - NHÓM 8](https://docs.google.com/document/d/1c2WakaX7NiGIqEkhQntFVZQymYSb74nUEQ6K1-Au5Mg/edit?usp=sharing)
* **Môi trường Live Demo (Google Cloud Compute Engine):** http://eventflow-ueh.duckdns.org/
* **Kho mã nguồn chính (GitHub Repository):** https://github.com/Phungcamtann/EventFlow.git

---

## 2. Chiến lược & Phạm vi Kiểm thử (Test Strategy)

### 2.1. Phạm vi kiểm thử (Test Scope)
* **Tài khoản & Phân quyền:** Đăng ký tài khoản sinh viên UEH, đăng nhập linh hoạt (Email/MSSV), phân quyền RBAC 3 tầng (`System Admin`, `Club Manager`, `Club Member` / `Event Supporter`, `Event Participant`) và quản trị trạng thái tài khoản (`IsActive`).
* **CLB & Thành viên:** Cập nhật hồ sơ CLB, tìm kiếm tức thời không dấu (`Zero-delay + AbortController`), quản lý chức vụ theo 4 phòng ban (`HR`, `SPEC`, `COMS`, `ER`) và quy trình tuyển quân.
* **Sự kiện & Đăng ký:** Tạo/sửa/hủy sự kiện, vận hành State Machine 7 trạng thái tự động, đăng ký/hủy đăng ký chống trùng và chống vượt sức chứa (`MaxCapacity`).
* **Điểm danh & Thống kê:** Check-in thời gian thực (Realtime Polling 3 giây), Hoàn tác (Undo), điểm danh khách vãng lai (`Walk-in`), phân công trực BTC, xuất báo cáo `.csv` (UTF-8 BOM) và thống kê KPI.

### 2.2. Cấp độ & Kỹ thuật kiểm thử
1. **Functional & Integration Testing:** Kiểm thử 44 ca kiểm thử chức năng/phi chức năng trên 9 nhóm nghiệp vụ và tính nhất quán dữ liệu giữa Frontend ↔ Backend (PHP 8.2) ↔ MariaDB/MySQL (11 bảng chuẩn 3NF).
2. **End-to-End (E2E) Testing:** Kiểm thử 8 kịch bản xuyên suốt 4 phân hệ lớn (`TC_E2E_01` – `TC_E2E_08`) từ vòng đời đăng ký tài khoản mới, nâng quyền, tạo sự kiện, check-in đến đối chiếu thống kê.
3. **Concurrency & Performance Testing:** Kiểm thử tranh chấp đồng thời suất cuối bằng khóa dòng `SELECT ... FOR UPDATE` trong Transaction ACID; đo tốc độ tải trang và độ trễ check-in qua Chrome DevTools.
4. **Security Testing:** Kiểm tra chống SQL Injection (PDO Prepared Statements), bảo vệ CSRF Token, mã hóa mật khẩu Bcrypt, khóa chỉ đọc trường `StudentCode` và chặn truy cập trái phép qua URL.
5. **Scenario-based UAT & Regression Testing:** Kiểm thử chấp nhận người dùng thực tế qua 7 kịch bản E2E (42 bước), ghi nhận Defect Log và thực hiện Retest toàn diện trên bản **MVP v2**.

---

## 3. Phân bổ Bộ Kịch bản Kiểm thử

### 3.1. Bộ 44 Test Cases Chức năng & Phi chức năng
| STT | Phân hệ / Nhóm nghiệp vụ | Số lượng TC | Mã tham chiếu |
| :--- | :--- | :---: | :--- |
| 1 | Danh sách sự kiện công khai & Lịch tương tác | 3 | `TC_SP5_01` – `TC_SP5_03` |
| 2 | Chi tiết sự kiện, Đăng ký & Hủy đăng ký | 9 | `TC_SP5_04` – `TC_SP5_12` |
| 3 | Biểu mẫu tạo & cập nhật sự kiện | 7 | `TC_SP5_13` – `TC_SP5_19` |
| 4 | Quản lý & Hủy sự kiện | 3 | `TC_SP5_20` – `TC_SP5_22` |
| 5 | Hoạt động của tôi (Sự kiện & Ca trực BTC) | 2 | `TC_SP5_23` – `TC_SP5_24` |
| 6 | Danh sách người đăng ký sự kiện | 6 | `TC_SP5_25` – `TC_SP5_30` |
| 7 | Điểm danh thời gian thực & Xử lý ngoại lệ | 5 | `TC_SP5_31` – `TC_SP5_35` |
| 8 | Thống kê & Báo cáo KPI | 6 | `TC_SP5_36` – `TC_SP5_41` |
| 9 | Bảo mật, Toàn vẹn & Kiểm thử đồng thời | 3 | `TC_SP5_42` – `TC_SP5_44` |
| **Tổng** | **9 nhóm nghiệp vụ** | **44 TC** | **Đạt 100% Pass** |

### 3.2. Bộ 7 Kịch bản UAT End-to-End (42 Bước)
* **UAT-01:** Tổ chức sự kiện và tiếp nhận đăng ký (8 bước)
* **UAT-02:** Điểm danh và theo dõi kết quả sự kiện (5 bước)
* **UAT-03:** Hủy đăng ký và cập nhật trạng thái tham gia (5 bước)
* **UAT-04:** Cập nhật thông tin và quản lý sự kiện (6 bước)
* **UAT-05:** Quản lý thành viên và phân quyền (8 bước)
* **UAT-06:** Xử lý các trường hợp đăng ký không hợp lệ (3 bước)
* **UAT-07:** Đăng ký tài khoản người dùng mới (7 bước)

---

## 4. Tổng hợp Kết quả Thực thi (MVP v1 → MVP v2)

Đợt kiểm thử chấp nhận người dùng (UAT) với 5 người dùng đại diện và nhóm Project Control Group (PCG) ghi nhận **20 vấn đề** (`EF-DEF-01` đến `EF-DEF-20`), gồm **4 Bug**, **11 Improvement** và **5 Clarification** (0 Critical, 5 High, 11 Medium, 4 Low). Toàn bộ 20/20 mục đã được khắc phục trên phiên bản **MVP v2** và kiểm thử lại (Retest) vào ngày **23/11/2026**.

| Hạng mục kiểm thử | Chỉ tiêu nghiệm thu (Exit Criteria) | Kết quả Vòng 1 (MVP v1) | Kết quả Vòng 2 (MVP v2) | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **Functional & Non-Functional** | ≥ 30 TC (Tỷ lệ Pass ≥ 90%) | 44/44 TC đạt | **44/44 TC đạt (100%)** | Đạt |
| **End-to-End (E2E) Testing** | 8 luồng nghiệp vụ xuyên suốt | 8/8 TC đạt | **8/8 TC đạt (100%)** | Đạt |
| **Defect / Feedback từ UAT** | 0 lỗi Critical/High còn mở | Ghi nhận 20 mục (5 High) | **Retest 20/20 Pass (0 lỗi mở)** | Đạt |
| **Hiệu năng tải trang / Check-in** | Tải trang ≤ 2s / Check-in ≤ 1s | Chưa đồng bộ realtime | **Tải ≤ 1.2s; Check-in ≤ 0.5s (Polling 3s)** | Đạt |
| **Kiểm soát vượt sức chứa** | 0% sai lệch khi đăng ký đồng thời | Đạt (`SELECT ... FOR UPDATE`) | **Đạt 100% (0% sai lệch)** | Đạt |

---

## 5. Tài khoản Kiểm thử trên Môi trường Demo

* **URL truy cập:** http://eventflow-ueh.duckdns.org/app/modules/auth/login.php
* **Mật khẩu chung:** `Password@123`

| Mã TK | Vai trò hệ thống | Email đăng nhập | Phạm vi kiểm thử chính |
| :---: | :--- | :--- | :--- |
| `ACC-01` | **Club Manager** | `manager@st.ueh.edu.vn` | Quản lý CLB, thành viên, sự kiện, danh sách, check-in, thống kê |
| `ACC-02` | **Event Supporter** | `supporter@st.ueh.edu.vn` | Kiểm tra quyền xem danh sách/điểm danh theo phạm vi được giao |
| `ACC-03` | **Club Member** | `minhan@st.ueh.edu.vn` | Hồ sơ thành viên, đăng ký/hủy, lịch sử cá nhân |
| `ACC-04` | **Event Participant** | `student@st.ueh.edu.vn` | Người tham gia sự kiện, đăng ký/hủy và xem trạng thái cá nhân |
| `ACC-05` | **System Administrator** | `admin@ueh.edu.vn` | Quản trị tài khoản và quyền hệ thống |
