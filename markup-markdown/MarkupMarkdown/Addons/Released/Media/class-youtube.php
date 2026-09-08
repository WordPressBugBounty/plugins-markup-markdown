<?php
/**
 * Stable addons Youtube media utilities
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

require_once markup_markdown()->plugin_dir . '/MarkupMarkdown/Abstracts/class-oembedtinyapi.php';

/**
 * A simple utility to convert Youtube links to embed
 */
final class Youtube extends \MarkupMarkdown\Abstracts\OEmbedTinyAPI {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'youtube',
		'release' => 'stable',
		'active'  => 1,
	);


	/**
	 * Simple check to verify if the addon is active
	 */
	public function __construct() {
		if ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		else :
			add_filter( 'markup_markdown_addon_mmd2html', array( $this, 'youtube2html' ) );
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
			return esc_html__( 'Youtube', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Convert automatically Youtube links to an embedded iframe.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}


	/**
	 * Method to parse Youtube links and output the related iframes
	 * Previously in core from 1.6.0 until refactoring in v2
	 *
	 * @access public
	 * @since 2.0.0
	 *
	 * @param String $content The html to be parsed.
	 * @return String The html with Youtube iframes embed code.
	 */
	public function youtube2html( $content = '' ) {
		return $this->oembed_service(
			array(
				'content'  => $content,
				'provider' => 'youtube',
				'endpoint' => 'https://www.youtube.com/oembed',
				'regexp'   => '#[^"a-zA-Z\/\/:\.]{1}[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)#u',
			)
		);
	}
}
