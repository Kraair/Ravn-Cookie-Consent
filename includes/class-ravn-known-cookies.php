<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ingebouwde herkenning van veelvoorkomende cookies en trackingscripts.
 * Geen uitputtende lijst - richt zich op de diensten die het vaakst
 * voorkomen op Nederlandse WordPress-sites. Bij twijfel blijft een cookie
 * "onbekend" en moet de gebruiker hem zelf indelen.
 */
class RAVN_CC_Known_Cookies {

	/**
	 * Cookienaam-patronen. Sleutel mag eindigen op '*' voor prefix-match
	 * (bijv. '_ga_*' matcht '_ga_ABC123DEF').
	 */
	public static function cookie_signatures() {
		return array(
			'_ga'          => array( 'provider' => 'Google Analytics', 'category' => 'analytics', 'purpose' => 'Onderscheidt unieke bezoekers.', 'duration' => '2 jaar' ),
			'_ga_*'        => array( 'provider' => 'Google Analytics (GA4)', 'category' => 'analytics', 'purpose' => 'Houdt sessiestatus bij per GA4-property.', 'duration' => '2 jaar' ),
			'_gid'         => array( 'provider' => 'Google Analytics', 'category' => 'analytics', 'purpose' => 'Onderscheidt bezoekers voor rapportage.', 'duration' => '24 uur' ),
			'_gat*'        => array( 'provider' => 'Google Analytics', 'category' => 'analytics', 'purpose' => 'Beperkt het aantal verzoeken naar Google.', 'duration' => '1 minuut' ),
			'_gcl_au'      => array( 'provider' => 'Google Ads', 'category' => 'marketing', 'purpose' => 'Meet conversies van advertenties.', 'duration' => '3 maanden' ),
			'_fbp'         => array( 'provider' => 'Meta (Facebook)', 'category' => 'marketing', 'purpose' => 'Toont relevante advertenties via Facebook/Instagram.', 'duration' => '3 maanden' ),
			'_fbc'         => array( 'provider' => 'Meta (Facebook)', 'category' => 'marketing', 'purpose' => 'Meet conversies afkomstig van Facebook-advertenties.', 'duration' => '3 maanden' ),
			'fr'           => array( 'provider' => 'Meta (Facebook)', 'category' => 'marketing', 'purpose' => 'Advertentiepersonalisatie op Facebook.', 'duration' => '3 maanden' ),
			'_hjSession*'  => array( 'provider' => 'Hotjar', 'category' => 'analytics', 'purpose' => 'Houdt een gebruikerssessie bij voor heatmaps/opnames.', 'duration' => '30 minuten' ),
			'_hjFirstSeen' => array( 'provider' => 'Hotjar', 'category' => 'analytics', 'purpose' => 'Herkent of dit de eerste sessie van de bezoeker is.', 'duration' => '30 minuten' ),
			'_clck'        => array( 'provider' => 'Microsoft Clarity', 'category' => 'analytics', 'purpose' => 'Onthoudt een uniek bezoekers-ID tussen sessies.', 'duration' => '1 jaar' ),
			'_clsk'        => array( 'provider' => 'Microsoft Clarity', 'category' => 'analytics', 'purpose' => 'Koppelt gedrag binnen één sessie.', 'duration' => '1 dag' ),
			'li_sugr'      => array( 'provider' => 'LinkedIn', 'category' => 'marketing', 'purpose' => 'Identificeert browser voor advertentiedoeleinden.', 'duration' => '3 maanden' ),
			'bcookie'      => array( 'provider' => 'LinkedIn', 'category' => 'marketing', 'purpose' => 'Herkent de browser voor LinkedIn-advertenties.', 'duration' => '1 jaar' ),
			'_ttp'         => array( 'provider' => 'TikTok', 'category' => 'marketing', 'purpose' => 'Meet advertentieprestaties via TikTok.', 'duration' => '13 maanden' ),
			'_pin_unauth'  => array( 'provider' => 'Pinterest', 'category' => 'marketing', 'purpose' => 'Advertentiemeting via Pinterest.', 'duration' => '1 jaar' ),
			'_mkto_trk'    => array( 'provider' => 'Marketo', 'category' => 'marketing', 'purpose' => 'Volgt bezoekersgedrag voor e-mailmarketing.', 'duration' => '2 jaar' ),
			'__hstc'       => array( 'provider' => 'HubSpot', 'category' => 'marketing', 'purpose' => 'Houdt bezoekersgeschiedenis bij voor HubSpot.', 'duration' => '6 maanden' ),
			'hubspotutk'   => array( 'provider' => 'HubSpot', 'category' => 'marketing', 'purpose' => 'Identificeert bezoekers en formulierinzendingen.', 'duration' => '6 maanden' ),
			'mp_*'         => array( 'provider' => 'Mixpanel', 'category' => 'analytics', 'purpose' => 'Productanalyse van gebruikersgedrag.', 'duration' => '1 jaar' ),
			'_pk_id*'      => array( 'provider' => 'Matomo', 'category' => 'analytics', 'purpose' => 'Onderscheidt unieke bezoekers (zelf gehost).', 'duration' => '13 maanden' ),
			'_pk_ses*'     => array( 'provider' => 'Matomo', 'category' => 'analytics', 'purpose' => 'Houdt een sessie bij (zelf gehost).', 'duration' => '30 minuten' ),
			'VISITOR_INFO1_LIVE' => array( 'provider' => 'YouTube', 'category' => 'functional', 'purpose' => 'Meet bandbreedte voor ingesloten video\'s.', 'duration' => '6 maanden' ),
			'YSC'          => array( 'provider' => 'YouTube', 'category' => 'functional', 'purpose' => 'Houdt weergaven van ingesloten video\'s bij.', 'duration' => 'Sessie' ),
			'vuid'         => array( 'provider' => 'Vimeo', 'category' => 'functional', 'purpose' => 'Analyseert gebruik van ingesloten Vimeo-video\'s.', 'duration' => '2 jaar' ),
			'PHPSESSID'    => array( 'provider' => 'Eigen server', 'category' => 'necessary', 'purpose' => 'Houdt sessiegegevens bij (bijv. winkelwagen, login).', 'duration' => 'Sessie' ),
			'wordpress_logged_in_*' => array( 'provider' => 'WordPress', 'category' => 'necessary', 'purpose' => 'Onthoudt of een gebruiker is ingelogd.', 'duration' => 'Sessie' ),
			'wp-settings-*' => array( 'provider' => 'WordPress', 'category' => 'necessary', 'purpose' => 'Onthoudt voorkeuren in het WP-dashboard.', 'duration' => '1 jaar' ),
			'woocommerce_cart_hash' => array( 'provider' => 'WooCommerce', 'category' => 'necessary', 'purpose' => 'Houdt bij of de winkelwagen is gewijzigd.', 'duration' => 'Sessie' ),
			'woocommerce_items_in_cart' => array( 'provider' => 'WooCommerce', 'category' => 'necessary', 'purpose' => 'Houdt bij of er producten in de winkelwagen zitten.', 'duration' => 'Sessie' ),
		);
	}

