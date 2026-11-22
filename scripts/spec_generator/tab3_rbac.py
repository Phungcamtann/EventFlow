# -*- coding: utf-8 -*-
"""
Tab 3: Ma Trận Phân Quyền 3 Tầng (3-Tier RBAC Architecture)
"""

def get_tab3():
    return """
    <!-- ==========================================
         TAB 3: MA TRẬN PHÂN QUYỀN 3 TẦNG (3-TIER RBAC)
         ========================================== -->
    <section id="tab-rbac" class="tab-content space-y-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2 flex items-center gap-2">
          <span class="w-2.5 h-6 bg-teal-500 rounded-sm inline-block"></span>
          Kiến Trúc Phân Quyền 3 Tầng (3-Tier Multi-Level RBAC Matrix)
        </h2>
        <p class="text-xs sm:text-sm text-slate-600 mb-6">
          Khắc phục hoàn toàn lỗ hổng cấp quyền toàn cục (Global Supporter) của phiên bản trước. Quyền hạn trong EventFlow v2.0 được thẩm định độc lập qua 3 tầng phân cấp: <strong>Tầng 1 (Hệ thống) &rarr; Tầng 2 (CLB & Phòng ban) &rarr; Tầng 3 (Theo từng Sự kiện)</strong>.
        </p>

        <!-- 3-Tier Definition Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-xs">
          <div class="bg-purple-50/60 border border-purple-200 rounded-xl p-4">
            <span class="px-2 py-0.5 bg-purple-600 text-white rounded font-bold uppercase tracking-wider text-[10px]">Tầng 1 · System Role</span>
            <h4 class="font-bold text-purple-950 text-sm mt-2 mb-1">Xác thực Hệ Thống</h4>
            <p class="text-purple-900 leading-relaxed">
              Lưu tại bảng <code class="font-mono text-purple-700">Account.RoleID</code>:
              <br/>• <strong>admin:</strong> Giám sát hạ tầng, quản lý tài khoản & audit log.
              <br/>• <strong>clubmanager:</strong> Cấp cho President & Vice President.
              <br/>• <strong>clubmember:</strong> Thành viên chính thức trong CLB.
              <br/>• <strong>participant:</strong> Sinh viên/Khách vãng lai tham gia.
            </p>
          </div>

          <div class="bg-blue-50/60 border border-blue-200 rounded-xl p-4">
            <span class="px-2 py-0.5 bg-blue-600 text-white rounded font-bold uppercase tracking-wider text-[10px]">Tầng 2 · Club & Department</span>
            <h4 class="font-bold text-blue-950 text-sm mt-2 mb-1">Cơ Cấu CLB & Phòng Ban</h4>
            <p class="text-blue-900 leading-relaxed">
              Lưu tại bảng <code class="font-mono text-blue-700">Club_Member.MemberRole & DeptID</code>:
              <br/>• <strong>President & VP:</strong> Toàn quyền CLB, duyệt sửa hồ sơ CLB.
              <br/>• <strong>Head / Vice Head HR:</strong> Chủ trì tuyển quân, phỏng vấn, <strong>Duyệt cấp tài khoản</strong>.
              <br/>• <strong>Head / Vice Head khác:</strong> Điều phối học thuật, truyền thông, đối ngoại.
              <br/>• <strong>Member:</strong> Tham gia sinh hoạt chuyên môn.
            </p>
          </div>

          <div class="bg-emerald-50/60 border border-emerald-200 rounded-xl p-4">
            <span class="px-2 py-0.5 bg-emerald-600 text-white rounded font-bold uppercase tracking-wider text-[10px]">Tầng 3 · Event-Scoped</span>
            <h4 class="font-bold text-emerald-950 text-sm mt-2 mb-1">Phân Công Cán Bộ Sự Kiện</h4>
            <p class="text-emerald-900 leading-relaxed">
              Lưu tại bảng <code class="font-mono text-emerald-700">Event_Staff (EventID, AccountID)</code>:
              <br/>• <strong>Organizer:</strong> Trưởng BTC sự kiện, điều phối chung.
              <br/>• <strong>Checkin_Lead & Support:</strong> Phụ trách đối chiếu thông tin và <strong>thực hiện điểm danh sự kiện</strong>.
              <br/>• <strong>Ràng buộc bảo mật:</strong> Chỉ cán bộ có tên trong danh sách BTC của sự kiện mới được mở bàn điểm danh.
            </p>
          </div>
        </div>

        <!-- Matrix Table -->
        <div class="overflow-x-auto border border-slate-200 rounded-xl mb-6">
          <table class="w-full text-xs text-left">
            <thead class="bg-slate-100 text-slate-700 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="p-3">Hành Động / Tính Năng</th>
                <th class="p-3 text-center">System Admin</th>
                <th class="p-3 text-center">President / VP</th>
                <th class="p-3 text-center">Head of HR</th>
                <th class="p-3 text-center">Dept Heads</th>
                <th class="p-3 text-center">CLB Member</th>
                <th class="p-3 text-center">Event Staff</th>
                <th class="p-3 text-center">Guest / Sinh viên</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr class="hover:bg-slate-50">
                <td class="p-3 font-medium">Xem Trang chủ, Thông tin CLB & Lịch công khai</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Không cần login)</td>
              </tr>
              <tr class="hover:bg-slate-50">
                <td class="p-3 font-medium">Đăng ký tham gia Sự kiện mở</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (1 click)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Form vãng lai)</td>
              </tr>
              <tr class="hover:bg-slate-50">
                <td class="p-3 font-medium">Nộp đơn ứng tuyển CLB (Tuyển quân)</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Nộp CV PDF)</td>
              </tr>
              <tr class="hover:bg-slate-50 bg-slate-50/50">
                <td class="p-3 font-medium">Truy cập Không gian Quản trị (Workspace Sidebar)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Admin Portal)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Toàn quyền)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (HR + SK)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Phòng ban + SK)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Member Area)</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗ Chặn 403</td>
              </tr>
              <tr class="hover:bg-slate-50">
                <td class="p-3 font-medium">Tạo & Chỉnh sửa Sự kiện (Yêu cầu min 2 BĐH)</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
              <tr class="hover:bg-slate-50 bg-teal-50/30">
                <td class="p-3 font-medium text-teal-900 font-semibold">Phân công BTC & Cán bộ hỗ trợ (Event_Staff)</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
              <tr class="hover:bg-slate-50 bg-teal-50/50">
                <td class="p-3 font-medium text-teal-900 font-bold">Mở bàn Điểm danh sự kiện (Check-in Console)</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-slate-400">Nếu được phân công</td>
                <td class="p-3 text-center text-slate-400">Nếu được phân công</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Chỉ sự kiện phân công)</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
              <tr class="hover:bg-slate-50 bg-indigo-50/40">
                <td class="p-3 font-medium text-indigo-900 font-semibold">Quản lý Đợt tuyển quân & Xem CV ứng viên</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Chủ trì HR)</td>
                <td class="p-3 text-center text-slate-400">Xem UV phòng mình</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
              <tr class="hover:bg-slate-50 bg-indigo-50/60">
                <td class="p-3 font-medium text-indigo-950 font-bold">Bấm Duyệt hồ sơ & Tự động Cấp tài khoản</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Trưởng phòng HR)</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
              <tr class="hover:bg-slate-50">
                <td class="p-3 font-medium">Chỉnh sửa Thông tin & Hồ sơ CLB</td>
                <td class="p-3 text-center text-slate-300">—</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (Chủ nhiệm/Phó CN)</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
              <tr class="hover:bg-slate-50 bg-purple-50/40">
                <td class="p-3 font-medium text-purple-950 font-semibold">Quản lý Tài khoản Hệ thống & System Health</td>
                <td class="p-3 text-center text-emerald-600 font-bold">✓ (System Admin)</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
                <td class="p-3 text-center text-rose-500 font-bold">✗</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Middleware PHP Code Implementation Guardrails -->
        <div>
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-2">Đặc Tả Code Hàm Kiểm Tra Quyền (Middleware Helper)</h3>
          <pre class="bg-slate-900 text-slate-100 p-4 rounded-xl text-xs overflow-x-auto leading-relaxed"><code class="language-php">&lt;?php
// /app/includes/auth_guard.php - EventFlow 3-Tier RBAC Guardrail

/**
 * Tầng 1: Kiểm tra đăng nhập bắt buộc
 */
function require_auth(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['account_id'])) {
        header('Location: /app/modules/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    // Ràng buộc tân thành viên phải đổi mật khẩu lần đầu (Ueh@<MSSV>)
    if (!empty($_SESSION['password_must_change']) && basename($_SERVER['PHP_SELF']) !== 'profile.php' && basename($_SERVER['PHP_SELF']) !== 'logout.php') {
        header('Location: /app/modules/auth/profile.php?notice=password_must_change');
        exit;
    }
    return $_SESSION['user'];
}

/**
 * Tầng 2: Kiểm tra chức danh Ban Điều Hành / Trưởng Phòng HR
 */
function require_club_lead(string $specificDept = null): bool {
    $user = require_auth();
    // System Admin hoặc President/Vice President luôn có quyền
    if ($user['role_name'] === 'admin' || in_array($user['member_role'] ?? '', ['President', 'Vice President'])) {
        return true;
    }
    // Trưởng phòng HR thao tác module tuyển quân
    if ($specificDept === 'HR' && ($user['dept_code'] ?? '') === 'HR' && in_array($user['member_role'] ?? '', ['Head', 'Vice Head'])) {
        return true;
    }
    http_response_code(403);
    include __DIR__ . '/../views/errors/403.php';
    exit;
}

/**
 * Tầng 3: Kiểm tra quyền điểm danh / điều hành theo từng Sự Kiện cụ thể
 */
function has_event_staff_perm(PDO $pdo, int $eventId, int $accountId): bool {
    // 1. Kiểm tra nếu là President hoặc Vice President
    $stmt = $pdo->prepare("SELECT MemberRole FROM Club_Member WHERE AccountID = ? AND Status = 'Active'");
    $stmt->execute([$accountId]);
    $role = $stmt->fetchColumn();
    if (in_array($role, ['President', 'Vice President'])) {
        return true;
    }
    // 2. Kiểm tra nếu có phân công chính thức trong Event_Staff
    $stmt = $pdo->prepare("SELECT 1 FROM Event_Staff WHERE EventID = ? AND AccountID = ?");
    $stmt->execute([$eventId, $accountId]);
    return (bool)$stmt->fetchColumn();
}
?&gt;</code></pre>
        </div>
      </div>
    </section>
"""
