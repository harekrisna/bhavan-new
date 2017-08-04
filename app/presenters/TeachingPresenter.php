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
		$this->template->articles = $this->article->findBy(['category_id' => $category_id,
															'article_id' => NULL])
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

		$category_articles = $this->article->findBy(['category_id' => $article->category_id,
													 'article_id' => $article->article_id])
										   ->order('position');

		$child_articles = $this->article->findBy(['article_id' => $article_id])
										->order('position');
		
		$next = $this->article->findBy(['category_id' => $article->category_id, 
										'article_id' => $article->article_id, 
										'position > ?' => $article->position])
							  ->order('position ASC');

		$prev = $this->article->findBy(['category_id' => $article->category_id, 
										'article_id' => $article->article_id,
										'position < ?' => $article->position])
							  ->order('position DESC');;
		
		$this->template->category_articles = $category_articles;
		$this->template->child_articles = $child_articles;
		$this->template->previous_article = $prev->fetch();
		$this->template->next_article = $next->fetch();
		$this->template->article = $article;
		$this->template->category = $this->articleCategory->get($article->category_id);

		$backlinks = [];
		while($article->article_id != NULL) {
			$article = $this->article->get($article->article_id);
			$backlinks = [$this->link('article', $article->id) => $article->title] + $backlinks;
		}

		
		$backlinks = [$this->link('category', $article->category_id) => $article->category->title] + $backlinks;
		$this->template->backlinks = $backlinks;
	}
}
