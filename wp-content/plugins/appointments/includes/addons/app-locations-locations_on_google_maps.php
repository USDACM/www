<?php
/*
Plugin Name: Locations on Google Maps
Description: Allows you to bind locations to your services.
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.0
AddonType: Locations
Requires: Google Maps plugin, Locations add-on
Author: Ve Bailovity (Incsub)
*/

class App_GoogleMaps_MyAppointmentsShortcode extends App_Shortcode {

	public function __construct () {
		$this->_defaults = array(
			'status' => array(
				'value' => 'paid,confirmed',
				'help' => __('Show Appointments with this status (comma-separated list)', 'appointments'),
				'allowed_values' => array('paid', 'confirmed', 'service'),
				'example' => 'paid,confirmed',
			),
			'user_id' => array(
				'value' => false,
				'help' => __('Show Appointments for this user ID', 'appointments'),
				'example' => '32',
			),
		);
	}

	public function process_shortcode ($args=array(), $content='') {
		if (!class_exists('AgmMarkerReplacer')) return false;

		$args = wp_parse_args($args, $this->_defaults_to_args());
		$status = $user = '';

		$raw_status = $this->_arg_to_string_list($args['status']);
		if ($raw_status) {
			$status = "status IN('" . join("','", $raw_status) . "')";
		}

		if (empty($args['user_id'])) {
			if (is_user_logged_in()) {
				$user = wp_get_current_user();
				$user = (!empty($status) ? 'AND ' : '') . "user=" . (int)$user->ID;
			} else {
				$apps = !empty($_COOKIE["wpmudev_appointments"])
					? unserialize(stripslashes($_COOKIE["wpmudev_appointments"]))
					: array()
				;
				$user = !empty($apps)
					? (!empty($status) ? 'AND ' : '') . 'ID in (' . join(',', $apps) . ')'
					: ''
				;
			}
		} else {
			$user = (!empty($status) ? 'AND ' : '') . "user=" . $this->_arg_to_int($args['user_id']);
		}

		if (!$user) return $content;

		global $wpdb, $appointments;
		$sql = "SELECT DISTINCT location FROM {$appointments->app_table} WHERE {$status} {$user}";
		$locations = $wpdb->get_col($sql);
		if (!$locations) return $content;

		$maps = array();
		$_locations = App_Locations_Model::get_instance();
		foreach ($locations as $loc_id) {
			$location = $_locations->find_by('id', $loc_id);
			if (!$location) continue;
			$map = $location->to_map();
			if (!$map) continue;
			$maps[] = $map;
		}
		if (empty($maps)) return $content;

		$codec = new AgmMarkerReplacer;
		$overrides = array();
		$overrides['show_images'] = !empty($overrides['show_images']) ? $overrides['show_images'] : 0;
		return $codec->create_overlay_tag($maps, $overrides);
	}

	public function get_usage_info () {
		return __('Renders a map of appointments', 'appointments');
	}
}

class App_Locations_GoogleMaps {

	private $_data;
	private $_locations;

	private function __construct () {}

	public static function serve () {
		$me = new App_Locations_GoogleMaps;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		// Init and dispatch post-init actions
		add_action('plugins_loaded', array($this, 'initialize'));

		// Map model class to our own
		add_filter('app-locations-location-model_instance_class', array($this, 'get_model_class_name'));

		// Add settings
		add_action('app-locations-settings-after_locations_list', array($this, 'show_settings'));
		add_filter('app-options-before_save', array($this, 'save_settings'));

		add_action('admin_notices', array($this, 'show_nags'));

		// Register shortcode
		add_filter('app-shortcodes-register', array($this, 'register_shortcode'));
	}

	public function register_shortcode ($instances) {
		$instances['app_my_appointments_map'] = 'App_GoogleMaps_MyAppointmentsShortcode';
		return $instances;
	}

	function show_nags () {
		if (!class_exists('App_Locations_Model') || !$this->_locations || !class_exists('App_Locations_LocationsWorker')) {
			echo '<div class="error"><p>' .
				__("You'll need Locations add-on activated for Google Maps Locations integration add-on to work", 'appointments') .
			'</p></div>';
		}
		if (!class_exists('AgmMapModel')) {
			echo '<div class="error"><p>' .
				__("You'll need Google Maps plugin installed and activated for Google Maps Locations integration add-on to work", 'appointments') .
			'</p></div>';
		}
	}

	public function get_model_class_name () { return 'App_Locations_MappedLocation'; }

	public function save_settings ($options) {
		if (empty($_POST['google_maps'])) return $options;

		$data = stripslashes_deep($_POST['google_maps']);
		$options['google_maps'] = !empty($data) ? $data : array();
		$options['google_maps']['overrides'] = !empty($data['overrides']) ? array_filter($data['overrides']) : array();

		return $options;
	}

