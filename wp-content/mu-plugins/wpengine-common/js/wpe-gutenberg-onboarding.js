/**
 * WP Engine Gutenberg Onboarding
 */
if( typeof( pagenow ) != 'undefined' ) {
    if( 'dashboard' === pagenow || 'dashboard-network' === pagenow ) {
        jQuery(document).ready(function( $ ) {
            var wpeGutenbergPanel = $( '#wpe-gutenberg-panel' ),
            wpeGutenbergPanelHide = $('#wp_wpe_gutenberg_panel-hide'),
            updateWPEGutenbergPanel, installGutenbergSuccess;

            // Set the user meta to dismiss the panel.
            updateWPEGutenbergPanel = function( visible ) {
                $.post( ajaxurl, {
                        action: 'update-wpe-gutenberg-panel',
                        visible: visible,
                        wpegutenbergpanelnonce: $( '#wpegutenbergpanelnonce' ).val()
                });
            };

            // Callback for Classic Editor install process.  Sets up "activate" state.
            installWPEGutenbergSuccess = function( response ) {
                var multisiteLabel = ( wpeIsMultisite === 1 ) ? 'Network ' : '';
                response.activateUrl += '&from=wpe-gutenberg';
                response.activateLabel = multisiteLabel + wp.updates.l10n.activatePluginLabel.replace( '%s', response.pluginName );
                wp.updates.installPluginSuccess( response );
            };

            if ( wpeGutenbergPanel.hasClass( 'hidden' ) && wpeGutenbergPanelHide.prop( 'checked' ) ) {
                wpeGutenbergPanel.removeClass( 'hidden' );
            }

            // Dismiss Notice proceess.  Hide the notice and update the user meta.
            $( '.wpe-gutenberg-panel-close, .wpe-gutenberg-panel-dismiss a', wpeGutenbergPanel ).on( 'click', function( e ) {
                e.preventDefault();
                wpeGutenbergPanel.addClass( 'hidden' );
                updateWPEGutenbergPanel( 0 );
                $('#wp_wpe_gutenberg_panel-hide').prop( 'checked', false );
            });

            // Action for the "ready" state.
            $( '.ready', wpeGutenbergPanel ).on( 'click', function( e ) {
                e.preventDefault();
                updateWPEGutenbergPanel( 0 );
                window.location.href = $(this).attr('href');
            });

            // Action for the "activate" state.
            $( '.activate-now', wpeGutenbergPanel ).on( 'click', function( e ) {
                e.preventDefault();
                if( typeof( wpe ) != 'undefined' ) {
                    wpe.updates.confirmButton( $(this) );
                }
                updateWPEGutenbergPanel( 0 );
                window.location.href = $(this).attr('href');
            });

            // Hide the panel and update the user meta.
            wpeGutenbergPanelHide.click( function() {
                wpeGutenbergPanel.toggleClass( 'hidden', ! this.checked );
                updateWPEGutenbergPanel( this.checked ? 1 : 0 );
            });

            // Handler for the installing the Classic Editor.
            wpeGutenbergPanel.on( 'click', '.install-now', function( e ) {
                e.preventDefault();
                if( typeof( wpe ) != 'undefined' ) {
                    wpe.updates.confirmButton( $(this) );
                }
                var args = {
                        slug: $( e.target ).data( 'slug' ),
                        success: installWPEGutenbergSuccess
                };
                wp.updates.installPlugin( args );
            } );
        });
    } //pagenow vars
} //pagenow present