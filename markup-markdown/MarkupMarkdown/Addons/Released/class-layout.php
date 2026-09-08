<?php
/**
 * Stable addon "Layout" to enable layout options
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
 * Various layout options that the user can tun
 */
final class Layout {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'layout',
		'release' => 'stable',
		'active'  => 1,
	);


	/**
	 * Boolean to flag if a gallery is in used
	 *
	 * @var integer
	 */
	private $gal = 0;


	/**
	 * Path to the static json config file
	 *
	 * @var string
	 */
	private $toolbar_conf = '';


	/**
	 * Post ID to use for the lightbox gallery
	 *
	 * @since 3.20.9
	 * @access private
	 *
	 * @var integer
	 */
	private $post_ID = 0;


	/**
	 * Initialize
	 */
	public function __construct() {
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_USE_LIGHTBOX' => 1 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_USE_IMAGESLOADED' => 1 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_USE_MASONRY' => 1 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_USE_BLOCKSTYLES' => 0 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_CUSTOM_TOOLBAR' => 0 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_USE_INDENT' => array( 'tabs', 2 ) );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_KEEP_SPACES' => 0 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_SUPER_BACKSLASH' => 0 );
		markup_markdown()->default_conf = array( 'MARKUP_MARKDOWN_USE_HEADINGS' => array( '1', '2', '3', '4', '5', '6' ) );
		$this->toolbar_conf             = markup_markdown()->conf_blog_prefix . 'conf_easymde_toolbar.json';
		if ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) :
			$this->prop['active'] = 0; // Do not continue.
		elseif ( is_admin() ) :
			add_filter( 'markup_markdown_verified_config', array( $this, 'update_config' ) );
			add_filter( 'markup_markdown_var2const', array( $this, 'create_const' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_layout_assets' ) );
		else :
			add_filter( 'markup_markdown_addon_mmd2html', array( $this, 'render_lightbox_masonry' ) );
			if ( defined( 'MARKUP_MARKDOWN_USE_BLOCKSTYLES' ) && MARKUP_MARKDOWN_USE_BLOCKSTYLES ) :
				// New theme using Gutenberg blocks need the hooks to be triggered earlier.
				add_filter( 'markup_markdown_addon_mmd2html', array( $this, 'render_gutenberg_basics' ), 12, 1 );
				add_filter( 'gallery_style', array( $this, 'gallery_style_filter' ), 11, 1 );
				add_filter( 'wp_get_attachment_link_attributes', array( $this, 'attachment_link_attributes_filter' ), 11, 2 );
				add_filter( 'body_class', array( $this, 'gutenberg_body_classes' ) );
			endif;
			add_action( 'wp_enqueue_scripts', array( $this, 'my_plugin_assets' ), 11 );
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
			return esc_html__( 'Layout', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'A few tools to help you enhancing your layout. (Lightbox, Masonry, etc...)', 'markup-markdown' );
		endif;
		return 'markup_markdown_undefined';
	}


	/**
	 * Filter to parse code highlighter options from the options screen when the form was submitted
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The modified data
	 */
	public function update_config( $my_cnf ) {
		$my_cnf['goodvibes']    = filter_input( INPUT_POST, 'mmd_goodvibes', FILTER_VALIDATE_INT );
		$my_cnf['lightbox']     = filter_input( INPUT_POST, 'mmd_lightbox', FILTER_VALIDATE_INT );
		$my_cnf['imagesloaded'] = filter_input( INPUT_POST, 'mmd_imagesloaded', FILTER_VALIDATE_INT );
		$my_cnf['masonry']      = filter_input( INPUT_POST, 'mmd_masonry', FILTER_VALIDATE_INT );
		$my_cnf['toolbar']      = preg_replace( '#[^a-z0-9_,]#', '', filter_input( INPUT_POST, 'mmd_toolbar', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		$my_cnf['headings']     = array();
		$fm_headings            = filter_input( INPUT_POST, 'mmd_headings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( isset( $fm_headings ) && is_array( $fm_headings ) ) :
			foreach ( $fm_headings as $heading ) :
				$heading = (int) $heading;
				if ( in_array( $heading, $my_cnf['headings'], true ) || ! is_numeric( $heading ) || $heading < 1 || $heading > 6 ) :
					continue;
				endif;
				$my_cnf['headings'][] = $heading;
			endforeach;
		endif;
		$my_cnf['indent_char']     = filter_input( INPUT_POST, 'mmd_indent_char', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$my_cnf['indent_size']     = filter_input( INPUT_POST, 'mmd_indent_size', FILTER_VALIDATE_INT );
		$my_cnf['keep_spaces']     = filter_input( INPUT_POST, 'mmd_keepspaces', FILTER_VALIDATE_INT );
		$my_cnf['super_backslash'] = filter_input( INPUT_POST, 'mmd_superbackslash', FILTER_VALIDATE_INT );
		return $my_cnf;
	}


	/**
	 * Generate PHP constants for the primary keys so we can access them quickly anywhere
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The associate array with the final constants to be created
	 */
	public function create_const( $my_cnf ) {
		$my_cnf['MARKUP_MARKDOWN_USE_LIGHTBOX'] = isset( $my_cnf['lightbox'] ) ? $my_cnf['lightbox'] : 0;
		unset( $my_cnf['lightbox'] );
		$my_cnf['MARKUP_MARKDOWN_USE_IMAGESLOADED'] = isset( $my_cnf['imagesloaded'] ) ? $my_cnf['imagesloaded'] : 0;
		unset( $my_cnf['imagesloaded'] );
		$my_cnf['MARKUP_MARKDOWN_USE_MASONRY'] = isset( $my_cnf['masonry'] ) ? $my_cnf['masonry'] : 0;
		unset( $my_cnf['masonry'] );
		$my_cnf['MARKUP_MARKDOWN_USE_BLOCKSTYLES'] = isset( $my_cnf['goodvibes'] ) ? $my_cnf['goodvibes'] : 0;
		unset( $my_cnf['goodvibes'] );
		$my_cnf['MARKUP_MARKDOWN_USE_HEADINGS'] = isset( $my_cnf['headings'] ) && count( $my_cnf['headings'] ) > 1 ? $my_cnf['headings'] : array();
		unset( $my_cnf['headings'] );
		if ( isset( $my_cnf['toolbar'] ) > 0 ) :
			markup_markdown()->put_contents( $this->toolbar_conf, '{"my_buttons":' . wp_json_encode( explode( ',', $my_cnf['toolbar'] ) ) . '}' );
			unset( $my_cnf['toolbar'] );
		endif;
		$my_cnf['MARKUP_MARKDOWN_USE_INDENT'] = array(
			isset( $my_cnf['indent_char'] ) && false !== in_array( $my_cnf['indent_char'], array( 'tabs', 'spaces' ), true ) ? $my_cnf['indent_char'] : 'tabs',
			isset( $my_cnf['indent_size'] ) && false !== in_array( (int) $my_cnf['indent_size'], array( 2, 4 ), true ) ? (int) $my_cnf['indent_size'] : 2,
		);
		unset( $my_cnf['indent_char'] );
		unset( $my_cnf['indent_size'] );
		$my_cnf['MARKUP_MARKDOWN_KEEP_SPACES'] = isset( $my_cnf['keep_spaces'] ) ? $my_cnf['keep_spaces'] : 0;
		unset( $my_cnf['keep_spaces'] );
		$my_cnf['MARKUP_MARKDOWN_SUPER_BACKSLASH'] = isset( $my_cnf['super_backslash'] ) ? $my_cnf['super_backslash'] : 0;
		unset( $my_cnf['super_backslash'] );
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
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabmenu() {
		echo "\t\t\t\t\t\t<li><a href=\"#tab-layout\" class=\"mmd-ico ico-layout\">" . esc_html__( 'Layout', 'markup-markdown' ) . "</a></li>\n";
	}


	/**
	 * Display layout options inside the options screen
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabcontent() {
		$conf_file = markup_markdown()->conf_blog_prefix . 'conf.php';
		if ( markup_markdown()->exists( $conf_file ) ) :
			require_once $conf_file;
		endif;
		$my_tmpl = markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Templates/tmpl-layoutform.php';
		if ( markup_markdown()->exists( $my_tmpl ) ) :
			markup_markdown()->clear_cache( $my_tmpl );
			$markup_markdown_layout_toolbar_conf = $this->toolbar_conf;
			include $my_tmpl;
		endif;
	}


	/**
	 * Increment the gallery counter to separate different lightbox
	 *
	 * @since 2.2.2
	 * @access public
	 *
	 * @param string $gallery_style The html opening tag and styles of current gallery.
	 *
	 * @return string The updated html code
	 */
	public function gallery_style_filter( $gallery_style ) {
		++$this->gal;
		return $gallery_style;
	}


	/**
	 * Add extra html markup to trigger the lightbox on gallery
	 *
	 * @since 2.2.2
	 * @access public
	 *
	 * @param array   $attributes The current link.
	 * @param integer $attach_id The post ID (Attchment ID).
	 *
	 * @return array The updated link attributes
	 */
	public function attachment_link_attributes_filter( $attributes, $attach_id ) {
		if ( isset( $attributes['href'] ) && strpos( $attributes['href'], 'attachment' ) === false ) :
			$attributes['data-lightbox']   = 'gallery' . $this->post_ID . '-' . $this->gal;
			$attributes['data-attachment'] = $attach_id;
		endif;
		return $attributes;
	}


	/**
	 * Load Lightbox assets on the frontend
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @return integer 1 if the framework is used or 0 if unused
	 */
	private function load_lighbox_framework() {
		if ( defined( 'MARKUP_MARKDOWN_USE_LIGHTBOX' ) && MARKUP_MARKDOWN_USE_LIGHTBOX > 0 ) :
			$plugin_uri = markup_markdown()->plugin_uri;
			wp_deregister_script( 'lightbox' );
			wp_deregister_script( 'jquery-lightbox' );
			wp_enqueue_style( 'lightbox', $plugin_uri . 'assets/lightbox2/css/lightbox.min.css', array(), '2.11.4' );
			wp_enqueue_script( 'lightbox', $plugin_uri . 'assets/lightbox2/js/lightbox.min.js', array( 'jquery' ), '2.11.4', true );
			// Old themes.
			add_filter( 'gallery_style', array( $this, 'gallery_style_filter' ), 11, 1 );
			add_filter( 'wp_get_attachment_link_attributes', array( $this, 'attachment_link_attributes_filter' ), 11, 2 );
			return 1;
		else :
			return 0;
		endif;
	}


	/**
	 * Load ImageLoaded assets on the frontend
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @return integer TRUE if the framework was enqueued, FALSE otherwise
	 */
	private function load_imagesloaded_framework() {
		if ( defined( 'MARKUP_MARKDOWN_USE_IMAGESLOADED' ) && MARKUP_MARKDOWN_USE_IMAGESLOADED > 0 && ( ! defined( 'MARKUP_MARKDOWN_USE_MASONRY' ) || ! MARKUP_MARKDOWN_USE_MASONRY ) ) :
			wp_enqueue_script( 'imagesloaded' );
			return true;
		endif;
		return false;
	}


	/**
	 * Load ImageLoaded assets on the frontend
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param integer $lightbox_used 1 if the Lightbox framework is used - just for the dependency.
	 *
	 * @return boolean TRUE if the framework was enqueued, FALSE otherwise
	 */
	private function load_masonry_framework( $lightbox_used = 0 ) {
		$masonry_used = 0;
		if ( defined( 'MARKUP_MARKDOWN_USE_MASONRY' ) && MARKUP_MARKDOWN_USE_MASONRY > 0 ) :
			if ( is_singular() && get_post_format() === 'gallery' ) :
				$masonry_used = 1;
			elseif ( is_archive() || is_category() || is_tag() || is_tax() ) :
				$masonry_used = 1;
			endif;
		endif;
		if ( ! $masonry_used ) :
			return false;
		endif;
		wp_enqueue_script( 'masonry' );
		wp_enqueue_script( 'jquery-masonry' );
		wp_add_inline_style( $lightbox_used > 0 ? 'lightbox' : 'masonry', '.lightbox-set { margin: 0 -8px } .grid-sizer, .grid-item { margin: 0 8px 16px 8px; width: calc(50% - 16px) } .grid-item a, .grid-item a img { display: block }' );
		wp_add_inline_script( 'masonry', 'jQuery( document ).ready(function() { jQuery( \'.grid\' ).each(function() { var $grid = jQuery( this ); $grid.imagesLoaded().progress(function() { $grid.masonry( \'layout\' ); }); }); });' );
		return true;
	}


	/**
	 * Trigger Masonry or lightbox assets on the frontend
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return boolean TRUE if assets were queued, FALSE otherwise
	 */
	public function my_plugin_assets() {
		$config = markup_markdown()->conf_blog_prefix . 'conf.php';
		if ( ! markup_markdown()->exists( $config ) ) :
			return false;
		endif;
		require_once $config;
		// Register and enqueue lightbox.
		$lightbox_used = $this->load_lighbox_framework();
		// Register and enqueue lightbox.
		$imagesloaded_used = $this->load_imagesloaded_framework();
		// Register and enqueue masonry.
		$this->load_masonry_framework( $lightbox_used );
		return true;
	}


	/**
	 * Format the html so lightboxes or masonry layout can be used
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @param string $content The HTML content to be parsed.
	 *
	 * @return string The modified HTML
	 */
	public function render_lightbox_masonry( $content = '' ) {
		$allowed_medias = 'jpg|JPG|jpeg|JPEG|gif|GIF|png|PNG|apng|APNG|webp|WEBP|tiff|TIFF|avif|AVIF';
		$replacers      = array(
			// Adjust lightbox for image sets with masonry.
			// Old versions with no *figure* and *figcaption* tags.
			array( '#<li><a href="(/wp-content/.*?\.(' . $allowed_medias . '))" title="(myset[0-9_]+)\s(.*?)</li>#u', '<div class="grid-item"><a data-lightbox="$3" href="$1" title="$4</div>' ),
			array( "#<ul>\n*<div class=\"grid-item\"><a data-lightbox=\"(.*?)\" href=\"(.*?)\"#u", "<div id=\"$1\" class=\"grid lightbox-set\" data-masonry='{ \"itemSelector\": \".grid-item\", \"columnWidth\": \".grid-sizer\", \"percentPosition\": true }'>\n<div class=\"grid-sizer\"></div>\n<div class=\"grid-item\"><a data-lightbox=\"$1\" href=\"$2\"" ),
			// New version with *figure* and *figcaption*.
			array( "#<li>\n*<figure([^>]+)><a href=\"(/wp-content/.*?\.(" . $allowed_medias . "))\" title=\"(myset[0-9_]+)\s(.*?)\n*</li>#u", '<div class="grid-item"><figure$1><a data-lightbox="$4" href="$2" title="$5</div>' ),
			array( "#<ul>\n*<div class=\"grid-item\"><figure id\=\"(.*?)\"([^>]+)><a data-lightbox=\"(.*?)\" href=\"(.*?)\"#u", "<div id=\"$1\" class=\"grid lightbox-set\" data-masonry='{ \"itemSelector\": \".grid-item\", \"columnWidth\": \".grid-sizer\", \"percentPosition\": true }'>\n<div class=\"grid-sizer\"></div>\n<div class=\"grid-item\"><figure id=\"$1\"$2><a data-lightbox=\"$3\" href=\"$4\"" ),
			// Safety clean.
			array( "#</div>\n*</ul>#u", "</div>\n</div>" ),
			// Adjust lightbox for single images.
			array( '#<a href="(/wp-content/.*?\.(' . $allowed_medias . '))"#u', '<a href="$1" data-lightbox="mygallery"' ),
		);
		foreach ( $replacers as $regexp ) :
			$content = preg_replace( $regexp[0], $regexp[1], $content );
		endforeach;
		if ( is_singular() && ! $this->post_ID ) :
			$this->post_ID = get_the_ID();
		endif;
		return $content;
	}


	/**
	 * Format the html so gutenberg block styles can be applied
	 *
	 * @since 3.3.0
	 * @access public
	 *
	 * @param string $content The HTML content to be parsed.
	 *
	 * @return string Modified headlines with WordPress classnames
	 */
	public function render_gutenberg_basics( $content ) {
		// <h2 class="has-text-align-center"> => <h2 class="wp-block-heading has-text-align-center">.
		// <h2 id="peter" class="has-text-align-center"> => <h2 d="peter" class="wp-block-heading has-text-align-center">.
		$content = preg_replace( '#<h(\d)(.*?)class="#u', '<h$1$2class="wp-block-heading ', $content );
		// <h2> => <h2 class="wp-block-heading">.
		$content = preg_replace( '#<h(\d)>#u', '<h$1 class="wp-block-heading">', $content );
		return $content;
	}


	/**
	 * Add the missing classes used by Gutenbenberg rendering tools
	 *
	 * @since 3.20.10
	 * @access public
	 *
	 * @param array $classes Current body classnames.
	 *
	 * @return array $classes the modified body classnames
	 */
	public function gutenberg_body_classes( $classes = array() ) {
		if ( false === in_array( 'wp-embed-responsive', $classes, true ) ) :
			$classes[] = 'wp-embed-responsive';
		endif;
		return $classes;
	}
}
