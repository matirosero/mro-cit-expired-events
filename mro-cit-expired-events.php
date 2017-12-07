<?php
/**
 * Plugin Name: CIT Expired Events
 * Plugin URI: https://github.com/matirosero/mro-cit-expired-events
 * Description: Change CPT to past events when an event expires.
 * Author: Matilde Rosero
 * Author URI: https://matilderosero.com
 * Version: 0.1.0
 */

//defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


add_action( 'mro_cit_expired_events_cron_hook', 'mro_cit_expired_events_cron_exec', 10, 0 );
function mro_cit_expired_events_cron_exec() {


 	//Run code.

 	$per_page = '-1';



	$query = new WP_Query(
		array(
			'post_type'   => 'tribe_events',
			'post_status' => 'publish',
			'order'       => 'DESC',
			'orderby'     => 'date',
			'posts_per_page' => -1,

			'eventDisplay' => 'past',
		)
	);

	if( ! $query->have_posts() ) :
	    return false;
	else :
		$message = 'Hola, estos son los eventos que acaban de pasar: '."\r\n\r\n";

		while ($query->have_posts()) : $query->the_post();

			//get post id
			$post_id =get_the_ID();

			//get the title
			$the_title = get_the_title();

			//get the link
			$link = get_permalink();

			//get event start date
			$event_date = tribe_get_start_date( null, false, 'Y-m-d H:i:s' );

			//convert to gmt date
			$gmt = get_gmt_from_date( $event_date );


			$message .= $the_title.' ( '.$link.' ) - '.$event_date."\r\n"; //Works till here!


			$convert = array(
		        'ID'            => $post_id, // ID of the post to update
		        'post_date'     => $event_date,
		        'post_date_gmt' => $gmt,
			);

			wp_update_post($convert);

			set_post_type( $post_id, 'cit_past_event' );

		endwhile;

		date_default_timezone_set('America/Costa_Rica');
		$date = date('m/d/Y h:i:s a', time());

		$message = $date."\r\n\r\n".$message;

		// components for our email
		$recepients = 'gekidasa@gmail.com';//roberto@sasso.com
		$subject = 'Eventos recién pasados CIT';

		// let's send it
		mail($recepients, $subject, $message);

	endif;



}



// Install plugin
function mro_cit_expired_events_install() {

	if( !wp_next_scheduled( 'mro_cit_expired_events_cron_hook' ) ) {
    	wp_schedule_event( time(), 'daily', 'mro_cit_expired_events_cron_hook' ); //time() for $timestamp
	}

}
register_activation_hook( __FILE__, 'mro_cit_expired_events_install' );


//unschedule
function mro_cit_expired_events_deactivation() {

	wp_clear_scheduled_hook( 'mro_cit_expired_events_cron_hook' );

	//http://www.smashingmagazine.com/2013/10/16/schedule-events-using-wordpress-cron/

	/*
	Itâ€™s worth noting that wp_clear_scheduled_hook() will remove all events associated with that hook.
	If you want to remove only one event, then use 	wp_unschedule_event() instead; this function requires
	that you pass in the timestamp of the event you wish to remove.
	*/

	//$timestamp = wp_next_scheduled( 'mro_cit_expired_events_cron_hook' ); //$timestamp??
	//wp_unschedule_event($timestamp, 'mro_cit_expired_events_cron_hook' );


}

//ininstall plugin
register_deactivation_hook( __FILE__, 'mro_cit_expired_events_deactivation' );


//check cron jobs: YOUR_SITE_URL/wp-cron.php
function mro_cit_expired_events_print_tasks() {
    echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';
}