<?php
// api.php — Aggregiert Parkplätze (frei/belegt) + Regen (mm/h)
// Nutzung: /api.php?days_ago=0  (0..30)
// Optional: /api.php?iso=2025-09-23T08:00:00  (ISO-Zeit, überschreibt days_ago)

header('Content-Type: application/json; charset=utf-8');

// --- Einstellungen ---
$BASLE_LAT  = 47.5584;   // Basel Koordinaten
$BASLE_LON  = 7.5733;
$TZ         = 'Europe/Zurich';

// Parken-API (liefert lots[] mit free/total wie in deinem JSON)
$PARKING_URL = 'https://api.parkleitsystem-basel.example.com/parking.json'; // <-- ERSETZEN durch echte URL

// Open‑Meteo für stündliche Regenwerte (mm)
// Doku: https://open-meteo.com/en/docs
// Wir holen die gesamte Stunde (start=...&end=...) und lesen precipitation oder rain
$OPENMETEO_BASE = 'https://api.open-meteo.com/v1/forecast';

// --- Parameter lesen ---
$daysAgo = isset($_GET['days_ago']) ? max(0, min(30, (int)$_GET['days_ago'])) : 0;
$iso     = isset($_GET['iso']) ? $_GET['iso'] : null;

// Ziel-Zeit: volle Stunde, lokal (Europa/Zurich), dann in ISO für APIs
$tz = new DateTimeZone($TZ);
if ($iso) {
    $target = new DateTime($iso, $tz);
} else {
    $target = new DateTime('now', $tz);
    $target->setTime((int)$target->format('H'), 0, 0); // auf volle Stunde
    if ($daysAgo > 0) $target->modify("-{$daysAgo} day");
}

// Für Open‑Meteo brauchen wir UTC-Range
$utc = new DateTimeZone('UTC');
$startUtc = clone $target; $startUtc->setTimezone($utc);
$endUtc   = clone $startUtc; $endUtc->modify('+1 hour');

// --- Helper: HTTP GET ---
function http_get_json(string $url, int $timeout = 12) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code >= 400) {
        throw new Exception("HTTP error ($code): $err for $url");
    }
    $data = json_decode($body, true);
    if ($data === null) throw new Exception("JSON decode failed for $url");
    return $data;
}

// --- 1) Parkplätze aggregieren ---
$freeTotal = null; $occupiedTotal = null; $lotsTotal = null;
try {
    $parking = http_get_json($PARKING_URL);
    $free = 0; $cap = 0;
    if (!empty($parking['lots']) && is_array($parking['lots'])) {
        foreach ($parking['lots'] as $lot) {
            if (isset($lot['free']) && is_numeric($lot['free'])) $free += (int)$lot['free'];
            if (isset($lot['total']) && is_numeric($lot['total']) && (int)$lot['total'] > 0) $cap += (int)$lot['total'];
        }
    }
    $freeTotal     = $free;
    $lotsTotal     = $cap; // ohne unbekannte/0er
    $occupiedTotal = ($cap > 0) ? max(0, $cap - $free) : null; // null, falls Kapazität unbekannt
} catch (Throwable $e) {
    // Fehler wird unten im Output gemeldet
}

// --- 2) Regen für die Stunde holen ---
$rainMm = null; $weatherNote = null;
try {
    $qs = http_build_query([
        'latitude'  => $BASLE_LAT,
        'longitude' => $BASLE_LON,
        'hourly'    => 'rain,precipitation', // manche Modelle liefern nur precipitation
        'timezone'  => 'UTC',
        'start_date'=> $startUtc->format('Y-m-d'),
        'end_date'  => $startUtc->format('Y-m-d'),
    ]);
    $wx = http_get_json($OPENMETEO_BASE . '?' . $qs);

    if (!empty($wx['hourly']['time'])) {
        $times = $wx['hourly']['time'];
        $i = array_search($startUtc->format('Y-m-d\TH:00'), $times, true);
        if ($i !== false) {
            // Bevorzugt rain, fallback precipitation
            if (!empty($wx['hourly']['rain'])) {
                $rainMm = (float)$wx['hourly']['rain'][$i];
            } elseif (!empty($wx['hourly']['precipitation'])) {
                $rainMm = (float)$wx['hourly']['precipitation'][$i];
                $weatherNote = 'precipitation used as fallback';
            } else {
                $weatherNote = 'no rain/precipitation field';
            }
        } else {
            $weatherNote = 'target hour not found in Open‑Meteo response';
        }
    } else {
        $weatherNote = 'no hourly array in Open‑Meteo response';
    }
} catch (Throwable $e) {
    $weatherNote = 'weather fetch failed: ' . $e->getMessage();
}

// --- Antwort ---
$out = [
    'meta' => [
        'tz'           => $TZ,
        'hour_local'   => $target->format(DateTime::ATOM),
        'hour_utc'     => $startUtc->format(DateTime::ATOM),
        'source'       => [
            'parking' => $PARKING_URL,
            'weather' => $OPENMETEO_BASE,
        ]
    ],
    'parking' => [
        'free_total_basel'     => $freeTotal,
        'occupied_total_basel' => $occupiedTotal,
        'capacity_known_sum'   => $lotsTotal,
    ],
    'weather' => [
        'rain_mm_hour' => $rainMm,
        'note'         => $weatherNote,
    ]
];

http_response_code( isset($freeTotal) || isset($rainMm) ? 200 : 502 );
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
