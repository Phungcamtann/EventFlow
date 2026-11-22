<?php
/**
 * EventFlow — Phạm vi CLB mà người dùng hiện tại được điểm danh / xem danh sách đăng ký.
 *  - Club Manager: CLB có ManagerID = tài khoản hiện tại.
 *  - Board Member / Event Supporter đang Active: CLB ghi trong session (ClubID).
 * Dùng chung cho registrations.php (SCR13) và checkin.php (SCR14).
 */

/**
 * Danh sách ClubID người dùng được phép thao tác.
 * @return int[]
 */
function att_allowed_club_ids(PDO $pdo): array {
    $ids = [];

    if (is_club_manager()) {
        $club = event_manager_club($pdo, (int)current_user_id());
        if ($club) {
            $ids[] = (int)$club['ClubID'];
        }
    }

    $sessionClub = $_SESSION['user']['ClubID'] ?? null;
    if ($sessionClub !== null) {
        $ids[] = (int)$sessionClub;
    }

    $uid = (int)current_user_id();
    if ($uid > 0) {
        $cmStmt = $pdo->prepare("SELECT DISTINCT ClubID FROM Club_Member WHERE AccountID = :u AND Status = 'Active'");
        $cmStmt->execute([':u' => $uid]);
        while ($c = $cmStmt->fetchColumn()) {
            $ids[] = (int)$c;
        }

        $esStmt = $pdo->prepare("SELECT DISTINCT e.ClubID FROM Event_Staff es JOIN Event e ON e.EventID = es.EventID WHERE es.AccountID = :u");
        $esStmt->execute([':u' => $uid]);
        while ($c = $esStmt->fetchColumn()) {
            $ids[] = (int)$c;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Tải sự kiện nếu thuộc một trong các CLB được phép. Trả về null nếu không.
 */
function att_load_event(PDO $pdo, int $eventId, array $clubIds): ?array {
    if ($eventId <= 0 || $clubIds === []) {
        return null;
    }
    $in = implode(',', array_map('intval', $clubIds)); // đã ép kiểu int, an toàn để nối chuỗi
    $stmt = $pdo->prepare("SELECT * FROM Event WHERE EventID = :id AND ClubID IN ({$in}) LIMIT 1");
    $stmt->execute([':id' => $eventId]);
    return $stmt->fetch() ?: null;
}

/**
 * Ghép chuỗi tìm kiếm thành mẫu LIKE an toàn (escape % và _).
 */
function att_like(string $q): string {
    return '%' . addcslashes($q, '%_\\') . '%';
}
