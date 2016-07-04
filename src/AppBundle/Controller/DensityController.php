<?php
// src/AppBundle/Controller/DensityController.php
namespace AppBundle\Controller;


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

class DensityController extends Controller
{

    /**
     * @Route("/density", name="density")
     */
    public function listDensityFiles(Request $request)
    {
        
	$path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data";
        	
	## reads records.xml and gets records available into $files0
	$recordsFile = $path . "/../data/records.xml"; 
	$records = $this->get('app.utils.metadata')->metadata($recordsFile);
	$files0 = array();
	foreach ($records->record as $r) { 
	   $id = $r['id'];
    	   //$pair[0]=[$id];
	   $keys = array($id);
	   $pair = array_fill_keys($keys, $id);
	   array_push($files0, $pair);
	}


	## creates form with $files0 ## symphony3.0: 	->add('chooseTwoFiles', ChoiceType::class,  array(
	$defaultData = array();
    	$form = $this->createFormBuilder($defaultData)
	->add('chooseTwoFiles', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',  array(
    	'choices' => array($files0),
	'multiple' => true,
        'expanded' => true,
    	'choices_as_values' => true))
	->getForm();

	## check if form already posted
 	$error = "";
        if ($request->isMethod('POST')) {
             $form->handleRequest($request);
		$data = $form->getData();
          
	    foreach ($data as $d){
		if (count($d) > 2) { 
			$error = "You can only choose TWO files!!";
			$html = $this->container->get('templating')->render(
		    	'density/density.html.twig',
		    	array('form' => $form->createView(), 'error' => $error));
	       		return new Response($html);
		}
		elseif (count($d) < 2) { 
			$error = "You need TWO files!!";
			$html = $this->container->get('templating')->render(
			'density/density.html.twig',
			array('form' => $form->createView(), 'error' => $error));
			return new Response($html);
		}
	        else {
		return $this->redirectToRoute('density_graph', array('file1' => $d[0], 'file2' => $d[1])); }
		}
        }

	else {
		$html = $this->container->get('templating')->render(
		'density/density.html.twig',
		array('form' => $form->createView(), 'error' => $error));
		return new Response($html);
	}

    }

    
    /**
     * @Route("/density/graph/{file1}/{file2}", name="density_graph")
     */
    public function graph($file1,$file2)
    {

	$path = $this->container->getParameter('kernel.root_dir');
        $indexFile = $path . "/../data/records.xml";
	$dataDir = $path . "/../data";

	if (file_exists($indexFile)) {
		// build domXpath
		$doc = new \DOMDocument();
		$doc->preserveWhiteSpace = false; 
		$doc->loadXml(file_get_contents($indexFile));
		
		$xpath = new \DOMXpath($doc);

		// get results  
		$files = array();
		$query1 = "//record[@id='" . $file1 ."']";
		$query2 = "//record[@id='" . $file2 ."']";
		//var_dump($query);
		$source1 = $xpath->query($query1);
		$source2 = $xpath->query($query2);
		foreach ($source1 as $r){
			$sources = $r->getElementsByTagName( "corpus" );
			$s1 = $sources->item(0)->nodeValue;
			#print $s1;
		}
		foreach ($source2 as $r){
			$sources = $r->getElementsByTagName( "corpus" );
			$s2 = $sources->item(0)->nodeValue;
			#print $s2;
		}
	}


        $data1 = $dataDir . "/" .  $s1 . "/Hits-All.txt";
	$data2 = $dataDir . "/" .  $s2 . "/Hits-All.txt";
	
	$csvFile1 = file($data1);
        $csvFile2 = file($data2);

        $maxValues = array();
	$rows = array();
	foreach ($csvFile1 as $l) {
	    $line = trim($l);
            $data = str_getcsv($line, "\t");
	    $file = substr($data[4], 0, -4); 
	    if ($file == $file1){
            $row = "";
	    $string = preg_replace('/[^A-Za-z0-9\- ,]/', '', $data[3]);
	    $text = "'" . $string . "'" ;
	    $row = '[' . $data[0] . ',' . $data[2]. ',' . $text . ',' .'null'. ',' .'null]';
	    array_push($rows,$row);
	    array_push($maxValues,$data[0]);
	    }
        }

	foreach ($csvFile2 as $l) {
	    $line = trim($l);
            $data = str_getcsv($line, "\t");
	    $file = substr($data[4], 0, -4);
	    if ($file == $file2){
            $row = "";
	    $string = preg_replace('/[^A-Za-z0-9\- ,]/', '', $data[3]);
	    $text = "'" . $string . "'" ;
            $row = '[' . $data[0] . ', null, null, ' . $data[2]. ',' . $text .']';
	    array_push($rows,$row);
	    }
        }	   
 	$maxValue = end($maxValues) + 50000;
	$data = implode(",",$rows);
    
        $html = $this->container->get('templating')->render(
            'density/graph.html.twig',
            array('key' => $data, 'lang1' => $file1, 'lang2' => $file2, 'maxValue' => $maxValue)
        );

        return new Response($html);

    }

}
