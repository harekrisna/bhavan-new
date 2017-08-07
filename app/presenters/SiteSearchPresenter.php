<?php

namespace App\Presenters;

use Nette,
	App\Model;

use App\Extensions\Mobile_Detect;
use Tracy\Debugger;

class SiteSearchPresenter extends BasePresenter {
	private $highlight_tag = "strong";
	private $search_preview_gap_before = 64;
	private $search_preview_gap_after = 64;

	/** @var Nette\Http\Session */
    private $session;
	
	public function renderSearch($q) {
		if($q == "") {
			$this->redirect("Homepage:default");
			$this->terminate();
		}

		$this->statsSearch->insert(['pattern' => $q, 'ip' => $_SERVER['REMOTE_ADDR']]);
        $this->setView('search-results'); 
        $query = trim($q);

        $search_articles = $this->article->searchArticles($query);

        $articles = [];

        foreach ($search_articles as $article) {
        	$text = strip_tags($article->text);
			$text = $this->highlight($query, $text);

			$first_match_pos = mb_strpos($text, "<".$this->highlight_tag.">");
			if($first_match_pos == false) // může se stát, že řetězec byl vyhledán databází v tagu, ale po odstranění tagů tam již není, takže to přeskočíme
				continue;

			$first_match_pos_end = mb_strpos($text, "</".$this->highlight_tag.">") + strlen("</".$this->highlight_tag.">");

			$preview_text_start = max($first_match_pos - $this->search_preview_gap_before, 0);
			$preview_text_length = mb_strlen($query) + strlen("<".$this->highlight_tag.">") + strlen("</".$this->highlight_tag.">") + $this->search_preview_gap_before + $this->search_preview_gap_after;

			$full_text_length = mb_strlen($text);
			
			$text = mb_substr($text, $preview_text_start, $preview_text_length); // vyseknutí textu okolo prvního výskytu slova
			
			if($preview_text_start > 0) {
				$text = mb_substr($text, mb_strpos($text, " ")); // odstranění textu před první mezerou
				$text = "…".$text;
			}

			if(($full_text_length - $first_match_pos_end) > $this->search_preview_gap_after) {
				$text = mb_substr($text, 0, mb_strrpos($text, " ")); // odstranění textu po poslední mezeře
				
				$open_tags_count = mb_substr_count($text, "<".$this->highlight_tag.">");
				$close_tags_count = mb_substr_count($text, "</".$this->highlight_tag.">");

				if($open_tags_count > $close_tags_count) {
					$text = $text."</".$this->highlight_tag.">";		
				}

				$text = $text." …";	
			}
			
        	$articles[] = ['id' => $article->id,
        				   'title' => $article->title,
        				   'text' => $text];
        }

        $this->template->articles = $articles;
        $this->template->lectures = $this->audio->searchLectures($query);
        $this->template->records = $this->music->searchRecords($query);

        $detect = new Mobile_Detect;
		$this->template->isMobile = $detect->isMobile();
		
		$this->session = $this->getSession('backlinks');
		$this->session->backlinks = [];
		$this->session->main_group = "";
        
        $this->template->search_text = $query;
    } 


	
    private function highlight($needle, $haystack) {
		// Priprava na slozeni regexpu (obsazeni temer vsech moznych akcentu)
		$map1[]="/[aãǎâăåąàȧáäā]/iu";
		$map1[]="/[bḃ]/iu";
		$map1[]="/[cčĉċćç]/iu";
		$map1[]="/[dďḋḑḍ]/iu";
		$map1[]="/[eěêĕęèéëȩẹ]/iu";
		$map1[]="/[fḟ]/iu";
		$map1[]="/[gǧĝġǵģ]/iu";
		$map1[]="/[hȟĥḣḧḩḥ]/iu";
		$map1[]="/[iĩǐîĭįìıíïīị]/iu";
		$map1[]="/[jǰĵ]/iu";
		$map1[]="/[kǩḱķ]/iu";
		$map1[]="/[lľĺļ]/iu";
		$map1[]="/[mṁḿ]/iu";
		$map1[]="/[nñňǹṅńņṇ]/iu";
		$map1[]="/[oõǒôŏǫòȯóőö]/iu";
		$map1[]="/[pṗṕþ]/iu";
		$map1[]="/[rřṙŕŗṛ]/iu";
		$map1[]="/[sšŝṡśşṣ]/iu";
		$map1[]="/[tťṫẗţṭ]/iu";
		$map1[]="/[uũǔûŭůųùúűüū]/iu";
		$map1[]="/[vṽ]/iu";
		$map1[]="/[wŵẘẁẇẃẅ]/iu";
		$map1[]="/[xẋ]/iu";
		$map1[]="/[yỹŷẙỳẏýÿ]/iu";
		$map1[]="/[zžẑżź]/iu";
		
		// Slozeni regexpu
		$map2[]="[aãǎâăåąàȧáäā]";
		$map2[]="[bḃ]";
		$map2[]="[cčĉċćç]";
		$map2[]="[dďḋḑḍ]";
		$map2[]="[eěêĕęèéëȩẹ]";
		$map2[]="[fḟ]";
		$map2[]="[gǧĝġǵģ]";
		$map2[]="[hȟĥḣḧḩḥ]";
		$map2[]="[iĩǐîĭįìıíïīị]";
		$map2[]="[jǰĵ]";
		$map2[]="[kǩḱķ]";
		$map2[]="[lľĺļ]";
		$map2[]="[mṁḿ]";
		$map2[]="[nñňǹṅńņṇ]";
		$map2[]="[oõǒôŏǫòȯóőö]";
		$map2[]="[pṗṕþ]";
		$map2[]="[rřṙŕŗṛ]";
		$map2[]="[sšŝṡśşṣ]";
		$map2[]="[tťṫẗţṭ]";
		$map2[]="[uũǔûŭůųùúűüū]";
		$map2[]="[vṽ]";
		$map2[]="[wŵẘẁẇẃẅ]";
		$map2[]="[xẋ]";
		$map2[]="[yỹŷẙỳẏýÿ]";
		$map2[]="[zžẑżź]";
		
		// Nahrazeni hledaneho vyrazu regexpem
		// "pocitac" se bude hledat jako: [pṗṕþ][oõǒôŏǫòȯóőö][cčĉċćç][iĩǐîĭįìıíï][tťṫẗţ][aãǎâăåąàȧáä][cčĉċćç]
		$needle = preg_replace($map1,$map2,$needle);

		// Finalni nahrazeni hledaneho vyrazu v textu
		$haystack = preg_replace("/${needle}/iu","<".$this->highlight_tag.">\$0</".$this->highlight_tag.">",$haystack);
		
		return $haystack;
	} 
}
