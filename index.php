<?php

require_once('vendor/autoload.php');

$object = new PCBIS2PDF\Providers\KNV;

// Combines all CSV files from `src/csv/*.csv` to `src/Titelexport.csv`
// $object->mergeCSV();

// Do it like this ..
try {
    // $fromCSV = $object->CSV2PHP('./example/Titelexport.csv', ';');
    $fromCSV = '978-3-522-18500-4';
    $array = $object->getBook($fromCSV);
    var_dump($array);
    // $object->PHP2CSV($array);
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage(), "\n";
}


// .. or go crazy like this:
// $object->PHP2CSV($object->process($object->CSV2PHP()));
