<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//offer new games to play
function doroto_offer_games($tournament_id) {
    global $wpdb;
	
	$tournament_id = intval($tournament_id);
	if ($tournament_id == 0){
		$output ='<div>'.esc_html__('No tournament has been created yet.','doubles-rotation-tournament' ).'</div>';
		return $output;
	};

	$tournament = doroto_prepare_tournament ($tournament_id);

	if ($tournament == null) {
		$output ="<div>".esc_html__('The tournament was not found.','doubles-rotation-tournament' )."</div>";
   		return $output;
	}

    $statistics = unserialize($tournament->statistics);	

    $activePlayers = doroto_find_active_players($statistics);

    $playing = $tournament->playing;
	if ($playing == '') {
		$playing = [];
	} else {
		$playing = unserialize($playing);
	}

	$matches_list = unserialize($tournament->matches_list);

    $special_group = $tournament->special_group;
	if ($special_group == '') {
		$special_group = [];
	} else {
		$special_group = unserialize($special_group);
	}

    $two_special_group = intval($tournament->two_special_group);
	$two_out_group = intval($tournament->two_out_group);
	
    if(!$matches_list || !$statistics) {
		$output ='<div class="doroto-warning-text">'.esc_html__('Match list not found!','doubles-rotation-tournament' ).'</div>';
        return $output;
    }
	
	$currently_played_count = count($playing)/4;
	$matches_to_select = doroto_matches_to_select_count($tournament,$currently_played_count);
	
    if ($matches_to_select > 0) {
        // Step 1: Filter active players
        $active_players = array_filter($statistics, function($player) {
            return $player['active'] == 1;
        });
	   
        // Step 2: Filter the players who are occupied
        $active_players = array_filter($active_players, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });
		
		//Step 3 Check if there are enough active players
   		if(count($active_players) < 4) {
				$output ='<p class="doroto-red-text">'.esc_html__('The small number of active players does not allow another match to be drawn.','doubles-rotation-tournament' ).'</p>';
        		return $output;
    	}		

        // Step 4: Make a copy of the $statistics array
        $statistics_temp = $active_players;

        // Step 5: Sort players by number of games played
		usort($statistics_temp, function($a, $b) {
            return $a['games'] - $b['games'];
        });

		// Step 6: Shuffle the order, but only at the beginning of the tournament
		$tournament_start=true;
		foreach ($statistics_temp as $player) {
			if($player['games'] > 0) $tournament_start = false;
		}	
		if($tournament_start) {
			shuffle($statistics_temp); 
		}

        // Step 7: Select the player with the lowest number of matches played
        $firstItem = reset($statistics_temp);
		$choosen_player = $firstItem['player_id'];
		
		$choosen_player_games = $firstItem['games'];

		// Step 8: Select other players with the same number of matches played and randomly select someone from them
		$possible_player_array = [];
		foreach ($statistics_temp as $player) {
			if($player['games'] == $choosen_player_games) $possible_player_array[] = $player['player_id'];
		}

		if(!empty($possible_player_array)) {
			$random_key = array_rand($possible_player_array);
			$choosen_player = $possible_player_array[$random_key];
		}
		
        // Step 9: Create fields for 'left' and 'right' position to search for teammate
        $possible_players_L1 = array();
        $possible_players_P1 = array();
		$possible_players_LP1 = array();
		$possible_opponents_list1 = array();

        foreach ($statistics_temp as $player) {
            if ($player['player_id'] == $choosen_player) {
                $possible_players_L1 = $player['playmates_L'];
                $possible_players_P1 = $player['playmates_P'];
				$possible_players_LP1 = $player['playmates'];
				$possible_opponents_list1 = $player['opponents'];
                break;
            }
        }

		// Step 10: Filter the players who are occupied
        $possible_players_L1 = array_filter($possible_players_L1, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });
		$possible_players_P1 = array_filter($possible_players_P1, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });
		$possible_players_LP1 = array_filter($possible_players_LP1, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });

	    // Step 11: If two_special_group is on and the player is in the special_group field, remove him
        if ($two_special_group == 1 && in_array($choosen_player, $special_group)) {
            $possible_players_L1 = array_filter($possible_players_L1, function($player) use ($special_group) {
                return !in_array($player['player_id'], $special_group);
            });

            $possible_players_P1 = array_filter($possible_players_P1, function($player) use ($special_group) {
                return !in_array($player['player_id'], $special_group);
            });
		    $possible_players_LP1 = array_filter($possible_players_LP1, function($player) use ($special_group) {
                return !in_array($player['player_id'], $special_group);
            });
        }

	    // Step 12: If two_out_group is on and both players are out of special_group, then remove that player
        if ($two_out_group == 1 && !in_array($choosen_player, $special_group)) {
            $possible_players_L1 = array_filter($possible_players_L1, function($player) use ($special_group) {
                return in_array($player['player_id'], $special_group);
            });

            $possible_players_P1 = array_filter($possible_players_P1, function($player) use ($special_group) {
                return in_array($player['player_id'], $special_group);
            });
		    $possible_players_LP1 = array_filter($possible_players_LP1, function($player) use ($special_group) {
                return in_array($player['player_id'], $special_group);
            });
        }
		
	    // Step 13: Filter out inactive players
        $possible_players_L1 = array_filter($possible_players_L1, function($player) use ($activePlayers) {
                return in_array($player['player_id'], $activePlayers);
        });

        $possible_players_P1 = array_filter($possible_players_P1, function($player) use ($activePlayers) {
                return in_array($player['player_id'], $activePlayers);
        });
        $possible_players_LP1 = array_filter($possible_players_LP1, function($player) use ($activePlayers) {
                return in_array($player['player_id'], $activePlayers);
        });		
   
		// Step 14: Check if a suitable teammate can be found and Line up players for 'left' and 'right' position
		if(count($possible_players_LP1) == 0) {
			$output ="<div>".esc_html__('The tournament settings do not allow the next match to be drawn.','doubles-rotation-tournament' )."</div>";
        	return $output;			
		}
        usort($possible_players_LP1, function($a, $b) {
            return $a['count'] - $b['count'];
        });
		
	    // Step 15: Determine the player with the fewest matches
        $firstItem_playmate = reset($possible_players_LP1);
		$firstItem_playmate_count = $firstItem_playmate['count'];
		$firstItem_playmate_player_id = $firstItem_playmate['player_id'];
	
		// Step 16: Select another player with the same number of co-matches played
		$possible_playmates_together = [];
		foreach ($possible_players_LP1 as $player) {
			if($player['count'] <= $firstItem_playmate_count) $possible_playmates_together[] = $player['player_id'];
		}

		// Step 17: Select other players with the same and fewer matches played and randomly select someone from them
		foreach ($statistics_temp as $player) {
			if($player['player_id'] == $firstItem_playmate_player_id) {
				$firstItem_playmate_player_total_count = $player['games'];
			}
		}
		
		$possible_co_player_array = [];
		foreach ($statistics_temp as $player) {
			if($player['games'] <= $firstItem_playmate_player_total_count) $possible_co_player_array[] = $player['player_id'];
		}
		
		// Step 18: merge both different fields
		$intersection = array_intersect($possible_playmates_together, $possible_co_player_array);
	
		usort($statistics_temp, function($a, $b) {
         	return $a['games'] - $b['games'];
      	});
		$statistics_temp_first = reset($statistics_temp); 
		$statistics_temp_first_games = $statistics_temp_first['games'];
		
		$statistics_short = array_filter($statistics_temp, function($player) use ($statistics_temp_first_games) {
            return $player['games'] == $statistics_temp_first_games;
        });
		shuffle($statistics_short);
			
		$choosen_co_player = $firstItem_playmate_player_id;
		if(!empty($intersection)) {
			foreach($statistics_short as $player) {
				if(in_array($player['player_id'], $intersection)) {
						$choosen_co_player = $player['player_id'];
						break;					
				}
			}			
		}
		
		//Step 19: define first_possible_players_L and first_possible_players_P
		foreach($possible_players_L1 as $player) {
			if($player['player_id'] == $choosen_co_player) {
				$first_possible_players_L = $player['count'];
				break;
			}
		}
	    foreach($possible_players_P1 as $player) {
			if($player['player_id'] == $choosen_co_player) {
				$first_possible_players_P = $player['count'];
				break;
			}
		}
		
		//Step 20: the left or right position will be random if the teammate has the same number of positions
		if ($first_possible_players_L == $first_possible_players_P) {
			$randomNumber = mt_rand(0, 1) % 2; 
			if($randomNumber) $first_possible_players_L++;
			else $first_possible_players_P++;
		}
	    if ($first_possible_players_L > $first_possible_players_P) {
            $both_possible_players = $possible_players_P1;
        } else {
			$both_possible_players = $possible_players_L1;
        }
		
        if ($first_possible_players_L > $first_possible_players_P) {
            $player_1 = $choosen_player;
            $player_2 =  $choosen_co_player;
        } else {
            $player_2 = $choosen_player;
            $player_1 = $choosen_co_player;
        }
		//Step 21: creating a list of teammate opponents
        $possible_players_L2 = array();
        $possible_players_P2 = array();
		$possible_opponents_playmate2 = array();

        foreach ($statistics_temp as $player) {
            if ($player['player_id'] == $choosen_co_player) {
				$possible_opponents_playmate2 = $player['opponents'];
                break;
            }
        }
		
		//Step 22: merge both fields
		$possible_opponents_main_player = array();

		foreach ($possible_opponents_list1 as $player_id => $data) {
    		if (isset($possible_opponents_playmate2[$player_id])) {
        		$data['count'] += $possible_opponents_playmate2[$player_id]['count'];
    		}
    		$possible_opponents_main_player[$player_id] = $data;
		}
	
        // Step 23: Remove players from $statistics_temp
        $statistics_temp = array_filter($statistics_temp, function($player) use ($player_1, $player_2) {
            return $player['player_id'] != $player_1 && $player['player_id'] != $player_2;
        });

		// Step 24: Sort players by number of games played
		usort($statistics_temp, function($a, $b) {
            return $a['games'] - $b['games'];
        });
		
	    // Step 25: Shuffle the order, but only at the beginning of the tournament
		$tournament_start=true;
		foreach ($statistics_temp as $player) {
			if($player['games'] > 0) $tournament_start = false;
		}	
		if($tournament_start) {
			shuffle($statistics_temp);
		}
        // Step 26: Begin Opponent Selection
        $avoid_players=array_merge($playing, array($player_1, $player_2));
     	$possible_opponents_main_player = array_filter($possible_opponents_main_player, function($player) use ($avoid_players) {
            return !in_array($player['player_id'], $avoid_players);
        });
	    usort($possible_opponents_main_player, function($a, $b) {
            return $a['count'] - $b['count'];
        });		

	   // Step 27: Choose the next player with the lowest number of matches played as the first opponent
        $firstItem = reset($statistics_temp);
		$choosen_opponent = $firstItem['player_id'];
		$choosen_opponent_games = $firstItem['games'];

		// Step 28: Select other players with the same or 1 more matches played and randomly select someone from them
		$possible_opponents_array = [];
		foreach ($statistics_temp as $player) {
			if($player['games'] <= $choosen_opponent_games) $possible_opponents_array[] = $player['player_id'];
		}
		if(!empty($possible_opponents_array)) {
			foreach ($possible_opponents_main_player as $player) {
				if(in_array($player['player_id'], $possible_opponents_array)) {
					$choosen_opponent = $player['player_id'];
					break;
				}
			}
		}

        // Step 29: Create a field for the 'left' and 'right' position for the other player
        $possible_players_L3 = array();
        $possible_players_P3 = array();
		$possible_players_LP3 = array();

        foreach ($statistics_temp as $player) {
            if ($player['player_id'] == $choosen_opponent) {
                $possible_players_L3 = $player['playmates_L'];
                $possible_players_P3 = $player['playmates_P'];
				$possible_players_LP3 = $player['playmates'];
                break;
            }
        }
		// Step 30: Filter the players who are occupied
        $possible_players_L3 = array_filter($possible_players_L3, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });
		$possible_players_P3 = array_filter($possible_players_P3, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });
		$possible_players_LP3 = array_filter($possible_players_LP3, function($player) use ($playing) {
            return !in_array($player['player_id'], $playing);
        });

        // Step 31: Line up the players for the 'left' and 'right' position for the other player
        usort($possible_players_L3, function($a, $b) {
            return $a['count'] - $b['count'];
        });

        usort($possible_players_P3, function($a, $b) {
            return $a['count'] - $b['count'];
        });
	    usort($possible_players_LP3, function($a, $b) {
            return $a['count'] - $b['count'];
        });

        // Step 32: If two_special_group is on and the player is in the special_group field, remove him
        if ($two_special_group == 1 && in_array($choosen_opponent, $special_group)) {
            $possible_players_L3 = array_filter($possible_players_L3, function($player) use ($special_group) {
                return !in_array($player['player_id'], $special_group);
            });

            $possible_players_P3 = array_filter($possible_players_P3, function($player) use ($special_group) {
                return !in_array($player['player_id'], $special_group);
            });
		    $possible_players_LP3 = array_filter($possible_players_LP3, function($player) use ($special_group) {
                return !in_array($player['player_id'], $special_group);
            });
        }
		
        // Step 33: If two_out_group is on and both players are out of special_group, then remove that player
        if ($two_out_group == 1 && !in_array($choosen_opponent, $special_group)) {
            $possible_players_L3 = array_filter($possible_players_L3, function($player) use ($special_group) {
                return in_array($player['player_id'], $special_group);
            });

            $possible_players_P3 = array_filter($possible_players_P3, function($player) use ($special_group) {
                return in_array($player['player_id'], $special_group);
            });
		    $possible_players_LP3 = array_filter($possible_players_LP3, function($player) use ($special_group) {
                return in_array($player['player_id'], $special_group);
            });
        }
		
	    // Step 34: Filter out inactive players
        $possible_players_L3 = array_filter($possible_players_L3, function($player) use ($activePlayers) {
                return in_array($player['player_id'], $activePlayers);
        });

        $possible_players_P3 = array_filter($possible_players_P3, function($player) use ($activePlayers) {
                return in_array($player['player_id'], $activePlayers);
        });
        $possible_players_LP3 = array_filter($possible_players_LP3, function($player) use ($activePlayers) {
                return in_array($player['player_id'], $activePlayers);
        });
		
		//Step 35: removes opponents from fields
		foreach ($possible_players_L3 as $key => $player) {
   			 if ($player['player_id'] == $player_1 || $player['player_id'] == $player_2) {
        		unset($possible_players_L3[$key]);
    		}
		}
		foreach ($possible_players_P3 as $key => $player) {
   			 if ($player['player_id'] == $player_1 || $player['player_id'] == $player_2) {
        		unset($possible_players_P3[$key]);
    		}
		}
		foreach ($possible_players_LP3 as $key => $player) {
   			 if ($player['player_id'] == $player_1 || $player['player_id'] == $player_2) {
        		unset($possible_players_LP3[$key]);
    		}
		}

		$possible_players_L3 = array_values($possible_players_L3);
		$possible_players_P3 = array_values($possible_players_P3);
		$possible_players_LP3 = array_values($possible_players_LP3);
    
		// Step 36: Evaluate the number of available opponents and Line up the players for the 'left' and 'right' position
		if(count($possible_players_LP3) == 0) {
			$output ="<div>". esc_html__('The tournament settings do not allow the next match to be drawn.','doubles-rotation-tournament' )."</div>";
        	return $output;			
		}
        usort($possible_players_LP3, function($a, $b) {
            return $a['count'] - $b['count'];
        });
		
	    // Step 37: Determine the player with the fewest matches
        $firstItem_playmate = reset($possible_players_LP3);
		$firstItem_playmate_count = $firstItem_playmate['count'];
		$firstItem_playmate_player_id = $firstItem_playmate['player_id'];
		
		// Step 38: Select another player with the same number of co-matches played
		$possible_playmates_together = [];
		foreach ($possible_players_LP3 as $player) {
			if($player['count'] == $firstItem_playmate_count) $possible_playmates_together[] = $player['player_id'];
		}

		// Step 39: Select another player with the same number of matches played, but filter the selected player from the base set
		$statistics_short = array_filter($statistics_temp, function($player) use ($firstItem_playmate_player_id) {
            return $player['player_id'] != $firstItem_playmate_player_id;
        });
		
		// Step 40: Sort players by number of games played
		usort($statistics_short, function($a, $b) {
            return $a['games'] - $b['games'];
        });
		
		// Step 41: Select other players with the same number of matches played and randomly select someone from them
		$possible_co_player_array = [];
		foreach ($statistics_short as $player) {
			if($player['games'] == $choosen_player_games) $possible_co_player_array[] = $player['player_id'];
		}
		
		// Step 42: merging the two different fields
		$intersection = array_intersect($possible_playmates_together, $possible_co_player_array);
		
		//Step 43: instead of randomly selecting a teammate from the other team, the player with the least number of counters is determined
		$possible_opp_players_together = array();
		foreach ($possible_opponents_list1 as $player_id => $data) {
    		if (isset($possible_opponents_playmate2[$player_id])) {
        		$data['count'] += $possible_opponents_playmate2[$player_id]['count'];
    		}
    		$possible_opp_players_together[$player_id] = $data;
		}
		
		// Step 44: Sort opponents by number of games played
		usort($possible_opp_players_together, function($a, $b) {
            return $a['count'] - $b['count'];
        });	
		
		//Step 45: the next selection will be according to the number of games
		$statistics_temp_first = reset($statistics_short); 
		$statistics_temp_first_games = $statistics_temp_first['games'];
		
		$statistics_short = array_filter($statistics_short, function($player) use ($statistics_temp_first_games) {
            return $player['games'] == $statistics_temp_first_games;
        });
		shuffle($statistics_short);
		
		$choosen_co_opp_player = $firstItem_playmate_player_id; 
		if(!empty($intersection)) {
			foreach($statistics_short as $player) {
				if(in_array($player['player_id'], $intersection)) {
						$choosen_co_opp_player = $player['player_id'];
						break;					
				}
			}			
		}		
		
		//Step 46: defines first_possible_players_L and first_possible_players_P
		foreach($possible_players_L3 as $player) {
			if($player['player_id'] == $choosen_co_opp_player) {
				$first_possible_players_L = $player['count'];
				break;
			}
		}
	    foreach($possible_players_P3 as $player) {
			if($player['player_id'] == $choosen_co_opp_player) {
				$first_possible_players_P = $player['count'];
				break;
			}
		}
		
		//Step 47: the left or right position will be random if the teammate has the same number of positions
		if ($first_possible_players_L == $first_possible_players_P) {
			$randomNumber = mt_rand(0, 1) % 2; // Výsledek bude vždy 0 nebo 1
			if($randomNumber) $first_possible_players_L++;
			else $first_possible_players_P++;
		}
		
		// Step 48: Designate players for the 'left' and 'right' positions for the other team	
        if ($first_possible_players_L > $first_possible_players_P) {
            $player_3 = $choosen_opponent;
            $player_4 =  $choosen_co_opp_player;
        } else {
            $player_4 = $choosen_opponent;
            $player_3 = $choosen_co_opp_player;
        }		
		
        // Step 49: Add the player to the $playing array
        $playing[] = $player_1;
        $playing[] = $player_2;
        $playing[] = $player_3;
        $playing[] = $player_4;

        // Step 50: Create a field for a new match
        $match = array(
            'match_number' => count($matches_list),
            'player_1' => $player_1,
            'player_2' => $player_2,
            'player_3' => $player_3,
            'player_4' => $player_4,
            'played' => 1,
            'hide' => 0,
            'result_1' => 0,
            'result_2' => 0
        );
		
        // Step 51: Save the $playing array back to the database
		$wpdb->update($wpdb->prefix . 'doroto_tournaments', array('playing' => serialize($playing)), array('id' => $tournament_id));

        // Step 52: Add a new match to the $matches_list array and save back to the database
        $matches_list[] = $match;
        $wpdb->update($wpdb->prefix . 'doroto_tournaments', array('matches_list' => serialize($matches_list)), array('id' => $tournament_id));

        // Step 53: Repeat all steps as needed
        $matches_to_select--;
        if ($matches_to_select > 0) {
            doroto_offer_games($tournament_id); // We recursively call the function for the next matches
        }
    }
}

