<?php
/**
 * Core Activation Class
 *
 * @category   Core
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Core;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED' ) ) :
	return false; // Don't load twice !
endif;


/**
 * This class registers the basic hooks to WordPress and triggers Markup Markdown main engine
 */
final class Activation {


	/**
	 * Add-on simple handler
	 *
	 * @var object
	 */
	public $addons;


	/**
	 * Absolute path to the plugin on the server
	 *
	 * @var string
	 */
	private $core_dir = '';


	/**
	 * Everything starts here
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED' ) ) :
			$this->initialize();
		endif;
	}


	/**
	 * Primary triggers
	 */
	private function initialize() {
		// Plugin Activation.
		register_activation_hook( MARKUP_MARKDOWN_FILE_URL, array( $this, 'plugin_activate' ) );
		// Plugin Upgrade.
		add_action( 'upgrader_process_complete', array( $this, 'plugin_patches' ), 10, 2 );
		// Translated Strings.
		add_filter( 'load_textdomain_mofile', array( $this, 'plugin_textdomain' ), 10, 2 );
		// Plugin Properties.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_custom_metas' ), 10, 2 );
		// Add options and allow setup from the admin and edit screen.
		define( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED', true );
		// Just in case.
		$this->prepare_cache();
		// Get the otions.
		$options_conf = markup_markdown()->conf_blog_prefix . 'conf.php';
		if ( markup_markdown()->exists( $options_conf ) ) :
			require_once $options_conf;
			if ( ! defined( 'MARKUP_MARKDOWN_VERSION' ) ) :
				$this->migrate_namespaces();
			endif;
		endif;
		$this->core_dir = markup_markdown()->plugin_dir . 'MarkupMarkdown/Core/';
		// Load the conf.
		$addons_conf = markup_markdown()->conf_blog_prefix . 'conf_screen.php';
		if ( markup_markdown()->exists( $addons_conf ) ) :
			// If not present, wait for the addons to be loaded !
			require_once $addons_conf;
		endif;
		// Load the Support core modules.
		require_once $this->core_dir . 'class-support.php';
		require_once $this->core_dir . 'class-addons.php';
		require_once $this->core_dir . 'class-autoplugs.php';
		new \MarkupMarkdown\Core\Support();
		$this->addons = new \MarkupMarkdown\Core\Addons();
		new \MarkupMarkdown\Core\AutoPlugs();
		do_action( 'markup_markdown_addons_loaded' );
		define( 'MARKUP_MARKDOWN_ADDONS_LOADED', true );
		add_action( 'plugins_loaded', array( $this, 'enable_settings' ), 10 );
	}


	/**
	 * Load the local mo files inside the plugin folder
	 *
	 * @since  3.4.2
	 *
	 * @param string $mofile The file containing the translation string.
	 * @param string $domain The plugin or asset related domain.
	 * @return string $mofile The language specific translation file
	 */
	public function plugin_textdomain( $mofile, $domain ) {
		if ( 'markup-markdown' === $domain ) :
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$locale     = apply_filters( 'plugin_locale', determine_locale(), $domain );
			$new_mofile = markup_markdown()->plugin_dir . 'languages/' . $domain . '-' . $locale . '.mo';
			if ( $new_mofile !== $mofile && markup_markdown()->exists( $new_mofile ) ) :
				return $new_mofile;
			endif;
		endif;
		return $mofile;
	}


	/**
	 * Meta links of the plugin
	 *
	 * @since  2.0.0
	 *
	 * @param   array  $input Existing links.
	 * @param   string $file  Current page.
	 * @return  array  $data  Modified links.
	 */
	public function plugin_custom_metas( $input, $file ) {
		if ( 'markup-markdown/markup-markdown.php' !== $file ) :
			return $input;
		endif;
		return array_merge(
			$input,
			array(
				'<a href="https://ko-fi.com/peterpower594" target="_blank" rel="noopener noreferrer">♥ ' . esc_html__( 'Buy me a coffee', 'markup-markdown' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/markup-markdown/" target="_blank" rel="noopener noreferrer">♣ ' . esc_html__( 'Support', 'markup-markdown' ) . '</a>',
				'<a href="https://wordpress.org/support/plugin/markup-markdown/reviews/#new-post" target="_blank" rel="noopener noreferrer">★ ' . esc_html__( 'Rate this plugin »', 'markup-markdown' ) . '</a>',
			)
		);
	}


	/**
	 * Quick method to trigger the creation of static cache folders if missing
	 *
	 * @return boolean TRUE if the configuration file was properly generated
	 */
	private function prepare_cache() {
		$mmd_folders = array( markup_markdown()->conf_dir, markup_markdown()->cache_dir );
		foreach ( $mmd_folders as $my_folder ) :
			if ( markup_markdown()->exists( $my_folder ) ) :
				continue;
			endif;
			markup_markdown()->mkdir( $my_folder );
			if ( ! markup_markdown()->exists( $my_folder . '/index.php' ) ) :
				markup_markdown()->touch( $my_folder . '/index.php' );
				markup_markdown()->put_contents( $my_folder . '/index.php', "<?php\n// Silence is gold" );
			endif;
		endforeach;
		return $this->make_default_conf( get_current_network_id(), get_current_blog_id() );
	}


	/**
	 * Migrate the settings file for network websites
	 *
	 * @since  3.5.0
	 *
	 * @param   string $ver  The current plugin version.
	 * @return  boolean true if data were migrated or false
	 */
	private function migrate_conf( $ver = '3.5.1' ) {
		if ( version_compare( $ver, '3.5.1', '>=' ) || ! is_dir( markup_markdown()->conf_dir ) ) :
			return false;
		endif;
		$conf_files  = array( 'conf.php', 'conf_screen.php', 'conf_easymde_toolbar.json' );
		$file_prefix = '1_1_';
		foreach ( $conf_files as $my_conf_file ) :
			if ( markup_markdown()->exists( markup_markdown()->cache_dir . '/' . $my_conf_file ) ) :
				markup_markdown()->move( markup_markdown()->cache_dir . '/' . $my_conf_file, markup_markdown()->conf_dir . '/' . $file_prefix . $my_conf_file );
			endif;
		endforeach;
		return true;
	}


	/**
	 * Migrate the namespace file for network websites
	 *
	 * @since 3.26
	 *
	 * @return void
	 */
	private function migrate_namespaces() {
		$base_dir   = markup_markdown()->conf_blog_prefix;
		$conf_files = array(
			$base_dir . 'conf.php',
			$base_dir . 'conf_screen.php',
			$base_dir . 'plugs.php',
		);
		$i          = count( $conf_files ) - 1;
		while ( $i >= 0 ) :
			$raw_conf = markup_markdown()->get_contents( $conf_files[ $i ] );
			if ( ! $raw_conf ) :
				--$i;
				continue;
			endif;
			$raw_conf = str_replace( array( 'MMD', '?>' ), array( 'MARKUP_MARKDOWN', '' ), $raw_conf );
			if ( ! $i && ! preg_match( '#MARKUP_MARKDOWN_VERSION#', $raw_conf ) ) :
				$raw_conf .= "\n\tdefine( 'MARKUP_MARKDOWN_VERSION', \"" . markup_markdown()->version . '" );';
			endif;
			markup_markdown()->put_contents( $conf_files[ $i ], $raw_conf );
			--$i;
		endwhile;
	}


	/**
	 * Create default configuration file
	 *
	 * @access private
	 * @since 3.5.0
	 *
	 * @param integer $curr_network_id The network ID when multisite is enabled. WordPress default is 1.
	 * @param integer $curr_blog_id The blog Id when multisite is enabled. WordPress default is 1.
	 * @return boolean true in case of success or false is an error occured
	 */
	private function make_default_conf( $curr_network_id = 1, $curr_blog_id = 1 ) {
		$conf_file = markup_markdown()->conf_dir . '/' . $curr_network_id . '_' . $curr_blog_id . '_conf.php';
		if ( markup_markdown()->exists( $conf_file ) ) :
			return true;
		endif;
		if ( 1 === $curr_network_id && 1 === $curr_blog_id && $this->migrate_conf( markup_markdown()->ver ) ) :
			return true;
		endif;
		markup_markdown()->touch( $conf_file );
		$params     = markup_markdown()->default_conf;
		$php_code   = array( '<?php' );
		$php_code[] = "\n\tdefined( 'ABSPATH' ) || exit;";
		$php_code[] = "\n\tdefine( 'MARKUP_MARKDOWN_VERSION', \"" . markup_markdown()->version . '" );';
		foreach ( $params as $const => $val ) :
			$php_code[] = "\n\tdefine( '" . $const . "', " . ( is_integer( $val ) ? $val : (int) $val ) . ' );';
		endforeach;
		$php_code[] = "\n?>";
		return markup_markdown()->put_contents( $conf_file, implode( '', $php_code ) ) > 0 ? true : false;
	}


	/**
	 * Proxy to trigger actions before activating the plugin
	 *
	 * @return void
	 */
	public function plugin_activate() {
		$this->prepare_cache();
		$this->whitelist_four();
	}


	/**
	 * Proxy to trigger actions when upgrading the plugin
	 *
	 * @param object $upgrader_object Current component being upgraded.
	 * @param array  $options Current options used while performing the upgrade.
	 * @return void
	 */
	public function plugin_patches( $upgrader_object, $options ) {
		if ( 'update' !== $options['action'] || 'plugin' !== $options['type'] ) :
			return;
		endif;
		if ( isset( $options['plugins'] ) && is_array( $options['plugins'] ) ) :
			foreach ( $options['plugins'] as $my_plugin ) :
				if ( 'markup-markdown/markup-markdown.php' === $my_plugin ) :
					$this->prepare_cache();
				endif;
			endforeach;
		endif;
	}


	/**
	 * White-list version 4 for fresh install to disable backward compatibility with old prefixes
	 * Save the current version on the database
	 *
	 * @return void
	 */
	public function whitelist_four() {
		$current_version = get_option( 'markup_markdown_version', null );
		if ( is_null( $current_version ) ) :
			add_option( 'markup_markdown_version', markup_markdown()->version );
			markup_markdown()->touch( markup_markdown()->conf_dir . '/compat_mod_off.txt' );
		else :
			update_option( 'markup_markdown_version', markup_markdown()->version );
		endif;
	}


	/**
	 * Enable global settings before loading components
	 *
	 * @return void
	 */
	public function enable_settings() {
		require_once $this->core_dir . 'class-settings.php';
		new \MarkupMarkdown\Core\Settings( $this->addons );
		$this->addons = null;
	}
}
