<?php
// src/App/Controller/KwicController.php
namespace App\Controller;

use App\Utils\Kwic;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class KwicController extends AbstractController
{
    protected $kwic;

    public function __construct(
        Kwic $kwic
    )
    {
        $this->kwic = $kwic;
    }
    /**
     * @Route("/kwic", name="kwicHome")
     */
    ## simple search facility for Corpus. Lists corpora available.
    public function concordancerHome()
    {
        $path = $this->getParameter('kernel.project_dir');
        $indexFile = $path . "/data/records.xml";
        $languages = array();
        $subjects = array();

        if (file_exists($indexFile)) {
            // biuld domXpath
            $doc = new \DOMDocument();
            $doc->loadXml(file_get_contents($indexFile));
            $doc->preserveWhiteSpace = false;
            $xpath = new \DOMXpath($doc);

            // get corpus
            $allCorpus = $xpath->query("//corpus/@id");
            $corp = array();
            foreach ($allCorpus as $l) {
                array_push($corp, trim(($l->nodeValue)));
            }
            $corpus = array_unique($corp);
        }

        return $this->render(
            'kwic/kwicHome.html.twig',
            array('corpus' => $corpus, 'title' => ""));
    }


    /**
     * @Route("/kwic/{corpus}", name="kwic")
     */
    ## simple search facility for Corpus (searches on 'Hits-All.txt' file with format: [utterance, source.file])
    ## allows for string search on Hits-All.txt file. Displays matching utterances (together with link to source file)
    public function concordancer($corpus, Request $request)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$corpus";
        $file = $dataDir . "/Hits-All.txt";

        ## creates form with textarea
        $defaultData = array();
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
                $values = array();
                $files = array();
                foreach ($lines as $l) {
                    $string = explode("\t", $l);
                    if (count($string) > 1) {
                        $path = explode("/", $string[4]);
                        array_pop($path);
                        $link = implode("/", $path);
                        #var_dump($string);print "<br/>";
                        $result = $this->kwic->kwic($d, $string[3]);
                        if ($result != "") {
                            array_push($values, array($result, $link));
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
                    array('form' => $form->createView(), 'error' => $error, 'result' => $values, 'corpus' => $corpus, 'title' => $title));
            }
        } else {
            return $this->render(
                'kwic/kwic.html.twig',
                array('form' => $form->createView(), 'error' => $error, 'result' => "", 'corpus' => $corpus, 'title' => ""));
        }
    }

    /**
     * @Route("/kwic/corpus/{dir}/{corpus}", name="kwiccorpus")
     */
    ## simple search facility for file (sentences.txt file).
    ## displays sentences.txt file and allows for string search. Displays text with matching strings in red
    public function showcorpus($dir, $corpus, Request $request)
    {
        #var_dump($corpus);
        ##
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir/$corpus";
        $file = $dataDir . "/data/sentences.txt";

        ## creates form with textarea
        $defaultData = array();
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
                    array('form' => $form->createView(), 'error' => $error, 'result' => $result, 'dir' => $dir, 'corpus' => $corpus, 'title' => $title));
            }
        } else {
            return $this->render(
                'kwic/kwicCorpus.html.twig',
                array('form' => $form->createView(), 'error' => $error, 'result' => $content, 'dir' => $dir, 'corpus' => $corpus, 'title' => ""));
        }
    }
}

