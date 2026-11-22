# -*- coding: utf-8 -*-
"""
Script to generate docs/eventflow_vibe_code_design_specification.html (Version 2.0)
"""
import sys

def get_head():
    return """<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EventFlow – Tài Liệu Phân Tích & Thiết Kế Kỹ Thuật (Vibe Code Blueprint v2.0)</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    code, pre { font-family: 'JetBrains Mono', monospace; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f5f9; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .badge-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-upcoming { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-open { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-closed { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .badge-ongoing { background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; }
    .badge-finished { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
    .badge-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
  </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">
"""

def get_header_nav():
    return """
  <!-- TOP HEADER -->
  <header class="bg-slate-900 text-white border-b border-slate-800 sticky top-0 z-30 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-500 to-emerald-400 flex items-center justify-center font-extrabold text-xl text-slate-950 shadow-inner">
          EF
        </div>
        <div>
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-lg sm:text-xl font-bold tracking-tight">EventFlow – Vibe Code Blueprint & Architecture</h1>
            <span class="bg-teal-500/20 text-teal-300 border border-teal-500/40 text-xs font-semibold px-2.5 py-0.5 rounded-full">Blueprint v2.0 · Sprint 7</span>
            <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 text-xs font-semibold px-2 py-0.5 rounded-full">Dual-Portal Architecture</span>
          </div>
          <p class="text-xs text-slate-400 mt-0.5">
            Dự án: Hệ thống quản lý hoạt động & nhân sự <strong>BELL CLUB UEH</strong> (Nhóm 8 - Học kỳ cuối 2026)
          </p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2 text-xs">
        <span class="px-2.5 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700">Stack: <strong>PHP 8.2 + PDO + Session</strong></span>
        <span class="px-2.5 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700">DB: <strong>MySQL InnoDB (11 Bảng)</strong></span>
        <span class="px-2.5 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700">UI: <strong>Bootstrap 5 + Left Sidebar</strong></span>
      </div>
    </div>

    <!-- NAVIGATION TABS -->
    <div class="bg-slate-800/90 border-t border-slate-700/80 backdrop-blur">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex overflow-x-auto no-scrollbar">
        <button onclick="switchTab('tab-overview')" id="btn-tab-overview" class="nav-btn px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 border-teal-400 text-teal-300 whitespace-nowrap transition">
          1. Tổng Quan & Kiến Trúc Dual-Portal
        </button>
        <button onclick="switchTab('tab-database')" id="btn-tab-database" class="nav-btn px-4 py-3 text-xs sm:text-sm font-medium border-b-2 border-transparent text-slate-300 hover:text-white whitespace-nowrap transition">
          2. CSDL & SQL DDL (11 Bảng Chuẩn Hóa 3NF)
        </button>
        <button onclick="switchTab('tab-rbac')" id="btn-tab-rbac" class="nav-btn px-4 py-3 text-xs sm:text-sm font-medium border-b-2 border-transparent text-slate-300 hover:text-white whitespace-nowrap transition">
          3. Ma Trận Phân Quyền 3 Tầng (3-Tier RBAC)
        </button>
        <button onclick="switchTab('tab-logic')" id="btn-tab-logic" class="nav-btn px-4 py-3 text-xs sm:text-sm font-medium border-b-2 border-transparent text-slate-300 hover:text-white whitespace-nowrap transition">
          4. Luồng Nghiệp Vụ & Ràng Buộc Kỹ Thuật (5 Luồng)
        </button>
        <button onclick="switchTab('tab-screens')" id="btn-tab-screens" class="nav-btn px-4 py-3 text-xs sm:text-sm font-medium border-b-2 border-transparent text-slate-300 hover:text-white whitespace-nowrap transition">
          5. Đặc Tả 18 Màn Hình & Notion Calendar
        </button>
        <button onclick="switchTab('tab-vibecode')" id="btn-tab-vibecode" class="nav-btn px-4 py-3 text-xs sm:text-sm font-medium border-b-2 border-transparent text-amber-300 hover:text-amber-200 whitespace-nowrap transition">
          6. Vibe Code Prompts & Kế Hoạch 4 Phase
        </button>
      </div>
    </div>
  </header>

  <!-- MAIN CONTENT CONTAINER -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full">
"""

print("Writing generator modules...")
