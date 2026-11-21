<?php
/**
 * Migration Script: UAT 05 - Cơ cấu BELL CLUB UEH & Chuẩn hóa 11 Bảng CSDL
 * EventFlow Sprint 7
 */

require_once __DIR__ . '/../../app/config/database.php';

echo "=== BẮT ĐẦU MIGRATION UAT 05 (11 BẢNG & SEED DATA BELL CLUB) ===\n";

try {
    $pdo = get_db_connection();

    // Helper: Kiểm tra cột đã tồn tại chưa
    $hasColumn = function($table, $column) use ($pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    };

    // Helper: Kiểm tra bảng đã tồn tại chưa
    $hasTable = function($table) use ($pdo) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    };

    // 1. CẬP NHẬT BẢNG ACCOUNT
    echo "1. Kiểm tra & Cập nhật bảng Account...\n";
    if (!$hasColumn('account', 'Phone')) {
        $pdo->exec("ALTER TABLE Account ADD COLUMN Phone VARCHAR(20) NULL AFTER StudentCode");
        echo "   + Đã thêm cột Phone\n";
    }
    if (!$hasColumn('account', 'Cohort')) {
        $pdo->exec("ALTER TABLE Account ADD COLUMN Cohort VARCHAR(10) NULL AFTER Phone");
        echo "   + Đã thêm cột Cohort\n";
    }
    if (!$hasColumn('account', 'Major')) {
        $pdo->exec("ALTER TABLE Account ADD COLUMN Major VARCHAR(100) NULL AFTER Cohort");
        echo "   + Đã thêm cột Major\n";
    }
    if (!$hasColumn('account', 'AvatarUrl')) {
        $pdo->exec("ALTER TABLE Account ADD COLUMN AvatarUrl VARCHAR(255) NULL AFTER Major");
        echo "   + Đã thêm cột AvatarUrl\n";
    }
    if (!$hasColumn('account', 'PasswordMustChange')) {
        $pdo->exec("ALTER TABLE Account ADD COLUMN PasswordMustChange TINYINT(1) NOT NULL DEFAULT 0 AFTER RoleID");
        echo "   + Đã thêm cột PasswordMustChange\n";
    }

    // 2. CẬP NHẬT BẢNG CLUB
    echo "2. Kiểm tra & Cập nhật bảng Club...\n";
    if (!$hasColumn('club', 'ShortName')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN ShortName VARCHAR(50) NOT NULL DEFAULT 'BELL UEH' AFTER ClubName");
        echo "   + Đã thêm cột ShortName\n";
    }
    if (!$hasColumn('club', 'Vision')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN Vision TEXT NULL AFTER Description");
        echo "   + Đã thêm cột Vision\n";
    }
    if (!$hasColumn('club', 'Mission')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN Mission TEXT NULL AFTER Vision");
        echo "   + Đã thêm cột Mission\n";
    }
    if (!$hasColumn('club', 'Email')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN Email VARCHAR(100) NULL AFTER ContactInfo");
        echo "   + Đã thêm cột Email\n";
    }
    if (!$hasColumn('club', 'Phone')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN Phone VARCHAR(20) NULL AFTER Email");
        echo "   + Đã thêm cột Phone\n";
    }
    if (!$hasColumn('club', 'FacebookUrl')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN FacebookUrl VARCHAR(255) NULL AFTER Phone");
        echo "   + Đã thêm cột FacebookUrl\n";
    }
    if (!$hasColumn('club', 'Address')) {
        $pdo->exec("ALTER TABLE Club ADD COLUMN Address VARCHAR(255) NOT NULL DEFAULT 'UEH Cơ sở B - 279 Nguyễn Tri Phương, Phường 5, Quận 10, TP.HCM' AFTER FacebookUrl");
        echo "   + Đã thêm cột Address\n";
    }

    // 3. TẠO BẢNG DEPARTMENT (MỚI)
    echo "3. Kiểm tra & Tạo bảng Department...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Department (
            DepartmentID INT AUTO_INCREMENT PRIMARY KEY,
            ClubID INT NOT NULL,
            DepartmentName VARCHAR(100) NOT NULL,
            DepartmentCode VARCHAR(20) NOT NULL,
            Description TEXT NOT NULL,
            KeyActivities TEXT NULL,
            OrderIndex INT NOT NULL DEFAULT 1,
            CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_department_club FOREIGN KEY (ClubID) REFERENCES Club(ClubID)
                ON UPDATE CASCADE ON DELETE CASCADE,
            UNIQUE KEY uk_club_dept_code (ClubID, DepartmentCode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "   + Bảng Department sẵn sàng\n";

    // 4. CẬP NHẬT BẢNG CLUB_MEMBER
    echo "4. Kiểm tra & Cập nhật bảng Club_Member...\n";
    if (!$hasColumn('club_member', 'DepartmentID')) {
        $pdo->exec("ALTER TABLE Club_Member ADD COLUMN DepartmentID INT NULL AFTER ClubID");
        $pdo->exec("ALTER TABLE Club_Member ADD CONSTRAINT fk_member_department FOREIGN KEY (DepartmentID) REFERENCES Department(DepartmentID) ON UPDATE CASCADE ON DELETE SET NULL");
        echo "   + Đã thêm cột DepartmentID & Foreign Key\n";
    }
    if (!$hasColumn('club_member', 'Bio')) {
        $pdo->exec("ALTER TABLE Club_Member ADD COLUMN Bio TEXT NULL AFTER JoinedDate");
        echo "   + Đã thêm cột Bio\n";
    }
    // Mở rộng độ dài MemberRole nếu cần
    $pdo->exec("ALTER TABLE Club_Member MODIFY MemberRole VARCHAR(50) NOT NULL DEFAULT 'Member'");

    // 5. CẬP NHẬT BẢNG EVENT
    echo "5. Kiểm tra & Cập nhật bảng Event...\n";
    if (!$hasColumn('event', 'MinStaffRequired')) {
        $pdo->exec("ALTER TABLE Event ADD COLUMN MinStaffRequired INT NOT NULL DEFAULT 2 AFTER MaxCapacity");
        echo "   + Đã thêm cột MinStaffRequired\n";
    }

    // 6. TẠO BẢNG EVENT_STAFF (MỚI)
    echo "6. Kiểm tra & Tạo bảng Event_Staff...\n";
    $pdo->exec("
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
    ");
    echo "   + Bảng Event_Staff sẵn sàng\n";

    // 7. CẬP NHẬT BẢNG EVENT_REGISTRATION
    echo "7. Kiểm tra & Cập nhật bảng Event_Registration...\n";
    // Cho phép AccountID nullable cho khách vãng lai
    $pdo->exec("ALTER TABLE Event_Registration MODIFY AccountID INT NULL");
    if (!$hasColumn('event_registration', 'RegistrationType')) {
        $pdo->exec("ALTER TABLE Event_Registration ADD COLUMN RegistrationType ENUM('Member', 'Guest') NOT NULL DEFAULT 'Member' AFTER AccountID");
        echo "   + Đã thêm cột RegistrationType\n";
    }
    if (!$hasColumn('event_registration', 'GuestName')) {
        $pdo->exec("ALTER TABLE Event_Registration ADD COLUMN GuestName VARCHAR(100) NULL AFTER RegistrationType");
        echo "   + Đã thêm cột GuestName\n";
    }
    if (!$hasColumn('event_registration', 'GuestEmail')) {
        $pdo->exec("ALTER TABLE Event_Registration ADD COLUMN GuestEmail VARCHAR(100) NULL AFTER GuestName");
        echo "   + Đã thêm cột GuestEmail\n";
    }
    if (!$hasColumn('event_registration', 'GuestPhone')) {
        $pdo->exec("ALTER TABLE Event_Registration ADD COLUMN GuestPhone VARCHAR(20) NULL AFTER GuestEmail");
        echo "   + Đã thêm cột GuestPhone\n";
    }
    if (!$hasColumn('event_registration', 'GuestStudentCode')) {
        $pdo->exec("ALTER TABLE Event_Registration ADD COLUMN GuestStudentCode VARCHAR(20) NULL AFTER GuestPhone");
        echo "   + Đã thêm cột GuestStudentCode\n";
    }

    // 8. TẠO BẢNG RECRUITMENT_CAMPAIGN (MỚI)
    echo "8. Kiểm tra & Tạo bảng Recruitment_Campaign...\n";
    $pdo->exec("
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
    ");
    echo "   + Bảng Recruitment_Campaign sẵn sàng\n";

    // 9. TẠO BẢNG RECRUITMENT_APPLICATION (MỚI)
    echo "9. Kiểm tra & Tạo bảng Recruitment_Application...\n";
    $pdo->exec("
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
    ");
    echo "   + Bảng Recruitment_Application sẵn sàng\n";

    // 10. NẠP SEED DATA BELL CLUB UEH & 4 PHÒNG BAN & 10 NHÂN SỰ LÃNH ĐẠO
    echo "10. Nạp / Đồng bộ Seed Data BELL CLUB UEH...\n";

    // Seed Club
    $clubStmt = $pdo->prepare("
        INSERT INTO Club (ClubID, ClubName, ShortName, Description, Vision, Mission, ContactInfo, Email, Phone, FacebookUrl, Address, ManagerID)
        VALUES (1, 'BELL Club - Câu lạc bộ Tiếng Anh Sinh viên UEH', 'BELL UEH',
        'BELL Club là tổ chức tiên phong về phong trào thực hành tiếng Anh năng động và chuyên nghiệp dành cho sinh viên Đại học Kinh tế TP. Hồ Chí Minh.',
        'Trở thành cộng đồng tiếng Anh sinh viên dẫn đầu cả nước về kỹ năng học thuật, tranh biện và hội nhập quốc tế.',
        'Tạo môi trường rèn luyện tiếng Anh tự nhiên, kết nối cơ hội nghề nghiệp và phát triển toàn diện kỹ năng lãnh đạo.',
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
            ManagerID = VALUES(ManagerID)
    ");
    $clubStmt->execute();
    echo "   + Hồ sơ Club đã cập nhật\n";

    // Seed 4 Departments
    $departments = [
        [
            'id' => 1, 'code' => 'HR', 'name' => 'Human Resources',
            'desc' => 'The department that keeps the BELL community connected, cared for and growing together. Human Resources focuses on member experience, internal connection and development. From recruitment journeys and bonding activities to internal care initiatives, HR helps BELLers feel welcomed, supported and ready to contribute.',
            'activities' => "Member care, Gatherings & BELL Care\nTraining, workshops & English Sharing Day\nInternal Communications & newsletters\nCheerleading, appreciation & connection",
            'order' => 1
        ],
        [
            'id' => 2, 'code' => 'SPEC', 'name' => 'Specialist',
            'desc' => 'The English-learning heartbeat of BELL, where practice becomes habit and ideas become learning experiences. Specialist designs and delivers activities that help members and students use English more confidently. Through speaking groups, translation initiatives and accessible knowledge-sharing formats, this department turns learning into something practical and lively.',
            'activities' => "Speaking Group Private (SGP) & Extended (SGE)\nTranslator Team, No Vietnamese Day & Monthly Challenge\nEnglish discussions and skill sharing\nRing the BELL & interactive practice",
            'order' => 2
        ],
        [
            'id' => 3, 'code' => 'COMS', 'name' => 'Communications',
            'desc' => 'The voice, look and storytelling energy that bring BELL activities to life. Communications develops BELL’s visual identity and public-facing content. From campaigns and event coverage to creative projects and multimedia storytelling, COMS helps people see, feel and remember the BELL spirit.',
            'activities' => "Content, Design & Production House\nVisuals, video and storytelling\nMedia campaigns and event coverage",
            'order' => 3
        ],
        [
            'id' => 4, 'code' => 'ER', 'name' => 'External Relations',
            'desc' => 'The bridge connecting BELL with partners, sponsors and opportunities beyond the club. External Relations builds and maintains relationships with brands, partners and outside stakeholders. By supporting sponsorship, collaboration and long-term connection, ER helps BELL expand its reach and create stronger opportunities for members and programs.',
            'activities' => "Sponsorship proposals & negotiation\nLong-term partner relationship building\nSpeakers, partners & event support\nSponsor and media collaborations",
            'order' => 4
        ]
    ];

    $deptStmt = $pdo->prepare("
        INSERT INTO Department (DepartmentID, ClubID, DepartmentName, DepartmentCode, Description, KeyActivities, OrderIndex)
        VALUES (:id, 1, :name, :code, :desc, :act, :order)
        ON DUPLICATE KEY UPDATE 
            DepartmentName = VALUES(DepartmentName),
            DepartmentCode = VALUES(DepartmentCode),
            Description = VALUES(Description),
            KeyActivities = VALUES(KeyActivities),
            OrderIndex = VALUES(OrderIndex)
    ");
    foreach ($departments as $d) {
        $deptStmt->execute([
            ':id' => $d['id'],
            ':name' => $d['name'],
            ':code' => $d['code'],
            ':desc' => $d['desc'],
            ':act' => $d['activities'],
            ':order' => $d['order']
        ]);
    }
    echo "   + 4 Phòng ban chuyên môn đã cập nhật\n";

    // 10 Nhân sự Lãnh đạo thực tế từ PDF
    $defaultHash = password_hash('password123', PASSWORD_BCRYPT);
    $leaders = [
        [
            'account_id' => 2,
            'email' => 'minhanh.president@st.ueh.edu.vn',
            'name' => 'Nguyễn Minh Anh',
            'mssv' => '31241020001',
            'phone' => '0902000001',
            'cohort' => 'K48',
            'major' => 'Kinh doanh quốc tế',
            'role_id' => 2, // clubmanager
            'member_role' => 'President',
            'dept_id' => null,
            'bio' => 'Chủ nhiệm CLB BELL UEH — Chịu trách nhiệm điều hành chung, định hướng chiến lược học thuật và đối ngoại của CLB.'
        ],
        [
            'account_id' => 3,
            'email' => 'hoangnam.vp@st.ueh.edu.vn',
            'name' => 'Trần Hoàng Nam',
            'mssv' => '31241020002',
            'phone' => '0902000002',
            'cohort' => 'K48',
            'major' => 'Thương mại điện tử',
            'role_id' => 2, // clubmanager
            'member_role' => 'Vice President',
            'dept_id' => null,
            'bio' => 'Phó Chủ nhiệm CLB BELL UEH — Phụ trách quản lý vận hành các ban chuyên môn và chất lượng sự kiện.'
        ],
        [
            'account_id' => 4,
            'email' => 'thutrang.hr@st.ueh.edu.vn',
            'name' => 'Phạm Thu Trang',
            'mssv' => '31241020003',
            'phone' => '0903000001',
            'cohort' => 'K49',
            'major' => 'Quản trị nhân lực',
            'role_id' => 3, // clubmember
            'member_role' => 'Head',
            'dept_id' => 1,
            'bio' => 'Trưởng phòng Nhân sự — Điều phối chiến dịch tuyển quân, gắn kết nội bộ và đào tạo thành viên.'
        ],
        [
            'account_id' => 5,
            'email' => 'vanbinh.hr@st.ueh.edu.vn',
            'name' => 'Nguyễn Văn Bình',
            'mssv' => '31241020004',
            'phone' => '0903000002',
            'cohort' => 'K49',
            'major' => 'Quản trị kinh doanh',
            'role_id' => 3,
            'member_role' => 'Vice Head',
            'dept_id' => 1,
            'bio' => 'Phó phòng Nhân sự — Quản lý hồ sơ ứng tuyển, xếp lịch phỏng vấn và hỗ trợ văn hóa CLB.'
        ],
        [
            'account_id' => 6,
            'email' => 'minhkhang.spec@st.ueh.edu.vn',
            'name' => 'Trần Minh Khang',
            'mssv' => '31241020005',
            'phone' => '0904000001',
            'cohort' => 'K49',
            'major' => 'Ngôn ngữ Anh thương mại',
            'role_id' => 3,
            'member_role' => 'Head',
            'dept_id' => 2,
            'bio' => 'Trưởng phòng Chuyên môn — Thiết kế hoạt động Speaking Group (SGP/SGE), điều phối nội dung học thuật.'
        ],
        [
            'account_id' => 7,
            'email' => 'thaovy.spec@st.ueh.edu.vn',
            'name' => 'Lê Thảo Vy',
            'mssv' => '31241020006',
            'phone' => '0904000002',
            'cohort' => 'K50',
            'major' => 'Tài chính quốc tế',
            'role_id' => 3,
            'member_role' => 'Vice Head',
            'dept_id' => 2,
            'bio' => 'Phó phòng Chuyên môn — Phụ trách Translator Team, No Vietnamese Day & Monthly Challenge.'
        ],
        [
            'account_id' => 8,
            'email' => 'hoanglong.coms@st.ueh.edu.vn',
            'name' => 'Nguyễn Hoàng Long',
            'mssv' => '31241020007',
            'phone' => '0905000001',
            'cohort' => 'K49',
            'major' => 'Truyền thông số',
            'role_id' => 3,
            'member_role' => 'Head',
            'dept_id' => 3,
            'bio' => 'Trưởng phòng Truyền thông — Định hình nhận diện thương hiệu BELL, chiến dịch truyền thông đa phương tiện.'
        ],
        [
            'account_id' => 9,
            'email' => 'ngocmai.coms@st.ueh.edu.vn',
            'name' => 'Phạm Ngọc Mai',
            'mssv' => '31241020008',
            'phone' => '0905000002',
            'cohort' => 'K50',
            'major' => 'Marketing',
            'role_id' => 3,
            'member_role' => 'Vice Head',
            'dept_id' => 3,
            'bio' => 'Phó phòng Truyền thông — Phụ trách sản xuất hình ảnh, video và quản lý nội dung fanpage.'
        ],
        [
            'account_id' => 10,
            'email' => 'thanhdat.er@st.ueh.edu.vn',
            'name' => 'Võ Thành Đạt',
            'mssv' => '31241020009',
            'phone' => '0906000001',
            'cohort' => 'K49',
            'major' => 'Quan hệ công chúng',
            'role_id' => 3,
            'member_role' => 'Head',
            'dept_id' => 4,
            'bio' => 'Trưởng phòng Quan hệ Đối ngoại — Kết nối nhà tài trợ, diễn giả, doanh nghiệp và đối tác đối ngoại.'
        ],
        [
            'account_id' => 11,
            'email' => 'khanhlinh.er@st.ueh.edu.vn',
            'name' => 'Đặng Khánh Linh',
            'mssv' => '31241020010',
            'phone' => '0906000002',
            'cohort' => 'K50',
            'major' => 'Logistics',
            'role_id' => 3,
            'member_role' => 'Vice Head',
            'dept_id' => 4,
            'bio' => 'Phó phòng Quan hệ Đối ngoại — Điều phối tài trợ sự kiện, quyền lợi truyền thông và chăm sóc đối tác.'
        ]
    ];

    $accStmt = $pdo->prepare("
        INSERT INTO Account (AccountID, Email, PasswordHash, FullName, StudentCode, Phone, Cohort, Major, RoleID, IsActive)
        VALUES (:id, :email, :pwd, :name, :code, :phone, :cohort, :major, :role, 1)
        ON DUPLICATE KEY UPDATE
            Email = VALUES(Email),
            FullName = VALUES(FullName),
            StudentCode = VALUES(StudentCode),
            Phone = VALUES(Phone),
            Cohort = VALUES(Cohort),
            Major = VALUES(Major),
            RoleID = VALUES(RoleID),
            IsActive = 1
    ");

    $memStmt = $pdo->prepare("
        INSERT INTO Club_Member (ClubID, AccountID, DepartmentID, MemberRole, Status, JoinedDate, Bio)
        VALUES (1, :aid, :did, :role, 'Active', '2025-01-15', :bio)
        ON DUPLICATE KEY UPDATE
            DepartmentID = VALUES(DepartmentID),
            MemberRole = VALUES(MemberRole),
            Status = 'Active',
            Bio = VALUES(Bio)
    ");

    foreach ($leaders as $l) {
        $accStmt->execute([
            ':id' => $l['account_id'],
            ':email' => $l['email'],
            ':pwd' => $defaultHash,
            ':name' => $l['name'],
            ':code' => $l['mssv'],
            ':phone' => $l['phone'],
            ':cohort' => $l['cohort'],
            ':major' => $l['major'],
            ':role' => $l['role_id']
        ]);

        $memStmt->execute([
            ':aid' => $l['account_id'],
            ':did' => $l['dept_id'],
            ':role' => $l['member_role'],
            ':bio' => $l['bio']
        ]);
    }
    echo "   + 10 Cán bộ lãnh đạo thực tế đã được nạp thành công\n";

    // Seed Phân công sự kiện mẫu (Event_Staff - tối thiểu 2 cán bộ)
    echo "11. Nạp phân công ban tổ chức Event_Staff mẫu...\n";
    $staffStmt = $pdo->prepare("
        INSERT INTO Event_Staff (EventID, AccountID, StaffRole, AssignedBy)
        VALUES (:eid, :aid, :role, 2)
        ON DUPLICATE KEY UPDATE StaffRole = VALUES(StaffRole)
    ");
    // Sự kiện 1: Speaking Circle
    $staffStmt->execute([':eid' => 1, ':aid' => 2, ':role' => 'Organizer']);
    $staffStmt->execute([':eid' => 1, ':aid' => 4, ':role' => 'Checkin_Lead']);
    $staffStmt->execute([':eid' => 1, ':aid' => 6, ':role' => 'Support']);
    // Sự kiện 2: Debate Masterclass
    $staffStmt->execute([':eid' => 2, ':aid' => 3, ':role' => 'Organizer']);
    $staffStmt->execute([':eid' => 2, ':aid' => 7, ':role' => 'Checkin_Lead']);
    echo "   + Phân công Event_Staff mẫu đã cập nhật\n";

    // Seed Chiến dịch tuyển quân mẫu
    echo "12. Nạp Chiến dịch Tuyển quân Recruitment_Campaign mẫu...\n";
    $campStmt = $pdo->prepare("
        INSERT INTO Recruitment_Campaign (CampaignID, ClubID, Title, Description, StartDate, EndDate, Status, TargetCount, CreatedBy)
        VALUES (1, 1, 'BELL Recruitment Gen 27: Ignite Your Spark',
        'Đợt tuyển chọn thành viên thế hệ thứ 27 của CLB Tiếng Anh BELL UEH cho 4 phòng ban: Human Resources, Specialist, Communications, External Relations.',
        '2026-11-15 00:00:00', '2026-12-10 23:59:00', 'Open', 25, 4)
        ON DUPLICATE KEY UPDATE
            Title = VALUES(Title),
            Description = VALUES(Description),
            Status = VALUES(Status),
            TargetCount = VALUES(TargetCount)
    ");
    $campStmt->execute();
    echo "   + Chiến dịch Tuyển quân Gen 27 đã cập nhật\n";

    echo "\n=== MIGRATION UAT 05 HOÀN TẤT THÀNH CÔNG (11 BẢNG INNODB) ===\n";

} catch (Exception $e) {
    echo "LỖI MIGRATION: " . $e->getMessage() . "\n";
    exit(1);
}
