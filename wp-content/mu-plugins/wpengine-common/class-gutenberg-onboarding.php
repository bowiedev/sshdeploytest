<?php
/**
 * Class for WordPress 5.0 Onboarding.
 *
 * @package wpe-gutenberg-onboarding
 */

namespace WPE;

/**
 * Gutenberg_Onboarding.
 */
class Gutenberg_Onboarding {
	const USER_META_DISMISS = 'wpe_gutenberg_notice';
	const ACTION            = 'update-wpe-gutenberg-panel';
	const NONCE_NAME        = 'wpegutenbergpanelnonce';
	const PLUGIN_CLASSIC    = 'classic-editor/classic-editor.php';
	const VERSION           = '1.0.0';

	/**
	 * Define columns.
	 *
	 * @var $columns Array of columns to render
	 */
	public static $columns = array();

	/**
	 * Define singleton.
	 *
	 * @var $instance Gutenberg_Onboarding
	 */
	private static $instance = false;

	/**
	 * Register singleton.
	 *
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() { }

	/**
	 * Clone
	 *
	 * @since 1.0.0
	 */
	private function __clone() { }

	/**
	 * Add actions and filters
	 *
	 * @uses add_action, add_filter
	 * @since 1.0.0
	 */
	private function setup() {
		// Load after WP-Admin is ready.
		add_action( 'admin_init', array( self::$instance, 'action_admin_init' ) );
	}

	/**
	 * Helper function to detect staging/development based on domain
	 *
	 * @since 1.0.0
	 */
	public static function is_staging() {
		return false !== strpos( home_url(), '.wpengine.com' );
	}

	/**
	 * Admin init
	 *
	 * Process query vars used for core updates and no-JS support.
	 * Executes on every admin page request.
	 */
	public static function action_admin_init() {
		// Update the user meta if the query argument is set.
		if ( isset( $_REQUEST['wpe_gutenberg'] ) ) {
			// Update the user meta.
			if ( '0' === $_REQUEST['wpe_gutenberg'] ) {
				// Check the Nonce.
				check_admin_referer( self::ACTION, self::NONCE_NAME );
				update_user_meta( get_current_user_id(), self::USER_META_DISMISS, true );
			} elseif ( '1' === $_REQUEST['wpe_gutenberg'] ) {
				// Not checking the Nonce since this is for debugging.
				update_user_meta( get_current_user_id(), self::USER_META_DISMISS, false );
			}
			return;
		}

		// Remove the default WordPress 4.9.X "Try Gutenberg" panel.
		remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );

		// Hook the "WP Engine Gutenberg" panel.
		add_action( 'current_screen', array( self::$instance, 'action_current_screen' ) );
		add_action( 'wp_ajax_' . self::ACTION, array( self::$instance, 'ajax_notice_handler' ) );
		add_action( 'wpe_gutenberg_panel', array( self::$instance, 'wpe_gutenberg_panel' ) );
	}

	/**
	 * Current screen.
	 *
	 * Check against a number of conditions to determine if we should exit before
	 * hooking the methods necessary for rendering the notice.
	 * Only exectutes on specified admin pages.
	 *
	 * @param Screen $screen The WordPress screen object.
	 */
	public static function action_current_screen( $screen ) {
		// Exit if the action was disabled by a subsequent filter.
		if ( ! has_action( 'wpe_gutenberg_panel' ) ) {
			return;
		}

		// Exit if the current user is not an Administrator.
		if ( ! is_super_admin() ) {
			return;
		}

		// Take no action when the Classic Editor is already active.
		if ( class_exists( 'Classic_Editor' ) ) {
			return;
		}

		// Exit if we're not looking at the dashboard.
		$display_pages = array( 'dashboard', 'dashboard-network' );
		if ( ! in_array( $screen->base, $display_pages, true ) ) {
			return;
		}

		// Populate the columns with data.
		self::setup_columns();

		// Finally, hook the methods necessary for rendering the notice.
		add_action( 'admin_enqueue_scripts', array( self::$instance, 'action_admin_enqueue_scripts' ) );

		if ( is_multisite() ) {
			add_action( 'network_admin_notices', array( self::$instance, 'action_admin_notices' ) );
		}
		add_action( 'admin_notices', array( self::$instance, 'action_admin_notices' ) );
	}

	/**
	 * Enqueue scripts styles.
	 */
	public static function action_admin_enqueue_scripts() {
		// Necessary for performing updates from the notice on the Network Admin Dashboard.
		wp_enqueue_script( 'updates' );
		// Disable this to test for no-JS compatility.
		wp_enqueue_script( 'wpe_gutenberg_onboarding_dashboard', plugins_url( 'js/wpe-gutenberg-onboarding.js', __FILE__ ), array(), self::VERSION, false );
		// Notice styles.
		wp_enqueue_style( 'wpe_gutenberg_onboarding_dashboard', plugins_url( 'css/wpe-gutenberg-onboarding.css', __FILE__ ), array(), self::VERSION, false );
		// Necessary for detecting a multisite from a single site page.
		wp_localize_script( 'wpe_gutenberg_onboarding_dashboard', 'wpeIsMultisite', is_multisite() );
	}

	/**
	 * AJAX handler to update the state of dismissible notices.
	 */
	public static function ajax_notice_handler() {
		// Verify nonce.
		check_ajax_referer( self::ACTION, self::NONCE_NAME );
		// Store it in the user meta table.
		update_user_meta( get_current_user_id(), self::USER_META_DISMISS, true );
		wp_send_json_success();
	}

	/**
	 * The rendering method for the notice.
	 */
	public static function action_admin_notices() {
		$hide = get_user_meta( get_current_user_id(), self::USER_META_DISMISS, true );
		if ( ! $hide ) {
			?>
			<div id="wpe-gutenberg-panel" class="wpe-gutenberg-panel notice">
				<?php wp_nonce_field( self::ACTION, self::NONCE_NAME, false ); ?>
				<a class="wpe-gutenberg-panel-close" href="<?php echo esc_url( wp_nonce_url( admin_url( '?wpe_gutenberg=0' ), self::ACTION, self::NONCE_NAME ) ); ?>" aria-label="<?php esc_attr_e( 'Dismiss the WPE Gutenberg panel' ); ?>"><?php esc_html_e( 'Dismiss' ); ?></a>
				<?php
				/**
				 * Add content to the WPE Gutenberg panel on the admin dashboard.
				 *
				 * To remove the WPE Gutenberg panel, use remove_action():
				 *
				 * remove_action( 'wpe_gutenberg_panel', array( Gutenberg_Onboarding::$instance, 'wpe_gutenberg_panel' ) );
				 *
				 * @since 1.0.0
				 */
				do_action( 'wpe_gutenberg_panel' );
				?>
			</div>
			<?php
		}
	}

	/**
	 * Populate the column variables.
	 */
	public static function setup_columns() {
		$col     = array();
		$plugins = get_plugins();

		// Image.
		$col['image'] = array(
			'action'  => __( 'Image' ),
			'url'     => '',
			'classes' => ' image',
		);

		// Ready for Gutenberg.
		$col['ready'] = array(
			'action'  => __( 'Start Using the New Editor' ),
			'url'     => self::smart_admin_url( 'post-new.php' ),
			'classes' => ' ready',
		);

		// Try it in Staging first!
		$col['environments'] = array(
			'action'  => __( 'Test First in Staging' ),
			'url'     => 'https://my.wpengine.com/sites/',
			'classes' => ' environments',
		);

		// Upgrade.
		if ( get_filesystem_method( array(), WP_PLUGIN_DIR ) === 'direct' ) {
			$col['upgrade'] = array(
				'action'  => ( is_multisite() ) ? __( 'Upgrade Network to WordPress 5.0' ) : __( 'Upgrade to WordPress 5.0' ),
				'url'     => wp_nonce_url( self::smart_admin_url( 'update-core.php?wpe_gutenberg=0' ), self::ACTION, self::NONCE_NAME ),
				'classes' => ( is_multisite() ) ? ' update network' : ' update',
			);
		}

		// Classic Editor.
		if ( empty( $plugins['classic-editor/classic-editor.php'] ) ) {
			// Ensure we can write to the filesystem.
			if ( get_filesystem_method( array(), WP_PLUGIN_DIR ) === 'direct' ) {
				$col['classic'] = array(
					'action'  => ( is_multisite() ) ? __( 'Network Install the Classic Editor' ) : __( 'Install the Classic Editor' ),
					'url'     => wp_nonce_url( self::smart_admin_url( 'update.php?action=install-plugin&plugin=classic-editor' ), 'install-plugin_classic-editor' ),
					'classes' => ( is_multisite() ) ? ' install-now install-classic-editor network' : ' install-now install-classic-editor',
				);
			}
		} elseif ( is_plugin_inactive( 'classic-editor/classic-editor.php' ) ) {
			$col['classic'] = array(
				'action'  => ( is_multisite() ) ? __( 'Network Activate the Classic Editor' ) : __( 'Activate the Classic Editor' ),
				'url'     => wp_nonce_url( wp_nonce_url( self::smart_admin_url( 'plugins.php?action=activate&plugin=classic-editor/classic-editor.php&from=wpe-gutenberg&wpe_gutenberg=0' ), 'activate-plugin_classic-editor/classic-editor.php' ), self::ACTION, self::NONCE_NAME ),
				'classes' => ( is_multisite() ) ? ' activate-now activate-classic-editor network' : ' activate-now activate-classic-editor',
			);
		} else {
			$col['classic'] = array(
				'action'  => __( 'The Classic Editor is activated' ),
				'url'     => wp_nonce_url( self::smart_admin_url( 'plugins.php?action=activate&plugin=classic-editor/classic-editor.php&from=wpe-gutenberg' ), 'activate-plugin_classic-editor/classic-editor.php' ),
				'classes' => ' button-disabled install-now updated-message',
			);
		}//end if

		self::$columns = $col;
	}

	/**
	 * The main render function.
	 */
	public static function wpe_gutenberg_panel() {
		global $wp_version;
		?>
		<div class="wpe-gutenberg-panel-content">
			<h2><?php esc_html_e( 'Enhance your copy, media and layout in with Gutenberg, the new WordPress editor.' ); ?></h2>

			<p class="about-description"><?php esc_html_e( 'Take your words, media, and layout in new directions with Gutenberg, the new WordPress editor.' ); ?></p>

			<hr />

			<div class="wpe-gutenberg-panel-column-container">
				<?php
				/**
				 * Check against the current version of WordPress.
				 */
				if ( version_compare( $wp_version, '5.0.0', '>=' ) ) {
					// 5.0.0
					/**
					 * Do not show the Environments panel on subdomains of wpengine.com.
					 * AKA Staging and Development environments.
					 */
					if ( ! self::is_staging() ) {
						self::render_environments_column();
					} else {
						self::render_image_column();
					}
					self::render_ready_column();
				} else {
					// 4.9.x
					/**
					 * Do not show the Environments panel on subdomains of wpengine.com.
					 * AKA Staging and Development environments.
					 */
					if ( ! self::is_staging() ) {
						self::render_environments_column();
					} else {
						self::render_image_column();
					}
					self::render_upgrade_column();
				}//end if

				// Always show the Classic Editor column.
				self::render_classic_column();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Image column.
	 */
	public static function render_image_column() {
		if ( ! self::$columns['image'] ) {
			return;
		}
		?>
			<div class="wpe-gutenberg-panel-column wpe-gutenberg-panel-image-column">
				<picture>
					<source srcset="about:blank" media="(max-width: 1024px)">
					<img src="https://s.w.org/images/core/gutenberg-screenshot.png?<?php echo esc_url( date( 'Ymd' ) ); ?>" alt="<?php esc_attr_e( 'Screenshot from the Gutenberg interface' ); ?>" />
				</picture>
			</div>
		<?php
	}

	/**
	 * Render the Upgrade column.
	 */
	public static function render_upgrade_column() {
		if ( ! self::$columns['upgrade'] ) {
			return;
		}
		?>
			<div class="wpe-gutenberg-panel-column plugin-card-gutenberg">
				<div>
					<h3><?php esc_html_e( 'Upgrade to WordPress 5.0.' ); ?></h3>

					<p>
						<?php esc_html_e( 'The new WordPress editor&mdash;WordPress 5.0&mdash;is named Gutenberg. The entire editing experience has been rebuilt to better enable media rich pages and posts. Whether you’re building your first site or write code for a living, you’ll gain enhanced flexibility by using the new "blocks" feature—blocks of content that can be manipulated like third-party page builder plugins.' ); ?>
					</p>
				</div>

				<div class="wpe-gutenberg-action">
					<p><a class="wpe-button-secondary button button-secondary button-hero<?php echo esc_attr( self::$columns['upgrade']['classes'] ); ?>" href="<?php echo esc_url( self::$columns['upgrade']['url'] ); ?>" data-name="<?php esc_attr_e( 'Upgrade' ); ?>" data-slug="upgrade"><?php echo esc_html( self::$columns['upgrade']['action'] ); ?></a></p>
					<p>
						<?php
							$learnmore = sprintf(
								/* translators: Link to https://wordpress.org/news/2018/12/bebo/ */
								__( '<a href="%s" target="blank">Learn more about this update</a>' ),
								__( 'https://wordpress.org/news/2018/12/bebo/' )
							);
							echo wp_kses_post( $learnmore );
						?>
					</p>
				</div>
			</div>
		<?php
	}

	/**
	 * Render the Ready column.
	 */
	public static function render_ready_column() {
		if ( ! self::$columns['ready'] ) {
			return;
		}
		?>
		<div class="wpe-gutenberg-panel-column plugin-card-gutenberg">

			<div>
				<h3><?php esc_html_e( 'Welcome to WordPress 5.0.' ); ?></h3>

				<p>
					<?php esc_html_e( 'The new WordPress editor&mdash;WordPress 5.0&mdash;is named Gutenberg. The entire editing experience has been rebuilt to better enable media rich pages and posts. Whether you’re building your first site or write code for a living, you’ll gain enhanced flexibility by using the new "blocks" feature—blocks of content that can be manipulated like third-party page builder plugins.' ); ?>
				</p>
			</div>

			<div class="wpe-gutenberg-action">
				<p><a class="wpe-button-primary button button-secondary button-hero<?php echo esc_attr( self::$columns['ready']['classes'] ); ?>" href="<?php echo esc_url( self::$columns['ready']['url'] ); ?>"><?php echo esc_html( self::$columns['ready']['action'] ); ?></a></p>

				<p>
					<?php
						$learnmore = sprintf(
							/* translators: Link to https://wordpress.org/gutenberg/ */
							__( '<a href="%s" target="blank">Learn more about Gutenberg</a>' ),
							__( 'https://wordpress.org/gutenberg/' )
						);
						echo wp_kses_post( $learnmore );
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Environments column.
	 */
	public static function render_environments_column() {
		if ( ! self::$columns['environments'] ) {
			return;
		}
		?>
		<div class="wpe-gutenberg-panel-column plugin-card-wpe-environments">
			<div>
				<h3><?php esc_html_e( 'Try it first. Then, upgrade.' ); ?></h3>

				<p>
					<?php esc_html_e( 'You can try out the new editor in your WP Engine development environment before upgrading your site(s). Visit the user portal to create a development environment, install WordPress 5.0, and start testing.' ); ?>
				</p>
			</div>

			<div class="wpe-gutenberg-action">
				<p><a class="wpe-button-primary button button-primary button-hero <?php echo esc_attr( self::$columns['environments']['classes'] ); ?>" href="<?php echo esc_url( self::$columns['environments']['url'] ); ?>" data-name="<?php esc_attr_e( 'Try it first' ); ?>" data-slug="wpe-environments"target="blank"><?php echo esc_html( self::$columns['environments']['action'] ); ?></a></p>
				<p>
					<?php
						$learnmore = sprintf(
							/* translators: Link to https://wpengine.com/support/staging-development-environments-wp-engine/ */
							__( '<a href="%s" target="blank">Learn more about WP Engine workflows</a>' ),
							__( 'https://wpengine.com/support/staging-development-environments-wp-engine/' )
						);
						echo wp_kses_post( $learnmore );
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Classic column.
	 */
	public static function render_classic_column() {
		global $wp_version;

		if ( ! self::$columns['classic'] ) {
			return;
		}
		?>
		<div class="wpe-gutenberg-panel-column plugin-card-classic-editor">
			<div>
				<h3><?php esc_html_e( 'Keep it Classic' ); ?></h3>

				<p>
					<?php
					// Different copy depending on version.
					if ( version_compare( $wp_version, '5.0.0', '>=' ) ) {
						esc_html_e( 'The new editor is enabled by default in this release of WordPress. If you’re not sure how compatible your current themes and plugins are, we’ve got you covered. ' );
					} else {
						esc_html_e( 'The new editor will be enabled by default in the next major release of WordPress. If you’re not sure how compatible your current themes and plugins are, we’ve got you covered. ' );
					}
						printf(
							/* translators: Link to the Classic Editor plugin page */
							wp_kses_post( __( 'Install the <a href="%s" target="blank">Classic Editor plugin</a> to keep using the current editor until you’re ready to make the switch.' ) ),
							esc_url( __( 'https://wordpress.org/plugins/classic-editor' ) )
						);
					?>
				</p>
			</div>

			<div class="wpe-gutenberg-action">
				<p><a class="wpe-button-secondary button button-secondary button-hero<?php echo esc_attr( self::$columns['classic']['classes'] ); ?>" href="<?php echo esc_url( self::$columns['classic']['url'] ); ?>" data-name="<?php esc_attr_e( 'Classic Editor' ); ?>" data-slug="classic-editor"><?php echo esc_html( self::$columns['classic']['action'] ); ?></a></p>
				<p>
					<?php
						$learnmore = sprintf(
							/* translators: Link to https://wpengine.com/support/how-to-use-the-classic-editor-plugin/ */
							__( '<a href="%s" target="blank">Learn more about the Classic Editor</a>' ),
							__( 'https://wpengine.com/support/how-to-use-the-classic-editor-plugin/' )
						);
						echo wp_kses_post( $learnmore );
					?>
				</p>
			</div>

		</div>
		<?php
	}

	/**
	 * Helper function for handling multisite URLs appropriately.
	 *
	 * @param string $path A relative URL path.
	 */
	public static function smart_admin_url( $path ) {
		if ( is_multisite() ) {
			return network_admin_url( $path );
		} else {
			return admin_url( $path );
		}
	}

}
Gutenberg_Onboarding::instance();
