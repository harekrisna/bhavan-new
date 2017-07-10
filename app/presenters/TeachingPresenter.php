<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;

class TeachingPresenter extends BasePresenter {	
	public function renderCategory($category_id) {
		$this->template->articles = $this->article->findBy(['category_id' => $category_id])
												  ->order('position');
												  
		$category = $this->articleCategory->get($category_id);
		if($category->url == 'jak-praktikovat') {
			$this->setView('jak-praktikovat');
		}
		
		$this->template->category = $category;
	}

	public function renderArticle($article_id) {
		$article = $this->article->get($article_id);
		
		$prev = $this->article->findBy(['category_id' => $article->category_id])
							  ->where('created <= ? AND id < ?', $article->created, $article->id)
							  ->order('created DESC, id DESC');
		
		$next = $this->article->findBy(['category_id' => $article->category_id])
							  ->where('created >= ? AND id > ?', $article->created, $article->id)
							  ->order('created ASC, id ASC');
		
		$this->template->previous_article = $prev->fetch();
		$this->template->next_article = $next->fetch();
		$this->template->article = $article;
		$this->template->category = $this->articleCategory->get($article->category_id);
	}
}
