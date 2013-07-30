// See themes-options.js for better method. Need to convert the code below to work the same. The code below does not 
// initialize the color wheel properly with the input field color value. 
(function ($) {
	$(document).ready(function () {
		
		// When the 'Reset' form button is clicked we remove all shortcode parameters. This will foce the shortcode to inherit all settings
		jQuery('input#reset').click(function() {
			output  = '[chat ';
			if ((wpmudev_chat_current_options.id != undefined) && (wpmudev_chat_current_options.id != ''))
				output = output+'id="'+wpmudev_chat_current_options.id+'" ]';

			if (wpmudev_chat_shortcode_str == '') {
				tinyMCEPopup.execCommand('mceReplaceContent', false, output);
			} else {
				tinyMCEPopup.execCommand('mceSetContent', false, tinyMCEPopup.editor.getContent().replace(wpmudev_chat_shortcode_str, output));
			}

			// Return
			tinyMCEPopup.close();						
		});

		// When the 'Insert' form button button is clicked we go through the form fields and check the value against the
		// default options. If there is a difference we add that parameter set to the shortcode output
		jQuery('input#insert').click(function() {
			output  ='[chat ';
			if ((wpmudev_chat_current_options.id != undefined) && (wpmudev_chat_current_options.id != ''))
				output = output+'id="'+wpmudev_chat_current_options.id+'" ';

			for (var chat_form_key in wpmudev_chat_default_options) {
				//console.log("chat_form_key=["+chat_form_key+"]");
				
				if ((chat_form_key == "id") || (chat_form_key == "blog_id") || (chat_form_key == "session_type") || (chat_form_key == "session_status") || (chat_form_key == "tinymce_roles") || (chat_form_key == "tinymce_post_types")) {
					continue;
					
				} else if (chat_form_key == "login_options") {
					var chat_login_options_arr = [];
					
					jQuery('input.chat_login_options:checked').each(function() {
						if (jQuery(this).val() != "current_user") {
							chat_login_options_arr.push(jQuery(this).val());
						}
					});					
					
					// So we don't add the empty item
					if (chat_login_options_arr.length > 0) {

						// Add our default item
						chat_login_options_arr.push('current_user');
						chat_login_options_arr.sort();

						wpmudev_chat_default_options.login_options.sort();

						if (wpmudev_chat_default_options.login_options.join(',') != jQuery.trim(chat_login_options_arr.join(','))) {
							output += 'login_options="'+jQuery.trim(chat_login_options_arr.join(','))+'" ';
						}
					}
					
				} else if (chat_form_key == "moderator_roles") {
					var chat_moderator_roles_arr = [];

					jQuery('input.chat_moderator_roles:checked').each(function() {
						if (jQuery(this).val() != "administrator") {
							chat_moderator_roles_arr.push(jQuery(this).val());
						}
					});

					// So we don't add the empty item
					if (chat_moderator_roles_arr.length > 0) {

						// Add our default item
						chat_moderator_roles_arr.push('administrator');								
						chat_moderator_roles_arr.sort();

						wpmudev_chat_default_options.moderator_roles.sort();
						
						if (wpmudev_chat_default_options.moderator_roles.join(',') != jQuery.trim(chat_moderator_roles_arr.join(','))) {
							output += 'moderator_roles="'+jQuery.trim(chat_moderator_roles_arr.join(','))+'" ';
						}
					}
					
				} else if (chat_form_key == "noauth_view") {
					var chat_noauth_view = '';
					jQuery('input.chat_noauth_view:checked').each(function() {
						chat_noauth_view = jQuery(this).val();
						jQuery.trim(chat_noauth_view);
					});

					if (wpmudev_chat_default_options.noauth_view != chat_noauth_view) {
						output += 'noauth_view="'+chat_noauth_view+'" ';
					}
				} else {
					var chat_form_value = jQuery.trim(jQuery('#chat_'+chat_form_key).val());
					if ((chat_form_key == "blocked_words_active") && (chat_form_value == '')) {
						chat_form_value = 'disabled';
					}
					if (chat_form_value != wpmudev_chat_default_options[chat_form_key]) {
						output += chat_form_key+'="'+chat_form_value+'" ';
					}
				}
			}
			output += ']';

			if (wpmudev_chat_shortcode_str == '') {
				tinyMCEPopup.execCommand('mceReplaceContent', false, output);
			} else {
				tinyMCEPopup.execCommand('mceSetContent', false, tinyMCEPopup.editor.getContent().replace(wpmudev_chat_shortcode_str, output));
			}

			// Return
			tinyMCEPopup.close();						
		});		
	});
})(jQuery);
