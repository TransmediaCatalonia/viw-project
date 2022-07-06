<?php
/* Shows source file */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\Metadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowFileController extends AbstractController
{
    /**
     * @Route("/show/{dir_id}/{subdir_id}/{file_id}", defaults={"file_id" = "null"})
     */
    public function showFile($file_id, $dir_id, $subdir_id)
    {
        $path = $this->getParameter('kernel.project_dir');
        if ($file_id == "null") {
            $dataDir = $path . "/";
            $file = $dataDir . $dir_id . '/' . $subdir_id;
        } else {
            $dataDir = $path . "/data/";
            $file = $dataDir . $dir_id . '/' . $subdir_id . '/' . $file_id;
        }

        if (file_exists($file)) {

            $options = array('http' => array(
                'method' => 'POST',
                'content' => $file,
                'header' =>
                    "Content-Type: text/plain\r\n" .
                    "Content-Length: " . strlen($file) . "\r\n"
            ));
            $context = stream_context_create($options);

            return new Response(file_get_contents($file, false, $context));
        } else {
            throw NotFoundHttpException("Guide {$filename} Not Found.");
        }


    }
}

