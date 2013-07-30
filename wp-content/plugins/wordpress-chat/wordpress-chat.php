<?php
/*
Plugin Name: WordPress Chat
Plugin URI: http://premium.wpmudev.org/project/wordpress-chat-plugin
Description: Provides you with a fully featured chat area either in a post, page, widget or bottom corner of your site - once activated configure <a href="admin.php?page=chat_settings_panel">here</a> and drop into a post or page by clicking on the new chat icon in your post/page editor.
Author: Paul Menard (Incsub)
WDP ID: 159
Version: 2.0.4.4
Author URI: http://premium.wpmudev.org
Text Domain: wordpress_chat
*/

include_once( dirname(__FILE__) . '/lib/wpmudev_chat_utilities.php');
include_once( dirname(__FILE__) . '/lib/wpmudev_chat_wpadminbar.php');

if ((!defined('WPMUDEV_CHAT_SHORTINIT')) || (WPMUDEV_CHAT_SHORTINIT != true)) {
	//include_once( dirname(__FILE__) . '/lib/dash-notices/wpmudev-dash-notification.php');
	include_once( dirname(__FILE__) . '/lib/wpmudev_chat_widget.php');
	include_once( dirname(__FILE__) . '/lib/wpmudev_chat_buddypress.php');
}

if (!class_exists('WPMUDEV_Chat')) {
class WPMUDEV_Chat {
	var $chat_current_version = '2.0.4.4';
	var $translation_domain = 'wordpress-chat';

	/**
	 * @var		array	$_chat_options			Consolidated options
	 */
	var $_chat_plugin_settings		= array();	// Container for setting within the running code.

	var $_chat_options 				= array();	// This is the local instance of the combined options from the wp_options table
	var $_chat_options_defaults 	= array();

	var $chat_localized 			= array(); 	// Contains localized strings passed to the JS file for display to user.

	var $chat_sessions 				= array();	// Contains all active user chat_sessions known.
	var $chat_user 					= array();	// Container for all user settings by chat_session. Things like if the chat window is minimized, sound on/off etc.
	var $chat_auth					= array();	// Container global information for how user is authenticated, avatar src, name, ID, user_hash.

	var $user_meta 					= array();	// Contains usermeta information related to Chat functionality within wp-admin

	var $using_popup_out_template 	= false;	// Flag set when loadin the pop-out template

	var $_show_own_admin 			= false;	// Flag set in on_load_panels() and user in wp-footer and wp_enqueue_scripts to know what script/styles to load.

	var $_registered_scripts		= array();	// array of scripts registered or enqueued via the various startup methods. User in wp_footer via print_scripts
	var $_registered_styles			= array();	// array of styles registered or enqueued via the various startup methods. User in wp_footer via print_styles

	var $font_list;								// Not used.

	/**
	 * Initializing object
	 *
	 * Plugin register actions, filters and hooks.
	 */
	function WPMUDEV_Chat() {

		$this->chat_sessions 	= array();
		$this->chat_auth 		= array();
		$this->chat_user 		= array();
		$this->_chat_options	= array();

		$this->_chat_plugin_settings['plugin_path'] 	= dirname(__FILE__);
		$this->_chat_plugin_settings['plugin_url'] 		= plugins_url('', __FILE__);
		$this->_chat_plugin_settings['blocked_urls']	= array();

		// Short circut out. We don't need these during our SHORTINIT processing.
		if ((defined('WPMUDEV_CHAT_SHORTINIT')) && (WPMUDEV_CHAT_SHORTINIT == true)) return;

		// Activation deactivation hooks
		register_activation_hook(__FILE__, array(&$this, 'install'));
		register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));

		add_action( 'init', array(&$this, 'init') );
		add_action( 'wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts') );

		add_action( 'admin_init', array(&$this, 'admin_init') );
		add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts') );

		add_action( 'template_redirect', array(&$this, 'load_template'), 1 );

		add_action( 'wp_footer', array(&$this, 'wp_footer'), 1 );
		add_action( 'admin_footer', array(&$this, 'admin_footer'), 1 );

		// Actions for editing/saving user profile.
		add_action( 'show_user_profile', array(&$this, 'chat_edit_user_profile') );
		add_action( 'personal_options_update', array(&$this, 'chat_save_user_profile') );
		add_action( 'edit_user_profile', array(&$this, 'chat_edit_user_profile') );
		add_action( 'edit_user_profile_update', array(&$this, 'chat_save_user_profile') );

		add_filter( 'manage_users_columns', array(&$this, 'chat_manage_users_columns') );
		add_filter( 'manage_users_custom_column', array(&$this, 'chat_manage_users_custom_column'), 10, 3 );

		add_action( 'wp_ajax_chatProcess', array(&$this, 'process_chat_actions') );
		add_action( 'wp_ajax_nopriv_chatProcess', array(&$this, 'process_chat_actions') );

		// TinyMCE options
		add_action( 'wp_ajax_chatTinymceOptions', array(&$this, 'tinymce_options') );

		add_action( 'admin_menu', array(&$this, 'admin_menu') );
		//add_action( 'network_admin_menu', array(&$this, 'network_admin_menu') );

		// Uncomment when ready to show chat on the WP admin toolbar. Not ready 2013-01-31
		add_action( 'wp_before_admin_bar_render', 'wpmudev_chat_wpadminbar_render' );

		add_shortcode( 'chat', array(&$this, 'process_chat_shortcode') );
	}

	/**
	 * Get the table name with prefixes
	 *
	 * @global	object	$wpdb
	 * @param	string	$table	Table name
	 * @return	string			Table name complete with prefixes
	 */
	function tablename($table) {
		global $wpdb;
		// We use a single table for all chats accross the network
		return $wpdb->base_prefix.'wpmudev_chat_'.$table;
	}

	/**
	 * Determine if we need to run the DB update options to bring the system up to date.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function check_upgrade() {

		// Check our version against the options table
		if (is_multisite())
			$options_version = get_site_option('wpmudev-chat-version');
		else
			$options_version = get_option('wpmudev-chat-version');
		if (version_compare($this->chat_current_version, $options_version) > 0) {
			$this->install();

			if (is_multisite())
				update_site_option('wpmudev-chat-version', $this->chat_current_version);
			else
				update_option('wpmudev-chat-version', $this->chat_current_version);
		}
		//die();
	}

	/**
	 * Activation hook
	 *
	 * Create tables if they don't exist and add plugin options
	 *
	 * @see		http://codex.wordpress.org/Function_Reference/register_activation_hook
	 *
	 * @global	object	$wpdb
	 */
	function install() {
		global $wpdb;

		/**
		 * WordPress database upgrade/creation functions
		 */
		if (!function_exists('dbDelta'))
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Get the correct character collate
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";

		$sql_table_message_1_3_x = "CREATE TABLE ". WPMUDEV_Chat::tablename('message') ." (
			id BIGINT NOT NULL AUTO_INCREMENT,
			blog_id INT NOT NULL ,
			chat_id INT NOT NULL ,
			timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ,
			name VARCHAR( 255 ) CHARACTER SET utf8 NOT NULL ,
			avatar VARCHAR( 1024 ) CHARACTER SET utf8 NOT NULL ,
			message TEXT CHARACTER SET utf8 NOT NULL ,
			moderator ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' ,
			archived ENUM( 'yes', 'no', 'yes-deleted', 'no-deleted' ) DEFAULT 'no' ,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id),
			KEY timestamp (timestamp),
			KEY archived (archived)
			) ENGINE = InnoDB {$charset_collate};";

		$sql_table_log_1_3_x = "CREATE TABLE ".WPMUDEV_Chat::tablename('log')." (
			id BIGINT NOT NULL AUTO_INCREMENT,
			blog_id INT NOT NULL ,
			chat_id INT NOT NULL ,
			start TIMESTAMP DEFAULT '0000-00-00 00:00:00' ,
			end TIMESTAMP DEFAULT '0000-00-00 00:00:00' ,
			created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id)
			) ENGINE = InnoDB {$charset_collate};";


		$sql_table_message__current = "CREATE TABLE ".WPMUDEV_Chat::tablename('message')." (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			blog_id int(11) NOT NULL,
			chat_id varchar(40) NOT NULL,
			session_type varchar(40) NOT NULL,
			timestamp TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ,
			name varchar(255) NOT NULL,
			avatar varchar(1024) NOT NULL,
			auth_hash varchar(50) NOT NULL,
			ip_address varchar(50) NOT NULL,
			message text NOT NULL,
			moderator enum('no','yes') NOT NULL DEFAULT 'no',
			deleted enum('no','yes') NOT NULL DEFAULT 'no',
			archived enum('no','yes') NOT NULL DEFAULT 'no',
			log_id INT(11) NOT NULL,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id),
			KEY auth_hash (auth_hash),
			KEY session_type (session_type),
			KEY timestamp (timestamp),
			KEY archived (archived),
			KEY log_id (log_id)
			) ENGINE = InnoDB {$charset_collate};";

		$sql_table_log_current = "CREATE TABLE ".WPMUDEV_Chat::tablename('log')." (
			id BIGINT NOT NULL AUTO_INCREMENT,
			blog_id INT NOT NULL ,
			chat_id VARCHAR( 40 ) NOT NULL ,
			session_type VARCHAR( 40 ) NOT NULL,
			messages_count INT( 11 ) NOT NULL ,
			users_count INT( 11 ) NOT NULL ,
			start TIMESTAMP DEFAULT '0000-00-00 00:00:00' ,
			end TIMESTAMP DEFAULT '0000-00-00 00:00:00' ,
			created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY blog_id (blog_id),
			KEY chat_id (chat_id),
			KEY session_type (session_type)
			) ENGINE = InnoDB {$charset_collate};";

		$sql_table_users_current = "CREATE TABLE ".WPMUDEV_Chat::tablename('users')." (
		  	blog_id bigint(20) unsigned NOT NULL,
		  	chat_id varchar(40) NOT NULL,
		  	auth_hash varchar(50) NOT NULL,
		  	name varchar(255) NOT NULL,
		  	avatar varchar(255) NOT NULL,
		  	moderator enum('no','yes') NOT NULL DEFAULT 'no',
		  	last_polled timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  	entered timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
		  	ip_address varchar(39) NOT NULL DEFAULT '',
		  	PRIMARY KEY  (blog_id,chat_id,auth_hash),
		  	KEY blog_id (blog_id),
		  	KEY chat_id (chat_id),
		  	KEY auth_hash (auth_hash),
		  	KEY last_polled (last_polled)
			) ENGINE = InnoDB {$charset_collate};";

		if($wpdb->get_var("SHOW TABLES LIKE '". WPMUDEV_Chat::tablename('message') ."'") != WPMUDEV_Chat::tablename('message'))
		{
			// First check if we have the old Chat 1.3.x table still around.
			if ($wpdb->get_var("SHOW TABLES LIKE '". $wpdb->base_prefix ."chat_message'") == $wpdb->base_prefix ."chat_message") {

				// Create a new table with the same structure.
				dbDelta($sql_table_message_1_3_x);

				// Now copy the rows from the old table into the new table.
				$sql_str = "INSERT INTO `". WPMUDEV_Chat::tablename('message') ."` SELECT * FROM ". $wpdb->base_prefix ."chat_message;";
				$wpdb->query($sql_str);

				// Finally we alter the table structure to work with our new plugin
				dbDelta($sql_table_message__current);
			} else {
				dbDelta($sql_table_message__current);
			}

		} else {
			dbDelta($sql_table_message__current);
		}

		if($wpdb->get_var("SHOW TABLES LIKE '". WPMUDEV_Chat::tablename('log') ."'") != WPMUDEV_Chat::tablename('log'))
		{
			// First check if we have the old Chat 1.3.x table still around.
			if ($wpdb->get_var("SHOW TABLES LIKE '". $wpdb->base_prefix ."chat_log'") == $wpdb->base_prefix ."chat_log") {
				dbDelta($sql_table_log_1_3_x);

				// Now copy the rows from the old table into the new table.
				$sql_str = "INSERT INTO `". WPMUDEV_Chat::tablename('log') ."` SELECT * FROM ". $wpdb->base_prefix ."chat_log;";
				$wpdb->query($sql_str);

				// Setup the chat log table
				dbDelta($sql_table_log_current);

			} else {
				dbDelta($sql_table_log_current);
			}
		} else {
			dbDelta($sql_table_log_current);
		}


		if($wpdb->get_var("SHOW TABLES LIKE '". WPMUDEV_Chat::tablename('users') ."'") != WPMUDEV_Chat::tablename('users'))
		{
			dbDelta($sql_table_users_current);
		} else {
			dbDelta($sql_table_users_current);
		}

		$this->load_configs();

		add_option('wpmudev-chat-page', 	$this->_chat_options['page']);
		add_option('wpmudev-chat-site', 	$this->_chat_options['site']);
		add_option('wpmudev-chat-widget', 	$this->_chat_options['widget']);
		add_option('wpmudev-chat-global', 	$this->_chat_options['global']);
		add_option('wpmudev-chat-banned', 	$this->_chat_options['banned']);
	}

	/**
	 * Deactivation hook
	 *
	 * @see		http://codex.wordpress.org/Function_Reference/register_deactivation_hook
	 *
	 * @global	object	$wpdb
	 */
	function uninstall() {
		global $wpdb;
		// Nothing to do
	}

	/**
	 * Loads the various config options from get_options calls. Parse the config array with the defaults in case new options were added/removed.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */

	function load_configs() {
		global $blog_id;

		$this->set_option_defaults();

		$this->_chat_options['user-statuses'] = get_option( 'wpmudev-chat-user-statuses', array() );
		$this->_chat_options['user-statuses'] = wp_parse_args( $this->_chat_options['user-statuses'], $this->_chat_options_defaults['user-statuses'] );

		if (isset($_POST['wpmudev-chat-sessions'])) {
			if ((is_array($_POST['wpmudev-chat-sessions'])) && (count($_POST['wpmudev-chat-sessions']))) {
				foreach($_POST['wpmudev-chat-sessions'] as $chat_session) {
					$chat_id = $chat_session['id'];
					//$transient_key = "chat-session-". $chat_id;
					//$transient_key = "chat-session-". $chat_session['blog_id'] ."-". $chat_session['id'] .'-'. $chat_session['session_type'];
					$transient_key = "chat-session-". $chat_session['id'] .'-'. $chat_session['session_type'];
					//echo "transient_key=[". $transient_key ."]<br />";
					$chat_session_transient = get_transient($transient_key);
					if ((!empty($chat_session_transient)) && (is_array($chat_session_transient))) {
						$chat_session_merge = wp_parse_args( $chat_session, $chat_session_transient );
						if ((!empty($chat_session_merge)) && (is_array($chat_session_merge))) {
							$this->chat_sessions[$chat_id] = $chat_session_merge;
						}
					}
				}
			}
		}

		foreach($this->chat_sessions as $chat_id => $chat_session) {
			if ((isset($chat_session['template'])) && ($chat_session['template'] == "wpmudev-chat-pop-out"))
				$this->using_popup_out_template = true;
		}


		if (isset($_COOKIE['wpmudev-chat-auth'])) {
			$this->chat_auth = json_decode(stripslashes($_COOKIE['wpmudev-chat-auth']), true);

			// IS the user a WP authenticated user? We odn't trust the details. So reset all fields.
			if (is_user_logged_in()) {

				// This is needed to update the user's activity we use to check if a user is online and available for chats
				$current_user = wp_get_current_user();

				wpmudev_chat_update_user_activity($current_user->ID);

				$this->chat_auth['type'] 					= 'wordpress';
				$this->chat_auth['name']					= $current_user->display_name;
				$this->chat_auth['email']					= $current_user->user_email;
				$this->chat_auth['auth_hash'] 				= md5($current_user->ID);
				$this->chat_auth['profile_link'] 			= '';
				$this->chat_auth['ip_address'] 				= (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];

				$this->user_meta = get_user_meta( $current_user->ID, 'wpmudev-chat-user', true);

				// Merge the user's meta info with the defaults.
				$this->user_meta = wp_parse_args( $this->user_meta, $this->_chat_options_defaults['user_meta'] );
				//echo "user_meta<pre>"; print_r($this->user_meta); echo "</pre>";

				// Get the user's Chat status carried as part of the user_meta information
				$chat_user_status = wpmudev_chat_get_user_status( $current_user->ID );
				if (isset($this->_chat_options['user-statuses'][$chat_user_status])) {
					$this->user_meta['chat_user_status'] = $chat_user_status;
				} else {
					$this->user_meta['chat_user_status'] = $this->_chat_options_defaults['user_meta']['chat_user_status'];
				}

				if ($this->user_meta['chat_name_display'] == "user_login")
					$this->chat_auth['name']				= $current_user->user_login;
				else
					$this->chat_auth['name']				= $current_user->display_name;

				// We can only get the avatar when not in SHORTINIT mode since SHORTINIT removes all other plugin filters.
				if ((!defined('WPMUDEV_CHAT_SHORTINIT')) || (WPMUDEV_CHAT_SHORTINIT != true)) {

					$avatar				 		= get_avatar($current_user->ID, 96, get_option('avatar_default'), $this->chat_auth['name']);
					if ($avatar) {
					    $avatar_parts = array();
						if (stristr($avatar, ' src="') !== false) {
					    	preg_match( '/src="([^"]*)"/i', $avatar, $avatar_parts );
						} else if (stristr($avatar, " src='") !== false) {
					    	preg_match( "/src='([^']*)'/i", $avatar, $avatar_parts );
						}
						if ((isset($avatar_parts[1])) && (!empty($avatar_parts[1])))
					    	$this->chat_auth['avatar'] = $avatar_parts[1];
					}
				}
				$this->chat_auth['chat_status'] 			= $this->user_meta['chat_user_status'];
			}
		} else {
			$this->chat_auth 							= array();
		}

		foreach($this->chat_sessions as $session_id => $chat_session) {
			if ((isset($this->chat_auth['type'])) && ($this->chat_auth['type'] == 'wordpress')) {
				if (wpmudev_chat_is_moderator($chat_session))
					$this->chat_sessions[$session_id]['moderator'] = "yes";
				else
					$this->chat_sessions[$session_id]['moderator'] = "no";

			} else {
				$this->chat_sessions[$session_id]['moderator'] = "no";
			}
		}

		// Grab user chat_user cookie data. The chat_user cookie contains user settings like sounds on/off or chat box maximixed/minimized.
		if (isset($_COOKIE['wpmudev-chat-user']))
			$this->chat_user = json_decode(stripslashes($_COOKIE['wpmudev-chat-user']), true);

		if (!isset($this->chat_user['__global__']))
			$this->chat_user['__global__'] = array();

		// The chat_user '__global__' array is the default settings used and compared to the individual settings from the chat_user cookie
		if (!isset($this->chat_user['__global__']['status_max_min']))
			$this->chat_user['__global__']['status_max_min'] = "max";
		if (!isset($this->chat_user['__global__']['sound_on_off']))
			$this->chat_user['__global__']['sound_on_off'] = "on";


		$this->_chat_options['page'] = get_option( 'wpmudev-chat-page', array() );

		// Note: For tinymce options we want to allow to conditions. First if these option is NOT set
		// then we pull the default. But we need to allow the admin to clear the selection. So if the
		// item is found but empty we don't merge the default options.

		if (empty($this->_chat_options['page'])) { // If empty see if we have an older version
			$_chat_options_default = get_option( 'chat_default', array() );
			if (!empty($_chat_options_default)) {
				$this->_chat_options['page'] = $this->convert_config('page', $_chat_options_default);
			}
			$this->_chat_options['page']['tinymce_roles'] = array('administrator');
			$this->_chat_options['page']['tinymce_post_types'] = array('page');
		}
		$this->_chat_options['page'] = wp_parse_args( $this->_chat_options['page'], $this->_chat_options_defaults['page'] );

		$this->_chat_options['site'] = get_option( 'wpmudev-chat-site', array() );
		if (empty($this->_chat_options['site'])) { // If empty see if we have an older version
			$_chat_options_site = get_option( 'chat_site', array() );
			if (!empty($_chat_options_site)) {
				$this->_chat_options['site'] = $this->convert_config('site', $_chat_options_site);
			}
		}
		$this->_chat_options['site'] = wp_parse_args( $this->_chat_options['site'], $this->_chat_options_defaults['site'] );

		$this->_chat_options['widget'] = get_option( 'wpmudev-chat-widget', array() );
		$this->_chat_options['widget'] = wp_parse_args( $this->_chat_options['widget'], $this->_chat_options_defaults['widget'] );

		$this->_chat_options['bp-group'] = get_option( 'wpmudev-chat-bp-group', array() );
		$this->_chat_options['bp-group'] = wp_parse_args( $this->_chat_options['bp-group'], $this->_chat_options_defaults['bp-group'] );

		$this->_chat_options['global'] = get_option( 'wpmudev-chat-global', array() );
		if (empty($this->_chat_options['global'])) { // If empty see if we have an older version
			$_chat_options_global = get_option( 'chat_default', array() );
			if (!empty($_chat_options_global)) {
				$this->_chat_options['global'] = $this->convert_config('global', $_chat_options_global);
			}
		}
		$this->_chat_options['global'] = wp_parse_args( $this->_chat_options['global'], $this->_chat_options_defaults['global'] );

		$this->_chat_options['banned'] = get_option( 'wpmudev-chat-banned', array() );
		$this->_chat_options['banned'] = wp_parse_args( $this->_chat_options['banned'], $this->_chat_options_defaults['banned'] );


		$this->_chat_options_defaults['fonts_list'] = array(
			"Arial" 				=> "Arial, Helvetica, sans-serif",
			"Arial Black" 			=> "'Arial Black', Gadget, sans-serif",
			"Bookman Old Style" 	=> "'Bookman Old Style', serif",
			"Comic Sans MS" 		=> "'Comic Sans MS', cursive",
			"Courier" 				=> "Courier, monospace",
			"Courier New" 			=> "'Courier New', Courier, monospace",
			"Garamond" 				=> "Garamond, serif",
			"Georgia" 				=> "Georgia, serif",
			"Impact" 				=> "Impact, Charcoal, sans-serif",
			"Lucida Console" 		=> "'Lucida Console', Monaco, monospace",
			"Lucida Sans Unicode" 	=> "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
			"MS Sans Serif" 		=> "'MS Sans Serif', Geneva, sans-serif",
			"MS Serif" 				=> "'MS Serif', 'New York', sans-serif",
			"Palatino Linotype" 	=> "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
			"Symbol" 				=> "Symbol, sans-serif",
			"Tahoma" 				=> "Tahoma, Geneva, sans-serif",
			"Times New Roman" 		=> "'Times New Roman', Times, serif",
			"Trebuchet MS" 			=> "'Trebuchet MS', Helvetica, sans-serif",
			"Verdana" 				=> "Verdana, Geneva, sans-serif",
			"Webdings" 				=> "Webdings, sans-serif",
			"Wingdings" 			=> "Wingdings, 'Zapf Dingbats', sans-serif"
		);

		if ((!defined('WPMUDEV_CHAT_SHORTINIT')) || (WPMUDEV_CHAT_SHORTINIT != true)) {

			$this->_chat_plugin_settings['blocked_urls']['admin'] 	= 	wpmudev_chat_check_is_blocked_urls($this->get_option('blocked_admin_urls', 'global'),
																		$this->get_option('blocked_admin_urls_action', 'global'), false);

			$this->_chat_plugin_settings['blocked_urls']['site'] 	= 	wpmudev_chat_check_is_blocked_urls($this->get_option('blocked_urls', 'site'),
																		$this->get_option('blocked_urls_action', 'site'), false);

			$this->_chat_plugin_settings['blocked_urls']['widget'] 	= 	wpmudev_chat_check_is_blocked_urls($this->get_option('blocked_urls', 'widget'),
																		$this->get_option('blocked_urls_action', 'widget'), true);

			if ($this->get_option('load_jscss_all', 'global') == "enabled") {
				$this->_chat_plugin_settings['blocked_urls']['front'] 	= 	wpmudev_chat_check_is_blocked_urls($this->get_option('blocked_front_urls', 'global'),
																		$this->get_option('blocked_front_urls_action', 'global'), false);
			} else {
				$this->_chat_plugin_settings['blocked_urls']['front']	=	true;
			}

			//echo "blocked_url<pre>"; print_r($this->_chat_plugin_settings['blocked_urls']); echo "</pre>";
		}


		// Related to the Network settings.
		if (is_multisite()) {
			$this->_chat_options['network-site'] = get_site_option( 'wpmudev-chat-network-site', array() );
			$this->_chat_options['network-site'] = wp_parse_args( $this->_chat_options['network-site'], $this->_chat_options_defaults['network-site'] );
		}
	}

	/**
	 * Converts the config arrays from the old format (version 1.3.x) into the 2.x format.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function convert_config($section, $old_config) {
		$new_config = array();

		$old_to_new = array(
			'id'							=>	'id',
			'sound'							=> 	'box_sound',
			'avatar'						=> 	'row_name_avatar',
			'emoticons'						=> 	'box_emoticons',
			'date_show'						=> 	'row_date',
			'time_show'						=> 	'row_time',
			'width'							=> 	'box_width',
			'height'						=> 	'box_height',
			'background_color'				=> 	'box_background_color',

			'background_row_area_color'		=>	'row_area_background_color',
			'background_row_color'			=>	'row_background_color',
			'row_border_color'				=>	'row_border_color',
			'row_border_width'				=>	'row_border_width',
			'row_spacing'					=>	'row_spacing',

			'background_highlighted_color'	=> 	'box_new_message_color',
			'date_color'					=> 	'row_date_color',
			'name_color'					=> 	'row_name_color',
			'moderator_name_color'			=> 	'row_moderator_name_color',
			'special_color'					=> 	'special_color',
			'text_color'					=> 	'row_text_color',
			'code_color'					=> 	'row_code_color',
			'font'							=> 	'box_font_family',
			'font_size'						=> 	'box_font_size',
			'font'							=> 	'row_font_family',
			'font_size'						=> 	'row_font_size',
			'font'							=>	'row_message_input_font_family',
			'font_size'						=>	'row_message_input_font_size',
			'log_creation'					=> 	'log_creation',
			'log_display'					=> 	'log_display',
			'log_limit'						=> 	'log_limit',
			'login_options'					=> 	'login_options',
			'moderator_roles'				=> 	'moderator_roles',
			'tinymce_roles'					=> 	'tinymce_roles',
			'tinymce_post_types'			=> 	'tinymce_post_types',
			'site'							=> 	'bottom_corner'
		);

		foreach($old_config as $old_config_key => $old_config_val) {
			// Remove emoty values to force new defaults.
			if ((empty($old_config_val)) && ($old_config_key != 'id'))
				continue;

			// Partial kludge. The previous 'avatar' values were enabled/disabled. So we need to convert these to our
			// new values of 'avatar' or 'name'. Eventually we will have 'avatar_name' to show both. Tristate
			if ($old_config_key == "avatar") {
				if ($old_config_val == "enabled")
					$new_config[$old_config_key] = "avatar";
				else
					$new_config[$old_config_key] = "name";
			} else {
				if (isset($old_to_new[$old_config_key]))
					$new_config[$old_to_new[$old_config_key]] = $old_config_val;
				else
					$new_config[$old_config_key] = $old_config_val;
			}
		}

		switch($section) {
			case 'page':

				foreach($new_config as $_key => $_val) {
					if (!isset($this->_chat_options_defaults['page'][$_key]))
						unset($new_config[$_key]);
				}

				break;

			case 'site':

				foreach($new_config as $_key => $_val) {
					if (!isset($this->_chat_options_defaults['site'][$_key]))
						unset($new_config[$_key]);
				}

				break;

			case 'global':
				foreach($new_config as $_key => $_val) {
					if (!isset($this->_chat_options_defaults['global'][$_key]))
						unset($new_config[$_key]);
				}
				break;

			default:
				break;
		}
		return $new_config;
	}

	/**
	 * Initializes our default options. All options processing is marge with the default arrays. This is how we add/remove options over time.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function set_option_defaults() {

		$this->_chat_options_defaults['page'] = array(
			'id'									=>	'',
			'blog_id'								=>	0,
			'session_type'							=>	'page',
			'session_status'						=>	'',
			//'blocked_ip_addresses_active'			=>	'enabled',
			'blocked_words_active'					=>	'disabled',

			'session_status_message'				=>	__('The Moderator has closed this chat session', $this->translation_domain),
			'session_cleared_message'				=> 	__('The Moderator has cleared the chat messages', $this->translation_domain),

			//'session_status_auto_close'			=>	'yes',
			'box_title'								=>	'',
			'box_width'								=>	'100%',
			'box_height'							=>	'500px',
			'box_sound'								=> 	'enabled',
			'box_input_position'					=>	'bottom',
			'box_font_family'						=> 	'',
			'box_font_size'							=> 	'',
			'box_text_color'						=>	'#000000',
			'box_background_color'					=> 	'#CCCCCC',
			'box_border_color'						=> 	'#CCCCCC',
			'box_border_width'						=> 	'1px',
			//'box_padding'							=>	'3px',
			'box_emoticons'							=> 	'disabled',
			//'buttonbar'								=> 	'disabled',
			'row_name_avatar'						=> 	'avatar',
			'row_avatar_width'						=>	'40px',
			'row_date'								=> 	'disabled',
			'row_date_format'						=>	get_option('date_format'),
			'row_time'								=> 	'disabled',
			'row_time_format'						=>	get_option('time_format'),
			'row_area_background_color'				=>	'#F9F9F9',
			'row_background_color'					=>	'#FFFFFF',
			'row_border_color'						=>	'#CCCCCC',
			'row_border_width'						=>	'1px',
			'row_spacing'							=>	'3px',

			'row_message_input_font_size'			=>	'',
			'row_message_input_font_family'			=>	'',
			'row_message_input_height'				=>	'45px',
			'row_message_input_length'				=>	'450',
			'row_message_input_background_color'	=>	'#FFFFFF',
			'row_message_input_text_color'			=>	'#000000',
			'row_date_text_color'					=> 	'#6699CC',
			'row_date_color'						=> 	'#FFFFFF',
			'row_name_color'						=> 	'#666666',
			'row_moderator_name_color'				=> 	'#6699CC',
			'row_text_color'						=> 	'#000000',
			'row_code_color'						=> 	'#FFFFCC',
			'row_font_family'						=> 	'',
			'row_font_size'							=> 	'',
			'log_creation'							=> 	'disabled',
			'log_display'							=> 	'disabled',
			'log_limit'								=> 	'100',
			//'log_purge'								=>	'',
			'login_options'							=> 	array('current_user', 'public_user'),
			'moderator_roles'						=> 	array('administrator'),
			'users_list_show'						=>	'avatar',
			'users_list_position'					=>	'none',
			'users_list_width'						=>	'25%',
			'users_list_avatar_width'				=>	'30px',
			'users_list_threshold_delete'			=>	'20',
			'users_list_background_color'			=>	'#FFFFFF',
			'users_list_name_color'					=>	'#000000',
			'users_list_font_family'				=> 	'',
			'users_list_font_size'					=> 	'',

			'tinymce_roles'							=> 	array(),
			'tinymce_post_types'					=> 	array(),

			'noauth_view'							=>	'default',
			'noauth_login_message'					=>	__('To get started just enter your email address and desired username:', $this->translation_domain),
			'noauth_login_prompt'					=>	__('You must login to participate in chat', $this->translation_domain)

		);

		$this->_chat_options_defaults['site'] = wp_parse_args(array(
				'session_type'						=>	'site',
				'bottom_corner'						=> 	'disabled',
				'bottom_corner_wpadmin'				=>	'disabled',
				'box_width'							=> 	'200px',
				'box_height'						=> 	'300px',
				'box_position_h'					=>	'right',
				'box_position_v'					=>	'bottom',
				'box_offset_h'						=>	'0px',
				'box_offset_v'						=>	'0px',
				'box_spacing_h'						=>	'10px',
				'box_shadow_show'					=>	'enabled',
				'box_shadow_v'						=>	'10px',
				'box_shadow_h'						=>	'10px',
				'box_shadow_blur'					=>	'5px',
				'box_shadow_spread'					=>	'0px',
				'box_shadow_color'					=>	'#888888',
				'box_resizable'						=>	'disabled',
				'users_list_show'					=>	'avatar',
				'users_list_position'				=>	'none',
				'log_creation'						=> 	'disabled',
				'log_display'						=> 	'disabled',
				'invite-info'						=>	array(),
				'blocked_on_shortcode'				=>	'disabled'

			), $this->_chat_options_defaults['page']
		);


		// Special section for Widgets. Based on the Page settings
		$this->_chat_options_defaults['widget'] = wp_parse_args(array(
				'session_type'						=>	'widget',
				'box_width'							=> 	'100%',
				'box_height'						=> 	'300px',
				'box_new_message_color'				=>	'#ff8400',
				'users_list_show'					=>	'avatar',
				'users_list_position'				=>	'none',
				'box_border_color'					=> 	'#4b96e2',
				//'box_padding'						=>	'2px',
				'box_border_width'					=> 	'2px',
				'row_avatar_width'					=>	'30px',
				'row_message_input_height'			=>	'35px',
				'log_creation'						=> 	'disabled',
				'log_display'						=> 	'disabled',
				'blocked_urls_action'				=>	'exclude',
				'blocked_urls'						=>	array(),
				'blocked_on_shortcode'				=>	'disabled'
			), $this->_chat_options_defaults['page']
		);

		$this->_chat_options_defaults['bp-group'] = wp_parse_args(array(
				'session_type'						=>	'bp-group',
				'box_width'							=> 	'100%',
				'box_height'						=> 	'400px',
				'bottom_corner'						=> 	'disabled',
				'users_list_show'					=>	'avatar',
				'users_list_position'				=>	'right',
				'users_list_width'					=>	'30%',
				'users_list_avatar_width'			=>	'50px',

				'row_message_input_height'			=>	'45px',

				'box_border_color'					=> 	'#4b96e2',
				'box_border_width'					=> 	'1px',
				'row_avatar_width'					=>	'30px',
				'row_message_input_height'			=>	'35px',
				'log_creation'						=> 	'disabled',
				'log_display'						=> 	'disabled',
				'blocked_urls_action'				=>	'exclude',
				'blocked_urls'						=>	array(),
			), $this->_chat_options_defaults['page']
		);

		$this->_chat_options_defaults['user-statuses'] = array(
				'available'							=>	__('Available', $this->translation_domain),
				'away'								=>	__('Away', $this->translation_domain),
				'offline'							=>	__('Offline', $this->translation_domain)
		);
		$this->_chat_options_defaults['user-statuses'] = apply_filters('wpmudev-chat-user-statuses', $this->_chat_options_defaults['user-statuses']);


//		$wp_upload_dir = wp_upload_dir();
//		if (isset($wp_upload_dir['basedir'])) {
//			$session_static_file_path = trailingslashit($wp_upload_dir['basedir']) . 'wpmudev_chat/logs';
//		} else {
//			$session_static_file_path = '';
//		}

		$this->_chat_options_defaults['global'] = array(
			'session_poll_interval_messages'		=>	'1',
			//'session_poll_interval_meta'			=>	'5',
			'session_poll_type'						=>	'plugin',
			'twitter_api_key'						=>	'',
			'twitter_api_secret'					=>	'',
			'google_plus_application_id'			=>	'',
			'facebook_application_id'				=>	'',
			'facebook_application_secret'			=>	'',
			'facebook_active_in_site'				=>	'',
			//'session_static_file_path'				=>	$session_static_file_path,
			'blocked_ip_addresses_active'			=>	'enabled',
			'blocked_ip_addresses'					=>	array('0.0.0.0'),
			'blocked_admin_urls_action'				=>	'exclude',
			'blocked_admin_urls'					=>	array(),
			'load_jscss_all'						=>	'enabled',
			'blocked_front_urls_action'				=>	'exclude',
			'blocked_front_urls'					=>	array(),
			'blocked_users'							=>	array(),
			'blocked_words_active'					=>	'disabled',
			'blocked_ip_message'					=>	__('Your account has been banned from participating in this chat session. Please contact site administrator for more information concerning this ban.', $this->translation_domain),
			'bp_menu_label'							=>	__('Group Chat', $this->translation_domain),
            'bp_menu_slug'							=>	'wpmudev-chat-bp-group',
			'bp_group_show_site'					=>	'enabled',
			'bp_group_admin_show_site'				=>	'enabled',
			'bp_group_show_widget'					=>	'enabled',
			'bp_group_admin_show_widget'			=>	'enabled',
			'bp_form_background_color'				=>	'#FDFDFD',
			'bp_form_label_color'					=>	'#333333',

		);

		$blocked_words = array();
		if (is_file(dirname(__FILE__).'/lib/bad_words_list.php')) {
			$blocked_words = file(dirname(__FILE__).'/lib/bad_words_list.php');
			if ((is_array($blocked_words)) && (count($blocked_words))) {
				foreach($blocked_words as $_idx => $_val) {
					$blocked_words[$_idx] = trim($_val);
				}
			}
		}

		$this->_chat_options_defaults['banned']['blocked_words_active'] 	= 'disabled';
		$this->_chat_options_defaults['banned']['blocked_words'] 			= $blocked_words;
		$this->_chat_options_defaults['banned']['blocked_words_replace'] 	= "";

		// User meta defaults for profile and other user specific settings. Saved to the user meta table (not wp_options)
		if ( is_user_logged_in() ) {
			$this->_chat_options_defaults['user_meta'] = wp_parse_args(get_option( 'wpmudev-chat-user-meta', array() ), array(
				'chat_user_status'		=>	'enabled',
				'chat_name_display'		=>	'display_name',
				'chat_wp_admin'			=>	'enabled',
				'chat_wp_toolbar'		=>	'enabled',
				'chat_users_listing'	=>	'disabled'
				)
			);

			if (has_filter('wpmudev-chat-options-defaults'))
				$this->_chat_options_defaults['user_meta'] = apply_filters('wpmudev-chat-options-defaults', 'user_meta', $this->_chat_options_defaults['user_meta']);
		}

		if (is_multisite()) {

			$this->_chat_options_defaults['network-site'] = wp_parse_args($this->_chat_options_defaults['site'], array(
				'bottom_corner'			=>	'disabled'
				)
			);
		}
	}

	/**
	 * Initialize the plugin
	 *
	 * @see		http://codex.wordpress.org/Plugin_API/Action_Reference
	 * @see		http://adambrown.info/p/wp_hooks/hook/init
	 */
	function init() {
		//global $bp;

		$this->check_upgrade();

		if (preg_match('/mu\-plugin/', PLUGINDIR) > 0) {
			load_muplugin_textdomain($this->translation_domain, dirname(plugin_basename(__FILE__)).'/languages/');
		} else {
			load_plugin_textdomain($this->translation_domain, false, dirname(plugin_basename(__FILE__)).'/languages/');
		}

		$this->load_configs();
		$this->chat_localized['settings'] 								= array();
		$this->chat_localized['settings']["ajax_url"] 					= site_url()."/wp-admin/admin-ajax.php";
		$this->chat_localized['settings']["plugin_url"] 				= plugins_url("/", __FILE__);
		$this->chat_localized['settings']["google_plus_text_sign_out"] 	= __('Sign out of Google+', $this->translation_domain);
		$this->chat_localized['settings']["facebook_text_sign_out"] 	= __('Sign out of Facebook', $this->translation_domain);
		$this->chat_localized['settings']["twitter_text_sign_out"] 		= __('Sign out of Twitter', $this->translation_domain);

		$this->chat_localized['settings']["please_wait"] 				= __('Please wait...', $this->translation_domain);
		$this->chat_localized['settings']["row_delete_text"]			= __('delete', $this->translation_domain);
		$this->chat_localized['settings']["row_undelete_text"]			= __('undelete', $this->translation_domain);

//		$this->chat_localized['settings']["cleared_message"]			= __('The moderator has cleared the chat messages.', $this->translation_domain);

		//$this->setup_session_logs();

		if ($this->get_option('twitter_api_key', 'global') != '') {
			$this->chat_localized['settings']["twitter_active"] = true;
		} else {
			$this->chat_localized['settings']["twitter_active"] = false;
		}

		if ($this->get_option('facebook_application_id', 'global') != '') {
			$this->chat_localized['settings']["facebook_app_id"] = $this->get_option('facebook_application_id', 'global');
			if ($this->get_option('facebook_active_in_site', 'global') == "no")
				$this->chat_localized['settings']["facebook_active"] = true;

		} else {
			$this->chat_localized['settings']["facebook_active"] = false;
		}

		if ($this->get_option('google_plus_application_id', 'global') != '') {
			$this->chat_localized['settings']["google_plus_active"] = true;
			$this->chat_localized['settings']["google_plus_application_id"] = $this->get_option('google_plus_application_id', 'global');
		} else {
			$this->chat_localized['settings']["google_plus_active"] = false;
		}

		$this->chat_localized['settings']["session_poll_interval_messages"]		= $this->get_option('session_poll_interval_messages', 'global');
		//$this->chat_localized['settings']["session_poll_interval_meta"]			= $this->get_option('session_poll_interval_meta', 'global');

		// Add our tool tips.
		if ( (function_exists('bp_is_group_admin_screen')) && ( bp_is_group_admin_screen( $this->get_option('bp_menu_slug', 'global'))) ) {
			if (!class_exists('WpmuDev_HelpTooltips'))
				require_once (dirname(__FILE__) . '/lib/class_wd_help_tooltips.php');
			$this->tips = new WpmuDev_HelpTooltips();
			$this->tips->set_icon_url(plugins_url('/images/information.png', __FILE__));
		}
	}

	/**
	 * Admin init logic. Things here will only run when viewing the admin area
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function admin_init() {
		$post_id = $post = $post_type = $post_type_object = null;
		$_show_tinymce_button = false;

		$url_path = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
		if (!$url_path)
			return $_show_tinymce_button;

		// If we are not on a post_type editor or add new form. Return
		if (!in_array($url_path, array('post-new.php','post.php')))
			return $_show_tinymce_button;

		if ( isset( $_GET['post_type']) ) {
			$post_type = $_GET['post_type'];
		} else {
			if ( isset( $_GET['post'] ) )
			 	$post_id = (int) $_GET['post'];
			elseif ( isset( $_POST['post_ID'] ) )
			 	$post_id = (int) $_POST['post_ID'];
			if ( $post_id ) {
				$post = get_post( $post_id );
				if ( $post ) {
					$post_type = $post->post_type;
				}
			}
		}
		if (!$post_type) {
			$post_type = "post";
		}

		if (!get_current_user_id())
			return $_show_tinymce_button;

		$current_user = wp_get_current_user();

		$tinymce_roles = $this->get_option('tinymce_roles', 'page' );
		if (!$tinymce_roles) $tinymce_roles = array();

		$tinymce_post_types = $this->get_option('tinymce_post_types', 'page');
		if (!$tinymce_post_types) $tinymce_post_types = array();

		// If the viewed post type is not in our allowed list return.
		if (!in_array($post_type, $tinymce_post_types))
			return $_show_tinymce_button;

		// The user's role is in the allowed roles set for Chat > Settings Page
		if (array_intersect($current_user->roles, $tinymce_roles)) {
			$_show_tinymce_button = true;
		} else {

			// If the allowed chat roles does not contain the admin then return;
			if (array_search('administrator', $tinymce_roles) === false) return;

			// However, if the 'administrator' role is in our allowed list and the user is super_admin then we are good.
			if (is_super_admin())
				$_show_tinymce_button = true;
		}

		if ($_show_tinymce_button === true) {
			add_filter("mce_external_plugins", array(&$this, "tinymce_add_plugin"));
			add_filter('mce_buttons', array(&$this,'tinymce_register_button'));
			//add_filter('mce_external_languages', array(&$this,'tinymce_load_langs'));

		}
		return $_show_tinymce_button;
	}

	function admin_enqueue_scripts() {
		// if we are showing one of our own settings panels then we don't need to be here since the
		if ($this->_show_own_admin === true) {

			wp_enqueue_style( 'farbtastic' );
			$this->_registered_styles['farbtastic'] = 'farbtastic';

			wp_enqueue_script( 'farbtastic' );
			$this->_registered_scripts['farbtastic'] = 'farbtastic';

			/* enqueue our plugin styles */
			wp_register_style( 'wpmudev-chat-admin-css', plugins_url( '/css/wpmudev-chat-admin.css', __FILE__ ),
				array(), $this->chat_current_version);
			$this->_registered_styles['wpmudev-chat-admin-css'] = 'wpmudev-chat-admin-css';

			wp_enqueue_script( 'jquery-cookie', plugins_url('/js/jquery-cookie.js', __FILE__), array('jquery'), $this->chat_current_version, true);
			$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';

			wp_enqueue_script( 'wpmudev-chat-admin-farbtastic-js', plugins_url('/js/wpmudev-chat-admin-farbtastic.js', __FILE__), array(), $this->chat_current_version, true);
			$this->_registered_scripts['wpmudev-chat-admin-farbtastic-js'] = 'wpmudev-chat-admin-farbtastic-js';

			wp_enqueue_script('wpmudev-chat-admin-js', plugins_url( '/js/wpmudev-chat-admin.js', __FILE__ ),
				array('jquery', 'jquery-ui-core', 'jquery-ui-tabs'), $this->chat_current_version);
			$this->_registered_styles['wpmudev-chat-admin-js'] = 'wpmudev-chat-admin-js';

			// The admin can even block chats from our own admin pages.
			//if (!wpmudev_chat_check_is_blocked_urls($this->get_option('blocked_admin_urls', 'global'),
			//					$this->get_option('blocked_admin_urls_action', 'global'))) {
			if ($this->_chat_plugin_settings['blocked_urls']['admin'] == false) {

				wp_register_style( 'wpmudev-chat-style', plugins_url('/css/wpmudev-chat-style.css', __FILE__), array(), $this->chat_current_version);
					$this->_registered_styles['wpmudev-chat-style'] = 'wpmudev-chat-style';

				wp_enqueue_script( 'wpmudev-chat-js', plugins_url('/js/wpmudev-chat.js', __FILE__), array('jquery'), $this->chat_current_version, true);
					$this->_registered_scripts['wpmudev-chat-js'] = 'wpmudev-chat-js';
			}

			if ((isset($this->user_meta['chat_wp_toolbar'])) && ($this->user_meta['chat_wp_toolbar'] == "enabled")) {
				wp_register_style( 'wpmudev-chat-wpadminbar-style', plugins_url( '/css/wpmudev-chat-wpadminbar.css', __FILE__ ), array(), $this->chat_current_version);
					$this->_registered_styles['wpmudev-chat-wpadminbar-style'] = 'wpmudev-chat-wpadminbar-style';
			}

			return;
		}

		// If we are blocking one of our URLs.
		//if (wpmudev_chat_check_is_blocked_urls($this->get_option('blocked_admin_urls', 'global'),
		//					$this->get_option('blocked_admin_urls_action', 'global'))) {
		if ($this->_chat_plugin_settings['blocked_urls']['admin'] == true) {
			return;
		}

		if ((isset($this->user_meta['chat_wp_admin'])) && ($this->user_meta['chat_wp_admin'] != "enabled")) {
			return;
		}

		if ((isset($this->user_meta['chat_wp_toolbar'])) && ($this->user_meta['chat_wp_toolbar'] == "enabled")) {
			wp_register_style( 'wpmudev-chat-wpadminbar-style', plugins_url( '/css/wpmudev-chat-wpadminbar.css', __FILE__ ), array(), $this->chat_current_version);
				$this->_registered_styles['wpmudev-chat-wpadminbar-style'] = 'wpmudev-chat-wpadminbar-style';
		}

		wp_register_style( 'wpmudev-chat-style', plugins_url('/css/wpmudev-chat-style.css', __FILE__), array(), $this->chat_current_version);
		$this->_registered_styles['wpmudev-chat-style'] = 'wpmudev-chat-style';

		wp_enqueue_script( 'jquery-cookie', plugins_url('/js/jquery-cookie.js', __FILE__), array('jquery'), $this->chat_current_version, true);
		$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';

		wp_enqueue_script( 'wpmudev-chat-js', plugins_url('/js/wpmudev-chat.js', __FILE__), array('jquery'), $this->chat_current_version, true);
		$this->_registered_scripts['wpmudev-chat-js'] = 'wpmudev-chat-js';
	}

	/**
	 * Enqueue all the scripts and styles we need. Per proper WP methods.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function wp_enqueue_scripts() {
		//global $bp;

		if (is_admin()) return;

		//$_SCRIPTS_LOADED = false;
		$_BLOCK_URL = false;

		// Why yes we are loading the scripts and styles for admin and non-admin pages. Because we are allowing chat to run under both!

		if ( (function_exists('bp_is_group_admin_screen')) && ( bp_is_group_admin_screen( $this->get_option('bp_menu_slug', 'global'))) ) {

			//$_SCRIPTS_LOADED = true;

			//wp_enqueue_style( 'farbtastic' );
			//$this->_registered_styles['farbtastic'] = 'farbtastic';

			wp_enqueue_script( 'jquery' );
			$this->_registered_scripts['jquery'] = 'jquery';

			//wp_enqueue_script( 'farbtastic' );
			//$this->_registered_scripts['farbtastic'] = 'farbtastic';

			wp_enqueue_script( 'jquery-ui-core' );
			$this->_registered_scripts['jquery-ui-core'] = 'jquery-ui-core';

			wp_enqueue_script( 'jquery-ui-tabs' );
			$this->_registered_scripts['jquery-ui-tabs'] = 'jquery-ui-tabs';

			wp_register_style( 'wpmudev-chat-style', plugins_url('/css/wpmudev-chat-style.css', __FILE__), array(), $this->chat_current_version);
			$this->_registered_styles['wpmudev-chat-style'] = 'wpmudev-chat-style';

			wp_register_style( 'wpmudev-chat-admin-css', plugins_url( '/css/wpmudev-chat-admin.css', __FILE__ ),
				array(), $this->chat_current_version);
			$this->_registered_styles[] = 'wpmudev-chat-admin-css';

			//wp_enqueue_script( 'jquery-cookie', plugins_url('/js/jquery-cookie.js', __FILE__), array('jquery'), $this->chat_current_version, true);
			//$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';

			wp_enqueue_script( 'wpmudev-chat-js', plugins_url('/js/wpmudev-chat.js', __FILE__), array('jquery'), $this->chat_current_version, true);
			$this->_registered_scripts['wpmudev-chat-js'] = 'wpmudev-chat-js';

			wp_enqueue_script( 'wpmudev-chat-admin-js', plugins_url( '/js/wpmudev-chat-admin.js', __FILE__ ), array('jquery'), $this->chat_current_version, true );
			$this->_registered_scripts['wpmudev-chat-admin-js'] = 'wpmudev-chat-admin-js';

			wp_enqueue_script( 'wpmudev-chat-admin-farbtastic-js', plugins_url('/js/wpmudev-chat-admin-farbtastic.js', __FILE__), array(), $this->chat_current_version, true);
			$this->_registered_scripts['wpmudev-chat-admin-farbtastic-js'] = 'wpmudev-chat-admin-farbtastic-js';


			if ($this->user_meta['chat_wp_toolbar'] == "enabled") {
				wp_register_style( 'wpmudev-chat-wpadminbar-style', plugins_url( '/css/wpmudev-chat-wpadminbar.css', __FILE__ ), array(), $this->chat_current_version);
				$this->_registered_styles['wpmudev-chat-wpadminbar-style'] = 'wpmudev-chat-wpadminbar-style';
			}

		} else {

			if ((isset($this->user_meta['chat_wp_toolbar'])) && ($this->user_meta['chat_wp_toolbar'] == "enabled")) {
				wp_register_style( 'wpmudev-chat-wpadminbar-style', plugins_url( '/css/wpmudev-chat-wpadminbar.css', __FILE__ ), array(), $this->chat_current_version);
				$this->_registered_styles['wpmudev-chat-wpadminbar-style'] = 'wpmudev-chat-wpadminbar-style';
			}

			wp_enqueue_script( 'jquery' );
			//wp_deregister_script('jquery');
			//wp_register_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js', false, null, false);

			if ($this->chat_localized['settings']["facebook_active"] === true) {
				$locale = get_locale();

				// We use 'facebook-all' to match our Ultimate Facebook plugin which enques the same script. Prevents enque duplication
				if (is_ssl()) {
					wp_enqueue_script('facebook-all', 'https://connect.facebook.net/'. $locale .'/all.js');
				} else {
					wp_enqueue_script('facebook-all', 'http://connect.facebook.net/'. $locale .'/all.js');
				}
				$this->_registered_scripts['facebook-all'] = 'facebook-all';
			}
			wp_register_style( 'wpmudev-chat-style', plugins_url('/css/wpmudev-chat-style.css', __FILE__), array(), $this->chat_current_version);
			$this->_registered_styles['wpmudev-chat-style'] = 'wpmudev-chat-style';

			wp_enqueue_script( 'wpmudev-chat-js', plugins_url('/js/wpmudev-chat.js', __FILE__), array('jquery'), $this->chat_current_version, true);
			$this->_registered_scripts['wpmudev-chat-js'] = 'wpmudev-chat-js';
		}
	}


	/**
	 * Special logic. We load two templates. One is the Twitter login popup. The second is the pop-out chat option. Both options pass query string parameters
	 * which are checked within this function. If we find a match we load the special template and exit.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function load_template() {

		if ( (isset($_GET['wpmudev-chat-action'])) && (!empty($_GET['wpmudev-chat-action'])) ) {

			switch ($_GET['wpmudev-chat-action']) {
				case 'pop-out':

					if ((isset($_GET['wpmudev-chat-key'])) && (!empty($_GET['wpmudev-chat-key']))) {

						$wpmudev_chat_key = base64_decode($_GET['wpmudev-chat-key']);
						$chat_session = get_transient($wpmudev_chat_key);
						if ((!empty($chat_session)) && (is_array($chat_session))) {
							$this->using_popup_out_template = true;
							if ( $wpmudev_chat_popup_template = locate_template( 'wpmudev-chat-pop-out-'. $chat_session['id'] .'.php' ) ) {
								load_template( $$wpmudev_chat_popup_template );
								die();
							} else if ( $wpmudev_chat_popup_template = locate_template( 'wpmudev-chat-pop-out.php' ) ) {
						   		load_template( $$wpmudev_chat_popup_template );
								die();
						 	} else {
								load_template( dirname( __FILE__ ) . '/templates/wpmudev-chat-pop-out.php' );
								die();
						 	}
						}
					}
					break;

				case 'pop-twitter':
					load_template( dirname( __FILE__ ) . '/templates/wpmudev-chat-pop-twitter-auth.php' );
					die();

					break;
			}
		}
	}

	/**
	 * Setup function for the static chat log file paths and security.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
/*
	function setup_session_logs() {
		$this->chat_localized['settings']['session_logs_path'] = trailingslashit($this->get_option('session_static_file_path', 'global'));
		if (!file_exists($this->chat_localized['settings']['session_logs_path'])) {
			wp_mkdir_p($this->chat_localized['settings']['session_logs_path']);
		}
		if (!is_writable($this->chat_localized['settings']['session_logs_path'])) {

			// Try updating the folder perms
			@ chmod( $this->chat_localized['settings']['session_logs_path'], 0775 );
			if (!is_writable($this->chat_localized['settings']['session_logs_path'])) {
				$this->chat_localized['settings']['session_logs_path'] = '';
			}
		}
		if (is_writable($this->chat_localized['settings']['session_logs_path'])) {

			if (!file_exists(trailingslashit($this->chat_localized['settings']['session_logs_path']) ."index.php")) {
				$fp = fopen(trailingslashit($this->chat_localized['settings']['session_logs_path']) ."index.php", "w+");
				if ($fp) {
					fwrite($fp, "<?php // Silence is golden. ?>");
					fclose($fp);
				}
			}
			if (!file_exists(trailingslashit($this->chat_localized['settings']['session_logs_path']) .".htaccess")) {
				$fp = fopen(trailingslashit($this->chat_localized['settings']['session_logs_path']) .".htaccess", "w+");
				if ($fp) {
					fwrite($fp, "IndexIgnore *\r\n");
					fclose($fp);
				}
			}
		}
	}
*/
	/**
	 * Here we use a class method to get options from our internal settings array. Similar to the WP get_option call.
	 *
	 * @global	none
	 * @param	$key - The options key.
	 * 			$session_type - This is the options group.
	 * @return	returns found value
	 */
	function get_option($key, $session_type = 'page') {
		if (isset($this->_chat_options[$session_type][$key])) {
			return $this->_chat_options[$session_type][$key];
		} else if (isset($this->_chat_options_defaults[$session_type][$key])) {
			return $this->_chat_options_defaults[$session_type][$key];
		}
	}

	/**
	 * Our footer output is busy. We need to output the logic for the bottom corner chat wrapper. This is a UL element and each chat box added is an <LI>
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function wp_footer() {

		// See $this->admin_footer() for admin footer logic
		if (is_admin()) return;

		$site_content = '';
		$site_content .= $this->chat_site_box_container();
		//$site_content .= $this->chat_network_site_box_container();

		if ($this->get_option('box_shadow_show', 'site') == "enabled") {

			$site_content .= '<style type="text/css">';
			$site_content .= 'div.wpmudev-chat-box.wpmudev-chat-box-site {
					box-shadow: '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_v', 'site'), array('px')) .' '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_h', 'site'), array('px')) .' '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_blur', 'site'), array('px')) .' '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_spread', 'site'), array('px')) .' '.
					$this->get_option('box_shadow_color', 'site') .' }';
			$site_content .= '</style>';
		}

		if (($this->_chat_plugin_settings['blocked_urls']['front'] != true)
		 || (count($this->chat_sessions) > 0) ) {

			if ($this->get_option('session_poll_type', 'global') == "plugin")
				$this->chat_localized['settings']["ajax_url"] 			= plugins_url( '/wpmudev-chat-ajax.php', __FILE__ );
			else
				$this->chat_localized['settings']["ajax_url"] 			= site_url()."/wp-admin/admin-ajax.php";

			$this->chat_localized['settings']['cookiepath'] 		= COOKIEPATH;
	        $this->chat_localized['settings']['cookie_domain'] 		= COOKIE_DOMAIN;
			$this->chat_localized['settings']['ABSPATH'] 			= base64_encode(ABSPATH);
	        //$this->chat_localized['settings']['soundManager-js'] 	= plugins_url('/js/soundmanager2-nodebug-jsmin.js', __FILE__);
	        $this->chat_localized['settings']['soundManager-js'] 	= plugins_url('/js/buzz.js', __FILE__);
	        $this->chat_localized['settings']['cookie-js'] 			= plugins_url('/js/jquery-cookie.js', __FILE__);

	        // Need to disable legacy setting.
			$this->chat_localized['settings']['box_resizable'] = false;

			//echo "chat_sessions<pre>"; print_r($this->chat_sessions); echo "</pre>";
			//echo "chat_user<pre>"; print_r($this->chat_user); echo "</pre>";

			$this->chat_localized['sessions'] 	= $this->chat_sessions;
			$this->chat_localized['user'] 		= $this->chat_user;
			$this->chat_localized['auth'] 		= $this->chat_auth;

			wp_localize_script('wpmudev-chat-js', 'wpmudev_chat_localized', $this->chat_localized);

			wp_print_styles(array_values($this->_registered_styles));
			wp_print_scripts( array_values($this->_registered_scripts) );

			//echo "scripts<pre>"; print_r($this->_registered_scripts); echo "</pre>";
		} else {
			foreach($this->_registered_styles as $_handle) {
				wp_dequeue_style($_handle);
			}
			foreach($this->_registered_scripts as $_handle) {
				wp_dequeue_script($_handle);
			}
		}

		if (!empty($site_content))
			echo $site_content;
	}

	function admin_footer() {

		if ((count($this->_registered_scripts)) && (isset($this->_registered_scripts['wpmudev-chat-js']))) {
			$site_content = '';
			$site_content .= $this->chat_site_box_container();
			//$site_content .= $this->chat_network_site_box_container();

			if ($this->get_option('box_shadow_show', 'site') == "enabled") {

				$site_content .= '<style type="text/css">';
				$site_content .= 'div.wpmudev-chat-box.wpmudev-chat-box-site {
						box-shadow: '.
						wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_v', 'site'), array('px')) .' '.
						wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_h', 'site'), array('px')) .' '.
						wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_blur', 'site'), array('px')) .' '.
						wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_spread', 'site'), array('px')) .' '.
						$this->get_option('box_shadow_color', 'site') .' }';
				$site_content .= '</style>';
			}

			echo $site_content;

			$this->chat_localized['settings']["ajax_url"] 			= plugins_url( '/wpmudev-chat-ajax.php', __FILE__ );
			$this->chat_localized['settings']['cookiepath'] 		= COOKIEPATH;
	        $this->chat_localized['settings']['cookie_domain'] 		= COOKIE_DOMAIN;
			$this->chat_localized['settings']['ABSPATH'] 			= base64_encode(ABSPATH);
	        $this->chat_localized['settings']['soundManager-js'] 	= plugins_url('/js/buzz.js', __FILE__);
	        $this->chat_localized['settings']['cookie-js'] 			= plugins_url('/js/jquery-cookie.js', __FILE__);

	        // Need to disable legacy setting.
			$this->chat_localized['settings']['box_resizable'] = false;

			$this->chat_localized['sessions'] 	= $this->chat_sessions;
			$this->chat_localized['user'] 		= $this->chat_user;
			$this->chat_localized['auth'] 		= $this->chat_auth;

			wp_localize_script('wpmudev-chat-js', 'wpmudev_chat_localized', $this->chat_localized);

			wp_print_styles(array_values($this->_registered_styles));
			wp_print_scripts( array_values($this->_registered_scripts) );

			//echo "_registered_scripts<pre>"; print_r($this->_registered_scripts); echo "</pre>";

		} else if ($this->_show_own_admin == true) {
			wp_print_styles(array_values($this->_registered_styles));
			wp_print_scripts( array_values($this->_registered_scripts) );
		}
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function network_admin_menu() {
//		return;

		// Planned global settings panel the network admin can control the default settings.

		// For example the bottom corner chat option can be set the enabled or disabled. Then all
		// new sites will inherit this option. Only if chat is network activated. Local activation
		// will not use site settings.

		if (!is_multisite()) return;
		if (!is_network_admin()) return;
		if (!is_super_admin()) return;

		if ( ! function_exists( 'is_plugin_active_for_network' ) )
		    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		if ( !is_plugin_active_for_network( 'wordpress-chat/wordpress-chat.php' ) )
			return;

		require( dirname(__FILE__) .'/lib/wpmudev_chat_admin_panels.php' );
		$this->_admin_panels = new wpmudev_chat_admin_panels( $this );

		$this->_pagehooks['chat_settings_panel_network_site'] = add_menu_page( 	_x("Chat", 'page label', $this->translation_domain),
						_x("Chat", 'menu label', $this->translation_domain),
						'manage_network_options',
						'chat_settings_panel_network_site',
						array($this->_admin_panels, 'chat_settings_panel_network_site')
						//plugin_dir_url( __FILE__ ) .'images/icon/greyscale-16.png'
		);

/*
		$this->_pagehooks['chat_settings_panel_network_page'] = add_submenu_page( 'chat_settings_panel',
					_x('Settings Page','page label', $this->translation_domain),
					_x('Settings Page', 'menu label', $this->translation_domain),
					'manage_network_options',
					'chat_settings_network_panel',
					array(&$this->_admin_panels, 'chat_settings_panel_page_network')
		);

		$this->_pagehooks['chat_settings_panel_network_site'] 	= add_submenu_page( 'chat_settings_panel',
					_x('Settings Network', 'page label', $this->translation_domain),
					_x('Settings Network', 'menu label', 'menu label', $this->translation_domain),
					'manage_network_options',
					'chat_settings_panel_network_site',
					array(&$this->_admin_panels, 'chat_settings_panel_network_site')
		);
*/
/*
		$this->_pagehooks['chat_settings_panel_widget'] 	= add_submenu_page( 'chat_settings_panel',
					_x('Settings Widget', 'page label', $this->translation_domain),
					_x('Settings Widget', 'menu label', 'menu label', $this->translation_domain),
					'manage_network_options',
					'chat_settings_panel_widget',
					array(&$this->_admin_panels, 'chat_settings_panel_widget')
		);
*/
/*
		$this->_pagehooks['chat_settings_panel_global'] 	= add_submenu_page( 'chat_settings_panel',
					_x('Settings Common', 'page label', $this->translation_domain),
					_x('Settings Common', 'menu label', 'menu label', $this->translation_domain),
					'manage_network_options',
					'chat_settings_panel_global',
					array(&$this->_admin_panels, 'chat_settings_panel_global')
		);
*/
		// Hook into the WordPress load page action for our new nav items. This is better then checking page query_str values.
		//add_action( 'load-'. $this->_pagehooks['chat_settings_panel_network_page'], 	array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['chat_settings_panel_network_site'], 	array(&$this, 'on_load_panels') );
		//add_action( 'load-'. $this->_pagehooks['chat_settings_panel_widget'], 	array(&$this, 'on_load_panels') );
		//add_action( 'load-'. $this->_pagehooks['chat_settings_panel_global'], 	array(&$this, 'on_load_panels') );
	}

	/**
	 * Standard function to create our admin menus
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 * @see		http://codex.wordpress.org/Adding_Administration_Menus
	 */
	function admin_menu() {

		require( dirname(__FILE__) .'/lib/wpmudev_chat_admin_panels.php' );
		$this->_admin_panels = new wpmudev_chat_admin_panels( $this );

		add_menu_page( 	_x("Chat", 'page label', $this->translation_domain),
						_x("Chat", 'menu label', $this->translation_domain),
						'manage_options',
						'chat_settings_panel',
						array($this->_admin_panels, 'chat_settings_panel_page')
						//plugin_dir_url( __FILE__ ) .'images/icon/greyscale-16.png'
		);

		$this->_pagehooks['chat_settings_panel_page'] = add_submenu_page( 'chat_settings_panel',
					_x('Settings Page','page label', $this->translation_domain),
					_x('Settings Page', 'menu label', $this->translation_domain),
					'manage_options',
					'chat_settings_panel',
					array(&$this->_admin_panels, 'chat_settings_panel_page')
		);

		$this->_pagehooks['chat_settings_panel_site'] 	= add_submenu_page( 'chat_settings_panel',
					_x('Settings Site', 'page label', $this->translation_domain),
					_x('Settings Site', 'menu label', 'menu label', $this->translation_domain),
					'manage_options',
					'chat_settings_panel_site',
					array(&$this->_admin_panels, 'chat_settings_panel_site')
		);

		$this->_pagehooks['chat_settings_panel_widget'] 	= add_submenu_page( 'chat_settings_panel',
					_x('Settings Widget', 'page label', $this->translation_domain),
					_x('Settings Widget', 'menu label', 'menu label', $this->translation_domain),
					'manage_options',
					'chat_settings_panel_widget',
					array(&$this->_admin_panels, 'chat_settings_panel_widget')
		);

		$this->_pagehooks['chat_settings_panel_global'] 	= add_submenu_page( 'chat_settings_panel',
					_x('Settings Common', 'page label', $this->translation_domain),
					_x('Settings Common', 'menu label', 'menu label', $this->translation_domain),
					'manage_options',
					'chat_settings_panel_global',
					array(&$this->_admin_panels, 'chat_settings_panel_global')
		);

/*
		$this->_pagehooks['chat_session_logs'] = add_submenu_page('chat_settings_panel',
					_x('Session Logs', 'page label', $this->translation_domain),
					_x('Session Logs', 'menu label', $this->translation_domain),
					'manage_options',
					'chat_session_logs',
					array(&$this->_admin_panels, 'chat_settings_panel_session_logs')
		);
*/
		// Hook into the WordPress load page action for our new nav items. This is better then checking page query_str values.
		add_action( 'load-'. $this->_pagehooks['chat_settings_panel_page'], 	array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['chat_settings_panel_site'], 	array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['chat_settings_panel_widget'], 	array(&$this, 'on_load_panels') );
		add_action( 'load-'. $this->_pagehooks['chat_settings_panel_global'], 	array(&$this, 'on_load_panels') );
//		add_action( 'load-'. $this->_pagehooks['chat_session_logs'], 			array(&$this, 'on_load_panels') );
	}

	/**
	 * Special function called when an of out admin pages are loaded. This way we can load any needed JS/CSS or processing logic
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function on_load_panels() {

		$this->_show_own_admin = true;

		/* These messages are displayed as part of the admin header message see 'admin_notices' WordPress action */
		$this->_messages['success-settings'] 			= __( "Settings have been update.", $this->translation_domain );

		if ((isset($_GET['page'])) && ($_GET['page'] == "chat_session_logs")) {

			if ((isset($_GET['action'])) && ($_GET['action'] == "details")) {

				$per_page = get_user_meta(get_current_user_id(), 'chat_page_chat_session_messages_per_page', true);
				if (!$per_page) $per_page = 20;

				if ((isset($_POST['wp_screen_options']['option']))
				 && ($_POST['wp_screen_options']['option'] == "chat_page_chat_session_logs_per_page")) {

					if (isset($_POST['wp_screen_options']['value'])) {
						$per_page = intval($_POST['wp_screen_options']['value']);
						if ((!$per_page) || ($per_page < 1)) {
							$per_page = 20;
						}
						update_user_meta(get_current_user_id(), 'chat_page_chat_session_messages_per_page', $per_page);
					}
				}
				add_screen_option( 'per_page', array('label' => __('per Page', $this->translation_domain ), 'default' => $per_page) );

			} else {
				$per_page = get_user_meta(get_current_user_id(), 'chat_page_chat_session_logs_per_page', true);
				if (!$per_page) $per_page = 20;

				if ((isset($_POST['wp_screen_options']['option']))
				 && ($_POST['wp_screen_options']['option'] == "chat_page_chat_session_logs_per_page")) {

					if (isset($_POST['wp_screen_options']['value'])) {
						$per_page = intval($_POST['wp_screen_options']['value']);
						if ((!$per_page) || ($per_page < 1)) {
							$per_page = 20;
						}
						update_user_meta(get_current_user_id(), 'chat_page_chat_session_logs_per_page', $per_page);
					}
				}
				add_screen_option( 'per_page', array('label' => __('per Page', $this->translation_domain ), 'default' => $per_page) );
			}
		}

		$this->process_panel_actions();
		$this->load_configs();

		include_once( dirname(__FILE__) . '/lib/wpmudev_chat_form_sections.php');

		// Since we are showing one of our admin panels we init the help system.
		include_once( dirname(__FILE__) . '/lib/wpmudev_chat_admin_panels_help.php' );
		wpmudev_chat_panel_help();

		// Add our tool tips.
		if (!class_exists('WpmuDev_HelpTooltips'))
			require_once (dirname(__FILE__) . '/lib/class_wd_help_tooltips.php');
		$this->tips = new WpmuDev_HelpTooltips();
		$this->tips->set_icon_url(plugins_url('/images/information.png', __FILE__));
	}

	/**
	 * Processing logic function. Called from on_load_pages above. This function handles the settings form submit filtering and storage of settings.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 * @see		http://codex.wordpress.org/Adding_Administration_Menus
	 */
	function process_panel_actions() {


		if (isset($_POST['chat_user_meta'])) {
			update_option( 'wpmudev-chat-user-meta', $_POST['chat_user_meta'] );
			$this->_chat_options_defaults['user_meta'] = $_POST['chat_user_meta'];

		}

		if (isset($_POST['chat'])) {

			//echo "_POST<pre>"; print_r($_POST); echo "</pre>";
			//die();

			if ( (!isset($_POST['wpmudev_chat_settings_save_wpnonce']))
			  || (!wp_verify_nonce($_POST['wpmudev_chat_settings_save_wpnonce'], 'wpmudev_chat_settings_save')) ) {
				return;
			}

			$chat_settings = $_POST['chat'];

			if (isset($chat_settings['section'])) {
				$chat_section = $chat_settings['section'];
				unset($chat_settings['section']);

				// Split off the Banned section since it goes into its own options key
				if ($chat_section == "global") {
					delete_transient( 'wpmudev-chat-global-data' );

					if (isset($chat_settings['banned'])) {
						$banned_section = $chat_settings['banned'];
						unset($chat_settings['banned']);

						// Need to convert the textarea list of words to an array for easier searching
						if (isset($banned_section['blocked_words'])) {
							$banned_section['blocked_words'] = split("\n", $banned_section['blocked_words']);
							foreach($banned_section['blocked_words'] as $_idx => $_val) {
								$_word = trim($_val);
								if (!empty($_word))
									$banned_section['blocked_words'][$_idx] = wp_kses($_word, '', '');
								else
									unset($banned_section['blocked_words'][$_idx]);
							}
							update_option('wpmudev-chat-banned', $banned_section);
							$this->_chat_options['banned'] = $banned_section;
						}
					}

					if (isset($chat_settings['blocked_ip_addresses'])) {
						$chat_settings['blocked_ip_addresses'] = split("\n", $chat_settings['blocked_ip_addresses']);
						foreach($chat_settings['blocked_ip_addresses'] as $_idx => $_val) {
							$_word = trim($_val);
							if (!empty($_word))
								$chat_settings['blocked_ip_addresses'][$_idx] = wp_kses($_word, '', '');
							else
								unset($chat_settings['blocked_ip_addresses'][$_idx]);
						}
					}

					if (isset($chat_settings['blocked_users'])) {
						$chat_settings['blocked_users'] = split("\n", $chat_settings['blocked_users']);
						foreach($chat_settings['blocked_users'] as $_idx => $_val) {
							$_word = trim($_val);
							if (!empty($_word))
								$chat_settings['blocked_users'][$_idx] = wp_kses($_word, '', '');
							else
								unset($chat_settings['blocked_users'][$_idx]);
						}
					}

					if (isset($chat_settings['blocked_admin_urls'])) {
						$chat_settings['blocked_admin_urls'] = split("\n", $chat_settings['blocked_admin_urls']);
						foreach($chat_settings['blocked_admin_urls'] as $_idx => $_val) {
							$_word = trim($_val);
							if (!empty($_word))
								$chat_settings['blocked_admin_urls'][$_idx] = wp_kses($_word, '', '');
							else
								unset($chat_settings['blocked_admin_urls'][$_idx]);
						}
					}

					if (isset($chat_settings['blocked_front_urls'])) {
						$chat_settings['blocked_front_urls'] = split("\n", $chat_settings['blocked_front_urls']);
						foreach($chat_settings['blocked_front_urls'] as $_idx => $_val) {
							$_word = trim($_val);
							if (!empty($_word))
								$chat_settings['blocked_front_urls'][$_idx] = wp_kses($_word, '', '');
							else
								unset($chat_settings['blocked_front_urls'][$_idx]);
						}
					}


				} else if (($chat_section == "page") || ($chat_section == "site") || ($chat_section == "widget")) {

					// Process the rest.
					if (!isset($chat_settings['login_options'])) {
						$chat_settings['login_options'] = array('current_user');
					} else if (!in_array('current_user', $chat_settings['login_options'])) {
						$chat_settings['login_options'][] = 'current_user';
					}

					if (!isset($chat_settings['moderator_roles'])) {
						$chat_settings['moderator_roles'] = array('administrator');
					} else if (!in_array('administrator', $chat_settings['moderator_roles'])) {
						$chat_settings['moderator_roles'][] = 'administrator';
					}

					if (isset($chat_settings['blocked_urls'])) {
						$chat_settings['blocked_urls'] = split("\n", $chat_settings['blocked_urls']);
						foreach($chat_settings['blocked_urls'] as $_idx => $_val) {
							$_word = trim($_val);
							if (!empty($_word))
								$chat_settings['blocked_urls'][$_idx] = wp_kses($_word, '', '');
							else
								unset($chat_settings['blocked_urls'][$_idx]);
						}
					}
				}

				foreach($chat_settings as $_idx => $_val) {
					if ($_idx == "login_options") {
						$chat_settings[$_idx] = $_val;
					} else if ($_idx == "moderator_roles") {
						$chat_settings[$_idx] = $_val;
					} else if (is_array($_val)) {
						$chat_settings[$_idx] = $_val;
					} else if (($_idx == "noauth_login_message") || ($_idx == "noauth_login_prompt")) {
						$args = array(
						    //formatting
						    'br' => array(),
							'strong' => array(),
						    'em'     => array(),
						    'b'      => array(),
						    'i'      => array(),
						);
						$chat_settings[$_idx] = wp_kses($_val, $args, '');
					} else {
						//$chat_settings[$_idx] = $_val;
						$chat_settings[$_idx] = wp_kses($_val, '', '');
					}
				}
				if (strncasecmp($chat_section, "network-", strlen("network-")) == 0) {
					update_site_option('wpmudev-chat-'. $chat_section, $chat_settings);

				} else {
					update_option('wpmudev-chat-'. $chat_section, $chat_settings);
				}
				$this->_chat_options[$chat_section] = $chat_settings;
			}
		}
	}

	/**
	 * This function is called when we init the TinyMCE hook from out init process. This function handles the needed logic to interface
	 * with the WP TinyMCE editor. Specifically, this function is called when the user clicks the chat button on the editor toolbar. This
	 * functin is the gateway to showing the popup settings window.
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function tinymce_options() {

		global $wp_version;

		// Enaueue the WordPress things
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'farbtastic' );

		wp_enqueue_script('tiny_mce_popup.js', includes_url() .'js/tinymce/tiny_mce_popup.js', array(), $wp_version);
		wp_enqueue_script('mctabs.js', includes_url() .'js/tinymce/utils/mctabs.js', array(), $wp_version);
		wp_enqueue_script('validate.js', includes_url() .'js/tinymce/utils/validate.js', array(), $wp_version);

		wp_enqueue_script('form_utils.js', includes_url() .'js/tinymce/utils/form_utils.js', array(), $wp_version);
		wp_enqueue_script('editable_selects.js', includes_url() .'js/tinymce/utils/editable_selects.js', array(), $wp_version);


		// Enqueue the Chat specific things
		wp_register_style( 'wpmudev-chat-admin-css', plugins_url( '/css/wpmudev-chat-admin.css', __FILE__ ),
			array(), $this->chat_current_version);
		$this->_registered_styles['wpmudev-chat-admin-css'] = 'wpmudev-chat-admin-css';

		wp_enqueue_script( 'jquery-cookie', plugins_url('/js/jquery-cookie.js', dirname(__FILE__)), array('jquery'), $this->chat_current_version);
		$this->_registered_scripts['jquery-cookie'] = 'jquery-cookie';

		wp_enqueue_script( 'wpmudev-chat-admin-js', plugins_url( '/js/wpmudev-chat-admin.js', __FILE__ ), array('jquery'), $this->chat_current_version, true );
		$this->_registered_scripts['wpmudev-chat-admin-js'] = 'wpmudev-chat-admin-js';

		wp_enqueue_script('wpmudev-chat-admin-tinymce-js', plugins_url( '/js/wpmudev-chat-admin-tinymce.js', __FILE__ ),
				array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'farbtastic'), $this->chat_current_version);
		$this->_registered_scripts['wpmudev-chat-admin-tinymce-js'] = 'wpmudev-chat-admin-tinymce-js';

		wp_enqueue_script( 'wpmudev-chat-admin-farbtastic-js', plugins_url('/js/wpmudev-chat-admin-farbtastic.js', __FILE__), array('jquery', 'farbtastic'), $this->chat_current_version, true);
		$this->_registered_scripts['wpmudev-chat-admin-farbtastic-js'] = 'wpmudev-chat-admin-farbtastic-js';

		// Add our tool tips.
		if (!class_exists('WpmuDev_HelpTooltips'))
			require_once (dirname(__FILE__) . '/lib/class_wd_help_tooltips.php');
		$this->tips = new WpmuDev_HelpTooltips();
		$this->tips->set_icon_url(plugins_url('/images/information.png', __FILE__));

		include_once( dirname(__FILE__) . '/lib/wpmudev_chat_form_sections.php');
		include_once( dirname(__FILE__) . '/lib/wpmudev_chat_admin_panels_help.php' );
		include_once( dirname(__FILE__) . '/lib/wpmudev_chat_admin_tinymce.php');
	}


	/**
	 * Called when the User profile is to be edited. This function adds some fields to the profile form specific to our chat plugin.
	 *
	 * @global	none
	 * @param	$user - This is the object of the user being edited.
	 * @return	none
	 */
	function chat_edit_user_profile( $user = '') {

		$this->load_configs();

		if (!$user) {
			global $current_user;
			$user = $current_user;
		}
		//echo "user<pre>"; print_r($user); echo "</pre>";

		$user_meta = get_user_meta( $user->ID, 'wpmudev-chat-user', true );
		$user_meta = wp_parse_args( $user_meta, $this->_chat_options_defaults['user_meta'] );
		//echo "profile user_meta<pre>"; print_r($user_meta); echo "</pre>";

		?>
	    <h3><?php _e('Chat Settings', $this->translation_domain); ?></h3>

	    <table class="form-table">
		    <tr>
		        <th><label for="wpmudev_chat_status"><?php _e('Set Chat status', $this->translation_domain); ?></label></th>
		        <td>
		            <select name="wpmudev_chat_user_settings[chat_user_status]" id="wpmudev_chat_status">
					<?php
						foreach($this->_chat_options['user-statuses'] as $status_key => $status_label) {
							if ($status_key == $user_meta['chat_user_status']) {
								$selected = ' selected="selected" ';
							} else {
								$selected = '';
							}

							?><option value="<?php echo $status_key;?>" <?php echo $selected; ?>><?php echo $status_label; ?></option><?php
						}
					?>
		            </select>
		        </td>
		    </tr>
		    <tr>
		        <th><label for="wpmudev_chat_name_display"><?php _e('In Chat Sessions show name as', $this->translation_domain); ?></label></th>
		        <td>
		            <select name="wpmudev_chat_user_settings[chat_name_display]" id="wpmudev_chat_name_display">
		            	<option value="display_name" <?php if ( $user_meta['chat_name_display'] == 'display_name' ) {
							echo ' selected="selected" '; } ?>><?php echo __('Display Name', $this->translation_domain). ": ". $user->display_name; ?></option>
		            	<option value="user_login" <?php if ( $user_meta['chat_name_display'] == 'user_login' ) {
							echo ' selected="selected" '; } ?>><?php echo __('User Login', $this->translation_domain). ": ". $user->user_login; ?></option>
		            </select>
		        </td>
		    </tr>
		    <tr>
		        <th><label for="wpmudev_chat_wp_admin"><?php _e('Show Chats within WPAdmin', $this->translation_domain); ?></label></th>
		        <td>
		            <select name="wpmudev_chat_user_settings[chat_wp_admin]" id="wpmudev_chat_wp_admin">
		            	<option value="enabled"<?php if ( $user_meta['chat_wp_admin'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
							_e('Enabled', $this->translation_domain); ?></option>
		            	<option value="disabled"<?php if ( $user_meta['chat_wp_admin'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
							_e('Disabled', $this->translation_domain); ?></option>
		            </select>
					<p class="description"><?php _e('This will disable all Chat functions including WordPress toolbar menu', $this->translation_domain); ?></p>
		        </td>
		    </tr>
		    <tr>
		        <th><label for="wpmudev_chat_wp_toolbar"><?php _e('Show Chat WordPress toolbar menu?', $this->translation_domain); ?></label></th>
		        <td>
		            <select name="wpmudev_chat_user_settings[chat_wp_toolbar]" id="wpmudev_chat_wp_toolbar">
		            	<option value="enabled"<?php if ( $user_meta['chat_wp_toolbar'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
							_e('Enabled', $this->translation_domain); ?></option>
		            	<option value="disabled"<?php if ( $user_meta['chat_wp_toolbar'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
							_e('Disabled', $this->translation_domain); ?></option>
		            </select>
		        </td>
		    </tr>

			<?php
			if (current_user_can('list_users')) { ?>
		    <tr>
		        <th><label for="wpmudev_chat_users_listing"><?php _e('Show Chat Status column on<br />Users > All Users listing?', $this->translation_domain); ?></label></th>
		        <td>
					<?php
					//echo "user_meta<pre>"; print_r($this->user_meta); echo "</pre>";
					?>

		            <select name="wpmudev_chat_user_settings[chat_users_listing]" id="wpmudev_chat_users_listing">
		            	<option value="enabled"<?php if ( $user_meta['chat_users_listing'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
							_e('Enabled', $this->translation_domain); ?></option>
		            	<option value="disabled"<?php if ( $user_meta['chat_users_listing'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
							_e('Disabled', $this->translation_domain); ?></option>
		            </select>
		        </td>
		    </tr>
			<?php }  ?>
	    </table>
	    <?php
	}

	/**
	 * Called when the User profile is saved. This function looks for our specific form fields added in the 'chat_edit_user_profile' function and adds those
	 * to the user's meta settings.
	 *
	 * @global	none
	 * @param	$user_id - This ID of the user profile we are saving.
	 * @return	none
	 */
	function chat_save_user_profile( $user_id = '') {
		if (!$user_id) return;
		if (!isset($_POST['wpmudev_chat_user_settings'])) return;

		if (isset($_POST['wpmudev_chat_user_settings']['chat_user_status'])) {
			$chat_user_status = esc_attr($_POST['wpmudev_chat_user_settings']['chat_user_status']);
			if (isset($this->_chat_options['user-statuses'][$chat_user_status])) {
				wpmudev_chat_update_user_status($user_id, $chat_user_status);
			}
			unset($_POST['wpmudev_chat_user_settings']['chat_user_status']);
		}

		$user_meta = get_user_meta( $user_id, 'wpmudev-chat-user', true);
		if (!$user_meta) $user_meta = array();

		$user_meta = wp_parse_args( $user_meta, $this->_chat_options_defaults['user_meta'] );
		$user_meta = wp_parse_args( $_POST['wpmudev_chat_user_settings'], $user_meta );

		update_user_meta( $user_id, 'wpmudev-chat-user', $user_meta );
	}


	/**
	 * Used when the user (admin) wants to add the chat status to all users on the Users > All Users listing table.
	 *
	 * @global	none
	 * @param	$columns - An array of columns used for the table output.
	 * @return	$columns - We add our unique columns array and return. This is a filter!
	 */
	function chat_manage_users_columns($columns) {

		if (current_user_can('list_users')) {

			//$wpmudev_chat_user_settings = get_user_meta( get_current_user_id(), 'wpmudev-chat-user', true);
			if ((isset($this->user_meta['chat_users_listing'])) && ($this->user_meta['chat_users_listing'] == "enabled")) {
				if (!isset($columns['wpmudev-chat-status'])) {
					$columns['wpmudev-chat-status'] = __('Chat Status', $this->translation_domain);
				}
			}
		}
		return $columns;
	}

	/**
	 * Used to display the custom column output for the user's row. This function works in coordination with the 'chat_manage_users_columns' function.
	 *
	 * @global	none
	 * @param	$output - will be blank.
	 * 			$column_name - Name (key) of the column we are to output. The key is set in the 'chat_manage_users_columns' function
	 * 			$friend_user_id - This is the ID of the user for the given row.
	 * @return	$output - Will be a complete output for this cell.
	 */
	function chat_manage_users_custom_column($output, $column_name, $friend_user_id) {

		if ((current_user_can('list_users')) && ($column_name == 'wpmudev-chat-status')) {

			if ($friend_user_id != get_current_user_id()) { // Make sure we don't check ourselves
				$output .= wpmudev_chat_get_chat_status_label(get_current_user_id(), $friend_user_id);
			}
		}
		return $output;
	}

	/**
	 * Process short code
	 *
	 * @global	object	$post
	 * @global	array	$chat_localized	Localized strings and options
	 * @return	string					Content
	 */
	function process_chat_shortcode($atts) {
		global $post, $current_user, $wpdb;

		if ((!isset($atts['id'])) || ($atts['id'] == '')) {

			if ((isset($post->ID)) && (intval($post->ID))
			 && (isset($post->post_type)) && (!empty($post->post_type))) {
				$atts['id'] = $post->post_type .'-'. $post->ID;
			} else {
				return;
			}
		}

		// Need to convert the shortcode string values for logion_options and moderator_roles to arrays. There are easier to work with.
		if ( (isset($atts['login_options'])) && (is_string($atts['login_options'])) && (strlen($atts['login_options'])) ) {
			$atts['login_options'] = explode(',', $atts['login_options']);
			if (count($atts['login_options'])) {
				foreach($atts['login_options'] as $_idx => $_val) {
					$atts['login_options'][$_idx] = trim($_val);
				}
			}
		}

		if ( (isset($atts['moderator_roles'])) && (is_string($atts['moderator_roles'])) && (strlen($atts['moderator_roles'])) ) {
			$atts['moderator_roles'] = explode(',', $atts['moderator_roles']);
			if (count($atts['moderator_roles'])) {
				foreach($atts['moderator_roles'] as $_idx => $_val) {
					$atts['moderator_roles'][$_idx] = trim($_val);
				}
			}
		}

		if ((isset($atts['session_type'])) && ($atts['session_type'] == 'site')) {
			$atts = $this->convert_config('site', $atts);
			$chat_session = wp_parse_args($atts, $this->_chat_options['site']);
			$chat_session['session_type'] = 'site';
		} else if ((isset($atts['session_type'])) && ($atts['session_type'] == 'private')) {
			$atts = $this->convert_config('site', $atts);
			$chat_session = wp_parse_args($atts, $this->_chat_options['site']);
			$chat_session['session_type'] 		= 'private';

			if (empty($atts['box_title']))
				$chat_session['box_title']			= __('Private', $this->translation_domain);

		} else if ((isset($atts['session_type'])) && ($atts['session_type'] == 'network-site')) {
			$atts = $this->convert_config('network-site', $atts);
			$chat_session = wp_parse_args($atts, $this->_chat_options['network-site']);
			$chat_session['session_type'] 		= 'network-site';

			if (empty($atts['box_title']))
				$chat_session['box_title']			= __('Network', $this->translation_domain);

		} else if ((isset($atts['session_type'])) && ($atts['session_type'] == 'widget')) {
			$atts = $this->convert_config('widget', $atts);
			$chat_session = wp_parse_args($atts, $this->_chat_options['widget']);
			$chat_session['session_type'] = 'widget';
		} else if ((isset($atts['session_type'])) && ($atts['session_type'] == 'bp-group')) {
			$chat_session = wp_parse_args($atts, $this->_chat_options['page']);
			$chat_session['session_type'] = 'bp-group';
		} else {
			$atts = $this->convert_config('page', $atts);
			$chat_session = wp_parse_args($atts, $this->_chat_options['page']);
			$chat_session['session_type'] = 'page';
		}


		//echo "atts<pre>"; print_r($atts); echo "</pre>";
		//echo "chat_session<pre>"; print_r($chat_session); echo "</pre>";

		$chat_session['id'] 		= $atts['id'];
		if ((is_multisite()) && ($chat_session['session_type'] == 'network-site')) {
			$chat_session['blog_id'] 	= 0;
		} else {
			$chat_session['blog_id'] 	= $wpdb->blogid;
		}


		// For login_options and moderator_roles we double check to make sure our default values are present
		if (!in_array('current_user', $chat_session['login_options']))
			$chat_session['login_options'][] = "current_user";

		if ($chat_session['session_type'] != "private") {
			if (!in_array('administrator', $chat_session['moderator_roles'])) {
				$chat_session['moderator_roles'][] = "administrator";
			}
		}

		// IF user is NOT logged in we check the no-auth display option
		//echo "chat_auth<pre>"; print_r($this->chat_auth); echo "</pre>";


		if (!isset($this->chat_auth['type'])) {

			// If we only have the 'current_user' in our options and the user is not authenticated then return
			if ( (array_search('current_user', $chat_session['login_options']) !== false) && (count($chat_session['login_options']) == 1) ) {
				return '';
			}
		} else {
			// Need to check the user.
			if ($this->chat_auth['type'] == "wordpress") {
				if (!is_user_logged_in()) {
					$this->chat_auth = array();
					$this->chat_auth['type'] = '';

					$this->chat_auth 					= array();
					$this->chat_auth['type'] 			= 'invalid';
				} else {
					if ( (is_multisite()) && (!is_super_admin()) ) {
						global $blog_id;

						$user_blogs = get_blogs_of_user($current_user->ID);
						//echo "blogid[". $blog_id ."] user_blogs<pre>"; print_r($user_blogs); echo "</pre>";

						if (!isset($user_blogs[$blog_id])) {
							// If we are allowing 'network' meaning users
							if (!in_array('network', $chat_session['login_options']))
								return false;
						}
					}
				}
			} else {
				//echo "chat_session<pre>"; print_r($chat_session); echo "</pre>";
				//echo "chat_auth->type=[". $this->chat_auth['type'] ."]<br />";
				//echo "session_type[". $chat_session['session_type'] ."] login_options<pre>"; print_r($chat_session['login_options']); echo "</pre>";
				if (!in_array($this->chat_auth['type'], $chat_session['login_options'])) {
					return false;
				}
			}
		}

		$box_font_style = "";
		if (!empty($chat_session['box_font_family'])) {
			if (isset($this->_chat_options_defaults['fonts_list'][$chat_session['box_font_family']])) {
				$box_font_style .= 'font-family: '. $this->_chat_options_defaults['fonts_list'][$chat_session['box_font_family']] .';';
			}
		}
		if (!empty($chat_session['box_font_size'])) {
			$box_font_style .= 'font-size: '. wpmudev_chat_check_size_qualifier($chat_session['box_font_size']) .';';
		}
		$chat_session['box_font_style'] = $box_font_style;

		$row_font_style = "";
		if (!empty($chat_session['row_font_family'])) {
			if (isset($this->_chat_options_defaults['fonts_list'][$chat_session['row_font_family']])) {
				$row_font_style .= 'font-family: '. $this->_chat_options_defaults['fonts_list'][$chat_session['row_font_family']] .';';
			}
		}
		if (!empty($chat_session['row_font_size'])) {
			$row_font_style .= 'font-size: '. wpmudev_chat_check_size_qualifier($chat_session['row_font_size']) .';';
		}
		$chat_session['row_font_style'] = $row_font_style;

		$row_message_input_font_style = "";
		if (!empty($chat_session['row_message_input_font_family'])) {
			if (isset($this->_chat_options_defaults['fonts_list'][$chat_session['row_message_input_font_family']])) {
				$row_message_input_font_style .= 'font-family: '. $this->_chat_options_defaults['fonts_list'][$chat_session['row_message_input_font_family']] .';';
			}
		}
		if (!empty($chat_session['row_message_input_font_size'])) {
			$row_message_input_font_style .= 'font-size: '. wpmudev_chat_check_size_qualifier($chat_session['row_message_input_font_size']) .';';
		}
		$chat_session['row_message_input_font_style'] = $row_message_input_font_style;

		$users_list_font_style = '';
		if (!empty($chat_session['users_list_font_family'])) {
			if (isset($this->_chat_options_defaults['fonts_list'][$chat_session['users_list_font_family']])) {
				$users_list_font_style .= 'font-family: '. $this->_chat_options_defaults['fonts_list'][$chat_session['users_list_font_family']] .';';
			}
		}
		if (!empty($chat_session['users_list_font_size'])) {
			$users_list_font_style .= 'font-size: '. wpmudev_chat_check_size_qualifier($chat_session['users_list_font_size']) .';';
		}
		$chat_session['users_list_font_style'] = $users_list_font_style;

		// Need to check all the input size type fields to make sure there is proper qualifiers (px, pt, em, %)

		$chat_session['box_width'] 					= wpmudev_chat_check_size_qualifier($chat_session['box_width']);
		$chat_session['box_height'] 				= wpmudev_chat_check_size_qualifier($chat_session['box_height']);
		$chat_session['box_border_width'] 			= wpmudev_chat_check_size_qualifier($chat_session['box_border_width']);
		//$chat_session['box_padding'] 				= wpmudev_chat_check_size_qualifier($chat_session['box_padding']);
		$chat_session['row_spacing'] 				= wpmudev_chat_check_size_qualifier($chat_session['row_spacing']);
		$chat_session['row_border_width'] 			= wpmudev_chat_check_size_qualifier($chat_session['row_border_width']);
		$chat_session['row_message_input_height'] 	= wpmudev_chat_check_size_qualifier($chat_session['row_message_input_height']);
		$chat_session['row_avatar_width']			= wpmudev_chat_check_size_qualifier($chat_session['row_avatar_width'], array('px'));

		$chat_session['users_list_width']			= wpmudev_chat_check_size_qualifier($chat_session['users_list_width'], array('px','%'));
		$chat_session['users_list_avatar_width']	= wpmudev_chat_check_size_qualifier($chat_session['users_list_avatar_width'], array('px'));

		$chat_session['row_message_input_length']	= intval($chat_session['row_message_input_length']);
		$chat_session['log_limit']					= intval($chat_session['log_limit']);

		if ($chat_session['log_limit'] == 0)
			$chat_session['log_limit'] = 100;

		// Enfore the user list threshold is not too low
		$chat_session['users_list_threshold_delete']	= intval($chat_session['users_list_threshold_delete']);
		if ($chat_session['users_list_threshold_delete'] < 20)
			$chat_session['users_list_threshold_delete'] = 20;

		if ($chat_session['users_list_position'] != "none") {
			if (($chat_session['users_list_position'] == "right") || ($chat_session['users_list_position'] == "left")) {

				if ($chat_session['users_list_position'] == "right")
					$chat_session['show_messages_position'] = "left";
				else if ($chat_session['users_list_position'] == "left")
					$chat_session['show_messages_position'] = "right";

				$user_list_width = intval($chat_session['users_list_width']);
				$chat_session['show_messages_width']	= 100 - $user_list_width ."%";
				$chat_session['users_list_width'] = intval($chat_session['users_list_width']) ."%";
			} else if (($chat_session['users_list_position'] == "above") || ($chat_session['users_list_position'] == "below")) {

				if ($chat_session['users_list_position'] == "above") {
					$chat_session['show_messages_position'] 	= "right";
				} else if ($chat_session['users_list_position'] == "below") {
					$chat_session['show_messages_position'] 	= "left";
				}
				$chat_session['users_list_width']			= "100%";
				$chat_session['show_messages_width']		= "100%";
			}

		} else {
			$chat_session['users_list_width']			= "0%";
			$chat_session['show_messages_position'] 	= "left";
			$chat_session['show_messages_width']		= "100%";
		}

		$chat_session['ip_address'] = (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];

		if (!isset($this->chat_user[$chat_session['id']]))
			$this->chat_user[$chat_session['id']] = $this->chat_user['__global__'];

		if (wpmudev_chat_is_moderator($chat_session))
			$chat_session['moderator'] = "yes";
		else
			$chat_session['moderator'] = "no";

		$chat_session['session_status'] = $this->chat_get_session_status($chat_session);

		if (!isset($this->chat_sessions[$chat_session['id']]))
			$this->chat_sessions[$chat_session['id']] = array();

		$this->chat_sessions[$chat_session['id']] = $chat_session;

		$content = '';

		// For most chats we simple add an empty div on page load. This empty div is populated by the 'init' AJAX call.
		// But for private chats we are already doing AJAX. So we build out the full chat box.
		if (($chat_session['session_type'] == "private") || ($chat_session['session_type'] == "network-site")) {
			$content = $this->chat_session_build_box($chat_session);
		}
		$content = $this->chat_box_container($chat_session, $content);

		$content .= $this->chat_logs_container($chat_session);

//		if ($this->using_popup_out_template == true)
//			$content .= $this->chat_session_box_styles($chat_session);
//		else if (($chat_session['session_type'] != 'site') && ($chat_session['session_type'] != 'private'))
//			$content .= $this->chat_session_box_styles($chat_session);
		$content .= $this->chat_session_box_styles($chat_session);



		// Set our transient for use on other sessions paths. Like on the pop-out option.
		//$transient_key = "chat-session-". $chat_session['blog_id'] ."-". $chat_session['id'] .'-'. $chat_session['session_type'];
		$transient_key = "chat-session-". $chat_session['id'] .'-'. $chat_session['session_type'];
		set_transient( $transient_key, $chat_session, 60*60*24 );

		return $content;
	}

	/**
	 * Displays the chat logs archive listing below the chat box.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the archives table. Will be echoed at some other point.
	 */
	function chat_logs_container($chat_session) {
		$content = '';

		if (($chat_session['log_display'] == 'enabled') && ($chat_session['session_type'] == "page")) {
			$chat_session_dates = $this->get_archives($chat_session);

			if ( ($chat_session_dates) && (is_array($chat_session_dates)) ) {
				krsort($chat_session_dates);

				$date_content = '';
				foreach ($chat_session_dates as $chat_session_date) {

					$query_args = array(
						'wpmudev-chat-log-id'	=>	$chat_session_date->id,
					);
					$archive_href 		= add_query_arg($query_args);

					$date_content .= '<li><a class="chat-log-link" style="text-decoration: none;" href="' . $archive_href . '">' .
						date_i18n(get_option('date_format') .' '.
							get_option('time_format'), strtotime($chat_session_date->start) + get_option('gmt_offset') * 3600, false) .
						' - ' . date_i18n(get_option('date_format') .' '.
							get_option('time_format'), strtotime($chat_session_date->end) + get_option('gmt_offset') * 3600, false) .
						'</a>';

					if (isset($_GET['wpmudev-chat-log-id']) && $_GET['wpmudev-chat-log-id'] == $chat_session_date->id) {
						$chat_session_log = $chat_session;
						$chat_session_log['session_type'] = "log";

						$chat_session['since'] 		= strtotime($chat_session_date->start);
						$chat_session['end'] 		= strtotime($chat_session_date->end);
						$chat_session['log_limit'] 	= 0;
						$chat_session['archived'] 	= array('yes');

						$chat_log_rows = $this->chat_session_get_messages($chat_session);

						if (($chat_log_rows) && (is_array($chat_log_rows)) && (count($chat_log_rows))) {
							$chat_rows_content = '';
							foreach ($chat_log_rows as $row) {
								$chat_rows_content .= $this->chat_session_build_row($row, $chat_session_log);
							}
							if (strlen($chat_rows_content)) {
								$date_content .= '<div class="wpmudev-chat-box"><div class="wpmudev-chat-module-messages-list" >'. $chat_rows_content .'</div></div>';
							}
						}
					}
					$date_content .= '</li>';
				}
				$content .= '<div id="wpmudev-chat-logs-wrap-'. $chat_session['id'].'" class="wpmudev-chat-logs-wrap"><p><strong>' .
					__('Chat Logs', $this->translation_domain) .
					'</strong></p><ul>' . $date_content . '</ul></div>';
			}
		}
		return $content;
	}

	/**
	 * Adds the CSS/Style output specific to the chat_session. Each chat_session can be different. So we need to output the CSS after each chat box with all the
	 * specifics for colors, fonts, widths, etc.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_box_styles($chat_session, $id_override = '') {
		$content = '';

		if (empty($id_override))
			$CSS_prefix = '#wpmudev-chat-box-'. $chat_session['id'];
		else
			$CSS_prefix = '.wpmudev-chat-box-'. $id_override;

		$content .= '<style type="text/css">';
		$content .= $CSS_prefix .' {
				height: '. $chat_session['box_height'] .';
				width: '. $chat_session['box_width'] .';
				color: '. $chat_session['box_text_color'] .';
				background-color: '. $chat_session['box_background_color'] .'; '. $chat_session['box_font_style'] .';
				border: '. $chat_session['box_border_width'] .' solid '. $chat_session['box_border_color'] .'; } ';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-header {
				background-color: '. $chat_session['box_border_color'] .'; } ';

/*
		if (($chat_session['session_type'] == "site") || ($chat_session['session_type'] == "private")) {
			$content .= $CSS_prefix .'.wpmudev-chat-session-new-messages {
					background-color: '. $chat_session['box_new_message_color'] .';
					border: '. $chat_session['box_border_width'] .' solid '. $chat_session['box_new_message_color'] .'; } ';
			$content .= $CSS_prefix .'.wpmudev-chat-session-new-messages div.wpmudev-chat-module-header {
							background-color: '. $chat_session['box_new_message_color'] .'; } ';
		}
*/
		if ($chat_session['users_list_position'] != "none") {

			$content .= $CSS_prefix .' div.wpmudev-chat-module-users-list {
				background-color: '. $chat_session['users_list_background_color'] .'; } ';
			if (($chat_session['users_list_position'] == "left") || ($chat_session['users_list_position'] == "right")) {
				$content .= $CSS_prefix .' div.wpmudev-chat-module-users-list {
					width: '. $chat_session['users_list_width'] .';
					float: '. $chat_session['users_list_position'] .'; } ';
			}
			if ($chat_session['users_list_show'] == "avatar") {

				$content .= $CSS_prefix .' div.wpmudev-chat-module-users-list ul li.wpmudev-chat-user a {
						width: '. $chat_session['users_list_avatar_width'].';
						height:'. $chat_session['users_list_avatar_width'] .';
						text-decoration: none;
						display: block; } ';

				$content .= $CSS_prefix .' div.wpmudev-chat-module-users-list ul li.wpmudev-chat-user a img {
						width: '. $chat_session['users_list_avatar_width'].';
						height:'. $chat_session['users_list_avatar_width'] .';
						border: 0;
						} ';


			} else if ($chat_session['users_list_show'] == "name") {
				$content .= $CSS_prefix .' div.wpmudev-chat-module-users-list ul li.wpmudev-chat-user a {
					color: '. $chat_session['users_list_name_color'] .';
					text-decoration: none;
					'. $chat_session['users_list_font_style'] .'; } ';
			}
		}
		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list {
				width: '. $chat_session['show_messages_width'] .';
				background-color: '. $chat_session['row_area_background_color'] .';
				float: '. $chat_session['show_messages_position'] .'; } ';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row {
				background-color:'. $chat_session['row_background_color'].';
				border-top:'. $chat_session['row_border_width'] .' solid '. $chat_session['row_border_color'].';
				border-bottom:'. $chat_session['row_border_width'] .' solid '. $chat_session['row_border_color'].';
				border-left: 0; border-right: 0;
				margin-bottom: '. $chat_session['row_spacing'] .'; } ';

		if ((isset($this->chat_auth['auth_hash'])) && (!empty($this->chat_auth['auth_hash']))) {
			$content .= $CSS_prefix .'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list div.wpmudev-chat-row-auth_hash-'. $this->chat_auth['auth_hash'].' ul.wpmudev-chat-row-footer  li.wpmudev-chat-user-invite, #wpmudev-chat-box-'. $chat_session['id'].'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list div.wpmudev-chat-row-auth_hash-'. $this->chat_auth['auth_hash'].' ul.wpmudev-chat-row-footer  li.wpmudev-chat-admin-actions-item-block-ip  { display:none; } ';
		}


//		if ($chat_session['row_name_avatar'] == "avatar") {
//			$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row {
//					min-height: '. $chat_session['row_avatar_width'] .'px; } ';
//		}


//		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row div.wpmudev-chat-row-avatar {
//			width: '. $chat_session['row_avatar_width'] .'px; height: '. $chat_session['row_avatar_width'] .'px; }';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row a.wpmudev-chat-user-avatar {
			display: block; width: '. $chat_session['row_avatar_width'] .'; height: '. $chat_session['row_avatar_width'] .'; }';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row a.wpmudev-chat-user-avatar img {
			border: 0; width: '. $chat_session['row_avatar_width'] .'; height: '. $chat_session['row_avatar_width'] .'; }';

		if (empty($chat_session['row_date_color']))
			$chat_session['row_date_color'] = $chat_session['row_background_color'];

		if ($chat_session['row_date'] == "enabled")	{
			if (!empty($chat_session['row_date_text_color']))	{
				$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row span.date {
					color: '. $chat_session['row_date_text_color'] .'; } ';
			}
			if (!empty($chat_session['row_date_color']))	{
				$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row span.date {
					background-color:'. $chat_session['row_date_color'] .'; } ';
			}
		}

		if ($chat_session['row_time'] == "enabled")	{
			if (!empty($chat_session['row_date_text_color']))	{
				$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row span.time {
					color: '. $chat_session['row_date_text_color'] .'; } ';
			}
			if (!empty($chat_session['row_date_color']))	{
				$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row span.time {
					background-color:'. $chat_session['row_date_color'] .'; } ';
			}
		}

		if ($chat_session['row_name_avatar'] == "name") {
			$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row a.wpmudev-chat-user-name {
				color: '. $chat_session['row_name_color'] .'; } ';

			$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row-moderator a.wpmudev-chat-user-name {
				color: '. $chat_session['row_moderator_name_color'] .'; } ';
		}
//		if ($chat_session['row_name_avatar'] == "name") {
//			$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row-avatar a.wpmudev-chat-user-avatar {
//				color: '. $chat_session['row_name_color'] .' } ';
//		}

		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row code {
				background-color:'. $chat_session['row_code_color'] .'; } ';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row p.wpmudev-chat-message {
				color:'. $chat_session['row_text_color'] .';
				'. $chat_session['row_font_style'] .'; } ';

		$content .= $CSS_prefix .' ul.wpmudev-chat-actions-menu ul.wpmudev-chat-actions-settings-menu {
				background-color: '. $chat_session['box_border_color'] .';
				border: 0px;
			}';

		if (!empty($chat_session['box_border_color'])) {
			$content .= $CSS_prefix .' ul.wpmudev-chat-actions-menu ul.wpmudev-chat-actions-settings-menu li {
				background-color: '. $chat_session['box_border_color'] .';
				border-left: 1px solid '. $chat_session['box_text_color'] .';
				border-right: 1px solid '. $chat_session['box_text_color'] .';
				border-bottom: 1px solid '. $chat_session['box_text_color'] .';
			}';
		}

		$content .= $CSS_prefix .' ul.wpmudev-chat-actions-menu ul.wpmudev-chat-actions-settings-menu a {
				color: '. $chat_session['box_text_color'] .';
				background-color: '. $chat_session['box_border_color'] .'
			}';

		$content .= $CSS_prefix .' ul.wpmudev-chat-actions-menu ul.wpmudev-chat-actions-settings-menu a:hover {
				color: '. $chat_session['box_border_color'] .';
				background-color: '. $chat_session['box_text_color'] .'
			}';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send {
				height: '. $chat_session['row_message_input_height'] .';
				background-color: '. $chat_session['row_message_input_background_color'] .';
				color: '. $chat_session['row_message_input_text_color'] .'; '. $chat_session['row_message_input_font_style'] .';
			}';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send::-webkit-input-placeholder {
			color: '. $chat_session['row_message_input_text_color'] .'; }';

		// set the placeholder text color to match the actual color for text.
		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send::-webkit-input-placeholder {
			color: '. $chat_session['row_message_input_text_color'] .'; }';
		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send:-moz-placeholder {
			color: '. $chat_session['row_message_input_text_color'] .'; }';
		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send::-moz-placeholder {
			color: '. $chat_session['row_message_input_text_color'] .'; }';
		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send:-ms-input-placeholder {
			color: '. $chat_session['row_message_input_text_color'] .'; }';


		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta {
				background-color: '. $chat_session['box_border_color'] .'; }';

		$content .= $CSS_prefix .' div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-emoticons ul.wpmudev-chat-emoticons-list {
			background-color: '. $chat_session['box_border_color'] .'; }';


		$content .= '</style>';

		return $content;
	}

	/**
	 * Adds the User list module to the chat box. The module is just a div container displayed within the outer chat box div
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_users_list_module($chat_session, $echo = false) {
		$content = '';

		$content_class = 'wpmudev-chat-module-users-list';
		$content_class .= ' wpmudev-chat-users-list-position-'. $chat_session['users_list_position'];
		$content_class .= ' wpmudev-chat-users-list-show-'. $chat_session['users_list_show'];

		$session_status_style = '';
		$content = $this->chat_session_module_wrap($chat_session, $content, $content_class, $session_status_style );

		return $content;
	}

	/**
	 * Adds the status module to the chat box. The module is just a div container displayed within the outer chat box div
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_status_module($chat_session) {

		$content = '';

		$content .= '<p class="chat-session-status-closed" style="text-align: center; font-weight:bold;">'. $chat_session['session_status_message'] .'</p>';

		$session_status_style = '';
		$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-session-status', $session_status_style );

		return $content;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_generic_message_module($chat_session) {
		$content = '';

		$content .= '<p style="text-align: center; font-weight:bold;"></p>';

		$session_status_style = 'display:none;';
		$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-session-generic-message', $session_status_style );

		return $content;
	}


	/**
	 * Adds the bannedstatus module to the chat box. The module is just a div container displayed within the outer chat box div
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_banned_status_module($chat_session) {

		$content = '';

		$content .= '<p class="chat-session-status-closed" style="text-align: center; font-weight:bold;">'.
			nl2br($this->get_option('blocked_ip_message', 'global')) .'</p>';

		$session_status_style = 'display:none;';

		$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-banned-status', $session_status_style );

		return $content;
	}

/*
	function chat_session_buttonbar_module($chat_session) {
		$content = '';

		if ($chat_session['buttonbar'] == 'enabled') {
			$content .= '<script type="text/javascript">edToolbar("wpmudev-chat-send-'. $chat_session['id']. '");</script>';
			$content = $this->chat_session_module_wrap($chat_session, $content);
		}
		return $content;
	}
*/

	/**
	 * Adds the status module to the chat box. The module is just a div container displayed within the outer chat box div
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_logout_module($chat_session) {
		$content = '';

		$content .= '<input type="button" value="'. __('Logout', $this->translation_domain) .'" name="chat-logout-submit"
		 	class="chat-logout-submit" id="chat-logout-submit-'.$chat_session['id'].'" />';

		$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-logout', 'display:none;');

		return $content;
	}

	/**
	 * Adds the message area module to the chat box. The module is just a div container displayed within the outer chat box div. The
	 * message area module contains the textarea as well as the footer buttons for emoticons, sound on/off and char count for entry limit.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_message_area_module($chat_session) {
		$content = '';

		//$content .= $this->chat_session_buttonbar_module($chat_session);
		//$content .= $this->chat_session_emoticons_module($chat_session);

		if (($chat_session['session_status'] != "open") && (!wpmudev_chat_is_moderator($chat_session)) ) {
			$display_style = ' style="display:none;" ';
		} else {
			$display_style = ' style="display:block;" ';
		}

		if (intval($chat_session['row_message_input_length']) > 0) {
			$textarea_max_length = ' maxlength="'. intval($chat_session['row_message_input_length']) .'" ';
		} else {
			$textarea_max_length = '';
		}

		//if ($chat_session['buttonbar'] == 'enabled') {
//		if ($chat_session['id'] == "bottom_corner") {
//			$content .= '<script type="text/javascript">
//				quicktags_settings_'.$chat_session['id'].' = {
//			        id : "wpmudev-chat-send-'. $chat_session['id'] .'",
//			        buttons: "strong,em,link,block"
//			    }
//
//			    quicktags(quicktags_settings_'.$chat_session['id'].');
//				</script>';
//		}

		$content .= '<textarea id="wpmudev-chat-send-'. $chat_session['id'] .'" class="wpmudev-chat-send" '. $textarea_max_length .' rows="5" placeholder="'. __('Type your message here', $this->translation_domain) .'"></textarea>';

		if (($chat_session['box_emoticons'] == "enabled") || ($chat_session['box_sound'] == "enabled") || (intval($chat_session['row_message_input_length']) > 0) ) {

			$content .= '<ul class="wpmudev-chat-send-meta">';

				/*
				if ($chat_session['session_type'] == "bp-group") {
					$chat_action_menu = $this->chat_session_settings_action_menu($chat_session);

					$content .= '<li class="wpmudev-chat-action-item wpmudev-chat-actions-settings" ><a href="#"><img src="'. plugins_url('/images/gear_icon.png', __FILE__) .'" alt="'. __('Chat Settings', $this->translation_domain) .'" class="wpmudev-chat-actions-settings-button" width="16" height="16" title="'. __('Chat Settings', $this->translation_domain).'" /></a>'. $chat_action_menu .'</li>';

				}
				*/

				if (intval($chat_session['row_message_input_length']) > 0) {
					$content .= '<li class="wpmudev-chat-send-input-length"><span class="wpmudev-chat-character-count">0</span>/'.
					 	intval($chat_session['row_message_input_length']) .'</li>';
				}

				if ($chat_session['box_sound'] == "enabled") {
					$content .= '<li class="wpmudev-chat-action-menu-item-sound-on"><a href="#" class="wpmudev-chat-action-sound" title="'.
						__('Turn chat sound of', $this->translation_domain) .'"><img height="16" width="16" src="'. plugins_url('/images/sound-on.png', __FILE__) .'" alt="'. __('Turn chat sound off', $this->translation_domain) .'" class="wpmudev-chat-sound-on" title="'. __('Turn chat sound off', $this->translation_domain).'" /></a></li>';

					$content .= '<li class="wpmudev-chat-action-menu-item-sound-off"><a href="#" class="wpmudev-chat-action-sound" title="'.
						__('Turn chat sound on', $this->translation_domain) .'"><img height="16" width="16" src="'. plugins_url('/images/sound-off.png', __FILE__) .'" alt="'. __('Turn chat sound on', $this->translation_domain) .'" class="wpmudev-chat-sound-off" title="'. __('Turn chat sound on', $this->translation_domain).'" /></a></li>';
				}


				if ($chat_session['box_emoticons'] == "enabled") {
					$smilies_list = array(
							':smile:',
							':grin:',
							':sad:',
							':eek:',
							':shock:',
							':???:',
							':cool:',
							':mad:',
							':razz:',
							':neutral:',
							':wink:',
							':lol:',
							':oops:',
							':cry:',
							':evil:',
							':twisted:',
							':roll:',
							':!:',
							':?:',
							':idea:',
							':arrow:');

					$content .= '<li class="wpmudev-chat-send-input-emoticons">';
					$content .= '<a class="wpmudev-chat-emoticons-menu" href="#">'. trim(convert_smilies($smilies_list[0])) .'</a>';
					$content .= '<ul class="wpmudev-chat-emoticons-list">';

					foreach ($smilies_list as $smilie) {
						$content .= '<li>'. convert_smilies($smilie) .'</li>';
					}
					$content .= '</ul>';
					$content .= '</li>';
				}

			$content .= '</ul>';
		}

		$container_style = '';
		$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-message-area', $container_style);
		return $content;
	}

	/**
	 * Adds the login module to the chat box. The module is just a div container displayed within the outer chat box div.
	 * The login module is a container for the public login form, as well as Facebook, Twitter and Google+ buttons
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_login_module($chat_session) {
		$content = '';

		if ( ($this->use_facebook_auth($chat_session)) || ($this->use_google_plus_auth($chat_session)) || ($this->use_twitter_auth($chat_session)) || ($this->use_public_auth($chat_session)) ) {

			$content .= $this->chat_login_public($chat_session);
			$content_auth = '';
			$twitter_login_button = $this->chat_login_twitter($chat_session);
			if (!empty($twitter_login_button)) {
				$content_auth .= '<span class="wpmudev-chat-login-button">'. $twitter_login_button .'</span>';
			}
			$google_login_button = $this->chat_login_google_plus($chat_session);
			if (!empty($google_login_button)) {
				$content_auth .= '<span class="wpmudev-chat-login-button">'. $google_login_button .'</span>';
			}

			$facebook_login_button = $this->chat_login_facebook($chat_session);
			if (!empty($facebook_login_button)) {
				$content_auth .= '<span class="wpmudev-chat-login-button">'. $facebook_login_button .'</span>';
			}

			if (!empty($content_auth)) {
				$content .= '<div class="login-message">'. __('Log in using:', $this->translation_domain) .'</div>';
				$content .= '<div class="chat-login-wrap">';
				$content .= $content_auth;
				$content .= '</div>';
			}

			$container_style = 'display:none;';

			$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-login', $container_style);

		}
		return $content;
	}

	/**
	 * Adds the login prompt module to the chat box. The module is just a div container displayed within the outer chat box div
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_login_prompt_module($chat_session) {
		$content = '';

		if (!empty($chat_session['noauth_login_prompt'])) {
			$content = '<p>'. $chat_session['noauth_login_prompt'] .'</p>';
			return $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-login-prompt');
		}
	}
	/**
	 * Adds the invite prompt module to the chat box. The module is just a div container displayed within the outer chat box div.
	 * The invite prompt module is used when one user initiates a private chat with another user. The invited user will see this
	 * invite prompt asking if they accept the invite.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_invite_prompt_module($chat_session) {
		$content = '';

		if (($chat_session['session_type'] == "private") && (!wpmudev_chat_is_moderator($chat_session))) {
			if (isset($chat_session['invite-info']['message']['host'])) {

				/*
				if (preg_match('/@/', $chat_session['invite-info']['message']['host']['avatar'])) {
					$avatar = get_avatar($chat_session['invite-info']['message']['host']['avatar'], intval($chat_session['row_avatar_width']), null, $chat_session['invite-info']['message']['host']['name']);
				} else {
					$avatar = '<img alt="'. $chat_session['invite-info']['message']['host']['name'] .'" height="'. intval($chat_session['row_avatar_width']) .'" src="'. $chat_session['invite-info']['message']['host']['avatar'] .'" class="wpmudev-chat-avatar photo" />';
				}
				*/
				if ((isset($chat_session['invite-info']['message']['host']['avatar'])) && (!empty($chat_session['invite-info']['message']['host']['avatar']))) {
					$avatar = '<img alt="'. $chat_session['invite-info']['message']['host']['name'] .'" height="'. intval($chat_session['row_avatar_width']) .'" src="'. $chat_session['invite-info']['message']['host']['avatar'] .'" class="wpmudev-chat-avatar photo" />';
				}

				$content .= '<p>'. $avatar .' '.  $chat_session['invite-info']['message']['host']['name'] .' '. __('has invited you to a private chat', $this->translation_domain) .'</p>';
			} else {
				$content .= '<p>'. __('You have been invited to private chat', $this->translation_domain) .'</p>';
			}

			$content .= '<p class="wpmudev-chat-invite-buttons"><button class="wpmudev-chat-invite-accept" type="button">'. __('Accept', $this->translation_domain) .'</button><button class="wpmudev-chat-invite-declined" type="button">'. __('Decline', $this->translation_domain) .'</button></p>';

			return $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-invite-prompt');
		}
	}

