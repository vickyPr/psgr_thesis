<?php

require_once('../common/global_routines.php');

/***********************************************************************/
/***********************************************************************/

// return requestURL
function createRequestURL($endpoint, $format, $term, $case)
{

	switch ($case)
	{
		case "dbpedia1":
			$query = '
			SELECT DISTINCT * WHERE {
			?ota a psgr:PaymentAgent ; psgr:validName ?validName ; psgr:cpaCode ?cpaCode .
			{?ota a psgrOrg:PublicLegalEntity.} UNION {?ota a psgrOrg:OtherLegalEntity.}
			OPTIONAL {?ota psgr:registrationDate ?regDate ; psgr:stopDate ?stopDate.}
			FILTER (?cpaCode != "Null" && (?stopDate = "Null" || ?stopDate = "") &&
			REGEX(?validName, "^'.$term.'$", "i"))
			} ORDER BY ?validName DESC(?regDate)
			';
			break;
		case "ownership1":
			$query = '
			SELECT distinct ?org ?orgName (sum(xsd:decimal(?am)) as ?sum) ?regDate ?stopDate WHERE {
			?decision psgr:decisionOrganization <'.$term.'> ; psgr:refersTo ?payment .
			?payment psgr:payer ?org ; psgr:paymentAmount ?am .
			?org psgr:validName ?orgName ; psgr:cpaCode ?cpaCode .
			OPTIONAL { ?org psgr:registrationDate ?regDate ; psgr:stopDate ?stopDate . }
			FILTER (?cpaCode != "Null" &&
			( REGEX(?orgName, "ΔΗΜΟΣ ", "i") || REGEX(?orgName, "ΔΗΜΟΥ ", "i") ||
			REGEX(?orgName, "ΔΗΜΟΤΙΚ", "i") || REGEX(?orgName, "ΣΥΝΔΕΣΜ", "i") ||
			REGEX(?orgName, "ΠΟΛΙΤ", "i") || REGEX(?orgName, "ΑΘΛΗΤ", "i") ||
			REGEX(?orgName, "ΕΠΙΤΡ", "i") || REGEX(?orgName, "ΚΟΙΝΩΦΕΛ", "i") ) )
			} ORDER BY DESC(?sum)
			';
			break;
		case "ownership2":
			$query = '
			SELECT ?org ?orgName WHERE {
			?org a psgr:Organization ; psgr:organizationName ?orgName .
			filter(regex(?orgName,"^'.$term.'$","i"))
			} ORDER BY ?orgName
			';
			break;
		case "RelOrgM2":
			$term = explode("|",$term);
			$query = '
			SELECT DISTINCT ?org ?validName ?cpaCode ?cpaGreekSubject WHERE {
			?org a psgr:PaymentAgent ; psgr:validName ?validName
			; psgr:cpaCode ?cpaCode ; psgr:cpaGreekSubject ?cpaGreekSubject
			; psgr:postalZipCode ?postalZipCode ; psgr:postalCodeArea ?orgPca .
			<'.$term[2].'> psgr:postalCodeArea ?pca .
			?geo a psgrGeo:Municipality ; psgrGeo:hasPart ?pca ; psgrGeo:hasPart ?munGeoPart .
			FILTER (?cpaCode != "Null" && ?munGeoPart = ?orgPca &&
			(REGEX(?validName, "'.$term[0].'", "i") || REGEX(?validName, "'.$term[1].'", "i")))
			} ORDER BY ?validName
			';
			break;
		case "RelOrgM2B":
			$term = explode("|",$term);
			$query = '
			SELECT DISTINCT ?org ?validName ?cpaCode ?cpaGreekSubject (sum(xsd:decimal(?am)) as ?sum) WHERE {
			?org a psgr:PaymentAgent ; psgr:validName ?validName
			; psgr:cpaCode ?cpaCode ; psgr:cpaGreekSubject ?cpaGreekSubject
			; psgr:postalZipCode ?postalZipCode ; psgr:postalCodeArea ?orgPca .
			?payment psgr:payer ?org ; psgr:paymentAmount ?am .
			<'.$term[2].'> psgr:postalCodeArea ?pca .
			?geo a psgrGeo:Municipality ; psgrGeo:hasPart ?pca ; psgrGeo:hasPart ?munGeoPart .
			FILTER (?cpaCode != "Null" && ?munGeoPart = ?orgPca &&
			(REGEX(?validName, "'.$term[0].'", "i") || REGEX(?validName, "'.$term[1].'", "i")))
			} ORDER BY DESC(?sum)
			';
			break;
		case "RelOrgM3":
			$term = explode("|",$term);
			$query = '
			SELECT DISTINCT ?org ?validName ?cpaCode ?cpaGreekSubject WHERE {
			?org a psgr:PaymentAgent ; psgr:validName ?validName
			; psgr:cpaCode ?cpaCode ; psgr:cpaGreekSubject ?cpaGreekSubject
			; psgr:postalZipCode ?postalZipCode ; psgr:postalCodeArea ?orgPca .
			<'.$term[2].'> psgr:postalCodeArea ?pca .
			?geo a psgrGeo:Municipality ; psgrGeo:hasPart ?pca ; psgrGeo:hasPart ?munGeoPart .
			FILTER (?cpaCode = "84111102" && ?munGeoPart = ?orgPca && 
			!REGEX(?validName, "ΠΕΡΙΦΕΡΕΙΑ", "i") && !REGEX(?validName, "ΝΟΜΑΡΧΙΑΚΗ", "i")) 
			} ORDER BY ?validName
			';
			break;
		case "OldNewM1":
			$query = '
			SELECT DISTINCT * WHERE {
			?m a psgrGeo:Municipality
			; psgrGeo:hasPart <http://publicspending.medialab.ntua.gr/resource/postalCodeArea/'.$term.'>
			; psgrGeo:name ?name ; psgrGeo:ypesCode ?ypesCode .
			filter(lang(?name)="el")
			} ORDER BY ?name
			';
			break;
		case "greeklod1":
			$query = '
			SELECT DISTINCT * WHERE {
			?municipality a kalikratis:municipalities; kalikratis:municipalities_name ?name; kalikratis:municipalities_name_en ?name_en .
			OPTIONAL { ?municipality owl:sameAs ?dbpediaUri }
			} LIMTIT 500
			';
			break;
		default:
			;
	}

	//echo $query; die();

	if ($case=="RelOrgM1" or $case=="OldNewM1") {
		$requestURL = $endpoint . 'default-graph-uri=&query=' . urlencode($query) . '&format=' . $format;
	}
	else {
		$requestURL = $endpoint . 'query=' . urlencode($query) . '&format=' . $format;
	}

	//echo $requestURL; die();
	return $requestURL;
} // createRequestURL

