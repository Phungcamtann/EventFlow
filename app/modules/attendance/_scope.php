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
    if (is_authorized_club_member() && $sessionClub !== null) {
        $ids[] = (int)$sessionClub;
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