		public function show_settings () {
			$map_types = array(
				'ROADMAP' => __('ROADMAP', 'agm_google_maps'),
				'SATELLITE' => __('SATELLITE', 'agm_google_maps'),
				'HYBRID' => __('HYBRID', 'agm_google_maps'),
				'TERRAIN' => __('TERRAIN', 'agm_google_maps'),
			);
			$map_units = array(
				'METRIC' => __('Metric', 'agm_google_maps'),
				'IMPERIAL' => __('Imperial', 'agm_google_maps'),
			);
		?>
<div class="postbox">
	<h3 class='hndle'><span><?php _e('Google Maps Settings', 'appointments') ?></span></h3>
	<div class="inside">
		<p><em><?php _e('Any setting you leave empty here will be inherited from the default Google Maps plugin settings.', 'appointments'); ?></em></p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Map size', 'appointments')?></th>
				<td>
					<label for="app-google_maps-width">
						<?php _e('Width:', 'appointments'); ?>
						<input type="text" size="4" id="app-google_maps-width" name="google_maps[overrides][width]" value="<?php esc_attr_e(@$this->_data['google_maps']['overrides']['width']); ?>" /><em class="app-inline_help">px</em>
					</label>
					<span class="app-hspacer">&times;</span>
					<label for="app-google_maps-height">
						<?php _e('Height:', 'appointments'); ?>
						<input type="text" size="4" id="app-google_maps-height" name="google_maps[overrides][height]" value="<?php esc_attr_e(@$this->_data['google_maps']['overrides']['height']); ?>" /><em class="app-inline_help">px</em>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Map appearance', 'appointments')?></th>
				<td>
					<label for="app-google_maps-zoom">
						<?php _e('Zoom:', 'appointments'); ?>
						<input type="text" size="4" id="app-google_maps-zoom" name="google_maps[overrides][zoom]" value="<?php esc_attr_e(@$this->_data['google_maps']['overrides']['zoom']); ?>" />
						<em class="app-inline_help"><?php _e('Numeric value', 'appointments'); ?></em>
					</label>
					<br />
					<label for="app-google_maps-type">
						<?php _e('Type:', 'appointments'); ?>
						<select name="google_maps[overrides][map_type]">
							<option value=""></option>
						<?php foreach ($map_types as $type => $label) { ?>
							<option value="<?php esc_attr_e($type); ?>"
								<?php selected(@$this->_data['google_maps']['overrides']['map_type'], $type); ?>
							><?php echo $label; ?></option>
						<?php } ?>
						</select>
					</label>
					<br />
					<label for="app-google_maps-units">
						<?php _e('Units:', 'appointments'); ?>
						<select name="google_maps[overrides][units]">
							<option value=""></option>
						<?php foreach ($map_units as $units => $label) { ?>
							<option value="<?php esc_attr_e($units); ?>"
								<?php selected(@$this->_data['google_maps']['overrides']['units'], $units); ?>
							><?php echo $label; ?></option>
						<?php } ?>
						</select>
					</label>
					<br />
					<label for="app-google_maps-show_images">
						<input type="hidden" name="google_maps[overrides][show_images]" value="" />
						<input type="checkbox" id="app-google_maps-show_images" name="google_maps[overrides][show_images]" value="1" <?php checked(1, @$this->_data['google_maps']['overrides']['show_images']); ?> />
						<?php _e('Show images', 'appointments'); ?>
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Automatic map overlays', 'appointments')?></th>
				<td>
					<p><?php _e('Automatically insert maps...', 'appointments'); ?></p>
					<label for="app-google_maps-my_appointments">
						<input type="hidden" name="google_maps[my_appointments]" value="" />
						<input type="checkbox" id="app-google_maps-my_appointments" name="google_maps[my_appointments]" value="1" <?php checked(1, $this->_data['google_maps']['my_appointments']); ?> />
						<?php _e('After My Appointments shortcode output', 'appointments'); ?>
					</label>
					<br />
					<label for="app-google_maps-all_appointments">
						<input type="hidden" name="google_maps[all_appointments]" value="" />
						<input type="checkbox" id="app-google_maps-all_appointments" name="google_maps[all_appointments]" value="1" <?php checked(1, $this->_data['google_maps']['all_appointments']); ?> />
						<?php _e('After All Appointments shortcode output', 'appointments'); ?>
					</label>
				</td>
			</tr>
		</table>
	</div>
</div>
		<?php
	}


	public function initialize () {
		global $appointments;
		$this->_data = $appointments->options;

		if (!class_exists('App_Locations_Model')) require_once(dirname(__FILE__) . '/lib/app_locations.php');
		$this->_locations = App_Locations_Model::get_instance();

		if (!empty($this->_data['google_maps']['my_appointments'])) {
			add_filter('app_my_appointments_after_table', array($this, 'add_joint_my_appointments_map'), 10, 2);
		}
		if (!empty($this->_data['google_maps']['all_appointments'])) {
			add_filter('app_all_appointments_after_table', array($this, 'add_joint_my_appointments_map'), 10, 2);
		}
	}

	public function add_joint_my_appointments_map ($out, $my_appointments) {
		if (!class_exists('AgmMarkerReplacer')) return $out;
		$maps = array();
		foreach ($my_appointments as $app) {
			if (empty($app->location)) continue;
			$location = $this->_locations->find_by('id', $app->location);
			$map = $location->to_map();
			if (!$map) continue;
			$maps[] = $map;
		}
		if (empty($maps)) return $out;

		$codec = new AgmMarkerReplacer;
		$overrides = !empty($this->_data['google_maps']['overrides']) ? $this->_data['google_maps']['overrides'] : array();
		$overrides['show_images'] = !empty($overrides['show_images']) ? $overrides['show_images'] : 0;
		$out .= $codec->create_overlay_tag($maps, $overrides);
		return $out;
	}
}
App_Locations_GoogleMaps::serve();