//create wp database for DoRoTo
function doroto_create_tournaments_table() {
	global $wpdb;
    ob_start();

    $table_name = $wpdb->prefix . 'doroto_tournaments';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
			name text NOT NULL,
			open_registration BOOLEAN DEFAULT 1,
			close_tournament BOOLEAN DEFAULT 0,
			admin_users text NOT NULL,
			max_games TINYINT UNSIGNED DEFAULT 5,
			max_players TINYINT UNSIGNED DEFAULT 0,
			change_results TINYINT DEFAULT 3,
			temp_suspend_winner BOOLEAN DEFAULT 1,
			special_group_can_win BOOLEAN DEFAULT 1,
			two_special_group BOOLEAN DEFAULT 1,
			two_out_group BOOLEAN DEFAULT 0,
			allow_input_results BOOLEAN DEFAULT 1,
			play_final_match BOOLEAN DEFAULT 1,
			final_result text NOT NULL,
			whole_names BOOLEAN DEFAULT 0,
			courts_available TINYINT UNSIGNED DEFAULT 2,
			min_not_playing INT UNSIGNED DEFAULT 7,
			final_four text NOT NULL,
			players text NOT NULL,
			playing text NOT NULL,
			special_group text NOT NULL,
			statistics mediumtext,
            matches_list longtext NOT NULL,
			create_date datetime DEFAULT NULL,
			close_date datetime DEFAULT NULL,
			page_id INT,
			invitation mediumtext,
			payment_display BOOLEAN DEFAULT 0,
			payment_done text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

//create a new tournament
function doroto_insert_tournament() {
	global $wpdb;
    ob_start();
    $table_name = $wpdb->prefix . 'doroto_tournaments';
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {	
   		 doroto_create_tournaments_table;
	}	

	$current_user = wp_get_current_user();
	$admin_users[] = $current_user->ID;	

	$current_date = date('Y-m-d');

	$name = "DoRoTo " . $current_date;

	$last_tournament = $wpdb->get_row($wpdb->prepare(
   		"SELECT * FROM $table_name WHERE admin_users LIKE %s ORDER BY id DESC LIMIT 1",    	
		'%' . $wpdb->esc_like('i:' . $current_user->ID . ';') . '%'
	));

	
	$special_group = [];
	$players = [];
	$statistics = [];
    if ($last_tournament) {
        $wpdb->insert(
            $table_name,
            array(
                'admin_users' => serialize($admin_users),
                'create_date' => date('Y-m-d H:i:s'),
                'name' => sanitize_text_field($name),
				'max_games' => intval($last_tournament->max_games),
				'max_players' => intval($last_tournament->max_players),
				'change_results' => intval($last_tournament->change_results),
				'temp_suspend_winner' => intval($last_tournament->temp_suspend_winner),
				'special_group_can_win' => intval($last_tournament->special_group_can_win),
				'two_special_group' => intval($last_tournament->two_special_group),
				'two_out_group' => intval($last_tournament->two_out_group),
				'allow_input_results' => intval($last_tournament->allow_input_results),
				'play_final_match' => intval($last_tournament->play_final_match),
				'whole_names' => intval($last_tournament->whole_names),
				'courts_available' => intval($last_tournament->courts_available),
				'min_not_playing' => intval($last_tournament->min_not_playing),
				'special_group' => serialize ($special_group),
				'statistics' => serialize ($statistics),
				'players' => serialize ($players),
				'invitation' => esc_html__('I invite you to', 'doubles-rotation-tournament').' <b>'. esc_html__('Doubles Rotation Tournament!', 'doubles-rotation-tournament').'</b>',
				'payment_display' => intval($last_tournament->payment_display)
            )
        );
    } else {
	$wpdb->insert(
    	$table_name,
    	array(
       	 	'admin_users' => serialize($admin_users), 
        	'create_date' => date('Y-m-d H:i:s'), 
			'name' => sanitize_text_field($name), 
			'special_group' => serialize ($special_group),
			'players' => serialize ($players),
			'statistics' => serialize ($statistics),
			'invitation' => esc_html__('I invite you to', 'doubles-rotation-tournament').' <b>'. esc_html__('Doubles Rotation Tournament!', 'doubles-rotation-tournament').'</b>',
			'payment_done' => serialize ($payment_done)
            )
        );
    }
    return $wpdb->insert_id;
}

// match number '0' for right array setting
function doroto_generate_fake_match($players){
	$matches = array();
	$players = array_values(unserialize($players));
	$match = array(
                'match_number' => 0,
                'player_1' => $players[0],
                'player_2' => $players[1],
                'player_3' => $players[2],
                'player_4' => $players[3],
                'played' => 0,
                'hide' => 1,
                'result_1' => 0,
                'result_2' => 0
            );
	$matches[] = $match;
	return $matches;
}

//find match by id
function doroto_get_match_by_id($match_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'doroto_tournaments';

    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT matches_list FROM $table_name WHERE ID = %d",
        $match_id
    ));

    if ($result !== null) {
        $array = explode(", ", trim($result, "{}"));
        $output = array(
            "ID" => (int) $array[0],
            "P1" => $array[1],
            "P2" => $array[2],
            "P3" => $array[3],
            "P4" => $array[4],
            "played" => (int) $array[5],
			"hide" => (int) $array[6],
            "result1" => (int) $array[7],
            "result2" => (int) $array[8]
        );

        return $output;
    }
}

