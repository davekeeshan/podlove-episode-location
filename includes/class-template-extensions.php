<?php

namespace PodloveEpisodeLocation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers Podlove template accessors for episode location data.
 *
 * Provides:
 *   {{ episode.locationName }}
 *   {{ episode.locationLat }}
 *   {{ episode.locationLng }}
 *   {{ episode.locationAddress }}
 */
class Template_Extensions
{
    public function __construct()
    {
        // Register template accessors if the Podlove template class exists
        if (class_exists('\Podlove\Template\Episode')) {
            \Podlove\Template\Episode::add_accessor(
                'locationName',
                [__CLASS__, 'accessor_location_name'],
                4
            );

            \Podlove\Template\Episode::add_accessor(
                'locationLat',
                [__CLASS__, 'accessor_location_lat'],
                4
            );

            \Podlove\Template\Episode::add_accessor(
                'locationLng',
                [__CLASS__, 'accessor_location_lng'],
                4
            );

            \Podlove\Template\Episode::add_accessor(
                'locationAddress',
                [__CLASS__, 'accessor_location_address'],
                4
            );
        }
    }

    /**
     * @accessor
     * @dynamicAccessor episode.locationName
     */
    public static function accessor_location_name($return, $method_name, $episode, $post, $args = [])
    {
        $location = Location_Model::find_by_episode_id($episode->id);
        return $location ? $location->location_name : '';
    }

    /**
     * @accessor
     * @dynamicAccessor episode.locationLat
     */
    public static function accessor_location_lat($return, $method_name, $episode, $post, $args = [])
    {
        $location = Location_Model::find_by_episode_id($episode->id);
        return $location ? $location->location_lat : '';
    }

    /**
     * @accessor
     * @dynamicAccessor episode.locationLng
     */
    public static function accessor_location_lng($return, $method_name, $episode, $post, $args = [])
    {
        $location = Location_Model::find_by_episode_id($episode->id);
        return $location ? $location->location_lng : '';
    }

    /**
     * @accessor
     * @dynamicAccessor episode.locationAddress
     */
    public static function accessor_location_address($return, $method_name, $episode, $post, $args = [])
    {
        $location = Location_Model::find_by_episode_id($episode->id);
        return $location ? $location->location_address : '';
    }
}
