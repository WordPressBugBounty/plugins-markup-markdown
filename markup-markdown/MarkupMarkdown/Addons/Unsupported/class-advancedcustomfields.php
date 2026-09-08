<?php
/**
 * Unstable addon "AdvancedCustomField" to add the markdown editor to spell checking
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      1.7.1
 */

namespace MarkupMarkdown\Addons\Unsupported;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;


/**
 * Registration logic for the new ACF field type.
 */
final class AdvancedCustomFields {



	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'acf',
		'release' => 'beta',
		'active'  => 1,
	);


	/**
	 * Check if active and initialize
	 */
	public function __construct() {
		if ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		else :
			$this->initialize();
		endif;
	}


	/**
	 * Properly initialize the addon
	 *
	 * @return void
	 */
	private function initialize() {
		add_action( 'init', array( $this, 'markup_markdown_include_acf_field_markdown' ) );
		if ( ! is_admin() ) :
			add_action( 'wp', array( $this, 'markup_markdown_frontend_filters' ) );
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
			return esc_html__( 'Advanced Custom Fields', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'This addon enable a new content type so you can write directly markdown with the "Markup Markdown" custom field from ACF.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}


	/**
	 * Register the markdown custom post field with the plugin Advanced Custom Field
	 *
	 * @return boolean TRUE in case of success, FALSE otherwise
	 */
	public function markup_markdown_include_acf_field_markdown() {
		if ( ! function_exists( 'acf_register_field_type' ) ) :
			return false;
		endif;
		require_once markup_markdown()->plugin_dir . 'MarkupMarkdown/Addons/Unsupported/AdvancedCustomFields/class-markupmarkdownacffield.php';
		acf_register_field_type( 'MarkupMarkdownAcfField' );
		add_filter(
			'acf/post_type/available_supports',
			function ( $acf_available_supports, $acf_post_type ) {
				$acf_available_supports['markup-markdown'] = 'Markup Markdown';
				return $acf_available_supports;
			},
			10,
			2
		);
		return true;
	}


	/**
	 * Allow markdown use on the frontend :
	 * + Filter to grant the markdown editor to be loaded on the frontend
	 * + Filter top disable TinyMCE on the frontend if need be, we switch the field type from "wysiwyg" to "textarea"
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @return boolean TRUE if backend related or FALSE if frontend related
	 */
	public function markup_markdown_frontend_filters() {
		// Action triggered by acf_form_head().
		add_action(
			'acf/input/admin_head',
			function () {
				add_filter( 'markup_markdown_frontend_enabled', '__return_true' );
			}
		);
		// Action triggered by acf_form().
		add_filter(
			'acf/get_valid_field',
			function ( $field ) {
				if ( false !== strpos( $field['name'], 'post_content' ) && 'wysiwyg' === $field['type'] ) :
					if ( defined( 'MARKUP_MARKDOWN_SUPPORT_ENABLED' ) && MARKUP_MARKDOWN_SUPPORT_ENABLED ) :
						$field['type']         = 'textarea';
						$field['toolbar']      = 0;
						$field['media_upload'] = 0;
						$field['rows']         = 15;
						$field['maxlength']    = 524524;
					endif;
				endif;
				return $field;
			}
		);
		return true;
	}
}
