<?php

/**
* Class 'Uri Request' evaluates all required variables from the perma link object parameter
*
* @author Joerg Stahlmann <>
* @package elements/class
*/
class UriRequest
{
    protected $con;
    protected $uid;
    protected $objNr;
    protected $full_object_id;
    protected $category;
    protected $overall_image;
    protected $current_image;
    protected $object_folder;
    protected $dynDir;

    /**
    * Constructor function of the class
    */
    public function __construct()
    { 
      $config = new Config;
      $this->host = $config->getSection('host');

        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        require_once('src/classes/DbConnection.class.php');
        require_once('src/classes/ImageObject.class.php');
        require_once('FirePHPCore/fb.php');
        ob_start();

        $fullObjId = '';
        $object = array();
        $dbcon = new DbConnection();
        $this->con = $dbcon->getConnection();

        if (isset($_GET['object'])) {
            $value = rtrim($_GET['object'], '/');
            $sql = "SELECT * FROM Object WHERE ObjNr LIKE '$value'";
        } elseif (isset($_GET['uid'])) {
            $value = $_GET['uid'];
            $sql = "SELECT * FROM Object WHERE UId LIKE '$value'";
        } elseif (isset($_GET['obj'])) {
            $arr = explode('_FR', $_GET['obj']);
            $value = $arr[0];
            $sql = "SELECT * FROM Object WHERE ObjNr LIKE '$value'";
        }

        // mysql query
        $ergebnis = mysqli_query($this->con, $sql);
        // run through request
        while ($row = mysqli_fetch_object($ergebnis)) {
            $this->uid = $row->UId;
            $this->objNr = $row->ObjNr;
            $this->full_object_id = $row->ObjNr . '_' . $row->ObjIdentifier;
            $this->object_folder = $this->full_object_id;
            setcookie('objectId', $this->object_folder, time()+3600, '/', '.'.$this->host->hostname);
        }

        if (isset($_GET['fol']) && isset($_GET['img'])) {
            setcookie('category', $_GET['fol'], time()+3600, '/', '.'.$this->host->hostname);
            $imgArr = explode('.', $_GET['img']);
            setcookie('current_image', $imgArr[0], time()+3600, '/', '.'.$this->host->hostname);

            if (isset($_GET['remarks'])) {
                setcookie('remarks', $_GET['remarks'], time()+3600, '/', '.'.$this->host->hostname);
            }

            $uri = $config->getBaseUrl() . $this->dynDir->getBaseDir() . $this->objNr;
            fb($uri);
            $this->redirect($uri);
        }

        // *** SET Overall Image *** //
        $imageObject = new ImageObject($this->uid, $this->object_folder);
        $this->overall_image = $imageObject->getOverallImage();

        $this->category = isset($_COOKIE['category']) ? $_COOKIE['category'] : '01_Overall';

        if (isset($_COOKIE['current_image'])) {
            $this->current_image = $_COOKIE['current_image'];
        } else {
            $this->current_image = $this->overall_image;
            setcookie('current_image', $this->overall_image, time()+3600, '/', '.'.$this->host->hostname);
        }

        $big_thumb = $this->current_image . '.jpg';

        if (file_exists($this->dynDir->getDir()
        . 'thumbnails/' . $this->object_folder . '/' . $this->category . '/' . $big_thumb)) {
            unset($_COOKIE['remarks']);
        }

        // check remarks
        if (isset($_COOKIE['remarks'])) {
            $add = $_COOKIE['remarks'];
            // eavluate the directory for the structure
            switch ($add) {
                case 'RKD':
                    /**
                     * Backdrop from image zoom already adds
                     * adds the remark to the current category
                     */
                    $pos = strpos($file, $add);
                    if ($pos === false) {
                        $this->category = '11_' . $add . '/' . $this->category;
                    }
                    break;
            }
            // evaluate the directory for the folder
            $this->object_folder = (file_exists($this->dynDir->getDir()
            . 'thumbnails/' . $this->full_object_id . '_' .$add . '/' . $this->category)) ?
                $this->full_object_id . '_' . $add : $this->full_object_id;
        }

        if (!file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->object_folder .'/'
            . $this->category . '/' . $this->current_image . '.jpg')) {
            $this->current_image = $this->overall_image;
            setcookie('current_image', $this->current_image, time()+3600, '/', '.'.$this->host->hostname);
            $this->category = '01_Overall';
            $this->object_folder = $this->full_object_id;
            setcookie('category', '01_Overall', time()+3600, '/', '.'.$this->host->hostname);
        }

        $object = array(
            'obj' => $this->full_object_id,
            'objNr' => $this->objNr,
            'fol' => $this->category,
            'img' => $this->current_image,
            'uid' => $this->uid
        );

        $_SESSION['object'] = $object;
    }

    /**
    * Function handles redirect for seo friendly URI
    */
    protected function redirect($url, $permanent = false)
    {
        header('Location: ' . $url, true, $permanent ? 301 : 302);
        exit();
    }
}
