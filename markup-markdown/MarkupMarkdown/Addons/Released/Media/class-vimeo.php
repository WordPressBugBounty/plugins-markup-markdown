<?php
/**
 * Stable addons Vimeo media utilities
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
 * A simple utility to convert Vimeo links to embed
 */
final class Vimeo extends \MarkupMarkdown\Abstracts\OEmbedTinyAPI {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'vimeo',
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
			add_filter( 'markup_markdown_addon_mmd2html', array( $this, 'vimeo2html' ) );
		endif;
	}


	/**
	 * Magic method to retrieve a property
	 *
	 * @param string $name The property key to retrieve
	 *
	 * @return string The property value
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->prop ) ) :
			return $this->prop[ $name ];
		elseif ( 'label' === $name ) :
			return esc_html__( 'Vimeo', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Convert automatically Vimeo links to an embedded iframe.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}


	/**
	 * Method to parse Vimeo links and output the related iframes
	 *
	 * @access public
	 * @since 1.5.3
	 *
	 * @param string $content the html to be parsed
	 * @return string html with Vimeo iframes embed code
	 */
	public function vimeo2html( $content = '' ) {
		return $this->oembed_service(
			array(
				'content'  => $content,
				'provider' => 'vimeo',
				'endpoint' => 'http://vimeo.com/api/oembed.json',
				'regexp'   => '#([^"a-zA-Z\/\/:\.]{1}|\n)[a-zA-Z\/\/:\.]*vimeo.com/[^\"\n\<]+#u',
			)
		);
	}
}
