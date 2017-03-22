<?php
namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Application\Responses\FileResponse;
use Nette\Environment;
use FileDownloader\FileDownload;
use Nette\Database\SqlLiteral;

class AudioPresenter extends BasePresenter	{
	/** @var Nette\Http\Session */
    private $session;
	
    /** @var object */
    private $model;
    
    protected function startup()  {
        parent::startup();
        $this->session = $this->getSession('backlinks');
		$this->model = $this->audio;
	}
	
	public function beforeRender() {
		parent::beforeRender();
		$this->template->addFilter('czPlural', function ($count) {
			if($count == 1)
				return $count." přednáška";
			elseif($count >= 2 && $count <= 4)
				return $count." přednášky";
			else 
				return $count." přednášek";
		});										   
	}
	
	public function renderLatest() {
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();		
		$this->template->last_30_days = $this->audio->findAll()
													->where(new SqlLiteral("`time_created` BETWEEN (CURDATE() - INTERVAL 30 DAY) AND (CURDATE() + 0)"))
													->order('time_created DESC');
													
		$this->template->last_60_days = $this->audio->findAll()
													->where(new SqlLiteral("`time_created` BETWEEN (CURDATE() - INTERVAL 60 DAY) AND (CURDATE() - INTERVAL 30 DAY)"))
													->order('time_created DESC');													
		
		$this->template->lectures = $this->audio->findAll()
												->where(new SqlLiteral("`time_created` < CURDATE() - INTERVAL 60 DAY"))
												->order('time_created DESC')
												->limit(20);
												
		$this->session->backlinks = [$this->link('latest') => "Audio"];
	}
	
	public function renderInterprets()	{
		$this->template->interprets = $this->interpret->getAll()
													  ->order('sort_order ASC');	
    }
	
	public function renderYears()	{
		$this->template->years = $this->audio->findAll()
											 ->select('audio_year AS year, COUNT(*) AS count')
											 ->group('audio_year')
											 ->order('audio_year DESC');
	}
	
	public function renderYear($year, $group_by = 'audio_interpret_id') {
		$groups = $this->audio->findBy(['audio_year' => $year])
							  ->group($group_by);
							  
  		$collections = $this->audio->findBy(['audio_year' => $year])
  								   ->select("COUNT('*') AS count, audio_collection_id")
								   ->group('audio_collection_id');
		
		$collections_count = [];								   
		foreach($collections as $collection) {
			$collections_count[$collection->audio_collection_id] = $collection->count;
		}
		
		$this->template->collections_count = $collections_count;
		$lectures = [];
									  
		foreach($groups as $group) {
			$lectures[$group->id] = $this->audio->findBy(['audio_year' => $year, 
														  $group_by => $group->$group_by
														])
												->order('audio_year DESC');
		}
		
		if($group_by == 'book_id') {
			$this->template->unclasified = $this->audio->findBy(['audio_year' => $year])
									   				   ->where('book_id IS NULL AND seminar = ? AND sankirtan = ? AND varnasrama = ?', array(0, 0, 0));
									   				   
				$this->template->seminars = $this->audio->findBy(['audio_year' => $year])
													->where('seminar = ?', 1);
													
			$this->template->sankirtan = $this->audio->findBy(['audio_year' => $year])
													 ->where('sankirtan = ?', 1);
													 
			$this->template->varnasrama = $this->audio->findBy(['audio_year' => $year])
													  ->where('varnasrama = ?', 1);													 

		}
		
		$this->template->backlinks = [$this->link('years') => "Audio"];
		$this->template->groups = $groups;
		$this->template->lectures = $lectures;
		$this->template->year = $year;
		$this->template->group_by = $group_by;
		$this->session->backlinks = [$this->link('years') => "Audio",
								     $this->link('year', $year, $group_by) => "Rok ".$year];
		
		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
   		
   		$this->template->back = "years";				
	}