//create an array with players statistics
function doroto_create_statistics_table ($tournament,$players,$whole_names){
	global $wpdb;
	if (empty($players)) {
		$statistics = array();
		return $statistics;
	}
	
	$players = array_values($players);
	
	$statistics = unserialize($tournament->statistics);

	if (empty($statistics)) {
		$statistics = array();	
	} else	{
		$statistics=doroto_add_player_statistics_table ($statistics,$tournament,$players);			
	}

	foreach ($players as $player) {
   	 	$user = doroto_get_also_false_user($player);
   		$player_id = $player;

   	 	$existing_player = false;
    	foreach ($statistics as $existing) {
      	  	if ($existing['player_id'] === $player_id) {
         	   $existing_player = true;
			   break;
			}
    	}

    	if (!$existing_player) {
		$playmates = array();

			for ($i = 0; $i < count($players); $i++) {
    		if($player_id == $players[$i]) continue; 
				$onerow=array(
					'player_id' => $players[$i],
					'count' => 0,
				);
				$playmates[] = $onerow;
		}	
        $player_info = array(
            	'player_id' => $player_id,
				'active' => '1',
            	'display_name' => intval($whole_names) ? esc_html($user->display_name) : esc_html(doroto_display_short_name($user->display_name)),
			 	'games' => 0, // Number of matches
            	'won' => 0, // Number of games won
            	'lost' => 0, // Number of games lost
            	'ratio' => 0, // The ratio of games won to games lost
			    'playmates' => $playmates, // An empty field for players with whom he played in the right+left position
            	'playmates_L' => $playmates, // An empty field for players he played with in the position on the right
			 	'playmates_P' => $playmates, // An empty field for players with whom he played in the position on the left
            	'opponents' => $playmates, // An empty field for players he played against
       	 );

       	 $statistics[] = $player_info;
    	}
	}
	
    usort($statistics, function($a, $b) {
            return $b['ratio'] <=> $a['ratio'];
    });
	return $statistics;
}

