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
