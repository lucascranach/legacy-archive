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

// start session
if(session_id() == '') {
    session_start();
}
// Import of the required subclasses
require_once("src/classes/Translator.class.php");
// db connection class
require_once('src/classes/DbConnection.class.php');
// Import of the required subclasses
require_once("src/classes/AdvancedSearch.class.php");
/**
 * Class 'Transcription' is a 'Cranach Digital Archive' extension.
 *
 * @author	Joerg Stahlmann <>
 * @package	elements/classes
 */
class Transcription {

	// selected language:
	private $_selectedLanguage;

	// translation class object:
	private $_t;

	// Database connection:
	private $_con;

	// index of object
	private $_index;

	// scan
	private $_scan;

	// object number
	private $_objNr;

	// object array
	protected $objArray;

	/**
	 * Constructor function of the class
	 */
	public function __construct() {
		// get the language from the session
		$this->_selectedLanguage = (isset($_SESSION['lang'])) ? $_SESSION['lang'] : 'Englisch';
    // if cookie language != session language
    if(isset($_COOKIE['lang']) && $this->_selectedLanguage != $_COOKIE['lang']) {
      // get the language from the cookie
      $this->_selectedLanguage = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'Englisch';
    }

		// create translation object
		$this->_t = new Translator('src/xml/locallang/locallang_trans.xml');

		// create database object
		$dbcon = new DbConnection();
		// get the db return value
		$this->_con = $dbcon->getConnection();

		// get object index
		$this->_index = (isset($_GET['index'])) ? $_GET['index'] : 1;

		// get scan
		$this->_scan = (isset($_GET['scan'])) ? $_GET['scan'] : "error";

    // create advanced Search object
    $advancedSearch = new AdvancedSearch($this->_con, 'AA_');
    // get searchResult
    $this->objArray = $advancedSearch->getResult();
    // save to session
    $_SESSION['aa_objects'] = $this->objArray;
	}

	/**
	* Get the Filter
	*
	* @return string html content of the filter list
	*/
	public function getFilter() {

		// get cookie
		$trans_dating = isset($_COOKIE['trans_dating']) ? $_COOKIE['trans_dating'] : "block";
		// list style
		$li_nav_dating = ($trans_dating == "block") ? "current" : "closed";

		// get cookie
		$trans_institution = isset($_COOKIE['trans_institution']) ? $_COOKIE['trans_institution'] : "block";
		// list style
		$li_nav_institution = ($trans_institution == "block") ? "current" : "closed";

		$content = '<ul>';

		// FILTER - DATIERUNG
		$content .= '<li  class="'.$li_nav_dating.'"><a href="javascript:leClick(\'nav_dating\')" name="nav_dating">'.$this->_t->trans('date_label_h').'</a>'
						.'<div id="nav_dating" style="display:'.$trans_dating.'">'
							.'<ul>'
								.'<!-- List Item -->'
								.'<li><input type="hidden" name="aa_date[]" index="dating" value="0">'
									.'<input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_1">1490 - 1500</li>'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_2">1501 - 1510</li>'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_3">1511 - 1520</li>'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_4">1521 - 1530</li>		'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_5">1531 - 1540</li>'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_6">1541 - 1550</li>'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_date[]" index="dating" value="date_7">1551 - 1560</li>'
							.'</ul>'
						.'</div>'
					.'</li>';

	$content .= '<li class="'.$li_nav_institution.'"><a href="javascript:leClick(\'nav_institution\')" name="nav_institution">'.$this->_t->trans('institution_label_h').'</a>'
						.'<div id="nav_institution" style="display:'.$trans_institution.'">'
							.'<ul>'
								.'<input type="hidden" name="aa_institution[]" index="nav_hiddenCol" value="0">'
								.'<!-- List Item -->'
								.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_institution[]" index="institution" value="institution_1">Th&uuml;ringisches Hauptstaatsarchiv Weimar</li>'
									.'<!-- List Item -->'
									.'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="aa_institution[]" index="institution" value="institution_2">Stadtarchiv Kronach / Franken</li>'
							.'</ul>'
						.'</div>'
					.'</li>';

		return $content;
	}




