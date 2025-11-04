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
$dbHost = '127.0.0.1';
$dbName = 'rainy_parking';
$dbUser = 'root';
$dbPass = 'root';

// --- Simple HTTP GET -> array ---
function http_get_json(string $url, int $timeout = 10): array {
    $ctx = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => $timeout, 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new RuntimeException("HTTP GET failed: $url");
    }
    $json = json_decode($raw, true);
    if (!is_array($json)) {
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
