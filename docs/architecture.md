# Responsibility boundaries

## Implemented structure

| Area                                               | Owns                                                                           | Does not own                                   |
| -------------------------------------------------- | ------------------------------------------------------------------------------ | ---------------------------------------------- |
| `app.jsx`                                          | Stable bootstrap configuration, navigation and the workspace shell             | HTTP transport or mutation rules               |
| Feature sections and hooks                         | Use-case orchestration, draft ownership and loading state                      | WordPress storage implementation               |
| GalleryEditor / GalleryImagesDialog / image panels | One editing task each, labelled controls and local interaction                 | AJAX URL construction or authentication policy |
| TypeScript action controllers                      | Typed request/response transport                                               | Component state or navigation                  |
| `useLoadableCollection`                            | Request deduplication, cached results and obsolete response rejection          | Gallery/media-specific policy                  |
| PHP action controllers                             | Method, action capability, nonce, object permission and HTTP error translation | Frontend form state                            |
| PHP feature services                               | Current application operations and validation                                  | Browser rendering                              |
| PHP repositories/models                            | WordPress persistence                                                          | UI orchestration                               |
| Bootstrap registrar                                | Explicit dependency construction                                               | User-facing decisions                          |

The former gallery list, image modal and settings section each mixed substantial state management and repeated presentation. They now compose focused views and hooks. The six-step create wizard and duplicated gallery list/card implementation were removed. A shared native dialog provides focus isolation, Escape handling and focus restoration. Native controls provide keyboard ordering for images.

The shared admin config type replaces the second, drifting type definition in controller hooks. Unused media controller dependencies and obsolete UI components were removed. The duplicate gallery override mapping lives in one small pure function. WordPress remains the backend framework; this change does not claim to make all existing services framework-independent.

## Rules for future changes

1. Put persistence changes in a repository/model; route them through a named application operation.
2. Keep object permission checks at every externally callable boundary. A valid nonce is not permission to edit an attachment.
3. Do not publish pages or alter navigation as a side effect of saving a collection.
4. Keep one owner for each draft and collection request. Keep effect dependencies stable and reject obsolete responses.
5. Do not add interfaces or managers unless they protect a real integration boundary or have an actual alternate implementation.

## Atomic persistence and AI reservations

`Storage/AtomicOptionStore` owns uncached reads and bounded compare-and-swap writes to existing WordPress option rows. A byte-exact SQL comparison detects another writer; the operation retries against the new value. Callbacks must be free of external side effects. Exhausted contention returns HTTP 503; it never falls back to an unconditional overwrite. Database errors fail explicitly. Core option caches are invalidated after a commit; plugin reads bypass those caches so a persistent cache cannot reintroduce stale writes.

Gallery creation, membership changes, media indexing, tracking increments and both settings sections use this boundary. Explicit replacement/clear operations still replace the requested collection; concurrent full edits of the same field use the last committed edit. CLI cleanup removes only the IDs found stale, preserving concurrent additions. Existing option keys and payloads remain readable without migration. The 500-entry eviction has been removed; originals and earlier index rows remain intact. Rows evicted by older plugin versions cannot be reconstructed automatically; use the existing CLI import to reindex those attachments.

`OpenAiUsageService` reserves the user and site counters in one atomic change before `OpenAiStoryService` dispatches its HTTP request. Defaults are 20 attempts per user and 100 per site per UTC day. Administrators are included; zero disables generation. Failed requests and timeouts count, because billing may already have happened. Local validation failures do not count. The previous day's counters are replaced on the first new reservation; user IDs are retained for the current day only. The provider request has an 800-token output cap. Request quotas are not currency budgets.

The stores and list endpoints still load complete collections. This removes the data-loss threshold and lost-update races, but does not establish performance at arbitrary scale. Database tables, pagination and statistics retention are separate work if measurements demand them. The public per-IP tracking throttle remains best-effort and analytics are not billing-grade metrics.
