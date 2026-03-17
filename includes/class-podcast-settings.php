<?php

namespace PodloveEpisodeLocation;

use Podlove\Settings\Podcast\Tab;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds a "Location" tab to the Podlove Podcast Settings page.
 *
 * Stores a default creator location for the whole podcast. This location
 * is used as a fallback when an episode does not have an explicit creator
 * location set, and is also emitted at the RSS channel level.
 */
class Podcast_Settings extends Tab
{
    private static $nonce = 'update_podcast_settings_location';
    private static $option_key = 'podlove_episode_location_podcast';

    public function init()
    {
        add_action($this->page_hook, [$this, 'register_page']);
    }

    /**
     * Register early hooks that must fire before admin_menu.
     *
     * Called directly from Episode_Location at plugins_loaded time,
     * because the Tab init() lifecycle runs during admin_menu — too late
     * for admin_init (form processing) and potentially unreliable for
     * admin_enqueue_scripts.
     */
    public static function register_early_hooks()
    {
        add_action('admin_init', [__CLASS__, 'process_form_static']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets_static']);
    }

    /**
     * Process the form submission and save location data.
     */
    public static function process_form_static()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== 'podlove_settings_podcast_handle') {
            return;
        }

        $tab = isset($_GET['podlove_tab']) ? sanitize_text_field($_GET['podlove_tab']) : '';
        if ($tab !== 'location') {
            return;
        }

