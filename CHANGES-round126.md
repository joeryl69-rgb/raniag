# Round 126 - Edit saved evacuation shelters

- Added an Edit action for saved shelters in the admin hazard workspace.
- The edit form loads the shelter's saved details, supports changing its pin location and properties, and provides a cancel action that restores the saved map marker.
- Expanded shelter updates to save name, barangay, address, coordinates, capacity, open status, notes, and pin color while preserving the existing color-only update behavior.
- Validation failures return to the selected shelter's edit state.

## Verification

- Shelter edit, color-only update, and validation recovery tests passed.
- Public hazard map tests passed.
- PHP syntax checks and `git diff --check` passed.
- The complete hazard authoring test file has one unrelated existing failure: an unclosed polygon test expects rejection, while the current controller intentionally closes open polygon boundaries.
