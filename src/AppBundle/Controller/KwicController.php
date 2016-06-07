<?php
// src/AppBundle/Controller/KwicController.php
namespace AppBundle\Controller;
use AppBundle\Utils\Kwic;

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

class KwicController extends Controller
{

/**  
     * @Route("/kwic", name="kwicHome")
     */
     ## simple search facility for Corpus. Lists corpora available.
    public function concordancerHome()
    {    
        
	$path = $this->container->getParameter('kernel.root_dir');
        $indexFile = $path . "/../data/records.xml";
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
        	foreach($allCorpus as $l){
			array_push($corp,trim(($l->nodeValue)));
        	}
		$corpus = array_unique($corp);
	}

		$html = $this->container->get('templating')->render(
		'kwic/kwicHome.html.twig',
		array('corpus' => $corpus, 'title' => ""));
		return new Response($html);
    }


     
/**  
     * @Route("/kwic/{corpus}", name="kwic")
     */
     ## simple search facility for Corpus (searches on 'corpus.txt' file with format: [utterance, source.file]) 
     ## allows for string search on corpus.txt file. Displays matching utterances (together with link to source file)
    public function concordancer($corpus, $word, Request $request)
    {    
        
	## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$corpus";
        $file = $dataDir .  "/corpus.txt";	

	## creates form with textarea
	$defaultData = array();
    	$form = $this->createFormBuilder($defaultData)
	->add('word', 'text')
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
             	
	     foreach ($data as $d){ #var_dump($d); 

		$content = file_get_contents($file);
		$lines = explode(PHP_EOL, $content);
		$values = array();
		$files = array();
		foreach ($lines as $l) { 
			$string = explode("\t", $l);  
      	 		if (count($string) > 1) {
				$path = explode("/",$string[1]);
				array_pop($path);
                      	  	$link = implode("/",$path);
				#var_dump($string);print "<br/>";
				$result = $this->get('app.utils.kwic')->kwic($d,$string[0]);
				if ($result != "") {
					array_push($values, array($result,$link));
					if( !in_array($link,$files)) array_push($files,$link);
				}
			}
		}
		#var_dump($values);
		$c = count($values);
		$cc = count($files);
		$title = "'$d' was found in $c utterances in $cc files";
		$html = $this->container->get('templating')->render(
		'kwic/kwic.html.twig',
		array('form' => $form->createView(), 'error' => $error, 'result' => $values, 'corpus' => $corpus, 'title' => $title));
		return new Response($html);
 		}
	}
	else {
		$html = $this->container->get('templating')->render(
		'kwic/kwic.html.twig',
		array('form' => $form->createView(), 'error' => $error, 'result' => "", 'corpus' => $corpus, 'title' => ""));
		return new Response($html);
	}
    }

/**  
     * @Route("/kwic/corpus/{dir}/{corpus}", name="kwiccorpus")
     */
     ## simple search facility for file (sentences.txt file).
     ## displays sentences.txt file and allows for string search. Displays text with matching strings in red
    public function showcorpus($dir,$corpus, $word, Request $request)
    {    
        #var_dump($corpus);
	## 
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir/$corpus";
        $file = $dataDir .  "/data/sentences.txt";

	## creates form with textarea
	$defaultData = array();
    	$form = $this->createFormBuilder($defaultData)
	->add('word', 'text')
	->getForm();

	$content = str_replace("###.","<br/>",file_get_contents($file));
	## check if form already posted
 	$error = "";
        if ($request->isMethod('POST')) {
             $form->handleRequest($request);
	     $data = $form->getData();
             	
	     foreach ($data as $d){ #var_dump($d); 

		$result = $this->get('app.utils.kwic')->kwicCorpus($d,$content);
	
		$title = "Looking for '$d' in $corpus'";
		$html = $this->container->get('templating')->render(
		'kwic/kwicCorpus.html.twig',
		array('form' => $form->createView(), 'error' => $error, 'result' => $result, 'dir' => $dir, 'corpus' => $corpus, 'title' => $title));
		return new Response($html);
 		}
	}
	else {
		$html = $this->container->get('templating')->render(
		'kwic/kwicCorpus.html.twig',
		array('form' => $form->createView(), 'error' => $error, 'result' => $content, 'dir' => $dir, 'corpus' => $corpus, 'title' => ""));
		return new Response($html);
	}
    }
}

