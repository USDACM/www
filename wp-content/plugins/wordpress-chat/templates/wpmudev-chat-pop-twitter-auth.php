<?php
session_start();

global $wpmudev_chat;

include_once( dirname(dirname(__FILE__)) . '/lib/twitteroauth/twitteroauth.php');
$twitter_status_message = '';

if ((isset($_GET['oauth_token'])) && (!empty($_GET['oauth_token']))
 && (isset($_GET['oauth_verifier'])) && (!empty($_GET['oauth_verifier'])) ) {

	$oauth_token 		= esc_attr($_GET['oauth_token']);
	$oauth_verifier 	= esc_attr($_GET['oauth_verifier']);

	if (isset($_SESSION['wpmudev-chat-twitter-tokens'])) {

		$twitter_tokens = $_SESSION['wpmudev-chat-twitter-tokens'];
		if ((isset($twitter_tokens['oauth_token'])) && (!empty($twitter_tokens['oauth_token']))
		 && (isset($twitter_tokens['oauth_token_secret'])) && (!empty($twitter_tokens['oauth_token_secret']))) {

			$twitter_connection = new TwitterOAuthChat(
				$wpmudev_chat->get_option('twitter_api_key', 'global'),
				$wpmudev_chat->get_option('twitter_api_secret', 'global'),
				$twitter_tokens['oauth_token'], $twitter_tokens['oauth_token_secret']);

			$access_token = $twitter_connection->getAccessToken($oauth_verifier);
			//echo "access_token<pre>"; print_r($access_token); echo "</pre>";

			if ((isset($access_token['oauth_token'])) && (!empty($access_token['oauth_token']))
			 && (isset($access_token['oauth_token_secret'])) && (!empty($access_token['oauth_token_secret']))) {

				$twitter_content = $twitter_connection->get('account/verify_credentials');
				if (isset($twitter_content->errors[0]->message)) {
					//echo "twitter_content<pre>"; print_r($twitter_content); echo "</pre>";
					$twitter_status_message = __("ERROR:", $wpmudev_chat->translation_domain) ." ". $twitter_content->errors[0]->message ."<br />";
				} else {

					//echo "twitter_content<pre>"; print_r($twitter_content); echo "</pre>";

					$wpmudev_chat->chat_auth 					= array();
					$wpmudev_chat->chat_auth['type'] 			= 'twitter';
					$wpmudev_chat->chat_auth['id'] 				= $twitter_content->id;
					$wpmudev_chat->chat_auth['name']			= $twitter_content->screen_name;
					$wpmudev_chat->chat_auth['email']			= '';
					$wpmudev_chat->chat_auth['avatar'] 			= $twitter_content->profile_image_url;
					$wpmudev_chat->chat_auth['auth_hash'] 		= md5($twitter_content->id);
					$wpmudev_chat->chat_auth['profile_link'] 	= 'http://twitter.com/@'. $twitter_content->screen_name;
					$wpmudev_chat->chat_auth['ip_address'] 		= (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
					$wpmudev_chat->chat_auth['access_token']	= $access_token;

					unset($_SESSION['wpmudev-chat-twitter-tokens']);

					setcookie('wpmudev-chat-auth', json_encode($wpmudev_chat->chat_auth));
					wp_redirect(remove_query_arg(array('wpmudev-chat-action', 'oauth_token', 'oauth_verifier')));
					die();
				}
			}
		}
		unset($_SESSION['wpmudev-chat-twitter-tokens']);
		wp_redirect(remove_query_arg(array('wpmudev-chat-action', 'oauth_token', 'oauth_verifier')));
		die();
	}
} else if (isset($_GET['denied'])) {
	unset($_SESSION['wpmudev-chat-twitter-tokens']);
	wp_redirect(remove_query_arg(array('wpmudev-chat-action', 'denied')));
	die();

} else {
	$twitter_connection = new TwitterOAuthChat($wpmudev_chat->get_option('twitter_api_key', 'global'), $wpmudev_chat->get_option('twitter_api_secret', 'global'));
	$query_args = array(
		'wpmudev-chat-action'	=>	'pop-twitter',
	);

	$callback_url 	= add_query_arg( $query_args, get_option('siteurl').$_SERVER['REQUEST_URI']);
	$request_token 	= $twitter_connection->getRequestToken($callback_url);

	if ((is_array($request_token)) && (isset($request_token['oauth_token']))) {
		$_SESSION['wpmudev-chat-twitter-tokens'] = $request_token;

   		$request_url = $twitter_connection->getAuthorizeURL($request_token['oauth_token']);
		wp_redirect($request_url);
	}
	die();
}