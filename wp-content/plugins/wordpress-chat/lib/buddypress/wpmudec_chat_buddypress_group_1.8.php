<?php
class WPMUDEV_Chat_BuddyPress extends BP_Group_Extension {
    /**
     * Here you can see more customization of the config options
     */
	//var static $settings_slug;
	const settings_slug = 'wpmudev_chat_bp_group';

    function __construct() {
		global $bp, $wpmudev_chat;

		$wpmudev_chat->load_configs();

        $this->create_step_position 	= 21;
        $this->nav_item_position 		= 31;
		$this->slug 					= $wpmudev_chat->get_option('bp_menu_slug', 'global');
		$this->name 					= $wpmudev_chat->get_option('bp_menu_label', 'global');
		$this->enable_nav_item 			= false;

		if (isset($bp->groups->current_group->id)) {
			if ( groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

				// First check if the old value
				$enabled = groups_get_groupmeta( $bp->groups->current_group->id, 'wpmudevchatbpgroupenable' );
				if (!empty($enabled)) {
					echo "here!<br />";
					groups_delete_groupmeta( $bp->groups->current_group->id, 'wpmudevchatbpgroupenable' );
					groups_update_groupmeta( $bp->groups->new_group_id, self::settings_slug .'_enable', $enabled );
				}

				$enabled = groups_get_groupmeta( $bp->groups->current_group->id, self::settings_slug .'_enable', true );
				if ($enabled == "yes") {
					$this->enable_nav_item = true;
				}
			}
		}

    	$args = array(
        	'slug' 					=> 	$this->slug,
        	'name' 					=> 	$this->name,
			'enable_nav_item'		=>	$this->enable_nav_item,
        	'nav_item_position' 	=> 	$this->nav_item_position,
        	'screens' 				=> 	array(
            								'edit' => array(
                								'name' => $this->name,
                								// Changes the text of the Submit button
                								// on the Edit page
                								'submit_text' => __('Submit', $wpmudev_chat->translation_domain),
            								),
            								'create' => array(
												'position' => $this->create_step_position,
            								),
        								),
    								);
        parent::init( $args );
    }
	public static function show_enable_chat_button() {
		global $bp, $wpmudev_chat;

		$checked = '';

		if (isset($bp->groups->current_group->id)) {
			$enabled = groups_get_groupmeta( $bp->groups->current_group->id, self::settings_slug .'_enable' );
			if ($enabled == "yes")
				$checked = ' checked="checked" ';
		}

		?><p><label for="<?php echo self::settings_slug; ?>_enable"><input type="checkbox" name="<?php
			echo self::settings_slug; ?>_enable" <?php echo $checked; ?>
			id="<?php echo self::settings_slug; ?>_enable" /> <?php _e("Enable Group Chat", $wpmudev_chat->translation_domain); ?></label></p><?php
	}

    function display() {
		global $bp, $wpmudev_chat;

		if ( groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

			$chat_id = 'bp-group-'.$bp->groups->current_group->id;
			//echo "chat_id=[". $chat_id ."]<br />";

			$atts = groups_get_groupmeta( $bp->groups->current_group->id, self::settings_slug );
			if (empty($atts)) {

				$atts = array(
					'id' 						=> 	$chat_id,
					'session_type'				=> 	'bp-group',
					'box_input_position'		=>	'top',
					'box-width'					=>	'100%',
					'users_list_show'			=>	'avatar',
					'users_list_position'		=>	'right',
					'users_list_width'			=>	'30%',
					'users_list_avatar_width'	=>	'50',

				);
			}

			// We changed the key because it was too long for the wp_options optin_name field
			if ((!isset($atts['id'])) || ($atts['id'] != $chat_id)) {
				$atts['id']  = $chat_id;
			}
			echo $wpmudev_chat->process_chat_shortcode($atts);
		} else {
			?><p><?php _e('You must be a member of this group to use Chat', $wpmudev_chat->translation_domain); ?></p><?php
		}
    }

