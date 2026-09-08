<?php
/**
 * Base methods to handle WordPress media images
 *
 * @category   Abstracts
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Mostly helpers to interact with WordPress image type media
 */
abstract class ImageTinyAPI {


	// No __construct here, nothing to do here mate !


	/**
	 * WordPress upload dir params
	 *
	 * @var array
	 */
	protected $upload_dir = array();


	/**
	 * Current blog home url
	 *
	 * @var string
	 */
	public $home_url = '';


	/**
	 * Folder where assets ID cache are stored
	 *
	 * @var string
	 */
	protected $asset_cache_dir = '';


	/**
	 * Get an attachment ID given a URL
	 *
	 * @since 3.14
	 * @access protected
	 * @link https://gist.github.com/wpscholar/3b00af01863c9dc562e5#file-get-attachment-id-php
	 *
	 * @param string $url The url of the target media.
	 * @return integer Attachment ID on success, 0 on failure
	 */
	protected function get_attachment_id( $url ) {
		if ( strpos( $url, $this->upload_dir['baseurl'] . '/' ) === false ) :
			// $url does not contain the upload directory
			return -2;
		endif;
		$file       = basename( $url );
		$query_args = array(
			'post_type'    => 'attachment',
			'post_status'  => 'inherit',
			'fields'       => 'ids',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'     => '_wp_attachment_metadata',
			'meta_compare' => 'LIKE',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'   => \esc_sql( $file ),
		);
		$query      = new \WP_Query( $query_args );
		if ( ! $query->have_posts() ) :
			\wp_reset_postdata();
			return -1;
		endif;
		$attachment_id = 0;
		foreach ( $query->posts as $post_id ) :
			$meta = \wp_get_attachment_metadata( $post_id );
			if ( ! isset( $meta ) || ! $meta || ! isset( $meta['file'] ) || ! isset( $meta['sizes'] ) ) :
				continue;
			endif;
			$original_file       = preg_replace( '#-scaled\.([a-z0-9]+)$#i', '.$1', basename( $meta['file'] ) );
			$cropped_image_files = \wp_list_pluck( $meta['sizes'], 'file' );
			if ( $original_file === $file || in_array( $file, $cropped_image_files, true ) ) :
				$attachment_id = $post_id;
				break;
			endif;
		endforeach;
		\wp_reset_postdata();
		return $attachment_id;
	}


	/**
	 * Retrieve the WP Attachment ID if already cached
	 *
	 * @since 3.14
	 * @access protected
	 *
	 * @param string $img_src The image source URL.
	 * @return integer The attachment ID
	 */
	protected function get_cached_asset_id( $img_src = '' ) {
		if ( empty( $img_src ) ) :
			return 0;
		elseif ( ! preg_match( '#^/#', $img_src ) && strpos( $img_src, $this->home_url ) === false ) :
			return 0;
		endif;
		$asset_cached_id = $this->asset_cache_dir . '/' . md5( $img_src ) . '.txt';
		$img_id          = 0;
		if ( markup_markdown()->exists( $asset_cached_id ) ) :
			$img_id = markup_markdown()->get_contents( $asset_cached_id );
		else :
			$img_src = $this->home_url . str_replace( $this->home_url, '', preg_replace( '#-(\d+)x(\d+)\.(\w+)$#', '.$3', $img_src ) ); // WordPress images.
			$img_id  = $this->get_attachment_id( $img_src );
			if ( (int) $img_id > 0 ) :
				markup_markdown()->touch( $asset_cached_id );
				markup_markdown()->put_contents( $asset_cached_id, $img_id );
			endif;
		endif;
		return (int) $img_id;
	}


	/**
	 * Extract the text and check for caption data
	 *
	 * @since 3.14
	 * @access protected
	 *
	 * @param string|null $caption Current image alternative text.
	 * @return array Text used for the image's alternative text and its related caption
	 */
	protected function check_alt_attribute( $caption ) {
		if ( ! isset( $caption ) && empty( $caption ) ) :
			return array();
		endif;
		if ( strpos( $caption, '--' ) !== false ) :
			$text = explode( '--', $caption );
			return array(
				'alt'     => trim( $text[0] ),
				'caption' => trim( $text[1] ),
			);
		else :
			return array(
				'alt' => trim( $caption ),
			);
		endif;
	}


