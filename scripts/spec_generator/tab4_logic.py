# -*- coding: utf-8 -*-
"""
Tab 4: Luồng Nghiệp Vụ & Ràng Buộc Kỹ Thuật (5 Luồng Cốt Lõi)
"""

def get_tab4():
    return """
    <!-- ==========================================
         TAB 4: LUỒNG NGHIỆP VỤ & RÀNG BUỘC KỸ THUẬT
         ========================================== -->
    <section id="tab-logic" class="tab-content space-y-6">
      
      <!-- FLOW 1: EVENT STATE MACHINE -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
          <span class="text-xs font-bold uppercase px-2.5 py-1 rounded bg-teal-100 text-teal-800">Luồng Nghiệp Vụ 1 · UAT 19</span>
          <h3 class="text-base font-bold text-slate-900 mt-2">Máy Trạng Thái Sự Kiện 7 Bước (Realtime Event State Machine)</h3>
          <p class="text-xs text-slate-600 mt-1">
            Mọi sự kiện trong hệ thống vận hành theo vòng đời 7 trạng thái chuẩn hóa, kết hợp giữa kích hoạt thủ công của Ban Điều Hành và tự động cập nhật theo mốc thời gian thực:
          </p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 text-center text-xs">
          <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
            <span class="font-bold font-mono text-slate-700 block mb-1">1. draft</span>
            <span class="text-[11px] text-slate-500">Bản nháp nội bộ, chưa công khai</span>
          </div>
          <div class="p-2.5 rounded-lg border border-blue-200 bg-blue-50">
            <span class="font-bold font-mono text-blue-700 block mb-1">2. upcoming</span>
            <span class="text-[11px] text-blue-600">Sắp mở đăng ký (Hiện Countdown)</span>
          </div>
          <div class="p-2.5 rounded-lg border border-emerald-200 bg-emerald-50">
            <span class="font-bold font-mono text-emerald-700 block mb-1">3. open</span>
            <span class="text-[11px] text-emerald-600">Đang mở đăng ký cho SV/Khách</span>
          </div>
          <div class="p-2.5 rounded-lg border border-amber-200 bg-amber-50">
            <span class="font-bold font-mono text-amber-700 block mb-1">4. closed</span>
            <span class="text-[11px] text-amber-600">Hết hạn ĐK hoặc đầy chỗ</span>
          </div>
          <div class="p-2.5 rounded-lg border border-pink-200 bg-pink-50">
            <span class="font-bold font-mono text-pink-700 block mb-1">5. ongoing</span>
            <span class="text-[11px] text-pink-600">Đang diễn ra (Mở bàn Check-in)</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-100">
            <span class="font-bold font-mono text-slate-600 block mb-1">6. finished</span>
            <span class="text-[11px] text-slate-500">Đã kết thúc, tổng kết số liệu</span>
          </div>
          <div class="p-2.5 rounded-lg border border-rose-200 bg-rose-50">
            <span class="font-bold font-mono text-rose-700 block mb-1">7. cancelled</span>
            <span class="text-[11px] text-rose-600">Đã hủy, khóa toàn bộ thao tác</span>
          </div>
        </div>

        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs">
          <p class="font-bold text-slate-700 mb-1">Quy tắc Chuyển Trạng Thái (State Transition Rules):</p>
          <ul class="list-disc list-inside space-y-0.5 text-slate-600">
            <li>Sự kiện chỉ được chuyển từ <code>draft</code> sang <code>upcoming</code> khi đã có đủ tối thiểu <strong>2 Cán bộ BĐH</strong> trong <code>Event_Staff</code>.</li>
            <li>Khi đến <code>RegistrationStartTime</code>, hệ thống tự động kích hoạt trạng thái <code>open</code>.</li>
            <li>Khi đến <code>RegistrationDeadline</code> HOẶC <code>CurrentParticipants &gt;= MaxCapacity</code>, chuyển thành <code>closed</code>.</li>
            <li>Khi đến <code>StartTime</code> của sự kiện, chuyển thành <code>ongoing</code>. Kết thúc <code>EndTime</code>, chuyển thành <code>finished</code>.</li>
          </ul>
        </div>
      </div>

      <!-- FLOW 2: RECRUITMENT PIPELINE & AUTO ACCOUNT CREATION -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div>
          <span class="text-xs font-bold uppercase px-2.5 py-1 rounded bg-indigo-100 text-indigo-800">Luồng Nghiệp Vụ 2 · UAT 02 & 09</span>
          <h3 class="text-base font-bold text-slate-900 mt-2">Quy Trình Tuyển Quân & Cấp Tài Khoản Tự Động Cho Tân Thành Viên</h3>
          <p class="text-xs text-slate-600 mt-1">
            Thay thế hoàn toàn cơ chế đăng ký tự do sai thực tế bằng phễu tuyển dụng bài bản của CLB sinh viên UEH:
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
            <span class="font-bold text-indigo-700 block mb-1">Bước 1: Mở Đợt Tuyển Quân</span>
            <p class="text-slate-600">Chủ nhiệm hoặc Trưởng phòng HR tạo chiến dịch tuyển sinh mới kèm thời hạn nộp đơn, mô tả yêu cầu 4 phòng ban.</p>
          </div>
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
            <span class="font-bold text-indigo-700 block mb-1">Bước 2: Nộp Hồ Sơ Online</span>
            <p class="text-slate-600">Ứng viên điền Họ tên, MSSV, SĐT, Email UEH, Khóa (K48-K52), Chuyên ngành, chọn Phòng ban và tải file CV định dạng PDF.</p>
          </div>
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
            <span class="font-bold text-indigo-700 block mb-1">Bước 3: Lọc Hồ Sơ & Phỏng Vấn</span>
            <p class="text-slate-600">HR xem CV, chấm điểm sơ khảo, cập nhật lịch hẹn phỏng vấn (Interview Date) và ghi chú đánh giá năng lực ứng viên.</p>
          </div>
          <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
            <span class="font-bold text-emerald-800 block mb-1">Bước 4: Duyệt & Sinh Tài Khoản</span>
            <p class="text-emerald-700">Trưởng phòng HR bấm "Phê duyệt" (Approve) &rarr; Hệ thống tự động kích hoạt Transaction cấp tài khoản đăng nhập.</p>
          </div>
        </div>

        <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-4 text-xs space-y-2">
          <p class="font-bold text-indigo-950">Công Thức Cấp Tài Khoản Tự Động Chuẩn UEH:</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 font-mono">
            <div class="bg-white p-2.5 rounded border border-indigo-200">
              <span class="text-slate-500 block text-[11px]">Tên đăng nhập (Username):</span>
              <strong class="text-indigo-700">Email ứng viên đã đăng ký (VD: 31241020055@st.ueh.edu.vn)</strong>
            </div>
            <div class="bg-white p-2.5 rounded border border-indigo-200">
              <span class="text-slate-500 block text-[11px]">Mật khẩu mặc định khởi tạo:</span>
              <strong class="text-emerald-700">Ueh@&lt;MSSV_Đã_Đăng_Ký&gt; (VD: Ueh@31241020055)</strong>
            </div>
          </div>
          <p class="text-slate-600 text-[11px]">
            * Lưu ý bảo mật: Tài khoản tạo mới được gắn cờ <code class="bg-white px-1 py-0.5 rounded text-indigo-700 font-mono">PasswordMustChange = 1</code>. Khi đăng nhập lần đầu, hệ thống bắt buộc người dùng chuyển hướng vào trang Đổi Mật Khẩu mới trước khi tiếp tục thao tác.
          </p>
        </div>
      </div>

      <!-- FLOW 3 & 4: GUEST REGISTRATION & STAFFING CONSOLE -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Flow 3: Guest & Member Registration -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
          <span class="text-xs font-bold uppercase px-2.5 py-1 rounded bg-blue-100 text-blue-800">Luồng Nghiệp Vụ 3 · UAT 02</span>
          <h3 class="text-base font-bold text-slate-900">Đăng Ký Sự Kiện: Thành Viên & Khách Vãng Lai</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Cho phép khách vãng lai (chưa có tài khoản) đăng ký sự kiện công khai với form tối giản: <strong>Họ tên, MSSV, SĐT, Email</strong>. Kiểm tra chống trùng theo cặp <code>(EventID, MSSV)</code> và bảo vệ giới hạn sức chứa với Transaction khóa dòng <code class="bg-slate-100 px-1 py-0.5 rounded text-blue-800 font-mono">SELECT ... FOR UPDATE</code>.
          </p>
          <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs font-mono space-y-1">
            <p class="text-slate-500">// Pseudo-code Transaction bảo vệ quota</p>
            <p>$pdo->beginTransaction();</p>
            <p>$stmt = $pdo->prepare("SELECT CurrentParticipants, MaxCapacity FROM Event WHERE EventID = ? FOR UPDATE");</p>
            <p>if ($event['CurrentParticipants'] &gt;= $event['MaxCapacity']) { rollback(); throw Exception('Hết chỗ'); }</p>
            <p>$pdo->commit();</p>
          </div>
        </div>

        <!-- Flow 4: Event Staffing & Min 2 Board Members -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
          <span class="text-xs font-bold uppercase px-2.5 py-1 rounded bg-teal-100 text-teal-800">Luồng Nghiệp Vụ 4 · UAT 08, 10 & 11</span>
          <h3 class="text-base font-bold text-slate-900">Phân Công Cán Bộ & Ràng Buộc Tối Thiểu 2 BĐH</h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Để đảm bảo tính trách nhiệm và quy chuẩn tổ chức sự kiện tại UEH, một sự kiện khi tạo ra <strong>bắt buộc phải được phân công tối thiểu 2 cán bộ thuộc Ban Điều Hành / Lãnh đạo Phòng ban</strong> vào danh sách ban tổ chức (<code class="font-mono text-teal-700">Event_Staff</code>).
          </p>
          <div class="bg-teal-50/60 p-3 rounded-lg border border-teal-200 text-xs space-y-1.5">
            <p class="font-bold text-teal-900">Quyền Hạn Điểm Danh Sự Kiện (Check-in Authority):</p>
            <p class="text-teal-800">
              • Chỉ cán bộ được chỉ định trong bảng <code class="font-mono bg-white px-1 py-0.5 rounded">Event_Staff</code> của đúng sự kiện đó mới được cấp quyền mở giao diện điểm danh và thực hiện check-in sinh viên.
              <br/>• Quản lý CLB (President/VP) có thể bổ nhiệm hoặc thu hồi quyền cán bộ ngay tại Event Console.
            </p>
          </div>
        </div>
      </div>

      <!-- FLOW 5: REALTIME ATTENDANCE CHECK-IN SYNC -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
        <span class="text-xs font-bold uppercase px-2.5 py-1 rounded bg-emerald-100 text-emerald-800">Luồng Nghiệp Vụ 5 · UAT 17</span>
        <h3 class="text-base font-bold text-slate-900">Điểm Danh Realtime Tức Thì & Đồng Bộ Nhiều Bàn Check-in (Multi-Staff Sync)</h3>
        <p class="text-xs text-slate-600 leading-relaxed">
          Kế thừa hoàn thiện từ UAT 17: Cán bộ bấm "Điểm danh" &rarr; Giao diện cập nhật ngay lập tức (Zero-latency Optimistic DOM update) &rarr; Gửi AJAX ngầm &rarr; Nền tảng chạy Polling ngầm mỗi 3 giây đồng bộ số liệu giữa nhiều máy bàn check-in cùng lúc tại cổng hội trường mà không gây xung đột session nhờ <code class="font-mono text-emerald-800 bg-emerald-50 px-1 py-0.5 rounded">session_write_close()</code>.
        </p>
      </div>
    </section>
"""
