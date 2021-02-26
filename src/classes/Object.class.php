<?php


/**
* Class 'Object Data' handles and displays all object data from the mysql database
*
* @author Joerg Stahlmann <>
* @package elements/class
*/
class ObjectData
{
    protected $con;
    protected $t;
    protected $lang;
    protected $content;
    protected $objectFolder;
    protected $category;
    protected $image;
    protected $id;
    protected $overallImage;
    protected $thumbClass;
    protected $dynDir;

    // ###### DATA VARIABLES #################
    protected $objNr;
    protected $frNr;
    protected $title;
    protected $attribution;
    protected $dating;
    protected $owner;
    protected $repo;
    protected $location;
    protected $dimensions;
    protected $support;
    protected $signature;
    protected $originalInscription;
    protected $inscriptions;
    protected $description;
    protected $provenance;
    protected $exhibitions;
    protected $publications;
    protected $interpretations;
    protected $connectedWorks;
    protected $relatedWorks;
    protected $materialTechnique;

    protected $prev;
    protected $next;

    protected $thumbnails;
    protected $page;

    /**
    * Constructor function of the class
    */
    public function __construct()
    {
        require_once('src/classes/DynamicDir.class.php');
        require_once('src/classes/DbConnection.class.php');
        require_once('src/classes/Translator.class.php');
        require_once('src/classes/UriRequest.class.php');
        require_once('src/classes/ImageObject.class.php');
        require_once('src/classes/Metadata.class.php');
        require_once('src/classes/Thumbnails.class.php');
        require_once('src/classes/RelatedWorks.class.php');
        // require_once('FirePHPCore/fb.php');
        // ob_start();

        $this->config = new Config;
        $this->dynDir = new DynamicDir();

        $dbcon = new DbConnection();
        $this->con = $dbcon->getConnection();

        $uriRequest = new UriRequest();
        $this->lang = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';
        $this->t = new Translator('src/xml/locallang/locallang_object.xml');
        $this->page = (isset($_COOKIE['page'])) ? $_COOKIE['page'] : 1;

        $this->content = "";
        $this->title = array();
        $this->attribution = array();
        $this->dating = array();
        $this->publications = array();
        $this->interpretations = array();
        $this->connectedWorks = array();
        $this->metadata = array();

        /** Get the thumbnail metadata **/
        $metadata = new Metadata();
        $this->metadata = $metadata->getMetadata();

        /** Set the object variables **/
        $this->objectFolder = isset($_SESSION['object']['obj']) ? $_SESSION['object']['obj'] : '';
        $this->id = isset($_SESSION['object']['uid']) ? $_SESSION['object']['uid'] : '';
        $this->category = isset($_SESSION['object']['fol']) ? $_SESSION['object']['fol'] : '01_Overall';

        // overall image
        $this->imageObject = new ImageObject($this->id, $this->objectFolder);
        $this->overallImage = $this->imageObject->getOverallImage();

        // current image name
        $this->image = isset($_SESSION['object']['img']) ? $_SESSION['object']['img'] : $this->overallImage;

        // Create Thumbnail Object
        $this->thumbClass = new Thumbnails($this->objectFolder, $this->overallImage);

        // fetch all object data from database
        $this->getAllData();

        // Realated Works
        $this->relatedWorks = new RelatedWorks($this->id, $this->con);

        // set all object reports
        $this->materialTechnique = array();
        $this->materialTechnique = $this->setObjecReports('exam_type');

        $this->condition = array();
        $this->condition = $this->setObjecReports('condition_type');

        $this->conservation = array();
        $this->conservation = $this->setObjecReports('history_type');

        // set closest objects
        $this->setClosestObjects();
        $this->thumbnails = array();
    }

    /**
    * SORT ALGORYTHM
    * for all Object reports, sorted by treatment date
    *
    * @param Integer Report Id a
    * @param Integer Report Id b
    * @return Integer -1 if a > b | 1 if a < b
    */
    private static function sortModul($a, $b)
    {
        $value = 0;
        // get sort number
        $date_a = $a['treatmentDate'];

        // get sort number
        $date_b = $b['treatmentDate'];

        // explode a
        $sort_a = explode(".", $date_a);

        // explode b
        $sort_b = explode(".", $date_b);

        // maximum sort a
        $max_a = count($sort_a)-1;

        // maximum sort b
        $max_b = count($sort_b)-1;

        // compare if equal
        if ($max_a == $max_b) {
            if ($sort_a[$max_a] == $sort_b[$max_b]) {
                if (isset($sort_a[$max_a-1]) && isset($sort_b[$max_b-1])) {
                    if ($sort_a[$max_a-1] == $sort_b[$max_b-1]) {
                        $value = ($sort_a[$max_a-2] > $sort_b[$max_b-2]) ? -1 : 1;
                    } else {
                        $value = ($sort_a[$max_a-1] > $sort_b[$max_b-1]) ? -1 : 1;
                    }
                }
            } else {
                $value = ($sort_a[$max_a] > $sort_b[$max_b]) ? -1 : 1;
            }
        } else {
            // sort if the amount of date entities differs
            if ($sort_a[$max_a] == $sort_b[$max_b]) {
                $value = ($max_a > $max_b) ? -1 : 1;
            } else {
                $value = ($sort_a[$max_a] > $sort_b[$max_b]) ? -1 : 1;
            }
        }
        return $value;
    }

    /**
    * Set previous and next object
    *
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
            // mysql query
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
    * Set previous and next thumbnail
    *
    */
    protected function setClosestThumbnail()
    {
        $this->prevThumb = '';
        $this->nextThumb = '';
        $needle = 0;
        $key = 0;
        $type = 'images';
        $file;
        $thumbFile = $this->image . '.jpg';

        // maximum category
        $max_keys = count($this->thumbnails)-1;

        /**
        * If there are only PDF-documents in the category subtract the max key variable by 1
        */
        while ($max_keys > 0 && empty($this->thumbnails[$max_keys]['images'])
            && empty($this->thumbnails[$max_keys]['rkd'])) {
            $max_keys--;
        }

        // first category name
        $first_category = (isset($_COOKIE['thumb_category'])
            && $_COOKIE['thumb_category'] != 'allImages') ? $this->thumbnails[0]['category'] : '01_Overall';
        // last category name
        $last_category = $this->thumbnails[$max_keys]['category'];
        // last needle of last category
        $last_needle = (empty($this->thumbnails[$max_keys]['rkd'])) ?
            count($this->thumbnails[$max_keys]['images'])-1 : count($this->thumbnails[$max_keys]['rkd'])-1;

        // type of first image
        $first_type = (empty($this->thumbnails[$max_keys]['images'])) ? 'rkd' : 'images';
        // type of last image
        $last_type = (empty($this->thumbnails[$max_keys]['rkd'])) ? 'images' : 'rkd';

        // maximum images in selected category
        $max_needles = 0;
        $remark = '';

        if (is_array($this->thumbnails) || $this->thumbnails instanceof Traversable) {
            // find key and needle for current selected image
            foreach ($this->thumbnails as $k => $thumbs) {
                foreach ($thumbs['images'] as $n => $image) {
                    if ($image == $thumbFile) {
                        $key = $k;
                        $needle = $n;
                        $type = 'images';
                    }
                }

                if (empty($key)) {
                    foreach ($thumbs['rkd'] as $n => $image) {
                        if ($image == $thumbFile) {
                            $key = $k;
                            $needle = $n;
                            $type = 'rkd';
                        }
                    }
                }
            }
        }

        // Previous Thumbnail
        // --------------------------------------------
        $category = $this->thumbnails[$key]['category'];

        if ($key == 0 && $category == $first_category && $needle == 0 && $type == $first_type) {
            $lastThumb = $this->thumbnails[$max_keys][$last_type][$last_needle];

            $file = preg_replace('/.jpg/', '.tif', $lastThumb);

            if ($last_type == 'rkd') {
                $remark = 'RKD';
            }

            $this->prevThumb = $_SERVER['PHP_SELF'];

            $this->prevThumb .= '?obj='.$this->objectFolder . '&fol='.$last_category;

            if (!empty($remark)) {
                $this->prevThumb .=  '&remarks='.$remark;
            }

            $this->prevThumb .=  '&img='.$file;
        } else {
            if ($needle == 0 && $type == $first_type) {
                // set previous category
                $category = $this->thumbnails[$key-1]['category'];

                if (empty($this->thumbnails[$key-1]['rkd'])) {
                    // count prev category and set maximum images
                    $max_needles = count($this->thumbnails[$key-1]['images'])-1;

                    $file = preg_replace('/.jpg/', '.tif', $this->thumbnails[$key-1]['images'][$max_needles]);
                } else {
                    $remark = 'RKD';

                    // count prev category and set maximum images
                    $max_needles = count($this->thumbnails[$key-1]['rkd'])-1;

                    $file = preg_replace('/.jpg/', '.tif', $this->thumbnails[$key-1]['rkd'][$max_needles]);
                }
            } elseif ($needle == 0 && $type != $first_type) {
                // count prev category and set maximum images
                $max_needles = count($this->thumbnails[$key][$first_type])-1;

                $file = preg_replace('/.jpg/', '.tif', $this->thumbnails[$key][$first_type][$max_needles]);

                if ($first_type == 'rkd') {
                    $remark = 'RKD';
                }
            } else {
                if ($type == 'rkd') {
                    $remark = 'RKD';
                }

                $file = preg_replace('/.jpg/', '.tif', $this->thumbnails[$key][$type][$needle-1]);
            }

            $this->prevThumb = $_SERVER['PHP_SELF'];

            $this->prevThumb .= '?obj='.$this->objectFolder . '&fol='.$category;

            if (!empty($remark)) {
                $this->prevThumb .=  '&remarks='.$remark;
            }

            $this->prevThumb .=  '&img='.$file;
        }

        // Next Thumbnail
        // --------------------------------------------
        $category = $this->thumbnails[$key]['category'];

        $remark = '';

        if ($key == $max_keys && $category == $last_category && $needle == $last_needle && $type == $last_type) {
            $firstThumb = $this->thumbnails[0][$first_type][0];

            $file = preg_replace('/.jpg/', '.tif', $firstThumb);

            $this->nextThumb = $_SERVER['PHP_SELF'];

            $this->nextThumb .= '?obj='.$this->objectFolder . '&fol='.$first_category;

            $this->nextThumb .=  '&img='.$file;
        } else {
            // count current category and set maximum images
            $max_needles = count($this->thumbnails[$key][$type])-1;

            if ($needle == $max_needles) {
                if ($type == 'images' && !empty($this->thumbnails[$key]['rkd'])) {
                    $remark = 'RKD';

                    $file = preg_replace('/.jpg/', '.tif', $this->thumbnails[$key]['rkd'][0]);
                } else {
                    // set previous category
                    $category = $this->thumbnails[$key+1]['category'];

                    $nextThumb = $this->thumbnails[$key+1]['images'][0];

                    $file = preg_replace('/.jpg/', '.tif', $nextThumb);
                }
            } else {
                if ($type == 'rkd') {
                    $remark = 'RKD';
                }

                $file = preg_replace('/.jpg/', '.tif', $this->thumbnails[$key][$type][$needle+1]);
            }

            $this->nextThumb = $_SERVER['PHP_SELF'];

            $this->nextThumb .= '?obj='.$this->objectFolder . '&fol='.$category;

            if (!empty($remark)) {
                $this->nextThumb .=  '&remarks='.$remark;
            }

            $this->nextThumb .=  '&img='.$file;
        }
    }

