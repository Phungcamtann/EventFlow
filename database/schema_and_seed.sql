-- ============================================================================
-- EVENTFLOW DATABASE SCHEMA & SEED DATA (MYSQL INNODB)
-- Phạm vi: MVP 01 CLB Tiếng Anh Sinh Viên (7 Bảng Chuẩn Hóa 3NF)
-- Thiết kế: UEH - Quản lý Dự án CNTT - Nhóm 8
-- ============================================================================

CREATE DATABASE IF NOT EXISTS eventflow_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE eventflow_db;

-- 1. Bảng Role (Vai trò hệ thống)
CREATE TABLE IF NOT EXISTS Role (
    RoleID INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng Account (Tài khoản người dùng)
CREATE TABLE IF NOT EXISTS Account (
    AccountID INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    StudentCode VARCHAR(20) NULL UNIQUE,
    RoleID INT NOT NULL,
    IsActive TINYINT(1) NOT NULL DEFAULT 1,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_account_role FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_account_search (FullName, StudentCode, Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng Club (Thông tin Câu lạc bộ)
CREATE TABLE IF NOT EXISTS Club (
    ClubID INT AUTO_INCREMENT PRIMARY KEY,
    ClubName VARCHAR(150) NOT NULL,
    Description TEXT NULL,
    ContactInfo VARCHAR(255) NULL,
    ManagerID INT NOT NULL,
    CONSTRAINT fk_club_manager FOREIGN KEY (ManagerID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng Club_Member (Thành viên Câu lạc bộ)
-- MemberRole chuẩn hóa: 'Member', 'Board Member', 'Event Supporter'
-- Status chuẩn hóa: 'Active', 'Inactive'
CREATE TABLE IF NOT EXISTS Club_Member (
    MemberID INT AUTO_INCREMENT PRIMARY KEY,
    AccountID INT NOT NULL,
    ClubID INT NOT NULL,
    MemberRole VARCHAR(50) NOT NULL DEFAULT 'Member',
    Status VARCHAR(30) NOT NULL DEFAULT 'Active',
    JoinedDate DATE NULL,
    CONSTRAINT uq_club_member UNIQUE (AccountID, ClubID),
    CONSTRAINT fk_member_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_member_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng Event (Sự kiện)
-- Status chuẩn hóa: 'Draft', 'Published', 'Cancelled'
CREATE TABLE IF NOT EXISTS Event (
    EventID INT AUTO_INCREMENT PRIMARY KEY,
    ClubID INT NOT NULL,
    EventName VARCHAR(200) NOT NULL,
    Description TEXT NULL,
    Location VARCHAR(255) NOT NULL,
    StartTime DATETIME NOT NULL,
    EndTime DATETIME NOT NULL,
    RegistrationDeadline DATETIME NOT NULL,
    MaxCapacity INT NULL,
    Status VARCHAR(30) NOT NULL DEFAULT 'Published',
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_event_status_time (Status, StartTime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng Event_Registration (Đăng ký sự kiện)
-- Status chuẩn hóa: 'Registered', 'Cancelled'
CREATE TABLE IF NOT EXISTS Event_Registration (
    RegID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    AccountID INT NOT NULL,
    RegDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(30) NOT NULL DEFAULT 'Registered',
    CONSTRAINT uq_event_account UNIQUE (EventID, AccountID),
    CONSTRAINT fk_reg_event FOREIGN KEY (EventID) REFERENCES Event(EventID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reg_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_reg_event_status (EventID, Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng Attendance (Điểm danh / Check-in)
-- AttendanceStatus chuẩn hóa: 1 (Present), 0 (Absent)
CREATE TABLE IF NOT EXISTS Attendance (
    AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
    RegID INT NOT NULL UNIQUE,
    AttendanceStatus TINYINT(1) NOT NULL DEFAULT 1,
    CheckInAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CheckedInBy INT NOT NULL,
    CONSTRAINT fk_att_reg FOREIGN KEY (RegID) REFERENCES Event_Registration(RegID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_att_checker FOREIGN KEY (CheckedInBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DỮ LIỆU KHỞI TẠO MẪU (SEED DATA BÁM SÁT PROTOTYPE TUẦN 3)
-- Mật khẩu mặc định của tất cả tài khoản demo: Password@123
-- ============================================================================

INSERT INTO Role (RoleID, RoleName) VALUES
(1, 'System Administrator'),
(2, 'Club Manager'),
(3, 'Club Member'),
(4, 'Event Participant')
ON DUPLICATE KEY UPDATE RoleName = VALUES(RoleName);

-- Bcrypt hash cho 'Password@123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO Account (AccountID, Email, PasswordHash, FullName, StudentCode, RoleID, IsActive) VALUES
(1, 'admin@ueh.edu.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin EventFlow', NULL, 1, 1),
(2, 'manager@st.ueh.edu.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dương Hoàng Sâm', '31241020001', 2, 1),
(3, 'supporter@st.ueh.edu.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Yến Phượng', '31241022663', 3, 1),
(4, 'minhan@st.ueh.edu.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Minh An', '31241020123', 3, 1),
(5, 'student@st.ueh.edu.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Hoàng Nam', '31241020999', 4, 1)
ON DUPLICATE KEY UPDATE FullName = VALUES(FullName);

INSERT INTO Club (ClubID, ClubName, Description, ContactInfo, ManagerID) VALUES
(1, 'UEH English Club (Bell Club)', 'Câu lạc bộ Tiếng Anh sinh viên trực thuộc Đại học Kinh tế TP. Hồ Chí Minh (UEH). Lĩnh vực hoạt động: Học thuật, Giao lưu văn hóa, Rèn luyện kỹ năng giao tiếp và Tranh biện tiếng Anh.', 'Email: englishclub@ueh.edu.vn | Fanpage: fb.com/uehenglishclub | Cơ sở B - 279 Nguyễn Tri Phương', 2)
ON DUPLICATE KEY UPDATE ClubName = VALUES(ClubName);

INSERT INTO Club_Member (MemberID, AccountID, ClubID, MemberRole, Status, JoinedDate) VALUES
(1, 2, 1, 'Board Member', 'Active', '2025-09-01'),
(2, 3, 1, 'Event Supporter', 'Active', '2025-10-15'),
(3, 4, 1, 'Member', 'Active', '2026-01-10')
ON DUPLICATE KEY UPDATE MemberRole = VALUES(MemberRole);

INSERT INTO Event (EventID, ClubID, EventName, Description, Location, StartTime, EndTime, RegistrationDeadline, MaxCapacity, Status) VALUES
(1, 1, 'English Speaking Circle', 'Buổi sinh hoạt giao tiếp tiếng Anh định kỳ theo chủ đề dành cho thành viên CLB và sinh viên UEH. Thảo luận các chủ đề đời sống, kết nối bạn bè và nâng cao phản xạ tiếng Anh tự nhiên.', 'UEH Cơ sở B - 279 Nguyễn Tri Phương (Phòng B1.302)', '2026-11-15 18:00:00', '2026-11-15 20:30:00', '2026-11-14 23:59:00', 40, 'Published'),
(2, 1, 'Debate Lab: Speak Up', 'Workshop hướng dẫn kỹ năng tranh biện tiếng Anh theo format Nghị viện Anh (British Parliamentary). Rèn luyện tư duy phản biện, lập luận logic và kỹ năng thuyết trình trước đám đông.', 'UEH Cơ sở B - Hội trường B1.302', '2026-11-20 14:00:00', '2026-11-20 17:00:00', '2026-11-19 23:59:00', 60, 'Published'),
(3, 1, 'Cultural Exchange Day', 'Ngày hội giao lưu văn hóa và thực hành ngôn ngữ dành cho toàn thể sinh viên. Khám phá các nền văn hóa đa quốc gia, tham gia minigame và thưởng thức âm nhạc acoustic.', 'Sảnh B1 - UEH Nguyễn Tri Phương', '2026-11-28 08:30:00', '2026-11-28 11:30:00', '2026-11-26 23:59:00', NULL, 'Published')
ON DUPLICATE KEY UPDATE EventName = VALUES(EventName);
