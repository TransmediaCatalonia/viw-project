<?php
// src/AppBundle/Controller/VocabularyController.php
namespace AppBundle\Controller;
use AppBundle\Utils\CSV;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

  vocabularyVerbs            ANY      ANY      ANY    /vocabulary/verbs/{dir}                           
  vocabularyVerbsProvider    ANY      ANY      ANY    /vocabulary/verbs/{dir}/{provider}                
  vocabularyVerbsSemantic    ANY      ANY      ANY    /vocabulary/verbssem/{dir}/{sem}                  
  vocabularyVerbsDash        ANY      ANY      ANY    /vocabulary/verbsdash/{dir}                       
  vocabularyNounsDash        ANY      ANY      ANY    /vocabulary/nounsdash/{dir}                       
  vocabularyAdjsDash         ANY      ANY      ANY    /vocabulary/adjsdash/{dir}                        
  vocabularyPosDash          ANY      ANY      ANY    /vocabulary/posdash/{dir}       
*/

class VocabularyController extends Controller
{
 
/**  
     * @Route("/vocabulary/pos/{dir}", name="vocabularyPos")
     * generates 3 bar charts: "verbs x provider"; "20 top frequency verbs", "20 to semantic class verbs"
     * input file: sem.csv (for corpus data)
     */
public function verbsDir($dir)
    {    
        ## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

	# returns ['Provider','NumVerbs','UniqVerbs']
        $result = $this->get('app.utils.csv')->getVerbsFiles($file,"V");

	# returns ['Provider','NumNouns','UniqVerbs']
	$result2 = $this->get('app.utils.csv')->getVerbsFiles($file,"N");

	# returns ['Provider','NumAdjs','UniqVerbs']
	$result3 = $this->get('app.utils.csv')->getVerbsFiles($file,"A");
		
	# returns ['Provider','NumAdvs','UniqVerbs']
	$result4 = $this->get('app.utils.csv')->getVerbsFiles($file,"R");

	$toHTML = implode(",",$result);
	
	$toHTML2 = implode(",",$result2);

	$toHTML3 = implode(",",$result3);

	$toHTML4 = implode(",",$result4);
	
        $html = $this->container->get('templating')->render(
            'vocabulary/vocabularyVerbs.html.twig',
            array('key' => $toHTML, 'title' => $dir, 'key2' => $toHTML2, 'key3' => $toHTML3, 'key4' => $toHTML4)
        );

        return new Response($html);

    }

/**  
     * @Route("/vocabulary/verbs/{dir}/{provider}", name="vocabularyVerbsProvider")
     * generates 2 barcharts: 20 top most frequent verbs and semantic class
     */
public function verbsFilesProvider($dir,$provider)
    { 
        ## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/" . $dir;
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);
        
	# returns: [verb, frequency, Mean]
	$result2 = $this->get('app.utils.csv')->getAllVerbsFilesProvider($file,$provider);

	# returns: [semantic class, frequency, Mean]
	$result3 = $this->get('app.utils.csv')->getAllSemVerbsFilesProvider($file,$provider);
	
	# implode results to get 20 first	
	$sliced_array = array_slice($result2,0,20);
	$toHTML2 = implode(",",$sliced_array);
	
	$sliced_array3 = array_slice($result3,0,20);
	$toHTML3 = implode(",",$sliced_array3);
	##var_dump($toHTML);

	$title = substr($provider, 0, -4);

        $html = $this->container->get('templating')->render(
            'vocabulary/vocabularyVerbsProvider.html.twig',
            array('title' => $title, 'key2' => $toHTML2, 'key3' => $toHTML3)
        );
        return new Response($html);
    }


