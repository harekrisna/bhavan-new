<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Utils\Html;
use Tracy\Debugger;

class ContactPresenter extends BasePresenter {

	public function renderContact() {   
    	$page = $this->page->findBy(['page' => 'contact'])
    	                   ->fetch();

    	Debugger::fireLog($page);

        $html = Html::el()->setHtml($page->text);
        $this->template->text = $html;		
	}
}
