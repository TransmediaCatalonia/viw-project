<?php

/* receives an csv file (exported from the ELAN tool), reads it and returns different arrays to be used by google API charts.
 *
 * 	Warning!!!!! check that your csv files have no BOM!!!! You can remove BOM using: 
 *   	awk '{if(NR==1)sub(/^\xef\xbb\xbf/,"");print}' text.csv  > new.csv
 */


namespace AppBundle\Utils;

/**
 * 
 * 
 */

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class CSV 
{
    /** ????????????????????? crec que no l'utilitza ningu!!!!!!!
     * 
     * reads csv and returns:
     * array ['lemma', 'Frequency', 'Distribution', 'SourceFile'] (***not really sourcefile, only used to calculate distribution)
     * 
     */
    public function getVerbs($csvFile)
    {
	$nWords = 0;
	$words = array();
	$result = array();
	## reads csv file into $data array (input format: Semantics, BeginTime, lemma-1, BeginTime, PoS-1, BeginTime, FileName)
	
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, ",");
	    // just to skip blank lines
	    if (count($data) > 2){ 
		$nWords ++;
            	$word = $data[2];
		//checks if word was already used to modify result
		if (in_array($word, $words)){
			// looks for word in $result
			foreach ($result as &$d){ 
				if ($d['lemma'] == $word){ 	
					if ($d['source'] !== $data[6]){
						$d['source'] = $data[6];
						$d['dist']++;
						$d['freq']++ ;
					} else { 
						$d['freq']++ ;
						}
				}

			}			
		}
		//checks word, if new -> initialise & adds it in $result
		else {
			array_push($words,$word);
			
			$d = array(
		    	'lemma' => $word,
		    	'freq' => 1,
		    	'dist' => 1,
			'source' => $data[6]
			);
			array_push($result,$d);
		} 
	    }
        }

	## ['lemma', 'Frequency', 'Distribution', 'SourceFile'] ***not really sourcefile, only the last one
	
        return array($result,$nWords);
    }


/**
     * 
     * reads csv and returns:
     * returns: array ['lemma', 'SemanticClass', 'Frequency' ] 
     * 
     */
    public function getLemmaSemFreq($csvFile,$pos)
    {
	$nWords = 0;
	$words = array();
	$result = array();
	## reads csv file into $data array (input format: "Annotation1-1","Annotation2-1","Annotation3-1","TranscriptionName")
	## Motion,cruzar,VMIP3P0,"What-Aptent-ES.eaf"


	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}


	foreach ($csv as $data) {
	   if ( ( ($pos == 'N') && ( 0 === strpos($data['Annotation3-1'], $pos) ) ) ||
	        ( ($pos == 'V') && ( 0 === strpos($data['Annotation3-1'], $pos) ) && ( 0 !== strpos($data['Annotation1-1'], 'A') ) ) ||
		( ($pos == 'A') && ( ( 0 === strpos($data['Annotation3-1'], $pos) ) || (0 === strpos($data['Annotation3-1'], 'VMP')) ))
		) {
		$word = $data['Annotation2-1'];
		//checks if word was already used to modify result
		if (in_array($word, $words)){
			// looks for word in $result
			foreach ($result as &$d){ 
				if ($d['lemma'] == $word){ 	
					if ($d['sem'] !== $data['Annotation1-1']){
						$d['lemma'] = $data['Annotation2-1'];
						$d['freq']++ ;
					} else { 
						$d['freq']++ ;
						}
				}
			}			
		}
		//checks word, if new -> initialise & adds it in $result
		else {
			array_push($words,$word);
			$d = array(
		    	'lemma' => $word,
			'sem' => $data['Annotation1-1'],
		    	'freq' => 1,
			);
			array_push($result,$d);
		} 
	   } 
        }

	# arrange results 
	$values = array();
	$headers = "['Lemma','SemClass','Frequency']";
	array_push($values, $headers);
	foreach ( $result as $key ){ 
		$val = "['". $key['lemma'] . "','" . $key['sem'] . "'," . $key['freq'] . "]";
		array_push($values, $val);
	}
	#var_dump($values);
        return array($values);
    }

