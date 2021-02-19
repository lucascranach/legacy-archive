<?php
/**
 * Ajax call for drag'n drop object
 * reads all neccessary data from mysql database
 * and returns it as json back to the javascript ajax method
 *
 * @author Joerg Stahlmann
 */
if (session_id() == '') {
    session_set_cookie_params(604800, '/');
    session_start();
}
require_once('../classes/DbConnection.class.php');
require_once('../classes/Metadata.class.php');

$array = array(false);

if (isset($_POST['url'])) {
    $dbcon = new DbConnection();
    $con = $dbcon->getConnection();
    $lang = (isset($_COOKIE['lang'])) ? $_COOKIE['lang'] : 'Englisch';

    $timestamp = time();
    $datum = date('d.m.Y', $timestamp);
    $uhrzeit = date('H:i', $timestamp);
    $timestamp = $datum . ' - ' . $uhrzeit;

    $url= $_POST['url'];
    $arr = parse_url($url);

    $query = array();
    parse_str($arr['query'], $query);

    $objNr = substr($query['obj'], 0, strpos($query['obj'], "_FR"));
    $name = substr($query['img'], 0, strpos($query['img'], ".tif"));

    // determine thumbnail directory
    if (!empty($query['remarks'])) {
        // RKD internal
        if (file_exists('../../thumbnails/' . $query['obj'] . '/11_RKD/' . $query['fol'] . '/')) {
            $thumb = 'thumbnails/' . $query['obj'] . '/11_RKD/' . $query['fol'] . '/' . $name.'.jpg';
        // RKD external
        } elseif (file_exists('../../thumbnails/' . $query['obj'] . '_RKD/11_RKD/' . $query['fol'] . '/')) {
            $thumb = 'thumbnails/' . $query['obj'] . '_RKD/11_RKD/' . $query['fol'] . '/' . $name.'.jpg';
        } else {
            $thumb = 'images/no-image.png';
        }
    } else {
        $thumb = 'thumbnails/' . $query['obj'] . '/' . $query['fol'] . '/' . $name.'.jpg';
    }


    $sql = "SELECT DISTINCT o.UId AS id, o.ObjNr AS objNr, o.ObjIdentifier AS frNr, t.Title AS title,\n"
    . "a.Name AS attr, a.Prefix AS prefix, a.Suffix AS suffix, m.Value AS repo\n"
    . "FROM Object o\n"
    . "INNER JOIN ObjectTitle t ON o.UId = t.Object_UId\n"
    . "INNER JOIN Attribution a ON o.UId = a.Object_UId\n"
    . "INNER JOIN MultipleTable m ON o.UId = m.Object_UId\n"
    . "WHERE o.ObjNr = '$objNr'\n"
    . "AND t.DisplayOrder < 3\n"
    . "AND t.Language LIKE '$lang'\n"
    . "AND a.DisplayOrder < 3\n"
    . "AND a.Language LIKE '$lang'\n"
    . "AND (m.Type LIKE '%Eigen%' OR m.Type LIKE '%Repo%')\n"
    . "AND m.Language LIKE '$lang'";

    $result = mysqli_query($sql, $con);

    while ($row = mysqli_fetch_object($result)) {
        $attr = (!empty($row->attr)) ? $row->attr : '';
        $prefix = (!empty($row->prefix)) ? $row->prefix : '';
        $suffix = (!empty($row->suffix)) ? $row->suffix : '';
        $attribution = $prefix.' '.$attr.' '.$suffix;

        $a = array(
            'name' => $name,
            'id' => $row->id,
            'objNr' => $row->objNr,
            'folder' => $query['obj'],
            'category' => $query['fol'],
            'frNr' => $row->frNr,
            'title' => $row->title,
            'attr' => $attribution,
            'repo' => $row->repo,
            'image' => $query['img'],
            'url' => $url,
            'thumb' => $thumb,
            'time' => $timestamp
        );
        $array = $a;
    }

    $metadata = new Metadata('', $query['img']);
    $array = array_merge($metadata->getMetadata(), $array);
}

$arr = $_SESSION['user']['compare'];

foreach ($arr as $obj) {
    if ($obj['image'] == $query['img']) {
        return false;
    }
}


if (empty($arr)) {
    $arr[] = $array;
} else {
    array_unshift($arr, $array);
}

if (count($arr) > 20) {
    array_pop($arr);
}

$_SESSION['user']['compare'] = $arr;
echo json_encode($array);
