<?php
function wpmudev_chat_buddypress_init() {
	if ( class_exists( 'BP_Group_Extension' ) ) {
		if (version_compare(bp_get_version(), "1.8") >= 0) {
			include_once('buddypress/wpmudec_chat_buddypress_group_1.8.php');
		} else {
			include_once('buddypress/wpmudec_chat_buddypress_group_1.7.2.php');
		}

		if ( class_exists( 'WPMUDEV_Chat_BuddyPress' ) ) {
			bp_register_group_extension( 'WPMUDEV_Chat_BuddyPress' );
		}
	}
}
add_action( 'bp_include', 'wpmudev_chat_buddypress_init' );


function wpmudev_chat_buddypress_friends_list() {
	global $bp, $members_template, $wpmudev_chat;

	if (isset($members_template->member->is_friend)) {

		$content = '';

		$content .= '<div id="wpmudev-chat-now-button-'. $members_template->member->ID .'" class="wpmudev-chat-now-button">';
		$friends_status = wpmudev_chat_get_friends_status($bp->loggedin_user->id, $members_template->member->ID);

		if (!empty($friends_status[0])) {
			$friends_status = $friends_status[0];
		} else {
			$friends_status = '';
		}

		$friend_data = wpmudev_chat_get_chat_status_data($members_template->member->ID, $friends_status);

		$friend_status_display = $friend_data['icon'] . $friend_data['label'];
		if (!empty($friend_data['href'])) {
			$content .= '<a class="button wpmudev-chat-button '. $friend_data['href_class'] .'" title="'. $friend_data['href_title'] .'" href="#" rel="'. $friend_data['href'] .'">'. $friend_status_display .'</a>';
		} else {
			$content .= '<a onclick="return false;" disabled="disabled" class="button wpmudev-chat-button '. $friend_data['href_class'] .'" title="'. $friend_data['href_title'] .'" href="#">'. $friend_status_display .'</a>';
		}
		$content .= '</div>';

		echo $content;
	}
}

add_action( 'bp_directory_members_actions', 'wpmudev_chat_buddypress_friends_list' );
add_action( 'bp_group_members_list_item_action', 'wpmudev_chat_buddypress_friends_list' );

