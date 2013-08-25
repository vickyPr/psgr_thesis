<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title>Municipalities Properties</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<?php

require_once('dbpedia_properties.php');

// Get dbpedia properties for each municipality
/*
$outfolder = "municipalities_properties";
$infile = "input/municipalities_v2.csv";
$file_columns = 3;
$municipalities = process_file($infile, $file_columns);
getDbpediaProperties($municipalities,$outfolder);

// Get dbpedia properties for each regions
$outfolder = "regions_properties_el";
$infile = "input/regions_el.csv";
$file_columns = 3;
$regions = process_file($infile, $file_columns, 2);
$endpoint = 'http://el.dbpedia.org/sparql?';
getDbpediaProperties($regions,$outfolder,$endpoint);

// Get dbpedia properties for each regions
$outfolder = "regions_properties_en";
$infile = "input/regions_en.csv";
$file_columns = 3;
$regions = process_file($infile, $file_columns, 2);
$endpoint = 'http://dbpedia.org/sparql?';
getDbpediaProperties($regions,$outfolder,$endpoint);

// Create municipalities properties table
$domain = "municipalities_properties";
createDomainPropertiesTable($domain);

// Create regions properties table
$domain = "regions_properties_el";
createDomainPropertiesTable($domain);
$domain = "regions_properties_en";
createDomainPropertiesTable($domain);
*/
?>

</body>
</html>