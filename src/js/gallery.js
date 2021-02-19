
$.ajax({
    type: 'POST',
    url: 'src/ajax/getSearchFromSession.ajax.php',
    success: function(data) {
        $.main(data);
    },
    error: function() {
        // console.error('Bad search request', e);
    }
});

$.main = function(data) {
    // eslint-disable-next-line no-undef
    var fil = new Filter();
    // eslint-disable-next-line no-undef
    var thesau = new Thesaurus(lang, data.thesaurus);
    var page = (typeof ($.cookie('page')) !== 'undefined') ? parseInt($.cookie('page'), 10) : 1;

    $.removeCookie('mntModul', { expires: 7, path: '/', domain: '.lucascranach.org' });
    $('[data-toggle="tooltip"]').tooltip();

    if (data.attr.length > 0) {
        fil.getFilter(data.attr);
    }
    if (data.date.length > 0) {
        fil.getFilter(data.date);
    }
    if (data.tech.length > 0) {
        fil.getFilter(data.tech);
    }
    if (data.collection.length > 0) {
        fil.getFilter(data.collection);
    }

    var h = window.innerHeight;
    var maxImages;

    var x = parseInt($('#content').width() / 153, 10);
    var y = parseInt(h / 153, 10);

    maxImages = (x * y);

    if (parseInt($.cookie('maxImages'), 10) !== maxImages && page < parseInt($.cookie('lastPage'), 10)) {
        // set max images
        $.cookie('maxImages', maxImages, { expires: 7, path: '/', domain: '.lucascranach.org' });
        // reload page
        self.location.href = window.location.pathname;
    }
};

$.leClick = function(value) {
    var doc = document.getElementById(value);
    var node = doc.parentNode;

    if (doc.style.display === 'none') {
        doc.style.display = 'block';
        node.setAttribute('class', 'current');
        document.cookie = value + '=block';
    } else {
        doc.style.display = 'none';
        node.setAttribute('class', 'closed');
        document.cookie = value + '=none';
    }
};

$.advancedSearch = function(value) {
    if (value) {
        $('search').hide();
        $('.advancedLink').hide();
        $('.advancedSearch').show();
        document.cookie = 'cdaAdvancedSearch=true';
        $('.resetFilter').css('top', 238);
    } else {
        $('.advancedLink').show();
        $('.advancedSearch').hide();
        document.cookie = 'cdaAdvancedSearch=false';
        $('.resetFilter').css('top', 94);
    }
};

/**
 * Set current selected language
 *
 * @returns {string} language
 */
$.setLanguage = function() {
    var lang = ($.cookie('lang') === 'Deutsch') ? 'Englisch' : 'Deutsch';
    $.cookie('lang', lang, { expires: 7, path: '/', domain: '.lucascranach.org' });
    location.reload();
    return lang;
};
