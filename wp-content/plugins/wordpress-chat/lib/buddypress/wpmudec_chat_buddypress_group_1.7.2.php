<?php
class WPMUDEV_Chat_BuddyPress extends BP_Group_Extension {

    function __construct() {
		global $bp, $wpmudev_chat;

		$wpmudev_chat->load_configs();
        $this->settings_slug = 'wpmudev_chat_bp_group';

		$this->slug = $wpmudev_chat->get_option('bp_menu_slug', 'global');
		$this->name = $wpmudev_chat->get_option('bp_menu_label', 'global');

        $this->create_step_position = 21;
        $this->nav_item_position = 31;

		$this->enable_nav_item = false;

		if (isset($bp->groups->current_group->id)) {
			if ( groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

				// First check if the old value
				$enabled = groups_get_groupmeta( $bp->groups->current_group->id, 'wpmudevchatbpgroupenable' );
				if (!empty($enabled)) {
					groups_delete_groupmeta( $bp->groups->current_group->id, 'wpmudevchatbpgroupenable' );
					groups_update_groupmeta( $bp->groups->new_group_id, $this->settings_slug .'_enable', $enabled );
				}

				$enabled = groups_get_groupmeta( $bp->groups->current_group->id, $this->settings_slug .'_enable' );
				if ($enabled == "yes") {
					$this->enable_nav_item = true;

				}

				$atts = groups_get_groupmeta( $bp->groups->current_group->id, $this->settings_slug );
				//if (!empty($atts)) {
					//if ((isset($atts['bp_menu_slug'])) && (!empty($atts['bp_menu_slug']))) {
					//	$this->slug = $atts['bp_menu_slug'];
					//}
					//if ((isset($atts['bp_menu_label'])) && (!empty($atts['bp_menu_label']))) {
					//	$this->nav_item_name = $atts['bp_menu_label'];
					//}
				//}
				//echo "slug=[". $this->slug ."]<br />";

			}
		}
    }

    /**
     * The content of the My Group Extension tab of the group creation process
     *
     * Don't need a group creation step? In the __construct() method:
     *
     *     $this->enable_create_step = false;
     */
    function create_screen() {
        if ( !bp_is_group_creation_step( $this->slug ) )
            return false;

		$this->show_enable_chat_button();
        wp_nonce_field( 'groups_create_save_' . $this->slug );
    }

	function show_enable_chat_button() {
		global $bp;

		$checked = '';

		if (isset($bp->groups->current_group->id)) {
			$enabled = groups_get_groupmeta( $bp->groups->current_group->id, $this->settings_slug .'_enable' );
			if ($enabled == "yes")
				$checked = ' checked="checked" ';
		}

		?><p><label for="<?php echo $this->settings_slug; ?>_enable"><input type="checkbox" name="<?php echo $this->settings_slug; ?>_enable" <?php echo $checked; ?>
			id="<?php echo $this->settings_slug; ?>_enable" /> <?php _e("Enable Group Chat", $wpmudev_chat->translation_domain); ?></label></p><?php
	}


    /**
     * The routine run after the user clicks Continue from your creation step
     *
     * You'll be pulling your data out of the $_POST global. Be sure to
     * sanitize as necessary.
     */
    function create_screen_save() {
        global $bp;

        check_admin_referer( 'groups_create_save_' . $this->slug );

		if ((isset($_POST[$this->settings_slug .'_enable'])) && ($_POST[$this->settings_slug .'_enable'] == "on")) {
            groups_update_groupmeta( $bp->groups->new_group_id, $this->settings_slug .'_enable', 'yes' );
		} else {
			groups_update_groupmeta( $bp->groups->new_group_id, $this->settings_slug .'_enable', 'no' );
		}
    }

