<?php

/**
 * Class 'Overall Image' sets, updates and returns the main overall image of the object
 *
 * @author Joerg Stahlmann <>
 * @package elements/class
 */
class ImageObject
{

    protected $con;
    protected $uid;
    protected $image;
    protected $category;
    protected $src;
    protected $overallImage;
    protected $folder;
    protected $dynDir;
    protected $thumbnails;

    /**
    * Constructor function of the class
    */
    public function __construct($id, $dir)
    {
        require_once('FirePHPCore/fb.php');
        ob_start();

        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        require_once('src/classes/DbConnection.class.php');
        $dbcon = new DbConnection();
        $this->con = $dbcon->getConnection();

        $this->uid = $id;
        $this->folder = $dir;
        $this->overallImage = $this->setOverallImage();
    }

    /**
    * Get Overall Image of the object
    *
    * @return Image directory
    */
    public function getOverallImage()
    {
        return $this->overallImage;
    }

    /**
    * Get Current Image of the object
    *
    * @return String image name
    */
    public function getCurrentImage()
    {
        return $this->image;
    }

    /**
    * Get Current Category of the object
    *
    * @return String category
    */
    public function getCurrentCategory()
    {
        return $this->category;
    }

    /**
    * Get all thumbnails from thumbnail class.
    *
    * @return Array thumbnails contains all thumbnails of the current object
    */
    protected function getThumbnailContainer()
    {
        $thumbnails = array();

        // Create Thumbnail Object
        require_once('src/classes/Thumbnails.class.php');
        $thumbnailClass = new Thumbnails($this->folder, $this->overallImage);

        // set category
        if (isset($_POST['thumb_category'])) {
            $category = $_POST['thumb_category'];
        } elseif (isset($_COOKIE['thumb_category'])) {
            $category = $_COOKIE['thumb_category'];
        } else {
            $category = 'allImages';
        }

        // get thumbnails by category
        if ($category == "allImages") {
            $thumbnails = $thumbnailClass->getAllThumbnails();
        } elseif ($category == "rkd") {
            $tmpArr = $thumbnailClass->getThumbnailsByCategory($category);
            $thumbnails = $tmpArr;
        } else {
            $tmpArr = $this->thumbClass->getThumbnailsByCategory($category);
            array_push($thumbnails, $tmpArr);
        }

        return $thumbnails;
    }

