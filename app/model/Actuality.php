<?php

namespace App\Model;
use Nette;
use Nette\Diagnostics\Debugger; 
use Nette\Utils\Image;

class Actuality extends Table   {
	protected $tableName = 'actuality'; 
	protected $iCloudURL = 'https://p30-calendars.icloud.com/published/2/K1nsJsPu8LjgMm4xLYW0KEwWUAkD_IT5Pdz-uk0to-ZjF2tXvKtyQ6i18AEytyzHaCl1g9wMgxW2pSTRnhrqPybjJIDO66azIH9zIANKZkQ';
	protected $imagesFolder = "images/actuality";

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




}