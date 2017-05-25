<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;

class TeachingPresenter extends BasePresenter {	
	public function renderCategory($category_id) {
		$this->template->articles = $this->article->findBy(['category_id' => $category_id]);
	}
}
