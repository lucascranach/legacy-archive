<?php

/**
 * Class 'User' handles the whole functionality of the User Area.
 * It lists all saved objects, adds the drag n drop functionality and sets the view of the last 5 objects.
 *
 * @author Joerg Stahlmann <>
 * @package elements/class
 */
class User
{
    protected $lang;
    protected $t;
    protected $id;
    protected $con;

    /**
    * Constructor function of the class
    */
    public function __construct()
    {
        require_once("src/classes/Translator.class.php");
        $this->t = new Translator('src/xml/locallang/locallang_user.xml');

        require_once('src/classes/DynamicDir.class.php');
        $this->dynDir = new DynamicDir();

        require_once('src/classes/DbConnection.class.php');
        $dbcon = new DbConnection();
        $this->con = $dbcon->getConnection();

        $this->lang = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';
        $this->id = isset($_SESSION['object']['uid']) ? $_SESSION['object']['uid'] : '';
    }

    /**
    * Save selected Object to the UserArea
    *
    */
    public function saveObjectToHistory()
    {
        $arr = array();
        $arr = (isset($_SESSION['user']['history'])) ? $_SESSION['user']['history'] : '';
        $prevId = (isset($arr[0]['id'])) ? $arr[0]['id'] : '';

        if (empty($this->id) || $prevId == $this->id) {
            return;
        }

        $timestamp = time();
        $datum = date("d.m.Y", $timestamp);
        $uhrzeit = date("H:i", $timestamp);
        $timestamp = $datum . " - " . $uhrzeit;

        if (empty($arr)) {
            $arr = array("id" => $this->id, "time" => $timestamp);
        } else {
            array_unshift($arr, array("id" => $this->id, "time" => $timestamp));
        }

        if (count($arr) > 20) {
            array_pop($arr);
        }

        $_SESSION['user']['history'] = $arr;
    }

    /**
    * Get the UserArea
    *
    * @return string html content of the user area
    */
    public function getUserArea()
    {
        $content = '<div id="collapseUserArea" class="collapse pull-right col-sm-12">';
        $content .= '<div class="panel with-nav-tabs panel-default">'
        . '<div class="panel-heading">'
        . '<ul class="nav nav-tabs">'
        . '<li class="active"><a href="#tab1default" data-toggle="tab">'
        . '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>&nbsp;'
        . $this->t->trans('verlauf') . '</a></li>'
        . '<li><a href="#tab2default" data-toggle="tab">'
        . '<span class="glyphicon glyphicon-map-marker" aria-hidden="true"></span>&nbsp;'
        . $this->t->trans('comparison') . '</a></li>'
        . '<button type="button" class="close" data-toggle="collapse"'
        . 'data-target="#collapseUserArea" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        . '</div>'
        . '<div class="panel-body">'
        . '<div class="tab-content flex-container">'
        . '<div class="tab-pane fade in active col-sm-12" id="tab1default">'
        . $this->getUserHistory()
        . '<div class="pull-right col-lg-2 col-md-3">'
        . '<div class="text-right"><a class="delete-history">'
        . '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;'
        . $this->t->trans('history_delete') . '</a></div>'
        . '</div>'
        . '</div>'
        . '<div class="tab-pane fade col-sm-12 droptarget" id="tab2default" ondrop="drop(event)"'
        . 'ondragover="allowDrop(event)">' . $this->getUserCompare()
        // Compare Box
        . '<div class="pull-right col-lg-2 col-md-3">'
        . '<div class="col-sm-12 compare-select-box panel panel-default">'
        . '<div class="panel-heading text-center">' . $this->t->trans('explanation') . '</div>'
        . '<div class="panel-body">'
        . '<div class="col-sm-6 compare-left text-center">'
        . '</div>'
        . '<div class="col-sm-6 compare-right text-center">'
        . '</div>'
        . '<div class="col-sm-12 text-center"><a class="compare-objects">'
        . '<span class="glyphicon glyphicon-sound-dolby" aria-hidden="true"></span>&nbsp;'
        . $this->t->trans('compare') . '</a></div>'
        . '</div>'
        . '</div>'
        . '<div class="text-right"><a class="delete-compare">'
        . '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;'
        . $this->t->trans('comparison_delete') . '</a></div>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '<div class="panel-footer text-center hint">' . $this->t->trans('hint') . '</div>'
        . '</div>'
        . '</div>';
        $content .= '</div>';

        return $content;
    }

