<?php
/**
 * Stable addons media comments utilies
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Addons\Released\Media;

defined( 'ABSPATH' ) || exit;

/**
 * A simple utility to manage tags allowed for comments
 */
final class CommentsTags {


	/**
	 * List of tags by scopes
	 *
	 * @var array
	 */
	protected $prop = array(
		'default_tags'  => array(),
		'noscope_tags'  => array( 'address', 'area', 'article', 'audio', 'aside', 'bdo', 'big', 'button', 'caption', 'col', 'colgroup', 'dfn', 'del', 'details', 'dl', 'dt', 'dd', 'div', 'embed', 'fieldset', 'figure', 'figcaption', 'font', 'footer', 'header', 'hgroup', 'h1', 'h2', 'h3', 'h6', 'input', 'ins', 'kbd', 'legend', 'label', 'main', 'map', 'mark', 'menu', 'nav', 'object', 'pre', 'rb', 'rp', 'rt', 'rtc', 'ruby', 'small', 'samp', 'section', 'summary', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'textarea', 'title', 'tr', 'track', 'var', 'video' ),
		'noscope_attrs' => array( 'align', 'aria-controls', 'aria-current', 'aria-describedby', 'aria-details', 'aria-expanded', 'aria-hidden', 'aria-expanded', 'aria-label', 'aria-labelledby', 'aria-live', 'border', 'data-*', 'hidden', 'hspace', 'download', 'id', 'name', 'role', 'style', 'target', 'usemap', 'value', 'valign', 'vspace', 'xml:lang' ),
		'allowed_tags'  => array(),
	);


	/**
	 * Check a few variables before initializinn
	 *
	 * @param string  $json Absolute path to the static json file where the setup was saved.
	 * @param boolean $associative TRUE to retrive data as array, FALSE for the default object.
	 * @return void
	 */
	public function __construct( $json, $associative ) {
		if ( isset( $json ) && ! empty( $json ) ) :
			$this->initialize( $json, isset( $associative ) && is_bool( $associative ) ? $associative : false );
		endif;
	}


	/**
	 * Initialize the setup
	 *
	 * @param string  $json Absolute path to the static json file where the setup was saved.
	 * @param boolean $associative TRUE to retrieve data as array, FALSE for the default object.
	 * @return void
	 */
	public function initialize( $json, $associative ) {
		if ( ! markup_markdown()->exists( $json ) ) :
			$this->make_default_tags();
			markup_markdown()->put_contents( $json, wp_json_encode( $this->prop['default_tags'] ) );
			$this->prop['allowed_tags'] = $this->prop['default_tags'];
		endif;
		markup_markdown()->clear_cache( $json );
		$my_tags = markup_markdown()->json_decode( $json, $associative );
		if ( isset( $my_tags ) ) :
			$this->prop['allowed_tags'] = $my_tags;
		else :
			// Switch callback.
			$this->make_default_tags();
			$this->prop['allowed_tags'] = $this->prop['default_tags'];
		endif;
	}


	/**
	 * Create an array from WordPress data with a set of possible html tags used for comments
	 *
	 * @since 3.17.0
	 * @access public
	 *
	 * @return boolean FALSE if default tags list were already generated, TRUE otherwise
	 */
	public function make_default_tags() {
		if ( count( $this->prop['default_tags'] ) > 0 ) :
			return false;
		endif;
		$html_tags = wp_kses_allowed_html( 'post' );
		foreach ( $html_tags as $my_tag => $my_attrs ) :
			$my_tag = strtolower( $my_tag );
			if ( false !== in_array( $my_tag, $this->prop['noscope_tags'], true ) ) :
				continue;
			endif;
			$this->prop['default_tags'][ $my_tag ] = array( 'active' => 1 );
			foreach ( $my_attrs as $attr_name => $attr_value ) :
				$attr_name = strtolower( $attr_name );
				if ( in_array( $attr_name, $this->prop['noscope_attrs'], true ) && 'active' !== $attr_name ) :
					continue;
				endif;
				$this->prop['default_tags'][ $my_tag ][ $attr_name ] = 1;
			endforeach;
		endforeach;
		return true;
	}


	/**
	 * Magic method to retrieve the property for a set of tag
	 * Trigger creation of the json with default configuration if need be
	 *
	 * @param string $name The target scope.
	 * @return array|string The list of tags
	 */
	public function __get( $name = '' ) {
		if ( array_key_exists( $name, $this->prop ) ) :
			if ( 'default_tags' === $name && ! count( $this->prop['default_tags'] ) ) :
				$this->make_default_tags();
			endif;
			return $this->prop[ $name ];
		endif;
		return 'mmd_undefined';
	}
}
