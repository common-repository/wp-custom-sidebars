/*!
 * WP Custom Sidebar admin metabox script
 *
 * admin-metabox.js
 */

;(function($) {
    
    "use strict";

    var processData = function(){
        
        if( !$('[data-wpcs-fields]').length )
            return;
    
        var obj = {},
            $inputs = $('[data-wpcs-fields] select');

        $inputs.each(function(){
            var $el = $(this),
                val = $el.val();
                
            if( val ){
                obj[$el.data('name')] = $el.val();
            }
        });

        $('[data-wpcs-data]').val( JSON.stringify(obj) );

        $inputs.removeAttr('name');
    }
    /**
     * Begin
     */
    $(document)
                
    // .on('submit', '#post', processData )
    .on('change', '[data-wpcs-fields] select', processData );
    
})(jQuery);