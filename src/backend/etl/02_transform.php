<?php
$data = include('01_extract.php');

$standorte = $data['data'];

    $transformed_data = [];
    foreach ($standorte as $standort) 
        {
            if (!str_contains($standort['name'], 'Wifi')){ 
            $transformed_data[] = [
            'passanten' => $standort['counter'],
            'standort' => $standort['name']
        ];
    }
        
    }
/*echo json_encode($transformed_data);*/

    return $transformed_data;
