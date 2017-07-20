<?php
namespace App\Presenters;

use Nette,
	App\Model;
use Tracy\Debugger;
use App\Extensions\Mobile_Detect;
use Nette\Application\Responses\FileResponse;
use Nette\Database\SqlLiteral;

class MusicPresenter extends BasePresenter	{
	/** @var Nette\Http\Session */
    private $session;
	
    /** @var object */
    private $model;
    
    protected function startup()  {
        parent::startup();
        $this->session = $this->getSession('backlinks');
		$this->model = $this->music;
	}

	public function beforeRender() {
		parent::beforeRender();
		$this->template->addFilter('czPlural', function ($count) {
			if($count == 1)
				return $count." skladba";
			elseif($count >= 2 && $count <= 4)
				return $count." skladby";
			else 
				return $count." skladeb";
		});										   
	}	
	
	public function renderLatest() {
		$this->template->last_30_days = $this->music->findAll()
													->where(new SqlLiteral("`time_created` BETWEEN (CURDATE() - INTERVAL 30 DAY) AND (CURDATE() + 1)"))
													->order('time_created DESC');
													
		$this->template->last_60_days = $this->music->findAll()
													->where(new SqlLiteral("`time_created` BETWEEN (CURDATE() - INTERVAL 60 DAY) AND (CURDATE() - INTERVAL 30 DAY)"))
													->order('time_created DESC');													
		
		$this->template->records = $this->music->findAll()
											   ->where(new SqlLiteral("`time_created` < CURDATE() - INTERVAL 60 DAY"))
											   ->order('time_created DESC')
											   ->limit(20);
												
		$this->session->backlinks = [$this->link('latest') => "Nejnovější"];
		$this->session->main_group = "latest";
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}


	public function renderInterprets()	{
		$this->template->interprets = $this->musicInterpret->findAll()
														   ->order('position ASC');

		$this->session->main_group = "interprets";													  
    }
    
	public function renderInterpret($interpret_id, $group_by = "music_year") {
		$groups = $this->music->findBy(['music_interpret_id' => $interpret_id]);
  		
  		$albums = $this->music->findBy(['music_interpret_id' => $interpret_id])
  							  ->select("COUNT('*') AS count, music_album_id")
							  ->group('music_album_id');
		
		$albums_count = [];						   
		foreach($albums as $album) {
			$albums_count[$album->music_album_id] = $album->count;
		}
		
		$this->template->albums_count = $albums_count;
		
		$records = [];

		if($group_by == "music_year") {
			$groups->order('music_year DESC')
				   ->group('music_year');

			foreach($groups as $group) {	
				$records[$group->id] = $this->music->findBy(['music_interpret_id' => $interpret_id, 
											  				 'music_year' => $group->music_year])
											  	   ->order('music_year DESC, music_month DESC, music_day DESC'); 
			}				   
		}			
		
		if($group_by == "music_genre_id") {
			$groups->order('music_genre_id DESC')
				   ->group('music_genre_id');
			
			foreach($groups as $group) {	
				$records[$group->id] = $this->music->findBy(['music_interpret_id' => $interpret_id, 
											  				 'music_genre_id' => $group->music_genre_id])
											  	   ->order('music_year DESC, music_month DESC, music_day DESC'); 
			}
		}
		
		if($group_by == "alphabetical") {
			$groups = [];
			$records = $this->music->findBy(['music_interpret_id' => $interpret_id])
									->order('title');

			foreach ($records as $record) {
				$first_letter = substr($record->title, 0, 2); // UTF8 literal
				if(strlen(utf8_decode($first_letter)) == 2) {
					$first_letter = substr($first_letter, 0, 1);
				}

				if(intval($first_letter)) {
					$groups['1-9'][] = $record;
				}
				elseif($first_letter == "Ś") {
					$groups["Š"][] = $record;
				}
				else {
					$groups[$first_letter][] = $record;
				}
			}
		}
		
		if($group_by == "time_created") {
			$this->template->last_30_days = $this->music->findBy(['music_interpret_id' => $interpret_id])
														->where(new SqlLiteral("`time_created` BETWEEN CURDATE() - INTERVAL 30 DAY AND (CURDATE() + 1)"))
														->order('time_created DESC');
												
			$this->template->last_60_days = $this->music->findBy(['music_interpret_id' => $interpret_id])
														->where(new SqlLiteral("`time_created` BETWEEN (CURDATE() - INTERVAL 60 DAY) AND (CURDATE() - INTERVAL 30 DAY)"))
														->order('time_created DESC');													
			
			$records = $this->music->findBy(['music_interpret_id' => $interpret_id])
									->where(new SqlLiteral("`time_created` < CURDATE() - INTERVAL 60 DAY"))
									->order('time_created DESC');
		}
		
		$interpret = $this->musicInterpret->get($interpret_id);
		$this->template->backlinks = [$this->link('interprets') => "Autoři"];
		$this->session->backlinks = [$this->link('interprets') => "Autoři",
								     $this->link('interpret', $interpret_id, $group_by) => $interpret->title];
									     
		$this->template->groups = $groups;
		$this->template->records = $records;
		$this->template->interpret = $interpret;
												   
		$this->template->group_by = $group_by;
		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}

	public function renderYears()	{
		$this->template->years = $this->music->findAll()
											 ->select('music_year AS year, COUNT(*) AS count')
											 ->group('music_year')
											 ->order('music_year DESC');

		$this->session->main_group = "years";
	}
	
