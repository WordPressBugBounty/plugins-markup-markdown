<?php
/**
 * Stable addon "Debug" to display debug information on the backend
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      3.16.0
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;

/**
 * Simple class to add options on the edit screen
 */
final class Debug {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'tools',
		'release' => 'stable',
		'active'  => 1,
	);


	/**
	 * Initialize
	 */
	public function __construct() {
		$this->prop['active'] = 1;
		if ( is_admin() ) :
			add_action( 'admin_enqueue_scripts', array( $this, 'load_layout_assets' ) );
		endif;
	}


	/**
	 * Check the hook being triggered on the admin screen to add the options form to the current screen if need be
	 *
	 * @param string $hook The WordPress hook name being triggered.
	 * @return void
	 */
	public function load_layout_assets( $hook ) {
		if ( 'settings_page_markup-markdown-admin' === $hook ) :
			add_action( 'markup_markdown_tabmenu_options', array( $this, 'add_tabmenu' ), 9999 );
			add_action( 'markup_markdown_tabcontent_options', array( $this, 'add_tabcontent' ), 9999 );
		endif;
	}


	/**
	 * Add the debug menu item inside the options screen
	 *
	 * @since 3.16.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabmenu() {
		echo "\t\t\t\t\t\t<li><a href=\"#tab-debug\" class=\"mmd-ico ico-file\">" . esc_html__( 'Debug', 'markup-markdown' ) . "</a></li>\n";
	}


	/**
	 * Display debug options inside the options screen
	 *
	 * @since 3.16.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabcontent() {
		printf( '<div id="tab-debug">' );
		$my_tmpl = markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Templates/tmpl-status.php';
		if ( markup_markdown()->exists( $my_tmpl ) ) :
			markup_markdown()->clear_cache( $my_tmpl );
			include $my_tmpl;
		endif;
		printf( '</div>' );
	}
}
