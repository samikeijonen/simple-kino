<?php
/**
 * Plugin Name: Simple Kino
 * Plugin URI: http://foxnet.fi
 * Description: Register movie site in WordPress theme
 * Author: Sami Keijonen
 * Version: 0.1.1
 * Author URI: http://foxnet.fi

 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Simple Kino
 * @version 0.1.0
 * @author Sami Keijonen <sami.keijonen@foxnet.fi>
 * @copyright Copyright (c) 2012, Sami Keijonen
 * @link 
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/* Set up the plugin. */
add_action( 'plugins_loaded', 'simple_kino_setup' );

/* Register plugin activation hook. */
register_activation_hook( __FILE__, 'simple_kino_plugin_activate' );

/* Register plugin deactivation hook. */
register_deactivation_hook( __FILE__, 'simple_kino_plugin_deactivate' );

/**
 * Sets up the Simple Kino plugin and loads files at the appropriate time.
 * @since 0.1.0
 */
function simple_kino_setup() {

	/* Set constant path to the Simple Kino plugin directory. */
	define( 'SIMPLE_KINO_DIR', plugin_dir_path( __FILE__ ) );

	/* Set constant path to the Simple Kino plugin url. */
	define( 'SIMPLE_KINO_URL', plugin_dir_url( __FILE__ ) );

	/* Load the translation of the plugin. */
	load_plugin_textdomain( 'simple-kino', false, 'simple-kino/languages' );
	
	/* If members plugin is activated, add members capabilities in role management. */
	if( function_exists( 'members_get_capabilities' ) )
		add_filter( 'members_get_capabilities', 'simple_kino_members_get_capabilities' );

	/* Add Custom Post Type movie. */
	add_action( 'init', 'simple_kino_register_my_post_type_movies' );
	
	/* Map meta capabilities. */
	add_filter( 'map_meta_cap', 'simple_kino_map_meta_cap', 10, 4 );
	
	/* Add taxonomies Genre, Director, Actors, Age limit for movies Post Type. Those are not hierarchical.
	 * Also add hierarchical taxonomy like 'categories' Now playing, Upcoming movies.
	 **************************************************************************************************/
	add_action( 'init', 'simple_kino_register_my_taxonomy' );
	
	/* Add custom meta box for movies */
	add_action( 'add_meta_boxes', 'simple_kino_create_meta_boxes' );
	
	/* Save metabox data */
	add_action( 'save_post', 'simple_kino_save_meta_boxes' );
	
	/* Load datepicker script. */
	add_action( 'admin_enqueue_scripts', 'simple_kino_load_admin_scripts' );
	
	/* Add new image size */
	add_action( 'init', 'simple_kino_register_image_sizes' );
	
	/* Filter movies order alphabetically with pre_get_posts-function. */
	if ( ! is_admin() )
		//add_filter( 'pre_get_posts', 'simple_kino_custom_post_types_admin_order' ); 
	
	/* Add functions/tabs in Hybrid Tab plugin. */
	add_action( 'init', 'simple_kino_create_my_custom_tabs' );
	
	/* Add shortcodes [movie-information] and [movie-showtimes], can be used in post or pages. */
	add_shortcode( 'movie-information', 'simple_kino_movie_info_shortcode' );
	add_shortcode( 'movie-showtimes', 'simple_kino_movie_showtimes_shortcode' );
	
	/* Filter movie columns to display checkbox, title (Movie), movietimes, durarion, comments and date. */
	add_filter( 'manage_edit-movie_columns', 'simple_kino_edit_movie_columns' );
	
	/* Add content to custom columns */
	add_action( 'manage_movie_posts_custom_column', 'simple_kino_manage_movie_columns', 10, 2 );

	/* add filter to ensure the text Movie, or movie, is displayed when user updates a movie.
	** @link http://codex.wordpress.org/Function_Reference/register_post_type
	*/ 
	add_filter( 'post_updated_messages', 'simple_kino_movie_updated_messages' );

}

/**
 * Add movie capabilities to administrator role. 
 * @since 0.1.0
 */
function simple_kino_plugin_activate() {

	/* Register the custom post type movie. */
	simple_kino_register_my_post_type_movies();
	
	/* Flush permalinks. */
    flush_rewrite_rules();
	
	$role =& get_role( 'administrator' );

	if ( !empty( $role ) ) {

		$role->add_cap( 'edit_simple_kino_movies' );
		$role->add_cap( 'edit_others_simple_kino_movies' );
		$role->add_cap( 'publish_simple_kino_movies' );
		$role->add_cap( 'read_private_simple_kino_movies' );
		$role->add_cap( 'delete_simple_kino_movies' );
		$role->add_cap( 'delete_others_simple_kino_movie' );
		$role->add_cap( 'manage_simple_kino_taxonomies' );
		$role->add_cap( 'edit_simple_kino_taxonomies' );
		$role->add_cap( 'delete	_simple_kino_taxonomies' );
	
	}
	
}

/**
 * Remove movie capabilities from administrator role. 
 * @since 0.1.0
 */
