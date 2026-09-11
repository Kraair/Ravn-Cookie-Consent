<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories = RAVN_DB::get_categories();
$settings   = wp_parse_args(
	get_option( 'ravn_settings', array() ),
	array(
		'banner_text'    => 'Wij gebruiken cookies om onze website goed te laten werken, het gebruik te analyseren en relevante content te tonen. Kies hieronder welke cookies je toestaat.',
		'accept_label'   => 'Alles accepteren',
		'reject_label'   => 'Alleen noodzakelijk',
		'settings_label' => 'Voorkeuren aanpassen',
		'primary_color'  => '#1a7f5a',
		'position'       => 'bottom',
	)
);
?>
<div class="wrap ravn-wrap">
	<h1>Cookiemelding</h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Opgeslagen.</p></div>
	<?php endif; ?>

	<h2 class="nav-tab-wrapper">
		<a href="?page=ravn-settings&tab=categories" class="nav-tab <?php echo $tab === 'categories' ? 'nav-tab-active' : ''; ?>">Categorieën</a>
		<a href="?page=ravn-settings&tab=cookies" class="nav-tab <?php echo $tab === 'cookies' ? 'nav-tab-active' : ''; ?>">Cookies</a>
		<a href="?page=ravn-settings&tab=design" class="nav-tab <?php echo $tab === 'design' ? 'nav-tab-active' : ''; ?>">Popup &amp; teksten</a>
		<a href="?page=ravn-settings&tab=integrations" class="nav-tab <?php echo $tab === 'integrations' ? 'nav-tab-active' : ''; ?>">Integraties</a>
		<a href="?page=ravn-settings&tab=scan" class="nav-tab <?php echo $tab === 'scan' ? 'nav-tab-active' : ''; ?>">
			Scan
			<?php $new_count = RAVN_DB::count_new_detected_cookies(); ?>
			<?php if ( $new_count > 0 ) : ?><span class="ravn-badge ravn-badge-count"><?php echo intval( $new_count ); ?></span><?php endif; ?>
		</a>
		<a href="?page=ravn-settings&tab=privacy" class="nav-tab <?php echo $tab === 'privacy' ? 'nav-tab-active' : ''; ?>">Privacy &amp; bewaartermijn</a>
	</h2>

	<?php if ( 'categories' === $tab ) : ?>

		<p>Zet een categorie uit om hem te verbergen in de popup en de bijbehorende scripts te blokkeren totdat er alsnog toestemming is.</p>
		<table class="widefat ravn-table">
			<thead>
				<tr>
					<th>Categorie</th>
					<th>Omschrijving</th>
					<th>Verplicht</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $categories as $cat ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $cat->name ); ?></strong></td>
					<td><?php echo esc_html( $cat->description ); ?></td>
					<td><?php echo $cat->is_required ? 'Ja' : 'Nee'; ?></td>
					<td>
						<?php if ( $cat->is_required ) : ?>
							<span class="ravn-badge ravn-badge-on">Altijd aan</span>
						<?php else : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( 'ravn_toggle_category' ); ?>
								<input type="hidden" name="action" value="ravn_toggle_category">
								<input type="hidden" name="category_id" value="<?php echo esc_attr( $cat->id ); ?>">
								<input type="hidden" name="enabled" value="<?php echo $cat->is_enabled ? 0 : 1; ?>">
								<button type="submit" class="ravn-toggle <?php echo $cat->is_enabled ? 'is-on' : 'is-off'; ?>">
									<?php echo $cat->is_enabled ? 'Aan' : 'Uit'; ?>
								</button>
							</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

	<?php elseif ( 'cookies' === $tab ) : ?>

		<h2>Nieuwe cookie registreren</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ravn-form">
			<?php wp_nonce_field( 'ravn_save_cookie' ); ?>
			<input type="hidden" name="action" value="ravn_save_cookie">
			<input type="hidden" name="cookie_id" value="0">

			<table class="form-table">
				<tr>
					<th><label for="cookie_name">Cookienaam</label></th>
					<td><input type="text" id="cookie_name" name="cookie_name" class="regular-text" required placeholder="bijv. _ga"></td>
				</tr>
				<tr>
					<th><label for="category_id">Categorie</label></th>
					<td>
						<select id="category_id" name="category_id" required>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="provider">Aanbieder</label></th>
					<td><input type="text" id="provider" name="provider" class="regular-text" placeholder="bijv. Google Analytics"></td>
				</tr>
				<tr>
					<th><label for="shared_with">Gedeeld met derden</label></th>
					<td>
						<input type="text" id="shared_with" name="shared_with" class="regular-text" placeholder="bijv. Google LLC (VS)">
						<p class="description">Laat leeg als dit hetzelfde is als de aanbieder hierboven. Alleen invullen als de data ná verzameling nog naar een andere partij gaat.</p>
					</td>
				</tr>
				<tr>
					<th><label for="purpose">Doel</label></th>
					<td><textarea id="purpose" name="purpose" class="large-text" rows="2" placeholder="Waar wordt deze cookie voor gebruikt?"></textarea></td>
				</tr>
				<tr>
					<th><label for="duration">Bewaartermijn</label></th>
					<td><input type="text" id="duration" name="duration" class="regular-text" placeholder="bijv. 2 jaar"></td>
				</tr>
				<tr>
					<th>Actief</th>
					<td><label><input type="checkbox" name="is_active" value="1" checked> Tonen in cookieoverzicht</label></td>
				</tr>
			</table>
			<?php submit_button( 'Cookie opslaan' ); ?>
		</form>

		<hr>
		<h2>Geregistreerde cookies</h2>
		<?php foreach ( $categories as $cat ) : ?>
			<h3><?php echo esc_html( $cat->name ); ?></h3>
			<?php $cookies = RAVN_DB::get_cookies_by_category( $cat->id ); ?>
			<?php if ( empty( $cookies ) ) : ?>
				<p><em>Nog geen cookies geregistreerd in deze categorie.</em></p>
			<?php else : ?>
				<table class="widefat ravn-table">
					<thead>
						<tr><th>Naam</th><th>Aanbieder</th><th>Gedeeld met</th><th>Doel</th><th>Bewaartermijn</th><th></th></tr>
					</thead>
					<tbody>
					<?php foreach ( $cookies as $cookie ) : ?>
						<tr>
							<td><?php echo esc_html( $cookie->cookie_name ); ?></td>
							<td><?php echo esc_html( $cookie->provider ); ?></td>
							<td><?php echo esc_html( $cookie->shared_with ?: $cookie->provider ); ?></td>
							<td><?php echo esc_html( $cookie->purpose ); ?></td>
							<td><?php echo esc_html( $cookie->duration ); ?></td>
							<td>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ravn_delete_cookie&cookie_id=' . $cookie->id ), 'ravn_delete_cookie' ) ); ?>"
								   onclick="return confirm('Deze cookie verwijderen?');">Verwijderen</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php endforeach; ?>

	<?php elseif ( 'design' === $tab ) : ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ravn-form">
			<?php wp_nonce_field( 'ravn_save_settings' ); ?>
			<input type="hidden" name="action" value="ravn_save_settings">

			<table class="form-table">
				<tr>
					<th><label for="banner_text">Bannertekst</label></th>
					<td><textarea id="banner_text" name="banner_text" class="large-text" rows="3"><?php echo esc_textarea( $settings['banner_text'] ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="accept_label">Tekst 'Alles accepteren'</label></th>
					<td><input type="text" id="accept_label" name="accept_label" class="regular-text" value="<?php echo esc_attr( $settings['accept_label'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="reject_label">Tekst 'Alleen noodzakelijk'</label></th>
					<td><input type="text" id="reject_label" name="reject_label" class="regular-text" value="<?php echo esc_attr( $settings['reject_label'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="settings_label">Tekst 'Voorkeuren aanpassen'</label></th>
					<td><input type="text" id="settings_label" name="settings_label" class="regular-text" value="<?php echo esc_attr( $settings['settings_label'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="primary_color">Accentkleur</label></th>
					<td><input type="text" id="primary_color" name="primary_color" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" class="ravn-color-field"></td>
				</tr>
				<tr>
					<th><label for="position">Positie banner</label></th>
					<td>
						<select id="position" name="position">
							<option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>>Onderaan (balk)</option>
							<option value="top" <?php selected( $settings['position'], 'top' ); ?>>Bovenaan (balk)</option>
							<option value="modal" <?php selected( $settings['position'], 'modal' ); ?>>Gecentreerd (popup/modal)</option>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Instellingen opslaan' ); ?>
		</form>

	<?php elseif ( 'integrations' === $tab ) :
		$integrations = wp_parse_args(
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
		?>

		<p>Vul hier je meet-ID's in. De plugin genereert de scripts zelf, al geblokkeerd totdat de bijbehorende categorie is toegestaan. Handmatig scripts aanpassen in je theme is dus niet meer nodig voor deze drie.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ravn-form">
			<?php wp_nonce_field( 'ravn_save_integrations' ); ?>
			<input type="hidden" name="action" value="ravn_save_integrations">

			<h2>Google Consent Mode v2</h2>
			<table class="form-table">
				<tr>
					<th>Consent Mode inschakelen</th>
					<td>
						<label>
							<input type="checkbox" name="consent_mode" value="1" <?php checked( $integrations['consent_mode'], 1 ); ?>>
							Stuur toestemmingsstatus door naar Google (aanbevolen als je GA4, Google Ads of GTM gebruikt)
						</label>
						<p class="description">Zonder dit werken metingen in GA4/Google Ads niet betrouwbaar zodra iemand cookies weigert.</p>
					</td>
				</tr>
			</table>

			<h2>Google Analytics 4</h2>
			<table class="form-table">
				<tr>
					<th><label for="ga4_id">Measurement ID</label></th>
					<td><input type="text" id="ga4_id" name="ga4_id" class="regular-text" placeholder="G-XXXXXXXXXX" value="<?php echo esc_attr( $integrations['ga4_id'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="ga4_category">Vereiste categorie</label></th>
					<td>
						<select id="ga4_category" name="ga4_category">
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $integrations['ga4_category'], $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<h2>Google Tag Manager</h2>
			<table class="form-table">
				<tr>
					<th><label for="gtm_id">Container-ID</label></th>
					<td><input type="text" id="gtm_id" name="gtm_id" class="regular-text" placeholder="GTM-XXXXXXX" value="<?php echo esc_attr( $integrations['gtm_id'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="gtm_category">Vereiste categorie</label></th>
					<td>
						<select id="gtm_category" name="gtm_category">
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $integrations['gtm_category'], $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Let op: als je tags zelf via GTM stuurt, regel triggers/consent bij voorkeur ook binnen GTM zelf (Consent Mode-integratie).</p>
					</td>
				</tr>
			</table>

			<h2>Meta (Facebook) Pixel</h2>
			<table class="form-table">
				<tr>
					<th><label for="meta_pixel_id">Pixel-ID</label></th>
					<td><input type="text" id="meta_pixel_id" name="meta_pixel_id" class="regular-text" placeholder="123456789012345" value="<?php echo esc_attr( $integrations['meta_pixel_id'] ); ?>"></td>
				</tr>
				<tr>
					<th><label for="meta_pixel_category">Vereiste categorie</label></th>
					<td>
						<select id="meta_pixel_category" name="meta_pixel_category">
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $integrations['meta_pixel_category'], $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Integraties opslaan' ); ?>
		</form>

	<?php elseif ( 'scan' === $tab ) :
		$live_enabled = (bool) get_option( 'ravn_live_scan_enabled' );
		$last_scan    = get_option( 'ravn_last_scan' );
		$detected     = RAVN_DB::get_detected_cookies( 'new' );
		?>

		<h2>Handmatige scan</h2>
		<p>Doorzoekt de homepage en tot 11 recent gewijzigde pagina's/berichten op bekende trackingscripts (Google Analytics, GTM, Meta Pixel, Hotjar, LinkedIn, TikTok, e.a.). Geen volledige site-crawl, dus scripts die alleen op minder gangbare pagina's staan kunnen gemist worden.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ravn_run_scan' ); ?>
			<input type="hidden" name="action" value="ravn_run_scan">
			<?php submit_button( 'Scan nu uitvoeren', 'secondary', 'submit', false ); ?>
			<?php if ( $last_scan ) : ?>
				<span class="ravn-scan-meta">Laatste scan: <?php echo esc_html( mysql2date( 'd-m-Y H:i', $last_scan ) ); ?></span>
			<?php endif; ?>
		</form>

		<?php if ( isset( $_GET['scanned'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo intval( $_GET['scanned'] ); ?> nieuwe cookie(s) gevonden.</p></div>
		<?php endif; ?>

		<hr>

		<h2>Live detectie</h2>
		<p>Houdt bij welke cookies daadwerkelijk in de browser van bezoekers verschijnen — de meest betrouwbare methode, ook voor scripts die de scan hierboven mist. Zet dit een tijdje aan om te inventariseren, en weer uit zodra je overzicht compleet is; niet bedoeld om permanent te laten meedraaien.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ravn_toggle_live_scan' ); ?>
			<input type="hidden" name="action" value="ravn_toggle_live_scan">
			<input type="hidden" name="enabled" value="<?php echo $live_enabled ? 0 : 1; ?>">
			<button type="submit" class="ravn-toggle <?php echo $live_enabled ? 'is-on' : 'is-off'; ?>">
				Live detectie: <?php echo $live_enabled ? 'Aan' : 'Uit'; ?>
			</button>
		</form>

		<hr>

		<h2>Nieuw gevonden cookies (<?php echo count( $detected ); ?>)</h2>
		<?php if ( isset( $_GET['added'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Cookie toegevoegd aan het overzicht.</p></div>
		<?php endif; ?>

		<?php if ( empty( $detected ) ) : ?>
			<p><em>Nog niets gevonden. Voer een scan uit of zet live detectie aan.</em></p>
		<?php else : ?>
			<?php foreach ( $detected as $item ) :
				$suggestion = RAVN_Known_Cookies::match_cookie_name( $item->cookie_name );
				$sugg_provider = $suggestion ? $suggestion['provider'] : ( $item->detected_via ?: '' );
				$sugg_purpose  = $suggestion ? $suggestion['purpose'] : '';
				$sugg_duration = $suggestion ? $suggestion['duration'] : '';
				$sugg_slug     = $suggestion ? $suggestion['category'] : 'analytics';
				?>
				<div class="ravn-detected-row">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ravn-detected-form">
						<?php wp_nonce_field( 'ravn_add_detected_cookie' ); ?>
						<input type="hidden" name="action" value="ravn_add_detected_cookie">
						<input type="hidden" name="detected_id" value="<?php echo esc_attr( $item->id ); ?>">

						<div class="ravn-detected-row__header">
							<strong><?php echo esc_html( $item->cookie_name ); ?></strong>
							<span class="ravn-badge <?php echo $suggestion ? 'ravn-badge-on' : ''; ?>">
								<?php echo $suggestion ? 'Herkend' : 'Onbekend'; ?>
							</span>
							<span class="ravn-detected-row__source">via <?php echo esc_html( 'scan' === $item->source ? 'scan' : 'live detectie' ); ?><?php echo $item->detected_via ? ' — ' . esc_html( $item->detected_via ) : ''; ?></span>
						</div>

						<input type="hidden" name="cookie_name" value="<?php echo esc_attr( $item->cookie_name ); ?>">

						<div class="ravn-detected-row__fields">
							<label>Categorie
								<select name="category_id">
									<?php foreach ( $categories as $cat ) : ?>
										<option value="<?php echo esc_attr( $cat->id ); ?>" <?php selected( $cat->slug, $sugg_slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label>Aanbieder
								<input type="text" name="provider" value="<?php echo esc_attr( $sugg_provider ); ?>">
							</label>
							<label>Gedeeld met derden
								<input type="text" name="shared_with" placeholder="zelfde als aanbieder indien leeg">
							</label>
							<label>Doel
								<input type="text" name="purpose" value="<?php echo esc_attr( $sugg_purpose ); ?>">
							</label>
							<label>Bewaartermijn
								<input type="text" name="duration" value="<?php echo esc_attr( $sugg_duration ); ?>">
							</label>
						</div>

						<div class="ravn-detected-row__actions">
							<button type="submit" class="ravn-btn-small ravn-btn-small--primary">Toevoegen aan overzicht</button>
						</div>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ravn-detected-dismiss">
						<?php wp_nonce_field( 'ravn_dismiss_detected_cookie' ); ?>
						<input type="hidden" name="action" value="ravn_dismiss_detected_cookie">
						<input type="hidden" name="detected_id" value="<?php echo esc_attr( $item->id ); ?>">
						<button type="submit" class="ravn-btn-small">Negeren</button>
					</form>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

	<?php elseif ( 'privacy' === $tab ) :
		$retention_months = (int) get_option( 'ravn_consent_log_retention_months', 24 );
		$policy_version   = (int) get_option( 'ravn_policy_version', 1 );
		?>

		<?php if ( isset( $_GET['reconsent'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Beleidsversie is verhoogd naar <?php echo esc_html( $policy_version ); ?>. Bezoekers met een bestaande toestemming krijgen de banner bij hun volgende bezoek opnieuw te zien.</p></div>
		<?php endif; ?>

		<h2>Bewaartermijn consent-log</h2>
		<p>De consent-log (onder "Toestemmingsbewijs") bevat een gehashte combinatie van IP-adres en browser, gekoppeld aan de gemaakte keuzes — dit telt als persoonsgegeven. AVG vereist dat je dit niet langer bewaart dan nodig. Een dagelijkse achtergrondtaak verwijdert automatisch regels die ouder zijn dan onderstaande termijn.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ravn-form">
			<?php wp_nonce_field( 'ravn_save_privacy_settings' ); ?>
			<input type="hidden" name="action" value="ravn_save_privacy_settings">
			<table class="form-table">
				<tr>
					<th><label for="retention_months">Bewaartermijn (maanden)</label></th>
					<td>
						<input type="number" id="retention_months" name="retention_months" min="1" max="120" value="<?php echo esc_attr( $retention_months ); ?>" class="small-text">
						<p class="description">Gangbaar is 24 maanden. Wordt elke nacht automatisch gecontroleerd.</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Bewaartermijn opslaan' ); ?>
		</form>

		<hr>

		<h2>Opnieuw toestemming vragen</h2>
		<p>Heb je een nieuwe trackingdienst toegevoegd, een cookie van categorie laten wisselen, of je cookiebeleid inhoudelijk gewijzigd? Dan is de eerder gegeven toestemming van bezoekers daar niet meer geldig voor. Klik hieronder om de beleidsversie te verhogen — de banner verschijnt dan bij ieders volgende bezoek opnieuw, ook als hun keuze-cookie nog geldig zou zijn.</p>
		<p><strong>Huidige beleidsversie: <?php echo esc_html( $policy_version ); ?></strong></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ravn_bump_policy_version' ); ?>
			<input type="hidden" name="action" value="ravn_bump_policy_version">
			<?php submit_button( 'Opnieuw toestemming vragen aan alle bezoekers', 'secondary', 'submit', false ); ?>
		</form>

	<?php endif; ?>
</div>
