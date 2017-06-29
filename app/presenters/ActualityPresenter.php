<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Utils\Html;

class ActualityPresenter extends BasePresenter
{
	public function renderList() {
		$actualities = $this->actuality->findAll()
									   ->where('show_from IS NULL OR show_from < NOW()')
									   ->where('show_to IS NULL OR show_to > NOW()')
									   ->order('date_from ASC, id ASC');
        
		$this->template->actualities = $actualities;

		$iter = 0;
		foreach ($this->template->actualities as $next) {
			Debugger::fireLog($iter++." - ".$next->id. ": ".$next->title);
			
		}
	}
	
	public function renderDetail($actuality_id) {
		$selected_actuality = $this->actuality->get($actuality_id);

		$actualities = $this->actuality->findAll()
									   ->where('show_from IS NULL OR show_from < NOW()')
									   ->where('show_to IS NULL OR show_to > NOW()')
									   ->order('date_from ASC, id ASC');
        
		$offset = 0;
		foreach ($actualities as $actuality) {
			if($actuality->id == $selected_actuality->id) 
				break;
			else
				$offset++;
		}

        if($offset == 0) {
			$this->template->prev = false;        	
        }
        else {
			$this->template->prev = $this->actuality->findAll()
													->where('show_from IS NULL OR show_from < NOW()')
													->where('show_to IS NULL OR show_to > NOW()')
													->order('date_from ASC, id ASC')
													->limit(1, $offset-1)
													->fetch();
		}
        
		$this->template->next = $this->actuality->findAll()
												->where('show_from IS NULL OR show_from < NOW()')
												->where('show_to IS NULL OR show_to > NOW()')
												->order('date_from ASC, id ASC')
												->limit(1, $offset+1)
												->fetch();

		$this->template->actuality = $actuality;
		$this->template->backlinks = [$this->link('list') => "Akce"];
	}
	
	public function renderSunday() {
		$page = $this->page->findBy(['page' => 'sunday'])
    	                   ->fetch();
    	                   
        $html = Html::el()->setHtml($page->text);
        
        $this->template->next = $this->actuality->findAll()
									   			->where('show_from IS NULL OR show_from < NOW()')
									   			->where('show_to IS NULL OR show_to > NOW()')
									   			->order('date_from ASC, id ASC')
									   			->fetch();

        $this->template->backlinks = [$this->link('list') => "Akce"];
        $this->template->text = $html;		
	}
}
