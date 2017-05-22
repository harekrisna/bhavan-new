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
    public $category_id = "all";
    
    /** @var object */
    private $record;
    
    /** @var object */
    private $model;
    
    protected function startup()  {
        parent::startup();
		$this->model = $this->article;
		
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function beforeRender() {	    
        $this->template->categories = $this->articleCategory->findAll();
    }

	function renderList($category_id) {
		$this->category_id = $category_id;
		
		if($category_id != "all") {
			$this->template->articles = $this->article->findBy(['category_id' => $category_id])
													->order('created DESC');
		}
		else {
			$this->template->articles = $this->article->findAll()
												 	  ->order('created DESC');
		}
	}
	
	public function renderAdd() {
		$this->setView("form");    			
		$this->template->form_title = "Nový článek:";
		
		$_SESSION['KCFINDER'] = [
			'disabled' => false,
			'_check4htaccess' => false,
			'uploadURL' => "../../images/kcfinder/teaching"
		];
	}

	function actionEdit($article_id) {
		$this->record = $this->model->get($article_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
		$this["articleForm"]['data']->setDefaults($this->record);
	}

	function renderEdit($article_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit článek:";
		$this->template->record = $this->record;
		
		$_SESSION['KCFINDER'] = [
			'disabled' => false,
			'_check4htaccess' => false,
			'uploadURL' => "../../images/kcfinder/teaching"
		];
	}
	


	public function handleDelete($id) {
		$record = $this->model->get($id);

		if (!$record)
            throw new Nette\Application\BadRequestException;
        
   		$record->delete($id);		
		$this->payload->success = TRUE;
		$this->sendPayload();
        $this->terminate();	                
    }
	
	


    protected function createComponentArticleForm(){
		$form = new Nette\Application\UI\Form();
		$data = $form->addContainer('data');
		
	    $data->addText('title', 'Název článku:', 55, 255)
			 ->setRequired('Zadejte prosím název.');
			 
	    $data->addText('url', 'URL článku:', 40)
      	     ->setRequired('Zadejte prosím URL článku.');
	       			 
	    $data->addSelect('category_id', 'Kategorie:', $this->articleCategory->findAll()->fetchPairs('id', 'title'));
	    										   
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
		$new_row = $this->article->insert($values['data']);											 
		
		if($new_row) {
			$this->flashMessage('Článek přidán.', 'success');
            $this->redirect('list');
		}
		else {
			$form->addError('Článek s tímto URL již existuje, zvolte prosím jiné.');
		}
    }
    
	public function articleFormUpdate(\Nette\Forms\Controls\SubmitButton $button)	{
		if (!$this->record)
            throw new Nette\Application\BadRequestException;

        $form = $button->form;
        $values = $form->getValues();
        
		$update = $this->article->update($this->record->id, 
									   $values->data);
		
		if($update !== false) {
            $this->flashMessage('Článek upraven.', 'success');
            $this->redirect('list');
		}
		else {
			$form->addError('Článek s tímto URL již existuje, zvolte prosím jiné.');
		}
    }
}
