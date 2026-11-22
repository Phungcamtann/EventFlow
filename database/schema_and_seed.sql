-- ============================================================================
-- EVENTFLOW DATABASE SCHEMA & SEED DATA (PRODUCTION HOSTING / SPRINT 7)
-- Đơn vị: CLB Tiếng Anh Sinh Viên UEH (BELL CLUB UEH)
-- Chuẩn hóa: 11 Bảng Quan Hệ 3NF + Foreign Keys + Composite Indexes + Seed Data
-- ============================================================================

CREATE DATABASE IF NOT EXISTS eventflow_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE eventflow_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- DỌN DẸP BẢNG CŨ THEO THỨ TỰ (DROP TABLES IF EXISTS)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS Attendance;
DROP TABLE IF EXISTS Event_Registration;
DROP TABLE IF EXISTS Event_Staff;
DROP TABLE IF EXISTS Event;
DROP TABLE IF EXISTS Club_Member;
DROP TABLE IF EXISTS Department;
DROP TABLE IF EXISTS Recruitment_Application;
DROP TABLE IF EXISTS Recruitment_Campaign;
DROP TABLE IF EXISTS Club;
DROP TABLE IF EXISTS Account;
DROP TABLE IF EXISTS Role;

-- ----------------------------------------------------------------------------
-- 1. BẢNG ROLE (Vai trò hệ thống cấp cao)
-- ----------------------------------------------------------------------------
CREATE TABLE Role (
    RoleID INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. BẢNG ACCOUNT (Tài khoản người dùng toàn hệ thống)
-- ----------------------------------------------------------------------------
CREATE TABLE Account (
    AccountID INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    StudentCode VARCHAR(20) NULL UNIQUE,
    Phone VARCHAR(20) NULL,
    Cohort VARCHAR(10) NULL,              -- K48, K49, K50, K51, K52
    Major VARCHAR(100) NULL,              -- Chuyên ngành đào tạo tại UEH
    AvatarUrl VARCHAR(255) NULL,
    RoleID INT NOT NULL,
    PasswordMustChange TINYINT(1) NOT NULL DEFAULT 0, -- 1 khi cấp tự động Ueh@<MSSV>
    IsActive TINYINT(1) NOT NULL DEFAULT 1,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_account_role FOREIGN KEY (RoleID) REFERENCES Role(RoleID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_account_search (FullName, StudentCode, Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. BẢNG CLUB (Thông tin Câu Lạc Bộ Tiếng Anh)
-- ----------------------------------------------------------------------------
CREATE TABLE Club (
    ClubID INT AUTO_INCREMENT PRIMARY KEY,
    ClubName VARCHAR(150) NOT NULL,
    ShortName VARCHAR(50) NOT NULL DEFAULT 'BELL UEH',
    Description TEXT NOT NULL,
    Vision TEXT NULL,
    Mission TEXT NULL,
    Avatar VARCHAR(255) NULL,
    BannerImage VARCHAR(255) NULL,
    ContactInfo VARCHAR(255) NULL,
    Email VARCHAR(100) NULL,
    Phone VARCHAR(20) NULL,
    FacebookUrl VARCHAR(255) NULL,
    Address VARCHAR(255) NOT NULL DEFAULT 'UEH Cơ sở B - 279 Nguyễn Tri Phương, Phường 5, Quận 10, TP.HCM',
    ManagerID INT NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_club_manager FOREIGN KEY (ManagerID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. BẢNG DEPARTMENT (Phòng ban trực thuộc CLB)
-- ----------------------------------------------------------------------------
CREATE TABLE Department (
    DepartmentID INT AUTO_INCREMENT PRIMARY KEY,
    ClubID INT NOT NULL,
    DepartmentName VARCHAR(100) NOT NULL,
    DepartmentCode VARCHAR(20) NOT NULL,  -- HR, SPEC, COMS, ER
    Description TEXT NOT NULL,
    KeyActivities TEXT NULL,
    OrderIndex INT NOT NULL DEFAULT 1,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_department_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uk_club_dept_code (ClubID, DepartmentCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. BẢNG CLUB_MEMBER (Thành viên CLB & Phân quyền nội bộ tầng 2)
-- ----------------------------------------------------------------------------
CREATE TABLE Club_Member (
    MemberID INT AUTO_INCREMENT PRIMARY KEY,
    AccountID INT NOT NULL,
    ClubID INT NOT NULL,
    DepartmentID INT NULL,                -- Nullable đối với President/Vice President
    MemberRole VARCHAR(50) NOT NULL DEFAULT 'Member', -- President, Vice President, Head, Vice Head, Board Member, Member
    Status VARCHAR(30) NOT NULL DEFAULT 'Active',
    JoinedDate DATE NULL,
    Bio TEXT NULL,
    CONSTRAINT uq_club_member UNIQUE (AccountID, ClubID),
    CONSTRAINT fk_member_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_member_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_member_department FOREIGN KEY (DepartmentID) REFERENCES Department(DepartmentID)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. BẢNG EVENT (Sự kiện CLB với State Machine 7 trạng thái)
-- ----------------------------------------------------------------------------
CREATE TABLE Event (
    EventID INT AUTO_INCREMENT PRIMARY KEY,
    ClubID INT NOT NULL,
    EventName VARCHAR(200) NOT NULL,
    Description TEXT NULL,
    Location VARCHAR(255) NOT NULL,
    StartTime DATETIME NOT NULL,
    EndTime DATETIME NOT NULL,
    RegistrationStartTime DATETIME NULL,
    RegistrationDeadline DATETIME NOT NULL,
    MaxCapacity INT NULL,                 -- NULL = Không giới hạn
    MinStaffRequired INT NOT NULL DEFAULT 2, -- Ràng buộc tối thiểu 2 BĐH/Cán bộ
    BannerImage VARCHAR(255) NULL,
    Status VARCHAR(30) NOT NULL DEFAULT 'Published',
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_event_status_time (Status, StartTime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. BẢNG EVENT_STAFF (Phân công cán bộ & Cấp quyền điểm danh)
-- ----------------------------------------------------------------------------
CREATE TABLE Event_Staff (
    EventStaffID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    AccountID INT NOT NULL,
    StaffRole ENUM('Organizer', 'Checkin_Lead', 'Support', 'MC') NOT NULL DEFAULT 'Support',
    AssignedBy INT NOT NULL,
    AssignedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CheckInAt DATETIME NULL DEFAULT NULL,
    CheckedInBy INT NULL DEFAULT NULL,
    CONSTRAINT fk_staff_event FOREIGN KEY (EventID) REFERENCES Event(EventID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_staff_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_staff_assigner FOREIGN KEY (AssignedBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_staff_checker FOREIGN KEY (CheckedInBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uk_event_account_staff (EventID, AccountID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. BẢNG EVENT_REGISTRATION (Đăng ký sự kiện - Thành viên & Khách vãng lai)
-- ----------------------------------------------------------------------------
CREATE TABLE Event_Registration (
    RegID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    AccountID INT NULL,                   -- Null nếu là khách vãng lai (Guest)
    RegistrationType ENUM('Member', 'Guest') NOT NULL DEFAULT 'Member',
    GuestName VARCHAR(100) NULL,
    GuestEmail VARCHAR(100) NULL,
    GuestPhone VARCHAR(20) NULL,
    GuestStudentCode VARCHAR(20) NULL,
    RegDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(30) NOT NULL DEFAULT 'Registered',
    CONSTRAINT fk_reg_event FOREIGN KEY (EventID) REFERENCES Event(EventID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reg_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_reg_event_status (EventID, Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. BẢNG ATTENDANCE (Điểm danh tham gia sự kiện)
-- ----------------------------------------------------------------------------
CREATE TABLE Attendance (
    AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
    RegID INT NOT NULL,
    AttendanceStatus TINYINT(1) NOT NULL DEFAULT 1,
    CheckInAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CheckedInBy INT NOT NULL,
    CONSTRAINT fk_attendance_reg FOREIGN KEY (RegID) REFERENCES Event_Registration(RegID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attendance_staff FOREIGN KEY (CheckedInBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uk_attendance_reg (RegID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 10. BẢNG RECRUITMENT_CAMPAIGN (Đợt tuyển quân CLB)
-- ----------------------------------------------------------------------------
CREATE TABLE Recruitment_Campaign (
    CampaignID INT AUTO_INCREMENT PRIMARY KEY,
    ClubID INT NOT NULL,
    Title VARCHAR(200) NOT NULL,
    Description TEXT NOT NULL,
    StartDate DATETIME NOT NULL,
    EndDate DATETIME NOT NULL,
    Status ENUM('Draft', 'Open', 'Closed') NOT NULL DEFAULT 'Draft',
    TargetCount INT NOT NULL DEFAULT 20,
    CreatedBy INT NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_campaign_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campaign_creator FOREIGN KEY (CreatedBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. BẢNG RECRUITMENT_APPLICATION (Hồ sơ ứng tuyển tân thành viên)
-- ----------------------------------------------------------------------------
CREATE TABLE Recruitment_Application (
    ApplicationID INT AUTO_INCREMENT PRIMARY KEY,
    CampaignID INT NOT NULL,
    DepartmentID INT NOT NULL,
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    StudentCode VARCHAR(20) NOT NULL,
    Cohort VARCHAR(10) NOT NULL,
    Major VARCHAR(100) NOT NULL,
    CV_Path VARCHAR(255) NULL,
    IntroText TEXT NOT NULL,
    Status ENUM('Submitted', 'Interview_Scheduled', 'Interviewed', 'Approved', 'Rejected') NOT NULL DEFAULT 'Submitted',
    InterviewDate DATETIME NULL,
    InterviewNote TEXT NULL,
    ReviewedBy INT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_campaign FOREIGN KEY (CampaignID) REFERENCES Recruitment_Campaign(CampaignID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_app_department FOREIGN KEY (DepartmentID) REFERENCES Department(DepartmentID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_app_reviewer FOREIGN KEY (ReviewedBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uk_campaign_student (CampaignID, StudentCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA CHUẨN HOÁ: BELL CLUB UEH & HỆ THỐNG PHÂN QUYỀN RBAC 3 TẦNG
-- Mật khẩu mặc định cho toàn bộ tài khoản mẫu: password123 (BCrypt hash)
-- Hash: $2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a
-- ============================================================================

-- 1. Vai trò hệ thống
INSERT INTO Role (RoleID, RoleName) VALUES
(1, 'System Administrator'),
(2, 'Club Manager'),
(3, 'Club Member'),
(4, 'Event Participant');

-- 2. Tài khoản người dùng
INSERT INTO Account (AccountID, Email, PasswordHash, FullName, StudentCode, Phone, Cohort, Major, AvatarUrl, RoleID, PasswordMustChange, IsActive, CreatedAt) VALUES
(1, 'admin@ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'System Admin EventFlow', NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2026-11-04 15:16:28'),
(2, 'minhanh.president@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Nguyễn Minh Anh', '31241020001', '0902000001', 'K48', 'Kinh doanh quốc tế', NULL, 2, 0, 1, '2026-11-04 15:16:28'),
(3, 'hoangnam.vp@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Trần Hoàng Nam', '31241020002', '0902000002', 'K48', 'Thương mại điện tử', NULL, 2, 0, 1, '2026-11-04 15:16:28'),
(4, 'thutrang.hr@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Phạm Thu Trang', '31241020003', '0903000001', 'K49', 'Quản trị nhân lực', NULL, 3, 0, 1, '2026-11-04 15:16:28'),
(5, 'vanbinh.hr@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Nguyễn Văn Bình', '31241020004', '0903000002', 'K49', 'Quản trị kinh doanh', NULL, 3, 0, 1, '2026-11-04 15:16:28'),
(6, 'minhkhang.spec@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Trần Minh Khang', '31241020005', '0904000001', 'K49', 'Ngôn ngữ Anh thương mại', NULL, 3, 0, 1, '2026-11-21 18:10:47'),
(7, 'thaovy.spec@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Lê Thảo Vy', '31241020006', '0904000002', 'K50', 'Tài chính quốc tế', NULL, 3, 0, 1, '2026-11-21 18:10:47'),
(8, 'hoanglong.coms@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Nguyễn Hoàng Long', '31241020007', '0905000001', 'K49', 'Truyền thông số', NULL, 3, 0, 1, '2026-11-21 18:10:47'),
(9, 'ngocmai.coms@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Phạm Ngọc Mai', '31241020008', '0905000002', 'K50', 'Marketing', NULL, 3, 0, 1, '2026-11-21 18:10:47'),
(10, 'thanhdat.er@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Võ Thành Đạt', '31241020009', '0906000001', 'K49', 'Quan hệ công chúng', NULL, 3, 0, 1, '2026-11-21 18:10:47'),
(11, 'khanhlinh.er@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Đặng Khánh Linh', '31241020010', '0906000002', 'K50', 'Logistics', NULL, 3, 0, 1, '2026-11-21 18:10:47'),
(20, 'manager@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Dương Hoàng Sâm', '31241029001', '0901234567', 'K48', 'Thương mại điện tử', NULL, 2, 0, 1, '2026-11-21 21:51:57'),
(21, 'supporter@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Trần Yến Phượng', '31241022663', '0901234568', 'K49', 'Quản trị nhân lực', NULL, 3, 0, 1, '2026-11-21 21:51:57'),
(22, 'minhan@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Nguyễn Minh An', '31241020123', '0901234569', 'K49', 'Kinh doanh quốc tế', NULL, 3, 0, 1, '2026-11-21 21:51:57'),
(23, 'student@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Lê Hoàng Nam', '31241020999', '0901234570', 'K50', 'Marketing số', NULL, 4, 0, 1, '2026-11-21 21:51:57'),
(24, '31241020199@st.ueh.edu.vn', '$2y$10$omGyc.ZSeqB5JBerbNmHTeNez4WWLOa8oBihHkW8P1mzEeaPpoh9a', 'Hoàng Gia Huy', '31241020199', '0909888999', 'K50', 'Truyền thông số & Thiết kế đa phương tiện', NULL, 3, 0, 1, '2026-11-22 12:32:51');

-- 3. Hồ sơ CLB BELL UEH
INSERT INTO Club (ClubID, ClubName, ShortName, Description, Vision, Mission, Avatar, BannerImage, ContactInfo, Email, Phone, FacebookUrl, Address, ManagerID, CreatedAt) VALUES
(1, 'BELL Club - Câu lạc bộ Tiếng Anh Sinh viên UEH', 'BELL UEH',
 'BELL Club là tổ chức tiên phong về phong trào thực hành tiếng Anh năng động và chuyên nghiệp dành cho sinh viên Đại học Kinh tế TP. Hồ Chí Minh.',
 'Trở thành cộng đồng tiếng Anh sinh viên dẫn đầu cả nước về kỹ năng học thuật, tranh biện và hội nhập quốc tế.',
 'Tạo môi trường rèn luyện tiếng Anh tự nhiên, kết nối cơ hội nghề nghiệp và phát triển toàn diện kỹ năng lãnh đạo.',
 'assets/uploads/club/ueh_club_avatar.jpg', 'assets/uploads/club/ueh_club_banner.jpg',
 'Email: bellclub@ueh.edu.vn | Hotline: 02838551779 | Fanpage: fb.com/bellueh',
 'bellclub@ueh.edu.vn', '02838551779', 'https://facebook.com/bellueh',
 'UEH Cơ sở B - 279 Nguyễn Tri Phương, Phường 5, Quận 10, TP.HCM', 2, '2026-11-04 15:16:28');

-- 4. 4 Phòng ban chuyên môn BELL Club
INSERT INTO Department (DepartmentID, ClubID, DepartmentName, DepartmentCode, Description, KeyActivities, OrderIndex, CreatedAt) VALUES
(1, 1, 'Human Resources', 'HR',
 'The department that keeps the BELL community connected, cared for and growing together. Human Resources focuses on member experience, internal connection and development. From recruitment journeys and bonding activities to internal care initiatives, HR helps BELLers feel welcomed, supported and ready to contribute.',
 'Member care, Gatherings & BELL Care\nTraining, workshops & English Sharing Day\nInternal Communications & newsletters\nCheerleading, appreciation & connection', 1, '2026-11-21 18:10:47'),
(2, 1, 'Specialist', 'SPEC',
 'The English-learning heartbeat of BELL, where practice becomes habit and ideas become learning experiences. Specialist designs and delivers activities that help members and students use English more confidently. Through speaking groups, translation initiatives and accessible knowledge-sharing formats, this department turns learning into something practical and lively.',
 'Speaking Group Private (SGP) & Extended (SGE)\nTranslator Team, No Vietnamese Day & Monthly Challenge\nEnglish discussions and skill sharing\nRing the BELL & interactive practice', 2, '2026-11-21 18:10:47'),
(3, 1, 'Communications', 'COMS',
 'The voice, look and storytelling energy that bring BELL activities to life. Communications develops BELL’s visual identity and public-facing content. From campaigns and event coverage to creative projects and multimedia storytelling, COMS helps people see, feel and remember the BELL spirit.',
 'Content, Design & Production House\nVisuals, video and storytelling\nMedia campaigns and event coverage', 3, '2026-11-21 18:10:47'),
(4, 1, 'External Relations', 'ER',
 'The bridge connecting BELL with partners, sponsors and opportunities beyond the club. External Relations builds and maintains relationships with brands, partners and outside stakeholders. By supporting sponsorship, collaboration and long-term connection, ER helps BELL expand its reach and create stronger opportunities for members and programs.',
 'Sponsorship proposals & negotiation\nLong-term partner relationship building\nSpeakers, partners & event support\nSponsor and media collaborations', 4, '2026-11-21 18:10:47');

-- 5. Thành viên CLB theo chức danh & phòng ban
INSERT INTO Club_Member (MemberID, AccountID, ClubID, DepartmentID, MemberRole, Status, JoinedDate, Bio) VALUES
(1, 2, 1, NULL, 'President', 'Active', '2024-09-01', 'Chủ nhiệm CLB BELL UEH — Chịu trách nhiệm điều hành chung, định hướng chiến lược học thuật và đối ngoại.'),
(2, 3, 1, NULL, 'Vice President', 'Active', '2024-09-01', 'Phó Chủ nhiệm CLB BELL UEH — Phụ trách quản lý vận hành các ban chuyên môn và chất lượng sự kiện.'),
(3, 4, 1, 1, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Nhân sự — Điều phối chiến dịch tuyển quân, gắn kết nội bộ và đào tạo thành viên.'),
(7, 5, 1, 1, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Nhân sự — Quản lý hồ sơ ứng tuyển, xếp lịch phỏng vấn và hỗ trợ văn hóa CLB.'),
(8, 6, 1, 2, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Chuyên môn — Thiết kế hoạt động Speaking Group (SGP/SGE), điều phối nội dung học thuật.'),
(9, 7, 1, 2, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Chuyên môn — Phụ trách Translator Team, No Vietnamese Day & Monthly Challenge.'),
(10, 8, 1, 3, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Truyền thông — Định hình nhận diện thương hiệu BELL, chiến dịch truyền thông đa phương tiện.'),
(11, 9, 1, 3, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Truyền thông — Phụ trách sản xuất hình ảnh, video và quản lý nội dung fanpage.'),
(12, 10, 1, 4, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Quan hệ Đối ngoại — Kết nối nhà tài trợ, diễn giả, doanh nghiệp và đối tác đối ngoại.'),
(13, 11, 1, 4, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Quan hệ Đối ngoại — Điều phối tài trợ sự kiện, quyền lợi truyền thông và chăm sóc đối tác.'),
(14, 20, 1, NULL, 'Board Member', 'Active', '2024-09-01', 'Ủy viên Ban Điều Hành CLB.'),
(15, 21, 1, NULL, 'Member', 'Active', '2025-02-01', 'Cộng tác viên Sự kiện.'),
(16, 22, 1, NULL, 'Member', 'Active', '2025-02-01', 'Thành viên CLB.'),
(17, 24, 1, 3, 'Member', 'Active', '2026-11-22', 'Tân thành viên Phòng Truyền thông Gen 27.');

-- 6. 5 Sự kiện mẫu của CLB (Đầy đủ Banner & Lịch hoạt động)
INSERT INTO Event (EventID, ClubID, EventName, Description, Location, StartTime, RegistrationStartTime, EndTime, RegistrationDeadline, MaxCapacity, MinStaffRequired, BannerImage, Status, CreatedAt) VALUES
(1, 1, 'English Speaking Circle', 'Buổi sinh hoạt giao tiếp tiếng Anh định kỳ theo chủ đề dành cho thành viên CLB và sinh viên UEH. Thảo luận các chủ đề đời sống, kết nối bạn bè và nâng cao phản xạ tiếng Anh tự nhiên.', 'UEH Cơ sở B - 279 Nguyễn Tri Phương (Phòng B1.302)', '2026-11-15 18:00:00', '2026-11-01 00:00:00', '2026-11-30 20:30:00', '2026-11-30 23:59:00', 40, 2, 'assets/uploads/events/event_speaking.jpg', 'Published', '2026-11-04 15:16:28'),
(2, 1, 'Debate Lab: Speak Up', 'Workshop hướng dẫn kỹ năng tranh biện tiếng Anh theo format Nghị viện Anh (British Parliamentary). Rèn luyện tư duy phản biện, lập luận logic và kỹ năng thuyết trình trước đám đông.', 'UEH Cơ sở B - Hội trường B1.302', '2026-11-20 14:00:00', NULL, '2026-11-20 17:00:00', '2026-11-19 23:59:00', 2, 2, 'assets/uploads/events/event_debate.jpg', 'Published', '2026-11-04 15:16:28'),
(3, 1, 'Cultural Exchange Day', 'Ngày hội giao lưu văn hóa và thực hành ngôn ngữ dành cho toàn thể sinh viên. Khám phá các nền văn hóa đa quốc gia, tham gia minigame và thưởng thức âm nhạc acoustic.', 'Sảnh B1 - UEH Nguyễn Tri Phương', '2026-11-28 08:30:00', NULL, '2026-11-28 11:30:00', '2026-11-26 23:59:00', NULL, 2, 'assets/uploads/events/event_culture.jpg', 'Published', '2026-11-04 15:16:28'),
(4, 1, 'Specialist Workshop: IELTS & Global Mindset', 'Buổi workshop chuyên sâu chia sẻ lộ trình chinh phục IELTS 7.5+ và rèn luyện tư duy công dân toàn cầu do Ban Học thuật (Specialist) chủ trì.', 'UEH Cơ sở B - Phòng B1.205', '2026-11-26 13:30:00', '2026-11-10 08:00:00', '2026-11-26 16:30:00', '2026-11-25 23:59:59', 100, 2, 'assets/uploads/events/event_ielts.jpg', 'Published', '2026-11-21 21:20:28'),
(5, 1, 'BELL Gen 27 Info Day & Networking Gala', 'Ngày hội giao lưu, định hướng văn hóa B.E.L.L và giải đáp thắc mắc về đợt tuyển quân Thế hệ 27.', 'UEH Cơ sở B - Hội trường A.103', '2026-11-29 18:30:00', '2026-11-12 08:00:00', '2026-11-29 21:00:00', '2026-11-29 12:00:00', 150, 2, 'assets/uploads/events/event_networking_gala.jpg', 'Published', '2026-11-21 21:20:28');

-- 7. Phân công Ban Tổ Chức (Event_Staff - Đảm bảo tối thiểu 2 cán bộ/sự kiện)
INSERT INTO Event_Staff (EventStaffID, EventID, AccountID, StaffRole, AssignedBy, AssignedAt, CheckInAt, CheckedInBy) VALUES
(1, 1, 2, 'Organizer', 2, '2026-11-21 18:10:47', '2026-11-15 17:45:00', 2),
(2, 1, 4, 'Checkin_Lead', 2, '2026-11-21 18:10:47', NULL, NULL),
(3, 1, 6, 'Support', 2, '2026-11-21 18:10:47', NULL, NULL),
(4, 2, 3, 'Organizer', 2, '2026-11-21 18:10:47', NULL, NULL),
(5, 2, 7, 'Checkin_Lead', 2, '2026-11-21 18:10:47', NULL, NULL),
(6, 3, 8, 'Organizer', 2, '2026-11-21 23:59:05', NULL, NULL),
(7, 3, 9, 'Checkin_Lead', 2, '2026-11-21 23:59:05', NULL, NULL),
(8, 3, 10, 'Support', 2, '2026-11-21 23:59:05', NULL, NULL),
(9, 4, 6, 'Organizer', 2, '2026-11-21 23:59:05', NULL, NULL),
(10, 4, 7, 'Checkin_Lead', 2, '2026-11-21 23:59:05', NULL, NULL),
(11, 4, 4, 'MC', 2, '2026-11-21 23:59:05', NULL, NULL),
(12, 5, 2, 'Organizer', 2, '2026-11-21 23:59:05', '2026-11-29 17:30:00', 2),
(13, 5, 3, 'Organizer', 2, '2026-11-21 23:59:05', '2026-11-29 17:30:00', 2),
(14, 5, 10, 'Checkin_Lead', 2, '2026-11-21 23:59:05', '2026-11-22 11:31:15', 2),
(15, 5, 11, 'Support', 2, '2026-11-21 23:59:05', '2026-11-22 11:58:54', 2),
(16, 5, 21, 'MC', 2, '2026-11-22 00:11:11', '2026-11-22 11:21:41', 2),
(17, 5, 20, 'Support', 2, '2026-11-22 16:43:51', NULL, NULL);

-- 8. Danh sách đăng ký sự kiện (Event_Registration - Thành viên & Khách vãng lai)
INSERT INTO Event_Registration (RegID, EventID, AccountID, RegistrationType, GuestName, GuestEmail, GuestPhone, GuestStudentCode, RegDate, Status) VALUES
(1, 1, 4, 'Member', NULL, NULL, NULL, NULL, '2026-11-11 21:38:51', 'Registered'),
(2, 2, 4, 'Member', NULL, NULL, NULL, NULL, '2026-11-11 23:25:04', 'Registered'),
(3, 2, 1, 'Member', NULL, NULL, NULL, NULL, '2026-11-16 10:49:57', 'Registered'),
(4, 3, 3, 'Member', NULL, NULL, NULL, NULL, '2026-11-21 16:17:18', 'Registered'),
(5, 4, NULL, 'Guest', 'Trần Quốc Đạt', '31241020999@st.ueh.edu.vn', '0918889999', '31241020999', '2026-11-22 12:34:48', 'Registered'),
(6, 4, 23, 'Member', NULL, NULL, NULL, NULL, '2026-11-22 13:46:34', 'Registered'),
(7, 4, NULL, 'Guest', 'Thành Tài', '31241025678@st.ueh.edu.vn', '09123123413', '31241025678', '2026-11-22 13:50:27', 'Registered');

-- 9. Dữ liệu điểm danh thực tế (Attendance)
INSERT INTO Attendance (AttendanceID, RegID, AttendanceStatus, CheckInAt, CheckedInBy) VALUES
(8, 4, 1, '2026-11-21 16:38:09', 3),
(11, 7, 1, '2026-11-22 16:19:42', 2),
(12, 6, 1, '2026-11-22 16:19:44', 2);

-- 10. Chiến dịch tuyển quân (Recruitment_Campaign)
INSERT INTO Recruitment_Campaign (CampaignID, ClubID, Title, Description, StartDate, EndDate, Status, TargetCount, CreatedBy, CreatedAt) VALUES
(1, 1, 'BELL Recruitment Gen 27: Ignite Your Spark',
 'Đợt tuyển chọn thành viên thế hệ thứ 27 của CLB Tiếng Anh BELL UEH cho 4 phòng ban: Human Resources, Specialist, Communications, External Relations.',
 '2026-11-15 00:00:00', '2026-12-10 23:59:00', 'Open', 25, 4, '2026-11-21 18:10:47');

-- 11. Hồ sơ ứng tuyển tuyển quân Gen 27 (Recruitment_Application)
INSERT INTO Recruitment_Application (ApplicationID, CampaignID, DepartmentID, FullName, Email, Phone, StudentCode, Cohort, Major, CV_Path, IntroText, Status, InterviewDate, InterviewNote, ReviewedBy, CreatedAt, UpdatedAt) VALUES
(1, 1, 1, 'Lê Quốc Bảo', '31241020088@st.ueh.edu.vn', '0912345678', '31241020088', 'K50', 'Kinh doanh quốc tế', 'uploads/cvs/sample_cv_lequocbao.pdf', 'Em là sinh viên năm nhất K50, rất đam mê các hoạt động gắn kết nội bộ và tổ chức teambuilding. Em mong muốn được tham gia ban Nhân sự BELL để trau dồi kỹ năng quản trị con người và cống hiến cho CLB.', 'Submitted', NULL, NULL, NULL, '2026-11-22 12:29:57', '2026-11-22 12:29:57'),
(2, 1, 2, 'Đỗ Mai Phương', '31241020123@st.ueh.edu.vn', '0987654321', '31241020123', 'K50', 'Ngôn ngữ Anh thương mại', 'uploads/cvs/sample_cv_domaiphuong.pdf', 'Em có chứng chỉ IELTS 7.5 và từng đạt giải Nhất cuộc thi Hùng biện Tiếng Anh cấp trường. Em muốn gia nhập ban Chuyên môn để cùng CLB thiết kế các chuỗi Speaking Group chất lượng cho sinh viên UEH.', 'Interview_Scheduled', '2026-11-25 14:30:00', 'Phỏng vấn trực tiếp tại Phòng B1.205 (UEH Cơ sở B). Yêu cầu mang theo thẻ sinh viên.', 4, '2026-11-22 12:29:57', '2026-11-22 12:29:57'),
(3, 1, 3, 'Hoàng Gia Huy', '31241020199@st.ueh.edu.vn', '0909888999', '31241020199', 'K50', 'Truyền thông số & Thiết kế đa phương tiện', 'uploads/cvs/sample_cv_hoanggiahuy.pdf', 'Em thành thạo Premiere, Photoshop, Figma và có kinh nghiệm sản xuất video ngắn TikTok đạt triệu view. Em mong muốn đưa hình ảnh của BELL Club lan tỏa mạnh mẽ hơn đến cộng đồng sinh viên.', 'Approved', '2026-11-21 09:00:00', 'Điểm phỏng vấn: 9.0/10. Portfolio thiết kế và dựng video rất xuất sắc, tư duy thương hiệu tốt. Đề xuất: Phê duyệt nhận vào ban COMS.', 2, '2026-11-22 12:29:57', '2026-11-22 12:32:51'),
(4, 1, 3, 'Phạm Thiên Long', 'longpham@st.ueh.edu.vn', '0123456789', '31241021234', 'K50', 'Kỹ Thuật Phần Mềm', 'uploads/cvs/sample_cv_longpham.pdf', 'Em đam mê thiết kế ấn phẩm đồ họa và xây dựng nội dung truyền thông sự kiện.', 'Submitted', NULL, NULL, NULL, '2026-11-22 13:02:58', '2026-11-22 13:02:58');

SET FOREIGN_KEY_CHECKS = 1;
