<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Application\UI\Form;

final class ActualityPresenter extends BasePresenter {        
    
    /** @var object */
    private $actuality_record;
    
    protected function startup()  {
        parent::startup();
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    
    public function beforeRender() {
		$this->template->wide = true;;
    }

	public function renderList() {		
		$this->template->actualities = $this->actuality->getAll()
													   ->order("date_from ASC");
	}
	
	public function renderDetail($actuality_id) {
		$this->template->actuality = $this->actuality->get($actuality_id);
	}
	
	public function renderAdd() {
		$this->setView("form");
		$this->template->form_title = "Nová aktualita:";
		$this->template->form_type = "add";
		
		$_SESSION['KCFINDER'] = [
			'disabled' => false,
			'_check4htaccess' => false,
			'uploadURL' => "../../images/kcfinder/actuality"
		];
	}
	
	public function actionEdit($actuality_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit aktualitu:";
		$this->template->form_type = "edit";
		
		$this->actuality_record = $this->actuality->get($actuality_id);

		if (!$this->actuality_record)
            throw new Nette\Application\BadRequestException;
			
        $this->template->actuality = $this->actuality_record;
        $actuality = $this->actuality_record->toArray();
        $this["actualityForm"]->setDefaults($actuality);

		$_SESSION['KCFINDER'] = [
			'uploadURL' => "../../images/kcfinder/actuality",
			'disabled' => false,
			'_check4htaccess' => false,
		];
	}
	
	public function actionRemove($actuality_id) {
		$actuality = $this->actuality->get($actuality_id);

		if (!$actuality)
            throw new Nette\Application\BadRequestException;
        else {
   			$this->actuality->delete($actuality->id);
   			$this->flashMessage("Aktualita byla odstraněna.", "success");
   			$this->redirect("list");
        }    		
	}
	
	
	public function actionDeletePreviewImage($actuality_id) {
		$actuality = $this->actuality->get($actuality_id);

		if (!$actuality)
            throw new Nette\Application\BadRequestException;

		$this->actuality->update($actuality_id, array("preview_image" => NULL));
		unlink("images/actuality/previews/".$actuality->preview_image);	
		$this->redirect("edit", $actuality_id);
	}
	
	
	public function actionDeleteArticleImage($actuality_id) {
		$actuality = $this->actuality->get($actuality_id);

		if (!$actuality)
            throw new Nette\Application\BadRequestException;

		$this->actuality->update($actuality_id, array("article_image" => NULL));
		unlink("images/actuality/".$actuality->article_image);	
		$this->redirect("edit", $actuality_id);
	}
		
		
	public function actionUpdateFromIcloud() {
		$actualities = $this->actuality->findBy(array("source" => "iCloud"));
		foreach($actualities as $actuality) {
			$this->actuality->delete($actuality->id);
		}
															 
		$this->actuality->updateActualityFromiCloud();
		$this->flashMessage("Aktuality byly aktualizovány.", "success");
		$this->redirect("list");
	}
	
	
    protected function createComponentActualityForm(){
		$form = new Nette\Application\UI\Form();
		
	    $form->addText('title', 'Název:', 40, 255)
			 ->setRequired('Zadejte prosím název aktuality.');
      	     
	    $form->addTextArea('description', 'Text:', 40);

	   	$form->addDateTimePicker('date_from', 'Datum od:', 10, 10)
			 ->setRequired('Zadejte prosím alespoň datum aktuality od.');
			 
	   	$form->addDateTimePicker('date_to', 'Datum do:', 10, 10);
					      	     
	    $form->addText('url', 'URL:', 40)
      	     ->setRequired('Zadejte prosím URL aktuality.');

      	$form->addUpload('preview_image', 'Obrázek náhledu:')
	  		 ->addCondition($form::FILLED)
	         	->addRule(Form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF.');

      	$form->addUpload('article_image', 'Obrázek v textu:')
	      	->addCondition($form::FILLED)
		         ->addRule(Form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF.');

		$form->addDateTimePicker('show_from', 'Zobrazovat od:', 16);
		$form->addDateTimePicker('show_to', 'Zobrazovat do:', 16);
		$form->addCheckbox('add_to_news', 'Přidat do novinek?');
		                
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'actualityFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'actualityFormUpdate');
		     
        return $form;
    }
    
	public function actualityFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
		
        $new_row = $this->actuality->insert(array('title' => $values['title'],
											  	  'description' => $values['description'],
											  	  'date_from' => $values['date_from'],
  											  	  'date_to' => $values['date_to'],
											  	  'url' => $values['url'],
											  	  'show_from' => $values['show_from'],
											  	  'show_to' => $values['show_to'],
											  	 ));											 
		if($new_row) {
        	if($values['preview_image']->isOK()) {
				$preview_file_name = $new_row->id."_".$values['preview_image']->name;
				$image = $values['preview_image']->toImage();
				$image->resize(200, 200, $image::EXACT);
				$image->save("images/actuality/previews/".$preview_file_name);
				$this->actuality->update($new_row->id, array("preview_image" => $preview_file_name));
	        }
        	if($values['article_image']->isOK()) {
				$article_file_name = $new_row->id."_".$values['article_image']->name;
				$image = $values['article_image']->toImage();
				$image->resize(400, 540);
				$image->save("images/actuality/".$article_file_name);
				$this->actuality->update($new_row->id, array("article_image" => $article_file_name));
	        }
	        
	        if($values['add_to_news']) {
				$actuality_image_source = "images/actuality/previews/".$preview_file_name;
				$target_link = $this->link(":Actuality:detail", $new_row->id);
				$this->news->addNew("Událost", $values['title'], $actuality_image_source, $target_link, 
									$values['date_from'], $values['date_to'], $values['show_from'], $values['show_to']);
			}            
			
			$this->flashMessage('Aktualita přidána.', 'success');
            $this->redirect('list');
		}
		else {
			$form->addError('Aktualita s tímto URL již existuje, zvolte prosím jiný.');
		}
    }
    
	public function actualityFormUpdate(\Nette\Forms\Controls\SubmitButton $button)	{
		if (!$this->actuality_record)
            throw new Nette\Application\BadRequestException;

        $form = $button->form;
        $values = $form->getValues();

		$change = $this->actuality->update($this->actuality_record->id, 
										   array('title' => $values['title'],
												 'description' => $values['description'],
												 'date_from' => $values['date_from'],
												 'date_to' => $values['date_to'],
												 'url' => $values['url'],
												 'show_from' => $values['show_from'],
												 'show_to' => $values['show_to'],
										   ));
		if($change !== false) {
			if($values['preview_image']->isOK()) {
				if($this->actuality_record->preview_image)
					unlink("images/actuality/previews/".$this->actuality_record->preview_image);
					
				$file_name = $this->actuality_record->id."_".$values['preview_image']->name;
				$image = $values['preview_image']->toImage();
				$image->resize(200, 200, $image::EXACT);
				$image->save("images/actuality/previews/".$file_name);
				$this->actuality->update($this->actuality_record->id, array("preview_image" => $file_name));
	        }
        	if($values['article_image']->isOK()) {
				if($this->actuality_record->article_image)
					unlink("images/actuality/".$this->actuality_record->article_image);
					
				$file_name = $this->actuality_record->id."_".$values['article_image']->name;
				$image = $values['article_image']->toImage();
				$image->resize(400, 540);
				$image->save("images/actuality/".$file_name);
				$this->actuality->update($this->actuality_record->id, array("article_image" => $file_name));
	        }
	        
            $this->flashMessage('Aktualita upravena.', 'success');
            $this->redirect('list');
		}
		else {
			$form->addError('Aktualita s tímto URL již existuje, zvolte prosím jiný.');
		}
    }    
}
