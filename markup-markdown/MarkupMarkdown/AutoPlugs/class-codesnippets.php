<?php
/**
 * Autoplug "CodeSnippets" to add snippets support with markdown posts
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 * @since      3.13.0
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adds a few check to not disable Gutenberg for a few admin screens
 */
final class CodeSnippets {


	/**
	 * Quick tiny check to check if we need to initialize the class
	 */
	public function __construct() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/code-snippets/code-snippets.php' ) ) :
			$this->init();
		endif;
	}


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	private function init() {
		if ( is_admin() ) :
			$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS );
			if ( isset( $page ) && false !== in_array( $page, array( 'add-snippet', 'edit-snippet' ), true ) ) :
				add_filter( 'markup_markdown_disable_gutenberg', '__return_false' );
			endif;
		endif;
	}
}