  /**
   * get navigation
   * Displays all single data navigation options
   *
   * @return String navigation html content
   */
  public function get_navigation($view="list")
  {
    if($view == "trans") {

      $content = '<nav class="navbar navbar-inverse navbar-static-top">'
      . '<div class="container-fluid">'
      . '<div class="navbar-header">';
      $content .='<a href="javascript:setLanguage()" class="navbar-brand">'.$this->_t->trans('lang').'</a>';
      $content .='<a href="/archival-documents" class="navbar-brand"><span class="glyphicon glyphicon-list" aria-hidden="true"></span></a>';
      $content .='<a href="'.$this->getPrev().'" class="navbar-brand"><span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span></a>';
      $content .='<a href="'.$this->getNext().'" class="navbar-brand"><span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></a>';
      $content .='<a href="" id="iipImageZoom" class="navbar-brand"><span class="glyphicon glyphicon-zoom-in" aria-hidden="true"></span></a>';
      $content .='<a href="" id="removeZoom" class="navbar-brand"><span class="glyphicon glyphicon-log-in" aria-hidden="true"></span></a>';
      $content .= '</div>'; // <!-- / container fluid -->
      $content .= '</nav>';

    } else {

      $content = '<nav class="navbar navbar-inverse navbar-static-top">'
      . '<div class="container-fluid">'
      . '<div class="navbar-header">';
      $content .='<a href="javascript:setLanguage()" class="navbar-brand">'.$this->_t->trans('lang').'</a>';
      $content .='<a href="" class="navbar-brand"><span class="glyphicon glyphicon-home" aria-hidden="true"></span></a>';
      $content .= '</div>'; // <!-- / container fluid -->
      $content .= '</nav>';

    }

    // return overview html content
    return $content;

  }




	/**
   * Method GET LIST VIEW
   *
	* @return string list html content
	*/
  public function getListView() {
    // init arr
    $arr = array();
    // get object array
    $arr = $_SESSION['aa_objects'];

    // static header content
    $content = '<div id="box" class="header">'
		. '<table>'
		. '<tr>'
		. '<td width="60px">&nbsp;</td>'
		. '<td width="200px"><b>'.$this->_t->trans('date_h').'</b></td>'
		. '<td><b>'.$this->_t->trans('summary_h').'</b></td>'
		. '</tr>'
		. '</table>'
		. '</div>';

    // dynamic single box data
    foreach($arr as $item) {
      // fill html content
      $content .= '<div id="box" index="'.$item['uid'].'" scan="'.$item['scan'].'" class="listView">'
        .'<table>'
          .'<tr>'
            .'<td align="center" valign="middle" width="50px"><img src="thumbnails/AA_ARCHIVALIEN/'.$item['scan'].'.jpg" onError="this.src=\'images/no-image.png\'" \ height="50px">'
            .'</td>'
            .'<td width="200px">'.$item['date'].'</td>'
            .'<td>'.$item['summary'].'</td>'
          .'</tr>'
        .'</table>'
      .'</div>';
    }
    // return
    return $content;
  }


