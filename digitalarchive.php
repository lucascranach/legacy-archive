<?php

require_once('src/classes/Config.class.php');
$config = new Config;
$host = $config->getSection('host');

// start session
if(session_id() == '') {
  session_set_cookie_params(604800, '/', $host->hostname);
  session_start();
}
// header("Content-type:text/html; charset=utf-8");
// echo '<?xml version="1.0" encoding="utf-8">';

// Import of the required once subclasses
require_once('src/classes/Filter.class.php');
require_once('src/classes/Gallery.class.php');
require_once('src/classes/User.class.php');
// Import of the required subclasses
require_once("src/classes/Translator.class.php");

/**
 * Request 'dimension' handles the document width and height funcionality.
 * If the width and height of the document is not already set in a session,
 * a javascript function redirects the location with the dimensions as parameter.
 *
 * @author	Joerg Stahlmann <>
 * @package	elements/utilities
 */

/**
 * Try to load search input from sessions if there is no post
 * though there is a prev selection
 **/
// Create Session for the language
if(isset($_POST['ddlLanguage'])) {
  $lang = $_POST['ddlLanguage'];
  setcookie('lang', $lang, time()+3600, '/', '.' . $host->hostname) or die ("Konnte nicht gesetzt werden");
  $_COOKIE['lang'] = $lang;
}

// get the language from the cookies
$_SESSION['lang'] = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';

// language
$selectedLanguage = $_SESSION['lang'];

// create translation object
$_t = new Translator('src/xml/locallang/locallang_gallery.xml');

/**
 * ###########################################################
 * ADVANCED SEARCH SECTION
 * ###########################################################
 **/

// init advanced search array:
$advancedArr = array();


if(isset($_COOKIE['cdaAdvancedSearch'])) {
  // get cookie
  $view = $_COOKIE['cdaAdvancedSearch'];

  if($view == "true") {
    // advanced view
    $advancedView = 'block';
    // advanced link
    $advancedLink = 'none';
    // reset button
    $top = '238';
  } else {
    // advanced view
    $advancedView = 'none';
    // advanced link
    $advancedLink = 'block';
    // reset button
    $top = '94';
  }
} else {

  // advanced view
  $advancedView = 'none';
  // advanced link
  $advancedLink = 'block';
  // reset button
  $top = '94';
}

// Get Gallery
$g = new Gallery();
// get navigation
$navigation = $g->getNavigation();
// public function get gallery
$content = $g->getGallery();


// Get Filter
$f = new Filter();
// public function
$filter = $f->getFilter();

// Get User Area
$u = new User();
// public content
$userArea = $u->getUserArea();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<head>
<meta charset="utf-8" />
<meta name="author" content="joerg Stahlmann &lt;stahlmann.joerg@gmail.com"/>
<meta name="keywords" content="CRANACH DIGITAL ARCHIVE Lucas Elder Smkp Stiftung museum kunstpalast IIPImage Ajax Internet Imaging Protocol IIP Zooming Streaming High Resolution Mootools"/>
<meta name="description" content="CRANACH DIGITAL ARCHIVE :: High Resolution Image"/>
<meta name="copyright" content="&copy; 2015 Stiftung museum kunstpalast"/>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="index,follow">
<base href="<?=$config->getBaseUrl()?>" />
<meta http-equiv="X-UA-Compatible" content="IE=9" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" media="all" href="css/bootstrap.custom.gallery.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/css3.object.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/cda.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/gallery.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/glyph-icons.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/jquery-ui.min.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/jquery-ui.structure.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/user.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/iip.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/iip.image.compare.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/facelift.css" />
<!--[if lt IE 9]>
<link rel="stylesheet" type="text/css" media="all" href="css/ie.css" />
<![endif]-->
<link rel="shortcut icon" href="images/cda-favicon.ico" />
<link rel="apple-touch-icon" href="images/cda-favicon.ico" />
<title>CRANACH DIGITAL ARCHIVE</title>
<!-- The JavaScript -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js" type="text/javascript"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js" type="text/javascript" language="javascript"></script>
<script src="//cdn.jsdelivr.net/jquery.cookie/1.4.1/jquery.cookie.min.js" type="text/javascript" language="javascript"></script>
<script src="src/js/bootstrap.min.js" type="text/javascript" language="javascript"></script>
<script src="src/js/thesaurus.js" type="text/javascript" language="javascript"></script>
<script src="src/js/filter.js" type="text/javascript" language="javascript"></script>
<script src="src/js/user.js" type="text/javascript" language="javascript"></script>

