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

Podlove Episode Location extends Podlove Publisher with geographic location support for podcast episodes. It registers as a proper Podlove module visible in the Modules settings page.

Features:

* **Podlove Module** — Appears in Podlove's Modules page and can be toggled on/off
* **Dual Locations** — Subject (what the episode is about) and Creator (where it was recorded)
* **Tabbed UI** — Clean tabbed interface in the episode editor
* **Interactive Maps** — OpenStreetMap + Leaflet.js with separate maps per location type
* **Nominatim Search** — Geocoding search with no API key required
* **OSM Data Capture** — Auto-captures OSM identifier and country code from search results
* **Draggable Markers** — Fine-tune locations by dragging pins
* **Template Tags** — Access both location types in Podlove templates
* **Podcasting 2.0 Feed** — Emits `<podcast:location>` tags with rel, geo, osm, and country attributes

**Requires Podlove Publisher** to be installed and active.

== Installation ==

1. Ensure Podlove Publisher is installed and active
2. Upload `podlove-episode-location` to `/wp-content/plugins/`
3. Activate the plugin through the Plugins menu
4. The module is auto-enabled in Podlove > Modules
5. Edit any episode to see the Episode Location meta box with Subject and Creator tabs

== Frequently Asked Questions ==

= Does this require an API key? =

No. The plugin uses OpenStreetMap tiles and Nominatim geocoding, which are free and open services.

= Does this modify Podlove Publisher? =

No. This is a standalone plugin that hooks into Podlove Publisher's extension points without modifying it.

= What happens to my data if I disable the module? =

Your location data is preserved in the database. Re-enabling the module will restore access to it.

= Can I set only one location type? =

Yes. You can set just a subject location, just a creator location, both, or neither. Only locations with data will appear in the feed.

== Changelog ==

= 1.0.0 =
* Registers as a Podlove module on the Modules settings page
* Dual location support: Subject and Creator
* Tabbed UI in the episode editor meta box
* OSM identifier and country code capture from Nominatim
* Podcasting 2.0 feed tags with rel, osm, and country attributes
* New template tags: locationSubjectName, locationCreatorName, etc.
