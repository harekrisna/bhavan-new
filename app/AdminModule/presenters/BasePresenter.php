<?php

namespace App\AdminModule\Presenters;


use Nette,
	App\Model;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    protected $common;
    protected $news;
    protected $newsType;
    protected $actuality;
    protected $slide;
    protected $article;
    protected $articleCategory;
    protected $galery;
    protected $photo;
    protected $interpret;
    protected $collection;
    protected $music;
    protected $musicInterpret;
    protected $audio;
    protected $book;
    protected $page;

    protected function startup()	{
		parent::startup();
        $this->common = $this->context->getService("common");
        $this->news = $this->context->getService("news");
        $this->newsType = $this->context->getService("newsType");
        $this->actuality = $this->context->getService("actuality");
        $this->slide = $this->context->getService("slide");
        $this->article = $this->context->getService("article");
        $this->articleCategory = $this->context->getService("articleCategory");        
        $this->galery = $this->context->getService("galery");
        $this->photo = $this->context->getService("photo");
        $this->interpret = $this->context->getService("interpret");
        $this->music = $this->context->getService("music");
        $this->musicInterpret = $this->context->getService("music_interpret");
        $this->collection = $this->context->getService("audio_collection");
        $this->audio = $this->context->getService("audio");
        $this->book = $this->context->getService("book");
        $this->page = $this->context->getService("page");

		$this->template->addFilter('dynamicDateSortable', $this->context->getService("filters")->dynamicDateSortable);
		$this->template->addFilter('verseReadable', $this->context->getService("filters")->verseReadable);
				
		\RadekDostal\NetteComponents\DateTimePicker\DateTimePicker:: register();
		\RadekDostal\NetteComponents\DateTimePicker\DatePicker:: register();
    }

    public function beforeRender() {		
        $this->template->menu = array();
    }
   	
    public function handleSignOut() {
        $this->getUser()->logout();
		$this->redirect('Sign:in');
  }
  
}

