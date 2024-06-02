<?php
// src/App/Controller/DensityController.php
namespace App\Controller;

use App\Utils\Metadata;
use DOMDocument;
use DOMXpath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DensityController extends AbstractController
{
    protected $metadata;

    public function __construct(
        Metadata $metadata
    )
    {
        $this->metadata = $metadata;
    }

    #[Route(path: '/density', name: 'density')]
    public function listDensityFiles(Request $request)
    {
        $path = $this->getParameter('kernel.project_dir');

        ## reads records.xml and gets records available into $files0
        $recordsFile = $path . "/data/records.xml";
        $records = $this->metadata->metadata($recordsFile);
        $files0 = [];
        foreach ($records->record as $r) {
            $id = $r['id'];
            //$pair[0]=[$id];
            $keys = [$id];
            $pair = array_fill_keys($keys, $id);
            array_push($files0, $pair);
        }


        ## creates form with $files0 ## symphony3.0: 	->add('chooseTwoFiles', ChoiceType::class,  array(
        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('chooseTwoFiles', ChoiceType::class, ['choices' => [$files0], 'multiple' => true, 'expanded' => true, 'choice_value' => fn($key) => $key])
            ->getForm();

        ## check if form already posted
        $error = "";
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();

            foreach ($data as $d) {
                if (count($d) > 2) {
                    $error = "You can only choose TWO files!!";
                    return $this->render(
                        'density/density.html.twig',
                        ['form' => $form, 'error' => $error]);
                } elseif (count($d) < 2) {
                    $error = "You need TWO files!!";
                    return $this->render(
                        'density/density.html.twig',
                        ['form' => $form, 'error' => $error]);
                } else {
                    return $this->redirectToRoute('density_graph', ['file1' => $d[0], 'file2' => $d[1]]);
                }
            }
        } else {
            return $this->render(
                'density/density.html.twig',
                ['form' => $form, 'error' => $error]);
        }
    }

    #[Route(path: '/density/graph/{file1}/{file2}', name: 'density_graph')]
    public function graph($file1, $file2): Response
    {
        $path = $this->getParameter('kernel.project_dir');
        $indexFile = $path . "/data/records.xml";
        $dataDir = $path . "/data";

        if (file_exists($indexFile)) {
            // build domXpath
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->loadXml(file_get_contents($indexFile));

            $xpath = new DOMXpath($doc);

            // get results
            $files = [];
            $query1 = "//record[@id='" . $file1 . "']";
            $query2 = "//record[@id='" . $file2 . "']";
            //var_dump($query);
            $source1 = $xpath->query($query1);
            $source2 = $xpath->query($query2);
            foreach ($source1 as $r) {
                $sources = $r->getElementsByTagName("corpus");
                $s1 = $sources->item(0)->nodeValue;
                #print $s1;
            }
            foreach ($source2 as $r) {
                $sources = $r->getElementsByTagName("corpus");
                $s2 = $sources->item(0)->nodeValue;
                #print $s2;
            }
        }


        $data1 = $dataDir . "/" . $s1 . "/Hits-All.txt";
        $data2 = $dataDir . "/" . $s2 . "/Hits-All.txt";

        $csvFile1 = file($data1);
        $csvFile2 = file($data2);

        $maxValues = [];
        $rows = [];
        foreach ($csvFile1 as $l) {
            $line = trim($l);
            $data = str_getcsv($line, "\t");
            if (count($data) > 2) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($file == $file1) {
                    $row = "";
                    $string = preg_replace('/[^A-Za-z0-9\- ,]/', '', $data[3]);
                    $text = "'" . $string . "'";
                    $t = $data[0] / 60000;
                    $row = '[' . $t . ',' . $data[2] . ',' . $text . ',' . 'null' . ',' . 'null]';
                    array_push($rows, $row);
                    array_push($maxValues, $t);
                }
            }
        }

        foreach ($csvFile2 as $l) {
            $line = trim($l);
            $data = str_getcsv($line, "\t");
            if (count($data) > 2) {
                $paths = explode("/", (string)$data[4]);
                $file = substr($paths[2], 0, -4);
                if ($file == $file2) {
                    $row = "";
                    $string = preg_replace('/[^A-Za-z0-9\- ,]/', '', $data[3]);
                    $text = "'" . $string . "'";
                    $t = $data[0] / 60000;
                    $row = '[' . $t . ', null, null, ' . $data[2] . ',' . $text . ']';
                    array_push($rows, $row);
                }
            }
        }
        $maxValue = end($maxValues) + 1;
        $data = implode(",", $rows);

        return $this->render(
            'density/graph.html.twig',
            ['key' => $data, 'lang1' => $file1, 'lang2' => $file2, 'maxValue' => $maxValue]
        );

    }

}