//definition of global variable at the beginning
function doroto_define_permanent_tournament_id() {
	global $doroto_pernament_tournament_id; 
	$doroto_pernament_tournament_id = 0; 
}

add_action('wp_loaded', 'doroto_define_permanent_tournament_id');

// create a new tournament after submitting form
function doroto_add_tournament_result() {
    global $wpdb;
	$tournament_id = doroto_getTournamentId();
	if ($tournament_id <= 0){
		$output = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);	
		return;
	};
	
	if (!is_user_logged_in()) { 
		$output = esc_html__('You must log in to add a tournament!','doubles-rotation-tournament');
		doroto_info_messsages_save ($output);		
    	doroto_redirect_modify_url($tournament_id,"");
		exit;
	}
	
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && $_POST['action'] == 'doroto_add_tournament_save') {
			
			if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_add_tournament_nonce')) {
        		wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    		}
			
			if(doroto_read_settings('only_admin_creates', 0) == 1) {
				$current_user = wp_get_current_user();
				if (!in_array('administrator', (array) $current_user->roles) && !in_array('editor', (array) $current_user->roles) && !in_array('author', (array) $current_user->roles) ) {
					$output = esc_html__('You do not have site administrator privileges to create a new tournament.','doubles-rotation-tournament');
					doroto_info_messsages_save ($output);	
					doroto_redirect_modify_url(0,"");	
					exit;
				}
			}
            $tournament_id = doroto_insert_tournament();
			$output = esc_html__('A new tournament has just been created.','doubles-rotation-tournament');
			doroto_info_messsages_save ($output);	
			doroto_redirect_modify_url($tournament_id,"");
			exit;
        }
    } else {
        if (isset($_GET['tournament_id'])) {
			$tournament_id = intval($_GET['tournament_id']); 
			if($tournament_id <= 0) {
				$output = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
			} else {
				$output = esc_html__('Tournament no.', 'doubles-rotation-tournament').' ' . esc_html($tournament_id) .' '. esc_html__('was created.', 'doubles-rotation-tournament');
			}			
        }
    }
    return $output;
}

add_action('admin_post_doroto_add_tournament_save', 'doroto_add_tournament_result');
add_action('admin_post_nopriv_doroto_add_tournament_save', 'doroto_add_tournament_result');

// select 4 finalists 
function doroto_select_final_doubles($player_results, $tournament, $current_user) {
    $output = ''; 
    $current_user_id = intval($current_user->ID);

	$tournament_id = intval($tournament->id);

    if (doroto_is_admin ($tournament_id) > 0) {
        $output .= '<p>' . esc_html__('Confirm the final pairings for the match that will determine the winning team.', 'doubles-rotation-tournament');
        $output .= "<div class='doroto-table-responsive'>";
        $output .= "<table>";
        $output .= "<tr><th>" . esc_html__("L1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R2", "doubles-rotation-tournament") . "</th></tr>";

        $output .= '<form method="post" action="">';
        $output .= "<tr>";
        $output .= "<td>";
        $output .= '<select name="l1" id="l1">';
        foreach ($player_results as $player) {
            $selected = ($player['player_id'] == $player_results[0]['player_id']) ? 'selected' : '';
            $output .= '<option value="' . esc_attr($player['player_id']) . '" ' . $selected . '>' . esc_html(doroto_find_player_name ($player['player_id'],$tournament->whole_names)) . '</option>';
        }
        $output .= '</select></td>';

        $output .= "<td>";
        $output .= '<select name="p1" id="p1">';
        foreach ($player_results as $player) {
            $selected = ($player['player_id'] == $player_results[1]['player_id']) ? 'selected' : '';
            $output .= '<option value="' . esc_attr($player['player_id']) . '" ' . $selected . '>' . esc_html(doroto_find_player_name ($player['player_id'],$tournament->whole_names)) . '</option>';
        }
        $output .= '</select></td>';

        $output .= "<td>";
        $output .= '<select name="l2" id="l2">';
        foreach ($player_results as $player) {
            $selected = ($player['player_id'] == $player_results[2]['player_id']) ? 'selected' : '';
            $output .= '<option value="' . esc_attr($player['player_id']) . '" ' . $selected . '>' . esc_html(doroto_find_player_name ($player['player_id'],$tournament->whole_names)) . '</option>';
        }
        $output .= '</select></td>';

        $output .= "<td>";
        $output .= '<select name="p2" id="p2">';
        foreach ($player_results as $player) {
            $selected = ($player['player_id'] == $player_results[3]['player_id']) ? 'selected' : '';
            $output .= '<option value="' . esc_attr($player['player_id']) . '" ' . $selected . '>' . esc_html(doroto_find_player_name ($player['player_id'],$tournament->whole_names)). '</option>';
        }
        $output .= '</select></td>';
        $output .= '</tr></table></div>';
		$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';
        $output .= '<input type="submit" name="final_doubles" value="' . esc_html__("Save the composition of the final group", "doubles-rotation-tournament") . '">';
        $output .= '</form></p>';        
    } else {
        $output .= '<p>' . esc_html__('You do not have permission to make a final group.', 'doubles-rotation-tournament') . '</p>';
    }
    return $output; 
}


