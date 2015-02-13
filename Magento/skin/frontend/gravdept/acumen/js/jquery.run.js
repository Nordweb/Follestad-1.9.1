/**
 * Acumen for Magento
 * http://gravitydept.com/to/acumen-magento
 *
 * @author     Brendan Falkowski
 * @package    gravdept_acumen
 * @copyright  Copyright 2011 Gravity Department http://gravitydept.com
 * @license    All rights reserved.
 * @version    1.3.2
 */


/* avoid PrototypeJS conflicts, assign jQuery to $jQ instead of $ */
var $jQ = jQuery.noConflict();

/* Create 'gravdept' namespace */
var gravdept = {};

/**
 * Use $jQ(document).ready() because Magento executes Prototype inline
 * and breaks if jQuery executes beforehand. Use function($) to maintain
 * normal jQuery syntax inside
 */
$jQ(document).ready(function ($) {
    var categoryCount = 8,
        headerBar = $('div#header-bar'),
        headerWidth = headerBar.width(),
        categoryWidth = Math.round(headerWidth / categoryCount),
        subCatBoxHeight = 'auto',
        isHideAfterSlideUp = false,
        categoriesStatus = [];

    $('#nav li.level0').each(function () {
        categoriesStatus.push({
            catId: -1,
            expanded: false
        }) //categoryStatus
    });
    $('#nav .menu').each(function (menuIdx) {
        var categories = $(this).find('li.level1');
        var categoriesSize = categories.length;
        var realCategoryWidth = 120;
        var categoriesAtLevel = Math.floor(headerWidth / realCategoryWidth);
        var levels = ((categoriesSize % categoriesAtLevel) == 0)
            ? categoriesSize / categoriesAtLevel
            : Math.floor(categoriesSize / categoriesAtLevel) + 1;
        var extraCatsCount = categoriesAtLevel - (categoriesSize - categoriesAtLevel * (levels - 1));
        /*extra border block at last level*/
        var last = $(this).find('li.level1:last');
        for (var e = 0; e < (extraCatsCount - 1); e++) {
            last.after('<li class="level1 border-block"></li>');
        }

        /*find indeces of categories with subcats to show level as [short] or [wide]*/
        categories = categories = $(this).find('li.level1');
        var wideLevels = [];
        categories.each(function (idx) {
            if ($(this).find('ul').length > 0) { // if there are sub categoties
                for (var level = 1; level <= levels; level++) { // go through bounds of levels
                    if ((idx + 1) <= categoriesSize) {
                        var leftBound = categoriesAtLevel * (level - 1);
                        leftBound = (level == 1) ? leftBound : leftBound + 1;
                        var rightBound = categoriesAtLevel * level;
                        if (leftBound <= (idx + 1) && (idx + 1) <= rightBound) {
                            if (wideLevels.indexOf(level) == -1) { // check  is level was widened
                                wideLevels.push(level);
                                var sliceLeft = (level == 1) ? leftBound : leftBound - 1;
                                categories.slice(sliceLeft, rightBound).css({
                                    'height': subCatBoxHeight
                                });
                            }
                        }
                    }
                }
            }
        });

        categories.each(function (idx) {
            var isLastInLevel = false;
            for (var level = 1; level < levels; level++) {
                isLastInLevel = ((idx + 1) % categoriesAtLevel * level == 0 );
                if (isLastInLevel) break;
            }

            var borderStyle = ' 1px dashed #909090';
            if (isLastInLevel) {
                $(this).css('border-right', 'none');
            } else {
                $(this).css('border-right', borderStyle);
            }

            $(this).width(realCategoryWidth);
            var hiddenSubCats = $(this).find('li.level2:gt(2)');
            hiddenSubCats.hide();

            var allCategories = $(this).parent().parent(); // all categories of current menu
            if (hiddenSubCats.length > 0) {
                var expandedCategories = [],
                    heightBeforeExpand = 0;

                var showAllSubCategories = function () {
                    var categoryClsParts = $(this).parent().attr('class').split(' ')[1].split('-');
                    var navId = parseInt(categoryClsParts[1]);
                    var catId = parseInt(categoryClsParts[2]);
                    if (!categoriesStatus[navId - 1].expanded) {
                        if (categoriesStatus[navId - 1].catId != catId && categoriesStatus[navId - 1].catId != -1) {
                            return;
                        }
                        categoriesStatus[navId - 1] = {
                            expanded: true,
                            catId: catId
                        };

                        var opValue = '0.4';
                        allCategories.find('li.level1').not(':eq(' + idx + ')').css({
                            'opacity': opValue
                        }).each(function () {
                                $(this).attr('onclick', 'return false;');
                            });

                        allCategories.find('li.level1:eq(' + (idx) + ')').css({
                            'border-right': 'none',
                            'opacity': '1'
                        });
                        if (!isLastInLevel) {
                            allCategories.find('li.level1:eq(' + (idx + 1) + ')').css({
                                'border-left': borderStyle,
                                'opacity': opValue
                            });
                        }

                        var selCategory = $(this).parent();

                        heightBeforeExpand = selCategory.height();
                        var pos = idx;
                        while (pos >= categoriesAtLevel) {
                            pos -= categoriesAtLevel;
                        }

                        var leftBound = null, rigthBound = null;
                        var zeroBasedCatsAtLevel = categoriesAtLevel - 1,
                            catsAtRight = null,
                            catsAtLeft = null;
                        if (pos < zeroBasedCatsAtLevel) {
                            if (pos != 0) {
                                catsAtRight = (zeroBasedCatsAtLevel - pos);
                                catsAtLeft = zeroBasedCatsAtLevel - catsAtRight;
                            } else {
                                catsAtRight = zeroBasedCatsAtLevel;
                                catsAtLeft = 0;
                            }
                        } else if (pos == zeroBasedCatsAtLevel) {
                            catsAtRight = 0;
                            catsAtLeft = zeroBasedCatsAtLevel;
                        }

                        leftBound = idx - catsAtLeft;
                        rigthBound = idx + catsAtRight;
                        expandedCategories = categories.slice(leftBound, rigthBound + 1);
                        $(this).text('Vis mindre');
                        hiddenSubCats.show();
                        hiddenSubCats.last().show(function () {
                            selCategory.css('height', '100%');
                            expandedCategories.css('height', selCategory.height());

                        });
                    } else {
                        $(this).trigger('click'); //initiate hideAllSubCategories call when user click trigger link in inactive mode
                    }
                }
                var hideAllSubCategories = function () {
                    var categoryClsParts = $(this).parent().attr('class').split(' ')[1].split('-');
                    var navId = parseInt(categoryClsParts[1]);
                    var catId = parseInt(categoryClsParts[2]);
                    if (categoriesStatus[navId - 1].catId == catId) {
                        categoriesStatus[navId - 1] = {
                            expanded: false,
                            catId: -1
                        }
                    } else {
                        return;
                    }
                    var opValue = '1';
                    allCategories.find('li.level1[visibility!=visible]').css({
                        'opacity': opValue
                    }).each(function () {
                            $(this).removeAttr('onclick');
                        });
                    allCategories.find('li.level1:eq(' + (idx) + ')').css({
                        'border-right': (isLastInLevel) ? 'none' : borderStyle,
                        'background-color': '#FFFFFF',
                        'opacity': '1'
                    });
                    if (!isLastInLevel) {
                        allCategories.find('li.level1:eq(' + (idx + 1) + ')').css({
                            'border-left': 'none',
                            'background-color': '#FFFFFF',
                            'opacity': opValue
                        });
                    }
                    /*If user clicks view all link to slide up the category we must handle event
                     to prevent menu closing */
                    var selCategory = $(this).parent();
                    hiddenSubCats.hide();
                    hiddenSubCats.last().hide(function () {
                        isHideAfterSlideUp = true;
                        selCategory.css('height', subCatBoxHeight);
                        expandedCategories.css('height', heightBeforeExpand);
                    });
                    $(this).text('Vis mer');
                }
                $(this).find('.view-all').toggle(showAllSubCategories, hideAllSubCategories);
            }
        });

        //set similar width as header width to the shown menu
        if ($(this).find('ul.level1:first').is('ul')) {
            //   $(this).width(headerWidth);
        }
    });

    $('#nav .menu').css({
        visibility: 'visible',
        display: 'none'
    });

    /*change active category while cursor is moving horizontally at nav menu*/
    $('#nav li.level0.active').find('span:first').css('color', '#f48b22')
    /*
     $('#nav li.level0').hover(function(){
     //   $(this).find('span:first').after('<div class="arrow-up"></div>');
     var offsetLeft = 0;//$('#nav').offset().left - $(this).offset().left
     var menu = $(this).find('div.menu');
     menu.css({
     'left': offsetLeft,
     'top': '40px'
     });
     $(this).parent().find('div.menu').each(function() {
     if ($(this).css('display') == "block") {
     $(this).css('display', 'none');
     }
     });
     menu.show();
     },function(){
     //   $(this).find('div.arrow-up').remove();
     if (!isHideAfterSlideUp) {
     $(this).find('div.menu:first').hide();
     } else {
     isHideAfterSlideUp = false;
     }
     $('#nav li.level0.active').find('span:first').css('color', '#f48b22');
     })
     */

    /*    $jQ('body').bind('click', function () {
     $jQ('#nav li.level0').parent().find('div.menu').each(function () {
     if ($(this).css('display') == "block") {
     $(this).slideUp('fast');
     }
     });
     });
     */
    /*
     $jQ('#nav li.level0').click(function () {
     $jQ(this).children('div.menu').slideToggle();
     });
     */
    $jQ('#nav li.level0 > a').click(function (event) {
        console.log($jQ(this).parent().find('div.menu').length);
        if ($jQ(this).parent().find('div.menu').length) {
            event.preventDefault();
        }
    });
    $jQ('#nav li.level0 ').find('div.menu > a').click(function () {
        console.log('sub-link clicked');
    });

    $jQ('#nav li.level0').bind('click', function (event) {

        var selectedChild = $jQ(this).find('div.menu');
        var prevState = selectedChild.css('display');
        $jQ(this).parent().find('div.menu').each(function () {
            if ($jQ(this).css('display') == "block") {
                $jQ(this).slideUp('fast');
            }

        });
        if (prevState == 'none') {
            selectedChild.slideDown('fast');
        }

    });

    // ------------------------------------
    // Progressive enhancement hook
    // ------------------------------------

    $('html')
        .removeClass('no-js')
        .addClass('js');


    // ------------------------------------
    // ColorBox
    // ------------------------------------

    if ($().colorbox) {

        gravdept['zoom'] = $('a.zoom');

        if (gravdept['zoom'].length) {
            gravdept['zoom'].colorbox({
                initialHeight: '200px',
                initialWidth: '200px',
                opacity: 0.75,
                speed: 350
            });
        }

    }


    // ------------------------------------
    // Slides JS
    // ------------------------------------

    if ($().slides) {

        /* Grid Slider */

        gravdept['gridSlider'] = $('.grid-slider');

        if (gravdept['gridSlider'].length) {
            gravdept['gridSlider'].slides({
                container: 'slides-container',
                paginationClass: 'slides-pagination',
                play: 4000,
                slideSpeed: 400
            });
        }

        /* Product Slider (Catalog & New Products) */

        gravdept['productSlider'] = $('.product-slider:first');

        if (gravdept['productSlider'].length) {
            gravdept['productSlider'].slides({
                autoHeight: true,
                container: 'slides-container',
                generatePagination: false,
                slideSpeed: 400
            });

        }


        gravdept['productSliderNew'] = $('.product-slider-new:first');

        if (gravdept['productSliderNew'].length) {
            gravdept['productSliderNew'].slides({
                autoHeight: true,
                container: 'slides-container',
                generatePagination: false,
                slideSpeed: 400
            });
        }


        gravdept['productSliderMostViewed'] = $('.product-slider-mostviewed:first');

        if (gravdept['productSliderMostViewed'].length) {
            gravdept['productSliderMostViewed'].slides({
                autoHeight: true,
                container: 'slides-container',
                generatePagination: false,
                slideSpeed: 400
            });
        }

        gravdept['productSliderBestseller'] = $('.product-slider-bestseller:first');

        if (gravdept['productSliderBestseller'].length) {
            gravdept['productSliderBestseller'].slides({
                autoHeight: true,
                container: 'slides-container',
                generatePagination: false,
                slideSpeed: 400
            });
        }

        /* Promo Slider */

        gravdept['promoSlider'] = $('.promo-slider');

        if (gravdept['promoSlider'].length) {
            gravdept['promoSlider'].slides({
                container: 'slides-container',
                paginationClass: 'slides-pagination',
                play: 4000,
                pause: 4000,
                slideSpeed: 400
            });
        }

        /* Thumb Slider */

        gravdept['thumbSlider'] = $('.thumb-slider');

        if (gravdept['thumbSlider'].length) {
            gravdept['thumbSlider'].slides({
                container: 'slides-container',
                generatePagination: false,
                paginationClass: 'slides-pagination',
                play: 4000,
                pause: 4000,
                slideSpeed: 400
            });
        }

    }


    // ------------------------------------
    // Toggle Catalog Options
    // ------------------------------------

    gravdept['optionsButton'] = $('#options-button');

    if (gravdept['optionsButton'].length) {
        gravdept['optionsButton'].click(function () {
            var button = $(this);
            var toolbar = $('#options-bar');

            if (button.hasClass('open')) {
                $(this)
                    .removeClass('open')
                    .find('.label').html('Vis alternativer');
                toolbar.slideUp();
            } else {
                $(this)
                    .addClass('open')
                    .find('.label').html('Skjul alternativer');
                toolbar.slideDown();
            }
        });
    }

    var newsletterProcess = false;
    $jQ('form#newsletter-form').submit(function () {
        if (newsletterProcess) {
            return false;
        }
        $jQ('form#newsletter-form #advice-validate-email-newsletter-email').hide();

        var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

        var ePost = $jQ('form#newsletter-form input#newsletter-email').val();
        var isValid = true;
        if (reg.test(ePost) == false || ePost == 'navn@eksempel.com') {
            isValid = false;
            $jQ('form#newsletter-form #advice-validate-email-newsletter-email').html('Fyll inn en gyldig e-postadresse. For eksempel ola@domain.com.').show();
        }

        if (!isValid) {
            return false;
        }

        var dataString = $jQ('form#newsletter-form').serialize();
        dataString.pb_ajax_hack = 'true';

        newsletterProcess = true;

        $jQ.ajax({
            type: "POST",
            url: "/index.php",
            data: dataString,
            success: function (msg) {
                newsletterProcess = false;
                $jQ('form#newsletter-form #advice-validate-email-newsletter-email').html('Takk for din p&aring;melding!').show();
            },
            error: function (errormessage) {
                newsletterProcess = false;
                $jQ('form#newsletter-form #advice-validate-email-newsletter-email').html('Feil!').show();
            }
        });

        return false;
    });


});  // END $jQ(document).ready()