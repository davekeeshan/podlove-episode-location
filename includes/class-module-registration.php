<?php

namespace PodloveEpisodeLocation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the Episode Location plugin as a module on the Podlove Publisher
 * "Modules" settings page.
 *
 * Since Podlove's module system discovers modules by scanning its own
 * lib/modules/ directory (with no filter for external plugins), this class
 * hooks into the WordPress Settings API at the point where Podlove renders
 * its module list. It adds our own entry using the same visual style and
 * stores the enabled/disabled state in the `podlove_active_modules` option,
 * exactly like native modules.
 *
 * Module key: `episode_location_external`
 *   (suffixed with _external to avoid conflicts if Podlove ever ships a
 *    built-in episode_location module)
 */
class Module_Registration
{
    const MODULE_KEY = 'episode_location_external';
    const MODULE_NAME = 'Episode Location';
    const MODULE_DESCRIPTION = 'Add geographic locations (subject and creator) to podcast episodes with interactive maps, Nominatim search, and Podcasting 2.0 &lt;podcast:location&gt; feed tags.';
    const MODULE_GROUP = 'metadata';

    public function __construct()
    {
        // Hook into the admin_init where Podlove registers its module settings.
        // We add our field to the same settings section.
        add_action('admin_init', [$this, 'register_module_field'], 20);

        // Ensure our module key is preserved when Podlove saves the modules form.
        // Podlove's form submits all module checkboxes under podlove_active_modules[].
        // Our checkbox is already included in that array, so it will be saved
        // automatically by WordPress's options.php handler.
    }

    /**
     * Check if the module is active.
     *
     * @return bool
     */
    public static function is_active()
    {
        $options = get_option('podlove_active_modules');

        if (!is_array($options)) {
            return true; // Default to active when option doesn't exist
        }

        return isset($options[self::MODULE_KEY]);
    }

    /**
     * Activate the module.
     */
    public static function activate()
    {
        $options = get_option('podlove_active_modules');
        if (!is_array($options)) {
            $options = [];
        }

        if (!isset($options[self::MODULE_KEY])) {
            $options[self::MODULE_KEY] = 'on';
            update_option('podlove_active_modules', $options);
        }
    }

    /**
     * Register our module entry on the Podlove Modules settings page.
     */
    public function register_module_field()
    {
        // Only run on the modules settings page
        if (!$this->is_modules_page()) {
            return;
        }

        // Ensure the metadata group section exists
        $pagehook = 'podlove_settings_modules_handle';

        add_settings_section(
            'podlove_setting_module_group_' . self::MODULE_GROUP,
            ucwords(self::MODULE_GROUP),
            function () {},
            $pagehook
        );

        add_settings_field(
            'podlove_setting_module_' . self::MODULE_KEY,
            // Title: checkbox + label, matching Podlove's format
            '<input name="podlove_active_modules[' . self::MODULE_KEY . ']" '
                . 'id="' . self::MODULE_KEY . '" type="checkbox" '
                . checked(self::is_active(), true, false) . '>'
                . sprintf(
                    '<label for="%s">%s</label><a name="%s"></a>',
                    self::MODULE_KEY,
                    self::MODULE_NAME,
                    self::MODULE_KEY
                ),
            // Callback: description
            function () {
                ?>
                <label for="<?php echo esc_attr(self::MODULE_KEY); ?>">
                    <?php echo self::MODULE_DESCRIPTION; ?>
                </label>
                <p class="description" style="margin-top: 8px; font-style: italic;">
                    <?php esc_html_e('Provided by the Podlove Episode Location plugin.', 'podlove-episode-location'); ?>
                </p>
                <?php
            },
            // Page
            $pagehook,
            // Section
            'podlove_setting_module_group_' . self::MODULE_GROUP
        );
    }

    /**
     * Check if we're on the Podlove Modules settings page.
     *
     * @return bool
     */
    private function is_modules_page()
    {
        if (function_exists('\\Podlove\\is_options_save_page') && \Podlove\is_options_save_page()) {
            return true;
        }

        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        return $page === 'podlove_settings_modules_handle';
    }
}
