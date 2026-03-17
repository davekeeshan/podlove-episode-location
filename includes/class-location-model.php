<?php

namespace PodloveEpisodeLocation;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database model for episode locations.
 *
 * Uses $wpdb directly to stay independent of Podlove Publisher internals,
 * while keeping the table name and schema fully compatible with Podlove's
 * naming convention so this can be migrated into a core module later.
 *
 * Table: {wp_prefix}podlove_episode_location
 *
 * Supports two relationship types per episode:
 *   - 'subject' — where the episode is about
 *   - 'creator' — where the episode was recorded
 */
class Location_Model
{
    public $id;
    public $episode_id;
    public $rel;
    public $location_name;
    public $location_lat;
    public $location_lng;
    public $location_address;
    public $location_country;
    public $location_osm;

    /**
     * Get the table name using Podlove's naming convention.
     */
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'podlove_episode_location';
    }

    /**
     * Create the database table if it doesn't exist, or migrate columns.
     */
    public static function build()
    {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            episode_id BIGINT UNSIGNED NOT NULL,
            rel VARCHAR(20) NOT NULL DEFAULT 'subject',
            location_name VARCHAR(255),
            location_lat DECIMAL(10,8),
            location_lng DECIMAL(11,8),
            location_address TEXT,
            location_country VARCHAR(2),
            location_osm VARCHAR(50),
            UNIQUE KEY episode_rel (episode_id, rel)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Migrate legacy rows that lack a rel value
        self::migrate_legacy_data();
    }

    /**
     * Migrate data from the old single-location schema.
     *
     * Old schema had no `rel` column. If we just added it with DEFAULT 'subject',
     * existing rows get the default. But we also need to handle the case where
     * the old table had a simple `episode_id` index instead of the unique key.
     */
    private static function migrate_legacy_data()
    {
        global $wpdb;

        $table = self::table_name();

        // Check if old idx_episode_id index exists and drop it
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = 'idx_episode_id'");
        if (!empty($indexes)) {
            $wpdb->query("ALTER TABLE {$table} DROP INDEX idx_episode_id");
        }
    }

    /**
     * Find a location record by episode ID and rel type.
     *
     * @param int    $episode_id
     * @param string $rel 'subject' or 'creator'
     * @return Location_Model|null
     */
    public static function find_by_episode_id_and_rel($episode_id, $rel = 'subject')
    {
        global $wpdb;

        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE episode_id = %d AND rel = %s LIMIT 1",
                $episode_id,
                $rel
            )
        );

        if (!$row) {
            return null;
        }

        return self::from_row($row);
    }

    /**
     * Find all location records for an episode.
     *
     * @param int $episode_id
     * @return Location_Model[]
     */
    public static function find_all_by_episode_id($episode_id)
    {
        global $wpdb;

        $table = self::table_name();
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE episode_id = %d", $episode_id)
        );

        $locations = [];
        foreach ($rows as $row) {
            $locations[] = self::from_row($row);
        }

        return $locations;
    }

    /**
     * Backwards-compatible: find by episode ID (returns subject location).
     *
     * @param int $episode_id
     * @return Location_Model|null
     */
    public static function find_by_episode_id($episode_id)
    {
        return self::find_by_episode_id_and_rel($episode_id, 'subject');
    }

    /**
     * Save the current record (insert or update).
     */
    public function save()
    {
        global $wpdb;

        $table = self::table_name();
        $data = [
            'episode_id'       => $this->episode_id,
            'rel'              => $this->rel ?: 'subject',
            'location_name'    => $this->location_name,
            'location_lat'     => $this->location_lat,
            'location_lng'     => $this->location_lng,
            'location_address' => $this->location_address,
            'location_country' => $this->location_country,
            'location_osm'     => $this->location_osm,
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if ($this->id) {
            $wpdb->update($table, $data, ['id' => $this->id], $formats, ['%d']);
        } else {
            $wpdb->insert($table, $data, $formats);
            $this->id = $wpdb->insert_id;
        }
    }

    /**
     * Delete the current record.
     */
    public function delete()
    {
        global $wpdb;

        if ($this->id) {
            $wpdb->delete(self::table_name(), ['id' => $this->id], ['%d']);
        }
    }

    /**
     * Create a model instance from a database row.
     *
     * @param object $row
     * @return Location_Model
     */
    private static function from_row($row)
    {
        $model = new self();
        $model->id               = (int) $row->id;
        $model->episode_id       = (int) $row->episode_id;
        $model->rel              = isset($row->rel) ? $row->rel : 'subject';
        $model->location_name    = $row->location_name;
        $model->location_lat     = $row->location_lat;
        $model->location_lng     = $row->location_lng;
        $model->location_address = $row->location_address;
        $model->location_country = isset($row->location_country) ? $row->location_country : '';
        $model->location_osm     = isset($row->location_osm) ? $row->location_osm : '';
        return $model;
    }
}
