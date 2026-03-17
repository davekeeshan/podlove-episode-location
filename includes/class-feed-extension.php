<?php

namespace PodloveEpisodeLocation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds <podcast:location> tag to Podlove RSS feed entries (Podcasting 2.0 namespace).
 */
class Feed_Extension
{
    public function __construct()
    {
        add_action('podlove_append_to_feed_entry', [$this, 'add_location_to_feed'], 10, 4);
    }

    /**
     * Output the <podcast:location> tag for a feed entry.
     *
     * @param mixed $podcast
     * @param mixed $episode
     * @param mixed $feed
     * @param mixed $format
     */
    public function add_location_to_feed($podcast, $episode, $feed, $format)
    {
        $location = Location_Model::find_by_episode_id($episode->id);

        if (!$location || (empty($location->location_lat) && empty($location->location_lng))) {
            return;
        }

        $geo = sprintf('geo:%s,%s', $location->location_lat, $location->location_lng);
        $name = !empty($location->location_name) ? esc_html($location->location_name) : '';

        if ($name) {
            echo sprintf("\n\t\t<podcast:location geo=\"%s\">%s</podcast:location>", esc_attr($geo), $name);
        } else {
            echo sprintf("\n\t\t<podcast:location geo=\"%s\" />", esc_attr($geo));
        }
    }
}