    /**
    * GET ALL DATABASE DATA
    */
    protected function getAllData()
    {
        // +++++++++++++++++
        // OBJECT COMPLETE
        // +++++++++++++++++
        $sql = "SELECT * FROM Object WHERE UId = '$this->id'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // get object number (cda number)
        $this->objNr = (!empty($row->ObjNr)) ? $row->ObjNr : '';

        // get fr number
        $this->frNr = (!empty($row->ObjIdentifier)) ? $row->ObjIdentifier : '';


        // +++++++++++++++++++
        // TITLE COMPLETE
        // +++++++++++++++++++
        $sql = "SELECT * FROM ObjectTitle\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Language = '$this->lang'\n"
        . "ORDER BY DisplayOrder";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // get title
            $title = (!empty($row->Title)) ? $row->Title : '';

            // get title type
            $type = (!empty($row->Titletype)) ? $row->Titletype : '';

            // get remarks of title
            $remarks = (!empty($row->Remarks) && $row->Remarks != 'null') ? $row->Remarks : '';

            // current title
            $current = ($row->DisplayOrder <= 2) ? $row->Title : '';

            // set titel array
            $array = array(
                'title' => $title,
                'type' => $type,
                'remarks' => $remarks,
                'current' => $current
            );

            // push into title array
            array_push($this->title, $array);
        }

        // +++++++++++++++++++++++
        // ATTRIBUTION COMPLETE
        // +++++++++++++++++++++++
        $sql = "SELECT * FROM Attribution\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Language = '$this->lang'\n"
        . "ORDER BY DisplayOrder";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // get attribution
            $attr = (!empty($row->Name)) ? $row->Name : '';

            // get function
            $function = (!empty($row->Function)) ? $row->Function : '';

            // get prefix
            $prefix = (!empty($row->Prefix)) ? $row->Prefix : '';

            // get suffix
            $suffix = (!empty($row->Suffix)) ? $row->Suffix : '';

            // get name type
            $name_type = (!empty($row->NameType)) ? $row->NameType : '';

            // get other name
            $other_name = (!empty($row->OtherName)) ? $row->OtherName : '';

            // get attribution remarks
            $remarks = (!empty($row->Remarks)) ? $row->Remarks : '';

            // get attribution date
            $date = (!empty($row->DisplayDate)) ? $row->DisplayDate : '';

            // get current attribution
            $current = ($row->DisplayOrder <= 2) ? $prefix . ' ' . $row->Name . ' ' . $suffix : '';
            $current = ltrim($current);
            $current = rtrim($current);

            // set attribution array
            $array = array(
                'attr' => $attr,
                'function' => $function,
                'prefix' => $prefix,
                'suffix' => $suffix,
                'name_type' => $name_type,
                'other_name' => $other_name,
                'remarks' => $remarks,
                'date' => $date,
                'current' => $current
            );

