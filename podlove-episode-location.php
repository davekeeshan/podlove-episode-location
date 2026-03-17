<?php
/**
 * Plugin Name: Podlove Episode Location
 * Plugin URI:  https://github.com/davekeeshan/podlove-episode-location
 * Description: Adds episode location/map functionality to Podlove Publisher using OpenStreetMap and Leaflet.js.
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
 * Initialize the plugin after all plugins are loaded.
 */
function podlove_episode_location_init()
{
    if (!podlove_episode_location_check_dependencies()) {
        return;
    }

    require_once PODLOVE_EPISODE_LOCATION_DIR . 'includes/class-location-model.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR . 'includes/class-meta-box.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR . 'includes/class-template-extensions.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR . 'includes/class-feed-extension.php';
    require_once PODLOVE_EPISODE_LOCATION_DIR . 'includes/class-episode-location.php';

    PodloveEpisodeLocation\Episode_Location::instance();
}
add_action('plugins_loaded', 'podlove_episode_location_init', 20);

/**
 * Create database table on plugin activation.
 */
function podlove_episode_location_activate()
{
    // Load the model so we can build the table
    require_once PODLOVE_EPISODE_LOCATION_DIR . 'includes/class-location-model.php';
    PodloveEpisodeLocation\Location_Model::build();
}
register_activation_hook(__FILE__, 'podlove_episode_location_activate');
