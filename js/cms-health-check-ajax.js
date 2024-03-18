var tersfdasdf;
jQuery( document ).ready( function() {
	jQuery( '.cms-health-check-ajax-options' ).on( 'submit', function( e ) {
		e.preventDefault();

		let formAction = jQuery( this ).attr( 'action' );

		let formData = jQuery( this ).serializeArray();
		formData.push( {
			name: 'form-action',
			value: formAction
		} );

		console.log( formData );

		jQuery.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'cms_health_check_save_options',
				form_data: formData
			},
			success: function ( response, textStatus, XMLHttpRequest ) {
				switch ( formAction ) {
					case '#regenerate-token':
						if ( ! response.success || typeof response.data.token === 'undefined' ) {
							jQuery( '#cms-health-check-security-token-message .success' ).slideUp();

							jQuery( '#cms-health-check-security-token-message .failed' ).slideDown();
						} else {
							jQuery( '#cms-health-check-security-token-message .failed' ).slideUp();

							jQuery( '#cms-health-check-security-token' ).val( response.data.token );
							jQuery( '#cms-health-check-security-token-message .success' ).slideDown();
						}
						break;
					case '#save-enabled-checks':
						if ( response.success ) {
							jQuery( '#cms-health-check-enable-checks-message .failed' ).slideUp();

							jQuery( '#cms-health-check-enable-checks-message .success' ).slideDown( function() {
								jQuery( this ).delay( 2000 ).slideUp();
							} );
						} else {
							jQuery( '#cms-health-check-enable-checks-message .success' ).slideUp();

							jQuery( '#cms-health-check-enable-checks-message .failed' ).slideDown();
						}
						break;
					default:
						console.log( 'Action not found.' );
				}
			},
			error: function ( XMLHttpRequest, textStatus, errorThrown ) {
			}
		} );

		return false;
	} );
} );