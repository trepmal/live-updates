<?php
/*
 * Plugin Name: Live Updates
 * Plugin URI: trepmal.com
 * Description:
 * Version: 2
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: live-updates
 * DomainPath:
 * Network:
 */

$live_updates = new Live_Updates();

class Live_Updates {

	function __construct() {
		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_ajax_get_latest', array( &$this, 'get_latest_cb' ) );
		add_action( 'wp_ajax_nopriv_get_latest', array( &$this, 'get_latest_cb' ) );
	}

	function defaults( $fetch ) {
		$options = array(
			'live_updates_loop_template' => 'content.php',
			'live_updates_interval' => 10000,
			'frontend_editor' => 1,
		);

		if ( isset( $fetch ) ) {
			return isset( $options[ $fetch ] ) ? $options[ $fetch ] : '';
		}

		return $options;
	}

	function admin_init() {

		add_settings_section( 'live_update_section', __( 'Live Updates', 'live-updates' ), '__return_empty_string', 'reading' );

		$field_name = 'live_updates_loop_template';
		register_setting( 'reading', $field_name, 'strip_tags' );
		add_settings_field( "_$field_name", __( 'Template Name', 'live-updates' ), array( &$this, 'input_text' ), 'reading', 'live_update_section', $field_name );

		$field_name = 'live_updates_interval';
		register_setting( 'reading', $field_name, 'intval' );
		add_settings_field( "_$field_name", __( 'Interval (in milliseconds)', 'live-updates' ), array( &$this, 'input_text' ), 'reading', 'live_update_section', $field_name );

		$field_name = 'frontend_editor';
		register_setting( 'reading', $field_name, 'intval' );
		add_settings_field( "_$field_name", __( 'Show Frontend Editor', 'live-updates' ), array( &$this, 'input_checkbox' ), 'reading', 'live_update_section', $field_name );

	}

	function input_text( $arg ) {
		$v = get_option( $arg, $this->defaults( $arg ) );
		echo "<input type='text' name='$arg' value='$v' />";
		if ( 'live_updates_loop_template' == $arg ) {
			echo '<p class="description">'. __( 'A template file in the theme, or a full path to a custom template. Template should handle the display inside the loop.', 'live-updates' ) .'</p>';
		} elseif ( 'live_updates_interval' == $arg ) {
			echo '<p class="description">'. __( 'How frequently to check for new posts. e.g. 10000 is 10 seconds', 'live-updates' ) .'</p>';
		}
	}

	function input_checkbox( $arg ) {
		$v = get_option( $arg, $this->defaults( $arg ) );
		echo "<input type='checkbox' name='$arg' value='1' ". checked( $v, 1, false ) ." />";
		// if ( 'live_updates_loop_template' == $arg ) {
		// 	echo '<p class="description">'. __( 'A template file in the theme, or a full path to a custom template. Template should handle the display inside the loop.', 'live-updates' ) .'</p>';
		// } elseif ( 'live_updates_interval' == $arg ) {
		// 	echo '<p class="description">'. __( 'How frequently to check for new posts. e.g. 10000 is 10 seconds', 'live-updates' ) .'</p>';
		// }
	}

	function wp_enqueue_scripts() {
		if ( ! is_home() ) return;
		if ( get_query_var('paged') > 1 ) return;
		wp_enqueue_script( 'live-updates', plugins_url( 'live-updates.js', __FILE__ ), array('jquery', 'jquery-color', 'editor' ), 1, true );
		wp_localize_script( 'live-updates', 'liveUpdates', array(
			'ajaxUrl'    => admin_url('admin-ajax.php'),
			'interval'   => get_option('live_updates_interval'),
			'loadingGif' => admin_url('images/loading.gif'),
			'security'   => wp_create_nonce( 'security_nonce'),
		) );
	}