	/**
	 * Check the align attribute extracted from the HTML image align attribute or an HTML link class attribute if available
	 *
	 * @since 3.14
	 * @access protected
	 *
	 * @param string $align Position of the media.
	 * @return array WP valide align value
	 */
	protected function check_align_attribute( $align = '' ) {
		if ( ! isset( $align ) || empty( $align ) ) :
			return array();
		endif;
		if ( in_array( $align, array( 'none', 'left', 'right', 'center' ), true ) ) :
			return array(
				'align' => $align,
			);
		endif;
	}


	/**
	 * Check the class attribute extracted from the HTML image if available
	 *
	 * @since 3.20
	 * @access protected
	 *
	 * @param string $classnames The item classes.
	 * @return array class attribute
	 */
	protected function check_class_attribute( $classnames = '' ) {
		$classnames = trim( preg_replace( '#align([a-z]+\s*)#', '', $classnames ) );
		return ! empty( $classnames ) ? array( 'class' => $classnames ) : array();
	}


	/**
	 * Check the width value extracted from the HTML image's width attribute if available
	 *
	 * @since 3.14
	 * @access protected
	 *
	 * @param array  $width Extracted values of the image's width attribute.
	 * @param string $src Extracted value of the image's source.
	 * @return array Requested width number
	 */
	protected function check_width_attribute( $width = array(), $src = '' ) {
		if ( isset( $width ) && is_array( $width ) && isset( $width[1] ) && is_numeric( $width[1] ) && (int) $width[1] > 0 ) :
			// Check first value extracted from the width's attribute.
			return array(
				'width' => (int) $width[1],
			);
		endif;
		$img_width = array();
		if ( isset( $src ) && ! empty( $src ) && preg_match( '#(\d+)x\d+\.[a-zA-Z0-9]+$#', $src, $img_width ) ) :
			// As a fallback try to extract the width from the thumbnail.
			if ( isset( $img_width ) && is_array( $img_width ) && isset( $img_width[1] ) ) :
				return array(
					'width' => (int) $img_width[1],
				);
			endif;
		endif;
		return array();
	}


	/**
	 * Check the height value extracted from the HTML image's width attribute if available
	 *
	 * @since 3.14
	 * @access protected
	 *
	 * @param array  $height Extracted value of the image's height attribute.
	 * @param string $src Extracted value of the image's source.
	 * @return array Requested height number
	 */
	protected function check_height_attribute( $height = array(), $src = '' ) {
		if ( ! isset( $height ) && is_array( $height ) && isset( $height[1] ) && is_numeric( $height[1] ) && (int) $height[1] > 0 ) :
			// Check first value extracted from the height's attribute.
			return array(
				'height' => (int) $height[1],
			);
		endif;
		$img_height = array();
		if ( isset( $src ) && ! empty( $src ) && preg_match( '#\d+x(\d+)\.[a-zA-Z0-9]+$#', $src, $img_height ) ) :
			// As a fallback try to extract the height from the thumbnail.
			if ( isset( $img_height ) && is_array( $img_height ) && isset( $img_height[1] ) ) :
				return array(
					'height' => (int) $img_height[1],
				);
			endif;
		endif;
		return array();
	}


