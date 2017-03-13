<?php

namespace App\AdminModule\Presenters;
use Nette,
	App\Model;
use Tracy\Debugger;
use Nette\Application\UI\Form;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\Strings;
use Nette\Database\SqlLiteral;

final class GaleryPresenter extends BasePresenter {
    
    /** @var object */
    private $record;

     /** @var object */
    private $model;   	    
    
    private $galery_dir = "./images/galery";
    
    protected function startup()  {
        parent::startup();
		$this->model = $this->galery;
    
        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
    
    public function beforeRender() {
		$this->template->wide = true;

		$this->template->addFilter('dynamicDate', function ($date) {
			if(strlen($date) == 4)
				return $date;
			elseif(strlen($date) == 7)
				return date("m.Y", strtotime($date));
			elseif (strlen($date) == 10)
				return date("d.m.Y", strtotime($date));
		});		
    }

	function renderList() {
			$this->template->galeries = $this->galery->getAll()
													 ->order('time_created DESC');
	}

	function renderDetail($galery_id) {
		$galery = $this->galery
		   		       ->get($galery_id);
		
		$this->template->images_dir_count = Finder::findFiles('*.jpg', '*.jpeg', '*.png', '*.gif')->in("./images/galery/{$galery->photos_folder}/photos")->count();
		
		$this->template->photos = $this->photo
									   ->getAll()
									   ->where(array("galery_id" => $galery_id))
									   ->order("position");;
						
		$this->template->galery = $galery;
	}
	
	function actionAddGalery() {
		$this['form']['galery_image']->setRequired('Vyberte prosím obrázek.');
		$this['form']['photos_folder']->setRequired('Zadejte prosím adresář s fotkami.');
	}
	
	function renderAddGalery() {
		$this->setView("form");
		$this->template->form_title = "Nová galerie";
	}
	
	function actionEdit($record_id) {
		$this->record = $this->model->get($record_id);

		if (!$this->record)
            throw new Nette\Application\BadRequestException;
            
		$this['form']['data']->setDefaults($this->record);
	}
	
	function renderEdit($record_id) {
		$this->setView("form");
		$this->template->form_title = "Upravit galerii";
		$this->template->record = $this->record;
	}

    protected function createComponentForm(){
	   	$form = new Nette\Application\UI\Form();
	   	
	   	$data = $form->addContainer('data');
	   	
	    $data->addText('title', 'Název:', 40, 255)
      	     ->setRequired('Zadejte prosím název galerie.');
      	     
	    $data->addTextArea('description', 'Popis:', 40);
		
		$days['0'] = "";
		$days = $days + $this->galery->generateNumberArray(1, 31);
		$months['0'] = "";
		$months = $months + $this->galery->generateNumberArray(1, 12);
		$years = $this->galery->generateNumberArray(2000, 2020);
				
	    $data->addSelect('galery_day', 'Den:', $days);
        $data->addSelect('galery_month', 'Měsíc:', $months);
	    $data->addSelect('galery_year', 'Rok:', $years)
	    	 ->setRequired('Zadejte alespoň rok.');

	    $form->addText('photos_folder', 'Název adresáře:', 40);
      	
	    $data->addText('url', 'URL galerie:', 40)
      	     ->setRequired('Zadejte prosím URL galerie.');

      	$form->addUpload('galery_image', 'Obrázek galerie:')
             ->addCondition(Form::IMAGE)
			 	->addRule(Form::MIME_TYPE, 'Soubor musí být obrázek typu JPEG', array('image/jpeg'));	  		 
        
        $form->addCheckbox('add_to_news', "Přidat no novinek?");
                
        $form->addSubmit('insert', 'Uložit')
		     ->onClick[] = array($this, 'formInsert');

        $form->addSubmit('update', 'Uložit')
   		     ->onClick[] = array($this, 'formUpdate');
		     
        return $form;
    }
    
    public function formInsert(\Nette\Forms\Controls\SubmitButton $button)	{
        $form = $button->form;
        $values = $form->getValues();
        $data = $values->data;

        $data->offsetSet('photos_folder', $data->galery_year."/".$values->photos_folder);
        
        $year_folder = $this->galery_dir."/".$values->data->galery_year;
		if(!file_exists($year_folder)) {
			mkdir($year_folder);
			chmod($year_folder, 0777);
		}
        
        $galery_folder = $this->galery_dir."/".$data->galery_year."/".$values->photos_folder;

        if(file_exists($galery_folder)) {
	        $form['photos_folder']->addError('Galerie s tímto adresářem již existuje, zvolte prosím jiný.');
        }
        else {		
			$galery_row = $this->galery
                			   ->insert($data);
			
			if($galery_row) {
				mkdir($galery_folder);
				chmod($galery_folder, 0777);
				mkdir($galery_folder."/photos");
				chmod($galery_folder."/photos", 0777);
				mkdir($galery_folder."/previews");
				chmod($galery_folder."/previews", 0777);
				
	        	if($values['galery_image']->isOK()) {
					$image = $values['galery_image']->toImage();
					$image->resize(200, 200, Image::EXACT);
					$image->save($galery_folder."/galery_image.jpg", 80);
					chmod($galery_folder."/galery_image.jpg", 0777);
	        	}				
				
				if($values['add_to_news']) {
					$galery_image_source = $galery_folder."/galery_image.jpg";
					$target_link = $this->link(":Galery:photos", $galery_row->id);
					$this->news->addNew("Foto", $data['title'], $galery_image_source, $target_link);
				}
				
	            $this->flashMessage('Galerie přidána.', 'success');
	            $this->redirect('list');
			}
			else {
	     	   $form['photos_folder']->addError('Galerie s tímto URL již existuje, zvolte prosím jiný.');
	     	} 
        }        
    }
    
    
    public function formUpdate(\Nette\Forms\Controls\SubmitButton $button) {
        $form = $button->form;
        $values = $form->getValues(); 

        $ok = $this->galery
             	   ->update($this->record->id, $values['data']);
        
        if($ok === false) {
	        $form->addError('Galerie s tímto URL již existuje, zvolte prosím jiné.');
        }
        else {
		    if($values['galery_image']->isOK()) {
				$image = $values['galery_image']->toImage();
				$image->resize(200, 200, Image::EXACT);
				$image->save($this->galery_dir."/".$this->record->photos_folder."/galery_image.jpg", 80);
				chmod($this->galery_dir."/".$this->record->photos_folder."/galery_image.jpg", 0777);
	    	}	
	        
	        $this->flashMessage('Galerie aktualizována.', 'success');
			$this->redirect('list');
        }
    }
	
	
	public function handleDeleteGalery($galery_id) {
    	$galery = $this->galery->get($galery_id);
    	if(is_dir("images/galery/{$galery->photos_folder}/photos")) {
			foreach (Finder::findFiles('*')->in("images/galery/{$galery->photos_folder}/photos") as $file_path => $file) {
				unlink($file_path);
			}
		}

		if(is_dir("images/galery/{$galery->photos_folder}/previews")) {
			foreach (Finder::findFiles('*')->in("images/galery/{$galery->photos_folder}/previews") as $file_path => $file) {
				unlink($file_path);
			}
		}

		@unlink("images/galery/{$galery->photos_folder}/galery_image.jpg");
		@rmdir("images/galery/{$galery->photos_folder}/photos");
		@rmdir("images/galery/{$galery->photos_folder}/previews");
		@rmdir("images/galery/{$galery->photos_folder}");
		
		$this->galery->delete($galery_id);
		$this->payload->success = TRUE;
		$this->sendPayload();
        $this->terminate();	                
    }

	function handleChangeActivation($galery_id, $status) {
		$stats_convert = ['active' => 1, 'inactive' => 0];
		$this->galery->update($galery_id, array("active" => $stats_convert[$status]));
		$this->payload->status = $stats_convert[$status];
		$this->sendPayload();
	}

	function handleUploadFile($galery_id) {		
		$fileTypes = array('jpg', 'jpeg', 'gif', 'png'); // Allowed file extensions
   		
		$verifyToken = md5('unique_salt' . $_POST['timestamp']);
		
		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
			
			$fileParts = pathinfo($_FILES['Filedata']['name']);
			
			if (in_array(strtolower($fileParts['extension']), $fileTypes)) {
				$galery = $this->galery->get($galery_id);
				$tempFile   = $_FILES['Filedata']['tmp_name'];
				$uploadDir  = $this->galery_dir."/photos";
				$targetFile = Strings::webalize($fileParts['filename']).".".$fileParts['extension'];
				
				$image = Image::fromFile($tempFile);

				$image->resize(NULL, 1200, Image::SHRINK_ONLY);
				$image->save($this->galery_dir."/".$galery->photos_folder."/photos/{$targetFile}");
				chmod($this->galery_dir."/".$galery->photos_folder."/photos/{$targetFile}", 0777);
                $filesize = filesize($this->galery_dir."/".$galery->photos_folder."/photos/{$targetFile}");
                
                $new_width = $image->width;
                $new_height = $image->height;
                
                $image->resize(NULL, 240);
				$image->save($this->galery_dir."/".$galery->photos_folder."/previews/{$targetFile}");
				chmod($this->galery_dir."/".$galery->photos_folder."/previews/{$targetFile}", 0777);

                unset($image);
                								
				$photo = $this->photo->findBy(['file' => $targetFile,
											   'galery_id' => $galery_id]);
											   
				$max_position = $this->photo->findAll()
											->where(['galery_id' => $galery_id])
                                            ->max('position');
				
				if($photo->count() == 0) { // fotka s tímto názvem souboru neexistuje, vloží se nakonec				
    				$new_photo = $this->photo->insert(["file" => $targetFile,
    												   'galery_id' => $galery_id,
    						 						   "width" => $new_width,
    												   "height" => $new_height,
    												   "position" => $max_position + 1,
                                                     ]);

                    Debugger::enable(Debugger::PRODUCTION); // háček kvůli tomu, aby se v ajaxové odpovědi neodesílal debug bar
                    $this->setView("photo-box");
                    $this->template->photo = $this->photo->get($new_photo->id);
				}
				
				else { // fotka s tímto názvem souboru existuje, aktualizuje se    				
    				$photo->update(['width' => $new_width,
    				                'height' => $new_height,
                                  ]);
    				                                          
                    //$this->handleUpdatePosition($new_photo->id, $max_position);
                    $photo = $photo->fetch();
                    $this->payload->photo = $this->photo->get($photo->id)
                                                        ->toArray();
                    
                    $filesize = \Latte\Runtime\Filters::bytes(filesize($this->galery_dir."/".$galery->photos_folder."/photos/".$targetFile));
                    $this->payload->filesize = $filesize;
                    $this->payload->file_path = $this->galery_dir."/".$galery->photos_folder."/previews/{$targetFile}";
                    
            		$this->payload->images_dir_count = Finder::findFiles('*.jpg', '*.jpeg', '*.gif', '*.png')->in($this->galery_dir."/".$galery->photos_folder."/photos")
            		                                                                                         ->count();
            		$this->payload->images_db_count = $this->photo->findAll()
            													  ->where(['galery_id' => $galery_id])
            		                                              ->count();
                    $this->sendPayload();
                    
				}
			}
		}
	}


