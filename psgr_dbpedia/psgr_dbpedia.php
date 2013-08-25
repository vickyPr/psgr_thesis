<?php

require_once('../common/global_routines.php');

/***********************************************************************/
/***********************************************************************/

// return requestURL
function createRequestURL($endpoint, $format, $term, $case)
{
	// Accepted cases
	$dbpedia_queries = array('el.dbpedia','en.dbpedia','live.dbpedia');

	// Check for specific cases
	if (in_array($case, $dbpedia_queries)) {

		if ($case=='el.dbpedia') $strstarts = 'REGEX';
		else $strstarts = 'STRSTARTS';
			
		//
		// Create REGEX string
		$term1 = explode("|",$term);

		$regex = '';
		foreach($term1 as $t) {
			if (!empty($t))	$regex .= ' REGEX(STR(?label), "'.$t.'", "i") ||';
		}
		$regex = rtrim($regex,"||");

		//
		// Create CONTAINS string
		$label_contains = '';
		
		// Remove ^,$ characters
		$search = explode(",","^,$");
		$replace = explode(",",",");
		$term2 = str_replace($search, $replace, $term);
		$term2 = explode("|",$term2);
			
		foreach($term2 as $t) {
			if (!empty($t))	{
				$label_contains .= ' {?dbpediaUri rdfs:label ?label. ?label bif:contains \'"'.$t.'"\'.} UNION';
			}
		}
		$label_contains = rtrim($label_contains,"UNION");
		
		//
		// OPTIONALS
		$optionals = '
		OPTIONAL {
			?dbpediaUri dbpedia-owl:wikiPageRedirects ?wikiPageRedirects.
		}
		OPTIONAL {
			?dbpediaUri owl:sameAs ?sameAs.
			FILTER('.$strstarts.'(STR(?sameAs),"http://el.dbpedia.org/resource") || '.$strstarts.'(STR(?sameAs),"http://dbpedia.org/resource") || '.$strstarts.'(STR(?sameAs),"http://live.dbpedia.org/resource")).
		}
		OPTIONAL {
			?wikiPageRedirects owl:sameAs ?redirectSameAs.
			FILTER('.$strstarts.'(STR(?redirectSameAs),"http://el.dbpedia.org/resource") || '.$strstarts.'(STR(?redirectSameAs),"http://dbpedia.org/resource") || '.$strstarts.'(STR(?redirectSameAs),"http://live.dbpedia.org/resource")).
		}';
		
				
	}
	
    switch ($case)
    {
        case "psgr_properties":
        	$query = '
			SELECT * WHERE {
			<'.$term.'> psgr:validName ?validName; psgr:searchEngName ?searchEngName.
			OPTIONAL{<'.$term.'> psgr:engShortName ?engShortName}
			OPTIONAL{<'.$term.'> psgr:greekShortName ?greekShortName}
			OPTIONAL{<'.$term.'> psgr:engName ?engName}
			OPTIONAL{<'.$term.'> psgr:firmDescription ?firmDescription}
    		} LIMIT 1
			';
        	
        	//echo $query; die();
        break;
        
        case "el.dbpedia":
        	$query = '
			SELECT DISTINCT ?dbpediaUri ?wikiPageRedirects ?sameAs ?redirectSameAs WHERE { 
        		?dbpediaUri rdfs:label ?label.
        		FILTER(lang(?label)="el").
  				FILTER(REGEX(STR(?dbpediaUri),"http://el.dbpedia.org/resource")).
				FILTER('.$regex.').
				'.$optionals.'
			} LIMIT 20
        	';
        	
        	echo $query; die();
        break;

        case "en.dbpedia": 
        	$query = '
			SELECT DISTINCT ?dbpediaUri ?wikiPageRedirects ?sameAs ?redirectSameAs WHERE {
        		'.$label_contains.'
				OPTIONAL {?dbpediaUri dbpedia-owl:wikiPageDisambiguates ?wikiPageDisambiguates.}
				FILTER (!BOUND(?wikiPageDisambiguates)).
        		FILTER(lang(?label)="en").
        		FILTER(STRSTARTS(STR(?dbpediaUri),"http://dbpedia.org/resource")).
        		FILTER('.$regex.').
				'.$optionals.'
			} LIMIT 20
        	';
        	 
        	echo $query; die();
        break;
        	 
        case "live.dbpedia":
        	$query = '
			SELECT DISTINCT ?dbpediaUri ?wikiPageRedirects ?sameAs ?redirectSameAs WHERE {
        		'.$label_contains.'
				OPTIONAL {?dbpediaUri dbpedia-owl:wikiPageDisambiguates ?wikiPageDisambiguates.}
				FILTER (!BOUND(?wikiPageDisambiguates)).
        		FILTER(lang(?label)="en").
        		FILTER(STRSTARTS(STR(?dbpediaUri),"http://dbpedia.org/resource")).
        		FILTER('.$regex.').
				'.$optionals.'
			} LIMIT 20
        	';
        		 
        	echo $query; die();
        break;
                
		default:
        	;
    }
    
    if ($case=="el.dbpedia") {
    	$requestURL = $endpoint . 'default-graph-uri=http://el.dbpedia.org&query=' . urlencode($query) . '&format=' . $format;
    }
    elseif ($case=="en.dbpedia") {
    	$requestURL = $endpoint . 'default-graph-uri=http://dbpedia.org&query=' . urlencode($query) . '&format=' . $format;
    }
    elseif ($case=="live.dbpedia") {
    	$requestURL = $endpoint . 'default-graph-uri=http://dbpedia.org&query=' . urlencode($query) . '&format=' . $format;
    }
    else {
    	$requestURL = $endpoint . 'query=' . urlencode($query) . '&format=' . $format;
    }
    
    //echo $requestURL; die();
    return $requestURL;
} // createRequestURL

