<?php
/**
 * Stable addon "OP Cache" to use static files with opcache
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      1.3.1
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;


/**
 * The OPCache class only enables a settings to toggle on / off the feature
 */
final class OPCache {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'nopcache',
		'release' => 'stable',
		'active'  => 0,
	);


	/**
	 * Properly check if active or not
	 */
	public function __construct() {
		if ( defined( 'MARKUP_MARKDOWN_OPCACHE' ) ) :
			// Disable in wp-config.php or somewhere else.
			$this->prop['active'] = ! MARKUP_MARKDOWN_OPCACHE ? 1 : 0;
		elseif ( defined( 'MARKUP_MARKDOWN_ADDONS' ) ) :
			// Warning : disable by default so !== sign here.
			if ( false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) :
				define( 'MARKUP_MARKDOWN_OPCACHE', 0 );
				$this->prop['active'] = 1;
			else :
				define( 'MARKUP_MARKDOWN_OPCACHE', 0 );
				$this->prop['active'] = 0;
			endif;
		else :
			// Since 3.3.0 cache is desactivated by default to avoid side effects.
			define( 'MARKUP_MARKDOWN_OPCACHE', 0 );
			$this->prop['active'] = 1;
		endif;
		$this->prop['active'] ? false : true;
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
			return esc_html__( 'Disable Static Cache', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Static html files can be generated to speed up the rendering if the default PHP OPCache if available. Uncheck to enable.', 'markup-markdown' );
		endif;
		return 'markup_markdown_undefined';
	}
}


return apply_filters( 'markup_markdown_load_addon', 'nopcache', new \MarkupMarkdown\Addons\Released\OPCache() );
