<?php
function wpmudev_chat_get_user_status($user_id=0) {
	global $wpmudev_chat;

	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return "offline";

	$status = get_user_meta($user_id, 'wpmudev_chat_user_status', true);

	// Double check the value stored is a valid posible status.
	if (isset($wpmudev_chat->_chat_options['user-statuses'][$status])) {
		return $status;
	} else
		return "offline";
}

function wpmudev_chat_update_user_status($user_id=0, $status = 'offline') {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	update_user_meta($user_id, 'wpmudev_chat_user_status', $status);
}

function wpmudev_chat_update_user_activity($user_id=0) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	update_user_meta($user_id, 'wpmudev_chat_last_activity', time());
}

function wpmudev_chat_wpadminbar_render() {
	global $wp_admin_bar, $wpmudev_chat;

	if ( !is_user_logged_in() ) return;

	if (!isset($wpmudev_chat->user_meta['chat_wp_admin'])) return;

	if ((is_admin()) && ($wpmudev_chat->user_meta['chat_wp_admin'] != 'enabled')) {
		return;
	}

	$user_id = get_current_user_id();
	if ( !is_admin_bar_showing() )
	      return;

	$wpmudev_chat->load_configs();

	if ((!isset($wpmudev_chat->user_meta['chat_wp_toolbar'])) || ($wpmudev_chat->user_meta['chat_wp_toolbar'] != 'enabled'))
		return;

	if (is_admin()) {
		if ($wpmudev_chat->_chat_plugin_settings['blocked_urls']['admin'] == true)
			return;
	} else {
		if (($wpmudev_chat->_chat_plugin_settings['blocked_urls']['front'] == true)
	 	 && (!count($wpmudev_chat->chat_sessions)) ) {
			return;
		}
	}

	$chat_user_status = $wpmudev_chat->user_meta['chat_user_status'];

	$_parent_menu_id = 'wpmudev-chat-container';
 	$wp_admin_bar->add_menu( array(
 		'parent'	=> false,
 		'id' 		=> $_parent_menu_id,
 		'title' 	=> '<span class="wpmudev-chat-user-status-current"><span class="wpmudev-chat-ab-icon wpmudev-chat-ab-icon-'. $chat_user_status
						. '"></span><span class="wpmudev-chat-ab-label">'. __( 'Chat', $wpmudev_chat->translation_domain ) .'</span>'
						. '</span>',
 		'href' 		=> false,
 	));

 	$wp_admin_bar->add_menu(array(
 		'parent' 	=> $_parent_menu_id,
 		'id' 		=> 'wpmudev-chat-user-statuses',
 		'title' 	=> __('Status', $wpmudev_chat->translation_domain) .' - <span class="wpmudev-chat-current-stauts-label wpmudev-chat-ab-label">'.
 			$wpmudev_chat->_chat_options['user-statuses'][$chat_user_status] .'</span>',
 		'href' 		=> false
 	));

	foreach($wpmudev_chat->_chat_options['user-statuses'] as $status_key => $status_label) {
		$sub_menu_meta_title 	= __('Switch Chat Status to', $wpmudev_chat->translation_domain) .' '. $status_label;
		$sub_menu_meta_status 	= '<span class="wpmudev-chat-ab-icon wpmudev-chat-ab-icon-'. $status_key .'"></span><span class="wpmudev-chat-ab-label">'.
		 	$status_label .'</span>';
		$sub_menu_meta_rel 		= $status_key;

		$wp_admin_bar->add_menu(array(
			'parent' 	=> 'wpmudev-chat-user-statuses',
			'id' 		=> 'wpmudev-chat-user-status-change-'. $status_key,
			'title' 	=> '<a class="ab-item" title="'. $sub_menu_meta_title .'" href="#" rel="'.
			 	$sub_menu_meta_rel .'">'. $sub_menu_meta_status .'</a>',
			'href' 		=> false,
		));

	}

	wpmudev_chat_wpadminbar_menu_friends($_parent_menu_id, $user_id);
	//wpmudev_chat_wpadminbar_menu_invites($_parent_menu_id, $user_id);
}

