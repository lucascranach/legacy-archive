<?php

/**
 * Class 'Viewer' handles and displays the IIPImage Viewer Area
 *
 * @author Joerg Stahlmann <>
 * @package elements/class
 */
class Viewer
{
    protected $con;
    protected $t;
    protected $lang;
    protected $imageObject;
    protected $object_folder;
    protected $category;
    protected $overallImage;
    protected $image;
    protected $id;
    protected $thumbClass;
    protected $prev;
    protected $next;
    protected $thumbnails;
    protected $objNr;
    protected $dynDir;

    /**
     * Constructor function of the class
     */
    public function __construct()
    {
        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        require_once('src/classes/DbConnection.class.php');
        $dbcon = new DbConnection();
        $this->con = $dbcon->getConnection();

        require_once("src/classes/Translator.class.php");
        $this->t = new Translator('src/xml/locallang/locallang_object.xml');

        require_once('src/classes/UriRequest.class.php');
        $uriRequest = new UriRequest();

        $this->lang = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';

        /** Set the object variables **/
        $this->object_folder = isset($_SESSION['object']['obj']) ? $_SESSION['object']['obj'] : '';
        $this->id = isset($_SESSION['object']['uid']) ? $_SESSION['object']['uid'] : '';
        $this->objNr = isset($_SESSION['object']['objNr']) ? $_SESSION['object']['objNr'] : '';
        $this->category = isset($_SESSION['object']['fol']) ? $_SESSION['object']['fol'] : '01_Overall';

        require_once('src/classes/ImageObject.class.php');
        $this->imageObject = new ImageObject($this->id, $this->object_folder);
        $this->overallImage = $this->imageObject->getOverallImage();

        $this->image = isset($_SESSION['object']['img']) ? $_SESSION['object']['img'] : $this->overallImage;

        require_once('src/classes/Thumbnails.class.php');
        $this->thumbClass = new Thumbnails($this->object_folder, $this->overallImage);

        $this->setClosestObjects();
        $this->thumbnails = array();
    }

    /**
     * Set previous and next object
     */
    protected function setClosestObjects()
    {
        $sessionId = session_id();

        $searchResult = array();

        // Search item in sql search results table
        $sql = "SELECT s.Object_UId AS id, o.ObjNr AS obj FROM SearchResult s\n"
        . "INNER JOIN Object o ON s.Object_UId = o.UId\n"
        . "WHERE s.SessionId = '$sessionId'\n"
        . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
        // mysql query
        $result = mysqli_query($this->con, $sql);

        while ($row = mysqli_fetch_object($result)) {
            $array = array(
                "id" => $row->id,
                "obj" => $row->obj
            );

            array_push($searchResult, $array);
        }

        // if there is no search result
        // fill the array with all objects
        if (empty($searchResult)) {
            // Search item in sql search results table
            $sql = "SELECT UId AS id, ObjNr AS obj FROM Object\n"
            . "ORDER BY SortNumber ASC";
            $result = mysqli_query($this->con, $sql);

            while ($row = mysqli_fetch_object($result)) {
                $array = array(
                    "id" => $row->id,
                    "obj" => $row->obj
                );

                array_push($searchResult, $array);
            }
        }

        // find key of the current object
        $key = array_search($this->id, array_column($searchResult, 'id'));
        $this->prev = ($key > 0) ? $searchResult[$key-1]['obj'] : $searchResult[$key]['obj'];
        $this->next = ($key+1 < count($searchResult)) ? $searchResult[$key+1]['obj'] : $searchResult[$key]['obj'];
    }