/***********************************************************************/
/***********************************************************************/

// return true/false
function psgrDdpediaMapping(
		$agents, // Array of Payment Agents
		$excludeAgentsUris, // Agents URIs to exclude
		$outfile1, // Output files
		$outfile2, // Output files
		$outfile3, // Output files
		$dbpedia,
		$write_to_file
) {
  global $NL;

  //
  // Initialize counters to zero
  $cnt1_en = $cnt2_en = $cnt3_en = 0;
  $cnt1_el = $cnt2_el = $cnt3_el = 0;
  $cnt1_live = $cnt2_live = $cnt3_live = 0;
  
  //
  // Backup files
  if ($dbpedia == "el") {
  	backupFile($outfile1);
  }
  if ($dbpedia == "en") {
  	backupFile($outfile2);
  }
  if ($dbpedia == "live") {
  	backupFile($outfile3);
  }
  if ($dbpedia == "all") {
  	backupFile($outfile1);
  	backupFile($outfile2);
  	backupFile($outfile3);
  }
  
  if (!is_array($agents) || count($agents)==0) {
  	dout("<span style='color:red'>Not valid input parameters!</span>");
  	return false;
  }
  
  foreach ($agents as $rec) {
  	//
  	// Initialize counters
  	$cnt_num_el = $cnt_num_en = $cnt_num_live = "";
  	 
  	if (!empty($rec[0])) {
        $agent_uri = $rec[0];
        
        // Skip specific agents
        if (in_array($rec[0], $excludeAgentsUris)) {
        	dout("--- Skipped ---");
        	continue;
        }
        
       	//
       	// Get payment agent info from psgr
        $endpoint = 'http://publicspending.medialab.ntua.gr/sparql?';
        $format = 'json';
        $term = $agent_uri;
        $case = "psgr_properties";
        
       	$requestURL = createRequestURL($endpoint, $format, $term, $case);
        $responseArray = json_decode(request($requestURL), true);
        $search_criteria = $responseArray['results']['bindings'][0];
        $cnt = count($search_criteria);
        //echo printArray($search_criteria);
        
        $engShortName = isset($search_criteria['engShortName']['value']) ? $search_criteria['engShortName']['value'] : "";
        $greekShortName = isset($search_criteria['greekShortName']['value']) ? $search_criteria['greekShortName']['value'] : "";
        $engName = isset($search_criteria['engName']['value']) ? $search_criteria['engName']['value'] : "";
        $validName = isset($search_criteria['validName']['value']) ? $search_criteria['validName']['value'] : "";
        $searchEngName = isset($search_criteria['searchEngName']['value']) ? $search_criteria['searchEngName']['value'] : "";
        $firmDescription = isset($search_criteria['firmDescription']['value']) ? $search_criteria['firmDescription']['value'] : "";

        //
        // Print payment agent Name
        $dout_agent_name = '<a href="'.$agent_uri.'" target="_blank">'.$validName.'</a>';
        dout("__________________________________________________________________________<br><br>");
        dout("--- $dout_agent_name ---");        
        
        //
        // Important: validName & searchEngName are mandatory fields!
	    if (empty($validName) || empty($searchEngName)) {
	    	dout("<span style='color:red'>ERROR: PSGR: NOT VALID SEARCH CRITERIA!</span>");
	    	continue;
	    }

	    //
	    // Search for Domains
	    $agent_domains = array();
	    $dout_agent_domains = "";
	    if (strpos($validName,'ΚΟΙΝΟΠΡΑΞΙΑ')!== false) $agent_domains['ΚΟΙΝΟΠΡΑΞΙΑ'] = 'ΚΟΙΝΟΠΡΑΞΙΑ';
	    if (trim($firmDescription)=='ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ') $agent_domains['ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ'] = 'ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ';
	    // TODO: change Handle freelanchers order (before after)
	    // and who is freelanchers
	    // and maybe get psgr:paymentAgentName
	    	  
	    foreach ($agent_domains as $d) {
	    	$dout_agent_domains .= "[$d]";
	    }
	    
	    //
	    // Print payment agent domains
	    if (count($agent_domains)>0) {
	    	dout("--- $dout_agent_domains ---");
	    }
	     
	    //
	    // Initialize results table
	    $results = array();

	    if ($dbpedia == "el" || $dbpedia == "all") {

	    	$elm1_engShortName = fixCriteriaGreek($engShortName, $agent_domains, true);
	    	$elm1_greekShortName = fixCriteriaGreek($greekShortName, $agent_domains, true);
	    	$elm1_validName = fixCriteriaGreek($validName, $agent_domains);
	    	
	    	$endpoint = 'http://el.dbpedia.org/sparql?';
	    	$dbpedia_case = 'el.dbpedia';
	    	$search_term_original = "$engShortName|$greekShortName|$validName";
	    	$search_term = trim(selectUniqueTerms("$elm1_engShortName|$elm1_greekShortName|$elm1_validName"),"|");
	    	
	    	$succ = handleRequest($endpoint, $dbpedia_case, $search_term_original, $search_term, $results);
	    	if (!$succ) continue;
	    	
	    	handleResults($agent_uri, $results, $agent_domains, $write_to_file, $outfile1, $cnt_num_el);
	    }

	    if ($dbpedia == "en" || $dbpedia == "all") {
	
	    	$enm1_engShortName = fixCriteria($engShortName, $agent_domains, true);
	        $enm1_engName = fixCriteria($engName, $agent_domains);
	        $enm1_searchEngName = fixCriteria($searchEngName, $agent_domains);

	        $endpoint = 'http://dbpedia.org/sparql?';
	    	$dbpedia_case = 'en.dbpedia';
	    	$search_term_original = "$engShortName|$engName|$searchEngName";
	        $search_term = trim(selectUniqueTerms("$enm1_engShortName|$enm1_engName|$enm1_searchEngName"),"|");

	    	$succ = handleRequest($endpoint, $dbpedia_case, $search_term_original, $search_term, $results);
	    	if (!$succ) continue;
	    	
	    	handleResults($agent_uri, $results, $agent_domains, $write_to_file, $outfile2, $cnt_num_en);
	    	
	    }
	    
	    if ($dbpedia == "live" || $dbpedia == "all") {
	    
	    	$enm1_engShortName = fixCriteria($engShortName, $agent_domains, true);
	    	$enm1_engName = fixCriteria($engName, $agent_domains);
	    	$enm1_searchEngName = fixCriteria($searchEngName, $agent_domains);
	    
	    	//The old php-based framework is deployed on one of OpenLink servers and currently has a SPARQL endpoint at 
	    	//$endpoint = 'http://dbpedia-live.openlinksw.com/sparql?';
	    	
	    	//The DBpedia-Live SPARQL-endpoint can be accessed at
	    	$endpoint = 'http://live.dbpedia.org/sparql?';
	    	
	    	$dbpedia_case = 'live.dbpedia';
	    	$search_term_original = "$engShortName|$engName|$searchEngName";
	    	$search_term = trim(selectUniqueTerms("$enm1_engShortName|$enm1_engName|$enm1_searchEngName"),"|");
	    
	    	$succ = handleRequest($endpoint, $dbpedia_case, $search_term_original, $search_term, $results);
	    	if (!$succ) continue;
	    
	    	handleResults($agent_uri, $results, $agent_domains, $write_to_file, $outfile3, $cnt_num_live);
	    
	    }
	     
	    if ($dbpedia == "el" || $dbpedia == "all") {
	    	if ($cnt_num_el == "1") $cnt1_el++;
	    	if ($cnt_num_el == "2") $cnt2_el++;
	    	if ($cnt_num_el == "3") $cnt3_el++;
	    }	 
  		if ($dbpedia == "en" || $dbpedia == "all") {
	    	if ($cnt_num_en == "1") $cnt1_en++;
	    	if ($cnt_num_en == "2") $cnt2_en++;
	    	if ($cnt_num_en == "3") $cnt3_en++;
  		}
	    if ($dbpedia == "live" || $dbpedia == "all") {
	    	if ($cnt_num_live == "1") $cnt1_live++;
	    	if ($cnt_num_live == "2") $cnt2_live++;
	    	if ($cnt_num_live == "3") $cnt3_live++;
	    }	     
    }
  }

  dout("_____________________________________________________________________________<br><br>");
  dout("el: [$cnt1_el] NO results, [$cnt2_el] accepted results, [$cnt3_el] NOT accepted results");
  dout("en: [$cnt1_en] NO results, [$cnt2_en] accepted results, [$cnt3_en] NOT accepted results");
  dout("live: [$cnt1_live] NO results, [$cnt2_live] accepted results, [$cnt3_live] NOT accepted results");
  
  return true;
  
} // psgrDdpediaMapping

