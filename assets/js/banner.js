/**
 * Ravn Cookie Consent - front-end logica.
 *
 * Om een script pas te laten laden na toestemming, verander je in de site:
 *   <script src="https://www.googletagmanager.com/gtag/js"></script>
 * in:
 *   <script type="text/plain" data-ravn-category="analytics" data-src="https://www.googletagmanager.com/gtag/js"></script>
 *
 * Inline scripts kunnen op dezelfde manier met type="text/plain" data-ravn-category="...".
 */
( function () {
	'use strict';

	var CONSENT_COOKIE = 'ravn_consent';
	var COOKIE_DAYS = 365;

	function getSettings() {
		return ( window.ravnData && window.ravnData.settings ) || {};
	}

	function getCategories() {
		return ( window.ravnData && window.ravnData.categories ) || [];
	}

	function getPolicyVersion() {
		return ( window.ravnData && window.ravnData.policyVersion ) || 1;
	}

	/**
	 * Leest de opgeslagen keuze. Retourneert null als er geen cookie is, of
	 * als de cookie een oudere beleidsversie heeft dan nu actief is - in dat
	 * geval is de eerder gegeven toestemming niet meer geldig omdat het
	 * cookiebeleid is gewijzigd, en moet opnieuw gevraagd worden.
	 */
	function readConsentCookie() {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + CONSENT_COOKIE + '=([^;]*)' ) );
		if ( ! match ) {
			return null;
		}
		try {
			var stored = JSON.parse( decodeURIComponent( match[ 1 ] ) );
			if ( ! stored || typeof stored !== 'object' || ! stored.choices ) {
				return null;
			}
			if ( stored.version !== getPolicyVersion() ) {
				return null; // Verouderde toestemming, telt niet meer mee.
			}
			return stored.choices;
		} catch ( e ) {
			return null;
		}
	}

	function writeConsentCookie( choices ) {
		var payload = { choices: choices, version: getPolicyVersion() };
		var expires = new Date();
		expires.setTime( expires.getTime() + COOKIE_DAYS * 24 * 60 * 60 * 1000 );
		document.cookie = CONSENT_COOKIE + '=' + encodeURIComponent( JSON.stringify( payload ) ) +
			'; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
	}

	/**
	 * Stuurt de keuzes door naar Google Consent Mode v2. De stub-functie gtag()
	 * is al aanwezig via de vroege wp_head-snippet, dus dit werkt ook vóórdat
	 * gtag.js/GTM daadwerkelijk geladen zijn.
	 */
	function updateGoogleConsentMode( choices ) {
		if ( typeof window.gtag !== 'function' ) {
			return;
		}
		var update = {};

		if ( choices.hasOwnProperty( 'analytics' ) ) {
			update.analytics_storage = choices.analytics ? 'granted' : 'denied';
		}
		if ( choices.hasOwnProperty( 'marketing' ) ) {
			update.ad_storage = choices.marketing ? 'granted' : 'denied';
			update.ad_user_data = choices.marketing ? 'granted' : 'denied';
			update.ad_personalization = choices.marketing ? 'granted' : 'denied';
		}
		if ( choices.hasOwnProperty( 'functional' ) ) {
			update.functionality_storage = choices.functional ? 'granted' : 'denied';
			update.personalization_storage = choices.functional ? 'granted' : 'denied';
		}

		window.gtag( 'consent', 'update', update );
	}

	function applyConsent( choices ) {
		updateGoogleConsentMode( choices );

		// Activeer scripts waarvoor toestemming is gegeven.
		var blocked = document.querySelectorAll( 'script[type="text/plain"][data-ravn-category]' );
		blocked.forEach( function ( oldScript ) {
			var cat = oldScript.getAttribute( 'data-ravn-category' );
			if ( ! choices[ cat ] ) {
				return;
			}
			var newScript = document.createElement( 'script' );
			Array.prototype.slice.call( oldScript.attributes ).forEach( function ( attr ) {
				if ( attr.name === 'type' ) {
					return;
				}
				newScript.setAttribute( attr.name === 'data-src' ? 'src' : attr.name, attr.value );
			} );
			if ( oldScript.getAttribute( 'data-src' ) ) {
				newScript.src = oldScript.getAttribute( 'data-src' );
			} else {
				newScript.text = oldScript.text;
			}
			oldScript.parentNode.replaceChild( newScript, oldScript );
		} );

		document.dispatchEvent( new CustomEvent( 'ravnConsentUpdated', { detail: choices } ) );
	}

	function logConsentServerSide( choices ) {
		if ( ! window.ravnData || ! window.ravnData.ajaxUrl ) {
			return;
		}
		var enabledCats = Object.keys( choices ).filter( function ( k ) {
			return choices[ k ];
		} );
		var body = new URLSearchParams();
		body.append( 'action', 'ravn_log_consent' );
		body.append( 'nonce', window.ravnData.nonce );
		body.append( 'categories', JSON.stringify( enabledCats ) );

		fetch( window.ravnData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} );
	}

	function buildDefaultChoices( allChecked ) {
		var choices = {};
		getCategories().forEach( function ( cat ) {
			choices[ cat.id ] = cat.required ? true : !! allChecked;
		} );
		return choices;
	}

	function el( tag, className, html ) {
		var e = document.createElement( tag );
		if ( className ) e.className = className;
		if ( html !== undefined ) e.innerHTML = html;
		return e;
	}

	function renderBanner( root ) {
		var settings = getSettings();
		var position = settings.position || 'bottom';
		var isModal  = position === 'modal';
		var positionClass = isModal ? 'ravn-position-modal' : ( position === 'top' ? 'ravn-position-top' : 'ravn-position-bottom' );

		var wrapper = el( 'div', 'ravn-banner-wrapper' + ( isModal ? ' ravn-banner-wrapper--modal' : '' ) );
		var banner  = el( 'div', 'ravn-banner ' + positionClass );
		banner.innerHTML =
			'<p class="ravn-banner__text">' + ( settings.banner_text || '' ) + '</p>' +
			'<div class="ravn-banner__actions">' +
				'<button type="button" class="ravn-btn ravn-btn--text" data-ravn-action="settings">' + ( settings.settings_label || 'Voorkeuren aanpassen' ) + '</button>' +
				'<button type="button" class="ravn-btn ravn-btn--reject" data-ravn-action="reject">' + ( settings.reject_label || 'Alleen noodzakelijk' ) + '</button>' +
				'<button type="button" class="ravn-btn ravn-btn--primary" data-ravn-action="accept">' + ( settings.accept_label || 'Alles accepteren' ) + '</button>' +
			'</div>';

		wrapper.appendChild( banner );
		root.appendChild( wrapper );

		requestAnimationFrame( function () {
			wrapper.classList.add( 'ravn-visible' );
		} );

		return wrapper;
	}

	function renderModal( root ) {
		var overlay = el( 'div', 'ravn-overlay' );
		var modal = el( 'div', 'ravn-modal' );

		var header = el( 'div', 'ravn-modal__header' );
		header.innerHTML = '<h2>Cookievoorkeuren</h2><button type="button" class="ravn-modal__close" aria-label="Sluiten">&times;</button>';
		modal.appendChild( header );

		getCategories().forEach( function ( cat ) {
			var box = el( 'div', 'ravn-category' );
			var cookieListHtml = '';
			if ( cat.cookies && cat.cookies.length ) {
				cookieListHtml = '<div class="ravn-cookie-list">' + cat.cookies.map( function ( c ) {
					return '<div class="ravn-cookie-list__item"><strong>' + c.name + '</strong>' +
						( c.provider ? ' — ' + c.provider : '' ) +
						( c.purpose ? ' — ' + c.purpose : '' ) +
						( c.duration ? ' (' + c.duration + ')' : '' ) +
						'</div>';
				} ).join( '' ) + '</div>';
			}

			box.innerHTML =
				'<div class="ravn-category__row">' +
					'<div>' +
						'<div class="ravn-category__title">' + cat.name + '</div>' +
						'<p class="ravn-category__desc">' + ( cat.description || '' ) + '</p>' +
					'</div>' +
					'<label class="ravn-switch">' +
						'<input type="checkbox" data-ravn-category-toggle="' + cat.id + '" ' +
							( cat.required ? 'checked disabled' : '' ) + '>' +
						'<span class="ravn-switch__track"></span>' +
					'</label>' +
				'</div>' + cookieListHtml;

			modal.appendChild( box );
		} );

		var footer = el( 'div', 'ravn-modal__footer' );
		var settings = getSettings();
		footer.innerHTML =
			'<button type="button" class="ravn-btn ravn-btn--reject" data-ravn-action="reject-modal">' + ( settings.reject_label || 'Alleen noodzakelijk' ) + '</button>' +
			'<button type="button" class="ravn-btn ravn-btn--primary" data-ravn-action="save-preferences">Voorkeuren opslaan</button>';
		modal.appendChild( footer );

		overlay.appendChild( modal );
		root.appendChild( overlay );
		return overlay;
	}

	/**
	 * Verzamelt de huidige stand van de toggles in een voorkeurenpaneel.
	 */
	function collectChoicesFromOverlay( overlay ) {
		var choices = {};
		overlay.querySelectorAll( '[data-ravn-category-toggle]' ).forEach( function ( input ) {
			choices[ input.getAttribute( 'data-ravn-category-toggle' ) ] = input.checked;
		} );
		return choices;
	}

	/**
	 * Zet de toggles in een voorkeurenpaneel gelijk aan een gegeven keuze,
	 * zodat het paneel nooit een verouderde stand toont (bijvoorbeeld na
	 * "Alles accepteren" in de banner, zonder dat de modal geopend was).
	 */
	function syncOverlayToggles( overlay, choices ) {
		overlay.querySelectorAll( '[data-ravn-category-toggle]' ).forEach( function ( input ) {
			var cat = input.getAttribute( 'data-ravn-category-toggle' );
			if ( choices.hasOwnProperty( cat ) ) {
				input.checked = !! choices[ cat ];
			}
		} );
	}

	/**
	 * Slaat een keuze definitief op: cookie wegschrijven, scripts activeren,
	 * Consent Mode bijwerken en serverside loggen. Gedeeld door zowel de
	 * eerste-bezoek-flow als het later heropenen van de voorkeuren.
	 */
	function finalizeConsent( choices ) {
		writeConsentCookie( choices );
		applyConsent( choices );
		logConsentServerSide( choices );
	}

	function init() {
		var root = document.getElementById( 'ravn-root' );
		if ( ! root ) {
			return;
		}

		// Verplaats altijd naar een direct kind van <body>. Als een thema of
		// pagebuilder ergens een bovenliggende container met CSS transform/
		// filter/will-change heeft (gangbaar bij paginaovergangen, sticky
		// headers, sommige animaties), wordt position:fixed daarbinnen relatief
		// aan dié container in plaats van aan het scherm - waardoor de modal
		// niet meer gecentreerd op het beeldscherm staat. Dit voorkomt dat.
		if ( root.parentElement !== document.body ) {
			document.body.appendChild( root );
		}

		var existingConsent = readConsentCookie();
		if ( existingConsent ) {
			applyConsent( existingConsent );
			return; // Al een keuze gemaakt, geen banner nodig.
		}

		var banner = renderBanner( root );
		var overlay = renderModal( root );

		function finish( choices ) {
			finalizeConsent( choices );
			syncOverlayToggles( overlay, choices );
			banner.classList.remove( 'ravn-visible' );
			overlay.classList.remove( 'ravn-visible' );
		}

		root.addEventListener( 'click', function ( event ) {
			var action = event.target.getAttribute && event.target.getAttribute( 'data-ravn-action' );
			if ( ! action ) {
				return;
			}

			if ( action === 'accept' ) {
				finish( buildDefaultChoices( true ) );
			} else if ( action === 'reject' || action === 'reject-modal' ) {
				finish( buildDefaultChoices( false ) );
			} else if ( action === 'settings' ) {
				overlay.classList.add( 'ravn-visible' );
			} else if ( action === 'save-preferences' ) {
				finish( collectChoicesFromOverlay( overlay ) );
			}

			if ( event.target.classList.contains( 'ravn-modal__close' ) ) {
				overlay.classList.remove( 'ravn-visible' );
			}
		} );

		overlay.addEventListener( 'click', function ( event ) {
			if ( event.target === overlay ) {
				overlay.classList.remove( 'ravn-visible' );
			}
		} );
	}

	/**
	 * Maakt het mogelijk om de voorkeuren later opnieuw te openen,
	 * bijvoorbeeld via een link "Cookie-instellingen" in de footer:
	 * <a href="#" onclick="ravnOpenPreferences(); return false;">Cookie-instellingen</a>
	 */
	window.ravnOpenPreferences = function () {
		var overlay = document.querySelector( '.ravn-overlay' );
		if ( overlay ) {
			syncOverlayToggles( overlay, readConsentCookie() || {} );
			overlay.classList.add( 'ravn-visible' );
			return;
		}
		// Banner al afgehandeld en verwijderd: opnieuw opbouwen met huidige keuzes vooraf ingevuld.
		var root = document.getElementById( 'ravn-root' );
		var rebuilt = renderModal( root );
		var consent = readConsentCookie() || {};
		rebuilt.querySelectorAll( '[data-ravn-category-toggle]' ).forEach( function ( input ) {
			var cat = input.getAttribute( 'data-ravn-category-toggle' );
			if ( consent[ cat ] ) {
				input.checked = true;
			}
		} );
		rebuilt.classList.add( 'ravn-visible' );

		rebuilt.addEventListener( 'click', function ( event ) {
			var action = event.target.getAttribute && event.target.getAttribute( 'data-ravn-action' );

			if ( action === 'reject-modal' ) {
				finalizeConsent( buildDefaultChoices( false ) );
				rebuilt.classList.remove( 'ravn-visible' );
			} else if ( action === 'save-preferences' ) {
				finalizeConsent( collectChoicesFromOverlay( rebuilt ) );
				rebuilt.classList.remove( 'ravn-visible' );
			}

			if ( event.target === rebuilt || event.target.classList.contains( 'ravn-modal__close' ) ) {
				rebuilt.classList.remove( 'ravn-visible' );
			}
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
