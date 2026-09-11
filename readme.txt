=== Ravn Cookie Consent ===
Contributors: ravn
Tags: cookies, consent, gdpr, avg, privacy
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookiemelding met categorieën, cookie-registratie, voorkeuren-popup en trackingscript-scanner voor WordPress.

== Description ==

Ravn Cookie Consent is een cookiemeldingsplugin voor WordPress, gericht op AVG-conforme toestemming
voor Nederlandse websites. De plugin toont een cookiebanner met categorieën, houdt bij welke cookies
op de website gezet worden en integreert met Google Consent Mode v2 zodat trackingscripts pas
activeren nadat toestemming is gegeven.

**Belangrijkste functies**

* Cookiebanner met vier standaardcategorieën: noodzakelijk, functioneel, analytisch en marketing.
* Voorkeuren-popup waarin bezoekers per categorie toestemming kunnen geven of intrekken.
* "Weigeren" krijgt visueel evenveel gewicht als "Accepteren", conform de eisen van de Autoriteit
  Persoonsgegevens.
* Cookieregister per categorie: naam, aanbieder, gedeeld-met-derden, doel en bewaartermijn.
* Shortcode `[ravn_cookieverklaring]` om het volledige cookieoverzicht op een pagina te tonen.
* Ingebouwde integraties voor Google Analytics 4, Google Tag Manager en Meta (Facebook) Pixel; de
  plugin genereert de scripts zelf en blokkeert ze tot de juiste categorie is toegestaan.
* Ondersteuning voor Google Consent Mode v2 (standaard "denied" totdat toestemming is gegeven).
* Handmatige scan die de homepage en recente pagina's/berichten doorzoekt op bekende
  trackingscripts (Google Analytics, GTM, Meta Pixel, Hotjar, LinkedIn, TikTok en meer).
* Optionele live detectie die daadwerkelijk gezette cookies in de browser van bezoekers herkent,
  bedoeld voor een tijdelijke inventarisatieperiode.
* Consent-log met instelbare bewaartermijn en een dagelijkse automatische opschoontaak.
* Eén klik om de beleidsversie te verhogen, waarmee eerder gegeven toestemming ongeldig wordt
  verklaard en bezoekers opnieuw om toestemming wordt gevraagd.

== Installation ==

1. Upload de map `ravn-cookie-consent` naar `/wp-content/plugins/`, of installeer de plugin via
   het WordPress-dashboard.
2. Activeer de plugin via het menu 'Plugins' in WordPress.
3. Ga naar het nieuwe menu-item **Cookiemelding** in het dashboard om categorieën, cookies,
   teksten en integraties in te stellen.
4. Voer eventueel een scan uit of zet live detectie tijdelijk aan om te inventariseren welke
   cookies de site daadwerkelijk zet.

== Frequently Asked Questions ==

= Hoe voeg ik trackingscripts toe die niet via de ingebouwde integraties lopen? =

Zet het scripttype op `text/plain` en voeg het attribuut `data-ravn-category` toe met de
bijbehorende categorie-slug, bijvoorbeeld:

`<script type="text/plain" data-ravn-category="analytics" data-src="https://voorbeeld.tld/script.js"></script>`

De plugin activeert dit script pas zodra de bezoeker toestemming geeft voor die categorie.

= Hoe vraag ik opnieuw toestemming aan alle bezoekers? =

Ga naar het tabblad **Privacy & bewaartermijn** en klik op "Opnieuw toestemming vragen aan alle
bezoekers". Dit verhoogt de beleidsversie, waardoor bestaande toestemmingscookies ongeldig worden.

= Waar blijft de logging van gegeven toestemming? =

Toestemming wordt serverside gelogd met een gehashte combinatie van IP-adres en user agent (geen
leesbaar persoonsgegeven), gekoppeld aan de gekozen categorieën. Regels ouder dan de ingestelde
bewaartermijn (standaard 24 maanden) worden dagelijks automatisch verwijderd.

== Changelog ==

= 1.3.0 =
* Herdoopt naar Ravn Cookie Consent.

== Upgrade Notice ==

= 1.3.0 =
Hernoemd naar Ravn Cookie Consent. Functionaliteit en opgeslagen instellingen blijven ongewijzigd.
