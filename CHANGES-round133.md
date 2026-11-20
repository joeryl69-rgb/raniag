# Changelog

## 2026-11-19

### Added
- Added administrator-editable copy for the public home page, public navigation and footer, search/social descriptions, reporting guide, app/support cards, and all FAQ questions and answers.
- Added FAQ controls to add, remove, and reorder up to 12 entries. The optional FAQ link continues to use the internal Community Dashboard route.
- Added dynamic system, organization, municipality, province, response-area, and tagline tokens for reusable public copy.

### Changed
- Kept the existing page layout, visual assets, and functional destinations while making their surrounding text configurable.
- Unified administrator branding panel backgrounds so they blend with the selected page surface in light and dark appearance modes.
- Reclassified all coordinate-bearing reports whenever an administrator changes or resets the municipality boundary, so inside/outside filters stay aligned across reports, incident queues, dashboards, and document lists.
- Kept reports with no coordinates in their saved known-location classification instead of guessing that missing locations are outside the municipality.
- Clarified that report routing filters are distinct from geographic location filters, which use the active municipality boundary.

### Safety
- Public copy is rendered as escaped text; administrators cannot enter HTML or change link destinations through this editor.
- Reset to defaults now also clears customized public page copy while preserving the municipality boundary and personal appearance settings.

## 2026-11-20

### Changed
- Reclassified all coordinate-bearing reports when the municipality boundary changes or resets, and added a one-time backfill for existing reports. Inside/outside filters now follow the active boundary across reports, incident queues, dashboards, and document lists.
- Kept reports without coordinates in their saved known-location classification rather than assuming that a missing location is outside the municipality.
- Clarified that report-routing filters are distinct from geographic location filters.
