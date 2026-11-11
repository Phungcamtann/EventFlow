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