	public function renderThemes()	{
		$this->template->bg = $this->book->findBy(['abbreviation' => 'BG'])
										 ->fetch();

		$this->template->sb = $this->audio->findAll()
										  ->where("book.abbreviation LIKE 'SB%'")
										  ->count();

		$this->template->cc = $this->audio->findAll()
										  ->where("book.abbreviation LIKE 'CC%'")
										  ->count();

		$this->template->seminar = $this->audio->findBy(['seminar' => 1])
											   ->count();
												
		$this->template->sankirtan = $this->audio->findBy(['sankirtan' => 1])
												 ->count();

		$this->template->varnasrama = $this->audio->findBy(['varnasrama' => 1])
												  ->count();
										 
		$this->template->unclasified = $this->audio->findAll()
												   ->where('book_id IS NULL AND seminar = ? AND sankirtan = ?', array(0, 0))
												   ->count();
	}
	
	public function renderBook($book_id, $group_by = 'audio_interpret_id') {
		$groups = $this->audio->findBy(['book_id' => $book_id])
							  ->group($group_by)
  							  ->order('audio_year DESC');
  							  
  		$collections = $this->audio->findBy(['book_id' => $book_id])
  								   ->select("COUNT('*') AS count, audio_collection_id")
								   ->group('audio_collection_id');
		
		$collections_count = [];								   
		foreach($collections as $collection) {
			$collections_count[$collection->audio_collection_id] = $collection->count;
		}

		$this->template->collections_count = $collections_count;							  	  
		$lectures = [];
									  
		foreach($groups as $group) {
			$lectures[$group->id] = $this->audio->findBy(['book_id' => $book_id, 
			  											  $group_by => $group->$group_by])
												->order('audio_year DESC');
			if($group_by == "audio_interpret_id")
				$lectures[$group->id]->order('chapter ASC, verse ASC');
			else {
				$lectures[$group->id]->order('audio_year DESC, audio_month DESC, audio_day DESC');
			}
				
		}
		
		$this->template->groups = $groups;
		$this->template->lectures = $lectures;
		$book = $this->book->get($book_id);
		$backlinks = [$this->link('themes') => "Audio"];
		
		if(strpos($book->abbreviation, "ŚB") === 0) {
			$backlinks[$this->link('sb')] = "Śrīmad-Bhāgavatam";
		}
		elseif(strpos($book->abbreviation, "CC") === 0) {
			$backlinks[$this->link('cc')] = "Śrī Caitanya-caritāmṛta";
		}
		
		$this->template->backlinks = $backlinks;
		$backlinks[$this->link('book', $book_id, $group_by)] = str_replace(["Śrīmad-Bhāgavatam ", "Śrī Caitanya-caritāmṛta "], ["", ""], $book->title);
		$this->session->backlinks = $backlinks;
		$this->template->book = $book;
		$this->template->group_by = $group_by;
		
   		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}

	/* Pro semináře, sankírtanové lekce a varnasrama */
	public function renderByType($type, $group_by) {
		$groups = $this->audio->findBy([$type => 1])
							  ->group($group_by);
		
		$collections = $this->audio->findBy([$type => 1])
  								   ->select("COUNT('*') AS count, audio_collection_id")
								   ->group('audio_collection_id');
		
		$collections_count = [];								   
		foreach($collections as $collection) {
			$collections_count[$collection->audio_collection_id] = $collection->count;
		}

		$this->template->collections_count = $collections_count;	
							  
		if($group_by == 'audio_year')
			$groups->order('audio_year DESC');							  
							  
		$lectures = [];
		
		if($type == 'sankirtan') {
			$title = "Sankírtanové lekce";
		} 
		elseif($type == 'seminar') {
			$title = "Semináře";
		} 
		elseif($type == 'varnasrama') {
			$title = "Varnášrama a farmy";
		}
		else {
            throw new Nette\Application\BadRequestException;
		}
									  
		foreach($groups as $group) {
			$lectures[$group->id] = $this->audio->findBy([$type => 1,
														  $group_by => $group->$group_by])
												->order('audio_year DESC, audio_month DESC, audio_day DESC');
		}
		
		$this->template->backlinks = [$this->link('themes') => "Audio"];
		$this->session->backlinks = [$this->link('themes') => "Audio",
								     $this->link('byType', $type, $group_by) => $title];

		$this->template->title = $title;									 
		$this->template->groups = $groups;
		$this->template->lectures = $lectures;
		$this->template->type = $type;
		$this->template->group_by = $group_by;
   		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}
	
