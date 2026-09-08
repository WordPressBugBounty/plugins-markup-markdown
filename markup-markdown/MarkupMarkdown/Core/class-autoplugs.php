<?php
/**
 * AutoPlugs Class
 *
 * @category   Core
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Core;

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED' ) ) :
	return false; // The plugin was not properly activated.
endif;


/**
 * This class handles the loading of a few bridges to plug correctly the mardowm features with a few existing plugins
 */
final class AutoPlugs {


	/**
	 * List of available plugs with properties
	 *
	 * @var array
	 */
	private $plugs = array(
		'BBPress'            => array( 0, 'https://wordpress.org/plugins/bbpress/', 'bbPress' ),
		'BuddyPress'         => array( 0, 'https://wordpress.org/plugins/buddypress/', 'BuddyPress' ),
		'BuddyPressDocs'     => array( 0, 'https://wordpress.org/plugins/buddypress-docs/', 'BuddyPress Docs' ),
		'CodeSnippets'       => array( 0, 'https://wordpress.org/plugins/code-snippets/', 'Code Snippets' ),
		'DisableEmojis'      => array( 1, 'https://wordpress.org/plugins/disable-emojis/', 'Disable Emojis (GDPR friendly)' ),
		'FrontendAdmin'      => array( 0, 'https://wordpress.org/plugins/acf-frontend-form-element/', 'Frontend Admin by DynamiApps' ),
		'O2'                 => array( 0, 'https://github.com/Automattic/o2', 'o2' ),
		'QTranslateXT'       => array( 0, 'https://github.com/qtranslate/qtranslate-xt', 'qTranslate-XT' ),
		'Woocommerce'        => array( 0, 'https://wordpress.org/plugins/woocommerce/', 'WooCommerce' ),
		'WPCodeBlocks'       => array( 0, 'https://wordpress.org/plugins/wp-codemirror-block/', 'CodeMirror Blocks' ),
		'WPGeshi'            => array( 0, 'https://plugins.svn.wordpress.org/wp-geshi-highlight/trunk/', 'WP-GeSHi-Highlight (Legacy)' ),
	);


	/**
	 * Location where the plugs are stored
	 *
	 * @var string
	 */
	private $autoplug_dir = '';


	/**
	 * Beginning of the plugs
	 */
	public function __construct() {
		add_filter( 'markup_markdown_autoplugs_enabled', array( $this, 'should_load_plugs' ), 10, 1 );
		if ( is_admin() ) :
			add_filter( 'markup_markdown_verified_config', array( $this, 'update_config' ) );
			add_filter( 'markup_markdown_var2const', array( $this, 'create_const' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'prepare_autoplugs_tab' ) );
		endif;
		$this->load_autoplugs( apply_filters( 'markup_markdown_autoplugs_enabled', true ) );
	}


	/**
	 * Quick helper to return a plug instance
	 *
	 * @param string $name The slug of the plug.
	 * @return mixed<object|null> The plugin object or null if not found
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->prop ) ) {
			return $this->prop[ $name ];
		}
		return null;
	}


	/**
	 * Default filter to allow or deny the plugs
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @param boolean $boolean TRUE in case the plugs are allowed or FALSE.
	 *
	 * @return boolean TRUE if required or FALSE
	 */
	public function should_load_plugs( $boolean ) {
		if ( ! defined( 'WP_MARKUP_MARKDOWN_PLUGS' ) ) :
			return $boolean;
		endif;
		return (int) WP_MARKUP_MARKDOWN_PLUGS > 0 ? true : false;
	}


	/**
	 * Filter to parse the autoplugs options from the options screen when the form was submitted
	 *
	 * @since 3.10.0
	 * @access public
	 *
	 * @param array $my_cnf Configuration object.
	 * @return array $my_cnf The modified object
	 */
	public function update_config( $my_cnf ) {
		$fm_plugs = filter_input( INPUT_POST, 'mmd_plugs', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! isset( $fm_plugs ) || ! is_array( $fm_plugs ) ) :
			$fm_plugs = array();
		endif;
		$my_plugs = array();
		foreach ( $this->plugs as $my_slug => $my_plug ) :
			$my_plugs[ $my_slug ] = in_array( $my_slug, $fm_plugs, true ) ? 1 : 0;
		endforeach;
		$my_cnf['plugs'] = $my_plugs;
		return $my_cnf;
	}


	/**
	 * Trigger the generation of constants from variables
	 *
	 * @param array $my_cnf Configuration object.
	 * @return array $my_cnf The modified object
	 */
	public function create_const( $my_cnf ) {
		if ( isset( $my_cnf['plugs'] ) ) :
			$this->sanitize_save_conf( $my_cnf['plugs'] );
			unset( $my_cnf['plugs'] );
		endif;
		return $my_cnf;
	}


