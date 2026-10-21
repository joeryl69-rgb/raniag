# Round 67 — Operations reporting workbench

- Replaced the one-purpose weekly picture page with three clear destinations: a period-based operations overview, a report builder, and a searchable archive.
- The overview now includes system activity beyond incidents: shelter occupancy, missing case papers, SMS outcomes, support requests, public notices, staff activity, and the current hazard map.
- The report builder creates a document from selected operational sections. Each section has only its own settings, visible server-rendered row previews, order controls, and row exports where supported. The starter document contains open cases, the shelter roll, and closed cases missing papers.
- Creating, saving, and filing validate the complete title, period, section list, and section settings before persisting. Filing locks the record and stores both its letterhead PDF and the exact section rows used to produce it; opening or exporting a filed section uses that same snapshot.
- Existing generated exports remain available in a separate, paginated archive section. Drafts can be continued or deleted; filed reports can be downloaded or copied into a new draft.
- Open-cases sections now contain only open cases. Tests cover validation-before-create, report generation, stable filed snapshots, chapter exports, and the older saved-file archive.
