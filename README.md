# Podlove Episode Location

A standalone WordPress plugin that adds episode location and map functionality to [Podlove Publisher](https://podlove.org/podlove-publisher/). Attach geographic locations to your podcast episodes with an interactive OpenStreetMap-powered map.

## Features

- **Interactive Map** — OpenStreetMap + Leaflet.js map widget in the episode editor
- **Location Search** — Nominatim geocoding to search for places and addresses
- **Draggable Marker** — Fine-tune location by dragging the map pin
- **Reverse Geocoding** — Auto-fills address when placing a pin on the map
- **Podlove Template Tags** — Access location data in Podlove templates
- **RSS Feed Support** — Adds Podcasting 2.0 `<podcast:location>` tags to your feed
- **No API Keys Required** — Uses free OpenStreetMap and Nominatim services

## Requirements

- WordPress 5.0+
- PHP 7.4+
- [Podlove Publisher](https://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/) plugin (must be installed and active)

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/davekeeshan/podlove-episode-location/releases)
2. Upload the `podlove-episode-location` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress **Plugins** menu
4. The "Episode Location" meta box will appear on the episode edit screen

Alternatively, clone directly into your plugins directory:

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/davekeeshan/podlove-episode-location.git
```

## Usage

### Setting a Location

1. Edit any podcast episode in WordPress
2. Scroll down to the **Episode Location** meta box
3. Search for a location by name or address, or click directly on the map
4. Adjust the marker by dragging it
5. Optionally edit the location name and address fields
6. Save the episode

### Template Tags

Use these in your Podlove templates:

```twig
{{ episode.locationName }}
{{ episode.locationLat }}
{{ episode.locationLng }}
{{ episode.locationAddress }}
```

Example — display a link to the location on OpenStreetMap:

```twig
{% if episode.locationLat %}
  <a href="https://www.openstreetmap.org/?mlat={{ episode.locationLat }}&mlon={{ episode.locationLng }}#map=15/{{ episode.locationLat }}/{{ episode.locationLng }}">
    {{ episode.locationName }}
  </a>
{% endif %}
```

### RSS Feed

The plugin automatically adds a Podcasting 2.0 `<podcast:location>` tag to each episode's feed entry when a location is set:

```xml
<podcast:location geo="geo:52.52000000,13.40500000">Berlin</podcast:location>
```

## Database Compatibility

The plugin uses Podlove's table naming convention (`{wp_prefix}podlove_episode_location`) and references Podlove episode IDs. This ensures that if the feature is later merged into Podlove Publisher as a native module, no data migration is needed.

### Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK) | Auto-increment primary key |
| `episode_id` | INT (indexed) | References Podlove episode ID |
| `location_name` | VARCHAR(255) | Display name for the location |
| `location_lat` | DECIMAL(10,8) | Latitude |
| `location_lng` | DECIMAL(11,8) | Longitude |
| `location_address` | TEXT | Full address string |

## Screenshots

<!-- Screenshots can be added here -->

1. **Episode Location meta box** — Interactive map with search in the episode editor
2. **Search results** — Nominatim-powered location search
3. **Feed output** — Podcasting 2.0 location tag in RSS

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

- [Leaflet.js](https://leafletjs.com/) — Interactive map library
- [OpenStreetMap](https://www.openstreetmap.org/) — Map tiles
- [Nominatim](https://nominatim.org/) — Geocoding service
- [Podlove Publisher](https://podlove.org/podlove-publisher/) — Podcast publishing platform for WordPress
