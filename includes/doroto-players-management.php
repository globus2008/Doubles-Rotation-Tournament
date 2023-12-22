<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//add a new player to statistics array
function doroto_add_player_statistics_table (&$statistics, $tournament,$players){
	global $wpdb;
	foreach ($statistics as &$player) {

      foreach ($players as $player_id) {
        if (!in_array($player_id, array_column($player['playmates'], 'player_id')) && $player['player_id'] != $player_id) { 
            $player['playmates'][] = array('player_id' => $player_id, 'count' => 0);
        }
        if (!in_array($player_id, array_column($player['playmates_L'], 'player_id')) && $player['player_id'] != $player_id) {
            $player['playmates_L'][] = array('player_id' => $player_id, 'count' => 0);
        }
        if (!in_array($player_id, array_column($player['playmates_P'], 'player_id')) && $player['player_id'] != $player_id) {
            $player['playmates_P'][] = array('player_id' => $player_id, 'count' => 0);
        }
        if (!in_array($player_id, array_column($player['opponents'], 'player_id')) && $player['player_id'] != $player_id) {
            $player['opponents'][] = array('player_id' => $player_id, 'count' => 0);
        }
      }
	}	
	unset($player); 
	return $statistics;
}	

//it goes through the $statistics array and if active for a player is 1, adds their player_id to the active players array
function doroto_find_active_players($statistics) {
    $activePlayers = array();
	if(empty($statistics)) return $activePlayers; 
    foreach ($statistics as $player) {
        if ($player['active'] == 1) {
            $activePlayers[] = $player['player_id'];
        }
    }
    return $activePlayers;
}

//return winner name
function doroto_get_winner($tournament_id,$whole_names) {
    global $wpdb;
	
    	$tournament = doroto_prepare_tournament ($tournament_id);

		if ($tournament == null) {
			$doroto_output_form = esc_html__('The tournament was not found.','doubles-rotation-tournament' );
   			return '';
		}
	
        $players = unserialize($tournament->players);
        if (empty($players)) {
			$doroto_output_form = '<p>'. esc_html__('No one has registered for the tournament yet.','doubles-rotation-tournament' ).'<p>'; 
            return '';
        }
		
		$statistics = unserialize($tournament->statistics);	
		$temp_suspend_winner = intval($tournament->temp_suspend_winner);
		
		// Sort the players by ratio, descending
       	usort($statistics, function($a, $b) {
        	return $b['ratio'] <=> $a['ratio'];
       	});

		$players = unserialize($tournament->players);

        $tournament_name = sanitize_text_field($tournament->name);
        $special_group = maybe_unserialize($tournament->special_group);
		$special_group_can_win = intval($tournament->special_group_can_win);

        if (!is_array($special_group)) {
            $special_group = [];
        }

		$winner_name_1 = '';
		$winner_name_2 = '';
		$winner_ratio_1 = 0;
		$winner_ratio_2 = 0;

        foreach ($statistics as $index => $player_result) {
            // Check if player ID is in special_group array and skip this player
            if ((in_array($player_result['player_id'], $special_group)) && !$special_group_can_win) {
                continue;
            } elseif (!$temp_suspend_winner && $player_result['active'] == 0) {
				continue;				
			} else {
				$name_winner = doroto_find_player_name ($player_result['player_id'],$whole_names);
                if($winner_name_1 == '') {
					$winner_name_1 = $name_winner;
					$winner_ratio_1 = $player_result['ratio'];
					continue;
				}
				if($winner_ratio_1 == $player_result['ratio'] && $winner_name_1 != '') {
					$winner_name_2 .= ' '. esc_html__('&','doubles-rotation-tournament' ).' '.$name_winner;
				} else {
					break;
				}	
            }
        }
        if ($winner_ratio_1 == 0) $winner_name = esc_html__('Not a single match was played, so everyone is a winner.','doubles-rotation-tournament' );
		else $winner_name = $winner_name_1.$winner_name_2;
		return $winner_name;
}

// Creating a shortcode for the player dropdown box
function doroto_get_players_from_tournaments($tournament_id) {
    global $wpdb;

    $query = $wpdb->prepare("SELECT players FROM {$wpdb->prefix}doroto_tournaments WHERE id = %d", $tournament_id);
    $result = $wpdb->get_var($query);

    if ($result) {
        $players = unserialize($result);
        if (is_array($players)) {
            return $players;
        }
    }
    return array(); // If there is no player list or there is an error, we return an empty field.
}

