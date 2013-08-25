<?php

require_once('../common/global_routines.php');

/***********************************************************************/
/***********************************************************************/

// return requestURL
function createRequestURL($endpoint, $format, $term, $case)
{

	switch ($case)
	{
		case "Municipalities":
			$term = explode("|",$term);
			$query = '
			SELECT DISTINCT ?org WHERE {
			?org a psgrGeo:Municipality ; psgrGeo:name ?name .
			FILTER(REGEX(?name, "^'.$term[0].'$", "i"))
			} ORDER BY ?org
			';
			break;
		case "Regions":
			$term = explode("|",$term);
			$query = '
			SELECT DISTINCT ?org WHERE {
			?org a psgrGeo:Region ; psgrGeo:name ?name .
			FILTER(REGEX(?name, "'.$term[0].'$", "i"))
			} ORDER BY ?org
			';
			break;
		case "RegionalUnits":
			$term = explode("|",$term);
			$query = '
			SELECT DISTINCT ?org WHERE {
			?org a psgrGeo:RegionalUnit ; psgrGeo:name ?name .
			FILTER(REGEX(?name, "'.$term[0].'$", "i"))
			} ORDER BY ?org
			';
			break;
		default:
			;
	}

	//echo $query; die();

	if ($case=="censusMun" || $case=="censusRegUn") {
		$requestURL = $endpoint . 'default-graph-uri=&query=' . urlencode($query) . '&format=' . $format;
	}
	else {
		$requestURL = $endpoint . 'query=' . urlencode($query) . '&format=' . $format;
	}

	//echo $requestURL; die();
	return $requestURL;
} // createRequestURL

/******************************************************************************/
/******************************************************************************/

function bundlePsgrPopulationCensus2011($case, $res_table, $outfile1)
{
	global $NL;

	$cnt1 = $cnt2 = $cnt3 = 0;

	// Backup files
	backupFile($outfile1);

	foreach ($res_table as $res) {
		if (!empty($res[0]) && !empty($res[1]) && !empty($res[2])) {
			$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
			$format = 'json';
			$divition = $res[0]; //"Διοικητική διαίρεση"
			$populationTotal = $res[1]; //"Σύνολο"
			$populationMen = $res[2]; //"Άρρενες"
			$populationWomen = $res[3]; //"Θήλεις"
			$populationDensity = $res[4]; //"Πυκνότητα μόνιμου πληθυσμού ανά τετρ. χιλιόμετρο"

				
			dout("--------- $case: $divition ---------");

			if ($case=="Municipalities" || $case=="RegionalUnits") {
				$search = explode(","," - ,-,Περιφερειακή ενότητα");
				$replace = explode(",",".*,.*,");
				$divition = str_replace($search, $replace, $divition);
					
				dout("--------- Search criteria: $divition ---------");
				 
				$requestURL = createRequestURL($endpoint, $format, $divition, $case);
				//echo $requestURL; die();
				$responseArray = json_decode(request($requestURL), true);
				$cnt = count($responseArray['results']['bindings']);
				//echo printArray($responseArray);
				$results = $responseArray['results']['bindings'][0];
				 
				if ($cnt==0) {
					dout("<span style='color:red;'>--------- Results: [$cnt] ---------</span>");
					$cnt1++;
				}
				elseif ($cnt==1) {
					dout("<span style='color:black;'>--------- Results: [$cnt] [".$results['org']['value']."] ---------</span>");
					$cnt2++;
				}
				elseif ($cnt>1) {
					dout("<span style='color:orange;'>--------- Results: [$cnt] ---------</span>");
					$cnt3++;
				}
			} // Municipality
			else if ($case=="Regions") {
				$results = $res_table;
			}

			if (count($results)>0) {
				$triplets = '';

				if ($case=="Municipalities" || $case=="RegionalUnits") {
					$org = $results['org']['value'];
				}
				else if ($case=="Regions") {
					$org = $divition;
					$regions_array = array(
						"Περιφέρεια Ανατολικής Μακεδονίας και Θράκης",
						"Περιφέρεια Αττικής",
						"Περιφέρεια Βορείου Αιγαίου",
						"Περιφέρεια Δυτικής Ελλάδας",
						"Περιφέρεια Δυτικής Μακεδονίας",
						"Περιφέρεια Ηπείρου",
						"Περιφέρεια Θεσσαλίας",
						"Περιφέρεια Ιονίων Νήσων",
						"Περιφέρεια Κεντρικής Μακεδονίας",
						"Περιφέρεια Κρήτης",
						"Περιφέρεια Νοτίου Αιγαίου",
						"Περιφέρεια Πελοποννήσου",
						"Περιφέρεια Στερεάς Ελλάδας"
					);
					$search = $regions_array;
					$replace = explode(",","EastMacedoniaandThrace,Attica,NorthAegean,WestGreece,WestMacedonia,Ipiros,Thessaly,IonianIslands,CentralMacedonia,Crete,SouthAegean,Peloponnisos,CentralGreece");
					$org = str_replace($search, $replace, $org);
					$org = 'http://publicspending.medialab.ntua.gr/resource/region/'.$org;
				}

				$s = '"'.$org.'"';
				$p = '"http://dbpedia.org/property/populationTotal"';
				$o = '"'.$populationTotal.'"';
				$triplets .= "$s,$p,$o" . $NL;

				$s = '"'.$org.'"';
				$p = '"http://dbpedia.org/property/populationAsOf"';
				$o = '"2011"';
				$triplets .= "$s,$p,$o" . $NL;

				$s = '"'.$org.'"';
				$p = '"http://dbpedia.org/property/sexRatio"';
				$sexRatio = ($populationMen/$populationTotal)*100;
				$o = '"'.$sexRatio.'"';
				$triplets .= "$s,$p,$o" . $NL;

				$s = '"'.$org.'"';
				$p = '"http://dbpedia.org/ontology/populationDensity"';
				$o = '"'.$populationDensity.'"';
				$triplets .= "$s,$p,$o" . $NL;

				$fp = fopen($outfile1, "a");
				fwrite($fp, $triplets);
				fclose($fp);
			}
		}

	}

	if ($case=="Municipalities" || $case=="RegionalUnits") {
		dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");
	}

} // bundlePsgrRelevantOrganisationsMethod3

?>