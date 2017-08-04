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
		$map1[]="/[aãǎâăåąàȧáä]/iu";
		$map1[]="/[bḃ]/iu";
		$map1[]="/[cčĉċćç]/iu";
		$map1[]="/[dďḋḑ]/iu";
		$map1[]="/[eěêĕęèéëȩ]/iu";
		$map1[]="/[fḟ]/iu";
		$map1[]="/[gǧĝġǵģ]/iu";
		$map1[]="/[hȟĥḣḧḩ]/iu";
		$map1[]="/[iĩǐîĭįìıíï]/iu";
		$map1[]="/[jǰĵ]/iu";
		$map1[]="/[kǩḱķ]/iu";
		$map1[]="/[lľĺļ]/iu";
		$map1[]="/[mṁḿ]/iu";
		$map1[]="/[nñňǹṅńņ]/iu";
		$map1[]="/[oõǒôŏǫòȯóőö]/iu";
		$map1[]="/[pṗṕþ]/iu";
		$map1[]="/[rřṙŕŗ]/iu";
		$map1[]="/[sšŝṡśş]/iu";
		$map1[]="/[tťṫẗţ]/iu";
		$map1[]="/[uũǔûŭůųùúűü]/iu";
		$map1[]="/[vṽ]/iu";
		$map1[]="/[wŵẘẁẇẃẅ]/iu";
		$map1[]="/[xẋ]/iu";
		$map1[]="/[yỹŷẙỳẏýÿ]/iu";
		$map1[]="/[zžẑżź]/iu";
		
		// Slozeni regexpu
		$map2[]="[aãǎâăåąàȧáä]";
		$map2[]="[bḃ]";
		$map2[]="[cčĉċćç]";
		$map2[]="[dďḋḑ]";
		$map2[]="[eěêĕęèéëȩ]";
		$map2[]="[fḟ]";
		$map2[]="[gǧĝġǵģ]";
		$map2[]="[hȟĥḣḧḩ]";
		$map2[]="[iĩǐîĭįìıíï]";
		$map2[]="[jǰĵ]";
		$map2[]="[kǩḱķ]";
		$map2[]="[lľĺļ]";
		$map2[]="[mṁḿ]";
		$map2[]="[nñňǹṅńņ]";
		$map2[]="[oõǒôŏǫòȯóőö]";
		$map2[]="[pṗṕþ]";
		$map2[]="[rřṙŕŗ]";
		$map2[]="[sšŝṡśş]";
		$map2[]="[tťṫẗţ]";
		$map2[]="[uũǔûŭůųùúűü]";
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
