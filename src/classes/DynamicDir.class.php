<?php

/**
 * Class 'Directory' evaluates the right directory path to an image.
 *
 * @author Joerg Stahlmann <>
 * @package src/class
 */
class DynamicDir
{
    protected $parentDir;

    /**
    * Constructor function of the class
    */
    public function __construct()
    {
        $this->parentDir = $this->findParentDir();
    }

    /**
    * Method defines wether the parent directory
    * is frontend, backend (admin) or developers (dev/beta) area.
    *
    * @return String directory;
    */
    protected function findParentDir()
    {
        $matches = [];
        preg_match('(^/admin/.*$)', $_SERVER['REQUEST_URI'], $matches);

        if (!empty($matches[0])) {
            return 'admin';
        }

        preg_match('(^/dev/.*$)', $_SERVER['REQUEST_URI'], $matches);

        if (!empty($matches[0])) {
            return 'dev';
        }

        return 'root';
    }

    /**
    * Method defines wether the parent directory
    * is frontend, backend (admin) or developers (dev/beta) area.
    *
    * @return String directory;
    */
    public function getDir()
    {
        $dir;

        switch ($this->parentDir) {
            case 'admin':
                $dir = '../';
                break;
            case 'dev':
                $dir = '../';
                break;
            case 'root':
                $dir = './';
                break;
        }

        return $dir;
    }

    /**
    * Method defines base parent directory
    *
    * @return String directory;
    */
    public function getBaseDir()
    {
        $dir;

        switch ($this->parentDir) {
            case 'admin':
                $dir = '/admin/';
                break;
            case 'dev':
                $dir = '/dev/';
                break;
            case 'root':
                $dir = '/';
                break;
        }

        return $dir;
    }
}
