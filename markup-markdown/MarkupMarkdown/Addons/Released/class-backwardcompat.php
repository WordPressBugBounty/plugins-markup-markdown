<?php
/**
 * Stable addon "Backward Compta Mod" to add layer compatibility with previous filters
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      4.0.0
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;

/**
 * Add compatibility wrapper for WordPress hooks with previous hooknames
 */
final class BackwardCompat {

	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'compatmod',
		'release' => 'stable',
		'active'  => 1,
	);


	/**
	 * Check the settings and initialize / exit if need be
	 */
	public function __construct() {
		$compat_mod = true;
		/** @disregard P1011 Optional constant from wp-config.php */
		if ( defined( 'DISABLE_WP_MARKUP_MARKDOWN' ) && DISABLE_WP_MARKUP_MARKDOWN ) :
			$compat_mod = false; // Specified explicitly in wp-config.php.
		elseif ( defined( 'MARKUP_MARKDOWN_ADDONS' ) ) :
			// Addon has been desactivated by the user.
			$compat_mod = in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) !== false ? true : false;
		elseif ( markup_markdown()->exists( markup_markdown()->conf_dir . '/compat_mod_off.txt' ) ) :
			$compat_mod = false; // Fresh install.
		endif;
		if ( ! $compat_mod ) :
			$this->prop['active'] = 0;
			$compat_mod           = false; // Addon inactive.
		endif;
		if ( $compat_mod ) :
			$this->constants_compat();
			$this->filters_compat();
			$this->actions_compat();
		endif;
	}


	/**
	 * Provides compatibility with previous constants
	 *
	 * @return void
	 */
	private function constants_compat() {
		if ( ! defined( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED' ) ) :
			define( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED', 1 );
		endif;
	}


	/**
	 * Provides compatibility with previous filters
	 *
	 * @return void
	 */
	private function filters_compat() {
		add_filter(
			'field_markdown2html',
			function ( $str ) {
				wp_trigger_error( 'Warning: The filter *field_markdown2html* will be removed in a future version of Markup Markdown. Please use *markup_markdown_field_mmd2html* instead.', 'field_markdown2html', E_USER_WARNING );
				return apply_filters( 'markup_markdown_field_mmd2html', $str );
			}
		);
		add_filter(
			'post_markdown2html',
			function ( $str ) {
				wp_trigger_error( 'Warning: The filter *post_markdown2html* will be removed in a future version of Markup Markdown. Please use *markup_markdown_post_mmd2html* instead.', 'post_markdown2html', E_USER_WARNING );
				return apply_filters( 'markup_markdown_post_mmd2html', $str );
			}
		);
		add_filter(
			'addon_markdown2html',
			function ( $str ) {
				wp_trigger_error( 'Warning: The filter *addon_markdown2html* will be removed in a future version of Markup Markdown. Please use *markup_markdown_addon_mmd2html* instead.', 'addon_markdown2html', E_USER_WARNING );
				return apply_filters( 'markup_markdown_addon_mmd2html', $str );
			}
		);
		add_filter(
			'mmd_verified_config',
			function ( $arr ) {
				wp_trigger_error( 'Warning: The filter *mmd_verified_config* will be removed in a future version of Markup Markdown. Please use *markup_markdown_verified_config* instead.', 'mmd_verified_config', E_USER_WARNING );
				return apply_filters( 'markup_markdown_verified_config', $arr );
			}
		);
		add_filter(
			'mmd_var2const',
			function ( $str ) {
				wp_trigger_error( 'Warning: The filter *mmd_var2const* will be removed in a future version of Markup Markdown. Please use *markup_markdown_var2const* instead.', 'mmd_var2const', E_USER_WARNING );
				return apply_filters( 'markup_markdown_var2const', $str );
			}
		);
		add_filter(
			'mmd_frontend_enabled',
			function ( $boolean ) {
				wp_trigger_error( 'Warning: The filter *mmd_frontend_enabled* will be removed in a future version of Markup Markdown. Please use *markup_markdown_frontend_enabled* instead.', 'mmd_frontend_enabled', E_USER_WARNING );
				return apply_filters( 'markup_markdown_frontend_enabled', $boolean );
			}
		);
		add_filter(
			'mmd_backend_enabled',
			function ( $boolean ) {
				wp_trigger_error( 'Warning: The filter *mmd_backend_enabled* will be removed in a future version of Markup Markdown. Please use *markup_markdown_backend_enabled* instead.', 'mmd_backend_enabled', E_USER_WARNING );
				return apply_filters( 'markup_markdown_backend_enabled', $boolean );
			}
		);
		add_filter(
			'mmd_localized_strings',
			function ( $arr ) {
				wp_trigger_error( 'Warning: The filter *mmd_localized_strings* will be removed in a future version of Markup Markdown. Please use *markup_markdown_localized_strings* instead.', 'mmd_localized_strings', E_USER_WARNING );
				return apply_filters( 'markup_markdown_localized_strings', $arr );
			}
		);
		add_filter(
			'mmd_toolbar_buttons',
			function ( $arr ) {
				wp_trigger_error( 'Warning: The filter *mmd_toolbar_buttons* will be removed in a future version of Markup Markdown. Please use *markup_markdown_toolbar_buttons* instead.', 'mmd_toolbar_buttons', E_USER_WARNING );
				return apply_filters( 'markup_markdown_toolbar_buttons', $arr );
			}
		);
		add_filter(
			'mmd_button_translations',
			function ( $arr ) {
				wp_trigger_error( 'Warning: The filter *mmd_button_translations* will be removed in a future version of Markup Markdown. Please use *markup_markdown_button_translations* instead.', 'mmd_button_translations', E_USER_WARNING );
				return apply_filters( 'markup_markdown_button_translations', $arr );
			}
		);
	}


	/**
	 * Provides compatibility with previous actions
	 *
	 * @return void
	 */
	private function actions_compat() {
		add_action(
			'mmd_load_engine_stylesheets',
			function ( $fnc ) {
				wp_trigger_error( 'Warning: The action *mmd_load_engine_stylesheets* will be removed in a future version of Markup Markdown. Please use *markup_markdown_load_engine_stylesheets* instead.', 'mmd_load_engine_stylesheets', E_USER_WARNING );
				return add_action( 'markup_markdown_load_engine_stylesheets', $fnc );
			}
		);
		add_action(
			'mmd_load_engine_scripts',
			function ( $fnc ) {
				wp_trigger_error( 'Warning: The action *mmd_load_engine_scripts* will be removed in a future version of Markup Markdown. Please use *markup_markdown_load_engine_scripts* instead.', 'mmd_load_engine_scripts', E_USER_WARNING );
				return add_action( 'markup_markdown_load_engine_scripts', $fnc );
			}
		);
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
			return esc_html__( 'Backward Compatibility', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Enable deprecated filters and actions started with the prefix mmd.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}
}
