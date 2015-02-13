(function(){
    "use strict";

    var K = Klarna,
        Class = Klarna.KClass,
        _ = Klarna._,
        KE = Klarna.use('Event'),
        qwery = Klarna.use('qwery'),
        bonzo = Klarna.use('bonzo'),
        urls  = Klarna.use('urls'),
        self = Klarna.use('Checkout');

    var $ = function (el) {
        if (_.isString(el)) {
            return document.getElementById(el);
        }
        return el;
    }

    Klarna.include(
        urls['static'] + 'external/kitt/toc/v1.1/js/klarna.terms.min.js'
    )

    K.extend(self, {

        Base: Class.extend({
            init: function(options) {
                this.options = options || {};
            },

            pnoUpdated: function (e) {
                var pno = e.value;
                if (!this.validatePno(pno)) {
                    this.addresses.hide();
                } else {
                    bonzo(e.parentNode).addClass('loading');
                    this.addresses.getAddresses(e.value);
                }
            },

            _pnoUpdated: function (event) {
                this.pnoUpdated(event.target);
            },

            validatePno: function (pno) {
                if (typeof pno == 'undefined')
                    return false;

                return pno.match(/^([1-9]{2})?[0-9]{6}[-\+]?[0-9]{4}$/)
            },

            addressesChanged: function (info) {
                bonzo(qwery('div.Klarna_pnoInputField', this.box))
                    .removeClass('loading');

                var comp = qwery('.company', this.box);
                if (info.company) {
                    bonzo(comp).show('block');
                } else {
                    bonzo(comp).hide();
                }
            },

            companyRadioChanged: function(event) {
                var el = event.target;
                var comp = qwery('.company', this.box)[0];
                var priv = qwery('.private', this.box)[0];
                var pno = qwery(
                    '.Klarna_pnoInputField .input_title', this.box)[0];

                if (el.value == 'company') {
                    comp.style.display = 'block';
                    priv.style.display = 'none';
                    pno.setAttribute('data-t-key', 'organisation_number');
                    pno.innerHTML = this.translator.translate('organisation_number');
                } else {
                    comp.style.display = 'none';
                    priv.style.display = 'block';
                    pno.setAttribute('data-t-key', 'person_number');
                    pno.innerHTML = this.translator.translate('person_number');
                }
            },

            setup: function() {
                var options = this.options;
                this.box = $(options.box);

                if (typeof options.collapse != "undefined") {
                    Klarna.Checkout.collapse(
                        this.box,
                        options.collapse.selector,
                        options.collapse.paymentCode
                    );
                }

                if (options.country == 'de') {
                    Klarna.include(urls['static'] + 'external/js/klarnaConsentNew.js')
                }

                // The translator
                this.translator = new Klarna.Translator({
                    'box': this.box,
                    'selector': '.klarna_translate',
                    'translations': Klarna.Checkout.translations,
                    'lang': options.language
                });

                // The translation selection widget
                this.translation = new Klarna.Checkout.Translation({
                    origLang: options.language,
                    translator: this.translator,
                    elem: qwery('.klarna_box_top .language', this.box)[0]
                });

                // The error message widget
                this.error = new Klarna.Checkout.Error({
                    box: qwery('.error_hook', this.box)[0],
                    event: 'checkout:' + options.payment_type + ':error',
                    type: options.payment_type
                });

                // The address selection widget
                this.addresses = new Klarna.Checkout.Addresses({
                    box: qwery('.klarna_box_bottom_address', this.box)[0],
                    country: options.country,
                    payment_type: options.payment_type,
                    ajax_path: options.ajax_path,
                    company_allowed: options.company_allowed,
                    lang: options.lang,
                    input_name: (options.params || {}).shipmentAddressInput,
                    error_event: 'checkout:' + options.payment_type + ':error'
                });

                KE.bind(
                    'checkout:changed:addresses',
                    _.bind(this.addressesChanged, this)
                );

                if (options.country == 'se') {
                    // refresh address widget on pno change
                    var pnofield = qwery('input.Klarna_pnoInputField', this.box)[0]
                    if (typeof pnofield != 'undefined') {

                        Klarna.bean.add(pnofield, 'keyup change blur focus',
                            _.bind(this._pnoUpdated, this));

                        this.pnoUpdated(pnofield);
                    }
                }

                // Company / Private radio buttons
                var invType = qwery('.invoice_type', this.box)[0];
                if (typeof invType != 'undefined') {
                    // using the click event and the label elements is only for crappy browsers
                    Klarna.bean.add(invType, 'input, label', 'change click',
                        _.bind(this.companyRadioChanged, this),
                        qwery
                    );
                }
                var comp = qwery('.company', this.box)
                if (comp && comp[0]) {
                    comp[0].style.display = 'none';
                }
            }
        })
    });
})();
(function(Klarna){
    "use strict";

    var _ = Klarna._;

    var $ = function (el) {
        if (_.isString(el)) {
            return document.getElementById(el);
        }
        return el;
    }

    Klarna.Class("Checkout.Translation", function(){
        return {
            init: function(options) {
                this.options = options || {};

                this.origLang = options.origLang;
                this.lang = options.lang;
                this.listVisible = false;

                this.elem = $(this.options.elem);
                if (!this.elem) {
                    throw new Error('Please provide an existing widget');
                }

                this.widget = Klarna.qwery('.klarna_box_top_flag', this.elem)[0];
                this.active = Klarna.qwery('.box_active_language', this.elem)[0];
                this.list = Klarna.qwery('.klarna_box_top_flag_list', this.elem)[0];

                this.widget.style.display = 'block';

                this.active.onclick = _.bind(this.toggleList, this);

                Klarna.bean.add(
                    this.list, 'img', 'click', _.bind(this.onListClick, this),
                    Klarna.qwery
                );
            },

            copyFlag: function(flag) {
                var aflag = Klarna.qwery('img', this.active)[0];
                aflag.alt = flag.alt;
                aflag.src = flag.src;
            },

            toggleList: function() {
                this.listVisible = !this.listVisible;
                this.list.style.display = (this.listVisible) ? 'block' : 'none';
            },

            onListClick: function(event) {
                this.toggleList();
                this.changeLanguage(event.target.getAttribute('alt'));
                this.copyFlag(event.target);
            },

            changeLanguage: function(lang) {
                this.options.translator.changeLanguage(lang)
                this.lang = lang;
                var tear = Klarna.qwery('.klarna_box_bottom_languageInfo', this.elem)[0];
                tear.style.display = (this.lang !== this.origLang) ? 'block' : 'none';
            }
        }
    }());
})(Klarna);
(function(Klarna){
    "use strict";

    var _ = Klarna._;

    Klarna.Class('Translator', function () {
        return {
            init: function (options) {
                this.options = options || {};
                _.defaults(this.options, {
                    'box': document.body,
                    'selector': 'klarna_translate',
                    'keyattrib': 'data-t-key',
                    'language': null
                });

                this.t = this.options.translations || {};
            },

            translate: function (key, lang) {
                return (this.t[key] || {})[lang || this.options.lang];
            },

            changeLanguage: function (lang) {
                var labels = Klarna.qwery(this.options.selector, this.options.box);
                var me = this;
                _.each(
                    labels,
                    function (elem) {
                        var key = elem.getAttribute(me.options.keyattrib)
                        var t = me.translate(key, lang)
                        if (typeof t != 'undefined') {
                            elem.innerHTML = t;
                        }
                    }
                );
                this.options.lang = lang;
            },

            getLanguage: function () {
                return this.options.lang;
            }
        }
    }());
})(Klarna);
(function(Klarna){
    "use strict";

    var _ = Klarna._,
        KE = Klarna.use('Event');

    var sngltempl = '<input type="hidden" name="{{input_name}}" value="{{address.key}}">' +
        '{{#address}}<p>{{company_name}}' +
        '{{^company_name}}{{first_name}} {{last_name}}{{/company_name}}</p>' +
        '<p>{{street}}</p><p>{{zip}} {{city}}</p><p>{{country_code}}</p>{{/address}}';

    var multitempl = '<select name="{{input_name}}">' +
        '{{#addresses}}<option value="{{key}}">{{company_name}}' +
        '{{^company_name}}{{first_name}} {{last_name}}{{/company_name}}' +
        ', {{street}}, {{zip}} {{city}}, {{country_code}}{{/addresses}}';

    var $ = function (el) {
        if (_.isString(el)) {
            return document.getElementById(el);
        }
        return el;
    }

    /**
     * Get Addresses Widget
     */
    Klarna.Class("Checkout.Addresses", {
        init: function(options) {
            this.options = options;
            this.error = false;
            this.box = $(options.box);
            this.busy = false;
        },

        showCompanyNotice: function () {
            this.error = true;

            KE.trigger(this.options.error_event, {
                'type': 'notice',
                'message': this.options.lang.companyNotAllowed
            })
        },

        /**
         * Get the address(es) for the given pno by calling the
         * store proxy of get_addresses
         */
        getAddresses: function(pno) {
            if (this.busy) {
                return
            }
            var me = this;
            Klarna.Ajax({
                method: 'get',
                type: 'json',
                url: this.options.ajax_path,
                data: {
                    action: 'getAddress',
                    type: this.options.payment_type,
                    country: this.options.country,
                    pno: pno
                },
                success: function (resp) {
                    me.busy = false;
                    if (resp.error) {
                        me.update({})
                        me.error = true;
                        KE.trigger(me.options.error_event, resp.error);
                    } else {
                        me.update(resp)
                    }
                },
                error: function (resp) {
                    me.busy = false;
                    me.update({})

                    resp = JSON.parse(resp.responseText);
                    me.error = true;
                    KE.trigger(me.options.error_event, resp.error);
                }
            })

            this.busy = true;
        },

        update: function(data) {
            if (data.length > 0) {
                var cont = Klarna.qwery('.klarna_box_bottom_address_content', this.box)[0]
                if (data.length > 1) {
                    cont.innerHTML = Klarna.Mustache.to_html(multitempl, {
                        addresses: data,
                        input_name: this.options.input_name
                    })
                } else {
                    cont.innerHTML = Klarna.Mustache.to_html(sngltempl, {
                        address: data[0],
                        input_name: this.options.input_name
                    })
                }
                this.display();
            } else {
                this.hide()
            }

            var company = false;
            for (var i = 0; i < data.length; i++) {
                if (data[i].company_name) {
                    company = true;
                    break;
                }
            }

            KE.trigger('checkout:changed:addresses', {
                'company': company,
                'addresses': data
            });

            if (!this.options.company_allowed) {
                if (company) {
                    this.showCompanyNotice()
                    return;
                }
            }

            if (this.error) {
                // Clear errors
                this.error = false;
                KE.trigger(this.options.error_event, null)
            }

        },

        display: function() {
            this.box.style.display = 'block';
        },

        hide: function() {
            this.box.style.display = 'none';
        }
    });
})(Klarna);
(function(Klarna){
    "use strict";

    var _ = Klarna._,
        KE = Klarna.use('Event');

    var $ = function (el) {
        if (_.isString(el)) {
            return document.getElementById(el);
        }
        return el;
    }

    var baloontmpl =
        '<div class="klarna_error {{type}}">' +
        '<div class="message"><span>{{message}}</span></div>' +
        '</div>';

    Klarna.Class("Checkout.Error", {
        init: function(options) {
            this.options = options;
            this.box = $(options.box);

            KE.bind(this.options.event, _.bind(this._displayError, this));
        },

        _displayError: function(data) {
            if (data === null) {
                this.clear();
            } else {
                this.box.innerHTML = Klarna.Mustache.to_html(baloontmpl, data);
            }
        },

        clear: function() {
            this.box.innerHTML = '';
        }
    });
})(Klarna);
(function(Klarna){
    "use strict";

    var K = Klarna,
        _ = Klarna._,
        Checkout = Klarna.use('Checkout'),
        qwery = Klarna.use('qwery'),
        util = Klarna.use('util'),
        Terms = Klarna.use('Terms');

    K.extend(Checkout, {
        Invoice: Checkout.Base.extend({
            init: function(options) {
                options.payment_type = 'invoice';
                if (typeof options.company_allowed == 'undefined') {
                    options.company_allowed = true;
                }
                this._super(options);
                this.setup();

                util.waitForEntity(
                    Terms, 'Invoice',
                    _.bind(this.createTerms, this)
                )
            },

            createTerms: function() {
                this.terms = new Terms.Invoice({
                    el: qwery('.klarna_box_top_agreement', this.box)[0],
                    eid: this.options.eid,
                    country: this.options.country,
                    charge: this.options.charge
                });
            }
        })
    });
})(Klarna);
(function(Klarna){
    "use strict";

    var K = Klarna,
        KE = Klarna.use('Event'),
        _ = Klarna._,
        Checkout = Klarna.use('Checkout'),
        qwery = Klarna.use('qwery'),
        LetOp = Klarna.use('LetOp'),
        Terms = Klarna.use('Terms'),
        util  = Klarna.use('util'),
        urls  = Klarna.use('urls');

    K.extend(Checkout, {
        Part: Checkout.Base.extend({
            init: function(options) {
                options.payment_type = 'part';
                this._super(options);
                this.setup();

                util.waitForEntity(
                    Terms, 'Account',
                    _.bind(this.createTerms, this)
                );

                if (this.options.country == 'nl') {
                    this.letOp();
                }
            },

            createTerms: function() {
                this.terms = new Terms.Account({
                    el: qwery('.klarna_box_top_agreement', this.box)[0],
                    eid: this.options.eid,
                    country: this.options.country
                });
            },

            letOp: function() {
                var me = this;

                Klarna.include(
                    urls['static'] + 'external/kitt/letop/v1.0/js/klarna.letop.js'
                )
                util.waitForEntity(LetOp, 'Banner', function() {
                    me.banner = new LetOp.Banner({
                        advert: qwery('.paymentPlan', me.box)[0],
                        replace: qwery('.nlbanner', me.box)[0],
                        eid: me.options.eid,
                        width: 212
                    });
                })
            }
        })
    });
})(Klarna);
(function(Klarna){
    "use strict";

    /**
     * Local member variables.
     */
    var K = Klarna,
        _ = Klarna._,
        qwery = Klarna.use('qwery'),
        self = Klarna.use('Checkout'),
        bean = Klarna.use('bean'),
        bonzo = Klarna.use('bonzo'),
        KE = Klarna.use('Event'),
        hasrun = false,
        boxes = {},
        selector = "",
        options = [];


    /**
     * This insanity is for virtuemart. Needs to be extended when it is time
     * for it to be upgraded.
     *
     * @param {element} DOM element
     *
     * @return string
     */
    function getUsableValue (element) {
        return element.value;
    }

    /**
     * set style display of provided element to none
     *
     * @param {box} element to hide
     *
     * @return void
     */
    function hide (box) {
        if (_.isElement(box)) {
            bonzo(qwery('.klarna_box_top_right, .klarna_box_bottom', box)).hide();
       }
    }

    /**
     * set style display of provided element to block
     *
     * @param {box} element to show
     *
     * @return void
     */
    function show (box) {
        if (_.isElement(box)) {
            bonzo(qwery('.klarna_box_top_right, .klarna_box_bottom', box)).show();
        }
    }

    /**
     * check all payment methods. Hide boxes that are unselected.
     *
     * @return void
     */
    function hideNonSelected() {
        options = qwery(selector);

        // Get all the selectors that actually match an input element
        var inputs = _.filter(options, function(o) {
            return o.tagName == 'INPUT';
        });

        // If we only have one input element, we don't want to hide it
        if (inputs.length == 1) {
            return;
        }

        // Get the selected inputfields value
        _.each(inputs, function(o){
            if (o.checked) {
               selected = getUsableValue(o);
               return;
            }
        });

        // Hide the non-selected boxes.
        for (var method in boxes) {
            if (method != selected) {
                hide(boxes[method]);
            }
        }

        // Make sure the selected box is visible.
        show(boxes[selected]);
    }

    /**
     * Optional to use collapse functionality.
     *
     * @param {box}         payment box element
     * @param {sel}         selector string to listen for clicks on
     * @param {paymentCode} code to match the box with, id or value string.
     *
     * @throws Error if the selector string is being changed
     *
     * @return void
     */
    K.extend (self, {
        collapse: function ( box, sel, paymentCode ) {
            if (selector == "") {
                selector = sel;
            } else if (selector != sel) {
                throw new Error('Selector can not be changed once set.');
            }

            // Map box element with paymentCode for that element.
            boxes[paymentCode] = box;

            // Make the small box clickable aswell.
            bean.add(box, ".klarna_box_top", 'click',
                function() {
                    _.chain(qwery(sel))
                        .filter(function(o) {
                            return o.tagName == 'INPUT';
                        })
                        .each(function(o) {
                            if (getUsableValue(o) == paymentCode) {
                                o.checked = 'checked';
                            }
                        });
                    hideNonSelected();
                },
                qwery
            );

            // make sure we only run once
            if (hasrun != false) {
                return;
            }
            hasrun = true;

            // // Add an event when the page is loaded
            KE.bind('ready', hideNonSelected);

            // Listener for payment method selection
            bean.add(document.body, selector, 'click',
                hideNonSelected,
                qwery
            );
        }
    });
})(Klarna);
(function(Klarna){
    "use strict";

    var K = Klarna,
        _ = Klarna._,
        Checkout = Klarna.use('Checkout'),
        qwery = Klarna.use('qwery'),
        LetOp = Klarna.use('LetOp'),
        Terms = Klarna.use('Terms'),
        util  = Klarna.use('util'),
        urls  = Klarna.use('urls');

    K.extend(Checkout, {
        Special: Checkout.Base.extend({
            init: function(options) {
                options.payment_type = 'special';
                if (typeof options.company_allowed == 'undefined') {
                    options.company_allowed = false;
                }
                this._super(options);
                this.setup();

                util.waitForEntity(
                    Terms, 'Special',
                    _.bind(this.createTerms, this)
                );

                if (this.options.country == 'nl') {
                    this.letOp();
                }
            },

            createTerms: function() {
                this.terms = new Terms.Special({
                    el: qwery('.klarna_box_top_agreement', this.box)[0],
                    eid: this.options.eid,
                    country: this.options.country,
                    charge: this.options.charge
                });
            },

            letOp: function() {
                var me = this;

                Klarna.include(
                    urls['static'] + 'external/kitt/letop/v1.0/js/klarna.letop.js'
                )
                util.waitForEntity(LetOp, 'Banner', function() {
                    me.banner = new LetOp.Banner({
                        advert: qwery('.paymentPlan', me.box)[0],
                        replace: qwery('.nlbanner', me.box)[0],
                        eid: me.options.eid,
                        width: 212
                    });
                })
            }
        })
    });
})(Klarna);
(function(data){Klarna.namespace('Checkout.translations',data)})({"street_adress":{"en":"Street address","sv":"Gatunamn","nl":"Straat","de":"Stra\u00dfe","nb":"Gate\/vei","da":"Gade","fi":"Katuosoite"},"error_klarna_title":{"en":"Klarna Invoice Error","sv":"Klarna Faktura fel","nl":"Klarna Factuur fout","de":"Klarna Rechnung Fehler","nb":"Klarna Faktura-feil","da":"Klarna Faktura Fejl","fi":"Klarna Lasku  virhe"},"error_save_inputvalue":{"en":"Could not save the details you send.","sv":"Det gick inte att spara de skickade v\u00e4rdena.","nl":"De gegevens konden niet worden opgeslagen.","de":"Die Angaben konnten nicht gespeichert werden.","nb":"Verdiene kunne ikke lagres.","da":"Kunne ikke gemme de sendte oplysninger.","fi":"L\u00e4hett\u00e4mi\u00e4si tietoja ei voitu tallentaa."},"error_currency":{"en":"Invalid currency set","sv":"Vald valuta \u00e4r ogiltig","nl":"Ongeldige valuta gekozen","de":"Ung\u00fcltige W\u00e4hrung","nb":"Ugyldig valuta","da":"Den valgte valuta er ugyldig","fi":"Virheellinen valuutta"},"error_title_2":{"en":"Please check your personal details.","sv":"Var v\u00e4nlig kontrollera dina personuppgifter.","nl":"Controleert u a.u.b. uw persoonlijke gegevens.","de":"Bitte \u00fcberpr\u00fcfen Sie Ihre pers\u00f6nlichen Daten.","nb":"Vennligst kontroller dine personlige opplysninger.","da":"Kontroll\u00e9r venligst dine personoplysninger.","fi":"Yst\u00e4v\u00e4llisesti tarkista henkil\u00f6kohtaiset tietosi."},"error_title_1":{"en":"Sorry, but we could not verify the following information:","sv":"Tyv\u00e4rr, vi kunde inte verifiera f\u00f6ljande information:","nl":"Het spijt ons maar we konden onderstaande gegevens niet controleren:","de":"Die folgende Information konnte nicht verifiziert werden:","nb":"Vi kan desverre ikke verifisere f\u00f8lgende informasjon:","da":"Vi kan desv\u00e6rre ikke verificere f\u00f8lgende information:","fi":"Valitettavasti emme voineet vahvistaa seuraavia tietoja:"},"error_no_address":{"en":"No address found","sv":"Inga adresser hittades","nl":"Geen adres gevonden","de":"Keine Adresse gefunden","nb":"Ingen adresser ble funnet","da":"Ingen fundne adresser","fi":"Osoitetta ei l\u00f6ytynyt"},"error_shipping_must_match_billing":{"en":"Your shipping address must be the same as your billing address.","sv":"Leveransadressen m\u00e5ste vara samma som fakturaadressen.","nl":"Het afleveradres moet hetzelfde zijn als uw factuuradres.","de":"Ihre Lieferadresse muss mit Ihrer Rechnungsadresse \u00fcbereinstimmen.","nb":"Din leveringsadresse m\u00e5 v\u00e6re den samme som faktureringsadressen.","da":"Din leveringsadresse skal v\u00e6re den samme som din faktura adresse.","fi":"Toimitusosoitteen on oltava sama kuin laskutusosoite."},"email_address":{"en":"Email","sv":"Epost","nl":"E-mail","de":"E-Mail","nb":"E-post","da":"E-mail","fi":"S\u00e4hk\u00f6postiosoite"},"notice_email_address":{"en":"Enter Email","sv":"Fyll i epost","nl":"Vult u a.u.b. uw e-mailadres in.","de":"E-Mail Adresse angeben","nb":"Fyll inn e-post","da":"Indtast E-mail","fi":"Sy\u00f6t\u00e4 s\u00e4hk\u00f6postiosoite"},"klarna_personalOrOrganisatio_number":{"en":"Personal or Company number","sv":"Persnr \/ Orgnr","nl":"Geboortedatum \/ KvK-nummer","de":"Geburtsdatum \/ Handelsregnr","nb":"Personnummer \/ Org.nr.","da":"CPR-nr \/ CVR-nr","fi":"Henkil\u00f6tunnus\/ Y-tunnus"},"klarna_module_testmode":{"en":"(TESTMODE)","sv":"(TESTMODE)","nl":"(TESTMODUS)","de":"(TESTMODUS)","nb":"(TESTMODUS)","da":"(TEST-TILSTAND)","fi":"(TESTITILA)"},"klarna_campaign_agreement":{"en":"Campaign terms","sv":"Villkor f\u00f6r kampanj","nl":"Voorwaarden voor Campaign","de":"Aktionsbedingungen","nb":"Kampanjevilk\u00e5r","da":"Kampagne vilk\u00e5r","fi":"Kampanjaehdot"},"klarna_account_agreement":{"en":"Terms and Conditions for Klarna Account","sv":"Allm\u00e4nna villkor f\u00f6r Klarna Konto","nl":"Voorwaarden voor Klarna Account","de":"Bedingungen f\u00fcr Klarna Ratenkauf","nb":"Delbetalingsvilk\u00e5r","da":"Vilk\u00e5r for Klarna Konto","fi":"Klarna Tilin ehdot"},"klarna_invoice_agreement":{"en":"Invoice terms","sv":"Fakturavillkor","nl":"Voorwaarden voor Klarna Factuur","de":"Rechnungsbedingungen","nb":"Fakturavilk\u00e5r","da":"Vilk\u00e5r for faktura","fi":"Laskuehdot"},"img_logo_account":{"en":"Klarna Account","sv":"Klarna Konto","nl":"Klarna  Account","de":"Klarna  Ratenkauf","nb":"Klarna  Konto","da":"Klarna  Konto","fi":"Klarna  Tili"},"first_name":{"en":"First name","sv":"F\u00f6rnamn","nl":"Voornaam","de":"Vorname","nb":"Fornavn","da":"Fornavn","fi":"Etunimi"},"notice_firstName":{"en":"Your first name","sv":"Ange ditt f\u00f6rnamn h\u00e4r","nl":"Vult u a.u.b. hier uw voornaam in.","de":"Bitte geben Sie Ihren Vornamen an.","nb":"Fyll inn fornavn her","da":"Indtast dit fornavn","fi":"Kirjaa t\u00e4h\u00e4n etunimesi"},"last_name":{"en":"Last name","sv":"Efternamn","nl":"Achternaam","de":"Nachname","nb":"Etternavn","da":"Efternavn","fi":"Sukunimi"},"notice_lastName":{"en":"Enter your surname here","sv":"Ange ditt efternamn h\u00e4r","nl":"Vult u a.ub. hier uw achternaam in.","de":"Bitte geben Sie Ihren Nachnamen an.","nb":"Fyll inn etternavn her","da":"Indtast dit efternavn","fi":"Kirjaa t\u00e4h\u00e4n sukunimesi"},"sex":{"en":"Gender","sv":"K\u00f6n","nl":"Geslacht","de":"Geschlecht","nb":"Kj\u00f8nn","da":"K\u00f8n","fi":"Sukupuoli"},"sex_male":{"en":"Male","sv":"Man","nl":"Man","de":"Mann","nb":"Mann","da":"Mand","fi":"Mies"},"sex_female":{"en":"Female","sv":"Kvinna","nl":"Vrouw","de":"Frau","nb":"Kvinne","da":"Kvinde","fi":"Nainen"},"address_street":{"en":"Street","sv":"Gata","nl":"Straat","de":"Stra\u00dfe","nb":"Adresse","da":"Adresse","fi":"Kadunnimi"},"notice_streetaddress":{"en":"Please note that delivery can only take place to the registered address when paying with Klarna","sv":"Observera att leverans endast kan ske till folkbokf\u00f6ringsadress vid betalning med Klarna","nl":"Levering bij betaling via Klarna kan alleen plaatsvinden op het adres waar u officieel geregistreerd staat.","de":"Bitte beachten Sie, dass die Lieferung bei einer Zahlung mit Klarna ausschlie\u00dflich an Ihre Meldeadresse erfolgen kann.","nb":"V\u00e6r oppmerksom p\u00e5 at varelevering kun kan gj\u00f8res til din folkeregistrerte adresse n\u00e5r du velger Klarna som betalingsm\u00e5te.","da":"V\u00e6r venlig, at specificere din folkeregister adresse","fi":"Huomaa, ett\u00e4 Klarnan maksutavoilla maksaessasi tilaus toimitetaan ainoastaan v\u00e4est\u00f6rekister\u00f6ityyn osoitteeseen"},"address_homenumber":{"en":"Housenumber","sv":"Husnummer","nl":"Huisnummer","de":"Hausnummer","nb":"Husnummer","da":"Husnummer","fi":"Talonumero"},"notice_housenumber":{"en":"Please submit your house number.","sv":"V\u00e4nligen ange ert husnummer.","nl":"Vult u a.u.b. hier uw huisnummer in.","de":"Bitte geben Sie Ihre Hausnummer an.","nb":"Vennligst fyll inn husnummer.","da":"Indtast venligst dit husnummer.","fi":"Kirjaa t\u00e4h\u00e4n talonumerosi."},"address_zip":{"en":"Zip code","sv":"Postnummer","nl":"Postcode","de":"Postleitzahl","nb":"Postnummer","da":"Postnummer","fi":"Postinumero"},"notice_zip":{"en":"Please submit your zip code.","sv":"V\u00e4ngligen ange ert postnummer.","nl":"Vult u a.u.b. hier uw postcode in.","de":"Bitte geben Sie Ihre Postleitzahl an.","nb":"Vennligst fyll inn postnummer.","da":"Indtast venligst dit postnummer","fi":"Kirjaa t\u00e4h\u00e4n postinumerosi"},"address_city":{"en":"City","sv":"Stad","nl":"Woonplaats","de":"Wohnort","nb":"Sted","da":"By","fi":"Kaupunki"},"notice_city":{"en":"Please submit your city.","sv":"V\u00e4ngligen ange er ort.","nl":"Vult u a.u.b. hier uw woonplaats in.","de":"Bitte geben Sie Ihren Wohnort an.","nb":"Vennligst fyll inn stedsnavn.","da":"Indtast venligst bynavn","fi":"Kirjaa t\u00e4h\u00e4n kaupunki"},"birthday":{"en":"Birthday","sv":"F\u00f6delsedag","nl":"Geboortedatum","de":"Geburtsdatum","nb":"Bursdag","da":"F\u00f8dselsdag","fi":"Syntym\u00e4p\u00e4iv\u00e4"},"date_day":{"en":"Day","sv":"Dag","nl":"Dag","de":"Tag","nb":"Dag","da":"Dag","fi":"P\u00e4iv\u00e4"},"date_month":{"en":"Month","sv":"M\u00e5nad","nl":"Maand","de":"Monat","nb":"M\u00e5ned","da":"M\u00e5ned","fi":"Kuukausi"},"month_1":{"en":"January","sv":"januari","nl":"januari","de":"Januar","nb":"Januar","da":"Januar","fi":"Tammikuu"},"month_2":{"en":"February","sv":"februari","nl":"februari","de":"Februar","nb":"Februar","da":"Februar","fi":"Helmikuu"},"month_3":{"en":"March","sv":"mars","nl":"maart","de":"M\u00e4rz","nb":"Mars","da":"Marts","fi":"Maaliskuu"},"month_4":{"en":"April","sv":"april","nl":"april","de":"April","nb":"April","da":"April","fi":"Huhtikuu"},"month_5":{"en":"May","sv":"maj","nl":"mei","de":"Mai","nb":"Mai","da":"Maj","fi":"Toukokuu"},"month_6":{"en":"June","sv":"juni","nl":"juni","de":"Juni","nb":"Juni","da":"Juni","fi":"Kes\u00e4kuu"},"month_7":{"en":"July","sv":"juli","nl":"juli","de":"Juli","nb":"Juli","da":"Juli","fi":"Hein\u00e4kuu"},"month_8":{"en":"August","sv":"augusti","nl":"augustus","de":"August","nb":"August","da":"August","fi":"Elokuu"},"month_9":{"en":"September","sv":"september","nl":"september","de":"September","nb":"September","da":"September","fi":"Syyskuu"},"month_10":{"en":"October","sv":"oktober","nl":"oktober","de":"Oktober","nb":"Oktober","da":"Oktober","fi":"Lokakuu"},"month_11":{"en":"November","sv":"november","nl":"november","de":"November","nb":"November","da":"November","fi":"Marraskuu"},"month_12":{"en":"December","sv":"december","nl":"december","de":"Dezember","nb":"Desember","da":"December","fi":"Joulukuu"},"date_year":{"en":"Year","sv":"\u00c5r","nl":"Jaar","de":"Jahr","nb":"\u00c5r","da":"\u00c5r","fi":"Vuosi"},"person_number":{"en":"Person number:","sv":"Personnummer:","nl":"Persoonsnummer:","de":"Sozialversicherungsnummer:","nb":"Personnummer:","da":"Personnummer:","fi":"Henkil\u00f6tunnus:"},"organisation_number":{"en":"Organisation number:","sv":"Organisationsnummer:","nl":"KvK-nummer:","de":"Handelsregisternummer:","nb":"Organisasjonsnummer:","da":"CVR-nr:","fi":"Yritystunnus:"},"invoice_type":{"en":"Type","sv":"Typ","nl":"Soort","de":"Typ","nb":"Type","da":"Type","fi":"Tyyppi"},"invoice_type_private":{"en":"Private person","sv":"Privatperson","nl":"Priv\u00e9persoon","de":"Privatperson","nb":"Privatperson","da":"Privat","fi":"Yksityishenkil\u00f6"},"invoice_type_company":{"en":"Company","sv":"F\u00f6retag","nl":"Bedrijf","de":"Firma","nb":"Bedrift","da":"Erhverv","fi":"Yritys"},"notice_socialNumber_dk":{"en":"Please enter your social security number here according to the following example: DDMMYYNNNN","sv":"Ange ditt personnummer h\u00e4r enligt f\u00f6ljande s\u00e4tt: DDMM\u00c5\u00c5NNNN.","nl":"Vult u a.u.b. uw persoonsnummer als volgt in: DDMMJJNNNN.","de":"Bitte geben Sie Ihre Sozialversicherungnummer an: TTMMJJNNNN.","nb":"Vennligst fyll inn personnummer i f\u00f8lgende format DDMM\u00c5\u00c5NNNN.","da":"Indtast dit personnummer i overensstemmelse med f\u00f8lgende eksempel: DDMM\u00c5\u00c5NNNN.","fi":"Ole hyv\u00e4 ja anna henkil\u00f6tunnuksesi seuraavassa muodossa: PPKKVVNNNN "},"notice_socialNumber_no":{"en":"Please enter your social security number here according to the following example: DDMMYY-NNNNN","sv":"Ange ditt personnummer h\u00e4r enligt f\u00f6ljande s\u00e4tt: DDMM\u00c5\u00c5-NNNNN.","nl":"Vult u a.u.b. uw persoonsnummer als volgt in: DDMMJJ-NNNNN.","de":"Bitte geben Sie Ihre Sozialversicherungnummer an: TTMMYY-NNNNN.","nb":"Vennligst fyll inn personnummer i f\u00f8lgende format DDMMYY-NNNNN.","da":"Indtast dit personnummer i overensstemmelse med f\u00f8lgende eksempel: DDMM\u00c5\u00c5-NNNNN.","fi":"Ole hyv\u00e4 ja anna henkil\u00f6tunnuksesi seuraavassa muodossa: PPKKVV-NNNNN "},"notice_socialNumber_se":{"en":"Please enter your social security number here according to the following example: YYMMDD-NNNN","sv":"Ange ditt person- \/ orgnummer h\u00e4r enligt f\u00f6ljande s\u00e4tt: \u00c5\u00c5MMDD-NNNN.","nl":"Vult u a.u.b. uw persoonsnummer als volgt in: JJMMDD-NNNN.","de":"Bitte geben Sie Ihre Sozialversicherungnummer an: YYMMTT-NNNN.","nb":"Vennligst fyll inn personnummer i f\u00f8lgende format YYMMDDCNNNN.","da":"Indtast dit personnummer i overensstemmelse med f\u00f8lgende eksempel: \u00c5\u00c5MMDD-NNNN.","fi":"Ole hyv\u00e4 ja anna henkil\u00f6tunnuksesi seuraavassa muodossa: VVKKPP-NNNN "},"notice_socialNumber_fi":{"en":"Please enter your social security number here according to the following example: DDMMYY-NNNN","sv":"Ange ditt personnummer h\u00e4r enligt f\u00f6ljande s\u00e4tt: DDMM\u00c5\u00c5-NNNN.","nl":"Vult u a.u.b. uw persoonsnummer als volgt in: DDMMJJ-NNNN.","de":"Bitte geben Sie Ihre Sozialversicherungnummer an: TTMMYY-NNNN.","nb":"Vennligst fyll inn personnummer i f\u00f8lgende format DDMMYY-NNNN.","da":"Indtast dit personnummer i overensstemmelse med f\u00f8lgende eksempel: DDMM\u00c5\u00c5-NNNN.","fi":"Ole hyv\u00e4 ja anna henkil\u00f6tunnuksesi seuraavassa muodossa: PPKKVV-NNNN "},"notice_socialNumber_part_se":{"en":"Please enter your social security number here.","sv":"Ange ditt personnummer h\u00e4r. OBS! V\u00e4nligen observera att du inte kan handla som f\u00f6retag vid delbetalning.","nl":"Vult u a.u.b. hier uw persoonsnummer in. NB: Als bedrijf kunt u niet met Klarna Account betalen.","de":"Bitte geben Sie Ihre Sozialversicherungsnummer hier ein. Bitte beachten Sie, dass Sie als Firma nicht mit Klarna Ratenkauf zahlen k\u00f6nnen","nb":"Vennligst fyll inn ditt personnummer her. MERK: V\u00e6r oppmerksom p\u00e5 at du ikke kan benytte delbetaling dersom du handler som bedrift. ","da":"Indtast dit personnummer her.","fi":"Kirjaa t\u00e4h\u00e4n henkil\u00f6tunnuksesi. HUOM: Huomaa, ett\u00e4 yritykset eiv\u00e4t voi tilata osamaksulla."},"notice_socialNumber_part_no":{"en":"Please enter your social security number here.","sv":"Ange ditt personnummer h\u00e4r. OBS! V\u00e4nligen observera att du inte kan handla som f\u00f6retag vid delbetalning.","nl":"Vult u a.u.b. hier uw persoonsnummer in. NB: Als bedrijf kunt u niet met Klarna Account betalen.","de":"Bitte geben Sie Ihre Sozialversicherungsnummer hier ein. Bitte beachten Sie, dass Sie als Firma nicht mit Klarna Ratenkauf zahlen k\u00f6nnen","nb":"Vennligst fyll inn ditt personnummer her. MERK: V\u00e6r oppmerksom p\u00e5 at du ikke kan benytte delbetaling dersom du handler som bedrift.","da":"Indtast dit personnummer her. ","fi":"Kirjaa t\u00e4h\u00e4n henkil\u00f6tunnuksesi. HUOM: Huomaa, ett\u00e4 yritykset eiv\u00e4t voi tilata osamaksulla."},"notice_socialNumber_part_dk":{"en":"Please enter your social security number here.","sv":"Ange ditt personnummer h\u00e4r. OBS! V\u00e4nligen observera att du inte kan handla som f\u00f6retag vid delbetalning.","nl":"Vult u a.u.b. hier uw persoonsnummer in. NB: Als bedrijf kunt u niet met Klarna Account betalen.","de":"Bitte geben Sie Ihre Sozialversicherungsnummer hier ein. Bitte beachten Sie, dass Sie als Firma nicht mit Klarna Ratenkauf zahlen k\u00f6nnen","nb":"Vennligst fyll inn ditt personnummer her. MERK: V\u00e6r oppmerksom p\u00e5 at du ikke kan benytte delbetaling dersom du handler som bedrift. ","da":"Indtast dit personnummer her.","fi":"Kirjaa t\u00e4h\u00e4n henkil\u00f6tunnuksesi. HUOM: Huomaa, ett\u00e4 yritykset eiv\u00e4t voi tilata osamaksulla."},"notice_socialNumber_part_fi":{"en":"Please enter your social security number here.","sv":"Ange ditt personnummer h\u00e4r. OBS! V\u00e4nligen observera att du inte kan handla som f\u00f6retag vid delbetalning.","nl":"Vult u a.u.b. hier uw persoonsnummer in. NB: Als bedrijf kunt u niet met Klarna Account betalen.","de":"Bitte geben Sie Ihre Sozialversicherungsnummer hier ein. Bitte beachten Sie, dass Sie als Firma nicht mit Klarna Ratenkauf zahlen k\u00f6nnen","nb":"Vennligst fyll inn ditt personnummer her. MERK: V\u00e6r oppmerksom p\u00e5 at du ikke kan benytte delbetaling dersom du handler som bedrift. ","da":"Indtast dit personnummer her. ","fi":"Kirjaa t\u00e4h\u00e4n henkil\u00f6tunnuksesi. HUOM: Huomaa, ett\u00e4 yritykset eiv\u00e4t voi tilata osamaksulla."},"company_name":{"en":"Company name","sv":"F\u00f6retagsnamn","nl":"Bedrijfsnaam","de":"Firmenname","nb":"Bedriftsnavn","da":"Firmanavn","fi":"Yrityksen nimi"},"notice_companyName":{"en":"please enter your company name here","sv":"V\u00e4nligen fyll i ditt f\u00f6retags namn h\u00e4r","nl":"Vult u a.u.b. hier uw bedrijfsnaam in.","de":"Firmenname Info","nb":"Vennligst fyll inn bedriftsnavn","da":"Indtast firmanavn her","fi":"Sy\u00f6t\u00e4 yrityksesi nimi t\u00e4h\u00e4n"},"reference":{"en":"Reference","sv":"Referens","nl":"Referentie","de":"Referenz","nb":"Referanse","da":"Reference","fi":"Viite"},"notice_reference":{"en":"Please submit the reference for this order","sv":"V\u00e4nligen fyll i er referens f\u00f6r denna order","nl":"Vult u a.u.b. hier uw referentie in.","de":"Bitte geben Sie Ihre Referenz f\u00fcr diese Bestellung an.","nb":"Vennligst oppgi din bestillingsreferanse","da":"V\u00e6r venlig, at oplyse reference for denne ordre","fi":"Ole hyv\u00e4 ja sy\u00f6t\u00e4 viitenumerosi"},"address_housenumber_addition":{"en":"Extension","sv":"Till\u00e4gg","nl":"Toevoeging","de":"Zusatz","nb":"Vedlegg","da":"Tilf\u00f8jelse","fi":"Lis\u00e4ys"},"notice_house_extension":{"en":"Please submit your house extension here. E.g. A, B, C, Red, Blue ect.","sv":"Ange h\u00e4r ditt hus till\u00e4gg. T.ex. A, B, C, R\u00f6d eller Bl\u00e5 osv.","nl":"Vult u a.u.b. hier de toevoeging aan het huisnummer in, bijv. A, 2, III hoog etc.","de":"Geben Sie Ihre Adresserg\u00e4nzungen hier an. Zum Beispiel: A, B, C, R\u00fcckgeb\u00e4ude, etc.","nb":"Vennligst fyll inn hustilleggene her. F.eks. A, B, C, r\u00f8d, bl\u00e5, osv.","da":"Indtast dine hus-tilf\u00f8jelser. Fx A, B, C, r\u00f8d eller bl\u00e5, osv.","fi":"Kirjaa t\u00e4h\u00e4n talosi lis\u00e4tiedot. Esim. A, B, C, punainen, sininen jne."},"languageSetting_note_no":{"en":"You have chosen the Norwegian method of payment. Please note that this is just a translated representation of the Norwegian payment system's functionality. Change your currency and country, if you want to show payment for your country.","sv":"Du har valt det norska betalningss\u00e4ttet. Observera att detta bara \u00e4r en \u00f6versatt representation av det norka betalningssystemets funktionalitet. \u00c4ndra din valuta och land, om du vill visa betalningss\u00e4tt f\u00f6r ditt land.","nl":"U heeft gekozen voor de Noorse betaalmethode. Houdt er rekening mee dat dit slechts een vertaling is van de Noorse betaalfunctionaliteit. Wijzig valuta en land als u de betaaloptie voor uw land wilt zien.","de":"Sie haben die norwegischen Zahlungsmethode ausgew\u00e4hlt. Bitte beachten Sie, dass dies nur eine \u00fcbersetzte Darstellung des NORKA Zahlung Funktionalit\u00e4t des Systems ist. \u00c4ndern Sie Ihre W\u00e4hrung und Land, wenn Sie die Zahlung f\u00fcr Ihr Land zeigen wollen.","nb":"Du har valgt en norsk betalingsm\u00e5te. V\u00e6r oppmerksom p\u00e5 at dette kun er en oversatt representasjon av det norske betalingssystemet. \u00d8nsker du \u00e5 se betalingsm\u00e5ter for ditt land endrer du valuta og land.","da":"Du har valgt den norske betalingsmetode. Bem\u00e6rk, at dette bare en en oversat gengivelse af norske betaling funktion af systemet. Skift valuta og land, hvis du \u00f8nsker at vise betalingen for dit land.","fi":"Olet valinnut norjalaisen maksutavan. Huomaa, ett\u00e4 t\u00e4m\u00e4 on vain k\u00e4\u00e4nnetty esittely Norjan maksuj\u00e4rjestelm\u00e4n toimivuudesta. Vaihda valuutta ja maa, jos haluat n\u00e4hd\u00e4 oman maasi maksuvaihtoehdot."},"languageSetting_note_se":{"en":"You have chosen the Swedish payment method. Please note that this is only a translated representation of the Swedish payment functionality. Change your currency and country if you would like to show the payment option suited for your country.","sv":"Du har valt det Svenska betalningss\u00e4ttet. Observera att detta bara \u00e4r en \u00f6versatt representation av det Svenska betalningssystemets funktionalitet. \u00c4ndra din valuta och land, om du vill visa betalningss\u00e4tt f\u00f6r ditt land.","nl":"U heeft gekozen voor de Zweedse betaalmethode. Houdt er rekening mee dat dit slechts een vertaling is van de Zweedse betaalfunctionaliteit. Wijzig valuta en land als u de betaaloptie voor uw land wilt zien.","de":" Sie haben die schwedischen Zahlungsmethode ausgew\u00e4hlt. Bitte beachten Sie, dass dies nur eine \u00fcbersetzte Darstellung der schwedischen Zahlungsfunktionalit\u00e4t ist. \u00c4ndern Sie W\u00e4hrung und Land, wenn Sie die Zahlungsmethode f\u00fcr Ihr Land angezeigt haben wollen.","nb":"Du har valgt en svensk betalingsm\u00e5te. V\u00e6r oppmerksom p\u00e5 at dette kun er en oversatt representasjon av det svenske betalingssystemet. \u00d8nsker du \u00e5 se betalingsm\u00e5ter for ditt land endrer du valuta og land.","da":"Du har valgt det svenske betalingsmetode. Bem\u00e6rk venligst at dette kun er en oversat gengivelse af den svenske betalingsystems funktioner. Skift valuta og land, hvis du gerne vil vise betalingsmuligheder egnet til dit land.","fi":"Olet valinnut ruotsalaisen maksutavan. Huomaa, ett\u00e4 t\u00e4m\u00e4 on vain k\u00e4\u00e4nnetty esittely Ruotsin maksuj\u00e4rjestelm\u00e4n toimivuudesta. Vaihda valuutta ja maa, jos haluat n\u00e4hd\u00e4 oman maasi maksuvaihtoehdot."},"languageSetting_note_fi":{"en":"You have chosen the Finish method of payment. Please note that this is just a translated representation of the Finish payment system's functionality. Change your currency and country, if you want to show payment for your country.","sv":"Du har valt det finska betalningss\u00e4ttet. Observera att detta bara \u00e4r en \u00f6versatt representation av det finska betalningssystemets funktionalitet. \u00c4ndra din valuta och land, om du vill visa betalningss\u00e4tt f\u00f6r ditt land.","nl":"U heeft gekozen voor de Finse betaalmethode. Houdt er rekening mee dat dit slechts een vertaling is van de Finse betaalfunctionaliteit. Wijzig valuta en land als u de betaaloptie voor uw land wilt zien.","de":"Sie haben die finnische Zahlungsart gew\u00e4hlt. Bitte beachten Sie, dass dies nur eine \u00fcbersetzte Darstellung f\u00fcr die die finnische Zahlungsart ist. \u00c4ndern Sie Ihre W\u00e4hrung und das Land, wenn Sie die Zahlungsarten f\u00fcr Ihr Land zeigen wollen.","nb":"Du har valgt en finsk betalingsm\u00e5te. V\u00e6r oppmerksom p\u00e5 at dette kun er en oversatt representasjon av det finske betalingssystemet. \u00d8nsker du \u00e5 se betalingsm\u00e5ter for ditt land endrer du valuta og land.","da":"Du har valgt den finske betalingsmetode. Bem\u00e6rk, at dette bare er en oversat gengivelse af det finske betalingssystems funktioner. Skift valuta og land, hvis du \u00f8nsker at vise betaling for dit land.","fi":"Olet valinnut suomalaisen maksutavan. Huomaa, ett\u00e4 t\u00e4m\u00e4 on vain k\u00e4\u00e4nnetty esittely Suomen maksuj\u00e4rjestelm\u00e4n toimivuudesta. Vaihda valuutta ja maa, jos haluat n\u00e4hd\u00e4 oman maasi maksuvaihtoehdot."},"languageSetting_note_nl":{"en":"You have chosen the Dutch method of payment. Please note that this is just a translated representation of the Dutch payment system's functionality. Change your currency and country, if you want to show payment for your country.","sv":"Du har valt det Nederl\u00e4ndska betalningss\u00e4ttet. Observera att detta bara \u00e4r en \u00f6versatt representation av det Nederl\u00e4ndska betalningssystemets funktionalitet. \u00c4ndra din valuta och land, om du vill visa betalningss\u00e4tt f\u00f6r ditt land.","nl":"U heeft gekozen voor de Nederlandse betaalmethode. Houdt er rekening mee dat dit slechts een vertaling is van de Nederlandse betaalfunctionaliteit. Wijzig valuta en land als u de betaaloptie voor uw land wilt zien.","de":"Sie haben die niederl\u00e4ndische Zahlungsart gew\u00e4hlt. Bitte beachten Sie, dass dies nur eine \u00fcbersetzte Darstellung f\u00fcr die die niederl\u00e4nische Zahlungsart ist. \u00c4ndern Sie Ihre W\u00e4hrung und das Land, wenn Sie die Zahlungsarten f\u00fcr Ihr Land zeigen wollen.","nb":"Du har valgt en nederlandsk betalingsm\u00e5te. V\u00e6r oppmerksom p\u00e5 at dette kun er en oversatt representasjon av det nederlandske betalingssystemet. \u00d8nsker du \u00e5 se betalingsm\u00e5ter for ditt land endrer du valuta og land.","da":"Du har valgt den hollandske betalingsmetode. Bem\u00e6rk, at dette bare er en oversat gengivelse af det hollandske betalingssystems funktioner. Skift valuta og land, hvis du \u00f8nsker at vise betaling for dit land.","fi":"Olet valinnut alankomaalaisen maksutavan. Huomaa, ett\u00e4 t\u00e4m\u00e4 on vain k\u00e4\u00e4nnetty esittely Alankomaiden maksuj\u00e4rjestelm\u00e4n toimivuudesta. Vaihda valuutta ja maa, jos haluat n\u00e4hd\u00e4 oman maasi maksuvaihtoehdot."},"languageSetting_note_de":{"en":"You have chosen the German method of payment. Please note that this is just a translated representation of the German payment system's functionality. Change your currency and country, if you want to show payment for your country.","sv":"Du har valt det tyska betalningss\u00e4ttet. Observera att detta bara \u00e4r en \u00f6versatt representation av det tyska betalningssystemets funktionalitet. \u00c4ndra din valuta och land, om du vill visa betalningss\u00e4tt f\u00f6r ditt land.","nl":"U heeft gekozen voor de Duitse betaalmethode. Houdt er rekening mee dat dit slechts een vertaling is van de Duitse betaalfunctionaliteit. Wijzig valuta en land als u de betaaloptie voor uw land wilt zien.","de":"Sie haben die deutsche Zahlungsart gew\u00e4hlt. Bitte beachten Sie, dass dies nur eine \u00fcbersetzte Darstellung f\u00fcr die die deutsche Zahlungsart ist. \u00c4ndern Sie Ihre W\u00e4hrung und das Land, wenn Sie die Zahlungsarten f\u00fcr Ihr Land zeigen wollen.","nb":"Du har valgt en tysk betalingsm\u00e5te. V\u00e6r oppmerksom p\u00e5 at dette kun er en oversatt representasjon av det tyske betalingssystemet. \u00d8nsker du \u00e5 se betalingsm\u00e5ter for ditt land endrer du valuta og land.","da":"Du har valgt den tyske betalingsmetode. Bem\u00e6rk, at dette bare er en oversat gengivelse af det tyske betalingssystems funktioner. Skift valuta og land, hvis du \u00f8nsker at vise betaling for dit land.","fi":"Olet valinnut saksalaisen maksutavan. Huomaa, ett\u00e4 t\u00e4m\u00e4 on vain k\u00e4\u00e4nnetty esittely Saksan maksuj\u00e4rjestelm\u00e4n toimivuudesta. Vaihda valuutta ja maa, jos haluat n\u00e4hd\u00e4 oman maasi maksuvaihtoehdot."},"languageSetting_note_dk":{"en":"You have chosen the Danish method of payment. Please note that this is just a translated representation of the Danish payment system's functionality. Change your currency and country, if you want to show payment for your country.","sv":"Du har valt det danska betalningss\u00e4ttet. Observera att detta bara \u00e4r en \u00f6versatt representation av det danka betalningssystemets funktionalitet. \u00c4ndra din valuta och land, om du vill visa betalningss\u00e4tt f\u00f6r ditt land.","nl":"U heeft gekozen voor de Deense betaalmethode. Houdt er rekening mee dat dit slechts een vertaling is van de Deense betaalfunctionaliteit. Wijzig valuta en land als u de betaaloptie voor uw land wilt zien.","de":"Sie haben die d\u00e4nische Zahlungsart gew\u00e4hlt. Bitte beachten Sie, dass dies nur eine \u00fcbersetzte Darstellung f\u00fcr die die d\u00e4nische Zahlungsart ist. \u00c4ndern Sie Ihre W\u00e4hrung und das Land, wenn Sie die Zahlungsarten f\u00fcr Ihr Land zeigen wollen.","nb":"Du har valgt en dansk betalingsm\u00e5te. V\u00e6r oppmerksom p\u00e5 at dette kun er en oversatt representasjon av det dansk betalingssystemet. \u00d8nsker du \u00e5 se betalingsm\u00e5ter for ditt land endrer du valuta og land.","da":"Du har valgt den danske betalingsmetode. V\u00e6r opm\u00e6rksom p\u00e5, at dette bare er en oversat gengivelse af det danske betalingssystems funktioner. Skift valuta og land, hvis du vil vise betaling for dit eget land.","fi":"Olet valinnut tanskalaisen maksutavan. Huomaa, ett\u00e4 t\u00e4m\u00e4 on vain k\u00e4\u00e4nnetty esittely Tanskan maksuj\u00e4rjestelm\u00e4n toimivuudesta. Vaihda valuutta ja maa, jos haluat n\u00e4hd\u00e4 oman maasi maksuvaihtoehdot."},"socialSecurityNumber":{"en":"Social security number","sv":"Personnummer","nl":"Persoonsnummer","de":"Sozialversicherungsnummer","nb":"Personnummer","da":"Personnummer","fi":"Henkil\u00f6tunnus"},"mobile_phone":{"en":"Mobile phone:","sv":"Mobilnummer:","nl":"Mobielnummer:","de":"Mobiltelefonnummer:","nb":"Mobilnummer:","da":"Mobiltelefon:","fi":"Matkapuhelinnumero:"},"mobile_phone_number":{"en":"Mobile phone","sv":"Mobilnummer","nl":"Mobielnummer","de":"Mobiltelefonnummer","nb":"Mobilnummer","da":"Mobilnummer","fi":"Matkapuhelinnumero"},"phone_number":{"en":"Phone number","sv":"Telefonnummer","nl":"Telefoonnummer","de":"Telefonnummer","nb":"Telefonnummer","da":"Telefonnummer","fi":"Puhelinnumero"},"notice_mobilePhone":{"en":"Please submit your mobile phone.","sv":"V\u00e4nligen ange ditt mobilnummer.","nl":"Vult u a.u.b. hier uw mobielnummer in.","de":"Bitte geben Sie Ihre Mobiltelefonnummer an.","nb":"Vennligst fyll inn ditt mobilnummer.","da":"Indtast venligst dit mobilnummer","fi":"Ole hyv\u00e4 ja sy\u00f6t\u00e4 matkapuhelinnumerosi"},"notice_phoneNumber_se":{"en":"Please submit your phone number","sv":"Uppge ditt telefonnummer","nl":"Vult u a.u.b. hier uw telefoonnummer in.","de":"Bitte geben Sie Ihre Telefonnummer an.","nb":"Vennligst fyll inn ditt telefonnummer","da":"Indtast venligst dit telefonnummer","fi":"Ole hyv\u00e4 ja ilmoita puhelinnumerosi"},"notice_phoneNumber_dk":{"en":"Please submit your phone number","sv":"Uppge ditt telefonnummer","nl":"Vult u a.u.b. hier uw telefoonnummer in.","de":"Bitte geben Sie Ihre Telefonnummer an.","nb":"Vennligst fyll inn ditt telefonnummer","da":"Indtast venligst dit telefonnummer","fi":"Ole hyv\u00e4 ja ilmoita puhelinnumerosi"},"notice_phoneNumber_no":{"en":"Please submit your phone number","sv":"Uppge ditt telefonnummer","nl":"Vult u a.u.b. hier uw telefoonnummer in.","de":"Bitte geben Sie Ihre Telefonnummer an.","nb":"Vennligst fyll inn ditt telefonnummer","da":"Indtast venligst dit telefonnummer","fi":"Ole hyv\u00e4 ja ilmoita puhelinnumerosi"},"notice_phoneNumber_fi":{"en":"Please submit your phone number","sv":"Uppge ditt telefonnummer","nl":"Vult u a.u.b. hier uw telefoonnummer in.","de":"Bitte geben Sie Ihre Telefonnummer an.","nb":"Vennligst fyll inn ditt telefonnummer","da":"Indtast venligst dit telefonnummer","fi":"Ole hyv\u00e4 ja ilmoita puhelinnumerosi"},"notice_phoneNumber_nl":{"en":"Please submit your phone number","sv":"Uppge ditt telefonnummer","nl":"Vult u a.u.b. hier uw telefoonnummer in.","de":"Bitte geben Sie Ihre Telefonnummer an.","nb":"Vennligst fyll inn ditt telefonnummer","da":"Indtast venligst dit telefonnummer","fi":"Ole hyv\u00e4 ja ilmoita puhelinnumerosi"},"notice_phoneNumber_de":{"en":"Please submit your phone number","sv":"Uppge ditt telefonnummer","nl":"Vult u a.u.b. hier uw telefoonnummer in.","de":"Bitte geben Sie Ihre Telefonnummer an.","nb":"Vennligst fyll inn ditt telefonnummer","da":"Indtast venligst dit telefonnummer","fi":"Ole hyv\u00e4 ja ilmoita puhelinnumerosi"},"delivery_address":{"en":"Delivery address","sv":"Leveransadress","nl":"Afleveradres","de":"Lieferadresse","nb":"Leveringsadresse","da":"Leveringsadresse","fi":"Toimitusosoite"},"part_payment":{"en":"Part payment options","sv":"Delbetalningsm\u00f6jligheter","nl":"Mogelijkheden deelbetaling","de":"Ratenkaufoptionen","nb":"Delbetalingsm\u00e5ter","da":"Delbetalings muligheder","fi":"Er\u00e4maksuvaihtoehdot"},"spec_payment":{"en":"Campaign options","sv":"Kampajner","nl":"Campaignopties","de":"Aktionsoptionen","nb":"Kampanjer","da":"Kampagne muligheder","fi":"Kampanjavaihtoehdot"},"company_not_allowed":{"en":"It seems you have chosen an partpayment option for a company; currently we do not allow any partpayment options for companies.","sv":"Det verkar som du har valt en delbetalning f\u00f6r ett f\u00f6retag, f\u00f6r n\u00e4rvarande till\u00e5ter vi inte n\u00e5gon delbetalning f\u00f6r f\u00f6retag.","nl":"U heeft gekozen voor deelbetalen voor een bedrijf; momenteel bieden wij deze mogelijkheid niet aan bedrijven aan.","de":"Sie haben eine Ratenkaufoption f\u00fcr ein Firmen gew\u00e4hlt. Zurzeit sind Ratenkaufbestellungen f\u00fcr Firmen nicht m\u00f6glich.","nb":"Det ser ut som at du har valgt delbetaling for en bedrift. For tilfellet tillater vi desverre ikke delbetaling for bedrifter.","da":"Du har valgt en delbetalings mulighed som erhvervskunde; p\u00e5 nuv\u00e6rende tidspunkt tillader vi ingen delbetalings muligheder for firmaer.","fi":"Olet valinnut maksun kuukausieriss\u00e4 yritykselle; t\u00e4ll\u00e4 hetkell\u00e4 emme valitettavasti tarjoa er\u00e4maksuvaihtoehtoa yrityksille"},"MODULE_INVOICE_TEXT_TITLE":{"en":"Klarna Invoice","sv":"Klarna Faktura","nl":"Klarna Factuur","de":"Klarna  Rechnung","nb":"Klarna  Faktura","da":"Klarna  Faktura","fi":"Klarna  Lasku"},"MODULE_PARTPAY_TEXT_TITLE":{"en":"Klarna Part Payment","sv":"Klarna Delbetalningar","nl":"Klarna Account","de":"Klarna  Ratenkauf","nb":"Klarna  Delbetaling","da":"Klarna  Delbetaling","fi":"Klarna  Tili"},"MODULE_SPEC_TEXT_TITLE":{"en":"Klarna Special Campaigns","sv":"Klarna Specialkampajner","nl":"Klarna Speciale Campaigns","de":"Klarna  Sonderaktion","nb":"Klarna  Spesialkampanjer","da":"Klarna  Specielle Kampagner","fi":"Klarna  Erikoiskampanjat"},"INVOICE_TITLE":{"en":"Klarna Invoice (+XX)","sv":"Faktura - Betala 14 dagar efter leverans (+XX)","nl":"Klarna Factuur (+XX)","de":"Klarna  Rechnung (+XX)","nb":"Klarna  Faktura (+XX)","da":"Klarna  Faktura (+XX)","fi":"Klarna  Lasku (+XX)"},"INVOICE_TITLE_NO_PRICE":{"en":"Klarna - Invoice","sv":"Faktura - Betala 14 dagar efter leverans","nl":"Klarna Factuur - Betaal binnen 14 dagen","de":"Rechnung - Zahlung innerhalb von 14 Tagen","nb":"Faktura - betal om 14 dager","da":"Faktura - Betal om 14 dage","fi":"Lasku - 14 p\u00e4iv\u00e4\u00e4 maksuaikaa"},"INVOICE_FEE_TITLE":{"en":"Invoice fee","sv":"Faktureringsavgift","nl":"Factuurkosten","de":"Rechnungsgeb\u00fchr","nb":"Fakturagebyr","da":"Fakturagebyr","fi":"Laskutusmaksu"},"PARTPAY_TITLE":{"en":"Part payment option from xx\/month","sv":"Delbetalning - Klarna fr\u00e5n xx\/m\u00e5n","nl":"Deelbetalen via Klarna - vanaf xx\/maand","de":"Ratenkauf ab xx","nb":"Delbetaling via Klarna - betal fra xx i m\u00e5neden","da":"Delbetaling - Klarna fra xx\/m\u00e5ned","fi":"Klarna Tili-  alkaen xx\/kk"},"PARTPAY_TITLE_NOSUM":{"en":"Part payment option from Klarna","sv":"Part payment option from Klarna","nl":"Deelbetalen via Klarna","de":"Ratenzahlung mit Klarna","nb":"Delbetalingsalternativ fra Klarna","da":" Delbetalingsmuligheder fra Klarna","fi":"Er\u00e4maksumahdollisuus Klarnalta"},"SPEC_TITLE":{"en":"Special campaign from Klarna","sv":"Special campaign from Klarna","nl":"Speciale Campaign van Klarna","de":"Sonderaktion von Klarna","nb":"Spesialkampanje fra Klarna","da":" S\u00e6rlige kampagner fra Klarna","fi":"Erikoiskampanja Klarnalta"},"INVOICE_TEXT_DESCRIPTION":{"en":"Invoice from Klarna","sv":"Swedish invoice from Klarna","nl":"Zweedse factuur van Klarna","de":"Schwedische Rechnung von Klarna","nb":"Svensk faktura fra Klarna","da":"Svensk faktura fra Klarna","fi":"Ruotsalainen lasku Klarnalta"},"PARTPAY_TEXT_DESCRIPTION":{"en":"Part payment options from Klarna","sv":"Swedish part payment options from Klarna","nl":"Deelbetalen via het Zweedse Klarna","de":"Schwedische Ratenkaufoption von Klarna","nb":"Svenske delbetalingsalternativ fra Klarna","da":"Svenske delbetalingsmuligheder fra Klarna","fi":"Ruotsalainen er\u00e4maksumahdollisuus Klarnalta"},"SPEC_TEXT_DESCRIPTION":{"en":"Special Campaigns from Klarna","sv":"Special Campaigns from Klarna","nl":"Speciale Campaigns van Klarna","de":"Sonderaktion von Klarna AB","nb":"Spesialkampanjer fra Klarna","da":"S\u00e6rlige Kampagner fra Klarna","fi":"Erikoiskampanjat Klarnalta"},"INVOICE_CONFIRM_DESCRIPTION":{"en":"www.klarna.com","sv":"www.klarna.com","nl":"www.klarna.nl","de":"www.klarna.de","nb":"www.klarna.no","da":"www.klarna.dk","fi":"www.klarna.fi"},"PARTPAY_CONFIRM_DESCRIPTION":{"en":"www.klarna.com","sv":"www.klarna.com","nl":"www.klarna.nl","de":"www.klarna.de","nb":"www.klarna.no","da":"www.klarna.dk","fi":"www.klarna.fi"},"SPEC_CONFIRM_DESCRIPTION":{"en":"www.klarna.com","sv":"www.klarna.com","nl":"www.klarna.nl","de":"www.klarna.de","nb":"www.klarna.no","da":"www.klarna.dk","fi":"www.klarna.fi"},"consent":{"en":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen.","sv":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen.","nl":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen.","de":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen.","nb":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen.","da":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen.","fi":"Mit der \u00dcbermittlung der f\u00fcr die Abwicklung des Rechnungskaufes und einer Identit\u00e4ts- und Bonit\u00e4tspr\u00fcfung erforderlichen Daten an Klarna bin ich einverstanden. Meine Einwilligung kann ich jederzeit mit Wirkung f\u00fcr die Zukunft widerrufen."},"show_consent":{"en":"(Show agreement)","sv":"(Visa avtal)","nl":"(Toon overeenkomst)","de":"(Vereinbarung anzeigen)","nb":"(Vis avtale)","da":"(Vis aftale)","fi":"(N\u00e4yt\u00e4 sopimus)"},"mobile_mobile_code":{"en":"XXXX","sv":"XXXX","nl":"****","de":"XXXX","nb":"XXXX","da":"XXXX","fi":"XXXX"},"mobile_topInfo":{"en":"All purchases via Klarna Mobile will be gathered on one bill per month. No costs, No fees.","sv":"Alla k\u00f6p via Klarna mobil samlas p\u00e5 en och samma m\u00e5nadsfaktura. Inga r\u00e4ntor, inga aviavgifter.","nl":"Alle aankopen via Klarna Mobile worden per maand verzameld op \u00e9\u00e9n factuur. Zonder rente en zonder kosten.","de":"Alle Eink\u00e4ufe mit Klarna Mobil werden auf einer Monatsrechnung gesammelt. Ohne Zinsen und ohne Geb\u00fchren.","nb":"Alle kj\u00f8p med Klarna mobil samles p\u00e5 en og samme m\u00e5nedsfaktura. Ingen rente, ingen fakturagebyr.","da":"Alle k\u00f8b via Klarna mobil samles p\u00e5 \u00e9n og samme m\u00e5nedsfaktura. Ingen renter og ingen indbetalingsafgifter.","fi":"Kaikki Klarna matkapuhelintilaukset kootaan samalle kuukausilaskulle. Ei korkoa, ei kuluja."},"mobile_agreement":{"en":"Terms and conditions","sv":"Villkor","nl":"Voorwaarden","de":"Bedingungen","nb":"Vilk\u00e5r","da":"Vilk\u00e5r","fi":"Ehdot"},"mobile_mobilePhoneNo":{"en":"Cellphone number","sv":"Mobilnummer","nl":"Mobielnummer","de":"Mobiltelefonnummer","nb":"Mobilnummer","da":"Mobilnummer","fi":"Puhelinnumero"},"mobile_pinCode":{"en":"Enter your Pin Code","sv":"Fyll i din kod","nl":"Voert u a.u.b. uw pincode in.","de":"Bitte geben Sie Ihren Pinkod an.","nb":"Fyll inn kode","da":"Indtast din pinkode","fi":"Sy\u00f6t\u00e4 koodisi"},"mobile_whoops":{"en":"Whoops!","sv":"Hoppsan!","nl":"Oeps!","de":"Hoppla!","nb":"Obs!","da":"Hovsa","fi":"Hups!"},"mobile_Close":{"en":"Close","sv":"St\u00e4ng","nl":"Sluiten","de":"Schlie\u00dfen","nb":"Lukk","da":"Luk","fi":"Sulje"},"PPBOX_fromText":{"en":"From","sv":"Fr\u00e5n","nl":"Vanaf","de":"Ab","nb":"Fra","da":"Fra","fi":"Alkaen"},"PPBOX_monthText":{"en":"\/month","sv":"\/m\u00e5nad","nl":"\/maand","de":"Monat","nb":"\/m\u00e5ned","da":"M\u00e5ned","fi":"\/kuukausi"},"PPBOX_account":{"en":"Account","sv":"Konto","nl":"Account","de":"Ratenkauf","nb":"Konto","da":"Konto","fi":"Tili"},"PPBOX_th_month":{"en":"Months","sv":"M\u00e5n","nl":"Maanden","de":"Monate","nb":"M\u00e5ned","da":"M\u00e5ned","fi":"Kuukausia"},"PPBOX_th_sum":{"en":"Sum\/month","sv":"Summa\/m\u00e5n","nl":"Bedrag\/maand","de":"Summe\/Monat","nb":"Sum\/m\u00e5ned","da":"Bel\u00f8b\/M\u00e5ned","fi":"Summa\/kk"},"PPBox_readMore":{"en":"More information","sv":"L\u00e4s mer","nl":"Lees meer","de":"Lesen Sie mehr.","nb":"Les mer","da":"Mere information","fi":"Lis\u00e4tietoja"},"no_consent":{"en":"Bitte stimmen Sie den AGB und der Datenschutzerkl\u00e4rung zu.","sv":"Bitte geben Sie Ihre Einwilligung zur Daten\u00fcbermittlung.","nl":"Bitte geben Sie Ihre Einwilligung zur Daten\u00fcbermittlung.","de":"Bitte geben Sie Ihre Einwilligung zur Daten\u00fcbermittlung.","nb":"Bitte geben Sie Ihre Einwilligung zur Daten\u00fcbermittlung.","da":"Bitte geben Sie Ihre Einwilligung zur Daten\u00fcbermittlung.","fi":"Bitte geben Sie Ihre Einwilligung zur Daten\u00fcbermittlung."},"ot_klarna_title":{"en":"Invoice fee","sv":"Faktureringsavgift","nl":"Factuurkosten","de":"Rechnungsgeb\u00fchr","nb":"Fakturagebyr","da":"Faktureringsgebyr","fi":"Laskutusmaksu"},"ot_klarna_description":{"en":"Klarna Invoice fee","sv":"Klarna Invoice fee (SE)","nl":"Factuurkosten Klarna (NL)","de":"Klarna  Rechnungsgeb\u00fchr (DE)","nb":"Klarna  Fakturagebyr (NO)","da":"Klarna  Fakturagebyr (DK)","fi":"Klarna  Laskutuslis\u00e4 (FI)"},"comment_purchase_accepted":{"en":"Purchase accepted by Klarna","sv":"Ditt k\u00f6p \u00e4r accepterat av Klarna","nl":"Aankoop door Klarna goedgekeurd","de":"Der Kauf wurde von Klarna akzeptiert.","nb":"Kj\u00f8pet er akseptert av Klarna","da":"K\u00f8bet er accepteret af Klarna","fi":"Klarna on hyv\u00e4ksynyt ostoksen"},"comment_purchase_pending":{"en":"Purchase accepted by Klarna","sv":"Ditt k\u00f6p \u00e4r accepterat av Klarna","nl":"Aankoop door Klarna goedgekeurd","de":"Der Kauf wurde von Klarna akzeptiert.","nb":"Kj\u00f8pet er akseptert av Klarna","da":"K\u00f8bet er accepteret af Klarna","fi":"Klarna on hyv\u00e4ksynyt ostoksen"},"comment_purchase_ref":{"en":"Reference","sv":"Referens","nl":"Referentie","de":"Referenz","nb":"Referanse","da":"Reference","fi":"Viite"},"comment_pay_option":{"en":"Payment choice","sv":"Betalningss\u00e4tt","nl":"Keuze betaalmogelijkheid","de":"Zahlungsmethode","nb":"Betalingsm\u00e5te","da":"Valg af betaling","fi":"Maksutapa"},"invoice_string":{"en":"Invoice","sv":"Faktura","nl":"Factuur","de":"Rechnung","nb":"Faktura","da":"Faktura","fi":"Lasku"},"year_salary":{"en":"Your yearly salary","sv":"Din \u00e5rsinkomst","nl":"Uw jaarinkomen","de":"Ihr j\u00e4hrliches Einkommen","nb":"\u00c5rsinntekt","da":"Din \u00e5rlige indkomst","fi":"Vuositulosi"},"notice_year_salary_dkk":{"en":"Your yearly salary in Dansk krone before tax","sv":"Din \u00e5rsinkomst i Danska kronor f\u00f6re skatt","nl":"Uw jaarinkomen v\u00f3\u00f3r belasting in Deense kronen","de":"Ihr j\u00e4hrliches Bruttoeinkommen in d\u00e4nischen Kronen","nb":"\u00c5rsinntekt i danske kroner, f\u00f8r skatt","da":"Din \u00e5rlige indkomst i DKK f\u00f8r skat","fi":"Vuositulosi Tanskan kruunuina ennen veroja"},"notice_year_salary_eur":{"en":"Your yearly salary in Euro before tax","sv":"Din \u00e5rsinkomst i Euro f\u00f6re skatt","nl":"Uw jaarinkomen v\u00f3\u00f3r belasting in Euro's","de":"Ihr j\u00e4hrliches Bruttoeinkommen in Euro","nb":"\u00c5rsinntekt i Euro, f\u00f8r skatt","da":"Din \u00e5rlige indkomst i Euro f\u00f8r skat","fi":"Vuositulosi euroina ennen veroja"},"notice_year_salary_nok":{"en":"Your yearly salary in Norwegian Crowns before tax","sv":"Din \u00e5rsinkomst i Norska kronor f\u00f6re skatt","nl":"Uw jaarinkomen v\u00f3\u00f3r belasting in Noorse kronen","de":"Ihr j\u00e4hrliches Bruttoeinkommen in norwegischen Kronen","nb":"\u00c5rsinntekt i norske kroner, f\u00f8r skatt","da":"Din \u00e5rlige indkomst i NOK f\u00f8r skat","fi":"Vuositulosi Norjan kruunuina ennen veroja"},"notice_year_salary_sek":{"en":"Your yearly salary in Swedish Crowns before tax","sv":"Din \u00e5rsinkomst i kronor f\u00f6re skatt","nl":"Uw jaarinkomen v\u00f3\u00f3r belasting in Zweedse kronen","de":"Ihr j\u00e4hrliches Bruttoeinkommen in schwedischen Kronen","nb":"\u00c5rsinntekt i svenske kroner, f\u00f8r skatt","da":"Din \u00e5rlige indkomst i SEK f\u00f8r skat","fi":"Vuositulosi Ruotsin kruunuina ennen veroja"},"notice_billing_same_as_shipping":{"en":"Your billing address will be overwritten with your shipping address","sv":"Din faktureringsadress kommer att skrivas \u00f6ver med din leveransadress","nl":"Het afleveradres zal gebruikt worden als factuuradres","de":"Ihre Lieferadresse wird als Rechnungsadresse benutzt.","nb":"Din faktureringsadresse vil v\u00e6re den samme som din leveringsadresse","da":"Din leveringsadresse vil blive anvendt som faktureringsadresse","fi":"Laskutusosoitteesi tulee vaihtumaan toimitusosoitteeksesi"},"no_get_address":{"en":"No address found. Please enter your social security number, this will update your address automatically","sv":"Ingen adress hittades. V\u00e4nligen skriv in ditt personnummer s\u00e5 uppdateras din adress automatiskt","nl":"Er is geen adres gevonden. Vult u a.u.b. uw persoonsnummer in, dan wordt uw adres automatisch gewijzigd.","de":"Keine Adresse gefunden. Bitte geben Sie Ihre Sozialversicherungsnummer an, damit Ihre Adresse automatisch aktualisiert werden kann.","nb":"Ingen adresse funnet. Vennligst skriv inn ditt personnummer s\u00e5 oppdateres adressen automatisk","da":"Ingen adresse fundet! V\u00e6r venlig, at indtaste dit CPR nummer, s\u00e5 bliver din adresse opdateret automatisk.","fi":"Osoitetta ei l\u00f6ytynyt. Ole yst\u00e4v\u00e4llinen ja t\u00e4yt\u00e4 henkil\u00f6tunnuksesi, niin osoitteesi p\u00e4ivitet\u00e4\u00e4n automaattisesti"},"ilt_chooseAnswer":{"en":"Choose answer","sv":"V\u00e4lj svar","nl":"Kies antwoord","de":"Antwort w\u00e4hlen","nb":"Velg svar","da":"V\u00e6lg svar","fi":"Valitse vastaus"},"ilt_title":{"en":"To complete your purchase, we need to ask you some questions: ","sv":"F\u00f6r att kunna slutf\u00f6ra Ert k\u00f6p beh\u00f6ver Ni besvara n\u00e5gra fr\u00e5gor.","nl":"Om uw aankoop te voltooien, moeten wij u enkele vragen stellen:","de":"Um Ihren Kauf abzuschlie\u00dfen, beantworten Sie bitte folgende Fragen:","nb":"For \u00e5 fullf\u00f8re kj\u00f8pet, m\u00e5 vi stille deg et par sp\u00f8rsm\u00e5l ","da":"For at f\u00e6rdigg\u00f8re dit k\u00f8b skal du f\u00f8rst svare p\u00e5 nogle sp\u00f8rgsm\u00e5l:","fi":"Vied\u00e4ksemme ostoksesi loppuun, meid\u00e4n t\u00e4ytyy kysy\u00e4 sinulta muutama kysymys:"},"invoice_number_text":{"en":"Klarna Invoice Number","sv":"Klarna OCR-\/Fakturanummer","nl":"Klarna referentie-\/factuurnummer","de":"Klarna Rechnungsnummer","nb":"KID-nummer Klarna","da":"Klarna Fakturanummer","fi":"Klarna- laskunumero"},"INVOICE_CREATED_SUCCESSFULLY":{"en":"Invoice with invoice number (xx) created successfully at Klarna","sv":"Faktura med fakturanummer (xx) \u00e4r skapad av Klarna","nl":"Factuur met factuurnummer (xx) is succesvol door Klarna aangemaakt","de":"Die Rechunung Nr: (xx) wurde erstellt","nb":"Faktura med fakturanummer (xx) er opprettet hos Klarna","da":"Faktura med fakturanummer (xx) blev oprettet hos Klarna","fi":"Lasku laskunumerolla (xx) on luotu onnistuneesti Klarnan j\u00e4rjestelm\u00e4ss\u00e4"},"address_updated_notice":{"en":"Your shipping address has been updated by Klarna.","sv":"Er leveransadress har uppdaterats av Klarna.","nl":"Klarna heeft uw afleveradres aangepast.","de":"Ihre Lieferadresse wurde von Klarna aktualisiert.","nb":"Din leveringsadresse er oppdatert av Klarna.","da":"Din leveringsadresse er opdateret af Klarna.","fi":"Klarna on p\u00e4ivitt\u00e4nyt toimitusosoitteenne."},"format_invoicefee_not_included":{"en":"An Invoice fee of (xx) will be added","sv":"Fakturaavgift tillkommer med (xx)","nl":"(xx) factuurkosten zullen worden toegevoegd","de":"Die Rechnungsgeb\u00fchr (xx) wird hinzugef\u00fcgt.","nb":"Fakturaavgift p\u00e5 (xx) vil bli lagt til","da":"Faktura gebyret (xx) er tilf\u00f8jet","fi":"Lis\u00e4t\u00e4\u00e4n laskutuslis\u00e4 (xx)"},"confirm_order":{"en":"I confirm my order","sv":"Jag bekr\u00e4ftar best\u00e4llningen","nl":"Ik bevestig mijn bestelling","de":"Ich stimme meiner Bestellung zu.","nb":"Jeg godkjenner min bestilling","da":"Jeg bekr\u00e6fter min ordre","fi":"Vahvistan tilaukseni"},"other_payment_methods":{"en":"Other payment methods","sv":"Andra betalningsmetoder","nl":"Andere betaalmogelijkheden","de":"Andere Zahlungsoptionen","nb":"Andre betalingsmetoder","da":"Andre betalingsmetoder","fi":"Muut maksutavat"},"confirm_order_notice":{"en":"Please confirm your order by clicking 'I confirm my order'","sv":"Bekr\u00e4fta best\u00e4llningen genom att klicka p\u00e5 'Jag bekr\u00e4ftar best\u00e4llningen'","nl":"Bevestig uw bestelling door te klikken op 'Ik bevestig mijn bestelling'","de":"Bitte best\u00e4tigen Sie Ihre Bestellung indem Sie auf ","nb":"Vennligst bekreft din bestilling ved \u00e5 klikke p\u00e5 ","da":"Bekr\u00e6ft venligst din ordre ved at trykke p\u00e5 'Jeg bekr\u00e6fter min ordre'","fi":"Ole hyv\u00e4 ja vahvista tilauksesi klikkaamalla `Vahvistan tilaukseni`"},"per_month":{"en":"\/ month","sv":"\/m\u00e5nad","fi":"\/ kuukausi","nl":"\/maand","de":"\/ Monat","da":"\/ M\u00e5ned","nb":"\/m\u00e5ned"},"status_pending":{"en":"Klarna Pending","sv":"Klarna Pending","nl":"Klarna Hangend","de":"Klarna Ausstehend","da":"Klarna Afventer","fi":"Odottava tilaus","nb":"Klarna Avventer"},"status_accepted":{"en":"Klarna Accepted","sv":"Klarna Accepted","nl":"Klarna Geaccepteerd","de":"Klarna Akzeptiert","da":"Accepteret af Klarna","fi":"Klarnan hyv\u00e4ksym\u00e4","nb":"Akseptert av Klarna"},"click_invoice_to_print":{"en":"Click on the invoice number to view and print it","sv":"Klicka p\u00e5 fakturanumret f\u00f6r att se och skriva ut fakturan","nl":"Klik op het factuurnummer om deze te bekijken of te printen","de":"Klicken Sie auf die Rechnungsnummer um sie anzusehen und auszudrucken.","da":"For visning og udskrivning af fakturaen, tryk p\u00e5 fakturanummeret","fi":"Klikkaa laskunumeroa n\u00e4hd\u00e4ksesi ja tulostaaksesi laskun","nb":"Klikk p\u00e5 fakturanummeret for \u00e5 se og skrive ut fakturaen"},"activated":{"en":"Activated","sv":"Aktiverad","nl":"Geactiveerd","de":"aktiviert","nb":"Aktivert","da":"Aktiveret","fi":"Aktivoitu"},"klarna_invoices":{"en":"Klarna Invoices","sv":"Klarna Fakturor","nl":"Klarna Facturen","de":"Klarna Rechnungen","da":"Klarna Fakturaer","fi":"Klarna Laskut","nb":"Klarna Fakturaer"},"reservation_number_text":{"en":"Klarna Reservation Number","sv":"Klarnas Reservationsnummer","nl":"Klarna Reserveringsnummer","de":"Klarna Reservierungsnummer","da":"Klarna Reservationsnummer","fi":"Klarna Varausnumero","nb":"Klarnas reservasjonsnummer"},"INVOICE_CREATED_SUCCESSFULLY_RETURNOCR":{"en":"Invoice with reference number (xx) created successfully at Klarna","sv":"Faktura med referens (xx) \u00e4r nu skapad hos Klarna","de":"Rechnung mit Referenznummer (xx) erfolgreich bei Klarna erstellt.","nl":"De rekening met referentienummer (xx) is succesvol bij Klarna aangemaakt","da":"Faktura med referencenummer (xx) er oprettet hos Klarna","fi":"Lasku viitenumerolla (xx) on luotu onnistuneesti Klarnalla","nb":"Faktura med referanse (xx) er n\u00e5 skapt hos Klarna"},"NO_INVOICE_FEE":{"en":"No invoice fee will be charged","sv":"Ingen faktureringsavgift tillkommer","nl":"Er worden geen factuurkosten in rekening gebracht","de":"Es werden keine Rechnungsgeb\u00fchren verlangt.","da":"Der vil ikke blive opkr\u00e6vet fakturagebyr","fi":"Laskutusmaksua ei perit\u00e4","nb":"Ingen faktureringsavgift tilkommer"},"discount":{"en":"Discount","fi":"Alennus","nl":"Korting","da":"Rabat","sv":"Rabatt","de":"Rabatt","nb":"Rabatt"},"coupon":{"en":"Coupon","fi":"Kuponki","nl":"Coupon","da":"Kupon","sv":"Kupong","de":"Gutschein","nb":"Kupong"},"gift_wrapping":{"en":"Gift Wrapping","fi":"Lahjapaketointi","nl":"Geschenkverpakking","da":"Gaveindpakning","sv":"Presentinslagning","de":"Geschenkverpackung","nb":"Gaveinnpakning"},"module_config":{"en":"Configuration","sv":"Konfiguration","nl":"Configuratie","de":"Konfiguration","nb":"Konfigurasjon","da":"Konfiguration","fi":"Konfiguraatio"},"status":{"en":"Status","sv":"Status","nl":"Status","de":"Status","nb":"Status","da":"Status","fi":"Status"},"status_denied":{"en":"Denied","sv":"Nekad","nl":"Geweigerd","de":"verweigert","nb":"Avsl\u00e5tt","da":"Afvist","fi":"Kielletty"},"invoice_created_with_id":{"en":"Klarna transaction created with number : XX","sv":"Klarna transaktion skapad med referens : XX","nl":"Klarnatransactie aangemaakt met nummer: XX","de":"Klarna-Transaktion mit der Referenznummer XX erstellt","nb":"Klarna-transaksjon skapt med nummer: XX","da":"Klarna-transaktion oprettet med nummer : XX","fi":"Klarna-transaktio luotu numerolla: xx"},"check_order_status":{"en":"Update Order Status","sv":"Uppdatera Order Status","nl":"Wijzig orderstatus","de":"Order-Status aktualisieren","nb":"Oppdater bestillingsstatus","da":"Opdat\u00e9r Ordrestatus","fi":"P\u00e4ivit\u00e4 tilausstatus"},"activate_order":{"en":"Activate Order","sv":"Aktivera Order","nl":"Activeer order","de":"Order aktivieren","nb":"Aktiver bestilling","da":"Aktiv\u00e9r Ordre","fi":"Aktivoi tilaus"},"new_order_status":{"en":"Order status successfully updated. New order status:","sv":"Order statusen har uppdaterats. Ny order status:","nl":"De orderstatus is succesvol gewijzigd. Nieuwe orderstatus:","de":"Order-Status erfolgreich aktualisiert. Neuer Order-Status: ","nb":"Bestillingsstatus oppdatert. Ny bestillingsstatus:","da":"Ordre status er opdateret. Ny ordrestatus:","fi":"Tilausstatus on p\u00e4ivitetty onnistuneesti. Uusi tilausstatus:"},"not_a_klarna_order":{"en":"This order is not a Klarna order.","sv":"Denna order \u00e4r inte en Klarna order.","nl":"Deze bestelling is geen Klarna order.","de":"Diese Order ist keine Klarna-Order.","nb":"Denne bestillingen er ingen Klarna-bestilling.","da":"Denne ordre er ikke en Klarna-ordre.","fi":"T\u00e4m\u00e4 tilaus ei ole Klarna-tilaus."},"settings_saved":{"en":"The settings have been saved.","sv":"Konfigurationen har sparats.","nl":"De instellingen zijn opgeslagen.","de":"Die Einstellungen wurden gespeichert.","nb":"Innstillingene er lagret.","da":"Indstillingerne er gemt.","fi":"Asetukset on tallennettu."},"email_sent_for":{"en":"Email has been successfully sent for invoice:","sv":"Mejl har skickats f\u00f6r transaktion med referensnummer:","de":"Die E-Mail wurde verschickt f\u00fcr die Transaktion mit der Referenznummer: ","nl":"Er is een e-mail verstuurd voor factuur:","nb":"Mail er sendt for faktura:","da":"Email er blevet sendt for faktura:","fi":"S\u00e4hk\u00f6posti on l\u00e4hetetty onnistuneesti koskien laskua:"},"email_sent":{"en":"Invoice sent through email:","sv":"Fakturan skickades med mejl:","nl":"Factuur is via e-mail verstuurd:","de":"Die Rechnung wurde per E-Mail verschickt","nb":"Faktura sendt via mail:","da":"Faktura er sendt via email:","fi":"Lasku on l\u00e4hetetty s\u00e4hk\u00f6postitse:"},"email_invoice":{"en":"Email Invoice","sv":"Mejla fakturan","nl":"E-mail Factuur","de":"E-Mail-Rechnung","nb":"Mailfaktura","da":"Email Faktura","fi":"S\u00e4hk\u00f6postilasku"},"admin_order_address_notice":{"en":"Upon purchase Klarna will do a risk assessment of the order. Among other things this includes verifying that the address is an approved address for delivery.  We strongly recommend that you do not change the address that was approved at the time of purchase. Changing the address may lead to Klarna not accepting the risk of the order.","sv":"Vid k\u00f6ptillf\u00e4llet kommer Klarna att g\u00f6ra en riskbed\u00f6mning av ordern. Bland annat s\u00e5 ser man s\u00e5 att addressen \u00e4r en godk\u00e4nd leveransaddress. Vi rekommenderar starkt att du inte \u00e4ndrar addressen som godk\u00e4ndes vid k\u00f6ptillf\u00e4llet. Om addressen \u00e4ndras kan det leda till att Klarna inte st\u00e5r f\u00f6r risken f\u00f6r ordern.","nl":"Op het moment van aankoop beoordeelt Klarna het risico van de order. Onder andere wordt gecontroleerd of het adres een goedgekeurd afleveradres is. Wij raden u ten zeerste af het adres te wijzigen dat op het moment van aankoop is goedgekeurd. Het wijzigen van het adres kan tot gevolg hebben dat Klarna niet langer het risico van de order accepteert.","de":"Klarna wird eine Risikobewertung der Bestellung beim Kauf vornehmen. Unter anderem wird \u00fcberpr\u00fcft, ob die Lieferadresse g\u00fcltig ist. Wir empfehlen daher sehr, die zum Zeitpunkt des Kaufes akzeptierte Adresse nicht mehr zu \u00e4ndern. Eine \u00c4nderung der Adresse kann dazu f\u00fchren, dass Klarna das Risiko der Bestellung nicht  \u00fcbernimmt.","fi":"Ostotapahtuman yhteydess\u00e4 Klarna tekee riskiarvion tilauksesta. T\u00e4m\u00e4 tarkoittaa muun muassa sen varmistamista, ett\u00e4 osoite on hyv\u00e4ksytty toimitusosoite. Suosittelemme vahvasti, ett\u00e4 et muuttaisi osoitetta, joka hyv\u00e4ksyttiin ostotapahtuman yhteydess\u00e4. Osoitteen muuttaminen voi johtaa siihen, ett\u00e4 Klarna ei kanna riski\u00e4 tilauksesta. ","da":"Ved k\u00f8b hos Klarna foretages en risikovurdering af ordren. Det inkludere bl.a. at kontroller om adressen er en godkendt leveranceadresse. Vi anbefaler derfor, at du ikke \u00e6ndre den adresse som blev godkendt p\u00e5 k\u00f8bstidspunktet. At \u00e6ndre adressen kan f\u00f8re til, at Klarna ikke godkender k\u00f8bet.","nb":"Ved kj\u00f8pstilfellet foretar Klarna en risikovurdering av bestillingen. Klarna kontrollerer blant annet at den oppgitte adressen er en godkjent leveringsadresse. Vi anbefaler at adressen som ble godkjent ved kj\u00f8pstilfellet ikke endres. En endring av adressen kan f\u00f8re til at Klarna fraskriver seg risikoansvaret for bestillingen."},"magento_config_hint":{"en":"_Configuration_ can be found in the menu on the left."}});