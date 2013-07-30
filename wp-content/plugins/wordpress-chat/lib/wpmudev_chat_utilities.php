<?php


/**
 * Tries to determine what a post is being shown/edited. Gathers post_id, post_type, etc information
 *
 * @since 2.0.0
 * @uses environment
 *
 * @param none
 * @return array of post_info
 */
function wpmudev_chat_utility_get_post_info() {
	$post_info = array();

	if ( isset( $_GET['post'] ) )
	 	$post_info['post_id'] = $post_ID = (int) $_GET['post'];
	elseif ( isset( $_POST['post_ID'] ) )
	 	$post_info['post_id'] = $post_ID = (int) $_POST['post_ID'];
	else
	 	$post_info['post_id'] = $post_ID = 0;

	$post = $post_type = $post_type_object = null;

	if ( $post_info['post_id'] ) {
		$post = get_post( $post_info['post_id'] );
		if ( $post )
			$post_info['post_type'] = $post->post_type;

	} else {
		if ( isset($_GET['post_type']) )
			$post_info['post_type'] = $_GET['post_type'];
		else
			$post_info['post_type'] = 'post';
	}
	return $post_info;
}

/**
 * Get the last chat id for the given blog
 *
 * @global	object	$wpdb
 * @global	int		$blog_id
 */
function wpmudev_chat_get_last_chat_id() {
	//global $wpdb, $blog_id;
	//$last_id = $wpdb->get_var("SELECT chat_id FROM `".WPMUDEV_Chat::tablename('message')."` WHERE blog_id = '{$blog_id}' ORDER BY chat_id DESC LIMIT 1");

	//if ($last_id) {
	//	return substr($last_id, 0, -1);
	//}
	//return 1;

	return wp_generate_password( 16, false);
}

/**
 * Test whether logged in user is a moderator
 *
 * @param	Array	$moderator_roles Moderator roles
 * @return	bool	$moderator	 True if moderator False if not
 */
function wpmudev_chat_is_moderator($chat_session, $debug = false) {
	global $current_user, $bp;

	if ($chat_session['session_type'] === "bp-group") {
		if ((function_exists('groups_is_user_mod'))
		 && (function_exists('groups_is_user_admin')) ) {
			if ( (groups_is_user_mod( $bp->loggedin_user->id, $bp->groups->current_group->id ))
			 ||  (groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ))
			 ||  (is_super_admin()) ) {
				return true;
			}
		}
		return false;

	}

	if ($chat_session['session_type'] === "private") {
		if ( (isset($chat_session['invite-info']['moderator'])) && ($chat_session['invite-info']['moderator'] == "yes") ) {
			return true;
		} else {
			return false;
		}
	}

	// all others

	// If the chat session doesn't have any defined moderator roles then no need to go further.
	if ( (!is_array($chat_session['moderator_roles'])) || (!count($chat_session['moderator_roles'])) )
		return false;

	if (!is_multisite()) {
		if ($current_user->ID) {
			foreach ($chat_session['moderator_roles'] as $role) {
				if (in_array($role, $current_user->roles)) {
					return true;
				}
			}
		}

	} else {
		// We only consider super admins IF the normal 'administrator' role is set.
		if ((is_super_admin()) && (array_search('administrator', $chat_session['moderator_roles']) !== false))
			return true;


		if ($current_user->ID) {
			foreach ($chat_session['moderator_roles'] as $role) {
				if (in_array($role, $current_user->roles)) {
					return true;
				}
			}
		}
	}
	return false;
}

/** Need to check the size_str that it contains one of the values. If not intval the string and append 'px' */
function wpmudev_chat_check_size_qualifier($size_str = '', $size_qualifiers = array('px', 'pt', 'em', '%')) {
	if (empty($size_str)) $size_str = "0"; //return $size_str;

	if (count($size_qualifiers)) {
		foreach($size_qualifiers as $size_qualifier) {
			if (empty($size_qualifier)) continue;

			if ( substr($size_str, strlen($size_qualifier) * -1, strlen($size_qualifier)) === $size_qualifier)
				return $size_str;
		}
		return intval($size_str) ."px";
	}
}

/**
 * We check the current url against the section blocked_urls.
 *
 * @global	object	$wpmudev_chat
 * @param	string	$section	Section name of options to check: site, widget, etc.
 * @return	true/false			true = URL is blocked. false = URL is not blocked.
 */