    function settings_screen( $group_id ) {
		global $wpmudev_chat, $bp;

	 	if ( (groups_is_user_mod( $bp->loggedin_user->id, $bp->groups->current_group->id ))
	   	  || (groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ))
	      || (is_super_admin()) ) {

			self::show_enable_chat_button();

			$atts = groups_get_groupmeta( $bp->groups->current_group->id, self::settings_slug );
			if (!empty($atts)) {
				$wpmudev_chat->_chat_options['bp-group'] = $atts;
			}

			include_once( $wpmudev_chat->_chat_plugin_settings['plugin_path'] . '/lib/wpmudev_chat_admin_panels.php' );
			$admin_panels = new wpmudev_chat_admin_panels(  );

			$admin_panels->chat_settings_panel_buddypress();

			// not sure why Farbtastic will not work with wp_register/enqueue_script
			?>
			<link rel='stylesheet' id='farbtastic-css'  href='<?php echo admin_url(); ?>/css/farbtastic.css?ver=1.3u1'
				type='text/css' media='all' />
			<script type='text/javascript' src='<?php echo admin_url(); ?>/js/farbtastic.js'></script>
			<?php
			$wpmudev_chat->tips->initialize();
		}

    }

    function settings_screen_save( $group_id ) {
        global $bp, $wpdb;


		if ( (!isset($_POST['wpmudev_chat_settings_save_wpnonce']))
		  || (!wp_verify_nonce($_POST['wpmudev_chat_settings_save_wpnonce'], 'wpmudev_chat_settings_save')) ) {
			return false;
		}

		// Controls our menu visibility. See the __construct logic.
		if ((isset($_POST[self::settings_slug .'_enable'])) && ($_POST[self::settings_slug .'_enable'] == "on")) {
			$enabled = "yes";
		} else {
			$enabled = "no";
		}
		groups_update_groupmeta( $bp->groups->current_group->id, self::settings_slug .'_enable', $enabled );

        if ( !isset( $_POST['chat'] ) )
            return false;

		if ( (groups_is_user_mod( $bp->loggedin_user->id, $bp->groups->current_group->id ))
	   	  || (groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ))
	      || (is_super_admin()) ) {

			$success = $chat_section = false;

			$chat_settings 				= $_POST['chat'];

			if (isset($chat_settings['section'])) {
				$chat_section = $chat_settings['section'];
				unset($chat_settings['section']);
			}
			$chat_settings['session_type']	= 'bp-group';
			$chat_settings['id'] 			= 'wpmudev-chat-bp-group-'.$bp->groups->current_group->id;
			$chat_settings['blog_id'] 		= $wpdb->blogid;

			groups_update_groupmeta( $bp->groups->current_group->id, self::settings_slug, $chat_settings );

            /* Insert your edit screen save code here */
			$success = true;

            /* To post an error/success message to the screen, use the following */
            if ( !$success )
                bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
            else
                bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

		}
    }

    /**
     * create_screen() is an optional method that, when present, will
     * be used instead of settings_screen() in the context of group
     * creation.
     *
     * Similar overrides exist via the following methods:
     *   * create_screen_save()
     *   * edit_screen()
     *   * edit_screen_save()
     *   * admin_screen()
     *   * admin_screen_save()
     */
    function create_screen( $group_id ) {
        $setting = groups_get_groupmeta( $group_id, 'group_extension_example_2_setting' );
		self::show_enable_chat_button();
    }

	function create_screen_save() {
        global $bp;

		if ((isset($_POST[self::settings_slug .'_enable'])) && ($_POST[self::settings_slug .'_enable'] == "on")) {
            groups_update_groupmeta( $bp->groups->new_group_id, self::settings_slug .'_enable', 'yes' );
		} else {
			groups_update_groupmeta( $bp->groups->new_group_id, self::settings_slug .'_enable', 'no' );
		}
    }
}