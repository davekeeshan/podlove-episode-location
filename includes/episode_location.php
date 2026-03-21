<?php

namespace Podlove\Modules\EpisodeLocation;

use Podlove\Settings\Expert\Tabs;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core plugin class — singleton that wires up all components.
 */
class Episode_Location
{
    private static $instance;

    private function __construct()
    {
        new Meta_Box();
        new Template_Extensions();
        new Feed_Extension();
        new Export_Import();

        Podcast_Settings::register_early_hooks();
        add_filter('podlove_podcast_settings_tabs', [$this, 'register_podcast_settings_tab']);
    }

    /**
     * Register the Location tab on the Podlove Podcast Settings page.
     *
     * @param Tabs $tabs
     *
     * @return Tabs
     */
    public function register_podcast_settings_tab($tabs)
    {
        $tabs->addTab(new Podcast_Settings(
            'location',
            __('Location', 'podlove-episode-location'),
            false
        ));

        return $tabs;
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
