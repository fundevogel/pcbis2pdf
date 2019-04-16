<?php

require_once('vendor/autoload.php');

$object = new PCBIS2PDF\PCBIS2PDF;

// Combines all CSV files from `src/csv/*.csv` to `src/Titelexport.csv`
// $object->mergeCSV();

// Do it like this ..
$fromCSV = $object->CSV2PHP();
$array = $object->process($fromCSV);
$object->PHP2CSV($array);

// .. or go crazy like this:
// $object->PHP2CSV($object->process($object->CSV2PHP()));
