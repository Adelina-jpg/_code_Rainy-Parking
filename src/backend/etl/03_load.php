<?php
$data = include('02_transform.php');
require_once '../config.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $sql = "INSERT INTO im_03lucerne (passanten, standort) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($data as $row) {
        $stmt->execute([
            $row['passanten'],
            $row['standort']
        ]);
    }

    echo "Alle Daten erfolgreich eingefügt.";
} catch (PDOException $e) {
    error_log("Datenbankfehler: " . $e->getMessage());
    exit("Fehler beim Speichern.");
}