/***********************************************************************/
/***********************************************************************/

// Inpout: Table of Kallikratis Municipalities
function bundlePsgrDbpedia($municipalities, $outfile1, $outfile2, $outfile3)
{
	global $NL;

	$cnt1 = $cnt2 = $cnt3 = 0;

	// Backup files
	backupFile($outfile1);
	backupFile($outfile2);
	backupFile($outfile3);

	foreach ($municipalities as $m) {
		if (!empty($m[0]) && !empty($m[1]) && !empty($m[2])) {
			$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
			$format = 'json';
			$term = $m[1];
			$case = "dbpedia1";

			$search = explode(",","ἰ,ή,ί,ύ,ό,ώ,ά,έ,ϊ,ΐ,Ή,Ί,Ύ,Ό,Ώ,Ά,Έ, - ,-, & ,&");
			$replace = explode(",","ι,η,ι,υ,ο,ω,α,ε,ι,ι,Η,Ι,Υ,Ο,Ω,Α,Ε,.*,.*,.*,.*");
			$term = str_replace($search, $replace, $term);

			$requestURL = createRequestURL($endpoint, $format, $term, $case);
			//echo $requestURL;
			$responseArray = json_decode(request($requestURL), true);
			$cnt = count($responseArray['results']['bindings']);
			//echo printArray($responseArray);

			// Try again
			if ($cnt==0) {
				// TODO: Remove some of them in future
				// Some special cases
				$search = explode(",","Αγιας,Αγιου,Νεας,Δημος Μαλεβιζιου,Δημος Καλυμνιων,Δημος Βυρωνος,Δημος Αλοννησου,Δημος Μυκονου,Δημος Παλαιου Φαληρου,Δημος Πειραιως,Δημος Πετρουπολεως,Δημος Πορου,Δημος Ρεθυμνου,Δημος Φουρνων Κορσεων");
				$replace = explode(",","Αγ.*,Αγ.*,Ν.*,Δημος..Μαλεβιζιου,Δημος Καλυμνου,Δημος Βυρωνα,Δημος Αλονησσου,Δημος Μυκονιων,Δημος Π.* Φαληρου,Δημος Πειραια,Δημος Πετρουπολης,Δημος Πορου Τροιζηνιας,Δημος Ρεθυμνης,Δημος Φουρνων");
				$term = str_replace($search, $replace, $term);

				$requestURL = createRequestURL($endpoint, $format, $term, $case);
				$responseArray = json_decode(request($requestURL), true);
				$cnt = count($responseArray['results']['bindings']);
			}
			/*
			 // Try again
			if ($cnt==0) {
			$term = mb_substr($term,0,-3,'UTF-8').".*";
			$requestURL = createRequestURL($endpoint, $format, $term, $case);
			$responseArray = json_decode(request($requestURL), true);
			$cnt = count($responseArray['results']['bindings']);
			}
			*/
			//$regDate = '"'.$responseArray['results']['bindings'][0]['regDate']['value'].'"';
			//$stopDate = '"'.$responseArray['results']['bindings'][0]['stopDate']['value'].'"';
			//$stopDate2 = '"'.$responseArray['results']['bindings'][1]['stopDate']['value'].'"';
			//dout("<span style='color:green'>stopDate($stopDate)($stopDate2)</span>");

			$n1 = '"'.$responseArray['results']['bindings'][0]['validName']['value'].'"';
			$n2 = '"'.$responseArray['results']['bindings'][1]['validName']['value'].'"';

			if ($cnt==0) {
				dout("<span style='color:red;'>$m[1] -> $term [$cnt]</span>");
				$cnt1++;
			}
			elseif ($cnt==1) {
				dout("<span style='color:black'>$m[1] -> $term [$cnt] [$n1]</span>");
				$cnt2++;
			}
			elseif ($cnt>1) {
				dout("<span style='color:blue'>$m[1] -> $term [$cnt] [$n1] [$n2]</span>");
				$cnt3++;
			}

			if ($cnt>0) {
				$s_org = $responseArray['results']['bindings'][0]['ota']['value'];
				
				$s = '"'.$s_org.'"';
				$p = '"http://www.w3.org/2002/07/owl#sameAs"';
				$o = '"'.$m[2].'"';
				$triplet = "$s,$p,$o";

				$fp = fopen($outfile1, "a");
				fwrite($fp, $triplet . $NL);
				fclose($fp);

				$s = ''.$s_org.'';
				$p = ''.$m[1].'';
				$o = ''.$responseArray['results']['bindings'][0]['validName']['value'].'';
				$triplet = "$s;$p;$o";

				$fp = fopen($outfile2, "a");
				fwrite($fp, $triplet . $NL);
				fclose($fp);

				$s = '"'.$s_org.'"';
				// TODO: TO CHANGE THE PREDICATE NAME
				$p = '"http://publicspending.medialab.ntua.gr/ontology#ypesCode"';
				$o = '"'.str_replace('http://greek-lod.math.auth.gr/kalikratis/resource/municipalities/','',$m[0]).'"';
				$triplet = "$s,$p,$o";
				 
				$fp = fopen($outfile3, "a");
				fwrite($fp, $triplet . $NL);
				fclose($fp);

			}
		}

	}

	dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");

} // bundlePsgrDbpedia