	/**
	 * Fetch new, unseen posts
	 */
	function get_latest_cb() {
		$topmost_post = array_shift( $_POST['postsOnPage'] );
		$topmost_post = get_post( $topmost_post );
		if ( is_null( $topmost_post ) ) {
			wp_send_json_error( ); // unabled to fetch latest post on page
		}
		$date = explode( ' ', date( 'Y m d G i s', strtotime( $topmost_post->post_date_gmt ) ) );
		$date = array_combine( array( 'year', 'month', 'day', 'hour', 'minute', 'second' ), $date );

		$query_args = array(
			'date_query' => array(
				array(
					'column'    => 'post_date_gmt',
					'after'     => $date,
					'inclusive' => false,
				)
			)
		);

		// usefull if the user has something other than chrono-ordered posts
		$query_args = apply_filters( 'live_updates_query_args', $query_args );

		$q = new WP_Query( $query_args );

		if ( $q->have_posts() ) :
			while( $q->have_posts() ) : $q->the_post();
				ob_start();
				if ( false !== ( $template = get_option( 'live_updates_loop_template', false ) ) ) {
					if ( locate_template( $template, true, false ) ) {

					} elseif ( file_exists( $template ) ) {
						load_template( $template, false );
					}
				} else {
					// manual template
					echo '<div>';
					the_title();
					echo '<br />';
					the_content();
					echo '</div>';
				}
				$html = ob_get_clean();
			endwhile;
		endif;

		wp_reset_postdata();

		if ( empty( $html ) ) {
			wp_send_json_error( 'No new posts' );
		}

		wp_send_json_success( trim($html) );

	}

}

/*
######## ########   #######  ##    ## ######## ######## ##    ## ########      ########   #######   ######  ########
##       ##     ## ##     ## ###   ##    ##    ##       ###   ## ##     ##     ##     ## ##     ## ##    ##    ##
##       ##     ## ##     ## ####  ##    ##    ##       ####  ## ##     ##     ##     ## ##     ## ##          ##
######   ########  ##     ## ## ## ##    ##    ######   ## ## ## ##     ##     ########  ##     ##  ######     ##
##       ##   ##   ##     ## ##  ####    ##    ##       ##  #### ##     ##     ##        ##     ##       ##    ##
##       ##    ##  ##     ## ##   ###    ##    ##       ##   ### ##     ##     ##        ##     ## ##    ##    ##
##       ##     ##  #######  ##    ##    ##    ######## ##    ## ########      ##         #######   ######     ##
*/

if ( get_option( 'frontend_editor' ) ) {
	add_action( 'loop_start', 'fep_loop_start' );
	add_action( 'wp_ajax_fep_post', 'fep_post_cb' );
}

/**
 * Insert editor at top of loop
 *
 * Check for main query, capabilities, and home page.
 */
function fep_loop_start( $query ) {

	if ( ! $query->is_main_query() ) return;
	if ( ! current_user_can('publish_posts') ) return;
	if ( ! is_home() ) return;
	if ( get_query_var('paged') > 1 ) return;

	wp_enqueue_style( 'frontend-post', plugins_url('frontend-post.css', __FILE__ ) );
	wp_enqueue_script( 'live-updates' );

	?><form id="frontend-post" method="post">
	<p><input type="text" name="fep_title" /></p>
	<?php
	wp_editor( '', 'fep_content', array(
		'textarea_rows' => 5,
	) );
	?>
	<p><input type="submit" class="button" value="Post" /></p>
	</form>
	<?php
}

/**
 * Insert post
 */
function fep_post_cb() {
	check_ajax_referer( 'security_nonce', 'security' ); // will die if failure

	parse_str( $_POST['data'], $data );

	$id = wp_insert_post( array(
		'post_title'   => htmlspecialchars( $data['fep_title'] ),
		'post_content' => wp_filter_post_kses( $data['fep_content'] ),
		'post_status'  => 'publish',
	) );

	if ( $id )
		wp_send_json_success( $data );
	else
		wp_send_json_error( $data );

}

//eof