<?php

namespace PodloveEpisodeLocation;

use Podlove\Template\Episode;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers Podlove template accessors for episode location data.
 *
 * Subject location accessors:
 *   {{ episode.locationSubjectName }}
 *   {{ episode.locationSubjectLat }}
 *   {{ episode.locationSubjectLng }}
 *   {{ episode.locationSubjectAddress }}
 *
 * Creator location accessors:
 *   {{ episode.locationCreatorName }}
 *   {{ episode.locationCreatorLat }}
 *   {{ episode.locationCreatorLng }}
 *   {{ episode.locationCreatorAddress }}
 */
class Template_Extensions
{
    public function __construct()
    {
        if (!class_exists('\Podlove\Template\Episode')) {
            return;
        }

        // Subject location accessors
        Episode::add_accessor(
            'locationSubjectName',
            [__CLASS__, 'accessor_subject_name'],
            4
        );
        Episode::add_accessor(
            'locationSubjectLat',
            [__CLASS__, 'accessor_subject_lat'],
            4
        );
        Episode::add_accessor(
            'locationSubjectLng',
            [__CLASS__, 'accessor_subject_lng'],
            4
        );
        Episode::add_accessor(
            'locationSubjectAddress',
            [__CLASS__, 'accessor_subject_address'],
            4
        );

        // Creator location accessors
        Episode::add_accessor(
            'locationCreatorName',
            [__CLASS__, 'accessor_creator_name'],
            4
        );
        Episode::add_accessor(
            'locationCreatorLat',
            [__CLASS__, 'accessor_creator_lat'],
            4
        );
        Episode::add_accessor(
            'locationCreatorLng',
            [__CLASS__, 'accessor_creator_lng'],
            4
        );
        Episode::add_accessor(
            'locationCreatorAddress',
            [__CLASS__, 'accessor_creator_address'],
            4
        );
    }

    // --- Subject accessors ---

    public static function accessor_subject_name($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_name');
    }

    public static function accessor_subject_lat($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_lat');
    }

    public static function accessor_subject_lng($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_lng');
    }

    public static function accessor_subject_address($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'subject', 'location_address');
    }

    // --- Creator accessors ---

    public static function accessor_creator_name($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_name');
    }

    public static function accessor_creator_lat($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_lat');
    }

    public static function accessor_creator_lng($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_lng');
    }

    public static function accessor_creator_address($return, $method_name, $episode, $post, $args = [])
    {
        return self::get_field($episode->id, 'creator', 'location_address');
    }

    /**
     * Retrieve a single field from a location record.
     *
     * @param int    $episode_id
     * @param string $rel
     * @param string $field
     *
     * @return string
     */
    private static function get_field($episode_id, $rel, $field)
    {
        $location = Location_Model::find_by_episode_id_and_rel($episode_id, $rel);

        return $location && isset($location->{$field}) ? $location->{$field} : '';
    }
}
