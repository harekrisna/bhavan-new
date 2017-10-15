<?php

namespace App\Model;
use Nette;
use Nette\Database;
use Nette\Diagnostics\Debugger;

class MusicInterpret extends Table   {
	protected $tableName = 'music_interpret'; 

    public function delete($id) {
        $record = $this->getTable()->where(array('id' => $id))
        						   ->fetch();        						   
		if($record->image)
			unlink("./images/music_interpret_avatars/".$record->image);
		
		return $record->delete();			
    }
}