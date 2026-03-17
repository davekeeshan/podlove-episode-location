(function ($) {
  "use strict";

  var map = null;
  var marker = null;
  var defaultLat = 51.505;
  var defaultLng = -0.09;
  var defaultZoom = 2;
  var locatedZoom = 15;

  /**
   * Initialize the Leaflet map inside the meta box.
   */
  function initMap() {
    var container = document.getElementById("podlove-location-map");
    if (!container) {
      return;
    }

    var existingLat = parseFloat(
      document.getElementById("podlove-location-lat").value
    );
    var existingLng = parseFloat(
      document.getElementById("podlove-location-lng").value
    );
    var hasExisting = !isNaN(existingLat) && !isNaN(existingLng);

    var startLat = hasExisting ? existingLat : defaultLat;
    var startLng = hasExisting ? existingLng : defaultLng;
    var startZoom = hasExisting ? locatedZoom : defaultZoom;

    map = L.map("podlove-location-map").setView(
      [startLat, startLng],
      startZoom
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19,
    }).addTo(map);

    if (hasExisting) {
      placeMarker(startLat, startLng);
    }

    map.on("click", function (e) {
      placeMarker(e.latlng.lat, e.latlng.lng);
      updateCoordinateFields(e.latlng.lat, e.latlng.lng);
      reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    setTimeout(function () {
      map.invalidateSize();
    }, 250);
  }

  /**
   * Place or move the draggable marker on the map.
   */
  function placeMarker(lat, lng) {
    if (marker) {
      marker.setLatLng([lat, lng]);
    } else {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);

      marker.on("dragend", function (e) {
        var pos = e.target.getLatLng();
        updateCoordinateFields(pos.lat, pos.lng);
        reverseGeocode(pos.lat, pos.lng);
      });
    }
  }

  /**
   * Update the latitude and longitude form fields.
   */
  function updateCoordinateFields(lat, lng) {
    document.getElementById("podlove-location-lat").value = lat.toFixed(8);
    document.getElementById("podlove-location-lng").value = lng.toFixed(8);
  }

  /**
   * Search for a location using Nominatim geocoding.
   */
  function searchLocation(query) {
    if (!query || query.trim().length < 2) {
      return;
    }

    var resultsContainer = document.getElementById(
      "podlove-location-search-results"
    );
    resultsContainer.innerHTML =
      '<div class="podlove-location-searching">Searching...</div>';

    var url =
      "https://nominatim.openstreetmap.org/search?format=json&limit=5&q=" +
      encodeURIComponent(query);

    $.ajax({
      url: url,
      dataType: "json",
      headers: {
        Accept: "application/json",
      },
      success: function (data) {
        displaySearchResults(data);
      },
      error: function () {
        resultsContainer.innerHTML =
          '<div class="podlove-location-error">Search failed. Please try again.</div>';
      },
    });
  }

  /**
   * Display geocoding search results as a selectable list.
   */
  function displaySearchResults(results) {
    var container = document.getElementById(
      "podlove-location-search-results"
    );

    if (!results || results.length === 0) {
      container.innerHTML =
        '<div class="podlove-location-no-results">No results found.</div>';
      return;
    }

    var html = '<ul class="podlove-location-results-list">';
    for (var i = 0; i < results.length; i++) {
      html +=
        '<li class="podlove-location-result-item" ' +
        'data-lat="' +
        results[i].lat +
        '" ' +
        'data-lng="' +
        results[i].lon +
        '" ' +
        'data-name="' +
        escapeHtml(results[i].display_name) +
        '">' +
        escapeHtml(results[i].display_name) +
        "</li>";
    }
    html += "</ul>";

    container.innerHTML = html;

    $(container)
      .find(".podlove-location-result-item")
      .on("click", function () {
        var lat = parseFloat($(this).data("lat"));
        var lng = parseFloat($(this).data("lng"));
        var name = $(this).data("name");

        placeMarker(lat, lng);
        updateCoordinateFields(lat, lng);
        map.setView([lat, lng], locatedZoom);

        document.getElementById("podlove-location-address").value = name;

        var nameField = document.getElementById("podlove-location-name");
        if (!nameField.value) {
          nameField.value = name.split(",")[0].trim();
        }

        container.innerHTML = "";
      });
  }

  /**
   * Reverse geocode coordinates to get an address.
   */
  function reverseGeocode(lat, lng) {
    var url =
      "https://nominatim.openstreetmap.org/reverse?format=json&lat=" +
      lat +
      "&lon=" +
      lng;

    $.ajax({
      url: url,
      dataType: "json",
      success: function (data) {
        if (data && data.display_name) {
          document.getElementById("podlove-location-address").value =
            data.display_name;

          var nameField = document.getElementById("podlove-location-name");
          if (!nameField.value && data.display_name) {
            nameField.value = data.display_name.split(",")[0].trim();
          }
        }
      },
    });
  }

  /**
   * Escape HTML entities to prevent XSS in search results.
   */
  function escapeHtml(text) {
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }

  /**
   * Bind event listeners.
   */
  function bindEvents() {
    $("#podlove-location-search-btn").on("click", function (e) {
      e.preventDefault();
      var query = $("#podlove-location-search").val();
      searchLocation(query);
    });

    $("#podlove-location-search").on("keypress", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        var query = $(this).val();
        searchLocation(query);
      }
    });

    $(document).on("postbox-toggled", function (e, postbox) {
      if (postbox.id === "podlove_episode_location" && map) {
        setTimeout(function () {
          map.invalidateSize();
        }, 100);
      }
    });
  }

  $(document).ready(function () {
    if (document.getElementById("podlove-location-map")) {
      initMap();
      bindEvents();
    }
  });
})(jQuery);
