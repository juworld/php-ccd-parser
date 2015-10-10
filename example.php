<?php
use michalisantoniou6\PhpCCDParser\CCDParser;

$xml = file_get_contents('demo.xml');

// Create new patient
$patient = new CCDParser($xml);

// Construct and echo JSON 
echo('<PRE>');
echo($patient->getPatientCCD());
echo('</PRE>');