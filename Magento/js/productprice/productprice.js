(function(Klarna){
    "use strict";

    var K = Klarna,
        Class = Klarna.KClass,
        _ = Klarna._,
        KE = Klarna.use('Event'),
        qwery = Klarna.use('qwery'),
        bean = Klarna.use('bean'),
        Terms = Klarna.use('Terms'),
        LetOp = Klarna.use('LetOp'),
        urls  = Klarna.use('urls'),
        util = Klarna.use('util'),
        self = Klarna.use('Product');

    var $ = function (el) {
        if (_.isString(el)) {
            return document.getElementById(el);
        }
        return el;
    }

    Klarna.include(
        urls['static'] + 'external/kitt/toc/v1.1/js/klarna.terms.min.js'
    )

    /**
     * Product Price Widget
     */
    K.extend(self, {

        PPBox: Class.extend({
            init: function(options) {
                this.options = options;
                this.box = $(options.box);
                this.dropDownVisible = false;
                this.dropDown = qwery('.klarna_PPBox_bottomMid', this.box)[0];
                this.pullDown = qwery('#klarna_PPBox_pullDown', this.box)[0];
                this.pullUp = qwery('#klarna_PPBox_pullUp', this.box)[0];
            },

            setup: function() {
                var me = this;

                bean.add(
                    this.box, '.klarna_PPBox_pull, .klarna_PPBox_top', 'click',
                    _.bind(this.toggleDropDown, this),
                    qwery
                );

                util.waitForEntity(
                    Terms, 'Account',
                    _.bind(this.createTerms, this)
                )

                if (this.options.country == 'nl') {
                    this.letOp();
                } else {
                    this.display();
                }
            },

            letOp: function() {
                var me = this;

                Klarna.include(
                    urls['static'] + 'external/kitt/letop/v1.0/js/klarna.letop.js'
                )
                util.waitForEntity(LetOp, 'Banner', function() {
                    me.banner = new LetOp.Banner({
                        advert: me.box,
                        container: qwery('.bannerhook', me.box)[0],
                        eid: me.options.eid
                    });
                });
            },

            createTerms: function()
            {
                this.terms = new Klarna.Terms.Account({
                    el: qwery('.klarna_PPBox_bottomMid_readMore', this.box)[0],
                    eid: this.options.eid,
                    country: this.options.country
                })
            },

            toggleDropDown: function() {
                this.dropDownVisible = !this.dropDownVisible;
                this.dropDown.style.display = this.dropDownVisible ? 'block' : 'none';
                this.pullDown.style.display = !this.dropDownVisible ? 'block' : 'none';
            },

            display: function() {
                this.box.style.display = 'block';
            },

            hide: function() {
                this.box.style.display = 'none';
            }
        })
    });
})(Klarna);
