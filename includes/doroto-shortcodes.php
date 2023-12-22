<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//display players statistics
function doroto_display_player_statistics() {
	global $wpdb;
    $tournament_id = doroto_getTournamentId();
	if(!doroto_check_need_to_display('doroto_display_player_statistics')) return;	
	
	if ($tournament_id == 0){
		$output ='<div>'.esc_html__('No tournament has been created yet.','doubles-rotation-tournament' ).'</div>';
		return $output;
	};
	
    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ='<div>'.esc_html__('The tournament was not found.','doubles-rotation-tournament' ).'</div>';
   		return $output;
	}
	
	$statistics = unserialize($tournament->statistics);
	$players = unserialize($tournament->players);
	$special_group = unserialize($tournament->special_group);
	if(empty($statistics)) {
		$output = '<p>' . esc_html__('Once registration is closed, statistical data will be available.', 'doubles-rotation-tournament').'</p>';
		return $output; 
	}
	$whole_names = intval($tournament->whole_names);
	
    $current_user_id = get_current_user_id();
	
	if(doroto_check_if_presentation_on()) {
		if ($players && is_array($players) && !empty($players)) {
    		$randomKey = array_rand($players);
    		$selected_player_id = $players[$randomKey];
		} else {
			$selected_player_id = 0; 
		}		
	} else {
		$selected_player_id = get_user_meta($current_user_id, 'doroto_filter_results', true);
		if(!in_array($selected_player_id, $players)) $selected_player_id = 0;
	}
	
	if($selected_player_id == 0) {
		$output = '<p>' . esc_html__('Statistical data cannot be displayed without using a filter.', 'doubles-rotation-tournament').'</p>';
		return $output; 
	}

	$user = get_userdata($selected_player_id);
	$output = '<p>'.esc_html__('Tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id).': ' . esc_html__('Statistical data for', 'doubles-rotation-tournament') .' <b>';
	if ($whole_names == 0) {
   		$output .= esc_html(doroto_display_short_name($user->display_name));
	} else {
    	$output .= esc_html($user->display_name);
	}
	$output .= '</b>.</p>';

    $selectedPlayerData = null;
    foreach ($statistics as $playerData) {
        if ($playerData['player_id'] == $selected_player_id) {
            $selectedPlayerData = $playerData;
            break;
        }
    }

    $output .= "<div class='doroto-table-responsive'>";
    $output .= '<table>';
    $output .= '<tr>';
    $output .= '<th>' . esc_html__('Player', 'doubles-rotation-tournament') . '</th>';
    $output .= '<th>' . esc_html__('Teammate L', 'doubles-rotation-tournament') . '</th>';
    $output .= '<th>' . esc_html__('Teammate R', 'doubles-rotation-tournament') . '</th>';
    $output .= '<th>' . esc_html__('Opponent', 'doubles-rotation-tournament') . '</th>';
	$output .= '<th>' . esc_html__('Total', 'doubles-rotation-tournament') . '</th>';
    $output .= '</tr>';

    foreach ($statistics as $player) {

        if ($player['player_id'] != $selected_player_id) {

			if ((in_array($player['player_id'], $special_group))) { 
      		  $output .= '<tr class="doroto-special-group-text">';
   		 	} else {
		  	  $output .='<tr>';
			}		
			
            $output .= '<td>' . esc_html(doroto_find_player_name ($player['player_id'],$whole_names)) . '</td>';
            
            foreach ($selectedPlayerData['playmates_L'] as $playmates_L) {
				if($playmates_L['player_id'] == $player['player_id']) {
            		$playmates_L_count = $playmates_L['count'];
				}
			}
	        foreach ($selectedPlayerData['playmates_P'] as $playmates_P) {
				if($playmates_P['player_id'] == $player['player_id']) {
            		$playmates_P_count = $playmates_P['count'];
				}
			}
		    foreach ($selectedPlayerData['opponents'] as $opponents) {
				if($opponents['player_id'] == $player['player_id']) {
            		$opponents_count = $opponents['count'];
				}
			}
          	$count_together = $playmates_L_count+$playmates_P_count+$opponents_count;

            $output .= '<td>' . esc_html($playmates_L_count) . '</td>';
            $output .= '<td>' . esc_html($playmates_P_count) . '</td>';
            $output .= '<td>' . esc_html($opponents_count) . '</td>';
			$output .= '<td>' . esc_html($count_together) . '</td>';
            
            $output .= '</tr>';
        }
    }

    $output .= '</tbody>';
    $output .= '</table></div>';
    return $output;
}

add_shortcode('doroto_display_player_statistics', 'doroto_display_player_statistics');

//change wrong written results of matches
function doroto_change_game_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;

	  $atts = array_change_key_case((array)$atts, CASE_LOWER);
	  $doroto_atts = shortcode_atts( array(
		'only_web_admin' => '0',
    ), $atts );
	
	if(doroto_check_if_presentation_on()) return '';
	
	$only_web_admin = $doroto_atts['only_web_admin'];
	$only_web_admin = intval($only_web_admin);
	if($only_web_admin !=0 && $only_web_admin !=1) return (esc_html__('Invalid value entered.','doubles-rotation-tournament' ));
	
    $current_user = wp_get_current_user();
    
	$tournament_id = doroto_getTournamentId();

    if (!isset($tournament_id)) {
        return (esc_html__('The tournament was not found.', 'doubles-rotation-tournament'));
    }

	if ($tournament_id == 0) {
  	  $output ="<div>". esc_html__('No tournament has been created yet.','doubles-rotation-tournament' )."</div>";
      return $output;
	}
	
    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ="<div>". esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}
	
	$whole_names = intval($tournament->whole_names);
    
    $admin_users = unserialize($tournament->admin_users); 
	$output = "<div>". esc_html__("You do not have permission to modify tournament results.", "doubles-rotation-tournament")."</div>";
    if ($only_web_admin == 1) {
        if (!(doroto_is_admin ($tournament_id) == 2)) return $output;
    } else {
		if (!(doroto_is_admin ($tournament_id) > 0)) return $output;
    }

    if ($tournament->open_registration == '1' || ($tournament->close_tournament == '1' && $tournament->play_final_match == '0' ) ||
	   ($tournament->close_tournament == '1' && $tournament->play_final_match == '1' && ($tournament->final_result == '' || strtotime($tournament->close_date) + 24 * 3600 < time()))) {
        $output ="<div>". esc_html__("The option to modify the match results is closed.", "doubles-rotation-tournament")."</div>";
		return $output;
    }

	if(empty($tournament->matches_list)) {
  	  return "";
	} else {
		$matches_list = maybe_unserialize($tournament->matches_list);
	}

	$output = "<div><b>" . esc_html__("Change of match result in tournament No.", "doubles-rotation-tournament") . " ".esc_html($tournament_id) . "</b>:";
	$output .= '<form id="doroto_change_game_result_form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';

	$output .= '<input type="hidden" name="action" value="doroto_change_game_result">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';
	$nothing_to_change=true;	
	
	usort($matches_list, function($a, $b) {
		return $b['match_number'] - $a['match_number'];
	});

	$output .= '<select name="match_to_change" class="doroto-select-width">';
	foreach($matches_list as $game) {
		if($tournament->close_tournament == '1' && $tournament->play_final_match == '1' && $tournament->final_result != '') {
			if($nothing_to_change) {
				$match_number = 0;
				$final_four = maybe_unserialize($tournament->final_four);
				$players_output = doroto_find_player_name ($final_four['l1'],$whole_names).'+'.doroto_find_player_name ($final_four['p1'],$whole_names)." | ".doroto_find_player_name ($final_four['l2'],$whole_names).'+'.doroto_find_player_name ($final_four['p2'],$whole_names);
				$output .= '<option value="' . esc_attr($match_number) . '">' . esc_html__("The final match", "doubles-rotation-tournament").': ' .esc_html($players_output). '</option>';
			}
			$nothing_to_change=false;
		} 
		elseif($game['played'] == 1 && $game['hide'] == 0 && ($game['result_1'] !=0 || $game['result_2'] !=0)) {
			$players_output = doroto_find_player_name ($game['player_1'],$whole_names).'+'.doroto_find_player_name ($game['player_2'],$whole_names)." | ".doroto_find_player_name ($game['player_3'],$whole_names).'+'.doroto_find_player_name ($game['player_4'],$whole_names);
    	    $output .= '<option value="' . esc_attr($game['match_number']) . '">' . esc_html__("ID", "doubles-rotation-tournament").' ' . esc_html($game['match_number']) . ': '.esc_html($players_output).'</option>';
			$nothing_to_change=false;	
   	 	}
	}
	$output .= '</select>';

    $output .= '<select name="game_result">';
	$output .= doroto_possible_results ($tournament);
    $output .= '</select>';

    $output .= '<input type="submit" value="' . esc_html__("Edit result", "doubles-rotation-tournament") . '">';  
	$output .= wp_nonce_field('doroto_change_game_form_nonce', '_wpnonce', true, false);
	$output .= '</form></div>';
	if($nothing_to_change) $output = '<div>' . esc_html__("No matches have been played in the tournament yet, so there is nothing to edit.", "doubles-rotation-tournament") . '</div>';		

	return $output;
}

add_shortcode('doroto_change_game', 'doroto_change_game_shortcode');

