<?php

namespace App\Presenters;

use Nette,
	App\Model;

class TeachingPresenter extends BasePresenter {	
	public function renderCategory($id) {
		$template->articles = $this->article->findBy(['category_id' => $id]);
	}
}
