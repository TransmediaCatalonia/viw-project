<?php
// src/App/Controller/VocabularyController.php
namespace App\Controller;

use App\Utils\CSV;

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

/*
Index:

vocabularyPos                 /vocabulary/pos/{dir}                   
vocabularyVerbsProvider       /vocabulary/verbs/{dir}/{provider}      
vocabularyVerbsSemantic       /vocabulary/verbssem/{dir}/{sem}        
vocabularyVerbsDash           /vocabulary/verbsdash/{dir}             
vocabularyNounsDash           /vocabulary/nounsdash/{dir}             
vocabularyAdjsDash            /vocabulary/adjsdash/{dir}              
vocabularyAdvsDash            /vocabulary/advsdash/{dir}              
vocabularyVerbsDashProvider   /vocabulary/verbsdash/{dir}/{provider}  
vocabularyNounsDashProvider   /vocabulary/nounsdash/{dir}/{provider}  
vocabularyAdjsDashProvider    /vocabulary/adjsdash/{dir}/{provider}   
vocabularyPosDash             /vocabulary/posdash/{dir}        
*/

class VocabularyController extends AbstractController
{
    protected $csv;

    public function __construct(
        CSV $csv
    )
    {
        $this->csv = $csv;
    }

    /**
     * @Route("/vocabulary/pos/{dir}", name="vocabularyPos")
     *
     * reads sem.csv file and generates 4 bar charts with verbs/nouns/adj/adv x provider";
     *
     * input file: sem.csv (for corpus data)
     */
    public function verbsDir($dir)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        # returns ['Provider','NumVerbs','UniqVerbs']
        list($verbs, $nouns, $adjectives, $adverbs, $csv) = $this->csv->getVerbsFiles($file);

        # returns ['Provider','NumNouns','UniqVerbs']
        #$result2 = $this->csv->getVerbsFiles($file,"N");

        # returns ['Provider','NumAdjs','UniqVerbs']
        #$result3 = $this->csv->getVerbsFiles($file,"A");

        # returns ['Provider','NumAdvs','UniqVerbs']
        #$result4 = $this->csv->getVerbsFiles($file,"R");

        $toHTML = implode(",", $verbs);

        $toHTML2 = implode(",", $nouns);

        $toHTML3 = implode(",", $adjectives);

        $toHTML4 = implode(",", $adverbs);

