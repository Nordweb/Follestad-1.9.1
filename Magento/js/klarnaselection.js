Ajax.Responders.register({
    onComplete: function() {
        var typeValue = Form.getInputs(
            'co-payment-form', 'radio', 'payment[method]'
            ).find(function(radio) {
            return radio.checked;
        }).value;
        if (typeof typeValue != 'undefined') {
            if ((typeValue == 'klarna_invoice')
                || (typeValue == 'klarna_partpayment')
                || (typeValue == 'klarna_specpayment')
                ) {
                $('payment_form_' + typeValue).show();
            }
        }
    }
});