function simple_kino_plugin_deactivate() {

	/* Flush permalinks. */
    flush_rewrite_rules();
	
	$role =& get_role( 'administrator' );

	if ( !empty( $role ) ) {

		$role->remove_cap( 'edit_simple_kino_movies' );
		$role->remove_cap( 'edit_others_simple_kino_movies' );
		$role->remove_cap( 'publish_simple_kino_movies' );
		$role->remove_cap( 'read_private_simple_kino_movies' );
		$role->remove_cap( 'delete_simple_kino_movies' );
		$role->remove_cap( 'delete_others_simple_kino_movie' );
		$role->remove_cap( 'manage_simple_kino_taxonomies' );
		$role->remove_cap( 'edit_simple_kino_taxonomies' );
		$role->remove_cap( 'delete_simple_kino_taxonomies' );
	
	}
	
}

/**
 * Add movie capabilities to Members plugin. 
 * @since 0.1.0
 */
function simple_kino_members_get_capabilities( $caps ) {

	return array_merge( $caps, array( 'edit_simple_kino_movies', 'edit_others_simple_kino_movies', 'publish_simple_kino_movies', 'read_private_simple_kino_movies', 'delete_simple_kino_movies', 'delete_others_simple_kino_movie', 'manage_simple_kino_taxonomies', 'edit_simple_kino_taxonomies', 'delete_simple_kino_taxonomies' ) );
	
}
 
/* 
 * Add Custon Post Types movie. 
 * @since 0.1.0
 */
function simple_kino_register_my_post_type_movies() {
	
	/* Set arguments for the movie Custom Post Type. */
	/* @link http://justintadlock.com/archives/2010/04/29/custom-post-types-in-wordpress */
	$movie_args = array(
		'public' => true, 
		'query_var' => 'movie',
		'menu_position' => 5,
		'menu_icon' => SIMPLE_KINO_URL . '/images/video.png',
		'supports' => array(
			'title',
			'editor',
			'thumbnail',
			'excerpt',
			'comments',
			'trackbacks',
			'revisions',
			'custom-fields'
		),
		'labels' => array(
			'name' => __( 'Movies', 'simple-kino' ),
			'singular_name' => __( 'Movie', 'simple-kino' ),
			'add_new' => __( 'Add New', 'simple-kino' ),
			'add_new_item' => __( 'Add New Movie', 'simple-kino' ),
			'edit' => __( 'Edit', 'simple-kino' ),
			'edit_item' => __( 'Edit Movie', 'simple-kino' ),
			'new_item' => __( 'New Movie', 'simple-kino' ),
			'view' => __( 'View Movie', 'simple-kino' ),
			'view_item' => __( 'View Movie', 'simple-kino' ),
			'search_items' => __( 'Search Movies', 'simple-kino' ),
			'not_found' => __( 'No movies found', 'simple-kino' ),
			'not_found_in_trash' => __( 'No movies found in Trash', 'simple-kino' ) 
		),
		'has_archive' => true,
		/* Global control over capabilities. @link http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types */
		'capability_type' => 'simple_kino_movie',

		/* Specific control over capabilities. */
		'capabilities' => array(
			'edit_posts' => 'edit_simple_kino_movies',
			'edit_others_posts' => 'edit_others_simple_kino_movies',
			'publish_posts' => 'publish_simple_kino_movies',
			'read_private_posts' => 'read_private_simple_kino_movies',
			'delete_posts' => 'delete_simple_kino_movies',
			'delete_others_posts' => 'delete_others_simple_kino_movie',
			'edit_post' => 'edit_simple_kino_movie',
			'delete_post' => 'delete_simple_kino_movie',
			'read_post' => 'read_simple_kino_movie',
		),
		//'map_meta_cap' => true,
	);
	
	/* Register the showcase post type with showcase arguments. */
	 register_post_type( 'movie', $movie_args );	
}

function simple_kino_map_meta_cap( $caps, $cap, $user_id, $args ) {

	/* If editing, deleting, or reading a movie, get the post and post type object. */
	if ( 'edit_simple_kino_movie' == $cap || 'delete_simple_kino_movie' == $cap || 'read_simple_kino_movie' == $cap ) {
		$post = get_post( $args[0] );
		$post_type = get_post_type_object( $post->post_type );

		/* Set an empty array for the caps. */
		$caps = array();
	}

	/* If editing a movie, assign the required capability. */
	if ( 'edit_simple_kino_movie' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->edit_posts;
		else
			$caps[] = $post_type->cap->edit_others_posts;
	}

	/* If deleting a movie, assign the required capability. */
	elseif ( 'delete_simple_kino_movie' == $cap ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->delete_posts;
		else
			$caps[] = $post_type->cap->delete_others_posts;
	}

	/* If reading a private movie, assign the required capability. */
	elseif ( 'read_simple_kino_movie' == $cap ) {

		if ( 'private' != $post->post_status )
			$caps[] = 'read';
		elseif ( $user_id == $post->post_author )
			$caps[] = 'read';
		else
			$caps[] = $post_type->cap->read_private_posts;
	}

	/* Return the capabilities required by the user. */
	return $caps;
	
}

