<?php

namespace App\Model;
use Nette;
use Nette\Diagnostics\Debugger; 
use Nette\Utils\Image;

class Actuality extends Table   {
	protected $tableName = 'actuality'; 

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
		
	public function insert($data)	{		
		try {
	    	return parent::insert($data);
	    } catch(\PDOException $e) {
	        if($e->getCode() == 23000)
	            return false;
	        else
	            throw $e;
	    }
	}
	
	public function update($id, $data)	{
		try {
	        return parent::update($id, $data);
	    } catch(\PDOException $e) {
	        if($e->getCode() == 23000)
	            return false;
	        else
	            throw $e;
	    }
	}
    
    public function delete($id) {
        $record = $this->getTable()->where(array('id' => $id))
        						   ->fetch();
        						   
		if($record->preview_image)
			unlink("images/actuality/previews/".$record->preview_image);

		if($record->article_image)
			unlink("images/actuality/".$record->article_image);
		
		return $record->delete();			
    }

    public function getList() {
    	return $this->findAll()
					->where('show_from IS NULL OR show_from < NOW()')
					->where('show_to IS NULL OR show_to > NOW()')
					->order('date_from ASC, id ASC');
    }
}