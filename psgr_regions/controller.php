<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title>PSGR Regions</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<?php

require_once('psgr_regions.php');

dout("Start of PSGR Regions process!");

/*
$infile1 = "input/regions.csv";
$outfile1 = "output/psgr_dbpedia_el_regions.csv";
$outfile2 = "output/psgr_dbpedia_en_regions.csv";
$outfile3 = "output/psgr_old_new_regions.csv";
$outfile4 = "output/psgr_regions_paymentAgents.csv";
$file_columns = 4;
$regions = process_file($infile1, $file_columns, 2);
bundlePsgrDbpedia($regions, $outfile1, $outfile2, $outfile3, $outfile4);

// Relevant Organisations - Organization Units
// Method 1
$infile1 = "input/psgr_regions_organizations.csv";
$outfile1 = "output/psgr_regions_relevant_organisations_m1.csv";
$outfile2 = "output/psgr_regions_relevant_organisations_m1_text.csv";
$file_columns = 2;
$municipalities = process_file($infile1, $file_columns, 2);
bundlePsgrRelevantOrganisationsMethod1($municipalities, $outfile1, $outfile2);

// Relevant Organisations - Text match
// Method 2
$infile = "output/psgr_regions_paymentAgents.csv";
$outfile1 = "output/psgr_regions_relevant_organisations_m2.csv";
$outfile2 = "output/psgr_regions_relevant_organisations_m2_text.csv";
$file_columns = 3; // psgr:paymentAgent URI, Kallikratis name, psgr:paymentAgent validName
$municipalities = process_file($infile, $file_columns);
bundlePsgrRelevantOrganisationsMethod2($municipalities, $outfile1, $outfile2);
*/

dout("End of PSGR Regions process!");

?>

</body>
</html>