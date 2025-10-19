<?php

function fetchWeatherData() {
    $url = "https://api.open-meteo.com/v1/forecast?latitude=47.5584&longitude=7.5733&current=rain&ref=freepublicapis.com";

    // Initialisiert eine cURL-Sitzung
    $ch = curl_init($url);

    // Setzt Optionen
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Führt die cURL-Sitzung aus und erhält den Inhalt
    $response = curl_exec($ch);

    // Schließt die cURL-Sitzung
    curl_close($ch);

    // Dekodiert die JSON-Antwort und gibt Daten zurück
    return json_decode($response, true);
}

echo '<pre>'
var_dump(fetchWeatherData());
echo '<pre>'
?>