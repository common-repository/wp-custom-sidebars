/*!
 * WP Custom Sidebar admin script
 *
 * admin-script.js
 */

;(function($) {
    
    "use strict";
    /**
     * Begin wpCustomSidebars object
     */
    var wpCustomSidebars = {
        
        /**
         * Action
         */
        init: function(){
            
            this._eventRunning = false;
            this._vars = typeof wpCustomSidebarsVars !== 'undefined' ? wpCustomSidebarsVars : {};
            
            var self = this;
            
            $(document)
                .on('click', '.wpcs-add-sidebar, .wpcs-remove-sidebar, .wpcs-import-data', self.doSidebar );
            
        },

        doSidebar: function(e){

            e.preventDefault();

            var self = wpCustomSidebars,
                $el = $(this),
                type = $el.data('type'),
                nonce = $('[name="wpcs_ajax_processor_nonce"]').val(),
                sidebarField = $el.prev( '[name="sidebar_name"]'),
                value = $el.data('id'),
                spinner = $('.wpcs-wrapper').find('.spinner'),
                table = $('#wpcs-table'),
                notice = $('.wpcs-notice'),
                answer = '';



            if( 'add' == type  ){
                value = sidebarField.val();

                if( '' == value ){
                    alert( self._vars.msgAddSidebarName );
                    return;
                }

            }else if( 'remove' == type  ){
                
                answer = confirm( self._vars.msgConfirmRemove );

                if( !answer )
                    return;

            }else if( 'import' == type ){

                value =  $el.prev( '[name="sidebar_transfer"]').val();              
                answer = confirm( self._vars.msgConfirmImport );

                if( !answer )
                    return;
            }

            if( self._eventRunning )
                return;

            self._eventRunning = true;
            spinner.fadeIn(function(){
                $(this).addClass('is-active');
            });

            //console.log( type +  nonce + value);

            // Ajax call
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'wp-custom-sidebars-ajax-action',
                    type: type,
                    nonce: nonce,
                    value : value
                },
                success: function( response ){

                    console.log( response );

                    if( response.success && 'add' == response.data.type ){
                        sidebarField.val('');

                        table.append( '<tr><td>' + response.data.name + '</td><td>' + response.data.id + '</td><td><button class="button button-small wpcs-remove-sidebar" data-type="remove" data-id="' + response.data.id + '">&times;</button></td></tr>');


                        var no_sidebar_tr = table.find( 'tr.no-sidebar-tr' );
                        if( no_sidebar_tr.length )
                            no_sidebar_tr.remove();

                    }else if( response.success && 'remove' == response.data.type ){
                        $el.closest('tr').remove();
                    }else if( response.success && 'import' == response.data.type ){
                        setTimeout(function(){
                            window.location.reload();
                        }, 2000);
                    }

                    /**
                     * Display Notice
                     */
                    if( typeof response.data.message !== 'undefined' ){
                        notice.html( response.data.message );

                        setTimeout(function(){
                            notice.html('');
                        }, 5000);
                    }

                    spinner.fadeOut(function(){
                        $(this).removeClass('is-active');
                    });
                    self._eventRunning = false;

                }
            });

        }
    }
    
    /**
     * Kickstart it
     */
    wpCustomSidebars.init();
    
})(jQuery);