/**
 * Flush permalinks when plugin is activated.
 * @link http://codex.wordpress.org/Function_Reference/register_post_type#Flushing_Rewrite_on_Activation
 * @since 0.1.0
 */
function simple_kino_rewrite_flush() {
   
   /* First, we "add" the custom post type movie via the above written function. */
    simple_kino_register_my_post_type_movies();

    /* This is *only* done during plugin activation hook. You should *NEVER EVER* do this on every page load!! */
    flush_rewrite_rules();
	
}

/*
 * Register taxonomies for movie post type. 
 * @since 0.1.0
 */
function simple_kino_register_my_taxonomy() {
	
	/* This is for movietimes like Playing now or Upcoming movies. */
	$movietimes_args = array(
		'hierarchical' => true, // like now or upcoming movies
		'query_var' => 'movietimes', 
		'show_tagcloud' => true,
		'labels' => array(
			'name' => __( 'Movietimes', 'simple-kino' ),
			'singular_name' => __( 'Movietime', 'simple-kino' ),
			'search_items' => __( 'Search Movietimes', 'simple-kino' ),
			'all_items' => __( 'All Movietimes', 'simple-kino' ),
			'edit_item' => __( 'Edit Movietime', 'simple-kino' ),
			'update_item' => __( 'Update Movietime', 'simple-kino' ),
			'add_new_item' => __( 'Add New Movietime', 'simple-kino' ),
			'new_item_name' => __( 'New Movietime Name', 'simple-kino' ),
		),
		'capabilities' => array(
			'manage_terms' => 'manage_simple_kino_taxonomies',
			'edit_terms' => 'edit_simple_kino_taxonomies',
			'delete_terms' => 'delete_simple_kino_taxonomies',
			'assign_terms' => 'edit_simple_kino_movies',
		),
);

/* This is for Genre */
	$genre_args = array(
		'hierarchical' => false,
		'query_var' => 'genre',
		'show_tagcloud' => true,
		'labels' => array(
			'name' => __( 'Genre', 'simple-kino' ),
			'singular_name' => __( 'Genre', 'simple-kino' ),
			'search_items' => __( 'Search Genres', 'simple-kino' ),
			'popular_items' => __( 'Popular Genres', 'simple-kino' ),
			'all_items' => __( 'All Genres', 'simple-kino' ),
			'edit_item' => __( 'Edit Genre', 'simple-kino' ),
			'update_item' => __( 'Update Genre', 'simple-kino' ),
			'add_new_item' => __( 'Add New Genre', 'simple-kino' ),
			'new_item_name' => __( 'New Genre Name', 'simple-kino' ),
			'separate_items_with_commas' => __( 'Sepatate Genres with commas', 'simple-kino' ),
			'add_or_remove_items' => __( 'Add or remove Genres', 'simple-kino' ),
			'choose_from_most_used' => __( 'Choose from the most popular Genres', 'simple-kino' ),
		),
		'capabilities' => array(
			'manage_terms' => 'manage_simple_kino_taxonomies',
			'edit_terms' => 'edit_simple_kino_taxonomies',
			'delete_terms' => 'delete_simple_kino_taxonomies',
			'assign_terms' => 'edit_simple_kino_movies',
		),
);	

/* This is for Director */
	$director_args = array(
		'hierarchical' => false,
		'query_var' => 'director',
		'show_tagcloud' => true,
		'labels' => array(
			'name' => __( 'Director', 'simple-kino' ),
			'singular_name' => __( 'Director', 'simple-kino' ),
			'search_items' => __( 'Search Directors', 'simple-kino' ),
			'popular_items' => __( 'Popular Directors', 'simple-kino' ),
			'all_items' => __( 'All Directors', 'simple-kino' ),
			'edit_item' => __( 'Edit Director', 'simple-kino' ),
			'update_item' => __( 'Update Director', 'simple-kino' ),
			'add_new_item' => __( 'Add New Director', 'simple-kino' ),
			'new_item_name' => __( 'New Director Name', 'simple-kino' ),
			'separate_items_with_commas' => __( 'Sepatate Directors with commas', 'simple-kino' ),
			'add_or_remove_items' => __( 'Add or remove Directors', 'simple-kino' ),
			'choose_from_most_used' => __( 'Choose from the most popular Directors', 'simple-kino' ),
		),
		'capabilities' => array(
			'manage_terms' => 'manage_simple_kino_taxonomies',
			'edit_terms' => 'edit_simple_kino_taxonomies',
			'delete_terms' => 'delete_simple_kino_taxonomies',
			'assign_terms' => 'edit_simple_kino_movies',
		),		
);	

/* This is for Actors */
	$actors_args = array(
		'hierarchical' => false,
		'query_var' => 'actors',
		'show_tagcloud' => true,
		'labels' => array(
			'name' => __( 'Actor', 'simple-kino' ),
			'singular_name' => __( 'Actor', 'simple-kino' ),
			'search_items' => __( 'Search Actors', 'simple-kino' ),
			'popular_items' => __( 'Popular Actors', 'simple-kino' ),
			'all_items' => __( 'All Actors', 'simple-kino' ),
			'edit_item' => __( 'Edit Actor', 'simple-kino' ),
			'update_item' => __( 'Update Actor', 'simple-kino' ),
			'add_new_item' => __( 'Add New Actor', 'simple-kino' ),
			'new_item_name' => __( 'New Actor Name', 'simple-kino' ),
			'separate_items_with_commas' => __( 'Sepatate Actors with commas', 'simple-kino' ),
			'add_or_remove_items' => __( 'Add or remove Actors', 'simple-kino' ),
			'choose_from_most_used' => __( 'Choose from the most popular Actors', 'simple-kino' ),
		),
		'capabilities' => array(
			'manage_terms' => 'manage_simple_kino_taxonomies',
			'edit_terms' => 'edit_simple_kino_taxonomies',
			'delete_terms' => 'delete_simple_kino_taxonomies',
			'assign_terms' => 'edit_simple_kino_movies',
		),
);	

/* This is for Age limit */
	$age_limit_args = array(
		'hierarchical' => false,
		'query_var' => 'agelimit',
		'show_tagcloud' => true,
		'labels' => array(
			'name' => __( 'Age limit', 'simple-kino' ),
			'singular_name' => __( 'Age limit', 'simple-kino' ),
			'search_items' => __( 'Search Age limits', 'simple-kino' ),
			'popular_items' => __( 'Popular Age limits', 'simple-kino' ),
			'all_items' => __( 'All Age limits', 'simple-kino' ),
			'edit_item' => __( 'Edit Age limit', 'simple-kino' ),
			'update_item' => __( 'Update Age limit', 'simple-kino' ),
			'add_new_item' => __( 'Add New Age limit', 'simple-kino' ),
			'new_item_name' => __( 'New Age limit Name', 'simple-kino' ),
			'separate_items_with_commas' => __( 'Sepatate Age limits with commas', 'simple-kino' ),
			'add_or_remove_items' => __( 'Add or remove Age limits', 'simple-kino' ),
			'choose_from_most_used' => __( 'Choose from the most popular Age limits', 'simple-kino' ),
		),
		'capabilities' => array(
			'manage_terms' => 'manage_simple_kino_taxonomies',
			'edit_terms' => 'edit_simple_kino_taxonomies',
			'delete_terms' => 'delete_simple_kino_taxonomies',
			'assign_terms' => 'edit_simple_kino_movies',
		),
);		
			
/* Register taxonomies */
	register_taxonomy( 'movietimes', array( 'movie' ), $movietimes_args );	
	register_taxonomy( 'genre', array( 'movie' ), $genre_args );
	register_taxonomy( 'director', array( 'movie' ), $director_args );	
	register_taxonomy( 'actors', array( 'movie' ), $actors_args );
	register_taxonomy( 'agelimit', array( 'movie' ), $age_limit_args );
}

