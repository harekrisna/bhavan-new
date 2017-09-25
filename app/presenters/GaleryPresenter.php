<?php

namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Utils\Finder;
use Nette\Utils\Image;

class GaleryPresenter extends BasePresenter
{	
	public function renderGaleries()	{
		$this->template->galeries = $this->galery->getList();
	}
	
	public function renderPhotos($galery_id) {
		$selected_galery = $this->galery->get($galery_id);
		
		$galeries = $this->galery->getList();
		        
		$offset = 0;
		foreach ($galeries as $galery) {
			if($galery->id == $selected_galery->id) 
				break;
			else
				$offset++;
		}
		
		if($offset == 0) {
			$this->template->prev = false;        	
        }
        else {
			$this->template->prev = $this->galery->getList()
												 ->limit(1, $offset-1)
												 ->fetch();
		}
        
		$this->template->next = $this->galery->getList()
											 ->limit(1, $offset+1)
											 ->fetch();
		
		$this->template->galery = $selected_galery;
		
		$this->template->photos = $this->photo->getAll()
											  ->where(["galery_id" => $galery_id])
											  ->order("position ASC");
											  
  		$this->template->backlinks = [$this->link('galeries') => "Foto"];
	}
}