    /**
    * Determine current image name of the selected object.
    * First check cookies image.
    * If no result run the overall image functionality.
    *
    * @param Array thumbnails contains all thumbnails of the current object
    */
    public function setImageProperties($thumbnails = array())
    {
        $this->thumbnails = (empty($thumbnails)) ? $this->getThumbnailContainer() : $thumbnails;
        $remarks = (isset($_COOKIE['remarks'])) ? $_COOKIE['remarks'] : '';
        $this->src = '';

        /**
        * if there is a active thumbnail filter selection
        * the first image of the category is selected
        */
        if (isset($_POST['thumb_category'])) {
            fb('hier', 'Post');
            if (!empty($this->thumbnails[0]['images'])) {
                $image = preg_replace('/.jpg/', '', $this->thumbnails[0]['images'][0]);
                $this->image = $image;
                $this->category = $this->thumbnails[0]['category'];
                $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                . $this->category . '/';
            } elseif (!empty($this->thumbnails[0]['rkd'])) {
                $image = preg_replace('/.jpg/', '', $this->thumbnails[0]['rkd'][0]);
                $this->image = $image;
                $this->category = $this->thumbnails[0]['category'];
                $remarks = 'RKD';
                $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_' . $remarks . '/11_RKD/'
                . $this->category . '/';
            } else {
                $this->image = $this->overallImage;
                $this->category = '01_Overall';
                $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                . $this->category . '/';
            }
        } elseif (isset($_COOKIE['thumb_category'])) {
            fb('hier', 'Cookie');
            $currentImageFromCookies = (isset($_COOKIE['current_image'])) ? $_COOKIE['current_image'] : '';
            fb(isset($_COOKIE['thumb_category']), 'isset cookie thumb_category');
            if (!empty($this->thumbnails[0]['images'])) {
                $this->category = $this->thumbnails[0]['category'];

                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                    . $this->category . '/';
                } elseif (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_RKD'
                    . '/11_RKD/' . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $remarks = 'RKD';
                    $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_RKD' . '/11_RKD/'
                    . $this->category . '/';
                } else {
                    // first image in selected category
                    $this->image = preg_replace('/.jpg/', '', $this->thumbnails[0]['images'][0]);
                    $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                    . $this->category . '/';
                }
            } elseif (!empty($this->thumbnails[0]['rkd'])) {
                $this->category = $this->thumbnails[0]['category'];
                $remarks = 'RKD';

                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_RKD'
                    . '/11_RKD/' . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_RKD' . '/11_RKD/'
                    . $this->category . '/';
                } else {
                    // first image in selected category
                    $this->image = preg_replace('/.jpg/', '', $this->thumbnails[0]['rkd'][0]);
                }
            } else {
                $this->image = $this->overallImage;
                $this->category = '01_Overall';
                $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                . $this->category . '/';
            }
        } else {
            fb('hier', 'Else');
            $this->category = (isset($_COOKIE['category'])) ? $_COOKIE['category'] : '01_Overall';
            $this->image = (isset($_COOKIE['current_image'])) ? $_COOKIE['current_image'] : $this->overallImage;

            if (!empty($remarks)) {
                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_RKD/'
                    . $this->category . '/' . $this->image . '.jpg')) {
                    $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '_RKD/'
                    . $this->category . '/';
                } elseif (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                    . $this->category . '/' . $this->image . '.jpg')) {
                    $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                    . $this->category . '/';
                }
            } else {
                $this->src = $this->dynDir->getDir() . 'thumbnails/' . $this->folder . '/'
                . $this->category . '/';
            }
        }
        fb($this->folder, 'Folder');
        fb($this->category, 'Category');
        fb($this->image, 'Image');
        fb($this->src, 'Source');
    }

    /**
    * Determine main overall image of the selected object.
    * First check database image table for entry.
    * If no result run through object overall directory.
    *
    * @return Image directory
    */
    protected function setOverallImage()
    {
        $overallImage = "";
        $sqlUpdate = false;

        $checkSql = "SELECT Overall_Image FROM Images WHERE Object_UId = '$this->uid'";
        $r = mysqli_query($this->con, $checkSql);

        if (mysqli_num_rows($r) != 0) {
            $row = mysqli_fetch_object($r);
            $overallImage = $row->Overall_Image;
            $update = true;
        }

        if (empty($overallImage)) {
            $overallImage = $this->findOverallImage();
            // insert / update overall image into database Overall_Image table
            $sql = ($update) ?
                "UPDATE Images SET Overall_Image='$overallImage' WHERE Object_UId='$this->uid'"
                : "INSERT INTO Images (Object_UId, Overall_Image) VALUES ('$this->uid', '$overallImage')";

            if (!mysqli_query($this->con, $sql)) {
                die('Error: ' . mysql_error());
            }
        }

        return $overallImage;
    }

    /**
    * Find Overall Image by directory look up
    * @return String Overall Image
    */
    protected function findOverallImage()
    {
        $overallArr = array();
        // init overall image
        $overallImage = "";

        // run through overall directory and select the main overall image
        if ($handle = opendir($this->dynDir->getDir()
        . 'thumbnails/' . $this->folder . '/01_Overall/')) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $pos = strpos($file, 'Overall.jpg');
                    if ($pos !== false) {
                        $overallArr[] = $file;
                    }
                }
            }
            closedir($handle);
        }

        // set overall image
        $overallImage = preg_replace('/.jpg/', '', $overallArr[0]);

        return $overallImage;
    }
}
