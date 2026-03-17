<?php

namespace PodloveEpisodeLocation;

use Podlove\Model\Episode;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers and renders the Episode Location meta box with tabbed UI
 * for Subject and Creator locations.
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
     * Render the tabbed meta box HTML.
     *
     * @param \WP_Post $post
     */
    public function render($post)
    {
        $episode = Episode::find_one_by_property('post_id', $post->ID);

        $subject = $this->get_location_data($episode, 'subject');
        $creator = $this->get_location_data($episode, 'creator');

        wp_nonce_field('podlove_episode_location_save', 'podlove_episode_location_nonce');
        ?>
        <div id="podlove-episode-location-wrapper">
            <div class="podlove-location-tabs">
                <button type="button" class="podlove-location-tab active" data-tab="subject">
                    <?php esc_html_e('Subject Location', 'podlove-episode-location'); ?>
                </button>
                <button type="button" class="podlove-location-tab" data-tab="creator">
                    <?php esc_html_e('Creator Location', 'podlove-episode-location'); ?>
                </button>
            </div>

            <?php $this->render_tab_panel('subject', $subject, __('Where is this episode about?', 'podlove-episode-location')); ?>
            <?php $this->render_tab_panel('creator', $creator, __('Where was this episode recorded?', 'podlove-episode-location')); ?>
        </div>
        <?php
    }

    /**
     * Save location data for both rel types when the post is saved.
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

        $all_data = $_POST['podlove_episode_location'];

        foreach (['subject', 'creator'] as $rel) {
            $data = isset($all_data[$rel]) ? $all_data[$rel] : [];
            $this->save_rel($episode->id, $rel, $data);
        }
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
            PODLOVE_EPISODE_LOCATION_URL.'assets/css/admin-map.css',
            ['leaflet'],
            PODLOVE_EPISODE_LOCATION_VERSION
        );

        // Plugin JS
        wp_enqueue_script(
            'podlove-episode-location-admin',
            PODLOVE_EPISODE_LOCATION_URL.'assets/js/admin-map.js',
            ['jquery', 'leaflet'],
            PODLOVE_EPISODE_LOCATION_VERSION,
            true
        );
    }

    /**
     * Render a single tab panel with map, search, and form fields.
     *
     * @param string $rel  'subject' or 'creator'
     * @param array  $data Location data
     * @param string $hint Descriptive hint text
     */
    private function render_tab_panel($rel, $data, $hint)
    {
        $active = ($rel === 'subject') ? ' active' : '';
        ?>
        <div class="podlove-location-tab-panel<?php echo $active; ?>" data-tab="<?php echo esc_attr($rel); ?>">
            <p class="podlove-location-tab-hint"><?php echo esc_html($hint); ?></p>

            <div class="podlove-location-search-wrapper">
                <label for="podlove-location-search-<?php echo esc_attr($rel); ?>">
                    <?php esc_html_e('Search Location', 'podlove-episode-location'); ?>
                </label>
                <div class="podlove-location-search-row">
                    <input
                        type="text"
                        id="podlove-location-search-<?php echo esc_attr($rel); ?>"
                        class="regular-text podlove-location-search-input"
                        data-rel="<?php echo esc_attr($rel); ?>"
                        placeholder="<?php esc_attr_e('Search for a place or address...', 'podlove-episode-location'); ?>"
                    />
                    <button type="button" class="button podlove-location-search-btn" data-rel="<?php echo esc_attr($rel); ?>">
                        <?php esc_html_e('Search', 'podlove-episode-location'); ?>
                    </button>
                </div>
                <div id="podlove-location-search-results-<?php echo esc_attr($rel); ?>" class="podlove-location-search-results"></div>
            </div>

            <div id="podlove-location-map-<?php echo esc_attr($rel); ?>" class="podlove-location-map"></div>

            <div class="podlove-location-fields">
                <div class="podlove-location-field-row">
                    <label for="podlove-location-name-<?php echo esc_attr($rel); ?>">
                        <?php esc_html_e('Location Name', 'podlove-episode-location'); ?>
                    </label>
                    <input
                        type="text"
                        id="podlove-location-name-<?php echo esc_attr($rel); ?>"
                        name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_name]"
                        class="regular-text"
                        value="<?php echo esc_attr($data['location_name']); ?>"
                        placeholder="<?php esc_attr_e('e.g. Berlin, Conference Hall...', 'podlove-episode-location'); ?>"
                    />
                </div>

                <div class="podlove-location-field-row podlove-location-coords-row">
                    <div class="podlove-location-coord">
                        <label for="podlove-location-lat-<?php echo esc_attr($rel); ?>">
                            <?php esc_html_e('Latitude', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-lat-<?php echo esc_attr($rel); ?>"
                            name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_lat]"
                            class="regular-text"
                            value="<?php echo esc_attr($data['location_lat']); ?>"
                            readonly
                        />
                    </div>
                    <div class="podlove-location-coord">
                        <label for="podlove-location-lng-<?php echo esc_attr($rel); ?>">
                            <?php esc_html_e('Longitude', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-lng-<?php echo esc_attr($rel); ?>"
                            name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_lng]"
                            class="regular-text"
                            value="<?php echo esc_attr($data['location_lng']); ?>"
                            readonly
                        />
                    </div>
                </div>

                <div class="podlove-location-field-row">
                    <label for="podlove-location-address-<?php echo esc_attr($rel); ?>">
                        <?php esc_html_e('Address', 'podlove-episode-location'); ?>
                    </label>
                    <input
                        type="text"
                        id="podlove-location-address-<?php echo esc_attr($rel); ?>"
                        name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_address]"
                        class="large-text"
                        value="<?php echo esc_attr($data['location_address']); ?>"
                        placeholder="<?php esc_attr_e('Full address (auto-filled from search)', 'podlove-episode-location'); ?>"
                    />
                </div>

                <div class="podlove-location-field-row podlove-location-extra-row">
                    <div class="podlove-location-coord">
                        <label for="podlove-location-country-<?php echo esc_attr($rel); ?>">
                            <?php esc_html_e('Country', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-country-<?php echo esc_attr($rel); ?>"
                            name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_country]"
                            class="small-text"
                            value="<?php echo esc_attr($data['location_country']); ?>"
                            maxlength="2"
                            placeholder="<?php esc_attr_e('e.g. GB', 'podlove-episode-location'); ?>"
                        />
                    </div>
                    <div class="podlove-location-coord">
                        <label for="podlove-location-osm-<?php echo esc_attr($rel); ?>">
                            <?php esc_html_e('OSM ID', 'podlove-episode-location'); ?>
                        </label>
                        <input
                            type="text"
                            id="podlove-location-osm-<?php echo esc_attr($rel); ?>"
                            name="podlove_episode_location[<?php echo esc_attr($rel); ?>][location_osm]"
                            class="regular-text"
                            value="<?php echo esc_attr($data['location_osm']); ?>"
                            placeholder="<?php esc_attr_e('e.g. R113314', 'podlove-episode-location'); ?>"
                        />
                    </div>
                </div>

                <p class="podlove-location-hint">
                    <?php esc_html_e('Search for a location or click on the map to set the pin. Drag the marker to adjust. Country and OSM ID are auto-filled from search results.', 'podlove-episode-location'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Get location data array for a given episode and rel type.
     *
     * @param null|Episode $episode
     * @param string       $rel
     *
     * @return array
     */
    private function get_location_data($episode, $rel)
    {
        $defaults = [
            'location_name' => '',
            'location_lat' => '',
            'location_lng' => '',
            'location_address' => '',
            'location_country' => '',
            'location_osm' => '',
        ];

        if (!$episode) {
            return $defaults;
        }

        $location = Location_Model::find_by_episode_id_and_rel($episode->id, $rel);
        if (!$location) {
            return $defaults;
        }

        return [
            'location_name' => $location->location_name,
            'location_lat' => $location->location_lat,
            'location_lng' => $location->location_lng,
            'location_address' => $location->location_address,
            'location_country' => $location->location_country,
            'location_osm' => $location->location_osm,
        ];
    }

    /**
     * Save a single rel type's location data.
     *
     * @param int    $episode_id
     * @param string $rel
     * @param array  $data
     */
    private function save_rel($episode_id, $rel, $data)
    {
        $location_name = sanitize_text_field($data['location_name'] ?? '');
        $location_lat = self::sanitize_coordinate($data['location_lat'] ?? '', 'lat');
        $location_lng = self::sanitize_coordinate($data['location_lng'] ?? '', 'lng');
        $location_address = sanitize_text_field($data['location_address'] ?? '');
        $location_country = sanitize_text_field($data['location_country'] ?? '');
        $location_osm = sanitize_text_field($data['location_osm'] ?? '');

        // Ensure country code is uppercase and max 2 chars
        $location_country = strtoupper(substr($location_country, 0, 2));

        $location = Location_Model::find_by_episode_id_and_rel($episode_id, $rel);

        // If all fields are empty, delete existing record
        if (empty($location_name) && empty($location_lat) && empty($location_lng)
            && empty($location_address) && empty($location_country) && empty($location_osm)
        ) {
            if ($location) {
                $location->delete();
            }

            return;
        }

        if (!$location) {
            $location = new Location_Model();
            $location->episode_id = $episode_id;
            $location->rel = $rel;
        }

        $location->location_name = $location_name;
        $location->location_lat = $location_lat;
        $location->location_lng = $location_lng;
        $location->location_address = $location_address;
        $location->location_country = $location_country;
        $location->location_osm = $location_osm;
        $location->save();
    }

    /**
     * Sanitize a coordinate value.
     *
     * @param string $value raw input value
     * @param string $type  'lat' or 'lng' for basic range validation
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

            // Basic range checks to avoid obviously invalid coordinates.
            if ($type === 'lat' && ($float < -90 || $float > 90)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(
                        sprintf(
                            '[Podlove Episode Location] Discarding out-of-range latitude value: %s',
                            $value
                        )
                    );
                }

                return '';
            }

            if ($type === 'lng' && ($float < -180 || $float > 180)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(
                        sprintf(
                            '[Podlove Episode Location] Discarding out-of-range longitude value: %s',
                            $value
                        )
                    );
                }

                return '';
            }

            return sprintf('%.8F', $float);
        }

        return '';
    }
}