/**
 * Adds metabox in movie post type. Showtimes, trailer, movie website, length and ticket price 
 * @since 0.1.0
 */
function simple_kino_create_meta_boxes() {
	
	add_meta_box( 'simple-movie-info', __( 'Movie Information', 'simple-kino' ), 'simple_kino_display_meta_boxes', 'movie', 'normal', 'high' );

}

/** 
 * Display metabox. Note that $post object is passed as a parameter, so that we can get post ID. 
 * @since 0.1.0
 */
function simple_kino_display_meta_boxes( $post ) {
	
	/* Get metadata values if they exist. */
	
	$simple_kino_showtimes = get_post_meta( $post->ID, 'simple_kino_showtimes', 'true' ); // Get showtimes
	$simple_kino_trailer = get_post_meta( $post->ID, 'simple_kino_trailer', 'true' ); // Get trailer
	$simple_kino_website = get_post_meta( $post->ID, 'simple_kino_website', 'true' ); // Get website
	$simple_kino_length = get_post_meta( $post->ID, 'simple_kino_length', 'true' ); // Get length
	$simple_kino_price = get_post_meta( $post->ID, 'simple_kino_price', 'true' ); // Get ticket price
	$simple_kino_premiere = get_post_meta( $post->ID, 'simple_kino_premiere', 'true' ); // Get premiere date
	
	/* Display metadata in table. */
	 
	/* Adds WP nonce field: http://codex.wordpress.org/Function_Reference/wp_nonce_field */
	wp_nonce_field( 'simple-kino-nonce', 'simple-kino-nonce-name' );
	
	/* wp_editor settings */
	$simple_kino_editor_settings = array(
	'wpautop' => true,
	'media_buttons' => false,
	'textarea_rows' => 8,
	'tinymce' => array(
		'theme_advanced_buttons1' => 'bold, bullist, numlist , ordered_list, separator, undo, redo',
		'theme_advanced_buttons2' => '',
		'theme_advanced_buttons3' => '',
		'theme_advanced_buttons4' => ''
	),
	'quicktags' => array(
		'buttons' => 'b,ul,ol,li,close'
	)
);
	
	?>
	<table class="form-table">
    	<tr>
			<th class="simple-kino-label"><label for="simple-kino-movietimes"><?php  _e( 'Movie showtimes:', 'simple-kino' ) ?></label></th>
			<td class="simple-kino-field"><?php wp_editor( $simple_kino_showtimes, 'simple_kino_showtimes', $simple_kino_editor_settings ); ?>
			<br /> <?php _e('Only ul, ol, li, p and strong html tags are accepted.', 'simple-kino' ); ?>
			</td>
		</tr>
		
		<tr>
			<th class="simple-kino-label"><label for="simple-kino-trailer"><?php  _e( 'Movie Trailer website:', 'simple-kino' ) ?></label></th>
			<td class="simple-kino-field"><input class="widefat" type="text" size="100" name="simple_kino_trailer" value="<?php echo esc_attr( $simple_kino_trailer ) ?>" />
			<br /> <?php _e('Remember to include http://', 'simple-kino' ); ?>
			</td>
		</tr>
		
    	<tr>
			<th class="simple-kino-label"><label for="simple-kino-website"><?php  _e( 'Movie website:', 'simple-kino' ) ?></label></th>
			<td class="simple-kino-field"><input class="widefat" type="text" size="100" name="simple_kino_website" value="<?php echo esc_attr( $simple_kino_website ) ?>" />
			<br /> <?php _e('Remember to include http://', 'simple-kino' ); ?>
		</td>
		</tr>
		
    	<tr>
			<th class="simple-kino-label"><label for="simple-kino-length"><?php  _e( 'Movie duration:', 'simple-kino' ) ?></label></th>
			<td class="simple-kino-field"><input class="widefat" type="text" size="100" name="simple_kino_length" value="<?php echo esc_attr( $simple_kino_length ) ?>" />
			<br /> <?php _e('For example 2h 20min.', 'simple-kino' ); ?>
		</td>
		</tr>
		
    	<tr>
			<th class="simple-kino-label"><label for="simple-kino-price"><?php  _e( 'Movie ticket price:', 'simple-kino' ) ?></label></th>
			<td class="simple-kino-field"><input class="widefat" type="text" size="100" name="simple_kino_price" value="<?php echo esc_attr( $simple_kino_price ) ?>" />
			<br /> <?php _e('For example 12 &euro;, students 10 &euro;', 'simple-kino' ); ?>
			</td>
		</tr>
		
		<tr>
			<th class="simple-kino-label"><label for="simple-kino-premiere"><?php  _e( 'Premiere:', 'simple-kino' ) ?></label></th>
			<td class="simple-kino-field"><input class="widefat simple-kino-premiere" type="text" size="100" name="simple_kino_premiere" value="<?php if ( !empty( $simple_kino_premiere ) ) echo date_i18n( __( 'Y/m/d', 'simple-kino' ), esc_attr( $simple_kino_premiere ) ) ?>" />
			<br /> <?php _e('For upcoming movies premiere date', 'simple-kino' ); ?>
			</td>
		</tr>
	</table>
    <?php
}

