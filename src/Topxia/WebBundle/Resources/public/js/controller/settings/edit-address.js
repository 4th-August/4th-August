define(function(require, exports, module) {
    var Validator = require('bootstrap.validator');
    Validator.addRule('passwordCheck', function(options, commit) {
        var element = options.element;
        var url = options.url ? options.url : (element.data('url') ? element.data('url') : null);

        $.post(url, {value:element.val()}, function(response) {
            commit(response.success, response.message);
        }, 'json');
    });
    require('common/validator-rules').inject(Validator);

    exports.run = function() {
        var validator = new Validator({
            element: '#edit-address-form',
            onFormValidated: function(error){
                if (error) {
                    return false;
                }
                $('#submit-btn').button('submiting').addClass('disabled');
            }
        });

        validator.addItem({
            element: '[name="tel"]',
            required: true,
            rule: 'phone'
        });

        validator.addItem({
            element: '[name="address"]',
            required: true
        });

        validator.addItem({
            element: '[name="name"]',
            required: true
        });

	};
});