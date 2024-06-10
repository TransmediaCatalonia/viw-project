<?php

/* receives an csv file (exported from the ELAN tool), reads it and returns different arrays to be used by google API charts.
 *
 * 	Warning!!!!! check that your csv files have no BOM!!!! You can remove BOM using: 
 *   	awk '{if(NR==1)sub(/^\xef\xbb\xbf/,"");print}' text.csv  > new.csv
 */


namespace App\Utils;

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

/**
     * 
     * reads csv (sem.csv) and returns:
     * returns: array ['lemma', 'SemanticClass', 'Frequency' ] 
     *
     * input: 
     * "Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName" 
     * Location,3324,playa,3324,NCFS000,3324,"Aptent-ES.eaf" 
     */
    public function getLemmaSemFreq($csvFile,$pos,$provider)
    {
	$nWords = 0;
	$words = [];
	$result = [];
	## reads csv file into $data array (input format: "Annotation1-1","Annotation2-1","Annotation3-1","TranscriptionName")
	## Human,9746,hombre,9746,NCMS000,9746,"Aptent-ES.eaf"

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = [];
	foreach ($rows as $row) {
		if ($provider == "") {
  			$csv[] = array_combine($header, $row);
		}
		elseif ($provider == $row[6]) {
  			$csv[] = array_combine($header, $row);
		}
	}


	foreach ($csv as $data) {
	   if ( ( ($pos == 'N') && ( str_starts_with((string) $data['Annotation3-1'], (string) $pos) ) ) ||
	        ( ($pos == 'V') && ( str_starts_with((string) $data['Annotation3-1'], (string) $pos) ) && ( !str_starts_with((string) $data['Annotation1-1'], 'A') ) ) ||
		( ($pos == 'A') && ( ( str_starts_with((string) $data['Annotation3-1'], (string) $pos) ) || (str_starts_with((string) $data['Annotation3-1'], 'VMP')) || (str_starts_with((string) $data['Annotation3-1'], 'JJ')) )) ||
		( ($pos == 'R') && ( ( str_starts_with((string) $data['Annotation3-1'], (string) $pos)) || ( str_starts_with((string) $data['Annotation3-1'], 'R')) ) ) 
		) {
		$word = $data['Annotation2-1'];
		$sem = $data['Annotation1-1'];
		$word_sem = $word . "@" . $sem; 
		//checks if word was already used to modify result
		if (in_array($word_sem, $words)){  
			// looks for word in $result
			foreach ($result as &$d){ 
				if ($d['lemma'] == $word_sem){ 	 
					$d['freq']++ ;
					
				}
			}
			unset($d);			
		}
		//checks word, if new -> initialise & adds it in $result
		else { 
			array_push($words,$word_sem);
			$d = ['lemma' => $word_sem, 'freq' => 1];
			array_push($result,$d); 
		} 
	   } 
        }
	#var_dump($result);
	# arrange results 
	$values = [];
	$headers = "['Lemma','SemClass','Frequency']";
	array_push($values, $headers);
	foreach ( $result as $key ){ 
		$items = explode("@",$key['lemma']);
		$clean_lemma = str_replace("'","",$items[0]);
		$val = "['". $clean_lemma . "','" . $items[1] . "'," . $key['freq'] . "]";
		array_push($values, $val);
	}
	#var_dump($values);
        return [$values, $result];
    }

