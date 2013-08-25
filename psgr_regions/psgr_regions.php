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
				?org a psgr:PaymentAgent ; psgr:validName ?validName ; psgr:cpaCode ?cpaCode .
				{ ?org a psgrOrg:PublicLegalEntity . } UNION { ?org a psgrOrg:OtherLegalEntity . }
				OPTIONAL { ?org psgr:registrationDate ?regDate; psgr:stopDate ?stopDate . }
				FILTER ( ?cpaCode != "Null" && REGEX(?validName, "^'.$term.'$", "i") )
				} ORDER BY DESC(?regDate)
        	';
		break;
      	case "RelOrgM2_NOT_USED":
      		$term = explode("|",$term);
			$query = '
				SELECT DISTINCT ?org ?validName ?cpaCode ?cpaGreekSubject (sum(xsd:decimal(?am)) as ?sum) WHERE {
				?org a psgr:PaymentAgent ; psgr:validName ?validName ; psgr:cpaCode ?cpaCode
				; psgr:cpaGreekSubject ?cpaGreekSubject ; psgr:postalCodeArea ?orgPca .
				?payment psgr:payer ?org ; psgr:paymentAmount ?am .
				<'.$term[1].'> psgr:postalCodeArea ?pca .
				?geo1 a psgrGeo:Region ; psgrGeo:hasPart ?pca .
				?geo2 a psgrGeo:Region ; psgrGeo:hasPart ?orgPca .
				FILTER (?cpaCode != "Null" && ?geo1 = ?geo2 && REGEX(?validName, "'.$term[0].'", "i"))
				} ORDER BY DESC(?sum)
        	';
		break;		
		case "RelOrgM2":
			$term = explode("|",$term);
			$query = '
				SELECT DISTINCT ?org ?validName ?cpaCode ?cpaGreekSubject (sum(xsd:decimal(?am)) as ?sum) WHERE {
				?org a psgr:PaymentAgent ; psgr:validName ?validName ; psgr:cpaCode ?cpaCode
				; psgr:cpaGreekSubject ?cpaGreekSubject ; psgr:postalZipCode ?zipCode .
			    ?payment psgr:payer ?org ; psgr:paymentAmount ?am .
				FILTER (?cpaCode != "Null" && REGEX(?validName, "'.$term[0].'", "i") && 
						!REGEX(?validName, "ΕΙΟΝΟΜΙΚΗ", "i") && 
						!REGEX(?validName, "ΑΠΟΚΕΝΤΡΩΜΕΝΗ", "i") &&
						!REGEX(?validName, "ΣΤΕΡΕΩΝ ΑΠΟΒΛΗΤΩΝ", "i") && 
						!REGEX(?validName, "ΕΝΩΣΗ ΔΗΜΩΝ", "i")  && 
						!REGEX(?validName, "ΠΡΩΤΟΒΑΘΜΙΑΣ ΚΑΙ ΔΕΥΤΕΡΟΒΑΘΜΙΑΣ", "i") &&
						!REGEX(?validName, "Α`ΘΜΙΑΣ ΚΑΙ Β`ΘΜΙΑΣ", "i") &&
						!REGEX(?validName, "ΠΘΜΙΑΣ  ΔΘΜΙΑΣ ΕΚΠΣΗΣ", "i") ) .
    			} ORDER BY DESC(?sum)
        	';
		break;
		case "RelOrgM1":
			$query = '
				SELECT DISTINCT ?org ?orgName (sum(xsd:decimal(?am)) as ?sum) ?regDate ?stopDate WHERE {
				?decision psgr:decisionOrganization <'.$term.'> ; psgr:refersTo ?payment .
				?payment psgr:payer ?org ; psgr:paymentAmount ?am .
				?org psgr:validName ?orgName ; psgr:cpaCode ?cpaCode .
				OPTIONAL { ?org psgr:registrationDate ?regDate ; psgr:stopDate ?stopDate . }
				FILTER (?cpaCode != "Null" && 
				 ( REGEX(?orgName, "ΠΕΡΙΦΕΡΕΙΑ ", "i") || 
				   REGEX(?orgName, "ΕΝΔΙΑΜΕΣΗ ΔΙΑΧΕΙΡΙΣΤΙΚΗ ΑΡΧΗ ΠΕΡ", "i") ||
				   REGEX(?orgName, "ΠΕΡΙΦΕΡΕΙΑΚΟ ΤΑΜΕΙΟ ΑΝΑΠΤΥΞΗΣ ", "i") ) )
				} ORDER BY DESC(?sum)
			'; 
		break;		

		default:
        	;
    }

    //echo $query; die();
    
    if ($case=="RelOrgM2") {
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

// Inpout: Table of Kallikratis Regions
function bundlePsgrDbpedia($regions, $outfile1, $outfile2, $outfile3, $outfile4) 
{
  global $NL;
  
  $cnt1 = $cnt2 = $cnt3 = 0;

  // Backup files
  backupFile($outfile1);
  backupFile($outfile2);
  backupFile($outfile3);
  backupFile($outfile4);
  
  foreach ($regions as $rec) {
    if (!empty($rec[0]) && !empty($rec[1]) && !empty($rec[2]) && !empty($rec[3])) {
        $endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
        $format = 'json';
        $term = $rec[1];
        $case = "dbpedia1";
        
        dout("--------- Region: $rec[1] ---------");
                
        $search = explode(",","ή,ί,ύ,ό,ώ,ά,έ,ϊ,ΐ,Ή,Ί,Ύ,Ό,Ώ,Ά,Έ, - ,-, & ,&, και,Ελλαδας,νησων");
        $replace = explode(",","η,ι,υ,ο,ω,α,ε,ι,ι,Η,Ι,Υ,Ο,Ω,Α,Ε,.*,.*,.*,.*,.*,Ελλαδ.ς,νησ.*ν");
        $term = str_replace($search, $replace, $term);

        dout("--------- Search criteria: $term ---------");
        
        $requestURL = createRequestURL($endpoint, $format, $term, $case);
        //echo $requestURL;
        $responseArray = json_decode(request($requestURL), true);
        $cnt = count($responseArray['results']['bindings']);
        //echo printArray($responseArray);          

        if ($cnt==0) {
        	dout("<span style='color:red;'>--------- Results: [$cnt] ---------</span>");
			$cnt1++;
        }
        elseif ($cnt==1) {
        	dout("<span style='color:green;'>--------- Results: [$cnt] ---------</span>");
			$cnt2++;
        }
        elseif ($cnt>1) {
        	dout("<span style='color:blue;'>--------- Results: [$cnt] ---------</span>");
			$cnt3++;
        }

        if ($cnt>0) {

        	$s_org = $responseArray['results']['bindings'][0]['org']['value'];
        	 
        	foreach ($responseArray['results']['bindings'] as $res) {
        		$validName = $res['validName']['value'];
        		$cpaCode = $res['cpaCode']['value'];
        		$regDate = mb_substr($res['regDate']['value'],0,10);
        		$stopDate = mb_substr($res['stopDate']['value'],0,10);
        		
        		dout("[$validName][$cpaCode][$regDate][$stopDate]");        	
        	} // foreach
        		 
        	$s = '"'.$s_org.'"';
        	$p = '"http://www.w3.org/2002/07/owl#sameAs"';
        	$o = '"'.$rec[2].'"';
        	$triplet = "$s,$p,$o";
        	 
        	$fp = fopen($outfile1, "a");
        	fwrite($fp, $triplet . $NL);
        	fclose($fp);
        	 
        	$s = '"'.$s_org.'"';
        	$p = '"http://www.w3.org/2002/07/owl#sameAs"';
        	$o = '"'.$rec[3].'"';
        	$triplet = "$s,$p,$o";
        	 
        	$fp = fopen($outfile2, "a");
        	fwrite($fp, $triplet . $NL);
        	fclose($fp);
        	 
        	$s = ''.$s_org.'';
        	$p = ''.$rec[1].'';
        	$o = ''.$responseArray['results']['bindings'][0]['validName']['value'].'';
        	$triplet = "$s;$p;$o";
        	 
        	$fp = fopen($outfile4, "a");
        	fwrite($fp, $triplet . $NL);
        	fclose($fp);        	

        	// Old - New Region
        	if ($cnt==2) {
        		createRelevantOranizationsTriplets($s_org, $responseArray['results']['bindings'], $outfile3);
        	} // if
        	     
        } // if
        
    }
  
  }

  dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");

} // bundlePsgrDbpedia

/******************************************************************************/
/******************************************************************************/

// Inpout: Organization URIs list
function bundlePsgrRelevantOrganisationsMethod1($regions, $outfile1, $outfile2) 
{
  global $NL;
  
  $cnt1 = $cnt2 = $cnt3 = 0;

  // Backup file
  backupFile($outfile1);
  backupFile($outfile2);
  
  foreach ($regions as $rec) {
    if (!empty($rec[0]) && !empty($rec[1])) {
        $endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
        $format = 'json';
        $term = $rec[0]; // organization uri
        $case = 'RelOrgM1';
        
        $requestURL = createRequestURL($endpoint, $format, $term, $case);
        //echo $requestURL; die();
        $responseArray = json_decode(request($requestURL), true);
        $cnt = count($responseArray['results']['bindings']);
        //echo printArray($responseArray);          
        
        dout("--------- Region: $rec[1]---------");
        if ($cnt>0) {
        	$s_org = $s_regDate = $s_orgName = '';
        	
        	foreach ($responseArray['results']['bindings'] as $res) {
        		$org = $res['org']['value'];
        		$orgName = $res['orgName']['value'];
        		$sum = $res['sum']['value'];
        		$regDate = mb_substr($res['regDate']['value'],0,10);
        		$stopDate = mb_substr($res['stopDate']['value'],0,10);

        		if (mb_substr($orgName,0,21) == 'ΠΕΡΙΦΕΡΕΙΑ ' && $stopDate == 'Null') {
	           		if ( ($s == '') || ($regDate>$s_regDate) ) {
	           			$s_org = $org;
	           			$s_regDate = $regDate;
	           			$s_orgName = $orgName;
	           		}
        		}

        	}
        	
        	if ($s_org == '') {
        		dout('***** Region Not found! *****');
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
        		
        	} // if
        }
    }
  
  }
  
  dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");

} // bundlePsgrRelevantOrganisationsMethod1

/******************************************************************************/
/******************************************************************************/

// Inpout: Table of Kallikratis Regions
// Columns: psgr:paymentAgent URI, Kallikratis name, psgr:paymentAgent validName
function bundlePsgrRelevantOrganisationsMethod2($regions, $outfile1, $outfile2)
{
	$cnt1 = $cnt2 = $cnt3 = 0;

	// Backup files
	backupFile($outfile1);
	backupFile($outfile2);
	
	foreach ($regions as $rec) {
		if (!empty($rec[0]) && !empty($rec[1]) && !empty($rec[2])) {
			$endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
			$format = 'json';
			$term1 = $rec[2]; // psgr:paymentAgent validName
			$term2 = $rec[0]; // paymentAgent URI
			$case = "RelOrgM2";

			dout("--------- Region: $rec[1] ---------");
				
			$search = explode(","," - ,-, & ,&, ΚΑΙ,ΕΛΛΑΔΑΣ,ΝΗΣΩΝ,ΠΕΡΙΦΕΡΕΙΑ,ΝΟΤΙΟΥ,ΒΟΡΕΙΟΥ,ΔΥΤΙΚΗΣ,ΚΕΝΤΡΙΚΗΣ,ΑΝΑΤΟΛΙΚΗΣ,ΜΑΚΕΔΟΝΙΑΣ");
			$replace = explode(",",".*,.*,.*,.*,.*,ΕΛΛΑΔ.*,ΝΗΣ.*,ΠΕΡ.*Φ.*,ΝΟΤ.*,ΒΟΡ.*,ΔΥΤ.*,ΚΕΝΤΡ.*,ΑΝ.*,ΜΑΚΕΔ.*");
			$term1 = str_replace($search, $replace, $term1);

			$term = "$term1|$term2";
			
			dout("--------- Search criteria: $term ---------");
				
			$requestURL = createRequestURL($endpoint, $format, $term, $case);
			//echo $requestURL; die();
			$responseArray = json_decode(request($requestURL), true);
			//echo printArray($responseArray);
			
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
						
			$terms_to_log = "validName|cpaCode|sum";				
			createRelevantOranizationsTriplets($rec[0], $responseArray['results']['bindings'], $outfile1, $outfile2, $rec[1], $terms_to_log);

		} // if

	} // foreach

	dout("$cnt1(not fount),$cnt2(exact found),$cnt3(more than one)");

} // bundlePsgrRelevantOrganisationsMethod2

/******************************************************************************/
/******************************************************************************/

?>