<?php
/**
 * Admin dashboard card that links to the Privatus Analytics dashboard.
 *
 * No iframe and no request to a remote service: the card only prints a link.
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard widget.
 */
final class Privatus_Analytics_Dashboard_Widget {

	const DEFAULT_URL = 'https://privatusanalytics.com/';

	/**
	 * Hook into ClassicPress.
	 */
	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
	}

	/**
	 * The dashboard link (https only). Falls back to the Privatus Analytics home page.
	 *
	 * @return string
	 */
	public static function dashboard_url() {
		$settings = Privatus_Analytics_Settings::get();
		$url      = (string) $settings['dashboard_url'];
		if ( '' === $url || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
			return self::DEFAULT_URL;
		}
		return $url;
	}

	/**
	 * Register the widget for users with the configured capability.
	 */
	public function add_widget() {
		$settings = Privatus_Analytics_Settings::get();
		if ( '' === $settings['site_id'] || ! current_user_can( $settings['dashboard_cap'] ) ) {
			return;
		}
		wp_add_dashboard_widget( 'privatus_analytics_widget', __( 'Privatus Analytics', 'privatus-analytics' ), array( $this, 'render' ) );
	}

	/**
	 * Render the card.
	 */
	public function render() {
		$settings = Privatus_Analytics_Settings::get();
		if ( ! current_user_can( $settings['dashboard_cap'] ) ) {
			return;
		}
		echo '<p>' . esc_html__( 'Your visits, sources, pages and conversions are counted without cookies. Open your Privatus Analytics dashboard to see the reports.', 'privatus-analytics' ) . '</p>';
		printf(
			'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> <a class="button" href="%3$s">%4$s</a></p>',
			esc_url( self::dashboard_url() ),
			esc_html__( 'Open dashboard', 'privatus-analytics' ),
			esc_url( admin_url( 'options-general.php?page=' . Privatus_Analytics_Settings::PAGE ) ),
			esc_html__( 'Plugin settings', 'privatus-analytics' )
		);
	}
}
