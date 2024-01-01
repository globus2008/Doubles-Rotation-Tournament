<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * content of the shortcode admin page 
 * @since 1.0.0
 */
function doroto_menu_page_content() {
    $output = "<h1>" . esc_html__( 'Overview of the available shortcode functions of the Doubles Rotation Tournament plugin.', 'doubles-rotation-tournament' ) . "</h1>";	
	$output .= "<h3>" . esc_html__( 'Contribute to the sustainable development of this plugin by using', 'doubles-rotation-tournament' ) . " <a href='" . esc_url( 'https://www.paypal.com/donate/?business=S295WXEHMKLF6&no_recurring=0&item_name=Contribution+to+the+development+of+Wordpress+plugin+Doubles+rotation+tournament.&currency_code=EUR' ) . "' target='_blank'>Paypal</a> " . esc_html__( 'gate.', 'doubles-rotation-tournament' ) . "</h3>";
    $output .= "<p>" . esc_html__( 'This plugin is designed to support a variant of a doubles tennis tournament, where there is a frequent change of teammates and the game thus becomes more interesting.', 'doubles-rotation-tournament' ) . "</p>";

	doroto_main_page_info ($output);
	doroto_help_page_info ($output);
	$output .= "<div>";
	$output .= '<table class="widefat fixed striped">';
    $output .= "<tr>";
    $output .= '<th style="width: 50px;">' . esc_html__( 'Item', 'doubles-rotation-tournament' ) . '</th>';
    $output .= '<th style="width: 400px;">' . esc_html__( 'Shortcode for embedding on the page', 'doubles-rotation-tournament' ) . '</th>';
    $output .= "<th>" . esc_html__( 'Description of function', 'doubles-rotation-tournament' ) . "</th>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>1.1</td>";
    $output .= "<td><ul>[doroto_games_to_play]</ul><ul>[doroto_games_to_play tournament_id='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'Shows drawn games. Their number depends on the parameters of the tournament, such as the number of courts, the minimum number of non-playing players and, according to the permission to compare the number of matches. The tournament_id parameter is optional.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>1.2</td>";
    $output .= "<td><ul>[doroto_display_players]</ul><ul>[doroto_display_players tournament_id='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'Displays the list of tournament participants and their running ranking. The format of displayed names depends on the setting of the display full names parameter. Add the tournament number after the id parameter if necessary.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>1.3</td>";
    $output .= "<td><ul>[doroto_refresh_page]</ul><ul>[doroto_refresh_page seconds_to_refresh='120']</ul></td>";
    $output .= "<td>" . esc_html__( 'It will refresh the page after a certain number of seconds.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>1.4</td>";
    $output .= "<td>[doroto_info_messsages]</td>";
    $output .= "<td>" . esc_html__( 'It will display red marked reports from submitted forms.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>2.1</td>";
    $output .= "<td><ul>[doroto_display_games]</ul><ul>[doroto_display_games tournament_id='1' only_played='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'Displays a list of drawn games for the selected tournament. The only_played parameter determines whether only played games are listed.', 'doubles-rotation-tournament' ) ."</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>2.2</td>";
    $output .= "<td><ul>[doroto_player_filter]</ul><ul>[doroto_player_filter tournament_id='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'Displays a list of drawn games only for the selected player.', 'doubles-rotation-tournament' ) ."</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>2.3</td>";
    $output .= "<td><ul>[doroto_change_game]</ul><ul>[doroto_change_game only_web_admin='0']</ul></td>";
  $output .= "<td>" . esc_html__( 'Match results can be edited here. Especially useful when an error occurred when entering the result of a played match.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>2.4</td>";
    $output .= "<td>[doroto_display_player_statistics]</td>";
  $output .= "<td>" . esc_html__( 'Displays all game statistics for the selected player.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";	
    $output .= "<tr>";
    $output .= "<td>3.1</td>";
   $output .= "<td><ul>[doroto_add_player]</ul><ul>[doroto_add_player only_web_admin='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'Allows the organizer to insert players from the user database. The only_web_admin parameter will limit the range of users of this function to only web administrators with the function admin, editor and editor-in-chief.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>3.2</td>";
    $output .= "<td><ul>[doroto_remove_player]</ul><ul>[doroto_remove_player only_web_admin='0']</ul></td>";
    $output .= "<td>" . esc_html__( 'Allows the tournament organizer to remove players from the tournament. When whole_names=1, the whole names are displayed, if whole_names=0, the first 4 characters of the first name and the last 4 characters of the last name are displayed. The only_web_admin parameter will limit the range of users of this function to only web administrators with the function admin, editor and editor-in-chief.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td>3.3</td>";
    $output .= "<td><ul>[doroto_add_special_group]</ul><ul>[doroto_add_special_group only_web_admin='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'If the player is included in a special group, then his name will be colored blue in the table. These other players are provided with information about a different implementation of the game.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td>3.4</td>";
    $output .= "<td><ul>[doroto_remove_special_group]</ul><ul>[doroto_remove_special_group only_web_admin='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'Ability to cancel assignment to a special group for a player.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";	
    $output .= "<tr>";
	$output .= "<td>3.5</td>";
    $output .= "<td><ul>[doroto_temporary_disable_player]</ul><ul>[doroto_temporary_disable_player only_web_admin='0' tournament_id='2']</ul></td>";
    $output .= "<td>" . esc_html__( 'It will allow the tournament organizer or the participant himself to temporarily suspend his participation in further matches. Then this player is not included in the draw for further games.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>3.6</td>";
    $output .= "<td><ul>[doroto_temporary_enable_player]</ul><ul>[doroto_temporary_enable_player only_web_admin='0' tournament_id='2']</ul></td>";
    $output .= "<td>" . esc_html__( 'It will allow the tournament organizer or participant to resume their participation in the tournament.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>3.7</td>";
    $output .= "<td><ul>[doroto_enter_payment_manually]</ul><ul>[doroto_enter_payment_manually only_web_admin='0' tournament_id='2']</ul></td>";
    $output .= "<td>" . esc_html__( 'Here you can manually enter information about the payment made by a certain player.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>3.8</td>";
    $output .= "<td><ul>[doroto_remove_payment_manually]</ul><ul>[doroto_remove_payment_manually only_web_admin='0' tournament_id='2']</ul></td>";
    $output .= "<td>" . esc_html__( 'Here you can manually delete the information about the completed payment of a certain player.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
    $output .= "<td>4.1</td>";
    $output .= "<td>[doroto_tournament_parameters]</td>";
    $output .= "<td>" . esc_html__( 'A function that displays the optional parameters for the tournament and allows them to be changed. The whole_names parameter determines how player names will be displayed, and the only_web_admin parameter limits the range of users who are allowed to edit the tournament.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>4.2</td>";
    $output .= "<td>[doroto_tournament_log_link tournament_id='85']</td>";
    $output .= "<td>" . esc_html__( 'Displays the text for logging into the tournament by id. It works with the invitation text parameter. This link disappears when registration is closed.' , 'doubles-rotation-tournament') . "</td>";
    $output .= "</tr>";
    $output .= "<tr>";
    $output .= "<td>4.3</td>";
    $output .= "<td><ul>[doroto_add_admin]</ul><ul>[doroto_add_admin whole_names='0' only_web_admin='1']</ul></td>";
    $output .= "<td>" . esc_html__( 'It will allow adding match organizer rights to other tournament participants.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
    $output .= "<td>4.4</td>";
    $output .= "<td><ul>[doroto_table]</ul><ul>[doroto_table display_rows='20']</ul></td>";
    $output .= "<td>" . esc_html__( 'Displays a list of available tournaments. If the display_rows parameter is not specified, only the last 20 tournaments will be displayed.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td>4.5</td>";
    $output .= "<td>[doroto_filter_tournaments]</td>";
    $output .= "<td>" . esc_html__( 'Filtering displayed tournaments in the table.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td>4.6</td>";
    $output .= "<td>[add_tournament]</td>";
    $output .= "<td>" . esc_html__( 'Shows a button to create a new tournament.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td>5.1</td>";
    $output .= "<td>[doroto_help_main_page]</td>";
    $output .= "<td>" . esc_html__( 'Shows help for using the main page.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";
	$output .= "<tr>";
	$output .= "<td>6.1</td>";
    $output .= "<td>[doroto_allow_presentation]</td>";
    $output .= "<td>" . esc_html__( 'Displays a link to start the presentation of the selected tournament. In the admin menu, you can set which tournament parameters will be shown on the screen in the selected time sequence. Especially suitable for displaying the running results of the tournament on a larger screen.', 'doubles-rotation-tournament' ) . "</td>";
    $output .= "</tr>";

    $output .= "</table>";
	$output .= "</div>";

    return $output;
}


/**
 * shortcode for page with shortcodes
 * @since 1.0.0
 */
function doroto_shortcodes_menu_page_callback() { 
	$allowed_html = doroto_allowed_html();
    echo '<div class="wrap">';
	echo wp_kses(doroto_menu_page_content(), 'post');
    echo '</div>';
}


/**
 * callback for admin homepage
 * @since 1.0.0
 */
function doroto_home_menu_page_callback() { 
	$allowed_html = doroto_allowed_html();
    echo '<div class="wrap">';
	echo wp_kses(doroto_home_page(), $allowed_html);
    echo '</div>';
}


/**
 * create admin menu
 * @since 1.0.0
 */
function doroto_create_menu() {
    $icon_url = 'dashicons-awards';
	$menu_slug = 'doubles-rotation-tournament';
    add_menu_page( esc_html__( 'Doubles Rotation Tournament', 'doubles-rotation-tournament' ), esc_html__( 'Doubles Rotation Tournament', 'doubles-rotation-tournament' ), 'manage_options', $menu_slug, null, $icon_url );
	//add_submenu_page( $menu_slug, esc_html__( 'Rules', 'doubles-rotation-tournament' ), esc_html__( 'Home', 'doubles-rotation-tournament' ), 'manage_options', $menu_slug, 'doroto_home_menu_page_callback' );	
	add_submenu_page( $menu_slug, esc_html__( 'Settings', 'doubles-rotation-tournament' ), esc_html__( 'Settings', 'doubles-rotation-tournament' ), 'manage_options', $menu_slug, 'doroto_settings_menu_page_callback' );
    add_submenu_page( $menu_slug, esc_html__ ('Help', 'doubles-rotation-tournament' ), esc_html__ ('Shortcodes', 'doubles-rotation-tournament' ), 'manage_options', $menu_slug.'-shortcodes', 'doroto_shortcodes_menu_page_callback' );
}
add_action( 'admin_menu', 'doroto_create_menu' );


/**
 * preparation for admin environmental settings page
 * @since 1.0.0
 */
function doroto_register_settings() {
    add_settings_section(
        'doroto_settings_section',		
         sanitize_text_field( __("Settings","doubles-rotation-tournament") ),
        'doroto_settings_section_callback',
        'doubles-rotation-tournament-settings' 
    );

    add_settings_field(
        'doroto_settings_options',
         sanitize_text_field( __("Environment settings","doubles-rotation-tournament") ),
        'doroto_settings_options_callback',
        'doubles-rotation-tournament-settings',   // name of an existing page
        'doroto_settings_section'
    );
	
    add_settings_field(
        'doroto_presentation_options',
         sanitize_text_field(__("Presentation settings","doubles-rotation-tournament") ),
        'doroto_presentation_options_callback',
        'doubles-rotation-tournament-settings',   
        'doroto_settings_section'
    );	
	
    add_settings_field(
        'doroto_data_options',
         sanitize_text_field(__("Uninstall and activate","doubles-rotation-tournament") ),
        'doroto_data_options_callback',
        'doubles-rotation-tournament-settings',  
        'doroto_settings_section'
    );
    register_setting('doroto_settings', 'doroto_settings', 'doroto_sanitize_settings');
}


/**
 * sanitize attribute 'display_rows' for tournament´s functions
 * @since 1.0.0
 */
function doroto_sanitize_settings($input) {
    $input['display_rows'] = isset($input['display_rows']) ? intval($input['display_rows']) : 0;
    return $input;
}


/**
 * label for admin page
 * @since 1.0.0
 */
function doroto_settings_section_callback() {
    echo '<p>'.esc_html__("Here you can set restrictions for tournament administration from the website administrator's point of view.","doubles-rotation-tournament").'</p>';
}


/**
 * content of admin enviromental setting page
 * @since 1.0.0
 */
function doroto_settings_options_callback() {

	$doroto_settings = get_option('doroto_settings');

    $only_admin_players = isset($doroto_settings['only_admin_players']) ? intval($doroto_settings['only_admin_players']) : '';
	$only_admin_posts = isset($doroto_settings['only_admin_posts']) ? intval($doroto_settings['only_admin_posts']) : '';
    $display_rows = isset($doroto_settings['display_rows']) ? intval($doroto_settings['display_rows']) : '';
 	$refresh_seconds = isset($doroto_settings['refresh_seconds']) ? intval($doroto_settings['refresh_seconds']) : '';
	$only_admin_creates = isset($doroto_settings['only_admin_creates']) ? intval($doroto_settings['only_admin_creates']) : '';
	$player_name_length = isset($doroto_settings['player_name_length']) ? intval($doroto_settings['player_name_length']) : '';
	$youtube_link = isset($doroto_settings['youtube_link']) ? sanitize_text_field( wp_unslash($doroto_settings['youtube_link'])) : '';
	
	echo '<table class="wp-list-table widefat fixed striped table-view-list forms doroto-admin-enlarged-table">';

    // settings 'only_admin_players'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_only_admin_players">' . esc_html__('Can only the site administrator work with the player database?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[only_admin_players]">';
   	echo '<option value="1" ' . selected(1, esc_attr($only_admin_players), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($only_admin_players), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("By default, this is enabled by the site administrator. Otherwise, anyone can be allowed to view usernames and add them to their tournament.", "doubles-rotation-tournament") .' '. esc_html__("This setting has lower priority than using an attribute in the shortcode.", "doubles-rotation-tournament").'</p>';
    echo '</td>';
    echo '</tr>';

    // settings 'display_rows'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_display_rows">' . esc_html__('Maximum number of displayed tournaments in the table. (0 = no limit)', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select id="doroto_settings_display_rows" name="doroto_settings[display_rows]">';
	for ($i = 0; $i <= 100; $i++) {
		echo '<option value="' . esc_attr($i) . '" ' . selected(esc_attr($i), esc_attr($display_rows), false) . '>' . esc_html($i) . '</option>';
	}
	echo '</select>';
    echo '<p class="description">' . esc_html__('Enter a value between 0 and 100 for display rows.', 'doubles-rotation-tournament') . '</p>';
    echo '</td>';
    echo '</tr>';
	
	// settings 'only_admin_posts'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_only_admin_posts">' . esc_html__('Allow the tournament organizer to create a post to promote and administer the tournament?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[only_admin_posts]">';
   	echo '<option value="0" ' . selected(0, esc_attr($only_admin_posts), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="1" ' . selected(1, esc_attr($only_admin_posts), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("By default, this is enabled by the site administrator. Otherwise, anyone can create a post.", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';
	
    // settings 'refresh_seconds'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_refresh_seconds">' . esc_html__('Choose the time after which the page will be refreshed. Good especially when you are not the only one entering new data.. (0 = no refresh)', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select id="doroto_settings_refresh_seconds" name="doroto_settings[refresh_seconds]">';
	for ($i = 0; $i <= 1000; $i+=10) {
   	 	echo '<option value="' . esc_attr($i) . '" ' . selected(esc_attr($i), esc_attr($refresh_seconds), false) . '>' . esc_html($i) . '</option>';		
	}
	echo '</select>';
    echo '<p class="description">' . esc_html__('Enter a value between 0 and 1000 for the number of seconds.', 'doubles-rotation-tournament') .' '. esc_html__("This setting has lower priority than using an attribute in the shortcode.", "doubles-rotation-tournament"). '</p>';
    echo '</td>';
    echo '</tr>';
	
	// settings 'only_admin_creates'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_only_admin_creates">' . esc_html__('Can only a website administrator create a new tournament?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[only_admin_creates]">';
   	echo '<option value="1" ' . selected(1, esc_attr($only_admin_creates), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($only_admin_creates), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("By default, this is enabled by everybody who is logged in. Otherwise, only an administrator, editor or author can create a tournament.", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';  
	
	    // settings 'display name maximal length'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_player_name_length">' . esc_html__('Enter the maximum number of characters allowed in the players display name.') . '</label></th>';
    echo '<td>';
    echo '<select id="doroto_settings_player_name_length" name="doroto_settings[player_name_length]">';
	for ($i = 5; $i <= 30; $i+=1) {
   	 	echo '<option value="' . esc_attr($i) . '" ' . selected($i, esc_attr($player_name_length), false) . '>' . esc_html($i) . '</option>';		
	}
	echo '</select>';
    echo '<p class="description">' . esc_html__('Enter a value between 5 and 30 for the number of characters.', 'doubles-rotation-tournament') . '</p>';
    echo '</td>';
    echo '</tr>';
	
	// settings 'youtube_link'
	echo '<tr class="alternate">';
	echo '<th scope="row"><label for="doroto_settings_youtube_link">' . esc_html__('YouTube Video Link with a help.', 'doubles-rotation-tournament') . '</label></th>';
	echo '<td>';
	echo '<input type="text" name="doroto_settings[youtube_link]" value="' . esc_attr($youtube_link) . '" />';
	echo '<p class="description">' . esc_html__('Enter the YouTube video link.', 'doubles-rotation-tournament') . ' '.  esc_html__('If you leave the field blank, the help video will not be displayed.', 'doubles-rotation-tournament') . '</p>';
	echo '</td>';
	echo '</tr>'; 
    echo '</table>';
}


/**
 * content of admin presentation setting page
 * @since 1.0.0
 */
function doroto_presentation_options_callback() {
	$doroto_settings = get_option('doroto_settings');

	$show_next_seconds = isset($doroto_settings['show_next_seconds']) ? intval($doroto_settings['show_next_seconds']) : '';	
    $doroto_tournament_log_link = isset($doroto_settings['doroto_tournament_log_link']) ? intval($doroto_settings['doroto_tournament_log_link']) : '';
	$doroto_games_to_play = isset($doroto_settings['doroto_games_to_play']) ? intval($doroto_settings['doroto_games_to_play']) : '';	
    $doroto_display_players = isset($doroto_settings['doroto_display_players']) ? intval($doroto_settings['doroto_display_players']) : '';
 	$doroto_display_games = isset($doroto_settings['doroto_display_games']) ? intval($doroto_settings['doroto_display_games']) : '';
	$doroto_display_player_statistics = isset($doroto_settings['doroto_display_player_statistics']) ? intval($doroto_settings['doroto_display_player_statistics']) : '';
	$doroto_table = isset($doroto_settings['doroto_table']) ? intval($doroto_settings['doroto_table']) : '';
	
	echo '<table class="wp-list-table widefat fixed striped table-view-list forms doroto-admin-enlarged-table">';

    // settings 'doroto_tournament_log_link'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_doroto_tournament_log_link"> [doroto_tournament_log_link] </label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[doroto_tournament_log_link]">';
   	echo '<option value="1" ' . selected(1, esc_attr($doroto_tournament_log_link), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($doroto_tournament_log_link), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("Display the output from this shortcode while the tournament presentation is on?", "doubles-rotation-tournament") .'</p>';
    echo '</td>';
    echo '</tr>';

	// settings 'doroto_games_to_play'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_doroto_games_to_play"> [doroto_games_to_play] </label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[doroto_games_to_play]">';
   	echo '<option value="1" ' . selected(1, esc_attr($doroto_games_to_play), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($doroto_games_to_play), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("Display the output from this shortcode while the tournament presentation is on?", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';
	
    // settings 'doroto_display_players'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_doroto_display_players"> [doroto_display_players] </label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[doroto_display_players]">';
   	echo '<option value="1" ' . selected(1, esc_attr($doroto_display_players), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($doroto_display_players), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("Display the output from this shortcode while the tournament presentation is on?", "doubles-rotation-tournament") .'</p>';
    echo '</td>';
    echo '</tr>';

	// settings 'doroto_display_games'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_doroto_display_games"> [doroto_display_games] </label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[doroto_display_games]">';
   	echo '<option value="1" ' . selected(1, esc_attr($doroto_display_games), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($doroto_display_games), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("Display the output from this shortcode while the tournament presentation is on?", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';	
	
    // settings 'doroto_display_player_statistics'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_doroto_display_player_statistics"> [doroto_display_player_statistics] </label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[doroto_display_player_statistics]">';
   	echo '<option value="1" ' . selected(1, esc_attr($doroto_display_player_statistics), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($doroto_display_player_statistics), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("Display the output from this shortcode while the tournament presentation is on?", "doubles-rotation-tournament") .'</p>';
    echo '</td>';
    echo '</tr>';

	// settings 'doroto_table'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_doroto_table"> [doroto_table] </label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[doroto_table]">';
   	echo '<option value="1" ' . selected(1, esc_attr($doroto_table), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($doroto_table), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("Display the output from this shortcode while the tournament presentation is on?", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';	

	// settings 'show_next_seconds'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_show_next_seconds">' . esc_html__('Enter the number of seconds as the time between transitions to show the next shortcode.', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select id="doroto_settings_show_next_seconds" name="doroto_settings[show_next_seconds]">';
	for ($i = 1; $i <= 120; $i+=1) {
   	 	echo '<option value="' . esc_attr($i) . '" ' . selected($i, esc_attr($show_next_seconds), false) . '>' . esc_html($i) . '</option>';		
	}
	echo '</select>';
    echo '<p class="description">' . esc_html__('Enter a value between 1 and 120 for the number of seconds.', 'doubles-rotation-tournament') . '</p>';
    echo '</td>';
    echo '</tr>';	
	
    echo '</table>';
}


/**
 * content of admin data setting page
 * @since 1.0.0
 */
function doroto_data_options_callback() {
    $doroto_settings = get_option('doroto_settings');

    $delete_database = isset($doroto_settings['delete_database']) ? intval($doroto_settings['delete_database']) : '';
	$delete_pages = isset($doroto_settings['delete_pages']) ? intval($doroto_settings['delete_pages']) : '';
    $delete_settings = isset($doroto_settings['delete_settings']) ? intval($doroto_settings['delete_settings']) : '';
 	$update_activation = isset($doroto_settings['update_activation']) ? intval($doroto_settings['update_activation']) : '';
	
	echo '<table class="wp-list-table widefat fixed striped table-view-list forms doroto-admin-enlarged-table">';
    // settings 'delete_database'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_delete_database">' . esc_html__('Uninstall the tournament database at the same time as the plugin?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[delete_database]">';
   	echo '<option value="1" ' . selected(1, esc_attr($delete_database), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($delete_database), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("If you ever want to return to the plugin, it will be a shame to lose your data.", "doubles-rotation-tournament").'</p>';
    echo '</td>';
    echo '</tr>';
 	
	// settings 'delete_pages'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_delete_pages">' . esc_html__('Uninstall tournament pages at the same time as the plugin?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[delete_pages]">';
   	echo '<option value="1" ' . selected(1, esc_attr($delete_pages), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($delete_pages), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("If you have edited page settings, here you have the option to keep these changes even in the event of an update or uninstallation.", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';
	
	// settings 'delete_settings'
    echo '<tr class="alternate">';
    echo '<th scope="row"><label for="doroto_settings_delete_settings">' . esc_html__('Uninstall saved settings data at the same time as the plugin?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[delete_settings]">';
   	echo '<option value="1" ' . selected(1, esc_attr($delete_settings), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($delete_settings), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
	echo '</select>';
    echo '<p class="description">' . esc_html__("If you ever want to return to the plugin, it will be a shame to lose your data.", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';  
	
	// settings 'update_activation'
    echo '<tr class="iedit">';
    echo '<th scope="row"><label for="doroto_settings_update_activation">' . esc_html__('Update pages every time you activate the plugin?', 'doubles-rotation-tournament') . '</label></th>';
    echo '<td>';
    echo '<select name="doroto_settings[update_activation]">';
   	echo '<option value="1" ' . selected(1, esc_attr($update_activation), false) . '>'. esc_html__("Yes", "doubles-rotation-tournament") .'</option>';
	echo '<option value="0" ' . selected(0, esc_attr($update_activation), false) . '>'. esc_html__("No", "doubles-rotation-tournament") .'</option>';
    echo '</select>';
    echo '<p class="description">' . esc_html__("This option keeps up with new plugin updates.", "doubles-rotation-tournament") . '</p>';
    echo '</td>';
    echo '</tr>';	
  
    echo '</table>';
}


/**
 * menu page callback
 * @since 1.0.0
 */
function doroto_settings_menu_page_callback() {
    ?>
    <div class="wrap">
        <form action="options.php" method="post">
			<?php
            if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
                echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'doubles-rotation-tournament') . '</p></div>';
            }
            ?>
            <?php
            settings_fields('doroto_settings');
            do_settings_sections('doubles-rotation-tournament-settings');
            submit_button(esc_html__('Save Settings', 'doubles-rotation-tournament'));
            ?>

        </form>
    </div>
    <?php
}

add_action('admin_init', 'doroto_register_settings');


/**
 * check if 'doroto_settings' exists in wp 'option' table
 * @since 1.0.0
 */
function doroto_settings_check_existence() {
    $doroto_settings = get_option('doroto_settings');

    // default values
    $default_values = array(
        'only_admin_players' => 1,
        'only_admin_posts' => 1,
        'display_rows' => 20,
        'refresh_seconds' => 120,
		'delete_database' => 0,
		'delete_pages' => 1,
		'delete_settings' => 1,
		'update_activation' => 1,
		'only_admin_creates' => 0,
		'show_next_seconds' => 10,
        'doroto_tournament_log_link' => 0,
        'doroto_games_to_play' => 1,
		'doroto_display_players' => 1,
		'doroto_display_games' => 1,
		'doroto_display_player_statistics' => 1,
		'doroto_table' => 0,
		'player_name_length' => 20,
		'youtube_link' => "https://www.youtube.com/watch?v=CdVC63XWr9E", //link with video help
    );

    $doroto_settings = wp_parse_args($doroto_settings, $default_values);
    update_option('doroto_settings', $doroto_settings);
}

register_activation_hook(__FILE__, 'doroto_settings_check_existence');
add_action('admin_init', 'doroto_settings_check_existence');


/**
 * check if 'doroto_presentation' exists in wp 'user_meta' table
 * @since 1.0.0
 */
function doroto_presentation_check_existence() {
	global $wpdb;
	$current_user_id = intval( get_current_user_id() );
	$doroto_presentation = maybe_unserialize(get_user_meta($current_user_id, 'doroto_presentation', true));	
	
    // default values
    $default_values = array(
        'allow_to_run' => 0,
    );

    $doroto_presentation = wp_parse_args($doroto_presentation, $default_values);
	update_user_meta($current_user_id, 'doroto_presentation', serialize($doroto_presentation));
}