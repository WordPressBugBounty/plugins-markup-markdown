<?php
/**
 * Addons Class
 *
 * @category   Core
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Core;

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED' ) ) :
	return false; // The plugin was not properly activated.
endif;


/**
 * This class handles the loading of Markup Markdown's addons
 */
final class Addons {


	/**
	 * Addons instances with their relative names
	 *
	 * @var array
	 */
	private $prop = array(
		'setup' => array(),
		'inst'  => array(),
	);


	/**
	 * Addon folder
	 *
	 * @var string
	 */
	private $addon_dir = '';


	/**
	 * Everything has a start
	 */
	public function __construct() {
		$addon_conf = markup_markdown()->conf_blog_prefix . 'conf_screen.php';
		if ( markup_markdown()->exists( $addon_conf ) ) :
			require_once $addon_conf;
		endif;
		$this->load_addons();
	}


	/**
	 * Retrieve a property set of an addon
	 *
	 * @param string $name The slug of the addon.
	 * @return array The set with its instance.
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->prop ) ) {
			return $this->prop[ $name ];
		}
		return null;
	}


	/**
	 * Properly trigger the load of addons by order
	 *
	 * @return void
	 */
	private function load_addons() {
		// Load addons modules.
		$this->addon_dir = markup_markdown()->plugin_dir . 'MarkupMarkdown/Addons/';
		add_filter( 'markup_markdown_load_addon', array( $this, 'load_addon' ), 10, 2 );
		// Kind of stable addons for a daily use.
		$my_buffer = array();
		require_once $this->addon_dir . 'Released/class-backwardcompat.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'compatmod', new \MarkupMarkdown\Addons\Released\BackwardCompat() );
		require_once $this->addon_dir . 'Released/class-engineeasymde.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'engine__easymde', new \MarkupMarkdown\Addons\Released\EngineEasyMDE() );
		require_once $this->addon_dir . 'Released/class-opcache.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'nopcache', new \MarkupMarkdown\Addons\Released\OPCache() );
		require_once $this->addon_dir . 'Released/class-layout.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'layout', new \MarkupMarkdown\Addons\Released\Layout() );
		require_once $this->addon_dir . 'Released/Media/class-youtube.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'youtube', new \MarkupMarkdown\Addons\Released\Media\Youtube() );
		require_once $this->addon_dir . 'Released/Media/class-vimeo.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'vimeo', new \MarkupMarkdown\Addons\Released\Media\Vimeo() );
		require_once $this->addon_dir . 'Released/Media/class-image.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'Image', new \MarkupMarkdown\Addons\Released\Media\Image() );
		require_once $this->addon_dir . 'Released/class-codehighlighter.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'codehighlighter', new \MarkupMarkdown\Addons\Released\CodeHighlighter() );
		require_once $this->addon_dir . 'Released/class-comments.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'comments', new \MarkupMarkdown\Addons\Released\Comments() );
		require_once $this->addon_dir . 'Released/class-latex.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'latex', new \MarkupMarkdown\Addons\Released\Latex() );
		require_once $this->addon_dir . 'Released/class-mermaid.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'mermaid', new \MarkupMarkdown\Addons\Released\Mermaid() );
		// Kind of usable addons but I wouldn't bet for extensive use.
		require_once $this->addon_dir . 'Unsupported/class-spellchecker.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'hungspellchecker', new \MarkupMarkdown\Addons\Unsupported\SpellChecker() );
		require_once $this->addon_dir . 'Unsupported/class-advancedcustomfields.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'acf', new \MarkupMarkdown\Addons\Unsupported\AdvancedCustomFields() );
		
		// End.
		require_once $this->addon_dir . 'Released/class-debug.php';
		$my_buffer[] = apply_filters( 'markup_markdown_load_addon', 'debug', new \MarkupMarkdown\Addons\Released\Debug() );
		unset( $my_buffer );
	}


	/**
	 * Quick check and addon loader
	 *
	 * @param string $slug The slug of the target addon.
	 * @param object $instance The instance of the target addon.
	 * @return boolean TRUE in case of success, FALSE otherwise
	 */
	public function load_addon( $slug, $instance ) {
		if ( ! isset( $slug ) || ! is_string( $slug ) || empty( $slug ) ) :
			return false; // Wrong slug.
		elseif ( in_array( $slug, $this->prop['setup'], true ) !== false ) :
			return false; // Already loaded.
		endif;
		if ( in_array( $slug, array( 'engine__easymde', 'debug' ), true ) === false ) :
			$this->prop['setup'][] = $slug;
		endif;
		$this->prop['inst'][ $slug ] = $instance;
		return true;
	}
}
