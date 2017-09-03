<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\DateTime;
use Nette\Database\SqlLiteral;



final class NewsPresenter extends BasePresenter {        
    
    /** @var object */
    private $record;
    
    /** @var object */
    private $model;
    
    /** @var string */
	private $images_dir = "images/news_thumbs";
	
    protected function startup()  {
        parent::startup();
		$this->model = $this->news;
		
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function beforeRender() {
        $this->template->wide = true;
    }

	function renderList() {
		
		$news = $this->model->getAll()
						  	->order('date_from DESC');
		
		$events = $photos = $audios = [];						  	
			
		foreach($news as $new) {
			$container = $new->news_type->type."s";
			$row = $new->toArray();
		
			if($new->target != "" && strpos($new->target, "http://") === FALSE) {
				$row['target'] = "http://www.bhavan.cz/".$new->target;
			}
			else {
				$row['target'] = "";
			}
			
			array_push($$container, $row);
		}
		
		$this->template->events = $events;
		$this->template->photos = $photos;	  
		$this->template->audios = $audios;
	}


	function actionAdd() {		
		$this['form']['file']->setRequired('Vyberte prosím obrázek.');
	}

	
	function renderAdd() {
		$this->setView("form");		
		$this->template->form_title = "Nová novinka:";
	}
	
	function actionEdit($record_id) {
		$this->record = $this->model->get($record_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
		$this["form"]->setDefaults($this->record);
	}

	function renderEdit($record_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit novinku:";
		$this->template->record = $this->record;
	}
	
	function actionRemove($record_id) {
		$slide = $this->model->get($record_id);

		$this->model->delete($record_id);
		@unlink($this->images_dir."/".$record->file);
		$this->flashMessage("Novinka odstaněna.", "success");
		$this->redirect("list");
	}	

    protected function createComponentForm(){
	   	$form = new Nette\Application\UI\Form();

		$new_types = $this->newsType->getAll()
									 ->fetchPairs('id', 'title');
							  

	    $form->addSelect('news_type_id', 'Typ:', $new_types);

	    $form->addText('title', 'Název:', 30, 255)
      	     ->setRequired('Zadejte prosím název novinky.');

		$form->addDateTimePicker('date_from', 'Datum od:', 10);
		$form->addDateTimePicker('date_to', 'Datum do:', 10);
				
		$form->addUpload('file', 'Obrázek:')
			 ->addCondition($form::FILLED)
				 ->addRule($form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF');

		$actualities = $this->actuality->findAll()
									   ->order('date_from DESC');

		foreach($actualities as $actuality) {
			$url = "aktuality/".$actuality->url;
			$actuality_pairs[$url] = $actuality->title;
		}
										   
		$galeries = $this->galery->findAll()
								 ->order('galery_year DESC, galery_month DESC, galery_day DESC');

		foreach($galeries as $galery) {
			$url = "fotky/".$galery->url;
			$galery_pairs[$url] = $galery->title;
		}
											 
		$interprets = $this->interpret->findAll();

		foreach($interprets as $interpret) {
			$url = "audio/".$interpret->url;
			$interpret_pairs[$url."/live"] = $interpret->title." - živé lekce";
			$interpret_pairs[$url."/record"] = $interpret->title." - záznam";
		}
		
		$pairs = ["Aktuality" => $actuality_pairs,
				  "Galerie" => $galery_pairs,
				  "Audio" => $interpret_pairs,
				 ];
										   
	    $form->addSelect('select_target', 'Vybrat:', $pairs)
	    	 ->setPrompt("- cíl odkazu -");
	    	 
	    $form->addText('target', 'Cíl odkazu:', 40);

		$form->addDateTimePicker('show_from', 'Zobrazovat od:', 16);
		$form->addDateTimePicker('show_to', 'Zobrazovat do:', 16);
		  	 
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'formInsert');

        $form->addSubmit('update', 'Uložit')
   		     ->onClick[] = array($this, 'formUpdate');
		     
        return $form;
    }
    
    public function formInsert(\Nette\Forms\Controls\SubmitButton $button)   {
        $form = $button->form;
        $values = $form->getValues();
        $file = $values->file;

        if($file->isOk()) {
			$new_row = $this->model->insert($values);
			
			if($new_row) {
				$new_file = $new_row->id."-".$file->getSanitizedName();
	            
		        $image = $file->toImage();
				$image->resize(130, 130, Image::EXACT);
				$image->save($this->images_dir."/".$new_file);
				
				$this->model->update($new_row->id, ['file' => $new_file]);
			}
        }

        $this->redirect("list");
    }
    
    public function formUpdate(\Nette\Forms\Controls\SubmitButton $button) {
		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
        $form = $button->form;
        $values = $form->getValues(); 
        $file = $values->file;
		
        if($file->isOk()) {            
			$new_file = $this->record->id."-".$file->getSanitizedName();

	        $image = $file->toImage();
			$image->resize(130, 130, Image::EXACT);
			$image->save($this->images_dir."/".$new_file);
			@unlink($this->record->file);
			
			$this->model->update($this->record->id, ['file' => $new_file]);
        }
                
        $this->model->update($this->record->id, ['title' => $values->title,
												 'news_type_id' => $values->news_type_id,
												 'date_from' => $values->date_from,
			 									 'date_to' => $values->date_to,
			 									 'target' => $values->target,
			 									 'show_from' => $values->show_from,
			 									 'show_to' => $values->show_to,
			 									]);
        
        $this->flashMessage('Novinka uložena.', 'success');
        $this->redirect('list');
    }
	
	function handleUpdatePosition($record_id, $new_position) {
        $old_position = $this->model->get($record_id)
                                    ->position;
                             
        if($old_position != $new_position) {
            $max_position = $this->model->getAll()
                                        ->max('position');
            
            $this->model->get($record_id)
            			->update(['position' => $new_position]);
            			
            $sign = $old_position < $new_position ? "-" : "+";
            $this->model->getAll()
                        ->where("id != ? AND position BETWEEN ? AND ?", $record_id, min($old_position, $new_position), max($old_position, $new_position))
                        ->update(["position" => new SqlLiteral("position {$sign} 1")]);
        }
        
        $this->redirect('list');
	}
}
