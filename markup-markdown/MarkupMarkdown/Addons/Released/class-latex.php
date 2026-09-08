<?php
/**
 * Stable addon "LaTeX" to enable the LaTeX input and output
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      3.12.0
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;

/**
 * Add the LaTeX support for EasyMDE on the edit screen + rendering on the frontend
 */
final class Latex {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'latex',
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
	 * Initialize or exit if need be
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_ADDONS' ) || ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS ) === false ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		else :
			if ( is_admin() ) :
				add_filter( 'markup_markdown_verified_config', array( $this, 'update_config' ) );
				add_filter( 'markup_markdown_var2const', array( $this, 'create_const' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'load_layout_assets' ) );
			endif;
			if ( defined( 'MARKUP_MARKDOWN_USE_LATEX' ) && isset( MARKUP_MARKDOWN_USE_LATEX[0] ) && (int) MARKUP_MARKDOWN_USE_LATEX[0] === 1 ) :
				if ( isset( MARKUP_MARKDOWN_USE_LATEX[1] ) ) :
					$this->prop['engine'] = MARKUP_MARKDOWN_USE_LATEX[1];
					$this->plugin_uri     = markup_markdown()->plugin_uri;
					if ( is_admin() ) :
						add_action( 'markup_markdown_load_engine_stylesheets', array( $this, 'load_latex_stylesheets' ) );
						add_action( 'markup_markdown_load_engine_scripts', array( $this, 'load_admin_latex_scripts' ) );
					elseif ( isset( MARKUP_MARKDOWN_USE_LATEX[2] ) && (int) MARKUP_MARKDOWN_USE_LATEX[2] > 0 ) :
						add_action( 'wp_head', array( $this, 'load_latex_stylesheets' ) );
						add_action( 'wp_footer', array( $this, 'load_front_latex_scripts' ) );
					endif;
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
			return esc_html__( 'LaTeX', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Easily type and render math formulas inside your post.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}


