<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Joerg Stahlmann <>
 *  All rights reserved
 *
 *  This script is part of the cranach project. The Cranach Digital Archive (cda) is an interdisciplinary collaborative research resource,
 *	providing access to art historical, technical and conservation information on paintings by Lucas Cranach (c.1472 - 1553) and his workshop.
 *	The repository presently provides information on more than 400 paintings including c.5000 images and documents from 19 partner institutions.
 *
 ***************************************************************/


/**
 * Class 'Advanced Search' handles the whole search and filter process.
 * It lists all search hits, clears sets and evaluates the subsets.
 *
 * @author	Joerg Stahlmann <>
 * @package	elements/class
 */
class AdvancedSearch
{
  // Array of search results:
  protected $searchResult;

  // Array of attributions:
  protected $attr;

  // Array of dates:
  protected $date;

  // Array of collections:
  protected $technique;

  // Array of collections:
  protected $collection;

  // Array of thesaurus:
  protected $thesau;

  // Array of category:
  protected $category;

  // Array of full search:
  protected $search_input;

  // Array of full AA (archivals documents) search:
  protected $aa_search_input;

  // Array of date AA (archivals documents) search:
  protected $aa_date;

  // Array of institution AA (archivals documents) search:
  protected $aa_institution;

  // Database connection:
  protected $con;

  // Current SessionID
  protected $session;

  // Current SessionID
  protected $session_type;

  // Filter Xml
  protected $xpath;

  // Language
  protected $selectedLanguage;

  // active search
  protected $active;

  // search status
  protected $status;

  // active search
  protected $prev_search_empty;

  /**
   * Constructor function of the class
   *
   */
  public function __construct($con, $session_type='default')
  {
    // require_once('src/classes/Config.class.php');
    // $this->config = new Config;
    require_once('src/classes/Helper.class.php');
    $this->helper = new Helper;

    // get the db return value
    $this->con = $con;
    //##########################
    // GET THE SESSON TYPE
    $this->session_type = $session_type;
    // GET THE SESSION ID
    $this->session = ($this->session_type == 'default') ? session_id() : $this->session_type.session_id();
    // ##################################
    // get the search variables from post
    // ##################################

    // UNSET SESSIONS
    // Check if there is an empty search field post
    if(isset($_POST['reset_search'])) {
      unset($_SESSION['search_input']);
      unset($_SESSION['search_attr']);
      unset($_SESSION['search_thesau']);
      unset($_SESSION['search_category']);
      unset($_SESSION['search_date']);
      unset($_SESSION['search_collection']);
      unset($_SESSION['search_tech']);
      unset($_SESSION['search_controller']);
      unset($_SESSION['aa_search_input']);
      unset($_SESSION['aa_date']);
      unset($_SESSION['aa_institution']);
      unset($_COOKIE['page']);
      // Remove search results
      $this->deleteSearchResultById();
    }

    // unset session if new session type
    if($this->session_type == "default") {
      unset($_SESSION['aa_search_input']);
      unset($_SESSION['aa_date']);
      unset($_SESSION['aa_institution']);
    } else {
      unset($_SESSION['search_input']);
      unset($_SESSION['search_attr']);
      unset($_SESSION['search_category']);
      unset($_SESSION['search_date']);
      unset($_SESSION['search_collection']);
      unset($_SESSION['search_tech']);
    }

    // INIT SEARCH CONTROLLER
    $this->active = (isset($_SESSION['search_controller'])) ? (boolean)$_SESSION['search_controller'] : false;

    // PREVIOUS SEARCH WITH NO RESULT
    $this->prev_search_empty = false;

    // attribution
    if(isset($_POST['attr'])) {
      $this->attr							  = array();
      $this->attr 							= $_POST['attr'];
      $_SESSION['search_attr'] 	= $this->attr;
    } else if(isset($_SESSION['search_attr'])) {
      $this->attr 							= $_SESSION['search_attr'];
    }

    // dating
    if(isset($_POST['date'])) {
      $this->date							  = array();
      $this->date 							= $_POST['date'];
      $_SESSION['search_date'] 	= $this->date;
    } else if(isset($_SESSION['search_date'])) {
      $this->date 							= $_SESSION['search_date'];
    }

    // collection
    if(isset($_POST['collection'])) {
      $this->collection							  = array();
      $this->collection 							= $_POST['collection'];
      $_SESSION['search_collection'] 	= $this->collection;
    } else if(isset($_SESSION['search_collection'])) {
      $this->collection 							= $_SESSION['search_collection'];
    }

    // technique
    if(isset($_POST['tech'])) {
      $this->technique							= array();
      $this->technique 							= $_POST['tech'];
      $_SESSION['search_tech'] 	    = $this->technique;
    } else if(isset($_SESSION['search_tech'])) {
      $this->technique 						  = $_SESSION['search_tech'];
    }

    // thesaurus
    if(isset($_POST['thesau'])) {
      $this->thesau							  = array();
      $this->thesau 							= $_POST['thesau'];
      $_SESSION['search_thesau'] 	= $this->thesau;
    } else if(isset($_SESSION['search_thesau'])) {
      $this->thesau 						  = $_SESSION['search_thesau'];
    }

    // category
    if(isset($_POST['category'])) {
      $this->category							  = array();
      $this->category 							= $_POST['category'];
      $_SESSION['search_category'] 	= $this->category;
    } else if(isset($_SESSION['search_category'])) {
      $this->category 						  = $_SESSION['search_category'];
    }

    // Save the input of the search field into a Session
    if(isset($_POST['search'])) {
      $this->search_input       = array();
      $this->search_input       = $_POST['search'];

      // Create Session variable
      $_SESSION['search_input'] = $this->search_input;
    } else if(isset($_SESSION['search_input'])) {
      $this->search_input 		  = $_SESSION['search_input'];
    }

    // ********************************
    // ARCHIVALES DOCUMENTS START HERE
    // ********************************

    // FULL ARCHIVALES TEXT SEARCH
    if(isset($_POST['aa_search'])) {
      $this->aa_search_input       = array();
      $this->aa_search_input       = $_POST['aa_search'];
      // Create Session variable
      $_SESSION['aa_search_input'] = $this->aa_search_input;
    } else if(isset($_SESSION['aa_search_input'])) {
      $this->aa_search_input 		  = $_SESSION['aa_search_input'];
    }

    // search filter date
    if(isset($_POST['aa_date'])) {
      $this->aa_date       = array();
      $this->aa_date       = $_POST['aa_date'];
      // Create Session variable
      $_SESSION['aa_date'] = $this->aa_date;
    } else if(isset($_SESSION['aa_date'])) {
      $this->aa_date 		  = $_SESSION['aa_date'];
    }

    // search filter institutions
    if(isset($_POST['aa_institution'])) {
      $this->aa_institution       = array();
      $this->aa_institution       = $_POST['aa_institution'];
      // Create Session variable
      $_SESSION['aa_institution'] = $this->aa_institution;
    } else if(isset($_SESSION['aa_institution'])) {
      $this->aa_institution 		  = $_SESSION['aa_institution'];
    }

    // ###################################
    // CHECK SEARCH CONTROLLER AND STATUS
    // Search Controller: True if search is activ
    // Status: Status change shows that there is a changed activ search
    // ###################################
  
    $checkbox = (count($this->helper->returnCountable($this->attr)) - 1)
    + (count($this->helper->returnCountable($this->collection)) - 1)
    + (count($this->helper->returnCountable($this->category)))
    + (count($this->helper->returnCountable($this->technique)) - 1)
    + (count($this->helper->returnCountable($this->date)) - 1)
    + (count($this->helper->returnCountable($this->thesau)) - 1)
    + (count($this->helper->returnCountable($this->aa_institution)) - 1)
    + (count($this->helper->returnCountable($this->aa_date)) - 1);

    $inputField = (empty($this->search_input[1])) ? 0 : 1;
    $inputField = (empty($this->search_input[2])) ? $inputField : $inputField++;
    $inputField = (empty($this->search_input[3])) ? $inputField : $inputField++;
    $inputField = (empty($this->search_input[4])) ? $inputField : $inputField++;
    $inputField = (empty($this->search_input[5])) ? $inputField : $inputField++;
    $inputField = (empty($this->aa_search_input[1])) ? $inputField : $inputField++;
    $inputField = (empty($this->aa_search_input[2])) ? $inputField : $inputField++;
    $inputField = (empty($this->aa_search_input[3])) ? $inputField : $inputField++;

    if(isset($_SESSION['status'])) {

      $this->status = $_SESSION['status'];

      if(($checkbox + $inputField) != $this->status) {
        $_SESSION['status'] = ($checkbox + $inputField);
        unset($_COOKIE['page']);
      }
    } else {
        $_SESSION['status'] = ($checkbox + $inputField);
    }

    if(count($this->helper->returnCountable($this->attr)) < 2
    && empty($this->category)
    && count($this->helper->returnCountable($this->collection)) < 2
      && count($this->helper->returnCountable($this->technique)) < 2
      && count($this->helper->returnCountable($this->thesau)) < 2
      && count($this->helper->returnCountable($this->date)) < 2
      && empty($this->search_input[1])
      && empty($this->search_input[2])
      && empty($this->search_input[3])
      && empty($this->search_input[4])
      && empty($this->search_input[5])
      && count($this->helper->returnCountable($this->aa_institution)) < 2
      && count($this->helper->returnCountable($this->aa_date)) < 2
      && empty($this->aa_search_input[1])
      && empty($this->aa_search_input[2])
      && empty($this->aa_search_input[3])
    ) {
      
      $this->active = false;
      unset($_SESSION['search_controller']);
    } else {
      
      // set active search controller
      $this->active = true;
      $_SESSION['search_controller'] = true;
    }
    // ------------------------------------


    // get the language from the session
    $this->selectedLanguage = $_SESSION['lang'];

    // init full set:
    $this->set = array();

    // init search results:
    $this->searchResult = array();

    // delete prev search results by timestamp older then 1 day
    $this->deleteSearchResultByTime();

    // set the document
    $doc = new DOMDocument;

    // load the document
    $doc->load('src/xml/locallang/locallang_filter.xml');

    // create the xpath object
    $this->xpath = new DOMXPath($doc);

    // run the search controller
    $this->runController();

  }


