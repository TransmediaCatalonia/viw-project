<?php

namespace App\Utils;

/**
 * simple Kwic script: receives string1 and string2. If string1 is incuded in string2: returns string2 with string1 highlighted. 
 * 
 */
class Kwic 
{
    /**
     * @param string $string
     *
     * @return string
     * simple Kwic script: receives string1 and string2. If string1 is incuded in string2: returns string2 with string1 highlighted. 
     */
    function kwic($str1,$str2) {
   
    $kwicLen = strlen($str1);

    $kwicArray = array();
    $pos          = 0;
    $count       = 0;

    while($pos !== FALSE) {
        $pos = stripos($str2,$str1,$pos); 
        if($pos !== FALSE) {
            $kwicArray[$count]['kwic'] = substr($str2,$pos,$kwicLen);
            $kwicArray[$count++]['pos']  = $pos;
            $pos++;
        }
    }

    $tengo = 0;
    for($I=count($kwicArray)-1;$I>=0;$I--) { 
        $kwic = '<span class="kwic">'.$kwicArray[$I]['kwic'].'</span>';
        $str2 = substr_replace($str2,$kwic,$kwicArray[$I]['pos'],$kwicLen);
	$tengo++;
    }
    if ($tengo > 0){
    	return($str2);
    } else { return(""); }
}

/**
     * @param string $string
     *
     * @return string
     * simple Kwic script: receives string1 and string2. Returns string2 with string1 highlighted. 
     */
function kwicCorpus($str1,$str2) {
   
    $kwicLen = strlen($str1);

    $kwicArray = array();
    $pos          = 0;
    $count       = 0;

    while($pos !== FALSE) {
        $pos = stripos($str2,$str1,$pos); 
        if($pos !== FALSE) {
            $kwicArray[$count]['kwic'] = substr($str2,$pos,$kwicLen);
            $kwicArray[$count++]['pos']  = $pos;
            $pos++;
        }
    }

    for($I=count($kwicArray)-1;$I>=0;$I--) { 
        $kwic = '<span class="kwic">'.$kwicArray[$I]['kwic'].'</span>';
        $str2 = substr_replace($str2,$kwic,$kwicArray[$I]['pos'],$kwicLen);
    }
    return($str2);
}
}
