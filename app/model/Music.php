<?php

namespace App\Model;
use Nette;
use Nette\Diagnostics\Debugger; 

class Music extends Table   {
	protected $tableName = 'music'; 
	
	public function insert($data) {
		if(isset($data['music_month']) && $data['music_month'] == "") {	$data['music_month'] = NULL; }
		if(isset($data['music_day']) && $data['music_day'] == "") {	$data['music_day'] = NULL; }
		
		return parent::insert($data);
	}
	
	public function update($id, $data) {
		if(isset($data['music_month']) && $data['music_month'] == "") {	$data['music_month'] = NULL; }
		if(isset($data['music_day']) && $data['music_day'] == "") {	$data['music_day'] = NULL; }
				
		return parent::update($id, $data);
	}	
}