<?php

/**
 * Class 'Gallery' handles the whole functionality of the gallery view.
 * It lists all overall images, adds the popup thumbnail view and sets the navigation.
 *
 * @author Joerg Stahlmann <>
 * @package src/classes
 */
class Gallery
{
    protected $selectedLanguage;
    protected $mod;
    protected $obj;
    protected $con;
    protected $index;
    protected $page;
    protected $t;
    protected $dynDir;

    /**
    * Constructor function of the class
    */
    public function __construct()
    {
        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        require_once("src/classes/Translator.class.php");
        $this->t = new Translator('src/xml/locallang/locallang_gallery.xml');

        require_once('src/classes/DbConnection.class.php');
        $dbcon = new DbConnection();
        $this->con = $dbcon->getConnection();

        require_once('src/classes/AdvancedSearch.class.php');
        $advancedSearch = new AdvancedSearch($this->con);
        $this->obj = $advancedSearch->getResult();

        $this->config = new Config;
        $this->imagehost = $this->config->getImagesBaseUrl();

        $this->helper = new Helper;

        // get the language from the session
        $this->selectedLanguage = $_SESSION['lang'];

        // various session request
        if (isset($_COOKIE['maxImages'])) {
            $this->mod = (int)$_COOKIE['maxImages'];
        } else {
            $this->mod = 60;
        }
    }

    public function getThumbnail($value, $tooltip){

      $image_data_url = $this->config->getImageDataUrl($value['objNr'] .'_' . $value['frNr']);
      $image_data = $this->helper->readFromCache($image_data_url);
      $image_data_json = $image_data ? json_decode($image_data) : json_decode(file_get_contents());

      $thumb_data = $image_data_json->imageStack->overall->images[0]->s;
      $thumb_url = $this->imagehost .'/'. $value['objNr'] .'_' . $value['frNr'] . '/' . $thumb_data->path . '/' . $thumb_data->src;
      $thumb = '<img loading="lazy" src="' . $thumb_url. '" onError="this.src=\'' . $this->dynDir->getDir() . 'images/default.jpg\'" width="150" height="150"'
      . 'class="grey-tooltip cda-thumbnail"'
      . 'data-toggle="tooltip"'
      . 'data-html="true"'
      . 'data-placement="auto bottom"'
      . 'title="' . htmlspecialchars($tooltip) .'"'
      . '>';

      return $thumb;
    }

    /**
    * get navigation
    * Displays all single data navigation options
    *
    * @return String navigation html content
    */
    public function getNavigation()
    {
        $home = "";

        if (isset($_COOKIE['lang']) && $_COOKIE['lang'] == "Englisch") {
            $home = "/home";
        }

        $content = '<nav class="navbar navbar-inverse navbar-static-top">'
        . '<div class="container-fluid">'
        . '<div class="navbar-header">';
        $content .='<a href="javascript:setLanguage()" class="navbar-brand">'.$this->t->trans('lang').'</a>';
        $content .='<a href="'.$home.'" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-home" aria-hidden="true"></span></a>';
        $content .='<a data-toggle="collapse" href="#collapseUserArea" aria-expanded="false"'
        . 'aria-controls="collapseUserArea" class="navbar-brand pull-right hidden-sm hidden-xs">'
        . '<span class="glyphicon glyphicon-user" aria-hidden="true"></span></a>';
        $content .= $this->pageNavigation();
        $content .= '</div>'; // <!-- / container fluid -->
        $content .= '</nav>';

        return $content;
    }