function wpmudev_chat_wpadminbar_menu_friends($_parent_menu_id='', $user_id=0) {
	global $wp_admin_bar, $wpmudev_chat, $bp;

	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	if ((!empty($bp)) && (function_exists('bp_get_friend_ids'))) {
		//echo "here in BP land!<br />";
		$friends_ids = bp_get_friend_ids($bp->loggedin_user->id);
		//echo "friends_ids=[". $friends_ids ."]<br />";
		if (!empty($friends_ids)) {
			$friends_list_ids = explode(',', $friends_ids);
		}
		//echo "friend_ids<pre>"; print_r($friend_ids); echo "</pre>";
		//die();

	} else {

		if ((!is_admin()) && (!function_exists('is_plugin_active'))) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if (!is_plugin_active('friends/friends.php')) {
			if ((is_multisite()) && (!is_plugin_active_for_network('friends/friends.php'))) {
				return;
			}
		}
		if (!function_exists('friends_get_list')) return;

		$friends_list_ids = friends_get_list($user_id);
	}

//	echo "friends_list_ids<pre>"; print_r($friends_list_ids); echo "</pre>";
	if (empty($friends_list_ids))
		return;

	$friends_status = wpmudev_chat_get_friends_status($user_id, $friends_list_ids);
	//echo "friends_status<pre>"; print_r($friends_status); echo "</pre>";
	if ( ($friends_status) && (is_array($friends_status)) && (count($friends_status)) ) {
	 	$wp_admin_bar->add_menu(array(
	 		'parent' 	=> $_parent_menu_id,
	 		'id' 		=> 'wpmudev-chat-user-friends',
	 		'title' 	=> __('Friends Online', $wpmudev_chat->translation_domain),
	 		'href' 		=> false
	 	));
		//echo "friends_status<pre>"; print_r($friends_status); echo "</pre>";
		foreach($friends_status as $friend) {
			if ((isset($friend->chat_status)) && ($friend->chat_status == "available")) {
				$friend_status_data = wpmudev_chat_get_chat_status_data($user_id, $friend);
				//echo "friend_status_data<pre>"; print_r($friend_status_data); echo "</pre>";
				$wp_admin_bar->add_menu(array(
					'parent' 	=> 	'wpmudev-chat-user-friends',
					'id'		=>	md5($friend->ID),
					'title'		=>	'<a class="ab-item '. $friend_status_data['href_class'] .'" title="'. $friend_status_data['href_title'] .'" href="#" rel="'.
					 	md5($friend->ID) .'"><span class="wpmudev-chat-ab-icon wpmudev-chat-ab-icon-'. $friend->chat_status .'"></span>'.
						'<span class="wpmudev-chat-ab-label">'. $friend->display_name .'</span>'
						.'</a>'
				));
			}
		}
	} else {
	 	$wp_admin_bar->add_menu(array(
	 		'parent' 	=> $_parent_menu_id,
	 		'id' 		=> 'wpmudev-chat-user-friends',
	 		'title' 	=> __('Friends Online', $wpmudev_chat->translation_domain),
	 		'href' 		=> false
	 	));

		$wp_admin_bar->add_menu(array(
			'parent' 	=> 	'wpmudev-chat-user-friends',
			'title'		=>	__('None', $wpmudev_chat->translation_domain),
			'id'		=>	'none'
		));
	}
}

function wpmudev_chat_get_chat_status_label($user_id, $friend_id, $label_on='', $label_off='') {
	global $wpmudev_chat;

	$friends_status = wpmudev_chat_get_friends_status($user_id, $friend_id);
	//echo "friends_status<pre>"; print_r($friends_status); echo "</pre>";
	if (!empty($friends_status[0])) {
		$friends_status = $friends_status[0];
	} else {
		$friends_status = '';
	}

	$friend_data = wpmudev_chat_get_chat_status_data($user_id, $friends_status);
	//echo "friend_data<pre>"; print_r($friend_data); echo "</pre>";
	$friend_status_display = $friend_data['icon'] . $friend_data['label'];
	if ((!empty($friend_data)) && (isset($friend_data['href'])) && (!empty($friend_data['href']))) {
		return '<a class="'. $friend_data['href_class'] .'" title="'. $friend_data['href_title'] .'"
			href="#" rel="'. $friend_data['href'] .'">'. $friend_status_display .'</a>';
	} else {
		return $friend_status_display;
	}
}

