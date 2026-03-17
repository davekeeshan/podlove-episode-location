<?php

namespace PodloveEpisodeLocation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds <podcast:location> tags to Podlove RSS feed entries (Podcasting 2.0 namespace).
 *
 * Emits separate tags for 'subject' and 'creator' rel types when data exists.
 */
class Feed_Extension
{
    public function __construct()
    {
        add_action('podlove_append_to_feed_entry', [$this, 'add_location_to_feed'], 10, 4);
    }

    /**
     * Output <podcast:location> tags for a feed entry.
     *
     * @param mixed $podcast
     * @param mixed $episode
     * @param mixed $feed
     * @param mixed $format
     */
    public function add_location_to_feed($podcast, $episode, $feed, $format)
    {
        foreach (['subject', 'creator'] as $rel) {
            $location = Location_Model::find_by_episode_id_and_rel($episode->id, $rel);

            if (!$location || (empty($location->location_lat) && empty($location->location_lng))) {
                continue;
            }

            $geo = sprintf('geo:%s,%s', $location->location_lat, $location->location_lng);
            $name = !empty($location->location_name) ? esc_html($location->location_name) : '';

            $attrs = sprintf('rel="%s" geo="%s"', esc_attr($rel), esc_attr($geo));

            if (!empty($location->location_osm)) {
                $attrs .= sprintf(' osm="%s"', esc_attr($location->location_osm));
            }

            if (!empty($location->location_country)) {
                $attrs .= sprintf(' country="%s"', esc_attr(strtoupper($location->location_country)));
            }

            if ($name) {
                echo sprintf("\n\t\t<podcast:location %s>%s</podcast:location>", $attrs, $name);
            } else {
                echo sprintf("\n\t\t<podcast:location %s />", $attrs);
            }
        }
    }
}
