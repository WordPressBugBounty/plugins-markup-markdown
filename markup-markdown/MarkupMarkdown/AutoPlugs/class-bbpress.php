<?php
/**
 * Autoplug "BBPress" to add markdown support to the BBPress plugin
 * BBPress add _Forum_ features to a WordPress instance
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 * @since      3.9.0
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adds few hooks and a bit of css / jss so markdown can be used with BBPress
 */
final class BBPress {


	/**
	 * The relative path to the plugin directory used for assets
	 *
	 * @var string $plugin_uri
	 */
	private $plugin_uri = '';


	/**
	 * Quick tiny check to avoid initializing the autoplug twice
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_BBPRESS_PLUG' ) ) :
			$this->initialize();
		endif;
	}


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	private function initialize() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/bbpress/bbpress.php' ) && function_exists( 'bbpress' ) ) :
			define( 'MARKUP_MARKDOWN_BBPRESS_PLUG', true );
		else :
			define( 'MARKUP_MARKDOWN_BBPRESS_PLUG', false );
		endif;
		if ( MARKUP_MARKDOWN_BBPRESS_PLUG && ! is_admin() ) :
			if ( ! defined( 'MARKUP_MARKDOWN_MEDIA_UPLOADER' ) ) :
				define( 'MARKUP_MARKDOWN_MEDIA_UPLOADER', false );
			endif;
			add_action( 'bbp_enqueue_scripts', array( $this, 'load_edit_mmdform' ) );
			add_filter( 'markup_markdown_proxy_filters', array( $this, 'get_bbpress_filters' ), 10, 1 );
		endif;
	}


	/**
	 * Tell Markup Markdown which filter should be triggered for the rendering
	 *
	 * @param array $arr The current array containing the list of target WordPress filters.
	 * @return array The modified array that includes BBPress filters
	 */
	public function get_bbpress_filters( $arr = array() ) {
		return array_merge(
			$arr,
			array(
				'bbp_get_forum_content',
				'bbp_get_reply_content',
				'bbp_get_topic_content',
			)
		);
	}


	/**
	 * Check we are on a bbpress related template and trigger the launch of the markdown editor
	 *
	 * @since 3.9.0
	 * @access public
	 *
	 * @return boolean TRUE if the edit form view was triggered or FALSE
	 */
	public function load_edit_mmdform() {
		if ( ! function_exists( 'bbp_use_wp_editor' ) || ! function_exists( 'is_bbpress' ) ) :
			return false;
		endif;
		if ( ! bbp_use_wp_editor() || ! is_bbpress() ) :
			return false;
		endif;
		add_filter( 'markup_markdown_frontend_enabled', '__return_true' );
		$this->plugin_uri = markup_markdown()->plugin_uri;
		add_action( 'markup_markdown_load_engine_stylesheets', array( $this, 'load_engine_stylesheets' ) );
		add_action( 'markup_markdown_load_engine_scripts', array( $this, 'load_engine_scripts' ) );
		return true;
	}


	/**
	 * Enqueue the BBPress related stylesheet
	 *
	 * @return void
	 */
	public function load_engine_stylesheets() {
		wp_enqueue_style( 'markup_markdown__bbpress_editor', $this->plugin_uri . 'assets/bbpress/css/field.min.css', array( 'markup_markdown__wordpress_richedit' ), bbpress()->version );
	}


	/**
	 * Enqueue the BBPress related script
	 *
	 * @return void
	 */
	public function load_engine_scripts() {
		wp_enqueue_script( 'markup_markdown__bbpress_editor', $this->plugin_uri . 'assets/bbpress/js/field.min.js', array( 'markup_markdown__wordpress_richedit' ), bbpress()->version, true );
	}
}
