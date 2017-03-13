<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\DateTime;
use Nette\Database\SqlLiteral;



final class SlidesPresenter extends BasePresenter {        
    
    /** @var object */
    private $record;
    
    /** @var object */
    private $model;
    
    protected function startup()  {
        parent::startup();
		$this->model = $this->slide;
		
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function beforeRender() {
        $this->template->wide = true;
    }

	function renderList() {
		$this->template->slides = $this->model->getAll()
											  ->order('position');
											  
		$this->template->max_position = $this->model->getAll()
													->max('position');
		
		$this->template->now = new DateTime('now');
	}

	function actionAdd() {
		$slides = $this->model->getAll()
							  ->order('position')
							  ->fetchPairs('position', 'title');
							  
		$pairs = [1 => "na začátek"];
		
		foreach($slides as $position => $title) {
			$pairs[$position + 1] = "za ". $title;
		}

		$this['slideForm']['file']->setRequired('Vyberte prosím obrázek.');
	    $this['slideForm']->addSelect('position', 'Pořadí:');
		$this['slideForm']['position']->setItems($pairs);
	}
	
	function renderAdd() {
		$this->setView("slideForm");		
		$this->template->form_title = "Nový slide:";
	}
	
	function actionEdit($slide_id) {
		$this->record = $this->model->get($slide_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
		$this["slideForm"]->setDefaults($this->record);
	}

	function renderEdit($slide_id) {
		$this->setView("slideForm");
		$this->template->form_title = "Upravit slide";
		$this->template->slide = $this->record;
	}
	
	function actionRemove($slide_id) {
		$slide = $this->model->get($slide_id);

		$this->model->getAll()
		            ->where('position > ?', $slide->position)
					->update(['position' => new SqlLiteral("position - 1")]);

		$this->model->delete($slide_id);
		@unlink("images/slides/{$slide->file}");
		$this->flashMessage("Slide odstaněn", "success");
		$this->redirect("list");
	}	

    protected function createComponentSlideForm(){
	   	$form = new Nette\Application\UI\Form();

	    $form->addText('title', 'Název:', 30, 255)
      	     ->setRequired('Zadejte prosím název slidu.');
		
		$form->addUpload('file', 'Obrázek:')
			 ->addCondition($form::FILLED)
				 ->addRule($form::IMAGE, 'Obrázek musí být JPEG, PNG nebo GIF');

		$actualities = $this->actuality->findAll()
									   ->order('actuality_date DESC');

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
		
	    $form->addText('delay', 'Prodleva:', 5, 3)
	    	 ->setDefaultValue('4')
			 ->addCondition($form::FILLED)
				 ->addRule($form::FLOAT, 'Zpoždění musí být číslo.');
		  	 
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'slideFormInsert');

        $form->addSubmit('update', 'Uložit')
   		     ->onClick[] = array($this, 'slideFormUpdate');
		     
        return $form;
    }
    
    public function slideFormInsert(\Nette\Forms\Controls\SubmitButton $button)   {
        $form = $button->form;
        $values = $form->getValues();
        $file = $values->file;

        if($file->isOk()) {
			$uploaded = pathinfo($file->getName());
			$new_basename = $uploaded['basename'];
			$new_filename = $uploaded['filename'];

            $is_in_folder = Finder::findFiles($new_basename)->in("images/slides")
            												->count();

			while($is_in_folder) {  // souboru s tímto názvem již existuje
                $new_filename = $this->common->incrementSuffixIndex($new_filename);
				$new_basename = $new_filename.".".$uploaded['extension'];
                $is_in_folder = Finder::findFiles($new_basename)->in("images/slides")
	            												->count();
			}
            
	        $image = $file->toImage();
			$image->resize(1980, 1052, Image::STRETCH);
			$image->save("images/slides/".$new_basename);
			
			$values->file = $new_basename;
        }

        $this->model->getAll()->where('position >= ?', $values->position)
	                          ->update(['position' => new SqlLiteral("position + 1")]);
		
		unset($values['select_target']);					  
		$this->model->insert($values);

        $this->redirect("list");
    }
    
    public function slideFormUpdate(\Nette\Forms\Controls\SubmitButton $button) {
		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
        $form = $button->form;
        $values = $form->getValues(); 
        $file = $values->file;
		
        if($file->isOk()) {
            $uploaded = pathinfo($file->getName());
			$new_basename = $uploaded['basename'];
			$new_filename = $uploaded['filename'];
            $db_basename = $this->record->file;
			                        

            $is_in_folder = Finder::findFiles($new_basename)->in("images/slides")
            														->count();

			while($is_in_folder) {  // souboru s tímto názvem již existuje
				if($new_basename == $db_basename) { // souboru patří slidu (můžeme přepsat)
					break;
				}
				else { // souboru nepatři slidu (nesmíme přepsat)
	                $new_filename = $this->common->incrementSuffixIndex($new_filename);
					$new_basename = $new_filename.".".$uploaded['extension'];
	                $is_in_folder = Finder::findFiles($new_basename)->in("images/slides")
		            												->count();
				}
			}
            
	        $image = $file->toImage();
			$image->resize(1980, 1052, Image::STRETCH);
			@unlink("images/slides/".$db_basename);
			$image->save("images/slides/".$new_basename);
			$this->model->update($this->record->id, array("file" => $new_basename));
        }
                
        $this->model->update($this->record->id, ["title" => $values->title,
			 									 "target" => $values->target,
			 									 'show_from' => $values->show_from,
			 									 'show_to' => $values->show_to,
			 									 'delay' => $values->delay,
			 									]);
        
        $this->flashMessage('Slide uložen.', 'success');
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
