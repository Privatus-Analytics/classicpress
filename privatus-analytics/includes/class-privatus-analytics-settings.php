<?php
/**
 * Settings page (Settings → Privatus Analytics) built on the Settings API.
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers, renders and sanitizes the plugin settings.
 */
final class Privatus_Analytics_Settings {

	const OPTION       = 'privatus_analytics_settings';
	const GROUP        = 'privatus_analytics';
	const PAGE         = 'privatus-analytics';
	const DEFAULT_HOST = 'https://privatusstats.com';
	const MODULES      = array( 'engage', 'auto', 'vitals', 'clicks', 'ask' );
	const SPA_MODES    = array( 'auto', 'history', 'hash', 'off' );
	const CAPABILITIES = array( 'manage_options', 'edit_others_posts', 'publish_posts', 'edit_posts', 'read' );

	/**
	 * Default option values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'site_id'       => '',
			'script_host'   => self::DEFAULT_HOST,
			'spa'           => 'auto',
			'manual'        => false,
			'modules'       => array( 'engage' ),
			'domains'       => '',
			'paths_skip'    => '',
			'mask'          => '',
			'params'        => '',
			'canonical'     => false,
			'hash'          => false,
			'exclude_roles' => array( 'administrator' ),
			'woo_enabled'   => false,
			'dashboard_url' => '',
			'dashboard_cap' => 'manage_options',
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		// Settings saved by earlier builds used the key "exclude" for the excluded paths.
		if ( isset( $saved['exclude'] ) && ! isset( $saved['paths_skip'] ) ) {
			$saved['paths_skip'] = $saved['exclude'];
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Origin that serves /js/pa.js.
	 *
	 * @return string
	 */
	public static function script_host() {
		$settings = self::get();
		return $settings['script_host'] ? untrailingslashit( $settings['script_host'] ) : self::DEFAULT_HOST;
	}

	/**
	 * Hook into ClassicPress.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PRIVATUS_ANALYTICS_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Add Settings → Privatus Analytics.
	 */
	public function add_page() {
		add_options_page(
			__( 'Privatus Analytics', 'privatus-analytics' ),
			__( 'Privatus Analytics', 'privatus-analytics' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'privatus-analytics' ) . '</a>' );
		return $links;
	}

