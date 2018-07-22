<?php
/*
Plugin Name: Order by Analytics
Version: 
Plugin URI: 
Author: 
Author URI: 
Description: 
Text Domain: 
Domain Path: 
*/


add_action( 'save_post', 'update_analytics_views_default' );
add_action( 'init', 'update_analytics_views_meta' );
add_action( 'pre_get_posts', 'modify_default_query' );



function modify_default_query( $query ) {
	//this doesn't include items that don't have the meta_key
	if ( !is_admin() && is_home() && $query->is_main_query()) {
		$query->set( 'meta_key', 'analytics_views' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'DESC' );
	
		//$meta_query = [];
		//$meta_query['relation'] = 'OR';

		// $meta_query[] = [
		// 	'key'=>'analytics_views',
		// 	'compare'=>'NOT EXISTS'
		// ];

		// $meta_query[] = [
		// 	'key'=>'analytics_views',
		// 	'orderby'=>'meta_value_num',
		// 	'order'=>'DESC'
		// ];

		// $query->set( 'meta_query', [$meta_query] );

	}

	return $query;
}



function update_analytics_views_default( $post_id ) {

	// If this is just a revision, don't send the email.
	if ( wp_is_post_revision( $post_id ) )
		return;

	$is_empty = get_post_meta( $post_id, 'analytics_views', true );
	if ( empty( $is_empty )) {
		update_post_meta( $post_id, 'analytics_views', 0 );
	}
}

function update_analytics_views_meta() {

	if ( false === ( $analytics_views_data = get_transient( 'analytics_views_data' ) ) ) {
		// It wasn't there, so regenerate the data and save the transient

		if (!class_exists('GADWP_GAPI_Controller')) {
			return;
		}


		try {


			$gadwp = GADWP();
			$gadwp->gapi_controller = new GADWP_GAPI_Controller();
			$metric = '';

			//https://www.googleapis.com/analytics/v3/data/ga?ids=ga%3A53974305&start-date=30daysAgo&end-date=yesterday&metrics=ga%3Apageviews&dimensions=ga%3ApageTitle&sort=-ga%3Apageviews

			$projectId = 53974305;
			$from = '30daysAgo';
			$to = 'yesterday';
			$filter = '';
			$metric = 'pageviews';
			$blah = $gadwp->gapi_controller->get( $projectId, 'contentpages', $from, $to, $filter, $metric );
			//write_log($blah);

			foreach ( $blah as $item ) {
				preg_match( '/([^&]+) - Design Methods and Processes/', $item[0], $matches );
				if ( !empty( $matches[1] ) ) {
					$post = get_page_by_title( $matches[1], $output = OBJECT, $post_type = 'post' );
					if ( !empty( $post ) ) {
						update_post_meta( $post->ID, 'analytics_views', $item[1] );
					}
				}

			}

			$analytics_views_data = $blah;

			set_transient( 'analytics_views_data', $analytics_views_data, 12 * HOUR_IN_SECONDS );

		} catch (Exception $e) {
			write_log($e);
		}
	}



}
