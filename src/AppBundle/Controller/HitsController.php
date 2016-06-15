<?php
// src/AppBundle/Controller/HitsController.php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

/**
	Index:

  vocabularyVerbs            ANY      ANY      ANY    /vocabulary/verbs/{dir}                           
  vocabularyVerbsProvider    ANY      ANY      ANY    /vocabulary/verbs/{dir}/{provider}                
  vocabularyVerbsSemantic    ANY      ANY      ANY    /vocabulary/verbssem/{dir}/{sem}                  
  vocabularyVerbsDash        ANY      ANY      ANY    /vocabulary/verbsdash/{dir}                       
  vocabularyNounsDash        ANY      ANY      ANY    /vocabulary/nounsdash/{dir}                       
  vocabularyAdjsDash         ANY      ANY      ANY    /vocabulary/adjsdash/{dir}                        
  vocabularyPosDash          ANY      ANY      ANY    /vocabulary/posdash/{dir}       

**/

class HitsController extends Controller
{
    /**
     * @Route("/hits/time/{subdir_id}/{dir_id}/{file_id}")
     */
	### reads 'file-Hits' file and displays utterances in timeline (counting time)
    public function hitsTime($file_id,$dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" . $dir_id ."/";
        $file = $dataDir .  $file_id;
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$duration = array();
	$lastTime = array();
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
		$time = $data[1]/60000;
		$d = $data[3]/1000;
		array_push($duration,$d);
            	$row = "";
		$text = "'Duration: " . $d . "'" ;
            	$row = '[' . $time . ',' . $d . ',' . $text .']';
	    	array_push($rows,$row);
		array_push($lastTime,$time); #to get last time
	    }
	$i++;
        }
	$maxValue = end($lastTime) + 1;
	$data = implode(",",$rows);
	////// stats
		$count = count($duration);
                $sum = array_sum($duration);
                $mean = $sum / $count; 

		rsort($duration);
                $middle = round(count($duration) / 2);
                $median = $duration[$middle-1]; 

		$min = min($duration);
		$max = max($duration);
		
		foreach($duration as $key => $num){ $devs[$key] = pow($num - $mean, 2);}
		$std = sqrt(array_sum($devs) / (count($devs) - 1));

		$var = 0.0;
		foreach ($duration as $i) {
			$var += pow($i - $mean, 2);}
		$var /= ( false ? count($duration) - 1 : count($duration) );
	///////
		
    
        $html = $this->container->get('templating')->render(
            'hits/Hits.html.twig',
            array('key' => $data, 'title' => $file_id, 'type' => 'duration in seconds.',
                  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var,
		  'maxValue' => $maxValue)
        );

        return new Response($html);

    }

   /**
     * @Route("/hits/words/{subdir_id}/{dir_id}/{file_id}")
     */
	### reads file-Hits file and displays utterances in timeline (counting words)
    public function hitsWords($file_id,$dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" . $dir_id ."/";
        $file = $dataDir .  $file_id;
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$words = array();
	$lastTime = array();
	#array_push($rows,'["time line", "words"]');
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
            	$row = "";
	    	$d = $data[1] / 60000;
		$sentence = explode(" ", $data[4]);
		$c = count($sentence);
		array_push($words,$c);
		$text = "'Num. words: " . $c . "'" ;
            	$row = '[' . $d . ',' . $c . ',' . $text . ']';
	    	array_push($rows,$row);
		array_push($lastTime,$d); #to get last time
	    }
	$i++;
        }
	$maxValue = end($lastTime) + 1;
	    
	$data = implode(",",$rows);
	////// stats
		$count = count($words);
                $sum = array_sum($words);
                $mean = $sum / $count; 

		rsort($words);
                $middle = round(count($words) / 2);
                $median = $words[$middle-1]; 

		$min = min($words);
		$max = max($words);
		
		foreach($words as $key => $num){ $devs[$key] = pow($num - $mean, 2);}
		$std = sqrt(array_sum($devs) / (count($devs) - 1));

		$var = 0.0;
		foreach ($words as $i) {
			$var += pow($i - $mean, 2);}
		$var /= ( false ? count($words) - 1 : count($words) );
	///////
    
        $html = $this->container->get('templating')->render(
            'hits/Hits.html.twig',
            array('key' => $data, 'title' => $file_id, 'type' => 'num. of words',
			'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var,
			'maxValue' => $maxValue)
        );

        return new Response($html);

    }

