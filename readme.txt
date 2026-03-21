=== Podlove Episode Location ===
Contributors: davekeeshan
Tags: podcast, podlove, location, map, openstreetmap
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Adds dual episode location (subject & creator) with interactive maps to Podlove Publisher. Registers as a Podlove module.

== Description ==

Podlove Episode Location extends Podlove Publisher with geographic location support for podcast episodes and podcast-level creator metadata. It registers as a proper Podlove module visible in the Modules settings page.

Features:

* **Podlove Module** — Appears in Podlove's Modules page and can be toggled on/off
* **Podcast Default Creator Location** — Set a default creator location in Podlove > Podcast Settings > Location; emitted at the RSS channel level
* **Dual Locations** — Subject (what the episode is about) and Creator (where it was recorded)
* **Tabbed UI** — Clean tabbed interface in the episode editor
* **Interactive Maps** — OpenStreetMap + Leaflet.js with separate maps per location type and podcast settings
* **Nominatim Search** — Geocoding search with no API key required
* **Reverse Geocoding** — Updates the human-readable name, address, and country when the pin moves
* **OSM Data Capture** — Captures OSM identifier and country code from explicit search results
* **Draggable Markers** — Fine-tune locations by dragging pins
* **Clear Location** — Reset a location entirely from the episode editor or podcast settings
* **Template Tags** — Access both location types in Podlove templates; creator tags fall back to the podcast default when an episode has none
* **Podcasting 2.0 Feed** — Emits `<podcast:location>` tags at channel and item level with rel, geo, osm, and country attributes when available
* **Publisher Import/Export** — When the Podlove Import/Export module is active, episode location rows are included in Publisher exports and restored on import (podcast-level option is already covered by Publisher’s options export)

**Requires Podlove Publisher** to be installed and active.

== Installation ==

1. Ensure Podlove Publisher is installed and active
2. Upload `podlove-episode-location` to `/wp-content/plugins/`
3. Activate the plugin through the Plugins menu
4. The module is auto-enabled in Podlove > Modules
5. Edit any episode to see the Episode Location meta box with Subject and Creator tabs
6. Optionally set a podcast-wide default creator location in Podlove > Podcast Settings > Location

== Frequently Asked Questions ==

= Does this require an API key? =

No. The plugin uses OpenStreetMap tiles and Nominatim geocoding, which are free and open services.

= Does this modify Podlove Publisher? =

No. This is a standalone plugin that hooks into Podlove Publisher's extension points without modifying it.

= What happens to my data if I disable the module? =

Your location data is preserved in the database. Re-enabling the module will restore access to it.

= Can I set only one location type? =

Yes. You can set just a subject location, just a creator location, both, or neither. Only locations with data will appear in the feed.

= What is the podcast default creator location used for? =

It is emitted at the channel level in the RSS feed and is also used as a fallback in Podlove template tags when an episode has no explicit creator location.

= When is the OSM ID set? =

The OSM ID is captured when you choose an explicit search result. If you manually click or drag the pin, the visible location fields update from reverse geocoding, but the OSM ID is cleared until a specific search result is chosen again.

= Can I clear or disable a location after setting it? =

Yes. Use the Clear Location button in the episode editor or podcast settings to remove the current location. If all fields are empty when saved, the location is removed.

= Are episode locations included in Podlove’s backup export? =

Yes, if the Podlove **Import/Export** module is enabled. The plugin hooks `podlove_xml_export` to add an `episode_locations` section. The podcast default location option (`podlove_episode_location_podcast`) is included automatically with other `podlove_*` options in Publisher’s export.

== Changelog ==

= 1.0.1 =
* Podlove Publisher export/import: `episode_locations` / `episode_location` XML; import job restores the `podlove_episode_location` table after core import jobs

= 1.0.0 =
* Registers as a Podlove module on the Modules settings page
* Dual location support: Subject and Creator
* Tabbed UI in the episode editor meta box
* Podcast settings Location tab with default creator location
* Reverse geocoding for name, address, and country
* OSM identifier capture from explicit search results
* Clear Location support in episode editor and podcast settings
* Podcasting 2.0 feed tags at channel and item level with rel, geo, osm, and country attributes
* New template tags: locationSubjectName, locationCreatorName, etc.
