jQuery(document).ready(function () {
    /* Adding one height for all blocks */
    var max = -1,
        addHeight = jQuery(".most_viewed ul.products-grid h3, .product-grid td");
    addHeight.each(function () {
        var h = jQuery(this).height();
        max = h > max ? h : max;
    });
    addHeight.css("min-height", max);

    /* Fixing responsive slider control height */
    jQuery(window).resize(function () {
        jQuery(".slides_control").css("height", jQuery(".slides_control div").height());
    });

    /* Checking each navigation menu on children count and adding styles */
    var nav = jQuery("#nav");
    var loop = nav.children().length;

    for (var i = 0; i <= loop; i++) {
        if (nav.find(".nav-" + i + " .menu .level0").children().length > 15) {
            nav.find(".nav-" + i).css("position", "static");
            nav.find(".nav-" + i + " .menu").css({"width":"930px", "left":"0"});
        }
    }
});