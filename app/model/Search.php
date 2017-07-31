<?php

namespace App\Model;
use Nette;
use Tracy\Debugger; 

class Search extends Table   {
	protected $tableName = 'article';

	public function searchArticles($pattern) {
		return $this->db->query("SELECT title, text, LOCATE('".$pattern."', text) as strpos FROM article WHERE text LIKE '%".$pattern."%'");
	}
}