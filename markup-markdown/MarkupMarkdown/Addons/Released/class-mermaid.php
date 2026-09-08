<?php
/**
 * Stable addon "Mermaid" to enable diagrams
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      1.7.2
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;


/**
 * The Mermaid class enable mermaid diagrams rendering on the edit screen as well as on the front end
 */
final class Mermaid {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'mermaid',
		'release' => 'stable',
		'active'  => 0,
		'engine'  => '',
	);


	/**
	 * Markup Markdown plugin folder
	 *
	 * @var string
	 */
	private $plugin_uri = '';


	/**
	 * Check if active and initialize
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_ADDONS' ) || ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		else :
			$this->initialize(); // Run.
		endif;
	}


	/**
	 * Initialize
	 */
	private function initialize() {
		if ( is_admin() ) :
			add_filter( 'markup_markdown_verified_config', array( $this, 'update_config' ) );
			add_filter( 'markup_markdown_var2const', array( $this, 'create_const' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_layout_assets' ) );
		endif;
		if ( defined( 'MARKUP_MARKDOWN_USE_MERMAID' ) && isset( MARKUP_MARKDOWN_USE_MERMAID[0] ) && 1 === (int) MARKUP_MARKDOWN_USE_MERMAID[0] ) :
			if ( isset( MARKUP_MARKDOWN_USE_MERMAID[1] ) ) :
				$this->prop['engine'] = MARKUP_MARKDOWN_USE_MERMAID[1];
				$this->plugin_uri     = markup_markdown()->plugin_uri;
				if ( is_admin() ) :
					add_action( 'markup_markdown_load_engine_scripts', array( $this, 'load_admin_mermaid_scripts' ) );
				elseif ( isset( MARKUP_MARKDOWN_USE_MERMAID[2] ) && (int) MARKUP_MARKDOWN_USE_MERMAID[2] > 0 ) :
					add_action( 'wp_footer', array( $this, 'load_front_mermaid_scripts' ) );
				endif;
			endif;
		endif;
	}


	/**
	 * Magic method to retrieve a property
	 *
	 * @param string $name The property key to retrieve.
	 *
	 * @return string The property value
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->prop ) ) :
			return $this->prop[ $name ];
		elseif ( 'label' === $name ) :
			return esc_html__( 'Mermaid', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Easily display diagrams and charts inside your post.', 'markup-markdown' );
		endif;
		return 'markup_markdown_undefined';
	}


	/**
	 * Filter to parse code highlighter options from the options screen when the form was submitted
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The modified data
	 */
	public function update_config( $my_cnf ) {
		$my_cnf['mermaid_engine'] = filter_input( INPUT_POST, 'mmd_usemermaid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$my_cnf['mermaid_active'] = isset( $my_cnf['mermaid_engine'] ) && in_array( $my_cnf['mermaid_engine'], array( 'mermaid' ), true ) ? 1 : 0;
		$my_cnf['mermaid_front']  = filter_input( INPUT_POST, 'mermaid_front', FILTER_VALIDATE_INT );
		return $my_cnf;
	}


	/**
	 * Generate PHP constants for the primary keys so we can access them quickly anywhere
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The associate array with the final constants to be created
	 */
	public function create_const( $my_cnf ) {
		$my_cnf['MARKUP_MARKDOWN_USE_MERMAID'] = array(
			isset( $my_cnf['mermaid_active'] ) && (int) $my_cnf['mermaid_active'] > 0 ? 1 : 0,
		);
		unset( $my_cnf['latex_active'] );
		if ( $my_cnf['MARKUP_MARKDOWN_USE_MERMAID'][0] > 0 ) :
			$my_cnf['MARKUP_MARKDOWN_USE_MERMAID'][1] = isset( $my_cnf['mermaid_engine'] ) ? htmlspecialchars( $my_cnf['mermaid_engine'] ) : '';
			$my_cnf['MARKUP_MARKDOWN_USE_MERMAID'][2] = isset( $my_cnf['mermaid_front'] ) ? (int) $my_cnf['mermaid_front'] : '';
		endif;
		unset( $my_cnf['mermaid_active'] );
		unset( $my_cnf['mermaid_engine'] );
		unset( $my_cnf['mermaid_front'] );
		return $my_cnf;
	}


	/**
	 * Check the hook being triggered on the admin screen to add the options form to the current screen if need be
	 *
	 * @param string $hook The WordPress hook name being triggered.
	 * @return void
	 */
	public function load_layout_assets( $hook ) {
		if ( 'settings_page_markup-markdown-admin' === $hook ) :
			add_action( 'markup_markdown_tabmenu_options', array( $this, 'add_tabmenu' ) );
			add_action( 'markup_markdown_tabcontent_options', array( $this, 'add_tabcontent' ) );
		endif;
	}


	/**
	 * Add the layout menu item inside the options screen
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabmenu() {
		echo "\t\t\t\t\t\t<li><a href=\"#tab-mermaid\" class=\"mmd-ico ico-chart\">" . esc_html__( 'Mermaid', 'markup-markdown' ) . "</a></li>\n";
	}


	/**
	 * Display layout options inside the options screen
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabcontent() {
		$conf_file = markup_markdown()->conf_blog_prefix . 'conf.php';
		if ( markup_markdown()->exists( $conf_file ) ) :
			require_once $conf_file;
		endif;
		$my_tmpl = markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Templates/tmpl-mermaidform.php';
		if ( markup_markdown()->exists( $my_tmpl ) ) :
			markup_markdown()->clear_cache( $my_tmpl );
			include $my_tmpl;
		endif;
	}


	/**
	 * Method to load the scripts related to the selected LaTeX Engine on the edit screen
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function load_admin_mermaid_scripts() {
		if ( isset( $this->prop['engine'] ) && ! empty( $this->prop['engine'] ) && 'mermaid' === $this->prop['engine'] ) :
			wp_enqueue_script( 'markup_markdown__mermaid', $this->plugin_uri . 'assets/mermaid/dist/mermaid.min.js', array( 'markup_markdown__wordpress_richedit' ), '11.6.0', true );
		endif;
	}


	/**
	 * Method to load the scripts related to the selected LaTeX Engine on the frontend screen
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function load_front_mermaid_scripts() {
		if ( isset( $this->prop['engine'] ) && ! empty( $this->prop['engine'] ) && 'mermaid' === $this->prop['engine'] ) :
			wp_enqueue_script( 'markup_markdown__mermaid_render', $this->plugin_uri . 'assets/mermaid/dist/mermaid.min.js', array(), '11.6.0', true );
			wp_add_inline_script( 'markup_markdown__mermaid_render', $this->add_inline_mermaid_conf() );
		endif;
	}


	/**
	 * Katex specific inline config for the frontend
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return string The javascript inline code to be added
	 */
	public function add_inline_mermaid_conf() {
		$js = 'mermaid.initialize({ startOnLoad: true });';
		return $js;
	}
}
