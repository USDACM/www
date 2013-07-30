<?php


/*
 * Custom control class
 *
 * Add description on control
 * */
if ( class_exists('WP_Customize_Control') ) {
class WPMUDEV_Customize_Control extends WP_Customize_Control {

	public $description = '';

	protected function render_content() {
		switch( $this->type ) {
			default:
				return parent::render_content();
			case 'text':
				?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php if ( isset($this->description) && !empty($this->description) ): ?>
					<span class="customize-control-description"><?php echo $this->description ?></span>
					<?php endif ?>
					<input type="text" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
				</label>
				<?php
				break;
			case 'radio':
				if ( empty( $this->choices ) )
					return;

				$name = '_customize-radio-' . $this->id;

				?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if ( isset($this->description) && !empty($this->description) ): ?>
				<span class="customize-control-description"><?php echo $this->description ?></span>
				<?php endif ?>
				<?php
				foreach ( $this->choices as $value => $label ) :
					?>
					<label>
						<input type="radio" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php $this->link(); checked( $this->value(), $value ); ?> />
						<?php echo esc_html( $label ); ?><br/>
					</label>
					<?php
				endforeach;
				break;
			case 'custom-radio':
				if ( empty( $this->choices ) )
					return;

				$name = '_customize-radio-' . $this->id;

				?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if ( isset($this->description) && !empty($this->description) ): ?>
				<span class="customize-control-description"><?php echo $this->description ?></span>
				<?php endif ?>
				<?php
				foreach ( $this->choices as $value => $label ) :
					$screenshot_img = substr($value,0,-4);
					?>
					<label>
						<div class="theme-img">
							<img src="<?php echo get_template_directory_uri(); ?>/_inc/preset-styles/images/<?php echo $screenshot_img . '.png'; ?>" alt="<?php echo $screenshot_img ?>" />
						</div>
						<input type="radio" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php $this->link(); checked( $this->value(), $value ); ?> />
						<?php echo esc_html( $label ); ?><br/>
					</label>
					<?php
				endforeach;
				break;
			case 'select':
				if ( empty( $this->choices ) )
					return;

				?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php if ( isset($this->description) && !empty($this->description) ): ?>
					<span class="customize-control-description"><?php echo $this->description ?></span>
					<?php endif ?>
					<select <?php $this->link(); ?>>
						<?php
						foreach ( $this->choices as $value => $label )
							echo '<option value="' . esc_attr( $value ) . '"' . selected( $this->value(), $value, false ) . '>' . $label . '</option>';
						?>
					</select>
				</label>
				<?php
				break;
			// Handle textarea
			case 'textarea':
				?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php if ( isset($this->description) && !empty($this->description) ): ?>
					<span class="customize-control-description"><?php echo $this->description ?></span>
					<?php endif ?>
					<textarea rows="10" cols="40" <?php $this->link(); ?>><?php echo esc_attr( $this->value() ); ?></textarea>
				</label>
				<?php
				break;
		}
	}

}
}

if ( class_exists('WP_Customize_Color_Control') ) {
class WPMUDEV_Customize_Color_Control extends WP_Customize_Color_Control {

	public $description = '';

	public function render_content() {
		?>
		<label>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( isset($this->description) && !empty($this->description) ): ?>
			<span class="customize-control-description"><?php echo $this->description ?></span>
			<?php endif ?>
			<div class="customize-control-content">
				<div class="dropdown">
					<div class="dropdown-content">
						<div class="dropdown-status"></div>
					</div>
					<div class="dropdown-arrow"></div>
				</div>
				<input class="color-picker-hex" type="text" maxlength="7" placeholder="<?php esc_attr_e('Hex Value'); ?>" />
			</div>
			<div class="farbtastic-placeholder"></div>
		</label>
		<?php
	}
}
}