	public function renderUnclasified($group_by = 'audio_interpret_id') {
		$groups = $this->audio->findAll()
							  ->where('book_id IS NULL AND seminar = ? AND sankirtan = ?', array(0, 0))
							  ->group($group_by);

		$collections = $this->audio->findAll()
							  	   ->where('book_id IS NULL AND seminar = ? AND sankirtan = ?', array(0, 0))
  								   ->select("COUNT('*') AS count, audio_collection_id")
								   ->group('audio_collection_id');
		
		$collections_count = [];								   
		foreach($collections as $collection) {
			$collections_count[$collection->audio_collection_id] = $collection->count;
		}

		$this->template->collections_count = $collections_count;
		  							  
		if($group_by == 'audio_year')
			$groups->order('audio_year DESC');	
			
		$lectures = [];
									  
		foreach($groups as $group) {
			$lectures[$group->id] = $this->audio->findBy([$group_by => $group->$group_by])
												->where('book_id IS NULL AND seminar = ? AND sankirtan = ?', array(0, 0))
												->order('audio_year DESC, audio_month DESC, audio_day DESC');
		}
		
		$backlinks = [$this->link('themes') => "Audio"];
		
		$this->template->backlinks = $backlinks;		
		$backlinks[$this->link('unclasified', $group_by)] = "Nezařazené";
		$this->session->backlinks = $backlinks;
		$this->template->groups = $groups;
		$this->template->lectures = $lectures;
		$this->template->book = false;
		$this->template->back = "themes";
		$this->template->group_by = $group_by;
   		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}
	
	public function renderSb() {
		$this->template->books = $this->book->findAll()
											->where("book.abbreviation LIKE 'SB%'");
											
		$this->template->backlinks = [$this->link('themes') => "Audio"];											
	}
	

	public function renderCc() {
		$this->template->books = $this->book->findAll()	
											->where("book.abbreviation LIKE 'CC%'");
											
		$this->template->backlinks = [$this->link('themes') => "Audio"];
	}	
		
	public function renderInterpret($interpret_id, $group_by = "audio_year") {
		$groups = $this->audio->findBy(['audio_interpret_id' => $interpret_id]);
  		
  		$collections = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
  								   ->select("COUNT('*') AS count, audio_collection_id")
								   ->group('audio_collection_id');
		
		$collections_count = [];								   
		foreach($collections as $collection) {
			$collections_count[$collection->audio_collection_id] = $collection->count;
		}
		
		$this->template->collections_count = $collections_count;
								   
		if($group_by == "audio_year") {
			$group_by_column = "audio_year";
			$groups->order('audio_year DESC')
				   ->group($group_by_column);
		}			
		elseif($group_by == "book_id") {
			$group_by_column = "book_id";
			$groups->order('book.id ASC')
				   ->group($group_by_column);
		}				
			
		$lectures = [];
									  
		foreach($groups as $group) {			
			if($group_by == 'time_created') {
										  		
				$this->template->last_30_days = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
															->where(new SqlLiteral("`time_created` BETWEEN CURDATE() - INTERVAL 30 DAY AND (CURDATE() + 0)"))
															->order('time_created DESC');
													
				$this->template->last_60_days = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
															->where(new SqlLiteral("`time_created` BETWEEN (CURDATE() - INTERVAL 60 DAY) AND (CURDATE() - INTERVAL 30 DAY)"))
															->order('time_created DESC');													
				
				$lectures = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
										->where(new SqlLiteral("`time_created` < CURDATE() - INTERVAL 60 DAY"))
										->order('time_created DESC');
			}
			else {
				$lectures[$group->id] = $this->audio->findBy(['audio_interpret_id' => $interpret_id, 
											  				  $group_by_column => $group->$group_by_column])
											  		->order('audio_year DESC, audio_month DESC, audio_day DESC'); 
			}
		}
		
		
		if($group_by == 'book_id') {
			$this->template->unclasified = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
									   				   ->where('book_id IS NULL AND seminar = ? AND sankirtan = ? AND varnasrama = ?', array(0, 0, 0));
									   				   
			$this->template->seminars = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
													->where('seminar = ?', 1)
													->order('time_created DESC');
													
			$this->template->sankirtan = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
													 ->where('sankirtan = ?', 1)
													 ->order('time_created DESC');
													 
			$this->template->varnasrama = $this->audio->findBy(['audio_interpret_id' => $interpret_id])
													  ->where('varnasrama = ?', 1)
													  ->order('time_created DESC');													 
		}
		
		$interpret = $this->interpret->get($interpret_id);
		$this->template->backlinks = [$this->link('interprets') => "Audio"];
		$this->session->backlinks = [$this->link('interprets') => "Audio",
								     $this->link('interpret', $interpret_id, $group_by) => $interpret->title];
									     
		$this->template->groups = $groups;
		$this->template->lectures = $lectures;
		$this->template->interpret = $interpret;
												   
		$this->template->group_by = $group_by;
		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}
	
