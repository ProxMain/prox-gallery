# Review

## SOLID

### 1. `App` is a god-class composition root

Files:
- [src/Bootstrap/App.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/App.php#L72)
- [src/Bootstrap/App.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/App.php#L107)
- [src/Bootstrap/App.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/App.php#L461)

Why it is a problem:
- `App` owns container bindings, feature toggles, manager registration, admin contributor wiring, and boot ordering.
- Adding a new module/controller/service usually requires edits in multiple places in the same class, which is a `SRP` and `OCP` smell.
- This also makes the bootstrap layer the main source of merge conflicts.

Suggested direction:
- Let each module register its own bindings/controllers/flows into the container, or move registrations into per-module provider classes.
- Keep `App` responsible only for high-level boot sequencing.

Status:
- Fixed by extracting container registration into [AppBindingRegistrar.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/AppBindingRegistrar.php) and manager population into [AppManagerRegistrar.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/AppManagerRegistrar.php).
- [App.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/App.php) now coordinates lifecycle boot only: build container, delegate bindings, delegate manager registration, and boot managers.
- This is still bootstrap-level extraction rather than full per-module providers, but it removes the main composition-root hotspot and lowers the change surface in `App`.

### 2. `FrontendGalleryController` mixes boundary, domain, persistence, and infrastructure concerns

Files:
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L30)
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L65)
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L108)
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L163)
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L219)

Why it is a problem:
- The controller handles shortcode registration, asset enqueueing, AJAX validation, gallery lookup, image lookup, gallery membership checks, and rate limiting.
- It bypasses existing service/repository abstractions by reading `prox_gallery_galleries` directly.
- That makes the controller harder to test and more fragile when gallery storage rules change.

Suggested direction:
- Move tracking validation, rate limiting, and gallery/image lookup behind dedicated services or repository methods.
- Keep the controller as a thin transport adapter.

## DRY

### 1. Admin config contributor logic is repeated across controllers

