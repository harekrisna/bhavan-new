<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Nette\Utils\Image;
use Tracy\Debugger;

final class InterpretPresenter extends BasePresenter {        
    
    /** @var object */
    private $record;
    
    private $mp3_folder = "./mp3";
    
    private $model;
    
    protected function startup()  {
        parent::startup();
   		$this->model = $this->interpret;
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    	
	function renderList($interpret_id) {
		$this->template->interprets = $this->interpret->findAll();	
	}
	
	function renderDetail($iterpret_id) {
		
	}
	
	function actionAdd() {
		$this['interpretForm']['image']->setRequired('Vyberte prosím obrázek.');
		$this['interpretForm']['data']['mp3_folder']->setRequired('Zadejte prosím adresář s fotkami.');
	}
	
	function renderAdd() {
		$this->setView("form");
		$this->template->form_title = "Nový interpret";
	}
	
		
	public function actionEdit($record_id) {
		$this->record = $this->interpret->get($record_id);
		
		if (!$this->record)
            throw new Nette\Application\BadRequestException("Interpret nenalezen.");
			
        $this->template->record = $this->record;
        $defaults = $this->record;
        unset($this["interpretForm"]['data']['mp3_folder']);
        $this["interpretForm"]['data']->setDefaults($defaults);		
	}

	public function renderEdit($record_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit interpreta";
	}
	
	public function handleRemove($record_id) {
		$this->record = $this->model->get($record_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
        else {
   			$this->model->delete($this->record->id);
   			$this->flashMessage("Interpret byl odstraněn.", "success");
			$this->payload->success = TRUE;
			$this->sendPayload();
	        $this->terminate();	           
        } 
	}
	
    protected function createComponentInterpretForm() {
		$form = new Nette\Application\UI\Form();

	   	$data = $form->addContainer('data');
		
	    $data->addText('title', 'Název', 55, 255)
			 ->setRequired('Zadejte prosím název.');

      	$form->addUpload('image', 'Obrázek')
             ->addCondition(Form::IMAGE)
			 	->addRule(Form::MIME_TYPE, 'Soubor musí být obrázek typu JPEG', array('image/jpeg'));
			 					 
		$data->addText('abbreviation', 'Zkratka', 4, 4);
		$data->addTextArea('description', 'Popis');
	    $data->addText('mp3_folder', 'Adresář', 55, 255);
			 
	    $data->addText('url', 'URL', 55, 255)
			 ->setRequired('Zadejte prosím URL.');
  	    
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'interpretFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'interpretFormUpdate');
		     
        return $form;
    }	
    
    public function interpretFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
        $data = $values->data;
        
        $interpret_folder = $this->mp3_folder."/".$data->mp3_folder;

        if(file_exists($interpret_folder)) {
	        $form['data']['mp3_folder']->addError('Interpret s tímto adresářem již existuje, zvolte prosím jiný.');
        }
        else {		
			try {
				$new_row = $this->model->insert($data);

				mkdir($interpret_folder);
				chmod($interpret_folder, 0777);
				
	        	if($values['image']->isOK()) {
					$preview_file_name = $new_row->id."_".$values['image']->name;
					$image = $values['image']->toImage();
					$image->resize(200, 200, Image::EXACT);
					$image->save("./images/interpret_avatars/".$preview_file_name, 80);
					chmod("./images/interpret_avatars/".$preview_file_name, 0777);
					$this->model->update($new_row->id, array("image" => $preview_file_name));
	        	}				
								
	            $this->flashMessage('Interpret přidán.', 'success');
	            $this->redirect('list');

		    } catch(Nette\Database\UniqueConstraintViolationException $e) {
				$form['data']['url']->addError('Interpret s tímto URL již existuje, zvolte prosím jiný.'); 
			}
        }        
    }
    
    
    public function interpretFormUpdate(\Nette\Forms\Controls\SubmitButton $button) {
        $form = $button->form;
        $values = $form->getValues(); 
        
		try	{
        	$this->model->update($this->record->id, $values['data']);
        	
		    if($values['image']->isOK()) {
   				if($this->record->image)
					@unlink("./images/interpret_avatars/".$this->record->image);
					
				$preview_file_name = $this->record->id."_".$values['image']->name;
				$image = $values['image']->toImage();
				$image->resize(200, 200, Image::EXACT);
				$image->save("./images/interpret_avatars/".$preview_file_name, 80);
				chmod("./images/interpret_avatars/".$preview_file_name, 0777);
				$this->model->update($this->record->id, array("image" => $preview_file_name));
	    	}	

	        $this->flashMessage('Interpret aktualizován.', 'success');
			$this->redirect('list');
        }	
        catch(Nette\Database\UniqueConstraintViolationException $e) {
			$form->addError('Interpret s tímto URL již existuje, zvolte prosím jiné.');
		}        
    }    
}
