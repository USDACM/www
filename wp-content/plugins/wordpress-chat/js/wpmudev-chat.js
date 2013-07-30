/**
 * WPMU DEV WordPress Chat plugin javascript
 * 
 * @author	Paul Menard <paul@incsub.com>
 * @since	2.0.0
 */
"use strict";

var wpmudev_chat = jQuery.extend(wpmudev_chat || {}, {
	settings: {},
	pids: {},
	popouts: {},
	bound: false,
	Sounds: [],
	init: function() {
		
		if ((wpmudev_chat_localized['settings']['session_poll_interval_messages'] == undefined) || (wpmudev_chat_localized['settings']['session_poll_interval_messages'] < 1)) {
			wpmudev_chat_localized['settings']['session_poll_interval_messages'] = 1;
		}
		
		try {
			// For ce trying to use the jQuery.cookie function. It is fails it will fall through to the 'catch' section below where the JS 
			// will be loaded then init called again. 
			var _test_cookie = jQuery.cookie('xxx_chat_cookie');
			//console.log('jQuery Cookie already loaded');
			
			wpmudev_chat.settings['sessions'] = {};
			if (wpmudev_chat_localized['sessions'] != undefined) {
				for (var chat_id in wpmudev_chat_localized['sessions']) {
					if (!wpmudev_chat_localized['sessions'].hasOwnProperty(chat_id)) continue;
	
					wpmudev_chat.settings['sessions'][chat_id] = wpmudev_chat_localized['sessions'][chat_id];
		
					// Set this flag on initial page load. Prevents the ping sound.
					wpmudev_chat.settings['sessions'][chat_id]['has_send_message'] = true;
				}
			}

			wpmudev_chat.settings['user'] = {};
			if (wpmudev_chat_localized['user'] != undefined) {
				//wpmudev_chat.settings['user'] = wpmudev_chat_localized['user'];
				for (var chat_id in wpmudev_chat_localized['user']) {
					if (!wpmudev_chat_localized['user'].hasOwnProperty(chat_id)) continue;
	
					wpmudev_chat.settings['user'][chat_id] = wpmudev_chat_localized['user'][chat_id];
				}
			}
			jQuery.cookie('wpmudev-chat-user', JSON.stringify(wpmudev_chat.settings['user']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});

			wpmudev_chat.settings['auth'] = {};
			if ((wpmudev_chat_localized['auth'] != undefined) && (!jQuery.isEmptyObject(wpmudev_chat_localized['auth']))) {
				if (wpmudev_chat_localized['auth']['type'] != 'invalid') {
					wpmudev_chat.settings['auth'] = wpmudev_chat_localized['auth'];
				}
			} else {
				var auth_cookie = jQuery.cookie('wpmudev-chat-auth');
				if ((auth_cookie != undefined) && (!jQuery.isEmptyObject(auth_cookie))) {
					wpmudev_chat.settings['auth'] = JSON.parse(auth_cookie);
				}
			}
			jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});

			//var auth_cookie = jQuery.cookie('wpmudev-chat-auth');
			//var auth_cookie_parsed = JSON.parse(auth_cookie);


			wpmudev_chat.pids['chat_session_message_update'] = '';
			wpmudev_chat.chat_privite_invite_click();

			wpmudev_chat.chat_sessions_init();
			//wpmudev_chat.chat_session_message_update();
		} catch(err) {
			if (wpmudev_chat_localized['settings']['cookie-js'] !== undefined) {
				jQuery.getScript(wpmudev_chat_localized['settings']['cookie-js'], function(data, textStatus, jqxhr) {
					wpmudev_chat.init(); // Go back to this function to recheck function
				});			
			}
		}
	},
	chat_sessions_init: function () {

		var sessions_data = {};

		if ((wpmudev_chat.settings['sessions'] != undefined) && (Object.keys(wpmudev_chat.settings['sessions']).length > 0)) {
			for (var chat_id in wpmudev_chat.settings['sessions']) {
				//sessions_data[chat_id] = wpmudev_chat.settings['sessions'][chat_id];
				var chat_session = wpmudev_chat.settings['sessions'][chat_id];
				sessions_data[chat_id] = {};
				sessions_data[chat_id]['id'] 			= chat_session['id'];
				sessions_data[chat_id]['blog_id'] 		= chat_session['blog_id'];
				sessions_data[chat_id]['session_type'] 	= chat_session['session_type'];
			}			
		}
		if (Object.keys(sessions_data).length > 0) {
			jQuery.ajax({
				type: "POST",
				url: wpmudev_chat_localized['settings']["ajax_url"],
				dataType: "json",
				cache: false,
				data: {
					'function': 'chat_init',
					'action': 'chatProcess',
					'wpmudev-chat-sessions': sessions_data,
					//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
					'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
				},
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('init: error HTTP Status['+jqXHR.status+'] '+errorThrown);
					
					var poll_interval = 1;
					setTimeout(function() {
						//console.log('calling init again');
						wpmudev_chat.chat_sessions_init();
					}, poll_interval*1000);					
				},
				success: function(reply_data) {
					if (reply_data != undefined) {
						for (var chat_id in reply_data) {
							var chat_reply_data = reply_data[chat_id];
							jQuery('div#wpmudev-chat-box-'+chat_id).html(chat_reply_data);
							wpmudev_chat.chat_session_box_actions(chat_id);	
							jQuery('div#wpmudev-chat-box-'+chat_id).show();						
						}
						wpmudev_chat.chat_session_set_auth_view();
						wpmudev_chat.chat_session_sound_setup();

						// If the user auth type is 'wordpress' we don't want to load the third party libs. Save some overhead. 
						if (wpmudev_chat.settings['auth']['type'] != "wordpress") {
							wpmudev_chat.chat_session_facebook_setup();
							wpmudev_chat.chat_session_google_plus_setup();
							wpmudev_chat.chat_session_twitter_setup();
						}
						
						wpmudev_chat.chat_session_size_message_list();
						wpmudev_chat.chat_session_message_update();						
					}
				},
				//complete: function () {
				//	wpmudev_chat.chat_session_message_update();		
				//},
				beforeSend: function() {
				}            
			});
		} else {
			wpmudev_chat.chat_session_message_update();					
		}
	},
	chat_session_message_update: function() {

		
		var sessions_data = {};

		// First loop through each session. Get the last row ID to seed the 'since' variable sent to the server. Controls the last message timestamp.
		if (wpmudev_chat.settings['sessions'] != undefined) {
			for (var chat_id in wpmudev_chat.settings['sessions']) {
				var chat_session = wpmudev_chat.settings['sessions'][chat_id];
				if (jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').length) {
					//sessions_data[chat_id] = wpmudev_chat.settings['sessions'][chat_id];
					sessions_data[chat_id] = {};
					sessions_data[chat_id]['id'] 			= chat_session['id'];
					sessions_data[chat_id]['blog_id'] 		= chat_session['blog_id'];
					sessions_data[chat_id]['session_type'] 	= chat_session['session_type'];

					if (jQuery('body').hasClass('wpmudev-chat-pop-out'))
						sessions_data[chat_id]['template'] 	= "wpmudev-chat-pop-out";

					if (jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').length) {
						if (chat_session['box_input_position'] == "top") {
							var last_row_id = jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').first().attr('id').replace('wpmudev-chat-row-', '');
						} else {
							var last_row_id = jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').last().attr('id').replace('wpmudev-chat-row-', '');
						}
						if (last_row_id != undefined) {
							sessions_data[chat_id]['since'] = last_row_id;
						}
					} else {
						sessions_data[chat_id]['since'] = '0';
					}
				} 
			}			
		}

//		if ((wpmudev_chat.pids['chat_session_message_update'] == '') 
//		 && (Object.keys(sessions_data).length > 0)) {
		if (wpmudev_chat.pids['chat_session_message_update'] == '') {
			
			wpmudev_chat.pids['chat_session_message_update'] = jQuery.ajax({
				type: "POST",
				url: wpmudev_chat_localized['settings']['ajax_url'],
				dataType: "json",
				cache: false,
				data: {  
				    'function': 'chat_messages_update',
				    'action': 'chatProcess',
					'wpmudev-chat-sessions': sessions_data,
					'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
				},
				error: function(jqXHR, textStatus, errorThrown ) {
					//console.log('chat_session_message_update: error HTTP Status['+jqXHR.status+'] '+errorThrown);					
				},
				success: function(reply_data) {
					var play_new_messages_sound = {};
					
					if (reply_data != undefined) {

						if (reply_data['invites'] != undefined) {

							for (var chat_id in reply_data['invites']) {
								if (!reply_data['invites'].hasOwnProperty(chat_id)) continue;

								wpmudev_chat.chat_session_add_item(chat_id, reply_data['invites'][chat_id]);

								// Clue in the box actions for processing. 
								wpmudev_chat.chat_session_box_actions(chat_id);							
							}
						}

						if (reply_data['sessions'] != undefined) {
							for (var chat_id in reply_data['sessions']) {
								var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
								if (chat_session == undefined)
									continue;

								var chat_reply_data = reply_data['sessions'][chat_id];							

								if (chat_reply_data['rows'] != undefined) {
									if (chat_reply_data['rows'] == "__EMPTY__") {
										
										if ((chat_reply_data['meta']['deleted-rows'] != undefined) && (Object.keys(chat_reply_data['meta']['deleted-rows']).length == 0)) {
											// If we get __EMPTY__ we let the user know the moderator has taken action. 
											jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').empty();
											jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-session-generic-message p').html(chat_session['session_cleared_message']);
											jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-session-generic-message').show().delay(5000).fadeOut();
										}
										
									} else if (Object.keys(chat_reply_data['rows']).length) {
								
										//console.log('box_input_position=['+chat_session['box_input_position']+']');
										var has_new_messages = wpmudev_chat.chat_session_process_rows(chat_session, chat_reply_data['rows']);
																		
										if (has_new_messages == true) {
											if ((wpmudev_chat.settings['sessions'][chat_id]['box_sound'] == "enabled") && (wpmudev_chat.settings['user'][chat_id]['sound_on_off'] == "on")) {
												if (!jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').hasClass('wpmudev-chat-box-pop-out')) {
													if (chat_session['has_send_message'] == true) {
														wpmudev_chat.settings['sessions'][chat_id]['has_send_message'] = false;
													} else {
														play_new_messages_sound[chat_id] = true;
													}
												}
											}
											//if ((chat_session['session_type'] == "site") || (chat_session['session_type'] == "private")) {
											//	jQuery('div#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-new-messages').click(function () {
											//		jQuery(this).removeClass('wpmudev-chat-session-new-messages');
											//	});
											//}
										}
									}
									wpmudev_chat.chat_session_click_avatar_row(chat_id);
									wpmudev_chat.chat_session_admin_row_actions(chat_id);
								
									// If not moderator we want to remove the admin UL item within the rows
									//if ((chat_session['moderator'] == "no") || (chat_session['session_type'] == "private")) {
									if (chat_session['moderator'] == "no") {
										jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row ul.wpmudev-chat-row-footer').remove();
							  		}
								}
							
								if ((chat_reply_data['meta'] != undefined) && (Object.keys(chat_reply_data['meta']).length)) {
									// Update our session status
									wpmudev_chat.chat_session_process_status_change(chat_id, chat_reply_data['meta']['session-status']);

									// Update the users list (optional)
									if (chat_reply_data['meta']['users-active'] != undefined) {
										wpmudev_chat.chat_session_process_users_list(chat_id, chat_reply_data['meta']['users-active']);
									}

									// Mark Deleted/Undeleted rows
									if (chat_reply_data['meta']['deleted-rows'] != undefined) {
										wpmudev_chat.chat_session_admin_process_row_delete_actions(chat_id, chat_reply_data['meta']['deleted-rows']);
									}
								}
							
								// Mark the rows with Blocked IP Addresses
								if (chat_reply_data['global']['blocked-ip-addresses'] != undefined) {
									wpmudev_chat.chat_session_admin_process_blocked_ip_addresses(chat_id, chat_reply_data['global']['blocked-ip-addresses']);
								}								

								// Mark the rows with Blocked Users
								if (chat_reply_data['global']['blocked-users'] != undefined) {
									wpmudev_chat.chat_session_admin_process_blocked_users(chat_id, chat_reply_data['global']['blocked-users']);
								}								
							
							}
							if (Object.keys(play_new_messages_sound).length > 0) {
								wpmudev_chat.chat_session_sound_play();	
							}
						}
						
						// We update out wp toolbar on each meta cycle!
						wpmudev_chat.wp_admin_bar_setup();		
						
						wpmudev_chat.chat_session_set_auth_view();

					}
				},
				complete: function(e, xhr, settings) {
					//console.log('status=['+e.status+']');

					wpmudev_chat.pids['chat_session_message_update'] = '';
					if (Object.keys(wpmudev_chat.settings['sessions']).length > 0) {
					
						//if (wpmudev_chat.settings['auth']['type'] != undefined) {
							var poll_interval = wpmudev_chat_localized['settings']['session_poll_interval_messages'];
						//} else {
						//	var poll_interval = wpmudev_chat_localized['settings']['session_poll_interval_messages']*3;
						//}
					} else {
						if (wpmudev_chat_localized['settings']['session_poll_interval_messages'] < 5)
							poll_interval = 5;
						else
							poll_interval = wpmudev_chat_localized['settings']['session_poll_interval_messages'];
					}
					setTimeout(function() {
						wpmudev_chat.chat_session_message_update();
					}, poll_interval*1000);

				},
	    	});
		} 
/*
		else {
			if (wpmudev_chat.settings['auth']['type'] != undefined) {
				var poll_interval = wpmudev_chat_localized['settings']['session_poll_interval_messages'];
			} else {
				var poll_interval = wpmudev_chat_localized['settings']['session_poll_interval_messages']*3;
			}
			setTimeout(function() {
				wpmudev_chat.chat_session_message_update();
			}, poll_interval*1000);
		}
*/
   	},
	// Called to dynamically add private chats to the user's screen
	chat_session_add_item: function (chat_id, chat_item) {

		// Double check we don't already have this session in our array/object
		if (wpmudev_chat.chat_session_get_session_by_id(chat_id) != undefined)
			return;
			
		// Add the new chat session to our sessions list
		wpmudev_chat.settings['sessions'][chat_id] 	= chat_item['session'];

		// Add the new chat session to our users settings list...and update the cookie
		wpmudev_chat.settings['user'][chat_id]		= chat_item['user'];
		jQuery.cookie('wpmudev-chat-user', JSON.stringify(wpmudev_chat.settings['user']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
		
		var chat_session = chat_item['session'];

		// Add the new session to our site container
		var items_cnt = 0;
		var item_offset_h = 0;

		jQuery('.wpmudev-chat-box-site').each(function() {
			items_cnt += 1;
			item_offset_h += jQuery(this).outerWidth(true)
		});

		jQuery("body").append(chat_item['html']);
		
		// We don't position the first element. Because it will be handled by CSS
		if (items_cnt > 0) {
			
			item_offset_h += parseInt(chat_session['box_offset_h']);
		
			if (chat_session['box_position_h'] == "left")
				jQuery('#wpmudev-chat-box-'+chat_session['id']).css('left', item_offset_h+'px');
			else
				jQuery('#wpmudev-chat-box-'+chat_session['id']).css('right', item_offset_h+'px');
		}
		jQuery('#wpmudev-chat-box-'+chat_session['id']).show();
	},
	// Called when the user leaves a private chat via menu option
	chat_session_remove_item: function(chat_id) {
		var chat_box = jQuery('div#wpmudev-chat-box-'+chat_id);

		// Remove the item from the DOM
		jQuery(chat_box).remove();
		
		// Remove the item from our internal lists. 
		var chat_id = jQuery(chat_box).attr('id').replace('wpmudev-chat-box-', '');			
		delete wpmudev_chat.settings['sessions'][chat_id];

		delete wpmudev_chat.settings['user'][chat_id];
		jQuery.cookie('wpmudev-chat-user', JSON.stringify(wpmudev_chat.settings['user']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});

		jQuery.ajax({
			type: "POST",
			url: wpmudev_chat_localized['settings']["ajax_url"],
			dataType: "json",
			cache: false,
			data: {  
		    	'function': 'chat_meta_delete_session',
		    	'action': 'chatProcess',
				'chat-id': chat_id,
				//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
				//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
				'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
			},
			success: function(reply_data) {
				if (reply_data != undefined) {
					if (reply_data['errorStatus'] != undefined) {
						if (reply_data['errorStatus'] == true) {
							if (reply_data['errorText'] != undefined) {
								console.log("Chat: chat_meta_delete_session: reply [%s]", reply_data['errorText']);
							}
						}
					}
				}
			}			
		});
	},
	chat_session_update_user_invite_status: function(chat_id, invite_status) {
		jQuery.ajax({
			type: "POST",
			url: wpmudev_chat_localized['settings']["ajax_url"],
			dataType: "json",
			cache: false,
			data: {  
		    	'function': 'chat_invite_update_user_status',
		    	'action': 'chatProcess',
				'chat-id': chat_id,
				'invite-status': invite_status,
				//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
				'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
			},
			success: function(reply_data) {
				if (reply_data != undefined) {
					if (reply_data['errorStatus'] != undefined) {
						if (reply_data['errorStatus'] == true) {
							if (reply_data['errorText'] != undefined) {
								console.log("Chat: chat_invite_update_user_status: reply [%s]", reply_data['errorText']);
							}
						}
					}
				}
			}			
		});
		
	},
	chat_session_size_message_list: function() {
		
		for (var chat_id in wpmudev_chat.settings['sessions']) {
			var chat_session = wpmudev_chat.settings['sessions'][chat_id];
		
			if (!jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').is(":visible"))
				continue;
				
			var chat_session_outer_height = jQuery('#wpmudev-chat-box-'+chat_id).height();
			var chat_session_wrap_height = 0;
			jQuery('#wpmudev-chat-box-'+chat_id+' .wpmudev-chat-module').each(function() {
				if ( (!jQuery(this).hasClass('wpmudev-chat-module-messages-list')) && (!jQuery(this).hasClass('wpmudev-chat-module-users-list')) && (jQuery(this).is(":visible")) ) {
					chat_session_wrap_height += jQuery(this).outerHeight(true);
				}
			});			

			if (chat_session['users_list_position'] == "none") {
//				var message_list_height = jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').outerHeight(true);
//				chat_session_wrap_height += messages_list_height;
				if (chat_session_wrap_height < chat_session_outer_height) {
					var height_diff = chat_session_outer_height - chat_session_wrap_height;
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(height_diff+1);
				} else if (chat_session_wrap_height > chat_session_outer_height) {
					var height_diff = chat_session_outer_height - chat_session_wrap_height;
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(height_diff+1);
				}

			} else if ((chat_session['users_list_position'] == "left") || (chat_session['users_list_position'] == "right")) {

				if ((jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list')) && (jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').is(":visible")) ) {
					var messages_list_height = jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').outerHeight(true);
				} else {
					var messages_list_height = 0;
				}

				if ((jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list')) && (jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').is(":visible"))) {
					var users_list_height = jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').outerHeight(true);				
				} else {
					var users_list_height = 0;
				}
				if (messages_list_height == users_list_height) {
					chat_session_wrap_height += messages_list_height;
				} else if (messages_list_height > users_list_height) {
					chat_session_wrap_height += messages_list_height;
					jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').height(messages_list_height);
				} else if (messages_list_height < users_list_height) {
					chat_session_wrap_height += users_list_height;
					jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(users_list_height);
				}

				var message_list_height = jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').outerHeight(true);
				if (chat_session_wrap_height < chat_session_outer_height) {
					var height_diff = chat_session_outer_height - chat_session_wrap_height;
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(message_list_height+height_diff);
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').height(message_list_height+height_diff);
				} else if (chat_session_wrap_height > chat_session_outer_height) {
					var height_diff = chat_session_outer_height - chat_session_wrap_height;
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(message_list_height+height_diff);
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').height(message_list_height+height_diff);
				}

			} else 	if ((chat_session['users_list_position'] == "above") || (chat_session['users_list_position'] == "below")) {

				if ((jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list')) && (jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').is(":visible"))) {
					chat_session_wrap_height += jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').outerHeight(true);				
				}
				if ((jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list')) && (jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').is(":visible")) ) {
					var messages_list_height = jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').outerHeight(true);
				} else  {
					var messages_list_height = 0;
				}
				chat_session_wrap_height += messages_list_height;
					
				//var message_list_height = jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').outerHeight(true);
				if (chat_session_wrap_height < chat_session_outer_height) {
					var height_diff = chat_session_outer_height - chat_session_wrap_height;
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(messages_list_height+height_diff+1);
				} else if (chat_session_wrap_height > chat_session_outer_height) {
					var height_diff = chat_session_outer_height - chat_session_wrap_height;
					jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').height(messages_list_height+height_diff+1);
				}
			}
		}		
	},
	// This function contains somewhat funky logic. Its role is to keep certain module visible based on the users auth ability. 
	// This function also considers the site type sessions which might be minimized. 
	chat_session_set_auth_view: function() {
		
		// General logic we can control via CSS rules. 
		//jQuery('div.wpmudev-chat-box-min div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').hide();
		//jQuery('div.wpmudev-chat-box-max div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').show();

//		jQuery('div.wpmudev-chat-box-site.wpmudev-chat-box-max div.wpmudev-chat-module-messages-list').show();
//		jQuery('div.wpmudev-chat-box-site.wpmudev-chat-box-max div.wpmudev-chat-module-users-list').show();
		
		jQuery('div.wpmudev-chat-box-site.wpmudev-chat-box-min div.wpmudev-chat-module-login').hide();		
		
		jQuery('div.wpmudev-chat-box.wpmudev-chat-box-max.wpmudev-chat-session-closed div.wpmudev-chat-module-session-status').show();		
		jQuery('div.wpmudev-chat-box.wpmudev-chat-box-max.wpmudev-chat-session-open div.wpmudev-chat-module-session-status').hide();
		jQuery('div.wpmudev-chat-box.wpmudev-chat-box-min.wpmudev-chat-session-closed div.wpmudev-chat-module-session-status').hide();		
						
		if (wpmudev_chat.settings['auth']['type'] != undefined) {

			// Hide the chat module for login because the user is already there. 
			jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-login').hide();
			jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-login-prompt').hide();

			//jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').show();
			if (jQuery('body').hasClass('wpmudev-chat-pop-out'))
				jQuery('body.wpmudev-chat-pop-out div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').hide();
			else
				jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').show();
			
			// For WordPress type users we don't provide a login/logout functionality. 
			if (wpmudev_chat.settings['auth']['type'] == "wordpress") {
				jQuery('div.wpmudev-chat-box ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-login').hide();
				jQuery('div.wpmudev-chat-box ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-logout').hide();
			} else {
				jQuery('div.wpmudev-chat-box ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-login').hide();
				
				jQuery('div.wpmudev-chat-box ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-logout').show();				
				jQuery('div.wpmudev-chat-box-private ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-logout').hide();					
			}
			
			for (var chat_id in wpmudev_chat.settings['sessions']) {
				if (!jQuery('div#wpmudev-chat-box-'+chat_id).length)
					continue;

				var chat_session = wpmudev_chat.settings['sessions'][chat_id];
				if (chat_session == undefined) continue;
				
				if (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-session-ip-blocked')) {
				  	if (chat_session['moderator'] == "no") {
				
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-banned-status').show();		
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-messages-list').hide();
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-users-list').hide();
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-message-area').hide();
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-login').hide();
					}
				} else if (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-session-user-blocked')) {
				  	if (chat_session['moderator'] == "no") {

						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-user-blocked div.wpmudev-chat-module-banned-status').show();		
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-user-blocked div.wpmudev-chat-module-messages-list').hide();
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-user-blocked div.wpmudev-chat-module-users-list').hide();
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-user-blocked div.wpmudev-chat-module-message-area').hide();
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-user-blocked div.wpmudev-chat-module-login').hide();
					}
				} else {
				
					//jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').show();
					
					if (chat_session['moderator'] == "no") {
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-banned-status').hide();
						
						//if (chat_session['session_status'] == 'closed') {
						if (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-session-closed')) {
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').hide();
						} else {
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').show();
						}
					}
				
					//if ((chat_session['session_type'] == "site") || (chat_session['session_type'] == "private")) {
					if ((jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-box-site')) || (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-box-private'))) {
						// If the site chat box is minimized then we don't show the modules
						if (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-box-min')) {
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-messages-list').hide();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-users-list').hide();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').hide();
							
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').hide();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').hide();
						} else {
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-users-list').show();			
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-messages-list').show();			
							//jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').show();

							if (chat_session['moderator'] == "no") {							
								if (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-session-closed')) {
									jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').hide();
								} else {
									jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').show();
								}
							}
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').show();
							
							if (jQuery('body').hasClass('wpmudev-chat-pop-out'))
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').hide();
							else
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').show();
						}
					} else {
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-users-list').show();			
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-messages-list').show();			
					}
				}
			}
			
		} else {			
			// If the user id not authenticated we hide the status about the session being closed. Too many prompts
			jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-session-status').hide();
			
			// Show the login menu. Hide the logout 
			jQuery('div.wpmudev-chat-box ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-login').show();
			jQuery('div.wpmudev-chat-box ul.wpmudev-chat-actions-menu li.wpmudev-chat-action-menu-item-logout').hide();

			jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out').hide();

			for (var chat_id in wpmudev_chat.settings['sessions']) {
				if (!jQuery('div#wpmudev-chat-box-'+chat_id).length)
					continue;

				var chat_session = wpmudev_chat.settings['sessions'][chat_id];
				if (chat_session == undefined) continue;
				
				if ( (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-session-ip-blocked')) 
				  && (chat_session['moderator'] == "no") ) {
				
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-banned-status').show();		
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-login-prompt').hide();
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-messages-list').hide();
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-users-list').hide();
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-message-area').hide();
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked div.wpmudev-chat-module-login').hide();
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+'.wpmudev-chat-session-ip-blocked ul.wpmudev-chat-actions-menu').hide();

				} else {
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu').show();
				
					jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-area').hide();
				
					if ( ((chat_session['session_type'] == "site") || (chat_session['session_type'] == "private")) && (jQuery('div#wpmudev-chat-box-'+chat_session['id']).hasClass('wpmudev-chat-box-min')) ) {
						jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-login-prompt').hide();
					} else {
						if (chat_session['noauth_view'] == "default") {
							if (jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-login').is(":visible")) {
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-login-prompt').hide();
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-message-list').hide();				
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-users-list').hide();				
							} else {
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-messages-list').show();
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-users-list').show();
								jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-login-prompt').show();
							}					
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').show();
						} else if (chat_session['noauth_view'] == "login-only") {
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-login').show();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-login button.wpmudev-chat-login-cancel').hide();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-messages-list').hide();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' div.wpmudev-chat-module-users-list').hide();
							jQuery('div#wpmudev-chat-box-'+chat_session['id']+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').hide();
						} 
					}
				}
			}
		}

		wpmudev_chat.chat_session_size_message_list();				
	},

	chat_session_handle_keyup: function(event) {

		var chat_box_id 	= jQuery(this).parents('div.wpmudev-chat-box').attr('id');
		var chat_id 		= chat_box_id.replace('wpmudev-chat-box-', '');;
		var chat_session 	= wpmudev_chat.chat_session_get_session_by_id(chat_id);
		if (chat_session != undefined) {

	    	var code = event.keyCode ? event.keyCode : event.which;
	    	if(code.toString() != 13) {
				var message_text = jQuery(this).val();
				if (chat_session['row_message_input_length'] > 0) {
					if (message_text.length < chat_session['row_message_input_length']) {
						var message_text_new = message_text.substr(0, chat_session['row_message_input_length']);
						jQuery(this).val(message_text_new);
						jQuery('#'+chat_box_id+' div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-length span.wpmudev-chat-character-count').html(message_text_new.length);
						event.preventDefault();						
					} else {
						jQuery('#'+chat_box_id+' div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-length span.wpmudev-chat-character-count').html(message_text.length);
					}
				} else {
					jQuery('#'+chat_box_id+' div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-length span.wpmudev-chat-character-count').html(message_text.length);
				}

			} else {
				event.preventDefault();			
			
				var message_text = jQuery.trim(jQuery(this).val());
	        	if(message_text != '') {
					wpmudev_chat.chat_session_message_send(message_text, chat_session);
					jQuery(this).val('');
					jQuery('#'+chat_box_id+' div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-length span.wpmudev-chat-character-count').html('0');
				}
	        }	        
		}
	},
	chat_session_get_session_by_id: function (chat_id) {
		if (wpmudev_chat.settings['sessions'][chat_id] != undefined)
			return wpmudev_chat.settings['sessions'][chat_id];
	},
	chat_session_get_auth_type: function() {
		if (wpmudev_chat.settings['auth']['type'] == undefined)
			return '';
		else
			return wpmudev_chat.settings['auth']['type'];
	},
	chat_session_message_send: function(message, chat_session) {
		var sessions_data = {};
		var chat_id = chat_session['id'];
		//sessions_data[chat_id] = chat_session;
		sessions_data[chat_id] = {};
		sessions_data[chat_id]['id'] 			= chat_session['id'];
		sessions_data[chat_id]['blog_id'] 		= chat_session['blog_id'];
		sessions_data[chat_id]['session_type'] 	= chat_session['session_type'];
		
		// Set a flag for this session so we don't make a sound when we update the message rows. 
		wpmudev_chat.settings['sessions'][chat_id]['has_send_message'] = true;
		
		message = jQuery.trim(message);
		
		jQuery.ajax({
			type: "POST",
			url: wpmudev_chat_localized['settings']["ajax_url"],
			dataType: "json",
			cache: false,
			data: {  
				'function': 'chat_message_send',
				'action': 'chatProcess',
				'chat_id': chat_session['id'],
				'wpmudev-chat-sessions': sessions_data,
				//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],				
				'chat_message': message,
				//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
				'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
			},
			success: function(reply_data){
				if (reply_data != undefined) {
					if (reply_data['errorStatus'] != undefined) {
						if (reply_data['errorStatus'] == true) {
							if (reply_data['errorText'] != undefined) {
								console.log("Chat: chat_message_send: reply [%s]", reply_data['errorText']);
							}
						}
					}
				}
			}
		});
	},
	
	/* Appends rows from AJAX reply to chat -box */
	chat_session_process_rows: function(chat_session, rows) {
	    var updateContent = '';
		var chat_id = chat_session['id'];
		
	    for (var i in rows) {
			if (rows.hasOwnProperty(i)) {
				if (!jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div#wpmudev-chat-row-'+i).length) {
					if (chat_session['box_input_position'] == "top") {
						updateContent = rows[i]+updateContent;
					} else {
						updateContent = updateContent+rows[i];
					}
				}
			}
	    }

		if ( updateContent !== '' ) {
			var force_scroll_bottom 	= true;
			
			var container 				= jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list');
		    var c_height 				= container.height();
			var row						= jQuery('div.wpmudev-chat-row', container).last();
			
			if (jQuery(row).length) {
				var r_height			= jQuery(row).height();
				var r_offset 			= row.offset();

				var c_offset 			= container.offset();
				
				var diff_offset = r_offset.top-c_offset.top; 
				if (diff_offset < c_height)
					force_scroll_bottom = true;
				else
					force_scroll_bottom = false
			}
			if (chat_session['box_input_position'] == "top") {
		    	jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').prepend(updateContent);
				
			} else if (chat_session['box_input_position'] == "bottom") {
		    	jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').append(updateContent);

				if (force_scroll_bottom == true) {
					var row					= jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').last();
					if (row.length) {
						var r_position			= row.position();
						var c_scrollTop 		= container.scrollTop()+r_position.top;

						jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').animate({ scrollTop: c_scrollTop }, 1000);
					}
				}
			}

			
			// This will limit the number of message show to the user on entry and page reload. Default 100 per settings. 
			if (chat_session['log_limit'] != undefined) {
				while (jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').length > chat_session['log_limit']) {
					if (chat_session['box_input_position'] == "bottom") {
						jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').eq(0).remove();
					} else if (chat_session['box_input_position'] == "top") {
						jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').last().remove();
					}
				}
			}
			return true;
		}
	},
	chat_session_admin_row_actions: function (chat_id) {
		
		if (wpmudev_chat.settings['auth']['auth_hash'] != undefined) {
			jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list div.wpmudev-chat-row ul.wpmudev-chat-row-footer li.wpmudev-chat-admin-actions-item-invite a[rel="'+wpmudev_chat.settings['auth']['auth_hash']+'"]').each(function() {
				jQuery(this).parents('li.wpmudev-chat-admin-actions-item-invite').hide();
			});			
		}
		
		
		var selector = 'div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list div.wpmudev-chat-row ul.wpmudev-chat-row-footer li.wpmudev-chat-admin-actions-item a';
		jQuery(selector).unbind('click');
		jQuery(selector).click(function(event) {
			event.preventDefault();
			
			var row_id = jQuery(this).parents('.wpmudev-chat-row').attr('id').replace('wpmudev-chat-row-', '');
			var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
			if (chat_session == undefined) return false;

			if (jQuery(this).hasClass('wpmudev-chat-admin-actions-item-delete')) {
				if (jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list #wpmudev-chat-row-'+row_id).hasClass('wpmudev-chat-row-deleted'))
					var admin_action = "undelete";
				else
					var admin_action = "delete";
					
				jQuery.ajax({
					type: "POST",
					url: wpmudev_chat_localized['settings']["ajax_url"],
					cache: false,
					dataType: "json",
					data: {  
						'action': 'chatProcess',
						'function': 'chat_session_moderate_message',
						'chat_id': chat_id,
						'chat_session': chat_session,
						'row_id': row_id,
						'moderate_action': admin_action,
						//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
						//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
						'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
					},
					success: function(reply_data) {
						if (reply_data != undefined) {
							if (reply_data['errorStatus'] != undefined) {
								if (reply_data['errorStatus'] == true) {
									if (reply_data['errorText'] != undefined) {
										console.log("Chat: chat_session_moderate_message: reply [%s]", reply_data['errorText']);
									}
								}
							}
						}
					}			
				});
				
			} else if (jQuery(this).hasClass('wpmudev-chat-admin-actions-item-block-ip')) {
				var row_ip_address = jQuery(this).attr('rel');
				
				if (jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list #wpmudev-chat-row-'+row_id).hasClass('wpmudev-chat-row-ip-blocked'))
					var admin_action = "unblock-ip";
				else
					var admin_action = "block-ip";
					
				jQuery.ajax({
					type: "POST",
					url: wpmudev_chat_localized['settings']["ajax_url"],
					cache: false,
					dataType: "json",
					data: {  
						'action': 'chatProcess',
						'function': 'chat_session_moderate_ipaddress',
						'chat_id': chat_id,
						'chat_session': chat_session,
						'ip_address': row_ip_address,
						'moderate_action': admin_action,
						//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
						//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
						'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
					},
					success: function(reply_data) {
						if (reply_data != undefined) {
							if (reply_data['errorStatus'] != undefined) {
								if (reply_data['errorStatus'] == true) {
									if (reply_data['errorText'] != undefined) {
										console.log("Chat: chat_session_moderate_ipaddress: reply [%s]", reply_data['errorText']);
									}
								}
							}
						}
					}			
				});
			} else if (jQuery(this).hasClass('wpmudev-chat-admin-actions-item-block-user')) {
					var row_user = jQuery(this).attr('rel');

					if (jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-moderator div.wpmudev-chat-module-messages-list #wpmudev-chat-row-'+row_id).hasClass('wpmudev-chat-row-user-blocked'))
						var moderate_action = "unblock-user";
					else
						var moderate_action = "block-user";

					jQuery.ajax({
						type: "POST",
						url: wpmudev_chat_localized['settings']["ajax_url"],
						cache: false,
						dataType: "json",
						data: {  
							'action': 'chatProcess',
							'function': 'chat_session_moderate_user',
							'chat_id': chat_id,
							'chat_session': chat_session,
							'moderate_item': row_user,
							'moderate_action': moderate_action,
							//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
							//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
							'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
						},
						success: function(reply_data) {
							if (reply_data != undefined) {
								if (reply_data['errorStatus'] != undefined) {
									if (reply_data['errorStatus'] == true) {
										if (reply_data['errorText'] != undefined) {
											console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
										}
									}
								}
							}
						}			
					});
			} 
			else if (jQuery(this).hasClass('wpmudev-chat-user-invite')) {
				
				var user_hash = jQuery(this).attr('rel');
				if (user_hash != '') {
					//console.log('chat_session_admin_row_actions user_hash=['+user_hash+']');
					wpmudev_chat.chat_process_private_invite(user_hash);
				}
			}
			return false;
		});
	},
	// Process Deleted Rows from AJAX meta information	
	chat_session_admin_process_row_delete_actions: function (chat_id, deleted_rows) {
		var delete_row_class = 'wpmudev-chat-row-deleted';
		
		jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row').each(function() {
			var row_id_full = jQuery(this).attr('id');
			var row_id = row_id_full.replace('wpmudev-chat-row-', '');
			var item_found = jQuery.inArray(parseInt(row_id), deleted_rows);
			if (item_found == -1) {
				jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).removeClass(delete_row_class);
				jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full+" ul.wpmudev-chat-row-footer li.wpmudev-chat-admin-actions-item a.wpmudev-chat-admin-actions-item-delete span").text(wpmudev_chat_localized['settings']["row_delete_text"]);
			} else {
				jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).addClass(delete_row_class);
				jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full+" ul.wpmudev-chat-row-footer li.wpmudev-chat-admin-actions-item a.wpmudev-chat-admin-actions-item-delete span").text(wpmudev_chat_localized['settings']["row_undelete_text"]);
			}
		});	
	},
	// Process Blocked IP Addresses from AJAX meta information
	chat_session_admin_process_blocked_ip_addresses: function (chat_id, blocked_ip_addresses) {
		var delete_row_class = 'wpmudev-chat-row-ip-blocked';
		
		// First we undelete all rows not in the ip_addresses listing...
		jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row.'+delete_row_class).each(function() {
			var row_id_full = jQuery(this).attr('id');
			//var row_id = row_id_full.replace('wpmudev-chat-row-', '');
			
			if (!jQuery('#wpmudev-chat-box-'+chat_id).hasClass('wpmudev-chat-box-private')) {
				var row_ip_address = jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full+' ul.wpmudev-chat-row-footer li.wpmudev-chat-admin-actions-item a.wpmudev-chat-admin-actions-item-block-ip').attr('rel');
				if (row_ip_address != undefined) {
					var item_found = jQuery.inArray(row_ip_address, blocked_ip_addresses);
					if (item_found == -1) {
						jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).removeClass(delete_row_class);
					} else {
						jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).addClass(delete_row_class);					
					}
				}
			}
		});	
		
		//...then we hide all rows per the ip_addresses listing
		for (var ip_idx in blocked_ip_addresses) {
			if (blocked_ip_addresses.hasOwnProperty(ip_idx)) {
				var ip_address = blocked_ip_addresses[ip_idx];
				ip_address = ip_address.replace('.', '-');
				ip_address = ip_address.replace('.', '-');
				ip_address = ip_address.replace('.', '-');
			
				if (!jQuery('#wpmudev-chat-box-'+chat_id).hasClass('wpmudev-chat-box-private')) {
					jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row-ip-'+ip_address).each(function() {
						var row_id_full = jQuery(this).attr('id');
						jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).addClass(delete_row_class);
					});	
				}
			}
		}
		
		// Last we check the current user's IP address against the ip_addresses listing. But also check if we are moderator
		if (!jQuery('#wpmudev-chat-box-'+chat_id).hasClass('wpmudev-chat-box-private')) {
			if ((wpmudev_chat.settings['auth']['ip_address'] != undefined) && (wpmudev_chat.settings['sessions'][chat_id]['moderator'] != 'yes')) {
				var session_ip_address = wpmudev_chat.settings['auth']['ip_address'];
				var item_found = jQuery.inArray(session_ip_address, blocked_ip_addresses);
				if (item_found == -1) {				
					jQuery('#wpmudev-chat-box-'+chat_id).removeClass('wpmudev-chat-session-ip-blocked');
				} else {
					jQuery('#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-ip-blocked');
				}
			}
		}
		
		// now double check the sessions on this page. Loop then and check the 'ip_address' against the blocked ip_addresses
		for (var chat_id in wpmudev_chat.settings['sessions']) {
			if (jQuery('div#wpmudev-chat-box-'+chat_id).length) {
				
				if (!jQuery('#wpmudev-chat-box-'+chat_id).hasClass('wpmudev-chat-box-private')) {
					var item_found = jQuery.inArray(wpmudev_chat.settings['sessions'][chat_id]['ip_address'], blocked_ip_addresses);
					if (item_found == -1) {				
						jQuery('#wpmudev-chat-box-'+chat_id).removeClass('wpmudev-chat-session-ip-blocked');
					} else {
						jQuery('#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-ip-blocked');
					}
				}
			} 
		}			
		
	},
	// Process Blocked IP Addresses from AJAX meta information
	chat_session_admin_process_blocked_users: function (chat_id, blocked_users) {
		var delete_row_class = 'wpmudev-chat-row-user-blocked';
		
		// First we undelete all rows not in the blocked_users listing...
		jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row.'+delete_row_class).each(function() {
			var row_id_full = jQuery(this).attr('id');
			
			var row_user = jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full+' ul.wpmudev-chat-row-footer li.wpmudev-chat-admin-actions-item a.wpmudev-chat-admin-actions-item-block-user').attr('rel');
			if (row_user != undefined) {
				var item_found = jQuery.inArray(row_user, blocked_users);
				if (item_found == -1) {
					jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).removeClass(delete_row_class);
				} else {
					jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).addClass(delete_row_class);					
				}
			}
		});	
		
		//...then we hide all rows per the blocked_users listing
		for (var user_idx in blocked_users) {
			if (blocked_users.hasOwnProperty(user_idx)) {
				var blocked_user = blocked_users[user_idx];
				blocked_user = blocked_user.replace('@', '-');
				blocked_user = blocked_user.replace('.', '-');
			
				jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row-user-'+blocked_user).each(function() {
					var row_id_full = jQuery(this).attr('id');
					var something_else;
					jQuery('#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list #'+row_id_full).addClass(delete_row_class);
				});	
			}
		}
		
		// Last we check the current user's avatar against the ip_addresses listing. But also check if we are moderator
		if ((wpmudev_chat.settings['auth']['email'] != undefined) && (wpmudev_chat.settings['sessions'][chat_id]['moderator'] != 'yes')) {
			var session_email = wpmudev_chat.settings['auth']['email'];
			var item_found = jQuery.inArray(session_email, blocked_users);
			if (item_found == -1) {				
				jQuery('#wpmudev-chat-box-'+chat_id).removeClass('wpmudev-chat-session-user-blocked');
			} else {
				jQuery('#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-user-blocked');
			}
		}
		
		// now double check the sessions on this page. Loop then and check the 'ip_address' against the blocked ip_addresses
