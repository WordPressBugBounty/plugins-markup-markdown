<?php
/**
 * Admin screen settings template for the _code highlighter_ addon
 *
 * @category   Addons Templates
 * @package    MarkupMarkdown
 * @since      3.19.0
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="tab-codehighlight" class="vertical-rows">
	<h2><?php esc_html_e( 'Syntax Highlighting', 'markup-markdown' ); ?></h2>
	<p><?php esc_html_e( 'Colorful syntax highlighting for your snippets code.', 'markup-markdown' ); ?></p>
	<table class="form-table" role="presentation">
		<tbody>
<?php
	$markup_markdown_hlgh_cnf = array(
		'code_highlighter'       => 'none',
		'code_highlighter_front' => 0,
		'code_highlighter_theme' => 'vs',
	);
	if ( defined( 'MARKUP_MARKDOWN_USE_CODEHIGHLIGHT' ) && is_array( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT ) ) :
		if ( isset( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[1] ) ) :
			$markup_markdown_hlgh_cnf['code_highlighter'] = MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[1];
		endif;
		if ( isset( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[2] ) ) :
			$markup_markdown_hlgh_cnf['code_highlighter_front'] = MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[2];
		endif;
		if ( isset( MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[3] ) ) :
			$markup_markdown_hlgh_cnf['code_highlighter_theme'] = MARKUP_MARKDOWN_USE_CODEHIGHLIGHT[3];
		endif;
	endif;
	?>
			<tr class="site-use-codehighlighter">
				<th scope="row">
					<?php esc_html_e( 'Rendering engine', 'markup-markdown' ); ?>
				</th>
				<td>
					<label for="mmd_usecodehighlighter1">
						<input type="radio" name="mmd_usecodehighlighter" id="mmd_usecodehighlighter1" value="prism" <?php echo ! isset( $markup_markdown_hlgh_cnf['code_highlighter'] ) || 'prism' === $markup_markdown_hlgh_cnf['code_highlighter'] ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Prism.js rendering (Default)', 'markup-markdown' ); ?>
					</label>&nbsp;&nbsp;
					<label for="mmd_usecodehighlighter2">
						<input type="radio" name="mmd_usecodehighlighter" id="mmd_usecodehighlighter2" value="highlight" <?php echo isset( $markup_markdown_hlgh_cnf['code_highlighter'] ) && 'highlight' === $markup_markdown_hlgh_cnf['code_highlighter'] ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Highlight.js rendering', 'markup-markdown' ); ?>
					</label>&nbsp;&nbsp;<br />
					<em><?php esc_html_e( 'Dark mode not supported on the backend, Visual Studio based theme is setup by default for the admin screen and the built-in preview.', 'markup-markdown' ); ?></em>
				</td>
			</tr>
			<tr class="site-load-front">
				<th scope="row">
					<?php esc_html_e( 'Load assets', 'markup-markdown' ); ?>
				</th>
				<td>
					<label for="code_highlighter_front">
						<input type="checkbox" name="mmd_codehighlighter_front" id="code_highlighter_front" value="1" <?php echo isset( $markup_markdown_hlgh_cnf['code_highlighter_front'] ) && (int) $markup_markdown_hlgh_cnf['code_highlighter_front'] > 0 ? 'checked="checked"' : ''; ?> />
						<?php esc_html_e( 'Activate syntax highlighting on the frontend as well (Disabled by default)', 'markup-markdown' ); ?><br />
						<em><?php esc_html_e( 'Useful if your theme does not support by default syntax highlighting for code snippets.', 'markup-markdown' ); ?></em>
					</label>
				</td>
			</tr>
			<tr class="site-pickup-theme">
				<th scope="row">
					<?php esc_html_e( 'Theme', 'markup-markdown' ); ?>
				</th>
				<td>
					<select name="mmd_codehighlighter_theme" id="code_highlighter_theme">
						<option value="#"><?php esc_html_e( 'Please select a theme', 'markup-markdown' ); ?></option>
					<?php
					if ( ! preg_match( '#^(prism|hl)-#', $markup_markdown_user_theme ) ) :
						$markup_markdown_user_theme = 'prism-' . $markup_markdown_user_theme;
					endif;
					foreach ( $markup_markdown_code_themes as $markup_markdown_engine_slug => $markup_markdown_engine_themes ) :
						$markup_markdown_engine_slug = str_replace( 'js', '', $markup_markdown_engine_slug );
						printf( '<optgroup label="%s" class="%s">', esc_attr( strtoupper( $markup_markdown_engine_slug ) ), esc_attr( $markup_markdown_engine_slug ) );
						foreach ( $markup_markdown_engine_themes as $markup_markdown_theme_slug => $markup_markdown_theme_label ) :
							printf( '<option value="%s"%s>%s</option>', esc_attr( $markup_markdown_theme_slug ), $markup_markdown_theme_slug === $markup_markdown_user_theme ? ' selected="selected"' : '', esc_html( $markup_markdown_theme_label ) );
						endforeach;
						printf( '</optgroup>' );
					endforeach;
					?>
					</select>
					<br>
					<em><?php esc_html_e( 'Dark themes are available, the selected theme will only be applied to the frontend when activated.', 'markup-markdown' ); ?></em><br>
					<a href="https://github.com/PrismJS/prism-themes" target="_blank" rel="nofollow"><?php esc_html_e( 'View the previews of Prism.js themes', 'markup-markdown' ); ?></a> /
					<a href="https://highlightjs.org/demo" target="_blank" rel="nofollow"><?php esc_html_e( 'Test in live Highlight.js themes', 'markup-markdown' ); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
</div><!-- #tab-latex  -->