	/**
	 * Following latest Gutenberg / Theme specification, the image is wrapped by a *figure* with a *figcaption* if need be
	 *
	 * @param integer $img_id WP Attachment ID.
	 * @param array   $img_attrs Image related attribute.
	 * @param string  $img_fallback Original img tag as fallback.
	 * @return string Modified image tag
	 */
	protected function wrap_image( $img_id = 0, $img_attrs = array(), $img_fallback = '' ) {
		if ( ! isset( $img_id ) || ! (int) $img_id || ! isset( $img_attrs ) || ! is_array( $img_attrs ) ) :
			return '';
		endif;
		$img_size = 'full';
		if ( isset( $img_attrs['width'] ) && isset( $img_attrs['height'] ) ) :
			$img_size = array( $img_attrs['width'], $img_attrs['height'] );
			unset( $img_attrs['width'] );
			unset( $img_attrs['height'] );
		endif;
		$img_caption = '';
		if ( isset( $img_attrs['caption'] ) ) :
			$img_caption = trim( $img_attrs['caption'] );
			unset( $img_attrs['caption'] );
		endif;
		$img_class = '';
		if ( isset( $img_attrs['class'] ) && ! empty( $img_attrs['class'] ) ) :
			$img_class = ' ' . $img_attrs['class'];
			if ( defined( 'MARKUP_MARKDOWN_USE_BLOCKSTYLES' ) && MARKUP_MARKDOWN_USE_BLOCKSTYLES ) :
				$img_attrs['class'] = 'wp-image-' . $img_id;
			endif;
		endif;
		$img_html = \wp_get_attachment_image( $img_id, $img_size, false, $img_attrs );
		if ( ( ! isset( $img_html ) || ! $img_html || empty( $img_html ) ) && ( isset( $img_fallback ) && ! empty( $img_fallback ) ) ) :
			$img_html = function_exists( 'wp_kses_post' ) ? wp_kses_post( $img_fallback ) : '';
		endif;
		return '<figure id="attachment_mmd_' . $img_id . '" '
			. ( ! empty( $img_caption ) ? 'aria-describedby="caption-attachment-mmd' . $img_id . '" class="wp-block-image wp-caption ' : 'class="wp-block-image ' )
			. ( isset( $img_attrs['align'] ) ? 'align' . $img_attrs['align'] : '' ) . $img_class
			. '">' . $img_html
			. ( ! empty( $img_caption ) ? '<figcaption id="caption-attachment-mmd' . $img_id . '" class="wp-caption-text wp-element-caption">' . trim( $img_caption ) . '</figcaption>' : '' )
			. '</figure>';
	}


	/**
	 * Replace HTML image tags with customized WordPress generated version
	 *
	 * @since 3.14
	 * @access protected
	 *
	 * @param integer $img_id Image ID within WordPress Media.
	 * @param string  $img_src Image original source.
	 * @param string  $img_html Image HTML code.
	 * @return string Modified html image
	 */
	protected function native_wp_image( $img_id = 0, $img_src = '', $img_html = '' ) {
		if ( ! isset( $img_id ) || ! (int) $img_id || ! isset( $img_html ) || empty( $img_html ) || ! isset( $img_src ) || empty( $img_src ) ) :
			return '';
		endif;
		$img_attrs = array(
			'decoding' => 'async',
			'loading'  => 'lazy',
		);
		$tmp_args  = array();
		// Check for captions related values.
		if ( preg_match( '#alt="(.*?)"#', $img_html, $tmp_args ) === 1 ) :
			$img_attrs = array_merge( $img_attrs, $this->check_alt_attribute( $tmp_args[1] ) );
		endif;
		// Check for a custom align value.
		if ( preg_match( '#class\=\"[A-Za-z0-9-_\s]*align([a-z]+)#', $img_html, $tmp_args ) === 1 ) :
			$img_attrs = array_merge( $img_attrs, $this->check_align_attribute( $tmp_args[1] ) );
		endif;
		// Check for a width value.
		preg_match( '#width="(.*?)"#', $img_html, $tmp_args );
		$img_attrs = array_merge( $img_attrs, $this->check_width_attribute( $tmp_args, $img_src ) );
		// Check for a height value.
		preg_match( '#height="(.*?)"#', $img_html, $tmp_args );
		$img_attrs = array_merge( $img_attrs, $this->check_height_attribute( $tmp_args, $img_src ) );
		// Check for custom classnames.
		if ( preg_match( '#class="([a-z\s]+)"#', $img_html, $tmp_args ) === 1 ) :
			$img_attrs = array_merge( $img_attrs, $this->check_class_attribute( $tmp_args[1] ) );
		endif;
		$new_img = $this->wrap_image( $img_id, $img_attrs, $img_html );
		return preg_replace( '#<img[^>]+>#', $new_img, $img_html );
	}
}
