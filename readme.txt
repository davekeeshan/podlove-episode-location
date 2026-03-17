=== Podlove Episode Location ===
Contributors: davekeeshan
Tags: podcast, podlove, location, map, openstreetmap
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Adds episode location and interactive map functionality to Podlove Publisher.

== Description ==

Podlove Episode Location is a standalone plugin that extends Podlove Publisher with geographic location support for podcast episodes.

Features:

* Interactive OpenStreetMap + Leaflet.js map in the episode editor
* Nominatim geocoding search (no API key needed)
* Draggable marker for precise positioning
* Reverse geocoding on pin drop
* Podlove template tags for location data
* Podcasting 2.0 `<podcast:location>` RSS feed tags

**Requires Podlove Publisher** to be installed and active.

== Installation ==

1. Ensure Podlove Publisher is installed and active
2. Upload `podlove-episode-location` to `/wp-content/plugins/`
3. Activate the plugin through the Plugins menu
4. Edit any episode to see the Episode Location meta box

== Frequently Asked Questions ==

= Does this require an API key? =

No. The plugin uses OpenStreetMap tiles and Nominatim geocoding, which are free and open services.

= Does this modify Podlove Publisher? =

No. This is a standalone plugin that hooks into Podlove Publisher's extension points without modifying it.

= What happens to my data if I deactivate? =

Your location data is preserved in the database. Reactivating the plugin will restore access to it.

== Changelog ==

= 1.0.0 =
* Initial release
* Interactive map with Leaflet.js
* Nominatim location search
* Draggable marker with reverse geocoding
* Podlove template tag integration
* Podcasting 2.0 feed location tags