Files:
- [src/Modules/Admin/Controllers/TemplateSettingsActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/TemplateSettingsActionController.php#L78)
- [src/Modules/Admin/Controllers/TrackingActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/TrackingActionController.php#L57)
- [src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php#L120)
- [src/Modules/MediaLibrary/Controllers/MediaCategoryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaCategoryActionController.php#L127)
- [src/Modules/OpenAi/Controllers/OpenAiActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/OpenAi/Controllers/OpenAiActionController.php#L147)
- [src/Modules/Gallery/Controllers/GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php#L295)

Why it is a problem:
- Each controller repeats the same pattern: read `action_controllers`, build `{ action, nonce }`, write the array back.
- The only real variation is the controller key and the actions exposed.
- Every structural change to the admin payload now has to be repeated in several places.

Suggested direction:
- Add a shared helper or small base abstraction that derives admin config entries directly from `actions()`.
- Let controllers only describe action metadata once.

Status:
- Fixed by adding shared admin action-config composition to [AbstractActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Controllers/AbstractActionController.php).
- Admin action controllers now declare only their controller key plus exposed action names, while the base controller builds the shared `{ action, nonce }` payload shape.
- Controller-specific extras like gallery `templates` and media-category `taxonomy` remain local to the relevant controllers.

### 2. Capability strings are duplicated instead of centralized

Files:
- [src/Policies/AdminCapabilityPolicy.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Policies/AdminCapabilityPolicy.php#L19)
- [src/Controllers/Admin/AdminMenuRegistrar.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Controllers/Admin/AdminMenuRegistrar.php#L14)
- [src/Modules/Gallery/Controllers/GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php#L40)
- [src/Modules/MediaLibrary/Controllers/MediaCategoryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaCategoryActionController.php#L35)
- [src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php#L48)
- [src/Modules/Admin/Controllers/TemplateSettingsActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/TemplateSettingsActionController.php#L31)

Why it is a problem:
- `manage_options` is repeated in many action definitions even though there is already an `AdminCapabilityPolicy`.
- If the plugin ever changes its admin capability model, the update becomes a scatter change.

Suggested direction:
- Introduce a shared capability constant or resolve capabilities through the policy layer instead of hardcoding them in each controller.

## POLA

### 1. Validation failures become `500 Request failed`

Files:
- [src/Controllers/AbstractActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Controllers/AbstractActionController.php#L122)
- [src/Controllers/AbstractActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Controllers/AbstractActionController.php#L184)
- [src/Modules/MediaLibrary/Controllers/MediaCategoryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaCategoryActionController.php#L80)
- [src/Modules/OpenAi/Services/OpenAiStoryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/OpenAi/Services/OpenAiStoryService.php#L22)

Why it is a problem:
- Several services/controllers throw `InvalidArgumentException` for bad input, but `AbstractActionController` catches all `Throwable` and always returns a generic `500`.
- From a client perspective, invalid input looks like a server fault.
- That is especially surprising because the frontend tracking controller returns proper `400`/`429` responses for comparable cases.

Suggested direction:
- Map input/validation exceptions to `400`, authorization exceptions to `403`, and reserve `500` for genuine server faults.

### 2. Permission behavior is inconsistent across admin surfaces

Files:
- [src/Policies/AdminCapabilityPolicy.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Policies/AdminCapabilityPolicy.php#L19)
- [src/Modules/Admin/Controllers/AdminGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/AdminGalleryController.php#L51)
- [src/Modules/Gallery/Controllers/GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php#L42)
- [src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/MediaLibrary/Controllers/MediaManagerActionController.php#L50)
- [src/Modules/OpenAi/Controllers/OpenAiActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/OpenAi/Controllers/OpenAiActionController.php#L40)

Why it is a problem:
- The admin page access path uses `prox_gallery/admin/can_manage`, but most AJAX controllers hardcode `manage_options`.
- OpenAI actions partly use `manage_options` and partly use `OpenAiModule::CAPABILITY_USE`.
- A developer can reasonably expect one permission hook/policy to govern the whole admin experience, but that is not true here.

Suggested direction:
- Define one explicit permission model for admin actions and route all controllers through it.
- If exceptions exist, make them deliberate and visible in a shared capability map.

## Single Source Of Truth

### 1. `prox_gallery_options` has split ownership, and one writer overwrites the other

Status:
- Fixed in [src/Modules/Admin/Services/TemplateCustomizationService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TemplateCustomizationService.php)
- Covered by regression test in [tests/TemplateCustomizationServiceTest.php](/home/marcelsanting/PhpstormProjects/prox-gallery/tests/TemplateCustomizationServiceTest.php)

Files:
- [src/States/AdminConfigurationState.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/States/AdminConfigurationState.php#L21)
- [src/Modules/Admin/Services/TemplateCustomizationService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TemplateCustomizationService.php#L46)
- [src/Modules/Admin/Services/TemplateCustomizationService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TemplateCustomizationService.php#L84)
- [src/Modules/OpenAi/Services/OpenAiSettingsService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/OpenAi/Services/OpenAiSettingsService.php#L31)
- [src/Modules/OpenAi/Services/OpenAiSettingsService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/OpenAi/Services/OpenAiSettingsService.php#L90)

Original problem:
- `TemplateCustomizationService` and `OpenAiSettingsService` both use `prox_gallery_options` as their backing store.
- `OpenAiSettingsService` treats that option as a shared document and merges its `openai` subtree before saving.
- `TemplateCustomizationService` writes only its own keys back to the full option, which can erase unrelated configuration stored by other services.

Resolution:
- `TemplateCustomizationService` now merges template keys into the existing options document before writing.
- This removes the overwrite bug while keeping the current shared option structure intact.

Remaining recommendation:
- Either introduce a single configuration repository for `prox_gallery_options`, or give each feature its own option key.
- Individual services should not each define their own persistence semantics for the same option record.

### 2. Gallery existence and membership are checked in two places, but only one is the repository

Files:
- [src/Modules/Frontend/Services/FrontendGalleryRepository.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryRepository.php#L22)
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L163)
- [src/Modules/Frontend/Controllers/FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L188)
- [src/Modules/Gallery/Models/GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L42)
- [src/Modules/Gallery/Models/GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L270)

Why it is a problem:
- Frontend rendering already has a repository abstraction for gallery data.
- `FrontendGalleryController` bypasses that abstraction and reads `prox_gallery_galleries` directly for `galleryExists()` and `galleryContainsImage()`.
- That creates two sources of truth for how gallery data is loaded and interpreted.

Where responsibility should live:
- Existence and membership checks belong in the gallery repository/service layer, not in the transport controller.
- The controller should call one dependency for gallery lookups instead of duplicating option parsing.

### 3. Gallery normalization rules are spread across controller, service, and model

Files:
- [src/Modules/Gallery/Controllers/GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php#L112)
- [src/Modules/Gallery/Controllers/GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php#L148)
- [src/Modules/Gallery/Services/GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php#L79)
- [src/Modules/Gallery/Services/GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php#L138)
- [src/Modules/Gallery/Services/GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php#L235)
- [src/Modules/Gallery/Models/GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L63)
- [src/Modules/Gallery/Models/GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L144)
- [src/Modules/Gallery/Models/GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L204)

Why it is a problem:
- The controller parses raw payload flags and override fields.
- The service applies some domain defaults and also normalizes transitions.
- The model normalizes stored values again when reading and writing.
- There is no single class that clearly owns the rules for valid gallery configuration.

Where responsibility should live:
- Put gallery normalization in one domain-level place, such as a value object/factory or a dedicated normalizer used by the repository and service.
- Controllers should only deserialize request payloads; models/repositories should persist already-normalized data.

Status:
- Fixed by introducing [GallerySettingsNormalizer.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Support/GallerySettingsNormalizer.php) as the canonical gallery settings normalizer.
- [GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php) now passes raw payload values into [GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php), instead of owning normalization rules itself.
- [GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php) now normalizes names, descriptions, templates, overrides, transitions, and visibility flags before persistence.
- [GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php) still normalizes values on read to keep legacy stored rows safe, but write-time normalization now comes from the service boundary.
- Regression coverage was added in [GalleryServiceTest.php](/home/marcelsanting/PhpstormProjects/prox-gallery/tests/GalleryServiceTest.php).

### 4. Development seeding bypasses the gallery persistence boundary

Files:
- [src/Modules/DevelopmentSeed/Services/DevelopmentSeedService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/DevelopmentSeed/Services/DevelopmentSeedService.php#L115)
- [src/Modules/DevelopmentSeed/Services/DevelopmentSeedService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/DevelopmentSeed/Services/DevelopmentSeedService.php#L128)
- [src/Modules/Gallery/Models/GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L20)
- [src/Modules/Gallery/Services/GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php#L79)

Why it is a problem:
- `DevelopmentSeedService` clears galleries with `delete_option('prox_gallery_galleries')` directly.
- Normal gallery writes go through `GalleryService` and `GalleryCollectionModel`.
- That means seeding uses a separate persistence path with different invariants.

Where responsibility should live:
- Resetting or clearing gallery data should go through the same repository/service boundary as normal gallery operations.
- Seed code should orchestrate domain services, not mutate storage directly.

### 5. `GalleryModel` looks like a domain source, but it is not actually used as one

Files:
- [src/Modules/Gallery/Models/GalleryModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryModel.php#L12)
- [src/Modules/Frontend/Services/FrontendGalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryService.php#L20)
- [src/Modules/Frontend/Services/FrontendGalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryService.php#L35)

Why it is a problem:
- `GalleryModel` is injected into `FrontendGalleryService`, but the service reads gallery data from `FrontendGalleryRepository`, not from the model.
- The model is effectively dead state passed into a boot hook, which makes the ownership of gallery state unclear.
- A class named `GalleryModel` implies authoritative gallery data, but it is not the authoritative source.

Where responsibility should live:
- If the repository is the source of truth, remove the inert model from the service contract.
- If a domain model is intended, it should encapsulate real gallery state and behavior instead of existing only for signaling.

Status:
- Fixed by removing [GalleryModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryModel.php) from [FrontendGalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Services/FrontendGalleryService.php) and from bootstrap wiring in [AppBindingRegistrar.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/AppBindingRegistrar.php).
- Frontend gallery boot signaling now exposes only the state and policy objects it actually depends on.
- The repository remains the single frontend source of gallery data.

## Frontend Atomic Plan

Goal:
- Move the admin React/TypeScript UI toward clear atomic reuse boundaries so state, domain actions, and presentational building blocks each live in the correct layer.
- Avoid splitting files just for the sake of smaller files; only extract parts that represent stable reusable patterns.

### Current frontend issues driving the plan

Files:
- [assets/admin/src/app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L23)
- [assets/admin/src/features/galleries/components/galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L45)
- [assets/admin/src/features/media-manager/components/media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L11)
- [assets/admin/src/features/settings/settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L90)
- [assets/admin/src/features/galleries/use-galleries-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/use-galleries-state.js#L37)
- [assets/admin/src/features/media-manager/use-media-manager-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/use-media-manager-state.js#L43)

Observed problems:
- `App` is acting as the integration hub for controller creation, feature loading, dashboard fetching, and cross-feature event handlers.
- `GalleriesLibraryCard`, `MediaFilesCard`, and `SettingsSection` are large “all-in-one” components with mixed UI, local workflow state, formatting, and domain interaction logic.
- Feature hooks follow similar async-load patterns but are hand-written separately.
- Several small interactive UI patterns already exist, but they are defined inline inside feature components instead of becoming reusable atoms or molecules.

### Target layering

Recommended frontend layering:
1. `ui/atoms`
- Stateless primitives with no domain knowledge.
- Examples: `IconActionButton`, `StatusMessage`, `EmptyState`, `MetricValue`, `Chip`, `FieldLabel`, `ToggleField`, `SelectField`, `TextField`, `TextareaField`, `ToolbarButton`.

2. `ui/molecules`
- Small compositions of atoms with stable UI behavior.
- Examples: `FilterPanel`, `PropertyRow`, `EntityMetaChips`, `SectionToolbar`, `SaveBar`, `LoadingBlock`, `DebugPayloadPanel`, `TagListEditor`.

3. `features/*/components`
- Feature-aware views that compose molecules and atoms, but do not own transport/controller wiring.
- Examples: `GalleryCreatePanel`, `GalleryDisplaySettingsPanel`, `GalleryImagePickerPanel`, `MediaListToolbar`, `MediaFiltersPanel`, `TemplateSettingsForm`, `OpenAiConnectionForm`, `PromptTemplatesEditor`.

4. `features/*/hooks`
- Feature state and workflows.
- Hooks should own async loading, mutation status, optimistic refresh/reload rules, and payload normalization for that feature.

5. `features/*/containers`
- Thin feature entry components that create the feature controller set, call hooks, and pass prepared props to feature views.
- This is the layer that should absorb much of the work currently living in `App`.

6. `modules/*/controllers`
- Transport adapters only.
- Keep request/response typing and dispatch here, but keep UI shaping and view-specific transformation out.

### Phase 1: Create shared atoms and form primitives

Scope:
- Extract repeated button, message, and form field patterns first.

Candidates:
- Extract the inline `ActionIconButton` from [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L7) into a shared `ui/atoms/icon-action-button`.
- Replace repeated raw action buttons in [media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L108) and [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L758) with a shared `ToolbarButton` / `PrimaryActionButton`.
- Add shared field atoms for labeled inputs, selects, checkboxes, and textareas used repeatedly in [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L400) and gallery forms in [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L293).
- Add shared `StatusMessage` and `LoadingText` atoms to replace scattered inline loading/error paragraphs.

Expected result:
- Visual consistency improves immediately.
- Future extractions stop copying Tailwind-heavy button/field markup.

### Phase 2: Extract reusable molecules from large feature cards

Scope:
- Pull out cohesive UI groups that already have clear boundaries.

Candidates from media manager:
- `MediaListToolbar` from [media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L103)
- `MediaFiltersPanel` from [media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L147)
- `MediaBrowserShell` to unify loading / empty / list branching from [media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L185)

Candidates from galleries:
- `GalleryMetaChips` from chip building logic in [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L79)
- `GalleryCreateForm` from create-state and create-submit flow in [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L171)
- `GalleryDisplaySettingsForm` from edit-state initialization and save flow in [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L377)

Candidates from settings:
- `SettingsSidebarNav` from [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L611)
- `TemplateEditorSection` from [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L652)
- `OpenAiConnectionForm`, `LanguageListEditor`, and `PromptTemplatesEditor` from [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L385)

Expected result:
- Large card components become feature shells instead of feature monoliths.
- Shared substructures become available to reuse in future screens or modals.

### Phase 3: Move workflow state out of feature view components

Scope:
- Co-locate workflow state with the feature, but outside presentational components.

Actions:
- Move create/edit gallery workflow state from [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L171) into dedicated hooks such as `useGalleryCreateForm`, `useGalleryDisplaySettings`, and `useGalleryImageSelection`.
- Move media modal/filter orchestration from [media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L28) into hooks such as `useMediaFilters` and `useMediaModalState`.
- Split the template settings and OpenAI settings state in [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L90) into separate hooks: `useTemplateSettingsForm` and `useOpenAiSettingsForm`.

Expected result:
- Presentational components become easier to reuse and test.
- Form state and mutation rules become the single source of truth for each feature workflow.

### Phase 4: Introduce feature containers and move orchestration out of `App`

Scope:
- Reduce `App` to routing/menu composition only.

Actions:
- Create feature containers such as `DashboardContainer`, `MediaManagerContainer`, `GalleriesContainer`, and `SettingsContainer`.
- Move controller instantiation from [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L29) into feature-specific factories/hooks like `useMediaManagerControllers`, `useGalleryControllers`, `useSettingsControllers`.
- Move dashboard fetch state from [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L244) into `useDashboardSummary`.
- Keep `App` responsible only for active menu selection and rendering the right container.

Expected result:
- Each feature owns its own dependencies.
- Cross-feature coupling in `App` drops significantly.

Status:
- Fixed by moving admin action-controller construction out of [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx) into shared feature hooks in [action-controller-hooks.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/lib/action-controller-hooks.js).
- [DashboardSection.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/dashboard/dashboard-section.jsx), [MediaManagerSection.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/media-manager-section.jsx), [GalleriesSection.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/galleries-section.jsx), and [SettingsSection.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx) now own their feature dependencies and loading orchestration.
- [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx) is reduced to menu selection and section visibility.

### Phase 5: Standardize async resource hooks

Scope:
- Remove duplicated load/reload/error patterns across hooks.

Actions:
- Replace the hand-rolled reducer duplication in [use-galleries-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/use-galleries-state.js#L1) and [use-media-manager-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/use-media-manager-state.js#L1) with a shared async resource hook pattern.
- Introduce a generic internal helper like `useAsyncCollection` or `useLoadableResource` for `{ data, isLoading, error, hasLoaded, load, reload }`.
- Keep feature-specific mutations in feature hooks, but base list-loading behavior on one shared abstraction.

Expected result:
- List screens behave consistently.
- Error/loading semantics become standardized.

### Phase 6: Tighten TypeScript boundaries before broad extraction

Scope:
- Improve type safety where reuse is intended.

Actions:
- Convert mixed `.js` feature hooks/components that own domain payloads into `.ts`/`.tsx` first, especially [use-galleries-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/use-galleries-state.js#L37) and [use-media-manager-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/use-media-manager-state.js#L43).
- Create shared frontend domain types for `Gallery`, `TrackedImage`, `TemplateSettings`, `OpenAiSettings`, and form payloads.
- Keep type definitions close to the feature boundary, then promote to shared types only when two or more features truly use them.

Expected result:
- Atomic reuse does not become prop-shape guesswork.
- Extracted atoms and molecules get stable contracts.

### Guardrails

Do:
- Extract only stable UI patterns used in at least two places or clearly destined for reuse.
- Keep domain-specific behavior in feature hooks/containers.
- Prefer one clear reusable component over many tiny wrappers.

Do not:
- Put feature business rules into `components/ui`.
- Move controller/network knowledge into atoms or molecules.
- Split a component into many files if the pieces are not independently reusable.

### Suggested implementation order

1. Create shared atoms for buttons, fields, and messages.
2. Extract `MediaListToolbar`, `MediaFiltersPanel`, and `SettingsSidebarNav`.
3. Split `SettingsSection` into `TemplateSettingsPanel` and `OpenAiSettingsPanel` with dedicated hooks.
4. Split `GalleriesLibraryCard` into create/edit/image-picker subcomponents and hooks.
5. Add feature containers and shrink `App` to section routing.
6. Standardize async resource hooks and migrate remaining feature state to typed hooks.

## Prioritized Fix Sequence

This is the recommended order for addressing the review. The sequence is based on risk reduction, source-of-truth correction, and minimizing rework in later frontend/backend refactors.

### Phase 1: Fix the highest-risk source-of-truth issues

1. `prox_gallery_options` overwrite bug
- Fixed by making [TemplateCustomizationService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Services/TemplateCustomizationService.php#L84) merge into the existing option document instead of replacing it.
- Regression coverage added in [tests/TemplateCustomizationServiceTest.php](/home/marcelsanting/PhpstormProjects/prox-gallery/tests/TemplateCustomizationServiceTest.php).
- Follow-up still recommended:
  - introduce a clearer configuration repository or per-feature option ownership later

2. Stop bypassing gallery storage/repository boundaries
- Remove direct gallery option reads from [FrontendGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Frontend/Controllers/FrontendGalleryController.php#L163).
- Remove direct gallery option mutation in [DevelopmentSeedService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/DevelopmentSeed/Services/DevelopmentSeedService.php#L128).
- Outcome:
  - gallery existence, membership, reset, and lookup all go through one repository/service path

### Phase 2: Correct responsibility boundaries in the backend

3. Centralize gallery normalization rules
- Consolidate payload/domain/storage normalization currently spread across:
  - [GalleryActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Controllers/GalleryActionController.php#L112)
  - [GalleryService.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Services/GalleryService.php#L79)
  - [GalleryCollectionModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryCollectionModel.php#L42)
- Outcome:
  - one domain-level normalization path for gallery settings and overrides
- Status: fixed, with read-side legacy normalization retained in the repository model

4. Fix error semantics in AJAX action handling
- Update [AbstractActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Controllers/AbstractActionController.php#L122) so validation/input errors no longer collapse into generic `500 Request failed`.
- Outcome:
  - input errors become `400`
  - permission errors remain `403`
  - server faults remain `500`
- Status:
  - fixed by mapping `InvalidArgumentException` responses to `400` in [AbstractActionController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Controllers/AbstractActionController.php) and covering the response contract in [AbstractActionControllerTest.php](/home/marcelsanting/PhpstormProjects/prox-gallery/tests/AbstractActionControllerTest.php)

5. Unify admin capability ownership
- Align menu access, policy, and action-controller capability checks across:
  - [AdminCapabilityPolicy.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Policies/AdminCapabilityPolicy.php#L19)
  - [AdminGalleryController.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Admin/Controllers/AdminGalleryController.php#L51)
  - action controllers that hardcode `manage_options`
- Outcome:
  - one explicit permission model for the admin surface
- Status:
  - fixed by centralizing the shared admin capability in [AdminCapabilityPolicy.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Policies/AdminCapabilityPolicy.php) and reusing it from the admin menu registrar, admin action controllers, and media-category taxonomy registration

### Phase 3: Reduce composition-root and bootstrap fragility

6. Break up the `App` composition root
- Refactor [App.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Bootstrap/App.php#L72) so module/provider-level registration owns bindings instead of one central god-class.
- Outcome:
  - less bootstrap coupling
  - lower change surface when adding features
  - fewer merge conflicts
- Status:
  - fixed by splitting binding registration and manager population into dedicated bootstrap collaborators while leaving `App` as the lifecycle orchestrator

7. Reassess dead or misleading backend types
- Resolve whether [GalleryModel.php](/home/marcelsanting/PhpstormProjects/prox-gallery/src/Modules/Gallery/Models/GalleryModel.php#L12) should become meaningful or be removed from the frontend service contract.
- Outcome:
  - clearer ownership of gallery state
- Status:
  - fixed by removing the inert gallery model from the frontend service contract and bootstrap bindings

### Phase 4: Restructure the frontend around feature boundaries

8. Shrink `App` and move orchestration into feature containers/hooks
- Reduce the integration burden in [app.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/app.jsx#L23).
- This should happen after backend truth/responsibility fixes so the frontend refactor targets stable contracts.
- Outcome:
  - `App` becomes section routing/navigation only
  - each feature owns its own controllers and workflows
- Status:
  - fixed by moving controller construction and feature loading into section-owned hooks/containers while leaving `App` responsible for menu selection only

9. Split large feature components into feature-level units
- Refactor the largest frontend monoliths:
  - [galleries-library-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/components/galleries-library-card.jsx#L45)
  - [media-files-card.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/components/media-files-card.jsx#L11)
  - [settings-section.jsx](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/settings/settings-section.jsx#L90)
- Outcome:
  - reusable feature molecules
  - state moved into hooks
  - cleaner atomic reuse

10. Standardize async resource hooks and tighten TS boundaries
- Consolidate duplicated reducer/loading patterns in:
  - [use-galleries-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/galleries/use-galleries-state.js#L37)
  - [use-media-manager-state.js](/home/marcelsanting/PhpstormProjects/prox-gallery/assets/admin/src/features/media-manager/use-media-manager-state.js#L43)
- Then migrate the most domain-heavy hooks/components to stronger TS types.
- Outcome:
  - predictable async behavior
  - safer reuse contracts

### Recommended execution strategy

Recommended order of actual implementation:
1. frontend component splitting
2. shared async hooks and TS tightening
