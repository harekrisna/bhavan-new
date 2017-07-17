<?php

namespace App\Presenters;

use Nette,
	App\Model;
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
    protected $musicInterpret;    
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
        $this->musicInterpret = $this->context->getService("music_interpret");
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
}
