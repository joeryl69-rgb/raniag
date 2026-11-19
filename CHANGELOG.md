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

### Changed
- Moved the evacuation-center panel directly below the “Most active right now” summary.
- Kept the hazard/shelter selector visible in the admin panel header and aligned row action controls.
- Starting live location no longer selects or routes to a shelter automatically.

### Fixed
- Prevented barangay names from appearing twice in incident locations and improved GPS-camera barangay resolution with dynamic municipal boundaries.
- Focused the map on the selected shelter during editing and hid other shelter pins.
- Kept new hazard and shelter forms visible on desktop by hiding saved-item lists while the editor is open.
