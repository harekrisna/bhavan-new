<?php

namespace App\Model;
use Nette;
use Nette\Diagnostics\Debugger; 
use Nette\Utils\Strings;

class Book extends Table	{

  	protected $tableName = 'book';  
 
	public function getTitleById($id)  {
		$book = $this->get($id);
		if($book)
			return Strings::webalize($book->abbreviation);
	}
	
	public function getIdByTitle($url)  {
		$book = $this->findBy(array("abbreviation" => $url))->fetch();
		if($book) {
			return $book->id;	
		}
	} 
 
}