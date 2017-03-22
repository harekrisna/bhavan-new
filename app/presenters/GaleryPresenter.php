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
		$this->template->galeries = $this->galery->getAll()
												 ->where('active IS TRUE')
												 ->order('galery_year DESC, galery_month DESC, galery_day DESC');
	}
	
	public function renderPhotos($galery_id) {
		$galery = $this->galery->get($galery_id);
		
		/*
		if(!$galery->active)
            throw new Nette\Application\BadRequestException;
		*/
		
		$this->template->galery = $galery;
		
		$this->template->photos = $this->photo->getAll()
											  ->where(["galery_id" => $galery_id])
											  ->order("position ASC");
											  
  		$this->template->backlinks = [$this->link('galeries') => "Foto"];
	}
}
