<?php

/**
 * Class 'User' handles the whole functionality of the User Area.
 * It lists all saved objects, adds the drag n drop functionality and sets the view of the last 5 objects.
 *
 * @author Joerg Stahlmann <>
 * @package elements/class
 */
class RelatedWorks
{
    protected $selectedLanguage;
    protected $t;
    protected $UID;
    protected $eUID;
    protected $relation;
    protected $relatedObj;
    protected $relatedRemarks;
    protected $relatedThumb;
    protected $empty;
    protected $con;
    protected $dynDir;

    /**
    * Constructor function of the class
    */
    public function __construct($uid, $con)
    {
        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        require_once("src/classes/Translator.class.php");
        $this->t = new Translator('src/xml/locallang/locallang_related.xml');

        $this->selectedLanguage = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : 'Englisch';

        if (isset($_COOKIE['lang']) && $this->selectedLanguage != $_COOKIE['lang']) {
            $this->selectedLanguage = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'Englisch';
        }

        $this->con = $con;
        $this->UID = $uid;
        $this->relation = '';
        $this->relatedObj = array();
        $this->relatedRemarks = array();
        $this->relatedThumb = array();
        $this->empty = true;
    }

    /**
    * Run the sql query with the given parameter
    * and save the variables
    *
    * @param string type of relation
    */
    public function runSqlQuery($type)
    {
        $i = 0;
        $relation = $type;

        $this->relatedObj = array();
        $this->relatedRemarks = array();

        $sql = "SELECT * FROM Linkage\n"
        . "WHERE Object_UId = '$this->UID'\n"
        . "AND Type LIKE '$relation'";

        $ergebnis = mysqli_query($this->con, $sql);
        while ($row = mysqli_fetch_object($ergebnis)) {
            $this->relatedObj[$i] =  $row->Name;
            $this->relatedRemarks[$i] = $row->Remarks;
            $i++;
        }
    }

