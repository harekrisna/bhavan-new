<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Tracy\Debugger;
use Nette\Utils\Finder;
use Nette\Utils\DateTime;
use Extensions\MP3;


final class TeachingPresenter extends BasePresenter {        
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

	function renderList($category) {
		$this->template->articles = $this->article->findAll()
											 	  ->order('created DESC');
	}
	
	public function renderAdd() {
		$this->setView("form");    			
		$this->template->form_title = "Nový článek:";
		$this->template->form_type = "add";
	}

	function actionEdit($id) {
		$this->record = $this->model->get($id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
		$this["articleForm"]->setDefaults($this->record);
	}

	function renderEdit($id) {
		$this->setView("form");
		$this->template->form_title = "Upravit článek:";
		$this->template->record = $this->record;
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
	


    protected function createComponentArticleForm(){
		$form = new Nette\Application\UI\Form();
		$data = $form->addContainer('data');
		
	    $data->addText('title', 'Název článku:', 55, 255)
			 ->setRequired('Zadejte prosím název.');
			 
	    $data->addText('url', 'URL článku:', 40)
      	     ->setRequired('Zadejte prosím URL článku.');
	       			 
	    $data->addSelect('category', 'Kategorie:', ['practice' => "Jak praktikovat", 
	    											'philosophy' => "Základy filisofie", 
	    											'blog' => "Blog"
	    										   ]);
	    										   
	    $data->addTextArea('text', 'Text:', 40);
	    
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'articleFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'articleFormUpdate');
		     
        return $form;
    }
    
    public function articleFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
		Debugger::fireLog($values);
		exit;
		$this->audio->insert(array('title' => $values['title'],
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
