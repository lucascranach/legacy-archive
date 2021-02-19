/***************************************************************
*  Copyright notice
*
*  (c) 2012 Joerg Stahlmann <>
*  All rights reserved
*
*  This script is part of the cranach project. The Cranach Digital Archive (cda) is an interdisciplinary collaborative research resource, 
*	 providing access to art historical, technical and conservation information on paintings by Lucas Cranach (c.1472 - 1553) and his workshop. 
*	 The repository presently provides information on more than 400 paintings including c.5000 images and documents from 19 partner institutions.
*
***************************************************************/
 
 
/**
* jFilter is a javascript helper library for the Cranach Project
* The javascript functions are based on the jquery 1.6.2 lib.
* The function sets the checkmark of the filter checkboxes and handles
* their visibilty.
*
* @author	Joerg Stahlmann <>
* @package	elements/jScript 
*/

function jFilter() {
	
	this.getFilter = function(arr) {
		
		$.each(arr, function(key, value) { 
				if(value != 0) {
					$("input:checkbox[value="+value+"]").attr("checked", true);
					var parentElement = "#nav_"+$("input:checkbox[value="+value+"]").attr("index");
					$(parentElement).css("display", "block");
					var parentLi = $(parentElement).prev().parent();
					parentLi.addClass("current");
					var parentDiv = parentLi.closest("div");
					parentDiv.css("display", "block");
					parentDiv.parent().closest("li").addClass("current");
				}
		});
	
	}
}

function selectOverall(value) {
	
	// determine if checkbox is checked or not
	var check = $("input:checkbox[value="+value+"]").is(':checked');
	
	// run through all checkboxes in the div
	$("input:checkbox[index="+value+"]").attr("checked", check);
	// run next checkbox level
	$("input:checkbox[index="+value+"]").each(function(){
		// set next index
		var next = $(this).attr('value');
		// determine if checkbox is checked or not
		var check = $("input:checkbox[value="+next+"]").is(':checked');
		
		// run through all checkboxes in the div
		$("input:checkbox[index="+next+"]").attr("checked", check);
	});
	
	// submit
	document.formFilter.submit();	
	
}