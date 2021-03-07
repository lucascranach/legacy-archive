<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Joerg Stahlmann <>
 *  All rights reserved
 *
 *  This script is part of the cranach project. The Cranach Digital Archive (cda) is an interdisciplinary collaborative research resource,
 *	providing access to art historical, technical and conservation information on paintings by Lucas Cranach (c.1472 - 1553) and his workshop.
 *	The repository presently provides information on more than 400 paintings including c.5000 images and documents from 19 partner institutions.
 *
 ***************************************************************/

// start session
if (session_id() == '') {
    session_start();
}
// Import of the required subclasses
require("src/classes/Translator.class.php");

/**
 * Utility Class 'Filter' handles the whole filter functionality in the gallery view.
 * It lists all items, adds checkboxes and manages the click / search handler.
 *
 * @author Jörg Stahlmann <>
 * @package elements/utility
 */
class Filter {

  // selected language:
  private $_selectedLanguage;

  // translation class object:
  private $_t;

  /**
   * Constructor function of the class
   */
  public function __construct() {

    // get the language from the session
    $this->_selectedLanguage = $_SESSION['lang'];

    // create translation object
    $this->_t = new Translator('src/xml/locallang/locallang_filter.xml');

  }


  /**
   * Get the Filter
   *
   * @return string html content of the filter list
   */
  public function getFilter() {

    // get cookie
    $nav_attr = isset($_COOKIE['nav_attr']) ? $_COOKIE['nav_attr'] : "none";
    // list style
    $li_nav_attr = ($nav_attr == "block") ? "current" : "closed";

    // get cookie
    $nav_named = isset($_COOKIE['nav_named']) ? $_COOKIE['nav_named'] : "none";
    // list style
    $li_nav_named = ($nav_named == "block") ? "current" : "closed";

    // get cookie
    $nav_dating = isset($_COOKIE['nav_dating']) ? $_COOKIE['nav_dating'] : "none";
    // list style
    $li_nav_dating = ($nav_dating == "block") ? "current" : "closed";

    // get cookie
    $nav_collection = isset($_COOKIE['nav_collection']) ? $_COOKIE['nav_collection'] : "none";
    // list style
    $li_nav_collection = ($nav_collection == "block") ? "current" : "closed";

    // get cookie
    $nav_tech = isset($_COOKIE['nav_tech']) ? $_COOKIE['nav_tech'] : "none";
    // list style
    $li_nav_tech = ($nav_tech == "block") ? "current" : "closed";

    
    $content = '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="category[]" index="category" value="top100">'.$this->_t->trans('top100').'</li>';

    // FILTER - ATTRIBUTION
    $content .= '<li class="'.$li_nav_attr.'" ><a href="javascript:leClick(\'nav_attr\')" name="nav_attr">'.$this->_t->trans('attr_label_h').'</a>'
    . '<div id="nav_attr" style="display:'.$nav_attr.'">'
    . '<ul>'
    . '<li><input type="hidden" name="attr[]" index="attr" value="0">'
    . '<input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_1">'.$this->_t->trans('attr_label[0]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_2">'.$this->_t->trans('attr_label[1]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_3">'.$this->_t->trans('attr_label[2]').'</li>';

    /** List Item Named Masters from the Cranach Workshop **/
    $namedMasters = $this->collectionList('//languageKey[@index="'.$this->_selectedLanguage.'"]/label[@index="named"]');

    $content .=	'<li class="'.$li_nav_named.'">'
    . '<a href="javascript:leClick(\'nav_named\')" name="nav_named"><span style="font-size:10px">'.$this->_t->trans('attr_label[3]').'</span></a>'
    . '<div id="nav_named" style="display:'.$nav_named.'">';
    $content .=	'								<ul>';
    foreach($namedMasters as $val => $master) {
      $content .= '<li>'
      . '<input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="named" value="'.$val.'">'.$master
      . '</li>';
    }
    $content .= '</ul>'
    . '</div>'
    . '</li>';

    $content .= '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_4">'.$this->_t->trans('attr_label[4]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_5">'.$this->_t->trans('attr_label[5]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_6">'.$this->_t->trans('attr_label[6]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_7">'.$this->_t->trans('attr_label[7]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_8">'.$this->_t->trans('attr_label[8]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_9">'.$this->_t->trans('attr_label[9]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_10">'.$this->_t->trans('attr_label[10]').'</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="attr[]" index="attr" value="attr_11">'.$this->_t->trans('attr_label[11]').'</li>'
    . '</ul>'
    . '</div>'
    . '</li>';

    // FILTER - DATIERUNG
    $content .= '<li  class="'.$li_nav_dating.'" ><a href="javascript:leClick(\'nav_dating\')" name="nav_dating">'.$this->_t->trans('date_label_h').'</a>'
    . '<div id="nav_dating" style="display:'.$nav_dating.'">'
    . '<ul>'
    . '<li><input type="hidden" name="date[]" index="dating" value="0">'
    . '<input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_1">1500 - 1510</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_2">1511 - 1520</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_3">1521 - 1530</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_4">1531 - 1540</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_5">1541 - 1550</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_6">1551 - 1560</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_7">1561 - 1570</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_8">1571 - 1580</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="date_9">1581 - *</li>'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="date[]" index="dating" value="dated">'.$this->_t->trans('dated_h').'</li>'
    . '</ul>'
    . '</div>'
    . '</li>';

    $content .= '<li class="'.$li_nav_collection.'" ><a href="javascript:leClick(\'nav_collection\')" name="nav_collection">'.$this->_t->trans('collection_label_h').'</a>'
    . '<div id="nav_collection" style="display:'.$nav_collection.'">'
    . '<ul>'
    . '<input type="hidden" name="collection[]" index="nav_hiddenCol" value="0">'
    . '<!-- List Item -->';
    /** ################### TRAVERS ROOT ################################ **/
    // TRAVERS THROUGH COUNTRY , CITY AND COLLECTION
    $root = $this->collectionList('//languageKey[@index="'.$this->_selectedLanguage.'"]/label[@index="root_directory"]');
    // for each country
    foreach ($root as $rootkey => $parent) {
      $children = $this->collectionList('//languageKey[@index="'.$this->_selectedLanguage.'"]/label[@index="'.$rootkey.'"]');

      if(count($children) > 0) {
        $content .=	'<li class="current"><a href="javascript:leClick(\'nav_'.$rootkey.'\')" name="nav_'.$rootkey.'"><span style="font-size:10px">'.$parent.'</span></a>'
        . '<div id="nav_'.$rootkey.'" style="display:block">';
        /** ############################################ **/
        // TRAVERS THROUGH COUNTRY , CITY AND COLLECTION
        $countries = $this->collectionList('//languageKey[@index="'.$this->_selectedLanguage.'"]/label[@index="'.$rootkey.'"]');
        // for each country
        foreach ($countries as $key => $country) {
          $cities = $this->collectionList('//languageKey[@index="'.$this->_selectedLanguage.'"]/label[@index="'.$key.'"]');
          if(count($cities) > 0) {
            $content .=	'<ul>'
            . '<li class="closed"><a href="javascript:leClick(\'nav_'.$key.'\')" name="nav_'.$key.'"><span style="font-size:10px">'
            . '<input type="checkbox" onClick="selectOverall(\''.$key.'\')" name="collection[]" index="'.$key.'" value="'.$key.'">'.$country.'</span></a>'
            . '<div id="nav_'.$key.'" style="display:none">';
            foreach($cities as $subkey => $city) {
              $collections = $this->collectionList('//languageKey[@index="'.$this->_selectedLanguage.'"]/label[@index="'.$subkey.'"]');
              if(count($collections) > 0) {
                $content .=	'<ul>'
                . '<li class="closed">'
                . '<a href="javascript:leClick(\'nav_'.$subkey.'\')" name="nav_'.$subkey.'"><span style="font-size:10px">'
                . '<input type="checkbox" onClick="selectOverall(\''.$subkey.'\')" name="collection[]" index="'.$key.'" value="'.$subkey.'">'.$city.'</span></a>'
                . '<div id="nav_'.$subkey.'" style="display:none">';
                $content .=	'<ul>';
                $i = 1;
                foreach($collections as $val => $collection) {
                  $content .= '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="collection[]" index="'.$subkey.'" value="'.$val.'">'.$collection.'</li>';
                  $i++;
                }
                $content .= '</ul>'
                . '</div>'
                . '</li>'
                . '</ul>';
              } else {
                $content .=	'<ul>'
                . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="collection[]" index="'.$key.'" value="'.$subkey.'">'.$city.'</li>';
                $content .= '</ul>';
              }
            }
            $content .= '</div>'
            . '</li>'
            . '</ul>';
          } else {
            $content .=	'<ul>'
            . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="collection[]" index="'.$rootkey.'" value="'.$key.'">'.$country.'</li>';
            $content .= '</ul>';
          }
        }
        /** ############################# **/
        $content .= '</div>'
        . '</li>';
      } else {
        $content .=	'<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="collection[]" index="root_directory" value="'.$rootkey.'">'.$parent.'</li>';
      }
    }
    $content .= '</div>'
    . '</li>';
    // FILTER - TECHNIK
    $content .= '<li  class="'.$li_nav_tech.'" ><a href="javascript:leClick(\'nav_tech\')" name="nav_tech">'.$this->_t->trans('tech_label_h').'</a>'
    . '<div id="nav_tech" style="display:'.$nav_tech.'">'
    . '<ul>'
    . '<!-- List Item -->'
    . '<li><input type="hidden" name="tech[]" index="technic" value="0">'
    . '<input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_1">'.$this->_t->trans('tech_label[0]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_2">'.$this->_t->trans('tech_label[1]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_3">'.$this->_t->trans('tech_label[2]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_4">'.$this->_t->trans('tech_label[3]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_5">'.$this->_t->trans('tech_label[4]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_7">'.$this->_t->trans('tech_label[6]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_8">'.$this->_t->trans('tech_label[7]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_9">'.$this->_t->trans('tech_label[8]').'</li>'
    . '<!-- List Item -->'
    . '<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="tech[]" index="technic" value="tech_10">'.$this->_t->trans('tech_label[9]').'</li>'
    . '</ul>'
    . '</div>'
    . '</li>'
    . '</ul>';

    return $content;
  }


  /**
   * sort list - gets the countries, cities and locatin from the xml-sheet.
   * Sorts the data alphabetical and sets the key of the array entities.
   *
   * @param string xml xpath query
   * @return array sorted array
   */
  protected function collectionList($query) {

    require_once('FirePHPCore/fb.php');
    ob_start();
    fb($query);
    // collection array
    $arr = array();
    try {
      // set the document
      $doc = new DOMDocument;
      // load the documen   t
      $doc->load('src/xml/locallang/locallang_filter.xml');
      // create the xpath object
      $xpath = new DOMXPath($doc);

      // select the nodes
      $nodes = $xpath->query($query);
    } catch (Exception $e) {
      fb($e);
    }
    // write the node to the return var
    foreach ($nodes as $node) {
      if($node->hasAttribute('name')) {
        fb($node->hasAttribute('name'), 'hat attribute name');
        // set the key of the array to the name attribute
        $key = $node->attributes->getNamedItem('name')->nodeValue;
        // add text content to the array
        $arr[$key] = $node->textContent;
      } else {
        $arr[] = $node->textContent;
      }
    }

    // sort array
    asort($arr);
    fb('return', $arr);
    // return array
    return $arr;
  }

}
