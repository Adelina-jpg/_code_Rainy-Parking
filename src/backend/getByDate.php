<?php
/**
 * getByDate.php (frontend-compatible)
 * - Input:  ?date=YYYY-MM-DD   (default = today, Europe/Zurich)
 * - Output: { "date":"YYYY-MM-DD", "records":[ { "rain_mm_h":int, "free_spaces":int }, ... ] }
 *   -> matches your JS (data.records, r.rain_mm_h, r.free_spaces)
 */

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

date_default_timezone_set('Europe/Zurich');
header('Content-Type: application/json; charset=utf-8');

// --- DB credentials (as used in dev) ---
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'rainy_parking';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'root';

// Read and validate date
$requested = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    // Select exactly the three columns you have
    $stmt = $pdo->prepare("
        SELECT hour, rain, parking_lots
        FROM hourly_data
        WHERE DATE(hour) = :d
        ORDER BY hour ASC
    ");
    $stmt->execute([':d' => $requested]);

    $records = [];
    while ($r = $stmt->fetch()) {
        // Map DB -> fields your JS expects
        $records[] = [
            'rain_mm_h'   => (int)$r['rain'],          // int in your schema
            'free_spaces' => (int)$r['parking_lots'],  // int in your schema
            // you can add 'timestamp' => $r['hour'] if you ever need it
        ];
    }

    echo json_encode([
        'date'    => $requested,
        'records' => $records
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