//		for (var chat_id in wpmudev_chat.settings['sessions']) {
//			if (jQuery('div#wpmudev-chat-box-'+chat_id).length) {
//				var item_found = jQuery.inArray(wpmudev_chat.settings['sessions'][chat_id]['ip_address'], ip_addresses);
//				if (item_found == -1) {				
//					jQuery('#wpmudev-chat-box-'+chat_id).removeClass('wpmudev-chat-session-ip-blocked');
//				} else {
//					jQuery('#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-ip-blocked');
//				}
//			} 
//		}			
		
	},
	chat_session_process_status_change: function(chat_id, chat_session_status) {

		wpmudev_chat.settings['sessions'][chat_id]['session_status'] = chat_session_status;
		if (chat_session_status == "open") {
			jQuery('div#wpmudev-chat-box-'+chat_id).removeClass('wpmudev-chat-session-closed');
			jQuery('div#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-open');
		} else {
			jQuery('div#wpmudev-chat-box-'+chat_id).removeClass('wpmudev-chat-session-open');
			jQuery('div#wpmudev-chat-box-'+chat_id).addClass('wpmudev-chat-session-closed');			
		}
	},
	chat_session_status_update: function(chat_id, chat_session_status) {

		// We are closing the chat session
		jQuery.ajax({
			type: "POST",
			url: wpmudev_chat_localized['settings']["ajax_url"],
			cache: false,
			dataType: "json",
			data: {  
				'action': 'chatProcess',
				'function': 'chat_session_moderate_status',
				'chat_session': wpmudev_chat.chat_session_get_session_by_id(chat_id),
				'chat_session_status': chat_session_status,
				//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
				//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
				'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
			},
			success: function(reply_data) {
				if (reply_data != undefined) {
					if (reply_data['errorStatus'] != undefined) {
						if (reply_data['errorStatus'] == true) {
							if (reply_data['errorText'] != undefined) {
								console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
							}
						}
					}
				}
			}			
		});		
	},
	chat_session_click_avatar_row: function(chat_id) {
		// We unbind the click first to prevent previous events bindings. 
		//jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list .wpmudev-chat-row-avatar a.wpmudev-chat-user-avatar').unbind('click'); // Works for jQuery 1.4.2

		// Then setup a new click binding. 
		jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row p.wpmudev-chat-message a.wpmudev-chat-user').unbind('click');
		jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list div.wpmudev-chat-row p.wpmudev-chat-message a.wpmudev-chat-user').click(function(event) {
			event.preventDefault();

			var name_title = jQuery(this).attr('title');
			if (name_title != '') {
				var existing_text = jQuery('#wpmudev-chat-box-'+chat_id+' textarea.wpmudev-chat-send').val();
				if (existing_text != '') existing_text = existing_text+' ';
				jQuery('#wpmudev-chat-box-'+chat_id+' textarea.wpmudev-chat-send').val(existing_text+jQuery(this).attr('title')).focus();
			}
			event.preventDefault();
			return false;
		});
	},
    chat_session_process_users_list: function(chat_id, active_users) {
		var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
		if (chat_session == undefined) return;

		// Update the private chat itle to show the users active in chat
		if (chat_session['session_type'] == "private") {
			
			var user_text = '';
			
			for (var user_type in active_users) {
				if (!active_users.hasOwnProperty(user_type)) continue;

				var active_users_list = active_users[user_type];
				for (var user_id in active_users_list) {
					if (!active_users_list.hasOwnProperty(user_id)) continue;

					var user = active_users_list[user_id];
					if (user['auth_hash'] != wpmudev_chat.settings['auth']['auth_hash']) {
						if (user_text != '') user_text = user_text+',';
						user_text = user_text+user['name'];
					}
				}
			}
			if (user_text != '')			
				jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-header span.wpmudev-chat-private-attendees').html(' - '+user_text);
			else
				jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-header span.wpmudev-chat-private-attendees').html(' - 0');
			
			var chat_box_width 		= jQuery('div#wpmudev-chat-box-'+chat_id).width();
			var chat_box_menu_width = jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu').width();
			jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-header span.wpmudev-chat-title-text').width(chat_box_width-chat_box_menu_width);	
		}

		if ((chat_session['users_list_position'] == "none") || (!jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').length)) return;			

		// 1. First we get all the current user list items
		for (var user_type in active_users) {
			if (!active_users.hasOwnProperty(user_type)) continue;

			if (!jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list ul.wpmudev-chat-'+user_type).length) {
				jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').append('<ul class="wpmudev-chat-'+user_type+'"></ul>');
			} else {
				jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list ul.wpmudev-chat-'+user_type+' li.wpmudev-chat-user').each(function() {
					var user_id_full = jQuery(this).attr('id');
					var user_id = user_id_full.replace('wpmudev-chat-user-', '');

					if (active_users[user_type][user_id] == undefined) {
						jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list ul.wpmudev-chat-'+user_type+' li#'+user_id_full).remove();
					}			
				});
			}
		}
		
		// Second add the new entries into the list. Append to the bottom
		for (var user_type in active_users) {
			if (!active_users.hasOwnProperty(user_type)) continue;
			
			var active_users_list = active_users[user_type];
			for (var user_id in active_users_list) {
				if (!active_users_list.hasOwnProperty(user_id)) continue;
				
				var user = active_users_list[user_id];
				
				if (!jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list ul.wpmudev-chat-'+user_type+' li#wpmudev-chat-user-'+user_id).length) {
					var user_html;
					if ((chat_session['users_list_show'] == "avatar") && (user['avatar'] != undefined)) {
						user_html = '<li id="wpmudev-chat-user-'+user_id+'" class="wpmudev-chat-user"><a title="@'+user['name']+'" href="#">'+user['avatar']+'</a></li>';					
					} else {
						user_html = '<li id="wpmudev-chat-user-'+user_id+'" class="wpmudev-chat-user"><a title="@'+user['name']+'" href="#">'+user['name']+'</a></li>';					
					}
					if (user_html != '') {
						jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list ul.wpmudev-chat-'+user_type).append(user_html);

						// Need to setup the click action...
						jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list ul.wpmudev-chat-'+user_type+' li#wpmudev-chat-user-'+user_id+' a').click(function(event) {
							event.preventDefault();
							var name_title = jQuery(this).attr('title');
							if (name_title != '') {
								var existing_text = jQuery('#wpmudev-chat-box-'+chat_id+' textarea.wpmudev-chat-send').val();
								if (existing_text != '') existing_text = existing_text+' ';
								jQuery('#wpmudev-chat-box-'+chat_id+' textarea.wpmudev-chat-send').val(existing_text+jQuery(this).attr('title')).focus();
							}
						});
					}
				}
			}
		}
	},
	chat_process_private_invite: function(user_hash) {
	
		if (user_hash != '') {
			//console.log('user_hash=['+user_hash+']');				

			jQuery.ajax({
				type: "POST",
				url: wpmudev_chat_localized['settings']["ajax_url"],
				cache: false,
				dataType: "json",
				data: {  
					'action': 'chatProcess',
					'function': 'chat_session_invite_private',
					'wpmudev-chat-to-user': user_hash,
					//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
					'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
				},
				success: function(reply_data) {
					if (reply_data != undefined) {
						if (reply_data['errorStatus'] != undefined) {
							if (reply_data['errorStatus'] == true) {
								if (reply_data['errorText'] != undefined) {
									console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
								}
							}
						}
					}
				}			
			});
		}
	},
	
	chat_process_user_status_change: function(user_new_status) {
		if (user_new_status != '') {

			jQuery.ajax({
				type: "POST",
				url: wpmudev_chat_localized['settings']["ajax_url"],
				cache: false,
				dataType: "json",
				data: {  
					'action': 'chatProcess',
					'function': 'chat_update_user_status',
					'wpmudev-chat-user-status': user_new_status,
					//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
					'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
				},
				success: function(reply_data) {

					jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container div.ab-item span.wpmudev-chat-user-status-current span.wpmudev-chat-ab-icon').removeClass('wpmudev-chat-ab-icon-'+wpmudev_chat.settings['auth']['chat_status']);
					jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container div.ab-item span.wpmudev-chat-user-status-current span.wpmudev-chat-ab-icon').addClass('wpmudev-chat-ab-icon-'+user_new_status);
					
					jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container div.ab-sub-wrapper li#wp-admin-bar-wpmudev-chat-user-statuses div.ab-sub-wrapper li#wp-admin-bar-wpmudev-chat-user-status-change-'+wpmudev_chat.settings['auth']['chat_status']).removeClass('wpmudev-chat-user-status-current');
					jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container div.ab-sub-wrapper li#wp-admin-bar-wpmudev-chat-user-statuses div.ab-sub-wrapper li#wp-admin-bar-wpmudev-chat-user-status-change-'+user_new_status).addClass('wpmudev-chat-user-status-current');
					
					var current_label = jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container div.ab-sub-wrapper li#wp-admin-bar-wpmudev-chat-user-statuses div.ab-sub-wrapper li.wpmudev-chat-user-status-current span.wpmudev-chat-ab-label').html();
					if (current_label != '') {
						//console.log('current_label=['+current_label+']');
						jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container div.ab-sub-wrapper li#wp-admin-bar-wpmudev-chat-user-statuses div.ab-item span.wpmudev-chat-current-stauts-label').html(current_label);
					}
					
					// Update our internal settings...and update the cookie
					wpmudev_chat.settings['auth']['chat_status'] = user_new_status;
					jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
					
				}
			});
		}
		
	},
	/* 
	Play a sound when new messages are received. Note we don't care about which session has sound since that was determined in 'chat_session_setup_sound' function.
	The fact that pingSound object is not false tells os we have one session with sound enabled.  
	*/
	chat_session_sound_play: function() {
		
		if (wpmudev_chat.Sounds['ping'] ) {
			wpmudev_chat.Sounds['ping'].play();
		}
		//if (wpmudev_chat.Sounds['chime'] ) {
		//	wpmudev_chat.Sounds['chime'].play();
		//}
	},
	
	/* We loop through the chat sessions. If we find just one that has sound enabled we setup the sound engine */
	chat_session_sound_setup: function() {
		
		try {
			wpmudev_chat.Sounds['ping'] = new buzz.sound(wpmudev_chat_localized['settings']['plugin_url'] + 'audio/ping', {
			    formats: [ "mp3","wav","ogg" ]
			});
			//wpmudev_chat.Sounds['chime'] = new buzz.sound(wpmudev_chat_localized['settings']['plugin_url'] + 'audio/chime', {
			//    formats: [ "mp3","wav","ogg" ]
			//});

		} catch(err) {
			if (wpmudev_chat_localized['settings']['soundManager-js'] !== undefined) {
				jQuery.getScript(wpmudev_chat_localized['settings']['soundManager-js'], function(data, textStatus, jqxhr) {
					wpmudev_chat.chat_session_sound_setup();					
				});
			}			
		}
	},
	chat_session_box_actions: function (chat_id) {
		
//		var quicktags_settings = {
//	        id : 'wpmudev-chat-send-'+chat_id,
//	        buttons: "strong,em,link,block"
//	    }
//	    QTags(quicktags_settings);
		//edToolbar('#'+chat_box_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send').
		
		// Just in case we received wrong status from the server. Go through the Site floating windows and set open or closed.
		if (wpmudev_chat.settings['user'][chat_id]['status_max_min'] == "max") {
			wpmudev_chat.chat_session_site_max(chat_id);					
		} else if (wpmudev_chat.settings['user'][chat_id]['status_max_min'] == "min") {
			wpmudev_chat.chat_session_site_min(chat_id);
		}
		
		// Handle the Settings 'gear' click events. We use clicks instead of hover. 
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings ul.wpmudev-chat-actions-settings-menu').css({display: "none"});
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').click(function(event){
			event.preventDefault();
	    	jQuery('ul.wpmudev-chat-actions-settings-menu', this).slideToggle(400);
//			if (jQuery('ul.wpmudev-chat-actions-settings-menu', this).is(':visible') ) {
//				jQuery('ul.wpmudev-chat-actions-settings-menu', this).css('z-index', '9999');
//			} else {
//				jQuery('ul.wpmudev-chat-actions-settings-menu', this).css('z-index', '0');
//			}
		});

		// Handle the Settings 'gear' children menu items clicks. Once a user click a child menu option we close the parent. 
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings ul.wpmudev-chat-actions-settings-menu li a').click(function(event){
			event.preventDefault();
			event.stopPropagation();
			jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings ul.wpmudev-chat-actions-settings-menu').css({display: "none"});
		});

		// Handle the Emoticons click/hover.
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-emoticons ul.wpmudev-chat-emoticons-list').css({display: "none"});
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-emoticons').click(function(event){
			event.preventDefault();
	    	jQuery('ul.wpmudev-chat-emoticons-list', this).slideToggle(400);
		});
		// Emoticons child item. When clicked will close the parent UL
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-emoticons ul.wpmudev-chat-emoticons-list li img').click(function(event){
			event.preventDefault();
			event.stopPropagation();
			jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta li.wpmudev-chat-send-input-emoticons ul.wpmudev-chat-emoticons-list').css({display: "none"});
		});

		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-out a').click(function(event){
			event.preventDefault();
			var popup_href = jQuery(this).attr('href');
			
			var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
			
	    	var popup_chat = window.open(popup_href, chat_session['box_title'], "width=600,height=500,resizable=yes,scrollbars=yes");
			if ((popup_chat == null || typeof(popup_chat) =='undefined')) {
				alert("Your browser has blocked a popup window\n\nWhen try to open the following url:\n"+popup_href);
				window.location.href = popup_href;
				
			} else  {
				wpmudev_chat.popouts[chat_id] = popup_chat;
				
				jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').hide();
				jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').addClass('wpmudev-chat-box-pop-out');
										
				var pollTimer = window.setInterval(function() {
				    if (popup_chat.closed !== false) {
						jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').show();
						jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').removeClass('wpmudev-chat-box-pop-out');
						wpmudev_chat.popouts[chat_id] = '';
				        window.clearInterval(pollTimer);		    
				    } else {
			            //console.log("Pop-up is open");				
					}
				}, 1000);
			}
		});

		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-private.wpmudev-chat-box-invite-pending div.wpmudev-chat-module-invite-prompt button').click(function(event){
			event.preventDefault();
			if (jQuery(this).hasClass('wpmudev-chat-invite-accept')) {
				//console.log('Accepted');
				wpmudev_chat.chat_session_update_user_invite_status(chat_id, 'accepted');
				jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-private').removeClass('wpmudev-chat-box-invite-pending');
				jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box-private').addClass('wpmudev-chat-box-invite-accepted');
			} else if (jQuery(this).hasClass('wpmudev-chat-invite-declined')) {
				//console.log('Declined');
				wpmudev_chat.chat_session_update_user_invite_status(chat_id, 'declined');
				wpmudev_chat.chat_session_remove_item(chat_id);
			}
		});
		

		// Close the Pop-out window
		jQuery('body.wpmudev-chat-pop-out div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings-pop-in a').click(function(event){
			event.preventDefault();
			window.close();
		});