    /**
     * The content of the My Group Extension tab of the group admin
     */
    function edit_screen() {
		global $wpmudev_chat, $bp;
        if ( !bp_is_group_admin_screen( $this->slug ) )
            return false;

	 	if ( (groups_is_user_mod( $bp->loggedin_user->id, $bp->groups->current_group->id ))
	   	  || (groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ))
	      || (is_super_admin()) ) {

			$this->show_enable_chat_button();

			$atts = groups_get_groupmeta( $bp->groups->current_group->id, $this->settings_slug );
			if (!empty($atts)) {
				$wpmudev_chat->_chat_options['bp-group'] = $atts;
			}

			include_once( $wpmudev_chat->_chat_plugin_settings['plugin_path'] . '/lib/wpmudev_chat_admin_panels.php' );
			$admin_panels = new wpmudev_chat_admin_panels( $this );

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

    /**
     * The routine run after the user clicks Save from your admin tab
     *
     * You'll be pulling your data out of the $_POST global. Be sure to
     * sanitize as necessary.
     */
    function edit_screen_save() {
        global $bp, $wpdb;

        if ( !bp_is_group_admin_screen( $this->slug ) )
            return false;

		if ( (!isset($_POST['wpmudev_chat_settings_save_wpnonce']))
		  || (!wp_verify_nonce($_POST['wpmudev_chat_settings_save_wpnonce'], 'wpmudev_chat_settings_save')) ) {
			return false;
		}

		// Controls our menu visibility. See the __construct logic.
		if ((isset($_POST[$this->settings_slug .'_enable'])) && ($_POST[$this->settings_slug .'_enable'] == "on")) {
			$enabled = "yes";
		} else {
			$enabled = "no";
		}
		groups_update_groupmeta( $bp->groups->current_group->id, $this->settings_slug .'_enable', $enabled );

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

			groups_update_groupmeta( $bp->groups->current_group->id, $this->settings_slug, $chat_settings );

            /* Insert your edit screen save code here */
			$success = true;

            /* To post an error/success message to the screen, use the following */
            if ( !$success )
                bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
            else
                bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

		}
        bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );
    }

    /**
     * Use this function to display the actual content of your group extension when the nav item is selected
     */
    function display() {
		global $bp, $wpmudev_chat;

		if ( groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {

			$chat_id = 'bp-group-'.$bp->groups->current_group->id;
			$atts = groups_get_groupmeta( $bp->groups->current_group->id, $this->settings_slug );
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
			if ($atts['id'] != $chat_id) {
				$atts['id']  = $chat_id;
			//	global $wpdb;
			//
			//	$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('log') ." SET chat_id=%s WHERE chat_id=%s;", $chat_id, $atts['id']);
			//	$wpdb->query($sql_str);
			//
			//	$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('message') ." SET chat_id=%s WHERE chat_id=%s;", $chat_id, $atts['id']);
			//	$wpdb->query($sql_str);
			//
			//	$sql_str = $wpdb->prepare("UPDATE ". WPMUDEV_Chat::tablename('users') ." SET chat_id=%s WHERE chat_id=%s;", $chat_id, $atts['id']);
			//	$wpdb->query($sql_str);
			//
			//	$atts['id']  = $chat_id;
			}
			echo $wpmudev_chat->process_chat_shortcode($atts);
		} else {
			?><p><?php _e('You must be a member of this group to use Chat', $wpmudev_chat->translation_domain); ?></p><?php
		}
    }

    /**
     * If your group extension requires a meta box in the Dashboard group admin,
     * use this method to display the content of the metabox
     *
     * As in the case of create_screen() and edit_screen(), it may be helpful
     * to abstract shared markup into a separate method.
     *
     * This is an optional method. If you don't need/want a metabox on the group
     * admin panel, don't define this method in your class.
     *
     * <a href="http://buddypress.org/community/members/param/" rel="nofollow">@param</a> int $group_id The numeric ID of the group being edited. Use
     *   this id to pull up any relevant metadata
     */
/*
    function admin_screen( $group_id ) {
        ?>

        <p>The HTML for my admin panel.</p>

        <?php
    }
*/
    /**
     * The routine run after the group is saved on the Dashboard group admin screen
     *
     * <a href="http://buddypress.org/community/members/param/" rel="nofollow">@param</a> int $group_id The numeric ID of the group being edited. Use
     *   this id to pull up any relevant metadata
     */
    function admin_screen_save( $group_id ) {
        // Grab your data out of the $_POST global and save as necessary
    }

/*
    function widget_display() { ?>
        <div class=&quot;info-group&quot;>
            <h4><?php echo esc_attr( $this->name ) ?></h4>
            <p>
                You could display a small snippet of information from your group extension here. It will show on the group
                home screen.
            </p>
        </div>
        <?php
    }
*/
}