	public function renderSingleAudio($id) {
		$audio_mp3 = $this->audio->get($id);

		if (!$audio_mp3)
            throw new Nette\Application\BadRequestException;
		
		$categories = [];
		
		if($audio_mp3->seminar) {
			$categories[] = ["link" => $this->link('byType', 'seminar'),
						  	 "text" => "Semináře"];
		}

		if($audio_mp3->sankirtan) {
			$categories[] = ["link" => $this->link('byType', 'sankirtan'),
						  	 "text" => "Sankírtan"];
		}

		if($audio_mp3->varnasrama) {
			$categories[] = ["link" => $this->link('byType', 'varnasrama'),
						  	 "text" => "Varnášrama"];
		}		

		if($audio_mp3->audio_interpret_id) {
			$categories[] = ["link" => $this->link('interpret', $audio_mp3->audio_interpret_id),
						  	 "text" => $audio_mp3->audio_interpret->title];		
		}
		
		if($audio_mp3->book_id) {
			$categories[] = ["link" => $this->link('book', $audio_mp3->book_id),
						  	 "text" => $audio_mp3->book->abbreviation];
		}
		
		
		$this->template->lecture = $audio_mp3;
		$this->template->categories = $categories;
		$this->template->backlinks = $this->session->backlinks;
		
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}
	
	public function renderAudioCollection($collection_id) {
		$audio_collection = $this->audio->findBy(['audio_collection_id' => $collection_id]);

		if (!$audio_collection)
            throw new Nette\Application\BadRequestException;
			
		$this->template->collection = $this->collection->get($collection_id);
		$this->template->audio_collection = $audio_collection;
		$this->template->backlinks = $this->session->backlinks;
		
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}
	
	public function actionDownloadMp3($id) {
		Debugger::fireLog($id);
		if (!$audio = $this->audio->get($id))
            throw new Nette\Application\BadRequestException;

		$this->sendResponse(new FileResponse("./mp3/".$audio->audio_interpret->mp3_folder."/".$audio->mp3_file));
	}
	
	public function actionIncreaseMp3Playcount($id) {
		$ip = $this->httpRequest->getRemoteAddress();
		$record = $this->audio_playcount->findBy(['audio_id' => $id,
												  'ip' => $ip])
									    ->fetch();
		if($record) {
			$now = new Nette\Utils\DateTime;
			$now = $now->getTimestamp();
			$last_playback = $record->last_playback->getTimestamp();
			if(($now - $last_playback) > 604800) { // lekce byla přehrána déle než před týdnem
				$record->update(['last_playback' => new Nette\Utils\DateTime,
								 'playcount' => new SqlLiteral("playcount + 1")]);
			}
		}	
		else {
			$record = $this->audio_playcount->insert(['audio_id' => $id,
										    		  'ip' => $ip]);
		}	
				
		$this->payload->playcount = $record->playcount;							    							
		$this->sendPayload();
		$this->terminate();
	}
	
	public function actionIncreaseMp3Download($id) {
		$ip = $this->httpRequest->getRemoteAddress();
		$record = $this->audio_downloadcount->findBy(['audio_id' => $id,
												 	  'ip' => $ip])
									   ->fetch();
		if($record) {
			$now = new Nette\Utils\DateTime;
			$now = $now->getTimestamp();
			$last_playback = $record->last_download->getTimestamp();
			if(($now - $last_playback) > 3600) { // lekce byla stažena déle než před hodinou
				$record->update(['last_download' => new Nette\Utils\DateTime,
								 'downloadcount' => new SqlLiteral("downloadcount + 1")]);
			}
		}	
		else {
			$record = $this->audio_downloadcount->insert(['audio_id' => $id,
										    		 	  'ip' => $ip]);
		}	
				
		$this->payload->downloadcount = $record->downloadcount;							    							
		$this->sendPayload();
		$this->terminate();
	}
}
