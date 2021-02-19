<?php
/**
 * Class 'Literature' is a 'Cranach Digital Archive' extension.
 *
 * @author Joerg Stahlmann <>
 * @package elements/classes
 */
class Literature
{
    // selected language:
    protected $selectedLanguage;
    // translation class object:
    protected $t;
    // Database connection:
    protected $con;
    // page
    protected $page;
    // index of object
    protected $index;
    // object number
    protected $objNr;
    // active search
    protected $active;

    /**
    * Constructor function of the class
    */
    public function __construct()
    {
        // Import of the required subclasses
        require_once("src/classes/Translator.class.php");
        // db connection class
        require_once('src/classes/DbConnection.class.php');

        // get the language from the session
        $this->selectedLanguage = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';

        // create translation object
        $this->t = new Translator('src/xml/locallang/locallang_literature.xml');

        // create database object
        $dbcon = new DbConnection();
        // get the db return value
        $this->con = $dbcon->getConnection();

        // get page
        $this->page = (isset($_GET['page'])) ? $_GET['page'] : 1;

        // set active search
        $this->active = false;

        // Save the input of the search field into a Session
        if (isset($_POST['search'])) {
            $search_input = $_POST['search'];
            // Create Session variable
            $_SESSION['search_input'] = $search_input;
            if (count($search_input) > 1) {
                // set active search
                $this->active = true;
            }
        }

        // Check if there is an empty search field post
        if (isset($_POST['reset_search'])) {
            unset($_SESSION['search_input']);
        }
    }

    /**
    * get navigation
    * Displays all single data navigation options
    *
    * @return String navigation html content
    */
    public function getNavigation($view = "list")
    {
        if ($view == "object") {
            $callback = '';

            if (isset($_GET['index'])) {
                $callback = '#' . $_GET['index'];
            }

            // get Single View PREVIOUS ID
            if (isset($_GET['prev'])) {
                // get SINGLE VIEW html content
                $prev = $_GET['prev'];
            }

            // get Single View NEXT ID
            if (isset($_GET['next'])) {
                // get SINGLE VIEW html content
                $next = $_GET['next'];
            }

            $content = '<nav class="navbar navbar-inverse navbar-static-top">'
            . '<div class="container-fluid">'
            . '<div class="navbar-header">';
            $content .='<a href="javascript:setLanguage()" class="navbar-brand">'
            . $this->t->trans('lang') . '</a>';
            $content .='<a href="/publications'.$callback.'" class="navbar-brand">
            <span class="glyphicon glyphicon-list" aria-hidden="true"></span></a>';
            $content .='<a href="'.$prev.'" class="navbar-brand">
            <span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span></a>';
            $content .='<a href="'.$next.'" class="navbar-brand">
            <span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></a>';
            $content .= '</div>'; // <!-- / container fluid -->
            $content .= '</nav>';
        } else {
            $content = '<nav class="navbar navbar-inverse navbar-static-top">'
            . '<div class="container-fluid">'
            . '<div class="navbar-header">';
            $content .='<a href="javascript:setLanguage()" class="navbar-brand">'
            . $this->t->trans('lang').'</a>';
            $content .='<a href="" class="navbar-brand">
            <span class="glyphicon glyphicon-home" aria-hidden="true"></span></a>';
            $content .= '</div>'; // <!-- / container fluid -->
            $content .= '</nav>';
        }

        return $content;
    }

