<?php
/**
 * Admin screen settings template for the _comments_ addon
 *
 * @category   Addons Templates
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit; ?>


<div id="tab-comments" class="vertical-rows">
	<h2><?php esc_html_e( 'Comments', 'markup-markdown' ); ?></h2>
	<p><?php esc_html_e( 'Use markdown inside your comments.', 'markup-markdown' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
<?php
	require_once markup_markdown()->plugin_dir . '/MarkupMarkdown/Addons/Released/Media/class-commentstags.php';
	$markup_markdown_comment_toolbar = new \MarkupMarkdown\Addons\Released\Media\CommentsTags( $markup_markdown_comments_tags_conf, false );
foreach ( $markup_markdown_comment_toolbar->default_tags as $markup_markdown_tag_name => $markup_markdown_tag_attrs ) :
	$markup_markdown_curr_attrs = isset( $markup_markdown_comment_toolbar->allowed_tags->{$markup_markdown_tag_name} ) ? $markup_markdown_comment_toolbar->allowed_tags->{$markup_markdown_tag_name} : array();
	?>
			<tr>
		<?php
		printf( '<th scope="row"><label for="comment_tag_%s"><input id="comment_tag_%s" name="comment_tag[%s]" type="checkbox" value="1" %s/> %s</label></th>', esc_attr( $markup_markdown_tag_name ), esc_attr( $markup_markdown_tag_name ), esc_attr( $markup_markdown_tag_name ), isset( $markup_markdown_curr_attrs->active ) ? ' checked="checked"' : '', esc_html( strtoupper( $markup_markdown_tag_name ) ) );
		printf( '<td>' );
		unset( $markup_markdown_tag_attrs['active'] );
		foreach ( $markup_markdown_tag_attrs as $markup_markdown_attr_name => $markup_markdown_attr_value ) :
			printf( '<label for="comment_tag_%s_attr_%s"><input id="comment_tag_%s_attr_%s" name="comment_tag_%s_attr[%s]" type="checkbox" value="1" %s/> %s</label>  &nbsp; ', esc_attr( $markup_markdown_tag_name ), esc_attr( $markup_markdown_attr_name ), esc_attr( $markup_markdown_tag_name ), esc_attr( $markup_markdown_attr_name ), esc_attr( $markup_markdown_tag_name ), esc_attr( $markup_markdown_attr_name ), isset( $markup_markdown_curr_attrs->{$markup_markdown_attr_name} ) ? ' checked="checked"' : '', esc_html( $markup_markdown_attr_name ) );
			endforeach;
		printf( '</td>' );
		?>
			</tr>
	<?php endforeach; ?>
		</tbody>
	</table>
</div>
