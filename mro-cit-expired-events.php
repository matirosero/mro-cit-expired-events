<?php
/**
 * Plugin Name: CIT Expored Events
 * Plugin URI: http://tedxpuravida.org
 * Description: Change CPT to past events when an event expires.
 * Author: Matilde Rosero
 * Author URI: http://matilderosero.com
 * Version: 0.1.0
 */

//defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


add_action( 'mro_cit_expired_events_cron_hook', 'mro_cit_expired_events_cron_exec' );
function mro_cit_expired_events_cron_exec(){


	$message = 'Hola, estos son los cambios en número de vistas. '."\r\n\r\n";


 	//Run code.

 	$per_page = '-1';



	$query = new WP_Query(
		array(
			'post_type' => 'tedxvideo',
			'posts_per_page' => $per_page,
			'tax_query'	=> array(
				array(
					'taxonomy'  => 'video-type',
					'field'     => 'slug',
					'terms'     => 'patrocinador', // exclude media posts in the news-cat custom taxonomy
					'operator'  => 'NOT IN'
				)
	        )

		)
	);


	while ($query->have_posts()) : $query->the_post();

		//get post id
		$post_id =get_the_ID();

		//get the title
		$the_title = get_the_title();

		//Get Youtube video ID
		//$ytvideo_id = get_post_meta($post_id, '_meta_tedx_ytvideo_id', true);

        $video = get_field( 'talk_youtube_link' );

        preg_match('/src="(.+?)"/', $video, $matches_url );
        if ( isset($matches_url[0]) && isset($matches_url[1]) ) :
            $src = $matches_url[1];  //undefined offset 1
        else:
            $src = '';
        endif;

        preg_match('/embed(.*?)?feature/', $src, $matches_id );
        if ( isset($matches_id[0]) && isset($matches_id[1]) ) :
            $youtube_id = $matches_id[1];  //undefined offset 1
        else:
            $youtube_id = '';
        endif;
        $ytvideo_id = str_replace( str_split( '?/' ), '', $youtube_id );





		$message = $message.$the_title.' ( https://www.youtube.com/watch?v='.$ytvideo_id.' )'."\r\n"; //Works till here!

		//YT IDs are CURRENTLY 11 characters
		if ( 11 == strlen( $ytvideo_id )) {

			//$message = $message.' and is less than 11 characters'."\r\n";


			$key = 'AIzaSyCxb_HsjaOhx9dLsV9QQYkMaS0MkNVWcvE';

			$jsonURL = file_get_contents("https://www.googleapis.com/youtube/v3/videos?id={$ytvideo_id}&key={$key}&part=statistics");
			$json = json_decode($jsonURL);
			$jsonviews = $json->{'items'}[0]->{'statistics'}->{'viewCount'};

			$message = $message.'Vistas actuales = '. $jsonviews."\r\n";

			if (isset($jsonviews)) {

				//$message = $message.'. IS SET'."\r\n";

				if ( get_post_meta($post_id, "_meta_tedx_ytvideo_viewCount", true) ) {

					//$message = $message.'There are views in DB'."\r\n";

					$dbviews = get_post_meta($post_id, "_meta_tedx_ytvideo_viewCount", true);

					$message = $message.'Vistas anteriores = '.$dbviews."\r\n";


					if ($jsonviews != $dbviews) {

						$message = $message.'Actualizar base de datos'."\r\n\r\n";

						$jsonviews = intval($jsonviews);


						//updates DB with real view count
						update_post_meta( $post_id, '_meta_tedx_ytvideo_viewCount', $jsonviews );



					} else {

						$message = $message.'Nada que hacer'."\r\n\r\n";

					}

				} else {

					$message = $message.'No había vistas en la base de datos, actualizar la base con vistas iniciales'."\r\n\r\n";

					$dbviews = 0;

					$jsonviews = intval($jsonviews);

					//updates DB with real view count
					add_post_meta( $post_id, '_meta_tedx_ytvideo_viewCount', $jsonviews );

				}

				//counter stuff
				$total_yt_views = $total_yt_views + $jsonviews;
				$total_db_views = $total_db_views + $dbviews;



			} else {

				$message = $message.'. IS NOT SET'."\r\n\r\n";

			}

		} else {
			$message = $message.'YT ID is longer than 11 digits. Bye bye. '."\r\n\r\n";
		}


	endwhile;

	date_default_timezone_set('America/Costa_Rica');
	$date = date('m/d/Y h:i:s a', time());

	$counter_stuff = 'Total de vistas = '.$total_yt_views."\r\n".'(Anteriores = '.$total_db_views.")\r\n\r\n";


	$message = $date."\r\n\r\n".$counter_stuff.$message;

	// components for our email
	$recepients = 'gekidasa@gmail.com';//roberto@sasso.com
	$subject = 'Vistas de videos TEDxPuravida';

	// let's send it
	mail($recepients, $subject, $message);


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