/**
     * @Route("/hits/wordsvisual/{subdir_id}/{dir_id}/{file_id}")
     */
	### reads file-Hits file and displays utterances in timeline (counting words) plus Filmic info (shoots)
    public function hitsWordsVisual($file_id,$dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" . $dir_id ."/";
        $file = $dataDir .  $file_id;
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$words = array();
	#array_push($rows,'["time line", "words"]');
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
            	$row = "";
		$d = $data[1] / 60000 ;
	    	//$text = "'" . $data[0] . "'" ;
		$sentence = explode(" ", $data[4]);
		$c = count($sentence);
		array_push($words,$c);
		$text = "'Words: " . $c . "'" ;
            	$row = '[' . $d . ',' . $c . ',' . $text . ',' .'null'. ',' .'null]';
	    	array_push($rows,$row);
	    }
	$i++;
        }    
	#$data = implode(",",$rows);
##
	$corpusFile = $path . "/../data/" . $subdir_id . "/" . $subdir_id ."-Hits.txt";
	$corpusCsvFile = file($corpusFile);
	$rowsCorpus = array();
	$lastTime = array();
	$i = 1;
	foreach ($corpusCsvFile as $line) {
            $d = str_getcsv($line, "\t"); 
	    if (count($d) > 2 & $i > 1){
		if (eregi("shoot", $d[3])) {
			$t = $d[0] / 60000;
			$text = "'" . $d[3] . "'" ;
		    	$row = '[' . $t . ', null, null, 1,'. $text .']';
		    	array_push($rows,$row);
		}
		array_push($lastTime,$t); #to get last time
	    }
	$i++;
        }    #var_dump($rowsCorpus);
	$data = implode(",",$rows);
	$maxValue = end($lastTime) + 1;
##
	////// stats
		$count = count($words);
                $sum = array_sum($words);
                $mean = $sum / $count; 

		rsort($words);
                $middle = round(count($words) / 2);
                $median = $words[$middle-1]; 

		$min = min($words);
		$max = max($words);
		
		foreach($words as $key => $num){ $devs[$key] = pow($num - $mean, 2);}
		$std = sqrt(array_sum($devs) / (count($devs) - 1));

		$var = 0.0;
		foreach ($words as $i) {
			$var += pow($i - $mean, 2);}
		$var /= ( false ? count($words) - 1 : count($words) );
	///////
    
        $html = $this->container->get('templating')->render(
            'hits/hitsVisual.html.twig',
            array('key' => $data, 'title' => $file_id, 'type' => 'num. of words',
		  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 
                  'std' => $std, 'var' => $var, 'maxValue' => $maxValue)
        );

        return new Response($html);

    }




/**
     * @Route("/hits/timewords/{subdir_id}/{dir_id}/{file_id}")
     */
     ### reads file-Hits file and displays utterances in timeline (counting time/words)
    public function timewords($file_id,$dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" . $dir_id ."/";
        $file = $dataDir .  $file_id;
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$duration = array();
	$lastTime = array();
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
		#$sentence = explode(" ", $data[4]);
		$vowels = array(" ", ",", ".", ";", "!", ":", "-");
		$sentence = str_replace($vowels, "", $data[4]);

		$c = strlen(utf8_decode($sentence));
		$t = $data[1] / 60000;

		#$x = $data[3] / str_word_count($data[4]);
		$time = $data[3] / 1000;
		$x = $c / $time ; 
		array_push($duration,$x);
            	$row = "";
		$text = "'characters/second: " . $x . "'" ;
            	$row = '[' . $t . ',' . $x . ',' . $text .']';
	    	array_push($rows,$row);
		array_push($lastTime,$t); #to get last time
	    }
	$i++;
        }
	$maxValue = end($lastTime) + 1;
	$data = implode(",",$rows);
	////// stats
		$count = count($duration);
                $sum = array_sum($duration);
                $mean = $sum / $count; 

		rsort($duration);
                $middle = round(count($duration) / 2);
                $median = $duration[$middle-1]; 

		$min = min($duration);
		$max = max($duration);
		
		foreach($duration as $key => $num){ $devs[$key] = pow($num - $mean, 2);}
		$std = sqrt(array_sum($devs) / (count($devs) - 1));

		$var = 0.0;
		foreach ($duration as $i) {
			$var += pow($i - $mean, 2);}
		$var /= ( false ? count($duration) - 1 : count($duration) );
	///////
		
    
        $html = $this->container->get('templating')->render(
            'hits/Hits.html.twig',
            array('key' => $data, 'title' => $file_id, 'type' => 'character/second',
                  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var,
		  'maxValue' => $maxValue)
        );

        return new Response($html);

    }
   
