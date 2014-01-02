jQuery(document).ready( function($) {

	$('#frontend-post').submit( function( ev ) {
		ev.preventDefault();
		var $form = $(this);
		$form.find( 'input[type="submit"]').attr('disabled', 'disabled');
		$.post( frontendPost.ajaxUrl, {
			action: 'fep_post',
			security: frontendPost.security,
			data: $form.serialize()
		}, function( response ) {
			if ( ! response.success ) return;
			console.log( response );

			$form.find( 'input[type="submit"]').removeAttr('disabled');
			// reset post form

		}, 'json' );
	});

});