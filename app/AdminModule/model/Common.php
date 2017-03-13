<?php

namespace App\AdminModule\Model;
use Nette;
use Nette\Diagnostics\Debugger; 

/**
 * Model s různými funkcemi
 */
class Common extends Nette\Object	{
	
	// přidá za řetězec _1 nebo zvýši číslo o 1 a vrátí řetězec
	public function incrementSuffixIndex($string) {
		$matches = array();
		preg_match('/_([0-9]+)$/', $string, $matches); // končí řetězec na "_číslo" ?
				
		if($matches != array()) {   // pokud ano zvýšíme v řetězeci číslo o 1
			$index = ++$matches[1]; 
            $string = preg_replace('/_([0-9]+)$/', '_'.$index, $string);
        }
        else { // jinak za řetězec přidáme _1
            $index = 1;
            $string .= '_'.$index;
		}
		
		return $string;
	}
	
	// zkontroluje url a vrátí http_code odpovědi
	public function checkUrl($target) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $target);
	    curl_setopt($ch, CURLOPT_HEADER, 1);
	    curl_setopt($ch , CURLOPT_RETURNTRANSFER, 1);
	    $data = curl_exec($ch);
	    $headers = curl_getinfo($ch);
	    curl_close($ch);
	    
	    return $headers['http_code'];
	}
}