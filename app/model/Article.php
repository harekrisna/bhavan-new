<?php

namespace App\Model;
use Nette;
use Nette\Database;
use Tracy\Debugger;


class Article extends Table   {
	protected $tableName = 'article'; 
	
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
}