/******************************************************************************/
/******************************************************************************/

// Inpout: Array of elements
function elementsMapping($elements, $outfile, $case, $write_to_file, $from, $to)
{
	global $NL;

	//
	// Initialize counters to zero
	$cnt1 = $cnt2 = $cnt3 = 0;

	//
	// Backup files
	backupFile($outfile);

	//
	// Slice Array of elements
	$elements = array_slice($elements, $from, $to);

	foreach ($elements as $rec) {

		if (!empty($rec[0]) && !empty($rec[1]) /*&& !empty($rec[2]) && !empty($rec[3])*/) {
			//
			// Initialize results table
			$results = array();

			if ($case == "port") {

				$code = $rec[0];
				$description = $rec[1];
				$local_description = $rec[2];
				$country_abbreviation = $rec[3];
				$g1 = $rec[4];
				
				dout("__________________________________________________________________________<br><br>");
				dout("--- $description [$code] ---");
				
				$endpoint = 'http://el.dbpedia.org/sparql?';
				$dbpedia_case = 'el.dbpedia';
				$search_term_original = "$code|$description|$local_description|$country_abbreviation|$g1";
				
				$element_domains = array();
				
				$code = fixCriteria($code,$element_domains);
				$description = fixCriteria($description,$element_domains);
				$local_description = fixCriteria($local_description,$element_domains);
				$country_abbreviation = fixCriteria($country_abbreviation,$element_domains);
				$g1 = fixCriteria($g1,$element_domains);
				
				$search_term = trim(selectUniqueTerms("$description|$local_description|$g1"),"|");
				
				$succ = handleRequest($endpoint, $dbpedia_case, $search_term_original, $search_term, $results);
				if (!$succ) continue;

				handleResults($description, $results, $element_domains, $write_to_file, $outfile, $cnt_num);

				if ($cnt_num == "1") $cnt1++;
				if ($cnt_num == "2") $cnt2++;
				if ($cnt_num == "3") $cnt3++;
			
			} // if port

		} // if
	
	} // foreach

	dout("_____________________________________________________________________________<br><br>");
	dout("[$cnt1] NO results, [$cnt2] accepted results, [$cnt3] NOT accepted results");

} // elementsMapping