//register a new player in the tournament
function doroto_register_player($tournament_id) {
    global $wpdb;
    global $current_user;
    wp_get_current_user();
	$output = '';
	
    if (isset($_GET['tournament_id'])) {
        $tournament_id = intval($_GET['tournament_id']);
    } else {
        $output = esc_html__("Tournament ID was not provided.", "doubles-rotation-tournament");
		doroto_info_messsages_save ($output);
    	doroto_redirect_modify_url($tournament_id,"");
		exit;
    }

    if (!is_user_logged_in()) {
		$output = esc_html__("If you want to register for the tournament, you must log in to your account!", "doubles-rotation-tournament");
		doroto_info_messsages_save ($output);
    	doroto_redirect_modify_url($tournament_id,"");
		exit;
    }

    $table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament = doroto_prepare_tournament ($tournament_id);

    if ($tournament !== null) {
        // Checking if the tournament is open
        if ($tournament->open_registration == 1) {
            // Checking if the player field is defined and if it really is an field            
   			 // Get a list of tournament players
    		$players = maybe_unserialize($tournament->players);
    		$special_group = maybe_unserialize($tournament->special_group);
	
    		if (!is_array($players)) {
       		 $players = [];
    		}

    		if (!is_array($special_group)) {
       		 $special_group = [];
    		}

            // Checking if the user is already registered
            if (in_array($current_user->ID, $players)) {
				//check if the statistics field allows deleting a player from the players field
				$statistics = unserialize($tournament->statistics);
				$statistics_permittion = true;
				if(!empty($statistics)) {
					foreach($statistics as $player) {
						if($player['games'] > 0) $statistics_permittion = false;
					}
					if($statistics_permittion) {
						//clearing the statistics field
						$statistics = array();

                   		$wpdb->update(
                    	$table_name,
   				     	array(
							'statistics' => serialize($statistics)
       					 ),
                    	array('id' => $tournament_id)
                		);
						
					    $tournament = $wpdb->get_row($wpdb->prepare(
        					"SELECT * FROM $table_name WHERE id = %d",
      						  $tournament_id
    					));				
					} else {
					$output =  esc_html__("A player cannot be removed because at least one match has already been played in the tournament.", "doubles-rotation-tournament");
					doroto_info_messsages_save ($output);
    				doroto_redirect_modify_url($tournament_id,"");	
					exit;
					}
				}
               	// The user is registered, we will delete him
               	$players = array_diff($players, array($current_user->ID));
				$players = array_values($players);
				$special_group = array_diff($special_group, array($player_id));
				$special_group = array_values($special_group);
				
				$statistics=doroto_create_statistics_table ($tournament,$players,intval($tournament->whole_names));
               	$wpdb->update(
                    	$table_name,
   				     	array(
         			   		'players' => serialize($players),
							'statistics' => serialize($statistics), 
           					'special_group' => maybe_serialize(array_values($special_group)) // Reset array keys before serialize
       					 ),
                    	array('id' => $tournament_id)
               	);
               	$output = esc_html__("You have opted out of tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id).'.';
	
            } else {
                // The user is not registered, we will add him
                $max_players = $tournament->max_players;
				if(count($players) < $max_players || $max_players == 0) {
               	 $players[] = $current_user->ID;
					$statistics=doroto_create_statistics_table ($tournament,$players,$tournament->whole_names);
               	 $wpdb->update(
                  	  $table_name,
       					 array(
          			 	 'players' => serialize($players),
						  'statistics' => serialize($statistics)
       						 ),
                   	 array('id' => $tournament_id)
               	 );
                	$output = esc_html__("You signed up for tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id).'.';					
				} else {
					//the tournament has reached the maximum number of entries
					$output = esc_html__("We are sorry, but the maximum number of registered participants has been reached in tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id).'.';
				}	
            }
        } else {
            $output = esc_html__("The registration for the tournament has already been closed.", "doubles-rotation-tournament");
        }
    } else {
        $output = esc_html__("The tournament was not found.", "doubles-rotation-tournament");
    }
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}

add_action('wp_ajax_doroto_register_player', 'doroto_register_player');
add_action('wp_ajax_nopriv_doroto_register_player', 'doroto_register_player'); 
add_action('wp_ajax_doroto_toggle_registration', 'doroto_toggle_registration');