	/**
	* Method GET ALL DATA stores all data from database
	* and returns the data array
	*
	* @return string array data
	*/
	public function getAllData() {
		// init data array
		$data = array();

		// select everything from the fobj table with the given id parameter
		$ergebnis = mysqli_query("SELECT * FROM Trans_Objects WHERE UId = '$this->_index'");
		// fetch object
		$row = mysqli_fetch_object($ergebnis);

		// get object number for the summary
		$this->_objNr = $row->ObjNr;

		// date
		$date = $row->Date;
		// date array
		// 0 - deutsch
		// 1 - english
		$dateArr = explode('#', $date);
		// set date
		if(!empty($dateArr[1])) {
			// set date for language
			$date = ($this->_selectedLanguage == 'Englisch') ? $dateArr[1] : $dateArr[0];
		}

		// location
		$location = $row->Location;

		// location array
		// 0 - deutsch
		// 1 - english
		$locationArr = explode('#', $location);
    fb($locationArr);
    // set location
		if(!empty($locationArr[1])) {
			// set date for language
			$location = ($this->_selectedLanguage == 'Englisch') ? $locationArr[1] : $locationArr[0];
		}

		// comments
		$comments = $row->Comments;
		// comments array
		// 0 - deutsch
    // 1 - english
    $commentsArr = array();
		$commentsArr = explode('#', $comments);
		// set date
		if(!empty($commentsArr[1])) {
			// set date for language
			$comments= ($this->_selectedLanguage == 'Englisch') ? $commentsArr[1] : $commentsArr[0];
		}

		// fill array
		$data = array("date" => $this->parse(addslashes($date)),
									"objNr" => $this->parse(addslashes($row->ObjNr)),
									"repository" => $this->parse(addslashes($row->Repository)),
									"transcription" => $this->parse(addslashes($row->Transcription)),
									"location" => $this->parse(addslashes($location)),
									"signature" => $this->parse(addslashes($row->Signature)),
									"comments" => $this->parse(addslashes($comments)),
									"trans_by" => $this->parse(addslashes($row->Trans_By)),
									"trans_date" => $this->parse(addslashes($row->Trans_Date)),
									"trans_to" => $this->parse(addslashes($row->Trans_to)),
									"summary" => '',
									"scans" => $this->parse(addslashes($row->Scans_Renamed)));
		// get summary
		//$ergebnis = mysqli_query("SELECT * FROM Trans_Summary WHERE ObjNr = '$objNr' AND Lang = '$this->_selectedLanguage'");
		$ergebnis = mysqli_query("SELECT * FROM Trans_Summary WHERE ObjNr = '$this->_objNr' AND Lang LIKE '$this->_selectedLanguage'");
		// fetch object
    while($row = mysqli_fetch_object($ergebnis)) {

      // add summary
			$data["summary"] .= $this->parse(addslashes($row->Summary));
    }

		// return
		return $data;
	}



	/**
	* Method GET Scans stores all scans from database
	* and returns the data array
	*
	* @return string array scans
	*/
	public function getScans() {
		// init data array
		$scans = array();

		// select everything from the fobj table with the given id parameter
		$ergebnis = mysqli_query("SELECT Scans_Renamed FROM Trans_Objects WHERE UId = '$this->_index'");
		// fetch object
		$row = mysqli_fetch_object($ergebnis);

		// fill array
		$arr = explode('#', $row->Scans_Renamed);

		// fill scans array

		foreach($arr as $value) {
			array_push($scans, trim($value));
		}

		// return
		return $scans;
	}