	/**
	 * Register options, sections and fields.
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section( 'privatus_general', __( 'Tracking', 'privatus-analytics' ), array( $this, 'section_general' ), self::PAGE );
		add_settings_section( 'privatus_script', __( 'Script options', 'privatus-analytics' ), '__return_false', self::PAGE );
		add_settings_section( 'privatus_exclusions', __( 'Exclusions', 'privatus-analytics' ), '__return_false', self::PAGE );
		add_settings_section( 'privatus_woo', __( 'WooCommerce', 'privatus-analytics' ), array( $this, 'section_woo' ), self::PAGE );
		add_settings_section( 'privatus_dashboard', __( 'Dashboard card', 'privatus-analytics' ), '__return_false', self::PAGE );

		$fields = array(
			array( 'site_id', __( 'Site ID', 'privatus-analytics' ), 'privatus_general', 'text', __( 'Your public site id, e.g. pa_AbC123. Find it in your Privatus Analytics dashboard under Site settings, then Setup.', 'privatus-analytics' ), 'pa_…' ),
			array( 'script_host', __( 'Script host', 'privatus-analytics' ), 'privatus_general', 'url', __( 'The address that serves the tracking script. Keep the default unless you host your own copy.', 'privatus-analytics' ), self::DEFAULT_HOST ),
			array( 'spa', __( 'SPA mode', 'privatus-analytics' ), 'privatus_script', 'select', __( 'How client-side navigations are detected.', 'privatus-analytics' ), array_combine( self::SPA_MODES, self::SPA_MODES ) ),
			array( 'manual', __( 'Manual pageviews', 'privatus-analytics' ), 'privatus_script', 'checkbox', __( 'Turn off the automatic pageview. You then call privatus.pageview() yourself.', 'privatus-analytics' ) ),
			array( 'modules', __( 'Modules', 'privatus-analytics' ), 'privatus_script', 'modules', __( 'engage = time on page and scroll depth. auto = outbound links, downloads and forms. vitals = Core Web Vitals. clicks = click maps. ask = on-site surveys.', 'privatus-analytics' ) ),
			array( 'domains', __( 'Allowed domains', 'privatus-analytics' ), 'privatus_script', 'text', __( 'Comma-separated hostnames. Visits on other hostnames, such as a staging site, are ignored.', 'privatus-analytics' ), 'example.com,www.example.com' ),
			array( 'canonical', __( 'Use canonical URL', 'privatus-analytics' ), 'privatus_script', 'checkbox', __( 'Record the rel=canonical URL instead of the address bar.', 'privatus-analytics' ) ),
			array( 'hash', __( 'Hash routing', 'privatus-analytics' ), 'privatus_script', 'checkbox', __( 'Treat #fragments as separate pages.', 'privatus-analytics' ) ),
			array( 'params', __( 'Extra query parameters', 'privatus-analytics' ), 'privatus_script', 'text', __( 'Comma-separated query parameters to keep (UTM parameters are always kept).', 'privatus-analytics' ), 'page,lang' ),
			array( 'paths_skip', __( 'Excluded paths', 'privatus-analytics' ), 'privatus_exclusions', 'text', __( 'Comma-separated globs never tracked.', 'privatus-analytics' ), '/wp-login.php,/private/*' ),
			array( 'mask', __( 'Path masks', 'privatus-analytics' ), 'privatus_exclusions', 'text', __( 'Comma-separated glob->replacement rules. WooCommerce order paths are masked automatically.', 'privatus-analytics' ), '/u/*->/u/:id' ),
			array( 'exclude_roles', __( 'Do not track logged-in users with role', 'privatus-analytics' ), 'privatus_exclusions', 'roles', __( 'Hold Ctrl/Cmd to select several roles.', 'privatus-analytics' ) ),
			array( 'woo_enabled', __( 'Track purchases', 'privatus-analytics' ), 'privatus_woo', 'checkbox', __( 'Record a purchase event (revenue, currency, item count, coupon used) in the visitor\'s browser on the thank-you page. It respects the visitor opt-out.', 'privatus-analytics' ) ),
			array( 'dashboard_url', __( 'Dashboard link', 'privatus-analytics' ), 'privatus_dashboard', 'url', __( 'An https:// link to your Privatus Analytics dashboard or a shared dashboard link. The admin dashboard shows a card with a button that opens it.', 'privatus-analytics' ), 'https://privatusanalytics.com/' ),
			array( 'dashboard_cap', __( 'Card visible to', 'privatus-analytics' ), 'privatus_dashboard', 'select', __( 'Users need this capability to see the card.', 'privatus-analytics' ), array_combine( self::CAPABILITIES, self::CAPABILITIES ) ),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				'privatus_' . $field[0],
				$field[1],
				array( $this, 'render_field' ),
				self::PAGE,
				$field[2],
				array(
					'key'         => $field[0],
					'type'        => $field[3],
					'description' => $field[4],
					'extra'       => isset( $field[5] ) ? $field[5] : null,
					'label_for'   => in_array( $field[3], array( 'modules' ), true ) ? null : 'privatus_' . $field[0],
				)
			);
		}
	}

	/**
	 * Intro text for the tracking section.
	 */
	public function section_general() {
		echo '<p>' . esc_html__( 'Privatus Analytics uses no cookies and stores nothing on visitors\' devices.', 'privatus-analytics' ) . '</p>';
		printf(
			'<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a> | <a href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a></p>',
			esc_html__( 'Once you save a site id, visitors\' browsers load a script from the Privatus Analytics service and send it anonymous page view data. Nothing is sent before that.', 'privatus-analytics' ),
			esc_url( 'https://privatusanalytics.com/legal/terms' ),
			esc_html__( 'Terms of service', 'privatus-analytics' ),
			esc_url( 'https://privatusanalytics.com/legal/privacy' ),
			esc_html__( 'Privacy policy', 'privatus-analytics' )
		);
	}

