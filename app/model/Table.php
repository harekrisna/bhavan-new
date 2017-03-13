<?php

namespace App\Model;
use Nette;
use Tracy\Debugger;

/**
 * Reprezentuje repozit?? pro datab?zovou tabulku
 */
abstract class Table extends Nette\Object
{

    /** @var Nette\Database\Connection */
    public $db;

    /** @var string */
    protected $tableName;

    /**
     * @param Nette\Database\Connection $db
     * @throws \Nette\InvalidStateException
     */
    public function __construct(Nette\Database\Context $db)
    {
        $this->db = $db;

        if ($this->tableName === NULL) {
            $class = get_class($this);
            throw new Nette\InvalidStateException("Název tabulky musí být definován v $class::\$tableName.");
        }
    }

	public function query($query) {
		return $this->db->query($query);	
	}
	
    /**
     * Vrac? celou tabulku z datab?ze
     * @return \Nette\Database\Table\Selection
     */
    protected function getTable($tableName = "")
    {
        if($tableName != "")
            return $this->db->table($tableName);
        else
            return $this->db->table($this->tableName);
    }

    /**
     * Vrac? v?echny z?znamy z datab?ze
     * @return \Nette\Database\Table\Selection
     */
    public function getAll()
    {
        return $this->getTable();
    }
    
    public function findAll()
    {
        return $this->getTable();
    }

    /**
     * Vrac? vyfiltrovan? z?znamy na z?klad? vstupn?ho pole
     * (pole array('name' => 'David') se p?evede na ??st SQL dotazu WHERE name = 'David')
     * @param array $by
     * @return \Nette\Database\Table\Selection
     */
    public function findBy(array $by)
    {
        return $this->getTable()->where($by);
    }

    /**
     * To sam? jako findBy akor?t vrac? v?dy jen jeden z?znam
     * @param array $by
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function findOneBy(array $by)
    {
        return $this->findBy($by)->limit(1)->fetch();
    }

    /**
     * Vrac? z?znam s dan?m prim?rn?m kl??em
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow|FALSE
     */
    public function get($id)
    {
        return $this->getTable()->get($id);
    }

    public function insert($data)	{
	    
	    $references = $this->db->getStructure()
                               ->getColumns($this->tableName);
        
        // ošetření DATETIME a DATE vstupů
        foreach($references as $column) {
	        $name = $column['name'];
			if(isset($data[$name])) {
		    	if(($column['nativetype'] == "DATETIME" || $column['nativetype'] == "DATE") && !($data[$name] instanceof \DateTime)) {
			    	$data[$name] = NULL;
		    	}
				
		    	if($column['nativetype'] == "YEAR" && $data[$name] == "") {
			    	$data[$name] = NULL;
		    	}
	    	}
	    }
	    	    
        return $this->getTable()->insert($data);
    }
    
    public function update($id, $data)	{
	    
	    $references = $this->db->getStructure()
                               ->getColumns($this->tableName);

        // ošetření DATETIME a DATE vstupů
        foreach($references as $column) {
	        $name = $column['name'];
	        if(isset($data[$name])) {
		    	if(($column['nativetype'] == "DATETIME" || $column['nativetype'] == "DATE") && !($data[$name] instanceof \DateTime)) {
			    	$data[$name] = NULL;
		    	}
		    	
		    	if($column['nativetype'] == "YEAR" && $data[$name] == "") {
			    	$data[$name] = NULL;
		    	}
	    	}
	    }
	    	    
        return $this->getTable()->where(array('id' => $id))
        						->update($data);
    }

    public function delete($id)
    {
        return $this->getTable()->where(array('id' => $id))
        						->delete();
    }
    
    public function truncate() {
	    return $this->db->query("TRUNCATE {$this->tableName}");
    }

}
