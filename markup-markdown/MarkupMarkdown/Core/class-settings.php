<?php
/**
 * Settings Class
 *
 * @category   Core
 * @package    MarkupMarkdown
 * @since      1.0.0
 */

namespace MarkupMarkdown\Core;

defined( 'ABSPATH' ) || exit;
if ( ! defined( 'MARKUP_MARKDOWN_PLUGIN_ACTIVATED' ) ) :
	return false; // The plugin was not properly activated.
endif;


/**
 * This class handles the settings on the admin screen and the configuration record helpers
 */
final class Settings {

	/**
	 * Status if the update, TRUE in case of success or FALSE
	 * Used when the config file has change
	 *
	 * @var boolean
	 */
	public $updated = -1;


	/**
	 * The addons properties
	 *
	 * @var object
	 */
	public $addons;


	/**
	 * What do you expect ?
	 *
	 * @param object $addons The object with addons properties and instances.
	 * @return void
	 */
	public function __construct( $addons ) {
		$this->addons = $addons;
		if ( is_admin() ) :
			// Only initilize when we are on the admin screen.
			$this->initialize();
		endif;
	}


	/**
	 * Initiliaze the plugin settings on the WordPress admin screen
	 *
	 * @return void
	 */
	private function initialize() {
		// Menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		// Options Edit Screen.
		$my_page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS );
		if ( isset( $my_page ) && 'markup-markdown-admin' === $my_page ) :
			// Add help and plugins toggler. This action should be run *after* admin_menu.
			add_action( 'load-settings_page_markup-markdown-admin', array( $this, 'mmd_setup_tools' ), 10 );
			// Check if the setting form was submitted.
			$this->update_config();
			// Load assets.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_setting_scripts' ) );
		else :
			$this->make_conf( false, true );
		endif;
	}


	/**
	 * Make the configuration file
	 *
	 * @since 1.7.2
	 * @access private
	 *
	 * @param array   $params data as key => val used later as constants.
	 * @param boolean $is_new to check whether the file already exists.
	 *
	 * @return boolean TRUE if the file already exists or was updated
	 */
	private function make_conf( $params = array(), $is_new = false ) {
		$conf_file = markup_markdown()->conf_blog_prefix . 'conf.php';
		if ( $is_new && markup_markdown()->exists( $conf_file ) ) :
			return false;
		endif;
		markup_markdown()->touch( $conf_file );
		if ( ! isset( $params ) || ! is_array( $params ) ) :
			$params = markup_markdown()->default_conf;
		endif;
		$php_code   = array( '<?php', "\n\tdefined( 'ABSPATH' ) || exit;" );
		$php_code[] = "\n\tdefine( 'MARKUP_MARKDOWN_VERSION', \"" . markup_markdown()->version . '" );';
		foreach ( $params as $const => $val ) :
			if ( is_integer( $val ) ) :
				$php_code[] = "\n\tdefine( '" . $const . "', " . (int) $val . ' );';
			elseif ( is_array( $val ) ) :
				$php_code[] = "\n\tdefine( '" . $const . "', [ \"" . implode( '", "', $val ) . '" ] );';
			else :
				$php_code[] = "\n\tdefine( '" . $const . "', \"" . htmlspecialchars( $val ) . '" );';
			endif;
		endforeach;
		$this->updated = markup_markdown()->put_contents( $conf_file, implode( '', $php_code ) ) ? 1 : 0;
		markup_markdown()->clear_cache( $conf_file );
	}


	/**
	 * Filter to parse the data from the options screen when the form was submitted
	 *
	 * @access public
	 *
	 * @return boolean TRUE in case of success or false
	 */
	public function update_config() {
		// Show the success or error message.
		$options_saved = filter_input( INPUT_GET, 'options_saved', FILTER_VALIDATE_INT );
		if ( isset( $options_saved ) && ! empty( $options_saved ) ) :
			if ( 1 === $options_saved ) :
				// New settings were saved.
				add_action(
					'admin_notices',
					function () {
						echo '<div class="updated notice notice-success"><p>' . esc_html__( 'Success!', 'markup-markdown' ) . '</p></div>';
					}
				);
			elseif ( 0 === $options_saved ) :
				// Error while overriding the file.
				add_action(
					'admin_notices',
					function () {
						echo '<div class="updated notice notice-error"><p>' . esc_html__( 'Error while saving the changes.', 'markup-markdown' ) . '</p></div>';
					}
				);
			endif;
		endif;
		// Update conf is the settings form was submitted.
		$my_nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! $my_nonce || ( function_exists( 'wp_verify_nonce' ) && ! \wp_verify_nonce( $my_nonce, 'update-mmd_settings' ) ) ) :
			return false;
		endif;
		$my_cnf = apply_filters( 'markup_markdown_verified_config', array() );
		$my_cst = apply_filters( 'markup_markdown_var2const', $my_cnf );
		$this->make_conf( $my_cst );
	}


	/**
	 * The options page
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @return void
	 */
	public function options_page() {
		if ( ! current_user_can( 'manage_options' ) ) :
			return '';
		endif;
		do_action( 'markup_markdown_before_options' );
		?>
		<div id="wrap">
			<h1>Markup Markdown <sup><?php echo esc_html( markup_markdown()->version ); ?></sup> : <?php esc_html_e( 'Settings', 'markup-markdown' ); ?></h1>
			<p>
			<?php
				/* translators: 1: Link opening tag, 2: Link closing tag */
				printf( esc_html__( 'Most of the following settings are related to addons. You can globally enable or disable addons from the %1$s screen options %2$s panel.', 'markup-markdown' ), '<a href="#show-settings-link" class="toggler">', '</a>' );
			?>
			</p>
			<form method="post">
				<div id="tabs" class="vertical">
					<ul>
		<?php do_action( 'markup_markdown_tabmenu_options' ); ?>
					</ul>
		<?php do_action( 'markup_markdown_tabcontent_options' ); ?>
				</div><!-- #tabs -->
				<p class="submit">
					<input type="hidden" name="action" value="update">
					<?php wp_nonce_field( 'update-mmd_settings' ); ?>
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_html__( 'Update', 'markup-markdown' ); ?>">
				</p>
			</form>
		</div><!-- .wrap -->
		<?php
		do_action( 'markup_markdown_after_options' );
	}


	/**
	 * Trigger when the menu item was added
	 *
	 * @since 2.0.0
	 * @access public
	 *
	 * @return void
	 */
	private function setup_options_completed() {
		if ( $this->updated > -1 ) :
			// Redirect the screen options page to avoid cache issues when the config file has been updated.
			$redirect_url = \menu_page_url( 'markup-markdown-admin', false )
				. '&options_saved=' . ( $this->updated > 0 ? '1' : '0' );
			\wp_safe_redirect( $redirect_url, 302 );
			exit;
		endif;
	}


	/**
	 * Add the options menu in the admin area
	 *
	 * @since 1.7.2
	 * @access public
	 *
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page( 'Markup Markdown', 'Markup Markdown', 'manage_options', 'markup-markdown-admin', array( $this, 'options_page' ) );
		$this->setup_options_completed();
	}


	/**
	 * Add new params in the "Options Screen" area
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function mmd_setup_tools() {
		$update = $this->mmd_update_screen_options( filter_input( INPUT_POST, 'screenoptionnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		add_filter( 'screen_options_show_screen', array( $this, 'mmd_screen_options_show_screen' ), 10, 2 );
		add_filter( 'screen_options_show_submit', array( $this, 'mmd_screen_options_show_submit' ), 10, 2 );
		add_filter( 'screen_settings', array( $this, 'mmd_screen_settings' ), 10, 2 );
	}


	/**
	 * If the MMD screen options area has submitted, update the related conf
	 *
	 * @since 2.1.2
	 * @access private
	 *
	 * @param string $submit_button The value of the submit button.
	 * @return boolean TRUE in case of success, FALSE otherwise
	 */
	private function mmd_update_screen_options( $submit_button = '' ) {
		if ( ! $submit_button || empty( $submit_button ) ) :
			return false;
		endif;
		if ( ! check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' ) ) :
			return false;
		endif;
		$my_addons     = filter_input( INPUT_POST, 'mmd_addons', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY );
		$my_cnf_screen = markup_markdown()->conf_blog_prefix . 'conf_screen.php';
		if ( ! markup_markdown()->exists( $my_cnf_screen ) ) :
			markup_markdown()->touch( $my_cnf_screen );
		endif;
		$php_code   = array( '<?php' );
		$php_code[] = "\n\t" . 'defined( \'ABSPATH\' ) || exit;';
		$php_code[] = "\n\t" . 'define( \'MARKUP_MARKDOWN_ADDONS\', [';
		if ( isset( $my_addons ) && is_array( $my_addons ) ) :
			foreach ( $my_addons as $addon ) :
				$php_code[] = "\n\t\t\"" . htmlspecialchars( $addon ) . '",';
			endforeach;
			$php_code[] = "\n\t\t" . '"eof"';
		endif;
		$php_code[] = "\n\t" . ']);';
		$php_code[] = "\n\t" . 'if ( ! defined( \'MARKUP_MARKDOWN_OPCACHE\' ) ) :'
			. "\n\t\t" . 'define( \'MARKUP_MARKDOWN_OPCACHE\', '
				. ( in_array( 'nopcache', $my_addons, true ) ? 'false' : 'true' )
			. ' );'
			. "\n\t" . 'endif;';
		if ( markup_markdown()->put_contents( $my_cnf_screen, implode( '', $php_code ) ) ) :
			markup_markdown()->clear_cache( $my_cnf_screen );
			$redirect_url = \menu_page_url( 'markup-markdown-admin', false )
				. '&options_saved=' . ( $this->updated > 0 ? '1' : '0' );
				\wp_safe_redirect( $redirect_url, 302 );
			exit;
		endif;
		return true;
	}


	/**
	 * Force to display the accordion with page screen options area on the top right of the MMD Settings page
	 *
	 * @since 2.1.2
	 * @access public
	 *
	 * @param boolean    $show_screen Whether to show Screen Options tab. Default true.
	 * @param \WP_Screen $screen The current WP_Screen instance.
	 * @return boolean TRUE in case the panel should be shown or FALSE.
	 */
	public function mmd_screen_options_show_screen( $show_screen, $screen ) {
		if ( is_object( $screen ) && isset( $screen->id ) && 'settings_page_markup-markdown-admin' === $screen->id ) :
			return true;
		endif;
		return $show_screen;
	}


	/**
	 * Force to display the submit button inside the screen options area on the top right of the MMD Settings page
	 *
	 * @since 2.1.2
	 * @access public
	 *
	 * @param boolean    $show_submit Whether to show Screen Options submit button. Default false.
	 * @param \WP_Screen $screen The current WP_Screen instance.
	 * @return boolean TRUE in case the panel should be shown or FALSE
	 */
	public function mmd_screen_options_show_submit( $show_submit, $screen ) {
		if ( is_object( $screen ) && isset( $screen->id ) && 'settings_page_markup-markdown-admin' === $screen->id ) :
			return true;
		endif;
		return $show_submit;
	}


	/**
	 * Custom HTML inside the screen options panel
	 *
	 * @since 2.1.2
	 * @access public
	 *
	 * @param string     $panel The html code for the current panel.
	 * @param \WP_Screen $screen The current screen settings objet.
	 * @return string The modified html code for the current panel
	 */
	public function mmd_screen_settings( $panel, $screen ) {
		if ( ! is_object( $screen ) || ( isset( $screen->id ) && 'settings_page_markup-markdown-admin' !== $screen->id ) ) :
			return $panel;
		endif;
		$conf_screen = markup_markdown()->conf_blog_prefix . 'conf_screen.php';
		if ( markup_markdown()->exists( $conf_screen ) ) :
			require_once $conf_screen;
		endif;
		$html  = '<fieldset class="metabox-prefs">';
		$html .= '<legend>' . esc_html__( 'Addons used', 'markup-markdown' ) . '</legend>';
		$html .= '<style>.dashicons-mmd-helpers{margin:5px 0 0 5px}.mmd-addon-helper{display:inline}</style>';
		$html .= '<p>' . esc_html__( 'You can manually activate or deactivate specific addons.', 'markup-markdown' ) . ' ' . str_replace( '*', '<sup>*</sup>', esc_html__( 'Addons marked with * should be used with caution.', 'markup-markdown' ) ) . '</p>';
		$html .= '<ul>';
		foreach ( $this->addons->setup as $slug ) :
			if ( ! $this->addons->inst[ $slug ] ) :
				continue;
			endif;
			$addon_inst = $this->addons->inst[ $slug ];
			$html      .= '<li class="mmd-addon-helper"><label for="mmd_addon-' . $slug . '">';
			$html      .= '<input class="enable-' . $slug . '-addon" name="mmd_addons[]" id="mmd_addon-' . $slug . '" type="checkbox" value="' . $slug . '"'
				. ( ( ( ! defined( 'MARKUP_MARKDOWN_ADDONS' ) && $addon_inst->active > 0 ) || ( defined( 'MARKUP_MARKDOWN_ADDONS' ) && in_array( $slug, MARKUP_MARKDOWN_ADDONS, true ) ) ) ? ' checked="checked"' : '' ) . ' /> ';
			$html      .= $addon_inst->label . ( 'stable' !== $addon_inst->release ? ' <sup>*</sup>' : '' );
			$html      .= '<span class="dashicons dashicons-editor-help dashicons-mmd-helpers" title="' . esc_attr( $addon_inst->desc ) . '"></span>';
			$html      .= '</label></li>';
		endforeach;
		$html .= '</ul>';
		$html .= '</fieldset>';
		return $panel . $html;
	}


	/**
	 * The options page assets
	 *
	 * @since 1.9.1
	 * @access public
	 *
	 * @return void
	 */
	public function enqueue_setting_scripts() {
		$plugin_uri = markup_markdown()->plugin_uri;
		wp_enqueue_style( 'markup_markdown-options', $plugin_uri . '/assets/markup-markdown/css/plugin_options.min.css', array(), '1.0.8' );
		wp_enqueue_style( 'markup_markdown-easymde_editor', $plugin_uri . 'assets/easy-markdown-editor/dist/easymde.min.css', array(), '2.19.1011' );
		foreach ( array( 'core', 'tabs', 'draggable', 'droppable', 'sortable', 'button' ) as $jq_component ) :
			wp_enqueue_script( 'jquery-ui-' . $jq_component );
		endforeach;
		wp_enqueue_script( 'markup_markdown-options', $plugin_uri . '/assets/markup-markdown/js/plugin_options.min.js', array( 'jquery-ui-tabs' ), '1.0.8', true );
	}
}
