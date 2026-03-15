# Feature Backlog

This file tracks shipped feature work in a condensed format.

## Proposed

## Feature: Dashboard rework for cinematic analytics

Status: proposed
Priority: high
Area: full-stack

### Problem

The current dashboard gives only a basic utility view of Prox Gallery activity.

Current state:
- The dashboard in [dashboard-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/dashboard/dashboard-section.jsx) shows totals, top countries, top galleries, and top images in a fairly flat card layout.
- The backend summary from [TrackingSummaryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TrackingSummaryService.php#L13) currently exposes totals, country counts, ranked galleries, ranked images, and `updated_at`.
- Frontend tracking in [FrontendTrackingService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendTrackingService.php#L10) records gallery visits and image views, but the dashboard does not yet turn that data into a strong visual story.
- Gallery domain data from [GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php#L13) already exposes useful context such as template, created date, image counts, and presentation settings that could enrich analytics.

This means photographers can technically inspect some stats, but they cannot quickly understand:
- which galleries are performing best right now
- which images are breakout portfolio pieces
- which categories or presentation styles are strongest
- what needs attention, promotion, or cleanup

### Desired outcome

The dashboard should become a cinematic analytics experience that feels more like a photographer’s command center than a generic admin page.

It should help users quickly answer:
1. How much traffic and image engagement am I getting?
2. Which galleries and images are performing best?
3. Which categories or portfolio areas are strongest or weakest?
4. What is trending now versus slowing down?
5. What actions should I take next to improve my portfolio or client-facing galleries?

### Constraints

Architectural constraints:
- Keep the dashboard as a read-only analytics surface.
- Do not move tracking logic into the dashboard UI.
- New event collection must stay inside frontend/backend tracking services, not presentation components.
- Avoid adding orchestration to [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L22); keep dashboard logic in the dashboard feature.
- Reuse the existing admin action-controller pattern through [TrackingActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/TrackingActionController.php#L13).
- Expand the analytics payload through [TrackingSummaryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TrackingSummaryService.php#L13) instead of introducing scattered summary builders.

Design constraints:
- The dashboard should feel cinematic and visual, not like a spreadsheet.
- It should work on desktop and mobile.
- Charts and insight blocks must remain readable when data is sparse.

### Technical design

High-level approach:
- Rework the dashboard layout into a more immersive analytics surface with a strong hero section, chart-led storytelling, and visually rich ranked insight blocks.
- Use the existing tracking summary endpoint as the main backend entry point, but expand the payload to include richer aggregates and comparison data.
- Combine analytics data with existing gallery metadata so dashboard blocks can show context like gallery template, image count, and recency.
- Build the dashboard in phases so `v1` uses mostly existing data, while `v2` adds deeper insight blocks after more tracking signals exist.

Implementation decision:
- `Phase 1` should ship the cinematic layout plus richer ranked and inventory-based insight panels without depending on new time-bucket tracking.
- `Phase 2` should add true trend charts, period comparisons, and momentum once daily buckets exist in tracking storage.
- `Phase 3` should add recommendation and interaction-driven analytics once richer behavior tracking exists.

Recommended dashboard structure:
- full-width hero performance strip
- primary trend area for traffic and engagement
- ranked performance blocks for galleries and images
- category and geography insight blocks
- action-oriented panels such as momentum, health, and recommendations

### Backend changes

#### 1. Expand the tracking summary payload for `Phase 1`

Current backend already provides:
- total gallery views
- total image views
- country counts
- ranked galleries
- ranked images
- `updated_at`

Needed additions for `Phase 1`:
- tracked image count
- gallery count
- gallery metadata merged into ranked gallery rows
- image preview metadata where practical
- category inventory summaries
- recent gallery creation activity
- recent tracked image activity

Recommended responsibility:
- keep summary composition inside [TrackingSummaryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TrackingSummaryService.php#L13)
- keep [TrackingActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/TrackingActionController.php#L13) as a thin transport layer

#### 2. Add time-based aggregates for `Phase 2`

Needed for:
- visits over time
- image views over time
- recent momentum
- period-over-period comparisons

Recommended direction:
- extend stored tracking stats to include daily buckets for gallery visits and image views
- expose current-period and previous-period aggregates in the summary payload

Do not:
- compute complicated trend logic in React from raw low-level event arrays
- create a second analytics storage path just for dashboard charts

#### 3. Add category-aware summary data

Needed for:
- category performance block
- portfolio gap analysis
- category-level comparisons

Recommended direction:
- aggregate gallery counts and image counts by category first
- later add visit and engagement metrics by category once category-linked traffic summaries exist

#### 4. Prepare later-phase insight metrics

Needed for later blocks:
- image-views-per-visit ratio
- underperforming gallery detection
- health scoring
- template and layout comparisons
- recommendation generation

Recommended direction:
- derive these from existing tracking plus gallery metadata inside the summary service
- keep scoring formulas centralized instead of spreading them across components

### Frontend changes

#### 1. Rework the dashboard into a cinematic analytics layout

Current dashboard:
- simple stat cards
- simple top-country list
- simple top-gallery list
- simple top-image list

Needed change:
- redesign the dashboard feature into a more immersive composition with:
  - hero strip
  - large visual chart area
  - ranked visual panels
  - secondary insight blocks
  - action-oriented summaries

#### 2. Add `Phase 1` dashboard blocks

Recommended `Phase 1` blocks:
- hero performance strip
- top galleries panel
- top images panel
- country reach panel
- category performance panel
- recent activity timeline
- portfolio gaps panel
- client-ready spotlight

Phase 1 note:
- Do not block the first release on true historical charts. Use strong visual ranking, composition, and contextual summaries first.

#### 3. Add `Phase 2` dashboard blocks

Recommended `Phase 2` blocks:
- gallery visits trend chart
- image views trend chart
- recent momentum block
- underperforming galleries
- fresh upload performance
- gallery health score
- template/layout comparison
- best time to publish

#### 4. Add `Phase 3` dashboard blocks

Recommended `Phase 3` blocks:
- smart recommendations
- visit source breakdown
- lightbox engagement
- hover and transition effectiveness
- device split insights
- seasonal comparison

#### 5. Keep orchestration inside the dashboard feature

Recommended structure:
- keep data loading inside the dashboard feature
- add small dashboard-specific chart or insight components only where justified
- keep summary normalization in the feature layer, not in generic UI atoms

Suggested candidates:
- `DashboardHeroStrip`
- `DashboardTrendChart`
- `DashboardPerformancePanel`
- `DashboardCategoryInsights`
- `DashboardActivityTimeline`
- `DashboardRecommendations`

### Data / API changes

### `Phase 1` summary payload additions

Recommended payload additions:
- `tracked_images_total`
- `galleries_total`
- `gallery_activity`
- `image_activity`
- `categories`
- `recent_activity`
- `gallery_metadata`
- `comparison` fields where available

Phase 1 decision:
- `comparison` fields are optional in Phase 1 and should not block shipping. If previous-period data is unavailable, the UI should omit comparison treatments cleanly.

### Phase 2 payload additions

Needed in Phase 2:
- daily or weekly time buckets
- previous-period metrics
- momentum aggregates
- gallery-level recent-trend metrics
- image-level recent-trend metrics

### Phase 3 payload additions

Needed later:
- template performance aggregates
- full-width vs contained aggregates
- referrer/source aggregates
- lightbox interaction aggregates
- device split aggregates

### Acceptance criteria

1. The dashboard feels like a visual analytics experience rather than a simple admin summary.
2. `Phase 1` clearly surfaces total performance, top galleries, top images, geography, category-level insight, and recent activity without requiring new tracking infrastructure.
3. The dashboard uses the existing tracking and gallery service boundaries instead of inventing a second analytics source.
4. `Phase 2` trend and comparison blocks degrade gracefully when historical data is limited.
5. Sparse accounts with little or no traffic still render useful empty states.
6. The dashboard remains usable on desktop and mobile.
7. Later phases build on the same analytics source of truth rather than adding disconnected logic.

### Testing plan

Backend tests:
- extend tracking summary service tests for new payload fields
- verify totals, ranking, and category summaries are computed correctly
- verify empty-state payloads remain stable
- verify Phase 2 comparison metrics do not break when time-bucket data is missing

Frontend tests or verification:
- verify hero metrics render correctly
- verify top galleries and top images display expected rankings
- verify empty states render cleanly
- verify Phase 1 panels remain readable with large and small datasets
- verify Phase 2 charts remain readable with large and small datasets
- verify mobile layout remains usable

Manual scenarios:
- dashboard with no tracking data
- dashboard with a few galleries and sparse traffic
- dashboard with heavy image-view traffic
- dashboard with multiple categories and uneven gallery sizes
- dashboard after recently creating galleries or tracking new images

### Roadmap

#### Phase 1: Cinematic overview without new tracking infrastructure

- Ship the redesigned dashboard layout first.
- Focus on strong visual presentation of data already available or easily derivable now.
- Include:
  - hero performance strip
  - top galleries
  - top images
  - country reach
  - category inventory/performance
  - recent activity timeline
  - portfolio gaps
  - client-ready spotlight

#### Phase 2: Real analytics trends and comparisons

- Extend tracking storage and summary payloads with daily buckets and previous-period metrics.
- Add:
  - gallery visits trend chart
  - image views trend chart
  - recent momentum
  - underperforming galleries
  - fresh upload performance
  - gallery health score
  - template/layout comparison
  - best time to publish

#### Phase 3: Recommendation and interaction intelligence

- Add richer interaction and attribution tracking.
- Add:
  - smart recommendations
  - visit source breakdown
  - lightbox engagement
  - hover and transition effectiveness
  - device split insights
  - seasonal comparison

### Decisions made

1. Phase 1 should not wait on charts. Ship the cinematic overview first using ranked and inventory-based insight blocks.
2. Recommendation blocks should start as read-only insights in Phase 3, with quick actions only after the insight quality is trustworthy.
3. Phase 1 category performance should start with gallery and image inventory signals, then add audience-performance metrics in Phase 2 or later.
4. Phase 1 should aim for a strong editorial-style visual treatment, but not at the cost of readability or implementation speed.

- Media Manager integrated upload and select-existing flow: Opens the WordPress media modal inside Prox Gallery, lets users upload or select existing images, tracks the selected attachments, and refreshes the media manager list. Done
- Gutenberg block to select and render a Prox Gallery gallery: Adds a dynamic `prox-gallery/gallery` block so editors can select an existing gallery in Gutenberg and render it through the existing frontend gallery pipeline. Done
- Guided gallery creation wizard: Adds a step-by-step gallery creation flow for basics, template, layout, effects, and content settings, then creates the gallery through the existing gallery service. Done
