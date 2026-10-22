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

## Remaining persistence work

The current option-backed stores are retained to avoid an untested data migration. They still need explicit redesign for larger installations:

- `UploadedImageQueueModel` silently retains at most 500 records in `remember()`. This is tracking-index eviction, not deletion of original WordPress files. Server-side pagination and an attachment-meta/query-backed index should replace this limit.
- Gallery and statistics writes replace shared option values. Concurrent writers can lose changes. Use atomic counters or bounded tables with a tested migration and rollback path.
- Statistics have no retention window. Cache/read policies and retention belong in persistence, not components.
- `OpenAiStoryService` still combines image preparation and the external request. If the integration grows, isolate the provider transport with tests rather than adding more flags to this service.

These are known follow-up requirements, not claims that the redesign makes storage production-ready at arbitrary scale.