//change wrong written results of matches after submitting form
function doroto_change_game_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_change_game_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }

    if (!isset($_POST['tournament_id']) || !isset($_POST['match_to_change']) || !isset($_POST['game_result'])) {
      	$output =  esc_html__('Missing tournament ID, match number, or game result.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }

    $tournament_id = intval($_POST['tournament_id']);
    $match_number = intval($_POST['match_to_change']);
    $game_result = explode(':', sanitize_text_field($_POST['game_result']));
	
	$result_1 = intval($game_result[0]);
    $result_2 = intval($game_result[1]);
	$result_1 = is_numeric($result_1) ? $result_1 : 0;
	$result_2 = is_numeric($result_2) ? $result_2 : 0;
	if(($result_1 < 0) || ($result_2 < 0) || (($result_1 == 0) && ($result_2 == 0))){
		$doroto_output_form = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
       	doroto_info_messsages_save ($doroto_output_form);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	$game_result = $result_1.':'.$result_2;

    $table_name = $wpdb->prefix . 'doroto_tournaments';
	$tournament = doroto_prepare_tournament ($tournament_id);

    if (!isset($tournament)) {
        $output ="<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
    if (!is_user_logged_in()) {
        $output = "<div>".esc_html__("You must be logged in to change the result!", "doubles-rotation-tournament")."</div>";
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }	
	
	if($match_number > 0) {
   	 	$matches_list = maybe_unserialize($tournament->matches_list);
    
    	if (!is_array($matches_list)) {
       	 	$matches_list = [];
    	}
	
    	foreach($matches_list as $game_id => &$game) {
        	if ($game['match_number'] == $match_number) {
				$match = $game;
            	$game['result_1'] = $result_1;
            	$game['result_2'] = $result_2;			
				break;
        	}
    	}

		$match['result_1'] = $result_1 - $match['result_1']; 
		$match['result_2'] = $result_2 - $match['result_2'];
		$correct = true; 
		doroto_update_statistics_by_result($tournament_id,$tournament,$match,$correct);

   		$wpdb->update(
        	$table_name,
       	 array(
            	'matches_list' => maybe_serialize($matches_list)
        	),
       	 array('id' => $tournament_id)
    	);
		$output = esc_html__('The result of match no.', 'doubles-rotation-tournament').' '.esc_html($match_number).' '.esc_html__('was changed.', 'doubles-rotation-tournament');
		
	} else {
		if(doroto_save_final_result($tournament_id,$game_result)) $output = esc_html__('The result of the final match', 'doubles-rotation-tournament').' '.esc_html__('was changed.', 'doubles-rotation-tournament');
	}

	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"played-matches");
	exit;
}
add_action('admin_post_doroto_change_game_result', 'doroto_change_game_form_submit');
add_action('admin_post_nopriv_doroto_change_game_result', 'doroto_change_game_form_submit');

//needs to synchronize data with all users
function doroto_refresh_page_shortcode($atts) {
    $atts = shortcode_atts(array(
        'seconds_to_refresh' => intval(doroto_read_settings('refresh_seconds', 120)), 
    ), $atts);
	$seconds = intval($atts['seconds_to_refresh']);
	
	//for presentation purpose
	global $wpdb;
	$current_user_id = get_current_user_id();
	$doroto_presentation = maybe_unserialize(get_user_meta($current_user_id, 'doroto_presentation', true));
	if($doroto_presentation && is_array($doroto_presentation)){	
		if($doroto_presentation['allow_to_run'] == 1) {
			$seconds = intval(doroto_read_settings('show_next_seconds', 10));
		} 
	} 
	
    if ($seconds <= 0) {
        $seconds = 120; 
    }
	
    ob_start();
    ?>
    <div id="doroto-refresh-container" data-seconds="<?php echo esc_attr($seconds); ?>"></div>
    <?php
    return ob_get_clean();
}

add_shortcode('doroto_refresh_page', 'doroto_refresh_page_shortcode');

//display all registered players in the tournament
function doroto_display_players_shortcode($atts = []) {
    global $wpdb;
	$atts = array_change_key_case((array)$atts, CASE_LOWER);
	  $doroto_atts = shortcode_atts( array(
        'tournament_id' => doroto_getTournamentId(),
    ), $atts );
	$tournament_id = intval($doroto_atts['tournament_id']);
	if ($tournament_id <= 0){
		$output ='<div>'.esc_html__('No tournament has been created yet.','doubles-rotation-tournament' ).'</div>';
		return $output;
	};
    
    $tournament = doroto_prepare_tournament ($tournament_id);

	if ($tournament == null) {
		$output = esc_html__('The tournament was not found.','doubles-rotation-tournament' );
   		return $output;
	}
	
    $whole_names = intval($tournament->whole_names);
	$max_players = intval($tournament->max_players);
	$special_group_can_win = intval($tournament->special_group_can_win);
	$temp_suspend_winner = intval($tournament->temp_suspend_winner);
	$current_user_id = get_current_user_id();
	
	//presentation doroto_display_players = 'doroto_display_players'
	$doroto_display_players = intval(doroto_read_settings('doroto_display_players', 1));
	$doroto_presentation = maybe_unserialize(get_user_meta($current_user_id, 'doroto_presentation', true));	
	if($doroto_presentation && is_array($doroto_presentation)){
		if($doroto_presentation['allow_to_run'] == 1 && ($doroto_presentation['slide'] != 'doroto_display_players' || !$doroto_display_players)) return;        	
	};

    $players = unserialize($tournament->players);
		
	$statistics = unserialize($tournament->statistics);		

    usort($statistics, function($a, $b) {
         return $b['ratio'] <=> $a['ratio'];
    });
		
	if (empty($players)) {
   			 return '<p>' . esc_html__("No one has registered for the tournament yet.", "doubles-rotation-tournament") . '</p>';
	}

	$player_results = $statistics;
	$open_registration = intval($tournament->open_registration);
		
    $tournament_name = sanitize_text_field($tournament->name);
		
	$special_group = maybe_unserialize($tournament->special_group);

	if (!is_array($special_group)) {
   	 		$special_group = [];
	}
	
	$payment_display = intval($tournament->payment_display);
	
	$table = '<p><b>' . esc_html__("Players", "doubles-rotation-tournament") . '</b> ('. esc_html__("total", "doubles-rotation-tournament").' ' . esc_html(count($players));
	if($max_players > 0 && $open_registration) $table .= ' '. esc_html__("from maximum", "doubles-rotation-tournament").' '.esc_html($max_players);
	$table .= ') ' . esc_html__("tournament no.", "doubles-rotation-tournament") . ' ' . esc_html($tournament_id) . ' (<b><a href="#doroto-display-players" data-type="internal" data-id="#doroto-display-players">' . esc_html($tournament_name) . '</a></b>):';	
		
	$table .= '</br><b>' . esc_html__("Registration", "doubles-rotation-tournament") . '</b> ' . esc_html__("to the tournament is", "doubles-rotation-tournament") . ' <b>';
	if ($open_registration)	{
			$table .= esc_html__("open", "doubles-rotation-tournament");
	} else {
			$table .= esc_html__("closed", "doubles-rotation-tournament");	
	}
	if(count($players) >= $max_players && $max_players > 0 && $open_registration) $table .= esc_html__(", but the maximum number of players has already been reached", "doubles-rotation-tournament");
	$table .= ".</b></p><div class='doroto-table-responsive'>";		
	$table .= '<table>';
	$table .= '<tr class="doroto-left-aligned">';
	$table .= '<th>'.esc_html__("Order", "doubles-rotation-tournament") .'</th>';
	$table .= '<th>'. esc_html__("State", "doubles-rotation-tournament") .'</th>';
	$table .= '<th>'. esc_html__("Name", "doubles-rotation-tournament") .'</th>';
	
	if (!$open_registration) $table .= '<th>'. esc_html__("Match count", "doubles-rotation-tournament") .'</th><th>'. esc_html__("Won games", "doubles-rotation-tournament") .'</th><th>'. esc_html__("Lost games", "doubles-rotation-tournament") .'</th><th>'. esc_html__("Ratio", "doubles-rotation-tournament") .'</th>';
	if($payment_display) {
		$table .= '<th>'. esc_html__("Payment", "doubles-rotation-tournament") .'</th>';
		$payment_done = maybe_unserialize($tournament->payment_done);
		if(!is_array($payment_done) || empty($payment_done)) $payment_done = [];
	}
	$table .= '</tr>';

	$ratio_current = 0;
	$ratio_max = 0;	
	$ratio_order = 0;
	$ratio_last = 0;	
		
	foreach ($player_results as $index => $player_result) {
    		// Check if player ID is in special_group array and change row color accordingly
    		if ((in_array($player_result['player_id'], $special_group)) && !$special_group_can_win) { 
				if($current_user_id == $player_result['player_id'])	$table .= '<tr class="doroto-special-group-text-underlined">';
      		  	else $table .= '<tr class="doroto-special-group-text">';
   		 	} else {
				$ratio_current = $player_result['ratio'];
				if($ratio_current >= $ratio_max  && $tournament->close_tournament == 1 && !(!$temp_suspend_winner && $player_result['active'] == 0)) {
					$table .='<tr class="doroto-winner-background">';
					$ratio_max = $ratio_current;
				} else {
					if ((in_array($player_result['player_id'], $special_group))) {
						if($current_user_id == $player_result['player_id'])	$table .= '<tr class="doroto-special-group-text-underlined">';
      		  			else $table .= '<tr class="doroto-special-group-text">';
					} else {
						if($current_user_id == $player_result['player_id'])	$table .= '<tr class="doroto-text-underlined">';
      		  			else $table .= '<tr">';
					}			
				}	
    		}

				if($player_result['ratio'] != $ratio_last) {
					$ratio_last = $player_result['ratio'];
					$ratio_order++;
				}
				if (!$open_registration) {
					$table .= '<td>' . $ratio_order . '</td>';
				} else {
					$table .= '<td>' . $index + 1 . '</td>';
				}
				
				if($player_result['active']) $table .= '<td>' . '&#9745;' . '</td>'; //active player
				else $table .= '<td>' . '&#9744;'. '</td>'; //non active player
			
    		$table .= '<td>' . esc_html(doroto_find_player_name ($player_result['player_id'],$whole_names)). '</td>';
		
			if (!$open_registration) {
				$table .= '<td>' . esc_html($player_result['games']) . '</td>';
    			$table .= '<td>' . esc_html($player_result['won']) . '</td>';
    			$table .= '<td>' . esc_html($player_result['lost']) . '</td>';
    			$table .= '<td>' . esc_html(round($player_result['ratio'], 2)) . '</td>';				
			}
		
			if($payment_display) {
				if (in_array($player_result['player_id'], $payment_done)) $table .= '<td>' . '&check;' . '</td>'; //player with a payment
				else $table .= '<td>' . ''. '</td>'; //player with no payment	
			}
		
    		$table .= '</tr>';
	}
	$table .= '</table></div>';
    return $table;
}

add_shortcode('doroto_display_players', 'doroto_display_players_shortcode');

// filter to display data for a selected player
function doroto_player_filter_dropdown_shortcode($atts) {
    global $wpdb;

    $a = shortcode_atts(array(
        'selected_player' => 0, 
        'tournament_id' => doroto_getTournamentId(), 
    ), $atts);
	
	if(doroto_check_if_presentation_on()) return '';
	
	$tournament_id = intval($a['tournament_id']) ?? intval(doroto_getTournamentId());
	
    if (!isset($tournament_id)) {
        return esc_html__('Invalid tournament ID.', 'doubles-rotation-tournament');
    }
	
	if ($tournament_id <= 0){
		$output ='<div>'.esc_html__('No tournament has been created yet.','doubles-rotation-tournament' ).'</div>';
		return $output;
	};
	
    $current_user_id = get_current_user_id();
	if($current_user_id == 0) {
		$output = '<div>'.esc_html__('Filtering by player is only available for logged in users.', 'doubles-rotation-tournament').'</div>';
		return $output;
	}
	
    if (isset($_POST['doroto_submit'])) {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_filter_by_player_nonce')) {
        	wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    	}
		
        $selected_player = intval($_POST['doroto_selected_player']);
		if($selected_player >= 0) update_user_meta($current_user_id, 'doroto_filter_results', $selected_player);
    } else {
		$selected_player = intval(get_user_meta($current_user_id, 'doroto_filter_results', true));
    }
	
    $players = doroto_get_players_from_tournaments($tournament_id);
	$tournament=doroto_prepare_tournament ($tournament_id);

	if ($tournament == null) {
		$output =esc_html__('The tournament was not found.','doubles-rotation-tournament' );
   		return $output;
	}
	
	$open_registration = intval($tournament->open_registration);
	$whole_names = intval($tournament->whole_names);
	
	if ($open_registration) return null; 

    $unique_players = array();
    foreach ($players as $player_id) {
            $user = get_userdata($player_id);
            if ($user) {
				if($whole_names) $unique_players[$player_id] = $user->display_name;
				else $unique_players[$player_id] = doroto_display_short_name($user->display_name);
            }
    }
	
    $unique_players[0] = esc_html__('No Filter', 'doubles-rotation-tournament');
	
    $output = '<form method="post" action="">';
	$output .= '<select name="doroto_selected_player">';

	foreach ($unique_players as $player_id => $display_name) {
    	$selected = ($player_id == $selected_player) ? 'selected' : '';
    	$output .= "<option value='" . esc_attr($player_id) . "' $selected>" . esc_html($display_name) . "</option>";
	}

	$output .= '</select>';

    $output .= '<input type="submit" name="doroto_submit" value="' . esc_attr__('Filter by player', 'doubles-rotation-tournament') . '">';
	$output .= '<input type="hidden" name="player_id" value="' . esc_attr($player_id) . '">';
	$output .= wp_nonce_field('doroto_filter_by_player_nonce', '_wpnonce', true, false);
    $output .= '</form>';

    return $output;
}

add_shortcode('doroto_player_filter', 'doroto_player_filter_dropdown_shortcode');

//add player to the tournament
function doroto_add_player_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
	
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
	  $doroto_atts = shortcode_atts( array(
		'only_web_admin' => doroto_read_settings('only_admin_players',1),
    ), $atts );
	if(doroto_check_if_presentation_on()) return '';
	
	$only_web_admin = $doroto_atts['only_web_admin'];
	$only_web_admin = intval($only_web_admin);
	if($only_web_admin !=0 && $only_web_admin !=1) return (esc_html__('Invalid value entered.','doubles-rotation-tournament' ));
	
    $current_user = wp_get_current_user();
	$tournament_id = doroto_getTournamentId();
    if (!isset($tournament_id)) {
        $output ="<div>". esc_html__('Invalid tournament ID.', 'doubles-rotation-tournament')."</div>";
		return $output;
    }

	if ($tournament_id == 0) {
  	  	$output ="<div>". esc_html__('No tournament has been created yet.', 'doubles-rotation-tournament')."</div>";
		return $output;
	}

    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ="<div>". esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}

    $admin_users = unserialize($tournament->admin_users); 
	$output = "<div>".esc_html__('You do not have permission to add a player from the database.', 'doubles-rotation-tournament')."</div>";
    if ($only_web_admin == 1) {
        if (!(doroto_is_admin ($tournament_id) == 2)) return $output;
    } else {
		if (!(doroto_is_admin ($tournament_id) > 0)) return $output;
    }

    if ($tournament->open_registration == '0' || $tournament->close_tournament == '1') {
        $output ="<div>". esc_html__('The option to add a player is closed.', 'doubles-rotation-tournament')."</div>";
		return $output;
    }

    $players = maybe_unserialize($tournament->players);
	$whole_names = intval($tournament->whole_names);


    if (!empty($players)) {
		$placeholders = implode(',', array_fill(0, count($players), '%d'));
    	$query = $wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID NOT IN ({$placeholders})", $players);
    	$users = $wpdb->get_results($query);
	} else {
    	$users = $wpdb->get_results("SELECT * FROM {$wpdb->users}");
	}
	
	usort($users, function($a, $b) {
		return strcasecmp($a->display_name, $b->display_name);
	});	
	
    $output = "<div><b>" . esc_html__('Add a new player to the tournament no.', 'doubles-rotation-tournament').' '. esc_html($tournament_id) . "</b>:";
    $output .= '<form id="add_player_form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    $output .= '<input type="hidden" name="action" value="doroto_add_player_to_tournament">';
    $output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';
    $output .= '<select name="player_to_add">';
    foreach($users as $user) {
		if ($whole_names==0) $output .= '<option value="'.esc_attr($user->ID).'">'.esc_html(doroto_display_short_name($user->display_name)).'</option>';
		else $output .= '<option value="'.esc_attr($user->ID).'">'.esc_html($user->display_name).'</option>';
    }
    $output .= '</select>';
    $output .= '<input type="submit" value="'. esc_html__("Add player", "doubles-rotation-tournament") .'">';
	$output .= wp_nonce_field('doroto_add_player_form_nonce', '_wpnonce', true, false);
    $output .= '</form></div>';	

    return $output;
}
add_shortcode('doroto_add_player', 'doroto_add_player_shortcode');

