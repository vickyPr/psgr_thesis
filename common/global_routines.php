<?php

/*
 * REGEX
 * 
 * . any character
 * X? matches X, once or not at all
 * X* matches X, zero or more times
 * X+ matches X, one or more times
 * X{n} matches X, exactly n times
 * X{n,} matches X, at least n times
 * X{n,m} matches X, at least n times, but not more than m times
 *  
 */

$NL = "\r\n";
define("READ_CHUNK_SIZE", 1024 * 100); // 100KB



function dout($output)
{
    echo "<div style='white-space:nowrap;'>$output</div>";
}

// return true/false
function backupFile($outfile) {
  
  if (file_exists($outfile)) {
    //unlink($outfile);
    //$randString = md5(time()); //encode the timestamp - returns a 32 chars long string
    $randString = time();
    list($splitName,$fileExt) = explode(".", $outfile); //split the file name by the dot
    $newFileName = $splitName.'_'.$randString.'.'.$fileExt;
    rename($outfile,$newFileName);
  }

}

/******************************************************************************/
/******************************************************************************/

function request($url)
{
    // is curl installed?
    if (!function_exists('curl_init')) {
        die('CURL is not installed!');
    }
    
    // get curl handle
    $ch = curl_init();
    
    // set request url
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // return response, don't print/echo
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    /*
    Here you find more options for curl:
    http://www.php.net/curl_setopt
    */
    
    $response = curl_exec($ch);
    
    curl_close($ch);
    
    return $response;
}

/******************************************************************************/
/******************************************************************************/

function printArray($array, $spaces = "")
{
    $retValue = "";
    
    if (is_array($array)) {
        $spaces = $spaces . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        
        $retValue = $retValue . "<br/>";
        
        foreach (array_keys($array) as $key) {
            $retValue = $retValue . $spaces . "<strong>" . $key . "</strong>" . printArray($array[$key], $spaces);
        }
        $spaces = substr($spaces, 0, -30);
    } else
        $retValue = $retValue . " - " . $array . "<br/>";
    
    return $retValue;
}

/******************************************************************************/
/******************************************************************************/

function merge_entries(
 $table1,
 $table1_column_to_be_replaced,
 $table2,
 $table2_column_to_search,
 $table1_column_replacement,
 $outfile	
) {

	global $NL;
	
    // Backup files
	backupFile($outfile);
  	
	$output = '';
	
	foreach ($table1 as $k1 => $v1) {
		$replacement = '';
		
		$to_be_replaced = $v1[$table1_column_to_be_replaced];
	
		foreach ($table2 as $k2 => $v2) {
			$search = $v2[$table2_column_to_search];
				
			if ($to_be_replaced==$search) {
				$replacement = $v2[$table1_column_replacement];
				break;
			}
		}
		
		if ($v1[0]!==$replacement && !empty($replacement)) {
			$output .= '"'.$v1[0].'","belongsTo","'.$replacement.'"'.$NL;
		} 
	}
	
    $fp = fopen($outfile, "a");
    fwrite($fp, $output . $NL);
    fclose($fp);
	
} // merge_entries

/***********************************************************************/
/***********************************************************************/

function process_file($infile,$file_columns,$type=1)
{
	dout("Starting with file [$infile]");
	if (!($fp = fopen($infile, "r"))) {
		dout("ERROR could not open CSV input");
		return;
	}

	if (filesize($infile) > 0) {
		$prev_data         = "";
		$no_of_reads       = -1;
		$total_no_of_lines = 0;
		$no_of_errors      = 0;
		$mc = 0; // Municipalities count

		do {
			//sleep(1);

			$chunk = fread($fp, READ_CHUNK_SIZE);
			$no_of_reads += 1;
			//echo "\n".$no_of_reads." ";
			if (strlen($chunk) == 0) {
				break;
			}
			$end = strrpos($chunk, "\n");
			if ($end == 0) {
				echo "********** No newline found in chunk. Aborting ****************";
				break;
			}
			$contents  = $prev_data . substr($chunk, 0, $end);
			$prev_data = substr($chunk, $end + 1);

			// Convert to UTF-8
			//$contents = mb_convert_encoding($contents,"UTF-8","ISO 8859-2");
			//dout($contents);
			$lines     = explode("\n", $contents);
			$nooflines = count($lines);

			for ($l = 0; $l < $nooflines; $l++) {
				$csvdel = ";";
				if ($type!=1) {
					$lines[$l] = str_replace('"', '', $lines[$l]);
					$csvdel = ",";
				}
				$row = explode($csvdel, $lines[$l]);

				if (count($row) == $file_columns) {
					$i = 0;

					for ($k=0; $k<$file_columns; $k++) {
						$municipalities[$mc][$k] = trim($row[$i++]);
					}

					$mc++;

				} // correct number of columns
				else {
					dout("Wrong number of column[" . count($row) . "]!");
				}
			} // for all lines in chunk
		} while (true); // for all chunks

		fclose($fp);

	} else {
		// file size was 0
		fclose($fp);
	}

	dout("End processing file " . $infile . " No of reads:" . $no_of_reads);

	return $municipalities;
}

