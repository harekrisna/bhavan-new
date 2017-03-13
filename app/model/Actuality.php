<?php

namespace App\Model;
use Nette;
use Nette\Diagnostics\Debugger; 
use Nette\Utils\Image;

class Actuality extends Table   {
	protected $tableName = 'actuality'; 
	protected $iCloudURL = 'https://p30-calendars.icloud.com/published/2/K1nsJsPu8LjgMm4xLYW0KEwWUAkD_IT5Pdz-uk0to-ZjF2tXvKtyQ6i18AEytyzHaCl1g9wMgxW2pSTRnhrqPybjJIDO66azIH9zIANKZkQ';
	protected $imagesFolder = "images/actuality";

	public function getTitleById($id)  {
		$record = $this->get($id);
		if($record)
			return $record->url;
	}
	
	public function getIdByTitle($url)  {
		$record = $this->findBy(array("url" => $url))->fetch();
		if($record) {
			return $record->id;	
		}
	}
		
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
    
    public function delete($id) {
        $record = $this->getTable()->where(array('id' => $id))
        						   ->fetch();
        						   
		if($record->preview_image)
			unlink("images/actuality/previews/".$record->preview_image);

		if($record->article_image)
			unlink("images/actuality/".$record->article_image);
		
		return $record->delete();			
    }

