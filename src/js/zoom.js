// The *full* image path on the server. This path does *not* need to be in the web
// server root directory. On Windows, use Unix style forward slash paths without
// the "c:" prefix

// Create our viewer object
// See documentation for more details of options
$.loadViewer = function(div, image) {
    // The iipsrv server path (/fcgi-bin/iipsrv.fcgi by default)
    var server = '/iipsrv/iipsrv.fcgi';
    var credit = '&copy; CRANACH DIGITAL ARCHIVE';

    $('#' + div).empty();

    // eslint-disable-next-line no-undef
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

    return iipmooviewer;
};

$.urlParam = function(name, url) {
    var result;
    var arr = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);

    if (arr !== null) result = arr[1];
    return result || 0;
};

$.setImage = function(url, thumb) {
    var object = {
        dir: $.urlParam('obj', url),
        category: $.urlParam('fol', url),
        image: $.urlParam('img', url),
        remarks: $.urlParam('remarks', url)
    };

    var category = '';
    var imageName = object.image.split('.');
    // set current cookies
    $.cookie('current_image', imageName[0], { expires: 7, path: '/', domain: '.lucascranach.org' });
    $.cookie('category', object.category, { expires: 7, path: '/', domain: '.lucascranach.org' });
    $.cookie('remarks', object.remarks, { expires: 7, path: '/', domain: '.lucascranach.org' });

    /**
    * Checks if RKD string exists.
    * Note: Algorithm has to search for the
    * thumbnail direcotry, because the iip file
    * lays out of root directory
    */
    if (object.remarks === 'RKD') {
        var remarks = (thumb.search('_RKD/11_RKD') >= 0) ? '_RKD/11_' + object.remarks : '/11_' + object.remarks;
        category = remarks + '/' + object.category;
    } else {
        category = '/' + object.category;
    }

    var image = '/var/www/iipimages/' + object.dir
    + category
    + '/pyramid'
    + '/' + object.image;

    return image;
};

$(document).on('click', '.zoom-object', function(e) {
    e.preventDefault();
    var url = $(this).attr('href');
    var thumb = $(this).children().attr('src');
    var image = $.setImage(url, thumb);
    $.loadViewer('targetframe', image);
});