/***********************************************************************/
/***********************************************************************/

function convertCSVtoJSON($outfile, $array, $identifier)
{
	global $NL;

	$retValue = $identifier . $NL;

	if (is_array($array['results']['bindings'])) {
		foreach ($array['results']['bindings'] as $property) {
			$retValue .= $property['property']['value'] . ';' . $property['hasValueFlag']['value'] . ';' . $property['isValueOfFlag']['value'] . $NL;
		}

		if (file_exists($outfile)) {
			unlink($outfile);
		}

		$fp = fopen($outfile, "w");
		fwrite($fp, $retValue);
		fclose($fp);
	}
}

/***********************************************************************/
/***********************************************************************/

// return requestURL
function globalCreateRequestURL($endpoint, $format, $term, $case)
{

	switch ($case)
	{
		case "searchEngName":
			$query = '
				SELECT ?searchEngName WHERE {
				  <'.$term.'> psgr:searchEngName ?searchEngName .
				}
        	';
			break;
		default:
			;
	}

	//echo $query; die();
	$requestURL = $endpoint . 'query=' . urlencode($query) . '&format=' . $format;

	//echo $requestURL; die();
	return $requestURL;
} // createRequestURL

/***********************************************************************/
/***********************************************************************/

function createRelevantOranizationsTriplets($groupLeader, $responseArray, $outfile1, $outfile2 = "" , $groupLeaderName = "" , $terms_to_log = "" )
{
	global $NL;

	$cnt = count($responseArray);
	$terms = explode("|",$terms_to_log);

	if ($cnt>0) {
		$triplets = "";

		if (!empty($outfile2)) {
			$text_log = "--------- $groupLeaderName ---------".$NL;
			dout("--------- Units ---------");
		}
		
		$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
		$format = 'json';
		$term = $groupLeader; // paymentAgent URI
		$case = "searchEngName";

		$requestURL = globalCreateRequestURL($endpoint, $format, $term, $case);
		$response = json_decode(request($requestURL), true);
		$goupName = $response['results']['bindings'][0]['searchEngName']['value'];
		$goupName = ucwords(strtolower($goupName));
		$goupName = str_replace(' ', '_', $goupName);

		foreach ($responseArray as $res) {
			$text = "";
				
			$s = '"http://publicspending.medialab.ntua.gr/resource/Groups/'.$goupName.'"';
			// TODO: TO CHANGE THE PREDICATE NAME
			$p = '"http://xmlns.com/foaf/0.1/member"';
			$o = '"'.$res['org']['value'].'"';

			$triplet = "$s,$p,$o";
			$triplets .= $triplet . $NL;

			if ($groupLeader==$res['org']['value']) {
				// TODO: TO CHANGE THE PREDICATE NAME
				$p = '"http://publicspending.medialab.ntua.gr/ontology#groupLeader"';
				$triplet = "$s,$p,$o";
				$triplets .= $triplet . $NL;
			}

				
			if (!empty($outfile2)) {
				foreach ($terms as $term) {
					$text .= '"'.$res[$term]['value'].'",';
				}
				
				dout($text);
				$text_log .= $text . $NL;
			}

		}


		$fp = fopen($outfile1, "a");
		fwrite($fp, $triplets);
		fclose($fp);

		if (!empty($outfile2)) {
			$fp = fopen($outfile2, "a");
			fwrite($fp, $text_log . $NL);
			fclose($fp);
		}
	}
}

/******************************************************************************/
/******************************************************************************/

?>