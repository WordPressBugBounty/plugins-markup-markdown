<?php
/**
 * Autoplug "WPGeshi" to enable the geshi rendering with WP Geshi
 *
 * @category   Autoplugs
 * @package    MarkupMarkdown
 */

namespace MarkupMarkdown\AutoPlugs;

defined( 'ABSPATH' ) || exit;


/**
 * This class adjusts a few hooks and adds a few parsers to trigger the geshi parser properly
 */
class WPGeshi {


	/**
	 * The CSS inline code generated on the fly during the loading of the page
	 *
	 * @var string
	 */
	private $mmd_geshi_css_code = '';


	/**
	 * Quick tiny check to verify if we need to initialize the class
	 */
	public function __construct() {
		if ( markup_markdown()->exists( WP_PLUGIN_DIR . '/wp-geshi-highlight/wp-geshi-highlight.php' ) ) :
			define( 'MARKUP_MARKDOWN_WPGESHI_PLUG', true );
			add_action( 'after_setup_theme', array( $this, 'wp_geshi_plug' ) );
		endif;
	}


	/**
	 * Initialize the hooks
	 *
	 * @global string $wp_geshi_used_languages;
	 * @return void
	 */
	public function wp_geshi_plug() {
		// Just in case make sure one of the wp geshi core function is available before going further.
		if ( function_exists( 'wp_geshi_filter_replace_code' ) ) :
			// Avoir error if the global variable is being triggered / reset earlier in the loading process.
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			global $wp_geshi_used_languages;
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$wp_geshi_used_languages = array();
			add_filter( 'markup_markdown_addon_mmd2html', array( $this, 'trigger_wp_geshi' ), 11, 1 );
		endif;
		if ( function_exists( 'wp_geshi_add_css_to_head' ) ) :
			// We are gonna output the styles in the footer instead of the head.
			add_action( 'wp_footer', array( $this, 'load_wp_geshi_stylesheets' ) );
		endif;
	}


	/**
	 * Trigger the Geshi version included with WP Geshi
	 *
	 * @param string $content The HTML code to parse.
	 * @return string The modified HTML code
	 */
	public function trigger_wp_geshi( $content ) {
		// Replace <pre><code class="language-php">...</code></pre> by <pre lang="php">...</pre>.
		$pre_friendly = preg_replace(
			'#<pre><code class="lang-([a-z0-9]+).*?">#',
			'<pre lang="$1" escaped="true" line="1">',
			str_replace( '</code></pre>', '</pre>', $content )
		);
		// 1) Instead of filter_and_replace_code_snippets, we call directly filter_replace_code.
		// <pre lang="xxx">...</pre> will be replaced by a token <p>abc123</p>.
		$ges_friendly = wp_geshi_filter_replace_code( $pre_friendly );
		// If code blocks are found, $wp_geshi_codesnipmatch_arrays will be defined.
		global $wp_geshi_codesnipmatch_arrays;
		if ( ! isset( $wp_geshi_codesnipmatch_arrays ) || ! $wp_geshi_codesnipmatch_arrays || ! count( $wp_geshi_codesnipmatch_arrays ) ) :
			return $pre_friendly; // Otherwise nothing to do, just exiting.
		endif;
		// 2) Prepare the styles.
		// As the filter might be called multiple times, the inline style might be overriden / lost.
		wp_geshi_highlight_and_generate_css();
		global $wp_geshi_css_code;
		if ( isset( $wp_geshi_css_code ) && ! empty( $wp_geshi_css_code ) ) :
			$this->mmd_geshi_css_code .= $wp_geshi_css_code;
		endif;
		// 3) Parse and replace the token with the appropriate colored snippets.
		$html_friendly = wp_geshi_insert_highlighted_code_filter( $ges_friendly );
		return $html_friendly;
	}


	/**
	 * Append snippets styles to the HTML header or inside the static cache file
	 * Discalimer: When MMD cache is enabled, markdown filters are not applied
	 * so raw PRE tag with the wrong format won't trigger WP Geshi as well.
	 * The same styles won't be applied recursively
	 *
	 * @global string $wp_geshi_css_code
	 * @global \WP_Object $wp_styles
	 * @return boolean true in case of success or false
	 */
	public function load_wp_geshi_stylesheets() {
		if ( empty( $this->mmd_geshi_css_code ) ) :
			return false;
		endif;
		// Override the global inline style variable. Solved the undefined error.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		global $wp_geshi_css_code;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$wp_geshi_css_code = '';
		if ( ! is_singular() || ! defined( 'WP_MMD_OPCACHE' ) || ! WP_MMD_OPCACHE || ! function_exists( 'ob_start' ) ) :
			// OP Cache is disabled or output buffering not available, just trigger the styles generator.
			wp_geshi_add_css_to_head();
			wp_add_inline_style( 'wpgeshi-wp-geshi-highlight', $this->mmd_geshi_css_code );
		else :
			// OP Cache is enabled and required PHP module exists. We compile the css inside and push it to the static cache file.
			$geshi_inline_sheet = '<style id="wpgeshi-wp-geshi-highlight-inline-css" type="text/css">';
			wp_geshi_add_css_to_head();
			// There is only 1 global stylesheet at the moment but we use the global variable just in case.
			// At least any stylesheet with the 'wpgeshi' stylesheet will be grabbed.
			// For the first run we still need to ouput the content !
			global $wp_styles;
			if ( ! isset( $wp_styles ) || ! isset( $wp_styles->queue ) ) :
				return true;
			endif;
			foreach ( $wp_styles->queue as $handle ) :
				if ( strpos( $handle, 'wpgeshi' ) !== false ) :
					$geshi_inline_sheet .= esc_html( file_get_contents( WP_PLUGIN_DIR . '/wp-geshi-highlight/wp-geshi-highlight.css' ) );
				endif;
			endforeach;
			$geshi_inline_sheet .= esc_html( $this->mmd_geshi_css_code );
			$geshi_inline_sheet .= '</style>';
			echo "\n" . wp_kses(
				preg_replace( '#\n\s*\n#', "\n", preg_replace( '#/\*.*?\*/#s', '', $geshi_inline_sheet ) ),
				array(
					'style' => array(
						'id'    => true,
						'class' => true,
						'type'  => true,
					),
				)
			);
			$post_content = markup_markdown()->cache_blog_prefix . get_the_id() . '.html';
			if ( markup_markdown()->exists( $post_content ) ) :
				markup_markdown()->put_contents(
					$post_content,
					markup_markdown()->get_contents( $post_content ) . wp_kses(
						$geshi_inline_sheet,
						array(
							'style' => array(
								'id'    => true,
								'class' => true,
								'type'  => true,
							),
						)
					)
				);
			endif;
		endif;
		return true;
	}
}
