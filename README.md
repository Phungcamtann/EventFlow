# EventFlow

**Website quản lý thành viên và sự kiện cho một câu lạc bộ duy nhất trong môi trường đại học.**

Nhóm thực hiện:
- Dương Hoàng Sâm (Leader - PM)
- Phạm Thiên Long (UI/UX Designer)
- Phùng Cẩm Tân (Backend Lead)
- Nguyễn Duy Thành Tài (Business analyst)
- Tiêu Lâm Định Quốc (Tester & Reporter)
- Trần Yến Phượng (Frontend Lead)

🧭 Project Coordinator(PC): Định Thị Anh Thư

EventFlow phục vụ hoạt động quản lý của **một câu lạc bộ** theo quy trình tập trung: quản lý thông tin thành viên, công bố sự kiện, tiếp nhận đăng ký, điểm danh và theo dõi kết quả tham gia. Ban chủ nhiệm, người tổ chức và người tham gia truy cập website qua trình duyệt trên máy tính hoặc điện thoại. **Backend sử dụng C# trên nền tảng .NET.**

> **Trạng thái tại ngày 18/10/2026:** Dự án đang ở giai đoạn xác định yêu cầu. Định hướng đã chốt là **website cho một CLB duy nhất, backend C#/.NET**. Thư mục hiện có hai tài liệu PDF, chưa có mã nguồn website, cơ sở dữ liệu, cấu hình triển khai hoặc kết quả kiểm thử. Các chức năng chi tiết, framework, công nghệ giao diện, cơ sở dữ liệu và kiến trúc dưới đây vẫn là đề xuất để nhóm thảo luận.

## 1. Bối cảnh và mục tiêu

Các nhu cầu cần kiểm chứng gồm:

- Ban chủ nhiệm cần theo dõi thông tin và tình trạng thành viên.
- Người tổ chức cần quản lý thông tin sự kiện, danh sách đăng ký và kết quả điểm danh.
- Sinh viên cần tìm thông tin sự kiện, đăng ký hoặc hủy trong thời hạn cho phép và biết trạng thái đăng ký của mình.
- Người phụ trách cần số liệu cơ bản để tổng kết hoạt động.

**Những khó khăn này chưa được xác nhận bằng khảo sát trong tài liệu hiện có.** Việc CLB được phục vụ đang dùng biểu mẫu, bảng tính, nhóm chat hoặc danh sách giấy mới là giả định cần tìm hiểu. Nhóm dự kiến phỏng vấn 5–10 người tổ chức/người tham gia hoạt động của CLB, khảo sát và quan sát quy trình thực tế; lưu ngày thực hiện, câu trả lời và ghi chú ẩn danh.

Mục tiêu MVP, tức phiên bản tối thiểu có thể dùng và nghiệm thu, là hoàn thành luồng nghiệp vụ cốt lõi đã được xác nhận, có tiêu chí nghiệm thu và minh chứng kiểm thử. Chưa đặt chỉ tiêu như “giảm 50% thời gian quản lý” khi chưa có số liệu ban đầu và cách đo.

## 2. Người dùng và các bên liên quan

| Nhóm | Nhu cầu chính |
| --- | --- |
| Ban chủ nhiệm / người phụ trách thành viên | Quản lý thông tin thành viên và theo dõi hoạt động CLB |
| Người được phân công tổ chức sự kiện | Tạo, cập nhật, công bố sự kiện; theo dõi đăng ký; điểm danh; xem thống kê |
| Sinh viên / thành viên tham gia | Xem sự kiện, đăng ký, hủy đăng ký trong hạn và kiểm tra trạng thái |
| Giảng viên / người có thẩm quyền phê duyệt | Xác nhận đề tài, phạm vi và tiêu chí đánh giá theo học phần |
| Nhóm phát triển | Khảo sát, thiết kế, xây dựng, kiểm thử và bàn giao sản phẩm |

Một người có thể đồng thời là người tổ chức và người tham gia. **Có tài khoản hoặc đăng ký một sự kiện không mặc nhiên đồng nghĩa với là thành viên CLB.** Cần xác nhận sự kiện có cho phép sinh viên ngoài CLB tham gia hay không trước khi chốt phân quyền.

## 3. Phạm vi MVP dự kiến

