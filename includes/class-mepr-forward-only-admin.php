<?php
/**
 * Admin settings UI under MemberPress.
 *
 * @package MemberPress_Forward_Only
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page and assets.
 */
final class Mepr_Forward_Only_Admin {

	/**
	 * Settings group id.
	 */
	private const GROUP = 'mepr_forward_only_settings_group';

	/**
	 * Menu slug.
	 */
	private const MENU_SLUG = 'memberpress-forward-only';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'mepr_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * MemberPress submenu.
	 *
	 * @return void
	 */
	public static function register_menu(): void {
		$capability = class_exists( 'MeprUtils' ) ? MeprUtils::get_mepr_admin_capability() : 'manage_options';

		add_submenu_page(
			'memberpress',
			__( 'Forward-Only Access', 'memberpress-forward-only' ),
			__( 'Forward-Only Access', 'memberpress-forward-only' ),
			$capability,
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register option and sanitization.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			self::GROUP,
			Mepr_Forward_Only_Bootstrap::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize saved settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$prev = get_option( Mepr_Forward_Only_Bootstrap::OPTION_NAME, array() );
		if ( ! is_array( $prev ) ) {
			$prev = array();
		}

		$rule_text = isset( $input['rule_ids_text'] ) ? sanitize_textarea_field( wp_unslash( $input['rule_ids_text'] ) ) : '';
		$rule_ids  = array();
		if ( '' !== $rule_text ) {
			$rule_text = str_replace( array( "\r\n", "\r" ), "\n", $rule_text );
			$parts     = preg_split( '/[\n,]+/', $rule_text, -1, PREG_SPLIT_NO_EMPTY );
			if ( is_array( $parts ) ) {
				foreach ( $parts as $p ) {
					$n = absint( trim( $p ) );
					if ( $n > 0 ) {
						$rule_ids[] = $n;
					}
				}
			}
			$rule_ids = array_values( array_unique( $rule_ids ) );
		}

		if ( ! isset( $input['message'] ) ) {
			$message = isset( $prev['message'] ) && is_string( $prev['message'] )
				? $prev['message']
				: Mepr_Forward_Only_Bootstrap::default_message_html();
		} else {
			$message = wp_kses_post( wp_unslash( $input['message'] ) );
			if ( '' === trim( $message ) ) {
				$message = ( isset( $prev['message'] ) && is_string( $prev['message'] ) && '' !== trim( $prev['message'] ) )
					? $prev['message']
					: Mepr_Forward_Only_Bootstrap::default_message_html();
			}
		}

		$excluded_post_types = isset( $input['excluded_post_types'] ) ? sanitize_textarea_field( wp_unslash( $input['excluded_post_types'] ) ) : '';
		$excluded_categories = isset( $input['excluded_categories'] ) ? sanitize_textarea_field( wp_unslash( $input['excluded_categories'] ) ) : '';

		Mepr_Forward_Only_Settings::flush_cache();

		return array(
			'rule_ids'            => $rule_ids,
			'message'             => $message,
			'excluded_post_types' => $excluded_post_types,
			'excluded_categories' => $excluded_categories,
		);
	}

	/**
	 * Enqueue admin styles on our page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_style(
			'mepr-forward-only-admin',
			plugins_url( 'assets/css/admin.css', MEPR_FORWARD_ONLY_PLUGIN_FILE ),
			array(),
			MEPR_FORWARD_ONLY_VERSION
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( class_exists( 'MeprUtils' ) ? MeprUtils::get_mepr_admin_capability() : 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'memberpress-forward-only' ) );
		}

		$settings = Mepr_Forward_Only_Settings::get();
		$rule_ids = isset( $settings['rule_ids'] ) && is_array( $settings['rule_ids'] ) ? $settings['rule_ids'] : array();
		$rule_text = ! empty( $rule_ids ) ? implode( "\n", array_map( 'intval', $rule_ids ) ) : '';

		$message = isset( $settings['message'] ) ? $settings['message'] : Mepr_Forward_Only_Bootstrap::default_message_html();
		$excluded_post_types = isset( $settings['excluded_post_types'] ) ? (string) $settings['excluded_post_types'] : '';
		$excluded_categories = isset( $settings['excluded_categories'] ) ? (string) $settings['excluded_categories'] : '';

		$rules_list_url = admin_url( 'edit.php?post_type=memberpressrule' );

		if ( ! empty( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'memberpress-forward-only' ) . '</p></div>';
		}

		?>
		<div class="wrap mepr-forward-only-wrap">
			<h1><?php echo esc_html__( 'Forward-Only Access', 'memberpress-forward-only' ); ?></h1>
			<p class="description">
				<?php echo esc_html__( 'Members only see content published on or after their membership start date (earliest active transaction). Administrators are never blocked.', 'memberpress-forward-only' ); ?>
			</p>

			<form method="post" action="options.php" class="mepr-forward-only-form">
				<?php settings_fields( self::GROUP ); ?>

				<div class="mepr-forward-only-grid">
					<div class="mepr-forward-only-card">
						<h2><?php esc_html_e( 'Rules', 'memberpress-forward-only' ); ?></h2>
						<p class="description">
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s: URL to MemberPress rules screen */
									__( 'Limit enforcement to specific <a href="%s">MemberPress rules</a>. Leave empty to apply to all rules.', 'memberpress-forward-only' ),
									esc_url( $rules_list_url )
								)
							);
							?>
						</p>
						<label for="mepr-forward-only-rule-ids" class="screen-reader-text"><?php esc_html_e( 'Rule IDs', 'memberpress-forward-only' ); ?></label>
						<textarea
							id="mepr-forward-only-rule-ids"
							name="<?php echo esc_attr( Mepr_Forward_Only_Bootstrap::OPTION_NAME ); ?>[rule_ids_text]"
							rows="6"
							class="large-text code"
							placeholder="<?php echo esc_attr__( 'Example: one rule ID per line, or comma-separated', 'memberpress-forward-only' ); ?>"
						><?php echo esc_textarea( $rule_text ); ?></textarea>
					</div>

					<div class="mepr-forward-only-card">
						<h2><?php esc_html_e( 'Message', 'memberpress-forward-only' ); ?></h2>
						<p class="description"><?php esc_html_e( 'HTML shown when a member is blocked. Use %signup_date% for the formatted membership start date.', 'memberpress-forward-only' ); ?></p>
						<?php
						wp_editor(
							$message,
							'mepr_forward_only_message',
							array(
								'textarea_name' => Mepr_Forward_Only_Bootstrap::OPTION_NAME . '[message]',
								'textarea_rows' => 12,
								'media_buttons' => false,
								'teeny'         => true,
								'quicktags'     => true,
							)
						);
						?>
					</div>

					<div class="mepr-forward-only-card">
						<h2><?php esc_html_e( 'Exclusions', 'memberpress-forward-only' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Optional: post type names and category slugs to never apply forward-only checks to.', 'memberpress-forward-only' ); ?></p>

						<p>
							<label for="mepr-forward-only-exclude-types"><strong><?php esc_html_e( 'Excluded post types', 'memberpress-forward-only' ); ?></strong></label>
						</p>
						<textarea
							id="mepr-forward-only-exclude-types"
							name="<?php echo esc_attr( Mepr_Forward_Only_Bootstrap::OPTION_NAME ); ?>[excluded_post_types]"
							rows="3"
							class="large-text code"
							placeholder="<?php echo esc_attr__( 'e.g. page, announcement', 'memberpress-forward-only' ); ?>"
						><?php echo esc_textarea( $excluded_post_types ); ?></textarea>

						<p>
							<label for="mepr-forward-only-exclude-cats"><strong><?php esc_html_e( 'Excluded category slugs', 'memberpress-forward-only' ); ?></strong></label>
						</p>
						<textarea
							id="mepr-forward-only-exclude-cats"
							name="<?php echo esc_attr( Mepr_Forward_Only_Bootstrap::OPTION_NAME ); ?>[excluded_categories]"
							rows="3"
							class="large-text code"
							placeholder="<?php echo esc_attr__( 'e.g. announcements, site-updates', 'memberpress-forward-only' ); ?>"
						><?php echo esc_textarea( $excluded_categories ); ?></textarea>
					</div>

					<div class="mepr-forward-only-card mepr-forward-only-card--help">
						<h2><?php esc_html_e( 'Shortcode: mepr_forward_link', 'memberpress-forward-only' ); ?></h2>
						<p class="description"><?php esc_html_e( 'On dashboards or landing pages, show archive links only when the target page’s publish date is on or before the member’s signup date.', 'memberpress-forward-only' ); ?></p>
						<pre class="mepr-forward-only-code"><code>[mepr_forward_link page_id="101"]
&lt;a href="/april-2026"&gt;April 2026 Archive&lt;/a&gt;
[/mepr_forward_link]</code></pre>
						<p class="description">
							<?php esc_html_e( 'Optional: membership_ids="1,2" scopes the signup date to those membership products.', 'memberpress-forward-only' ); ?>
						</p>
					</div>
				</div>

				<?php submit_button(); ?>
			</form>

			<?php if ( defined( 'MEPR_FORWARD_ONLY_RULE_IDS' ) || defined( 'MEPR_FORWARD_ONLY_MESSAGE' ) ) : ?>
				<div class="notice notice-info inline" style="margin-top: 1.5em;">
					<p>
						<?php esc_html_e( 'wp-config.php constants MEPR_FORWARD_ONLY_RULE_IDS and/or MEPR_FORWARD_ONLY_MESSAGE are defined and override these settings.', 'memberpress-forward-only' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
