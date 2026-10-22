# Changelog

## 0.2.0-rc.2 - 2026-10-22

- Redesigned the admin workspace, gallery collection cards, media library, image editor and settings.
- Replaced the gallery wizard with a compact editor and accessible native dialogs; image ordering works with keyboard controls.
- Split feature responsibilities, removed obsolete UI and unused controller dependencies, and centralised gallery override mapping.
- Stabilised collection request lifecycles, deduplicated requests and rejected obsolete responses.
- Added attachment-level AI permissions, persistent capability revocations, redacted key responses and bounded AI file reads.
- Preserved nested tracking statistics, rejected unverified country headers and tightened tracking payload validation.
- Kept gallery creation separate from explicit page publication; media listing now reads current titles without rewriting storage.
- Kept lightbox browsing inside its gallery, restored focus/scrolling, preserved alternative text and respected reduced motion.
- Pinned dependencies, added Composer/PHPUnit configuration, regression suites, CI, packaging tools and a portable design preview.
- Added atomic compare-and-swap persistence for gallery, media, tracking and settings mutations; retries preserve concurrent updates.
- Removed silent media-index eviction above 500 attachments.
- Added configurable atomic AI quotas (20 per user / 100 per site per UTC day), HTTP 429 responses and remaining usage in the editor. Failed provider attempts count; zero pauses generation.
- Full-collection reads and the best-effort public tracking throttle remain documented scaling limits.

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