/** 
 * Save metabox data. Note that pass $post_id variable as a parameter. The post ID is used when saving metadata. 
 * @since 0.1.0
 */
function simple_kino_save_meta_boxes( $post_id ) {

	global $post;
	
	/* Verify nonce. */
	if ( !wp_verify_nonce( $_POST['simple-kino-nonce-name'], 'simple-kino-nonce' ) )
		return $post_id;
	
	/* Get the post type object. */
    $post_type = get_post_type_object( $post->post_type );
	
	/* Checks if current user can edit this movie. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id; 
		
	/* Get meta keys in an array. */
	$movie_meta_info_all = array( 'simple_kino_showtimes', 'simple_kino_trailer', 'simple_kino_website', 'simple_kino_length', 'simple_kino_price' );

	/* Loop through all of post meta box arguments. */
	foreach ( $movie_meta_info_all as $movie_meta_info ) {

		/* Get the meta value of the custom field key. */
		$meta_value = get_post_meta( $post_id, $movie_meta_info, true );
		
		/* Get the meta value the user input and sanitaze info. */
		
		if ( $movie_meta_info == 'simple_kino_showtimes' )
			$new_meta_value = simple_kino_check_showtimes( $_POST[ $movie_meta_info ] );
		
		if ( $movie_meta_info == 'simple_kino_website' || $movie_meta_info == 'simple_kino_trailer' )
			$new_meta_value = esc_url_raw( $_POST[ $movie_meta_info ] );
			
		if ( $movie_meta_info == 'simple_kino_length' || $movie_meta_info == 'simple_kino_price' )
			$new_meta_value = sanitize_text_field( $_POST[ $movie_meta_info ] );
			
		//if ( $movie_meta_info == 'simple_kino_premiere' )
			//$new_meta_value = strtotime( sanitize_text_field( $_POST[ $movie_meta_info ] ) );
				
		/* If a new meta value was added and there was no previous value, add it. */
		if ( $new_meta_value && '' == $meta_value )
			add_post_meta( $post_id, $movie_meta_info, $new_meta_value, true );

		/* If the new meta value does not match the old value, update it. */
		elseif ( $new_meta_value && $new_meta_value != $meta_value )
			update_post_meta( $post_id, $movie_meta_info, $new_meta_value );

		/* If there is no new meta value but an old value exists, delete it. */
		elseif ( '' == $new_meta_value && $meta_value )
			delete_post_meta( $post_id, $movie_meta_info, $meta_value );
	}	
	
	/* Save data for meta_key simple_kino_premiere. Add 0, if user adds nothing. */
	$meta_key = 'simple_kino_premiere';

	/* Get the meta value of the custom field key. */
	$meta_value = get_post_meta( $post_id, $meta_key, true );
	
	/* New meta value. */
	$new_meta_value = strtotime( sanitize_text_field( $_POST[ $meta_key ] ) );
	
	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );
	
	/* If there is no new meta value, add 0 so that all movies have some premiere 'date'. */
	elseif ( $new_meta_value == '' && '' == $meta_value ) {
		$new_meta_value = 0;
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );
	}
	
	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );
	
	/* If there is no new meta value but an old value exists, add 0. */
	elseif ( '' == $new_meta_value && $meta_value ) {
		$new_meta_value = 0;
		update_post_meta( $post_id, $meta_key, $new_meta_value );
	}
	
}

