<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Utils\Html;

class AboutPresenter extends BasePresenter {

	public function renderAbout() {   
    	$page = $this->page->findBy(['page' => 'about'])
    	                   ->fetch();

        $html = Html::el()->setHtml($page->text);
        $this->template->text = $html;		
	}
}
