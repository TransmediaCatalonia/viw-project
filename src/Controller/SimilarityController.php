<?php
// src/App/Controller/SimilarityController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/*
Index:

  */

class SimilarityController extends AbstractController
{

    #[Route(path: '/similarity/{dir}', name: 'similarity')]
    public function similarity($dir): Response
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/similarity.csv";


        $rows = array_map('str_getcsv', file($file));
        $header = array_shift($rows);
        $csv = [];

        $first = implode("','", $header);
        $first = "['" . $first . "']";
        array_push($csv, $first);

        foreach ($rows as $row) {
            $one = array_shift($row);
            $two = implode(",", $row);
            $item = "['" . $one . "'," . $two . "]";
            array_push($csv, $item);
        }

        $toHTML = implode(",", $csv);


        return $this->render(
            'similarity/similarity.html.twig',
            ['key' => $toHTML, 'dir' => $dir]
        );
    }
}