//save 4 finalists 
function doroto_save_final_doubles() {
    global $wpdb;
	global $doroto_output_form;
	$output = '';

    if (isset($_POST['final_doubles']) && isset($_POST['tournament_id'])) {
            $l1 = intval($_POST['l1']);
            $p1 = intval($_POST['p1']);
            $l2 = intval($_POST['l2']);
            $p2 = intval($_POST['p2']);
		
			if(	($l1 <= 0) || ($p1 <= 0) || ($l2 <= 0) || ($p2 <= 0) ){
				$output = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
				$doroto_output_form = $output; 
        		return $output;
			}		
		
			$tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
			$tournament = doroto_prepare_tournament ($tournament_id);
			
			if ($tournament == null) {
				$doroto_output_form = esc_html__('The tournament was not found.','doubles-rotation-tournament' );
        		return $doroto_output_form;
			}

            if ($l1 != $p1 && $l1 != $l2 && $l1 != $p2 && $p1 != $l2 && $p1 != $p2 && $l2 != $p2) {
                doroto_update_final_four($tournament, $l1, $p1, $l2, $p2); 
                $output .= esc_html__('Final group composition saved.', 'doubles-rotation-tournament').'<br>';
				$output .= esc_html__('You can start playing the final match.', 'doubles-rotation-tournament') ;
            } else {
                $output .= esc_html__('Please choose different names for L1, R1, L2 and R2!', 'doubles-rotation-tournament');
            }
    }
	$doroto_output_form = $output;
	return $output;
}

add_action('init', 'doroto_save_final_doubles'); //template_redirect

//result final match
function doroto_result_final_doubles($player_results, $tournament, $current_user) {
    $output = ''; 
    $current_user_id = $current_user->ID;

    $admin_users = unserialize($tournament->admin_users);
	$double_players = unserialize($tournament->final_four);
	$whole_names = intval($tournament->whole_names);
	
	
	$current_user_id = get_current_user_id();
	$players = unserialize($tournament->final_four);
	$tournament_id = intval($tournament->id);
	$final_result = unserialize($tournament->final_result);
	$allow_input_results = intval($tournament->allow_input_results);
	
        $output .= '<p>';
        $output .= "<div class='doroto-table-responsive'>";
        $output .= "<table>";
        $output .= "<tr class = 'doroto-left-aligned'><th>" . esc_html__("L1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R1", "doubles-rotation-tournament") . "</th><th>" . esc_html__("L2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("R2", "doubles-rotation-tournament") . "</th><th>" . esc_html__("Result", "doubles-rotation-tournament") . "</th>";
		if(empty($final_result)) $output .= "<th>" . esc_html__("Save", "doubles-rotation-tournament") . "</th>";
		$output .= "</tr>";
	
        $output .= "<tr>";
        $output .= "<td>";
        $output .= esc_html(doroto_find_player_name ($double_players['l1'],$whole_names));
        $output .= '</td>';
		$output .= "<td>";
        $output .= esc_html(doroto_find_player_name ($double_players['p1'],$whole_names));
        $output .= '</td>';
		$output .= "<td>";
        $output .= esc_html(doroto_find_player_name ($double_players['l2'],$whole_names));
        $output .= '</td>';
		$output .= "<td>";
        $output .= esc_html(doroto_find_player_name ($double_players['p2'],$whole_names));
        $output .= '</td>';
		

		if((in_array($current_user_id, $players) && $allow_input_results)|| (doroto_is_admin ($tournament_id) > 0)) {
			$output .= '<td>';
			if(empty($final_result)) {

				if (isset($_SERVER['REQUEST_URI'])) $uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
				else $uri = '';
		
				$uri = '/' . ltrim($uri, '/');
				
				$output .= '<form method="post" action="'.esc_url($uri).'">';
           	 	// Adding the form for results
				$output .= '<input type="hidden" name="tournament_id" value="' . esc_attr($tournament_id) . '">';
    			$output .= '<select name="final_result">';
				$output .= doroto_possible_results ($tournament);
				$output .= '</td><td>';
				$output .= '<input type="submit" name="submit_final_result" value="'. esc_html__('Save','doubles-rotation-tournament').'">';
            	$output .= '</form>';				
			} else {
				$output .= esc_html($final_result['result_1'].' : '.$final_result['result_2']);
			}
			$output .= '</td>';
			$output .= '</tr></table></div>';
        	$output .= '</p>';
    	} else {
			if(!empty($final_result)) {
				$output .= '<td>';
				$output .= esc_html($final_result['result_1'].' : '.$final_result['result_2']);
				$output .= '</td>';
			}	
			$output .= '</tr></table></div>';
        	$output .= '</p>';
		}			
    return $output; 
}

//update final match result
function doroto_update_final_match_result() {
    global $wpdb;
	global $doroto_output_form;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tournament_id']) && isset($_POST['final_result'])) {
		$final_result = sanitize_text_field($_POST['final_result']);
		$tournament_id = isset($_POST['tournament_id']) ? intval($_POST['tournament_id']) : 0;
		if(doroto_save_final_result($tournament_id,$final_result)) {
		    $output = esc_html__('The final match is over!','doubles-rotation-tournament' );
			$doroto_output_form = $output;
        	return $output;
		}
    }
}
add_action('init', 'doroto_update_final_match_result');

//save final match result 
function doroto_save_final_result($tournament_id,$final_result){
		global $wpdb;
		global $doroto_output_form;
		$tournament_id = intval($tournament_id);
	
        list($result_1, $result_2) = explode(':', sanitize_text_field($final_result));
		$results_array = array();
	
		$result_1 = intval($result_1);
		$result_1 = is_numeric($result_1) ? $result_1 : 0;
		$result_2 = intval($result_2);
		$result_2 = is_numeric($result_2) ? $result_2 : 0;
		if(($result_1 < 0) || ($result_2 < 0) || (($result_1 == 0) && ($result_2 == 0))){
			$doroto_output_form = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
        	return 0;
		}

		$results_array['result_1'] = $result_1;
		$results_array['result_2'] = $result_2;	

		$tournament = doroto_prepare_tournament ($tournament_id);

        if ($tournament) {
            $wpdb->update("{$wpdb->prefix}doroto_tournaments", ['final_result' => serialize($results_array)], ['id' => $tournament_id]);
        	return 1;
        } else {
			return 0;
		}
}

//update selected 4 finalists
function doroto_update_final_four($tournament, $l1, $p1, $l2, $p2) {
    global $wpdb;

    $tournament_id = intval($tournament->id);
    $final_four_data = array(
        'l1' => $l1,
        'p1' => $p1,
        'l2' => $l2,
        'p2' => $p2,
    );
    $table_name = $wpdb->prefix . 'doroto_tournaments';
                    
	$wpdb->update(
                    $table_name,
   				     array(
         			   'final_four' => serialize($final_four_data)
       					 ),
                    array('id' => $tournament_id)
                );
}