    /**
    * Get all neccessary data from objects
    * saved in the user history session
    *
    * @return html history data
    */
    protected function getUserHistory()
    {
        $history = array();
        $arr = array();
        $arr = (isset($_SESSION['user']['history'])) ? $_SESSION['user']['history'] : '';

        if (empty($arr)) {
            return;
        }

        foreach ($arr as $obj) {
            if(!isset($obj['id'])) return;
            $id = $obj['id'];
            
            $sql = "SELECT DISTINCT o.UId AS id, o.ObjNr AS objNr, o.ObjIdentifier AS frNr, t.Title AS title,\n"
            . "a.Name AS attr, a.Prefix AS prefix, a.Suffix AS suffix, m.Value AS repo, i.Overall_Image AS image\n"
            . "FROM Object o\n"
            . "INNER JOIN ObjectTitle t ON o.UId = t.Object_UId\n"
            . "INNER JOIN Attribution a ON o.UId = a.Object_UId\n"
            . "INNER JOIN MultipleTable m ON o.UId = m.Object_UId\n"
            . "INNER JOIN Images i ON o.UId = i.Object_UId\n"
            . "WHERE o.UId = '$id'\n"
            . "AND t.DisplayOrder < 3\n"
            . "AND t.Language LIKE '$this->lang'\n"
            . "AND a.DisplayOrder < 3\n"
            . "AND a.Language LIKE '$this->lang'\n"
            . "AND (m.Type LIKE '%Eigen%' OR m.Type LIKE '%Repo%')\n"
            . "AND m.Language LIKE '$this->lang'";

            $result = mysqli_query($this->con, $sql);
            while ($row = mysqli_fetch_object($result)) {
                $attr = (!empty($row->attr)) ? $row->attr : '';
                $prefix = (!empty($row->prefix)) ? $row->prefix : '';
                $suffix = (!empty($row->suffix)) ? $row->suffix : '';
                $attribution = $prefix . ' ' . $attr . ' ' . $suffix;

                $a = array(
                  'id' => $row->id,
                  'objNr' => $row->objNr,
                  'frNr' => $row->frNr,
                  'title' => $row->title,
                  'attr' => $attribution,
                  'repo' => $row->repo,
                  'image' => $row->image,
                  'time' => $obj['time']
                );
                array_push($history, $a);
            }
        }
        $content = $this->getUserHistoryContent($history);

        return $content;
    }

    /**
    * Get html history content
    *
    * @return html history data
    */
    protected function getUserHistoryContent($arr)
    {
        $content = '';

        foreach ($arr as $obj) {
            $folder = $obj['objNr'] . '_' . $obj['frNr'];
            $thumbnail ='<img class="img-responsive img-thumbnail"'
            . 'title="Cranach - ' . htmlspecialchars($obj['title']) . '"'
            . 'alt="Cranach - ' . htmlspecialchars($obj['title']) . '"'
            . 'src="' . $this->dynDir->getDir() . 'thumbnails/'
            . $folder . '/01_Overall/' . $obj['image'] . '.jpg">';

            $content .= '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12">';
            $content .= '<div class="col-lg-4 col-md-5 col-sm-6 col-xs-4">';
            $content .= '<a href="object.php'
            . '?&obj=' . $folder . '&uid=' . $obj['id'] . '&fol=01_Overall&img=' . $obj['image'] . '.tif">'
            . $thumbnail
            . '</a>';
            $content .= '</div>';

            $content .= '<div class="col-lg-8 col-md-7 col-sm-6 col-xs-8">';
            $content .= '<dt>' . $obj['title'] . '</dt>';
            $content .= '<dd>' . $obj['attr'] . '</dd>';
            $content .= '<dd>' . $obj['repo'] . '</dd>';
            $content .= '<dd><i>' . $obj['time'] . '</i></dd>';
            $content .= '</div>';
            $content .= '</div>';
        }

        return $content;
    }

    /**
    * Get html compare content
    *
    * @return html compare data
    */
    protected function getUserCompare()
    {
        $content = '';

        $arr = (isset($_SESSION['user']['compare'])) ? $_SESSION['user']['compare'] : '';

        if (empty($arr)) {
            return;
        }

        foreach ($arr as $obj) {
            $folder = $obj['objNr'] . '_' . $obj['frNr'];
            $thumbnail ='<img class="img-responsive img-thumbnail"'
            . 'title="Cranach - ' . htmlspecialchars($obj['title']) . '"'
            . 'alt="Cranach - ' . htmlspecialchars($obj['title']) . '"'
            . 'src="' . $obj['thumb'] . '">';

            $content .= '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 compare-box">';
            $content .= '<div class="col-lg-4 col-md-5 col-sm-6 col-xs-4 margin-bot-small">';
            $content .= '<a href="' . $obj['url'] . '">'
            . $thumbnail
            . '</a>';

            $content .= '<div class="add-compare text-center">'
            . '<span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>&nbsp;'
            . $this->t->trans('add') . '</div>';
            $content .= '</div>';
            $content .= '<div class="col-lg-8 col-md-7 col-sm-6 col-xs-8">';
            $content .= '<dt>' . $obj['title'] . '</dt>';
            $content .= '<dd><i>' . $obj['name'] . '</i></dd>';
            $content .= '<dd>' . $obj['fileType'] . '</dd>';
            $content .= '<dd>' . $obj['imageDesc'] . '</dd>';
            $content .= '</div>';
            $content .= '</div>';
        }

        return $content;
    }
}
