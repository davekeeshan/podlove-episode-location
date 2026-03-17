<?php

namespace PodloveEpisodeLocation;

use Podlove\Model\Episode;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers and renders the Episode Location meta box on the podcast edit screen.
 */
class Meta_Box
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register']);
        add_action('save_post_podcast', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Register the meta box on the podcast post type.
     */
    public function register()
    {
        add_meta_box(
            'podlove_episode_location',
            __('Episode Location', 'podlove-episode-location'),
            [$this, 'render'],
            'podcast',
            'normal',
            'low'
        );
    }

    /**
     * Render the meta box HTML.
     *
     * @param \WP_Post $post
     */
    public function render($post)
    {
        $episode = Episode::find_one_by_property('post_id', $post->ID);

        $location_name    = '';
        $location_lat     = '';
        $location_lng     = '';
        $location_address = '';

        if ($episode) {
            $location = Location_Model::find_by_episode_id($episode->id);
            if ($location) {
                $location_name    = $location->location_name;
                $location_lat     = $location->location_lat;
                $location_lng     = $location->location_lng;
                $location_address = $location->location_address;
            }
        }

        wp_nonce_field('podlove_episode_location_save', 'podlove_episode_location_nonce');
        ?>
        <div id="podlove-episode-location-wrapper">
            <div class="podlove-location-search-wrapper">
                <label for="podlove-location-search">
                    <?php esc_html_e('Search Location', 'podlove-episode-location'); ?>
                </label>
                <div class="podlove-location-search-row">
                    <input
                        type="text"
                        id="podlove-location-search"
                        class="regular-text"
                        placeholder="<?php esc_attr_e('Search for a place or address...', 'podlove-episode-location'); ?>"
                    />
                    <button type="button" id="podlove-location-search-btn" class="button">
                        <?php esc_html_e('Search', 'podlove-episode-location'); ?>
                    </button>
                </div>
                <div id="podlove-location-search-results"></div>
            </div>

            <div id="podlove-location-map"></div>

            <div class="podlove-location-fields">
                <div class="podlove-location-field-row">
                    <label for="podlove-location-name">
                        <?php esc_html_e('Location Name', 'podlove-episode-location'); ?>
                    </label>
                    <input
                        type="text"
                        id="podlove-location-name"
                        name="podlove_episode_location[location_name]"
                        class="regular-text"
                        value="<?php echo esc_attr($location_name); ?>"
                        placeholder="<?php esc_attr_e('e.g. Berlin, Conference Hall...', 'podlove-episode-location'); ?>"
                    />
                </div>

                <div class="podlove-location-field-row podlove-location-coords-row">
                    <div class="podlove-location-coord">
                        <label for="podlove-location-lat">
                            <?php esc_html_e('Latitude', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-lat"
                            name="podlove_episode_location[location_lat]"
                            class="regular-text"
                            value="<?php echo esc_attr($location_lat); ?>"
                            readonly
                        />
                    </div>
                    <div class="podlove-location-coord">
                        <label for="podlove-location-lng">
                            <?php esc_html_e('Longitude', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-lng"
                            name="podlove_episode_location[location_lng]"
                            class="regular-text"
                            value="<?php echo esc_attr($location_lng); ?>"
                            readonly
                        />
                    </div>
                </div>

                <div class="podlove-location-field-row">
                    <label for="podlove-location-address">
                        <?php esc_html_e('Address', 'podlove-episode-location'); ?>
                    </label>
                    <input
                        type="text"
                        id="podlove-location-address"
                        name="podlove_episode_location[location_address]"
                        class="large-text"
                        value="<?php echo esc_attr($location_address); ?>"
                        placeholder="<?php esc_attr_e('Full address (auto-filled from search)', 'podlove-episode-location'); ?>"
                    />
                </div>

                <p class="podlove-location-hint">
                    <?php esc_html_e('Search for a location or click on the map to set the pin. Drag the marker to adjust.', 'podlove-episode-location'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Save location data when the post is saved.
     *
     * @param int      $post_id
     * @param \WP_Post $post
     */
    public function save($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['podlove_episode_location_nonce'])
            || !wp_verify_nonce($_POST['podlove_episode_location_nonce'], 'podlove_episode_location_save')
        ) {
            return;
        }

        if (!isset($_POST['podlove_episode_location'])) {
            return;
        }

        $episode = Episode::find_one_by_property('post_id', $post_id);
        if (!$episode) {
            return;
        }

        $data = $_POST['podlove_episode_location'];

        $location_name    = sanitize_text_field($data['location_name'] ?? '');
        $location_lat     = self::sanitize_coordinate($data['location_lat'] ?? '');
        $location_lng     = self::sanitize_coordinate($data['location_lng'] ?? '');
        $location_address = sanitize_text_field($data['location_address'] ?? '');

        $location = Location_Model::find_by_episode_id($episode->id);

        // If all fields are empty, delete existing record
        if (empty($location_name) && empty($location_lat) && empty($location_lng) && empty($location_address)) {
            if ($location) {
                $location->delete();
            }
            return;
        }

        if (!$location) {
            $location = new Location_Model();
            $location->episode_id = $episode->id;
        }

        $location->location_name    = $location_name;
        $location->location_lat     = $location_lat;
        $location->location_lng     = $location_lng;
        $location->location_address = $location_address;
        $location->save();
    }

    /**
     * Enqueue Leaflet.js and custom assets on episode edit pages only.
     *
     * @param string $hook_suffix
     */
    public function enqueue_assets($hook_suffix)
    {
        if (!in_array($hook_suffix, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'podcast') {
            return;
        }

        // Leaflet CSS
        wp_enqueue_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            [],
            '1.9.4'
        );

        // Leaflet JS
        wp_enqueue_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            [],
            '1.9.4',
            true
        );

        // Plugin CSS
        wp_enqueue_style(
            'podlove-episode-location-admin',
            PODLOVE_EPISODE_LOCATION_URL . 'assets/css/admin-map.css',
            ['leaflet'],
            PODLOVE_EPISODE_LOCATION_VERSION
        );

        // Plugin JS
        wp_enqueue_script(
            'podlove-episode-location-admin',
            PODLOVE_EPISODE_LOCATION_URL . 'assets/js/admin-map.js',
            ['jquery', 'leaflet'],
            PODLOVE_EPISODE_LOCATION_VERSION,
            true
        );
    }

    /**
     * Sanitize a coordinate value.
     *
     * @param string $value
     * @return string
     */
    private static function sanitize_coordinate($value)
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (string) floatval($value);
        }

        return '';
    }
}