//display all possible results
function doroto_possible_results ($tournament) {
	$max_games = intval($tournament->max_games);
	$change_results = intval($tournament->change_results);
	$output = '';
	
	//standard game
	if($max_games == 13) {
			$options = array('0:6','1:6','2:6','3:6','4:6','6:0','6:1','6:2','6:3','6:4','6:7','7:6');
	    foreach ($options as $option) {
    		$output .= "<option value='" . esc_attr($option) . "'>" . esc_html($option) . "</option>";
		}
	
	} 	
	//The game can only be ended with a lead of 2 games
	elseif ($change_results == 2) {
		if($max_games == 3) $options = array('0:3','1:3','2:3','3:0','3:1','3:2');
		if($max_games == 5) $options = array('0:5','1:4','2:4','3:4','4:1','4:2','4:3','5:0');
		if($max_games == 7) $options = array('0:7','1:6','2:5','3:5','4:5','5:2','5:3','5:4','6:1','7:0');
		if($max_games == 9) $options = array('0:9','1:8','2:7','3:6','4:6','5:6','6:5','6:4','6:3','7:2','8:1','9:0');
		if($max_games == 11) $options = array('0:11','1:10','2:9','3:8','4:7','5:7','6:7','7:4','7:5','7:6','8:3','9:2','10:1','11:0');		
		foreach ($options as $option) {
    		$output .= "<option value='" . esc_attr($option) . "'>" . esc_html($option) . "</option>";
		}
		
	} 
	//The game can be ended with a lead of 2 games and prematurely
	elseif ($change_results == 3) {
		if($max_games == 3) $options = array('0:3','1:3','2:3','3:0','3:1','3:2');
		if($max_games == 5) $options = array('0:4','0:5','1:4','2:4','3:4','4:0','4:1','4:2','4:3','5:0');
		if($max_games == 7) $options = array('0:5','0:6','0:7','1:5','1:6','2:5','3:5','4:5','5:0','5:1','5:2','5:3','5:4','6:0','6:1','7:0');
		if($max_games == 9) $options = array('0:6','0:7','0:8','0:9','1:6','1:7','1:8','2:6','2:7','3:6','4:6','5:6','6:0','6:1','6:2','6:3','6:4','6:5','7:0','7:1','7:2','8:0','8:1','9:0');
		if($max_games == 11) $options = array('0:7','0:8','0:9','0:10','0:11','1:7','1:8','1:9','1:10','2:7','2:8','2:9','3:7','3:8','4:7','5:7','6:7', '7:0','7:1','7:2','7:3','7:4','7:5','7:6','8:0','8:1','8:2','8:3','9:1','9:2','10:0','10:1','11:0');	
		foreach ($options as $option) {
    		$output .= "<option value='" . esc_attr($option) . "'>" . esc_html($option) . "</option>";
		}

	} 
	//If the match can no longer be won, then the option of early termination can be enabled here.		
	else {
      for($i = 0; $i <= $max_games; $i++) {
		if ($change_results == 1 && (($max_games-$i) > ($max_games/2)) && (ceil($max_games/2)+$i < $max_games)) {

			for ($cnt = 0; (ceil($max_games / 2) + $cnt) < $max_games - $i; $cnt++) {
    			$value = $i . ':' . (intval(ceil($max_games / 2)) + $cnt);
    			$output .= "<option value='" . esc_attr($value) . "'>" . esc_html($value) . "</option>";
			}
		}

		if ($change_results == 1 && ($i > ($max_games / 2))) {
    		for ($cnt = 0; ($i + $cnt) < $max_games; $cnt++) {
        		$value = $i . ':' . $cnt;
        		$output .= "<option value='" . esc_attr($value) . "'>" . esc_html($value) . "</option>";
    		}
		}
  
		//Basic settings according to the parameter Maximum number of games in a match
	    $output .= "<option value='" . esc_attr("{$i}:" . ($max_games - $i)) . "'>" . esc_html("{$i}:" . ($max_games - $i)) . "</option>";

      }
	}
	return $output;
}


//update mach result after submitting form
function doroto_update_match_result() {
    global $wpdb;
	global $doroto_output_form;
	
	$match_number_post = isset($_POST['match_number']) ? intval($_POST['match_number']) : 0;
	
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['result']) && isset($_POST['match_number']) && isset($_POST['hide_' . $match_number_post])) {
		
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'doroto_games_to_play_nonce')) {	
        	wp_die( esc_html__('Invalid request.','doubles-rotation-tournament' ) );
    	}
		
		if($match_number_post <= 0) {
			$output = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
			$doroto_output_form = $output; 
        	return $output;
		}
		
		$result_post = isset($_POST['result']) ? sanitize_text_field($_POST['result']) : '';
        list($result_1, $result_2) = explode(':', $result_post);
		$result_1 = intval($result_1);
		$result_1 = is_numeric($result_1) ? $result_1 : 0;
		$result_2 = intval($result_2);
		$result_2 = is_numeric($result_2) ? $result_2 : 0;
		if(($result_1 < 0) || ($result_2 < 0) || (($result_1 == 0) && ($result_2 == 0))){
			$output = esc_html__('Invalid value entered.','doubles-rotation-tournament' );
			$doroto_output_form = $output; 
        	return $output;
		}
			
        $tournament_id = doroto_getTournamentId();
        $tournament = doroto_prepare_tournament ($tournament_id);

        if ($tournament) {
            $matches = unserialize($tournament->matches_list);
			$statistics = unserialize($tournament->statistics);

   		  if (is_array($matches)) {		

            foreach ($matches as &$match) {
                if ($match['match_number'] == $match_number_post) {
					if($match['result_1'] == 0 && $match['result_2'] == 0) {					
					    $match['result_1'] = intval($result_1);
                    	$match['result_2'] = intval($result_2);
                  		$match['played'] = 1;
						$match['hide'] = isset($_POST['hide_' . $match['match_number']]) ? intval($_POST['hide_' . $match['match_number']]) : 0;
						if ($match['hide'] != 1 && $match['hide'] != 0) {
    						$match['hide'] = 0;
						}
					
						$correct = false; 
						if($match['hide'] != 1) {
							doroto_update_statistics_by_result($tournament_id,$tournament,$match,$correct); 
							$output = esc_html__('The result of match no.','doubles-rotation-tournament' ).' '. esc_html($match['match_number']).' '. esc_html__('was saved with a score','doubles-rotation-tournament' ).' '. esc_html($match['result_1']).':'. esc_html($match['result_2']).'.';
						} else {
							$output = esc_html__('Match no.','doubles-rotation-tournament' ).' '. esc_html($match['match_number']).' '. esc_html__('was skipped.','doubles-rotation-tournament' );
						}
					} else {
						$output = esc_html__('The result of match no.','doubles-rotation-tournament' ).' '. esc_html($match['match_number']).' '. esc_html__('was previously entered with a score','doubles-rotation-tournament' ).' '. esc_html($match['result_1']).':'. esc_html($match['result_2']).'.';
						$doroto_output_form = $output; 
        				return $output;
					}
					break;
                }
            }

            if($match['hide'] != 1) {
			   $wpdb->update("{$wpdb->prefix}doroto_tournaments", ['matches_list' => serialize($matches)], ['id' => $tournament_id]);
		   } else {	 
    			$playersInMatch = [$match['player_1'], $match['player_2'], $match['player_3'], $match['player_4']];
			    $playing = unserialize($tournament->playing);
			   
			    foreach ($playersInMatch as $playerToRemove) {
   				   $key = array_search($playerToRemove, $playing);
   	 			    if ($key !== false) {
       	 		      unset($playing[$key]);
    			    }
			    }
			   
			    $playing = array_values($playing);
			   
				$wpdb->update(
    			"{$wpdb->prefix}doroto_tournaments",
    				array(
        				'matches_list' => serialize($matches),
        				'playing' => serialize($playing)
    					),
   	 				array('id' => $tournament_id)
				);
		  	}
		  } else {
		    	$output = esc_html__('Tournament no.','doubles-rotation-tournament' ).' '.esc_html($tournament_id).' '. esc_html__('has not been scheduled yet.','doubles-rotation-tournament' );
   		    }
		  $doroto_output_form = $output;  
          return $output;	
        }
    }
}

