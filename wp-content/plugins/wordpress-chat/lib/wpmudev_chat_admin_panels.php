<?php
if ( !class_exists( "wpmudev_chat_admin_panels" ) ) {
	class wpmudev_chat_admin_panels {

		/**
		 * The old-style PHP Class constructor. Used when an instance of this class
	 	 * is needed. If used (PHP4) this function calls the PHP5 version of the constructor.
		 *
		 * @since 2.0.0
		 * @param none
		 * @return self
		 */
		function wpmudev_chat_admin_panels( ) {
	        $this->__construct();
		}

		/**
		 * The PHP5 Class constructor. Used when an instance of this class is needed.
		 * Sets up the initial object environment and hooks into the various WordPress
		 * actions and filters.
		 *
		 * @since 1.0.0
		 * @uses $this->_settings array of our settings
		 * @uses $this->_messages array of admin header message texts.
		 * @param none
		 * @return self
		 */
		function __construct() {

		}

		function chat_settings_panel_page() {
			global $wpmudev_chat;

			$form_section = "page";
			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Page', $wpmudev_chat->translation_domain); ?></h2>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e('The following settings are used to control the inline chat shortcodes applied to Posts, Pages, etc. You can setup default options here. As well as override these default options with shortcode parameters on the specific Post, Page, etc.', $wpmudev_chat->translation_domain); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel"><span><?php
								_e('Box Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
								_e('Message Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
								_e('Message Input', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
								_e('Users List', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
								_e('Authentication', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_timymce_buttom_tab"><a href="#wpmudev_chat_timymce_buttom_panel"><span><?php
								_e('WYSIWYG Button', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
								_e('Advanced', $wpmudev_chat->translation_domain); ?></span></a></li>
						</ul>
						<div id="chat_box_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_container($form_section); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_wrapper($form_section); ?>
							<?php wpmudev_chat_form_section_messages_rows($form_section); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_input($form_section); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php wpmudev_chat_users_list($form_section); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_login_options($form_section); ?>
							<?php wpmudev_chat_form_section_login_view_options($form_section); ?>
							<?php wpmudev_chat_form_section_moderator_roles($form_section); ?>
						</div>
						<div id="wpmudev_chat_timymce_buttom_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_tinymce_button_post_types($form_section); ?>
							<?php wpmudev_chat_form_section_tinymce_button_roles($form_section); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_logs($form_section); ?>
							<?php wpmudev_chat_form_section_logs_limit($form_section); ?>
							<?php wpmudev_chat_form_section_session_messages($form_section); ?>

							<?php /* if ($wpmudev_chat->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
								wpmudev_chat_form_section_blocked_ip_addresses($form_section);
							} */ ?>
							<?php if ($wpmudev_chat->get_option('blocked_words_active', 'banned') == "enabled") {
								wpmudev_chat_form_section_blocked_words($form_section);
							} ?>

						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>
				</form>
			</div>
			<?php
		}

		function chat_settings_panel_site() {
			global $wpmudev_chat;

			$form_section = "site";

			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Site', $wpmudev_chat->translation_domain); ?></h2>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">
					<?php //settings_fields('chat'); ?>

					<p><?php _e('The following settings are used to control the bottom corner and private chat area settings.', $wpmudev_chat->translation_domain); ?></p>
					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_bottom_corner_tab"><a href="#chat_bottom_corner_panel" class="current"><span><?php
								_e('Bottom Corner', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel" class="current"><span><?php
								_e('Box Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_box_position_tab"><a href="#chat_box_position_panel"><span><?php
								_e('Box Position', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
								_e('Message Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
								_e('Message Input', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
								_e('Users List', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
								_e('Authentication', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
								_e('Advanced', $wpmudev_chat->translation_domain); ?></span></a></li>
						</ul>
						<div id="chat_bottom_corner_panel" class="panel current">
							<?php wpmudev_chat_form_section_bottom_corner($form_section); ?>
						</div>
						<div id="chat_box_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_container($form_section); ?>
						</div>
						<div id="chat_box_position_panel" class="panel">
							<?php wpmudev_chat_form_section_site_position($form_section); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_wrapper($form_section); ?>
							<?php wpmudev_chat_form_section_messages_rows($form_section); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_input($form_section); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php wpmudev_chat_users_list($form_section); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_login_options($form_section); ?>
							<?php wpmudev_chat_form_section_login_view_options($form_section); ?>
							<?php wpmudev_chat_form_section_moderator_roles($form_section); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php //wpmudev_chat_form_section_logs($form_section); ?>
							<?php wpmudev_chat_form_section_logs_limit($form_section); ?>
							<?php wpmudev_chat_form_section_session_messages($form_section); ?>

							<?php /* if ($wpmudev_chat->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
								wpmudev_chat_form_section_blocked_ip_addresses($form_section);
							} */ ?>
							<?php if ($wpmudev_chat->get_option('blocked_words_active', 'banned') == "enabled") {
								wpmudev_chat_form_section_blocked_words($form_section);
							} ?>
							<?php wpmudev_chat_form_section_block_urls_site($form_section); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>

				</form>
			</div>
			<?php
		}

		function chat_settings_panel_widget() {
			global $wpmudev_chat;

			$form_section = "widget";

			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Widget', $wpmudev_chat->translation_domain); ?></h2>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">
					<?php //settings_fields('chat'); ?>

					<p><?php _e('The following settings are used to control all Chat Widgets.', $wpmudev_chat->translation_domain); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel" class="current"><span><?php
								_e('Box Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
								_e('Message Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
								_e('Message Input', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
								_e('Users List', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
								_e('Authentication', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
								_e('Advanced', $wpmudev_chat->translation_domain); ?></span></a></li>
						</ul>
						<div id="chat_box_appearance_panel" class="panel current">
							<?php wpmudev_chat_form_section_container($form_section); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_wrapper($form_section); ?>
							<?php wpmudev_chat_form_section_messages_rows($form_section); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_input($form_section); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php wpmudev_chat_users_list($form_section); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_login_options($form_section); ?>
							<?php wpmudev_chat_form_section_login_view_options($form_section); ?>
							<?php wpmudev_chat_form_section_moderator_roles($form_section); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_logs_limit($form_section); ?>
							<?php wpmudev_chat_form_section_session_messages($form_section); ?>

							<?php if ($wpmudev_chat->get_option('blocked_words_active', 'banned') == "enabled") {
								wpmudev_chat_form_section_blocked_words($form_section);
							} ?>
							<?php wpmudev_chat_form_section_block_urls_widget($form_section); ?>

						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>
				</form>
			</div>
			<?php
		}


		function chat_settings_panel_buddypress() {
			global $wpmudev_chat;

			$form_section = "bp-group";
			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Group Chat Settings', $wpmudev_chat->translation_domain); ?></h2>

				<?php if (version_compare(bp_get_version(), '1.8') < 0) { ?>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">
				<?php } ?>
					<?php //settings_fields('chat'); ?>

					<?php
						include_once( dirname(dirname(__FILE__)) . '/lib/wpmudev_chat_form_sections.php');
						include_once( dirname(dirname(__FILE__)) . '/lib/wpmudev_chat_admin_panels_help.php' );
					?>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel"><span><?php
								_e('Box Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
								_e('Message Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
								_e('Message Input', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
								_e('Users List', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
								_e('Advanced', $wpmudev_chat->translation_domain); ?></span></a></li>
						</ul>
						<div id="chat_box_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_information($form_section); ?>
							<?php wpmudev_chat_form_section_container($form_section); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_wrapper($form_section); ?>
							<?php wpmudev_chat_form_section_messages_rows($form_section); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_input($form_section); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php wpmudev_chat_users_list($form_section); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php //wpmudev_chat_form_section_logs($form_section); ?>
							<?php wpmudev_chat_form_section_logs_limit($form_section); ?>
							<?php wpmudev_chat_form_section_session_messages($form_section); ?>

							<?php /* if ($wpmudev_chat->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
								wpmudev_chat_form_section_blocked_ip_addresses($form_section);
							} */ ?>
							<?php if ($wpmudev_chat->get_option('blocked_words_active', 'banned') == "enabled") {
								wpmudev_chat_form_section_blocked_words($form_section);
							} ?>

						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>

				<?php if (version_compare(bp_get_version(), '1.8') < 0) { ?>
					</form>
				<?php } ?>
				<style type="text/css">

					#wpmudev-chat-wrap .ui-tabs-panel.ui-widget-content {
					    background-color: <?php echo $wpmudev_chat->get_option('bp_form_background_color', 'global'); ?> !important;
					}
					#wpmudev-chat-wrap fieldset table td.chat-label-column {
						color: <?php echo $wpmudev_chat->get_option('bp_form_label_color', 'global'); ?> !important;
					}
				</style>
			</div>
			<?php
		}

		function chat_settings_panel_global() {
			global $wpmudev_chat;

			$buddypress_active = false;
			if (is_plugin_active('buddypress/bp-loader.php')) {
				$buddypress_active = true;
			} else if ( (is_multisite()) && (is_plugin_active_for_network('buddypress/bp-loader.php')) ) {
				$buddypress_active = true;
			}

			$form_section = "global";
			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Common', $wpmudev_chat->translation_domain); ?></h2>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">

					<p><?php _e('The following settings are used for all chat session types (Page, Site, Private, Support).',
						$wpmudev_chat->translation_domain); ?></p>

					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="wpmudev_chat_interval_tab"><a href="#wpmudev_chat_interval_panel"><span><?php
								_e('Poll Intervals', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_google_plus_tab"><a href="#wpmudev_chat_google_plus_panel"><span><?php
								_e('Google+', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_facebook_tab"><a href="#wpmudev_chat_facebook_panel"><span><?php
								_e('Facebook', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_twitter_tab"><a href="#wpmudev_chat_twitter_panel"><span><?php
								_e('Twitter', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_blocked_ip_addresses_tab"><a href="#wpmudev_chat_blocked_ip_addresses_panel"><span><?php
								_e('Block IP/User', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_blocked_words_tab"><a href="#wpmudev_chat_blocked_words_panel"><span><?php
								_e('Blocked Words', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="wpmudev_chat_wp_tab"><a href="#wpmudev_chat_blocked_urls"><span><?php
								_e('Blocked URLs', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_wpadmin_tab"><a href="#chat_wpadmin_panel"><span><?php
								_e('WPAdmin', $wpmudev_chat->translation_domain); ?></span></a></li>
							<?php if ($buddypress_active) { ?>
							<li><a href="#wpmudev_chat_buddypress_panel"><span><?php _e('BuddyPress', $wpmudev_chat->translation_domain); ?></span></a></li>
							<?php } ?>
						</ul>
						<div id="wpmudev_chat_interval_panel" class="chat_panel current">
							<?php wpmudev_chat_form_section_polling_interval($form_section); ?>
							<?php wpmudev_chat_form_section_polling_content($form_section); ?>
						</div>
						<div id="wpmudev_chat_google_plus_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_google_plus($form_section); ?>
						</div>
						<div id="wpmudev_chat_facebook_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_facebook($form_section); ?>
						</div>
						<div id="wpmudev_chat_twitter_panel" class="chat_panel current">
							<?php wpmudev_chat_form_section_twitter($form_section); ?>
						</div>
						<div id="wpmudev_chat_blocked_ip_addresses_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_blocked_ip_addresses_global($form_section); ?>
							<?php wpmudev_chat_form_section_block_users_global('global'); ?>
						</div>
						<div id="wpmudev_chat_blocked_words_panel" class="chat_panel">
							<?php wpmudev_chat_form_section_blocked_words_global('banned'); ?>
						</div>
						<div id="wpmudev_chat_blocked_urls" class="chat_panel">
							<?php wpmudev_chat_form_section_blocked_urls_admin('global'); ?>
							<?php wpmudev_chat_form_section_blocked_urls_front('global'); ?>
						</div>
						<div id="chat_wpadmin_panel" class="panel">
							<?php wpmudev_chat_form_section_wpadmin($form_section); ?>
						</div>
						<?php if ($buddypress_active) { ?>
						<div id="wpmudev_chat_buddypress_panel" class="chat_panel">
							<p class="info"><?php _e('This section control how Chat works within the BuddyPress system. These are global settings and effect all Groups', $wpmudev_chat->translation_domain); ?></p>
							<?php wpmudev_chat_form_section_buddypress_group_information($form_section); ?>
							<?php wpmudev_chat_form_section_buddypress_group_hide_site($form_section); ?>
							<?php wpmudev_chat_form_section_buddypress_group_hide_widget($form_section); ?>
							<?php wpmudev_chat_form_section_buddypress_group_admin_colors($form_section); ?>
						</div>
						<?php } ?>

					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>
				</form>
			</div>
			<?php
		}

		function chat_settings_panel_session_logs() {
			global $wpdb, $wpmudev_chat;

			if ((isset($_GET['action'])) && ($_GET['action'] == "details")) {
				?>
				<div id="wpmudev-chat-messages-listing-panel" class="wrap wpmudev-chat-wrap wpmudev-chat-wrap-settings-page">
					<?php screen_icon('wpmudev-chat'); ?>
					<h2><?php _ex("Chat Session Messages", "Page Title", $wpmudev_chat->translation_domain); ?></h2>
					<p><?php _ex("", 'page description', $wpmudev_chat->translation_domain); ?></p>
					<?php
						require_once( dirname(__FILE__) . '/wpmudev_chat_admin_session_messages.php');
						$this->_logs_table = new WPMUDEVChat_Session_Messages_Table( );
						$this->_logs_table->prepare_items();
					?>
					<form id="wpmudev-chat-edit-listing" action="?page=chat_session_logs&amp;action=details"
						 method="post">
						<input type="hidden" name="chat-action" value="delete-bulk" />
						<?php wp_nonce_field('chat-delete', 'chat-noonce-field'); ?>
						<?php $this->_logs_table->display(); ?>
					</form>
				</div>
				<?php
			} else {
				?>
				<div id="wpmudev-chat-messages-listing-panel" class="wrap wpmudev-chat-wrap wpmudev-chat-wrap-settings-page">
					<?php screen_icon('wpmudev-chat'); ?>
					<h2><?php _ex("Chat Session Logs", "Page Title", $wpmudev_chat->translation_domain); ?></h2>
					<p><?php _ex("", 'page description', $wpmudev_chat->translation_domain); ?></p>
					<?php
						require_once( dirname(__FILE__) . '/wpmudev_chat_admin_session_logs.php');
						$this->_logs_table = new WPMUDEVChat_Session_Logs_Table( );
						$this->_logs_table->prepare_items();
					?>
					<form id="chat-edit-listing" action="?page=chat_session_logs"
						 method="post">
						<input type="hidden" name="chat-action" value="delete-bulk" />
						<?php wp_nonce_field('chat-delete', 'chat-noonce-field'); ?>
						<?php $this->_logs_table->display(); ?>
					</form>
				</div>
				<?php
			}
		}

		function chat_settings_panel_page_network() {
			global $wpmudev_chat;

			$form_section = "page";
			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Page Network', $wpmudev_chat->translation_domain); ?></h2>

				<p><?php _e('This is a placeholder page. Current there are no Network settings for Chat Pages.', $wpmudev_chat->translation_domain); ?></p>

			</div>
			<?php
		}

/*
		function chat_settings_panel_network_global() {
			global $wpmudev_chat;

			$form_section = "network-site";

			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Global Network', $wpmudev_chat->translation_domain); ?></h2>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">
					<?php //settings_fields('chat'); ?>

					<p><?php _e('The following settings are used to control the bottom corner for all sites within the Multisite environment',
					 	$wpmudev_chat->translation_domain); ?></p>
					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_bottom_corner_tab"><a href="#chat_bottom_corner_panel" class="current"><span><?php
								_e('Bottom Corner', $wpmudev_chat->translation_domain); ?></span></a></li>
						</ul>
						<div id="chat_bottom_corner_panel" class="panel current">
							<?php wpmudev_chat_form_section_bottom_corner_network($form_section); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>

				</form>
			</div>
			<?php
		}
*/

		function chat_settings_panel_network_site() {
			global $wpmudev_chat;

			$form_section = "network-site";

			?>
			<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-settings-page">
				<h2><?php _e('Chat Settings Network', $wpmudev_chat->translation_domain); ?></h2>
				<form method="post" id="wpmudev-chat-settings-form" action="?page=<?php echo $_GET['page']; ?>">
					<?php //settings_fields('chat'); ?>

					<p><?php _e('The following settings are used to control the bottom corner for all sites within the Multisite environment. This bottom corner chat is global meaning messages are the same across all sites within the Multisite network URLs. Once enabled this will add a new bottom corner chat similar and in addition to the local bottom corner chat maintained by the local site admin.', $wpmudev_chat->translation_domain); ?></p>
					<div id="chat_tab_pane" class="chat_tab_pane">
						<ul>
							<li id="chat_bottom_corner_tab"><a href="#chat_bottom_corner_panel" class="current"><span><?php
								_e('Bottom Corner', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_box_appearance_tab"><a href="#chat_box_appearance_panel" class="current"><span><?php
								_e('Box Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_appearance_tab"><a href="#chat_messages_appearance_panel"><span><?php
								_e('Message Appearance', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_messages_input_tab"><a href="#chat_messages_input_panel"><span><?php
								_e('Message Input', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_users_list_tab"><a href="#chat_users_list_panel"><span><?php
								_e('Users List', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_authentication_tab"><a href="#chat_authentication_panel"><span><?php
								_e('Authentication', $wpmudev_chat->translation_domain); ?></span></a></li>
							<li id="chat_advanced_tab"><a href="#chat_advanced_panel"><span><?php
								_e('Advanced', $wpmudev_chat->translation_domain); ?></span></a></li>
						</ul>
						<div id="chat_bottom_corner_panel" class="panel current">
							<?php wpmudev_chat_form_section_bottom_corner($form_section); ?>
						</div>
						<div id="chat_box_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_container($form_section); ?>
						</div>
						<div id="chat_messages_appearance_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_wrapper($form_section); ?>
							<?php wpmudev_chat_form_section_messages_rows($form_section); ?>
						</div>
						<div id="chat_messages_input_panel" class="panel">
							<?php wpmudev_chat_form_section_messages_input($form_section); ?>
						</div>
						<div id="chat_users_list_panel" class="panel">
							<?php wpmudev_chat_users_list($form_section); ?>
						</div>
						<div id="chat_authentication_panel" class="chat_panel">
							<?php //wpmudev_chat_form_section_login_options($form_section); ?>
							<?php //wpmudev_chat_form_section_login_view_options($form_section); ?>
							<?php wpmudev_chat_form_section_moderator_roles($form_section); ?>
						</div>
						<div id="chat_advanced_panel" class="chat_panel">
							<?php //wpmudev_chat_form_section_logs($form_section); ?>
							<?php wpmudev_chat_form_section_logs_limit($form_section); ?>
							<?php wpmudev_chat_form_section_session_messages($form_section); ?>

							<?php /* if ($wpmudev_chat->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
								wpmudev_chat_form_section_blocked_ip_addresses($form_section);
							} */ ?>
							<?php /* if ($wpmudev_chat->get_option('blocked_words_active', 'banned') == "enabled") {
								wpmudev_chat_form_section_blocked_words($form_section);
							} */ ?>
							<?php //wpmudev_chat_form_section_block_urls_site($form_section); ?>
						</div>
					</div>
					<input type="hidden" name="chat[section]" value="<?php echo $form_section; ?>" />
					<?php wp_nonce_field( 'wpmudev_chat_settings_save', 'wpmudev_chat_settings_save_wpnonce' ); ?>
					<p class="submit"><input type="submit" name="Submit" class="button-primary"
						value="<?php _e('Save Changes', $wpmudev_chat->translation_domain) ?>" /></p>

				</form>
			</div>
			<?php
		}

	}
}