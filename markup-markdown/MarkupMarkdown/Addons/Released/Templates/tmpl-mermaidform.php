<?php
/**
 * Admin screen settings template for the _mermaid_ addon
 *
 * @category   Addons Templates
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="tab-mermaid" class="vertical-rows">
	<h2><?php esc_html_e( 'Mermaid', 'markup-markdown' ); ?></h2>
	<p><?php esc_html_e( 'Easily display diagrams and charts inside your post.', 'markup-markdown' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
<?php
	$markup_markdown_mermaid_conf = array(
		'mermaid'       => 'none',
		'mermaid_front' => 0,
	);
	if ( defined( 'MARKUP_MARKDOWN_USE_MERMAID' ) && is_array( MARKUP_MARKDOWN_USE_MERMAID ) ) :
		if ( isset( MARKUP_MARKDOWN_USE_MERMAID[1] ) ) :
			$markup_markdown_mermaid_conf['mermaid'] = MARKUP_MARKDOWN_USE_MERMAID[1];
		endif;
		if ( isset( MARKUP_MARKDOWN_USE_MERMAID[2] ) ) :
			$markup_markdown_mermaid_conf['mermaid_front'] = MARKUP_MARKDOWN_USE_MERMAID[2];
		endif;
	endif;
	?>
			<tr class="site-load-front">
				<th scope="row">
					<?php esc_html_e( 'Load assets', 'markup-markdown' ); ?>
				</th>
				<td>
					<label for="mmd_mermaid_front">
						<input type="checkbox" name="mermaid_front" id="mmd_mermaid_front" value="1" <?php echo isset( $markup_markdown_mermaid_conf['mermaid_front'] ) && (int) $markup_markdown_mermaid_conf['mermaid_front'] > 0 ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Load the Mermaid engine related assets on the frontend as well. (Only loaded on the edit screen by default)', 'markup-markdown' ); ?>
					</label>
					<input type="hidden" name="mmd_usemermaid" id="mmd_usemermaid" value="mermaid" />
					<br /><br />To render Mermaid diagrams as svg, please to use the <code>pre</code> tag. For example:
					<pre>&lt;pre class="mermaid"&gt;
	sequenceDiagram
		participant Alice
		participant Bob
		Alice->>Bob: Hello Bob, how are you?
		Bob-->>Alice: I am good thanks!
&lt;/pre&gt;</pre>
				</td>
			</tr>
		</tbody>
	</table>
</div><!-- #tab-mermaid  -->