    /**
    * Get the Gallery
    *
    * @return string html content of the filter list
    */
    public function getGallery()
    {
        $selectedLanguage = $this->selectedLanguage;
        // get private var for index:
        $index = $this->index;
        // slice array by mod value
        $arr = array_slice($this->obj, $index, $this->mod, true);
        /** count all objects in the database **/
        $values = array();
        $ergebnis = 0;
        
        $sql = "SELECT count(*) AS total FROM Object";
        $result = mysqli_query($this->con, $sql );
        $values = mysqli_fetch_assoc($result);
        $ergebnis = $values['total'];

        // create gallery container
        $content = '<div id="gallery">';
        $content .= '<div id="gallery_wrapper">';

        foreach ($arr as $value) {
            // init titel
            $tooltip_title = "";
            // init date
            $tooltip_date = "";
            // init repo
            $tooltip_repo = "";
            // tmp id for tooltip title
            $tmp_id = $value['uid'];

            /** Titel and Titeltype**/
            $sql = "SELECT Title FROM ObjectTitle\n"
            . "WHERE Object_UId = '$tmp_id'\n"
            . "AND DisplayOrder < 3\n"
            . "AND Language = '$selectedLanguage'";
            $ergebnis = mysqli_query($this->con, $sql);

            while ($row = mysqli_fetch_object($ergebnis)) {
                if ($row->Title != 'null') {
                    $tooltip_title = $row->Title;
                }
            }

            /** Dating current **/
            $eventType = $this->t->trans('eventType');

            $sql = "SELECT * FROM Dating\n"
            . "WHERE Object_UId = '$tmp_id'\n"
            . "AND EventType = '$eventType'\n"
            . "AND Language = '$selectedLanguage'";
            $dateErgebnis = mysqli_query($this->con, $sql);

            while ($row = mysqli_fetch_object($dateErgebnis)) {
                if ($row->Dating != 'null') {
                    $tooltip_date = $row->Dating;
                }
            }

            /** Relations Repository **/

            $relType = $this->t->trans('relType');

            $sql = "SELECT Type, Value FROM MultipleTable\n"
            . "WHERE Object_UId = '$tmp_id'\n"
            . "AND Type = '$relType'\n"
            . "AND Language = '$selectedLanguage'";
            $dateErgebnis = mysqli_query($this->con, $sql);

            while ($row = mysqli_fetch_object($dateErgebnis)) {
                if ($row->Value != 'null') {
                    $tooltip_repo = $row->Value;
                }
            }

            $tooltip = '<table>'
            . '<tr>'
            . '<td>'.$this->t->trans('title_h').':</td>'
            . '<td>'.$tooltip_title.'</td>'
            . '</tr>'
            . '<tr>'
            . '<td>'.$this->t->trans('date_h').':</td>'
            . '<td>'.$tooltip_date.'</td>'
            . '</tr>'
            . '<tr>'
            . '<td>'.$this->t->trans('repository_h').':</td>'
            . '<td>'.$tooltip_repo.'</td>'
            . '</tr>'
            . '</table>';

            // ****
            // SET ALL IMAGES
            // ****
            /*if (file_exists($this->dynDir->getDir() . 'thumbnails/'
            . $value['objNr'] . '_' . $value['frNr'] . '/01_Overall/')) {
                if ($handle = opendir($this->dynDir->getDir() . 'thumbnails/'
                . $value['objNr'] .'_' . $value['frNr'] . '/01_Overall/')) {
                    while (false !== ($file = readdir($handle))) {
                        if ($file != "." && $file != "..") {
                            $pos = strpos($file, 'Overall.jpg');
                            if ($pos !== false) {
                                $image = $file;
                            }
                        }
                    }
                    closedir($handle);
                }
                // THUMBNAIL
                $thumb = '<img src="' . $this->dynDir->getDir() . 'thumbnails/'
                . $value['objNr'] .'_' . $value['frNr']
                . '/01_Overall/' . $image
                . '" onError="this.src=\'' . $this->dynDir->getDir() . 'images/default.jpg\'" width="150" height="150"'
                . 'class="grey-tooltip"'
                . 'data-toggle="tooltip"'
                . 'data-html="true"'
                . 'data-placement="auto bottom"'
                . 'title="' . htmlspecialchars($tooltip) .'"'
                . '>';

                // FILENAME TIF
                $filename = preg_replace('/.jpg/', '.tif', $image);
            } else {
                // THUMBNAIL
                $thumb = '<img src="' . $this->dynDir->getDir() . 'images/no-image.png" width="150"'
                . 'class="grey-tooltip"'
                . 'data-toggle="tooltip"'
                . 'data-html="true"'
                . 'data-placement="auto bottom"'
                . 'title="' . htmlspecialchars($tooltip) . '"'
                . '>';

                // FILENAME TIF
                $filename = '';

                // BIG THUMB
                //$big_thumb = '<img src="images/no-image.png" width="300" height="300" />';
            }*/
            //error_log('THUMB: '.$thumb.' FILENAME: '.$filename.' BIGTHUMB: '.$big_thumb);
            // SHOW IMAGE
            $thumb = $this->getThumbnail($value, $tooltip);
            $filename = "a";
            $content .= '<a href="' . $this->dynDir->getBaseDir() . 'object.php'
            . '?&obj=' . $value['objNr'] . '_' .$value['frNr']
            . '&uid=' . $value['uid']
            . '&page=' . $this->page
            . '&fol=01_Overall'
            . '&img=' . $filename.'"'
            . 'draggable="true" ondragstart="drag(event)"'
            . '>'
            . $thumb
            . '</a>';
        }

        // close gallery wrapper container
        $content .= '</div>';
        // close gallery container
        $content .= '</div>';

        return $content;
    }



