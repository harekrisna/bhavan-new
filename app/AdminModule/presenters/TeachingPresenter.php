<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Nette\Application\UI\Form;
use Tracy\Debugger;
use Nette\Utils\Image;
use Nette\Database\SqlLiteral;

final class TeachingPresenter extends BasePresenter {        
	/** @persistent */
    public $category_id;
    
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
		if($category_id == null) {
			$category_id = $this->articleCategory->findAll()
												 ->order('id')
												 ->fetch()
												 ->id;
		}
			
		$this->category_id = $category_id;
		$this->template->articles = $this->article->findBy(['category_id' => $category_id])
												  ->order('position DESC');
		
		$this->template->max_position = $this->model->findBy(['category_id' => $category_id])
													->max('position');
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

		$this->model->findBy(['category_id' => $record->category_id])
					->where('position > ?', $record->position)
					->update(['position' => new SqlLiteral("position - 1")]);

   		$record->delete();
   		@unlink(ARTICLES_IMG_FOLDER."/previews/".$record->preview_image);
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
		
		$form->addUpload('preview_image', 'Obrázek:')
			 ->addCondition($form::FILLED)
				 ->addRule($form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF');

	    $data->addSelect('category_id', 'Kategorie:', $this->articleCategory->findAll()->fetchPairs('id', 'title'));
	    										   
	    $data->addTextArea('text', 'Text:', 40);
	    //$data->addCheckbox('main', 'Zvýraznit');

        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'articleFormInsert');

        $form->addSubmit('update', 'Uložit')
   	     	 ->onClick[] = array($this, 'articleFormUpdate');
		     
        return $form;
    }
    
    public function articleFormInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
        $preview_image = $values->preview_image;
        $data = $values['data'];
        $max_position = $this->model->findBy(['category_id' => $data['category_id']])
        							->max('position');

		$data['position'] = $max_position + 1;

		$new_row = $this->article->insert($data);
		
		if($new_row) {
			if($preview_image->isOk()) {
				$new_file = $new_row->id."_".$preview_image->getSanitizedName();
	            
		        $image = $preview_image->toImage();
				$image->resize(200, 200, Image::EXACT);
				$image->save(ARTICLES_IMG_FOLDER."/previews/".$new_file);
				
				$this->model->update($new_row->id, ['preview_image' => $new_file]);
	        }

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
        $preview_image = $values->preview_image;
        
        $update = $this->article->update($this->record->id, 
									   	 $values->data);

        if($preview_image->isOk()) {            
			$new_file = $this->record->id."_".$preview_image->getSanitizedName();

	        $image = $preview_image->toImage();
			$image->resize(200, 200, Image::EXACT);
			$image->save(ARTICLES_IMG_FOLDER."/previews/".$new_file);
			@unlink(ARTICLES_IMG_FOLDER."/previews/".$this->record->preview_image);
			
			$this->model->update($this->record->id, ['preview_image' => $new_file]);
        }
		
		if($update !== false) {
            $this->flashMessage('Článek upraven.', 'success');
            $this->redirect('list');
		}
		else {
			$form->addError('Článek s tímto URL již existuje, zvolte prosím jiné.');
		}
    }
    
	function actionUpdatePosition($article_id, $new_position) {
		$article = $this->article->get($article_id);
        $old_position = $article->position;
        $category_id = $article->category_id;
		
        if($old_position != $new_position) {
            $max_position = $this->article->findAll()
            							  ->where(['category_id' => $category_id])
										  ->max('position');
            
            $this->article->update($article_id, ['position' => $new_position]);
            $sign = $old_position < $new_position ? "-" : "+";
            $this->article->findAll()
                          ->where("id != ? AND position BETWEEN ? AND ?", $article_id, min($old_position, $new_position), max($old_position, $new_position))
						  ->where(['category_id' => $category_id])
                          ->update(["position" => new SqlLiteral("position {$sign} 1")]);
        }
        
        $this->redirect('list', $category_id);
	}
}
