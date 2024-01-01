<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * check if a user is log in
 * @since 1.0.0
 */
function doroto_not_logged_message(){
	if (isset($_GET['log_in'])) {
  	  	return '<p class="doroto-red-text"><b>' . esc_html__("If you want to register for the tournament, you must log in to your account!", "doubles-rotation-tournament") . '</b></p>';
	} else {
		return '';
	}
}


/**
 * display messages from forms
 * @since 1.0.0
 */
function doroto_info_messsages_shortcode (){	
	global $doroto_output_form;
	
	//display messages from forms
	doroto_update_match_result();
	
	//support presentation
	doroto_change_presentation();
	
	$current_user_id = intval( get_current_user_id() );
	if($doroto_output_form =='') {
		if(!is_user_logged_in()) {
			$doroto_output_form = get_option('doroto_output_form');
			update_option('doroto_output_form', '');			
		} else {
			$doroto_output_form = get_user_meta($current_user_id, 'doroto_output_form',true);
			update_user_meta($current_user_id, 'doroto_output_form', '');
		}	
	} 
	if($doroto_output_form != '') 	{
		$allowed_html = doroto_allowed_html();
		$output = '<p class="doroto-red-text">'.wp_kses($doroto_output_form, $allowed_html).'</p>';	
	}	
	else {
		$output = '';	
	}
	$doroto_output_form = '';
	
	if (!doroto_current_user_in_tournaments()) {
		$youtube_link = doroto_read_settings("youtube_link", "");
		$youtube_link = sanitize_text_field( wp_unslash ( $youtube_link));

		if( $youtube_link != "") {
			$output .= '<b><p><a href=" '. esc_attr($youtube_link) .' " target="_blank">' . esc_html__('View the help video in an external window.','doubles-rotation-tournament') .'</a></p></b>';
		};	
	}
	
	return $output;
}
add_shortcode('doroto_info_messsages', 'doroto_info_messsages_shortcode');


/**
 * save output message to 'usermeta' table
 * @since 1.0.0
 */
function doroto_info_messsages_save ($output){
	global $doroto_output_form;
	$doroto_output_form = $output;	       
    $allowed_html = doroto_allowed_html();

	if (!is_user_logged_in()) {
		update_option('doroto_output_form', wp_kses($output, $allowed_html));
	} else {
		$current_user = wp_get_current_user();
		update_user_meta( intval($current_user->ID) , 'doroto_output_form', wp_kses($output, $allowed_html));
	}
}

/**
 * allow these html entities in strings and posts
 * @since 1.0.0
 */
function doroto_allowed_html() {
    $allowed_html = array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'target' => array()
        ),
        'br' => array(),
        'em' => array(),
        'strong' => array(),
        'p' => array(
            'class' => array(),
            'id' => array(),
        ),
        'b' => array(
            'class' => array(),
            'id' => array(),
        ),
        'ul' => array(),
        'h1' => array(
            'class' => array(),
            'id' => array(),
        ),
        'h2' => array(
            'class' => array(),
            'id' => array(),
        ),
        'h3' => array(
            'class' => array(),
            'id' => array(),
        ),
        'h4' => array(
            'class' => array(),
            'id' => array(),
        ),
        'h5' => array(
            'class' => array(),
            'id' => array(),
        ),
        'h6' => array(
            'class' => array(),
            'id' => array(),
        ),
        'li' => array(
            'class' => array(),
            'id' => array(),
        ),
    );

    return $allowed_html;
}


/**
 * get tournament ID
 * @since 1.0.0
 */
