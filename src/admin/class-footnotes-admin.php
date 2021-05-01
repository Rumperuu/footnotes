<?php
/**
 * Admin: Footnotes_Admin class
 *
 * The Admin. subpackage is initialised at runtime by the {@see Footnotes_Admin}
 * class, which draws in the {@see Footnotes_WYSIWYG} class for WYSIWYG editor
 * integration and the {@see footnotes\admin_layout} subpackage for rendering
 * dashboard pages.
 *
 * @package  footnotes\admin
 * @since  2.8.0
 */

/**
 * Class provide all admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and enqueues all admin-specific stylesheets
 * and JavaScript.
 *
 * @package  footnotes\admin
 * @since  2.8.0
 */
class Footnotes_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @access  private
	 * @since  2.8.0
	 * @see  Footnotes::$plugin_name
	 * @var  string  $plugin_name  The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access  private
	 * @since  2.8.0
	 * @see  Footnotes::$version
	 * @var  string  $version  The current version of this plugin.
	 */
	private $version;

	/**
	 * The WYSIWYG editor integration object.
	 *
	 * @since  2.8.0
	 * @var  Footnotes_WYSIWYG  $wysiwyg  The WYSIWYG editor integration object.
	 */
	public $wysiwyg;

	/**
	 * Initialize the class and set its properties.

	 * @param  string $plugin_name  The name of this plugin.
	 * @param  string $version  The version of this plugin.
	 *
	 * @since  2.8.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();

	}

	/**
	 * Load the required admin-specific dependencies.
	 *
	 * Includes the following files that provide the admin-specific functionality
	 * of this plugin:
	 *
	 * - {@see Footnotes_WYSIWYG}: Provides plugin integration with the WYSIWYG editor.
	 * - {@see Footnotes_Layout_Settings}: Defines the plugin dashboard page(s).
	 *
	 * @access  private
	 *
	 * @since  2.8.0
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for WYSIWYG editor integration.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-footnotes-wysiwyg.php';

		$this->wysiwyg = new Footnotes_WYSIWYG( $this->plugin_name );

		/**
		 * The class responsible for constructing the plugin dashboard page(s).
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/layout/class-footnotes-layout-init.php';

		new Footnotes_Layout_Init( $this->plugin_name );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since  2.8.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/settings' . ( ( PRODUCTION_ENV ) ? '.min' : '' ) . '.css',
			array(),
			( PRODUCTION_ENV ) ? $this->version : filemtime(
				plugin_dir_path(
					dirname( __FILE__ )
				) . 'css/settings.css'
			),
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since  2.8.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/wysiwyg-editor' . ( ( PRODUCTION_ENV ) ? '.min' : '' ) . '.js',
			array(),
			( PRODUCTION_ENV ) ? $this->version : filemtime(
				plugin_dir_path(
					dirname( __FILE__ )
				) . 'js/wysiwyg-editor.js'
			),
			false
		);

	}

	/**
	 * Appends the Plugin links for display in the dashboard Plugins page.
	 *
	 * @param  string[] $links  The default set of links to display.
	 * @return  string[]  The full set of links to display.
	 *
	 * @since  1.5.0
	 * @since  2.8.0  Moved from `Footnotes_Hooks` class to `Footnotes_Admin`.
	 */
	public function footnotes_action_links( array $links ): array {
		// Append link to the WordPress Plugin page.
		$links[] = sprintf( '<a href="https://wordpress.org/support/plugin/footnotes" target="_blank">%s</a>', __( 'Support', 'footnotes' ) );
		// Append link to the settings page.
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=footnotes' ) ), __( 'Settings', 'footnotes' ) );
		// Append link to the PayPal donate function.
		$links[] = sprintf( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6Z6CZDW8PPBBJ" target="_blank">%s</a>', __( 'Donate', 'footnotes' ) );

		return $links;
	}

}