  /**
   * Run the search controller
   * The method starts the mysql requests for each search parameter
   *
   */
  protected function runController()
  {
    // init searchResult
    $searchResult = array();

    // ************************
    // ZUSCHSCHREIBUNG / ATTRIBUTION
    // ************************
    if(!empty($this->attr)) {
      // search for attribution
      foreach($this->attr as $value) {
        // if value is not 0
        if($value == '0') {
          // Delete prev search result
          $this->deleteSearchResultById();
        } else {
          // run mysql search request
          $result = $this->searchAttr($value);
          // set the subset
          $searchResult = $this->setSubset($result, "attr");
        }
      }
    }

    // ************************
    // DATIERUNG / DATING
    // ************************
    // init tmp
    $tmp = 0;
    $query = '';
    // init date query
    if(count($this->helper->returnCountable($this->date)) > 1) {
      // search for date
      foreach($this->date as $value) {
        // if value is not 0
        if($value != '0') {
          // run mysql search request
          $query .= ($tmp == 0) ? $this->searchDate($value) : 'OR '.$this->searchDate($value);
          // incr
          $tmp++;
        }
      }
      if(!empty($query)) {
        // set the subset
        $searchResult = $this->setSubset(array(), "date", $query);
        // write search result into table
        $this->writeResult($searchResult);
      }
    }

    // ************************
    // TECHNIK / TECHNIQUE
    // ************************
    // init tmp
    $tmp = 0;
    $query = '';
    // technique
    if(!empty($this->technique)) {
      // search for attribution
      foreach($this->technique as $value) {
        // if value is not 0
        if($value != '0') {
          // run mysql search request
          $query .= ($tmp == 0) ? $this->searchTech($value) : 'OR '.$this->searchTech($value);
          //incr
          $tmp++;
        }
      }
      if(!empty($query)) {
        // set the subset
        $searchResult = $this->setSubset(array(), "tech", $query);
        // write search result into table
        $this->writeResult($searchResult);
      }
    }

    // ************************
    // Category
    // ************************
    // init tmp
    $tmp = 0;
    $results = array();
    // technique
    if(!empty($this->category)) {
      
      // search for attribution
      foreach($this->category as $value) {
        array_push($results, $this->searchCategory($value));
      }

      if(!empty($results)) {
        // write search result into table
        foreach($results as $result){
          $this->writeResult($result);
        }
      }
    }

    // ************************
    // SAMMLUNGEN / COLLECTION
    // ************************
    // init tmp
    $tmp = 0;
    $query = '';
    // collection
    if(!empty($this->collection)) {
      // search for attribution
      foreach($this->collection as $value) {
        // if value is not 0
        if($value != '0') {
          // run mysql search request
          $result = $this->searchCollection($value);
          if(!empty($result)) {
            $query .= ($tmp == 0) ? $result : 'OR '.$result;
            //incr
            $tmp++;
          }
        }
      }
      if(!empty($query)) {
        // set the subset
        $searchResult = $this->setSubset(array(), "collection", $query);
        // write search result into table
        $this->writeResult($searchResult);
      }
    }

    // ************************
    // THESAURUS
    // ************************
    // init tmp
    $tmp = 0;
    $query = '';
    // collection
    if(!empty($this->thesau)) {
      // search for attribution
      foreach($this->thesau as $value) {
        // if value is not 0
        if($value != '0') {
          // run mysql search request
          $result = "t.Phrase = '$value'";
          // create query
          $query .= ($tmp == 0) ? $result : 'OR '.$result;
          //incr
          $tmp++;
        }
      }
      if(!empty($query)) {
        // set the subset
        $searchResult = $this->setSubset(array(), "thesau", $query);
        // write search result into table
        $this->writeResult($searchResult);
      }
    }


    // ************************
    // TITLE ADVANCED SEARCH
    // ************************
    if(!empty($this->search_input[2])) {

      // set search title variable:
      $search_title = str_replace("*", "%", $this->search_input[2]);

      // run mysql search request
      $query = "t.Title LIKE '%$search_title%'";

      // set subset
      $searchResult = $this->setSubset(array(), "title", $query);
      // write search result into table
      $this->writeResult($searchResult);
    }


    // ************************
    // FR-NO ADVANCED SEARCH
    // ************************
    if(!empty($this->search_input[3])) {

      // set seach title variable:
      $search_fr = $this->search_input[3];
      // run mysql search request
      $query = "o.ObjIdentifier LIKE '%$search_fr%'";
      // set subset
      $searchResult = $this->setSubset(array(), "fr-no", $query);
      // write search result into table
      $this->writeResult($searchResult);
    }

    // ************************
    // LOCATION ADVANCED SEARCH
    // ************************
    if(!empty($this->search_input[4])) {
      // set seach title variable:
      $search_location = $this->search_input[4];

      // run mysql search request
      $query = "l.Location LIKE '%$search_location%'";

      // set subset
      $searchResult = $this->setSubset(array(), "location", $query);
      // write search result into table
      $this->writeResult($searchResult);
    }

    // ************************
    // ID ADVANCED SEARCH
    // ************************
    if(!empty($this->search_input[5])) {
      // set seach title variable:
      $search_id = $this->search_input[5];

      // run mysql search request
      $query = "o.ObjNr LIKE '%$search_id%'";

      // set subset
      $searchResult = $this->setSubset(array(), "refNr", $query);
      // write search result into table
      $this->writeResult($searchResult);
    }


    // ************************
    // FULLTEXT SEARCH
    // ************************
    if(!empty($this->search_input[1])) {
      // init result
      $result = array();
      // get search input
      $search_input		= $this->search_input[1];
      // get fulltext search query
      $result = $this->searchFullText($search_input);
      // set subset
      $searchResult = $this->setSubset($result, "fullText");
      // write search result into table
      $this->writeResult($searchResult);
    }

    // ************************+++++++++++++++++++
    // AA (ARCHIVAL DOCUMENTS) DATIERUNG / DATING
    // ************************+++++++++++++++++++
    // init tmp
    $tmp = 0;
    $query = '';
    // init date query
    if(!empty($this->aa_date)) {
      // search for date
      foreach($this->aa_date as $value) {

        // if value is not 0
        if($value == '0') {
          // Delete prev search result
          $this->deleteSearchResultById();
          // if value is not 0
        } else {
          // run mysql search request
          $query .= ($tmp == 0) ? $this->aa_searchDate($value) : 'OR '.$this->aa_searchDate($value);
          // incr
          $tmp++;
        }
      }
      if(!empty($query)) {
        // set the subset
        $searchResult = $this->setSubset(array(), "AA", $query);
        // write search result into table
        $this->writeResult($searchResult);
      }
    }

    // ************************************************
    // AA (ARCHIVAL DOCUMENTS) INSTITUTION
    // ************************************************
    // init tmp
    $tmp = 0;
    $query = '';
    // collection
    if(!empty($this->aa_institution)) {
      // search for attribution
      foreach($this->aa_institution as $value) {
        // if value is not 0
        if($value != '0') {
          // run mysql search request
          $result = $this->aa_searchInstitution($value);
          if(!empty($result)) {
            $query .= ($tmp == 0) ? $result : 'OR '.$result;
            //incr
            $tmp++;
          }
        }
      }
      if(!empty($query)) {
        // set the subset
        $searchResult = $this->setSubset(array(), "AA", $query);
        // write search result into table
        $this->writeResult($searchResult);
      }
    }

    // ***********************************
    // AA (ARCHIVAL DOCUMENTS) YEAR
    // ***********************************
    if(!empty($this->aa_search_input[2])) {

      // set search title variable:
      $aa_search_year = str_replace("*", "%", $this->aa_search_input[2]);

      // run mysql search request
      $query = "t.DATE LIKE '%$aa_search_year%'";

      // set subset
      $searchResult = $this->setSubset(array(), "AA", $query);
      // check result
      if(empty($searchResult)) $this->prev_search_empty = true;
      // write search result into table
      $this->writeResult($searchResult);
    }

    // ***********************************
    // AA (ARCHIVAL DOCUMENTS) SIGNATURE
    // ***********************************
    if(!empty($this->aa_search_input[3])) {

      // set search title variable:
      $aa_search_sig = str_replace("*", "%", $this->aa_search_input[3]);

      // run mysql search request
      $query = "t.Signature LIKE '%$aa_search_sig%'";

      // set subset
      $searchResult = $this->setSubset(array(), "AA", $query);
      // check result
      if(empty($searchResult)) $this->prev_search_empty = true;
      // write search result into table
      $this->writeResult($searchResult);
    }

    // ***************************************
    // AA (ARCHIVAL DOCUMENTS) FULLTEXT SEARCH
    // ***************************************
    if(!empty($this->aa_search_input[1])) {
      // init result
      $result = array();
      // get search input
      $aa_search_input		= $this->aa_search_input[1];
      // get fulltext search query
      $result = $this->aa_searchFullText($aa_search_input);
      // set subset
      $searchResult = $this->setSubset($result, "aa_fullText");
      // check result
      if(empty($searchResult)) $this->prev_search_empty = true;
      // write search result into table
      $this->writeResult($searchResult);
    }

    // add subset to the main set
    $this->searchResult = $this->fullSet();
  }



