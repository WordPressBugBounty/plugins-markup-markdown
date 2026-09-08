<?php
/**
 * Autoplug "WooCommerce" to allow WooCommerce categories to use markdown
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 * @since      3.4.1
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adds a few hooks to add missing markdown outputs for a few WooCommerce fields
 */
class Woocommerce {


	/**
	 * Quick tiny check to avoid initializing the autoplug twice
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_WOOCOMMERCE_PLUG' ) ) :
			$this->initialize();
		endif;
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	private function initialize() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) :
			define( 'MARKUP_MARKDOWN_WOOCOMMERCE_PLUG', true );
		else :
			define( 'MARKUP_MARKDOWN_WOOCOMMERCE_PLUG', false );
		endif;
		if ( defined( 'MARKUP_MARKDOWN_WOOCOMMERCE_PLUG' ) && MARKUP_MARKDOWN_WOOCOMMERCE_PLUG ) :
			add_action( 'after_setup_theme', array( $this, 'woocommerce_plug' ) );
		endif;
	}


	/**
	 * Trigger the required filters
	 *
	 * @return void
	 */
	public function woocommerce_plug() {
		// For the PHP templates.
		add_filter( 'woocommerce_taxonomy_archive_description_raw', array( $this, 'tax_desc_mmd2html' ), 10, 1 );
	}


	/**
	 * Filters the archive's raw description on taxonomy archives.
	 *
	 * @since 3.4.1
	 * @source woocommerce/includes/wc-template-functions.php
	 *
	 * @param string $term_desc Raw description text.
	 *
	 * @return string The modified term description
	 */
	public function tax_desc_mmd2html( $term_desc ) {
		return apply_filters( 'markup_markdown_post_mmd2html', str_replace( array( '<p>', '</p>' ), '', $term_desc ), false );
	}
}
