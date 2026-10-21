# Round 105 — Evidence video, GPS capture, and map interaction fixes

## Changed

- Replaced inline evidence playback controls with a compact video preview that opens a shared popup player. The popup has native playback controls plus minimize, restore, maximize, and close actions, so videos no longer expand evidence cards.
- Increased GPS video watermark readability and wrap long location, coordinate, and timestamp strings without cutting off long words.
- Kept successive file-picker selections attached together, enforced the shared evidence count and file-size limits, and added a removable file list with an explicit “attached; sent on submit” status.
- Kept the public GPS camera launch control in the report form's existing compact primary-button style.
- Restored the mobile map’s visible “Back to scrolling” control and touch interaction styling.
- Stabilized the homepage report-path guide by selecting the step nearest the viewport center, preventing the mascot, caption, and step number from flickering between entries during scroll.

## Verification

- Focused public tracking, upload, archived-video, and byte-range playback tests passed.
- JavaScript syntax checks, Blade compilation, and `git diff --check` passed.
- Browser checks confirmed successive file selections accumulate, the five-file cap is enforced, selected files can be removed, the popup minimizes/restores/maximizes/closes, and mobile map mode keeps the “Back to scrolling” button visible.
- GPS watermark rendering retained the full coordinates, place, and timestamp in portrait-mobile and laptop-sized frame checks.
- Public homepage test passed, and browser scroll checks stayed on the same guide step while adjusting the scroll position by a few pixels.