/******************************************************************************/
/******************************************************************************/

function handleRequest($endpoint, $dbpedia_case, $search_term_original, $search_term, &$results) {

	$format = 'json';
	
	$requestURL = createRequestURL($endpoint, $format, $search_term, $dbpedia_case);
	$responseArray = json_decode(request($requestURL), true);
	$results = $responseArray['results']['bindings'];
	
	dout("--- $search_term_original ---");
	dout("--- $search_term ---");
	
	if (is_array($results)) {
		dout("--- Initial search results [".count($results)."] for [$dbpedia_case] ---");
		return true;
	}
	else {
		dout("<span style='color:red;font-weight:bold;'>ERROR: $dbpedia_case: NOT VALID RESULTS!</span>");
		return false;
	}

}

/******************************************************************************/
/******************************************************************************/

function handleResults($agent_uri, $results, $agent_domains, $write_to_file, $outfile, &$cnt_num) {
	
	global $NL;
	
	$triplets = "";
	$distinct_uris = array();

	if (count($results)>0) {		 
		foreach ($results as $res) {
			$dbpediaUri = isset($res['dbpediaUri']['value']) ? $res['dbpediaUri']['value'] : "";
			$wikiPageRedirects = isset($res['wikiPageRedirects']['value']) ? $res['wikiPageRedirects']['value'] : "";
			$sameAs = isset($res['sameAs']['value']) ? $res['sameAs']['value'] : "";
			$redirectSameAs = isset($res['redirectSameAs']['value']) ? $res['redirectSameAs']['value'] : "";
	
			$uri = !empty($wikiPageRedirects) ? $wikiPageRedirects : $dbpediaUri;
			if (checkDbpediaResourceURI($uri)) $distinct_uris[$uri] = $uri;
			if (checkDbpediaResourceURI($sameAs)) $distinct_uris[$sameAs] = $sameAs;
			if (checkDbpediaResourceURI($redirectSameAs)) $distinct_uris[$redirectSameAs] = $redirectSameAs;
		} // foreach
	}
		 
	$cnt = count($distinct_uris);
	 
	if ($cnt==0) {
		dout("<span style='color:red;'>--- Final results: [$cnt] ---</span>");
		$cnt_num = "1";
	}
	elseif ((!empty($agent_domains['ΚΟΙΝΟΠΡΑΞΙΑ']) && $cnt<=20) || ($cnt<=6)) {
		dout("<span style='color:green;'>--- Final results: [$cnt] ---</span>");
		$cnt_num = "2";
	}
	else {
		dout("<span style='color:red;font-weight:bold;'>--- Final results: [$cnt] - DISMISSED!!! ---</span>");
		$cnt_num = "3";
		return;
	}
	 
	foreach ($distinct_uris as $uri) {
		$s = $agent_uri;
		$p = 'http://www.w3.org/2002/07/owl#sameAs';
		$o = $uri;
		$triplets .= "$s,$p,$o".$NL;
		dout('<a href="'.$uri.'" target="_blank">'.$uri.'</a>');
	}
	 
	//
	// Write to file
	if ($write_to_file) {
		$fp = fopen($outfile, "a");
		fwrite($fp, $triplets);
		fclose($fp);
	}
	
} // handleResults

