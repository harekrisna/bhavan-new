<?php

namespace App\Model;
use Nette;
use Tracy\Debugger; 
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;

class News extends Table {
	
	protected $tableName = 'news';

    public function insert($data) {		
		unset($data['select_target']);
			
		return parent::insert($data);
    }
    
    public function maxGroupNews() {
	    return $this->query("SELECT MAX(news.count) AS max_group_news 
	    					 FROM (SELECT COUNT(*) AS count 
	    					 	   FROM {$this->tableName} 
	    					 	   WHERE (`show_from` IS NULL OR `show_from` < NOW()) AND (`show_to` IS NULL OR `show_to` > NOW())
	    					 	   GROUP BY news_type_id) AS news")
	    			->fetch()['max_group_news'];
    }
    
    public function addNew($type_title, $title, $image_source, $target_link, $date_from = NULL, $date_to = NULL, $show_from = NULL, $show_to = NULL) {	   
   	    $newsType = new NewsType($this->db);
		$audio_type_id = $newsType->findBy(["title" => $type_title])
								  ->fetch()
								  ->id;
		
		$data =["title" => $title,
				"target" => $target_link,
				"date_to" => $date_to,
				"show_from" => $show_from,
				"show_to" => $show_to,
				"news_type_id" => $audio_type_id];
				
		if($date_from != NULL && $date_from != "") {
			$data["date_from"] = $date_from;
		}
		
        $new_row = $this->insert($data);
							  	  
		if($new_row) {
			$source_pathinfo = pathinfo($image_source);
			$image_target = $new_row->id."-".Strings::webalize($title).".".$source_pathinfo['extension'];
			FileSystem::copy($image_source, "./images/news_thumbs/".$image_target);
			chmod("./images/news_thumbs/".$image_target, 0777);
			
			$this->update($new_row->id, ['file' => $image_target]);
		}
		
		return $new_row->id;
    }

}