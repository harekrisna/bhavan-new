<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;

class TeachingPresenter extends BasePresenter {	
	/** @persistent */
    public $category_id;

    public function beforeRender() {
		parent::beforeRender();
		$this->template->article_categories = $this->articleCategory->findAll()
							  										->order('position');
	}

	public function renderCategory($category_id) {
		$this->template->articles = $this->article->findBy(['category_id' => $category_id])
												  ->order('position');
												  
		$category = $this->articleCategory->get($category_id);
		
		if($category->url == 'jak-praktikovat') {
			$this->setView('categoryList');
		}
		
		$this->category_id = $category->id;
		$this->template->category = $category;
	}

	public function renderArticle($article_id) {
		$article = $this->article->get($article_id);
		
		$next = $this->article->findBy(['category_id' => $article->category_id, 'position < ?' => $article->position])
							  ->order('position DESC');

		$prev = $this->article->findBy(['category_id' => $article->category_id, 'position > ?' => $article->position])
							  ->order('position ASC');;
		
		$this->template->previous_article = $prev->fetch();
		$this->template->next_article = $next->fetch();
		$this->template->article = $article;
		$this->template->category = $this->articleCategory->get($article->category_id);
		$this->template->backlinks = [$this->link('category', $article->category_id) => $article->category->title];
	}
}
