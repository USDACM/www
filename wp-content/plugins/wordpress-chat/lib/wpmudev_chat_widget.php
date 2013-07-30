<?php
if (!class_exists('WPMUDEVChatWidget')) {

	class WPMUDEVChatWidget extends WP_Widget {

		function WPMUDEVChatWidget () {
			global $wpmudev_chat;

			$widget_ops = array('classname' => __CLASS__, 'description' => __('WPMU DEV Chat Widget.', $wpmudev_chat->translation_domain));
			parent::WP_Widget(__CLASS__, __('WPMU DEV Chat Widget', $wpmudev_chat->translation_domain), $widget_ops);
		}

		function form($instance) {
			global $wpmudev_chat;

			// Set defaults
			// ...
			$defaults = array(
				'title' 		=> 	'',
				'id'			=>	'',
				'height'		=>	'300px',
				'sound'			=>	'disabled',
				'avatar'		=>	'avatar',
				'emoticons'		=>	'disabled',
				'row_date'		=>	'disabled',
				'row_time'		=>	'disabled'
			);

			$instance = wp_parse_args( (array) $instance, $defaults );
			//echo "instance<pre>"; print_r($instance); echo "</pre>";

			if (empty($instance['height'])) {
				$instance['height'] = "300px";
			}

			if ($instance['sound'] == "enabled")
				$widget_sound = 'checked="checked"';
			else
			 	$widget_sound = '';

			if ($instance['avatar'] == "avatar")
				$widget_avatar = 'checked="checked"';
			else
			 	$widget_avatar = '';

			if ($instance['emoticons'] == "enabled")
				$widget_emoticons = 'checked="checked"';
			else
			 	$widget_emoticons = '';

			if ($instance['row_date'] == "enabled")
				$widget_date = 'checked="checked"';
			else
			 	$widget_date = '';

			if ($instance['row_time'] == "enabled")
				$widget_time = 'checked="checked"';
			else
			 	$widget_time = '';

			?>
			<input type="hidden" name="<?php echo $this->get_field_name('id'); ?>" id="<?php echo $this->get_field_id('id'); ?>"
				class="widefat" value="<?php echo $instance['id'] ?> "/>
			<p>
				<label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:', $wpmudev_chat->translation_domain); ?></label>
				<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>"
					class="widefat" value="<?php echo $instance['title'] ?>" />
			</p>

			<p>
				<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php
					_e('Height for widget:', $wpmudev_chat->translation_domain); ?></label>

				<input type="text" id="<?php echo $this->get_field_id( 'height' ); ?>" value="<?php echo $instance['height']; ?>"
					name="<?php echo $this->get_field_name( 'height'); ?>" class="widefat" style="width:100%;" />
					<span class="description"><?php _e('The width will be 100% of the widget area', $wpmudev_chat->translation_domain); ?></span>
			</p>

			<p>
				<input type="checkbox" class="checkbox" <?php echo $widget_sound; ?> id="<?php echo $this->get_field_id( 'sound' ); ?>"
					value="<?php echo $instance['sound']; ?>"
					name="<?php echo $this->get_field_name( 'sound'); ?>" /> <label for="<?php echo $this->get_field_id( 'sound' ); ?>"><?php
						_e('Enable Sound', $wpmudev_chat->translation_domain); ?></label><br />

				<input type="checkbox" class="checkbox" <?php echo $widget_avatar; ?> id="<?php echo $this->get_field_id( 'avatar' ); ?>"
					value="<?php echo $instance['avatar']; ?>"
					name="<?php echo $this->get_field_name( 'avatar'); ?>" /> <label for="<?php echo $this->get_field_id( 'avatar' ); ?>"><?php
						_e('Show User Avatars', $wpmudev_chat->translation_domain); ?></label><br />

				<input type="checkbox" class="checkbox" <?php echo $widget_emoticons; ?> id="<?php echo $this->get_field_id( 'emoticons' ); ?>"
					value="<?php echo $instance['emoticons']; ?>"
					name="<?php echo $this->get_field_name( 'emoticons'); ?>" /> <label for="<?php echo $this->get_field_id( 'emoticons' ); ?>"><?php
						_e('Show Emoticons', $wpmudev_chat->translation_domain); ?></label><br />

				<input type="checkbox" class="checkbox" <?php echo $widget_date; ?> id="<?php echo $this->get_field_id( 'row_date' ); ?>"
					value="<?php echo $instance['row_date']; ?>"
					name="<?php echo $this->get_field_name( 'row_date'); ?>" /> <label for="<?php echo $this->get_field_id( 'row_date' ); ?>"><?php
						_e('Show Date', $wpmudev_chat->translation_domain); ?></label><br />

				<input type="checkbox" class="checkbox" <?php echo $widget_time; ?> id="<?php echo $this->get_field_id( 'row_time' ); ?>"
					value="<?php echo $instance['row_time']; ?>"
					name="<?php echo $this->get_field_name( 'row_time'); ?>" /> <label for="<?php echo $this->get_field_id( 'row_time' ); ?>"><?php
						_e('Show Time', $wpmudev_chat->translation_domain); ?></label>
			</p>


			<?php /* ?><p><a class="wpmudev-chat-widget-settings" href="#">Edit Chat Options</a></p><?php */ ?>
			<p><?php _e('More control over widgets colors and options via', $wpmudev_chat->translation_domain)?> <a
				href="<?php echo admin_url( 'admin.php?page=chat_settings_panel_widget'); ?>"><?php _e('Widget Settings Menu', $wpmudev_chat->translation_domain); ?></a></p>

			<?php
		}

		function update($new_instance, $old_instance) {
			global $wpmudev_chat;

			//echo "new_instance<pre>"; print_r($new_instance); echo "</pre>";
			//echo "old_instance<pre>"; print_r($old_instance); echo "</pre>";
			//die();

			$instance = $old_instance;

			$instance['title'] 			= strip_tags($new_instance['title']);

			if ((!empty($new_instance['id'])) && (intval($new_instance['id'])))
				$instance['id'] 		= intval($new_instance['id']);
			else {
				$last_chat_id = wpmudev_chat_get_last_chat_id();
				$instance['id']	=  rand($last_chat_id+1, $last_chat_id*1000);
			}

			if (isset($new_instance['height']))
				$instance['height'] 	= esc_attr($new_instance['height']);
			else
				$instance['height']		= '300px';

			if (isset($new_instance['sound']))
				$instance['sound'] 		= 'enabled';
			else
				$instance['sound']		= 'disabled';

			if (isset($new_instance['avatar']))
				$instance['avatar'] 	= 'avatar';
			else
				$instance['avatar']		= 'name';

			if (isset($new_instance['emoticons']))
				$instance['emoticons'] 	= 'enabled';
			else
				$instance['emoticons']		= 'disabled';

			if (isset($new_instance['row_date']))
				$instance['row_date'] 	= 'enabled';
			else
				$instance['row_date']		= 'disabled';

			if (isset($new_instance['row_time']))
				$instance['row_time'] 	= 'enabled';
			else
				$instance['row_time']		= 'disabled';

			//echo "instance<pre>"; print_r($instance); echo "</pre>";

			return $instance;
		}

		function widget($args, $instance) {
			global $wpmudev_chat, $post, $bp;

			if ($wpmudev_chat->get_option('blocked_on_shortcode', 'widget') == "enabled") {
				if (strstr($post->post_content, '[chat ') !== false)
					return;
			}


			if ((isset($bp->groups->current_group->id)) && (intval($bp->groups->current_group->id))) {

				// Are we viewing the Group Admin screen?
				$bp_group_admin_url_path 	= parse_url(bp_get_group_admin_permalink($bp->groups->current_group), PHP_URL_PATH);
				$request_url_path 			= parse_url(get_option('siteurl') . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

				if ( (!empty($request_url_path)) && (!empty($bp_group_admin_url_path))
			  	  && (substr($request_url_path, 0, strlen($bp_group_admin_url_path)) == $bp_group_admin_url_path) ) {
					if ($wpmudev_chat->get_option('bp_group_admin_show_widget', 'global') != "enabled") {
						return;
					}
				} else {
					if ($wpmudev_chat->get_option('bp_group_show_widget', 'global') != "enabled") {
						return;
					}
				}
			}


			if ($wpmudev_chat->_chat_plugin_settings['blocked_urls']['widget'] != true) {

				echo $args['before_widget'];

				$title = apply_filters('widget_title', $instance['title']);
				if ($title) echo $args['before_title'] . $title . $args['after_title'];

				if (isset($new_instance['height']))
					$instance['height'] 	= esc_attr($new_instance['height']);
				else
					$instance['height']		= '300px';

				if (isset($new_instance['sound']))
					$instance['sound'] 		= 'enabled';
				else
					$instance['sound']		= 'disabled';

				if (isset($new_instance['avatar']))
					$instance['avatar'] 	= 'avatar';
				else
					$instance['avatar']		= 'name';

				if (isset($new_instance['emoticons']))
					$instance['emoticons'] 	= 'enabled';
				else
					$instance['emoticons']		= 'disabled';

				if (isset($new_instance['row_date']))
					$instance['row_date'] 	= 'enabled';
				else
					$instance['row_date']		= 'disabled';

				if (isset($new_instance['row_time']))
					$instance['row_time'] 	= 'enabled';
				else
					$instance['row_time']		= 'disabled';

				//echo "instance<pre>"; print_r($instance); echo "</pre>";

				$atts = array(
					'id' 					=> 	$args['widget_id'],
					'session_type'			=> 	'widget',
					'box_height'			=>	$instance['height'],
					'box_sound'				=>	$instance['sound'],
					'row_name_avatar'		=>	$instance['avatar'],
					'box_emoticons'			=>	$instance['emoticons'],
					'date_show'				=>	$instance['row_date'],
					'row_time'				=>	$instance['row_time']
				);

				echo $wpmudev_chat->process_chat_shortcode($atts);

				echo $args['after_widget'];
			}
		}
	}

	function wpmudev_chat_widget_init_proc() {
		register_widget('WPMUDEVChatWidget');
	}
	add_action( 'widgets_init', 'wpmudev_chat_widget_init_proc');
}