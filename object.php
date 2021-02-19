<?php
require_once('src/classes/Config.class.php');
$config = new Config;
$host = $config->getSection('host');
if (session_id() == '') {
  session_set_cookie_params(604800, '/', '.'.$host->hostname);
  session_start();
}
$thumbCategory = (isset($_COOKIE['thumb_category'])) ? $_COOKIE['thumb_category'] : 'allImages';

if (isset($_POST['thumb_category'])) {
    $thumbCategory =  $_POST['thumb_category'];
    setcookie('thumb_category', $_POST['thumb_category'], time()+3600, '/', '.lucascranach.org');
}
ob_start();
?>

<!DOCTYPE html>
<html xml:lang="en" lang="en">
<head>
<meta name="author" content="joerg Stahlmann &lt;stahlmann.joerg@gmail.com"/>
<meta name="keywords" content="CRANACH DIGITAL ARCHIVE Lucas Elder Smkp Stiftung museum kunstpalast IIPImage
    Ajax Internet Imaging Protocol IIP Zooming Streaming High Resolution Mootools"/>
<meta name="description" content="CRANACH DIGITAL ARCHIVE :: High Resolution Image"/>
<meta name="copyright" content="&copy; 2010 Stiftung museum kunstpalast"/>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=9" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<base href="<?=$config->getBaseUrl()?>" />
<link rel="stylesheet" type="text/css" media="all" href="css/bootstrap.custom.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/css3.object.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/user.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/iip.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/iip.image.compare.css" />
<!--[if lt IE 9]>
<link rel="stylesheet" type="text/css" media="all" href="css/ie.css" />
<![endif]-->
<link rel="shortcut icon" href="images/cda-favicon.ico" />
<link rel="apple-touch-icon" href="images/cda-favicon.ico" />
<title>CRANACH DIGITAL ARCHIVE</title>
<?php

require_once('src/classes/Object.class.php');
require_once('src/classes/User.class.php');
require_once("src/classes/Translator.class.php");

// create translation object
$t = new Translator('src/xml/locallang/locallang_object.xml');

$content = '';
$object = new ObjectData();

$navigation = $object->getNavigation();
$overview = $object->getOverview();
$thumbnails = $object->getThumbnails($thumbCategory);
$connectedWorks = $object->getConnectedWorks();
$bigThumb = $object->getBigThumb();
$content = $object->getContent();

// Get User Area
$u = new User();
$u->saveObjectToHistory();
$userArea = $u->getUserArea();

?>
<!-- recaptcha -->
<script src='https://www.google.com/recaptcha/api.js'></script>
</head>
<body>
    <!-- NAVBAR STATIC TOP -->
    <?php echo $navigation; ?>
    <div class="row user-row">
        <?php echo $userArea; ?>
    </div> <!-- /.row -->

    <!-- WRAPPER -->
    <div class="container-fluid wrapper">
        <!-- #################### MODAL CONTENT ################### -->
        <div id="contact-modal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title message-title">
                            <?php echo $t->trans('message-title'); ?>
                            &nbsp;
                            <?php echo $object->getObjNr(); ?>
                        </h4>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <div class="form-group" id="message-sender-grp">
                                <label class="control-label" for="message-sender">
                                    <?php echo $t->trans('message-sender'); ?>
                                </label>
                                <textarea class="form-control" id="message-sender"
                                wrap="soft" name="message-sender" rows="5"></textarea>
                            </div>
                            <div class="form-group" id="message-email-grp">
                                <label class="control-label" for="message-email">
                                    <?php echo $t->trans('message-email'); ?>
                                </label>
                                <input type="text" class="form-control"
                                id="message-email" name="message-email" value="" />
                            </div>
                            <div class="form-group" id="message-subject-grp">
                                <label class="control-label" for="message-subject">
                                    <?php echo $t->trans('message-subject'); ?>
                                </label>
                                <input type="text" class="form-control"
                                id="message-subject" name="message-subject" value="" />
                            </div>
                            <div class="form-group" id="message-body-grp">
                                <label class="control-label" for="message-body">
                                    <?php echo $t->trans('message-body'); ?>
                                </label>
                                <textarea class="form-control" id="message-body"
                                wrap="soft" name="message-body" rows="15"></textarea>
                            </div>
                            <div class="form-group" id="recaptcha-grp">
                                <div class="g-recaptcha" data-sitekey="6Lfk6AYUAAAAAD0B0l5pqd4IRKVuXOZkv9UNKU7J"></div>
                            </div>
                            <button type="submit" class="btn btn-success message-send">
                                <?php echo $t->trans('message-send'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="compare-modal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
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
        <!-- main container row -->
        <div class="row voffset1">
            <!-- #################### SIDEBAR LEFT ################### -->
            <div class="col-md-2 hidden-sm hidden-xs">
                <!-- Overview Top Left -->
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $overview; ?>
                    </div> <!-- /.column -->
                    <!-- Thumbnail Container Bottom Left (php imported)-->
                    <div class="col-md-12 col-sm-12">
                        <?php echo $thumbnails; ?>
                    </div> <!-- /.column -->
                </div> <!-- /.row -->
            </div> <!-- /.column -->
            <!-- ###################################################### -->
            <!-- #################### COLUMN RIGHT ################### -->
            <div class="col-md-3 col-md-push-7">
                <div class="row">
                    <div class="col-md-12 hidden-sm hidden-xs">
                        <!-- get CONNECTED WORKS -->
                        <?php echo $connectedWorks; ?>
                    </div> <!-- /.column -->
                    <div class="col-md-12">
                        <!-- get BIG THUMBNAIL -->
                        <?php echo $bigThumb; ?>
                    </div> <!-- /.column -->
                </div> <!-- /.row -->
            </div> <!-- /.column -->
            <!-- ###################################################### -->
            <!-- #################### MIDDLE CONTENT ################### -->
            <!-- Control Panel -->
            <div class="col-md-7 col-md-pull-3">
                <!-- CONTENT -->
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $content; ?>
                    </div> <!-- /.column -->
                </div> <!-- /.row -->
            </div> <!-- /.column -->
            <!-- ###################################################### -->
        </div> <!-- /.main row -->
        <!-- FOOTER -->
        <div class="row">
            <div class="col-md-12">
                <div class="footer">
                    <div id="copyright" class="pull-right"></div>
                </div>
            </div> <!-- /.column -->
        </div> <!-- /.footer row -->
    </div> <!-- /WRAPPER Container fluid -->
</body>
</html>
<script>
window.onload = function() {
    // Set copyright with the current year
    var jetzt = new Date();
    var jahr = jetzt.getFullYear();
    document.getElementById("copyright")
        .innerHTML="&copy; Stiftung Museum Kunstpalast, D&uuml;sseldorf / Technische Hochschule K&ouml;ln, "+jahr;
}
</script>
<!-- The JavaScript -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js" type="text/javascript"></script>
<!-- <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js" type="text/javascript"></script> -->
<script src="//cdn.jsdelivr.net/jquery.cookie/1.4.1/jquery.cookie.min.js" type="text/javascript"></script>
<script src="src/js/bootstrap.min.js" type="text/javascript"></script>
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
<script type="text/javascript" src="src/js/object.js"></script>
<script type="text/javascript" src="src/js/user.js"></script>
<script type="text/javascript" src="src/js/compare.js"></script>
<script type="text/javascript" src="src/js/zoom.js"></script>
<!-- GOOGLE ANALYTICS -->
<script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-29211177-1']);
    _gaq.push(['_trackPageview']);

    (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www')
            + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
</script>

<?php
ob_end_flush();