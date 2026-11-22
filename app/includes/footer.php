    </main>

    <!-- FOOTER COMPONENT (EVENTFLOW PLATFORM - LIGHT HARMONIOUS THEME) -->
    <footer class="mt-auto py-5" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
        <div class="container">
            <div class="row g-4 mb-4">
                <!-- Col 1: Platform Brand & Mission -->
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="<?= base_url('assets/images/logo.png') ?>" alt="EventFlow" class="rounded-3 shadow-xs" style="width: 36px; height: 36px; object-fit: contain;">
                        <span class="fw-bold fs-5" style="font-family: 'Inter', sans-serif; font-weight: 800; letter-spacing: -0.5px; color: #0f172a;">
                            Event<span style="color: #2563eb;">Flow</span>
                        </span>
                    </div>
                    <p class="small mb-2 lh-base" style="max-width: 420px; color: #64748b;">
                        Nền tảng Quản lý & Điểm danh Sự kiện Thông minh dành cho Câu lạc bộ Sinh viên UEH. Tối ưu quy trình tổ chức, đồng bộ dữ liệu thời gian thực và kết nối sinh viên.
                    </p>
                    <div class="fst-italic small" style="color: #2563eb; font-weight: 500;">
                        "Connecting Students · Streamlining Campus Events · Empowering Clubs"
                    </div>
                </div>

                <!-- Col 2: Navigation Links -->
                <div class="col-sm-6 col-lg-3">
                    <h6 class="text-uppercase fw-bold small mb-3 tracking-wider" style="color: #1e293b; font-size: 0.8rem; letter-spacing: 0.05em;">
                        Khám phá & Hoạt động
                    </h6>
                    <ul class="list-unstyled small space-y-2 mb-0">
                        <li class="mb-2"><a href="<?= base_url('index.php') ?>" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">Lịch sự kiện & Workshop</a></li>
                        <li class="mb-2"><a href="<?= base_url('app/modules/club/info.php') ?>" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">CLB Tiếng Anh UEH (BELL Club)</a></li>
                        <li class="mb-2"><a href="<?= base_url('app/modules/club/info.php#departments') ?>" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">Cơ cấu 4 Phòng Ban</a></li>
                        <li class="mb-2"><a href="<?= base_url('app/modules/auth/login.php') ?>" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">Cổng đăng nhập hệ thống</a></li>
                    </ul>
                </div>

                <!-- Col 3: Contact Channels -->
                <div class="col-sm-6 col-lg-4">
                    <h6 class="text-uppercase fw-bold small mb-3 tracking-wider" style="color: #1e293b; font-size: 0.8rem; letter-spacing: 0.05em;">
                        Thông tin liên hệ & Hỗ trợ
                    </h6>
                    <div class="small space-y-2" style="color: #64748b;">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-geo-alt-fill text-primary mt-0.5"></i>
                            <span>Đại học Kinh tế TP. Hồ Chí Minh (UEH) — 279 Nguyễn Tri Phương, Q.10</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-facebook text-primary"></i>
                            <a href="https://www.facebook.com/bellueh" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">Fanpage: CLB Tiếng Anh BELL UEH</a>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-envelope-fill text-primary"></i>
                            <a href="mailto:eventflow.support@ueh.edu.vn" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">eventflow.support@ueh.edu.vn</a>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-github text-primary"></i>
                            <a href="https://github.com/Phungcamtann/EventFlow" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color: #64748b;" onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='#64748b'">
                                Dự án Quản lý Dự án CNTT — Nhóm 8 UEH <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.72rem;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Sub-Footer -->
            <div class="pt-4 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small" style="border-color: #e2e8f0 !important; color: #94a3b8;">
                <div>
                    © 2026 <strong style="color: #475569;">EventFlow Platform</strong> · SMART CAMPUS EVENT MANAGEMENT
                </div>
                <div>
                    Phiên bản 2.0 · Nhóm 8 · QLDA UEH
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 Bundle with Popper CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