/**
     * 
     * reads csv (sem.csv) and returns:
     * returns: array ['lemma', 'PoS', 'Frequency' ] 
     * 
     * input: 
     * "Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName" 
     * Location,3324,playa,3324,NCFS000,3324,"Aptent-ES.eaf" 
     */
    public function getLemmaPoSFreq($csvFile,$provider)
    {
	$nWords = 0;
	$words = [];
	$result = [];
	## reads ﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = [];
	foreach ($rows as $row) { 
		if ($provider == "") {
  			$csv[] = array_combine($header, $row);
		}
		elseif ($provider == $row[6]) {
  			$csv[] = array_combine($header, $row);
		}
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
			unset($d);			
		}
		//checks word, if new -> initialise & adds it in $result
		else {
			array_push($words,$word);
			$d = ['lemma' => $word, 'pos' => $data['Annotation3-1'][0], 'freq' => 1];
			array_push($result,$d);
		} 
	    
        }

	# arrange results 
	$values = []; 
	$headers = "['Lemma','PoS','Frequency']";
	array_push($values, $headers);
	foreach ( $result as $key ){ 
		if ($provider == "") {
			if ($key['freq'] > 2){
			$clean_lemma = str_replace("'","",$key['lemma']);
			$val = "['". $clean_lemma . "','" . $key['pos'] . "'," . $key['freq'] . "]";
			array_push($values, $val);
			}
		}
		else {
			$clean_lemma = str_replace("'","",$key['lemma']);
			$val = "['". $clean_lemma . "','" . $key['pos'] . "'," . $key['freq'] . "]";
			array_push($values, $val);
		}
	}
	#var_dump($values);
        return [$values, $result];
    }



 /**
     * reads sem.csv file and returns with verbs/nouns/adj/adv x provider"; 
     * returns: ['Provider','NumVerbs/Nouns/Adjs/Adv','UniqVerbs/Nouns/Adjs/Adv'] for all 'corpus' 
     */
    public function getVerbsFiles($csvFile)
    {
	##﻿csv: "Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}  
        #var_dump($csv[2]);
	$result_v = [];$result_n = [];$result_a = [];$result_r = [];
	
	foreach ($csv as $c) { 
	#if ($pos == "V"){
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) || str_starts_with((string) $c['Annotation3-1'], "VB")){
		#foreach ($c as $key => $value) {var_dump($key);var_dump($value);}
		$fileName = $c['TranscriptionName'];
		#$lemma = $c['Annotation1']; var_dump($lemma);
		$lemma = $c['Annotation2-1'];

		##initialize the result for this fileName because it doesnt exist yet
  		if (!isset($result_v[$fileName])) {
			$v = 1 ;
    			$result_v[$fileName] = [];
			$result_v[$fileName]['verbs'] = [];
		}
		++$v; 
                $result_v[$fileName]['lemma'] = $v;
		array_push($result_v[$fileName]['verbs'], $lemma);
	   }
	#}
	#if ($pos == "N"){
	   elseif (str_starts_with((string) $c['Annotation3-1'], "N")){
		#foreach ($c as $key => $value) {var_dump($key);var_dump($value);}
		$fileName = $c['TranscriptionName'];
		#$lemma = $c['Annotation1']; var_dump($lemma);
		$lemma = $c['Annotation2-1'];

		##initialize the result for this fileName because it doesnt exist yet
  		if (!isset($result_n[$fileName])) {
			$n = 1 ;
    			$result_n[$fileName] = [];
			$result_n[$fileName]['verbs'] = [];
		}
		++$n; 
                $result_n[$fileName]['lemma'] = $n;
		array_push($result_n[$fileName]['verbs'], $lemma);
	   }
	#}
	#if ($pos == "A"){
	   elseif (str_starts_with((string) $c['Annotation3-1'], "A") || str_starts_with((string) $c['Annotation3-1'], "VMP") || str_starts_with((string) $c['Annotation3-1'], "J")){
		#foreach ($c as $key => $value) {var_dump($key);var_dump($value);}
		$fileName = $c['TranscriptionName'];
		#$lemma = $c['Annotation1']; var_dump($lemma);
		$lemma = $c['Annotation2-1'];

		##initialize the result for this fileName because it doesnt exist yet
  		if (!isset($result_a[$fileName])) {
			$a = 1 ;
    			$result_a[$fileName] = [];
			$result_a[$fileName]['verbs'] = [];
		}
		++$a; 
                $result_a[$fileName]['lemma'] = $a;
		array_push($result_a[$fileName]['verbs'], $lemma);
	   }
	#}
	#if ($pos == "R"){
	   elseif (str_starts_with((string) $c['Annotation3-1'], "R")){
		#foreach ($c as $key => $value) {var_dump($key);var_dump($value);}
		$fileName = $c['TranscriptionName'];
		#$lemma = $c['Annotation1']; var_dump($lemma);
		$lemma = $c['Annotation2-1'];

		##initialize the result for this fileName because it doesnt exist yet
  		if (!isset($result_r[$fileName])) {
			$r = 1 ;
    			$result_r[$fileName] = [];
			$result_r[$fileName]['verbs'] = [];
		}
		++$r; 
                $result_r[$fileName]['lemma'] = $r;
		array_push($result_r[$fileName]['verbs'], $lemma);
	   }
	#}
	}
	#var_dump($result);
	$values_v = []; $values_n = []; $values_a = []; $values_r = [];
	$headers_v = "['Provider','NumVerbs','UniqVerbs']";
	$headers_n = "['Provider','NumNouns','UniqNouns']";
	$headers_a = "['Provider','NumAdjs','UniqAdjs']";
	$headers_r = "['Provider','NumAdvs','UniqAdvs']";
	array_push($values_v, $headers_v);
	array_push($values_n, $headers_n);
	array_push($values_a, $headers_a);
	array_push($values_r, $headers_r);
	foreach ($result_v as $key => $value) {
		$val1 = ""; $val2 = "";
		foreach ($value as $k => $v) {	
			#	$pair = "['" . $key ."'," . $v ."]";
			#	array_push($values, $pair);
			if ($k == 'lemma'){ $val1 = $v;}
			if ($k == 'verbs'){ $unique = array_count_values($v); $val2 = count($unique); }	
		}
		$pair = "['" . $key ."'," . $val1 .", " . $val2 . "]";
		array_push($values_v, $pair);
	}
	foreach ($result_n as $key => $value) {
		$val1 = ""; $val2 = "";
		foreach ($value as $k => $v) {	
			#	$pair = "['" . $key ."'," . $v ."]";
			#	array_push($values, $pair);
			if ($k == 'lemma'){ $val1 = $v;}
			if ($k == 'verbs'){ $unique = array_count_values($v); $val2 = count($unique); }	
		}
		$pair = "['" . $key ."'," . $val1 .", " . $val2 . "]";
		array_push($values_n, $pair);
	}
	foreach ($result_a as $key => $value) {
		$val1 = ""; $val2 = "";
		foreach ($value as $k => $v) {	
			#	$pair = "['" . $key ."'," . $v ."]";
			#	array_push($values, $pair);
			if ($k == 'lemma'){ $val1 = $v;}
			if ($k == 'verbs'){ $unique = array_count_values($v); $val2 = count($unique); }	
		}
		$pair = "['" . $key ."'," . $val1 .", " . $val2 . "]";
		array_push($values_a, $pair);
	}
	foreach ($result_r as $key => $value) {
		$val1 = ""; $val2 = "";
		foreach ($value as $k => $v) {	
			#	$pair = "['" . $key ."'," . $v ."]";
			#	array_push($values, $pair);
			if ($k == 'lemma'){ $val1 = $v;}
			if ($k == 'verbs'){ $unique = array_count_values($v); $val2 = count($unique); }	
		}
		$pair = "['" . $key ."'," . $val1 .", " . $val2 . "]";
		array_push($values_r, $pair);
	}		
	#var_dump($values);	  
        return [$values_v, $values_n, $values_a, $values_r, $csv];
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
	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = [];
	
	foreach ($csv as $c) { 
	   if (str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")){
		$lemma = $c['Annotation2-1'];
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
	
	$values = [];
	$headers = "['Lemma','Frequency','RelFreq in %']";
	array_push($values, $headers);
	$total = count($csv);
	foreach ($result as $key => $value) {	
		$val2 = ($value / $total) * 100;
		$clean_lemma = str_replace("'","",$key);
		$pair = "['" . $clean_lemma ."'," . $value .", " . $val2 . "]";
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
	$header2 = [];
		
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = [];
	
	foreach ($csv as $c) { 
	   if (str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")){
		$sem = $c['Annotation1-1'];
		##initialize the result for this Sem because it doesnt exist yet
  		if (!isset($result[$sem])) {
			$n = 1 ;
    			$result[$sem] = $n;
		}
		else {$result[$sem]++;}
	   }
	}
	arsort($result);
	#var_dump($result);
	
	$values = [];
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
	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
	$result = [];
	
	foreach ($csv as $c) { 
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) || str_starts_with((string) $c['Annotation3-1'], "VB")){
		$sem = $c['Annotation1-1']; 
		##initialize the result for this Sem because it doesnt exist yet
  		if (!isset($result[$sem])) {
			$n = 1 ;
    			$result[$sem] = $n;
		}
		else {$result[$sem]++;}
	   }
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
	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = [];
	$total = 0;
	foreach ($csv as $c) { 
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) || str_starts_with((string) $c['Annotation3-1'], "VB")){
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
	}
	arsort($result);
	#var_dump($result);
	
	$values = [];
	$headers = "['Sem','Verbs']";
	array_push($values, $headers);
	
	foreach ($result as $key => $value) {	
		$clean_lemma = str_replace("'","",$key);
		$pair = "['" . $clean_lemma ."'," . $value . "]";
		array_push($values, $pair);
	}
			  
	#var_dump($values);		
	# ['lemma', 'Frequency']
	
        return [$values, $total];
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
	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = [];
	$total = 1;
	foreach ($csv as $c) { 
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) || str_starts_with((string) $c['Annotation3-1'], "VB")){		
		if ($c['Annotation1-1'] == $sem ) {
			$lemma = $c['Annotation2-1'];
			$time = $c['BeginTime'] / 60000;
			##initialize the result for this Lemma because it doesnt exist yet
  			if (!isset($result[$lemma])) {
			$result[$lemma] = [];
			$n = 1;
    			array_push($result[$lemma],$n);
    			array_push($result[$lemma],$time);

			}
			else {$result[$lemma][0]++; array_push($result[$lemma],$time);}
			$total++;
		}
	   }
	}
	#var_dump($result); # result lemma -> [time,time,time...]
	
	$values = [];
	$times = [];
	foreach ($result as $key => $value) {
		$num = $value[0];
		array_shift($value);
		foreach ($value as $v){
			$clean_lemma = str_replace("'","",$key);
			$pair = "[$v," . $num .",'" . $clean_lemma . "']";
			array_push($values, $pair);
			array_push($times, $v);
		}
	}
	#get the maxValue BeginTime (for visualisation issues)
	
	#$maxValue = max($times) + 50000; #when using miliseconds
	$maxValue = max($times) + 1 ;     #when using minutes
	
	#var_dump($maxValue);		
	# values: [time,Freq,'lemma']
	
        return [$values, $total, $maxValue];
    }


