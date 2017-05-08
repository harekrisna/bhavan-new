<?php

namespace App\Presenters;

use Nette,
	App\Model;

class TeachingPresenter extends BasePresenter {	
	public function renderArticle($id) {
		$this->setView($id);
	    $this->template->id = $id;
  		$this->template->backlinks = [$this->link('list') => "Články"];
	}
}
