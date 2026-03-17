<?php

namespace PodloveEpisodeLocation;

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
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
