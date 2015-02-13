(function (Klarna) {
    "use strict";

    var K = Klarna,
        _ = Klarna._,
        KE = Klarna.use('Event'),
        qwery = Klarna.use('qwery'),
        bonzo = Klarna.use('bonzo'),
        bean = Klarna.use('bean'),
        self = Klarna.use('Checkout.OSC'),
        url = false,
        country = false,
        klarna_type = "",
        hasrun = false;

    function getSelectedInput() {
        var inputs = qwery("#checkout-payment-method-load > dt > input");
        for (var i = 0; i < inputs.length; i++) {
            if (!!inputs[i].checked) {
                return inputs[i];
            }
        }
    }

    function changePaymentOption() {
        var selected = getSelectedInput();
        if (selected.value === 'klarna_invoice'
            || selected.value === 'klarna_partpayment'
            || selected.value === 'klarna_specpayment')
        {
            self.klarna_type = selected.value;
            bonzo(qwery('#klarna_input', ".onestepcheckout-column-left")).show();
        } else {
            self.klarna_type = "";
            bonzo(qwery('#klarna_input', ".onestepcheckout-column-left")).hide();
        }
    }

    function set (selector, value) {
        var element = qwery(selector).first();
        if (typeof element != "undefined") {
            bonzo(element).val(value);
        }
    }
    function highlight(selector) {
        var element = qwery(selector);
        if (typeof element != "undefined") {
            bonzo(element).css({border:'1px solid red'});
        }
    }

    function setFields (address) {
        if (address['company_name'] && address['company_name'].length > 0) {
            set('.input-company > input"', address['company_name']);
            highlight('.input-firstname > input');
            highlight('.input-lastname > input');
        } else {
            set('.input-firstname > input', address['first_name']);
            set('.input-lastname > input', address['last_name']);
            set('.input-company > input', "");
        }
        set('.input-address > input', address['street']);
        set('.input-city > input', address['city']);
        set('.input-postcode > input', address['zip']);
        highlight('.input-email > input');
        set('.input-country > select', 'SE');
        set('#klarna_address_key', address['key']);
    }

    function showError (message) {
        bonzo(qwery('#klarna_ssn_error', '#klarna_input')).html(message);
        bonzo(qwery('#klarna_ssn_error', "#klarna_input")).show();
    }

    function hideError () {
        bonzo(qwery('#klarna_ssn_error', '#klarna_input')).hide();
    }

    function showSpinner () {
        bonzo(qwery('#klarna_spinner', '#klarna_input')).show();
    }

    function hideSpinner () {
        bonzo(qwery('#klarna_spinner', '#klarna_input')).hide();
    }

    function update (data) {
        if (data.length <= 0) {
            return;
        }
        if (data.length > 1) {
            bonzo(qwery('#klarna_address', '#klarna_input')).show();
            bonzo(qwery('#klarna_multi_address_select')).empty();
            setFields(data[0]);
            data.each(function(item, index) {
                var info = [];
                var opt = document.createElement('option');
                opt.value = JSON.stringify(item);
                if(item['company_name'].length > 0){
                    info[0] = item['company_name'];
                    info[1] = item['street'];
                    info[2] = item['zip'];
                    info[3] = item['city'];
                } else {
                    info[0] = item['first_name'];
                    info[1] = item['last_name'];
                    info[2] = item['street'];
                    info[3] = item['zip'];
                    info[4] = item['city'];
                }
                bonzo(opt).text(info.join(", "));
                bonzo(qwery('#klarna_multi_address_select', '#klarna_input')).append(opt);
            });
        } else {
            bonzo(qwery('#klarna_address', '#klarna_input')).hide();
            setFields(data[0]);
        }
    }

    function getAddressesOneStep () {
        var pno = bonzo(qwery('#klarna_ssn')).val();
        pno = pno.replace(/-/g, "");
        if (pno.length < 10) {
            hideSpinner();
            bonzo(qwery('#klarna_address')).hide();
            return;
        }
        getAddresses(pno);
    }

    function getAddresses (pno) {
        showSpinner();
        hideError();
        Klarna.Ajax({
            method: 'post',
            type: 'json',
            url: self.url,
            data: {
                action: 'getAddress',
                type: self.klarna_type,
                country: self.country,
                pno: pno
            },
            success: function (resp) {
                if (resp.error) {
                    showError(resp.error.message);
                } else {
                    update(resp);
                }
            },
            error: function (resp) {
                showError(resp.responseText)
            }
        });
        hideSpinner();
    }

    function updateSelected() {
        var url = window.location.href + "ajax/save_billing/";
        get_separate_save_methods_function(url, false).call();
    }

    K.extend(self, {
        init: function (country, url) {
            if (hasrun != false) {
                return;
            }
            hasrun = true;
            KE.bind('ready', updateSelected);
            if (country !== "se" && country !== 209) {
                return;
            }
            self.country = country;
            self.url = url;
            bean.add(
                document.body,
                "#checkout-payment-method-load > dt > input",
                'click',
                changePaymentOption,
                qwery
            );
            KE.bind('ready', changePaymentOption);
            bean.add(
                document.body,
                '#klarna_ssn',
                'keyup change blur focus',
                getAddressesOneStep,
                qwery
            );
        },

        changeFields: function () {
            var json = bonzo(qwery('#klarna_multi_address_select')).val();
            setFields(JSON.parse(json));
        }
    });
})(Klarna);
