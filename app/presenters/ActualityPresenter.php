<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Nette\Diagnostics\Debugger;
use Nette\Utils\Html;

class ActualityPresenter extends BasePresenter
{
	public function renderDefault() {
		$this->template->actualities = $this->actuality->findAll()
													   ->where('show_from IS NULL OR show_from < NOW()')
													   ->where('show_to IS NULL OR show_to > NOW()')
													   ->order('date_from');

	}
	
	public function renderDetail($actuality_id) {
		$this->template->actuality = $this->actuality->get($actuality_id);
		$this->template->backlinks = [$this->link('list') => "Akce"];
	}
	
	public function renderSunday() {
		$page = $this->page->findBy(['page' => 'sunday'])
    	                   ->fetch();
    	                   
        $html = Html::el()->setHtml($page->text);
        
        $this->template->backlinks = [$this->link('default') => "Akce"];
        $this->template->text = $html;		
	}
}
