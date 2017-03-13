<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Diagnostics\Debugger;

final class AdminTestPresenter extends BasePresenter {        
    
    protected function startup()  {
        parent::startup();
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function beforeRender() {
        $this->template->menu = array("addSlide" => "Test");
    }

	protected function createComponentGaleryForm(){
	   	$form = new Nette\Application\UI\Form();
	
      	$form->addUpload('galery_image', 'Obrázek galerie:')
 	  		 ->setRequired('Vyberte obrázek galerie.');
                
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'galeryFormInsert');

        $form->addSubmit('update', 'Uložit')
   		     ->onClick[] = array($this, 'galeryFormUpdate');
		     
        return $form;
    }
    
        public function galeryFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{

        $form = $button->form;
        $values = $form->getValues();
        
		Debugger::FireLog($values['galery_image']);
    }

	function renderDefault() {
		
	}

}
