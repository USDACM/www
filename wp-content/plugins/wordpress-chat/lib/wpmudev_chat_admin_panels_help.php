<?php
function wpmudev_chat_panel_help() {

	global $wpmudev_chat, $wp_version;

	$screen = get_current_screen();
	//echo "screen<pre>"; print_r($screen); echo "</pre>";

	$screen_help_text = array();

	if ((isset($_GET['page'])) && ($_GET['page'] === "chat_settings_panel")) {
		$screen_help_text['wpmudev-chat-help-page-overview'] = '<p>' . __( 'This is the page overview', $wpmudev_chat->translation_domain ) . '</p>';
		$screen->add_help_tab( array(
			'id'		=> 'wpmudev-chat-help-page-overview',
			'title'		=> __('Page Settings Overview', $wpmudev_chat->translation_domain ),
			'content'	=> $screen_help_text['wpmudev-chat-help-page-overview']
			)
		);
	} else if ((isset($_GET['page'])) && ($_GET['page'] === "chat_settings_panel_site")) {
		$screen_help_text['wpmudev-chat-help-site-overview'] = '<p>' . __( 'This is the site overview', $wpmudev_chat->translation_domain ) . '</p>';
		$screen->add_help_tab( array(
			'id'		=> 'wpmudev-chat-help-site-overview',
			'title'		=> __('Site Settings Overview', $wpmudev_chat->translation_domain ),
			'content'	=> $screen_help_text['wpmudev-chat-help-site-overview']
			)
		);
	} else if ((isset($_GET['page'])) && ($_GET['page'] === "chat_settings_panel_widget")) {
		$screen_help_text['wpmudev-chat-help-widget-overview'] = '<p>' . __( 'This is the widget overview', $wpmudev_chat->translation_domain ) . '</p>';
		$screen->add_help_tab( array(
			'id'		=> 'wpmudev-chat-help-widget-overview',
			'title'		=> __('Site Settings Overview', $wpmudev_chat->translation_domain ),
			'content'	=> $screen_help_text['wpmudev-chat-help-widget-overview']
			)
		);
	} else if ((isset($_GET['page'])) && ($_GET['page'] === "chat_settings_panel_global")) {
		$screen_help_text['wpmudev-chat-help-global-overview'] = '<p>' . __( 'This is the global overview', $wpmudev_chat->translation_domain ) . '</p>';
		$screen->add_help_tab( array(
			'id'		=> 'wpmudev-chat-help-global-overview',
			'title'		=> __('Site Settings Overview', $wpmudev_chat->translation_domain ),
			'content'	=> $screen_help_text['wpmudev-chat-help-global-overview']
			)
		);
	} else if ((isset($_GET['page'])) && ($_GET['page'] === "chat_session_logs")) {
		$screen_help_text['wpmudev-chat-help-session-logs-overview'] = '<p>' . __( 'This is the session logs overview', $wpmudev_chat->translation_domain ) . '</p>';
		$screen->add_help_tab( array(
			'id'		=> 'wpmudev-chat-help-session-logs-overview',
			'title'		=> __('Site Settings Overview', $wpmudev_chat->translation_domain ),
			'content'	=> $screen_help_text['wpmudev-chat-help-session-logs-overview']
			)
		);
	}
}

