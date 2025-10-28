<?php

$host = '4w4f1r.myd.infomaniak.com'; 
$dbname = '4w4f1r_im03';
$username = '4w4f1r_im03';
$password = 'X0%LB2-6h-B3ub';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false
];

return [
  'external_url' => 'https://portal.alfons.io/app/devicecounter/api/sensors?api_key=3ad08d9e67919877e4c9f364974ce07e36cbdc9e',
  'timeout' => 10,  // in Sekunden
];
