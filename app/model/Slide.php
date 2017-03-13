<?php

namespace App\Model;
use Nette;
use Tracy\Debugger; 
use Nette\Utils\DateTime;

class Slide extends Table {
	
	protected $tableName = 'slide';

    public function insert($data)	{
	    $references = $this->db->getStructure()
                               ->getColumns($this->tableName);
        
        
        foreach($references as $column) {
	        $name = $column['name'];
	        if(isset($data[$name])) {
		    	if($column['nativetype'] == "DATETIME" && !($data[$name] instanceof \DateTime)) {
			    	$data[$name] = NULL;
		    	}
	    	}
	    }

		parent::insert($data);
    }

	public function update($id, $data) {
	    $references = $this->db->getStructure()
                               ->getColumns($this->tableName);

        foreach($references as $column) {
	        $name = $column['name'];
	        if(isset($data[$name])) {
		    	if($column['nativetype'] == "DATETIME" && !($data[$name] instanceof \DateTime)) {
			    	$data[$name] = NULL;
		    	}
	    	}
	    }
  
		parent::update($id, $data);
    }
}