/*
	function chat_session_admin_buttons_module($chat_session) {
		$content = '';

		if (wpmudev_chat_is_moderator($chat_session['moderator_roles'])) {

			if ($chat_session['session_status'] == "open") {
				$content .= '<input type="button" value="'. __('Close Chat', $this->translation_domain) .'" name="chat-session-close"
					class="wpmudev-chat-session-status" id="wpmudev-chat-session-status-close-'.$chat_session['id'].'" />';
				$content .= '<input type="button" style="display: none;" value="'. __('Open Chat', $this->translation_domain) .'" name="chat-session-open" class="wpmudev-chat-session-status" id="wpmudev-chat-session-status-open-'.$chat_session['id'].'" />';
			} else {
				$content .= '<input type="button" style="display: none;" value="'. __('Close Chat', $this->translation_domain) .'" name="chat-session-close" class="wpmudev-chat-session-status" id="wpmudev-chat-session-status-close-'.$chat_session['id'].'" />';
				$content .= '<input type="button" value="'. __('Open Chat', $this->translation_domain) .'" name="chat-session-open" class="wpmudev-chat-session-status" id="chat-session-open-'.$chat_session['id'].'" />';
			}

			if ($chat_session['log_creation'] == 'enabled' && $chat_session['id'] != 1) {
				$content .= '<input type="button" value="'. __('Archive', $this->translation_domain) .'" name="chat-archive" class="chat-archive"
				 id="chat-archive-'. $chat_session['id'] .'" />';
			}

			$content .= '<input type="button" value="'. __('Clear', $this->translation_domain) .'" name="chat-clear" class="chat-clear"
				id="chat-clear-'. $chat_session['id'] .'" />';
			$content = $this->chat_session_module_wrap($chat_session, $content, 'wpmudev-chat-module-admin-buttons');
		}

		return $content;
	}
*/

	/**
	 * This is the main chat box outer container. From the chat_session settings various 'class' values are determined.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_box_container($chat_session, $content = '') {
		$chat_box_style = 'display:none;';
		$chat_box_class = "wpmudev-chat-box";

		$chat_box_class .= " wpmudev-chat-box-". $chat_session['session_type'];

		if (wpmudev_chat_is_moderator($chat_session)) {
			$chat_box_class .= " wpmudev-chat-box-moderator";
		}

		if ($this->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
			//$chat_box_class .= " wpmudev-chat-box-ip-address-". str_replace('.', '-', $chat_session['ip_address']);

			if ((!isset($this->_chat_options['global']['blocked_ip_addresses']))
			 || (empty($this->_chat_options['global']['blocked_ip_addresses'])) )
				$this->_chat_options['global']['blocked_ip_addresses'] = array();

			if ( (array_search($chat_session['ip_address'], $this->_chat_options['global']['blocked_ip_addresses']) !== false)
			 && (!wpmudev_chat_is_moderator($chat_session)) && ($chat_session['session_type'] != "private") ) {
				$chat_box_class .= " wpmudev-chat-session-ip-blocked";
			}
		}

		if (($chat_session['session_type'] == "private") || ($chat_session['session_type'] == "network-site")) {
			// For private chat box we also add the 'wpmudev-chat-box-site' for processing and CSS purpose
			$chat_box_class .= " wpmudev-chat-box-site";

			if (wpmudev_chat_is_moderator($chat_session)) {
				$chat_box_class .= " wpmudev-chat-box-invite-accepted";
			} else {
				$chat_box_class .= " wpmudev-chat-box-invite-". $chat_session['invite-info']['message']['invite-status'];
			}
		}

		if ($this->chat_user[$chat_session['id']]['status_max_min'] == "max") {
			$chat_box_class .= " wpmudev-chat-box-max";
		} else {
			$chat_box_class .= " wpmudev-chat-box-min";
		}

		if ($chat_session['box_sound'] == "enabled") {

			if ($this->chat_user[$chat_session['id']]['sound_on_off'] == "on")
				$chat_box_class .= " wpmudev-chat-box-sound-on";
			else
				$chat_box_class .= " wpmudev-chat-box-sound-off";
		}

		if ($chat_session['session_status'] == "open") {
			$chat_box_class .= " wpmudev-chat-session-open";
		} else {
			$chat_box_class .= " wpmudev-chat-session-closed";
		}

		if ($chat_session['session_type'] == "log") {
			$content = '<div id="wpmudev-chat-box-'.$chat_session['id'].'" style="'. $chat_box_style .'" class="'. $chat_box_class. '">'. $content .'</div>';
		} else {
			$content = '<div id="wpmudev-chat-box-'.$chat_session['id'].'" style="'. $chat_box_style .'" class="'. $chat_box_class. '">'. $content .'</div>';
		}

		return $content;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_site_box_container($content = '') {
		global $bp;

		// We don't want to add the container on our pop-out template.
		if ($this->using_popup_out_template == true) return;

		$site_chat_box = '';

		if (!is_admin()) {
			if ($this->get_option('bottom_corner', 'site') == 'enabled')
				$_SHOW_SITE_CHAT = true;
			else
				$_SHOW_SITE_CHAT = false;

			if ($_SHOW_SITE_CHAT == true) {

				// Are we viewing a BuddyPress Group pages?
				if ((isset($bp->groups->current_group->id)) && (intval($bp->groups->current_group->id))) {

					// Are we viewing the Group Admin screen?
					$bp_group_admin_url_path 	= parse_url(bp_get_group_admin_permalink($bp->groups->current_group), PHP_URL_PATH);
					$request_url_path 			= parse_url(get_option('siteurl') . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

					if ( (!empty($request_url_path)) && (!empty($bp_group_admin_url_path))
				  	  && (substr($request_url_path, 0, strlen($bp_group_admin_url_path)) == $bp_group_admin_url_path) ) {
						if ($this->get_option('bp_group_admin_show_site', 'global') != "enabled") {
							$_SHOW_SITE_CHAT = false;
						}
					} else {
						if ($this->get_option('bp_group_show_site', 'global') != "enabled") {
							$_SHOW_SITE_CHAT = false;
						}
					}
				} else {
					if ($this->_chat_plugin_settings['blocked_urls']['site'] == true) {
						$_SHOW_SITE_CHAT = false;
					} else {
						if ($this->get_option('blocked_on_shortcode', 'site') == 'enabled') {
							global $post;
							if ((!empty($post->post_content)) && (strstr($post->post_content, '[chat ') !== false)) {
								$_SHOW_SITE_CHAT = false;
							}
						}
					}
				}
			}

		} else {
			if ($this->get_option('bottom_corner_wpadmin', 'site') == 'enabled')
				$_SHOW_SITE_CHAT = true;
			else
				$_SHOW_SITE_CHAT = false;

			//echo "blocked_urls<pre>"; print_r($this->_chat_plugin_settings['blocked_urls']); echo "</pre>";
			if ($this->_chat_plugin_settings['blocked_urls']['admin'] == true)
				$_SHOW_SITE_CHAT = false;

			if ($this->_chat_plugin_settings['blocked_urls']['site'] == true)
				$_SHOW_SITE_CHAT = false;
		}

		if ($_SHOW_SITE_CHAT == true) {
			$atts = array(
				'id' 					=> 'bottom_corner',
				'session_type'			=> 'site'
			);

			$atts = wp_parse_args( $atts, $this->_chat_options['site'] );
			$content .= $this->process_chat_shortcode($atts);
		}
		$site_box_height 		= wpmudev_chat_check_size_qualifier($this->get_option('box_height', 'site'), array('px'));
		$site_box_position_v 	= $this->get_option('box_position_v', 'site');
		$site_box_position_h 	= $this->get_option('box_position_h', 'site');
		$site_box_offset_v 		= wpmudev_chat_check_size_qualifier($this->get_option('box_offset_v', 'site'), array('px'));
		$site_box_offset_h 		= wpmudev_chat_check_size_qualifier($this->get_option('box_offset_h', 'site'), array('px'));
		$site_box_spacing_h 	= wpmudev_chat_check_size_qualifier($this->get_option('box_spacing_h', 'site'), array('px'));

		if ($site_box_position_h == "left")
			$site_box_spacing = "0 ". $site_box_spacing_h .' 0 0';
		else
			$site_box_spacing = "0 0 0 ". $site_box_spacing_h .'';

		$site_box_float 		= $site_box_position_h;
		$site_box_position_h 	= $site_box_position_h.': '. $site_box_offset_h .';';

		$height_offset = 0;
		if ($site_box_position_v == "bottom") {
			$border_width = intval($this->get_option('box_border_width', 'site'));
			if ($border_width > 0) {
				$height_offset = wpmudev_chat_check_size_qualifier($border_width*2, array('px'));
			}
		}

		$site_box_position_v 	= $site_box_position_v.': '. $site_box_offset_v .';';

		$content .= '<style type="text/css">';
		$content .= 'div.wpmudev-chat-box.wpmudev-chat-box-site {  margin: 0; padding: 0;
			position: fixed; '. $site_box_position_h .' '. $site_box_position_v .' z-index: 10000;  margin: '. $site_box_spacing .'; padding: 0; } ';

		$content .= '</style>';

		//$content .= $this->chat_session_box_styles($this->_chat_options['site'], 'site');
		//$content .= $this->chat_session_box_styles($this->_chat_options['site'], 'private');

		return $content;
	}

	function chat_network_site_box_container($content = '') {
		global $bp;

		// We don't want to add the container on our pop-out template.
		if ($this->using_popup_out_template == true) return;

		$site_chat_box = '';

		if (!is_admin()) {
			if ($this->get_option('bottom_corner', 'network-site') == 'enabled')
				$_SHOW_SITE_CHAT = true;
			else
				$_SHOW_SITE_CHAT = false;

			if ($_SHOW_SITE_CHAT == true) {

				// Are we viewing a BuddyPress Group pages?
				if ((isset($bp->groups->current_group->id)) && (intval($bp->groups->current_group->id))) {

					// Are we viewing the Group Admin screen?
					$bp_group_admin_url_path 	= parse_url(bp_get_group_admin_permalink($bp->groups->current_group), PHP_URL_PATH);
					$request_url_path 			= parse_url(get_option('siteurl') . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

					if ( (!empty($request_url_path)) && (!empty($bp_group_admin_url_path))
				  	  && (substr($request_url_path, 0, strlen($bp_group_admin_url_path)) == $bp_group_admin_url_path) ) {
						if ($this->get_option('bp_group_admin_show_site', 'global') != "enabled") {
							$_SHOW_SITE_CHAT = false;
						}
					} else {
						if ($this->get_option('bp_group_show_site', 'global') != "enabled") {
							$_SHOW_SITE_CHAT = false;
						}
					}
				} else {
					if ($this->_chat_plugin_settings['blocked_urls']['site'] == true) {
						$_SHOW_SITE_CHAT = false;
					} else {
						if ($this->get_option('blocked_on_shortcode', 'site') == 'enabled') {
							global $post;
							if ((!empty($post->post_content)) && (strstr($post->post_content, '[chat ') !== false)) {
								$_SHOW_SITE_CHAT = false;
							}
						}
					}
				}
			}

		} else {
			if ($this->get_option('bottom_corner_wpadmin', 'site') == 'enabled')
				$_SHOW_SITE_CHAT = true;
			else
				$_SHOW_SITE_CHAT = false;

			//echo "blocked_urls<pre>"; print_r($this->_chat_plugin_settings['blocked_urls']); echo "</pre>";
			if ($this->_chat_plugin_settings['blocked_urls']['admin'] == true)
				$_SHOW_SITE_CHAT = false;

			if ($this->_chat_plugin_settings['blocked_urls']['site'] == true)
				$_SHOW_SITE_CHAT = false;
		}

		if ($_SHOW_SITE_CHAT == true) {
//			$atts = array(
//				'id' 					=> 'bottom_corner',
//				'session_type'			=> 'site'
//			);
//
//			$atts = wp_parse_args( $atts, $this->_chat_options['site'] );
//			$content .= $this->process_chat_shortcode($atts);

			$site_box_height 		= wpmudev_chat_check_size_qualifier($this->get_option('box_height', 'site'), array('px'));
			$site_box_position_v 	= $this->get_option('box_position_v', 'site');
			$site_box_position_h 	= $this->get_option('box_position_h', 'site');
			$site_box_offset_v 		= wpmudev_chat_check_size_qualifier($this->get_option('box_offset_v', 'site'), array('px'));
			$site_box_offset_h 		= wpmudev_chat_check_size_qualifier($this->get_option('box_offset_h', 'site'), array('px'));
			$site_box_spacing_h 	= wpmudev_chat_check_size_qualifier($this->get_option('box_spacing_h', 'site'), array('px'));

			if ($site_box_position_h == "left")
				$site_box_spacing = "0 ". $site_box_spacing_h .' 0 0';
			else
				$site_box_spacing = "0 0 0 ". $site_box_spacing_h .'';

			$site_box_float 		= $site_box_position_h;
			$site_box_position_h 	= $site_box_position_h.': '. $site_box_offset_h .';';

			$height_offset = 0;
			if ($site_box_position_v == "bottom") {
				$border_width = intval($this->get_option('box_border_width', 'site'));
				if ($border_width > 0) {
					$height_offset = wpmudev_chat_check_size_qualifier($border_width*2, array('px'));
				}
			}

			$site_box_position_v 	= $site_box_position_v.': '. $site_box_offset_v .';';

			$content .= '<style type="text/css">';
			$content .= 'div.wpmudev-chat-box.wpmudev-chat-box-site {  margin: 0; padding: 0;
				position: fixed; '. $site_box_position_h .' '. $site_box_position_v .' z-index: 10000;  margin: '. $site_box_spacing .'; padding: 0; } ';

			$content .= '</style>';



			if ((is_multisite()) && ($this->get_option('bottom_corner', 'network-site') == "enabled")) {
				$atts = array(
					'id' 					=> 'network-site',
					'session_type'			=> 'network-site'
				);

				$atts = wp_parse_args( $atts, $this->_chat_options['network-site'] );
				$content .= $this->process_chat_shortcode($atts);

				$content .= '<style type="text/css">';


				echo "site_box_position_h=[". $site_box_position_h."]<br />";
				echo "site_box_offset_v=[". $site_box_offset_v."]<br />";
				echo "site_box_spacing=[". $site_box_spacing."]<br />";

				if ($site_box_position_h == "left")
					$content .= 'div#wpmudev-chat-box-network-site {  right: 225px; } ';
				else
					$content .= 'div#wpmudev-chat-box-network-site {  right: 225px; } ';

				$content .= '</style>';

			}
		}

		//$content .= $this->chat_session_box_styles($this->_chat_options['site'], 'site');
		//$content .= $this->chat_session_box_styles($this->_chat_options['site'], 'private');

		if ($this->get_option('box_shadow_show', 'site') == "enabled") {

			$content .= '<style type="text/css">';
			$content .= 'div.wpmudev-chat-box.wpmudev-chat-box-site {
					box-shadow: '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_v', 'site'), array('px')) .' '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_h', 'site'), array('px')) .' '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_blur', 'site'), array('px')) .' '.
					wpmudev_chat_check_size_qualifier($this->get_option('box_shadow_spread', 'site'), array('px')) .' '.
					$this->get_option('box_shadow_color', 'site') .' }';
			$content .= '</style>';
		}

		return $content;
	}

	/**
	 * Adds the header module to the chat box. The module is just a div container displayed within the outer chat box div.
	 * The header module contains the top bar for the chat which include the title and gear/settings action menu.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_box_header_container($chat_session) {
		$content = '';

		$chat_header_images = '';
		if (!isset($this->chat_user[$chat_session['id']])) return $content;

		if ($this->chat_user[$chat_session['id']]['status_max_min'] == "max") {
			$chat_style_min = "display:block;";
			$chat_style_max = "display:none;";
		} else {
			$chat_style_min = "display:none;";
			$chat_style_max = "display:block;";
		}

		$chat_action_menu = $this->chat_session_settings_action_menu($chat_session);

		$chat_header_actions = '<ul class="wpmudev-chat-actions-menu">';

		if ($chat_session['session_type'] != "bp-group") {
			$chat_header_images .= '<img class="wpmudev-chat-min" src="'. plugins_url('/images/16-square-blue-remove.png', __FILE__)  .'" alt="-" width="16" height="16" style="'. $chat_style_min .'" title="'. __('Minimize Chat', $this->translation_domain) .'" />';
			$chat_header_images .= '<img class="wpmudev-chat-max" src="'. plugins_url('/images/16-square-green-add.png', __FILE__) .'" alt="+" width="16" height="16" style="'. $chat_style_max .'" title="'. __('Maximize Chat', $this->translation_domain) .'" />';
			$chat_header_actions .= '<li class="wpmudev-chat-action-item wpmudev-chat-min-max"><a href="#">'. $chat_header_images .'</a></li>';
		}
		if ($this->chat_user[$chat_session['id']]['status_max_min'] != "max")
			$chat_style_settings = "display:none;";
		else
			$chat_style_settings = '';

		$chat_header_actions .= '<li class="wpmudev-chat-action-item wpmudev-chat-actions-settings" style="'. $chat_style_settings .'"><a href="#" class="wpmudev-chat-actions-settings-button"><img src="'. plugins_url('/images/gear_icon.png', __FILE__) .'" alt="'. __('Chat Settings', $this->translation_domain) .'" width="16" height="16" title="'. __('Chat Settings', $this->translation_domain).'" /></a>'. $chat_action_menu .'</li>';

		//$transient_key = "chat-session-". $chat_session['blog_id'] ."-". $chat_session['id'] .'-'. $chat_session['session_type'];
		$transient_key = "chat-session-". $chat_session['id'] .'-'. $chat_session['session_type'];

		if ($chat_session['session_type'] != "bp-group") {
			$chat_header_actions .= '<li class="wpmudev-chat-action-item wpmudev-chat-actions-settings-pop-out"><a title="'. __('Pop out', $this->translation_domain) .'" href="'. add_query_arg( array('wpmudev-chat-action' => 'pop-out', 'wpmudev-chat-key' => base64_encode($transient_key) ), get_option('siteurl')) .'" class="wpmudev-chat-action-pop-out">&#x25B2;</a></li>';
			$chat_header_actions .= '<li class="wpmudev-chat-action-item wpmudev-chat-actions-settings-pop-in"><a title="'. __('Pop in', $this->translation_domain) .'" href="'. add_query_arg( array('wpmudev-chat-action' => 'pop-in', 'wpmudev-chat-id' => base64_encode($chat_session['id'])), get_option('siteurl')) .'" class="wpmudev-chat-action-pop-out">&#9660;</a></li>';
		}

		$chat_header_actions .= '</ul>';

//		if (!strlen($chat_session['box_title']))
//			$chat_title = __('Chat', $this->translation_domain);
//		else
//			$chat_title = urldecode($chat_session['box_title']);

		$chat_title = '';
		if (strlen($chat_session['box_title']))
			$chat_title = urldecode($chat_session['box_title']);

//		if ($chat_session['session_type'] == "site") {
//			if ($chat_session['id'] == "bottom_corner")
//				$chat_title .= ' - '. __('Public', $this->translation_domain);
//			else
//				$chat_title .= ' - '. __('Private', $this->translation_domain);
//		} else {
//			$chat_title .= ' - '. __('Public', $this->translation_domain);
//		}

		if ($chat_session['session_status'] == "open")
			$chat_title_status = __('open', $this->translation_domain);
		else
			$chat_title_status = __('closed', $this->translation_domain);

		$content .= '<span class="wpmudev-chat-title-text">'. $chat_title .'</span>';
		$content .= $chat_header_actions;

		return $content;
	}

	/**
	 * Adds the settings action menu. The module is just a div container displayed within the outer chat box div.
	 * This function is called from the header module function and is used to build out the settings gear menu.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_settings_action_menu($chat_session) {
		$chat_action_menu = '<ul class="wpmudev-chat-actions-settings-menu">';

		if ( (isset($this->chat_auth['type'])) && (!empty($this->chat_auth['type'])) ) {
			if ($this->chat_auth['type'] === 'wordpress') {
				$chat_style_login 	= "display: none;";
				$chat_style_logout 	= "display: none;";
			} else {
				$chat_style_login 	= "display: none;";
				$chat_style_logout 	= "display: block;";
			}
		} else {
			$chat_style_login 	= "display: block;";
			$chat_style_logout 	= "display: none;";
		}

		$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-login" style="'. $chat_style_login .'"><a href="#" class="wpmudev-chat-action-login">'.
			__('Login', $this->translation_domain).'</a></li>';
		$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-logout" style="'. $chat_style_logout .'"><a href="#" class="wpmudev-chat-action-logout">'.
			__('Logout', $this->translation_domain).'</a></li>';

		if ($chat_session['session_type'] == "private") {
			$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-exit"><a href="#" class="wpmudev-chat-action-exit">'.
				__('Leave Chat', $this->translation_domain).'</a></li>';
		}

		if ($chat_session['box_sound'] == "enabled") {
			$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-sound-on"><a title="'. __('Turn chat sound off', $this->translation_domain) .'"
				href="#" class="wpmudev-chat-action-sound">'. __('Sound Off', $this->translation_domain).'</a></li>';
			$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-sound-off"><a title="'. __('Turn chat sound on', $this->translation_domain) .'"
				href="#" class="wpmudev-chat-action-sound">'. __('Sound On', $this->translation_domain).'</a></li>';
		}

		if (wpmudev_chat_is_moderator($chat_session)) {

			$chat_style_session_status_open = '';
			$chat_style_session_status_closed = '';

			if ($chat_session['session_type'] != "private") {

				$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-session-status-open" style="'. $chat_style_session_status_open .'"><a href="#" class="wpmudev-chat-action-session-open">'. __('Open Chat', $this->translation_domain).'</a></li>';
				$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-session-status-closed" style="'. $chat_style_session_status_closed .'"><a href="#" class="wpmudev-chat-action-session-closed">'. __('Close Chat', $this->translation_domain).'</a></li>';
			}

			$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-session-clear"><a href="#"
				class="wpmudev-chat-action-session-clear">'.
				__('Clear Chat', $this->translation_domain).'</a></li>';

			if ($chat_session['session_type'] != "private") {

				if ($chat_session['log_creation'] == 'enabled') {
					$chat_action_menu .= '<li class="wpmudev-chat-action-menu-item-session-archive"><a href="#" class="wpmudev-chat-action-session-archive">'.
				 		__('Archive Chat', $this->translation_domain).'</a></li>';
				}
			}
		}

		$chat_action_menu .= '</ul>';

		return $chat_action_menu;
	}

	/**
	 * Generic utility function call from all module functions. This function builds the actual HTML output for the module.
	 *
	 * @global	none
	 * @param	$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_module_wrap($chat_session, $content='', $extra_class = '', $extra_style='') {

		$wrapper_class = "wpmudev-chat-module";
		if (!empty($extra_class))
			$wrapper_class .= " ". $extra_class;
		if (!empty($wrapper_class))
			$wrapper_class = ' class="'. $wrapper_class .'"';
		$wrapper_style = '';
		if (!empty($extra_style))
			$wrapper_style .= " ". $extra_style;
		if (!empty($wrapper_style))
			$wrapper_style = ' style="'. $wrapper_style .'"';

		$content = '<div'. $wrapper_class .' '. $wrapper_style .'>'. $content .'</div>';

		return $content;
	}

	/**
	 * Given a chat message from the database. This function builds the row output.
	 *
	 * @global	none
	 * @param	$row - The DB object containing all the message information
	 * 			$chat_session - This is out master settings instance.
	 * @return	$content - The output of the styles. Will be echoed at some other point.
	 */
	function chat_session_build_row($row, $chat_session) {

		$message = stripslashes($row->message);
		//$reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";

		//if(($message) != "\n" && ($message) != "<br />" && ($message) != "") {
		//	if(preg_match_all($reg_exUrl, $message, $urls) && isset($urls[0]) && count($urls[0]) > 0) {
		//		foreach ($urls[0] as $url) {
		//			$message = str_replace($url, '<a href="'.$url.'" target="_blank">'.$url.'</a>', $message);
		//		}
		//	}
		//}

		//$message = preg_replace(array('/\[code\]/','/\[\/code\]/'), array('<code>', '</code>'), $message);

		//$code_start_count = preg_match_all('/<code/i', $message, $code_starts);
		//$code_end_count = preg_match_all('/<\/code>/i', $message, $code_ends);

		//if ($code_start_count > $code_end_count) {
		//	$code_diff = $code_start_count - $code_end_count;

		//	for ($i=0; $i<$code_diff; $i++) {
		//		$message .= '</code>';
		//	}

		//} else {
		//	$code_diff = $code_end_count - $code_start_count;

		//	for ($i=0; $i<$code_diff; $i++) {
		//		$message = '<code>'.$message;
		//	}
		//}

		//$message = str_replace("\n", "<br />", $message);

		//$prepend = "";
		//$prepend .= '</div>';
		$row_class = "wpmudev-chat-row";
		if ($row->moderator == 'yes') {
			$row_class .= " wpmudev-chat-row-moderator";
		} else {
			$row_class .= " wpmudev-chat-row-user";

			if (is_email($row->avatar))
				$row_class .= " wpmudev-chat-row-user-". str_replace(array('@', '.'), '-', strtolower($row->avatar));
		}

		if ((isset($row->auth_hash)) && (!empty($row->auth_hash))) {
			$row_class .= " wpmudev-chat-row-auth_hash-". $row->auth_hash;

		}

		$row_class .= " wpmudev-chat-row-ip-". str_replace('.', '-', $row->ip_address);
		//$row_class .= " wpmudev-chat-row-ip-". base64_encode($row->ip_address);

		if ($this->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
			if (array_search($row->ip_address, $this->get_option('blocked_ip_addresses', 'global')) !== false) {
				$row_class .= " wpmudev-chat-row-ip-blocked";
			}
		}

		$row_text = '';
		$row_text .= '<div id="wpmudev-chat-row-'. strtotime($row->timestamp). '" class="'. $row_class .'">';

		$row_avatar_name = '';
			if ($chat_session['row_name_avatar'] == 'avatar') {
				/*
				if (preg_match('/@/', $row->avatar)) {
					$avatar = get_avatar($row->avatar, intval($chat_session['row_avatar_width']), null, $row->name);
				} else {
					$avatar = '<img alt="'. $row->name .'" src="'. $row->avatar .'" class="wpmudev-chat-user wpmudev-chat-user-avatar" height="'.
						intval($chat_session['row_avatar_width']) .'" />';
				}
				*/
				if ((isset($row->avatar)) && (!empty($row->avatar))) {
					$avatar = '<img alt="'. $row->name .'" src="'. $row->avatar .'" class="wpmudev-chat-user wpmudev-chat-user-avatar" height="'.
						intval($chat_session['row_avatar_width']) .'" />';
					$row_avatar_name .= '<a class="wpmudev-chat-user wpmudev-chat-user-avatar" title="@'. $row->name . '" href="#">'. $avatar .'</a>';
				}

			} else if ($chat_session['row_name_avatar'] == "name") {

				$row_avatar_name .= '<a class="wpmudev-chat-user wpmudev-chat-user-name" title="@'. $row->name . '" href="#">'. $row->name .'</a>';
			}
/*
			else if ($chat_session['row_name_avatar'] == "name-avatar") {
				$row_text .= '<div class="wpmudev-chat-row-avatar">';
					if (preg_match('/@/', $row->avatar)) {
						$avatar = get_avatar($row->avatar, $chat_session['row_avatar_width'], null, $row->name);
					} else {
						$avatar = "<img alt='{$row->name}' src='{$row->avatar}' class='wpmudev-avatar photo' />";
					}
					$row_text .= '<a class="wpmudev-chat-user-avatar" title="@'. $row->name .
						'" href="#">'. "$avatar " .'</a>';

				$row_text .= "</div>";

				$row_text .= '<a class="wpmudev-chat-user-name" title="@'. $row->name . '" href="#">'. $row->name .'</a>';
			}
*/

			$row_date_time = '';
			if ($chat_session['row_date'] == 'enabled') {
				$row_date_time .= '<span class="date">'. date_i18n(get_option('date_format'),
					strtotime($row->timestamp) + get_option('gmt_offset') * 3600, false) . '</span>';
			}

			if ($chat_session['row_time'] == 'enabled') {
				if (!empty($row_date_time)) $row_date_time .= " ";

				$row_date_time .= '<span class="time">'. date_i18n(get_option('time_format'),
					strtotime($row->timestamp) + get_option('gmt_offset') * 3600, false) . '</span>';
			}
			if (!empty($row_date_time))
				$row_date_time = "<br />". $row_date_time;

			$row_text .= '<p class="wpmudev-chat-message">'. $row_avatar_name .' '. convert_smilies($message) . $row_date_time .'</p>';


//			if (($chat_session['row_date'] == 'enabled')
//			 || ($chat_session['row_time'] == 'enabled')
//			 || (wpmudev_chat_is_moderator($chat_session))) {

				$row_text .= '<ul class="wpmudev-chat-row-footer">';

					$this->chat_localized['settings']["row_delete_text"]			= __('delete', $this->translation_domain);
					$this->chat_localized['settings']["row_undelete_text"]			= __('undelete', $this->translation_domain);

					//if (($chat_session['session_type'] != "log") && ($row->moderator != "yes")) {
					if ($chat_session['session_type'] != "log") {

						$row_text .= '<li class="wpmudev-chat-admin-actions-item wpmudev-chat-user-invite"><a class="wpmudev-chat-user-invite" rel="'. $row->auth_hash .'" title="'. __('Invite user to private chat:', $this->translation_domain) .' '. $row->name . '" href="#"><span class="action"><img height="10" src="'. plugins_url('/images/padlock-icon-th.png', __FILE__) .'" alt=""/></span></a></li>';

						$row_text .= '<li class="wpmudev-chat-admin-actions-item wpmudev-chat-admin-actions-item-delete"><a class="wpmudev-chat-admin-actions-item-delete" title="'. __('moderate this message', $this->translation_domain) .'" href="#"><span  class="action">'.$this->chat_localized['settings']["row_delete_text"].'</span></a></li>';

						$row_text .= '<li class="wpmudev-chat-admin-actions-item wpmudev-chat-admin-actions-item-block-ip"><a class="wpmudev-chat-admin-actions-item-block-ip" title="'. __('moderate IP address:', $this->translation_domain) . $row->ip_address .'" rel="'. $row->ip_address .'" href="#"><span class="action">'. $row->ip_address .'</span></a></li>';

/*
						$row_text .= '<li class="wpmudev-chat-admin-actions-item wpmudev-chat-admin-actions-item-block-user"><a
						 	class="wpmudev-chat-admin-actions-item-block-user"
							title="'. __('moderate user:', $this->translation_domain) . $row->name .'" rel="'. $row->auth_hash .'"
						 	href="#"><span class="action">'. $row->name .'</span></a></li>';
*/
						//$row_text .= '</ul>'; // End of ul.wpmudev-chat-admin-actions
					}

				$row_text .= '</ul>';	// End of row footer span
//			}
		$row_text .= '</div>';	// End of Row
		return $row_text;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_messages_list_module($chat_session) {
		$content='';

		$content = '<div class="wpmudev-chat-module wpmudev-chat-module-messages-list" >'. $content .'</div>';

		return $content;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function use_public_auth($chat_session) {
		return in_array('public_user', $chat_session['login_options']);
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_login_public($chat_session) {
		$content = '';

		if ($this->use_public_auth($chat_session)) {

			$content .= '<div class="login-message">'. $this->get_option('noauth_login_message', $chat_session['session_type']) .'</div>';

			$content .= '<div id="chat-login-wrap-'. $chat_session['id'] .'" class="chat-login-wrap">';
			$content .= '<p class="wpmudev-chat-login-error" style="color: #FF0000; display:none;"></p>';
			$content .= '<input id="chat-login-name-'. $chat_session['id'] .'" style="width: 90%" name="wpmudev-chat-login-name" class="wpmudev-chat-login-name" type="text" placeholder="'. __('Enter Name', $this->translation_domain) .'"/><br /><br />';
			$content .= '<input id="chat-login-email-'.$chat_session['id'].'" style="width: 90%" name="wpmudev-chat-login-email" class="wpmudev-chat-login-email" type="text" placeholder="'. __('Enter Email', $this->translation_domain) .'"/><br />';

			$content .= '<p class="wpmudev-chat-login-buttons"><button class="wpmudev-chat-login-submit" type="button">'. __('Login', $this->translation_domain) .'</button><button class="wpmudev-chat-login-cancel" type="button">'. __('Cancel', $this->translation_domain) .'</button></p>';

/*
			$content .= '<input type="submit" value="'. __('Login', $this->translation_domain) .'" name="wpmudev-chat-login-submit" class="wpmudev-chat-login-submit" id="wpmudev-chat-login-submit-'. $chat_session['id'].'" /> <input type="submit" value="'. __('Cancel', $this->translation_domain) .'" name="wpmudev-chat-login-cancel" class="wpmudev-chat-login-cancel" id="wpmudev-chat-login-cancel-' . $chat_session['id'].'" />';
*/

			$content .= '</div>';
		}
		return $content;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function is_facebook_setup() {
		if (($this->get_option('facebook_application_id', 'global') != '') && ($this->get_option('facebook_application_secret', 'global') != '')) {
			return true;
		}
		return false;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function use_facebook_auth($chat_session) {
		return ( (in_array('facebook', $chat_session['login_options'])) && ($this->get_option('facebook_application_id', 'global') != '') );
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_login_facebook($chat_session) {
		$content = '';

		if ($this->use_facebook_auth($chat_session)) {
			$content .= '<span id="chat-facebook-signin-btn-'.$chat_session['id'].'"
				class="chat-auth-button chat-facebook-signin-btn"><fb:login-button></fb:login-button></span>';
		}

		return $content;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function is_google_plus_setup() {
		if ($this->get_option('google_plus_application_id', 'global') != '') {
			return true;
		}
		return false;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function use_google_plus_auth($chat_session) {
		return ( (in_array('google_plus', $chat_session['login_options'])) && ($this->get_option('google_plus_application_id', 'global') != '') );
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_login_google_plus($chat_session) {
		$content = '';

		if ($this->use_google_plus_auth($chat_session)) {
			$content .= '<span class="g-signin" data-callback="WPMUDEVChatGooglePlusSigninCallback" data-clientid="'. $this->get_option('google_plus_application_id', 'global')
				.'" data-cookiepolicy="single_host_origin" data-requestvisibleactions="http://schemas.google.com/AddActivity" data-scope="https://www.googleapis.com/auth/plus.login"></span>';
		}

		return $content;
	}


	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function is_twitter_setup() {
		if ($this->get_option('twitter_api_key', 'global') != '') {
			return true;
		}
		return false;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function use_twitter_auth($chat_session) {
		return ( (in_array('twitter', $chat_session['login_options'])) && ($this->get_option('twitter_api_key', 'global') != '') );
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_login_twitter($chat_session) {
		$content = '';

		if ($this->use_twitter_auth($chat_session)) {
			$content .= '<a href="#" id="chat-twitter-signin-btn-'. $chat_session['id'].'" class="chat-auth-button chat-twitter-signin-btn"></a>';
		}

		return $content;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_register_button($buttons) {
		array_push($buttons, "separator", "chat");
		return $buttons;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_load_langs($langs) {
		$langs["chat"] =  plugins_url('/tinymce/langs/langs.php', __FILE__);
		return $langs;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_add_plugin($plugin_array) {
		$plugin_array['chat'] = plugins_url('/tinymce/editor_plugin.js', __FILE__);
		return $plugin_array;
	}

	/**
	 * Process chat requests
	 *
	 * Mostly copied from process.php
	 *
	 * @global	object	$current_user
	 * @param	string	$return		Return? 'yes' or 'no'
	 * @return	string			If $return is yes will return the output else echo
	 */
	function process_chat_actions($return = 'no') {

		if (!isset($_POST['function'])) die();
		$function = $_POST['function'];

		$this->load_configs();

		switch($function) {

			case 'chat_init':
				$reply_data = array();

				foreach($this->chat_sessions as $chat_id => $chat_session) {

					$reply_data[$chat_id] = $this->chat_session_build_box($chat_session);
				}

				wp_send_json($reply_data);
				die();

				break;

			case 'chat_message_send':

				$reply_data = array();
				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				if (!isset($_POST['chat_id'])) die();
				$chat_id = esc_attr($_POST['chat_id']);

				if (!isset($this->chat_sessions[$chat_id])) {
					$reply_data['errorText'] = "invalid chat_id[". $chat_id ."]";
					$reply_data['errorStatus'] = true;
					wp_send_json($reply_data);
					die();
				}
				$chat_session = $this->chat_sessions[$chat_id];

				// Double check the user's authentication. Seems some users can login with multiple tabs. If they log out of one tab they
				// should not be able to post via the other tab.
				if (!isset($this->chat_auth['type'])) {
					$reply_data['errorText'] = "Unknown user type";
					$reply_data['errorStatus'] = true;
					wp_send_json($reply_data);
					die();
				}

				if ((!isset($_POST['chat_message'])) || (!strlen($_POST['chat_message']))) {
					$reply_data['errorText'] = "chat_message not received";
					$reply_data['errorStatus'] = true;
					wp_send_json($reply_data);
					die();
				}

				// From wordpress-chat-2.0.2-Beta1
				// Begin message filtering

				//$chat_message = $_POST['chat_message'];

				$chat_message = urldecode($_POST['chat_message']);
				$chat_message = stripslashes($chat_message);

				// Replace the chr(10) Line feed (not the chr(13) carraige return) with a placeholder. Will be replaced with real <br /> after filtering
				// This is done so when we convert text within [code][/code] the <br /> are not converted to entities. Because we want the code to be formatted
				$chat_message = str_replace(chr(10), "[[CR]]", $chat_message);

				// In case the user entered HTML <code></code> instead of [code][/code]
				$chat_message = str_replace("<code>", "[code]", $chat_message);
				$chat_message = str_replace("</code>", "[/code]", $chat_message);

				// We also can accept backtick quoted text and convert to [code][/code]
				$chat_message = preg_replace('/`(.*?)`/', '[code]$1[/code]', $chat_message);

				// Now split out the [code][/code] sections.
				//preg_match_all("|\[code\](.*)\[/code\]|s", $chat_message, $code_out);
				preg_match_all("~\[code\](.+?)\[/code\]~si", $chat_message, $code_out);
				if (($code_out) && (is_array($code_out)) && (is_array($code_out[0])) && (count($code_out[0]))) {
					foreach($code_out[0] as $code_idx => $code_str_original) {
						if (!isset($code_out[1][$code_idx])) continue;

						// Here we replace our [code][/code] block or text in the message with placeholder [code-XXX] where XXX is the index (0,1,2,3, etc.)
						// Again we do this because in the next step we will strip out all HTML not allowed. We want to protect any HTML within the code block
						// which will be converted to HTML entities after the filtering.
						$chat_message = str_replace($code_str_original, '[code-'. $code_idx.']', $chat_message);
					}
				}

				// First strip all the tags!
				$allowed_protocols = array();
				$allowed_html = array();
				/*
				$allowed_html = array(	'a' => array('href' => array()),
										'br' => array(),
										'em' => array(),
										'strong' => array(),
										'strike' => array(),
										'blockquote' => array()
									);
				*/
				$chat_message = wp_kses($chat_message, $allowed_html, $allowed_protocols);

				// If the user enters something that liiks like a link (http://, ftp://, etc) it will be made clickable
				// in that is will be wrapped in an anchor, etc. The the link tarket will be set so clicking it will open
				// in a new window
				$chat_message = links_add_target(make_clickable($chat_message));

				// Now that we can filtered the text outside the [code][/code] we want to convert the code section HTML to entities since it
				// will be viewed that way by other users.
				if (($code_out) && (is_array($code_out)) && (is_array($code_out[0])) && (count($code_out[0]))) {
					foreach($code_out[0] as $code_idx => $code_str_original) {
						if (!isset($code_out[1][$code_idx])) continue;

						$code_str_replace = "<code>". htmlentities2($code_out[1][$code_idx], ENT_QUOTES|ENT_XHTML) ."</code>";
						$chat_message = str_replace('[code-'.$code_idx.']', $code_str_replace, $chat_message);
					}
				}

				// Finally convert any of our CR placeholders to HTML breaks.
				$chat_message = str_replace("[[CR]]", '<br />', $chat_message);

				// Just as a precaution. After processing we may end up with double breaks. So we convert to single.
				$chat_message = str_replace("<br /><br />", '<br />', $chat_message);


				// End message filtering

				if (empty($chat_message)) {
					$reply_data['errorText'] 	= "chat_message empty after filtering";
					$reply_data['errorStatus'] 	= true;
					wp_send_json($reply_data);
					die();
				}

//				if ( $this->get_option('session_poll_type', 'global') == "static" )
//					$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);

				// Truncate the message IF the max length is set
				if (!wpmudev_chat_is_moderator($chat_session)) {
					if (($chat_session['row_message_input_length'] > 0) && (strlen($chat_message) > $chat_session['row_message_input_length'])) {
						$chat_message = substr($chat_message, 0, $chat_session['row_message_input_length']);
					}
				}

				// Process bad words
				if (($this->_chat_options['banned']['blocked_words_active'] == "enabled") && ($chat_session['blocked_words_active'] == "enabled")) {
					$chat_message = str_ireplace($this->_chat_options['banned']['blocked_words'],
						$this->_chat_options['banned']['blocked_words_replace'], $chat_message);
				}

				/*
				// Save for later. We had a request to support latex via chat
				if (preg_match('/\[latex\](.*)\[\/latex\]/', $chat_message, $match)) {
					if (isset($match[1])) {
						$latex_content = $match[1];
						$latex_content = '[latexpage] \['. $match[1] .'\]';
						$latex_image = quicklatex_parser($latex_content);
						if ($latex_image) {
							$latex_image = strip_tags($latex_image, '<img>');
							$chat_message = str_replace($match[0], $latex_image, $chat_message);
						}
					}
				}
				*/

				$ret = $this->chat_session_send_message($chat_message, $chat_session);

				// Now update the disk file for others.
//				if ( $this->get_option('session_poll_type', 'global') == "static" ) {
//					$this->chat_session_update_message_rows($chat_session);
//				}

				$reply_data['errorText'] 	= "chat_message sent to DB wpdb[". $ret. "]";
				wp_send_json($reply_data);
				die();
				break;

			case 'chat_user_login':
				$reply_data = array();
				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				if (!isset($_POST['user_info'])) {

					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('missing POST user_info', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}
				$user_info = $_POST['user_info'];

				switch($user_info['type']) {
					case 'public_user':
						if ((!isset($user_info['name'])) || (!isset($user_info['email']))) {

							$reply_data['errorText'] 	= __('Please provide valid Name and Email.', $this->translation_domain);
							$reply_data['errorStatus'] 	= true;

							wp_send_json($reply_data);
							die();
						}
						$user_info['name'] 	= esc_attr($user_info['name']);
						$user_info['email'] = esc_attr($user_info['email']);
						if ( (empty($user_info['name'])) || (empty( $user_info['email'] )) || (!is_email( $user_info['email'] )) ) {

							$reply_data['errorText'] 	= __('Please provide valid Name and Email.', $this->translation_domain);
							$reply_data['errorStatus'] 	= true;

							wp_send_json($reply_data);
							die();
						}

						$user_name_id  	= username_exists( $user_info['name'] );
						if ($user_name_id) {

							$reply_data['errorText'] 	= __('Name already registered. Try something unique', $this->translation_domain);
							$reply_data['errorStatus'] 	= true;

							wp_send_json($reply_data);
							die();
						}
						$user_name_id = email_exists( $user_info['email'] );
						if ($user_name_id) {

							$reply_data['errorText'] 	= __('Email already registered. Try something  unique', $this->translation_domain);
							$reply_data['errorStatus'] 	= true;

							wp_send_json($reply_data);
							die();
						}
						$avatar 					= get_avatar($user_info['email'], 96, get_option('avatar_default'), $user_info['name']);
						if ($avatar) {
						    $avatar_parts = array();
							if (stristr($avatar, ' src="') !== false) {
						    	preg_match( '/src="([^"]*)"/i', $avatar, $avatar_parts );
							} else if (stristr($avatar, " src='") !== false) {
						    	preg_match( "/src='([^']*)'/i", $avatar, $avatar_parts );
							}
							if ((isset($avatar_parts[1])) && (!empty($avatar_parts[1])))
						    	$user_info['avatar'] = $avatar_parts[1];
						}

						$user_info['ip_address'] 	= (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
						$user_info['auth_hash'] 	= md5($user_info['name'].$user_info['email'].$user_info['ip_address']);
						$reply_data['user_info']	= $user_info;
						break;

					case 'facebook':
					case 'google_plus':
					case 'twitter':
						$user_info['ip_address'] 	= (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
						$user_info['auth_hash'] 	= md5($user_info['id'].$user_info['ip_address']);
						$reply_data['user_info']	= $user_info;
						break;

					default:
						break;
				}
				wp_send_json($reply_data);
				die();
				break;

			case 'chat_messages_update':
				$reply_data = array();

				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				$reply_data['sessions'] = array();
				$reply_data['invites'] = array();

				//echo "chat_sessions<pre>"; print_r($this->chat_sessions); echo "</pre>";
				//echo "chat_auth<pre>"; print_r($this->chat_auth); echo "</pre>";

				// We first want to grab the invites for the users. This will setup the extra items in the $this->chat_sessions reference. Then later
				// in this section we will also add the rows and meta updates for the new invite box.
				if ($this->using_popup_out_template == false)
					$reply_data['invites'] = $this->chat_session_get_invites_new();

				foreach($this->chat_sessions as $chat_id => $chat_session) {

					$reply_data['sessions'][$chat_id]['rows'] = array();

					$new_rows = $this->chat_session_get_message_new($chat_session);
					if (($new_rows) && (count($new_rows))) {

		    			foreach ($new_rows as $row) {
							$reply_data['sessions'][$chat_id]['rows'][strtotime($row->timestamp)] = $this->chat_session_build_row($row, $chat_session);
		    			}

						if (count($reply_data['sessions'][$chat_id]['rows'])) {
							ksort($reply_data['sessions'][$chat_id]['rows']);
						}
					}

					// Now process the meta information. Session Status, Deleted Row IDs and Session Users
					$reply_data['sessions'][$chat_id]['meta'] = array();

					$reply_data['sessions'][$chat_id]['meta']['session-status'] = $this->chat_get_session_status($chat_session);

					//echo "reply_data<pre>"; print_r($reply_data); echo "</pre>";

					// If both the new rows and the deleted rows is empty. Double check if the session was cleared and/or archived
					if (!isset($chat_session['since'])) $chat_session['since'] = 0;
					if ( ($chat_session['since'] > 0)
					  && (empty($reply_data['sessions'][$chat_id]['rows']))
					  && (empty($reply_data['sessions'][$chat_id]['meta']['deleted-rows'])) ) {

						$chat_session['count_messages'] = true;
						$chat_session['since'] 			= 0;
						$chat_session['end'] 			= 0;
						$chat_session['log_limit'] 		= 0;
						$chat_session['archived'] 		= array('no');

						$rows = $this->chat_session_get_messages($chat_session);

						// IF the message_count is zero...but the chat_session[since] timestamp is greater than zero. We assume
						// the moderator has cleared/archived the session. So send a trigger __EMPTY__ to the users to force clear.
						if ((isset($rows->messages_count)) && ($rows->messages_count == 0)) {
							$reply_data['sessions'][$chat_id]['rows'] = "__EMPTY__";
						}
					}

					$this->chat_auth['ip_address'] = (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];

					$this->chat_session_users_update_polltime($chat_session);

					$reply_data['sessions'][$chat_id]['meta'] 		= $this->chat_session_update_meta_log($chat_session);
					$reply_data['sessions'][$chat_id]['global'] 	= $this->chat_session_update_global_log($chat_session);
				}

				wp_send_json($reply_data);
				die();
				break;

/*
			case 'chat_meta_update':
				$chat_sessions = array(); // Local var to hold new session to be returned

				// Get Private chats
				if ((isset($this->chat_auth['auth_hash'])) && (!empty($this->chat_auth['auth_hash']))) {

					global $wpdb;
					$sql_str = $wpdb->prepare("SELECT * FROM ". WPMUDEV_Chat::tablename('message') ." WHERE session_type=%s AND auth_hash=%s AND archived IN('no', 'no-deleted') ORDER BY timestamp ASC", 'invite', $this->chat_auth['auth_hash'] );
					//echo "sql_str=[". $sql_str ."]<br />";

					$invite_chats = $wpdb->get_results( $sql_str );
					if (!empty($invite_chats)) {
						foreach($invite_chats as $invite_chat) {
							//echo "invite_chat<pre>"; print_r($invite_chat); echo "</pre>";

							if ((empty($invite_chat->name)) || (empty($invite_chat->avatar)) || (empty($invite_chat->ip_address))) {

								$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('message') ." SET `name`=%s, `avatar`=%s, `ip_address`=%s WHERE `id`=%d LIMIT 1", $this->chat_auth['name'], $this->chat_auth['avatar'], $this->chat_auth['ip_address'], $invite_chat->id);
								//echo "sql_str=[". $sql_str ."]<br />";
								$wpdb->query( $sql_str );
							}
							//die();


							if ((isset($invite_chat->message)) && (!empty($invite_chat->message))) {
								$invite_chat->message = unserialize($invite_chat->message);

							} else {
								$invite_info = array();
							}

							if (!isset($this->chat_sessions[$invite_chat->chat_id])) {

								$reply_data['user']['invites'][$invite_chat->chat_id];
								$atts 					= array();
								$atts['id'] 			= $invite_chat->chat_id;
								$atts['session_type']	= 'private';

								$atts['box_title']		= __('Private', $this->translation_domain) .'<span class="wpmudev-chat-private-attendees"></span>';


								if (!isset($invite_chat->message['invite-status']))
									$invite_chat->message['invite-status'] = "pending";

								$atts['invite-info'] = $invite_chat;
								//echo "atts<pre>"; print_r($atts); echo "</pre>";

								$content = $this->process_chat_shortcode($atts);
								if (isset($this->chat_sessions[$invite_chat->chat_id])) {
									$chat_sessions[$invite_chat->chat_id]['html'] 		= 	'<li id="wpmudev-chat-site-item-'. $invite_chat->chat_id
										.'" class="wpmudev-chat-site-item">'. $content .'</li>';
									$chat_sessions[$invite_chat->chat_id]['session'] 	= 	$this->chat_sessions[$invite_chat->chat_id];

									if (isset($this->chat_user['$invite_chat->chat_id']))
										$chat_sessions[$invite_chat->chat_id]['user']	= 	$this->chat_user[$invite_chat->chat_id];
									else
										$chat_sessions[$invite_chat->chat_id]['user']	=	$this->chat_user['__global__'];

									$chat_sessions[$invite_chat->chat_id]['user']['invite-status'] 		= $invite_chat->message;
									$chat_sessions[$invite_chat->chat_id]['user']['invite-moderator'] 	= $invite_chat->moderator;
								}
							}
						}
					}
				}

				foreach($this->chat_sessions as $chat_id => $chat_session) {
					//$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);

					$reply_data[$chat_id]['meta'] = array();

					// Update the user poll time to show active user lists. Really should only do this IF we are showing user lists for this session
//					if ($chat_session['users_list_show'] == "yes") {
						$trigger_log_update = true;
						$this->chat_auth['ip_address'] = (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
						$this->chat_session_users_update_polltime($chat_session);
//						$chat_sessions['meta_data'] 	= $this->chat_session_update_meta_log($chat_session);
//						$chat_sessions['global'] 		= $this->chat_session_update_global_log($chat_session);
//					}
				}

				wp_send_json($chat_sessions);
				die();
				break;
*/
			case 'chat_meta_delete_session':

				$reply_data = array();
				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				if (!isset($_POST['chat-id'])) {

					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat-id', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}
				$chat_id = esc_attr($_POST['chat-id']);

				// Get Private chats
				if ((!isset($this->chat_auth['auth_hash'])) || (empty($this->chat_auth['auth_hash']))) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid auth_hash', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}

				global $wpdb;
				$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('message') ." SET archived=%s WHERE chat_id=%s AND session_type=%s AND auth_hash=%s LIMIT 1", 'yes', $chat_id, 'invite', $this->chat_auth['auth_hash']);
				$wpdb->get_results( $sql_str );

				wp_send_json($reply_data);
				die();
				break;

			case 'chat_messages_clear':
				global $wpdb;

				$reply_data = array();
				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				// If the user doesn't have a type
				if (!isset($this->chat_auth['type'])) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				} else if ( $this->chat_auth['type'] != "wordpress" ) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}

				if ( !is_user_logged_in() ) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}

				foreach($this->chat_sessions as $chat_id => $chat_session) {

					if (wpmudev_chat_is_moderator($chat_session)) {

						$sql_str = $wpdb->prepare("DELETE FROM `".WPMUDEV_Chat::tablename('message')."` WHERE blog_id = %d AND chat_id = %s AND archived IN ('no') AND session_type = %s;", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type']);
						$wpdb->query($sql_str);

					} else {
						$reply_data['errorStatus'] 	= true;
						$reply_data['errorText'] 	= __('Not moderator', $this->translation_domain);
						die();
					}
				}
				wp_send_json($reply_data);
				die();
				break;

			case 'chat_messages_archive':
				global $wpdb;

				$reply_data = array();
				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				// If the user doesn't have a type
				if (!isset($this->chat_auth['type'])) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				} else if ( $this->chat_auth['type'] != "wordpress" ) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}

				if ( !is_user_logged_in() ) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}

				foreach($this->chat_sessions as $chat_id => $chat_session) {

					if (wpmudev_chat_is_moderator($chat_session)) {

						$sql_str = $wpdb->prepare("SELECT * FROM `".WPMUDEV_Chat::tablename('message')."` WHERE blog_id = %d
							AND chat_id = %s AND archived IN ('no') ORDER BY timestamp ASC;",
							$chat_session['blog_id'], $chat_session['id']);
						$chat_messages = $wpdb->get_results($sql_str);
						if (($chat_messages) && (count($chat_messages))) {

							$created = date('Y-m-d H:i:s');
							$chat_summary 					= array();
							$chat_summary['messages_count'] = count($chat_messages);
							$chat_summary['users'] 			= array();
							$chat_summary['id']				=	array();
							foreach($chat_messages as $id => $chat_message) {
								$chat_summary['id'][] = $chat_message->id;
								if ($id == 0) {
									$chat_summary['start'] 			= $chat_message->timestamp;
									$chat_summary['session_type'] 	= $chat_message->session_type;
								}
								$chat_summary['end'] 			= $chat_message->timestamp;

								if (!isset($chat_summary['users'][$chat_message->auth_hash]))
									$chat_summary['users'][$chat_message->auth_hash] = $chat_message->auth_hash;
							}

							$sql_str = $wpdb->prepare("INSERT INTO ".WPMUDEV_Chat::tablename('log')."
										(blog_id, chat_id, session_type, messages_count, users_count, start, end, created)
										VALUES (%d, %s, %s, %d, %d, %s, %s, %s);",
										$chat_session['blog_id'], $chat_session['id'],
										$chat_summary['session_type'], $chat_summary['messages_count'], count($chat_summary['users']),
										$chat_summary['start'], $chat_summary['end'], $created);

							$wpdb->query($sql_str);
							if ($wpdb->insert_id) {
								$sql_str = $wpdb->prepare("UPDATE `". WPMUDEV_Chat::tablename('message')."` set archived = 'yes', log_id=%d WHERE blog_id = %d AND chat_id = %s AND id IN(". join(',', array_values($chat_summary['id'])) .") AND archived = 'no';", $wpdb->insert_id, $chat_session['blog_id'], $chat_session['id'], $chat_summary['start'], $chat_summary['end']);
								$wpdb->query($sql_str);
							}
						}

//						$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
//						$this->chat_session_update_message_rows($chat_session);

					}
				}
				wp_send_json($reply_data);
				die();
				break;

			case 'chat_session_moderate_status':

				$reply_data = array();
				$reply_data['errorStatus'] 	= false;
				$reply_data['errorText'] 	= '';

				$chat_id = 0;

				if (!isset($_POST['chat_session'])) {

					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_session', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				}
				$chat_session = $_POST['chat_session'];
				$chat_id = esc_attr($chat_session['id']);
				if ($chat_id == '') {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_id', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				}

				if (!isset($_POST['chat_session_status'])) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_session_status', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				}

				$chat_session_status = esc_attr($_POST['chat_session_status']);
				if (($chat_session_status != "open") && ($chat_session_status != "closed")) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_session_status', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				}


				// If the user doesn't have a type
				if (!isset($this->chat_auth['type'])) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();
				} else if ( $this->chat_auth['type'] != "wordpress" ) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				}

				if ( !is_user_logged_in() ) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('Invalid chat_auth [type]', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				}

				if (!wpmudev_chat_is_moderator($chat_session)) {
					$reply_data['errorStatus'] 	= true;
					$reply_data['errorText'] 	= __('not moderator', $this->translation_domain);

					wp_send_json($reply_data);
					die();

				}

				$status_status =  $this->chat_set_session_status($chat_id, $chat_session_status);

//				$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);

				delete_transient( 'wpmudev-chat-global-data' );
				$this->chat_session_update_meta_log($chat_session);
				$this->chat_session_update_global_log($chat_session);

				wp_send_json($reply_data);
				die();
				break;

			case 'chat_session_moderate_message':
				global $wpdb;

				if (!isset($_POST['chat_id'])) {
					wp_send_json_error();
					die();
				}
				$chat_id = $_POST['chat_id'];

				if ($chat_id == '') {
					wp_send_json_error();
					die();
				}

				if (!isset($_POST['row_id'])) {
					wp_send_json_error();
					die();
				}
				$row_id = intval($_POST['row_id']);
				if (empty($row_id)) {
					wp_send_json_error();
					die();
				}

				if (!isset($_POST['moderate_action'])) {
					wp_send_json_error();
					die();
				}
				$moderate_action = esc_attr($_POST['moderate_action']);
				if (empty($moderate_action)) {
					wp_send_json_error();
					die();
				}

				if (!isset($_POST['chat_session'])) {
					wp_send_json_error();
					die();
				}
				$chat_session = $_POST['chat_session'];

				// If the user doesn't have a type
				if (!isset($this->chat_auth['type'])) {
					wp_send_json_error();
					die();
				} else if ( $this->chat_auth['type'] != "wordpress" ) {
					wp_send_json_error();
					die();
				}

				if ( !is_user_logged_in() ) {
					wp_send_json_error();
					die();
				}

				if (!wpmudev_chat_is_moderator($chat_session)) {
					wp_send_json_error();
					die();
				}

				$row_date  = date('Y-m-d H:i:s', $row_id);

				$sql_str = $wpdb->prepare("SELECT id, deleted FROM `". WPMUDEV_Chat::tablename('message')
					."` WHERE blog_id = %d AND chat_id = %s AND timestamp = %s LIMIT 1;",
					$chat_session['blog_id'], $chat_id, $row_date);
				echo "sql_str=[". $sql_str ."]<br />";

				$chat_row = $wpdb->get_row($sql_str);
				echo "chat_row<pre>"; print_r($chat_row); echo "</pre>";

				if (($chat_row) && (isset($chat_row->deleted))) {
					$chat_row_deleted_new = '';

					if ($moderate_action == "delete") {
						$chat_row_deleted_new = 'yes';
					} else if ($moderate_action == "undelete") {
						$chat_row_deleted_new = 'no';
					}

					if (!empty($chat_row_deleted_new)) {
						$sql_str = $wpdb->prepare("UPDATE `".WPMUDEV_Chat::tablename('message')
							."` SET deleted=%s WHERE id=%d AND blog_id = %d AND chat_id = %s LIMIT 1;",
							$chat_row_deleted_new, $chat_row->id, $chat_session['blog_id'], $chat_id);

						$wpdb->get_results( $sql_str );

						//$this->chat_session_update_message_rows_deleted($chat_session);

//						$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
//						$this->chat_session_update_meta_log($chat_session);
//						$this->chat_session_update_global_log($chat_session);

						delete_transient( 'wpmudev-chat-delete-rows-'. $chat_session['id'] );
						$this->chat_session_update_meta_log($chat_session);
						$this->chat_session_update_global_log($chat_session);

						wp_send_json_success();
						die();
					}
				}
				break;

			case 'chat_session_moderate_ipaddress':

				global $wpdb;

				if (!isset($_POST['chat_id'])) die();
				$chat_id = esc_attr($_POST['chat_id']);
				if ($chat_id == '') die();

				if (!isset($_POST['ip_address'])) die();
				$ip_address = esc_attr($_POST['ip_address']);
				if (empty($ip_address)) die();

				if (!isset($_POST['moderate_action'])) die();
				$moderate_action = esc_attr($_POST['moderate_action']);
				if (empty($moderate_action)) die();

				if (!isset($_POST['chat_session'])) die();
				$chat_session = $_POST['chat_session'];

				// If the user doesn't have a type
				if (!isset($this->chat_auth['type']))
					die();
				else if ( $this->chat_auth['type'] != "wordpress" )
					die();

				if ( !is_user_logged_in() )
					die();

				if (!wpmudev_chat_is_moderator($chat_session)) die();

				if ($this->get_option('blocked_ip_addresses_active', 'global') != "enabled") die();

				if ((!isset($this->_chat_options['global']['blocked_ip_addresses']))
				 || (empty($this->_chat_options['global']['blocked_ip_addresses'])) )
					$this->_chat_options['global']['blocked_ip_addresses'] = array();

				if ($moderate_action == "block-ip") {

					$this->_chat_options['global']['blocked_ip_addresses'][] = $ip_address;
					$this->_chat_options['global']['blocked_ip_addresses'] = array_unique($this->_chat_options['global']['blocked_ip_addresses']);

					update_option('wpmudev-chat-global', $this->_chat_options['global']);

					delete_transient( 'wpmudev-chat-global-data' );

//					$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
					$this->chat_session_update_meta_log($chat_session);
					$this->chat_session_update_global_log($chat_session);


				} else if ($moderate_action == "unblock-ip") {

					$arr_idx = array_search($ip_address, $this->_chat_options['global']['blocked_ip_addresses']);
					if (($arr_idx !== false) && (isset($this->_chat_options['global']['blocked_ip_addresses'][$arr_idx]))) {
						unset( $this->_chat_options['global']['blocked_ip_addresses'][$arr_idx] );
						update_option('wpmudev-chat-global', $this->_chat_options['global']);
					}

					delete_transient( 'wpmudev-chat-global-data' );
//					$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
					$this->chat_session_update_meta_log($chat_session);
					$this->chat_session_update_global_log($chat_session);
				}
				wp_send_json_success();
				die();
				break;


			case 'chat_session_moderate_user':

				global $wpdb;

				if (!isset($_POST['chat_id'])) die();
				$chat_id = esc_attr($_POST['chat_id']);
				if ($chat_id == '') die();

				if (!isset($_POST['moderate_item'])) die();
				$moderate_item = esc_attr($_POST['moderate_item']);
				if (empty($moderate_item)) die();

				if (!isset($_POST['moderate_action'])) die();
				$moderate_action = esc_attr($_POST['moderate_action']);
				if (empty($moderate_action)) die();

				if (!isset($_POST['chat_session'])) die();
				$chat_session = $_POST['chat_session'];

				// If the user doesn't have a type
				if (!isset($this->chat_auth['type']))
					die();
				else if ( $this->chat_auth['type'] != "wordpress" )
					die();

				if ( !is_user_logged_in() )
					die();

				if (!wpmudev_chat_is_moderator($chat_session)) die();

				if ($moderate_action == "block-user") {

					$this->_chat_options['global']['blocked_users'][] = $moderate_item;
					$this->_chat_options['global']['blocked_users'] = array_unique($this->_chat_options['global']['blocked_users']);

					update_option('wpmudev-chat-global', $this->_chat_options['global']);

//					$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
					delete_transient( 'wpmudev-chat-global-blocked_users-'. $chat_session['id'] );
					$this->chat_session_update_meta_log($chat_session);
					$this->chat_session_update_global_log($chat_session);

				} else if ($moderate_action == "unblock-user") {

					$arr_idx = array_search($moderate_item, $this->_chat_options['global']['blocked_users']);
					if (($arr_idx !== false) && (isset($this->_chat_options['global']['blocked_users'][$arr_idx]))) {
						unset( $this->_chat_options['global']['blocked_users'][$arr_idx] );

						update_option('wpmudev-chat-global', $this->_chat_options['global']);
					}
					delete_transient( 'wpmudev-chat-global-blocked_users-'. $chat_session['id'] );
//					$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
					$this->chat_session_update_meta_log($chat_session);
					$this->chat_session_update_global_log($chat_session);
				}
				wp_send_json_success();
				die();
				break;

			case 'chat_session_invite_private':
				global $wpdb;

				// We ONLY allow logged in users to perform private invites
				if (!is_user_logged_in()) {
					wp_send_json_error();
					return;
				}

				if (md5(get_current_user_id()) != $this->chat_auth['auth_hash']) {
					wp_send_json_error();
					return;
				}
				$user_from_hash = $this->chat_auth['auth_hash'];

				if ((!isset($_REQUEST['wpmudev-chat-to-user'])) || (empty($_REQUEST['wpmudev-chat-to-user']))) {
					wp_send_json_error();
					return;
				}
				$user_to_hash = esc_attr($_REQUEST['wpmudev-chat-to-user']);

				$private_invite_noonce = time();
				$chat_id = "private-". $private_invite_noonce;

				if (is_multisite())
					$blog_id = $wpdb->blogid;
				else
					$blog_id = 1;

				// First add the FROM User
				$user_moderator = "yes";
				$sql_str = $wpdb->prepare("SELECT * FROM ". WPMUDEV_Chat::tablename('message') ." WHERE session_type=%s AND chat_id=%s AND auth_hash=%s AND archived IN('no') ORDER BY timestamp ASC", 'invite', $chat_id, $user_from_hash);
				$invites = $wpdb->get_results( $sql_str );
				if (empty($invites)) {

					$sql_str = $wpdb->prepare("INSERT INTO ". WPMUDEV_Chat::tablename('message') .
						" (`blog_id`, `chat_id`, `session_type`, `timestamp`, `name`, `avatar`, `auth_hash`, `ip_address`, `message`, `moderator`, `deleted`, `archived`, `log_id`) VALUES(%d, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %d);",
						$blog_id, $chat_id, 'invite', $this->chat_auth['name'], $this->chat_auth['avatar'], $user_from_hash,
						$this->chat_auth['ip_address'], '', $user_moderator, 'no', 'no', 0);

					$wpdb->get_results( $sql_str );
					if (intval($wpdb->insert_id)) {

						$invitation							= array();
						$invitation['id'] 					= $wpdb->insert_id;
						$invitation['host']					= array();
						$invitation['host']					= $this->chat_auth;
						$invitation['invite-status']		= 'pending';

						// Then add the to
						$user_moderator = "no";
						$sql_str = 	$wpdb->prepare("SELECT * FROM ". WPMUDEV_Chat::tablename('message') ." WHERE session_type=%s AND chat_id=%s AND auth_hash=%s AND archived IN('no') ORDER BY timestamp ASC", 'invite', $chat_id, $user_to_hash);
						$invites = $wpdb->get_results( $sql_str );
						if (empty($invites)) {

							$sql_str = $wpdb->prepare("INSERT INTO ". WPMUDEV_Chat::tablename('message') .
								" (`blog_id`, `chat_id`, `session_type`, `timestamp`, `name`, `avatar`, `auth_hash`, `ip_address`, `message`, `moderator`, `deleted`, `archived`, `log_id`) VALUES(%d, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %d);",
								$blog_id, $chat_id, 'invite', $this->chat_auth['name'], $this->chat_auth['avatar'], $user_to_hash,
								$this->chat_auth['ip_address'], serialize($invitation), $user_moderator, 'no', 'no', 0);

							$wpdb->get_results( $sql_str );
						}
					}
				}
				wp_send_json_success();
				die();
				break;

			case 'chat_update_user_status':
				if (!is_user_logged_in()) return;

				$user_id = get_current_user_id();
				if (md5($user_id) == $this->chat_auth['auth_hash']) {
					if (isset($_POST['wpmudev-chat-user-status'])) {
						$new_status = esc_attr($_POST['wpmudev-chat-user-status']);
						if (isset($this->_chat_options['user-statuses'][$new_status])) {
							wpmudev_chat_update_user_status($user_id, $new_status);
							wp_send_json_success();
						}
					}
				}
				die();
				break;

			case 'chat_invite_update_user_status':

				$chat_id = esc_attr($_POST['chat-id']);
				if (!$chat_id) die();

				if ((!isset($this->chat_auth['auth_hash'])) || (empty($this->chat_auth['auth_hash']))) die();

				$invite_status = esc_attr($_POST['invite-status']);
				if ( ($invite_status != 'accepted') && (($invite_status != 'declined')) )
					$invite_status = 'declined';

				global $wpdb;
				$sql_str = $wpdb->prepare("SELECT * FROM ". WPMUDEV_Chat::tablename('message') ." WHERE session_type=%s AND auth_hash=%s AND archived IN('no') ORDER BY timestamp ASC", 'invite', $this->chat_auth['auth_hash']);

				$invite_chats = $wpdb->get_results( $sql_str );
				if (!empty($invite_chats)) {
					foreach($invite_chats as $invite_chat) {
						//echo "invite_chat<pre>"; print_r($invite_chat); echo "</pre>";
						$invite_info = unserialize($invite_chat->message);
						$invite_info['invite-status'] = $invite_status;

						$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('message') ." SET `message`= %s WHERE id=%d", serialize($invite_info), $invite_chat->id);

						$wpdb->query($sql_str);
					}
				}
				wp_send_json_success();
				die();
				break;
		}
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_build_box($chat_session) {
		$content = '';

		// Initialize our Messag and Deleted rows log files. This is what the polling AJAX will read.
//		if ( $this->get_option('session_poll_type', 'global') == "static" ) {
//			$chat_session['logs'] = $this->chat_session_init_log_filenames($chat_session);
//			$this->chat_session_update_message_rows($chat_session);
//		}

		$chat_session['session_status'] = $this->chat_get_session_status($chat_session);

//		if ($chat_session['session_type'] != "bp-group") {
			$content_tmp 	= $this->chat_box_header_container($chat_session);
			$content 		= $this->chat_session_module_wrap($chat_session, $content_tmp, 'wpmudev-chat-module-header');
//		}

		$content 		.= $this->chat_session_status_module($chat_session);
		$content 		.= $this->chat_session_generic_message_module($chat_session);
		$content		.= $this->chat_session_login_prompt_module($chat_session);
		$content		.= $this->chat_session_invite_prompt_module($chat_session);

//		$content_tmp 	= $this->chat_session_users_list_module($chat_session);
//		$content_tmp 	.= $this->chat_session_messages_list_module($chat_session);
//		$content 		.= $this->chat_session_module_wrap($chat_session, $content_tmp, 'wpmudev-chat-module-lists');


		if ($chat_session['box_input_position'] == "top") {
			$content 		.= $this->chat_session_message_area_module($chat_session);
		}

		if (($chat_session['users_list_position'] == "above") || ($chat_session['users_list_position'] == "left")) {
			$content 		.= $this->chat_session_users_list_module($chat_session);
			$content 		.= $this->chat_session_messages_list_module($chat_session);
		} else if (($chat_session['users_list_position'] == "below") || ($chat_session['users_list_position'] == "right")) {
			$content 		.= $this->chat_session_messages_list_module($chat_session);
			$content 		.= $this->chat_session_users_list_module($chat_session);
		} else if ($chat_session['users_list_position'] == "none") {
			$content 		.= $this->chat_session_messages_list_module($chat_session);
		}

		$content 		.= $this->chat_session_login_module($chat_session);
		$content 		.= $this->chat_session_banned_status_module($chat_session);

		if ($chat_session['box_input_position'] == "bottom") {
			$content 		.= $this->chat_session_message_area_module($chat_session);
		}

		return $content;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_init_log_filenames($chat_session = array()) {

		$chat_logs = array();
		$log_dir = trailingslashit($this->chat_localized['settings']['session_logs_path']);
		if (!file_exists($log_dir)) {
			wp_mkdir_p($log_dir);
		}

		if (isset($chat_session['id'])) {
			$filename_prefix = "chat-session-". $chat_session['blog_id'] ."-". $chat_session['id'] .'-'. $chat_session['session_type'];

			$chat_logs['messages'] 	= $log_dir . $filename_prefix .'-messages-'. md5($filename_prefix).'.log';
			$chat_logs['meta'] 		= $log_dir . $filename_prefix .'-meta-'. md5($filename_prefix) .'.log';

			if (!file_exists($chat_logs['messages'])) {
				touch($chat_logs['messages']);
			}
			if (!file_exists($chat_logs['meta'])) {
				touch($chat_logs['meta']);
			}
		}

		$global_prefix 			= "chat-session-". $chat_session['blog_id'] ."-global";
		$chat_logs['global'] 	= $log_dir . $global_prefix .'-global-'. md5($global_prefix) .'.log';
		if (!file_exists($chat_logs['global'])) {
			touch($chat_logs['global']);
		}


		return $chat_logs;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_update_message_rows($chat_session) {

		$chat_session['since'] 		= 0;
		$chat_session['end'] 		= 0;
		$chat_session['archived'] 	= array('no');

		$reply_data_rows_serialized = '';
		$reply_data_rows = array();
		$meta_data = array();
		$meta_data['deleted-rows'] = array();

		$rows_tmp = $this->chat_session_get_messages($chat_session);

		if (($rows_tmp) && (count($rows_tmp))) {

			foreach ($rows_tmp as $row) {
				$reply_data_rows[strtotime($row->timestamp)] = $this->chat_session_build_row($row, $chat_session);

				if ($row->deleted == "yes")
					$meta_data['deleted-rows'][] = strtotime($row->timestamp);
			}

			if (count($reply_data_rows)) {
				krsort($reply_data_rows);
			}
		}

		$fp = fopen($chat_session['logs']['messages'], "w+");
		if ($fp) {
			if (flock($fp, LOCK_EX | LOCK_NB)) {
				foreach($reply_data_rows as $key => $val) {
					//fwrite($fp, $key.' '. serialize($val) ."\r\n");
					fwrite($fp, $key.' '. base64_encode($val) ."\r\n");
				}
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}

//		$this->chat_session_update_meta_log($chat_session, $meta_data['deleted-rows']);
//		$this->chat_session_update_global_log($chat_session);

	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_get_invites_new() {
		global $wpdb;

		$invite_sessions = array();

		if (!isset($this->chat_auth['auth_hash'])) {
			return $invite_sessions;
		}

		// We want to exclude existing chat_session IDs.
		$chat_id_str = '';
		foreach($this->chat_sessions as $chat_id => $chat_session) {
			if (strlen($chat_id_str)) $chat_id_str .= ",";
			$chat_id_str .= "'". esc_attr($chat_id) ."'";
		}
		if (!empty($chat_id_str))
			$chat_id_str = " AND chat_id NOT IN (". $chat_id_str .") ";

		$sql_str = $wpdb->prepare("SELECT * FROM ". WPMUDEV_Chat::tablename('message') ." WHERE session_type=%s AND auth_hash=%s AND archived IN('no') ". $chat_id_str ." ORDER BY timestamp ASC", 'invite', $this->chat_auth['auth_hash'] );

		$invite_chats = $wpdb->get_results( $sql_str, ARRAY_A );
		if (!empty($invite_chats)) {
			foreach($invite_chats as $invite_chat) {

				if ((empty($invite_chat['name'])) || (empty($invite_chat['avatar'])) || (empty($invite_chat['ip_address']))) {

					$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('message') ." SET `name`=%s, `avatar`=%s, `ip_address`=%s WHERE `id`=%d LIMIT 1", $this->chat_auth['name'], $this->chat_auth['avatar'], $this->chat_auth['ip_address'], $invite_chat['id']);
					$wpdb->query( $sql_str );
				}
				//die();


				if ((isset($invite_chat['message'])) && (!empty($invite_chat['message']))) {
					$invite_chat['message'] = unserialize($invite_chat['message']);

				} else {
					$invite_info = array();
				}

				if (!isset($this->chat_sessions[$invite_chat['chat_id']])) {

					//$reply_data['user']['invites'][$invite_chat['chat_id']];
					$atts 					= array();
					$atts['id'] 			= $invite_chat['chat_id'];
					$atts['session_type']	= 'private';

					$atts['box_title']		= __('Private', $this->translation_domain) .'<span class="wpmudev-chat-private-attendees"></span>';


					if (!isset($invite_chat['message']['invite-status']))
						$invite_chat['message']['invite-status'] = "pending";

					$atts['invite-info'] = $invite_chat;

					$content = $this->process_chat_shortcode($atts);
					if (isset($this->chat_sessions[$invite_chat['chat_id']])) {
						//$reply_data['invites'][$invite_chat['chat_id']]['html'] 	= 	'<li id="wpmudev-chat-site-item-'. $invite_chat['chat_id']
						//	.'" class="wpmudev-chat-site-item">'. $content .'</li>';

						$invite_sessions[$invite_chat['chat_id']]['html'] 	= 	$content;

						$invite_sessions[$invite_chat['chat_id']]['session'] 	= 	$this->chat_sessions[$invite_chat['chat_id']];

						if (isset($this->chat_user[$invite_chat['chat_id']]))
							$invite_sessions[$invite_chat['chat_id']]['user']	= 	$this->chat_user[$invite_chat['chat_id']];
						else
							$invite_sessions[$invite_chat['chat_id']]['user']	=	$this->chat_user['__global__'];

						$invite_sessions[$invite_chat['chat_id']]['user']['invite-status'] 		= $invite_chat['message'];
						$invite_sessions[$invite_chat['chat_id']]['user']['invite-moderator'] 	= $invite_chat['moderator'];
					}
				}
			}
		}
		return $invite_sessions;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_get_message_new($chat_session) {
		if (!isset($chat_session['since']))
			$chat_session['since'] = 0;

		if (!isset($chat_session['end']))
			$chat_session['end'] = 0;

		if ($chat_session['since'] > 0) {
			$chat_session['log_limit'] = 0;
		}

		if (!isset($chat_session['archived']))
			$chat_session['archived'] = array('no');

		return $this->chat_session_get_messages($chat_session, false);
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_get_message_rows_deleted($chat_session) {

		$transient_key = 'wpmudev-chat-delete-rows-'. $chat_session['id'];

		if ( false !== ( $deleted_rows_transient = get_transient( $transient_key ) ) ) {
			if ((!is_array($deleted_rows_transient)) && ($deleted_rows_transient === "__EMPTY__"))
				return array();
			if (($deleted_rows_transient) && (is_array($deleted_rows_transient)))
				return $deleted_rows_transient;
		}

		$chat_session['since'] 		= 0;
		$chat_session['end'] 		= 0;
		$chat_session['log_limit'] 	= 0;
		$chat_session['archived'] 	= array('no');
		$chat_session['deleted'] 	= array('yes');

		if (isset($chat_session['count_messages']))
			unset($chat_session['count_messages']);

		$deleted_rows = array();

		$rows_tmp = $this->chat_session_get_messages($chat_session);
		if (($rows_tmp) && (count($rows_tmp))) {

			foreach ($rows_tmp as $row) {
				if ($row->deleted == "yes")
					$deleted_rows[] = strtotime($row->timestamp);
			}
		}

		if (!count($deleted_rows))
			$deleted_rows = "__EMPTY__";

		set_transient( $transient_key, $deleted_rows, 1*DAY_IN_SECONDS );

		return $deleted_rows;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_update_meta_log($chat_session, $meta_data = array()) {

		if (!isset($meta_data['deleted-rows'])) {
			$meta_data['deleted-rows'] = $this->chat_session_get_message_rows_deleted($chat_session);
		}

		if (!isset($meta_data['session-status'])) {
			$meta_data['session-status'] = $this->chat_get_session_status($chat_session);
		}

		if (!isset($meta_data['users-active'])) {
			$meta_data['users-active'] = $this->chat_session_get_active_users($chat_session);
		}

		// Now write the meta data to the output log.
//		file_put_contents($chat_session['logs']['meta'], serialize($meta_data));

		//set_transient( 'wpmudev-chat-meta-log-'. $chat_session['id'],  $meta_data, 60);

		return $meta_data;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_update_global_log($chat_session, $global_data = array()) {

		$global_data_transient = get_transient( 'wpmudev-chat-global-data' );
		if ($global_data_transient)
			return $global_data_transient;

		if (!isset($global_data['blocked-ip-addresses'])) {
			$global_data['blocked-ip-addresses'] = $this->chat_session_get_blocked_ip_addresses($chat_session);
		}

		if (!isset($global_data['blocked-users'])) {
			$global_data['blocked-users'] = $this->chat_session_get_blocked_users($chat_session);
		}

		//file_put_contents($chat_session['logs']['global'], serialize($global_data));
		set_transient( 'wpmudev-chat-global-data', $global_data, 60 );

		return $global_data;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_users_update_polltime($chat_session) {
		global $wpdb;

		if (!isset($chat_session['id'])) return;

		// IF the user has not logged in yet. Ignore for now.
		if ((!isset($this->chat_auth['auth_hash'])) || (empty($this->chat_auth['auth_hash']))) {
			return;
		}

		$blog_id 			= $chat_session['blog_id'];
		$chat_id 			= $chat_session['id'];
		$user_name 			= trim($this->chat_auth['name']);
		$user_avatar 		= trim($this->chat_auth['avatar']);
		$user_hash 			= trim($this->chat_auth['auth_hash']);
		$user_ip_address 	= trim($chat_session['ip_address']);
		$user_moderator 	= trim($chat_session['moderator']);
		if ($user_moderator != "yes")
			$user_moderator = "no";


		$chat_delete_threshold = intval($chat_session['users_list_threshold_delete']);
		if ($chat_delete_threshold > 0) {

			$sql_str = $wpdb->prepare("DELETE FROM ". WPMUDEV_Chat::tablename('users') ." WHERE chat_id=%s AND last_polled < TIMESTAMPADD(SECOND, -". $chat_delete_threshold .", NOW());", $chat_session['id']);
			$wpdb->query( $sql_str );
			//echo "sql_str=[". $sql_str ."]<br />";
		}

		if ((isset($user_name)) && (strlen($user_name))) {
			$sql_str = $wpdb->prepare("INSERT INTO ". WPMUDEV_Chat::tablename('users') .
			" (blog_id, chat_id, auth_hash, name, avatar, moderator, last_polled, entered, ip_address)
			VALUES(%d, %s, %s, %s, %s, %s, NOW(), NOW(), %s)
			ON DUPLICATE KEY UPDATE blog_id = %d, chat_id = %s, auth_hash = %s, name = %s, avatar = %s, moderator = %s, last_polled = NOW(), ip_address = %s;",
			$blog_id, $chat_id, $user_hash, $user_name, $user_avatar, $user_moderator, $user_ip_address,
			$blog_id, $chat_id, $user_hash, $user_name, $user_avatar, $user_moderator, $user_ip_address);

			//echo "sql_str=[". $sql_str ."]<br />";

			$wpdb->get_results( $sql_str );
		}

		return;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_get_active_users($chat_session) {
		global $wpdb;

		if (isset($chat_session['users_list_avatar_width'])) {
			$avatar_size = intval($chat_session['users_list_avatar_width']);
		}
		if ((!isset($avatar_size)) || ($avatar_size < 1) || ($avatar_size > 100)) {
			$avatar_size = 50;
		}

	 	$sql_str = $wpdb->prepare("SELECT * FROM ". WPMUDEV_Chat::tablename('users') ." WHERE chat_id=%s AND blog_id=%d ORDER BY name ASC", $chat_session['id'], $chat_session['blog_id']);
		$users = $wpdb->get_results( $sql_str );
		if (($users) && (count($users))) {
			$users_data = array();
			$users_data['moderators'] 	= array();
			$users_data['users']		= array();

			foreach($users as $user) {
				$_tmp 				= array();
				//$_tmp['id'] 		= $user->id;
				$_tmp['name'] 		= $user->name;
				$_tmp['moderator'] 	= $user->moderator;
				$_tmp['ip'] 		= $user->ip_address;
				$_tmp['auth_hash']	= $user->auth_hash;

				if ((isset($user->avatar)) && (strlen($user->avatar))) {
					//if (preg_match('/@/', $user->avatar)) {
					//	$avatar = get_avatar($user->avatar, $avatar_size, null, $user->name);
					//} else {
						$avatar = '<img alt="'. $user->name .'" style="width: '. $avatar_size .'; height: '.$avatar_size.';" width="'. $avatar_size .
						'" src="'. $user->avatar .'" class="avatar photo" />';
					//}
					$_tmp['avatar'] = $avatar;
				}
				if ($_tmp['moderator'] == "yes") {
					$users_data['moderators'][$_tmp['auth_hash']] = $_tmp;
				} else {
					$users_data['users'][$_tmp['auth_hash']] = $_tmp;
				}
			}
			return $users_data;
		}
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_get_blocked_ip_addresses($chat_session) {

		$blocked_ip_addresses = array();

		// If IP filtering is enabled for this section...
		//if ($chat_session['blocked_ip_addresses_active'] != "enabled") return $blocked_ip_addresses;

		// ...AND if IP filtering is enabled globally
		//if ($this->get_option('blocked_ip_addresses_active', 'global') != "enabled") return $blocked_ip_addresses;

		$blocked_ip_addresses = $this->get_option('blocked_ip_addresses', 'global');
		if (empty($blocked_ip_addresses)) return array();
		else if (!is_array($blocked_ip_addresses))
			$blocked_ip_addresses = array($blocked_ip_addresses);
		return $blocked_ip_addresses;
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_session_get_blocked_users($chat_session) {

		$blocked_users = array();

		$blocked_users = $this->get_option('blocked_users', 'global');
		if (empty($blocked_users)) return array();
		else if (!is_array($blocked_users))
			return array($blocked_users);
		else
			return $blocked_users;
	}


	/**
	 * Get message
	 *
	 * @global	object	$wpdb
	 * @global	int		$blog_id
	 * @param	int		$chat_id	Chat ID
	 * @param	int		$since		Start Unix timestamp
	 * @param	int		$end		End Unix timestamp
	 * @param	string	$archived	Archived? 'yes' or 'no'
	 */
	function chat_session_get_messages($chat_session) {
		global $wpdb;

		if ((isset($chat_session['since'])) && ($chat_session['since'] > 0))
			$since_timestamp = date('Y-m-d H:i:s', $chat_session['since']);
		else
			$since_timestamp = 0;

		if ((isset($chat_session['end'])) && ($chat_session['end'] > 0))
			$end_timestamp = date('Y-m-d H:i:s', $chat_session['end']);
		else
			$end_timestamp = 0;

		$archived_str = "";
		if (!isset($chat_session['archived']))
			$chat_session['archived'] = array('no');

		if ((isset($chat_session['archived'])) && (count($chat_session['archived']))) {
			foreach($chat_session['archived'] as  $_val) {
				if (strlen($archived_str)) $archived_str .= ",";
				$archived_str .= "'". $_val ."'";
			}
		}

		$deleted_str = "";
		if (!isset($chat_session['deleted']))
			$chat_session['deleted'] = array('no');

		if ((isset($chat_session['deleted'])) && (count($chat_session['deleted']))) {
			foreach($chat_session['deleted'] as  $_val) {
				if (strlen($deleted_str)) $deleted_str .= ",";
				$deleted_str .= "'". $_val ."'";
			}
		}

		if ((isset($chat_session['count_messages'])) && ($chat_session['count_messages'] == true)) {
			$sql_str = $wpdb->prepare("SELECT count(*) as messages_count FROM `".WPMUDEV_Chat::tablename('message')."` WHERE ".
				" blog_id = %s ".
				" AND chat_id = %s ".
				" AND session_type=%s ".
				" AND archived IN ( ". $archived_str ." ) ".
				" AND deleted IN ( ". $deleted_str ." ) ".
				" AND timestamp > '". $since_timestamp ."' ".
				" ORDER BY timestamp DESC", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type']);
			return $wpdb->get_row($sql_str);

		} else if ($chat_session['end'] > 0) {
			$sql_str = $wpdb->prepare("SELECT * FROM `".WPMUDEV_Chat::tablename('message')."` WHERE ".
				" blog_id = %s ".
				" AND chat_id = %s ".
				" AND session_type=%s ".
				" AND archived IN ( ". $archived_str ." ) ".
				" AND deleted IN ( ". $deleted_str ." ) ".
				" AND timestamp BETWEEN '". $since_timestamp ."' AND '". $end_timestamp ."' ".
				" ORDER BY timestamp ASC", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type']);
		} else {
			$sql_str = $wpdb->prepare("SELECT * FROM `".WPMUDEV_Chat::tablename('message')."` WHERE ".
				" blog_id = %s ".
				" AND chat_id = %s ".
				" AND session_type=%s ".
				" AND archived IN ( ". $archived_str ." ) ".
				" AND deleted IN ( ". $deleted_str ." ) ".
				" AND timestamp > '". $since_timestamp ."' ".
				" ORDER BY timestamp DESC", $chat_session['blog_id'], $chat_session['id'], $chat_session['session_type']);
			if (intval($chat_session['log_limit']) > 0) {
				$sql_str .= " LIMIT ". intval($chat_session['log_limit']);
			}
		}
		return $wpdb->get_results( $sql_str );
	}

	/**
	 * Send the message
	 *
	 * @global	object	$wpdb
	 * @global	int	$blog_id
	 * @param	int	$chat_id	Chat ID
	 * @param	string	$name		Name
	 * @param	string	$avatar		URL or e-mail
	 * @param	string	$message	Payload message
	 * @param	string	$moderator	Moderator
	 */
	function chat_session_send_message($message, $chat_session) {
		global $wpdb;

		//$wpdb->real_escape = true;

		$time_stamp = date("Y-m-d H:i:s");

		$blog_id 		= $chat_session['blog_id'];
		$chat_id 		= $chat_session['id'];
		$session_type 	= trim($chat_session['session_type']);
		$name 			= trim($this->chat_auth['name']);
		$user_avatar 	= trim($this->chat_auth['avatar']);
		$auth_hash 		= trim($this->chat_auth['auth_hash']);

		$ip_address 	= (isset($_SERVER['HTTP_X_FORWARD_FOR'])) ? $_SERVER['HTTP_X_FORWARD_FOR'] : $_SERVER['REMOTE_ADDR'];
		$message 		= trim($message);
		$moderator_str 	= trim($chat_session['moderator']);

		if (empty($message)) {
			return false;
		}

		$sql_str = $wpdb->prepare("INSERT INTO ".WPMUDEV_Chat::tablename('message')."
					(`blog_id`, `chat_id`, `session_type`, `timestamp`, `name`, `avatar`, `auth_hash`, `ip_address`, `message`, `moderator`, `deleted`, `archived`, `log_id`) VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 0);",
						$blog_id, $chat_id, $session_type, $time_stamp, $name, $user_avatar, $auth_hash, $ip_address, $message, $moderator_str, 'no', 'no', 0);
		//return $wpdb->query($sql_str);
		$ret = $wpdb->query($sql_str);
		//echo "DEBUG: sql_str=[". $sql_str ."<br />";
		//echo "ret=[". $ret ."]<br />";
		//echo "wpdb<pre>"; print_r($wpdb); echo "</pre>";
	}

	/**
	 * Get a list of archives for the given chat
	 *
	 * @global	object	$wpdb
	 * @global	int		$blog_id
	 * @param	int		$chat_id	Chat ID
	 * @return	array				List of archives
	 */
	function get_archives($chat_session) {
		global $wpdb;

		$sql_str = $wpdb->prepare("SELECT * FROM `".WPMUDEV_Chat::tablename('log')."` WHERE blog_id = %d AND chat_id = %s ORDER BY created ASC;", $chat_session['blog_id'], $chat_session['id']);
		return $wpdb->get_results( $sql_str );
	}


	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_get_session_status($chat_session) {

		if (!empty($chat_session['id'])) {
			$options = get_option('wpmudev-chat-session-status', array());
			if (!isset($options[$chat_session['id']]))  {
				if (isset($chat_session['session_status'])) {
					$this->chat_set_session_status($chat_session['id'], $chat_session['session_status']);
					return $chat_session['session_status'];
				} else if (isset($this->_chat_options[$chat_session['session_type']]['session_status'])) {
					$this->chat_set_session_status($chat_session['id'], $this->_chat_options[$chat_session['session_type']]['session_status']);
					return $this->_chat_options[$chat_session['session_type']]['session_status'];
				} else {
					return "open";
				}
			} else {
				if (($options[$chat_session['id']] == "open") || ($options[$chat_session['id']] == "closed")) return $options[$chat_session['id']];
				else return "open";
			}
		}
		return "open";
	}

	/**
	 *
	 *
	 * @global	none
	 * @param	none
	 * @return	none
	 */
	function chat_set_session_status($chat_id, $chat_session_status) {
		$options = get_option('wpmudev-chat-session-status', array());
		$options[$chat_id] = $chat_session_status;
		return update_option('wpmudev-chat-session-status', $options);
	}

}
} // End of class_exists()

// Lets get things started
$wpmudev_chat = new WPMUDEV_Chat();