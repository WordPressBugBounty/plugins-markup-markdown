<?php
/**
 * Admin screen settings template for the _latex_ addon
 *
 * @category   Addons Templates
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="tab-latex" class="vertical-rows">
	<h2><?php esc_html_e( 'LaTeX', 'markup-markdown' ); ?></h2>
	<p><?php esc_html_e( 'Easily type and render math formulas inside your post.', 'markup-markdown' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
<?php
	$markup_markdown_latex_conf = array(
		'latex'          => 'none',
		'latex_front'    => 0,
		'latex_front_id' => 0,
	);
	if ( defined( 'MARKUP_MARKDOWN_USE_LATEX' ) && is_array( MARKUP_MARKDOWN_USE_LATEX ) ) :
		if ( isset( MARKUP_MARKDOWN_USE_LATEX[1] ) ) :
			$markup_markdown_latex_conf['latex'] = MARKUP_MARKDOWN_USE_LATEX[1];
		endif;
		if ( isset( MARKUP_MARKDOWN_USE_LATEX[2] ) ) :
			$markup_markdown_latex_conf['latex_front'] = MARKUP_MARKDOWN_USE_LATEX[2];
		endif;
		if ( isset( MARKUP_MARKDOWN_USE_LATEX[3] ) ) :
			$markup_markdown_latex_conf['latex_front_id'] = MARKUP_MARKDOWN_USE_LATEX[3];
		endif;
	endif;
	?>
			<tr class="site-use-latex">
				<th scope="row">
					<?php esc_html_e( 'Rendering engine', 'markup-markdown' ); ?>
				</th>
				<td>
					<label for="mmd_uselatex0">
						<input type="radio" name="mmd_uselatex" id="mmd_uselatex0" value="none" <?php echo ! isset( $markup_markdown_latex_conf['latex'] ) || $markup_markdown_latex_conf['latex'] === 'none' ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'None', 'markup-markdown' ); ?>
					</label>&nbsp;&nbsp;
					<label for="mmd_uselatex1">
						<input type="radio" name="mmd_uselatex" id="mmd_uselatex1" value="katex" <?php echo isset( $markup_markdown_latex_conf['latex'] ) && $markup_markdown_latex_conf['latex'] === 'katex' ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Katex rendering', 'markup-markdown' ); ?> (HTML / MATHML - v0.16.22)
					</label>&nbsp;&nbsp;
					<label for="mmd_uselatex2">
						<input type="radio" name="mmd_uselatex" id="mmd_uselatex2" value="mathjax" <?php echo isset( $markup_markdown_latex_conf['latex'] ) && $markup_markdown_latex_conf['latex'] === 'mathjax' ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Mathjax rendering', 'markup-markdown' ); ?> (SVG / MATHML - v3.2.2)
					</label>
				</td>
			</tr>
			<tr class="site-load-front">
				<th scope="row">
					<?php esc_html_e( 'Load assets', 'markup-markdown' ); ?>
				</th>
				<td>
					<label for="mmd_latex_front">
						<input type="checkbox" name="mmd_latex_front" id="mmd_latex_front" value="1" <?php echo isset( $markup_markdown_latex_conf['latex_front'] ) && (int) $markup_markdown_latex_conf['latex_front'] > 0 ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Load the LaTeX engine related assets on the frontend as well. (Only added to the edit screen by default)', 'markup-markdown' ); ?>
					</label><br />
					<br />
					<label for="mmd_latex_front_id">
						<?php esc_html_e( 'Specify the ID of an HTML node to parse when a page is loaded. (Parse the whole body by default)', 'markup-markdown' ); ?><br />
						<input type="text" name="mmd_latex_front_id" id="mmd_latex_front_id" value="<?php echo isset( $markup_markdown_latex_conf['latex_front_id'] ) && ! empty( $markup_markdown_latex_conf['latex_front_id'] ) ? esc_attr( $markup_markdown_latex_conf['latex_front_id'] ) : ''; ?>" class="regular-text">
					</label>
				</td>
			</tr>
		</tbody>
	</table>
</div><!-- #tab-latex  -->