/******************************************************************************/
/******************************************************************************/

function checkDbpediaResourceURI($uri) {

	if (strstr($uri,'/resource/') && 
		!strstr($uri,'/resource/Category%3A') && 
		!strstr($uri,'/resource/Category:') && 
		!strstr($uri,'/resource/Κατηγορία%3A') && 
		!strstr($uri,'/resource/Κατηγορία:')
	) {
		return true;
	}
	else {
		return false;
	}

}

/******************************************************************************/
/******************************************************************************/

function fixCriteria($name, $agent_domains, $is_short_name=false) {

	if(empty($name)) return "";
	
	$name_fixed = trim(trim($name,"-"));

	//
	// Common words
	if (mb_strlen($name_fixed, 'UTF-8')>=10) {
		$name_fixed = " $name_fixed ";
		$search = explode("|"," XEROX HELLAS | ANONYMOS VIOMICHANIKI KAI EMPORIKI ETAIREIA | AEROPORIKON METAFORON | ANONYMOS ETAIREIA | ANONYMI EMPORIKI ETAIRIA | ANONYMI ETAIREIA | AE | SA | OE |  ");
		$replace = explode("|"," XEROX | | | | | | | | | ");
		$name_fixed = str_replace($search, $replace, $name_fixed);
		$name_fixed = trim($name_fixed);
	}
	
	//
	// Handle joint ventures
	if (!empty($agent_domains['ΚΟΙΝΟΠΡΑΞΙΑ'])) {
	
		$venture_names_fixed = "";
	
		$venture_names = fixCriteriaJointVentures($name_fixed);
	
		$words = explode("|",$venture_names);
		foreach ($words as $w) {
			$venture_names_fixed .= handleAbbreviations($w,8,12)."|";
		}
	
		return rtrim($venture_names_fixed,"|");
	}
	
	//
	// Handle freelanchers
	if (!empty($agent_domains['ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ'])) {
		;
	}
	
	$extented_names_fixed = fixCriteriaPerTerm($name_fixed, $is_short_name)."|";
	
	$words = explode(" ",$name_fixed);
	
	if (count($words)==2) {
		$l_term = $words[1]." ".$words[0];
		$extented_names_fixed .= fixCriteriaPerTerm($l_term, $is_short_name)."|";
	}
	elseif (count($words)==3) {
		//
		// We get $words[0] as more important
		$l_term = $words[0]." ".$words[1];
		$extented_names_fixed .= fixCriteriaPerTerm($l_term, $is_short_name)."|";
	
		$l_term = $words[1]." ".$words[0];
		$extented_names_fixed .= fixCriteriaPerTerm($l_term, $is_short_name)."|";
	
		$l_term = $words[0]." ".$words[2];
		$extented_names_fixed .= fixCriteriaPerTerm($l_term, $is_short_name)."|";
	
		$l_term = $words[2]." ".$words[0];
		$extented_names_fixed .= fixCriteriaPerTerm($l_term, $is_short_name)."|";
	
	}
	
	$extented_names_fixed = rtrim($extented_names_fixed,"|");
	
	return $extented_names_fixed;	
	
} // fixCriteria


