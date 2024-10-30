<?php
/*
Plugin Name: Empty Cart Button for WooCommerce
Plugin URI: https://wordpress.org/plugins/empty-cart-button-for-woocommerce/
Description: "Empty cart" button for WooCommerce.
Version: 1.4.2
Author: ProWCPlugins
Author URI: https://prowcplugins.com
Text Domain: empty-cart-button-for-woocommerce
Domain Path: /langs
WC tested up to: 9.1.4
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly
define('ECB_FILE', __FILE__);
define('ECB_DIR', plugin_dir_path(ECB_FILE));
define('ECB_URL', plugins_url('/', ECB_FILE));
define('ECB_TEXTDOMAIN', 'empty-cart-button-for-woocommerce');

if (!class_exists('ProWC_Empty_Cart_Button')) :

	/**
	 * Main ProWC_Empty_Cart_Button Class
	 *
	 * @class   ProWC_Empty_Cart_Button
	 * @version 1.2.2
	 * @since   1.0.0
	 */
	final class ProWC_Empty_Cart_Button {

		/**
		 * Plugin version
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '1.4.2';

		/**
		 * @var   ProWC_Empty_Cart_Button The single instance of the class
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Main ProWC_Empty_Cart_Button Instance
		 *
		 * Ensures only one instance of ProWC_Empty_Cart_Button is loaded or can be loaded
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @return  ProWC_Empty_Cart_Button - Main instance
		 */
		public static function instance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * ProWC_Empty_Cart_Button Constructor
		 *
		 * @version 1.2.2
		 * @since   1.0.0
		 * @access  public
		 */
		function __construct() {

			// Set up localization
			load_plugin_textdomain('empty-cart-button-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/langs/');

			// Include required files
			$this->includes();

			// Admin
			if (is_admin()) {
				$this->admin();
			}
		}

		/**
		 * Include required core files used in admin and on the frontend
		 *
		 * @version 1.2.2
		 * @since   1.0.0
		 */
		public $core;
		public function includes() {
			// Core
			$this->core = require_once('includes/class-prowc-empty-cart-button-core.php');
		}

		/**
		 * Add Admin settings tab
		 *
		 * @version 1.2.2
		 * @since   1.2.2
		 */
		public $settings;
		public function admin() {
			// Action links
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'action_links'));
			// Settings
			add_filter('woocommerce_get_settings_pages', array($this, 'add_woocommerce_settings_tab'));
			require_once('includes/settings/class-prowc-empty-cart-button-settings-section.php');
			$this->settings = array();
			$this->settings['general'] = require_once('includes/settings/class-prowc-empty-cart-button-settings-general.php');
			// Version updated
			if (get_option('prowc_empty_cart_button_version', '') !== $this->version) {
				add_action('admin_init', array($this, 'version_updated'));
			}

			add_action('admin_enqueue_scripts', array($this, 'prowc_empty_cart_button_admin_style'));
			add_action('admin_init',  array($this,'prowc_empty_cart_button_notice_update'));
			add_action('admin_init',  array($this,'prowc_empty_cart_button_plugin_notice_remindlater'));
			add_action('admin_init',  array($this,'prowc_empty_cart_button_plugin_notice_review'));
			add_action('admin_notices', array($this,'prowc_empty_cart_button_admin_upgrade_notice'));
			add_action('admin_notices', array($this,'prowc_empty_cart_button_admin_review_notice'));
			add_action('plugins_loaded', array($this,'prowc_empty_cart_button_check_version'));
			register_activation_hook( __FILE__, array($this,'prowc_empty_cart_button_check_activation_hook'));
			add_action('before_woocommerce_init', array($this,'proWC_empty_cart_button_hpos_compatibility'));

			// Admin notice
			if (!class_exists('WooCommerce')) {
				add_action('admin_notices', array( $this, 'ecb_fail_load') );
				return;
			}
		}

		// Database options upgrade
		function prowc_empty_cart_button_check_version() {
			if ( version_compare( $this->version, '1.3.0', '<' ) ) {
				$cache_key = 'prowc_old_option_keys';
				$old_keys = wp_cache_get( $cache_key, 'prowc_ecb_cache_group' );
		
				if ( false === $old_keys ) {
					$all_options = wp_load_alloptions();
					$old_keys = array_filter( $all_options, function( $option_name ) {
						return strpos( $option_name, 'alg_wc_' ) === 0;
					}, ARRAY_FILTER_USE_KEY );
		
					wp_cache_set( $cache_key, $old_keys, 'prowc_ecb_cache_group' );
				}
		
				if ( is_array( $old_keys ) || is_object( $old_keys ) ) {
					foreach ( $old_keys as $option_name => $option_value ) {
						$new_key = str_replace( 'alg_wc_', 'prowc_', $option_name );
						$old_option_value = get_option( $option_name );
						update_option( $new_key, $old_option_value );
						delete_option( $option_name );
					}
				}
			}
		}

		/**
		 * Show action links on the plugin screen
		 *
		 * @version 1.2.1
		 * @since   1.0.0
		 * @param   mixed $links
		 * @return  array
		 */
		function action_links($links) {
			$custom_links = array();
			$custom_links[] = '<a href="' . admin_url('admin.php?page=wc-settings&tab=prowc_empty_cart_button') . '">' . __('Settings', 'empty-cart-button-for-woocommerce') . '</a>';
			if ('empty-cart-button-for-woocommerce.php' === basename(__FILE__)) {
				$custom_links[] = '<a target="_blank" href="https://prowcplugins.com/downloads/empty-cart-button-for-woocommerce/?utm_source=empty-cart-button-for-woocommerce&utm_medium=referral&utm_campaign=settings">' . __('Unlock All', 'empty-cart-button-for-woocommerce') . '</a>';
			}
			return array_merge($custom_links, $links);
		}

		/**
		 * Include Admin Style
		 *
		 * @version 1.2.1
		 * @since   1.0.0
		 */
		function prowc_empty_cart_button_admin_style() {
			wp_enqueue_style('prowc-empty-button-style', ECB_URL . '/includes/css/admin-style.css',array (), 1.1);
			wp_enqueue_script('prowc-empty-button-script', ECB_URL . '/includes/js/admin-script.js', array ( 'jquery' ), 1.1, true);
			
			//admin rating popup js
			wp_enqueue_script('prowc-empty-button-sweetalert-min', ECB_URL . '/includes/js/sweetalert.min.js', array ( 'jquery' ), 1.1, true);
			
		}

		/**
		 * Add Empty Cart Button settings tab to WooCommerce settings
		 *
		 * @version 1.2.2
		 * @since   1.0.0
		 */
		function add_woocommerce_settings_tab($settings) {
			$settings[] = require_once('includes/settings/class-prowc-settings-empty-cart-button.php');
			return $settings;
		}

		/**
		 * Update Plugin Database version
		 *
		 * @version 1.2.2
		 * @since   1.2.1
		 */
		function version_updated() {
			update_option('prowc_empty_cart_button_version', $this->version);
		}

		function prowc_empty_cart_button_check_activation_hook() {
			$get_activation_time = gmdate('Y-m-d', strtotime('+ 3 days'));
			add_option('prowc_empty_cart_button_activation_time', $get_activation_time ); 
		}

		function prowc_empty_cart_button_notice_update() {
			$remdate = gmdate('Y-m-d', strtotime('+ 7 days'));
			$rDater = get_option('prowc_empty_cart_button_plugin_notice_nopemaybelater');
			if(!get_option('prowc_empty_cart_button_plugin_notice_remindlater')){
				update_option('prowc_empty_cart_button_plugin_notice_remindlater',$remdate);
				update_option('prowc_empty_cart_button_plugin_reviewtrack', 0);
			}
			
			if($rDater && gmdate('Y-m-d') >= $rDater) {
				update_option('prowc_empty_cart_button_plugin_notice_remindlater',$remdate);
			}
		}

		/**
		 * Get the plugin url.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		function plugin_url() {
			return untrailingslashit(plugin_dir_url(__FILE__));
		}

		/**
		 * Get the plugin path.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		function plugin_path() {
			return untrailingslashit(plugin_dir_path(__FILE__));
		}

		/**
		 * Admin Notice for WooCommerce Install & Active.
		 *
		 * @version 1.2.9
		 * @since   1.2.9
		 * @return  string
		 */
		function prowc_ecb_installed() {

			$file_path = 'woocommerce/woocommerce.php';
			$installed_plugins = get_plugins();

			return isset($installed_plugins[$file_path]);
		}

		/**
		* Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
		*
		* @access public
		* @since  1.4.2
		* @static 1.4.2
		*
		* @return null
		*/

		function proWC_empty_cart_button_hpos_compatibility() {
			if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
					'custom_order_tables',
					__FILE__,
					true // true (compatible, default) or false (not compatible)
				);
			}
		}

		/**
		 * Admin Notice for WooCommerce Install & Active.
		 *
		 * @version 1.2.9
		 * @since   1.2.9
		 * @return  string
		 */
		function ecb_fail_load() {
			if(function_exists('WC')){
				return;
			}
		    $screen = get_current_screen();
		    if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
		        return;
		    }

		    $plugin = 'woocommerce/woocommerce.php';
		    if ($this->prowc_ecb_installed()) {
		        if (!current_user_can('activate_plugins')) {
		            return;
		        }
		        $activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);

		        $message = '<p><strong>' . esc_html__('Empty Cart Button for WooCommerce', 'empty-cart-button-for-woocommerce') . '</strong>' . esc_html__(' plugin is not working because you need to activate the WooCoomerce plugin.', 'empty-cart-button-for-woocommerce') . '</p>';
		        $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $activation_url, __('Activate Woocommerce Now', 'empty-cart-button-for-woocommerce')) . '</p>';
		    } else {
		        if (!current_user_can('install_plugins')) {
		            return;
		        }

		        $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');

		        $message = '<p><strong>' . esc_html__('Empty Cart Button for WooCommerce', 'empty-cart-button-for-woocommerce') . '</strong>' . esc_html__(' plugin is not working because you need to install the WooCoomerce plugin', 'empty-cart-button-for-woocommerce') . '</p>';
		        $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $install_url, __('Install WooCoomerce Now', 'empty-cart-button-for-woocommerce')) . '</p>';
		    }

		    echo  '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
		}

		/* Admin Notice for upgrade plan Start */
		function prowc_empty_cart_button_admin_upgrade_notice() {
			$rDate = get_option('prowc_empty_cart_button_plugin_notice_remindlater');
			if (gmdate('Y-m-d') >= $rDate && !get_option('prowc_empty_cart_button_plugin_notice_dismissed')) {
				?>
				<div class="notice is-dismissible prowc_empty_cart_button_prowc_notice">
					<div class="prowc_empty_cart_wrap">
						<div class="prowc_empty_cart_gravatar">
							<img alt="" src="<?php echo esc_url( ECB_URL . '/includes/img/prowc_logo.png' ); ?>">
						</div>
						<div class="prowc_empty_cart_authorname">
							<div class="notice_texts">
								<a href="<?php echo esc_url('https://prowcplugins.com/downloads/empty-cart-button-for-woocommerce/?utm_source=empty-cart-button-for-woocommerce&utm_medium=referral&utm_campaign=notification'); ?>" target="_blank"><?php esc_html_e('Upgrade Empty Cart Button for WooCommerce', 'empty-cart-button-for-woocommerce'); ?> </a> <?php esc_html_e('to get additional features, security, and support. ', 'empty-cart-button-for-woocommerce'); ?> <strong><?php esc_html_e('Get 20% OFF', 'empty-cart-button-for-woocommerce'); ?></strong><?php esc_html_e(' your upgrade, use coupon code', 'empty-cart-button-for-woocommerce'); ?> <strong><?php esc_html_e('WP20', 'empty-cart-button-for-woocommerce'); ?></strong>
							</div>
							<div class="prowc_empty_cart_desc">
								<div class="notice_button">
									<?php wp_nonce_field( 'prowc_remind_later_nonce', 'nonce' ); ?>
									<a class="prowc_empty_cart_button button-primary" href="<?php echo esc_url('https://prowcplugins.com/downloads/empty-cart-button-for-woocommerce/?utm_source=empty-cart-button-for-woocommerce&utm_medium=referral&utm_campaign=notification'); ?>" target="_blank"><?php echo esc_html__('Buy Now', 'empty-cart-button-for-woocommerce'); ?></a>
									<a href="?prowc-wc-ecb-plugin-remindlater"><?php esc_html_e('Remind me later', 'empty-cart-button-for-woocommerce'); ?></a>
									<a href="?prowc-wc-ecb-plugin-dismissed"><?php esc_html_e('Dismiss Notice', 'empty-cart-button-for-woocommerce'); ?></a>
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>
					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"></span>
					</button>
				</div>
		<?php }
		}
		function prowc_empty_cart_button_plugin_notice_remindlater() {
			$curDate = gmdate('Y-m-d', strtotime(' + 7 days'));
			$rlDate = gmdate('Y-m-d', strtotime(' + 15 days'));
			if ( isset( $_GET['prowc-wc-ecb-plugin-remindlater'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'prowc_remind_later_nonce' ) ) {
				update_option('prowc_empty_cart_button_plugin_notice_remindlater', $curDate);
				update_option('prowc_empty_cart_button_plugin_reviewtrack', 1);
				update_option('prowc_empty_cart_button_plugin_notice_nopemaybelater', $rlDate);
			}
			if (isset($_GET['prowc-wc-ecb-plugin-dismissed'])) {
				update_option('prowc_empty_cart_button_plugin_reviewtrack', 1);
				update_option('prowc_empty_cart_button_plugin_notice_nopemaybelater', $rlDate);
				update_option('prowc_empty_cart_button_plugin_notice_dismissed', 'true');
			}
			if(isset($_GET['prowc-wc-ecb-plugin-remindlater-rating'])){
				update_option('prowc_empty_cart_button_plugin_notice_remindlater_rating', $curDate);
			}
			if (isset($_GET['prowc-wc-ecb-plugin-dismissed-rating'])) {
				update_option('prowc_empty_cart_button_plugin_notice_dismissed_rating', 'true');
			}
		}
		/* Admin Notice for upgrade plan End */

		/* Admin Notice for Plugin Review Start */
		function prowc_empty_cart_button_admin_review_notice() {

			$plugin_data = get_plugin_data( __FILE__ );	
			$plugin_name = $plugin_data['Name'];
			$rDate = get_option('prowc_empty_cart_button_plugin_notice_remindlater_rating');
			$activationDate = get_option('prowc_empty_cart_button_activation_time');

			$rDater = get_option('prowc_empty_cart_button_plugin_notice_nopemaybelater');
			$prowctrack = get_option('prowc_empty_cart_button_plugin_reviewtrack');

			if (gmdate('Y-m-d') >= $activationDate && gmdate('Y-m-d') >= $rDate && !get_option('prowc_empty_cart_button_plugin_notice_dismissed_rating')) {
			?>
				<div class="notice notice-info  is-dismissible">
					<?php  
					// translators: %s is a placeholder for the plugin name
					printf( '<p>%s</p>', esc_html( __( 'How are you liking the %s?', 'empty-cart-button-for-woocommerce' ), $plugin_name ) ); ?>
					<div class="starts-main-div">
						<div class="stars ecb-star">
							<input type="radio" name="star" class="star-1" id="ecb-star-1" value="1" />
							<label class="star-1" for="ecb-star-1">1</label>
							<input type="radio" name="star" class="star-2" id="ecb-star-2" value="2" />
							<label class="star-2" for="ecb-star-2">2</label>
							<input type="radio" name="star" class="star-3" id="ecb-star-3" value="3" />
							<label class="star-3" for="ecb-star-3">3</label>
							<input type="radio" name="star" class="star-4" id="ecb-star-4" value="4" />
							<label class="star-4" for="ecb-star-4">4</label>
							<input type="radio" name="star" class="star-5" id="ecb-star-5" value="5" />
							<label class="star-5" for="ecb-star-5">5</label>
							<span></span>
						</div>
						<div class="notice_button">
							<a href="?prowc-wc-ecb-plugin-remindlater-rating" class="button-secondary" ><?php esc_html_e('Remind me later', 'empty-cart-button-for-woocommerce'); ?></a>
							<a href="?prowc-wc-ecb-plugin-dismissed-rating" class="button-secondary" ><?php esc_html_e('Dismiss Notice', 'empty-cart-button-for-woocommerce'); ?></a>
						</div>
					</div>
				</div>
			<?php
			}
		
			if ($rDater != "")
				if (gmdate('Y-m-d') >= $rDater && $prowctrack && !get_option('prowc_empty_cart_button_plugin_notice_alreadydid')) {
				?>
				<div class="notice is-dismissible prowc_empty_cart_button_prowc_notice">
					<div class="prowc_empty_cart_wrap">
						<div class="prowc_empty_cart_gravatar">
							<img alt="" src="<?php echo esc_url( ECB_URL . '/includes/img/prowc_logo.png'); ?>">
						</div>
						<div class="prowc_empty_cart_authorname">
							<div class="notice_texts">
								<strong><?php esc_html_e('Are you enjoying Empty Cart Button for WooCommerce?', 'empty-cart-button-for-woocommerce'); ?></strong>
							</div>
							<div class="prowc_empty_cart_desc">
								<div class="notice_button">
									<button class="prowc_empty_cart_button button-primary prowc_empty_cart_button_yes"><?php echo esc_html__('Yes!', 'empty-cart-button-for-woocommerce'); ?></button>
									<a class="prowc_empty_cart_button button action" href="?prowc-wc-ecb-plugin-alreadydid"><?php echo esc_html__('Not Really!', 'empty-cart-button-for-woocommerce'); ?></a>
								</div>
							</div>
						</div>
						<div class="clearfix"></div>
					</div>

					<button type="button" class="notice-dismiss">
						<span class="screen-reader-text"></span>
					</button>
					<div class="prowc_empty_cart_button_prowc_notice_review_yes">
						<div class="notice_texts">
							<?php esc_html_e('That\'s awesome! Could you please do me a BIG favor and give it 5-star rating on WordPress to help us spread the word and boost our motivation?' , 'empty-cart-button-for-woocommerce'); ?>
						</div>
						<div class="prowc_empty_cart_desc">
							<div class="notice_button">
								<?php wp_nonce_field( 'prowc_wc_ecb_plugin_nopemaybelater', 'nonce' ); ?>
								<a class="prowc_empty_cart_button button-primary" href="<?php echo esc_url('https://wordpress.org/support/plugin/empty-cart-button-for-woocommerce/reviews/?filter=5#new-post'); ?>" target="_blank"><?php echo esc_html__('Okay You Deserve It', 'empty-cart-button-for-woocommerce'); ?></a>
								<a class="prowc_empty_cart_button button action" href="?prowc-wc-ecb-plugin-nopemaybelater"><?php echo esc_html__('Nope Maybe later', 'empty-cart-button-for-woocommerce'); ?></a>
								<a class="prowcesc_html__mpty_cart_button button action" href="?prowc-wc-ecb-plugin-alreadydid"><?php echo esc_html__('I Already Did', 'empty-cart-button-for-woocommerce'); ?></a>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		<?php }

		function prowc_empty_cart_button_plugin_notice_review() {
			$curDate = gmdate('Y-m-d', strtotime(' + 7 Days'));
			if ( isset( $_GET['prowc-wc-ecb-plugin-nopemaybelater'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'prowc_wc_ecb_plugin_nopemaybelater' ) ) {
				update_option('prowc_empty_cart_button_plugin_notice_nopemaybelater', $curDate);
			}
			if (isset($_GET['prowc-wc-ecb-plugin-alreadydid'])) {
				update_option('prowc_empty_cart_button_plugin_notice_alreadydid', 'true');
			}
		}
		/* Admin Notice for Plugin Review End */
	}

endif;

if (!function_exists('prowc_empty_cart_free_activation')) {

	/**
	 * Add action on plugin activation
	 *
	 * @version 1.3.4
	 * @since   1.3.4
	 */
	function prowc_empty_cart_free_activation() {

		// Deactivate Empty Cart Button Pro for WooCommerce
		deactivate_plugins('empty-cart-button-pro-for-woocommerce/empty-cart-button-pro-for-woocommerce.php');
	}
}
register_activation_hook(__FILE__, 'prowc_empty_cart_free_activation');

if (!function_exists('prowc_empty_cart_button')) {
	/**
	 * Returns the main instance of ProWC_Empty_Cart_Button to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  ProWC_Empty_Cart_Button
	 */
	function prowc_empty_cart_button() {
		return ProWC_Empty_Cart_Button::instance();
	}
}

/* Admin Notice for upgrade plan End */

prowc_empty_cart_button();
