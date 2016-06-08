<?php
// src/AppBundle/Controller/SimilarityController.php
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

  */

class SimilarityController extends Controller
{
 
/**  
     * @Route("/similarity/{dir}", name="similarity")
     * 
     */
public function similarity($dir)
    {    
        ## gets data from CSV.php controller
        $path = $this->container->getParameter('kernel.root_dir');
        $dataDir = $path . "/../data/$dir";
        $file = $dataDir .  "/similarity.csv";


	$rows = array_map('str_getcsv', file($file));
	$header = array_shift($rows);
	$csv = array();

	$first = implode("','", $header);
	$first = "['" . $first . "']"; 
	array_push($csv,$first);

	foreach ($rows as $row) {
		$one = array_shift($row);
		$two = implode(",", $row);
		$item = "['". $one . "'," . $two . "]";
		array_push($csv, $item);
	}
	
	$toHTML  = implode(",", $csv);
	

        $html = $this->container->get('templating')->render(
            'similarity/similarity.html.twig',
            array('key' => $toHTML, 'dir' => $dir)
        );

        return new Response($html);

    }

}
