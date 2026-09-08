<?php
/**
 * Stable addon "Comments" to allow the use of markdown inside comments
 *
 * @category   Addons
 * @package    MarkupMarkdown
 * @since      3.17.0
 */

namespace MarkupMarkdown\Addons\Released;

defined( 'ABSPATH' ) || exit;
if ( defined( 'MARKUP_MARKDOWN_ADDONS_LOADED' ) ) :
	return false;
endif;

/**
 * This class allow the user to enable markdown within comments and to define which tags can be used
 */
final class Comments {


	/**
	 * Addon properties. Active by default
	 *
	 * @var array
	 */
	private $prop = array(
		'slug'    => 'comments',
		'release' => 'stable',
		'active'  => 0,
	);


	/**
	 * Path to the json config file
	 *
	 * @var string
	 */
	private $comments_tags_conf = '';


	/**
	 * List of HTML tags allowed
	 *
	 * @var array
	 */
	private $allowed_html = array();


	/**
	 * Check the settings and initialize / exit if need be
	 */
	public function __construct() {
		$this->comments_tags_conf = markup_markdown()->conf_blog_prefix . 'conf_comments_tags.json';
		if ( ! defined( 'MARKUP_MARKDOWN_ADDONS' ) || ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && false === in_array( $this->prop['slug'], MARKUP_MARKDOWN_ADDONS, true ) ) ) :
			$this->prop['active'] = 0; // Addon has been desactivated.
		elseif ( is_admin() ) :
			add_filter( 'markup_markdown_verified_config', array( $this, 'update_config' ) );
			add_filter( 'markup_markdown_var2const', array( $this, 'create_json' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_layout_assets' ) );
		else :
			add_filter( 'comment_text', array( $this, 'mmd_comments_text' ), 11, 2 );
		endif;
	}


	/**
	 * Filter to parse code highlighter options from the options screen when the form was submitted
	 *
	 * @since 3.17.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The modified data
	 */
	public function update_config( $my_cnf ) {
		$comment_tag_names = filter_input( INPUT_POST, 'comment_tag', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
		if ( ! isset( $comment_tag_names ) || ! is_array( $comment_tag_names ) ) :
			return $my_cnf;
		endif;
		$comment_tags = array();
		foreach ( $comment_tag_names as $tag_name => $tag_val ) :
			$comment_tag_attrs           = filter_input( INPUT_POST, 'comment_tag_' . $tag_name . '_attr', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );
			$comment_tag_attrs['active'] = 1;
			$comment_tags[ $tag_name ]   = $comment_tag_attrs;
		endforeach;
		$my_cnf['comment_tags'] = $comment_tags;
		unset( $comment_tags );
		return $my_cnf;
	}


	/**
	 * Generate PHP constants for the primary keys so we can access them quickly anywhere
	 *
	 * @since 3.19.0
	 * @access public
	 *
	 * @param  array $my_cnf The form fields associative array.
	 * @return array $my_cnf The associate array with the final constants to be created
	 */
	public function create_json( $my_cnf ) {
		if ( isset( $my_cnf['comment_tags'] ) && is_array( $my_cnf['comment_tags'] ) && count( $my_cnf['comment_tags'] ) > 0 ) :
			markup_markdown()->put_contents( $this->comments_tags_conf, wp_json_encode( $my_cnf['comment_tags'] ) );
		endif;
		unset( $my_cnf['comment_tags'] );
		return $my_cnf; // Required.
	}


	/**
	 * Check the hook being triggered on the admin screen to add the options form to the current screen if need be
	 *
	 * @param string $hook The WordPress hook name being triggered.
	 * @return void
	 */
	public function load_layout_assets( $hook ) {
		if ( 'settings_page_markup-markdown-admin' === $hook ) :
			add_action( 'markup_markdown_tabmenu_options', array( $this, 'add_tabmenu' ) );
			add_action( 'markup_markdown_tabcontent_options', array( $this, 'add_tabcontent' ) );
		endif;
	}


	/**
	 * Add the layout menu item inside the options screen
	 *
	 * @since 3.17.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabmenu() {
		echo "\t\t\t\t\t\t<li><a href=\"#tab-comments\" class=\"mmd-ico ico-dialog\">" . esc_html__( 'Comments', 'markup-markdown' ) . "</a></li>\n";
	}


	/**
	 * Display layout options inside the options screen
	 *
	 * @since 3.17.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_tabcontent() {
		$conf_file = markup_markdown()->conf_blog_prefix . 'conf.php';
		if ( markup_markdown()->exists( $conf_file ) ) :
			require_once $conf_file;
		endif;
		$my_tmpl = markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Templates/tmpl-commentsform.php';
		if ( markup_markdown()->exists( $my_tmpl ) ) :
			$markup_markdown_comments_tags_conf = $this->comments_tags_conf;
			markup_markdown()->clear_cache( $my_tmpl );
			include $my_tmpl;
		endif;
	}


	/**
	 * Parse the comment content's markdown and filter the HTML output
	 *
	 * @since 3.17.0
	 * @access public
	 *
	 * @param string           $text The text comment to be parsed for markdown.
	 * @param \WP_Comment|null $comment The WP comment object.
	 * @return string The sanitized HTML code
	 */
	public function mmd_comments_text( $text = '', $comment = null ) {
		if ( ! isset( $comment ) || ! is_object( $comment ) || ! isset( $comment->comment_content ) ) :
			return $text;
		endif;
		$comment_body = apply_filters( 'markup_markdown_field_mmd2html', $comment->comment_content );
		if ( ! count( $this->allowed_html ) ) :
			require_once markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Media/class-commentstags.php';
			$my_toolbar         = new \MarkupMarkdown\Addons\Released\Media\CommentsTags( $this->comments_tags_conf, true );
			$this->allowed_html = $my_toolbar->allowed_tags;
			unset( $my_toolbar );
		endif;
		$santized_content = wp_kses( $comment_body, $this->allowed_html );
		return $santized_content;
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
			return esc_html__( 'Comments', 'markup-markdown' );
		elseif ( 'desc' === $name ) :
			return esc_html__( 'Use markdown inside your comments', 'markup-markdown' );
		endif;
		return 'mmd_undefined';
	}
}
