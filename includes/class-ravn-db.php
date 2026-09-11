<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alles rondom database: aanmaken tabellen en CRUD-helpers.
 */
class RAVN_DB {

	public static function table_categories() {
		global $wpdb;
		return $wpdb->prefix . 'ravn_categories';
	}

	public static function table_cookies() {
		global $wpdb;
		return $wpdb->prefix . 'ravn_cookies';
	}

	public static function table_consent_log() {
		global $wpdb;
		return $wpdb->prefix . 'ravn_consent_log';
	}

	public static function table_detected_cookies() {
		global $wpdb;
		return $wpdb->prefix . 'ravn_detected_cookies';
	}

	/**
	 * Maak de benodigde tabellen aan (of update ze) via dbDelta.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql_categories = "CREATE TABLE " . self::table_categories() . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(60) NOT NULL,
			name VARCHAR(120) NOT NULL,
			description TEXT NULL,
			is_required TINYINT(1) NOT NULL DEFAULT 0,
			is_enabled TINYINT(1) NOT NULL DEFAULT 1,
			sort_order INT NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		$sql_cookies = "CREATE TABLE " . self::table_cookies() . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			category_id BIGINT UNSIGNED NOT NULL,
			cookie_name VARCHAR(190) NOT NULL,
			provider VARCHAR(190) NULL,
			shared_with VARCHAR(190) NULL,
			purpose TEXT NULL,
			duration VARCHAR(100) NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY category_id (category_id)
		) $charset_collate;";

		$sql_log = "CREATE TABLE " . self::table_consent_log() . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			visitor_hash VARCHAR(64) NOT NULL,
			categories_json TEXT NOT NULL,
			consent_given_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY visitor_hash (visitor_hash)
		) $charset_collate;";

		$sql_detected = "CREATE TABLE " . self::table_detected_cookies() . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			cookie_name VARCHAR(190) NOT NULL,
			source VARCHAR(20) NOT NULL DEFAULT 'live',
			detected_via VARCHAR(190) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'new',
			first_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY cookie_name (cookie_name)
		) $charset_collate;";

		dbDelta( $sql_categories );
		dbDelta( $sql_cookies );
		dbDelta( $sql_log );
		dbDelta( $sql_detected );
	}

	/**
	 * Zet vier standaardcategorieën klaar zodat de plugin direct bruikbaar is.
	 */
	public static function maybe_seed_default_categories() {
		global $wpdb;
		$table = self::table_categories();
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		if ( $count > 0 ) {
			return;
		}

		$defaults = array(
			array( 'slug' => 'necessary', 'name' => 'Noodzakelijk', 'description' => 'Nodig om de website goed te laten werken. Kan niet worden uitgezet.', 'is_required' => 1, 'sort_order' => 1 ),
			array( 'slug' => 'functional', 'name' => 'Functioneel', 'description' => 'Onthoudt voorkeuren om de website prettiger te gebruiken.', 'is_required' => 0, 'sort_order' => 2 ),
			array( 'slug' => 'analytics', 'name' => 'Analytisch', 'description' => 'Meet websitegebruik om de site te verbeteren.', 'is_required' => 0, 'sort_order' => 3 ),
			array( 'slug' => 'marketing', 'name' => 'Marketing', 'description' => 'Wordt gebruikt om relevante advertenties te tonen.', 'is_required' => 0, 'sort_order' => 4 ),
		);

		foreach ( $defaults as $cat ) {
			$wpdb->insert(
				$table,
				array(
					'slug'        => $cat['slug'],
					'name'        => $cat['name'],
					'description' => $cat['description'],
					'is_required' => $cat['is_required'],
					'is_enabled'  => 1,
					'sort_order'  => $cat['sort_order'],
				)
			);
		}
	}

	public static function get_categories( $only_enabled = false ) {
		global $wpdb;
		$table = self::table_categories();
		$where = $only_enabled ? 'WHERE is_enabled = 1' : '';
		return $wpdb->get_results( "SELECT * FROM $table $where ORDER BY sort_order ASC" );
	}

