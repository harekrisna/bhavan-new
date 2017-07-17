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
		$this->model = $this->audio;
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
	
	public function renderInterprets()	{
		$this->template->interprets = $this->musicInterpret->findAll()
														   ->order('position ASC');

		$this->session->main_group = "interprets";													  
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
		$this->template->backlinks = [$this->link('interprets') => "Autoři"];
		$this->session->backlinks = [$this->link('interprets') => "Autoři",
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
		$this->template->main_group = $this->session->main_group;
		$this->template->main_audio_type = "lecture";
		
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}
}
