<?php
/**
 * EventFlow — Cấu hình và Kết nối CSDL MySQL (PDO)
 * Bám sát Three-Tier Architecture & Chuẩn XAMPP Localhost
 */

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'eventflow_db');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Trả về đối tượng PDO kết nối tới CSDL MySQL/MariaDB
 * Hỗ trợ tự động nhận diện port 3307 (XAMPP cấu hình riêng khi 3306 bận) hoặc port 3306 chuẩn
 * 
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $ports = [];
        if (getenv('DB_PORT')) {
            $ports[] = (int)getenv('DB_PORT');
        } else {
            // Thử port 3307 (XAMPP my.ini) trước, sau đó fallback sang 3306
            $ports = [3307, 3306];
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        $lastException = null;
        foreach ($ports as $port) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                $port,
                DB_NAME,
                DB_CHARSET
            );

            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                break;
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        if ($pdo === null && $lastException !== null) {
            error_log('[EventFlow DB Error] ' . $lastException->getMessage());
            die('<div style="font-family:sans-serif;padding:24px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:20px;">' .
                '<h3>Không thể kết nối Cơ sở dữ liệu!</h3>' .
                '<p>Vui lòng đảm bảo dịch vụ MySQL trong XAMPP đang chạy và CSDL <code>eventflow_db</code> đã được tạo theo file <code>database/schema_and_seed.sql</code>.</p>' .
                '<small>Lỗi chi tiết: ' . htmlspecialchars($lastException->getMessage()) . '</small>' .
                '</div>');
        }
    }

    return $pdo;
}