/******************************************************************************/
/******************************************************************************/

function fixCriteriaPerTerm($name_fixed, $is_short_name=false) {
	
	//
	// Regex special chars
	if ($is_short_name) {
		$regex_specail_chars = explode(",",".");
		$replacement = explode(",","\\\\.?");
		$name_fixed = str_replace($regex_specail_chars, $replacement, $name_fixed);
	}
	else {
		$regex_specail_chars = explode(",",".");
		$replacement = explode(",","");
		$name_fixed = str_replace($regex_specail_chars, $replacement, $name_fixed);
	}
	
	//
	// Common connecting words
	if (mb_strlen($name_fixed, 'UTF-8')>=10) {
		$name_fixed = " $name_fixed ";
		$search = explode("|"," - |-| & |&| KAI | K |  ");
		$replace = explode("|"," ?.{0,3} ?| ?.{0,3} ?| ?.{0,3} ?| ?.{0,3} ?| .{0,3} | .{0,3} | ");
		$name_fixed = str_replace($search, $replace, $name_fixed);
		$name_fixed = trim($name_fixed);
	}
	
	//
	// Short name & Abbreviation
	$name_fixed = handleAbbreviations($name_fixed,10,20);
	
	return $name_fixed;
		
}

/******************************************************************************/
/******************************************************************************/