/*
		jQuery(window).resize(function() {
			if (jQuery('body').hasClass('wpmudev-chat-pop-out')) {
				//jQuery('body').append('<p> width:' + jQuery(document).innerWidth() + '  height: '+ jQuery(document).height() +'</p>');
				var height_html = '';
				height_html = height_html + "Dheight: "+jQuery(document).height()+' ';
				height_html = height_html + "Wheight: "+jQuery(window).height()+' ';
//				height_html = height_html + "Dheight: "+window.height()+' ';
//				var height_html = height_html + "innerHeight: "+jQuery(window).innerHeight()+' ';
//				jQuery('body.wpmudev-chat-pop-out p span#win-height').html(height_html);
				
				
				
//				jQuery('body.wpmudev-chat-pop-out div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').height(jQuery(window).height());
				//jQuery('body.wpmudev-chat-pop-out div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box').width(jQuery(window).innerWidth());
			}
		});
*/
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send').on('keyup', wpmudev_chat.chat_session_handle_keyup);
		
		// Handle Minimize/Maximize of the Site floating chat windows. 
	    jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box .wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-min-max img').click(function(event) {

			event.preventDefault();
			
			var chat_box_id 	= jQuery(this).parents('.wpmudev-chat-box').attr('id');
			//var chat_id 		= chat_box_id.replace('wpmudev-chat-box-', '');

			//var chat_box_id = 'div#wpmudev-chat-box-'+chat_id+'..wpmudev-chat-box';
			var chat_site_display_status = '';

			if (jQuery('#'+chat_box_id).hasClass('wpmudev-chat-box-min')) {
				wpmudev_chat.chat_session_site_max(chat_id);
				chat_site_display_status = "max";
				jQuery('#'+chat_box_id+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').show();

			} else if (jQuery('#'+chat_box_id).hasClass('wpmudev-chat-box-max')) {
				wpmudev_chat.chat_session_site_min(chat_id);
				chat_site_display_status = "min";
				jQuery('#'+chat_box_id+' ul.wpmudev-chat-actions-menu li.wpmudev-chat-actions-settings').hide();
			}
			wpmudev_chat.settings['user'][chat_id]['status_max_min'] = chat_site_display_status;
			jQuery.cookie('wpmudev-chat-user', JSON.stringify(wpmudev_chat.settings['user']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});			
			
	    });
	
		// Event handler when the login event is clicked
	    jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-login a.wpmudev-chat-action-login').click(function() {
			var chat_box_id 	= jQuery(this).parents('.wpmudev-chat-box').attr('id');
			
			jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-messages-list').hide();
			jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-users-list').hide();
			jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-login').show();
			jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-login-prompt').hide();			
			
			jQuery('div#wpmudev-chat-box-'+chat_id+' div.wpmudev-chat-module-login input.wpmudev-chat-login-name').focus();
			
			return false;
	    });	

		// Event handler when logout is clicked
	    jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-logout a.wpmudev-chat-action-logout').click(function() {
			if (wpmudev_chat.settings['auth']['type'] == "wordpress") {
				// There is no logout for wordpress users. 
				return;

			} else if (wpmudev_chat.settings['auth']['type'] == "public_user") {
				wpmudev_chat.settings['auth'] = {};

			} else if (wpmudev_chat.settings['auth']['type'] == "facebook") {
				FB.logout();
				wpmudev_chat.settings['auth'] = {};

			} else if (wpmudev_chat.settings['auth']['type'] == "google_plus") {
				if ((wpmudev_chat.settings['auth']['access_token'] != "") && (wpmudev_chat.settings['auth']['access_token'] != undefined)) {
					wpmudev_chat.settings['auth'] = {};
					var revokeUrl = 'https://accounts.google.com/o/oauth2/revoke?token='+wpmudev_chat.settings['auth']['access_token'];
					jQuery.ajax({
						type: 'GET',
						url: revokeUrl,
						async: false,
						contentType: "application/json",
						dataType: 'jsonp',
						success: function(nullResponse) {
							//console.log('logout success');
							wpmudev_chat.settings['auth'] = {};
					    },
					    error: function(e) {
							//console.log('logout error');
					    }
					});
				}
			} else if (wpmudev_chat.settings['auth']['type'] == "twitter") {
				wpmudev_chat.settings['auth'] = {};			
			} 

			// Update our cookie
			jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
						
			wpmudev_chat.chat_session_set_auth_view();
			return false;
	    });	
		
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-exit a.wpmudev-chat-action-exit').click(function(event) {
			event.preventDefault();
			
			//var chat_id = jQuery(this).parents('.wpmudev-chat-box').attr('id').replace('wpmudev-chat-box-', '');
			wpmudev_chat.chat_session_remove_item(chat_id);
	    });	
	    
		// From the login form if the Cancel button is clicked. Cancel and return to default view.
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box button.wpmudev-chat-login-cancel').click(function() {
			var chat_box 	= jQuery(this).parents('.wpmudev-chat-box');
			jQuery('div.wpmudev-chat-module-login', chat_box).hide();
			wpmudev_chat.chat_session_set_auth_view();
			return false;
		});	
	
		// Event handler for Sound Off/On click
	    jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box a.wpmudev-chat-action-sound').click(wpmudev_chat.chat_session_site_change_sound);		

		// From the login form if the Submit button is clicked. Validate the info and take the needed action. 
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box button.wpmudev-chat-login-submit').click(function(){
			var chat_box_id 	= jQuery(this).parents('.wpmudev-chat-box').attr('id');
			//var chat_id 		= chat_box_id.replace('wpmudev-chat-box-', '');

			var form_name		= jQuery('#'+chat_box_id+' input.wpmudev-chat-login-name').val();
			var form_email 		= jQuery('#'+chat_box_id+' input.wpmudev-chat-login-email').val();
			
			var user_info 				= {};
			user_info['type']			= 'public_user';
			user_info['id']				= '';
			user_info['name']			= form_name;
			user_info['profile_link']	= '';
			user_info['avatar']			= form_email;
			user_info['email']			= form_email;

