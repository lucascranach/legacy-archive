<?php

class Helper
{

    public function __construct()
    {
      $this->config = new Config;
      $this->caching = $this->config->getSection('caching');
    }

    public function returnNumber( $value )
    {
        return is_numeric($value) ? intval($value) : 0;
    }

    public function returnCountable( $value )
    {
        return is_countable($value) ? $value : [];
    }

    public function writeToCache($cached_file, $content){
      file_put_contents($cached_file, $content);
      return;
    }

    public function readFromCache($path){
      $id = md5($path);
      if(!$this->caching->active) return file_get_contents($path);

      $cached_file = $this->caching->path .'/'.$id;
      if(file_exists($cached_file)){
        return file_get_contents($cached_file);
      }else{
        $content = file_get_contents($path);
        $this->writeToCache($cached_file, $content);
        return $content;
      }
      
    }
}
