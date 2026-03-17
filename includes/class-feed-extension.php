<?php

namespace PodloveEpisodeLocation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds <podcast:location> tags to Podlove RSS feeds (Podcasting 2.0 namespace).
 *
 * - Channel level: emits the podcast-level default creator location.
 * - Item level: emits subject and creator locations per episode, falling back
 *   to the podcast default for creator when an episode has none.
 */
class Feed_Extension
{
    public function __construct()
    {
        add_action('rss2_ns', [$this, 'add_podcasting_namespace']);
        add_action('podlove_append_to_feed_head', [$this, 'add_location_to_feed_head'], 10, 3);
        add_action('podlove_append_to_feed_entry', [$this, 'add_location_to_feed_entry'], 10, 4);
    }

    /**
     * Add the Podcasting 2.0 namespace declaration to RSS feeds.
     */
    public function add_podcasting_namespace()
    {
        echo ' xmlns:podcast="https://podcastindex.org/namespace/1.0"';
    }

    /**
     * Output a channel-level <podcast:location> tag for the podcast default
     * creator location (if set).
     *
     * @param mixed $podcast
     * @param mixed $feed
     * @param mixed $format
     */
    public function add_location_to_feed_head($podcast, $feed, $format)
    {
        if (!Podcast_Settings::has_podcast_location()) {
            return;
        }

        $data = Podcast_Settings::get_podcast_location();
        self::emit_location_tag($data, 'creator', "\n\t");
    }

    /**
     * Output episode-level <podcast:location> tags for a feed entry.
     *
     * For 'subject', the episode location is used as-is.
     * For 'creator', the episode location is used if set; otherwise the
     * podcast-level default creator location is used as a fallback.
     *
     * @param mixed $podcast
     * @param mixed $episode
     * @param mixed $feed
     * @param mixed $format
     */
    public function add_location_to_feed_entry($podcast, $episode, $feed, $format)
    {
        // Subject location — episode only, no fallback.
        $subject = Location_Model::find_by_episode_id_and_rel($episode->id, 'subject');
        if ($subject && (!empty($subject->location_lat) || !empty($subject->location_lng))) {
            self::emit_location_tag([
                'location_name' => $subject->location_name,
                'location_lat' => $subject->location_lat,
                'location_lng' => $subject->location_lng,
                'location_osm' => $subject->location_osm,
                'location_country' => $subject->location_country,
            ], 'subject', "\n\t\t");
        }

        // Creator location — episode only; the podcast default is emitted
        // at the channel level and does not repeat in each item.
        $creator = Location_Model::find_by_episode_id_and_rel($episode->id, 'creator');
        if ($creator && (!empty($creator->location_lat) || !empty($creator->location_lng))) {
            self::emit_location_tag([
                'location_name' => $creator->location_name,
                'location_lat' => $creator->location_lat,
                'location_lng' => $creator->location_lng,
                'location_osm' => $creator->location_osm,
                'location_country' => $creator->location_country,
            ], 'creator', "\n\t\t");
        }
    }

    /**
     * Emit a single <podcast:location> XML element.
     *
     * @param array  $data   location data with location_name, location_lat, etc
     * @param string $rel    'subject' or 'creator'
     * @param string $indent Whitespace prefix for formatting
     */
    private static function emit_location_tag($data, $rel, $indent)
    {
        if (empty($data['location_lat']) && empty($data['location_lng'])) {
            return;
        }

        $geo = sprintf('geo:%s,%s', $data['location_lat'], $data['location_lng']);
        $name = !empty($data['location_name']) ? esc_html($data['location_name']) : '';

        $attrs = sprintf('rel="%s" geo="%s"', esc_attr($rel), esc_attr($geo));

        if (!empty($data['location_osm'])) {
            $attrs .= sprintf(' osm="%s"', esc_attr($data['location_osm']));
        }

        if (!empty($data['location_country'])) {
            $attrs .= sprintf(' country="%s"', esc_attr(strtoupper($data['location_country'])));
        }

        if ($name) {
            echo sprintf('%s<podcast:location %s>%s</podcast:location>', $indent, $attrs, $name);
        } else {
            echo sprintf('%s<podcast:location %s />', $indent, $attrs);
        }
    }
}
