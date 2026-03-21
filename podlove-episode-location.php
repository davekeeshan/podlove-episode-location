<?php
/**
 * Plugin Name: Podlove Episode Location
 * Plugin URI:  https://github.com/davekeeshan/podlove-episode-location
 * Description: Adds dual episode location (subject & creator) with interactive maps to Podlove Publisher. Registers as a Podlove module.
 * Version:     1.0.0
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

define('PODLOVE_EPISODE_LOCATION_VERSION', '1.0.0');
define('PODLOVE_EPISODE_LOCATION_FILE', __FILE__);
define('PODLOVE_EPISODE_LOCATION_DIR', plugin_dir_path(__FILE__));
define('PODLOVE_EPISODE_LOCATION_URL', plugin_dir_url(__FILE__));

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
 * Create database table on plugin activation.
 */
function podlove_episode_location_activate()
{
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/model/location.php';
    Location::build();

    if (podlove_episode_location_is_replaced_by_native_module()) {
        return;
    }

    // Auto-enable the module in Podlove's active modules list on first activation
    require_once PODLOVE_EPISODE_LOCATION_DIR.'includes/module_registration.php';
    Module_Registration::activate();
}
register_activation_hook(__FILE__, 'podlove_episode_location_activate');