//			jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').html('');
//			jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').hide();
			var replyText = wpmudev_chat.chat_session_user_login(user_info, chat_box_id);
//			if ((replyText != '') && (replyText != undefined)) {
//				jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').html(replyText);
//				jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').show();
//			}
		});
		
		//jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta ul.wpmudev-chat-emoticons-list img').unbind('click');		
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta ul.wpmudev-chat-emoticons-list img').click(function(event) {
			var chat_box_id 	= jQuery(this).parents('.wpmudev-chat-box').attr('id');
			//var chat_id 		= chat_box_id.replace('wpmudev-chat-box-', '');
			//jQuery('#'+chat_box_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area ul.wpmudev-chat-send-meta ul.wpmudev-chat-emoticons-list').css('display', 'none');
			jQuery('#'+chat_box_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send').val( jQuery('#'+chat_box_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send').val()+' '+jQuery(this).attr('alt'));
			jQuery('#'+chat_box_id+'.wpmudev-chat-box div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send').focus();
//			event.preventDefault();
			return false;
			
		}); 
		
		// ADMIN: Session Open
		//jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-status-open a.wpmudev-chat-action-session-open').unbind('click');		
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-status-open a.wpmudev-chat-action-session-open').click(function() {
			var chat_session_status 	= 'open';
			//var chat_id 				= jQuery(this).parents('div.wpmudev-chat-box').attr('id').replace('wpmudev-chat-box-', '');
			wpmudev_chat.chat_session_status_update(chat_id, chat_session_status);

			return false;
	    });	

		// ADMIN: Session Close
		//jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-status-closed a.wpmudev-chat-action-session-closed').unbind('click');		
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-status-closed a.wpmudev-chat-action-session-closed').click(function() {
			var chat_session_status 	= 'closed';
			//var chat_id 				= jQuery(this).parents('div.wpmudev-chat-box').attr('id').replace('wpmudev-chat-box-', '');
			wpmudev_chat.chat_session_status_update(chat_id, chat_session_status);

			return false;
	    });	
	    
		// ADMIN: Clear menu options
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-clear a.wpmudev-chat-action-session-clear').click(function(){
			var chat_box_id 	= jQuery(this).parents('.wpmudev-chat-box').attr('id');

			var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
			if (chat_session != undefined) {

				var sessions_data = {};
				//sessions_data[chat_id] = chat_session;
				sessions_data[chat_id] = {};
				sessions_data[chat_id]['id'] 			= chat_session['id'];
				sessions_data[chat_id]['blog_id'] 		= chat_session['blog_id'];
				sessions_data[chat_id]['session_type'] 	= chat_session['session_type'];

				jQuery.ajax({
					type: "POST",
					url: wpmudev_chat_localized['settings']["ajax_url"],
					cache: false,
					dataType: "json",					
					data: {  
						'action': 'chatProcess',
						'function': 'chat_messages_clear',
						'wpmudev-chat-sessions': sessions_data,
						//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
						//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
						'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
					},
					success: function(reply_data) {
						if (reply_data != undefined) {
							if (reply_data['errorStatus'] != undefined) {
								if (reply_data['errorStatus'] == true) {
									if (reply_data['errorText'] != undefined) {
										console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
									}
								}
							}
						}
					}			
				});
			}
			return false;
		});	


		// ADMIN: Archive menu option
		//jQuery('div.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-archive a.wpmudev-chat-action-session-archive').unbind('click');		
		jQuery('div#wpmudev-chat-box-'+chat_id+'.wpmudev-chat-box div.wpmudev-chat-module-header ul.wpmudev-chat-actions-settings-menu li.wpmudev-chat-action-menu-item-session-archive a.wpmudev-chat-action-session-archive').click(function(){
			var chat_box_id 	= jQuery(this).parents('.wpmudev-chat-box').attr('id');
			//var chat_id 		= chat_box_id.replace('wpmudev-chat-box-', '');
	
			var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
			if (chat_session != undefined) {

				var sessions_data = {};
				//sessions_data[chat_id] = chat_session;
				sessions_data[chat_id] = {};
				sessions_data[chat_id]['id'] 			= chat_session['id'];
				sessions_data[chat_id]['blog_id'] 		= chat_session['blog_id'];
				sessions_data[chat_id]['session_type'] 	= chat_session['session_type'];

				jQuery.ajax({
					type: "POST",
					url: wpmudev_chat_localized['settings']["ajax_url"],
					cache: false,
					dataType: "json",
					data: {  
						'action': 'chatProcess',
						'function': 'chat_messages_archive',
						'wpmudev-chat-sessions': sessions_data,
						//'wpmudev-chat-auth': wpmudev_chat.settings['auth'],
						//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
						'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
					},
					success: function(reply_data) {
						if (reply_data != undefined) {
							if (reply_data['errorStatus'] != undefined) {
								if (reply_data['errorStatus'] == true) {
									if (reply_data['errorText'] != undefined) {
										console.log("Chat: chat_session_moderate_user: reply [%s]", reply_data['errorText']);
									}
								}
							}
						}
					}			
				});
			}
			return false;
		});	
		
	},
	chat_session_site_change_sound: function(event) {
		event.preventDefault();
        
		var chat_box 	= jQuery(this).parents('.wpmudev-chat-box');
		var chat_id 	= jQuery(chat_box).attr('id').replace('wpmudev-chat-box-', '');

		if (jQuery(chat_box).hasClass('wpmudev-chat-box-sound-on')) {
			jQuery(chat_box).removeClass('wpmudev-chat-box-sound-on');
			jQuery(chat_box).addClass('wpmudev-chat-box-sound-off');

			wpmudev_chat.settings['user'][chat_id]['sound_on_off'] = "off";
			
		} else if (jQuery(chat_box).hasClass('wpmudev-chat-box-sound-off')) {
			jQuery(chat_box).removeClass('wpmudev-chat-box-sound-off');
			jQuery(chat_box).addClass('wpmudev-chat-box-sound-on');

			wpmudev_chat.settings['user'][chat_id]['sound_on_off'] = "on";			
		}
		jQuery.cookie('wpmudev-chat-user', JSON.stringify(wpmudev_chat.settings['user']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});

	},
	chat_session_site_max: function (chat_id) {
		if (jQuery('body').hasClass('wpmudev-chat-pop-out'))
			return;
			
		var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
		if (chat_session == undefined) return;

		var chat_box = jQuery('#wpmudev-chat-box-'+chat_id);
		jQuery(chat_box).removeClass('wpmudev-chat-box-min');
		jQuery(chat_box).addClass('wpmudev-chat-box-max');

		if (chat_session['box_height'] != undefined) {
			jQuery(chat_box).height(chat_session['box_height']);			
		}
		
		jQuery('.wpmudev-chat-module', chat_box).each(function() {
			if (jQuery(this).hasClass('wpmudev-chat-module-min-hidden')) {
				jQuery(this).removeClass('wpmudev-chat-module-min-hidden');
				jQuery(this).show();				
			}
		});

		// Swap our corner images
		jQuery('.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-min-max img.wpmudev-chat-min', chat_box).show();
		jQuery('.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-min-max img.wpmudev-chat-max', chat_box).hide();

		// Let the chat_session_set_auth function figure out what modules to show.
		wpmudev_chat.chat_session_set_auth_view();
	},
	chat_session_site_min: function (chat_id) {
		
		var chat_session = wpmudev_chat.chat_session_get_session_by_id(chat_id);
		if (chat_session == undefined) return;
		
		var chat_box = jQuery('#wpmudev-chat-box-'+chat_id);
		jQuery(chat_box).removeClass('wpmudev-chat-box-max');
		jQuery(chat_box).addClass('wpmudev-chat-box-min');

		var chat_box_height_old = jQuery(chat_box).outerHeight();
		var chat_box_height_new = 0;
		
		jQuery('.wpmudev-chat-module', chat_box).each(function() {
			if (jQuery(this).hasClass('wpmudev-chat-module-header')) {
				chat_box_height_new += jQuery(this).outerHeight(true);
			} else {
				if (jQuery(this).is(':visible')) {
					jQuery(this).addClass('wpmudev-chat-module-min-hidden');
					jQuery(this).hide();
				}
			}
		});

		if (chat_box_height_new > 0) {
			jQuery(chat_box).height(chat_box_height_new);

			if (chat_session['box_position_v'] == "bottom") {
				var border_width = chat_session['box_border_width'] ? chat_session['box_border_width'] : 0;
				border_width = parseInt(border_width);
				border_width = border_width ? border_width : 0;
//				jQuery(chat_box).css('bottom', chat_box_height_new-(chat_box_height_old-border_width-border_width));
			}
		}

		// Swap our corner images
		jQuery('.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-min-max img.wpmudev-chat-max', chat_box).show();
		jQuery('.wpmudev-chat-module-header ul.wpmudev-chat-actions-menu li.wpmudev-chat-min-max img.wpmudev-chat-min', chat_box).hide();
		
		return false;
	},
	
	chat_session_user_login: function(user_info, chat_box_id) {

		//console.log('chat_session_user_login user_info %o', user_info);

		jQuery.ajax({
			type: "POST",
			url: wpmudev_chat_localized['settings']["ajax_url"],
			dataType: "json",
			cache: false,
			data: {
				'function': 'chat_user_login',
				'action': 'chatProcess',
				'user_info': user_info,
				//'wpmudev-chat-settings': wpmudev_chat_localized['settings']
				'wpmudev-chat-settings-abspath': wpmudev_chat_localized['settings']['ABSPATH']
			},
			success: function(reply_data) {
				if (reply_data != undefined) {
					if (reply_data['errorStatus'] != undefined) {
						if (reply_data['errorStatus'] == false) {							
							if (reply_data['user_info'] != undefined) {
								wpmudev_chat.settings['auth'] = reply_data['user_info'];

								jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
								//var tmp_cookie_json = jQuery.cookie('wpmudev-chat-auth');
								//var tmp_cookie_obj	= JSON.parse(tmp_cookie_json);
								
								if (chat_box_id != '') {
									jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').html('');
									jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').hide();
								}
								wpmudev_chat.chat_session_set_auth_view();

				    			return false;
							}
				
						} else if (reply_data['errorStatus'] == true) {
							if ((reply_data['errorText'] != undefined) && (reply_data['errorText'] != '')) {
								//return reply_data['errorText'];
								if (chat_box_id != '') {
									jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').html(reply_data['errorText']);
									jQuery('#'+chat_box_id+' .wpmudev-chat-login-error').show();
								}
								
							}
						} 
					}
				}
			}
		});
	},
