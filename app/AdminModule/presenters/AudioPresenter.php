<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Tracy\Debugger;
use Nette\Utils\Finder;
use Nette\Utils\DateTime;
use Extensions\MP3;


final class AudioPresenter extends BasePresenter {        
	/** @persistent */
    public $interpret_id = "all";
    
    /** @var object */
    private $record;
    
    private $mp3_folder = "./mp3";
        
    protected function startup()  {
        parent::startup();
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function beforeRender() {	    
        $this->template->interprets = $this->interpret->findAll();
    }

	function renderList($interpret_id) {
		$this->interpret_id = $interpret_id;
		if($interpret_id != "all") {
			$this->template->audio = $this->audio->findAll()
												 ->where(['audio_interpret_id' => $interpret_id])
												 ->order('audio_year DESC, audio_month DESC, audio_day DESC');
		}
		else {
			$this->template->audio = $this->audio->findAll()
												 ->order('audio_year DESC, audio_month DESC, audio_day DESC');
		}
	}

	public function actionAdd() {
		$unclassified_mp3_files = array();
		foreach (Finder::findFiles('*.mp3')->in("mp3/upload/") as $file_path => $file) {
			$unclassified_mp3_files[] = basename($file);
		}

	    $this['audioForm']['mp3_file']->setItems($unclassified_mp3_files)
									  ->setRequired('Vyberte prosím soubor.');
	}
	
	public function renderAdd() {
		$this->setView("form");
		
		if(!$this->isAjax()) {
			foreach ($this['mp3TagsForm']->getControls() as $control) {
		        $control->disabled = true;
	    	}
    	}
    			
		$this->template->form_title = "Nové audio:";
		$this->template->form_type = "add";
		$this->template->interpret_id_selected = $this->interpret_id;
	}

	public function actionEdit($record_id) {
		$this->record = $this->audio->get($record_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
			
        $this->template->record = $this->record;
        $defaults = $this->record;

        unset($this["audioForm"]['mp3_file']);
        $this["audioForm"]->setDefaults($defaults);
		$mp3_file = $this->mp3_folder."/".$this->record->audio_interpret->mp3_folder."/".$this->record->mp3_file;
		$mp3_tag = new MP3($mp3_file);
		$this->template->mp3_tag = $mp3_tag;
		
		$defaults = ['title' => $mp3_tag->title,
					 'artist' => $mp3_tag->artist,
					 'album' => $mp3_tag->album,
					 'genre' => $mp3_tag->genre,
					 'year' => $mp3_tag->year,
					];
		
		$this['mp3TagsForm']->setDefaults($defaults);
		$this['mp3TagsForm']['mp3_file_path']->setValue($mp3_file);
	}

	public function renderEdit($record_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit přednášku:";
		$this->template->form_type = "edit";
		$this->template->interpret_id_selected = $this->interpret_id;
	}


	public function handleDelete($audio_id) {
		$audio = $this->audio->get($audio_id);

		if (!$audio)
            throw new Nette\Application\BadRequestException;
        
   		$this->audio->delete($audio_id);
		@unlink("mp3/".$audio->audio_interpret->mp3_folder."/".$audio->mp3_file);
		
		$this->payload->success = TRUE;
		$this->sendPayload();
        $this->terminate();	                
    }
	

	public function handleGetMp3Tags($file_name) {
		if(!file_exists($this->mp3_folder."/upload/".$file_name)) {			
			foreach ($this['mp3TagsForm']->getControls() as $control) {
		        $control->disabled = true;
	    	}
	    	
   			$this['mp3TagsForm']['mp3_file_path']->setValue("");
   			$this->redrawControl('mp3_tags');
   			$this->payload->is_empty = true;
   			return;
		}

		foreach ($this['mp3TagsForm']->getControls() as $control) {
	        $control->disabled = false;
    	}		
    	
		$mp3_file = $this->mp3_folder."/upload/".$file_name;
		$mp3_tag = new MP3($mp3_file);
		$this->template->mp3_tag = $mp3_tag;
		
		$defaults = ['title' => $mp3_tag->title,
					 'artist' => $mp3_tag->artist,
					 'album' => $mp3_tag->album,
					 'genre' => $mp3_tag->genre,
					 'year' => $mp3_tag->year,
					];
		
		$this["mp3TagsForm"]->setDefaults($defaults);
		
		foreach ($this['mp3TagsForm']->getControls() as $control) {
	        $control->disabled = false;
    	}
    	
		$this['mp3TagsForm']['mp3_file_path']->setValue($mp3_file);
		$this->payload->is_empty = false;
		$this->redrawControl('mp3_tags');
	}
	

    protected function createComponentMp3TagsForm(){
		$form = new Form();

	    $form->addUpload('picture', 'Obrázek alba');
	    $form->addText('title', 'Název skladby', 55, 255);
	    $form->addText('artist', 'Interpret', 55, 255);
	    $form->addText('album', 'Album', 40, 255);
	    $form->addText('genre', 'Žanr', 40, 255);
	    $form->addText('year', 'Rok:', 4, 4);
			 
	    $form->addHidden('mp3_file_path');
		$form->addSubmit('save', 'Uložit MP3 tagy');
		
        $form->onSuccess[] = array($this, 'saveMp3Tags');
        return $form;
    }

    public function saveMp3Tags(Form $form, $values) {	    
   		$mp3 = new MP3($values->mp3_file_path);
   		
   		$mp3->artist = $values->artist;
		$mp3->title = $values->title;
		$mp3->album = $values->album;
		$mp3->year = $values->year;
		$mp3->genre = $values->genre;
		
	    if($values->picture->isOk() && $values->picture->isImage()) {
			$mp3->set_art($values->picture->getContents(), $values->picture->getContentType());
	    }
	    elseif($mp3->image['data'] != null) {
	   		$mp3->set_art($mp3->image['data'], $mp3->image['mime']);
	    }
	   
		$mp3->save();
		$this->template->mp3_tag = new MP3($values->mp3_file_path);
		$this->redrawControl('mp3_tags');
    }
    
    // nedodělané neodzkoušené, na WEDOSu je post_max_size pouze 32MB, takže se nedá použít
	function handleUploadMp3() {		
		$fileTypes = array('mp3'); // Allowed file extensions
   		$verifyToken = md5('unique_salt' . $_POST['timestamp']);
   		
		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			
			if (in_array(strtolower($fileParts['extension']), $fileTypes)) {
				$uploads_dir  = "mp3/upload";
				$tmp_name   = $_FILES['Filedata']['tmp_name'];
				$name = $_FILES['Filedata']['name'];

				$this->payload->move_ok = move_uploaded_file($tmp_name, $uploads_dir."/".$name);
				chmod("$uploads_dir/$name", 0777);
			}
		}
		
        $this->sendPayload();
	}


    protected function createComponentAudioForm(){
		$form = new Nette\Application\UI\Form();
		
	    $form->addText('title', 'Název lekce:', 55, 255)
			 ->setRequired('Zadejte prosím název.');

	    $form->addSelect('audio_interpret_id', 'Kdo:', $this->interpret->getAll()->fetchPairs('id', 'title'));
	    $form->addText('place', 'Kde:', 40, 255);
								  
	    $form->addSelect('book_id', 'Titul:', $this->book->getAll()->fetchPairs('id', 'title'))
	    	 ->setPrompt('- žádný -');
	    
	    $form->addText('chapter', 'Kapitola', 2, 2)
   			 ->addCondition($form::FILLED)
				 ->addRule($form::INTEGER, 'Kapitolo musí být číslo.');
				 
		$form->addText('verse', 'Verš', 3, 3)
			 ->addCondition($form::FILLED)
				 ->addRule($form::INTEGER, 'Verš musí být číslo.');
  	    
		$days['0'] = "";
		$days = $days + $this->galery->generateNumberArray(1, 31);
		$months['0'] = "";
		$months = $months + $this->galery->generateNumberArray(1, 12);
		$years = $this->galery->generateNumberArray(1960, 2020);
			
	    $form->addSelect('audio_day', 'Den:', $days);
        $form->addSelect('audio_month', 'Měsíc:', $months);
	    $form->addSelect('audio_year', 'Rok:', $years)
		     ->setRequired('Zadejte alespoň rok.')
			 ->setDefaultValue(date('Y'));


	    $form->addSelect('mp3_file', 'Soubor:')
   	    	 ->setPrompt('- nevybrán -');
   			 
	    $form->addSelect('type', 'Typ lekce:', array('live' => "Živá lekce",
	    											 'record' => "Překlad ze záznamu"
	    											));

	    $form->addSelect('audio_collection_id', 'Kolekce:', $this->collection->getAll()->fetchPairs('id', 'title'))
		     ->setPrompt('- žádná -');;
	    											
		$form->addCheckbox('seminar', "Seminář");
		$form->addCheckbox('sankirtan', "Sankírtan");
		$form->addCheckbox('varnasrama', "Varnášrama a farmy");
		$form->addCheckbox('add_to_news', "Přidat do novinek?");
		$form->addHidden('mp3_file_name');
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'audioFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'audioFormUpdate');
		     
        return $form;
    }
    
    public function audioFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
	    $mp3_file = "";
	    
		$interpret = $this->interpret->get($values['audio_interpret_id']);
		
	    foreach (Finder::findFiles('*.mp3')->in("mp3/upload/") as $file_path => $file) {
			if($values['mp3_file_name'] == basename($file))	{
				$mp3_file = $values['mp3_file_name'];
				break;
			}
		}
	    
	    if(!$mp3_file) {
		    $form->addError("Vybraný soubor: ".$values['mp3_file_name']." nenalezen.");
		    return;
	    }
	    
		$ok = $this->audio->insert(array('title' => $values['title'],
				   	   				     'audio_interpret_id' => $values['audio_interpret_id'],
					   					 'place' => $values['place'],
					   					 'book_id' => $values['book_id'],
					   					 'chapter' => $values['chapter'],
										 'verse' => $values['verse'],
				   						 'audio_year' => $values['audio_year'],
				   						 'audio_month' => $values['audio_month'],
				   						 'audio_day' => $values['audio_day'],
				   	   					 'type' => $values['type'],
				   						 'audio_collection_id' => $values['audio_collection_id'],
				   	   					 'seminar' => $values['seminar'],
				   	   					 'sankirtan' => $values['sankirtan'],
				   	   					 'varnasrama' => $values['varnasrama'],				   	   					 
				   	   					 'mp3_file' => $values['mp3_file_name'],
				   	   					));
		
		if($ok) {				
			rename("mp3/upload/".$mp3_file, "mp3/".$interpret->mp3_folder."/".$mp3_file); // zkopírování souboru z dočasné složky do složky interpreta
			chmod("mp3/".$interpret->mp3_folder."/".$mp3_file, 0777);

			if($values['add_to_news']) {
				$interpret_image_source = "./images/interpret_avatars/".$interpret->image;
	    		$target_link = $this->link(":Audio:latest");
				$this->news->addNew("Audio", $values['title'], $interpret_image_source, $target_link);
	        }
	        
            $this->flashMessage('Audio lekce přidána.', 'success');
            $this->redirect('list', $this->interpret_id);
            
		}
		else {
			$form->addError("Audio nahrávku se nepodařilo vložit.");
		}
    }
    
