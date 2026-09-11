# Ravn Cookie Consent

> [!WARNING]
> **Nog in ontwikkeling.** Deze plugin is niet af en niet getest voor productiegebruik. Gebruik op
> een live site is op eigen risico; instellingen, database-structuur en functienamen kunnen nog
> wijzigen zonder migratiepad.

Cookiemeldingsplugin voor WordPress, gericht op AVG-conforme toestemming voor Nederlandse
websites. Toont een cookiebanner met categorieën, houdt een cookieregister bij en integreert met
Google Consent Mode v2 zodat trackingscripts pas activeren na toestemming.

## Functies

- Cookiebanner met vier standaardcategorieën: noodzakelijk, functioneel, analytisch en marketing.
- Voorkeuren-popup waarin bezoekers per categorie toestemming geven of intrekken; "Weigeren" heeft
  visueel evenveel gewicht als "Accepteren".
- Cookieregister per categorie (naam, aanbieder, gedeeld met derden, doel, bewaartermijn) en de
  shortcode `[ravn_cookieverklaring]` om dit overzicht op een pagina te tonen.
- Ingebouwde, zelf-blokkerende integraties voor Google Analytics 4, Google Tag Manager en Meta
  (Facebook) Pixel, met ondersteuning voor Google Consent Mode v2.
- Handmatige scan en optionele tijdelijke live detectie om bekende trackingscripts en cookies op de
  site te vinden.
- Consent-log met instelbare bewaartermijn, dagelijkse automatische opschoning, en één klik om de
  beleidsversie te verhogen en opnieuw toestemming te vragen aan alle bezoekers.

## Installatie

1. Upload de map naar `wp-content/plugins/` of installeer de plugin via het WordPress-dashboard.
2. Activeer de plugin.
3. Ga naar **Cookiemelding** in het dashboardmenu om categorieën, cookies, teksten en integraties
   in te stellen.

Zie [readme.txt](readme.txt) voor de volledige WordPress-plugindocumentatie.

## Licentie

GPLv2 of later. Zie [LICENSE](LICENSE).
