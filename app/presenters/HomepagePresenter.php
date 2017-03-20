<?php

namespace App\Presenters;

use Nette;
use Nette\Utils\DateTime;
use Tracy\Debugger;

class HomepagePresenter extends BasePresenter  {
	
	public function renderDefault()	{
		$this->template->slides = $this->slide->getAll()
											  ->where('show_from IS NULL OR show_from < NOW()')
											  ->where('show_to IS NULL OR show_to > NOW()')
											  ->order('position');
											  
		$this->template->now = new DateTime('now');

		$this->template->news_count = $this->news->getAll()
												 ->where('show_from IS NULL OR show_from < NOW()')
												 ->where('show_to IS NULL OR show_to > NOW()')
												 ->count();
	
		$this->template->max_group_news = $this->news->maxGroupNews();
	
		$this->template->event_news = $this->news->getAll()
												 ->where(['news_type.type' => 'event'])
												 ->where('show_from IS NULL OR show_from < NOW()')
												 ->where('show_to IS NULL OR show_to > NOW()')
												 ->order('date_from ASC');
											  
		$this->template->photo_news = $this->news->getAll()
												 ->where(['news_type.type' => 'photo'])
												 ->where('show_from IS NULL OR show_from < NOW()')
												 ->where('show_to IS NULL OR show_to > NOW()')
												 ->order('date_from DESC');
											  
		$this->template->audio_news = $this->news->getAll()
												 ->where(['news_type.type' => 'audio'])
												 ->where('show_from IS NULL OR show_from < NOW()')
												 ->where('show_to IS NULL OR show_to > NOW()')
												 ->order('date_from DESC');
	}
}