/**
 * Check showtimes. Only accepts certain html tags 
 * @since 0.1.0
 */
function simple_kino_check_showtimes( $showtimes_allowed ) {
	
	/* Allowed tags and attributes */
	$allowed_s = array (
	'ul' => array(),
	'ol' => array(),
	'li' => array(),
	'p' => array(),
	'strong' => array()
	);
	
	$showtimes_allowed = wp_kses( $showtimes_allowed, $allowed_s );
	
	return $showtimes_allowed;
}

/** 
 * Add datepicker js.
 * @since 0.1.0
 */
function simple_kino_load_admin_scripts( $hook ) {

	global $post_type;

	/* Return if post_type is not movie. */
	if( 'movie' != $post_type )
		return; 
 
	/* Add datepicker. */
	wp_enqueue_script( 'datepicker-settings', SIMPLE_KINO_URL . 'js/datepicker/datepicker-settings.js', array( 'jquery-ui-datepicker' ), '30052012', true );
	
	/* Localize dateformat. @link: http://pippinsplugins.com/use-wp_localize_script-it-is-awesome */
	wp_localize_script('datepicker-settings', 'datepicker_settings_vars', array(
			'dateformat' => __( 'yy/m/d', 'simple-kino' )
		)
	);
	
	/* Translations. */
	if( get_bloginfo( 'language' ) == 'fi' ) wp_enqueue_script( 'datepicker-fi', SIMPLE_KINO_URL . 'js/datepicker/languages/jquery.ui.datepicker-fi.js', array( 'jquery-ui-datepicker' ), '30052012', true );
	if( get_bloginfo( 'language' ) == 'sv_SE' ) wp_enqueue_script( 'datepicker-sv-se', SIMPLE_KINO_URL . 'js/datepicker/languages/jquery.ui.datepicker-sv.js', array( 'jquery-ui-datepicker' ), '30052012', true );
	
	/* Add styles to datepicker. @link: http://jqueryui.com/download/?themeParams=%3FffDefault%3DVerdana%2CArial%2Csans-serif%26fwDefault%3Dnormal%26fsDefault%3D1.1em%26cornerRadius%3D4px%26bgColorHeader%3Dcccccc%26bgTextureHeader%3D03_highlight_soft.png%26bgImgOpacityHeader%3D75%26borderColorHeader%3Daaaaaa%26fcHeader%3D222222%26iconColorHeader%3D222222%26bgColorContent%3Dffffff%26bgTextureContent%3D01_flat.png%26bgImgOpacityContent%3D75%26borderColorContent%3Daaaaaa%26fcContent%3D222222%26iconColorContent%3D222222%26bgColorDefault%3De6e6e6%26bgTextureDefault%3D02_glass.png%26bgImgOpacityDefault%3D75%26borderColorDefault%3Dd3d3d3%26fcDefault%3D555555%26iconColorDefault%3D888888%26bgColorHover%3Ddadada%26bgTextureHover%3D02_glass.png%26bgImgOpacityHover%3D75%26borderColorHover%3D999999%26fcHover%3D212121%26iconColorHover%3D454545%26bgColorActive%3Dffffff%26bgTextureActive%3D02_glass.png%26bgImgOpacityActive%3D65%26borderColorActive%3Daaaaaa%26fcActive%3D212121%26iconColorActive%3D454545%26bgColorHighlight%3Dfbf9ee%26bgTextureHighlight%3D02_glass.png%26bgImgOpacityHighlight%3D55%26borderColorHighlight%3Dfcefa1%26fcHighlight%3D363636%26iconColorHighlight%3D2e83ff%26bgColorError%3Dfef1ec%26bgTextureError%3D02_glass.png%26bgImgOpacityError%3D95%26borderColorError%3Dcd0a0a%26fcError%3Dcd0a0a%26iconColorError%3Dcd0a0a%26bgColorOverlay%3Daaaaaa%26bgTextureOverlay%3D01_flat.png%26bgImgOpacityOverlay%3D0%26opacityOverlay%3D30%26bgColorShadow%3Daaaaaa%26bgTextureShadow%3D01_flat.png%26bgImgOpacityShadow%3D0%26opacityShadow%3D30%26thicknessShadow%3D8px%26offsetTopShadow%3D-8px%26offsetLeftShadow%3D-8px%26cornerRadiusShadow%3D8px */
	wp_enqueue_style( 'jquery.ui.theme', SIMPLE_KINO_URL . 'css/datepicker/jquery-ui-1.8.20.custom.css' );

}