    /**
    * The function list all entities, calculates the amount of pages
    * and displays them.
    *
    * @return array returns the page navigation
    */
    protected function pageNavigation()
    {
        /** Add the next and prev button controls! **/
        // Amount of all entities in the array
        $entities = count($this->obj);

        // evaluate the selected page
        $page = (isset($_COOKIE['page'])) ? $_COOKIE['page'] : 1;
        // evaluate current index
        $this->index = $this->mod * ($page - 1);

        // set private page variable
        $this->page = $page;

        // max page
        $max = ceil($entities / $this->mod);

        setcookie("lastPage", $max, time()+3600, '/', '.lucascranach.org');


        // page skipper
        $skip = 15;

        // evaluate runner
        if ($page < $skip && $page != ($skip - 1)) {
            // runner from 1 to selected number
            $runner = ($max < $skip) ? $max : $skip - 1;
            // number where the points start
            $cut = $skip;
            // state of the navigatin - 0 : default
            $state = 0;
        } elseif ($page == ($skip - 1) && $page != $max) {
            // runner from 1 to selected number
            $runner = $skip - 9;
            // number where the points start
            $cut = $skip - 2;
            // state of the navigatin - 1 : middle
            $state = 1;
        } elseif ($page >= $skip && $page != $max) {
            // runner from 1 to selected number
            $runner = $skip - 9;
            // number where the points start
            $cut = $skip - 1;
            // state of the navigatin - 1 : middle
            $state = 1;
        } elseif ($page >= $skip && $page == $max) {
            // runner from 1 to selected number
            $runner = $skip - 9;
            // number where the points start
            $cut = 0;
            // state of the navigatin - 2 : last
            $state = 2;
        }

        // generate the navigation with the evaluated parameters
        $navigation = $this->navigation($runner, $page, $max, $cut, $state);

        // retunr the page navigation
        return $navigation;
    }

