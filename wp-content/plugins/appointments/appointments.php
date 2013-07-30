<?php
/*
Plugin Name: Appointments+
Description: Lets you accept appointments from front end and manage or create them from admin side
Plugin URI: http://premium.wpmudev.org/project/appointments-plus/
Version: 1.3
Author: Hakan Evin <hakan@incsub.com>
Author URI: http://premium.wpmudev.org/
Textdomain: appointments
WDP ID: 679841
*/

/*
Copyright 2007-2013 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( !class_exists( 'Appointments' ) ) {

class Appointments {

	var $version = "1.3";

	/**
     * Constructor
     */
	function Appointments() {
		$this->__construct();
	}
	function __construct() {

		$this->plugin_dir = WP_PLUGIN_DIR . '/appointments';
		$this->plugin_url = plugins_url( 'appointments' );

		// Read all options at once
		$this->options = get_option( 'appointments_options' );

		// To follow WP Start of week, time, date settings
		$this->local_time			= current_time('timestamp');
		if ( !$this->start_of_week	= get_option('start_of_week') )
			$this->start_of_week	= 0;
		$this->time_format			= get_option('time_format');
		$this->date_format			= get_option('date_format');
		$this->datetime_format		= $this->date_format . " " . $this->time_format;

		add_action( 'wpmu_new_blog', array( &$this, 'new_blog' ), 10, 6);	// Install database tables for a new blog
		add_action( 'delete_blog', array( &$this, 'delete_blog' ), 10, 2);	// Uninstall tables for a deleted blog
		add_action( 'delete_user', array( &$this, 'delete_user' ) );		// Modify database in case a user is deleted
		add_action( 'wpmu_delete_user', array( &$this, 'delete_user' ) );	// Same as above
		add_action( 'remove_user_from_blog', array( &$this, 'remove_user_from_blog' ), 10, 2 );	// Remove his records only for that blog

		add_action( 'plugins_loaded', array(&$this, 'localization') );		// Localize the plugin
		add_action( 'init', array( &$this, 'init' ) ); 						// Initial stuff
		add_action( 'init', array( &$this, 'cancel' ), 3 ); 				// Check cancellation of an appointment
		add_filter( 'the_posts', array(&$this, 'load_styles') );			// Determine if we use shortcodes on the page
		add_action( 'wp_ajax_nopriv_app_paypal_ipn', array(&$this, 'handle_paypal_return')); // Send Paypal to IPN function

		// Add/edit some fields on the user pages
		add_action( 'show_user_profile', array(&$this, 'show_profile') );
		add_action( 'edit_user_profile', array(&$this, 'show_profile') );
		add_action( 'personal_options_update', array(&$this, 'save_profile') );
		add_action( 'edit_user_profile_update', array(&$this, 'save_profile') );

		// Admin hooks
		add_action( 'admin_menu', array( &$this, 'admin_init' ) ); 						// Creates admin settings window
		add_action( 'admin_notices', array( &$this, 'admin_notices' ) ); 				// Warns admin
		add_action( 'admin_print_scripts', array(&$this, 'admin_scripts') );			// Load scripts
		add_action( 'admin_print_styles', array(&$this, 'admin_css') );					// Add style to all admin pages
		add_action( 'admin_print_styles-appointments_page_app_settings', array( &$this, 'admin_css_settings' ) ); // Add style to settings page
		add_action( 'right_now_content_table_end', array($this, 'add_app_counts') );	// Add app counts
		add_action( 'wp_ajax_delete_log', array( &$this, 'delete_log' ) ); 				// Clear log
		add_action( 'wp_ajax_inline_edit', array( &$this, 'inline_edit' ) ); 			// Add/edit appointments
		add_action( 'wp_ajax_inline_edit_save', array( &$this, 'inline_edit_save' ) ); 	// Save edits
		add_action( 'wp_ajax_js_error', array( &$this, 'js_error' ) ); 					// Track js errors
		add_action( 'wp_ajax_app_export', array( &$this, 'export' ) ); 					// Export apps

		// Shortcodes
		//add_shortcode( 'app_all_appointments', array(&$this,'all_appointments') );		// New in V1.2.7
		//add_shortcode( 'app_my_appointments', array(&$this,'my_appointments') );
		//add_shortcode( 'app_services', array(&$this,'services') );
		//add_shortcode( 'app_service_providers', array(&$this,'service_providers') );
		//add_shortcode( 'app_schedule', array(&$this,'weekly_calendar') );
		//add_shortcode( 'app_monthly_schedule', array(&$this,'monthly_calendar') );
		//add_shortcode( 'app_pagination', array(&$this,'pagination') );
		//add_shortcode( 'app_confirmation', array(&$this,'confirmation') );
		//add_shortcode( 'app_login', array(&$this,'login') );
		//add_shortcode( 'app_paypal', array(&$this,'paypal') );

		// Front end ajax hooks
		add_action( 'wp_ajax_pre_confirmation', array( &$this, 'pre_confirmation' ) ); 			// Get pre_confirmation results
		add_action( 'wp_ajax_nopriv_pre_confirmation', array( &$this, 'pre_confirmation' ) ); 	// Get pre_confirmation results
		add_action( 'wp_ajax_post_confirmation', array( &$this, 'post_confirmation' ) ); 		// Do after final confirmation
		add_action( 'wp_ajax_nopriv_post_confirmation', array( &$this, 'post_confirmation' ) ); // Do after final confirmation

		add_action( 'wp_ajax_cancel_app', array( &$this, 'cancel' ) ); 							// Cancel appointment from my appointments
		add_action( 'wp_ajax_nopriv_cancel_app', array( &$this, 'cancel' ) ); 					// Cancel appointment from my appointments

		// API login after the options have been initialized
		add_action('init', array($this, 'setup_api_logins'));

		// Widgets
		require_once( $this->plugin_dir . '/includes/widgets.php' );
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

		// Buddypress
		add_action( 'bp_setup_nav', array( &$this, 'setup_nav'), 20 );
		add_action( 'bp_init', array( &$this, 'bp_init') );
		add_action( 'bp_init', array( &$this, 'cancel' ), 3 ); 						// Check cancellation of an appointment
		add_action( 'template_redirect', array( &$this, 'bp_template_redirect') ); 	// bp_template_redirect is not working
		add_action( 'wp_footer', array( &$this, 'bp_footer' ) ); 					// The same action as wp_footer

		// Caching
		if ( 'yes' == @$this->options['use_cache'] ) {
			add_filter( 'the_content', array( &$this, 'pre_content' ), 8 );				// Check content before do_shortcode
			add_filter( 'the_content', array( &$this, 'post_content' ), 100 );			// Serve this later than do_shortcode
			add_action( 'wp_footer', array( &$this, 'save_script' ), 8 );				// Save script to database
			add_action( 'permalink_structure_changed', array( &$this, 'flush_cache' ) );// Clear cache in case permalink changed
			add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 ); 			// Clear cache if it has shortcodes
		}
		$this->pages_to_be_cached = array();
		$this->had_filter = false; // There can be a wpautop filter. We will check this later on.

		// Membership integration
		$this->membership_active = false;
		add_action( 'plugins_loaded', array( &$this, 'check_membership_plugin') );

		// Marketpress integration
		$this->marketpress_active = $this->mp = false;
		$this->mp_posts = array();
		add_action( 'plugins_loaded', array( &$this, 'check_marketpress_plugin') );

		$this->gcal_api = false;
		add_action('init', array($this, 'setup_gcal_sync'));

		// Database variables
		global $wpdb;
		$this->db 					= &$wpdb;
		$this->wh_table 			= $wpdb->prefix . "app_working_hours";
		$this->exceptions_table 	= $wpdb->prefix . "app_exceptions";
		$this->services_table 		= $wpdb->prefix . "app_services";
		$this->workers_table 		= $wpdb->prefix . "app_workers";
		$this->app_table 			= $wpdb->prefix . "app_appointments";
		$this->transaction_table 	= $wpdb->prefix . "app_transactions";
		$this->cache_table 			= $wpdb->prefix . "app_cache";
		// DB version
		$this->db_version 			= get_option( 'app_db_version' );

		// Set log file location
		$uploads = wp_upload_dir();
		if ( isset( $uploads["basedir"] ) )
			$this->uploads_dir 	= $uploads["basedir"] . "/";
		else
			$this->uploads_dir 	= WP_CONTENT_DIR . "/uploads/";
		$this->log_file 		= $this->uploads_dir . "appointments-log.txt";

		// Other default settings
		$this->script = $this->bp_script = $this->uri = $this->error_url = '';
		$this->location = $this->service = $this->worker = 0;
		$this->gcal_image = '<img src="' . $this->plugin_url . '/images/gc_button1.gif" />';
		$this->locale_error = $this->bp = false;

		// Create a salt, if it doesn't exist from the previous installation
		if ( !$salt = get_option( "appointments_salt" ) ) {
			$salt = mt_rand();
			add_option( "appointments_salt", $salt ); // Save it to be used until it is cleared manually
		}
		$this->salt = $salt;
	}

	function setup_gcal_sync () {
		// GCal Integration
		$this->gcal_api = false;
		// Allow forced disabling in case of emergency
		if ( !defined( 'APP_GCAL_DISABLE' ) ) {
			require_once $this->plugin_dir . '/includes/class.gcal.php';
			$this->gcal_api = new AppointmentsGcal();
		}
	}

	function setup_api_logins () {
		if (!@$this->options['accept_api_logins']) return false;

		add_action('wp_ajax_nopriv_app_facebook_login', array($this, 'handle_facebook_login'));
		add_action('wp_ajax_nopriv_app_get_twitter_auth_url', array($this, 'handle_get_twitter_auth_url'));
		add_action('wp_ajax_nopriv_app_twitter_login', array($this, 'handle_twitter_login'));
		add_action('wp_ajax_nopriv_app_ajax_login', array($this, 'ajax_login'));

		// Google+ login
		if (!class_exists('LightOpenID')) {
			if( function_exists('curl_init') || in_array('https', stream_get_wrappers()) ) {
				include_once( $this->plugin_dir . '/includes/lightopenid/openid.php' );
				$this->openid = new LightOpenID;
			}
		}
		else
			$this->openid = new LightOpenID;

		if ( @$this->openid ) {

			if ( !session_id() )
				@session_start();

			add_action('wp_ajax_nopriv_app_get_google_auth_url', array($this, 'handle_get_google_auth_url'));
			add_action('wp_ajax_nopriv_app_google_login', array($this, 'handle_google_login'));

			$this->openid->identity = 'https://www.google.com/accounts/o8/id';
			$this->openid->required = array('namePerson/first', 'namePerson/last', 'namePerson/friendly', 'contact/email');
			if (!empty($_REQUEST['openid_ns'])) {
				$cache = $this->openid->getAttributes();
				if (isset($cache['namePerson/first']) || isset($cache['namePerson/last']) || isset($cache['contact/email'])) {
					$_SESSION['app_google_user_cache'] = $cache;
				}
			}
			if ( isset( $_SESSION['app_google_user_cache'] ) )
				$this->_google_user_cache = $_SESSION['app_google_user_cache'];
			else
				$this->_google_user_cache = '';
		}
	}

/**
***************************************************************************************************************
* Methods for optimization
*
* $l: location ID - For future use
* $s: service ID
* $w: worker ID
* $stat: Status (open: working or closed: not working)
* IMPORTANT: This plugin is NOT intended for hundreds of services or service providers,
*  but it is intended to make database queries as cheap as possible with smaller number of services/providers.
*  If you have lots of services and/or providers, codes will not scale and appointments pages will be VERY slow.
*  If you need such an application, override some of the methods below with a child class.
***************************************************************************************************************
*/

	/**
	 * Get location, service, worker
	 */
	function get_lsw() {
		$this->location = $this->get_location_id();
		$this->service = $this->get_service_id();
		$this->worker = $this->get_worker_id();
	}

	/**
	 * Get location ID for future use
	 */
	function get_location_id() {
		if ( isset( $_REQUEST["app_location_id"] ) )
			return (int)$_REQUEST["app_location_id"];

		return 0;
	}

	/**
	 * Get smallest service ID
	 * We assume total number of services is not too high, which is the practical case.
	 * Otherwise this method might be expensive
	 * @return integer
	 */
	function get_first_service_id() {
		$min = wp_cache_get( 'min_service_id' );
		if ( false === $min ) {
			$services = $this->get_services();
			if ( $services ) {
				$min = 9999999;
				foreach ( $services as $service ) {
					if ( $service->ID < $min )
						$min = $service->ID;
				}
				wp_cache_set( 'min_service_id', $min );
			}
			else
				$min = 0; // No services ?? - Not possible but let's be safe
		}
		return $min;
	}

	/**
	 * Get service ID from front end
	 * @return integer
	 */
	function get_service_id() {
		if ( isset( $_REQUEST["app_service_id"] ) )
			return (int)$_REQUEST["app_service_id"];
		else if ( !$service_id = $this->get_first_service_id() )
			$service_id = 0;

		return $service_id;
	}

	/**
	 * Get worker ID from front end
	 * worker = provider
	 * @return integer
	 */
	function get_worker_id() {
		if ( isset( $_REQUEST["app_provider_id"] ) )
			return (int)$_REQUEST["app_provider_id"];

		if ( isset( $_REQUEST["app_worker_id"] ) )
			return (int)$_REQUEST["app_worker_id"];

		return 0;
	}

	/**
	 * Get all services
	 * @param order_by: ORDER BY clause for mysql
	 * @return array of objects
	 */
	function get_services( $order_by="ID" ) {
		$order_by = $this->sanitize_order_by( $order_by );
		$services = wp_cache_get( 'all_services_' . $order_by );
		if ( false === $services ) {
			$services = $this->db->get_results("SELECT * FROM " . $this->services_table . " ORDER BY ". $order_by ." " );
			wp_cache_set( 'all_services_' . $order_by, $services );
		}
		return $services;
	}

	 /**
	 * Allow only certain order_by clauses
	 * @since 1.2.8
	 */
	function sanitize_order_by( $order_by="ID" ) {
		$whitelist = apply_filters( 'app_order_by_whitelist', array( 'ID', 'name', 'start', 'end', 'duration', 'price',
					'ID DESC', 'name DESC', 'start DESC', 'end DESC', 'duration DESC', 'price DESC', 'RAND()' ) );
		if ( in_array( $order_by, $whitelist ) )
			return $order_by;
		else
			return 'ID';
	}

	/**
	 * Get a single service with given ID
	 * @param ID: Id of the service to be retrieved
	 * @return object
	 */
	function get_service( $ID ) {
		$service = wp_cache_get( 'service_'. $ID );
		if ( false === $service ) {
			$services = $this->get_services();
			if ( $services ) {
				foreach ( $services as $s ) {
					if ( $s->ID == $ID ) {
						$service = $s;
						break;
					}
				}
				wp_cache_set( 'service_'. $ID, $service );
			}
			else
				$service = null;
		}
		return $service;
	}

	/**
	 * Get services given by a certain worker
	 * @param w: ID of the worker
	 * @since 1.2.3
	 * @return array of objects
	 */
	function get_services_by_worker( $w ) {
		$services_by_worker = wp_cache_get( 'services_by_worker_' . $w );
		if ( false === $services_by_worker ) {
			$services_by_worker = array();
			$worker = $this->get_worker( $w );
			if ( $worker && is_object( $worker ) ) {
				$services_provided = $this->_explode( $worker->services_provided );
				asort( $services_provided ); // Sort by service ID from low to high
				foreach( $services_provided as $service_id ) {
					$services_by_worker[] = $this->get_service( $service_id );
				}
			}
			wp_cache_set( 'services_by_worker_' . $w , $services_by_worker );
		}
		return $services_by_worker;
	}

	/**
	 * Get all workers
	 * @param order_by: ORDER BY clause for mysql
	 * @return array of objects
	 */
	function get_workers( $order_by="ID" ) {
		$order_by = $this->sanitize_order_by( $order_by );
		$workers = wp_cache_get( 'all_workers_' . str_replace( ' ', '_', $order_by ) );
		if ( false === $workers ) {
			// Sorting by name requires special case
			if ( stripos( $order_by, 'name' ) !== false ) {
				$workers_ = $this->db->get_results("SELECT * FROM " . $this->workers_table . " " );
				if ( stripos( $order_by, 'desc' ) !== false )
					usort( $workers_, array( &$this, 'get_workers_desc' ) );
				else
					usort( $workers_, array( &$this, 'get_workers_asc' ) );
				$workers = $workers_;
			}
			else
				$workers = $this->db->get_results("SELECT * FROM " . $this->workers_table . " ORDER BY ".$order_by ." " );
			wp_cache_set( 'all_workers_' . str_replace( ' ', '_', $order_by ), $workers );
		}
		return $workers;
	}

	/**
	 * Helper function to sort workers in ascending order
	 * @since 1.1.9
	 * @return integer
	 */
	function get_workers_asc( $a, $b ) {
		return strcmp( $this->get_worker_name( $a->ID ), $this->get_worker_name( $b->ID ) );
	}

	/**
	 * Helper function to sort workers in descending order
	 * @since 1.1.9
	 * @return integer
	 */
	function get_workers_desc( $a, $b ) {
		return strcmp( $this->get_worker_name( $b->ID ), $this->get_worker_name( $a->ID ) );
	}

	/**
	 * Get a single worker with given ID
	 * @param ID: Id of the worker to be retrieved
	 * @return object
	 */
	function get_worker( $ID ) {
		$worker = null;
		$workers = $this->get_workers();
		if ( $workers ) {
			foreach ( $workers as $w ) {
				if ( $w->ID == $ID ) {
					$worker = $w;
					break;
				}
			}
		}
		return $worker;
	}

	/**
	 * Get workers giving a specific service (by its ID)
 	 * We assume total number of workers is not too high, which is the practical case.
	 * Otherwise this method would be expensive
	 * @param ID: Id of the service to be retrieved
	 * @param order_by: ORDER BY clause for mysql
	 * @return array of objects
	 */
	function get_workers_by_service( $ID, $order_by="ID" ) {
		//$order_by = $this->sanitize_order_by( $order_by );
		$workers_by_service = false;
		$workers = $this->get_workers( $order_by );
		if ( $workers ) {
			$workers_by_service = array();
			foreach ( $workers as $worker ) {
				if ( strpos( $worker->services_provided, ':'.$ID.':' ) !== false )
					$workers_by_service[] = $worker;
			}
		}
		return $workers_by_service;
	}

	/**
	 * Check if there is only one worker giving the selected service
	 * @param service: Id of the service for which check will be done
 	 * @since 1.1.1
	 * @return string (worker ID if there is one, otherwise 0)
	 */
	function is_single_worker( $service ) {
		$workers = $this->get_workers_by_service( $service );
		if ( $workers && 1 === count( $workers ) && is_object( $workers[0] ) ) {
			return $workers[0]->ID;
		}
		else return 0;
	}

	/**
	 * Return a row from working hours table, i.e. days/hours we are working or we have break
	 * @param stat: open (works), or closed (breaks).
	 * @return object
	 */
	function get_work_break( $l, $w, $stat ) {
		$work_break = null;
		$work_breaks = wp_cache_get( 'work_breaks_'. $l . '_' . $w );

		if ( false === $work_breaks ) {
			$work_breaks = $this->db->get_results( "SELECT * FROM ". $this->wh_table. " WHERE worker=".$w." AND location=".$l." ");
			wp_cache_set( 'work_breaks_'. $l . '_' . $w, $work_breaks );
		}
		if ( $work_breaks ) {
			foreach ( $work_breaks as $wb ) {
				if ( $wb->status == $stat ) {
					$work_break = $wb;
					break;
				}
			}
		}
		return $work_break;
	}

	/**
	 * Return a row from exceptions table, i.e. days we are working or having holiday
	 * @return object
	 */
	function get_exception( $l, $w, $stat ) {
		$exception = null;
		$exceptions = wp_cache_get( 'exceptions_'. $l . '_' . $w );
		if ( false === $exceptions ) {
			$exceptions = $this->db->get_results( "SELECT * FROM " . $this->exceptions_table . " WHERE worker=".$w." AND location=".$l." " );
			wp_cache_set( 'exceptions_'. $l . '_' . $w, $exceptions );
		}
		if ( $exceptions ) {
			foreach ( $exceptions as $e ) {
				if ( $e->status == $stat ) {
					$exception = $e;
					break;
				}
			}
		}
		return $exception;
	}

	/**
	 * Return an appointment given its ID
	 * @param app_id: ID of the appointment to be retreived from database
	 * @since 1.1.8
	 * @return object
	 */
	function get_app( $app_id ) {
		if ( !$app_id )
			return false;
		$app = wp_cache_get( 'app_'. $app_id );
		if ( false === $app ) {
			$app = $this->db->get_row( "SELECT * FROM " . $this->app_table . " WHERE ID=".$app_id." " );
			wp_cache_set( 'app_'. $app_id, $app );
		}
		return $app;
	}

	/**
	 * Return all reserve appointments (i.e. pending, paid, confirmed or reserved by GCal)
	 * @param week: Optionally appointments only in the number of week in ISO 8601 format (since 1.2.3).
	 * Weekly gives much better results in RAM usage compared to monthly, with a tolerable, slight increase in number of queries
	 * @return array of objects
	 */
	function get_reserve_apps( $l, $s, $w, $week=0 ) {
		$apps = wp_cache_get( 'reserve_apps_'. $l . '_' . $s . '_' . $w . '_' . $week );
		if ( false === $apps ) {
			$location = $l ? "location='" . $this->db->escape($location) . "' AND" : '';
			if ( 0 == $week )
				$apps = $this->db->get_results( "SELECT * FROM " . $this->app_table . "
					WHERE ".$location." service=".$s." AND worker=".$w."
					AND (status='pending' OR status='paid' OR status='confirmed' OR status='reserved') " );
			else
				$apps = $this->db->get_results( "SELECT * FROM " . $this->app_table . "
					WHERE ".$location." service=".$s." AND worker=".$w."
					AND (status='pending' OR status='paid' OR status='confirmed' OR status='reserved') AND WEEKOFYEAR(start)=".$week. " " );
			wp_cache_set( 'reserve_apps_'. $l . '_' . $s . '_' . $w . '_' . $week, $apps );
		}
		return $apps;
	}

	/**
	 * Return all reserve appointments by worker ID
	 * @param week: Optionally appointments only in the number of week in ISO 8601 format (since 1.2.3)
	 * @return array of objects
	 */
	function get_reserve_apps_by_worker( $l, $w, $week=0 ) {
		$apps = wp_cache_get( 'reserve_apps_by_worker_'. $l . '_' . $w . '_' . $week );
		if ( false === $apps ) {
			$services = $this->get_services();
			if ( $services ) {
				$apps = array();
				foreach ( $services as $service ) {
					$apps_worker = $this->get_reserve_apps( $l, $service->ID, $w, $week );
					if ( $apps_worker )
						$apps = array_merge( $apps, $apps_worker );
				}
			}
			wp_cache_set( 'reserve_apps_by_worker_'. $l . '_' . $w . '_' . $week, $apps );
		}
		return $apps;
	}

	/**
	 * Return reserve appointments by service ID
	 * @param week: Optionally appointments only in the number of week in ISO 8601 format (since 1.2.3)
	 * @since 1.1.3
	 * @return array of objects
	 */
	function get_reserve_apps_by_service( $l, $s, $week=0 ) {
		$apps = wp_cache_get( 'reserve_apps_by_service_'. $l . '_' . $s . '_' . $week );
		if ( false === $apps ) {
			$workers = $this->get_workers_by_service( $s );
			$apps = array();
			if ( $workers ) {
				foreach ( $workers as $worker ) {
					$apps_service = $this->get_reserve_apps( $l, $s, $worker->ID, $week );
					if ( $apps_service )
						$apps = array_merge( $apps, $apps_service );
				}
			}
			// Also include appointments by general staff for this service
			$apps_service_0 = $this->get_reserve_apps( $l, $s, 0, $week );
			if ( $apps_service_0 )
				$apps = array_merge( $apps, $apps_service_0 );

			// Remove duplicates
			$apps = $this->array_unique_object_by_ID( $apps );

			wp_cache_set( 'reserve_apps_by_service_'. $l . '_' . $s . '_' . $week, $apps );
		}
		return $apps;
	}

	/**
	 * Find if a user is worker
	 * @param user_id: Id of the user who will be checked if he is worker
	 * @return bool
	 */
	function is_worker( $user_id=0 ) {
		global $wpdb, $current_user;
		if ( !$user_id )
			$user_id = $current_user->ID;

		$result = $this->get_worker( $user_id );
		if ( $result != null )
			return true;

		return false;
	}

	/**
	 * Find if a user is dummy
	 * @param user_id: Id of the user who will be checked if he is dummy
	 * since 1.0.6
	 * @return bool
	 */
	function is_dummy( $user_id=0 ) {
		global $wpdb, $current_user;
		if ( !$user_id )
			$user_id = $current_user->ID;

		// A dummy should be a worker
		$result = $this->get_worker( $user_id );
		if ( $result == null )
			return false;

		// This is only supported after V1.0.6 and if DB is altered
		if ( !$this->db_version )
			return false;

		if ( $result->dummy )
			return true;

		return false;
	}


	/**
	 * Find worker name given his ID
	 * @return string
	 */
	function get_worker_name( $worker=0, $php=true ) {
		global $current_user;
		$user_name = '';
		if ( 0 == $worker ) {
			// Show different text to authorized people
			if ( is_admin() || App_Roles::current_user_can( 'manage_options', App_Roles::CTX_STAFF ) || $this->is_worker( $current_user->ID ) )
				$user_name = __('Our staff', 'appointments');
			else
				$user_name = __('A specialist', 'appointments');
		}
		else {
			$userdata = get_userdata( $worker );
			if (is_object($userdata) && !empty($userdata->app_name)) {
				$user_name = $userdata->app_name;
			}
			if (empty($user_name)) {
				if ( !$php )
					$user_name = $userdata->user_login;
				else
					$user_name = $userdata->display_name;

				if ( !$user_name )
					$user_name = $userdata->first_name . " " . $userdata->last_name;
				if ( "" == trim( $user_name ) )
					$user_name = $userdata->user_login;
			}
		}
		return apply_filters( 'app_get_worker_name', $user_name, $worker );
	}

	/**
	 * Find worker email given his ID
	 * since 1.0.6
	 * @return string
	 */
	function get_worker_email( $worker=0 ) {
		// Real person
		if ( !$this->is_dummy( $worker ) ) {
			$worker_data = get_userdata( $worker );
			if ( $worker_data )
				$worker_email = $worker_data->user_email;
			else
				$worker_email = '';
			return apply_filters( 'app_worker_email', $worker_email, $worker );
		}
		// Dummy
		if ( isset( $this->options['dummy_assigned_to'] ) && $this->options['dummy_assigned_to'] ) {
			$worker_data = get_userdata( $this->options['dummy_assigned_to'] );
			if ( $worker_data )
				$worker_email = $worker_data->user_email;
			else
				$worker_email = '';
			return apply_filters( 'app_dummy_email', $worker_email, $worker );
		}

		// If not set anything, assign to admin
		return $this->get_admin_email( );
	}

	/**
	 * Return admin email
	 * since 1.2.7
	 * @return string
	 */
	function get_admin_email( ) {
		global $current_site;
		$admin_email = get_option('admin_email');
		if ( !$admin_email )
			$admin_email = 'admin@' . $current_site->domain;

		return apply_filters( 'app_get_admin_email', $admin_email );
	}

	/**
	 * Find service name given its ID
	 * @return string
	 */
	function get_service_name( $service=0 ) {
		// Safe text if we delete a service
		$name = __('Not defined', 'appointments');
		$result = $this->get_service( $service );
		if ( $result != null )
			$name = $result->name;

		$name = apply_filters( 'app_get_service_name', $name, $service );

		return stripslashes( $name );
	}

	/**
	 * Find client name given his appointment
	 * @return string
	 */
	function get_client_name( $app_id ) {
		$name = '';
		// This is only used on admin side, so an optimization is not required.
		$result = $this->db->get_row( "SELECT * FROM " . $this->app_table . " WHERE ID=".$app_id." " );
		if ( $result !== null ) {
			// Client can be a user
			if ( $result->user ) {
				$userdata = get_userdata( $result->user );
				if ( $userdata ) {
					$href = function_exists('bp_core_get_user_domain') && (defined('APP_BP_LINK_TO_PROFILE') && APP_BP_LINK_TO_PROFILE)
						? bp_core_get_user_domain($result->user)
						: admin_url("user-edit.php?user_id="). $result->user
					;
					$name = '<a href="' . apply_filters('app_get_client_name-href', $href, $app_id, $result) . '" target="_blank">'. $userdata->user_login . '</a>';
				}
				else
					$name = $result->name;
			}
			else {
				$name = $result->name;
				if ( !$name )
					$name = $result->email;
			}
		}
		return apply_filters( 'app_get_client_name', $name, $app_id, $result );
	}

	/**
	 * Get price for the current service and worker
	 * If worker has additional price (optional), it is added to the service price
	 * @param paypal: If set true, deposit price is calculated
	 * @return string
	 */
	function get_price( $paypal=false ) {
		global $current_user;
		$this->get_lsw();
		$service_obj = $this->get_service( $this->service );
		$worker_obj = $this->get_worker( $this->worker );

		if ( $worker_obj != null && $worker_obj->price )
			$worker_price = $worker_obj->price;
		else
			$worker_price = 0;

		$price = $service_obj->price + $worker_price;

		// Discount
		if ( $this->is_member() && isset( $this->options["members_discount"] ) && $this->options["members_discount"] ) {
			// Special condition: Free for members
			if ( 100 == $this->options["members_discount"] )
				$price = 0;
			else
				$price = number_format( $price * ( 100 - $this->options["members_discount"] )/100, 2 );
		}

		if ( $paypal ) {
			// Deposit
			if ( isset( $this->options["percent_deposit"] ) && $this->options["percent_deposit"] )
				$price = number_format( $price * $this->options["percent_deposit"] / 100, 2 );
			if ( isset( $this->options["fixed_deposit"] ) && $this->options["fixed_deposit"] )
				$price = $this->options["fixed_deposit"];

			// It is possible to ask special amounts to be paid
			return apply_filters( 'app_paypal_amount', $price, $this->service, $this->worker, $current_user->ID );
		}

		return apply_filters( 'app_get_price', $price, $this->service, $this->worker, $current_user->ID );
	}

	/**
	 * Get deposit given price
	 * This is required only for manual pricing
	 * @param price: the full price
	 * @since 1.0.8
	 * @return string
	 */
	function get_deposit( $price ) {

		$deposit = 0;

		if ( !$price )
			return apply_filters( 'app_get_deposit', 0 );

		// Discount
		if ( $this->is_member() && isset( $this->options["members_discount"] ) && $this->options["members_discount"] ) {
			// Special condition: Free for members
			if ( 100 == $this->options["members_discount"] )
				$price = 0;
			else
				$price = number_format( $price * ( 100 - $this->options["members_discount"] )/100, 2 );
		}

		// Deposit
		if ( isset( $this->options["percent_deposit"] ) && $this->options["percent_deposit"] )
			$deposit = number_format( $price * $this->options["percent_deposit"] / 100, 2 );
		if ( isset( $this->options["fixed_deposit"] ) && $this->options["fixed_deposit"] )
			$deposit = $this->options["fixed_deposit"];

		return apply_filters( 'app_get_deposit', $deposit );
	}


	/**
	 * Get the capacity of the current service
	 * @return integer
	 */
	function get_capacity() {
		$capacity = wp_cache_get( 'capacity_'. $this->service );
		if ( false === $capacity ) {
			// If no worker is defined, capacity is always 1
			$count = count( $this->get_workers() );
			if ( !$count ) {
				$capacity = 1;
			}
			else {
				// Else, find number of workers giving that service and capacity of the service
				$worker_count = count( $this->get_workers_by_service( $this->service ) );
				$service = $this->get_service( $this->service );
				if ( $service != null ) {
					if ( !$service->capacity ) {
						$capacity = $worker_count; // No service capacity limit
					}
					else
						$capacity = min( $service->capacity, $worker_count ); // Return whichever smaller
				}
				else
					$capacity = 1; // No service ?? - Not possible but let's be safe
			}
			wp_cache_set( 'capacity_'. $this->service, $capacity );
		}
		return apply_filters( 'app_get_capacity', $capacity, $this->service, $this->worker );
	}

/**
**************************************
* Methods for Specific Content Caching
* Developed especially for this plugin
**************************************
*/

	/**
	 * Check if plugin should use cache
	 * Available for visitors for the moment
	 * TODO: extend this for logged in users too
	 * @since 1.0.2
	 */
	function use_cache() {
		if ( 'yes' == $this->options["use_cache"] && !is_user_logged_in() )
			return true;

		return false;
	}

	/**
	 * Add a post ID to the array to be cached
	 *
	 */
	function add_to_cache( $post_id ) {
		if ( $this->use_cache() )
			$this->pages_to_be_cached[] = $post_id;
	}

	/**
	 * Serve content from cache DB if is available and post is supposed to be cached
	 * This is called before do_shortcode (this method's priority: 8)
	 * @return string (the content)
	 */
	function pre_content( $content ) {
		global $post;
		// Check if this page is to be cached
		if ( !in_array( $post->ID, $this->pages_to_be_cached ) )
			return $content;

		// Get uri and mark it for other functions too
		// The other functions are called after this (content with priority 100 and the other with footer hook)
		$this->uri = $this->get_uri();

		$result = $this->db->get_row( "SELECT * FROM " . $this->cache_table . " WHERE uri= '". $this->uri . "' " );
		if ( $result != null ) {
			// Clear uri so other functions do not deal with update/insert
			$this->uri = false;
			// We need to serve the scripts too
			$this->script = $result->script;

			// If wpautop had filter, it is almost certain that it was removed
			if ( $this->had_filter )
				$new_content = $result->content;
			else
				$new_content = wpautop( $result->content );

			return $new_content . '<!-- Served from WPMU DEV Appointments+ Cache '. $result->created .' -->';
		}
		// If cache is empty return content
		// If wpautop had filter, it is almost certain that it was removed
		if ( $this->had_filter )
			return $content;
		else
			return wpautop( $content ); // Add wpautop which we removed before
	}

	/**
	 * Save newly created content to cache DB
	 * @return string (the content)
	 */
	function post_content( $content ) {
		// Check if this page is to be cached.
		if ( !$this->uri )
			return $content;
		// Also don't save empty content
		if ( !trim( $content ) ) {
			$this->uri = '';
			return $content;
		}
		// At this point it means there is no such a row, so we can safely insert
		$this->db->insert( $this->cache_table,
					array(
						'uri' 		=> $this->uri,
						'created' 	=> date ("Y-m-d H:i:s", $this->local_time ),
						'content'	=> $content
					)
			);
		return $content;
	}

	/**
	 * Save newly created scripts at wp footer location
	 * @return none
	 */
	function save_script() {
		// Check if this page is to be cached
		if ( !$this->uri || !trim( $this->script ) )
			return;
		// There must be already such a row
		$this->db->update( $this->cache_table,
			array( 'script'	=> $this->script ),
			array( 'uri' 	=> $this->uri )
		);
	}

	/**
	 * Get request uri
	 * @return string
	 */
	function get_uri() {
		// Get rid of # part
		if ( strpos( $_SERVER['REQUEST_URI'], '#' ) !== false ) {
			$uri_arr = explode( '#', $_SERVER['REQUEST_URI'] );
			$uri = $uri_arr[0];
		}
		else
			$uri = $_SERVER['REQUEST_URI'];

		return $uri;
	}

	/**
	 * Clear cache in case saved post has our shortcodes
	 * @return none
	 */
	function save_post( $post_id, $post ) {
		if ( strpos( $post->post_content, '[app_' ) !== false )
			$this->flush_cache();
	}

	/**
	 * Flush both database and object caches
	 *
	 */
	function flush_cache( ) {
		wp_cache_flush();
		if ( 'yes' == @$this->options["use_cache"] )
			$result = $this->db->query( "TRUNCATE TABLE " . $this->cache_table . " " );
	}

/****************
* General methods
*****************
*/

	/**
     * Provide options if asked outside the class
 	 * @return array
     */
	function get_options() {
		return $this->options;
	}

	/**
	 * Save a message to the log file
	 */
	function log( $message='' ) {
		if ( $message ) {
			$to_put = '<b>['. date_i18n( $this->datetime_format, $this->local_time ) .']</b> '. $message;
			// Prevent multiple messages with same text and same timestamp
			if ( !file_exists( $this->log_file ) || strpos( @file_get_contents( $this->log_file ), $to_put ) === false )
				@file_put_contents( $this->log_file, $to_put . chr(10). chr(13), FILE_APPEND );
		}
	}

	/**
	 * Remove tabs and breaks
	 */
	function esc_rn( $text ) {
		$text = str_replace( array("\t","\n","\r"), "", $text );
		return $text;
	}

	/**
	 * Converts number of seconds to hours:mins acc to the WP time format setting
	 * @return string
	 */
	function secs2hours( $secs ) {
		$min = (int)($secs / 60);
		$hours = "00";
		if ( $min < 60 )
			$hours_min = $hours . ":" . $min;
		else {
			$hours = (int)($min / 60);
			if ( $hours < 10 )
				$hours = "0" . $hours;
			$mins = $min - $hours * 60;
			if ( $mins < 10 )
				$mins = "0" . $mins;
			$hours_min = $hours . ":" . $mins;
		}
		if ( $this->time_format )
			$hours_min = date( $this->time_format, strtotime( $hours_min . ":00" ) );

		return $hours_min;
	}

	/**
	 * Return an array of preset base times, so that strange values are not set
	 * @return array
	 */
	function time_base() {
		$default = array( 10,15,30,60,90,120 );
		$a = $this->options["additional_min_time"];
		// Additional time bases
		if ( isset( $a ) && $a && is_numeric( $a ) )
			$default[] = $a;
		return apply_filters( 'app_time_base', $default );
	}

	/**
	 *	Return minimum set interval time
	 *  If not set, return a safe time.
	 *	@return integer
	 */
	function get_min_time(){
		if ( isset( $this->options["min_time"] ) && $this->options["min_time"] && $this->options["min_time"]>apply_filters( 'app_safe_min_time', 9 ) )
			return (int)$this->options["min_time"];
		else
			return apply_filters( 'app_safe_time', 10 );
	}

	/**
	 *	Number of days that an appointment can be taken
	 *	@return integer
	 */
	function get_app_limit() {
		if ( isset( $this->options["app_limit"] ) && $this->options["app_limit"] )
			return apply_filters( 'app_limit', (int)$this->options["app_limit"] );
		else
			return apply_filters( 'app_limit', 365 );
	}

	/**
	 * Return an array of weekdays
	 * @return array
	 */
	function weekdays() {
		return array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	}

	/**
	 * Return all available statuses
	 * @return array
	 */
	function get_statuses() {
		return apply_filters( 'app_statuses',
					array(
						'pending'	=> __('Pending', 'appointments'),
						'paid'		=> __('Paid', 'appointments'),
						'confirmed'	=> __('Confirmed', 'appointments'),
						'completed'	=> __('Completed', 'appointments'),
						'reserved'	=> __('Reserved by GCal', 'appointments'),
						'removed'	=> __('Removed', 'appointments')
						)
				);
	}


	/**
	 * Return a selected field name to further customize them and make translation easier
	 * @return string (name of the field)
	 */
	function get_field_name( $key ) {

		$field_names = array(
						'name'		=> __('Name', 'appointments'),
						'email'		=> __('Email', 'appointments'),
						'phone'		=> __('Phone', 'appointments'),
						'address'	=> __('Address', 'appointments'),
						'city'		=> __('City', 'appointments'),
						'note'		=> __('Note', 'appointments')
					);

		$field_names = apply_filters( 'app_get_field_name', $field_names );

		if ( array_key_exists( $key, $field_names ) )
			return $field_names[$key];
		else
			return __( 'Not defined', 'appointments' );
	}

	/**
	 * Return an array of all available front end box classes
	 * @return array
	 */
	function get_classes() {
		return apply_filters( 'app_box_class_names',
							array(
								'free'			=> __('Free', 'appointments'),
								'busy'			=> __('Busy', 'appointments'),
								'notpossible'	=> __('Not possible', 'appointments')
								)
				);
	}

	/**
	 * Return a default color for a selected box class
	 * @return string
	 */
	function get_preset( $class, $set ) {
		if ( 1 == $set )
			switch ( $class ) {
				case 'free'			:	return '48c048'; break;
				case 'busy'			:	return 'ffffff'; break;
				case 'notpossible'	:	return 'ffffff'; break;
				default				:	return '111111'; break;
			}
		else if ( 2 == $set )
			switch ( $class ) {
				case 'free'			:	return '73ac39'; break;
				case 'busy'			:	return '616b6b'; break;
				case 'notpossible'	:	return '8f99a3'; break;
				default				:	return '111111'; break;
			}
		else if ( 3 == $set )
			switch ( $class ) {
				case 'free'			:	return '40BF40'; break;
				case 'busy'			:	return '454C54'; break;
				case 'notpossible'	:	return '454C54'; break;
				default				:	return '111111'; break;
			}
	}

	/**
	 * Change status for a given app ID
	 * @return bool
	 */
	function change_status( $stat, $app_id ) {
		global $wpdb;
		if ( !$app_id || !$stat )
			return false;

		$result = $wpdb->update( $this->app_table,
									array('status'	=> $stat),
									array('ID'		=> $app_id)
					);
		if ( $result ) {
			$this->flush_cache();
			do_action( 'app_change_status', $stat, $app_id );
			if ( ($stat == 'paid' || $stat == 'confirmed') && is_object( $this->gcal_api ) )
				$this->gcal_api->update( $app_id );
			return true;
		}
		else
			return false;
	}


/************************************************************
* Methods for Shortcodes and those related to shortcodes only
*************************************************************
*/


	/**
	 * Generate an excerpt from the selected service/worker page
	 */
	function get_excerpt( $page_id, $thumb_size, $thumb_class, $worker_id=0 ) {
		$text = '';
		if ( !$page_id )
			return $text;
		$page = get_post( $page_id );
		if ( !$page )
			return $text;

		$text = $page->post_content;

		$text = strip_shortcodes( $text );

		$text = apply_filters('app_the_content', $text, $page_id, $worker_id );
		$text = str_replace(']]>', ']]&gt;', $text);
		$excerpt_length = apply_filters('app_excerpt_length', 55);
		$excerpt_more = apply_filters('app_excerpt_more', ' &hellip; <a href="'. esc_url( get_permalink($page->ID) ) . '" target="_blank">' . __( 'More information <span class="meta-nav">&rarr;</span>', 'appointments' ) . '</a>');
		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		$thumb = $this->get_thumbnail( $page_id, $thumb_size, $thumb_class, $worker_id );

		return apply_filters( 'app_excerpt', $thumb. $text, $page_id, $worker_id );
	}

	/**
	 * Fetch content from the selected service/worker page
	 */
	function get_content( $page_id, $thumb_size, $thumb_class, $worker_id=0 ) {
		$content = '';
		if ( !$page_id )
			return $content;
		$page = get_post( $page_id );
		if ( !$page )
			return $content;

		$thumb = $this->get_thumbnail( $page_id, $thumb_size, $thumb_class, $worker_id );

		$app_content = apply_filters( 'app_pre_content', wpautop( $this->strip_app_shortcodes( $page->post_content ), $page_id, $worker_id ) );

		return apply_filters( 'app_content', $thumb. $app_content, $page_id, $worker_id );
	}

	/**
	 * Clear app shortcodes
	 * @since 1.1.9
	 */
	function strip_app_shortcodes( $content ) {
		// Don't even try to touch a non string, just in case
		if ( !is_string( $content ) )
			return $content;
		else
			return preg_replace( '%\[app_(.*?)\]%is', '', $content );
	}

	/**
	 * Get html code for thumbnail or avatar
	 */
	function get_thumbnail( $page_id, $thumb_size, $thumb_class, $worker_id ) {

		if ( $thumb_size && 'none' != $thumb_size ) {
			if ( strpos( $thumb_size, 'avatar' ) !== false ) {
				if ( strpos( $thumb_size, ',' ) !== false ) {
					$size_arr = explode( ",", $thumb_size );
					$size = $size_arr[1];
				}
				else
					$size = 96;
				$thumb = get_avatar( $worker_id, $size );
				if ( $thumb_class ) {
					// Dirty, but faster than preg_replace
					$thumb = str_replace( "class='", "class='".$thumb_class." ", $thumb );
					$thumb = str_replace( 'class="', 'class="'.$thumb_class.' ', $thumb );
				}
			}
			else {
				if ( strpos( $thumb_size, ',' ) !== false )
					$size = explode( ",", $thumb_size );
				else
					$size = $thumb_size;

				$thumb = get_the_post_thumbnail( $page_id, $size, apply_filters( 'app_thumbnail_attr', array('class'=>$thumb_class) ) );
			}
		}
		else
			$thumb = '';

		return apply_filters( 'app_thumbnail', $thumb, $page_id, $worker_id );
	}

	/**
	 * Check and return necessary fields to the front end
	 * @return json object
	 */
	function pre_confirmation() {

		$values 		= explode( ":", $_POST["value"] );
		$location 		= $values[0];
		$service 		= $values[1];
		$worker 		= $values[2];
		$start 			= $values[3];
		$end 			= $values[4];
		$post_id		= $values[5];

		// A little trick to pass correct lsw variables to the get_price, is_busy and get_capacity functions
		$_REQUEST["app_location_id"] = $location;
		$_REQUEST["app_service_id"] = $service;
		$_REQUEST["app_provider_id"] = $worker;
		$this->get_lsw();

		$price = $this->get_price( );

		// It is possible to apply special discounts
		$price = apply_filters( 'app_display_amount', $price, $service, $worker );
		$price = apply_filters( 'app_pre_confirmation_price', $price, $service, $worker, $start, $end );

		global $wpdb;

		if ( $this->is_busy( $start,  $end, $this->get_capacity() ) )
			die( json_encode( array("error"=>apply_filters( 'app_booked_message',__( 'We are sorry, but this time slot is no longer available. Please refresh the page and try another time slot. Thank you.', 'appointments')))));

		$service_obj = $this->get_service( $service );
		$service = '<label><span>'. __('Service name: ', 'appointments' ).  '</span>'. apply_filters( 'app_confirmation_service', stripslashes( $service_obj->name ), $service_obj->name ) . '</label>';
		$start = '<label><span>'.__('Date and time: ', 'appointments' ). '</span>'. apply_filters( 'app_confirmation_start', date_i18n( $this->datetime_format, $start ), $start ) . '</label>';
		$end = '<label><span>'.__('Lasts (approx): ', 'appointments' ). '</span>'. apply_filters( 'app_confirmation_lasts', $service_obj->duration . " ". __('minutes', 'appointments'), $service_obj->duration ) . '</label>';
		if ( $price > 0 )
			$price = '<label><span>'.__('Price: ', 'appointments' ).  '</span>'. apply_filters( 'app_confirmation_price', $price . " " . $this->options["currency"], $price ) . '</label>';
		else
			$price = 0;

		if ( $worker )
			$worker = '<label><span>'. __('Service provider: ', 'appointments' ).  '</span>'. apply_filters( 'app_confirmation_worker', stripslashes( $this->get_worker_name( $worker ) ), $worker ) . '</label>';
		else
			$worker = '';

		if ( $this->options["ask_name"] )
			$ask_name = "ask";
		else
			$ask_name = "";

		if ( $this->options["ask_email"] )
			$ask_email = "ask";
		else
			$ask_email = "";

		if ( $this->options["ask_phone"] )
			$ask_phone = "ask";
		else
			$ask_phone = "";

		if ( $this->options["ask_address"] )
			$ask_address = "ask";
		else
			$ask_address = "";

		if ( $this->options["ask_city"] )
			$ask_city = "ask";
		else
			$ask_city = "";

		if ( $this->options["ask_note"] )
			$ask_note = "ask";
		else
			$ask_note = "";

		if ( isset( $this->options["gcal"] ) && 'yes' == $this->options["gcal"] )
			$ask_gcal = "ask";
		else
			$ask_gcal = "";

		$reply_array = array(
							'service'	=> $service,
							'worker'	=> $worker,
							'start'		=> $start,
							'end'		=> $end,
							'price'		=> $price,
							'name'		=> $ask_name,
							'email'		=> $ask_email,
							'phone'		=> $ask_phone,
							'address'	=> $ask_address,
							'city'		=> $ask_city,
							'note'		=> $ask_note,
							'gcal'		=> $ask_gcal
						);

		$reply_array = apply_filters( 'app_pre_confirmation_reply', $reply_array );

		die( json_encode( $reply_array ));
	}

	/**
	 * Make checks on submitted fields and save appointment
	 * @return json object
	 */
	function post_confirmation() {

		if ( !$this->check_spam() )
			die( json_encode( array("error"=>apply_filters( 'app_spam_message',__( 'You have already applied for an appointment. Please wait until you hear from us.', 'appointments')))));

		global $wpdb, $current_user, $post;

		$values 		= explode( ":", $_POST["value"] );
		$location 		= $values[0];
		$service 		= $values[1];
		$worker 		= $values[2];
		$start 			= $values[3];
		$end 			= $values[4];
		$post_id		= $values[5];

		if ( is_user_logged_in( ) ) {
			$user_id = $current_user->ID;
			$userdata = get_userdata( $current_user->ID );
			$user_email = $userdata->email;

			$user_name = $userdata->display_name;
			if ( !$user_name )
				$user_name = $userdata->first_name . " " . $userdata->last_name;
			if ( "" == trim( !$user_name ) )
				$user_name = $userdata->user_login;
		}
		else{
			$user_id = 0;
			$user_email = '';
			$user_name = '';
		}

		// A little trick to pass correct lsw variables to the get_price, is_busy and get_capacity functions
		$_REQUEST["app_location_id"] = $location;
		$_REQUEST["app_service_id"] = $service;
		$_REQUEST["app_provider_id"] = $worker;
		$this->get_lsw();

		// Default status
		$status = 'pending';

		if ( 'yes' != $this->options["payment_required"] && isset( $this->options["auto_confirm"] ) && 'yes' == $this->options["auto_confirm"] )
			$status = 'confirmed';

		// We may have 2 prices now: 1) Service full price, 2) Amount that will be paid to Paypal
		$price = $this->get_price( );
		$price = apply_filters( 'app_post_confirmation_price', $price, $service, $worker, $start, $end );
		$paypal_price = $this->get_price( true );
		$paypal_price = apply_filters( 'app_post_confirmation_paypal_price', $paypal_price, $service, $worker, $start, $end );

		if ( isset( $_POST["app_name"] ) )
			$name = sanitize_text_field( $_POST["app_name"] );
		else
			$name = $user_name;

		$name_check = apply_filters( "app_name_check", true, $name );
		if ( !$name_check )
			$this->json_die( 'name' );

		if ( isset( $_POST["app_email"] ) )
			$email = $_POST["app_email"];
		else
			$email = $user_email;

		if ( $this->options["ask_email"] && !is_email( $email ) )
			$this->json_die( 'email' );

		if ( isset( $_POST["app_phone"] ) )
			$phone = sanitize_text_field( $_POST["app_phone"] );
		else
			$phone = '';

		$phone_check = apply_filters( "app_phone_check", true, $phone );
		if ( !$phone_check )
			$this->json_die( 'phone' );

		if ( isset( $_POST["app_address"] ) )
			$address = sanitize_text_field( $_POST["app_address"] );
		else
			$address = '';

		$address_check = apply_filters( "app_address_check", true, $address );
		if ( !$address_check )
			$this->json_die( 'address' );

		if ( isset( $_POST["app_city"] ) )
			$city = sanitize_text_field( $_POST["app_city"] );
		else
			$city = '';

		$city_check = apply_filters( "app_city_check", true, $city );
		if ( !$city_check )
			$this->json_die( 'city' );

		if ( isset( $_POST["app_note"] ) )
			$note = sanitize_text_field( $_POST["app_note"] );
		else
			$note = '';

		if ( isset( $_POST["app_gcal"] ) && $_POST["app_gcal"] )
			$gcal = $_POST["app_gcal"];
		else
			$gcal = '';

		// It may be required to add additional data here
		$note = apply_filters( 'app_note_field', $note );

		$service_result = $this->get_service( $service );

		if ( $service_result !== null )
			$duration = $service_result->duration;
		if ( !$duration )
			$duration = $this->get_min_time(); // In minutes

		$duration = apply_filters( 'app_post_confirmation_duration', $duration, $service, $worker, $user_id );

		if ( $this->is_busy( $start,  $start + ($duration * 60), $this->get_capacity() ) )
			die( json_encode( array("error"=>apply_filters( 'app_booked_message', __( 'We are sorry, but this time slot is no longer available. Please refresh the page and try another time slot. Thank you.', 'appointments')))));

		$status = apply_filters( 'app_post_confirmation_status', $status, $price, $service, $worker, $user_id );

		$result = $wpdb->insert( $this->app_table,
							array(
								'created'	=>	date ("Y-m-d H:i:s", $this->local_time ),
								'user'		=>	$user_id,
								'name'		=>	$name,
								'email'		=>	$email,
								'phone'		=>	$phone,
								'address'	=>	$address,
								'city'		=>	$city,
								'location'	=>	$location,
								'service'	=>	$service,
								'worker'	=> 	$worker,
								'price'		=>	$price,
								'status'	=>	$status,
								'start'		=>	date ("Y-m-d H:i:s", $start),
								'end'		=>	date ("Y-m-d H:i:s", $start + ($duration * 60 ) ),
								'note'		=>	$note
							)
						);
		if ( !$result ) {
			die( json_encode( array("error"=>__( 'Appointment could not be saved. Please contact website admin.', 'appointments'))));
		}

		// A new appointment is accepted, so clear cache
		$insert_id = $wpdb->insert_id; // Save insert ID
		$this->flush_cache();
		$this->save_cookie( $insert_id, $name, $email, $phone, $address, $city, $gcal );
		do_action( 'app_new_appointment', $insert_id );

		// Send confirmation for pending, payment not required cases, if selected so
		if ( 'yes' != $this->options["payment_required"] && isset( $this->options["send_notification"] )
			&& 'yes' == $this->options["send_notification"] && 'pending' == $status )
			$this->send_notification( $insert_id );

		// Send confirmation if we forced it
		if ( 'confirmed' == $status && isset( $this->options["send_confirmation"] ) && 'yes' == $this->options["send_confirmation"] )
			$this->send_confirmation( $insert_id );

		// Add to GCal API
		if ( ('confirmed' == $status || 'paid' == $status) && is_object( $this->gcal_api ) )
			$this->gcal_api->insert( $insert_id );

		// GCal button
		if ( isset( $this->options["gcal"] ) && 'yes' == $this->options["gcal"] && $gcal )
			$gcal_url = $this->gcal( $service, $start, $start + ($duration * 60 ), false, $address, $city );
		else
			$gcal_url = '';

		// Check if this is a App Product page and add variation if it is
		$post = get_post( $post_id );
		if ( $this->check_marketpress_plugin() && 'product' == $post->post_type && strpos( $post->post_content, '[app_' ) !== false ) {
			$mp = 1;
			$variation = $this->add_variation( $insert_id, $post_id, $service, $worker, $start, $end );
		}
		else
			$mp = $variation = 0;

		if ( isset( $this->options["gcal_same_window"] ) && $this->options["gcal_same_window"] )
			$gcal_same_window = 1;
		else
			$gcal_same_window = 0;

		if ( isset( $this->options["payment_required"] ) && 'yes' == $this->options["payment_required"] ) {
			die( json_encode(
							array(
							"cell"				=> $_POST["value"],
							"app_id"			=> $insert_id,
							"refresh"			=> 0,
							"price"				=> $paypal_price,
							"service_name"		=> stripslashes( $service_result->name ),
							'gcal_url'			=> $gcal_url,
							'gcal_same_window'	=> $gcal_same_window,
							'mp'				=> $mp,
							'variation'			=> $variation
							)
						)
					);
		}
		else {
			die( json_encode(
							array(
							"cell"				=> $_POST["value"],
							"app_id"			=> $insert_id,
							"refresh"			=> 1,
							'gcal_url'			=> $gcal_url,
							'gcal_same_window'	=> $gcal_same_window,
							)
				));
		}
	}

	/**
	 * Build GCal url for GCal Button. It requires UTC time.
	 * @param start: Timestamp of the start of the app
	 * @param end: Timestamp of the end of the app
	 * @param php: If this is called for php. If false, called for js
	 * @param address: Address of the appointment
	 * @param city: City of the appointment
	 * @return string
	 */
	function gcal( $service, $start, $end, $php=false, $address, $city ) {
		// Find time difference from Greenwich as GCal asks UTC
		$tdif = current_time('timestamp') - time();
		$text = sprintf( __('%s Appointment', 'appointments'), $this->get_service_name( $service ) );
		if ( !$php )
			$text = esc_js( $text );

		if ( isset( $this->options["gcal_location"] ) && '' != trim( $this->options["gcal_location"] ) )
			$location = esc_js( str_replace( array('ADDRESS', 'CITY'), array($address, $city), $this->options["gcal_location"] ) );
		else
			$location = esc_js( get_bloginfo( 'description' ) );

		$param = array(
					'action'	=> 'TEMPLATE',
					'text'		=> $text,
					'dates'		=> date( "Ymd\THis\Z", $start - $tdif ) . "/" . date( "Ymd\THis\Z", $end - $tdif ),
					'sprop'		=> 'website:' . home_url(),
					'location'	=> $location
				);

		return add_query_arg( apply_filters( 'app_gcal_variables', $param, $service, $start, $end ), 'http://www.google.com/calendar/event' );
	}

	/**
	 * Die showing which field has a problem
	 * @return json object
	 */
	function json_die( $field_name ) {
		die( json_encode( array("error"=>sprintf( __( 'Something wrong about the submitted %s', 'appointments'), $this->get_field_name($field_name)))));
	}

	/**
	 * Check for too frequent back to back apps
	 * return true means no spam
	 * @return bool
	 */
	function check_spam() {
		global $wpdb;
		if ( !isset( $this->options["spam_time"] ) || !$this->options["spam_time"] ||
			!isset( $_COOKIE["wpmudev_appointments"] ) )
			return true;

		$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );

		if ( !is_array( $apps ) || empty( $apps ) )
			return true;

		// Get details of the appointments
		$q = '';
		foreach ( $apps as $app_id ) {
			// Allow only numeric values
			if ( is_numeric( $app_id ) )
				$q .= " ID=".$app_id." OR ";
		}
		$q = rtrim( $q, "OR " );

		$checkdate = date( 'Y-m-d H:i:s', $this->local_time - $this->options["spam_time"] );

		$results = $wpdb->get_results( "SELECT * FROM " . $this->app_table .
					" WHERE created>'".$checkdate."' AND status='pending' AND (".$q.")  " );
		// A recent app is found
		if ( $results )
			return false;

		return true;
	}

	/**
	 *	IPN handling for Paypal
	 */
	function handle_paypal_return() {
		// PayPal IPN handling code
		$this->options = get_option( 'appointments_options' );

		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {

			if ($this->options['mode'] == 'live') {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			$req = 'cmd=_notify-validate';
			if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc()) $v = stripslashes($v);
				$req .= '&' . $k . '=' . $v;
			}

			$header = 'POST /cgi-bin/webscr HTTP/1.0' . "\r\n"
					. 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
					. 'Content-Length: ' . strlen($req) . "\r\n"
					. "\r\n";

			@set_time_limit(60);
			if ($conn = @fsockopen($domain, 80, $errno, $errstr, 30)) {
				fputs($conn, $header . $req);
				socket_set_timeout($conn, 30);

				$response = '';
				$close_connection = false;
				while (true) {
					if (feof($conn) || $close_connection) {
						fclose($conn);
						break;
					}

					$st = @fgets($conn, 4096);
					if ($st === false) {
						$close_connection = true;
						continue;
					}

					$response .= $st;
				}

				$error = '';
				$lines = explode("\n", str_replace("\r\n", "\n", $response));
				// looking for: HTTP/1.1 200 OK
				if (count($lines) == 0) $error = 'Response Error: Header not found';
				else if (substr($lines[0], -7) != ' 200 OK') $error = 'Response Error: Unexpected HTTP response';
				else {
					// remove HTTP header
					while (count($lines) > 0 && trim($lines[0]) != '') array_shift($lines);

					// first line will be empty, second line will have the result
					if (count($lines) < 2) $error = 'Response Error: No content found in transaction response';
					else if (strtoupper(trim($lines[1])) != 'VERIFIED') $error = 'Response Error: Unexpected transaction response';
				}

				if ($error != '') {
					$this->log( $error );
					exit;
				}
			}

			// We are using server time. Not Paypal time.
			$timestamp = $this->local_time;

			$new_status = false;
			// process PayPal response
			switch ($_POST['payment_status']) {
				case 'Partially-Refunded':
					break;

				case 'In-Progress':
					break;

				case 'Completed':
				case 'Processed':
					// case: successful payment
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$this->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], '');
					if ( $this->change_status( 'paid', $_POST['custom'] ) )
						$this->send_confirmation( $_POST['custom'] );
					else {
						// Something wrong. Warn admin
						$message = sprintf( __('Paypal confirmation arrived, but status could not be changed for some reason. Please check appointment with ID %s', 'appointments'), $_POST['custom'] );

						wp_mail( $this->get_admin_email( ), __('Appointment status could not be changed','appointments'), $message, $this->message_headers() );
					}
					break;

				case 'Reversed':
					// case: charge back
					$note = __('Last transaction has been reversed. Reason: Payment has been reversed (charge back)', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$this->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
					break;

				case 'Refunded':
					// case: refund
					$note = __('Last transaction has been reversed. Reason: Payment has been refunded', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$this->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);
					break;

				case 'Denied':
					// case: denied
					$note = __('Last transaction has been reversed. Reason: Payment Denied', 'appointments');
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					$this->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					break;

				case 'Pending':
					// case: payment is pending
					$pending_str = array(
						'address' => __('Customer did not include a confirmed shipping address', 'appointments'),
						'authorization' => __('Funds not captured yet', 'appointments'),
						'echeck' => __('eCheck that has not cleared yet', 'appointments'),
						'intl' => __('Payment waiting for aproval by service provider', 'appointments'),
						'multi-currency' => __('Payment waiting for service provider to handle multi-currency process', 'appointments'),
						'unilateral' => __('Customer did not register or confirm his/her email yet', 'appointments'),
						'upgrade' => __('Waiting for service provider to upgrade the PayPal account', 'appointments'),
						'verify' => __('Waiting for service provider to verify his/her PayPal account', 'appointments'),
						'*' => ''
						);
					$reason = @$_POST['pending_reason'];
					$note = __('Last transaction is pending. Reason: ', 'appointments') . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];

					// Save transaction.
					$this->record_transaction($_POST['custom'], $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					break;

				default:
					// case: various error cases
			}
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			// This is IPN response, so echoing will not help. Let's log it.
			$this->log( 'Error: Missing POST variables. Identification is not possible.' );
			exit;
		}
		exit;
	}

	/**
	 * Find timestamp of first day of month for a given time
	 * @param time: input whose first day will be found
	 * @param add: how many months to add
	 * @return integer (timestamp)
	 * @since 1.0.4
	 */
	function first_of_month( $time, $add ) {
		$year = date( "Y", $time );
		$month = date( "n",  $time ); // Notice "n"

		return mktime( 0, 0, 0, $month+$add, 1, $year );
	}

	/**
	 * Helper function to create a monthly schedule
	 */
	function get_monthly_calendar( $timestamp=false, $class='', $long, $widget ) {
		global $wpdb;

		$this->get_lsw();

		$price = $this->get_price( );

		$date = $timestamp ? $timestamp : $this->local_time;

		$year = date("Y", $date);
		$month = date("m",  $date);
		$time = strtotime("{$year}-{$month}-01");

		$days = (int)date('t', $time);
		$first = (int)date('w', strtotime(date('Y-m-01', $time)));
		$last = (int)date('w', strtotime(date('Y-m-' . $days, $time)));

		$schedule_key = sprintf('%sx%s', strtotime(date('Y-m-01', $time)), strtotime(date('Y-m-' . $days, $time)));

		$tbl_class = $class;
		$tbl_class = $tbl_class ? "class='{$tbl_class}'" : '';

		$ret = '';
		$ret .= '<div class="app_monthly_schedule_wrapper">';

		$ret .= '<a id="app_schedule">&nbsp;</a>';
		$ret  = apply_filters( 'app_monthly_schedule_before_table', $ret );
		$ret .= "<table width='100%' {$tbl_class}>";
		$ret .= $this->_get_table_meta_row_monthly('thead', $long);
		$ret .= '<tbody>';

		$ret = apply_filters( 'app_monthly_schedule_before_first_row', $ret );

		if ( $first > $this->start_of_week )
			$ret .= '<tr><td class="no-left-border" colspan="' . ($first - $this->start_of_week) . '">&nbsp;</td>';
		else if ( $first < $this->start_of_week )
			$ret .= '<tr><td class="no-left-border" colspan="' . (7 + $first - $this->start_of_week) . '">&nbsp;</td>';
		else
			$ret .= '<tr>';

		$todays_no = date("w", $this->local_time ); // Number of today
		$working_days = $this->get_working_days( $this->worker, $this->location ); // Get an array of working days
		$capacity = $this->get_capacity();
		$time_table = '';

		for ($i=1; $i<=$days; $i++) {
			$date = date('Y-m-' . sprintf("%02d", $i), $time);
			$dow = (int)date('w', strtotime($date));
			$ccs = strtotime("{$date} 00:00");
			$cce = strtotime("{$date} 23:59");
			if ($this->start_of_week == $dow)
				$ret .= '</tr><tr>';

			$class_name = '';
			// First mark passed days
			if ( $this->local_time > $cce )
				$class_name = 'notpossible app_past';
			// Then check if this time is blocked
			else if ( isset( $this->options["app_lower_limit"] ) && $this->options["app_lower_limit"]
				&&( $this->local_time + $this->options["app_lower_limit"] * 3600) > $cce )
				$class_name = 'notpossible app_blocked';
			// Check today is holiday
			else if ( $this->is_holiday( $ccs, $cce ) )
				$class_name = 'notpossible app_holiday';
			// Check if we are working today
			else if ( !in_array( date("l", $ccs ), $working_days ) && !$this->is_exceptional_working_day( $ccs, $cce ) )
				$class_name = 'notpossible notworking';
			// Check if we are exceeding app limit at the end of day
			else if ( $cce > $this->local_time + ( $this->get_app_limit() + 1 )*86400 )
				$class_name = 'notpossible';
			// If nothing else, then it must be free unless all time slots are taken
			else {
				// At first assume all cells are busy
				$this->is_a_timetable_cell_free = false;

				$time_table .= $this->get_timetable( $ccs, $capacity, $schedule_key );

				// Look if we have at least one cell free from get_timetable function
				if ( $this->is_a_timetable_cell_free )
					$class_name = 'free';
				else
					$class_name = 'busy';
				// Clear time table for widget
				if ( $widget )
					$time_table = '';
			}
			// Check for today
			if ( $this->local_time > $ccs && $this->local_time < $cce )
				$class_name = $class_name . ' today';

			$ret .= '<td class="'.$class_name.'" title="'.date_i18n($this->date_format, $ccs).'"><p>'.$i.'</p>
			<input type="hidden" class="appointments_select_time" value="'.$ccs .'" /></td>';

		}
		if ( 0 == (6 - $last + $this->start_of_week) )
			$ret .= '</tr>';
		else if ( $last > $this->start_of_week )
			$ret .= '<td class="no-right-border" colspan="' . (6 - $last + $this->start_of_week) . '">&nbsp;</td></tr>';
		else if ( $last + 1 == $this->start_of_week )
			$ret .= '</tr>';
		else
			$ret .= '<td class="no-right-border" colspan="' . (6 + $last - $this->start_of_week) . '">&nbsp;</td></tr>';

		$ret = apply_filters( 'app_monthly_schedule_after_last_row', $ret );
		$ret .= '</tbody>';
		$ret .= $this->_get_table_meta_row_monthly('tfoot', $long);
		$ret .= '</table>';
		$ret  = apply_filters( 'app_monthly_schedule_after_table', $ret );
		$ret .= '</div>';

		$ret .= '<div class="app_timetable_wrapper">';
		$ret .= $time_table;
		$ret .= '</div>';

		$ret .= '<div style="clear:both"></div>';

		$script  = '';
		$script .= '$(".app_monthly_schedule_wrapper table td.free").click(function(){';
		$script .= 'var selected_timetable=$(".app_timetable_"+$(this).find(".appointments_select_time").val());';
		$script .= '$(".app_timetable:not(selected_timetable)").hide();';
		$script .= 'selected_timetable.show("slow");';
		$script .= '});';

		$this->add2footer( $script );

		return $ret;
	}

	/**
	 * Helper function to create a time table for monthly schedule
	 */
	function get_timetable( $day_start, $capacity, $schedule_key=false ) {

		// We need this only for the first timetable
		// Otherwise $time will be calculated from $day_start
		if ( isset( $_GET["wcalendar"] ) )
			$time = $_GET["wcalendar"];
		else
			$time = $this->local_time;

		// Are we looking to today?
		// If today is a working day, shows its free times by default
		if ( date( 'Ymd', $day_start ) == date( 'Ymd', $time ) )
			$style = '';
		else
			$style = ' style="display:none"';

		$start = $end = 0;
		if ( $min_max = $this->min_max_wh( 0, 0 ) ) {
			$start = $min_max["min"];
			$end = $min_max["max"];
		}
		if ( $start >= $end ) {
			$start = 8;
			$end = 18;
		}
		$start = apply_filters( 'app_schedule_starting_hour', $start );
		$end = apply_filters( 'app_schedule_ending_hour', $end );

		$first = $start *3600 + $day_start; // Timestamp of the first cell
		$last = $end *3600 + $day_start; // Timestamp of the last cell

		$service = $this->get_service($this->service);
		$step = (!empty($service->duration) ? $service->duration : $this->get_min_time()) * 60; // Timestamp increase interval to one cell ahead


		$ret  = '';
		$ret .= '<div class="app_timetable app_timetable_'.$day_start.'"'.$style.'>';
		$ret .= '<div class="app_timetable_title">';
		$ret .= date_i18n( $this->date_format, $day_start );
		$ret .= '</div>';

		for ( $t=$first; $t<$last; $t=$t+$step ) {

			$ccs = apply_filters( 'app_ccs', $t ); 				// Current cell starts
			$cce = $ccs + $step;								// Current cell ends

			$is_busy = $this->is_busy( $ccs, $cce, $capacity );
			$title = apply_filters('app-schedule_cell-title', date_i18n($this->datetime_format, $ccs), $is_busy, $ccs, $cce, $schedule_key);

			$class_name = '';
			// Mark now
			if ( $this->local_time > $ccs && $this->local_time < $cce )
				$class_name = 'notpossible now';
			// Mark passed hours
			else if ( $this->local_time > $ccs )
				$class_name = 'notpossible app_past';
			// Then check if this time is blocked
			else if ( isset( $this->options["app_lower_limit"] ) && $this->options["app_lower_limit"]
				&&( $this->local_time + $this->options["app_lower_limit"] * 3600) > $cce )
				$class_name = 'notpossible app_blocked';
			// Check if this is break
			else if ( $this->is_break( $ccs, $cce ) )
				$class_name = 'notpossible app_break';
			// Then look for appointments
			else if ( $is_busy )
				$class_name = 'busy';
			// Then check if we have enough time to fulfill this app
			else if ( !$this->is_service_possible( $ccs, $cce, $capacity ) )
				$class_name = 'notpossible service_notpossible';
			// If nothing else, then it must be free
			else {
				$class_name = 'free';
				// We found at least one timetable cell to be free
				$this->is_a_timetable_cell_free = true;
			}

			$class_name = apply_filters( 'app_class_name', $class_name, $ccs, $cce );

			$ret .= '<div class="app_timetable_cell '.$class_name.'" title="'.esc_attr($title).'">'.
						$this->secs2hours( $ccs - $day_start ). '<input type="hidden" class="appointments_take_appointment" value="'.$this->pack( $ccs, $cce ).'" />';

			$ret .= '</div>';
		}

		$ret .= '<div style="clear:both"></div>';

		$ret .= '</div>';

		return $ret;

	}

	function _get_table_meta_row_monthly ($which, $long) {
		if ( !$long )
			$day_names_array = $this->arrange( $this->get_short_day_names(), false );
		else
			$day_names_array = $this->arrange( $this->get_day_names(), false );
		$cells = '<th>' . join('</th><th>', $day_names_array) . '</th>';
		return "<{$which}><tr>{$cells}</tr></{$which}>";
	}

	/**
	 * Helper function to create a weekly schedule
	 */
	function get_weekly_calendar( $timestamp=false, $class='', $long ) {
		global $wpdb;

		$this->get_lsw();

		$price = $this->get_price( );

		$year = date("Y", $this->local_time);
		$month = date("m",  $this->local_time);

		$date = $timestamp ? $timestamp : $this->local_time;

		$sunday = $this->sunday( $date ); // Timestamp of first Sunday of any date

		$start = $end = 0;
		if ( $min_max = $this->min_max_wh( 0, 0 ) ) {
			$start = $min_max["min"];
			$end = $min_max["max"];
		}
		if ( $start >= $end ) {
			$start = 8;
			$end = 18;
		}
		$start = apply_filters( 'app_schedule_starting_hour', $start );
		$end = apply_filters( 'app_schedule_ending_hour', $end );

		$first = $start *3600 + $sunday; // Timestamp of the first cell of first Sunday
		$last = $end *3600 + $sunday; // Timestamp of the last cell of first Sunday
		$schedule_key = sprintf("%sx%s", $date, $date+(7*86400));

		$service = $this->get_service($this->service);
		$step = (!empty($service->duration) ? $service->duration : $this->get_min_time()) * 60; // Timestamp increase interval to one cell below

		$days = $this->arrange( array(0,1,2,3,4,5,6), -1, true ); // Arrange days acc. to start of week

		$tbl_class = $class;
		$tbl_class = $tbl_class ? "class='{$tbl_class}'" : '';

		$ret = '';
		$ret .= '<a name="app_schedule">&nbsp;</a>';
		$ret = apply_filters( 'app_schedule_before_table', $ret );
		$ret .= "<table width='100%' {$tbl_class}>";
		$ret .= $this->_get_table_meta_row('thead', $long);
		$ret .= '<tbody>';

		$ret = apply_filters( 'app_schedule_before_first_row', $ret );

		$todays_no = date("w", $this->local_time ); // Number of today
		$working_days = $this->get_working_days( $this->worker, $this->location ); // Get an array of working days
		$capacity = $this->get_capacity();

		for ( $t=$first; $t<$last; $t=$t+$step ) {
			foreach ( $days as $key=>$i ) {
				if ( $i == -1 ) {
					$from = apply_filters( 'app_weekly_calendar_from', $this->secs2hours( $t - $sunday ), $t );
					$to = apply_filters( 'app_weekly_calendar_to', $this->secs2hours( $t - $sunday + $step ), $t );
					$ret .= "<td class='appointments-weekly-calendar-hours-mins'>".$from." &#45; ".$to."</td>";
				}
				else {
					$ccs = apply_filters( 'app_ccs', $t + $i * 86400 ); // Current cell starts
					$cce = $ccs + $step;								// Current cell ends

					$class_name = '';
					$is_busy = $this->is_busy( $ccs, $cce, $capacity );
					$title = apply_filters('app-schedule_cell-title', date_i18n($this->datetime_format, $ccs), $is_busy, $ccs, $cce, $schedule_key);

					// Also mark now
					if ( $this->local_time > $ccs && $this->local_time < $cce )
						$class_name = 'notpossible now';
					// Mark passed hours
					else if ( $this->local_time > $ccs )
						$class_name = 'notpossible app_past';
					// Then check if this time is blocked
					else if ( isset( $this->options["app_lower_limit"] ) && $this->options["app_lower_limit"]
						&&( $this->local_time + $this->options["app_lower_limit"] * 3600) > $cce )
						$class_name = 'notpossible app_blocked';
					// Check today is holiday
					else if ( $this->is_holiday( $ccs, $cce ) )
						$class_name = 'notpossible app_holiday';
					// Check if we are working today
					else if ( !in_array( date("l", $ccs ), $working_days ) && !$this->is_exceptional_working_day( $ccs, $cce ) )
						$class_name = 'notpossible notworking';
					// Check if this is break
					else if ( $this->is_break( $ccs, $cce ) )
						$class_name = 'notpossible app_break';
					// Then look for appointments
					else if ( $is_busy )
						$class_name = 'busy';
					// Then check if we have enough time to fulfill this app
					else if ( !$this->is_service_possible( $ccs, $cce, $capacity ) )
						$class_name = 'notpossible service_notpossible';
					// If nothing else, then it must be free
					else
						$class_name = 'free';

					$class_name = apply_filters( 'app_class_name', $class_name, $ccs, $cce );

					$ret .= '<td class="'.$class_name.'" title="'.esc_attr($title).'">
					<input type="hidden" class="appointments_take_appointment" value="'.$this->pack( $ccs, $cce ).'" /></td>';
				}
			}
			$ret .= '</tr><tr>'; // Close the last day of the week
		}
		$ret = apply_filters( 'app_schedule_after_last_row', $ret );
		$ret .= '</tbody>';
		$ret .= $this->_get_table_meta_row('tfoot', $long);
		$ret .= '</table>';
		$ret = apply_filters( 'app_schedule_after_table', $ret );

		return $ret;
	}

	function _get_table_meta_row ($which, $long) {
		if ( !$long )
			$day_names_array = $this->arrange( $this->get_short_day_names(), __(' ', 'appointments') );
		else
			$day_names_array = $this->arrange( $this->get_day_names(), __(' ', 'appointments') );
		$cells = '<th class="hourmin_column">&nbsp;' . join('</th><th>', $day_names_array) . '</th>';
		return "<{$which}><tr>{$cells}</tr></{$which}>";
	}

	function get_day_names () {
		return array(
			__('Sunday',	'appointments'),
			__('Monday',	'appointments'),
			__('Tuesday',	'appointments'),
			__('Wednesday',	'appointments'),
			__('Thursday',	'appointments'),
			__('Friday',	'appointments'),
			__('Saturday',	'appointments'),
		);
	}

	function get_short_day_names () {
		return array(
			__('Su', 'appointments'),
			__('Mo', 'appointments'),
			__('Tu', 'appointments'),
			__('We', 'appointments'),
			__('Th', 'appointments'),
			__('Fr', 'appointments'),
			__('Sa', 'appointments'),
		);
	}

	/**
	 * Returns the timestamp of Sunday of the current time or selected date
	 * @param timestamp: Timestamp of the selected date or false for current time
	 * @return integer (timestamp)
	 */
	function sunday( $timestamp=false ) {

		$date = $timestamp ? $timestamp : $this->local_time;
		// Return today's timestamp if today is sunday and start of the week is set as Sunday
		if ( "Sunday" == date( "l", $date ) && 0 == $this->start_of_week )
			return strtotime("today");
		// Else return last week's timestamp
		else
			return strtotime("last Sunday", $date );
	}

	/**
	 * Arranges days array acc. to start of week, e.g 1234567 (Week starting with Monday)
	 * @param days: input array
	 * @param prepend: What to add as first element
	 * @pram nod: If number of days (true) or name of days (false)
	 * @return array
	 */
	function arrange( $days, $prepend, $nod=false ) {
		if ( $this->start_of_week ) {
			for ( $n = 1; $n<=$this->start_of_week; $n++ ) {
				array_push( $days, array_shift( $days ) );
			}
			// Fix for displaying past days; apply only for number of days
			if ( $nod ) {
				$first = false;
				$temp = array();
				foreach ( $days as $key=>$day ) {
					if ( !$first )
						$first = $day; // Save the first day
					if ( $day < $first )
						$temp[$key] = $day + 7; // Latter days should be higher than the first day
					else
						$temp[$key] = $day;
				}
				$days = $temp;
			}
		}
		if ( false !== $prepend )
			array_unshift( $days, $prepend );

		return $days;
	}

	/**
	 * Get which days of the week we are working
	 * @return array (may be empty)
	 */
	function get_working_days( $worker=0, $location=0 ) {
		global $wpdb;
		$working_days = array();
		$result = $this->get_work_break( $location, $worker, 'open' );
		if ( $result !== null ) {
			$days = maybe_unserialize( $result->hours );
			if ( is_array( $days ) ) {
				foreach ( $days as $day_name=>$day ) {
					if ( isset( $day["active"] ) && 'yes' == $day["active"] ) {
						$working_days[] = $day_name;
					}
				}
			}
		}
		return $working_days;
	}

	/**
	 * Check if this is an exceptional working day
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_exceptional_working_day( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( !$w )
			$w = $this->worker;
		$is_working_day = false;
		$result = $this->get_exception( $this->location, $w, 'open' );
		if ( $result != null  && strpos( $result->days, date( 'Y-m-d', $ccs ) ) !== false )
			$is_working_day = true;

		return apply_filters( 'app_is_exceptional_working_day', $is_working_day, $ccs, $cce, $this->service, $w );
	}

	/**
	 * Check if today is holiday
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_holiday( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( !$w )
			$w = $this->worker;
		$is_holiday = false;
		$result = $this->get_exception( $this->location, $w, 'closed' );
		if ( $result != null  && strpos( $result->days, date( 'Y-m-d', $ccs ) ) !== false )
			$is_holiday = true;

		return apply_filters( 'app_is_holiday', $is_holiday, $ccs, $cce, $this->service, $w );
	}

	/**
	 * Check if it is break time
	 * Optionally a worker is selectable ( $w != 0 )
	 * @return bool
	 */
	function is_break( $ccs, $cce, $w=0 ) {
		// A worker can be forced
		if ( !$w )
			$w = $this->worker;

		// Try getting cached preprocessed hours
		$days = wp_cache_get('app-break_times-for-' . $w);
		if (!$days) {
			// Preprocess and cache workhours
			// Look where our working hour ends
			$result_days = $this->get_work_break($this->location, $w, 'closed');
			if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
			if ($days) wp_cache_set('app-break_times-for-' . $w, $days);
		}
		if (!is_array($days) || empty($days)) return false;

		// What is the name of this day?
		$this_days_name = date("l", $ccs );
		// This days midnight
		$this_day = date("d F Y", $ccs );

		foreach( $days as $day_name=>$day ) {
			if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {
				$end = $this->to_military( $day["end"] );
				// Special case: End is 00:00
				if ( '00:00' == $end )
					$end = '24:00';
				if ( $ccs >= strtotime( $this_day. " ". $this->to_military( $day["start"] ), $this->local_time ) &&
					$cce <= $this->str2time( $this_day, $end ) ) {
					return true;
				}
			} else if ($day_name == $this_days_name && isset($day["active"]) && is_array($day["active"])) {
				foreach ($day["active"] as $idx => $active) {
					$end = $this->to_military( $day["end"][$idx] );
					// Special case: End is 00:00
					if ( '00:00' == $end )
						$end = '24:00';
					if (
						$ccs >= strtotime( $this_day. " ". $this->to_military( $day["start"][$idx] ), $this->local_time )
						&&
						$cce <= $this->str2time( $this_day, $end )
					) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if a specific worker is working at this time slot
	 * @return bool
	 * @since 1.2.2
	 */
	function is_working( $ccs, $cse, $w ) {
		if ( $this->is_exceptional_working_day( $ccs, $cse, $w ) )
			return true;
		if ( $this->is_holiday( $ccs, $cse, $w ) )
			return false;
		if ( $this->is_break( $ccs, $cse, $w ) )
			return false;

		return true;
	}

	/**
	 * Correctly calculate timestamp based on day and hours:min
	 * This is required as php versions prior to 5.3 cannot calculate 24:00
	 * @param $this_day: Date in d F Y format
	 * @param $end: time in military hours:min format
	 * @since 1.1.8
	 * @return integer (timestamp)
	 */
	function str2time( $this_day, $end ) {
		if ( '24:00' != $end )
			return strtotime( $this_day. " ". $end, $this->local_time );
		else
			return ( strtotime( $this_day. " 23:59", $this->local_time ) + 60 );
	}

	/**
	 * Check if time is enough for this service
	 * e.g if we are working until 6pm, it is not possible to take an app with 60 mins duration at 5:30pm
	 * Please note that "not possible" is an exception
	 * @return bool
	 */
	function is_service_possible( $ccs, $cce, $capacity ) {

		// If this cell exceeds app limit then return false
		if ( $this->get_app_limit() < ceil( ( $ccs - $this->local_time ) /86400 ) )
			return false;

		$result = $this->get_service( $this->service );
		if ( !$result !== null ) {
			$duration = $result->duration;
			if( !$duration )
				return true; // This means min time will be applied. No need to look

			// The same for break time
			if ( isset( $this->options["allow_overwork_break"] ) && 'yes' == $this->options["allow_overwork_break"] )
				$allow_overwork_break = true;
			else
				$allow_overwork_break = false;

			// Check for further appointments or breaks on this day, if this is a lasting appointment
			if ( $duration > $this->get_min_time() ) {
				$step = ceil( $duration/$this->get_min_time() );
				$min_secs = $this->get_min_time() *60;
				if ( $step < 20 ) { // Let's not exaggerate !
					for ( $n =1; $n < $step; $n++ ) {
						if ( $this->is_busy( $ccs + $n * $min_secs, $ccs + ($n+1) * $min_secs, $capacity ) )
							return false; // There is an appointment in the predeeding times
						// We can check breaks here too
						if ( !$allow_overwork_break ) {
							if ( $this->is_break( $ccs + $n * $min_secs, $ccs + ($n+1) * $min_secs ) )
								return false; // There is a break in the predeeding times
						}
					}
				}
			}
			// Now look where our working hour ends

			$days = wp_cache_get('app-open_times-for-' . $this->worker);
			if (!$days) {
				// Preprocess and cache workhours
				// Look where our working hour ends
				$result_days = $this->get_work_break($this->location, $this->worker, 'open');
				if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
				if ($days) wp_cache_set('app-open_times-for-' . $this->worker, $days);
			}
			if (!is_array($days) || empty($days)) return true;

			// If overwork is allowed, lets mark this
			if ( isset( $this->options["allow_overwork"] ) && 'yes' == $this->options["allow_overwork"] )
				$allow_overwork = true;
			else
				$allow_overwork = false;

			// What is the name of this day?
			$this_days_name = date("l", $ccs );
			// This days midnight
			$this_day = date("d F Y", $ccs );
			// Will the service exceed or working time?
			$css_plus_duration = $ccs + ($duration *60);

			foreach( $days as $day_name=>$day ) {
				if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {

					// Special case: End time is 00:00
					$end_mil = $this->to_military( $day["end"] );
					if ( '00:00' == $end_mil )
						$end_mil = '24:00';

					if ( $allow_overwork ) {
						if ( $ccs >= $this->str2time( $this_day, $end_mil ) )
							return false;
					}
					else {
						if (  $css_plus_duration > $this->str2time( $this_day, $end_mil ) )
							return false;
					}

					// We need to check a special case where schedule starts on eg 4pm, but our work starts on 4:30pm.
					if ( $ccs < strtotime( $this_day . " " . $this->to_military( $day["start"] ) , $this->local_time ) )
						return false;
				}
			}

		}
		return true;
	}

	/**
	 * Return available number of workers for a time slot
	 * e.g if one worker works between 8-11 and another works between 13-15, there is no worker between 11-13
	 * This is called from is_busy function
	 * since 1.0.6
	 * @return integer
	 */
	function available_workers( $ccs, $cce ) {
		// If a worker is selected we dont need to do anything special
		if ( $this->worker )
			return $this->get_capacity();

		// Dont proceed further if capacity is forced
		if ( has_filter( 'app_get_capacity' ) )
			return apply_filters( 'app_get_capacity', 1, $this->service, $this->worker );

		$n = 0;
		$workers = $this->get_workers_by_service( $this->service );
		if (!$workers) return $this->get_capacity(); // If there are no workers for this service, apply the service capacity

		foreach( $workers as $worker ) {

			// Try getting cached preprocessed hours
			$days = wp_cache_get('app-open_times-for-' . $worker->ID);
			if (!$days) {
				// Preprocess and cache workhours
				// Look where our working hour ends
				$result_days = $this->get_work_break($this->location, $worker->ID, 'open');
				if ($result_days && is_object($result_days) && !empty($result_days->hours)) $days = maybe_unserialize($result_days->hours);
				if ($days) wp_cache_set('app-open_times-for-' . $worker->ID, $days);
			}
			if (!is_array($days) || empty($days)) continue;


			if ( is_array( $days ) ) {
				// What is the name of this day?
				$this_days_name = date("l", $ccs );
				// This days midnight
				$this_day = date("d F Y", $ccs );

				foreach( $days as $day_name=>$day ) {
					if ( $day_name == $this_days_name && isset( $day["active"] ) && 'yes' == $day["active"] ) {
						$end = $this->to_military( $day["end"] );
						// Special case: End is 00:00
						if ( '00:00' == $end )
							$end = '24:00';
						if (
							$ccs >= strtotime( $this_day. " ". $this->to_military( $day["start"] ), $this->local_time )
							&&
							$cce <= $this->str2time( $this_day, $end )
							&&
							!$this->is_break( $ccs, $cce, $worker->ID )
						) $n++;
					}
				}
			}

		}

		// We have to check service capacity too
		$service = $this->get_service( $this->service );
		if ( $service != null ) {
			if ( !$service->capacity ) {
				$capacity = $n; // No service capacity limit
			}
			else
				$capacity = min( $service->capacity, $n ); // Return whichever smaller
		}
		else
			$capacity = 1; // No service ?? - Not possible but let's be safe

		return $capacity;
	}

	/**
	 * Check if a cell is not available, i.e. all appointments taken OR we dont have workers for this time slot
	 * @return bool
	 */
	function is_busy( $start, $end, $capacity ) {

		$week= date( "W", $start );

		// If a specific worker is selected, we will look at his schedule first.
		if ( 0 != $this->worker ) {
			$apps = $this->get_reserve_apps_by_worker( $this->location, $this->worker, $week );
			if ( $apps ) {
				foreach ( $apps as $app ) {
					if ( $start >= strtotime( $app->start ) && $end <= strtotime( $app->end ) )
						return true;
				}
			}
		}
		// If we are here, no preference is selected (provider_id=0) or selected provider is not busy. There are 2 cases here:
		// 1) There are several providers: Look for reserve apps for the workers giving this service.
		// 2) No provider defined: Look for reserve apps for worker=0, because he will carry out all services
		if ( $this->get_workers() != null ) {
			$workers = $this->get_workers_by_service( $this->service );
			$apps = array();
			if ( $workers ) {
				foreach( $workers as $worker ) {
					if ( $this->is_working( $start, $end, $worker->ID ) ) {
						$app_worker = $this->get_reserve_apps_by_worker( $this->location, $worker->ID, $week );
						if ( $app_worker && is_array( $app_worker ) )
							$apps = array_merge( $apps, $app_worker );

						// Also include appointments by general staff for services that can be given by this worker
						$services_provided = $this->_explode( $worker->services_provided );
						if ( $services_provided && is_array( $services_provided ) && !empty( $services_provided ) ) {
							foreach ( $services_provided as $service_ID ) {
								$apps_service_0 = $this->get_reserve_apps( $this->location, $service_ID, 0, $week );
								if ( $apps_service_0 && is_array( $apps_service_0 ) )
									$apps = array_merge( $apps, $apps_service_0 );
							}
						}
					}
				}
				// Remove duplicates
				$apps = $this->array_unique_object_by_ID( $apps );
			}
		}
		else
			$apps = $this->get_reserve_apps_by_worker( $this->location, 0, $week );

		$n = 0;
		foreach ( $apps as $app ) {
			if ( $start >= strtotime( $app->start ) && $end <= strtotime( $app->end ) )
				$n++; // Number of appointments for this time frame
		}

		if ( $n >= $this->available_workers( $start, $end ) )
			return true;

		// Nothing found, so this time slot is not busy
		return false;
	}

	/**
	 * Remove duplicate appointment objects by app ID
	 * @since 1.1.5.1
	 * @return array of objects
	 */
	function array_unique_object_by_ID( $apps ) {
		if ( !is_array( $apps ) || empty( $apps ) )
			return array();
		$idlist = array();
		// Save array to a temp area
		$result = $apps;
		foreach ( $apps as $key=>$app ) {
			if ( isset( $app->ID ) ) {
				if ( in_array( $app->ID, $idlist ) )
					unset( $result[$key] );
				else
					$idlist[] = $app->ID;
			}
		}
		return $result;
	}

	/**
	 * Get the maximum and minimum working hour
	 * @return array
	 */
	function min_max_wh( $worker=0, $location=0 ) {
		$this->get_lsw();
		$result = $this->get_work_break( $this->location, $this->worker, 'open' );
		if ( $result !== null ) {
			$days = maybe_unserialize( $result->hours );
			if ( is_array( $days ) ) {
				$min = 24; $max = 0;
				foreach ( $days as $day ) {
					if ( isset( $day["active"] ) && 'yes' == $day["active"] ) {
						$start = date( "G", strtotime( $this->to_military( $day["start"] ) ) );
						$end_timestamp = strtotime( $this->to_military( $day["end"] ) );
						$end = date( "G", $end_timestamp );
						// Add 1 hour if there are some minutes left. e.g. for 10:10pm, make max as 23
						if ( '00' != date( "i", $end_timestamp ) && $end != 24 )
							$end = $end + 1;
						if ( $start < $min )
							$min = $start;
						if ( $end > $max )
							$max = $end;
						// Special case: If end is 0:00, regard it as 24
						if ( 0 == $end && '00' == date( "i", $end_timestamp ) )
							$max = 24;
					}
				}
				return array( "min"=>$min, "max"=>$max );
			}
		}
		return false;
	}

	/**
	 * Convert any time format to military format
	 * @since 1.0.3
	 * @return string
	 */
	function to_military( $time, $end=false ) {
		// Already in military format
		if ( 'H:i' == $this->time_format )
			return $time;
		// In one of the default formats
		if ( 'g:i a' == $this->time_format  || 'g:i A' == $this->time_format )
			return date( 'H:i', strtotime( $time ) );

		// Custom format. Use a reference time
		// ref will something like 23saat45dakika
		$ref = date_i18n( $this->time_format, strtotime( "23:45" ) );
		if ( strpos( $ref, "23" ) !== false )
			$twentyfour = true;
		else
			$twentyfour = false;
		// Now ref is something like saat,dakika
		$ref = ltrim( str_replace( array( '23', '45' ), ',', $ref ), ',' );
		$ref_arr = explode( ',', $ref );
		if ( isset( $ref_arr[0] ) ) {
			$s = $ref_arr[0]; // separator. We will replace it by :
			if ( isset($ref_arr[1]) && $ref_arr[1] )
				$e = $ref_arr[1];
			else {
				$e = 'PLACEHOLDER';
				$time = $time. $e; // Add placeholder at the back
			}
			if ( $twentyfour )
				$new_e = '';
			else
				$new_e = ' a';
		}
		else
			return $time; // Nothing found ??

		return date( 'H:i', strtotime( str_replace( array($s,$e), array(':',$new_e), $time ) ) );
	}


	/**
	 * Pack several fields as a string using glue ":"
	 * location : service : worker : ccs : cce : post ID
	 * @return string
	 */
	function pack( $ccs, $cce ){
		global $post;
		if ( is_object( $post ) )
			$post_id = $post->ID;
		else
			$post_id = 0;
		return $this->location . ":" . $this->service . ":" . $this->worker . ":" . $ccs . ":" . $cce . ":" . $post_id;
	}

	/**
	 * Save a cookie so that user can see his appointments
	 */
	function save_cookie( $app_id, $name, $email, $phone, $address, $city, $gcal ) {
		if ( isset( $_COOKIE["wpmudev_appointments"] ) )
			$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );
		else
			$apps = array();

		$apps[] = $app_id;

		// Prevent duplicates
		$apps = array_unique( $apps );
		// Add 365 days grace time
		$expire = $this->local_time + 3600 * 24 * ( $this->options["app_limit"] + 365 );

		$expire = apply_filters( 'app_cookie_time', $expire );

		if ( defined('COOKIEPATH') ) $cookiepath = COOKIEPATH;
		else $cookiepath = "/";
		if ( defined('COOKIEDOMAIN') ) $cookiedomain = COOKIEDOMAIN;
		else $cookiedomain = '';

		@setcookie("wpmudev_appointments", serialize($apps), $expire, $cookiepath, $cookiedomain);

		$data = array(
					"n"	=> $name,
					"e"	=> $email,
					"p"	=> $phone,
					"a"	=> $address,
					"c"	=> $city,
					"g"	=> $gcal
					);
		@setcookie("wpmudev_appointments_userdata", serialize($data), $expire, $cookiepath, $cookiedomain);

		// May be required to clean up or modify userdata cookie
		do_action( 'app_save_cookie', $app_id, $apps );

		// Save user data too
		if ( is_user_logged_in() ) {
			global $current_user;
			if ( $name )
				update_user_meta( $current_user->ID, 'app_name', $name );
			if ( $email )
				update_user_meta( $current_user->ID, 'app_email', $email );
			if ( $phone )
				update_user_meta( $current_user->ID, 'app_phone', $phone );
			if ( $address )
				update_user_meta( $current_user->ID, 'app_address', $address );
			if ( $city )
				update_user_meta( $current_user->ID, 'app_city', $city );

			do_action( 'app_save_user_meta', $current_user->ID, array( 'name'=>$name, 'email'=>$email, 'phone'=>$phone, 'address'=>$address, 'city'=>$city ) );
		}
	}


/*******************************
* Methods for frontend login API
********************************
*/
	/**
	 * Login from front end by Wordpress
	 */
	function ajax_login( ) {

		header("Content-type: application/json");
		$user = wp_signon( );

		if ( !is_wp_error($user) ) {

			die(json_encode(array(
				"status" => 1,
				"user_id"=>$user->ID
			)));
		}
		die(json_encode(array(
				"status" => 0,
				"error" => $user->get_error_message()
			)));
	}

	/**
	 * Handles Facebook user login and creation
	 * Modified from Events and Bookings by Ve
	 */
	function handle_facebook_login () {
		header("Content-type: application/json");
		$resp = array(
			"status" => 0,
		);
		$fb_uid = @$_POST['user_id'];
		$token = @$_POST['token'];
		if (!$token) die(json_encode($resp));

		$request = new WP_Http;
		$result = $request->request(
			'https://graph.facebook.com/me?oauth_token=' . $token,
			array('sslverify' => false) // SSL certificate issue workaround
		);
		if (200 != $result['response']['code']) die(json_encode($resp)); // Couldn't fetch info

		$data = json_decode($result['body']);
		if (!$data->email) die(json_encode($resp)); // No email, can't go further

		$email = is_email($data->email);
		if (!$email) die(json_encode($resp)); // Wrong email

		$wp_user = get_user_by('email', $email);

		if (!$wp_user) { // Not an existing user, let's create a new one
			$password = wp_generate_password(12, false);
			$username = @$data->name
				? preg_replace('/[^_0-9a-z]/i', '_', strtolower($data->name))
				: preg_replace('/[^_0-9a-z]/i', '_', strtolower($data->first_name)) . '_' . preg_replace('/[^_0-9a-z]/i', '_', strtolower($data->last_name))
			;

			$wp_user = wp_create_user($username, $password, $email);
			if (is_wp_error($wp_user)) die(json_encode($resp)); // Failure creating user
		} else {
			$wp_user = $wp_user->ID;
		}

		$user = get_userdata($wp_user);

		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID); // Logged in with Facebook, yay
		do_action('wp_login', $user->user_login);

		die(json_encode(array(
			"status" => 1,
			"user_id"=>$user->ID
		)));
	}

	/**
	 * Spawn a TwitterOAuth object.
	 */
	function _get_twitter_object ($token=null, $secret=null) {
		// Make sure options are loaded and fresh
		if ( !$this->options['twitter-app_id'] )
			$this->options = get_option( 'appointments_options' );
		if (!class_exists('TwitterOAuth'))
			include WP_PLUGIN_DIR . '/appointments/includes/twitteroauth/twitteroauth.php';
		$twitter = new TwitterOAuth(
			$this->options['twitter-app_id'],
			$this->options['twitter-app_secret'],
			$token, $secret
		);
		return $twitter;
	}

	/**
	 * Get OAuth request URL and token.
	 */
	function handle_get_twitter_auth_url () {
		header("Content-type: application/json");
		$twitter = $this->_get_twitter_object();
		$request_token = $twitter->getRequestToken($_POST['url']);
		echo json_encode(array(
			'url' => $twitter->getAuthorizeURL($request_token['oauth_token']),
			'secret' => $request_token['oauth_token_secret']
		));
		die;
	}

	/**
	 * Login or create a new user using whatever data we get from Twitter.
	 */
	function handle_twitter_login () {
		header("Content-type: application/json");
		$resp = array(
			"status" => 0,
		);
		$secret = @$_POST['secret'];
		$data_str = @$_POST['data'];
		$data_str = ('?' == substr($data_str, 0, 1)) ? substr($data_str, 1) : $data_str;
		$data = array();
		parse_str($data_str, $data);
		if (!$data) die(json_encode($resp));

		$twitter = $this->_get_twitter_object($data['oauth_token'], $secret);
		$access = $twitter->getAccessToken($data['oauth_verifier']);

		$twitter = $this->_get_twitter_object($access['oauth_token'], $access['oauth_token_secret']);
		$tw_user = $twitter->get('account/verify_credentials');

		// Have user, now register him/her
		$domain = preg_replace('/www\./', '', parse_url(site_url(), PHP_URL_HOST));
		$username = preg_replace('/[^_0-9a-z]/i', '_', strtolower($tw_user->name));
		$email = $username . '@twitter.' . $domain; //STUB email
		$wp_user = get_user_by('email', $email);

		if (!$wp_user) { // Not an existing user, let's create a new one
			$password = wp_generate_password(12, false);
			$count = 0;
			while (username_exists($username)) {
				$username .= rand(0,9);
				if (++$count > 10) break;
			}

			$wp_user = wp_create_user($username, $password, $email);
			if (is_wp_error($wp_user)) die(json_encode($resp)); // Failure creating user
		} else {
			$wp_user = $wp_user->ID;
		}

		$user = get_userdata($wp_user);
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID); // Logged in with Twitter, yay
		do_action('wp_login', $user->user_login);

		die(json_encode(array(
			"status" => 1,
			"user_id"=>$user->ID
		)));
	}

	/**
	 * Get OAuth request URL and token.
	 */
	function handle_get_google_auth_url () {
		header("Content-type: application/json");

		$this->openid->returnUrl = $_POST['url'];

		echo json_encode(array(
			'url' => $this->openid->authUrl()
		));
		exit();
	}

	/**
	 * Login or create a new user using whatever data we get from Google.
	 */
	function handle_google_login () {
		header("Content-type: application/json");
		$resp = array(
			"status" => 0,
		);

		$cache = $this->openid->getAttributes();

		if (isset($cache['namePerson/first']) || isset($cache['namePerson/last']) || isset($cache['namePerson/friendly']) || isset($cache['contact/email'])) {
			$this->_google_user_cache = $cache;
		}

		// Have user, now register him/her
		if ( isset( $this->_google_user_cache['namePerson/friendly'] ) )
			$username = $this->_google_user_cache['namePerson/friendly'];
		else
			$username = $this->_google_user_cache['namePerson/first'];
		$email = $this->_google_user_cache['contact/email'];
		$wordp_user = get_user_by('email', $email);

		if (!$wordp_user) { // Not an existing user, let's create a new one
			$password = wp_generate_password(12, false);
			$count = 0;
			while (username_exists($username)) {
				$username .= rand(0,9);
				if (++$count > 10) break;
			}

			$wordp_user = wp_create_user($username, $password, $email);
			if (is_wp_error($wordp_user))
				die(json_encode($resp)); // Failure creating user
			else {
				update_user_meta($wordp_user, 'first_name', $this->_google_user_cache['namePerson/first']);
				update_user_meta($wordp_user, 'last_name', $this->_google_user_cache['namePerson/last']);
			}
		}
		else {
			$wordp_user = $wordp_user->ID;
		}

		$user = get_userdata($wordp_user);
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID); // Logged in with Google, yay
		do_action('wp_login', $user->user_login);

		die(json_encode(array(
			"status" => 1,
		)));
	}


/*******************************
* User methods
********************************
*/
	/**
	 * Saves working hours from user profile
	 */
	function save_profile( $profileuser_id ) {
		global $current_user, $wpdb;

		// Copy key file to uploads folder
		if ( is_object( $this->gcal_api ) ) {
			$kff = $this->gcal_api->key_file_folder( ); // Key file folder
			$kfn = $this->gcal_api->get_key_file( $profileuser_id ). '.p12'; // Key file name
			if ( $kfn && is_dir( $kff ) && !file_exists( $kff . $kfn ) && file_exists( $this->plugin_dir . '/includes/gcal/key/' . $kfn ) )
				copy( $this->plugin_dir . '/includes/gcal/key/' . $kfn, $kff . $kfn );
		}

		// Only user himself can save his data
		if ( $current_user->ID != $profileuser_id )
			return;

		// Save user meta
		if ( isset( $_POST['app_name'] ) )
			update_user_meta( $profileuser_id, 'app_name', $_POST['app_name'] );
		if ( isset( $_POST['app_email'] ) )
			update_user_meta( $profileuser_id, 'app_email', $_POST['app_email'] );
		if ( isset( $_POST['app_phone'] ) )
			update_user_meta( $profileuser_id, 'app_phone', $_POST['app_phone'] );
		if ( isset( $_POST['app_address'] ) )
			update_user_meta( $profileuser_id, 'app_address', $_POST['app_address'] );
		if ( isset( $_POST['app_city'] ) )
			update_user_meta( $profileuser_id, 'app_city', $_POST['app_city'] );

		// Save Google API settings
		if ( isset( $_POST['gcal_api_mode'] ) )
			update_user_meta( $profileuser_id, 'app_api_mode', $_POST['gcal_api_mode'] );
		if ( isset( $_POST['gcal_service_account'] ) )
			update_user_meta( $profileuser_id, 'app_service_account', trim( $_POST['gcal_service_account'] ) );
		if ( isset( $_POST['gcal_key_file'] ) )
			update_user_meta( $profileuser_id, 'app_key_file', trim( str_replace( '.p12', '', $_POST['gcal_key_file'] ) ) );
		if ( isset( $_POST['gcal_selected_calendar'] ) )
			update_user_meta( $profileuser_id, 'app_selected_calendar', trim( $_POST['gcal_selected_calendar'] ) );
		if ( isset( $_POST['gcal_summary'] ) ) {
			if ( !trim( $_POST['gcal_summary'] ) )
				$summary = __('SERVICE Appointment','appointments');
			else
				$summary = $_POST['gcal_summary'];
			update_user_meta( $profileuser_id, 'app_gcal_summary', $summary );
		}
		if ( isset( $_POST['gcal_description'] ) ) {
			if ( !trim( $_POST['gcal_description'] ) )
$gcal_description = __("Client Name: CLIENT
Service Name: SERVICE
Service Provider Name: SERVICE_PROVIDER
", "appointments")
;
			else
				$gcal_description = $_POST['gcal_description'];

			update_user_meta( $profileuser_id, 'app_gcal_description', $gcal_description );
		}

		// Cancel appointment
		if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] &&
			isset( $_POST['app_cancel'] ) && is_array( $_POST['app_cancel'] ) && !empty( $_POST['app_cancel'] ) ) {
			foreach ( $_POST['app_cancel'] as $app_id=>$value ) {
				if ( $this->change_status( 'removed', $app_id ) ) {
					$this->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $this->get_client_name( $app_id ), $app_id ) );
					$this->send_notification( $app_id, true );
				}
			}
		}

		// Only user who is a worker can save the rest
		if ( !$this->is_worker( $profileuser_id ) )
			return;

		// Confirm an appointment using profile page
		if ( isset( $_POST['app_confirm'] ) && is_array( $_POST['app_confirm'] ) && !empty( $_POST['app_confirm'] ) ) {
			foreach ( $_POST['app_confirm'] as $app_id=>$value ) {
				if ( $this->change_status( 'confirmed', $app_id ) ) {
					$this->log( sprintf( __('Service Provider %s manually confirmed appointment with ID: %s','appointments'), $this->get_worker_name( $current_user->ID ), $app_id ) );
					$this->send_confirmation( $app_id );
				}
			}
		}

		// Save working hours table
		// Do not save these if we are coming from BuddyPress confirmation tab
		if ( isset($this->options["allow_worker_wh"]) && 'yes' == $this->options["allow_worker_wh"] && isset( $_POST['open'] ) && isset( $_POST['closed'] ) ) {
			$result = $result2 = false;
			$location = 0;
			foreach ( array( 'closed', 'open' ) as $stat ) {
				$count = $wpdb->get_var("SELECT COUNT(*) FROM ". $this->wh_table .
						" WHERE location=".$location." AND worker=".$profileuser_id." AND status='".$stat."' ");

				if ( $count > 0 ) {
					$result = $wpdb->update( $this->wh_table,
						array( 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
						array( 'location'=>$location, 'worker'=>$profileuser_id, 'status'=>$stat ),
						array( '%s', '%s' ),
						array( '%d', '%d', '%s' )
					);
				}
				else {
					$result = $wpdb->insert( $this->wh_table,
						array( 'location'=>$location, 'worker'=>$profileuser_id, 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
						array( '%d', '%d', '%s', '%s' )
						);
				}
				// Save exceptions
				$count2 = $wpdb->get_var( "SELECT COUNT(*) FROM ". $this->exceptions_table .
						" WHERE location=".$location." AND worker=".$profileuser_id." AND status='".$stat."' ");

				if ( $count2 > 0 ) {
					$result2 = $wpdb->update( $this->exceptions_table,
						array(
								'days'		=> $_POST[$stat]["exceptional_days"],
								'status'	=> $stat
							),
						array(
							'location'	=> $location,
							'worker'	=> $profileuser_id,
							'status'	=> $stat ),
						array( '%s', '%s' ),
						array( '%d', '%d', '%s' )
					);
				}
				else {
					$result2 = $wpdb->insert( $this->exceptions_table,
						array( 'location'	=> $location,
								'worker'	=> $profileuser_id,
								'days'		=> $_POST[$stat]["exceptional_days"],
								'status'	=> $stat
							),
						array( '%d', '%d', '%s', '%s' )
						);
				}
			}
			if ( $result || $result2 ) {
				$message = sprintf( __('%s edited his working hours.', 'appointments'), $this->get_worker_name( $profileuser_id ) );
				$this->log( $message );
				// Employer can be noticed here
				do_action( "app_working_hour_update", $message, $profileuser_id );
				// Also clear cache
				$this->flush_cache();
			}
		}
	}

	/**
	 * Displays appointment schedule on the user profile
	 */
	function show_profile( $profileuser ) {
		global $current_user, $wpdb;

		// Only user or admin can see his data
		if ( $current_user->ID != $profileuser->ID && !App_Roles::current_user_can('list_users', CTX_STAFF) )
			return;

		// For other than user himself, display data as readonly
		if ( $current_user->ID != $profileuser->ID )
			$is_readonly = ' readonly="readonly"';
		else
			$is_readonly = '';

		$is_readonly = apply_filters( 'app_show_profile_readonly', $is_readonly, $profileuser );

		if ( isset( $this->options["gcal"] ) && 'yes' == $this->options["gcal"] )
			$gcal = ''; // Default is already enabled
		else
			$gcal = ' gcal="0"';
	?>
		<h3><?php _e("Appointments+", 'appointments'); ?></h3>

		<table class="form-table">
		<tr>
		<th><label><?php _e("My Name", 'appointments'); ?></label></th>
		<td>
		<input type="text" style="width:25em" name="app_name" value="<?php echo get_user_meta( $profileuser->ID, 'app_name', true ) ?>" <?php echo $is_readonly ?> />
		</td>
		</tr>

		<tr>
		<th><label><?php _e("My email for A+", 'appointments'); ?></label></th>
		<td>
		<input type="text" style="width:25em" name="app_email" value="<?php echo get_user_meta( $profileuser->ID, 'app_email', true ) ?>" <?php echo $is_readonly ?> />
		</td>
		</tr>

		<tr>
		<th><label><?php _e("My Phone", 'appointments'); ?></label></th>
		<td>
		<input type="text" style="width:25em" name="app_phone" value="<?php echo get_user_meta( $profileuser->ID, 'app_phone', true ) ?>"<?php echo $is_readonly ?> />
		</td>
		</tr>

		<tr>
		<th><label><?php _e("My Address", 'appointments'); ?></label></th>
		<td>
		<input type="text" style="width:50em" name="app_address" value="<?php echo get_user_meta( $profileuser->ID, 'app_address', true ) ?>" <?php echo $is_readonly ?> />
		</td>
		</tr>

		<tr>
		<th><label><?php _e("My City", 'appointments'); ?></label></th>
		<td>
		<input type="text" style="width:25em" name="app_city" value="<?php echo get_user_meta( $profileuser->ID, 'app_city', true ) ?>" <?php echo $is_readonly ?> />
		</td>
		</tr>

		<?php if ( !$this->is_worker( $profileuser->ID ) ) { ?>
		<tr>
		<th><label><?php _e("My Appointments", 'appointments'); ?></label></th>
		<td>
		<?php echo do_shortcode("[app_my_appointments allow_cancel=1 client_id=".$profileuser->ID." ".$gcal."]") ?>
		</td>
		</tr>
			<?php
			if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] ) { ?>
				<script type='text/javascript'>
				jQuery(document).ready(function($){
					$('#your-profile').submit(function() {
						if ( $('.app-my-appointments-cancel').is(':checked') ) {
							if ( !confirm('<?php echo esc_js( __("Are you sure to cancel the selected appointment(s)?","appointments") ) ?>') )
							{return false;}
						}
					});
				});
				</script>
			<?php
			}
		}
		else { ?>
		<tr>
		<th><label><?php _e("My Appointments as Provider", 'appointments'); ?></label></th>
		<td>
		<?php echo do_shortcode("[app_my_appointments status='pending,confirmed,paid' _allow_confirm=1 provider_id=".$profileuser->ID."  provider=1 ".$gcal."]") ?>
		</td>
		</tr>
		<?php
			if ( isset( $this->options['allow_worker_confirm'] ) && 'yes' == $this->options['allow_worker_confirm'] ) { ?>
				<script type='text/javascript'>
				jQuery(document).ready(function($){
					$('#your-profile').submit(function() {
						if ( $('.app-my-appointments-confirm').is(':checked') ) {
							if ( !confirm('<?php echo esc_js( __("Are you sure to confirm the selected appointment(s)?","appointments") ) ?>') )
							{return false;}
						}
					});
				});
				</script>
				<?php
			}
			if ( isset($this->options["allow_worker_wh"]) && 'yes' == $this->options["allow_worker_wh"] ) { ?>
			<?php
			// A little trick to pass correct lsw variables to the related function
			$_REQUEST["app_location_id"] = 0;
			$_REQUEST["app_provider_id"] = $profileuser->ID;

			$this->get_lsw();

			$result = array();
			$result_open = $this->get_exception( $this->location, $this->worker, 'open' );
			if ( $result_open )
				$result["open"] = $result_open->days;
			else
				$result["open"] = null;

			$result_closed = $this->get_exception( $this->location, $this->worker, 'closed' );
			if ( $result_closed )
				$result["closed"] = $result_closed->days;
			else
				$result["closed"] = null;
			?>
			<tr>
			<th><label><?php _e("My Working Hours", 'appointments'); ?></label></th>
			<td>
			<?php echo $this->working_hour_form('open') ?>
			</td>
			</tr>
			<tr>
			<th><label><?php _e("My Break Hours", 'appointments'); ?></label></th>
			<td>
			<?php echo $this->working_hour_form('closed') ?>
			</td>
			</tr>
			<tr>
			<th><label><?php _e("My Exceptional Working Days", 'appointments'); ?></label></th>
			<td>
			<input class="datepick" id="open_datepick" type="text" style="width:100%" name="open[exceptional_days]" value="<?php if (isset($result["open"])) echo $result["open"]?>" />
			</td>
			</tr>
			<tr>
			<th><label><?php _e("My Holidays", 'appointments'); ?></label></th>
			<td>
			<input class="datepick" id="closed_datepick" type="text" style="width:100%" name="closed[exceptional_days]" value="<?php if (isset($result["closed"])) echo $result["closed"]?>" />
			</td>
			</tr>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$("#open_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
				$("#closed_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
			});
			</script>
			<?php } ?>
		<?php } ?>
		<?php if ( isset($this->options["gcal_api_allow_worker"]) && 'yes' == $this->options["gcal_api_allow_worker"] && $this->is_worker( $profileuser->ID ) ) { ?>
			<tr>
			<th><label><?php _e("Appointments+ Google Calendar API", 'appointments'); ?></label></th>
			<td>
			</td>
			</tr>
			<tr>
			<td colspan="2">
			<?php
				if ( is_object( $this->gcal_api ) )
					$this->gcal_api->display_nag( $profileuser->ID ); ?>
			</td>
			</tr>
		<?php
			if ( is_object( $this->gcal_api ) )
				$this->gcal_api->display_settings( $profileuser->ID );
		 } ?>
		</table>
	<?php
	}

/*******************************
* Methods for Buddypress
********************************
*/

	/**
     * Save setting submitted from front end
     */
	function bp_init() {

		$this->bp = true;

		if ( !isset( $_POST["app_bp_settings_submit"] ) || !isset( $_POST["app_bp_settings_user"] ) )
			return;

		// In the future we may use this function without BP too
		if ( function_exists( 'bp_loggedin_user_id') )
			$user_id = bp_loggedin_user_id();
		else {
			global $current_user;
			$user_id = $current_user->ID;
		}

		if ( !$user_id || !wp_verify_nonce($_POST['app_bp_settings_submit'],'app_bp_settings_submit')
				|| $user_id != $_POST["app_bp_settings_user"] || !$this->is_worker( $user_id )
				|| !isset( $this->options["allow_worker_wh"] ) || 'yes' != $this->options["allow_worker_wh"] ) {
			wp_die( 'You don\'t have the authority to do this.', 'appointments' );
			exit;
		}
		// Checks are ok, let's save settings.
		$this->save_profile( $user_id );
	}

	/**
     * Determine which page we are on
	 * If it is correct, load necessary scripts and css files
     */
	function bp_template_redirect() {
		global $bp;
		if ( !is_object( $bp ) )
			return;

		$scheme        = is_ssl() ? 'https://' : 'http://';
		$requested_url = strtolower( $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$page_url = str_replace( $bp->displayed_user->domain . 'appointments/', '', $requested_url );

		// Load datepick if we are on settings page
		if ( strpos( $page_url, 'appointment-settings' ) !== false ) {
			$this->bp_script ='$("#open_datepick").datepick({dateFormat: "yyyy-mm-dd",multiSelect: 999, monthsToShow: 2});
				$("#closed_datepick").datepick({dateFormat: "yyyy-mm-dd",multiSelect: 999, monthsToShow: 2});';

			wp_enqueue_script( 'jquery-datepick', $this->plugin_url . '/js/jquery.datepick.min.js', array('jquery'), $this->version);
			wp_enqueue_style( "jquery-datepick", $this->plugin_url . "/css/jquery.datepick.css", false, $this->version );
		}
	}

	/**
	 * Load javascript to the footer
	 */
	function bp_footer() {
		$script = '';
		$this->bp_script = apply_filters( 'app_bp_footer_scripts', $this->bp_script );

		if ( $this->bp_script ) {
			$script .= '<script type="text/javascript">';
			$script .= "jQuery(document).ready(function($) {";
			$script .= $this->bp_script;
			$script .= "});</script>";
		}

		echo $this->esc_rn( $script );
	}

	/**
     * Add a nav and two subnav items
     */
	function setup_nav() {
		global $bp;
		bp_core_new_nav_item( array(
			'name' => __( 'Appointments', 'appointments' ),
			'slug' => 'appointments',
			'show_for_displayed_user' => false,
			'screen_function' => array( &$this, 'tab_template' )
		) );

		$link = $bp->loggedin_user->domain . 'appointments/';

		$user_id = bp_loggedin_user_id();
		if ( !$this->is_worker( $user_id ) )
			$name = __( 'My Appointments', 'appointments' );
		else
			$name = __( 'My Appointments as Provider', 'appointments' );

		bp_core_new_subnav_item( array(
			'name' => $name,
			'slug' => 'my-appointments',
			'parent_url' => $link,
			'parent_slug' => 'appointments',
			'screen_function' => array( &$this, 'tab_template_my_app' )
		) );

		// Generate this tab only if allowed
		if ( $this->is_worker( $user_id ) && isset($this->options["allow_worker_wh"]) && 'yes' == $this->options["allow_worker_wh"] ) {
			bp_core_new_subnav_item( array(
				'name' => __( 'Appointments Settings', 'appointments' ),
				'slug' => 'appointment-settings',
				'parent_url' => $link,
				'parent_slug' => 'appointments',
				'screen_function' => array( &$this, 'tab_template_app_settings' )
			) );
		}
	}

	/**
     * Helper functions that BP requires
     */
	function tab_template() {
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	function tab_template_my_app() {
		add_action( 'bp_template_content', array( &$this, 'screen_content_my_app' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	function tab_template_app_settings() {
		add_action( 'bp_template_content', array( &$this, 'screen_content_app_settings' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
     * Generate content for my apps
     */
	function screen_content_my_app() {
		if ( isset( $this->options["gcal"] ) && 'yes' == $this->options["gcal"] )
			$gcal = ''; // Default is already enabled
		else
			$gcal = ' gcal="0"';

		$user_id = bp_loggedin_user_id();

		do_action( 'app_before_bp_my_appointments', $user_id );

		if ( !$this->is_worker( $user_id ) )
			echo do_shortcode("[app_my_appointments ".$gcal."]");
		else
			echo do_shortcode('[app_my_appointments status="paid,confirmed,pending" _allow_confirm=1 provider=1 '.$gcal.']');

		do_action( 'app_after_bp_my_appointments', $user_id );
	}

	/**
     * Generate content for app settings
     */
	function screen_content_app_settings() {
		// In the future we may use this function without BP too
		if ( function_exists( 'bp_loggedin_user_id') )
			$user_id = bp_loggedin_user_id();
		else {
			global $current_user;
			$user_id = $current_user->ID;
		}

		if ( $this->is_worker( $user_id ) && isset($this->options["allow_worker_wh"]) && 'yes' == $this->options["allow_worker_wh"] ) {
			// A little trick to pass correct lsw variables to the related function
			$_REQUEST["app_location_id"] = 0;
			$_REQUEST["app_provider_id"] = $user_id;

			$this->get_lsw();

			$result = array();
			$result_open = $this->get_exception( $this->location, $this->worker, 'open' );
			if ( $result_open )
				$result["open"] = $result_open->days;
			else
				$result["open"] = null;

			$result_closed = $this->get_exception( $this->location, $this->worker, 'closed' );
			if ( $result_closed )
				$result["closed"] = $result_closed->days;
			else
				$result["closed"] = null;

			do_action( 'app_before_bp_app_settings', $user_id );

			?>
			<div class="standard-form">
				<form method="post">
					<h4><?php _e('My Working Hours', 'appointments'); ?></h4>
					<?php echo $this->working_hour_form('open'); ?>
					<h4><?php _e('My Break Hours', 'appointments'); ?></h4>
					<?php echo $this->working_hour_form('closed'); ?>

					<h4><?php _e('My Exceptional Working Days', 'appointments'); ?></h4>

					<input class="datepick" id="open_datepick" type="text" style="width:100%" name="open[exceptional_days]" value="<?php if (isset($result["open"])) echo $result["open"]?>" />

					<h4><?php _e('My Holidays', 'appointments'); ?></h4>

					<input class="datepick" id="closed_datepick" type="text" style="width:100%" name="closed[exceptional_days]" value="<?php if (isset($result["closed"])) echo $result["closed"]?>" />


					<div class="submit">
						<input type="submit" name="app_bp_settings_submit" value="<?php _e('Save Changes', 'appointments')?>" class="auto">
						<input type="hidden" name="app_bp_settings_user" value="<?php echo $user_id ?>">
						<?php wp_nonce_field('app_bp_settings_submit','app_bp_settings_submit'); ?>
					</div>
				</form>
			</div>

			<?php
			do_action( 'app_after_bp_app_settings', $user_id );
		}
	}

/****************************************
* Methods for integration with Membership
*****************************************
*/

	/**
	 * Check if Membership plugin is active
	 */
	function check_membership_plugin() {
		if( ( is_admin() && class_exists('membershipadmin') ) || ( !is_admin() && class_exists('membershippublic') ) )
			$this->membership_active = true;
	}

	/**
	* Finds if user is Membership member with sufficient level
	* @return bool
	*/
	function is_member( ) {
		if ( $this->membership_active && isset( $this->options["members"] ) ) {
			global $current_user;
			$meta = maybe_unserialize( $this->options["members"] );
			$member = new M_Membership($current_user->ID);
			if( is_array( $meta ) && $current_user->ID > 0 && $member->has_levels()) {
				// Load the levels for this member
				$levels = $member->get_level_ids( );
				if ( is_array( $levels ) && is_array( $meta["level"] ) ) {
					foreach ( $levels as $level ) {
						if ( in_array( $level->level_id, $meta["level"] ) )
							return true; // Yes, user has sufficent level
					}
				}
			}
		}
		return false;
	}

/*****************************************
* Methods for integration with Marketpress
******************************************
*/

	/**
	 * Check if Marketpress plugin is active
	 * @Since 1.0.1
	 */
	function check_marketpress_plugin() {
		global $mp;
		if ( class_exists('MarketPress') && is_object( $mp ) ) {
			$this->marketpress_active = true;
			// Also check if it is activated
			if ( isset( $this->options["use_mp"] ) && $this->options["use_mp"] ) {
				$this->mp = true;
				add_action( 'manage_posts_custom_column', array(&$this, 'edit_products_custom_columns'), 1 );
				add_action( 'wp_ajax_nopriv_mp-update-cart', array(&$this, 'pre_update_cart'), 1 );
				add_action( 'wp_ajax_mp-update-cart', array(&$this, 'pre_update_cart'), 1 );
				add_action( 'wp', array(&$this, 'remove_from_cart_manual'), 1 );
				add_filter( 'the_content', array( &$this, 'product_page' ), 18 );
				add_action( 'mp_order_paid', array( &$this, 'handle_mp_payment' ) );
				add_filter( 'mp_product_list_meta', array( &$this, 'mp_product_list_meta' ), 10, 2 );
				add_filter( 'mp_order_notification_body', array( &$this, 'modify_email' ), 10, 2 );
				add_filter( 'mp_product_name_display_in_cart', array( &$this, 'modify_name' ), 10, 2 );
				add_filter( 'mp_buy_button_tag', array( &$this, 'mp_buy_button_tag' ), 10, 3 );
				return true;
			}
		}
		return false;
	}

	/**
	 * Remove duplicate buttons on Product List page and modify button text, also replace form with a link
	 * @param $button, $product_id, $context: See MarketPress
	 * @return string
	 * @Since 1.2.5
	 */
	function mp_buy_button_tag( $button, $product_id, $context ) {

		$book_now = apply_filters( 'app_mp_book_now', __('Choose Option &raquo;','appointments') );

		$product = get_post( $product_id );
		if ( 'list' != $context || !$this->is_app_mp_page( $product ) )
			return $button;

		if ( isset($_REQUEST['order'] ) ) {
			$button = preg_replace(
				'%<input class="mp_button_buynow"(.*?)value="(.*?)" />%is',
				'<input class="mp_button_buynow" type="submit" name="buynow" value="'.$book_now.'" />',
				$button
			);
			$button = preg_replace(
				'%<input class="mp_button_addcart"(.*?)value="(.*?)" />%is',
				'<input class="mp_button_buynow" type="submit" name="buynow" value="'.$book_now.'" />',
				$button
			);
			$button = preg_replace(
				'%<form(.*?)></form>%is',
				'<a class="mp_link_buynow" href="'.get_permalink($product_id).'">'.$book_now.'</a>',
				$button
			);

			return $button;
		}
		else return '';
	}

	/**
	 * Determine if a page is A+ Product page from the shortcodes used
	 * @param $product custom post object
	 * @return bool
	 * @Since 1.0.1
	 */
	function is_app_mp_page( $product ) {
		$result = false;
		if ( is_object( $product ) && strpos( $product->post_content, '[app_' ) !== false )
			$result = true;
		// Maybe required for templates
		return apply_filters( 'app_is_mp_page', $result, $product );
	}

	/**
	 * Hide column details for A+ products
	 * @Since 1.0.1
	 */
	function edit_products_custom_columns( $column ) {

		global $post, $mp;
		if ( !$this->is_app_mp_page( $post ) )
			return;
		if ( 'variations' == $column || 'sku' == $column || 'pricing' == $column ) {
			remove_action( 'manage_posts_custom_column', array($mp, 'edit_products_custom_columns') );
			echo '-';
		}
		else
			add_action( 'manage_posts_custom_column', array($mp, 'edit_products_custom_columns') );
	}

	/**
	 * Remove download link from confirmation email
	 * @Since 1.0.1
	 */
	function modify_email( $body, $order ) {

		if ( !is_object( $order ) || !is_array( $order->mp_cart_info ) )
			return $body;

		$order_id = $order->post_title; // Strange, but true :)

		foreach ( $order->mp_cart_info as $product_id=>$product_detail ) {
			$product = get_post( $product_id );
			// Find if this is an A+ product and change link if it is
			if ( $this->is_app_mp_page( $product ) )
				$body = str_replace( get_permalink( $product_id ) . "?orderid=$order_id", '-', $body );
		}

		// Addons may want to modify MP email
		return apply_filters( 'app_mp_email', $body, $order );
	}

	/**
	 * Modify display name in the cart
	 * @Since 1.0.1
	 */
	function modify_name( $name, $product_id ) {
		$product = get_post( $product_id );
		$var_names = get_post_meta( $product_id, 'mp_var_name', true );
		if ( !$this->is_app_mp_page( $product ) || !is_array( $var_names ) )
			return $name;

		list( $app_title, $app_id ) = split( ':', $name );
		if ( $app_id ) {
			global $wpdb;
			$result = $this->get_app( $app_id );
			if ( $result ) {
				$name = $name . " (". date_i18n( $this->datetime_format, strtotime( $result->start ) ) . ")";
				$name = apply_filters( 'app_mp_product_name_in_cart', $name, $this->get_service_name( $result->service ), $this->get_worker_name( $result->worker ), $result->start );
			}
		}
		return $name;
	}

	/**
	 * Handle after a successful Marketpress payment
	 * @Since 1.0.1
	 */
	function handle_mp_payment( $order ) {

		if ( !is_object( $order ) || !is_array( $order->mp_cart_info ) )
			return;

		foreach ( $order->mp_cart_info as $product_id=>$product_detail ) {
			$product = get_post( $product_id );
			// Find if this is an A+ product
			if ( $this->is_app_mp_page( $product ) && is_array( $product_detail ) ) {
				foreach( $product_detail as $var ) {
					// Find variation = app id which should also be downloadable
					if ( isset( $var['name'] ) && isset( $var['download'] ) ) {
						list( $product_name, $app_id ) = split( ':', $var['name'] );
						$app_id = (int)trim( $app_id );
						if ( $this->change_status( 'paid', $app_id ) ) {
							do_action( 'app_mp_order_paid', $app_id );
						}
					}
				}
			}
		}
	}

	/**
	 * Add to array of product pages where we have A+ shortcodes
	 * @Since 1.0.1
	 */
	function add_to_mp( $post_id ) {
		$this->mp_posts[] = $post_id;
	}

	/**
	 * If this is an A+ product page add js codes to footer to hide some MP fields
	 * @param content: post content
	 * @Since 1.0.1
	 */
	function product_page( $content ) {

		global $post;
		if ( is_object( $post ) && in_array( $post->ID, $this->mp_posts ) )
			$this->add2footer( '$(".mp_quantity,.mp_product_price,.mp_buy_form,.mp_product_variations,.appointments-paypal").hide();' );

		return $content;
	}

	/**
	 * Hide meta (Add to chart button, price) for an A+ product
	 * @Since 1.0.1
	 */
	function mp_product_list_meta( $meta, $post_id) {

		if ( in_array( $post_id, $this->mp_posts ) )
			return '<a class="mp_link_buynow" href="' . get_permalink($post_id) . '">' . __('Choose Option &raquo;', 'mp') . '</a>';
		else
			return	$meta;
	}

	/**
	 * Adds and returns a variation to the app product
	 * @Since 1.0.1
	 */
	function add_variation( $app_id, $post_id, $service, $worker, $start, $end ) {

		$meta = get_post_meta( $post_id, 'mp_var_name', true );
		// MP requires at least 2 variations, so we add a dummy one	if there is none
		if ( !$meta || !is_array( $meta ) ) {
			add_post_meta( $post_id, 'mp_var_name', array( 0 ) );
			add_post_meta( $post_id, 'mp_sku', array( 0 ) );

			// Find minimum service price here:
			global $wpdb;
			$min_price = $wpdb->get_var( "SELECT MIN(price) FROM " . $this->services_table . " WHERE price>0 " );
			if ( !$min_price )
				$min_price = 0;

			add_post_meta( $post_id, 'mp_price', array( $min_price ) );
			// Variation ID
			$meta = array( 0 );
		}

		$max = count( $meta );
		$meta[$max] = $app_id;
		update_post_meta( $post_id, 'mp_var_name', $meta );

		$sku = get_post_meta( $post_id, 'mp_sku', true );
		$sku[$max] = $this->service;
		update_post_meta( $post_id, 'mp_sku', $sku );

		$price = get_post_meta( $post_id, 'mp_price', true );
		$price[$max] = apply_filters( 'app_mp_price', $this->get_price( true ), $service, $worker, $start, $end ); // Filter added at V1.2.3.1
		update_post_meta( $post_id, 'mp_price', $price );

		// Add a download link, so that app will be a digital product
		$file = get_post_meta($post_id, 'mp_file', true);
		if ( !$file )
			add_post_meta( $post_id, 'mp_file', get_permalink( $post_id ) );

		return $max;
	}

	/**
	 * If a pending app is removed automatically, also remove it from the cart
	 * @Since 1.0.1
	 */
	function remove_from_cart( $app ) {
		global $mp;
		$changed = false;
		$cart = $mp->get_cart_cookie();

		if ( is_array( $cart ) ) {
			foreach ( $cart as $product_id=>$product_detail ) {
				$product = get_post( $product_id );
				$var_names = get_post_meta( $product_id, 'mp_var_name', true );
				// Find if this is an A+ product
				if ( $this->is_app_mp_page( $product ) && is_array( $product_detail ) && is_array( $var_names ) ) {
					foreach( $product_detail as $var_id=>$var_val ) {
						// Find variation = app id
						if ( isset( $var_names[$var_id] ) && $var_names[$var_id] == $app->ID ) {
							unset( $cart[$product_id] );
							$changed = true;
						}
					}
				}
			}
		}
		// Update cart only if something has changed
		if ( $changed )
			$mp->set_cart_cookie($cart);
	}

	/**
	 * Clear appointment that is removed from the cart also from the database
	 * This is called before MP
	 * @Since 1.0.1
	 */
	function remove_from_cart_manual( ) {

		if (isset($_POST['update_cart_submit'])) {
			if (isset($_POST['remove']) && is_array($_POST['remove'])) {
				foreach ($_POST['remove'] as $pbid) {
					list($bid, $product_id, $var_id) = split(':', $pbid);
					$product = get_post( $product_id );
					// Check if this is an app product page
					if ( $this->is_app_mp_page( $product ) ) {
						// We need to find var name = app_id
						$var_names = get_post_meta( $product_id, 'mp_var_name', true );
						if ( isset( $var_names[$var_id] ) ) {
							$this->change_status( 'removed', (int)trim( $var_names[$var_id] ) );
						}
					}
				}
			}
		}
	}

	/**
	 * Add the appointment to the cart
	 * This is called before MP
	 * @Since 1.0.1
	 */
	function pre_update_cart( ) {
		global $mp;

		if ( isset( $_POST['product_id'] )  && isset( $_POST['variation'] ) && $_POST['product_id'] && $_POST['variation'] ) {
			$product_id = $_POST['product_id'];
			$product = get_post( $product_id );
			// Check if this is an app product page
			if ( $this->is_app_mp_page( $product ) ) {
				$variation = $_POST['variation'];

				$cart = $mp->get_cart_cookie();
				if ( !is_array( $cart ) )
					$cart = array();

				// Make quantity 0 so that MP can set it to 1
				$cart[$product_id][$variation] = 0;

				//save items to cookie
				$mp->set_cart_cookie($cart);

				// Set email to SESSION variables if not set before
				if ( !isset( $_SESSION['mp_shipping_info']['email'] ) && isset( $_COOKIE["wpmudev_appointments_userdata"] ) ) {
					$data = unserialize( stripslashes( $_COOKIE["wpmudev_appointments_userdata"] ) );
					if ( is_array( $data ) && isset( $data["e"] ) )
						@$_SESSION['mp_shipping_info']['email'] = $data["e"];
				}
			}
		}
	}

/*******************************
* Methods for inits, styles, js
********************************
*/

	/**
     * Find blogs and install tables for each of them
	 * @since 1.0.2
     */
	function install() {
		global $wpdb;

		if ( function_exists('is_multisite') && is_multisite() ) {
				$network = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : "";
				$activate = isset($_GET['action']) ? $_GET['action'] : "";
				$is_network = ($network=='/wp-admin/network/plugins.php') ? true:false;
				$is_activation = ($activate=='deactivate') ? false:true;

			if ($is_network && $is_activation){
				$old_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					Appointments::_install();
				}
				switch_to_blog($old_blog);
				return;
			}
		}
		Appointments::_install();
	}

	/**
     * Install database tables
     */
	function _install() {
		// Create a salt, if it doesn't exist from the previous installation
		if ( !$salt = get_option( "appointments_salt" ) ) {
			$salt = mt_rand();
			add_option( "appointments_salt", $salt ); // Save it to be used until it is cleared manually
		}

		global $wpdb;

		$sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "app_appointments" . "` (
		`ID` bigint(20) unsigned NOT NULL auto_increment,
		`created` datetime,
		`user` bigint(20) NOT NULL default '0',
		`name` varchar(250) default NULL,
		`email` varchar(250) default NULL,
		`phone` varchar(250) default NULL,
		`address` varchar(250) default NULL,
		`city` varchar(250) default NULL,
		`location` bigint(20) NOT NULL default '0',
		`service` bigint(20) NOT NULL default '0',
		`worker` bigint(20) NOT NULL default '0',
		`price` bigint(20) default NULL,
		`status` varchar(35) default NULL,
		`start` datetime default NULL,
		`end` datetime default NULL,
		`sent` text,
		`sent_worker` text,
		`note` text,
		`gcal_ID` varchar(250) default NULL,
		`gcal_updated` datetime,
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		// V1.2.0: New gcal field
		$sql_ = "ALTER TABLE `" .$wpdb->prefix . "app_appointments". "` ADD (`gcal_ID` varchar(250) default NULL, `gcal_updated` datetime default NULL) ";

		// V1.2.2: make gcal_ID unique
		$sql_0 = "ALTER TABLE `" .$wpdb->prefix . "app_appointments". "` ADD UNIQUE (`gcal_ID`) ";

		$sql1 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "app_transactions" . "` (
		`transaction_ID` bigint(20) unsigned NOT NULL auto_increment,
		`transaction_app_ID` bigint(20) NOT NULL default '0',
		`transaction_paypal_ID` varchar(30) default NULL,
		`transaction_stamp` bigint(35) NOT NULL default '0',
		`transaction_total_amount` bigint(20) default NULL,
		`transaction_currency` varchar(35) default NULL,
		`transaction_status` varchar(35) default NULL,
		`transaction_note` text,
		PRIMARY KEY  (`transaction_ID`),
		KEY `transaction_app_ID` (`transaction_app_ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$sql2 = "CREATE TABLE IF NOT EXISTS `" .$wpdb->prefix . "app_working_hours" . "` (
		`ID` bigint(20) unsigned NOT NULL auto_increment,
		`location` bigint(20) NOT NULL default '0',
		`service` bigint(20) NOT NULL default '0',
		`worker` bigint(20) NOT NULL default '0',
		`status` varchar(30) default NULL,
		`hours` text,
		`note` text,
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		// TODO: Make this WP time format free
		$sql21 = "INSERT INTO " .$wpdb->prefix . "app_working_hours (ID, location, worker,  `status`, hours, note) VALUES
		(NULL, 0, 0, 'open', 'a:7:{s:6:\"Sunday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"1:00 pm\";}s:6:\"Monday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:7:\"Tuesday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:9:\"Wednesday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:8:\"Thursday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:6:\"Friday\";a:3:{s:6:\"active\";s:3:\"yes\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"5:00 pm\";}s:8:\"Saturday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:7:\"8:00 am\";s:3:\"end\";s:7:\"1:00 pm\";}}', NULL),
		(NULL, 0, 0, 'closed', 'a:7:{s:6:\"Sunday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:6:\"Monday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:7:\"Tuesday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:9:\"Wednesday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:8:\"Thursday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:6:\"Friday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}s:8:\"Saturday\";a:3:{s:6:\"active\";s:2:\"no\";s:5:\"start\";s:8:\"12:00 pm\";s:3:\"end\";s:7:\"1:00 pm\";}}', NULL);
		";

		$sql3 = "CREATE TABLE IF NOT EXISTS `" .$wpdb->prefix . "app_exceptions" . "` (
		`ID` bigint(20) unsigned NOT NULL auto_increment,
		`location` bigint(20) NOT NULL default '0',
		`service` bigint(20) NOT NULL default '0',
		`worker` bigint(20) NOT NULL default '0',
		`status` varchar(30) default NULL,
		`days` text,
		`note` text,
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";


		$sql4 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "app_services" . "` (
		`ID` bigint(20) unsigned,
		`name` varchar(255) default NULL,
		`capacity` bigint(20) NOT NULL default '0',
		`duration` bigint(20) NOT NULL default '0',
		`price` varchar(255) default NULL,
		`page` bigint(20) NOT NULL default '0',
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$sql41 = "INSERT INTO " . $wpdb->prefix . "app_services (ID, `name`, capacity, duration, `price`, page)
		VALUES (1, 'Default Service', 0, 30, '1', 0)
		";

		$sql5 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "app_workers" . "` (
		`ID` bigint(20) unsigned,
		`dummy` varchar(255) default NULL,
		`price` varchar(255) default NULL,
		`services_provided` text,
		`page` bigint(20) NOT NULL default '0',
		PRIMARY KEY  (`ID`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		// V1.0.6: New dummy field
		$sql51 = "ALTER TABLE `" .$wpdb->prefix . "app_workers". "` ADD `dummy` varchar(255) DEFAULT NULL AFTER `ID` ";

		$sql6 = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "app_cache" . "` (
		`uri` varchar(255) default NULL,
		`created` datetime,
		`content` longtext,
		`script` longtext,
		 UNIQUE (`uri`)
		)
		DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

		$wpdb->query($sql);
		$wpdb->query($sql1);
		// Add default working hours
		$wpdb->query($sql2);
		$count = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->prefix . "app_working_hours " );
		if ( !$count )
			$wpdb->query($sql21);
		$wpdb->query($sql3);
		// Add default service
		$wpdb->query($sql4);
		$count = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->prefix . "app_services " );
		if ( !$count )
			$wpdb->query($sql41);
		$wpdb->query($sql5);
		$wpdb->query($sql6);

		// If no DB version, this is a new installation
		if ( !$db_version = get_option( 'app_db_version' ) ) {
			$db_version = "1.2.2";
			update_option( 'app_db_version', '1.2.2' );
		}

		// Update database for versions less than 1.0.6
		if ( version_compare( $db_version, '1.0.6', '<' ) ) {
			$result = $wpdb->query($sql51);
			if ( $result || $wpdb->query( "SHOW COLUMNS FROM " . $wpdb->prefix . "app_workers" . " LIKE 'dummy' " ) )
				update_option( 'app_db_version', '1.0.6' );
		}
		// Check and update database for versions less than 1.1.8
		if( version_compare( $db_version, '1.1.8', '<' ) ) {
			$table_status = $wpdb->get_row( "SHOW TABLE STATUS LIKE '" . $wpdb->prefix . "app_services' " );

			if ( 'utf8_general_ci' != $table_status->Collation ) {
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_appointments" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_transactions" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_working_hours" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_exceptions" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_services" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_workers" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
				$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "app_cache" . "` CHARACTER SET utf8 COLLATE utf8_general_ci " );
			}
			update_option( 'app_db_version', '1.1.8' );
		}
		// Update database for versions less than 1.2.0
		if ( version_compare( $db_version, '1.2.0', '<' ) ) {
			$result = $wpdb->query($sql_);
			if ( $result || $wpdb->query( "SHOW COLUMNS FROM " . $wpdb->prefix . "app_appointments" . " LIKE 'gcal_ID' " ) )
				update_option( 'app_db_version', '1.2.0' );
		}

		// Update database for versions less than 1.2.2
		if ( version_compare( $db_version, '1.2.2', '<' ) ) {
			$result = $wpdb->query($sql_0);
			update_option( 'app_db_version', '1.2.2' );
		}
	}

	/**
	 * Install tables for new blog
	 * @since 1.0.2
	 */
	function new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		global $wpdb;

		if ( !function_exists( 'is_plugin_active_for_network' ) )
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		if ( is_plugin_active_for_network('appointments/appointments.php') ) {
			$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			Appointments::_install();
			switch_to_blog( $old_blog );
		}
	}

	/**
	 * Remove tables for a deleted blog
	 * @since 1.0.2
	 */
	function delete_blog( $blog_id, $drop ) {
		global $wpdb;

		if ( $blog_id >1 ) {
			$old_blog = $wpdb->blogid;
			switch_to_blog( $blog_id );
			_wpmudev_appointments_uninstall( );
			switch_to_blog( $old_blog );
		}
	}

	/**
	 * Initialize widgets
	 */
	function widgets_init() {
		if ( !is_blog_installed() )
			return;

		register_widget( 'Appointments_Widget_Services' );
		register_widget( 'Appointments_Widget_Service_Providers' );
		register_widget( 'Appointments_Widget_Monthly_Calendar' );
	}

	/**
	 * Add a script to be used in the footer, checking duplicates
	 * In some servers, footer scripts were called twice. This function fixes it.
	 * @since 1.2.0
	 */
	function add2footer( $script='' ) {

		if ( $script && strpos( $this->script, $script ) === false )
			$this->script = $this->script . $script;
	}

	/**
	 * Load javascript to the footer
	 */
	function wp_footer() {
		$script = '';
		$this->script = apply_filters( 'app_footer_scripts', $this->script );

		if ( $this->script ) {
			$script .= '<script type="text/javascript">';
			$script .= "jQuery(document).ready(function($) {";
			$script .= $this->script;
			$script .= "});</script>";
		}

		echo $this->esc_rn( $script );
	}

	/**
	 * Load style and script only when they are necessary
	 * http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/
	 */
	function load_styles( $posts ) {
		if ( empty($posts) || is_admin() )
			return $posts;

		$this->shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
		foreach ( $posts as $post ) {
			if ( is_object( $post ) && stripos( $post->post_content, '[app_' ) !== false ) {
				$this->shortcode_found = true;
				$this->add_to_cache( $post->ID );
				// Don't go further if MP is not active, this may save some time for archive pages
				if ( !$this->mp )
					break;
				// Also add to A+ product posts
				if ( 'product' == $post->post_type )
					$this->add_to_mp( $post->ID );
			}
		}

		if ( $this->shortcode_found )
			$this->load_scripts_styles( );

		return $posts;
	}

	/**
	 * Function to load all necessary scripts and styles
	 * Can be called externally, e.g. when forced from a page template
	 */
	function load_scripts_styles( ) {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-tablesorter', $this->plugin_url . '/js/jquery.tablesorter.min.js', array('jquery'), $this->version );
		add_action( 'wp_footer', array( &$this, 'wp_footer' ) );	// Publish plugin specific scripts in the footer

		// TODO: consider this
		wp_enqueue_script( 'app-js-check', $this->plugin_url . '/js/js-check.js', array('jquery'), $this->version);
		wp_localize_script( 'app-js-check', '_appointments_data',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'root_url' => plugins_url('appointments/images/')
				)
		);

		if ( !current_theme_supports( 'appointments_style' ) ) {
			wp_enqueue_style( "appointments", $this->plugin_url. "/css/front.css", array(), $this->version );
			add_action( 'wp_head', array( &$this, 'wp_head' ) );
		}

		// wpautop does strange things to cache content, so remove it first and add to output
		if ( $this->use_cache() ) {
			if ( has_filter( 'wpautop' ) ) {
				$this->had_filter = true;
			}
			remove_filter( 'the_content', 'wpautop' );
			remove_filter( 'the_excerpt', 'wpautop' );
		}

		do_action('app-scripts-general');

		// Prevent external caching plugins for this page
		if ( !defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', true );
		// Prevent W3T Minify
		if ( !defined( 'DONOTMINIFY' ) )
			define( 'DONOTMINIFY', true );

		// Load the rest only if API use is selected
		if (@$this->options['accept_api_logins']) {
			wp_enqueue_script('appointments_api_js', $this->plugin_url . '/js/appointments-api.js', array('jquery'), $this->version );
			wp_localize_script('appointments_api_js', 'l10nAppApi', apply_filters('app-scripts-api_l10n', array(
				'facebook' => __('Login with Facebook', 'appointments'),
				'twitter' => __('Login with Twitter', 'appointments'),
				'google' => __('Login with Google+', 'appointments'),
				'wordpress' => __('Login with WordPress', 'appointments'),
				'submit' => __('Submit', 'appointments'),
				'cancel' => __('Cancel', 'appointments'),
				'please_wait' => __('Please, wait...', 'appointments'),
				'logged_in' => __('You are now logged in', 'appointments'),
				'error' => __('Login error. Please try again.', 'appointments'),
				'_can_use_twitter' => (!empty($this->options['twitter-app_id']) && !empty($this->options['twitter-app_secret'])),
			)));

			if (!empty($this->options['facebook-app_id'])) {
				if (!$this->options['facebook-no_init']) {
					add_action('wp_footer', create_function('', "echo '" .
					sprintf(
						'<div id="fb-root"></div><script type="text/javascript">
						window.fbAsyncInit = function() {
							FB.init({
							  appId: "%s",
							  status: true,
							  cookie: true,
							  xfbml: true
							});
						};
						// Load the FB SDK Asynchronously
						(function(d){
							var js, id = "facebook-jssdk"; if (d.getElementById(id)) {return;}
							js = d.createElement("script"); js.id = id; js.async = true;
							js.src = "//connect.facebook.net/en_US/all.js";
							d.getElementsByTagName("head")[0].appendChild(js);
						}(document));
						</script>',
						$this->options['facebook-app_id']
					) .
					"';"));
				}
			}
			do_action('app-scripts-api');
		}
	}

	/**
	 * css that will be added to the head, again only for app pages
	 */
	function wp_head() {

		?>
		<style type="text/css">
		<?php

		if ( isset( $this->options["additional_css"] ) && '' != trim( $this->options["additional_css"] ) ) {
			echo $this->options['additional_css'];
		}

		foreach ( $this->get_classes() as $class=>$name ) {
			if ( !isset( $this->options["color_set"] ) || !$this->options["color_set"] ) {
				if ( isset( $this->options[$class."_color"] ) )
					$color = $this->options[$class."_color"];
				else
					$color = $this->get_preset( $class, 1 );
			}
			else
				$color = $this->get_preset( $class, $this->options["color_set"] );

			echo 'td.'.$class.',div.'.$class.' {background: #'. $color .' !important;}';
		}

		// Don't show Google+ button if openid is not enabled
		if ( !@$this->openid )
			echo '.appointments-login_link-google{display:none !important;}';
		?>
		</style>
		<?php
	}

	/**
     * Localize the plugin
     */
	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in Appointments+'s "languages" folder and name it "appointments-[value in wp-config].mo"
		load_plugin_textdomain( 'appointments', false, '/appointments/languages/' );
	}

	/**
	 *	Add initial settings
	 *
	 */
	function init() {

		// Since wp-cron is not reliable, use this instead
		add_option( "app_last_update", time() );

$confirmation_message = "Dear CLIENT,

We are pleased to confirm your appointment for SITE_NAME.

Here are the appointment details:
Requested service: SERVICE
Date and time: DATE_TIME

SERVICE_PROVIDER will assist you for this service.

Kind regards,
SITE_NAME
";

$reminder_message = "Dear CLIENT,

We would like to remind your appointment with SITE_NAME.

Here are your appointment details:
Requested service: SERVICE
Date and time: DATE_TIME

SERVICE_PROVIDER will assist you for this service.

Kind regards,
SITE_NAME
";
		add_option( 'appointments_options', array(
													'min_time'					=> 30,
													'additional_min_time'		=> '',
													'admin_min_time'			=> '',
													'app_lower_limit'			=> 0,
													'app_limit'					=> 365,
													'clear_time'				=> 60,
													'spam_time'					=> 0,
													'auto_confirm'				=> 'no',
													'allow_worker_selection'	=> 'no',
													'allow_worker_confirm'		=> 'no',
													'allow_overwork'			=> 'no',
													'allow_overwork_break'		=> 'no',
													'dummy_assigned_to'			=> 0,
													'app_page_type'				=> 'monthly',
													'accept_api_logins'			=> '',
													'facebook-app_id'			=> '',
													'twitter-app_id'			=> '',
													'twitter-app_secret'		=> '',
													'show_legend'				=> 'yes',
													'gcal'						=> 'yes',
													'gcal_location'				=> '',
													'color_set'					=> 1,
													'free_color'				=> '48c048',
													'busy_color'				=> 'ffffff',
													'notpossible_color'			=> 'ffffff',
													'make_an_appointment'		=> '',
													'ask_name'					=> '1',
													'ask_email'					=> '1',
													'ask_phone'					=> '1',
													'ask_address'				=> '',
													'ask_city'					=> '',
													'ask_note'					=> '',
													'additional_css'			=> '.entry-content td{border:none;width:50%}',
													'payment_required'			=> 'no',
													'percent_deposit'			=> '',
													'fixed_deposit'				=> '',
													'currency'					=> 'USD',
													'mode'						=> 'sandbox',
													'merchant_email'			=> '',
													'return'					=> 1,
													'login_required'			=> 'no',
													'send_confirmation'			=> 'yes',
													'send_notification'			=> 'no',
													'send_reminder'				=> 'yes',
													'reminder_time'				=> '24',
													'send_reminder_worker'		=> 'yes',
													'reminder_time_worker'		=> '4',
													'confirmation_subject'		=> __('Confirmation of your Appointment','appointments'),
													'confirmation_message'		=> $confirmation_message,
													'reminder_subject'			=> __('Reminder for your Appointment','appointments'),
													'reminder_message'			=> $reminder_message,
													'log_emails'				=> 'yes',
													'use_cache'					=> 'no',
													'use_mp'					=> false,
													'allow_cancel'				=> 'no',
													'cancel_page'				=> 0
										)
		);

		//  Run this code not before 10 mins
		if ( ( time( ) - get_option( "app_last_update" ) ) < apply_filters( 'app_update_time', 600 ) )
			return;
		$this->remove_appointments();
		$this->send_reminder();
		$this->send_reminder_worker();
		// Update Google API imports
		if ( is_object( $this->gcal_api ) )
			$this->gcal_api->import_and_update();
	}

/*******************************
* Methods for Confirmation
********************************

	/**
	 *	Send confirmation email
	 *  @param app_id: ID of the app whose confirmation will be sent
	 */
	function send_confirmation( $app_id ) {
		if ( !isset( $this->options["send_confirmation"] ) || 'yes' != $this->options["send_confirmation"] )
			return;
		global $wpdb;
		$r = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE ID=".$app_id." " );
		if ( $r != null ) {

			$_REQUEST["app_location_id"] = 0;
			$_REQUEST["app_service_id"] = $r->service;
			$_REQUEST["app_provider_id"] = $r->worker;

			$body = apply_filters( 'app_confirmation_message', $this->add_cancel_link( $this->_replace( $this->options["confirmation_message"],
					$r->name, $this->get_service_name( $r->service), $this->get_worker_name( $r->worker), $r->start, $r->price,
					$this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email, $r->city ), $app_id ), $r, $app_id );

			$mail_result = wp_mail(
						$r->email,
						$this->_replace( $this->options["confirmation_subject"], $r->name,
							$this->get_service_name( $r->service), $this->get_worker_name( $r->worker),
							$r->start, $r->price, $this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email, $r->city ),
						$body,
						$this->message_headers( ),
						apply_filters( 'app_confirmation_email_attachments', '' )
					);
			if ( $r->email && $mail_result ) {
				// Log only if it is set so
				if ( isset( $this->options["log_emails"] ) && 'yes' == $this->options["log_emails"] )
					$this->log( sprintf( __('Confirmation message sent to %s for appointment ID:%s','appointments'), $r->email, $app_id ) );

				do_action( 'app_confirmation_sent', $body, $r, $app_id );

				// Allow disabling of confirmation email to admin
				$disable = apply_filters( 'app_confirmation_disable_admin', false, $r, $app_id );
				if ( $disable )
					return;

				//  Send a copy to admin and service provider
				$to = array( $this->get_admin_email( ) );

				$worker_email = $this->get_worker_email( $r->worker );
				if ( $worker_email )
					$to[]= $worker_email;

				$provider_add_text  = sprintf( __('A new appointment has been made on %s. Below please find a copy of what has been sent to your client:', 'appointments'), get_option( 'blogname' ) );
				$provider_add_text .= "\n\n\n";

				wp_mail(
						$to,
						$this->_replace( __('New Appointment','appointments'), $r->name, $this->get_service_name( $r->service), $this->get_worker_name( $r->worker),
							$r->start, $r->price, $this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email, $r->city ),
						$provider_add_text . $body,
						$this->message_headers( )
					);
			}
		}
	}

	/**
	 * Send notification email
	 * @param cancel: If this is a cancellation
	 * @since 1.0.2
	 */
	function send_notification( $app_id, $cancel=false ) {
		// In case of cancellation, continue
		if ( !$cancel && !isset( $this->options["send_notification"] ) || 'yes' != $this->options["send_notification"] )
			return;
		global $wpdb;
		$r = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE ID=".$app_id." " );
		if ( $r != null ) {

			$admin_email = apply_filters( 'app_notification_email', $this->get_admin_email( ), $r );

			if ( $cancel ) {
				$subject = __('An appointment has been cancelled', 'appointments');
				$body = sprintf( __('Appointment with ID %s has been cancelled by the client. You can see it clicking this link: %s','appointments'), $app_id, admin_url("admin.php?page=appointments&type=removed") );
			}
			else {
				$subject = __('An appointment requires your confirmation', 'appointments');
				$body = sprintf( __('The new appointment has an ID %s and you can edit it clicking this link: %s','appointments'), $app_id, admin_url("admin.php?page=appointments&type=pending") );
			}
			$body = apply_filters( 'app_notification_message', $body, $r, $app_id );

			$mail_result = wp_mail(
					$admin_email,
					$subject,
					$body,
					$this->message_headers( )
				);

			if ( $mail_result && isset( $this->options["log_emails"] ) && 'yes' == $this->options["log_emails"] ) {
				$this->log( sprintf( __('Notification message sent to %s for appointment ID:%s','appointments'), $admin_email, $app_id ) );
				do_action( 'app_notification_sent', $body, $r, $app_id );
			}

			// Also notify service provider if he is allowed to confirm it
			// Note that message itself is different from that of the admin
			// Don't send repeated email to admin if he is the provider
			if ( $r->worker &&  $admin_email != $this->get_worker_email( $r->worker ) && isset( $this->options['allow_worker_confirm'] ) && 'yes' == $this->options['allow_worker_confirm'] ) {

				if ( $cancel ) {
				/* Translators: First %s is for appointment ID and the second one is for date and time of the appointment */
					$body = sprintf( __('Cancelled appointment has an ID %s for %s.','appointments'), $app_id, date_i18n( $this->datetime_format, strtotime($r->start) ) );
				}
				else
					$body = sprintf( __('The new appointment has an ID %s for %s and you can confirm it using your profile page.','appointments'), $app_id, date_i18n( $this->datetime_format, strtotime($r->start) ) );

				$mail_result = wp_mail(
					$this->get_worker_email( $r->worker ),
					$subject,
					$body,
					$this->message_headers( )
				);

				if ( $mail_result && isset( $this->options["log_emails"] ) && 'yes' == $this->options["log_emails"] )
					$this->log( sprintf( __('Notification message sent to %s for appointment ID:%s','appointments'), $this->get_worker_email( $r->worker ), $app_id ) );
			}
		}
	}

	/**
	 *	Check and send reminders to clients for appointments
	 *
	 */
	function send_reminder() {
		if ( !isset( $this->options["reminder_time"] ) || !$this->options["reminder_time"] || 'yes' != $this->options["send_reminder"] )
			return;

		$hours = explode( "," , trim( $this->options["reminder_time"] ) );

		if ( !is_array( $hours ) || empty( $hours ) )
			return;

		global $wpdb;

		$messages = array();
		foreach ( $hours as $hour ) {
			$results = $wpdb->get_results( "SELECT * FROM " . $this->app_table . "
				WHERE (status='paid' OR status='confirmed')
				AND (sent NOT LIKE '%:".trim($hour).":%' OR sent IS NULL)
				AND DATE_ADD('".date( 'Y-m-d H:i:s', $this->local_time )."', INTERVAL ".$hour." HOUR) > start " );

			if ( $results ) {
				foreach ( $results as $r ) {
					$_REQUEST["app_location_id"] = 0;
					$_REQUEST["app_service_id"] = $r->service;
					$_REQUEST["app_provider_id"] = $r->worker;

					$messages[] = array(
								'ID'		=> $r->ID,
								'to'		=> $r->email,
								'subject'	=> $this->_replace( $this->options["reminder_subject"], $r->name, $this->get_service_name( $r->service),
									$this->get_worker_name( $r->worker), $r->start, $r->price, $this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email, $r->city ),
								'message'	=> apply_filters( 'app_reminder_message', $this->add_cancel_link( $this->_replace( $this->options["reminder_message"],
									$r->name, $this->get_service_name( $r->service), $this->get_worker_name( $r->worker), $r->start,
									$r->price, $this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email, $r->city ), $app_id ), $r, $app_id )
							);
					// Update "sent" field
					$wpdb->update( $this->app_table,
									array( 'sent'	=> rtrim( $r->sent, ":" ) . ":" . trim( $hour ) . ":" ),
									array( 'ID'		=> $r->ID ),
									array ( '%s' )
								);
				}
			}
		}
		// Remove duplicates
		$messages = $this->array_unique_by_ID( $messages );
		if ( is_array( $messages ) && !empty( $messages ) ) {
			foreach ( $messages as $message ) {
				$mail_result = wp_mail( $message["to"], $message["subject"], $message["message"], $this->message_headers(), apply_filters( 'app_reminder_email_attachments', '' ) );
				if ( $mail_result && isset( $this->options["log_emails"] ) && 'yes' == $this->options["log_emails"] )
					$this->log( sprintf( __('Reminder message sent to %s for appointment ID:%s','appointments'), $message["to"], $message["ID"] ) );
			}
		}
	}

	/**
	 *	Remove duplicate messages by app ID
	 */
	function array_unique_by_ID( $messages ) {
		if ( !is_array( $messages ) || empty( $messages ) )
			return false;
		$idlist = array();
		// Save array to a temp area
		$result = $messages;
		foreach ( $messages as $key=>$message ) {
			if ( in_array( $message['ID'], $idlist ) )
				unset( $result[$key] );
			else
				$idlist[] = $message['ID'];
		}
		return $result;
	}

	/**
	 *	Check and send reminders to worker for appointments
	 */
	function send_reminder_worker() {
		if ( !isset( $this->options["reminder_time_worker"] ) || !$this->options["reminder_time_worker"] || 'yes' != $this->options["send_reminder_worker"] )
			return;

		$hours = explode( "," , $this->options["reminder_time_worker"] );

		if ( !is_array( $hours ) || empty( $hours ) )
			return;

		global $wpdb;

		$messages = array();
		foreach ( $hours as $hour ) {
			$results = $wpdb->get_results( "SELECT * FROM " . $this->app_table . "
				WHERE (status='paid' OR status='confirmed')
				AND worker <> 0
				AND (sent_worker NOT LIKE '%:".trim($hour).":%' OR sent_worker IS NULL)
				AND DATE_ADD('".date( 'Y-m-d H:i:s', $this->local_time )."', INTERVAL ".$hour." HOUR) > start " );

			$provider_add_text  = __('You are receiving this reminder message for your appointment as a provider. The below is a copy of what may have been sent to your client:', 'appointments');
			$provider_add_text .= "\n\n\n";

			if ( $results ) {
				foreach ( $results as $r ) {
					$_REQUEST["app_location_id"] = 0;
					$_REQUEST["app_service_id"] = $r->service;
					$_REQUEST["app_provider_id"] = $r->worker;

					$messages[] = array(
								'ID'		=> $r->ID,
								'to'		=> $this->get_worker_email( $r->worker ),
								'subject'	=> $this->_replace( $this->options["reminder_subject"], $r->name, $this->get_service_name($r->service),
									$this->get_worker_name($r->worker), $r->start, $r->price, $this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email ),
								'message'	=> $provider_add_text . $this->_replace( $this->options["reminder_message"], $r->name,
									$this->get_service_name( $r->service), $this->get_worker_name( $r->worker), $r->start, $r->price,
									$this->get_deposit($r->price), $r->phone, $r->note, $r->address, $r->email )
							);
					// Update "sent" field
					$wpdb->update( $this->app_table,
									array( 'sent_worker' => rtrim( $r->sent_worker, ":" ) . ":" . trim( $hour ) . ":" ),
									array( 'ID'		=> $r->ID ),
									array ( '%s' )
								);
				}
			}
		}
		// Remove duplicates
		$messages = $this->array_unique_by_ID( $messages );
		if ( is_array( $messages ) && !empty( $messages ) ) {
			foreach ( $messages as $message ) {
				$mail_result = wp_mail( $message["to"], $message["subject"], $message["message"], $this->message_headers() );
				if ( $mail_result && isset( $this->options["log_emails"] ) && 'yes' == $this->options["log_emails"] )
					$this->log( sprintf( __('Reminder message sent to %s for appointment ID:%s','appointments'), $message["to"], $message["ID"] ) );
			}
		}
	}

	/**
	 *	Replace placeholders with real values for email subject and content
	 */
	function _replace( $text, $user, $service, $worker, $datetime, $price, $deposit, $phone, $note, $address, $email, $city ) {
		/*
		return str_replace(
					array( "SITE_NAME", "CLIENT", "SERVICE_PROVIDER", "SERVICE", "DATE_TIME", "PRICE", "DEPOSIT", "PHONE", "NOTE", "ADDRESS", "EMAIL", "CITY" ),
					array( wp_specialchars_decode(get_option('blogname'), ENT_QUOTES), $user, $worker, $service, mysql2date( $this->datetime_format, $datetime ), $price, $deposit, $phone, $note, $address, $email, $city ),
					$text
				);
		*/
		$balance = !empty($price) && !empty($deposit)
			? (float)$price - (float)$deposit
			: (!empty($price) ? $price : 0.0)
		;
		$replacement = array(
			'SITE_NAME' => wp_specialchars_decode(get_option('blogname'), ENT_QUOTES),
			'CLIENT' => $user,
			'SERVICE_PROVIDER' => $worker,
			'SERVICE' => $service,
			'DATE_TIME' => mysql2date($this->datetime_format, $datetime),
			'PRICE' => $price,
			'DEPOSIT' => $deposit,
			'BALANCE' => $balance,
			'PHONE' => $phone,
			'NOTE' => $note,
			'ADDRESS' => $address,
			'EMAIL' => $email,
			'CITY' => $city,
		);
		foreach($replacement as $macro => $repl) {
			$text = preg_replace('/' . preg_quote($macro, '/') . '/U', $repl, $text);
		}
		return $text;
	}

	/**
	 *	Email message headers
	 */
	function message_headers( ) {
		$admin_email = $this->get_admin_email();
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$content_type = apply_filters('app-emails-content_type', 'text/plain');
		if (!(defined('APP_EMAIL_DROP_LEGACY_HEADERS') && APP_EMAIL_DROP_LEGACY_HEADERS)) {
			$message_headers = "MIME-Version: 1.0\n" . "From: {$blogname}" .  " <{$admin_email}>\n" . "Content-Type: {$content_type}; charset=\"" . get_option('blog_charset') . "\"\n";
		} else {
			$message_headers = "MIME-Version: 1.0\n" .
				"Content-Type: {$content_type}; charset=\"" . get_option('blog_charset') . "\"\n"
			;
			add_filter('wp_mail_from', create_function('', "return '{$admin_email}';"));
			add_filter('wp_mail_from_name', create_function('', "return '{$blogname}';"));
		}
		// Modify message headers
		$message_headers = apply_filters( 'app_message_headers', $message_headers );

		return $message_headers;
	}

	/**
	 *	Remove an appointment if not paid or expired
	 *	Clear expired appointments.
	 *	Change status to completed if they are confirmed or paid
	 *	Change status to removed if they are pending or reserved
	 */
	function remove_appointments( ) {

		global $wpdb;

		$expireds = $wpdb->get_results( "SELECT * FROM " . $this->app_table . " WHERE start<'" . date ("Y-m-d H:i:s", $this->local_time ). "' " );
		if ( $expireds ) {
			foreach ( $expireds as $expired ) {
				if ( 'pending' == $expired->status || 'reserved' == $expired->status )
					$new_status = 'removed';
				else if ( 'confirmed' == $expired->status || 'paid' == $expired->status )
					$new_status = 'completed';
				else
					$new_status = $expired->status; // Do nothing ??
				$update = $wpdb->update( $this->app_table,
								array( 'status'	=> $new_status ),
								array( 'ID'	=> $expired->ID )
							);
				if ( $update )
					do_action( 'app_remove_expired', $expired, $new_status );
			}
		}

		// Clear appointments that are staying in pending status long enough
		if ( isset( $this->options["clear_time"] ) && $this->options["clear_time"] > 0 ) {
			$clear_secs = $this->options["clear_time"] * 60;
			$expireds = $wpdb->get_results( "SELECT * FROM " . $this->app_table . " WHERE status='pending' AND created<'" . date ("Y-m-d H:i:s", $this->local_time - $clear_secs ). "' " );
			if ( $expireds ) {
				foreach ( $expireds as $expired ) {
					$update = $wpdb->update( $this->app_table,
									array( 'status'	=> 'removed' ),
									array( 'ID'	=> $expired->ID )
								);
					if ( $update ) {
						do_action( 'app_remove_pending', $expired );
						if ( $this->mp )
							$this->remove_from_cart( $expired );
					}
				}
			}
		}
		update_option( "app_last_update", time() );

		// Appointment status probably changed, so clear cache.
		// Anyway it is good to clear the cache in certain intervals.
		// This can be removed for pages with very heavy visitor traffic, but little appointments
		$this->flush_cache();
	}

	/**
	 * Handle cancellation of an appointment by the client
	 * @since 1.2.6
	 */
	function cancel() {

		if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] ) {

			/* Cancel by the link in email */
			// We don't want to break any other plugin's init, so these conditions are very strict
			if ( isset( $_GET['app_cancel'] ) && isset( $_GET['app_id'] ) && isset( $_GET['app_nonce'] ) ) {

				$app_id = $_GET['app_id'];
				$app = $this->get_app( $app_id );

				if( isset( $app->status ) )
					$stat = $app->status;
				else
					$stat = '';

				// Addons may want to add or omit some stats, but as default we don't want completed appointments to be cancelled
				$in_allowed_stat = apply_filters( 'app_cancel_allowed_status', ('pending' == $stat || 'confirmed' == $stat || 'paid' == $stat), $stat, $app_id );

				// Also the clicked link may belong to a formerly created and deleted appointment.
				// Another irrelevant app may have been created after cancel link has been sent. So we will check creation date
				if ( $in_allowed_stat && $_GET['app_nonce'] == md5( $_GET['app_id']. $this->salt . strtotime( $app->created ) ) ) {
					if ( $this->change_status( 'removed', $app_id ) ) {
						$this->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $this->get_client_name( $app_id ), $app_id ) );
						$this->send_notification( $app_id, true );
						// If there is a header warning other plugins can do whatever they need
						if ( !headers_sent() ) {
							if ( isset( $this->options['cancel_page'] ) &&  $this->options['cancel_page'] ) {
								wp_redirect( get_permalink( $this->options['cancel_page'] ) );
								exit;
							}
							else {
								wp_redirect( home_url() );
								exit;
							}
						}
					}
					// Gracefully go to home page if appointment has already been cancelled, or do something here
					do_action( 'app_cancel_failed', $app_id );
				}
			}

			/* Cancel from my appointments table by ajax */
			if ( isset( $_POST['app_id'] ) && isset( $_POST['cancel_nonce'] ) ) {
				$app_id = $_POST['app_id'];

				// Check if user is the real owner of this appointment to prevent malicious attempts
				$owner = false;
				// First try to find from database
				if ( is_user_logged_in() ) {
					global $current_user;
					$app = $this->get_app( $app_id );
					if ( $app->user && $app->user == $current_user->ID )
						$owner = true;
				}
				// Then check cookie. Check is not so strict here, as he couldn't be seeing that cancel checkbox in the first place
				if ( !$owner && isset( $_COOKIE["wpmudev_appointments"] ) ) {
					$apps = unserialize( stripslashes( $_COOKIE["wpmudev_appointments"] ) );
					if ( is_array( $apps ) && in_array( $app_id, $apps ) )
						$owner = true;
				}
				// Addons may want to do something here
				$owner = apply_filters( 'app_cancellation_owner', $owner, $app_id );

				// He is the wrong guy, or he may have cleared his cookies while he is on the page
				if ( !$owner )
					die( json_encode( array('error'=>esc_js(__('There is an issue with this appointment. Please refresh the page and try again. If problem persists, please contact website admin.','appointments') ) ) ) );

				// Now we can safely continue for cancel
				if ( $this->change_status( 'removed', $app_id ) ) {
					$this->log( sprintf( __('Client %s cancelled appointment with ID: %s','appointments'), $this->get_client_name( $app_id ), $app_id ) );
					$this->send_notification( $app_id, true );
					die( json_encode( array('success'=>1)));
				}
				else
					die( json_encode( array('error'=>esc_js(__('Appointment could not be cancelled. Please refresh the page and try again.','appointments') ) ) ) );
			}
		}
		else if ( isset( $_POST['app_id'] ) && isset( $_POST['cancel_nonce'] ) )
			die( json_encode( array('error'=>esc_js(__('Cancellation of appointments is disabled. Please contact website admin.','appointments') ) ) ) );
	}

	/**
	 * Replace CANCEL placeholder with its link
	 * @param text: email text
	 * @param app_id: ID of the appointment to be cancelled
	 * @since 1.2.6
	 */
	function add_cancel_link( $text, $app_id ) {
		if ( isset( $this->options['allow_cancel'] ) && 'yes' == $this->options['allow_cancel'] && $app_id ) {

			$app = $this->get_app( $app_id );
			// The link to be clicked may belong to a formerly created and deleted appointment.
			// Another irrelevant app may have been created after cancel link has been sent. So we will add creation date for check
			if ( $app )
				return str_replace( 'CANCEL', add_query_arg( array( 'app_cancel'=>1, 'app_id'=>$app_id, 'app_nonce'=>md5( $app_id . $this->salt . strtotime( $app->created ) ) ), home_url() ), $text);
			else
				return str_replace( 'CANCEL', '', $text );
		}
		else
			return str_replace( 'CANCEL', '', $text );
	}

/*******************************
* Methods for Admin
********************************
*/
	/**
	 * Add app status counts in admin Right Now Dashboard box
	 * http://codex.wordpress.org/Plugin_API/Action_Reference/right_now_content_table_end
	 */
	function add_app_counts() {

		global $wpdb;

		$num_active = $wpdb->get_var("SELECT COUNT(ID) FROM " . $this->app_table . " WHERE status='paid' OR status='confirmed' " );

        $num = number_format_i18n( $num_active );
        $text = _n( 'Active Appointment', 'Active Appointments', intval( $num_active ) );
        if ( App_Roles::current_user_can( 'manage_options', App_Roles::CTX_DASHBOARD ) ) {
            $num = "<a href='admin.php?page=appointments'>$num</a>";
            $text = "<a href='admin.php?page=appointments'>$text</a>";
        }
        echo '<td class="first b b-appointment">' . $num . '</td>';
        echo '<td class="t appointment">' . $text . '</td>';

        echo '</tr>';

		$num_pending = $wpdb->get_var("SELECT COUNT(ID) FROM " . $this->app_table . " WHERE status='pending' " );

        if ( $num_pending > 0 ) {
            $num = number_format_i18n( $num_pending );
            $text = _n( 'Pending Appointment', 'Pending Appointments', intval( $num_pending ) );
            if ( App_Roles::current_user_can( 'manage_options', App_Roles::CTX_DASHBOARD ) ) {
                $num = "<a href='admin.php?page=appointments&type=pending'>$num</a>";
                $text = "<a href='admin.php?page=appointments&type=pending'>$text</a>";
            }
            echo '<td class="first b b-appointment">' . $num . '</td>';
            echo '<td class="t appointment">' . $text . '</td>';

            echo '</tr>';
        }
	}

	// Enqeue js on admin pages
	function admin_scripts() {
		$screen = get_current_screen();
		if (empty($screen->base) || !preg_match('/(^|\b|_)appointments($|\b|_)/', $screen->base)) return false;
		wp_enqueue_script( 'jquery-colorpicker', $this->plugin_url . '/js/colorpicker.js', array('jquery'), $this->version);
		wp_enqueue_script( 'jquery-datepick', $this->plugin_url . '/js/jquery.datepick.min.js', array('jquery'), $this->version);
		wp_enqueue_script( 'jquery-multiselect', $this->plugin_url . '/js/jquery.multiselect.min.js', array('jquery-ui-core','jquery-ui-widget', 'jquery-ui-position'), $this->version);
		// Make a locale check to update locale_error flag
		$date_check = $this->to_us( date_i18n( $this->safe_date_format(), strtotime('today') ) );

		// Localize datepick only if there is no locale error
		if ( $file = $this->datepick_localfile() ) {
			if ( !$this->locale_error )
				wp_enqueue_script( 'jquery-datepick-local', $this->plugin_url . $file, array('jquery'), $this->version);
		}
		if ( !@$this->options["disable_js_check_admin"] )
			wp_enqueue_script( 'app-js-check', $this->plugin_url . '/js/js-check.js', array('jquery'), $this->version);

		wp_enqueue_script("appointments-admin", $this->plugin_url . "/js/admin.js", array('jquery'), $this->version);
		do_action('app-admin-admin_scripts');
	}

	// Enqueue css on settings page
	function admin_css_settings() {
		wp_enqueue_style( 'jquery-colorpicker-css', $this->plugin_url . '/css/colorpicker.css', false, $this->version);
	}

	// Enqueue css for all admin pages
	function admin_css() {
		wp_enqueue_style( "appointments-admin", $this->plugin_url . "/css/admin.css", false, $this->version );

		$screen = get_current_screen();
		if (empty($screen->base) || !preg_match('/(^|\b|_)appointments($|\b|_)/', $screen->base)) return false;
		wp_enqueue_style( "jquery-datepick", $this->plugin_url . "/css/jquery.datepick.css", false, $this->version );
		wp_enqueue_style( "jquery-multiselect", $this->plugin_url . "/css/jquery.multiselect.css", false, $this->version );
		wp_enqueue_style( "jquery-ui-smoothness", $this->plugin_url . "/css/smoothness/jquery-ui-1.8.16.custom.css", false, $this->version );
		do_action('app-admin-admin_styles');
	}

	// Return datepick locale file if it exists
	// Since 1.0.6
	function datepick_localfile() {

		$locale = preg_replace('/_/', '-', get_locale());

		$locale = apply_filters( 'app_locale', $locale );
		$file = '/js/jquery.datepick-'.$locale.'.js';
		if ( file_exists( $this->plugin_dir . $file ) )
			return $file;

		if ( substr_count( $locale, '-' ) ) {
			$l = explode( '-', $locale );
			$locale = $l[0];
			$file = '/js/jquery.datepick-'.$locale.'.js';
			if ( file_exists( $this->plugin_dir . $file ) )
				return $file;
		}

		return false;
	}

	// Read and return local month names from datepick
	// Since 1.0.6.1
	function datepick_local_months() {
		if ( !$file = $this->datepick_localfile() )
			return false;

		if ( !$file_content = @file_get_contents(  $this->plugin_dir . $file ) )
			return false;

		$file_content = str_replace( array("\r","\n","\t"), '', $file_content );

		if ( preg_match( '/monthNames:(.*?)]/s', $file_content, $matches ) ) {
			$months = str_replace( array('[',']',"'",'"'), '', $matches[1] );
			return explode( ',', $months );
		}
		return false;
	}


	// Read and return abbrevated local month names from datepick
	// Since 1.0.6.3
	function datepick_abb_local_months() {
		if ( !$file = $this->datepick_localfile() )
			return false;

		if ( !$file_content = @file_get_contents(  $this->plugin_dir . $file ) )
			return false;

		$file_content = str_replace( array("\r","\n","\t"), '', $file_content );

		if ( preg_match( '/monthNamesShort:(.*?)]/s', $file_content, $matches ) ) {
			$months = str_replace( array('[',']',"'",'"'), '', $matches[1] );
			return explode( ',', $months );
		}
		return false;
	}

	/**
	 * Track javascript errors
	 * @since 1.0.3
	 */
	function js_error() {
		// TODO: Activate this again in future releases
		if  ( false && isset( $_POST['url'] ) ) {
			$this->error_url = $_POST['url'];
			$this->log( __('Javascript error on : ', 'appointments') . $this->error_url );
			die( json_encode( array( 'message'	=> '<div class="error"><p>' .
				sprintf( __('<b>[Appointments+]</b> You have at least one javascript error on %s.
				<br />Error message: %s<br />File: %s<br />Line: %s', 'appointments'), $this->error_url, @$_POST['errorMessage'], @$_POST['file'], @$_POST['lineNumber']) .
			'</p></div>')
			)
			);
		}
		die();
	}

	/**
	 * Check if there are more than one shortcodes for certain shortcode types
	 * @since 1.0.5
	 * @return bool
	 */
	function has_duplicate_shortcode( $post_id ) {
		$post = get_post( $post_id );
		if ( is_object( $post) && $post && strpos( $post->post_content, '[app_' ) !== false ) {
			if ( substr_count( $post->post_content, '[app_services' ) > 1 || substr_count( $post->post_content, '[app_service_providers' ) > 1
				|| substr_count( $post->post_content, '[app_confirmation' ) > 1 || substr_count( $post->post_content, '[app_paypal' ) > 1
				|| substr_count( $post->post_content, '[app_login' ) > 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if confirmation shortcode missing
	 * @since 1.2.5
	 * @return bool
	 */
	function confirmation_shortcode_missing( $post_id ) {
		$post = get_post( $post_id );
		if ( is_object( $post) && $post && strpos( $post->post_content, '[app_' ) !== false ) {
			if ( !substr_count( $post->post_content, '[app_confirmation' )
				&& ( substr_count( $post->post_content, '[app_monthly' ) || substr_count( $post->post_content, '[app_schedule' ) ) )
				return true;
		}
		return false;
	}

	/**
	 *	Warn admin if no services defined or duration is wrong
	 */
	function admin_notices() {

		$this->dismiss();

		global $wpdb, $current_user;
		$r = false;
		$results = $this->get_services();
		if ( !$results ) {
			echo '<div class="error"><p>' .
				__('<b>[Appointments+]</b> You must define at least once service.', 'appointments') .
			'</p></div>';
			$r = true;
		}
		else {
			foreach ( $results as $result ) {
				if ( $result->duration < $this->get_min_time() ) {
					echo '<div class="error"><p>' .
						__('<b>[Appointments+]</b> One of your services has a duration smaller than time base. Please visit Services tab and after making your corrections save new settings.', 'appointments') .
					'</p></div>';
					$r = true;
					break;
				}
				if ( $result->duration % $this->get_min_time() != 0 ) {
					echo '<div class="error"><p>' .
						__('<b>[Appointments+]</b> One of your services is not divisible by the time base. Please visit Services tab and after making your corrections save new settings.', 'appointments') .
					'</p></div>';
					$r = true;
					break;
				}
				if ( $result->duration > 1440 ) {
					echo '<div class="error"><p>' .
						__('<b>[Appointments+]</b> One of your services has a duration greater than 24 hours. Appointments+ does not support services exceeding 1440 minutes (24 hours). ', 'appointments') .
					'</p></div>';
					$r = true;
					break;
				}
				$dismissed = false;
				$dismiss_id = get_user_meta( $current_user->ID, 'app_dismiss', true );
				if ( $dismiss_id && $dismiss_id == session_id() )
					$dismissed = true;
				if ( $this->get_workers() && !$this->get_workers_by_service( $result->ID ) && !$dismissed ) {
					echo '<div class="error"><p>' .
						__('<b>[Appointments+]</b> One of your services does not have a service provider assigned. Delete services you are not using.', 'appointments') .
					'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
					'</p></div>';
					$r = true;
					break;
				}
			}
		}
		if ( !$this->db_version || version_compare( $this->db_version, '1.2.2', '<' ) ) {
			echo '<div class="error"><p>' .
				__('<b>[Appointments+]</b> Appointments+ database tables need to be updated. Please deactivate and reactivate the plugin (DO NOT DELETE the plugin). You will not lose any saved information.', 'appointments') .
			'</p></div>';
			$r = true;
		}
		// Warn if Openid is not loaded
		$dismissed_g = false;
		$dismiss_id_g = get_user_meta( $current_user->ID, 'app_dismiss_google', true );
		if ( $dismiss_id_g && $dismiss_id_g == session_id() )
			$dismissed_g = true;
		if ( @$this->options['accept_api_logins'] && !@$this->openid && !$dismissed_g ) {
			echo '<div class="error"><p>' .
				__('<b>[Appointments+]</b> Either php curl is not installed or HTTPS wrappers are not enabled. Login with Google+ will not work.', 'appointments') .
			'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_google=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			'</p></div>';
			$r = true;
		}
		// Check for duplicate shortcodes for a visited page
		if ( isset( $_GET['post'] ) && $_GET['post'] && $this->has_duplicate_shortcode( $_GET['post'] ) ) {
			echo '<div class="error"><p>' .
			__('<b>[Appointments+]</b> More than one instance of services, service providers, confirmation, Paypal or login shortcodes on the same page may cause problems.</p>', 'appointments' ).
			'</div>';
		}

		// Check for missing confirmation shortcode
		$dismissed_c = false;
		$dismiss_id_c = get_user_meta( $current_user->ID, 'app_dismiss_confirmation_lacking', true );
		if ( $dismiss_id_c && $dismiss_id_c == session_id() )
			$dismissed_c = true;
		if ( !$dismissed_c && isset( $_GET['post'] ) && $_GET['post'] && $this->confirmation_shortcode_missing( $_GET['post'] ) ) {
			echo '<div class="error"><p>' .
				__('<b>[Appointments+]</b> Confirmation shortcode [app_confirmation] is always required to complete an appointment.', 'appointments') .
			'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a title="'.__('Dismiss this notice for this session', 'appointments').'" href="' . $_SERVER['REQUEST_URI'] . '&app_dismiss_confirmation_lacking=1"><small>'.__('Dismiss', 'appointments').'</small></a>'.
			'</p></div>';
			$r = true;
		}
		return $r;
	}

	/**
	 *	Dismiss warning messages for the current user for the session
	 *	@since 1.1.7
	 */
	function dismiss() {
		global $current_user;
		if ( isset( $_REQUEST['app_dismiss'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss', session_id() );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
		if ( isset( $_REQUEST['app_dismiss_google'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss_google', session_id() );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
		if ( isset( $_REQUEST['app_dismiss_confirmation_lacking'] ) ) {
			update_user_meta( $current_user->ID, 'app_dismiss_confirmation_lacking', session_id() );
			?><div class="updated fade"><p><?php _e('Notice dismissed.', 'appointments'); ?></p></div><?php
		}
	}

	/**
	 *	Admin pages init stuff, save settings
	 *
	 */
	function admin_init() {

		if ( !session_id() )
			@session_start();

		$page = add_menu_page('Appointments', __('Appointments','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_APPOINTMENTS),  'appointments', array(&$this,'appointment_list'),'div');
		add_submenu_page('appointments', __('Transactions','appointments'), __('Transactions','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_TRANSACTIONS), "app_transactions", array(&$this,'transactions'));
		add_submenu_page('appointments', __('Settings','appointments'), __('Settings','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SETTINGS), "app_settings", array(&$this,'settings'));
		add_submenu_page('appointments', __('Shortcodes','appointments'), __('Shortcodes','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_SHORTCODES), "app_shortcodes", array(&$this,'shortcodes_page'));
		add_submenu_page('appointments', __('FAQ','appointments'), __('FAQ','appointments'), App_Roles::get_capability('manage_options', App_Roles::CTX_PAGE_FAQ), "app_faq", array(&$this,'faq_page'));

		// Add datepicker to appointments page
		add_action( "admin_print_scripts-$page", array( &$this, 'admin_scripts' ) );

		if ( isset($_POST["action_app"]) && !wp_verify_nonce($_POST['app_nonce'],'update_app_settings') ) {
			add_action( 'admin_notices', array( &$this, 'warning' ) );
			return;
		}

		// Read Location, Service, Worker
		$this->get_lsw();
		global $wpdb;

		if ( isset($_POST["action_app"]) && 'save_general' == $_POST["action_app"] ) {
			$this->options["min_time"]					= $_POST["min_time"];
			$this->options["additional_min_time"]		= trim( $_POST["additional_min_time"] );
			$this->options["admin_min_time"]			= $_POST["admin_min_time"];
			$this->options["app_lower_limit"]			= trim( $_POST["app_lower_limit"] );
			$this->options["app_limit"]					= trim( $_POST["app_limit"] );
			$this->options["clear_time"]				= trim( $_POST["clear_time"] );
			$this->options["spam_time"]					= trim( $_POST["spam_time"] );
			$this->options["auto_confirm"]				= $_POST["auto_confirm"];
			$this->options["allow_worker_wh"]			= $_POST["allow_worker_wh"];
			$this->options["allow_worker_confirm"]		= $_POST["allow_worker_confirm"];
			$this->options["allow_overwork"]			= $_POST["allow_overwork"];
			$this->options["allow_overwork_break"]		= $_POST["allow_overwork_break"];
			$this->options["dummy_assigned_to"]			= !$this->is_dummy( @$_POST["dummy_assigned_to"] ) ? @$_POST["dummy_assigned_to"] : 0;

			$this->options["login_required"]			= $_POST["login_required"];
			$this->options["accept_api_logins"]			= isset( $_POST["accept_api_logins"] );
			$this->options["facebook-no_init"]			= isset( $_POST["facebook-no_init"] );
			$this->options['facebook-app_id']			= trim( $_POST['facebook-app_id'] );
			$this->options['twitter-app_id']			= trim( $_POST['twitter-app_id'] );
			$this->options['twitter-app_secret']		= trim( $_POST['twitter-app_secret'] );

			$this->options["app_page_type"]				= $_POST["app_page_type"];
			$this->options["show_legend"]				= $_POST["show_legend"];
			$this->options["color_set"]					= $_POST["color_set"];
			foreach ( $this->get_classes() as $class=>$name ) {
				$this->options[$class."_color"]			= $_POST[$class."_color"];
			}
			$this->options["ask_name"]					= isset( $_POST["ask_name"] );
			$this->options["ask_email"]					= isset( $_POST["ask_email"] );
			$this->options["ask_phone"]					= isset( $_POST["ask_phone"] );
			$this->options["ask_phone"]					= isset( $_POST["ask_phone"] );
			$this->options["ask_address"]				= isset( $_POST["ask_address"] );
			$this->options["ask_city"]					= isset( $_POST["ask_city"] );
			$this->options["ask_note"]					= isset( $_POST["ask_note"] );
			$this->options["additional_css"]			= trim( $_POST["additional_css"] );

			$this->options["payment_required"]			= $_POST["payment_required"];
			$this->options["percent_deposit"]			= trim( str_replace( '%', '', $_POST["percent_deposit"] ) );
			$this->options["fixed_deposit"]				= trim( str_replace( $this->options["currency"], '', $_POST["fixed_deposit"] ) );
			$this->options['members_no_payment'] 		= isset( $_POST['members_no_payment'] );
			$this->options['members_discount'] 			= trim( str_replace( '%', '', $_POST['members_discount'] ) );
			$this->options["members"]					= maybe_serialize( @$_POST["members"] );
			$this->options['currency'] 					= $_POST['currency'];
			$this->options['mode'] 						= $_POST['mode'];
			$this->options['merchant_email'] 			= trim( $_POST['merchant_email'] );
			$this->options['return'] 					= $_POST['return'];

			$this->options["send_confirmation"]			= $_POST["send_confirmation"];
			$this->options["send_notification"]			= @$_POST["send_notification"];
			$this->options["confirmation_subject"]		= stripslashes( $_POST["confirmation_subject"] );
			$this->options["confirmation_message"]		= stripslashes( $_POST["confirmation_message"] );
			$this->options["send_reminder"]				= $_POST["send_reminder"];
			$this->options["reminder_time"]				= str_replace( " ", "", $_POST["reminder_time"] );
			$this->options["send_reminder_worker"]		= $_POST["send_reminder_worker"];
			$this->options["reminder_time_worker"]		= str_replace( " ", "", $_POST["reminder_time_worker"] );
			$this->options["reminder_subject"]			= stripslashes( $_POST["reminder_subject"] );
			$this->options["reminder_message"]			= stripslashes( $_POST["reminder_message"] );
			$this->options["log_emails"]				= $_POST["log_emails"];

			$this->options['use_cache'] 				= $_POST['use_cache'];
			$this->options['disable_js_check_admin']	= isset( $_POST['disable_js_check_admin'] );
			$this->options['disable_js_check_frontend']	= isset( $_POST['disable_js_check_frontend'] );

			$this->options['use_mp']	 				= isset( $_POST['use_mp'] );
			$this->options["app_page_type_mp"]			= @$_POST["app_page_type_mp"];

			$this->options['allow_cancel'] 				= @$_POST['allow_cancel'];
			$this->options['cancel_page'] 				= @$_POST['cancel_page'];

			$this->options["records_per_page"]			= (int)trim( @$_POST["records_per_page"] );

			$this->options = apply_filters('app-options-before_save', $this->options);

			$saved = false;
			if ( update_option( 'appointments_options', $this->options ) ) {
				$saved = true;
				if ( 'yes' == $this->options['use_cache'] )
					add_action( 'admin_notices', array ( &$this, 'saved_cleared' ) );
				else
					add_action( 'admin_notices', array ( &$this, 'saved' ) );
			}

			// Flush cache
			if ( isset( $_POST["force_flush"] ) || $saved ) {
				$this->flush_cache();
				if ( isset( $_POST["force_flush"] ) )
					add_action( 'admin_notices', array ( &$this, 'cleared' ) );
			}
			// Redirecting when saving options
			if ($saved) {
				wp_redirect(add_query_arg('saved', 1));
				die;
			}

// Bimonthly schedule
$two_months = '
<td colspan="2">
[app_monthly_schedule]
</td>
</tr>
<td colspan="2">
[app_monthly_schedule add="1"]
</td>
</tr>
<tr>
<td colspan="2">
[app_pagination step="2" month="1"]
</td>
';

// Monthly schedule
$one_month = '
<td colspan="2">
[app_monthly_schedule]
</td>
</tr>
<tr>
<td colspan="2">
[app_pagination month="1"]
</td>
';

// Two week schedule
$two_weeks = '
<td>
[app_schedule]
</td>
<td>
[app_schedule add="1"]
</td>
</tr>
<tr>
<td colspan="2">
[app_pagination step="2"]
</td>
';

// One week schedule
$one_week = '
<td colspan="2">
[app_schedule long="1"]
</td>
</tr>
<tr>
<td colspan="2">
[app_pagination]
</td>
';

// Common parts
$template = '
<table>
<tbody>
<tr>
<td colspan="2">
[app_my_appointments]
</td>
</tr>
<tr>
<td>[app_services]</td>
<td>[app_service_providers]</td>
</tr>
<tr>
PLACEHOLDER
</tr>
<tr>
<td colspan="2">
[app_login]
</td>
</tr>
<tr>
<td colspan="2">
[app_confirmation]
</td>
</tr>
<tr>
<td colspan="2">
[app_paypal]
</td>
</tr>
</tbody>
</table>
';
			// Add an appointment page
			if ( isset( $_POST["make_an_appointment"] ) ) {

				switch( $_POST["app_page_type"] ) {
					case 'two_months':	$content = str_replace( 'PLACEHOLDER', $two_months, $template ); break;
					case 'one_month':	$content = str_replace( 'PLACEHOLDER', $one_month, $template ); break;
					case 'two_weeks':	$content = str_replace( 'PLACEHOLDER', $two_weeks, $template ); break;
					case 'one_week':	$content = str_replace( 'PLACEHOLDER', $one_week, $template ); break;
					default:			$content = str_replace( 'PLACEHOLDER', $one_month, $template ); break;
				}

				wp_insert_post(
						array(
							'post_title'	=> 'Make an Appointment',
							'post_status'	=> 'publish',
							'post_type'		=> 'page',
							'post_content'	=> $content
						)
				);
			}

			// Add an appointment product page
			if ( isset( $_POST["make_an_appointment_product"] ) && $this->marketpress_active ) {

				switch( $_POST["app_page_type_mp"] ) {
					case 'two_months':	$content = str_replace( 'PLACEHOLDER', $two_months, $template ); break;
					case 'one_month':	$content = str_replace( 'PLACEHOLDER', $one_month, $template ); break;
					case 'two_weeks':	$content = str_replace( 'PLACEHOLDER', $two_weeks, $template ); break;
					case 'one_week':	$content = str_replace( 'PLACEHOLDER', $one_week, $template ); break;
					default:			$content = str_replace( 'PLACEHOLDER', $one_month, $template ); break;
				}

				$post_id = wp_insert_post(
						array(
							'post_title'	=> 'Appointment',
							'post_status'	=> 'publish',
							'post_type'		=> 'product',
							'post_content'	=> $content
						)
				);
				if ( $post_id ) {
					// Add a download link, so that app will be a digital product
					$file = get_post_meta($post_id, 'mp_file', true);
					if ( !$file )
						add_post_meta( $post_id, 'mp_file', get_permalink( $post_id) );

/*
					// Do NOT! Add product link because it'll blow up store orders...
					$link = get_post_meta($post_id, 'mp_product_link', true);
					if ( !$link )
						add_post_meta( $post_id, 'mp_product_link', get_permalink( $post_id ) );
*/

					// MP requires at least 2 variations, so we add a dummy one
					add_post_meta( $post_id, 'mp_var_name', array( 0 ) );
					add_post_meta( $post_id, 'mp_sku', array( 0 ) );
					add_post_meta( $post_id, 'mp_price', array( 0 ) );
				}
			}
		}

		$result = $updated = $inserted = false;
		// Save Working Hours
		if ( isset($_POST["action_app"]) && 'save_working_hours' == $_POST["action_app"] ) {
			$location = (int)$_POST['location'];
			foreach ( array( 'closed', 'open' ) as $stat ) {
				$query = "SELECT COUNT(*) FROM ". $this->wh_table .
						" WHERE location=".$location." AND worker=".$this->worker." AND status='".$stat."' ";

				$count = $wpdb->get_var($query);

				if ( $count > 0 ) {
					$r = $wpdb->update( $this->wh_table,
								array( 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
								array( 'location'=>$location, 'worker'=>$this->worker, 'status'=>$stat ),
								array( '%s', '%s' ),
								array( '%d', '%d', '%s' )
							);
					if ( $r )
						$result = true;
				}
				else {
					$r = $wpdb->insert( $this->wh_table,
								array( 'location'=>$location, 'worker'=>$this->worker, 'hours'=>serialize($_POST[$stat]), 'status'=>$stat ),
								array( '%d', '%d', '%s', '%s' )
							);
					if ( $r )
						$result = true;

				}
				if ( $result )
					add_action( 'admin_notices', array ( &$this, 'saved' ) );
			}
		}
		// Save Exceptions
		if ( isset($_POST["action_app"]) && 'save_exceptions' == $_POST["action_app"] ) {
			$location = (int)$_POST['location'];
			foreach ( array( 'closed', 'open' ) as $stat ) {
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM ". $this->exceptions_table .
						" WHERE location=".$location." AND worker=".$this->worker." AND status='".$stat."' ");

				if ( $count > 0 ) {
					$r = $wpdb->update( $this->exceptions_table,
								array(
										'days'		=> $this->_sort( $_POST[$stat]["exceptional_days"] ),
										'status'	=> $stat
									),
								array(
									'location'	=> $location,
									'worker'	=> $this->worker,
									'status'	=> $stat ),
								array( '%s', '%s' ),
								array( '%d', '%d', '%s' )
							);
					if ( $r )
						$result = true;
				}
				else {
					$r = $wpdb->insert( $this->exceptions_table,
								array( 'location'	=> $location,
										'worker'	=> $this->worker,
										'days'		=> $this->_sort( $_POST[$stat]["exceptional_days"] ),
										'status'	=> $stat
									),
								array( '%d', '%d', '%s', '%s' )
								);
					if ( $r )
						$result = true;
				}
				if ( $result )
					add_action( 'admin_notices', array ( &$this, 'saved' ) );
			}
		}
		// Save Services
		if ( isset($_POST["action_app"]) && 'save_services' == $_POST["action_app"] && is_array( $_POST["services"] ) ) {
			do_action('app-services-before_save');
			foreach ( $_POST["services"] as $ID=>$service ) {
				if ( '' != trim( $service["name"] ) ) {
					// Update or insert?
					$count = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $this->services_table . " WHERE ID=".$ID." " );
					if ( $count ) {
						$r = $wpdb->update( $this->services_table,
									array(
										'name'		=> $service["name"],
										'capacity'	=> (int)$service["capacity"],
										'duration'	=> $service["duration"],
										'price'		=> preg_replace("/[^0-9,.]/", "", $service["price"]),
										'page'		=> $service["page"]
										),
									array( 'ID'		=> $ID ),
									array( '%s', '%d', '%d','%s','%d' )
								);
						if ( $r )
							$result = true;
					}
					else {
						$r = $wpdb->insert( $this->services_table,
									array(
										'ID'		=> $ID,
										'name'		=> $service["name"],
										'capacity'	=> (int)$service["capacity"],
										'duration'	=> $service["duration"],
										'price'		=> preg_replace("/[^0-9,.]/", "", $service["price"]),
										'page'		=> $service["page"]
										),
									array( '%d', '%s', '%d', '%d','%s','%d' )
									);
						if ( $r )
							$result = true;
					}
					do_action('app-services-service-updated', $ID);
				}
				else {
					// Entering an empty name means deleting of a service
					$r = $wpdb->query( "DELETE FROM ". $this->services_table . " WHERE ID=".$ID." LIMIT 1 " );
					// Remove deleted service also from workers table
					$r1 = $wpdb->query( "UPDATE ". $this->workers_table . " SET services_provided = REPLACE(services_provided,':".$ID.":','') ");
					if ( $r || $r1 )
						$result = true;
				}
			}
			if( $result )
				add_action( 'admin_notices', array ( &$this, 'saved' ) );
		}
		// Save Workers
		if ( isset($_POST["action_app"]) && 'save_workers' == $_POST["action_app"] && is_array( $_POST["workers"] ) ) {
			foreach ( $_POST["workers"] as $worker ) {
				$ID = $worker["user"];
				if ( $ID && !empty ( $worker["services_provided"] ) ) {
					$inserted = false;
					// Does the worker have already a record?
					$count = $wpdb->get_var( "SELECT COUNT(*) FROM " . $this->workers_table . " WHERE ID=".$ID." " );
					if ( $count ) {
						if ( !$this->db_version )
							$r = $wpdb->update( $this->workers_table,
										array(
											'price'				=> preg_replace("/[^0-9,.]/", "", $worker["price"]),
											'services_provided'	=> $this->_implode( $worker["services_provided"] ),
											'page'				=> $worker["page"]
											),
										array( 'ID'				=> $worker["user"] ),
										array( '%s', '%s','%d' )
										);
						else
							$r = $wpdb->update( $this->workers_table,
										array(
											'price'				=> preg_replace("/[^0-9,.]/", "", $worker["price"]),
											'services_provided'	=> $this->_implode( $worker["services_provided"] ),
											'page'				=> $worker["page"],
											'dummy'				=> isset( $worker["dummy"] )
											),
										array( 'ID'				=> $worker["user"] ),
										array( '%s', '%s','%d', '%s' )
										);
						if ( $r )
							$updated = true;
					}
					else {
						if ( !$this->db_version )
							$r = $wpdb->insert( $this->workers_table,
										array(
											'ID'				=> $worker["user"],
											'price'				=> preg_replace("/[^0-9,.]/", "", $worker["price"]),
											'services_provided'	=> $this->_implode( $worker["services_provided"] ),
											'page'				=> $worker["page"]
											),
										array( '%d', '%s', '%s','%d' )
										);
						else
							$r = $wpdb->insert( $this->workers_table,
										array(
											'ID'				=> $worker["user"],
											'price'				=> preg_replace("/[^0-9,.]/", "", $worker["price"]),
											'services_provided'	=> $this->_implode( $worker["services_provided"] ),
											'page'				=> $worker["page"],
											'dummy'				=> isset ( $worker["dummy"] )
											),
										array( '%d', '%s', '%s', '%d', '%s' )
										);
						if ( $r ) {
							// Insert the default working hours to the worker's working hours
							foreach ( array('open', 'closed') as $stat ) {
								$result_wh = $wpdb->get_row( "SELECT * FROM " . $this->wh_table . " WHERE location=0 AND service=0 AND status='".$stat."' ", ARRAY_A );
								if ( $result_wh != null ) {
									$result_wh["ID"] = 'NULL';
									$result_wh["worker"] = $ID;
									$wpdb->insert( $this->wh_table,
													$result_wh
												);
								}
							}
							// Insert the default holidays to the worker's holidays
							foreach ( array('open', 'closed') as $stat ) {
								$result_wh = $wpdb->get_row( "SELECT * FROM " . $this->exceptions_table . " WHERE location=0 AND service=0 AND status='".$stat."' ", ARRAY_A );
								if ( $result_wh != null ) {
									$result_wh["ID"] = 'NULL';
									$result_wh["worker"] = $ID;
									$wpdb->insert( $this->exceptions_table,
													$result_wh
												);
								}
							}
							$inserted = true;
						}
					}
					do_action('app-workers-worker-updated', $ID);
				}
				// Entering an empty service name means deleting of a worker
				else if ( $ID ) {
					$r = $wpdb->query( "DELETE FROM " . $this->workers_table . " WHERE ID=".$ID." LIMIT 1 " );
					$r1 = $wpdb->query( "DELETE FROM " . $this->wh_table . " WHERE worker=".$ID." " );
					$r2 = $wpdb->query( "DELETE FROM " . $this->exceptions_table . " WHERE worker=".$ID." " );
					if ( $r || $r1 || $r2 )
						$result = true;
				}
			}
			if( $result || $updated || $inserted )
				add_action( 'admin_notices', array ( &$this, 'saved' ) );
		}

		// Delete removed app records
		if ( isset($_POST["delete_removed"]) && 'delete_removed' == $_POST["delete_removed"]
			&& isset( $_POST["app"] ) && is_array( $_POST["app"] ) ) {
			$q = '';
			foreach ( $_POST["app"] as $app_id ) {
				$q .= " ID=". $app_id. " OR";
			}
			$q = rtrim( $q, " OR" );
			$result = $wpdb->query( "DELETE FROM " . $this->app_table . " WHERE " . $q . " " );
			if ( $result ) {
				global $current_user;
				$userdata = get_userdata( $current_user->ID );
				add_action( 'admin_notices', array ( &$this, 'deleted' ) );
				do_action( 'app_deleted',  $_POST["app"] );
				$this->log( sprintf( __('Appointment(s) with id(s):%s deleted by user:%s', 'appointments' ),  implode( ', ', $_POST["app"] ), $userdata->user_login ) );
			}
		}

		// Bulk status change
		if ( isset( $_POST["app_status_change"] ) && $_POST["app_new_status"] && isset( $_POST["app"] ) && is_array( $_POST["app"] ) ) {
			$q = '';
			foreach ( $_POST["app"] as $app_id ) {
				$q .= " ID=". $app_id. " OR";
			}
			$q = rtrim( $q, " OR" );

			// Make a new status re-check here - It should be in status map
			$new_status = $_POST["app_new_status"];
			if ( array_key_exists( $new_status, $this->get_statuses() ) ) {
				$result = $wpdb->query( "UPDATE " . $this->app_table . " SET status='".$new_status."' WHERE " . $q . " " );
				if ( $result ) {
					global $current_user;
					$userdata = get_userdata( $current_user->ID );
					add_action( 'admin_notices', array ( &$this, 'updated' ) );
					do_action( 'app_bulk_status_change',  $_POST["app"] );
					$this->log( sprintf( __('Status of Appointment(s) with id(s):%s changed to %s by user:%s', 'appointments' ),  implode( ', ', $_POST["app"] ), $new_status, $userdata->user_login ) );

					if ( is_object( $this->gcal_api ) ) {
						// If deleted, remove these from GCal too
						if ( 'removed' == $new_status ) {
							foreach ( $_POST["app"] as $app_id ) {
								$this->gcal_api->delete( $app_id );
							}
						}
						// If confirmed or paid, add these to GCal
						else if ( 'paid' == $new_status || 'confirmed' == $new_status ) {
							foreach ( $_POST["app"] as $app_id ) {
								$this->gcal_api->insert( $app_id );
							}
						}
					}
				}
			}
		}

		// Determine if we shall flush cache
		if ( ( isset( $_POST["action_app"] ) ) && ( $result || $updated || $inserted ) ||
			( isset( $_POST["delete_removed"] ) && 'delete_removed' == $_POST["delete_removed"] ) ||
			( isset( $_POST["app_status_change"] ) && $_POST["app_new_status"] ) )
			// As it means any setting is saved, lets clear cache
			$this->flush_cache();
	}

	function shortcodes_page () {
		?>
<div class="wrap">
	<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $this->plugin_url . '/images/general.png'; ?>" /></div>
	<h2><?php echo __('Appointments+ Shortcodes','appointments'); ?></h2>
	<div class="metabox-holder columns-2">
		<?php if (file_exists(APP_PLUGIN_DIR . '/includes/support/app-shortcodes.php')) include(APP_PLUGIN_DIR . '/includes/support/app-shortcodes.php'); ?>
	</div>
</div>
		<?php
	}

	function faq_page () {
		?>
<div class="wrap">
	<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $this->plugin_url . '/images/general.png'; ?>" /></div>
	<h2><?php echo __('Appointments+ FAQ','appointments'); ?></h2>
	<?php if (file_exists(APP_PLUGIN_DIR . '/includes/support/app-faq.php')) include(APP_PLUGIN_DIR . '/includes/support/app-faq.php'); ?>
</div>
		<?php
	}

	/**
	 *	Sorts a comma delimited string
	 *	@since 1.2
	 */
	function _sort( $input ) {
		if ( strpos( $input, ',') === false )
			return $input;
		$temp = explode( ',', $input );
		sort( $temp );
		return implode( ',', $temp );
	}

	/**
	 *	Packs an array into a string with : as glue
	 */
	function _implode( $input ) {
		if ( !is_array( $input ) || empty( $input ) )
			return false;
		return ':'. implode( ':', array_filter( $input ) ) . ':';
	}

	/**
	 *	Packs a string into an array assuming : as glue
	 */
	function _explode( $input ){
		if ( !is_string( $input ) )
			return false;
		return array_filter( explode( ':' , ltrim( $input , ":") ) );
	}

	/**
	 * Deletes a worker's database records in case he is deleted
	 * @since 1.0.4
	 */
	function delete_user( $ID ) {
		if ( !$ID )
			return;

		global $wpdb;
		$r = $wpdb->query( "DELETE FROM " . $this->workers_table . " WHERE ID=".$ID." LIMIT 1 " );
		$r1 = $wpdb->query( "DELETE FROM " . $this->wh_table . " WHERE worker=".$ID." " );
		$r2 = $wpdb->query( "DELETE FROM " . $this->exceptions_table . " WHERE worker=".$ID." " );

		// Also modify app table
		$r3 = $wpdb->update( $this->app_table,
						array( 'worker'	=>	0 ),
						array( 'worker'	=> $ID )
					);

		if ( $r || $r1 || $r2 || $r3 )
			$this->flush_cache();
	}

	/**
	 * Removes a worker's database records in case he is removed from that blog
	 * @param ID: user ID
	 * @param blog_id: ID of the blog that user has been removed from
	 * @since 1.2.3
	 */
	function remove_user_from_blog( $ID, $blog_id ) {
		if ( !$ID || !$blog_id )
			return;

		global $wpdb;

		// Let's be safe
		if ( !method_exists( $wpdb, 'get_blog_prefix' ) )
			return;

		$prefix = $wpdb->get_blog_prefix( $blog_id );

		if ( !$prefix )
			return;

		$r = $wpdb->query( "DELETE FROM " . $prefix . "app_workers WHERE ID=".$ID." LIMIT 1 " );
		$r1 = $wpdb->query( "DELETE FROM " . $prefix . "app_working_hours WHERE worker=".$ID." " );
		$r2 = $wpdb->query( "DELETE FROM " . $prefix . "app_exceptions WHERE worker=".$ID." " );

		// Also modify app table
		$r3 = $wpdb->update( $prefix . "app_appointments",
						array( 'worker'	=>	0 ),
						array( 'worker'	=> $ID )
					);

		if ( $r || $r1 || $r2 || $r3 )
			$this->flush_cache();
	}

	/**
	 * Prints "Cache cleared" message on top of Admin page
	 */
	function cleared( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Cache cleared.','appointments').'</p></div>';
	}

	/**
	 * Prints "settings saved and cache cleared" message on top of Admin page
	 * @since 1.1.7
	 */
	function saved_cleared( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Settings saved and cache cleared.','appointments').'</p></div>';
	}

	/**
	 * Prints "saved" message on top of Admin page
	 */
	function saved( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Settings saved.','appointments').'</p></div>';
	}

	/**
	 * Prints "deleted" message on top of Admin page
	 */
	function deleted( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Selected record(s) deleted.','appointments').'</p></div>';
	}

	/**
	 * Prints "updated" message on top of Admin page
	 */
	function updated( ) {
		echo '<div class="updated fade"><p><b>[Appointments+]</b> '. __('Selected record(s) updated.','appointments').'</p></div>';
	}

	/**
	 * Prints warning message on top of Admin page
	 */
	function warning( ) {
		echo '<div class="updated fade"><p><b>[Appointments+] '. __('You are not authorised to do this.','appointments').'</b></p></div>';
	}

	/**
	 * Admin settings HTML code
	 */
	function settings() {

		if (!App_Roles::current_user_can('manage_options', App_Roles::CTX_PAGE_SETTINGS)) {
			wp_die( __('You do not have sufficient permissions to access this page.','appointments') );
		}
		$this->get_lsw();
		global $wpdb;
	?>
		<div class="wrap">
		<div class="icon32" style="margin:10px 0 0 0"><img src="<?php echo $this->plugin_url . '/images/general.png'; ?>" /></div>
		<h2><?php echo __('Appointments+ Settings','appointments'); ?></h2>
		<h3 class="nav-tab-wrapper">
			<?php
			$tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : 'main';

			$tabs = array(
				'gcal'			=> __('Google Calendar', 'appointments'),
				'working_hours'	=> __('Working Hours', 'appointments'),
				'exceptions'	=> __('Exceptions', 'appointments'),
				'services'      => __('Services', 'appointments'),
				'workers' 	    => __('Service Providers', 'appointments'),
				//'shortcodes'    => __('Shortcodes', 'appointments'),
				'addons'		=> __('Add-ons', 'appointments'),
				'log'    		=> __('Logs', 'appointments'),
				//'faq'    		=> __('FAQ', 'appointments'),
			);

			$tabhtml = array();

			// If someone wants to remove or add a tab
			$tabs = apply_filters( 'appointments_tabs', $tabs );

			$class = ( 'main' == $tab ) ? ' nav-tab-active' : '';
			$tabhtml[] = '	<a href="' . admin_url( 'admin.php?page=app_settings' ) . '" class="nav-tab'.$class.'">' . __('General', 'appointments') . '</a>';

			foreach ( $tabs as $stub => $title ) {
				$class = ( $stub == $tab ) ? ' nav-tab-active' : '';
				$tabhtml[] = '	<a href="' . admin_url( 'admin.php?page=app_settings&amp;tab=' . $stub ) . '" class="nav-tab'.$class.'" id="app_tab_'.$stub.'">'.$title.'</a>';
			}

			echo implode( "\n", $tabhtml );
			?>
		</h3>
		<div class="clear"></div>
		<?php switch( $tab ) {
		case 'main':	?>

		<div id="poststuff" class="metabox-holder">
		<span class="description"><?php _e('Appointments+ plugin makes it possible for your clients to apply for appointments from the front end or for you to enter appointments from backend. You can define services with different durations and assign service providers to any of them. In this page, you can set settings which will be valid in general.', 'appointments') ?></span>
		<br />
		<br />
			<form method="post" action="" >

				<div class="postbox">
					<h3 class='hndle'><span><?php _e('Time Settings', 'appointments') ?></span></h3>
					<div class="inside">
						<table class="form-table">

						<tr valign="top">
						<th scope="row" ><?php _e('Time base (minutes)', 'appointments')?></th>
						<td colspan="2">
						<select name="min_time">
						<?php
						foreach ( $this->time_base() as $min_time ) {
							if ( ( isset($this->options["min_time"]) ) && $this->options["min_time"] == $min_time )
								$s = ' selected="selected"';
							else
								$s = '';
							echo '<option value="'.$min_time .'"'. $s . '>'. $min_time . '</option>';
						}
						?>
						</select>
						<span class="description"><?php _e('Minimum time that will be effective for durations, appointment and schedule intervals. Service durations can only be set as multiples of this value. Default: 30.', 'appointments') ?></span>
						</tr>

						<tr valign="top">
						<th scope="row" ><?php _e('Additional time base (minutes)', 'appointments')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="additional_min_time" value="<?php if ( isset($this->options["additional_min_time"]) ) echo $this->options["additional_min_time"] ?>" />
						<span class="description"><?php _e('If the above time bases do not fit your business, you can add a new one, e.g. 240. Note: After you save this additional time base, you must select it using the above setting. Note: Minimum allowed time base setting is 10 minutes.', 'appointments') ?></span>
						</tr>

						<tr valign="top">
						<th scope="row" ><?php _e('Admin side time base (minutes)', 'appointments')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="admin_min_time" value="<?php if ( isset($this->options["admin_min_time"]) ) echo $this->options["admin_min_time"] ?>" />
						<span class="description"><?php _e('This setting may be used to provide flexibility while manually setting and editing the appointments. For example, if you enter here 15, you can reschedule an appointment for 15 minutes intervals even selected time base is 45 minutes. If you leave this empty, then the above selected time base will be applied on the admin side.', 'appointments') ?></span>
						</tr>

						<tr valign="top">
						<th scope="row" ><?php _e('Appointments lower limit (hours)', 'appointments')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="app_lower_limit" value="<?php if ( isset($this->options["app_lower_limit"]) ) echo $this->options["app_lower_limit"] ?>" />
						<span class="description"><?php _e('This will block time slots to be booked with the set value starting from current time. For example, if you need 2 days to evaluate and accept an appointment, enter 48 here. Default: 0 (no blocking - appointments can be made if end time has not been passed)', 'appointments') ?></span>
						</tr>

						<tr valign="top">
						<th scope="row" ><?php _e('Appointments upper limit (days)', 'appointments')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="app_limit" value="<?php if ( isset($this->options["app_limit"]) ) echo $this->options["app_limit"] ?>" />
						<span class="description"><?php _e('Maximum number of days from today that a client can book an appointment. Default: 365', 'appointments') ?></span>
						</tr>

						<tr valign="top">
						<th scope="row" ><?php _e('Disable pending appointments after (mins)', 'appointments')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="clear_time" value="<?php if ( isset($this->options["clear_time"]) ) echo $this->options["clear_time"] ?>" />
						<span class="description"><?php _e('Pending appointments will be automatically removed (not deleted - deletion is only possible manually) after this set time and that appointment time will be freed. Enter 0 to disable. Default: 60. Please note that pending and GCal reserved appointments whose starting time have been passed will always be removed, regardless of any other setting.', 'appointments') ?></span>
						</tr>

						<tr valign="top">
						<th scope="row" ><?php _e('Minimum time to pass for new appointment (secs)', 'appointments')?></th>
						<td colspan="2"><input type="text" style="width:50px" name="spam_time" value="<?php if ( isset($this->options["spam_time"]) ) echo $this->options["spam_time"] ?>" />
						<span class="description"><?php _e('You can limit appointment application frequency to prevent spammers who can block your appointments. This is only applied to pending appointments. Enter 0 to disable. Tip: To prevent any further appointment applications of a client before a payment or manual confirmation, enter a huge number here.', 'appointments') ?></span>
						</tr>

						</table>
					</div>
				</div>

		<div class="postbox">
            <h3 class='hndle'><span><?php _e('Accessibility Settings', 'appointments') ?></span></h3>
            <div class="inside">

				<table class="form-table">

					<tr valign="top">
						<th scope="row" ><?php _e('Auto confirm', 'appointments')?></th>
						<td colspan="2">
						<select name="auto_confirm">
						<option value="no" <?php if ( @$this->options['auto_confirm'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['auto_confirm'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Setting this as Yes will automatically confirm all appointment applications for no payment required case. Note: "Payment required" case will still require a payment.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Allow client cancel own appointments', 'appointments')?></th>
						<td colspan="2">
						<select name="allow_cancel">
						<option value="no" <?php if ( @$this->options['allow_cancel'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['allow_cancel'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Whether to allow clients cancel their appointments using the link in confirmation and reminder emails or using my appointments table or for logged in users, using check boxes in their profile pages. For the email case, you will also need to add CANCEL placeholder to the email message settings below. For my appointments table, you will need to add parameter allow_cancel="1" to the shortcode. Note: Admin and service provider will always get a notification email.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e('Appointment cancelled page', 'appointments') ?></th>
						<td colspan="2">
						<?php wp_dropdown_pages( array( "show_option_none"=>__('Home page', 'appointments'),"option_none_value "=>0,"name"=>"cancel_page", "selected"=>@$this->options["cancel_page"] ) ) ?>
						<span class="description"><?php _e('In case he is cancelling using the email link, the page that client will be redirected after he cancelled his appointment.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Allow service provider set working hours', 'appointments')?></th>
						<td colspan="2">
						<select name="allow_worker_wh">
						<option value="no" <?php if ( @$this->options['allow_worker_wh'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['allow_worker_wh'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Whether you let service providers to set their working/break hours, exceptional days using their profile page or their navigation tab in BuddyPress.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Allow service provider confirm own appointments', 'appointments')?></th>
						<td colspan="2">
						<select name="allow_worker_confirm">
						<option value="no" <?php if ( @$this->options['allow_worker_confirm'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['allow_worker_confirm'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Whether you let service providers to confirm pending appointments assigned to them using their profile page.', 'appointments') ?></span>
						</td>
					</tr>


					<tr valign="top">
						<th scope="row" ><?php _e('Assign dummy service providers to', 'appointments')?></th>
						<td colspan="2">
						<?php
						wp_dropdown_users( array( 'show_option_all'=>__('None','appointments'), 'show'=>'user_login', 'selected' => isset( $this->options["dummy_assigned_to"] ) ? $this->options["dummy_assigned_to"] : 0, 'name'=>'dummy_assigned_to' ) );
						?>
						<span class="description"><?php _e('You can define "Dummy" service providers to enrich your service provider alternatives and variate your working schedules. They will behave exactly like ordinary users except the emails they are supposed to receive will be forwarded to the user you select here. Note: You cannot select another dummy user. It must be a user which is not set as dummy.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Login required', 'appointments')?></th>
						<td colspan="2">
						<select name="login_required">
						<option value="no" <?php if ( @$this->options['login_required'] != 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['login_required'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Whether you require the client to login to the website to apply for an appointment. Plugin lets front end logins, without the need for leaving the front end appointment page.', 'appointments') ?></span>
						</td>
					</tr>
					<?php
					if ( 'yes' != $this->options["login_required"] )
						$style='style="display:none"';
					else
						$style='';
					?>
					<script type="text/javascript">
					jQuery(document).ready(function($){
						$('select[name="login_required"]').change(function() {
							if ( $('select[name="login_required"] :selected').val() == 'yes') { $(".api_detail").show(); }
							else { $(".api_detail").hide(); }
						});
					});
					</script>
					<tr valign="top" class="api_detail" <?php echo $style?>>
						<th scope="row" ><?php _e('Accept login from front end','appointments')?></th>
						<td colspan="2">
						<input type="checkbox" id="accept_api_logins" name="accept_api_logins" value="true" <?php if ( isset($this->options["accept_api_logins"]) && $this->options["accept_api_logins"]) echo "checked='checked'"?>>
						<span class="description"><?php _e('Enables login to website from front end using Facebook, Twitter, Google+ or Wordpress.','appointments')?></span>
						</td>
					</tr>

					<tr valign="top" class="api_detail" <?php echo $style?>>
						<th scope="row" ><?php _e('My website already uses Facebook','appointments')?></th>
						<td colspan="2">
						<input type="checkbox" name="facebook-no_init" value="true" <?php if ( isset($this->options["facebook-no_init"]) && $this->options["facebook-no_init"]) echo "checked='checked'"?>>
						<span class="description"><?php _e('By default, Facebook script will be loaded by the plugin. If you are already running Facebook scripts, to prevent any conflict, check this option.','appointments')?></span>
						</td>
					</tr>

					<tr valign="top" class="api_detail" <?php echo $style?>>
						<th scope="row" ><?php _e('Facebook App ID','appointments')?></th>
						<td colspan="2">
						<input type="text" style="width:200px" name="facebook-app_id" value="<?php if (isset($this->options["facebook-app_id"])) echo $this->options["facebook-app_id"] ?>" />
						<br /><span class="description"><?php printf(__("Enter your App ID number here. If you don't have a Facebook App yet, you will need to create one <a href='%s'>here</a>", 'appointments'), 'https://developers.facebook.com/apps')?></span>
						</td>
					</tr>

					<tr valign="top" class="api_detail" <?php echo $style?>>
						<th scope="row" ><?php _e('Twitter Consumer Key','appointments')?></th>
						<td colspan="2">
						<input type="text" style="width:200px" name="twitter-app_id" value="<?php if (isset($this->options["twitter-app_id"])) echo $this->options["twitter-app_id"] ?>" />
						<br /><span class="description"><?php printf(__('Enter your Twitter App ID number here. If you don\'t have a Twitter App yet, you will need to create one <a href="%s">here</a>', 'appointments'), 'https://dev.twitter.com/apps/new')?></span>
						</td>
					</tr>

					<tr valign="top" class="api_detail" <?php echo $style?>>
						<th scope="row" ><?php _e('Twitter Consumer Secret','appointments')?></th>
						<td colspan="2">
						<input type="text" style="width:200px" name="twitter-app_secret" value="<?php if (isset($this->options["twitter-app_secret"])) echo $this->options["twitter-app_secret"] ?>" />
						<br /><span class="description"><?php _e('Enter your Twitter App ID Secret here.', 'appointments')?></span>
						</td>
					</tr>
					<?php do_action('app-settings-accessibility_settings', $style); ?>
				</table>
			</div>
		</div>

				<div class="postbox">
					<h3 class='hndle'><span><?php _e('Display Settings', 'appointments') ?></span></h3>
					<div class="inside">
						<table class="form-table">

						<tr valign="top">
						<th scope="row" ><?php _e('Create an Appointment Page', 'appointments')?></th>
						<td colspan="2">
						<input type="checkbox" name="make_an_appointment" <?php if ( isset( $this->options["make_an_appointment"] ) && $this->options["make_an_appointment"] ) echo 'checked="checked"' ?> />
						&nbsp;<?php _e('with', 'appointments') ?>&nbsp;
						<select name="app_page_type">
						<option value="one_month"><?php _e('current month\'s schedule', 'appointments')?></option>
						<option value="two_months" <?php if ( 'two_months' == @$this->options["app_page_type"] ) echo 'selected="selected"' ?>><?php _e('current and next month\'s schedules', 'appointments')?></option>
						<option value="one_week" <?php if ( 'one_week' == @$this->options["app_page_type"] ) echo 'selected="selected"' ?>><?php _e('current week\'s schedule', 'appointments')?></option>
						<option value="two_weeks" <?php if ( 'two_weeks' == @$this->options["app_page_type"] ) echo 'selected="selected"' ?>><?php _e('current and next week\'s schedules', 'appointments')?></option>
						</select>
						<br />
						<span class="description"><?php _e('Creates a front end Appointment page with title "Make an Appointment" with the selected schedule type and inserts all necessary shortcodes (My Appointments, Service Selection, Service Provider Selection, Appointment Schedule, Front end Login, Confirmation Field, Paypal Form)  inside it. You can edit, add parameters to shortcodes, remove undesired shortcodes and customize this page later.', 'appointments') ?></span>
						<?php
						$page_id = $wpdb->get_var( "SELECT ID FROM ". $wpdb->posts. " WHERE post_title = 'Make an Appointment' AND post_type='page' ");
						if ( $page_id ) { ?>
							<br />
							<span class="description"><?php _e('<b>Note:</b> You already have such a page. If you check this checkbox, another page with the same title will be created. To edit existing page: ' , 'appointments') ?>
							<a href="<?php echo admin_url('post.php?post='.$page_id.'&action=edit')?>" target="_blank"><?php _e('Click here', 'appointments')?></a>
							&nbsp;
							<?php _e('To view the page:', 'appointments') ?>
							<a href="<?php echo get_permalink( $page_id)?>" target="_blank"><?php _e('Click here', 'appointments')?></a>
							</span>
						<?php }
						?>
						</td>
						</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Show Legend', 'appointments')?></th>
						<td colspan="2">
						<select name="show_legend">
						<option value="no" <?php if ( @$this->options['show_legend'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['show_legend'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Whether to display description fields above the pagination area.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Color Set', 'appointments')?></th>
						<td style="width:10%">
						<select name="color_set">
						<option value="1" <?php if ( @$this->options['color_set'] == 1 ) echo "selected='selected'"?>><?php _e('Preset 1', 'appointments')?></option>
						<option value="2" <?php if ( @$this->options['color_set'] == 2 ) echo "selected='selected'"?>><?php _e('Preset 2', 'appointments')?></option>
						<option value="3" <?php if ( @$this->options['color_set'] == 3 ) echo "selected='selected'"?>><?php _e('Preset 3', 'appointments')?></option>
						<option value="0" <?php if ( @$this->options['color_set'] == 0 ) echo "selected='selected'"?>><?php _e('Custom', 'appointments')?></option>
						</select>
						</td>
						<td >
						<div class="preset_samples" <?php if ( @$this->options['color_set'] == 0 ) echo 'style="display:none"' ?>>
						<label style="width:15%;display:block;float:left;font-weight:bold;">
						<?php _e('Sample:', 'appointments') ?>
						</label>
						<?php foreach ( $this->get_classes() as $class=>$name ) { ?>
						<label style="width:28%;display:block;float:left;">
							<span style="float:left">
								<?php echo $name ?>:
							</span>
							<span style="float:left;margin-right:8px;">
								<a href="javascript:void(0)" class="pickcolor <?php echo $class?> hide-if-no-js" <?php if ( @$this->options['color_set'] != 0 ) echo 'style="background-color:#'. $this->get_preset($class, $this->options['color_set']). '"' ?>></a>
							</span>

						</label>
					<?php } ?>
						<div style="clear:both"></div>
						</div>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" >&nbsp;</th>
						<td colspan="2">
						<span class="description"><?php _e('You can select table cell colors from presets with the given samples or you can define your custom set below which is visible after you select "Custom".', 'appointments') ?></span>
						</td>
					</tr>

					<script type="text/javascript">
					jQuery(document).ready(function($){
						var hex = new Array;

						$('select[name="color_set"]').change(function() {
							var n = $('select[name="color_set"] :selected').val();
							if ( n == 0) { $(".custom_color_row").show(); $(".preset_samples").hide(); }
							else { $(".custom_color_row").hide();
							$(".preset_samples").show();
							<?php foreach ( $this->get_classes() as $class=>$name ) {
							echo $class .'=new Array;';
							for ( $k=1; $k<=3; $k++ ) {
								echo $class .'['. $k .'] = "'. $this->get_preset( $class, $k ) .'";';
							}
							echo '$(".preset_samples").find("a.'. $class .'").css("background-color", "#"+'. $class.'[n]);';
							} ?>
							}
						});
					});
					</script>

					<tr valign="top" class="custom_color_row" <?php if ( @$this->options['color_set'] != 0 ) echo 'style="display:none"'?>>
						<th scope="row" ><?php _e('Custom Color Set', 'appointments')?></th>
						<td colspan="2">
					<?php foreach ( $this->get_classes() as $class=>$name ) { ?>
						<label style="width:31%;display:block;float:left;">
							<span style="float:left"><?php echo $name ?>:</span>
							<span style="float:left;margin-right:8px;">
								<a href="javascript:void(0)" class="pickcolor hide-if-no-js" <?php if( isset($this->options[$class."_color"]) ) echo 'style="background-color:#'. $this->options[$class."_color"]. '"' ?>></a>
								<input style="width:50px" type="text" class="colorpicker_input" maxlength="6" name="<?php echo $class?>_color" id="<?php echo $class?>_color" value="<?php if( isset($this->options[$class."_color"]) ) echo $this->options[$class."_color"] ?>" />
							</span>

						</label>
					<?php } ?>
						<div style="clear:both"></div>
						<span class="description"><?php _e('If you have selected Custom color set, for each cell enter 3 OR 6-digit Hex code of the color manually without # in front or use the colorpicker.', 'appointments') ?></span>
						</td>
					</tr>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$('.colorpicker_input').each( function() {
					var id = this.id;
					$('#'+id).ColorPicker({
						onSubmit: function(hsb, hex, rgb, el) {
							$(el).val(hex);
							$(el).ColorPickerHide();
						},
						onBeforeShow: function () {
							$(this).ColorPickerSetColor(this.value);
						},
						onChange: function (hsb, hex, rgb) {
							$('#'+id).val(hex);
							$('#'+id).parent().find('a.pickcolor').css('background-color', '#'+hex);
						}
					  })
					  .bind('keyup', function(){
						$(this).ColorPickerSetColor(this.value);
					});;
				});
				$('.colorpicker_input').keyup( function() {
					var a = $(this).val();
					a = a.replace(/[^a-fA-F0-9]/, '');
					if ( a.length === 3 || a.length === 6 )
						$(this).parent().find('a.pickcolor').css('background-color', '#'+a);
				});
			});
			</script>
			<tr valign="top">
				<th scope="row" ><?php _e('Require these from the client:', 'appointments')?></th>
				<td colspan="2">
				<input type="checkbox" name="ask_name" <?php if ( isset( $this->options["ask_name"] ) && $this->options["ask_name"] ) echo 'checked="checked"' ?> />&nbsp;<?php echo $this->get_field_name('name') ?>&nbsp;&nbsp;&nbsp;
				<input type="checkbox" name="ask_email" <?php if ( isset( $this->options["ask_email"] ) && $this->options["ask_email"] ) echo 'checked="checked"' ?> />&nbsp;<?php echo $this->get_field_name('email') ?>&nbsp;&nbsp;&nbsp;
				<input type="checkbox" name="ask_phone" <?php if ( isset( $this->options["ask_phone"] ) && $this->options["ask_phone"] ) echo 'checked="checked"' ?> />&nbsp;<?php echo $this->get_field_name('phone') ?>&nbsp;&nbsp;&nbsp;
				<input type="checkbox" name="ask_address" <?php if ( isset( $this->options["ask_address"] ) && $this->options["ask_address"] ) echo 'checked="checked"' ?> />&nbsp;<?php echo $this->get_field_name('address') ?>&nbsp;&nbsp;&nbsp;
				<input type="checkbox" name="ask_city" <?php if ( isset( $this->options["ask_city"] ) && $this->options["ask_city"] ) echo 'checked="checked"' ?> />&nbsp;<?php echo $this->get_field_name('city') ?>&nbsp;&nbsp;&nbsp;
				<input type="checkbox" name="ask_note" <?php if ( isset( $this->options["ask_note"] ) && $this->options["ask_note"] ) echo 'checked="checked"' ?> />&nbsp;<?php echo $this->get_field_name('note') ?>&nbsp;&nbsp;&nbsp;
				<br />
				<span class="description"><?php _e('The selected fields will be available in the confirmation area and they will be asked from the client. If selected, filling of them is mandatory (except note field).', 'appointments') ?></span>
				</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Additional css Rules', 'appointments') ?></th>
					<td colspan="2">
					<textarea cols="90" name="additional_css"><?php echo esc_textarea($this->options['additional_css']); ?></textarea>
					<br />
					<span class="description"><?php _e('You can add css rules to customize styling. These will be added to the front end appointment page only.', 'appointments') ?></span>
					</td>
				</tr>

				</table>
			</div>
		</div>

		<div class="postbox">
			<h3 class='hndle'><span><?php _e('Payment Settings', 'appointments'); ?></span></h3>
			<div class="inside">
			<table class="form-table">

			<tr valign="top">
				<th scope="row" ><?php _e('Payment required', 'appointments')?></th>
				<td colspan="2">
				<select name="payment_required">
				<option value="no" <?php if ( @$this->options['payment_required'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
				<option value="yes" <?php if ( @$this->options['payment_required'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
				</select>
				<span class="description"><?php printf( __('Whether you require a payment to accept appointments. If selected Yes, client is asked to pay through Paypal and the appointment will be in pending status until the payment is confirmed by Paypal IPN. If selected No, appointment will be in pending status until you manually approve it using the %s unless Auto Confirm is not set as Yes.', 'appointments'), '<a href="'.admin_url('admin.php?page=appointments').'">'.__('Appointments page', 'appointments').'</a>' ) ?></span>
				</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
				<th scope="row"><?php _e('Paypal Currency', 'appointments') ?></th>
				<td colspan="2">
	          <select name="currency">
	          <?php
	          $sel_currency = ($this->options['currency']) ? $this->options['currency'] : $this->options['currency'];
	          $currencies = array(
	              'AUD' => __('AUD - Australian Dollar', 'appointments'),
	              'BRL' => __('BRL - Brazilian Real', 'appointments'),
	              'CAD' => __('CAD - Canadian Dollar', 'appointments'),
	              'CHF' => __('CHF - Swiss Franc', 'appointments'),
	              'CZK' => __('CZK - Czech Koruna', 'appointments'),
	              'DKK' => __('DKK - Danish Krone', 'appointments'),
	              'EUR' => __('EUR - Euro', 'appointments'),
	              'GBP' => __('GBP - Pound Sterling', 'appointments'),
	              'ILS' => __('ILS - Israeli Shekel', 'appointments'),
	              'HKD' => __('HKD - Hong Kong Dollar', 'appointments'),
	              'HUF' => __('HUF - Hungarian Forint', 'appointments'),
	              'JPY' => __('JPY - Japanese Yen', 'appointments'),
	              'MYR' => __('MYR - Malaysian Ringgits', 'appointments'),
	              'MXN' => __('MXN - Mexican Peso', 'appointments'),
	              'NOK' => __('NOK - Norwegian Krone', 'appointments'),
	              'NZD' => __('NZD - New Zealand Dollar', 'appointments'),
	              'PHP' => __('PHP - Philippine Pesos', 'appointments'),
	              'PLN' => __('PLN - Polish Zloty', 'appointments'),
	              'SEK' => __('SEK - Swedish Krona', 'appointments'),
	              'SGD' => __('SGD - Singapore Dollar', 'appointments'),
	              'TWD' => __('TWD - Taiwan New Dollars', 'appointments'),
	              'THB' => __('THB - Thai Baht', 'appointments'),
				  'TRY' => __('TRY - Turkish lira', 'appointments'),
	              'USD' => __('USD - U.S. Dollar', 'appointments')
	          );

	          foreach ($currencies as $k => $v) {
	              echo '<option value="' . $k . '"' . ($k == $sel_currency ? ' selected' : '') . '>' . esc_html($v, true) . '</option>' . "\n";
	          }
	          ?>
	          </select>
	        </td>
	        </tr>
				<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
					<th scope="row"><?php _e('PayPal Mode', 'appointments') ?></th>
					<td colspan="2">
					<select name="mode">
					  <option value="sandbox"<?php selected($this->options['mode'], 'sandbox') ?>><?php _e('Sandbox', 'appointments') ?></option>
					  <option value="live"<?php selected($this->options['mode'], 'live') ?>><?php _e('Live', 'appointments') ?></option>
					</select>
					</td>
				</tr>

				<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
					<th scope="row"><?php _e('PayPal Merchant E-mail', 'appointments') ?></th>
					<td colspan="2">
					<input value="<?php echo esc_attr($this->options['merchant_email']); ?>" size="30" name="merchant_email" type="text" />
					<span class="description">
					<?php
					printf( __('Just for your information, your IPN link is: <b>%s </b>. You may need this information in some cases.', 'appointments'), admin_url('admin-ajax.php?action=app_paypal_ipn') );
					?>
					</span>
					</td>
				</tr>

				</tr>
				<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
					<th scope="row"><?php _e('Thank You Page', 'appointments') ?></th>
					<td colspan="2">
					<?php wp_dropdown_pages( array( "show_option_none"=>__('Home page', 'appointments'),"option_none_value "=>0,"name"=>"return", "selected"=>@$this->options["return"] ) ) ?>
					<span class="description"><?php _e('The page that client will be returned when he clicks the return link on Paypal website.', 'appointments') ?></span>
					</td>

				</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
					<th scope="row"><?php _e('Deposit (%)', 'appointments') ?></th>
					<td colspan="2">
					<input value="<?php echo esc_attr(@$this->options['percent_deposit']); ?>" style="width:50px" name="percent_deposit" type="text" />
					<span class="description"><?php _e('You may want to ask a certain percentage of the service price as deposit, e.g. 25. Leave this field empty to ask for full price.', 'appointments') ?></span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
					<th scope="row"><?php _e('Deposit (fixed)', 'appointments') ?></th>
					<td colspan="2">
					<input value="<?php echo esc_attr(@$this->options['fixed_deposit']); ?>" style="width:50px" name="fixed_deposit" type="text" />
					<span class="description"><?php _e('Same as above, but a fixed deposit will be asked from the client per appointment. If both fields are filled, only the fixed deposit will be taken into account.', 'appointments') ?></span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'; else echo 'style="border-top: 1px solid lightgrey;"'?>>
					<th scope="row">&nbsp;</th>
					<td colspan="2">
					<span class="description"><?php printf( __('The below fields require %s plugin. ', 'appointments'), '<a href="http://premium.wpmudev.org/project/membership/" target="_blank">Membership</a>') ?></span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"';?>>
					<th scope="row"><?php _e('Don\'t ask advance payment from selected Membership level(s)', 'appointments') ?></th>
					<td colspan="2">
					<input type="checkbox" name="members_no_payment" <?php if ( isset( $this->options["members_no_payment"] ) && $this->options["members_no_payment"] ) echo 'checked="checked"' ?> />
					<span class="description"><?php _e('Below selected level(s) will not be asked for an advance payment or deposit. This does not mean that service will be free of charge for them. Such member appointments are automatically confirmed.', 'appointments') ?></span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'; ?>>
					<th scope="row"><?php _e('Discount for selected Membership level(s) (%)', 'appointments') ?></th>
					<td colspan="2">
					<input type="text" name="members_discount" style="width:50px" value="<?php echo @$this->options["members_discount"] ?>" />
					<span class="description"><?php _e('Below selected level(s) will get a discount given in percent, e.g. 20. Leave this field empty for no discount. Tip: If you enter 100, service will be free of charge for these members.', 'appointments') ?></span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'?>>
					<th scope="row"><?php _e('Membership levels for the above selections', 'appointments') ?></th>
					<td colspan="2">
					<?php
					if ( $this->membership_active ) {
						$meta = maybe_unserialize( @$this->options["members"] );
						global $membershipadmin;
						$levels = $membershipadmin->get_membership_levels(array('level_id' => 'active'));
						if ( $levels && is_array( $levels ) ) {
							echo '<div style="float:left"><select multiple="multiple" name="members[level][]" >';
							foreach ( $levels as $level ) {
								if ( $level->level_slug != 'visitors' ) { // Do not include strangers
									if ( is_array( $meta["level"] ) AND in_array( $level->id, $meta["level"] ) )
										$sela = 'selected="selected"';
									else
										$sela = '';
									echo '<option value="'.$level->id.'"' . $sela . '>'. $level->level_title . '</option>';
								}
							}
							echo '</select></div>';
						}
						else
							echo '<input type="text" size="40" value="'. __('No level was defined yet','appointments').'" readonly="readonly" />';
					}
					else
						echo '<input type="text" size="40" value="'. __('Membership plugin is not activated.','appointments').'" readonly="readonly" />';
					?>
					<div style="float:left;width:80%;margin-left:5px;">
					<span class="description"><?php _e('Selected level(s) will not be asked advance payment/deposit and/or will take a discount, depending on the above selections. You can select multiple levels using CTRL and SHIFT keys.', 'appointments') ?></span>
					</div>
					<div style="clear:both"></div>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"'; else echo 'style="border-top: 1px solid lightgrey;"'?>>
					<th scope="row">&nbsp;</th>
					<td colspan="2">
					<span class="description"><?php printf( __('The below fields require %s plugin. ', 'appointments'), '<a href="http://premium.wpmudev.org/project/e-commerce/" target="_blank">MarketPress</a>') ?></span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"';?>>
					<th scope="row"><?php _e('Integrate with MarketPress', 'appointments') ?></th>
					<td colspan="2">
					<input type="checkbox" name="use_mp" <?php if ( isset( $this->options["use_mp"] ) && $this->options["use_mp"] ) echo 'checked="checked"' ?> />
					<span class="description"><?php _e('Appointments can be set as products. Any appointment shortcode added to a product page will make that page an "Appointment Product Page". For details, please see FAQ.', 'appointments') ?>
					<?php if ( !$this->marketpress_active ) {
					echo '<br />';
					_e( 'Note: MarketPress is not actived on this website', 'appointments' );
					}	?>
					</span>
					</td>
			</tr>

			<tr class="payment_row" <?php if ( $this->options['payment_required'] != 'yes' ) echo 'style="display:none"';?>>
				<th scope="row" ><?php _e('Create an Appointment Product Page', 'appointments')?></th>
				<td colspan="2">
				<input type="checkbox" name="make_an_appointment_product" <?php if ( isset( $this->options["make_an_appointment_product"] ) && $this->options["make_an_appointment_product"] ) echo 'checked="checked"' ?> />
				&nbsp;<?php _e('with', 'appointments') ?>&nbsp;
				<select name="app_page_type_mp">
				<option value="one_month"><?php _e('current month\'s schedule', 'appointments')?></option>
				<option value="two_months" <?php if ( 'two_months' == @$this->options["app_page_type_mp"] ) echo 'selected="selected"' ?>><?php _e('current and next month\'s schedules', 'appointments')?></option>
				<option value="one_week" <?php if ( 'one_week' == @$this->options["app_page_type_mp"] ) echo 'selected="selected"' ?>><?php _e('current week\'s schedule', 'appointments')?></option>
				<option value="two_weeks" <?php if ( 'two_weeks' == @$this->options["app_page_type_mp"] ) echo 'selected="selected"' ?>><?php _e('current and next week\'s schedules', 'appointments')?></option>
				</select>
				<br />
				<span class="description"><?php _e('Same as the above "Create an Appointment Page", but this time appointment shortcodes will be inserted in a new Product page and page title will be "Appointment". This is also the product name.', 'appointments') ?></span>
				<?php
				$page_id = $wpdb->get_var( "SELECT ID FROM ". $wpdb->posts. " WHERE post_title = 'Appointment' AND post_type='product' ");
				if ( $page_id ) { ?>
					<br /><span class="description"><?php _e('<b>Note:</b> You already have such a page. If you check this checkbox, another page with the same title will be created. To edit existing page: ', 'appointments') ?>
					<a href="<?php echo admin_url('post.php?post='.$page_id.'&action=edit')?>" target="_blank"><?php _e('Click here', 'appointments')?></a>
					&nbsp;
					<?php _e('To view the page:', 'appointments') ?>
					<a href="<?php echo get_permalink( $page_id)?>" target="_blank"><?php _e('Click here', 'appointments')?></a>
				</span>
				<?php }
				?>
				</td>
			</tr>

	        </table>
			</div>
			</div>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$('select[name="payment_required"]').change(function() {
					if ( $('select[name="payment_required"]').val() == "yes" ) { $(".payment_row").show(); }
					else { $(".payment_row").hide(); }
				});
			});
			</script>

			<div class="postbox">
            <h3 class='hndle'><span class="notification_settings"><?php _e('Notification Settings', 'appointments') ?></span></h3>
            <div class="inside">

				<table class="form-table">

				<tr valign="top">
					<th scope="row" ><?php _e('Send Confirmation email', 'appointments')?></th>
					<td colspan="2">
					<select name="send_confirmation">
					<option value="no" <?php if ( @$this->options['send_confirmation'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
					<option value="yes" <?php if ( @$this->options['send_confirmation'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
					</select>
					<span class="description"><?php _e('Whether to send an email after confirmation of the appointment. Note: Admin and service provider will also get a copy as separate emails.', 'appointments') ?></span>
					</td>
					</tr>
   				<tr>

				<tr valign="top">
					<th scope="row" ><?php _e('Send Notification to admin if confirmation is required', 'appointments')?></th>
					<td colspan="2">
					<select name="send_notification">
					<option value="no" <?php if ( @$this->options['send_notification'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
					<option value="yes" <?php if ( @$this->options['send_notification'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
					</select>
					<span class="description"><?php _e('You may want to receive a notification email whenever a new appointment is made from front end in pending status. This email is only sent if you do not require a payment, that is, if your approval is required. Note: Notification email is also sent to the service provider, if a provider is namely selected by the client, and "Allow Service Provider Confirm Own Appointments" is set as Yes.', 'appointments') ?></span>
					</td>
					</tr>
   				<tr>

				<tr>
					<th scope="row"><?php _e('Confirmation Email Subject', 'appointments') ?></th>
					<td>
					<input value="<?php echo esc_attr($this->options['confirmation_subject']); ?>" size="90" name="confirmation_subject" type="text" />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Confirmation email Message', 'appointments') ?></th>
					<td>
					<textarea cols="90" name="confirmation_message"><?php echo esc_textarea($this->options['confirmation_message']); ?></textarea>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" ><?php _e('Send Reminder email to the Client', 'appointments')?></th>
					<td colspan="2">
					<select name="send_reminder">
					<option value="no" <?php if ( @$this->options['send_reminder'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
					<option value="yes" <?php if ( @$this->options['send_reminder'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
					</select>
					<span class="description"><?php _e('Whether to send reminder email(s) to the client before the appointment.', 'appointments') ?></span>
					</td>
					</tr>
   				<tr>

				<tr>
					<th scope="row"><?php _e('Reminder email Sending Time for the Client (hours)', 'appointments') ?></th>
					<td>
					<input value="<?php echo esc_attr($this->options['reminder_time']); ?>" size="90" name="reminder_time" type="text" />
					<br />
					<span class="description"><?php _e('Defines how many hours reminder will be sent to the client before the appointment will take place. Multiple reminders are possible. To do so, enter reminding hours separated with a comma, e.g. 48,24.', 'appointments') ?></span>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" ><?php _e('Send Reminder email to the Provider', 'appointments')?></th>
					<td colspan="2">
					<select name="send_reminder_worker">
					<option value="no" <?php if ( @$this->options['send_reminder_worker'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
					<option value="yes" <?php if ( @$this->options['send_reminder_worker'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
					</select>
					<span class="description"><?php _e('Whether to send reminder email(s) to the service provider before the appointment.', 'appointments') ?></span>
					</td>
					</tr>
   				<tr>

				<tr>
					<th scope="row"><?php _e('Reminder email Sending Time for the Provider (hours)', 'appointments') ?></th>
					<td>
					<input value="<?php echo esc_attr($this->options['reminder_time_worker']); ?>" size="90" name="reminder_time_worker" type="text" />
					<br />
					<span class="description"><?php _e('Same as above, but defines the time for service provider.', 'appointments') ?></span>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Reminder email Subject', 'appointments') ?></th>
					<td>
					<input value="<?php echo esc_attr($this->options['reminder_subject']); ?>" size="90" name="reminder_subject" type="text" />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Reminder email Message', 'appointments') ?></th>
					<td>
					<textarea cols="90" name="reminder_message"><?php echo esc_textarea($this->options['reminder_message']); ?></textarea>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row" ><?php _e('Log Sent email Records', 'appointments')?></th>
					<td colspan="2">
					<select name="log_emails">
					<option value="no" <?php if ( @$this->options['log_emails'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
					<option value="yes" <?php if ( @$this->options['log_emails'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
					</select>
					<span class="description"><?php _e('Whether to log confirmation and reminder email records (Not the emails themselves).', 'appointments') ?></span>
					</td>
					</tr>
   				<tr>

				<tr>
				<th scope="row">&nbsp;</th>
				<td>
				<span class="description">
				<?php _e('For the above email subject and message contents, you can use the following placeholders which will be replaced by their real values:', 'appointments') ?>&nbsp;SITE_NAME, CLIENT, SERVICE, SERVICE_PROVIDER, DATE_TIME, PRICE, DEPOSIT, <span class="app-has_explanation" title="(PRICE - DEPOSIT)">BALANCE</span>, PHONE, NOTE, ADDRESS, CITY, EMAIL <?php _e("(Client's email)", "appointments")?>, CANCEL <?php _e("(Adds a cancellation link to the email body)", "appointments")?>
				</span>
				</td>
				</tr>

				</table>
			</div>
			</div>

		<div class="postbox">
            <h3 class='hndle'><span><?php _e('Advanced Settings', 'appointments') ?></span></h3>
            <div class="inside">

				<table class="form-table">

					<tr valign="top">
						<th scope="row" ><?php _e('Use Built-in Cache', 'appointments')?></th>
						<td colspan="2">
						<select name="use_cache">
						<option value="no" <?php if ( @$this->options['use_cache'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['use_cache'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Appointments+ has a built-in cache to increase performance. If you are making changes in the styling of your appointment pages, modifying shortcode parameters or having some issues while using it, disable it by selecting No.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Clear Cache', 'appointments')?></th>
						<td colspan="2">
						<input type="checkbox" name="force_cache" <?php if ( isset( $this->options["force_cache"] ) && $this->options["force_cache"] ) echo 'checked="checked"' ?> />
						<span class="description"><?php _e('Cache is automatically cleared at regular intervals (Default: 10 minutes) or when you change a setting. To clear it manually check this checkbox.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Allow Overwork (end of day)', 'appointments')?></th>
						<td colspan="2">
						<select name="allow_overwork">
						<option value="no" <?php if ( @$this->options['allow_overwork'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['allow_overwork'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Whether you accept appointments exceeding working hours for the end of day. For example, if you are working until 6pm, and a client asks an appointment for a 60 minutes service at 5:30pm, to allow such an appointment you should select this setting as Yes. Please note that this is only practical if the selected service lasts longer than the base time. Such time slots are marked as "not possible" in the schedule.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Allow Overwork (break hours)', 'appointments')?></th>
						<td colspan="2">
						<select name="allow_overwork_break">
						<option value="no" <?php if ( @$this->options['allow_overwork_break'] <> 'yes' ) echo "selected='selected'"?>><?php _e('No', 'appointments')?></option>
						<option value="yes" <?php if ( @$this->options['allow_overwork_break'] == 'yes' ) echo "selected='selected'"?>><?php _e('Yes', 'appointments')?></option>
						</select>
						<span class="description"><?php _e('Same as above, but valid for break hours. If you want to allow appointments exceeding break times, then select this as Yes.', 'appointments') ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row" ><?php _e('Number of appointment records per page', 'appointments')?></th>
						<td colspan="2">
						<input type="text" style="width:50px" name="records_per_page" value="<?php if ( isset($this->options["records_per_page"]) ) echo $this->options["records_per_page"] ?>" />
						<span class="description"><?php _e('Number of records to be displayed on admin appointments page. If left empty: 50', 'appointments') ?></span>
						</td>
					</tr>
					<?php do_action('app-settings-advanced_settings'); ?>
				</table>
			</div>
			</div>
			<?php do_action('app-settings-after_advanced_settings'); ?>

				<input type="hidden" name="action_app" value="save_general" />
				<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Settings', 'appointments') ?>" />
				</p>

			</form>

		</div>
		<?php break;

		case 'gcal':
			if ( is_object( $this->gcal_api ) )
				$this->gcal_api->render_tab();
		break;

		case 'working_hours': _e( '<i>Here you can define working hours and breaks for your business. When you add new service providers, their working and break hours will be set to the default schedule. Then you can edit their schedule by selecting their names from the dropdown menu below.</i>', 'appointments'); ?>
			<br />
			<br />
			<?php
			$workers = $wpdb->get_results( "SELECT * FROM " . $this->workers_table . " " );
			?>
			<?php _e('List for:', 'appointments')?>
			&nbsp;
			<select id="app_provider_id" name="app_provider_id">
			<option value="0"><?php _e('No specific provider', 'appointments')?></option>
			<?php
			if ( $workers ) {
				foreach ( $workers as $worker ) {
					if ( $this->worker == $worker->ID )
						$s = " selected='selected'";
					else
						$s = '';
					echo '<option value="'.$worker->ID.'"'.$s.'>' . $this->get_worker_name( $worker->ID, false ) . '</option>';
				}
			}
			?>
			</select>
			<br /><br />
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$('#app_provider_id').change(function(){
					var app_provider_id = $('#app_provider_id option:selected').val();
					window.location.href = "<?php echo admin_url('admin.php?page=app_settings&tab=working_hours')?>" + "&app_provider_id=" + app_provider_id;
				});
			});
			</script>
			<form method="post" action="" >
				<table class="widefat fixed">
				<tr>
				<th style="width:40%"><?php _e( 'Working Hours', 'appointments' ) ?></th>
				<th style="width:40%"><?php _e( 'Break Hours', 'appointments' ) ?></th>
				<tr>
				<td>
				<?php echo $this->working_hour_form( 'open' ); ?>
				</td>
				<td>
				<?php echo $this->working_hour_form( 'closed' ); ?>
				</td>
				</tr>
				</table>

				<input type="hidden" name="worker" value="0" />
				<input type="hidden" name="location" value="0" />
				<input type="hidden" name="action_app" value="save_working_hours" />
				<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Working Hours', 'appointments') ?>" />
				</p>

			</form>
			<?php break; ?>

		<?php case 'exceptions': _e( '<i>Here you can define exceptional working or non working days for your business and for your service providers. You should enter holidays here. You can also define a normally non working week day (e.g. a specific Sunday) as a working day. When you add new service providers, their expections will be set to the default schedule.</i>', 'appointments'); ?>
			<br />
			<br />
			<?php
			$result = array();
			foreach ( array( 'open', 'closed' ) as $stat ) {
				$result[$stat] = $wpdb->get_var( "SELECT days FROM " . $this->exceptions_table . " WHERE status='".$stat."' AND worker=".$this->worker." " );
			}
			$workers = $this->get_workers();
			?>
			<?php _e('List for:', 'appointments')?>
			&nbsp;
			<select id="app_provider_id" name="app_provider_id">
			<option value="0"><?php _e('No specific provider', 'appointments')?></option>
			<?php
			if ( $workers ) {
				foreach ( $workers as $worker ) {
					if ( $this->worker == $worker->ID )
						$s = " selected='selected'";
					else
						$s = '';
					echo '<option value="'.$worker->ID.'"'.$s.'>' . $this->get_worker_name( $worker->ID, false ) . '</option>';
				}
			}
			?>
			</select>
			<br /><br />
			<form method="post" action="" >
				<table class="widefat fixed">
				<tr>
				<td>
				<?php _e( 'Exceptional working days, e.g. a specific Sunday you decided to work:', 'appointments') ?>
				</td>
				</tr>
				<tr>
				<td>
				<input class="datepick" id="open_datepick" type="text" style="width:100%" name="open[exceptional_days]" value="<?php if (isset($result["open"])) echo $result["open"]?>" />
				</td>
				</tr>

				<tr>
				<td>
				<?php _e( 'Exceptional NON working days, e.g. holidays:', 'appointments') ?>
				</td>
				</tr>
				<tr>
				<td>
				<input class="datepick" id="closed_datepick" type="text" style="width:100%" name="closed[exceptional_days]" value="<?php if (isset($result["closed"])) echo $result["closed"]?>" />
				</td>
				</tr>

				<tr>
				<td>
				<span class="description"><?php _e('Please enter each date using YYYY-MM-DD format (e.g. 2012-08-13) and separate each day with a comma. Datepick will allow entering multiple dates. ', 'appointments')?></span>
				</td>
				</tr>
				</table>

				<input type="hidden" name="location" value="0" />
				<input type="hidden" name="action_app" value="save_exceptions" />
				<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
				<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Exceptional Days', 'appointments') ?>" />
				</p>

			</form>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$('#app_provider_id').change(function(){
					var app_provider_id = $('#app_provider_id option:selected').val();
					window.location.href = "<?php echo admin_url('admin.php?page=app_settings&tab=exceptions')?>" + "&app_provider_id=" + app_provider_id;
				});
				$("#open_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
				$("#closed_datepick").datepick({dateFormat: 'yyyy-mm-dd',multiSelect: 999, monthsToShow: 2});
			});
			</script>

		<?php break; ?>

		<?php case 'services': _e( '<i>Here you should define your services for which your client will be making appointments. <b>There must be at least one service defined.</b> Capacity is the number of customers that can take the service at the same time. Enter 0 for no specific limit (Limited to number of service providers, or to 1 if no service provider is defined for that service). Price is only required if you request payment to accept appointments. You can define a description page for the service you are providing.</i>', 'appointments') ?>
			<div class='submit'>
			<input type="button" id="add_service" class='button-secondary' value='<?php _e( 'Add New Service', 'appointments' ) ?>' />
			</div>

			<form method="post" action="" >

				<table class="widefat fixed" id="services-table" >
				<tr>
				<th style="width:5%"><?php _e( 'ID', 'appointments') ?></th>
				<th style="width:35%"><?php _e( 'Name', 'appointments') ?></th>
				<th style="width:10%"><?php _e( 'Capacity', 'appointments') ?></th>
				<th style="width:15%"><?php _e( 'Duration (mins)', 'appointments') ?></th>
				<th style="width:10%"><?php echo __( 'Price', 'appointments') . ' ('. $this->options['currency']. ')' ?></th>
				<th style="width:25%"><?php _e( 'Description page', 'appointments') ?></th>
				</tr>
				<?php
				$services = $this->get_services();
				$max_id = null;
				if ( is_array( $services ) && $nos = count( $services ) ) {
					foreach ( $services as $service ) {
						echo $this->add_service( true, $service );
						if ( $service->ID > $max_id )
							$max_id = $service->ID;
					}
				}
				else {
					echo '<tr class="no_services_defined"><td colspan="4">'. __( 'No services defined', 'appointments' ) . '</td></tr>';
				}
				?>

				</table>

				<div class='submit' id='div_save_services' <?php if ($max_id==null) echo 'style="display:none"' ?>>
				<input type="hidden" name="number_of_services" id="number_of_services" value="<?php echo $max_id;?>" />
				<input type="hidden" name="action_app" value="save_services" />
				<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
				<input class='button-primary' type='submit' value='<?php _e( 'Save Services', 'appointments' ) ?>' />
				&nbsp;&nbsp;
				<?php _e( '<i>Tip: To delete a service, just clear its name and save.</i>', 'appointments' ); ?>
				</div>

			</form>
			<?php break; ?>

			<?php case 'workers': printf( __( '<i>Here you can optionally select your service providers, i.e. workers, and assign them to certain services. Your service providers must be users of the website. To add a new user %s. You can define additional price for them. This will be added to the price of the service. You can define a bio page for the service provider. <br />A dummy service provider is a user whose emails are redirected to another user that is set on the General tab.</i>', 'appointments'), '<a href="'.admin_url('user-new.php').'">' . __('Click here', 'appointments') . '</a>'); ?>
			<div class='submit'>
			<input type="button" id="add_worker" class='button-secondary' value='<?php _e( 'Add New Service Provider', 'appointments' ) ?>' />
			<img class="add-new-waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
			</div>

			<form method="post" action="" >

				<table class="widefat fixed" id="workers-table" >
				<tr>
				<th style="width:5%"><?php _e( 'ID', 'appointments') ?></th>
				<th style="width:25%"><?php _e( 'Service Provider', 'appointments') ?></th>
				<th style="width:10%"><?php _e( 'Dummy?', 'appointments') ?></th>
				<th style="width:10%"><?php  echo __( 'Additional Price', 'appointments') . ' ('. $this->options['currency']. ')' ?></th>
				<th style="width:25%"><?php _e( 'Services Provided*', 'appointments') ?></th>
				<th style="width:25%"><?php _e( 'Bio page', 'appointments') ?></th>
				</tr>
				<tr>
				<td colspan="6"><span class="description" style="font-size:11px"><?php _e('* <b>You must select at least one service, otherwise provider will not be saved!</b>', 'appointments') ?></span>
				</td>
				</tr>
				<?php
				$workers = $wpdb->get_results("SELECT * FROM " . $this->workers_table . " " );
				$max_id = 0;
				if ( is_array( $workers ) && $nos = count( $workers ) ) {
					foreach ( $workers as $worker ) {
						echo $this->add_worker( true, $worker );
						if ( $worker->ID > $max_id )
							$max_id = $worker->ID;
					}
				}
				else {
					echo '<tr class="no_workers_defined"><td colspan="6">'. __( 'No service providers defined', 'appointments' ) . '</td></tr>';
				}
				?>

				</table>

				<div class='submit' id='div_save_workers' <?php if (!$max_id) echo 'style="display:none"' ?>>
				<input type="hidden" name="number_of_workers" id="number_of_workers" value="<?php echo $max_id;?>" />
				<input type="hidden" name="action_app" value="save_workers" />
				<?php wp_nonce_field( 'update_app_settings', 'app_nonce' ); ?>
				<input class='button-primary' type='submit' value='<?php _e( 'Save Service Providers', 'appointments' ) ?>' />
				&nbsp;&nbsp;
				<?php _e( '<i>Tip: To remove a service provider, uncheck all "Services Provided" selections and save.</i>', 'appointments' ); ?>
				</div>

			</form>
			<script type="text/javascript">
			var multiselect_options = {
				noneSelectedText:'<?php echo esc_js( __('Select services', 'appointments' )) ?>',
				checkAllText:'<?php echo esc_js( __('Check all', 'appointments' )) ?>',
				uncheckAllText:'<?php echo esc_js( __('Uncheck all', 'appointments' )) ?>',
				selectedText:'<?php echo esc_js( __('# selected', 'appointments' )) ?>',
				selectedList:5,
				position: {
				  my: 'left bottom',
				  at: 'left top'
			   }
			};
			jQuery(document).ready(function($){
				$(".add_worker_multiple").multiselect(multiselect_options);
			});
			</script>
			<?php break; ?>

		<?php case 'log':	?>
		<div class="postbox">
			<div class="inside" id="app_log">
			<?php
				if ( is_writable( $this->uploads_dir ) ) {
					if ( file_exists( $this->log_file ) )
						echo nl2br( file_get_contents( $this->log_file ) );
					else
						echo __( 'There are no log records yet.', 'appointments' );
				}
				else
					echo __( 'Uploads directory is not writable.', 'appointments' );
				?>
			</div>
		</div>
			<table class="form-table">
				<tr valign="top">
					<th scope="row" >
					<input type="button" id="log_clear_button" class="button-secondary" value="<?php _e('Clear Log File') ?>" title="<?php _e('Clicking this button deletes logs saved on the server') ?>" />
					</th>
				</tr>
			</table>
		<?php break; ?>

		<?php
			case 'addons': App_AddonHandler::create_addon_settings(); break;
		?>

		<?php
		// For custom tab additions
		case 'custom1':	do_action( 'app_additional_tab1' ); break;
		case 'custom2':	do_action( 'app_additional_tab2' ); break;
		case 'custom3':	do_action( 'app_additional_tab3' ); break;

		default: do_action('app-settings-tabs', $tab); break;

		} // End of the big switch ?>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('#add_service').click(function(){
				$('.add-new-waiting').show();
				var n = 1;
				if ( $('#number_of_services').val() > 0 ) {
					n = parseInt( $('#number_of_services').val() ) + 1;
				}
				$('#services-table').append('<?php echo $this->esc_rn( $this->add_service() )?>');
				$('#number_of_services').val(n);
				$('#div_save_services').show();
				$('.no_services_defined').hide();
				$('.add-new-waiting').hide();
			});
			$('#add_worker').click(function(){
				$('.add-new-waiting').show();
				var k = parseInt( $('#number_of_workers').val() ) + 1;
				$('#workers-table').append('<?php echo $this->esc_rn( $this->add_worker() )?>');
				$('#number_of_workers').val(k);
				$('#div_save_workers').show();
				$('.no_workers_defined').hide();
				$(".add_worker_multiple").multiselect( multiselect_options );
				$('.add-new-waiting').hide();
			});
			$('#log_clear_button').click(function() {
				if ( !confirm('<?php echo esc_js( __("Are you sure to clear the log file?","appointments") ) ?>') ) {return false;}
				else{
					$('.add-new-waiting').show();
					var data = {action: 'delete_log', nonce: '<?php echo wp_create_nonce() ?>'};
					$.post(ajaxurl, data, function(response) {
						$('.add-new-waiting').hide();
						if ( response && response.error ) {
							alert(response.error);
						}
						else{
							$("#app_log").html('<?php echo esc_js( __("Log file cleared...","appointments") ) ?>');
						}
					},'json');
				}
			});
		});
		</script>
	<?php
	}

	function delete_log(){
		// check_ajax_referer( );
		if ( !unlink( $this->log_file ) )
			die( json_encode( array('error' => esc_js( __('Log file could not be deleted','appointments')))));
		die();
	}

	/**
	 *	Add a service
	 *  @param php: True if this will be used in first call, false if this is js
	 *  @param service: Service object that will be displayed (only when php is true)
	 */
	function add_service( $php=false, $service='' ) {
		if ( $php ) {
			if ( is_object($service)) {
				$n = $service->ID;
				$name = $service->name;
				$capacity = $service->capacity;
				$price = $service->price;
			 }
			 else return;
		}
		else {
			$n = "'+n+'";
			$name = '';
			$capacity = '0';
			$price = '';
		}

		$min_time = $this->get_min_time();

		$html = '';
		$html .= '<tr><td>';
		$html .= $n;
		$html .= '</td><td>';
		$html .= '<input style="width:100%" type="text" name="services['.$n.'][name]" value="'.stripslashes( $name ).'" />' . apply_filters('app-settings-services-service-name', '', $n);
		$html .= '</td><td>';
		$html .= '<input style="width:90%" type="text" name="services['.$n.'][capacity]" value="'.$capacity.'" />';
		$html .= '</td><td>';
		$html .= '<select name="services['.$n.'][duration]" >';
		$k_max = apply_filters( 'app_selectable_durations', min( 24, (int)(1440/$min_time) ) );
		for ( $k=1; $k<=$k_max; $k++ ) {
			if ( $php && is_object( $service ) && $k * $min_time == $service->duration )
				$html .= '<option selected="selected">'. ($k * $min_time) . '</option>';
			else
				$html .= '<option>'. ($k * $min_time) . '</option>';
		}
		$html .= '</select>';
		$html .= '</td><td>';
		$html .= '<input style="width:90%" type="text" name="services['.$n.'][price]" value="'.$price.'" />';
		$html .= '</td><td>';
		$pages = apply_filters('app-service_description_pages-get_list', array());
		if (empty($pages)) $pages = get_pages( apply_filters('app_pages_filter',array() ) );
		$html .= '<select name="services['.$n.'][page]" >';
		$html .= '<option value="0">'. __('None','appointments') .'</option>';
		foreach( $pages as $page ) {
			if ( $php )
				$title = esc_attr( $page->post_title );
			else
				$title = esc_js( $page->post_title );

			if ( $php && is_object( $service ) && $service->page == $page->ID )
				$html .= '<option value="'.$page->ID.'" selected="selected">'. $title . '</option>';
			else
				$html .= '<option value="'.$page->ID.'">'. $title . '</option>';
		}
		$html .= '</select>';
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 *	Add a worker
	 *  @param php: True if this will be used in first call, false if this is js
	 *  @param worker: Worker object that will be displayed (only when php is true)
	 */
	function add_worker( $php=false, $worker='' ) {
		if ( $php ) {
			if ( is_object($worker)) {
				$k = $worker->ID;
				if ( $this->is_dummy( $worker->ID ) )
					$dummy = ' checked="checked"';
				else
					$dummy = "";
				$price = $worker->price;
				$workers = wp_dropdown_users( array( 'echo'=>0, 'show'=>'user_login', 'selected' => $worker->ID, 'name'=>'workers['.$k.'][user]', 'exclude'=>apply_filters('app_filter_providers', null) ) );
			}
			 else return;
		}
		else {
			$k = "'+k+'";
			$price = '';
			$dummy = '';
			$workers =str_replace( array("\t","\n","\r"), "", str_replace( array("'", "&#039;"), array('"', "'"), wp_dropdown_users( array ( 'echo'=>0, 'show'=>'user_login', 'include'=>0, 'name'=>'workers['.$k.'][user]', 'exclude'=>apply_filters('app_filter_providers', null)) ) ) );
		}
		global $wpdb;

		$html = '';
		$html .= '<tr><td>';
		$html .= $k;
		$html .= '</td><td>';
		$html .= $workers  . apply_filters('app-settings-workers-worker-name', '', $worker->ID, $worker);
		$html .= '</td><td>';
		$html .= '<input type="checkbox" name="workers['.$k.'][dummy]" '.$dummy.' />';
		$html .= '</td><td>';
		$html .= '<input type="text" name="workers['.$k.'][price]" style="width:80%" value="'.$price.'" />';
		$html .= '</td><td>';
		$services = $this->get_services();
		if ( $services ) {
			if ( $php && is_object( $worker ) )
				$services_provided = $this->_explode( $worker->services_provided );
			else
				$services_provided = false;
			$html .= '<select class="add_worker_multiple" style="width:280px" multiple="multiple" name="workers['.$k.'][services_provided][]" >';
			foreach ( $services as $service ) {
				if ( $php )
					$title = stripslashes( $service->name );
				else
					$title = esc_js( $service->name );

				if ( is_array( $services_provided ) && in_array( $service->ID, $services_provided ) )
					$html .= '<option value="'. $service->ID . '" selected="selected">'. $title . '</option>';
				else
					$html .= '<option value="'. $service->ID . '">'. $title . '</option>';
			}
			$html .= '</select>';
		}
		else
			$html .= __( 'No services defined', 'appointments' );
		$html .= '</td><td>';
		$pages = apply_filters('app-biography_pages-get_list', array());
		if (empty($pages)) $pages = get_pages( apply_filters('app_pages_filter',array() ) );
		$html .= '<select name="workers['.$k.'][page]" >';
		$html .= '<option value="0">'. __('None','appointments') .'</option>';
		foreach( $pages as $page ) {
			if ( $php )
				$title = esc_attr( $page->post_title );
			else
				$title = esc_js( $page->post_title );

			if ( $php && is_object( $worker ) && $worker->page == $page->ID )
				$html .= '<option value="'.$page->ID.'" selected="selected">'. $title . '</option>';
			else
				$html .= '<option value="'.$page->ID.'">'. $title . '</option>';
		}
		$html .= '</select>';
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 *	Create a working hour form
	 *  Worker can be forced.
	 *  @param status: Open (working hours) or close (break hours)
	 */
	function working_hour_form( $status='open' ) {

		$this->get_lsw();

		if ( isset( $this->options["admin_min_time"] ) && $this->options["admin_min_time"] )
			$min_time = $this->options["admin_min_time"];
		else
			$min_time = $this->get_min_time();

		$min_secs = 60 * $min_time;

		$wb = $this->get_work_break( $this->location, $this->worker, $status );
		if ( $wb )
			$whours = maybe_unserialize( $wb->hours );
		else
			$whours = array();

		$form = '';
		$form .= '<table>';
		if ( 'open' == $status )
			$form .= '<tr><th>'.__('Day', 'appointments').'</th><th>'.__('Work?', 'appointments' ).'</th><th>'.__('Start', 'appointments').'</th><th>'.__('End', 'appointments').'</th></tr>';
		else
			$form .= '<tr><th>'.__('Day', 'appointments').'</th><th>'.__('Give break?','appointments').'</th><th>'.__('Start','appointments').'</th><th>'.__('End','appointments').'</th></tr>';
		foreach ( $this->weekdays() as $day ) {
			if (!empty($whours[$day]['active']) && is_array($whours[$day]['active'])) {
				$total_whour_segments = count($whours[$day]['active']) - 1;
				// We have multiple breaks for today.
				foreach ($whours[$day]['active'] as $idx => $active) {
					$form .= '<tr ' . ($idx < $total_whour_segments ? 'class="app-repeated"' : '') . '><td>';
					if (0 == $idx) $form .= $day;
					$form .= '</td>';
					$form .= '<td>';
					$form .= '<select name="'.$status.'['.$day.'][active][' . $idx . ']">';
					if ( 'yes' == $active )
						$s = " selected='selected'";
					else $s = '';
					$form .= '<option value="no">'.__('No', 'appointments').'</option>';
					$form .= '<option value="yes"'.$s.'>'.__('Yes', 'appointments').'</option>';
					$form .= '</select>';
					$form .= '</td>';
					$form .= '<td>';
					$form .= '<select name="'.$status.'['.$day.'][start][' . $idx . ']">';
					for ( $t=0; $t<3600*24; $t=$t+$min_secs ) {
						$dhours = $this->secs2hours( $t ); // Hours in 08:30 format
						if ( isset($whours[$day]['start'][$idx]) && $dhours == $whours[$day]['start'][$idx] )
							$s = " selected='selected'";
						else $s = '';

						$form .= '<option'.$s.'>';
						$form .= $dhours;
						$form .= '</option>';
					}
					$form .= '</select>';
					$form .= '</td>';

					$form .= '<td>';
					$form .= '<select name="'.$status.'['.$day.'][end][' . $idx . ']">';
					for ( $t=$min_secs; $t<=3600*24; $t=$t+$min_secs ) {
						$dhours = $this->secs2hours( $t ); // Hours in 08:30 format
						if ( isset($whours[$day]['end'][$idx]) && $dhours == $whours[$day]['end'][$idx] )
							$s = " selected='selected'";
						else $s = '';

						$form .= '<option'.$s.'>';
						$form .= $dhours;
						$form .= '</option>';
					}
					$form .= '</select>';
					if ('closed' == $status && $idx == 0 && 'yes' == $active) $form .= '&nbsp;<a href="#add_break" class="app-add_break" title="' . esc_attr(__('Add break', 'appointments')) . '"><span>' . __('Add break', 'appointments') . '</span></a>';
					$form .= '</td>';

					$form .= '</tr>';
				}
			} else {
				// Oh, it's just one break.
				$form .= '<tr><td>';
				$form .= $day;
				$form .= '</td>';
				$form .= '<td>';
				$form .= '<select name="'.$status.'['.$day.'][active]">';
				if ( isset($whours[$day]['active']) && 'yes' == $whours[$day]['active'] )
					$s = " selected='selected'";
				else $s = '';
				$form .= '<option value="no">'.__('No', 'appointments').'</option>';
				$form .= '<option value="yes"'.$s.'>'.__('Yes', 'appointments').'</option>';
				$form .= '</select>';
				$form .= '</td>';
				$form .= '<td>';
				$form .= '<select name="'.$status.'['.$day.'][start]">';
				for ( $t=0; $t<3600*24; $t=$t+$min_secs ) {
					$dhours = $this->secs2hours( $t ); // Hours in 08:30 format
					if ( isset($whours[$day]['start']) && $dhours == $whours[$day]['start'] )
						$s = " selected='selected'";
					else $s = '';

					$form .= '<option'.$s.'>';
					$form .= $dhours;
					$form .= '</option>';
				}
				$form .= '</select>';
				$form .= '</td>';

				$form .= '<td>';
				$form .= '<select name="'.$status.'['.$day.'][end]">';
				for ( $t=$min_secs; $t<=3600*24; $t=$t+$min_secs ) {
					$dhours = $this->secs2hours( $t ); // Hours in 08:30 format
					if ( isset($whours[$day]['end']) && $dhours == $whours[$day]['end'] )
						$s = " selected='selected'";
					else $s = '';

					$form .= '<option'.$s.'>';
					$form .= $dhours;
					$form .= '</option>';
				}
				$form .= '</select>';
				if ('closed' == $status && isset($whours[$day]['active']) && 'yes' == $whours[$day]['active']) $form .= '&nbsp;<a href="#add_break" class="app-add_break" title="' . esc_attr(__('Add break', 'appointments')) . '"><span>' . __('Add break', 'appointments') . '</span></a>';
				$form .= '</td>';

				$form .= '</tr>';
			}
		}

		$form .= '</table>';

		return $form;
	}

	/**
	 *	Return results for appointments
	 */
	function get_admin_apps($type, $startat, $num) {

		if( isset( $_GET['s'] ) && trim( $_GET['s'] ) != '' ) {
			$s = $_GET['s'];
			$add = " AND ( name LIKE '%{$s}%' OR email LIKE '%{$s}%' OR ID IN ( SELECT ID FROM {$this->db->users} WHERE user_login LIKE '%{$s}%' ) ) ";
		}
		else
			$add = "";

		if(isset($_GET['app_service_id']) && $_GET['app_service_id'] )
			$add .= " AND service={$_GET['app_service_id']} ";

		if(isset($_GET['app_provider_id']) && $_GET['app_provider_id'] )
			$add .= " AND worker={$_GET['app_provider_id']} ";

		if ( isset( $_GET['app_order_by']) && $_GET['app_order_by'] )
			$order_by = str_replace( '_', ' ', $_GET['app_order_by'] );
		else
			$order_by = "ID DESC";

		switch($type) {

			case 'active':
						$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('confirmed', 'paid') {$add} ORDER BY {$order_by} LIMIT {$startat}, {$num}";
						break;
			case 'pending':
						$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('pending') {$add} ORDER BY {$order_by} LIMIT {$startat}, {$num}";
						break;
			case 'completed':
						$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('completed') {$add} ORDER BY {$order_by} LIMIT {$startat}, {$num}";
						break;
			case 'removed':
						$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('removed') {$add} ORDER BY {$order_by} LIMIT {$startat}, {$num}";
						break;
			case 'reserved':
						$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('reserved') {$add} ORDER BY {$order_by} LIMIT {$startat}, {$num}";
						break;
			default:
						$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->app_table} WHERE status IN ('confirmed', 'paid') {$add} ORDER BY {$order_by} LIMIT {$startat}, {$num}";
						break;
		}

		return $this->db->get_results( $sql );

	}

	function get_apps_total() {
		return $this->db->get_var( "SELECT FOUND_ROWS();" );
	}

	/**
	 *	Creates the list for Appointments admin page
	 */
	function appointment_list() {

		global $page, $action, $type;

		wp_reset_vars( array('type') );

		if(empty($type)) $type = 'active';

		$filter = array();

		if(isset($_GET['s'])) {
			$s = stripslashes($_GET['s']);
			$filter['s'] = $s;
		} else {
			$s = '';
		}

		if(isset($_GET['app_service_id']))
			$service_id = $_GET['app_service_id'];
		else
			$service_id = '';

		if(isset($_GET['app_provider_id']))
			$worker_id = $_GET['app_provider_id'];
		else
			$worker_id = '';

		if(isset($_GET['app_order_by']))
			$order_by = $_GET['app_order_by'];
		else
			$order_by = '';

		?>
		<div id="wpbody-content">
		<div class='wrap'>
			<div class="icon32" style="margin:8px 0 0 0"><img src="<?php echo $this->plugin_url . '/images/appointments.png'; ?>" /></div>
			<h2><?php echo __('Appointments','appointments'); ?><a href="javascript:void(0)" class="add-new-h2"><?php _e('Add New', 'appointments')?></a>
			<img class="add-new-waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
			</h2>

			<ul class="subsubsub">
				<li><a href="<?php echo add_query_arg('type', 'active'); ?>" class="rbutton <?php if($type == 'active') echo 'current'; ?>"><?php  _e('Active appointments', 'appointments'); ?></a> | </li>
				<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending appointments', 'appointments'); ?></a> | </li>
				<li><a href="<?php echo add_query_arg('type', 'completed'); ?>" class="rbutton <?php if($type == 'completed') echo 'current'; ?>"><?php  _e('Completed appointments', 'appointments'); ?></a> | </li>
				<li><a href="<?php echo add_query_arg('type', 'reserved'); ?>" class="rbutton <?php if($type == 'reserved') echo 'current'; ?>"><?php  _e('Reserved by GCal', 'appointments'); ?></a> | </li>
				<li><a href="<?php echo add_query_arg('type', 'removed'); ?>" class="rbutton <?php if($type == 'removed') echo 'current'; ?>"><?php  _e('Removed appointments', 'appointments'); ?></a></li>
				<li><a href="javascript:void(0)" class="info-button" title="<?php _e('Click to toggle information about statuses', 'appointments')?>"><img src="<?php echo $this->plugin_url . '/images/information.png'?>" alt="" /></a></li>
			</ul>
		<br /><br />
		<span class="description status-description" style="display:none;">
		<ul>
		<li><?php _e('<b>Completed:</b> Appointment became overdue after it is confirmed or paid', 'appointments') ?></li>
		<li><?php _e('<b>Removed:</b> Appointment was not paid for or was not confirmed manually in the allowed time', 'appointments') ?></li>
		<li><?php _e('<b>Reserved by GCal:</b> If you import appointments from Google Calender using Google Calendar API, that is, synchronize your calendar with Appointments+, events in your Google Calendar will be regarded as appointments and they will be shown here. These records cannot be edited here. Use your Google Calendar instead. They will be automatically updated in A+ too.', 'appointments') ?></li>
		<li><?php _e('If you require payment:', 'appointments') ?></li>
		<li><?php _e('<b>Active/Paid:</b> Paid and confirmed by Paypal', 'appointments') ?></li>
		<li><?php _e('<b>Pending:</b> Client applied for the appointment, but not yet paid.', 'appointments') ?></li>
		</ul>
		<ul>
		<li><?php _e('If you do not require payment:', 'appointments') ?></li>
		<li><?php _e('<b>Active/Confirmed:</b> Manually confirmed', 'appointments') ?></li>
		<li><?php _e('<b>Pending:</b> Client applied for the appointment, but it is not manually confirmed.', 'appointments') ?></li>
		</ul>
		</span>

		<form method="get" action="<?php echo add_query_arg('page', 'appointments'); ?>" class="search-form">
		<p class="search-box">
			<label for="app-search-input" class="screen-reader-text"><?php _e('Search Client','appointments'); ?>:</label>
			<input type="hidden" value="appointments" name="page" />
			<input type="hidden" value="<?php echo $type?>" name="type" />
			<input type="hidden" value="<?php echo $service_id?>" name="app_service_id" />
			<input type="hidden" value="<?php echo $worker_id?>" name="app_provider_id" />
			<input type="text" value="<?php echo esc_attr($s); ?>" name="s" />
			<input type="submit" class="button" value="<?php _e('Search Client','appointments'); ?>" />
		</p>
		</form>

		<br class='clear' />

		<div class="tablenav top">

			<div class="alignleft actions">
				<form id="app-bulk-change-form" method="post" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
					<input type="hidden" value="appointments" name="page" />
					<input type="hidden" value="1" name="app_status_change" />
					<select name="app_new_status" style='float:none;'>
						<option value=""><?php _e('Bulk status change','appointments'); ?></option>
						<?php foreach ( $this->get_statuses() as $value=>$name ) {
							echo '<option value="'.$value.'" class="hide-if-no-js">'.$name.'</option>';
						} ?>
					</select>
					<input type="submit" class="button app-change-status-btn" value="<?php _e('Change Status','appointments'); ?>" />
				</form>
			</div>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				$(".app-change-status-btn").click(function(e){
					var button = $(this);
					e.preventDefault();
					// var data = { 'app[]' : []};
					$("td.app-check-column input:checkbox:checked").each(function() {
					  // data['app[]'].push($(this).val());
					    button.after('<input type="hidden" name="app[]" value="'+$(this).val()+'"/>');
					});

						$('#app-bulk-change-form').submit();

				});
			});
			</script>

			<div class="alignright">

				<div class="alignleft actions">
					<form method="get" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
						<input type="hidden" value="appointments" name="page" />
						<input type="hidden" value="<?php echo $type?>" name="type" />
						<input type="hidden" value="<?php echo $service_id?>" name="app_service_id" />
						<input type="hidden" value="<?php echo $worker_id?>" name="app_provider_id" />
						<select name="app_order_by" style='float:none;'>
							<option value=""><?php _e('Sort by','appointments'); ?></option>
							<option value="ID" <?php selected( $order_by, 'ID' ); ?>><?php _e('Creation date (Oldest to newest)','appointments'); ?></option>
							<option value="ID_DESC" <?php selected( $order_by, 'ID_DESC' ); ?>><?php _e('Creation date (Newest to oldest)','appointments'); ?></option>
							<option value="start" <?php selected( $order_by, 'start' ); ?>><?php _e('Appointment date (Closest first)','appointments'); ?></option>
							<option value="start_DESC" <?php selected( $order_by, 'start_DESC' ); ?>><?php _e('Appointment date (Closest last)','appointments'); ?></option>
						</select>
						<input type="submit" class="button" value="<?php _e('Sort','appointments'); ?>" />
					</form>
				</div>

				<div class="alignleft actions">
					<form method="get" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
						<input type="hidden" value="appointments" name="page" />
						<input type="hidden" value="<?php echo $type?>" name="type" />
						<input type="hidden" value="<?php echo $worker_id?>" name="app_provider_id" />
						<select name="app_service_id" style='float:none;'>
							<option value=""><?php _e('Filter by service','appointments'); ?></option>
						<?php
						$services = $this->get_services();
						if ( $services ) {
							foreach ( $services as $service ) {
								if ( $service_id == $service->ID )
									$selected = " selected='selected' ";
								else
									$selected = "";
								echo '<option '.$selected.' value="'.$service->ID.'">'. $this->get_service_name( $service->ID ) .'</option>';
							}
						}
						?>
						</select>
						<input type="submit" class="button" value="<?php _e('Filter','appointments'); ?>" />
					</form>
				</div>

				<div class="alignleft actions">
					<form method="get" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
						<input type="hidden" value="appointments" name="page" />
						<input type="hidden" value="<?php echo $type?>" name="type" />
						<input type="hidden" value="<?php echo $service_id?>" name="app_service_id" />
						<select name="app_provider_id" style='float:none;'>
							<option value=""><?php _e('Filter by service provider','appointments'); ?></option>
						<?php
						$workers = $this->get_workers();
						if ( $workers ) {
							foreach ( $workers as $worker ) {
								if ( $worker_id == $worker->ID )
									$selected = " selected='selected' ";
								else
									$selected = "";
								echo '<option '.$selected.' value="'.$worker->ID.'">'. $this->get_worker_name( $worker->ID ) .'</option>';
							}
						}
						?>
						</select>
						<input type="submit" class="button" value="<?php _e('Filter','appointments'); ?>" />
					</form>

				</div>

				<div class="alignright actions">
					<form method="get" action="<?php echo add_query_arg('page', 'appointments'); ?>" >
						<input type="hidden" value="appointments" name="page" />
						<input type="hidden" value="<?php echo $type?>" name="type" />
						<input type="hidden" value="" name="app_service_id" />
						<input type="hidden" value="" name="app_provider_id" />
						<input type="hidden" value="" name="app_order_by" />
						<input type="hidden" value="" name="s" />
						<input type="submit" class="button" value="<?php _e('Reset sort order and filters','appointments'); ?>" />
					</form>

				</div>

			</div>
		</div>

			<?php
				$this->myapps($type);

			?>
			<br />
			<br />
			<form action="<?php echo admin_url('admin-ajax.php?action=app_export'); ?>" method="post">
				<input type="hidden" name="action" value="app_export" />
				<input type="submit" class="button-secondary" value="<?php _e('Export Appointments','appointments') ?>" title="<?php _e('If you click this button a CSV file containing ALL appointment records will be saved on your PC','appointments') ?>" />
			</form>

		</div> <!-- wrap -->
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$(".info-button").click(function(){
				$(".status-description").toggle('fast');
			});
		});
		</script>
		<?php

	}

	/**
	 * Save a CSV file of all appointments
	 * @since 1.0.9
	 */
	function export(){

		$apps = $this->db->get_results( "SELECT * FROM " . $this->app_table . " ORDER BY ID", ARRAY_A );

		if ( !is_array( $apps ) || empty( $apps ) )
			die(__('Nothing to download!','appointments'));

		$file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
		// Add field names to the file
		fputcsv( $file, $this->db->get_col_info() );

		foreach ( $apps as $app ) {
			array_walk( $app, array(&$this, 'export_helper') );
			fputcsv( $file, $app );
		}

		$filename = "appointments_".date('F')."_".date('d')."_".date('Y').".csv";

		//serve the file
		rewind($file);
		ob_end_clean(); //kills any buffers set by other plugins
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		$output = stream_get_contents($file);
		//$output = $output . "\xEF\xBB\xBF"; // UTF-8 BOM
		header('Content-Length: ' . strlen($output));
		fclose($file);
		die($output);
	}

	/**
	 * Helper function for export
	 * @since 1.0.9
	 */
	function export_helper( &$value, $key ) {
		if ( 'created' == $key || 'start' == $key || 'end' == $key )
			$value = mysql2date( $this->datetime_format, $value );
		else if ( 'user' == $key && $value ) {
			$userdata = get_userdata( $value );
			if ( $userdata )
				$value = $userdata->user_login;
		}
		else if ( 'service' == $key )
			$value = $this->get_service_name( $value );
		else if ( 'worker' == $key )
			$value = $this->get_worker_name( $value );
	}

	/**
	 * Helper function for displaying appointments
	 *
	 */
	function myapps($type = 'active') {

		if(empty($_GET['paged'])) {
			$paged = 1;
		} else {
			$paged = ((int) $_GET['paged']);
		}

		if ( isset( $this->options["records_per_page"] ) && $this->options["records_per_page"] )
			$rpp = $this->options["records_per_page"];
		else
			$rpp = 50;

		$startat = ($paged - 1) * $rpp;

		$apps = $this->get_admin_apps($type, $startat, $rpp);
		$total = $this->get_apps_total();

		$columns = array();

		if ( true || isset( $_GET["type"] ) && 'removed' == $_GET["type"] )
			$columns['delete'] = '<input type="checkbox" />';
		$columns['app_ID'] = __('ID','appointments');
		$columns['user'] = __('Client','appointments');
		$columns['date'] = __('Date/Time','appointments');
		$columns['service'] = __('Service','appointments');
		$columns['worker'] = __('Provider','appointments');
		$columns['status'] = __('Status','appointments');

		$trans_navigation = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'total' => ceil($total / $rpp),
			'current' => $paged
		));

		if ( $trans_navigation ) {
			echo '<div class="tablenav">';
			echo "<div class='tablenav-pages'>$trans_navigation</div>";
			echo '</div>';
		}

		// Only for "Removed" tab
		if ( true || isset( $_GET["type"] ) && 'removed' == $_GET["type"] ) {
		?>
			<form method="post" >

		<?php
		}
		?>

			<table cellspacing="0" class="widefat">
				<thead>
				<tr>
				<?php
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?> app-column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</thead>

				<tfoot>
				<tr>
				<?php
					reset($columns);
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?> app-column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</tfoot>

				<tbody>
					<?php
					if($apps) {
						foreach($apps as $key => $app) {

							?>
							<tr valign="middle" class="alternate app-tr">
							<?php
							// Only for "Removed" tab
							if ( true || isset( $_GET["type"] ) && 'removed' == $_GET["type"] ) {
							?>
								<td class="column-delete check-column app-check-column">
								<input type="checkbox" name="app[]" value="<?php echo $app->ID;?>" />
								</td>

							<?php
							}
							?>
								<td class="column-app_ID">
									<span class="span_app_ID"><?php	echo $app->ID;?></span>

								</td>
								<td class="column-user">
									<?php
										echo $this->get_client_name( $app->ID );
									?>
									<div class="row-actions">
									<a href="javascript:void(0)" class="app-inline-edit"><?php if ( 'reserved' == $app->status ) _e('See Details (Cannot be edited)', 'appointments'); else _e('See Details and Edit', 'appointments') ?></a>
									<img class="waiting" style="display:none;" src="<?php echo admin_url('images/wpspin_light.gif')?>" alt="">
									</div>
								</td>
								<td class="column-date">
									<?php
										echo mysql2date($this->datetime_format, $app->start);

									?>
								</td>
								<td class="column-service">
									<?php
									echo $this->get_service_name( $app->service );
									?>
								</td>
								<td class="column-worker">
									<?php
										echo $this->get_worker_name( $app->worker );
									?>
								</td>
								<td class="column-status">
									<?php
										if(!empty($app->status)) {
											echo App_Template::get_status_name($app->status);
										} else {
											echo __('None yet','appointments');
										}
									?>
								</td>
							</tr>
							<?php

						}
					}
					else {
						$columncount = count($columns);
						?>
						<tr valign="middle" class="alternate" >
							<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No appointments have been found.','appointments'); ?></td>
						</tr>
						<?php
					}
					?>

				</tbody>
			</table>
		<?php
		// Only for "Removed" tab
		if ( isset( $_GET["type"] ) && 'removed' == $_GET["type"] ) {
		?>
			<p>
			<input type="submit" id="delete_removed" class="button-secondary" value="<?php _e('Permanently Delete Selected Records', 'appointments') ?>" title="<?php _e('Clicking this button deletes logs saved on the server') ?>" />
			<input type="hidden" name="delete_removed" value="delete_removed" />

			</p>


		<?php } ?>
			</form>

			<script type="text/javascript">
			var service_price = new Array();
			<?php foreach( $this->get_services() as $service_obj ) { ?>
				service_price[<?php echo $service_obj->ID ?>] = '<?php echo $service_obj->price ?>';
			<?php
			}
			?>
			jQuery(document).ready(function($){
				$("#delete_removed").click( function() {
					if ( !confirm('<?php echo esc_js( __("Are you sure to delete the selected record(s)?","appointments") ) ?>') )
					{return false;}
					else {
						return true;
					}
				});
				var th_sel = $("th.column-delete input:checkbox");
				var td_sel = $("td.column-delete input:checkbox");
				th_sel.change( function() {
					if ( $(this).is(':checked') ) {
						td_sel.attr("checked","checked");
						th_sel.not(this).attr("checked","checked");
					}
					else{
						td_sel.removeAttr('checked');
						th_sel.not(this).removeAttr('checked');
					}
				});
				var col_len = $("table").find("tr:first th").length;
				// Add new
				$(".add-new-h2").click(function(){
					$(".add-new-waiting").show();
					var data = {action: 'inline_edit', col_len: col_len, app_id:0, nonce: '<?php echo wp_create_nonce() ?>'};
					$.post(ajaxurl, data, function(response) {
						$(".add-new-waiting").hide();
						if ( response && response.error ){
							alert(response.error);
						}
						else if (response) {
							$("table.widefat").prepend(response.result);
						}
						else {alert("<?php echo esc_js(__('Unexpected error','appointments'))?>");}
					},'json');
				});
				// Edit
				$(".app-inline-edit").click(function(){
					var app_parent = $(this).parents(".app-tr");
					app_parent.find(".waiting").show();
					var app_id = app_parent.find(".span_app_ID").html();
					var data = {action: 'inline_edit', col_len: col_len, app_id: app_id, nonce: '<?php echo wp_create_nonce() ?>'};
					$.post(ajaxurl, data, function(response) {
						app_parent.find(".waiting").hide();
						if ( response && response.error ){
							alert(response.error);
						}
						else if (response) {
							app_parent.hide();
							app_parent.after(response.result);
						}
						else {alert('<?php echo esc_js(__('Unexpected error','appointments'))?>');}
					},'json');
				});
				$("table").on("click", ".cancel", function(){
					$(".inline-edit-row").hide();
					$(".app-tr").show();
				});
				// Add datepicker only once and when focused
				// Ref: http://stackoverflow.com/questions/3796207/using-one-with-live-jquery
				$("table").on("focus", ".datepicker", function(e){
					if( $(e.target).data('focused')!='yes' ) {
						var php_date_format = "<?php echo $this->safe_date_format() ?>";
						var js_date_format = php_date_format.replace("F","MM").replace("j","dd").replace("Y","yyyy").replace("y","yy");
						$(".datepicker").datepick({dateFormat: js_date_format});
					}
					 $(e.target).data('focused','yes');
				});
				$("table").on("click", ".save", function(){
					var save_parent = $(this).parents(".inline-edit-row");
					save_parent.find(".waiting").show();
					var user = save_parent.find('select[name="user"] option:selected').val();
					var name = save_parent.find('input[name="cname"]').val();
					var email = save_parent.find('input[name="email"]').val();
					var phone = save_parent.find('input[name="phone"]').val();
					var address = save_parent.find('input[name="address"]').val();
					var city = save_parent.find('input[name="city"]').val();
					var service = save_parent.find('select[name="service"] option:selected').val();
					var worker = save_parent.find('select[name="worker"] option:selected').val();
					var price = save_parent.find('input[name="price"]').val();
					var date = save_parent.find('input[name="date"]').val();
					var time = save_parent.find('select[name="time"] option:selected').val();
					var note = save_parent.find('textarea').val();
					var status = save_parent.find('select[name="status"] option:selected').val();
					var resend = 0;
					if (save_parent.find('input[name="resend"]').is(':checked') ) { resend=1;}
					var app_id = save_parent.find('input[name="app_id"]').val();
					var data = {action: 'inline_edit_save', user:user, name:name, email:email, phone:phone, address:address,city:city, service:service, worker:worker, price:price, date:date, time:time, note:note, status:status, resend:resend, app_id: app_id, nonce: '<?php echo wp_create_nonce() ?>'};
					$(document).trigger('app-appointment-inline_edit-save_data', [data]);
					$.post(ajaxurl, data, function(response) {
						save_parent.find(".waiting").hide();
						if ( response && response.error ){
							save_parent.find(".error").html(response.error).show().delay(10000).fadeOut('slow');
						}
						else if (response) {
							save_parent.find(".error").html(response.result).show().delay(10000).fadeOut('slow');
						}
						else {alert("<?php echo esc_js(__('Unexpected error','appointments'))?>");}
					},'json');
				});
				// Change service price as selection changes
				$("table").on("change", 'select[name="service"]', function(){
					$(this).parents(".inline-edit-col").find('input[name="price"]').val(service_price[$(this).val()]);
				});
			});
			</script>
		<?php
	}

	/**
	 * Return a safe date format that datepick can use
	 * @return string
	 * @since 1.0.4.2
	 */
	function safe_date_format() {
		// Allowed characters
		$check = str_replace( array( '-', '/', ',', 'F', 'j', 'y', 'Y', 'd', 'M', 'm' ), '', $this->date_format );
		if ( '' == trim( $check ) )
			return $this->date_format;

		// Return a default safe format
		return 'F j Y';
	}

	/**
	 * Modify a date if it is non US
	 * Change d/m/y to d-m-y so that strtotime can behave correctly
	 * Also change local dates to m/d/y format
	 * @return string
	 * @since 1.0.4.2
	 */
	function to_us( $date ) {
		// Find the real format we are using
		$date_format = $this->safe_date_format();
		$date_arr = explode( '/', $date_format );
		if ( isset( $date_arr[0] ) && isset( $date_arr[1] ) && 'd/m' == $date_arr[0] .'/'. $date_arr[1] )
			return str_replace( '/', '-', $date );
		// Already US format
		if ( isset( $date_arr[0] ) && isset( $date_arr[1] ) && 'm/d' == $date_arr[0] .'/'. $date_arr[1] )
			return $date;

		global $wp_locale;
		if ( !is_object( $wp_locale )  || empty( $wp_locale->month ) ) {
			$this->locale_error = true;
			return $date;
		}

		$datepick_local_months = $this->datepick_local_months();
		$datepick_abb_local_months = $this->datepick_abb_local_months();

		$months = array( 'January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06',
						'July'=>'07','August'=>'08','September'=>'09','October'=>'10','November'=>'11','December'=>'12' );
		// A special check where locale is set, but language files are not loaded
		if ( strpos( $date_format, 'F' ) !== false || strpos( $date_format, 'M' ) !== false ) {
			$n = 0;
			$k = 0;
			foreach ( $months as $month_name => $month_no ) {
				$month_name_local = $wp_locale->get_month($month_no);
				$month_name_abb_local = $wp_locale->get_month_abbrev($month_name_local);
				if ( $month_name_local == $month_name )
					$n++;
				// Also check if any month will give a 1970 result
				if ( '1970-01-01' == date( 'Y-m-d', strtotime( $month_name . ' 1 2012' ) ) ) {
					$this->locale_error = true;
					return $date;
				}
				// Also check translation of datepick
				if ( strpos( $date_format, 'F' ) !== false ) {
					if ( $month_name_local != trim( $datepick_local_months[$k] ) ) {
						$this->locale_error = true;
						return $date;
					}
				}
				if ( strpos( $date_format, 'M' ) !== false ) {
					// Also check translation of datepick for short month names
					if ( $month_name_abb_local != trim( $datepick_abb_local_months[$k] ) ) {
						$this->locale_error = true;
						return $date;
					}
				}
				$k++;
			}
			if ( $n > 11 ) {
				// This means we shall use English
				$this->locale_error = true;
				return $date;
			}
		}

		// Check if F (long month name) is set
		if ( strpos( $date_format, 'F' ) !== false ) {
			foreach ( $months as $month_name => $month_no ) {
				$month_name_local = $wp_locale->get_month($month_no);
				if ( strpos( $date, $month_name_local ) !== false )
					return date( 'm/d/y', strtotime( str_replace( $month_name_local, $month_name, $date ) ) );
			}
		}

		if ( strpos( $date_format, 'M' ) !== false ) {
			// Check if M (short month name) is set
			$month_abb = array( );
			foreach ( $months as $month_name => $month_no ) {
				$month_name_local = $wp_locale->get_month($month_no);
				$month_name_abb_local = $wp_locale->get_month_abbrev($month_name_local);
				if ( strpos( $date, $month_name_abb_local ) !== false )
					return date( 'm/d/y', strtotime( str_replace( $month_name_abb_local, $month_name, $date ) ) );
			}
		}

		$this->locale_error = true;
		return $date;
	}

	// Edit or create appointments
	function inline_edit() {
		$safe_date_format = $this->safe_date_format();
		// Make a locale check to update locale_error flag
		$date_check = $this->to_us( date_i18n( $safe_date_format, strtotime('today') ) );

		global $wpdb;
		$app_id = $_POST["app_id"];
		if ( $app_id ) {
			$app = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE ID=".$app_id." " );
			if ( $this->locale_error )
				$start_date = date( $safe_date_format, strtotime( $app->start ) );
			else
				$start_date = date_i18n( $safe_date_format, strtotime( $app->start ) );

			$start_time = date_i18n( $this->time_format, strtotime( $app->start ) );
			$end_datetime = date_i18n( $this->datetime_format, strtotime( $app->end ) );
			// Is this a registered user?
			if ( $app->user ) {
				$name = get_user_meta( $app->user, 'app_name', true );
				if ( $name )
					$app->name = $name;

				$email = get_user_meta( $app->user, 'app_email', true );
				if ( $email )
					$app->email = $email;

				$phone = get_user_meta( $app->user, 'app_phone', true );
				if ( $phone )
					$app->phone = $phone;

				$address = get_user_meta( $app->user, 'app_address', true );
				if ( $address )
					$app->address = $address;

				$city = get_user_meta( $app->user, 'app_city', true );
				if ( $city )
					$app->city = $city;
			}
		}
		else {
			// Get maximum ID
			$app_max = $wpdb->get_var( "SELECT MAX(ID) FROM " . $this->app_table . " " );
			// Check if nothing has saved yet
			if ( !$app_max )
				$app_max = 0;
			$app = new stdClass(); // This means insert a new app object
			$app->ID = $app_max + 1 ; // We want to create a new record
			// Set other fields to default so that we don't get notice messages
			$app->user = $app->location = $app->worker = 0;
			$app->created = $app->end = $app->name = $app->email = $app->phone = $app->address = $app->city = $app->status = $app->sent = $app->sent_worker = $app->note = '';

			// Get first service and its price
			$app->service = $this->get_first_service_id();
			$_REQUEST['app_service_id'] = $app->service;
			$_REQUEST['app_provider_id'] = 0;
			$app->price = $this->get_price( );

			// Select time as next 1 hour
			$start_time = date_i18n( $this->time_format, intval(($this->local_time + 60*$this->get_min_time())/3600)*3600 );

			// Set start date as now + 60 minutes.
			if ( $this->locale_error ) {
				$start_date = date( $safe_date_format, $this->local_time + 60*$this->get_min_time() );
			}
			else {
				$start_date = date_i18n( $safe_date_format, $this->local_time + 60*$this->get_min_time() );
			}
		}

		$html = '';
		$html .= '<tr class="inline-edit-row inline-edit-row-post quick-edit-row-post">';
		if ( isset( $_POST["col_len"] ) )
			$html .= '<td colspan="'.$_POST["col_len"].'" class="colspanchange">';
		else
			$html .= '<td colspan="6" class="colspanchange">';

		$html .= '<fieldset class="inline-edit-col-left" style="width:33%">';
		$html .= '<div class="inline-edit-col">';
		$html .= '<h4>'.__('CLIENT', 'appointments').'</h4>';
		/* user */
		$html .= '<label>';
		$html .= '<span class="title">'.__('User', 'appointments'). '</span>';
		$html .= wp_dropdown_users( array( 'show_option_all'=>__('Not registered user','appointments'), 'show'=>'user_login', 'echo'=>0, 'selected' => $app->user, 'name'=>'user' ) );
		$html .= '</label>';
		/* Client name */
		$html .= '<label>';
		$html .= '<span class="title">'.$this->get_field_name('name'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="cname" class="ptitle" value="'.stripslashes( $app->name ).'" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client email */
		$html .= '<label>';
		$html .= '<span class="title">'.$this->get_field_name('email'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="email" class="ptitle" value="'.$app->email.'" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client Phone */
		$html .= '<label>';
		$html .= '<span class="title">'.$this->get_field_name('phone'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="phone" class="ptitle" value="'.stripslashes( $app->phone ).'" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client Address */
		$html .= '<label>';
		$html .= '<span class="title">'.$this->get_field_name('address'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="address" class="ptitle" value="'.stripslashes( $app->address ).'" />';
		$html .= '</span>';
		$html .= '</label>';
		/* Client City */
		$html .= '<label>';
		$html .= '<span class="title">'.$this->get_field_name('city'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="city" class="ptitle" value="'.stripslashes( $app->city ).'" />';
		$html .= '</span>';
		$html .= '</label>';
		$html .= '</div>';
		$html .= '</fieldset>';

		$html .= '<fieldset class="inline-edit-col-center" style="width:28%">';
		$html .= '<div class="inline-edit-col">';
		$html .= '<h4>'.__('SERVICE', 'appointments').'</h4>';
		/* Services */
		$services = $this->get_services();
		$html .= '<label>';
		$html .= '<span class="title">'.__('Name', 'appointments'). '</span>';
		$html .= '<select name="service">';
		if ( $services ) {
			foreach ( $services as $service ) {
				if ( $app->service == $service->ID )
					$sel = ' selected="selected"';
				else
					$sel = '';
				$html .= '<option value="'.$service->ID.'"'.$sel.'>'. stripslashes( $service->name ) . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</label>';
		/* Workers */
		$workers = $wpdb->get_results("SELECT * FROM " . $this->workers_table . " " );
		$html .= '<label>';
		$html .= '<span class="title">'.__('Provider', 'appointments'). '</span>';
		$html .= '<select name="worker">';
		// Always add an "Our staff" field
		$html .= '<option value="0">'. __('No specific provider', 'appointments') . '</option>';
		if ( $workers ) {
			foreach ( $workers as $worker ) {
				if ( $app->worker == $worker->ID ) {
					$sel = ' selected="selected"';
				}
				else
					$sel = '';
				$html .= '<option value="'.$worker->ID.'"'.$sel.'>'. $this->get_worker_name( $worker->ID, false ) . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</label>';
		/* Price */
		$html .= '<label>';
		$html .= '<span class="title">'.__('Price', 'appointments'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="text" name="price" style="width:50%" class="ptitle" value="'.$app->price.'" />';
		$html .= '</span>';
		$html .= '</label>';
		$html .= '</label>';
		$html .= apply_filters('app-appointments_list-edit-services', '', $app);
		$html .= '</div>';
		$html .= '</fieldset>';

		$html .= '<fieldset class="inline-edit-col-right" style="width:38%">';
		$html .= '<div class="inline-edit-col">';
		$html .= '<h4>'.__('APPOINTMENT', 'appointments').'</h4>';
		/* Created - Don't show for a new app */
		if ( $app_id ) {
			$html .= '<label>';
			$html .= '<span class="title">'.__('Created', 'appointments'). '</span>';
			$html .= '<span class="input-text-wrap" style="height:26px;padding-top:4px;">';
			$html .= date_i18n( $this->datetime_format, strtotime($app->created) );
			$html .= '</span>';
			$html .= '</label>';
		}
		/* Start */
		$html .= '<label style="float:left;width:65%">';
		$html .= '<span class="title">'.__('Start', 'appointments'). '</span>';
		$html .= '<span class="input-text-wrap" >';
		$html .= '<input type="text" name="date" class="datepicker" size="12" value="'.$start_date.'" />';
		$html .= '</label>';
		$html .= '<label style="float:left;width:30%; padding-left:5px;">';

		// Check if an admin min time (time base) is set. @since 1.0.2
		if ( isset( $this->options["admin_min_time"] ) && $this->options["admin_min_time"] )
			$min_time = $this->options["admin_min_time"];
		else
			$min_time = $this->get_min_time();

		$min_secs = 60 * apply_filters( 'app_admin_min_time', $min_time );
		$html .= '<select name="time" >';
		for ( $t=0; $t<3600*24; $t=$t+$min_secs ) {
			$dhours = $this->secs2hours( $t ); // Hours in 08:30 format
			if ( $dhours == $start_time )
				$s = " selected='selected'";
			else $s = '';

			$html .= '<option'.$s.'>';
			$html .= $dhours;
			$html .= '</option>';
		}
		$html .= '</select>';
		$html .= '</span>';
		$html .= '</label>';
		$html .= '<div style="clear:both; height:0"></div>';
		/* End - Don't show for a new app */
		if ( $app_id ) {
			$html .= '<label style="margin-top:8px">';
			$html .= '<span class="title">'.__('End', 'appointments'). '</span>';
			$html .= '<span class="input-text-wrap" style="height:26px;padding-top:4px;">';
			$html .= $end_datetime;
			$html .= '</span>';
			$html .= '</label>';
		}
		/* Note */
		$html .= '<label>';
		$html .= '<span class="title">'.$this->get_field_name('note'). '</span>';
		$html .= '<textarea cols="22" rows=1">';
		$html .= stripslashes( $app->note );
		$html .= '</textarea>';
		$html .= '</label>';
		/* Status */
		//$statuses = $this->get_statuses();
		$statuses = App_Template::get_status_names();
		$html .= '<label>';
		$html .= '<span class="title">'.__('Status', 'appointments'). '</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<select name="status">';
		if ( $statuses ) {
			foreach ( $statuses as $status => $status_name ) {
				if ( $app->status == $status )
					$sel = ' selected="selected"';
				else
					$sel = '';
				$html .= '<option value="'.$status.'"'.$sel.'>'. $status_name . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</span>';
		$html .= '</label>';
		/* Confirmation email */
		// Default is "checked" for a new appointment
		if ( $app_id ) {
			$c = '';
			$text = __('(Re)send confirmation email', 'appointments');
		}
		else {
			$c = ' checked="checked"';
			$text = __('Send confirmation email', 'appointments');
		}

		$html .= '<label>';
		$html .= '<span class="title">'.__('Confirm','appointments').'</span>';
		$html .= '<span class="input-text-wrap">';
		$html .= '<input type="checkbox" name="resend" value="1" '.$c.' />&nbsp;' .$text;
		$html .= '</span>';
		$html .= '</label>';

		$html .= '</div>';
		$html .= '</fieldset>';
		/* General fields required for save and cancel */
		$html .= '<p class="submit inline-edit-save">';
		$html .= '<a href="javascript:void(0)" title="'.__('Cancel', 'appointments').'" class="button-secondary cancel alignleft">'.__('Cancel','appointments').'</a>';
		if ( 'reserved' == $app->status ) {
			$js = 'style="display:none"';
			$title = __('GCal reserved appointments cannot be edited here. Edit them in your Google calendar.', 'appointments');
		}
		else {
			$js = 'href="javascript:void(0)"';
			$title = __('Click to save or update', 'appointments');
		}
		$html .= '<a '.$js.' title="'.$title.'" class="button-primary save alignright">'.__('Save / Update','appointments').'</a>';
		$html .= '<img class="waiting" style="display:none;" src="'.admin_url('images/wpspin_light.gif').'" alt="">';
		$html .= '<input type="hidden" name="app_id" value="'.$app->ID.'">';
		$html .= '<span class="error" style="display:none"></span>';
		$html .= '<br class="clear">';
		$html .= '</p>';

		$html .= '</td>';
		$html .= '</tr>';

		die( json_encode( array( 'result'=>$html)));

	}

	function inline_edit_save() {
		$app_id = $_POST["app_id"];
		global $wpdb, $current_user;
		$app = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE ID=".$app_id." " );

		$data = array();
		if ( $app != null )
			$data['ID'] = $app_id;
		else {
			$data['created']	= date("Y-m-d H:i:s", $this->local_time );
			$data['ID'] 		= 'NULL';
		}
		$data['user']		= $_POST['user'];
		$data['email']		= $_POST['email'];
		$data['name']		= $_POST['name'];
		$data['phone']		= $_POST['phone'];
		$data['address'] 	= $_POST['address'];
		$data['city']		= $_POST['city'];
		$data['service']	= $_POST['service'];
		$service			= $this->get_service( $_POST['service'] );
		$data['worker']		= $_POST['worker'];
		$data['price']		= $_POST['price'];
		// Clear comma from date format. It creates problems for php5.2
		$data['start']		= date( 'Y-m-d H:i:s', strtotime( str_replace( ',','', $this->to_us( $_POST['date'] ) ). " " . $this->to_military( $_POST['time'] ) ) );
		$data['end']		= date( 'Y-m-d H:i:s', strtotime( str_replace( ',','', $this->to_us( $_POST['date'] ) ). " " . $this->to_military( $_POST['time'] ) ) + $service->duration *60 );
		$data['note']		= $_POST['note'];
		$data['status']		= $_POST['status'];
		$resend				= $_POST["resend"];

		$data = apply_filters('app-appointment-inline_edit-save_data', $data);

		$update_result = $insert_result = false;
		if( $app != null ) {
			// Update
			$update_result = $wpdb->update( $this->app_table, $data, array('ID' => $app_id) );
			if ( $update_result ) {
				if ( ( 'pending' == $data['status'] || 'removed' == $data['status'] || 'completed' == $data['status'] ) && is_object( $this->gcal_api ) )
					$this->gcal_api->delete( $app_id );
				else if ( ( 'paid' == $data['status'] || 'confirmed' == $data['status'] ) && is_object( $this->gcal_api ) )
					$this->gcal_api->update( $app_id ); // This also checks for event insert
			}
			if ( $update_result && $resend ) {
				if ( 'removed' == $data['status'] )
					do_action( 'app_removed', $app_id );
				else
					$this->send_confirmation( $app_id );
			}
		}
		else {
			// Insert
			$insert_result = $wpdb->insert( $this->app_table, $data );
			if ( $insert_result && $resend )
				$this->send_confirmation( $wpdb->insert_id );
			if ( $insert_result && is_object( $this->gcal_api ) )
				$this->gcal_api->insert( $app_id );
		}

		do_action('app-appointment-inline_edit-after_save', ($update_result ? $app_id : $wpdb->insert_id), $data);

		if ( ( $update_result || $insert_result ) && $data['user'] ) {
			if ( $data['name'] )
				update_user_meta( $data['user'], 'app_name',  $data['name'] );
			if (  $data['email'] )
				update_user_meta( $data['user'], 'app_email', $data['email'] );
			if ( $data['phone'] )
				update_user_meta( $data['user'], 'app_phone', $data['phone'] );
			if ( $data['address'] )
				update_user_meta( $data['user'], 'app_address', $data['address'] );
			if ( $data['city'] )
				update_user_meta( $data['user'], 'app_city', $data['city'] );

			do_action( 'app_save_user_meta', $data['user'], $data );
		}

		if ( $update_result ) {
			// Log change of status
			if ( $data['status'] != $app->status ) {
				$this->log( $this->log( sprintf( __('Status changed from %s to %s by %s for appointment ID:%d','appointments'), $app->status, $data["status"], $current_user->user_login, $app->ID ) ) );
			}
			die( json_encode( array("result" => __('<span style="color:green;font-weight:bold">Changes saved.</span>', 'appointments') ) ) );
		}
		else if ( $insert_result )
			die( json_encode( array("result" => __('<span style="color:green;font-weight:bold">New appointment succesfully saved.</span>', 'appointments') ) ) );
		else
			die( json_encode( array("result" => __('<span style="color:red;font-weight:bold">Record could not be saved OR you did not make any changes!</span>', 'appointments') ) ) );
	}

	 // For future use
	function reports() {
	}

	/**
	 *	Get transaction records
	 *  Modified from Membership plugin by Barry
	 */
	function get_transactions($type, $startat, $num) {

		switch($type) {

			case 'past':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transaction_table} WHERE transaction_status NOT IN ('Pending', 'Future') ORDER BY transaction_ID DESC  LIMIT %d, %d", $startat, $num );
						break;
			case 'pending':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transaction_table} WHERE transaction_status IN ('Pending') ORDER BY transaction_ID DESC LIMIT %d, %d", $startat, $num );
						break;
			case 'future':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->transaction_table} WHERE transaction_status IN ('Future') ORDER BY transaction_ID DESC LIMIT %d, %d", $startat, $num );
						break;

		}

		return $this->db->get_results( $sql );

	}

	/**
	 *	Find if a Paypal transaction is duplicate or not
	 */
	function duplicate_transaction($app_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note,$content=0) {
		$sql = $this->db->prepare( "SELECT transaction_ID FROM {$this->transaction_table} WHERE transaction_app_ID = %d AND transaction_paypal_ID = %s AND transaction_stamp = %d LIMIT 1 ", $app_id, $paypal_ID, $timestamp );

		$trans = $this->db->get_var( $sql );
		if(!empty($trans)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 *	Save a Paypal transaction to the database
	 */
	function record_transaction($app_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note) {

		$data = array();
		$data['transaction_app_ID'] = $app_id;
		$data['transaction_paypal_ID'] = $paypal_ID;
		$data['transaction_stamp'] = $timestamp;
		$data['transaction_currency'] = $currency;
		$data['transaction_status'] = $status;
		$data['transaction_total_amount'] = (int) round($amount * 100);
		$data['transaction_note'] = $note;

		$existing_id = $this->db->get_var( $this->db->prepare( "SELECT transaction_ID FROM {$this->transaction_table} WHERE transaction_paypal_ID = %s LIMIT 1", $paypal_ID ) );

		if(!empty($existing_id)) {
			// Update
			$this->db->update( $this->transaction_table, $data, array('transaction_ID' => $existing_id) );
		} else {
			// Insert
			$this->db->insert( $this->transaction_table, $data );
		}

	}

	function get_total() {
		return $this->db->get_var( "SELECT FOUND_ROWS();" );
	}

	function transactions() {

		global $page, $action, $type;

		wp_reset_vars( array('type') );

		if(empty($type)) $type = 'past';

		?>
		<div class='wrap'>
			<div class="icon32" style="margin:8px 0 0 0"><img src="<?php echo $this->plugin_url . '/images/transactions.png'; ?>" /></div>
			<h2><?php echo __('Transactions','appointments'); ?></h2>

			<ul class="subsubsub">
				<li><a href="<?php echo add_query_arg('type', 'past'); ?>" class="rbutton <?php if($type == 'past') echo 'current'; ?>"><?php  _e('Recent transactions', 'appointments'); ?></a> | </li>
				<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending transactions', 'appointments'); ?></a> | </li>
				<li><a href="<?php echo add_query_arg('type', 'future'); ?>" class="rbutton <?php if($type == 'future') echo 'current'; ?>"><?php  _e('Future transactions', 'appointments'); ?></a></li>
			</ul>

			<?php
				$this->mytransactions($type);

			?>
		</div> <!-- wrap -->
		<?php

	}

	function mytransactions($type = 'past') {

		if(empty($_GET['paged'])) {
			$paged = 1;
		} else {
			$paged = ((int) $_GET['paged']);
		}

		$startat = ($paged - 1) * 50;

		$transactions = $this->get_transactions($type, $startat, 50);
		$total = $this->get_total();

		$columns = array();

		$columns['subscription'] = __('App ID','appointments');
		$columns['user'] = __('User','appointments');
		$columns['date'] = __('Date/Time','appointments');
		$columns['service'] = __('Service','appointments');
		$columns['amount'] = __('Amount','appointments');
		$columns['transid'] = __('Transaction id','appointments');
		$columns['status'] = __('Status','appointments');

		$trans_navigation = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'total' => ceil($total / 50),
			'current' => $paged
		));

		echo '<div class="tablenav">';
		if ( $trans_navigation ) echo "<div class='tablenav-pages'>$trans_navigation</div>";
		echo '</div>';
		?>

			<table cellspacing="0" class="widefat fixed">
				<thead>
				<tr>
				<?php
					foreach($columns as $key => $col) {
						?>
						<th class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</thead>

				<tfoot>
				<tr>
				<?php
					reset($columns);
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</tfoot>

				<tbody>
					<?php
					if($transactions) {
						foreach($transactions as $key => $transaction) {
							?>
							<tr valign="middle" class="alternate">
								<td class="column-subscription">
									<?php
										echo $transaction->transaction_app_ID;
									?>

								</td>
								<td class="column-user">
									<?php
										echo $this->get_client_name( $transaction->transaction_app_ID );
									?>
								</td>
								<td class="column-date">
									<?php
										echo date_i18n($this->datetime_format, $transaction->transaction_stamp);

									?>
								</td>
								<td class="column-service">
								<?php
								$service_id = $this->db->get_var( "SELECT service FROM " . $this->app_table . " WHERE ID=". $transaction->transaction_app_ID . " " );
								echo $this->get_service_name( $service_id );
								?>
								</td>
								<td class="column-amount">
									<?php
										$amount = $transaction->transaction_total_amount / 100;

										echo $transaction->transaction_currency;
										echo "&nbsp;" . number_format($amount, 2, '.', ',');
									?>
								</td>
								<td class="column-transid">
									<?php
										if(!empty($transaction->transaction_paypal_ID)) {
											echo $transaction->transaction_paypal_ID;
										} else {
											echo __('None yet','appointments');
										}
									?>
								</td>
								<td class="column-status">
									<?php
										if(!empty($transaction->transaction_status)) {
											echo $transaction->transaction_status;
										} else {
											echo __('None yet','appointments');
										}
									?>
								</td>
							</tr>
							<?php
						}
					} else {
						$columncount = count($columns);
						?>
						<tr valign="middle" class="alternate" >
							<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Transactions have been found, patience is a virtue.','appointments'); ?></td>
						</tr>
						<?php
					}
					?>

				</tbody>
			</table>
		<?php
	}


}
}

define('APP_PLUGIN_DIR', dirname(__FILE__), true);

if (file_exists(APP_PLUGIN_DIR . '/includes/wpmudev-dash-notification.php')) require_once APP_PLUGIN_DIR . '/includes/wpmudev-dash-notification.php';
require_once APP_PLUGIN_DIR . '/includes/default_filters.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_roles.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_codec.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_shortcodes.php';
require_once APP_PLUGIN_DIR . '/includes/class_app_addon_helper.php';

App_AddonHandler::serve();
App_Shortcodes::serve();

global $appointments;
$appointments = new Appointments();

if (is_admin()) {
	require_once APP_PLUGIN_DIR . '/includes/support/class_app_tutorial.php';
	App_Tutorial::serve();

	require_once APP_PLUGIN_DIR . '/includes/support/class_app_admin_help.php';
	App_AdminHelp::serve();
}

/**
 * Find blogs and uninstall tables for each of them
 * @since 1.0.2
 */
if ( !function_exists( 'wpmudev_appointments_uninstall' ) ) {
	function wpmudev_appointments_uninstall() {
		global $wpdb;

		if ( function_exists('is_multisite') && is_multisite() ) {
				$network = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : "";
				$activate = isset($_GET['action']) ? $_GET['action'] : "";
				$is_network = ($network=='/wp-admin/network/plugins.php') ? true:false;
				$is_activation = ($activate=='deactivate') ? false:true;

			if ($is_network && !$is_activation){
				$old_blog = $wpdb->blogid;
				// Get all blog ids
				$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				foreach ($blogids as $blog_id) {
					switch_to_blog($blog_id);
					_wpmudev_appointments_uninstall( );
				}
				switch_to_blog($old_blog);
				return;
			}
		}
		// If not multisite, just make an ordinary uninstall
		_wpmudev_appointments_uninstall( );
	}
}

if ( !function_exists( '_wpmudev_appointments_uninstall' ) ) {
	function _wpmudev_appointments_uninstall( ) {
		global $wpdb;

		delete_option( 'appointments_options' );
		delete_option( 'app_last_update' );
		delete_option( 'app_db_version' );

		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_working_hours" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_exceptions" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_services" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_workers" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_appointments" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_transactions" );
		$wpdb->query( "DROP TABLE " . $wpdb->prefix . "app_cache" );

		// Delete user metas
		$wpdb->query( "DELETE FROM " . $wpdb->usermeta . " WHERE meta_key='app_api_mode' OR meta_key='app_service_account'
			OR meta_key='app_key_file' OR meta_key='app_selected_calendar' OR meta_key='app_gcal_summary' OR meta_key='app_gcal_description' OR meta_key LIKE 'app_dismiss%' " );

		// Remove all possible folders with their contents
		$uploads = wp_upload_dir();
		if ( isset( $uploads["basedir"] ) )
			$uploads_dir 	= $uploads["basedir"] . "/";
		else
			$uploads_dir 	= WP_CONTENT_DIR . "/uploads/";

		wpmudev_appointments_rmdir( $uploads_dir . '__app/' );
		if ( defined( 'AUTH_KEY' ) )
			wpmudev_appointments_rmdir( $uploads_dir . md5( 'AUTH_KEY' ) . '/' );

	}

	// Recursively remove a folder
	function wpmudev_appointments_rmdir( $dir ) {
		foreach( glob($dir . '/*') as $file ) {
			if( is_dir( $file ) )
				@wpmudev_appointments_rmdir( $file );
			else
				@unlink( $file );
		}
		@rmdir( $dir );
	}
}

register_uninstall_hook(  __FILE__ , "wpmudev_appointments_uninstall" );
register_activation_hook( __FILE__, array('Appointments', 'install') );