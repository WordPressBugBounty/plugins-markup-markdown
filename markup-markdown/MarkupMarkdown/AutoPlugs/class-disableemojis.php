<?php
/**
 * Autoplug "DisableEmojis" to disable the default emoji rendering
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 * @since      3.6.1
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adds a few hooks to completely remove emojis rendering
 */
final class DisableEmojis {


	/**
	 * Check which kind of hooks should be applied
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_DISABLEEMOJIS_PLUG' ) ) :
			define( 'MARKUP_MARKDOWN_DISABLEEMOJIS_PLUG', true );
		endif;
		if ( defined( 'MARKUP_MARKDOWN_DISABLEEMOJIS_PLUG' ) && MARKUP_MARKDOWN_DISABLEEMOJIS_PLUG ) :
			$this->initialize();
		endif;
	}


	/**
	 * Initialize emoji features
	 *
	 * @return void
	 */
	private function initialize() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/disable-emojis/disable-emojis.php' ) ) :
			// The plugin exists, only need the community patch.
			add_action( 'admin_init', array( $this, 'disable_back_emojis' ) );
		else :
			// The plugin wasn't installed.
			add_action( 'init', array( $this, 'disable_front_emojis' ) );
			add_action( 'admin_init', array( $this, 'disable_back_emojis' ) );
		endif;
	}


	/**
	 * Disable emojis
	 *
	 * @access public
	 * @since 3.6.0
	 *
	 * @return void
	 */
	public function disable_front_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
		add_filter( 'wp_resource_hints', array( $this, 'disable_emojis_remove_dns_prefetch' ), 10, 2 );
	}


	/**
	 * Disable emojis
	 *
	 * @access public
	 * @since 3.6.0
	 *
	 * @return void
	 */
	public function disable_back_emojis() {
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}


	/**
	 * Filter function used to remove the tinymce emoji plugin.
	 *
	 * @access public
	 * @since 3.6.0
	 *
	 * @param array $plugins The TinyMCE list of plugins.
	 * @return array The difference betwen the two arrays
	 */
	public function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) :
			return array_diff( $plugins, array( 'wpemoji' ) );
		endif;
		return array();
	}


	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @access public
	 * @since 3.6.0
	 *
	 * @param array  $urls The URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for.
	 * @return array The difference betwen the two arrays.
	 */
	public function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) :
			// Strip out any URLs referencing the WordPress.org emoji location.
			$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
			foreach ( $urls as $key => $url ) :
				if ( false !== strpos( $url, $emoji_svg_url_bit ) ) :
					unset( $urls[ $key ] );
				endif;
			endforeach;
		endif;
		return $urls;
	}
}



new \MarkupMarkdown\AutoPlugs\DisableEmojis();