Website phục vụ một CLB duy nhất. Danh sách thành viên, sự kiện, phân quyền và thống kê đều thuộc phạm vi hoạt động của CLB này.

### Module quản lý thành viên

Tài liệu hiện mới xác định module này ở mức tổng quát. Các thao tác chi tiết sau là **đề xuất cần xác nhận**:

- Xem và tìm kiếm danh sách thành viên.
- Thêm, cập nhật thông tin thành viên và trạng thái hoạt động.
- Xem hồ sơ cơ bản và lịch sử tham gia sự kiện của thành viên theo quyền truy cập.

Quy trình xin gia nhập, duyệt thành viên và tự đăng ký tài khoản cần được làm rõ qua khảo sát.

### Module quản lý sự kiện

Các nhóm chức năng đã được nêu trong tài liệu đề tài:

| Chức năng | Kết quả mong muốn |
| --- | --- |
| Tạo và cập nhật sự kiện | Người tổ chức quản lý nội dung và thông tin sự kiện |
| Công bố và xem sự kiện | Người tham gia tiếp cận thông tin sự kiện được công bố |
| Đăng ký tham gia | Sinh viên đăng ký và kiểm tra được trạng thái của mình |
| Hủy đăng ký | Người tham gia hủy trong thời hạn được cho phép |
| Theo dõi danh sách đăng ký | Người phụ trách biết ai đã đăng ký sự kiện |
| Điểm danh | Người phụ trách ghi nhận người thực tế tham gia |
| Thống kê cơ bản | Người tổ chức xem kết quả đăng ký và tham gia |

**Chi tiết triển khai đề xuất:** sự kiện có tên, mô tả, địa điểm, thời gian bắt đầu/kết thúc và hạn đăng ký/hủy. Số lượng chỗ tối đa, quy trình duyệt đăng ký, trạng thái sự kiện và công thức thống kê cần được nhóm xác nhận. Bản đầu có thể điểm danh thủ công trên danh sách; QR là phương án mở rộng nếu có nhu cầu.

### Chức năng nền tảng đề xuất

- Đăng nhập, đăng xuất và quản lý tài khoản bằng ASP.NET Core Identity.
- Phân quyền theo vai trò, kết hợp kiểm tra người được phân công phụ trách từng sự kiện.
- Giao diện thích ứng với màn hình máy tính và điện thoại.
- Kiểm tra dữ liệu nhập, thông báo lỗi rõ ràng và bảo vệ thông tin cá nhân.

### Ngoài phạm vi

Theo TopicSelection: quản lý tài chính, nhà tài trợ, ứng dụng di động riêng, AI nâng cao và chức năng ngoài phạm vi đã duyệt.

Theo phạm vi đã chốt, hệ thống không bao gồm quản lý nhiều CLB, tạo/chuyển đổi giữa các CLB, quản trị liên CLB hoặc phát triển app cài đặt riêng trên máy tính/điện thoại.

Đề xuất có thể cân nhắc sau cho chính CLB này: bán vé/thanh toán, chat, tích hợp hệ thống của trường, gửi thông báo tự động và điểm danh QR. Đây chưa phải cam kết của MVP.

## 4. Luồng sử dụng chính

1. Người tổ chức đăng nhập và tạo sự kiện.
2. Người tổ chức kiểm tra thông tin, thiết lập các thời hạn đã thống nhất và công bố sự kiện.
3. Sinh viên xem thông tin và đăng ký tham gia.
4. Sinh viên kiểm tra trạng thái hoặc hủy đăng ký trong hạn.
5. Người phụ trách theo dõi danh sách đăng ký.
6. Khi sự kiện diễn ra, người được phân công thực hiện điểm danh.
7. Sau sự kiện, người tổ chức xem thống kê và tổng kết.

Luồng quản lý thành viên vận hành song song, cung cấp thông tin thành viên cho các nghiệp vụ liên quan sau khi quy tắc liên kết tài khoản và thành viên được xác nhận.

## 5. Công nghệ đề xuất

**Backend đã xác định: C#/.NET. Bộ công nghệ khuyến nghị cho website: ASP.NET Core MVC trên .NET 10 LTS + Razor Views/Bootstrap + Entity Framework Core + SQL Server.**