function wpmudev_chat_check_is_blocked_urls($urls = array(), $blocked_urls_action = 'exclude', $DEBUG=false) {
	$_FLAG_BLOCK_CHAT = false;

	if ((is_array($urls)) && (count($urls))) {

		$request_url = get_option('siteurl') . $_SERVER['REQUEST_URI'];
		$request_url_parts = parse_url($request_url);
		//if ($DEBUG == true) {
		//	echo "request_url_parts<pre>"; print_r($request_url_parts); echo "</pre>";
		//}

		// Rebuild the request_url without the query part.
		$request_url = $request_url_parts['scheme'] .'://'. $request_url_parts['host'] . $request_url_parts['path'];

		if ((isset($request_url_parts['query'])) && (!empty($request_url_parts['query']))) {
			//if ($DEBUG == true) {
			//	echo "query[". $request_url_parts['query'] ."]<br />";
			//}
			parse_str($request_url_parts['query'], $request_url_query);
		} else {
			$request_url_query = '';
		}

		// Now go through and expand the blocked urls. Those which are relative will be prepended with scheme and host
		foreach($urls as $_idx => $url) {
			$url_parts = parse_url($url);

			if (!isset($url_parts['scheme']))
				$url_parts['scheme'] = $request_url_parts['scheme'];

			if (!isset($url_parts['host']))
				$url_parts['host'] = $request_url_parts['host'];

			$url_check = $url_parts['scheme'] .'://'. $url_parts['host'] . $url_parts['path'];

			$blocked_urls[$_idx] = array();
			$blocked_urls[$_idx]['url'] 	= $url_check;
			$blocked_urls[$_idx]['query']	= array();

			if ((isset($url_parts['query'])) && (strlen($url_parts['query']))) {
				$url_parts['query'] = str_replace("&amp;", '&', $url_parts['query']);
				parse_str($url_parts['query'], $_query_string);
				if ((is_array($_query_string)) && (count($_query_string))) {
					foreach($_query_string as $q_param => $q_val) {
						$q_param 	= trim($q_param);
						$q_val		= trim($q_val);

						// We are allowing or query string parameters without value!
						if (!empty($q_param))
							$blocked_urls[$_idx]['query'][$q_param] = $q_val;
					}
				}
			}
		}

		//if ($DEBUG == true) {
		//
		//	echo "request_url=[". $request_url ."]<br />";
		//	echo "request_url_query<pre>"; print_r($request_url_query); echo "</pre>";
		//
		//	echo "blocked_urls_action=[". $blocked_urls_action ."]<br />";
		//	echo "blocked_urls<pre>"; print_r($blocked_urls); echo "</pre>";
		//}

		$blocked_urls = apply_filters('wpmudev-chat-blocked-site-urls', $blocked_urls);
		if (!empty($blocked_urls)) {

			//$blocked_urls_action = $wpmudev_chat->get_option('blocked_urls_action', $section);

			if ($blocked_urls_action == "exclude") {
				foreach($blocked_urls as $_idx => $blocked_url) {
					if ( $request_url == $blocked_url['url'] ) {
						if (count($blocked_url['query'])) {
							// If our blocked URL contains more query string parameters than our current match then we knowit is not a match.
							if ( count($blocked_url['query']) > count($request_url_query)) {
								$_FLAG_BLOCK_CHAT = false;
							} else {
								foreach($blocked_url['query'] as $q_param => $q_val) {
									if (!isset($request_url_query[$q_param])) {
										//$_FLAG_BLOCK_CHAT = false;
									} else if ($request_url_query[$q_param] != $q_val) {
										//$_FLAG_BLOCK_CHAT = false;
									} else {
										$_FLAG_BLOCK_CHAT = true;
									}
								}
							}
						} else {
							$_FLAG_BLOCK_CHAT = true;
						}
					}
					if ($_FLAG_BLOCK_CHAT == true)
						break;
				}
			} else if ($blocked_urls_action == "include") {
				$_FLAG_BLOCK_CHAT = true;
				foreach($blocked_urls as $blocked_url) {

					//if ($DEBUG == true) {
					//	echo "request_url AAA[". $request_url ."] [". $blocked_url['url']."]<br />";
					//}

					if ( $request_url == $blocked_url['url'] ) {
						//if ($DEBUG == true) {
						//	echo "request_url XXX[". $request_url ."] [". $blocked_url['url']."]<br />";
						//	echo "blocked_url query<pre>"; print_r($blocked_url['query']); echo "</pre>"; echo "</pre>";
						//}

						if (count($blocked_url['query'])) {
							// If our blocked URL contains more query string parameters than our current match then we knowit is not a match.
							if ( count($blocked_url['query']) > count($request_url_query)) {
								$_FLAG_BLOCK_CHAT = false;
							} else {
								foreach($blocked_url['query'] as $q_param => $q_val) {
									if (!isset($request_url_query[$q_param])) {
										$_FLAG_BLOCK_CHAT = false;
									} else if ($request_url_query[$q_param] != $q_val) {
										$_FLAG_BLOCK_CHAT = false;
									}
								}
							}
						} else {
							$_FLAG_BLOCK_CHAT = false;
						}
					}
					if ($_FLAG_BLOCK_CHAT == false)
						break;

				}
			}
		}

		//if ($DEBUG == true) {
		//	if ($_FLAG_BLOCK_CHAT == true)
		//		echo "_FLAG_BLOCK_CHAT=true<br />";
		//	else
		//		echo "_FLAG_BLOCK_CHAT=false<br />";
		//}
	}

	return $_FLAG_BLOCK_CHAT;
}