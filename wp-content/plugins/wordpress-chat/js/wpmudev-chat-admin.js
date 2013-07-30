// See themes-options.js for better method. Need to convert the code below to work the same. The code below does not 
// initialize the color wheel properly with the input field color value. 
(function ($) {
	jQuery(document).ready(function () {
		
		/* controls the show/hide of the Row Name/Avatar form fields. Save some space. */
		jQuery('select#chat_row_name_avatar').change(function(){
			var option_selected = jQuery(this).val();

			if (option_selected == "avatar") {
				jQuery('tr#chat_row_name_color_tr').hide();
				jQuery('tr#chat_row_moderator_name_color_tr').hide();
				jQuery('tr#chat_row_avatar_width_tr').show();
				
			} else if (option_selected == "name") {
				jQuery('tr#chat_row_name_color_tr').show();
				jQuery('tr#chat_row_moderator_name_color_tr').show();
				jQuery('tr#chat_row_avatar_width_tr').hide();
				
			} else if (option_selected == "disabled") {
				jQuery('tr#chat_row_name_color_tr').hide();
				jQuery('tr#chat_row_moderator_name_color_tr').hide();
				jQuery('tr#chat_row_avatar_width_tr').hide();
			}
		});
		
		jQuery('select#chat_users_list_show').change(function(){
			var option_selected = jQuery(this).val();

			if (option_selected == "avatar") {
				jQuery('tr#chat_users_list_avatar_width_tr').show();
				jQuery('tr#chat_users_list_name_color_tr').hide();
				jQuery('tr#chat_users_list_font_family_tr').hide();
				jQuery('tr#chat_users_list_font_size_tr').hide();
				
			} else if (option_selected == "name") {
				jQuery('tr#chat_users_list_avatar_width_tr').hide();
				jQuery('tr#chat_users_list_name_color_tr').show();
				jQuery('tr#chat_users_list_font_family_tr').show();
				jQuery('tr#chat_users_list_font_size_tr').show();
			} 
		});

		jQuery('select#chat_load_jscss_all').change(function(){
			var option_selected = jQuery(this).val();

			if (option_selected == "enabled") {
				jQuery('tr#chat_front_urls_actions_tr').show();
				jQuery('tr#chat_front_urls_list_tr').show();
				
			} else if (option_selected == "disabled") {
				jQuery('tr#chat_front_urls_actions_tr').hide();
				jQuery('tr#chat_front_urls_list_tr').hide();
			} 
		});

		if (jQuery('#chat_tab_pane').length) {
			// If the URL has a hash we check of this might be a hash to one of the tabs. If so set the tab index
			var url_hash = window.location.hash;
			if (url_hash != '') {
				url_hash = url_hash.replace('_panel', '_tab');
				var target_tab_li = jQuery('#chat_tab_pane ul li'+url_hash);
				var target_tab_idx = jQuery('#chat_tab_pane ul li').index(target_tab_li);
			} else {
				var target_tab_idx = jQuery.cookie('selected-tab');
			}
			jQuery("#chat_tab_pane").tabs({ 
		    	activate: function (e, ui) { 
		        	jQuery.cookie('selected-tab', ui.newTab.index(), { path: '/' }); 
		    	}, 
		    	active: target_tab_idx             
			});
		}
	});
})(jQuery);
