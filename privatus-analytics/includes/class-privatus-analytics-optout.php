<?php
/**
 * [privatus_optout] shortcode: lets visitors opt out of (or back into) analytics.
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opt-out toggle shortcode.
 */
final class Privatus_Analytics_Optout {

	const HANDLE = 'privatus-analytics-optout';

	/**
	 * Hook into ClassicPress.
	 */
	public function register() {
		add_shortcode( 'privatus_optout', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_script' ) );
	}

	/**
	 * Register (not enqueue) the toggle script; it is enqueued only where the shortcode is used.
	 */
	public function register_script() {
		wp_register_script( self::HANDLE, PRIVATUS_ANALYTICS_URL . 'assets/optout.js', array(), PRIVATUS_ANALYTICS_VERSION, true );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'opt_out_label' => __( 'Opt out of anonymous analytics', 'privatus-analytics' ),
				'opt_in_label'  => __( 'Opt back in to anonymous analytics', 'privatus-analytics' ),
				'opted_out'     => __( 'You are opted out. This browser will not be counted.', 'privatus-analytics' ),
				'opted_in'      => __( 'This site counts visits anonymously, without cookies.', 'privatus-analytics' ),
			),
			$atts,
			'privatus_optout'
		);

		if ( ! wp_script_is( self::HANDLE, 'registered' ) ) {
			$this->register_script();
		}
		wp_enqueue_script( self::HANDLE );

		return sprintf(
			'<div class="privatus-optout" data-opt-out-label="%1$s" data-opt-in-label="%2$s" data-opted-out="%3$s" data-opted-in="%4$s"><p class="privatus-optout__status" aria-live="polite">%4$s</p><button type="button" class="privatus-optout__button wp-element-button" aria-pressed="false">%1$s</button><noscript><p>%5$s</p></noscript></div>',
			esc_attr( $atts['opt_out_label'] ),
			esc_attr( $atts['opt_in_label'] ),
			esc_attr( $atts['opted_out'] ),
			esc_attr( $atts['opted_in'] ),
			esc_html__( 'JavaScript is disabled, so this browser is not counted.', 'privatus-analytics' )
		);
	}
}
