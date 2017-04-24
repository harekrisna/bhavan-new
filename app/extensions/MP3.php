<?php

namespace Extensions;
use Tracy\Debugger;
class MP3
{
    protected static $_id3;

    protected $file;
    protected $id3;
    protected $data     = null;


    protected $info =  ['duration'];
    protected $tags =  ['title', 'artist', 'album', 'year', 'genre', 'comment', 'track', 'attached_picture', 'image'];
    protected $readonly_tags =  ['attached_picture', 'comment', 'image'];
                                //'popularimeter' => ['email'=> 'music@whisppa.com', 'rating'=> 1, 'data'=> 0],//rating: 5 = 255, 4 = 196, 3 = 128, 2 = 64,1 = 1 | data: counter


    public function __construct($file)
    {
        $this->file = $file;
        $this->id3  = self::id3();
    }

    public function update_filepath($file)
    {
        $this->file = $file;
    }

    public function save()
    {
        $tagwriter = new \GetId3\Write\Tags;
        $tagwriter->filename = $this->file;
        $tagwriter->tag_encoding = 'UTF-8';
        $tagwriter->tagformats = ['id3v2.3', 'id3v1'];
        $tagwriter->overwrite_tags = true;
        $tagwriter->remove_other_tags = false;
		
        $tagwriter->tag_data = $this->data;
		
        // write tags
        if ($tagwriter->WriteTags())
            return true;
        else
            throw new \Exception(implode(' : ', $tagwriter->errors));
    }


    public static function id3()
    {
        if(!self::$_id3)
            self::$_id3 = new \GetId3\GetId3Core;

        return self::$_id3;
    }

    public function set_art($data, $mime = 'image/jpeg', $caption = '')
    {
        $this->data['attached_picture'] = [];

        $this->data['attached_picture'][0]['data']            = $data;
        $this->data['attached_picture'][0]['picturetypeid']   = 0x03;    // 'Cover (front)'    
        $this->data['attached_picture'][0]['description']     = $caption;
        $this->data['attached_picture'][0]['mime']            = $mime;

        return $this;
    }

    public function __get($key)
    {
        if(!in_array($key, $this->tags) && !in_array($key, $this->info) && !isset($this->info[$key]))
            throw new \Exception("Unknown property '$key' for class '" . __class__ . "'");
		    
        if($this->data === null)
            $this->analyze();

        if($key == 'image') {
	        if (isset($this->data['attached_picture'])) {
		        $image = [];
		        $image['data'] = $this->data['attached_picture'][0]['data'];
		        if (isset($this->data['attached_picture'][0]['mime'])) {
			        $image['mime'] = $this->data['attached_picture'][0]['mime'];
			    }
			    elseif (isset($this->data['attached_picture'][0]['image_mime'])) {
   			        $image['mime'] = $this->data['attached_picture'][0]['image_mime'];
				}
				
				return $image;
	        }
	        else 
	        	return null;
        }
        else if(isset($this->info[$key]))
            return $this->info[$key];
        else
            return isset($this->data[$key]) ? $this->data[$key][0] : null;
    }

    public function __set($key, $value)
    {
        if(!in_array($key, $this->tags))
            throw new \Exception("Unknown property '$key' for class '" . __class__ . "'");
        if(in_array($key, $this->readonly_tags))
            throw new \Exception("Tying to set readonly property '$key' for class '" . __class__ . "'");

        if($this->data === null)
            $this->analyze();

        $this->data[$key] = [$value];
    }

    protected function analyze()
    {
        $data = $this->id3->analyze($this->file);
        $this->info =  [
                'duration' => isset($data['playtime_seconds']) ? gmdate("H:i:s", ceil($data['playtime_seconds'])) : 0,
            ];		
		
		if(empty($data['tags']['id3v2'])) 
			return false;
		
        $this->data = isset($data['tags']) ? array_intersect_key($data['tags']['id3v2'], array_flip($this->tags)) : [];
        $this->data['comment'] = [''];

        if(isset($data['id3v2']['APIC'])) {
            $this->data['attached_picture'] = [$data['id3v2']['APIC'][0]];
        }
        elseif(isset($data['id3v2']['PIC'])) {
            $this->data['attached_picture'] = [$data['id3v2']['PIC'][0]];
        }
    }


}