	/**
	 * Filter to parse code highlighter options from the options screen when the form was submitted
	 *
	 * @since 3.12.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The modified data
	 */
	public function update_config( $my_cnf ) {
		$my_cnf['latex_engine']   = filter_input( INPUT_POST, 'mmd_uselatex', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$my_cnf['latex_active']   = isset( $my_cnf['latex_engine'] ) && in_array( $my_cnf['latex_engine'], array( 'katex', 'mathjax' ) ) ? 1 : 0;
		$my_cnf['latex_front']    = filter_input( INPUT_POST, 'mmd_latex_front', FILTER_VALIDATE_INT );
		$my_cnf['latex_front_id'] = filter_input( INPUT_POST, 'mmd_latex_front_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		return $my_cnf;
	}


	/**
	 * Generate PHP constants for the primary keys so we can access them quickly anywhere
	 *
	 * @since 3.12.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The associate array with the final constants to be created
	 */
	public function create_const( $my_cnf ) {
		$my_cnf['MARKUP_MARKDOWN_USE_LATEX'] = array(
			isset( $my_cnf['latex_active'] ) && (int) $my_cnf['latex_active'] > 0 ? 1 : 0,
		);
		unset( $my_cnf['latex_active'] );
		if ( $my_cnf['MARKUP_MARKDOWN_USE_LATEX'][0] > 0 ) :
			$my_cnf['MARKUP_MARKDOWN_USE_LATEX'][1] = isset( $my_cnf['latex_engine'] ) ? htmlspecialchars( $my_cnf['latex_engine'] ) : '';
			$my_cnf['MARKUP_MARKDOWN_USE_LATEX'][2] = isset( $my_cnf['latex_front'] ) ? (int) $my_cnf['latex_front'] : '';
			$my_cnf['MARKUP_MARKDOWN_USE_LATEX'][3] = isset( $my_cnf['latex_front_id'] ) ? htmlspecialchars( $my_cnf['latex_front_id'] ) : '';
		endif;
		unset( $my_cnf['latex_engine'] );
		unset( $my_cnf['latex_front'] );
		unset( $my_cnf['latex_front_id'] );
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
		echo "\t\t\t\t\t\t<li><a href=\"#tab-latex\" class=\"mmd-ico ico-square\">" . esc_html__( 'LaTeX', 'markup-markdown' ) . "</a></li>\n";
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
		$my_tmpl = markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Templates/tmpl-latexform.php';
		if ( markup_markdown()->exists( $my_tmpl ) ) :
			markup_markdown()->clear_cache( $my_tmpl );
			include $my_tmpl;
		endif;
	}


	/**
	 * Method to load the stylesheets related to the selected LaTeX Engine
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return boolean TRUE if a admin asset was enqueued, FALSE otherwise
	 */
	public function load_latex_stylesheets() {
		if ( ! isset( $this->prop['engine'] ) || empty( $this->prop['engine'] ) || 'none' === $this->prop['engine'] ) :
			return false; // Do nothing.
		elseif ( 'katex' === $this->prop['engine'] ) :
			wp_enqueue_style( 'markup_markdown__latex_katex', $this->plugin_uri . 'assets/katex/katex.min.css', is_admin() ? array( 'markup_markdown__wordpress_richedit' ) : array(), '0.16.22' );
			return true;
		elseif ( 'mathml' === $this->prop['engine'] ) :
			return true; // Nothing to be done.
		endif;
	}


	/**
	 * Method to load the scripts related to the selected LaTeX Engine on the edit screen
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return boolean TRUE if an admin asset was enqueued, FALSE otherwise
	 */
	public function load_admin_latex_scripts() {
		if ( ! isset( $this->prop['engine'] ) || empty( $this->prop['engine'] ) || 'none' === $this->prop['engine'] ) :
			return false; // Do nothing.
		elseif ( 'katex' === $this->prop['engine'] ) :
			wp_enqueue_script( 'markup_markdown__latex_katex', $this->plugin_uri . 'assets/katex/katex.min.js', array( 'markup_markdown__wordpress_richedit' ), '0.16.22', true );
			return true;
		elseif ( 'mathjax' === $this->prop['engine'] ) :
			wp_enqueue_script( 'markup_markdown__latex_mathjax', $this->plugin_uri . 'assets/mathjax/es5/tex-svg.js', array( 'markup_markdown__wordpress_richedit' ), '3.2.2', true );
			return true;
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
	public function load_front_latex_scripts() {
		if ( ! isset( $this->prop['engine'] ) || empty( $this->prop['engine'] ) || 'none' === $this->prop['engine'] ) :
			return false; // Do nothing.
		elseif ( 'katex' === $this->prop['engine'] ) :
			wp_enqueue_script( 'markup_markdown__latex_katex', $this->plugin_uri . 'assets/katex/katex.min.js', array(), '0.16.22', true );
			wp_enqueue_script( 'markup_markdown__latex_katex_render', $this->plugin_uri . 'assets/katex/contrib/auto-render.min.js', array( 'markup_markdown__latex_katex' ), '0.16.22', true );
			wp_add_inline_script( 'markup_markdown__latex_katex_render', $this->add_inline_katex_conf() );
			return true;
		elseif ( 'mathjax' === $this->prop['engine'] ) :
			wp_enqueue_script( 'markup_markdown__latex_mathjax_render', $this->plugin_uri . 'assets/mathjax/es5/tex-svg.js', array(), '3.2.2', true );
			wp_add_inline_script( 'markup_markdown__latex_mathjax_render', $this->add_inline_mathjax_conf() );
			return true;
		endif;
	}


	/**
	 * Katex specific inline config for the frontend
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_inline_katex_conf() {
		$js = 'document.addEventListener("DOMContentLoaded",function(){renderMathInElement(';
		if ( isset( MARKUP_MARKDOWN_USE_LATEX[3] ) && ! empty( MARKUP_MARKDOWN_USE_LATEX[3] ) ) :
			$js .= 'document.getElementById("' . MARKUP_MARKDOWN_USE_LATEX[3] . '")';
		else :
			$js .= 'document.body';
		endif;
		$js .= ',{delimiters:[{left:\'$$\',right:\'$$\',display:true},{left:\'\$\',right:\'\$\',display:false}],throwOnError:false});';
		$js .= '});';
		return $js;
	}


	/**
	 * Mathjax specific inline config for the frontend
	 *
	 * @since 3.8.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_inline_mathjax_conf() {
		$js  = 'window.MathJax={tex:{inlineMath:[[\'$\',\'$\']]},svg:{fontCache:\'global\'},options:{skipHtmlTags:[\'code\',\'pre\']}};';
		$js .= '(function(_d){var s=_d.createElement(\'script\');s.src="' . $this->plugin_uri . 'assets/mathjax/es5/tex-svg.js";s.async=true;_d.head.appendChild(s);})(window.document);';
		return $js;
	}
}
