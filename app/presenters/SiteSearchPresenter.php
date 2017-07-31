<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;

class SiteSearchPresenter extends BasePresenter {

	public function renderAbout() {   
    	$page = $this->page->findBy(['page' => 'about'])
    	                   ->fetch();

        $html = Html::el()->setHtml($page->text);
        $this->template->text = $html;		
	}

	public function renderSearch($q) {  
        $this->setView('search-results'); 
        $query = trim($q);

        $search_articles = $this->search->searchArticles($query);

        $articles = [];

        foreach ($search_articles as $article) {
        	$text = strip_tags($article->text);

			$text = preg_replace("/\b([a-z]*${query}[a-z]*)\b/i","<b>$1</b>",$text);

			//$first_match_pos = strpos(haystack, needle)


        	$articles[] = ['title' => $article->title,
        				   'text' => $text];
       		
        }

        $this->template->articles = $articles;
        $this->template->search_text = $query;
    }   
}
