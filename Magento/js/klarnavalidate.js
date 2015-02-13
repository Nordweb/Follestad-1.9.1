/**
 * Custom Magento validation
 */
Validation.addAllThese([
    ['klarna-validate-text', 'Required entry', function(v, elm) {
        return ((v != "") && (v != null) && (v.length != 0))
    }],
    ['klarna-validate-select', 'Please select one', function(v, elm) {
        return ((v != "00") && (v != null) && (v.length != 0));
    }],
    ['klarna-validate-radio', 'Please select one', function(v, elm) {
        var inputs = $$('input[name="' + elm.name.replace(/([\\"])/g, '\\$1') + '"]');
        var result = false;
        for (i in inputs) {
            if ((inputs[i].type == 'radio')
                && (inputs[i].checked)
            ) {
                result = true
            }
            if ((inputs[i].type == 'radio')
                && (Validation.isOnChange)
            ) {
                Validation.reset(inputs[i]);
            }
        }
        return result;
    }],
    ['klarna-validate-checkbox', 'Please accept the AGB terms', function(v, elm) {
        var result = false;
        if (elm.type == 'checkbox' && elm.checked) {
            result = true;
        }
        return result;
    }]
]);