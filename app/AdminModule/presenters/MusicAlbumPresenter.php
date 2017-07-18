<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Nette\Utils\Image;
use Tracy\Debugger;

final class MusicAlbumPresenter extends BasePresenter {        
    
    /** @var object */
    private $record;
    
    private $model;
    
    protected function startup()  {
        parent::startup();
   		$this->model = $this->musicAlbum;
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    	
	function renderList() {
		$this->template->albums = $this->model->findAll();	
	}
	
		
	function renderAdd() {
		$this->setView("form");
		$this->template->form_title = "Nové album";
	}
	
		
	public function actionEdit($record_id) {
		$this->record = $this->model->get($record_id);
		
		if (!$this->record)
            throw new Nette\Application\BadRequestException("Album nenalezeno.");
			
        $this->template->record = $this->record;
        $this["albumForm"]['data']->setDefaults($this->record);
	}

	public function renderEdit($record_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit album";
	}
	
	public function handleRemove($record_id) {
		$this->record = $this->model->get($record_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException("Album nenalezeno.");
        else {
   			$this->model->delete($this->record->id);
   			$this->flashMessage("Album byli odstraněno.", "success");
			$this->payload->success = TRUE;
			$this->sendPayload();
	        $this->terminate();	           
        } 
	}
	
    protected function createComponentAlbumForm() {
		$form = new Nette\Application\UI\Form();

	   	$data = $form->addContainer('data');
		
	    $data->addText('title', 'Název alba', 55, 255)
			 ->setRequired('Zadejte prosím název.');
			 
	    $data->addText('url', 'URL', 55, 255)
			 ->setRequired('Zadejte prosím URL.');
  	    
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'albumFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'albumFormUpdate');
		     
        return $form;
    }	
    
    public function albumFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
        $data = $values->data;

		try {
			$new_row = $this->model->insert($data);

            $this->flashMessage('Album přidáno.', 'success');
            $this->redirect('list');

	    } catch(Nette\Database\UniqueConstraintViolationException $e) {
			$form['data']['url']->addError('Album s tímto URL již existuje, zvolte prosím jiné.'); 
		}
    }
    
    public function albumFormUpdate(\Nette\Forms\Controls\SubmitButton $button) {
        $form = $button->form;
        $values = $form->getValues(); 
        
		try	{
        	$this->model->update($this->record->id, $values['data']);
	        $this->flashMessage('Album aktualizováno.', 'success');
			$this->redirect('list');
        }	
        catch(Nette\Database\UniqueConstraintViolationException $e) {
			$form->addError('Album s tímto URL již existuje, zvolte prosím jiné.');
		}        
    }    
}
