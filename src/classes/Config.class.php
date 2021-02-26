<?php

class Config
{

    protected $config;

    public function __construct()
    {
        $this->config = json_decode(file_get_contents('cranach.config.json'));
    }

    public function getSection($section = false)
    {
        if (!isset($section)) {
            return false;
        }

        return $this->config->$section;
    }

    public function getBaseUrl()
    {
        $host = $this->config->host;
        $port = (isset($host->port) && $host->port !== false) ? ':' . $host->port : '';

        return $host->protocol . '://' . $host->hostname . $port;
    }

    public function getImagesBaseUrl()
    {
        $imagehost = $this->config->imagehost;
        $port = (isset($imagehost->port) && $imagehost->port !== false) ? ':' . $imagehost->port : '';
        $segment = (isset($imagehost->path)) ? '/' . $imagehost->path : '';
        return $imagehost->protocol . '://' . $imagehost->hostname . $port . $segment;
    }

    public function getImageDataUrl($path)
    {
        if (!isset($path)) {echo "getImageDataUrl(\$path) expects \$path." && exit;}
        $imagehost = $this->config->imagehost;
        return $this->getImagesBaseUrl() . '/' . $path .'/'. $imagehost->imagedata;
    }

}