/**
 * Add new image size for movies, it's called movie-thumbnail and size is 134*186.
 * @link http://codex.wordpress.org/Function_Reference/add_image_size
 * @since 0.1.0
 */
function simple_kino_register_image_sizes() {
	
	add_image_size( 'movie-thumbnail', 134, 186, true );

}

/* Order Movies. */
function simple_kino_custom_post_types_admin_order( $query ) {  
   
    // Get the post type from the query  
    $post_type = $query->query['post_type']; 
	
	/* Order if is custom post type movie or is taxonomy movietimes, genre, director, actors or agelimit. */
	if ( $query->is_main_query() && ( 'movie' == $post_type or is_tax( 'movietimes' ) or is_tax( 'genre' ) or is_tax( 'director' ) or is_tax( 'actors' ) or is_tax( 'agelimit' ) ) ) {
		$query->set( 'orderby', 'title' );
		//$query->set( 'meta_key', 'simple_kino_premiere' );
		//$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'asc' );
	}

	return $query; 
}  

/**
 * Add functions in Hybrid Tab plugin: content(), showtimes, gallery. 
 * @since 0.1.0
 */
function simple_kino_create_my_custom_tabs() {
	
	if ( function_exists( 'register_hybrid_tab' ) )
		register_hybrid_tab( 'movie_synopsis', array( 'label' => __( 'Movie Synopsis', 'simple-kino' ), 'callback' => 'simple_kino_tab_movie_synopsis' ) );
	
	if ( function_exists( 'register_hybrid_tab' ) )
		register_hybrid_tab( 'movie_showtimes', array( 'label' => __( 'Movie Showtimes', 'simple-kino' ), 'callback' => 'simple_kino_tab_movie_showtimes' ) );
		
	if ( function_exists( 'register_hybrid_tab' ) )
		register_hybrid_tab( 'movie_gallery', array( 'label' => __( 'Movie Gallery', 'simple-kino' ), 'callback' => 'simple_kino_tab_movie_gallery' ) );
		
}

// Movie content tab
function simple_kino_tab_movie_synopsis() {
	the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'simple-kino' ) ); 
}

// Movie showtimes tab
function simple_kino_tab_movie_showtimes() {
	
	global $post; // $post->ID won't work without this
	$simple_kino_showtimes = get_post_meta( $post->ID, 'simple_kino_showtimes', 'true' );
	?>
		<div class="simple-kino-showtimes"><?php echo $simple_kino_showtimes; ?></div>
	<?php
}

// Movie picture gallery tab
function simple_kino_tab_movie_gallery() {
	
	/* Gallery shortcode, using Justin's Gleaner Gallery Plugin. */
    echo do_shortcode( '[gallery columns="4"]' );
}

/**
 * Shortcode function [movie-information]. 
 * @since 0.1.0
 */
function simple_kino_movie_info_shortcode() {
	
	/* Get movies meta. */
	$simple_kino_trailer = get_post_meta( get_the_ID(), 'simple_kino_trailer', 'true' ); // Get trailer
	$simple_kino_website = get_post_meta( get_the_ID(), 'simple_kino_website', 'true' ); // Get website
	$simple_kino_length = get_post_meta( get_the_ID(), 'simple_kino_length', 'true' ); // Get length
	$simple_kino_price = get_post_meta( get_the_ID(), 'simple_kino_price', 'true' ); // Get ticket price
	$simple_kino_premiere = get_post_meta( get_the_ID(), 'simple_kino_premiere', 'true' ); // Get ticket premiere
						
	/* Taxonomies Genre, Director, Actors, Age limit. */
	$movie_info .= '<div class="movie-information">';
	
	$movie_info .= '<p>' . get_the_term_list( get_the_ID(), 'genre', __('Genre:&nbsp;', 'simple-kino' ) , ', ', '') . '</p>'; 
	$movie_info .= '<p>' . get_the_term_list( get_the_ID(), 'director', __('Director:&nbsp;', 'simple-kino' ) , ', ', '') . '</p>'; 
	$movie_info .= '<p>' . get_the_term_list( get_the_ID(), 'actors', __('Actors:&nbsp;', 'simple-kino' ) , ', ', '') . '</p>'; 
	
	/* Open <p> for age, length, trailer and movie website .*/
	$movie_info .= '<p>' ;
	
	$movie_info .= get_the_term_list( get_the_ID(), 'agelimit', __('Age limit:&nbsp;', 'simple-kino' ) , ', ', ''); 
	
	/* Do not print empty strings. */
	if( !empty( $simple_kino_length ) ) {
		$movie_info .= ', ' . __( 'Movie duration:', 'simple-kino' ) . ' ' ;
		$movie_info .= $simple_kino_length;
	}
	
	if( !empty( $simple_kino_trailer ) ) {
		$movie_info .= ', <a href="' . $simple_kino_trailer . '" title="Trailer">' ;
		$movie_info .= __( 'Trailer', 'simple-kino' );
		$movie_info .= '</a>' ;
	}
	
	if( !empty( $simple_kino_website ) ) {
		$movie_info .= ', <a href="' . $simple_kino_website . '" title="Website">' ;
		$movie_info .= __( 'Website', 'simple-kino' );
		$movie_info.= '</a>';
	}
	
	/* Close <p> for age, length, trailer and movie website. */
	$movie_info .= '</p>' ; 
	
	/* Do not print empty strings. */
	if( !empty( $simple_kino_price ) ) {
		$movie_info .= '<p>' . __( 'Tickets:', 'simple-kino' ) . ' ' ;
		$movie_info .= $simple_kino_price . '</p>' ;
	}
	
	/* Do not print empty strings. */
	if( !empty( $simple_kino_premiere ) ) {
		$movie_info .= '<p>' . __( 'Premiere:', 'simple-kino' ) . ' ' ;
		$movie_info .= date( __( 'Y/m/d', 'simple-kino' ), $simple_kino_premiere ) . '</p>' ;
	}
	
	/* Close movie-information div.  */
	$movie_info.= '</div>' ;
	
	/* Return movie info. */
	return $movie_info;
}

