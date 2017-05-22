<?php

namespace App\Model;
use Nette;
use Nette\Database;
use Nette\Diagnostics\Debugger;


class Interpret extends Table   {
	protected $tableName = 'audio_interpret'; 

    public function delete($id) {
        $record = $this->getTable()->where(array('id' => $id))
        						   ->fetch();        						   
		if($record->image)
			unlink("./images/interpret_avatars/".$record->image);
		
		return $record->delete();			
    }
    
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