function wpmudev_chat_get_help_item($key, $type='full', $form_section = "page") {

	global $wpmudev_chat;

	$wpmudev_chat_help_items = array();

	$wpmudev_chat_help_items['log_creation'] = array(
		'full'	=>	__('If enabled will allow chat messages to be archived at the end of the chat session.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['log_display'] = array(
		'full'	=>	__('If enabled will show a list of logs for past chat session below the chat box.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['log_limit'] = array(
		'full'	=>	__('When a user first enters chat they will see only the last number of messages. As new message are added older messages are remove from the message listing. This option does not purge the database.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['log_purge'] = array(
		'full'	=>	__('If you host a chat spanning many hours this option will help purge the older message to prevent server load', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_status_message'] = array(
		'full'	=>	__('This is message shown to users when the chat session has been closed by the moderator.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_cleared_message'] = array(
		'full'	=>	__('This message is briefly displayed when the moderator archives or clears the current session messages.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_status_auto_close'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_position_h'] = array(
		'full'	=>	__('Horizontal position of site and private chat boxed', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_position_v'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
		'tip'	=>	__('Vertical position of site and private chat boxed.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_offset_h'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
		'tip'	=>	__('Pixels vertical offset from left/right edge of browsers', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Ex. 3px, 10px, etc.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_offset_v'] = array(
		'full'			=>	__('', $wpmudev_chat->translation_domain),
		'tip'			=>	__('Pixels vertical offset from top/bottom edge of browsers.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Ex. 3px, 10px, etc.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_spacing_h'] = array(
		'full'			=>	__('Pixels spacing between boxes.', $wpmudev_chat->translation_domain),
		'tip'			=>	__('Pixels spacing between boxes.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Pixels spacing between boxes. Ex. 3px, 10px, etc. ', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_resizable'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
		'tip'	=>	__('', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_shadow_show'] = array(
		'full'	=>	__('Enable dropshadow on Bottom Corner and Private chat boxes ', $wpmudev_chat->translation_domain),
		'tip'	=>	__('', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_shadow_v'] = array(
		'full'			=>	__('The position of the vertical shadow. Negative values are allowed', $wpmudev_chat->translation_domain),
		'tip'			=>	__('', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Ex. 3px, 10px, etc.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_shadow_h'] = array(
		'full'			=>	__('The position of the horizontal shadow. Negative values are allowed', $wpmudev_chat->translation_domain),
		'tip'			=>	__('', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Ex. 3px, 10px, etc.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_shadow_blur'] = array(
		'full'			=>	__('Controls how sharp/soft the shadow edge is. The blur distance', $wpmudev_chat->translation_domain),
		'tip'			=>	__('', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Ex. 3px, 10px, etc.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_shadow_spread'] = array(
		'full'			=>	__('The size of shadow', $wpmudev_chat->translation_domain),
		'tip'			=>	__('', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Ex. 3px, 10px, etc.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_shadow_color'] = array(
		'full'	=>	__('Starting color for dropshadow', $wpmudev_chat->translation_domain),
		'tip'	=>	__('', $wpmudev_chat->translation_domain)

	);

	$wpmudev_chat_help_items['box_title'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
		'tip'	=>	__('The title of the chat session. Will be displayed in the header bar. This is different than the post/page title.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('The title of the chat session', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_width'] = array(
		'full'	=>	__('This sets the width of the chat box. For Page/Post chat set this to 100% to use the full width of the content area. For Site chats set this to a specific value like 300px.', $wpmudev_chat->translation_domain),
		'tip'	=>	__('This sets the width of the chat box. For Page/Post chat set this to 100% to use the full width of the content area. For Site chats set this to a specific value like 300px.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Width of chat box', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_height'] = array(
		'full'	=>	__('This sets the height of the chat box. This should be a specific value like 300px.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Height of chat box', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_font_family'] = array(
		'full'	=>	__("Font family used for the message input. Select 'inherit' to use default page front from your theme", $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_font_size'] = array(
		'full'	=>	__('Font size used for the message input.', $wpmudev_chat->translation_domain),
		'placeholder'	=> __("12px, 0.9em or leave blank to inherit from theme", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_text_color'] = array(
		'full'	=>	__('Color of text within the chat box. This is not the same as the message row text color', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_background_color'] = array(
		'full'	=>	__('Background color of chat box area. This is not the message area color.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_border_color'] = array(
		'full'	=>	__('Border color for chat elements like Message area, User List, etc. ', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_border_width'] = array(
		'full'	=>	__('Border width for outer chat box.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Border width 1px, 3px, etc.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_padding'] = array(
		'full'	=>	__('The spacing between the outer container border and the chat elements like message list, message area, etc.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('Padding 1px, 3px, etc.', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['box_sound'] = array(
		'full'	=>	__('Play sound when a new message is received', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_emoticons'] = array(
		'full'	=>	__('Display emoticons in chat session.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_new_message_color'] = array(
		'full'	=>	__('Text color for elements of the outer chat box.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['buttonbar'] = array(
		'full'	=>	__('Display Button bar above message entry', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_area_background_color'] = array(
		'full'	=>	__('Background color of the message area', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_background_color'] = array(
		'full'	=>	__('Background color of the message row items', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_spacing'] = array(
		'full'	=>	__('Spacing between row items', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_border_color'] = array(
		'full'	=>	__('Border color of the message row items. Top and Bottom borders only.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_border_width'] = array(
		'full'	=>	__('Border width of the message row items', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['background_highlighted_color'] = array(
		'full'	=>	__('Chat box background color when there is a new message', $wpmudev_chat->translation_domain),
	);


	$wpmudev_chat_help_items['row_font_family'] = array(
		'full'	=>	__("Font family used for the message input. Select 'inherit' to use default page front from your theme", $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_font_size'] = array(
		'full'	=>	__('Font size used for the message input.', $wpmudev_chat->translation_domain),
		'placeholder'	=> __("12px, 0.9em or leave blank to inherit from theme", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['row_text_color'] = array(
		'full'	=>	__('Chat message row text color', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_name_avatar'] = array(
		'full'	=>	__("Display the user's avatar with the message", $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_name_color'] = array(
		'full'	=>	__('User name background color', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_moderator_name_color'] = array(
		'full'	=>	__('Moderator Name background color', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_avatar_width'] = array(
		'full'	=>	__('The Avatar is a square graphic and represents the user. Enter a value for the maximum width/height of the graphic', $wpmudev_chat->translation_domain),
		'placeholder'	=>	'Enter a value as 25px, 30px, etc., '
	);

	$wpmudev_chat_help_items['row_date'] = array(
		'full'	=>	__('Display date the or messages', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_time'] = array(
		'full'	=>	__('Display the date the message was sent', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_date_text_color'] = array(
		'full'	=>	__('Date/Time color', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_date_color'] = array(
		'full'	=>	__('Date/Time background color', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_code_color'] = array(
		'full'	=>	__('Text color for source code', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['box_input_position'] = array(
		'full'	=>	__("Controls position of the chat message input. When set to top the chat message history is ordered newest at top. When set to bottom the chat message history is ordered newest at bottom", $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_message_input_font_family'] = array(
		'full'	=>	__("Font family used for the message input. Select 'inherit' to use default page front from your theme", $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_message_input_font_size'] = array(
		'full'	=>	__('Font size used for the message input.', $wpmudev_chat->translation_domain),
		'placeholder'	=> __("12px, 0.9em or leave blank to inherit from theme", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['row_message_input_height'] = array(
		'full'			=> __('Height of the message input area', $wpmudev_chat->translation_domain),
		'placeholder'	=> __('', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_message_input_length'] = array(
		'full'	=>	__('Maximum number of character a user can enter', $wpmudev_chat->translation_domain),
		'placeholder'	=>	'blank or zero for no limit'
	);

	$wpmudev_chat_help_items['row_message_input_text_color'] = array(
		'full'	=>	__('Text color for message input', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['row_message_input_background_color'] = array(
		'full'	=>	__('Background color for message input area', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_poll_interval_messages'] = array(
		'full'			=>	__('Controls how often (seconds) to check for new message from other chat participants. Value can be partial seconds like 1.5, 2.75. etc.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__('1, 2, 1.5, etc. ', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_poll_interval_meta'] = array(
		'full'	=>	__('Controls how often (seconds) to update the Users Lists for the chat session', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_poll_type'] = array(
		'full'	=>	__('Controls the polling source of for new message and status changes', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['session_static_file_path'] = array(
		'full'	=>	__('Location for static polling files.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_words_active'] = array(
		'full'	=>	__('Enable blocked word filtering.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_words_replace'] = array(
		'full'	=>	__('Replaces blocked word with the something else.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_words'] = array(
		'full'	=>	__('Blocked works not allowed in chat sessions. This is global to all sessions.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_users'] = array(
		'full'	=>	__('Blocked used banned from all chat sessions. This option will hide all chat from certain user email addresses.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_urls_action'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
		'tip'	=>	__('', $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['blocked_urls'] = array(
		'full'	=>	__('This option will hide the Bottom Corner chat based on the URLs provided.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_ip_addresses_active'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_ip_message'] = array(
		'full'	=>	__('Message displayed to user when their IP address has been banned.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_ip_addresses'] = array(
		'full'	=>	__('List of blocked IP addresses. Each IP address should be entered as dotted decimal format. Example: 123.123.123.123, 10.0.1.168', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_ip_addresses_active'] = array(
		'full'	=>	__('', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bottom_corner'] = array(
		'full'	=>	__('The Bottom Corner chat is a global group chat box shown on ALL pages of your site.', $wpmudev_chat->translation_domain),
	);
	$wpmudev_chat_help_items['bottom_corner_wpadmin'] = array(
		'full'	=>	__('Display bottom corner chat within WPAdmin.', $wpmudev_chat->translation_domain),
	);


	$wpmudev_chat_help_items['users_list_position'] = array(
		'full'	=>	__('Controls position of participating users in chat session', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['users_list_show'] = array(
		'full'	=>	__('Show list of users participating in chat session.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['users_list_width'] = array(
		'full'	=>	__('Width of user list area. Than can be a fixed size 250px or percentage 25%, ', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("Message area width will be adjusted automatically", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['users_list_avatar_width'] = array(
		'full'	=>	__('Size of user avatars shown in user list area.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("30px, 40px, etc. Must be a fixed size. ", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['users_list_threshold_delete'] = array(
		'full'	=>	__('When a user leaves the chat by navigating to a different page on the site or closing the browser they will be removed after this threshold of seconds. Minimum 20 seconds.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("Seconds delay when the user is remove from the listing. Minimum 20 seconds.",
		 $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['users_list_background_color'] = array(
		'full'			=>	__('Background color of users list area.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['users_list_name_color'] = array(
		'full'			=>	__('Color of the user names. Not used is displaying avatars.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['users_list_font_family'] = array(
		'full'			=>	__('Font family for the user name list items.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['users_list_font_size'] = array(
		'full'			=>	__('Font size for the user name list items.', $wpmudev_chat->translation_domain),
		'placeholder'	=>	__("", $wpmudev_chat->translation_domain)
	);

	$wpmudev_chat_help_items['login_options'] = array(
		'full'	=>	__('Which user login options are allowed for the chat sessions', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['noauth_view'] = array(
		'full'	=>	__('Controls what the user is allowed to see if they have not logged into chat.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['noauth_login_prompt'] = array(
		'full'	=>	__('This is the prompt message telling the user they need to login prior to posting chat messages.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['noauth_login_message'] = array(
		'full'	=>	__('The login message shown above the login form. ', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['moderator_roles'] = array(
		'full'	=>	__('Controls which users can moderator message and other users during the chat session.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['tinymce_roles'] = array(
		'full'	=>	__('Controls which WordPress user roles will see the Chat WYSIWYG toolbar button.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['tinymce_post_types'] = array(
		'full'	=>	__('Controls which post types will have the Chat WYSIWYG toolbar button enabled', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_admin_urls'] = array(
		'full'	=>	__('Allows blocking Chat from loading on certain WordPress Admin pages.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['blocked_on_shortcode'] = array(
		'full'	=>	__('Hide/Show the widget on Posts/Pages containing the Chat shortcode.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_menu_label'] = array(
		'full'	=>	__('The title of the tab within the BuddyPress group section to access Chat', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_menu_slug'] = array(
		'full'	=>	__('The URL slug shown to the user when accessing the Group Chat page.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_group_show_site'] = array(
		'full'	=>	__('Controls display of bottom corner chats on the BuddyPress Group section', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_group_admin_show_site'] = array(
		'full'	=>	__('Controls display of bottom corner chats on the BuddyPress Group Admin section', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_group_show_widget'] = array(
		'full'	=>	__('Controls display of widget chats on the BuddyPress Group section', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_group_admin_show_widget'] = array(
		'full'	=>	__('Controls display of bottom widget on the BuddyPress Group Admin section', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_form_background_color'] = array(
		'full'	=>	__('Controls the background color on BuddyPress Group Admin Chat form.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['bp_form_label_color'] = array(
		'full'	=>	__('Controls the txt color for labels on the BuddyPress Group Admin Chat form.', $wpmudev_chat->translation_domain),
	);


	$wpmudev_chat_help_items['bottom_corner_global'] = array(
		'full'	=>	__('The global bottom corner chat means the chat message will be from all sites within the Multisite system. Switching between sites will retain the messages.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['chat_user_status'] = array(
		'full'	=>	__('This user option controls your public chat status to other users within your network. This lets you control when others can initiate private chat sessions with you. Note this does not prevent private chats during existing chat sessions you are participating in.', $wpmudev_chat->translation_domain),
	);

	$wpmudev_chat_help_items['chat_name_display'] = array(
		'full'	=>	__(''),
	);
	$wpmudev_chat_help_items['chat_wp_admin'] = array(
		'full'	=>	__('This will disable all Chat functions including WordPress toolbar menu', $wpmudev_chat->translation_domain),
	);
	$wpmudev_chat_help_items['chat_wp_toolbar'] = array(
		'full'	=>	__(''),
	);







	if ($type == "tip") {
		if ( (isset($wpmudev_chat_help_items[$key]['tip'])) && (strlen($wpmudev_chat_help_items[$key]['tip'])) ) {
			return $wpmudev_chat->tips->add_tip($wpmudev_chat_help_items[$key]['tip']);
		} else if ( (isset($wpmudev_chat_help_items[$key]['full'])) && (strlen($wpmudev_chat_help_items[$key]['full'])) ) {
			return $wpmudev_chat->tips->add_tip($wpmudev_chat_help_items[$key]['full']);
		}
	} else if ( (isset($wpmudev_chat_help_items[$key][$type])) && (strlen($wpmudev_chat_help_items[$key][$type])) ) {
		return $wpmudev_chat_help_items[$key][$type];
	}
}