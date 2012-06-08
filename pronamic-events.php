<?php
/*
Plugin Name: Pronamic Events
Plugin URI: http://pronamic.eu/wordpress/events/
Description: This plugin add some basic Event functionality to WordPress
Version: 1.0
Requires at least: 3.0
Author: Pronamic
Author URI: http://pronamic.eu/
License: GPL
*/

/**
 * Flush data
 */
function pronamic_events_rewrite_flush() {
    pronamic_events_init();

    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'pronamic_events_rewrite_flush');

////////////////////////////////////////////////////////////

/**
 * Register post type
 */
function pronamic_events_init() {
	$relPath = dirname(plugin_basename(__FILE__)) . '/languages/';

	load_plugin_textdomain('pronamic_events', false, $relPath);

	register_post_type('pronamic_event', array(
		'labels' => array(
			'name' => _x('Events', 'post type general name', 'pronamic_events') , 
			'singular_name' => _x('Event', 'post type singular name', 'pronamic_events') , 
			'add_new' => _x('Add New', 'event', 'pronamic_events') , 
			'add_new_item' => __('Add New Event', 'pronamic_events') , 
			'edit_item' => __('Edit Event', 'pronamic_events') , 
			'new_item' => __('New Event', 'pronamic_events') , 
			'view_item' => __('View Event', 'pronamic_events') , 
			'search_items' => __('Search Events', 'pronamic_events') , 
			'not_found' =>  __('No events found', 'pronamic_events') , 
			'not_found_in_trash' => __('No events found in Trash', 'pronamic_events') , 
			'parent_item_colon' => __('Parent Event:', 'pronamic_events') ,
			'menu_name' => __('Agenda', 'pronamic_events') , 
		) , 
		'public' => true , 
		'publicly_queryable' => true , 
		'show_ui' => true , 
		'show_in_menu' => true ,  
		'query_var' => true , 
		'rewrite' => true , 
		'capability_type' => 'post' , 
		'has_archive' => true , 
		'rewrite' => array('slug' => 'agenda') ,
		'menu_icon' =>  plugins_url('/admin/icons/event.png', __FILE__) ,
		'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt')
	));

	register_taxonomy('pronamic_event_category', 'pronamic_event' , 
		array( 
			'hierarchical' => true , 
			'labels' => array(
				'name' => _x('Event categories', 'class general name', 'pronamic_events') , 
				'singular_name' => _x('Event category', 'class singular name', 'pronamic_events') , 
				'search_items' =>  __('Search Event categories', 'pronamic_events') , 
				'all_items' => __('All Event categories', 'pronamic_events') , 
				'parent_item' => __('Parent Event category', 'pronamic_events') , 
				'parent_item_colon' => __('Parent Event category:', 'pronamic_events') , 
				'edit_item' => __('Edit Event category', 'pronamic_events') ,  
				'update_item' => __('Update Event category', 'pronamic_events') , 
				'add_new_item' => __('Add New Event category', 'pronamic_events') , 
				'new_item_name' => __('New Event category Name', 'pronamic_events') , 
				'menu_name' => __('Event categories', 'pronamic_events') 
			) , 
			'show_ui' => true ,
			'query_var' => true
		)
	);
}

add_action('init', 'pronamic_events_init');

////////////////////////////////////////////////////////////

/**
 * Meta boxes
 */
add_action('add_meta_boxes', 'pronamic_events_add_dates_box');
add_action('save_post', 'pronamic_events_save_postdata');

/* Add metaboxes */
function pronamic_events_add_dates_box() {
    add_meta_box( 
        'pronamic_events_dates',
        __( 'Event Dates', 'pronamic_events'),
        'pronamic_events_dates_box',
        'pronamic_event' ,
        'side' ,
        'high'
    );

    add_meta_box( 
        'pronamic_events_location',
        __( 'Event Location', 'pronamic_events'),
        'pronamic_events_location_box',
        'pronamic_event' ,
        'side' ,
        'high'
    );
}

/**
 * Print metaboxes
 */
