<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title>PSGR Municipalities</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<?php

require_once('psgr_municipalities.php');

dout("Start of PSGR Municipalities process!");

/*
$infile1 = "input\municipalities_v3.csv";
$outfile1 = "output\psgr_dbpedia_el_municipalities.csv";
$outfile2 = "output\psgr_municipalities_paymentAgents.csv";
$outfile3 = "output\psgr_municipalities_paymentAgents_ypesCodes.csv";

$file_columns = 3;
$municipalities = process_file($infile1, $file_columns);
bundlePsgrDbpedia($municipalities, $outfile1, $outfile2, $outfile3);
*/
/*
// Relevant Organisations - Organization Units
// Method 1
$infile1 = "input\psgr_municipalities_organizations_v1.csv";
$outfile1 = "output\psgr_municipalities_relevant_organisations_m1.csv";
$outfile2 = "output\psgr_municipalities_relevant_organisations_m1_text.csv";
$file_columns = 2;
$municipalities = process_file($infile1, $file_columns);
bundlePsgrRelevantOrganisationsMethod1($municipalities, $outfile1, $outfile2);
*/
/*
// Relevant Organisations - Text match
// Method 2
$infile = "output\psgr_municipalities_paymentAgents.csv";
$outfile1 = "output\psgr_municipalities_relevant_organisations_m2.csv";
$outfile2 = "output\psgr_municipalities_relevant_organisations_m2_text.csv";
$file_columns = 3; // psgr:paymentAgent URI, Kallikratis name, psgr:paymentAgent validName
$municipalities = process_file($infile, $file_columns);
bundlePsgrRelevantOrganisationsMethod2($municipalities, $outfile1, $outfile2);
*/
/*
// Relevant Organisations - CPA 84111102 & TK
// Method 3
$infile = "output\psgr_municipalities_paymentAgents.csv";
$outfile1 = "output\psgr_municipalities_relevant_organisations_m3.csv";
$outfile2 = "output\psgr_municipalities_relevant_organisations_m3_text.csv";
$file_columns = 3; // psgr:paymentAgent URI, Kallikratis name, psgr:paymentAgent validName
$municipalities = process_file($infile, $file_columns);
bundlePsgrRelevantOrganisationsMethod3($municipalities, $outfile1, $outfile2);
*/

// Old New Municipalities
// Method 1 STEP 2
$infile1 = "input\psgr_municipalities_paymentAgents_old_new_bundle_v2.csv";
$infile2 = "output\psgr_municipalities_paymentAgents_ypesCodes.csv";
$outfile = "output\psgr_municipalities_relevant_organisations_old_new.csv";
$table1 = process_file($infile1, 4, 2);
$table2 = process_file($infile2, 3, 2);
bundlePsgrRelevantOrganisationsOldNew($table1, $table2, $outfile);

// Test greek-lod open data service
//greeklodTest();

dout("End of PSGR Municipalities process!");


?>

</body>
</html>