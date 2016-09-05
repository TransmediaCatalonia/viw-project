<?php
// src/AppBundle/Controller/HitsController.php
namespace AppBundle\Controller;

use AppBundle\Utils\CSV;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

/**
	Index:

  app_hits_hitstime             ANY      ANY      ANY    /hits/time/{subdir_id}/{dir_id}
  app_hits_hitstimevisual       ANY      ANY      ANY    /hits/timevisual/{subdir_id}/{dir_id}           
  app_hits_hitswords            ANY      ANY      ANY    /hits/words/{subdir_id}/{dir_id}        
  app_hits_hitswordsvisual      ANY      ANY      ANY    /hits/wordsvisual/{subdir_id}/{dir_id} 
  app_hits_timewords            ANY      ANY      ANY    /hits/timewords/{subdir_id}/{dir_id}
  app_hits_timewordsvisual      ANY      ANY      ANY    /hits/timewordsvisual/{subdir_id}/{dir_id}
  app_hits_wordscorpus          ANY      ANY      ANY    /words/{corpus}/{subdir_id}/{file_id}             
  app_hits_words                ANY      ANY      ANY    /words/{subdir_id}/{dir_id}/{file_id}             
  app_hits_timeline             ANY      ANY      ANY    /hits/timeline/{corpus_id}      
  app_hits_timelinejs           ANY      ANY      ANY    /hits/timelinejs/{corpus_id}      
**/

class HitsController extends Controller
{
    /**
     * @Route("/hits/time/{subdir_id}/{dir_id}")
     */
	### reads 'Hits-All.txt' file and displays utterances in timeline (counting duration)
    public function hitsTime($dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  "Hits-All.txt";
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$duration = array();
	$lastTime = array();
        $i = 1;
	
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){      
	      $paths = explode("/",$data[4]); 
	      $file = substr($paths[2], 0, -4); 
	      if ($dir_id == $file){ 
		$time = $data[0]/60000; 	#beguin time (in seconds)
		$d = $data[2]/1000;		#duration
		array_push($duration,$d);
            	$row = "";
		$text = "'Duration: " . $d . "'" ;
            	$row = '[' . $time . ',' . $d . ',' . $text .']';
	    	array_push($rows,$row);
		array_push($lastTime,$time); #to get last time
	      }
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
            array('key' => $data, 'title' => $dir_id, 'type' => 'duration in seconds',
                  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var,
		  'maxValue' => $maxValue, 'path' => $subdir_id)
        );

        return new Response($html);

    }

 /**
     * @Route("/hits/timevisual/{subdir_id}/{dir_id}")
     */
	### reads 'Hits-All.txt' file and displays utterances in timeline + filmic info (counting duration)
    public function hitsTimevisual($dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  "Hits-All.txt";
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$duration = array();
	$lastTime = array();
        $i = 1;
	
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
	      $paths = explode("/",$data[4]); 
	      $file = substr($paths[2], 0, -4);  
	      if ($dir_id == $file){
		$time = $data[0]/60000; 	#beguin time (in seconds)
		$d = $data[2]/1000;		#duration
		array_push($duration,$d);
            	$row = "";
		$text = "'Duration: " . $d . "'" ;
            	$row = '[' . $time . ',' . $d . ',' . $text .',' .'null'. ',' .'null]';
	    	array_push($rows,$row);
		array_push($lastTime,$time); #to get last time
	      }
	    }
	$i++;
        }

        # Adds filmic info: returns ['time',null,null,1,'text']
	$corpusFile = $path . "/../data/" . $subdir_id . "/Filmic-Hits.txt";

        list($filmic_rows,$filmic_lastTime) = $this->get('app.utils.csv')->getFilmic($corpusFile);
	$mergeR = array_merge($rows, $filmic_rows);
	$mergeT = array_merge($rows, $filmic_lastTime);

	$data = implode(",",$mergeR);
	$maxValue = end($mergeT) + 1;
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
            'hits/hitsVisual.html.twig',
            array('key' => $data, 'title' => $dir_id, 'type' => 'Duration in seconds',
		  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 
                  'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id)
        );
        return new Response($html);

    }



   /**
     * @Route("/hits/words/{subdir_id}/{dir_id}")
     */
	### reads Hits-All.txt file and displays utterances in timeline (counting words)
    public function hitsWords($dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  "Hits-All.txt";
	
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
	      $paths = explode("/",$data[4]); 
	      $file = substr($paths[2], 0, -4);  
	      if ($dir_id == $file){
            	$row = "";
	    	$d = $data[0] / 60000;
		$sentence = explode(" ", $data[3]);
		$c = count($sentence);
		array_push($words,$c);
		$text = "'Num. words: " . $c . "'" ;
            	$row = '[' . $d . ',' . $c . ',' . $text . ']';
	    	array_push($rows,$row);
		array_push($lastTime,$d); #to get last time
	      }
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
            array('key' => $data, 'title' => $dir_id, 'type' => 'Num. of words',
			'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var,
			'maxValue' => $maxValue, 'path' => $subdir_id)
        );

        return new Response($html);

    }

