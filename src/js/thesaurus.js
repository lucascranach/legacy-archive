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


/**
 * jThesaurus is a javascript helper library for the Cranach Project
 * The javascript functions are based on the jquery 1.6.2 lib.
 * The function generates a thesaurus with keywords for the cranach objects.
 * Keywords are delivered by a xml document.
 *
 * @author	Joerg Stahlmann <>
 * @package	js
 */
var html=[];
var flag = false;
var language;
var arr = Array();
var entities = Array();
var i = 0;

html.push('<input type="hidden" name="thesau[]" index="thesau" value="0">');	

function jThesaurus(lang, tArr) {
  // set array
  arr = tArr;
  // set language
  language = lang;
  // get xml nodes
  $.ajax({
    type: "GET",
    url: "src/xml/ThesXMLExport.xml",
    dataType: "xml",
    success: parseXml
  });
}


function auswahl(value) {
  // determine if checkbox is checked or not
  var check = $("input:checkbox[value="+value+"]").is(':checked');
  // run through all checkboxes in the div
  $('#'+value).find(':checkbox').each(function(){
    // set checked to true or false
    $(this).attr('checked', check);
  });

  // submit
  document.formFilter.submit();	

}




function traverse(node){
  // label of the checkbox:
  var label = "";

  if($(this).attr('type')=='Descriptor') {

    // get british equivalent of node
    var en_name = $(this).children("alt-term")[0].getAttribute("term");

    // get id of node
    var node_id = $(this).children("alt-term")[1].getAttribute("term");



    // Set to the right label depending on the chosen language
    if(language == "Englisch") {
      label = en_name;
    } else {
      label = $(this).attr('term');
    }

    // run through nodes
    if( $(this).children("term").length>0 && node_id <= 104) {
      i++;
      html.push('<li class="closed">'
          +'<a href="javascript:leClick(\''+node_id+'\')" name="node_id" style="padding:0 0 0 20px; margin:5px 0px 0px 0px;">'
          +label
          +'</a>');
      html.push('<div id="'+node_id+'" style="display:none">');
    } else if( $(this).children("term").length>0) {
      i++;
      html.push('<li class="closed">'
          +'<a href="javascript:leClick(\''+node_id+'\')" name="node_id" style="padding:0 0 0 14px; margin:0px;">'
          +'<input type="checkbox" class="subCb" onClick="auswahl(\''+node_id+'\')" name="thesau[]" value="'+node_id+'" id="cb'+node_id+'">'+label
          +'</a>');
      html.push('<div id="'+node_id+'" style="display:none">');
    } else {
      html.push('<li><input type="checkbox" onchange="javascript:document.formFilter.submit();" name="thesau[]" value="'+node_id+'" id="'+node_id+'">'+label);
    }
    flag = true;
  }

  if(flag) {
    if( $(this).children("term").length>0 ){
      html.push('<ul>');
      $(this).children().each(traverse);
      html.push('</ul>');
    }else{
      html.push('</li>'); 
    }
  } else {
    $(this).children().each(traverse);
  }
}

function getElements(node) {


}


function parseXml(xml) {
  $(xml).children().each(traverse);
  $('#menu').append(html.join(""));

  $.each(arr, function(key, value) { 
    if(value != 0) {

      $("input:checkbox[value="+value+"]").attr("checked", true);

      while(value.length > 4) {
        value = value.substring(0, value.length-2);
        var parentElement = "#"+value+"";
        $(parentElement).css("display", "block");
        var parentLi = $(parentElement).prev().parent();
        parentLi.addClass("current");
      }
    }

  });
}
