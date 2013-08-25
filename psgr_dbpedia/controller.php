<?php
$s_mt = explode(" ",microtime());
$process_name = "PSGR BDpedia mapping";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title><?php echo $process_name; ?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>

	<?php
	require_once('psgr_dbpedia.php');

	dout("<h1>Start of $process_name process!</h1>");

	//
	// Settings: Agents to exclude
	// Read from file (exclude municipilities & regions)
	$file_columns = 3; // agent (uri, name, name)
	$excludeFile1 = "input/exclude/psgr_municipalities_paymentAgents.csv";
	$excludeFile2 = "input/exclude/psgr_regions_paymentAgents.csv";
	$excludeAgents1 = process_file($excludeFile1, $file_columns, 1);
	$excludeAgents2 = process_file($excludeFile2, $file_columns, 1);
	$excludeAgents = array_merge($excludeAgents1, $excludeAgents2);
	$excludeAgentsUris = array();

	if (isset($excludeAgents) && count($excludeAgents)>0) {
		foreach($excludeAgents as $ag) {
			$excludeAgentsUris[] = $ag[0];
		}
	}

	//
	// Settings: Mode (test/pilot/final)
	$mode = 'final';

	//
	// Settings: Input file(s)
	// $argv — Array of arguments passed to script
	// http://php.net/manual/en/reserved.variables.argv.php
	$suffix = $mode;
	if ($mode == 'final' && isset($argv[1])) {
		$suffix .= $argv[1];
	}

	$infile = "input/psgr_payment_agents_$suffix.csv";

	//
	// Settings: Write to file (true/false)
	$write_to_file = true;

	//
	// Settings: DBpedia (el/en/live/all)
	$dbpedia = "all";

	dout("<h2>Settings: mode[$suffix], write to file[$write_to_file], dbpedia[$dbpedia]</h2>");

	//
	// Start process
	$file_columns = 1; // agent URI
	$agents = process_file($infile, $file_columns, 2);

	//
	// Slice Array of Payment Agents
	if (is_array($agents) && count($agents)>0) {
		$num_of_elements = 2000;

		$count = ceil(count($agents)/$num_of_elements);

		for ($part=0; $part<$count; $part++) {
			$from = ($num_of_elements * $part) + 1;
			$to = ($num_of_elements * $part) + $num_of_elements;

			dout("<h2>Part[".($part+1)."/$count], From[$from], To[$to]</h2>");

			$agents_part = array_slice($agents, $from, $num_of_elements);

			//
			// Settings: Ooutput file(s)
			$outfile1 = "output/$mode/psgr_bdpedia_mapping_payment_agents_el_".$suffix."_part".($part+1).".csv";
			$outfile2 = "output/$mode/psgr_bdpedia_mapping_payment_agents_en_".$suffix."_part".($part+1).".csv";
			$outfile3 = "output/$mode/psgr_bdpedia_mapping_payment_agents_live_".$suffix."_part".($part+1).".csv";

			if (is_array($agents_part) && count($agents_part)>0) {
				psgrDdpediaMapping(
				$agents_part,
				$excludeAgentsUris,
				$outfile1, $outfile2, $outfile3,
				$dbpedia, $write_to_file
				);
			} // if
		} // for
	} // if

	dout("<h1>End of $process_name process!</h1>");

	$e_mt = explode(" ",microtime());
	$s = (($e_mt[1] + $e_mt[0]) - ($s_mt[1] + $s_mt[0]));
	dout("Script executed in ".$s." seconds. [".floor($s/60)."m ".($s%60)."s]");
	?>

</body>
</html>