/**
     * 
     * reads csv and returns:
     * returns: array ['lemma', 'PoS', 'Frequency' ] 
     * 
     */
    public function getLemmaPoSFreq($csvFile)
    {
	$nWords = 0;
	$words = array();
	$result = array();
	## reads ﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"


	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}

	foreach ($csv as $data) {
		$word = $data['Annotation2-1'];
		//checks if word was already used to modify result
		if (in_array($word, $words)){
			// looks for word in $result
			foreach ($result as &$d){ 
				# we only want first character in pos tag (avoid freeling long tags)
				
				if ($d['lemma'] == $word){ 	
					if ($d['pos'] !== $data['Annotation3-1'][0]){
						$d['lemma'] = $data['Annotation2-1'];
						$d['freq']++ ;
					} else { 
						$d['freq']++ ;
						}
				}
			}			
		}
		//checks word, if new -> initialise & adds it in $result
		else {
			array_push($words,$word);
			$d = array(
		    	'lemma' => $word,
			'pos' => $data['Annotation3-1'][0],
		    	'freq' => 1,
			);
			array_push($result,$d);
		} 
	    
        }

	# arrange results 
	$values = array();
	$headers = "['Lemma','PoS','Frequency']";
	array_push($values, $headers);
	foreach ( $result as $key ){ 
		if ($key['freq'] > 2){
		$val = "['". $key['lemma'] . "','" . $key['pos'] . "'," . $key['freq'] . "]";
		array_push($values, $val);
		}
	}
	#var_dump($values);
        return array($values);
    }



 /**
     * reads csv file
     * returns: ['Provider','NumVerbs','UniqVerbs'] for all 'corpus' (num of verbs/uniq_verbs used by provider)
     */

    public function getVerbsFiles($csvFile)
    {
	##﻿csv: "Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	
	foreach ($csv as $c) { 
		#foreach ($c as $key => $value) {var_dump($key);var_dump($value);}
		$fileName = $c['TranscriptionName'];
		#$lemma = $c['Annotation1']; var_dump($lemma);
		$lemma = $c['Annotation2-1'];

		##initialize the result for this fileName because it doesnt exist yet
  		if (!isset($result[$fileName])) {
			$n = 1 ;
    			$result[$fileName] = array();
			$result[$fileName]['verbs'] = array();
		}
		++$n; 
                $result[$fileName]['lemma'] = $n;
		array_push($result[$fileName]['verbs'], $lemma);
	}
	#var_dump($result);
	
	$values = array();
	$headers = "['Provider','NumVerbs','UniqVerbs']";
	array_push($values, $headers);
	foreach ($result as $key => $value) {
		$val1 = ""; $val2 = "";
		foreach ($value as $k => $v) {	
			#	$pair = "['" . $key ."'," . $v ."]";
			#	array_push($values, $pair);
			if ($k == 'lemma'){ $val1 = $v;}
			if ($k == 'verbs'){ $unique = array_count_values($v); $val2 = count($unique); }	
		}
		$pair = "['" . $key ."'," . $val1 .", " . $val2 . "]";
		array_push($values, $pair);
	}
		
	#var_dump($values);	  
        return $values;
    }


/**
     * reads csv file
     * returns: [verb, frequency, relativeFrequency in %] for all 'corpus'
     */

    public function getAllVerbsFiles($csvFile)
    {

	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	## lemma, semantic  -> file, frequencyVerbsLemma, distinctVerbsLemma

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	
	foreach ($csv as $c) { 
		$lemma = $c['Annotation2-1'];
		##initialize the result for this Lemma because it doesnt exist yet
  		if (!isset($result[$lemma])) {
			$n = 1 ;
    			$result[$lemma] = $n;
		}
		else {$result[$lemma]++;}
	}
	arsort($result);
	#var_dump($result);
	
	$values = array();
	$headers = "['Lemma','Frequency','RelFreq in %']";
	array_push($values, $headers);
	$total = count($csv);
	foreach ($result as $key => $value) {	
		$val2 = ($value / $total) * 100;
		$pair = "['" . $key ."'," . $value .", " . $val2 . "]";
		array_push($values, $pair);
	}
			  
	#var_dump($values);		
        return $values;
    }


