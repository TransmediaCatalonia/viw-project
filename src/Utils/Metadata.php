<?php

/* receives an XML file, reads it (with simplexml_load_file) and returns
 * 
 */


namespace App\Utils;

/**
 * integrating simple classes as
 * services into a Symfony application.

 */
class Metadata 
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function metadata($file)
    {
//	var_dump($file);
	if (file_exists($file)) {
                
    		$xml = simplexml_load_file($file);
		
	} 
	
        return $xml;
    }
}