/**
     * input: csvFile
     * returns: aray with [frequency, semclass]  'used to pie semclass for verbs'
     */
    public function scatterSemVerbs2($csvFile)
    {
	##﻿"Annotation1-1","BeginTime","Annotation2-1","BeginTime","Annotation3-1","BeginTime","TranscriptionName"
	## Motion,5698,cruzar,5698,VMIP3P0,5698,"What-Aptent-ES.eaf"

	$rows = array_map('str_getcsv', file($csvFile));
	$header = array_shift($rows);
	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = [];
	$total = 1;
	foreach ($csv as $c) { 
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) || str_starts_with((string) $c['Annotation3-1'], "VB")){
			$sem = $c['Annotation1-1'];
			##initialize the result for this sem because it doesnt exist yet
  			if (!isset($result[$sem])) {
			$result[$sem] = [];
			$n = 1;
    			$result[$sem] = $n;
			}
			else {$result[$sem]++;}
			$total++;
	   }
		
	}
	#var_dump($result); # result sem -> [time,time,time...]
	
	$values = [];
	array_push($values, "['SemClass', 'Frequency']");
	# ['Time', 'Frequency', 'SemClass'],
	foreach ($result as $key => $value) {	
		
		$pair = "['" . $key ."'," . $value . "]";
		array_push($values, $pair);
	}
	
	reset($result);
	
        return [$values, $total];
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
	
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv[2]);
	$result = [];
	$class = [];
	$prov = [];
	
	foreach ($csv as $c) { 
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) || str_starts_with((string) $c['Annotation3-1'], "VB")){
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
	}
	arsort($result);
	#var_dump($result);
	
	$values = [];
	$headers = "['Lemma','Frequency','Mean in corpus']";
	array_push($values, $headers);
	$total = count($prov);
	foreach ($result as $key => $value) {	
		if (array_key_exists($key, $class)) { $val2 = $class[$key] / $total; }
		$clean_lemma = str_replace("'","",$key);
		$pair = "['" . $clean_lemma ."'," . $value .", " . $val2 . "]";
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
	$header2 = [];
		
	$csv = [];
	foreach ($rows as $row) {
  		$csv[] = array_combine($header, $row);
	}
        
        #var_dump($csv);
	$class = [];
	$result = [];
	$prov = [];

	foreach ($csv as $c) { 
	   if ((str_starts_with((string) $c['Annotation3-1'], "VM") && !str_starts_with((string) $c['Annotation3-1'], "VMP")) ||str_starts_with((string) $c['Annotation3-1'], "VB") ){
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
	}
	arsort($result);
	arsort($class);
	#var_dump($result);
	#var_dump($class);

	$values = [];
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



/**
     * input: csv for filmic annotations
     * returns array with: returns ['time',null,null,1,'text']
     */
    public function getFilmic($corpusFile)
    {
	$corpusCsvFile = file($corpusFile);
	$rows = [];
	$lastTime = [];
	$i = 1;
	foreach ($corpusCsvFile as $line) {
            $d = str_getcsv($line, "\t"); 
	    if (count($d) > 2 & $i > 1){ 
		if ($d[0] == "Scene") {
			$t = $d[1] / 60000;
			$text = "'" . $d[4] . "'" ;
		    	$row = '[' . $t . ', null, null, 1,'. $text .']';
		    	array_push($rows,$row);
		}
		array_push($lastTime,$t); #to get last time
	    }
	$i++;
        }    
	return [$rows, $lastTime];
    }


}