function wpmudev_chat_get_chat_status_data($user_id, $friend) {
	global $wpmudev_chat;

	$chat_status_array = array();

	if ( (!isset($friend->chat_status)) || (!isset($wpmudev_chat->_chat_options['user-statuses'][$friend->chat_status])) ) {
		$friend->chat_status = 'offline';
	}

	//echo "user-statuses<pre>"; print_r($wpmudev_chat->_chat_options['user-statuses']); echo "</pre>";

	$chat_status_array['icon'] 	= '<span class="wpmudev-chat-ab-icon wpmudev-chat-ab-icon-'. $friend->chat_status .'"></span>';
	$chat_status_array['label'] = '<span class="wpmudev-chat-ab-label">'. $wpmudev_chat->_chat_options['user-statuses'][$friend->chat_status] .'</span>';

	if ($friend->chat_status == "available") {
		$chat_status_array['href'] 			= md5($friend->ID);
		$chat_status_array['href_title'] 	= $wpmudev_chat->_chat_options['user-statuses'][$friend->chat_status]; //__('Chat now with') .' '. $friend->display_name;
		$chat_status_array['href_class'] 	= 'wpmudev-chat-user-invite';
	} else {
		$chat_status_array['href'] 			= '';
		$chat_status_array['href_title'] 	= $wpmudev_chat->_chat_options['user-statuses'][$friend->chat_status]; //__('Chat - Offline', $wpmudev_chat->translation_domain);
		$chat_status_array['href_class'] 	= '';
	}
	return $chat_status_array;
}

function wpmudev_chat_get_friends_status($user_id, $friends_list) {
	global $wpdb;

	if (empty($friends_list)) return;

//	echo "user_id=[". $user_id ."]<br />";
//	echo "friends_list<pre>"; print_r($friends_list); echo "</pre>";

//	if ( $friends = get_transient( 'wpmudev-chat-friends-status-'. $user_id ) ) {
//		//echo "XXXfriends<pre>"; print_r($friends); echo "</pre>";
//		return $friends;
//	}

	//echo "user_id=[". $user_id ."]<br />";
	//echo "friends_list<pre>"; print_r($friends_list); echo "</pre>";

	if (!is_array($friends_list))
		$friends_list = array($friends_list);

	//echo "friends_list<pre>"; print_r($friends_list); echo "</pre>";

	$time_threshold = time()-300;	// 5 minites. Though the user_meta field is updated on each page load.

	$sql_str = "SELECT users.ID, users.display_name, usermeta.meta_value as last_activity, usermeta2.meta_value as chat_status
			FROM ". $wpdb->base_prefix ."users as users
			LEFT JOIN ". $wpdb->base_prefix ."usermeta as usermeta ON users.ID=usermeta.user_id
			LEFT JOIN ". $wpdb->base_prefix ."usermeta as usermeta2 ON users.ID=usermeta2.user_id
			WHERE users.ID IN (". implode(",", $friends_list) .")
				AND usermeta.meta_key='wpmudev_chat_last_activity' AND usermeta.meta_value > ". $time_threshold ." AND usermeta2.meta_key='wpmudev_chat_user_status'
			ORDER BY users.display_name ASC
			LIMIT 50";
	//echo "sql_str=[". $sql_str ."]<br />";

	$friends_status = $wpdb->get_results( $sql_str );
	//echo "friends_status<pre>"; print_r($friends_status); echo "</pre>";
//	set_transient( 'wpmudev-chat-friends-status-'. $user_id, $friends_status, 60 );

	return $friends_status;
}

