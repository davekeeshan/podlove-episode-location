<?php

namespace Podlove\Modules\EpisodeLocation\Model;

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
class Location
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

        return $wpdb->prefix.'podlove_episode_location';
    }

    /**
     * Create the database table if it doesn't exist.
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

        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Basic robustness: log if table creation failed.
        $existing_table = $wpdb->get_var($wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $table
        ));

        if ($existing_table !== $table && defined('WP_DEBUG') && WP_DEBUG) {
            error_log(
                sprintf(
                    '[Podlove Episode Location] Failed to create table %s. DB error: %s',
                    $table,
                    $wpdb->last_error
                )
            );
        }
    }

    /**
     * Find a location record by episode ID and rel type.
     *
     * @param int    $episode_id
     * @param string $rel        'subject' or 'creator'
     *
     * @return null|Location
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
     *
     * @return Location[]
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
     * All location rows (for Publisher export).
     *
     * @return Location[]
     */
    public static function all()
    {
        global $wpdb;

        $table = self::table_name();
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC");

        if (!$rows) {
            return [];
        }

        $locations = [];
        foreach ($rows as $row) {
            $locations[] = self::from_row($row);
        }

        return $locations;
    }

    /**
     * Remove all location rows (used before Publisher import).
     */
    public static function delete_all()
    {
        global $wpdb;

        $wpdb->query('DELETE FROM '.self::table_name());
    }

    /**
     * Save the current record (insert or update).
     */
    public function save()
    {
        global $wpdb;

        $table = self::table_name();
        $data = [
            'episode_id' => (int) $this->episode_id,
            'rel' => $this->rel ?: 'subject',
            'location_name' => $this->location_name,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'location_address' => $this->location_address,
            'location_country' => $this->location_country,
            'location_osm' => $this->location_osm,
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if ($this->id) {
            $wpdb->update($table, $data, ['id' => $this->id], $formats, ['%d']);
        } else {
            $wpdb->insert($table, $data, $formats);
            $this->id = $wpdb->insert_id;
        }

        if (!empty($wpdb->last_error) && defined('WP_DEBUG') && WP_DEBUG) {
            error_log(
                sprintf(
                    '[Podlove Episode Location] Failed to save location record (episode_id=%d, rel=%s). DB error: %s',
                    $this->episode_id,
                    $this->rel,
                    $wpdb->last_error
                )
            );
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
     *
     * @return Location
     */
    private static function from_row($row)
    {
        $model = new self();
        $model->id = (int) $row->id;
        $model->episode_id = (int) $row->episode_id;
        $model->rel = $row->rel;
        $model->location_name = $row->location_name;
        $model->location_lat = $row->location_lat;
        $model->location_lng = $row->location_lng;
        $model->location_address = $row->location_address;
        $model->location_country = $row->location_country;
        $model->location_osm = $row->location_osm;

        return $model;
    }
}