  /**
   * Write the result into databse
   *
   * @param array result of the mysql query
   */
  protected function writeResult($result)
  {
    // delete prev search result with the same session id
    $this->deleteSearchResultById();
    //run through all results
    foreach($result as $value) {
      // get id
      $id = $value['id'];
      // get relevance
      $relevance = $value['relevance'];
      // get sort number
      $sort = $value['sort'];
      // if there is no entry yet insert value array into database
      $sql = "INSERT INTO SearchResult (Object_UId, Relevance, SortNumber, SessionId) VALUES ('$id', '$relevance', '$sort', '$this->session')";
      // mysql query
      if (!mysqli_query($this->con, $sql)) {
        die('Error: ' . mysql_error());
      }
    }
  }


  /**
   * Set the subset
   * The method checks for redundant entries,
   * filters them and adds the search results to the subset.
   *
   * @param array result of the mysql query
   * @param string type of subset
   */
  protected function setSubset($result, $type, $query="")
  {

    // ************************
    // ** ATTRIBUTION
    // ** schreibt selbst in die Datenbank SearchResult die Ergebnisse
    // ** und benoetigt daher keinen extra writeResult() aufruf!

    // ************************
    if($type === "attr") {
      foreach($result as $value) {
        // get id
        $id = $value['id'];
        // get relevance
        $relevance = $value['relevance'];
        // get sort number
        $sort = $value['sort'];
        // Search item in sql search results table
        $sql = "SELECT DISTINCT * FROM SearchResult WHERE Object_UId = '$id' AND SessionId = '$this->session'";
        // mysql query
        $r = mysqli_query($this->con, $sql);
        // if there is no entry yet insert value array into database
        if(mysqli_num_rows($r) == 0) {
          $sql = "INSERT INTO SearchResult (Object_UId, Relevance, SortNumber, SessionId) VALUES ('$id', '$relevance', '$sort', '$this->session')";
          // mysql query
          if (!mysqli_query($this->con, $sql)) {
            die('Error: ' . mysql_error());
          }
        } else {
          $row = mysqli_fetch_object($r);
          $new_relevance = ($row->Relevance < $relevance) ? $relevance : $row->Relevance;
          $sql = "UPDATE SearchResult SET Relevance = '$new_relevance' WHERE Object_UId = '$id AND SessionId = '$this->session";
          mysqli_query($this->con, $sql);
        }
      }

      // init search
      $search = array();
      // Search item in sql search results table
      $sql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session' ORDER BY Relevance DESC, SortNumber ASC";
      // mysql query
      $r = mysqli_query($this->con, $sql);
      // if there is no entry yet insert value array into database
      while($row = mysqli_fetch_object($r)) {
        // get Object_UId
        array_push($search, $row->Object_UId);
      }
      // set searchResult
      $searchResult = $search;

      // ************************
      // ** DATE
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "date") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        // Search item in sql search results table
        $sql = "SELECT DISTINCT *, o.UId, d.Object_UId AS d_id FROM Object o\n"
          . "INNER JOIN Dating d ON (o.UId = d.Object_UId)\n"
          . "WHERE d.Language LIKE 'Englisch'\n"
          . "AND d.EventType LIKE 'Current'\n"
          . "AND (".$query.")\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, d.Object_UId AS d_id FROM SearchResult s\n"
          . "INNER JOIN Dating d ON (s.Object_UId = d.Object_UId)\n"
          . "WHERE s.SessionId = '$this->session'\n"
          . "AND d.Language LIKE 'Englisch'\n"
          . "AND d.EventType LIKE 'Current'\n"
          . "AND (".$query.")\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->d_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** TECHNIQUE
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "tech") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        // Search item in sql search results table
        $sql = "SELECT DISTINCT sub.Object_UId AS sub_id, o.SortNumber FROM\n"
          . "(SELECT r.Object_UId FROM ObjectReports as r\n"
          . "INNER JOIN MaterialAndTechnique as m ON r.UId= m.RestModul_UId\n"
          . "WHERE ".$query."\n"
          . "GROUP BY r.Object_UId)as sub\n"
          . "INNER JOIN Object as o ON o.UId = sub.Object_UId\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, sub.Object_UId AS sub_id FROM\n"
          . "(SELECT r.Object_UId FROM ObjectReports as r\n"
          . "INNER JOIN MaterialAndTechnique as m ON r.UId= m.RestModul_UId\n"
          . "WHERE ".$query
          . "GROUP BY r.Object_UId)as sub\n"
          . "INNER JOIN SearchResult s ON (s.Object_UId = sub.Object_UId)\n"
          . "WHERE s.SessionId = '$this->session'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->sub_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** COLLECTION
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "collection") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        $sql = "SELECT DISTINCT multi.Object_UId AS col_id, o.SortNumber FROM MultipleTable multi\n"
          . "INNER JOIN Object as o ON o.UId = multi.Object_UId\n"
          . "WHERE (".$query.")\n"
          . "AND multi.Type = 'Repository'\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, multi.Object_UId AS col_id FROM SearchResult s\n"
          . "INNER JOIN MultipleTable multi ON (s.Object_UId = multi.Object_UId)\n"
          . "WHERE (".$query.")\n"
          . "AND multi.Type = 'Repository'\n"
          . "AND s.SessionId = '$this->session'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->col_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** THESAURUS
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "thesau") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        $sql = "SELECT DISTINCT t.Object_UId AS t_id, o.SortNumber FROM Thesaurus t\n"
          . "INNER JOIN Object as o ON o.UId = t.Object_UId\n"
          . "WHERE (".$query.")\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT DISTINCT s.Object_UId, t.Object_UId AS t_id, s.Relevance, s.SortNumber FROM SearchResult s\n"
          . "INNER JOIN Thesaurus t ON (s.Object_UId = t.Object_UId)\n"
          . "WHERE (".$query.")\n"
          . "AND s.SessionId = '$this->session'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->t_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** TITLE
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "title") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        $sql = "SELECT DISTINCT t.Object_UId AS t_id, o.SortNumber FROM ObjectTitle t\n"
          . "INNER JOIN Object as o ON o.UId = t.Object_UId\n"
          . "WHERE (".$query.")\n"
          . "AND Language Like '$this->selectedLanguage'\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, t.Object_UId AS t_id FROM SearchResult s\n"
          . "INNER JOIN ObjectTitle t ON (s.Object_UId = t.Object_UId)\n"
          . "WHERE (".$query.")\n"
          . "AND s.SessionId = '$this->session'\n"
          . "AND Language Like '$this->selectedLanguage'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->t_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** FR_NO
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "fr-no") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        $sql = "SELECT DISTINCT o.UId AS o_id, o.SortNumber FROM Object o\n"
          . "WHERE (".$query.")\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, o.UId AS o_id FROM SearchResult s\n"
          . "INNER JOIN Object o ON (s.Object_UId = o.UId)\n"
          . "WHERE (".$query.")\n"
          . "AND s.SessionId = '$this->session'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }

      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->o_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** LOCATION
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "location") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        $sql = "SELECT DISTINCT l.Object_UId AS l_id, o.SortNumber FROM Location l\n"
          . "INNER JOIN Object as o ON o.UId = l.Object_UId\n"
          . "WHERE (".$query.")\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, l.Object_UId AS l_id FROM SearchResult s\n"
          . "INNER JOIN Location l ON (s.Object_UId = l.Object_UId)\n"
          . "WHERE (".$query.")\n"
          . "AND s.SessionId = '$this->session'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->l_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** OBJECT REFERENCE NUMBER
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "refNr") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        $sql = "SELECT DISTINCT o.UId AS o_id, o.SortNumber FROM Object o\n"
          . "WHERE (".$query.")\n"
          . "ORDER BY o.SortNumber ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, o.UId AS o_id FROM SearchResult s\n"
          . "INNER JOIN Object o ON (s.Object_UId = o.UId)\n"
          . "WHERE (".$query.")\n"
          . "AND s.SessionId = '$this->session'\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->o_id,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** FULLTEXT SEARCH
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "fullText") {
      // init result array
      $resultArr = array();
      // Search for previous search results
      $checkSql = "SELECT DISTINCT * FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0) {
        // run through parameter result
        foreach($result as $value) {
          // sql string
          $sql = "SELECT UId AS o_id, SortNumber AS o_sort FROM Object WHERE UId='$value'";
          // mysql query
          $ergebnis = mysqli_query($this->con, $sql);
          while($row = mysqli_fetch_object($ergebnis)) {
            // eval relevance points
            $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
            // init items multi array with relevance
            $item = array(
              "id" => $row->o_id,
              "sort" => $row->o_sort,
              "relevance" => $rel
            );
            array_push($resultArr, $item);
          }
        }
      } else {
        // CHECK IF SEARCHRESULT IS IN THE ARRAY
        while($row = mysqli_fetch_object($r)) {
          if(in_array($row->Object_UId, $result)) {
            // init items multi array with relevance
            $item = array(
              "id" => $row->Object_UId,
              "sort" => $row->SortNumber,
              "relevance" => $row->Relevance
            );
            array_push($resultArr, $item);
          }
        }
      }
      // set search result
      $searchResult = $resultArr;

      // ******************************************************************************
      // ** AA (ARCHIVAL DOCUMENTS)
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ******************************************************************************
    } else if($type === "AA") {
      // Search for previous search results
      $checkSql = "SELECT Object_UId FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0 && $this->prev_search_empty==false) {
        // Search item in sql search results table
        $sql = "SELECT DISTINCT *, t.UId AS t_id, t.Sort AS t_sort FROM Trans_Objects t\n"
          . "WHERE t.Sort NOT LIKE ''\n"
          . "AND (".$query.")\n"
          . "ORDER BY t.Sort ASC";
      } else {
        // Search item in sql search results table
        $sql = "SELECT *, s.Object_UId, s.SortNumber AS t_sort, t.UId AS t_id FROM SearchResult s\n"
          . "INNER JOIN Trans_Objects t ON (s.Object_UId = t.UId)\n"
          . "WHERE s.SessionId = '$this->session'\n"
          . "AND (".$query.")\n"
          . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
      }
      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
        // init items multi array with relevance
        $item = array(
          "id" => $row->t_id,
          "sort" => $row->t_sort,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      // set search result
      $searchResult = $result;

      // ************************
      // ** AA (ARCHIVAL DOCUMENTS) FULLTEXT SEARCH
      // ** Ergebnisse werden mit writeResult() in die Tablle SearchResult geschrieben
      // ************************
    } else if($type === "aa_fullText") {
      // init result array
      $resultArr = array();
      // Search for previous search results
      $checkSql = "SELECT DISTINCT * FROM SearchResult WHERE SessionId = '$this->session'";
      // mysql query
      $r = mysqli_query($this->con, $checkSql);
      // if there is no entry yet insert value array into database
      if(mysqli_num_rows($r) == 0 && $this->prev_search_empty==false) {
        // run through parameter result
        foreach($result as $value) {
          // sql string
          $sql = "SELECT t.UId AS t_id, t.Sort AS t_sort FROM Trans_Objects t\n"
            . "WHERE t.UId LIKE '$value'\n"
            . "ORDER BY t.Sort ASC";
          // mysql query
          $ergebnis = mysqli_query($this->con, $sql);
          while($row = mysqli_fetch_object($ergebnis)) {
            // eval relevance points
            $rel = (empty($row->Relevance)) ? 5 : $row->Relevance;
            // init items multi array with relevance
            $item = array(
              "id" => $row->t_id,
              "sort" => $row->t_sort,
              "relevance" => $rel
            );
            array_push($resultArr, $item);
          }
        }
      } else {
        // CHECK IF SEARCHRESULT IS IN THE ARRAY
        while($row = mysqli_fetch_object($r)) {
          if(in_array($row->Object_UId, $result)) {
            // init items multi array with relevance
            $item = array(
              "id" => $row->Object_UId,
              "sort" => $row->SortNumber,
              "relevance" => $row->Relevance
            );
            array_push($resultArr, $item);
          }
        }
      }
      // set search result
      $searchResult = $resultArr;
    }

    // return search result
    return $searchResult;
  }


  /**
   * Set the full set
   * The method checks for redundant entries,
   * filters them and adds the search results to the subset.
   * Furthermore it clears the subset.
   *
   */
  protected function fullSet()
  {
    // init set
    $set = array();
    // check if its first search result
    // Search item in sql search results table
    $sql = "SELECT * FROM SearchResult s\n"
      . "WHERE s.SessionId = '$this->session'\n"
      . "ORDER BY s.Relevance DESC, s.SortNumber ASC";
    // mysql query
    $r = mysqli_query($this->con, $sql);
    // if there is no entry yet insert value array into database
    
    if($this->active === false) {
      // ######################################
      // DEFAULT SEARCH OBJECTS
      // ######################################
      if($this->session_type == "default") {
        // Search item in sql search results table
        $sql = "SELECT * FROM Object ORDER BY SortNumber ASC";
        // mysql query
        $result = mysqli_query($this->con, $sql);
        // if there is no entry yet insert value array into database
        while($row = mysqli_fetch_object($result)) {
          // get Object_UId
          $array = array(
            "uid" => $row->UId,
            "objNr" => $row->ObjNr,
            "frNr" => $row->ObjIdentifier,
            "sortNr" => $row->SortNumber
          );
          // push into set
          array_push($set, $array);
        }
        // ######################################
        // ARCHIVAL DOCUMENTS
        // ######################################
      } else if($this->session_type = "AA_") {
        // SQL STATEMENT
        $sql = "SELECT *, o.UId as id, o.ObjNr as objNr, su.Summary as sum, su.Lang FROM Trans_Objects o\n"
          . "INNER JOIN Trans_Summary su ON (o.ObjNr = su.ObjNr)\n"
          . "WHERE su.Lang LIKE '$this->selectedLanguage'\n"
          . "GROUP BY o.UId\n"
          . "ORDER BY o.Sort ASC";

        // select everything from the fobj table with the given id parameter
        $ergebnis = mysqli_query($this->con, $sql);
        // fetch object
        while($row = mysqli_fetch_object($ergebnis)) {
          // date
          $date = $row->Date;
          // date array
          // 0 - deutsch
          // 1 - english
          $dateArr = explode('#', $date);
          //$dateArr = array();
          // set date
          if(count($dateArr) > 1) {
            // set date for language
            $date = ($this->selectedLanguage == 'Englisch') ? $dateArr[1] : $dateArr[0];
          }

          // UID
          $uid = $row->id;

          // object number
          $objNr = $row->objNr;
          // thumb
          $thumbs = $row->Scans_Renamed;

          // explode
          $thumbArr = explode('#', $thumbs);
          // set first scan
          $scan = trim($thumbArr[0]);
          // sort number
          $sort = $row->Sort;

          // summary
          $summary = $row->sum;

          $summary = (strlen($summary) > 230) ? substr($summary, 0, 230)."..." : $summary;

          // set AA Object Array
          $array = array(
            "uid" => $uid,
            "objNr" => $objNr,
            'date' => $date,
            "scan" => $scan,
            "summary" => $summary,
            "sort" => $sort
          );
          // push into set
          array_push($set, $array);
        }
      }
    } else {
      while($row = mysqli_fetch_object($r)) {
        //get id
        $value = $row->Object_UId;
        // ###########################################
        // DEFAULT SEARCH OBJECTS WITH ACTIVE SEARCH
        // ###########################################
        if($this->session_type == "default") {
          // Search item in sql search results table
          $sql = "SELECT * FROM Object WHERE UId = '$value'";
          // mysql query
          $result = mysqli_query($this->con, $sql);
          // if there is no entry yet insert value array into database
          while($row = mysqli_fetch_object($result)) {
            // get Object_UId
            $array = array(
              "uid" => $row->UId,
              "objNr" => $row->ObjNr,
              "frNr" => $row->ObjIdentifier,
              "sortNr" => $row->SortNumber
            );
            // push into set
            array_push($set, $array);
          }
          // ######################################
          // ARCHIVAL DOCUMENTS
          // ######################################
        } else if($this->session_type = "AA_") {
          // SQL STATEMENT
          $sql = "SELECT DISTINCT *, o.UId as id, o.ObjNr as objNr, su.Summary as sum, su.Lang FROM Trans_Objects o\n"
            . "INNER JOIN Trans_Summary su ON (o.ObjNr = su.ObjNr)\n"
            . "WHERE su.Lang LIKE '$this->selectedLanguage'\n"
            . "AND o.UId = '$value'\n"
            . "GROUP BY o.UId\n"
            . "ORDER BY o.Sort ASC";
          // select everything from the fobj table with the given id parameter
          $result = mysqli_query($this->con, $sql);
          // if there is no entry yet insert value array into database
          while($row = mysqli_fetch_object($result)) {
            // date
            $date = $row->Date;
            // date array
            // 0 - deutsch
            // 1 - english
            $dateArr = explode('#', $date);
            //$dateArr = array();
            // set date
            if(count($dateArr) > 1) {
              // set date for language
              $date = ($this->selectedLanguage == 'Englisch') ? $dateArr[1] : $dateArr[0];
            }

            // UID
            $uid = $row->id;

            // object number
            $objNr = $row->objNr;
            // thumb
            $thumbs = $row->Scans_Renamed;

            // explode
            $thumbArr = explode('#', $thumbs);
            // set first scan
            $scan = trim($thumbArr[0]);
            // sort number
            $sort = $row->Sort;

            // summary
            $summary = $row->sum;

            $summary = (strlen($summary) > 230) ? substr($summary, 0, 230)."..." : $summary;

            // set AA Object Array
            $array = array(
              "uid" => $uid,
              "objNr" => $objNr,
              'date' => $date,
              "scan" => $scan,
              "summary" => $summary,
              "sort" => $sort
            );

            // push into set
            array_push($set, $array);
          }
        }
      }
    }
    // save in session
    $_SESSION['myObj'] = $set;
    // return full set
    return $set;
  }


  /**
   * The method returns the final search result
   *
   * @return array of the filter search result
   */
  public function getResult()
  {
    // return the search result
    return $this->searchResult;
  }


  /**
   * The method deletes the search result by its session id
   *
   */
  protected function deleteSearchResultById()
  {
    $sql = "DELETE FROM SearchResult\n"
      . "WHERE SessionId = '$this->session'";
    mysqli_query($this->con, $sql);
  }


  /**
   * The method deletes the search result by timestamp
   *
   */
  protected function deleteSearchResultByTime()
  {
    $sql = "DELETE FROM SearchResult\n"
      . "WHERE Timestamp < (NOW() - INTERVAL 1 DAY)";
    mysqli_query($this->con, $sql);
  }


  /**
   * MySql request for attribution
   *
   * @param string search value
   * @return array of the search result
   */
  protected function searchAttr($value)
  {
    // init search result array:
    $result = array();

    /**
     * ###########################################################
     * ATTRIBUTION SECTION
     * ###########################################################
     **/

    switch ($value) {
      /**
       * CASE ATTR_1 *
       **/
    case "attr_1":
      // Display Order low WITHOUT Sortnumber
      $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name = 'Lucas Cranach the Elder'
        AND Suffix = ''
        AND Prefix = ''
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }

      break;
      /**
       * CASE ATTR_2 *
       **/
    case "attr_2":
      // Display Order low WITHOUT Sortnumber
      $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name = 'Lucas Cranach the Elder'
        AND	Suffix LIKE '%and Workshop%'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }

      break;
      /**
       * CASE ATTR_3 *
       **/
    case "attr_3":
      // Display Order low WITHOUT Sortnumber
      $ergebnis = mysqli_query( $this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name = 'Workshop Lucas Cranach the Elder'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      break;
    case "attr_4":
      $ergebnis = mysqli_query( $this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name = 'Anonymous Master from the Cranach Workshop'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }

      break;
    case "attr_5":
      $ergebnis = mysqli_query( $this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Suffix LIKE '%follow%'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }

      break;
    case "attr_6":
      $ergebnis = mysqli_query( $this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Suffix LIKE '%Circle%Elder%'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }

      break;
    case "attr_7":
      $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Suffix LIKE '%Copy%Elder%'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }

      break;
    case "attr_8":
      $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name = 'Lucas Cranach the Younger' AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      break;
    case "attr_9":
      $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name = 'Lucas Cranach the Younger'
        AND	Suffix LIKE '%and Workshop%'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      break;
    case "attr_10":
      $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
        INNER JOIN Object ON Object.UId = Attribution.Object_UId
        WHERE Name LIKE '%work%younger%'
        AND Language LIKE 'Englisch'");
      while($row = mysqli_fetch_object($ergebnis)) {
        // eval relevance points
        $rel = ($row->DisplayOrder > 2) ? 10 : 20;
        // init items multi array with relevance
        $item = array(
          "id" => $row->Object_UId,
          "sort" => $row->SortNumber,
          "relevance" => $rel
        );
        array_push($result, $item);
      }
      break;
      case "attr_11":
        $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
          INNER JOIN Object ON Object.UId = Attribution.Object_UId
          WHERE Name = 'Hans Cranach'
          AND Language LIKE 'Englisch'");
        while($row = mysqli_fetch_object($ergebnis)) {
          // eval relevance points
          $rel = ($row->DisplayOrder > 2) ? 10 : 20;
          // init items multi array with relevance
          $item = array(
            "id" => $row->Object_UId,
            "sort" => $row->SortNumber,
            "relevance" => $rel
          );
          array_push($result, $item);
        }
        break;

    default:
      $master = $value;
      // select the nodes
      $nodes = $this->xpath->query('//languageKey[@index="'.$this->selectedLanguage.'"]/label[@name="'.$master.'"]');
      // write the node to the return var
      foreach ($nodes as $node) {

        if($node->hasAttribute('origin')) {
          // set the key of the array to the name attribute
          $key = $node->attributes->getNamedItem('origin')->nodeValue;

          // if node has attribute prefix
          if($node->hasAttribute('prefix')) {
            $prefix = $node->attributes->getNamedItem('prefix')->nodeValue;

            // SQL QUERY
            $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
              INNER JOIN Object ON Object.UId = Attribution.Object_UId
              WHERE Name = '$key'
              AND Prefix = '$prefix'
              AND Language Like 'Englisch'");
            while($row = mysqli_fetch_object($ergebnis)) {
              // eval relevance points
              $rel = ($row->DisplayOrder > 2) ? 10 : 20;
              // init items multi array with relevance
              $item = array(
                "id" => $row->Object_UId,
                "sort" => $row->SortNumber,
                "relevance" => $rel
              );
              array_push($result, $item);
            }
          } else {
            // SQL QUERY
            $ergebnis = mysqli_query($this->con, "SELECT * FROM Attribution
              INNER JOIN Object ON Object.UId = Attribution.Object_UId
              WHERE Name = '$key'
              AND Language Like 'Englisch'");
            while($row = mysqli_fetch_object($ergebnis)) {
              // eval relevance points
              $rel = ($row->DisplayOrder > 2) ? 10 : 20;
              // init items multi array with relevance
              $item = array(
                "id" => $row->Object_UId,
                "sort" => $row->SortNumber,
                "relevance" => $rel
              );
              array_push($result, $item);
            }
          }
        }
      }
      break;
    }

    // return the search result
    return $result;
  }


  /**
   * MySql request for date
   *
   * @param string search value
   * @return array of the search result
   */
  protected function searchDate($value)
  {
    // init result
    $timeline = '';

    /**
     * ###########################################################
     * DATE SEARCH SECTION
     * ###########################################################
     **/
    switch($value) {
    case "date_1":
      $timeline = "(\n"
        . "d.BeginningDate > 1500\n"
        . "AND d.BeginningDate < 1510\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1510\n"
        . "AND d.EndDate >= 1500\n"
        .")";
      break;
    case "date_2":
      $timeline = "(\n"
        . "d.BeginningDate > 1510\n"
        . "AND d.BeginningDate < 1520\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1520\n"
        . "AND d.EndDate >= 1510\n"
        .")";
      break;
    case "date_3":
      $timeline = "(\n"
        . "d.BeginningDate > 1520\n"
        . "AND d.BeginningDate < 1530\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1530\n"
        . "AND d.EndDate >= 1520\n"
        .")";
      break;
    case "date_4":
      $timeline = "(\n"
        . "d.BeginningDate > 1530\n"
        . "AND d.BeginningDate < 1540\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1540\n"
        . "AND d.EndDate >= 1530\n"
        .")";
      break;
    case "date_5":
      $timeline = "(\n"
        . "d.BeginningDate > 1540\n"
        . "AND d.BeginningDate < 1550\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1550\n"
        . "AND d.EndDate >= 1540\n"
        .")";
      break;
    case "date_6":
      $timeline = "(\n"
        . "d.BeginningDate > 1550\n"
        . "AND d.BeginningDate < 1560\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1560\n"
        . "AND d.EndDate >= 1550\n"
        . ")";
      break;
    case "date_7":
      $timeline = "(\n"
        . "d.BeginningDate > 1560\n"
        . "AND d.BeginningDate < 1570\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1570\n"
        . "AND d.EndDate >= 1560\n"
        . ")";
      break;
    case "date_8":
      $timeline = "(\n"
        . "d.BeginningDate > 1570\n"
        . "AND d.BeginningDate < 1580\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "d.EndDate <= 1580\n"
        . "AND d.EndDate >= 1570\n"
        . ")";
      break;
    case "date_9":
      $timeline = "d.BeginningDate > 1581";
      break;
    case "dated":
      $timeline = "d.Remarks = '[dated]'";
      break;
    }

    // return result
    return $timeline;
  }



  /**
   * MySql request for technique
   *
   * @param string search value
   * @return array of the search result
   */
  protected function searchTech($value)
  {
    // init query
    $query = '';

    // switch value
    switch ($value) {
    case "tech_1":
      // create query
      $query = "m.Remarks LIKE '%Infrared photography%'\n";
      break;
    case "tech_2":
      // create query
      $query = "m.Remarks LIKE '%Infrared reflectography%'\n";
      break;
    case "tech_3":
      // create query
      $query = "m.Remarks LIKE '%X-radiography%'\n";
      break;
    case "tech_4":
      // create query
      $query = "m.Remarks LIKE '%UV-light photography%'\n";
      break;
    case "tech_5":
      // create query
      $query = "m.Remarks LIKE '%Other imaging techniques%'\n";
      break;
    case "tech_6":
      // create query
      $query = "m.Remarks LIKE '%Light microscopy%'\n";
      break;
    case "tech_7":
      // create query
      $query = "m.Remarks LIKE '%Instrumental material analysis%'\n";
      break;
    case "tech_8":
      // create query
      $query = "m.Remarks LIKE '%Micro-sampling / cross-sections%'\n";
      break;
    case "tech_9":
      // create query
      $query = "m.Remarks LIKE '%Dendrochronology%'\n";
      break;
    case "tech_10":
      // create query
      $query = "m.Remarks LIKE '%Stereomicroscopy%'\n";
      break;
    }
    return $query;
  }


  /**
   * MySql request for collection
   *
   * @param string search value
   * @return array of the search result
   */
  protected function searchCollection($value)
  {
    // init query
    $query = '';

    // select the nodes
    $nodes = $this->xpath->query('//languageKey[@index="'.$this->selectedLanguage.'"]/label[@name="'.$value.'"]');
    // write the node to the return var
    foreach ($nodes as $node) {

      if($node->hasAttribute('origin')) {
        // set the key of the array to the name attribute
        $key = $node->attributes->getNamedItem('origin')->nodeValue;

        $query = "multi.Value LIKE '%$key%'";
      }
    }
    return $query;
  }

  /**
   * MySql request for category, e.g. 100 Masterpieces
   *
   * @param string search value
   * @return array of the search result
   */
  protected function searchCategory($value){
    
    $result = array();

    $ergebnis = mysqli_query($this->con, "SELECT * FROM AdditionalProperties
      INNER JOIN Object ON Object.UId = AdditionalProperties.Object_UId
      WHERE IsBestOf = 1");
    while($row = mysqli_fetch_object($ergebnis)) {
      // eval relevance points
      $rel = 100;
      // init items multi array with relevance
      $item = array(
        "id" => $row->Object_UId,
        "sort" => $row->SortNumber,
        "relevance" => $rel
      );
      array_push($result, $item);
    }

    return $result;
  }


  /**
   * MySql request for FULLTEXT
   *
   * @param string search value
   * @return array result of the mysql request
   */
  protected function searchFullText($value)
  {
    // Add Wildcard to the end
    // NOTE: MATCH AGAINST only allows wildcards at the end of the string
    // NOTE: MATCH AGAINST has a minimum character variable 'ft_min_word_len' (whose default value is 4)
    $search_input		= $value.'*';

    // init result
    $result = array();

    // init query
    $query = '';

    // SELECT FROM QUERY
    $query = "(SELECT UId AS id FROM Object\n"
      . "WHERE MATCH (ObjNr, Classification, ObjIdentifier)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM Attribution\n"
      . "WHERE MATCH (Function, Name, Prefix, Suffix, NameType, OtherName, Remarks, DisplayDate)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM AlternativeNames\n"
      . "WHERE MATCH (Name)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM Dating\n"
      . "WHERE MATCH (Dating, Remarks)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM Description\n"
      . "WHERE MATCH (Value, Date, Author)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM Linkage\n"
      . "WHERE MATCH (Type, Name)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM Location\n"
      . "WHERE MATCH (Location)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM MultipleTable\n"
      . "WHERE MATCH (Type, Value)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM ObjectReports\n"
      . "WHERE MATCH (SurveyType, Project, ConditionReport, TreatmentReport,\n"
      . "ShortText, TreatmentDate, Entered, Modified, Author)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM ObjectTitle\n"
      . "WHERE MATCH (TitleType, Title, Remarks)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT Object_UId AS id FROM Metadata\n"
      . "WHERE MATCH (Name, Creator, Title, Date, FileType, ImageDesc, ImageDate, ImageCreated, ImageSrc)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))";

    // mysql query
    $ergebnis = mysqli_query($query);
    while($row = mysqli_fetch_object($ergebnis)) {
      array_push($result, $row->id);
    }


    // SPECIAL TREATMENT FOR REST MODUL
    // ++++++++++++++++++++++++++++++++
    // init restmodul array
    $restModulArr = array();
    // query
    $query = "(SELECT RestModul_UId AS id FROM MaterialAndTechnique\n"
      . "WHERE MATCH (Type, Purpose, Remarks, TextField)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT RestModul_UId AS id FROM Operators\n"
      . "WHERE MATCH (Role, Operator)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))";

    // mysql query
    $ergebnis = mysqli_query($query);
    while($row = mysqli_fetch_object($ergebnis)) {
      array_push($restModulArr, $row->id);
    }

    // run through modul arr and push into results
    foreach($restModulArr as $value) {
      $ergebnis = mysqli_query("SELECT Object_UId AS id FROM ObjectReports WHERE UId = '$value'");
      while($row = mysqli_fetch_object($ergebnis)) {
        if (!in_array($row->id, $result)) {
          array_push($result, $row->id);
        }
      }
    }
    // return result
    return $result;
  }


  /**
   * MySql request for AA (ARCHIVAL DOCUMENTS) date
   *
   * @param string search value
   * @return array of the search result
   */
  protected function aa_searchDate($value)
  {
    // init result
    $timeline = '';

    /**
     * ###########################################################
     * AA - DATE SEARCH SECTION
     * ###########################################################
     **/
    switch($value) {
      // 1490 - 1500
    case "date_1":
      $timeline = "(\n"
        . "t.Date_Begin > 1490\n"
        . "AND t.Date_Begin < 1500\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1500\n"
        . "AND t.Date_End >= 1491\n"
        .")";
      break;
      // 1500 - 1510
    case "date_2":
      $timeline = "(\n"
        . "t.Date_Begin > 1500\n"
        . "AND t.Date_Begin < 1510\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1510\n"
        . "AND t.Date_End >= 1501\n"
        .")";
      break;
      // 1510 - 1520
    case "date_3":
      $timeline = "(\n"
        . "t.Date_Begin > 1510\n"
        . "AND t.Date_Begin < 1520\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1520\n"
        . "AND t.Date_End >= 1511\n"
        .")";
      break;
      // 1520 - 1530
    case "date_4":
      $timeline = "(\n"
        . "t.Date_Begin > 1520\n"
        . "AND t.Date_Begin < 1530\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1530\n"
        . "AND t.Date_End >= 1521\n"
        .")";
      break;
      // 1530 - 1540
    case "date_5":
      $timeline = "(\n"
        . "t.Date_Begin > 1530\n"
        . "AND t.Date_Begin < 1540\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1540\n"
        . "AND t.Date_End >= 1531\n"
        .")";
      break;
      // 1540 - 1550
    case "date_6":
      $timeline = "(\n"
        . "t.Date_Begin > 1540\n"
        . "AND t.Date_Begin < 1550\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1550\n"
        . "AND t.Date_End >= 1541\n"
        .")";
      break;
      // 1550 - 1560
    case "date_7":
      $timeline = "(\n"
        . "t.Date_Begin > 1550\n"
        . "AND t.Date_Begin < 1560\n"
        . ")\n"
        . "OR\n"
        . "(\n"
        . "t.Date_End <= 1560\n"
        . "AND t.Date_End >= 1551\n"
        .")";
      break;
    }

    // return result
    return $timeline;
  }


  /**
   * MySql request for AA (ARCHIVAL DOCUMENTS) INSTITUTIONS
   *
   * @param string search value
   * @return array of the search result
   */
  protected function aa_searchInstitution($value)
  {
    switch($value) {
      // Thüringisches Hauptstaatsarchiv Weimar
    case "institution_1":
      $result = "t.Repository LIKE '%Thüringisches%'";
      break;
      // Stadtarchiv Kronach / Franken
    case "institution_2":
      $result = "t.Repository LIKE '%Kronach%'";
      break;
    }
    return $result;
  }


  /**
   * MySql request for AA FULLTEXT (ARCHIVAL DOCUMENTS)
   *
   * @param string search value
   * @return array result of the mysql request
   */
  protected function aa_searchFullText($value)
  {
    // Add Wildcard to the end
    // NOTE: MATCH AGAINST only allows wildcards at the end of the string
    // NOTE: MATCH AGAINST has a minimum character variable 'ft_min_word_len' (whose default value is 4)
    $search_input		= $value.'*';

    // init tmp array
    $tmp = array();
    // init result
    $result = array();

    // init query
    $query = '';

    // SELECT FROM QUERY
    $sql = "(SELECT ObjNr FROM Trans_Objects\n"
      . "WHERE MATCH (ObjNr, Date, Transcription, Location, Repository, Signature, Comments, Trans_By, Trans_Date, Trans_to, Verification)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))\n"
      . "UNION\n"
      . "(SELECT ObjNr FROM Trans_Summary\n"
      . "WHERE MATCH (Summary)\n"
      . "AGAINST ('$search_input' IN BOOLEAN MODE))";
    // mysql query
    $ergebnis = mysqli_query($this->con, $sql);
    while($row = mysqli_fetch_object($ergebnis)) {
      array_push($tmp, $row->ObjNr);
    }

    // run through all results and select the UId
    foreach($tmp as $item) {
      $sql = "SELECT DISTINCT UId FROM Trans_Objects WHERE ObjNr LIKE '$item' ORDER BY Sort ASC";

      // mysql query
      $ergebnis = mysqli_query($this->con, $sql);
      while($row = mysqli_fetch_object($ergebnis)) {
        if(!in_array($row->UId, $result)) {
          array_push($result, $row->UId);
        }
      }
    }

    // return result
    return $result;
  }
}
?>
