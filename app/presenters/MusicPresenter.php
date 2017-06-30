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
	
	public function renderMusic() {
		$detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
	}	
}
