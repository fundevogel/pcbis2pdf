<?php

require_once('vendor/autoload.php');

$object = new PCBIS2PDF\PCBIS2PDF;

// Combines all CSV files from `src/csv/*.csv` to `src/Titelexport.csv`
// $object->mergeCSV();

// Do it like this ..
try {
    $fromCSV = $object->CSV2PHP('./example/Titelexport.csv', ';');
    $array = $object->process($fromCSV);
    $object->PHP2CSV($array);
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage(), "\n";
}


// .. or go crazy like this:
// $object->PHP2CSV($object->process($object->CSV2PHP()));
