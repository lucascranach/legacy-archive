$(document).ready(function() {
    var $xml;
    var hideContent;
    var showMoreContent;
    var lang;

    // bugfix and workaround for mootools collapse
    $('#infoNavigation').on('hidden.bs.collapse', function() {
        $(this).hide();
    });

    // Load the xml file using ajax
    $.ajax({
        type: 'GET',
        url: 'src/xml/locallang/locallang_object.xml',
        dataType: 'xml',
        success: function(xml) {
            $.init($(xml));
        }
    });

    $.init = function(xml) {
        if ($.detectIE()) {
            // alert('You are using Internet Explorer.');
        }

        $xml = xml;
        lang = ($.cookie('lang')) ? $.cookie('lang') : 'Englisch';
        hideContent = $xml.find('languageKey[index="' + lang + '"]').find('label[index="hide"]').text();
        showMoreContent = $xml.find('languageKey[index="' + lang + '"]').find('label[index="show"]').text();

        /**
        * Initial Data View -
        * sets all panel to active with identifaction
        * as class and handles show and hide routine for
        * data tables.^
        */
        var prevPanel = $.cookie('panel');

        if (typeof prevPanel !== 'undefined') {
            var item = $('#' + prevPanel);
            $.navigate(item);
        } else {
            $('.identification').each(function() {
                $(this).removeClass('panel-inactive').addClass('panel-active');
                var modul = $(this).attr('index');
                $.modul_view(modul);
            });
            // Initial historical view
            $('.art_historical_information').each(function() {
                $(this).removeClass('panel-inactive').addClass('panel-active');
                var modul = $(this).attr('index');
                $.modul_view(modul);
            });
            // Initial examination view
            $('.material_technique').each(function() {
                $(this).removeClass('panel-inactive').addClass('panel-active');
                $('.restModul').each(function() {
                    var modul = $(this).attr('index');
                    $.modul_view(modul);
                });
            });
            // Initial examination view
            $('.conservation_restoration').each(function() {
                $(this).removeClass('panel-inactive').addClass('panel-active');
                $('.restModul').each(function() {
                    var modul = $(this).attr('index');
                    $.modul_view(modul);
                });
            });
        }

        // contact form
        $.contact($xml);
    };

    // ----------------------------------------------
    // navigation
    $.navigate = function(item) {
        var navItem = item;
        var index = navItem.attr('index');
        var parent = navItem.attr('parent');
        $.cookie('panel', index, { expires: 7, path: '/', domain: '.lucascranach.org' });

        // remove class selected in nav item
        $('.nav-item').each(function() {
            $(this).removeClass('selected');
        });

        navItem.addClass('selected');
        var panel = $('.' + index );

        // set all panel data to invisible
        $('.panel-data').each(function() {
            $(this).removeClass('panel-active').addClass('panel-inactive');
        });

        panel.removeClass('panel-inactive').addClass('panel-active');

        // set breadcrumb
        var parentCrumb;
        var home = $xml.find('languageKey[index="' + lang + '"]').find('label[index="view_all"]').text();
        var crumb = $xml.find('languageKey[index="' + lang + '"]').find('label[index="' + index + '"]').text();
        if (typeof parent !== 'undefined') {
            parentCrumb = $xml.find('languageKey[index="' + lang + '"]').find('label[index="' + parent + '"]').text();
        }
        setBreadcrumb(home, crumb, parentCrumb, index, parent);
    };

    /**
    * Model view.
    * @param {string} modul to be shown or hidden.
    * @returns {object} object.
    */
    $.modul_view = function(modul) {
        // switch cookie for initial view
        if (typeof modul !== 'undefined') {
            var view = ($.cookie(modul) === 'shown') ? 'hidden' : 'shown';
            $.cookie(modul, view, { expires: 7, path: '/', domain: '.lucascranach.org' });

            // run specific modul view function
            if ($('.' + modul).find('table tr').length) {
                modulContent(modul);
            } else {
                restModul(modul);
            }
        }

        return;
    };

    /**
    *
    * @param {string} xml xml content
    * @return {Object} contact form
    */
    $.contact = function() {
        // ----------------------------------------------
        // binding onclick to navi
        $('.message-send').click(function(e) {
            e.preventDefault();

            var isValid = true;

            $('.has-error').each(function() {
                $(this).removeClass('has-error');
            });

            var sender = $('#message-sender').val();
            var email = $('#message-email').val();
            var subject = $('#message-subject').val();
            var message = $('#message-body').val();

            // eslint-disable-next-line no-undef
            var isHuman = grecaptcha.getResponse();

            if (!sender) {
                $('#message-sender-grp').addClass('has-error');
                isValid = false;
            }
            if (!subject) {
                $('#message-subject-grp').addClass('has-error');
                isValid = false;
            }
            if (!message) {
                $('#message-body-grp').addClass('has-error');
                isValid = false;
            }
            if (!isHuman) {
                isValid = false;
            }
            if (validateEmail(email) === false) {
                $('#message-email-grp').addClass('has-error');
                isValid = false;
            }

            var objectId = ($.cookie('objectId')) ? $.cookie('objectId') : '';

            if (isValid) {
                var obj = {
                    objectId: objectId,
                    sender: sender,
                    email: email,
                    subject: subject,
                    message: message
                };

                $.ajax({
                    type: 'POST',
                    url: 'src/ajax/contact.ajax.php',
                    data: {send: JSON.stringify(obj)},
                    success: function(result) {
                        var success = $xml.find('languageKey[index="' + lang + '"]').find('label[index="send_success"]').text();
                        if (result === 'true') {
                            $('#contact-modal .modal-body').empty();
                            var html = '<div class="row">'
                            + '<div class="col-xs-12">'
                            + '<p class="text-center">'
                            + '<span style="font-size:70px; color:lightgreen;" class="glyphicon glyphicon-ok" aria-hidden="true"></span>'
                            + '</p>'
                            + '</div>'
                            + '</div>'
                            + '<div class="row">'
                            + '<div class="col-xs-12">'
                            + '<p style="font-size:30px" class="text-center">'
                            + success
                            + '</p>'
                            + '</div>'
                            + '</div>';
                            $('#contact-modal .modal-body').append(html);
                        }
                    }
                });
            }
        });

        return;
    };

    // ----------------------------------------------
    // binding onclick to navi
    $('.lang-btn').click(function(e) {
        setLanguage();
        e.preventDefault();
    });

    // ----------------------------------------------
    // binding onclick to navi
    $('.modul-content').click(function(e) {
        modulContent($(this).attr('id'));
        e.preventDefault();
    });

    // ----------------------------------------------
    // binding onclick to navi
    $('.rest-modul').click(function(e) {
        restModul($(this).attr('id'));
        e.preventDefault();
    });

    // ----------------------------------------------
    // binding onclick to navi
    $('.nav-item').click(function(e) {
        $.navigate($(this));
        e.preventDefault();
    });

    // ----------------------------------------------
    // binding onclick to liturature modul
    $('.lit_modul').click(function() {
        var row;

        if ($(this).hasClass('td-active')) {
            $(this).removeClass('td-active').addClass('td-inactive');
            row = $('#' + $(this).attr('index'));
            row.hide();
        } else {
            $(this).removeClass('td-inactive').addClass('td-active');
            row = $('#' + $(this).attr('index'));
            row.show();
        }
    });

    /**
    * Function handels show/hide routine of all tables
    * @param {string} modul type to be shown
    * @returns {object} object
    */
    var modulContent = function(modul) {
        if ($.cookie(modul) === 'shown') {
            $('.' + modul).find('table tr:gt(0)').hide();
            $('.' + modul).find('table tr:last').show();
            $.cookie(modul, 'hidden', { expires: 7, path: '/', domain: '.lucascranach.org' });
            $('.' + modul).find('a').text(showMoreContent);
        } else if ($.cookie(modul) === 'hidden') {
            $('.' + modul).find('table tr').show();
            $.cookie(modul, 'shown', { expires: 7, path: '/', domain: '.lucascranach.org' });
            $('.' + modul).find('a').text(hideContent);
        } else {
            $('.' + modul).find('table tr').show();
            $.cookie(modul, 'shown', { expires: 7, path: '/', domain: '.lucascranach.org' });
            $('.' + modul).find('a').text(hideContent);
        }

        return;
    };

    /**
    * Function handels show/hide routine of all tables
    * @param {string} modul type to be shown
    * @returns {object} object
    */
    var restModul = function(modul) {
        if ($.cookie(modul) === 'shown') {
            $('.' + modul).hide();
            $.cookie(modul, 'hidden', { expires: 7, path: '/', domain: '.lucascranach.org' });
            $('#' + modul).text(showMoreContent);
        } else if ($.cookie(modul) === 'hidden') {
            $('.' + modul).show();
            $.cookie(modul, 'shown', { expires: 7, path: '/', domain: '.lucascranach.org' });
            $('#' + modul).text(hideContent);
        } else {
            $('.' + modul).show();
            $.cookie(modul, 'shown', { expires: 7, path: '/', domain: '.lucascranach.org' });
            $('#' + modul).text(hideContent);
        }

        return;
    };

    /**
    * Set current selectedd language
    * @returns {object} object
    */
    var setLanguage = function() {
        lang = ($.cookie('lang') === 'Deutsch') ? 'Englisch' : 'Deutsch';
        $.cookie('lang', lang, { expires: 7, path: '/', domain: '.lucascranach.org' });
        location.reload();
    };

    /**
    * Set breadcrumb
    * @param {string} home directory
    * @param {string} crumb current selected
    * @param {string} parentCrumb parent directory
    * @param {string} index current index
    * @param {string} parent directory path
    * @returns {object} object
    */
    var setBreadcrumb = function(home, crumb, parentCrumb, index, parent) {
        $('.breadcrumb').empty();

        var backlink = $('<li><a href="">' + home + '</a></li>')
        .click(function(e) {
            e.preventDefault();
            deselectNavigation();
        });
        $('.breadcrumb').append(backlink);

        if (typeof parent !== 'undefined') {
            var breadcrumb = $('<li><a href="" >' + parentCrumb + '</a></li>')
            .click(function(e) {
                e.preventDefault();
                setNavigate(parent);
            });
            $('.breadcrumb').append(breadcrumb);
        }

        $('.breadcrumb').append('<li>' + crumb + '</li>');
        $('.breadcrumb').show();

        return;
    };

    /**
    * Set Navigation at prepare it for
    * navigate function
    *
    * @param {string} index item
    * @returns {object} object
    */
    var setNavigate = function(index) {
        var item = $('#' + index);
        $.navigate(item);

        return;
    };

    /**
    * Set Navigation unselected
    *
    * @returns {object} object
    */
    var deselectNavigation = function() {
        $.removeCookie('panel', { expires: 7, path: '/', domain: '.lucascranach.org' });
        window.location.reload();

        return;
    };

    var validateEmail = function(email) {
        var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    };

    /**
     * detect IE
     * returns version of IE or false, if browser is not Internet Explorer
     *
     * @returns {boolean} ie
     */
    $.detectIE = function() {
        var ua = window.navigator.userAgent;

        var msie = ua.indexOf('MSIE ');
        if (msie > 0) {
            // IE 10 or older => return version number
            return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
        }

        var trident = ua.indexOf('Trident/');
        if (trident > 0) {
            // IE 11 => return version number
            var rv = ua.indexOf('rv:');
            return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
        }

        var edge = ua.indexOf('Edge/');
        if (edge > 0) {
            // Edge (IE 12+) => return version number
            return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
        }

        // other browser
        return false;
    };
});
