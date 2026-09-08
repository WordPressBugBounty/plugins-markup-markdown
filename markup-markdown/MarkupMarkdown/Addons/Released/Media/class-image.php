<?php
/**
 * Stable addons image utilies
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Addons\Released\Media;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;

require_once markup_markdown()->plugin_dir . '/MarkupMarkdown/Abstracts/class-imagetinyapi.php';

/**
 * A simple utility to manipulate HTML images
 */
final class Image extends \MarkupMarkdown\Abstracts\ImageTinyAPI {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'Image',
		'release' => 'stable',
		'active'  => 1,
	);


	/**
	 * Current blog URL
	 *
	 * @var string
	 */
	public $home_url = '';


	/**
	 * Media sizes available
	 *
	 * @var array
	 */
	public $def_sizes = array();


	/**
	 * Media sizes available
	 *
	 * @var integer<0|1>
	 */
	public $gutenberg = 0;


	/**
	 * Absolute path to the cache directory
	 *
	 * @var string
	 */
	protected $asset_cache_dir = '';


	/**
	 * Properties about the WordPress upload dir
	 *
	 * @var array
	 */
	protected $upload_dir = array();


	/**
	 * Simple check to verify if the addon is active
	 */
	public function __construct() {
		if ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		elseif ( ! is_admin() ) :
			add_filter( 'markup_markdown_addon_mmd2html', array( $this, 'render_responsive_images' ), 9, 1 );
		endif;
	}


	/**
	 * Magic method to retrieve properties of the current setting
	 *
	 * @param string $name The property key.
	 *
	 * @return string The property relaed value
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->prop ) ) :
			return $this->prop[ $name ];
		elseif ( 'label' === $name ) :
			return esc_html__( 'Responsive Image', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Add basic html code syntax for responsive media.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}


	/**
	 * Load invidual block stylesheets if the theme was built for the Gutenberg editor
	 *
	 * @return boolean TRUE if the assets were loaded, FALSE otherwise
	 */
	public function load_image_block_assets() {
		if ( $this->gutenberg > 0 ) : // Already loaded.
			return false;
		endif;
		$this->gutenberg = 1;
		$blog_version    = get_bloginfo( 'version' );
		wp_enqueue_style( 'wp-block-image', '/wp-includes/blocks/image/style.min.css', array(), $blog_version );
		wp_enqueue_style( 'wp-block-embed', '/wp-includes/blocks/embed/style.min.css', array(), $blog_version );
		wp_enqueue_style( 'wp-block-table', '/wp-includes/blocks/embed/table.min.css', array(), $blog_version );
		wp_enqueue_style( 'wp-block-gallery', '/wp-includes/blocks/gallery/style.min.css', array(), $blog_version );
		wp_enqueue_style( 'mmd-block-gallery', markup_markdown()->plugin_uri . '/assets/markup-markdown/css/gallery-compatibility.min.css', array(), $blog_version );
		add_filter( 'the_content', array( $this, 'render_gutenberg_html4_galleries' ), 19, 1 );
		return true;
	}


	/**
	 * Simple parser to check HTML image tags and replace it with the WordPress responsive ones
	 *
	 * @param string $content The HTML content to be parsed.
	 *
	 * @return string The modified HTML code
	 */
	private function parse_img_tags( $content = '' ) {
		$html_imgs = array();
		if ( ! preg_match_all( '#<img.*?src="(.*?)"[^>]*>#', $content, $html_imgs ) ) :
			return $content;
		endif;
		if ( ! isset( $html_imgs ) || ! isset( $html_imgs[0] ) || ! is_array( $html_imgs[0] ) ) :
			return $content;
		endif;
		foreach ( $html_imgs[0] as $idx => $img_tag ) :
			$img_id = $this->get_cached_asset_id( $html_imgs[1][ $idx ] );
			if ( $img_id > 0 ) :
				$new_img = $this->native_wp_image( $img_id, $html_imgs[1][ $idx ], $img_tag );
				$content = str_replace( $html_imgs[0][ $idx ], $new_img, $content );
			endif;
		endforeach;
		return $content;
	}


	/**
	 * Simple parser to fix HTML image tags:
	 * - Wrap links inside the figure tag
	 * - Remove the P tag wrapping figure
	 *
	 * @param string $content The HTML content to be parsed.
	 *
	 * @return string The modified HTML code
	 */
	private function clean_html( $content = '' ) {
		if ( ! isset( $content ) || empty( $content ) ) :
			return '';
		endif;
		if ( preg_match( '#</figure></a>#', $content ) ) :
			$content = preg_replace( '#(<a[^>]+>)(<figure[^>]+>)#', '$2$1', str_replace( '</figure></a>', '</a></figure>', $content ) );
		endif;
		if ( preg_match( '#</figure></p>#', $content ) ) :
			$content = str_replace( array( '<p><figure', '</figure></p>' ), array( '<figure', '</figure>' ), $content );
		endif;
		if ( preg_match( '#<table>#', $content ) && defined( 'MARKUP_MARKDOWN_USE_BLOCKSTYLES' ) && MARKUP_MARKDOWN_USE_BLOCKSTYLES ) :
			$content = preg_replace( '#<table>#', '<figure class="wp-block-table"><table class="has-fixed-layout">', str_replace( '</table>', '</table></figure>', $content ) );
		endif;
		return $content;
	}


	/**
	 * Format the images html tags as WordPress
	 *
	 * @param string $content The html generated from the markdown.
	 *
	 * @return string $content The modified html code
	 */
	public function render_responsive_images( $content = '' ) {
		if ( defined( 'MARKUP_MARKDOWN_USE_BLOCKSTYLES' ) && MARKUP_MARKDOWN_USE_BLOCKSTYLES ) :
			$this->load_image_block_assets();
		endif;
		if ( empty( $this->home_url ) ) :
			$this->home_url = preg_replace( '#(\.[a-z]+)\/.*?$#', '$1/', get_home_url() );
		endif;
		if ( empty( $this->asset_cache_dir ) ) :
			$this->asset_cache_dir = markup_markdown()->cache_dir . '/.assets';
			if ( ! markup_markdown()->exists( $this->asset_cache_dir ) ) :
				markup_markdown()->mkdir( $this->asset_cache_dir );
			endif;
		endif;
		if ( empty( $this->upload_dir ) ) :
			$this->upload_dir = wp_upload_dir();
		endif;
		// Cleanup HTML.
		return $this->clean_html( $this->parse_img_tags( $content ) );
	}


	/**
	 * Format the gallery classnames generated from the shortcode
	 *
	 * @access public
	 * @since 3.20.7
	 *
	 * @param string $content The html generated.
	 * @return string $content The modified html code
	 */
	public function render_gutenberg_html4_galleries( $content = '' ) {
		preg_match_all( '#<div id\=[\"|\']{1}gallery-\d*[\"|\']{1} class\=[\"|\']{1}(gallery galleryid-\d* gallery-columns-\d* gallery-size-[a-z]+)[\"|\']{1}>#', $content, $html4_galleries );
		if ( ! isset( $html4_galleries ) || ! isset( $html4_galleries[0] ) || ! is_array( $html4_galleries[0] ) ) :
			return $content;
		endif;
		foreach ( $html4_galleries[0] as $idx => $gal_opener ) :
				$content = str_replace( $html4_galleries[0][ $idx ], str_replace( $html4_galleries[1][ $idx ], $html4_galleries[1][ $idx ] . ' ' . str_replace( 'gallery', 'wp-block-gallery', $html4_galleries[1][ $idx ] ) . ' has-nested-images columns-default is-cropped is-layout-flex wp-block-gallery-is-layout-flex', $html4_galleries[0][ $idx ] ), $content );
		endforeach;
		$content = preg_replace( '#figure class\=[\"|\']{1}gallery-item[\"|\']{1}#', 'figure class="gallery-item wp-block-image"', $content );
		return $content;
	}
}
