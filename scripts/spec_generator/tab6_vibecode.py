# -*- coding: utf-8 -*-
"""
Tab 6: Vibe Code Prompts & Kế Hoạch Triển Khai Sprint 7 (4 Phase)
"""

def get_tab6():
    return """
    <!-- ==========================================
         TAB 6: VIBE CODE PROMPTS & KẾ HOẠCH SPRINT 7
         ========================================== -->
    <section id="tab-vibecode" class="tab-content space-y-6">
      
      <!-- DIRECTORY STRUCTURE -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="w-2.5 h-6 bg-amber-500 rounded-sm inline-block"></span>
            Cấu Trúc Thư Mục Chuẩn Hóa Dự Án EventFlow (Monolith Architecture)
          </h2>
          <p class="text-xs text-slate-600 mt-1">
            Bố trí module mạch lạc theo miền nghiệp vụ, phân tách rõ các dịch vụ API, giao diện công chúng và không gian quản trị nội bộ:
          </p>
        </div>

        <pre class="bg-slate-900 text-slate-100 p-4 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed"><code>e:/Dev/Study'/QLDA/Project/EventFlow/
├── app/
│   ├── config/
│   │   ├── database.php             # Cấu hình kết nối PDO MySQL
│   │   └── app.php                  # Cấu hình hằng số hệ thống & timezone (Asia/Ho_Chi_Minh)
│   ├── database/
│   │   ├── connection.php           # Khởi tạo PDO Singleton
│   │   └── schema_and_seed_v2.sql   # Script DDL 11 bảng & dữ liệu mẫu BELL Club
│   ├── includes/
│   │   ├── auth_guard.php           # Middleware kiểm tra RBAC 3 tầng & đổi pass lần đầu
│   │   ├── top_nav.php              # Header & Top Navigation cho Public Portal
│   │   ├── workspace_layout.php     # Layout Khung Left-Sidebar cho Workspace Portal
│   │   ├── footer.php               # Footer công khai & Fanpage BELL UEH
│   │   └── helpers.php              # Format ngày tháng, badge trạng thái 7 nhãn
│   ├── modules/
│   │   ├── auth/                    # SCR02, SCR17: Login, Logout, Profile, Change Password
│   │   ├── club/                    # SCR04, SCR16: Info công khai, Quản trị hồ sơ CLB
│   │   ├── events/                  # SCR05, SCR06, SCR07, SCR10, SCR11, SCR12, SCR13
│   │   │   ├── index.php            # Danh sách sự kiện công khai (Figma 02)
│   │   │   ├── detail.php           # Chi tiết SK & Form đăng ký khách/thành viên
│   │   │   ├── calendar.php         # Lịch Notion Calendar (Lịch tháng & Lịch tuần timeline)
│   │   │   ├── manage.php           # Quản lý sự kiện workspace
│   │   │   ├── form.php             # Form tạo/sửa sự kiện (Ràng buộc min 2 BĐH)
│   │   │   ├── console.php          # Unified Event Console (4 tabs hợp nhất UAT 14,15,16)
│   │   │   ├── my_events.php        # 2 tabs: Sự kiện tôi điều hành vs Sự kiện tôi tham gia
│   │   │   ├── api_register.php     # Endpoint xử lý đăng ký khách & thành viên
│   │   │   ├── api_checkin.php      # Endpoint toggle điểm danh (Kiểm tra Event_Staff)
│   │   │   └── api_poll.php         # Endpoint polling ngầm 3s đồng bộ multi-staff
│   │   ├── members/                 # SCR14: Quản lý nhân sự & 4 phòng ban
│   │   │   ├── index.php            # Danh sách thành viên 4 phòng ban
│   │   │   └── api_search.php       # AJAX realtime member search không độ trễ
│   │   ├── recruitment/             # SCR03, SCR15: Tuyển quân & Cấp tài khoản tự động
│   │   │   ├── apply.php            # Form nộp hồ sơ ứng tuyển công khai + Upload CV PDF
│   │   │   ├── manage.php           # Bảng quản lý ứng viên, chấm điểm, phỏng vấn
│   │   │   └── api_approve.php      # Action phê duyệt & trigger cấp tài khoản Ueh@<MSSV>
│   │   └── admin/                   # SCR18: Quản trị hệ thống & Audit logs
│   │       ├── index.php            # Giám sát hệ thống & tài khoản
│   │       └── accounts.php         # Quản lý tài khoản toàn hệ thống
│   └── assets/
│       ├── css/style.css            # Custom CSS, badges 7 trạng thái, left-sidebar style
│       ├── js/
│       │   ├── calendar.js          # Logic Notion Calendar (Month/Week toggle, Time bar)
│       │   ├── event_console.js     # Realtime check-in, polling 3s, staff assignment
│       │   └── member_search.js     # AJAX realtime member search
│       └── images/                  # Logo BELL Club, banner slider, booklet assets
├── docs/
│   ├── sprint-7/
│   │   ├── plan.md                  # Kế hoạch chi tiết từng UAT Sprint 7
│   │   └── files.md                 # Nhật ký thay đổi file theo từng commit
│   └── eventflow_vibe_code_design_specification.html # Tài liệu kỹ thuật v2.0
</code></pre>
      </div>

      <!-- SPRINT 7 4-PHASE IMPLEMENTATION ROADMAP -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="w-2.5 h-6 bg-teal-500 rounded-sm inline-block"></span>
            Kế Hoạch Triển Khai 13 UAT Còn Lại (Lộ Trình 4 Phase Sprint 7)
          </h2>
          <p class="text-xs text-slate-600 mt-1">
            Tuân thủ quy trình chuẩn: Lập <code>plan.md</code> & <code>files.md</code> &rarr; Triển khai &rarr; Hướng dẫn test thủ công &rarr; Người dùng chốt duyệt &rarr; Commit Git theo định danh quy định:
          </p>
        </div>

        <div class="space-y-4 text-xs">
          <!-- Phase 1 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-teal-600 text-white flex items-center justify-center text-xs">1</span>
                Phase 1: Chuẩn Hóa CSDL 11 Bảng & Cơ Cấu BELL CLUB UEH (UAT 05)
              </span>
              <span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 font-mono font-bold">Commit: SP7_Fix_UAT05</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              • Chạy migration nâng cấp DB: Tạo 4 bảng mới (<code>Department</code>, <code>Event_Staff</code>, <code>Recruitment_Campaign</code>, <code>Recruitment_Application</code>) và bổ sung các trường vào <code>Account</code>, <code>Club_Member</code>, <code>Event</code>, <code>Event_Registration</code>.
              <br/>• Nạp seed data chuẩn: 1 CLB BELL UEH, 4 phòng ban (HR, SPEC, COMS, ER) và 10 cán bộ lãnh đạo thực tế từ PDF.
              <br/>• Cập nhật giao diện trang Thông tin CLB (<code>info.php</code>) hiển thị đầy đủ cơ cấu tổ chức và 4 phòng ban.
            </p>
          </div>

          <!-- Phase 2 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">2</span>
                Phase 2: Dual-Portal Layout & Lịch Tương Tác Notion Calendar (UAT 18, 20, 06, 13)
              </span>
              <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-mono font-bold">Commit: SP7_Fix_UAT18_20_06_13</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              • Triển khai Public Top Navigation & Banner Slider trang chủ, Khối Sự kiện Hot (4 cards) và Footer Fanpage.
              <br/>• Triển khai Không gian Workspace với Modern Left-Sidebar Navigation. Xóa bỏ hoàn toàn các nút truy cập trùng lặp (nút Sửa CLB chỉ hiện trên info.php cho President/VP, dọn sạch nút điểm danh ngoài menu).
              <br/>• Xây dựng module Lịch sự kiện Notion Calendar (<code>calendar.php</code>) hỗ trợ toggle <strong>Lịch Tháng (Event pills)</strong> và <strong>Lịch Tuần (Timeline 07:00-22:00 có vạch đỏ giờ thực)</strong> + Mini dot calendar widget trên Dashboard.
            </p>
          </div>

          <!-- Phase 3 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs">3</span>
                Phase 3: Unified Event Console, Phân Công BTC & RBAC Điểm Danh (UAT 14, 15, 16, 08, 11, 10)
              </span>
              <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-mono font-bold">Commit: SP7_Fix_UAT14_15_16_08_11_10</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              • Xây dựng <strong>Unified Event Console</strong> (<code>console.php</code>) hợp nhất 4 tabs: Tổng quan & Chỉnh sửa, Điểm danh realtime với polling 3s, Ban tổ chức & Phân công cán bộ, Thống kê báo cáo tỷ lệ check-in.
              <br/>• Siết chặt RBAC tầng 3: Chỉ cán bộ được chỉ định trong <code>Event_Staff</code> của sự kiện X mới được mở bàn điểm danh sự kiện X.
              <br/>• Bắt buộc tối thiểu 2 cán bộ BĐH khi tạo sự kiện mới.
              <br/>• Tách "Sự kiện của tôi" (<code>my_events.php</code>) thành 2 tabs rõ ràng: "Sự kiện tôi điều hành/hỗ trợ" vs "Sự kiện tôi tham gia".
            </p>
          </div>

          <!-- Phase 4 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 text-sm flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">4</span>
                Phase 4: Đăng Ký Khách Vãng Lai & Phễu Tuyển Quân Cấp Tài Khoản Tự Động (UAT 02, 09)
              </span>
              <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono font-bold">Commit: SP7_Fix_UAT02_09</span>
            </div>
            <p class="text-slate-600 leading-relaxed">
              • Mở các endpoint đọc công khai (thông tin CLB, danh sách sự kiện, lịch sự kiện) không bắt buộc xác thực token/session.
              • Bổ sung form đăng ký sự kiện cho khách vãng lai: Họ tên, MSSV, SĐT, Email với kiểm tra định dạng và chống trùng lặp.
              • Xây dựng Cổng Tuyển Quân (<code>apply.php</code>): Form ứng tuyển 4 phòng ban, upload CV PDF.
              • Xây dựng Không gian Quản lý Tuyển quân cho Trưởng phòng HR & President (<code>manage.php</code>): Chấm điểm CV, lên lịch phỏng vấn và nút <strong>"Phê duyệt & Cấp tài khoản tự động"</strong> sinh tài khoản <code class="font-mono bg-white px-1 py-0.5 rounded">Ueh@&lt;MSSV&gt;</code> kèm cờ bắt buộc đổi pass lần đầu.
              • Tách biệt phân quyền System Admin (chỉ quản trị tài khoản hệ thống & logs, không can thiệp sâu sự kiện CLB).
            </p>
          </div>
        </div>
      </div>
    </section>
"""