<script type="text/javascript" src="src/iip/mootools-core-1.5.2.js"></script>
<script type="text/javascript" src="src/iip/mootools-more-1.5.2.js"></script>

<!-- Main iipmooviewer include -->
<script type="text/javascript" src="src/iip/iipmooviewer-2.0.js"></script>

<!-- Load our protocols -->
<script type="text/javascript" src="src/iip/protocols/deepzoom.js"></script>
<script type="text/javascript" src="src/iip/protocols/djatoka.js"></script>
<script type="text/javascript" src="src/iip/protocols/iip.js"></script>
<script type="text/javascript" src="src/iip/protocols/zoomify.js"></script>
<script type="text/javascript" src="src/iip/protocols/iiif.js"></script>

<!-- Load our various components -->
<script type="text/javascript" src="src/iip/blending.js"></script>
<script type="text/javascript" src="src/iip/navigation.js"></script>
<script type="text/javascript" src="src/iip/scale.js"></script>
<script type="text/javascript" src="src/iip/touch.js"></script>
<script type="text/javascript" src="src/iip/annotations.js"></script>
<script type="text/javascript" src="src/iip/annotations-edit.js"></script>

<!-- Load our language files -->
<script type="text/javascript" src="src/iip/lang/help.en.js"></script>
<script type="text/javascript" src="src/js/compare.js"></script>

<script type="text/javascript">

$( function() {
  // reset Treatment Modul cookie
  $.removeCookie('mntModul', { expires: 7, path: '/', domain: '<?=$host->hostname;?>' });

  $('[data-toggle="tooltip"]').tooltip();

  // checkbox array attribution
  var formAttrArr = Array();
  // checkbox array date
  var formDateArr = Array();
  // checkbox array tech
  var formTechArr = Array();
  // checkbox array collection
  var formCollArr = Array();
  // checkbox array thesaurus
  var formThesauArr = Array();

  // system language
  var lang = "<?php echo $selectedLanguage; ?>";
  // checkmark array form filter
  if(<?php if(isset($_SESSION['search_attr'])) { echo count($_SESSION['search_attr']); } else { echo 0; } ?> != 0) {
    formAttrArr = $.parseJSON('<?php if(isset($_SESSION['search_attr'])) { echo json_encode($_SESSION['search_attr']); } ?>');
  }

  if(<?php if(isset($_SESSION['search_date'])) { echo count($_SESSION['search_date']); } else { echo 0; } ?> != 0) {
    formDateArr = $.parseJSON('<?php if(isset($_SESSION['search_date'])) { echo json_encode($_SESSION['search_date']); } ?>');
  }

  if(<?php if(isset($_SESSION['search_tech'])) { echo count($_SESSION['search_tech']); } else { echo 0; } ?> != 0) {
    formTechArr = $.parseJSON('<?php if(isset($_SESSION['search_tech'])) { echo json_encode($_SESSION['search_tech']); } ?>');
  }

  if(<?php if(isset($_SESSION['search_collection'])) { echo count($_SESSION['search_collection']); } else { echo 0; } ?> != 0) {
    formCollArr = $.parseJSON('<?php if(isset($_SESSION['search_collection'])) { echo json_encode($_SESSION['search_collection']); } ?>');
  }

  if(<?php if(isset($_SESSION['search_thesau'])) { echo count($_SESSION['search_thesau']); } else { echo 0; } ?> != 0) {
    var formThesauArr = $.parseJSON('<?php if(isset($_SESSION['search_thesau'])) { echo json_encode($_SESSION['search_thesau']); } ?>');
  }


  // Create and Run jFilter Functions
  var fil = new jFilter();

  if(formAttrArr.length > 0){
    fil.getFilter(formAttrArr);
  }
  if(formDateArr.length > 0){
    fil.getFilter(formDateArr);
  }
  if(formTechArr.length > 0){
    fil.getFilter(formTechArr);
  }
  if(formCollArr.length > 0){
    fil.getFilter(formCollArr);
  }

  // Create jThesaurus Object
  var thesau = new jThesaurus(lang, formThesauArr);

  /*$("#gallery img").tooltip({
    effect: 'bouncy',
    predelay: 800,
    position: "center center"
  });*/


  // get document inner height
  var h = window.innerHeight;
  // get document inner width
  var w = window.innerWidth;

  var maxImages;


  var x = parseInt($('#content').width() / 153);

  var y = parseInt(h / 153);

  maxImages =(x*y);

  var page = (typeof($.cookie('page')) !== 'undefined') ? parseInt($.cookie('page')) : 1;

  if(parseInt($.cookie('maxImages')) != maxImages && page < parseInt($.cookie('lastPage'))) {
    // set max images
    $.cookie('maxImages', maxImages, { expires: 7, path: '/', domain: '<?=$host->hostname?>' });
    // reload page
    self.location.href=window.location.pathname;
  }
});