/**
     * reads csv file
     * returns: [semClass, frequency, RelFreq] for all 'corpus'
     */

    public function getAllSemVerbsFiles($csvFile)
    {

	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	$header2 = array();
		
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	
	foreach ($csv as $c) { 
		$sem = $c['Annotation1-1'];
		##initialize the result for this Sem because it doesnt exist yet
  		if (!isset($result[$sem])) {
			$n = 1 ;
    			$result[$sem] = $n;
		}
		else {$result[$sem]++;}
	}
	arsort($result);
	#var_dump($result);
	
	$values = array();
	$headers = "['SemClass','Frequency','RelFreq in %']";
	array_push($values, $headers);
	$total = count($csv);
	foreach ($result as $key => $value) {	
		$val2 = ($value / $total) * 100 ;
		$pair = "['" . $key ."'," . $value .", " . $val2 . "]";
		array_push($values, $pair);
	}
			  
	#var_dump($values);		
        return $values;
    }


/**
     * reads csv file
     * returns: array with: [semClass,frequency] for all 'corpus'
     */
    public function listSem($csvFile)
    {

	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	## lemma, semantic  -> file, frequencyVerbsLemma, distinctVerbsLemma

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
	$result = array();
	
	foreach ($csv as $c) { 
		$sem = $c['Annotation1-1']; 
		##initialize the result for this Sem because it doesnt exist yet
  		if (!isset($result[$sem])) {
			$n = 1 ;
    			$result[$sem] = $n;
		}
		else {$result[$sem]++;}
	}
	#var_dump($result);	
        return $result;
    }



/**
     * input: SemClass
     * returns: returns array with [lemma, Frequency] for SemClass
     */
    public function pieSemVerbs($csvFile,$sem)
    {
	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	$total = 0;
	foreach ($csv as $c) { 
		if ($c['Annotation1-1'] == $sem ) {
			$lemma = $c['Annotation2-1'];
			##initialize the result for this Lemma because it doesnt exist yet
  			if (!isset($result[$lemma])) {
			$n = 1 ;
    			$result[$lemma] = $n;
			}
			else {$result[$lemma]++;}
			$total++;
		}
	}
	arsort($result);
	#var_dump($result);
	
	$values = array();
	$headers = "['Sem','Verbs']";
	array_push($values, $headers);
	
	foreach ($result as $key => $value) {	
		
		$pair = "['" . $key ."'," . $value . "]";
		array_push($values, $pair);
	}
			  
	#var_dump($values);		
	# ['lemma', 'Frequency']
	
        return array($values,$total);
    }


/**
     * input: SemClass
     * returns: aray with [time, frequency, lemma]  'used to scatter verbs in timeline'
     */
    public function scatterSemVerbs($csvFile,$sem)
    {

	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	$total = 1;
	foreach ($csv as $c) { 
		if ($c['Annotation1-1'] == $sem ) {
			$lemma = $c['Annotation2-1'];
			##initialize the result for this Lemma because it doesnt exist yet
  			if (!isset($result[$lemma])) {
			$result[$lemma] = array();
			$n = 1;
    			array_push($result[$lemma],$n);
    			array_push($result[$lemma],$c['BeginTime']);

			}
			else {$result[$lemma][0]++; array_push($result[$lemma],$c['BeginTime']);}
			$total++;
		}
	}
	#var_dump($result); # result lemma -> [time,time,time...]
	
	$values = array();
	$times = array();
	foreach ($result as $key => $value) {
		$num = $value[0];
		array_shift($value);
		foreach ($value as $v){
			$pair = "[$v," . $num .",'" . $key . "']";
			array_push($values, $pair);
			array_push($times, $v);
		}
	}
	#get the maxValue BeginTime (for visualisation issues)
	
	$maxValue = max($times) + 50000;
	
	#var_dump($maxValue);		
	# values: [time,Freq,'lemma']
	
        return array($values,$total,$maxValue);
    }


