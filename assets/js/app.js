/**
 * EventFlow — Main Client-Side JavaScript
 * Hỗ trợ các tương tác UI, xác thực biểu mẫu và Flash Messages
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Tự động ẩn thông báo Flash Alert sau 5 giây nếu có
    const flashAlerts = document.querySelectorAll('.alert-dismissible');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } else {
                alert.style.transition = 'opacity 0.4s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 400);
            }
        }, 5000);
    });

    // 2. Validate form submit password match (SCR01)
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('password')?.value;
            const confirm = document.getElementById('confirmPassword')?.value;
            if (password && confirm && password !== confirm) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp! Vui lòng kiểm tra lại.');
            }
        });
    }

    // 3. Khởi tạo Bootstrap tooltips nếu có
    if (window.bootstrap && bootstrap.Tooltip) {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
});