function fixCriteriaGreek($name, $agent_domains, $is_short_name=false) {

	if(empty($name)) return "";

	$name_fixed = trim(trim($name,"-"));

	//
	// Common words
	if (mb_strlen($name_fixed, 'UTF-8')>=10) {
		$name_fixed = " $name_fixed ";
		$search = explode("|"," ΑΝΩΝΥΜΟΣ ΒΙΟΜΗΧΑΝΙΚΗ ΚΑΙ ΕΜΠΟΡΙΚΗ ΕΤΑΙΡΕΙΑ | ΑΕΡΟΠΟΡΙΚΩΝ ΜΕΤΑΦΟΡΩΝ | ΑΝΩΝΥΜΗ ΕΜΠΟΡΙΚΗ ΕΤΑΙΡΙΑ | ΑΝΩΝΥΜΗ ΕΤΑΙΡΕΙΑ | ΑΝΩΝΥΜΟΣ ΕΤΑΙΡΕΙΑ | ΑΕ |Σ | OE |  ");
		$replace = explode("|"," | | | | | |ς | | ");
		$name_fixed = str_replace($search, $replace, $name_fixed);
		$name_fixed = trim($name_fixed);
	}
	
	//
	// Handle joint ventures
	if (!empty($agent_domains['ΚΟΙΝΟΠΡΑΞΙΑ'])) {
		
		$venture_names_fixed = "";

		$venture_names = fixCriteriaJointVentures($name_fixed);
		
		$words = explode("|",$venture_names);
		foreach ($words as $w) {
			$venture_names_fixed .= fixCriteriaGreekPerTerm($w, $is_short_name)."|";
		}
		
		return rtrim($venture_names_fixed,"|");
	}
	
	//
	// Handle freelanchers
	if (!empty($agent_domains['ΕΠΙΤΗΔΕΥΜΑΤΙΑΣ'])) {
	
		//
		// Fix freelancers' names
		$name_fixed = " $name_fixed ";
		$search = explode("|"," ΓΕΩΡΓ |  ");
		$replace = explode("|"," ΓΕΩΡΓΙΟς | ");
		$name_fixed = str_replace($search, $replace, $name_fixed);
		$name_fixed = trim($name_fixed);

	}
	
	$extented_names_fixed = fixCriteriaGreekPerTerm($name_fixed, $is_short_name)."|";
	
	$words = explode(" ",$name_fixed);
	
	if (count($words)==2) {
		$l_term = $words[1]." ".$words[0];
		$extented_names_fixed .= fixCriteriaGreekPerTerm($l_term, $is_short_name)."|";
	}
	elseif (count($words)==3) {
		//
		// We get $words[0] as more important
		$l_term = $words[0]." ".$words[1];
		$extented_names_fixed .= fixCriteriaGreekPerTerm($l_term, $is_short_name)."|";
		
		$l_term = $words[1]." ".$words[0];
		$extented_names_fixed .= fixCriteriaGreekPerTerm($l_term, $is_short_name)."|";
		
		$l_term = $words[0]." ".$words[2];
		$extented_names_fixed .= fixCriteriaGreekPerTerm($l_term, $is_short_name)."|";
		
		$l_term = $words[2]." ".$words[0];
		$extented_names_fixed .= fixCriteriaGreekPerTerm($l_term, $is_short_name)."|";
		
	}
	
	$extented_names_fixed = rtrim($extented_names_fixed,"|");
	
	return $extented_names_fixed;
}


/******************************************************************************/
/******************************************************************************/

function fixCriteriaGreekPerTerm($name_fixed, $is_short_name=false) {
	
	//
	// Common words
	$name_fixed = " $name_fixed ";
	if (mb_strlen($name_fixed, 'UTF-8')>=10) {
		$search = explode("|"," ΥΠ | ΕΛΛΑΔΑΣ | ΕΛΛΑΔΟΣ | ΝΗΣΩΝ |  ");
		$replace = explode("|"," υπ.* | ελλ.δ.ς | ελλ.δ.ς | ν.σ.*ν | ");
		$name_fixed = str_replace($search, $replace, $name_fixed);
	}
	$name_fixed = trim($name_fixed);
	
	//
	// Common connecting words
	if (mb_strlen($name_fixed, 'UTF-8')>=10) {
		$name_fixed = " $name_fixed ";
		$search = explode("|"," - |-| & |&| ΚΑΙ | Κ |  ");
		$replace = explode("|"," ?.{0,3} ?| ?.{0,3} ?| ?.{0,3} ?| ?.{0,3} ?| .{0,3} | .{0,3} | ");
		$name_fixed = str_replace($search, $replace, $name_fixed);
		$name_fixed = trim($name_fixed);
	}
	
	//
	// Greek annotation
	if (!$is_short_name) {
		$name_fixed = fixGreekAnnotation($name_fixed);
	}
	
	//
	// Short name & Abbreviation
	$name_fixed = handleAbbreviations($name_fixed,10,20);
	
	return $name_fixed;
	
}

