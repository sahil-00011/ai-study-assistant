<?php
/**
 * Database Connection using PHP PDO
 * AI Study Assistant
 */

require_once __DIR__ . '/config.php';

// Database credentials (127.0.0.1 connects via TCP on both XAMPP and Linux containers)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'ai_study_assistant');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection (Singleton)
 *
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Self-healing check: Ensure tables exist
            try {
                $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
                if (!$check) {
                    $sqlFile = dirname(__DIR__) . '/database/ai_study_assistant.sql';
                    if (file_exists($sqlFile)) {
                        $sqlContent = file_get_contents($sqlFile);
                        if (!empty($sqlContent)) {
                            $pdo->exec($sqlContent);
                        }
                    }
                }
            } catch (Exception $seedEx) {
                error_log("Database schema auto-check error: " . $seedEx->getMessage());
            }

        } catch (PDOException $e) {
            // Check if database does not exist (1049) -> Auto-initialize!
            if ($e->getCode() === 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $serverDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                    $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Reconnect to newly created database
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    
                    // Import SQL schema
                    $sqlFile = dirname(__DIR__) . '/database/ai_study_assistant.sql';
                    if (file_exists($sqlFile)) {
                        $sqlContent = file_get_contents($sqlFile);
                        if (!empty($sqlContent)) {
                            $pdo->exec($sqlContent);
                        }
                    }
                    return $pdo;
                } catch (Exception $initEx) {
                    error_log("Auto-database creation error: " . $initEx->getMessage());
                }
            }

            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}
