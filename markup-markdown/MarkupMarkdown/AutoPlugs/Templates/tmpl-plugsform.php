<?php
/** Admin screen settings template for the _autoplugs_ tag
 *
 * @category   Autoplug Templates
 * @package    MarkupMarkdown
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="tab-plugs">
	<h2><?php esc_html_e( 'Autoplugs', 'markup-markdown' ); ?></h2>
	<p><?php esc_html_e( 'Bridges to activate markdown with existing plugins if available.', 'markup-markdown' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
<?php
if ( ! defined( 'MARKUP_MARKDOWN_AUTOPLUGS' ) || ! is_array( MARKUP_MARKDOWN_AUTOPLUGS ) ) :
	define( 'MARKUP_MARKDOWN_AUTOPLUGS', array() );
endif;
foreach ( MARKUP_MARKDOWN_AUTOPLUGS as $markup_markdown_plug_slug => $markup_markdown_active_plug ) :
	$markup_markdown_plug_slug_class = esc_attr( strtolower( $markup_markdown_plug_slug ) );
	?>
			<tr class="<?php echo esc_attr( 'site-plug-' . $markup_markdown_plug_slug_class ); ?>">
				<th scope="row">
			<?php
			if ( isset( $default_plugs ) && isset( $default_plugs[ $markup_markdown_plug_slug ] ) && is_array( $default_plugs[ $markup_markdown_plug_slug ] ) ) :
				if ( isset( $default_plugs[ $markup_markdown_plug_slug ][2] ) ) :
					echo '<a href="' . esc_attr( $default_plugs[ $markup_markdown_plug_slug ][1] ) . '" target="_blank" rel="nofollow">' . esc_html( $default_plugs[ $markup_markdown_plug_slug ][2] ) . '</a>';
					elseif ( isset( $default_plugs[ $markup_markdown_plug_slug ][1] ) ) :
						echo esc_html( $default_plugs[ $markup_markdown_plug_slug ][1] );
						else :
							echo esc_html( $markup_markdown_plug_slug );
						endif;
					else :
						echo esc_html( $markup_markdown_plug_slug );
					endif;
					?>
				</th>
				<td>
					<label for="<?php echo esc_attr( 'mmd_plug_' . $markup_markdown_plug_slug_class ); ?>">
						<input type="checkbox" name="mmd_plugs[]" id="mmd_plug_<?php echo esc_attr( $markup_markdown_plug_slug_class ); ?>" value="<?php echo esc_attr( $markup_markdown_plug_slug ); ?>" <?php echo isset( $markup_markdown_active_plug ) && (int) $markup_markdown_active_plug > 0 ? 'checked="checked"' : ''; ?> />
					<?php esc_html_e( 'Activated', 'markup-markdown' ); ?>
					</label>
				</td>
			</tr>
	<?php
	endforeach;
?>
		</tbody>
	</table>
</div>