	/**
	* Method GET Thumbnail Container stores all scans from database
	* and displays them in a thumbnail box
	*
	* @return string html thumbnailbox
	*/
	public function getThumbnailContainer() {
		// init data array
		$thumbnailbox = "";

		// scans array
		$scans = array();

		// select everything from the fobj table with the given id parameter
		$ergebnis = mysqli_query("SELECT Scans_Renamed FROM Trans_Objects WHERE UId = '$this->_index'");
		// fetch object
		$row = mysqli_fetch_object($ergebnis);

		// fill array
		$arr = explode('#', $row->Scans_Renamed);

		// fill scans array
		foreach($arr as $value) {
			array_push($scans, trim($value));
		}

		// evaluate index
		$wrapper_index =  count($scans) / 16;
		// round wrapper index
		$wrapper_index = ceil($wrapper_index);



		/** ###		ALL THUMBNAILS CONTAINER		### **/
		for($j = 0; $j <  $wrapper_index; $j++) {

			// set visiblity marks
			$display = "none";
			$active = "false";


			// set index
			$index = 16 * $j;
			// create div
			$thumbnailbox .= '<div class="msg_thumb_wrapper" name="all" index="'.$j.'" active="'.$active.'" style="display:'.$display.';">';
			// run through scans array
			for($i = 0; $i < 16; $i++) {
				if(($i + $index) < count($scans)) {
					$thumbnailbox .= '<a href="'.$_SERVER['PHP_SELF'].'?&index='.$this->_index.'&scan='.$scans[$index + $i].'" name="'.$scans[$index + $i].'">'
							.'<img src="thumbnails/AA_ARCHIVALIEN/'.$scans[$index + $i].'.jpg" onError="this.src=\'images/no-image.png\'" width="50px" name="'.$scans[$index + $i].'">'
						.'</a>';
				}
			}
			// close div msg_thumb_wrapper
			$thumbnailbox .= '</div>';
		}

		$thumbnailbox .= '<a id="msg_thumb_next" class="msg_thumb_next" href=""></a>'
			.'<a id="msg_thumb_prev" class="msg_thumb_prev" href=""></a>';

		// return
		return $thumbnailbox;
	}



