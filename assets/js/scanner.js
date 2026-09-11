/**
 * Ravn Cookie Consent - live detectiescript.
 * Draait alleen als "Live detectie" in het dashboard is aangezet
 * (bedoeld voor een beperkte inventarisatieperiode, niet permanent).
 *
 * Leest document.cookie uit (alleen namen, geen waarden) en meldt nieuwe,
 * nog onbekende cookienamen aan het dashboard zodat ze daar beoordeeld
 * kunnen worden.
 */
( function () {
	'use strict';

	if ( ! window.ravnScanData || ! window.ravnScanData.ajaxUrl ) {
		return;
	}

	var CHECK_DELAYS = [ 1000, 4000, 10000 ]; // meteen, na 4s, na 10s - vangt scripts die vertraagd laden

	function getCurrentCookieNames() {
		return document.cookie
			.split( ';' )
			.map( function ( part ) {
				return part.split( '=' )[ 0 ].trim();
			} )
			.filter( function ( name ) {
				return name.length > 0;
			} );
	}

	function reportNames( names ) {
		if ( ! names.length ) {
			return;
		}
		var body = new URLSearchParams();
		body.append( 'action', 'ravn_report_cookies' );
		body.append( 'nonce', window.ravnScanData.nonce );
		body.append( 'cookies', JSON.stringify( names ) );

		fetch( window.ravnScanData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} );
	}

	function checkOnce() {
		reportNames( getCurrentCookieNames() );
	}

	CHECK_DELAYS.forEach( function ( delay ) {
		setTimeout( checkOnce, delay );
	} );
} )();
