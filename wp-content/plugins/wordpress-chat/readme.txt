=== Chat ===
Contributors: Paul Menard, Incsub
Tags: chat, twitter, facebook, google, shortcode, buddypress, widget, chat widget, buddypress group, buddypress friend
Requires at least: 3.5
Stable tag: trunk
Tested up to: 3.6

Allow your readers to chat with you

== Description ==

Add a chat to your blog and allow your readers to chat with you.

== Screenshots ==

1. In post chat
2. Chat widget

== ChangeLog ==

= 2.0.4.4 =
- Fixed reported issue where chat_id was set yo zero or null. 
- Fixed reported issue where pop-out chat on Pages/Widgets was including private chat sessions.
- Added CSS/JS to help control chat avatar to override some themes which set content images too large.
- Added CSS/JS to help control images added on list items to prevent showing within the chat session

= 2.0.4.3 =
- Fixed reported issues with BuddyPress 1.8 Groups and Groups Admin sections
- Fixed reported issues where setting non-auth view was not showing proper display
- Fixed reported issues where Friend/Member private chat buttons were not working correctly in BuddyPress various pages
- Fixed display issue on minimized chat that was showing the chat maximized on initial page load briefly before forcing minimized via JS.

= 2.0.4.2 =
- Fixed issue with position of private chat box when bottom corner chat not enabled. Missing CSS for positioned chat elements
- Fixed issue with Group Chat under BuddyPress 1.8
- Removed footer debug output. 

= 2.0.4.1 =
- Added Chat options to BuddyPress profile settings to control chat status, and visibility.
- Fixed issue where user can hack cookie and promote self to moderator user list. But not actual moderator functionality.
- Fixed issue where user avatar for user list and message were not matched. 
- Fixed issue where deleting single message row forced cleared the chat session instead of hiding row. 
- Fixed issue where selecting NO for Load JS/CSS effected chat admin screens layouts.
- Cleanup some of the INSERT queries which were reporting error on some member sites because the database columns didn't have assigned default values. 



= 2.0.4 =
- Fixed issue with transient keys length causing BuddyPress Group chat not to load properly. 
- Fixed issue in Bottom Corner chat were non-authenticated users were still able to post to a closed session.
- Fixed issue where proper settings tab not being set active when link to with hash.
- Fixed issue where WP toolbar Chat menu was not being hidden on admin URLs when configured not to show.
- Fixed issue where chat session sound is disabled was still producing ping sound on new messages.
- Removed some debug statements from JavaScript.
- Added Settings Widget option to hide on URLs where shortcode is used. 
- Added Settings Widget option to include/exclude widget chats on specified URLs.
- Added Settings Site option to hide on URLs where shortcode is used. 
- Added Settings Site option to include/exclude bottom corner chats on specified URLs.
- Added Settings Global option to prevent load of JS/CSS where chats are not displayed. By default JS/CSS are loaded on ALL URLs.
- Added Settings Global option to using WordPress AJAX instead of plugin AJAX. Calling the plugin AJAX file is prevented in some server configurations. 


= 2.0.3 =
- Updated JS/CSS enqueue logic to only load JS/CSS where essentially needed. 
- Update AJAX processing to handle be more efficient and not send settings data as POST information
- Fixed PHP Notices shown when Chat TinyMCE Post types and/or WP Roles were empty
- Fixed issue where Chat tTinyMCE button was effecting other TinyMCE buttons from functioning like link popup
- Added retry logic to AJAX init and message_update process. For better handling of server errors. 
- Corrected issue with language files not loading correctly on AJAX requests. 
- Corrected issue on non-Multisite where blog_id was being set to zero instead of one.
- Rewrite of message filtering logic to provide better support for cyrillic and other language formats. Also provides better processing for inline code blocks.
- Correct formatting of SQL used for dbDelta which caused PHP Notices related to duplicate keys
- Updates language files. 
- Added WP toolbar color support for MP6 Admin plugin

= 2.0.2 =
- Updated to user Twitter API version 1.1 since 1.0 expired on June 12, 2012
- Fixed issue for allowing local user avatars. 
- Fixed issue on Multisite where promoted super admins were not seeing chat button on post type button bar.
- Fixed issue with database check logic not properly setting current version on activation for Multisite. 
- Fixed issue in Site options exclude URL comparison. 
- Fixed issue where language files were not being loaded properly on the polling loop. 
- Added new Settings Common > WP Admin panel to control not showing chat on certain pages within WP Admin.
- Added new Settings Common > BuddyPress panel to control BP Group page slug and menu label values
- Replace JavaScript Audio library to be more flexible. 
- Added lazy loading of jQuery.cookie and buzz sound in case loaded from other plugins. 

= 2.0.1 =
- Corrected database update/migration errors and warnings.
- Corrected warnings and notices when using WP_DEBUG on
- Re-added filter to allow language input into messages. This was added into 1.3.x but the code was not added to the 2.0 code base.