	function handleRemovePhoto($photo_id) {
		$photo = $this->photo->get($photo_id);
		$dir = $this->galery_dir."/".$photo->galery->photos_folder;
		
		@unlink($dir."/photos/".$photo->file);
		@unlink($dir."/previews/".$photo->file);

		$this->photo->findAll()
		            ->where('position > ?', $photo->position)
					->where(['galery_id' => $photo->galery_id])
                    ->update(["position" => new SqlLiteral("position - 1")]);

		$this->photo->delete($photo_id);
		$this->payload->images_dir_count = Finder::findFiles('*.jpg', '*.jpeg', '*.gif', '*.png')->in($dir."/photos")
		                                                                                         ->count();
		$this->payload->images_db_count = $this->photo->findAll()
													  ->where(['galery_id' => $photo->galery_id])		
		                                              ->count();
		$this->payload->success = true;
		$this->sendPayload();
		$this->terminate();
	}

	function actionGeneratePhotos($galery_id) {
		$galery = $this->galery->get($galery_id);
		$galery_dir = $this->galery_dir."/".$galery->photos_folder;
		$galery->related("photo", "galery_id")->delete();

		foreach (Finder::findFiles('*.jpg', '*.jpeg', '*.png', '*.gif')->in($galery_dir."/previews") as $file_path => $file) {
			unlink($file_path);
		}
		
		foreach (Finder::findFiles('*.jpg', '*.jpeg', '*.png', '*.gif')->in($galery_dir."/photos") as $file_path => $file) {			
			$image = Image::fromFile($file_path);
			
			$pathinfo = pathinfo($file_path);
			
			$image->resize(NULL, 1200);
			unlink($galery_dir."/photos/".$pathinfo['basename']);
			$webalize_basename = Strings::webalize($pathinfo['filename']).".".$pathinfo['extension'];
			$image->save($galery_dir."/photos/".$webalize_basename);
			chmod($galery_dir."/photos/".$webalize_basename, 0777);
	
			$max_position = $this->photo->findAll()
										->where(['galery_id' => $galery_id])
                                        ->max('position');

			$this->photo->insert(array("file" => $webalize_basename,
									   "galery_id" => $galery_id,
									   "width" => $image->width,
									   "height" => $image->height,
									   "position" => $max_position + 1,
									  ));

			$image->resize(NULL, 240);
			$image->save($galery_dir."/previews/".basename($file_path));
			
			unset($image);
		}
		
		$this->redirect("detail", $galery_id);
	}
	
