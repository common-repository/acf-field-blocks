<?php
/**
 * Plugin Name:       Blocks for ACF Fields
 * Plugin URI:        https://www.acffieldblocks.com
 * Description:       Seamlessly integrates Advanced Custom Fields into the WordPress block editor, display custom fields effortlessly without any coding.
 * Requires at least: 6.3
 * Tested up to:      6.6
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            gamaup
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       acf-field-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ACF_Field_Blocks' ) ) {

	/**
	 * Main Class
	 */
	class ACF_Field_Blocks {

		/**
		 * Class instance
		 * 
		 * @var ACF_Field_Blocks
		 */
		private static $instance;

		/**
		 * Initiator
		 * 
		 * @return ACF_Field_Blocks()
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
				do_action( 'acf_field_blocks_loaded' );
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			if ( ! version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
				add_action( 'admin_notices', [ $this, 'fail_wp_version' ] );
				return;
			}

			if ( ! class_exists( 'ACF' ) || ! version_compare( acf()->version, '6.1.0', '>=' )  ) {
				add_action( 'admin_notices', [ $this, 'fail_acf_required' ] );
				return;
			}

			$this->define_constants();
			$this->autoload();
		}

		/**
		 * Define all constants
		 */
		public function define_constants() {
			define( 'ACF_FIELD_BLOCKS_VERSION', '1.0.0' );
			define( 'ACF_FIELD_BLOCKS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			define( 'ACF_FIELD_BLOCKS_URL', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) );
		}

		/**
		 * Load autoloader and register the namespace
		 */
		public function autoload() {
			require_once ACF_FIELD_BLOCKS_PATH . '/autoloader.php';
			$autoloader = new \ACFFieldBlocks\Autoloader();
			$autoloader->add_namespace( '\ACFFieldBlocks', ACF_FIELD_BLOCKS_PATH . '/inc/' );
			$autoloader->add_namespace( '\ACFFieldBlocks\Pro', ACF_FIELD_BLOCKS_PATH . '/pro/' );
			$autoloader->register();

			\ACFFieldBlocks\Blocks::instance();
			\ACFFieldBlocks\Rest::instance();
			if ( is_dir( ACF_FIELD_BLOCKS_PATH . '/pro/' ) ) {
				\ACFFieldBlocks\Pro\Blocks::instance();
			}
		}

		/**
		 * Warn user when the site doesn't have the minimum required WordPress version.
		 */
		public function fail_wp_version() {
			/* translators: %s: WordPress version */
			$message      = sprintf( esc_html__( 'Blocks for ACF Fields requires WordPress version %s+. Because you are using an earlier version, the plugin is currently not running.', 'acf-field-blocks' ), '6.3' );
			$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}

		/**
		 * Warn user when the site doesn't have the minimum required WordPress version.
		 */
		public function fail_acf_required() {
			/* translators: %s: WordPress version */
			$message      = sprintf( esc_html__( 'Blocks for ACF Fields requires the Advanced Custom Fields plugin version %s+ to be activated. The plugin is currently not running.', 'acf-field-blocks' ), '6.1.0' );
			$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}

	}
}

add_action( 'plugins_loaded', function() {
	ACF_Field_Blocks::get_instance();
} );

function acf_field_blocks() {
	return ACF_Field_Blocks::get_instance();
}
