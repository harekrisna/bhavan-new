<?php

namespace App\Model;
use Nette;
use Tracy\Debugger; 

class Galery extends Table   {
	protected $tableName = 'galery'; 

	public function insert($data)	{
	  	try {
	      	return $this->getTable()->insert($data);
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
	
	public function generateNumberArray($start, $end) {
		$array = array();
		for($i = $start; $i <= $end; $i++) {
		  	$array[$i] = $i;
		}
		
		return $array;
	}

    public function getList() {
    	return $this->findAll()
					->where('active IS TRUE')
					->order('galery_year DESC, galery_month DESC, galery_day DESC');
    }	
	
}