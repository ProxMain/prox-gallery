# Feature Backlog

This file is the working backlog for product features we want to add.

How to use this file:
- Add each feature as its own section using the template below.
- Treat [review.md](/home/marcelsanting/PhpstormProjects/prox-gallery/review.md) as the architectural source of truth.
- New work should follow the review guardrails:
  - single source of truth for storage and configuration
  - responsibilities must live in the correct class/layer
  - frontend reuse should follow the atomic plan instead of adding more monolith components
  - feature orchestration belongs in feature hooks/containers, not in generic UI atoms

Suggested feature template:

```md
## Feature: <name>

Status: proposed | planned | in-progress | done
Priority: low | medium | high
Area: backend | frontend | full-stack

### Problem

### Desired outcome

### Constraints

### Technical design

### Backend changes

### Frontend changes

### Data / API changes

### Acceptance criteria

### Open questions
```

---

## Feature: Media Manager integrated upload and select-existing flow

Status: proposed
Priority: high
Area: full-stack

### Problem

In the current media manager, the `Upload` action sends the user away from the plugin UI to the default WordPress Media Library upload page.

Current state:
- The button in [media-manager-header.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-manager-header.jsx#L14) links directly to `/wp-admin/upload.php`.
- New uploads are tracked automatically by [MediaUploadController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaUploadController.php#L24) through attachment hooks.
- Existing images that were added before tracking are not selectable from inside the Prox Gallery media manager UI.
- The only current catch-up mechanism is broad sync via [MediaManagerSyncService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Services/MediaManagerSyncService.php#L18), which is not a targeted user workflow.

### Desired outcome

Inside the Prox Gallery media manager, the user should be able to:

1. click an upload/select button without leaving the plugin screen
2. open the standard WordPress media modal
3. upload new files in that modal
4. browse existing image attachments that are not yet tracked
5. select one or more images
6. confirm selection
7. have the selected attachments become tracked in Prox Gallery
8. return to the same Prox Gallery screen with the tracked list refreshed

### Constraints

Architectural constraints from the review:
- Do not add direct storage writes in the frontend.
- Do not bypass the media tracking service with duplicate tracking logic.
- Do not add more top-level orchestration into [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L23).
- Keep a single source of truth for tracking in the media library backend service layer.
- Follow the frontend atomic plan in [review.md](/home/marcelsanting/PhpstormProjects/prox-gallery/review.md):
  - feature-specific orchestration goes into a media-manager container/hook
  - reusable buttons/panels belong in reusable UI layers
  - do not put WordPress media-modal integration into generic `ui` atoms

### Technical design

High-level approach:
- Replace the current external upload link with an integrated WordPress media modal workflow.
- Reuse the WordPress media frame (`wp.media`) for both uploading and selecting existing attachments.
- Add a targeted backend action to track selected attachment IDs.
- Keep all “what does it mean to track an image” logic inside the existing backend tracking service.

Recommended user flow:
- User clicks `Add media` in the media manager header or toolbar.
- A Prox-specific media modal opens.
- The modal allows upload and library browsing.
- The library view should be filtered to images only.
- After selecting attachments and confirming, the frontend sends selected attachment IDs to a backend action.
- The backend tracks only those selected attachments.
- The media manager list reloads and shows the newly tracked items.

### Backend changes

#### 1. Add a targeted “track selected attachments” action

Current backend only has:
- `list`
- `sync`
- `update`

Files affected:
- [MediaManagerActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php#L18)
- [App.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/App.php#L191)

Needed change:
- Add a new AJAX action, for example:
  - `prox_gallery_media_manager_track_selected`

Controller responsibility:
- validate capability and nonce through the existing action-controller mechanism
- parse `attachment_ids`
- delegate to a service
- return a structured response

Do not:
- put tracking rules directly in the controller
- parse attachment posts in the controller

#### 2. Add a dedicated service for targeted tracking

Recommended new service:
- `MediaManagerTrackSelectionService`

Reason:
- `MediaManagerSyncService` tracks everything
- `TrackUploadedImageService` tracks one item at a time
- this feature needs a service that coordinates a batch of explicit user-selected IDs

Suggested responsibility:
- accept `list<int> $attachmentIds`
- normalize IDs
- call `TrackUploadedImageService::track()` for each item
- aggregate result counts
- return a response such as:
  - `requested_count`
  - `tracked_count`
  - `skipped_count`
  - `tracked_ids`
  - `skipped_ids`

Suggested file:
- `src/Modules/MediaLibrary/Services/MediaManagerTrackSelectionService.php`

This preserves single source of truth:
- “how an attachment becomes tracked” remains inside [TrackUploadedImageService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Services/TrackUploadedImageService.php#L29)
- the new service only coordinates batch use of that existing rule

#### 3. Keep queue ownership in the existing model

Tracking persistence should still flow through:
- [TrackUploadedImageService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Services/TrackUploadedImageService.php#L35)
- [UploadedImageQueueModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Models/UploadedImageQueueModel.php#L19)

Do not:
- create a second queue writer
- write `prox_gallery_uploaded_image_ids` directly from a controller or feature-specific service

#### 4. Response shape

Suggested response:

```json
{
  "action": "prox_gallery_media_manager_track_selected",
  "requested_count": 5,
  "tracked_count": 4,
  "skipped_count": 1,
  "tracked_ids": [12, 15, 18, 21],
  "skipped_ids": [9]
}
```

This gives the frontend enough information to show useful notices without adding new truth sources.

### Frontend changes

#### 1. Replace the external upload link with an integrated action

Current file:
- [media-manager-header.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-manager-header.jsx#L14)

Needed change:
- Replace the direct anchor link with a button like `Add media`
- That button should trigger a feature-level callback, not directly call `wp.media`

Why:
- keep WordPress modal orchestration out of presentational atoms
- keep header component reusable

#### 2. Add a media modal integration layer in the media-manager feature

Recommended structure following the review:
- feature hook or service adapter:
  - `useWpMediaFrame`
  - or `useMediaManagerPicker`

Responsibility:
- lazy-create and reuse a `wp.media` frame
- configure library type to images only
- allow multi-select
- surface selected attachment models as normalized frontend data
- expose `open()` and selection callbacks

Suggested location:
- `assets/admin/src/features/media-manager/hooks/use-media-manager-picker.ts`

Do not:
- place this in `components/ui`
- place this in `app.jsx`

#### 3. Add a targeted media-manager action on the client controller

Current file:
- [media-manager-action-controller.ts](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/modules/media-library/controllers/media-manager-action-controller.ts#L7)

Needed change:
- add a new definition key:
  - `track_selected`
- add a method like:
  - `trackSelectedAttachments(attachmentIds: number[])`

Responsibility:
- serialize IDs
- call the backend action
- stay a transport adapter only

#### 4. Move workflow orchestration into a media-manager feature hook/container

Current issue:
- feature wiring is centralized in [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L23)

Recommended change:
- add a media-manager container or hook that owns:
  - media-manager controller
  - category controller
  - WordPress media frame integration
  - reload after track
  - notices for tracked/skipped counts

Suggested candidates:
- `MediaManagerContainer`
- `useMediaManagerActions`
- `useMediaManagerPicker`

This follows the review:
- orchestration belongs to the feature
- `App` should not absorb new media-modal logic

#### 5. Add reusable UI pieces only where they are actually reusable

Potential reusable pieces under the atomic plan:
- `ToolbarButton`
- `StatusMessage`
- `SelectionSummary`

But:
- the WordPress media modal adapter itself is feature-specific
- selection-to-track workflow is feature-specific

#### 6. Refresh behavior

After successful selection:
- call `trackSelectedAttachments`
- then call existing `reloadTrackedImages`
- show a contextual success notice:
  - “4 images added to Media Manager”
  - or “4 images added, 1 skipped”

### Data / API changes

#### Admin config payload

Current config for media manager includes:
- `list`
- `sync`
- `update`

Files:
- [MediaManagerActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php#L120)
- [admin-config.ts](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/lib/admin-config.ts#L11)

Needed change:
- add `track_selected` to the media-manager action definitions in both backend and frontend typing

#### Optional frontend typing additions

Recommended new types:
- `MediaManagerTrackSelectedResponse`
- `WpMediaAttachmentSelection`

### Acceptance criteria

1. The media manager no longer forces the user to leave the Prox Gallery screen to upload/select media.
2. Clicking the new action opens the WordPress media modal.
3. The modal supports both:
   - uploading new images
   - selecting existing image attachments
4. Selecting one or more attachments tracks only those selected attachments.
5. Already tracked items are handled safely and do not create duplicates.
6. After confirmation, the tracked images list refreshes automatically.
7. The feature does not directly write queue storage outside the existing media tracking service/model path.
8. Frontend orchestration does not expand `App` further and follows the container/hook guidance from `review.md`.

### Testing plan

Backend tests:
- add controller test for `track_selected`
- add service test for `MediaManagerTrackSelectionService`
- verify duplicates are skipped or treated idempotently
- verify non-image attachment IDs do not break the request

Frontend tests or verification:
- verify the new button opens the WP media frame
- verify multi-select returns attachment IDs
- verify successful selection reloads tracked images
- verify notices for partial success
- verify missing controller config fails gracefully

Manual scenarios:
- upload a brand-new image in the modal and confirm it appears in the tracked list
- select old untracked images and confirm they appear
- select already tracked images and confirm no duplicate rows are created
- mix tracked and untracked images in one selection

### Open questions

1. Should the media modal show all images, or should it prefer filtering out already tracked images where possible?
2. Should the action button text be `Upload`, `Add media`, or `Add / select media`?
3. Do we want a separate quick action for `Upload new only`, or is one combined modal enough?
4. Should selection happen from the header only, or also from the `Media files` card toolbar?

---

## Feature: Gutenberg block to select and render a Prox Gallery gallery

Status: proposed
Priority: high
Area: full-stack

### Problem

Right now Prox Gallery frontend rendering is shortcode-first.

Current state:
- Frontend rendering is exposed through the `[prox_gallery]` shortcode in [FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L39).
- Gallery pages are provisioned by generating shortcode content in [GalleryPageProvisioningService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryPageProvisioningService.php#L59).
- There is no Gutenberg block or editor UI for selecting one of our existing galleries directly from the block editor.

This means editors must:
- know the shortcode format
- manually type gallery IDs
- or rely on generated pages instead of using a native editor workflow

### Desired outcome

Editors should be able to insert a Prox Gallery block in Gutenberg and:

1. search/select one of the galleries created in Prox Gallery
2. optionally choose or override the template in the block UI
3. preview the selected gallery in the editor
4. save the post with a block instead of handwritten shortcode
5. render on the frontend through the same gallery rendering source of truth already used by the shortcode

### Constraints

Architectural constraints from [review.md](/home/marcelsanting/PhpstormProjects/prox-gallery/review.md):
- do not create a second gallery data source for block rendering
- do not duplicate gallery rendering logic just for Gutenberg
- do not bypass repository/service boundaries
- frontend/editor integrations should follow feature boundaries and avoid stuffing more orchestration into unrelated entry points
- gallery selection and rendering rules must continue to have a single source of truth

### Technical design

High-level approach:
- Add a dynamic Gutenberg block, for example `prox-gallery/gallery`.
- Store only minimal block attributes:
  - `galleryId`
  - optional `template`
- Render the block on the server by delegating to the same frontend gallery service/render pipeline used by the shortcode.

Key principle:
- The block is an editor integration, not a new rendering engine.
- Gallery rendering must stay owned by the frontend gallery service/template renderer path.

### Backend changes

#### 1. Register a dynamic block

Needed addition:
- register a Gutenberg block on plugin boot
- use `render_callback` instead of static saved HTML

Reason:
- dynamic rendering keeps the block aligned with current gallery/template settings
- avoids saved markup drift

Suggested backend responsibility:
- a dedicated controller or registrar such as:
  - `FrontendGalleryBlockController`
  - or `GutenbergGalleryBlockRegistrar`

Responsibility:
- register block type
- expose editor assets
- provide `render_callback`

Do not:
- register the block directly inside unrelated controllers
- bury block registration inside the existing shortcode controller

#### 2. Reuse the existing frontend rendering path

Current rendering source:
- [FrontendGalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryService.php#L55)
- [FrontendGalleryTemplateRegistry.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryTemplateRegistry.php#L15)
- [FrontendGalleryTemplateRenderer.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryTemplateRenderer.php#L18)

Recommended implementation:
- have the block `render_callback` translate block attributes into the same attribute shape expected by `FrontendGalleryService::renderShortcode()`
- call that service directly

Example strategy:
- block attributes:
  - `galleryId`
  - `template`
- render callback builds:
  - `['id' => <galleryId>, 'template' => <template>]`
- then delegates to `FrontendGalleryService::renderShortcode()`

This preserves single source of truth:
- template resolution stays in `FrontendGalleryTemplateRegistry`
- gallery loading stays in `FrontendGalleryRepository`
- rendering stays in `FrontendGalleryTemplateRenderer`

#### 3. Add an editor-facing gallery catalog endpoint or block preload path

The editor needs a way to list available galleries.

Recommended options:
- preferred: add a dedicated REST endpoint for editor use
- acceptable fallback: reuse existing AJAX action if the team wants consistency with current admin transport

Preferred direction:
- add a read-only editor endpoint returning:
  - `id`
  - `name`
  - `description`
  - `template`
  - `image_count`

Reason:
- Gutenberg/editor integrations fit more naturally with REST than custom AJAX posts
- the endpoint can remain thin and delegate to the gallery service/repository

Suggested backend class:
- `GalleryBlockDataController`
- or `GalleryEditorCatalogController`

Do not:
- let the block editor read `prox_gallery_galleries` directly from localized globals
- duplicate gallery normalization specifically for editor consumption

### Frontend changes

#### 1. Add a Gutenberg block editor package

Needed addition:
- a dedicated block editor entry, separate from the current admin React app in `assets/admin/src`

Suggested structure:
- `assets/blocks/gallery-selector/`
  - `block.json`
  - `index.tsx`
  - `edit.tsx`
  - `save.tsx` or server-rendered `save` returning `null`
  - `components/`
  - `hooks/`

Reason:
- the admin SPA and Gutenberg editor are separate integration surfaces
- block-editor code should not be mixed into the admin dashboard bundle

#### 2. Editor UI behavior

The block edit UI should support:
- gallery picker dropdown or searchable combobox
- gallery summary preview:
  - name
  - description
  - image count
  - default template
- optional block-level template override
- placeholder state when no gallery is selected
- helpful empty state when no galleries exist

Recommended atomic breakdown:
- `GallerySelectField`
- `GallerySummaryCard`
- `TemplateOverrideField`
- `GalleryBlockPlaceholder`

These can be block-editor-specific components, not generic dashboard atoms.

#### 3. Data loading in the editor

Recommended hook:
- `useGalleryCatalog`

Responsibility:
- load available galleries for the editor
- own loading/error state
- normalize API response into block-editor option data

Do not:
- put fetching directly inside the top-level `edit.tsx` if the logic grows
- duplicate the same load/error pattern if more editor blocks are added later

#### 4. Preview strategy

Recommended preview behavior:
- use server-side rendering in the block editor where possible
- or render a lightweight editor preview using returned gallery metadata plus server render

Best long-term option:
- dynamic block with server-side preview, so frontend and editor output stay aligned

### Data / API changes

#### Block attributes

Suggested attributes:

```json
{
  "galleryId": {
    "type": "number"
  },
  "template": {
    "type": "string",
    "default": ""
  }
}
```

Meaning:
- `galleryId` is required for a meaningful render
- `template` is optional; empty means “use gallery/default template resolution”

#### Gallery catalog response

Suggested editor data shape:

```json
[
  {
    "id": 12,
    "name": "Summer Collection",
    "description": "Beach shots",
    "template": "basic-grid",
    "image_count": 24
  }
]
```

This should come from the existing gallery domain layer, not from raw option reads.

### Acceptance criteria

1. A Prox Gallery block appears in Gutenberg inserter.
2. The editor can select one of the existing Prox Gallery galleries.
3. The block stores a gallery reference, not static gallery markup.
4. The frontend render path is shared with the existing shortcode/service rendering flow.
5. Template overrides in the block, if supported, use the same template resolution rules already used by the frontend service.
6. If the selected gallery is missing or deleted, the block fails gracefully.
7. The feature does not introduce a second gallery storage or rendering source of truth.

### Testing plan

Backend tests:
- block registration test
- render callback test for valid gallery
- render callback test for missing gallery
- editor catalog endpoint test

Frontend/editor verification:
- inserter shows block
- selecting a gallery updates block attributes
- saved post renders selected gallery on frontend
- changing gallery/template settings still affects dynamic block output

Manual scenarios:
- insert block into a post and select an existing gallery
- insert block when no galleries exist
- delete a gallery referenced by a block and confirm graceful behavior
- change template defaults and verify block output remains consistent

### Open questions

1. Do we want the block to support only single-gallery selection, or later multiple galleries?
2. Should the block allow a template override, or should it always inherit the gallery’s configured template?
3. Do we want a live visual preview in the editor, or is a metadata summary enough for v1?
4. Should the shortcode remain the internal rendering mechanism, or should the block render callback call the frontend gallery service directly without going through shortcode semantics?
