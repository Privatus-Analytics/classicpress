<?php
/**
 * Uninstall: remove all plugin options and the per-order tracking marker.
 *
 * @package PrivatusAnalytics
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin data for the current site.
 */
function privatus_analytics_uninstall_site() {
	global $wpdb;

	delete_option( 'privatus_analytics_settings' );
	// Left by plugin versions that had a secret key setting. No current version writes it.
	delete_option( 'privatus_analytics_ingest_key' );

	// Legacy order storage (posts + postmeta).
	delete_post_meta_by_key( '_privatus_tracked' );

	// WooCommerce High-Performance Order Storage.
	$table = $wpdb->prefix . 'wc_orders_meta';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off cleanup on uninstall.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off cleanup on uninstall.
		$wpdb->delete( $table, array( 'meta_key' => '_privatus_tracked' ), array( '%s' ) );
	}
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $privatus_blog_id ) {
		switch_to_blog( $privatus_blog_id );
		privatus_analytics_uninstall_site();
		restore_current_blog();
	}
} else {
	privatus_analytics_uninstall_site();
}