function pronamic_events_dates_box($post) {
	global $post;

	wp_nonce_field(plugin_basename(__FILE__), 'pronamic_events_nonce');

	if(get_post_meta($post->ID, '_pronamic_start_date', true))
		$start_date = date('d-m-Y', get_post_meta($post->ID, '_pronamic_start_date', true));
	else
		$start_date = '';
	
	if(get_post_meta($post->ID, '_pronamic_end_date', true))
		$end_date = date('d-m-Y', get_post_meta($post->ID, '_pronamic_end_date', true));
	else
		$end_date = $start_date;
	
	?>

	<div>
		<label for="pronamic_start_date"><?php _e('Start date', 'pronamic_events'); ?></label>
		<input type="text" id="pronamic_start_date" name="pronamic_start_date" value="<?php echo $start_date; ?>" size="25" />
	</div>
	
	<div>
		<label for="pronamic_end_date"><?php _e('End date', 'pronamic_events'); ?></label>
		<input type="text" id="pronamic_end_date" name="pronamic_end_date" value="<?php echo $end_date; ?>" size="25" />
	</div>
	
	<?php
}

function pronamic_events_location_box($post) {
	global $post;

	wp_nonce_field(plugin_basename(__FILE__), 'pronamic_events_nonce');
	
	?>

	<input type="text" id="pronamic_location" name="pronamic_location" value="<?php echo get_post_meta($post->ID, '_pronamic_location', true); ?>" size="25" />
	
	<?php
}

/**
 * Save metaboxes
 */
function pronamic_events_save_postdata($post_id) {
	global $post;

	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if(!isset($_POST['pronamic_events_nonce']))
		return;

	if(!wp_verify_nonce( $_POST['pronamic_events_nonce'], plugin_basename(__FILE__)))
		return;

	if(!current_user_can('edit_post', $post->ID))
		return;
		
	// Define timestamps
	$start_date_timestamp = strtotime($_POST['pronamic_start_date']);
	$end_date_timestamp = strtotime($_POST['pronamic_end_date']);
	
	// Save data
	update_post_meta($post->ID, '_pronamic_start_date', $start_date_timestamp);
	update_post_meta($post->ID, '_pronamic_end_date', $end_date_timestamp);
	update_post_meta($post->ID, '_pronamic_location', $_POST['pronamic_location']);
}

////////////////////////////////////////////////////////////

/**
 * Customize query for the archive page
 */
function pronamic_events_query($query) {
	global $wp_the_query;

	if($query->is_main_query() && !is_admin() && $query->is_post_type_archive('pronamic_event')) {
		$meta_query = array(
			array(
				'key' => '_pronamic_end_date' ,
				'value' => strtotime('-1 day') ,
				'compare' => '>'
			)
		);

		$query->set('meta_query', $meta_query);
		$query->set('orderby', 'meta_value_num');
		$query->set('meta_key', '_pronamic_start_date');
		$query->set('order', 'ASC');
	}
}
add_action('pre_get_posts', 'pronamic_events_query');

////////////////////////////////////////////////////////////

/**
 * Return formatted start date
 */
function pronamic_get_the_start_date($format = null) {
	global $post;

	if($format == null) {
		$format = get_option('date_format');
	}

	$start_date = get_post_meta($post->ID, '_pronamic_start_date', true);

	return date_i18n($format, $start_date);
}

/**
 * Echo formatted start date
 */
function pronamic_the_start_date($format = null) {
	echo pronamic_get_the_start_date($format);
}

/**
 * Conditional tag for start date
 */
function pronamic_has_start_date() {
	global $post;

	$start_date = get_post_meta($post->ID, '_pronamic_start_date', true);

	return !empty($start_date);
}

////////////////////////////////////////////////////////////

/**
 * Return formatted end date
 */
function pronamic_get_the_end_date($format = null) {
	global $post;

	if($format == null) {
		$format = get_option('date_format');
	}

	$end_date = get_post_meta($post->ID, '_pronamic_end_date', true);

	return date_i18n($format, $end_date);
}

/**
 * Echo formatted end date
 */
function pronamic_the_end_date($format = null) {
	echo pronamic_get_the_end_date($format);
}

/**
 * Conditional tag for end date
 */
function pronamic_has_end_date() {
	global $post;

	$end_date = get_post_meta($post->ID, '_pronamic_end_date', true);

	return !empty($end_date);
}