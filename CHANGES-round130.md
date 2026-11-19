# Changelog

## 2026-11-17

### Added
- Added a responsive live-route modal with GPS follow, evacuation-center selection, distance and ETA details, and continuously updated positioning.
- Added evacuation-center locations and open/closed status to the community dashboard map and list, with automatic data refresh.

### Changed
- GPS reporting and camera capture now retain the full reverse-geocoded address and actual municipality for locations outside Pamplona's active response boundary.
- When Mapbox routing is unavailable, the live map displays a clearly labeled straight-line distance and time estimate instead of implying a road route.

### Fixed
- Prevented community-dashboard data and active-boundary requests from being served from stale service-worker cache entries, including when the app is hosted under a URL prefix.

## 2026-11-19

### Added
- Added shelter capacity and clearer locality details to the community evacuation-center list and map popups.
- Selecting an evacuation center from the list now focuses its map pin and opens its details.
- Added live occupancy and remaining-space information for each shelter.
- Added an in-app “Route here” action that opens the live map with the selected shelter as the route destination.
- Added a live current-location readout with GPS accuracy and update time, whether or not a route is active.
- Added a prominent current-location card with reverse-geocoded address, barangay, municipality, and GPS update details instead of displaying coordinates.

### Changed
- Moved the evacuation-center panel directly below the “Most active right now” summary.
- Kept the hazard/shelter selector visible in the admin panel header and aligned row action controls.
- Starting live location no longer selects or routes to a shelter automatically.
- Improved route-summary contrast on the dark route panel.
- Kept the hazard/shelter switch and Add action visible while saved items scroll independently; the saved-list heading remains pinned and rows no longer overlap.
- On smaller screens, kept the nearest-center suggestion above the open-report list and limited scrolling to the report list.
- Matched community-dashboard shelter pins to each shelter’s saved map color and clarified that Open/Closed labels indicate arrival status.

### Fixed
- Prevented barangay names from appearing twice in incident locations and improved GPS-camera barangay resolution with dynamic municipal boundaries.
- Removed the misleading unresolved-barangay prefix when reverse-geocoded place details exist and eliminated duplicate province/region labels in GPS-camera addresses.
- Focused the map on the selected shelter during editing and hid other shelter pins.
- Kept new hazard and shelter forms visible on desktop by hiding saved-item lists while the editor is open.