//add player to the tournament after submitting form
function doroto_add_player_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_add_player_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
	
    if (!isset($_POST['tournament_id']) || !isset($_POST['player_to_add'])) {
		$output =  esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }
	
    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['player_to_add']);

    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
    	$output = esc_html__("You need to log in to add a player!", "doubles-rotation-tournament");
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
    $players = maybe_unserialize($tournament->players);
    if (!is_array($players)) {
        $players = [];
    }

	if (!in_array($player_id, $players)) {
    	$players[] = $player_id;		
	}
	$statistics=doroto_create_statistics_table ($tournament,$players,intval($tournament->whole_names));
    $wpdb->update(
                    $table_name,
   				     array(
         			   'players' => serialize($players),
 					   'statistics' => serialize($statistics)
       					 ),
                    array('id' => $tournament_id)
                );

    $output = esc_html__('Player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('was added to the tournament.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_add_player_to_tournament', 'doroto_add_player_form_submit');
add_action('admin_post_nopriv_doroto_add_player_to_tournament', 'doroto_add_player_form_submit');


//removing a player from special group
function doroto_remove_special_group_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
	  $doroto_atts = shortcode_atts( array(
		'only_web_admin' => '0',
    ), $atts );
	
	if(doroto_check_if_presentation_on()) return '';
	
	$only_web_admin = $doroto_atts['only_web_admin'];
	$only_web_admin = intval($only_web_admin);
	if($only_web_admin !=0 && $only_web_admin !=1) return (esc_html__('Invalid value entered.','doubles-rotation-tournament' ));
	
    $current_user = wp_get_current_user();

	$tournament_id = doroto_getTournamentId();

    if (!isset($tournament_id)) {
		$output ="<div>".esc_html__('Missing tournament ID','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</div>";
		return $output;
    }

	if ($tournament_id == 0) {
	  $output ="<div>".esc_html__('No tournament has been created yet.','doubles-rotation-tournament' )."</div>";
	  return $output;	
	}

    $tournament = doroto_prepare_tournament ($tournament_id);    
	if (!isset($tournament)) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
    	return $output;
	}
	$whole_names = intval($tournament->whole_names);

    $output ="<div>".esc_html__('You do not have permission to remove from a special group.','doubles-rotation-tournament' )."</div>";
    if ($only_web_admin == 1) {
        if (doroto_is_admin ($tournament_id) < 2) return $output;
    } else {
		if (doroto_is_admin ($tournament_id) < 1) return $output;
    }

    if ($tournament->open_registration == '0' || $tournament->close_tournament == '1') {
		$output ="<div>".esc_html__('The option to remove from a special group is closed.','doubles-rotation-tournament' )."</div>";
        return $output;
    }

    $special_group = maybe_unserialize($tournament->special_group);
	$players = maybe_unserialize($tournament->players);

	if (is_array($players)) {
		if (!is_array($special_group)) {
			$special_group=[];
		}

    if (empty($special_group)) {
       	 $output ="<div>".esc_html__('The special group is empty, so there is no one to subscribe to.','doubles-rotation-tournament' )."</div>";
         return $output;
		}
	} else {
		$output ="<div>".esc_html__('No one has signed up for the tournament yet, so there is no one to remove from the special group.','doubles-rotation-tournament' )."</div>";
    	return $output;	
	}
	$users = $wpdb->get_results("SELECT * FROM {$wpdb->users}");	    
     
	$output =  "<div><b>".esc_html__('Removal from the special group of the tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</b>:";
	$output .= '<form id="remove_special_group_form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_remove_special_group_to_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

	if (count($special_group) > 0) {
    	$output .= '<select name="player_to_add">';
    	foreach ($special_group as $player_id) {
       	 	$user = get_userdata($player_id); 
			$output .= '<option value="'.esc_attr($user->ID).'">'.esc_html(doroto_find_player_name ($user->ID,$whole_names)).'</option>';
    	}
    $output .= '</select>';
	$output .= '<input type="submit" value="'.esc_html__("Remove from special group","doubles-rotation-tournament" ).'">';

	} else {
    	$output .= esc_html__('No one has signed up for the tournament yet, so there is no one to remove from the special group.','doubles-rotation-tournament' );
	}
	$output .= wp_nonce_field('doroto_remove_special_group_form_nonce', '_wpnonce', true, false);
	$output .= '</form></div>';	

return $output;
}

add_shortcode('doroto_remove_special_group', 'doroto_remove_special_group_shortcode');

//removing a player from special group after submitting form
function doroto_remove_special_group_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_remove_special_group_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }

    if (!isset($_POST['tournament_id']) || !isset($_POST['player_to_add'])) {
        $output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }

    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['player_to_add']); 
 
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
    	$output =esc_html__('You must log in to remove from the special group!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
    $players = maybe_unserialize($tournament->special_group);
    if (!is_array($players)) {
        $players = [];
    }

	$index = array_search($player_id, $players);
	if ($index !== false) {
   	 array_splice($players, $index, 1);
	}

    $wpdb->update(
                    $table_name,
   				     array(
         			   'special_group' => serialize($players),
       					 ),
                    array('id' => $tournament_id)
                );
	
    $output = esc_html__('Player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('was taken from a special group.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_remove_special_group_to_tournament', 'doroto_remove_special_group_form_submit');
add_action('admin_post_nopriv_doroto_remove_special_group_to_tournament', 'doroto_remove_special_group_form_submit');

//add a player to special group
function doroto_add_special_group_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
	
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
	  $doroto_atts = shortcode_atts( array(
		'only_web_admin' => '0',
    ), $atts );
	
	if(doroto_check_if_presentation_on()) return '';
	
	$only_web_admin = $doroto_atts['only_web_admin'];
	$only_web_admin = intval($only_web_admin);
	if($only_web_admin !=0 && $only_web_admin !=1) return (esc_html__('Invalid value entered.','doubles-rotation-tournament' ));
    
    $current_user = wp_get_current_user();
    
	$tournament_id = doroto_getTournamentId();

    if (!isset($tournament_id)) {
		$output ="<div>".esc_html__('Missing tournament ID','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</div>";
		return $output;
    }

	if ($tournament_id == 0) {
	  $output ="<div>".esc_html__('No tournament has been created yet.','doubles-rotation-tournament' )."</div>";
	  return $output;	
	}

    $tournament = doroto_prepare_tournament ($tournament_id);
	if (!isset($tournament)) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
    	return $output;
	}
	
	$whole_names = intval($tournament->whole_names);  

    $output ="<div>".esc_html__('You do not have permission to add to a special group.','doubles-rotation-tournament' )."</div>";
    if ($only_web_admin == 1) {
        if (doroto_is_admin ($tournament_id) < 2) return $output;
    } else {
		if (doroto_is_admin ($tournament_id) < 1) return $output;
    }

    if ($tournament->open_registration == '0' || $tournament->close_tournament == '1') {
		$output ="<div>".esc_html__('The option to add to a special group is closed.','doubles-rotation-tournament' )."</div>";
        return $output;
    }

    $special_group = maybe_unserialize($tournament->special_group);
	$players = maybe_unserialize($tournament->players);

	if (is_array($players)) {
		if (!is_array($special_group)) {
			$special_group=[];
		}

   	 if (!empty($special_group)) {
        	$players = array_diff($players, $special_group);
		}
	} else {
		$output ="<div>".esc_html__('No one has signed up for the tournament yet, so there is no one to include in a special group.','doubles-rotation-tournament' )."</div>";
    	return $output;	
	}
     
	$output ="<div><b>".esc_html__('Addition to the special group of the tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</b>:";
	$output .= '<form id="add_special_group_form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_add_special_group_to_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

	if (count($players) > 0) {
    	$output .= '<select name="player_to_add">';
    	foreach ($players as $player_id) {
       		 $user = get_userdata($player_id);
			 $output .= '<option value="'.esc_attr($user->ID).'">'.esc_html(doroto_find_player_name ($user->ID,$whole_names)).'</option>';	
   		}
   		$output .= '</select>';
		$output .= '<input type="submit" value="'.esc_html__("Add to special group","doubles-rotation-tournament" ).'">';

	} else {
   		 $output .="<div>". esc_html__('No one has signed up for the tournament yet, so there is no one to include in a special group.','doubles-rotation-tournament' )."</div>";
	}
	$output .= wp_nonce_field('doroto_add_special_group_form_nonce', '_wpnonce', true, false);
	$output .= '</form></div>';
	return $output;
}

add_shortcode('doroto_add_special_group', 'doroto_add_special_group_shortcode');

//add a player to special group after submitting form
function doroto_add_special_group_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_add_special_group_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
    
    if (!isset($_POST['tournament_id']) || !isset($_POST['player_to_add'])) {
        $output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }

    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['player_to_add']);
 
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);
	
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
    	$output = esc_html__('You must log in to add a player to a special group!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
    $players = maybe_unserialize($tournament->special_group);
    if (!is_array($players)) {
        $players = [];
    }

    if(!in_array($player_id,$players)) $players[] = $player_id;

	$wpdb->update(
                    $table_name,
   				     array(
         			   'special_group' => serialize($players),
       					 ),
                    array('id' => $tournament_id)
                );
	
    $output = esc_html__('Player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('has been added to a special group.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_add_special_group_to_tournament', 'doroto_add_special_group_form_submit');
add_action('admin_post_nopriv_doroto_add_special_group_to_tournament', 'doroto_add_special_group_form_submit');

//add a player to admin group
function doroto_add_admin_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
	$output = '';
	$atts = array_change_key_case((array)$atts, CASE_LOWER);
	$doroto_atts = shortcode_atts( array(
		'only_web_admin' => '0',
    ), $atts );
	
	if(doroto_check_if_presentation_on()) return '';
	
	$only_web_admin = intval($doroto_atts['only_web_admin']);
	if($only_web_admin !=0 && $only_web_admin !=1) return (esc_html__('Invalid value entered.','doubles-rotation-tournament' ));
	
    $current_user = wp_get_current_user();

	$tournament_id = doroto_getTournamentId();

    if (!isset($tournament_id)) {
		$output ="<div>".esc_html__('Missing tournament ID','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</div>";
		return $output;
    }

	if ($tournament_id == 0) {
	  $output ="<div>".esc_html__('No tournament has been created yet.','doubles-rotation-tournament' )."</div>";
      return $output;		
	}
	
    $tournament = doroto_prepare_tournament ($tournament_id);    
	if (!isset($tournament)) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
    	return $output;
	}
	
	$admin_users = unserialize($tournament->admin_users);
	$tournament_name = sanitize_text_field($tournament->name);
	if (!is_array($admin_users)) $admin_users=[];
	
	$whole_names = intval($tournament->whole_names);
	$output =esc_html__('The organizer of tournament no.','doubles-rotation-tournament' )." ". esc_html($tournament_id)." (<b>".esc_html($tournament_name)."</b>) ".esc_html__('is','doubles-rotation-tournament' )." <span class='doroto-info-text'>";
	$admin_names=[];
		foreach ($admin_users as $admin_id) {
			$user_data = doroto_get_also_false_user($admin_id);
			$admin_display_name = $user_data ? $user_data->display_name : '';
			if ($whole_names==0) $admin_display_name = doroto_display_short_name($admin_display_name);
			$admin_names[]=$admin_display_name;
		}
	$output .= esc_html(implode(' '. __("&", "doubles-rotation-tournament").' ', $admin_names));
	$output .="</span>.</p>";

	if ($only_web_admin == 1) {
        if (!(doroto_is_admin ($tournament_id) == 2)) {
			$output .= '</br>'.esc_html__('You do not have permission to add organizer rights.','doubles-rotation-tournament' );
			return $output;
		}
    } else {
		if (!(doroto_is_admin ($tournament_id) > 0)) {
			$output .= '</br>'.esc_html__('You do not have permission to add organizer rights.','doubles-rotation-tournament' );
			return $output;
		}
    }

	if ($tournament->close_tournament == '1') {
		$output .= '</br>'.esc_html__('The option to add organizer rights is closed.','doubles-rotation-tournament' );
        return $output; 
    }

	$players = maybe_unserialize($tournament->players);

   	if (!empty($admin_users) &&  !empty($players)) {
        	$players = array_diff($players, $admin_users);
	} else {
   		$output .= "</br>".esc_html__('No one is currently registered for the tournament, so there is no one to grant organizer rights to.','doubles-rotation-tournament' );
   	return $output;
	}
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users}");
     
	$output .="</br><b>".esc_html__('Adding organizer rights in tournament no.','doubles-rotation-tournament' ).' '. esc_html($tournament_id)."</b>:";
	$output .= '<form id="add_admin_form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_add_admin_to_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

	if (count($players) > 0) {
    	$output .= '<select name="player_to_add">';
   		foreach ($players as $player_id) {
        	$user = get_userdata($player_id);
			if (!in_array($player_id, $admin_users)) {
				$output .= '<option value="'.esc_attr($user->ID).'">'.esc_html(doroto_find_player_name ($user->ID,$whole_names)).'</option>';       		
			}	
    	}
    	$output .= '</select>';
		$output .= '<input type="submit" value="'.esc_html__("Add organizer rights","doubles-rotation-tournament" ).'">';
	} else {
    	$output .= esc_html__('No one is currently registered for the tournament, so there is no one to grant organizer rights to.',"doubles-rotation-tournament" );
	}
	$output .= wp_nonce_field('doroto_add_admin_form_nonce', '_wpnonce', true, false);
	$output .= '</form>';

	return $output;
}
add_shortcode('doroto_add_admin', 'doroto_add_admin_shortcode');
add_action('admin_post_doroto_add_admin_to_tournament', 'doroto_add_admin_form_submit');
add_action('admin_post_nopriv_doroto_add_admin_to_tournament', 'doroto_add_admin_form_submit');

