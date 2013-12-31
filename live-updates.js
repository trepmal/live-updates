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
			container = $('.hentry:first').parent();
			var newPosts = $( response.data ),
				newPostsBg = newPosts.css('background-color');
			newPosts.hide().css( 'background-color', 'rgba( 238, 238, 153, 0.8 )').prependTo( container ).slideDown( 'slow', function() {
				newPosts.animate( {
					'background-color': newPostsBg
				});
			});
		} );
	}

	setInterval( function() {
		if ( ! getLatestPostsPause )
			getLatestPosts()
	}, liveUpdates.interval );

});