        if (!isset($_POST['podlove_podcast_location'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!wp_verify_nonce($_REQUEST['_podlove_nonce'] ?? '', self::$nonce)) {
            return;
        }

        $raw = $_POST['podlove_podcast_location'];

        $data = [
            'location_name' => sanitize_text_field($raw['location_name'] ?? ''),
            'location_lat' => self::sanitize_coordinate($raw['location_lat'] ?? '', 'lat'),
            'location_lng' => self::sanitize_coordinate($raw['location_lng'] ?? '', 'lng'),
            'location_address' => sanitize_text_field($raw['location_address'] ?? ''),
            'location_country' => strtoupper(substr(sanitize_text_field($raw['location_country'] ?? ''), 0, 2)),
            'location_osm' => sanitize_text_field($raw['location_osm'] ?? ''),
        ];

        update_option(self::$option_key, $data);
        self::flush_podlove_feed_cache();

        $redirect = admin_url('admin.php?page=podlove_settings_podcast_handle&podlove_tab=location');
        wp_safe_redirect($redirect);

        exit;
    }

    /**
     * Render the Location tab content.
     */
    public function register_page()
    {
        $data = self::get_podcast_location();
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=podlove_settings_podcast_handle&podlove_tab=location')); ?>">
            <?php wp_nonce_field(self::$nonce, '_podlove_nonce'); ?>

            <p class="podlove-location-tab-hint" style="font-style: italic; color: #666; margin-bottom: 16px;">
                <?php esc_html_e('Set a default creator location for this podcast. It is emitted at the channel level in your RSS feed and used as a fallback in Podlove templates (e.g. {{ episode.locationCreatorName }}) when an episode has no explicit creator location.', 'podlove-episode-location'); ?>
            </p>

            <div id="podlove-podcast-location-wrapper">
                <div class="podlove-location-search-wrapper">
                    <label for="podlove-location-search-podcast">
                        <?php esc_html_e('Search Location', 'podlove-episode-location'); ?>
                    </label>
                    <div class="podlove-location-search-row">
                        <input
                            type="text"
                            id="podlove-location-search-podcast"
                            class="regular-text podlove-location-search-input"
                            data-rel="podcast"
                            placeholder="<?php esc_attr_e('Search for a place or address...', 'podlove-episode-location'); ?>"
                        />
                        <button type="button" class="button podlove-location-search-btn" data-rel="podcast">
                            <?php esc_html_e('Search', 'podlove-episode-location'); ?>
                        </button>
                    </div>
                    <div id="podlove-location-search-results-podcast" class="podlove-location-search-results"></div>
                </div>

                <div id="podlove-location-map-podcast" class="podlove-location-map"></div>

                <div class="podlove-location-fields">
                    <div class="podlove-location-field-row">
                        <label for="podlove-location-name-podcast">
                            <?php esc_html_e('Location Name', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-name-podcast"
                            name="podlove_podcast_location[location_name]"
                            class="regular-text"
                            value="<?php echo esc_attr($data['location_name']); ?>"
                            placeholder="<?php esc_attr_e('e.g. Berlin, Home Studio...', 'podlove-episode-location'); ?>"
                        />
                    </div>

                    <div class="podlove-location-field-row podlove-location-coords-row">
                        <div class="podlove-location-coord">
                            <label for="podlove-location-lat-podcast">
                                <?php esc_html_e('Latitude', 'podlove-episode-location'); ?>
                            </label>
                            <input
                                type="text"
                                id="podlove-location-lat-podcast"
                                name="podlove_podcast_location[location_lat]"
                                class="regular-text"
                                value="<?php echo esc_attr($data['location_lat']); ?>"
                                readonly
                            />
                        </div>
                        <div class="podlove-location-coord">
                            <label for="podlove-location-lng-podcast">
                                <?php esc_html_e('Longitude', 'podlove-episode-location'); ?>
                            </label>
                            <input
                                type="text"
                                id="podlove-location-lng-podcast"
                                name="podlove_podcast_location[location_lng]"
                                class="regular-text"
                                value="<?php echo esc_attr($data['location_lng']); ?>"
                                readonly
                            />
                        </div>
                    </div>

                    <div class="podlove-location-field-row">
                        <label for="podlove-location-address-podcast">
                            <?php esc_html_e('Address', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-address-podcast"
                            name="podlove_podcast_location[location_address]"
                            class="large-text"
                            value="<?php echo esc_attr($data['location_address']); ?>"
                            placeholder="<?php esc_attr_e('Full address (auto-filled from search)', 'podlove-episode-location'); ?>"
                        />
                    </div>

                    <div class="podlove-location-field-row podlove-location-extra-row">
                        <div class="podlove-location-coord">
                            <label for="podlove-location-country-podcast">
                                <?php esc_html_e('Country', 'podlove-episode-location'); ?>
                            </label>
                            <input
                                type="text"
                                id="podlove-location-country-podcast"
                                name="podlove_podcast_location[location_country]"
                                class="small-text"
                                value="<?php echo esc_attr($data['location_country']); ?>"
                                maxlength="2"
                                placeholder="<?php esc_attr_e('e.g. GB', 'podlove-episode-location'); ?>"
                            />
                        </div>
                        <div class="podlove-location-coord">
                            <label for="podlove-location-osm-podcast">
                                <?php esc_html_e('OSM ID', 'podlove-episode-location'); ?>
                            </label>
                            <input
                                type="text"
                                id="podlove-location-osm-podcast"
                                name="podlove_podcast_location[location_osm]"
                                class="regular-text"
                                value="<?php echo esc_attr($data['location_osm']); ?>"
                                placeholder="<?php esc_attr_e('e.g. R113314', 'podlove-episode-location'); ?>"
                            />
                        </div>
                    </div>

                    <div class="podlove-location-actions">
                        <button type="button" class="button podlove-location-clear-btn" data-rel="podcast">
                            <?php esc_html_e('Clear Location', 'podlove-episode-location'); ?>
                        </button>
                        <span class="podlove-location-hint">
                            <?php esc_html_e('Search for a location or click on the map to set the pin. Drag the marker to adjust.', 'podlove-episode-location'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php submit_button(__('Save Changes', 'podlove-episode-location')); ?>
        </form>
        <?php
    }

    /**
     * Enqueue map assets on the podcast settings page when Location tab is active.
     */
    public static function enqueue_assets_static()
    {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $tab = isset($_GET['podlove_tab']) ? sanitize_text_field($_GET['podlove_tab']) : '';

        if ($page !== 'podlove_settings_podcast_handle' || $tab !== 'location') {
            return;
        }

        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        wp_enqueue_style(
            'podlove-episode-location-admin',
            PODLOVE_EPISODE_LOCATION_URL.'assets/css/admin-map.css',
            ['leaflet'],
            PODLOVE_EPISODE_LOCATION_VERSION
        );

        wp_enqueue_script(
            'podlove-episode-location-admin',
            PODLOVE_EPISODE_LOCATION_URL.'assets/js/admin-map.js',
            ['jquery', 'leaflet'],
            PODLOVE_EPISODE_LOCATION_VERSION,
            true
        );
    }

    /**
     * Retrieve the stored podcast-level creator location.
     *
     * @return array
     */
    public static function get_podcast_location()
    {
        $defaults = [
            'location_name' => '',
            'location_lat' => '',
            'location_lng' => '',
            'location_address' => '',
            'location_country' => '',
            'location_osm' => '',
        ];

        $data = get_option(self::$option_key, $defaults);

        return wp_parse_args($data, $defaults);
    }

    /**
     * Check whether a podcast-level creator location has been set.
     *
     * @return bool
     */
    public static function has_podcast_location()
    {
        $data = self::get_podcast_location();

        return !empty($data['location_lat']) && !empty($data['location_lng']);
    }

    /**
     * Flush Podlove Publisher's feed cache transients so location
     * changes appear in the RSS feed immediately.
     */
    public static function flush_podlove_feed_cache()
    {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_podlove_cachev2_%'
                OR option_name LIKE '_transient_timeout_podlove_cachev2_%'"
        );
    }

    /**
     * Sanitize a coordinate value with range validation.
     *
     * @param string $value Raw input value
     * @param string $type  'lat' or 'lng'
     *
     * @return string
     */
    private static function sanitize_coordinate($value, $type)
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $float = (float) $value;

            if ($type === 'lat' && ($float < -90 || $float > 90)) {
                return '';
            }

            if ($type === 'lng' && ($float < -180 || $float > 180)) {
                return '';
            }

            return sprintf('%.8F', $float);
        }

        return '';
    }
}
