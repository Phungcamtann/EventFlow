# EventFlow – Website Quản Lý Hoạt Động và Thành Viên Câu Lạc Bộ Tiếng Anh Sinh Viên

> **Môn học:** Quản lý Dự án Công nghệ Thông tin – Khoa Công nghệ Thông tin Kinh doanh, Trường Công nghệ và Thiết kế, Đại học Kinh tế TP. Hồ Chí Minh (UEH)  
> **Nhóm thực hiện:** Nhóm 8  
> **Project Sponsor:** TS. Nguyễn Mạnh Tuấn  
> **Phạm vi sản phẩm:** Minimum Viable Product (MVP) phục vụ nội bộ **01 Câu lạc bộ Tiếng Anh sinh viên**

---

## Mục Lục
1. [Tổng Quan Dự Án & Bối Cảnh](#1-tổng-quan-dự-án--bối-cảnh)
2. [Mục Tiêu Dự Án (Project Objectives)](#2-mục-tiêu-dự-án-project-objectives)
3. [Nhóm Người Dùng & Phân Quyền (Target Users & RBAC)](#3-nhóm-người-dùng--phân-quyền-target-users--rbac)
4. [Phạm Vi Sản Phẩm (MoSCoW & MVP Scope)](#4-phạm-vi-sản-phẩm-moscow--mvp-scope)
5. [Kiến Trúc Hệ Thống & Công Nghệ Sử Dụng (Tech Stack)](#5-kiến-trúc-hệ-thống--công-nghệ-sử-dụng-tech-stack)
6. [Thiết Kế Cơ Sở Dữ Liệu (Database Schema)](#6-thiết-kế-cơ-sở-dữ-liệu-database-schema)
7. [Danh Sách Màn Hình & Truy Vết Yêu Cầu](#7-danh-sách-màn-hình--truy-vết-yêu-cầu)
8. [Hướng Dẫn Cài Đặt & Chạy Dự Án (Localhost XAMPP)](#8-hướng-dẫn-cài-đặt--chạy-dự-án-localhost-xampp)
9. [Tiêu Chí Nghiệm Thu & Kiểm Thử (QA & UAT)](#9-tiêu-chí-nghiệm-thu--kiểm-thử-qa--uat)
10. [Đội Ngũ Phát Triển (Team Charter)](#10-đội-ngũ-phát-triển-team-charter)
11. [Tài Liệu & Liên Kết Dự Án](#11-tài-liệu--liên-kết-dự-án)

---

## 1. Tổng Quan Dự Án & Bối Cảnh

### Bài toán thực tế (Problem Statement)
Qua kết quả khảo sát định tính ban đầu (5 cuộc phỏng vấn sâu `P01–P05` với Ban chủ nhiệm, Thành viên CLB và Sinh viên tham gia sự kiện), quy trình vận hành hoạt động tại CLB đang bị phân mảnh trên nhiều công cụ rời rạc (`Google Forms`, `Google Sheets`, `Facebook Fanpage`, `Messenger`, `Danh sách giấy`), dẫn đến **5 Pain Points chính**:
1. **Thông tin đăng ký bị phân tán và cập nhật thủ công (`P01`):** Người tham gia thay đổi thông tin (như MSSV) qua Fanpage nhưng bảng tính check-in chưa kịp đồng bộ, gây tắc nghẽn khi đối chiếu.
2. **Thành viên dễ bỏ lỡ thông báo thay đổi sự kiện (`P02`):** Thay đổi địa điểm/thời gian trao đổi qua nhóm chat dễ bị trôi tin nhắn, dẫn đến sai sót khi truyền thông.
3. **Khó tra cứu lịch sử tham gia của thành viên (`P03`):** Ban chủ nhiệm phải mở đối chiếu 4–5 file danh sách rời rạc để tổng hợp lịch sử hoạt động của một thành viên trong học kỳ.
4. **Quá trình Check-in mất nhiều thời gian xác minh (`P04`):** Tra cứu thủ công trên Google Sheets hoặc giấy dễ xảy ra sai sót, điểm danh trùng hoặc khó xử lý nhanh người chưa đăng ký.
5. **Người tham gia không chắc chắn về trạng thái đăng ký (`P05`):** Sau khi điền Google Forms, sinh viên không rõ mình đã được ghi nhận giữ chỗ thành công hay chưa.

### Giải pháp đề xuất (Proposed Solution)
**EventFlow** là nền tảng website quản lý tập trung dành riêng cho **một Câu lạc bộ Tiếng Anh sinh viên**, giúp đồng bộ hóa xuyên suốt quy trình: **Quản lý thông tin CLB & Thành viên $\rightarrow$ Tạo & Công bố sự kiện $\rightarrow$ Đăng ký tham gia (kiểm soát hạn & số chỗ) $\rightarrow$ Điểm danh (Check-in) $\rightarrow$ Thống kê hoạt động cơ bản**.

---

## 2. Mục Tiêu Dự Án (Project Objectives)

| Mã | Mục tiêu dự án | Chỉ số đánh giá (KPI) & Mốc thời gian |
| :--- | :--- | :--- |
| **OBJ01** | Xác định và phân tích yêu cầu nghiệp vụ quản lý CLB sinh viên | Hoàn thiện phỏng vấn `P01–P05`, Requirement List (`FR01–FR20`, `NFR01–NFR07`), Use Case, BPMN và Product Backlog (`29 items`). **Mốc: M2 (22/10/2026)**. |
| **OBJ02** | Thiết kế và phát triển website quản lý CLB với các phân hệ MVP | Sign-off thiết kế Kiến trúc, ERD 7 bảng và Prototype (**M3: 25/10/2026**). Hoàn thành 100% chức năng `Must Have` (**M4: 05/11/2026**). |
| **OBJ03** | Đảm bảo chất lượng và khả năng sử dụng của hệ thống | Thực thi tối thiểu **30 Test Cases**, tỷ lệ Pass mục tiêu **$\ge 90\%$**, **0 lỗi Critical** tồn đọng; tổ chức UAT với tối thiểu **5 người dùng** (**M5: 12/11/2026**). |
| **OBJ04** | Hoàn thành triển khai demo và bàn giao hồ sơ dự án | Bàn giao mã nguồn, website demo, tài liệu phân tích - thiết kế - kiểm thử và hồ sơ đóng dự án (**M6: 26/11/2026**). |

---

## 3. Nhóm Người Dùng & Phân Quyền (Target Users & RBAC)

Hệ thống phân quyền truy cập theo **4 vai trò hệ thống (`Role`)** kết hợp với **vai trò nghiệp vụ nội bộ CLB (`Club_Member.MemberRole`)**:

| Mã Actor | Vai trò (`RoleName`) | Mô tả & Quyền hạn chính trong MVP |
| :--- | :--- | :--- |
| **A4** | `System Administrator` | Quản trị tài khoản hệ thống: tạo mới, khóa/mở khóa tài khoản (`IsActive`), cấp/đổi vai trò hệ thống (`FR17`). Không can thiệp nghiệp vụ nội bộ CLB. |
| **A3** | `Club Manager` | Ban chủ nhiệm CLB: cập nhật hồ sơ CLB (`FR05`), quản lý danh sách/vai trò/trạng thái thành viên (`FR07, FR08`), tạo/sửa/hủy sự kiện (`FR09`), xem danh sách đăng ký (`FR14`), thực hiện điểm danh (`FR15`) và xem thống kê cơ bản (`FR16`). |
| **A1** | `Club Member` | Thành viên CLB: kế thừa toàn bộ quyền của `Event Participant`; xem tư cách thành viên cá nhân (`FR04`). Nếu được cấp quyền ủy quyền hỗ trợ (`MemberRole` là `'Board Member'` hoặc `'Event Supporter'`), được phép xem danh sách đăng ký (`FR14`) và điểm danh (`FR15`). |
| **A2** | `Event Participant` | Sinh viên / Người tham gia: đăng ký tài khoản (`FR01`), đăng nhập (`FR02`), quản lý hồ sơ cá nhân (`FR04`), xem thông tin CLB (`FR06`) & sự kiện (`FR10`), đăng ký/hủy đăng ký sự kiện (`FR12`) và theo dõi trạng thái đăng ký/lịch sử tham gia của bản thân (`FR13`). |

---

## 4. Phạm Vi Sản Phẩm (MoSCoW & MVP Scope)

### Phân loại Yêu cầu Chức năng (Functional Requirements)

| Mức ưu tiên | Mã FR | Tên chức năng | Backlog ID | Thuộc MVP |
| :---: | :---: | :--- | :---: | :---: |
| **Must Have** | `FR01` | Đăng ký tài khoản | `PB01` | Có |
| **Must Have** | `FR02` | Đăng nhập, đăng xuất và kiểm soát truy cập theo vai trò (RBAC) | `PB02, PB03` | Có |
| **Must Have** | `FR04` | Hồ sơ cá nhân và xem tư cách thành viên | `PB05` | Có |
| **Must Have** | `FR05` | Cập nhật thông tin CLB | `PB07` | Có |
| **Must Have** | `FR06` | Xem thông tin công khai của CLB | `PB06` | Có |
| **Must Have** | `FR07` | Quản lý thông tin thành viên (xem, tìm kiếm, thêm, sửa) | `PB08, PB09` | Có |
| **Must Have** | `FR08` | Quản lý vai trò và trạng thái thành viên trong CLB | `PB10` | Có |
| **Must Have** | `FR09` | Quản lý sự kiện (tạo, sửa, hủy; bảo toàn dữ liệu đăng ký) | `PB11, PB12` | Có |
| **Must Have** | `FR10` | Xem danh sách và chi tiết sự kiện | `PB13, PB14` | Có |
| **Must Have** | `FR12` | Đăng ký tham gia sự kiện (kiểm tra hạn, số chỗ, chống trùng) | `PB15` | Có |
| **Must Have** | `FR13` | Xem trạng thái đăng ký và lịch sử tham gia cá nhân | `PB16` | Có |
| **Must Have** | `FR14` | Quản lý danh sách đăng ký theo sự kiện | `PB17` | Có |
| **Must Have** | `FR15` | Điểm danh (Check-in) và xử lý ngoại lệ (chưa đăng ký / trùng) | `PB18, PB19` | Có |
| **Must Have** | `FR16` | Xem thống kê hoạt động cơ bản theo sự kiện | `PB20` | Có |
| **Must Have** | `FR17` | Quản trị tài khoản và quyền truy cập hệ thống | `PB04` | Có |
| **Should Have** | `FR03` | Đặt lại mật khẩu | `PB21` | Giai đoạn sau |
| **Should Have** | `FR11` | Tìm kiếm và lọc sự kiện theo từ khóa / thời gian | `PB22` | Giai đoạn sau |
| **Should Have** | `FR18` | Thông báo nội bộ trong hệ thống | `PB23` | Giai đoạn sau |
| **Could Have** | `FR19` | Xuất danh sách đăng ký và điểm danh ra Excel | `PB24` | Giai đoạn sau |
| **Could Have** | `FR20` | Quản lý và phân công nhiệm vụ thành viên | `PB25` | Giai đoạn sau |

### Yêu cầu Phi chức năng Trọng tâm (Non-Functional Requirements)
* **`NFR01` (Toàn vẹn & Nhất quán dữ liệu):** Dữ liệu đăng ký, check-in và lịch sử tham gia đồng nhất 100% giữa các màn hình; không phát sinh bản ghi trùng lặp hoặc vượt quá sức chứa (`MaxCapacity`).
* **`NFR02` (Tính cập nhật):** Thay đổi sau khi lưu hiển thị trên màn hình liên quan trong vòng **$\le 3\text{ giây}$** (đạt $\ge 95\%$ lượt kiểm tra).
* **`NFR03` (Hiệu năng tra cứu & Check-in):** Tra cứu người đăng ký **$\le 2\text{ giây}$**, lưu kết quả check-in **$\le 2\text{ giây}$**, tải lịch sử tham gia **$\le 3\text{ giây}$**.
* **`NFR04` (Tính dễ sử dụng):** Người dùng tự hoàn thành thao tác cốt lõi trong **30–45 giây** không cần trợ giúp ($\ge 4/5$ người dùng UAT).
* **`NFR05` (Rõ ràng trạng thái):** Hiển thị tường minh các nhãn trạng thái (`Registered`, `Cancelled`, `Present`, `Absent`) và lý do từ chối thao tác.
* **`NFR07` (Thích nghi đa thiết bị):** Giao diện Responsive hoạt động tốt trên Desktop (Windows 10/11, macOS 14+) và Mobile (Android 10+).

### Ngoài phạm vi MVP (Out of Scope / Won't Have)
* Quản lý nhiều CLB độc lập trên cùng hệ thống (Multi-tenant).
* Ứng dụng di động Native (iOS/Android).
* Thu quỹ, cổng thanh toán trực tuyến, quản lý tài chính/nhà tài trợ.
* Tích hợp SSO hoặc cơ sở dữ liệu sinh viên nội bộ của trường.
* Check-in bằng mã QR (`PB26`) và gửi thông báo tự động qua Email/SMS (`PB27`) khi chưa có Change Request được phê duyệt.

---

## 5. Kiến Trúc Hệ Thống & Công Nghệ Sử Dụng (Tech Stack)

Hệ thống được thiết kế theo mô hình **Three-Tier Architecture (Kiến trúc 3 lớp)** trên một ứng dụng **PHP Monolith**:

```text
[ Người dùng (Trình duyệt PC / Mobile) ]
                  │
                  ▼  (HTTP/HTTPS - Form Submit / Fetch JSON)
┌──────────────────────────────────────────────────────────┐
│ 1. PRESENTATION LAYER (Giao diện Website)                │
│    HTML5 · CSS3 · Bootstrap 5 · Vanilla JavaScript       │
└──────────────────────────────────────────────────────────┘
                  │
                  ▼  (Xác thực PHP Session, CSRF Token, RBAC)
┌──────────────────────────────────────────────────────────┐
│ 2. APPLICATION LAYER (Backend Nghiệp vụ - Apache & PHP)  │
│    - Auth & RBAC Module     - Club & Member Module       │
│    - Event & Registration   - Check-in & Stats Module    │
└──────────────────────────────────────────────────────────┘
                  │
                  ▼  (PDO Prepared Statements / ACID Transactions)
┌──────────────────────────────────────────────────────────┐
│ 3. DATA LAYER (Cơ sở dữ liệu Quan hệ)                    │
│    MySQL (Storage Engine: InnoDB) · Quản trị: phpMyAdmin │
└──────────────────────────────────────────────────────────┘
```

### Bảng Công Nghệ Chi Tiết

| Thành phần | Lựa chọn Công nghệ | Lý do lựa chọn & Giải pháp kỹ thuật |
| :--- | :--- | :--- |
| **Frontend** | `HTML5`, `CSS3`, `JavaScript`, `Bootstrap 5` | Đảm bảo giao diện Responsive (`NFR07`); hệ thống Grid/Form/Table của Bootstrap 5 giúp chuẩn hóa UI nhanh; JS hỗ trợ thao tác tra cứu & check-in mượt mà. |
| **Backend & Web Server** | `PHP 8.x`, `Apache Server` | Kiến trúc Monolith gọn nhẹ, phù hợp phạm vi 1 CLB; kiểm soát chặt chẽ logic nghiệp vụ và phân quyền phía Server trước khi chạm tới CSDL. |
| **Data Access** | `PHP Data Objects (PDO)` | Sử dụng 100% **Prepared Statements** (truy vấn tham số hóa) để ngăn chặn triệt để lỗ hổng SQL Injection. |
| **Database** | `MySQL` (`InnoDB` Engine) | Đảm bảo toàn vẹn khóa ngoại (`FK`), chỉ mục duy nhất (`UNIQUE`), hỗ trợ **ACID Transaction** và khóa dòng **`SELECT ... FOR UPDATE`** chống vượt số chỗ khi nhiều người đăng ký cùng lúc. |
| **Authentication & Security** | `PHP Session`, `password_hash()`, `CSRF Token` | Băm mật khẩu bằng `password_hash()` / `password_verify()`; bảo vệ phiên với cookie `HttpOnly`, `SameSite`; chống CSRF cho mọi biểu mẫu thay đổi dữ liệu và dùng `htmlspecialchars()` chống XSS. |
| **Môi trường & Công cụ** | `XAMPP`, `phpMyAdmin`, `Git/GitHub`, `Jira`, `Figma` | Chi phí triển khai 0 VNĐ (phù hợp ràng buộc ngân sách dự án), dễ dàng thiết lập môi trường phát triển và kiểm thử cục bộ. |

---

## 6. Thiết Kế Cơ Sở Dữ Liệu (Database Schema)

Cơ sở dữ liệu đạt chuẩn **3NF**, gồm **7 bảng thực thể cốt lõi** với toàn bộ giá trị trạng thái/vai trò được chuẩn hóa bằng **Tiếng Anh**:

1. **`Role`**: Lưu trữ 4 vai trò hệ thống (`'System Administrator'`, `'Club Manager'`, `'Club Member'`, `'Event Participant'`).
2. **`Account`**: Lưu thông tin định danh và đăng nhập (`AccountID`, `Email UNIQUE`, `PasswordHash`, `FullName`, `StudentCode`, `RoleID`, `IsActive`, `CreatedAt`).
3. **`Club`**: Lưu hồ sơ CLB Tiếng Anh (`ClubID`, `ClubName`, `Description`, `ContactInfo`, `ManagerID`).
4. **`Club_Member`**: Lưu danh sách thành viên thuộc CLB (`MemberID`, `AccountID`, `ClubID`, `MemberRole` [`'Member'`, `'Board Member'`, `'Event Supporter'`], `Status` [`'Active'`, `'Inactive'`], `JoinedDate`).
5. **`Event`**: Lưu thông tin sự kiện (`EventID`, `ClubID`, `EventName`, `Description`, `Location`, `StartTime`, `EndTime`, `RegistrationDeadline`, `MaxCapacity`, `Status` [`'Draft'`, `'Published'`, `'Cancelled'`], `CreatedAt`).
6. **`Event_Registration`**: Lưu lượt đăng ký tham gia (`RegID`, `EventID`, `AccountID`, `RegDate`, `Status` [`'Registered'`, `'Cancelled'`]). Ràng buộc **`UNIQUE(EventID, AccountID)`**.
7. **`Attendance`**: Lưu kết quả điểm danh thực tế (`AttendanceID`, `RegID UNIQUE`, `AttendanceStatus` [`1`: Present, `0`: Absent], `CheckInAt`, `CheckedInBy`).

---

## 7. Danh Sách Màn Hình & Truy Vết Yêu Cầu

Hệ thống bao gồm **16 màn hình thuộc phạm vi MVP (`SCR01–SCR16`)** và 3 màn hình mở rộng (`SCR17–SCR19`):

| Mã SCR | Tên màn hình | Yêu cầu (FR) | Use Case | Backlog ID | Vai trò truy cập |
| :---: | :--- | :---: | :---: | :---: | :--- |
| `SCR01` | Đăng ký tài khoản | `FR01` | `UC01` | `PB01` | Khách (Chưa đăng nhập) |
| `SCR02` | Đăng nhập | `FR02` | `UC02` | `PB02, PB03` | Mọi vai trò |
| `SCR03` | Hồ sơ cá nhân & Tư cách thành viên | `FR04` | `UC04` | `PB05` | `Club Member`, `Event Participant`, `Club Manager` |
| `SCR04` | Thông tin CLB (Xem công khai) | `FR06` | `UC05` | `PB06` | Mọi vai trò |
| `SCR05` | Quản lý hồ sơ CLB | `FR05` | `UC17` | `PB07` | `Club Manager` |
| `SCR06` | Danh sách thành viên CLB | `FR07` | `UC06` | `PB08` | `Club Manager` |
| `SCR07` | Thêm / Sửa thành viên (Vai trò & Trạng thái) | `FR07, FR08` | `UC06` | `PB09, PB10` | `Club Manager` |
| `SCR08` | Danh sách sự kiện | `FR10` | `UC08` | `PB13` | Mọi vai trò |
| `SCR09` | Chi tiết sự kiện, Đăng ký & Hủy đăng ký | `FR10, FR12` | `UC08, UC09` | `PB14, PB15` | Mọi vai trò (Đăng ký/Hủy: `Member`, `Participant`) |
| `SCR10` | Biểu mẫu Tạo / Sửa sự kiện | `FR09` | `UC07` | `PB11, PB12` | `Club Manager` |
| `SCR11` | Quản lý danh sách sự kiện (Nội bộ) | `FR09` | `UC07` | `PB11, PB12` | `Club Manager` |
| `SCR12` | Hoạt động của tôi (Trạng thái & Lịch sử) | `FR12, FR13` | `UC10` | `PB16` | `Club Member`, `Event Participant` |
| `SCR13` | Danh sách đăng ký theo sự kiện | `FR14` | `UC11` | `PB17` | `Club Manager`, `Authorized Club Member` |
| `SCR14` | Điểm danh (Check-in) sự kiện | `FR15` | `UC12` | `PB18, PB19` | `Club Manager`, `Authorized Club Member` |
| `SCR15` | Thống kê hoạt động cơ bản | `FR16` | `UC13` | `PB20` | `Club Manager` |
| `SCR16` | Quản trị tài khoản & Quyền hệ thống | `FR17` | `UC14` | `PB04` | `System Administrator` |

---

## 8. Hướng Dẫn Cài Đặt & Chạy Dự Án (Localhost XAMPP)

### Yêu cầu môi trường
* **XAMPP** (hỗ trợ `Apache` + `PHP >= 8.0` + `MySQL 8.0` với `InnoDB` Engine).
* Trình duyệt web hiện đại (Chrome, Edge, Safari).

### Các bước thiết lập
1. **Clone mã nguồn về thư mục `htdocs` của XAMPP:**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Phungcamtann/EventFlow.git
   ```
2. **Khởi động dịch vụ:**
   * Mở **XAMPP Control Panel**, nhấn **Start** tại module `Apache` và `MySQL`.
3. **Khởi tạo Cơ sở dữ liệu:**
   * Truy cập `http://localhost/phpmyadmin`.
   * Tạo cơ sở dữ liệu mới tên `eventflow_db` (Collation: `utf8mb4_unicode_ci`).
   * Import tệp kịch bản SQL khởi tạo bảng và dữ liệu mẫu (`database/schema_and_seed.sql`).
4. **Cấu hình kết nối CSDL:**
   * Kiểm tra thông tin kết nối PDO trong tệp cấu hình (`config/database.php`):
     ```php
     $host = '127.0.0.1';
     $db   = 'eventflow_db';
     $user = 'root';
     $pass = '';
     ```
5. **Truy cập ứng dụng:**
   * Mở trình duyệt tại địa chỉ: `http://localhost/EventFlow/`

---

## 9. Tiêu Chí Nghiệm Thu & Kiểm Thử (QA & UAT)

* **Kiểm thử chức năng (Functional Testing):** Thiết kế và thực thi tối thiểu **30 Test Cases** bao phủ toàn bộ 15 yêu cầu `Must Have` (bao gồm các ca hợp lệ, không hợp lệ, điều kiện biên và đồng thời); tỷ lệ Pass đạt **$\ge 90\%$**, không còn lỗi mức `Critical`.
* **Kiểm thử phi chức năng (Performance & Responsive):**
  * Kiểm tra đồng nhất dữ liệu giữa bảng đăng ký, điểm danh và thống kê (`NFR01`).
  * Đo thời gian phản hồi tra cứu & check-in bằng Chrome DevTools đạt ngưỡng **$\le 2-3\text{ giây}$** (`NFR02`, `NFR03`).
  * Kiểm thử hiển thị trên màn hình Desktop và Mobile Android/iOS (`NFR07`).
* **Kiểm thử chấp nhận người dùng (UAT):** Thực hiện với tối thiểu **5 người dùng thực tế** thuộc các nhóm vai trò của CLB Tiếng Anh.

---

## 10. Đội Ngũ Phát Triển (Team Charter)

| Họ và Tên | Vai trò (Role) | Trách nhiệm chính | Liên hệ |
| :--- | :--- | :--- | :--- |
| **Dương Hoàng Sâm** | Project Manager (PM) | Lập kế hoạch dự án (Charter, WBS, Gantt); điều phối Sprint trên Jira; quản lý rủi ro, thay đổi và tổng hợp báo cáo tuần. | `hssamd27@gmail.com` |
| **Nguyễn Duy Thành Tài** | Business Analyst (BA) | Phỏng vấn người dùng; xây dựng FR/NFR, Use Case, BPMN, Scope Statement, Product Backlog và ma trận truy vết. | `tainguyen.31241020132@st.ueh.edu.vn` |
| **Phạm Thiên Long** | UI/UX Designer | Thiết kế User Flow, Wireframe (`SCR01–SCR19`), Design System và Interactive Prototype trên Figma. | `longpham17.works@gmail.com` |
| **Trần Yến Phượng** | Frontend Lead | Phát triển giao diện Responsive (HTML5, CSS3, Bootstrap 5, JS) cho 4 vai trò; tích hợp dữ liệu hiển thị và xử lý tương tác. | `phuongtran.31241022663@st.ueh.edu.vn` |
| **Phùng Cẩm Tân** | Backend Lead | Thiết kế ERD & MySQL Schema; xây dựng Backend PHP (PDO, Session Auth, RBAC, Transaction xử lý đăng ký, check-in, thống kê). | `tanphung.31241020320@st.ueh.edu.vn` |
| **Tiêu Lâm Định Quốc** | Tester & Reporter (QA) | Xây dựng Test Plan, Test Cases ($\ge 30$ ca); thực thi kiểm thử chức năng/hiệu năng, quản lý Defect Log và tổ chức UAT. | `dinhquoc.31241025304@st.ueh.edu.vn` |

---

## 11. Tài Liệu & Liên Kết Dự Án

* **GitHub Repository:** [https://github.com/Phungcamtann/EventFlow](https://github.com/Phungcamtann/EventFlow)
* **Figma Wireframe & Prototype:** [Thiết kế UI/UX EventFlow trên Figma](https://www.figma.com/design/8yt3gM5gUk5y43TXyy7DSa/QLDA?node-id=0-1&p=f&t=pbqrU5BoQNC9mIQ2-0)
* **Sơ đồ Use Case, BPMN & ERD (Draw.io):** [Tài liệu Sơ đồ Hệ thống Nhóm 8](https://app.diagrams.net/#G1CEjQ70BKB_PwUnkaPdCWKMmkTB-bJEgL)
* **WBS & Kế hoạch Tiến độ (Google Sheets):** [Bảng WBS & Gantt Chart Nhóm 8](https://docs.google.com/spreadsheets/d/1DCLJRxN7x6FoUHaaXYfCKyY90hSTmsj8iEtdFLiiFAM/edit?usp=sharing)
* **Hồ sơ Minh chứng Phỏng vấn (Google Drive):** [Thư mục Phỏng vấn P01–P05](https://drive.google.com/drive/folders/1_HhO3_SpBOi02RXDMmmbp6-_0basH2VW?usp=drive_link)