function wpmudev_chat_buddypress_profile_settings() {
	global $wpmudev_chat;

	//global $current_user;
	//echo "current_user<pre>"; print_r($current_user); echo "</pre>";

	$wpmudev_chat->chat_edit_user_profile();

	return;
	?>
	<fieldset>
		<legend><?php _e('Chat Options', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Using the following options you can define defaults for users within your site. These settings are just defaults in the case where the user has not already saved their own values via their profile.', $wpmudev_chat->translation_domain); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="wpmudev_chat_status"><?php _e('User Chat Status', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select name="chat_user_meta[chat_user_status]" id="wpmudev_chat_status">
				<?php
					foreach($wpmudev_chat->_chat_options['user-statuses'] as $status_key => $status_label) {
						if ($status_key == $wpmudev_chat->_chat_options_defaults['user_meta']['chat_user_status']) {
							$selected = ' selected="selected" ';
						} else {
							$selected = '';
						}

						?><option value="<?php echo $status_key;?>" <?php echo $selected; ?>><?php echo $status_label; ?></option><?php
					}
				?>
	            </select>
			</td>
			<td class="chat-help-column"><?php //echo wpmudev_chat_get_help_item('', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}
//add_action( 'bp_core_general_settings_before_submit', 'wpmudev_chat_buddypress_profile_settings' );


function wpmudev_chat_buddypress_settings_setup_nav() {
	global $wpmudev_chat;

	$slug_parent 	= 'settings';
	$slug_page 		= 'chat';

	// Determine user to use
	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	} elseif ( bp_loggedin_user_domain() ) {
		$user_domain = bp_loggedin_user_domain();
	} else {
		return;
	}
	//echo "user_domain=[". $user_domain ."]<br />";
	$settings_link = trailingslashit( $user_domain . $slug_parent );
	//echo "settings_link=[". $settings_link ."]<br />";
	$sub_nav = array(
		'name'            => __( 'Chat', $wpmudev_chat->translation_domain ),
		'slug'            => $slug_page,
		'parent_url'      => $settings_link,
		'parent_slug'     => $slug_parent,
		'screen_function' => 'wpmudev_chat_settings_screen_chat_proc',
		'position'        => 30,
		'user_has_access' => bp_core_can_edit_settings()
	);
	bp_core_new_subnav_item($sub_nav);
}
add_action('bp_settings_setup_nav', 'wpmudev_chat_buddypress_settings_setup_nav');

function wpmudev_chat_settings_screen_chat_proc() {
	global $wpmudev_chat, $current_user;

	if (isset($_POST['wpmudev_chat_user_settings'])) {
		//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
		//die();

		if (isset($_POST['wpmudev_chat_user_settings']['chat_user_status'])) {
			$chat_user_status = esc_attr($_POST['wpmudev_chat_user_settings']['chat_user_status']);
			if (isset($wpmudev_chat->_chat_options['user-statuses'][$chat_user_status])) {
				wpmudev_chat_update_user_status($current_user->ID, $chat_user_status);
			}
			unset($_POST['wpmudev_chat_user_settings']['chat_user_status']);
		}


		$user_meta = get_user_meta( $current_user->ID, 'wpmudev-chat-user', true);
		if (!$user_meta) $user_meta = array();
		//echo "after get_user_meta: user_meta<pre>"; print_r($user_meta); echo "</pre>";

		//$user_meta = wp_parse_args( $user_meta, $wpmudev_chat->_chat_options_defaults['user_meta'] );
		$user_meta = wp_parse_args( $_POST['wpmudev_chat_user_settings'], $user_meta );
		//echo "before update_user_meta: user_meta<pre>"; print_r($user_meta); echo "</pre>";

		update_user_meta( $current_user->ID, 'wpmudev-chat-user', $user_meta );
		$wpmudev_chat->user_meta = $user_meta;

		// Show the standard BP green success message
		bp_core_add_message( __( 'Changes saved.', $wpmudev_chat->translation_domain ) );
	}

	add_action( 'bp_template_title', 'wpmudev_chat_settings_show_screen_title' );
	add_action( 'bp_template_content', 'wpmudev_chat_settings_show_screen_content' );
	bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

	return;
}

function wpmudev_chat_settings_show_screen_title() {
	global $wpmudev_chat;

	_e('Chat Settings', $wpmudev_chat->translation_domain);
	return;
}

function wpmudev_chat_settings_show_screen_content() {
	global $wpmudev_chat, $current_user;

	$user_meta = get_user_meta( $current_user->ID, 'wpmudev-chat-user', true );
	$user_meta = wp_parse_args( $user_meta, $wpmudev_chat->_chat_options_defaults['user_meta'] );

	$chat_user_status = wpmudev_chat_get_user_status( $current_user->ID );
	if (isset($wpmudev_chat->_chat_options['user-statuses'][$chat_user_status])) {
		$user_meta['chat_user_status'] = $chat_user_status;
	} else {
		$user_meta['chat_user_status'] = $wpmudev_chat->_chat_options_defaults['user_meta']['chat_user_status'];
	}

	?>
	<form id="profile-edit-form" class="standard-form base" method="post" action="#">
		<div class="editfield wpmudev_chat_status">
			<label for="wpmudev_chat_status"><?php _e('Set Chat status', $wpmudev_chat->translation_domain); ?></label>
			<p class="description"><?php _e('This user option controls your public chat status to other users within your network. This lets you control when others can initiate private chat sessions with you. Note this does not prevent private chats during existing chat sessions you are participating in.', $wpmudev_chat->translation_domain); ?></p>
			<select name="wpmudev_chat_user_settings[chat_user_status]" id="wpmudev_chat_status">
			<?php
				foreach($wpmudev_chat->_chat_options['user-statuses'] as $status_key => $status_label) {
					if ($status_key == $user_meta['chat_user_status']) {
						$selected = ' selected="selected" ';
					} else {
						$selected = '';
					}

					?><option value="<?php echo $status_key;?>" <?php echo $selected; ?>><?php echo $status_label; ?></option><?php
				}
			?>
            </select>
		</div>

		<div class="editfield wpmudev_chat_name_display">
			<label for="wpmudev_chat_name_display"><?php _e('In Chat Sessions show name as', $wpmudev_chat->translation_domain); ?></label>
			<p class="description"><?php _e('During chat sessions you are participating in this setting controls how you will be labelled to other users. Default is display name.', $wpmudev_chat->translation_domain); ?></p>
            <select name="wpmudev_chat_user_settings[chat_name_display]" id="wpmudev_chat_name_display">
            	<option value="display_name" <?php if ( $user_meta['chat_name_display'] == 'display_name' ) {
					echo ' selected="selected" '; } ?>><?php echo __('Display Name', $wpmudev_chat->translation_domain). ": ". $current_user->display_name; ?></option>
            	<option value="user_login" <?php if ( $user_meta['chat_name_display'] == 'user_login' ) {
					echo ' selected="selected" '; } ?>><?php echo __('User Login', $wpmudev_chat->translation_domain). ": ". $current_user->user_login; ?></option>
            </select>
		</div>

		<?php if ( is_admin_bar_showing() ) { ?>
		<div class="editfield wpmudev_chat_wp_toolbar">
			<label for="wpmudev_chat_wp_toolbar"><?php _e('Show Chat WordPress toolbar menu?', $wpmudev_chat->translation_domain); ?></label>
            <select name="wpmudev_chat_user_settings[chat_wp_toolbar]" id="wpmudev_chat_wp_toolbar">
            	<option value="enabled"<?php if ( $user_meta['chat_wp_toolbar'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
					_e('Enabled', $wpmudev_chat->translation_domain); ?></option>
            	<option value="disabled"<?php if ( $user_meta['chat_wp_toolbar'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
					_e('Disabled', $wpmudev_chat->translation_domain); ?></option>
            </select>
		</div>
		<?php } ?>
		<div class="submit">
			<input id="wpmudev_chat-submit" type="submit" value="<?php _e('Save Changes', $wpmudev_chat->translation_domain); ?>" name="wpmudev_chat">
		</div>
	</form>
	<?php
	return;

}