/**
     * @Route("/hits/wordsvisual/{subdir_id}/{dir_id}")
     */
	### reads Hits-All.txt file and displays utterances in timeline (counting words) plus Filmic info (shoots)
    public function hitsWordsVisual($dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  "Hits-All.txt";
	
	$csvFile = file($file);
        
	$rows = array();
	$words = array();
	#array_push($rows,'["time line", "words"]');
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
	      $paths = explode("/",$data[4]); 
	      $file = substr($paths[2], 0, -4);  
	      if ($dir_id == $file){
            	$row = "";
		$d = $data[0] / 60000 ;
	    	//$text = "'" . $data[0] . "'" ;
		$sentence = explode(" ", $data[3]);
		$c = count($sentence);
		array_push($words,$c);
		$text = "'Words: " . $c . "'" ;
            	$row = '[' . $d . ',' . $c . ',' . $text . ',' .'null'. ',' .'null]';
	    	array_push($rows,$row);
	      }
	    }
	$i++;
        }    
	#$data = implode(",",$rows);

        # Adds filmic info: returns ['time',null,null,1,'text']
	$corpusFile = $path . "/../data/" . $subdir_id . "/Filmic-Hits.txt";

        list($filmic_rows,$filmic_lastTime) = $this->get('app.utils.csv')->getFilmic($corpusFile);
	$mergeR = array_merge($rows, $filmic_rows);
	$mergeT = array_merge($rows, $filmic_lastTime);

	$data = implode(",",$mergeR);
	$maxValue = end($mergeT) + 1;

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
            array('key' => $data, 'title' => $dir_id, 'type' => 'num. of words',
		  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 
                  'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id)
        );

        return new Response($html);

    }


/**
     * @Route("/hits/timewords/{subdir_id}/{dir_id}")
     */
     ### reads Hits-All.txt file and displays utterances in timeline (counting time/words)
    public function timewords($dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  "Hits-All.txt";
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$duration = array();
	$lastTime = array();
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
	      $paths = explode("/",$data[4]); 
	      $file = substr($paths[2], 0, -4);  
	      if ($dir_id == $file){
            	$row = "";
		#$sentence = explode(" ", $data[3]);
		$vowels = array(" ", ",", ".", ";", "!", ":", "-");
		$sentence = str_replace($vowels, "", $data[3]);

		$c = strlen(utf8_decode($sentence));
		$t = $data[0] / 60000;

		#$x = $data[2] / str_word_count($data[3]);
		$time = $data[2] / 1000;
		$x = $c / $time ; 
		array_push($duration,$x);
            	$row = "";
		$text = "'characters/second: " . $x . "'" ;
            	$row = '[' . $t . ',' . $x . ',' . $text .']';
	    	array_push($rows,$row);
		array_push($lastTime,$t); #to get last time
	      }
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
            array('key' => $data, 'title' => $dir_id, 'type' => 'characters/second',
                  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var,
		  'maxValue' => $maxValue, 'path' => $subdir_id)
        );

        return new Response($html);

    }
   
