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
 */
class Location_Model
{
    public $id;
    public $episode_id;
    public $location_name;
    public $location_lat;
    public $location_lng;
    public $location_address;

    /**
     * Get the table name using Podlove's naming convention.
     */
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'podlove_episode_location';
    }

    /**
     * Create the database table if it doesn't exist.
     */
    public static function build()
    {
        global $wpdb;

        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            episode_id INT,
            location_name VARCHAR(255),
            location_lat DECIMAL(10,8),
            location_lng DECIMAL(11,8),
            location_address TEXT,
            INDEX idx_episode_id (episode_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Find a location record by episode ID.
     *
     * @param int $episode_id
     * @return Location_Model|null
     */
    public static function find_by_episode_id($episode_id)
    {
        global $wpdb;

        $table = self::table_name();
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE episode_id = %d LIMIT 1", $episode_id)
        );

        if (!$row) {
            return null;
        }

        return self::from_row($row);
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
            'location_name'    => $this->location_name,
            'location_lat'     => $this->location_lat,
            'location_lng'     => $this->location_lng,
            'location_address' => $this->location_address,
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s'];

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
        $model->location_name    = $row->location_name;
        $model->location_lat     = $row->location_lat;
        $model->location_lng     = $row->location_lng;
        $model->location_address = $row->location_address;
        return $model;
    }
}
