<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Nette\Utils\Image;
use Tracy\Debugger;

final class CollectionPresenter extends BasePresenter {        
    
    /** @var object */
    private $record;
    
    private $model;
    
    protected function startup()  {
        parent::startup();
   		$this->model = $this->collection;
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    	
	function renderList() {
		$this->template->collections = $this->model->findAll();	
	}
	
		
	function renderAdd() {
		$this->setView("form");
		$this->template->form_title = "Nová kolekce";
	}
	
		
	public function actionEdit($record_id) {
		$this->record = $this->model->get($record_id);
		
		if (!$this->record)
            throw new Nette\Application\BadRequestException("Kolekce nenalezena.");
			
        $this->template->record = $this->record;
        $this["collectionForm"]['data']->setDefaults($this->record);
	}

	public function renderEdit($record_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit kolekci";
	}
	
	public function handleRemove($record_id) {
		$this->record = $this->model->get($record_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException("Kolekce nenalezena.");
        else {
   			$this->model->delete($this->record->id);
   			$this->flashMessage("Kolekce byla odstraněna.", "success");
			$this->payload->success = TRUE;
			$this->sendPayload();
	        $this->terminate();	           
        } 
	}
	
    protected function createComponentCollectionForm() {
		$form = new Nette\Application\UI\Form();

	   	$data = $form->addContainer('data');
		
	    $data->addText('title', 'Název kolekce', 55, 255)
			 ->setRequired('Zadejte prosím název.');
			 
	    $data->addText('url', 'URL', 55, 255)
			 ->setRequired('Zadejte prosím URL.');
  	    
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'collectionFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'collectionFormUpdate');
		     
        return $form;
    }	
    
    public function collectionFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
        $data = $values->data;

		try {
			$new_row = $this->model->insert($data);

            $this->flashMessage('Kolekce přidána.', 'success');
            $this->redirect('list');

	    } catch(Nette\Database\UniqueConstraintViolationException $e) {
			$form['data']['url']->addError('Kolekce s tímto URL již existuje, zvolte prosím jiný.'); 
		}
    }
    
    
    public function collectionFormUpdate(\Nette\Forms\Controls\SubmitButton $button) {
        $form = $button->form;
        $values = $form->getValues(); 
        
		try	{
        	$this->model->update($this->record->id, $values['data']);
	        $this->flashMessage('Kolekce aktualizován.', 'success');
			$this->redirect('list');
        }	
        catch(Nette\Database\UniqueConstraintViolationException $e) {
			$form->addError('Kolekce s tímto URL již existuje, zvolte prosím jiné.');
		}        
    }    
}
