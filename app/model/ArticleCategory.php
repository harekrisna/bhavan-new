<?php

namespace App\Model;
use Nette;
use Tracy\Debugger; 
use Nette\Utils\DateTime;

class ArticleCategory extends Table {
	
	protected $tableName = 'article_category';

	public function getTitleById($id)  {
		$record = $this->get($id);
		if($record)
			return String::webalize($record->title);
	}
	
	public function getIdByTitle($url)  {
		$record = $this->findBy(array("title" => String::webalize($url)))->fetch();
		if($record) {
			return $record->id;	
		}
	}
}