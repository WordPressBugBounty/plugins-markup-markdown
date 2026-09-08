<?php
/**
 * Stable addon "EasyMDE" to enable the EasyMDE Markdown editor on the backend
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;


/**
 * Class to plug the EasyMDE editor to the WordPress edit screen
 */
final class EngineEasyMDE {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'     => 'engine__easymde',
		'release'  => 'stable',
		'hlengine' => 'prism',
		'active'   => 1,
	);


	/**
	 * Flag to remember if we are on a admin screen or not
	 *
	 * @var boolean
	 */
	public $is_admin = false;


	/**
	 * To know if markdown syntax was enabled - or not - on the frontend
	 *
	 * @since 3.3.0
	 * @access private
	 * @var boolean
	 */
	public $frontend_enabled = false;


	/**
	 * To know if markdown syntax was enabled - or not - on the backend
	 *
	 * @since 3.4.0
	 * @access private
	 * @var boolean
	 */
	public $backend_enabled = false;


	/**
	 * Check the settings and initialize, or do nothing
	 */
	public function __construct() {
		if ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false !== in_array( 'engine__summernote', MARKUP_MARKDOWN_ADDONS, true ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		else :
			$this->is_admin = is_admin() ? true : false;
			if ( $this->is_admin ) :
				// Hooks that run only in the backend.
				add_action( 'wp_ajax_mmduser-editoptions', array( $this, 'save_mmd_edit_options' ) );
				$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS );
				if ( 'edit' === $action ) :
					add_filter( 'screen_settings', array( $this, 'mmd_post_screen_options_settings' ), 9, 2 );
				endif;
				add_action( 'init', array( $this, 'prepare_editor_assets' ), 10000 );
			else :
				// Hooks that might be used on the frontend as well.
				// Use the same or higher priority than defined in Core/Support.php.
				add_action( 'wp_head', array( $this, 'prepare_editor_assets' ), 12 );
			endif;
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
		elseif ( 'label' === $this->prop ) :
			return esc_html__( 'EasyMde WYSIWYG', 'markup-markdown' );
		elseif ( 'desc' === $this->prop ) :
			return esc_html__( 'The default Markdown Editor.', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}


	/**
	 * Custom HTML inside the screen options panel
	 *
	 * @since 2.5.2
	 * @access public
	 */
	public function save_mmd_edit_options() {
		check_ajax_referer( 'mmdeditoptions', 'mmdeditoptionsnonce' );
		$user = wp_get_current_user();
		if ( ! $user ) {
			wp_die( -1 );
		}
		$user_options = filter_input_array(
			INPUT_POST,
			array(
				'options' => array(
					'filter' => FILTER_VALIDATE_INT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				),
			)
		);
		if ( in_array( null, $user_options, true ) ) :
			wp_die( -1 );
		endif;
		$is_sticky = $user_options['options']['mmd_sticky_toolbar'];
		update_user_meta( $user->ID, '_mmd_sticky_toolbar', $is_sticky );
		wp_die( 1 );
	}


	/**
	 * Custom HTML inside the screen options panel
	 *
	 * @since 2.5.2
	 * @access public
	 *
	 * @param String     $panel The HTML code for the current panel.
	 * @param \WP_Screen $screen The current screen settings objet.
	 * @return String The modified HTML code for the current panel
	 */
	public function mmd_post_screen_options_settings( $panel, $screen ) {
		$is_sticky      = get_user_meta( get_current_user_id(), '_mmd_sticky_toolbar', true );
		$sticky_options = array(
			'<fieldset class="mmd-easymde-prefs">',
			'<legend class="screen-mmd">Markup Markdown Options</legend>',
			'<label for="mmd_sticky_toolbar">',
			'<input class="sticky-toolbar-tog" name="mmd_sticky_toolbar" type="checkbox" id="mmd_sticky_toolbar" value="1"' . ( $is_sticky ? ' checked="checked"' : '' ) . '>',
			'Sticky Toolbar',
			'</label>',
			wp_nonce_field( 'mmdeditoptions', 'mmdeditoptionsnonce', false ), // Add a nonce.
			'</fieldset>',
		);
		return implode( '', $sticky_options );
	}


	/**
	 * Check and trigger the assets load if need be
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @return boolean TRUE if we need to load the assets or FALSE
	 */
	public function prepare_editor_assets() {
		if ( $this->is_admin ) : // Backend called earlier in the *init* hook or similar.
			// We don't have access to the edit screen property yet so the check will be made in the next hook.
			add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
		else : // Frontend: we are inside the *wp_head* hook.
			// Check if allowed and load straight the asset.
			$this->frontend_enabled = apply_filters( 'markup_markdown_frontend_enabled', false );
			if ( ! $this->frontend_enabled ) :
				return false;
			endif;
			$this->load_assets();
		endif;
		return true;
	}


	/**
	 * Load step by step the required assets
	 *
	 * @access public
	 * @since 3.0.0
	 *
	 * @param string $hook the current hook in use.
	 * @return boolean TRUE if assets were pushed to the queue, FALSE otherwise
	 */
	public function load_assets( $hook = 'unknown.php' ) {
		if ( $this->is_admin ) : // Backend.
			$this->backend_enabled = apply_filters( 'markup_markdown_backend_enabled', $hook, false );
			if ( ! $this->backend_enabled ) :
				// Not editing a post, do not load asset & exit.
				return false;
			endif;
		elseif ( ! $this->frontend_enabled ) :
			// Frontend and user is no logged or not possible to edit content.
			return false;
		endif;
		if ( ! defined( 'MARKUP_MARKDOWN_USE_CODEHIGHLIGHT' ) || ! is_array( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT ) || ! isset( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[1] ) || empty( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[1] ) ) :
			$this->prop['hlengine'] = 'prism';
		else :
			$this->prop['hlengine'] = MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[1];
		endif;
		// (1) Load the media related manager assets.
		$this->load_engine_media();
		// (2) Load the markdown editor related stylesheets.
		$this->load_engine_stylesheets();
		// (3) Conditional markdown editor scripts loading inside the footer after all plugins are loaded.
		add_action( $this->is_admin ? 'admin_footer' : 'wp_footer', array( $this, 'load_engine_scripts' ) );
		return true;
	}


	/**
	 * Queue the media manager related assets
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @return boolean TRUE if the WP Native media upload libraries are queued or FALSE if disabled
	 */
	public function load_engine_media() {
		if ( defined( 'MARKUP_MARKDOWN_MEDIA_UPLOADER' ) && ! MARKUP_MARKDOWN_MEDIA_UPLOADER ) :
			return false;
		endif;
		$args    = array();
		$post_id = function_exists( 'get_the_ID' ) ? get_the_ID() : 0;
		if ( (int) $post_id > 0 ) :
			$args['post'] = $post_id;
		endif;
		wp_enqueue_media( $args );
		wp_playlist_scripts( 'audio' );
		wp_playlist_scripts( 'video' );
		add_thickbox();
		return true;
	}


	/**
	 * Trigger the loading of the editor scripts if and only if we are
	 * on the edit screen of a post / page using the markdown version of wysiwyg
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function load_engine_stylesheets() {
		$plugin_uri = markup_markdown()->plugin_uri;
		wp_enqueue_style( 'markup_markdown__cssengine_editor', $plugin_uri . 'assets/easy-markdown-editor/dist/easymde.min.css', array(), '2.20.1001' );
		if ( 'prism' === $this->prop['hlengine'] ) :
			wp_enqueue_style( 'markup_markdown__highlight_theme', $plugin_uri . 'assets/prism/v1/themes/prism-vs.min.css', array( 'markup_markdown__cssengine_editor' ), '1.30.1001' );
		elseif ( 'highlight' === $this->prop['hlengine'] ) :
			wp_enqueue_style( 'markup_markdown__highlight_theme', $plugin_uri . 'assets/highlightjs/styles/hl-vs.min.css', array( 'markup_markdown__cssengine_editor' ), '11.11.1001' );
		endif;
		wp_enqueue_style( 'markup_markdown__wordpress_richedit', $plugin_uri . 'assets/markup-markdown/css/wordpress_richedit-easymde.min.css', array( 'markup_markdown__highlight_theme' ), '1.2.6' );
		do_action( 'markup_markdown_load_engine_stylesheets' );
	}


	/**
	 * Trigger the loading of the editor scripts
	 *
	 * @access public
	 * @since 3.3.0
	 *
	 * @return void
	 */
	public function load_engine_scripts() {
		$plugin_uri = markup_markdown()->plugin_uri;
		// Debug / Minified version introduced since 3.6.
		if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'MARKUP_MARKDOWN_SCRIPT_DEBUG' ) && MARKUP_MARKDOWN_SCRIPT_DEBUG ) ) :
			wp_enqueue_script( 'markup_markdown__jsengine_editor', $plugin_uri . 'assets/easy-markdown-editor/dist/easymde.debug.js', array(), '2.20.1001', true );
			if ( 'prism' === $this->prop['hlengine'] ) :
				wp_enqueue_script( 'markup_markdown__prism_core', $plugin_uri . 'assets/prism/v1/components/prism-core.js', array( 'markup_markdown__jsengine_editor' ), '1.30.1001', true );
				wp_enqueue_script( 'markup_markdown__prism_autoloader', $plugin_uri . 'assets/prism/v1/plugins/autoloader/prism-autoloader.js', array( 'markup_markdown__prism_core' ), '1.30.1001', true );
			elseif ( 'highlight' === $this->prop['hlengine'] ) :
				wp_enqueue_script( 'markup_markdown__hl_core', $plugin_uri . 'assets/highlightjs/highlight.js', array( 'markup_markdown__jsengine_editor' ), '11.11.1', true );
			endif;
			wp_enqueue_script( 'markup_markdown__waypoints', $plugin_uri . 'assets/jquery-waypoints/lib/jquery.waypoints.min.js', array( 'markup_markdown__jsengine_editor' ), '4.0.1', true );
			wp_enqueue_script( 'markup_markdown__sticky', $plugin_uri . 'assets/jquery-waypoints/lib/shortcuts/sticky.min.js', array( 'markup_markdown__waypoints' ), '4.0.1', true );
			wp_enqueue_script( 'markup_markdown__codemirror_spellchecker', $plugin_uri . 'assets/custom-codemirror-spell-checker/dist/spell-checker.debug.js', array( 'markup_markdown__sticky' ), '1.1.25', true );
			wp_enqueue_script( 'markup_markdown__wordpress_spellchecker', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-spellchecker.debug.js', array( 'markup_markdown__codemirror_spellchecker' ), '1.0.3', true );
			wp_enqueue_script( 'markup_markdown__wordpress_preview', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-preview.debug.js', array( 'markup_markdown__wordpress_spellchecker' ), '1.1.4', true );
			wp_enqueue_script( 'markup_markdown__wordpress_media', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-media.debug.js', array( 'markup_markdown__wordpress_preview' ), '1.0.29', true );
			wp_enqueue_script( 'markup_markdown__wordpress_richedit', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-easymde.debug.js', array( 'markup_markdown__wordpress_media' ), '1.6.7', true );
		elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) :
			wp_enqueue_script( 'markup_markdown__jsengine_editor', $plugin_uri . 'assets/easy-markdown-editor/dist/easymde.min.js', array(), '2.20.1001', true );
			if ( 'prism' === $this->prop['hlengine'] ) :
				wp_enqueue_script( 'markup_markdown__prism_core', $plugin_uri . 'assets/prism/v1/components/prism-core.min.js', array( 'markup_markdown__jsengine_editor' ), '1.30.0', true );
				wp_enqueue_script( 'markup_markdown__prism_autoloader', $plugin_uri . 'assets/prism/v1/plugins/autoloader/prism-autoloader.min.js', array( 'markup_markdown__prism_core' ), '1.30.1001', true );
			elseif ( 'highlight' === $this->prop['hlengine'] ) :
				wp_enqueue_script( 'markup_markdown__hl_core', $plugin_uri . 'assets/highlightjs/highlight.min.js', array( 'markup_markdown__jsengine_editor' ), '11.11.1', true );
			endif;
			wp_enqueue_script( 'markup_markdown__waypoints', $plugin_uri . 'assets/jquery-waypoints/lib/jquery.waypoints.min.js', array( 'markup_markdown__jsengine_editor' ), '4.0.1', true );
			wp_enqueue_script( 'markup_markdown__sticky', $plugin_uri . 'assets/jquery-waypoints/lib/shortcuts/sticky.min.js', array( 'markup_markdown__waypoints' ), '4.0.1', true );
			wp_enqueue_script( 'markup_markdown__codemirror_spellchecker', $plugin_uri . 'assets/custom-codemirror-spell-checker/dist/spell-checker.min.js', array( 'markup_markdown__sticky' ), '1.1.25', true );
			wp_enqueue_script( 'markup_markdown__wordpress_spellchecker', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-spellchecker.min.js', array( 'markup_markdown__codemirror_spellchecker' ), '1.0.3', true );
			wp_enqueue_script( 'markup_markdown__wordpress_preview', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-preview.min.js', array( 'markup_markdown__wordpress_spellchecker' ), '1.1.4', true );
			wp_enqueue_script( 'markup_markdown__wordpress_media', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-media.min.js', array( 'markup_markdown__wordpress_preview' ), '1.0.29', true );
			wp_enqueue_script( 'markup_markdown__wordpress_richedit', $plugin_uri . 'assets/markup-markdown/js/wordpress_richedit-easymde.min.js', array( 'markup_markdown__wordpress_media' ), '1.6.7', true );
		elseif ( 'prism' === $this->prop['hlengine'] ) :
			wp_enqueue_script( 'markup_markdown__wordpress_richedit', $plugin_uri . 'assets/markup-markdown/js/builder_prism.min.js', array(), '1.2.0', true );
		elseif ( 'highlight' === $this->prop['hlengine'] ) :
			wp_enqueue_script( 'markup_markdown__wordpress_richedit', $plugin_uri . 'assets/markup-markdown/js/builder_hl.min.js', array(), '1.2.0', true );
		endif;
		$local_str = array(
			'mmd_pipe'            => esc_html__( 'Pipe', 'markup-markdown' ),
			'mmd_bold'            => esc_html__( 'Bold', 'markup-markdown' ),
			'mmd_italic'          => esc_html__( 'Italic', 'markup-markdown' ),
			'mmd_strikethrough'   => esc_html__( 'Strikethrough', 'markup-markdown' ),
			'mmd_heading'         => esc_html__( 'Heading', 'markup-markdown' ),
			'mmd_heading-smaller' => esc_html__( 'Smaller Heading', 'markup-markdown' ),
			'mmd_heading-bigger'  => esc_html__( 'Bigger Heading', 'markup-markdown' ),
			'mmd_heading-1'       => esc_html__( 'Big Heading', 'markup-markdown' ),
			'mmd_heading-2'       => esc_html__( 'Medium Heading', 'markup-markdown' ),
			'mmd_heading-3'       => esc_html__( 'Small Heading', 'markup-markdown' ),
			'mmd_code'            => esc_html__( 'Code', 'markup-markdown' ),
			'mmd_quote'           => esc_html__( 'Quote', 'markup-markdown' ),
			'mmd_unordered-list'  => esc_html__( 'Generic List', 'markup-markdown' ),
			'mmd_ordered-list'    => esc_html__( 'Numbered List', 'markup-markdown' ),
			'mmd_clean-block'     => esc_html__( 'Clean block', 'markup-markdown' ),
			'mmd_link'            => esc_html__( 'Create Link', 'markup-markdown' ),
			'mmd_wpsimage'        => esc_html__( 'Insert or Upload Media', 'markup-markdown' ),
			'mmd_table'           => esc_html__( 'Insert Table', 'markup-markdown' ),
			'mmd_horizontal-rule' => esc_html__( 'Insert Horizontal Line', 'markup-markdown' ),
			'mmd_preview'         => esc_html__( 'Toggle Preview', 'markup-markdown' ),
			'mmd_side-by-side'    => esc_html__( 'Toggle Side by Side', 'markup-markdown' ),
			'mmd_fullscreen'      => esc_html__( 'Toggle Fullscreen', 'markup-markdown' ),
			'mmd_guide'           => esc_html__( 'Markdown Guide', 'markup-markdown' ),
			'mmd_undo'            => esc_html__( 'Undo', 'markup-markdown' ),
			'mmd_redo'            => esc_html__( 'Redo', 'markup-markdown' ),
			'mmd_spell-check'     => esc_html__( 'Spellchecker', 'markup-markdown' ),
		);
		$ext_str   = apply_filters( 'markup_markdown_localized_strings', array() );
		if ( isset( $ext_str ) && is_array( $ext_str ) && count( $ext_str ) > 0 ) :
			$local_str = array_merge( $local_str, $ext_str );
		endif;
		wp_localize_script( 'markup_markdown__wordpress_richedit', 'mmd_wpr_vars', $local_str );
		wp_add_inline_script( 'markup_markdown__wordpress_richedit', $this->add_inline_editor_conf() );
		do_action( 'markup_markdown_load_engine_scripts' );
	}


	/**
	 * Method to add inline JavaScript setup variable to the admin edit screen
	 *
	 * @access public
	 * @since 3.0.0
	 *
	 * @return string inline easymde configuration tool
	 */
	public function add_inline_editor_conf() {
		$home_url = get_home_url() . '/';
		$js       = "window.wp = window.wp || {};\n"; // Just in case.
		$js      .= "wp.pluginMarkupMarkdown = wp.pluginMarkupMarkdown || {};\n";
		$js      .= 'wp.pluginMarkupMarkdown.homeURL = "' . $home_url . "\";\n";
		$json     = markup_markdown()->conf_blog_prefix . 'conf_easymde_toolbar.json';
		if ( ! markup_markdown()->exists( $json ) ) :
			$toolbar_setup = markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Media/class-toolbareasymde.php';
			if ( markup_markdown()->exists( $toolbar_setup ) ) :
				require_once $toolbar_setup; // Dummy init to generate the json file.
				new \MarkupMarkdown\Addons\Released\Media\ToolbarEasyMDE( $json );
			endif;
		endif;
		$toolbar_buttons = json_decode( preg_replace( '#[^a-z0-9-_\,\:"\{\}\[\]]#', '', markup_markdown()->get_contents( $json ) ), false );
		$js             .= 'wp.pluginMarkupMarkdown.primaryArea = ' . ( defined( 'MARKUP_MARKDOWN_SUPPORT_ENABLED' ) && MARKUP_MARKDOWN_SUPPORT_ENABLED ? '1' : '0' ) . ";\n";
		$js             .= 'wp.pluginMarkupMarkdown.toolbarButtons = [ "' . implode( '","', str_replace( '_', '-', $toolbar_buttons->my_buttons ) ) . "\" ];\n";
		if ( defined( 'MARKUP_MARKDOWN_MEDIA_UPLOADER' ) && ! MARKUP_MARKDOWN_MEDIA_UPLOADER ) :
			$js .= "wp.pluginMarkupMarkdown.mediaUploader = 0;\n";
		endif;
		if ( defined( 'MARKUP_MARKDOWN_USE_HEADINGS' ) && is_array( MARKUP_MARKDOWN_USE_HEADINGS ) && count( MARKUP_MARKDOWN_USE_HEADINGS ) > 1 && count( MARKUP_MARKDOWN_USE_HEADINGS ) < 6 ) :
			$js .= 'wp.pluginMarkupMarkdown.headingLevels = [ ' . implode( ', ', MARKUP_MARKDOWN_USE_HEADINGS ) . " ];\n";
		endif;
		if ( defined( 'MARKUP_MARKDOWN_USE_INDENT' ) && is_array( MARKUP_MARKDOWN_USE_INDENT ) && count( MARKUP_MARKDOWN_USE_INDENT ) === 2 ) :
			$js .= "wp.pluginMarkupMarkdown.indentStyles = [ '" . htmlspecialchars( MARKUP_MARKDOWN_USE_INDENT[0] ) . "', " . (int) MARKUP_MARKDOWN_USE_INDENT[1] . " ];\n";
		endif;
		if ( 'prism' === $this->prop['hlengine'] ) :
			$js .= "Prism.plugins.autoloader.languages_path = '" . markup_markdown()->plugin_uri . "/assets/prism/v1/components/';\n";
		endif;
		return $js;
	}
}