	/**
	 * Intro text for the WooCommerce section.
	 */
	public function section_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<p>' . esc_html__( 'WooCommerce is not active. These settings take effect once it is.', 'privatus-analytics' ) . '</p>';
		}
		echo '<p>' . esc_html__( 'No order ids, emails, names or addresses are ever sent.', 'privatus-analytics' ) . '</p>';
	}

	/**
	 * Render one field.
	 *
	 * @param array<string, mixed> $args Field args.
	 */
	public function render_field( $args ) {
		$settings = self::get();
		$key      = $args['key'];
		$id       = 'privatus_' . $key;
		$name     = self::OPTION . '[' . $key . ']';
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

		switch ( $args['type'] ) {
			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html( $args['description'] )
				);
				return;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( (array) $args['extra'] as $option_value => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option_value ), selected( $value, $option_value, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'modules':
				echo '<fieldset>';
				foreach ( self::MODULES as $module ) {
					printf(
						'<label style="margin-right:1em"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s /> %2$s</label>',
						esc_attr( $name ),
						esc_attr( $module ),
						checked( in_array( $module, (array) $value, true ), true, false )
					);
				}
				echo '</fieldset>';
				break;

			case 'roles':
				printf( '<select id="%1$s" name="%2$s[]" multiple size="6">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( wp_roles()->get_names() as $role => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $role ),
						selected( in_array( $role, (array) $value, true ), true, false ),
						esc_html( translate_user_role( $label ) )
					);
				}
				echo '</select>';
				break;

			case 'url':
			case 'text':
			default:
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" placeholder="%5$s" />',
					esc_attr( 'url' === $args['type'] ? 'url' : 'text' ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( is_array( $value ) ? '' : (string) $value ),
					esc_attr( (string) $args['extra'] )
				);
				break;
		}

		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<form action="' . esc_url( admin_url( 'options.php' ) ) . '" method="post">';
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE );
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Normalise a comma-separated list.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function clean_list( $value ) {
		$items = array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( (string) $value ) ) ) );
		return implode( ',', array_filter( $items, 'strlen' ) );
	}

	/**
	 * Sanitize an origin/prefix URL (http or https, no trailing slash).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function clean_host( $value ) {
		$url = esc_url_raw( trim( (string) $value ), array( 'https', 'http' ) );
		return $url ? untrailingslashit( $url ) : '';
	}

	/**
	 * Sanitize callback for the main option.
	 *
	 * @param mixed $input Submitted values.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$site_id = isset( $input['site_id'] ) ? trim( sanitize_text_field( $input['site_id'] ) ) : '';
		if ( '' === $site_id || preg_match( '/^pa_[A-Za-z0-9_-]{1,64}$/', $site_id ) ) {
			$out['site_id'] = $site_id;
		} else {
			add_settings_error( self::OPTION, 'site_id', __( 'The site id must look like pa_XXXX.', 'privatus-analytics' ) );
			$out['site_id'] = self::get()['site_id'];
		}

		$script_host        = isset( $input['script_host'] ) ? self::clean_host( $input['script_host'] ) : '';
		$out['script_host'] = $script_host ? $script_host : self::DEFAULT_HOST;

		$out['spa'] = isset( $input['spa'] ) && in_array( $input['spa'], self::SPA_MODES, true ) ? $input['spa'] : 'auto';

		foreach ( array( 'manual', 'canonical', 'hash', 'woo_enabled' ) as $flag ) {
			$out[ $flag ] = ! empty( $input[ $flag ] );
		}

		$modules        = isset( $input['modules'] ) ? array_map( 'sanitize_key', (array) $input['modules'] ) : array();
		$out['modules'] = array_values( array_intersect( self::MODULES, $modules ) );

		foreach ( array( 'domains', 'paths_skip', 'mask', 'params' ) as $list ) {
			$out[ $list ] = isset( $input[ $list ] ) ? self::clean_list( $input[ $list ] ) : '';
		}

		$roles                = isset( $input['exclude_roles'] ) ? array_map( 'sanitize_key', (array) $input['exclude_roles'] ) : array();
		$out['exclude_roles'] = array_values( array_intersect( array_keys( wp_roles()->get_names() ), $roles ) );

		$dashboard_url = isset( $input['dashboard_url'] ) ? esc_url_raw( trim( (string) $input['dashboard_url'] ), array( 'https' ) ) : '';
		if ( '' !== $dashboard_url && 'https' !== wp_parse_url( $dashboard_url, PHP_URL_SCHEME ) ) {
			$dashboard_url = '';
		}
		if ( '' === $dashboard_url && ! empty( $input['dashboard_url'] ) ) {
			add_settings_error( self::OPTION, 'dashboard_url', __( 'The dashboard link must be an https:// URL.', 'privatus-analytics' ) );
		}
		$out['dashboard_url'] = $dashboard_url;

		$out['dashboard_cap'] = isset( $input['dashboard_cap'] ) && in_array( $input['dashboard_cap'], self::CAPABILITIES, true ) ? $input['dashboard_cap'] : 'manage_options';

		return $out;
	}
}