//add a player to admin group after submitting form
function doroto_add_admin_form_submit() {
    global $wpdb;
	global $doroto_output_form;

	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_add_admin_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
	
    if (!isset($_POST['tournament_id']) || !isset($_POST['player_to_add'])) {
		$output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }

    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['player_to_add']);

    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);
    if (!isset($tournament)) {
		$output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
    	$output .= esc_html__('To add organizer rights, please log in!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}	
	
	$admin_users = unserialize($tournament->admin_users); 
	if(!in_array($player_id,$admin_users)) $admin_users[] = $player_id;

	$wpdb->update(
               $table_name,
   				    array(
         			   'admin_users' => serialize($admin_users),
       					 ),
                    array('id' => $tournament_id)
           );
	$output = esc_html__('Player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('has been added to the admins.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
	doroto_redirect_modify_url($tournament_id,"tournament-editing");
	exit;
}

//temporary disable a player
function doroto_temporary_disable_player_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
    $a = shortcode_atts( array(
        'tournament_id' => doroto_getTournamentId(),
    ), $atts );
    $tournament_id = intval($a['tournament_id']);
	if(doroto_check_if_presentation_on()) return '';
	
    if (!is_user_logged_in()) {
		$output = "<div>".esc_html__('You must log in to temporarily suspend a player!', 'doubles-rotation-tournament')."</div>";        
        return $output;
    }

    if (!isset($tournament_id)) {
        $output = "<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    if ($tournament_id <= 0 ) {
        $output = "<div>".esc_html__('No tournament has been created yet.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    $tournament = doroto_prepare_tournament ($tournament_id);
	if (!isset($tournament)) {
		$output = "<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
		return $output;
    }
	
	if ($tournament->close_tournament == '1') {
        $output = "<div>".esc_html__('The option to suspend the player game is closed.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

	$statistics = unserialize($tournament->statistics);
	if(empty($statistics)) return null;
	$activePlayers = doroto_find_active_players($statistics); 

    $user_id = get_current_user_id(); 
    if (doroto_is_admin ($tournament_id) < 1 && !in_array($user_id, unserialize($tournament->players))) {
        return null;
    }

	$output ="<div><b>".esc_html__('Suspension of the game of the selected player in tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</b>:";
	$output .= '<form id="doroto_temporary_disable_player" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_disable_player_in_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

    $output .= '<select name="disable_player">';
	$allow_to_display = false;
    foreach ($statistics as $player) {
	   if(!in_array($player['player_id'],$activePlayers)) continue;
       $display_name = '';				
	   if (doroto_is_admin ($tournament_id) > 0) {
    		$display_name = doroto_find_player_name ($player['player_id'],$tournament->whole_names);
		} elseif ($player['player_id'] == $user_id) {
   			$display_name = doroto_find_player_name ($player['player_id'],$tournament->whole_names);
		}

        if($display_name != '') {
			$allow_to_display = true;
			$output .= '<option value="' . esc_attr($player['player_id']) . '">' . esc_html($display_name) . '</option>';
		}
    }
	$output .= '<option value=" 0 ">' . esc_html__('All players', 'doubles-rotation-tournament'). '</option>';
    $output .= '</select>';

	$output .= '<input type="submit" name="disable_temporary" value="'.esc_html__("Suspend participation","doubles-rotation-tournament" ).'">';
	$output .= wp_nonce_field('doroto_temporary_disable_player_nonce', '_wpnonce', true, false);
    $output .= '</form></div>';	

    if($allow_to_display) return $output;
	else return null;
}

add_shortcode('doroto_temporary_disable_player', 'doroto_temporary_disable_player_shortcode');

//temporary disable a player after submitting form
function doroto_temporary_disable_player_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_temporary_disable_player_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }

    if (!isset($_POST['tournament_id']) || !isset($_POST['disable_player'])) {
        $output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }
    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['disable_player']);

    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
   		$output = esc_html__('You must log in to temporarily suspend a player!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
    $statistics = maybe_unserialize($tournament->statistics);
    if (!is_array($statistics)) {
        return null;
    }

	foreach($statistics as &$player) {
		if($player_id == 0) $player['active'] = 0;
		elseif($player['player_id'] == $player_id) {
			$player['active'] = 0;
			break;
		}
	}
	unset($player); 
                   
	$wpdb->update(
                    $table_name,
   				     array(
         			   'statistics' => serialize($statistics),
       					 ),
                    array('id' => $tournament_id)
                );
	
    if($player_id == 0) $output = esc_html__('All players have temporarily suspended participation.', 'doubles-rotation-tournament');
    else $output = esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('has temporarily suspended participation.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_disable_player_in_tournament', 'doroto_temporary_disable_player_form_submit');
add_action('admin_post_nopriv_doroto_disable_player_in_tournament', 'doroto_temporary_disable_player_form_submit');

//temporary enable a player
function doroto_temporary_enable_player_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
    $a = shortcode_atts( array(
        'tournament_id' => doroto_getTournamentId(),
    ), $atts );
	if(doroto_check_if_presentation_on()) return '';
    $tournament_id = intval($a['tournament_id']);

    if (!is_user_logged_in()) {
		$output = "<div>".esc_html__('You must log in to restore the game!', 'doubles-rotation-tournament')."</div>";       
        return $output;
    }

    if (!isset($tournament_id)) {
        $output = "<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    if ($tournament_id == 0 ) {
        $output = "<div>".esc_html__('No tournament has been created yet.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

	$tournament = doroto_prepare_tournament ($tournament_id);
	if (!isset($tournament)) {
        $output ="<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
		return $output;
    }
	
	if ($tournament->close_tournament == '1') {
        $output = "<div>".esc_html__('The option to restore the player game is closed.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

	$statistics = unserialize($tournament->statistics);
	if(empty($statistics)) return null;
	$activePlayers = doroto_find_active_players($statistics); 

    $user_id = get_current_user_id(); 
    if (doroto_is_admin ($tournament_id) < 1 && !in_array($user_id, unserialize($tournament->players))) {
        return null;
    }

	$output ="<div><b>".esc_html__('Resuming the game of the selected player in tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</b>:";
	$output .= '<form id="doroto_temporary_enable_player" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_enable_player_in_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

    $output .= '<select name="enable_player">';
	$allow_to_display = false;
    foreach ($statistics as $player) {
	   if(in_array($player['player_id'],$activePlayers)) continue;
       $display_name = '';				
	   if (doroto_is_admin ($tournament_id) > 0) {
    		$display_name = doroto_find_player_name ($player['player_id'],$tournament->whole_names);
		} elseif ($player['player_id'] == $user_id ) {
   			$display_name = doroto_find_player_name ($player['player_id'],$tournament->whole_names);
		}

        if($display_name != '') {
			$allow_to_display = true;
			$output .= '<option value="' . esc_attr($player['player_id']) . '">' . esc_html($display_name) . '</option>';
		}
			
    }
	$output .= '<option value=" 0 ">' . esc_html__('All players', 'doubles-rotation-tournament'). '</option>';
    $output .= '</select>';

	$output .= '<input type="submit" name="enable_temporary" value="'.esc_html__("Resume participation","doubles-rotation-tournament" ).'">';
	$output .= wp_nonce_field('doroto_temporary_enable_player_nonce', '_wpnonce', true, false);
    $output .= '</form></div>';	

    if($allow_to_display) return $output;
	else return esc_html__('All players are active.', 'doubles-rotation-tournament');
}

add_shortcode('doroto_temporary_enable_player', 'doroto_temporary_enable_player_shortcode');

//temporary enable a player after submitting form
function doroto_temporary_enable_player_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_temporary_enable_player_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
	
    if (!isset($_POST['tournament_id']) || !isset($_POST['enable_player'])) {
        $output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }
    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['enable_player']);
    
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);	
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
   		$output = esc_html__('You must log in to restore the game!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
    $statistics = maybe_unserialize($tournament->statistics);
    if (!is_array($statistics)) {
        return null;
    }

	foreach($statistics as &$player) {
		if($player_id == 0) $player['active'] = 1;
		elseif($player['player_id'] == $player_id) {
			$player['active'] = 1;
			break;
		}
	}
	unset($player); 
                    
	$wpdb->update(
                    $table_name,
   				     array(
         			   'statistics' => serialize($statistics),
       					 ),
                    array('id' => $tournament_id)
                );
	
    if($player_id == 0) $output = esc_html__('All players have renewed participation.', 'doubles-rotation-tournament');
    else $output = esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('has renewed participation.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_enable_player_in_tournament', 'doroto_temporary_enable_player_form_submit');
add_action('admin_post_nopriv_doroto_enable_player_in_tournament', 'doroto_temporary_enable_player_form_submit');

//remove a player from the tournament
function doroto_remove_player_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
	$doroto_atts = shortcode_atts( array(
        'only_web_admin' => '0',
    ), $atts );
	if(doroto_check_if_presentation_on()) return '';
	
	$only_web_admin = $doroto_atts['only_web_admin'];
	$only_web_admin = intval($only_web_admin);
	if($only_web_admin !=0 && $only_web_admin !=1) return (esc_html__('Invalid value entered.','doubles-rotation-tournament' ));
    
    $current_user = wp_get_current_user();
 
	$tournament_id = doroto_getTournamentId();

    if (!isset($tournament_id)) {
        $output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
        return $output;
    }

	if ($tournament_id == 0) {
  	  $output ="<div>".esc_html__('No tournament has been created yet.','doubles-rotation-tournament' )."</div>";
	  return $output;
	}

    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}
	
	$whole_names = intval($tournament->whole_names);  

    $admin_users = unserialize($tournament->admin_users); 
	$output = "<div>".esc_html__("You do not have permission to remove a player.", "doubles-rotation-tournament")."</div>";
    if ($only_web_admin == 1) {
        if (doroto_is_admin ($tournament_id) < 2) return $output;
    } else {
		if (doroto_is_admin ($tournament_id) < 1) return $output;
    }

    if ($tournament->open_registration == '0' || $tournament->close_tournament == '1') {
        $output = "<div>". esc_html__("The option to remove a player is closed.", "doubles-rotation-tournament")."</div>";
		return $output;
    }
	
	$players = maybe_unserialize($tournament->players);

	if (!empty($players)) {
		$placeholders = implode(',', array_fill(0, count($players), '%d'));
    	$query = $wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID IN ({$placeholders})", $players);
    	$users = $wpdb->get_results($query);

		usort($users, function($a, $b) {
			return strcasecmp($a->display_name, $b->display_name);
		});	
    
    	$output = "<div><b>" . esc_html__("Removing a player from tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id) . "</b>:";
    	$output .= '<form id="remove_player_form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    	$output .= '<input type="hidden" name="action" value="doroto_remove_player_from_tournament">';
    	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';
    	$output .= '<select name="player_to_remove">';
    	foreach($users as $user) {
			if ($whole_names == 0) $output .= '<option value="'. esc_attr($user->ID) .'">'. esc_html(doroto_display_short_name($user->display_name)) .'</option>';
			else $output .= '<option value="'.esc_attr($user->ID).'">'.esc_html($user->display_name).'</option>';
    	}
    	$output .= '</select>';
    	$output .= '<input type="submit" value="' . esc_html__("Remove player", "doubles-rotation-tournament") . '">';
		$output .= wp_nonce_field('doroto_remove_player_form_nonce', '_wpnonce', true, false);
    	$output .= '</form></div>';    	
	} else {
    	$output = '<p>' . esc_html__("No one has registered for the tournament yet, so there is no one to remove.", "doubles-rotation-tournament") . '</p>';
	}
    return $output;
}
add_shortcode('doroto_remove_player', 'doroto_remove_player_shortcode');

//remove a player from the tournament after submitting form
function doroto_remove_player_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_remove_player_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
	
	if (!is_user_logged_in()) {
        $output = esc_html__("To remove a player, you need to log in first!", "doubles-rotation-tournament");
		$doroto_output_form = $output; 
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}

    if (!isset($_POST['tournament_id']) || !isset($_POST['player_to_remove'])) {
        $output =  esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		$doroto_output_form = $output; 
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }

    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['player_to_remove']);

    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output = esc_html__('The tournament was not found.','doubles-rotation-tournament' );
   		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}

    $players = maybe_unserialize($tournament->players);
    $special_group = maybe_unserialize($tournament->special_group);
	
    if (!is_array($players)) {
        $players = [];
    }

    if (!is_array($special_group)) {
        $special_group = [];
    }
	
	$statistics = unserialize($tournament->statistics);
	$statistics_permittion = true;
	
	if(!empty($statistics)) {
		foreach($statistics as $player) {
			if($player['games'] > 0) $statistics_permittion = false;
		}
		if($statistics_permittion) {
			$statistics = array();
		    $table_name = $wpdb->prefix . 'doroto_tournaments';
                    
			$wpdb->update(
                    $table_name,
   				     array(
         			   'statistics' => serialize($statistics)
       					 ),
                    array('id' => $tournament_id)
                );

			$tournament = doroto_prepare_tournament ($tournament_id);				
		} else {
			$output = esc_html__("A player cannot be removed because at least one match has already been played in the tournament.", "doubles-rotation-tournament");
			doroto_info_messsages_save ($output);
			doroto_redirect_modify_url($tournament_id,"");
			exit;
		}
	} 
	
    $players = array_diff($players, array($player_id));
	$players = array_values($players);
    $special_group = array_diff($special_group, array($player_id));
	$special_group = array_values($special_group);
	
	$statistics=doroto_create_statistics_table ($tournament,$players,intval($tournament->whole_names));	

    $wpdb->update(
        $table_name,
        array(
            'players' => maybe_serialize(array_values($players)), // Reset array keys before serialize
			'statistics' => serialize($statistics), 
            'special_group' => maybe_serialize(array_values($special_group)) // Reset array keys before serialize
        ),
        array('id' => $tournament_id)
    );

    $output = esc_html__('Player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('was removed from the tournament.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_remove_player_from_tournament', 'doroto_remove_player_form_submit');
add_action('admin_post_nopriv_doroto_remove_player_from_tournament', 'doroto_remove_player_form_submit');

//display matches of the tournament
function doroto_display_games_func($atts = []) {
    global $wpdb;
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
	$doroto_atts = shortcode_atts( array(
        'only_played' => '1',
		'tournament_id' => doroto_getTournamentId(),
    ), $atts );
	
	$current_user_id = get_current_user_id();
	
	if(!doroto_check_need_to_display('doroto_display_games')) return '';
	
	$only_played = intval($doroto_atts['only_played']);
	$tournament_id = intval($doroto_atts['tournament_id']);
	if ($tournament_id == 0) $tournament_id = doroto_getTournamentId();
	if ($tournament_id == 0){
		$output ='<div>'.esc_html__('No tournament has been created yet.','doubles-rotation-tournament' ).'</div>';
		return $output;
	};

    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}
	
	$whole_names = intval($tournament->whole_names);

    if ($tournament && property_exists($tournament, 'matches_list')) {
        $games = unserialize($tournament->matches_list);

        if (is_array($games)) {
			$tournament_name = sanitize_text_field($tournament->name);
			
			$matches_played_count = 0;
			$game_played_count = 0;
			foreach ($games as $match) {
   				 if ($match['played'] == 1 && $match['hide'] == 0 && ($match['result_1'] != 0 || $match['result_2'] != 0)) {
       		 	$matches_played_count++;
				$game_played_count += $match['result_1']+$match['result_2'];	 
   				 }
			}
		if($matches_played_count > 0)	{
			
		 	usort($games, function($a, $b) {
   	 	 		return $b['match_number'] <=> $a['match_number'];
    	 	});
			
			$output = '<p><b>'. esc_html__("Match results", "doubles-rotation-tournament") . '</b> ' . esc_html__("tournament no.", "doubles-rotation-tournament") . ' '. esc_html($tournament_id) .' (<b> <a href="#doroto-display-games" data-type="internal" data-id="#doroto-display-games">'.esc_html($tournament_name).'</a></b>).';
			$output .= '</br><b>' . esc_html__("Meantime", "doubles-rotation-tournament") . '</b>' . ' ' . esc_html__("were played", "doubles-rotation-tournament") . ' <b>' . esc_html($matches_played_count) . ' ' . esc_html__("matches and", "doubles-rotation-tournament") . ' ' . esc_html($game_played_count) . ' ' . esc_html__("games", "doubles-rotation-tournament") . '</b>.</br><b>';

			if ($tournament->close_tournament == '1' )	{
				$output .= esc_html__("Tournament is closed.", "doubles-rotation-tournament");
			} else {
				$output .= esc_html__("The tournament is still being played.", "doubles-rotation-tournament");
			}	

			$output .= "</b></p><div class='doroto-table-responsive'>";

            $output .= "<table>";
			$output .= "<tr><th>" . esc_html__("ID", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L1+R1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L2+R2", "doubles-rotation-tournament") . "</th></tr>";

			$special_group = maybe_unserialize($tournament->special_group);
			if (!is_array($special_group)) {
    			$special_group = [];
			}
			
			$doroto_filter_results = get_user_meta($current_user_id, 'doroto_filter_results', true);
			if (empty($doroto_filter_results)) {
    			$doroto_filter_results = 0; 
			}
			$players = maybe_unserialize($tournament->players);
			if (!in_array($doroto_filter_results, $players)) {
				$doroto_filter_results = 0;
				update_user_meta($current_user_id, 'doroto_filter_results', 0);
			}

			foreach ($games as $game) {
       		 			if ($game['hide'] == 1) continue;
        				if ($only_played == 1 && $game['played'] == 0)  continue;
						if ($game['result_1'] == 0 && $game['result_2'] == 0) continue;
						if ($game['player_1'] != $doroto_filter_results && $game['player_2'] != $doroto_filter_results && $game['player_3'] != $doroto_filter_results && $game['player_4'] != $doroto_filter_results && $doroto_filter_results !=0) continue;
        				$output .= "<tr>";
						$output .= "<td>" . esc_html($game['match_number']) . "</td>";
	
						doroto_output_player_data ($output,$game,'player_1',$special_group,$whole_names,$doroto_filter_results); //save variables (sanitized and escaped)
						doroto_output_player_data ($output,$game,'player_2',$special_group,$whole_names,$doroto_filter_results);
						doroto_output_player_data ($output,$game,'player_3',$special_group,$whole_names,$doroto_filter_results);
						doroto_output_player_data ($output,$game,'player_4',$special_group,$whole_names,$doroto_filter_results);

						$output .= "<td>" . esc_html($game['result_1']) . "</td>";
						$output .= "<td>" . esc_html($game['result_2']) . "</td>";
        				$output .= "</tr>";		
			}
            $output .= "</table>";
			$output .= "</div>";
		  }	else {
			$output ='';
		  }
        } else {
            $output = "<div>".esc_html__("Player registration is still in progress and the matches have not yet been drawn.", "doubles-rotation-tournament")."</div>";
        }
    } else {
        $output = "<div>".esc_html__( "Tournament no.", "doubles-rotation-tournament" ).' ' . esc_html($tournament_id) .' '. esc_html__( "was not found.", "doubles-rotation-tournament" )."</div>";
    }

    return $output;
}
add_shortcode('doroto_display_games', 'doroto_display_games_func');

//display matches ready to play of the tournament 
function doroto_games_to_play_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
	global $doroto_output_form;
    $atts = array_change_key_case((array)$atts, CASE_LOWER);
	$doroto_atts = shortcode_atts( array(
		'tournament_id' => doroto_getTournamentId(),        
    ), $atts );
	
	$current_user_id = get_current_user_id();
	
	if(!doroto_check_need_to_display('doroto_games_to_play')) return null;
	
	$tournament_id = intval($doroto_atts['tournament_id']);
	if ($tournament_id == 0) $tournament_id = doroto_getTournamentId();
	if ($tournament_id == 0){
		$output ='<div>'.esc_html__('No tournament has been created yet.','doubles-rotation-tournament' ).'</div>';
		return $output;
	};

	$output = doroto_offer_games($tournament_id);
	
    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ='<div>'.esc_html__('The tournament was not found.','doubles-rotation-tournament' ).'</div>';
   		return $output;
	}
	
	$close_tournament = intval($tournament->close_tournament);
	if ($close_tournament == 1) {
		$tournament_name = sanitize_text_field($tournament->name);
		$output .='<div>'.esc_html__('Tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id).' (<b>'.esc_html($tournament_name).'</b>) '.esc_html__('is closed.','doubles-rotation-tournament' ).'</div>';
	}
	
	$current_user = wp_get_current_user();
	$whole_names = intval($tournament->whole_names);
	$open_registration = intval($tournament->open_registration);
	$players = unserialize($tournament->players);
	$final_four = unserialize($tournament->final_four);
	$final_result = unserialize($tournament->final_result);
	$play_final_match = intval($tournament->play_final_match);
	
	$statistics = unserialize($tournament->statistics);		

   	usort($statistics, function($a, $b) {
   	 	 return $b['ratio'] <=> $a['ratio'];
    });
	$player_results = $statistics;
	
	if ($close_tournament == 1) $output .= '<p><div class="doroto-grey-background">';
	if ($play_final_match && $close_tournament == 1 && empty($final_four)) {
		if ($open_registration) {
			$doroto_output_form = esc_html__('You cannot go to the finals while registration is open.','doubles-rotation-tournament' );
		} else {
			$output .= doroto_select_final_doubles($player_results, $tournament, $current_user);
		}	
	}

	$image_url = plugins_url( 'lib/img/trophy.jpg', dirname(__FILE__) );
	$image_html = '<img src="' . esc_url( $image_url ) . '"  style="display: inline-block; height: 1.3em; width: auto; vertical-align: middle;">';
	
	if ($play_final_match && $close_tournament == 1 && !empty($final_four) && !empty($final_result)) {
		$output .= '<p><b>'.$image_html.' '.esc_html__('Winner in the category of the most ideal couple:','doubles-rotation-tournament' ).' ';

		if($final_result['result_1'] > $final_result['result_2']) {
			$output .= "<span class='doroto-winner-text'>".esc_html(doroto_find_player_name ($final_four['l1'],$whole_names)).' '.esc_html__('&','doubles-rotation-tournament' ).' '.esc_html(doroto_find_player_name ($final_four['p1'],$whole_names))."</b></span>!</p>";
		}
		else {
			$output .= "<span class='doroto-winner-text'>".esc_html(doroto_find_player_name ($final_four['l2'],$whole_names)).' '.esc_html__('&','doubles-rotation-tournament' ).' '.esc_html(doroto_find_player_name ($final_four['p2'],$whole_names))."</b></span>!</p>";
		}
	}
	
	if ($play_final_match && $close_tournament == 1 && !empty($final_four)) {
		if(empty($final_result)) {
			$output .= '<p>'.esc_html__('The final match is being played.','doubles-rotation-tournament' ).'</br>';
			if (doroto_is_admin ($tournament_id) > 0) { 
				$output .= esc_html__('If you want, you can adjust the Maximum number of games and Settings of the possible results of the match for this match.','doubles-rotation-tournament' ).'</p>';
			}
		}
		$output .= doroto_result_final_doubles($player_results, $tournament, $current_user);
	}
	
	if ($close_tournament == 1) {
		$output .= '<b>'.$image_html.' '.esc_html__('Best Player Winner:','doubles-rotation-tournament' ).' '."<span class='doroto-winner-text'>".esc_html(doroto_get_winner($tournament_id,$whole_names))."</b></span>!";
		$output .= '</div></p>'; 
    	return $output;
	}
    
	if(empty($tournament->matches_list) || $tournament->open_registration == 1) {
  	  return "";
	} else {
  	  $matches = unserialize($tournament->matches_list);
	}
	
	$special_group = maybe_unserialize($tournament->special_group);
	if (!is_array($special_group)) {
    	$special_group = [];
	}
   	$change_results = intval($tournament->change_results);
	$allow_input_results = intval($tournament->allow_input_results);

	$tournament_name = sanitize_text_field($tournament->name);
	$whole_names =intval($tournament->whole_names);    
	$output .= '<div><b>'.esc_html__('Currently, the following matches are being played in tournament no.','doubles-rotation-tournament').' '.esc_html($tournament_id).' (<a href="#doroto-games-to-play" data-type="internal" data-id="#doroto-games-to-play">'.esc_html($tournament_name).'</a>):</b></div>';	

    $output .= "<div class='doroto-table-responsive'>";
    $output .= '<table>';
    $output .= "<tr class='doroto-left-aligned'><th>" . esc_html__("ID", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Hide", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Result", "doubles-rotation-tournament") . "</th>";
	$output .= '<th>'. esc_html__("Save", "doubles-rotation-tournament").'</th>';
	$output .= "</tr>";

    foreach($matches as $match) {
        if($match['played'] == 1 && $match['hide'] == 0 && $match['result_1'] == 0 && $match['result_2'] == 0) {
            $output .= '<tr>';
			$output .= "<td>" . esc_html($match['match_number']) . "</td>";

			doroto_output_player_data ($output,$match,'player_1',$special_group,$whole_names,$current_user_id); //save and sanitized variables
			doroto_output_player_data ($output,$match,'player_2',$special_group,$whole_names,$current_user_id);
			doroto_output_player_data ($output,$match,'player_3',$special_group,$whole_names,$current_user_id);
			doroto_output_player_data ($output,$match,'player_4',$special_group,$whole_names,$current_user_id);
			
			$players = [$match['player_1'], $match['player_2'], $match['player_3'], $match['player_4']];

	if((in_array($current_user_id, $players) && $allow_input_results)|| (doroto_is_admin ($tournament_id) > 0)) {
			$output .= '<td>';
		
			if (isset($_SERVER['REQUEST_URI'])) $uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
  			else $uri = '';
			$uri = '/' . ltrim($uri, '/');
					
			$output .= '<form method="post" action="' . esc_url($uri) . '">';
			$output .= '<input type="hidden" name="hide_' . esc_attr($match['match_number']) . '" value="0">';
			$checked = $match['hide'] == 1 ? 'checked' : '';
			$output .= '<input type="checkbox" name="hide_' . esc_attr($match['match_number']) . '" value="1" ' . esc_attr($checked) . '>';
			$output .= '</td>';

			$output .= '<td>';
			$output .= '<input type="hidden" name="match_number" value="' . esc_attr($match['match_number']) . '">';
			$output .= '<select name="result">';
			$output .= doroto_possible_results($tournament);
			$output .= '</td><td>';
			$output .= '<input type="submit" value="' . esc_html(__('Save', 'doubles-rotation-tournament')) . '">';
			$output .= wp_nonce_field('doroto_games_to_play_nonce', '_wpnonce', true, false);
			$output .= '</form>';
			$output .= '</td>';       
    }    
     $output .= '</tr>';
        }
    }
    
    $output .= '</table>';
    $output .= '</div></p>';
    return $output;
}

add_shortcode('doroto_games_to_play', 'doroto_games_to_play_shortcode');

//edit tournament variables
function doroto_add_tournament_parameters() {
	global $wpdb;
	$current_user = wp_get_current_user();
	$tournament_id = doroto_getTournamentId();
	
	if(doroto_check_if_presentation_on()) return '';
	
	if ($tournament_id == 0) {
		$output = "<div>".esc_html__("No tournament has been created yet.", "doubles-rotation-tournament").'</div>';
		return $output;
	}
   	
	$table_name = $wpdb->prefix . 'doroto_tournaments';
   	$tournament =  $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' ).'</div>';
   		return $output;
	}
	
    ob_start();
	$output = '<b>' . esc_html__("Edit tournament no.","doubles-rotation-tournament").' ' . esc_html($tournament_id) . ':</b>';
	$output .= '<form method="post" action="'.esc_url(admin_url('admin-post.php?action=doroto_tournament_parameters_save')).'">';

	$output .= '<input type="hidden" name="action" value="doroto_tournament_parameters">'; 
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

	$output .= '<div class="doroto-table-responsive">';
	$output .= '<table class="doroto-table">';
	$output .= '<tr class="doroto-left-aligned">';
	$output .= '<th class="doroto-table-width">';	
	$output .= esc_html__("Variable","doubles-rotation-tournament");
	$output .= '</th><th>';
	$output .= esc_html__("Value","doubles-rotation-tournament");
	$output .= '</th></tr>';
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("The name of the tournament can be edited here.","doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT name FROM $table_name WHERE id = %d";
	$name_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
    $output .= '<textarea id="doroto_tournament_parameters_name" name="doroto_tournament_parameters_name" style="width: 100%;height:100px;">';
	$output .= esc_attr($name_table);
	$output .= '</textarea>';

	$output .= '</td>';	
	$output .= '</tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("This option determines whether player names will be displayed in full or if they will be obfuscated to protect personal data.","doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT whole_names FROM $table_name WHERE id = %d";
	$whole_names_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_whole_names" name="doroto_tournament_parameters_whole_names" style="width: 100%;">';
	$options = array(0 => esc_html__("Abbreviated names","doubles-rotation-tournament"), 1 => esc_html__("Full names","doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
		 $selected = ($value == $whole_names_table) ? ' selected="selected"' : '';
		 $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
	$output .= '</tr>';
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Number of courts available for the tournament.","doubles-rotation-tournament");
	$output .= '</td>';
	$output .= '<td>';
	$query = "SELECT courts_available FROM $table_name WHERE id = %d";
	$courts_available= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_courts_number" name="doroto_tournament_parameters_courts_number">';
      $minValue = 1;
      $maxValue = 10;
      for ($i = $minValue; $i <= $maxValue; $i++) {
		$selected = ($i == $courts_available) ? 'selected' : '';
		$output .= "<option value=\"" . esc_attr($i) . "\" $selected>" . esc_html($i). "</option>";
      }
 	$output .= '</select>';
	$output .= '</td>';
	$output .= '</tr>';
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("The minimum number of non-playing players required to schedule another match. A higher number ensures greater variability but may result in longer waiting times for the next match. A lower number speeds up the game but may lead to some players playing more matches than others. The default value is 0, which means that the next scheduling will occur only after all previously scheduled matches are completed.","doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT min_not_playing FROM $table_name WHERE id = %d";
	$min_not_playing= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_min_not_playing" name="doroto_tournament_parameters_min_not_playing">';
	$output .= '<option value="0" selected>0</option>';

	if (empty($tournament->players) || count(unserialize($tournament->players)) < 5)  {
    $output .= '<option value="4" selected>4</option>';
	} else {
    $minValue = 5;
    $maxValue = count(unserialize($tournament->players));

    for ($i = $minValue; $i <= $maxValue; $i++) {
        $selected = ($i == $min_not_playing) ? 'selected' : '';
		$output .= "<option value=\"" . esc_attr($i) . "\" $selected>" . esc_html($i). "</option>";
  	  }
	}
	$output .= '</select>';
	$output .= '</td>';
	$output .= '</tr>';
		
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';		
	$output .= esc_html__("Maximum sum of games in a match.","doubles-rotation-tournament");
	$output .= '</td>';
	$output .= '<td>';
	$query = "SELECT max_games FROM $table_name WHERE id = %d";
	$max_games_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_max_games" name="doroto_tournament_parameters_max_games">';
	$options = array(3,5,7,9,11,13);
	foreach ($options as $option) {
   		 $selected = ($option == $max_games_table) ? ' selected="selected"' : '';
		 $output .= "<option value=\"" . esc_attr($option) . "\"$selected>" . esc_html($option) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';	
	$output .= '</tr>';
	
	$output .= '<tr class="doroto-separator-background"><td></td><td></td></tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';		
	$output .= esc_html__("Maximum number of registered players. (0 = no limit)","doubles-rotation-tournament");
	$output .= '</td>';
	$output .= '<td>';
	$query = "SELECT max_players FROM $table_name WHERE id = %d";
	$max_players_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_max_players" name="doroto_tournament_parameters_max_players">';
	for ($i = 0; $i <= 99; $i++) {
    	$selected = ($i == $max_players_table) ? ' selected="selected"' : '';
    	if($i == 0 || $i >= 4) $output .= "<option value=\"" . esc_attr($i) . "\" $selected>" . esc_html($i). "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';	
	$output .= '</tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';
	$output .= '<div>'.esc_html__("Setting possible match results:","doubles-rotation-tournament").'</div>';
	$output .= '<div>'.esc_html__("(example for Maximum number of games in a match = 5)","doubles-rotation-tournament").'</div>';
	$output .= '<ol>';
	$output .= '<li>'.'<div>'.esc_html__("Basic settings according to the parameter Maximum number of games in a match.","doubles-rotation-tournament").'<div>';
	$output .= '<div>'.esc_html__("For example (5:0, 4:1, 3:2)","doubles-rotation-tournament").'<div>'.'</li>';
	$output .= '<li>'.'<div>'.esc_html__("If it is no longer possible to win a match, this option allows for the possibility of an early termination. For example, in a game played up to 5 sets, the match can be concluded even if the current score is 3:0.","doubles-rotation-tournament").'</div>';
	$output .= '<div>'.esc_html__("For example (5:0, 4:1, 3:2, 3:1, 3:0)","doubles-rotation-tournament").'<div>'.'</li>';
	$output .= '<li>'.'<div>'.esc_html__("The game can only be ended with a lead of 2 gems. For example, a 5-game match ends at 4:1, but continues if the current score is 3:2. However, it ends at 4:2 or 4:3 (ended in a tiebreak).","doubles-rotation-tournament").'<div>';
	$output .= '<div>'.esc_html__("For example (5:3, 4:2, 4:1, 5:0)","doubles-rotation-tournament").'<div>'.'</li>';
	$output .= '<li>'.'<div>'.esc_html__("The game can only be ended when the lead is not only 2 games, but also prematurely, when the match can no longer be won.","doubles-rotation-tournament").'<div>';
	$output .= '<div>'.esc_html__("For example (5:3, 4:2, 4:1, 4:0, 5:0)","doubles-rotation-tournament").'<div>'.'</li>';
	$output .= '</ol>';
	$output .= '</td>';	
	
	$output .= '<td>';
	$query = "SELECT change_results FROM $table_name WHERE id = %d";
	$change_results_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_change_results" name="doroto_tournament_parameters_change_results" style="width: 100%;">';
	$options = array(
		0 => esc_html__("Basic settings","doubles-rotation-tournament"), 
		1 => esc_html__("Allow early match termination","doubles-rotation-tournament"),
		2 => esc_html__("End the game only with a lead of 2 games","doubles-rotation-tournament"),
		3 => esc_html__("End the game early and even with a 2 game lead","doubles-rotation-tournament")
	);
	foreach ($options as $value => $name) {
  		 $selected = ($value == $change_results_table) ? ' selected="selected"' : '';
		 $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';

	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Can a player who is currently suspended be declared the winner?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT temp_suspend_winner FROM $table_name WHERE id = %d";
	$temp_suspend_winner_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_temp_suspend_winner" name="doroto_tournament_parameters_temp_suspend_winner">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $temp_suspend_winner_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Can a player in a special group win a tournament?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT special_group_can_win FROM $table_name WHERE id = %d";
	$special_group_can_win_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_special_group_can_win" name="doroto_tournament_parameters_special_group_can_win">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $special_group_can_win_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Skip matches where 2 players would play together in a special group?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT two_special_group FROM $table_name WHERE id = %d";
	$two_special_group_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_two_special_group" name="doroto_tournament_parameters_two_special_group">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $two_special_group_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';
	
	$output .= '<tr class="doroto-separator-background"><td></td><td></td></tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Skip matches where 2 non-special group players would play together?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT two_out_group FROM $table_name WHERE id = %d";
	$two_out_group_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_two_out_group" name="doroto_tournament_parameters_two_out_group">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $two_out_group_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';
	
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Can players independently enter game results?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT allow_input_results FROM $table_name WHERE id = %d";
	$allow_input_results_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_allow_input_results" name="doroto_tournament_parameters_allow_input_results">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $allow_input_results_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';

	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("After the tournament closes, allow a final match to determine the best pair?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT play_final_match FROM $table_name WHERE id = %d";
	$play_final_match_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_play_final_match" name="doroto_tournament_parameters_play_final_match">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $play_final_match_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';

	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Show control over the paid entry fee?", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT payment_display FROM $table_name WHERE id = %d";
	$payment_display_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
	$output .= '<select id="doroto_tournament_parameters_payment_display" name="doroto_tournament_parameters_payment_display">';
	$options = array(0 => esc_html__("No", "doubles-rotation-tournament"), 1 => esc_html__("Yes", "doubles-rotation-tournament"));
	foreach ($options as $value => $name) {
  		 $selected = ($value == $payment_display_table) ? ' selected="selected"' : '';
   	     $output .= "<option value=\"" . esc_attr($value) . "\"$selected>" . esc_html($name) . "</option>";
	}
	$output .= '</select>';
	$output .= '</td>';
    $output .= '</tr>';
	
	$output .= '<tr>';		
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("When you check the box, a new post dedicated only to this tournament will be created.", "doubles-rotation-tournament");	
	$only_admin_posts = intval(doroto_read_settings('only_admin_posts',1));
	if($only_admin_posts) $output .= ' '.esc_html__("(Available only for website administrators)", "doubles-rotation-tournament");
	$output .= '</td>';
	$output .= '<td>';	
	$output .='<input type="checkbox" id="doroto_tournament_parameters_new_post" name="doroto_tournament_parameters_new_post">';
	$output .= '</td>';	
	$output .= '</tr>';
	$output .= '<tr>';
	$output .= '<td class="doroto-table-width">';	
	$output .= esc_html__("Welcome text for the tournament. It will be inserted into a new post. HTML tags can be used.", "doubles-rotation-tournament");
	if($only_admin_posts) $output .= ' '.esc_html__("(Available only for website administrators)", "doubles-rotation-tournament");
	$output .= '</td>';	
	$output .= '<td>';
	$query = "SELECT invitation FROM $table_name WHERE id = %d";
	$invitation_table= $wpdb->get_var($wpdb->prepare($query, $tournament_id));
    $output .= '<textarea id="doroto_tournament_parameters_invitation" name="doroto_tournament_parameters_invitation" style="width: 100%;height:100px;">';
	$output .= esc_attr($invitation_table);
	$output .= '</textarea>';
	$output .= '</td>';	
	$output .= '</tr>';	

	$output .= '<tr>';		
	$output .= '<td class="doroto-table-width-red">';	
	$output .= esc_html__("Checking the box will delete all match results and put the tournament into open registration. Thanks to this option, you can test the course of the tournament with the option of returning to the default state. (Irreversible change!)", "doubles-rotation-tournament");
	$output .= '</td>';
	$output .= '<td>';	
	$output .='<input type="checkbox" id="doroto_tournament_parameters_empty_tournament" name="doroto_tournament_parameters_empty_tournament">';// Checkbox pro smazání turnaje
	$output .= '</td>';	
	$output .= '</tr>';
	
	$output .= '<tr>';		
	$output .= '<td class="doroto-table-width-red">';	
	$output .= esc_html__("Checking the box will delete the tournament record after saving. (Irreversible change!)", "doubles-rotation-tournament");
	$output .= '</td>';
	$output .= '<td>';	
	$output .='<input type="checkbox" id="doroto_tournament_parameters_delete_tournament" name="doroto_tournament_parameters_delete_tournament">';// Checkbox pro smazání turnaje
	$output .= '</td>';	
	$output .= '</tr>';
	$output .= '</table>';
	$output .= '</div>';
	
    if (!(doroto_is_admin ($tournament_id) > 0 )) {			
          $output .= "<div>".esc_html__("You are not authorized to change the parameters of tournament no.","doubles-rotation-tournament").' ' . esc_html($tournament_id) . "."."</div>";
    } else {
			if (!($tournament->close_tournament == 1 && strtotime($tournament->close_date) + 24 * 3600 < time())) {
				$output .= '<input type="submit" name="doroto_tournament_parameters_save" value="' . esc_html__("Save", "doubles-rotation-tournament") . '">';
			} else {
				$output .= "<div>".esc_html__("Tournament parameters cannot be changed more than 24 hours after closing.","doubles-rotation-tournament")."</div>";
			}
	}	
	$output .= wp_nonce_field('doroto_tournament_parameters_form_nonce', '_wpnonce', true, false);
    $output .= '</form>';	

    return $output;
}

//edit tournament variables after submitting form
function doroto_tournament_parameters_results() {
	global $wpdb;
	global $doroto_output_form;
	$output = '';
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_tournament_parameters_form_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }

	if (!is_user_logged_in()) {
    	$output = esc_html__("You must log in to change tournament parameters!", "doubles-rotation-tournament");
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}	
    if (isset($_POST['doroto_tournament_parameters_save'])) {
        $tournament_id = intval($_POST['tournament_id']);

        if (isset($_POST['doroto_tournament_parameters_delete_tournament'])) {			
			$tournament = doroto_prepare_tournament ($tournament_id);
			if($tournament) {
				$page_id = intval($tournament->page_id);
				if($page_id != null) {
					wp_delete_post($page_id, true);
				}
			}
             
			$wpdb->delete(
      		 	$wpdb->prefix . 'doroto_tournaments', 
        		array('id' => $tournament_id)
    			);

        } else {
            $allowed_html = doroto_allowed_html();            

            $name = sanitize_text_field($_POST['doroto_tournament_parameters_name']);
			$invitation = wp_kses($_POST['doroto_tournament_parameters_invitation'], $allowed_html);
            $max_games = intval($_POST['doroto_tournament_parameters_max_games']);
			$max_players = intval($_POST['doroto_tournament_parameters_max_players']);
			$whole_names = intval($_POST['doroto_tournament_parameters_whole_names']);
			$change_results = intval($_POST['doroto_tournament_parameters_change_results']);
			$temp_suspend_winner = intval($_POST['doroto_tournament_parameters_temp_suspend_winner']);
			$special_group_can_win = intval($_POST['doroto_tournament_parameters_special_group_can_win']);
			$two_special_group = intval($_POST['doroto_tournament_parameters_two_special_group']);
			$two_out_group = intval($_POST['doroto_tournament_parameters_two_out_group']);
			$allow_input_results = intval($_POST['doroto_tournament_parameters_allow_input_results']);
			$play_final_match = intval($_POST['doroto_tournament_parameters_play_final_match']);
			$courts_available = intval($_POST['doroto_tournament_parameters_courts_number']);
			$min_not_playing = intval($_POST['doroto_tournament_parameters_min_not_playing']);
            $table_name = $wpdb->prefix . 'doroto_tournaments';
			$payment_display = intval($_POST['doroto_tournament_parameters_payment_display']);
			
			$fields = array(
    			 'max_games' => $max_games,
				 'max_players' => $max_players,
   				 'whole_names' => $whole_names,
				 'change_results' => $change_results,
				 'temp_suspend_winner' => $temp_suspend_winner,
			   	 'special_group_can_win' => $special_group_can_win,
				 'two_special_group' => $two_special_group,
				 'two_out_group' => $two_out_group,
				 'allow_input_results' => $allow_input_results,
				 'play_final_match' => $play_final_match,
				 'courts_available' => $courts_available,
				 'min_not_playing' => $min_not_playing,
				 'invitation' => $invitation,
				 'payment_display' => $payment_display,
			);
			
			if (isset($_POST['doroto_tournament_parameters_empty_tournament'])) {
    			$fields['statistics'] = '';
    			$fields['matches_list'] = '';
    			$fields['open_registration'] = 1;
    			$fields['close_tournament'] = 0;
    			$fields['final_result'] = '';
    			$fields['final_four'] = '';
    			$fields['playing'] = '';
    			$fields['close_date'] = '0000-00-00 00:00:00';			
			}

			if ($name != '') {
   				 $fields['name'] = $name;
			}
			$wpdb->update(
  				  $table_name,
   				  $fields,
    		array('id' => $tournament_id)
			);

			$tournament = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}doroto_tournaments WHERE id = %d", $tournament_id));

			$statistics=doroto_create_statistics_table ($tournament,unserialize($tournament->players),intval($tournament->whole_names));	
			$wpdb->update("{$wpdb->prefix}doroto_tournaments", ['statistics' => serialize($statistics)], ['id' => $tournament_id]);
			
			if (isset($_POST['doroto_tournament_parameters_new_post'])) {
				$current_user = wp_get_current_user();
				$only_admin_posts = intval(doroto_read_settings('only_admin_posts',1));
				if((($only_admin_posts == 1) && (doroto_is_admin ($tournament_id) == 2)) || (($only_admin_posts == 0) && (doroto_is_admin ($tournament_id) > 0))) {
					 doroto_create_new_tournament_post($tournament_id);
				} else {
					$output .= esc_html__('You do not have the necessary rights to create a post.', 'doubles-rotation-tournament').'<br>';
				}
			}
        }
		$output .= esc_html__('Tournament parameters no.', 'doubles-rotation-tournament').' '.esc_html($tournament_id).' '.esc_html__('were saved.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"tournament-editing");
		exit;
    } 
}

add_shortcode('doroto_tournament_parameters', 'doroto_add_tournament_parameters');
add_action('admin_post_doroto_tournament_parameters', 'doroto_tournament_parameters_results');
add_action('admin_post_nopriv_doroto_tournament_parameters', 'doroto_tournament_parameters_results');
add_action('admin_post_doroto_tournament_parameters_save', 'doroto_tournament_parameters_results');
add_action('admin_post_nopriv_doroto_tournament_parameters_save', 'doroto_tournament_parameters_results');

//display tournament status in a form with hyperlink
function doroto_add_link_to_tournament ($atts = [], $content = null, $tag = ''){
    global $wpdb;
	$a = shortcode_atts( array(
        'tournament_id' => doroto_getTournamentId(),
    ), $atts );
	$tournament_id = intval($a['tournament_id']);
	
	$current_user_id = get_current_user_id();
	if(!doroto_check_need_to_display('doroto_tournament_log_link')) return '';	
	
    if (!isset($tournament_id)) {
        $output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
    	return $output;
    }

	if ($tournament_id == 0) {
  	  $output ="<div>".esc_html__('No tournament has been created yet.','doubles-rotation-tournament' )."</div>";
	  return $output;
	}
	
    $tournament = doroto_prepare_tournament ($tournament_id);
	if ($tournament == null) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}
	
	$open_registration = intval($tournament->open_registration);
	$whole_names = intval($tournament->whole_names);
	$output = '';
	if(!$open_registration && !doroto_check_if_presentation_on()) {
		$output .= '<div class="doroto-content-main">';
		$output .= '<h4 class="doroto-clickable-title">' . esc_html__('Details about tournament no.','doubles-rotation-tournament').' '. esc_html($tournament_id). ' ...</h4>';
		$output .= '<div class="doroto-content-container" id="details">';
	}	
		$output .= doroto_not_logged_message();
		$invitation = wp_kses($tournament->invitation, doroto_allowed_html());
		if($invitation != '') $output .= '<p>'.$invitation.'</p>';
	if($open_registration) {	
		$site_url = get_site_url();
		$output .= "<p>" . esc_html__("You can enter the tournament using this link:", "doubles-rotation-tournament") . " <a href=\"" . esc_url("{$site_url}/wp-admin/admin-ajax.php?action=doroto_register_player&tournament_id=" . esc_html($tournament_id)) . "\" data-type=\"URL\" data-id=\"" . esc_url("{$site_url}/wp-admin/admin-ajax.php?action=doroto_register_player&tournament_id=" . esc_html($tournament_id)) . "\">" . esc_html__("Log in/Log out from the tournament", "doubles-rotation-tournament") . "</a>.</p>";
	}
		
		$admin_users = unserialize($tournament->admin_users);
		$tournament_name = sanitize_text_field($tournament->name);
		$output .="<p>". esc_html__("The organizer of tournament no.", "doubles-rotation-tournament") . " ". esc_html($tournament_id)." (<b>".esc_html($tournament_name)."</b>) " . esc_html__("is", "doubles-rotation-tournament") . " <span class='doroto-info-text'>";
		$admin_names=[];
		foreach ($admin_users as $admin_id) {
			$user_data = doroto_get_also_false_user($admin_id);
			$admin_display_name = $user_data ? $user_data->display_name : '';
			if ($whole_names == 0) $admin_display_name = doroto_display_short_name($admin_display_name);
			$admin_names[]=$admin_display_name;
		}
		$output .= esc_html(implode(' '.__("&", "doubles-rotation-tournament").' ', $admin_names));
		$output .="</span>.</p>";	

		$admin_users = unserialize($tournament->admin_users); 
		$admin_info = '<p>'.esc_html__("If you wish, you can as an administrator", "doubles-rotation-tournament").' ';
		if (doroto_is_admin ($tournament_id) > 0 && $tournament->close_tournament != '1') {
   			 $open_registration_text = $tournament->open_registration == '1' ? esc_html__("close registration", "doubles-rotation-tournament") : esc_html__("reopen registration", "doubles-rotation-tournament");
   		     $output .= $admin_info;
			 $output .= "<a href='" . esc_url(admin_url('admin-ajax.php?action=doroto_toggle_registration&tournament_id=' . esc_html($tournament->id))) . "'>" . esc_html($open_registration_text) . "</a>.</p>";
		} 

		if (doroto_is_admin ($tournament_id) > 0 && $tournament->open_registration == '0' && !($tournament->close_tournament == '1' && strtotime($tournament->close_date) + 24 * 3600 < time())) {
				$play_final_match = intval($tournament->play_final_match);
				$final_result = unserialize($tournament->final_result);	
				if($play_final_match) {
					if(empty($final_result)) { 
						$output .= $admin_info;
						$close_tournament_text = $tournament->close_tournament == '1' ? esc_html__("back to drawn matches", "doubles-rotation-tournament") : esc_html__("start the final match", "doubles-rotation-tournament");
						$output .= "<a href='" . esc_url(admin_url('admin-ajax.php?action=doroto_toggle_tournament&tournament_id=' . esc_html($tournament_id))) . "'>".esc_html($close_tournament_text)."</a>.</p>";
					} 				
 				} else { 
					$output .= $admin_info;
				 	$close_tournament_text = $tournament->close_tournament == '1' ? esc_html__("reopen the tournament", "doubles-rotation-tournament") : esc_html__("end the tournament", "doubles-rotation-tournament");
   				 	$output .= "<a href='" . esc_url(admin_url('admin-ajax.php?action=doroto_toggle_tournament&tournament_id=' . esc_html($tournament_id))) . "'>" .esc_html($close_tournament_text) ."</a>.</p>";
				}
		} 	
	
	if(!$open_registration && !doroto_check_if_presentation_on()) {
		$output .= '</div></div>';
	}	
	return $output;
}
add_shortcode('doroto_tournament_log_link', 'doroto_add_link_to_tournament');

//display all available tournaments
function doroto_display_table($atts) {
    global $wpdb;
	$a = shortcode_atts( array(
        'display_rows' => doroto_read_settings('display_rows',20),
    ), $atts );
	
	if(!doroto_check_need_to_display('doroto_table')) return '';	
	$current_user = wp_get_current_user();

	$display_rows = intval($a['display_rows']);
	if($display_rows <= 0) $display_rows = 9999;

	$output = doroto_not_logged_message();

	$table_name = $wpdb->prefix . 'doroto_tournaments';
	$results = doroto_prepare_filtered_tournaments($display_rows);

    if(!empty($results)) {
        $output .= "<b>" . esc_html__("List of available tournaments:", "doubles-rotation-tournament") . "</b>";
		$output .= "<div class='doroto-table-responsive'>";
		$output .= "<table>";
        $output .= "<tr class='doroto-left-aligned'><th>" . esc_html__("ID", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Name", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Log in", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Players count", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Creation date", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Registration status", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Organizer", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Tournament status", "doubles-rotation-tournament") . "</th></tr>";
        
        foreach($results as $row) {
            $output .= "<tr>";
            $output .= "<td>" . esc_html($row->id) . "</td>";
			global $post;

			$output .= "<td><a href='" . esc_url(admin_url('admin-ajax.php?action=doroto_choose_tournament&tournament_id=' . esc_html($row->id))) . "'>" . esc_html($row->name) . "</a></td>";

			try {
   				 	$players = unserialize($row->players);
				} catch (Exception $e) {
   				 	$players = array(); 
				}

			$login_text = is_array($players) && in_array($current_user->ID, $players) ? esc_html__("Log out", "doubles-rotation-tournament") : esc_html__("Log in", "doubles-rotation-tournament");

			if($row->close_tournament != '1' && $row->open_registration != '0') {	
 			   $output .= "<td><a href='" . esc_url(admin_url('admin-ajax.php?action=doroto_register_player&tournament_id=' . esc_html($row->id))) . "'>" .esc_html($login_text)."</a></td>";
			} else {
 		   $output .= "<td>" .esc_html($login_text) ."</td>";
			}

            $output .= "<td>" . esc_html(count(unserialize($row->players)));
			$max_players = $row->max_players;
			if($max_players > 0) $output .= esc_html(" / " . $max_players);
			$output .= "</td>";
			
			$output .= "<td>" . esc_html(date('Y-m-d H:i', strtotime($row->create_date))) . "</td>";
            
         	$whole_names = intval($row->whole_names);
            $admin_users = unserialize($row->admin_users); 
			if (doroto_is_admin ($row->id) > 0) {
   			 	$open_registration_text = $row->open_registration == '1' ? esc_html__("close registration", "doubles-rotation-tournament") : esc_html__("reopen registration", "doubles-rotation-tournament");
    			if($row->close_tournament != '1') {
					$output .= "<td><a href='" . esc_url(admin_url('admin-ajax.php?action=doroto_toggle_registration&tournament_id=' . esc_html($row->id))) . "'>" .esc_html($open_registration_text) ."</a></td>";
				} else {
        			$output .= "<td>" .esc_html($open_registration_text). "</td>";
    				}
			} else {
   					 $open_registration_text = $row->open_registration == '1' ? esc_html__("registration allowed", "doubles-rotation-tournament") : esc_html__("registration closed", "doubles-rotation-tournament");
   					 $output .= "<td>" .esc_html($open_registration_text) ."</td>";
					}

            $output .= "<td>";
            foreach ($admin_users as $admin_id) {
				$user_info = doroto_get_also_false_user($admin_id);
				if ($user_info !== false) {
					if ($whole_names == 0) {
   						 $output .= esc_html(doroto_display_short_name($user_info->display_name) . " ");
					} else {
    			 		$output .= esc_html($user_info->display_name . " ");
					}
				} else {
   					 $output .= esc_html__("Unknown user", "doubles-rotation-tournament") . " ";
				}
			}
			$output .= "</td>";
			
			if (doroto_is_admin ($row->id) > 0 && !($row->close_tournament == '1' && strtotime($row->close_date) + 24 * 3600 < time())) {
				$tournament = doroto_prepare_tournament ($row->id);
				$play_final_match = intval($tournament->play_final_match);
				$final_result = unserialize($tournament->final_result);
				$tournament_id = intval($row->id);
				if($play_final_match) {
					if(empty($final_result)) { 
						$close_tournament_text = $row->close_tournament == '1' ? esc_html__("back to drawn matches", "doubles-rotation-tournament") : esc_html__("start the final match", "doubles-rotation-tournament");
						$output .= "<td><a href='" .  esc_url(admin_url('admin-ajax.php?action=doroto_toggle_tournament&tournament_id=' . esc_html($row->id))) . "'>" .esc_html($close_tournament_text). "</a></td>";
					} else {
						$close_tournament_text = $row->close_tournament == '1' ? esc_html__("finished tournament", "doubles-rotation-tournament") : esc_html__("open tournament", "doubles-rotation-tournament");
						$output .= "<td>" .esc_html($close_tournament_text). "</td>";						
					}				
 				} else { 		
				 	$close_tournament_text = $row->close_tournament == '1' ? esc_html__("reopen the tournament", "doubles-rotation-tournament") : esc_html__("end the tournament", "doubles-rotation-tournament");
   				 	$output .= "<td><a href='" .  esc_url(admin_url('admin-ajax.php?action=doroto_toggle_tournament&tournament_id=' . esc_html($row->id))) . "'>" .esc_html($close_tournament_text). "</a></td>";
				}
			} else { 
				  $close_tournament_text = $row->close_tournament == '1' ? esc_html__("finished tournament", "doubles-rotation-tournament") : esc_html__("open tournament", "doubles-rotation-tournament");
  				  $output .= "<td>" .esc_html($close_tournament_text). "</td>";
			}

            $output .= "</tr>";
        }
        $output .= "</table>";
		$output .= "</div>";
    } else {
        $output = esc_html__("No tournament has been found.", "doubles-rotation-tournament");
    }
    return $output;
}

add_shortcode('doroto_table', 'doroto_display_table');

//create a new tournament
function doroto_add_tournament_shortcode() {
	if(doroto_check_if_presentation_on()) return '';
	
    $output = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    $output .= '<input type="hidden" name="action" value="doroto_add_tournament_save">';
	
	if (isset($_SERVER['REQUEST_URI'])) $uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
  	else $uri = '';
	$uri = '/' . ltrim($uri, '/');
	
    $output .= '<input type="hidden" name="redirect_uri" value="' . esc_url($uri) . '">'; // add this line
    $output .= '<input type="submit" value="'.esc_html__('Create a new tournament','doubles-rotation-tournament').'">';
	$output .= wp_nonce_field('doroto_add_tournament_nonce', '_wpnonce', true, false);
    $output .= '</form>';
    return $output;
}

add_shortcode('doroto_add_tournament', 'doroto_add_tournament_shortcode');


//confirm a payment for a player in the tournament
function doroto_enter_payment_manually_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
    $a = shortcode_atts( array(
        'tournament_id' => doroto_getTournamentId(),
    ), $atts );
	
	if(doroto_check_if_presentation_on()) return '';
	
    $tournament_id = intval($a['tournament_id']);	
	$output = '';

    if (!is_user_logged_in()) {
		$output = "<div>".esc_html__('You must log in to confirm a payment!', 'doubles-rotation-tournament')."</div>";        
        return $output;
    }

    if (!isset($tournament_id)) {
        $output = "<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    if ($tournament_id == 0 ) {
        $output = "<div>".esc_html__('No tournament has been created yet.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    $tournament = doroto_prepare_tournament ($tournament_id);
	if (!isset($tournament)) {
        $output ="<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
		return $output;
    }
	
    $players = maybe_unserialize($tournament->players);
    $payment_done = maybe_unserialize($tournament->payment_done);
	$whole_names = intval($tournament->whole_names);
	if($whole_names != 0 && $whole_names != 1) $whole_names = 0;
	$payment_display = intval($tournament->payment_display);
	if($payment_display != 0 && $payment_display != 1) $payment_display = 0;
	
	if(!$payment_display) return null;
	
    if (!is_array($players)) {
        $players = [];
    }

    if (!is_array($payment_done)) {
        $payment_done = [];
    }
	
    $user_id = get_current_user_id(); 
    if (doroto_is_admin ($tournament_id) < 1) {
		$output .= "<div>".esc_html__('You do not have permission to confirm a payment.','doubles-rotation-tournament' )."</div>";
        return $output;
    }
	
   	if (!empty($players)) {
        	$players = array_diff($players, $payment_done);
	} else {
   		$output .= "<div>".esc_html__('All players have already paid.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users}");
     
	$output ="<div><b>".esc_html__('Confirm the payment of the selected player in tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</b>:";
	$output .= '<form id="doroto_enter_payment_manually" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_enter_payment_in_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

	if (count($players) > 0) {
    	$output .= '<select name="confirm_payment">';
   		foreach ($players as $player_id) {
        	$user = get_userdata($player_id);
			if (!in_array($player_id, $payment_done)) {
				$output .= '<option value="'.esc_attr($user->ID).'">'.esc_html(doroto_find_player_name ($user->ID,$whole_names)).'</option>';       		
			}	
    	}
    	$output .= '</select>';
		$output .= '<input type="submit" name="enter_manually" value="'.esc_html__("Confirm payment","doubles-rotation-tournament" ).'">';
	} else {
   	 	$output .= "<div>".esc_html__('All players have already paid.',"doubles-rotation-tournament" )."</div>";
	}
	$output .= wp_nonce_field('doroto_enter_payment_manually_nonce', '_wpnonce', true, false);
	$output .= '</form></div>';	

    return $output;
}

add_shortcode('doroto_enter_payment_manually', 'doroto_enter_payment_manually_shortcode');

//confirm a payment for a player in the tournament after submitting form
function doroto_enter_payment_manually_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_enter_payment_manually_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
	
    if (!isset($_POST['tournament_id']) || !isset($_POST['confirm_payment'])) {
        $output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }
    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['confirm_payment']);
 
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);	
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
   		$output = esc_html__('You must log in to restore the game!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
	$payment_done = unserialize($tournament->payment_done);
	if(!is_array($payment_done)) $payment_done = [];
	
	if(!in_array($player_id,$payment_done)) $payment_done[] = $player_id;
           
	$wpdb->update(
               $table_name,
   				    array(
         			   'payment_done' => serialize($payment_done),
       					 ),
                    array('id' => $tournament_id)
           );
	$output = esc_html__('The payment of the player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('has been added to the list.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
	doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_enter_payment_in_tournament', 'doroto_enter_payment_manually_form_submit');
add_action('admin_post_nopriv_doroto_enter_payment_in_tournament', 'doroto_enter_payment_manually_form_submit');


//remove a payment of a player in the tournament
function doroto_remove_payment_manually_shortcode($atts = [], $content = null, $tag = '') {
    global $wpdb;
    $a = shortcode_atts( array(
        'tournament_id' => doroto_getTournamentId(),
    ), $atts );
    $tournament_id = intval($a['tournament_id']);
	$output = '';
	if(doroto_check_if_presentation_on()) return '';
	
    if (!is_user_logged_in()) {
		$output = "<div>".esc_html__('You must log in to remove a payment!', 'doubles-rotation-tournament')."</div>";      
        return $output;
    }

    if (!isset($tournament_id)) {
        $output = "<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    if ($tournament_id == 0 ) {
        $output = "<div>".esc_html__('No tournament has been created yet.', 'doubles-rotation-tournament')."</div>";
        return $output;
    }

    $tournament = doroto_prepare_tournament ($tournament_id);
	if (!isset($tournament)) {
        $output ="<div>".esc_html__('The tournament was not found.', 'doubles-rotation-tournament')."</div>";
		return $output;
    }
	
    $players = maybe_unserialize($tournament->players);
    $payment_done = maybe_unserialize($tournament->payment_done);
	$whole_names = intval($tournament->whole_names);
	if($whole_names !=0 && $whole_names !=1) $whole_names =0;
	$payment_display = intval($tournament->payment_display);
	if($payment_display != 0 && $payment_display != 1) $payment_display = 0;
	
	if($payment_display == 0) return null;
	
    if (!is_array($players)) {
        $players = [];
    }

    if (!is_array($payment_done)) {
        $payment_done = [];
    }
	
    $user_id = get_current_user_id(); 
    if (doroto_is_admin ($tournament_id) < 1) {
		$output .= "<div>".esc_html__('You do not have permission to remove a payment.','doubles-rotation-tournament' )."</div>";
        return $output;
    }
	
   	if (empty($payment_done)) {
   		$output .= "<div>".esc_html__('None of the players have paid yet.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users}");
     
	$output ="<div><b>".esc_html__('Remove the payment of the selected player in tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id)."</b>:";
	$output .= '<form id="doroto_remove_payment_manually" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
	$output .= '<input type="hidden" name="action" value="doroto_enter_payment_in_tournament">';
	$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';

	if (count($payment_done) > 0) {
    	$output .= '<select name="remove_payment">';
   		foreach ($payment_done as $player_id) {
        	$user = get_userdata($player_id);
			$output .= '<option value="'.esc_attr($user->ID).'">'.esc_html(doroto_find_player_name ($user->ID,$whole_names)).'</option>';       		
    	}
    	$output .= '</select>';
		$output .= '<input type="submit" name="remove_manually" value="'.esc_html__("Remove payment","doubles-rotation-tournament" ).'">';
	} else {
   	 	$output .= "<div>".esc_html__('None of the players have paid yet.',"doubles-rotation-tournament" )."</div>";
	}
	$output .= wp_nonce_field('doroto_remove_payment_manually_nonce', '_wpnonce', true, false);
	$output .= '</form></div>';	

	return $output;
}

add_shortcode('doroto_remove_payment_manually', 'doroto_remove_payment_manually_shortcode');

//remove a payment of a player in the tournament after submitting form
function doroto_remove_payment_manually_form_submit() {
    global $wpdb;
	global $doroto_output_form;
	
	if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_remove_payment_manually_nonce')) {
        wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    }
	
    if (!isset($_POST['tournament_id']) || !isset($_POST['remove_payment'])) {
        $output = esc_html__('Tournament or user not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		return null;
    }
    $tournament_id = intval($_POST['tournament_id']);
    $player_id = intval($_POST['remove_payment']);
  
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);	
    if (!isset($tournament)) {
        $output = esc_html__('The tournament was not found.', 'doubles-rotation-tournament');
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
    }
	
	if (!is_user_logged_in()) {
   		$output = esc_html__('You must log in to remove a payment!','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
		doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
	$payment_done = unserialize($tournament->payment_done);
	if(!is_array($payment_done)) $payment_done = [];
	
	if(in_array($player_id,$payment_done)) $payment_done = array_diff($payment_done, array($player_id));
           
	$wpdb->update(
               $table_name,
   				    array(
         			   'payment_done' => serialize($payment_done),
       					 ),
                    array('id' => $tournament_id)
           );
	$output = esc_html__('The payment of the player', 'doubles-rotation-tournament').' '.esc_html(doroto_find_player_name ($player_id,$tournament->whole_names)).' '.esc_html__('has been removed from the list.', 'doubles-rotation-tournament');
	doroto_info_messsages_save ($output);
	doroto_redirect_modify_url($tournament_id,"");
	exit;
}
add_action('admin_post_doroto_enter_payment_in_tournament', 'doroto_remove_payment_manually_form_submit');
add_action('admin_post_nopriv_doroto_enter_payment_in_tournament', 'doroto_remove_payment_manually_form_submit');

//filter displayed tournaments 
function doroto_filter_tournaments_shortcode() {
    $output = '';
    ob_start();

    if (doroto_check_if_presentation_on()) {
        return $output;
    }

    $default_filter_option = get_user_meta(get_current_user_id(), 'doroto_filter_tournaments', true);
    $default_filter_option = $default_filter_option ? intval($default_filter_option) : 0;

    $output .= '<form method="post" action="">';
    $output .= '<select name="doroto_filter_option" id="doroto_filter_option">';
    $output .= '<option value="0" ' . selected(0, $default_filter_option, false) . '>' . esc_html__('No Filter', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="1" ' . selected(1, $default_filter_option, false) . '>' . esc_html__('Only Open Registration', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="2" ' . selected(2, $default_filter_option, false) . '>' . esc_html__('Still Playing', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="3" ' . selected(3, $default_filter_option, false) . '>' . esc_html__('Only Closed', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="4" ' . selected(4, $default_filter_option, false) . '>' . esc_html__('Where I Am Logged In', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="5" ' . selected(5, $default_filter_option, false) . '>' . esc_html__('Where I Am Not Logged In', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="6" ' . selected(6, $default_filter_option, false) . '>' . esc_html__('Where I Am Admin', 'doubles-rotation-tournament') . '</option>';
    $output .= '<option value="7" ' . selected(7, $default_filter_option, false) . '>' . esc_html__('Where I Am Not Admin', 'doubles-rotation-tournament') . '</option>';
    $output .= '</select>';
    $output .= '<input type="submit" name="doroto_filter_submit" value="' . esc_html__('Filter Tournaments', 'doubles-rotation-tournament') . '">';
    $output .= wp_nonce_field('doroto_filter_tournaments_nonce', '_wpnonce', true, false);
    $output .= '</form>';

    return $output . ob_get_clean();
}
add_shortcode('doroto_filter_tournaments', 'doroto_filter_tournaments_shortcode');

//filter displayed tournaments after submitting form
function doroto_filter_tournaments_result() {
    if (isset($_POST['doroto_filter_submit'])) {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_filter_tournaments_nonce')) {
        	wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    	}
		
        $filter_option = isset($_POST['doroto_filter_option']) ? intval($_POST['doroto_filter_option']) : 0;
        $filter_option = esc_attr($filter_option);
        update_user_meta(get_current_user_id(), 'doroto_filter_tournaments', $filter_option);
    }
}
add_action('init', 'doroto_filter_tournaments_result');

//link to run a presentation
function doroto_allow_presentation_shortcode() {
    $user_id = get_current_user_id();
	if($user_id == 0) {
		$output = "<div>".esc_html__("If you log in, you can start the presentation of the selected tournament.", "doubles-rotation-tournament")."</div>";
		return $output;
	}
    $doroto_presentation = unserialize(get_user_meta($user_id, 'doroto_presentation', true));

    if (empty($doroto_presentation) || !is_array($doroto_presentation)) {
        $doroto_presentation = array('allow_to_run' => 0,'slide' => 'doroto_games_to_play');
    } 
	$slide_value = $doroto_presentation['slide'];

    $text = esc_html__("Click here to run the presentation.", "doubles-rotation-tournament");
    $action_value = 1; 

    if ($doroto_presentation['allow_to_run'] == 1) {
		$text = esc_html__("Click here to stop the presentation.", "doubles-rotation-tournament");
        $action_value = 0; 
    }

    $output = '<a href="' . esc_url(add_query_arg('doroto_presentation_action', $action_value)) . '">' . esc_html($text) . '</a>';

    if (isset($_GET['doroto_presentation_action'])) {
        $action_value = intval($_GET['doroto_presentation_action']);

        update_user_meta($user_id, 'doroto_presentation', serialize(array('allow_to_run' => $action_value , 'slide' => $slide_value)));
    }
    return $output;
}

add_shortcode('doroto_allow_presentation', 'doroto_allow_presentation_shortcode');

//display div first
function doroto_display_div_first_shortcode($atts){
	global $wpdb;
	$attributs = shortcode_atts( array(
        'div_id' => '',
		'label' => '',
    ), $atts );

	$div_id = sanitize_text_field($attributs['div_id']);
	$label = sanitize_text_field($attributs['label']);

	if(!doroto_check_if_presentation_on()) {
		$output = '<div class="doroto-content-main">
				<h4 class="doroto-clickable-title">' . esc_html($label) . '</h4>
				<div class="doroto-content-container" id="' . esc_html($div_id) . '">';
	} else {
		$output = '';
	}	
	return $output;
}
add_shortcode('doroto_display_div_first', 'doroto_display_div_first_shortcode');

//display div last
function doroto_display_div_last_shortcode(){
	global $wpdb;

	if(!doroto_check_if_presentation_on()) {
		$output = '</div></div>';		
	} else {
		$output = '';
	}	
	return $output;
}
add_shortcode('doroto_display_div_last', 'doroto_display_div_last_shortcode'); 

//div with other background first
function doroto_display_other_background_first_shortcode($atts){
	global $wpdb;
	$attributs = shortcode_atts( array(
		'label' => '',
    ), $atts );
	$label = sanitize_text_field($attributs['label']);
	if(!doroto_check_if_presentation_on()) {
		$output = '<div class="' . esc_attr($label) . '">';	
	} else {
		$output = '';
	}	
	return $output;
}
add_shortcode('doroto_display_other_background_first', 'doroto_display_other_background_first_shortcode'); 

//div with other background last
function doroto_display_other_background_last_shortcode(){
	global $wpdb;
	if(!doroto_check_if_presentation_on()) {
		$output = '</div>';	
	} else {
		$output = '';
	}	
	return $output;
}
add_shortcode('doroto_display_other_background_last', 'doroto_display_other_background_last_shortcode'); 
