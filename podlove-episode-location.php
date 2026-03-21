<?php
/**
 * Plugin Name: Podlove Episode Location
 * Plugin URI:  https://github.com/davekeeshan/podlove-episode-location
 * Description: Adds dual episode location (subject & creator) with interactive maps to Podlove Publisher. Registers as a Podlove module.
 * Version:     1.0.2
 * Author:      Dave Keeshan
 * Author URI:  https://github.com/davekeeshan
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: podlove-episode-location
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

use Podlove\Modules\EpisodeLocation\Episode_Location;
use Podlove\Modules\EpisodeLocation\Model\Location;
use Podlove\Modules\EpisodeLocation\Module_Registration;

define('PODLOVE_EPISODE_LOCATION_VERSION', '1.0.2');
define('PODLOVE_EPISODE_LOCATION_FILE', __FILE__);
define('PODLOVE_EPISODE_LOCATION_DIR', plugin_dir_path(__FILE__));
define('PODLOVE_EPISODE_LOCATION_URL', plugin_dir_url(__FILE__));
define(
    'PODLOVE_EPISODE_LOCATION_BASENAME',
    plugin_basename(PODLOVE_EPISODE_LOCATION_FILE)
);

/**
 * Per-site activation work: DB table + enable module in podlove_active_modules.
 *
 * Must run in the correct blog context (use switch_to_blog for multisite).
 */
function podlove_episode_location_activate_current_site()
{
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/model/location.php';
    Location::build();

    if (podlove_episode_location_is_replaced_by_native_module()) {
        return;
    }

    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/module_registration.php';
    Module_Registration::activate();
}

/**
 * One-time fix: network activation used to run only on the main site, so subsites
 * never got the module flag in podlove_active_modules and the plugin stayed "off".
 */
function podlove_episode_location_maybe_migrate_multisite_network()
{
    if (!is_multisite()) {
        return;
    }

    if (!function_exists('is_plugin_active_for_network')
        || !is_plugin_active_for_network(PODLOVE_EPISODE_LOCATION_BASENAME)) {
        return;
    }

    if (get_site_option('podlove_episode_location_network_sites_bootstrapped')) {
        return;
    }

    foreach (get_sites(['number' => 0]) as $site) {
        switch_to_blog((int) $site->blog_id);
        podlove_episode_location_activate_current_site();
        restore_current_blog();
    }

    update_site_option('podlove_episode_location_network_sites_bootstrapped', '1');
}
add_action('plugins_loaded', 'podlove_episode_location_maybe_migrate_multisite_network', 5);

/**
 * When a new site is added to the network, repeat per-site setup if we are network-active.
 *
 * @param \WP_Site $new_site New site object (WP 5.1+).
 */
function podlove_episode_location_on_initialize_site($new_site)
{
    if (!is_multisite()
        || !function_exists('is_plugin_active_for_network')
        || !is_plugin_active_for_network(PODLOVE_EPISODE_LOCATION_BASENAME)) {
        return;
    }

    switch_to_blog((int) $new_site->blog_id);
    podlove_episode_location_activate_current_site();
    restore_current_blog();
}
// New site in a network (WP 5.1+). WP 5.0 fallback: wpmu_new_blog.
if (version_compare($GLOBALS['wp_version'], '5.1', '>=')) {
    add_action('wp_initialize_site', 'podlove_episode_location_on_initialize_site', 10, 1);
} else {
    add_action('wpmu_new_blog', 'podlove_episode_location_on_wpmu_new_blog', 10, 6);
}

/**
 * @param int $blog_id
 */
function podlove_episode_location_on_wpmu_new_blog($blog_id)
{
    if (!is_multisite()
        || !function_exists('is_plugin_active_for_network')
        || !is_plugin_active_for_network(PODLOVE_EPISODE_LOCATION_BASENAME)) {
        return;
    }

    switch_to_blog((int) $blog_id);
    podlove_episode_location_activate_current_site();
    restore_current_blog();
}

/**
 * Check if Podlove Publisher is active before loading.
 */
function podlove_episode_location_check_dependencies()
{
    if (!class_exists('\Podlove\Model\Episode')) {
        add_action('admin_notices', 'podlove_episode_location_missing_dependency_notice');

        return false;
    }

    return true;
}

/**
 * Show admin notice when Podlove Publisher is not active.
 */
function podlove_episode_location_missing_dependency_notice()
{
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('Podlove Episode Location', 'podlove-episode-location'); ?>:</strong>
            <?php esc_html_e('This plugin requires Podlove Publisher to be installed and active.', 'podlove-episode-location'); ?>
        </p>
    </div>
    <?php
}

/**
 * Detect if the native Podlove Publisher Locations module has replaced this plugin.
 */
function podlove_episode_location_is_replaced_by_native_module()
{
    if (!class_exists('\Podlove\Modules\Base')) {
        return false;
    }

    return \Podlove\Modules\Base::is_active('locations');
}

/**
 * Show admin notice when the native Locations module has taken over.
 */
function podlove_episode_location_replaced_notice()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('Podlove Episode Location', 'podlove-episode-location'); ?>:</strong>
            <?php esc_html_e('The native Podlove Publisher Locations module is active and now takes precedence over this standalone plugin while using the same stored data.', 'podlove-episode-location'); ?>
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>">
                <?php esc_html_e('Deactivate this plugin', 'podlove-episode-location'); ?>
            </a>
        </p>
    </div>
    <?php
}

/**
 * Initialize the plugin after all plugins are loaded.
 */
function podlove_episode_location_init()
{
    if (!podlove_episode_location_check_dependencies()) {
        return;
    }

    if (podlove_episode_location_is_replaced_by_native_module()) {
        add_action('admin_notices', 'podlove_episode_location_replaced_notice');

        return;
    }

    // Always load the module registration class so our entry appears on
    // the Podlove Modules settings page even when the module is disabled.
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/module_registration.php';
    new Module_Registration();

    // Only load the full plugin functionality when the module is active.
    if (!Module_Registration::is_active()) {
        return;
    }

    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/model/location.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/meta_box.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/template_extensions.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/feed_extension.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/podcast_settings.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/podcast-import-episode-locations-job.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/export_import.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/episode_location.php';

    Episode_Location::instance();
}
add_action('plugins_loaded', 'podlove_episode_location_init', 20);

/**
 * Create database table on plugin activation and enable the Podlove module on this site.
 *
 * @param bool $network_wide True when activated from Network Admin for the whole network.
 */
function podlove_episode_location_activate($network_wide = false)
{
    if ($network_wide && is_multisite()) {
        foreach (get_sites(['number' => 0]) as $site) {
            switch_to_blog((int) $site->blog_id);
            podlove_episode_location_activate_current_site();
            restore_current_blog();
        }
        update_site_option('podlove_episode_location_network_sites_bootstrapped', '1');

        return;
    }

    podlove_episode_location_activate_current_site();
}
register_activation_hook(__FILE__, 'podlove_episode_location_activate');
