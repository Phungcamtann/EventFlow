# -*- coding: utf-8 -*-
"""
Tab 1: Tổng Quan & Kiến Trúc Phân Hệ Kép (Dual-Portal System Architecture)
"""

def get_tab1():
    return """
    <!-- ==========================================
         TAB 1: TỔNG QUAN & KIẾN TRÚC PHÂN HỆ KÉP
         ========================================== -->
    <section id="tab-overview" class="tab-content active space-y-6">
      <!-- Guardrail Alert -->
      <div class="bg-gradient-to-r from-teal-50 to-emerald-50 border-l-4 border-teal-600 p-4 rounded-r-xl shadow-sm">
        <div class="flex items-start gap-3">
          <div class="text-teal-700 font-bold text-xl">🛡️</div>
          <div>
            <h3 class="text-sm font-bold text-teal-950 uppercase tracking-wider">Nguyên tắc Thiết kế Chuẩn Hóa Blueprint v2.0 (Sprint 7 Baseline)</h3>
            <p class="text-sm text-teal-900 mt-1 leading-relaxed">
              Tài liệu kỹ thuật v2.0 được tái cấu trúc toàn diện dựa trên <strong>Hồ sơ Quản lý Dự án Sprint 7</strong> và <strong>Dữ liệu thực tế CLB Tiếng Anh UEH (BELL Club)</strong>. Hệ thống giải quyết trọn vẹn 20 User Stories/UAT, phân tách rõ <strong>Cổng thông tin công chúng (Public Portal)</strong> và <strong>Không gian làm việc điều hành (Workspace Portal)</strong>, chuẩn hóa quy trình tuyển quân phỏng vấn cấp tài khoản tự động, bổ sung cấu trúc 4 phòng ban và giao diện lịch tương tác Notion Calendar.
            </p>
          </div>
        </div>
      </div>

      <!-- High-level stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow transition">
          <p class="text-xs font-semibold text-slate-500 uppercase">Phạm vi UAT Sprint 7</p>
          <p class="text-2xl font-extrabold text-teal-600 mt-1">20 Use Cases</p>
          <p class="text-xs text-slate-500 mt-1">7 Hoàn thành · 13 Đang tinh chỉnh v2</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow transition">
          <p class="text-xs font-semibold text-slate-500 uppercase">Mô hình Dữ liệu (3NF)</p>
          <p class="text-2xl font-extrabold text-indigo-600 mt-1">11 Bảng Cốt Lõi</p>
          <p class="text-xs text-slate-500 mt-1">+4 Bảng mới: Dept, Staff, Recruits</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow transition">
          <p class="text-xs font-semibold text-slate-500 uppercase">Cơ cấu Tổ chức CLB</p>
          <p class="text-2xl font-extrabold text-emerald-600 mt-1">04 Phòng Ban</p>
          <p class="text-xs text-slate-500 mt-1">HR · Specialist · Coms · External Rel</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow transition">
          <p class="text-xs font-semibold text-slate-500 uppercase">Kiến trúc Giao diện</p>
          <p class="text-2xl font-extrabold text-amber-600 mt-1">Dual-Portal</p>
          <p class="text-xs text-slate-500 mt-1">Public Top-nav & Workspace Left-Sidebar</p>
        </div>
      </div>

      <!-- Status of Sprint 7 UATs Banner -->
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-3 flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          Báo cáo Tiến độ Sprint 7: Trạng thái 20 Yêu cầu Chấp nhận Người dùng (UAT)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <div class="bg-emerald-50/70 border border-emerald-200 rounded-lg p-3">
            <p class="font-bold text-emerald-900 mb-1.5 flex items-center gap-1.5">
              <span>✅ 07 UAT Đã Hoàn Thành & Xác Minh Git Sprint-7:</span>
            </p>
            <ul class="space-y-1 text-emerald-800 list-disc list-inside">
              <li><strong>UAT 01:</strong> Fix lỗi ngắt dòng subtitle tiêu đề & padding auth mobile (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit 9d57c39</code>).</li>
              <li><strong>UAT 03:</strong> Lọc ẩn sự kiện đã kết thúc/hủy trên trang chủ (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit 0e1e431, 7ba3769</code>).</li>
              <li><strong>UAT 04:</strong> Navbar mobile responsive, touch targets đạt chuẩn &ge; 44px (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit 9f2cd57</code>).</li>
              <li><strong>UAT 07:</strong> Tìm kiếm thành viên AJAX thời gian thực không độ trễ debounce (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit 16dbd8b</code>).</li>
              <li><strong>UAT 12:</strong> Khóa trường MSSV thành readonly chống gian lận định danh (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit bfc5a74</code>).</li>
              <li><strong>UAT 17:</strong> Điểm danh cập nhật DOM tức thời & đồng bộ polling ngầm 3s (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit b75cea1</code>).</li>
              <li><strong>UAT 19:</strong> State Machine sự kiện 7 trạng thái realtime + giờ mở ĐK (<code class="bg-emerald-100 px-1 py-0.5 rounded">commit 722a078</code>).</li>
            </ul>
          </div>
          <div class="bg-indigo-50/70 border border-indigo-200 rounded-lg p-3">
            <p class="font-bold text-indigo-900 mb-1.5 flex items-center gap-1.5">
              <span>🚀 13 UAT Nâng Cấp Kỹ Thuật Lên Blueprint v2.0:</span>
            </p>
            <ul class="space-y-1 text-indigo-800 list-disc list-inside">
              <li><strong>UAT 02:</strong> Endpoint công khai, ĐK sự kiện vãng lai & Quy trình Ứng tuyển tuyển quân.</li>
              <li><strong>UAT 05:</strong> Cơ cấu chuẩn CLB BELL UEH: Ban điều hành & 4 phòng ban chuyên môn.</li>
              <li><strong>UAT 06 & 13:</strong> Thống nhất sitemap, xóa bỏ nút trùng lặp & menu chuyển trang chuẩn.</li>
              <li><strong>UAT 08 & 11:</strong> Bỏ vai trò toàn cục; phân công hỗ trợ & quyền điểm danh theo từng sự kiện.</li>
              <li><strong>UAT 09:</strong> Tách biệt System Admin vs Club Manager; Trưởng phòng HR duyệt cấp tài khoản.</li>
              <li><strong>UAT 10:</strong> Bắt buộc tối thiểu 2 BĐH khi tạo sự kiện; "Sự kiện của tôi" chia 2 tab quản trị/tham gia.</li>
              <li><strong>UAT 14, 15, 16:</strong> Gom Quản lý, Thống kê, Phân công & Điểm danh vào Unified Event Console.</li>
              <li><strong>UAT 18:</strong> Giao diện Lịch tương tác Notion Calendar (Lịch tháng pill + Lịch tuần timeline 07h-22h).</li>
              <li><strong>UAT 20:</strong> Bộ nhận diện BELL UEH, banner slider, sự kiện hot & Left-Sidebar workspace.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- DUAL-PORTAL ARCHITECTURE OVERVIEW -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="w-2.5 h-6 bg-teal-500 rounded-sm inline-block"></span>
            Mô Hình Kiến Trúc Phân Hệ Kép: Public Portal vs. Workspace Portal
          </h2>
          <p class="text-sm text-slate-600 mt-1">
            Giải quyết triệt để vấn đề phân mảnh trải nghiệm người dùng bằng cách chia rõ rệt 2 phân hệ chuyên biệt tương ứng với 2 nhóm đối tượng trọng tâm:
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Public Portal Card -->
          <div class="border border-blue-200 bg-blue-50/40 rounded-xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-1 rounded bg-blue-600 text-white font-bold text-xs uppercase tracking-wider">Phân Hệ 1 · Public Portal</span>
              <span class="text-xs text-blue-700 font-semibold">Dành cho Khách vãng lai & Sinh viên</span>
            </div>
            <h3 class="text-base font-bold text-slate-900">Cổng Thông Tin Công Chúng & Đăng Ký Trải Nghiệm</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Tập trung tối đa vào tốc độ tải trang, tính thẩm mỹ nhận diện thương hiệu BELL CLUB UEH, khả năng tương tác không rào cản và tỉ lệ chuyển đổi ứng tuyển/đăng ký.
            </p>
            <div class="bg-white rounded-lg p-3 border border-blue-100 text-xs space-y-2">
              <div class="flex items-center gap-2">
                <span class="font-bold text-blue-800 min-w-[90px]">Top Navigation:</span>
                <span class="text-slate-700">Trang chủ | Giới thiệu CLB | Sự kiện & Lịch | Ứng tuyển CTV | Đăng nhập</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-blue-800 min-w-[90px]">Trang chủ:</span>
                <span class="text-slate-700">Banner Slider hoạt động CLB, Khối Sự kiện Hot (4 cards nổi bật), Footer liên hệ & Fanpage</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-blue-800 min-w-[90px]">Đăng ký SK:</span>
                <span class="text-slate-700">Khách vãng lai đăng ký trực tiếp không cần đăng nhập (Họ tên, MSSV, SĐT, Email)</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-blue-800 min-w-[90px]">Ứng tuyển CLB:</span>
                <span class="text-slate-700">Form nộp đơn ứng tuyển theo đợt tuyển quân, chọn phòng ban, đính kèm CV PDF</span>
              </div>
            </div>
          </div>

          <!-- Workspace Portal Card -->
          <div class="border border-indigo-200 bg-indigo-50/40 rounded-xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-1 rounded bg-indigo-600 text-white font-bold text-xs uppercase tracking-wider">Phân Hệ 2 · Workspace Portal</span>
              <span class="text-xs text-indigo-700 font-semibold">Dành cho Ban Điều Hành & Thành Viên</span>
            </div>
            <h3 class="text-base font-bold text-slate-900">Không Gian Làm Việc & Điều Hành Hoạt Động Nội Bộ</h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              Tối ưu năng suất công việc với giao diện thanh điều hướng cố định bên trái (Left-Sidebar), bảng điều khiển quản trị tập trung (Event Console), phân quyền ma trận 3 tầng.
            </p>
            <div class="bg-white rounded-lg p-3 border border-indigo-100 text-xs space-y-2">
              <div class="flex items-center gap-2">
                <span class="font-bold text-indigo-800 min-w-[90px]">Left-Sidebar:</span>
                <span class="text-slate-700">Dashboard | Lịch Notion | Quản lý Sự kiện | Nhân sự & Phòng ban | Tuyển quân | Tài khoản</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-indigo-800 min-w-[90px]">Event Console:</span>
                <span class="text-slate-700">Hợp nhất Thông tin, Điểm danh Realtime, Phân công BTC (min 2 BĐH) & Thống kê vào 1 chỗ</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-indigo-800 min-w-[90px]">Cấp tài khoản:</span>
                <span class="text-slate-700">Trưởng phòng HR/Chủ nhiệm duyệt hồ sơ trúng tuyển &rarr; Hệ thống tự tạo tài khoản <code class="font-mono text-indigo-700">Ueh@&lt;MSSV&gt;</code></span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-bold text-indigo-800 min-w-[90px]">Điểm danh:</span>
                <span class="text-slate-700">Chỉ cấp quyền cho nhân sự được phân công theo từng sự kiện thông qua bảng <code class="font-mono text-indigo-700">Event_Staff</code></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3-TIER MONOLITH ARCHITECTURE -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
          <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="w-2.5 h-6 bg-teal-500 rounded-sm inline-block"></span>
            Kiến Trúc Kỹ Thuật 3 Lớp (Monolith MVC-Inspired Architecture)
          </h2>
          <p class="text-sm text-slate-600 mt-1">
            Ứng dụng mô hình kiến trúc Monolith 3 lớp gọn nhẹ, độc lập và bảo mật cao, đảm bảo vận hành ổn định trên môi trường Apache/PHP 8.2 + MySQL chuẩn tại UEH:
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
          <!-- Layer 1 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 flex flex-col justify-between">
            <div>
              <div class="w-7 h-7 rounded-lg bg-teal-500 text-slate-900 font-bold flex items-center justify-center mb-2">1</div>
              <h4 class="font-bold text-slate-900 text-sm mb-1">Presentation Layer (Giao diện)</h4>
              <p class="text-slate-600 leading-relaxed">
                Bootstrap 5.3 + Tailwind Utility + Vanilla ES6 JS. Hỗ trợ responsive đa kích thước màn hình (Mobile &ge; 44px touch target, Tablet, Desktop). Sử dụng kỹ thuật AJAX AbortController cho tìm kiếm thời gian thực và DOM optimistic updates cho điểm danh tức thì.
              </p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200 font-mono text-[11px] text-teal-800">
              Templates: /app/modules/*/views/*.php
            </div>
          </div>

          <!-- Layer 2 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 flex flex-col justify-between">
            <div>
              <div class="w-7 h-7 rounded-lg bg-indigo-500 text-white font-bold flex items-center justify-center mb-2">2</div>
              <h4 class="font-bold text-slate-900 text-sm mb-1">Application Layer (Nghiệp vụ)</h4>
              <p class="text-slate-600 leading-relaxed">
                PHP 8.2 Monolith với mô hình Service/Action. Xác thực qua PHP Native Session, CSRF Token chống tấn công giả mạo, Middleware RBAC 3 tầng phân định quyền hạn, giải phóng Session Lock bằng <code class="bg-indigo-100 px-1 py-0.5 rounded">session_write_close()</code> cho các tiến trình polling ngầm.
              </p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200 font-mono text-[11px] text-indigo-800">
              Services: /app/modules/*/services/*.php
            </div>
          </div>

          <!-- Layer 3 -->
          <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 flex flex-col justify-between">
            <div>
              <div class="w-7 h-7 rounded-lg bg-emerald-500 text-slate-900 font-bold flex items-center justify-center mb-2">3</div>
              <h4 class="font-bold text-slate-900 text-sm mb-1">Data Layer (Dữ liệu)</h4>
              <p class="text-slate-600 leading-relaxed">
                MySQL 8.0 InnoDB với 11 bảng quan hệ chuẩn hóa 3NF. Ràng buộc toàn vẹn khóa ngoại (Foreign Keys), cơ chế khóa dòng <code class="bg-emerald-100 px-1 py-0.5 rounded">SELECT ... FOR UPDATE</code> bảo vệ hạn ngạch chỗ đăng ký, kết nối qua PDO Prepared Statements chống SQL Injection 100%.
              </p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-200 font-mono text-[11px] text-emerald-800">
              Database: /app/database/connection.php
            </div>
          </div>
        </div>
      </div>
    </section>
"""
