<?php

require_once('../common/global_routines.php');

/***********************************************************************/
/***********************************************************************/

// Get distinct properties
// return a table with dbpedia property URIs
function getDomainProperties($domain)
{
    global $NL;
    
    if ($handle = opendir($domain)) {
        define("READ_CHUNK_SIZE", 1024 * 100); // 100KB 
        
        $l_properties_table = array();
        
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $filename = $domain . '/' . $entry;
                if (!($fp = fopen($filename, "r"))) {
                    dout("ERROR could not open CSV input");
                    return;
                }
                
                if (filesize($filename) > 0) {
                    $prev_data         = "";
                    $no_of_reads       = -1;
                    $total_no_of_lines = 0;
                    $no_of_errors      = 0;
                    
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
                        
                        for ($l = 1; $l < $nooflines; $l++) {
                            $row = explode(";", $lines[$l]);
                            
                            if (count($row) == 3) {
                                $i = 0;
                                
                                $property      = trim($row[$i++]);
                                $hasValueFlag  = trim($row[$i++]);
                                $isValueOfFlag = trim($row[$i++]);
                                
                                if (!empty($property)) {
                                    if ($isValueOfFlag == "1") {
                                        $property = "is $property of";
                                    }
                                    $l_properties_table[$property] = $property;
                                }
                                
                            } // correct number of columns
                            else {
                                dout("Wrong number of column[" . count($row) . "]!");
                            }
                        } // for all lines in chunk    
                    } while (true); // for all chunks
                    
                    //dout("File [$filename] size > 0");
                    fclose($fp);
                } else {
                    //dout("File [$filename] size = 0");
                    fclose($fp);
                }
            } // if it is file
        } // while
        
        //setlocale(LC_COLLATE, 'nl_BE.utf8');
        usort($l_properties_table, 'strcoll'); 
        //print_r($l_properties_table);
        
        foreach ($l_properties_table as $value) {
            $properties_for_csv .= $value . $NL;
        }
        $fp = fopen($domain.'.csv', "w");
        fwrite($fp, $properties_for_csv);
        fclose($fp);
        
        closedir($handle);
    } // if
    
    return $l_properties_table;
} // getDomainProperties

function createDomainPropertiesTable($domain)
{
    global $NL;
    
    $properties_for_csv = "dbpediaURI;Name";
    $properties_table = getDomainProperties($domain);
    
    foreach ($properties_table as $value) {
        $properties_for_csv .=  ';' . $value;
    }
    
    $outfile = $domain.'_table.csv';
    if (file_exists($outfile)) {
        unlink($outfile);
    }

    $fp = fopen($outfile, "w");
    fwrite($fp, $properties_for_csv . $NL);
    fclose($fp);
    
    if ($handle = opendir($domain)) {
        define("READ_CHUNK_SIZE", 1024 * 100); // 100KB 
        
        $p_table = array();
        
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $filename = $domain . '/' . $entry;
                if (!($fp = fopen($filename, "r"))) {
                    dout("ERROR could not open CSV input");
                    return;
                }
                
                $tmp_properties_table = array();
                
                if (filesize($filename) > 0) {
                    $prev_data         = "";
                    $no_of_reads       = -1;
                    $total_no_of_lines = 0;
                    $no_of_errors      = 0;
                    
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

                        $row = explode(";", $lines[0]);
                        $uri = trim($row[0]);
                        $name = trim($row[1]);
                        $dbpediaURI = trim($row[2]);
                        $values_for_csv = "$dbpediaURI;$name";
                        
                        for ($l = 1; $l < $nooflines; $l++) {
                            $row = explode(";", $lines[$l]);
                            
                            if (count($row) == 3) {
                                  $i             = 0;
                                  $property      = trim($row[$i++]);
                                  $hasValueFlag  = trim($row[$i++]);
                                  $isValueOfFlag = trim($row[$i++]);
                                  
                                  if (!empty($property)) {
                                      if ($isValueOfFlag == "1") {
                                          $property = "is $property of";
                                      }
                                      $tmp_properties_table["$property"] = $property;
                                  }
                            } // correct number of columns
                            else {
                                dout("Wrong number of column[" . count($row) . "]!");
                            }
                        } // for all lines in chunk    
                    } while (true); // for all chunks
                    
                    foreach ($properties_table as $value) {
                        if ($tmp_properties_table[$value] == $value) {
                            $values_for_csv .= ';1';
                        } else {
                            $values_for_csv .= ';0';
                        }
                    }
                    
                    //dout("File [$filename] size > 0");
                    fclose($fp);
                } else {
                    //dout("File [$filename] size = 0");
                    fclose($fp);
                }

                $fp2 = fopen($outfile, "a");
                fwrite($fp2, $values_for_csv . $NL);
                fclose($fp2);

            } // if it is file
        } // while
        
        closedir($handle);
    } // if
    
    
} // createDomainPropertiesTable

function createRequestURL($term, $endpoint)
{
    $format = 'json';
    
    $query = "
      SELECT DISTINCT ?property BOUND(?hasValue) AS ?hasValueFlag BOUND(?isValueOf) AS ?isValueOfFlag
      WHERE {
        { <$term> ?property ?hasValue }
        UNION
        { ?isValueOf ?property <$term> }
      }
      ORDER BY (!BOUND(?hasValue)) ?property
   ";
    
    $searchUrl = $endpoint . 'query=' . urlencode($query) . '&format=' . $format;
    
    return $searchUrl;
}


/***********************************************************************/
/***********************************************************************/

function getDbpediaProperties($records,$outfolder, $endpoint) {

	foreach ($records as $rec) {
		
		if (!empty($rec[0]) && !empty($rec[1]) && !empty($rec[2])) {
		
			$requestURL    = createRequestURL($rec[2], $endpoint);
			$responseArray = json_decode(request($requestURL), true);
			dout($rec[2]);
			//echo printArray($responseArray);
			
			$search = array('http://greek-lod.math.auth.gr/kalikratis/resource/','/');
			$replace = array('','_');
			$outfile = $outfolder . '/' . str_replace($search, $replace, $rec[0]) . '.txt';
			$identifier = "$rec[0];$rec[1];$rec[2]";
			convertCSVtoJSON($outfile, $responseArray, $identifier);

		} 
	}
	
}


?>