	/**
	* LITERATURE DATABASE
	* All the data comes from the Literature Modul!
	*
	* @return Literature Text String content
	**/
	public function getLiterature() {
		// init
		$litArr = array();
		// init
		$litObj = array();

		// helper var
		$i = 0;

		// object value
		$value = 'A_'.$this->_objNr;

		$ergebnis = mysqli_query("SELECT o.UId as ID, o.ReferenceNr as REFNR, l.Appendage as PAGES, l.Catalogue as CATALOGUE
															FROM Lit_LinkedObject l
															LEFT JOIN Lit_Object o ON l.Object_UId = o.UId
															WHERE l.ObjNr LIKE '$value'");

		while($row = mysqli_fetch_object($ergebnis)) {

			// fill array
			$litArr = array("uid" => $this->parse(addslashes($row->ID)),
										"refNr" => $this->parse(addslashes($row->REFNR)),
										"appendage" => $this->parse(addslashes($row->PAGES)),
										"catalogue" => $this->parse(addslashes($row->CATALOGUE)));

			$litObj[$i] = $litArr;

			$i++;
		}

		return $litObj;
	}



	/**
	* LITERATURE DATABASE FULL OBJECT
	* All the data comes from the Literature Modul!
	*
	* @return Literature Text String content
	**/
	public function getFullLiteratureObject() {
		// init
		$fullLitObjectArr = array();
		// init
    $litObj = array();
    // init
    $litFullObj = array();

		// init uid
		$lit_UId = '';
		// init reference number
		$lit_RefNr = '';
		// init title
		$lit_Title = '';
		// init subtitle
		$lit_Subtitle = '';
		// init heading
		$lit_Heading = '';
		// init journal
		$lit_Journal = '';
		// init series
		$lit_Series = '';
		// init volume
		$lit_Volume = '';
		// init edition
		$lit_Edition = '';
		// init place published
		$lit_PlacePubl = '';
		// init year published
		$lit_YearPubl = '';
		// init the numbers of pages
		$lit_NumOfPages = '';
		// init date
		$lit_Date = '';
		// init copyright
		$lit_Copyright = '';
		// init authors
		$lit_Authors = '';
		// init publisher
		$lit_Publisher = '';

		// helper var
		$i = 0;

		// object value
		$value = 'A_'.$this->_objNr;

		// get all lit object uids
		$ergebnis = mysqli_query("SELECT Object_UId
								FROM Lit_LinkedObject
								WHERE ObjNr LIKE '$value'");

		while($row = mysqli_fetch_object($ergebnis)) {
			// fill array with all object ids
			array_push($litObj, $row->Object_UId);
		}

		// run through all IDs and fill array with lit information
		foreach($litObj as $val) {
			// sql query for object uid
			$ergebnis = mysqli_query("SELECT * FROM Lit_Object WHERE UID LIKE '$val'");

			// fetch object
			$row = mysqli_fetch_object($ergebnis);

			// set uid
			$lit_UId = $row->UId;
			// set reference number
			$lit_RefNr = $row->ReferenceNr;
			// set title
			$lit_Title = $row->Title;
			// set subtitle
			$lit_Subtitle = $row->Subtitle;
			// set heading
			$lit_Heading = $row->Heading;
			// set journal
			$lit_Journal = $row->Journal;
			// set series
			$lit_Series = $row->Series;
			// set volume
			$lit_Volume = $row->Volume;
			// set edition
			$lit_Edition = $row->Edition;
			// set place published
			$lit_PlacePubl = $row->PlacePubl;
			// set year published
			$lit_YearPubl = $row->YearPubl;
			// set the numbers of pages
			$lit_NumOfPages = $row->NumOfPages;
			// set date
			$lit_Date = $row->Date;
			// set copyright
			$lit_Copyright = $row->Copyright;


			// Lit_Persons
			$lit_Autor = array();
			$lit_Herausgeber = array();
			$lit_Authors = "";
			$lit_Publisher = "";

			$ergebnis = mysqli_query(
					"Select * from Lit_Persons
						WHERE Object_UId = '$lit_UId'
						AND Role = 'Autor'");

			while($row = mysqli_fetch_object($ergebnis)) {
				$lit_Autor[] = $row->Name;
			}

			$ergebnis = mysqli_query(
					"Select * from Lit_Persons
						WHERE Object_UId = '$lit_UId'
						AND Role = 'Herausgeber'");

			while($row = mysqli_fetch_object($ergebnis)) {
				$lit_Herausgeber[] = $row->Name;
			}


			if(count($lit_Autor) > 0) {
				foreach($lit_Autor as $autor) {
					$lit_Authors .= ($lit_Authors == '') ? $autor : ', '.$autor;
				}
			}

			if(count($lit_Herausgeber) > 0) {
				foreach($lit_Herausgeber as $herausgeber) {
					$lit_Publisher = ($lit_Publisher == '') ? $herausgeber : ', '.$herausgeber;
				}

			}


			// fill array
			$fullLitObjectArr = array("uid" => $this->parse(addslashes($lit_UId)),
									"refNr" => $this->parse(addslashes($lit_RefNr)),
									"title" => $this->parse(addslashes($lit_Title)),
									"subtitle" => $this->parse(addslashes($lit_Subtitle)),
									"heading" => $this->parse(addslashes($lit_Heading)),
									"journal" => $this->parse(addslashes($lit_Journal)),
									"series" => $this->parse(addslashes($lit_Series)),
									"volume" => $this->parse(addslashes($lit_Volume)),
									"edition" => $this->parse(addslashes($lit_Edition)),
									"placePubl" => $this->parse(addslashes($lit_PlacePubl)),
									"yearPubl" => $this->parse(addslashes($lit_YearPubl)),
									"numOfPages" => $this->parse(addslashes($lit_NumOfPages)),
									"date" => $this->parse(addslashes($lit_Date)),
									"copyright" => $this->parse(addslashes($lit_Copyright)),
									"authors" => $this->parse(addslashes($lit_Authors)),
									"publisher" => $this->parse(addslashes($lit_Publisher)));


			// store in array
			$litFullObj[$i] = $fullLitObjectArr;

			$i++;

		}

		return $litFullObj;
	}



	/**
	* Method GET ALL DATA HEADERS
	* and returns the header array
	*
	* @return string array header
	*/
	public function getAllHeaders() {
		// init data array
		$header = array();


		// fill array with headers
		$header = array("date" => $this->_t->trans('date'),
						"summary" => $this->_t->trans('summary'),
						"objNr" => $this->_t->trans('objNr'),
						"repository" => $this->_t->trans('repository'),
						"transcription" => $this->_t->trans('transcription'),
						"location" => $this->_t->trans('location'),
						"signature" => $this->_t->trans('signature'),
						"comments" => $this->_t->trans('comments'),
						"trans_by" => $this->_t->trans('trans_by'),
						"trans_date" => $this->_t->trans('trans_date'),
						"trans_to" => $this->_t->trans('trans_to'),
						"literature" => $this->_t->trans('literature'),
						"lit_authors" => $this->_t->trans('lit_authors'),
						"lit_publisher" => $this->_t->trans('lit_publisher'),
						"lit_title" => $this->_t->trans('lit_title'),
						"lit_subtitle" => $this->_t->trans('lit_subtitle'),
						"lit_journal" => $this->_t->trans('lit_journal'),
						"lit_series" => $this->_t->trans('lit_series'),
						"lit_volume" => $this->_t->trans('lit_volume'),
						"lit_edition" => $this->_t->trans('lit_edition'),
						"lit_placePubl" => $this->_t->trans('lit_placePubl'),
						"lit_yearPubl" => $this->_t->trans('lit_yearPubl'),
						"lit_numOfPages" => $this->_t->trans('lit_numOfPages'),
						"lit_copyright" => $this->_t->trans('lit_copyright'),
						"label_show" => $this->_t->trans('label_show'),
						"label_hide" => $this->_t->trans('label_hide'));
		// return
		return $header;
	}


	/**
	* Method PARSE runs through the text
	* and replaces the linebreaks
	*
	* @return string parsed text
	*/
	private function parse($text) {
    // Damn pesky carriage returns...
    $text = str_replace("\r\n", "<br />", $text);
    $text = str_replace("\r", "<br />", $text);
    $text = str_replace("\t", "", $text);
    // JSON requires new line characters be escaped
    $text = str_replace("\n", "<br />", $text);

    // JSON replace single quote to double quotes
    $text = str_replace("'", '"', $text);
    return $text;
	}



	/**
	* Method GET SCAN selects the scan file
	* and returns the overview html content
	*
	* @return string html content
	*/
	public function getScan() {
		// init content
		$content = "";

		// select everything from the fobj table with the given id parameter
		$ergebnis = mysqli_query("SELECT * FROM Trans_Objects WHERE UId = '$this->_index'");
		// fetch object
		$row = mysqli_fetch_object($ergebnis);
		// set the thumb
		$thumbs = $row->Scans_Renamed;
		// explode
		$thumbArr = explode('#', $thumbs);
		// set first scan
		$scan = $this->_scan;
		// date
		$date = $row->Date;
		// date array
		// 0 - deutsch
		// 1 - english

		$dateArr = explode('#', $date);
		//$dateArr = array();
		// set date
		if(!empty($dateArr[1])) {
			// set date for language
			$date = ($this->_selectedLanguage == 'Englisch') ? $dateArr[1] : $dateArr[0];
		}
		// object number
		$objNr = $row->ObjNr;



		// set content
		$content = '<a href="" id="zoomIn">'
          .'<img src="thumbnails/AA_ARCHIVALIEN/'.$scan.'.jpg" onError="this.src=\'images/no-image.png\'" \ width="200px">'
					.'<img src="images/zoom.png" class="zoom">'
				.'</a>';
				//	<a href="'.$prev_str.'" id="thumb_prev" class="thumb_prev"><img src="images/icons/prev_thumb.png" /></a>
        //  <a href="'.$next_str.'" id="thumb_next" class="thumb_next"><img src="images/icons/next_thumb.png" /></a>
		$content .= '<ul>'
					.'<li>'.$date.'</li>'
					.'<li></li>'
					.'<li>'.$objNr.'</li>'
				.'</ul>';
		// return html content
		return $content;
	}



	/**
	* Method GET NAVI creates the navigation
	* and returns the html content
	*
	* @return string html content
	*/
	public function getNavi() {
		// init content
		$content = "";

		/**
		 *	SUB NAVIGATION OF THE DATABASE DATA
		 **/
		$content = '<h2 class="current"><a href="" index="show_all" class="tooltips" title="'.$this->_t->trans('show_all_tooltip').'">'.$this->_t->trans('show_all').'</a></h2>'
				.'<ul>'
					.'<li><a href="" index="date" class="tooltips"  title="'.$this->_t->trans('date_tooltip').'">'.$this->_t->trans('date').'</a></li>'
					.'<li><a href="" index="summary"  class="tooltips"  title="'.$this->_t->trans('summary_tooltip').'">'.$this->_t->trans('summary').'</a></li>'
					.'<li><a href="" index="transcription"  class="tooltips"  title="'.$this->_t->trans('transcription_tooltip').'">'.$this->_t->trans('transcription').'</a></li>'
					.'<li><a href="" index="location"  class="tooltips"  title="'.$this->_t->trans('location_tooltip').'">'.$this->_t->trans('location').'</a></li>'
					.'<li><a href="" index="repository"  class="tooltips"  title="'.$this->_t->trans('repository_tooltip').'">'.$this->_t->trans('repository').'</a></li>'
					.'<li><a href="" index="signature"  class="tooltips"  title="'.$this->_t->trans('signature_tooltip').'">'.$this->_t->trans('signature').'</a></li>'
					.'<li><a href="" index="comments"  class="tooltips"  title="'.$this->_t->trans('comments_tooltip').'">'.$this->_t->trans('comments').'</a></li>'
					.'<li><a href="" index="trans_by"  class="tooltips"  title="'.$this->_t->trans('trans_by_tooltip').'">'.$this->_t->trans('trans_by').'</a></li>'
					.'<li><a href="" index="trans_date"  class="tooltips"  title="'.$this->_t->trans('trans_date_tooltip').'">'.$this->_t->trans('trans_date').'</a></li>'
					.'<li><a href="" index="trans_to"  class="tooltips"  title="'.$this->_t->trans('trans_to_tooltip').'">'.$this->_t->trans('trans_to').'</a></li>'
					.'<li><a href="" index="literature"  class="tooltips"  title="'.$this->_t->trans('literature_tooltip').'">'.$this->_t->trans('literature').'</a></li>'
					.'<li><a href="" index="objNr"  class="tooltips"  title="'.$this->_t->trans('objNr_tooltip').'">'.$this->_t->trans('objNr').'</a></li>'
				.'</ul>';

		// return html content
		return $content;
	}


	/**
	* Method GET NEXT
	* returns the next object UId
	*
	* @return Int object uid
	*/
	public function getNext() {
    // init next
    $next = '';
    // init arr
    $arr = array();
    // get object array
    $arr = $_SESSION['aa_objects'];
    // init key
    $key = 0;
		/** NEXT / PREV Function **/
    foreach($arr as $index => $item) {
      if($item['uid'] == $this->_index) {
        $key = $index;
      }
    }
		if($key+1 < count($arr)) {
      // init next;
      $next = "transcription.php?index=".$arr[$key+1]['uid']."&scan=".$arr[$key+1]['scan'];
    }
		// return
		return $next;
	}


	/**
	* Method GET PREV
	* returns the previous object UId
	*
	* @return Int object uid
	*/
	public function getPrev() {
    // init next
    $prev = '';
    // init arr
    $arr = array();
    // get object array
    $arr = $_SESSION['aa_objects'];
    // init key
    $key = 0;
		/** NEXT / PREV Function **/
    foreach($arr as $index => $item) {
      if($item['uid'] == $this->_index) {
        $key = $index;
      }
    }
		if($key - 1 >= 0) {
      // init next;
      $prev = "transcription.php?index=".$arr[$key - 1]['uid']."&scan=".$arr[$key - 1]['scan'];
    }
		// return
		return $prev;
	}
}
?>