    /**
    * The function displays the page navigation
    *
    * @param int number from - to before points
    * @param int current page number
    * @param int maximum images per site
    * @param int number where the points start
    * @param int number of the current state
    * @return array returns the page navigation content
    */
    protected function navigation($runner, $curr, $max, $skip, $state)
    {
        // prev page
        if ($curr - 1 < 1) {
            $prev = 1;
        } else {
            $prev = $curr - 1;
        }

        // next page
        if ($curr + 1 >= $max) {
            $next = $max;
        } else {
            $next = $curr + 1;
        }

        // preprev page
        $preprev = $prev - 1;

        // postnext page
        $postnext = $next + 1;

        // recalculate points if the current page is one page pre maximum
        //$runner = ($curr == ($max - 1) && $max >= 3) ? $runner + 1 : $runner;

        // dev directory
        // write content
        $page_navi ='<ul class="pagination">';
        $page_navi .= '<li>';
        $page_navi .='<a href="' . $this->dynDir->getBaseDir()
        . 'gallery?page='.$prev.'"><span aria-hidden="true">&laquo;</span></a>';
        $page_navi .= '</li>';
        for ($i = 1; $i <= $runner; $i++) {
            if ($i == $curr) {
                $page_navi .= '<li class="active">';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.$i.'">'.$i.'<span class="sr-only">(current)</span></a>';
                $page_navi .= '</li>';
            } else {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.$i.'">'.$i.'</a>';
                $page_navi .= '</li>';
            }
        }

        if ($runner != $max) {
            $page_navi .= '<li class="disabled">';
            $page_navi.= '<a href="">...</a>';
            $page_navi .= '</li>';
        }

        // view per given state
        // 0 : default part
        // 1 : middle part
        // 2 : last part
        if ($state == 1) {
            if (($preprev-2) > $runner) {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.($preprev - 2).'">'.($preprev - 2).'</a>';
                $page_navi .= '</li>';
            }

            if (($preprev-1) > $runner) {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.($preprev - 1).'">'.($preprev - 1).'</a>';
                $page_navi .= '</li>';
            }

            if (($preprev) > $runner) {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir() .
                'gallery?page='.$preprev.'">'.$preprev.'</a>';
                $page_navi .= '</li>';
            }

            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.$prev.'">'.$prev.'</a>';
            $page_navi .= '</li>';

            $page_navi .= '<li class="active">';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.$curr.'">'.$curr.'<span class="sr-only">(current)</span></a>';
            $page_navi .= '</li>';

            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.$next.'">'.$next.'</a>';
            $page_navi .= '</li>';

            if ($postnext <= $max) {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.$postnext.'">'.$postnext.'</a>';
                $page_navi .= '</li>';
            }

            if (($postnext+1) <= $max) {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.($postnext+1).'">'.($postnext+1).'</a>';
                $page_navi .= '</li>';
            }

            if (($postnext+2) <= $max) {
                $page_navi .= '<li>';
                $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
                . 'gallery?page='.($postnext+2).'">'.($postnext+2).'</a>';
                $page_navi .= '</li>';
            }

            // set points only if the current page isnt one page pre maximum
            if ($curr < ($max - 3)) {
                $page_navi .= '<li class="disabled">';
                $page_navi.= '<a href="">...</a>';
                $page_navi .= '</li>';
            }
        } elseif ($state == 2) {
            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.($preprev - 3).'">'.($preprev - 3).'</a>';
            $page_navi .= '</li>';

            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.($preprev - 2).'">'.($preprev - 2).'</a>';
            $page_navi .= '</li>';

            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.($preprev - 1).'">'.($preprev - 1).'</a>';
            $page_navi .= '</li>';

            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.$preprev.'">'.$preprev.'</a>';
            $page_navi .= '</li>';

            $page_navi .= '<li>';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.$prev.'">'.$prev.'</a>';
            $page_navi .= '</li>';

            $page_navi .= '<li class="active">';
            $page_navi.= '<a href="' . $this->dynDir->getBaseDir()
            . 'gallery?page='.$curr.'">'.$curr.'<span class="sr-only">(current)</span></a>';
            $page_navi .= '</li>';
        }

        $page_navi .= '<li>';
        $page_navi .='<a href="' . $this->dynDir->getBaseDir()
        . 'gallery?page='.$max.'">'.$this->t->trans('last').'</a>';
        $page_navi .= '</li>';

        $page_navi .= '<li>';
        $page_navi .='<a href="' . $this->dynDir->getBaseDir()
        . 'gallery?page='.$next.'">&raquo;</a>';
        $page_navi .= '</li>';

        $page_navi .= '<li class="disabled">';
        $page_navi .='<a href="">'.$this->t->trans('page').' '.$curr.' '.$this->t->trans('of').' '.$max.'</a>';
        $page_navi .= '</li>';

        return $page_navi;
    }
}