    /**
    * Run the sql query with the given parameter
    * and sets the thumbnail
    *
    * @param string object number
    * @return string thumbnail
    */
    public function getThumbnail($id)
    {
        $str = '';
        $val = $id;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;

        $query= "SELECT * FROM Object WHERE ObjNr = '$val'";

        $ergebnis = mysqli_query($query, $this->con);
        $row = mysqli_fetch_object($ergebnis);

        $this->eUID = $row->UId;

        if ($handle = opendir($this->dynDir->getDir() . 'thumbnails/'
        . $row->ObjNr .'_' . $row->ObjIdentifier . '/01_Overall/')) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $pos = strpos($file, 'Overall.jpg');
                    if ($pos !== false) {
                        // get name of the tif
                        $fileName = preg_replace('/.jpg/', '.tif', $file);
                        // generate the link
                        $str = '<a href="' . $this->dynDir->getBaseDir()
                        . 'object.php?&page=' . $page . '&obj=' .$row->ObjNr .'_'. $row->ObjIdentifier;
                        $str .= '&uid=' . $row->UId . '&fol=01_Overall&img=' . $fileName . '">';
                        $str .= '<img class="img-thumbnail" width="50px" src="'
                        . $this->dynDir->getDir() . 'thumbnails/'
                        . $row->ObjNr . '_' . $row->ObjIdentifier . '/01_Overall/' . $file . '"></a>';
                    }
                }
            }
            closedir($handle);
        }

        return $str;
    }

    /**
    * Run the sql query with the given parameter
    * and return the title of the object
    *
    * @param string object number
    * @return string title
    */
    public function getTitle($id)
    {
        /** Titel and Titeltype**/
        $ergebnis = mysqli_query(
            "Select Title from ObjectTitle
            where Object_UId = '$id'
            and DisplayOrder < 3
            and Language = '$this->selectedLanguage'",
            $this->con
        );

        $row = mysqli_fetch_object($ergebnis);
        $title = $row->Title;

        return $title;
    }

    /**
    * Run the sql query with the given parameter
    * and return the repository of the object
    *
    * @param string object number
    * @return string repository
    */
    public function getRepository($id)
    {
        /** Relations Repository **/
        if ($this->selectedLanguage != 'Englisch') {
            $relType = "Besitzer";
        } else {
            $relType = "Repository";
        }

        $ergebnis = mysqli_query(
            "Select Value from MultipleTable
            where Object_UId = '$id'
            and Type = '$relType'
            and Language = '$this->selectedLanguage'",
            $this->con
        );

        $row = mysqli_fetch_object($ergebnis);
        $repository = $row->Value;

        return $repository;
    }


    /**
    * Get the content of a single entity
    *
    * @param string subrelations
    * @param string Id of the current object
    * @return string html content of the single entity
    */
    public function getEntityContent($subrelations, $id)
    {
        $relatedListing = explode('#', $subrelations);
        $content = '<tr>';
        $content .= '<td>'.$this->getThumbnail($id).'</td>';
        $content .= '<td>'.$id.'</br>'.$this->getTitle($this->eUID).'</br>'.$this->getRepository($this->eUID).'</br>';

        if (count($relatedListing)>0 && $relatedListing[0] != "null") {
            $content .= '<br>';
            $content .= '<ul>';

            foreach ($relatedListing as $value) {
                if ($value == '00') {
                    $listItem = ($this->selectedLanguage == 'Deutsch') ? $relatedListing[1] : $relatedListing[2];
                    $content .= '<li><i>'.$listItem.'</i></li>';
                    break;
                } else {
                    $content .= ($this->t->trans($value) != "") ? '<li><i>'.$this->t->trans($value).'</i></li>' : '';
                }
            }
            $content .= '</ul>';
        }

        $content .= '</td>';
        $content .= '</tr>';

        return $content;
    }

    /**
    * Get the realted Works
    *
    * @return string html content of the related works
    */
    public function getRelatedWorks()
    {
        // fill content
        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive related_works art_historical_information">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('header').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<ul>';

        /** VERSIONEN / VERSIONS **/
        $this->runSqlQuery('inhaltlich verwandt mit');
        // existing relation?
        if (count($this->relatedObj) > 0) {
            $content .= '<li>';
            $content .= '<table class="table related_works_table">';
            $content .= '<tr>';
            $content .= '<td colspan="2">';
            // header
            $content .= '<span>'.$this->t->trans('versions').':</span>';

            $content .= '</td>';

            $content .= '<td></td>';

            $content .= '</tr>';

            // run through all entities
            for ($i = 0; $i < count($this->relatedObj); $i++) {
                $content .= $this->getEntityContent($this->relatedRemarks[$i], $this->relatedObj[$i]);
            }

            $content .= '</table>';

            $content .= '</li>';

            $this->empty = false;
        }

        /** VERGLEICHBARE MOTIVE / SIMILAR MOTIFS **/
        $this->runSqlQuery('Geh%rt thematisch zu');
        // existing relation?
        if (count($this->relatedObj) > 0) {
            $content .= '<li>';
            $content .= '<table class="table related_works_table">';
            $content .= '<tr>';
            $content .= '<td colspan="2">';

            // header
            $content .= '<span>'.$this->t->trans('similar').':</span>';

            $content .= '</td>';

            $content .= '<td></td>';

            $content .= '</tr>';

            // run through all entities
            for ($i = 0; $i < count($this->relatedObj); $i++) {
                $content .= $this->getEntityContent($this->relatedRemarks[$i], $this->relatedObj[$i]);
            }
            $content .= '</table>';
            $content .= '</li>';

            $this->empty = false;
        }

        /** BILDTR�GER AUS DEM SELBEN BAUM GEFERTIGT / SUPPORT MADE FROM THE SAME TREE **/
        $this->runSqlQuery('geh%rt zu');
        // existing relation?
        if (count($this->relatedObj) > 0) {
            $content .= '<li>';
            $content .= '<table class="table related_works_table">';
            $content .= '<tr>';
            $content .= '<td colspan="2">';

            // header
            $content .= '<span>'.$this->t->trans('support').':</span>';

            $content .= '</td>';

            $content .= '<td></td>';

            $content .= '</tr>';

            // run through all entities
            for ($i = 0; $i < count($this->relatedObj); $i++) {
                $content .= $this->getEntityContent($this->relatedRemarks[$i], $this->relatedObj[$i]);
            }
            $content .= '</table>';
            $content .= '</li>';

            $this->empty = false;
        }

        /** GRAFIK / GRAPHIC ART **/
        $this->runSqlQuery('aufgelegt mit');
        // existing relation?
        if (count($this->relatedObj) > 0) {
            $content .= '<li>';
            $content .= '<table class="table related_works_table">';
            $content .= '<tr>';
            $content .= '<td colspan="2">';

            // header
            $content .= '<span>'.$this->t->trans('graphic').':</span>';

            $content .= '</td>';

            $content .= '<td></td>';

            $content .= '</tr>';

            // run through all entities
            for ($i = 0; $i < count($this->relatedObj); $i++) {
                $content .= $this->getEntityContent($this->relatedRemarks[$i], $this->relatedObj[$i]);
            }
            $content .= '</table>';
            $content .= '</li>';

            $this->empty = false;
        }

        /** END **/
        $content .= '</ul>';
        $content .= '</div>'; // ./panel-body
        $content .= '</div>'; // ./panel
        $content .= '</div>'; // ./row

        if ($this->empty) {
            $content = '';
        }

        return $content;
    }
}