/******************************************************************************/
/******************************************************************************/

// Inpout: Organization URIs list
function bundlePsgrRelevantOrganisationsMethod1($municipalities, $outfile1, $outfile2)
{
	global $NL;

	$cnt1 = $cnt2 = $cnt3 = 0;

	// Backup file
	backupFile($outfile1);
	backupFile($outfile2);

	foreach ($municipalities as $rec) {
		if (!empty($rec[0]) && !empty($rec[1])) {
			$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
			$format = 'json';
			$term = $rec[0]; // organization uri
			$case = 'ownership1';

			$requestURL = createRequestURL($endpoint, $format, $term, $case);
			//echo $requestURL; die();
			$responseArray = json_decode(request($requestURL), true);
			$cnt = count($responseArray['results']['bindings']);
			//echo printArray($responseArray);

			dout("--------- Municipality: $rec[1]---------");
			if ($cnt>0) {
				$s_org = $s_regDate = $s_orgName = '';
				 
				foreach ($responseArray['results']['bindings'] as $res) {
					$org = $res['org']['value'];
					$orgName = $res['orgName']['value'];
					$sum = $res['sum']['value'];
					$regDate = mb_substr($res['regDate']['value'],0,10);
					$stopDate = mb_substr($res['stopDate']['value'],0,10);
			
					if (mb_substr($orgName,0,11) == 'ΔΗΜΟΣ ' && $stopDate == 'Null') {
						if ( ($s == '') || ($regDate>$s_regDate) ) {
							$s_org = $org;
							$s_regDate = $regDate;
							$s_orgName = $orgName;
						}
					}
			
				}
				 
				if ($s_org == '') {
					dout('***** Region Not found2! *****');
				}
				else {
					//dout("[$s_orgName][$s_org][$s_regDate]");
			
					$cnt = count($responseArray['results']['bindings']);
						
					if ($cnt==0) {
						dout("<span style='color:red;'>--------- Results: [$cnt] ---------</span>");
						$cnt1++;
					}
					elseif ($cnt==1) {
						dout("<span style='color:orange;'>--------- Results: [$cnt] ---------</span>");
						$cnt2++;
					}
					elseif ($cnt>1) {
						dout("<span style='color:black;'>--------- Results: [$cnt] ---------</span>");
						$cnt3++;
					}
			
					$terms_to_log = "orgName|sum|regDate|stopDate";
					createRelevantOranizationsTriplets($s_org, $responseArray['results']['bindings'], $outfile1, $outfile2, $rec[1], $terms_to_log);
			
				} // elseS
			} // if
			
		} // if
	} // foreach

	dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");
	
} // bundlePsgrRelevantOrganisationsMethod1