/**
 * Shortcode function [movie-showtimes].
 * @since 0.1.0
 */
function simple_kino_movie_showtimes_shortcode() {
	
	/* Get movie showtimes */
	$simple_kino_showtimes = get_post_meta( get_the_ID(), 'simple_kino_showtimes', 'true' );
	
	if( !empty( $simple_kino_showtimes ) ) {
		$movie_showtimes .='<h3>' . __( 'Movie Showtimes', 'simple-kino' ) . '</h3>';
		$movie_showtimes .= '<div class="simple-kino-showtimes">'  . $simple_kino_showtimes . ' </div>';
	}
	
	/* Return Showtimes */
	return $movie_showtimes;
}

/**
 * Filter columns for custom post type movie.
 * @link http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
 * @since 0.1.0
 */
function simple_kino_edit_movie_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Movie', 'simple-kino' ),
		'movietimes' => __( 'Movietimes', 'simple-kino' ),
		'duration' => __( 'Duration', 'simple-kino' ),
		'comments' => __( 'Comments', 'simple-kino' ),
		'date' => __( 'Date', 'simple-kino' )
	);

	return $columns;
}

/**
 * Add content to columns for custom post type movie.
 * @link http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
 * @since 0.1.0
 */
function simple_kino_manage_movie_columns( $column, $post_id ) {
	global $post;

	switch( $column ) {

		/* If displaying the 'duration' column. */
		case 'duration' :

			/* Get the post meta. */
			$simple_kino_duration = get_post_meta( $post_id, 'simple_kino_length', true );

			/* If no duration is found, output a default message. */
			if ( empty( $simple_kino_duration ) )
				echo __( 'Unknown', 'simple-kino' );

			/* If there is a duration, append 'minutes' to the text string. */
			else
				printf( __( '%s', 'simple-kino' ), $simple_kino_duration );

			break;

		/* If displaying the 'movietimes' column. */
		case 'movietimes' :

			/* Get the movietimes for the post. */
			$terms = get_the_terms( $post_id, 'movietimes' );

			/* If terms were found. */
			if ( !empty( $terms ) ) {

				$out = array();

				/* Loop through each term, linking to the 'edit posts' page for the specific term. */
				foreach ( $terms as $term ) {
					$out[] = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'movietimes' => $term->slug ), 'edit.php' ) ),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'movietimes', 'display' ) )
					);
				}

				/* Join the terms, separating them with a comma. */
				echo join( ', ', $out );
			}

			/* If no terms were found, output a default message. */
			else {
				_e( 'No Movietimes', 'simple-kino' );
			}

			break;

		/* Just break out of the switch statement for everything else. */
		default :
			break;
	}
}

/**
 * add filter to ensure the text Movie, or movie, is displayed when user updates a movie.
 * @since 0.1.0
 */
function simple_kino_movie_updated_messages( $messages ) {
  global $post, $post_ID;

  $messages['movie'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Movie updated. <a href="%s">View movie</a>', 'simple-kino' ) ,esc_url( get_permalink( $post_ID ) ) ),
    2 => __('Custom field updated.', 'simple-kino' ),
    3 => __('Custom field deleted.', 'simple-kino' ),
    4 => __('Movie updated.', 'simple-kino' ),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Movie restored to revision from %s', 'simple-kino' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Movie published. <a href="%s">View movie</a>', 'simple-kino' ), esc_url( get_permalink( $post_ID ) ) ),
    7 => __('Movie saved.'),
    8 => sprintf( __('Movie submitted. <a target="_blank" href="%s">Preview movie</a>', 'simple-kino' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Movie scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview movie</a>', 'simple-kino' ),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i', 'simple-kino' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Movie draft updated. <a target="_blank" href="%s">Preview movie</a>', 'simple-kino' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
  );

  return $messages;
}

?>