//when a match is finished then update statistics array
function doroto_update_statistics_by_result($tournament_id, $tournament, $match, $correct) {
	global $wpdb;
    $statistics = unserialize($tournament->statistics);

    $playersInMatch = [$match['player_1'], $match['player_2'], $match['player_3'], $match['player_4']];	
	$playing = unserialize($tournament->playing);
	
    foreach ($statistics as &$playerData) {
        if (in_array($playerData['player_id'], $playersInMatch)) {
            // a) Increase games by +1
            if(!$correct) $playerData['games']++; //does not increase in case of correction of the result

            // Index for players in the match
            // the index can only be equal to 0,1,2 or 3 depending on the position in the array
            $index = array_search($playerData['player_id'], $playersInMatch);

            if ($index !== false) {
                // b) and c) - Updating won and lost according to the results
                if ($index < 2) {
                    $playerData['won'] += $match['result_1'];
                    $playerData['lost'] += $match['result_2'];
                } else {
                    $playerData['won'] += $match['result_2'];
                    $playerData['lost'] += $match['result_1'];
                }

                // d) Update ratio
                if($playerData['lost'] !=0) {
					$playerData['ratio'] = $playerData['won'] / $playerData['lost'];
				} else {
					$playerData['ratio'] = $playerData['won'];
				}

                // e) and f) - Update playmates and opponents
                if ($index < 2 && !$correct) {	//it won't happen if it's a match fix				
                    // Update playmates_L
                    if ($playersInMatch[0] != $playerData['player_id']) { //teammate on the left
                        $playmateIndex = array_search($playersInMatch[0], array_column($playerData['playmates_L'], 'player_id'));
                        if ($playmateIndex !== false) {
                            $playerData['playmates_L'][$playmateIndex]['count']++;
							$playerData['playmates'][$playmateIndex]['count']++;
                        }
                    }

                    // Update playmates_P
                    if ($playersInMatch[1] != $playerData['player_id']) {
                        $playmateIndex = array_search($playersInMatch[1], array_column($playerData['playmates_P'], 'player_id'));
                        if ($playmateIndex !== false) {
                            $playerData['playmates_P'][$playmateIndex]['count']++;
							$playerData['playmates'][$playmateIndex]['count']++;
                        }
                    }

                    // Update opponents
                    $opponentIndex = array_search($playersInMatch[2], array_column($playerData['opponents'], 'player_id'));
                    if ($opponentIndex !== false) {
                        $playerData['opponents'][$opponentIndex]['count']++;
                    }

                    $opponentIndex = array_search($playersInMatch[3], array_column($playerData['opponents'], 'player_id'));
                    if ($opponentIndex !== false) {
                        $playerData['opponents'][$opponentIndex]['count']++;
                    }
                } elseif (!$correct) { //it won't happen if it's a match fix
                    // Aktualizace playmates_L
                    if ($playersInMatch[2] != $playerData['player_id']) {
                        $playmateIndex = array_search($playersInMatch[2], array_column($playerData['playmates_L'], 'player_id'));
                        if ($playmateIndex !== false) {
                            $playerData['playmates_L'][$playmateIndex]['count']++;
							$playerData['playmates'][$playmateIndex]['count']++;
                        }
                    }

                    // Update playmates_P
                    if ($playersInMatch[3] != $playerData['player_id']) {
                        $playmateIndex = array_search($playersInMatch[3], array_column($playerData['playmates_P'], 'player_id'));
                        if ($playmateIndex !== false) {
                            $playerData['playmates_P'][$playmateIndex]['count']++;
							$playerData['playmates'][$playmateIndex]['count']++;
                        }
                    }

                    // Update opponents
                    $opponentIndex = array_search($playersInMatch[0], array_column($playerData['opponents'], 'player_id'));
                    if ($opponentIndex !== false) {
                        $playerData['opponents'][$opponentIndex]['count']++;
                    }

                    $opponentIndex = array_search($playersInMatch[1], array_column($playerData['opponents'], 'player_id'));
                    if ($opponentIndex !== false) {
                        $playerData['opponents'][$opponentIndex]['count']++;
                    }
                }
            }
        }
    }

	// Removing players from the $playersInMatch array
	if(!$correct) {
			foreach ($playersInMatch as $playerToRemove) {
   				 $key = array_search($playerToRemove, $playing);
   	 			if ($key !== false) {
       	 		unset($playing[$key]);
    			}
			}	
			// Reindexing an array after removing elements
			$playing = array_values($playing);
	}

	$wpdb->update(
    	"{$wpdb->prefix}doroto_tournaments",
    	array(
        	'statistics' => serialize($statistics),
        	'playing' => serialize($playing)
    	),
   	    array('id' => $tournament_id)
	);
}

//check if there is a need to find a new match
function doroto_allow_to_find_new_match ($tournament,$selected_matches,$currently_played_array) {
	$players_count = count(unserialize($tournament->players));
	$min_not_playing = intval($tournament->min_not_playing);
	$currently_played_count=count($currently_played_array);
	$selected_games_count=count($selected_matches);
	if($min_not_playing == 0) $min_not_playing = $players_count; 
	
	if ($currently_played_count == 0) {
		return true;
	} elseif ($players_count - ($selected_games_count+$currently_played_count)*4 >= $min_not_playing) {
		return true;		
	} else {
		return false;
	}
}

//reduction of the number of matches offered according to the number of courts and according to the number of players
function doroto_matches_to_select_count($tournament,$currently_played_count){ 
	global $wpdb;
    $courts_available = intval($tournament->courts_available);
	$min_not_playing = intval($tournament->min_not_playing);
	$players_count = count(unserialize($tournament->players));
	$max_based_on_players=floor($players_count / 4);
	if ($courts_available <= $max_based_on_players) {
		$maximum_number_games = $courts_available;
	} else {
		$maximum_number_games = $max_based_on_players;
	}
	$matches_to_select = $maximum_number_games - $currently_played_count; 
	
	if($min_not_playing == 0) $min_not_playing = $players_count; 
	if ($currently_played_count == 0) {
		return $matches_to_select;
	} elseif ($players_count - ($currently_played_count*4) >= $min_not_playing) {
		return $matches_to_select;		
	} else {
		return 0;
	}
	
	return $matches_to_select;
}

//close or reopen a tournament
function doroto_toggle_tournament() {
    global $wpdb;
    global $current_user;
    wp_get_current_user();
	$output ='';

    if (!isset($_REQUEST['tournament_id'])) {
        $output = esc_html__("Tournament ID was not provided.", "doubles-rotation-tournament");
		doroto_info_messsages_save ($output);
    	doroto_redirect_modify_url(0,"");
		exit;
    }

    $tournament_id = intval($_REQUEST['tournament_id']);	

	$tournament = doroto_prepare_tournament ($tournament_id);

	if ($tournament == null) {
		$output = esc_html__('The tournament was not found.','doubles-rotation-tournament' );
		doroto_info_messsages_save ($output);
    	doroto_redirect_modify_url(0,"");
		exit;
	}

	$open_registration = intval($tournament->open_registration); 
	if($open_registration) {
		$output = esc_html__("A tournament cannot be closed while player registration is open.", "doubles-rotation-tournament");
	}

	if (doroto_is_admin ($tournament_id) > 0) {
		$final_four = $tournament->final_four;
		$final_result = $tournament->final_result;

        if($open_registration == 0) {
			$new_value = $tournament->close_tournament == '1' ? '0' : '1';
			$close_date = date('Y-m-d H:i:s');
			$final_four = '';
			$final_result = '';
		} else {
			$new_value = $tournament->close_tournament;
			$close_date = '9999-09-09 09:09:09';
		}
        $table_name = $wpdb->prefix . 'doroto_tournaments';
		$wpdb->update(
            $table_name,
            array('close_tournament' => $new_value,
				  'final_four' => $final_four,
				  'final_result' => $final_result,
				  'close_date' => $close_date),
			
            array('id' => $tournament_id)
        );
    }
	
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}

add_action('wp_ajax_doroto_toggle_tournament', 'doroto_toggle_tournament');

