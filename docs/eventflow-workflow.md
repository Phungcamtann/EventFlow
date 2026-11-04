# 🔄 EventFlow — Agent WorkFlow Guide

> **Mục đích:** Hướng dẫn agent AI làm việc theo quy trình chuẩn, có tổ chức, có kiểm soát.  
> **Phiên bản:** 1.0  
> **Cập nhật:** 04/11/2026

---

## Mục lục

1. [Quy tắc tuyệt đối](#1--quy-tắc-tuyệt-đối)
2. [Tổng quan quy trình](#2--tổng-quan-quy-trình)
3. [Chi tiết từng bước](#3--chi-tiết-từng-bước)
4. [Cấu trúc thư mục docs/](#4--cấu-trúc-thư-mục-docs)
5. [Template files.md](#5--template-filesmd)
6. [Template plan.md](#6--template-planmd)
7. [Quy tắc commit](#7--quy-tắc-commit)
8. [Checklist hoàn thành pha](#8--checklist-hoàn-thành-pha)
9. [Xử lý tình huống đặc biệt](#9--xử-lý-tình-huống-đặc-biệt)
10. [Ví dụ minh họa](#10--ví-dụ-minh-họa)

---

## 1. 🚨 Quy tắc tuyệt đối

> [!CAUTION]
> Các quy tắc dưới đây là **BẮT BUỘC**, không có ngoại lệ. Vi phạm bất kỳ quy tắc nào sẽ làm vô hiệu toàn bộ kết quả của pha đó.

### QT-1: Không bịa đặt

```
❌ TUYỆT ĐỐI KHÔNG:
   • Bịa yêu cầu, tính năng, logic không có trong dữ liệu đã cung cấp
   • Tự suy diễn business logic khi chưa được xác nhận
   • Thêm field, bảng, API, trang mà user chưa yêu cầu
   • Giả định công nghệ, thư viện, cấu trúc chưa được thống nhất

✅ CHỈ ĐƯỢC:
   • Làm đúng và đủ những gì đã được cung cấp trong tài liệu
   • Tham chiếu trực tiếp đến nguồn dữ liệu khi triển khai
   • Ghi rõ "Dựa theo [tài liệu X, mục Y]" khi implement
```

### QT-2: Hỏi trước khi làm

```
Khi gặp BẤT KỲ tình huống nào sau đây → DỪNG LẠI và HỎI USER:

   ? Yêu cầu mơ hồ, có thể hiểu theo nhiều cách
   ? Thiếu thông tin để triển khai (field nào, logic gì, UI ra sao)
   ? Xung đột giữa các tài liệu / yêu cầu
   ? Cần quyết định kỹ thuật ảnh hưởng đến kiến trúc
   ? Không chắc chắn về phạm vi (scope) của pha hiện tại
   ? Tài liệu thiết kế không cover trường hợp cụ thể

Cách hỏi:
   1. Nêu rõ VẤN ĐỀ gặp phải
   2. Liệt kê các PHƯƠNG ÁN khả thi (nếu có)
   3. Đề xuất phương án KHUYẾN NGHỊ (nếu có cơ sở)
   4. CHỜ user trả lời → Mới tiếp tục
```

### QT-3: Không sợ tốn công

```
✅ LUÔN LUÔN:
   • Làm đầy đủ, không cắt xén, không tắt bước
   • Viết documentation đầy đủ cho mỗi pha
   • Tạo đủ files.md và plan.md trước khi code
   • Test / review kỹ trước khi báo hoàn thành
   • Refactor nếu cần để đảm bảo chất lượng

❌ KHÔNG BAO GIỜ:
   • Bỏ qua bước vì "tiết kiệm thời gian"
   • Gộp nhiều pha thành 1 để "cho nhanh"
   • Skip documentation vì "code đã rõ ràng"
   • Commit code chưa hoàn chỉnh với lý do "sẽ fix sau"
```

---

## 2. 🗺 Tổng quan quy trình

```
╔══════════════════════════════════════════════════════════════════╗
║                    WORKFLOW TỔNG THỂ                            ║
╠══════════════════════════════════════════════════════════════════╣
║                                                                  ║
║   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐      ║
║   │  BƯỚC 1     │     │  BƯỚC 2     │     │  BƯỚC 3     │      ║
║   │  Phân tích  │────►│  Chia pha   │────►│  Lập kế     │      ║
║   │  yêu cầu    │     │             │     │  hoạch pha  │      ║
║   └─────────────┘     └─────────────┘     └──────┬──────┘      ║
║                                                   │              ║
║                                                   ▼              ║
║                                           ┌─────────────┐       ║
║                                           │  BƯỚC 4     │       ║
║                              ┌───────────►│  Thực hiện  │       ║
║                              │            │  pha hiện   │       ║
║                              │            │  tại        │       ║
║                              │            └──────┬──────┘       ║
║                              │                   │               ║
║                              │                   ▼               ║
║                              │            ┌─────────────┐       ║
║                              │            │  BƯỚC 5     │       ║
║                              │            │  Review &   │       ║
║                              │            │  Kiểm tra   │       ║
║                              │            └──────┬──────┘       ║
║                              │                   │               ║
║                              │                   ▼               ║
║                              │            ┌─────────────┐       ║
║                              │            │  BƯỚC 6     │       ║
║                              │            │  Cập nhật   │       ║
║                              │            │  docs &     │       ║
║                              │            │  Commit     │       ║
║                              │            └──────┬──────┘       ║
║                              │                   │               ║
║                              │      Còn pha?     │               ║
║                              │     ┌──YES──┘     │               ║
║                              │     │              │ NO            ║
║                              └─────┘              ▼               ║
║                                           ┌─────────────┐       ║
║                                           │  HOÀN THÀNH │       ║
║                                           │  🎉          │       ║
║                                           └─────────────┘       ║
║                                                                  ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## 3. 📋 Chi tiết từng bước

### Bước 1 — Phân tích yêu cầu

| Hành động | Chi tiết |
|-----------|---------|
| Đọc kỹ toàn bộ tài liệu | Đọc hết yêu cầu, use case, thiết kế, ERD, wireframe... đã được cung cấp |
| Liệt kê những gì ĐÃ RÕ | Các tính năng, entity, flow đã có đủ thông tin |
| Liệt kê những gì CHƯA RÕ | Thiếu gì? Mâu thuẫn gì? Cần quyết định gì? |
| **Hỏi user nếu cần** | Gửi danh sách câu hỏi → **Chờ trả lời** trước khi đi tiếp |

### Bước 2 — Chia pha

Phân chia toàn bộ công việc thành các **pha (phase)** theo nguyên tắc:

```
Nguyên tắc chia pha:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. MỖI PHA = MỘT LUỒNG HOÀN CHỈNH
   → Pha kết thúc phải chạy được, không phụ thuộc pha sau
   → Ví dụ: "Pha Auth" = Đăng ký + Đăng nhập + Phân quyền
            chứ không phải "Pha Đăng ký" rồi "Pha Đăng nhập"

2. THỨ TỰ PHỤ THUỘC
   → Pha trước là nền tảng cho pha sau
   → Pha 1: Setup → Pha 2: Auth → Pha 3: CRUD → ...

3. ĐỘ LỚN HỢP LÝ
   → Không quá nhỏ (1-2 file) → Gộp lại
   → Không quá lớn (20+ file) → Tách ra
   → Lý tưởng: 5-15 files/pha

4. FULL-STACK THEO FEATURE
   → Mỗi pha bao gồm: Backend + Frontend + Tests (nếu có)
   → Không chia theo layer (1 pha chỉ làm BE, 1 pha chỉ làm FE)
```

Trình bày danh sách pha cho user duyệt trước khi bắt đầu:

```
Danh sách pha đề xuất:
───────────────────────────────────────
Pha 1: [Tên pha] — [Mô tả ngắn]
Pha 2: [Tên pha] — [Mô tả ngắn]
Pha 3: [Tên pha] — [Mô tả ngắn]
...

→ User duyệt / chỉnh sửa → Mới bắt đầu thực hiện
```

### Bước 3 — Lập kế hoạch pha (trước khi code)

Trước khi viết **bất kỳ dòng code nào**, phải tạo 2 file documentation:

| File | Nội dung | Thời điểm tạo |
|------|---------|---------------|
| `docs/pha-X/plan.md` | Kế hoạch chi tiết: mục tiêu, tasks, cấu trúc file, flow logic | **TRƯỚC** khi code |
| `docs/pha-X/files.md` | Danh sách file sẽ tạo/sửa (dự kiến) | **TRƯỚC** khi code, cập nhật **SAU** khi hoàn thành |

### Bước 4 — Thực hiện pha

```
Quy trình thực hiện trong 1 pha:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  ① Đọc lại plan.md của pha hiện tại
  
  ② Thực hiện từng task theo thứ tự trong plan
     → Code theo đúng yêu cầu đã phân tích
     → Tham chiếu tài liệu gốc khi cần
     → GẶP VẤN ĐỀ → HỎI, không tự suy diễn
  
  ③ Sau mỗi task:
     → Kiểm tra code vừa viết
     → Đảm bảo không break code cũ
     → Cập nhật files.md (đánh dấu đã hoàn thành)
  
  ④ Hoàn thành tất cả tasks trong pha
     → Chạy thử toàn bộ luồng
     → Fix bugs nếu có
```

### Bước 5 — Review & Kiểm tra

```
Checklist review trước khi commit:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  □ Code chạy được, không lỗi runtime
  □ Luồng hoàn chỉnh từ đầu đến cuối
  □ Không có code thừa, code TODO bỏ sót
  □ Không bịa thêm tính năng ngoài plan
  □ Tuân thủ design rules (bảng màu, spacing, layout...)
  □ Files.md đã cập nhật đầy đủ
  □ Không break bất kỳ pha nào trước đó
```

### Bước 6 — Cập nhật docs & Commit

```
  ① Cập nhật files.md → trạng thái cuối cùng (file nào đã tạo/sửa/xóa)
  ② Commit với message chuẩn (xem mục 7)
  ③ Thông báo user: Pha X hoàn thành
  ④ Chờ user xác nhận → Mới chuyển sang pha tiếp theo
```

---

## 4. 📁 Cấu trúc thư mục docs/

```
project-root/
├── docs/
│   ├── pha-1/
│   │   ├── plan.md          ← Kế hoạch chi tiết pha 1
│   │   └── files.md         ← Danh sách file pha 1
│   │
│   ├── pha-2/
│   │   ├── plan.md
│   │   └── files.md
│   │
│   ├── pha-3/
│   │   ├── plan.md
│   │   └── files.md
│   │
│   └── ... (tiếp tục theo số pha)
│
├── src/                      ← Source code
├── public/                   ← Static assets
└── ...
```

> [!IMPORTANT]
> Tên thư mục pha **phải** theo format `pha-X` (X = số thứ tự).  
> Mỗi pha **bắt buộc** có đủ 2 file: `plan.md` và `files.md`.

---

## 5. 📄 Template files.md

Dưới đây là template chuẩn cho `files.md` của mỗi pha:

```markdown
# 📁 Pha [X]: [Tên pha] — Danh sách File

> **Trạng thái:** 🔴 Chưa bắt đầu | 🟡 Đang thực hiện | 🟢 Hoàn thành  
> **Ngày bắt đầu:** [DD/MM/YYYY]  
> **Ngày hoàn thành:** [DD/MM/YYYY]  
> **Commit hash:** [hash sau khi commit]

---

## Tổng kết

| Loại | Số lượng |
|------|---------|
| File tạo mới | X |
| File chỉnh sửa | X |
| File xóa | X |
| **Tổng** | **X** |

---

## Danh sách chi tiết

### Tạo mới

| # | Đường dẫn file | Mô tả | Trạng thái |
|---|---------------|-------|-----------|
| 1 | `src/path/to/file.ext` | Mô tả ngắn chức năng | ✅ / ⬜ |
| 2 | `src/path/to/file.ext` | Mô tả ngắn chức năng | ✅ / ⬜ |

### Chỉnh sửa

| # | Đường dẫn file | Nội dung thay đổi | Trạng thái |
|---|---------------|-------------------|-----------|
| 1 | `src/path/to/file.ext` | Thay đổi gì, tại sao | ✅ / ⬜ |

### Xóa (nếu có)

| # | Đường dẫn file | Lý do xóa |
|---|---------------|----------|
| 1 | `src/path/to/old-file.ext` | Thay thế bởi file mới X |

---

## Ghi chú
- [Ghi chú đặc biệt nếu có]
```

---

## 6. 📄 Template plan.md

Dưới đây là template chuẩn cho `plan.md` của mỗi pha:

```markdown
# 📋 Pha [X]: [Tên pha] — Kế hoạch thực hiện

> **Mục tiêu:** [Mô tả mục tiêu chính của pha này trong 1-2 câu]  
> **Phụ thuộc:** Pha [X-1] phải hoàn thành trước  
> **Tham chiếu:** [Liệt kê tài liệu yêu cầu / thiết kế liên quan]

---

## 1. Phạm vi (Scope)

### Bao gồm (In scope)
- [ ] Chức năng A
- [ ] Chức năng B
- [ ] Chức năng C

### Không bao gồm (Out of scope)
- Chức năng X (sẽ làm ở Pha Y)
- Chức năng Z (chưa có yêu cầu)

---

## 2. Tasks chi tiết

### Task 1: [Tên task]
- **Mô tả:** [Chi tiết task cần làm gì]
- **File liên quan:** `src/path/file.ext`
- **Logic / Flow:**
  1. Bước 1...
  2. Bước 2...
  3. Bước 3...
- **Dữ liệu tham chiếu:** [Lấy từ tài liệu nào, mục nào]

### Task 2: [Tên task]
- **Mô tả:** ...
- ...

### Task N: [Tên task]
- ...

---

## 3. Cấu trúc file dự kiến

```
src/
├── folder/
│   ├── new-file-1.ext      ← [Mô tả]
│   ├── new-file-2.ext      ← [Mô tả]
│   └── ...
```

---

## 4. Điểm cần lưu ý
- [Lưu ý kỹ thuật]
- [Phụ thuộc đặc biệt]
- [Rủi ro tiềm ẩn]

---

## 5. Tiêu chí hoàn thành (Definition of Done)
- [ ] Tiêu chí 1
- [ ] Tiêu chí 2
- [ ] Tiêu chí 3
```

---

## 7. 📝 Quy tắc Commit

### Format commit message

```
[Pha X] <type>: <mô tả ngắn>

<body - mô tả chi tiết (tùy chọn)>
```

### Commit types

| Type | Ý nghĩa | Ví dụ |
|------|---------|-------|
| `feat` | Tính năng mới | `[Pha 2] feat: hoàn thành đăng nhập/đăng ký` |
| `fix` | Sửa lỗi | `[Pha 2] fix: sửa lỗi validate email` |
| `refactor` | Tái cấu trúc | `[Pha 3] refactor: tách component EventCard` |
| `docs` | Documentation | `[Pha 1] docs: thêm plan.md và files.md` |
| `style` | UI/CSS | `[Pha 4] style: cập nhật responsive navbar` |
| `setup` | Cài đặt, config | `[Pha 1] setup: khởi tạo project Bootstrap 5.3` |

### Quy tắc commit

| # | Quy tắc |
|---|---------|
| 1 | **Mỗi pha = 1 commit chính** khi hoàn thành toàn bộ pha |
| 2 | Commit message **phải có prefix `[Pha X]`** |
| 3 | Message bằng **tiếng Việt** (mô tả) hoặc tiếng Anh (type) |
| 4 | Không commit code dở dang, chưa chạy được |
| 5 | Commit docs (plan.md, files.md) **có thể tách riêng** trước khi commit code |

### Ví dụ flow commit cho 1 pha

```
Commit 1: [Pha 3] docs: tạo plan.md và files.md cho quản lý sự kiện
Commit 2: [Pha 3] feat: hoàn thành CRUD sự kiện (danh sách, chi tiết, tạo mới, chỉnh sửa)
```

---

## 8. ✅ Checklist hoàn thành pha

Trước khi tuyên bố pha hoàn thành, agent **PHẢI** đảm bảo tất cả các mục sau:

### Documentation
- [ ] `docs/pha-X/plan.md` đã tạo đầy đủ, rõ ràng
- [ ] `docs/pha-X/files.md` đã cập nhật trạng thái ✅ cho mọi file
- [ ] Files.md ghi đúng đường dẫn, đúng mô tả, đúng số lượng

### Code Quality
- [ ] Code chạy được, không có lỗi syntax/runtime
- [ ] Luồng chức năng hoàn chỉnh từ đầu đến cuối
- [ ] Không có code TODO / FIXME / placeholder bỏ sót
- [ ] Không bịa thêm tính năng ngoài scope pha
- [ ] Tuân thủ design rules (màu sắc, layout, spacing, typography...)

### Integration
- [ ] Không break bất kỳ chức năng nào từ các pha trước
- [ ] Tương thích với cấu trúc project hiện tại
- [ ] Các link / navigation hoạt động đúng

### Commit
- [ ] Commit message đúng format `[Pha X] type: mô tả`
- [ ] Đã commit thành công

### Handoff
- [ ] Thông báo user kết quả pha
- [ ] Tóm tắt những gì đã làm
- [ ] Nêu rõ pha tiếp theo là gì
- [ ] **Chờ user xác nhận** trước khi tiếp tục

---

## 9. ⚠️ Xử lý tình huống đặc biệt

### Khi phát hiện lỗi ở pha trước

```
  ① DỪNG pha hiện tại
  ② Báo user: "Phát hiện lỗi ở Pha X: [mô tả lỗi]"
  ③ Đề xuất: Fix tại chỗ hay tạo commit hotfix riêng?
  ④ Chờ user quyết định
  ⑤ Fix xong → Commit: "[Pha X] fix: [mô tả]"
  ⑥ Tiếp tục pha hiện tại
```

### Khi scope pha thay đổi giữa chừng

```
  ① DỪNG code
  ② Cập nhật plan.md với thay đổi mới
  ③ Đánh dấu rõ: "⚠️ Scope thay đổi: [lý do]"
  ④ Trình bày user duyệt plan mới
  ⑤ Chờ xác nhận → Tiếp tục
```

### Khi pha quá lớn

```
  ① Phát hiện pha có > 15 files hoặc > 5 luồng chức năng
  ② Đề xuất user: Tách thành Pha Xa và Pha Xb
  ③ Ví dụ: Pha 3 → Pha 3a (CRUD Events) + Pha 3b (Event Registration)
  ④ Chờ user đồng ý → Tạo docs cho từng pha con
```

### Khi không chắc chắn về thiết kế UI

```
  ① Kiểm tra lại design rules (EventFlow-Design-Rules.md)
  ② Kiểm tra wireframe / mockup đã cung cấp
  ③ Vẫn không rõ → HỎI USER kèm:
     • Screenshot vùng liên quan (nếu có)
     • 2-3 phương án giao diện
     • Đề xuất phương án phù hợp nhất
```

---

## 10. 📌 Ví dụ minh họa

### Ví dụ: Nhận yêu cầu "Xây dựng hệ thống quản lý sự kiện"

**Bước 1 — Phân tích:**
```
Đã có:
  ✅ Use case diagram
  ✅ ERD / Database schema
  ✅ Design rules (bảng màu, layout)
  ✅ Wireframe các màn hình chính

Chưa rõ:
  ❓ Dùng database gì? (MySQL / PostgreSQL / MongoDB)
  ❓ Backend framework? (Node.js / PHP / Python)
  ❓ Authentication method? (Session / JWT / OAuth)

→ HỎI USER 3 câu trên trước khi tiếp tục
```

**Bước 2 — Chia pha (sau khi có đủ thông tin):**

```
Pha 1: Project Setup & Base Layout
     → Khởi tạo project, cấu hình, tạo layout base (navbar, footer)

Pha 2: Authentication (Đăng ký / Đăng nhập / Phân quyền)
     → Form đăng ký, đăng nhập, middleware phân quyền

Pha 3: Quản lý sự kiện (CRUD)
     → Danh sách, chi tiết, tạo mới, chỉnh sửa, xóa sự kiện

Pha 4: Đăng ký tham gia sự kiện
     → Flow đăng ký, xem trạng thái, hủy đăng ký

Pha 5: Điểm danh
     → QR code, check-in, trạng thái điểm danh

Pha 6: Dashboard & Thống kê
     → Trang tổng quan, biểu đồ, báo cáo

→ Trình bày user duyệt
→ User OK → Bắt đầu Pha 1
```

**Bước 3 — Plan.md cho Pha 1:**

```markdown
# 📋 Pha 1: Project Setup & Base Layout

> **Mục tiêu:** Khởi tạo project, cài đặt dependencies,
> tạo layout base với Navbar và Footer theo design rules.
> **Phụ thuộc:** Không có (pha đầu tiên)
> **Tham chiếu:** EventFlow-Design-Rules.md

## Scope
- [x] Khởi tạo project structure
- [x] Cài đặt Bootstrap 5.3 CDN
- [x] Tạo CSS variables (từ design rules)
- [x] Tạo Navbar component
- [x] Tạo Footer component
- [x] Tạo trang Home (placeholder)

## Tasks
### Task 1: Khởi tạo project...
### Task 2: CSS Variables...
### Task 3: Navbar...
...
```

**Bước 4 → 5 → 6:** Thực hiện → Review → Commit

```bash
git add .
git commit -m "[Pha 1] setup: khởi tạo project và base layout (navbar, footer, CSS variables)"
```

**Báo cáo user:**
```
✅ Pha 1 hoàn thành!

Đã làm:
  • Khởi tạo project structure
  • Cài Bootstrap 5.3 CDN
  • Tạo CSS variables theo design rules
  • Tạo Navbar (sticky, dark indigo)
  • Tạo Footer
  • Trang Home placeholder

Pha tiếp theo: Pha 2 — Authentication
→ Bạn muốn tiếp tục không?
```

---

## Tóm tắt Flow — Quick Reference

```
┌──────────────────────────────────────────────┐
│  📥 NHẬN YÊU CẦU                             │
│    → Đọc kỹ tài liệu                        │
│    → Thiếu thông tin? → HỎI                  │
├──────────────────────────────────────────────┤
│  📊 CHIA PHA                                  │
│    → Mỗi pha = 1 luồng hoàn chỉnh            │
│    → Trình bày user duyệt                    │
├──────────────────────────────────────────────┤
│  📝 TẠO DOCS TRƯỚC KHI CODE                  │
│    → docs/pha-X/plan.md                       │
│    → docs/pha-X/files.md                      │
├──────────────────────────────────────────────┤
│  ⚡ THỰC HIỆN PHA                             │
│    → Code theo plan                           │
│    → Không bịa, không skip                    │
│    → Gặp vấn đề → HỎI                        │
├──────────────────────────────────────────────┤
│  ✅ REVIEW & COMMIT                           │
│    → Chạy checklist                           │
│    → Cập nhật files.md                        │
│    → Commit: [Pha X] type: mô tả             │
├──────────────────────────────────────────────┤
│  📢 BÁO CÁO USER                             │
│    → Tóm tắt kết quả                         │
│    → Nêu pha tiếp theo                        │
│    → CHỜ xác nhận → Pha tiếp                  │
└──────────────────────────────────────────────┘
```