	public function updateActualityFromiCloud() {
		$ch = curl_init();

		$options = array(
		        CURLOPT_URL => $this->iCloudURL,
		        CURLOPT_HEADER => false,
		        CURLOPT_RETURNTRANSFER => 1,
	    );

		curl_setopt_array($ch, $options); 
		
		if($content = curl_exec($ch)) {
			while(strlen($content) > 20) {
				$actuality = array();
				
				$content = substr($content, strpos($content, "BEGIN:VEVENT")); // oříznutí hlaviček kalendáře
				$start = strpos($content, "BEGIN:VEVENT"); // nalezení začátku události
				$end = strpos($content, "END:VEVENT"); // nalezení konce události
				$event = substr($content, $start, $end + 10);
				
				// název
				$matches = array();
				preg_match('/SUMMARY:([\s\S]*)[A-Z]{2,}[CLASS|CREATED|DESCRITION|DTSTART|GEO|LAST\-MOD|LOCATION|ORGANIZER|PRIORITY|DTSTAMP|SEQ|STATUS|TRANSP|UID|URL|RECURID|ATTACH|ATTENDEE|CATEGORIES|COMMENT|CONTACT|EXDATE|EXRULE|RSTATUS|RELATED|RESOURCES|RDATE|RRULEX\-PROP][;|:]/U', $event, $matches);
				$actuality['title'] = stripslashes(nl2br(trim($matches[1])));

				//url z názvu
				$actuality['url'] = $this->seoURL($actuality['title']);
				
				// datum a čas
				$matches = array();
				preg_match('/DTSTART;([^\s]+)/', $event, $matches);
				$date_stamp = substr($matches[1], strpos($matches[1], ":") + 1);
				$mysql_date = substr($date_stamp, 0, 4)."-".substr($date_stamp, 4, 2)."-".substr($date_stamp, 6, 2);
				if(strpos($date_stamp, "T") !== false) {
					$mysql_time = substr($date_stamp, 9, 2).":".substr($date_stamp, 11, 2).":".substr($date_stamp, 13, 2);
					$actuality['actuality_date'] = $mysql_date." ".$mysql_time;
				}
				else
					$actuality['actuality_date'] = $mysql_date;
				
				// popis
				$matches = array();
				preg_match('/DESCRIPTION:([\s\S]*)[A-Z]{2,}[CLASS|CREATED|DESCRITION|DTSTART|GEO|LAST\-MOD|LOCATION|ORGANIZER|PRIORITY|DTSTAMP|SEQ|STATUS|TRANSP|UID|URL|RECURID|ATTACH|ATTENDEE|CATEGORIES|COMMENT|CONTACT|EXDATE|EXRULE|RSTATUS|RELATED|RESOURCES|RDATE|RRULEX\-PROP][;|:]/U', $event, $matches);
				$description = preg_replace("/\r\n|\r|\n/", '', trim($matches[1]));
				$actuality['description'] = stripslashes(str_replace(array("\\,", "\\n"), array(",", "<br />"), $description));
				
				$record = $this->findBy(array('url' => $actuality['url']))->fetch();
				if($record)
					$this->delete($record->id);
								
				$id = $this->insert($actuality);
				
				// přílohy
				if(strpos($event, "ATTACH;")) {
					$attach = explode("ATTACH;", $event);
					
					// malý obrázek náhledu
					$preview_string = $attach[1];
					$file = substr($preview_string, strpos($preview_string, "FILENAME="));
					$file_name = substr($file, strpos($file, "FILENAME=") + 9, strpos($file, ";") - 9);
					
					$url_part = substr($preview_string, strpos($preview_string, "https:"));
					$url_part = str_replace(array("\n", "\r"), "", $url_part);
					$url_part = str_replace(array(" "), "", $url_part);
					$url = substr($url_part, 0, strpos($url_part, "/{$file_name}"));
					$url = $url."/".$file_name;
					
					// url obrázku
					$matches = array();
					preg_match('/icloud.com\/([0-9]+)\/attach/', $url, $matches);
					$dsid = $matches[1];
					$link = $url."?dsid=".$dsid;
					
					// načtení obrázku z linku, zmenseni a ulozeni ve formatu: id zaznamu_jmeno souboru.pripona
					$file_name = $id."_".$file_name;
					$image = Image::fromFile($link);
					$image->resize(200, 200, Image::EXACT);
					$image->save($this->imagesFolder."/previews/".$file_name);
					$this->update($id, array("preview_image" => $file_name));

					// obrázek v textu
					if(isset($attach[2])) { 
						$article_string = $attach[2];
						$file = substr($article_string, strpos($article_string, "FILENAME="));
						$file_name = substr($file, strpos($file, "FILENAME=") + 9, strpos($file, ";") - 9);
	
						$url_part = substr($article_string, strpos($article_string, "https:"));
						$url_part = str_replace(array("\n", "\r"), "", $url_part);
						$url_part = str_replace(array(" "), "", $url_part);
						$url = substr($url_part, 0, strpos($url_part, "/{$file_name}"));
						$url = $url."/".$file_name;
						$link = $url."?dsid=".$dsid;
						
						$file_name = $id."_".$file_name;
						$image = Image::fromFile($link);
						$image->resize(400, 540);
						$image->save($this->imagesFolder."/".$file_name);
						$this->update($id, array("article_image" => $file_name));
					}
				}
				$content = substr($content, $end + 10); // uříznutí události od zbytku
			}
		} 
	}

	public function seoURL($title){
	    $address = $title;
	    $address = str_replace(Array("á","č","ď","é","ě","í","ľ","ň","ó","ř","š","ť","ú","ů","ý ","ž","Á","Č","Ď","É","Ě","Í","Ľ","Ň","Ó","Ř","Š","Ť","Ú","Ů","Ý","Ž", " "),
                        	   Array("a","c","d","e","e","i","l","n","o","r","s","t","u","u","y ","z","A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z", "-"),
							   $address
							  );	    // nahradi znaky s diakritikou na znaky bez diakritiky	
	    $address = strtolower ($address); 	    							// prevede vsechna velka pismena na mala	
	    $address = preg_replace ("/[^[:alpha:][:digit:]]/", "-", $address); // nahradi pomlckou vsechny znanky, ktera nejsou pismena
	    $address = trim ($address, "-");	    							// odstrani ze zacatku a z konce retezce pomlcky
	    $address = preg_replace ("/[-]+/", "-", $address);	    			// odstrani z adresy pomlcky, pokud jsou dve a vice vedle sebe
	
	    return $address;
	}
	
	public function mysqlDate($date) {
		return substr($date, 6, 4)."-".substr($date, 3, 2)."-".substr($date, 0, 2);
	}



}