	/**
	 * Zoekt een match voor een cookienaam, inclusief prefix-patronen met '*'.
	 * Retourneert null als niets bekend is.
	 */
	public static function match_cookie_name( $cookie_name ) {
		$signatures = self::cookie_signatures();

		if ( isset( $signatures[ $cookie_name ] ) ) {
			return $signatures[ $cookie_name ];
		}

		foreach ( $signatures as $pattern => $data ) {
			if ( substr( $pattern, -1 ) === '*' ) {
				$prefix = rtrim( $pattern, '*' );
				if ( 0 === strpos( $cookie_name, $prefix ) ) {
					return $data;
				}
			}
		}
		return null;
	}

	/**
	 * Signatures voor de server-side scan: herkent trackingscripts in de
	 * HTML-broncode (script src of inline inhoud) en koppelt daaraan de
	 * cookies die die dienst doorgaans zet.
	 */
	public static function script_signatures() {
		return array(
			array(
				'match'   => 'googletagmanager.com/gtag/js',
				'label'   => 'Google Analytics (GA4)',
				'cookies' => array( '_ga', '_ga_*', '_gid' ),
			),
			array(
				'match'   => 'googletagmanager.com/gtm.js',
				'label'   => 'Google Tag Manager',
				'cookies' => array(),
			),
			array(
				'match'   => 'googleadservices.com',
				'label'   => 'Google Ads',
				'cookies' => array( '_gcl_au' ),
			),
			array(
				'match'   => 'connect.facebook.net',
				'label'   => 'Meta (Facebook) Pixel',
				'cookies' => array( '_fbp', 'fr' ),
			),
			array(
				'match'   => 'static.hotjar.com',
				'label'   => 'Hotjar',
				'cookies' => array( '_hjSession*', '_hjFirstSeen' ),
			),
			array(
				'match'   => 'clarity.ms',
				'label'   => 'Microsoft Clarity',
				'cookies' => array( '_clck', '_clsk' ),
			),
			array(
				'match'   => 'snap.licdn.com',
				'label'   => 'LinkedIn Insight Tag',
				'cookies' => array( 'li_sugr', 'bcookie' ),
			),
			array(
				'match'   => 'analytics.tiktok.com',
				'label'   => 'TikTok Pixel',
				'cookies' => array( '_ttp' ),
			),
			array(
				'match'   => 'pintrk',
				'label'   => 'Pinterest Tag',
				'cookies' => array( '_pin_unauth' ),
			),
			array(
				'match'   => 'js.hs-scripts.com',
				'label'   => 'HubSpot',
				'cookies' => array( '__hstc', 'hubspotutk' ),
			),
			array(
				'match'   => 'youtube.com/embed',
				'label'   => 'YouTube (ingesloten video)',
				'cookies' => array( 'VISITOR_INFO1_LIVE', 'YSC' ),
			),
			array(
				'match'   => 'player.vimeo.com',
				'label'   => 'Vimeo (ingesloten video)',
				'cookies' => array( 'vuid' ),
			),
			array(
				'match'   => 'cdn.mxpnl.com',
				'label'   => 'Mixpanel',
				'cookies' => array( 'mp_*' ),
			),
		);
	}
}
