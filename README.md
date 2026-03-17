# Podlove Episode Location

A standalone WordPress plugin that adds dual episode location support (subject & creator) to [Podlove Publisher](https://podlove.org/podlove-publisher/). Registers as a **Podlove module** that can be enabled/disabled from the Podlove "Modules" settings page.

## Features

- **Podlove Module Integration** — Appears in the Podlove Publisher "Modules" settings page and can be toggled on/off like any native module
- **Dual Location Support** — Two independent locations per episode:
  - **Subject Location** — Where the episode is about
  - **Creator Location** — Where the episode was recorded
- **Tabbed UI** — Clean tabbed interface in the episode editor to switch between Subject and Creator location editors
- **Interactive Maps** — OpenStreetMap + Leaflet.js map widget with separate maps per tab
- **Location Search** — Nominatim geocoding to search for places and addresses (no API key needed)
- **Draggable Markers** — Fine-tune locations by dragging map pins
- **Reverse Geocoding** — Auto-fills address, country code, and OSM identifier when placing a pin
- **OSM Data Capture** — Automatically captures `osm_type`/`osm_id` and country code from Nominatim
- **Podlove Template Tags** — Access both subject and creator location data in Podlove templates
- **RSS Feed Support** — Emits Podcasting 2.0 `<podcast:location>` tags with `rel`, `geo`, `osm`, and `country` attributes
## Requirements

- WordPress 5.0+
- PHP 7.4+
- [Podlove Publisher](https://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/) plugin (must be installed and active)

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/davekeeshan/podlove-episode-location/releases)
2. Upload the `podlove-episode-location` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress **Plugins** menu
4. The module is automatically enabled in Podlove's Modules settings
5. To disable, go to **Podlove > Modules** and uncheck "Episode Location"

Alternatively, clone directly into your plugins directory:

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/davekeeshan/podlove-episode-location.git
```

## Usage

### Setting Locations

1. Edit any podcast episode in WordPress
2. Scroll down to the **Episode Location** meta box
3. Use the **Subject Location** tab to set where the episode is about
4. Use the **Creator Location** tab to set where the episode was recorded
5. In each tab: search for a location, click the map, or drag the marker
6. Country code and OSM ID are auto-filled from search results
7. Save the episode

You can set one location, both, or neither — only locations with data will appear in the feed.

### Template Tags

**Subject location:**

```twig
{{ episode.locationSubjectName }}
{{ episode.locationSubjectLat }}
{{ episode.locationSubjectLng }}
{{ episode.locationSubjectAddress }}
```

**Creator location:**

```twig
{{ episode.locationCreatorName }}
{{ episode.locationCreatorLat }}
{{ episode.locationCreatorLng }}
{{ episode.locationCreatorAddress }}
```

**Example** — display links for both locations:

```twig
{% if episode.locationSubjectLat %}
  <p>About:
    <a href="https://www.openstreetmap.org/?mlat={{ episode.locationSubjectLat }}&mlon={{ episode.locationSubjectLng }}#map=15/{{ episode.locationSubjectLat }}/{{ episode.locationSubjectLng }}">
      {{ episode.locationSubjectName }}
    </a>
  </p>
{% endif %}

{% if episode.locationCreatorLat %}
  <p>Recorded in:
    <a href="https://www.openstreetmap.org/?mlat={{ episode.locationCreatorLat }}&mlon={{ episode.locationCreatorLng }}#map=15/{{ episode.locationCreatorLat }}/{{ episode.locationCreatorLng }}">
      {{ episode.locationCreatorName }}
    </a>
  </p>
{% endif %}
```

### RSS Feed

The plugin automatically adds Podcasting 2.0 `<podcast:location>` tags to each episode's feed entry. Separate tags are emitted for each location type that has data:

```xml
<podcast:location rel="subject" geo="geo:51.50740000,-0.12780000" osm="R65606" country="GB">London</podcast:location>
<podcast:location rel="creator" geo="geo:53.34980000,-6.26030000" osm="R1109531" country="IE">Dublin</podcast:location>
```

Attributes include:
- `rel` — "subject" or "creator" per the Podcasting 2.0 spec
- `geo` — Geo URI with latitude and longitude
- `osm` — OpenStreetMap identifier (e.g. "R65606" for a relation)
- `country` — ISO 3166-1 alpha-2 country code

## Module Registration

This plugin registers itself on the Podlove Publisher "Modules" settings page under the **Metadata** group. It can be enabled or disabled from there like any native Podlove module. When disabled, the meta box, template tags, and feed output are all deactivated, but the plugin remains installed and your location data is preserved.

## Database

The plugin uses a single table `{wp_prefix}podlove_episode_location` with a unique constraint on `(episode_id, rel)` to store one subject and one creator location per episode.

### Schema

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED (PK) | Auto-increment primary key |
| `episode_id` | BIGINT UNSIGNED | References Podlove episode ID |
| `rel` | VARCHAR(20) | Relationship type: 'subject' or 'creator' |
| `location_name` | VARCHAR(255) | Display name for the location |
| `location_lat` | DECIMAL(10,8) | Latitude |
| `location_lng` | DECIMAL(11,8) | Longitude |
| `location_address` | TEXT | Full address string |
| `location_country` | VARCHAR(2) | ISO 3166-1 alpha-2 country code |
| `location_osm` | VARCHAR(50) | OSM identifier (e.g. "R113314") |

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

- [Leaflet.js](https://leafletjs.com/) — Interactive map library
- [OpenStreetMap](https://www.openstreetmap.org/) — Map tiles
- [Nominatim](https://nominatim.org/) — Geocoding service
- [Podlove Publisher](https://podlove.org/podlove-publisher/) — Podcast publishing platform for WordPress