Đây là lựa chọn phù hợp với phạm vi quản lý dữ liệu, biểu mẫu, phân quyền và báo cáo cơ bản của một CLB. Backend C# xử lý đăng nhập, quyền truy cập và nghiệp vụ trên server; giao diện website được xây bằng Razor Views, HTML/CSS và JavaScript khi cần. Nhóm có thể tổ chức hai phần trong cùng dự án web và chia việc theo chức năng.

### Bộ công nghệ chính

| Thành phần | Đề xuất | Lý do lựa chọn |
| --- | --- | --- |
| Ngôn ngữ và nền tảng backend | C#, .NET 10 LTS | Giữ backend bằng C# theo yêu cầu; phiên bản .NET được đề xuất có thời gian hỗ trợ dài |
| Framework backend và kết xuất trang | ASP.NET Core MVC | Xử lý yêu cầu tại server và tạo các trang HTML cho website quản lý |
| Giao diện | Razor Views, HTML/CSS, Bootstrap 5; JavaScript khi cần | Xây dựng form, bảng, bộ lọc và giao diện responsive trong cùng dự án |
| Truy cập dữ liệu | Entity Framework Core 10, SQL Server provider và migrations | Ánh xạ dữ liệu bằng C#, quản lý thay đổi cấu trúc cơ sở dữ liệu |
| Cơ sở dữ liệu | SQL Server | Phù hợp dữ liệu quan hệ như thành viên, sự kiện, đăng ký và điểm danh |
| Tài khoản và phân quyền | ASP.NET Core Identity, cookie authentication, authorization policies | Có nền tảng quản lý người dùng; bổ sung kiểm tra quyền trên từng tài nguyên |
| Kiểm thử | xUnit và kiểm thử tích hợp với SQL Server dành riêng cho test | Kiểm tra quy tắc nghiệp vụ, phân quyền và tính nhất quán dữ liệu |
| Làm việc nhóm | Git và GitHub; GitHub Actions khi có mã nguồn | Quản lý thay đổi, review và tự động build/test |