/**
     * input: csvFile
     * returns: aray with [frequency, semclass]  'used to scatter verbs in timeline'
     */
    public function scatterSemVerbs2($csvFile)
    {
	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	## Motion,5698,cruzar,5698,VMIP3P0,5698,"What-Aptent-ES.eaf"

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	$total = 1;
	foreach ($csv as $c) { 
			$sem = $c['Annotation1-1'];
			##initialize the result for this sem because it doesnt exist yet
  			if (!isset($result[$sem])) {
			$result[$sem] = array();
			$n = 1;
    			$result[$sem] = $n;
			}
			else {$result[$sem]++;}
			$total++;
		
	}
	#var_dump($result); # result sem -> [time,time,time...]
	
	$values = array();
	array_push($values, "['SemClass', 'Frequency']");
	# ['Time', 'Frequency', 'SemClass'],
	foreach ($result as $key => $value) {	
		
		$pair = "['" . $key ."'," . $value . "]";
		array_push($values, $pair);
	}
	#get the maxValue BeginTime (for visualisation issues)
	
	reset($result);
	
        return array($values,$total);
    }



/**
     * input: provider
     * returns array with: [verb, frequency, Mean] for a specific provider
     */
    public function getAllVerbsFilesProvider($csvFile,$provider)
    {

	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = array();
	$class = array();
	$prov = array();
	
	foreach ($csv as $c) { 

		$lemma = $c['Annotation2-1'];
		##initialize the class for this Sem because it doesnt exist yet
	  	if (!isset($class[$lemma])) {
			$nn = 1 ;
	    		$class[$lemma] = $nn;
		}
		else {$class[$lemma]++;}

		$p = $c['TranscriptionName'];
		##initialize the p to count providers
	  	if (!isset($prov[$p])) {
			$nnn = 1 ;
	    		$prov[$p] = $nnn;
		}
		else {$prov[$p]++;}

		if ($c['TranscriptionName'] == $provider){ 
			##initialize the result for this Lemma because it doesnt exist yet
	  		if (!isset($result[$lemma])) {
				$n = 1 ;
	    			$result[$lemma] = $n;
			}
			else {$result[$lemma]++;}
		}
	}
	arsort($result);
	#var_dump($result);
	
	$values = array();
	$headers = "['Lemma','Frequency','Mean in corpus']";
	array_push($values, $headers);
	$total = count($prov);
	foreach ($result as $key => $value) {	
		if (array_key_exists($key, $class)) { $val2 = $class[$key] / $total; }
		$pair = "['" . $key ."'," . $value .", " . $val2 . "]";
		array_push($values, $pair);
	}
			  
	#var_dump($values);		
        return $values;
    }



/**
     * input: provider
     * returns array with: [semClass, frequency, Mean] for specific provider
     */
    public function getAllSemVerbsFilesProvider($csvFile,$provider)
    {

	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	#var_dump($csvFile,$provider);
	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	$header2 = array();
		
	$csv = array();
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv);
	$class = array();
	$result = array();
	$prov = array();

	foreach ($csv as $c) { 

		$sem = $c['Annotation1-1'];
		##initialize the class for this Sem because it doesnt exist yet
	  	if (!isset($class[$sem])) {
			$nn = 1 ;
	    		$class[$sem] = $nn;
		}
		else {$class[$sem]++;}

		$p = $c['TranscriptionName'];
		##initialize the p to count providers
	  	if (!isset($prov[$p])) {
			$nnn = 1 ;
	    		$prov[$p] = $nnn;
		}
		else {$prov[$p]++;}

		if ($c['TranscriptionName'] == $provider){
			
			##initialize the result for this Sem because it doesnt exist yet
	  		if (!isset($result[$sem])) {
				$n = 1 ;
	    			$result[$sem] = $n;
			}
			else {$result[$sem]++;}
		}
	}
	arsort($result);
	arsort($class);
	#var_dump($result);
	#var_dump($class);

	$values = array();
	$headers = "['SemClass','Frequency','Mean in corpus']";
	array_push($values, $headers);
	$total = count($prov);
	foreach ($result as $key => $value) {	
		
		if (array_key_exists($key, $class)) { $val2 = $class[$key] / $total; }
		$pair = "['" . $key ."'," . $value .", " . $val2 . "]";
		array_push($values, $pair);
	}
			  
	#var_dump($values);		
        return $values;
    }



}