	public function audioFormUpdate(\Nette\Forms\Controls\SubmitButton $button)	{
		if (!$this->record)
            throw new Nette\Application\BadRequestException;

        $form = $button->form;
        $values = $form->getValues();
        
		$update = $this->audio->update($this->record->id, 
									   array('title' => $values['title'],
				   	   				   		 'audio_interpret_id' => $values['audio_interpret_id'],
				   	   				   		 'place' => $values['place'],
				   	   				   		 'book_id' => $values['book_id'],
					   	   					 'chapter' => $values['chapter'],
					   	   					 'verse' => $values['verse'],
				   	   				   		 'audio_year' => $values['audio_year'],
				   	   				   		 'audio_month' => $values['audio_month'],
				   	   				   		 'audio_day' => $values['audio_day'],
				   	   				   		 'type' => $values['type'],
				   	   				   		 'audio_collection_id' => $values['audio_collection_id'],
				   	   				   		 'seminar' => $values['seminar'],
					   	   					 'sankirtan' => $values['sankirtan'],
					   	   					 'varnasrama' => $values['varnasrama'],
				   	   					));
		
		if($update !== false) {
			if($this->record->audio_interpret_id != $values->audio_interpret_id) { // interpret byl změněn
				$old_interpret = $this->interpret->get($this->record->audio_interpret_id);
				$new_interpret = $this->interpret->get($values->audio_interpret_id);
				$old_filepath = "mp3/".$old_interpret->mp3_folder."/".$this->record->mp3_file;
				$new_filepath = "mp3/".$new_interpret->mp3_folder."/".$this->record->mp3_file;

				// přesun mp3 souboru do adresáře nového interpreta
				rename($old_filepath, $new_filepath);
			}
            $this->flashMessage('Přednáška upravena.', 'success');
            $this->redirect('list', $this->interpret_id);
		}
		else {
			$form->addError('Přednášku se nepodařilo uložit.');
		}
    }
}