/******************************************************************************/
/******************************************************************************/

// Inpout: Table of Kallikratis Municipalities that exist in psgr!!!
// Columns: psgr:paymentAgent URI, Kallikratis name, psgr:paymentAgent validName
function bundlePsgrRelevantOrganisationsMethod2($municipalities, $outfile1, $outfile2)
{
	global $NL;

	$cnt1 = $cnt2 = $cnt3 = 0;

	// Backup files
	backupFile($outfile1);
	backupFile($outfile2);

	foreach ($municipalities as $m) {
		if (!empty($m[0]) && !empty($m[1]) && !empty($m[2])) {
			$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
			$format = 'json';
			$term1 = $m[1]; // Kallikratis name
			$term2 = $m[2]; // psgr:paymentAgent validName
			$term3 = $m[0]; // paymentAgent URI
			$case = "RelOrgM2B";

			dout("--------- Municipality: $m[1] ---------");

			$search = explode(",","ἰ,ή,ί,ύ,ό,ώ,ά,έ,ϊ,ΐ,Ή,Ί,Ύ,Ό,Ώ,Ά,Έ, - ,-, & ,&,Δημος ,ΔΗΜΟΣ ,Αγιας ,Αγιου ,Νεας, ΑΤΤΙΚΗΣ, Αττικης, ΚΡΗΤΗΣ, Κρητης");
			$replace = explode(",","ι,η,ι,υ,ο,ω,α,ε,ι,ι,Η,Ι,Υ,Ο,Ω,Α,Ε,.*,.*,.*,.*,,,Αγ.*,Αγ.*,Ν.*,,,,");
			$term1 = str_replace($search, $replace, $term1);
			$term2 = str_replace($search, $replace, $term2);

			$term = "$term1|$term2|$term3";
				
			dout("--------- Search criteria: $term ---------");

			$requestURL = createRequestURL($endpoint, $format, $term, $case);
			//echo $requestURL; die();
			$responseArray = json_decode(request($requestURL), true);
			$cnt = count($responseArray['results']['bindings']);
			//echo printArray($responseArray);

			if ($cnt==0) {
				dout("<span style='color:red;'>--------- Results: [$cnt] ---------</span>");
				$cnt1++;
			}
			elseif ($cnt==1) {
				dout("<span style='color:orange;'>--------- Results: [$cnt] ---------</span>");
				$cnt2++;
			}
			elseif ($cnt>1) {
				dout("<span style='color:black;'>--------- Results: [$cnt] ---------</span>");
				$cnt3++;
			}
			
			$terms_to_log = "validName|cpaCode|cpaGreekSubject|sum";
			createRelevantOranizationsTriplets($m[0], $responseArray['results']['bindings'], $outfile1, $outfile2, $m[1], $terms_to_log);
				
		} // if		
	} // foreach

	dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");

} // bundlePsgrRelevantOrganisationsMethod2