/**  
     * @Route("/vocabulary/verbssem/{dir}/{sem}", defaults={"sem" = null }, name="vocabularyVerbsSemantic")
     *  shows a form and a pie with verbal semantic classes, the user selects a semclass and results are placed in timeline
     */
    public function verbssem($dir, Request $request)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";

	# returns [semClass,frequency]
        $result = $this->get('app.utils.csv')->listSem($file);

	#var_dump($result);

	# initialise array
	$values = array();

	# order result in descending mode and generate "value,label" pairs for the form (where value=label)
	arsort($result);
	foreach($result as $key => $value) { 
		$values[$key] = $key;
	}
	#var_dump($values);

	## creates form with $values
	$defaultData = array();
    	$form = $this->createFormBuilder($defaultData)
	->add('chooseSemanticClass', 'choice',  array(   #->add('chooseSem', ChoiceType::class,  array(  OJO:symfony3
    	'choices' => $values))
	->getForm();
			
	## check if form already posted
 	$error = "";
        if ($request->isMethod('POST')) {
             $form->handleRequest($request);
	     $data = $form->getData();
             	
	     foreach ($data as $d){ #var_dump($d);
		## gets data from CSV.php controller
        	$path = $this->container->getParameter('kernel.root_dir');
        	$dataDir = $path . "/../data/$dir";
        	$file = $dataDir .  "/sem.csv";
		$csvFile = file($file);
        	list($result,$nWords) = $this->get('app.utils.csv')->pieSemVerbs($file,$d);
		list($result2,$nWords2,$maxValue) = $this->get('app.utils.csv')->scatterSemVerbs($file,$d);
		#var_dump($result);
		#var_dump($result2);

		$i = count($result) - 1;
		
		$sem = "$d (total: $nWords ; different: $i)";
		$toHTML = implode(",",$result);

		$toHTML2 = implode(",",$result2);
		$dash = null;

		$html = $this->container->get('templating')->render(
            	'vocabulary/vocabularyVerbsSem.html.twig',
            	array('dash' => $dash, 'form' => $form->createView(),'pie' => $toHTML, 'scatter' => $toHTML2, 
		      'error' => $error, 'sem' => $sem, 'dir' => $dir, 'maxValue' => $maxValue));
		return new Response($html);
	     }
	}
	else {

		$result = $this->get('app.utils.csv')->scatterSemVerbs2($file);
		#var_dump($result);
		$toHTML = implode(",",$result[0]);

		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyVerbsSem.html.twig',
		array('dash' => $toHTML, 'form' => $form->createView(), 'error' => $error, 'pie' => "", 'scatter' => "",'sem' => "", 'dir' => $dir, 'maxValue' => ""));
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
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'V',"");
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'Semantic Class', 'pos' => 'verbs', 'path' => 'corpus'));
		return new Response($html);
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
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'N',"");
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'Semantic Class', 'pos' => 'nouns', 'path' => 'corpus'));
		return new Response($html);
    }

/**  
     * @Route("/vocabulary/adjsdash/{dir}", name="vocabularyAdjsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     */
    public function adjsdash($dir)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'A',"");
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'Semantic Class', 'pos' => 'adjectives', 'path' => 'corpus'));
		return new Response($html);
    }


/**  
     * @Route("/vocabulary/verbsdash/{dir}/{provider}", name="vocabularyVerbsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for verbs/semclass
     */
    public function verbsdashPovider($dir,$provider)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'V',$provider);
	#var_dump($result);
	$corpus = substr($provider, 0, -4);
	$corpus = preg_replace('/^What-/', '', $corpus); #dirty...
	$path = 'metadata/' . $dir;

	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'Semantic Class', 'pos' => 'verbs', 'path' => $path));
		return new Response($html);
    }


/**  
     * @Route("/vocabulary/nounsdash/{dir}/{provider}", name="vocabularyNounsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for nouns/semclass
     */
    public function nounsdashPovider($dir,$provider)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'N',$provider);
	#var_dump($result);
	$corpus = substr($provider, 0, -4);
	$corpus = preg_replace('/^What-/', '', $corpus); #dirty...
	$path = 'metadata/' . $dir;

	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'Semantic Class', 'pos' => 'nouns', 'path' => $path));
		return new Response($html);
    }

/**  
     * @Route("/vocabulary/adjsdash/{dir}/{provider}", name="vocabularyAdjsDashProvider")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     * pie + table + form for nouns/semclass
     */
    public function adjsdashPovider($dir,$provider)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'A',$provider);
	#var_dump($result);
	$corpus = substr($provider, 0, -4);
	$corpus = preg_replace('/^What-/', '', $corpus); #dirty...
	$path = 'metadata/' . $dir;

	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $corpus, 'message' => 'Semantic Class', 'pos' => 'adjectives', 'path' => $path));
		return new Response($html);
    }



/**  
     * @Route("/vocabulary/posdash/{dir}", name="vocabularyPosDash")
     * returns: ['lemma', 'PoS', 'Frequency' ]
     * pie + table + form for posTag
     */
    public function sposdash($dir)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaPoSFreq($file);
	#var_dump($result);
	$path = 'corpus' ;
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'PoS tag', 'pos' => '', 'path' => $path));
		return new Response($html);
    }



}
