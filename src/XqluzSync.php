<?php
/**
 * Main Plugin class
 *
 * @package XqluzSync
 */

namespace XqluzSync;

defined( 'ABSPATH' ) || exit;

/**
 * Final XqluzSync class.
 *
 * @package XqluzSync
 */
final class XqluzSync {

	/**
	 * Version
	 */
	public $version = '1.0.0';

	/**
	 * Single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Class Instance
	 *
	 * @return class instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		$this->defineConstants();
		// $this->includes();
		$this->init_hooks();

		$this->admin_settings = new XqluzSyncAdmin();
		$this->public         = new XqluzSyncPublic();
	}

	/**
	 * Fires during plugin activation
	 *
	 * @return void
	 */
	public function activate() {

	}

	/**
	 * Fires during plugin deactivation
	 *
	 * @return void
	 */
	public function deactivate() {

	}

	/**
	 * Init plugin hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		register_activation_hook( XQLUZ_SYNC_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( XQLUZ_SYNC_PLUGIN_FILE, array( $this, 'deactivate' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
     * Init Wheel_Of_Life when WordPress initializes.
     *
     * @since 1.0.0
     * @access public
     */
    public function init() {
        // Before init action.
		do_action( 'before_xqluzsync_init' );

        // Set up localization.
        $this->loadPluginTextdomain();
    }

	/**
	 * Define constants
	 *
	 * @return void
	 */
	public function defineConstants() {
		$this->define( 'XQLUZ_SYNC_PLUGIN_NAME', 'xqluzsync' );
		$this->define( 'XQLUZ_SYNC_ABSPATH', dirname( XQLUZ_SYNC_PLUGIN_FILE ) . '/' );
		$this->define( 'XQLUZ_SYNC_VERSION', $this->version );
		$this->define( 'XQLUZ_SYNC_TABLET_BREAKPOINT', '1024' );
		$this->define( 'XQLUZ_SYNC_MOBILE_BREAKPOINT', '767' );
	}

	/**
	 * Plugin includes
	 *
	 * @return void
	 */
	public function includes() {
		// require plugin_dir_path( XQLUZ_SYNC_PLUGIN_FILE ) . 'src/helpers/class-xqluzsync-helpers.php';
		// require plugin_dir_path( XQLUZ_SYNC_PLUGIN_FILE ) . 'includes/helpers/class-xqluzsync-block-helpers.php';
		// require plugin_dir_path( XQLUZ_SYNC_PLUGIN_FILE ) . 'includes/classes/class-xqluzsync-fonts-manager.php';
		require plugin_dir_path( XQLUZ_SYNC_PLUGIN_FILE ) . 'src/helpers/xqluzsync-helpers.php';
		require_once plugin_dir_path( XQLUZ_SYNC_PLUGIN_FILE ) . 'src/blocks/class-blocks.php';

	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name       Constant name.
	 * @param string|bool $value      Constant value.
	 * @return void
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 *
	 * Note: the first-loaded translation file overrides any following ones -
	 * - if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/xqluzsync/xqluzsync-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/xqluzsync-LOCALE.mo
	 */
	public function loadPluginTextdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'xqluzsync' );

		unload_textdomain( 'xqluzsync' );
		load_textdomain( 'xqluzsync', WP_LANG_DIR . '/xqluzsync/xqluzsync-' . $locale . '.mo' );
		load_plugin_textdomain(
			'xqluzsync',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

}