	function actionSortPhotos($galery_id) {
		$galery = $this->galery->get($galery_id);
		$folder = $galery->photos_folder;
		$photos = $this->photo->findAll()
							  ->where(['galery_id' => $galery_id])
							  ->order('CONCAT(REPEAT("0", 18 - LENGTH(file)), file)');
		
		$position = 1;
		foreach($photos as $photo) {
			$this->photo->update($photo->id, ['position' => $position]);
			$position++;
		}
				
		$this->redirect("detail", $galery_id);
	}	
	
	
	
	function handleUpdateDescription($photo_id, $text) {
    	$this->photo->update($photo_id, ["description" => $text]);
    	$this->sendPayload();
	}	
	
	function handleUpdatePosition($galery_id, $photo_id, $new_position) {
    	$this->setView("galery");
        $old_position = $this->photo->get($photo_id)
                                    ->position;
		
        if($old_position != $new_position) {
            $max_position = $this->photo->findAll()
            							->where(['galery_id' => $galery_id])
                                        ->max('position');
            
            $this->photo->update($photo_id, ['position' => $new_position]);
            $sign = $old_position < $new_position ? "-" : "+";
            $this->photo->findAll()
                        ->where("id != ? AND position BETWEEN ? AND ?", $photo_id, min($old_position, $new_position), max($old_position, $new_position))
						->where(['galery_id' => $galery_id])
                        ->update(["position" => new SqlLiteral("position {$sign} 1")]);
        }
        
        $this->sendPayload();
	}	
}
