<?php
/* Shows source file */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Utils\Metadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowFile extends Controller
{
    /**
     * @Route("/show/{dir_id}/{subdir_id}/{file_id}", defaults={"file_id" = "null"})
     */
public function showFile($file_id,$dir_id,$subdir_id)
    {

	$path = $this->container->getParameter('kernel.root_dir');
	if ($file_id == "null") { 
		$dataDir = $path . "/../";
        	$file = $dataDir . $dir_id . '/' . $subdir_id ;}
	else {
        	$dataDir = $path . "/../data/";
        	$file = $dataDir . $dir_id . '/' . $subdir_id . '/' . $file_id;
	}

	if (file_exists($file)) {

	$options = array('http' => array(
        'method'  => 'POST',
        'content' => $file,
        'header'  => 
            "Content-Type: text/plain\r\n" .
            "Content-Length: " . strlen($file) . "\r\n"
    ));
    	$context  = stream_context_create($options);

        return new Response(file_get_contents($file, false, $context));
    } else {
        throw NotFoundHttpException("Guide {$filename} Not Found.");
    }
        
	 

    }
}

