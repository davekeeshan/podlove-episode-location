<?php

namespace Podlove\Modules\EpisodeLocation;

use Podlove\Modules\EpisodeLocation\Model\Location;
use PodloveEpisodeLocation\PodcastImportEpisodeLocationsJob;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hooks Podlove Publisher Import/Export for episode location data.
 */
class Export_Import
{
    public function __construct()
    {
        if (!class_exists('\Podlove\Modules\ImportExport\Export\PodcastExporter')) {
            return;
        }

        add_action('podlove_xml_export', [$this, 'expand_export'], 10, 1);
        add_filter('podlove_import_jobs', [$this, 'expand_import'], 10, 1);
    }

    /**
     * Append episode_location rows to the Publisher export XML.
     *
     * @param \SimpleXMLElement $xml
     */
    public function expand_export($xml)
    {
        if (!$xml instanceof \SimpleXMLElement) {
            return;
        }

        $xml_group = $xml->addChild('xmlns:wpe:episode_locations');

        foreach (Location::all() as $location) {
            $xml_item = $xml_group->addChild('xmlns:wpe:episode_location');
            foreach (self::export_property_names() as $property_name) {
                $value = $location->{$property_name};
                if ($value === null || $value === '') {
                    continue;
                }
                $xml_item->addChild(
                    'xmlns:wpe:'.$property_name,
                    htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                );
            }
        }
    }

    /**
     * Register import job after core Publisher import jobs.
     *
     * @param string[] $jobs
     *
     * @return string[]
     */
    public function expand_import($jobs)
    {
        if (!is_array($jobs)) {
            $jobs = [];
        }

        $jobs[] = PodcastImportEpisodeLocationsJob::class;

        return $jobs;
    }

    /**
     * @return string[]
     */
    private static function export_property_names()
    {
        return [
            'id',
            'episode_id',
            'rel',
            'location_name',
            'location_lat',
            'location_lng',
            'location_address',
            'location_country',
            'location_osm',
        ];
    }
}
