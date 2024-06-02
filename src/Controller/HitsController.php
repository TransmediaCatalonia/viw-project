<?php
// src/App/Controller/HitsController.php
namespace App\Controller;

use App\Utils\CSV;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Index:
 *
 * app_hits_hitstime             ANY      ANY      ANY    /hits/time/{subdir_id}/{dir_id}
 * app_hits_hitstimevisual       ANY      ANY      ANY    /hits/timevisual/{subdir_id}/{dir_id}
 * app_hits_hitswords            ANY      ANY      ANY    /hits/words/{subdir_id}/{dir_id}
 * app_hits_hitswordsvisual      ANY      ANY      ANY    /hits/wordsvisual/{subdir_id}/{dir_id}
 * app_hits_timewords            ANY      ANY      ANY    /hits/timewords/{subdir_id}/{dir_id}
 * app_hits_timewordsvisual      ANY      ANY      ANY    /hits/timewordsvisual/{subdir_id}/{dir_id}
 * app_hits_wordscorpus          ANY      ANY      ANY    /words/{corpus}/{subdir_id}/{file_id}
 * app_hits_words                ANY      ANY      ANY    /words/{subdir_id}/{dir_id}/{file_id}
 * app_hits_timeline             ANY      ANY      ANY    /hits/timeline/{corpus_id}
 * app_hits_timelinejs           ANY      ANY      ANY    /hits/timelinejs/{corpus_id}
 **/
class HitsController extends AbstractController
{
    protected $csv;

    public function __construct(CSV $csv)
    {
        $this->csv = $csv;
    }

