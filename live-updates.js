jQuery(document).ready( function($) {

	/*
	 * Pause flag keeps us from potentially checking for new posts while a post is being inserted
	 * which could lead to a double posting (as the inserted's id wouldn't be used as the newest)
	 */
	var getLatestPostsPause = false;

	/*
	 * Check for the latest posts on page, fetch the newer ones from database
	 */
	function getLatestPosts() {
		getLatestPostsPause = true;
		postsOnPage = [];

		$('.hentry').each( function() {
			postsOnPage.push( $(this).attr('id').replace('post-', '' ) );
		} );

		$.post( liveUpdates.ajaxUrl, {
			action: 'get_latest',
			postsOnPage: postsOnPage
		}, function( response ) {
			// console.log( response );
			getLatestPostsPause = false;
			if ( ! response.success ) return;
			toppost = $('.hentry:first');
			// toppost.css('border', 'solid 2px red');

			// container = $('.hentry:first').parent();
			var newPosts = $( response.data ),
				newPostsBg = newPosts.css('background-color');
			// newPosts.hide().css( 'background-color', 'rgba( 238, 238, 153, 0.8 )').prependTo( container ).slideDown( 'slow', function() {
			newPosts.hide().css( 'background-color', 'rgba( 238, 238, 153, 0.8 )').insertBefore( toppost ).slideDown( 'slow', function() {
				newPosts.animate( {
					'background-color': newPostsBg
				});
			});

		} );
	}

	setInterval( function() {
		if ( ! getLatestPostsPause )
			getLatestPosts();
	}, liveUpdates.interval );

	$('#frontend-post').submit( function( ev ) {
		ev.preventDefault();

		tinymce.triggerSave(); // make sure the <textarea> has val()

		var $form = $(this),
			$btn = $form.find( 'input[type="submit"]');

		$btn.attr('disabled', 'disabled');
		$btn.after( '<img src="'+ liveUpdates.loadingGif +'" />');
		$.post( liveUpdates.ajaxUrl, {
			action: 'fep_post',
			security: liveUpdates.security,
			data: $form.serialize()
		}, function( response ) {

			$btn.next('img').remove();
			$btn.removeAttr('disabled');

			console.log( response );
			if ( ! response.success ) return;

			getLatestPosts();

			// reset post form
			tinymce.execCommand('mceSetContent', false, '');
			$form.find( 'input[type="text"]').val('');

		}, 'json' );
	});

});