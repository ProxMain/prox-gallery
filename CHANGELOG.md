# Changelog

## 0.1.2 - 2026-03-14

- Fixed Vite module resolution for shared typed frontend utilities by restoring JS compatibility entrypoints.
- Centralized shared frontend async loading state and feature-level controller wiring in the admin app.
- Extracted reusable frontend components from gallery, media-manager, and settings screens.
- Centralized admin action config assembly and admin capability ownership.
- Corrected AJAX error semantics to return explicit `400`, `403`, `404`, and `500` responses.
- Moved gallery normalization and gallery access behind service and repository boundaries.
- Split bootstrap registration concerns out of `App` and removed the dead gallery model dependency.
- Prevented template settings updates from overwriting unrelated shared plugin options.
- Added `feature.md` to track upcoming product work.