function leClick(value) {
  var doc = document.getElementById(value);
  var a = document.getElementsByTagName("a");

  var node = doc.parentNode;

  if(doc.style.display == "none") {
    doc.style.display = "block";
    node.setAttribute('class', 'current');
    document.cookie = value+"=block";
  } else {
    doc.style.display = "none";
    node.setAttribute('class', 'closed');
    document.cookie = value+"=none";
  }
}

function advancedSearch(value) {
  if(value) {
    $("search").hide();
    $(".advancedLink").hide();
    $(".advancedSearch").show();
    document.cookie = "cdaAdvancedSearch=true";
    $(".resetFilter").css("top", 238);
  } else {
    $(".advancedLink").show();
    $(".advancedSearch").hide();
    document.cookie = "cdaAdvancedSearch=false";
    $(".resetFilter").css("top", 94);
  }
}

/**
 * Set current selectedd language
 */
setLanguage = function() {
  var lang = ($.cookie("lang") === "Deutsch") ? "Englisch" : "Deutsch";
  $.cookie('lang', lang, { expires: 7, path: '/', domain: '<?=$host->hostname;?>' });
  location.reload();
}
</script>
</head>
  <body>

    <!-- Top Control Panel -->
    <?php echo $navigation; ?>
    <?php echo $userArea; ?>
    <!-- WRAPPER -->
    <div class="container-fluid wrapper no-padding-left no-padding-right">

      <div id="compare-modal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
              <!-- main container row -->
              <div class="row iipimage">

                <!-- #################### MIDDLE CONTENT ################### -->

                <!-- Control Panel -->
                <div class="col-md-6 fill">

                  <!-- IIP IMAGE DIV -->
                  <div id="targetframe-left"></div>

                </div> <!-- /.column -->
                <!-- Control Panel -->
                <div class="col-md-6 fill">

                  <!-- IIP IMAGE DIV -->
                  <div id="targetframe-right"></div>

                </div> <!-- /.column -->

                <!-- ###################################################### -->

              </div> <!-- /.main row -->
            </div>
        </div>
      </div>
    </div>

      <div class="row">

        <!-- GALLERY -->
        <div class="col-lg-10 col-md-9 col-sm-8 col-lg-push-2 col-md-push-3 col-sm-push-4 no-padding-left no-padding-right voffset1">
          <div class="collapse" id="collapseUserArea"><?php echo $userArea; ?></div>
          <div id="content"><?php echo $content; ?></div>
        </div>

        <!-- LEFT MENU NAV -->
        <div class="col-lg-2 col-md-3 col-sm-4 col-lg-pull-10 col-md-pull-9 col-sm-pull-8 no-padding-left no-padding-right">

          <div id="nav">

            <div id="slidedown_menu" style="display:block">

              <div class="desc"><?php echo $_t->trans('searchby'); ?>:</div>

              <div class="resetFilter" style="top:<?php echo $top ?>px; z-index:2; left:150px;">

                <form method="POST" name="reset_search" action="/gallery">
                    <input type="hidden" name="reset_search" value="0" />
                    <input type="image" src="images/reset_arrow.png" value="" width="7" height="8" style="float:left;margin:0.4em 0px"/>
                  <input type="submit" value="<?php echo $_t->trans('resetLink') ?>" style=" background:none;border:none;margin:0px;padding:0px;font:12px Arial, Helvetica, sans-serif;cursor:pointer;color:#555566" />
                </form>

              </div>

              <div class="reset">

                <form method="POST" action="/gallery">
                  <input type="hidden" name="reset_search" value="0" />
                  <input type="image" src="images/reset_arrow.png" width="10" height="10" style="float:left;margin:9px 0px 0px 5px"/>
                </form>

              </div>

              <form method="POST" name="formFilter" action="/gallery">

                <!-- Overall Search -->
                <div class="search">

                  <input type="hidden" name="search[]" value="0">
                  <input type="image" src="images/searchicon.gif" width="15" height="15" style="float:right;margin:4px;" />
                  <input maxlength="1024" name="search[]" value="<?php if(isset($_SESSION['search_input'][1])) { echo $_SESSION['search_input'][1]; } ?>"
                      onKeyPress="javascript:return submitenter(this,event)"
                      style="width:200px;height:18px;float:left;border:none;margin-top:1px;margin-left:15px" />

                </div>

                <div class="advancedLink" style="display:<?php echo $advancedLink ?>"><a href="javascript:advancedSearch(true)"><?php echo $_t->trans('advanced_label_show') ?></a></div>

                <!-- ADVANCED SEARCH -->
                <div class="advancedSearch" style="display:<?php echo $advancedView ?>" >

                    <!-- Title Search -->
                    <?php echo $_t->trans('advanced_title') ?>

                    <div class="searchField">

                      <input type="image" src="images/searchicon.gif" width="15" height="15" style="float:right;margin:4px;" />
                      <input maxlength="1024" name="search[]" value="<?php if(isset($_SESSION['search_input'][2])) { echo $_SESSION['search_input'][2]; } ?>"
                          onKeyPress="javascript:return submitenter(this,event)"
                          style="width:200px;height:18px;float:left;border:none;margin-top:1px;margin-left:15px" />

                    </div>

                    <!-- Fr No Search -->
                    <?php echo $_t->trans('advanced_fr') ?>

                    <div class="searchField">
                      <input type="image" src="images/searchicon.gif" width="15" height="15" style="float:right;margin:4px;" />
                      <input maxlength="1024" name="search[]" value="<?php if(isset($_SESSION['search_input'][3])) { echo $_SESSION['search_input'][3]; } ?>"
                          onKeyPress="javascript:return submitenter(this,event)"
                          style="width:200px;height:18px;float:left;border:none;margin-top:1px;margin-left:15px" />
                    </div>

                    <!-- location Search -->
                    <?php echo $_t->trans('advanced_location') ?>

                    <div class="searchField">

                      <input type="image" src="images/searchicon.gif" width="15" height="15" style="float:right;margin:4px;" />
                      <input maxlength="1024" name="search[]" value="<?php if(isset($_SESSION['search_input'][4])) { echo $_SESSION['search_input'][4]; } ?>"
                          onKeyPress="javascript:return submitenter(this,event)"
                          style="width:200px;height:18px;float:left;border:none;margin-top:1px;margin-left:15px" />

                    </div>

                    <!-- location Search -->
                    <?php echo $_t->trans('advanced_id') ?>

                    <div class="searchField">

                      <input type="image" src="images/searchicon.gif" width="15" height="15" style="float:right;margin:4px;" />
                      <input maxlength="1024" name="search[]" value="<?php if(isset($_SESSION['search_input'][5])) { echo $_SESSION['search_input'][5]; } ?>"
                          onKeyPress="javascript:return submitenter(this,event)"
                          style="width:200px;height:18px;float:left;border:none;margin-top:1px;margin-left:15px" />

                    </div>

                    <a href="javascript:advancedSearch(false)" style="background: center left url(images/arrow_up.png) no-repeat transparent; padding-left:10px"><?php echo $_t->trans('advanced_label_hide') ?></a>

                </div>
                <!-- /ADVANCED SEARCH -->

                <div class="desc"><?php echo $_t->trans('filterby'); ?>:</div>

                <div id="filter"><?php echo $filter; ?></div>

                <ul id="menu"></ul>

              </form>

            </div>
            <!-- /END OF SLIDEDOWN MENU -->

          </div>
          <!-- /END OF NAV -->

        </div>
        <!-- /END OF LEFT MENU -->

      </div>
      <!-- / Content row -->
    
    </div>
    <!-- / Wrapper -->
    <footer id="footer">
      <div id="copyright"></div>
    </footer>

    <script>
    window.onload = function() {
      // Set copyright with the current year
      var jetzt = new Date();
      var jahr = jetzt.getFullYear();
      document.getElementById("copyright").innerHTML="&copy; Stiftung Museum Kunstpalast, D&uuml;sseldorf / Technische Hochschule K&ouml;ln, "+jahr;
    }
    </script>
    <!-- GOOGLE ANALYTICS -->
    <script type="text/javascript">

    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-29211177-1']);
    _gaq.push(['_trackPageview']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    
    </script>

</body>
</html>