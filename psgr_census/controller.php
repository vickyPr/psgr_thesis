<?php
$process_name = "2012-12 population census 2011";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title><?php echo $process_name; ?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

<?php
require_once('psgr_census.php');

dout("Start of [$process_name] process!");

$file_columns = 5;
$file_type = 1;

// Domain: Regions
$infile = "input\population_census_2011_regions.csv";
$outfile1 = "output\psgr_population_census_2011_regions_triplets.csv";
$res_table = process_file($infile, $file_columns, $file_type);
bundlePsgrPopulationCensus2011('Regions', $res_table, $outfile1);
/*
// Domain: Regional Units
$infile = "input\population_census_2011_regional_units.csv";
$outfile1 = "output\psgr_population_census_2011_regional_units_triplets.csv";
$res_table = process_file($infile, $file_columns, $file_type);
bundlePsgrPopulationCensus2011('RegionalUnits', $res_table, $outfile1);

// Domain: Municipalities
$infile = "input\population_census_2011_municipalities.csv";
$outfile1 = "output\psgr_population_census_2011_municipalities_triplets.csv";
$res_table = process_file($infile, $file_columns, $file_type);
bundlePsgrPopulationCensus2011('Municipalities', $res_table, $outfile1);
*/

dout("End of [$process_name] process!");

?>

</body>
</html>