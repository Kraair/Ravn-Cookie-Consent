<?php

/**
 * Plugin Name: Ravn Cookie Consent
 * Description: Cookiemelding met categorieën, cookie-registratie en voorkeuren-popup.
 * Version: 1.3.0
 * Author: KraaiR
 * Author URI: https://tammohaan.nl
 * Text Domain: ravn
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
	exit; // Direct toegang niet toegestaan.
}

define('RAVN_CC_VERSION', '1.3.0');
define('RAVN_CC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RAVN_CC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once RAVN_CC_PLUGIN_DIR . 'includes/class-ravn-db.php';
require_once RAVN_CC_PLUGIN_DIR . 'includes/class-ravn-known-cookies.php';
require_once RAVN_CC_PLUGIN_DIR . 'includes/class-ravn-scanner.php';
require_once RAVN_CC_PLUGIN_DIR . 'includes/class-ravn-admin.php';
require_once RAVN_CC_PLUGIN_DIR . 'includes/class-ravn-frontend.php';

/**
 * Activatie: tabellen aanmaken + standaardcategorieën wegschrijven + dagelijkse opschoning inplannen.
 */
function ravn_activate_plugin()
{
	RAVN_CC_DB::create_tables();
	RAVN_CC_DB::maybe_seed_default_categories();

	if (! wp_next_scheduled('ravn_daily_cleanup')) {
		wp_schedule_event(time(), 'daily', 'ravn_daily_cleanup');
	}
}
register_activation_hook(__FILE__, 'ravn_activate_plugin');

/**
 * Deactivatie: geplande opschoontaak weer verwijderen.
 */
function ravn_deactivate_plugin()
{
	wp_clear_scheduled_hook('ravn_daily_cleanup');
}
register_deactivation_hook(__FILE__, 'ravn_deactivate_plugin');

/**
 * Dagelijkse opschoning: verwijdert consent-logregels ouder dan de
 * ingestelde bewaartermijn (standaard 24 maanden).
 */
function ravn_run_daily_cleanup()
{
	$months = (int) get_option('ravn_consent_log_retention_months', 24);
	if ($months > 0) {
		RAVN_CC_DB::purge_old_consent_logs($months);
	}
}
add_action('ravn_daily_cleanup', 'ravn_run_daily_cleanup');

/**
 * Plugin opstarten.
 */
/**
 * Plugin opstarten. Draait dbDelta opnieuw als de plugin geüpdatet is,
 * zodat nieuwe kolommen (zoals 'shared_with') ook op bestaande installaties
 * worden toegevoegd zonder dat de gebruiker moet de-activeren/activeren.
 */
function ravn_maybe_upgrade()
{
	$installed_version = get_option('ravn_db_version', '');
	if ($installed_version !== RAVN_CC_VERSION) {
		RAVN_CC_DB::create_tables();
		update_option('ravn_db_version', RAVN_CC_VERSION);
	}
	if (! wp_next_scheduled('ravn_daily_cleanup')) {
		wp_schedule_event(time(), 'daily', 'ravn_daily_cleanup');
	}
}

function ravn_init_plugin()
{
	ravn_maybe_upgrade();
	new RAVN_CC_Admin();
	new RAVN_CC_Frontend();
}
add_action('plugins_loaded', 'ravn_init_plugin');
