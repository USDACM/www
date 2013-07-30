<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<?php
			wp_print_styles( array('farbtastic','wpmudev-chat-admin-css') );
			wp_print_scripts( array('jquery', 'tiny_mce_popup.js', 'mctabs.js', 'validate.js', 'form_utils.js', 'editable_selects.js', 'farbtastic', 'jquery-cookie', 'wpmudev-chat-admin-js', 'wpmudev-chat-admin-tinymce-js', 'wpmudev-chat-admin-farbtastic-js') );
		?>
		<script type="text/javascript">
			<?php
			// We basically want to built to options lists. The 'wpmudev_chat_default_options' will match our Settings panel.
			// The 'wpmudev_chat_current_options' will match the parsed shortcode combined with the default_options values.
			?>
			var wpmudev_chat_default_options = {
				<?php
					foreach($this->_chat_options['page'] as $key => $val) {
						if (($key == "blog_id") || ($key == "id") || ($key == "session_type") || ($key == "session_status")) continue;

						if ($key == 'blocked_words_active') {
							if ((!$this->_chat_options['banned']['blocked_words_active'] == "enabled") || ($val == '')) {
								$val = 'disabled';
							}
						}
						?>'<?php echo $key; ?>': "<?php
						if (is_array($val)) {
							echo join(',', $val);
						} else {
							echo $val;
						} ?>", <?php
					}
				?>
			};

			if ((wpmudev_chat_default_options.login_options != undefined) && (wpmudev_chat_default_options.login_options.length > 0)) {

				// Convert to an array...
				wpmudev_chat_default_options.login_options = wpmudev_chat_default_options.login_options.split(',');

				// ... Then add our default value if needed...
				if ( jQuery.inArray("current_user", wpmudev_chat_default_options.login_options) == -1)
					wpmudev_chat_default_options.login_options.push('current_user');

				// ...finally sort the array
				wpmudev_chat_default_options.login_options.sort();
			}

			if ((wpmudev_chat_default_options.moderator_roles != undefined) && (wpmudev_chat_default_options.moderator_roles.length > 0)) {

				// Convert to an array...
				wpmudev_chat_default_options.moderator_roles = wpmudev_chat_default_options.moderator_roles.split(',');

				// ... Then add our default value if needed...
				if (jQuery.inArray("administrator", wpmudev_chat_default_options.moderator_roles) == -1)  {
					wpmudev_chat_default_options.moderator_roles.push('administrator');

					// ...finally sort the array
				wpmudev_chat_default_options.moderator_roles.sort();
			}

			var wpmudev_chat_current_options = {};
			for (attr in wpmudev_chat_default_options) {
					wpmudev_chat_current_options[attr] = wpmudev_chat_default_options[attr];
				}
			}

			var wpmudev_chat_shortcode_str = '';
			var _tmp_chat_shortcode = tinyMCEPopup.editor.getContent().split('[chat ');
			if (_tmp_chat_shortcode.length > 1) {
				_tmp_chat_shortcode = _tmp_chat_shortcode[1].split(']');
				wpmudev_chat_shortcode_str = '[chat '+_tmp_chat_shortcode[0]+']';

				// Parse the WP shortcode. Taken from shortcode.js
				var wpmudev_chat_shortcode_pairs   = {},
					numeric = [],
					pattern, match;

				pattern = /(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/g;

				// Map zero-width spaces to actual spaces.
				wpmudev_chat_shortcode_str = wpmudev_chat_shortcode_str.replace( /[\u00a0\u200b]/g, ' ' );

				// Match and normalize attributes.
				while ( (match = pattern.exec( wpmudev_chat_shortcode_str )) ) {
					if ( match[1] ) {
						wpmudev_chat_shortcode_pairs[ match[1].toLowerCase() ] = match[2];
					} else if ( match[3] ) {
						wpmudev_chat_shortcode_pairs[ match[3].toLowerCase() ] = match[4];
					} else if ( match[5] ) {
						wpmudev_chat_shortcode_pairs[ match[5].toLowerCase() ] = match[6];
					}
				}
				// Now that we have the shortcode parsed into object pairs we apply the values to our wpmudev_chat_current_options object which is then
				// loaded to the form fields later.
				for (attr in wpmudev_chat_shortcode_pairs) {
					//var attr_val = wpmudev_chat_shortcode_pairs[attr];

					// For the login_options and moderator_roles we convert to array (easier to work with)...
					if ((attr == "login_options") || (attr == "moderator_roles")) {
						var attr_array = wpmudev_chat_current_options[attr] = wpmudev_chat_shortcode_pairs[attr].split(',');
						if (attr_array.length > 0) {

							// If we have a non-empty array we loop through and trim the elements. No whitespace allowed!
							for (attr_idx in attr_array) {
								attr_array[attr_idx] = jQuery.trim(attr_array[attr_idx]);
							}
						}

						// Check for the existance of the 'key' items. These values are ALWAYS set.
						if ( (attr == "login_options") && (jQuery.inArray("current_user", attr_array) == -1) ) {
							attr_array.push('current_user');
						} else if ( (attr == "moderator_roles") && (jQuery.inArray("administrator", attr_array) == -1) ) {
							attr_array.push('administrator');
						}
						// Reassign the value back to our current options array.
						wpmudev_chat_current_options[attr] = attr_array;
					} else {
						wpmudev_chat_current_options[attr] = wpmudev_chat_shortcode_pairs[attr];
					}
				}
			}

		</script>
		<title><?php _e('WordPress Chat', $this->translation_domain); ?></title>
	</head>
	<body style="display: none">
		<div id="wpmudev-chat-wrap" class="wrap wpmudev-chat-wrap-popup">
			<form action="#">
				<div class="tabs">
					<ul>
						<li id="wpmudev-chat-box-appearance-tab" class="current"><span><a
							 href="javascript:mcTabs.displayTab('wpmudev-chat-box-appearance-tab', 'wpmudev-chat-box-appearance-panel');"
							onmousedown="return false;"><?php _e('Box Appearance', $this->translation_domain); ?></a></span></li>

						<li id="wpmudev-chat-messages-appearance-tab"><span><a
							href="javascript:mcTabs.displayTab('wpmudev-chat-messages-appearance-tab', 'wpmudev-chat-messages-appearance-panel');"
							onmousedown="return false;"><?php _e('Message Appearance', $this->translation_domain); ?></a></span></li>

						<li id="wpmudev-chat-messages-input-tab"><span><a
							href="javascript:mcTabs.displayTab('wpmudev-chat-messages-input-tab', 'wpmudev-chat-messages-input-panel');"
							onmousedown="return false;"><?php _e('Message Input', $this->translation_domain); ?></a></span></li>

						<li id="wpmudev-chat-users-list-tab"><span><a
							href="javascript:mcTabs.displayTab('wpmudev-chat-users-list-tab', 'wpmudev-chat-users-list-panel');"
							onmousedown="return false;"><?php _e('Users List', $this->translation_domain); ?></a></span></li>

						<li id="wpmudev-chat-authentication-tab"><span><a
							 href="javascript:mcTabs.displayTab('wpmudev-chat-authentication-tab', 'wpmudev-chat-authentication-panel');"
							 onmousedown="return false;"><?php _e('Authentication', $this->translation_domain); ?></a></span></li>

						<li id="wpmudev-chat-advanced-tab"><span><a
							 href="javascript:mcTabs.displayTab('wpmudev-chat-advanced-tab', 'wpmudev-chat-advanced-panel');"
							 onmousedown="return false;"><?php _e('Advanced', $this->translation_domain); ?></a></span></li>
					</ul>
				</div>
				<?php $form_section = "page"; ?>
				<div class="panel_wrapper">
					<div id="wpmudev-chat-box-appearance-panel" class="panel current">
						<?php wpmudev_chat_form_section_information($form_section); ?>
						<?php wpmudev_chat_form_section_container($form_section); ?>
					</div>
					<div id="wpmudev-chat-messages-appearance-panel" class="panel">
						<?php wpmudev_chat_form_section_messages_wrapper($form_section); ?>
						<?php wpmudev_chat_form_section_messages_rows($form_section); ?>
					</div>
					<div id="wpmudev-chat-messages-input-panel" class="panel">
						<?php wpmudev_chat_form_section_messages_input($form_section); ?>
					</div>
					<div id="wpmudev-chat-users-list-panel" class="panel">
						<?php wpmudev_chat_users_list($form_section); ?>
					</div>
					<div id="wpmudev-chat-authentication-panel" class="panel">
						<?php wpmudev_chat_form_section_login_options($form_section); ?>
						<?php wpmudev_chat_form_section_login_view_options($form_section); ?>
						<?php wpmudev_chat_form_section_moderator_roles($form_section); ?>
					</div>
					<div id="wpmudev-chat-advanced-panel" class="panel">
						<?php wpmudev_chat_form_section_logs($form_section); ?>
						<?php wpmudev_chat_form_section_logs_limit($form_section); ?>
						<?php wpmudev_chat_form_section_session_messages($form_section); ?>

						<?php /* if ($this->get_option('blocked_ip_addresses_active', 'global') == "enabled") {
							wpmudev_chat_form_section_blocked_ip_addresses($form_section);
						} */ ?>
						<?php if ($this->get_option('blocked_words_active', 'banned') == "enabled") {
							wpmudev_chat_form_section_blocked_words($form_section);
						} ?>

					</div>
				</div>

				<div class="mceActionPanel">
					<div style="float: left; width: 40%;">
						<input type="button" id="cancel" name="cancel"
							value="<?php _e('Cancel', $this->translation_domain); ?>"
							title="<?php _e('Cancel change and close popup', $this->translation_domain); ?>"
							onclick="tinyMCEPopup.close();" />
					</div>

					<div style="float: right; width: 60%;">
						<input type="submit" id="reset" class="mceButton" name="reset" style="float: right;"
							value="<?php _e('Defaults', $this->translation_domain); ?>"
							title="<?php _e('Reset shortcode to default values', $this->translation_domain); ?>" />
						<input type="submit" id="insert" name="insert" style="float: right;"
							value="<?php _e('Insert', $this->translation_domain); ?>"
							title="<?php _e('Save settings and insert shortcode at cursor', $this->translation_domain); ?>" />
					</div>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			jQuery(window).load(function() {

				// This code takes the JS wpmudev_chat_current_options array and applies the value to the form elements.
				for (attr in wpmudev_chat_current_options) {
					if (attr == "id") continue;

					//var attr_val = wpmudev_chat_shortcode_pairs[attr];

					// For checkboxes we need to build the unique ID and check the box.
					if ((attr == "login_options") || (attr == "moderator_roles")) {
						// But first we need to unset all checkboxes in the set.
						jQuery('input.chat_'+attr).each(function() {
							jQuery(this).attr('checked', false);
						});

						for(attr_value in wpmudev_chat_current_options[attr]) {
							jQuery("#chat_"+attr+"_"+wpmudev_chat_current_options[attr][attr_value]).attr('checked', 'checked');
						}

					} else {
						if (attr == "row_name_avatar") {
							if (wpmudev_chat_current_options[attr] == "avatar") {
								jQuery('tr#chat_row_avatar_width_tr').show();
								jQuery('tr#chat_row_name_color_tr').hide();
								jQuery('tr#chat_row_moderator_name_color_tr').hide();

							} else if (wpmudev_chat_current_options[attr] == "name") {
								jQuery('tr#chat_row_avatar_width_tr').hide();
								jQuery('tr#chat_row_name_color_tr').show();
								jQuery('tr#chat_row_moderator_name_color_tr').show();
							}
						}

						if (attr == "users_list_show") {
							if (wpmudev_chat_current_options[attr] == "avatar") {
								jQuery('tr#chat_users_list_avatar_width_tr').show();
								jQuery('tr#chat_users_list_name_color_tr').hide();
								jQuery('tr#chat_users_list_font_family_tr').hide();
								jQuery('tr#chat_users_list_font_size_tr').hide();

							} else if (wpmudev_chat_current_options[attr] == "name") {
								jQuery('tr#chat_users_list_avatar_width_tr').hide();
								jQuery('tr#chat_users_list_name_color_tr').show();
								jQuery('tr#chat_users_list_font_family_tr').show();
								jQuery('tr#chat_users_list_font_size_tr').show();
							}
						}
						jQuery("#chat_"+attr).val(wpmudev_chat_current_options[attr]);
						if (jQuery("input#chat_"+attr).hasClass('pickcolor_input')) {
							jQuery("#chat_"+attr).attr('value', wpmudev_chat_current_options[attr]);
							jQuery("#chat_"+attr).attr('data-default-color', wpmudev_chat_current_options[attr]);
							jQuery("#chat_"+attr).css('background-color', wpmudev_chat_current_options[attr]);

						}
					}
				}
			});
		</script>
		<?php
			// Force print of tooltip JS/CSS
			$this->tips->initialize();
		?>
	</body>
</html>
<?php
exit(0);