function doroto_getTournamentId() {
    global $wpdb;
	global $doroto_pernament_tournament_id;

    $current_user = wp_get_current_user();
    $userId = intval( $current_user->ID );
    $table_name = $wpdb->prefix . 'doroto_tournaments';
	
    // We will try to get the tournament_id from the URL if it is available
    if (isset($_GET['tournament_id'])) {
		$tournament_id = intval($_GET['tournament_id']);
		$exists = $wpdb->get_row($wpdb->prepare(
    		"SELECT * FROM $table_name WHERE id = %d",
    		$tournament_id
		));
        if($exists) {
			return $tournament_id;
		}
    }
	if ($doroto_pernament_tournament_id != 0) {
		return $doroto_pernament_tournament_id; //returning a global variable
	}
	
    // We will try to get the highest tournament ID where close_tournament = 0 and the player is present
	$sql = $wpdb->prepare(
   		 "SELECT MAX(id) as max_id FROM $table_name WHERE (close_tournament = 0 OR (play_final_match = 1 AND (final_result = '' OR final_result IS NULL))) AND players LIKE %s",
    	 '%' . $wpdb->esc_like($userId) . '%'
	);

    $result = $wpdb->get_row($sql);
	if ($result && $result->max_id != null) {
        $doroto_pernament_tournament_id = intval( $result->max_id );
        return $doroto_pernament_tournament_id;
    }

    // If that fails, we take the highest ID where close_tournament = 0
    $sql = "SELECT MAX(id) as max_id FROM $table_name WHERE close_tournament = 0";
    $result = $wpdb->get_row($sql);
    if ($result && $result->max_id != null) {
        return intval( $result->max_id );
    }

    // If all else fails, let's take the highest ID ever
    $sql = "SELECT MAX(id) as max_id FROM $table_name";
    $result = $wpdb->get_row($sql);
    if ($result && $result->max_id != null) {
        return intval( $result->max_id );
    } else {
        // If the table is empty, we return '0'
        return 0;
    }
	return 0;
}


/**
 * return diplay_name in full or short version
 * @since 1.0.0
 */
function doroto_find_player_name ($player_id,$whole_names) {
	global $wpdb;
	$user_data = get_userdata($player_id);
	if ($user_data) {
   		$display_name = sanitize_text_field( $user_data->display_name );
		if ($whole_names == 0) {
   			$output = doroto_display_short_name($display_name);
		} else {
    	 	$output = $display_name;
		}
	} else {
    	$output = esc_html__('Unknown player', 'doubles-rotation-tournament');
	}
	$max_length = intval(doroto_read_settings('player_name_length', 16));
	if (strlen($output) > $max_length) {
   		$output = substr($output, 0, $max_length);
	}
	return $output;
}


/**
 * shorten display name
 * @since 1.0.0
 */
function doroto_display_short_name($display_name){
	if(mb_strlen($display_name, 'UTF-8') > 8) {
  		$first_part = mb_substr($display_name, 0, 4, 'UTF-8');
   		$last_part = mb_substr($display_name, -4, null, 'UTF-8');
   		$display_name = $first_part . $last_part;
		$display_name = str_replace(" ", "", $display_name);
	}
	return $display_name;
}


/**
 * in case of erased user name from a database then return unknown user
 * @since 1.0.0
 */
function doroto_get_also_false_user($player) {
    $user = get_user_by('id', $player);
	if ($user === false) {
    	$user = new stdClass();
		$user->display_name = sanitize_text_field( __('Unknown player', 'doubles-rotation-tournament') );
		$user->ID = intval( $player );
	}
	return $user;
}

/**
 * from id prepare tournament data
 * @since 1.0.0
 */
function doroto_prepare_tournament ($tournament_id){
	global $wpdb;
	$tournament_id = intval($tournament_id);
	if($tournament_id <= 0) {
		return null;
	}
	
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $tournament_id
    ));
	return $tournament;
}


/**
 * work with url and reload the page
 * @since 1.0.0
 */
function doroto_redirect_modify_url($tournament_id, $container = '') {
    global $wpdb;
    $tournament_id = intval($tournament_id);

    // Get the current URL
    if (isset($_SERVER['HTTP_REFERER'])) {
        $current_url = sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER']));
    } else {
        // Get page id from options
        $doroto_main_page_id = intval( get_option('doroto_main_page_id') );

        // Checking if the page with this ID exists
        if ($doroto_main_page_id && get_post($doroto_main_page_id)) {
            // It exists, so we get the URL
            $current_url = sanitize_text_field(wp_unslash( get_permalink($doroto_main_page_id) ));
        } else {
            // The page with the given ID does not exist
            $current_url = sanitize_text_field(wp_unslash( home_url('/') ));
        }
    }

    $new_url = strtok($current_url, '?');
    if ($tournament_id == 0) {
		$tournament_id = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : '';
	}

    if (!empty($tournament_id)) {
        $new_url .= "?tournament_id=$tournament_id";
    }

	if (!empty($container)) {
        $new_url .= "/#$container";
    }

    wp_redirect($new_url);
    exit;
}