/**
     * @Route("/hits/timewordsvisual/{subdir_id}/{dir_id}")
     */
     ### reads Hits-All.txt file and displays utterances in timeline + visual info (counting time/words)
    public function timewordsvisual($dir_id,$subdir_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $subdir_id . "/" ;
        $file = $dataDir .  "Hits-All.txt";
	
	$csvFile = file($file);
        #$data = [];
        
	$rows = array();
	$duration = array();
	$lastTime = array();
        $i = 1;
	foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t"); 
	    if (count($data) > 2 & $i > 1){
	      $paths = explode("/",$data[4]); 
	      $file = substr($paths[2], 0, -4);  
	      if ($dir_id == $file){
            	$row = "";
		#$sentence = explode(" ", $data[3]);
		$vowels = array(" ", ",", ".", ";", "!", ":", "-");
		$sentence = str_replace($vowels, "", $data[3]);

		$c = strlen(utf8_decode($sentence));
		$t = $data[0] / 60000;

		#$x = $data[2] / str_word_count($data[3]);
		$time = $data[2] / 1000;
		$x = $c / $time ; 
		array_push($duration,$x);
            	$row = "";
		$text = "'characters/second: " . $x . "'" ;
            	$row = '[' . $t . ',' . $x . ',' . $text . ',' .'null'. ',' .'null]';
	    	array_push($rows,$row);
		array_push($lastTime,$t); #to get last time
	      }
	    }
	$i++;
        }
        # Adds filmic info: returns ['time',null,null,1,'text']
	$corpusFile = $path . "/../data/" . $subdir_id . "/Filmic-Hits.txt";

        list($filmic_rows,$filmic_lastTime) = $this->get('app.utils.csv')->getFilmic($corpusFile);
	$mergeR = array_merge($rows, $filmic_rows);
	$mergeT = array_merge($rows, $filmic_lastTime);

	$data = implode(",",$mergeR);
	$maxValue = end($mergeT) + 1;
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
            'hits/hitsVisual.html.twig',
            array('key' => $data, 'title' => $dir_id, 'type' => 'characters/second',
		  'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 
                  'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id)
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
	$back = "corpus/" . $subdir_id; 
        $html = $this->container->get('templating')->render(
            'hits/words.html.twig',
            array('key' => $data, 'all' => $all, 'key2' => $data2, 'title' => $subdir_id, 'type' => 'num. of words', 'back' => $back)
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
	$back = "metadata/". $subdir_id . "/" . $dir_id ;
        $html = $this->container->get('templating')->render(
            'hits/words.html.twig',
            array('key' => $data, 'all' => $all, 'key2' => $data2, 'title' => $dir_id, 'type' => 'num. of words', 'back' => $back)
        );

        return new Response($html);

    }

/**
     * @Route("/hits/timeline/{corpus_id}")
     */
     ## reads Hits-All.txt file (for files) and displays data in timeline,
     ## 
    public function timeline($corpus_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $corpus_id ."/";
        $file = $dataDir .  "Hits-All.txt";
	
	$lines = file($file);
                
	$rows = array();
	$data = array();
	# 176450	177010	560	S'atura.	AccessFriendly-CA.eaf
	foreach ($lines as $l) {
	    $line = trim($l); 
            $data = explode("\t",$line);
	 if (count($data) > 2) {
	    $text = htmlentities(mb_substr(rtrim($data[3]),0,60));
	    $paths = explode("/",$data[4]); 
	    $file = substr($paths[2], 0, -4); 
	    $row = '["' . $file . '", "", "' . $text . '",' . $data[0] . ',' . $data[1] . ']';
	    array_push($rows,$row);
	 }
	}


	$filmic_file = $dataDir .  "Filmic-Hits.txt";
	$filmic_lines = file($filmic_file);
	# Scene	67520	218920	151400	S2-Beach
	foreach ($filmic_lines as $l) {
	    $line = trim($l);
            $data = explode("\t",$line);
	 if (count($data) > 2 ){
		if (preg_match('/Speech/',$data[4])) {
			$row = '["Speech", "" , "speech",' . $data[1] . ',' . $data[2] . ']';
	    		array_push($rows,$row);
		}
	    $text = htmlentities(mb_substr(rtrim($data[4]),0,60)); 
	    $row = '["' . $data[0] . '", "' . $text . '", "' . $text . '",' . $data[1] . ',' . $data[2] . ']';
	    array_push($rows,$row);
	 }
	}


	$data = implode(",",$rows);
        
        $html = $this->container->get('templating')->render(
            'hits/timeline.html.twig',
            array('key' => $data, 'corpus' => $corpus_id)
        );

        return new Response($html);

    }

/**
     * @Route("/hits/timelinejs/{corpus_id}")
     */
     ## reads Hits-All.txt file (for files) and displays data in timeline,
     ## 
    public function timelinejs($corpus_id)
    {
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $corpus_id ."/";
        $file = $dataDir .  "Hits-All.txt";
	
	$lines = file($file);
                
	$rows = array();
	$data = array();
	$times = array();
	# 176450	177010	560	S'atura.	AccessFriendly-CA.eaf
	# [new Date(t.getTime()+759230), ,"Blackness.", "SDI-Media-UK.eaf"],
	foreach ($lines as $l) {
	    $line = trim($l);
            $data = explode("\t",$line);
	  if (count($data) > 2){
	    //$text = htmlentities(mb_substr(rtrim($data[3]),0,100));
	    $t = htmlentities($data[3]);
	    $text = wordwrap($t, 100, "<br>");
	    $paths = explode("/",$data[4]); 
	    $file = substr($paths[2], 0, -4);
	    $row = '[new Date(t.getTime()+' . $data[0] . '), , "' . $text . '","' . $file . '"]';
	    array_push($rows,$row);
	    array_push($times,$data[0]);
	  }
	}

	sort($times, SORT_NUMERIC); 
  	$start = $times[0];
	$data = implode(",",$rows);
        
        $html = $this->container->get('templating')->render(
            'default/timelineCorpus.html.twig',
            array('key' => $data, 'corpus' => $corpus_id, 'start' => $start)
        );

        return new Response($html);

    }

}