    /**
     * get Thumbnail-Box bottom-left
     * Displays all Thumbnails
     * of the selected Object
     *
     * @param String thumbnail category
     * @return String thumbnail html content
     */
    public function getThumbnails($cat)
    {
        $tmpArr = array();
        $thumb_categories = $this->thumbClass->getAllCategories();

        // get thumbnails by category
        if ($cat != "allImages") {
            $tmpArr = $this->thumbClass->getThumbnailsByCategory($cat);
            array_push($this->thumbnails, $tmpArr);
        } else {
            $this->thumbnails = $this->thumbClass->getAllThumbnails();
        }

        // init image category
        $category = "";

        // init content
        $thumbnails_content = '<div class="thumbnail_wrapper">';

        foreach ($this->thumbnails as $categories) {
            // set image category
            $category = $categories['category'];

            /**
             * THUMBNAIL CONTAINER IMAGES
             */
            foreach ($categories['images'] as $item) {
                // IMAGES
                $fileName = preg_replace('/.jpg/', '.tif', $item);

                $thumbnails_content .= '<a href="' . $_SERVER['PHP_SELF']
                . '?obj=' . $this->object_folder
                . '&fol=' . $category
                . '&img=' . $fileName . '" class="zoom-object">'
                . '<img width="50px" class="img-thumbnail" src="'. $this->dynDir->getDir() . 'thumbnails/'
                . $this->object_folder . '/' . $category . '/' . $item . '">'
                . '</a>';
            }

            /**
             * THUMBNAIL CONTAINER RKD
             */
            foreach ($categories['rkd'] as $item) {
                // check for internal or external RKD directory
                if (file_exists($this->dynDir->getDir() . 'thumbnails/'
                . $this->object_folder . '_RKD/11_RKD/' . $category . '/' . $item)) {
                    // external
                    $rkd_dir = $this->dynDir->getDir() . 'thumbnails/'
                    . $this->object_folder . '_RKD/11_RKD/' . $category . '/' . $item;
                } else {
                    // internal
                    $rkd_dir = $this->dynDir->getDir() . 'thumbnails/'
                    . $this->object_folder . '/11_RKD/' . $category . '/' . $item;
                }
                // IMAGES
                $fileName = preg_replace('/.jpg/', '.tif', $item);

                $thumbnails_content .= '<a href="' . $_SERVER['PHP_SELF']
                . '?obj=' . $this->object_folder
                . '&fol=' . $category
                . '&remarks=RKD'
                . '&img=' . $fileName . '" class="zoom-object">'
                . '<img width="50px" class="img-thumbnail" src="' . $rkd_dir . '">'
                . '</a>';
            }
        }

        // close div content
        $thumbnails_content .= '</div>';

        /** Thumbnail Return Panel Content **/
        $content = '<div class="panel panel-thumbnail">';

        // panel heading
        $content .= '<div class="panel-heading">'
        . '<div class="panel-label">' . $this->t->trans('filterby') . ':</div>'
        . '<div class="row">'
        . '<div class="col-md-2">'
        . '<form role="form" action="" name="formThumbCategories" method="post">'
        . '<select class="form-control" name="thumb_category"'
        . 'onchange="javascript:document.formThumbCategories.submit();" size=1>';

        $content .= '<option value="'.$cat.'" selected>'.$this->t->trans($cat).'</option>';

        foreach ($thumb_categories as $item) {
            if ($cat != $item) {
                $content .= '<option value="'.$item.'">'.$this->t->trans($item).'</option>';
            }
        }

        $content .= '</select>'
        . '</form>'
        . '</div>'
        . '</div>'
        . '</div>';

        // panel body
        $content .= '<div class="panel-body">';
        $content .= $thumbnails_content;
        $content .= '</div>' //. panel-body -->
        . '</div>'; //. panel -->

        $content .='<a href="" class="toggle-thumb"><span class="glyphicon glyphicon-picture" aria-hidden="true">'
        . '</span>&nbsp;&nbsp;' . $this->t->trans('hide_thumbs') . '</a>';

        // return thumbnail html content
        return $content;
    }

    /**
     * get navigation
     * Displays all single data navigation options
     *
     * @return String navigation html content
     */
    public function getNavigation()
    {
        $content = '<nav class="navbar navbar-inverse navbar-static-top">'
        . '<div class="container-fluid">'
        . '<div class="navbar-header">';
        $content .='<a href="javascript:setLanguage()" class="navbar-brand">' . $this->t->trans('lang') . '</a>';
        $content .='<a href="' . $this->dynDir->getBaseDir() . $this->objNr . '" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-list" aria-hidden="true"></span></a>';
        $content .='<a href="' . $this->dynDir->getBaseDir() . $this->prev . '/image" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span></a>';
        $content .='<a href="' . $this->dynDir->getBaseDir() . $this->next . '/image" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></a>';
        $content .='<a href="" id="navbar-thumbnails" class="navbar-brand toggle-thumb active">'
        . '<span id="menu-arrow" class="glyphicon glyphicon-menu-up" aria-hidden="true"></span></a>';
        $content .= '</div>'; // <!-- / container fluid -->
        $content .= '</nav>';

        return $content;
    }