            // push into attribution array
            array_push($this->attribution, $array);
        }

        // ++++++++++++++++++++
        // DATING COMPLETE
        // ++++++++++++++++++++
        $sql = "SELECT * FROM Dating\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // get dating
            $dating = (!empty($row->Dating)) ? $row->Dating : '';

            // event type
            $event = (!empty($row->EventType)) ? $row->EventType : '';

            // beginning date
            $begin = (!empty($row->BeginningDate)) ? $row->BeginningDate : '';

            // end date
            $end = (!empty($row->EndDate)) ? $row->EndDate : '';

            // remarks
            $remarks = (!empty($row->Remarks)) ? $row->Remarks : '';

            // current dating
            $current = ($event == "Aktuell" || $event == "Current") ? $row->Dating : '';

            // set dating array
            $array = array(
                'dating' => $dating,
                'event' => $event,
                'begin' => $begin,
                'end' => $end,
                'remarks' => $remarks,
                'current' => $current
            );

            // push into dating array
            array_push($this->dating, $array);
        }

        // +++++++++++++++++++++++++++++++++++++++++++++++++++
        // RELATIONS OWNER / REPOSITORY / LOCATION COMPLETE
        // +++++++++++++++++++++++++++++++++++++++++++++++++++
        // get owner string from translation
        $t_owner = $this->t->trans('owner');

        // get repository string from translation
        $t_repo = $this->t->trans('repo');

        // get location string from translation
        $t_location = $this->t->trans('location');

        // GET OWNER FROM DATABASE
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = '$t_owner'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set object owner
        $this->owner = (!empty($row->Value)) ? $row->Value : '';

        // GET REPOSITORY FROM DATABASE
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = '$t_repo'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set object repository
        $this->repo = (!empty($row->Value)) ? $row->Value : '';

        // GET LOCATION FROM DATABASE
        $sql = "SELECT * FROM Location\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set location
        $this->location = (empty($row->Remarks)) ? $row->Location : $row->Remarks;

        // ++++++++++++++++++++++++++
        // DIMENSIONS COMPLETE
        // ++++++++++++++++++++++++++
        // get dimensions from sql database
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Dimensions'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // explode dimensions
        $dimensions = explode('[', $row->Value);

        // set short description of dimension
        $short = (count($dimensions) > 1) ? $dimensions[0] : $row->Value;

        // set dimensions
        $this->dimensions = (!empty($row->Value)) ? array('short' => $short, 'long' => $row->Value) : '';

        // +++++++++++++++++++++++++++++++++++++++++++
        // MATERIAL / TECHNIQUE COMPLETE --> SUPPORT
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Material/Technik'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set support
        $this->support = (!empty($row->Value)) ? $row->Value : '';

        // +++++++++++++++++++++++++++++++++++++++++++
        // DATIERUNG / SIGNATUR
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type LIKE 'Datierung/K%'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set signature
        $this->signature = (!empty($row->Value)) ? $row->Value : '';

        // +++++++++++++++++++++++++++++++++++++++++++
        // ORIGINAL INSCRIPTION ---> Jetzt in Inscription, Marks, Labels, Seals
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Beschriftung'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set original inscription
        $this->originalInscription = (!empty($row->Value)) ? $row->Value : '';

        // +++++++++++++++++++++++++++++++++++++++++++
        // INSCRIPTIONS, MARKS, LABELS, SEALS
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Stempel/Zeichen'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set inscription
        $this->inscriptions = (!empty($row->Value)) ? $row->Value : '';

        // +++++++++++++++++++++++++++++++++++++++++++
        // DESCRIPTION COMPLETE
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Beschreibung'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set description
        $this->description = (!empty($row->Value)) ? $row->Value : '';

        /**
        * ### ART HISTORICAL INFORMATION ###
        **/

        // +++++++++++++++++++++++++++++++++++++++++++
        // PROVENIENZ
        // nl2br VALUE from Database
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Provenienz'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set provenienz
        $this->provenance = (!empty($row->Value)) ? nl2br($row->Value) : '';

        // +++++++++++++++++++++++++++++++++++++++++++
        // EXHIBITION
        // +++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value FROM MultipleTable\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type = 'Ausstellungsgeschichte'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        $row = mysqli_fetch_object($result);

        // set exhibition
        $this->exhibitions = (!empty($row->Value)) ? nl2br($row->Value) : '';

        // ++++++++++++++++++++++++++++++++++++++++++
        // SOURCES / PUBLICATIONS
        // ++++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT *, o.UId AS id, lo.Remarks AS figure FROM Literature l\n"
        . "INNER JOIN Lit_Object o ON l.Value = o.ReferenceNr\n"
        . "INNER JOIN Lit_LinkedObject lo ON o.UId = lo.Object_UId\n"
        . "WHERE l.Object_UId = '$this->id'\n"
        . "AND lo.ObjNr = '$this->objNr'\n"
        . "ORDER BY o.YearPubl desc";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // id
            $id = $row->id;

            // get publication
            $publication = (!empty($row->Value)) ? $row->Value : '';

            // get page
            $page = (!empty($row->Page)) ? $row->Page : '';

            // get catalogue
            $figure = (!empty($row->figure)) ? $row->figure : '';

            // get catalogue
            $catalogue = (!empty($row->Catalogue)) ? $row->Catalogue : '';

            // remarks
            $remarks = (!empty($row->Remarks)) ? $row->Remarks : '';

            // set reference number
            $refNr = (!empty($row->ReferenceNr)) ? $row->ReferenceNr : '';

            // set title
            $title = (!empty($row->Title)) ? $row->Title : '';

            // set subtitle
            $subtitle = (!empty($row->Subtitle)) ? $row->Subtitle : '';

            // set heading
            $heading = (!empty($row->Heading)) ? $row->Heading : '';

            // set journal
            $journal = (!empty($row->Journal)) ? $row->Journal : '';

            // set series
            $series = (!empty($row->Series)) ? $row->Series : '';

            // set volume
            $volume = (!empty($row->Volume)) ? $row->Volume : '';

            // set edition
            $edition = (!empty($row->Edition)) ? $row->Edition : '';

            // set place published
            $placePubl = (!empty($row->PlacePubl)) ? $row->PlacePubl : '';

            // set year published
            $yearPubl = (!empty($row->YearPubl)) ? $row->YearPubl : '';

            // set the numbers of pages
            $numOfPages = (!empty($row->NumOfPages)) ? $row->NumOfPages : '';

            // set date
            $date = (!empty($row->Date)) ? $row->Date : '';

            // set copyright
            $copyright = (!empty($row->Copyright)) ? $row->Copyright : '';

            // Lit Persons
            // needs extra treatment because of the role
            //-------------------------------------------

            // AUTHOR
            $tmp = array();

            $authors = "";

            $sql = "SELECT * FROM Lit_Persons\n"
            . "WHERE Object_UId = '$id'\n"
            . "AND Role = 'Autor'";

            // mysql query
            $r = mysqli_query($this->con, $sql);

            while ($row = mysqli_fetch_object($r)) {
                array_push($tmp, $row->Name);
            }

            if (!empty($tmp)) {
                foreach ($tmp as $author) {
                    $authors .= (empty($authors)) ? $author : ', '.$author;
                }
            }
            // PUBLISHER
            $tmp = array();

            $publisher = "";

            $sql = "SELECT *  FROM Lit_Persons\n"
            . "WHERE Object_UId = '$id'\n"
            . "AND Role = 'Herausgeber'";

            // mysql query
            $r = mysqli_query($this->con, $sql);

            while ($row = mysqli_fetch_object($r)) {
                array_push($tmp, $row->Name);
            }

            if (!empty($tmp)) {
                foreach ($tmp as $herausgeber) {
                    $publisher .= (empty($publisher)) ? $herausgeber : ', '.$herausgeber;
                }
            }

            // set publications array
            $array = array(
                'id' => $id,
                'publication' => $publication,
                'page' => $page,
                'figure' => $figure,
                'catalogue' => $catalogue,
                'remarks' => $remarks,
                "refNr" => $refNr,
                "title" => $title,
                "subtitle" => $subtitle,
                "heading" => $heading,
                "journal" => $journal,
                "series" => $series,
                "volume" => $volume,
                "edition" => $edition,
                "placePubl" => $placePubl,
                "yearPubl" => !empty($date) ? $date : $yearPubl,
                "numOfPages" => $numOfPages,
                "date" => $date,
                "copyright" => $copyright,
                "authors" => $authors,
                "publisher" => $publisher
            );
            // push into publications array
            array_push($this->publications, $array);
        }

        // ++++++++++++++++++++++++++++++++++++++++
        // INTERPRETATION / HISTORY / DISCUSSION
        // nl2br VALUE from Database
        // ++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Value, Date, Author FROM Description\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Language = '$this->lang'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // get interpretation
            $interpretation = (!empty($row->Value)) ? nl2br($row->Value) : '';

            // get date
            $date = (!empty($row->Date)) ? $row->Date : '';

            // get author
            $author = (!empty($row->Author)) ? $row->Author : '';

            $array = array(
                'interpretation' => $interpretation,
                'date' => $date,
                'author' => $author
            );

            // unset array
            if ($interpretation !== "null") {
                array_push($this->interpretations, $array);
            }
        }

        // ++++++++++++++++++++++++++++++++++++++++
        // CONNECTED WORKS
        // ++++++++++++++++++++++++++++++++++++++++
        $sql = "SELECT Name FROM Linkage\n"
        . "WHERE Object_UId = '$this->id'\n"
        . "AND Type LIKE 'Teil eines Werkes'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // if name is not empty
            if (!empty($row->Name)) {
                // get repository string from translation
                $t_repo = $this->t->trans('repo');

                $sql = "SELECT *, o.ObjIdentifier as frNr, t.Title as title, m.Value as repository\n"
                . "FROM Object o\n"
                . "INNER JOIN ObjectTitle t ON o.UId = t.Object_UId\n"
                . "INNER JOIN Attribution a ON o.UId = a.Object_UId\n"
                . "INNER JOIN MultipleTable m ON o.UId = m.Object_UId\n"
                . "WHERE o.ObjNr = '$row->Name'\n"
                . "AND t.DisplayOrder <= 2\n"
                . "AND a.DisplayOrder <= 2\n"
                . "AND t.Language = '$this->lang'\n"
                . "AND a.Language = '$this->lang'\n"
                . "AND m.Type = '$t_repo'";

                // run query
                $ergebnis = mysqli_query($this->con, $sql);

                // fetch object
                $r = mysqli_fetch_object($ergebnis);

                // if object exists in thumbnails directory
                if (file_exists($this->dynDir->getDir()
                    . 'thumbnails/' . $r->ObjNr . '_' . $r->frNr . '/01_Overall/')) {
                    // open the directory handler
                    if ($handle = opendir($this->dynDir->getDir() . 'thumbnails/'
                     . $r->ObjNr . '_' . $r->frNr . '/01_Overall/')) {
                        // as long as there are files in the directory read em
                        while (false !== ($file = readdir($handle))) {
                            // if file is not dot or double dot
                            if ($file != "." && $file != "..") {
                                // search for main overall file
                                $pos = strpos($file, 'Overall.jpg');
                                // save thumbs
                                if ($pos !== false) {
                                    // save minithumb
                                    $minithumb = $file;
                                }
                            }
                        }

                        // close directory handler
                        closedir($handle);
                    }
                }

                $name = $r->Prefix . ' ' . $r->Name . ' ' . $r->Suffix;
                $name = ltrim($name);
                $name = rtrim($name);

                // create single connected object array
                $array = array(
                    'objNr' => $r->ObjNr,
                    'frNr' => $r->frNr,
                    'title' => $r->title,
                    'repo' => $r->repository,
                    'name' => $name,
                    'minithumb' => $minithumb
                );

                // push into connected works array
                array_push($this->connectedWorks, $array);
            }
        }
    }

    /**
    * Set object report based on the type given
    * by the report parameter.
    *
    * exam_type : Material and Technique
    * condition_type : Condition Report
    * history_type : Conservation Report
    *
    * @param String Type of Report
    * @return Array Reports Data
    *
    */
    protected function setObjecReports($report)
    {
        $reports = array();

        $type = $this->t->trans($report);

        $sql = "SELECT *, r.UId  FROM ObjectReports r\n"
        . "WHERE r.Object_UId = '$this->id'\n"
        . "AND r.SurveyType LIKE '$type'";

        // mysql query
        $result = mysqli_query($this->con, $sql);

        // fetch object
        while ($row = mysqli_fetch_object($result)) {
            // if UId is not empty
            if (!empty($row->UId)) {
                $id = $row->UId;

                $surveyType = $row->SurveyType;

                $treatmentDate = $row->TreatmentDate;

                $entered = $row->Entered;

                $author = $row->Author;

                // set array
                $array = array(
                    'id' => $row->UId,
                    'surveyType' => $row->SurveyType,
                    'project' => $row->Project,
                    'condition' => $row->ConditionReport,
                    'treatment' => $row->TreatmentReport,
                    'treatmentDate' => $row->TreatmentDate,
                    'entered' => $row->Entered,
                    "author" => $row->Author,
                    "files" => $row->Files
                );

                // ONlY FOR MATERIAL AND TECHNIQUE
                //---------------------------------
                if ($report == "exam_type") {
                    $sql = "SELECT * FROM MaterialAndTechnique\n"
                    . "WHERE RestModul_UId = '$id'\n"
                    . "AND Language = '$this->lang'";

                    // mysql query
                    $r = mysqli_query($this->con, $sql);

                    $materialTechnique = array();

                    // fetch object
                    while ($row = mysqli_fetch_object($r)) {
                        $arr = array(
                            "mid" => $row->UId,
                            "purpose" => $row->Purpose,
                            "type" => $row->Type,
                            "remarks" => $row->Remarks,
                            "textField" => nl2br($row->TextField)
                        );
                        array_push($materialTechnique, $arr);
                    }

                    // add material and technique to array
                    $array['restModul'] = $materialTechnique;
                }

                // OPERATORS
                $sql = "SELECT * FROM Operators\n"
                . "WHERE RestModul_UId = '$id'\n"
                . "AND Language = '$this->lang'";

                // mysql query
                $r = mysqli_query($this->con, $sql);

                $operators = array();

                // fetch object
                while ($row = mysqli_fetch_object($r)) {
                    $arr = array(
                        "oid" => $row->UId,
                        "role" => $row->Role,
                        "operator" => $row->Operator
                    );

                    array_push($operators, $arr);
                }

                // add material and technique to array
                $array['operators'] = $operators;

                // push into array
                array_push($reports, $array);
            }
        }

        // sort the modules by date
        usort($reports, array($this, 'sortModul'));

        // return report array
        return $reports;
    }

    /**
    * get breadcrumbs for Object navigation
    *
    * @return String html content
    */
    protected function getBreadcrumbs()
    {
        $content = '<ol class="breadcrumb" style="display:none">'
        . '</ol>';

        return $content;
    }

    /**
    * get current selected Image metadata Sidebar-right TOP-MIDDLE
    * Data is evaluated by the Metadata Class
    *
    * @return String html content
    */
    protected function getMetadata()
    {
        $lang = ($this->lang == 'Deutsch') ? 'de' : 'en';

        $creator = $this->attribution[0]['current'];

        $title = $this->title[0]['current'];

        $date = $this->dating[0]['current'];

        $fileType = (isset($this->metadata['file-type-' . $lang])) ?
            $this->metadata['file-type-' . $lang] : '';

        $imageDesc = (isset($this->metadata['image-description-' . $lang])) ?
            $this->metadata['image-description-' . $lang] : '';

        $imageDate = (isset($this->metadata['image-date-' . $lang])) ?
            $this->metadata['image-date-' . $lang] : '';

        $imageCreated = (isset($this->metadata['image-created-' . $lang])) ?
            $this->metadata['image-created-' . $lang] : '';

        $imageSrc = (isset($this->metadata['image-source-' . $lang])) ?
            $this->metadata['image-source-' . $lang] : '';

        // thumb metadata box
        $content = '<div class="caption">';
        $content .= '<p class="hoffset3">' . $this->t->trans('objCreator') . ': ' . $creator . '</p>';
        $content .= '<p class="hoffset3">' . $this->t->trans('objTitle') .': ' . $title . '</p>';
        $content .= '<p class="hoffset3">' . $this->t->trans('objDate') . ': ' . $date . '</p>';
        $content .= '<p class="hoffset3">' . $this->t->trans('fileType') . ': ' . $fileType . '</p>';
        $content .= '<p class="hoffset3">' . $this->t->trans('imageDesc') . ': ' . $imageDesc . '</p>';
        $content .= '<p class="hoffset3">' . $this->t->trans('imageDate') . ': ' . $imageDate . '</p>';
        $content .= '<p class="hoffset3">' . $this->t->trans('imageCreated') . ': ' . $imageCreated . '</p>';
        $content .= '<p class="hoffset3">'. $this->t->trans('imageSrc') . ': ' . $imageSrc . '</p>';
        $content .= '</div>';

        return $content;
    }

    /**
    * Get Object Classification from XML-File
    *
    * @return String classification
    */
    public function getClassification()
    {
        // return
        return $this->t->trans('classification');
    }

    /**
    * Get Object Number
    *
    * @return String object number
    */
    public function getObjNr()
    {
        // return
        return $this->objNr;
    }

    /**
    * Get Friedlaender Rosenberg Number
    *
    * @return String fr number
    */
    public function getFrNr()
    {
        // return
        return $this->frNr;
    }

    /**
    * Get ObjNr / Friedlaender
    *
    * @return String html content
    */
    protected function getInformation()
    {
        // if no content in database
        ////if(empty($this->frNr)) return '';

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive information identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('classification_data').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<table class="table">';
        $content .= '<tr>'
        . '<td class="col-md-6">' . $this->t->trans('cdaId') . '</td>'
        . '<td class="col-md-6">' . $this->objNr . '</td>'
        . '</tr>';
        $content .= '<tr>'
        . '<td class="col-md-6">' . $this->t->trans('persistent') . '</td>'
        . '<td class="col-md-6"><a href="https://www.lucascranach.org/' . $this->objNr . '">'
        . '<span style="color:#000000; font-size:12px">https://lucascranach.org/' . $this->objNr . '</span></a></td>'
        . '</tr>';
        $content .= '<tr>'
        . '<td class="col-md-6">' . $this->t->trans('frNr') . '</td><td class="col-md-6">' . $this->frNr . '</td>'
        . '</tr>';
        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Title
    *
    * @return String html content
    */
    protected function getTitle()
    {
        // if no content in database
        if (empty($this->title)) {
            return '';
        }

        // modul open
        $view = (isset($_COOKIE['title'])) ? $this->t->trans($_COOKIE['title']) : $this->t->trans('hide');

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive title identification" index="title">';
        $content .= '<div class="panel-heading col-md-3">' . $this->t->trans('title') . ':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<table class="table">';
        foreach ($this->title as $title) {
            $content .= '<tr>'
            . '<td class="col-md-6">' . $title['title'] . '</td><td class="col-md-6">' . $title['remarks'] . '</td>'
            . '</tr>';
        }
        if (count($this->title) > 1) {
            $content .= '<tr><td class="col-md-6 view"><a href="" class="modul-content" id="title">'
            . $view . '</a></td><td></td></tr>';
        }
        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Attribution
    *
    * @return String html content
    */
    protected function getAttribution()
    {
        // if no content in database
        if (empty($this->attribution)) {
            return '';
        }

        // modul open
        $view = (isset($_COOKIE['attr'])) ? $this->t->trans($_COOKIE['attr']) : $this->t->trans('hide');

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive attr identification" index="attr">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('attr').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<table class="table">';
        foreach ($this->attribution as $attr) {
            $content .= '<tr>'
            . '<td class="col-md-6">'.$attr['prefix'].' '.$attr['attr'].' '.$attr['suffix'].'</td>'
            . '<td class="col-md-6">';
            if (!empty($attr['date'])) {
                $content .= $attr['date'].'<br>';
            }
            $content .= $attr['remarks'].'</td>';
            $content .= '</tr>';
        }
        if (count($this->attribution) > 1) {
            $content .= '<tr><td class="col-md-6 view"><a href="" class="modul-content" id="attr">'
            . $view . '</a></td><td></td></tr>';
        }
        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get dating
    *
    * @return String html content
    */
    protected function getDating()
    {
        // if no content in database
        if (empty($this->dating)) {
            return '';
        }

        // modul open
        $view = (isset($_COOKIE['dating'])) ? $this->t->trans($_COOKIE['dating']) : $this->t->trans('hide');

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive dating identification" index="dating">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('dating').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<table class="table">';
        foreach ($this->dating as $dating) {
            $content .= '<tr>'
            . '<td class="col-md-6">'.$dating['dating'].'</td><td class="col-md-6">'.$dating['remarks'].'</td>'
            . '</tr>';
        }
        if (count($this->dating) > 1) {
            $content .= '<tr><td class="col-md-6 view"><a href="" class="modul-content" id="dating">'
            . $view . '</a></td><td></td></tr>';
        }
        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }



    /**
    * Get Owner / Repository / Location
    *
    * @return String html content
    */
    protected function getOwnerRepoLocaction()
    {
        // if no content in database
        //if(empty($this->dating)) return '';

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive owner_repository_location identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('owner_repository_location').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<table class="table">';
        $content .= '<tr>'
        . '<td class="col-md-6">'.$this->t->trans('owner').'</td><td class="col-md-6">'.$this->owner.'</td>'
        . '</tr>';
        $content .= '<tr>'
        . '<td class="col-md-6">'.$this->t->trans('repo').'</td><td class="col-md-6">'.$this->repo.'</td>'
        . '</tr>';
        $content .= '<tr>'
        . '<td class="col-md-6">'.$this->t->trans('location').'</td><td class="col-md-6">'.$this->location.'</td>'
        . '</tr>';
        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Dimension
    *
    * @return String html content
    */
    protected function getDimensions()
    {
        // if no content in database
        if (empty($this->dimensions)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive dimensions identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('dim').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">'.$this->dimensions['long'].'</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Support
    *
    * @return String html content
    */
    protected function getSupport()
    {
        // if no content in database
        if (empty($this->support)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive support identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('support').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">'.$this->support.'</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Signature
    *
    * @return String html content
    */
    protected function getSignature()
    {
        // if no content in database
        if (empty($this->signature)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive signature identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('signature').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">'.$this->signature.'</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Inscriptions, Marks, Labels, Seals
    *
    * @return String html content
    */
    protected function getInscriptions()
    {
        // if no content in database
        if (empty($this->inscriptions) && empty($this->originalInscription)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive inscription identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('inscriptions').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';

        if (!empty($this->originalInscription)) {
            $content .= '<table class="table">';
            if (!empty($this->inscriptions)) {
                $content .= '<tr>'
                . '<td class="col-md-6">'.$this->t->trans('origin_inscription').':</td>'
                . '</tr>';
            }
            $content .= '<tr>'
            . '<td class="col-md-6">'.$this->originalInscription.'</td>'
            . '</tr>';
            $content .= '</table>';
        }

        if (!empty($this->inscriptions)) {
            if (!empty($this->originalInscription)) {
                $content .= '<div class="voffset2">';
            }
            $content .= '<table class="table">';
            if (!empty($this->originalInscription)) {
                $content .= '<tr>'
                . '<td class="col-md-6">'.$this->t->trans('inscriptions_h').':</td>'
                . '</tr>';
            }
            $content .= '<tr>'
            . '<td class="col-md-6">'.$this->inscriptions.'</td>'
            . '</tr>';
            $content .= '</table>';
            if (!empty($this->originalInscription)) {
                $content .= '</div>';
            }
        }

        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Description
    *
    * @return String html content
    */
    protected function getDescription()
    {
        // if no content in database
        if (empty($this->description)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive description identification">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('description').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">'.$this->description.'</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Provenance
    *
    * @return String html content
    */
    protected function getProvenance()
    {
        // if no content in database
        if (empty($this->provenance)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive provenance art_historical_information">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('provenance').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">'.$this->provenance.'</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Exhibitions
    *
    * @return String html content
    */
    protected function getExhibitions()
    {
        // if no content in database
        if (empty($this->exhibitions)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive exhibitions art_historical_information">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('exhibitions').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">'.$this->exhibitions.'</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Publication
    *
    * @return String html content
    */
    protected function getPublications()
    {
        // if no content in database
        if (empty($this->publications)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive sources art_historical_information">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('sources').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';

        $content .= '<table class="table table-hover">';

        $content .= '<tr>'
        . '<td class="col-md-3"></td>'
        . '<td class="col-md-3">'.$this->t->trans('lit_page').'</td>'
        . '<td class="col-md-3">'.$this->t->trans('lit_cat').'</td>'
        . '<td class="col-md-3">'.$this->t->trans('lit_fig').'</td>'
        . '</tr>';

        foreach ($this->publications as $value) {
            $content .= '<tr class="lit_modul" index="'.$value['id'].'">'
            . '<td class="col-md-3"><span style="color:#CC5800">'.$value['publication'].'</span></td>'
            . '<td class="col-md-3">'.$value['page'].'</td>'
            . '<td class="col-md-3">'.$value['catalogue'].'</td>'
            . '<td class="col-md-3">'.$value['figure'].'</td>';

            $content .= '</tr>'
            . '<tr>'
            . '<td colspan="4" style="border:none;">';
            // Inner table
            $content .= '<table id="'.$value['id'].'" class="inner_table" style="display:none">'
            . '<tr>';
            // authors
            if ($value['authors'] != '') {
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_authors').'</td>';
                $content .= '<td class="col-md-8">'.$value['authors'].'</td>';
            }
            $content .= '</tr>';
            $content .= '<tr>';

            // publisher
            if ($value['publisher']!= '') {
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_publisher').'</td>';
                $content .= '<td class="col-md-8">'.$value['publisher'].'</td>';
            }
            $content .= '</tr>';

            // title
            if ($value['title'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_title').'</td>';
                $content .= '<td class="col-md-8">'.$value['title'].'</td>';
                $content .= '</tr>';
            }

            // subtitle
            if ($value['subtitle'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_subtitle').'</td>';
                $content .= '<td class="col-md-8">'.$value['subtitle'].'</td>';
                $content .= '</tr>';
            }

            // journal
            if ($value['journal'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_journal').'</td>';
                $content .= '<td class="col-md-8">'.$value['journal'].'</td>';
                $content .= '</tr>';
            }

            // series
            if ($value['series'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_series').'</td>';
                $content .= '<td class="col-md-8">'.$value['series'].'</td>';
                $content .= '</tr>';
            }

            // volume
            if ($value['volume'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_volume').'</td>';
                $content .= '<td class="col-md-8">'.$value['volume'].'</td>';
                $content .= '</tr>';
            }

            // edition
            if ($value['edition'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_edition').'</td>';
                $content .= '<td class="col-md-8">'.$value['edition'].'</td>';
                $content .= '</tr>';
            }

            // places published
            if ($value['placePubl'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_placePubl').'</td>';
                $content .= '<td class="col-md-8">'.$value['placePubl'].'</td>';
                $content .= '</tr>';
            }

            // year published
            if ($value['yearPubl'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_yearPubl').'</td>';
                $content .= '<td class="col-md-8">'.$value['yearPubl'].'</td>';
                $content .= '</tr>';
            }

            // Number of Pages
            if ($value['numOfPages'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_numOfPages').'</td>';
                $content .= '<td class="col-md-8">'.$value['numOfPages'].'</td>';
                $content .= '</tr>';
            }

            // Copyright
            if ($value['copyright'] != '') {
                $content .= '<tr>';
                $content .= '<td class="col-md-4">'.$this->t->trans('lit_copyright').'</td>';
                $content .= '<td class="col-md-8"><a href="'.$value['copyright'].'">'.$value['copyright'].'</a></td>';
                $content .= '</tr>';
            }

            $content .= '</table>'
            . '</td>'
            . '</tr>';
        }

        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Interpretations
    *
    * @return String html content
    */
    protected function getInterpretations()
    {
        // if no content in database
        if (empty($this->interpretations)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive interpretation art_historical_information">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans('interpretation').':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';
        $content .= '<table class="table">';
        foreach ($this->interpretations as $interpretation) {
            $content .= '<tr>'
            . '<td class="col-md-12">'.$interpretation['interpretation'].'</td>'
            . '</tr>';
        }
        $content .= '</table>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        // return
        return $content;
    }

    /**
    * Get Object Reports by the given
    * report type.
    *
    * Parameter type:
    * examination = Material and Technique
    * condition = Condition Report
    * conservation = History of Restoration
    *
    * @param String Type of report
    * @return String html content
    */
    protected function getObjectReports($type_of_report)
    {
        // init variables
        $object_report = array();

        $heading = '';

        // set the report to the variable
        switch ($type_of_report) {
            case "examination":
                $object_report = $this->materialTechnique;
                $heading = 'material_technique';
                $panelIndex = 'material_technique';
                break;

            case "condition":
                $object_report = $this->condition;
                $heading = 'condition_reports';
                $panelIndex = 'conservation_restoration';
                break;

            case "conservation":
                $object_report = $this->conservation;
                $heading = 'history_reports';
                $panelIndex = 'conservation_restoration';
                break;
        }

        // if no content in database
        if (empty($object_report)) {
            return '';
        }

        $content = '<div class="row">';
        $content .= '<div class="panel panel-data panel-inactive '.$heading.' '.$panelIndex.'">';
        $content .= '<div class="panel-heading col-md-3">'.$this->t->trans($heading).':</div>';
        $content .= '<div class="panel-body col-lg-7 col-md-9">';

        foreach ($object_report as $report) {
            $content .= '<ul>'
            . '<li><b>'.$this->t->trans('date').':</b> '.$report['treatmentDate'].'</li>';

            // ONLY FOR MATERIAL AND TECHNIQUE
            if ($type_of_report == 'examination') {
                $content .= '<li><b>'.$report['restModul'][0]['purpose'].'</b></li>';

                $remarks = array_column($report['restModul'], 'remarks');

                foreach ($remarks as $remark) {
                    if (!empty($remark)) {
                        $list = array();

                        $list = explode('#', $remark);

                        foreach ($list as $item) {
                            $content .= '<li>'.trim($item).'</li>';
                        }
                    }
                }
            }
            //---------------------------------------


            if (!empty($report['files'])) {
                $list = array();

                $list = explode('#', $report['files']);

                foreach ($list as $item) {
                    // init the file array
                    $fileArr = array();
                    // explode the file at '/' character
                    // array first index: folder directory
                    // array second index: filename
                    $fileArr = explode('/', $item);
                    // set folder
                    if (!empty($fileArr[0])) {
                        $category = trim($fileArr[0]);
                    }
                    if (!empty($fileArr[1])) {
                        // set filename
                        $currentFile = trim($fileArr[1]);
                    }

                    // image
                    if (strpos($item, '.tif') !== false) {
                        $thumbFile = preg_replace('/.tif/', '.jpg', $currentFile);
                        $reportImage = $this->dynDir->getDir()
                        . 'thumbnails/' . $this->objectFolder . '/' . $category . '/' .$thumbFile;
                        $trimImage = trim($reportImage);
                        // document
                    } elseif (strpos($item, '.pdf') !== false) {
                        $reportImage = $this->dynDir->getDir()
                        . 'documents/' . $this->objectFolder .'/' . $category . '/' . $currentFile;
                        $trimImage = trim($reportImage);
                    }


                    if (file_exists($trimImage)) {
                        if (strpos($trimImage, '.jpg') !== false) {
                            $content .= '<a href="'. $this->config->getBaseUrl() .'/object.php'
                            . '?obj='.$this->objectFolder
                            . '&uid='.$this->id
                            . '&fol='.$category
                            . '&img='.$currentFile.'">'
                            . '<img src="'.$config->getLegacyImagesBaseUrl().$trimImage.'"'
                            . 'alt="'
                            . htmlspecialchars($this->attribution[0]['current']) . ' - '
                            . htmlspecialchars($this->repo) . ' - '
                            . htmlspecialchars($this->title[0]['current']) . ' - '
                            . $this->t->trans($this->thumbClass->getCleanNameByCategory($category)) . '"'
                            . 'title="'
                            . htmlspecialchars($this->attribution[0]['current']) . ' - '
                            . htmlspecialchars($this->repo) . ' - '
                            . htmlspecialchars($this->title[0]['current']) . ' - '
                            . $this->t->trans($this->thumbClass->getCleanNameByCategory($category)) . '"'
                            . 'width="50px" class="img-thumbnail" />'
                            . '</a>';
                        } elseif (strpos($trimImage, '.pdf') !== false) {
                            $content .= '<a href="'.$trimImage.'" target="_blank" type="application/pdf ">'
                            . '<img class="img-thumbnail " src="'.$this->config->getLegacyImagesBaseUrl(). $this->dynDir->getDir()
                            . 'images/pdf_icon.jpg" width="50px"'
                            . 'title="' . $currentFile . '">'
                            . '</a>';
                        }
                    } // ./file exists
                } // ./for each
            } // ./not empty
            $content .= '</ul>';
            $content .= '<div class="restModul modul_'.$report['id'].'" index="modul_'.$report['id'].'">';

            // ONLY FOR MATERIAL AND TECHNIQUE
            // display all examination
            if ($type_of_report == 'examination') {
                foreach ($report['restModul'] as $restModul) {
                    if ($restModul['type'] != 'Filenames Documents') {
                        $content .= '<ul>';
                        $content .= '<li>'.$restModul['type'].'</li>'
                        . '<li>'.$restModul['textField'].'</li>';
                        $content .= '</ul>';
                    }
                }
            }
            //--------------------------------

            $content .= '<ul>';

            if (!empty($report['condition'])) {
                $content .= '<li>'.$report['condition'].'</li>';
            }
            if (!empty($report['treatment'])) {
                $content .= '<li>'.$report['treatment'].'</li>';
            }
            $content .= '</ul>';
            $content .= '<div class="operators">';
            $filter = array('', ' ', 'Eigentümer', 'Besitzer', 'Erworben von', 'Provenienz', 'Hersteller');
            $content .= '<ul>';

            // display all operators
            foreach ($report['operators'] as $operator) {
                if (!in_array($operator['role'], $filter)) {
                    $content .= '<li><i>'.$operator['role'].': '.$operator['operator'].'</i></li>';
                }
            }
            $content .= '</ul>';
            $content .= '</div>'; // ./operators
            $content .= '</div>'; // ./restModul
            $content .= '<div class="view"'
            . ' style="border-top:1px solid #dfdfdf; margin-bottom: 30px;">'
            . '<a href="" id="modul_' . $report['id'] . '" class="rest-modul">'
            . $this->t->trans('show') . '</a></div>';
        }
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        return $content;
    }

    /**
    * get Overview-Box top-left
    * Displays Overall-Thumbnail, Object Title,
    * Main Attribution, Date and Repository
    * of the selected Object
    *
    * @return String overview html content
    */
    public function getOverview()
    {
        $content = '<div class="thumbnail sidebar overview">'
        . '<img class="img-responsive" '
        . 'alt="'
        . htmlspecialchars($this->attribution[0]['current'])
        . ' - '
        . htmlspecialchars($this->repo)
        . ' - '
        . htmlspecialchars($this->title[0]['current'])
        . ' - '
        . $this->t->trans('overall')
        . '"'
        . 'title="'
        . htmlspecialchars($this->attribution[0]['current'])
        . ' - '
        . htmlspecialchars($this->repo)
        . ' - '
        . htmlspecialchars($this->title[0]['current'])
        . ' - '
        . $this->t->trans('overall')
        . '"'
        . 'src="' .$this->config->getLegacyImagesBaseUrl(). $this->dynDir->getDir() . 'thumbnails/'
        . $this->objectFolder . '/01_Overall/'
        . $this->overallImage . '.jpg">'
        . '<div class="caption">'
        . '<h6>' . $this->title[0]['current'] . '</h6>'
        . '<p>' . $this->dating[0]['current'] . '</p>'
        . '<p>' . $this->attribution[0]['prefix'] . ' '
        . $this->attribution[0]['current'] . ' ' . $this->attribution[0]['suffix'] . '</p>'
        . '<p>' . $this->repo . '</p>'
        //		. '<p>'.$this->objNr.'</p>'
        //		. '<p>'.$this->t->trans('frNr').': '.$this->frNr.'</p>'
        //		. '<p>'.$this->dimensions['short'].'</p>'
        . '</div>';

        // close overview div
        $content .= '</div>';

        // return overview html content
        return $content;
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
        $remarks = '';

        // get thumbnails by category
        $thumb_categories = $this->thumbClass->getAllCategories();

        // get thumbnails by category
        if ($cat == "allImages") {
            $this->thumbnails = $this->thumbClass->getAllThumbnails();
        } elseif ($cat == "rkd") {
            $tmpArr = $this->thumbClass->getThumbnailsByCategory($cat);
            $this->thumbnails = $tmpArr;
        } else {
            $tmpArr = $this->thumbClass->getThumbnailsByCategory($cat);
            array_push($this->thumbnails, $tmpArr);
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
            if (is_array($categories['images']) || $categories['images'] instanceof Traversable) {
                foreach ($categories['images'] as $item) {
                    // IMAGES
                    $fileName = preg_replace('/.jpg/', '.tif', $item);

                    $thumbnails_content .= '<a href="' . $this->config->getBaseUrl() . '/object.php'
                    . '?obj=' . $this->objectFolder
                    . '&fol=' . $category
                    . '&img=' .$fileName
                    . '" class="objLink" draggable="true" ondragstart="drag(event)" ondragend="dragend(event)">'
                    . '<img class="img-thumbnail" width="50px"'
                    . 'alt="'
                    . htmlspecialchars($this->attribution[0]['current']) . ' - '
                    . htmlspecialchars($this->repo) . ' - '
                    . htmlspecialchars($this->title[0]['current']) . ' - '
                    . $this->t->trans($this->thumbClass->getCleanNameByCategory($category)) . '"'
                    . 'title="'
                    . htmlspecialchars($this->attribution[0]['current']) . ' - '
                    . htmlspecialchars($this->repo) . ' - '
                    . htmlspecialchars($this->title[0]['current']) . ' - '
                    . $this->t->trans($this->thumbClass->getCleanNameByCategory($category)) . '"'
                    . 'src="'.$this->config->getLegacyImagesBaseUrl() . '/thumbnails/'
                    . $this->objectFolder . '/' . $category . '/' . $item . '">'
                    . '</a>';
                }
            }

            /**
             * THUMBNAIL CONTAINER RKD
             */
            if (is_array($categories['rkd']) || $categories['rkd'] instanceof Traversable) {
                foreach ($categories['rkd'] as $item) {
                    // check for internal or external RKD directory
                    if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder
                        . '_RKD/11_RKD/' . $category . '/' . $item)) {
                        // external
                        $rkd_dir = $this->dynDir->getDir() .'thumbnails/'
                        . $this->objectFolder
                        . '_RKD/11_RKD/'
                        . $category
                        . '/'
                        . $item;
                    } else {
                        // internal
                        $rkd_dir = $this->dynDir->getDir() . 'thumbnails/'
                        . $this->objectFolder
                        . '/11_RKD/'
                        . $category
                        . '/'
                        . $item;
                    }
                    // IMAGES
                    $fileName = preg_replace('/.jpg/', '.tif', $item);

                    $thumbnails_content .= '<a href="'. $this->config->getBaseUrl() . 'object.php'
                    . '?obj='.$this->objectFolder
                    . '&fol='.$category
                    . '&remarks=RKD'
                    . '&img='.$fileName.'" class="objLink" draggable="true" ondragstart="drag(event)">'
                    . '<img class="img-thumbnail"'
                    . 'alt="'
                    . htmlspecialchars($this->attribution[0]['current']) . ' - '
                    . htmlspecialchars($this->repo) . ' - '
                    . htmlspecialchars($this->title[0]['current']) . ' - '
                    . $this->t->trans($this->thumbClass->getCleanNameByCategory($category)) . '"'
                    . 'title="'
                    . htmlspecialchars($this->attribution[0]['current']) . ' - '
                    . htmlspecialchars($this->repo) . ' - '
                    . htmlspecialchars($this->title[0]['current']) . ' - '
                    . $this->t->trans($this->thumbClass->getCleanNameByCategory($category)) . '"'
                    . 'width="50px" src="'.$config->getLegacyImagesBaseUrl().$rkd_dir.'">'
                    . '</a>';
                }
            }

            /** THUMBNAIL CONTAINER PDF **/
            if (is_array($categories['pdf']) || $categories['pdf'] instanceof Traversable) {
                foreach ($categories['pdf'] as $item) {
                    // PDF
                    $thumbnails_content .= '<a href="' . $this->dynDir->getDir() . 'documents/'
                    . $this->objectFolder
                    . '/' . $category
                    . '/' . $item
                    . '" target="_blank" type="application/pdf ">'
                    . '<img class="img-thumbnail" src="'.$config->getLegacyImagesBaseUrl() . $this->dynDir->getDir()
                    . 'images/pdf_icon.jpg" width="50px" title="' . $item . '">'
                    . '</a>';
                }
            }
        }

        // close div content
        $thumbnails_content .= '</div>';

        /** Thumbnail Return Panel Content **/
        $content = '<div class="panel panel-default">';
        // panel heading
        $content .= '<div class="panel-heading">'
        . '<div class="panel-label">'.$this->t->trans('filterby').':</div>'
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
        . '</div>';

        // panel body
        $content .= '<div class="panel-body">';
        $content .= $thumbnails_content;
        $content .= '</div>' //. panel-body -->
        . '</div>'; //. panel -->

        // return thumbnail html content
        return $content;
    }

    /**
    * get Connected Works TOP-RIGHT
    * Displays Overall-Thumbnails
    * of the Connected Objects
    *
    * @return String html content
    */
    public function getConnectedWorks()
    {
        $content = '';

        // if connected works not empty
        if (!empty($this->connectedWorks)) {
            $content = '<div class="panel panel-default">';
            $content .= '<div class="panel-heading">'.$this->t->trans('connected_works').':</div>';
            $content .= '<div class="panel-body">';
            // run through all connected objects
            foreach ($this->connectedWorks as $value) {
                $content .= '<a href="' . $this->config->getLegacyImagesBaseUrl()
                . $value['objNr'] . '">';
                $content .= '<img class="img-thumbnail" width="50px"'
                . 'alt="'
                . htmlspecialchars($value['name']) . ' - '
                . htmlspecialchars($value['repo']) . ' - '
                . htmlspecialchars($value['title']) . ' - '
                . $this->t->trans('overall')
                . '"'
                . 'title="'
                . htmlspecialchars($value['name']) . ' - '
                . htmlspecialchars($value['repo']) . ' - '
                . htmlspecialchars($value['title']) . ' - '
                . $this->t->trans('overall')
                . '"'
                . 'src="' . $this->dynDir->getDir() . 'thumbnails/'
                . $value['objNr'] . '_' . $value['frNr']
                . '/01_Overall/' . $value['minithumb'] . '">';
                $content .= '</a>';
            }
            $content .= '</div>';
            $content .= '</div>';
        }

        return $content;
    }

    /**
    * get current selected Image Thumbnail Sidebar-right TOP-MIDDLE
    * Displays Big-Thumbnail
    * of the selected Image
    *
    * @return String html content
    */
    public function getBigThumb()
    {
        $remarks = '';
        $src = '';
        $cat = '';

        /**
        * if there is a active thumbnail filter selection
        * the first image of the category is selected
        */
        if (isset($_POST['thumb_category'])) {
            if (!empty($this->thumbnails[0]['images'])) {
                $image = preg_replace('/.jpg/', '', $this->thumbnails[0]['images'][0]);
                $this->image = $image;
                $this->category = $this->thumbnails[0]['category'];
                $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                . $this->category . '/';
            } elseif (!empty($this->thumbnails[0]['rkd'])) {
                $image = preg_replace('/.jpg/', '', $this->thumbnails[0]['rkd'][0]);
                $this->image = $image;
                $this->category = $this->thumbnails[0]['category'];
                $remarks = 'RKD';
                $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_' . $remarks . '/11_RKD/'
                . $this->category . '/';
            } else {
                $this->image = $this->overallImage;
                $this->category = '01_Overall';
                $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                . $this->category . '/';
            }
        } elseif (isset($_COOKIE['thumb_category'])) {
            $currentImageFromCookies = (isset($_COOKIE['current_image'])) ? $_COOKIE['current_image'] : '';

            if (!empty($this->thumbnails[0]['images'])) {
                $this->category = $this->thumbnails[0]['category'];

                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                    . $this->category . '/';
                } elseif (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD'
                    . '/11_RKD/' . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD' . '/11_RKD/'
                    . $this->category . '/';
                } else {
                    // first image in selected category
                    $this->image = preg_replace('/.jpg/', '', $this->thumbnails[0]['images'][0]);
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                    . $this->category . '/';
                }
            } elseif (!empty($this->thumbnails[0]['rkd'])) {
                $this->category = $this->thumbnails[0]['category'];

                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD'
                    . '/11_RKD/' . $this->category . '/' . $currentImageFromCookies . '.jpg')) {
                    $this->image = $currentImageFromCookies;
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD' . '/11_RKD/'
                    . $this->category . '/';
                } else {
                    // first image in selected category
                    $this->image = preg_replace('/.jpg/', '', $this->thumbnails[0]['rkd'][0]);
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                    . $this->category . '/';
                }
                $remarks = 'RKD';
            } else {
                $this->image = $this->overallImage;
                $this->category = '01_Overall';
                $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                . $this->category . '/';
            }
        } else {
            if (isset($_COOKIE['remarks'])) {
                // find image from cookies in selected category?
                if (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD/'
                    . $this->category . '/' . $this->image . '.jpg')) {
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '_RKD/'
                    . $this->category . '/';
                } elseif (file_exists($this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                    . $this->category . '/' . $this->image . '.jpg')) {
                    $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                    . $this->category . '/';
                }
            } else {
                $src = $this->dynDir->getDir() . 'thumbnails/' . $this->objectFolder . '/'
                . $this->category . '/';
            }
        }

        // set prev and next thumb
        $this->setClosestThumbnail();
        // get current category clean name for image title
        $cat = $this->thumbClass->getCleanNameByCategory($this->category);

        // Big Thumb View Box
        $content = '<div class="thumbnail big_thumb sidebar">';
        $content .='<div class="row">';
        $content .='<div class="col-md-12">';
        // create link
        $content .= '<a href="' . $this->config->getBaseUrl() . '/image.php?obj=' . $this->objectFolder . '">';
        // set big thumbnail
        $content .= '<img class="img-responsive"'
        . 'alt="'
        . htmlspecialchars($this->attribution[0]['current']) . ' - '
        . htmlspecialchars($this->repo) . ' - '
        . htmlspecialchars($this->title[0]['current']) . ' - '
        . $this->t->trans($cat)
        . '"'
        . 'title="'
        . htmlspecialchars($this->attribution[0]['current']) . ' - '
        . htmlspecialchars($this->repo) . ' - '
        . htmlspecialchars($this->title[0]['current']) . ' - '
        . $this->t->trans($cat)
        . '"'
        . ' src="' . $this->config->getLegacyImagesBaseUrl().'/'. $src . $this->image . '.jpg'
        . ' ">';
        // close link
        $content .= '</a>';

        //!-- Controls -->
        $content .= '<a class="left carousel-control" href="'.$this->prevThumb.'" role="button">'
        . '<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>'
        . '<span class="sr-only">Previous</span>'
        . '</a>'
        . '<a class="right carousel-control" href="'.$this->nextThumb.'" role="button">'
        . '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>'
        . '<span class="sr-only">Next</span>'
        . '</a>';
        $content .='</div>';
        $content .='</div>';

        $content .='<div class="row">';
        $content .='<div class="col-md-12 hidden-sm hidden-xs">';
        $content .= '<p class="text-center" style="margin-bottom: 2px;">';
        // create link
        $content .= '<a href="' . $this->config->getLegacyImagesBaseUrl() . '/image.php?obj=' . $this->objectFolder . '">';
        $content .= '<span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span>';
        // close link
        $content .= '<h8>'.$this->image.'</h8>';
        $content .= '</p>';
        $content .= '</a>';

        // get METADATA
        $content .= $this->getMetadata();
        $content .='</div>';
        $content .='</div>';

        // close big thumb box
        $content .= '</div>';

        $content .='<div class="row">';
        $content .='<div class="col-md-12 hidden-sm hidden-xs">';
        $content .= $this->getCitingHint();

        $content .= '<p class="hoffset5">'.$this->t->trans('notice_text').'</p>';

        $content .='</div>';
        $content .='</div>';

        return $content;
    }

    /**
    * get citing hint
    * Displays the citing modal option
    *
    * @return String html content
    */
    protected function getCitingHint()
    {
        $text = '<b>Entry with author:</b>'
        . '<br>'
        . '&lt;author\'s name&gt; &lt;title of object, Inventory number, title of document or image&gt;.'
        . '&lt;Name of Database&gt; &lt;&lt;URL&gt;&gt; &lt;date of document&gt; (Accessed &lt;date accessed&gt;)'
        . '<br>'
        . '<i>Example:</i>'
        . '<br>'
        . 'Karl Sch&uuml;tz, \'The Crucifixion of Christ\', AT_KHM_GG6905, Interpretation. '
        . 'In: Cranach Digital Archive &lt;https://www.lucascranach.org/digitalarchive.php&gt; '
        . '01.01.2005 (Accessed: 21.10.2011)'
        .'<br>'
        .'<b>Entry with no author:</b>'
        .'<br>'
        .'&lt;title of object, Inventory number, title of document or image&gt;. '
        . '&lt;Name of Database&gt; &lt;&lt;UR&gt;&gt;  &lt;date of document&gt; (Accessed  &lt;date accessed&gt;)'
        .'<i>Example:</i>'
        .'The Martyrdom of St Catherine, HU_HCBC, Description. '
        . 'In: Cranach Digital Archive  &lt;https://www.lucascranach.org/digitalarchive.php&gt; '
        . '(Accessed: 21.10.2011)';

        //-- Modal -->
        $content = '<div class="modal fade" id="citing" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">'
        . '<div class="modal-dialog modal-lg" role="document">'
        . '<div class="modal-content">'
        . '<div class="modal-header">'
        . '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
        . '<span aria-hidden="true">&times;</span></button>'
        . '<h4 class="modal-title" id="myModalLabel">'.$this->t->trans('citing').'</h4>'
        . '</div>'
        . '<div class="modal-body">'
        . $this->t->trans('citing_text')
        . '</div>'
        . '<div class="modal-footer">'
        . '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';

        $content .= '<p class="hoffset5"><a href="" class="modal-toggle" data-toggle="modal" data-target="#citing">'
        . $this->t->trans('citing')
        . '</a></p>';

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
        . '<div class="navbar-header">'
        . '<button type="button" class="navbar-toggle collapsed" '
        . 'data-toggle="collapse" data-target="#infoNavigation" aria-expanded="false">'
        . '<span class="sr-only">Toggle navigation</span>'
        . '<span class="icon-bar"></span>'
        . '<span class="icon-bar"></span>'
        . '<span class="icon-bar"></span>'
        . '</button>';
        $content .='<a href="" class="navbar-brand lang-btn">' . $this->t->trans('lang') . '</a>';
        $content .='<a href="' . $this->config->getLegacyImagesBaseUrl() . '/gallery?page=' . $this->page . '" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-th" aria-hidden="true"></span></a>';
        $content .='<a href="' . $this->config->getLegacyImagesBaseUrl() . '/image.php?obj='
        . $this->objectFolder . '" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></a>';
        $content .='<a href="' . $this->config->getLegacyImagesBaseUrl() . '/'. $this->prev . '" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span></a>';
        $content .='<a href="'. $this->config->getLegacyImagesBaseUrl() . '/'. $this->next .'" class="navbar-brand">'
        . '<span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></a>';
        $content .= '</div>';

        // <!-- Collect the nav links, forms, and other content for toggling -->
        $content .= '<div class="collapse navbar-collapse" id="infoNavigation">';
        $content .= '<ul class="nav navbar-nav">';

        // list item identification
        $content .= '<li>';

        // button group
        $content .= '<div class="btn-group navbar-btn">';

        // button identifaction
        $content .= '<button id="identification" index="identification" class="tooltips nav-item btn btn-inverse" '
        . 'title="'.strtoupper($this->t->trans('identification')).'">'
        . $this->t->trans('identification')
        . '</button>';

        // button dropdown toggle
        $content .= '<button data-toggle="dropdown" class="btn dropdown-toggle btn-inverse">'
        . '<span class="caret"></span>'
        . '</button>';

        // dropdown menu
        $content .= '<ul class="dropdown-menu">';
        $content .= '<li><a href="" parent="identification" id="information" '
        . 'index="information" class="tooltips nav-item"  title="'.$this->t->trans('classification').'">'
        . $this->t->trans('classification').'</a></li>';

        $content .= '<li><a href="" parent="identification" id="title" '
        . 'index="title" class="tooltips nav-item"  title="'.$this->t->trans('title').'">'
        . $this->t->trans('title') . '</a></li>';

        $content .= '<li><a href="" parent="identification" id="dating" '
        . 'index="dating" class="tooltips nav-item"  title="'.$this->t->trans('dating').'">'
        . $this->t->trans('dating').'</a></li>';

        $content .= '<li><a href="" parent="identification" id="attr" '
        . 'index="attr" class="tooltips nav-item"  title="'.$this->t->trans('attr').'">'
        . $this->t->trans('attr') . '</a></li>';

        $content .= '<li><a href="" parent="identification" id="owner_repository_location" '
        . 'index="owner_repository_location" class="tooltips nav-item" '
        . 'title="'.$this->t->trans('owner_repository_location').'">'
        . $this->t->trans('owner_repository_location') . '</a></li>';

        $content .= '<li><a href="" parent="identification" id="dimensions" index="dimensions" '
        . 'class="tooltips nav-item"  title="'.$this->t->trans('dim').'">'.$this->t->trans('dim').'</a></li>';

        $content .= '<li><a href="" parent="identification" id="support" index="support" class="tooltips nav-item" '
        . 'title="'.$this->t->trans('support').'">'.$this->t->trans('support').'</a></li>';

        $content .= '<li><a href="" parent="identification" id="signature" index="signature" class="tooltips nav-item" '
        . ' title="'.$this->t->trans('signature').'">'.$this->t->trans('signature').'</a></li>';

        $content .= '<li><a href="" parent="identification" id="inscription" index="inscription" '
        . 'class="tooltips nav-item"  title="'.$this->t->trans('inscriptions').'">'
        . $this->t->trans('inscriptions') . '</a></li>';

        $content .= '<li><a href="" parent="identification" id="description" '
        . 'index="description" class="tooltips nav-item"  title="'.$this->t->trans('description').'">'
        . $this->t->trans('description') . '</a></li>';
        $content .= '</ul>';
        // close btn group
        $content .= '</div>';
        // close identification li
        $content .= '</li>';

        // list item historical information
        $content .= '<li>';
        // button group
        $content .= '<div class="btn-group navbar-btn">';

        // button historical information
        $content .= '<button id="art_historical_information" index="art_historical_information" '
        . 'class="tooltips nav-item btn btn-inverse" '
        . 'title="'.strtoupper($this->t->trans('art_historical_information')).'">'
        . $this->t->trans('art_historical_information')
        . '</button>';

        // button dropdown toggle
        $content .= '<button data-toggle="dropdown" class="btn dropdown-toggle btn-inverse">'
        . '<span class="caret"></span>'
        . '</button>';

        // dropdown menu
        $content .= '<ul class="dropdown-menu">';
        $content .= '<li><a href="" parent="art_historical_information" id="provenance" '
        . 'index="provenance" class="tooltips nav-item" '
        . 'title="' . $this->t->trans('provenance').'">'.$this->t->trans('provenance').'</a></li>';

        $content .= '<li><a href="" parent="art_historical_information" id="exhibitions" '
        . 'index="exhibitions" class="tooltips nav-item" title="' . $this->t->trans('exhibitions') . '">'
        . $this->t->trans('exhibitions') . '</a></li>';

        $content .= '<li><a href="" parent="art_historical_information" id="sources" index="sources" '
        . 'class="tooltips nav-item" title="'.$this->t->trans('sources').'">'.$this->t->trans('sources').'</a></li>';

        $content .= '<li><a href="" parent="art_historical_information" id="interpretation" index="interpretation" '
        . 'class="tooltips nav-item" title="'.$this->t->trans('interpretation').'">'
        . $this->t->trans('interpretation') . '</a></li>';

        $content .= '<li><a href="" parent="art_historical_information" id="related_works" index="related_works" '
        . 'class="tooltips nav-item" title="' . $this->t->trans('related_works') . '">'
        . $this->t->trans('related_works') . '</a></li>';
        $content .= '</ul>';
        // close btn group
        $content .= '</div>';
        // close historical information li
        $content .= '</li>';

        // list item material technique
        $content .= '<li>';
        // button group
        $content .= '<div class="btn-group navbar-btn">';
        // button material technique
        $content .= '<button id="material_technique" index="material_technique" '
        . 'class="tooltips nav-item btn btn-inverse" title="' . strtoupper($this->t->trans('material_technique')) . '">'
        . $this->t->trans('material_technique')
        . '</button>';
        // close btn group
        $content .= '</div>';
        // close material technique li
        $content .= '</li>';

        // list item conservation restoration
        $content .= '<li>';

        // button group
        $content .= '<div class="btn-group navbar-btn">';
        // button conservation restoration
        $content .= '<button id="conservation_restoration" index="conservation_restoration" '
        . 'class="tooltips nav-item btn btn-inverse" '
        . 'title="' . strtoupper($this->t->trans('conservation_restoration')) . '">'
        . $this->t->trans('conservation_restoration')
        . '</button>';

        // button dropdown toggle
        $content .= '<button data-toggle="dropdown" class="btn dropdown-toggle btn-inverse">'
        . '<span class="caret"></span>'
        . '</button>';

        // dropdown menu
        $content .= '<ul class="dropdown-menu">';
        $content .= '<li><a href="" parent="conservation_restoration" id="condition_reports" index="condition_reports" '
        . 'class="tooltips nav-item"  title="' . $this->t->trans('condition_reports') . '">'
        . $this->t->trans('condition_reports') . '</a></li>';

        $content .= '<li><a href="" parent="conservation_restoration" id="history_reports" index="history_reports" '
        . 'class="tooltips nav-item"  title="' . $this->t->trans('history_reports') . '">'
        . $this->t->trans('history_reports') . '</a></li>';
        $content .= '</ul>';

        // close btn group
        $content .= '</div>';
        // close conservation restoration li
        $content .= '</li>';
        $content .= '</ul>'; // <!-- /navbar-nav -->

        $content .='<a data-toggle="collapse" href="#collapseUserArea" aria-expanded="false" '
        . 'aria-controls="collapseUserArea" class="navbar-brand pull-right hidden-sm hidden-xs">'
        . '<span class="glyphicon glyphicon-user" aria-hidden="true"></span></a>';
        $content .= '</div>'; // <!-- / navbar collapse -->
        $content .= '</div>'; // <!-- / container fluid -->
        $content .= '</nav>';

        return $content;
    }

    /**
    * get all data content
    * Displays Right-Sidebar connected Works, Big-Thumbnail,
    * Selected Image Metadata, Subnavigation and all Data
    * of the selected Object
    *
    * @return String data html content
    */
    public function getContent()
    {
        // GET Breadcrumbs
        $content = $this->getBreadcrumbs();

        // IDENTIFICATION PART
        //--------------------------------------
        // GET Information DATA
        $content .= $this->getInformation();

        // GET Title DATA
        $content .= $this->getTitle();

        // GET Attribution DATA
        $content .= $this->getAttribution();

        // GET Dating DATA
        $content .= $this->getDating();

        // GET Owner / Repository / Location DATA
        $content .= $this->getOwnerRepoLocaction();

        // GET Dimensions DATA
        $content .= $this->getDimensions();

        // GET Support DATA
        $content .= $this->getSupport();

        // GET Signature DATA
        $content .= $this->getSignature();

        // GET Inscriptions DATA
        $content .= $this->getInscriptions();

        // GET Description DATA
        $content .= $this->getDescription();


        // ART HISTORICAL INFORMATION PART
        //--------------------------------------
        // GET Provenance DATA
        $content .= $this->getProvenance();

        // GET Exhibitions DATA
        $content .= $this->getExhibitions();

        // GET Publications DATA
        $content .= $this->getPublications();

        // GET Interpretation DATA
        $content .= $this->getInterpretations();

        // GET Related Works DATA
        $content .= $this->relatedWorks->getRelatedWorks();

        // GET Object Reports DATA
        $filter = array('examination', 'condition', 'conservation');

        foreach ($filter as $report) {
            $content .= $this->getObjectReports($report);
        }

        return $content;
    }
}
