# Changelog

## 2026-11-20

### Added
- Added administrator-only Branding & System Settings in the administrator profile menu.
- Added global system and organization names, public logo upload/removal, primary/accent/page-background colors, configurable public header/footer text and visibility, and organization contact details.
- Added configurable municipality, province, and response-area display labels for the site and GPS address fallbacks.

### Changed
- Applied branding to public and staff layouts, authentication screens, GPS-camera overlays and photo watermarks, browser icons, and the public footer while preserving each user's personal appearance settings.
- Kept branding location labels separate from geofence and barangay boundary data.
- Replaced technical placeholder instructions with a plain-language picker and labeled chips for details that update with system settings.
- Added a clickable homepage layout map and a nearby live-detail picker that shows each selected value's current source.
- Reworked hazard-area and municipal-boundary editors for narrower screens so detail forms remain accessible and boundary tools no longer cover the map controls.
- Disabled hazard drawing and clear controls until an administrator opens the matching editor and activates the map; shelters only enable Clear after placing a pin.
- Kept saved item lists hidden while their editor is open so starting a hazard boundary does not replace the editor form.