/******************************************************************************/
/******************************************************************************/

// Inpout: Table of Kallikratis Municipalities that exist in psgr!!!
// Columns: psgr:paymentAgent URI, Kallikratis name, psgr:paymentAgent validName
function bundlePsgrRelevantOrganisationsMethod3($municipalities, $outfile1, $outfile2)
{
	global $NL;

	$cnt1 = $cnt2 = $cnt3 = 0;

	// Backup files
	backupFile($outfile1);
	backupFile($outfile2);

	foreach ($municipalities as $m) {
		if (!empty($m[0]) && !empty($m[1]) && !empty($m[2])) {
			$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
			$format = 'json';
			$term1 = $m[1]; // Kallikratis name
			$term2 = $m[2]; // psgr:paymentAgent validName
			$term3 = $m[0]; // paymentAgent URI
			$case = "RelOrgM3";

			dout("--------- Municipality: $m[1] ---------");

			$term = "$term1|$term2|$term3";

			dout("--------- Search criteria: $term ---------");

			$requestURL = createRequestURL($endpoint, $format, $term, $case);
			//echo $requestURL; die();
			$responseArray = json_decode(request($requestURL), true);
			$cnt = count($responseArray['results']['bindings']);
			//echo printArray($responseArray);

			if ($cnt==0) {
				dout("<span style='color:red;'>--------- Results: [$cnt] ---------</span>");
				$cnt1++;
			}
			elseif ($cnt==1) {
				dout("<span style='color:orange;'>--------- Results: [$cnt] ---------</span>");
				$cnt2++;
			}
			elseif ($cnt>1) {
				dout("<span style='color:black;'>--------- Results: [$cnt] ---------</span>");
				$cnt3++;
			}

			$terms_to_log = "validName|org";
			createRelevantOranizationsTriplets($m[0], $responseArray['results']['bindings'], $outfile1, $outfile2, $m[1], $terms_to_log);
				
		}

	}

	dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");

} // bundlePsgrRelevantOrganisationsMethod3

/******************************************************************************/
/******************************************************************************/

function bundlePsgrRelevantOrganisationsOldNew($munPaymentAgentOldNewBundle, $munPaymentAgentsYpesCodes, $outfile) {

	// Backup files
	backupFile($outfile);

	// Foreach municipality search for the New Kalikratis municipality with ypesCode
	foreach ($munPaymentAgentOldNewBundle as $k1 => $v1) {
		$newMunicipality = '';
		$municipality = $v1[0];
		$newMunicipalityYpesCode = $v1[2];
	
		foreach ($munPaymentAgentsYpesCodes as $k2 => $v2) {
			$search = $v2[2];
	
			if ($newMunicipalityYpesCode==$search) {
				$newMunicipality = $v2[0];
				break;
			}
		}
	
		if (!empty($newMunicipality)) {
			$table[] = array ($municipality,'belongsTo',$newMunicipality);
		}
	}	
	
	$group_name = "";
	$responseArray = array();
	$i = 0;
	foreach ($table as $column) {
		$s_org = $column[2];
		
		if (!empty($group_name) && ($s_org!= $group_name)) {
			createRelevantOranizationsTriplets($group_name, $responseArray, $outfile);
			$responseArray = array();
			$i = 0;
		}
		
		$group_name = $s_org;
		$responseArray[$i]['org']['value'] = $column[0];
		$i++;
	}
	
} // bundlePsgrRelevantOrganisationsOldNew

/******************************************************************************/
/******************************************************************************/

function greeklodTest() {
	$endpoint = 'http://greek-lod.math.auth.gr/kalikratis/snorql?';
	$format = 'json';
	$term = '';
	$case = "greeklod1";

	$requestURL = createRequestURL($endpoint, $format, $term, $case);
	echo $requestURL;
	$responseArray = json_decode(request($requestURL), true);
	$cnt = count($responseArray['results']['bindings']);
	echo printArray($responseArray);

}
?>