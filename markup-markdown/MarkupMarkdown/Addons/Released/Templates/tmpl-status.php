<?php
/**
 * Admin screen settings template for the _debug_ section
 *
 * @category   Addons Templates
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

$markup_markdown_blog_conf_file = markup_markdown()->conf_blog_prefix . 'conf.php';
if ( markup_markdown()->exists( $markup_markdown_blog_conf_file ) ) :
	printf( '<div id="mmd_debug_settings">' );
	printf( '<h3>%s</h3>', esc_html( 'Below is the summary of the settings used for the current blog:' ) );
	$markup_markdown_blog_conf = markup_markdown()->get_contents( $markup_markdown_blog_conf_file );
	$markup_markdown_blog_conf = str_replace( array( '<?php', '?>', 'define(', ')', '[', ']' ), '', $markup_markdown_blog_conf );
	$markup_markdown_blog_conf = preg_replace( '#defined.*?;#', '', $markup_markdown_blog_conf );
	$markup_markdown_blog_conf = str_replace( array( '\',', '\'', ';' ), ' | ', $markup_markdown_blog_conf );
	$markup_markdown_blog_conf = preg_replace( '#\n[\s\t]*#', "\n", $markup_markdown_blog_conf );
	if ( defined( 'MARKUP_MARKDOWN_ADDONS' ) ) :
		$markup_markdown_blog_conf .= "\n" . '| MARKUP_MARKDOWN_ADDONS | "' . implode( '", "', MARKUP_MARKDOWN_ADDONS ) . '" |';
	endif;
	if ( defined( 'MARKUP_MARKDOWN_AUTOPLUGS' ) ) :
		$markup_markdown_active_plugs = array();
		foreach ( MARKUP_MARKDOWN_AUTOPLUGS as $markup_markdown_plug_name => $markup_markdown_plug_bool ) :
			if ( (int) $markup_markdown_plug_bool > 0 ) :
				$markup_markdown_active_plugs[] = $markup_markdown_plug_name;
			endif;
		endforeach;
		$markup_markdown_blog_conf .= "\n" . '| MARKUP_MARKDOWN_AUTOPLUGS | "' . implode( '", "', $markup_markdown_active_plugs ) . '" |';
	endif;
	$markup_markdown_blog_conf = '| ' . esc_html( 'Constants', 'markup-markdown' )
		. ' | ' . esc_html( 'Values', 'markup-markdown' ) . ' |'
		. "\n" . '| ---- | ---- |' . "\n" . $markup_markdown_blog_conf . "\n";
	$markup_markdown_blog_conf = preg_replace( '#\n+#', "\n", $markup_markdown_blog_conf );
	printf(
		'%s',
		wp_kses(
			str_replace( '<table>', '<table class="wp-list-table widefat fixed striped table-view-list">', markup_markdown()->markdown2html( $markup_markdown_blog_conf ) ),
			array(
				'table' => array( 'class' => true ),
				'thead' => array(),
				'tbody' => array(),
				'tr'    => array(),
				'th'    => array(),
				'td'    => array(),
				'br'    => array(),
			)
		)
	);
	printf( '</div>' );
endif;

printf( '<div id="mmd_debug_plugins">' );
// https://stackoverflow.com/questions/20488264/how-do-i-get-activated-plugin-list-in-wordpress-plugin-development
printf( '<h3>%s</h3>', esc_html( 'List of active plugins for the current blog:' ) );
$markup_markdown_blog_plugins = esc_html( 'Plugin', 'markup-markdown' )
	. ' | ' . esc_html( 'Version', 'markup-markdown' )
	. ' | ' . esc_html( 'Description', 'markup-markdown' ) . ' |'
	. "\n" . '| ---- | ---- | ---- |' . "\n";
$markup_markdown_all_plugins  = get_option( 'active_plugins' );
$markup_markdown_plugins      = get_plugins();
foreach ( $markup_markdown_all_plugins as $markup_markdown_curr_plugin ) :
	if ( isset( $markup_markdown_plugins[ $markup_markdown_curr_plugin ] ) ) :
		$markup_markdown_blog_plugins .= '| ';
		if ( isset( $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['PluginURI'] ) && ! empty( $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['PluginURI'] ) ) :
			$markup_markdown_blog_plugins .= '[' . $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['Name'] . '](' . $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['PluginURI'] . ')';
		else :
			$markup_markdown_blog_plugins .= $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['Name'];
		endif;
		$markup_markdown_blog_plugins .= ' | ' . ( isset( $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['Version'] ) ? $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['Version'] : '' );
		$markup_markdown_blog_plugins .= ' | ' . $markup_markdown_plugins[ $markup_markdown_curr_plugin ]['Description'] . ' |' . "\n";
	endif;
endforeach;
printf(
	'%s',
	wp_kses(
		str_replace( '<table>', '<table class="wp-list-table widefat fixed striped table-view-list">', markup_markdown()->markdown2html( $markup_markdown_blog_plugins ) ),
		array(
			'table' => array( 'class' => true ),
			'thead' => array(),
			'tbody' => array(),
			'tr'    => array(),
			'th'    => array(),
			'td'    => array(),
			'br'    => array(),
			'a'     => array( 'href' => array() ),
		)
	)
);
printf( '</div>' );

printf( '<h2>%s</h2>', esc_html( 'Information to attach to the support ticket if need be:' ) );
printf( '%s', '<pre id="mmd_debug_info"></pre>' );