    /**
    * get iip image directory
    *
    * @return String iip image directory
    */
    public function getIipimage()
    {
        $remarks = '';
        $src = '';
        $cat = '';

        $this->imageObject->setImageProperties($this->thumbnails);

        /**
        * if there is a active thumbnail filter selection
        * the first image of the category is selected
        */
        if (isset($_POST['thumb_category'])) {
            if (!empty($this->thumbnails[0]['images'])) {
                $image = preg_replace('/.jpg/', '', $this->thumbnails[0]['images'][0]);
                $this->image = $image;
                $this->category = $this->thumbnails[0]['category'];
                // remove remarks
                unset($_COOKIE['remarks']);
                $remarks = '';
            } elseif (!empty($this->thumbnails[0]['rkd'])) {
                $image = preg_replace('/.jpg/', '', $this->thumbnails[0]['rkd'][0]);
                $this->image = $image;
                $this->category = $this->thumbnails[0]['category'];
                $remarks = 'RKD';
            } else {
                $this->image = $this->overallImage;
                $this->category = '01_Overall';
                // remove remarks
                unset($_COOKIE['remarks']);
                $remarks = '';
            }
        } elseif (isset($_COOKIE['thumb_category'])) {
            $currentImageFromCookies = (isset($_COOKIE['current_image'])) ? $_COOKIE['current_image'] : '';

            if (!empty($this->thumbnails[0]['images'])) {
                $this->category = $this->thumbnails[0]['category'];

                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    // remove remarks
                    unset($_COOKIE['remarks']);
                    $remarks = '';
                } elseif (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD'
                    . '/11_RKD/' . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $remarks = 'RKD';
                } else {
                    // first image in selected category
                    $this->image = preg_replace('/.jpg/', '', $this->thumbnails[0]['images'][0]);
                    // remove remarks
                    unset($_COOKIE['remarks']);
                    $remarks = '';
                }
            } elseif (!empty($this->thumbnails[0]['rkd'])) {
                $this->category = $this->thumbnails[0]['category'];

                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD'
                    . '/11_RKD/' . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                } else {
                    // first image in selected category
                    $this->image = preg_replace('/.jpg/', '', $this->thumbnails[0]['rkd'][0]);
                }
                $remarks = 'RKD';
            } else {
                $this->image = $this->overallImage;
                $this->category = '01_Overall';
                // remove remarks
                unset($_COOKIE['remarks']);
                $remarks = '';
            }
        } else {
            if (isset($_COOKIE['remarks'])) {
                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD/'
                    . $this->category . '/' . $this->image . '.jpg')) {
                    $this->object_folder = $this->object_folder. '_' . $add;
                }
            }
        }

        // check remarks
        if (isset($_COOKIE['remarks']) || !empty($remarks)) {
            // set the additional directory
            $add = 'RKD';
            setcookie('remarks', $add, time()+3600, '/', '.lucascranach.org');
            // evaluate the directory for the folder
            if (file_exists($this->dynDir->getDir() . 'thumbnails/'
                . $this->object_folder . '_' . $add . '/' . $this->category . '/' . $big_thumb)) {
                $this->object_folder = $this->object_folder. '_' . $add;
            }
        }
        // set current cookies
        setcookie('current_image', $this->image, time()+3600, '/', '.lucascranach.org');
        setcookie('category', $this->category, time()+3600, '/', '.lucascranach.org');

        $iipimage = '/var/www/iipimages/'.$this->object_folder.'/'.$this->category.'/pyramid/'.$this->image.'.tif';

        return $iipimage;
    }
}
