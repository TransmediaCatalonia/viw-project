<?php
// src/App/Controller/KwicController.php
namespace App\Controller;

use App\Utils\Kwic;
use DOMDocument;
use DOMXpath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KwicController extends AbstractController
{
    protected $kwic;

    public function __construct(
        Kwic $kwic
    )
    {
        $this->kwic = $kwic;
    }

    ## simple search facility for Corpus. Lists corpora available.
    #[Route(path: '/kwic', name: 'kwicHome')]
    public function concordancerHome(): Response
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
        }

        return $this->render(
            'kwic/kwicHome.html.twig',
            ['corpus' => $corpus, 'title' => ""]);
    }


    ## simple search facility for Corpus (searches on 'Hits-All.txt' file with format: [utterance, source.file])
    ## allows for string search on Hits-All.txt file. Displays matching utterances (together with link to source file)
    #[Route(path: '/kwic/{corpus}', name: 'kwic')]
    public function concordancer($corpus, Request $request): Response
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$corpus";
        $file = $dataDir . "/Hits-All.txt";

        ## creates form with textarea
        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('word', TextType::class)
            ->getForm();
        # ->add('word', TextType::class) symfony3
        ## checks if corpus file exists
        $error = "";
        if (!file_exists($file)) {
            $error = "Sorry there's no corpus text available yet!!!! You can try a different corpus.";
        }

        ## check if form already posted

        if ($request->isMethod('POST') && $error == "") {
            $form->handleRequest($request);
            $data = $form->getData();

            foreach ($data as $d) { #var_dump($d);

                $content = file_get_contents($file);
                $lines = explode(PHP_EOL, $content);
                $values = [];
                $files = [];
                foreach ($lines as $l) {
                    $string = explode("\t", $l);
                    if (count($string) > 1) {
                        $path = explode("/", $string[4]);
                        array_pop($path);
                        $link = implode("/", $path);
                        #var_dump($string);print "<br/>";
                        $result = $this->kwic->kwic($d, $string[3]);
                        if ($result != "") {
                            array_push($values, [$result, $link]);
                            if (!in_array($link, $files)) array_push($files, $link);
                        }
                    }
                }
                #var_dump($values);
                $c = count($values);
                $cc = count($files);
                $title = "'$d' was found in $c AD units in $cc files";
                return $this->render(
                    'kwic/kwic.html.twig',
                    ['form' => $form, 'error' => $error, 'result' => $values, 'corpus' => $corpus, 'title' => $title]);
            }
        } else {
            return $this->render(
                'kwic/kwic.html.twig',
                ['form' => $form, 'error' => $error, 'result' => "", 'corpus' => $corpus, 'title' => ""]);
        }
    }

    ## simple search facility for file (sentences.txt file).
    ## displays sentences.txt file and allows for string search. Displays text with matching strings in red
    #[Route(path: '/kwic/corpus/{dir}/{corpus}', name: 'kwiccorpus')]
    public function showcorpus($dir, $corpus, Request $request): Response
    {
        #var_dump($corpus);
        ##
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir/$corpus";
        $file = $dataDir . "/data/sentences.txt";

        ## creates form with textarea
        $defaultData = [];
        $form = $this->createFormBuilder($defaultData)
            ->add('word', TextType::class)
            ->getForm();

        $content = str_replace("###.", "<br/>", file_get_contents($file));
        ## check if form already posted
        $error = "";
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();

            foreach ($data as $d) { #var_dump($d);

                $result = $this->kwic->kwicCorpus($d, $content);

                $title = "Looking for '$d' in $corpus'";
                return $this->render(
                    'kwic/kwicCorpus.html.twig',
                    ['form' => $form, 'error' => $error, 'result' => $result, 'dir' => $dir, 'corpus' => $corpus, 'title' => $title]);
            }
        } else {
            return $this->render(
                'kwic/kwicCorpus.html.twig',
                ['form' => $form, 'error' => $error, 'result' => $content, 'dir' => $dir, 'corpus' => $corpus, 'title' => ""]);
        }
    }
}