	public static function get_category_by_slug( $slug ) {
		global $wpdb;
		$table = self::table_categories();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE slug = %s", $slug ) );
	}

	public static function get_cookies_by_category( $category_id ) {
		global $wpdb;
		$table = self::table_cookies();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE category_id = %d AND is_active = 1 ORDER BY cookie_name ASC", $category_id )
		);
	}

	public static function get_all_cookies_grouped() {
		$categories = self::get_categories();
		$result     = array();
		foreach ( $categories as $cat ) {
			$result[] = array(
				'category' => $cat,
				'cookies'  => self::get_cookies_by_category( $cat->id ),
			);
		}
		return $result;
	}

	public static function insert_cookie( $data ) {
		global $wpdb;
		return $wpdb->insert( self::table_cookies(), $data );
	}

	public static function update_cookie( $id, $data ) {
		global $wpdb;
		return $wpdb->update( self::table_cookies(), $data, array( 'id' => $id ) );
	}

	public static function delete_cookie( $id ) {
		global $wpdb;
		return $wpdb->delete( self::table_cookies(), array( 'id' => $id ) );
	}

	public static function toggle_category( $id, $enabled ) {
		global $wpdb;
		return $wpdb->update( self::table_categories(), array( 'is_enabled' => $enabled ? 1 : 0 ), array( 'id' => $id ) );
	}

	public static function log_consent( $visitor_hash, $categories ) {
		global $wpdb;
		$wpdb->insert(
			self::table_consent_log(),
			array(
				'visitor_hash'    => $visitor_hash,
				'categories_json' => wp_json_encode( $categories ),
			)
		);
	}

	/**
	 * Verwijdert consent-logregels ouder dan de ingestelde bewaartermijn.
	 * AVG staat niet toe persoonsgegevens (een gehashte IP+useragent valt
	 * daaronder) langer te bewaren dan nodig voor het doel (bewijsvoering).
	 */
	public static function purge_old_consent_logs( $retention_months ) {
		global $wpdb;
		$table = self::table_consent_log();
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table WHERE consent_given_at < DATE_SUB(NOW(), INTERVAL %d MONTH)",
				absint( $retention_months )
			)
		);
	}

	/**
	 * Is deze cookienaam al als "echte" cookie geregistreerd? Voorkomt dat
	 * al ingedeelde cookies steeds opnieuw als "nieuw gevonden" verschijnen.
	 */
	public static function is_cookie_already_registered( $cookie_name ) {
		global $wpdb;
		$table = self::table_cookies();
		$count = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE cookie_name = %s", $cookie_name )
		);
		return $count > 0;
	}

	/**
	 * Voegt een gedetecteerde cookie toe, of werkt 'last_seen' bij als hij al
	 * bekend is. Retourneert true als het een nieuwe, nog niet geregistreerde
	 * cookie is (relevant voor tellingen in de UI).
	 */
	public static function upsert_detected_cookie( $cookie_name, $source, $detected_via = null ) {
		global $wpdb;

		if ( self::is_cookie_already_registered( $cookie_name ) ) {
			return false;
		}

		$table    = self::table_detected_cookies();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table WHERE cookie_name = %s", $cookie_name ) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'last_seen' => current_time( 'mysql' ) ),
				array( 'id' => $existing->id )
			);
			return false;
		}

		$wpdb->insert(
			$table,
			array(
				'cookie_name'  => $cookie_name,
				'source'       => $source,
				'detected_via' => $detected_via,
				'status'       => 'new',
			)
		);
		return true;
	}

	public static function get_detected_cookies( $status = 'new' ) {
		global $wpdb;
		$table = self::table_detected_cookies();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table WHERE status = %s ORDER BY last_seen DESC", $status )
		);
	}

	public static function set_detected_cookie_status( $id, $status ) {
		global $wpdb;
		return $wpdb->update( self::table_detected_cookies(), array( 'status' => $status ), array( 'id' => $id ) );
	}

	public static function get_detected_cookie( $id ) {
		global $wpdb;
		$table = self::table_detected_cookies();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public static function count_new_detected_cookies() {
		global $wpdb;
		$table = self::table_detected_cookies();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'new'" );
	}
}
