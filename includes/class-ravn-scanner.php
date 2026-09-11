<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RAVN_Scanner {

	const MAX_PAGES_PER_SCAN = 12;

	/**
	 * Handmatige scan: haalt een aantal pagina's van de site op en zoekt in
	 * de HTML naar bekende trackingscripts. Voegt treffers toe aan de
	 * "gedetecteerde cookies"-tabel, met voorgestelde categorie/aanbieder.
	 * Retourneert het aantal nieuw gevonden items.
	 */
	public static function run_manual_scan() {
		$urls  = self::get_urls_to_scan();
		$found = 0;

		foreach ( $urls as $url ) {
			$html = self::fetch_html( $url );
			if ( ! $html ) {
				continue;
			}
			$found += self::scan_html_for_signatures( $html );
		}

		update_option( 'ravn_last_scan', current_time( 'mysql' ) );
		return $found;
	}

	/**
	 * Verzamelt een representatieve set URL's: home + tot 11 recente
	 * gepubliceerde pagina's/berichten. Geen volledige site-crawl, om
	 * timeouts op grote sites te voorkomen.
	 */
	private static function get_urls_to_scan() {
		$urls = array( home_url( '/' ) );

		$posts = get_posts(
			array(
				'post_type'      => array( 'page', 'post' ),
				'post_status'    => 'publish',
				'posts_per_page' => self::MAX_PAGES_PER_SCAN - 1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		foreach ( $posts as $post_id ) {
			$urls[] = get_permalink( $post_id );
		}

		return array_unique( $urls );
	}

	private static function fetch_html( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 10,
				'sslverify' => true,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Doorzoekt HTML-inhoud op bekende scriptdomeinen/patronen en registreert
	 * de daarbij horende cookies als "gedetecteerd, nog niet ingedeeld".
	 */
	private static function scan_html_for_signatures( $html ) {
		$new_count  = 0;
		$signatures = RAVN_Known_Cookies::script_signatures();

		foreach ( $signatures as $sig ) {
			if ( false === strpos( $html, $sig['match'] ) ) {
				continue;
			}

			if ( empty( $sig['cookies'] ) ) {
				// Dienst herkend (bijv. GTM-container), maar geen vaste cookienaam om te loggen.
				continue;
			}

			foreach ( $sig['cookies'] as $cookie_pattern ) {
				$inserted = RAVN_DB::upsert_detected_cookie( $cookie_pattern, 'scan', $sig['label'] );
				if ( $inserted ) {
					$new_count++;
				}
			}
		}

		return $new_count;
	}

	/**
	 * Verwerkt cookienamen die de live-detectiescript in de browser heeft
	 * waargenomen (echt gezette cookies, dus 100% zeker aanwezig).
	 */
	public static function register_live_detected_cookies( array $cookie_names ) {
		$ignored = self::get_ignored_cookie_names();
		$new_count = 0;

		foreach ( $cookie_names as $name ) {
			$name = sanitize_text_field( $name );
			if ( '' === $name || in_array( $name, $ignored, true ) ) {
				continue;
			}
			$inserted = RAVN_DB::upsert_detected_cookie( $name, 'live', null );
			if ( $inserted ) {
				$new_count++;
			}
		}
		return $new_count;
	}

	/**
	 * Cookies die de plugin zelf zet, of standaard WP-cookies die al via de
	 * ingebouwde database herkend worden, hoeven niet los als "nieuw" te
	 * verschijnen wanneer ze al als registered cookie bekend zijn.
	 */
	private static function get_ignored_cookie_names() {
		return array( 'ravn_consent' );
	}
}