	public function renderYear($year, $group_by = 'music_interpret_id') {
		$groups = $this->music->findBy(['music_year' => $year])
							  ->group($group_by);
							  
  		$albums = $this->music->findBy(['music_year' => $year])
  							  ->select("COUNT('*') AS count, music_album_id")
							  ->group('music_album_id');
		
		$albums_count = [];								   
		foreach($albums as $album) {
			$albums_count[$album->music_album_id] = $album->count;
		}
		
		$this->template->albums_count = $albums_count;

		$records = [];

		if($group_by == "music_interpret_id") {
			$groups->order('music_year DESC')
				   ->group('music_interpret_id');

			foreach($groups as $group) {
				$records[$group->id] = $this->music->findBy(['music_year' => $year, 
															 'music_interpret_id' => $group->music_interpret_id
															]);
			}
		}			
		
		if($group_by == "music_genre_id") {
			$groups->order('music_genre_id DESC')
				   ->group('music_genre_id');

			foreach($groups as $group) {
				$records[$group->id] = $this->music->findBy(['music_year' => $year, 
															 'music_genre_id' => $group->music_genre_id
															]);
			}				   
		}

		if($group_by == "alphabetical") {
			$groups = [];
			$records = $this->music->findBy(['music_year' => $year])
									->order('title');

			foreach ($records as $record) {
				$first_letter = substr($record->title, 0, 2); // UTF8 literal
				if(strlen(utf8_decode($first_letter)) == 2) {
					$first_letter = substr($first_letter, 0, 1);
				}

				if(intval($first_letter)) {
					$groups['1-9'][] = $record;
				}
				elseif($first_letter == "Ś") {
					$groups["Š"][] = $record;
				}
				else {
					$groups[$first_letter][] = $record;
				}
			}
		}

		$this->template->backlinks = [$this->link('years') => "Roky"];
		$this->template->groups = $groups;
		$this->template->records = $records;
		$this->template->year = $year;
		$this->template->group_by = $group_by;
		$this->session->backlinks = [$this->link('years') => "Roky",
								     $this->link('year', $year, $group_by) => "Rok ".$year];
		
		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}

	public function renderGenres()	{
		$this->template->genres = $this->musicGenre->findAll()
											  ->order('position');

		$this->session->main_group = "genres";
	}	

	public function renderGenre($genre_id, $group_by = "music_year") {
		$genre = $this->musicGenre->get($genre_id);

		$groups = $this->music->findBy(['music_genre_id' => $genre_id])
							  ->group($group_by);
		
		$albums = $this->music->findBy(['music_genre_id' => $genre_id])
						   	  ->select("COUNT('*') AS count, music_album_id")
							  ->group('music_album_id');
		
		$albums_count = [];								   
		foreach($albums as $album) {
			$albums_count[$album->music_album_id] = $album->count;
		}

		$this->template->albums_count = $albums_count;	
							  
		if($group_by == 'music_year')
			$groups->order('music_year DESC');							  
							  
		$records = [];
									  
		foreach($groups as $group) {
			$records[$group->id] = $this->music->findBy(['music_genre_id' => $genre_id,
														 $group_by => $group->$group_by])
											   ->order('music_year DESC, music_month DESC, music_day DESC');
		}
		
		$this->template->backlinks = [$this->link('genres') => "Žánr"];
		$this->session->backlinks = [$this->link('genres') => "Žánr",
								     $this->link('genre', $genre_id, $group_by) => $genre->title];
		
		$this->template->genre = $genre;
		$this->template->groups = $groups;
		$this->template->records = $records;
		$this->template->group_by = $group_by;
   		$detect = new Mobile_Detect;
   		$this->template->isMobile = $detect->isMobile();
	}

	public function renderSingleAudio($id) {
		$audio_mp3 = $this->music->get($id);

		if (!$audio_mp3)
            throw new Nette\Application\BadRequestException;
		
		$categories = [];
		
		if($audio_mp3->music_interpret_id) {
			$categories[] = ["link" => $this->link('interpret', $audio_mp3->music_interpret_id),
						  	 "text" => $audio_mp3->music_interpret->title];		
		}
		
		$this->template->record = $audio_mp3;
		$this->template->categories = $categories;
		$this->template->backlinks = $this->session->backlinks;
		$this->template->main_group = $this->session->main_group;
		$this->template->main_audio_type = "music";
		
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}

	public function renderAlbum($album_id) {
		$records = $this->music->findBy(['music_album_id' => $album_id]);

		if (!$records)
            throw new Nette\Application\BadRequestException;
			
		$this->template->album = $this->musicAlbum->get($album_id);
		$this->template->records = $records;
		$this->template->backlinks = $this->session->backlinks;
		$this->template->main_group = $this->session->main_group;
		$this->template->main_audio_type = "music";
		
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}	

	public function actionIncreaseMp3Playcount($id) {
		$ip = $this->httpRequest->getRemoteAddress();
		$record = $this->musicPlaycount->findBy(['music_id' => $id,
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
			$record = $this->musicPlaycount->insert(['music_id' => $id,
										    		  'ip' => $ip]);
		}	
				
		$this->payload->playcount = $record->playcount;							    							
		$this->sendPayload();
		$this->terminate();
	}
	
	public function actionIncreaseMp3Download($id) {
		$ip = $this->httpRequest->getRemoteAddress();
		$record = $this->musicDownloadcount->findBy(['music_id' => $id,
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
			$record = $this->musicDownloadcount->insert(['music_id' => $id,
										    		 	  'ip' => $ip]);
		}	
				
		$this->payload->downloadcount = $record->downloadcount;							    							
		$this->sendPayload();
		$this->terminate();
	}	
}
