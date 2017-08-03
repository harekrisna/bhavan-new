<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Tracy\Debugger;
/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {
	protected $actuality;
	protected $slide;
    protected $news;
    protected $interpret;
    protected $galery;
    protected $photo;
    protected $article;
    protected $articleCategory;
    protected $audio;
    protected $audio_playcount;
    protected $audio_downloadcount;
    protected $collection;    
    protected $music;
    protected $musicInterpret; 
    protected $musicAlbum;
    protected $musicGenre;
    protected $musicPlaycount;
    protected $musicDownloadcount;
    protected $book;
    protected $page;
    
    protected $httpRequest;
	    
	protected function startup()	{
		parent::startup();
		$container = $this->presenter->context->getService("container");
		$httpRequest = $container->getByType('Nette\Http\Request');
		$this->httpRequest = $httpRequest;
		$host = $httpRequest->getUrl()->getHost();
		if($host == "www.prabhupadbhavan.cz" || $host == "prabhupadbhavan.cz") {
			$this->redirectUrl("http://www.bhavan.cz");
		}
		
        $this->actuality = $this->context->getService("actuality");
        $this->slide = $this->context->getService("slide");
        $this->news = $this->context->getService("news");
        $this->interpret = $this->context->getService("interpret");
        $this->galery = $this->context->getService("galery");
        $this->photo = $this->context->getService("photo");
        $this->article = $this->context->getService("article");
        $this->articleCategory = $this->context->getService("articleCategory");          
        $this->audio = $this->context->getService("audio");
        $this->audio_playcount = $this->context->getService("audio_playcount");
        $this->audio_downloadcount = $this->context->getService("audio_downloadcount");
        $this->collection = $this->context->getService("audio_collection");
        $this->music = $this->context->getService("music");
        $this->musicInterpret = $this->context->getService("music_interpret");
        $this->musicAlbum = $this->context->getService("music_album");
        $this->musicGenre = $this->context->getService("music_genre");
        $this->musicPlaycount = $this->context->getService("music_playcount");
        $this->musicDownloadcount = $this->context->getService("music_downloadcount");
        $this->book = $this->context->getService("book");
        $this->page = $this->context->getService("page");
	}
  
  	public function beforeRender() {
		$this->template->addFilter('dynamicDate', $this->context->getService("filters")->dynamicDate);
		$this->template->addFilter('verseReadable', $this->context->getService("filters")->verseReadable);
		
		$this->template->actualities = $this->actuality->findAll()
													   ->where('show_from IS NULL OR show_from < NOW()')
													   ->where('show_to IS NULL OR show_to > NOW()')
													   ->order('date_from ASC, id ASC');
		$this->template->back = "";													   
	}

    protected function createComponentSearchForm(){
        $form = new Form();

        $form->addText('search_text', 'Hledat');
        $form->addSubmit('search', 'Hledat');
        $form->setMethod('GET');

        $form->onSuccess[] = array($this, 'search');
        return $form;
    }

    public function search(Form $form, $values) {  
        $this->setView('../Search/search-results'); 
        $search_text = trim($values->search_text);

        $this->template->search_articles = $this->search->searchArticles($search_text);
        $this->template->search_text = $values->search_text;
    }    
}