//close or reopen a registration
function doroto_toggle_registration() {
    global $wpdb;
	$table_name = $wpdb->prefix . 'doroto_tournaments';
    $tournament_id = intval($_GET['tournament_id']);
	$output = '';
	$tournament = doroto_prepare_tournament ($tournament_id);
	
    if ($tournament) {
        if ($tournament->open_registration == '1') {
			$players_count = count(unserialize($tournament->players));
            if ($players_count < 4) {
                $output .= esc_html__("Tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id).' ' . esc_html__("does not have sufficient occupancy to close registration.", "doubles-rotation-tournament");
            } else {
				if ($players_count <= 8) { 
					$min_not_playing = 0;
				} else {
					$min_not_playing = $tournament->min_not_playing;
				}
                $output .= esc_html__("Registration of tournament players no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id) .' '. esc_html__("was closed.", "doubles-rotation-tournament");
				
				$matches_list = unserialize($tournament->matches_list);
				if(empty($matches_list)) {
					$matches_list = doroto_generate_fake_match($tournament->players);
				}
				
				$statistics = doroto_create_statistics_table ($tournament,unserialize($tournament->players),$tournament->whole_names);

				$wpdb->update(
   						$table_name, 
    					array(
      					 'open_registration' => 0, 
						 'min_not_playing' => $min_not_playing,	
       					 'matches_list' => serialize($matches_list),
						 'statistics' => serialize($statistics)
    						), 
    					array(
        					'id' => $tournament_id
  							  )
							);
            }
        } else {
            $wpdb->update($table_name, array('open_registration' => 1), array('id' => $tournament_id));
            $outpute .= esc_html__("Tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id).' '. esc_html__("was open for registration.", "doubles-rotation-tournament");
        }
    } else {
        $output .= esc_html__("The tournament was not found.", "doubles-rotation-tournament");
		
    }

	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}

//save the first new tournament into wp database as an example
function doroto_create_tournament_record() {
	ob_start();
    global $wpdb;

    $current_user_id = get_current_user_id();

    // Checking if a record with ID 1 exists in table 'doroto_tournaments'
    $tournament = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}doroto_tournaments WHERE id = 1");

    if ($tournament === null) {
        $players[] = $current_user_id;
		$special_group[] = $current_user_id;
		$admin_users[] = $current_user_id;
		
	$users = $wpdb->get_results($wpdb->prepare(
    	"SELECT ID FROM {$wpdb->prefix}users WHERE ID != %d LIMIT 8",
    	$current_user_id
	));

    foreach ($users as $user) {
        if ($user->ID != 1) {
			$players[] = $user->ID;
		}
    }
		$current_date = date("Y-m-d H:i:s");
		if (count($players) >= 4) {
			$matches_list = doroto_generate_fake_match(serialize($players));
			$open_registration = 0;
		} else {
			$matches_list = array();
			$open_registration = 1;
		}

        $wpdb->insert(
            "{$wpdb->prefix}doroto_tournaments",
            [
                'id' => 1,
				'name' => esc_html__("My first Doubles Rotation Tournament", "doubles-rotation-tournament"),
                'admin_users' => serialize($admin_users),
         	    'players' => serialize($players),
				'special_group' => serialize($special_group),
				'max_games' => 5,
				'max_players' => 13,
				'whole_names' => 1,
				'two_special_group' => 0,
				'two_out_group' => 0,
				'open_registration' => $open_registration,
				'courts_available' => 1,
				'matches_list' => serialize($matches_list),
				'create_date' => $current_date,
 				'invitation' => esc_html__("I invite you to participate in testing the principle of operation of the Doubles Rotation Tournament. This is only a virtual tournament without actual play on the tennis court.", "doubles-rotation-tournament")
            ]
        );
		$tournament_id = 1;
		$tournament = $wpdb->get_row($wpdb->prepare(
    		"SELECT * FROM {$wpdb->prefix}doroto_tournaments WHERE id = %d",
    		$tournament_id
		));

        if ($tournament) {
			$statistics = doroto_create_statistics_table ($tournament,$players,intval($tournament->whole_names));
        	$wpdb->update("{$wpdb->prefix}doroto_tournaments", ['statistics' => serialize($statistics)], ['id' => $tournament_id]);
		}
    }
	ob_get_clean();
}

//choose a tournament for displaying on the screen
function doroto_choose_tournament($tournament_id) {
    global $wpdb;
	$output = '';
	
    if (isset($_GET['tournament_id'])) {
        $tournament_id = intval($_GET['tournament_id']);
    } else {
        $output = esc_html__("Tournament ID was not provided.", "doubles-rotation-tournament");
		doroto_info_messsages_save ($output);
    	doroto_redirect_modify_url($tournament_id,"");
		exit;
    }

	$output = esc_html__("The data is being displayed for the tournament no.", "doubles-rotation-tournament").' ' . esc_html($tournament_id).'.';
	doroto_info_messsages_save ($output);
    doroto_redirect_modify_url($tournament_id,"");
	exit;
}

add_action('wp_ajax_doroto_choose_tournament', 'doroto_choose_tournament');
add_action('wp_ajax_nopriv_doroto_choose_tournament', 'doroto_choose_tournament'); // This is for non-logged in users

//list of filtered tournaments
function doroto_prepare_filtered_tournaments($display_rows) {
    global $wpdb;

    $filter_option = get_user_meta(get_current_user_id(), 'doroto_filter_tournaments', true);
    $filter_option = $filter_option ? intval($filter_option) : 0;

    $sql_query = "SELECT * FROM {$wpdb->prefix}doroto_tournaments";

    $current_user_id = get_current_user_id();
    $tournaments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}doroto_tournaments");
    $filtered_tournaments = array();

    switch ($filter_option) {
        case 1:
            // Only open registration
            $sql_query .= " WHERE open_registration = 1";
            break;
        case 2:
            // Still playing
            $sql_query .= " WHERE open_registration = 0 AND close_tournament = 0";
            break;
        case 3:
            // Only closed
            $sql_query .= " WHERE close_tournament = 1";
            break;
        case 4:
            // Where I am logged in
            foreach ($tournaments as $tournament) {
                $players = maybe_unserialize($tournament->players);
                if (is_array($players) && in_array($current_user_id, $players)) {
                    $filtered_tournaments[] = $tournament;
                }
            }
            break;
        case 5:
            // Where I am not logged in
            foreach ($tournaments as $tournament) {
                $players = maybe_unserialize($tournament->players);
                if (!is_array($players) || (is_array($players) && !in_array($current_user_id, $players))) {
                    $filtered_tournaments[] = $tournament;
                }
            }
            break;
        case 6:
            // Where I am admin
            foreach ($tournaments as $tournament) {
                $admin_users = maybe_unserialize($tournament->admin_users);
                if (is_array($admin_users) && in_array($current_user_id, $admin_users)) {
                    $filtered_tournaments[] = $tournament;
                }
            }
            break;
        case 7:
            // Where I am not admin
            foreach ($tournaments as $tournament) {
                $admin_users = maybe_unserialize($tournament->admin_users);
                if (!is_array($admin_users) || !in_array($current_user_id, $admin_users)) {
                    $filtered_tournaments[] = $tournament;
                }
            }
            break;
        default:
            // No filter
            break;
    }

    // If filters 4-7 are not selected, add sorting
    if ($filter_option < 4 || $filter_option > 7) {
        $sql_query .= " ORDER BY id DESC";
    }

    // Query execution if no filter 4-7 has been selected
    if ($filter_option < 4 || $filter_option > 7) {
        $results = $wpdb->get_results($sql_query);
		
        // Crop to maximum number of records
        $results = array_slice($results, 0, $display_rows);
        return $results;
    }

    // If filter 4-7 is selected, return filtered tournaments and add sorting
    usort($filtered_tournaments, function ($a, $b) {
        return $b->id - $a->id;
    });

    // Crop to maximum number of records
    $filtered_tournaments = array_slice($filtered_tournaments, 0, $display_rows);

    return $filtered_tournaments;
}

//function that change what is presented during a tournament
function doroto_change_presentation(){
	global $wpdb;
	$current_user_id = get_current_user_id();
	$doroto_presentation = maybe_unserialize(get_user_meta($current_user_id, 'doroto_presentation', true));	
	$shortcodes = array('doroto_tournament_log_link','doroto_games_to_play','doroto_display_players','doroto_display_games','doroto_display_player_statistics','doroto_table');
	
	if(!$doroto_presentation || !is_array($doroto_presentation)){
		$doroto_presentation = array(
        	'allow_to_run' => 0,
        	'slide' => $shortcodes[0],
    	);
		update_user_meta($current_user_id, 'doroto_presentation', serialize($doroto_presentation));
	} 
	$allow_to_run = intval($doroto_presentation['allow_to_run']);
	if($allow_to_run != 1) return null;
	
	$doroto_settings = get_option('doroto_settings');
	if(!isset($doroto_settings)) return null;
	$shortcodes_validated=[];
	foreach($shortcodes as $code) {
		if($doroto_settings[$code] == 1) $shortcodes_validated[] = $code;
	}
	if(empty($shortcodes_validated)) return null;
	
	$current_slide = sanitize_text_field($doroto_presentation['slide']);
	
	$current_index = array_search($current_slide, $shortcodes_validated);
	if ($current_index === false || $current_index === count($shortcodes_validated) - 1) {
		$doroto_presentation['slide'] = $shortcodes_validated[0];
	} else {
		$doroto_presentation['slide'] = $shortcodes_validated[$current_index + 1];	
	}
	
	update_user_meta($current_user_id, 'doroto_presentation', serialize($doroto_presentation));
}