    /**
    * Method SEARCH runs a few search algorithm through all data from database
    * and returns the data array
    *
    * @return string array data
    */
    public function search()
    {
        // if no post but set session
        if (isset($_SESSION['search_input'])) {
            $search_inputArr = $_SESSION['search_input'];
        }

        // FULLTEXT SEARCH
        if (!empty($search_inputArr[1])) {
            $noSearch = false;
            $searchArr = array();
            $search_input = $search_inputArr[1];

            $sql = "(SELECT UId FROM Lit_Object\n"
            . "WHERE MATCH (ReferenceNr, Title, Subtitle, Heading, Journal, Series, Volume,\n"
            . "Edition, PlacePubl, YearPubl, NumOfPages, Date, Copyright)\n"
            . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
            . "UNION\n"
            . "(SELECT Object_UId FROM Lit_Persons\n"
            . "WHERE MATCH (Role, Name)\n"
            . "AGAINST ('$search_input' IN BOOLEAN MODE))";

            $ergebnis = mysqli_query($this->con, $sql);
            while ($row = mysqli_fetch_object($ergebnis)) {
                if (!in_array($row->UId, $searchArr) && !empty($row->UId)) {
                    array_push($searchArr, $row->UId);
                }
                if (!in_array($row->Object_UId, $searchArr) && !empty($row->Object_UId)) {
                    array_push($searchArr, $row->Object_UId);
                }
            }
            // Create Session variable
            $subset = $searchArr;

            if (empty($fullset)) {
                $fullset = $subset;
            }

            // clear search result
            $searchResult = array();

            foreach ($subset as $value) {
                // check for redundant entries
                // NOTE: Add if already in array!
                if (in_array($value, $fullset)) {
                      // add search result to subset
                      array_push($searchResult, $value);
                }
            }

            // transfer search results to set
            $fullset = $searchResult;

            // clear subset
            $subset = array();
            // clear filter array
            $searchArr = array();
        }

        /**
        * ###########################################################
        * ADVANCED SEARCH SECTION
        * ###########################################################
        **/


        // init advanced search array:
        $advancedArr = array();

        // AUTHOR ADVANCED SEARCH
        if (!empty($search_inputArr[2])) {
            //error_log('----> IN AUTOR SEARCH: '.$search_inputArr[2]);
            // set author array
            $authorArr = array();

            // set search author variable:
            //$search_author = str_replace("*", "%", $search_inputArr[2]);
            $search_author = $search_inputArr[2];
            // mySql search with wildcard at both sides
            $ergebnis = mysqli_query("SELECT Object_UId FROM
                Lit_Persons WHERE Name LIKE '%$search_author%'", $this->con);
            // fetch entities
            while ($row = mysqli_fetch_object($ergebnis)) {
                $value = $row->Object_UId;
                // if object is NOT already in the conatainer
                if (!in_array($value, $authorArr)) {
                    array_push($authorArr, $value);
                }
            }
            // If there is a Search Selection
            // Run though the Temporary Array (former search) and compare to the filter (array)
            // Add the file to the searchArray if the requirements are ok
            // Create Session variable

            $subset = $authorArr;

            if (empty($fullset)) {
                $fullset = $subset;
            }

            // clear search result
            $searchResult = array();

            foreach ($subset as $value) {
                // check for redundant entries
                // NOTE: Add if already in array!
                if (in_array($value, $fullset)) {
                    // add search result to subset
                    array_push($searchResult, $value);
                }
            }

            // transfer search results to set
            $fullset = $searchResult;

            // clear subset
            $subset = array();
        }

        // REFERENCE NUMBER ADVANCED SEARCH
        if (!empty($search_inputArr[3])) {
            // set title array
            $refNrArr = array();

            // set search title variable:
            $search_refNr = str_replace("*", "%", $search_inputArr[3]);

            // mySql search with wildcard at both sides
            $ergebnis = mysqli_query("SELECT UId FROM Lit_Object WHERE ReferenceNr LIKE '%$search_refNr%'");
            // fetch entities
            while ($row = mysqli_fetch_object($ergebnis)) {
                $value = $row->UId;
                // if object is NOT already in the conatainer
                if (!in_array($value, $refNrArr)) {
                    array_push($refNrArr, $value);
                }
            }
            // If there is a Search Selection
            // Run though the Temporary Array (former search) and compare to the filter (array)
            // Add the file to the searchArray if the requirements are ok
            // Create Session variable

            $subset = $refNrArr;

            if (empty($fullset)) {
                $fullset = $subset;
            }

            // clear search result
            $searchResult = array();

            foreach ($subset as $value) {
                // check for redundant entries
                // NOTE: Add if already in array!
                if (in_array($value, $fullset)) {
                    // add search result to subset
                    array_push($searchResult, $value);
                }
            }

            // transfer search results to set
            $fullset = $searchResult;

            // clear subset
            $subset = array();
        }

        // YEAR ADVANCED SEARCH
        if (!empty($search_inputArr[4])) {
            // set title array
            $yearArr = array();

            // set search title variable:
            $search_year = str_replace("*", "%", $search_inputArr[4]);

            // mySql search with wildcard at both sides
            $ergebnis = mysqli_query("SELECT UId FROM Lit_Object WHERE YearPubl LIKE '%$search_year%'");
            // fetch entities
            while ($row = mysqli_fetch_object($ergebnis)) {
                $value = $row->UId;
                // if object is NOT already in the conatainer
                if (!in_array($value, $yearArr)) {
                    array_push($yearArr, $value);
                }
            }
            // If there is a Search Selection
            // Run though the Temporary Array (former search) and compare to the filter (array)
            // Add the file to the searchArray if the requirements are ok
            // Create Session variable

            $subset = $yearArr;

            if (empty($fullset)) {
                $fullset = $subset;
            }

            // clear search result
            $searchResult = array();

            foreach ($subset as $value) {
                // check for redundant entries
                // NOTE: Add if already in array!
                if (in_array($value, $fullset)) {
                    // add search result to subset
                    array_push($searchResult, $value);
                }
            }

            // transfer search results to set
            $fullset = $searchResult;

            // clear subset
            $subset = array();
        }

        return $fullset;
    }

    /**
     * Method GET ALL DATA stores all data from database
     * and returns the data array
     *
     * @return string array data
     */
    public function getAllData($arr = null)
    {
        // init data array
        $data = array();

        if (empty($arr) && $this->active === false) {
            // Lit_Object
            $ergebnis = mysqli_query("SELECT UId, ReferenceNr, YearPubl, PlacePubl, Title,
                Date, Journal, Subtitle FROM Lit_Object", $this->con);

            while ($row = mysqli_fetch_object($ergebnis)) {
                if (!empty($row->Subtitle) || !empty($row->Journal)) {
                    $description = $this->t->trans("aufsatz");
                } else {
                    $description = $this->t->trans("monographie");
                }
                // GET PERSONS
                $i = 1;
                $name = '';
                $result = mysqli_query("SELECT Name FROM Lit_Persons WHERE Object_UId = '$row->UId'");
                while ($zeile = mysqli_fetch_object($result)) {
                    $name = ($i == 1) ? $zeile->Name : $name.', '.$zeile->Name;
                    $i++;
                }

                // eval last name as sort value
                $split = explode(' ', $name);
                // lastname is the last part of the split array
                $lastname = $split[count($split)-1];
                // fill object array
                $array = array(
                "uid" => $row->UId,
                "refNr" => $row->ReferenceNr,
                "title" => $row->Title,
                "placePubl" => $row->PlacePubl,
                "yearPubl" => $row->YearPubl,
                "date" => $row->Date,
                "autor" => $name,
                "descr" => $description,
                "lastname" => $lastname);

                // fill multi array
                array_push($data, $array);
            }
        } else {
            // run through search result
            foreach ($arr as $id) {
                $sql = "SELECT o.UId, o.ReferenceNr, o.YearPubl, o.PlacePubl, o.Title,\n"
                . "o.Date, o.Journal, o.Subtitle, p.Role, p.Name\n"
                . "FROM Lit_Object o\n"
                . "LEFT JOIN Lit_Persons p\n"
                . "ON (o.UId = p.Object_UId)\n"
                . "WHERE o.UId = '$id'";
                // run mysql query
                $result = mysqli_query($this->con, $sql);
                // fetch row
                $row = mysqli_fetch_object($result);

                if (!empty($row->Subtitle) || !empty($row->Journal)) {
                    $description = $this->t->trans("aufsatz");
                } else {
                    $description = $this->t->trans("monographie");
                }

                // GET PERSONS
                $i = 1;
                $name = '';
                $result = mysqli_query("SELECT Name FROM Lit_Persons WHERE Object_UId = '$row->UId'");
                while ($zeile = mysqli_fetch_object($result)) {
                    $name = ($i == 1) ? $zeile->Name : $name.', '.$zeile->Name;
                    //error_log('----> '.$name);
                    $i++;
                }

                // eval last name as sort value
                $split = explode(' ', $name);
                // lastname is the last part of the split array
                $lastname = $split[count($split)-1];

                // fill object array
                $array = array(
                    "uid" => $row->UId,
                    "refNr" => $row->ReferenceNr,
                    "title" => $row->Title,
                    "placePubl" => $row->PlacePubl,
                    "yearPubl" => $row->YearPubl,
                    "date" => $row->Date,
                    "autor" => $name,
                    "descr" => $description,
                    "lastname" => $lastname);

                    // fill multi array
                    array_push($data, $array);
            }
        }

        // return
        return $data;
    }

    /**
     * Method sorts the given array by its given fields
     * and returns the data array
     *
     * @param array container to be sorted
     * @param field argument to be sorted with
     * @return string array data
     */
    public function sortArrayByFields($arr, $field, $type)
    {
        foreach ($arr as $nr => $inhalt) {
            $uid[$nr]  = strtolower($inhalt['uid']);
            $refNr[$nr]   = strtolower($inhalt['refNr']);
            $title[$nr] = strtolower($inhalt['title']);
            $placePubl[$nr] = strtolower($inhalt['placePubl']);
            $yearPubl[$nr] = strtolower($inhalt['yearPubl']);
            $date[$nr] = strtolower($inhalt['date']);
            $autor[$nr] = strtolower($inhalt['autor']);
            $description[$nr] = strtolower($inhalt['descr']);
            $lastname[$nr] = strtolower($inhalt['lastname']);
        }

        switch ($field) {
            case 'uid':
                $sort = $uid;
                break;
            case 'refNr':
                $sort = $refNr;
                break;
            case 'title':
                $sort = $title;
                break;
            case 'placePubl':
                $sort = $placePubl;
                break;
            case 'yearPubl':
                $sort = $yearPubl;
                break;
            case 'date':
                $sort = $date;
                break;
            case 'autor':
                $sort = $autor;
                break;
            case 'descr':
                $sort = $description;
                break;
            case 'lastname':
                $sort = $lastname;
                break;
            default:
                $sort = $uid;
                break;
        }

        $sort_type = ($type == 'asc') ? SORT_ASC : SORT_DESC;
        array_multisort($sort, $sort_type, $arr);

        // save to session
        $_SESSION['lit_listing'] = $arr;

        return $arr;
    }

    /**
     * Method RUNS THROUGH the DATA-ARRAY
     * and returns the HTML CONTENT
     *
     * @param string sort value
     * @param string sort type
     * @return string html content
     */
    public function getListView($sort = 'refNr', $type = 'asc')
    {
        // if no post but set session
        if (isset($_SESSION['search_input'])) {
            $arr = $this->search();
            // get all data for listview
            $data = $this->getAllData($arr);
        } else {
            // get all data for listView
            $data = $this->getAllData();
        }
        // sort all data by lastname
        $sorted = $this->sortArrayByFields($data, $sort, $type);

        $content = '<div id="box" class="header">'
        . '<table>'
        . '<tr>'
        . '<td width="150px" style="border-right:1px solid #ccc;text-align:center;">'
        . $this->t->trans('lit_RefNr') . '&nbsp;&nbsp;'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=refNr&type=asc"><i class="icon-arrow-up icon"></i></a>'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=refNr&type=desc"><i class="icon-arrow-down icon"></i></a>'
        . '</td>'
        . '<td width="150px" style="border-right:1px solid #ccc;text-align:center;">'
        . $this->t->trans('lit_author') . '&nbsp;&nbsp;'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=lastname&type=asc"><i class="icon-arrow-up icon"></i></a>'
        . '<a href="'.$_SERVER['PHP_SELF'] . '?&sort=lastname&type=desc"><i class="icon-arrow-down icon"></i></a>'
        . '</td>'
        . '<td width="150px" style="border-right:1px solid #ccc;text-align:center;">'
        . $this->t->trans('lit_PlacePubl') . '&nbsp;&nbsp;'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=placePubl&type=asc"><i class="icon-arrow-up icon"></i></a>'
        . '<a href="'. $_SERVER['PHP_SELF'] . '?&sort=placePubl&type=desc"><i class="icon-arrow-down icon"></i></a>'
        . '</td>'
        . '<td width="60px" style="border-right:1px solid #ccc;text-align:center;">'
        . $this->t->trans('year') . '&nbsp;&nbsp;'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=yearPubl&type=asc"><i class="icon-arrow-up icon"></i></a>'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=yearPubl&type=desc"><i class="icon-arrow-down icon"></i></a>'
        . '</td>'
        . '<td width="100px" style="border-right:1px solid #ccc;text-align:center;">'
        . $this->t->trans('textart') . '&nbsp;&nbsp;'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=descr&type=asc"><i class="icon-arrow-up icon"></i></a>'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=descr&type=desc"><i class="icon-arrow-down icon"></i></a>'
        . '</td>'
        . '<td>'
        . $this->t->trans('lit_Title') . '&nbsp;&nbsp;'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=title&type=asc"><i class="icon-arrow-up icon"></i></a>'
        . '<a href="' . $_SERVER['PHP_SELF'] . '?&sort=title&type=desc"><i class="icon-arrow-down icon"></i></a>'
        . '</td>'
        . '</tr>'
        . '</table>'
        . '</div>';
        // run through the data array
        foreach ($sorted as $value) {
            $content .= '<a name="' . $value['uid'] . '"></a>';
            $content .= '<div id="box" page="' . $this->page . '" index="' . $value['uid'] . '" class="listView">'
            . '<table>'
            . '<tr>'
            . '<td width="150px"'
            . 'style="border-right:1px solid #ccc;overflow:hidden;height:50px;padding-left:3px !important;">'
            . $value['refNr'] . '</a></td>'
            . '<td width="150px"'
            . 'style="border-right:1px solid #ccc;overflow:hidden;height:50px;padding-left:3px !important;">'
            . mb_strimwidth($value['autor'], 0, 50, '...') . '</td>'
            . '<td width="150px"'
            . 'style="border-right:1px solid #ccc;overflow:hidden;height:50px;padding-left:3px !important;">'
            . $value['placePubl'] . '</td>'
            . '<td width="60px" style="border-right:1px solid #ccc;overflow:hidden;height:50px;text-align:center;">'
            . $value['yearPubl'] . '</td>'
            . '<td width="100px" style="border-right:1px solid #ccc;overflow:hidden;height:50px;text-align:center;">'
            . '<div style="display:fixed;max-height:50px;overflow:hidden;">' . $value['descr'] . '</div></td>'
            . '<td style="padding-left:3px !important;">'
            . '<div style="display:fixed;max-height:50px;overflow:hidden">'
            . mb_strimwidth($value['title'], 0, 150, '...') . '</div></td>'
            . '</tr>'
            . '</table>'
            . '</div>';
        }

        return $content;
    }

    /**
     * Method GET DATA of SINGLE OBJECT stores all data from database
     * and returns the HTML CONTENT
     *
     * @param int object id
     * @return string html content
     */
    public function getSingleView($id)
    {
        // get id
        //$id = $arr['uid'];
        // set array lit listing array
        $listing = $_SESSION['lit_listing'];
        // get current key in listing array
        $key = $this->searchForId($id, $listing);
        //error_log('-----> KEY: '.$key .' : '.print_r($listing, true));
        // LITERATURE MODUL LANGUAGE
        $lit_modul_h = $this->t->trans('lit_modul');
        $lit_RefNr_h = $this->t->trans('lit_RefNr');
        $lit_Title_h = $this->t->trans('lit_Title');
        $lit_page_h = $this->t->trans('lit_page');
        $lit_fig_h = $this->t->trans('lit_fig');
        $lit_cat_h = $this->t->trans('lit_cat');
        $lit_Subtitle_h = $this->t->trans('lit_Subtitle');
        $lit_Heading_h = $this->t->trans('lit_Heading');
        $lit_Journal_h = $this->t->trans('lit_Journal');
        $lit_Series_h = $this->t->trans('lit_Series');
        $lit_Volume_h = $this->t->trans('lit_Volume');
        $lit_Edition_h = $this->t->trans('lit_Edition');
        $lit_PlacePubl_h = $this->t->trans('lit_PlacePubl');
        $lit_YearPubl_h = $this->t->trans('lit_YearPubl');
        $lit_NumOfPages_h = $this->t->trans('lit_NumOfPages');
        $lit_Date_h = $this->t->trans('lit_Date');
        $lit_Copyright_h = $this->t->trans('lit_Copyright');
        $lit_author_h = $this->t->trans('lit_author');
        $lit_publisher_h = $this->t->trans('lit_publisher');
        $lit_redaktion_h = $this->t->trans('lit_redaktion');


        /**
        * Lit_Object
        **/
        // init LITERATURE OBJECT data array
        $litObj = array();

        // init LITERATURE LINKED OBJECT data array
        $linkedData = array();

        // mysql query
        $ergebnis = mysqli_query("
          SELECT o.*, lo.Catalogue, lo.Remarks, l.Page FROM Lit_Object o
          LEFT JOIN Lit_LinkedObject lo ON (o.ReferenceNr = lo.ObjNr)
          LEFT JOIN Literature l ON (o.ReferenceNr = l.Value)
          WHERE o.UId = '$id'
        ", $this->con);

        while ($row = mysqli_fetch_object($ergebnis)) {
            // fill array
            $litObj = array(
                "uid" => $row->UId,
                "refNr" => $row->ReferenceNr,
                "title" => $row->Title,
                "subtitle" => $row->Subtitle,
                "heading" => $row->Heading,
                "journal" => $row->Journal,
                "series" => $row->Series,
                "volume" => $row->Volume,
                "edition" => $row->Edition,
                "placePubl" => $row->PlacePubl,
                "yearPubl" => $row->YearPubl,
                "numOfPages" => $row->NumOfPages,
                "date" => $row->Date,
                "copyright" => $row->Copyright,
                "catalogue" => $row->Catalogue,
                "figure" => $row->Remarks,
                "pages" => $row->Page
            );
        }
        // Lit_Persons
        $lit_Autor = array();
        $lit_Herausgeber = array();
        $litId = $litObj['uid'];
        $ergebnis = mysqli_query("SELECT * FROM Lit_Persons
            WHERE Object_UId = '$litId' AND Role = 'Autor'", $this->con);

        while ($row = mysqli_fetch_object($ergebnis)) {
            array_push($lit_Autor, $row->Name);
        }

        $ergebnis = mysqli_query("SELECT * FROM Lit_Persons
            WHERE Object_UId = '$litId' AND Role = 'Herausgeber'", $this->con);

        while ($row = mysqli_fetch_object($ergebnis)) {
            array_push($lit_Herausgeber, $row->Name);
        }

        $ergebnis = mysqli_query("SELECT * FROM Lit_Persons
            WHERE Object_UId = '$litId' AND Role = 'Redatkion'", $this->con);

        while ($row = mysqli_fetch_object($ergebnis)) {
            array_push($lit_Redatkion, $row->Name);
        }

        $content = '<div class="singleView" rel="' . $id . '" next="' . $listing[$key+1]['uid'] . '" prev="'
        . $listing[$key-1]['uid'] . '" style="display:block;">';
        $content .=  '<ul style="margin:20px;border:1px solid #ccc;">';
        $content .=  '<li>';
        $content .=  '<span>';
        if ($litObj['refNr'] != '') {
            $content .=  $litObj['refNr'];
        }
        $content .=  '</span><br>';
        $content .=  '<table>';
        $content .=  '<tr>';
        if (count($lit_Autor) > 0) {
            $content .=  '<td>'.$lit_author_h.':</td>';
            $content .=  '<td>';
            foreach ($lit_Autor as $autor) {
                $content .=  $autor.'<br>';
            }
            $content .=  '</td>';
        }
        $content .=  '</tr>';
        $content .=  '<tr>';
        if (count($lit_Herausgeber) > 0) {
            $content .=  '<td>'.$lit_publisher_h.':</td>';
            $content .=  '<td>';
            foreach ($lit_Herausgeber as $herausgeber) {
                $content .=  $herausgeber.'<br>';
            }
            $content .=  '</td>';
        }
        $content .=  '</tr>';
        $content .=  '<tr>';
        if (count($lit_Redaktion) > 0) {
            $content .=  '<td>'.$lit_redaktion_h.':</td>';
            $content .=  '<td>';
            foreach ($lit_Redaktion as $redaktion) {
                $content .=  $redaktion.'<br>';
            }
            $content .=  '</td>';
        }
        $content .=  '</tr>';
        if ($litObj['title'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Title_h.':</td>';
            $content .=  '<td> '.$litObj['title'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['subtitle'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Subtitle_h.':</td>';
            $content .=  '<td> '.$litObj['subtitle'].'</td>';
            $content .=  '</tr>';
        }


        if ($litObj['journal'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Journal_h.':</td>';
            $content .=  '<td> <i>'.$litObj['journal'].'</i></td>';
            $content .=  '</tr>';
        }

        if ($litObj['series'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Series_h.':</td>';
            $content .=  '<td> '.$litObj['series'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['volume'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Volume_h.':</td>';
            $content .=  '<td> '.$litObj['volume'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['edition'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Edition_h.':</td>';
            $content .=  '<td> '.$litObj['edition'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['placePubl'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_PlacePubl_h.':</td>';
            $content .=  '<td> '.$litObj['placePubl'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['yearPubl'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_YearPubl_h.':</td>';
            $content .=  '<td> '.$litObj['yearPubl'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['numOfPages'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_NumOfPages_h.':</td>';
            $content .=  '<td> '.$litObj['numOfPages'].'</td>';
            $content .=  '</tr>';
        }

        if ($litObj['copyright'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_Copyright_h.':</td>';
            $content .=  '<td> <a href="'.$litObj['copyright'].'" target="blank_">"'.$litObj['copyright'].'"</a></td>';
            $content .=  '</tr>';
        }
        $content .=  '</table>';
        $content .=  '<table>';

        if ($litObj['figure'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_fig_h.':</td>';
            $content .=  '<td> '.$litObj['figure'].'</td>';
            $content .=  '</tr>';
        }


        if ($litObj['catalogue'] != '') {
            $content .=  '<tr>';
            $content .=  '<td>'.$lit_cat_h.':</td>';
            $content .=  '<td> '.$litObj['catalogue'].'</td>';
            $content .=  '</tr>';
        }
        $content .=  '</table>';
        $content .=  '</li>';
        $content .=  '</ul>';

        $content .=  '</div>';

        return $content;
    }

    /**
     * The function list all entities, calculates the amount of pages
     * and displays them.
     *
     * @return array returns the page navigation
     */
    public function pageNavigation()
    {
        /** Add the next and prev button controls! **/
        // Amount of all entities in the array
        $entities = count($this->_objArr);

        // evaluate the selected page
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        // evaluate current index
        $this->_index = $this->_mod * ($page - 1);

        // set private page variable
        $this->_page = $page;

        // max page
        $max = ceil($entities / $this->_mod);

        // page skipper
        $skip = 11;

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
            $runner = $skip - 5;
            // number where the points start
            $cut = $skip - 2;
            // state of the navigatin - 1 : middle
            $state = 1;
        } elseif ($page >= $skip && $page != $max) {
            // runner from 1 to selected number
            $runner = $skip - 5;
            // number where the points start
            $cut = $skip - 1;
            // state of the navigatin - 1 : middle
            $state = 1;
        } elseif ($page >= $skip && $page == $max) {
            // runner from 1 to selected number
            $runner = $skip - 4;
            // number where the points start
            $cut = 0;
            // state of the navigatin - 2 : last
            $state = 2;
        }

        // generate the navigation with the evaluated parameters
        $navigation = $this->_navigation($runner, $page, $max, $cut, $state);

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
    private function _navigation($runner, $curr, $max, $skip, $state)
    {
        // prev page
        if ($curr -1 < 1) {
            $prev = 1;
        } else {
            $prev = $curr -1;
        }

        // next page
        if ($curr +1 >= $max) {
            $next = $max;
        } else {
            $next = $curr +1;
        }

        // preprev page
        $preprev = $prev - 1;

        // postnext page
        $postnext = $next + 1;

        // recalculate points if the current page is one page pre maximum
        $runner = ($curr == ($max - 1)) ? $runner + 1 : $runner;

        // write content
        $page_navi = '<div id="list_controls" class="list_controls">'
        . '<table>'
        . '<tr>';
        $page_navi .='<td style="border-left:solid 1px #CCCCCC">'
        . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$prev.'" class="prev"></a>'
        . '</td>';
        for ($i = 1; $i <= $runner; $i++) {
            if ($i == $curr) {
                $page_navi .='<td>'
                . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$i.'"><span>'.$i.'</span></a>'
                . '</td>';
            } else {
                $page_navi .='<td>'
                . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$i.'">'.$i.'</a>'
                . '</td>';
            }
        }

        if ($runner != $max) {
            $page_navi .='<td>'
            . '<a href="">...</a>'
            . '</td>';
        }

        // view per given state
        // 0 : default part
        // 1 : middle part
        // 2 : last part
        if ($state == 1) {
            $page_navi .='
            <td>
            <a href="'.$_SERVER['PHP_SELF'].'?&page='.$prev.'">'.$prev.'</a>
            </td>';
            $page_navi .='
            <td>
            <a href="'.$_SERVER['PHP_SELF'].'?&page='.$curr.'"><span>'.$curr.'</span></a>
            </td>';
            $page_navi .='
            <td>
            <a href="'.$_SERVER['PHP_SELF'].'?&page='.$next.'">'.$next.'</a>
            </td>';
            // set points only if the current page isnt one page pre maximum
            if ($curr != ($max - 1)) {
                $page_navi .='<td>'
                . '<a href="">...</a>'
                . '</td>';
            }
        } elseif ($state == 2) {
            $page_navi .='<td>'
            . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$preprev.'">'.$preprev.'</a>'
            . '</td>';
            $page_navi .='<td>'
            . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$prev.'">'.$prev.'</a>'
            . '</td>';
            $page_navi .='<td>
            <a href="'.$_SERVER['PHP_SELF'].'?&page='.$curr.'"><span>'.$curr.'</span></a>
            </td>';
        }

        $page_navi .= '<td>'
        . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$max.'">'.$this->_t->trans('last').' &raquo;</a>'
        . '</td>'
        . '<td>'
        . '<a href="'.$_SERVER['PHP_SELF'].'?&page='.$next.'" class="next"></a>'
        . '</td>';
        $page_navi .='<td style="background-color:#898989"><label>'.$this->_t->trans('page').' ' . $curr . ' '
        . $this->_t->trans('of') . ' ' . $max . '</label></td>'
        . '</tr>'
        . '</table>'
        . '</div>';

        return $page_navi;
    }

    protected function searchForId($id, $array)
    {
        foreach ($array as $key => $val) {
            if ($val['uid'] === $id) {
                return $key;
            }
        }
        return null;
    }
}
