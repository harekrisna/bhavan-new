<?php

namespace App\Model;
use Nette;
use Nette\Database;
use Nette\Diagnostics\Debugger;


class AudioCollection extends Table   {
	protected $tableName = 'audio_collection'; 
    
	public function getTitleById($id)  {
		$record = $this->get($id);
		if($record)
			return $record->url;
	}
	
	public function getIdByTitle($url)  {
		$record = $this->findBy(array("url" => $url))->fetch();
		if($record) {
			return $record->id;	
		}
	}
}