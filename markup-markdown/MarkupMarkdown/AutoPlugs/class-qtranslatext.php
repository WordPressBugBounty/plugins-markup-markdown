<?php
/**
 * Autoplug "QTranslateXT" to allow markdown with the multi-language QTranslateXT Plugin
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 * @since      3.19.0
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adds a few hooks to use properly markdown editors with QTranslateXT on the edit screen
 */
final class QTranslateXT {


	/**
	 * The relative path to the plugin directory used for assets
	 *
	 * @since 3.19.0
	 * @access private
	 *
	 * @var string
	 */
	private $plugin_uri = '';


	/**
	 * Quick tiny check to verify if we need to initialize the class
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_QTRANSLATEXT_PLUG' ) ) :
			$this->init();
		endif;
	}


	/**
	 * Trigger assets 
	 *
	 * @return void
	 */
	private function init() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/qtranslate-xt/qtranslate.php' ) && function_exists( 'qtranxf_init_language' ) ) :
			define( 'MARKUP_MARKDOWN_QTRANSLATEXT_PLUG', true );
		else :
			define( 'MARKUP_MARKDOWN_QTRANSLATEXT_PLUG', false );
		endif;
		if ( is_admin() ) :
			add_action( 'markup_markdown_load_engine_scripts', array( $this, 'load_qtranslate_scripts' ) );
		endif;
	}


	/**
	 * Enqueue the qTranslateXT related assets
	 *
	 * @return void
	 */
	public function load_qtranslate_scripts() {
		$my_bridge = markup_markdown()->plugin_uri . 'assets/qtranslate-xt/js/bridge.';
		if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MMD_SCRIPT_DEBUG' ) && MMD_SCRIPT_DEBUG ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) :
			$my_bridge .= 'debug';
		else :
			$my_bridge .= 'min';
		endif;
		$my_bridge .= '.js';
		wp_enqueue_script( 'markup_markdown__qtranslate_bridge', $my_bridge, array( 'markup_markdown__wordpress_richedit' ), '1.0.0', true );
	}
}
