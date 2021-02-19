
/**
 * jUser is a javascript helper library for the Cranach Project
 * The javascript functions are based on the jquery lib
 *
 * Copyright 2016, Joerg Stahlmann
 **/
$(function() {
    $('#collapseUserArea').on('hidden.bs.collapse', function() {
        $(this).hide();
    });

    $(document).on('click', 'div.add-compare', function() {
        var url = $(this).prev().attr('href');
        var thumb = $(this).prev().children().attr('src');

        var object = {
            dir: $.urlParam('obj', url),
            category: $.urlParam('fol', url),
            image: $.urlParam('img', url),
            remarks: $.urlParam('remarks', url),
            thumb: thumb
        };
        $.addZoomObject(object);
    });

    $(document).on('click', '.remove-compare', function() {
        var storedObjects;
        var object = $(this).parent();
        if (object.hasClass('compare-left')) {
            $('.compare-left').empty();
            $('.compare-left').prepend('<img class="img-responsive img-thumbnail" src="images/compare_1.jpg">');
            storedObjects = JSON.parse(localStorage.getItem('object')) || {};
            storedObjects.left = {};
            localStorage.setItem('object', JSON.stringify(storedObjects));
        } else if (object.hasClass('compare-right')) {
            $('.compare-right').empty();
            $('.compare-right').prepend('<img class="img-responsive img-thumbnail" src="images/compare_2.jpg">');
            storedObjects = JSON.parse(localStorage.getItem('object')) || {};
            storedObjects.right = {};
            localStorage.setItem('object', JSON.stringify(storedObjects));
        }
        $('.compare-objects').hide();
    });

    $(document).on('click', '.delete-history', function() {
        var dir = 'src/ajax/rmSessions.ajax.php';
        $.ajax({
            type: 'POST',
            url: dir,
            data: {data: 'history'},
            success: function(data) {
                if (data) location.reload();
            }
        });
    });

    $(document).on('click', '.delete-compare', function() {
        localStorage.clear();

        var dir = 'src/ajax/rmSessions.ajax.php';
        $.ajax({
            type: 'POST',
            url: dir,
            data: {data: 'compare'},
            success: function(data) {
                if (data) location.reload();
            }
        });
    });

    // when scroll
    $(window).scroll(function() {
        if (window.pageYOffset > 1) {
            $('.user-row').css('margin-top', '-51px');
        } else {
            $('.user-row').css('margin-top', '0px');
        }
    });

    /**
    * Workaround for scroallable collapse area
    */
    $(document).on('hidden.bs.collapse', function() {
        $('.user-row').css('z-index', 0);
        $('.compare-box').css('z-index', 0);
        $('.compare-left').css('z-index', 0);
        $('.compare-right').css('z-index', 0);
    });
    $(document).on('show.bs.collapse', function() {
        $('.user-row').css('z-index', 1);
        $('.compare-box').css('z-index', 2);
        $('.compare-left').css('z-index', 2);
        $('.compare-right').css('z-index', 2);
    });

    var savedObjects = JSON.parse(localStorage.getItem('savedObjects')) || {};
    if ($.isEmptyObject(savedObjects)) {
        $('.droptarget').addClass('highlight');
    }
    /**
    * Add prev images saved for compare to
    * compare display
    */
    var storedObjects = JSON.parse(localStorage.getItem('object')) || {};
    var content = '';

    if (!$.isEmptyObject(storedObjects.left)) {
        content = '<img class="img-responsive img-thumbnail" src="' + storedObjects.left.thumb + '">'
        + '<span class="glyphicon glyphicon-remove-sign remove-compare remove-compare-left" aria-hidden="true"></span>';
    } else {
        content = '<img class="img-responsive img-thumbnail" src="images/compare_1.jpg">';
    }
    $('.compare-left').prepend(content);

    content = '';
    if (!$.isEmptyObject(storedObjects.right)) {
        content = '<img class="img-responsive img-thumbnail" src="' + storedObjects.right.thumb + '">'
        + '<span class="glyphicon glyphicon-remove-sign remove-compare remove-compare-right" aria-hidden="true"></span>';
    } else {
        content = '<img class="img-responsive img-thumbnail" src="images/compare_2.jpg">';
    }

    $('.compare-right').prepend(content);

    if (!$.isEmptyObject(storedObjects.left) && !$.isEmptyObject(storedObjects.right)) {
        $('.compare-objects').show();
    }
});

