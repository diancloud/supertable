jQuery.fn.supertable = function( cmd, properties ) {
    var self = this;

    $.validator.addMethod( "allow", function( value, element, fmt ) {
        if ( typeof fmt == 'object') {
            if ( fmt.indexOf(value) != -1 ) {
                return true;
            }
        }
        return false;
    }, "field Not allow" );
  

    // FormCreate
    var methods = {
        columnCreate: function() {

            // 默认值
            var defaults = {
                    action: null,
                    ignore: ".ignore",
                    errorClass: 'help-block text-right animated fadeInDown',
                    errorElement: 'div',
                    beforeSubmit: function( form ) { return true; },
                    submitHandler:function( form ) { return true; },
                    errorPlacement: function(error, e) {
                        jQuery(e).parents('.form-group .form-material').append(error);
                    },
                    highlight: function(e) {
                        jQuery(e).closest('.form-group .form-material').removeClass('has-error').addClass('has-error');
                        jQuery(e).closest('.help-block').remove();
                    },
                    success: function(e) {
                        jQuery(e).closest('.form-group .form-material').removeClass('has-error');
                        jQuery(e).closest('.help-block').remove();
                    },
                    rules: {},
                    message:{},
                },
                option = self.data('_supertable.columnCreate.option') || {};

            jQuery.extend( option, defaults, properties );
            self.data('_supertable.columnCreate.option', option);
            self.data('_supertable.columnCreate.submitHandler', option.submitHandler );

            // 设定验证规则
            jQuery.extend( option, { submitHandler:function( form ) { return $(form).data('_supertable.columnCreate.submitHandler')(); }} );
            $(self).validate(option);

            // 按钮事件
            $('.supertable-submit', self).unbind('click');
            $('.supertable-submit', self).click(function(event) {
                if ( option.beforeSubmit(self) == false ) {
                    return false;
                }
                if ( $('.supertable-submit', self).attr('type') != 'submit') {
                    $(self).submit();    
                }
            });

        },
    }

    if ( typeof methods[cmd] == 'function' ) {
        methods[cmd]();
    }
    return this;
}(jQuery);