/**
 * checking if the player is an administrator
 * @since 1.0.0
 * output 0: is not admin
 * output 1: is admin in DoRoTo
 * output 2: is admin in DoRoTo and a web administrator
 */
function doroto_is_admin ($tournament_id){
	global $wpdb;
    $current_user = wp_get_current_user();
	
    if (!isset($tournament_id)) {
		$output = esc_html__('Missing tournament ID','doubles-rotation-tournament' ).' '.esc_html($tournament_id);
		wp_die($output);
    }

	if ($tournament_id === 0) {
	  	$output = esc_html__('No tournament has been created yet.','doubles-rotation-tournament' );
	  	wp_die($output);	
	}

    $tournament = doroto_prepare_tournament ($tournament_id);
	if (!isset($tournament)) {
		$output = esc_html__('The tournament was not found.','doubles-rotation-tournament' );
    	wp_die($output);
	}
	
	$whole_names = intval($tournament->whole_names);
    $admin_users = unserialize($tournament->admin_users); 

    if (!in_array('administrator', (array) $current_user->roles) && !in_array('editor', (array) $current_user->roles) && !in_array('author', (array) $current_user->roles) && !in_array($current_user->ID, $admin_users)) {
            return 0;
     } elseif (!in_array('administrator', (array) $current_user->roles) && !in_array('editor', (array) $current_user->roles) && !in_array('author', (array) $current_user->roles)){
		return 1; 
	 } else {
		return 2; 
	}
}


/**
 * display player name as an output
 * @since 1.0.0
 */
function doroto_output_player_data (&$output,$match,$player,$special_group,$whole_names,$mark_user){
	$user = doroto_get_also_false_user($match[$player]);

	if($mark_user == $match[$player]) {
		if (in_array($match[$player], $special_group)) {
			$output  .= '<td class="doroto-special-group-text-underlined">';
		}
   		else {
			$output  .= '<td class="doroto-text-underlined">';
		}
	} else {
		if (in_array($match[$player], $special_group)) {
			$output  .= '<td class="doroto-special-group-text">';
		}
   		else {
			$output  .= '<td>';	
		}
	}
	$output .= esc_html( doroto_find_player_name( intval($user->ID) , intval( $whole_names ) )) . "</td>";
}


/**
 * read a variable from wp option table
 * @since 1.0.0
 */
function doroto_read_settings($variable, $default_value) {
    $doroto_settings = get_option('doroto_settings');

    if (is_array($doroto_settings) && array_key_exists($variable, $doroto_settings)) {
        return $doroto_settings[$variable];
    }

    return $default_value;
}


/**
 * check if shortcode should be displayed or not because of a presentation
 * @since 1.0.0
 */
function doroto_check_need_to_display($shortcode) {
	global $wpdb;
	$current_user_id = intval( get_current_user_id() );
	$display_shortcode = intval(doroto_read_settings($shortcode, 1));
	$doroto_presentation = maybe_unserialize(get_user_meta($current_user_id, 'doroto_presentation', true));	
	if($doroto_presentation && is_array($doroto_presentation)){
		if($doroto_presentation['allow_to_run'] == 1 && ($doroto_presentation['slide'] != $shortcode || !$display_shortcode)) {
			return 0; 
		}
	};	
	return 1;
}


/**
 * is presentation running?
 * @since 1.0.0
 */
function doroto_check_if_presentation_on() {
	global $wpdb;
	$current_user_id = intval( get_current_user_id() );
	$doroto_presentation = maybe_unserialize(get_user_meta($current_user_id, 'doroto_presentation', true));	
	if($doroto_presentation && is_array($doroto_presentation)){
		if($doroto_presentation['allow_to_run'] == 1) {
			return 1;  
		}
	};	
	return 0;
}