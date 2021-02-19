<?php

/**
 * Class 'Thumbnails' handles all imags data from the CDA folder structure
 *
 * @author Joerg Stahlmann <>
 * @package elements/class
 */
class Thumbnails
{
    protected $directory;
    protected $overall;
    protected $reverse;
    protected $irr;
    protected $xradiograph;
    protected $uvlight;
    protected $detail;
    protected $photomicrograph;
    protected $conservation;
    protected $other;
    protected $analysis;
    protected $rkd;
    protected $koe;
    protected $extensions;
    protected $overall_image;
    protected $dynDir;

    /**
    * Constructor function of the class
    */
    public function __construct($folder, $image)
    {
        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        // set overall image
        $this->overall_image = $image;

        // init array of all rkd images
        $this->rkd = array();

        // init extension array
        $this->extensions = array("pdf");

        // init folder directory
        $this->directory = $folder;

        // get overall
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('overall'));
        $this->overall = $arr;

        // get reverse
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('reverse'));
        $this->reverse = $arr;

        // get irr
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('irr'));
        $this->irr = $arr;

        // get x-radiograph
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('xradiograph'));
        $this->xradiograph = $arr;

        // get uv-light
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('uvlight'));
        $this->uvlight = $arr;

        // get detail
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('detail'));
        $this->detail = $arr;

        // get photomicrograph
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('photomicrograph'));
        $this->photomicrograph = $arr;

        // get conservation
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('conservation'));
        $this->conservation = $arr;

        // get others
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('other'));
        $this->other = $arr;

        // get analysis
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('analysis'));
        $this->analysis = $arr;

        // get koeplin
        $arr = $this->setThumbnailsByCategory($this->getDirectoryByCategory('koe'));
        $this->koe = $arr;
    }

    /**
    * Set Thumbnail by Category function handles all
    * Thumbnails in the given Category
    *
    * @return Array thumbnails
    */
    protected function setThumbnailsByCategory($category)
    {
        // init thumbnails array
        $thumbnails = array();

        // init pdf array
        $pdf = array();

        // init rkd array
        $rkd = array();

        // init koeplin array
        $koe = array();

        if ($category == '01_Overall') {
            array_push($thumbnails, $this->overall_image.'.jpg');
        }

        // IMAGES
        if (file_exists($this->dynDir->getDir() . 'thumbnails/'
        . $this->directory . '/' . $category . '/')) {
            if ($handle = opendir($this->dynDir->getDir()
            . 'thumbnails/' . $this->directory . '/' . $category . '/')) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != ".." && $file != $this->overall_image.'.jpg') {
                        // insert into array
                        array_push($thumbnails, $file);
                    }
                }
                closedir($handle);
            }
        }

        // PDF
        if (file_exists($this->dynDir->getDir() . 'documents/' . $this->directory . '/' . $category .'/')) {
            if ($handle = opendir($this->dynDir->getDir() . 'documents/' . $this->directory . '/' . $category .'/')) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        // extract extension
                        $ext = substr($file, strrpos($file, '.') + 1);
                        // if extension is in the array
                        if (in_array($ext, $this->extensions)) {
                            // add pdf to the array
                            array_push($pdf, $file);
                        }
                    }
                }
                closedir($handle);
            }
        }

        // RKD external
        if (file_exists($this->dynDir->getDir() . 'thumbnails/'
        . $this->directory . '_RKD/11_RKD/' . $category . '/')) {
            if ($handle = opendir($this->dynDir->getDir()
            . 'thumbnails/' . $this->directory . '_RKD/11_RKD/' . $category . '/')) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        // add file to rkd
                        array_push($rkd, $file);
                    }
                }
                closedir($handle);
            }
        }

        // RKD internal
        if (file_exists($this->dynDir->getDir() . 'thumbnails/'
        . $this->directory . '/11_RKD/' . $category . '/')) {
            if ($handle = opendir($this->dynDir->getDir()
            . 'thumbnails/' .$this->directory . '/11_RKD/' . $category . '/')) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        // add file to rkd
                        array_push($rkd, $file);
                    }
                }
                closedir($handle);
            }
        }

        // return false if all arrays are empty
        if (empty($thumbnails) && empty($pdf) && empty($rkd)) {
            return false;
        }

        $arr = array(
            'images' => $thumbnails,
            'pdf' => $pdf,
            'rkd' => $rkd,
            'category' => $category
        );

        if (!empty($rkd)) {
            $rkdArr = array(
                'rkd' => $rkd,
                'category' => $category
            );

            array_push($this->rkd, $rkdArr);
        }

        return $arr;
    }

    /**
    * Get clean category name by the given category
    *
    * @param String Thumbnail Category
    * @return String Clean name of the given category
    */
    public function getCleanNameByCategory($category)
    {
        switch ($category) {
            case "01_Overall":
                return "overall";
                break;
            case "02_Reverse":
                return "reverse";
                break;
            case "03_IRR":
                return "irr";
                break;
            case "04_X-radiograph":
                return "xradiograph";
                break;
            case "05_UV-light":
                return "uvlight";
                break;
            case "06_Detail":
                return "detail";
                break;
            case "07_Photomicrograph":
                return "photomicrograph";
                break;
            case "08_Conservation":
                return "conservation";
                break;
            case "09_Other":
                return "other";
                break;
            case "10_Analysis":
                return "analysis";
                break;
            case "11_RKD":
                return "rkd";
                break;
            case "11_RKD/01_Overall":
                return "overall";
                break;
            case "11_RKD/06_Detail":
                return "detail";
                break;
            case "12_KOE":
                return "koe";
                break;
            default:
                return false;
        }
    }

    /**
    * Get directory by the given Category
    *
    * @param String Thumbnail Category
    * @return String Directory of the given Category
    */
    protected function getDirectoryByCategory($category)
    {
        switch ($category) {
            case "overall":
                return "01_Overall";
                break;
            case "reverse":
                return "02_Reverse";
                break;
            case "irr":
                return "03_IRR";
                break;
            case "xradiograph":
                return "04_X-radiograph";
                break;
            case "uvlight":
                return "05_UV-light";
                break;
            case "detail":
                return "06_Detail";
                break;
            case "photomicrograph":
                return "07_Photomicrograph";
                break;
            case "conservation":
                return "08_Conservation";
                break;
            case "other":
                return "09_Other";
                break;
            case "analysis":
                return "10_Analysis";
                break;
            case "rkd":
                return "11_RKD";
                break;
            case "koe":
                return "12_KOE";
                break;
            default:
                return false;
        }
    }

    /**
    * Get Thumbnails by the given Category
    *
    * @param String Thumbnail Category
    * @return array Thumbnails in the given Category
    */
    public function getThumbnailsByCategory($category)
    {
        switch ($category) {
            case "overall":
                return $this->overall;
                break;
            case "reverse":
                return $this->reverse;
                break;
            case "irr":
                return $this->irr;
                break;
            case "xradiograph":
                return $this->xradiograph;
                break;
            case "uvlight":
                return $this->uvlight;
                break;
            case "detail":
                return $this->detail;
                break;
            case "photomicrograph":
                return $this->photomicrograph;
                break;
            case "conservation":
                return $this->conservation;
                break;
            case "other":
                return $this->other;
                break;
            case "analysis":
                return $this->analysis;
                break;
            case "rkd":
                return $this->rkd;
                break;
            case "koe":
                return $this->koe;
                break;
            default:
                return false;
        }
    }

    /**
    * Get Thumbnails by the given Category
    *
    * @return array All Categories
    */
    public function getAllCategories()
    {
        $arr = array();

        array_push($arr, "allImages");

        if (!empty($this->overall)) {
            array_push($arr, "overall");
        }

        if (!empty($this->reverse)) {
            array_push($arr, "reverse");
        }

        if (!empty($this->irr)) {
            array_push($arr, "irr");
        }

        if (!empty($this->xradiograph)) {
            array_push($arr, "xradiograph");
        }

        if (!empty($this->uvlight)) {
            array_push($arr, "uvlight");
        }

        if (!empty($this->detail)) {
            array_push($arr, "detail");
        }

        if (!empty($this->photomicrograph)) {
            array_push($arr, "photomicrograph");
        }

        if (!empty($this->conservation)) {
            array_push($arr, "conservation");
        }

        if (!empty($this->other)) {
            array_push($arr, "other");
        }

        if (!empty($this->analysis)) {
            array_push($arr, "analysis");
        }

        if (!empty($this->rkd)) {
            array_push($arr, "rkd");
        }

        if (!empty($this->koe)) {
            array_push($arr, "koe");
        }

        return $arr;
    }

    /**
    * Get all Thumbnailsy
    *
    * @return array Thumbnails
    */
    public function getAllThumbnails()
    {
        $arr = array();

        if (!empty($this->overall)) {
            array_push($arr, $this->overall);
        }

        if (!empty($this->reverse)) {
            array_push($arr, $this->reverse);
        }

        if (!empty($this->irr)) {
            array_push($arr, $this->irr);
        }

        if (!empty($this->xradiograph)) {
            array_push($arr, $this->xradiograph);
        }

        if (!empty($this->uvlight)) {
            array_push($arr, $this->uvlight);
        }

        if (!empty($this->detail)) {
            array_push($arr, $this->detail);
        }

        if (!empty($this->photomicrograph)) {
            array_push($arr, $this->photomicrograph);
        }

        if (!empty($this->conservation)) {
            array_push($arr, $this->conservation);
        }

        if (!empty($this->other)) {
            array_push($arr, $this->other);
        }

        if (!empty($this->analysis)) {
            array_push($arr, $this->analysis);
        }

        if (!empty($this->koe)) {
            array_push($arr, $this->koe);
        }

        return $arr;
    }
}