function wpmudev_chat_wpadminbar_menu_invites($_parent_menu_id='', $user_id=0) {
	global $wp_admin_bar, $wpmudev_chat;

	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	wpmudev_chat_process_invites($user_id);
	$invites = wpmudev_chat_get_invites($user_id);
	if ($invites) {
		//echo "invites<pre>"; print_r($invites); echo "</pre>";
		$wp_admin_bar->add_menu(array(
	 		'parent' 	=> $_parent_menu_id,
	 		'id' 		=> 'wpmudev-chat-user-invites',
	 		'title' 	=> __('Invites', $wpmudev_chat->translation_domain),
	 		'href' 		=> '#'
	 	));

		if (isset($invites['from'])) {

			$wp_admin_bar->add_menu(array(
		 		'parent' 	=> 'wpmudev-chat-user-invites',
		 		'id' 		=> 'wpmudev-chat-user-invites-from',
		 		'title' 	=> __('From', $wpmudev_chat->translation_domain),
		 		'href' 		=> '#'
		 	));
			foreach($invites['from'] as $invite_user_id => $invite) {
				$wp_admin_bar->add_menu(array(
			 		'parent' 	=> 'wpmudev-chat-user-invites-from',
			 		'id' 		=> 'wpmudev-chat-user-invites-from-user-'. $invite_user_id,
			 		'title' 	=> get_the_author_meta('display_name', $invite_user_id) ." (". human_time_diff(intval($invite['timestamp'])) ." ago)",
			 		'href' 		=> '#'
			 	));
			}
		}

		if (isset($invites['to'])) {
			$wp_admin_bar->add_menu(array(
		 		'parent' 	=> 'wpmudev-chat-user-invites',
		 		'id' 		=> 'wpmudev-chat-user-invites-to',
		 		'title' 	=> __('To', $wpmudev_chat->translation_domain),
		 		'href' 		=> '#'
		 	));
			foreach($invites['to'] as $invite_user_id => $invite) {
				$wp_admin_bar->add_menu(array(
			 		'parent' 	=> 'wpmudev-chat-user-invites-to',
			 		'id' 		=> 'wpmudev-chat-user-invites-to-user-'. $invite_user_id,
			 		'title' 	=> get_the_author_meta('display_name', $invite_user_id) ." (". human_time_diff(intval($invite['timestamp'])) ." ago)",
			 		'href' 		=> '#'
			 	));
			}
		}
	}
}

function wpmudev_chat_get_invites($user_id=0) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	return get_user_meta($user_id, 'wpmudev_chat_invites', true);
}

function wpmudev_chat_update_invites($user_id=0, $invites) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	return update_user_meta($user_id, 'wpmudev_chat_invites', $invites);
}

function wpmudev_chat_process_invites($user_id=0) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return;

	if (isset($_GET['wpmudev-chat-invite-user'])) {

		$friend_user_id = intval($_GET['wpmudev-chat-invite-user']);
		if ($friend_user_id > 0) {
			if ((isset($_GET['wpmudev-chat-invite-noonce-field']))
		 	 && wp_verify_nonce($_GET['wpmudev-chat-invite-noonce-field'], 'wpmudev-chat-invite-noonce-field'. $user_id.'-'.$friend_user_id)) {

				// For Chat Invites we set one record to the requestors 'to' stack...
				$user_invites = wpmudev_chat_get_invites($user_id);
				if (!isset($user_invites['to'])) $user_invites['to'] = array();
				$invite_item = array(
					'key'		=>	$_GET['wpmudev-chat-invite-noonce-field'],
					'timestamp'	=>	time()
				);
				$user_invites['to'][$friend_user_id] = $invite_item;
				wpmudev_chat_update_invites($user_id, $user_invites);

				// Then we set one record in the requestee's stack.
				$user_invites = wpmudev_chat_get_invites($friend_user_id);
				if (!isset($user_invites['from'])) $user_invites['from'] = array();
				$invite_item = array(
					'key'		=>	$_GET['wpmudev-chat-invite-noonce-field'],
					'timestamp'	=>	time()
				);

				$user_invites['from'][$user_id] = $invite_item;
				wpmudev_chat_update_invites($friend_user_id, $user_invites);
			}
		}
	}
}