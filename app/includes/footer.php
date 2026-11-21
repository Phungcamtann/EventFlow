    </main>

    <!-- FOOTER COMPONENT (EVENTFLOW PLATFORM BRANDING) -->
    <footer class="mt-auto text-white py-5" style="background-color: #080f1a; border-top: 2px solid #2563eb;">
        <div class="container">
            <div class="row g-4 mb-4">
                <!-- Col 1: Platform Brand & Mission -->
                <div class="col-lg-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="ef-brand-icon shadow-sm" style="width: 36px; height: 36px; font-size: 1.05rem;">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                        <span class="fw-bold text-white fs-5" style="font-family: 'Inter', sans-serif; font-weight: 800; letter-spacing: -0.5px;">
                            Event<span style="color: #60a5fa;">Flow</span>
                        </span>
                    </div>
                    <p class="text-white-50 small mb-2 lh-base" style="max-width: 420px;">
                        Nền tảng Quản lý & Điểm danh Sự kiện Thông minh dành cho Câu lạc bộ Sinh viên UEH. Tối ưu quy trình tổ chức, đồng bộ dữ liệu thời gian thực và kết nối sinh viên.
                    </p>
                    <div class="fst-italic text-info small">
                        "Connecting Students · Streamlining Campus Events · Empowering Clubs"
                    </div>
                </div>

                <!-- Col 2: Navigation Links -->
                <div class="col-sm-6 col-lg-3">
                    <h6 class="text-white text-uppercase fw-bold small mb-3 tracking-wider text-ef-sky">
                        Khám phá & Hoạt động
                    </h6>
                    <ul class="list-unstyled small text-white-50 space-y-1.5 mb-0">
                        <li class="mb-1.5"><a href="<?= base_url('index.php') ?>" class="text-decoration-none text-white-50 hover-white">Lịch sự kiện & Workshop</a></li>
                        <li class="mb-1.5"><a href="<?= base_url('app/modules/club/info.php') ?>" class="text-decoration-none text-white-50 hover-white">CLB Tiếng Anh UEH (BELL Club)</a></li>
                        <li class="mb-1.5"><a href="<?= base_url('app/modules/club/info.php#departments') ?>" class="text-decoration-none text-white-50 hover-white">Cơ cấu 4 Phòng Ban</a></li>
                        <li class="mb-1.5"><a href="<?= base_url('app/modules/auth/login.php') ?>" class="text-decoration-none text-white-50 hover-white">Cổng đăng nhập hệ thống</a></li>
                    </ul>
                </div>

                <!-- Col 3: Contact Channels -->
                <div class="col-sm-6 col-lg-4">
                    <h6 class="text-white text-uppercase fw-bold small mb-3 tracking-wider text-ef-sky">
                        Thông tin liên hệ & Hỗ trợ
                    </h6>
                    <div class="small text-white-50 space-y-2">
                        <div class="d-flex align-items-start gap-2 mb-1.5">
                            <i class="bi bi-geo-alt-fill text-info"></i>
                            <span>Đại học Kinh tế TP. Hồ Chí Minh (UEH) — 279 Nguyễn Tri Phương, Q.10</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-1.5">
                            <i class="bi bi-envelope-fill text-info"></i>
                            <a href="mailto:eventflow.support@ueh.edu.vn" class="text-decoration-none text-white-50">eventflow.support@ueh.edu.vn</a>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-1.5">
                            <i class="bi bi-github text-info"></i>
                            <span>Dự án Quản lý Dự án CNTT — Nhóm 8 UEH</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Sub-Footer -->
            <div class="pt-4 border-top border-secondary border-opacity-25 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-white-50 small">
                <div>
                    © 2026 <strong>EventFlow Platform</strong> · SMART CAMPUS EVENT MANAGEMENT
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