= 2.0 =
- Rewrite of core messaging function to improve overall performance.
- Added support for Google+ user authentication.
- Added integration for BuddyPress group and friends.
- Added integration with WPMU DEV Friends plugin.
- Redesign of chat UI.
- Added support for user lists for each chat session.
- Better support for Widgets.
- Support for Private one-to-one chats initiated by moderators
- Support for one-to-one chat between WP users.
- More settings to control the look, feel and colors of the chat windows
- Added ability to ban user by email address
- Added ability to block words. 
- Added ability to control position of bottom corner and private chat to top/bottom/left/right
- Added popout/popin ability on all chat windows to break out of the theme frame. Full screen on tablets and smart phones.

= 1.3.0.2 =
- Corrected issue where bottom corner chat was not resuming polling after being closed then opened.
- Removed some debug output from message replies.

= 1.3.0.2 =
- Corrected some undefined variables which throw Notices when full error reporting is enabled.

= 1.3.0.1 =
- Corrected some undefined variables in the widget.

= 1.3.0 =
- Added Advanced option to limit of TinyMCE button to selected post types.
- Added Advanced option to limit of TinyMCE button to selected user roles.
- Rewrote code messaging logic to limit polling. This should clear up many user reports or chat crashing servers. 

= 1.2.0 =

* Corrected logic when using Facebook authentication only for bottom corner chat and not for inline chat. Which was causing endless refresh of page http://premium.wpmudev.org/forums/topic/wordpress-chat-endlessly-refreshes-for-facebook
* Renamed global plugin instance from $chat to $wpmudev_chat. https://app.asana.com/0/589152284006/1796940364279
* Added Chat Widget with some of the options. http://premium.wpmudev.org/forums/topic/chat-box-as-a-widget-instead-of-floating
* Added support for moderator to delete/undelete messages http://premium.wpmudev.org/forums/topic/moderate-chat-ban-users-delete-messages
* Added support to close/open chat session. Similar to WPMU DEV. Thanks Enzo. 
* Corrected emoticons. Had two not properly displaying. 
* Corrected issue where depending on the WordPress setup the trailing slash is removed from the base URL. Causing sound manager to not load. http://premium.wpmudev.org/forums/topic/soundmanager2swf-404-chat-plugin
* Added some color options for Row area background, Row item background, Row item border width, Row item border color. http://premium.wpmudev.org/forums/topic/moderate-chat-ban-users-delete-messages
* Switched plugin to use new WPMU DEV Dashboard plugin updates


= 1.1.0 =

* Recode Facebook authentication for OAuth 2.0 and PHP-SDK

= 1.0.9.1 =

* Twitter instructions updated

= 1.0.9 =

* Fixed: Archive and clear capabilties conflict

= 1.0.8 =

* Fixed: TwentyEleven header image covering chat window
* Fixed: In IE message text box loses focus

= 1.0.7 =

* Fixed: Wrong path for soundmanager2.swf
* Fixed: Scrolling issue
* Stop autoscrolling if the user scrolls to a particular position
* Fixed: Bottom corner chat size changes
* Highlight chat box in a different color when there is a new message
* Fixed: Prevent line breaks when enter key is pressed
* Fixed: Code tags instructions
* Fixed: Chat message encoding issue
* Do not include swf if sound is disabled
* Function split() is deprecated

= 1.0.6 =

* Fixed: Missing styles

= 1.0.5 =

* Improve host compatibility with login with Facebook
* Balance code tags
* Allow multiple links to be in the chat message

= 1.0.4 =

* Remove chat js if no chat is in the page
* Fixed: upgrade race

= 1.0.3 =

* Fixed: Multiple messages posted
* Added Moderators
* Notify user when offline

= 1.0.2 =

* Tested with WordPress 3.1
* Added Auto Update plugin installation check
* Fixed: mod_security issue
* Fixed: FB & Twitter button alignment
* Fixed: Setting height and width of a in post chat
* Fixed: Configure refresh interval

= 1.0.1 =

* Fixed: Parse error: syntax error, unexpected T_STATIC, expecting T_OLD_FUNCTION or T_FUNCTION or T_VAR or '}'
* Fixed: Issue in displaying non ASCII characters (Ü,Ö,Ä,ü,ö,ä,...)
* Fixed: Not sufficient permissions to modify Chat plugin settings
* Fixed: Slashes in message and author name
* Fixed: Sound issues
* Fixed: IE 7 javascript errors
* Fixed: Timezone issue
* Fixed: Unicode characters issue
* Allow users with only edit_posts or edit_pages (not both) to add to posts
* Fixed: IE 8 javascript errors in wp-admin
* Allow admin to control the chat text color

= 1.0.0 =

* Initial release


36617-1375208134