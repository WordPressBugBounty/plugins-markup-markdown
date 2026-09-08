<?php
/**
 * Markup Markdown
 *
 * @package           MarkupMarkdown
 * @author            Pierre-Henri Lavigne
 * @license           GPLv3 or later
 *
 * @wordpress-plugin
 * Plugin Name: Markup Markdown
 * Plugin URI:  https://www.markup-markdown.com
 * Description: Replaces the Gutenberg Block Editor in favor of pure markdown based markups
 * Version:     4.0.2
 * Author:      Pierre-Henri Lavigne
 * Author URI:  https://www.markup-markdown.com
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html#license-text
 * Text Domain: markup-markdown
 * Domain Path: /languages
 * Requires at least: 6.6
 * Tested up to: 7.1
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 3, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

defined( 'ABSPATH' ) || exit;
define( 'MARKUP_MARKDOWN_FILE_URL', __FILE__ );


if ( ! class_exists( 'Markup_Markdown' ) ) :

	/**
	 * The root core class, everything begins here.
	 *
	 * @class Markup_Markdown
	 */
	class Markup_Markdown {


		/**
		 * The property that will held the PHP markdown parser
		 *
		 * @var object
		 */
		protected $parser;


		/**
		 * The basic setup
		 *
		 * @var array<string, array>
		 */
		protected $settings = array(
			'version'      => '4.0.2',
			'plugin_uri'   => '', // The http url used to access the plugin assets.
			'plugin_dir'   => '', // The full path to the plugin directory.
			'plugin_slug'  => '', // The slug used inside the WordPress plugin directory.
			'cache_dir'    => '', // The full path to the cache directory.
			'conf_dir'     => '', // The full path where configuration files are stored.
			'curr_blog'    => '1_1', // Default active blog configuration.
			'default_conf' => array(), // Default setup variables.
		);


		/**
		 * Flag to check if the file system helpers were loaded
		 *
		 * @var integer
		 */
		protected $filesystem_ready = 0;


		/**
		 * Class to perform various actions with static files
		 *
		 * @var object
		 */
		protected $filesystem;


		/**
		 * Everything starts here
		 */
		public function __construct() {
			$curr_blog                           = get_current_network_id() . '_' . get_current_blog_id();
			$this->settings['plugin_slug']       = plugin_basename( __DIR__ );
			$this->settings['plugin_uri']        = plugin_dir_url( __FILE__ );
			$this->settings['plugin_dir']        = plugin_dir_path( __FILE__ );
			$this->settings['cache_dir']         = WP_CONTENT_DIR . '/mmd-cache';
			$this->settings['conf_dir']          = WP_CONTENT_DIR . '/mmd-conf';
			$this->settings['curr_blog']         = $curr_blog;
			$this->settings['cache_blog_prefix'] = WP_CONTENT_DIR . '/mmd-cache/.posts/' . $curr_blog . '_';
			$this->settings['conf_blog_prefix']  = WP_CONTENT_DIR . '/mmd-conf/' . $curr_blog . '_';
			require_once $this->settings['plugin_dir'] . 'MarkupMarkdown/Core/class-activation.php';
		}


		/**
		 * Overloading method __get
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $name The name of the key in the $settings variable to retrieve.
		 * @return mixed The value of the related key in $settings or an empty string.
		 */
		public function __get( $name ) {
			return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : '';
		}


		/**
		 * Overloading method __set
		 *
		 * @since 2.0.0
		 * @access public
		 *
		 * @param string $name The name of the key in the $settings variable to overwrite.
		 * @param mixed  $val The new value of the related key in the $settings variable.
		 * @return void
		 */
		public function __set( $name, $val ) {
			if ( isset( $this->settings[ $name ] ) && is_array( $this->settings[ $name ] ) && is_array( $val ) ) :
				$this->settings[ $name ] = array_merge( $this->settings[ $name ], $val );
			else :
				$fixed = array( 'plugin_uri', 'plugin_dir', 'plugin_slug', 'cache_dir', 'conf_dir', 'curr_blog', 'default_conf', 'cache_blog_prefix', 'conf_blog_prefix' );
				if ( false === in_array( $name, $fixed, true ) ) :
					$this->settings[ $name ] = $val;
				endif;
			endif;
		}


		/**
		 * Global method that will be used to convert markdown string on the fly
		 *
		 *  @since 1.0
		 *  @access public
		 *
		 *  @param string $content The markdown code.
		 *
		 *  @return string The HTML content
		 */
		final public function markdown2html( $content ) {
			$filtered = apply_filters( 'markup_markdown_field_mmd2html', $content );
			$html     = htmlspecialchars_decode( $filtered, ENT_COMPAT );
			return do_shortcode( $html );
		}


		/**
		 * Global method that can be access to clear OP Cache file
		 *
		 *  @since 3.0
		 *  @access public
		 *
		 *  @param string $file String Target file.
		 *
		 *  @return void
		 */
		final public function clear_cache( $file = '' ) {
			if ( function_exists( 'wp_opcache_invalidate' ) ) :
				wp_opcache_invalidate( $file );
			elseif ( function_exists( 'opcache_invalidate' ) ) :
				opcache_invalidate( $file );
			endif;
		}


		/**
		 * Tiny function to filter user permissions
		 *
		 * @since 3.3.0
		 * @access public
		 *
		 * @param integer $user_id The WordPress user ID to check.
		 *
		 * @return boolean TRUE if granted or FALSE
		 */
		final public function user_allowed( $user_id = 0 ) {
			if ( ! $user_id ) :
				$user_id = get_current_user_id();
			endif;
			if ( ! $user_id ) :
				// Disable *Guest* users.
				return false;
			endif;
			$user = new \WP_User( $user_id );
			if ( $user && ! $user->has_cap( 'edit_posts' ) ) :
				// Disable *Subscribers* or users without edit permissions.
				return false;
			endif;
			return true;
		}


		/**
		 * Tiny utility to decode json in a custom way
		 *
		 * @since 3.17.0
		 * @access public
		 *
		 * @param string  $file Absolute path with file name.
		 * @param boolean $associative To return the data an associative array, object otherwise.
		 *
		 * @return array|object The JSON decoded data
		 */
		final public function json_decode( $file, $associative ) {
			if ( ! isset( $file ) || empty( $file ) ) :
				return false;
			endif;
			$my_data = $this->filesystem->get_contents( $file );
			if ( ! isset( $my_data ) || empty( $my_data ) ) :
				return false;
			endif;
			if ( substr( $my_data, 0, 3 ) === "\xEF\xBB\xBF" ) :
				$my_data = substr( $my_data, 3 );
			endif;
			return json_decode( $my_data, ! isset( $associative ) ? false : $associative );
		}


		/**
		 * Initialize an instance of the WordPress file system utility
		 * that can be used to perform various operations with files
		 *
		 * @since 3.19
		 * @access private
		 *
		 * @return boolean TRUE if $wp_filesystem can be used or false
		 */
		private function check_filesystem() {
			if ( ! $this->filesystem_ready ) :
				if ( ! function_exists( 'wp_filesystem' ) ) :
					require_once ABSPATH . 'wp-admin/includes/file.php';
				endif;
				$this->filesystem_ready = WP_Filesystem() ? 1 : -1;
				if ( $this->filesystem_ready > 0 ) :
					global $wp_filesystem;
					$this->filesystem = clone $wp_filesystem;
				else :
					$this->file_system_ready = -1; // Silent failed.
				endif;
			endif;
			return $this->filesystem_ready > 0 ? true : false;
		}


		/**
		 * Helper to check if a file already exists
		 *
		 * @since 3.19
		 * @access public
		 *
		 * @param string $item Target file or directory.
		 *
		 * @return boolean TRUE in case of success, FALSE otherwise
		 */
		final public function exists( $item ) {
			if ( ! $this->check_filesystem() || ! isset( $item ) || empty( $item ) ) :
				return false;
			endif;
			return $this->filesystem->exists( $item );
		}


		/**
		 * Helper to retrieve the raw content of a file
		 *
		 * @since 3.19
		 * @access private
		 *
		 * @param string $file Name of the file to read.
		 *
		 * @return string|FALSE Content of the file as a string on success, FALSE otherwise
		 */
		final public function get_contents( $file ) {
			if ( ! $this->check_filesystem() || ! isset( $file ) || empty( $file ) ) :
				return false;
			endif;
			return $this->filesystem->get_contents( $file );
		}


		/**
		 * Helper to create a folder on the server
		 *
		 * @since 3.19
		 * @access public
		 *
		 * @param string $dir Target directory path.
		 *
		 * @return boolean TRUE in case of success, FALSE otherwise
		 */
		final public function mkdir( $dir ) {
			if ( ! $this->check_filesystem() || ! isset( $dir ) || empty( $dir ) ) :
				return false;
			endif;
			return $this->filesystem->mkdir( $dir, FS_CHMOD_DIR );
		}


		/**
		 * Helper to move a file / folder
		 *
		 * @since 3.19
		 * @access public
		 *
		 * @param string $src Source of the item.
		 * @param string $dest Target of the item.
		 *
		 * @return boolean TRUE in case of success, FALSE otherwise
		 */
		final public function move( $src, $dest ) {
			if ( ! $this->check_filesystem() ) :
				return false;
			endif;
			if ( ! isset( $src ) || empty( $src ) || ! isset( $dest ) || empty( $desc ) ) :
				return false;
			endif;
			return $this->filesystem->move( $src, $dest, true );
		}


		/**
		 * Helper to write data into a file
		 *
		 * @since 3.19
		 * @access public
		 *
		 * @param string $file Target file.
		 * @param string $contents Data to write to the target file.
		 *
		 * @return boolean TRUE in case of success, FALSE otherwise
		 */
		final public function put_contents( $file, $contents ) {
			if ( ! $this->check_filesystem() ) :
				return false;
			endif;
			if ( ! isset( $file ) || empty( $file ) || ! isset( $contents ) || empty( $contents ) ) :
				return false;
			endif;
			return $this->filesystem->put_contents( $file, $contents );
		}


		/**
		 * Helper to create a file
		 *
		 * @since 3.19
		 * @access public
		 *
		 * @param string $file Target file.
		 *
		 * @return boolean TRUE in case of success, FALSE otherwise
		 */
		final public function touch( $file ) {
			if ( ! $this->check_filesystem() ) :
				return false;
			endif;
			if ( ! isset( $file ) || empty( $file ) ) :
				return false;
			endif;
			return $this->filesystem->touch( $file );
		}


		/**
		 * Helper to delete a file
		 *
		 * @since 3.26
		 * @access public
		 *
		 * @param string $file Target file.
		 *
		 * @return boolean TRUE in case of success, FALSE otherwise
		 */
		final public function unlink( $file ) {
			if ( ! $this->check_filesystem() ) :
				return false;
			endif;
			if ( ! isset( $file ) || empty( $file ) ) :
				return false;
			endif;
			return $this->filesystem->delete( $file, false, false );
		}
	}


	/**
	 * Allow developers to access public properties and methods of the instance.
	 */
	final class Markup_Markdown_Instance {

		/**
		 * The handler of the primary instance
		 *
		 * @var Object
		 */
		private static $instance;

		/**
		 * Initialize if need be a new instance of Markup Markdown
		 *
		 * @return Object The static instance
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Markup_Markdown_Instance ) ) :
				self::$instance = new Markup_Markdown();
				new \MarkupMarkdown\Core\Activation();
			endif;
			return self::$instance;
		}
	}


	if ( ! function_exists( 'markup_markdown' ) ) :
		/**
		 * Global available helper
		 *
		 * @function markup_markdown
		 */
		function markup_markdown() {
			return Markup_Markdown_Instance::instance();
		}
		// Run !
		markup_markdown();

		if ( ! function_exists( 'mmd' ) ) :
			/**
			 * Previous global available helper
			 *
			 * @function mmd
			 */
			function mmd() {
				return markup_markdown();
			}

		endif;

	endif;

else :

	die( "Don't call Markup Markdown Twice ! ! !" );

endif;
