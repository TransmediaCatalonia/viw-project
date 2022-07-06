<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use App\Utils\Metadata;

class DefaultController extends AbstractController
{
    protected $metadata;

    public function __construct(
        Metadata $metadata
    )
    {
        $this->metadata = $metadata;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $path = $this->getParameter('kernel.project_dir');

        ## reads records.xml and lists records available
        $recordsFile = $path . "/data/records.xml";
        $records = $this->metadata->metadata($recordsFile);
//	$records = $this->get('app.utils.metadata')->metadata($recordsFile);

        $corpora = array();
        foreach ($records->corpus as $c) {
            $id = (string)$c['id'];
            $corpora[$id] = (string)$c->description;
        }
        #var_dump($corpora);


        $help = "You can choose one corpus to work with ...";
        return $this->render(
            'default/indexCorpus.html.twig',
            array('corpus' => $corpora, 'help' => $help)
        );

    }

    /**
     * @Route("/corpus/{corpus_id}/", name="_corpusAction")
     */
    public function corpusAction($corpus_id)
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/";
        $corpus_dir = $dataDir . $corpus_id;
        $recordsFile = $dataDir . "records.xml";

        ## reads records.xml and looks for relevant record/info to be displayed (file_id). 
        $records = $this->metadata->metadata($recordsFile);

        ##gets metadata for corpus
        $metadata = array();
        $language;
        foreach ($records->corpus as $r) {
            $att = 'id';
            $id = (string)$r->attributes()->$att;
            if ($id == $corpus_id) {
                $metadata = $r;
                $language = (string)$r->language;
            }
        }

        ## looks for corpus' files
        $files = array();
        foreach ($records->record as $r) {
            if ($r->corpus == $corpus_id) {
                #array_push($files,$r->source);
                $id = (string)$r['id'];
                $files[$id] = (string)$r->source;
            }
        }
        #var_dump($files);

        ## looks for files in dir ($file_id) to generate 'visualizations'

        $finder = new Finder();
        $finder->depth('== 0');
        $finder->files()->in($corpus_dir);

        #var_dump($dir);
        #var_dump($langDir);

        # source files to be 'displayed'
        $words = null;
        $sentences = null;
        $sem = null;
        $hits_timeline = null;
        $corpus = null;
        $corpusfile = null;
        $similarity = null;

        foreach ($finder as $f) {
            if (preg_match("/countWords.txt$/i", $f->getRelativePathname())) {
                $words = $f->getRelativePathname();
            }
            if (preg_match("/corpus.txt$/i", $f->getRelativePathname())) {
                $corpusfile = $f->getRelativePathname();
            }


            if (preg_match("/sem.csv$/i", $f->getRelativePathname())) {
                $sem = $f->getRelativePathname();
            }
            if (preg_match("/Hits-All.txt$/i", $f->getRelativePathname())) {
                $hits_timeline = $f->getRelativePathname();
            }
            if (preg_match("/similarity.csv$/i", $f->getRelativePathname())) {
                $similarity = $f->getRelativePathname();
            }
        }

        if ($language == "EN") {
            $filmic = "Filmic_English";
        }
        if ($language == "ES") {
            $filmic = "Filmic_Spanish";
        }
        if ($language == "CA") {
            $filmic = "Filmic_Catalan";
        }


        return $this->render(
            'default/metadataCorpus.html.twig',
            array('metadata' => $metadata,
                'words' => $words,
                'corpus' => $corpus_id,
                'corpusfile' => $corpusfile,
                'similarity' => $similarity,
                'sem' => $sem,
                'hits_timeline' => $hits_timeline,
                'files' => $files,
                'filmic' => $filmic)
        );
    }


    /**
     * @Route("/metadata/{dir_id}/{file_id}", name="_metadata")
     */
    public function fileAction($dir_id, $file_id)
    {
        $path = $this->getParameter('kernel.project_dir');
        $dataDir = $path . "/data/";
        $recordsFile = $dataDir . "records.xml";

        ## reads records.xml and looks for relevant record/info to be displayed (file_id). 
        $records = $this->metadata->metadata($recordsFile);
        $title = "";        # file's title
        $record = "";        # all metadata record
        $corpus = "";        # file's corpus
        $source = $dir_id . "/" . $file_id;
        foreach ($records->record as $r) {
            if ($r->source == $source) {
                $title = $r['id'];
                $record = $r;
                $corpus = $r->corpus;
            }
        }


        ## looks files in dir to generate 'visualizations'

        # file's dir
        $subdir = $dir_id . "/" . $file_id;
        $dir = $dataDir . $dir_id . "/" . $file_id;
        $finder = new Finder();
        $finder->depth('== 0');
        $finder->files()->in($dir);

        # corpu's dir
        $langDir = $dataDir . $dir_id;
        $finder2 = new Finder();
        $finder2->depth('== 0');
        $finder2->files()->in($langDir);

        #var_dump($dir);
        #var_dump($langDir);

        # source files to be 'displayed'
        $html = null;
        $eaf = null;
        $hits = null;
        $stats = null;
        $words = null;
        $verbs = null;
        $sentences = null;

        # path = ./data
        foreach ($finder as $f) {
            if (preg_match("/html$/i", $f->getRelativePathname())) {
                $html = $f->getRelativePathname();
            }

            if (preg_match("/eaf$/i", $f->getRelativePathname())) {
                $eaf = $f->getRelativePathname();
            }

            if (preg_match("/countWords.txt$/i", $f->getRelativePathname())) {
                $words = $f->getRelativePathname();
            }

        }

        # path = ./data
        foreach ($finder2 as $f) {
            if (preg_match("/sem.csv$/i", $f->getRelativePathname())) {
                $verbs = $f->getRelativePathname();
            }
            if (preg_match("/Hits-All.txt$/i", $f->getRelativePathname())) {
                $hits = $f->getRelativePathname();
            }
        }


        return $this->render(
            'default/metadata.html.twig',
            array('record' => $record,        # all metadata stuff from record
                'title' => $title,        # file's title
                'subdir' => $subdir,        # $subdir = $dir_id . "/" . $file_id;
                'corpus' => $corpus,        # file's corpus
                'langdir' => $dir_id,        # corpus dir
                'html' => $html,
                'eaf' => $eaf,
                'hits' => $hits,
                'words' => $words,
                'verbs' => $verbs,        # semverb.csv (only for if exists purposes)
                'sentences' => "sentences.txt")
        );
    }
}