/*
	chat_session_facebook_setup: function() {
		
		if (wpmudev_chat_localized['settings']['facebook_active'] == "1") {

			if (!jQuery('#fb-root').length) {
				jQuery("body").append('<div id="fb-root"></div>');
			}

			window.fbAsyncInit = function() {
				
				FB.init({appId: wpmudev_chat_localized['settings']['facebook_app_id'], status: true, cookie: true, xfbml: true});
				FB.XFBML.parse();

				FB.Event.subscribe('auth.statusChange', function(response) {
					var _cookie_auth_str = jQuery.cookie('wpmudev-chat-auth');
					if (_cookie_auth_str == '') {
						wpmudev_chat.settings['auth'] = {};
					} else {
						wpmudev_chat.settings['auth'] = jQuery.parseJSON(_cookie_auth_str);
					}

					if (response.status === 'connected') {

						FB.api('/me', function(response) {
							if (response.id != undefined) {

								var user_info 				= {};
								user_info['type']			= 'facebook';
								user_info['id']				= response.id
								user_info['name']			= response.name;
								user_info['profile_link']	= response.link;
								user_info['avatar']			= "http://graph.facebook.com/"+response.id+"/picture";
								user_info['email']			= '';

								wpmudev_chat.chat_session_user_login(user_info, '');

							} else {
								if ((wpmudev_chat.settings['auth']['type'] != undefined) && (wpmudev_chat.settings['auth']['type'] == "facebook")) {
									wpmudev_chat.settings['auth'] = {};

									jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
									wpmudev_chat.chat_session_set_auth_view();
								}
							}
						});
					} else if (response.status === 'not_authorized') {
						if ((wpmudev_chat.settings['auth']['type'] != undefined) && (wpmudev_chat.settings['auth']['type'] == "facebook")) {
							wpmudev_chat.settings['auth'] = {};
							jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
							wpmudev_chat.chat_session_set_auth_view();
						}

		  			} else {
						if ((wpmudev_chat.settings['auth']['type'] != undefined) && (wpmudev_chat.settings['auth']['type'] == "facebook")) {
							wpmudev_chat.settings['auth'] = {};
							jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
							wpmudev_chat.chat_session_set_auth_view();
						}
		  			}
			    });
			};
			
		    (function(d, s, id){
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) {return;}
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/en_US/all.js";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));
			
			
		} else {
			jQuery('div.wpmudev-chat-box span.chat-facebook-signin-btn').hide();
		}
		return;	
	},
*/
	chat_session_facebook_setup: function() {
		//console.log('in chat_session_facebook_setup');
		if (wpmudev_chat_localized['settings']['facebook_active'] == "1") {
			//console.log('using FB');
			
			if (!jQuery('#fb-root').length) {
				jQuery("body").append('<div id="fb-root"></div>');
			}
			FB.init({appId: wpmudev_chat_localized['settings']['facebook_app_id'], status: true, cookie: true, xfbml: true});
			FB.XFBML.parse();
		
			FB.Event.subscribe('auth.statusChange', function(response) {
				var _cookie_auth_str = jQuery.cookie('wpmudev-chat-auth');
				if (_cookie_auth_str == '') {
					wpmudev_chat.settings['auth'] = {};
				} else {
					wpmudev_chat.settings['auth'] = jQuery.parseJSON(_cookie_auth_str);
				}

				if (response.status === 'connected') {
					//console.log('FB connected %o', response);
					FB.api('/me', function(response) {
						if (response.id != undefined) {

							var user_info 				= {};
							user_info['type']			= 'facebook';
							user_info['id']				= response.id
							user_info['name']			= response.name;
							user_info['profile_link']	= response.link;
							user_info['avatar']			= "http://graph.facebook.com/"+response.id+"/picture";
							user_info['email']			= '';

							wpmudev_chat.chat_session_user_login(user_info, '');
						
						} else {
							if ((wpmudev_chat.settings['auth']['type'] != undefined) && (wpmudev_chat.settings['auth']['type'] == "facebook")) {
								wpmudev_chat.settings['auth'] = {};

								jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
								wpmudev_chat.chat_session_set_auth_view();
							}
						}
					});
				} else if (response.status === 'not_authorized') {
					//console.log('FB not_authorized %o', response);
					
					if ((wpmudev_chat.settings['auth']['type'] != undefined) && (wpmudev_chat.settings['auth']['type'] == "facebook")) {
						wpmudev_chat.settings['auth'] = {};
						jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
						wpmudev_chat.chat_session_set_auth_view();
					}
				
	  			} else {
					//console.log('FB unknown %o', response);
					if ((wpmudev_chat.settings['auth']['type'] != undefined) && (wpmudev_chat.settings['auth']['type'] == "facebook")) {
						wpmudev_chat.settings['auth'] = {};
						jQuery.cookie('wpmudev-chat-auth', JSON.stringify(wpmudev_chat.settings['auth']), { path: wpmudev_chat_localized['settings']['cookiepath'], domain: wpmudev_chat_localized['settings']['cookie_domain']});
						wpmudev_chat.chat_session_set_auth_view();
					}
	  			}
		    });
	    
		} else {
			jQuery('div.wpmudev-chat-box span.chat-facebook-signin-btn').hide();
		}
		return;	
	},
	chat_session_google_plus_setup: function() {
		if (wpmudev_chat_localized['settings']['google_plus_active'] == "1") {
		
			var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
			po.src = 'https://apis.google.com/js/client:plusone.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
		}
	},
	
	
	chat_session_twitter_setup: function() {
		if (wpmudev_chat_localized['settings']['twitter_active'] == 1) {
			//console.log('Twitter active');
			jQuery('.wpmudev-chat-box .wpmudev-chat-module-login a.chat-twitter-signin-btn').click(function(event){
		        event.preventDefault();
				//var popup_href = jQuery(this).attr('href');				
				if (window.location.search == "") {
					var popup_href = window.location.href+"?"+"&wpmudev-chat-action=pop-twitter";;
				} else {
					var popup_href = window.location.href+"&wpmudev-chat-action=pop-twitter";
				}				
				//console.log('popup_href=['+popup_href+']');
				window.location.href = popup_href;

// The following is not longer used but produces a popup window for Teitter Auth. Using the above is a redirect and cleaner. 
/*
				var popup_twitter_auth = window.open(popup_href, "", "width=600,height=500,resizable=yes,scrollbars=yes");									
				if ((popup_twitter_auth == null || typeof(popup_twitter_auth) =='undefined')) {
					//alert("Your browser has blocked a popup window\n\nWe try to open the following url:\n"+popup_href);
					window.location(popup_href);
				} else  {
					var pollTimerTwitter = window.setInterval(function() {
					    if (popup_twitter_auth.closed !== false) {
					        console.log("Twitter Pop-up is closed");
					        window.clearInterval(pollTimerTwitter);		    
							var auth_cookie = jQuery.cookie('wpmudev-chat-auth');
							if ((auth_cookie != undefined) && (!jQuery.isEmptyObject(auth_cookie))) {
								wpmudev_chat.settings['auth'] = JSON.parse(auth_cookie);
								wpmudev_chat.chat_session_set_auth_view();
							}
					    } 
					}, 1000);
				} 
*/
				return false;

			});
		}
	},


	wp_admin_bar_setup: function() {

		if (jQuery('#wpadminbar #wp-toolbar li#wp-admin-bar-wpmudev-chat-container').length) {
			
			// Hide the current status
			if (wpmudev_chat.settings['auth']['chat_status'] != undefined) {
				jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container li#wp-admin-bar-wpmudev-chat-user-status-change-'+wpmudev_chat.settings['auth']['chat_status']).addClass('wpmudev-chat-user-status-current');
			}
			
			if (wpmudev_chat.bound != true) {
				wpmudev_chat.bound = true;
				
				jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container li#wp-admin-bar-wpmudev-chat-user-statuses ul#wp-admin-bar-wpmudev-chat-user-statuses-default li a.ab-item').click(function (event) {
		        	event.preventDefault();
					var user_new_status = jQuery(this).attr('rel');
				
					if (wpmudev_chat.settings['auth']['chat_status'] != user_new_status) {				
						wpmudev_chat.chat_process_user_status_change(user_new_status);
					} 
					return false;			
				});
		
				jQuery('#wp-toolbar li#wp-admin-bar-wpmudev-chat-container li#wp-admin-bar-wpmudev-chat-user-friends ul.ab-submenu li a.wpmudev-chat-user-invite').click(function (event) {
		        	event.preventDefault();
					var user_hash = jQuery(this).attr('rel');
					user_hash = user_hash ? user_hash : '';
					if (user_hash != '') {
						wpmudev_chat.chat_process_private_invite(user_hash);
					}
					return false;
				});
			} else {
				//console.log('already click events');				
			}
		}
	},
	
	chat_privite_invite_click: function() {
		// Check for WPMU DEV Friends list page
		if (jQuery('div.friends-wrap').length) {
			jQuery('div.friends-wrap a.wpmudev-chat-user-invite').click(function (event) {
				event.preventDefault();						
				var user_hash = jQuery(this).attr('rel');
				user_hash = user_hash ? user_hash : '';
				if (user_hash != '') {
					wpmudev_chat.chat_process_private_invite(user_hash);
				}
			});
		}
		
		// Check for WP User List
		if (jQuery('body.users-php table.wp-list-table td.column-wpmudev-chat-status').length) {
			jQuery('body.users-php table.wp-list-table td.column-wpmudev-chat-status a.wpmudev-chat-user-invite').click(function (event) {
				event.preventDefault();						
				var user_hash = jQuery(this).attr('rel');
				user_hash = user_hash ? user_hash : '';
				if (user_hash != '') {
					wpmudev_chat.chat_process_private_invite(user_hash);
				}
			});
		}

		// Check for BP User List
		jQuery(document).on("click", "body.buddypress ul#members-list div.wpmudev-chat-now-button a.wpmudev-chat-user-invite", function(event){
			event.preventDefault();						
			var user_hash = jQuery(this).attr('rel');
			user_hash = user_hash ? user_hash : '';
			if (user_hash != '') {
				wpmudev_chat.chat_process_private_invite(user_hash);
			} 
		});

		jQuery(document).on("click", "body.buddypress ul#member-list div.wpmudev-chat-now-button a.wpmudev-chat-user-invite", function(event){
			event.preventDefault();						
			var user_hash = jQuery(this).attr('rel');
			user_hash = user_hash ? user_hash : '';
			if (user_hash != '') {
				wpmudev_chat.chat_process_private_invite(user_hash);
			} 
		});
	}
});
jQuery(document).ready(wpmudev_chat.init());

function WPMUDEVChatGooglePlusSigninCallback(authResult) {
	if (authResult['access_token']) {
		// Successfully authorized
		// Hide the sign-in button now that the user is authorized, for example:
		//document.getElementById('signinButton').setAttribute('style', 'display: none');
	
		jQuery.ajax({
			type: "GET",
			url: "https://www.googleapis.com/plus/v1/people/me?access_token="+authResult['access_token'],
			cache: false,
			dataType: "json",
			success: function(response) {
				if (response.id != undefined) {
				
					var user_info				= {};
					user_info['access_token']	= authResult['access_token'];
					user_info['type']			= 'google_plus';
					user_info['id']				= response['id'];
					user_info['name']			= response['displayName'];
					user_info['profile_link']	= response['url'];						
					user_info['avatar']			= response['image']['url'];

					wpmudev_chat.chat_session_user_login(user_info, '');
				}
			}
		});
  	} else if (authResult['error']) {
    	// There was an error.
    	// Possible error codes:
    	//   "access_denied" - User denied access to your app
    	//   "immediate_failed" - Could not automatially log in the user
    	// console.log('There was an error: ' + authResult['error']);
  	}
}
