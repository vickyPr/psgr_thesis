<?php
$s_mt = explode(" ",microtime());
$process_name = "fcrs_stations";
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
require_once('fcrs_stations.php');

dout("<h3>Start of $process_name process!</h3>");

$infile = "input/fcrs_stations.csv";
$outfile = "output/fcrs_stations_wikipedia.csv";
$write_to_file = true;
$case = "port";
$file_columns = 5; //"CODE","DESCRIPTION","LOCAL_DESCRIPTION","COUNTRY_ABBREVIATION","G1"
$elements = process_file($infile, $file_columns, 2);
elementsMapping($elements, $outfile, $case, $write_to_file, 1, 500);

dout("<h3>End of $process_name process!</h3>");

$e_mt = explode(" ",microtime());
$s = (($e_mt[1] + $e_mt[0]) - ($s_mt[1] + $s_mt[0]));
dout("Script executed in ".$s." seconds. [".floor($s/60)."m ".($s%60)."s]");
?>

</body>
</html>