	/**
	 * Properly generate and sanitize the constants
	 *
	 * @param array $my_cnf Configuration object.
	 * @return string $cnf_file The target modified static file
	 */
	public function sanitize_save_conf( $my_cnf = array() ) {
		$cnf_file = markup_markdown()->conf_blog_prefix . 'plugs.php';
		$data     = "<?php\n\tdefined( 'ABSPATH' ) || exit;";
		$data    .= "\n\tdefine( \"MARKUP_MARKDOWN_AUTOPLUGS\", ";
		$safe_cnf = wp_json_encode( $my_cnf );
		if ( ! $safe_cnf ) :
			$data .= '[]';
		else :
			$data .= str_replace( array( '{', '}', ':' ), array( '[', ']', '=>' ), $safe_cnf );
		endif;
		$data .= ");\n?>";
		markup_markdown()->put_contents( $cnf_file, $data );
		markup_markdown()->clear_cache( $cnf_file );
		return $cnf_file;
	}


	/**
	 * Add a few "plugs" with existing WP Plugins to make a smooth connection
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @param boolean $auto TRUE to load automatically the plugs or FALSE.
	 *
	 * @return boolean TRUE in case series of plugs should be loaded or FALSE
	 */
	public function load_autoplugs( $auto = true ) {
		if ( ! $auto ) :
			return false;
		endif;
		$this->autoplug_dir = markup_markdown()->plugin_dir . '/MarkupMarkdown/AutoPlugs/';
		$conf_file          = $this->check_plugs();
		require_once $conf_file;
		if ( ! defined( 'MARKUP_MARKDOWN_AUTOPLUGS' ) ) :
			return false;
		endif;
		foreach ( MARKUP_MARKDOWN_AUTOPLUGS as $slug => $active ) :
			if ( ! $active ) :
				continue;
			endif;
			$curr_plug = $this->autoplug_dir . 'class-' . strtolower( $slug ) . '.php';
			if ( markup_markdown()->exists( $curr_plug ) ) :
				require_once $curr_plug;
				$plug_class = '\\MarkupMarkdown\\AutoPlugs\\' . $slug;
				if ( class_exists( $plug_class, false ) ) :
					new $plug_class();
				endif;
			endif;
		endforeach;
		return true;
	}


	/**
	 * Check existing plugins for required plugs
	 *
	 * @access private
	 * @since 3.10.0
	 *
	 * @return string the autoplugs settings file
	 */
	private function check_plugs() {
		$plugs_conf_file = markup_markdown()->conf_blog_prefix . 'plugs.php';
		if ( markup_markdown()->exists( $plugs_conf_file ) ) :
			return $plugs_conf_file;
		endif;
		$my_plug_cnf = array();
		foreach ( $this->plugs as $plug_slug => $plug_setting ) :
			$curr_plug = $this->autoplug_dir . $plug_slug . '.php';
			if ( ! markup_markdown()->exists( $curr_plug ) ) :
				$my_plug_cnf[ $plug_slug ] = 0;
				continue;
			endif;
			require_once $curr_plug;
			$my_const = 'MARKUP_MARKDOWN_' . strtoupper( $plug_slug ) . '_PLUG';
			if ( defined( $my_const ) ) :
				$my_plug_cnf[ $plug_slug ] = 1;
			else :
				$my_plug_cnf[ $plug_slug ] = isset( $plug_setting[0] ) && (int) $plug_setting[0] > 0 ? 1 : 0;
			endif;
		endforeach;
		return $this->sanitize_save_conf( $my_plug_cnf );
	}


	/**
	 * Trigger the actions to update the tabs on the settings screen
	 *
	 * @since 3.10.0
	 * @access public
	 *
	 * @param string $hook The WordPress hook being triggered.
	 * @return void
	 */
	public function prepare_autoplugs_tab( $hook ) {
		if ( 'settings_page_markup-markdown-admin' === $hook ) :
			add_action( 'markup_markdown_tabmenu_options', array( $this, 'add_tabmenu' ), 99, 1 );
			add_action( 'markup_markdown_tabcontent_options', array( $this, 'add_tabcontent' ), 99, 1 );
		endif;
	}


	/**
	 * Add the autoplugs menu item inside the options screen
	 *
	 * @since 3.10.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabmenu() {
		echo "\t\t\t\t\t\t<li><a href=\"#tab-plugs\" class=\"mmd-ico ico-plug\">" . esc_html__( 'Autoplugs', 'markup-markdown' ) . "</a></li>\n";
	}


	/**
	 * Display autoplugs options inside the options screen
	 *
	 * @since 3.10.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabcontent() {
		$conf_file = markup_markdown()->conf_blog_prefix . 'plugs.php';
		if ( markup_markdown()->exists( $conf_file ) ) :
			require_once $conf_file;
		endif;
		$my_tmpl = markup_markdown()->plugin_dir . '/MarkupMarkdown/AutoPlugs/Templates/tmpl-plugsform.php';
		if ( markup_markdown()->exists( $my_tmpl ) ) :
			$default_plugs = $this->plugs;
			markup_markdown()->clear_cache( $my_tmpl );
			include $my_tmpl;
		endif;
	}
}