$.urlParam = function(name, url) {
    var result;
    var arr = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);

    if (arr !== null) result = arr[1];
    return result || 0;
};

$.addZoomObject = function(object) {
    var storedObjects = JSON.parse(localStorage.getItem('object')) || {};

    if ($.isEmptyObject(storedObjects.left)) {
        storedObjects.left = object;
        localStorage.setItem('object', JSON.stringify(storedObjects));
        var content = '<img class="img-responsive img-thumbnail" src="' + object.thumb + '">'
        + '<span class="glyphicon glyphicon-remove-sign remove-compare remove-compare-left" aria-hidden="true"></span>';
        $('.compare-left').empty();
        $('.compare-left').prepend(content);

        if (!$.isEmptyObject(storedObjects.right)) {
            $('.compare-objects').show();
        }
    } else {
        storedObjects.right = object;
        localStorage.setItem('object', JSON.stringify(storedObjects));
        content = '<img class="img-responsive img-thumbnail" src="' + object.thumb + '">'
        + '<span class="glyphicon glyphicon-remove-sign  remove-compare remove-compare-right" aria-hidden="true"></span>';
        $('.compare-right').empty();
        $('.compare-right').prepend(content);

        if (!$.isEmptyObject(storedObjects.left)) {
            $('.compare-objects').show();
        }
    }
};

// eslint-disable-next-line no-unused-vars
function drag(e) {
    var url = (typeof e.target.href === 'undefined') ? e.target.parentNode.href : e.target.href;
    e.dataTransfer.setData('Text', url);
    $('#collapseUserArea').collapse('show');
    $('a[href="#tab2default"]').tab('show');
    $('.droptarget').addClass('highlight');
}

// eslint-disable-next-line no-unused-vars
function allowDrop(e) {
    e.preventDefault();
}

// eslint-disable-next-line no-unused-vars
function dragend(e) {
    e.preventDefault();
    $('.droptarget').removeClass('highlight');
}

// eslint-disable-next-line no-unused-vars
function drop(e) {
    e.preventDefault();
    $('.droptarget').removeClass('highlight');

    var data = {};
    data.url = e.dataTransfer.getData('Text');
    var dir = 'src/ajax/getObject.ajax.php';

    $.ajax({
        type: 'POST',
        url: dir,
        data: {url: data.url},
        success: function(result) {
            try {
                var obj = JSON.parse(result);
                var add = ($.cookie('lang') === 'Deutsch') ? 'hinzufügen' : 'add';
                var fileType = ($.cookie('lang') === 'Deutsch') ? obj['file-type-de'] : obj['file-type-en'];
                var imageDesc = ($.cookie('lang') === 'Deutsch') ? obj['image-description-de'] : obj['image-description-en'];

                var content = '<div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 compare-box">';
                content += '<div class="col-lg-4 col-md-5 col-sm-6 col-xs-4 margin-bot-small">';
                content += '<a href="' + obj.url + '">'
                + '<img class="img-responsive img-thumbnail" src="' + obj.thumb + '">'
                + '</a>';
                content += '<div class="add-compare text-center"><span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>&nbsp;' + add + '</div>';
                content += '</div>';
                content += '<div class="col-lg-8 col-md-7 col-sm-6 col-xs-8">';
                content += '<dt>' + obj.title + '</dt>';
                content += '<dd><i>' + obj.name + '</i></dd>';
                content += '<dd>' + fileType + '</dd>';
                content += '<dd>' + imageDesc + '</dd>';
                content += '</div>';
                content += '</div>';

                $('#tab2default').prepend(content);


                localStorage.setItem('savedObjects', JSON.stringify(obj));
            } catch (err) {
                // console.log(err);
            }
        }
    });
}
