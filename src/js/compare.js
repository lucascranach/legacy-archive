// The iipsrv server path (/fcgi-bin/iipsrv.fcgi by default)
var server = '/iipsrv/iipsrv.fcgi';

// The *full* image path on the server. This path does *not* need to be in the web
// server root directory. On Windows, use Unix style forward slash paths without
// the "c:" prefix

// Create our viewer object
// See documentation for more details of options
$.loadViewer = function (div, image) {
  $('#' + div).empty();
  var iipmooviewer = new IIPMooViewer( div, {
    image: image,
    server: server,
    credit: credit,
    scale: 0,
    showNavWindow: true,
    showNavButtons: true,
    winResize: true,
    protocol: 'iip'
  });
}

// Copyright or information message
var credit = '&copy; CRANACH DIGITAL ARCHIVE';

/**
 * Checks if RKD string exists.
 * Note: Algorithm has to search for the 
 * thumbnail direcotry, because the iip file
 * lays out of root directory
 */
$.getCategory= function (object) {
  var category;
  if (object.remarks == 'RKD') {
    var remarks = (object.thumb.search('_RKD/11_RKD') >= 0) ? '_RKD/11_' + object.remarks : '/11_' + object.remarks;
    category = remarks + '/' + object.category;
  } else {
    category = '/' + object.category;
  }
  return category;
}

$(document).on('click', '.compare-objects', function(e) {
  e.preventDefault();
  $('#compare-modal').modal('show');
  /**
   * Add prev images saved for compare to iip
   */
  var storedObjects = JSON.parse(localStorage.getItem("object")) || {};
  var images = {};

  if(!$.isEmptyObject(storedObjects.left)) {
    images.left = '/var/www/iipimages/' + storedObjects.left.dir
    + $.getCategory(storedObjects.left)
    + '/pyramid'
    + '/' +storedObjects.left.image;

    $.loadViewer("targetframe-left", images.left);
  }

  if(!$.isEmptyObject(storedObjects.right)) {
    images.right = '/var/www/iipimages/' + storedObjects.right.dir
    + $.getCategory(storedObjects.right)
    + '/pyramid'
    + '/' +storedObjects.right.image;

    $.loadViewer("targetframe-right", images.right);
  }
});
