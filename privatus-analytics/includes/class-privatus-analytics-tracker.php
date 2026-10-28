<?php
/**
 * Enqueues the tracker script on front-end pages.
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end snippet injection.
 */
final class Privatus_Analytics_Tracker {

	/**
	 * Queue stub so privatus.track() can be called before pa.js has loaded.
	 * Calls are replayed by pa.js. Pure JavaScript, no user input.
	 */
	const STUB = "window.privatus=window.privatus||{q:[]};['track','pageview','props','optOut','optIn','ready'].forEach(function(m){window.privatus[m]=window.privatus[m]||function(){window.privatus.q.push([m,Array.prototype.slice.call(arguments)])}});";

	/**
	 * Script handle of the tracker. ClassicPress prints it with the id privatus-analytics-js.
	 */
	const HANDLE = 'privatus-analytics';

	/**
	 * Hook into ClassicPress.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 1 );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 10, 2 );
	}

	/**
	 * Whether the current request should be tracked.
	 *
	 * @return bool
	 */
	public static function should_track() {
		$settings = Privatus_Analytics_Settings::get();
		if ( '' === $settings['site_id'] ) {
			return false;
		}
		if ( is_admin() || is_preview() || is_customize_preview() || wp_doing_ajax() || is_feed() || is_robots() ) {
			return false;
		}
		if ( is_user_logged_in() && ! empty( $settings['exclude_roles'] ) ) {
			$user = wp_get_current_user();
			if ( array_intersect( (array) $user->roles, (array) $settings['exclude_roles'] ) ) {
				return false;
			}
		}
		/**
		 * Filters whether the Privatus Analytics snippet is printed for this request.
		 *
		 * @param bool $track Whether to track.
		 */
		return (bool) apply_filters( 'privatus_analytics_should_track', true );
	}

	/**
	 * Build the script tag attributes.
	 *
	 * @return array<string, string|bool>
	 */
	public static function script_attributes() {
		$settings = Privatus_Analytics_Settings::get();
		$attrs    = array(
			'src'       => Privatus_Analytics_Settings::script_host() . '/js/pa.js',
			'defer'     => true,
			'data-site' => $settings['site_id'],
		);
		if ( 'auto' !== $settings['spa'] ) {
			$attrs['data-spa'] = $settings['spa'];
		}
		if ( $settings['manual'] ) {
			$attrs['data-manual'] = 'true';
		}
		$modules = array_values( (array) $settings['modules'] );
		if ( array( 'engage' ) !== $modules ) {
			// An empty string is meaningful: it disables all modules.
			$attrs['data-modules'] = implode( ',', $modules );
		}
		$lists = array(
			'domains'    => 'data-domains',
			'paths_skip' => 'data-exclude',
			'params'     => 'data-params',
		);
		foreach ( $lists as $key => $attribute ) {
			if ( '' !== $settings[ $key ] ) {
				$attrs[ $attribute ] = $settings[ $key ];
			}
		}
		$masks = array_filter( array_merge( explode( ',', $settings['mask'] ), self::woocommerce_masks() ) );
		if ( $masks ) {
			$attrs['data-mask'] = implode( ',', array_unique( $masks ) );
		}
		if ( $settings['canonical'] ) {
			$attrs['data-canonical'] = 'true';
		}
		if ( $settings['hash'] ) {
			$attrs['data-hash'] = 'true';
		}

		/**
		 * Filters the attributes of the Privatus Analytics script tag.
		 *
		 * @param array $attrs Attribute name => value (true for boolean attributes).
		 */
		return (array) apply_filters( 'privatus_analytics_script_attributes', $attrs );
	}

	/**
	 * Mask WooCommerce URLs that contain order ids so they never reach analytics.
	 *
	 * @return string[]
	 */
	private static function woocommerce_masks() {
		if ( ! function_exists( 'wc_get_checkout_url' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
			return array();
		}
		$masks = array();
		$pairs = array(
			array( wc_get_checkout_url(), 'order-received' ),
			array( wc_get_checkout_url(), 'order-pay' ),
			array( wc_get_page_permalink( 'myaccount' ), 'view-order' ),
		);
		foreach ( $pairs as $pair ) {
			$base = wp_parse_url( wc_get_endpoint_url( $pair[1], '', $pair[0] ), PHP_URL_PATH );
			// With plain permalinks the order id is in the query string, which pa.js already removes.
			// The path is then "/", and masking it would turn every page into the home page.
			if ( $base && '/' !== trailingslashit( $base ) ) {
				$base    = trailingslashit( $base );
				$masks[] = $base . '*->' . $base;
			}
		}
		return $masks;
	}

	/**
	 * Enqueue the tracker script with the queue stub in front of it.
	 */
	public function enqueue() {
		if ( ! self::should_track() ) {
			return;
		}
		$attrs = self::script_attributes();
		if ( empty( $attrs['src'] ) ) {
			return;
		}
		// ClassicPress builds the tag for an enqueued file itself and only offers the
		// script_loader_tag filter, so defer and the data-* settings are added there.
		wp_enqueue_script(
			self::HANDLE,
			(string) $attrs['src'],
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- the remote service versions its own script; a ver query string would split its cache.
			false
		);
		wp_add_inline_script( self::HANDLE, self::STUB, 'before' );
	}

	/**
	 * The extra attributes of the tracker tag as an escaped HTML string.
	 *
	 * @return string
	 */
	public static function attribute_html() {
		$extra = self::script_attributes();
		unset( $extra['src'], $extra['id'], $extra['type'] );
		$html = '';
		foreach ( $extra as $name => $value ) {
			$name = strtolower( (string) $name );
			if ( ! preg_match( '/^[a-z][a-z0-9-]*$/', $name ) || false === $value || null === $value ) {
				continue;
			}
			if ( true === $value ) {
				$html .= ' ' . $name;
			} else {
				$html .= sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) );
			}
		}
		return $html;
	}

	/**
	 * Add defer and the data-* settings to the tracker script tag.
	 *
	 * @param string $tag    The HTML ClassicPress built for the script, with any inline scripts.
	 * @param string $handle The script handle.
	 * @return string
	 */
	public function filter_script_tag( $tag, $handle ) {
		if ( self::HANDLE !== $handle || ! is_string( $tag ) ) {
			return $tag;
		}
		return (string) preg_replace_callback(
			'/(<script\b[^>]*\bid=([\'"])' . preg_quote( self::HANDLE, '/' ) . '-js\2)([^>]*>)/',
			function ( $m ) {
				// Drop attributes ClassicPress may have added that the settings now decide.
				$rest = preg_replace( '/\s(?:defer|async)(?=[\s>])|\sdata-wp-strategy=([\'"])[^\'"]*\1/', '', $m[3] );
				return $m[1] . self::attribute_html() . $rest;
			},
			$tag,
			1
		);
	}
}
