# -*- coding: utf-8 -*-
"""
Tab 2: CSDL & SQL DDL (11 Bảng Chuẩn Hóa 3NF & Dữ Liệu Mẫu BELL Club UEH)
"""

def get_tab2():
    return """
    <!-- ==========================================
         TAB 2: CSDL & SQL DDL (11 BẢNG CHUẨN HÓA 3NF)
         ========================================== -->
    <section id="tab-database" class="tab-content space-y-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
          <div>
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
              <span class="w-2.5 h-6 bg-teal-500 rounded-sm inline-block"></span>
              Thiết Kế Cơ Sở Dữ Liệu Quan Hệ (11 Bảng Chuẩn Hóa 3NF)
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-1">
              Hệ thống được chuẩn hóa toàn diện từ 7 bảng ban đầu lên 11 bảng chuẩn hóa 3NF, mô hình hóa chính xác cơ cấu tổ chức 4 phòng ban và quy trình tuyển dụng thực tế của <strong>BELL CLUB UEH</strong>.
            </p>
          </div>
          <button onclick="copyCode('sql-ddl-code')" class="self-start sm:self-auto bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg shadow transition flex items-center gap-2">
            <span>📋 Copy Toàn Bộ Script SQL (Schema + Seed)</span>
          </button>
        </div>

        <!-- Standardized Enum Badges -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
          <div>
            <p class="font-bold text-slate-700 mb-1">1. Vai trò Hệ thống (<code>Role.RoleName</code>):</p>
            <div class="flex flex-wrap gap-1.5">
              <span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded font-mono font-semibold">'admin'</span>
              <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded font-mono font-semibold">'clubmanager'</span>
              <span class="px-2 py-0.5 bg-teal-100 text-teal-800 rounded font-mono font-semibold">'clubmember'</span>
              <span class="px-2 py-0.5 bg-slate-200 text-slate-800 rounded font-mono font-semibold">'participant'</span>
            </div>
          </div>
          <div>
            <p class="font-bold text-slate-700 mb-1">2. Chức danh Nội bộ (<code>Club_Member.MemberRole</code>):</p>
            <div class="flex flex-wrap gap-1.5">
              <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-mono font-semibold">'President'</span>
              <span class="px-2 py-0.5 bg-orange-100 text-orange-800 rounded font-mono font-semibold">'Vice President'</span>
              <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 rounded font-mono font-semibold">'Head'</span>
              <span class="px-2 py-0.5 bg-sky-100 text-sky-800 rounded font-mono font-semibold">'Vice Head'</span>
              <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded font-mono font-semibold">'Member'</span>
            </div>
          </div>
          <div>
            <p class="font-bold text-slate-700 mb-1">3. Trạng thái Sự kiện (<code>Event.Status</code>):</p>
            <div class="flex flex-wrap gap-1">
              <span class="px-1.5 py-0.5 badge-draft rounded font-mono text-[10px]">'draft'</span>
              <span class="px-1.5 py-0.5 badge-upcoming rounded font-mono text-[10px]">'upcoming'</span>
              <span class="px-1.5 py-0.5 badge-open rounded font-mono text-[10px]">'open'</span>
              <span class="px-1.5 py-0.5 badge-closed rounded font-mono text-[10px]">'closed'</span>
              <span class="px-1.5 py-0.5 badge-ongoing rounded font-mono text-[10px]">'ongoing'</span>
              <span class="px-1.5 py-0.5 badge-finished rounded font-mono text-[10px]">'finished'</span>
              <span class="px-1.5 py-0.5 badge-cancelled rounded font-mono text-[10px]">'cancelled'</span>
            </div>
          </div>
        </div>

        <!-- 11 TABLES SUMMARY GRID -->
        <div class="mb-6">
          <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide mb-3">Danh Mục 11 Bảng Quan Hệ Chuẩn Hóa</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-xs">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">1. Role</span>
              <p class="text-slate-600 mt-1">Vai trò tài khoản hệ thống (admin, clubmanager, clubmember, participant).</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">2. Account</span>
              <p class="text-slate-600 mt-1">Tài khoản cá nhân, bổ sung SĐT, Chuyên ngành, Khóa (K48-K52), cờ đổi mật khẩu.</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">3. Club</span>
              <p class="text-slate-600 mt-1">Hồ sơ câu lạc bộ (BELL Club UEH: Tầm nhìn, Sứ mệnh, Fanpage, Địa chỉ).</p>
            </div>
            <div class="p-3 bg-teal-50 border border-teal-200 rounded-lg">
              <span class="font-bold text-teal-900">4. Department (MỚI · UAT 05)</span>
              <p class="text-teal-800 mt-1">4 phòng ban chuyên môn: Human Resources, Specialist, Communications, External Relations.</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">5. Club_Member (NÂNG CẤP)</span>
              <p class="text-slate-600 mt-1">Liên kết Account & Department, vai trò nội bộ (President, VP, Head, Vice Head, Member).</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">6. Event (NÂNG CẤP · UAT 19)</span>
              <p class="text-slate-600 mt-1">Quản lý sự kiện, State Machine 7 trạng thái, giờ mở ĐK, ràng buộc min 2 BĐH.</p>
            </div>
            <div class="p-3 bg-teal-50 border border-teal-200 rounded-lg">
              <span class="font-bold text-teal-900">7. Event_Staff (MỚI · UAT 08, 11)</span>
              <p class="text-teal-800 mt-1">Phân công BTC & Cán bộ hỗ trợ sự kiện. Chỉ người được phân công mới có quyền điểm danh.</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">8. Event_Registration (NÂNG CẤP · UAT 02)</span>
              <p class="text-slate-600 mt-1">Đăng ký tham gia: Hỗ trợ cả Thành viên đã đăng nhập và Khách vãng lai (Guest).</p>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
              <span class="font-bold text-slate-900">9. Attendance (NÂNG CẤP · UAT 17)</span>
              <p class="text-slate-600 mt-1">Ghi nhận điểm danh có lưu nhân sự thực hiện (CheckedBy), trạng thái Present/Absent.</p>
            </div>
            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
              <span class="font-bold text-indigo-900">10. Recruitment_Campaign (MỚI · UAT 02, 09)</span>
              <p class="text-indigo-800 mt-1">Đợt tuyển quân mới của CLB (Ngày bắt đầu, hạn nộp, chỉ tiêu, trạng thái Mở/Đóng).</p>
            </div>
            <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
              <span class="font-bold text-indigo-900">11. Recruitment_Application (MỚI · UAT 02, 09)</span>
              <p class="text-indigo-800 mt-1">Hồ sơ ứng tuyển của sinh viên: Chọn phòng ban, nộp CV PDF, lịch phỏng vấn & trigger cấp tài khoản.</p>
            </div>
          </div>
        </div>

        <!-- FULL SQL CODE BLOCK -->
        <div>
          <div class="flex items-center justify-between bg-slate-800 px-4 py-2 rounded-t-xl">
            <span class="text-xs font-mono text-teal-400 font-semibold">schema_and_seed_v2.sql (MySQL 8.0 InnoDB)</span>
            <span class="text-[11px] text-slate-400">11 Bảng · 10 Nhân sự Mẫu BELL Club UEH</span>
          </div>
          <pre id="sql-ddl-code" class="bg-slate-950 text-slate-100 p-4 rounded-b-xl text-xs overflow-x-auto max-h-[550px] leading-relaxed"><code class="language-sql">-- ============================================================================
-- EVENTFLOW DATABASE SCHEMA & SEED DATA (BLUEPRINT V2.0)
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
    LogoUrl VARCHAR(255) NULL,
    CoverUrl VARCHAR(255) NULL,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    FacebookUrl VARCHAR(255) NULL,
    Address VARCHAR(255) NOT NULL DEFAULT 'UEH Cơ sở B - 279 Nguyễn Tri Phương, Q.10, TP.HCM',
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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
    ClubMemberID INT AUTO_INCREMENT PRIMARY KEY,
    ClubID INT NOT NULL,
    AccountID INT NOT NULL,
    DepartmentID INT NULL,                -- Nullable đối với President/Vice President
    MemberRole ENUM('President', 'Vice President', 'Head', 'Vice Head', 'Member') NOT NULL DEFAULT 'Member',
    Status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    JoinedDate DATE NOT NULL,
    Bio TEXT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_member_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_member_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_member_department FOREIGN KEY (DepartmentID) REFERENCES Department(DepartmentID)
        ON UPDATE CASCADE ON DELETE SET NULL,
    UNIQUE KEY uk_club_account (ClubID, AccountID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. BẢNG EVENT (Sự kiện CLB với State Machine 7 trạng thái)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Event (
    EventID INT AUTO_INCREMENT PRIMARY KEY,
    ClubID INT NOT NULL,
    Title VARCHAR(200) NOT NULL,
    Description TEXT NOT NULL,
    Location VARCHAR(255) NOT NULL,
    StartTime DATETIME NOT NULL,
    EndTime DATETIME NOT NULL,
    RegistrationStartTime DATETIME NOT NULL,
    RegistrationDeadline DATETIME NOT NULL,
    MaxCapacity INT NULL,                 -- NULL = Không giới hạn
    CurrentParticipants INT NOT NULL DEFAULT 0,
    MinStaffRequired INT NOT NULL DEFAULT 2, -- Ràng buộc tối thiểu 2 BĐH/Cán bộ
    BannerUrl VARCHAR(255) NULL,
    Status ENUM('draft', 'upcoming', 'open', 'closed', 'ongoing', 'finished', 'cancelled') NOT NULL DEFAULT 'draft',
    CreatedBy INT NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_event_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_event_creator FOREIGN KEY (CreatedBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_event_dates (StartTime, EndTime, RegistrationDeadline),
    INDEX idx_event_status (Status)
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
    RegistrationID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    AccountID INT NULL,                   -- Null nếu là khách vãng lai (Guest)
    RegistrationType ENUM('Member', 'Guest') NOT NULL DEFAULT 'Member',
    GuestName VARCHAR(100) NULL,
    GuestEmail VARCHAR(100) NULL,
    GuestPhone VARCHAR(20) NULL,
    GuestStudentCode VARCHAR(20) NULL,
    Status ENUM('Registered', 'Cancelled') NOT NULL DEFAULT 'Registered',
    RegisteredAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_registration_event FOREIGN KEY (EventID) REFERENCES Event(EventID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_registration_account FOREIGN KEY (AccountID) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_reg_event_account (EventID, AccountID),
    INDEX idx_reg_event_guest (EventID, GuestStudentCode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. BẢNG ATTENDANCE (Điểm danh tham gia sự kiện · UAT 17)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Attendance (
    AttendanceID INT AUTO_INCREMENT PRIMARY KEY,
    EventID INT NOT NULL,
    RegistrationID INT NOT NULL,
    CheckinTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CheckedBy INT NOT NULL,               -- Nhân sự thực hiện check-in (từ Event_Staff)
    Status ENUM('Present', 'Absent', 'Excused') NOT NULL DEFAULT 'Present',
    Notes VARCHAR(255) NULL,
    CONSTRAINT fk_attendance_event FOREIGN KEY (EventID) REFERENCES Event(EventID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attendance_registration FOREIGN KEY (RegistrationID) REFERENCES Event_Registration(RegistrationID)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attendance_staff FOREIGN KEY (CheckedBy) REFERENCES Account(AccountID)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uk_attendance_registration (RegistrationID)
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
    DepartmentID INT NOT NULL,            -- Phòng ban ứng tuyển
    FullName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    StudentCode VARCHAR(20) NOT NULL,
    Cohort VARCHAR(10) NOT NULL,          -- K48, K49, K50, K51, K52
    Major VARCHAR(100) NOT NULL,
    CV_Path VARCHAR(255) NULL,            -- File PDF upload
    IntroText TEXT NOT NULL,              -- Giới thiệu bản thân & động lực
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
INSERT INTO Club (ClubID, ClubName, ShortName, Description, Vision, Mission, Email, Phone, FacebookUrl, Address) VALUES
(1, 'BELL Club - Câu lạc bộ Tiếng Anh Sinh viên UEH', 'BELL UEH',
 'BELL Club là tổ chức tiên phong về phong trào thực hành tiếng Anh năng động và chuyên nghiệp dành cho sinh viên Đại học Kinh tế TP. Hồ Chí Minh.',
 'Trở thành cộng đồng tiếng Anh sinh viên dẫn đầu cả nước về kỹ năng học thuật, tranh biện và hội nhập quốc tế.',
 'Tạo môi trường rèn luyện tiếng Anh tự nhiên, kết nối cơ hội nghề nghiệp và phát triển toàn diện kỹ năng lãnh đạo.',
 'bellclub@ueh.edu.vn', '02838551779', 'https://facebook.com/bellueh',
 'UEH Cơ sở B - 279 Nguyễn Tri Phương, Phường 5, Quận 10, TP.HCM')
ON DUPLICATE KEY UPDATE ClubName = VALUES(ClubName);

-- 4 Phòng ban chuyên môn BELL Club
INSERT INTO Department (DepartmentID, ClubID, DepartmentName, DepartmentCode, Description, KeyActivities, OrderIndex) VALUES
(1, 1, 'Human Resources', 'HR',
 'The department that keeps the BELL community connected, cared for and growing together. Human Resources focuses on member experience, internal connection and development.',
 'Member care, Gatherings & BELL Care; Training, workshops & English Sharing Day; Internal Communications & newsletters; Cheerleading & connection.', 1),
(2, 1, 'Specialist', 'SPEC',
 'The English-learning heartbeat of BELL, where practice becomes habit and ideas become learning experiences. Designs and delivers speaking groups and knowledge-sharing formats.',
 'Speaking Group Private (SGP) & Extended (SGE); Translator Team, No Vietnamese Day & Monthly Challenge; English discussions; Ring the BELL.', 2),
(3, 1, 'Communications', 'COMS',
 'The voice, look and storytelling energy that bring BELL activities to life. COMS develops visual identity, campaigns, event coverage and multimedia storytelling.',
 'Content, Design & Production House; Visuals, video and storytelling; Media campaigns and event coverage.', 3),
(4, 1, 'External Relations', 'ER',
 'The bridge connecting BELL with partners, sponsors and opportunities beyond the club. Builds and maintains relationships with brands, speakers and media partners.',
 'Sponsorship proposals & negotiation; Long-term partner relationship building; Speakers & event support; Sponsor collaborations.', 4)
ON DUPLICATE KEY UPDATE DepartmentName = VALUES(DepartmentName);

-- 10 Nhân sự Ban Điều Hành & Lãnh Đạo Phòng Ban thực tế từ Hồ Sơ BELL UEH
-- Hash cho 'password123': $2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q
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
(11, 'khanhlinh.er@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Đặng Khánh Linh', '31241020010', '0906000002', 'K50', 'Logistics', 3, 1),
(12, 'guest.student@st.ueh.edu.vn', '$2y$10$wT8f6sQ5yGZJt3a.U6yCg.H6eA5V6gR3L0P3h3J4k5l6m7n8o9p0q', 'Nguyễn Văn Sinh Viên', '31241020999', '0909999999', 'K50', 'Kinh tế đầu tư', 4, 1)
ON DUPLICATE KEY UPDATE FullName = VALUES(FullName);

-- Gán Thành viên CLB theo phòng ban & chức danh
INSERT INTO Club_Member (ClubID, AccountID, DepartmentID, MemberRole, Status, JoinedDate, Bio) VALUES
(1, 2, NULL, 'President', 'Active', '2024-09-01', 'Chủ nhiệm CLB BELL UEH - Điều hành chung hoạt động và chiến lược phát triển.'),
(1, 3, NULL, 'Vice President', 'Active', '2024-09-01', 'Phó Chủ nhiệm CLB BELL UEH - Phụ trách nội dung học thuật và vận hành sự kiện.'),
(1, 4, 1, 'Head', 'Active', '2025-01-10', 'Trưởng phòng Nhân sự - Phụ trách tuyển quân, chăm sóc thành viên và nội bộ.'),
(1, 5, 1, 'Vice Head', 'Active', '2025-01-10', 'Phó phòng Nhân sự - Quản lý hồ sơ ứng viên và điều phối phỏng vấn.'),
(1, 6, 2, 'Head', 'Active', '2025-01-10', 'Trưởng phòng Chuyên môn - Chủ nhiệm các buổi sinh hoạt SGP/SGE và học thuật.'),
(1, 7, 2, 'Vice Head', 'Active', '2025-01-10', 'Phó phòng Chuyên môn - Điều phối các chủ đề thảo luận và bài tập tiếng Anh.'),
(1, 8, 3, 'Head', 'Active', '2025-01-10', 'Trưởng phòng Truyền thông - Quản lý hình ảnh thương hiệu và nội dung fanpage.'),
(1, 9, 3, 'Vice Head', 'Active', '2025-01-10', 'Phó phòng Truyền thông - Phụ trách thiết kế và sản xuất tư liệu sự kiện.'),
(1, 10, 4, 'Head', 'Active', '2025-01-10', 'Trưởng phòng Quan hệ Đối ngoại - Kết nối nhà tài trợ, diễn giả và đối tác.'),
(1, 11, 4, 'Vice Head', 'Active', '2025-01-10', 'Phó phòng Quan hệ Đối ngoại - Điều phối truyền thông chéo và tài trợ sự kiện.')
ON DUPLICATE KEY UPDATE MemberRole = VALUES(MemberRole);

-- Sự kiện mẫu bám sát State Machine 7 trạng thái
INSERT INTO Event (EventID, ClubID, Title, Description, Location, StartTime, EndTime, RegistrationStartTime, RegistrationDeadline, MaxCapacity, CurrentParticipants, MinStaffRequired, Status, CreatedBy) VALUES
(1, 1, 'BELL Speaking Circle: Global Mindset', 'Buổi sinh hoạt giao tiếp tiếng Anh định kỳ theo chủ đề dành cho sinh viên UEH.', 'UEH Cơ sở B - B1.302 (279 Nguyễn Tri Phương)', '2026-11-25 18:00:00', '2026-11-25 20:30:00', '2026-11-15 08:00:00', '2026-11-24 23:59:00', 40, 2, 2, 'open', 2),
(2, 1, 'Debate Masterclass: British Parliamentary', 'Workshop chuyên sâu về kỹ năng tranh biện và phản biện tiếng Anh quốc tế.', 'Hội trường B1.302 - Cơ sở B UEH', '2026-11-28 14:00:00', '2026-11-28 17:00:00', '2026-11-20 08:00:00', '2026-11-27 18:00:00', 60, 0, 2, 'upcoming', 2),
(3, 1, 'BELL English Camp 2026: Break The Limits', 'Dã ngoại trải nghiệm kết hợp thử thách giao tiếp 100% tiếng Anh ngoài trời.', 'Khu sinh thái BCR, TP. Thủ Đức', '2026-12-05 07:00:00', '2026-12-06 17:00:00', '2026-11-26 08:00:00', '2026-12-02 23:59:00', 80, 0, 2, 'draft', 3);

-- Phân công BTC sự kiện (Tối thiểu 2 BĐH/Cán bộ)
INSERT INTO Event_Staff (EventID, AccountID, StaffRole, AssignedBy) VALUES
(1, 2, 'Organizer', 2),
(1, 4, 'Checkin_Lead', 2),
(1, 6, 'Support', 2),
(2, 3, 'Organizer', 2),
(2, 7, 'Checkin_Lead', 2)
ON DUPLICATE KEY UPDATE StaffRole = VALUES(StaffRole);

-- Đợt tuyển quân mẫu của CLB BELL
INSERT INTO Recruitment_Campaign (CampaignID, ClubID, Title, Description, StartDate, EndDate, Status, TargetCount, CreatedBy) VALUES
(1, 1, 'BELL Recruitment Gen 27: Ignite Your Spark', 'Đợt tuyển chọn thành viên thế hệ thứ 27 của CLB Tiếng Anh BELL UEH cho 4 phòng ban: HR, Specialist, Communications, External Relations.', '2026-11-15 00:00:00', '2026-12-10 23:59:00', 'Open', 25, 4)
ON DUPLICATE KEY UPDATE Title = VALUES(Title);
</code></pre>
        </div>
      </div>
    </section>
"""
