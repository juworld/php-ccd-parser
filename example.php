<?php
use michalisantoniou6\PhpCCDParser\CCDParser;

require('./vendor/autoload.php');

$xml = file_get_contents('demo.xml');

$ccd = new CCDParser($xml);

echo '<pre>';
//var_dump($ccd->provider);
//var_dump($ccd->demographics);
//var_dump($ccd->allergies);

echo $ccd->getParsedCCD('json');
//var_dump($ccd->getParsedCCD('array'));

//var_dump($ccd->getParsedCCD('object'));
//var_dump($ccd->getParsedCCD('object')->demographics->name->first);
echo '</pre>';