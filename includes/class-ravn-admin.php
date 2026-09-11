<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RAVN_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_ravn_save_cookie', array( $this, 'handle_save_cookie' ) );
		add_action( 'admin_post_ravn_delete_cookie', array( $this, 'handle_delete_cookie' ) );
		add_action( 'admin_post_ravn_toggle_category', array( $this, 'handle_toggle_category' ) );
		add_action( 'admin_post_ravn_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_ravn_save_integrations', array( $this, 'handle_save_integrations' ) );
		add_action( 'admin_post_ravn_run_scan', array( $this, 'handle_run_scan' ) );
		add_action( 'admin_post_ravn_toggle_live_scan', array( $this, 'handle_toggle_live_scan' ) );
		add_action( 'admin_post_ravn_add_detected_cookie', array( $this, 'handle_add_detected_cookie' ) );
		add_action( 'admin_post_ravn_dismiss_detected_cookie', array( $this, 'handle_dismiss_detected_cookie' ) );
		add_action( 'admin_post_ravn_save_privacy_settings', array( $this, 'handle_save_privacy_settings' ) );
		add_action( 'admin_post_ravn_bump_policy_version', array( $this, 'handle_bump_policy_version' ) );
	}

	public function add_menu() {
		$new_count = RAVN_DB::count_new_detected_cookies();
		$badge     = $new_count > 0 ? ' <span class="ravn-menu-badge">' . intval( $new_count ) . '</span>' : '';

		add_menu_page(
			'Cookiemelding',
			'Cookiemelding' . $badge,
			'manage_options',
			'ravn-settings',
			array( $this, 'render_page' ),
			'dashicons-shield',
			80
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ravn-settings' ) === false ) {
			return;
		}
		wp_enqueue_style( 'ravn-admin', RAVN_PLUGIN_URL . 'assets/css/admin.css', array(), RAVN_VERSION );
	}

	/**
	 * Categorie aan/uit zetten. Bepaalt of scripts in die categorie geblokkeerd worden
	 * en of de categorie in de popup verschijnt.
	 */
	public function handle_toggle_category() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_toggle_category' );

		$id      = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$enabled = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;
		RAVN_DB::toggle_category( $id, $enabled );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=categories&updated=1' ) );
		exit;
	}

	public function handle_save_cookie() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_save_cookie' );

		$id   = isset( $_POST['cookie_id'] ) ? absint( $_POST['cookie_id'] ) : 0;
		$data = array(
			'category_id' => absint( $_POST['category_id'] ),
			'cookie_name' => sanitize_text_field( $_POST['cookie_name'] ),
			'provider'    => sanitize_text_field( $_POST['provider'] ),
			'shared_with' => sanitize_text_field( $_POST['shared_with'] ),
			'purpose'     => sanitize_textarea_field( $_POST['purpose'] ),
			'duration'    => sanitize_text_field( $_POST['duration'] ),
			'is_active'   => isset( $_POST['is_active'] ) ? 1 : 0,
		);

		if ( $id > 0 ) {
			RAVN_DB::update_cookie( $id, $data );
		} else {
			RAVN_DB::insert_cookie( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=cookies&updated=1' ) );
		exit;
	}

	public function handle_delete_cookie() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_delete_cookie' );

		$id = isset( $_GET['cookie_id'] ) ? absint( $_GET['cookie_id'] ) : 0;
		RAVN_DB::delete_cookie( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=cookies&deleted=1' ) );
		exit;
	}

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_save_settings' );

		$settings = array(
			'banner_text'     => sanitize_textarea_field( $_POST['banner_text'] ),
			'accept_label'    => sanitize_text_field( $_POST['accept_label'] ),
			'reject_label'    => sanitize_text_field( $_POST['reject_label'] ),
			'settings_label'  => sanitize_text_field( $_POST['settings_label'] ),
			'primary_color'   => sanitize_hex_color( $_POST['primary_color'] ),
			'position'        => sanitize_text_field( $_POST['position'] ),
		);
		update_option( 'ravn_settings', $settings );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=design&updated=1' ) );
		exit;
	}

	/**
	 * Slaat de trackingscript-integraties op (GA4, GTM, Meta Pixel) plus welke
	 * categorie ze vereisen. De frontend gebruikt dit om de scripts zelf te
	 * genereren, al geblokkeerd tot de juiste toestemming er is.
	 */
	public function handle_save_integrations() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_save_integrations' );

		$valid_categories = wp_list_pluck( RAVN_DB::get_categories(), 'slug' );

		$sanitize_category = function ( $value ) use ( $valid_categories ) {
			$value = sanitize_key( $value );
			return in_array( $value, $valid_categories, true ) ? $value : 'analytics';
		};

		$integrations = array(
			'ga4_id'              => sanitize_text_field( $_POST['ga4_id'] ),
			'ga4_category'        => $sanitize_category( $_POST['ga4_category'] ),
			'gtm_id'               => sanitize_text_field( $_POST['gtm_id'] ),
			'gtm_category'        => $sanitize_category( $_POST['gtm_category'] ),
			'meta_pixel_id'       => sanitize_text_field( $_POST['meta_pixel_id'] ),
			'meta_pixel_category' => $sanitize_category( $_POST['meta_pixel_category'] ),
			'consent_mode'        => isset( $_POST['consent_mode'] ) ? 1 : 0,
		);
		update_option( 'ravn_integrations', $integrations );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=integrations&updated=1' ) );
		exit;
	}

	/**
	 * Handmatige "Scan nu": doorzoekt een set pagina's op bekende
	 * trackingscripts en zet nieuwe vondsten klaar ter beoordeling.
	 */
	public function handle_run_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_run_scan' );

		require_once RAVN_PLUGIN_DIR . 'includes/class-ravn-known-cookies.php';
		require_once RAVN_PLUGIN_DIR . 'includes/class-ravn-scanner.php';
		$found = RAVN_Scanner::run_manual_scan();

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=scan&scanned=' . intval( $found ) ) );
		exit;
	}

	/**
	 * Live detectie aan/uit. Staat standaard uit: alleen aanzetten voor een
	 * periode terwijl je de site inventariseert, niet permanent laten
	 * meedraaien voor alle bezoekers.
	 */
	public function handle_toggle_live_scan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_toggle_live_scan' );

		$enabled = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;
		update_option( 'ravn_live_scan_enabled', $enabled );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=scan&updated=1' ) );
		exit;
	}

	/**
	 * Zet een gedetecteerde cookie om in een echt geregistreerde cookie,
	 * met de voorgestelde gegevens (indien bekend) als startpunt.
	 */
	public function handle_add_detected_cookie() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_add_detected_cookie' );

		require_once RAVN_PLUGIN_DIR . 'includes/class-ravn-known-cookies.php';

		$id = isset( $_POST['detected_id'] ) ? absint( $_POST['detected_id'] ) : 0;
		$detected = RAVN_DB::get_detected_cookie( $id );
		if ( ! $detected ) {
			wp_die( 'Onbekende gedetecteerde cookie.' );
		}

		$category_id = absint( $_POST['category_id'] );
		if ( ! $category_id ) {
			$suggestion = RAVN_Known_Cookies::match_cookie_name( $detected->cookie_name );
			$slug       = $suggestion ? $suggestion['category'] : 'analytics';
			$category   = RAVN_DB::get_category_by_slug( $slug );
			$category_id = $category ? $category->id : 0;
		}

		RAVN_DB::insert_cookie(
			array(
				'category_id' => $category_id,
				'cookie_name' => sanitize_text_field( $_POST['cookie_name'] ),
				'provider'    => sanitize_text_field( $_POST['provider'] ),
				'shared_with' => sanitize_text_field( $_POST['shared_with'] ),
				'purpose'     => sanitize_textarea_field( $_POST['purpose'] ),
				'duration'    => sanitize_text_field( $_POST['duration'] ),
				'is_active'   => 1,
			)
		);
		RAVN_DB::set_detected_cookie_status( $id, 'added' );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=scan&added=1' ) );
		exit;
	}

	public function handle_dismiss_detected_cookie() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_dismiss_detected_cookie' );

		$id = isset( $_POST['detected_id'] ) ? absint( $_POST['detected_id'] ) : 0;
		RAVN_DB::set_detected_cookie_status( $id, 'ignored' );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=scan&dismissed=1' ) );
		exit;
	}

	/**
	 * Bewaartermijn voor de consent-log opslaan. De dagelijkse cron-taak
	 * gebruikt deze waarde om oude regels automatisch te verwijderen.
	 */
	public function handle_save_privacy_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_save_privacy_settings' );

		$months = isset( $_POST['retention_months'] ) ? absint( $_POST['retention_months'] ) : 24;
		$months = max( 1, min( $months, 120 ) ); // tussen 1 maand en 10 jaar, ter bescherming tegen invoerfouten
		update_option( 'ravn_consent_log_retention_months', $months );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=privacy&updated=1' ) );
		exit;
	}

	/**
	 * Verhoogt de beleidsversie. Bestaande toestemming van bezoekers wordt
	 * daarmee ongeldig verklaard: de banner verschijnt bij hun volgende
	 * bezoek opnieuw, ook al staat hun oude keuze-cookie nog binnen de
	 * geldigheidstermijn.
	 */
	public function handle_bump_policy_version() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'ravn_bump_policy_version' );

		$current = (int) get_option( 'ravn_policy_version', 1 );
		update_option( 'ravn_policy_version', $current + 1 );

		wp_safe_redirect( admin_url( 'admin.php?page=ravn-settings&tab=privacy&reconsent=1' ) );
		exit;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'categories';
		require RAVN_PLUGIN_DIR . 'admin/settings-page.php';
	}
}
