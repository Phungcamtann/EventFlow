# -*- coding: utf-8 -*-
"""
Tab 5: Đặc Tả 18 Màn Hình Hệ Thống & Giao Diện Lịch Notion Calendar
"""

def get_tab5():
    return """
    <!-- ==========================================
         TAB 5: ĐẶC TẢ 18 MÀN HÌNH & NOTION CALENDAR
         ========================================== -->
    <section id="tab-screens" class="tab-content space-y-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <div>
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <span class="w-2.5 h-6 bg-teal-500 rounded-sm inline-block"></span>
              Danh Mục & Đặc Tả 18 Màn Hình Hệ Thống (SCR01 – SCR18)
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
              Phân định mạch lạc giữa <strong>Cổng thông tin công chúng (Public Portal)</strong> và <strong>Không gian điều hành nội bộ (Workspace Portal)</strong>, xóa bỏ hoàn toàn điểm truy cập trùng lặp.
            </p>
          </div>
          <div class="w-full md:w-72">
            <input type="text" id="screen-search" onkeyup="filterScreens()" placeholder="🔍 Lọc theo mã màn hình, tên..." class="w-full text-xs px-3.5 py-2 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-sm" />
          </div>
        </div>

        <!-- NOTION CALENDAR DEEP-DIVE SPECIFICATION (UAT 18) -->
        <div class="mb-8 border border-blue-200 bg-blue-50/40 rounded-xl p-5 space-y-4">
          <div class="flex items-center justify-between">
            <span class="px-2.5 py-1 rounded bg-blue-600 text-white font-bold text-xs uppercase tracking-wider">Đặc Tả Trọng Tâm · UAT 18</span>
            <span class="text-xs text-blue-700 font-semibold">Phong cách Notion Calendar Chuẩn Mực</span>
          </div>
          <h3 class="text-base font-bold text-slate-900">Giao Diện Lịch Sự Kiện Đa Chế Độ: Lịch Tháng (Pills) & Lịch Tuần (Timeline 07h – 22h)</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Hệ thống cung cấp trải nghiệm quản lý thời gian trực quan theo chuẩn Notion Calendar (bám sát mockup đính kèm), phục vụ cả người xem công chúng và cán bộ ban tổ chức CLB:
          </p>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
            <!-- Mode 1: Month View -->
            <div class="bg-white rounded-lg p-4 border border-blue-100 shadow-sm space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="font-bold text-slate-900">1. Chế Độ Lịch Tháng (Month View Grid)</h4>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">7 Cột: Sun &rarr; Sat</span>
              </div>
              <p class="text-slate-600 leading-relaxed">
                • Header điều hướng: Tên tháng năm (VD: <em>November 2026</em>), 2 nút mũi tên chuyển tháng <code class="bg-slate-100 px-1 py-0.5 rounded">&lt; &gt;</code>, nút "Today" để quay về ngày hiện tại.
                <br/>• Ô ngày trong tháng hiển thị số ngày ở góc trên. Phía dưới là các <strong>thanh nhãn sự kiện (Event Pills)</strong> chứa Emoji/Icon, Tên sự kiện rút gọn và nhãn danh mục/trạng thái (VD: <span class="bg-amber-100 text-amber-800 px-1 rounded">✨ Speaking Circle</span>).
                <br/>• Bấm vào bất kỳ thẻ sự kiện nào sẽ mở popup xem nhanh thông tin tóm tắt và nút điều hướng chi tiết.
              </p>
            </div>

            <!-- Mode 2: Week Timeline View -->
            <div class="bg-white rounded-lg p-4 border border-blue-100 shadow-sm space-y-2">
              <div class="flex items-center justify-between">
                <h4 class="font-bold text-slate-900">2. Chế Độ Lịch Tuần (Week / Timeline View)</h4>
                <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 font-mono text-[11px]">Mốc giờ 07:00 – 22:00</span>
              </div>
              <p class="text-slate-600 leading-relaxed">
                • Trục ngang gồm 7 cột ngày trong tuần (Mon 24, Tue 25, <strong>Wed 26 [Today]</strong>, Thu 27, Fri 28...).
                <br/>• Trục dọc chia khung giờ từ <strong>07:00 đến 22:00</strong> (bước nhảy 60 phút). Có vạch ngang màu đỏ biểu thị <strong>thời gian hiện tại (Current Time Bar)</strong> kèm đồng hồ thời gian thực (VD: <em>10:15 AM</em>).
                <br/>• Các khối sự kiện được tính toán vị trí và chiều cao chính xác bám theo <code class="font-mono text-indigo-700">StartTime</code> và <code class="font-mono text-indigo-700">EndTime</code>, có màu sắc theo phòng ban phụ trách (HR: Tím, Specialist: Xanh dương, Coms: Hồng, ER: Cam).
              </p>
            </div>
          </div>
        </div>

        <!-- 18 SCREENS MASTER TABLE -->
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-100 text-slate-700 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="p-3 w-16">Mã</th>
                <th class="p-3">Tên Màn Hình & Tính Năng</th>
                <th class="p-3">Phân Hệ</th>
                <th class="p-3">Người Dùng Chính</th>
                <th class="p-3">URL File / Route</th>
                <th class="p-3">Mô Tả Trọng Tâm</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <!-- PUBLIC PORTAL -->
              <tr class="screen-row hover:bg-slate-50 bg-blue-50/20">
                <td class="p-3 font-mono font-bold text-blue-700">SCR01</td>
                <td class="p-3 font-semibold text-slate-900">Trang chủ Cổng thông tin CLB</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium">Public</span></td>
                <td class="p-3">Tất cả (Khách/SV)</td>
                <td class="p-3 font-mono text-slate-600">/app/index.php</td>
                <td class="p-3 text-slate-600">Banner Slider hoạt động BELL Club, Khối 4 Sự kiện Hot, Mini dot calendar, Footer Fanpage.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-slate-700">SCR02</td>
                <td class="p-3 font-semibold text-slate-900">Đăng nhập Hệ thống</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-medium">Auth</span></td>
                <td class="p-3">Thành viên / BĐH / Admin</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/auth/login.php</td>
                <td class="p-3 text-slate-600">Figma 01: Nhập Email + Mật khẩu, điều hướng đúng phân hệ theo vai trò tài khoản.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50 bg-indigo-50/20">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR03</td>
                <td class="p-3 font-semibold text-slate-900">Cổng Ứng Tuyển Tuyển Quân CLB</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Recruitment</span></td>
                <td class="p-3">Sinh viên UEH ứng tuyển</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/recruitment/apply.php</td>
                <td class="p-3 text-slate-600">Form nộp đơn tuyển quân: Họ tên, MSSV, SĐT, Email UEH, Khóa, chọn Phòng ban, upload CV PDF.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-slate-700">SCR04</td>
                <td class="p-3 font-semibold text-slate-900">Giới thiệu CLB BELL & 4 Phòng Ban</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium">Public</span></td>
                <td class="p-3">Tất cả (Khách/SV)</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/club/info.php</td>
                <td class="p-3 text-slate-600">Công khai thông tin BELL Club, sứ mệnh, cơ cấu 4 phòng ban & 10 cán bộ lãnh đạo. Không có nút sửa lặp.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-slate-700">SCR05</td>
                <td class="p-3 font-semibold text-slate-900">Danh mục Sự kiện Công khai</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium">Public</span></td>
                <td class="p-3">Tất cả (Khách/SV)</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/index.php</td>
                <td class="p-3 text-slate-600">Figma 02: Danh sách sự kiện, bộ lọc 7 trạng thái, tìm kiếm AJAX realtime, ẩn các sự kiện đã kết thúc/hủy.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-slate-700">SCR06</td>
                <td class="p-3 font-semibold text-slate-900">Chi tiết Sự kiện & Đăng ký Khách vãng lai</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium">Public</span></td>
                <td class="p-3">Tất cả (Khách/SV)</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/detail.php</td>
                <td class="p-3 text-slate-600">Figma 03, 04: Xem chi tiết sự kiện, đăng ký 1-click cho thành viên HOẶC mở modal form cho khách vãng lai.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50 bg-teal-50/20">
                <td class="p-3 font-mono font-bold text-teal-700">SCR07</td>
                <td class="p-3 font-semibold text-slate-900">Lịch Sự Kiện Đầy Đủ Notion Calendar</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 font-medium">Public / Workspace</span></td>
                <td class="p-3">Tất cả người dùng</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/calendar.php</td>
                <td class="p-3 text-slate-600">UAT 18: Toggle linh hoạt Lịch Tháng (Event pills) và Lịch Tuần (Timeline Notion 07:00-22:00 có vạch đỏ).</td>
              </tr>

              <!-- WORKSPACE PORTAL -->
              <tr class="screen-row hover:bg-slate-50 bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR08</td>
                <td class="p-3 font-semibold text-slate-900">Khung Giao Diện Workspace (Left-Sidebar)</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">BĐH & Thành viên CLB</td>
                <td class="p-3 font-mono text-slate-600">/app/includes/workspace_layout.php</td>
                <td class="p-3 text-slate-600">UAT 20: Thanh điều hướng cố định bên trái (Left-Sidebar), tối ưu thao tác nhanh gọn cho cán bộ CLB.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR09</td>
                <td class="p-3 font-semibold text-slate-900">Workspace Dashboard BĐH</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">BĐH CLB (President/Heads)</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/dashboard/index.php</td>
                <td class="p-3 text-slate-600">Thống kê chỉ số: Tổng thành viên, sự kiện đang mở, tỷ lệ tham gia, widget lịch mini dot.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR10</td>
                <td class="p-3 font-semibold text-slate-900">Quản lý Danh sách Sự kiện CLB</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">BĐH CLB</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/manage.php</td>
                <td class="p-3 text-slate-600">Danh sách quản trị sự kiện, lọc 7 trạng thái, nút chuyển đổi trạng thái nhanh, link vào Event Console.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR11</td>
                <td class="p-3 font-semibold text-slate-900">Tạo / Chỉnh sửa Sự kiện</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">BĐH CLB</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/form.php</td>
                <td class="p-3 text-slate-600">UAT 10 & 19: Form nhập giờ mở ĐK, kiểm tra ràng buộc bắt buộc chỉ định tối thiểu 2 cán bộ BĐH.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50 bg-teal-50/30">
                <td class="p-3 font-mono font-bold text-teal-700">SCR12</td>
                <td class="p-3 font-semibold text-teal-950 font-bold">Unified Event Console (Trung Tâm Điều Hành)</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 font-medium">Workspace</span></td>
                <td class="p-3">BĐH & Cán bộ Event Staff</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/console.php</td>
                <td class="p-3 text-slate-600">UAT 14, 15, 16: Hợp nhất 4 tabs: Tổng quan/Sửa, Điểm danh Realtime Polling 3s, Phân công BTC & Báo cáo số liệu.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR13</td>
                <td class="p-3 font-semibold text-slate-900">Sự kiện Của Tôi (2 Tabs)</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">Thành viên & Cán bộ</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/events/my_events.php</td>
                <td class="p-3 text-slate-600">UAT 10: Tách rõ 2 tab: "Sự kiện tôi điều hành/hỗ trợ" (Event_Staff) vs. "Sự kiện tôi tham gia" (Participant).</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR14</td>
                <td class="p-3 font-semibold text-slate-900">Quản lý Nhân sự & Cơ cấu Phòng Ban</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">BĐH & Trưởng phòng HR</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/members/index.php</td>
                <td class="p-3 text-slate-600">UAT 05 & 07: Danh sách thành viên phân nhóm 4 phòng ban, tìm kiếm AJAX tức thời không độ trễ debounce.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50 bg-indigo-50/30">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR15</td>
                <td class="p-3 font-semibold text-indigo-950 font-bold">Quản lý Tuyển Quân & Duyệt Cấp Tài Khoản</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">Trưởng phòng HR & President</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/recruitment/manage.php</td>
                <td class="p-3 text-slate-600">UAT 02 & 09: Xem CV, đặt lịch phỏng vấn, bấm Duyệt hồ sơ &rarr; Tự động kích hoạt tài khoản Ueh@&lt;MSSV&gt;.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-indigo-700">SCR16</td>
                <td class="p-3 font-semibold text-slate-900">Quản lý Hồ sơ & Thông tin CLB</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-medium">Workspace</span></td>
                <td class="p-3">President & Vice President</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/club/manage.php</td>
                <td class="p-3 text-slate-600">UAT 06 & 13: Chỉnh sửa tầm nhìn, sứ mệnh, link fanpage. Chỉ hiển thị cho Chủ nhiệm/Phó Chủ nhiệm.</td>
              </tr>
              <tr class="screen-row hover:bg-slate-50">
                <td class="p-3 font-mono font-bold text-slate-700">SCR17</td>
                <td class="p-3 font-semibold text-slate-900">Hồ sơ Cá nhân & Đổi mật khẩu</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-medium">Account</span></td>
                <td class="p-3">Tất cả tài khoản đăng nhập</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/auth/profile.php</td>
                <td class="p-3 text-slate-600">UAT 12: MSSV bị khóa readonly; bắt buộc đổi mật khẩu cho tân thành viên khởi tạo từ tuyển quân.</td>
              </tr>

              <!-- SYSTEM ADMIN -->
              <tr class="screen-row hover:bg-slate-50 bg-purple-50/20">
                <td class="p-3 font-mono font-bold text-purple-700">SCR18</td>
                <td class="p-3 font-semibold text-purple-950 font-bold">Quản trị Hệ thống & Giám sát Tài khoản</td>
                <td class="p-3"><span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 font-medium">Admin</span></td>
                <td class="p-3">System Administrator</td>
                <td class="p-3 font-mono text-slate-600">/app/modules/admin/index.php</td>
                <td class="p-3 text-slate-600">UAT 09: Quản lý toàn bộ danh sách Account, phân quyền tầng 1, giám sát trạng thái DB và logs hệ thống.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
"""