        return $this->render(
            'vocabulary/vocabularyVerbs.html.twig',
            array('key' => $toHTML, 'title' => $dir, 'key2' => $toHTML2, 'key3' => $toHTML3, 'key4' => $toHTML4, 'csv' => $csv)
        );
    }

    /**
     * @Route("/vocabulary/verbs/{dir}/{provider}", name="vocabularyVerbsProvider")
     * generates 2 barcharts: 20 top most frequent verbs and semantic class
     */
    public function verbsFilesProvider($dir, $provider)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/" . $dir;
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        # returns: [verb, frequency, Mean]
        $result2 = $this->csv->getAllVerbsFilesProvider($file, $provider);

        # returns: [semantic class, frequency, Mean]
        $result3 = $this->csv->getAllSemVerbsFilesProvider($file, $provider);

        # implode results to get 20 first
        $sliced_array = array_slice($result2, 0, 20);
        $toHTML2 = implode(",", $sliced_array);

        $sliced_array3 = array_slice($result3, 0, 20);
        $toHTML3 = implode(",", $sliced_array3);
        ##var_dump($toHTML);

        $title = substr($provider, 0, -4);

        return $this->render(
            'vocabulary/vocabularyVerbsProvider.html.twig',
            array('title' => $title, 'key2' => $toHTML2, 'key3' => $toHTML3, 'path' => $dir)
        );
    }


    /**
     * @Route("/vocabulary/verbssem/{dir}/{sem}", defaults={"sem" = null }, name="vocabularyVerbsSemantic")
     *  shows a form and a pie with verbal semantic classes, the user selects a semclass and results are placed in timeline
     */
    public function verbssem($dir, Request $request)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";

        # returns [semClass,frequency]
        $result = $this->csv->listSem($file);

        #var_dump($result);

        # initialise array
        $values = array();

        # order result in descending mode and generate "value,label" pairs for the form (where value=label)
        arsort($result);
        foreach ($result as $key => $value) {
            $values[$key] = $key;
        }
        #var_dump($values);

        ## creates form with $values
        $defaultData = array();
        $form = $this->createFormBuilder($defaultData)
            ->add('chooseSemanticClass', ChoiceType::class, array(   #->add('chooseSem', ChoiceType::class,  array(  OJO:symfony3
                'choices' => $values))
            ->getForm();

        ## check if form already posted
        $error = "";
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();

            foreach ($data as $d) { #var_dump($d);
                ## gets data from CSV.php controller
                $path = $this->getParameter('kernel.project_dir');
                $dataDir = $path . "/data/$dir";
                $file = $dataDir . "/sem.csv";
                $csvFile = file($file);
                list($result, $nWords) = $this->csv->pieSemVerbs($file, $d);
                list($result2, $nWords2, $maxValue) = $this->csv->scatterSemVerbs($file, $d);
                #var_dump($result);
                #var_dump($result2);

                $i = count($result) - 1;

                $sem = "$d (total: $nWords ; different: $i)";
                $toHTML = implode(",", $result);

                $toHTML2 = implode(",", $result2);
                $dash = null;

                return $this->render(
                    'vocabulary/vocabularyVerbsSem.html.twig',
                    array('dash' => $dash, 'form' => $form->createView(), 'pie' => $toHTML, 'scatter' => $toHTML2,
                        'error' => $error, 'sem' => $sem, 'dir' => $dir, 'maxValue' => $maxValue));
                return new Response($html);
            }
        } else {

            $result = $this->csv->scatterSemVerbs2($file);
            #var_dump($result);
            $toHTML = implode(",", $result[0]);

            return $this->render(
                'vocabulary/vocabularyVerbsSem.html.twig',
                array('dash' => $toHTML, 'form' => $form->createView(), 'error' => $error, 'pie' => "", 'scatter' => "", 'sem' => "", 'dir' => $dir, 'maxValue' => ""));
            return new Response($html);
        }
    }


    /**
     * @Route("/vocabulary/verbsdash/{dir}", name="vocabularyVerbsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     */
    public function verbsdash($dir)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'V', "");
        #var_dump($result);

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $dir, 'message' => 'semantic class', 'pos' => 'verbs', 'path' => 'corpus', 'csv' => $result));
    }

    /**
     * @Route("/vocabulary/nounsdash/{dir}", name="vocabularyNounsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     *
     */
    public function nounsdash($dir)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'N', "");
        #var_dump($result);

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $dir, 'message' => 'semantic class', 'pos' => 'nouns', 'path' => 'corpus', 'csv' => $result));
    }

    /**
     * @Route("/vocabulary/adjsdash/{dir}", name="vocabularyAdjsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     */
    public function adjsdash($dir)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'A', "");
        #var_dump($result);

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $dir, 'message' => 'semantic class', 'pos' => 'adjectives', 'path' => 'corpus', 'csv' => $result));
    }

    /**
     * @Route("/vocabulary/advsdash/{dir}", name="vocabularyAdvsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     */
    public function advsdash($dir)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'R', "");
        #var_dump($result);

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $dir, 'message' => 'semantic class', 'pos' => 'adverbs', 'path' => 'corpus', 'csv' => $result));
    }

    /**
     * @Route("/vocabulary/verbsdash/{dir}/{provider}", name="vocabularyVerbsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     */
    public function verbsdashPovider($dir, $provider)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'V', $provider);
        #var_dump($result);
        $corpus = substr($provider, 0, -4);
        $corpus = preg_replace('/^What-/', '', $corpus); #dirty...
        $path = 'metadata/' . $dir;

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'semantic class', 'pos' => 'verbs', 'path' => $path, 'csv' => $result));
    }


    /**
     * @Route("/vocabulary/nounsdash/{dir}/{provider}", name="vocabularyNounsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for nouns/semclass
     */
    public function nounsdashPovider($dir, $provider)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'N', $provider);
        #var_dump($result);
        $corpus = substr($provider, 0, -4);
        $corpus = preg_replace('/^What-/', '', $corpus); #dirty...
        $path = 'metadata/' . $dir;

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'semantic class', 'pos' => 'nouns', 'path' => $path, 'csv' => $result));
    }

    /**
     * @Route("/vocabulary/adjsdash/{dir}/{provider}", name="vocabularyAdjsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for nouns/semclass
     */
    public function adjsdashPovider($dir, $provider)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'A', $provider);
        #var_dump($result);
        $corpus = substr($provider, 0, -4);
        $corpus = preg_replace('/^What-/', '', $corpus); #dirty...
        $path = 'metadata/' . $dir;

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'semantic class', 'pos' => 'adjectives', 'path' => $path, 'csv' => $result));
    }

    /**
     * @Route("/vocabulary/advsdash/{dir}/{provider}", name="vocabularyAdvsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for nouns/semclass
     */
    public function advsdashPovider($dir, $provider)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaSemFreq($file, 'R', $provider);
        #var_dump($result);
        $corpus = substr($provider, 0, -4);
        $corpus = preg_replace('/^What-/', '', $corpus); #dirty...
        $path = 'metadata/' . $dir;

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'semantic class', 'pos' => 'adverbs', 'path' => $path, 'csv' => $result));
    }


    /**
     * @Route("/vocabulary/posdash/{dir}", name="vocabularyPosDash")
     * returns: ['lemma', 'PoS', 'Frequency' ]
     * pie + table + form for posTag
     */
    public function sposdash($dir)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        list($values, $result) = $this->csv->getLemmaPoSFreq($file, "");
        #var_dump($result);
        $path = 'corpus';
        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $dir, 'message' => 'PoS tag', 'pos' => '', 'path' => $path, 'csv' => $result));
    }


    /**
     * @Route("/vocabulary/posdash/{dir}/{provider}", name="vocabularyPosDashProvider")
     * returns: ['lemma', 'PoS', 'Frequency' ]
     * pie + table + form for posTag
     */
    public function sposdashProvider($dir, $provider)
    {
        ## gets data from CSV.php controller
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/$dir";
        $file = $dataDir . "/sem.csv";
        $csvFile = file($file);

        $corpus = substr($provider, 0, -4);
        $corpus = preg_replace('/^What-/', '', $corpus); #dirty...
        $path = 'metadata/' . $dir;

        list($values, $result) = $this->csv->getLemmaPoSFreq($file, $provider);
        #var_dump($result);

        $toHTML = implode(",", $values);
        return $this->render(
            'vocabulary/vocabularyDash.html.twig',
            array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'PoS tag', 'pos' => '', 'path' => $path, 'csv' => $result));
    }

}