if ( class_exists('WP_Customize_Image_Control') ) {

class WPMUDEV_Customize_Image_Control extends WP_Customize_Image_Control {

	public $description = '';

	public function render_content() {
		$src = $this->value();
		if ( isset( $this->get_url ) )
			$src = call_user_func( $this->get_url, $src );

		?>
		<div class="customize-image-picker">
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( isset($this->description) && !empty($this->description) ): ?>
			<span class="customize-control-description"><?php echo $this->description ?></span>
			<?php endif ?>

			<div class="customize-control-content">
				<div class="dropdown preview-thumbnail">
					<div class="dropdown-content">
						<?php if ( empty( $src ) ): ?>
							<img style="display:none;" />
						<?php else: ?>
							<img src="<?php echo esc_url( set_url_scheme( $src ) ); ?>" />
						<?php endif; ?>
						<div class="dropdown-status"></div>
					</div>
					<div class="dropdown-arrow"></div>
				</div>
			</div>

			<div class="library">
				<ul>
					<?php foreach ( $this->tabs as $id => $tab ): ?>
						<li data-customize-tab='<?php echo esc_attr( $id ); ?>'>
							<?php echo esc_html( $tab['label'] ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php foreach ( $this->tabs as $id => $tab ): ?>
					<div class="library-content" data-customize-tab='<?php echo esc_attr( $id ); ?>'>
						<?php call_user_func( $tab['callback'] ); ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="actions">
				<a href="#" class="remove"><?php _e( 'Remove Image' ); ?></a>
			</div>
		</div>
		<?php
	}
}
}


function edus_customize_register( $wp_customize ) {
	global $options, $shortname, $shortprefix;
	$options_list = $options;
	$sections = array(
		array(
			'section' => 'intro',
			'title' => __("Intro Settings", TEMPLATE_DOMAIN),
			'priority' => 30
		),
		array(
			'section' => 'header',
			'title' => __("Header Settings", TEMPLATE_DOMAIN),
			'priority' => 31
		), array(
			'section' => 'css',
			'title' => __("CSS Settings", TEMPLATE_DOMAIN),
			'priority' => 32
		), array(
			'section' => 'buddypress',
			'title' => __("BuddyPress Settings", TEMPLATE_DOMAIN),
			'priority' => 33
		), array(
			'section' => 'features',
			'title' => __("Features Settings", TEMPLATE_DOMAIN),
			'priority' => 34
		), array(
			'section' => 'nav',
			'title' => __("Navigation Settings", TEMPLATE_DOMAIN),
			'priority' => 35
		), array(
			'section' => 'netrss',
			'title' => __("RSS Tabs Settings", TEMPLATE_DOMAIN),
			'priority' => 36
		)
	);
	// Add sections
	foreach ( $sections as $section ) {
		$wp_customize->add_section( $shortname . $shortprefix . $section['section'], array(
			'title' => $section['title'],
			'priority' => $section['priority']
		) );
	}
	// Add settings and controls
	$opt_section = '';
	foreach ( $options_list as $o => $option ) {
		if ( ! edus_option_in_customize($option) )
			continue;
		$transport = 'postMessage';
		$wp_customize->add_setting( $option['id'], array(
			'default' => ( isset($option['std']) ? $option['std'] : '' ),
			'type' => 'option',
			'capability' => 'edit_theme_options',
			'transport' => $transport
		) );
		$control_param = array(
			'label' => strip_tags($option['name']),
			'description' => ( isset($option['description']) && !empty($option['description']) ? $option['description'] : '' ),
			'section' => $shortname . $shortprefix . $option['inblock'],
			'settings' => $option['id'],
			'priority' => $o // make sure we have the same order as theme options :)
		);
		if ( $option['type'] == 'color' || $option['type'] == 'colorpicker' ) {
			$wp_customize->add_control( new WPMUDEV_Customize_Color_Control( $wp_customize, $option['id'].'_control', $control_param ) );
		}
		else if ( $option['type'] == 'image' ) {
			$control_param['type'] = $option['type'];
			$wp_customize->add_control( new WPMUDEV_Customize_Image_Control( $wp_customize, $option['id'].'_control', $control_param) );
		}
		else if ( $option['type'] == 'text' || $option['type'] == 'textarea' ) {
			$control_param['type'] = $option['type'];
			$wp_customize->add_control( new WPMUDEV_Customize_Control( $wp_customize, $option['id'].'_control', $control_param) );
		}
		else if ( $option['type'] == 'custom-radio' ) {
			$control_param['type'] ='custom-radio';
			// @TODO choices might get removed in future
			$choices = array();
			foreach ( $option['options'] as $choice )
				$choices[$choice] = $choice;
			$control_param['choices'] = $choices;
			$wp_customize->add_control( new WPMUDEV_Customize_Control( $wp_customize, $option['id'].'_control', $control_param) );
		}
		else if ( $option['type'] == 'select' || $option['type'] == 'select-preview' ) {
			$control_param['type'] = 'select';
			// @TODO choices might get removed in future
			$choices = array();
			foreach ( $option['options'] as $choice )
				$choices[$choice] = $choice;
			$control_param['choices'] = $choices;
			$wp_customize->add_control( new WPMUDEV_Customize_Control( $wp_customize, $option['id'].'_control', $control_param) );
		}
	}

	// Support Wordpress custom background
	$wp_customize->get_setting('background_color')->transport = 'postMessage';
	$wp_customize->get_setting('background_image')->transport = 'postMessage';
	$wp_customize->get_setting('background_repeat')->transport = 'postMessage';
	$wp_customize->get_setting('background_position_x')->transport = 'postMessage';
	$wp_customize->get_setting('background_attachment')->transport = 'postMessage';
	$wp_customize->get_setting('header_image')->transport = 'postMessage';
	$wp_customize->get_setting('blogname')->transport = 'postMessage';
	$wp_customize->get_setting('blogdescription')->transport = 'postMessage';
}
add_action('customize_register', 'edus_customize_register');

function edus_customize_preview() {
	global $options, $shortname, $shortprefix;
	?>
	<div id="theme-customizer-css"></div>

	<script type="text/javascript">
		var theme_prefix = "<?php echo $shortname . $shortprefix ?>";
	</script>
	<?php
}

function edus_customize_init() {
	add_action('wp_footer', 'edus_customize_preview', 21);
	wp_enqueue_script( 'edus-theme-customizer', get_template_directory_uri() . '/_inc/js/theme-customizer.js', array( 'customize-preview' ) );
	wp_enqueue_script( 'edus-theme-customizer-options', get_template_directory_uri() . '/_inc/js/theme-customizer-options.js', array( 'customize-preview' ) );
}
add_action( 'customize_preview_init', 'edus_customize_init' );

// Add additional styling to better fit on Customizer options
function  edus_customize_controls_footer() {
	global $options, $shortname, $shortprefix;
	?>
	<style type="text/css">
		.customize-control-title { line-height: 18px; padding: 2px 0; }
		.customize-control-description { font-size: 11px; color: #666; margin: 0 0 3px; display: block; }
		.customize-control input[type="text"], .customize-control textarea { width: 98%; line-height: 18px; margin: 0; }
	</style>
	<?php
}
add_action('customize_controls_print_footer_scripts', 'edus_customize_controls_footer');



function edus_option_in_customize( $option ){
	global $shortname, $shortprefix, $bp_existed;
	$ids = array(
		$shortname . $shortprefix  . "span_meta_color",
		$shortname . $shortprefix  . "span_meta_border_color",
		$shortname . $shortprefix  . "span_meta_text_color",
		$shortname . $shortprefix  . "span_meta_hover_color",
		$shortname . $shortprefix  . "span_meta_border_hover_color",
		$shortname . $shortprefix  . "span_meta_text_hover_color",
		$shortname . $shortprefix  . "nav_bg_color",
		$shortname . $shortprefix  . "nav_text_color",
		$shortname . $shortprefix  . "nav_hover_bg_color",
		$shortname . $shortprefix  . "nav_hover_border_color",
		$shortname . $shortprefix  . "nav_hover_text_color",
		$shortname . $shortprefix  . "top_header_bg_colour",
		$shortname . $shortprefix  . "top_header_bg_image",
		$shortname . $shortprefix  . "top_header_text_colour",
		$shortname . $shortprefix  . "top_header_text_link_colour",
		$shortname . $shortprefix  . "top_header_text_link_hover_colour",
		//$shortname . $shortprefix  . "header_text",
		//$shortname . $shortprefix  . "header_logged_text",
		//$shortname . $shortprefix  . "header_listing",
		$shortname . $shortprefix  . "pri_bg_colour",
		$shortname . $shortprefix  . "pri_bg_border_colour",
		$shortname . $shortprefix  . "pri_text_colour",
		$shortname . $shortprefix  . "body_font",
		$shortname . $shortprefix  . "headline_font",
		$shortname . $shortprefix  . "font_size",
		$shortname . $shortprefix  . "link_colour",
		$shortname . $shortprefix  . "feat_header_title",
		$shortname . $shortprefix  . "tab_bg_colour",
		$shortname . $shortprefix  . "tab_border_colour",
		$shortname . $shortprefix  . "tab_text_colour",
		$shortname . $shortprefix  . "tab_link_colour",
	);
	if ( $option['inblock'] == 'buddypress' && $bp_existed == 'false' )
		return false;
	if ( in_array( $option['id'], $ids ) )
		return true;
	return false;
}

?>