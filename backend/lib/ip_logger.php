<?php
// (keep or re-add strict_types once BOM removed)

require_once __DIR__ . '/../config.php';

if (!function_exists('ip_logger_client_ip')) {
    function ip_logger_client_ip(): string {
        $candidates = [];
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
                $ip = trim($part);
                if ($ip !== '') $candidates[] = $ip;
            }
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) $candidates[] = $_SERVER['HTTP_X_REAL_IP'];
        if (!empty($_SERVER['REMOTE_ADDR'])) $candidates[] = $_SERVER['REMOTE_ADDR'];

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE)) {
                if ($ip === '::1') return '127.0.0.1';
                return $ip;
            }
        }
        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                if ($ip === '::1') return '127.0.0.1';
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('log_request_ip')) {
    /**
     * Logs current request IP and returns associative array.
     * @param string|null $tag
     * @return array{ok:bool,id?:int,ip_masked?:string,hash?:string,tag?:?string,error?:string}
     */
    function log_request_ip(?string $tag = null): array {
        try {
            $conn = get_connection();
            $conn->query("
                CREATE TABLE IF NOT EXISTS ip_audit_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip VARCHAR(45) NOT NULL,
                    ip_hash CHAR(64) NOT NULL,
                    forwarded_for VARCHAR(255) NULL,
                    user_agent VARCHAR(255) NULL,
                    path VARCHAR(255) NULL,
                    tag VARCHAR(64) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ip (ip),
                    INDEX idx_created (created_at),
                    INDEX idx_hash (ip_hash)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $ip        = ip_logger_client_ip();
            $forwarded = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? substr($_SERVER['HTTP_X_FORWARDED_FOR'], 0, 255) : null;
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
            $path      = isset($_SERVER['REQUEST_URI']) ? substr($_SERVER['REQUEST_URI'], 0, 255) : null;
            $tagSafe   = $tag ? substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $tag), 0, 64) : null;
            $ipHash    = hash('sha256', $ip);

            $stmt = $conn->prepare("
                INSERT INTO ip_audit_log (ip, ip_hash, forwarded_for, user_agent, path, tag)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                $conn->close();
                return ['ok' => false, 'error' => 'prepare failed'];
            }

            $stmt->bind_param('ssssss', $ip, $ipHash, $forwarded, $userAgent, $path, $tagSafe);
            if (!$stmt->execute()) {
                $stmt->close(); $conn->close();
                return ['ok' => false, 'error' => 'insert failed'];
            }
            $id = $stmt->insert_id;
            $stmt->close(); $conn->close();

            $displayIp = $ip;
            if (strpos($ip, ':') === false) {
                $parts = explode('.', $ip);
                if (count($parts) === 4) {
                    $parts[3] = 'x';
                    $displayIp = implode('.', $parts);
                }
            } else {
                $displayIp = substr($ip, 0, 16) . '...';
            }

            return [
                'ok' => true,
                'id' => $id,
                'ip_masked' => $displayIp,
                'hash' => $ipHash,
                'tag' => $tagSafe
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'server error'];
        }
    }
}