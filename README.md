# Podlove Episode Location

A standalone WordPress plugin that adds dual episode location support (subject & creator) to [Podlove Publisher](https://podlove.org/podlove-publisher/). Registers as a **Podlove module** that can be enabled/disabled from the Podlove "Modules" settings page.

## Features

- **Podlove Module Integration** — Appears in the Podlove Publisher "Modules" settings page and can be toggled on/off like any native module
- **Podcast Default Creator Location** — Set a default creator location in **Podlove > Podcast Settings > Location**. Emitted at the RSS channel level; used as a fallback in Podlove templates when an episode has no explicit creator location
- **Dual Location Support** — Two independent locations per episode:
  - **Subject Location** — Where the episode is about
  - **Creator Location** — Where the episode was recorded
- **Tabbed UI** — Clean tabbed interface in the episode editor to switch between Subject and Creator location editors
- **Interactive Maps** — OpenStreetMap + Leaflet.js map widget with separate maps per tab (episode editor and podcast settings)
- **Location Search** — Nominatim geocoding to search for places and addresses (no API key needed)
- **Draggable Markers** — Fine-tune locations by dragging map pins
- **Reverse Geocoding** — Auto-fills the human-readable name, address, and country code when placing or moving a pin
- **OSM Data Capture** — Captures `osm_type`/`osm_id` from explicit search results
- **Clear Location** — Button to reset/disable a location (clear all fields including coordinates)
- **Podlove Template Tags** — Access both subject and creator location data; creator tags fall back to the podcast default when the episode has none
- **RSS Feed Support** — Emits Podcasting 2.0 `<podcast:location>` tags at channel and item level with `rel`, `geo`, `osm`, and `country` attributes
- **Feed Cache Flush** — Location changes automatically flush Podlove's feed cache so updates appear in the RSS feed immediately

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

### Podcast Default Creator Location

1. Go to **Podlove > Podcast Settings**
2. Open the **Location** tab
3. Search for a location, click the map, or drag the marker to set your default creator location
4. Click **Save Changes**

This location is emitted at the channel level in your RSS feed and is used as a fallback in Podlove templates when an episode has no explicit creator location. Leave it empty if you prefer not to set a default.

If you choose an explicit search result, the OSM ID and country code are captured from that result. If you manually move the pin, the visible location fields update from reverse geocoding, but the OSM ID is cleared until a specific search result is chosen again.

### Setting Episode Locations

1. Edit any podcast episode in WordPress
2. Scroll down to the **Episode Location** meta box
3. Use the **Subject Location** tab to set where the episode is about
4. Use the **Creator Location** tab to set where the episode was recorded
5. In each tab: search for a location, click the map, or drag the marker
6. Country code and OSM ID are auto-filled from search results
7. Use **Clear Location** to remove a location entirely
8. Save the episode

You can set one location, both, or neither — only locations with data will appear in the feed. You can also clear the location name or any field to empty; if all fields are empty, the location is removed on save.

If you manually click or drag the pin, the human-readable name, address, country, and coordinates update from reverse geocoding. The OSM ID is only preserved when you choose an explicit search result.

### Template Tags

**Subject location** (episode data only):

```twig
{{ episode.locationSubjectName }}
{{ episode.locationSubjectLat }}
{{ episode.locationSubjectLng }}
{{ episode.locationSubjectAddress }}
```

**Creator location** (falls back to podcast default when episode has none):

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

**Channel level:** When a podcast default creator location is set, a single `<podcast:location rel="creator">` tag is emitted inside the `<channel>` element.

**Item level:** Episode entries receive `<podcast:location>` tags only when they have explicit subject or creator locations set. The podcast default is not repeated in each item.

The plugin supports both fully populated locations and text-only locations. When available, `geo`, `osm`, and `country` attributes are emitted; when they are not available, the human-readable node value can still be emitted on its own.

```xml
<!-- Channel level -->
<channel>
  ...
  <podcast:location rel="creator" geo="geo:53.34965,-6.43881" osm="W450518064" country="IE">Lucan-Newlands Road</podcast:location>
  ...
  <item>
    ...
    <podcast:location rel="subject" geo="geo:40.49234,-74.44571" osm="W277742502" country="US">South Slave Region</podcast:location>
    <podcast:location rel="creator" geo="geo:7.69991,23.41053" osm="R2634510" country="CF">Haute-Kotto</podcast:location>
  </item>
</channel>
```

Attributes:
- `rel` — "subject" or "creator" per the Podcasting 2.0 spec
- `geo` — Geo URI with latitude and longitude, when coordinates are available
- `osm` — OpenStreetMap identifier (e.g. "R65606" for a relation), when a specific search result was chosen
- `country` — ISO 3166-1 alpha-2 country code, when available

## Module Registration

This plugin registers itself on the Podlove Publisher "Modules" settings page under the **Metadata** group. It can be enabled or disabled from there like any native Podlove module. When disabled, the meta box, podcast settings Location tab, template tags, and feed output are all deactivated, but the plugin remains installed and your location data is preserved.

## Storage

- **Episode locations:** Table `{wp_prefix}podlove_episode_location` with a unique constraint on `(episode_id, rel)` — one subject and one creator location per episode.
- **Podcast default creator location:** WordPress option `podlove_episode_location_podcast` (serialized array).
- **Display name limit:** The human-readable location name is limited to 128 characters to align with the Podcasting 2.0 location guidance.

### Episode Table Schema

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
