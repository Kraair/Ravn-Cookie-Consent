<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RAVN_Frontend {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_banner' ) );
		add_action( 'wp_ajax_ravn_log_consent', array( $this, 'ajax_log_consent' ) );
		add_action( 'wp_ajax_nopriv_ravn_log_consent', array( $this, 'ajax_log_consent' ) );
		add_shortcode( 'ravn_cookieverklaring', array( $this, 'render_cookieverklaring_shortcode' ) );
		add_action( 'wp_ajax_ravn_report_cookies', array( $this, 'ajax_report_cookies' ) );
		add_action( 'wp_ajax_nopriv_ravn_report_cookies', array( $this, 'ajax_report_cookies' ) );

		// Consent Mode default moet zo vroeg mogelijk in de <head>, vóór GTM/gtag.
		add_action( 'wp_head', array( $this, 'print_consent_mode_defaults' ), 1 );
		add_action( 'wp_head', array( $this, 'print_integration_scripts' ), 20 );
	}

	private function get_integrations() {
		return wp_parse_args(
			get_option( 'ravn_integrations', array() ),
			array(
				'ga4_id'              => '',
				'ga4_category'        => 'analytics',
				'gtm_id'              => '',
				'gtm_category'        => 'analytics',
				'meta_pixel_id'       => '',
				'meta_pixel_category' => 'marketing',
				'consent_mode'        => 1,
			)
		);
	}

	/**
	 * Zet Google Consent Mode v2 standaard op "denied" vóórdat gtag/GTM laadt.
	 * Dit is de door Google voorgeschreven manier om GA4/Ads consent-bewust te
	 * laten werken; zonder dit blijven metingen op "granted" staan totdat
	 * gtag zelf geladen is, wat niet AVG-proof is.
	 */
	public function print_consent_mode_defaults() {
		$integrations = $this->get_integrations();
		if ( ! $integrations['consent_mode'] ) {
			return;
		}
		if ( empty( $integrations['ga4_id'] ) && empty( $integrations['gtm_id'] ) ) {
			return; // Geen Google-tags actief, snippet is dan overbodig.
		}
		?>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){ dataLayer.push(arguments); }
			gtag('consent', 'default', {
				'ad_storage': 'denied',
				'ad_user_data': 'denied',
				'ad_personalization': 'denied',
				'analytics_storage': 'denied',
				'functionality_storage': 'denied',
				'personalization_storage': 'denied',
				'security_storage': 'granted'
			});
		</script>
		<?php
	}

	/**
	 * Print de daadwerkelijke GA4/GTM/Meta Pixel scripts, als
	 * type="text/plain" zodat banner.js ze pas activeert na toestemming.
	 */
	public function print_integration_scripts() {
		$integrations = $this->get_integrations();

		if ( ! empty( $integrations['ga4_id'] ) ) :
			$id  = esc_js( $integrations['ga4_id'] );
			$cat = esc_attr( $integrations['ga4_category'] );
			?>
			<script type="text/plain" data-ravn-category="<?php echo $cat; ?>" data-src="https://www.googletagmanager.com/gtag/js?id=<?php echo $id; ?>"></script>
			<script type="text/plain" data-ravn-category="<?php echo $cat; ?>">
				window.dataLayer = window.dataLayer || [];
				function gtag(){ dataLayer.push(arguments); }
				gtag('js', new Date());
				gtag('config', '<?php echo $id; ?>');
			</script>
			<?php
		endif;

		if ( ! empty( $integrations['gtm_id'] ) ) :
			$id  = esc_js( $integrations['gtm_id'] );
			$cat = esc_attr( $integrations['gtm_category'] );
			?>
			<script type="text/plain" data-ravn-category="<?php echo $cat; ?>">
				(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});
				var f=d.getElementsByTagName(s)[0], j=d.createElement(s), dl=l!='dataLayer'?'&l='+l:'';
				j.async=true; j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
				f.parentNode.insertBefore(j,f);
				})(window,document,'script','dataLayer','<?php echo $id; ?>');
			</script>
			<?php
		endif;

		if ( ! empty( $integrations['meta_pixel_id'] ) ) :
			$id  = esc_js( $integrations['meta_pixel_id'] );
			$cat = esc_attr( $integrations['meta_pixel_category'] );
			?>
			<script type="text/plain" data-ravn-category="<?php echo $cat; ?>">
				!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
				n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
				n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
				t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
				document,'script','https://connect.facebook.net/en_US/fbevents.js');
				fbq('init', '<?php echo $id; ?>');
				fbq('track', 'PageView');
			</script>
			<?php
		endif;
	}

	/**
	 * Shortcode [ravn_cookieverklaring] - toont alle geregistreerde cookies
	 * per categorie, direct bruikbaar op de privacy-/cookiepagina.
	 * Werkt los van de banner-JS zodat hij ook zonder JavaScript leesbaar is.
	 */
	public function render_cookieverklaring_shortcode() {
		wp_enqueue_style( 'ravn-banner', RAVN_PLUGIN_URL . 'assets/css/banner.css', array(), RAVN_VERSION );

		$grouped = RAVN_DB::get_all_cookies_grouped();
		if ( empty( $grouped ) ) {
			return '<p>' . esc_html__( 'Er zijn nog geen cookies geregistreerd.', 'ravn' ) . '</p>';
		}

		ob_start();
		?>
		<div class="ravn-declaration">
			<?php foreach ( $grouped as $group ) :
				$cat = $group['category'];
				if ( ! $cat->is_enabled ) {
					continue;
				}
				?>
				<div class="ravn-declaration__category">
					<h3>
						<?php echo esc_html( $cat->name ); ?>
						<?php if ( $cat->is_required ) : ?>
							<span class="ravn-badge ravn-badge-on"><?php esc_html_e( 'Altijd actief', 'ravn' ); ?></span>
						<?php endif; ?>
					</h3>
					<?php if ( $cat->description ) : ?>
						<p class="ravn-declaration__desc"><?php echo esc_html( $cat->description ); ?></p>
					<?php endif; ?>

					<?php if ( empty( $group['cookies'] ) ) : ?>
						<p class="ravn-declaration__empty"><em><?php esc_html_e( 'Geen cookies geregistreerd in deze categorie.', 'ravn' ); ?></em></p>
					<?php else : ?>
						<table class="ravn-declaration__table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Naam', 'ravn' ); ?></th>
									<th><?php esc_html_e( 'Aanbieder', 'ravn' ); ?></th>
									<th><?php esc_html_e( 'Gedeeld met', 'ravn' ); ?></th>
									<th><?php esc_html_e( 'Doel', 'ravn' ); ?></th>
									<th><?php esc_html_e( 'Bewaartermijn', 'ravn' ); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $group['cookies'] as $cookie ) : ?>
								<tr>
									<td data-label="<?php esc_attr_e( 'Naam', 'ravn' ); ?>"><?php echo esc_html( $cookie->cookie_name ); ?></td>
									<td data-label="<?php esc_attr_e( 'Aanbieder', 'ravn' ); ?>"><?php echo esc_html( $cookie->provider ); ?></td>
									<td data-label="<?php esc_attr_e( 'Gedeeld met', 'ravn' ); ?>"><?php echo esc_html( $cookie->shared_with ?: $cookie->provider ); ?></td>
									<td data-label="<?php esc_attr_e( 'Doel', 'ravn' ); ?>"><?php echo esc_html( $cookie->purpose ); ?></td>
									<td data-label="<?php esc_attr_e( 'Bewaartermijn', 'ravn' ); ?>"><?php echo esc_html( $cookie->duration ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<p class="ravn-declaration__footer">
				<button type="button" class="ravn-btn ravn-btn--text" onclick="window.ravnOpenPreferences && window.ravnOpenPreferences(); return false;">
					<?php esc_html_e( 'Cookievoorkeuren aanpassen', 'ravn' ); ?>
				</button>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'ravn-banner', RAVN_PLUGIN_URL . 'assets/css/banner.css', array(), RAVN_VERSION );
		wp_enqueue_script( 'ravn-banner', RAVN_PLUGIN_URL . 'assets/js/banner.js', array(), RAVN_VERSION, true );

		$data = array(
			'categories'    => $this->get_categories_for_js(),
			'settings'      => $this->get_settings(),
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'ravn_consent' ),
			'policyVersion' => (int) get_option( 'ravn_policy_version', 1 ),
		);
		wp_localize_script( 'ravn-banner', 'ravnData', $data );

		if ( get_option( 'ravn_live_scan_enabled' ) ) {
			wp_enqueue_script( 'ravn-scanner', RAVN_PLUGIN_URL . 'assets/js/scanner.js', array(), RAVN_VERSION, true );
			wp_localize_script(
				'ravn-scanner',
				'ravnScanData',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'ravn_scan' ),
				)
			);
		}
	}

	private function get_settings() {
		return wp_parse_args(
			get_option( 'ravn_settings', array() ),
			array(
				'banner_text'    => 'Wij gebruiken cookies om onze website goed te laten werken, het gebruik te analyseren en relevante content te tonen.',
				'accept_label'   => 'Alles accepteren',
				'reject_label'   => 'Alleen noodzakelijk',
				'settings_label' => 'Voorkeuren aanpassen',
				'primary_color'  => '#1a7f5a',
				'position'       => 'bottom',
			)
		);
	}

	/**
	 * Bouwt de categorie- en cookie-data die de banner-JS nodig heeft
	 * om per categorie te tonen en scripts te blokkeren/vrij te geven.
	 */
	private function get_categories_for_js() {
		$grouped = RAVN_DB::get_all_cookies_grouped();
		$output  = array();

		foreach ( $grouped as $group ) {
			$cat = $group['category'];
			if ( ! $cat->is_enabled ) {
				continue;
			}
			$cookies = array();
			foreach ( $group['cookies'] as $cookie ) {
				$cookies[] = array(
					'name'     => $cookie->cookie_name,
					'provider' => $cookie->provider,
					'purpose'  => $cookie->purpose,
					'duration' => $cookie->duration,
				);
			}
			$output[] = array(
				'id'          => $cat->slug,
				'name'        => $cat->name,
				'description' => $cat->description,
				'required'    => (bool) $cat->is_required,
				'cookies'     => $cookies,
			);
		}
		return $output;
	}

	public function render_banner() {
		echo '<div id="ravn-root"></div>';
		echo '<button type="button" id="ravn-reopen" class="ravn-reopen" aria-label="Cookie-instellingen aanpassen" onclick="window.ravnOpenPreferences(); return false;">' .
			esc_html__( 'Cookie-instellingen', 'ravn' ) . '</button>';
	}

	/**
	 * Slaat de gekozen categorieën serverside op (voor AVG-verantwoording),
	 * los van de cookie die clientside het gedrag bepaalt.
	 */
	public function ajax_log_consent() {
		check_ajax_referer( 'ravn_consent', 'nonce' );

		$categories_raw = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : '[]';
		$categories     = json_decode( $categories_raw, true );
		if ( ! is_array( $categories ) ) {
			$categories = array();
		}
		$categories = array_map( 'sanitize_text_field', $categories );

		$visitor_hash = hash( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) . wp_salt() );
		RAVN_DB::log_consent( $visitor_hash, $categories );

		wp_send_json_success();
	}

	/**
	 * Ontvangt cookienamen die in de browser zijn waargenomen door
	 * scanner.js (alleen actief als live-detectie in het dashboard aan
	 * staat) en zet ze klaar als "nieuw gevonden" in het dashboard.
	 */
	public function ajax_report_cookies() {
		check_ajax_referer( 'ravn_scan', 'nonce' );

		if ( ! get_option( 'ravn_live_scan_enabled' ) ) {
			wp_send_json_error( 'Live detectie staat uit.' );
		}

		$names_raw = isset( $_POST['cookies'] ) ? wp_unslash( $_POST['cookies'] ) : '[]';
		$names     = json_decode( $names_raw, true );
		if ( ! is_array( $names ) ) {
			$names = array();
		}
		$names = array_slice( array_map( 'sanitize_text_field', $names ), 0, 30 ); // bescheiden limiet per verzoek

		require_once RAVN_PLUGIN_DIR . 'includes/class-ravn-scanner.php';
		$new_count = RAVN_Scanner::register_live_detected_cookies( $names );

		wp_send_json_success( array( 'new' => $new_count ) );
	}
}
