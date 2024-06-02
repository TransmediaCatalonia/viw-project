<?php
// src/App/Controller/SearchController.php

/* Search in xml file */

namespace App\Controller;

use DOMDocument;
use DOMXpath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route(path: '/search')]
    public function searchXML(): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $indexFile = $path . "/data/records.xml";
        $languages = [];
        $subjects = [];

        if (file_exists($indexFile)) {
            // biuld domXpath
            $doc = new DOMDocument();
            $doc->loadXml(file_get_contents($indexFile));
            $doc->preserveWhiteSpace = false;
            $xpath = new DOMXpath($doc);

            // get corpus
            $allCorpus = $xpath->query("//corpus/@id");
            $corp = [];
            foreach ($allCorpus as $l) {
                array_push($corp, trim(($l->nodeValue)));
            }
            $corpus = array_unique($corp);
            sort($corpus);

            // get languages
            $allLanguages = $xpath->query("//language");
            $langs = [];
            foreach ($allLanguages as $l) {
                array_push($langs, trim(($l->nodeValue)));
            }
            $languages = array_unique($langs);
            sort($languages);

            // get providers
            $allSubjects = $xpath->query("//creator");
            $pros = [];
            foreach ($allSubjects as $l) {
                array_push($pros, trim(($l->nodeValue)));
            }
            $providers = array_unique($pros);
            sort($providers);

            // get expertisse
            $allSubjects = $xpath->query("//expertise");
            $exps = [];
            foreach ($allSubjects as $l) {
                array_push($exps, trim(($l->nodeValue)));
            }
            $expertise = array_unique($exps);
            sort($expertise);

        } else {
            /*throw NotFoundHttpException("Guide {$filename} Not Found.");*/
        }

        return $this->render(
            'Search/search.html.twig',
            ['corpus' => $corpus, 'languages' => $languages, 'providers' => $providers, 'expertise' => $expertise]
        );
    }


    #[Route(path: '/search/{node}/{value}')] // Example: http://localhost:8000/search/language/CA
    public function searchNode($node, $value): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $indexFile = $path . "/data/records.xml";
        $languages = [];
        $subjects = [];
        //var_dump($node,$value);

        if (file_exists($indexFile)) {
            // build domXpath
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->loadXml(file_get_contents($indexFile));

            $xpath = new DOMXpath($doc);

            // get results
            $files = [];
            $query = "//record[" . $node . "='" . $value . "']";
            //var_dump($query);
            $results = $xpath->query($query);
            foreach ($results as $result) {
                $source = $result->getElementsByTagName("source");
                $id = $source->item(0)->nodeValue;
                $dir = explode("/", $id);
                $titles = $result->getElementsByTagName("title");
                $title = $titles->item(0)->nodeValue;
                #$descriptions = $result->getElementsByTagName( "description" );
                #$description = $descriptions->item(0)->nodeValue;
                $selectedFields = [];
                array_push($selectedFields, $title, $dir[0], $dir[1]);
                array_push($files, $selectedFields);
                ksort($files);
            }
//$xpath = new DOMXPath($dom);
//foreach ($xpath->query('/root/p/text()') as $textNode) {
            //  echo $textNode->nodeValue;
//}

        } else {
            /*throw NotFoundHttpException("Guide {$filename} Not Found.");*/
        }

        return $this->render(
            'Search/results.html.twig',
            ['node' => $node, 'value' => $value, 'files' => $files]
        );
    }
}