/**
     * @Route("/words/{corpus}/{subdir_id}/{file_id}", requirements={"corpus": "corpus"}  )
     *
     * we set corpus to 'corpus' (silly thing to avoid routing errors with next function)
     */
     ## reads countWords.txt file (for corpus) and displays some figures
    public function wordsCorpus($subdir_id,$file_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  $file_id;
	
	$lines = file($file);
        #$data = [];
        
	$rows = array();
	$pWords = array();
	$sValues = array();
	$rows2 = array();
        $sWords = array();
	$all = array();


	foreach ($lines as $line) {
            $data = explode("\t",$line);
					    
	    if ($data[0] == "SentencesXpar:") {
            	$sents = chop($data[1]) ;
		$sents = trim($sents, '[');
		$sents = trim($sents, ']');
		$sValues = explode(",", $sents);	
	    }
	    elseif ($data[0] == "WordsXpar:") {
            	$words = chop($data[1]) ;
		$words = trim($words, '[');
		$words = trim($words, ']');
		$pWords = explode(",", $words);	
 	    }
	    elseif ($data[0] == "WordsXsentence:") {
            	$words = chop($data[1]) ;
		$words = trim($words, '[');
		$words = trim($words, ']');
		$sWords = explode(",", $words);	
 	    }
	    else {
		$k = trim($data[0],':'); 
		$v = chop($data[1]);
		$all[$k] = $v; 
		}
	}

	$i = 0; 
	foreach ($sValues as $s) {
		$w = $pWords[$i];
            	$row = '[' . $i . ',' . $s . ',' . $w . ']';
	    	array_push($rows,$row);
		$i++;
        }    
	$data = implode(",",$rows);
        $j = 0;
	foreach ($sWords as $s) {
           	$row = '[' . $j . ',' . $s . ']';
	    	array_push($rows2,$row);
		$j++;
        }    
	$data2 = implode(",",$rows2); 

        $html = $this->container->get('templating')->render(
            'hits/words.html.twig',
            array('key' => $data, 'all' => $all, 'key2' => $data2, 'title' => $subdir_id, 'type' => 'num. of words')
        );

        return new Response($html);

    }


/**
     * @Route("/words/{subdir_id}/{dir_id}/{file_id}")
     */
     ## reads countWords.txt file (for files) and displays some figures
    public function words($file_id,$dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" . $dir_id ."/";
        $file = $dataDir .  $file_id;
	
	$lines = file($file);
        #$data = [];
        
	$rows = array();
	$pWords = array();
	$sValues = array();
	$rows2 = array();
        $sWords = array();
	$all = array();


	foreach ($lines as $line) {
            $data = explode("\t",$line);
					    
	    if ($data[0] == "SentencesXpar:") {
            	$sents = chop($data[1]) ;
		$sents = trim($sents, '[');
		$sents = trim($sents, ']');
		$sValues = explode(",", $sents);	
	    }
	    elseif ($data[0] == "WordsXpar:") {
            	$words = chop($data[1]) ;
		$words = trim($words, '[');
		$words = trim($words, ']');
		$pWords = explode(",", $words);	
 	    }
	    elseif ($data[0] == "WordsXsentence:") {
            	$words = chop($data[1]) ;
		$words = trim($words, '[');
		$words = trim($words, ']');
		$sWords = explode(",", $words);	
 	    }
	    else {
		$k = trim($data[0],':'); 
		$v = chop($data[1]);
		$all[$k] = $v; 
		}
	}

	$i = 0; 
	foreach ($sValues as $s) {
		$w = $pWords[$i];
            	$row = '[' . $i . ',' . $s . ',' . $w . ']';
	    	array_push($rows,$row);
		$i++;
        }    
	$data = implode(",",$rows);
        $j = 0;
	foreach ($sWords as $s) {
           	$row = '[' . $j . ',' . $s . ']';
	    	array_push($rows2,$row);
		$j++;
        }    
	$data2 = implode(",",$rows2); 

        $html = $this->container->get('templating')->render(
            'hits/words.html.twig',
            array('key' => $data, 'all' => $all, 'key2' => $data2, 'title' => $dir_id, 'type' => 'num. of words')
        );

        return new Response($html);

    }

/**
     * @Route("/hits/timeline/{corpus_id}")
     */
     ## reads Hits-All.txt file (for files) and displays data in timeline,
     ## Input: What-Tragora-ES	417000	420000	Sobreimpreso en pantalla: Jess, la estudiante.
     ## Output: ["What-Tragora-ES","",533000,542000],
    public function timeline($corpus_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $corpus_id ."/";
        $file = $dataDir .  "Hits-All.txt";
	
	$lines = file($file);
                
	$rows = array();
	$data = array();

	foreach ($lines as $line) {
            $data = explode("\t",$line);
	    $text = htmlentities(mb_substr(rtrim($data[3]),0,60)); 
	    $row = '["' . $data[0] . '", "", "' . $text . '",' . $data[1] . ',' . $data[2] . ']';
	    array_push($rows,$row);
	}

	$data = implode(",",$rows);
        
        $html = $this->container->get('templating')->render(
            'hits/timeline.html.twig',
            array('key' => $data, 'corpus' => $corpus_id)
        );

        return new Response($html);

    }

}
