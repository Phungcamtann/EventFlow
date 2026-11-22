-- ============================================================================
-- EVENTFLOW DATABASE SCHEMA & SEED DATA (BLUEPRINT V2.0 - SPRINT 7)
-- Đơn vị: CLB Tiếng Anh Sinh Viên UEH (BELL CLUB UEH)
-- Chuẩn hóa: 11 Bảng Quan Hệ 3NF + Foreign Keys + Composite Indexes
-- ============================================================================

CREATE DATABASE IF NOT EXISTS eventflow_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE eventflow_db;

-- ----------------------------------------------------------------------------
-- 1. BẢNG ROLE (Vai trò hệ thống cấp cao)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Role (
    RoleID INT AUTO_INCREMENT PRIMARY KEY,
    RoleName VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. BẢNG ACCOUNT (Tài khoản người dùng toàn hệ thống)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Account (
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
CREATE TABLE IF NOT EXISTS Club (
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
-- 4. BẢNG DEPARTMENT (Phòng ban trực thuộc CLB - MỚI · UAT 05)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Department (
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
CREATE TABLE IF NOT EXISTS Club_Member (
    MemberID INT AUTO_INCREMENT PRIMARY KEY,
    AccountID INT NOT NULL,
    ClubID INT NOT NULL,
    DepartmentID INT NULL,                -- Nullable đối với President/Vice President
    MemberRole VARCHAR(50) NOT NULL DEFAULT 'Member', -- President, Vice President, Head, Vice Head, Member
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
CREATE TABLE IF NOT EXISTS Event (
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
-- 7. BẢNG EVENT_STAFF (Phân công cán bộ & Cấp quyền điểm danh - MỚI · UAT 08, 11)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Event_Staff (
    EventStaffID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    AccountID INT NOT NULL,
    StaffRole ENUM('Organizer', 'Checkin_Lead', 'Support', 'MC') NOT NULL DEFAULT 'Support',
    AssignedBy INT NOT NULL,
    AssignedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_event FOREIGN KEY (EventID) REFERENCES Event(EventID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_staff_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_staff_assigner FOREIGN KEY (AssignedBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uk_event_account_staff (EventID, AccountID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. BẢNG EVENT_REGISTRATION (Đăng ký sự kiện - Thành viên & Khách vãng lai · UAT 02)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Event_Registration (
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
-- 9. BẢNG ATTENDANCE (Điểm danh tham gia sự kiện · UAT 17)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Attendance (
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
-- 10. BẢNG RECRUITMENT_CAMPAIGN (Đợt tuyển quân CLB - MỚI · UAT 02, 09)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Recruitment_Campaign (
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
-- 11. BẢNG RECRUITMENT_APPLICATION (Hồ sơ ứng tuyển - MỚI · UAT 02, 09)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Recruitment_Application (
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
-- SEED DATA CHUẨN: BELL CLUB UEH & 10 NHÂN SỰ LÃNH ĐẠO THỰC TẾ
-- Password mặc định: password123 (hash bcrypt)
-- ============================================================================

-- Vai trò hệ thống
INSERT INTO Role (RoleID, RoleName) VALUES
(1, 'admin'),
(2, 'clubmanager'),
(3, 'clubmember'),
(4, 'participant')
ON DUPLICATE KEY UPDATE RoleName = VALUES(RoleName);

-- Hồ sơ CLB BELL UEH
INSERT INTO Club (ClubID, ClubName, ShortName, Description, Vision, Mission, Avatar, BannerImage, ContactInfo, Email, Phone, FacebookUrl, Address, ManagerID) VALUES
(1, 'BELL Club - Câu lạc bộ Tiếng Anh Sinh viên UEH', 'BELL UEH',
 'BELL Club là tổ chức tiên phong về phong trào thực hành tiếng Anh năng động và chuyên nghiệp dành cho sinh viên Đại học Kinh tế TP. Hồ Chí Minh.',
 'Trở thành cộng đồng tiếng Anh sinh viên dẫn đầu cả nước về kỹ năng học thuật, tranh biện và hội nhập quốc tế.',
 'Tạo môi trường rèn luyện tiếng Anh tự nhiên, kết nối cơ hội nghề nghiệp và phát triển toàn diện kỹ năng lãnh đạo.',
 'assets/images/logo_bell.png', 'assets/images/banner_bell.jpg',
 'Email: bellclub@ueh.edu.vn | Hotline: 02838551779 | Fanpage: fb.com/bellueh',
 'bellclub@ueh.edu.vn', '02838551779', 'https://facebook.com/bellueh',
 'UEH Cơ sở B - 279 Nguyễn Tri Phương, Phường 5, Quận 10, TP.HCM', 2)
ON DUPLICATE KEY UPDATE 
    ClubName = VALUES(ClubName),
    ShortName = VALUES(ShortName),
    Description = VALUES(Description),
    Vision = VALUES(Vision),
    Mission = VALUES(Mission),
    ContactInfo = VALUES(ContactInfo),
    Email = VALUES(Email),
    Phone = VALUES(Phone),
    FacebookUrl = VALUES(FacebookUrl),
    Address = VALUES(Address),
    ManagerID = VALUES(ManagerID);

-- 4 Phòng ban chuyên môn BELL Club
INSERT INTO Department (DepartmentID, ClubID, DepartmentName, DepartmentCode, Description, KeyActivities, OrderIndex) VALUES
(1, 1, 'Human Resources', 'HR',
 'The department that keeps the BELL community connected, cared for and growing together. Human Resources focuses on member experience, internal connection and development. From recruitment journeys and bonding activities to internal care initiatives, HR helps BELLers feel welcomed, supported and ready to contribute.',
 'Member care, Gatherings & BELL Care\nTraining, workshops & English Sharing Day\nInternal Communications & newsletters\nCheerleading, appreciation & connection', 1),
(2, 1, 'Specialist', 'SPEC',
 'The English-learning heartbeat of BELL, where practice becomes habit and ideas become learning experiences. Specialist designs and delivers activities that help members and students use English more confidently. Through speaking groups, translation initiatives and accessible knowledge-sharing formats, this department turns learning into something practical and lively.',
 'Speaking Group Private (SGP) & Extended (SGE)\nTranslator Team, No Vietnamese Day & Monthly Challenge\nEnglish discussions and skill sharing\nRing the BELL & interactive practice', 2),
(3, 1, 'Communications', 'COMS',
 'The voice, look and storytelling energy that bring BELL activities to life. Communications develops BELL’s visual identity and public-facing content. From campaigns and event coverage to creative projects and multimedia storytelling, COMS helps people see, feel and remember the BELL spirit.',
 'Content, Design & Production House\nVisuals, video and storytelling\nMedia campaigns and event coverage', 3),
(4, 1, 'External Relations', 'ER',
 'The bridge connecting BELL with partners, sponsors and opportunities beyond the club. External Relations builds and maintains relationships with brands, partners and outside stakeholders. By supporting sponsorship, collaboration and long-term connection, ER helps BELL expand its reach and create stronger opportunities for members and programs.',
 'Sponsorship proposals & negotiation\nLong-term partner relationship building\nSpeakers, partners & event support\nSponsor and media collaborations', 4)
ON DUPLICATE KEY UPDATE 
    DepartmentName = VALUES(DepartmentName),
    DepartmentCode = VALUES(DepartmentCode),
    Description = VALUES(Description),
    KeyActivities = VALUES(KeyActivities),
    OrderIndex = VALUES(OrderIndex);

-- 10 Cán bộ Ban Điều Hành & Lãnh Đạo Phòng Ban từ PDF (password: password123)
INSERT INTO Account (AccountID, Email, PasswordHash, FullName, StudentCode, Phone, Cohort, Major, RoleID, IsActive) VALUES
(1, 'admin@eventflow.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'System Administrator', 'ADMIN001', '0901000001', 'K47', 'Hệ thống thông tin', 1, 1),
(2, 'minhanh.president@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Nguyễn Minh Anh', '31241020001', '0902000001', 'K48', 'Kinh doanh quốc tế', 2, 1),
(3, 'hoangnam.vp@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Trần Hoàng Nam', '31241020002', '0902000002', 'K48', 'Thương mại điện tử', 2, 1),
(4, 'thutrang.hr@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Phạm Thu Trang', '31241020003', '0903000001', 'K49', 'Quản trị nhân lực', 3, 1),
(5, 'vanbinh.hr@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Nguyễn Văn Bình', '31241020004', '0903000002', 'K49', 'Quản trị kinh doanh', 3, 1),
(6, 'minhkhang.spec@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Trần Minh Khang', '31241020005', '0904000001', 'K49', 'Ngôn ngữ Anh thương mại', 3, 1),
(7, 'thaovy.spec@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Lê Thảo Vy', '31241020006', '0904000002', 'K50', 'Tài chính quốc tế', 3, 1),
(8, 'hoanglong.coms@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Nguyễn Hoàng Long', '31241020007', '0905000001', 'K49', 'Truyền thông số', 3, 1),
(9, 'ngocmai.coms@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Phạm Ngọc Mai', '31241020008', '0905000002', 'K50', 'Marketing', 3, 1),
(10, 'thanhdat.er@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Võ Thành Đạt', '31241020009', '0906000001', 'K49', 'Quan hệ công chúng', 3, 1),
(11, 'khanhlinh.er@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Đặng Khánh Linh', '31241020010', '0906000002', 'K50', 'Logistics', 3, 1)
ON DUPLICATE KEY UPDATE FullName = VALUES(FullName);

-- Gán Thành viên CLB theo chức danh & phòng ban
INSERT INTO Club_Member (ClubID, AccountID, DepartmentID, MemberRole, Status, JoinedDate, Bio) VALUES
(1, 2, NULL, 'President', 'Active', '2024-09-01', 'Chủ nhiệm CLB BELL UEH — Chịu trách nhiệm điều hành chung, định hướng chiến lược học thuật và đối ngoại.'),
(1, 3, NULL, 'Vice President', 'Active', '2024-09-01', 'Phó Chủ nhiệm CLB BELL UEH — Phụ trách quản lý vận hành các ban chuyên môn và chất lượng sự kiện.'),
(1, 4, 1, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Nhân sự — Điều phối chiến dịch tuyển quân, gắn kết nội bộ và đào tạo thành viên.'),
(1, 5, 1, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Nhân sự — Quản lý hồ sơ ứng tuyển, xếp lịch phỏng vấn và hỗ trợ văn hóa CLB.'),
(1, 6, 2, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Chuyên môn — Thiết kế hoạt động Speaking Group (SGP/SGE), điều phối nội dung học thuật.'),
(1, 7, 2, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Chuyên môn — Phụ trách Translator Team, No Vietnamese Day & Monthly Challenge.'),
(1, 8, 3, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Truyền thông — Định hình nhận diện thương hiệu BELL, chiến dịch truyền thông đa phương tiện.'),
(1, 9, 3, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Truyền thông — Phụ trách sản xuất hình ảnh, video và quản lý nội dung fanpage.'),
(1, 10, 4, 'Head', 'Active', '2025-01-15', 'Trưởng phòng Quan hệ Đối ngoại — Kết nối nhà tài trợ, diễn giả, doanh nghiệp và đối tác đối ngoại.'),
(1, 11, 4, 'Vice Head', 'Active', '2025-01-15', 'Phó phòng Quan hệ Đối ngoại — Điều phối tài trợ sự kiện, quyền lợi truyền thông và chăm sóc đối tác.')
ON DUPLICATE KEY UPDATE MemberRole = VALUES(MemberRole), DepartmentID = VALUES(DepartmentID), Bio = VALUES(Bio);

-- Sự kiện mẫu
INSERT INTO Event (EventID, ClubID, EventName, Description, Location, StartTime, EndTime, RegistrationStartTime, RegistrationDeadline, MaxCapacity, MinStaffRequired, Status) VALUES
(1, 1, 'BELL Speaking Circle: Global Mindset', 'Buổi sinh hoạt giao tiếp tiếng Anh định kỳ theo chủ đề dành cho sinh viên UEH.', 'UEH Cơ sở B - B1.302 (279 Nguyễn Tri Phương)', '2026-11-25 18:00:00', '2026-11-25 20:30:00', '2026-11-15 08:00:00', '2026-11-24 23:59:00', 40, 2, 'Published'),
(2, 1, 'Debate Masterclass: British Parliamentary', 'Workshop chuyên sâu về kỹ năng tranh biện và phản biện tiếng Anh quốc tế.', 'Hội trường B1.302 - Cơ sở B UEH', '2026-11-28 14:00:00', '2026-11-28 17:00:00', '2026-11-20 08:00:00', '2026-11-27 18:00:00', 60, 2, 'Published')
ON DUPLICATE KEY UPDATE EventName = VALUES(EventName);

-- Phân công BTC Event_Staff (Tối thiểu 2 cán bộ - UAT 10)
INSERT INTO Event_Staff (EventID, AccountID, StaffRole, AssignedBy) VALUES
(1, 2, 'Organizer', 2),
(1, 4, 'Checkin_Lead', 2),
(1, 6, 'Support', 2),
(2, 3, 'Organizer', 2),
(2, 7, 'Checkin_Lead', 2),
(3, 8, 'Organizer', 2),
(3, 9, 'Checkin_Lead', 2),
(3, 10, 'Support', 2),
(4, 6, 'Organizer', 2),
(4, 7, 'Checkin_Lead', 2),
(4, 4, 'MC', 2),
(5, 2, 'Organizer', 2),
(5, 3, 'Organizer', 2),
(5, 10, 'Checkin_Lead', 2),
(5, 11, 'Support', 2)
ON DUPLICATE KEY UPDATE StaffRole = VALUES(StaffRole);

-- Chiến dịch tuyển quân mẫu
INSERT INTO Recruitment_Campaign (CampaignID, ClubID, Title, Description, StartDate, EndDate, Status, TargetCount, CreatedBy) VALUES
(1, 1, 'BELL Recruitment Gen 27: Ignite Your Spark',
 'Đợt tuyển chọn thành viên thế hệ thứ 27 của CLB Tiếng Anh BELL UEH cho 4 phòng ban: Human Resources, Specialist, Communications, External Relations.',
 '2026-11-15 00:00:00', '2026-12-10 23:59:00', 'Open', 25, 4)
ON DUPLICATE KEY UPDATE Title = VALUES(Title);
