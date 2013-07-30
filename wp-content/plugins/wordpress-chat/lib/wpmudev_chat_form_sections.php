<?php

function wpmudev_chat_form_section_logs($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e("Logs", $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td class="chat-label-column"><label for="chat_log_creation"><?php _e("Log creation", $wpmudev_chat->translation_domain); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_log_creation" name="chat[log_creation]" >
						<option value="enabled" <?php print ($wpmudev_chat->get_option('log_creation', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php _e("Enabled", $wpmudev_chat->translation_domain); ?></option>
						<option value="disabled" <?php print ($wpmudev_chat->get_option('log_creation', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php _e("Disabled", $wpmudev_chat->translation_domain); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('log_creation', 'tip' ); ?></td>
			</tr>

			<tr>
				<td class="chat-label-column"><label for="chat_log_display"><?php _e("Log display", $wpmudev_chat->translation_domain); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_log_display" name="chat[log_display]" >
						<option value="enabled" <?php print ($wpmudev_chat->get_option('log_display', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php _e("Enabled", $wpmudev_chat->translation_domain); ?></option>
						<option value="disabled" <?php print ($wpmudev_chat->get_option('log_display', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php _e("Disabled", $wpmudev_chat->translation_domain); ?></option>
					</select>
				</td>
				<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('log_display', 'tip') ; ?></td>
			</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_logs_limit($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e("Display Messages Limit", $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_log_limit"><?php _e("Limit Messages Shown", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_log_limit" name="chat[log_limit]"
					value="<?php print $wpmudev_chat->get_option('log_limit', $form_section); ?>" /><br />
				<span class="description"><?php _e("default 100. Leave empty for all.", $wpmudev_chat->translation_domain); ?></span>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('log_limit', 'tip'); ?></td>
		</tr>
<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_log_purge"><?php _e("Automatically Purge Messages", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_log_purge" name="chat[log_purge]"
					value="<?php print $wpmudev_chat->get_option('log_purge', $form_section); ?>" /> hours<br />
				<span class="description"><?php _e("Leave empty for no purge. Does purge database messages", $wpmudev_chat->translation_domain); ?></span>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('log_purge', 'tip'); ?></td>
		</tr>
<?php */ ?>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_session_messages($form_section = "page") {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e("Session message", $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_session_status_message"><?php _e("Session Closed Message", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" name="chat[session_status_message]" id="chat_session_status_message"
				value="<?php print $wpmudev_chat->get_option('session_status_message', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_status_message', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_session_cleared_message"><?php _e("Session Cleared Message", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" name="chat[session_cleared_message]" id="chat_session_cleared_message"
				value="<?php print $wpmudev_chat->get_option('session_cleared_message', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_cleared_message', 'tip'); ?></td>
			</tr>

<?php /* ?>
		<tr>
			<td class="chat-label-column">
				<label for="chat_session_status_auto_close"><?php _e("Automatically closed when Moderator not joined.", $wpmudev_chat->translation_domain); ?></label>
			</td>
			<td class="chat-value-column">
				<select id="chat_session_status_auto_close" name="chat[session_status_auto_close]" >
					<option value="yes" <?php print ($wpmudev_chat->get_option('session_status_auto_close', $form_section) == 'yes')?'selected="selected"':''; ?>><?php _e("Yes", $wpmudev_chat->translation_domain); ?></option>
					<option value="no" <?php print ($wpmudev_chat->get_option('session_status_auto_close', $form_section) == 'no')?'selected="selected"':''; ?>><?php _e("No", $wpmudev_chat->translation_domain); ?></option>
				</select>

			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_status_auto_close', 'tip'); ?></td>
		</tr>
<?php */ ?>
		</table>
	</fieldset>
	<?php
}
function wpmudev_chat_form_section_fonts($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e("Fonts", $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">

			<tr>
				<td class="chat-label-column"><label for="chat_font_family"><?php _e("Font", $wpmudev_chat->translation_domain); ?></label></td>
				<td class="chat-value-column">
					<select id="chat_font_family" name="chat[font_family]">
						<option value=""><?php _e("-- None inherit from theme --", $wpmudev_chat->translation_domain); ?></option>
						<?php foreach ($wpmudev_chat->_chat_options_defaults['fonts_list'] as $font_name => $font) { ?>
							<option value="<?php print $font; ?>" <?php print ($wpmudev_chat->get_option('font_family', $form_section) == $font)?'selected="selected"':''; ?> ><?php print $font_name; ?></option>
						<?php } ?>
					</select>
				</td>
				<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('font_family', 'tip') ; ?></td>
			</tr>

			<tr>
				<td class="chat-label-column"><label for="chat_font_size"><?php _e("Font size", $wpmudev_chat->translation_domain); ?></label></td>
				<td class="chat-value-column">
					<?php $font_size = trim($wpmudev_chat->get_option('font_size', $form_section)); ?>
					<input type="text" name="chat[font_size]" id="chat_font_size"
						value="<?php echo (!empty($font_size)) ? wpmudev_chat_check_size_qualifier($font_size) : ''; ?>"
						placeholder="<?php echo wpmudev_chat_get_help_item('font_size', 'placeholder'); ?>" />
				</td>
				<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('font_size', 'tip') ; ?></td>
			</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_site_position($form_section = 'site') {
	global $wpmudev_chat;
	//echo "chat<pre>"; print_r($wpmudev_chat); echo "</pre>";

	?>
	<fieldset>
		<legend><?php _e("Chat Box Position", $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Controls the position of the Site Chat boxes. These boxes include Bottom Corner chat as well as Private Chats. Selecting right the multiple chat boxes will be from right to left.',  $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_position_h"><?php _e("Position Horizontal", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<select id="chat_box_position_h" name="chat[box_position_h]">
					<option value="right" <?php print ($wpmudev_chat->get_option('box_position_h', $form_section) == 'right')?'selected="selected"':'';
						?>><?php _e('Right', $wpmudev_chat->translation_domain); ?></option>
					<option value="left" <?php print ($wpmudev_chat->get_option('box_position_h', $form_section) == 'left')?'selected="selected"':'';
						?>><?php _e('Left', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_position_h', 'tip') ; ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_position_v"><?php _e("Position Vertical", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<select id="chat_box_position_v" name="chat[box_position_v]">
					<option value="top" <?php print ($wpmudev_chat->get_option('box_position_v', $form_section) == 'top')?'selected="selected"':'';
						?>><?php _e('Top', $wpmudev_chat->translation_domain); ?></option>
					<option value="bottom" <?php print ($wpmudev_chat->get_option('box_position_v', $form_section) == 'bottom')?'selected="selected"':'';
						?>><?php _e('Bottom', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_position_v', 'tip') ; ?></td>
		</tr>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php _e("Chat Box Position Offset", $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Allows you to provide offsets to bring the chat boxes away from the default edge of the screen. For example setting the position above to bottom/right could conflict with some other fixed position footer bars. So you can set the vertical offset below to 20px or some value to prevent from hiding other fixed elements. ', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_offset_h"><?php _e("Position from Left/Right edge", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_offset_h]" id="chat_box_offset_h"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_offset_h', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_offset_h', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_offset_h', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_offset_v"><?php _e("Position from Top/Bottom Edge", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_offset_v]" id="chat_box_offset_v"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_offset_v', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('chat_box_offset_v', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_offset_v', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>


	<fieldset>
		<legend><?php _e("Chat Box Spacing", $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Controls the horizontal spacing between multiple Chat boxes', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_spacing_h"><?php _e("Spacing", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_spacing_h]" id="chat_box_spacing_h"
					value="<?php print $wpmudev_chat->get_option('box_spacing_h', $form_section); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_spacing_h', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_spacing_h', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
<?php /* ?>
	<fieldset>
		<legend><?php _e("Chat Box Resizable", $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Allows users to resize the Site Chat boxes. This will load the jQuery UI JavaScript libraries which may effect page load time and/or conflict with your theme.', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_spacing_h"><?php _e("Chat window can be resized", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<select id="chat_box_resizable" name="chat[box_resizable]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('box_resizable', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('box_resizable', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_resizable', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
<?php */ ?>
	<fieldset>
		<legend><?php _e("Chat Box Shadow", $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Controls the horizontal spacing between multiple Chat boxes', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_shadow_show"><?php _e("Display Box Shadow", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<select id="chat_box_shadow_show" name="chat[box_shadow_show]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('box_shadow_show', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('box_shadow_show', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_shadow_show', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_shadow_v"><?php _e("Vertical Right Edge", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_shadow_v]" id="chat_box_shadow_v"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_shadow_v', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_shadow_v', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_shadow_v', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_shadow_h"><?php _e("Horizontal Bottom Edge", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_shadow_h]" id="chat_box_shadow_h"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_shadow_h', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_shadow_h', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_shadow_h', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_shadow_blur"><?php _e("Shadow Sharpness", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_shadow_blur]" id="chat_box_shadow_blur"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_shadow_blur', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_shadow_blur', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_shadow_blur', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_shadow_spread"><?php _e("Shadow Spread", $wpmudev_chat->translation_domain); ?></label><br />
			</td>
			<td class="chat-value-column">
				<input type="text" name="chat[box_shadow_spread]" id="chat_box_shadow_spread"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_shadow_spread', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_shadow_spread', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_shadow_spread', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_shadow_color"><?php _e('Shadow Color',
						$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_shadow_color" name="chat[box_shadow_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('box_shadow_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('box_shadow_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_shadow_color', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>

	<?php
}

function wpmudev_chat_form_section_login_options($form_section = 'page') {
	global $wpmudev_chat;

	//echo "network-site<pre>"; print_r($wpmudev_chat->_chat_options['network-site']); echo "</pre>";
	?>
	<fieldset>
		<legend><?php _e("Login Options", $wpmudev_chat->translation_domain); ?> - <?php
			_e("Authentication methods users can use", $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column-wide">
				<p class="info"><?php _e("If the user is authenticated for another chat that login option will be used for all chats. If the login option is not allowed the chat will not be visible to the user.",$wpmudev_chat->translation_domain); ?></p>

				<p class="info"><?php _e("For example you setup a chat widget allowing Twitter login. The bottom corner allows only Facebook. The Twitter authenticated user will not be shown or be able to participate in the bottom corner chat session.", $wpmudev_chat->translation_domain); ?></p>

				<ul id="wpmudev-chat-login-options-list">
					<?php if (is_multisite()) { ?>

						<li><input type="checkbox" id="chat_login_options_current_user" disabled="disabled"
							name="chat[login_options][]" class="chat_login_options" value="current_user" checked="checked"
						 	/> <label><?php _e('WordPress Site user - only users for current site. Includes any Super Admin.', $wpmudev_chat->translation_domain); ?></label>
							<ul><li><input type="checkbox" id="chat_login_options_network_user"
								name="chat[login_options][]" class="chat_login_options" value="network"
								<?php print (in_array('network', $wpmudev_chat->get_option('login_options', $form_section)) > 0)?'checked="checked"':''; ?>
						 		/> <label><?php _e('WordPress Network user - Allow users from other sites to view chats.', $wpmudev_chat->translation_domain); ?></label></li></ul></li>
					<?php } else {
						?><li><input type="checkbox" id="chat_login_options_current_user" disabled="disabled"
							name="chat[login_options][]" class="chat_login_options" value="current_user" checked="checked"
						 	/> <label><?php _e('WordPress user', $wpmudev_chat->translation_domain); ?></label></li>
					<?php } ?>

					<li><input type="checkbox" id="chat_login_options_public_user"
						name="chat[login_options][]" class="chat_login_options" value="public_user"
						<?php print (in_array('public_user', $wpmudev_chat->get_option('login_options', $form_section)) > 0)?'checked="checked"':''; ?>
						 /> <label><?php _e('Public user - Anonymous users visiting your site', $wpmudev_chat->translation_domain); ?></label></li>

					<li><input type="checkbox" id="chat_login_options_twitter"
						name="chat[login_options][]" class="chat_login_options" value="twitter"
						<?php print (!$wpmudev_chat->is_twitter_setup())?'disabled="disabled"':''; ?>
						<?php print (in_array('twitter', $wpmudev_chat->get_option('login_options', $form_section)) > 0)?'checked="checked"':''; ?> />
						 <label><?php _e('Twitter', $wpmudev_chat->translation_domain); ?></label> <a
							href="admin.php?page=chat_settings_panel_global#wpmudev_chat_twitter_panel"><?php
							_e('Setup', $wpmudev_chat->translation_domain) ?></a></li>

					<li><input type="checkbox" id="chat_login_options_google_plus"
						name="chat[login_options][]" class="chat_login_options" value="google_plus"
						<?php print (!$wpmudev_chat->is_google_plus_setup())?'disabled="disabled"':''; ?>
						<?php print (in_array('google_plus', $wpmudev_chat->get_option('login_options', $form_section)) > 0)?'checked="checked"':''; ?> />
						 <label><?php _e('Google+', $wpmudev_chat->translation_domain); ?></label> <a
						 	href="admin.php?page=chat_settings_panel_global#wpmudev_chat_google_plus_panel"><?php
							_e('Setup', $wpmudev_chat->translation_domain) ?></a></li>

					<li><input type="checkbox" id="chat_login_options_facebook"
						name="chat[login_options][]" class="chat_login_options" value="facebook"
						<?php print (!$wpmudev_chat->is_facebook_setup())?'disabled="disabled"':''; ?>
						<?php print (in_array('facebook', $wpmudev_chat->get_option('login_options', $form_section)) > 0)?'checked="checked"':''; ?> />
						 <label><?php _e('Facebook', $wpmudev_chat->translation_domain); ?></label> <a
						 	href="admin.php?page=chat_settings_panel_global#wpmudev_chat_facebook_panel"><?php
							_e('Setup', $wpmudev_chat->translation_domain) ?></a></li>
				</ul>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('login_options', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_information($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Chat Box Information', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_box_title"><?php _e("Title", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_title" name="chat[box_title]" value="<?php echo $wpmudev_chat->get_option('box_title', $form_section); ?>"
					size="5" placeholder="<?php echo wpmudev_chat_get_help_item('box_title', 'placeholder'); ?>" />

				<?php /* ?><p class="info"><?php _e('Title will be displayed in chat bar above messages', $wpmudev_chat->translation_domain); ?></p><?php */ ?>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_title', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_container($form_section = 'page') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Chat Box Container', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_width"><?php _e("Width", $wpmudev_chat->translation_domain); ?></label>
			</td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_width" name="chat[box_width]" class="size" size="5"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_width', $form_section)); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_width', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_width', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column">
				<label for="chat_box_height"><?php _e("Height", $wpmudev_chat->translation_domain); ?></label>
			</td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_height" name="chat[box_height]" class="size" size="5"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('box_height', $form_section)); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_height', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_height', 'tip'); ?></td>
		</tr>


		<tr>
			<td class="chat-label-column"><label for="chat_box_font_family"><?php _e("Font", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_box_font_family" name="chat[box_font_family]">
					<option value=""><?php _e("-- None inherit from theme --", $wpmudev_chat->translation_domain); ?></option>
					<?php foreach ($wpmudev_chat->_chat_options_defaults['fonts_list'] as $font_name => $font) { ?>
						<option value="<?php print $font_name; ?>" <?php print ($wpmudev_chat->get_option('box_font_family', $form_section) == $font_name)?'selected="selected"':''; ?> ><?php print $font_name; ?></option>
					<?php } ?>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_font_family', 'tip') ; ?></td>
		</tr>

		<tr>
			<td class="chat-label-column"><label for="chat_box_font_size"><?php _e("Font size", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<?php $box_font_size = trim($wpmudev_chat->get_option('box_font_size', $form_section)); ?>
				<input type="text" name="chat[box_font_size]" id="chat_box_font_size"
					value="<?php echo (!empty($box_font_size)) ? wpmudev_chat_check_size_qualifier($box_font_size) : ''; ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_font_size', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_font_size', 'tip'); ?></td>
		</tr>

		<tr>
			<td class="chat-label-column"><label for="chat_box_text_color"><?php _e('Text', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_text_color" name="chat[box_text_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('box_text_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('box_text_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_text_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_box_background_color"><?php _e('Background', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_background_color" name="chat[box_background_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('box_background_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('box_background_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_box_border_color"><?php _e('Border Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_border_color" name="chat[box_border_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('box_border_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('box_border_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_border_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_box_border_width"><?php _e('Border Width',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_border_width" name="chat[box_border_width]"
					value="<?php echo $wpmudev_chat->get_option('box_border_width', $form_section); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_border_width', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_border_width', 'tip'); ?></td>
		</tr>
<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_box_padding"><?php _e('Element Padding',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_padding" name="chat[box_padding]"
					value="<?php echo $wpmudev_chat->get_option('box_padding', $form_section); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('box_padding', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_padding', 'tip'); ?></td>
		</tr>
<?php */ ?>
		<tr>
			<td class="chat-label-column"><label for="chat_box_sound"><?php _e("Sound", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_box_sound" name="chat[box_sound]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('box_sound', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('box_sound', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_sound', 'tip'); ?></td>
		</tr>
		<?php /* if ($form_section == "site") { ?>
		<tr>
			<td class="chat-label-column"><label for="chat_box_new_message_color"><?php _e('New Message Border Color ', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_new_message_color" name="chat[box_new_message_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('box_new_message_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('box_new_message_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_new_message_color', 'tip'); ?></td>
		</tr>
		<?php } */ ?>

		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_messages_wrapper($form_section = 'page') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Chat Messages Wrapper', $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_row_area_background_color"><?php _e('Message Area Background',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_area_background_color" name="chat[row_area_background_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_area_background_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_area_background_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_area_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_background_color"><?php _e('Message Background',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_background_color" name="chat[row_background_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_background_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_background_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_spacing"><?php _e('Spacing Between Message', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_spacing" name="chat[row_spacing]"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('row_spacing', $form_section)); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_spacing', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_border_color"><?php _e('Message Border Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_border_color" name="chat[row_border_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_border_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_border_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_border_color', 'tip'); ?></td>
		</tr>

		<tr>
			<td class="chat-label-column"><label for="chat_row_border_width"><?php _e('Message Border Width',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_border_width" name="chat[row_border_width]"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('row_border_width', $form_section)); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_border_width', 'tip'); ?></td>
		</tr>
<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_background_highlighted_color"><?php _e('Highlighted Background',
			 	$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_background_highlighted_color" name="chat[background_highlighted_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('background_highlighted_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('background_highlighted_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('background_highlighted_color', 'tip'); ?></td>
		</tr>
<?php */ ?>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_messages_rows($form_section = 'page') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Chat Message Elements', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">

		<tr>
			<td class="chat-label-column"><label for="chat_row_font_family"><?php _e("Font", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_row_font_family" name="chat[row_font_family]">
					<option value=""><?php _e("-- None inherit from theme --", $wpmudev_chat->translation_domain); ?></option>
					<?php foreach ($wpmudev_chat->_chat_options_defaults['fonts_list'] as $font_name => $font) { ?>
						<option value="<?php print $font_name; ?>" <?php print ($wpmudev_chat->get_option('row_font_family', $form_section) == $font_name)?'selected="selected"':''; ?> ><?php print $font_name; ?></option>
					<?php } ?>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_font_family', 'tip') ; ?></td>
		</tr>

		<tr>
			<td class="chat-label-column"><label for="chat_row_font_size"><?php _e("Font size", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<?php
					$row_font_size = trim($wpmudev_chat->get_option('row_font_size', $form_section));
				?>
				<input type="text" name="chat[row_font_size]" id="chat_row_font_size"
					value="<?php echo (!empty($row_font_size)) ? wpmudev_chat_check_size_qualifier($row_font_size) : ''; ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('row_font_size', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_font_size', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_text_color"><?php _e('Message Text Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_text_color" name="chat[row_text_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_text_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_text_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_text_color', 'tip'); ?></td>
		</tr>
		<?php $row_name_avatar = $wpmudev_chat->get_option('row_name_avatar', $form_section); ?>
		<tr>
			<td class="chat-label-column"><label for="chat_row_name_avatar"><?php _e("Show Avatar/Name", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_row_name_avatar" name="chat[row_name_avatar]" >
					<option value="avatar" <?php print ($row_name_avatar == 'avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatar", $wpmudev_chat->translation_domain); ?></option>
					<option value="name" <?php print ($row_name_avatar == 'name')?'selected="selected"':''; ?>><?php
						_e("Name", $wpmudev_chat->translation_domain); ?></option>
<?php /* ?>
					<option value="name-avatar" <?php print ($row_name_avatar == 'name-avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatar and Name", $wpmudev_chat->translation_domain); ?></option>
<?php */ ?>
					<option value="disabled" <?php print ($row_name_avatar == 'none')?'selected="selected"':''; ?>><?php
					 	_e("None", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_name_avatar', 'tip'); ?></td>
		</tr>
		<tr id="chat_row_name_color_tr" <?php if ($row_name_avatar != "name") { echo ' style="display:none" '; } ?> >
			<td class="chat-label-column"><label for="chat_row_name_color"><?php _e('User Name Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_name_color" name="chat[row_name_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_name_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_name_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_name_color', 'tip'); ?></td>
		</tr>
		<tr id="chat_row_moderator_name_color_tr" <?php if ($row_name_avatar != "name") { echo ' style="display:none" '; } ?>>
			<td class="chat-label-column"><label for="chat_row_moderator_name_color"><?php _e('Moderator Name Color',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_moderator_name_color" name="chat[row_moderator_name_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_moderator_name_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_moderator_name_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_moderator_name_color', 'tip'); ?></td>
		</tr>
		<tr id="chat_row_avatar_width_tr" <?php if ($row_name_avatar != "avatar") { echo ' style="display:none" '; } ?>>
			<td class="chat-label-column"><label for="chat_row_avatar_width"><?php _e('User Avatar Width',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_avatar_width" name="chat[row_avatar_width]"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('row_avatar_width', $form_section), array('px')); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_avatar_width', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_date"><?php _e("Show Date", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_row_date" name="chat[row_date]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('row_date', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php _e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('row_date', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php _e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
				<input id="chat_row_date_format" type="text" name="chat[row_date_format]" value="<?php echo $wpmudev_chat->get_option('row_date_format', $form_section); ?>"/>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_date', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_time"><?php _e("Show time", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_row_time" name="chat[row_time]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('row_time', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php _e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('row_time', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php _e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
				<input id="chat_row_time_format" type="text" name="chat[row_time_format]" value="<?php echo $wpmudev_chat->get_option('row_time_format', $form_section); ?>"/>

			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_time', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_date_text_color"><?php _e('Date/Time Text Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_date_text_color" name="chat[row_date_text_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_date_text_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_date_text_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_date_text_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_date_color"><?php _e('Date/Time Text Background', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_date_color" name="chat[row_date_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_date_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_date_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_date_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_code_color"><?php _e('CODE Text Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_code_color" name="chat[row_code_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_code_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_code_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_code_color', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_messages_input($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Chat Message Input', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_box_input_position"><?php _e("Message input location", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_box_input_position" name="chat[box_input_position]" >
					<option value="top" <?php print ($wpmudev_chat->get_option('box_input_position', $form_section) == 'top')?'selected="selected"':''; ?>><?php
					 	_e("Top - Messages order newest first", $wpmudev_chat->translation_domain); ?></option>
					<option value="bottom" <?php print ($wpmudev_chat->get_option('box_input_position', $form_section) == 'bottom')?'selected="selected"':''; ?>><?php
					 	_e("Bottom - Messages ordered newest last", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_input_position', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_message_input_height"><?php _e('Input Height', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_message_input_height" name="chat[row_message_input_height]"
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('row_message_input_height', $form_section), array('px')); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_message_input_height', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_message_input_length"><?php _e('Max Characters', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_message_input_length" name="chat[row_message_input_length]"
					value="<?php echo $wpmudev_chat->get_option('row_message_input_length', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_message_input_length', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_message_input_font_family"><?php _e("Font", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_row_message_input_font_family" name="chat[row_message_input_font_family]">
					<option value=""><?php _e("-- None inherit from theme --", $wpmudev_chat->translation_domain); ?></option>
					<?php foreach ($wpmudev_chat->_chat_options_defaults['fonts_list'] as $font_name => $font) { ?>
						<option value="<?php print $font_name; ?>" <?php print ($wpmudev_chat->get_option('row_message_input_font_family', $form_section) == $font_name) ? 'selected="selected"':''; ?> ><?php print $font_name; ?></option>
					<?php } ?>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_message_input_font_family', 'tip') ; ?></td>
		</tr>

		<tr>
			<td class="chat-label-column"><label for="chat_row_message_input_font_size"><?php _e("Font size", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" name="chat[row_message_input_font_size]" id="chat_row_message_input_font_size"
					value="<?php print $wpmudev_chat->get_option('row_message_input_font_size', $form_section); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('row_message_input_font_size', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_message_input_font_size', 'tip') ; ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_message_input_text_color"><?php _e('Text Color',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_message_input_text_color" name="chat[row_message_input_text_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_message_input_text_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_message_input_text_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo  wpmudev_chat_get_help_item('row_message_input_text_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_row_message_input_background_color"><?php _e('Background Color',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_row_message_input_background_color" name="chat[row_message_input_background_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('row_message_input_background_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('row_message_input_background_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('row_message_input_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_box_emoticons"><?php _e("Emoticons", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_box_emoticons" name="chat[box_emoticons]" >
					<option value="enabled" <?php print ($wpmudev_chat->get_option('box_emoticons', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('box_emoticons', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_emoticons', 'tip'); ?></td>
		</tr>


<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_buttonbar"><?php _e("Button Bar", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_buttonbar" name="chat[buttonbar]" >
					<option value="enabled" <?php print ($wpmudev_chat->get_option('buttonbar', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('buttonbar', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 _e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('buttonbar', 'tip'); ?></td>
		</tr>
<?php */ ?>

		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_moderator_roles($form_section = 'page') {
	global $wpmudev_chat, $wp_roles;
	?>
	<fieldset>
		<legend><?php _e('Moderator Roles', $wpmudev_chat->translation_domain); ?> - <?php _e("Select which roles are moderators",
		 	$wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column-wide">
				<ul>
				<?php
					foreach ($wp_roles->role_names as $role => $name) {
						if ($role == "administrator") {
							if (is_multisite())
								$name .= ' - '. __('Includes Super Admins', $wpmudev_chat->translation_domain);

							?><li><input type="checkbox" id="chat_moderator_roles_<?php print $role; ?>" disabled="disabled" checked="checked"
								name="chat[moderator_roles][]" class="chat_moderator_roles" value="<?php print $role; ?>" /> <label><?php
								echo $name; ?></label></li><?php
						} else {
							?><li><input type="checkbox" id="chat_moderator_roles_<?php print $role; ?>"
								name="chat[moderator_roles][]" class="chat_moderator_roles" value="<?php print $role; ?>" <?php
								echo (in_array($role, $wpmudev_chat->get_option('moderator_roles', $form_section)) > 0)?'checked="checked"':''; ?> /> <label><?php
								echo $name; ?></label></li><?php
						}
					}
				?>
				</ul>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('moderator_roles', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_login_view_options($form_section = 'page') {
	global $wpmudev_chat;

	$noauth_view			= $wpmudev_chat->get_option('noauth_view', $form_section);
	$noauth_login_message 	= $wpmudev_chat->get_option('noauth_login_message', $form_section);
	$noauth_login_prompt	= $wpmudev_chat->get_option('noauth_login_prompt', $form_section);
	?>
	<fieldset>
		<legend><?php _e('What to show non-Authenticated users', $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column-wide">
				<ul style="width: 100%">
					<li style="width: 100%"><input type="radio" id="chat_noauth_view_default" name="chat[noauth_view]" class="chat_noauth_view"
						value="default" <?php if ($noauth_view == "default") { echo ' checked="checked" '; } ?> /> <label for="chat_noauth_view_default"><?php
							_e('Chat Messages and User Lists. (default)', $wpmudev_chat->translation_domain); ?></label></li>
					<li><input type="radio" id="chat_noauth_view_login_only" name="chat[noauth_view]" class="chat_noauth_view"
						value="login-only" <?php if ($noauth_view == "login-only") { echo ' checked="checked" '; } ?> /> <label for="chat_noauth_view_login_only"><?php
							_e('Chat Login form only', $wpmudev_chat->translation_domain); ?></label></li>
					<?php /* ?>
					<li><input type="radio" id="chat_noauth_view_nothing" name="chat[noauth_view]" class="chat_noauth_view"
						value="nothing" <?php if ($noauth_view == "nothing") { echo ' checked="checked" '; } ?> /> <label for="chat_noauth_view_nothing"><?php
							_e('Nothing', $wpmudev_chat->translation_domain); ?></label></li>
					<?php */ ?>
				</ul>

			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('noauth_view', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column-wide">
				<label for="chat_noauth_login_prompt"><?php _e('Message displayed on main chat area to prompt user login',
					$wpmudev_chat->translation_domain); ?></label<br />
				<input name="chat[noauth_login_prompt]" id="chat_noauth_login_prompt" style="width: 100%"
				value="<?php echo $noauth_login_prompt ?>" />
				<p class="description"><?php echo htmlentities('Allowed HTML <br />, <strong>, <em>, <i>, <b>'); ?></p>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('noauth_login_prompt', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column-wide">
				<label for="chat_noauth_login_message"><?php _e('Login form message displayed above the login fields and Facebook button', $wpmudev_chat->translation_domain); ?></label<br />
				<input name="chat[noauth_login_message]" id="chat_noauth_login_message" style="width: 100%"
				value="<?php echo $noauth_login_message ?>" />
				<p class="description"><?php echo htmlentities('Allowed HTML <br />, <strong>, <em>, <i>, <b>'); ?></p>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('noauth_login_message', 'tip'); ?></td>
		</tr>

		</table>
	</fieldset>
	<?php
}


function wpmudev_chat_form_section_twitter($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Twitter', $wpmudev_chat->translation_domain); ?></legend>

		<p class="info"><?php _e(sprintf('As of <strong><a target="_blank" href="%s">March 5, 2013 Twitter has discontinued support for the @Anywhere API</a></strong>. If you were using a previous twitter application via the @Anywhere API you simply need to update the <strong>Consumer key</strong> and <strong>Consumer secret</strong> by following the instructions below.', 'https://dev.twitter.com/docs/anywhere/welcome'), $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td style="vertical-align:top; width: 35%">
					<label for="chat_twitter_api_key"><?php _e('Consumer key', $wpmudev_chat->translation_domain); ?></label><br />
					<input type="text" id="chat_twitter_api_key" name="chat[twitter_api_key]"
						value="<?php print $wpmudev_chat->get_option('twitter_api_key', $form_section); ?>" class="" style="width: 90%" size="30" /><br /><br />

					<label for="chat_twitter_api_secret"><?php _e('Consumer secret', $wpmudev_chat->translation_domain); ?></label><br />
					<input type="password" id="chat_twitter_api_secret" name="chat[twitter_api_secret]"
						value="<?php print $wpmudev_chat->get_option('twitter_api_secret', $form_section); ?>" class="" style="width: 90%" size="30" />
				</td>
				<td class="info" style="vertical-align:top; width: 65%">
					<ol>
						<li><?php _e(sprintf('Login to twitter.com, then navigate to the <a target="_blank" href="%s">Twitter Developer</a> section.', "https://dev.twitter.com/apps"), $wpmudev_chat->translation_domain); ?></li>

						<li><?php _e(sprintf('If you already have a Twitter application registered for your website you can reuse the same Consumer key and Consumer secret keys.', "https://dev.twitter.com/apps"), $wpmudev_chat->translation_domain); ?></li>


						<li><?php _e(sprintf("If you need to create a new application you can click the '<strong>Create a new application</strong>' and follow the rest of the instructions below.", "https://dev.twitter.com/apps"), $wpmudev_chat->translation_domain); ?></li>
						<li><?php _e('Fill in the form with values for your website:', $wpmudev_chat->translation_domain); ?>
							<ul>
								<li><?php _e('<strong>Name</strong>: Should be the name of your website.', $wpmudev_chat->translation_domain); ?></li>
								<li><?php _e('<strong>Description</strong>: Should be a short description of your website.', $wpmudev_chat->translation_domain); ?></li>
								<li><?php _e('<strong>Website</strong>: Should be the home URL of your website. <strong>Not the page where Chat is displayed</strong>', $wpmudev_chat->translation_domain); ?></li>
								<li><?php _e('<strong>Callback URL</strong>: Should be the home URL of your website. Not the page where Chat is displayed', $wpmudev_chat->translation_domain); ?> <strong><?php print get_bloginfo('url'); ?></strong></li>
							</ul>
						</li>
						<li><?php _e('After you have submitted the App form the next page will show the details for your new Twitter app. Copy the values for the <strong>Consumer key</strong> and <strong>Consumer secret</strong> into the form fields on this page.', $wpmudev_chat->translation_domain); ?></li>
					</ol>
				</td>
			</tr>
		</table>
	</fieldset>
	<?php
}


function wpmudev_chat_form_section_polling_interval($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Chat Session Polling Interval', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Each Chat box (Page, Bottom Corner) will call back to the server via AJAX to check for new messages and status changes. The following options control options for this AJAX polling', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_session_poll_interval_messages"><?php _e('How often to update message lists',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_session_poll_interval_messages" name="chat[session_poll_interval_messages]"
					value="<?php echo $wpmudev_chat->get_option('session_poll_interval_messages', $form_section); ?>" />
				<p class="description"><?php _e('<strong>Recommended 1 or 2 seconds</strong>. Message lists are primary elements of chat and should be updated as often as possible.', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_poll_interval_messages', 'tip'); ?></td>
		</tr>
<?php /* ?>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_session_poll_interval_meta"><?php _e('How often to update meta list',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_session_poll_interval_meta" name="chat[session_poll_interval_meta]"
					value="<?php echo $wpmudev_chat->get_option('session_poll_interval_meta', $form_section); ?>" />
				<p class="description"><?php _e('<strong>Recommended 5 seconds</strong>. Meta lists are secondary elements of chat which include the active user lists for all open chats as well as invitations  from other users to join private chats.', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_poll_interval_meta', 'tip'); ?></td>
		</tr>
<?php */ ?>
		</table>
	</fieldset>
	<?php
}


function wpmudev_chat_form_section_polling_content($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Chat Session Polling Content', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('By default the chat sessions poll a special AJAX file located in the plugin directory (wpmudev-chat-ajax.php). Sometimes due to security issues on the server this is not allowed. In those cases set the polling type to WordPress.', $wpmudev_chat->translation_domain); ?></p>
		<p class="info"><?php _e('<strong>The WordPress AJAX will be much slower and user more server resources than using the Plugin AJAX.</strong>', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_session_poll_type"><?php _e('Select Polling source type',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_session_poll_type" name="chat[session_poll_type]">
					<option value="plugin" <?php print ($wpmudev_chat->get_option('session_poll_type', $form_section) == 'plugin') ? 'selected="selected"' : '';
						?>><?php _e('Plugin AJAX', $wpmudev_chat->translation_domain); ?></option>
					<option value="wordpress" <?php print ($wpmudev_chat->get_option('session_poll_type', $form_section) == 'wordpress') ? 'selected="selected"' : '';
						?>><?php _e('WordPress AJAX', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_poll_type', 'tip'); ?></td>
		</tr>
<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_session_static_file_path"><?php _e('Static Files Directory',
				$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_session_static_file_path" name="chat[session_static_file_path]"
					value="<?php echo $wpmudev_chat->get_option('session_static_file_path', $form_section); ?>" />
				<p class="description"><?php _e('This static file directory should be writable and accessible at all times from the chat plugin.',
				 	$wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('session_static_file_path', 'tip'); ?></td>
		</tr>
<?php */ ?>
		</table>
	</fieldset>

	<?php
}


function wpmudev_chat_form_section_facebook($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Facebook', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td style="vertical-align:top; width: 35%">
					<label for="chat_facebook_application_id"><?php _e('App id', $wpmudev_chat->translation_domain); ?></label><br />
					<input type="text" id="chat_facebook_application_id" name="chat[facebook_application_id]" value="<?php
						print $wpmudev_chat->get_option('facebook_application_id', $form_section); ?>" class="" style="width: 90%" size="40" /><br /><br />

					<label for="chat_facebook_application_secret"><?php _e('App secret', $wpmudev_chat->translation_domain); ?></label><br />
					<input type="password" id="chat_facebook_application_secret" name="chat[facebook_application_secret]"
						value="<?php print $wpmudev_chat->get_option('facebook_application_secret', $form_section); ?>" class="" style="width: 90%"  size="40" /><br /><br />


					<label for="chat_facebook_active_in_site"><?php _e('Load Facebook JavaScript ( all.js ) library?', $wpmudev_chat->translation_domain); ?></label><br />
					<label><input type="radio" id="chat_facebook_active_in_site" name="chat[facebook_active_in_site]" value="yes" <?php
								print ($wpmudev_chat->get_option('facebook_active_in_site', $form_section) == 'yes')?'checked="checked"':''; ?> class=""
							 /> <?php _e('Yes', $wpmudev_chat->translation_domain); ?></label>
					<label><input type="radio" id="chat_facebook_active_in_site" name="chat[facebook_active_in_site]" value="no" <?php
								print ($wpmudev_chat->get_option('facebook_active_in_site', $form_section) == 'no')?'checked="checked"':''; ?> class=""
							/> <?php _e('No', $wpmudev_chat->translation_domain); ?></label><br />

							<p class="description"><?php _e('Select NO If you are running other Facebook plugins like Ultimate Facebook.', $wpmudev_chat->translation_domain); ?></p>

				</td>
				<td class="info" style="vertical-align:top; width: 65%">

					<ol>
						<li><?php print sprintf(__('Register this site as an application on Facebook\'s <a target="_blank" href="%s">app registration page</a>', $wpmudev_chat->translation_domain), 'http://www.facebook.com/developers/createapp.php'); ?></li>
						<li><?php _e('If you\'re not logged in, you can use your Facebook username and password', $wpmudev_chat->translation_domain); ?></li>
						<li><?php _e('The site URL should be', $wpmudev_chat->translation_domain); ?> <b><?php print get_bloginfo('url'); ?></b></li>
						<li><?php _e('Once you have registered your site as an application, you will be provided with a App ID and a App secret.', $wpmudev_chat->translation_domain); ?></li>
						<li><?php _e('Copy and paste them to the fields on the left', $wpmudev_chat->translation_domain); ?></li>
					</ol>
				</td>
			</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_google_plus($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Google+', $wpmudev_chat->translation_domain); ?></legend>
		<p><?php _e('To create a client ID and client secret, create a Google APIs Console project, enable the Google+ API, create an OAuth 2.0 client ID, and register your JavaScript origins', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
			<tr>
				<td style="vertical-align:top; width: 35%">
					<label for="chat_google_plus_application_id"><?php _e('Client ID', $wpmudev_chat->translation_domain); ?></label><br />
					<input type="text" id="chat_google_plus_application_id" name="chat[google_plus_application_id]"
						value="<?php print $wpmudev_chat->get_option('google_plus_application_id', $form_section); ?>" class="" style="width: 90%" size="30" />
				</td>
				<td class="info" style="vertical-align:top; width: 65%">
					<ol>
						<li><?php _e('First log into your Google+ account', $wpmudev_chat->translation_domain); ?></li>
						<li><?php echo sprintf(__('In the <a target="_blank" href="%s">Google APIs Console</a>, select <strong>Create</strong> from the pull-down menu on the left, and enter a project name (such as "WordPress Chat").', $wpmudev_chat->translation_domain), "https://developers.google.com/console"); ?></li>

						<li><?php echo sprintf(__('In the <a target="_blank" href="%s">Services pane</a>, enable the <strong>Google+ API</strong>.', $wpmudev_chat->translation_domain), "https://code.google.com/apis/console/?api=plus#:services"); ?></li>

						<li><?php echo sprintf(__('In the <a target="_blank" href="%s">API Access pane</a>, click Create an OAuth 2.0 Client ID.', $wpmudev_chat->translation_domain), "https://code.google.com/apis/console/#:access"); ?>
							<ol style="list-style-type:lower-alpha;">
								<li><?php _e('In the <strong>Product name</strong> field, enter a name for your application (such as "Wordpress Chat"). All other fields are optional. Click <strong>Next</strong>.', $wpmudev_chat->translation_domain); ?></li>
								<li><?php _e('In the Client ID Settings section, do the following:	', $wpmudev_chat->translation_domain); ?>
									<ol style="list-style-type:circle">
										<li><?php _e('Select <em>Web application</em> for the <strong>Application type</strong>.', $wpmudev_chat->translation_domain); ?></li>
										<li><?php _e('Enter your site full domain into the <strong>Your site or hostname</strong>. Ensure the dropdown for the prefix is properly selected. You should only select <em>https://</em> if your site is using a valid SSL certificate installed.', $wpmudev_chat->translation_domain); ?> <strong><?php print get_bloginfo('url'); ?></strong></li>
										<li><?php _e('Finally, click the <strong>Create client ID</strong> button.', $wpmudev_chat->translation_domain); ?></li>
									</ol>
								</li>
							</ol>
						</li>
						<li><?php _e('On the next page find the section <strong>Client ID for web applications</strong>.', $wpmudev_chat->translation_domain); ?>
							<ol style="list-style-type:lower-alpha;">
								<li><?php _e('Confirm the <em>JavaScript origins</em> matches the URL for your site home.', $wpmudev_chat->translation_domain); ?></li>
								<li><?php _e('Copy the <strong>Client ID</strong> value into the field on the left.', $wpmudev_chat->translation_domain); ?></li>
							</ol>
						</li>
					</ol>
				</td>
			</tr>
		</table>
	</fieldset>
	<?php
}



function wpmudev_chat_form_section_blocked_words_global($form_section = 'banned') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Banned Words Filtering', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Control the banned words filter used for ALL chat sessions. Once enabled you can then control individual chat sessions via the Advanced tab.', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_words_active"><?php _e('Active for ALL chat sessions?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_words_active" name="chat[<?php echo $form_section; ?>][blocked_words_active]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('blocked_words_active', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('blocked_words_active', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
				<p class="description"><?php _e('Controls blocked word filtering for this specific chat.', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_words_active', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_words_replace"><?php _e('Replace blocked words with', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_blocked_words_replace" name="chat[<?php echo $form_section; ?>][blocked_words_replace]"
					value="<?php print $wpmudev_chat->get_option('blocked_words_replace', $form_section); ?>" /><br />
					<span class="description"><?php _e('Leave blank to remove', $wpmudev_chat->translation_domain); ?></span>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_words_replace', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_words"><?php _e('Blocked Words',
			 	$wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One word per line. Partial word matches will be included.', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-value-column">
				<textarea id="chat_blocked_words" name="chat[<?php echo $form_section; ?>][blocked_words]" cols="40" rows="30"><?php
					$blocked_words = $wpmudev_chat->get_option('blocked_words', $form_section);
					if ((isset($blocked_words)) && (is_array($blocked_words)) && (count($blocked_words))) {
						foreach($blocked_words as $bad_word) {
							echo trim($bad_word) ."\n";
						}
					}
				?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_words', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_blocked_words($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Blocked Words Filtering This Session', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_words_active"><?php _e('Active for this chat session?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">

				<select id="chat_blocked_words_active" name="chat[blocked_words_active]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('blocked_words_active', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('blocked_words_active', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_words_active', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_block_users_global($form_section = 'global') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Blocked Users by Email Address', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_users"><?php _e('Users (email address only)', $wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One user email per line. Patterns or ranges are not supported. Only effects public users. Does not support blocking Facebook, Twitter or Google+ users.', $wpmudev_chat->translation_domain); ?></p></td>
			<td class="chat-value-column">

				<textarea id="chat_blocked_users" name="chat[blocked_users]" cols="40" rows="8"><?php
					$blocked_users = $wpmudev_chat->get_option('blocked_users', $form_section);
					if ((isset($blocked_users)) && (is_array($blocked_users)) && (count($blocked_users))) {
						foreach($blocked_users as $blocked_user) {
							echo trim($blocked_user) ."\n";
						}
					}
				?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_users', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_block_urls_site($form_section = 'site') {
	global $wpmudev_chat;
	//$blocked_urls = $wpmudev_chat->get_option('blocked_urls', $form_section);
	$blocked_urls_str = '';
	$blocked_urls_array = $wpmudev_chat->get_option('blocked_urls', $form_section);
	if ((isset($blocked_urls_array)) && (is_array($blocked_urls_array)) && (count($blocked_urls_array))) {
		foreach($blocked_urls_array as $blocked_url) {
			$blocked_urls_str .= trim($blocked_url) ."\n";
		}
	}

	?>
	<fieldset>
		<legend><?php _e('Hide Bottom Corner Chat on URLs', $wpmudev_chat->translation_domain); ?></legend>

		<p class="info"><?php _e('This setting control where the Bottom Corner chat is shown within your site. This settings does not effect the WP toolbar chat menu, Page chat, Private chats or Widget chats.', $wpmudev_chat->translation_domain)?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_on_shortcode"><?php _e("Block on URLs with shortcode", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_on_shortcode" name="chat[blocked_on_shortcode]" >
					<option value="enabled" <?php print ($wpmudev_chat->get_option('blocked_on_shortcode', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('blocked_on_shortcode', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_on_shortcode', 'tip'); ?></td>
		</tr>
		</table>

		<p class="info"><?php _e('In addition to the above you can also exclude/include Bottom Corner chat on specific URLs using the options below. The URLs can be front or WPAdmin URLs.',
		 	$wpmudev_chat->translation_domain)?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_urls_action"><?php _e("Select Action", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_urls_action" name="chat[blocked_urls_action]" >
					<option value="include" <?php print ($wpmudev_chat->get_option('blocked_urls_action', $form_section) == 'include')?'selected="selected"':''; ?>><?php _e("Show on URLs ONLY", $wpmudev_chat->translation_domain); ?></option>
					<option value="exclude" <?php print ($wpmudev_chat->get_option('blocked_urls_action', $form_section) == 'exclude')?'selected="selected"':''; ?>><?php _e("Hide on URLs", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_urls_action', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_urls"><?php _e('URLs', $wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One URL per line.<br />URL can be relative or absolute and may contain parameters', $wpmudev_chat->translation_domain); ?></p></td>
			<td class="chat-value-column">

				<textarea id="chat_blocked_urls" name="chat[blocked_urls]" cols="40" rows="5"><?php echo $blocked_urls_str; ?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_urls', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_block_urls_widget($form_section = 'widget') {
	global $wpmudev_chat;

	$blocked_urls_str = '';
	$blocked_urls_array = $wpmudev_chat->get_option('blocked_urls', $form_section);
	if ((isset($blocked_urls_array)) && (is_array($blocked_urls_array)) && (count($blocked_urls_array))) {
		foreach($blocked_urls_array as $blocked_url) {
			$blocked_urls_str .= trim($blocked_url) ."\n";
		}
	}

	?>
	<fieldset>
		<legend><?php _e('Hide Widget Chat on URLs', $wpmudev_chat->translation_domain); ?></legend>

		<p class="info"><?php _e('This setting control where Widget chats are shown within your site. This settings does not effect the WP toolbar chat menu, Page chat, Private chats or Bottom Corner chat.', $wpmudev_chat->translation_domain)?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_on_shortcode"><?php _e("Block on URLs with shortcode", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_on_shortcode" name="chat[blocked_on_shortcode]" >
					<option value="enabled" <?php print ($wpmudev_chat->get_option('blocked_on_shortcode', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('blocked_on_shortcode', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_on_shortcode', 'tip'); ?></td>
		</tr>
		</table>

		<p class="info"><?php _e('In addition to the above you can also exclude/include Widget chats on specific URLs using the options below',
		 	$wpmudev_chat->translation_domain)?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_urls_action"><?php _e("Select Action", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_urls_action" name="chat[blocked_urls_action]" >
					<option value="include" <?php print ($wpmudev_chat->get_option('blocked_urls_action', $form_section) == 'include')?'selected="selected"':''; ?>><?php _e("Show on URLs ONLY", $wpmudev_chat->translation_domain); ?></option>
					<option value="exclude" <?php print ($wpmudev_chat->get_option('blocked_urls_action', $form_section) == 'exclude')?'selected="selected"':''; ?>><?php _e("Hide on URLs", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_urls_action', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_urls"><?php _e('URLs', $wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One URL per line.<br />URL can be relative or absolute and may contain parameters', $wpmudev_chat->translation_domain); ?></p></td>
			<td class="chat-value-column">

				<textarea id="chat_blocked_urls" name="chat[blocked_urls]" cols="40" rows="5"><?php echo $blocked_urls_str; ?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_urls', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_blocked_ip_addresses_global($form_section = 'global') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Blocked IP Addresses', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
<?php /* ?>
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_words_active"><?php _e('Active for ALL chat sessions?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_ip_addresses_active" name="chat[blocked_ip_addresses_active]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('blocked_ip_addresses_active', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('blocked_ip_addresses_active', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_ip_addresses_active', 'tip'); ?></td>
		</tr>
<?php */ ?>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_ip_message"><?php _e('Message displayed to user',
			 	$wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('When the user is banned this message will be display in place of all other chat message and information', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-value-column">
				<textarea id="chat_blocked_ip_message" name="chat[blocked_ip_message]" cols="40" rows="8"><?php
					echo $wpmudev_chat->get_option('blocked_ip_message', $form_section);
				?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_ip_message', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_ip_addresses"><?php _e('Blocked IP Addresses',
			 	$wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One IP Address per line. Patterns or ranges are not supported', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-value-column">
				<textarea id="chat_blocked_ip_addresses" name="chat[blocked_ip_addresses]" cols="40" rows="8"><?php
					$blocked_ip_addresses = $wpmudev_chat->get_option('blocked_ip_addresses', $form_section);
					if ((isset($blocked_ip_addresses)) && (is_array($blocked_ip_addresses)) && (count($blocked_ip_addresses))) {
						foreach($blocked_ip_addresses as $blocked_ip_address) {
							echo trim($blocked_ip_address) ."\n";
						}
					}
				?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_ip_addresses', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_blocked_ip_addresses($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Blocked IP Addresses', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_words_active"><?php _e('Active for this chat session?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_ip_addresses_active" name="chat[blocked_ip_addresses_active]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('blocked_ip_addresses_active', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('blocked_ip_addresses_active', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_ip_addresses_active', 'tip'); ?></td>
		</tr>
		</tr>
		</table>
	</fieldset>
	<?php
}


function wpmudev_chat_form_section_bottom_corner($form_section = 'site') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Bottom Corner Chat', $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
		<?php
			if (is_multisite()) {
				$bottom_corner_global = $wpmudev_chat->get_option('bottom_corner_global', 'network-site');
				//echo "bottom_corner_global=[". $bottom_corner_global ."]<br />";
				if ($bottom_corner_global == "enabled") {
					?>
					<tr>
						<td colspan="3">
							<p class="info"><?php _e('The bottom corner chat has been globally enabled by the Super Admin. This means message contained will be from ALL sites within the Multisite system not just messages from your site. Moderators will still have authority to manage messages and users. You may also chose to disable button corner chat for your site.', $wpmudev_chat->translation_domain); ?></p>
						</td>
					</tr>
					<?php
				}
			}
		?>
		<tr>
			<td class="chat-label-column"><label for="chat_site_bottom_corner"><?php _e('Display bottom corner chat on Front?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_site_bottom_corner" name="chat[bottom_corner]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bottom_corner', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bottom_corner', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bottom_corner', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_site_bottom_corner_wpadmin"><?php _e('Display bottom corner chat on WPAdmin?',
			 	$wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_site_bottom_corner_wpadmin" name="chat[bottom_corner_wpadmin]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bottom_corner_wpadmin', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bottom_corner_wpadmin', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bottom_corner_wpadmin', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_box_title"><?php _e("Title", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_box_title" name="chat[box_title]" value="<?php echo $wpmudev_chat->get_option('box_title', $form_section); ?>"
					size="5" placeholder="<?php echo wpmudev_chat_get_help_item('box_title', 'placeholder'); ?>" />

				<?php /* ?><p class="info"><?php _e('Title will be displayed in chat bar above messages', $wpmudev_chat->translation_domain); ?></p><?php */ ?>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('box_title', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}


function wpmudev_chat_form_section_tinymce_button_roles($form_section = 'global') {
	global $wpmudev_chat, $wp_roles;
	?>
	<fieldset>
		<legend><?php _e('WYSIWYG Chat button User Roles', $wpmudev_chat->translation_domain); ?></legend>

		<p class="info"><?php _e("Select which roles will use the Chat WYSIWYG button. Note the user must also have Edit capabilities for the Post type.", $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column-wide">
				<?php
				foreach ($wp_roles->role_names as $role => $name) {
					?><input type="checkbox" id="chat_tinymce_roles_<?php print $role; ?>" name="chat[tinymce_roles][]" class="chat_tinymce_roles" value="<?php print $role; ?>" <?php print (in_array($role, $wpmudev_chat->get_option('tinymce_roles', $form_section)) > 0)?'checked="checked"':''; ?> /> <label><?php _e($name, $wpmudev_chat->translation_domain); ?></label><br/>
				<?php
				}
				?>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('tinymce_roles', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_tinymce_button_post_types($form_section = 'page') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('WYSIWYG Chat button Post Types', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e("Select which Post Types will have the Chat WYSIWYG button available.", $wpmudev_chat->translation_domain); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column-wide">
				<?php
				foreach ((array) get_post_types( array( 'show_ui' => true ), 'name' ) as $post_type => $details) {
					if ($post_type == "attachment") continue;

				?><input type="checkbox" id="chat_tinymce_post_types_<?php print $post_type; ?>"
					name="chat[tinymce_post_types][]" class="chat_tinymce_roles"
					value="<?php print $post_type; ?>" <?php
					print (in_array($post_type, $wpmudev_chat->get_option('tinymce_post_types', $form_section)) > 0)?'checked="checked"':''; ?> /> <label><?php echo $details->labels->name; ?></label><br/><?php
				}
				?>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('tinymce_post_types', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_users_list($form_section = 'page') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Show list of chat Users', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e("With this option you can show the list of users participating in the chat session. You may position the users list on either side of the chat window. You may also chose to display the user avatar or name.", $wpmudev_chat->translation_domain); ?></p>
		<p class="info"><?php _e("For left right positions the avatar option works best. For above or below positions the name format works best.", $wpmudev_chat->translation_domain); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_users_list_position"><?php _e("Show Users Position", $wpmudev_chat->translation_domain);
			 	?></label></td>
			<td class="chat-value-column">
				<select id="chat_users_list_position" name="chat[users_list_position]" >
					<option value="none" <?php print ($wpmudev_chat->get_option('users_list_position', $form_section) == 'none') ? 'selected="selected"':'';
						?>><?php _e("Do not show users list", $wpmudev_chat->translation_domain); ?></option>
					<option value="right" <?php print ($wpmudev_chat->get_option('users_list_position', $form_section) == 'right') ? 'selected="selected"':'';
						?>><?php _e("To the Right of the Messages list", $wpmudev_chat->translation_domain); ?></option>
					<option value="left" <?php print ($wpmudev_chat->get_option('users_list_position', $form_section) == 'left') ? 'selected="selected"':'';
						?>><?php _e("To the left of the Messages list", $wpmudev_chat->translation_domain); ?></option>
					<option value="above" <?php print ($wpmudev_chat->get_option('users_list_position', $form_section) == 'above') ? 'selected="selected"':'';
						?>><?php _e("Above of the Messages list", $wpmudev_chat->translation_domain); ?></option>
					<option value="below" <?php print ($wpmudev_chat->get_option('users_list_position', $form_section) == 'below') ? 'selected="selected"':'';
						?>><?php _e("Below the the Messages list", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_position', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_width_tr">
			<td class="chat-label-column"><label for="chat_users_list_width"><?php _e('List Width/Height', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_users_list_width" name="chat[users_list_width]" class=""
					value="<?php print $wpmudev_chat->get_option('users_list_width', $form_section); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('users_list_width', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_width', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_background_color_tr">
			<td class="chat-label-column"><label for="chat_users_list_background_color"><?php _e('Background Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_users_list_background_color" name="chat[users_list_background_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('users_list_background_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('users_list_background_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_background_color', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_width_tr">
			<td class="chat-label-column"><label for="chat_users_list_threshold_delete"><?php _e('Remove Inactive User after',
			 $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_users_list_threshold_delete" name="chat[users_list_threshold_delete]" class=""
					value="<?php print $wpmudev_chat->get_option('users_list_threshold_delete', $form_section); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('users_list_threshold_delete', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_threshold_delete', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_show_tr">
			<?php $users_list_show = $wpmudev_chat->get_option('users_list_show', $form_section); ?>

			<td class="chat-label-column"><label for="chat_users_list_show"><?php _e("Show Users List", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_users_list_show" name="chat[users_list_show]" >
					<option value="avatar" <?php print ($users_list_show == 'avatar')?'selected="selected"':''; ?>><?php
					 	_e("Avatars", $wpmudev_chat->translation_domain); ?></option>
					<option value="name" <?php print ($users_list_show == 'name')?'selected="selected"':''; ?>><?php
					 	_e("Names", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_show', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_avatar_width_tr" <?php if ($users_list_show != "avatar") { echo ' style="display:none" '; } ?>>
			<td class="chat-label-column"><label for="chat_users_list_avatar_width"><?php _e('User Avatar Width', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_users_list_avatar_width" name="chat[users_list_avatar_width]" class=""
					value="<?php echo wpmudev_chat_check_size_qualifier($wpmudev_chat->get_option('users_list_avatar_width', $form_section), array('px')); ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('users_list_avatar_width', 'placeholder'); ?>"/>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_avatar_width', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_name_color_tr"  <?php if ($users_list_show != "name") { echo ' style="display:none" '; } ?>>
			<td class="chat-label-column"><label for="chat_users_list_name_color"><?php _e('User Name Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_users_list_name_color" name="chat[users_list_name_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('users_list_name_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('users_list_name_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_name_color', 'tip'); ?></td>
		</tr>
		<tr id="chat_users_list_font_family_tr"  <?php if ($users_list_show != "name") { echo ' style="display:none" '; } ?>>
			<td class="chat-label-column"><label for="chat_users_list_font_family"><?php _e("Font", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_box_font_family" name="chat[users_list_font_family]">
					<option value=""><?php _e("-- None inherit from theme --", $wpmudev_chat->translation_domain); ?></option>
					<?php foreach ($wpmudev_chat->_chat_options_defaults['fonts_list'] as $font_name => $font) { ?>
						<option value="<?php print $font_name; ?>" <?php print ($wpmudev_chat->get_option('users_list_font_family', $form_section) == $font_name)?'selected="selected"':''; ?> ><?php print $font_name; ?></option>
					<?php } ?>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_font_family', 'tip') ; ?></td>
		</tr>
		<tr id="chat_users_list_font_size_tr" <?php if ($users_list_show != "name") { echo ' style="display:none" '; } ?>>
			<td class="chat-label-column"><label for="chat_users_list_font_size"><?php _e("Font size", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<?php $users_list_font_size = trim($wpmudev_chat->get_option('users_list_font_size', $form_section)); ?>
				<input type="text" name="chat[users_list_font_size]" id="chat_users_list_font_size"
					value="<?php echo (!empty($users_list_font_size)) ? wpmudev_chat_check_size_qualifier($users_list_font_size) : ''; ?>"
					placeholder="<?php echo wpmudev_chat_get_help_item('users_list_font_size', 'placeholder'); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('users_list_font_size', 'tip'); ?></td>
		</tr>

		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_blocked_urls_admin($form_section = 'global') {
	global $wpmudev_chat;

	$blocked_admin_urls_str = '';
	$blocked_admin_urls_array = $wpmudev_chat->get_option('blocked_admin_urls', $form_section);
	if ((isset($blocked_admin_urls_array)) && (is_array($blocked_admin_urls_array)) && (count($blocked_admin_urls_array))) {
		foreach($blocked_admin_urls_array as $blocked_admin_url) {
			$blocked_admin_urls_str .= trim($blocked_admin_url) ."\n";
		}
	}

	?>
	<fieldset>
		<legend><?php _e('Hide Chat on WP Admin URLs', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('This section control how Chat works within the WordPress admin area. These are global settings and effect all users', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_blocked_admin_urls_action"><?php _e("Select Action", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_admin_urls_action" name="chat[blocked_admin_urls_action]" >
					<option value="include" <?php print ($wpmudev_chat->get_option('blocked_admin_urls_action', $form_section) == 'include')?'selected="selected"':''; ?>><?php _e("Show on Admin URLs ONLY", $wpmudev_chat->translation_domain); ?></option>
					<option value="exclude" <?php print ($wpmudev_chat->get_option('blocked_admin_urls_action', $form_section) == 'exclude')?'selected="selected"':''; ?>><?php _e("Hide on Admin URLs", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_admin_urls_action', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_admin_urls"><?php _e('WP Admin URLs', $wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One URL per line.<br />URL can be relative or absolute and may contain parameters', $wpmudev_chat->translation_domain); ?></p></td>
			<td class="chat-value-column">

				<textarea id="chat_blocked_admin_urls" name="chat[blocked_admin_urls]" cols="40" rows="5"><?php echo $blocked_admin_urls_str; ?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_admin_urls', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_blocked_urls_front($form_section = 'global') {
	global $wpmudev_chat;

	$blocked_front_urls_str = '';
	$blocked_front_urls_array = $wpmudev_chat->get_option('blocked_front_urls', $form_section);
	if ((isset($blocked_front_urls_array)) && (is_array($blocked_front_urls_array)) && (count($blocked_front_urls_array))) {
		foreach($blocked_front_urls_array as $blocked_front_url) {
			$blocked_front_urls_str .= trim($blocked_front_url) ."\n";
		}
	}

	$chat_load_jscss_all = $wpmudev_chat->get_option('load_jscss_all', $form_section);
	?>
	<fieldset>
		<p class="info"><?php _e('By default Chat will load the needed JS/CSS on all Front URLs. This is to facilitate Private chat invitations as well as interactions with the WP toolbar Chat section. If disabled, the JS/CSS files will not be loaded. Also the WP toolbar Chat menu as well as Private chats will not be displayed.', $wpmudev_chat->translation_domain); ?></p>

		<p class="info"><?php printf( __("Note this ONLY effects URLs where you don't have Page chat (shortcode), Widget Chat or Bottom Corner chat already displayed. You can disable the %s and %s chat within the the Setting section.", $wpmudev_chat->translation_domain),
		'<a href="admin.php?page=chat_settings_panel_site#chat_advanced_panel">' . __('Bottom Corner', $wpmudev_chat->translation_domain) .'</a>',
		'<a href="admin.php?page=chat_settings_panel_widget#chat_advanced_panel">' . __('Widget', $wpmudev_chat->translation_domain) .'</a>'); ?></p>


		<legend><?php _e('Hide Chat on WP Front URLs', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_load_jscss_all"><?php _e("Load JS/CSS on ALL URLs", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_load_jscss_all" name="chat[load_jscss_all]" >
					<option value="enabled" <?php print ($chat_load_jscss_all == 'enabled') ? 'selected="selected"':''; ?>><?php _e("Yes, All URLs", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($chat_load_jscss_all == 'disabled') ?'selected="selected"':''; ?>><?php _e("No, Only URLs where needed for shortcode, widgets, etc.", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('load_jscss_all', 'tip'); ?></td>
		</tr>
		<tr id="chat_front_urls_actions_tr" <?php if ($chat_load_jscss_all == "disabled") { echo ' style="display:none;" '; } ?>>
			<td class="chat-label-column"><label for="chat_blocked_front_urls_action"><?php _e("Select Action", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_blocked_front_urls_action" name="chat[blocked_front_urls_action]" >
					<option value="include" <?php print ($wpmudev_chat->get_option('blocked_front_urls_action', $form_section) == 'include')?'selected="selected"':''; ?>><?php _e("Show on Front URLs ONLY", $wpmudev_chat->translation_domain); ?></option>
					<option value="exclude" <?php print ($wpmudev_chat->get_option('blocked_front_urls_action', $form_section) == 'exclude')?'selected="selected"':''; ?>><?php _e("Hide on Front URLs", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_front_urls_action', 'tip'); ?></td>
		</tr>
		<tr id="chat_front_urls_list_tr" <?php if ($chat_load_jscss_all == "disabled") { echo ' style="display:none;" '; } ?>>
			<td class="chat-label-column chat-label-column-top"><label for="chat_blocked_front_urls"><?php _e('WP Front URLs', $wpmudev_chat->translation_domain); ?></label>
				<p class="description"><?php _e('One URL per line.<br />URL can be relative or absolute and may contain parameters', $wpmudev_chat->translation_domain); ?></p></td>
			<td class="chat-value-column">

				<textarea id="chat_blocked_front_urls" name="chat[blocked_front_urls]" cols="40" rows="5"><?php echo $blocked_front_urls_str; ?></textarea>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('blocked_front_urls', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_buddypress_group_information($form_section = 'global') {
	global $wpmudev_chat;

	?>
	<fieldset>
		<legend><?php _e('Group Information', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Controls the Group menu label and URL slug within the BuddyPress Group pages.', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_bp_menu_label"><?php _e("Menu Label", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_bp_menu_label" name="chat[bp_menu_label]" value="<?php echo $wpmudev_chat->get_option('bp_menu_label', $form_section); ?>"
					size="5" placeholder="<?php echo wpmudev_chat_get_help_item('bp_menu_label', 'placeholder'); ?>" />

			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_menu_label', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_bp_menu_slug"><?php _e("Page Slug", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_bp_menu_slug" name="chat[bp_menu_slug]" value="<?php echo $wpmudev_chat->get_option('bp_menu_slug', $form_section); ?>"
					size="5" placeholder="<?php echo wpmudev_chat_get_help_item('bp_menu_slug', 'placeholder'); ?>" />

				<?php /* ?><p class="info"><?php _e('Title will be displayed in chat bar above messages', $wpmudev_chat->translation_domain); ?></p><?php */ ?>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_menu_slug', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_buddypress_group_hide_site($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Hide Bottom Corner Chats', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Controls the display of the Bottom Corner chat within the BuddyPress Group pages. This setting will override the blocked URLs set within the Settings Site tab.', $wpmudev_chat->translation_domain); ?></p>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_bp_group_show_site"><?php _e("Show on Groups pages", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_bp_group_show_site" name="chat[bp_group_show_site]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bp_group_show_site', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bp_group_show_site', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_group_show_site', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_bp_group_admin_show_site"><?php _e("Show on Groups admin pages", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_bp_group_admin_show_site" name="chat[bp_group_admin_show_site]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bp_group_admin_show_site', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bp_group_admin_show_site', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_group_admin_show_site', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_buddypress_group_hide_widget($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Hide Widget Chats', $wpmudev_chat->translation_domain); ?></legend>
		<p class="info"><?php _e('Controls the display of the Widget chat within the BuddyPress Group pages. This setting will override the blocked URLs set within the Settings Widget tab.', $wpmudev_chat->translation_domain); ?></p>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_bp_group_show_widget"><?php _e("Show on Groups pages", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_bp_group_show_widget" name="chat[bp_group_show_widget]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bp_group_show_widget', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bp_group_show_widget', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_group_show_widget', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_bp_group_admin_show_widget"><?php _e("Show on Groups admin pages", $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<select id="chat_bp_group_admin_show_widget" name="chat[bp_group_admin_show_widget]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bp_group_admin_show_widget', $form_section) == 'enabled')?'selected="selected"':''; ?>><?php
					 	_e("Enabled", $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bp_group_admin_show_widget', $form_section) == 'disabled')?'selected="selected"':''; ?>><?php
					 	_e("Disabled", $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_group_admin_show_widget', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_buddypress_group_admin_colors($form_section = 'global') {
	global $wpmudev_chat;
	?>
	<fieldset>
		<legend><?php _e('Colors for BuddyPress Group Admin Chat form', $wpmudev_chat->translation_domain); ?></legend>
		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_bp_form_background_color"><?php _e('Background Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_bp_form_background_color" name="chat[bp_form_background_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('bp_form_background_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('bp_form_background_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="chat_bp_form_label_color"><?php _e('Label Color', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<input type="text" id="chat_bp_form_label_color" name="chat[bp_form_label_color]" class="pickcolor_input"
					value="<?php echo $wpmudev_chat->get_option('bp_form_label_color', $form_section); ?>"
					data-default-color="<?php echo $wpmudev_chat->get_option('bp_form_label_color', $form_section); ?>" />
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bp_form_label_color', 'tip'); ?></td>
		</tr>

		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_wpadmin($form_section = 'global') {
	global $wpmudev_chat, $current_user;

	//echo "user_meta defaults<pre>"; print_r($wpmudev_chat->_chat_options_defaults['user_meta']); echo "</pre>";

	?>
	<fieldset>
		<legend><?php _e('User Profile defaults', $wpmudev_chat->translation_domain); ?></legend>
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
		<tr>
			<td class="chat-label-column"><label for="wpmudev_chat_name_display"><?php _e('In Chat Sessions show name as', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
	            <select name="chat_user_meta[chat_name_display]" id="wpmudev_chat_name_display">
	            	<option value="display_name" <?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_name_display'] == 'display_name' ) {
						echo ' selected="selected" '; } ?>><?php echo __('Display Name', $wpmudev_chat->translation_domain) ?></option>
	            	<option value="user_login" <?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_name_display'] == 'user_login' ) {
						echo ' selected="selected" '; } ?>><?php echo __('User Login', $wpmudev_chat->translation_domain) ?></option>
	            </select>
			</td>
			<td class="chat-help-column"><?php //echo wpmudev_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="wpmudev_chat_wp_admin"><?php _e('Show Chats within WPAdmin', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
	            <select name="chat_user_meta[chat_wp_admin]" id="wpmudev_chat_wp_admin">
	            	<option value="enabled"<?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_wp_admin'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
						_e('Enabled', $wpmudev_chat->translation_domain); ?></option>
	            	<option value="disabled"<?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_wp_admin'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
						_e('Disabled', $wpmudev_chat->translation_domain); ?></option>
	            </select>
				<p class="description"><?php _e('This will disable all Chat functions including WordPress toolbar menu', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php //echo wpmudev_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column"><label for="wpmudev_chat_wp_toolbar"><?php _e('Show Chat WordPress toolbar menu?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
	            <select name="chat_user_meta[chat_wp_toolbar]" id="wpmudev_chat_wp_toolbar">
	            	<option value="enabled"<?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_wp_toolbar'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
						_e('Enabled', $wpmudev_chat->translation_domain); ?></option>
	            	<option value="disabled"<?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_wp_toolbar'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
						_e('Disabled', $wpmudev_chat->translation_domain); ?></option>
	            </select>
			</td>
			<td class="chat-help-column"><?php //echo wpmudev_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
		</tr>
		<tr>
			<td class="chat-label-column" style="veritcal-align:top"><label for="wpmudev_chat_users_listing"><?php _e('Show Chat Status column on Users > All Users listing?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
	            <select name="chat_user_meta[chat_users_listing]" id="wpmudev_chat_users_listing">
	            	<option value="enabled"<?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_users_listing'] == 'enabled' ) { echo ' selected="selected" '; } ?>><?php
						_e('Enabled', $wpmudev_chat->translation_domain); ?></option>
	            	<option value="disabled"<?php if ( $wpmudev_chat->_chat_options_defaults['user_meta']['chat_users_listing'] == 'disabled' ) { echo ' selected="selected" '; } ?>><?php
						_e('Disabled', $wpmudev_chat->translation_domain); ?></option>
	            </select>
				<p class="description"><?php _e('User must have <strong>list_users</strong> role capability', $wpmudev_chat->translation_domain); ?></p>
			</td>
			<td class="chat-help-column"><?php //echo wpmudev_chat_get_help_item('bp_form_background_color', 'tip'); ?></td>
		</tr>

		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_form_section_bottom_corner_network($form_section = 'network-site') {
	global $wpmudev_chat;

	//echo "chat<pre>"; print_r($wpmudev_chat); echo "</pre>";

	?>
	<fieldset>
		<legend><?php _e('Bottom Corner Chat', $wpmudev_chat->translation_domain); ?></legend>

		<table border="0" cellpadding="4" cellspacing="0">
		<tr>
			<td class="chat-label-column"><label for="chat_site_bottom_corner_global"><?php _e('Enable global bottom corner chat?', $wpmudev_chat->translation_domain); ?></label></td>
			<td class="chat-value-column">
				<p class="description"><?php _e('Enabling global bottom corner chat means message posted from one site will be shown to all sites within your Multisite system. Local site moderators will still be able to clear/delete message, etc. Also local admins will still be able to disable bottom corner chat from showing within their site.', $wpmudev_chat->translation_domain); ?></p>
				<select id="chat_site_bottom_corner_global" name="chat[bottom_corner_global]">
					<option value="enabled" <?php print ($wpmudev_chat->get_option('bottom_corner_global', $form_section) == 'enabled')?'selected="selected"':'';
						?>><?php _e('Enabled', $wpmudev_chat->translation_domain); ?></option>
					<option value="disabled" <?php print ($wpmudev_chat->get_option('bottom_corner_global', $form_section) == 'disabled')?'selected="selected"':'';
						?>><?php _e('Disabled', $wpmudev_chat->translation_domain); ?></option>
				</select>
			</td>
			<td class="chat-help-column"><?php echo wpmudev_chat_get_help_item('bottom_corner_global', 'tip'); ?></td>
		</tr>
		</table>
	</fieldset>
	<?php
}

function wpmudev_chat_show_size_selector($field_name, $form_section = "page") {
	global $wpmudev_chat;

	$size_values = array(
		'pc'	=>	__('pc', $wpmudev_chat->translation_domain),
		'pt'	=>	__('pt', $wpmudev_chat->translation_domain),
		'px'	=>	__('px', $wpmudev_chat->translation_domain),
		'em'	=>	__('em', $wpmudev_chat->translation_domain),
		'%'		=>	__('%', $wpmudev_chat->translation_domain)
	);

	$field_value = $wpmudev_chat->get_option($field_name, $form_section);

	?><select id="chat_<?php echo $field_name; ?>" name="chat[<?php echo $field_name; ?>]" class="size_qualifier_field"><?php

	foreach($size_values as $size_key => $size_val) {
		?><option value="<?php echo $size_key; ?>" <?php print ($field_value == $size_key)?'selected="selected"':''; ?>><?php echo $size_val; ?></option><?php
	}
	?></select><?php
}