[ASP.NET Core MVC](https://learn.microsoft.com/en-us/aspnet/core/mvc/overview?view=aspnetcore-10.0) hỗ trợ Razor để tạo HTML trên server. Vì vậy C# là ngôn ngữ chính, nhưng xây dựng giao diện vẫn cần HTML/CSS và một phần JavaScript. [EF Core SQL Server provider](https://learn.microsoft.com/en-us/ef/core/providers/sql-server/) hỗ trợ SQL Server và Azure SQL; [ASP.NET Core Identity](https://learn.microsoft.com/en-us/aspnet/core/security/authentication/identity?view=aspnetcore-10.0) cung cấp nền tảng quản lý tài khoản.

## 6. Môi trường phát triển và triển khai

**Sản phẩm là website dành cho một CLB duy nhất.** Người dùng mở địa chỉ website trên trình duyệt của máy tính hoặc điện thoại; backend C#/.NET và cơ sở dữ liệu chạy trên server. Giao diện thích ứng với màn hình điện thoại là một phần của website. Windows là môi trường phát triển được đề xuất cho nhóm, không phải yêu cầu đối với thiết bị của người dùng cuối.

| Mục đích | Môi trường đề xuất |
| --- | --- |
| Viết mã và debug | Windows 11, Visual Studio 2026 Community, workload **ASP.NET and web development**, .NET 10 SDK |
| Cơ sở dữ liệu trên máy phát triển | SQL Server 2025 Developer và SQL Server Management Studio (SSMS) |
| Chạy và kiểm tra trong quá trình làm | ASP.NET Core chạy local qua HTTPS, mỗi thành viên dùng cơ sở dữ liệu riêng |
| Demo online / triển khai sau khi chốt ngân sách | Azure App Service để chạy website và backend C#; Azure SQL Database cho dữ liệu |
| Đồng bộ trong nhóm | Thống nhất SDK, migrations, dữ liệu mẫu và cách cấu hình qua README |

[Visual Studio Community](https://visualstudio.microsoft.com/vs/community/) là lựa chọn phù hợp cho học tập; dùng bản tương thích .NET 10 theo [danh sách SDK cho Visual Studio](https://dotnet.microsoft.com/en-us/download/visual-studio-sdks). Nếu ưu tiên trình soạn thảo gọn hơn, có thể dùng VS Code cùng công cụ C# và .NET SDK.

[SQL Server Developer](https://www.microsoft.com/en-us/sql-server/sql-server-downloads) dành cho phát triển và kiểm thử, không dùng bản này làm cơ sở dữ liệu production. Phương án [ASP.NET Core + Azure App Service + Azure SQL](https://learn.microsoft.com/en-us/azure/app-service/tutorial-dotnetcore-sqldb-app) có hướng dẫn chính thức; chi phí và ưu đãi sinh viên cần kiểm tra trước khi chọn gói. Chưa giả định dự án đã có tài khoản cloud, ngân sách hoặc hosting.

Nếu ngân sách hoặc hạ tầng sau này ưu tiên PostgreSQL, nhóm có thể đánh giá EF Core với provider Npgsql thay thế. Nên chốt **một** hệ quản trị cơ sở dữ liệu trước khi tạo migrations, thay vì triển khai song song hai hệ trong MVP.

### Chuẩn bị trước khi lập trình

1. Xác nhận nghiệp vụ chi tiết với đại diện CLB và giảng viên trong phạm vi website cho một CLB.
2. Thống nhất framework, công nghệ giao diện và phiên bản SDK giữa các thành viên; backend giữ C#/.NET.
3. Chuẩn bị IDE, .NET SDK và cơ sở dữ liệu phát triển.
4. Khởi tạo solution, dự án web, dự án kiểm thử và quản lý mã nguồn bằng Git.
5. Thiết lập Identity, cấu hình kết nối riêng cho từng máy và dữ liệu mẫu.
6. Cập nhật README với lệnh thiết lập môi trường, tạo database và chạy website đã được kiểm chứng.

**Hiện chưa có hướng dẫn chạy website thực tế:** thư mục chưa chứa solution hoặc file `.csproj`. Không có tài khoản demo, đường dẫn API hay URL triển khai đã được xác nhận.

## 7. Kiến trúc đề xuất

Bắt đầu với **một website có backend C# được phân lớp, triển khai như một khối thống nhất (monolith)** và một cơ sở dữ liệu dành cho CLB. Nhóm chia chức năng theo thành viên và sự kiện, đồng thời giữ phần giao diện, nghiệp vụ và truy cập dữ liệu riêng biệt.

Luồng xử lý: **Trình duyệt → backend C# (ASP.NET Core MVC) → dịch vụ nghiệp vụ → EF Core → SQL Server.** Backend trả về các trang HTML để trình duyệt hiển thị.

| Phần | Trách nhiệm |
| --- | --- |
| Controllers, Views, ViewModels | Nhận yêu cầu, kiểm tra dữ liệu đầu vào và hiển thị kết quả |
| Services | Xử lý quy tắc thành viên, sự kiện, đăng ký, hủy và điểm danh |
| Entities / Models | Biểu diễn các đối tượng và trạng thái nghiệp vụ |
| Data | DbContext, cấu hình quan hệ dữ liệu và migrations |
| Identity và authorization | Xác thực và kiểm tra quyền thực hiện thao tác |

Nghiệp vụ nên nằm trong service hoặc model phù hợp, để controller gọn và có thể kiểm thử độc lập. Nếu giao diện website cần tải hoặc cập nhật dữ liệu mà không tải lại toàn bộ trang, có thể bổ sung endpoint trên cùng backend C#.

### Mô hình dữ liệu sơ bộ

Đây là gợi ý thiết kế cho **một CLB duy nhất**, chưa phải schema đã có. Thông tin chung như tên, mô tả và thông tin liên hệ của CLB có thể lưu trong cấu hình website hoặc một hồ sơ CLB duy nhất. Mô hình không cần cơ chế phân tách dữ liệu giữa nhiều CLB.

| Đối tượng | Thông tin chính dự kiến |
| --- | --- |
| ApplicationUser | Tài khoản đăng nhập, tên hiển thị, email; dùng nền tảng Identity |
| ClubMember | Liên kết tài khoản, thông tin thành viên của CLB, ngày tham gia và trạng thái |
| Event | Sự kiện do CLB tổ chức: tên, nội dung, địa điểm, thời gian, thời hạn đăng ký/hủy, người phụ trách, trạng thái |
| EventRegistration | Sự kiện, tài khoản đăng ký, thời điểm đăng ký/hủy và trạng thái |
| Attendance | Bản đăng ký, thời điểm điểm danh và người thực hiện |

Một sự kiện có nhiều bản đăng ký; một tài khoản có thể đăng ký nhiều sự kiện. Mỗi bản đăng ký có tối đa một kết quả điểm danh trong mô hình MVP đề xuất. Việc liên kết ClubMember không nên ép mọi người đăng ký sự kiện đều phải là thành viên nếu khảo sát cho phép sinh viên ngoài CLB tham gia.

### Quy tắc đề xuất cần xác nhận

- Một tài khoản có tối đa một bản đăng ký cho mỗi sự kiện; đăng ký lại sau khi hủy cập nhật bản ghi theo chính sách được chốt.
- Chỉ cho đăng ký vào sự kiện đang mở và chưa quá hạn; chỉ cho hủy trong thời hạn được cho phép.
- Nếu có giới hạn chỗ, kiểm soát số lượng trong transaction phù hợp để các lượt đăng ký đồng thời không vượt sức chứa.
- Chỉ người được phân quyền và phân công mới được sửa sự kiện, xem danh sách riêng tư hoặc điểm danh.
- Chỉ điểm danh cho bản đăng ký hợp lệ; thao tác điểm danh lặp không tạo thêm bản ghi.
- Phân biệt “đã đăng ký” với “đã tham gia”; thống nhất cách tính số đăng ký còn hiệu lực và tỷ lệ tham dự.
- Kiểm tra quyền ở server, kể cả khi người dùng truy cập trực tiếp đường dẫn hoặc sửa dữ liệu gửi lên.
- Vai trò quản lý và báo cáo thống kê áp dụng trong phạm vi CLB duy nhất của website.
- Nếu lưu thời gian theo UTC, chuyển về múi giờ Việt Nam khi hiển thị và áp dụng nhất quán khi kiểm tra hạn.

## 8. Tổ chức thư mục

### Hiện có

```text
EventFlow/
├── README.md             # Tổng quan và định hướng triển khai
├── ProjectChapter.pdf    # Đề xuất EventFlow và lý do lựa chọn đề tài
└── TopicSelection.pdf    # Khung nội dung, phạm vi và tiêu chí dự kiến
```

### Cấu trúc mã nguồn gợi ý khi bắt đầu phát triển

```text
src/
└── EventFlow.Web/
    ├── Controllers/
    ├── Views/
    ├── ViewModels/
    ├── Models/
    ├── Services/
    ├── Data/
    └── wwwroot/
tests/
└── EventFlow.Tests/
docs/
```

Các thư mục `src`, `tests`, `docs` ở trên **chưa được tạo**. Với MVP, một dự án web và một dự án kiểm thử là điểm bắt đầu vừa sức; có thể tách thêm thư viện khi quy mô và nhu cầu thực tế yêu cầu.

## 9. Kế hoạch thực hiện và phân công

Tài liệu nêu nhóm có 6 thành viên với các vai trò PM, BA, UI/UX, Backend, Frontend và Tester/Reporter. Phân công dưới đây là gợi ý; chưa có tên người phụ trách chính thức.

| Vai trò | Trọng tâm công việc |
| --- | --- |
| PM | Quản lý phạm vi, kế hoạch, rủi ro và phối hợp nghiệm thu |
| BA | Khảo sát, mô tả yêu cầu, quy tắc nghiệp vụ và tiêu chí chấp nhận |
| UI/UX | Thiết kế luồng, bố cục và giao diện trên máy tính/điện thoại |
| Backend | Thiết kế dữ liệu, Identity, phân quyền và nghiệp vụ C# |
| Frontend | Xây dựng Razor Views, biểu mẫu và giao diện responsive; tích hợp với backend |
| Tester/Reporter | Lập ca kiểm thử, ghi nhận lỗi, tổng hợp minh chứng và báo cáo |

Lộ trình đề xuất theo đầu ra, chưa ấn định thời lượng vì tài liệu chưa xác nhận deadline toàn dự án:

1. **Khảo sát và chốt phạm vi:** có bằng chứng nhu cầu, danh sách chức năng ưu tiên và người phê duyệt.
2. **Thiết kế và dựng nền:** có giao diện mẫu, mô hình dữ liệu, dự án web, đăng nhập và phân quyền ban đầu.
3. **Hoàn thành luồng chính:** quản lý thành viên, tạo/công bố sự kiện, đăng ký/hủy, điểm danh và thống kê.
4. **Kiểm thử và bàn giao:** sửa lỗi, demo, nghiệm thu, hoàn thiện tài liệu và hướng dẫn chạy.

## 10. Kiểm thử và tiêu chí hoàn thành

Các nội dung sau là kế hoạch, chưa có kết quả kiểm thử thực tế:

- Mỗi chức năng ưu tiên cao có yêu cầu rõ ràng và tiêu chí chấp nhận.
- Luồng tạo sự kiện → công bố → đăng ký → điểm danh → thống kê hoạt động xuyên suốt.
- Kiểm tra các trường hợp đăng ký trùng, hết hạn, hủy quá hạn, điểm danh lặp và truy cập trái quyền.
- Nếu áp dụng giới hạn chỗ, kiểm thử đăng ký đồng thời ở thời điểm chỉ còn một chỗ.
- Số liệu thống kê đối chiếu được với dữ liệu đăng ký và điểm danh mẫu.
- Giao diện chính dùng được trên máy tính và điện thoại; thông báo lỗi giúp người dùng sửa thao tác.
- Kiểm thử tích hợp các ràng buộc dữ liệu trên SQL Server test; không chỉ dựa vào database giả lập trong bộ nhớ.
- Mật khẩu được xử lý qua Identity; thông tin kết nối và bí mật để trong cấu hình môi trường hoặc User Secrets, không đưa vào mã nguồn.
- Có bằng chứng kiểm thử và người đại diện có thẩm quyền nghiệm thu phạm vi đã chốt.

Sản phẩm bàn giao dự kiến gồm website MVP, tài liệu yêu cầu, thiết kế, mã nguồn, hướng dẫn cài đặt/sử dụng, kết quả kiểm thử và minh chứng nghiệm thu.

## 11. Những điểm cần chốt tiếp

- Tên CLB được phục vụ; ai là đại diện cung cấp yêu cầu và người nghiệm thu?
- Ai được tạo tài khoản, thêm thành viên, tạo sự kiện và phân công người phụ trách?
- Sự kiện có mở cho sinh viên ngoài CLB không? Đăng ký được xác nhận ngay hay phải duyệt?
- Có giới hạn chỗ, hạn đăng ký, hạn hủy và yêu cầu điểm danh cụ thể nào?
- Cách tính thống kê, dữ liệu cá nhân cần thu thập và quyền xem từng loại dữ liệu?
- Deadline, năng lực công nghệ của nhóm, ngân sách và môi trường demo?
- Tên PM, cơ chế phê duyệt phạm vi và các mốc kiểm tra của học phần?

Các rủi ro hiện được tài liệu nêu gồm khó tiếp cận người dùng, trễ tiến độ và thay đổi phạm vi. Nhóm nên khảo sát sớm, ưu tiên luồng cốt lõi và ghi nhận thay đổi yêu cầu trước khi mở rộng chức năng.

## 12. Tài liệu đầu vào

| Tài liệu | Nội dung được sử dụng |
| --- | --- |
| [ProjectChapter.pdf](./ProjectChapter.pdf) | Đề xuất EventFlow, người dùng mục tiêu, luồng quản lý sự kiện, lý do đổi đề tài và nhu cầu khảo sát |
| [TopicSelection.pdf](./TopicSelection.pdf) | Khung lựa chọn đề tài, hai module thành viên/sự kiện, phạm vi loại trừ, quy mô nhóm và tiêu chí thành công |

README ghi nhận định hướng đã thống nhất: **website phục vụ một CLB duy nhất, backend C#/.NET**. Khi yêu cầu chi tiết và bộ công nghệ được phê duyệt, cần cập nhật tài liệu này theo quyết định thực tế và bổ sung hướng dẫn chạy website từ mã nguồn đã xây dựng.
