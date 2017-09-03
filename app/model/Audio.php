<?php

namespace App\Model;
use Nette;
use Nette\Diagnostics\Debugger; 

class Audio extends Table   {
	protected $tableName = 'audio'; 
	
	public function insert($data) {
		if(isset($data['chapter']) && $data['chapter'] == "") {	$data['chapter'] = NULL; }
		if(isset($data['verse']) && $data['verse'] == "") {	$data['verse'] = NULL;   }
		if(isset($data['audio_month']) && $data['audio_month'] == "") {	$data['audio_month'] = NULL; }
		if(isset($data['audio_day']) && $data['audio_day'] == "") {	$data['audio_day'] = NULL; }
		
		return parent::insert($data);
	}
	
	public function update($id, $data) {
		if(isset($data['chapter']) && $data['chapter'] == "") {	$data['chapter'] = NULL; }
		if(isset($data['verse']) && $data['verse'] == "") {	$data['verse'] = NULL;   }
		if(isset($data['audio_month']) && $data['audio_month'] == "") {	$data['audio_month'] = NULL; }
		if(isset($data['audio_day']) && $data['audio_day'] == "") {	$data['audio_day'] = NULL; }
				
		return parent::update($id, $data);
	}
	
	public function findYears() {
		return $this->query("SELECT SUBSTRING(audio_date, 1, 4) AS year, COUNT(*) AS count
							 FROM {$this->tableName}
							 GROUP BY year
							 ORDER BY year DESC");
	}
	
}