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


@Route("/vocabulary/verbs/{dir}", name="vocabularyVerbs") 
     ex: vocabulary/verbs/What-ES  (generates 3 bar charts: "verbs x provider"; "20 top frequency verbs", "20 to semantic class verbs")

@Route("/vocabulary/verbs/{dir}/{provider}", name="vocabularyVerbsProvider")
	vocabulary/verbs/What-ES/What-Aptent-ES.eaf  (two bar charts: "20 top frequency verbs", "20 to semantic class verbs")

@Route("/vocabulary/verbssem/{dir}/{sem}", defaults={"sem" = null }, name="vocabularyVerbsSemantic")
	form that gives piechart         *****

@Route("/vocabulary/verbssemdash/{dir}", name="vocabularyVerbsSemanticDash")
     generates dashboard with lemma/semantic class
*/

class VocabularyController extends Controller
{
 
/**  
     * @Route("/vocabulary/verbs/{dir}", name="vocabularyVerbs")
     * generates 3 bar charts: "verbs x provider"; "20 top frequency verbs", "20 to semantic class verbs"
     */
public function verbsDir($dir)
    {    
        ## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/semVerbs.csv";
	$csvFile = file($file);

	# returns ['Provider','NumVerbs','UniqVerbs']
        $result = $this->get('app.utils.csv')->getVerbsFiles($file);

	# returns [verb, frequency, relativeFrequency in %]
	$result2 = $this->get('app.utils.csv')->getAllVerbsFiles($file);

	# returns [semClass, frequency, RelFreq]
	$result3 = $this->get('app.utils.csv')->getAllSemVerbsFiles($file);
	
		
	$toHTML = implode(",",$result);
	
	# we slice array to get the first 20
	$sliced_array = array_slice($result2,0,20);
	$toHTML2 = implode(",",$sliced_array);

	# we slice array to get the first 20
	$sliced_array3 = array_slice($result3,0,20);
	$toHTML3 = implode(",",$sliced_array3);
	

        $html = $this->container->get('templating')->render(
            'vocabulary/vocabularyVerbs.html.twig',
            array('key' => $toHTML, 'title' => $dir, 'key2' => $toHTML2, 'key3' => $toHTML3)
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
        $file = $dataDir .  "/semVerbs.csv";
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
     */
    public function verbssem($dir, Request $request)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/semVerbs.csv";

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
	->add('chooseSem', 'choice',  array(   
    	'choices' => $values))
	->getForm();
#->add('chooseSem', ChoiceType::class,  array(
	## check if form already posted
 	$error = "";
        if ($request->isMethod('POST')) {
             $form->handleRequest($request);
	     $data = $form->getData();
             	
	     foreach ($data as $d){ #var_dump($d);
		## gets data from CSV.php controller
        	$path = $this->container->getParameter('kernel.root_dir');
        	$dataDir = $path . "/../data/$dir";
        	$file = $dataDir .  "/semVerbs.csv";
		$csvFile = file($file);
        	list($result,$nWords) = $this->get('app.utils.csv')->pieSemVerbs($file,$d);
		list($result2,$nWords2,$maxValue) = $this->get('app.utils.csv')->scatterSemVerbs($file,$d);
		#var_dump($result);
		#var_dump($result2);

		$i = count($result);
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
     */
    public function verbsdash($dir)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'V');
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'Semantic Class', 'pos' => 'verbs'));
		return new Response($html);
    }

/**  
     * @Route("/vocabulary/nounsdash/{dir}", name="vocabularyNounsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     */
    public function nounsdash($dir)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'N');
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'Semantic Class', 'pos' => 'nouns'));
		return new Response($html);
    }

/**  
     * @Route("/vocabulary/adjsdash/{dir}", name="vocabularyAdjsDash")
     * returns: ['lemma', 'SemanticClass', 'Frequency' ]
     */
    public function adjsdash($dir)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaSemFreq($file,'A');
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'Semantic Class', 'pos' => 'adjectives'));
		return new Response($html);
    }

/**  
     * @Route("/vocabulary/semdash/{dir}", name="vocabularySemanticDash")
     * returns: ['lemma', 'PoS', 'Frequency' ]
     */
    public function semdash($dir)
    {    
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/sem.csv";
	$csvFile = file($file);

        $result = $this->get('app.utils.csv')->getLemmaPoSFreq($file);
	#var_dump($result);
	
	$toHTML = implode(",",$result[0]);
		$html = $this->container->get('templating')->render(
		'vocabulary/vocabularyDash.html.twig',
		array('key' => $toHTML, 'corpus' => $dir, 'message' => 'PoS tag', 'pos' => ''));
		return new Response($html);
    }



}
