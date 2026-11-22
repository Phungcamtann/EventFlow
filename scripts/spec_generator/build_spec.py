# -*- coding: utf-8 -*-
"""
Main Builder for docs/eventflow_vibe_code_design_specification.html
"""
from gen_spec_parts import get_head, get_header_nav
from tab1_overview import get_tab1
from tab2_database import get_tab2
from tab3_rbac import get_tab3
from tab4_logic import get_tab4
from tab5_screens import get_tab5
from tab6_vibecode import get_tab6

def get_footer_and_scripts():
    return """
  </main>

  <!-- TOAST NOTIFICATION -->
  <div id="toast" class="fixed bottom-6 right-6 hidden items-center gap-2 bg-emerald-600 text-white px-4 py-2.5 rounded-xl shadow-lg text-xs font-semibold z-50 transition transform">
    <span>✅</span>
    <span>Đã sao chép mã nguồn vào clipboard!</span>
  </div>

  <!-- FOOTER -->
  <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800 text-center text-xs mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-1">
      <p class="font-semibold text-slate-300">
        EventFlow System Architecture Blueprint & Vibe Code Specification v2.0
      </p>
      <p class="text-slate-500">
        Dự án Quản lý Hoạt động & Nhân sự CLB Tiếng Anh Sinh Viên UEH (BELL CLUB) · Nhóm 8 · Sprint 7 Baseline
      </p>
    </div>
  </footer>

  <!-- JAVASCRIPT LOGIC -->
  <script>
    function switchTab(tabId) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('border-teal-400', 'text-teal-300', 'font-semibold');
        btn.classList.add('border-transparent', 'text-slate-300', 'font-medium');
      });

      const target = document.getElementById(tabId);
      if (target) {
        target.classList.add('active');
      }
      const activeBtn = document.getElementById('btn-' + tabId);
      if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-slate-300', 'font-medium');
        activeBtn.classList.add('border-teal-400', 'text-teal-300', 'font-semibold');
      }
    }

    function copyCode(elementId) {
      const el = document.getElementById(elementId);
      if (!el) return;
      const text = el.innerText;
      navigator.clipboard.writeText(text).then(() => {
        const toast = document.getElementById('toast');
        toast.classList.remove('hidden');
        toast.classList.add('flex');
        setTimeout(() => {
          toast.classList.add('hidden');
          toast.classList.remove('flex');
        }, 2500);
      });
    }

    function filterScreens() {
      const q = document.getElementById('screen-search').value.toLowerCase();
      document.querySelectorAll('.screen-row').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    }
  </script>
</body>
</html>
"""

def main():
    print("Building Blueprint v2.0 HTML document...")
    html_content = "".join([
        get_head(),
        get_header_nav(),
        get_tab1(),
        get_tab2(),
        get_tab3(),
        get_tab4(),
        get_tab5(),
        get_tab6(),
        get_footer_and_scripts()
    ])

    out_path = "docs/eventflow_vibe_code_design_specification.html"
    with open(out_path, "w", encoding="utf-8") as f:
        f.write(html_content)

    print(f"Successfully generated {out_path} ({len(html_content)} characters)!")

if __name__ == "__main__":
    main()
