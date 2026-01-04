<?php
/**
 * cron_fetch.php
 * - Fetch hourly city-wide free parking spaces (sum of ParkenDD Basel lots)
 *   and current rain from Open-Meteo; store into rainy_parking.hourly_data.
 * - Table schema (existing):
 *   hour (DATETIME, PK), rain (INT NOT NULL), parking_lots (INT NOT NULL)
 */

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Zurich');

// --- DB credentials (in-file per school project) ---
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'rainy_parking';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'root';

function http_get_json(string $url, int $timeout = 15): array {
    if (!function_exists('curl_init')) {
        error_log('[HTTP FAIL] curl not available; url="' . $url . '"');
        throw new RuntimeException("cURL not available on this server");
    }

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'User-Agent: rainy-parking-cron/1.0',
        ],
        // TLS verification ON (good default)
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $raw = curl_exec($ch);

    $errno   = curl_errno($ch);
    $errstr  = curl_error($ch);
    $status  = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $ctype   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    curl_close($ch);

    if ($raw === false || $errno !== 0) {
        error_log(sprintf(
            '[HTTP FAIL] url="%s" curl_errno=%d curl_error="%s" statusCode=%d contentType="%s"',
            $url,
            $errno,
            str_replace(["\n", "\r"], ' ', $errstr),
            $status,
            $ctype
        ));
        throw new RuntimeException("HTTP GET failed: $url");
    }

    if ($status < 200 || $status >= 300) {
        error_log(sprintf(
            '[HTTP NON2XX] url="%s" statusCode=%d contentType="%s" bodySnippet="%s"',
            $url,
            $status,
            $ctype,
            str_replace(["\n", "\r"], ' ', substr($raw, 0, 300))
        ));
        throw new RuntimeException("HTTP non-2xx ($status) from $url");
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        error_log(sprintf(
            '[HTTP BADJSON] url="%s" statusCode=%d contentType="%s" bodySnippet="%s"',
            $url,
            $status,
            $ctype,
            str_replace(["\n", "\r"], ' ', substr($raw, 0, 300))
        ));
        throw new RuntimeException("Invalid JSON from $url");
    }

    return $json;
}

try {
    // --- Connect ---
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    // --- Fetch ParkenDD Basel ---
    $park = http_get_json('https://api.parkendd.de/Basel');
    $freeTotal = 0;
    if (!empty($park['lots']) && is_array($park['lots'])) {
        foreach ($park['lots'] as $lot) {
            if (isset($lot['free']) && is_numeric($lot['free'])) {
                $freeTotal += (int)$lot['free']; // Sum of free spots city-wide
            }
        }
    } else {
        throw new RuntimeException('ParkenDD payload missing lots[]');
    }

    // --- Fetch Open-Meteo current rain (mm/h) ---
    $weather = http_get_json('https://api.open-meteo.com/v1/forecast?latitude=47.5584&longitude=7.5733&current=rain');
    $rainInt = 0;
    if (isset($weather['current']['rain']) && is_numeric($weather['current']['rain'])) {
        // Column is INT -> round; clamp to >=0 for safety
        $rainInt = max(0, (int)round((float)$weather['current']['rain']));
    }

    // --- Hour bucket (start of hour) to match PK uniqueness ---
    $bucketTs = (new DateTimeImmutable('now'))->setTime(
        (int)date('H'), 0, 0
    )->format('Y-m-d H:i:s');

    // --- Upsert into hourly_data ---
    $stmt = $pdo->prepare("
        INSERT INTO hourly_data (hour, rain, parking_lots)
        VALUES (:hour, :rain, :lots)
        ON DUPLICATE KEY UPDATE
            rain = VALUES(rain),
            parking_lots = VALUES(parking_lots)
    ");
    $stmt->execute([
        ':hour' => $bucketTs,
        ':rain' => $rainInt,
        ':lots' => $freeTotal,
    ]);

    echo "[OK] {$bucketTs} rain={$rainInt} parking_lots={$freeTotal}\n";

} catch (Throwable $e) {
    fwrite(STDERR, '[ERR] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
