<?php
/**
 * Suggested privacy policy text (Settings → Privacy → Policy guide).
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Privacy policy helper.
 */
final class Privatus_Analytics_Privacy {

	/**
	 * Hook into ClassicPress.
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'add_policy_content' ) );
	}

	/**
	 * Page that explains how Privatus Analytics handles privacy.
	 */
	const PRIVACY_URL = 'https://privatusanalytics.com/privacy-analytics';

	/**
	 * Register the suggested policy text.
	 */
	public function add_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content( __( 'Privatus Analytics', 'privatus-analytics' ), self::policy_content() );
	}

	/**
	 * The suggested policy text, as safe HTML.
	 *
	 * Paragraphs with the class privacy-policy-tutorial are notes for the site owner.
	 * ClassicPress leaves them out when the text is copied.
	 *
	 * @return string
	 */
	public static function policy_content() {
		$link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( self::PRIVACY_URL ), esc_html( 'Privatus Analytics' ) );

		$content  = '<h2>' . esc_html__( 'Analytics', 'privatus-analytics' ) . '</h2>';
		$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'Suggested text, added by the Privatus Analytics plugin. Change it to match your site.', 'privatus-analytics' ) . '</p>';
		$content .= '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'privatus-analytics' ) . ' </strong>';
		$content .= sprintf(
			/* translators: %s: the name "Privatus Analytics", linked to the page that explains how it handles privacy. */
			esc_html__( 'We use %s to count visits to this site and to see which pages people read. It does not use cookies. It does not follow you from one website to another, or from one day to the next.', 'privatus-analytics' ),
			$link
		) . '</p>';
		$content .= '<p>' . esc_html__( 'For each page you open, we record the page address, the website that sent you here, your language, your country, your type of browser and device, and the rough size of your screen.', 'privatus-analytics' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Your country comes from a country code that Cloudflare adds to each visit. Cloudflare is the network company that passes the visits on to Privatus Analytics. Privatus Analytics does not look up where you are, and it keeps nothing more exact than the country.', 'privatus-analytics' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Your IP address is the number that lets the internet reach your device. Your IP address and your browser details are used for a moment, in memory only. They are mixed with a random value that changes every day. This lets us count each visitor once a day without knowing who they are. They also help to spot robots. Your IP address and browser details are never stored.', 'privatus-analytics' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Privatus Analytics keeps the data in the United States.', 'privatus-analytics' ) . '</p>';
		$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'Keep the next paragraph only if you turned on WooCommerce purchase tracking in Settings, Privatus Analytics.', 'privatus-analytics' ) . '</p>';
		$content .= '<p>' . esc_html__( 'When you buy something in our shop, we record the total price, the currency, the number of items and whether a coupon was used. We never send your name, email address, postal address or order number.', 'privatus-analytics' ) . '</p>';
		$content .= '<p class="privacy-policy-tutorial">' . esc_html__( 'Keep the next paragraph only if you added the [privatus_optout] button to your site.', 'privatus-analytics' ) . '</p>';
		$content .= '<p>' . esc_html__( 'You can ask us not to count your visits. Use the button on this site to do this. Your browser then keeps one small note for this site, called privatus_optout. It only says that you do not want to be counted. It is kept in your browser\'s local storage, not in a cookie. This note is the only thing Privatus Analytics ever stores on your device. It works only in the browser where you clicked the button. If you clear this site\'s data in your browser, the note is gone and your visits are counted again.', 'privatus-analytics' ) . '</p>';

		return wp_kses_post( $content );
	}
}
