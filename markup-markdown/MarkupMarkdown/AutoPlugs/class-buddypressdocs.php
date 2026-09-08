<?php
/**
 * Autoplug "BuddyPress Docs" to add markdown support to the BuddyPress Doc plugin
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 * @since      3.10.0
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adds few hooks and a bit of css / jss so markdown can be used with BuddyPress Doc
 */
final class BuddyPressDocs {


	/**
	 * The relative path to the plugin directory used for assets
	 *
	 * @since 3.10.0
	 * @access private
	 *
	 * @var string
	 */
	private $plugin_uri = '';


	/**
	 * Quick tiny check to avoid initializing the autoplug twice
	 */
	public function __construct() {
		if ( ! defined( 'MARKUP_MARKDOWN_BUDDYPRESSDOCS_PLUG' ) ) :
			$this->initialize();
		endif;
	}


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	private function initialize() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/buddypress-docs/bp-docs.php' ) && function_exists( 'bp_docs_init' ) ) :
			define( 'MARKUP_MARKDOWN_BUDDYPRESSDOCS_PLUG', true );
		else :
			define( 'MARKUP_MARKDOWN_BUDDYPRESSDOCS_PLUG', false );
		endif;
		if ( ! is_admin() ) :
			add_action( 'bp_enqueue_scripts', array( $this, 'load_edit_mmdform' ) );
		endif;
	}


	/**
	 * Check we are on a bbpress related template and trigger the launch of the markdown editor
	 *
	 * @since 3.10.0
	 * @access public
	 *
	 * @return boolean TRUE if the edit form view was triggered, FALSE otherwise
	 */
	public function load_edit_mmdform() {
		if ( ! function_exists( 'bp_docs_is_doc_create' ) || ! function_exists( 'bp_docs_is_doc_edit' ) ) :
			return false; // Just in case.
		endif;
		if ( ! is_admin() ) :
			$should_load = false;
			if ( bp_docs_is_doc_create() || bp_docs_is_doc_edit() ) :
				$should_load = true;
			endif;
			if ( ! $should_load ) :
				return false;
			endif;
			add_filter( 'markup_markdown_frontend_enabled', '__return_true' );
		endif;
		$this->plugin_uri = markup_markdown()->plugin_uri;
		add_action( 'markup_markdown_load_engine_stylesheets', array( $this, 'load_engine_stylesheets' ) );
		add_action( 'markup_markdown_load_engine_scripts', array( $this, 'load_engine_scripts' ) );
		return true;
	}


	/**
	 * Enqueue the BuddyPress Docs related stylesheet
	 *
	 * @return void
	 */
	public function load_engine_stylesheets() {
		wp_enqueue_style( 'markup_markdown__bp_editor', $this->plugin_uri . 'assets/buddypress-docs/css/field.min.css', array( 'markup_markdown__wordpress_richedit' ), buddypress()->version );
	}


	/**
	 * Enqueue the BuddyPress Docs related script
	 *
	 * @return void
	 */
	public function load_engine_scripts() {
		wp_enqueue_script( 'markup_markdown__bp_editor', $this->plugin_uri . 'assets/buddypress-docs/js/field.min.js', array( 'markup_markdown__wordpress_richedit' ), buddypress()->version, true );
	}
}