    ### reads 'Hits-All.txt' file and displays utterances in timeline (counting duration)
    #[Route(path: '/hits/time/{subdir_id}/{dir_id}')]
    public function hitsTime($dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $csvFile = file($file);
        #$data = [];

        $rows = [];
        $duration = [];
        $lastTime = [];
        $i = 1;

        foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t");
            if (count($data) > 2 & $i > 1) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($dir_id == $file) {
                    $time = $data[0] / 60000;    #beguin time (in seconds)
                    $d = $data[2] / 1000;        #duration
                    array_push($duration, $d);
                    $row = "";
                    $text = "'Duration: " . $d . "'";
                    $row = '[' . $time . ',' . $d . ',' . $text . ']';
                    array_push($rows, $row);
                    array_push($lastTime, $time); #to get last time
                }
            }
            $i++;
        }
        $maxValue = end($lastTime) + 1;
        $data = implode(",", $rows);

        ////// stats
        $count = count($duration);
        $sum = array_sum($duration);
        $mean = $sum / $count;

        rsort($duration);
        $middle = round(count($duration) / 2);
        $median = $duration[$middle - 1];

        $min = min($duration);
        $max = max($duration);

        foreach ($duration as $key => $num) {
            $devs[$key] = ($num - $mean) ** 2;
        }
        $std = sqrt(array_sum($devs) / (count($devs) - 1));

        $var = 0.0;
        foreach ($duration as $i) {
            $var += ($i - $mean) ** 2;
        }
        $var /= (false ? count($duration) - 1 : count($duration));
        ///////


        return $this->render(
            'hits/Hits.html.twig',
            ['key' => $data, 'title' => $dir_id, 'type' => 'duration in seconds', 'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id]
        );

    }

    ### reads 'Hits-All.txt' file and displays utterances in timeline + filmic info (counting duration)
    #[Route(path: '/hits/timevisual/{subdir_id}/{dir_id}')]
    public function hitsTimevisual($dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $csvFile = file($file);
        #$data = [];

        $rows = [];
        $duration = [];
        $lastTime = [];
        $i = 1;

        foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t");
            if (count($data) > 2 & $i > 1) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($dir_id == $file) {
                    $time = $data[0] / 60000;    #beguin time (in seconds)
                    $d = $data[2] / 1000;        #duration
                    array_push($duration, $d);
                    $row = "";
                    $text = "'Duration: " . $d . "'";
                    $row = '[' . $time . ',' . $d . ',' . $text . ',' . 'null' . ',' . 'null]';
                    array_push($rows, $row);
                    array_push($lastTime, $time); #to get last time
                }
            }
            $i++;
        }

        # Adds filmic info: returns ['time',null,null,1,'text']
        $corpusFile = $path . "/data/" . $subdir_id . "/Filmic-Hits.txt";

        [$filmic_rows, $filmic_lastTime] = $this->csv->getFilmic($corpusFile);
        $mergeR = array_merge($rows, $filmic_rows);
        $mergeT = array_merge($rows, $filmic_lastTime);

        $data = implode(",", $mergeR);
        $maxValue = end($mergeT) + 1;
        ////// stats
        $count = count($duration);
        $sum = array_sum($duration);
        $mean = $sum / $count;

        rsort($duration);
        $middle = round(count($duration) / 2);
        $median = $duration[$middle - 1];

        $min = min($duration);
        $max = max($duration);

        foreach ($duration as $key => $num) {
            $devs[$key] = ($num - $mean) ** 2;
        }
        $std = sqrt(array_sum($devs) / (count($devs) - 1));

        $var = 0.0;
        foreach ($duration as $i) {
            $var += ($i - $mean) ** 2;
        }
        $var /= (false ? count($duration) - 1 : count($duration));
        ///////

        return $this->render(
            'hits/hitsVisual.html.twig',
            ['key' => $data, 'title' => $dir_id, 'type' => 'Duration in seconds', 'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id]
        );
    }

    ### reads Hits-All.txt file and displays utterances in timeline (counting words)
    #[Route(path: '/hits/words/{subdir_id}/{dir_id}')]
    public function hitsWords($dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $csvFile = file($file);
        #$data = [];

        $rows = [];
        $words = [];
        $lastTime = [];
        #array_push($rows,'["time line", "words"]');
        $i = 1;
        foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t");
            if (count($data) > 2 & $i > 1) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($dir_id == $file) {
                    $row = "";
                    $d = $data[0] / 60000;
                    $sentence = explode(" ", (string)$data[3]);
                    $c = count($sentence);
                    array_push($words, $c);
                    $text = "'Num. words: " . $c . "'";
                    $row = '[' . $d . ',' . $c . ',' . $text . ']';
                    array_push($rows, $row);
                    array_push($lastTime, $d); #to get last time
                }
            }
            $i++;
        }
        $maxValue = end($lastTime) + 1;

        $data = implode(",", $rows);
        ////// stats
        $count = count($words);
        $sum = array_sum($words);
        $mean = $sum / $count;

        rsort($words);
        $middle = round(count($words) / 2);
        $median = $words[$middle - 1];

        $min = min($words);
        $max = max($words);

        foreach ($words as $key => $num) {
            $devs[$key] = ($num - $mean) ** 2;
        }
        $std = sqrt(array_sum($devs) / (count($devs) - 1));

        $var = 0.0;
        foreach ($words as $i) {
            $var += ($i - $mean) ** 2;
        }
        $var /= (false ? count($words) - 1 : count($words));
        ///////

        return $this->render(
            'hits/Hits.html.twig',
            ['key' => $data, 'title' => $dir_id, 'type' => 'Num. of words', 'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id]
        );
    }

    ### reads Hits-All.txt file and displays utterances in timeline (counting words) plus Filmic info (shoots)
    #[Route(path: '/hits/wordsvisual/{subdir_id}/{dir_id}')]
    public function hitsWordsVisual($dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $csvFile = file($file);

        $rows = [];
        $words = [];
        #array_push($rows,'["time line", "words"]');
        $i = 1;
        foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t");
            if (count($data) > 2 & $i > 1) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($dir_id == $file) {
                    $row = "";
                    $d = $data[0] / 60000;
                    //$text = "'" . $data[0] . "'" ;
                    $sentence = explode(" ", (string)$data[3]);
                    $c = count($sentence);
                    array_push($words, $c);
                    $text = "'Words: " . $c . "'";
                    $row = '[' . $d . ',' . $c . ',' . $text . ',' . 'null' . ',' . 'null]';
                    array_push($rows, $row);
                }
            }
            $i++;
        }
        #$data = implode(",",$rows);

        # Adds filmic info: returns ['time',null,null,1,'text']
        $corpusFile = $path . "/data/" . $subdir_id . "/Filmic-Hits.txt";

        [$filmic_rows, $filmic_lastTime] = $this->csv->getFilmic($corpusFile);
        $mergeR = array_merge($rows, $filmic_rows);
        $mergeT = array_merge($rows, $filmic_lastTime);

        $data = implode(",", $mergeR);
        $maxValue = end($mergeT) + 1;

        ////// stats
        $count = count($words);
        $sum = array_sum($words);
        $mean = $sum / $count;

        rsort($words);
        $middle = round(count($words) / 2);
        $median = $words[$middle - 1];

        $min = min($words);
        $max = max($words);

        foreach ($words as $key => $num) {
            $devs[$key] = ($num - $mean) ** 2;
        }
        $std = sqrt(array_sum($devs) / (count($devs) - 1));

        $var = 0.0;
        foreach ($words as $i) {
            $var += ($i - $mean) ** 2;
        }
        $var /= (false ? count($words) - 1 : count($words));
        ///////

        return $this->render(
            'hits/hitsVisual.html.twig',
            ['key' => $data, 'title' => $dir_id, 'type' => 'num. of words', 'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id]
        );
    }


    ### reads Hits-All.txt file and displays utterances in timeline (counting time/words)
    #[Route(path: '/hits/timewords/{subdir_id}/{dir_id}')]
    public function timewords($dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $csvFile = file($file);
        #$data = [];

        $rows = [];
        $duration = [];
        $lastTime = [];
        $i = 1;
        foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t");
            if (count($data) > 2 & $i > 1) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($dir_id == $file) {
                    $row = "";
                    #$sentence = explode(" ", $data[3]);
                    $vowels = [" ", ",", ".", ";", "!", ":", "-"];
                    $sentence = str_replace($vowels, "", $data[3]);

                    $c = strlen(mb_convert_encoding($sentence, 'ISO-8859-1'));
                    $t = $data[0] / 60000;

                    #$x = $data[2] / str_word_count($data[3]);
                    $time = $data[2] / 1000;
                    $x = $c / $time;
                    array_push($duration, $x);
                    $row = "";
                    $text = "'characters/second: " . $x . "'";
                    $row = '[' . $t . ',' . $x . ',' . $text . ']';
                    array_push($rows, $row);
                    array_push($lastTime, $t); #to get last time
                }
            }
            $i++;
        }
        $maxValue = end($lastTime) + 1;
        $data = implode(",", $rows);
        ////// stats
        $count = count($duration);
        $sum = array_sum($duration);
        $mean = $sum / $count;

        rsort($duration);
        $middle = round(count($duration) / 2);
        $median = $duration[$middle - 1];

        $min = min($duration);
        $max = max($duration);

        foreach ($duration as $key => $num) {
            $devs[$key] = ($num - $mean) ** 2;
        }
        $std = sqrt(array_sum($devs) / (count($devs) - 1));

        $var = 0.0;
        foreach ($duration as $i) {
            $var += ($i - $mean) ** 2;
        }
        $var /= (false ? count($duration) - 1 : count($duration));
        ///////


        return $this->render(
            'hits/Hits.html.twig',
            ['key' => $data, 'title' => $dir_id, 'type' => 'characters/second', 'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id]
        );
    }

    ### reads Hits-All.txt file and displays utterances in timeline + visual info (counting time/words)
    #[Route(path: '/hits/timewordsvisual/{subdir_id}/{dir_id}')]
    public function timewordsvisual($dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $csvFile = file($file);
        #$data = [];

        $rows = [];
        $duration = [];
        $lastTime = [];
        $i = 1;
        foreach ($csvFile as $line) {
            $data = str_getcsv($line, "\t");
            if (count($data) > 2 & $i > 1) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($dir_id == $file) {
                    $row = "";
                    #$sentence = explode(" ", $data[3]);
                    $vowels = [" ", ",", ".", ";", "!", ":", "-"];
                    $sentence = str_replace($vowels, "", $data[3]);

                    $c = strlen(mb_convert_encoding($sentence, 'ISO-8859-1'));
                    $t = $data[0] / 60000;

                    #$x = $data[2] / str_word_count($data[3]);
                    $time = $data[2] / 1000;
                    $x = $c / $time;
                    array_push($duration, $x);
                    $row = "";
                    $text = "'characters/second: " . $x . "'";
                    $row = '[' . $t . ',' . $x . ',' . $text . ',' . 'null' . ',' . 'null]';
                    array_push($rows, $row);
                    array_push($lastTime, $t); #to get last time
                }
            }
            $i++;
        }
        # Adds filmic info: returns ['time',null,null,1,'text']
        $corpusFile = $path . "/data/" . $subdir_id . "/Filmic-Hits.txt";

        [$filmic_rows, $filmic_lastTime] = $this->csv->getFilmic($corpusFile);
        $mergeR = array_merge($rows, $filmic_rows);
        $mergeT = array_merge($rows, $filmic_lastTime);

        $data = implode(",", $mergeR);
        $maxValue = end($mergeT) + 1;
        ////// stats
        $count = count($duration);
        $sum = array_sum($duration);
        $mean = $sum / $count;

        rsort($duration);
        $middle = round(count($duration) / 2);
        $median = $duration[$middle - 1];

        $min = min($duration);
        $max = max($duration);

        foreach ($duration as $key => $num) {
            $devs[$key] = ($num - $mean) ** 2;
        }
        $std = sqrt(array_sum($devs) / (count($devs) - 1));

        $var = 0.0;
        foreach ($duration as $i) {
            $var += ($i - $mean) ** 2;
        }
        $var /= (false ? count($duration) - 1 : count($duration));
        ///////

        return $this->render(
            'hits/hitsVisual.html.twig',
            ['key' => $data, 'title' => $dir_id, 'type' => 'characters/second', 'mean' => $mean, 'median' => $median, 'min' => $min, 'max' => $max, 'std' => $std, 'var' => $var, 'maxValue' => $maxValue, 'path' => $subdir_id]
        );
    }

    ## reads countWords.txt file (for corpus) and displays some figures
    #[Route(path: '/words/{corpus}/{subdir_id}/{file_id}', requirements: ['corpus' => 'corpus'])] // we set corpus to 'corpus' (silly thing to avoid routing errors with next function)
    public function wordsCorpus($subdir_id, $file_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/";
        $file = $dataDir . $file_id;

        $lines = file($file);
        #$data = [];

        $rows = [];
        $pWords = [];
        $sValues = [];
        $rows2 = [];
        $sWords = [];
        $all = [];


        foreach ($lines as $line) {
            $data = explode("\t", $line);

            if ($data[0] == "SentencesXpar:") {
                $sents = chop($data[1]);
                $sents = trim($sents, '[');
                $sents = trim($sents, ']');
                $sValues = explode(",", $sents);
            } elseif ($data[0] == "WordsXpar:") {
                $words = chop($data[1]);
                $words = trim($words, '[');
                $words = trim($words, ']');
                $pWords = explode(",", $words);
            } elseif ($data[0] == "WordsXsentence:") {
                $words = chop($data[1]);
                $words = trim($words, '[');
                $words = trim($words, ']');
                $sWords = explode(",", $words);
            } else {
                $k = trim($data[0], ':');
                $v = chop($data[1]);
                $all[$k] = $v;
            }
        }

        $i = 0;
        foreach ($sValues as $s) {
            $w = $pWords[$i];
            $row = '[' . $i . ',' . $s . ',' . $w . ']';
            array_push($rows, $row);
            $i++;
        }
        $data = implode(",", $rows);
        $j = 0;
        foreach ($sWords as $s) {
            $row = '[' . $j . ',' . $s . ']';
            array_push($rows2, $row);
            $j++;
        }
        $data2 = implode(",", $rows2);
        $back = "corpus/" . $subdir_id;
        return $this->render(
            'hits/words.html.twig',
            ['key' => $data, 'all' => $all, 'key2' => $data2, 'title' => $subdir_id, 'type' => 'num. of words', 'back' => $back]
        );
    }


    ## reads countWords.txt file (for files) and displays some figures
    #[Route(path: '/words/{subdir_id}/{dir_id}/{file_id}')]
    public function words($file_id, $dir_id, $subdir_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $subdir_id . "/" . $dir_id . "/";
        $file = $dataDir . $file_id;

        $lines = file($file);
        #$data = [];

        $rows = [];
        $pWords = [];
        $sValues = [];
        $rows2 = [];
        $sWords = [];
        $all = [];


        foreach ($lines as $line) {
            $data = explode("\t", $line);

            if ($data[0] == "SentencesXpar:") {
                $sents = chop($data[1]);
                $sents = trim($sents, '[');
                $sents = trim($sents, ']');
                $sValues = explode(",", $sents);
            } elseif ($data[0] == "WordsXpar:") {
                $words = chop($data[1]);
                $words = trim($words, '[');
                $words = trim($words, ']');
                $pWords = explode(",", $words);
            } elseif ($data[0] == "WordsXsentence:") {
                $words = chop($data[1]);
                $words = trim($words, '[');
                $words = trim($words, ']');
                $sWords = explode(",", $words);
            } else {
                $k = trim($data[0], ':');
                $v = chop($data[1]);
                $all[$k] = $v;
            }
        }

        $i = 0;
        foreach ($sValues as $s) {
            $w = $pWords[$i];
            $row = '[' . $i . ',' . $s . ',' . $w . ']';
            array_push($rows, $row);
            $i++;
        }
        $data = implode(",", $rows);
        $j = 0;
        foreach ($sWords as $s) {
            $row = '[' . $j . ',' . $s . ']';
            array_push($rows2, $row);
            $j++;
        }
        $data2 = implode(",", $rows2);
        $back = "metadata/" . $subdir_id . "/" . $dir_id;
        return $this->render(
            'hits/words.html.twig',
            ['key' => $data, 'all' => $all, 'key2' => $data2, 'title' => $dir_id, 'type' => 'num. of words', 'back' => $back]
        );
    }

    ## reads Hits-All.txt file (for files) and displays data in timeline,
    ##
    #[Route(path: '/hits/timeline/{corpus_id}')]
    public function timeline($corpus_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $corpus_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $lines = file($file);

        $rows = [];
        $data = [];
        # 176450	177010	560	S'atura.	AccessFriendly-CA.eaf
        foreach ($lines as $l) {
            $line = trim($l);
            $data = explode("\t", $line);
            if (count($data) > 2) {
                $text = htmlentities(mb_substr(rtrim($data[3]), 0, 60));
                $paths = explode("/", $data[4]);
                $file = substr($paths[2], 0, -4);
                $row = '["' . $file . '", "", "' . $text . '",' . $data[0] . ',' . $data[1] . ']';
                array_push($rows, $row);
            }
        }


        $filmic_file = $dataDir . "Filmic-Hits.txt";
        $filmic_lines = file($filmic_file);
        # Scene	67520	218920	151400	S2-Beach
        foreach ($filmic_lines as $l) {
            $line = trim($l);
            $data = explode("\t", $line);
            if (count($data) > 2) {
                if (preg_match('/Speech/', $data[4])) {
                    $row = '["Speech", "" , "speech",' . $data[1] . ',' . $data[2] . ']';
                    array_push($rows, $row);
                }
                $text = htmlentities(mb_substr(rtrim($data[4]), 0, 60));
                $row = '["' . $data[0] . '", "' . $text . '", "' . $text . '",' . $data[1] . ',' . $data[2] . ']';
                array_push($rows, $row);
            }
        }


        $data = implode(",", $rows);

        return $this->render(
            'hits/timeline.html.twig',
            ['key' => $data, 'corpus' => $corpus_id]
        );
    }

    ## reads Hits-All.txt file (for files) and displays data in timeline,
    ##
    #[Route(path: '/hits/timelinejs/{corpus_id}')]
    public function timelinejs($corpus_id): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $corpus_id . "/";
        $file = $dataDir . "Hits-All.txt";

        $lines = file($file);

        $rows = [];
        $data = [];
        $times = [];
        # 176450	177010	560	S'atura.	AccessFriendly-CA.eaf
        # [new Date(t.getTime()+759230), ,"Blackness.", "SDI-Media-UK.eaf"],
        foreach ($lines as $l) {
            $line = trim($l);
            $data = explode("\t", $line);
            if (count($data) > 2) {
                //$text = htmlentities(mb_substr(rtrim($data[3]),0,100));
                $t = htmlentities($data[3]);
                $text = wordwrap($t, 100, "<br>");
                $paths = explode("/", $data[4]);
                $file = substr($paths[2], 0, -4);
                $row = '[new Date(t.getTime()+' . $data[0] . '), , "' . $text . '","' . $file . '"]';
                array_push($rows, $row);
                array_push($times, $data[0]);
            }
        }

        sort($times, SORT_NUMERIC);
        $start = $times[0];
        $data = implode(",", $rows);

        return $this->render(
            'default/timelineCorpus.html.twig',
            ['key' => $data, 'corpus' => $corpus_id, 'start' => $start]
        );
    }

}