/******************************************************************************/
/******************************************************************************/

function fixGreekAnnotation($name) {

	$name_fixed = $name;
		
	if(mb_strlen($name_fixed,'UTF-8')>5) {
		//
		// Annotation
		$greek_vowels = explode(",","Η,Ι,Υ,Ο,Ω,Α,Ε,Ή,Ί,Ύ,Ό,Ώ,Έ,Ά");
		$replacement = explode(",",".,.,.,.,.,.,.,.,.,.,.,.,.,.");
		$name_fixed = str_replace($greek_vowels, $replacement, $name_fixed);
		
		//
		// Count dots ratio
		$dots_ratio = 0.5;
		$dots_count = substr_count($name_fixed,".");
		$dots_ratio = $dots_count/mb_strlen($name_fixed,'UTF-8');
	
		//dout("--- IN [$name], OUT [$name_fixed], DOTS RATIO [$dots_ratio] ---");
	
		if((mb_strlen($name_fixed, 'UTF-8')<=10 && $dots_ratio>=0.55) || (mb_strlen($name_fixed, 'UTF-8')>10 && $dots_ratio>=0.65)) {
			return $name;
		}
	}

	return $name_fixed;
	
}

/******************************************************************************/
/******************************************************************************/

function fixCriteriaJointVentures($name) {
	
	if(empty($name)) return "";
	
	//
	// Remove relative to venture words
	$search = explode(",","J VENT ,ΚΞ ,ΚΟΙΝΟΠΡΑΞΙΑ ,JOINT VENTURE ,KOINOPRAXIA");
	$replace = explode(",",",,,,,");
	$name = str_replace($search, $replace, $name);

	//
	// Skip common words
	$common_words = array(
			"DEVELOPMENT","FUND","EU","OF","ΑΘΗΝΑ","ATHENA","ATHINA","PROJECT",
			"ΚΑΤΑΣΚΕΥΑΣΤΙΚΗ","CONSTRUCTION","CONSTRUCTIONS","KATASKEUASTIKI",
			"RAILWAY","LINE","AKTOR","TRANSPORT","EUROPEAN","AND",
			"ΑΝΩΝΥΜΗ","ΕΤΑΙΡΕΙΑ","ΚΑΙ",
			"ANONYMOS ETAIREIA","ΑΕ","AE","SA","ATE","ΑΤΕ","STS");

	$words = explode(" ",$name);
	$domain_names = "";
	foreach ($words as $w) {
		if (!in_array($w, $common_words) && mb_strlen($w)>1 && !is_numeric($w)) {
			$domain_names .= $w."|";
		}
	}

	if (!in_array($name, $common_words) && mb_strlen($name)>1 && !is_numeric($name)) {
		$domain_names = $domain_names.$name;
	}

	return trim($domain_names,"|");
	
}

/******************************************************************************/
/******************************************************************************/

function handleAbbreviations($name,$num1,$num2) {

	$name = trim($name);
	
	$exceptions1 = array('AEGEK');
	$exceptions2 = array();
	
	// Import numbers $num1 & $num2!!!
	if (mb_strlen($name, 'UTF-8')<=$num1) {
		if (!in_array($name, $exceptions1)) {
			$name = "^$name$";
		}
		else {
			if (!in_array($name, $exceptions2)) {
				$name = "^$name";
			}
		}
	}
	elseif (mb_strlen($name, 'UTF-8')<=$num2) {
		if (!in_array($name, $exceptions2)) {
			$name = "^$name";
		}
	}
	
	return $name;
}

/******************************************************************************/
/******************************************************************************/

function  selectUniqueTerms($terms) {

	$unique_terms = "";
	
	$terms_array = explode("|",$terms);
	
	$unique_terms_array = array_unique($terms_array);
	
	foreach ($unique_terms_array as $t) {
		$unique_terms .= $t."|";
	}
	
	$unique_terms = rtrim($unique_terms,"|");

	return $unique_terms;
}

/******************************************************************************/
/******************************************************************************/

?>